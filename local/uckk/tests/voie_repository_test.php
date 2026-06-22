<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Atlas Voie repository.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use local_uckk\local\atlas\voie_repository;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\atlas\voie_repository.
 *
 * The repository is the read boundary for Atlas Voie JSON files. It must read
 * only the 10 canonical files declared through the Atlas manifest and must not
 * accept arbitrary paths, slugs, Faculty files, or Moodle course identifiers.
 *
 * @covers \local_uckk\local\atlas\voie_repository
 */
final class voie_repository_test extends advanced_testcase {
    /** Atlas schema version. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Canonical Voies from DOC_12. */
    private const EXPECTED_VOIES = [
        'voie_grand_jeu_social' => [
            'code' => 'GJS',
            'file' => 'voie_grand_jeu_social.json',
            'course_prefix' => 'GJS',
            'category_idnumber' => 'UCKK-GJS',
            'sortorder' => 1,
        ],
        'voie_economie' => [
            'code' => 'EC',
            'file' => 'voie_economie.json',
            'course_prefix' => 'EC',
            'category_idnumber' => 'UCKK-EC',
            'sortorder' => 2,
        ],
        'voie_ecologie' => [
            'code' => 'ECL',
            'file' => 'voie_ecologie.json',
            'course_prefix' => 'ECL',
            'category_idnumber' => 'UCKK-ECL',
            'sortorder' => 3,
        ],
        'voie_sciences_politiques' => [
            'code' => 'SP',
            'file' => 'voie_sciences_politiques.json',
            'course_prefix' => 'SP',
            'category_idnumber' => 'UCKK-SP',
            'sortorder' => 4,
        ],
        'voie_linguistique_architecture_du_sens' => [
            'code' => 'LI',
            'file' => 'voie_linguistique_architecture_du_sens.json',
            'course_prefix' => 'LI',
            'category_idnumber' => 'UCKK-LI',
            'sortorder' => 5,
        ],
        'voie_metaphysique' => [
            'code' => 'ME',
            'file' => 'voie_metaphysique.json',
            'course_prefix' => 'ME',
            'category_idnumber' => 'UCKK-ME',
            'sortorder' => 6,
        ],
        'voie_ia_gouvernable' => [
            'code' => 'IA',
            'file' => 'voie_ia_gouvernable.json',
            'course_prefix' => 'IA',
            'category_idnumber' => 'UCKK-IA',
            'sortorder' => 7,
        ],
        'voie_intervention_sociale_systemes_humains' => [
            'code' => 'IS',
            'file' => 'voie_intervention_sociale_systemes_humains.json',
            'course_prefix' => 'IS',
            'category_idnumber' => 'UCKK-IS',
            'sortorder' => 8,
        ],
        'voie_architecture_sociotechnique' => [
            'code' => 'AS',
            'file' => 'voie_architecture_sociotechnique.json',
            'course_prefix' => 'AS',
            'category_idnumber' => 'UCKK-AS',
            'sortorder' => 9,
        ],
        'voie_ecosysteme_digital_koa' => [
            'code' => 'KOA',
            'file' => 'voie_ecosysteme_digital_koa.json',
            'course_prefix' => 'KOA',
            'category_idnumber' => 'UCKK-KOA',
            'sortorder' => 10,
        ],
    ];

    /** Required top-level Atlas Voie fields. */
    private const REQUIRED_VOIE_FIELDS = [
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

    /** Forbidden aliases explicitly excluded by DOC_12. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /**
     * Repository class and canonical methods must exist.
     */
    public function test_repository_exposes_canonical_methods(): void {
        $this->assertTrue(
            class_exists(voie_repository::class),
            voie_repository::class . ' must exist.'
        );

        $this->assertTrue(method_exists(voie_repository::class, 'get_by_voie_id'));
        $this->assertTrue(method_exists(voie_repository::class, 'get_by_file'));
        $this->assertTrue(method_exists(voie_repository::class, 'all'));
    }

    /**
     * get_by_voie_id() must return the requested canonical Atlas Voie.
     */
    public function test_get_by_voie_id_returns_canonical_voie(): void {
        $repository = self::repository();

        $voie = $repository->get_by_voie_id('voie_grand_jeu_social');

        $this->assertIsArray($voie);
        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $voie['schema_version']);
        $this->assertSame('voie_grand_jeu_social', $voie['voie_id']);
        $this->assertSame('GJS', $voie['code']);
        $this->assertArrayHasKey('nom', $voie);
        $this->assertIsString($voie['nom']);
        $this->assertNotSame('', trim($voie['nom']));

        $this->assert_has_required_voie_shape($voie);
    }

    /**
     * get_by_file() must resolve only safe manifest-declared basenames.
     */
    public function test_get_by_file_returns_canonical_voie(): void {
        $repository = self::repository();

        $voie = $repository->get_by_file('voie_ia_gouvernable.json');

        $this->assertIsArray($voie);
        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $voie['schema_version']);
        $this->assertSame('voie_ia_gouvernable', $voie['voie_id']);
        $this->assertSame('IA', $voie['code']);

        $this->assert_has_required_voie_shape($voie);
    }

    /**
     * all() must return exactly the 10 canonical Voies.
     */
    public function test_all_returns_exactly_ten_canonical_voies(): void {
        $repository = self::repository();

        $voies = $repository->all();

        $this->assertIsArray($voies);
        $this->assertCount(10, $voies);

        $actualbyid = [];

        foreach ($voies as $voie) {
            $this->assertIsArray($voie);
            $this->assertArrayHasKey('voie_id', $voie);
            $this->assertIsString($voie['voie_id']);

            $actualbyid[$voie['voie_id']] = $voie;
        }

        $this->assertSameCanonicalizing(
            array_keys(self::EXPECTED_VOIES),
            array_keys($actualbyid)
        );

        foreach (self::EXPECTED_VOIES as $voieid => $expected) {
            $this->assertArrayHasKey($voieid, $actualbyid);
            $this->assertSame($expected['code'], $actualbyid[$voieid]['code']);
            $this->assert_has_required_voie_shape($actualbyid[$voieid]);
        }
    }

    /**
     * all() must preserve the pedagogical manifest order.
     */
    public function test_all_returns_voies_in_manifest_sortorder(): void {
        $repository = self::repository();

        $voies = $repository->all();

        $actualids = array_map(
            static fn(array $voie): string => (string) $voie['voie_id'],
            $voies
        );

        $this->assertSame(array_keys(self::EXPECTED_VOIES), $actualids);
    }

    /**
     * Every returned Voie must have exactly 10 conceptual courses.
     */
    public function test_all_returned_voies_have_ten_conceptual_courses(): void {
        $repository = self::repository();

        foreach ($repository->all() as $voie) {
            $this->assertArrayHasKey('cours_conceptuels', $voie);
            $this->assertIsArray($voie['cours_conceptuels']);
            $this->assertCount(10, $voie['cours_conceptuels']);

            $expectedprefix = (string) $voie['code'];
            $orders = [];

            foreach ($voie['cours_conceptuels'] as $course) {
                $this->assertIsArray($course);
                $this->assertArrayHasKey('cours_id', $course);
                $this->assertArrayHasKey('ordre', $course);
                $this->assertArrayHasKey('nom', $course);
                $this->assertArrayHasKey('concept_maitre', $course);
                $this->assertArrayHasKey('concepts_associes', $course);
                $this->assertArrayHasKey('artefact_maitrise', $course);
                $this->assertArrayHasKey('criteres_passage', $course);
                $this->assertArrayHasKey('relations', $course);

                $this->assertMatchesRegularExpression(
                    '/^' . preg_quote($expectedprefix, '/') . '10[1-9]$|^' . preg_quote($expectedprefix, '/') . '110$/',
                    (string) $course['cours_id']
                );

                $orders[] = (int) $course['ordre'];

                $this->assertIsArray($course['concept_maitre']);
                $this->assertSame('concept_maitre', $course['concept_maitre']['type'] ?? null);

                $this->assertIsArray($course['concepts_associes']);

                foreach ($course['concepts_associes'] as $concept) {
                    $this->assertIsArray($concept);
                    $this->assertSame('concept_associe', $concept['type'] ?? null);
                    $this->assertArrayHasKey('notions_fines', $concept);
                    $this->assertIsArray($concept['notions_fines']);
                }
            }

            sort($orders);
            $this->assertSame(range(1, 10), $orders);
        }
    }

    /**
     * Repository output must not be a rendered public-page payload.
     */
    public function test_repository_does_not_return_rendered_html_or_dynamic_blocks(): void {
        $repository = self::repository();

        $voie = $repository->get_by_voie_id('voie_grand_jeu_social');

        $this->assertArrayNotHasKey('sections', $voie);
        $this->assertArrayNotHasKey('navigation', $voie);
        $this->assertArrayNotHasKey('atlas_projection', $voie);
        $this->assertArrayNotHasKey('dynamic_blocks', $voie);
        $this->assertArrayNotHasKey('html', $voie);
        $this->assertArrayNotHasKey('rendered', $voie);
    }

    /**
     * Unknown voie_id values must fail closed.
     */
    public function test_get_by_voie_id_rejects_unknown_voie_id(): void {
        $repository = self::repository();

        $this->expectException(Throwable::class);

        $repository->get_by_voie_id('voie_inconnue');
    }

    /**
     * Forbidden legacy voie_id aliases must fail closed.
     */
    public function test_get_by_voie_id_rejects_forbidden_aliases(): void {
        $repository = self::repository();

        foreach (self::FORBIDDEN_VOIE_IDS as $voieid) {
            try {
                $repository->get_by_voie_id($voieid);
                $this->fail('Forbidden voie_id alias was accepted: ' . $voieid);
            } catch (Throwable $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    /**
     * get_by_file() must reject path traversal and non-Voie files.
     */
    public function test_get_by_file_rejects_unsafe_file_names(): void {
        $repository = self::repository();

        $unsafe = [
            '../voie_grand_jeu_social.json',
            '..\\voie_grand_jeu_social.json',
            '/voie_grand_jeu_social.json',
            'voies/voie_grand_jeu_social.json',
            'grand-jeu-social.faculty.json',
            'atlas_manifest.json',
            'voie_grand_jeu_social.php',
            'voie_koa.json',
        ];

        foreach ($unsafe as $filename) {
            try {
                $repository->get_by_file($filename);
                $this->fail('Unsafe Atlas file name was accepted: ' . $filename);
            } catch (Throwable $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    /**
     * Manifest stable fields must match the loaded JSON identities.
     */
    public function test_repository_output_matches_atlas_manifest_identity_fields(): void {
        $repository = self::repository();
        $manifest = self::load_manifest_items_by_voie_id();

        foreach ($repository->all() as $voie) {
            $voieid = (string) $voie['voie_id'];

            $this->assertArrayHasKey($voieid, $manifest);
            $this->assertSame($manifest[$voieid]['code'], $voie['code']);
        }
    }

    /**
     * Instantiate the repository under test.
     *
     * @return voie_repository
     */
    private static function repository(): voie_repository {
        return new voie_repository();
    }

    /**
     * Assert the documented Atlas Voie top-level shape.
     *
     * @param array<string, mixed> $voie Voie data.
     */
    private function assert_has_required_voie_shape(array $voie): void {
        foreach (self::REQUIRED_VOIE_FIELDS as $field) {
            $this->assertArrayHasKey($field, $voie, 'Missing Atlas Voie field: ' . $field);
        }

        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $voie['schema_version']);
        $this->assertIsString($voie['voie_id']);
        $this->assertIsString($voie['code']);
        $this->assertIsString($voie['nom']);
        $this->assertIsString($voie['domaine_operatoire']);
        $this->assertIsString($voie['niveau_vise']);
        $this->assertIsString($voie['titre_symbolique']);
        $this->assertIsString($voie['parchemin']);
        $this->assertIsString($voie['statut']);
        $this->assertIsString($voie['definition_courte']);
        $this->assertIsString($voie['angle_fondamental']);
        $this->assertIsString($voie['competence_centrale']);
        $this->assertIsArray($voie['seuils_progression']);
        $this->assertIsArray($voie['cours_conceptuels']);
        $this->assertIsArray($voie['projet_final']);
        $this->assertIsArray($voie['limites_ethiques']);
        $this->assertIsArray($voie['relations_intervoies']);
        $this->assertIsArray($voie['tags']);
    }

    /**
     * Load Atlas manifest items indexed by voie_id.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function load_manifest_items_by_voie_id(): array {
        $manifestpath = self::atlas_root() . DIRECTORY_SEPARATOR . 'atlas_manifest.json';

        self::assertFileExists($manifestpath);
        self::assertFileIsReadable($manifestpath);

        $contents = file_get_contents($manifestpath);
        self::assertNotFalse($contents);

        $manifest = json_decode($contents, true);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $items = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            self::assertArrayHasKey('voie_id', $item);
            self::assertIsString($item['voie_id']);

            $items[$item['voie_id']] = $item;
        }

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
}