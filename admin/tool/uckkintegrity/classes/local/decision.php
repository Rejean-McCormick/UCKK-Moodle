<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

use tool_uckkintegrity\event\case_closed;
use tool_uckkintegrity\event\correction_requested;
use tool_uckkintegrity\event\item_invalidated;

/**
 * Integrity case decision service.
 */
class decision {
    /**
     * Record an integrity decision.
     *
     * @param \stdClass $case Existing integrity case record.
     * @param \stdClass $data Submitted decision data.
     */
    public static function record(\stdClass $case, \stdClass $data): void {
        global $DB;

        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        require_capability('tool/uckkintegrity:closecase', $context);

        $status = integrity_policy::validate_status($data->status ?? 'resolved');
        integrity_policy::require_transition($case, $status);

        $decisiontext = clean_text($data->decision ?? '', FORMAT_PLAIN);
        $correctiontext = clean_text($data->correction ?? '', FORMAT_PLAIN);
        $appealpath = clean_text($data->appealpath ?? '', FORMAT_PLAIN);
        $archivesummary = clean_text($data->archivesummary ?? '', FORMAT_PLAIN);
        $invalidateitem = !empty($data->invalidateitem);

        if ($correctiontext !== '' || $status === 'correction_required') {
            require_capability('tool/uckkintegrity:issuecorrection', $context);
        }

        if ($invalidateitem) {
            require_capability('tool/uckkintegrity:invalidate', $context);
        }

        $case->decision = $decisiontext;
        $case->correction = $correctiontext !== '' ? $correctiontext : null;
        $case->appealpath = $appealpath !== '' ? $appealpath : null;
        $case->archivesummary = $archivesummary !== '' ? $archivesummary : null;
        $case->archiveitemid = !empty($data->archiveitemid) ? (int) $data->archiveitemid : null;
        $case->status = $status;
        $case->timemodified = time();
        $case->versionno = (int) $case->versionno + 1;

        if (self::is_closing_status($status)) {
            $case->timeclosed = time();
        }

        $case->provenancehash = integrity_case::hash_record($case);

        $DB->update_record(integrity_case::TABLE, $case);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'decision',
            'body' => $decisiontext,
            'visibility' => confidentiality::PARTIES,
        ]);

        if ($correctiontext !== '' || $status === 'correction_required') {
            correction_requested::create([
                'context' => $context,
                'objectid' => $case->id,
                'other' => [
                    'status' => $status,
                ],
            ])->trigger();
        }

        if ($invalidateitem) {
            item_invalidated::create([
                'context' => $context,
                'objectid' => $case->id,
                'other' => [
                    'subjectcomponent' => $case->subjectcomponent,
                    'subjectid' => $case->subjectid,
                ],
            ])->trigger();
        }

        if (self::is_closing_status($status)) {
            case_closed::create([
                'context' => $context,
                'objectid' => $case->id,
                'other' => [
                    'status' => $status,
                ],
            ])->trigger();
        }
    }

    /**
     * Whether a status represents a final/closed integrity outcome.
     *
     * @param string $status
     * @return bool
     */
    private static function is_closing_status(string $status): bool {
        return in_array($status, [
            'resolved',
            'dismissed',
            'archived',
            'closed',
        ], true);
    }
}