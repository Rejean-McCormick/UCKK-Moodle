<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK challenge activity.
 *
 * @package    mod_uckkchallenge
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge;

use context_module;
use context_system;
use mod_uckkchallenge\event\challenge_created;
use mod_uckkchallenge\event\challenge_viewed;
use mod_uckkchallenge\local\integrity_state;
use mod_uckkchallenge\task\close_expired_challenges;
use required_capability_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/uckkchallenge/lib.php');

/**
 * Tests for the UCKK challenge activity.
 *
 * These tests define the minimum activity contract for Défis King Klown:
 * creation, visibility, workflow state, proof submission capability, integrity
 * state handling, events, and scheduled expiry.
 */
final class challenge_test extends \advanced_testcase {
    /**
     * Prepare a clean database for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * A challenge activity can be created with canonical UCKK fields.
     */
    public function test_create_challenge_activity_sets_canonical_fields(): void {
        global $DB;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $challenge = $this->getDataGenerator()->create_module('uckkchallenge', [
            'course' => $course->id,
            'name' => 'Map a hidden rule',
            'intro' => 'A Défi King Klown about system mapping.',
            'introformat' => FORMAT_HTML,
            'challengecode' => 'UCKK-DEF-001',
            'challengetype' => 'system_mapping',
            'status' => 'draft',
            'statement' => 'Map one rule that shapes behaviour but is rarely named.',
            'contexttext' => 'The learner must identify a concrete system boundary.',
            'rules' => 'Respect dignity, evidence, and contestability.',
            'corridors' => "Observe\nMap\nExplain\nSubmit proof",
            'ethicalconstraints' => 'No humiliation, doxxing, harassment, or fabricated evidence.',
            'evidencepolicy' => 'Submit a text proof, a diagram, and source notes.',
            'criteria' => 'Evidence quality, clarity, usefulness, and integrity.',
            'visibility' => 'course',
            'integrityrequired' => 1,
            'archivepolicy' => 'summary',
            'timeopen' => time() - HOURSECS,
            'timeclose' => time() + WEEKSECS,
            'timereviewby' => time() + (2 * WEEKSECS),
        ]);

        $record = $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST);

        $this->assertSame((int)$course->id, (int)$record->course);
        $this->assertSame('Map a hidden rule', $record->name);
        $this->assertSame('UCKK-DEF-001', $record->challengecode);
        $this->assertSame('system_mapping', $record->challengetype);
        $this->assertSame('draft', $record->status);
        $this->assertSame('course', $record->visibility);
        $this->assertSame('summary', $record->archivepolicy);
        $this->assertSame(1, (int)$record->integrityrequired);
        $this->assertNotEmpty($record->statement);
        $this->assertNotEmpty($record->rules);
        $this->assertNotEmpty($record->evidencepolicy);
        $this->assertNotEmpty($record->criteria);
    }

    /**
     * The challenge_created event is a module-context create event.
     */
    public function test_challenge_created_event_is_valid(): void {
        $this->setAdminUser();

        [$course, $cm, $challenge] = $this->create_challenge_fixture([
            'status' => 'draft',
            'visibility' => 'course',
            'challengetype' => 'internal_learning',
        ]);

        $context = context_module::instance((int)$cm->id);
        $sink = $this->redirectEvents();

        $event = challenge_created::create([
            'objectid' => $challenge->id,
            'context' => $context,
            'other' => [
                'courseid' => $course->id,
                'cmid' => $cm->id,
                'status' => $challenge->status,
                'visibility' => $challenge->visibility,
                'challengetype' => $challenge->challengetype,
            ],
        ]);

        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkchallenge', $challenge);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(challenge_created::class, $events[0]);
        $this->assertSame('c', $events[0]->crud);
        $this->assertSame(\core\event\base::LEVEL_TEACHING, $events[0]->edulevel);
        $this->assertSame('uckkchallenge', $events[0]->objecttable);
        $this->assertSame((int)$challenge->id, (int)$events[0]->objectid);
        $this->assertSame(CONTEXT_MODULE, $events[0]->contextlevel);
        $this->assertSame((int)$cm->id, (int)$events[0]->contextinstanceid);
        $this->assertStringContainsString('/mod/uckkchallenge/view.php', $events[0]->get_url()->out(false));
    }

    /**
     * The challenge_viewed event is a module-context read event.
     */
    public function test_challenge_viewed_event_is_valid(): void {
        $this->setAdminUser();

        [$course, $cm, $challenge] = $this->create_challenge_fixture([
            'status' => 'open',
            'visibility' => 'course',
        ]);

        $context = context_module::instance((int)$cm->id);
        $sink = $this->redirectEvents();

        $event = challenge_viewed::create([
            'objectid' => $challenge->id,
            'context' => $context,
            'other' => [
                'courseid' => $course->id,
                'cmid' => $cm->id,
                'status' => $challenge->status,
                'visibility' => $challenge->visibility,
            ],
        ]);

        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkchallenge', $challenge);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(challenge_viewed::class, $events[0]);
        $this->assertSame('r', $events[0]->crud);
        $this->assertSame(\core\event\base::LEVEL_PARTICIPATING, $events[0]->edulevel);
        $this->assertSame('uckkchallenge', $events[0]->objecttable);
        $this->assertSame((int)$challenge->id, (int)$events[0]->objectid);
        $this->assertSame(CONTEXT_MODULE, $events[0]->contextlevel);
        $this->assertSame((int)$cm->id, (int)$events[0]->contextinstanceid);
    }

    /**
     * Integrity states enforce human-review boundaries.
     */
    public function test_integrity_state_contract_blocks_recognition_until_verified(): void {
        $this->assertTrue(integrity_state::is_valid(integrity_state::UNVERIFIED));
        $this->assertTrue(integrity_state::is_valid(integrity_state::HUMAN_REVIEWED));
        $this->assertTrue(integrity_state::is_valid(integrity_state::VERIFIED));
        $this->assertTrue(integrity_state::is_valid(integrity_state::CONTESTED));
        $this->assertTrue(integrity_state::is_valid(integrity_state::INVALIDATED));
        $this->assertTrue(integrity_state::is_valid(integrity_state::ARCHIVED));

        $this->assertTrue(integrity_state::blocks_recognition(integrity_state::UNVERIFIED));
        $this->assertTrue(integrity_state::blocks_recognition(integrity_state::HUMAN_REVIEWED));
        $this->assertFalse(integrity_state::blocks_recognition(integrity_state::VERIFIED));
        $this->assertTrue(integrity_state::blocks_recognition(integrity_state::CONTESTED));
        $this->assertTrue(integrity_state::blocks_recognition(integrity_state::INVALIDATED));

        $this->assertTrue(integrity_state::blocks_archive_export(integrity_state::UNVERIFIED));
        $this->assertTrue(integrity_state::blocks_archive_export(integrity_state::CONTESTED));
        $this->assertFalse(integrity_state::blocks_archive_export(integrity_state::VERIFIED));

        $this->assertSame(
            'validated',
            integrity_state::suggested_challenge_status(integrity_state::VERIFIED)
        );

        $this->assertSame(
            'contested',
            integrity_state::suggested_challenge_status(integrity_state::CONTESTED)
        );

        $this->assertSame(
            'invalidated',
            integrity_state::suggested_challenge_status(integrity_state::INVALIDATED)
        );
    }

    /**
     * Integrity transitions allow contestability without allowing arbitrary jumps.
     */
    public function test_integrity_state_transition_contract(): void {
        $this->assertTrue(integrity_state::can_transition(
            integrity_state::UNVERIFIED,
            integrity_state::HUMAN_REVIEWED
        ));

        $this->assertTrue(integrity_state::can_transition(
            integrity_state::HUMAN_REVIEWED,
            integrity_state::VERIFIED
        ));

        $this->assertTrue(integrity_state::can_transition(
            integrity_state::VERIFIED,
            integrity_state::CONTESTED
        ));

        $this->assertTrue(integrity_state::can_transition(
            integrity_state::CONTESTED,
            integrity_state::INVALIDATED
        ));

        $this->assertTrue(integrity_state::can_transition(
            integrity_state::INVALIDATED,
            integrity_state::ARCHIVED
        ));

        $this->assertFalse(integrity_state::can_transition(
            integrity_state::ARCHIVED,
            integrity_state::VERIFIED
        ));
    }

    /**
     * Learners need the canonical submitproof capability to submit evidence.
     */
    public function test_submitproof_capability_controls_submission_access(): void {
        $user = $this->getDataGenerator()->create_user();

        [, $cm] = $this->create_challenge_fixture([
            'status' => 'open',
            'visibility' => 'course',
        ]);

        $context = context_module::instance((int)$cm->id);

        $this->setUser($user);

        $this->assertFalse(has_capability('mod/uckkchallenge:submitproof', $context, $user));

        $this->assign_capability_to_user((int)$user->id, 'mod/uckkchallenge:submitproof', $context);

        $this->assertTrue(has_capability('mod/uckkchallenge:submitproof', $context, $user));
    }

    /**
     * Integrity validation requires the canonical validateintegrity capability.
     */
    public function test_validateintegrity_capability_controls_integrity_actions(): void {
        $user = $this->getDataGenerator()->create_user();

        [, $cm] = $this->create_challenge_fixture([
            'status' => 'integrity_review',
            'visibility' => 'restricted_integrity',
        ]);

        $context = context_module::instance((int)$cm->id);

        $this->setUser($user);

        $this->assertFalse(has_capability('mod/uckkchallenge:validateintegrity', $context, $user));

        $this->assign_capability_to_user((int)$user->id, 'mod/uckkchallenge:validateintegrity', $context);

        $this->assertTrue(has_capability('mod/uckkchallenge:validateintegrity', $context, $user));
    }

    /**
     * The expiry task closes open challenges whose close time has passed.
     */
    public function test_close_expired_challenges_task_expires_open_empty_challenge(): void {
        global $DB;

        $this->setAdminUser();

        [, , $challenge] = $this->create_challenge_fixture([
            'status' => 'open',
            'visibility' => 'course',
            'timeopen' => time() - WEEKSECS,
            'timeclose' => time() - HOURSECS,
        ]);

        $task = new close_expired_challenges();
        $task->execute();

        $updated = $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST);

        $this->assertSame('expired', $updated->status);
    }

    /**
     * The expiry task must not expire challenges that already have submissions.
     */
    public function test_close_expired_challenges_task_skips_challenge_with_submission(): void {
        global $DB;

        $this->setAdminUser();

        [, , $challenge] = $this->create_challenge_fixture([
            'status' => 'open',
            'visibility' => 'course',
            'timeopen' => time() - WEEKSECS,
            'timeclose' => time() - HOURSECS,
        ]);

        if (!$DB->get_manager()->table_exists('uckkchallenge_sub')) {
            $this->markTestSkipped('The uckkchallenge_sub table is not installed yet.');
        }

        $DB->insert_record('uckkchallenge_sub', (object)[
            'challengeid' => $challenge->id,
            'courseid' => $challenge->course,
            'cmid' => $challenge->cmid ?? 0,
            'contextid' => $challenge->contextid ?? 0,
            'userid' => get_admin()->id,
            'createdby' => get_admin()->id,
            'modifiedby' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'visibility' => 'course',
            'versionno' => 1,
            'prooftype' => 'text',
            'integritystate' => integrity_state::UNVERIFIED,
            'metadata' => json_encode(['test' => true]),
        ]);

        $task = new close_expired_challenges();
        $task->execute();

        $updated = $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST);

        $this->assertSame('open', $updated->status);
    }

    /**
     * The expiry task must not alter review, integrity, validation, or archive states.
     *
     * @dataProvider non_expirable_status_provider
     * @param string $status Challenge status.
     */
    public function test_close_expired_challenges_task_skips_non_expirable_statuses(string $status): void {
        global $DB;

        $this->setAdminUser();

        [, , $challenge] = $this->create_challenge_fixture([
            'status' => $status,
            'visibility' => 'course',
            'timeopen' => time() - WEEKSECS,
            'timeclose' => time() - HOURSECS,
        ]);

        $task = new close_expired_challenges();
        $task->execute();

        $updated = $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST);

        $this->assertSame($status, $updated->status);
    }

    /**
     * Statuses that must not be expired by the scheduled task.
     *
     * @return array<string, array{0: string}>
     */
    public static function non_expirable_status_provider(): array {
        return [
            'draft' => ['draft'],
            'submitted' => ['submitted'],
            'under_review' => ['under_review'],
            'integrity_review' => ['integrity_review'],
            'revision_required' => ['revision_required'],
            'resubmitted' => ['resubmitted'],
            'validated' => ['validated'],
            'archived' => ['archived'],
            'closed' => ['closed'],
            'contested' => ['contested'],
            'invalidated' => ['invalidated'],
            'withdrawn' => ['withdrawn'],
        ];
    }

    /**
     * Create a UCKK challenge fixture.
     *
     * @param array<string, mixed> $overrides Field overrides.
     * @return array{0: stdClass, 1: stdClass, 2: stdClass} Course, cm, challenge.
     */
    private function create_challenge_fixture(array $overrides = []): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        $defaults = [
            'course' => $course->id,
            'name' => 'UCKK test challenge',
            'intro' => 'Test challenge intro.',
            'introformat' => FORMAT_HTML,
            'challengecode' => 'UCKK-TEST-' . random_string(6),
            'challengetype' => 'internal_learning',
            'status' => 'open',
            'statement' => 'Produce a proof that can be reviewed.',
            'contexttext' => 'Test context.',
            'rules' => 'Use evidence, disclose uncertainty, preserve dignity.',
            'corridors' => "Observe\nMap\nSubmit",
            'ethicalconstraints' => 'No harassment, humiliation, fabricated evidence, or doxxing.',
            'evidencepolicy' => 'Submit text, URL, or file proof with provenance.',
            'criteria' => 'Clear claim, evidence, relation to criteria, provenance.',
            'visibility' => 'course',
            'integrityrequired' => 1,
            'archivepolicy' => 'summary',
            'publicsummary' => 'Public-safe test summary.',
            'timeopen' => time() - HOURSECS,
            'timeclose' => time() + WEEKSECS,
            'timereviewby' => time() + (2 * WEEKSECS),
        ];

        $challenge = $this->getDataGenerator()->create_module(
            'uckkchallenge',
            array_merge($defaults, $overrides)
        );

        $cm = get_coursemodule_from_instance(
            'uckkchallenge',
            (int)$challenge->id,
            (int)$course->id,
            false,
            MUST_EXIST
        );

        $record = $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST);

        if (property_exists($record, 'cmid') && empty($record->cmid)) {
            $record->cmid = $cm->id;
            $DB->update_record('uckkchallenge', $record);
        }

        if (property_exists($record, 'contextid') && empty($record->contextid)) {
            $record->contextid = context_module::instance((int)$cm->id)->id;
            $DB->update_record('uckkchallenge', $record);
        }

        return [$course, $cm, $DB->get_record('uckkchallenge', ['id' => $challenge->id], '*', MUST_EXIST)];
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
            'shortname' => 'uckkchallengetest' . substr(md5($capability . $userid . $context->id), 0, 8),
            'name' => 'UCKK challenge test role',
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