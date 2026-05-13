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
 * Appeal handling for UCKK integrity cases.
 *
 * Appeals are contestation records attached to integrity cases. Submitting an
 * appeal does not silently change the original decision. It creates a durable
 * appeal record and a visible case note. Reviewers can later decide the appeal.
 */
class appeal {
    /** @var string Database table for appeal records. */
    public const TABLE = 'tool_uckkintegrity_appeal';

    /** @var string Appeal has been submitted and awaits review. */
    public const STATUS_SUBMITTED = 'submitted';

    /** @var string Appeal is under review. */
    public const STATUS_UNDER_REVIEW = 'under_review';

    /** @var string Appeal was upheld. */
    public const STATUS_UPHELD = 'upheld';

    /** @var string Appeal was rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** @var string Appeal was withdrawn. */
    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Return available appeal statuses.
     *
     * @return string[]
     */
    public static function statuses(): array {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_UPHELD,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ];
    }

    /**
     * Create an appeal for an integrity case.
     *
     * The user must be allowed to view the case. The appeal is recorded as both
     * a dedicated appeal row and a case note with parties-level visibility.
     *
     * @param \stdClass $case Integrity case record.
     * @param \stdClass $data Submitted form data.
     * @return int Appeal ID.
     */
    public static function create(\stdClass $case, \stdClass $data): int {
        global $DB, $USER;

        integrity_policy::require_can_view_case($case);

        if (!self::is_within_appeal_window($case)) {
            throw new \moodle_exception('appealwindowclosed', 'tool_uckkintegrity');
        }

        $now = time();

        $record = (object) [
            'caseid' => $case->id,
            'userid' => $USER->id,
            'body' => clean_text($data->body ?? '', FORMAT_PLAIN),
            'status' => self::STATUS_SUBMITTED,
            'decision' => null,
            'decidedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        if (trim($record->body) === '') {
            throw new \moodle_exception('emptyappeal', 'tool_uckkintegrity');
        }

        $appealid = $DB->insert_record(self::TABLE, $record);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'appeal',
            'body' => $record->body,
            'visibility' => confidentiality::PARTIES,
            'metadata' => json_encode([
                'appealid' => $appealid,
                'appealstatus' => self::STATUS_SUBMITTED,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        self::touch_case($case);

        return $appealid;
    }

    /**
     * Mark an appeal as under review.
     *
     * @param int $appealid Appeal ID.
     */
    public static function start_review(int $appealid): void {
        global $DB;

        $appeal = self::get($appealid);
        $case = integrity_case::get((int) $appeal->caseid);
        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        require_capability('tool/uckkintegrity:reviewcase', $context);

        $appeal->status = self::STATUS_UNDER_REVIEW;
        $appeal->timemodified = time();
        $DB->update_record(self::TABLE, $appeal);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'appeal',
            'body' => get_string('appealunderreview', 'tool_uckkintegrity'),
            'visibility' => confidentiality::RESTRICTED,
            'metadata' => json_encode([
                'appealid' => $appealid,
                'appealstatus' => self::STATUS_UNDER_REVIEW,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        self::touch_case($case);
    }

    /**
     * Decide an appeal.
     *
     * If the appeal is upheld and the case can be reopened, the case is reopened
     * with a traceable transition. Otherwise, the appeal decision is still stored
     * and added to case notes.
     *
     * @param int $appealid Appeal ID.
     * @param string $status Final appeal status.
     * @param string $decisiontext Decision rationale.
     */
    public static function decide(int $appealid, string $status, string $decisiontext): void {
        global $DB, $USER;

        if (!in_array($status, [self::STATUS_UPHELD, self::STATUS_REJECTED, self::STATUS_WITHDRAWN], true)) {
            throw new \moodle_exception('invalidappealstatus', 'tool_uckkintegrity');
        }

        $appeal = self::get($appealid);
        $case = integrity_case::get((int) $appeal->caseid);
        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        require_capability('tool/uckkintegrity:reviewcase', $context);

        $decisiontext = clean_text($decisiontext, FORMAT_PLAIN);
        if (trim($decisiontext) === '') {
            throw new \moodle_exception('emptyappealdecision', 'tool_uckkintegrity');
        }

        $appeal->status = $status;
        $appeal->decision = $decisiontext;
        $appeal->decidedby = $USER->id;
        $appeal->timemodified = time();
        $DB->update_record(self::TABLE, $appeal);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'decision',
            'body' => $decisiontext,
            'visibility' => confidentiality::PARTIES,
            'metadata' => json_encode([
                'appealid' => $appealid,
                'appealstatus' => $status,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($status === self::STATUS_UPHELD && integrity_policy::can_transition($case->status, 'reopened')) {
            integrity_case::transition($case, 'reopened', $decisiontext);
        } else {
            self::touch_case($case);
        }
    }

    /**
     * Withdraw an appeal submitted by the current user.
     *
     * Reviewers may withdraw any appeal; ordinary users may withdraw only their own.
     *
     * @param int $appealid Appeal ID.
     */
    public static function withdraw(int $appealid): void {
        global $DB, $USER;

        $appeal = self::get($appealid);
        $case = integrity_case::get((int) $appeal->caseid);
        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        if ((int) $appeal->userid !== (int) $USER->id
                && !has_capability('tool/uckkintegrity:reviewcase', $context)) {
            throw new \required_capability_exception($context, 'tool/uckkintegrity:reviewcase', 'nopermissions', '');
        }

        $appeal->status = self::STATUS_WITHDRAWN;
        $appeal->timemodified = time();
        $DB->update_record(self::TABLE, $appeal);

        integrity_case::add_note($case->id, (object) [
            'notetype' => 'appeal',
            'body' => get_string('appealwithdrawn', 'tool_uckkintegrity'),
            'visibility' => confidentiality::PARTIES,
            'metadata' => json_encode([
                'appealid' => $appealid,
                'appealstatus' => self::STATUS_WITHDRAWN,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        self::touch_case($case);
    }

    /**
     * Get one appeal.
     *
     * @param int $appealid Appeal ID.
     * @return \stdClass
     */
    public static function get(int $appealid): \stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $appealid], '*', MUST_EXIST);
    }

    /**
     * Get all appeals for a case.
     *
     * @param int $caseid Case ID.
     * @return \stdClass[]
     */
    public static function get_for_case(int $caseid): array {
        global $DB;

        return array_values($DB->get_records(self::TABLE, ['caseid' => $caseid], 'timecreated ASC, id ASC'));
    }

    /**
     * Check whether the case can still receive appeals.
     *
     * Open and in-review cases can receive responses. Formal appeals are normally
     * tied to cases with decisions or closure timestamps.
     *
     * @param \stdClass $case Integrity case record.
     * @return bool
     */
    public static function is_within_appeal_window(\stdClass $case): bool {
        $window = (int) get_config('tool_uckkintegrity', 'appealwindow');

        if ($window <= 0) {
            return true;
        }

        $base = 0;
        if (!empty($case->timeclosed)) {
            $base = (int) $case->timeclosed;
        } else if (!empty($case->timemodified)
                && in_array($case->status, ['resolved', 'dismissed', 'archived', 'closed'], true)) {
            $base = (int) $case->timemodified;
        }

        if ($base === 0) {
            return true;
        }

        return time() <= ($base + $window);
    }

    /**
     * Return the appeal deadline for display, or null when no deadline applies.
     *
     * @param \stdClass $case Integrity case record.
     * @return int|null Unix timestamp.
     */
    public static function appeal_deadline(\stdClass $case): ?int {
        $window = (int) get_config('tool_uckkintegrity', 'appealwindow');

        if ($window <= 0) {
            return null;
        }

        if (!empty($case->timeclosed)) {
            return (int) $case->timeclosed + $window;
        }

        if (!empty($case->timemodified)
                && in_array($case->status, ['resolved', 'dismissed', 'archived', 'closed'], true)) {
            return (int) $case->timemodified + $window;
        }

        return null;
    }

    /**
     * Touch the parent case without changing the case decision.
     *
     * @param \stdClass $case Integrity case record.
     */
    private static function touch_case(\stdClass $case): void {
        global $DB;

        $case->timemodified = time();
        $case->versionno = (int) $case->versionno + 1;
        $case->provenancehash = integrity_case::hash_record($case);

        $DB->update_record(integrity_case::TABLE, $case);
    }
}