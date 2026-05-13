<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Tests for UCKK Challenge lib.php callbacks.
 *
 * @package    mod_uckkchallenge
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge;

use cm_info;
use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/uckkchallenge/lib.php');

/**
 * Unit tests for UCKK Challenge activity callbacks.
 */
final class lib_test extends \advanced_testcase {
    /**
     * Reset database state before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * The activity declares the expected Moodle feature support.
     */
    public function test_supports_expected_features(): void {
        $this->assertTrue(uckkchallenge_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(uckkchallenge_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(uckkchallenge_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(uckkchallenge_supports(FEATURE_BACKUP_MOODLE2));

        // UCKK Challenge completion must be evidence/review based, not view-only.
        $this->assertFalse(uckkchallenge_supports(FEATURE_COMPLETION_TRACKS_VIEWS));

        // Unknown features must not be claimed.
        $this->assertNull(uckkchallenge_supports('unknown_uckk_feature'));
    }

    /**
     * Adding a challenge instance stores the canonical challenge configuration.
     */
    public function test_add_instance_creates_challenge_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);

        $id = uckkchallenge_add_instance($data, null);

        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record('uckkchallenge', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame((int)$course->id, (int)$record->course);
        $this->assertSame($data->name, $record->name);
        $this->assertSame($data->intro, $record->intro);
        $this->assertSame((int)$data->introformat, (int)$record->introformat);
        $this->assertSame($data->challengecode, $record->challengecode);
        $this->assertSame($data->challengetype, $record->challengetype);
        $this->assertSame($data->status, $record->status);
        $this->assertSame($data->visibility, $record->visibility);
        $this->assertSame($data->archivepolicy, $record->archivepolicy);

        $this->assertSame((int)$data->completionrequiresubmission, (int)$record->completionrequiresubmission);
        $this->assertSame((int)$data->completionrequirevalidation, (int)$record->completionrequirevalidation);
        $this->assertSame((int)$data->completionrequireintegrityclear, (int)$record->completionrequireintegrityclear);
        $this->assertSame((int)$data->completionrequirearchive, (int)$record->completionrequirearchive);

        $this->assertGreaterThan(0, (int)$record->timecreated);
        $this->assertGreaterThan(0, (int)$record->timemodified);
    }

    /**
     * Updating a challenge instance changes editable configuration without
     * creating a second instance.
     */
    public function test_update_instance_updates_existing_challenge_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);

        $id = uckkchallenge_add_instance($data, null);
        $before = $DB->get_record('uckkchallenge', ['id' => $id], '*', MUST_EXIST);

        $updated = $this->get_valid_instance_data((int)$course->id);
        $updated->id = $id;
        $updated->instance = $id;
        $updated->name = 'Updated UCKK challenge';
        $updated->challengecode = 'UCKK-DEF-UPDATED';
        $updated->statement = 'Updated challenge statement.';
        $updated->rules = 'Updated challenge rules.';
        $updated->evidencepolicy = 'Updated evidence policy.';
        $updated->criteria = 'Updated evaluation criteria.';
        $updated->status = 'open';
        $updated->visibility = 'program';
        $updated->archivepolicy = 'full';
        $updated->completionrequirearchive = 1;

        $this->assertTrue(uckkchallenge_update_instance($updated, null));

        $after = $DB->get_record('uckkchallenge', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame((int)$before->id, (int)$after->id);
        $this->assertSame('Updated UCKK challenge', $after->name);
        $this->assertSame('UCKK-DEF-UPDATED', $after->challengecode);
        $this->assertSame('Updated challenge statement.', $after->statement);
        $this->assertSame('Updated challenge rules.', $after->rules);
        $this->assertSame('Updated evidence policy.', $after->evidencepolicy);
        $this->assertSame('Updated evaluation criteria.', $after->criteria);
        $this->assertSame('open', $after->status);
        $this->assertSame('program', $after->visibility);
        $this->assertSame('full', $after->archivepolicy);
        $this->assertSame(1, (int)$after->completionrequirearchive);
        $this->assertGreaterThanOrEqual((int)$before->timemodified, (int)$after->timemodified);

        $this->assertEquals(1, $DB->count_records('uckkchallenge', ['course' => $course->id]));
    }

    /**
     * Deleting a challenge instance removes the main activity record.
     */
    public function test_delete_instance_removes_challenge_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);

        $id = uckkchallenge_add_instance($data, null);

        $this->assertTrue($DB->record_exists('uckkchallenge', ['id' => $id]));
        $this->assertTrue(uckkchallenge_delete_instance($id));
        $this->assertFalse($DB->record_exists('uckkchallenge', ['id' => $id]));
    }

    /**
     * Deleting a challenge instance removes directly owned child records.
     */
    public function test_delete_instance_removes_challenge_child_records(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $reviewer = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $challengeid = uckkchallenge_add_instance($data, null);

        $corridorid = $this->insert_record_if_table_exists('uckkchallenge_corr', [
            'challengeid' => $challengeid,
            'shortname' => 'course_corridor',
            'name' => 'Course corridor',
            'description' => 'Course-bound evidence path.',
            'audience' => 'Course participants',
            'entrypoint' => 'Moodle course',
            'actionmode' => 'Evidence submission',
            'evidencerequirement' => 'Text, file, or URL proof.',
            'ethicallimits' => 'No harassment, doxxing, or fabricated evidence.',
            'risklevel' => 'medium',
            'visibility' => 'course',
            'status' => 'active',
            'required' => 1,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
            'metadata' => '{}',
        ]);

        $ruleid = $this->insert_record_if_table_exists('uckkchallenge_rule', [
            'challengeid' => $challengeid,
            'shortname' => 'respect_dignity',
            'name' => 'Respect dignity',
            'description' => 'The spectacle is allowed. Abuse is not.',
            'status' => 'active',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
            'metadata' => '{}',
        ]);

        $submissionid = $this->insert_record_if_table_exists('uckkchallenge_sub', [
            'challengeid' => $challengeid,
            'userid' => $user->id,
            'status' => 'submitted',
            'visibility' => 'course',
            'submissiontext' => 'Evidence text.',
            'submissiontextformat' => FORMAT_HTML,
            'submissionurl' => '',
            'relationtocriteria' => 'Maps to the proof criteria.',
            'provenancestatement' => 'Human-authored proof.',
            'sourceauthor' => fullname($user),
            'sourcedate' => time(),
            'aiassisted' => 0,
            'ailog' => '',
            'uncertaintynotes' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        if ($submissionid > 0) {
            $proofid = $this->insert_record_if_table_exists('uckkchallenge_proof', [
                'submissionid' => $submissionid,
                'userid' => $user->id,
                'prooftype' => 'text',
                'title' => 'Proof record',
                'description' => 'Proof description.',
                'source' => 'Learner submission',
                'author' => fullname($user),
                'sourcedate' => time(),
                'visibility' => 'course',
                'relationtocriteria' => 'Supports evaluation criteria.',
                'provenance' => 'human',
                'integritystate' => 'unverified',
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $evaluationid = $this->insert_record_if_table_exists('uckkchallenge_eval', [
                'submissionid' => $submissionid,
                'userid' => $user->id,
                'reviewerid' => $reviewer->id,
                'status' => 'validated',
                'validationstate' => 'human_reviewed',
                'feedback' => 'Validated by mentor.',
                'privatefeedback' => 'Private mentor note.',
                'competencyrating' => 'met',
                'badgetriggered' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $stateid = $this->insert_record_if_table_exists('uckkchallenge_state', [
                'submissionid' => $submissionid,
                'userid' => $user->id,
                'status' => 'validated',
                'integritystate' => 'human_reviewed',
                'archived' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'metadata' => '{}',
            ]);
        }

        $this->assertTrue(uckkchallenge_delete_instance($challengeid));

        $this->assertFalse($DB->record_exists('uckkchallenge', ['id' => $challengeid]));

        if (!empty($corridorid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_corr', ['id' => $corridorid]));
        }

        if (!empty($ruleid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_rule', ['id' => $ruleid]));
        }

        if (!empty($submissionid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_sub', ['id' => $submissionid]));
        }

        if (!empty($proofid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_proof', ['id' => $proofid]));
        }

        if (!empty($evaluationid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_eval', ['id' => $evaluationid]));
        }

        if (!empty($stateid)) {
            $this->assertFalse($DB->record_exists('uckkchallenge_state', ['id' => $stateid]));
        }
    }

    /**
     * Course module info exposes the user-facing name and intro.
     */
    public function test_get_coursemodule_info_returns_instance_display_data(): void {
        global $DB;

        if (!function_exists('uckkchallenge_get_coursemodule_info')) {
            $this->markTestSkipped('uckkchallenge_get_coursemodule_info is not implemented.');
        }

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);
        $id = uckkchallenge_add_instance($data, null);

        $module = $DB->get_record('modules', ['name' => 'uckkchallenge'], '*', MUST_EXIST);

        $cm = (object)[
            'id' => 123,
            'course' => $course->id,
            'module' => $module->id,
            'instance' => $id,
            'modname' => 'uckkchallenge',
            'name' => $data->name,
        ];

        $info = uckkchallenge_get_coursemodule_info($cm);

        $this->assertInstanceOf(cm_info::class, $info);
        $this->assertSame($data->name, $info->name);
    }

    /**
     * Completion rules configured through lib.php remain evidence-based.
     */
    public function test_completion_custom_rules_are_preserved_by_add_and_update(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        $data = $this->get_valid_instance_data((int)$course->id);
        $data->completionrequiresubmission = 1;
        $data->completionrequirevalidation = 1;
        $data->completionrequireintegrityclear = 1;
        $data->completionrequirearchive = 0;

        $id = uckkchallenge_add_instance($data, null);
        $record = $DB->get_record('uckkchallenge', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame(1, (int)$record->completionrequiresubmission);
        $this->assertSame(1, (int)$record->completionrequirevalidation);
        $this->assertSame(1, (int)$record->completionrequireintegrityclear);
        $this->assertSame(0, (int)$record->completionrequirearchive);

        $data->id = $id;
        $data->instance = $id;
        $data->completionrequirearchive = 1;

        $this->assertTrue(uckkchallenge_update_instance($data, null));

        $updated = $DB->get_record('uckkchallenge', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame(1, (int)$updated->completionrequiresubmission);
        $this->assertSame(1, (int)$updated->completionrequirevalidation);
        $this->assertSame(1, (int)$updated->completionrequireintegrityclear);
        $this->assertSame(1, (int)$updated->completionrequirearchive);
    }

    /**
     * Build valid activity instance data for add/update callbacks.
     *
     * @param int $courseid Course id.
     * @return stdClass
     */
    private function get_valid_instance_data(int $courseid): stdClass {
        $now = time();

        return (object)[
            'course' => $courseid,
            'name' => 'UCKK Challenge — Map a hidden rule',
            'intro' => '<p>Challenge introduction.</p>',
            'introformat' => FORMAT_HTML,

            'challengecode' => 'UCKK-DEF-001',
            'challengetype' => 'system_mapping',
            'status' => 'published',

            'statement' => 'Map a hidden rule in a system and explain who benefits from it.',
            'contexttext' => 'This challenge belongs to the UCKK learning and governance method.',
            'rules' => 'Respect dignity. Provide evidence. Distinguish fact from fiction.',
            'corridors' => json_encode([
                [
                    'shortname' => 'course_corridor',
                    'name' => 'Course corridor',
                    'description' => 'Work inside the Moodle course.',
                ],
            ]),
            'ethicalconstraints' => 'No harassment, humiliation, doxxing, intimidation, or fabricated evidence.',

            'evidencepolicy' => 'Submit text, URL, file, dataset, observation, AI log, or decision record.',
            'criteria' => 'Evidence must include source, author, date, visibility, provenance, integrity state, and relation to criteria.',
            'teamsubmissions' => 0,
            'maxsubmissions' => 1,
            'allowresubmission' => 1,

            'timeopen' => $now - DAYSECS,
            'timeclose' => $now + WEEKSECS,
            'timereviewby' => $now + WEEKSECS + DAYSECS,

            'integrityrequired' => 1,
            'visibility' => 'course',
            'integritynotes' => 'Integrity review is required when proof quality, dignity, or fact/fiction boundaries are contested.',
            'aipolicy' => 'AI may assist. It cannot validate facts, grade, award badges, certify competencies, or replace human review.',

            'archivepolicy' => 'summary',
            'publicsummary' => 'Public summary after validation only.',
            'competencylinks' => json_encode(['UCKK-COMP-005', 'UCKK-COMP-013']),
            'badgelinks' => json_encode(['gardien_preuve']),

            'completionrequiresubmission' => 1,
            'completionrequirevalidation' => 1,
            'completionrequireintegrityclear' => 1,
            'completionrequirearchive' => 0,
        ];
    }

    /**
     * Insert a test record only when the table exists.
     *
     * This keeps this test compatible while the final schema is still being
     * generated, but still verifies child cleanup when the table exists.
     *
     * @param string $table Table name without braces.
     * @param array<string, mixed> $recorddata Record data.
     * @return int Inserted id, or 0 when the table does not exist.
     */
    private function insert_record_if_table_exists(string $table, array $recorddata): int {
        global $DB;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists($table)) {
            return 0;
        }

        $columns = $DB->get_columns($table);
        $filtered = [];

        foreach ($recorddata as $field => $value) {
            if (array_key_exists($field, $columns)) {
                $filtered[$field] = $value;
            }
        }

        return (int)$DB->insert_record($table, (object)$filtered);
    }
}