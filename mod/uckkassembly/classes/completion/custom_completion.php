<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Custom completion rules for the UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\completion;

defined('MOODLE_INTERNAL') || die();

use core_completion\activity_custom_completion;

/**
 * Activity custom completion implementation for mod_uckkassembly.
 *
 * These rules are evidence of participation in an Assembly. They do not certify
 * badges, competencies, legitimacy, integrity, or archive validation by
 * themselves.
 */
final class custom_completion extends activity_custom_completion {
    /** Completion rule: user must participate in the assembly. */
    public const COMPLETION_PARTICIPATION = 'completionparticipation';

    /** Completion rule: user must propose at least one motion. */
    public const COMPLETION_MOTION = 'completionmotion';

    /** Completion rule: user must submit at least one amendment. */
    public const COMPLETION_AMENDMENT = 'completionamendment';

    /** Completion rule: user must cast at least one vote/reading. */
    public const COMPLETION_VOTE = 'completionvote';

    /** Completion rule: at least one decision must be published. */
    public const COMPLETION_DECISION = 'completiondecision';

    /** Completion rule: minutes must be published. */
    public const COMPLETION_MINUTES = 'completionminutes';

    /** Completion rule: assembly must be archived. */
    public const COMPLETION_ARCHIVE = 'completionarchive';

    /**
     * Return completion state for a custom rule.
     *
     * @param string $rule Rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $assembly = $this->get_assembly();

        // If the rule is not enabled on this activity instance, it is complete
        // for Moodle's custom-rule check purposes.
        if (empty($assembly->{$rule})) {
            return COMPLETION_COMPLETE;
        }

        $complete = match ($rule) {
            self::COMPLETION_PARTICIPATION => $this->has_participated($assembly),
            self::COMPLETION_MOTION => $this->has_created_motion($assembly),
            self::COMPLETION_AMENDMENT => $this->has_created_amendment($assembly),
            self::COMPLETION_VOTE => $this->has_voted($assembly),
            self::COMPLETION_DECISION => $this->has_published_decision($assembly),
            self::COMPLETION_MINUTES => $this->has_published_minutes($assembly),
            self::COMPLETION_ARCHIVE => $this->is_archived($assembly),
            default => false,
        };

        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Return all custom rules supported by this activity.
     *
     * These field names must match the module instance table fields and the
     * custom completion controls added in mod_form.php.
     *
     * @return array<int, string>
     */
    public static function get_defined_custom_rules(): array {
        return [
            self::COMPLETION_PARTICIPATION,
            self::COMPLETION_MOTION,
            self::COMPLETION_AMENDMENT,
            self::COMPLETION_VOTE,
            self::COMPLETION_DECISION,
            self::COMPLETION_MINUTES,
            self::COMPLETION_ARCHIVE,
        ];
    }

    /**
     * Return human-readable descriptions for enabled custom completion rules.
     *
     * @return array<string, string>
     */
    public function get_custom_rule_descriptions(): array {
        $assembly = $this->get_assembly();
        $descriptions = [];

        $strings = [
            self::COMPLETION_PARTICIPATION => 'completionparticipation_desc',
            self::COMPLETION_MOTION => 'completionmotion_desc',
            self::COMPLETION_AMENDMENT => 'completionamendment_desc',
            self::COMPLETION_VOTE => 'completionvote_desc',
            self::COMPLETION_DECISION => 'completiondecision_desc',
            self::COMPLETION_MINUTES => 'completionminutes_desc',
            self::COMPLETION_ARCHIVE => 'completionarchive_desc',
        ];

        foreach ($strings as $rule => $stringkey) {
            if (!empty($assembly->{$rule})) {
                $descriptions[$rule] = get_string($stringkey, 'uckkassembly');
            }
        }

        return $descriptions;
    }

    /**
     * Return display order for custom completion rules.
     *
     * @return array<int, string>
     */
    public function get_sort_order(): array {
        return [
            self::COMPLETION_PARTICIPATION,
            self::COMPLETION_MOTION,
            self::COMPLETION_AMENDMENT,
            self::COMPLETION_VOTE,
            self::COMPLETION_DECISION,
            self::COMPLETION_MINUTES,
            self::COMPLETION_ARCHIVE,
        ];
    }

    /**
     * Load the assembly instance for this course module.
     *
     * @return \stdClass
     */
    private function get_assembly(): \stdClass {
        global $DB;

        return $DB->get_record('uckkassembly', ['id' => $this->cm->instance], '*', MUST_EXIST);
    }

    /**
     * Return whether the user has participated in the assembly.
     *
     * Participation can be recorded directly in {uckkassembly_part}, or inferred
     * from user-owned motions, amendments, objections, votes, minutes, or
     * contestations.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_participated(\stdClass $assembly): bool {
        return $this->has_record('uckkassembly_part', [
                'assemblyid' => $assembly->id,
                'userid' => $this->userid,
            ])
            || $this->has_created_motion($assembly)
            || $this->has_created_amendment($assembly)
            || $this->has_created_objection($assembly)
            || $this->has_voted($assembly)
            || $this->has_contributed_minutes($assembly)
            || $this->has_contested($assembly);
    }

    /**
     * Return whether the user has proposed at least one motion.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_created_motion(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_motion', (int)$assembly->id, [
            'userid',
            'createdby',
            'proposedby',
        ]);
    }

    /**
     * Return whether the user has submitted at least one amendment.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_created_amendment(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_amend', (int)$assembly->id, [
            'userid',
            'createdby',
            'proposedby',
        ]);
    }

    /**
     * Return whether the user has submitted at least one objection.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_created_objection(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_object', (int)$assembly->id, [
            'userid',
            'createdby',
            'objectedby',
        ]);
    }

    /**
     * Return whether the user has cast at least one vote/reading.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_voted(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_vote', (int)$assembly->id, [
            'userid',
            'createdby',
            'votedby',
        ]);
    }

    /**
     * Return whether the user contributed to minutes.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_contributed_minutes(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_minutes', (int)$assembly->id, [
            'userid',
            'createdby',
            'recordedby',
        ]);
    }

    /**
     * Return whether the user has contested a decision.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_contested(\stdClass $assembly): bool {
        return $this->has_user_record_with_any_user_field('uckkassembly_contest', (int)$assembly->id, [
            'userid',
            'createdby',
            'contestedby',
        ]);
    }

    /**
     * Return whether at least one decision has been published for the assembly.
     *
     * This rule is activity-level, not user-owned. It supports assemblies where
     * completion depends on the collective process reaching a documented
     * decision.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_published_decision(\stdClass $assembly): bool {
        global $DB;

        if (!$this->table_exists('uckkassembly_decision')) {
            return false;
        }

        $params = [
            'assemblyid' => $assembly->id,
        ];

        $sql = "assemblyid = :assemblyid
                AND status IN ('published', 'validated', 'closed', 'archived')";

        return $DB->record_exists_select('uckkassembly_decision', $sql, $params);
    }

    /**
     * Return whether minutes have been published for the assembly.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_published_minutes(\stdClass $assembly): bool {
        global $DB;

        if (!$this->table_exists('uckkassembly_minutes')) {
            return false;
        }

        $params = [
            'assemblyid' => $assembly->id,
        ];

        $sql = "assemblyid = :assemblyid
                AND status IN ('published', 'validated', 'closed', 'archived')";

        return $DB->record_exists_select('uckkassembly_minutes', $sql, $params);
    }

    /**
     * Return whether the assembly has been archived.
     *
     * @param \stdClass $assembly Assembly record.
     * @return bool
     */
    private function is_archived(\stdClass $assembly): bool {
        global $DB;

        $status = (string)($assembly->status ?? '');
        $state = (string)($assembly->state ?? '');

        if (in_array($status, ['archived', 'closed'], true) || in_array($state, ['archived', 'closed'], true)) {
            return true;
        }

        if (!$this->table_exists('uckkassembly_decision')) {
            return false;
        }

        return $DB->record_exists_select(
            'uckkassembly_decision',
            "assemblyid = :assemblyid AND decisiontype = :decisiontype AND status IN ('validated', 'published', 'archived')",
            [
                'assemblyid' => $assembly->id,
                'decisiontype' => 'archival',
            ]
        );
    }

    /**
     * Check whether a table exists.
     *
     * @param string $tablename Table name without prefix.
     * @return bool
     */
    private function table_exists(string $tablename): bool {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }

    /**
     * Check whether a record exists in a table.
     *
     * @param string $tablename Table name without prefix.
     * @param array<string, mixed> $conditions Conditions.
     * @return bool
     */
    private function has_record(string $tablename, array $conditions): bool {
        global $DB;

        if (!$this->table_exists($tablename)) {
            return false;
        }

        return $DB->record_exists($tablename, $conditions);
    }

    /**
     * Check a table for a user-owned record using any possible user field.
     *
     * This keeps completion compatible with the canonical table names while
     * allowing service-layer implementations to use clearer owner fields such
     * as createdby, proposedby, votedby, recordedby, or contestedby.
     *
     * @param string $tablename Table name without prefix.
     * @param int $assemblyid Assembly id.
     * @param array<int, string> $userfields Candidate user id fields.
     * @return bool
     */
    private function has_user_record_with_any_user_field(string $tablename, int $assemblyid, array $userfields): bool {
        global $DB;

        if (!$this->table_exists($tablename)) {
            return false;
        }

        $columns = $DB->get_columns($tablename);

        $assemblyfield = array_key_exists('assemblyid', $columns) ? 'assemblyid' : 'uckkassemblyid';

        if (!array_key_exists($assemblyfield, $columns)) {
            return false;
        }

        foreach ($userfields as $userfield) {
            if (!array_key_exists($userfield, $columns)) {
                continue;
            }

            if ($DB->record_exists($tablename, [
                $assemblyfield => $assemblyid,
                $userfield => $this->userid,
            ])) {
                return true;
            }
        }

        return false;
    }
}
