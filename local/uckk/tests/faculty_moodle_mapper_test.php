<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Unit tests for the UCKK Faculty Moodle mapper.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for {@see faculty_moodle_mapper}.
 *
 * @package local_uckk
 * @covers  \local_uckk\local\faculty\faculty_moodle_mapper
 */
final class faculty_moodle_mapper_test extends \advanced_testcase {
    /**
     * Mapper under test.
     *
     * @var faculty_moodle_mapper
     */
    private faculty_moodle_mapper $mapper;

    /**
     * Prepare a clean test state.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->mapper = new faculty_moodle_mapper();
    }

    /**
     * Category identity must come from the Faculty Moodle mapping block.
     */
    public function test_category_mapping_uses_faculty_moodle_contract(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();

        $mapping = $this->mapper->category_mapping($faculty, $atlas, 'atlas-hash', 'faculty-hash');

        $this->assertSame('UCKK-GJS', $mapping['idnumber']);
        $this->assertSame('Voie du Grand Jeu social', $mapping['name']);
        $this->assertSame(1, $mapping['visible']);
        $this->assertSame('faculty_grand_jeu_social', $mapping['customfields']['uckk_faculty_id']);
        $this->assertSame('voie_grand_jeu_social', $mapping['customfields']['uckk_voie_id']);
        $this->assertSame('GJS', $mapping['customfields']['uckk_code']);
        $this->assertSame('grand-jeu-social', $mapping['customfields']['uckk_slug']);
        $this->assertSame('Grand Jeu social', $mapping['customfields']['uckk_domaine_operatoire']);
        $this->assertSame('Puissance opératoire', $mapping['customfields']['uckk_niveau_vise']);
        $this->assertSame('Stratège du Grand Jeu social', $mapping['customfields']['uckk_titre_symbolique']);
        $this->assertSame('published', $mapping['customfields']['uckk_statut']);
        $this->assertSame('UCKK-ATLAS-0.2-draft', $mapping['customfields']['uckk_schema_version']);
        $this->assertSame('UCKK-FACULTY-0.1', $mapping['customfields']['uckk_faculty_profile_version']);
        $this->assertSame('atlas-hash', $mapping['customfields']['uckk_atlas_source_hash']);
        $this->assertSame('faculty-hash', $mapping['customfields']['uckk_faculty_source_hash']);
    }

    /**
     * Course identity must follow the Atlas course convention.
     */
    public function test_course_mapping_uses_cours_id_for_idnumber_and_shortname(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $course = $atlas['cours_conceptuels'][0];

        $mapping = $this->mapper->course_mapping($faculty, $atlas, $course, 'atlas-hash');

        $this->assertSame('GJS101', $mapping['idnumber']);
        $this->assertSame('GJS101', $mapping['shortname']);
        $this->assertSame('GJS101 — Cartographie du Grand Jeu social', $mapping['fullname']);
        $this->assertSame('UCKK-GJS', $mapping['category_idnumber']);
        $this->assertSame(1, $mapping['sortorder']);
    }

    /**
     * Course custom fields must preserve the Atlas course projection.
     */
    public function test_course_mapping_includes_canonical_custom_fields(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $course = $atlas['cours_conceptuels'][0];

        $mapping = $this->mapper->course_mapping($faculty, $atlas, $course, 'atlas-hash');

        $this->assertSame('GJS101', $mapping['customfields']['uckk_cours_id']);
        $this->assertSame('voie_grand_jeu_social', $mapping['customfields']['uckk_voie_id']);
        $this->assertSame('faculty_grand_jeu_social', $mapping['customfields']['uckk_faculty_id']);
        $this->assertSame(1, $mapping['customfields']['uckk_ordre']);
        $this->assertSame('concept_gjs_cartographie', $mapping['customfields']['uckk_concept_maitre_id']);
        $this->assertSame('Cartographie du Grand Jeu social', $mapping['customfields']['uckk_concept_maitre_nom']);
        $this->assertSame('carte', $mapping['customfields']['uckk_artefact_type']);
        $this->assertSame('Carte initiale du Grand Jeu social', $mapping['customfields']['uckk_artefact_nom']);
        $this->assertSame('Parchemin de Puissance opératoire — Voie du Grand Jeu social', $mapping['customfields']['uckk_parchemin']);
        $this->assertSame('atlas-hash', $mapping['customfields']['uckk_atlas_source_hash']);
        $this->assertSame('not_synced', $mapping['customfields']['uckk_sync_status']);
    }

    /**
     * Hub course shortname must use the canonical CODE-HUB convention.
     */
    public function test_hub_course_shortname_uses_code_hub_convention(): void {
        $this->assertSame('GJS-HUB', $this->mapper->hub_shortname($this->sample_faculty_profile(), $this->sample_atlas_voie()));
    }

    /**
     * Badge idnumber must use the canonical UCKK-BADGE-{CODE}-PO convention.
     */
    public function test_badge_idnumber_uses_canonical_parchemin_convention(): void {
        $this->assertSame(
            'UCKK-BADGE-GJS-PO',
            $this->mapper->badge_idnumber($this->sample_faculty_profile(), $this->sample_atlas_voie())
        );
    }

    /**
     * Existing Moodle category can be resolved by category idnumber.
     */
    public function test_find_category_by_idnumber_returns_existing_category(): void {
        $this->getDataGenerator()->create_category([
            'name' => 'UCKK Grand Jeu social',
            'idnumber' => 'UCKK-GJS',
        ]);

        $category = $this->mapper->find_category($this->sample_faculty_profile());

        $this->assertInstanceOf(stdClass::class, $category);
        $this->assertSame('UCKK-GJS', $category->idnumber);
        $this->assertSame('UCKK Grand Jeu social', $category->name);
    }

    /**
     * Missing Moodle category returns null rather than creating a category.
     */
    public function test_find_category_returns_null_when_category_is_missing(): void {
        $this->assertNull($this->mapper->find_category($this->sample_faculty_profile()));
    }

    /**
     * Existing Moodle course can be resolved by course idnumber.
     */
    public function test_find_course_by_cours_id_returns_existing_course(): void {
        $category = $this->getDataGenerator()->create_category([
            'name' => 'UCKK Grand Jeu social',
            'idnumber' => 'UCKK-GJS',
        ]);
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'GJS101 — Cartographie du Grand Jeu social',
            'shortname' => 'GJS101',
            'idnumber' => 'GJS101',
            'category' => $category->id,
            'visible' => 1,
        ]);

        $resolved = $this->mapper->find_course('GJS101');

        $this->assertInstanceOf(stdClass::class, $resolved);
        $this->assertSame((int) $course->id, (int) $resolved->id);
        $this->assertSame('GJS101', $resolved->shortname);
        $this->assertSame('GJS101', $resolved->idnumber);
    }

    /**
     * Missing Moodle course returns null rather than creating a course.
     */
    public function test_find_course_returns_null_when_course_is_missing(): void {
        $this->assertNull($this->mapper->find_course('GJS101'));
    }

    /**
     * Public-page mapping is read-only and must not create Moodle records.
     */
    public function test_read_only_mapping_does_not_create_missing_category_or_courses(): void {
        global $DB;

        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();

        $categorycountbefore = $DB->count_records('course_categories');
        $coursecountbefore = $DB->count_records('course');

        $report = $this->mapper->build_readonly_report($faculty, $atlas, 'atlas-hash', 'faculty-hash');

        $this->assertSame($categorycountbefore, $DB->count_records('course_categories'));
        $this->assertSame($coursecountbefore, $DB->count_records('course'));
        $this->assertSame('read_only', $report['mode']);
        $this->assertSame('missing_in_moodle', $report['category']['status']);
        $this->assertSame('missing_in_moodle', $report['courses'][0]['status']);
    }

    /**
     * Dry-run reports missing category, courses, custom fields, badges and hashes without mutation.
     */
    public function test_dry_run_reports_expected_missing_items_without_mutation(): void {
        global $DB;

        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();

        $categorycountbefore = $DB->count_records('course_categories');
        $coursecountbefore = $DB->count_records('course');

        $report = $this->mapper->build_dryrun_report($faculty, $atlas, 'atlas-hash', 'faculty-hash');

        $this->assertSame($categorycountbefore, $DB->count_records('course_categories'));
        $this->assertSame($coursecountbefore, $DB->count_records('course'));
        $this->assertSame('dry_run', $report['mode']);
        $this->assertContains('UCKK-GJS', $report['categories_to_create']);
        $this->assertContains('GJS101', $report['courses_to_create']);
        $this->assertContains('uckk_faculty_id', $report['missing_category_custom_fields']);
        $this->assertContains('uckk_cours_id', $report['missing_course_custom_fields']);
        $this->assertContains('UCKK-BADGE-GJS-PO', $report['badges_to_create']);
        $this->assertSame('atlas-hash', $report['hashes']['atlas_source_hash']);
        $this->assertSame('faculty-hash', $report['hashes']['faculty_source_hash']);
    }

    /**
     * Existing matching Moodle records are reported as in_sync.
     */
    public function test_compare_existing_matching_course_returns_in_sync(): void {
        $category = $this->getDataGenerator()->create_category([
            'name' => 'Voie du Grand Jeu social',
            'idnumber' => 'UCKK-GJS',
        ]);
        $moodlecourse = $this->getDataGenerator()->create_course([
            'fullname' => 'GJS101 — Cartographie du Grand Jeu social',
            'shortname' => 'GJS101',
            'idnumber' => 'GJS101',
            'category' => $category->id,
            'visible' => 1,
        ]);

        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $expected = $this->mapper->course_mapping($faculty, $atlas, $atlas['cours_conceptuels'][0], 'atlas-hash');

        $comparison = $this->mapper->compare_course($expected, $moodlecourse);

        $this->assertSame('in_sync', $comparison['status']);
        $this->assertSame([], $comparison['diffs']);
    }

    /**
     * Existing Moodle course with manual title drift is reported as changed_in_moodle.
     */
    public function test_compare_existing_course_with_title_drift_returns_changed_in_moodle(): void {
        $category = $this->getDataGenerator()->create_category([
            'name' => 'Voie du Grand Jeu social',
            'idnumber' => 'UCKK-GJS',
        ]);
        $moodlecourse = $this->getDataGenerator()->create_course([
            'fullname' => 'GJS101 — Titre manuel',
            'shortname' => 'GJS101',
            'idnumber' => 'GJS101',
            'category' => $category->id,
            'visible' => 1,
        ]);

        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $expected = $this->mapper->course_mapping($faculty, $atlas, $atlas['cours_conceptuels'][0], 'atlas-hash');

        $comparison = $this->mapper->compare_course($expected, $moodlecourse);

        $this->assertSame('changed_in_moodle', $comparison['status']);
        $this->assertArrayHasKey('fullname', $comparison['diffs']);
        $this->assertSame('GJS101 — Cartographie du Grand Jeu social', $comparison['diffs']['fullname']['expected']);
        $this->assertSame('GJS101 — Titre manuel', $comparison['diffs']['fullname']['actual']);
    }

    /**
     * A missing Moodle course comparison must be missing_in_moodle.
     */
    public function test_compare_missing_course_returns_missing_in_moodle(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $expected = $this->mapper->course_mapping($faculty, $atlas, $atlas['cours_conceptuels'][0], 'atlas-hash');

        $comparison = $this->mapper->compare_course($expected, null);

        $this->assertSame('missing_in_moodle', $comparison['status']);
        $this->assertSame('GJS101', $comparison['idnumber']);
    }

    /**
     * Alignment validation catches Faculty/Atlas voie mismatch.
     */
    public function test_validate_alignment_reports_voie_id_mismatch(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $atlas['voie_id'] = 'voie_economie';

        $result = $this->mapper->validate_alignment($faculty, $atlas);

        $this->assertFalse($result['valid']);
        $this->assertContains('faculty.voie_id_mismatch', $result['errors']);
    }

    /**
     * Alignment validation catches Faculty course prefix / Atlas code mismatch.
     */
    public function test_validate_alignment_reports_course_prefix_mismatch(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $atlas['code'] = 'EC';

        $result = $this->mapper->validate_alignment($faculty, $atlas);

        $this->assertFalse($result['valid']);
        $this->assertContains('faculty.course_prefix_mismatch', $result['errors']);
    }

    /**
     * Alignment validation accepts a coherent Faculty/Atlas pair.
     */
    public function test_validate_alignment_accepts_coherent_faculty_and_atlas_pair(): void {
        $result = $this->mapper->validate_alignment($this->sample_faculty_profile(), $this->sample_atlas_voie());

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    /**
     * Mapper must expose only documented sync statuses.
     */
    public function test_allowed_sync_statuses_are_canonical(): void {
        $this->assertSame([
            'not_synced',
            'in_sync',
            'changed_in_json',
            'changed_in_moodle',
            'conflict',
            'missing_in_moodle',
            'missing_in_json',
            'sync_error',
        ], faculty_moodle_mapper::allowed_sync_statuses());
    }

    /**
     * Invalid Faculty slugs must not be accepted by mapper methods.
     *
     * @dataProvider invalid_slug_provider
     * @param string $slug Invalid slug.
     */
    public function test_category_mapping_rejects_invalid_slugs(string $slug): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['slug'] = $slug;

        $this->expectException(coding_exception::class);

        $this->mapper->category_mapping($faculty, $this->sample_atlas_voie(), 'atlas-hash', 'faculty-hash');
    }

    /**
     * Invalid category idnumbers must not be accepted by mapper methods.
     *
     * @dataProvider invalid_category_idnumber_provider
     * @param string $idnumber Invalid category idnumber.
     */
    public function test_category_mapping_rejects_invalid_category_idnumbers(string $idnumber): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['moodle']['category_idnumber'] = $idnumber;

        $this->expectException(coding_exception::class);

        $this->mapper->category_mapping($faculty, $this->sample_atlas_voie(), 'atlas-hash', 'faculty-hash');
    }

    /**
     * Invalid course prefixes must not be accepted by mapper methods.
     *
     * @dataProvider invalid_course_prefix_provider
     * @param string $prefix Invalid course prefix.
     */
    public function test_course_mapping_rejects_invalid_course_prefixes(string $prefix): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['moodle']['course_prefix'] = $prefix;

        $this->expectException(coding_exception::class);

        $this->mapper->course_mapping($faculty, $this->sample_atlas_voie(), $this->sample_atlas_voie()['cours_conceptuels'][0], 'hash');
    }

    /**
     * Invalid course ids must not be accepted by mapper methods.
     *
     * @dataProvider invalid_course_id_provider
     * @param string $coursid Invalid course id.
     */
    public function test_course_mapping_rejects_invalid_course_ids(string $coursid): void {
        $atlas = $this->sample_atlas_voie();
        $atlas['cours_conceptuels'][0]['cours_id'] = $coursid;

        $this->expectException(coding_exception::class);

        $this->mapper->course_mapping($this->sample_faculty_profile(), $atlas, $atlas['cours_conceptuels'][0], 'hash');
    }

    /**
     * Invalid slugs.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_slug_provider(): array {
        return [
            'empty' => [''],
            'uppercase' => ['Grand-Jeu-Social'],
            'underscore' => ['grand_jeu_social'],
            'relative path' => ['../content/faculties/grand-jeu-social.faculty'],
            'absolute path' => ['/local/uckk/content/faculties/grand-jeu-social.faculty.json'],
            'extension' => ['grand-jeu-social.faculty.json'],
            'query' => ['grand-jeu-social?debug=1'],
        ];
    }

    /**
     * Invalid category idnumbers.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_category_idnumber_provider(): array {
        return [
            'empty' => [''],
            'lowercase' => ['uckk-gjs'],
            'missing prefix' => ['GJS'],
            'pathlike' => ['../UCKK-GJS'],
            'extension' => ['UCKK-GJS.json'],
            'space' => ['UCKK GJS'],
        ];
    }

    /**
     * Invalid course prefixes.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_course_prefix_provider(): array {
        return [
            'empty' => [''],
            'lowercase' => ['gjs'],
            'dash' => ['G-JS'],
            'numeric first' => ['1GJS'],
            'pathlike' => ['../GJS'],
            'space' => ['G JS'],
        ];
    }

    /**
     * Invalid course ids.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_course_id_provider(): array {
        return [
            'empty' => [''],
            'lowercase' => ['gjs101'],
            'wrong prefix' => ['EC101'],
            'missing number' => ['GJS'],
            'pathlike' => ['../GJS101'],
            'extension' => ['GJS101.json'],
            'space' => ['GJS 101'],
        ];
    }

    /**
     * Sample Faculty profile.
     *
     * @return array<string, mixed>
     */
    private function sample_faculty_profile(): array {
        return [
            'schema_version' => 'UCKK-FACULTY-0.1',
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'status' => 'published',
            'visibility' => 'public',
            'identity' => [
                'name' => 'Voie du Grand Jeu social',
                'short_name' => 'Grand Jeu social',
                'domain' => 'Grand Jeu social',
                'level' => 'Puissance opératoire',
                'title_symbolique' => 'Stratège du Grand Jeu social',
            ],
            'source_atlas' => [
                'file' => 'voie_grand_jeu_social.json',
                'voie_id' => 'voie_grand_jeu_social',
            ],
            'moodle' => [
                'category_idnumber' => 'UCKK-GJS',
                'course_prefix' => 'GJS',
                'hub_course_shortname' => 'GJS-HUB',
            ],
            'atlas_projection' => [
                'show_courses' => true,
                'show_concept_maitre' => true,
                'show_concepts_associes' => false,
            ],
        ];
    }

    /**
     * Sample Atlas Voie.
     *
     * @return array<string, mixed>
     */
    private function sample_atlas_voie(): array {
        return [
            'schema_version' => 'UCKK-ATLAS-0.2-draft',
            'voie_id' => 'voie_grand_jeu_social',
            'code' => 'GJS',
            'nom' => 'Voie du Grand Jeu social',
            'domaine_operatoire' => 'Grand Jeu social',
            'niveau_vise' => 'Puissance opératoire',
            'titre_symbolique' => 'Stratège du Grand Jeu social',
            'parchemin' => [
                'nom' => 'Parchemin de Puissance opératoire — Voie du Grand Jeu social',
            ],
            'cours_conceptuels' => [
                [
                    'cours_id' => 'GJS101',
                    'ordre' => 1,
                    'nom' => 'Cartographie du Grand Jeu social',
                    'concept_maitre' => [
                        'concept_id' => 'concept_gjs_cartographie',
                        'nom' => 'Cartographie du Grand Jeu social',
                    ],
                    'artefact_maitrise' => [
                        'type' => 'carte',
                        'nom' => 'Carte initiale du Grand Jeu social',
                    ],
                    'criteres_passage' => [
                        'Identifier les principaux acteurs, règles et tensions.',
                    ],
                ],
                [
                    'cours_id' => 'GJS102',
                    'ordre' => 2,
                    'nom' => 'Règles explicites et règles implicites',
                    'concept_maitre' => [
                        'concept_id' => 'concept_gjs_regles',
                        'nom' => 'Règles du jeu',
                    ],
                    'artefact_maitrise' => [
                        'type' => 'analyse',
                        'nom' => 'Analyse des règles visibles et invisibles',
                    ],
                    'criteres_passage' => [
                        'Distinguer règles formelles et règles tacites.',
                    ],
                ],
            ],
        ];
    }
}