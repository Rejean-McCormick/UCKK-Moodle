<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity;

defined('MOODLE_INTERNAL') || die();

use tool_uckkintegrity\local\appeal;
use tool_uckkintegrity\local\confidentiality;
use tool_uckkintegrity\local\decision;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_policy;
use tool_uckkintegrity\local\integrity_review;
use tool_uckkintegrity\local\severity;

/**
 * Tests for UCKK integrity case records and transitions.
 *
 * @group tool_uckkintegrity
 * @coversDefaultClass \tool_uckkintegrity\local\integrity_case
 */
final class integrity_case_test extends \advanced_testcase {
    /**
     * Create a valid base case.
     *
     * @param array $overrides Record overrides.
     * @return \stdClass Case record.
     */
    private function create_case(array $overrides = []): \stdClass {
        $context = \context_system::instance();

        $data = (object) array_merge([
            'contextid' => $context->id,
            'casetype' => 'proof_quality',
            'subjectcomponent' => 'mod_uckkchallenge',
            'subjectid' => 42,
            'severity' => severity::NORMAL,
            'summary' => 'Evidence needs an integrity review.',
            'visibility' => confidentiality::RESTRICTED,
        ], $overrides);

        $caseid = integrity_case::create($data);

        return integrity_case::get($caseid);
    }

    /**
     * A privileged user can open an integrity case.
     *
     * @covers ::create
     * @covers ::get
     */
    public function test_create_case_records_required_fields(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $case = $this->create_case();

        $this->assertNotEmpty($case->id);
        $this->assertSame('proof_quality', $case->casetype);
        $this->assertSame('mod_uckkchallenge', $case->subjectcomponent);
        $this->assertSame(42, (int) $case->subjectid);
        $this->assertSame(severity::NORMAL, $case->severity);
        $this->assertSame('opened', $case->status);
        $this->assertSame(confidentiality::RESTRICTED, $case->visibility);
        $this->assertSame('Evidence needs an integrity review.', $case->summary);
        $this->assertNotEmpty($case->provenancehash);
        $this->assertGreaterThan(0, (int) $case->timecreated);
        $this->assertGreaterThan(0, (int) $case->timemodified);
    }

    /**
     * Invalid case types are rejected.
     *
     * @covers ::create
     */
    public function test_create_case_rejects_unknown_case_type(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\moodle_exception::class);

        $this->create_case([
            'casetype' => 'not_a_real_case_type',
        ]);
    }

    /**
     * Review records a note and updates the workflow state.
     *
     * @covers ::add_note
     * @covers ::notes
     */
    public function test_review_case_adds_note_and_changes_status(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $case = $this->create_case();

        integrity_review::review($case, (object) [
            'status' => 'under_review',
            'assignedto' => 0,
            'notetype' => 'observation',
            'body' => 'The evidence package has been reviewed by the Inquisiteur.',
            'visibility' => confidentiality::RESTRICTED,
        ]);

        $case = integrity_case::get((int) $case->id);
        $notes = integrity_case::notes((int) $case->id);

        $this->assertSame('under_review', $case->status);
        $this->assertCount(1, $notes);
        $this->assertSame('observation', $notes[0]->notetype);
        $this->assertSame(
            'The evidence package has been reviewed by the Inquisiteur.',
            $notes[0]->body
        );
        $this->assertSame(confidentiality::RESTRICTED, $notes[0]->visibility);
    }

    /**
     * Invalid transitions are blocked by policy.
     */
    public function test_policy_rejects_invalid_transition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $case = $this->create_case();

        $this->expectException(\moodle_exception::class);

        integrity_case::transition($case, 'closed', 'A newly opened case cannot be closed directly.');
    }

    /**
     * A full case can be reviewed, decided and resolved.
     */
    public function test_case_can_be_reviewed_and_resolved_with_decision(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $case = $this->create_case();

        integrity_review::review($case, (object) [
            'status' => 'under_review',
            'assignedto' => 0,
            'notetype' => 'evidence',
            'body' => 'Evidence was checked against the stated criteria.',
            'visibility' => confidentiality::RESTRICTED,
        ]);

        $case = integrity_case::get((int) $case->id);

        decision::record($case, (object) [
            'status' => 'resolved',
            'decision' => 'The evidence is acceptable after review.',
            'correction' => '',
            'appealpath' => 'Appeal within the configured appeal window.',
            'archivesummary' => 'Integrity case resolved and retained as an audit record.',
            'archiveitemid' => 0,
            'invalidateitem' => 0,
        ]);

        $case = integrity_case::get((int) $case->id);

        $this->assertSame('resolved', $case->status);
        $this->assertSame('The evidence is acceptable after review.', $case->decision);
        $this->assertSame('Appeal within the configured appeal window.', $case->appealpath);
        $this->assertNotEmpty($case->timeclosed);

        $this->assertTrue($DB->record_exists('tool_uckkintegrity_note', [
            'caseid' => $case->id,
            'notetype' => 'decision',
        ]));
    }

    /**
     * A correction-required decision records correction text.
     */
    public function test_decision_can_request_correction(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $case = $this->create_case();

        integrity_review::review($case, (object) [
            'status' => 'under_review',
            'assignedto' => 0,
            'notetype' => 'observation',
            'body' => 'Review started.',
            'visibility' => confidentiality::RESTRICTED,
        ]);

        $case = integrity_case::get((int) $case->id);

        decision::record($case, (object) [
            'status' => 'correction_required',
            'decision' => 'The evidence is incomplete.',
            'correction' => 'Provide source dates and provenance links.',
            'appealpath' => 'Respond in the case thread.',
            'archivesummary' => '',
            'archiveitemid' => 0,
            'invalidateitem' => 0,
        ]);

        $case = integrity_case::get((int) $case->id);

        $this->assertSame('correction_required', $case->status);
        $this->assertSame('The evidence is incomplete.', $case->decision);
        $this->assertSame('Provide source dates and provenance links.', $case->correction);
    }

    /**
     * Appeals create durable appeal rows and case notes.
     */
    public function test_appeal_can_be_submitted_for_resolved_case(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('appealwindow', DAYSECS * 14, 'tool_uckkintegrity');

        $case = $this->create_case();

        integrity_review::review($case, (object) [
            'status' => 'under_review',
            'assignedto' => 0,
            'notetype' => 'observation',
            'body' => 'Review started.',
            'visibility' => confidentiality::RESTRICTED,
        ]);

        $case = integrity_case::get((int) $case->id);

        decision::record($case, (object) [
            'status' => 'resolved',
            'decision' => 'Decision recorded.',
            'correction' => '',
            'appealpath' => 'Appeal within 14 days.',
            'archivesummary' => '',
            'archiveitemid' => 0,
            'invalidateitem' => 0,
        ]);

        $case = integrity_case::get((int) $case->id);

        $appealid = appeal::create($case, (object) [
            'body' => 'I contest the decision because relevant evidence was omitted.',
        ]);

        $this->assertTrue($DB->record_exists('tool_uckkintegrity_appeal', [
            'id' => $appealid,
            'caseid' => $case->id,
            'status' => appeal::STATUS_SUBMITTED,
        ]));

        $this->assertTrue($DB->record_exists('tool_uckkintegrity_note', [
            'caseid' => $case->id,
            'notetype' => 'appeal',
        ]));
    }

    /**
     * Appeal deadlines are derived from the configured appeal window.
     */
    public function test_appeal_deadline_uses_configured_window(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('appealwindow', DAYSECS * 7, 'tool_uckkintegrity');

        $case = $this->create_case();

        integrity_review::review($case, (object) [
            'status' => 'under_review',
            'assignedto' => 0,
            'notetype' => 'observation',
            'body' => 'Review started.',
            'visibility' => confidentiality::RESTRICTED,
        ]);

        $case = integrity_case::get((int) $case->id);

        decision::record($case, (object) [
            'status' => 'resolved',
            'decision' => 'Decision recorded.',
            'correction' => '',
            'appealpath' => 'Appeal within 7 days.',
            'archivesummary' => '',
            'archiveitemid' => 0,
            'invalidateitem' => 0,
        ]);

        $case = integrity_case::get((int) $case->id);
        $deadline = appeal::appeal_deadline($case);

        $this->assertNotNull($deadline);
        $this->assertSame((int) $case->timeclosed + (DAYSECS * 7), $deadline);
        $this->assertTrue(appeal::is_within_appeal_window($case));
    }

    /**
     * Summary counts include created case status and severity.
     *
     * @covers ::summary_counts
     */
    public function test_summary_counts_group_cases(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->create_case([
            'casetype' => 'proof_quality',
            'severity' => severity::LOW,
        ]);

        $this->create_case([
            'casetype' => 'ai_misuse',
            'severity' => severity::CRITICAL,
            'summary' => 'AI use requires integrity review.',
        ]);

        $statuscounts = integrity_case::summary_counts('status');
        $severitycounts = integrity_case::summary_counts('severity');

        $statusmap = [];
        foreach ($statuscounts as $row) {
            $statusmap[$row->status] = (int) $row->total;
        }

        $severitymap = [];
        foreach ($severitycounts as $row) {
            $severitymap[$row->severity] = (int) $row->total;
        }

        $this->assertSame(2, $statusmap['opened']);
        $this->assertSame(1, $severitymap[severity::LOW]);
        $this->assertSame(1, $severitymap[severity::CRITICAL]);
    }

    /**
     * Case filtering returns matching cases only.
     *
     * @covers ::get_cases
     * @covers ::count_cases
     */
    public function test_get_cases_filters_by_case_type_and_severity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->create_case([
            'casetype' => 'proof_quality',
            'severity' => severity::LOW,
        ]);

        $this->create_case([
            'casetype' => 'ai_misuse',
            'severity' => severity::CRITICAL,
            'summary' => 'AI use requires integrity review.',
        ]);

        $cases = integrity_case::get_cases([
            'casetype' => 'ai_misuse',
            'severity' => severity::CRITICAL,
        ]);

        $this->assertCount(1, $cases);
        $this->assertSame('ai_misuse', $cases[0]->casetype);
        $this->assertSame(severity::CRITICAL, $cases[0]->severity);

        $this->assertSame(1, integrity_case::count_cases([
            'casetype' => 'ai_misuse',
            'severity' => severity::CRITICAL,
        ]));
    }

    /**
     * Policy menus expose canonical case types and states.
     */
    public function test_policy_exposes_canonical_case_types_and_statuses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $casetypes = integrity_policy::case_type_menu();
        $statuses = integrity_policy::status_menu();

        $this->assertArrayHasKey('proof_quality', $casetypes);
        $this->assertArrayHasKey('ai_misuse', $casetypes);
        $this->assertArrayHasKey('archive_correction', $casetypes);

        $this->assertArrayHasKey('opened', $statuses);
        $this->assertArrayHasKey('under_review', $statuses);
        $this->assertArrayHasKey('correction_required', $statuses);
        $this->assertArrayHasKey('resolved', $statuses);
        $this->assertArrayHasKey('archived', $statuses);
    }
}