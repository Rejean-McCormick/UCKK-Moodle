<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Integrity correction service.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

use tool_uckkintegrity\event\correction_requested;

/**
 * Handles correction requests issued by an Inquisiteur.
 */
class correction {
    /**
     * Request a correction for an integrity case.
     *
     * This records a restricted correction note, stores the correction on the
     * case record, moves the case to correction_required, and emits a Moodle
     * event for auditability.
     *
     * @param \stdClass $case Integrity case record.
     * @param string $body Correction instruction.
     * @param string|null $appealpath Optional appeal or contestation path.
     * @return void
     */
    public static function request(\stdClass $case, string $body, ?string $appealpath = null): void {
        global $DB, $USER;

        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        require_capability('tool/uckkintegrity:issuecorrection', $context);

        $body = trim($body);
        if ($body === '') {
            throw new \coding_exception('Correction body cannot be empty.');
        }

        integrity_policy::require_transition($case, 'correction_required');

        $case->correction = clean_text($body, FORMAT_PLAIN);

        if ($appealpath !== null && trim($appealpath) !== '') {
            $case->appealpath = clean_text($appealpath, FORMAT_PLAIN);
        }

        $case->status = 'correction_required';
        $case->timemodified = time();
        $case->versionno = (int) $case->versionno + 1;
        $case->provenancehash = integrity_case::hash_record($case);

        $metadata = integrity_case::decode_metadata($case->metadata);
        $metadata['last_correction_requested_by'] = (int) $USER->id;
        $metadata['last_correction_requested_at'] = $case->timemodified;

        $case->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $DB->update_record(integrity_case::TABLE, $case);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'correction',
            'body' => $body,
            'visibility' => confidentiality::PARTIES,
            'metadata' => json_encode([
                'appealpath' => $appealpath,
                'previous_status' => $case->status,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        correction_requested::create([
            'context' => $context,
            'objectid' => $case->id,
            'relateduserid' => $USER->id,
            'other' => [
                'status' => 'correction_required',
                'subjectcomponent' => $case->subjectcomponent,
                'subjectid' => (int) $case->subjectid,
            ],
        ])->trigger();
    }
}