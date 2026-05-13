<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Privacy provider for local_uckk.
 *
 * local_uckk stores UCKK institutional data that may include personal data:
 *
 * - player profiles;
 * - symbolic roles;
 * - active pathway assignments;
 * - portfolio links;
 * - integrity flags;
 * - user reflections;
 * - system maps;
 * - provenance records;
 * - canon and pathway authorship metadata.
 *
 * Deletion policy:
 *
 * - user-owned private data is deleted;
 * - institutional records are preserved when needed for audit/memory;
 * - user references in institutional records are anonymised when appropriate;
 * - specialised plugins remain responsible for their own data:
 *   mod_uckkchallenge, mod_uckkassembly, mod_uckkarchive,
 *   tool_uckkintegrity, report_uckk and aiprovider_uckk.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\privacy;

use context;
use context_system;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\helper;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation.
 *
 * @package local_uckk
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    core_userlist_provider {

    /** Main profile table. */
    private const TABLE_PLAYER = 'local_uckk_player';

    /** Pathway table. */
    private const TABLE_PATHWAY = 'local_uckk_pathway';

    /** Program table. */
    private const TABLE_PROGRAM = 'local_uckk_program';

    /** Symbolic role registry table. */
    private const TABLE_ROLE = 'local_uckk_role';

    /** Canon table. */
    private const TABLE_CANON = 'local_uckk_canon';

    /** Provenance table. */
    private const TABLE_PROVENANCE = 'local_uckk_prov';

    /** Reflection table. */
    private const TABLE_REFLECTION = 'local_uckk_reflect';

    /** System map table. */
    private const TABLE_MAP = 'local_uckk_map';

    /**
     * Return metadata about user data stored by local_uckk.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::TABLE_PLAYER, [
            'userid' => 'privacy:metadata:local_uckk_player:userid',
            'displaytitle' => 'privacy:metadata:local_uckk_player:displaytitle',
            'symbolicroles' => 'privacy:metadata:local_uckk_player:symbolicroles',
            'activepathwayids' => 'privacy:metadata:local_uckk_player:activepathwayids',
            'portfolioarchiveid' => 'privacy:metadata:local_uckk_player:portfolioarchiveid',
            'integrityflags' => 'privacy:metadata:local_uckk_player:integrityflags',
            'visibility' => 'privacy:metadata:local_uckk_player:visibility',
            'status' => 'privacy:metadata:local_uckk_player:status',
            'metadata' => 'privacy:metadata:local_uckk_player:metadata',
            'createdby' => 'privacy:metadata:local_uckk_player:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_player:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_player:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_player:timemodified',
        ], 'privacy:metadata:local_uckk_player');

        $collection->add_database_table(self::TABLE_PATHWAY, [
            'programid' => 'privacy:metadata:local_uckk_pathway:programid',
            'shortname' => 'privacy:metadata:local_uckk_pathway:shortname',
            'fullname' => 'privacy:metadata:local_uckk_pathway:fullname',
            'requiredcourseids' => 'privacy:metadata:local_uckk_pathway:requiredcourseids',
            'requiredbadges' => 'privacy:metadata:local_uckk_pathway:requiredbadges',
            'requiredcompetencies' => 'privacy:metadata:local_uckk_pathway:requiredcompetencies',
            'status' => 'privacy:metadata:local_uckk_pathway:status',
            'visibility' => 'privacy:metadata:local_uckk_pathway:visibility',
            'createdby' => 'privacy:metadata:local_uckk_pathway:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_pathway:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_pathway:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_pathway:timemodified',
        ], 'privacy:metadata:local_uckk_pathway');

        $collection->add_database_table(self::TABLE_CANON, [
            'itemkey' => 'privacy:metadata:local_uckk_canon:itemkey',
            'itemtype' => 'privacy:metadata:local_uckk_canon:itemtype',
            'title' => 'privacy:metadata:local_uckk_canon:title',
            'summary' => 'privacy:metadata:local_uckk_canon:summary',
            'content' => 'privacy:metadata:local_uckk_canon:content',
            'visibility' => 'privacy:metadata:local_uckk_canon:visibility',
            'status' => 'privacy:metadata:local_uckk_canon:status',
            'versionno' => 'privacy:metadata:local_uckk_canon:versionno',
            'createdby' => 'privacy:metadata:local_uckk_canon:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_canon:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_canon:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_canon:timemodified',
        ], 'privacy:metadata:local_uckk_canon');

        $collection->add_database_table(self::TABLE_PROVENANCE, [
            'component' => 'privacy:metadata:local_uckk_prov:component',
            'itemtype' => 'privacy:metadata:local_uckk_prov:itemtype',
            'itemid' => 'privacy:metadata:local_uckk_prov:itemid',
            'contextid' => 'privacy:metadata:local_uckk_prov:contextid',
            'userid' => 'privacy:metadata:local_uckk_prov:userid',
            'action' => 'privacy:metadata:local_uckk_prov:action',
            'source' => 'privacy:metadata:local_uckk_prov:source',
            'sourcecomponent' => 'privacy:metadata:local_uckk_prov:sourcecomponent',
            'sourceitemid' => 'privacy:metadata:local_uckk_prov:sourceitemid',
            'sourcetext' => 'privacy:metadata:local_uckk_prov:sourcetext',
            'hash' => 'privacy:metadata:local_uckk_prov:hash',
            'state' => 'privacy:metadata:local_uckk_prov:state',
            'metadata' => 'privacy:metadata:local_uckk_prov:metadata',
            'createdby' => 'privacy:metadata:local_uckk_prov:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_prov:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_prov:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_prov:timemodified',
        ], 'privacy:metadata:local_uckk_prov');

        $collection->add_database_table(self::TABLE_REFLECTION, [
            'userid' => 'privacy:metadata:local_uckk_reflect:userid',
            'courseid' => 'privacy:metadata:local_uckk_reflect:courseid',
            'contextid' => 'privacy:metadata:local_uckk_reflect:contextid',
            'title' => 'privacy:metadata:local_uckk_reflect:title',
            'body' => 'privacy:metadata:local_uckk_reflect:body',
            'visibility' => 'privacy:metadata:local_uckk_reflect:visibility',
            'status' => 'privacy:metadata:local_uckk_reflect:status',
            'metadata' => 'privacy:metadata:local_uckk_reflect:metadata',
            'createdby' => 'privacy:metadata:local_uckk_reflect:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_reflect:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_reflect:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_reflect:timemodified',
        ], 'privacy:metadata:local_uckk_reflect');

        $collection->add_database_table(self::TABLE_MAP, [
            'userid' => 'privacy:metadata:local_uckk_map:userid',
            'courseid' => 'privacy:metadata:local_uckk_map:courseid',
            'contextid' => 'privacy:metadata:local_uckk_map:contextid',
            'title' => 'privacy:metadata:local_uckk_map:title',
            'mapdata' => 'privacy:metadata:local_uckk_map:mapdata',
            'visibility' => 'privacy:metadata:local_uckk_map:visibility',
            'status' => 'privacy:metadata:local_uckk_map:status',
            'metadata' => 'privacy:metadata:local_uckk_map:metadata',
            'createdby' => 'privacy:metadata:local_uckk_map:createdby',
            'modifiedby' => 'privacy:metadata:local_uckk_map:modifiedby',
            'timecreated' => 'privacy:metadata:local_uckk_map:timecreated',
            'timemodified' => 'privacy:metadata:local_uckk_map:timemodified',
        ], 'privacy:metadata:local_uckk_map');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_subsystem_link('core_log', [], 'privacy:metadata:core_log');

        return $collection;
    }

    /**
     * Return contexts where local_uckk stores personal data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        self::add_user_context_if_exists($contextlist, $userid);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_PLAYER, $userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_REFLECTION, $userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_MAP, $userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_PROVENANCE, $userid, [
            'userid',
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_PATHWAY, $userid, [
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_PROGRAM, $userid, [
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_CANON, $userid, [
            'createdby',
            'modifiedby',
        ]);

        self::add_contexts_from_table_user_fields($contextlist, self::TABLE_ROLE, $userid, [
            'createdby',
            'modifiedby',
        ]);

        return $contextlist;
    }

    /**
     * Export user data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && (int)$context->instanceid === (int)$user->id) {
                self::export_user_context_data($context, (int)$user->id);
            }

            self::export_context_records($context, (int)$user->id);
        }
    }

    /**
     * Delete all local_uckk user data in a context.
     *
     * This is used for context deletion, not only one user deletion.
     *
     * @param context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_USER) {
            $userid = (int)$context->instanceid;

            self::delete_user_owned_records($userid);
            self::anonymise_user_references($userid);
            return;
        }

        foreach ([self::TABLE_REFLECTION, self::TABLE_MAP, self::TABLE_PROVENANCE] as $table) {
            if (self::table_exists($table) && self::field_exists($table, 'contextid')) {
                $DB->delete_records($table, ['contextid' => $context->id]);
            }
        }

        foreach ([self::TABLE_PROGRAM, self::TABLE_PATHWAY, self::TABLE_ROLE, self::TABLE_CANON] as $table) {
            if (self::table_exists($table) && self::field_exists($table, 'contextid')) {
                self::anonymise_records_in_context($table, $context);
            }
        }
    }

    /**
     * Delete data for one user from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            self::delete_user_data_in_context($userid, $context);
        }
    }

    /**
     * Add users who have local_uckk data in the provided context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel === CONTEXT_USER) {
            $userlist->add_user((int)$context->instanceid);
            return;
        }

        foreach ([self::TABLE_REFLECTION, self::TABLE_MAP, self::TABLE_PROVENANCE] as $table) {
            self::add_users_from_table($userlist, $table, $context, ['userid', 'createdby', 'modifiedby']);
        }

        foreach ([self::TABLE_PROGRAM, self::TABLE_PATHWAY, self::TABLE_ROLE, self::TABLE_CANON] as $table) {
            self::add_users_from_table($userlist, $table, $context, ['createdby', 'modifiedby']);
        }
    }

    /**
     * Delete data for multiple users in a context.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        $userids = array_map('intval', $userlist->get_userids());

        if (empty($userids)) {
            return;
        }

        foreach ($userids as $userid) {
            self::delete_user_data_in_context($userid, $context);
        }
    }

    /**
     * Export data stored directly in the user's context.
     *
     * @param context $context User context.
     * @param int $userid User id.
     * @return void
     */
    private static function export_user_context_data(context $context, int $userid): void {
        global $DB;

        if (self::table_exists(self::TABLE_PLAYER)) {
            $profile = $DB->get_record(self::TABLE_PLAYER, ['userid' => $userid]);
            if ($profile) {
                self::export_single_record(
                    $context,
                    [get_string('privacy:path:profile', 'local_uckk')],
                    self::clean_record_for_export($profile)
                );
            }
        }

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_PROVENANCE,
            [get_string('privacy:path:provenance', 'local_uckk')],
            ['userid', 'createdby', 'modifiedby'],
            false
        );
    }

    /**
     * Export data for one context.
     *
     * @param context $context Context.
     * @param int $userid User id.
     * @return void
     */
    private static function export_context_records(context $context, int $userid): void {
        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_REFLECTION,
            [get_string('privacy:path:reflections', 'local_uckk')],
            ['userid', 'createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_MAP,
            [get_string('privacy:path:maps', 'local_uckk')],
            ['userid', 'createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_PROVENANCE,
            [get_string('privacy:path:provenance', 'local_uckk')],
            ['userid', 'createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_PATHWAY,
            [get_string('privacy:path:pathways', 'local_uckk')],
            ['createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_PROGRAM,
            [get_string('privacy:path:programs', 'local_uckk')],
            ['createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_CANON,
            [get_string('privacy:path:canon', 'local_uckk')],
            ['createdby', 'modifiedby'],
            true
        );

        self::export_user_related_records(
            $context,
            $userid,
            self::TABLE_ROLE,
            [get_string('privacy:path:symbolicroles', 'local_uckk')],
            ['createdby', 'modifiedby'],
            true
        );
    }

    /**
     * Export records related to a user.
     *
     * @param context $context Context.
     * @param int $userid User id.
     * @param string $table Table.
     * @param array<int, string> $subcontext Subcontext.
     * @param string[] $userfields User fields.
     * @param bool $requirecontextid Whether to filter by contextid.
     * @return void
     */
    private static function export_user_related_records(
        context $context,
        int $userid,
        string $table,
        array $subcontext,
        array $userfields,
        bool $requirecontextid
    ): void {
        global $DB;

        if (!self::table_exists($table)) {
            return;
        }

        $existingfields = array_values(array_filter($userfields, static fn(string $field): bool => self::field_exists($table, $field)));

        if (empty($existingfields)) {
            return;
        }

        $conditions = [];
        $params = [
            'userid' => $userid,
        ];

        foreach ($existingfields as $field) {
            $conditions[] = "{$field} = :userid";
        }

        $where = '(' . implode(' OR ', $conditions) . ')';

        if ($requirecontextid && self::field_exists($table, 'contextid')) {
            $where .= ' AND contextid = :contextid';
            $params['contextid'] = $context->id;
        }

        $records = $DB->get_records_select($table, $where, $params, 'timecreated ASC, id ASC');

        foreach ($records as $record) {
            $recordsubcontext = $subcontext;
            $recordsubcontext[] = self::get_record_export_name($record, $table);
            self::export_single_record($context, $recordsubcontext, self::clean_record_for_export($record));
        }
    }

    /**
     * Export one record.
     *
     * @param context $context Context.
     * @param array<int, string> $subcontext Subcontext.
     * @param stdClass $record Record.
     * @return void
     */
    private static function export_single_record(context $context, array $subcontext, stdClass $record): void {
        writer::with_context($context)->export_data($subcontext, $record);
    }

    /**
     * Delete one user's data in a specific context.
     *
     * @param int $userid User id.
     * @param context $context Context.
     * @return void
     */
    private static function delete_user_data_in_context(int $userid, context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_USER && (int)$context->instanceid === $userid) {
            self::delete_user_owned_records($userid);
            self::anonymise_user_references($userid);
            return;
        }

        foreach ([self::TABLE_REFLECTION, self::TABLE_MAP] as $table) {
            if (!self::table_exists($table)) {
                continue;
            }

            if (self::field_exists($table, 'contextid') && self::field_exists($table, 'userid')) {
                $DB->delete_records($table, [
                    'contextid' => $context->id,
                    'userid' => $userid,
                ]);
            }
        }

        if (self::table_exists(self::TABLE_PROVENANCE) && self::field_exists(self::TABLE_PROVENANCE, 'contextid')) {
            self::delete_or_anonymise_provenance_in_context($userid, $context);
        }

        foreach ([self::TABLE_PROGRAM, self::TABLE_PATHWAY, self::TABLE_ROLE, self::TABLE_CANON] as $table) {
            if (self::table_exists($table) && self::field_exists($table, 'contextid')) {
                self::anonymise_user_references_in_context($table, $userid, $context);
            }
        }
    }

    /**
     * Delete user-owned records stored directly by local_uckk.
     *
     * @param int $userid User id.
     * @return void
     */
    private static function delete_user_owned_records(int $userid): void {
        global $DB;

        if (self::table_exists(self::TABLE_PLAYER)) {
            $DB->delete_records(self::TABLE_PLAYER, ['userid' => $userid]);
        }

        foreach ([self::TABLE_REFLECTION, self::TABLE_MAP] as $table) {
            if (self::table_exists($table) && self::field_exists($table, 'userid')) {
                $DB->delete_records($table, ['userid' => $userid]);
            }
        }

        if (self::table_exists(self::TABLE_PROVENANCE) && self::field_exists(self::TABLE_PROVENANCE, 'userid')) {
            $DB->delete_records(self::TABLE_PROVENANCE, [
                'userid' => $userid,
                'component' => 'local_uckk',
                'itemtype' => 'player_profile',
            ]);
        }
    }

    /**
     * Anonymise references to a user in institutional records.
     *
     * @param int $userid User id.
     * @return void
     */
    private static function anonymise_user_references(int $userid): void {
        foreach ([self::TABLE_PROGRAM, self::TABLE_PATHWAY, self::TABLE_ROLE, self::TABLE_CANON, self::TABLE_PROVENANCE] as $table) {
            if (self::table_exists($table)) {
                self::anonymise_user_references_in_table($table, $userid);
            }
        }
    }

    /**
     * Anonymise user references in one table.
     *
     * @param string $table Table name.
     * @param int $userid User id.
     * @return void
     */
    private static function anonymise_user_references_in_table(string $table, int $userid): void {
        global $DB;

        foreach (['createdby', 'modifiedby', 'userid'] as $field) {
            if (!self::field_exists($table, $field)) {
                continue;
            }

            $records = $DB->get_records($table, [$field => $userid], '', 'id');
            foreach ($records as $record) {
                $update = (object)[
                    'id' => $record->id,
                    $field => 0,
                ];

                if (self::field_exists($table, 'timemodified')) {
                    $update->timemodified = time();
                }

                $DB->update_record($table, $update);
            }
        }
    }

    /**
     * Anonymise user references in one context.
     *
     * @param string $table Table.
     * @param int $userid User id.
     * @param context $context Context.
     * @return void
     */
    private static function anonymise_user_references_in_context(string $table, int $userid, context $context): void {
        global $DB;

        if (!self::field_exists($table, 'contextid')) {
            return;
        }

        foreach (['createdby', 'modifiedby', 'userid'] as $field) {
            if (!self::field_exists($table, $field)) {
                continue;
            }

            $records = $DB->get_records($table, [
                'contextid' => $context->id,
                $field => $userid,
            ], '', 'id');

            foreach ($records as $record) {
                $update = (object)[
                    'id' => $record->id,
                    $field => 0,
                ];

                if (self::field_exists($table, 'timemodified')) {
                    $update->timemodified = time();
                }

                $DB->update_record($table, $update);
            }
        }
    }

    /**
     * Anonymise every user reference in a context.
     *
     * @param string $table Table.
     * @param context $context Context.
     * @return void
     */
    private static function anonymise_records_in_context(string $table, context $context): void {
        global $DB;

        if (!self::field_exists($table, 'contextid')) {
            return;
        }

        $records = $DB->get_records($table, ['contextid' => $context->id], '', 'id');

        foreach ($records as $record) {
            $update = (object)[
                'id' => $record->id,
            ];

            $changed = false;
            foreach (['userid', 'createdby', 'modifiedby'] as $field) {
                if (self::field_exists($table, $field)) {
                    $update->{$field} = 0;
                    $changed = true;
                }
            }

            if ($changed) {
                if (self::field_exists($table, 'timemodified')) {
                    $update->timemodified = time();
                }

                $DB->update_record($table, $update);
            }
        }
    }

    /**
     * Delete or anonymise provenance records in a context.
     *
     * @param int $userid User id.
     * @param context $context Context.
     * @return void
     */
    private static function delete_or_anonymise_provenance_in_context(int $userid, context $context): void {
        global $DB;

        $table = self::TABLE_PROVENANCE;

        if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
            return;
        }

        if (self::field_exists($table, 'userid')) {
            $DB->delete_records($table, [
                'contextid' => $context->id,
                'userid' => $userid,
                'component' => 'local_uckk',
                'itemtype' => 'player_profile',
            ]);
        }

        self::anonymise_user_references_in_context($table, $userid, $context);
    }

    /**
     * Add user ids from a table to a userlist.
     *
     * @param userlist $userlist User list.
     * @param string $table Table.
     * @param context $context Context.
     * @param string[] $fields User id fields.
     * @return void
     */
    private static function add_users_from_table(userlist $userlist, string $table, context $context, array $fields): void {
        if (!self::table_exists($table)) {
            return;
        }

        $existingfields = array_values(array_filter($fields, static fn(string $field): bool => self::field_exists($table, $field)));

        if (empty($existingfields)) {
            return;
        }

        foreach ($existingfields as $field) {
            if (self::field_exists($table, 'contextid')) {
                $sql = "SELECT {$field} AS userid
                          FROM {{$table}}
                         WHERE contextid = :contextid
                           AND {$field} > 0";
                $userlist->add_from_sql('userid', $sql, ['contextid' => $context->id]);
            }
        }
    }

    /**
     * Add the user context to a contextlist when it exists.
     *
     * @param contextlist $contextlist Context list.
     * @param int $userid User id.
     * @return void
     */
    private static function add_user_context_if_exists(contextlist $contextlist, int $userid): void {
        $sql = "SELECT id
                  FROM {context}
                 WHERE contextlevel = :contextlevel
                   AND instanceid = :userid";
        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
    }

    /**
     * Add contexts from a table where user fields match.
     *
     * @param contextlist $contextlist Context list.
     * @param string $table Table.
     * @param int $userid User id.
     * @param string[] $userfields User fields.
     * @return void
     */
    private static function add_contexts_from_table_user_fields(
        contextlist $contextlist,
        string $table,
        int $userid,
        array $userfields
    ): void {
        if (!self::table_exists($table)) {
            return;
        }

        $existingfields = array_values(array_filter($userfields, static fn(string $field): bool => self::field_exists($table, $field)));

        if (empty($existingfields)) {
            return;
        }

        $conditions = [];
        $params = ['userid' => $userid];

        foreach ($existingfields as $field) {
            $conditions[] = "t.{$field} = :userid";
        }

        if (self::field_exists($table, 'contextid')) {
            $sql = "SELECT DISTINCT ctx.id
                      FROM {{$table}} t
                      JOIN {context} ctx ON ctx.id = t.contextid
                     WHERE " . implode(' OR ', $conditions);
            $contextlist->add_from_sql($sql, $params);
            return;
        }

        if ($table === self::TABLE_PLAYER && in_array('userid', $existingfields, true)) {
            self::add_user_context_if_exists($contextlist, $userid);
        } else {
            $systemcontext = context_system::instance();
            $contextlist->add_from_sql(
                "SELECT id FROM {context} WHERE id = :contextid",
                ['contextid' => $systemcontext->id]
            );
        }
    }

    /**
     * Clean a record for privacy export.
     *
     * @param stdClass $record Raw record.
     * @return stdClass
     */
    private static function clean_record_for_export(stdClass $record): stdClass {
        $data = new stdClass();

        foreach ((array)$record as $field => $value) {
            if (in_array($field, ['id', 'password', 'secret', 'token'], true)) {
                continue;
            }

            if (in_array($field, ['timecreated', 'timemodified'], true) && is_numeric($value)) {
                $data->{$field} = transform::datetime((int)$value);
                continue;
            }

            if (is_string($value) && self::looks_like_json($value)) {
                $decoded = json_decode($value, true);
                $data->{$field} = $decoded === null ? $value : $decoded;
                continue;
            }

            $data->{$field} = $value;
        }

        return $data;
    }

    /**
     * Return a stable record export name.
     *
     * @param stdClass $record Record.
     * @param string $table Table.
     * @return string
     */
    private static function get_record_export_name(stdClass $record, string $table): string {
        foreach (['title', 'fullname', 'shortname', 'itemkey', 'action'] as $field) {
            if (!empty($record->{$field})) {
                return clean_param((string)$record->{$field}, PARAM_TEXT);
            }
        }

        return $table . '-' . (int)($record->id ?? 0);
    }

    /**
     * Determine whether a string appears to be JSON.
     *
     * @param string $value Value.
     * @return bool
     */
    private static function looks_like_json(string $value): bool {
        $value = trim($value);

        return $value !== '' && (
            ($value[0] === '{' && substr($value, -1) === '}')
            || ($value[0] === '[' && substr($value, -1) === ']')
        );
    }

    /**
     * Determine whether a table exists.
     *
     * @param string $table Table name without prefix.
     * @return bool
     */
    private static function table_exists(string $table): bool {
        global $DB;

        $dbman = $DB->get_manager();

        return $dbman->table_exists(new xmldb_table($table));
    }

    /**
     * Determine whether a field exists in a table.
     *
     * @param string $table Table name without prefix.
     * @param string $field Field name.
     * @return bool
     */
    private static function field_exists(string $table, string $field): bool {
        global $DB;

        if (!self::table_exists($table)) {
            return false;
        }

        $dbman = $DB->get_manager();
        $xmldbtable = new xmldb_table($table);

        return $dbman->field_exists($xmldbtable, $field);
    }
}

