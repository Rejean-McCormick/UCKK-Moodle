<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Tests for UCKK Archive lib.php callbacks.
 *
 * @package    mod_uckkarchive
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive;

use cached_cm_info;
use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/uckkarchive/lib.php');

/**
 * Unit tests for UCKK Archive activity callbacks.
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
        $this->assertTrue(uckkarchive_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(uckkarchive_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(uckkarchive_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(uckkarchive_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(uckkarchive_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(uckkarchive_supports(FEATURE_GROUPS));
        $this->assertTrue(uckkarchive_supports(FEATURE_GROUPINGS));

        // UCKK Archive is institutional memory, not a gradebook activity.
        $this->assertFalse(uckkarchive_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertFalse(uckkarchive_supports(FEATURE_GRADE_OUTCOMES));
        $this->assertFalse(uckkarchive_supports(FEATURE_RATE));
        $this->assertFalse(uckkarchive_supports(FEATURE_PLAGIARISM));

        $this->assertSame(MOD_PURPOSE_CONTENT, uckkarchive_supports(FEATURE_MOD_PURPOSE));

        // Unknown features must not be claimed.
        $this->assertNull(uckkarchive_supports('unknown_uckk_feature'));
    }

    /**
     * Adding an archive instance stores canonical archive configuration.
     */
    public function test_add_instance_creates_archive_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);

        $id = uckkarchive_add_instance($data, null);

        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record('uckkarchive', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame((int)$course->id, (int)$record->course);
        $this->assertSame($data->name, $record->name);
        $this->assertSame($data->intro, $record->intro);
        $this->assertSame((int)$data->introformat, (int)$record->introformat);
        $this->assertSame('active', $record->status);
        $this->assertSame('course', $record->visibility);
        $this->assertSame(1, (int)$record->completionadditem);
        $this->assertSame(0, (int)$record->completionvalidateitem);
        $this->assertSame(1, (int)$record->versionno);

        $this->assertGreaterThan(0, (int)$record->createdby);
        $this->assertGreaterThan(0, (int)$record->modifiedby);
        $this->assertGreaterThan(0, (int)$record->timecreated);
        $this->assertGreaterThan(0, (int)$record->timemodified);

        $metadata = json_decode((string)$record->metadata, true);
        $this->assertIsArray($metadata);
        $this->assertSame('lib_test', $metadata['fixture']);
    }

    /**
     * Updating an archive instance changes editable configuration without
     * creating a second instance.
     */
    public function test_update_instance_updates_archive_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);

        $id = uckkarchive_add_instance($data, null);

        $data->id = $id;
        $data->instance = $id;
        $data->name = 'Updated UCKK archive';
        $data->intro = '<p>Updated archive intro.</p>';
        $data->status = 'pending_review';
        $data->visibility = 'restricted_integrity';
        $data->completionadditem = 1;
        $data->completionvalidateitem = 1;
        $data->metadata = [
            'fixture' => 'lib_test',
            'updated' => true,
        ];

        $this->assertTrue(uckkarchive_update_instance($data, null));

        $records = $DB->get_records('uckkarchive');
        $this->assertCount(1, $records);

        $record = $DB->get_record('uckkarchive', ['id' => $id], '*', MUST_EXIST);

        $this->assertSame('Updated UCKK archive', $record->name);
        $this->assertSame('<p>Updated archive intro.</p>', $record->intro);
        $this->assertSame('pending_review', $record->status);
        $this->assertSame('restricted_integrity', $record->visibility);
        $this->assertSame(1, (int)$record->completionadditem);
        $this->assertSame(1, (int)$record->completionvalidateitem);

        $metadata = json_decode((string)$record->metadata, true);
        $this->assertIsArray($metadata);
        $this->assertTrue($metadata['updated']);
    }

    /**
     * Deleting an archive instance removes the main record.
     */
    public function test_delete_instance_removes_archive_record(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);
        $id = uckkarchive_add_instance($data, null);

        $this->assertTrue($DB->record_exists('uckkarchive', ['id' => $id]));
        $this->assertTrue(uckkarchive_delete_instance($id));
        $this->assertFalse($DB->record_exists('uckkarchive', ['id' => $id]));
    }

    /**
     * Deleting a missing archive instance is a no-op.
     */
    public function test_delete_missing_instance_returns_false(): void {
        $this->assertFalse(uckkarchive_delete_instance(999999));
    }

    /**
     * Status normalisation preserves canonical statuses and falls back safely.
     *
     * @dataProvider normalise_status_provider
     * @param string|null $input Raw input.
     * @param string $expected Expected status.
     */
    public function test_normalise_status(?string $input, string $expected): void {
        $this->assertSame($expected, uckkarchive_normalise_status($input));
    }

    /**
     * Data provider for {@see self::test_normalise_status()}.
     *
     * @return \Generator
     */
    public static function normalise_status_provider(): \Generator {
        yield 'draft' => ['draft', 'draft'];
        yield 'active' => ['active', 'active'];
        yield 'pending review' => ['pending_review', 'pending_review'];
        yield 'validated' => ['validated', 'validated'];
        yield 'rejected' => ['rejected', 'rejected'];
        yield 'correction required' => ['correction_required', 'correction_required'];
        yield 'contested' => ['contested', 'contested'];
        yield 'invalidated' => ['invalidated', 'invalidated'];
        yield 'closed' => ['closed', 'closed'];
        yield 'archived' => ['archived', 'archived'];
        yield 'unknown defaults active' => ['unknown_status', 'active'];
        yield 'empty defaults active' => ['', 'active'];
        yield 'null defaults active' => [null, 'active'];
    }

    /**
     * Visibility normalisation preserves canonical visibilities and falls back
     * safely to course visibility.
     *
     * @dataProvider normalise_visibility_provider
     * @param string|null $input Raw input.
     * @param string $expected Expected visibility.
     */
    public function test_normalise_visibility(?string $input, string $expected): void {
        $this->assertSame($expected, uckkarchive_normalise_visibility($input));
    }

    /**
     * Data provider for {@see self::test_normalise_visibility()}.
     *
     * @return \Generator
     */
    public static function normalise_visibility_provider(): \Generator {
        yield 'private' => ['private', 'private'];
        yield 'user' => ['user', 'user'];
        yield 'group' => ['group', 'group'];
        yield 'course' => ['course', 'course'];
        yield 'cohort' => ['cohort', 'cohort'];
        yield 'program' => ['program', 'program'];
        yield 'institution' => ['institution', 'institution'];
        yield 'public' => ['public', 'public'];
        yield 'restricted' => ['restricted', 'restricted'];
        yield 'restricted integrity' => ['restricted_integrity', 'restricted_integrity'];
        yield 'hidden' => ['hidden', 'hidden'];
        yield 'archived' => ['archived', 'archived'];
        yield 'unknown defaults course' => ['unknown_visibility', 'course'];
        yield 'empty defaults course' => ['', 'course'];
        yield 'null defaults course' => [null, 'course'];
    }

    /**
     * Metadata normalisation accepts arrays, stdClass values, and valid JSON.
     */
    public function test_normalise_metadata_accepts_supported_inputs(): void {
        $array = [
            'source' => 'phpunit',
            'nested' => [
                'ok' => true,
            ],
        ];

        $arrayjson = uckkarchive_normalise_metadata($array);
        $this->assertNotNull($arrayjson);

        $decodedarray = json_decode($arrayjson, true);
        $this->assertSame('phpunit', $decodedarray['source']);
        $this->assertTrue($decodedarray['nested']['ok']);

        $object = new stdClass();
        $object->source = 'object';

        $objectjson = uckkarchive_normalise_metadata($object);
        $this->assertNotNull($objectjson);

        $decodedobject = json_decode($objectjson, true);
        $this->assertSame('object', $decodedobject['source']);

        $json = uckkarchive_normalise_metadata('{"source":"json"}');
        $this->assertNotNull($json);

        $decodedjson = json_decode($json, true);
        $this->assertSame('json', $decodedjson['source']);
    }

    /**
     * Metadata normalisation rejects invalid metadata.
     */
    public function test_normalise_metadata_rejects_invalid_inputs(): void {
        $this->assertNull(uckkarchive_normalise_metadata(null));
        $this->assertNull(uckkarchive_normalise_metadata(''));
        $this->assertNull(uckkarchive_normalise_metadata('not json'));
        $this->assertNull(uckkarchive_normalise_metadata(42));
    }

    /**
     * The archive declares the canonical file areas.
     */
    public function test_get_fileareas_returns_canonical_archive_fileareas(): void {
        $this->assertSame([
            'proof_files',
            'decision_attachments',
            'minutes_files',
            'kristal_files',
            'portfolio_files',
            'integrity_exports',
        ], uckkarchive_get_fileareas());
    }

    /**
     * File areas map to their owning archive tables.
     *
     * @dataProvider filearea_table_provider
     * @param string $filearea File area.
     * @param string $expectedtable Expected table.
     */
    public function test_get_filearea_table(string $filearea, string $expectedtable): void {
        $this->assertSame($expectedtable, uckkarchive_get_filearea_table($filearea));
    }

    /**
     * Data provider for {@see self::test_get_filearea_table()}.
     *
     * @return \Generator
     */
    public static function filearea_table_provider(): \Generator {
        yield 'proof files' => ['proof_files', 'uckkarchive_proof'];
        yield 'kristal files' => ['kristal_files', 'uckkarchive_kristal'];
        yield 'integrity exports' => ['integrity_exports', 'uckkarchive_export'];
        yield 'decision attachments' => ['decision_attachments', 'uckkarchive_item'];
        yield 'minutes files' => ['minutes_files', 'uckkarchive_item'];
        yield 'portfolio files' => ['portfolio_files', 'uckkarchive_item'];
        yield 'unknown' => ['unknown_filearea', ''];
    }

    /**
     * Course module info exposes user-facing name, intro, and custom data.
     */
    public function test_get_coursemodule_info_returns_instance_display_data(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $data = $this->get_valid_instance_data((int)$course->id);
        $id = uckkarchive_add_instance($data, null);

        $module = $DB->get_record('modules', ['name' => 'uckkarchive'], '*', MUST_EXIST);

        $cm = (object)[
            'id' => 123,
            'course' => $course->id,
            'module' => $module->id,
            'instance' => $id,
            'modname' => 'uckkarchive',
            'name' => $data->name,
        ];

        $info = uckkarchive_get_coursemodule_info($cm);

        $this->assertInstanceOf(cached_cm_info::class, $info);
        $this->assertSame($data->name, $info->name);
        $this->assertIsArray($info->customdata);
        $this->assertSame('active', $info->customdata['status']);
        $this->assertSame('course', $info->customdata['visibility']);
    }

    /**
     * Completion is false when the add-item rule is enabled and no user item
     * exists.
     */
    public function test_completion_add_item_rule_requires_user_item(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $data->completionadditem = 1;
        $data->completionvalidateitem = 0;

        $archiveid = uckkarchive_add_instance($data, null);

        $cm = (object)[
            'instance' => $archiveid,
        ];

        $this->assertFalse(uckkarchive_get_completion_state($course, $cm, (int)$user->id, COMPLETION_AND));
    }

    /**
     * Completion is true when the add-item rule is enabled and a user item
     * exists.
     */
    public function test_completion_add_item_rule_passes_when_user_item_exists(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $data->completionadditem = 1;
        $data->completionvalidateitem = 0;

        $archiveid = uckkarchive_add_instance($data, null);

        $this->insert_archive_item($archiveid, (int)$course->id, (int)$user->id, [
            'status' => 'draft',
        ]);

        $cm = (object)[
            'instance' => $archiveid,
        ];

        $this->assertTrue(uckkarchive_get_completion_state($course, $cm, (int)$user->id, COMPLETION_AND));
        $this->assertTrue($DB->record_exists('uckkarchive_item', ['archiveid' => $archiveid]));
    }

    /**
     * Completion validation rule requires a validated user item.
     */
    public function test_completion_validation_rule_requires_validated_user_item(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $data->completionadditem = 0;
        $data->completionvalidateitem = 1;

        $archiveid = uckkarchive_add_instance($data, null);

        $this->insert_archive_item($archiveid, (int)$course->id, (int)$user->id, [
            'status' => 'draft',
        ]);

        $cm = (object)[
            'instance' => $archiveid,
        ];

        $this->assertFalse(uckkarchive_get_completion_state($course, $cm, (int)$user->id, COMPLETION_AND));

        $this->insert_archive_item($archiveid, (int)$course->id, (int)$user->id, [
            'title' => 'Validated archive item',
            'status' => 'validated',
        ]);

        $this->assertTrue(uckkarchive_get_completion_state($course, $cm, (int)$user->id, COMPLETION_AND));
    }

    /**
     * User outline is empty when the user has no archive activity.
     */
    public function test_user_outline_returns_null_without_activity(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $archiveid = uckkarchive_add_instance($data, null);
        $archive = $this->get_archive_record($archiveid);

        $mod = (object)[
            'id' => 1,
            'instance' => $archiveid,
        ];

        $this->assertNull(uckkarchive_user_outline($course, $user, $mod, $archive));
    }

    /**
     * User outline reports item/proof activity.
     */
    public function test_user_outline_reports_user_archive_activity(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_valid_instance_data((int)$course->id);
        $archiveid = uckkarchive_add_instance($data, null);
        $archive = $this->get_archive_record($archiveid);

        $this->insert_archive_item($archiveid, (int)$course->id, (int)$user->id);

        if ($DB->get_manager()->table_exists('uckkarchive_proof')) {
            $this->insert_archive_proof($archiveid, (int)$course->id, (int)$user->id);
        }

        $mod = (object)[
            'id' => 1,
            'instance' => $archiveid,
        ];

        $outline = uckkarchive_user_outline($course, $user, $mod, $archive);

        $this->assertInstanceOf(stdClass::class, $outline);
        $this->assertGreaterThan(0, $outline->time);
        $this->assertStringContainsString('1', $outline->info);
    }

    /**
     * File-area item checks allow normal course visibility.
     */
    public function test_can_view_filearea_item_allows_course_visible_item(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        [$archive, $cm] = $this->create_archive_module($course, [
            'visibility' => 'course',
        ]);

        $context = context_module::instance((int)$cm->id);

        $itemid = $this->insert_archive_item((int)$archive->id, (int)$course->id, (int)$user->id, [
            'cmid' => (int)$cm->id,
            'contextid' => (int)$context->id,
            'visibility' => 'course',
        ]);

        $this->assertTrue(uckkarchive_can_view_filearea_item($cm, $context, 'decision_attachments', $itemid));
    }

    /**
     * Restricted file-area items require restricted archive capability.
     */
    public function test_can_view_filearea_item_requires_capability_for_restricted_item(): void {
        $course = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $viewer = $this->getDataGenerator()->create_user();

        [$archive, $cm] = $this->create_archive_module($course, [
            'visibility' => 'course',
        ]);

        $context = context_module::instance((int)$cm->id);

        $itemid = $this->insert_archive_item((int)$archive->id, (int)$course->id, (int)$owner->id, [
            'cmid' => (int)$cm->id,
            'contextid' => (int)$context->id,
            'visibility' => 'restricted_integrity',
        ]);

        $this->setUser($viewer);
        $this->assertFalse(uckkarchive_can_view_filearea_item($cm, $context, 'decision_attachments', $itemid));

        $this->assign_capability_to_user((int)$viewer->id, 'mod/uckkarchive:viewrestricted', $context);
        $this->assertTrue(uckkarchive_can_view_filearea_item($cm, $context, 'decision_attachments', $itemid));
    }

    /**
     * Build valid activity instance data for add/update callbacks.
     *
     * @param int $courseid Course id.
     * @return stdClass
     */
    private function get_valid_instance_data(int $courseid): stdClass {
        $data = new stdClass();
        $data->course = $courseid;
        $data->name = 'UCKK archive test';
        $data->intro = '<p>Archive intro.</p>';
        $data->introformat = FORMAT_HTML;
        $data->status = 'active';
        $data->visibility = 'course';
        $data->timeopen = 0;
        $data->timeclose = 0;
        $data->completionadditem = 1;
        $data->completionvalidateitem = 0;
        $data->metadata = [
            'fixture' => 'lib_test',
        ];

        return $data;
    }

    /**
     * Create a real course module for uckkarchive if the activity generator is
     * available. Falls back to direct instance creation plus a manually created
     * course module record.
     *
     * @param stdClass $course Course record.
     * @param array<string, mixed> $overrides Instance overrides.
     * @return array{0: stdClass, 1: stdClass}
     */
    private function create_archive_module(stdClass $course, array $overrides = []): array {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_uckkarchive');

        if ($generator !== null && method_exists($generator, 'create_instance')) {
            $archive = $generator->create_instance(array_merge([
                'course' => $course->id,
                'name' => 'UCKK archive module',
            ], $overrides));

            $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);

            return [$archive, $cm];
        }

        $data = $this->get_valid_instance_data((int)$course->id);

        foreach ($overrides as $key => $value) {
            $data->{$key} = $value;
        }

        $archiveid = uckkarchive_add_instance($data, null);
        $module = $DB->get_record('modules', ['name' => 'uckkarchive'], '*', MUST_EXIST);

        $cm = new stdClass();
        $cm->course = $course->id;
        $cm->module = $module->id;
        $cm->instance = $archiveid;
        $cm->section = 0;
        $cm->idnumber = '';
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = 1;
        $cm->visibleold = 1;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 0;
        $cm->completiongradeitemnumber = null;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->availability = null;
        $cm->showdescription = 0;
        $cm->deletioninprogress = 0;
        $cm->downloadcontent = 1;
        $cm->lang = '';
        $cm->id = $DB->insert_record('course_modules', $cm);

        $cm->modname = 'uckkarchive';
        $cm->name = $data->name;

        $archive = $DB->get_record('uckkarchive', ['id' => $archiveid], '*', MUST_EXIST);

        return [$archive, $cm];
    }

    /**
     * Insert a minimal archive item for tests.
     *
     * @param int $archiveid Archive instance id.
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @param array<string, mixed> $overrides Field overrides.
     * @return int Archive item id.
     */
    private function insert_archive_item(int $archiveid, int $courseid, int $userid, array $overrides = []): int {
        global $DB;

        $now = time();

        $record = (object)array_merge([
            'archiveid' => $archiveid,
            'courseid' => $courseid,
            'cmid' => 0,
            'contextid' => 0,
            'userid' => $userid,
            'itemtype' => 'proof',
            'title' => 'Archive item',
            'summary' => 'Archive item summary.',
            'content' => 'Archive item content.',
            'sourcecomponent' => 'mod_uckkarchive',
            'sourceid' => 0,
            'sourceref' => null,
            'status' => 'draft',
            'visibility' => 'course',
            'provenance' => 'human',
            'validationstate' => 'unverified',
            'provenancehash' => null,
            'versionno' => 1,
            'sortorder' => 0,
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'metadata' => json_encode(['fixture' => 'lib_test']),
        ], $overrides);

        return (int)$DB->insert_record('uckkarchive_item', $record);
    }

    /**
     * Insert a minimal archive proof for tests.
     *
     * @param int $archiveid Archive instance id.
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @param array<string, mixed> $overrides Field overrides.
     * @return int Archive proof id.
     */
    private function insert_archive_proof(int $archiveid, int $courseid, int $userid, array $overrides = []): int {
        global $DB;

        $now = time();

        $record = (object)array_merge([
            'archiveid' => $archiveid,
            'itemid' => 0,
            'courseid' => $courseid,
            'cmid' => 0,
            'contextid' => 0,
            'userid' => $userid,
            'prooftype' => 'text',
            'title' => 'Archive proof',
            'summary' => 'Archive proof summary.',
            'content' => 'Archive proof content.',
            'sourcecomponent' => 'mod_uckkarchive',
            'sourceid' => 0,
            'sourceref' => null,
            'status' => 'draft',
            'visibility' => 'course',
            'provenance' => 'human',
            'validationstate' => 'unverified',
            'provenancehash' => null,
            'versionno' => 1,
            'createdby' => $userid,
            'modifiedby' => $userid,
            'timecreated' => $now,
            'timemodified' => $now,
            'metadata' => json_encode(['fixture' => 'lib_test']),
        ], $overrides);

        return (int)$DB->insert_record('uckkarchive_proof', $record);
    }

    /**
     * Load an archive record.
     *
     * @param int $archiveid Archive id.
     * @return stdClass
     */
    private function get_archive_record(int $archiveid): stdClass {
        global $DB;

        return $DB->get_record('uckkarchive', ['id' => $archiveid], '*', MUST_EXIST);
    }

    /**
     * Assign a capability to a user in a specific context.
     *
     * @param int $userid User id.
     * @param string $capability Capability name.
     * @param \context $context Context.
     */
    private function assign_capability_to_user(int $userid, string $capability, \context $context): void {
        $roleid = $this->getDataGenerator()->create_role([
            'shortname' => 'uckkarchivetest' . substr(md5($capability . $userid . $context->id), 0, 8),
            'name' => 'UCKK archive test role',
            'archetype' => '',
        ]);

        assign_capability(
            $capability,
            CAP_ALLOW,
            $roleid,
            $context->id,
            true
        );

        role_assign($roleid, $userid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
    }
}

