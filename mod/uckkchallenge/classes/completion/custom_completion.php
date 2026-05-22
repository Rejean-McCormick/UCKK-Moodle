<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Custom completion rules for UCKK Challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\completion;

use core_completion\activity_custom_completion;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity custom completion implementation for mod_uckkchallenge.
 *
 * Supported rules:
 * - completionrequiresubmission
 * - completionrequirevalidation
 */
final class custom_completion extends activity_custom_completion {
    /**
     * Rule key: user must submit proof/evidence.
     */
    public const RULE_SUBMISSION = 'completionrequiresubmission';

    /**
     * Rule key: user submission must be validated/reviewed.
     */
    public const RULE_VALIDATION = 'completionrequirevalidation';

    /**
     * Return the state for a custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        $challenge = $this->get_challenge_instance();

        if (!$this->is_rule_enabled($challenge, $rule)) {
            return COMPLETION_INCOMPLETE;
        }

        $challengeid = (int)$challenge->id;
        $userid = (int)$this->userid;

        $complete = match ($rule) {
            self::RULE_SUBMISSION => $this->has_user_submission($challengeid, $userid),
            self::RULE_VALIDATION => $this->has_user_validated_submission($challengeid, $userid),
            default => false,
        };

        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Return all custom completion rule names this activity defines.
     *
     * @return array<int, string>
     */
    public static function get_defined_custom_rules(): array {
        return [
            self::RULE_SUBMISSION,
            self::RULE_VALIDATION,
        ];
    }

    /**
     * Return human-readable descriptions for active custom completion rules.
     *
     * @return array<string, string>
     */
    public function get_custom_rule_descriptions(): array {
        $challenge = $this->get_challenge_instance();
        $descriptions = [];

        if ($this->is_rule_enabled($challenge, self::RULE_SUBMISSION)) {
            $descriptions[self::RULE_SUBMISSION] = $this->safe_get_string(
                'completionrequiresubmissiondesc',
                'Submit proof for this challenge.'
            );
        }

        if ($this->is_rule_enabled($challenge, self::RULE_VALIDATION)) {
            $descriptions[self::RULE_VALIDATION] = $this->safe_get_string(
                'completionrequirevalidationdesc',
                'Receive validation for submitted proof.'
            );
        }

        return $descriptions;
    }

    /**
     * Return display order for completion rules.
     *
     * @return array<int, string>
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            self::RULE_SUBMISSION,
            self::RULE_VALIDATION,
        ];
    }

    /**
     * Validate that a rule is known.
     *
     * Moodle expects this method to be public.
     *
     * @param string $rule Rule name.
     */
    public function validate_rule(string $rule): void {
        if (!in_array($rule, self::get_defined_custom_rules(), true)) {
            throw new \coding_exception('Unknown UCKK Challenge completion rule: ' . $rule);
        }
    }

    /**
     * Load the Challenge instance for this course module.
     *
     * @return stdClass
     */
    private function get_challenge_instance(): stdClass {
        global $DB;

        return $DB->get_record(
            'uckkchallenge',
            ['id' => $this->cm->instance],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Check whether a rule is enabled for this Challenge instance.
     *
     * @param stdClass $challenge Challenge instance.
     * @param string $rule Rule name.
     * @return bool
     */
    private function is_rule_enabled(stdClass $challenge, string $rule): bool {
        return !empty($challenge->{$rule});
    }

    /**
     * Check whether the user has at least one meaningful submission.
     *
     * @param int $challengeid Challenge id.
     * @param int $userid User id.
     * @return bool
     */
    private function has_user_submission(int $challengeid, int $userid): bool {
        global $DB;

        if (!$this->table_exists('uckkchallenge_sub')) {
            return false;
        }

        $params = [
            'challengeid' => $challengeid,
            'userid' => $userid,
        ];

        $where = 'challengeid = :challengeid AND userid = :userid';

        if ($this->field_exists('uckkchallenge_sub', 'status')) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(
                [
                    'draft',
                    'withdrawn',
                    'rejected',
                    'invalidated',
                ],
                SQL_PARAMS_NAMED,
                'excludedstatus',
                false
            );

            $where .= " AND status {$notinsql}";
            $params = array_merge($params, $notinparams);
        }

        return $DB->record_exists_select('uckkchallenge_sub', $where, $params);
    }

    /**
     * Check whether the user has at least one validated/reviewed submission.
     *
     * @param int $challengeid Challenge id.
     * @param int $userid User id.
     * @return bool
     */
    private function has_user_validated_submission(int $challengeid, int $userid): bool {
        global $DB;

        if (!$this->table_exists('uckkchallenge_sub')) {
            return false;
        }

        if ($this->has_user_submission_with_validated_status($challengeid, $userid)) {
            return true;
        }

        if ($this->has_user_submission_with_review_timestamp($challengeid, $userid)) {
            return true;
        }

        if ($this->has_user_evaluation($challengeid, $userid)) {
            return true;
        }

        return false;
    }

    /**
     * Check validated statuses on the submission table.
     *
     * @param int $challengeid Challenge id.
     * @param int $userid User id.
     * @return bool
     */
    private function has_user_submission_with_validated_status(int $challengeid, int $userid): bool {
        global $DB;

        if (!$this->field_exists('uckkchallenge_sub', 'status')) {
            return false;
        }

        [$insql, $inparams] = $DB->get_in_or_equal(
            [
                'validated',
                'accepted',
                'approved',
                'complete',
                'completed',
                'passed',
            ],
            SQL_PARAMS_NAMED,
            'validstatus'
        );

        $params = array_merge([
            'challengeid' => $challengeid,
            'userid' => $userid,
        ], $inparams);

        $where = "challengeid = :challengeid AND userid = :userid AND status {$insql}";

        return $DB->record_exists_select('uckkchallenge_sub', $where, $params);
    }

    /**
     * Check reviewed timestamp on the submission table.
     *
     * @param int $challengeid Challenge id.
     * @param int $userid User id.
     * @return bool
     */
    private function has_user_submission_with_review_timestamp(int $challengeid, int $userid): bool {
        global $DB;

        if (!$this->field_exists('uckkchallenge_sub', 'reviewedtime')) {
            return false;
        }

        return $DB->record_exists_select(
            'uckkchallenge_sub',
            'challengeid = :challengeid AND userid = :userid AND reviewedtime > 0',
            [
                'challengeid' => $challengeid,
                'userid' => $userid,
            ]
        );
    }

    /**
     * Check whether a submission by this user has an evaluation.
     *
     * @param int $challengeid Challenge id.
     * @param int $userid User id.
     * @return bool
     */
    private function has_user_evaluation(int $challengeid, int $userid): bool {
        global $DB;

        if (!$this->table_exists('uckkchallenge_eval')) {
            return false;
        }

        $sql = "SELECT 1
                  FROM {uckkchallenge_eval} e
                  JOIN {uckkchallenge_sub} s ON s.id = e.submissionid
                 WHERE s.challengeid = :challengeid
                   AND s.userid = :userid";

        $params = [
            'challengeid' => $challengeid,
            'userid' => $userid,
        ];

        if ($this->field_exists('uckkchallenge_eval', 'status')) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(
                [
                    'draft',
                    'withdrawn',
                    'invalidated',
                ],
                SQL_PARAMS_NAMED,
                'excludedstatus',
                false
            );

            $sql .= " AND e.status {$notinsql}";
            $params = array_merge($params, $notinparams);
        }

        $sql .= " LIMIT 1";

        return (bool)$DB->get_record_sql($sql, $params, IGNORE_MISSING);
    }

    /**
     * Safe string lookup with fallback.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function safe_get_string(string $identifier, string $fallback): string {
        $manager = get_string_manager();

        if ($manager->string_exists($identifier, 'uckkchallenge')) {
            return get_string($identifier, 'uckkchallenge');
        }

        return $fallback;
    }

    /**
     * Check whether a table exists.
     *
     * @param string $tablename Moodle table name without braces.
     * @return bool
     */
    private function table_exists(string $tablename): bool {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }

    /**
     * Check whether a database field exists.
     *
     * @param string $tablename Moodle table name without braces.
     * @param string $fieldname Field name.
     * @return bool
     */
    private function field_exists(string $tablename, string $fieldname): bool {
        global $DB;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists($tablename)) {
            return false;
        }

        return $dbman->field_exists($tablename, $fieldname);
    }
}