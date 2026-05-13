<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

class integrity_policy {
    public const CASE_TYPES = [
        'proof_quality',
        'fiction_fact_confusion',
        'ai_misuse',
        'harassment_or_humiliation',
        'dignity_violation',
        'authority_capture',
        'assessment_dispute',
        'challenge_dispute',
        'assembly_dispute',
        'archive_correction',
        'privacy_concern',
    ];

    public const STATUSES = [
        'opened',
        'triaged',
        'assigned',
        'under_review',
        'waiting_for_response',
        'correction_required',
        'resolved',
        'archived',
        'dismissed',
        'escalated',
        'paused',
        'reopened',
        'closed',
    ];

    public const NOTE_TYPES = [
        'observation',
        'evidence',
        'response',
        'decision',
        'correction',
        'appeal',
    ];

    private const TRANSITIONS = [
        'opened' => ['triaged', 'assigned', 'under_review', 'dismissed', 'paused'],
        'triaged' => ['assigned', 'under_review', 'waiting_for_response', 'dismissed', 'paused', 'escalated'],
        'assigned' => ['under_review', 'waiting_for_response', 'correction_required', 'paused', 'escalated'],
        'under_review' => ['waiting_for_response', 'correction_required', 'resolved', 'dismissed', 'paused', 'escalated'],
        'waiting_for_response' => ['under_review', 'correction_required', 'resolved', 'dismissed', 'paused'],
        'correction_required' => ['under_review', 'waiting_for_response', 'resolved', 'archived', 'closed'],
        'resolved' => ['archived', 'reopened', 'closed'],
        'dismissed' => ['reopened', 'archived', 'closed'],
        'escalated' => ['under_review', 'resolved', 'archived', 'closed'],
        'paused' => ['under_review', 'resolved', 'dismissed', 'escalated'],
        'reopened' => ['under_review', 'waiting_for_response', 'resolved', 'dismissed', 'paused'],
        'archived' => ['reopened'],
        'closed' => ['reopened'],
    ];

    public static function case_type_menu(): array {
        $menu = [];
        foreach (self::CASE_TYPES as $type) {
            $menu[$type] = get_string('type:' . $type, 'tool_uckkintegrity');
        }
        return $menu;
    }

    public static function status_menu(): array {
        $menu = [];
        foreach (self::STATUSES as $status) {
            $menu[$status] = get_string('status:' . $status, 'tool_uckkintegrity');
        }
        return $menu;
    }

    public static function note_type_menu(): array {
        $menu = [];
        foreach (self::NOTE_TYPES as $type) {
            $menu[$type] = get_string('note:' . $type, 'tool_uckkintegrity');
        }
        return $menu;
    }

    public static function validate_case_type(string $type): string {
        if (!in_array($type, self::CASE_TYPES, true)) {
            throw new \moodle_exception('unknowntype', 'tool_uckkintegrity');
        }
        return $type;
    }

    public static function validate_status(string $status): string {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \moodle_exception('unknownstatus', 'tool_uckkintegrity');
        }
        return $status;
    }

    public static function can_transition(string $from, string $to): bool {
        return $from === $to || in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function require_transition(\stdClass $case, string $to): void {
        self::validate_status($to);

        if (!self::can_transition($case->status, $to)) {
            throw new \moodle_exception('invalidtransition', 'tool_uckkintegrity', '', (object) [
                'from' => $case->status,
                'to' => $to,
            ]);
        }
    }

    public static function require_can_view_case(\stdClass $case): void {
        global $USER;

        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        if (has_capability('tool/uckkintegrity:viewrestricted', $context)
                || has_capability('tool/uckkintegrity:reviewcase', $context)) {
            return;
        }

        if ((int) $case->openedby === (int) $USER->id && has_capability('tool/uckkintegrity:view', $context)) {
            return;
        }

        if (!empty($case->assignedto)
                && (int) $case->assignedto === (int) $USER->id
                && has_capability('tool/uckkintegrity:view', $context)) {
            return;
        }

        throw new \required_capability_exception(
            $context,
            'tool/uckkintegrity:viewrestricted',
            'nopermissions',
            ''
        );
    }
}