<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Tests for mod_uckkassembly lib.php callbacks.
 *
 * @package    mod_uckkassembly
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/uckkassembly/lib.php');

/**
 * Tests for UCKK Assembly module callbacks.
 */
final class lib_test extends \advanced_testcase {
    /**
     * Prepare each test with a clean database state.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * The activity declares the expected Moodle feature support.
     */
    public function test_supports_expected_activity_features(): void {
        $this->assertSame(MOD_ARCHETYPE_OTHER, \uckkassembly_supports(FEATURE_MOD_ARCHETYPE));

        $this->assertTrue(\uckkassembly_supports(FEATURE_GROUPS));
        $this->assertTrue(\uckkassembly_supports(FEATURE_GROUPINGS));
        $this->assertTrue(\uckkassembly_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(\uckkassembly_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(\uckkassembly_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(\uckkassembly_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(\uckkassembly_supports(FEATURE_BACKUP_MOODLE2));

        $this->assertFalse(\uckkassembly_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertFalse(\uckkassembly_supports(FEATURE_GRADE_OUTCOMES));
        $this->assertFalse(\uckkassembly_supports(FEATURE_RATE));

        $this->assertNull(\uckkassembly_supports('unknown_feature'));
    }

    /**
     * Instance data is normalised before persistence.
     */
    public function test_normalise_instance_record_applies_canonical_defaults(): void {
        $course = $this->getDataGenerator()->create_course();

        $data = (object)[
            'course' => $course->id,
            'name' => 'Assemblée des savoirs',
            'intro' => '<p>Assemblée test</p>',
            'introformat' => FORMAT_HTML,
            'assemblytype' => 'savoirs',
            'status' => 'active',
            'visibility' => 'course',
            'timeopen' => 100,
            'timeclose' => 200,
            'contestuntil' => 300,
            'votingmethod' => 'readings',
            'quorum' => 5,
            'allowmotions' => 1,
            'allowamendments' => 1,
            'allowobjections' => 1,
            'allowcontestations' => 1,
            'minutesformat' => 'structured',
            'archivepolicy' => 'summary',
            'provenance' => 'human',
            'completionrequiresmotion' => 1,
            'completionrequirevote' => 1,
            'completionrequiresdecision' => 1,
            'metadata' => [
                'canonical_cycle' => 'Connaître → Choisir → Agir → Se souvenir',
            ],
        ];

        $record = \uckkassembly_normalise_instance_record($data, true);

        $this->assertSame((int)$course->id, $record->course);
        $this->assertSame('Assemblée des savoirs', $record->name);
        $this->assertSame('<p>Assemblée test</p>', $record->intro);
        $this->assertSame(FORMAT_HTML, $record->introformat);
        $this->assertSame('savoirs', $record->assemblytype);
        $this->assertSame('active', $record->status);
        $this->assertSame('course', $record->visibility);
        $this->assertSame(100, $record->timeopen);
        $this->assertSame(200, $record->timeclose);
        $this->assertSame(300, $record->contestuntil);
        $this->assertSame('readings', $record->votingmethod);
        $this->assertSame(5, $record->quorum);
        $this->assertSame(1, $record->allowmotions);
        $this->assertSame(1, $record->allowamendments);
        $this->assertSame(1, $record->allowobjections);
        $this->assertSame(1, $record->allowcontestations);
        $this->assertSame('structured', $record->minutesformat);
        $this->assertSame('summary', $record->archivepolicy);
        $this->assertSame('human', $record->provenance);
        $this->assertSame(1, $record->completionrequiresmotion);
        $this->assertSame(1, $record->completionrequirevote);
        $this->assertSame(1, $record->completionrequiresdecision);

        $this->assertNotEmpty($record->metadata);
        $metadata = json_decode($record->metadata, true);
        $this->assertSame('Connaître → Choisir → Agir → Se souvenir', $metadata['canonical_cycle']);
    }

    /**
     * Invalid metadata is ignored instead of being persisted as non-JSON.
     */
    public function test_normalise_instance_record_rejects_invalid_metadata(): void {
        $course = $this->getDataGenerator()->create_course();

        $data = $this->get_base_instance_data($course);
        $data->metadata = '{invalid-json';

        $record = \uckkassembly_normalise_instance_record($data, true);

        $this->assertNull($record->metadata);
    }

    /**
     * Add, update and delete callbacks persist and remove the Assembly instance.
     */
    public function test_add_update_and_delete_instance(): void {
        global $DB, $USER;

        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();

        $data = $this->get_base_instance_data($course);
        $data->name = 'Assemblée initiale';

        $id = \uckkassembly_add_instance($data);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($DB->record_exists('uckkassembly', ['id' => $id]));

        $created = $DB->get_record('uckkassembly', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('Assemblée initiale', $created->name);
        $this->assertSame((int)$USER->id, (int)$created->createdby);
        $this->assertSame((int)$USER->id, (int)$created->modifiedby);
        $this->assertSame(1, (int)$created->versionno);

        $updatedata = $this->get_base_instance_data($course);
        $updatedata->instance = $id;
        $updatedata->id = $id;
        $updatedata->name = 'Assemblée modifiée';
        $updatedata->status = 'active';
        $updatedata->visibility = 'program';

        $this->assertTrue(\uckkassembly_update_instance($updatedata));

        $updated = $DB->get_record('uckkassembly', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('Assemblée modifiée', $updated->name);
        $this->assertSame('active', $updated->status);
        $this->assertSame('program', $updated->visibility);
        $this->assertSame(2, (int)$updated->versionno);

        $this->assertTrue(\uckkassembly_delete_instance($id));
        $this->assertFalse($DB->record_exists('uckkassembly', ['id' => $id]));
    }

    /**
     * Deleting a missing instance returns false.
     */
    public function test_delete_missing_instance_returns_false(): void {
        $this->assertFalse(\uckkassembly_delete_instance(999999));
    }

    /**
     * Course module info exposes the activity name and custom timing data.
     */
    public function test_get_coursemodule_info_returns_cached_info(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $assemblyid = $this->insert_assembly($course, [
            'name' => 'Assemblée cache',
            'intro' => '<p>Intro visible</p>',
            'timeopen' => 100,
            'timeclose' => 200,
            'status' => 'active',
            'visibility' => 'course',
        ]);

        $cm = (object)[
            'id' => 77,
            'instance' => $assemblyid,
        ];

        $info = \uckkassembly_get_coursemodule_info($cm);

        $this->assertInstanceOf(\cached_cm_info::class, $info);
        $this->assertSame('Assemblée cache', $info->name);

        $customdata = json_decode($info->customdata, true);
        $this->assertSame(100, $customdata['timeopen']);
        $this->assertSame(200, $customdata['timeclose']);
        $this->assertSame('active', $customdata['status']);
        $this->assertSame('course', $customdata['visibility']);
    }

    /**
     * A missing Assembly record returns no course module cache info.
     */
    public function test_get_coursemodule_info_returns_null_for_missing_instance(): void {
        $cm = (object)[
            'id' => 77,
            'instance' => 999999,
        ];

        $this->assertNull(\uckkassembly_get_coursemodule_info($cm));
    }

    /**
     * File areas are registered for intro, motions, amendments, decisions,
     * minutes and contestations.
     */
    public function test_get_file_areas_returns_expected_areas(): void {
        $course = $this->getDataGenerator()->create_course();
        $context = \context_system::instance();

        $areas = \uckkassembly_get_file_areas($course, (object)[], $context);

        $this->assertArrayHasKey('intro', $areas);
        $this->assertArrayHasKey('motion_attachments', $areas);
        $this->assertArrayHasKey('amendment_attachments', $areas);
        $this->assertArrayHasKey('decision_attachments', $areas);
        $this->assertArrayHasKey('minutes_files', $areas);
        $this->assertArrayHasKey('contest_attachments', $areas);
    }

    /**
     * Only contestation attachments are restricted by default.
     */
    public function test_filearea_restriction_detection(): void {
        $this->assertTrue(\uckkassembly_filearea_is_restricted('contest_attachments'));

        $this->assertFalse(\uckkassembly_filearea_is_restricted('intro'));
        $this->assertFalse(\uckkassembly_filearea_is_restricted('motion_attachments'));
        $this->assertFalse(\uckkassembly_filearea_is_restricted('decision_attachments'));
        $this->assertFalse(\uckkassembly_filearea_is_restricted('minutes_files'));
    }

    /**
     * Completion state is false until required motion, vote and decision records exist.
     */
    public function test_completion_state_requires_configured_participation_records(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course, [
            'completionrequiresmotion' => 1,
            'completionrequirevote' => 1,
            'completionrequiresdecision' => 1,
        ]);

        $cm = (object)[
            'id' => 99,
            'instance' => $assemblyid,
        ];

        $this->assertFalse(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, true));

        $motionid = $this->insert_motion($assemblyid, (int)$user->id);
        $this->assertFalse(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, true));

        $this->insert_vote($assemblyid, $motionid, (int)$user->id);
        $this->assertFalse(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, true));

        $this->insert_decision($assemblyid, $motionid, 'published');

        $this->assertTrue(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, true));
    }

    /**
     * Completion state returns the supplied type when no custom rules are active.
     */
    public function test_completion_state_returns_type_when_no_custom_rules_are_active(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course, [
            'completionrequiresmotion' => 0,
            'completionrequirevote' => 0,
            'completionrequiresdecision' => 0,
        ]);

        $cm = (object)[
            'id' => 99,
            'instance' => $assemblyid,
        ];

        $this->assertTrue(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, true));
        $this->assertFalse(\uckkassembly_get_completion_state($course, $cm, (int)$user->id, false));
    }

    /**
     * Active completion rule descriptions reflect configured rules.
     */
    public function test_get_completion_active_rule_descriptions(): void {
        $course = $this->getDataGenerator()->create_course();

        $assemblyid = $this->insert_assembly($course, [
            'completionrequiresmotion' => 1,
            'completionrequirevote' => 1,
            'completionrequiresdecision' => 0,
        ]);

        $cm = (object)[
            'instance' => $assemblyid,
        ];

        $descriptions = \uckkassembly_get_completion_active_rule_descriptions($cm);

        $this->assertContains(get_string('completiondetail:motion', 'uckkassembly'), $descriptions);
        $this->assertContains(get_string('completiondetail:vote', 'uckkassembly'), $descriptions);
        $this->assertNotContains(get_string('completiondetail:decision', 'uckkassembly'), $descriptions);
    }

    /**
     * User outline summarises motions, votes and contestations for one user.
     */
    public function test_user_outline_summarises_user_participation(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course);
        $assembly = (object)[
            'id' => $assemblyid,
        ];

        $motionid = $this->insert_motion($assemblyid, (int)$user->id);
        $this->insert_vote($assemblyid, $motionid, (int)$user->id);
        $this->insert_contestation($assemblyid, (int)$user->id);

        $outline = \uckkassembly_user_outline($course, $user, (object)[], $assembly);

        $this->assertNotNull($outline);
        $this->assertStringContainsString(get_string('outline:motions', 'uckkassembly', 1), $outline->info);
        $this->assertStringContainsString(get_string('outline:votes', 'uckkassembly', 1), $outline->info);
        $this->assertStringContainsString(get_string('outline:contestations', 'uckkassembly', 1), $outline->info);
        $this->assertGreaterThan(0, $outline->time);
    }

    /**
     * User outline returns null when the user has no participation records.
     */
    public function test_user_outline_returns_null_for_no_participation(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course);
        $assembly = (object)[
            'id' => $assemblyid,
        ];

        $this->assertNull(\uckkassembly_user_outline($course, $user, (object)[], $assembly));
    }

    /**
     * Reset user data removes participant-owned Assembly records.
     */
    public function test_reset_userdata_removes_participant_records(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course);
        $motionid = $this->insert_motion($assemblyid, (int)$user->id);
        $this->insert_vote($assemblyid, $motionid, (int)$user->id);
        $this->insert_amendment($assemblyid, $motionid, (int)$user->id);
        $this->insert_objection($assemblyid, $motionid, (int)$user->id);
        $this->insert_contestation($assemblyid, (int)$user->id);

        $status = \uckkassembly_reset_userdata((object)[
            'courseid' => $course->id,
            'reset_uckkassembly' => 1,
        ]);

        $this->assertCount(1, $status);
        $this->assertFalse($status[0]['error']);

        $this->assertFalse($DB->record_exists('uckkassembly_motion', ['assemblyid' => $assemblyid]));
        $this->assertFalse($DB->record_exists('uckkassembly_vote', ['assemblyid' => $assemblyid]));
        $this->assertFalse($DB->record_exists('uckkassembly_amend', ['assemblyid' => $assemblyid]));
        $this->assertFalse($DB->record_exists('uckkassembly_object', ['assemblyid' => $assemblyid]));
        $this->assertFalse($DB->record_exists('uckkassembly_contest', ['assemblyid' => $assemblyid]));

        $this->assertTrue($DB->record_exists('uckkassembly', ['id' => $assemblyid]));
    }

    /**
     * Reset does nothing when the reset flag is absent.
     */
    public function test_reset_userdata_does_nothing_without_flag(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $assemblyid = $this->insert_assembly($course);
        $this->insert_motion($assemblyid, (int)$user->id);

        $status = \uckkassembly_reset_userdata((object)[
            'courseid' => $course->id,
            'reset_uckkassembly' => 0,
        ]);

        $this->assertSame([], $status);
        $this->assertTrue($DB->record_exists('uckkassembly_motion', ['assemblyid' => $assemblyid]));
    }

    /**
     * View and post action lists expose Assembly activity verbs.
     */
    public function test_view_and_post_actions_are_declared(): void {
        $this->assertContains('view', \uckkassembly_get_view_actions());
        $this->assertContains('view motion', \uckkassembly_get_view_actions());
        $this->assertContains('view decision', \uckkassembly_get_view_actions());
        $this->assertContains('view minutes', \uckkassembly_get_view_actions());

        $this->assertContains('add motion', \uckkassembly_get_post_actions());
        $this->assertContains('add amendment', \uckkassembly_get_post_actions());
        $this->assertContains('add objection', \uckkassembly_get_post_actions());
        $this->assertContains('vote', \uckkassembly_get_post_actions());
        $this->assertContains('publish decision', \uckkassembly_get_post_actions());
        $this->assertContains('contest decision', \uckkassembly_get_post_actions());
        $this->assertContains('archive assembly', \uckkassembly_get_post_actions());
    }

    /**
     * Build default form data for an Assembly instance.
     *
     * @param \stdClass $course Course record.
     * @return \stdClass
     */
    private function get_base_instance_data(\stdClass $course): \stdClass {
        return (object)[
            'course' => $course->id,
            'coursemodule' => 0,
            'name' => 'Assemblée UCKK',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'assemblytype' => 'savoirs',
            'status' => 'draft',
            'visibility' => 'course',
            'timeopen' => 0,
            'timeclose' => 0,
            'contestuntil' => 0,
            'votingmethod' => 'readings',
            'quorum' => 0,
            'allowmotions' => 1,
            'allowamendments' => 1,
            'allowobjections' => 1,
            'allowcontestations' => 1,
            'minutesformat' => 'structured',
            'archivepolicy' => 'summary',
            'provenance' => 'human',
            'completionrequiresmotion' => 0,
            'completionrequirevote' => 0,
            'completionrequiresdecision' => 0,
            'metadata' => null,
        ];
    }

    /**
     * Insert an Assembly record.
     *
     * @param \stdClass $course Course record.
     * @param array<string, mixed> $overrides Override values.
     * @return int
     */
    private function insert_assembly(\stdClass $course, array $overrides = []): int {
        global $DB, $USER;

        $now = time();

        $record = (object)array_merge([
            'course' => $course->id,
            'name' => 'Assemblée UCKK',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'assemblytype' => 'savoirs',
            'status' => 'active',
            'visibility' => 'course',
            'timeopen' => 0,
            'timeclose' => 0,
            'contestuntil' => 0,
            'votingmethod' => 'readings',
            'quorum' => 0,
            'allowmotions' => 1,
            'allowamendments' => 1,
            'allowobjections' => 1,
            'allowcontestations' => 1,
            'minutesformat' => 'structured',
            'archivepolicy' => 'summary',
            'provenance' => 'human',
            'completionrequiresmotion' => 0,
            'completionrequirevote' => 0,
            'completionrequiresdecision' => 0,
            'metadata' => null,
            'createdby' => $USER->id ?: 2,
            'modifiedby' => $USER->id ?: 2,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
        ], $overrides);

        return (int)$DB->insert_record('uckkassembly', $record);
    }

    /**
     * Insert a motion record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return int
     */
    private function insert_motion(int $assemblyid, int $userid): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_motion', (object)[
            'assemblyid' => $assemblyid,
            'title' => 'Motion test',
            'body' => 'Motion body.',
            'motiontype' => 'information',
            'status' => 'active',
            'visibility' => 'course',
            'provenance' => 'human',
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }

    /**
     * Insert an amendment record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param int $userid User id.
     * @return int
     */
    private function insert_amendment(int $assemblyid, int $motionid, int $userid): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_amend', (object)[
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'body' => 'Amendment body.',
            'status' => 'active',
            'visibility' => 'course',
            'provenance' => 'human',
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }

    /**
     * Insert an objection record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param int $userid User id.
     * @return int
     */
    private function insert_objection(int $assemblyid, int $motionid, int $userid): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_object', (object)[
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'body' => 'Objection body.',
            'status' => 'active',
            'visibility' => 'course',
            'provenance' => 'human',
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }

    /**
     * Insert a vote record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param int $userid User id.
     * @return int
     */
    private function insert_vote(int $assemblyid, int $motionid, int $userid): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_vote', (object)[
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'decisionid' => null,
            'userid' => $userid,
            'votevalue' => 'reading',
            'rationale' => 'Reading recorded.',
            'status' => 'active',
            'visibility' => 'course',
            'provenance' => 'human',
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }

    /**
     * Insert a decision record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param string $status Decision status.
     * @return int
     */
    private function insert_decision(int $assemblyid, int $motionid, string $status = 'published'): int {
        global $DB, $USER;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_decision', (object)[
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'decisiontype' => 'information',
            'title' => 'Decision test',
            'body' => 'Decision body.',
            'publicsummary' => 'Decision summary.',
            'status' => $status,
            'visibility' => 'course',
            'integritystate' => 'unverified',
            'provenance' => 'human',
            'contestuntil' => 0,
            'contestable' => 1,
            'sortorder' => 0,
            'createdby' => $USER->id ?: 2,
            'modifiedby' => $USER->id ?: 2,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }

    /**
     * Insert a contestation record.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return int
     */
    private function insert_contestation(int $assemblyid, int $userid): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record('uckkassembly_contest', (object)[
            'assemblyid' => $assemblyid,
            'decisionid' => null,
            'summary' => 'Contestation summary.',
            'body' => 'Contestation body.',
            'status' => 'active',
            'visibility' => 'course',
            'provenance' => 'human',
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'versionno' => 1,
            'metadata' => null,
        ]);
    }
}