<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Tests for the UCKK Seed admin tool.
 *
 * @package    tool_uckkseed
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed;

use stdClass;
use tool_uckkseed\local\seeder;
use tool_uckkseed\local\validation_result;

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit tests for UCKK seeding.
 */
final class seeder_test extends \advanced_testcase {
    /**
     * Reset test state.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * validation_result tracks canonical counts and messages.
     */
    public function test_validation_result_tracks_counts_messages_and_status(): void {
        $result = new validation_result();

        $result
            ->add_created('course', 'UCKK-TC101', 101)
            ->add_updated('cohort', 'uckk_players', 202)
            ->add_skipped('badge', 'joueur_initie', 303)
            ->add_warning('Warning message', 'courses', 'course', 'UCKK-TC101')
            ->add_error('Error message', 'badges', 'badge', 'joueur_initie');

        $this->assertFalse($result->is_ok());
        $this->assertTrue($result->has_warnings());
        $this->assertTrue($result->has_errors());
        $this->assertSame(validation_result::STATUS_FAILED, $result->get_status());

        $counts = $result->get_counts();

        $this->assertSame(1, $counts['created']);
        $this->assertSame(1, $counts['updated']);
        $this->assertSame(1, $counts['skipped']);
        $this->assertSame(1, $counts['warnings']);
        $this->assertSame(1, $counts['errors']);

        $messages = $result->get_messages();

        $this->assertCount(2, $messages);
        $this->assertSame(validation_result::SEVERITY_WARNING, $messages[0]['severity']);
        $this->assertSame(validation_result::SEVERITY_ERROR, $messages[1]['severity']);
    }

    /**
     * validation_result can merge child results.
     */
    public function test_validation_result_merge_combines_child_results(): void {
        $parent = validation_result::success('Parent result');
        $child = validation_result::success('Child result');

        $child
            ->add_created('category', 'tronc_commun', 11)
            ->add_updated('course', 'UCKK-TC101', 22)
            ->add_warning('Minor issue', 'courses', 'course', 'UCKK-TC101')
            ->complete();

        $parent->merge($child);

        $this->assertTrue($parent->has_warnings());
        $this->assertFalse($parent->has_errors());

        $counts = $parent->get_counts();

        $this->assertSame(1, $counts['created']);
        $this->assertSame(1, $counts['updated']);
        $this->assertSame(1, $counts['warnings']);
        $this->assertStringContainsString('Parent result', $parent->get_summary());
        $this->assertStringContainsString('Child result', $parent->get_summary());
    }

    /**
     * Valid preset files should validate successfully.
     */
    public function test_validate_presets_returns_success_for_valid_presets(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $result = $seeder->validate([
            'action' => 'validate',
            'mode' => 'dry_run',
            'presetpath' => $presetpath,
            'presets' => [
                'categories',
                'courses',
                'cohorts',
                'roles',
                'capabilities',
                'competencies',
                'badges',
                'reports',
                'course_templates',
                'challenge_templates',
                'assembly_templates',
                'archive_templates',
            ],
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertTrue($result->is_ok(), $result->to_json());
        $this->assertFalse($result->has_errors(), $result->to_json());
    }

    /**
     * Invalid preset schema should return a failed validation result.
     */
    public function test_validate_presets_reports_invalid_schema(): void {
        $presetpath = make_temp_directory('tool_uckkseed_invalid_presets');

        file_put_contents($presetpath . '/categories.json', json_encode([
            'schema' => 'wrong.schema',
            'component' => 'tool_uckkseed',
            'preset' => 'categories',
            'version' => 2026051200,
            'items' => [],
        ], JSON_PRETTY_PRINT));

        $seeder = new seeder();

        $result = $seeder->validate([
            'action' => 'validate',
            'mode' => 'dry_run',
            'presetpath' => $presetpath,
            'presets' => ['categories'],
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertFalse($result->is_ok());
        $this->assertTrue($result->has_errors());
    }

    /**
     * Dry-run seeding validates and plans work without creating Moodle records.
     */
    public function test_dry_run_does_not_create_records(): void {
        global $DB;

        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $beforecategories = $DB->count_records('course_categories');
        $beforecourses = $DB->count_records('course');

        $result = $seeder->seed([
            'action' => 'seed',
            'mode' => 'dry_run',
            'dryrun' => true,
            'presetpath' => $presetpath,
            'presets' => [
                'categories',
                'courses',
            ],
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertTrue($result->is_ok(), $result->to_json());

        $this->assertSame($beforecategories, $DB->count_records('course_categories'));
        $this->assertSame($beforecourses, $DB->count_records('course'));
    }

    /**
     * Repeated dry-runs should remain idempotent.
     */
    public function test_seed_is_idempotent_in_dry_run(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $options = [
            'action' => 'seed',
            'mode' => 'dry_run',
            'dryrun' => true,
            'presetpath' => $presetpath,
            'presets' => [
                'categories',
                'courses',
                'roles',
                'competencies',
                'badges',
            ],
            'source' => 'phpunit',
        ];

        $first = $seeder->seed($options);
        $second = $seeder->seed($options);

        $this->assertInstanceOf(validation_result::class, $first);
        $this->assertInstanceOf(validation_result::class, $second);
        $this->assertTrue($first->is_ok(), $first->to_json());
        $this->assertTrue($second->is_ok(), $second->to_json());
        $this->assertFalse($first->has_errors());
        $this->assertFalse($second->has_errors());
    }

    /**
     * Reset must not apply destructive changes without confirmation.
     */
    public function test_reset_requires_explicit_confirmation(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $result = $seeder->reset([
            'action' => 'reset',
            'mode' => 'apply',
            'scope' => 'reset_all_uckk_seeded_content',
            'presetpath' => $presetpath,
            'presets' => ['categories', 'courses'],
            'dryrun' => false,
            'rollbackplan' => false,
            'force' => false,
            'confirm' => false,
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertFalse($result->is_ok());
        $this->assertTrue($result->has_errors());
    }

    /**
     * Reset in dry-run mode should be allowed without applying changes.
     */
    public function test_reset_dry_run_returns_plan(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $result = $seeder->reset([
            'action' => 'reset',
            'mode' => 'dry_run',
            'scope' => 'reset_seed_logs',
            'presetpath' => $presetpath,
            'presets' => [],
            'dryrun' => true,
            'rollbackplan' => true,
            'force' => false,
            'confirm' => false,
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertTrue($result->is_ok(), $result->to_json());
    }

    /**
     * Exporting a preset should return the canonical schema.
     */
    public function test_export_preset_returns_canonical_schema(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $result = $seeder->export_preset([
            'action' => 'export_preset',
            'mode' => 'report',
            'presetpath' => $presetpath,
            'preset' => 'competencies',
            'json' => true,
            'source' => 'phpunit',
        ]);

        $this->assertInstanceOf(validation_result::class, $result);
        $this->assertTrue($result->is_ok(), $result->to_json());

        $metadata = $result->get_metadata();

        $this->assertArrayHasKey('preset', $metadata);
        $this->assertSame('competencies', $metadata['preset']);
        $this->assertArrayHasKey('export', $metadata);
        $this->assertIsArray($metadata['export']);
        $this->assertSame('uckkseed.preset.v1', $metadata['export']['schema']);
        $this->assertSame('tool_uckkseed', $metadata['export']['component']);
        $this->assertSame('competencies', $metadata['export']['preset']);
        $this->assertSame(2026051200, $metadata['export']['version']);
        $this->assertArrayHasKey('items', $metadata['export']);
        $this->assertIsArray($metadata['export']['items']);
    }

    /**
     * The seeder can load selected presets by canonical preset id.
     */
    public function test_load_presets_returns_selected_presets(): void {
        $presetpath = $this->create_fixture_preset_directory();
        $seeder = new seeder();

        $presets = $seeder->load_presets([
            'categories',
            'courses',
            'competencies',
        ], [
            'presetpath' => $presetpath,
        ]);

        $this->assertIsArray($presets);
        $this->assertArrayHasKey('categories', $presets);
        $this->assertArrayHasKey('courses', $presets);
        $this->assertArrayHasKey('competencies', $presets);
        $this->assertArrayNotHasKey('badges', $presets);

        foreach ($presets as $presetid => $preset) {
            $this->assertSame('uckkseed.preset.v1', $preset['schema']);
            $this->assertSame('tool_uckkseed', $preset['component']);
            $this->assertSame($presetid, $preset['preset']);
            $this->assertSame(2026051200, $preset['version']);
            $this->assertArrayHasKey('items', $preset);
            $this->assertIsArray($preset['items']);
        }
    }

    /**
     * Run and log tables accept canonical rows.
     */
    public function test_run_and_log_tables_accept_canonical_rows(): void {
        global $DB, $USER;

        $this->setAdminUser();

        $now = time();

        $run = new stdClass();
        $run->action = 'validate';
        $run->mode = 'dry_run';
        $run->status = 'completed';
        $run->component = 'tool_uckkseed';
        $run->preset = 'competencies';
        $run->summary = 'PHPUnit validation run';
        $run->userid = $USER->id;
        $run->createdby = $USER->id;
        $run->modifiedby = $USER->id;
        $run->timecreated = $now;
        $run->timemodified = $now;
        $run->metadata = json_encode([
            'source' => 'phpunit',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $runid = $DB->insert_record('tool_uckkseed_run', $run);

        $log = new stdClass();
        $log->runid = $runid;
        $log->level = 'info';
        $log->action = 'validate';
        $log->mode = 'dry_run';
        $log->status = 'completed';
        $log->component = 'tool_uckkseed';
        $log->preset = 'competencies';
        $log->targettype = 'competency';
        $log->targetkey = 'UCKK-COMP-001';
        $log->targetid = 0;
        $log->message = 'Validated competency preset.';
        $log->userid = $USER->id;
        $log->timecreated = $now;
        $log->metadata = json_encode([
            'source' => 'phpunit',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $logid = $DB->insert_record('tool_uckkseed_log', $log);

        $this->assertTrue($DB->record_exists('tool_uckkseed_run', ['id' => $runid]));
        $this->assertTrue($DB->record_exists('tool_uckkseed_log', ['id' => $logid, 'runid' => $runid]));

        $storedrun = $DB->get_record('tool_uckkseed_run', ['id' => $runid], '*', MUST_EXIST);
        $storedlog = $DB->get_record('tool_uckkseed_log', ['id' => $logid], '*', MUST_EXIST);

        $this->assertSame('validate', $storedrun->action);
        $this->assertSame('dry_run', $storedrun->mode);
        $this->assertSame('completed', $storedrun->status);
        $this->assertSame('tool_uckkseed', $storedrun->component);
        $this->assertSame('competencies', $storedrun->preset);

        $this->assertSame('info', $storedlog->level);
        $this->assertSame('competency', $storedlog->targettype);
        $this->assertSame('UCKK-COMP-001', $storedlog->targetkey);
    }

    /**
     * Create a temporary preset directory with canonical fixture preset files.
     *
     * @return string
     */
    private function create_fixture_preset_directory(): string {
        $dir = make_temp_directory('tool_uckkseed_presets_' . uniqid('', true));

        $presets = [
            'categories' => $this->preset_categories(),
            'courses' => $this->preset_courses(),
            'cohorts' => $this->preset_cohorts(),
            'roles' => $this->preset_roles(),
            'capabilities' => $this->preset_capabilities(),
            'competencies' => $this->preset_competencies(),
            'badges' => $this->preset_badges(),
            'reports' => $this->preset_reports(),
            'course_templates' => $this->preset_course_templates(),
            'challenge_templates' => $this->preset_challenge_templates(),
            'assembly_templates' => $this->preset_assembly_templates(),
            'archive_templates' => $this->preset_archive_templates(),
        ];

        foreach ($presets as $presetid => $preset) {
            file_put_contents(
                $dir . '/' . $presetid . '.json',
                json_encode($preset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        return $dir;
    }

    /**
     * Base preset wrapper.
     *
     * @param string $preset Preset id.
     * @param array<int, array<string, mixed>> $items Items.
     * @param array<string, mixed> $extra Extra root data.
     * @return array<string, mixed>
     */
    private function preset(string $preset, array $items, array $extra = []): array {
        return array_merge([
            'schema' => 'uckkseed.preset.v1',
            'component' => 'tool_uckkseed',
            'preset' => $preset,
            'version' => 2026051200,
            'items' => $items,
        ], $extra);
    }

    /**
     * Category fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_categories(): array {
        return $this->preset('categories', [
            [
                'key' => 'uckk_root',
                'name' => 'UCKK',
                'idnumber' => 'UCKK',
                'parent' => null,
                'description' => 'UCKK root category.',
                'sortorder' => 10,
                'visible' => true,
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
            [
                'key' => 'tronc_commun',
                'name' => 'Tronc commun',
                'idnumber' => 'UCKK-TC',
                'parent' => 'uckk_root',
                'description' => 'UCKK common core.',
                'sortorder' => 20,
                'visible' => true,
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Course fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_courses(): array {
        return $this->preset('courses', [
            [
                'key' => 'COURSE_TC101',
                'fullname' => 'UCKK-TC101 — Lire le jeu social',
                'shortname' => 'UCKK-TC101',
                'idnumber' => 'UCKK-TC101',
                'category' => 'tronc_commun',
                'format' => 'uckk',
                'template' => 'uckk_standard_course',
                'summary' => 'Cours canonique du tronc commun.',
                'visible' => true,
                'startdate' => 0,
                'enddate' => 0,
                'sections' => [
                    'orientation',
                    'concepts',
                    'canon',
                    'atelier',
                    'preuves',
                    'deliberation',
                    'livrable',
                    'evaluation',
                    'archive',
                ],
                'completion' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Cohort fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_cohorts(): array {
        return $this->preset('cohorts', [
            [
                'key' => 'uckk_players',
                'name' => 'Joueurs UCKK',
                'idnumber' => 'UCKK-PLAYERS',
                'context' => 'system',
                'description' => 'Default UCKK player cohort.',
                'visible' => true,
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Role fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_roles(): array {
        return $this->preset('roles', [
            [
                'shortname' => 'uckkmanager',
                'name' => 'UCKK manager',
                'description' => 'Technical UCKK manager role.',
                'archetype' => 'manager',
                'contextlevels' => ['system', 'course_category', 'course'],
                'capabilities' => [
                    'local/uckk:viewcampus',
                    'tool/uckkseed:seed',
                    'tool/uckkseed:validate',
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
            [
                'shortname' => 'uckkplayer',
                'name' => 'UCKK player',
                'description' => 'Technical UCKK learner/player role.',
                'archetype' => 'student',
                'contextlevels' => ['course'],
                'capabilities' => [
                    'local/uckk:viewcampus',
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Capability fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_capabilities(): array {
        return $this->preset('capabilities', [
            [
                'role' => 'uckkmanager',
                'capability' => 'tool/uckkseed:seed',
                'permission' => 'allow',
                'context' => 'system',
                'component' => 'tool_uckkseed',
            ],
            [
                'role' => 'uckkmanager',
                'capability' => 'tool/uckkseed:reset',
                'permission' => 'allow',
                'context' => 'system',
                'component' => 'tool_uckkseed',
            ],
            [
                'role' => 'uckkmanager',
                'capability' => 'tool/uckkseed:validate',
                'permission' => 'allow',
                'context' => 'system',
                'component' => 'tool_uckkseed',
            ],
        ]);
    }

    /**
     * Competency fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_competencies(): array {
        return $this->preset('competencies', [
            [
                'key' => 'COMP_READ_GAME',
                'idnumber' => 'UCKK-COMP-001',
                'shortname' => 'Lire le jeu social',
                'description' => 'Observer une situation sociale et reconnaître ses règles.',
                'framework' => 'uckk_core_competencies',
                'parent' => null,
                'scale' => 'uckk_competency_scale',
                'sortorder' => 10,
                'metadata' => [
                    'status' => 'active',
                    'visibility' => 'institution',
                    'provenance' => 'system',
                    'requires_evidence' => true,
                    'requires_human_validation' => true,
                ],
            ],
            [
                'key' => 'COMP_PRODUCE_PROOF',
                'idnumber' => 'UCKK-COMP-005',
                'shortname' => 'Produire une preuve',
                'description' => 'Créer et documenter une preuve vérifiable.',
                'framework' => 'uckk_core_competencies',
                'parent' => null,
                'scale' => 'uckk_competency_scale',
                'sortorder' => 50,
                'metadata' => [
                    'status' => 'active',
                    'visibility' => 'institution',
                    'provenance' => 'system',
                    'requires_evidence' => true,
                    'requires_human_validation' => true,
                ],
            ],
        ], [
            'frameworks' => [
                [
                    'key' => 'uckk_core_competencies',
                    'idnumber' => 'UCKK-COMP-FRAMEWORK',
                    'shortname' => 'Compétences UCKK',
                    'description' => 'Référentiel canonique UCKK.',
                    'scale' => 'uckk_competency_scale',
                    'visible' => true,
                    'metadata' => [
                        'status' => 'active',
                        'visibility' => 'institution',
                        'provenance' => 'system',
                    ],
                ],
            ],
            'scales' => [
                [
                    'key' => 'uckk_competency_scale',
                    'idnumber' => 'UCKK-COMP-SCALE',
                    'name' => 'Échelle de maîtrise UCKK',
                    'items' => [
                        ['key' => 'not_observed', 'name' => 'Non observé', 'sortorder' => 10],
                        ['key' => 'emerging', 'name' => 'Émergent', 'sortorder' => 20],
                        ['key' => 'developing', 'name' => 'En développement', 'sortorder' => 30],
                        ['key' => 'demonstrated', 'name' => 'Démontré', 'sortorder' => 40],
                        ['key' => 'strong', 'name' => 'Solide', 'sortorder' => 50],
                    ],
                    'metadata' => [
                        'status' => 'active',
                        'visibility' => 'institution',
                        'provenance' => 'system',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Badge fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_badges(): array {
        return $this->preset('badges', [
            [
                'key' => 'joueur_initie',
                'name' => 'Joueur initié',
                'description' => 'Première reconnaissance UCKK interne.',
                'type' => 'badge',
                'criteria' => [
                    'competencies' => ['UCKK-COMP-001'],
                    'requires_evidence' => true,
                    'requires_human_validation' => true,
                ],
                'competencies' => ['UCKK-COMP-001'],
                'requiredarchive' => true,
                'requireshumanvalidation' => true,
                'metadata' => [
                    'status' => 'active',
                    'visibility' => 'institution',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Report fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_reports(): array {
        return $this->preset('reports', [
            [
                'key' => 'uckk_overview',
                'name' => 'UCKK overview',
                'component' => 'report_uckk',
                'capability' => 'report/uckk:view',
                'source' => 'local_uckk',
                'description' => 'Overview report configuration.',
                'enabled' => true,
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Course template fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_course_templates(): array {
        return $this->preset('course_templates', [
            [
                'key' => 'uckk_standard_course',
                'name' => 'UCKK standard course',
                'component' => 'format_uckk',
                'description' => 'Standard UCKK nine-section course template.',
                'defaults' => [
                    'format' => 'uckk',
                    'visible' => true,
                ],
                'sections' => [
                    'orientation',
                    'concepts',
                    'canon',
                    'atelier',
                    'preuves',
                    'deliberation',
                    'livrable',
                    'evaluation',
                    'archive',
                ],
                'activities' => [],
                'completion' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Challenge template fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_challenge_templates(): array {
        return $this->preset('challenge_templates', [
            [
                'key' => 'uckk_default_challenge',
                'name' => 'UCKK default challenge',
                'component' => 'mod_uckkchallenge',
                'description' => 'Default challenge template.',
                'defaults' => [
                    'status' => 'active',
                    'visibility' => 'course',
                ],
                'sections' => [],
                'activities' => [],
                'completion' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Assembly template fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_assembly_templates(): array {
        return $this->preset('assembly_templates', [
            [
                'key' => 'uckk_default_assembly',
                'name' => 'UCKK default assembly',
                'component' => 'mod_uckkassembly',
                'description' => 'Default assembly template.',
                'defaults' => [
                    'status' => 'active',
                    'visibility' => 'course',
                ],
                'sections' => [],
                'activities' => [],
                'completion' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }

    /**
     * Archive template fixture.
     *
     * @return array<string, mixed>
     */
    private function preset_archive_templates(): array {
        return $this->preset('archive_templates', [
            [
                'key' => 'uckk_default_archive',
                'name' => 'UCKK default archive',
                'component' => 'mod_uckkarchive',
                'description' => 'Default archive template.',
                'defaults' => [
                    'status' => 'active',
                    'visibility' => 'course',
                ],
                'sections' => [],
                'activities' => [],
                'completion' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'status' => 'active',
                    'provenance' => 'system',
                ],
            ],
        ]);
    }
}

