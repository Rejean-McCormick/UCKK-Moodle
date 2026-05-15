<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Custom completion rules for the UCKK Archive activity.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive\completion;

use cm_info;
use coding_exception;
use core_completion\activity_custom_completion;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom completion rules for mod_uckkarchive.
 *
 * This class only reads archive records to determine Moodle completion state.
 * It must not create archive items, validate items, revise records, export
 * packages, alter provenance, or decide visibility/integrity outcomes.
 */
final class custom_completion extends activity_custom_completion {
    /** Completion rule: the user has created at least one archive item. */
    public const RULE_ITEM_ADDED = 'completionitemadded';

    /** Completion rule: the user has at least one validated archive item. */
    public const RULE_ITEM_VALIDATED = 'completionitemvalidated';

    /** Completion rule: the user has created at least one Kristal. */
    public const RULE_KRISTAL_CREATED = 'completionkristalcreated';

    /** Completion rule: the archive activity contains at least one validated item. */
    public const RULE_ARCHIVE_HAS_VALIDATED_ITEM = 'completionarchivevalidated';

    /** Completion rule: the archive activity has been exported. */
    public const RULE_ARCHIVE_EXPORTED = 'completionarchiveexported';

    /** Completion rule: the archive activity itself is archived. */
    public const RULE_ARCHIVE_STATE = 'completionarchivestate';

    /**
     * Return completion state for a custom rule.
     *
     * @param string $rule Completion rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        if (!$this->is_rule_enabled($rule)) {
            return COMPLETION_INCOMPLETE;
        }

        return match ($rule) {
            self::RULE_ITEM_ADDED => $this->has_user_archive_item(),
            self::RULE_ITEM_VALIDATED => $this->has_user_validated_archive_item(),
            self::RULE_KRISTAL_CREATED => $this->has_user_kristal(),
            self::RULE_ARCHIVE_HAS_VALIDATED_ITEM => $this->archive_has_validated_item(),
            self::RULE_ARCHIVE_EXPORTED => $this->archive_has_export(),
            self::RULE_ARCHIVE_STATE => $this->archive_is_archived(),
            default => COMPLETION_INCOMPLETE,
        };
    }

    /**
     * Return all custom rules defined by this activity.
     *
     * @return string[]
     */
    public static function get_defined_custom_rules(): array {
        return [
            self::RULE_ITEM_ADDED,
            self::RULE_ITEM_VALIDATED,
            self::RULE_KRISTAL_CREATED,
            self::RULE_ARCHIVE_HAS_VALIDATED_ITEM,
            self::RULE_ARCHIVE_EXPORTED,
            self::RULE_ARCHIVE_STATE,
        ];
    }

    /**
     * Return the custom rules available for this module instance.
     *
     * @return string[]
     */
    public function get_available_custom_rules(): array {
        $available = [];

        foreach (self::get_defined_custom_rules() as $rule) {
            if ($this->is_rule_enabled($rule)) {
                $available[] = $rule;
            }
        }

        return $available;
    }

    /**
     * Return human-readable descriptions of active custom rules.
     *
     * @return array<string, string>
     */
    public function get_custom_rule_descriptions(): array {
        $descriptions = [];

        foreach ($this->get_available_custom_rules() as $rule) {
            $descriptions[$rule] = match ($rule) {
                self::RULE_ITEM_ADDED => get_string('completionitemadded_desc', 'uckkarchive'),
                self::RULE_ITEM_VALIDATED => get_string('completionitemvalidated_desc', 'uckkarchive'),
                self::RULE_KRISTAL_CREATED => get_string('completionkristalcreated_desc', 'uckkarchive'),
                self::RULE_ARCHIVE_HAS_VALIDATED_ITEM => get_string('completionarchivevalidated_desc', 'uckkarchive'),
                self::RULE_ARCHIVE_EXPORTED => get_string('completionarchiveexported_desc', 'uckkarchive'),
                self::RULE_ARCHIVE_STATE => get_string('completionarchivestate_desc', 'uckkarchive'),
                default => '',
            };
        }

        return array_filter($descriptions);
    }

    /**
     * Return whether a rule is defined by this activity.
     *
     * @param string $rule Completion rule name.
     * @return bool
     */
    public function is_defined(string $rule): bool {
        return in_array($rule, self::get_defined_custom_rules(), true);
    }

    /**
     * Throw if a rule is unknown.
     *
     * @param string $rule Completion rule name.
     */
    private function validate_rule(string $rule): void {
        if (!$this->is_defined($rule)) {
            throw new coding_exception("Unknown mod_uckkarchive custom completion rule: {$rule}");
        }
    }

    /**
     * Return whether a custom completion rule is enabled for this cm.
     *
     * Supports both:
     * - values stored in cm_info customdata['customcompletionrules']; and
     * - direct fields on the archive instance record.
     *
     * @param string $rule Rule name.
     * @return bool
     */
    private function is_rule_enabled(string $rule): bool {
        if (
            isset($this->cm->customdata['customcompletionrules'])
            && is_array($this->cm->customdata['customcompletionrules'])
            && array_key_exists($rule, $this->cm->customdata['customcompletionrules'])
        ) {
            return !empty($this->cm->customdata['customcompletionrules'][$rule]);
        }

        $archive = $this->get_archive_instance();

        if ($archive !== null && property_exists($archive, $rule)) {
            return !empty($archive->{$rule});
        }

        return false;
    }

    /**
     * Load the archive instance for this course module.
     *
     * @return stdClass|null
     */
    private function get_archive_instance(): ?stdClass {
        global $DB;

        if (empty($this->cm->instance)) {
            return null;
        }

        return $DB->get_record('uckkarchive', ['id' => $this->cm->instance], '*', IGNORE_MISSING) ?: null;
    }

    /**
     * Return whether a table exists.
     *
     * @param string $tablename Moodle table name without braces.
     * @return bool
     */
    private function table_exists(string $tablename): bool {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }

    /**
     * Return whether a field exists in a table.
     *
     * @param string $tablename Moodle table name without braces.
     * @param string $field Field name.
     * @return bool
     */
    private function field_exists(string $tablename, string $field): bool {
        global $DB;

        if (!$this->table_exists($tablename)) {
            return false;
        }

        $columns = $DB->get_columns($tablename);

        return array_key_exists($field, $columns);
    }

    /**
     * Return the archive foreign-key condition for a child table.
     *
     * @param string $tablename Table name.
     * @return array<string, int>|null
     */
    private function get_archive_condition(string $tablename): ?array {
        if ($this->field_exists($tablename, 'archiveid')) {
            return ['archiveid' => (int)$this->cm->instance];
        }

        if ($this->field_exists($tablename, 'uckkarchiveid')) {
            return ['uckkarchiveid' => (int)$this->cm->instance];
        }

        return null;
    }

    /**
     * Return an OR SQL condition for ownership by the current user.
     *
     * @param string $tablename Table name.
     * @param array<string, mixed> $params Existing params.
     * @return array{sql: string, params: array<string, mixed>}
     */
    private function get_user_condition(string $tablename, array $params = []): array {
        $fields = [];

        foreach (['userid', 'authorid', 'createdby', 'submittedby', 'ownerid'] as $field) {
            if ($this->field_exists($tablename, $field)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $this->userid;
            }
        }

        if (empty($fields)) {
            return [
                'sql' => '1 = 0',
                'params' => $params,
            ];
        }

        return [
            'sql' => '(' . implode(' OR ', $fields) . ')',
            'params' => $params,
        ];
    }

    /**
     * Return an SQL condition for accepted validation states.
     *
     * @param string $tablename Table name.
     * @param array<string, mixed> $params Existing params.
     * @return array{sql: string, params: array<string, mixed>}
     */
    private function get_validated_condition(string $tablename, array $params = []): array {
        $parts = [];

        if ($this->field_exists($tablename, 'status')) {
            $parts[] = 'status IN (:statusvalidated, :statuspublished, :statusarchived)';
            $params['statusvalidated'] = 'validated';
            $params['statuspublished'] = 'published';
            $params['statusarchived'] = 'archived';
        }

        if ($this->field_exists($tablename, 'validationstate')) {
            $parts[] = 'validationstate IN (:validationhumanreviewed, :validationverified, :validationarchived)';
            $params['validationhumanreviewed'] = 'human_reviewed';
            $params['validationverified'] = 'verified';
            $params['validationarchived'] = 'archived';
        }

        if (empty($parts)) {
            return [
                'sql' => '1 = 0',
                'params' => $params,
            ];
        }

        return [
            'sql' => '(' . implode(' OR ', $parts) . ')',
            'params' => $params,
        ];
    }

    /**
     * Completion state when the current user has created an archive item.
     *
     * @return int
     */
    private function has_user_archive_item(): int {
        global $DB;

        $tablename = 'uckkarchive_item';

        if (!$this->table_exists($tablename)) {
            return COMPLETION_INCOMPLETE;
        }

        $archivecondition = $this->get_archive_condition($tablename);

        if ($archivecondition === null) {
            return COMPLETION_INCOMPLETE;
        }

        $params = [
            'archiveid' => reset($archivecondition),
        ];

        $archivefield = key($archivecondition);
        $usercondition = $this->get_user_condition($tablename, $params);

        $count = $DB->count_records_select(
            $tablename,
            "{$archivefield} = :archiveid AND {$usercondition['sql']}",
            $usercondition['params']
        );

        return $count > 0 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Completion state when the current user has a validated archive item.
     *
     * @return int
     */
    private function has_user_validated_archive_item(): int {
        global $DB;

        $tablename = 'uckkarchive_item';

        if (!$this->table_exists($tablename)) {
            return COMPLETION_INCOMPLETE;
        }

        $archivecondition = $this->get_archive_condition($tablename);

        if ($archivecondition === null) {
            return COMPLETION_INCOMPLETE;
        }

        $params = [
            'archiveid' => reset($archivecondition),
        ];

        $archivefield = key($archivecondition);
        $usercondition = $this->get_user_condition($tablename, $params);
        $validatedcondition = $this->get_validated_condition($tablename, $usercondition['params']);

        $count = $DB->count_records_select(
            $tablename,
            "{$archivefield} = :archiveid AND {$usercondition['sql']} AND {$validatedcondition['sql']}",
            $validatedcondition['params']
        );

        return $count > 0 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Completion state when the current user has created a Kristal.
     *
     * @return int
     */
    private function has_user_kristal(): int {
        global $DB;

        $tablename = 'uckkarchive_kristal';

        if (!$this->table_exists($tablename)) {
            return COMPLETION_INCOMPLETE;
        }

        $archivecondition = $this->get_archive_condition($tablename);

        if ($archivecondition === null) {
            return COMPLETION_INCOMPLETE;
        }

        $params = [
            'archiveid' => reset($archivecondition),
        ];

        $archivefield = key($archivecondition);
        $usercondition = $this->get_user_condition($tablename, $params);

        $count = $DB->count_records_select(
            $tablename,
            "{$archivefield} = :archiveid AND {$usercondition['sql']}",
            $usercondition['params']
        );

        return $count > 0 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Completion state when the archive has any validated item.
     *
     * @return int
     */
    private function archive_has_validated_item(): int {
        global $DB;

        $tablename = 'uckkarchive_item';

        if (!$this->table_exists($tablename)) {
            return COMPLETION_INCOMPLETE;
        }

        $archivecondition = $this->get_archive_condition($tablename);

        if ($archivecondition === null) {
            return COMPLETION_INCOMPLETE;
        }

        $params = [
            'archiveid' => reset($archivecondition),
        ];

        $archivefield = key($archivecondition);
        $validatedcondition = $this->get_validated_condition($tablename, $params);

        $count = $DB->count_records_select(
            $tablename,
            "{$archivefield} = :archiveid AND {$validatedcondition['sql']}",
            $validatedcondition['params']
        );

        return $count > 0 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Completion state when an export package exists for this archive.
     *
     * @return int
     */
    private function archive_has_export(): int {
        global $DB;

        $tablename = 'uckkarchive_export';

        if (!$this->table_exists($tablename)) {
            return COMPLETION_INCOMPLETE;
        }

        $archivecondition = $this->get_archive_condition($tablename);

        if ($archivecondition === null) {
            return COMPLETION_INCOMPLETE;
        }

        $params = [
            'archiveid' => reset($archivecondition),
        ];

        $archivefield = key($archivecondition);
        $statussql = '1 = 1';

        if ($this->field_exists($tablename, 'status')) {
            $statussql = 'status IN (:statusexported, :statuspublished, :statusarchived)';
            $params['statusexported'] = 'exported';
            $params['statuspublished'] = 'published';
            $params['statusarchived'] = 'archived';
        }

        $count = $DB->count_records_select(
            $tablename,
            "{$archivefield} = :archiveid AND {$statussql}",
            $params
        );

        return $count > 0 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Completion state when the archive activity itself is archived.
     *
     * @return int
     */
    private function archive_is_archived(): int {
        $archive = $this->get_archive_instance();

        if ($archive === null) {
            return COMPLETION_INCOMPLETE;
        }

        $state = clean_param((string)($archive->state ?? ''), PARAM_ALPHANUMEXT);
        $status = clean_param((string)($archive->status ?? ''), PARAM_ALPHANUMEXT);

        return $state === 'archived' || $status === 'archived'
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }
}
