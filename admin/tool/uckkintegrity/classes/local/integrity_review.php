<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Integrity review service.
 *
 * Handles Inquisiteur review actions:
 * - reviewer assignment;
 * - status transition;
 * - review notes;
 * - correction requests;
 * - auditable case modification.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 UCKK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class integrity_review {
    /**
     * Record an integrity review action.
     *
     * Expected $data fields:
     * - status: requested next case status;
     * - assignedto: optional assigned Inquisiteur userid;
     * - notetype: observation, evidence, response, decision, correction, appeal;
     * - body: review note body;
     * - visibility: restricted, parties, public_summary.
     *
     * @param \stdClass $case Existing integrity case record.
     * @param \stdClass $data Submitted review form data.
     * @return int Note id if a note was created, otherwise 0.
     */
    public static function review(\stdClass $case, \stdClass $data): int {
        global $DB;

        $context = \context::instance_by_id((int) $case->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:reviewcase', $context);

        $case = integrity_case::get((int) $case->id);
        $noteid = 0;
        $changed = false;

        if (isset($data->assignedto) && (int) $data->assignedto > 0
                && (int) $data->assignedto !== (int) ($case->assignedto ?? 0)) {
            require_capability('tool/uckkintegrity:assigncase', $context);

            $case->assignedto = (int) $data->assignedto;
            $changed = true;

            if (in_array($case->status, ['opened', 'triaged'], true)
                    && empty($data->status)) {
                $data->status = 'assigned';
            }
        }

        if (!empty($data->status) && $data->status !== $case->status) {
            integrity_policy::require_transition($case, $data->status);
            $case->status = $data->status;
            $changed = true;
        }

        if (!empty($data->body)) {
            $noteid = integrity_case::add_note((int) $case->id, (object) [
                'notetype' => self::normalise_note_type($data->notetype ?? 'observation'),
                'body' => clean_text($data->body, FORMAT_PLAIN),
                'visibility' => self::normalise_visibility($data->visibility ?? confidentiality::RESTRICTED),
            ]);

            if (($data->notetype ?? '') === 'correction'
                    || ($data->status ?? '') === 'correction_required') {
                require_capability('tool/uckkintegrity:issuecorrection', $context);
            }
        }

        if ($changed) {
            self::update_case_after_review($case);

            if ($case->status === 'correction_required' && !empty($data->body)) {
                correction::request($case, clean_text($data->body, FORMAT_PLAIN));
            }
        }

        return $noteid;
    }

    /**
     * Assign an integrity case to a reviewer without adding a review note.
     *
     * @param \stdClass $case Case record.
     * @param int $userid Reviewer user id.
     */
    public static function assign(\stdClass $case, int $userid): void {
        global $DB;

        $context = \context::instance_by_id((int) $case->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:assigncase', $context);

        $case = integrity_case::get((int) $case->id);
        $case->assignedto = $userid;

        if (in_array($case->status, ['opened', 'triaged'], true)) {
            integrity_policy::require_transition($case, 'assigned');
            $case->status = 'assigned';
        }

        self::update_case_after_review($case);
    }

    /**
     * Request additional evidence or response from parties.
     *
     * @param \stdClass $case Case record.
     * @param string $body Request text.
     * @return int Created note id.
     */
    public static function request_response(\stdClass $case, string $body): int {
        $context = \context::instance_by_id((int) $case->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:reviewcase', $context);

        $case = integrity_case::get((int) $case->id);
        integrity_policy::require_transition($case, 'waiting_for_response');

        $case->status = 'waiting_for_response';
        self::update_case_after_review($case);

        return integrity_case::add_note((int) $case->id, (object) [
            'notetype' => 'observation',
            'body' => clean_text($body, FORMAT_PLAIN),
            'visibility' => confidentiality::PARTIES,
        ]);
    }

    /**
     * Persist case audit fields after review.
     *
     * @param \stdClass $case Case record.
     */
    private static function update_case_after_review(\stdClass $case): void {
        global $DB;

        $case->timemodified = time();
        $case->versionno = (int) $case->versionno + 1;

        if (in_array($case->status, ['resolved', 'dismissed', 'archived', 'closed'], true)
                && empty($case->timeclosed)) {
            $case->timeclosed = time();
        }

        $case->provenancehash = integrity_case::hash_record($case);

        $DB->update_record(integrity_case::TABLE, $case);
    }

    /**
     * Validate and normalise a note type.
     *
     * @param string $notetype Raw note type.
     * @return string
     */
    private static function normalise_note_type(string $notetype): string {
        return in_array($notetype, integrity_policy::NOTE_TYPES, true)
            ? $notetype
            : 'observation';
    }

    /**
     * Validate and normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        return in_array($visibility, confidentiality::values(), true)
            ? $visibility
            : confidentiality::RESTRICTED;
    }
}