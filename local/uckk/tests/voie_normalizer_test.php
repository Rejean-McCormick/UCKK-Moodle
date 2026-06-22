<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Atlas Voie normalizer.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use JsonException;
use local_uckk\local\atlas\voie_normalizer;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\atlas\voie_normalizer.
 *
 * The normalizer prepares Atlas Voie arrays for validation, mapping and public
 * projection. It may trim strings, add documented optional defaults and restore
 * stable ordering, but it must not hide structural errors, resolve forbidden
 * aliases, or inject Faculty Profile / Moodle runtime fields.
 *
 * @covers \local_uckk\local\atlas\voie_normalizer
 */
final class voie_normalizer_test extends advanced_testcase {
    /** Atlas schema version. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Required Atlas Voie top-level fields. */
    private const REQUIRED_TOP_LEVEL_FIELDS = [
        'schema_version',
        'voie_id',
        'code',
        'nom',
        'domaine_operatoire',
        'niveau_vise',
        'titre_symbolique',
        'parchemin',
        'statut',
        'definition_courte',
        'angle_fondamental',
        'competence_centrale',
        'seuils_progression',
        'cours_conceptuels',
        'projet_final',
        'limites_ethiques',
        'relations_intervoies',
        'tags',
    ];

    /** Documented optional Atlas Voie top-level fields. */
    private const OPTIONAL_TOP_LEVEL_DEFAULTS = [
        'titre_interne_vise' => '',
        'version' => '0.2 — normalisation Atlas',
        'role_dans_atlas' => '',
        'principe_fondateur' => '',
        'distinctions_clefs' => [],
        'risques_specifiques' => [],
        'exigences_gouvernance' => [],
    ];

    /** Fields that belong to Faculty Profiles or public page rendering, not Atlas Voies. */
    private const FORBIDDEN_PUBLIC_PAGE_FIELDS = [
        'faculty_id',
        'slug',
        'visibility',
        'source_atlas',
        'moodle',
        'identity',
        'seo',
        'hero',
        'navigation',
        'sections',
        'atlas_projection',
        'dynamic_blocks',
        'featured_blocks',
        'faq',
        'contact',
        'governance',
        'cache',
        'html',
        'rendered',
    ];

    /** Forbidden legacy or drifted Voie aliases. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /**
     * The normalizer class and canonical normalize() method must exist.
     */
    public function test_normalizer_exposes_canonical_normalize_method(): void {
        $this->assertTrue(
            class_exists(voie_normalizer::class),
            voie_normalizer::class . ' must exist.'
        );

        $this->assertTrue(
            method_exists(voie_normalizer::class, 'normalize'),
            voie_normalizer::class . ' must expose normalize(array $voie).'
        );
    }

    /**
     * normalize() must return an Atlas Voie array.
     */
    public function test_normalize_returns_array(): void {
        $normalized = self::normalizer()->normalize(self::valid_voie());

        $this->assertIsArray($normalized);
        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $normalized['schema_version']);
        $this->assertSame('voie_grand_jeu_social', $normalized['voie_id']);
        $this->assertSame('GJS', $normalized['code']);
    }

    /**
     * normalize() must trim documented string values recursively.
     */
    public function test_normalize_trims_documented_string_values_recursively(): void {
        $voie = self::valid_voie();

        $voie['schema_version'] = '  ' . self::ATLAS_SCHEMA_VERSION . '  ';
        $voie['voie_id'] = '  voie_grand_jeu_social  ';
        $voie['code'] = '  GJS  ';
        $voie['nom'] = '  Voie du Grand Jeu social  ';
        $voie['seuils_progression'][0] = '  Identifier les acteurs.  ';
        $voie['cours_conceptuels'][0]['cours_id'] = '  GJS101  ';
        $voie['cours_conceptuels'][0]['nom'] = '  Cartographie du Grand Jeu social  ';
        $voie['cours_conceptuels'][0]['concept_maitre']['concept_id'] = '  gjs_concept_maitre_1  ';
        $voie['cours_conceptuels'][0]['concept_maitre']['nom'] = '  Concept maître 1  ';
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['concept_id'] = '  gjs_concept_associe_1_a  ';
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['nom'] = '  Concept associé 1A  ';
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines'][] = '  notion fine  ';
        $voie['cours_conceptuels'][0]['criteres_passage'][0] = '  Critère de passage 1.  ';
        $voie['tags'][0] = '  grand-jeu-social  ';

        $normalized = self::normalizer()->normalize($voie);

        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $normalized['schema_version']);
        $this->assertSame('voie_grand_jeu_social', $normalized['voie_id']);
        $this->assertSame('GJS', $normalized['code']);
        $this->assertSame('Voie du Grand Jeu social', $normalized['nom']);
        $this->assertSame('Identifier les acteurs.', $normalized['seuils_progression'][0]);
        $this->assertSame('GJS101', $normalized['cours_conceptuels'][0]['cours_id']);
        $this->assertSame('Cartographie du Grand Jeu social', $normalized['cours_conceptuels'][0]['nom']);
        $this->assertSame('gjs_concept_maitre_1', $normalized['cours_conceptuels'][0]['concept_maitre']['concept_id']);
        $this->assertSame('Concept maître 1', $normalized['cours_conceptuels'][0]['concept_maitre']['nom']);
        $this->assertSame('gjs_concept_associe_1_a', $normalized['cours_conceptuels'][0]['concepts_associes'][0]['concept_id']);
        $this->assertSame('Concept associé 1A', $normalized['cours_conceptuels'][0]['concepts_associes'][0]['nom']);
        $this->assertSame('notion fine', $normalized['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines'][0]);
        $this->assertSame('Critère de passage 1.', $normalized['cours_conceptuels'][0]['criteres_passage'][0]);
        $this->assertSame('grand-jeu-social', $normalized['tags'][0]);
    }

    /**
     * normalize() must preserve all required Atlas Voie fields.
     */
    public function test_normalize_preserves_required_top_level_fields(): void {
        $normalized = self::normalizer()->normalize(self::valid_voie());

        foreach (self::REQUIRED_TOP_LEVEL_FIELDS as $field) {
            $this->assertArrayHasKey($field, $normalized, 'Missing required Atlas field after normalization: ' . $field);
        }
    }

    /**
     * normalize() may add only documented optional Atlas fields.
     */
    public function test_normalize_adds_documented_optional_defaults(): void {
        $voie = self::valid_voie();

        foreach (array_keys(self::OPTIONAL_TOP_LEVEL_DEFAULTS) as $field) {
            unset($voie[$field]);
        }

        $normalized = self::normalizer()->normalize($voie);

        foreach (self::OPTIONAL_TOP_LEVEL_DEFAULTS as $field => $expected) {
            $this->assertArrayHasKey($field, $normalized);
            $this->assertSame($expected, $normalized[$field]);
        }
    }

    /**
     * Existing documented optional Atlas fields must be preserved and normalized.
     */
    public function test_normalize_preserves_existing_documented_optional_fields(): void {
        $voie = self::valid_voie();
        $voie['titre_interne_vise'] = '  Architecte social  ';
        $voie['version'] = '  0.2 — normalisation Atlas  ';
        $voie['role_dans_atlas'] = '  Voie fondatrice  ';
        $voie['principe_fondateur'] = '  Lire les jeux sociaux.  ';
        $voie['distinctions_clefs'] = ['  distinction 1  '];
        $voie['risques_specifiques'] = ['  risque 1  '];
        $voie['exigences_gouvernance'] = ['  exigence 1  '];

        $normalized = self::normalizer()->normalize($voie);

        $this->assertSame('Architecte social', $normalized['titre_interne_vise']);
        $this->assertSame('0.2 — normalisation Atlas', $normalized['version']);
        $this->assertSame('Voie fondatrice', $normalized['role_dans_atlas']);
        $this->assertSame('Lire les jeux sociaux.', $normalized['principe_fondateur']);
        $this->assertSame(['distinction 1'], $normalized['distinctions_clefs']);
        $this->assertSame(['risque 1'], $normalized['risques_specifiques']);
        $this->assertSame(['exigence 1'], $normalized['exigences_gouvernance']);
    }

    /**
     * Courses must be returned in ordre 1..10.
     */
    public function test_normalize_orders_cours_conceptuels_by_ordre(): void {
        $voie = self::valid_voie();

        $voie['cours_conceptuels'] = [
            $voie['cours_conceptuels'][2],
            $voie['cours_conceptuels'][0],
            $voie['cours_conceptuels'][9],
            $voie['cours_conceptuels'][1],
            $voie['cours_conceptuels'][4],
            $voie['cours_conceptuels'][3],
            $voie['cours_conceptuels'][6],
            $voie['cours_conceptuels'][5],
            $voie['cours_conceptuels'][8],
            $voie['cours_conceptuels'][7],
        ];

        $normalized = self::normalizer()->normalize($voie);

        $orders = array_map(
            static fn(array $course): int => (int) $course['ordre'],
            $normalized['cours_conceptuels']
        );

        $courseids = array_map(
            static fn(array $course): string => (string) $course['cours_id'],
            $normalized['cours_conceptuels']
        );

        $this->assertSame(range(1, 10), $orders);
        $this->assertSame(
            ['GJS101', 'GJS102', 'GJS103', 'GJS104', 'GJS105', 'GJS106', 'GJS107', 'GJS108', 'GJS109', 'GJS110'],
            $courseids
        );
    }

    /**
     * Empty string items in simple string lists may be removed.
     */
    public function test_normalize_removes_empty_values_from_simple_string_lists(): void {
        $voie = self::valid_voie();

        $voie['seuils_progression'] = [
            '  Identifier les acteurs.  ',
            '',
            '   ',
            'Cartographier les tensions.',
        ];

        $voie['tags'] = [
            '  grand-jeu-social  ',
            '',
            '   ',
            'systemes-sociaux',
        ];

        $voie['cours_conceptuels'][0]['criteres_passage'] = [
            '  Critère de passage 1.  ',
            '',
            '   ',
            'Critère de passage 2.',
        ];

        $normalized = self::normalizer()->normalize($voie);

        $this->assertSame(
            ['Identifier les acteurs.', 'Cartographier les tensions.'],
            $normalized['seuils_progression']
        );

        $this->assertSame(
            ['grand-jeu-social', 'systemes-sociaux'],
            $normalized['tags']
        );

        $this->assertSame(
            ['Critère de passage 1.', 'Critère de passage 2.'],
            $normalized['cours_conceptuels'][0]['criteres_passage']
        );
    }

    /**
     * normalize() must not inject Faculty Profile or public page fields.
     */
    public function test_normalize_does_not_inject_faculty_or_public_page_fields(): void {
        $normalized = self::normalizer()->normalize(self::valid_voie());

        foreach (self::FORBIDDEN_PUBLIC_PAGE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $normalized, 'Forbidden non-Atlas field was injected: ' . $field);
        }
    }

    /**
     * normalize() must remove incoming Faculty Profile or public page drift fields.
     */
    public function test_normalize_removes_incoming_faculty_or_public_page_fields(): void {
        $voie = self::valid_voie();

        foreach (self::FORBIDDEN_PUBLIC_PAGE_FIELDS as $field) {
            $voie[$field] = 'drift';
        }

        $normalized = self::normalizer()->normalize($voie);

        foreach (self::FORBIDDEN_PUBLIC_PAGE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $normalized, 'Forbidden non-Atlas field survived normalization: ' . $field);
        }
    }

    /**
     * normalize() must not convert forbidden aliases into canonical voie_id values.
     */
    public function test_normalize_does_not_rewrite_forbidden_voie_id_aliases(): void {
        foreach (self::FORBIDDEN_VOIE_IDS as $forbidden) {
            $voie = self::valid_voie();
            $voie['voie_id'] = '  ' . $forbidden . '  ';

            $normalized = self::normalizer()->normalize($voie);

            $this->assertSame(
                $forbidden,
                $normalized['voie_id'],
                'The normalizer must not hide forbidden aliases from the validator.'
            );
        }
    }

    /**
     * normalize() must not silently repair invalid schema versions.
     */
    public function test_normalize_does_not_rewrite_invalid_schema_version(): void {
        $voie = self::valid_voie();
        $voie['schema_version'] = '  UCKK-ATLAS-0.1  ';

        $normalized = self::normalizer()->normalize($voie);

        $this->assertSame(
            'UCKK-ATLAS-0.1',
            $normalized['schema_version'],
            'The normalizer must not hide invalid schema versions from the validator.'
        );
    }

    /**
     * normalize() must not convert invalid structural collection types to valid
     * arrays, because that would hide errors from voie_validator.
     */
    public function test_normalize_does_not_silently_repair_invalid_structural_collection_types(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines'] = 'not-an-array';
        $voie['cours_conceptuels'][0]['criteres_passage'] = 'not-an-array';
        $voie['relations_intervoies'] = 'not-an-array';

        $normalized = self::normalizer()->normalize($voie);

        $this->assertSame(
            'not-an-array',
            $normalized['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines']
        );

        $this->assertSame(
            'not-an-array',
            $normalized['cours_conceptuels'][0]['criteres_passage']
        );

        $this->assertSame(
            'not-an-array',
            $normalized['relations_intervoies']
        );
    }

    /**
     * normalize() must be idempotent.
     */
    public function test_normalize_is_idempotent(): void {
        $normalized = self::normalizer()->normalize(self::valid_voie());
        $renormalized = self::normalizer()->normalize($normalized);

        $this->assertSame($normalized, $renormalized);
    }

    /**
     * normalize() must be idempotent for all manifest-declared Atlas Voie files.
     */
    public function test_normalize_is_idempotent_for_all_manifest_declared_voie_files(): void {
        foreach (self::manifest_items() as $item) {
            $this->assertArrayHasKey('file', $item);
            $this->assertIsString($item['file']);

            $voie = self::read_json_file(self::voies_dir() . DIRECTORY_SEPARATOR . $item['file']);

            $normalized = self::normalizer()->normalize($voie);
            $renormalized = self::normalizer()->normalize($normalized);

            $this->assertSame(
                $normalized,
                $renormalized,
                'Normalization must be idempotent for ' . $item['file']
            );
        }
    }

    /**
     * Instantiate the normalizer under test.
     *
     * @return voie_normalizer
     */
    private static function normalizer(): voie_normalizer {
        return new voie_normalizer();
    }

    /**
     * Build a complete valid Atlas Voie fixture.
     *
     * @return array<string, mixed>
     */
    private static function valid_voie(): array {
        return [
            'schema_version' => self::ATLAS_SCHEMA_VERSION,
            'voie_id' => 'voie_grand_jeu_social',
            'code' => 'GJS',
            'nom' => 'Voie du Grand Jeu social',
            'domaine_operatoire' => 'Systèmes sociaux',
            'niveau_vise' => 'Puissance opératoire',
            'titre_symbolique' => 'Maître du Grand Jeu social',
            'parchemin' => 'Parchemin de Puissance opératoire — Voie du Grand Jeu social',
            'statut' => 'Voie fondatrice UCKK',
            'definition_courte' => 'Lecture stratégique des systèmes sociaux.',
            'angle_fondamental' => 'Comprendre les jeux sociaux comme systèmes d’action.',
            'competence_centrale' => 'Cartographier et intervenir dans un système social complexe.',
            'seuils_progression' => [
                'Identifier les acteurs.',
                'Cartographier les tensions.',
                'Formuler une intervention responsable.',
            ],
            'titre_interne_vise' => 'Architecte social',
            'version' => '0.2 — normalisation Atlas',
            'role_dans_atlas' => 'Voie fondatrice',
            'principe_fondateur' => 'Lire les jeux sociaux sans les confondre avec les personnes.',
            'distinctions_clefs' => [
                'Acteur',
                'Rôle',
                'Institution',
            ],
            'risques_specifiques' => [
                'Manipulation sociale',
            ],
            'exigences_gouvernance' => [
                'Consentement',
                'Traçabilité',
            ],
            'cours_conceptuels' => self::valid_courses('GJS'),
            'projet_final' => [
                'type' => 'projet',
                'nom' => 'Cartographie opératoire du Grand Jeu social',
                'description' => 'Projet final de synthèse.',
            ],
            'limites_ethiques' => [
                'Ne pas manipuler les personnes ou les groupes.',
                'Ne pas utiliser la cartographie sociale pour contourner le consentement.',
            ],
            'relations_intervoies' => [
                [
                    'voie_id' => 'voie_sciences_politiques',
                    'type' => 'relation',
                    'description' => 'Relation avec les institutions et la décision collective.',
                ],
            ],
            'tags' => [
                'grand-jeu-social',
                'systemes-sociaux',
            ],
        ];
    }

    /**
     * Build 10 valid conceptual courses for a Voie.
     *
     * @param string $code Course prefix.
     * @return array<int, array<string, mixed>>
     */
    private static function valid_courses(string $code): array {
        $courses = [];

        for ($i = 1; $i <= 10; $i++) {
            $number = 100 + $i;
            $courseid = $code . $number;

            $courses[] = [
                'cours_id' => $courseid,
                'ordre' => $i,
                'nom' => 'Cours conceptuel ' . $i,
                'concept_maitre' => [
                    'concept_id' => strtolower($code) . '_concept_maitre_' . $i,
                    'nom' => 'Concept maître ' . $i,
                    'type' => 'concept_maitre',
                    'definition_courte' => 'Définition courte du concept maître ' . $i . '.',
                    'fonction_pedagogique' => 'Fonction pédagogique du concept maître ' . $i . '.',
                ],
                'concepts_associes' => [
                    [
                        'concept_id' => strtolower($code) . '_concept_associe_' . $i . '_a',
                        'nom' => 'Concept associé ' . $i . 'A',
                        'type' => 'concept_associe',
                        'definition_courte' => 'Définition courte du concept associé ' . $i . 'A.',
                        'role_dans_le_concept_maitre' => 'Premier appui du concept maître.',
                        'notions_fines' => [
                            'Notion fine ' . $i . 'A',
                        ],
                    ],
                    [
                        'concept_id' => strtolower($code) . '_concept_associe_' . $i . '_b',
                        'nom' => 'Concept associé ' . $i . 'B',
                        'type' => 'concept_associe',
                        'definition_courte' => 'Définition courte du concept associé ' . $i . 'B.',
                        'role_dans_le_concept_maitre' => 'Second appui du concept maître.',
                        'notions_fines' => [],
                    ],
                ],
                'artefact_maitrise' => [
                    'type' => 'dossier',
                    'nom' => 'Artefact de maîtrise ' . $i,
                    'description' => 'Description de l’artefact de maîtrise ' . $i . '.',
                ],
                'criteres_passage' => [
                    'Critère de passage ' . $i . '.',
                ],
                'relations' => [],
            ];
        }

        return $courses;
    }

    /**
     * Load manifest items.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function manifest_items(): array {
        $manifest = self::read_json_file(self::atlas_root() . DIRECTORY_SEPARATOR . 'atlas_manifest.json');

        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $items = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            $items[] = $item;
        }

        self::assertCount(10, $items);

        return $items;
    }

    /**
     * Return the Atlas root directory.
     *
     * @return string
     */
    private static function atlas_root(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'uckk'
            . DIRECTORY_SEPARATOR . 'atlas';
    }

    /**
     * Return the Atlas Voies directory.
     *
     * @return string
     */
    private static function voies_dir(): string {
        return self::atlas_root() . DIRECTORY_SEPARATOR . 'voies';
    }

    /**
     * Read and decode a JSON file.
     *
     * @param string $path Absolute path.
     * @return array<string, mixed>
     */
    private static function read_json_file(string $path): array {
        self::assertFileExists($path);
        self::assertFileIsReadable($path);

        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail(sprintf(
                'Invalid JSON in %s: %s',
                $path,
                $exception->getMessage()
            ));
        }

        self::assertIsArray($decoded);

        return $decoded;
    }
}