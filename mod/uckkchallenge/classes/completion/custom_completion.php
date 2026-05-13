<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Custom completion rules for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\completion;

use core_completion\activity_custom_completion;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity custom completion implementation for mod_uckkassembly.
 *
 * Supported rules:
 * - completionmotionenabled + completionmotioncount
 * - completionparticipationenabled + completionparticipationcount
 * - completionvoteenabled + completionvotecount
 *
 * The rules count learner activity inside the Assembly. They do not validate
 * decisions, publish minutes, archive records, or resolve contestations.
 */
final class custom_completion extends activity_custom_completion {
    /**
     * Rule key: required motions.
     */
    public const RULE_MOTIONS = 'completionmotionenabled';

    /**
     * Rule key: required participation.
     */
    public const RULE_PARTICIPATION = 'completionparticipationenabled';

    /**
     * Rule key: required votes/readings.
     */
    public const RULE_VOTES = 'completionvoteenabled';

    /**
     * Setting key: required motion count.
     */
    private const FIELD_MOTION_COUNT = 'completionmotioncount';

    /**
     * Setting key: required participation count.
     */
    private const FIELD_PARTICIPATION_COUNT = 'completionparticipationcount';

    /**
     * Setting key: required vote/reading count.
     */
    private const FIELD_VOTE_COUNT = 'completionvotecount';

    /**
     * Return the state for a custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        $assembly = $this->get_assembly_instance();

        if (!$this->is_rule_enabled($assembly, $rule)) {
            return COMPLETION_INCOMPLETE;
        }

        $required = $this->get_required_count($assembly, $rule);

        if ($required <= 0) {
            return COMPLETION_INCOMPLETE;
        }

        $actual = match ($rule) {
            self::RULE_MOTIONS => $this->count_user_motions((int)$assembly->id, (int)$this->userid),
            self::RULE_PARTICIPATION => $this->count_user_participation((int)$assembly->id, (int)$this->userid),
            self::RULE_VOTES => $this->count_user_votes((int)$assembly->id, (int)$this->userid),
            default => 0,
        };

        return $actual >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Return all custom completion rule names this activity defines.
     *
     * @return array<int, string>
     */
    public static function get_defined_custom_rules(): array {
        return [
            self::RULE_MOTIONS,
            self::RULE_PARTICIPATION,
            self::RULE_VOTES,
        ];
    }

    /**
     * Return human-readable descriptions for active custom completion rules.
     *
     * @return array<string, string>
     */
    public function get_custom_rule_descriptions(): array {
        $assembly = $this->get_assembly_instance();
        $descriptions = [];

        if ($this->is_rule_enabled($assembly, self::RULE_MOTIONS)) {
            $descriptions[self::RULE_MOTIONS] = get_string(
                'completionmotiondesc',
                'uckkassembly',
                $this->get_required_count($assembly, self::RULE_MOTIONS)
            );
        }

        if ($this->is_rule_enabled($assembly, self::RULE_PARTICIPATION)) {
            $descriptions[self::RULE_PARTICIPATION] = get_string(
                'completionparticipationdesc',
                'uckkassembly',
                $this->get_required_count($assembly, self::RULE_PARTICIPATION)
            );
        }

        if ($this->is_rule_enabled($assembly, self::RULE_VOTES)) {
            $descriptions[self::RULE_VOTES] = get_string(
                'completionvotedesc',
                'uckkassembly',
                $this->get_required_count($assembly, self::RULE_VOTES)
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
            self::RULE_MOTIONS,
            self::RULE_PARTICIPATION,
            self::RULE_VOTES,
        ];
    }

    /**
     * Load the Assembly instance for this course module.
     *
     * @return stdClass
     */
    private function get_assembly_instance(): stdClass {
        global $DB;

        return $DB->get_record(
            'uckkassembly',
            ['id' => $this->cm->instance],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Validate that a rule is known.
     *
     * @param string $rule Rule name.
     */
    private function validate_rule(string $rule): void {
        if (!in_array($rule, self::get_defined_custom_rules(), true)) {
            throw new \coding_exception('Unknown UCKK Assembly completion rule: ' . $rule);
        }
    }

    /**
     * Check whether a rule is enabled for this Assembly instance.
     *
     * @param stdClass $assembly Assembly instance.
     * @param string $rule Rule name.
     * @return bool
     */
    private function is_rule_enabled(stdClass $assembly, string $rule): bool {
        return !empty($assembly->{$rule});
    }

    /**
     * Return configured required count for a rule.
     *
     * @param stdClass $assembly Assembly instance.
     * @param string $rule Rule name.
     * @return int
     */
    private function get_required_count(stdClass $assembly, string $rule): int {
        $field = match ($rule) {
            self::RULE_MOTIONS => self::FIELD_MOTION_COUNT,
            self::RULE_PARTICIPATION => self::FIELD_PARTICIPATION_COUNT,
            self::RULE_VOTES => self::FIELD_VOTE_COUNT,
            default => '',
        };

        if ($field === '' || empty($assembly->{$field})) {
            return 0;
        }

        return max(0, (int)$assembly->{$field});
    }

    /**
     * Count motions proposed by the user in this Assembly.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return int
     */
    private function count_user_motions(int $assemblyid, int $userid): int {
        return $this->count_user_records('uckkassembly_motion', $assemblyid, $userid, [
            'draft',
            'withdrawn',
            'rejected',
            'invalidated',
        ]);
    }

    /**
     * Count votes/readings submitted by the user in this Assembly.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return int
     */
    private function count_user_votes(int $assemblyid, int $userid): int {
        return $this->count_user_records('uckkassembly_vote', $assemblyid, $userid, [
            'draft',
            'withdrawn',
            'invalidated',
        ]);
    }

    /**
     * Count all meaningful user participation in this Assembly.
     *
     * Participation includes motions, amendments, objections, votes/readings,
     * minutes contributions, and contestations where those tables exist.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return int
     */
    private function count_user_participation(int $assemblyid, int $userid): int {
        $excludedstatuses = [
            'draft',
            'withdrawn',
            'rejected',
            'invalidated',
        ];

        return $this->count_user_records('uckkassembly_motion', $assemblyid, $userid, $excludedstatuses)
            + $this->count_user_records('uckkassembly_amend', $assemblyid, $userid, $excludedstatuses)
            + $this->count_user_records('uckkassembly_object', $assemblyid, $userid, $excludedstatuses)
            + $this->count_user_records('uckkassembly_vote', $assemblyid, $userid, $excludedstatuses)
            + $this->count_user_records('uckkassembly_minutes', $assemblyid, $userid, $excludedstatuses)
            + $this->count_user_records('uckkassembly_contest', $assemblyid, $userid, $excludedstatuses);
    }

    /**
     * Count records in an Assembly-owned table for a user.
     *
     * This helper is defensive about column names because UCKK tables use a
     * consistent domain shape, but early migration versions may use either
     * assemblyid or uckkassemblyid and either userid or createdby.
     *
     * @param string $tablename Moodle table name without braces.
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @param array<int, string> $excludedstatuses Statuses not counted as completion.
     * @return int
     */
    private function count_user_records(
        string $tablename,
        int $assemblyid,
        int $userid,
        array $excludedstatuses = []
    ): int {
        global $DB;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists($tablename)) {
            return 0;
        }

        $assemblyfield = $this->first_existing_field($tablename, [
            'assemblyid',
            'uckkassemblyid',
            'instanceid',
        ]);

        $userfield = $this->first_existing_field($tablename, [
            'userid',
            'createdby',
            'authorid',
        ]);

        if ($assemblyfield === null || $userfield === null) {
            return 0;
        }

        $params = [
            'assemblyid' => $assemblyid,
            'userid' => $userid,
        ];

        $where = "{$assemblyfield} = :assemblyid AND {$userfield} = :userid";

        if ($this->field_exists($tablename, 'status') && !empty($excludedstatuses)) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(
                $excludedstatuses,
                SQL_PARAMS_NAMED,
                'excludedstatus',
                false
            );

            $where .= " AND status {$notinsql}";
            $params = array_merge($params, $notinparams);
        }

        return (int)$DB->count_records_select($tablename, $where, $params);
    }

    /**
     * Return the first existing field from a candidate list.
     *
     * @param string $tablename Table name without braces.
     * @param array<int, string> $candidates Candidate field names.
     * @return string|null
     */
    private function first_existing_field(string $tablename, array $candidates): ?string {
        foreach ($candidates as $candidate) {
            if ($this->field_exists($tablename, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Check whether a database field exists.
     *
     * @param string $tablename Table name without braces.
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