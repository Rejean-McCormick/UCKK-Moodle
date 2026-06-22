<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Atlas Voie validator.
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
use local_uckk\local\atlas\voie_validator;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\atlas\voie_validator.
 *
 * The validator protects the Atlas Voie JSON schema. It validates pedagogical
 * Atlas data only; it must not validate Faculty Profile sections, public page
 * navigation, dynamic Moodle blocks, Moodle enrolment state, or rendered HTML.
 *
 * @covers \local_uckk\local\atlas\voie_validator
 */
final class voie_validator_test extends advanced_testcase {
    /** Atlas schema version. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Canonical Voie ids. */
    private const CANONICAL_VOIE_IDS = [
        'voie_grand_jeu_social',
        'voie_economie',
        'voie_ecologie',
        'voie_sciences_politiques',
        'voie_linguistique_architecture_du_sens',
        'voie_metaphysique',
        'voie_ia_gouvernable',
        'voie_intervention_sociale_systemes_humains',
        'voie_architecture_sociotechnique',
        'voie_ecosysteme_digital_koa',
    ];

    /** Forbidden legacy or drifted Voie ids. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /** Required top-level Atlas Voie fields. */
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

    /** Required course fields. */
    private const REQUIRED_COURSE_FIELDS = [
        'cours_id',
        'ordre',
        'nom',
        'concept_maitre',
        'concepts_associes',
        'artefact_maitrise',
        'criteres_passage',
        'relations',
    ];

    /**
     * The validator class and canonical validate() method must exist.
     */
    public function test_validator_exposes_canonical_validate_method(): void {
        $this->assertTrue(
            class_exists(voie_validator::class),
            voie_validator::class . ' must exist.'
        );

        $this->assertTrue(
            method_exists(voie_validator::class, 'validate'),
            voie_validator::class . ' must expose validate(array $voie).'
        );
    }

    /**
     * A complete canonical Voie fixture must validate.
     */
    public function test_validate_accepts_complete_canonical_voie(): void {
        $this->assert_validation_passes(self::valid_voie());
    }

    /**
     * Every shipped Atlas Voie JSON file must validate.
     */
    public function test_validate_accepts_all_manifest_declared_voie_json_files(): void {
        foreach (self::manifest_items() as $item) {
            $this->assertArrayHasKey('file', $item);
            $this->assertIsString($item['file']);

            $path = self::voies_dir() . DIRECTORY_SEPARATOR . $item['file'];
            $voie = self::read_json_file($path);

            $this->assert_validation_passes($voie);
        }
    }

    /**
     * Invalid schema versions must fail closed.
     */
    public function test_validate_rejects_invalid_schema_version(): void {
        $voie = self::valid_voie();
        $voie['schema_version'] = 'UCKK-ATLAS-0.1';

        $this->assert_validation_fails($voie, 'Invalid schema_version must be rejected.');
    }

    /**
     * Missing required top-level fields must fail closed.
     */
    public function test_validate_rejects_missing_required_top_level_fields(): void {
        foreach (self::REQUIRED_TOP_LEVEL_FIELDS as $field) {
            $voie = self::valid_voie();
            unset($voie[$field]);

            $this->assert_validation_fails($voie, 'Missing top-level field must be rejected: ' . $field);
        }
    }

    /**
     * Non-array fields documented as arrays must fail closed.
     */
    public function test_validate_rejects_non_array_top_level_collections(): void {
        foreach (['seuils_progression', 'cours_conceptuels', 'limites_ethiques', 'relations_intervoies', 'tags'] as $field) {
            $voie = self::valid_voie();
            $voie[$field] = 'not-an-array';

            $this->assert_validation_fails($voie, 'Non-array top-level collection must be rejected: ' . $field);
        }
    }

    /**
     * Each Voie must have exactly 10 conceptual courses.
     */
    public function test_validate_rejects_course_count_other_than_ten(): void {
        $voie = self::valid_voie();
        array_pop($voie['cours_conceptuels']);

        $this->assert_validation_fails($voie, 'A Voie with fewer than 10 courses must be rejected.');

        $voie = self::valid_voie();
        $extra = $voie['cours_conceptuels'][9];
        $extra['ordre'] = 11;
        $extra['cours_id'] = 'GJS111';
        $voie['cours_conceptuels'][] = $extra;

        $this->assert_validation_fails($voie, 'A Voie with more than 10 courses must be rejected.');
    }

    /**
     * Course order must be exactly 1..10.
     */
    public function test_validate_rejects_invalid_course_order(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][4]['ordre'] = 99;

        $this->assert_validation_fails($voie, 'Invalid course ordre must be rejected.');
    }

    /**
     * Course ids must follow CODE + three-digit number convention.
     */
    public function test_validate_rejects_invalid_course_id_prefix_or_number(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['cours_id'] = 'BAD101';

        $this->assert_validation_fails($voie, 'Course id with wrong prefix must be rejected.');

        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['cours_id'] = 'GJS999';

        $this->assert_validation_fails($voie, 'Course id outside 101..110 must be rejected.');
    }

    /**
     * Missing required course fields must fail closed.
     */
    public function test_validate_rejects_missing_required_course_fields(): void {
        foreach (self::REQUIRED_COURSE_FIELDS as $field) {
            $voie = self::valid_voie();
            unset($voie['cours_conceptuels'][0][$field]);

            $this->assert_validation_fails($voie, 'Missing course field must be rejected: ' . $field);
        }
    }

    /**
     * Missing concept_maitre.type must fail closed.
     */
    public function test_validate_rejects_missing_concept_maitre_type(): void {
        $voie = self::valid_voie();
        unset($voie['cours_conceptuels'][0]['concept_maitre']['type']);

        $this->assert_validation_fails($voie, 'Missing concept_maitre.type must be rejected.');
    }

    /**
     * Invalid concept_maitre.type must fail closed.
     */
    public function test_validate_rejects_invalid_concept_maitre_type(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concept_maitre']['type'] = 'concept_associe';

        $this->assert_validation_fails($voie, 'Invalid concept_maitre.type must be rejected.');
    }

    /**
     * Invalid associated concept type must fail closed.
     */
    public function test_validate_rejects_invalid_concept_associe_type(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['type'] = 'concept_maitre';

        $this->assert_validation_fails($voie, 'Invalid associated concept type must be rejected.');
    }

    /**
     * Non-array notions_fines must fail closed.
     */
    public function test_validate_rejects_non_array_notions_fines(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines'] = null;

        $this->assert_validation_fails($voie, 'Null notions_fines must be rejected.');

        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['notions_fines'] = 'not-an-array';

        $this->assert_validation_fails($voie, 'String notions_fines must be rejected.');
    }

    /**
     * Duplicate concept ids inside one course must fail closed.
     */
    public function test_validate_rejects_duplicate_concept_ids_in_same_course(): void {
        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][0]['concept_id'] =
            $voie['cours_conceptuels'][0]['concept_maitre']['concept_id'];

        $this->assert_validation_fails(
            $voie,
            'A concept_associe must not reuse the concept_maitre concept_id.'
        );

        $voie = self::valid_voie();
        $voie['cours_conceptuels'][0]['concepts_associes'][1]['concept_id'] =
            $voie['cours_conceptuels'][0]['concepts_associes'][0]['concept_id'];

        $this->assert_validation_fails(
            $voie,
            'Duplicate associated concept_id values in one course must be rejected.'
        );
    }

    /**
     * Invalid inter-Voie relation targets must fail closed.
     */
    public function test_validate_rejects_invalid_relation_voie_id(): void {
        $voie = self::valid_voie();
        $voie['relations_intervoies'][] = [
            'voie_id' => 'voie_inconnue',
            'type' => 'relation',
            'description' => 'Relation invalide.',
        ];

        $this->assert_validation_fails($voie, 'Invalid relation voie_id must be rejected.');
    }

    /**
     * Forbidden legacy Voie ids must fail closed.
     */
    public function test_validate_rejects_forbidden_voie_id_aliases(): void {
        foreach (self::FORBIDDEN_VOIE_IDS as $forbidden) {
            $voie = self::valid_voie();
            $voie['voie_id'] = $forbidden;

            $this->assert_validation_fails($voie, 'Forbidden voie_id alias must be rejected: ' . $forbidden);
        }
    }

    /**
     * Faculty Profile and public page fields must not be accepted as Atlas fields.
     */
    public function test_validate_rejects_faculty_or_public_page_payload_fields(): void {
        $forbiddenfields = [
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
        ];

        foreach ($forbiddenfields as $field) {
            $voie = self::valid_voie();
            $voie[$field] = [];

            $this->assert_validation_fails($voie, 'Faculty/public page field must be rejected in Atlas Voie: ' . $field);
        }
    }

    /**
     * Assert that a Voie payload validates.
     *
     * Supports either of these implementation styles:
     * - validate() returns true/false;
     * - validate() returns an array with valid/errors fields;
     * - validate() returns a validation result object;
     * - validate() returns void/null on success and throws on failure.
     *
     * @param array<string, mixed> $voie Voie payload.
     */
    private function assert_validation_passes(array $voie): void {
        try {
            $result = self::validator()->validate($voie);
        } catch (Throwable $exception) {
            $this->fail('Validation was expected to pass, but failed: ' . $exception->getMessage());
        }

        $this->assertTrue(
            self::validation_result_is_valid($result),
            'Validation was expected to pass.'
        );
    }

    /**
     * Assert that a Voie payload fails validation.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @param string $message Assertion message.
     */
    private function assert_validation_fails(array $voie, string $message): void {
        try {
            $result = self::validator()->validate($voie);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
            return;
        }

        $this->assertFalse(
            self::validation_result_is_valid($result),
            $message
        );
    }

    /**
     * Instantiate the validator under test.
     *
     * @return voie_validator
     */
    private static function validator(): voie_validator {
        return new voie_validator();
    }

    /**
     * Convert common validation result shapes to a boolean.
     *
     * @param mixed $result Validation result.
     * @return bool
     */
    private static function validation_result_is_valid(mixed $result): bool {
        if ($result === null) {
            return true;
        }

        if (is_bool($result)) {
            return $result;
        }

        if (is_array($result)) {
            if (array_key_exists('valid', $result)) {
                return (bool) $result['valid'];
            }

            if (array_key_exists('success', $result)) {
                return (bool) $result['success'];
            }

            if (array_key_exists('errors', $result) && is_array($result['errors'])) {
                return count($result['errors']) === 0;
            }

            if (array_key_exists('messages', $result) && is_array($result['messages'])) {
                return !self::messages_contain_errors($result['messages']);
            }

            return true;
        }

        if (is_object($result)) {
            foreach (['is_valid', 'isValid', 'valid'] as $method) {
                if (method_exists($result, $method)) {
                    return (bool) $result->{$method}();
                }
            }

            foreach (['has_errors', 'hasErrors'] as $method) {
                if (method_exists($result, $method)) {
                    return !(bool) $result->{$method}();
                }
            }

            foreach (['get_errors', 'getErrors'] as $method) {
                if (method_exists($result, $method)) {
                    $errors = $result->{$method}();
                    return is_array($errors) && count($errors) === 0;
                }
            }

            foreach (['get_messages', 'getMessages'] as $method) {
                if (method_exists($result, $method)) {
                    $messages = $result->{$method}();
                    return is_array($messages) && !self::messages_contain_errors($messages);
                }
            }

            if (property_exists($result, 'valid')) {
                return (bool) $result->valid;
            }

            if (property_exists($result, 'errors') && is_array($result->errors)) {
                return count($result->errors) === 0;
            }
        }

        return true;
    }

    /**
     * Detect error/blocker messages in common validation message structures.
     *
     * @param array<int|string, mixed> $messages Messages.
     * @return bool
     */
    private static function messages_contain_errors(array $messages): bool {
        foreach ($messages as $message) {
            if (is_array($message)) {
                $severity = (string)($message['severity'] ?? $message['level'] ?? '');
                if (in_array($severity, ['error', 'blocker', 'failed'], true)) {
                    return true;
                }
            } else if (is_object($message)) {
                $severity = '';

                if (property_exists($message, 'severity')) {
                    $severity = (string)$message->severity;
                } else if (method_exists($message, 'get_severity')) {
                    $severity = (string)$message->get_severity();
                }

                if (in_array($severity, ['error', 'blocker', 'failed'], true)) {
                    return true;
                }
            }
        }

        return false;
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
                        'notions_fines' => [],
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
            self::assertArrayHasKey('voie_id', $item);
            self::assertContains($item['voie_id'], self::CANONICAL_VOIE_IDS);

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