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
 * Canonical integrity rules, menus and access checks.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class integrity_policy {
    public static function case_type_menu(): array {
        return [
            'proof_quality' => get_string('type:proof_quality', 'tool_uckkintegrity'),
            'fiction_fact_confusion' => get_string('type:fiction_fact_confusion', 'tool_uckkintegrity'),
            'ai_misuse' => get_string('type:ai_misuse', 'tool_uckkintegrity'),
            'harassment_or_humiliation' => get_string('type:harassment_or_humiliation', 'tool_uckkintegrity'),
            'dignity_violation' => get_string('type:dignity_violation', 'tool_uckkintegrity'),
            'authority_capture' => get_string('type:authority_capture', 'tool_uckkintegrity'),
            'assessment_dispute' => get_string('type:assessment_dispute', 'tool_uckkintegrity'),
            'challenge_dispute' => get_string('type:challenge_dispute', 'tool_uckkintegrity'),
            'assembly_dispute' => get_string('type:assembly_dispute', 'tool_uckkintegrity'),
            'archive_correction' => get_string('type:archive_correction', 'tool_uckkintegrity'),
            'privacy_concern' => get_string('type:privacy_concern', 'tool_uckkintegrity'),
            'data_export_concern' => get_string('type:data_export_concern', 'tool_uckkintegrity'),
            'data_deletion_concern' => get_string('type:data_deletion_concern', 'tool_uckkintegrity'),
        ];
    }

    public static function status_menu(): array {
        return [
            'opened' => get_string('status:opened', 'tool_uckkintegrity'),
            'triaged' => get_string('status:triaged', 'tool_uckkintegrity'),
            'assigned' => get_string('status:assigned', 'tool_uckkintegrity'),
            'under_review' => get_string('status:under_review', 'tool_uckkintegrity'),
            'waiting_for_response' => get_string('status:waiting_for_response', 'tool_uckkintegrity'),
            'correction_required' => get_string('status:correction_required', 'tool_uckkintegrity'),
            'resolved' => get_string('status:resolved', 'tool_uckkintegrity'),
            'dismissed' => get_string('status:dismissed', 'tool_uckkintegrity'),
            'escalated' => get_string('status:escalated', 'tool_uckkintegrity'),
            'paused' => get_string('status:paused', 'tool_uckkintegrity'),
            'reopened' => get_string('status:reopened', 'tool_uckkintegrity'),
            'archived' => get_string('status:archived', 'tool_uckkintegrity'),
            'closed' => get_string('status:closed', 'tool_uckkintegrity'),
        ];
    }

    public static function decision_status_menu(): array {
        return array_intersect_key(self::status_menu(), array_flip([
            'correction_required',
            'resolved',
            'dismissed',
            'escalated',
            'archived',
            'closed',
        ]));
    }

    public static function note_type_menu(): array {
        return [
            'observation' => get_string('note:observation', 'tool_uckkintegrity'),
            'evidence' => get_string('note:evidence', 'tool_uckkintegrity'),
            'response' => get_string('note:response', 'tool_uckkintegrity'),
            'decision' => get_string('note:decision', 'tool_uckkintegrity'),
            'correction' => get_string('note:correction', 'tool_uckkintegrity'),
            'appeal' => get_string('note:appeal', 'tool_uckkintegrity'),
        ];
    }

    public static function transition_map(): array {
        return [
            'opened' => ['triaged', 'assigned', 'under_review', 'dismissed', 'paused'],
            'triaged' => ['assigned', 'under_review', 'dismissed', 'paused', 'escalated'],
            'assigned' => ['under_review', 'waiting_for_response', 'dismissed', 'paused', 'escalated'],
            'under_review' => ['waiting_for_response', 'correction_required', 'resolved', 'dismissed', 'escalated', 'paused'],
            'waiting_for_response' => ['under_review', 'correction_required', 'resolved', 'dismissed', 'escalated', 'paused'],
            'correction_required' => ['under_review', 'waiting_for_response', 'resolved', 'escalated', 'archived'],
            'resolved' => ['archived', 'reopened', 'closed'],
            'dismissed' => ['archived', 'reopened', 'closed'],
            'escalated' => ['under_review', 'resolved', 'dismissed', 'archived', 'closed'],
            'paused' => ['under_review', 'waiting_for_response', 'escalated', 'dismissed'],
            'reopened' => ['assigned', 'under_review', 'waiting_for_response', 'dismissed', 'paused'],
            'archived' => ['reopened', 'closed'],
            'closed' => [],
        ];
    }

    public static function ensure_valid_case_type(string $casetype): string {
        if (!array_key_exists($casetype, self::case_type_menu())) {
            throw new \moodle_exception('unknowntype', 'tool_uckkintegrity');
        }

        return $casetype;
    }

    public static function ensure_valid_status(string $status): string {
        if (!array_key_exists($status, self::status_menu())) {
            throw new \moodle_exception('unknownstatus', 'tool_uckkintegrity');
        }

        return $status;
    }

    public static function assert_valid_transition(string $from, string $to): void {
        $from = self::ensure_valid_status($from);
        $to = self::ensure_valid_status($to);

        if ($from === $to) {
            return;
        }

        $allowed = self::transition_map()[$from] ?? [];

        if (!in_array($to, $allowed, true)) {
            throw new \moodle_exception('invalidtransition', 'tool_uckkintegrity', '', (object) [
                'from' => $from,
                'to' => $to,
            ]);
        }
    }

    public static function can_view_case(\stdClass $case, ?int $userid = null): bool {
        global $USER;

        $userid = $userid ?? (int) ($USER->id ?? 0);
        $context = \context::instance_by_id((int) $case->contextid, MUST_EXIST);

        if (is_siteadmin($userid)) {
            return true;
        }

        if (has_capability('tool/uckkintegrity:viewrestricted', $context, $userid)) {
            return true;
        }

        if (has_capability('tool/uckkintegrity:reviewcase', $context, $userid)) {
            return true;
        }

        if (has_capability('tool/uckkintegrity:closecase', $context, $userid)) {
            return true;
        }

        if (!has_capability('tool/uckkintegrity:view', $context, $userid)) {
            return false;
        }

        if ((int) $case->openedby === $userid || (int) ($case->assignedto ?? 0) === $userid) {
            return true;
        }

        return !confidentiality::is_restricted($case);
    }

    public static function require_can_view_case(\stdClass $case): void {
        if (!self::can_view_case($case)) {
            throw new \moodle_exception('notpermitted', 'tool_uckkintegrity');
        }
    }
}
