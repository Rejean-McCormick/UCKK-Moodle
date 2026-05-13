`mod/uckkassembly/classes/privacy/provider.php`

This provider implements Moodle’s privacy contract for a plugin that stores personal data. Moodle requires standard data-storing plugins to locate contexts, export user data, delete all user data in a context, delete one user’s data in approved contexts, locate users in a context, and delete data for multiple approved users.  UCKK specifies that `mod_uckkassembly` stores motions, votes/readings, arguments, and minutes contributions, so it must implement a real privacy provider.  Deletion must preserve institutional integrity where required, especially for assembly votes/readings and decisions, while handling personal participation according to privacy/retention policy. 

```php
<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Privacy provider for mod_uckkassembly.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for UCKK Assemblies.
 *
 * The assembly activity stores participant-authored deliberation records:
 * motions, amendments, objections, votes/readings, decisions, minutes
 * contributions, and contestations.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Component name.
     */
    private const COMPONENT = 'mod_uckkassembly';

    /**
     * Activity table.
     */
    private const ASSEMBLY_TABLE = 'uckkassembly';

    /**
     * User-contribution tables.
     *
     * Each table should include the common UCKK fields where possible:
     * userid, createdby, modifiedby, timecreated, timemodified, status,
     * visibility, provenance, versionno, metadata.
     */
    private const CONTRIBUTION_TABLES = [
        'uckkassembly_motion' => [
            'area' => 'motions',
            'lang' => 'privacy:path:motions',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'rationale', 'summary'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_amend' => [
            'area' => 'amendments',
            'lang' => 'privacy:path:amendments',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'rationale', 'summary'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_object' => [
            'area' => 'objections',
            'lang' => 'privacy:path:objections',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'proposedresolution', 'summary'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_vote' => [
            'area' => 'votes',
            'lang' => 'privacy:path:votes',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => '',
            'bodyfields' => ['rationale', 'comment', 'readingnote'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_decision' => [
            'area' => 'decisions',
            'lang' => 'privacy:path:decisions',
            'useridfields' => ['userid', 'createdby', 'modifiedby', 'publishedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'summary', 'publicsummary', 'rationale'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_minutes' => [
            'area' => 'minutes',
            'lang' => 'privacy:path:minutes',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'summary', 'minutes', 'contribution'],
            'delete_draft_statuses' => ['draft'],
        ],
        'uckkassembly_contest' => [
            'area' => 'contestations',
            'lang' => 'privacy:path:contestations',
            'useridfields' => ['userid', 'createdby', 'modifiedby'],
            'titlefield' => 'title',
            'bodyfields' => ['body', 'reason', 'summary', 'requestedcorrection'],
            'delete_draft_statuses' => ['draft'],
        ],
    ];

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('uckkassembly_motion', [
            'assemblyid' => 'privacy:metadata:uckkassembly_motion:assemblyid',
            'userid' => 'privacy:metadata:uckkassembly_motion:userid',
            'title' => 'privacy:metadata:uckkassembly_motion:title',
            'body' => 'privacy:metadata:uckkassembly_motion:body',
            'status' => 'privacy:metadata:uckkassembly_motion:status',
            'visibility' => 'privacy:metadata:uckkassembly_motion:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_motion:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_motion:timemodified',
        ], 'privacy:metadata:uckkassembly_motion');

        $collection->add_database_table('uckkassembly_amend', [
            'assemblyid' => 'privacy:metadata:uckkassembly_amend:assemblyid',
            'motionid' => 'privacy:metadata:uckkassembly_amend:motionid',
            'userid' => 'privacy:metadata:uckkassembly_amend:userid',
            'body' => 'privacy:metadata:uckkassembly_amend:body',
            'status' => 'privacy:metadata:uckkassembly_amend:status',
            'visibility' => 'privacy:metadata:uckkassembly_amend:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_amend:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_amend:timemodified',
        ], 'privacy:metadata:uckkassembly_amend');

        $collection->add_database_table('uckkassembly_object', [
            'assemblyid' => 'privacy:metadata:uckkassembly_object:assemblyid',
            'userid' => 'privacy:metadata:uckkassembly_object:userid',
            'targettype' => 'privacy:metadata:uckkassembly_object:targettype',
            'objectiontype' => 'privacy:metadata:uckkassembly_object:objectiontype',
            'title' => 'privacy:metadata:uckkassembly_object:title',
            'body' => 'privacy:metadata:uckkassembly_object:body',
            'status' => 'privacy:metadata:uckkassembly_object:status',
            'visibility' => 'privacy:metadata:uckkassembly_object:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_object:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_object:timemodified',
        ], 'privacy:metadata:uckkassembly_object');

        $collection->add_database_table('uckkassembly_vote', [
            'assemblyid' => 'privacy:metadata:uckkassembly_vote:assemblyid',
            'motionid' => 'privacy:metadata:uckkassembly_vote:motionid',
            'userid' => 'privacy:metadata:uckkassembly_vote:userid',
            'votevalue' => 'privacy:metadata:uckkassembly_vote:votevalue',
            'readingtype' => 'privacy:metadata:uckkassembly_vote:readingtype',
            'rationale' => 'privacy:metadata:uckkassembly_vote:rationale',
            'timecreated' => 'privacy:metadata:uckkassembly_vote:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_vote:timemodified',
        ], 'privacy:metadata:uckkassembly_vote');

        $collection->add_database_table('uckkassembly_decision', [
            'assemblyid' => 'privacy:metadata:uckkassembly_decision:assemblyid',
            'userid' => 'privacy:metadata:uckkassembly_decision:userid',
            'publishedby' => 'privacy:metadata:uckkassembly_decision:publishedby',
            'title' => 'privacy:metadata:uckkassembly_decision:title',
            'body' => 'privacy:metadata:uckkassembly_decision:body',
            'publicsummary' => 'privacy:metadata:uckkassembly_decision:publicsummary',
            'status' => 'privacy:metadata:uckkassembly_decision:status',
            'visibility' => 'privacy:metadata:uckkassembly_decision:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_decision:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_decision:timemodified',
        ], 'privacy:metadata:uckkassembly_decision');

        $collection->add_database_table('uckkassembly_minutes', [
            'assemblyid' => 'privacy:metadata:uckkassembly_minutes:assemblyid',
            'userid' => 'privacy:metadata:uckkassembly_minutes:userid',
            'title' => 'privacy:metadata:uckkassembly_minutes:title',
            'body' => 'privacy:metadata:uckkassembly_minutes:body',
            'status' => 'privacy:metadata:uckkassembly_minutes:status',
            'visibility' => 'privacy:metadata:uckkassembly_minutes:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_minutes:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_minutes:timemodified',
        ], 'privacy:metadata:uckkassembly_minutes');

        $collection->add_database_table('uckkassembly_contest', [
            'assemblyid' => 'privacy:metadata:uckkassembly_contest:assemblyid',
            'userid' => 'privacy:metadata:uckkassembly_contest:userid',
            'title' => 'privacy:metadata:uckkassembly_contest:title',
            'body' => 'privacy:metadata:uckkassembly_contest:body',
            'status' => 'privacy:metadata:uckkassembly_contest:status',
            'visibility' => 'privacy:metadata:uckkassembly_contest:visibility',
            'timecreated' => 'privacy:metadata:uckkassembly_contest:timecreated',
            'timemodified' => 'privacy:metadata:uckkassembly_contest:timemodified',
        ], 'privacy:metadata:uckkassembly_contest');

        return $collection;
    }

    /**
     * Return contexts containing personal data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $params = [
            'modname' => 'uckkassembly',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $conditions = [];
        foreach (self::CONTRIBUTION_TABLES as $table => $config) {
            foreach ($config['useridfields'] as $field) {
                $conditions[] = "{$tablealias = self::alias_for_table($table)}.{$field} = :{$tablealias}{$field}";
                $params[$tablealias . $field] = $userid;
            }
        }

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} md ON md.id = cm.module AND md.name = :modname
                  JOIN {" . self::ASSEMBLY_TABLE . "} a ON a.id = cm.instance
             LEFT JOIN {uckkassembly_motion} motion ON motion.assemblyid = a.id
             LEFT JOIN {uckkassembly_amend} amend ON amend.assemblyid = a.id
             LEFT JOIN {uckkassembly_object} objectn ON objectn.assemblyid = a.id
             LEFT JOIN {uckkassembly_vote} vote ON vote.assemblyid = a.id
             LEFT JOIN {uckkassembly_decision} decisionn ON decisionn.assemblyid = a.id
             LEFT JOIN {uckkassembly_minutes} minutesn ON minutesn.assemblyid = a.id
             LEFT JOIN {uckkassembly_contest} contest ON contest.assemblyid = a.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND (" . implode(' OR ', $conditions) . ")";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Add users with data in the supplied context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('uckkassembly', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        foreach (self::CONTRIBUTION_TABLES as $table => $config) {
            foreach ($config['useridfields'] as $field) {
                self::add_users_from_table_field($userlist, $table, $field, (int)$cm->instance);
            }
        }
    }

    /**
     * Export all user data for the supplied approved context list.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('uckkassembly', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $assembly = self::get_assembly((int)$cm->instance);
            if (!$assembly) {
                continue;
            }

            $basepath = [
                get_string('pluginname', 'uckkassembly'),
                format_string($assembly->name),
            ];

            self::export_assembly_summary($basepath, $assembly);

            foreach (self::CONTRIBUTION_TABLES as $table => $config) {
                $records = self::get_user_records_for_table($table, $config, (int)$assembly->id, $userid);
                self::export_records($basepath, $config, $records);
            }
        }
    }

    /**
     * Delete all user data for all users in a context.
     *
     * @param context $context Context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('uckkassembly', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        foreach (array_keys(self::CONTRIBUTION_TABLES) as $table) {
            self::delete_or_anonymise_records_for_assembly($table, (int)$cm->instance);
        }
    }

    /**
     * Delete data for one approved user across approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('uckkassembly', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            foreach (self::CONTRIBUTION_TABLES as $table => $config) {
                self::delete_or_anonymise_user_records($table, $config, (int)$cm->instance, $userid);
            }
        }
    }

    /**
     * Delete data for multiple approved users in a context.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('uckkassembly', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            foreach (self::CONTRIBUTION_TABLES as $table => $config) {
                self::delete_or_anonymise_user_records($table, $config, (int)$cm->instance, (int)$userid);
            }
        }
    }

    /**
     * Export basic assembly summary.
     *
     * @param array<int, string> $basepath Export path.
     * @param stdClass $assembly Assembly record.
     */
    private static function export_assembly_summary(array $basepath, stdClass $assembly): void {
        $summary = (object)[
            'name' => format_string($assembly->name ?? ''),
            'assemblytype' => $assembly->assemblytype ?? '',
            'status' => $assembly->status ?? '',
            'visibility' => $assembly->visibility ?? '',
            'timecreated' => !empty($assembly->timecreated) ? transform::datetime((int)$assembly->timecreated) : null,
            'timemodified' => !empty($assembly->timemodified) ? transform::datetime((int)$assembly->timemodified) : null,
        ];

        writer::with_context(context_module::instance((int)$assembly->cmid))->export_data(
            array_merge($basepath, [get_string('privacy:path:assemblysummary', 'uckkassembly')]),
            $summary
        );
    }

    /**
     * Export records for one contribution area.
     *
     * @param array<int, string> $basepath Base export path.
     * @param array<string, mixed> $config Table config.
     * @param array<int, stdClass> $records Records.
     */
    private static function export_records(array $basepath, array $config, array $records): void {
        if (empty($records)) {
            return;
        }

        foreach ($records as $record) {
            $title = self::get_record_title($record, $config);
            $path = array_merge($basepath, [
                get_string($config['lang'], 'uckkassembly'),
                $title,
            ]);

            writer::with_context(context_module::instance((int)$record->contextid))->export_data(
                $path,
                self::transform_record($record, $config)
            );
        }
    }

    /**
     * Transform a DB record into export data.
     *
     * @param stdClass $record Record.
     * @param array<string, mixed> $config Config.
     * @return stdClass
     */
    private static function transform_record(stdClass $record, array $config): stdClass {
        $data = new stdClass();

        foreach ((array)$record as $field => $value) {
            if (in_array($field, ['id', 'assemblyid', 'courseid', 'cmid', 'contextid'], true)) {
                $data->{$field} = (int)$value;
                continue;
            }

            if (in_array($field, ['userid', 'createdby', 'modifiedby', 'publishedby'], true)) {
                $data->{$field} = (int)$value;
                continue;
            }

            if (in_array($field, ['timecreated', 'timemodified', 'timepublished', 'timeclosed'], true)) {
                $data->{$field} = !empty($value) ? transform::datetime((int)$value) : null;
                continue;
            }

            if ($field === 'metadata') {
                $data->{$field} = self::decode_metadata($value);
                continue;
            }

            if (is_string($value)) {
                $data->{$field} = s($value);
                continue;
            }

            $data->{$field} = $value;
        }

        foreach ($config['bodyfields'] as $bodyfield) {
            if (isset($record->{$bodyfield}) && is_string($record->{$bodyfield})) {
                $data->{$bodyfield} = format_text($record->{$bodyfield}, FORMAT_HTML);
            }
        }

        return $data;
    }

    /**
     * Get records belonging to a user for one contribution table.
     *
     * @param string $table Table.
     * @param array<string, mixed> $config Config.
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     * @return array<int, stdClass>
     */
    private static function get_user_records_for_table(string $table, array $config, int $assemblyid, int $userid): array {
        global $DB;

        $conditions = [];
        $params = [
            'assemblyid' => $assemblyid,
        ];

        foreach ($config['useridfields'] as $field) {
            $conditions[] = "{$field} = :{$field}";
            $params[$field] = $userid;
        }

        $sql = "SELECT *
                  FROM {{$table}}
                 WHERE assemblyid = :assemblyid
                   AND (" . implode(' OR ', $conditions) . ")
              ORDER BY timecreated ASC, id ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Add users from a user field in a contribution table.
     *
     * @param userlist $userlist User list.
     * @param string $table Table.
     * @param string $field User id field.
     * @param int $assemblyid Assembly id.
     */
    private static function add_users_from_table_field(userlist $userlist, string $table, string $field, int $assemblyid): void {
        $params = [
            'assemblyid' => $assemblyid,
        ];

        $sql = "SELECT DISTINCT {$field}
                  FROM {{$table}}
                 WHERE assemblyid = :assemblyid
                   AND {$field} IS NOT NULL
                   AND {$field} > 0";

        $userlist->add_from_sql($field, $sql, $params);
    }

    /**
     * Delete or anonymise all records for an assembly.
     *
     * Draft/private records are deleted. Institutional records are anonymised.
     *
     * @param string $table Table.
     * @param int $assemblyid Assembly id.
     */
    private static function delete_or_anonymise_records_for_assembly(string $table, int $assemblyid): void {
        global $DB;

        $records = $DB->get_records($table, ['assemblyid' => $assemblyid]);

        foreach ($records as $record) {
            if (self::record_can_be_deleted($record)) {
                $DB->delete_records($table, ['id' => $record->id]);
            } else {
                self::anonymise_record($table, $record);
            }
        }
    }

    /**
     * Delete or anonymise records for one user in an assembly.
     *
     * @param string $table Table.
     * @param array<string, mixed> $config Table config.
     * @param int $assemblyid Assembly id.
     * @param int $userid User id.
     */
    private static function delete_or_anonymise_user_records(string $table, array $config, int $assemblyid, int $userid): void {
        global $DB;

        $records = self::get_user_records_for_table($table, $config, $assemblyid, $userid);

        foreach ($records as $record) {
            if (self::record_can_be_deleted($record)) {
                $DB->delete_records($table, ['id' => $record->id]);
            } else {
                self::anonymise_user_fields($table, $record, $config['useridfields'], $userid);
            }
        }
    }

    /**
     * Determine whether a contribution can be deleted rather than anonymised.
     *
     * @param stdClass $record Record.
     * @return bool
     */
    private static function record_can_be_deleted(stdClass $record): bool {
        $status = (string)($record->status ?? '');
        $visibility = (string)($record->visibility ?? '');

        if ($status === 'draft') {
            return true;
        }

        if ($visibility === 'private' && in_array($status, ['', 'draft', 'submitted', 'pending_review'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Anonymise all user references on a record.
     *
     * @param string $table Table.
     * @param stdClass $record Record.
     */
    private static function anonymise_record(string $table, stdClass $record): void {
        global $DB;

        foreach (['userid', 'createdby', 'modifiedby', 'publishedby'] as $field) {
            if (property_exists($record, $field)) {
                $record->{$field} = 0;
            }
        }

        $record->timemodified = time();
        $record->metadata = self::append_privacy_redaction($record->metadata ?? null);

        $DB->update_record($table, $record);
    }

    /**
     * Anonymise specific user references on a record.
     *
     * @param string $table Table.
     * @param stdClass $record Record.
     * @param array<int, string> $fields User id fields.
     * @param int $userid User id to anonymise.
     */
    private static function anonymise_user_fields(string $table, stdClass $record, array $fields, int $userid): void {
        global $DB;

        $changed = false;

        foreach ($fields as $field) {
            if (property_exists($record, $field) && (int)$record->{$field} === $userid) {
                $record->{$field} = 0;
                $changed = true;
            }
        }

        if (!$changed) {
            return;
        }

        $record->timemodified = time();
        $record->metadata = self::append_privacy_redaction($record->metadata ?? null);

        $DB->update_record($table, $record);
    }

    /**
     * Add redaction marker to metadata JSON.
     *
     * @param mixed $metadata Current metadata.
     * @return string
     */
    private static function append_privacy_redaction(mixed $metadata): string {
        $decoded = self::decode_metadata($metadata);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        $decoded['privacy_redaction'] = [
            'redacted' => true,
            'redacted_at' => time(),
            'method' => 'hide_identity',
        ];

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode JSON metadata safely.
     *
     * @param mixed $metadata Metadata value.
     * @return mixed
     */
    private static function decode_metadata(mixed $metadata): mixed {
        if (!is_string($metadata) || trim($metadata) === '') {
            return null;
        }

        $decoded = json_decode($metadata, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Get assembly record.
     *
     * @param int $assemblyid Assembly id.
     * @return stdClass|null
     */
    private static function get_assembly(int $assemblyid): ?stdClass {
        global $DB;

        $sql = "SELECT a.*, cm.id AS cmid
                  FROM {" . self::ASSEMBLY_TABLE . "} a
                  JOIN {course_modules} cm ON cm.instance = a.id
                  JOIN {modules} md ON md.id = cm.module AND md.name = :modname
                 WHERE a.id = :assemblyid";

        return $DB->get_record_sql($sql, [
            'assemblyid' => $assemblyid,
            'modname' => 'uckkassembly',
        ]) ?: null;
    }

    /**
     * Get readable record title.
     *
     * @param stdClass $record Record.
     * @param array<string, mixed> $config Config.
     * @return string
     */
    private static function get_record_title(stdClass $record, array $config): string {
        $titlefield = (string)$config['titlefield'];

        if ($titlefield !== '' && !empty($record->{$titlefield})) {
            return format_string((string)$record->{$titlefield});
        }

        return get_string('privacy:path:record', 'uckkassembly', $record->id ?? '');
    }

    /**
     * Return the SQL alias used by get_contexts_for_userid.
     *
     * @param string $table Table name.
     * @return string
     */
    private static function alias_for_table(string $table): string {
        return match ($table) {
            'uckkassembly_motion' => 'motion',
            'uckkassembly_amend' => 'amend',
            'uckkassembly_object' => 'objectn',
            'uckkassembly_vote' => 'vote',
            'uckkassembly_decision' => 'decisionn',
            'uckkassembly_minutes' => 'minutesn',
            'uckkassembly_contest' => 'contest',
            default => clean_param($table, PARAM_ALPHANUMEXT),
        };
    }
}
```

Add these strings to `mod/uckkassembly/lang/en/uckkassembly.php`:

```php
$string['privacy:metadata:uckkassembly_motion'] = 'Stores motions proposed in an Assembly.';
$string['privacy:metadata:uckkassembly_motion:assemblyid'] = 'The Assembly linked to the motion.';
$string['privacy:metadata:uckkassembly_motion:userid'] = 'The user who proposed or owns the motion.';
$string['privacy:metadata:uckkassembly_motion:title'] = 'The motion title.';
$string['privacy:metadata:uckkassembly_motion:body'] = 'The motion body.';
$string['privacy:metadata:uckkassembly_motion:status'] = 'The motion status.';
$string['privacy:metadata:uckkassembly_motion:visibility'] = 'The motion visibility.';
$string['privacy:metadata:uckkassembly_motion:timecreated'] = 'The time the motion was created.';
$string['privacy:metadata:uckkassembly_motion:timemodified'] = 'The time the motion was modified.';

$string['privacy:metadata:uckkassembly_amend'] = 'Stores amendments proposed for Assembly motions.';
$string['privacy:metadata:uckkassembly_amend:assemblyid'] = 'The Assembly linked to the amendment.';
$string['privacy:metadata:uckkassembly_amend:motionid'] = 'The motion linked to the amendment.';
$string['privacy:metadata:uckkassembly_amend:userid'] = 'The user who proposed or owns the amendment.';
$string['privacy:metadata:uckkassembly_amend:body'] = 'The amendment body.';
$string['privacy:metadata:uckkassembly_amend:status'] = 'The amendment status.';
$string['privacy:metadata:uckkassembly_amend:visibility'] = 'The amendment visibility.';
$string['privacy:metadata:uckkassembly_amend:timecreated'] = 'The time the amendment was created.';
$string['privacy:metadata:uckkassembly_amend:timemodified'] = 'The time the amendment was modified.';

$string['privacy:metadata:uckkassembly_object'] = 'Stores objections raised in an Assembly.';
$string['privacy:metadata:uckkassembly_object:assemblyid'] = 'The Assembly linked to the objection.';
$string['privacy:metadata:uckkassembly_object:userid'] = 'The user who raised or owns the objection.';
$string['privacy:metadata:uckkassembly_object:targettype'] = 'The object type targeted by the objection.';
$string['privacy:metadata:uckkassembly_object:objectiontype'] = 'The objection type.';
$string['privacy:metadata:uckkassembly_object:title'] = 'The objection title.';
$string['privacy:metadata:uckkassembly_object:body'] = 'The objection body.';
$string['privacy:metadata:uckkassembly_object:status'] = 'The objection status.';
$string['privacy:metadata:uckkassembly_object:visibility'] = 'The objection visibility.';
$string['privacy:metadata:uckkassembly_object:timecreated'] = 'The time the objection was created.';
$string['privacy:metadata:uckkassembly_object:timemodified'] = 'The time the objection was modified.';

$string['privacy:metadata:uckkassembly_vote'] = 'Stores votes and readings in an Assembly.';
$string['privacy:metadata:uckkassembly_vote:assemblyid'] = 'The Assembly linked to the vote or reading.';
$string['privacy:metadata:uckkassembly_vote:motionid'] = 'The motion linked to the vote or reading.';
$string['privacy:metadata:uckkassembly_vote:userid'] = 'The user who voted or contributed the reading.';
$string['privacy:metadata:uckkassembly_vote:votevalue'] = 'The vote value.';
$string['privacy:metadata:uckkassembly_vote:readingtype'] = 'The reading type.';
$string['privacy:metadata:uckkassembly_vote:rationale'] = 'The optional rationale.';
$string['privacy:metadata:uckkassembly_vote:timecreated'] = 'The time the vote or reading was created.';
$string['privacy:metadata:uckkassembly_vote:timemodified'] = 'The time the vote or reading was modified.';

$string['privacy:metadata:uckkassembly_decision'] = 'Stores Assembly decisions and public summaries.';
$string['privacy:metadata:uckkassembly_decision:assemblyid'] = 'The Assembly linked to the decision.';
$string['privacy:metadata:uckkassembly_decision:userid'] = 'The user linked to the decision record.';
$string['privacy:metadata:uckkassembly_decision:publishedby'] = 'The user who published the decision.';
$string['privacy:metadata:uckkassembly_decision:title'] = 'The decision title.';
$string['privacy:metadata:uckkassembly_decision:body'] = 'The decision body.';
$string['privacy:metadata:uckkassembly_decision:publicsummary'] = 'The public decision summary.';
$string['privacy:metadata:uckkassembly_decision:status'] = 'The decision status.';
$string['privacy:metadata:uckkassembly_decision:visibility'] = 'The decision visibility.';
$string['privacy:metadata:uckkassembly_decision:timecreated'] = 'The time the decision was created.';
$string['privacy:metadata:uckkassembly_decision:timemodified'] = 'The time the decision was modified.';

$string['privacy:metadata:uckkassembly_minutes'] = 'Stores Assembly minutes and minute contributions.';
$string['privacy:metadata:uckkassembly_minutes:assemblyid'] = 'The Assembly linked to the minutes.';
$string['privacy:metadata:uckkassembly_minutes:userid'] = 'The user who authored or contributed to the minutes.';
$string['privacy:metadata:uckkassembly_minutes:title'] = 'The minutes title.';
$string['privacy:metadata:uckkassembly_minutes:body'] = 'The minutes body.';
$string['privacy:metadata:uckkassembly_minutes:status'] = 'The minutes status.';
$string['privacy:metadata:uckkassembly_minutes:visibility'] = 'The minutes visibility.';
$string['privacy:metadata:uckkassembly_minutes:timecreated'] = 'The time the minutes were created.';
$string['privacy:metadata:uckkassembly_minutes:timemodified'] = 'The time the minutes were modified.';

$string['privacy:metadata:uckkassembly_contest'] = 'Stores contestations of Assembly decisions or process.';
$string['privacy:metadata:uckkassembly_contest:assemblyid'] = 'The Assembly linked to the contestation.';
$string['privacy:metadata:uckkassembly_contest:userid'] = 'The user who opened or owns the contestation.';
$string['privacy:metadata:uckkassembly_contest:title'] = 'The contestation title.';
$string['privacy:metadata:uckkassembly_contest:body'] = 'The contestation body.';
$string['privacy:metadata:uckkassembly_contest:status'] = 'The contestation status.';
$string['privacy:metadata:uckkassembly_contest:visibility'] = 'The contestation visibility.';
$string['privacy:metadata:uckkassembly_contest:timecreated'] = 'The time the contestation was created.';
$string['privacy:metadata:uckkassembly_contest:timemodified'] = 'The time the contestation was modified.';

$string['privacy:path:assemblysummary'] = 'Assembly summary';
$string['privacy:path:motions'] = 'Motions';
$string['privacy:path:amendments'] = 'Amendments';
$string['privacy:path:objections'] = 'Objections';
$string['privacy:path:votes'] = 'Votes and readings';
$string['privacy:path:decisions'] = 'Decisions';
$string['privacy:path:minutes'] = 'Minutes';
$string['privacy:path:contestations'] = 'Contestations';
$string['privacy:path:record'] = 'Record {$a}';
```

Add these strings to `mod/uckkassembly/lang/fr/uckkassembly.php`:

```php
$string['privacy:metadata:uckkassembly_motion'] = 'Stocke les motions proposées dans une Assemblée.';
$string['privacy:metadata:uckkassembly_motion:assemblyid'] = 'L’Assemblée liée à la motion.';
$string['privacy:metadata:uckkassembly_motion:userid'] = 'L’utilisateur qui a proposé ou possède la motion.';
$string['privacy:metadata:uckkassembly_motion:title'] = 'Le titre de la motion.';
$string['privacy:metadata:uckkassembly_motion:body'] = 'Le contenu de la motion.';
$string['privacy:metadata:uckkassembly_motion:status'] = 'Le statut de la motion.';
$string['privacy:metadata:uckkassembly_motion:visibility'] = 'La visibilité de la motion.';
$string['privacy:metadata:uckkassembly_motion:timecreated'] = 'Le moment de création de la motion.';
$string['privacy:metadata:uckkassembly_motion:timemodified'] = 'Le moment de modification de la motion.';

$string['privacy:metadata:uckkassembly_amend'] = 'Stocke les amendements proposés aux motions d’Assemblée.';
$string['privacy:metadata:uckkassembly_amend:assemblyid'] = 'L’Assemblée liée à l’amendement.';
$string['privacy:metadata:uckkassembly_amend:motionid'] = 'La motion liée à l’amendement.';
$string['privacy:metadata:uckkassembly_amend:userid'] = 'L’utilisateur qui a proposé ou possède l’amendement.';
$string['privacy:metadata:uckkassembly_amend:body'] = 'Le contenu de l’amendement.';
$string['privacy:metadata:uckkassembly_amend:status'] = 'Le statut de l’amendement.';
$string['privacy:metadata:uckkassembly_amend:visibility'] = 'La visibilité de l’amendement.';
$string['privacy:metadata:uckkassembly_amend:timecreated'] = 'Le moment de création de l’amendement.';
$string['privacy:metadata:uckkassembly_amend:timemodified'] = 'Le moment de modification de l’amendement.';

$string['privacy:metadata:uckkassembly_object'] = 'Stocke les objections formulées dans une Assemblée.';
$string['privacy:metadata:uckkassembly_object:assemblyid'] = 'L’Assemblée liée à l’objection.';
$string['privacy:metadata:uckkassembly_object:userid'] = 'L’utilisateur qui a formulé ou possède l’objection.';
$string['privacy:metadata:uckkassembly_object:targettype'] = 'Le type d’objet visé par l’objection.';
$string['privacy:metadata:uckkassembly_object:objectiontype'] = 'Le type d’objection.';
$string['privacy:metadata:uckkassembly_object:title'] = 'Le titre de l’objection.';
$string['privacy:metadata:uckkassembly_object:body'] = 'Le contenu de l’objection.';
$string['privacy:metadata:uckkassembly_object:status'] = 'Le statut de l’objection.';
$string['privacy:metadata:uckkassembly_object:visibility'] = 'La visibilité de l’objection.';
$string['privacy:metadata:uckkassembly_object:timecreated'] = 'Le moment de création de l’objection.';
$string['privacy:metadata:uckkassembly_object:timemodified'] = 'Le moment de modification de l’objection.';

$string['privacy:metadata:uckkassembly_vote'] = 'Stocke les votes et lectures dans une Assemblée.';
$string['privacy:metadata:uckkassembly_vote:assemblyid'] = 'L’Assemblée liée au vote ou à la lecture.';
$string['privacy:metadata:uckkassembly_vote:motionid'] = 'La motion liée au vote ou à la lecture.';
$string['privacy:metadata:uckkassembly_vote:userid'] = 'L’utilisateur qui a voté ou contribué à la lecture.';
$string['privacy:metadata:uckkassembly_vote:votevalue'] = 'La valeur du vote.';
$string['privacy:metadata:uckkassembly_vote:readingtype'] = 'Le type de lecture.';
$string['privacy:metadata:uckkassembly_vote:rationale'] = 'La justification optionnelle.';
$string['privacy:metadata:uckkassembly_vote:timecreated'] = 'Le moment de création du vote ou de la lecture.';
$string['privacy:metadata:uckkassembly_vote:timemodified'] = 'Le moment de modification du vote ou de la lecture.';

$string['privacy:metadata:uckkassembly_decision'] = 'Stocke les décisions d’Assemblée et leurs synthèses publiques.';
$string['privacy:metadata:uckkassembly_decision:assemblyid'] = 'L’Assemblée liée à la décision.';
$string['privacy:metadata:uckkassembly_decision:userid'] = 'L’utilisateur lié à la décision.';
$string['privacy:metadata:uckkassembly_decision:publishedby'] = 'L’utilisateur qui a publié la décision.';
$string['privacy:metadata:uckkassembly_decision:title'] = 'Le titre de la décision.';
$string['privacy:metadata:uckkassembly_decision:body'] = 'Le contenu de la décision.';
$string['privacy:metadata:uckkassembly_decision:publicsummary'] = 'La synthèse publique de la décision.';
$string['privacy:metadata:uckkassembly_decision:status'] = 'Le statut de la décision.';
$string['privacy:metadata:uckkassembly_decision:visibility'] = 'La visibilité de la décision.';
$string['privacy:metadata:uckkassembly_decision:timecreated'] = 'Le moment de création de la décision.';
$string['privacy:metadata:uckkassembly_decision:timemodified'] = 'Le moment de modification de la décision.';

$string['privacy:metadata:uckkassembly_minutes'] = 'Stocke les procès-verbaux et contributions aux procès-verbaux.';
$string['privacy:metadata:uckkassembly_minutes:assemblyid'] = 'L’Assemblée liée au procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:userid'] = 'L’utilisateur qui a rédigé ou contribué au procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:title'] = 'Le titre du procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:body'] = 'Le contenu du procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:status'] = 'Le statut du procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:visibility'] = 'La visibilité du procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:timecreated'] = 'Le moment de création du procès-verbal.';
$string['privacy:metadata:uckkassembly_minutes:timemodified'] = 'Le moment de modification du procès-verbal.';

$string['privacy:metadata:uckkassembly_contest'] = 'Stocke les contestations de décisions ou de procédures d’Assemblée.';
$string['privacy:metadata:uckkassembly_contest:assemblyid'] = 'L’Assemblée liée à la contestation.';
$string['privacy:metadata:uckkassembly_contest:userid'] = 'L’utilisateur qui a ouvert ou possède la contestation.';
$string['privacy:metadata:uckkassembly_contest:title'] = 'Le titre de la contestation.';
$string['privacy:metadata:uckkassembly_contest:body'] = 'Le contenu de la contestation.';
$string['privacy:metadata:uckkassembly_contest:status'] = 'Le statut de la contestation.';
$string['privacy:metadata:uckkassembly_contest:visibility'] = 'La visibilité de la contestation.';
$string['privacy:metadata:uckkassembly_contest:timecreated'] = 'Le moment de création de la contestation.';
$string['privacy:metadata:uckkassembly_contest:timemodified'] = 'Le moment de modification de la contestation.';

$string['privacy:path:assemblysummary'] = 'Résumé de l’Assemblée';
$string['privacy:path:motions'] = 'Motions';
$string['privacy:path:amendments'] = 'Amendements';
$string['privacy:path:objections'] = 'Objections';
$string['privacy:path:votes'] = 'Votes et lectures';
$string['privacy:path:decisions'] = 'Décisions';
$string['privacy:path:minutes'] = 'Procès-verbaux';
$string['privacy:path:contestations'] = 'Contestations';
$string['privacy:path:record'] = 'Enregistrement {$a}';
```
