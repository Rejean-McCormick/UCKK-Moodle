<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Privacy provider for the UCKK Seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for tool_uckkseed.
 *
 * The seed tool stores execution/audit metadata about users who run seed,
 * reset, validate, or export operations. It does not own the records created
 * by those operations; those remain owned by their Moodle components.
 */
final class provider implements metadata_provider, request_provider, core_userlist_provider {
    /**
     * Plugin component.
     */
    private const COMPONENT = 'tool_uckkseed';

    /**
     * Seed run table.
     */
    private const TABLE_RUN = 'tool_uckkseed_run';

    /**
     * Seed log table.
     */
    private const TABLE_LOG = 'tool_uckkseed_log';

    /**
     * Export subcontext.
     */
    private const SUBCONTEXT = ['UCKK seed'];

    /**
     * Return metadata describing stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            self::TABLE_RUN,
            [
                'userid' => 'privacy:metadata:tool_uckkseed_run:userid',
                'createdby' => 'privacy:metadata:tool_uckkseed_run:createdby',
                'modifiedby' => 'privacy:metadata:tool_uckkseed_run:modifiedby',
                'action' => 'privacy:metadata:tool_uckkseed_run:action',
                'mode' => 'privacy:metadata:tool_uckkseed_run:mode',
                'status' => 'privacy:metadata:tool_uckkseed_run:status',
                'component' => 'privacy:metadata:tool_uckkseed_run:component',
                'preset' => 'privacy:metadata:tool_uckkseed_run:preset',
                'targettype' => 'privacy:metadata:tool_uckkseed_run:targettype',
                'targetkey' => 'privacy:metadata:tool_uckkseed_run:targetkey',
                'targetid' => 'privacy:metadata:tool_uckkseed_run:targetid',
                'summary' => 'privacy:metadata:tool_uckkseed_run:summary',
                'details' => 'privacy:metadata:tool_uckkseed_run:details',
                'metadata' => 'privacy:metadata:tool_uckkseed_run:metadata',
                'timecreated' => 'privacy:metadata:tool_uckkseed_run:timecreated',
                'timemodified' => 'privacy:metadata:tool_uckkseed_run:timemodified',
            ],
            'privacy:metadata:tool_uckkseed_run'
        );

        $collection->add_database_table(
            self::TABLE_LOG,
            [
                'runid' => 'privacy:metadata:tool_uckkseed_log:runid',
                'userid' => 'privacy:metadata:tool_uckkseed_log:userid',
                'createdby' => 'privacy:metadata:tool_uckkseed_log:createdby',
                'modifiedby' => 'privacy:metadata:tool_uckkseed_log:modifiedby',
                'action' => 'privacy:metadata:tool_uckkseed_log:action',
                'mode' => 'privacy:metadata:tool_uckkseed_log:mode',
                'status' => 'privacy:metadata:tool_uckkseed_log:status',
                'level' => 'privacy:metadata:tool_uckkseed_log:level',
                'component' => 'privacy:metadata:tool_uckkseed_log:component',
                'preset' => 'privacy:metadata:tool_uckkseed_log:preset',
                'targettype' => 'privacy:metadata:tool_uckkseed_log:targettype',
                'targetkey' => 'privacy:metadata:tool_uckkseed_log:targetkey',
                'targetid' => 'privacy:metadata:tool_uckkseed_log:targetid',
                'summary' => 'privacy:metadata:tool_uckkseed_log:summary',
                'message' => 'privacy:metadata:tool_uckkseed_log:message',
                'details' => 'privacy:metadata:tool_uckkseed_log:details',
                'metadata' => 'privacy:metadata:tool_uckkseed_log:metadata',
                'timecreated' => 'privacy:metadata:tool_uckkseed_log:timecreated',
                'timemodified' => 'privacy:metadata:tool_uckkseed_log:timemodified',
            ],
            'privacy:metadata:tool_uckkseed_log'
        );

        return $collection;
    }

    /**
     * Return contexts where the plugin stores data for a user.
     *
     * The seed tool is an admin tool, so its own execution metadata belongs to
     * the system context.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if (!self::tables_exist()) {
            return $contextlist;
        }

        if (self::user_has_seed_data($userid)) {
            $contextlist->add_context(context_system::instance());
        }

        return $contextlist;
    }

    /**
     * Export user data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_system) {
                continue;
            }

            self::export_system_context_data((int)$user->id, $context);
        }
    }

    /**
     * Delete all seed-tool personal data for all users in a context.
     *
     * This deletes only seed run/log records, not Moodle content created by the
     * seed tool.
     *
     * @param context $context Context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_system || !self::tables_exist()) {
            return;
        }

        $DB->delete_records(self::TABLE_LOG);
        $DB->delete_records(self::TABLE_RUN);
    }

    /**
     * Delete seed-tool data for one approved user.
     *
     * This deletes only seed run/log rows associated with the user. It does not
     * delete the seeded distribution records.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                self::delete_user_seed_logs((int)$user->id);
            }
        }
    }

    /**
     * Add users who have seed-tool data in the supplied context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof context_system || !self::tables_exist()) {
            return;
        }

        self::add_users_from_table($userlist, self::TABLE_RUN, 'userid');
        self::add_users_from_table($userlist, self::TABLE_RUN, 'createdby');
        self::add_users_from_table($userlist, self::TABLE_RUN, 'modifiedby');

        self::add_users_from_table($userlist, self::TABLE_LOG, 'userid');
        self::add_users_from_table($userlist, self::TABLE_LOG, 'createdby');
        self::add_users_from_table($userlist, self::TABLE_LOG, 'modifiedby');
    }

    /**
     * Delete seed-tool data for multiple approved users in one context.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if (!$userlist->get_context() instanceof context_system || !self::tables_exist()) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_seed_logs((int)$userid);
        }
    }

    /**
     * Export data for the system context.
     *
     * @param int $userid User id.
     * @param context_system $context System context.
     */
    private static function export_system_context_data(int $userid, context_system $context): void {
        $runs = self::get_user_run_records($userid);
        $logs = self::get_user_log_records($userid);

        if (empty($runs) && empty($logs)) {
            return;
        }

        $data = new stdClass();
        $data->runs = array_values(array_map([self::class, 'clean_export_record'], $runs));
        $data->logs = array_values(array_map([self::class, 'clean_export_record'], $logs));

        writer::with_context($context)->export_data(self::SUBCONTEXT, $data);
    }

    /**
     * Get seed run records related to a user.
     *
     * @param int $userid User id.
     * @return stdClass[]
     */
    private static function get_user_run_records(int $userid): array {
        global $DB;

        if (!self::table_exists(self::TABLE_RUN)) {
            return [];
        }

        [$usersql, $params] = self::user_match_sql($userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        return $DB->get_records_select(
            self::TABLE_RUN,
            $usersql,
            $params,
            'timecreated ASC, id ASC'
        );
    }

    /**
     * Get seed log records related to a user.
     *
     * @param int $userid User id.
     * @return stdClass[]
     */
    private static function get_user_log_records(int $userid): array {
        global $DB;

        if (!self::table_exists(self::TABLE_LOG)) {
            return [];
        }

        [$usersql, $params] = self::user_match_sql($userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        return $DB->get_records_select(
            self::TABLE_LOG,
            $usersql,
            $params,
            'timecreated ASC, id ASC'
        );
    }

    /**
     * Delete seed run/log rows associated with a user.
     *
     * @param int $userid User id.
     */
    private static function delete_user_seed_logs(int $userid): void {
        global $DB;

        if (!self::tables_exist()) {
            return;
        }

        // First delete logs directly associated with this user.
        self::delete_user_rows(self::TABLE_LOG, $userid);

        // Also delete logs belonging to runs directly associated with this user.
        $runids = self::get_user_run_ids($userid);

        if (!empty($runids)) {
            [$insql, $params] = $DB->get_in_or_equal($runids, SQL_PARAMS_NAMED, 'runid');
            $DB->delete_records_select(self::TABLE_LOG, "runid {$insql}", $params);
        }

        self::delete_user_rows(self::TABLE_RUN, $userid);
    }

    /**
     * Delete rows in a table related to a user.
     *
     * @param string $table Table name.
     * @param int $userid User id.
     */
    private static function delete_user_rows(string $table, int $userid): void {
        global $DB;

        if (!self::table_exists($table)) {
            return;
        }

        [$usersql, $params] = self::user_match_sql($userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        $DB->delete_records_select($table, $usersql, $params);
    }

    /**
     * Get run ids associated with a user.
     *
     * @param int $userid User id.
     * @return int[]
     */
    private static function get_user_run_ids(int $userid): array {
        global $DB;

        if (!self::table_exists(self::TABLE_RUN)) {
            return [];
        }

        [$usersql, $params] = self::user_match_sql($userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        $records = $DB->get_records_select(self::TABLE_RUN, $usersql, $params, '', 'id');

        return array_map('intval', array_keys($records));
    }

    /**
     * Whether the user has any seed-tool data.
     *
     * @param int $userid User id.
     * @return bool
     */
    private static function user_has_seed_data(int $userid): bool {
        global $DB;

        foreach ([self::TABLE_RUN, self::TABLE_LOG] as $table) {
            if (!self::table_exists($table)) {
                continue;
            }

            [$usersql, $params] = self::user_match_sql($userid, [
                'userid',
                'createdby',
                'modifiedby',
            ]);

            if ($DB->record_exists_select($table, $usersql, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add users from a field in a table.
     *
     * @param userlist $userlist User list.
     * @param string $table Table name.
     * @param string $field User id field.
     */
    private static function add_users_from_table(userlist $userlist, string $table, string $field): void {
        global $DB;

        if (!self::table_exists($table) || !self::field_exists($table, $field)) {
            return;
        }

        $sql = "SELECT DISTINCT {$field}
                  FROM {{$table}}
                 WHERE {$field} IS NOT NULL
                   AND {$field} <> 0";

        $userids = $DB->get_fieldset_sql($sql);

        if (empty($userids)) {
            return;
        }

        $userlist->add_users(array_map('intval', $userids));
    }

    /**
     * Create SQL for matching user-related fields.
     *
     * @param int $userid User id.
     * @param string[] $fields Candidate fields.
     * @return array{0: string, 1: array<string, int>}
     */
    private static function user_match_sql(int $userid, array $fields): array {
        $conditions = [];
        $params = [];

        foreach ($fields as $field) {
            $conditions[] = "{$field} = :{$field}";
            $params[$field] = $userid;
        }

        return [
            '(' . implode(' OR ', $conditions) . ')',
            $params,
        ];
    }

    /**
     * Clean a record for export.
     *
     * @param stdClass $record Database record.
     * @return stdClass
     */
    private static function clean_export_record(stdClass $record): stdClass {
        $export = new stdClass();

        foreach ((array)$record as $key => $value) {
            if ($key === 'metadata' || $key === 'details') {
                $decoded = self::decode_json((string)$value);
                $export->{$key} = $decoded ?? $value;
                continue;
            }

            $export->{$key} = $value;
        }

        return $export;
    }

    /**
     * Decode JSON string if possible.
     *
     * @param string $value Raw value.
     * @return mixed|null
     */
    private static function decode_json(string $value): mixed {
        if (trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * Return whether both plugin tables exist.
     *
     * @return bool
     */
    private static function tables_exist(): bool {
        return self::table_exists(self::TABLE_RUN) && self::table_exists(self::TABLE_LOG);
    }

    /**
     * Return whether a table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    private static function table_exists(string $table): bool {
        global $DB;

        return $DB->get_manager()->table_exists($table);
    }

    /**
     * Return whether a field exists.
     *
     * @param string $table Table name.
     * @param string $field Field name.
     * @return bool
     */
    private static function field_exists(string $table, string $field): bool {
        global $DB;

        if (!self::table_exists($table)) {
            return false;
        }

        $columns = $DB->get_columns($table);

        return array_key_exists($field, $columns);
    }
}
