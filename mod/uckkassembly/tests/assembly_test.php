<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly;

use context_module;
use context_system;
use mod_uckkassembly\completion\custom_completion;
use mod_uckkassembly\event\assembly_archived;
use mod_uckkassembly\event\assembly_created;
use mod_uckkassembly\local\vote;
use mod_uckkassembly\output\motion_list;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/uckkassembly/locallib.php');

/**
 * Tests for mod_uckkassembly.
 */
final class assembly_test extends \advanced_testcase {
    /**
     * Reset state before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * Assembly capabilities are registered.
     */
    public function test_assembly_capabilities_are_registered(): void {
        $capabilities = [
            'mod/uckkassembly:addinstance',
            'mod/uckkassembly:view',
            'mod/uckkassembly:createassembly',
            'mod/uckkassembly:proposemotion',
            'mod/uckkassembly:amendmotion',
            'mod/uckkassembly:vote',
            'mod/uckkassembly:publishdecision',
            'mod/uckkassembly:contestdecision',
            'mod/uckkassembly:archive',
        ];

        foreach ($capabilities as $capability) {
            $this->assertNotEmpty(
                get_capability_info($capability),
                "Missing capability: {$capability}"
            );
        }
    }

    /**
     * Locallib exposes canonical assembly options.
     */
    public function test_locallib_exposes_canonical_options(): void {
        $types = uckkassembly_get_assembly_type_options();
        $states = uckkassembly_get_state_options();
        $decisions = uckkassembly_get_decision_type_options();
        $votes = uckkassembly_get_vote_options();
        $visibilities = uckkassembly_get_visibility_options();

        $this->assertArrayHasKey('savoirs', $types);
        $this->assertArrayHasKey('defis', $types);
        $this->assertArrayHasKey('joueurs', $types);
        $this->assertArrayHasKey('batisseurs', $types);
        $this->assertArrayHasKey('inquisiteurs', $types);
        $this->assertArrayHasKey('grand_jeu', $types);

        $this->assertArrayHasKey('open', $states);
        $this->assertArrayHasKey('voting', $states);
        $this->assertArrayHasKey('decided', $states);
        $this->assertArrayHasKey('archived', $states);

        $this->assertArrayHasKey('information', $decisions);
        $this->assertArrayHasKey('recommendation', $decisions);
        $this->assertArrayHasKey('validation', $decisions);
        $this->assertArrayHasKey('correction', $decisions);
        $this->assertArrayHasKey('rejection', $decisions);
        $this->assertArrayHasKey('archival', $decisions);
        $this->assertArrayHasKey('integrity', $decisions);

        $this->assertArrayHasKey('for', $votes);
        $this->assertArrayHasKey('against', $votes);
        $this->assertArrayHasKey('abstain', $votes);
        $this->assertArrayHasKey('block', $votes);

        $this->assertArrayHasKey('course', $visibilities);
        $this->assertArrayHasKey('public', $visibilities);
        $this->assertArrayHasKey('restricted_integrity', $visibilities);
    }

    /**
     * Locallib state guards return expected workflow affordances.
     */
    public function test_locallib_state_guards(): void {
        $open = (object)[
            'state' => 'open',
        ];

        $voting = (object)[
            'state' => 'voting',
        ];

        $decided = (object)[
            'state' => 'decided',
            'contestuntil' => time() + DAYSECS,
        ];

        $closed = (object)[
            'state' => 'closed',
        ];

        $this->assertTrue(uckkassembly_accepts_motions($open));
        $this->assertTrue(uckkassembly_accepts_amendments($open));
        $this->assertFalse(uckkassembly_accepts_votes($open));

        $this->assertTrue(uckkassembly_accepts_votes($voting));
        $this->assertTrue(uckkassembly_accepts_decision_publication($voting));

        $this->assertTrue(uckkassembly_is_contestable($decided));
        $this->assertTrue(uckkassembly_is_archive_ready($decided));
        $this->assertTrue(uckkassembly_is_archive_ready($closed));
    }

    /**
     * Vote model normalises values and exports safe data.
     */
    public function test_vote_model_exports_valid_vote(): void {
        $vote = vote::create(
            12,
            34,
            56,
            vote::VALUE_FOR,
            'The motion is justified by the evidence.'
        )->with_context(7, 8, 9)->with_metadata([
            'reading' => 'participant_count',
            'source' => 'assembly_floor',
        ]);

        $record = $vote->to_record(56, 1770000000);
        $export = $vote->to_export();

        $this->assertSame(12, (int)$record->assemblyid);
        $this->assertSame(34, (int)$record->motionid);
        $this->assertSame(56, (int)$record->userid);
        $this->assertSame(vote::VALUE_FOR, $record->votevalue);
        $this->assertSame(vote::STATUS_SUBMITTED, $record->status);
        $this->assertSame(vote::VISIBILITY_COURSE, $record->visibility);
        $this->assertSame(vote::PROVENANCE_HUMAN, $record->provenance);
        $this->assertNotEmpty($record->metadata);

        $this->assertSame(vote::VALUE_FOR, $export->votevalue);
        $this->assertFalse($export->isblock);
        $this->assertFalse($export->isabstention);
        $this->assertTrue($export->iscountable);
        $this->assertSame('participant_count', $export->metadata['reading']);
    }

    /**
     * Vote model rejects missing target object.
     */
    public function test_vote_model_requires_motion_amendment_or_decision(): void {
        $vote = new vote([
            'assemblyid' => 1,
            'userid' => 2,
            'votevalue' => vote::VALUE_ABSTAIN,
        ]);

        $this->expectException(\coding_exception::class);
        $vote->to_record(2);
    }

    /**
     * Motion list exports a template-safe motion summary.
     */
    public function test_motion_list_exports_template_context(): void {
        global $PAGE;

        $motions = [
            [
                'id' => 10,
                'assemblyid' => 5,
                'title' => 'Validate the archive principle',
                'summary' => 'The Assembly considers an archive rule.',
                'motiontype' => 'validation',
                'status' => 'open',
                'visibility' => 'course',
                'proposedby' => 'Ada Joueur',
                'timecreated' => 1770000000,
                'amendmentcount' => 2,
                'objectioncount' => 1,
                'contestcount' => 0,
                'votereading' => [
                    'for' => 6,
                    'against' => 2,
                    'abstain' => 1,
                    'block' => 0,
                ],
                'url' => new moodle_url('/mod/uckkassembly/motion.php', ['motionid' => 10]),
                'amendurl' => new moodle_url('/mod/uckkassembly/amend.php', ['motionid' => 10]),
                'voteurl' => new moodle_url('/mod/uckkassembly/vote.php', ['motionid' => 10]),
                'canamend' => true,
                'canvote' => true,
            ],
        ];

        $list = new motion_list(
            5,
            22,
            33,
            $motions,
            new moodle_url('/mod/uckkassembly/motion.php', ['id' => 22]),
            new moodle_url('/mod/uckkassembly/motions.php', ['id' => 22]),
            true,
            false
        );

        $data = $list->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(5, (int)$data->assemblyid);
        $this->assertSame(22, (int)$data->cmid);
        $this->assertTrue($data->hasmotions);
        $this->assertTrue($data->canpropose);
        $this->assertSame(1, (int)$data->counts->total);
        $this->assertSame(1, (int)$data->counts->open);

        $motion = $data->motions[0];

        $this->assertSame('Validate the archive principle', $motion->title);
        $this->assertSame('validation', $motion->motiontype);
        $this->assertSame('open', $motion->status);
        $this->assertTrue($motion->hasamendments);
        $this->assertTrue($motion->hasobjections);
        $this->assertTrue($motion->hasvotereading);
        $this->assertTrue($motion->canamend);
        $this->assertTrue($motion->canvote);
        $this->assertSame(9, (int)$motion->votereading->total);
        $this->assertSame(67, (int)$motion->votereading->forpercent);
    }

    /**
     * Custom completion defines canonical rules.
     */
    public function test_custom_completion_defines_expected_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();

        $this->assertContains('completionparticipation', $rules);
        $this->assertContains('completionmotion', $rules);
        $this->assertContains('completionamendment', $rules);
        $this->assertContains('completionvote', $rules);
        $this->assertContains('completiondecision', $rules);
        $this->assertContains('completionminutes', $rules);
        $this->assertContains('completionarchive', $rules);
    }

    /**
     * Completion detects motion participation.
     */
    public function test_custom_completion_detects_motion_participation(): void {
        global $DB;

        [$course, $cm, $assembly, $context] = $this->create_assembly_module([
            'completionparticipation' => 1,
            'completionmotion' => 1,
        ]);

        $user = $this->getDataGenerator()->create_user();

        $DB->insert_record('uckkassembly_motion', [
            'assemblyid' => $assembly->id,
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'contextid' => $context->id,
            'title' => 'Motion for completion',
            'summary' => 'Completion evidence.',
            'motiontype' => 'recommendation',
            'status' => 'submitted',
            'visibility' => 'course',
            'userid' => $user->id,
            'createdby' => $user->id,
            'proposedby' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, (int)$user->id);

        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionparticipation'));
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionmotion'));
    }

    /**
     * Completion detects vote participation.
     */
    public function test_custom_completion_detects_vote_participation(): void {
        global $DB;

        [$course, $cm, $assembly, $context] = $this->create_assembly_module([
            'completionvote' => 1,
        ]);

        $user = $this->getDataGenerator()->create_user();

        $motionid = $DB->insert_record('uckkassembly_motion', [
            'assemblyid' => $assembly->id,
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'contextid' => $context->id,
            'title' => 'Motion for vote',
            'summary' => 'Voting evidence.',
            'motiontype' => 'validation',
            'status' => 'open',
            'visibility' => 'course',
            'userid' => $user->id,
            'createdby' => $user->id,
            'proposedby' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $DB->insert_record('uckkassembly_vote', [
            'assemblyid' => $assembly->id,
            'motionid' => $motionid,
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'contextid' => $context->id,
            'userid' => $user->id,
            'votedby' => $user->id,
            'votevalue' => 'for',
            'rationale' => 'The motion is supported.',
            'status' => 'submitted',
            'visibility' => 'course',
            'provenance' => 'human',
            'versionno' => 1,
            'createdby' => $user->id,
            'modifiedby' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, (int)$user->id);

        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionvote'));
    }

    /**
     * Completion detects published decision and minutes.
     */
    public function test_custom_completion_detects_decision_and_minutes(): void {
        global $DB;

        [$course, $cm, $assembly, $context] = $this->create_assembly_module([
            'completiondecision' => 1,
            'completionminutes' => 1,
        ]);

        $user = $this->getDataGenerator()->create_user();

        $DB->insert_record('uckkassembly_decision', [
            'assemblyid' => $assembly->id,
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'contextid' => $context->id,
            'title' => 'Published decision',
            'summary' => 'Decision summary.',
            'decisiontype' => 'validation',
            'status' => 'published',
            'visibility' => 'course',
            'createdby' => $user->id,
            'modifiedby' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $DB->insert_record('uckkassembly_minutes', [
            'assemblyid' => $assembly->id,
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'contextid' => $context->id,
            'title' => 'Published minutes',
            'summary' => 'Minutes summary.',
            'status' => 'published',
            'visibility' => 'course',
            'recordedby' => $user->id,
            'createdby' => $user->id,
            'modifiedby' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, (int)$user->id);

        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completiondecision'));
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionminutes'));
    }

    /**
     * Completion detects archived assembly state.
     */
    public function test_custom_completion_detects_archived_assembly(): void {
        global $DB;

        [$course, $cm, $assembly] = $this->create_assembly_module([
            'completionarchive' => 1,
            'state' => 'archived',
            'status' => 'archived',
        ]);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, (int)$this->getDataGenerator()->create_user()->id);

        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionarchive'));

        $DB->set_field('uckkassembly', 'state', 'open', ['id' => $assembly->id]);
        $DB->set_field('uckkassembly', 'status', 'active', ['id' => $assembly->id]);

        rebuild_course_cache($course->id, true);

        $cminfo = get_fast_modinfo($course, 0, true)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, (int)$this->getDataGenerator()->create_user()->id);

        $this->assertSame(COMPLETION_INCOMPLETE, $completion->get_state('completionarchive'));
    }

    /**
     * Assembly created event validates required data and emits correctly.
     */
    public function test_assembly_created_event_can_be_triggered(): void {
        [$course, $cm, $assembly, $context] = $this->create_assembly_module();

        $sink = $this->redirectEvents();

        $event = assembly_created::create([
            'objectid' => $assembly->id,
            'context' => $context,
            'other' => [
                'courseid' => $course->id,
                'cmid' => $cm->id,
                'assemblytype' => $assembly->assemblytype,
                'visibility' => $assembly->visibility,
                'state' => $assembly->state,
            ],
        ]);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkassembly', $assembly);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(assembly_created::class, $events[0]);
        $this->assertSame((int)$assembly->id, (int)$events[0]->objectid);
        $this->assertSame('uckkassembly', $events[0]->objecttable);
        $this->assertSame('c', $events[0]->crud);
    }

    /**
     * Assembly archived event validates required data and emits correctly.
     */
    public function test_assembly_archived_event_can_be_triggered(): void {
        [$course, $cm, $assembly, $context] = $this->create_assembly_module([
            'state' => 'archived',
            'status' => 'archived',
        ]);

        $sink = $this->redirectEvents();

        $event = assembly_archived::create([
            'objectid' => $assembly->id,
            'context' => $context,
            'other' => [
                'courseid' => $course->id,
                'cmid' => $cm->id,
                'archiveitemid' => 0,
                'visibility' => $assembly->visibility,
                'state' => 'archived',
            ],
        ]);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkassembly', $assembly);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(assembly_archived::class, $events[0]);
        $this->assertSame((int)$assembly->id, (int)$events[0]->objectid);
        $this->assertSame('uckkassembly', $events[0]->objecttable);
        $this->assertSame('u', $events[0]->crud);
    }

    /**
     * Regular users do not receive privileged assembly capabilities by default.
     */
    public function test_privileged_capabilities_are_not_granted_by_default(): void {
        [$course, $cm, , $context] = $this->create_assembly_module();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $this->setUser($user);

        $this->assertFalse(has_capability('mod/uckkassembly:publishdecision', $context, $user));
        $this->assertFalse(has_capability('mod/uckkassembly:archive', $context, $user));
    }

    /**
     * A granted role can publish decisions in module context.
     */
    public function test_publish_decision_capability_can_be_granted(): void {
        [$course, , , $context] = $this->create_assembly_module();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'teacher');

        $this->assign_capability_to_user((int)$user->id, 'mod/uckkassembly:publishdecision', $context);

        $this->setUser($user);

        $this->assertTrue(has_capability('mod/uckkassembly:publishdecision', $context, $user));
    }

    /**
     * Create an assembly activity and return useful records.
     *
     * @param array<string, mixed> $overrides Instance overrides.
     * @return array{0:\stdClass,1:\stdClass,2:\stdClass,3:context_module}
     */
    private function create_assembly_module(array $overrides = []): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
        ]);

        $defaults = [
            'course' => $course->id,
            'name' => 'Assembly of Savoirs',
            'intro' => 'Assembly introduction.',
            'introformat' => FORMAT_HTML,
            'assemblytype' => 'savoirs',
            'state' => 'open',
            'status' => 'active',
            'visibility' => 'course',
            'completionparticipation' => 0,
            'completionmotion' => 0,
            'completionamendment' => 0,
            'completionvote' => 0,
            'completiondecision' => 0,
            'completionminutes' => 0,
            'completionarchive' => 0,
        ];

        $record = (object)array_merge($defaults, $overrides);

        $module = $this->getDataGenerator()->create_module('uckkassembly', $record, [
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $cm = get_coursemodule_from_instance('uckkassembly', $module->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $assembly = $DB->get_record('uckkassembly', ['id' => $module->id], '*', MUST_EXIST);

        return [$course, $cm, $assembly, $context];
    }

    /**
     * Assign a capability to a user in a context.
     *
     * @param int $userid User id.
     * @param string $capability Capability name.
     * @param \context $context Context.
     */
    private function assign_capability_to_user(int $userid, string $capability, \context $context): void {
        $roleid = $this->getDataGenerator()->create_role([
            'shortname' => 'uckkassemblytest' . substr(md5($capability . $userid . $context->id), 0, 8),
            'name' => 'UCKK Assembly test role',
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