<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Privacy provider for the UCKK Archive activity.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;
use xmldb_field;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_uckkarchive.
 *
 * Archive records can be institutional memory, but they can also contain
 * personal data: user-authored evidence, portfolio items, Kristals, validation
 * notes, provenance statements, revisions, and export logs.
 *
 * Deletion methods avoid silent archive erasure where possible by anonymising
 * user references and redacting user-authored fields. Full context deletion
 * removes archive child records and files inside that context.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Plugin component name.
     */
    private const COMPONENT = 'mod_uckkarchive';

    /**
     * Plugin table map.
     */
    private const TABLES = [
        'uckkarchive' => [
            'userfields' => ['userid', 'createdby', 'modifiedby'],
            'textfields' => ['name', 'intro', 'archivecontext', 'archivepurpose', 'metadata'],
            'metadata' => 'privacy:metadata:uckkarchive',
        ],
        'uckkarchive_item' => [
            'userfields' => ['userid', 'createdby', 'modifiedby', 'validatedby'],
            'textfields' => [
                'title',
                'summary',
                'content',
                'description',
                'notes',
                'sourceauthor',
                'sourceurl',
                'license',
                'tags',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_item',
        ],
        'uckkarchive_kristal' => [
            'userfields' => ['userid', 'createdby', 'modifiedby', 'validatedby'],
            'textfields' => [
                'title',
                'summary',
                'statement',
                'content',
                'contexttext',
                'proof',
                'notes',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_kristal',
        ],
        'uckkarchive_proof' => [
            'userfields' => ['userid', 'createdby', 'modifiedby', 'validatedby'],
            'textfields' => [
                'title',
                'summary',
                'content',
                'description',
                'sourceauthor',
                'sourceurl',
                'notes',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_proof',
        ],
        'uckkarchive_prov' => [
            'userfields' => ['userid', 'createdby', 'modifiedby', 'validatedby'],
            'textfields' => [
                'statement',
                'source',
                'sourceauthor',
                'sourceurl',
                'notes',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_prov',
        ],
        'uckkarchive_rev' => [
            'userfields' => ['userid', 'createdby', 'modifiedby'],
            'textfields' => [
                'reason',
                'notes',
                'summary',
                'contentbefore',
                'contentafter',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_rev',
        ],
        'uckkarchive_export' => [
            'userfields' => ['userid', 'createdby', 'modifiedby', 'exportedby'],
            'textfields' => [
                'title',
                'summary',
                'reason',
                'notes',
                'exportpath',
                'metadata',
                'provenancehash',
            ],
            'metadata' => 'privacy:metadata:uckkarchive_export',
        ],
    ];

    /**
     * File areas that can hold user content.
     */
    private const FILE_AREAS = [
        'item_content',
        'proof_files',
        'decision_attachments',
        'minutes_files',
        'kristal_files',
        'portfolio_files',
        'integrity_exports',
        'export_packages',
    ];

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $commonfields = [
            'id' => 'privacy:metadata:common:id',
            'courseid' => 'privacy:metadata:common:courseid',
            'cmid' => 'privacy:metadata:common:cmid',
            'contextid' => 'privacy:metadata:common:contextid',
            'userid' => 'privacy:metadata:common:userid',
            'createdby' => 'privacy:metadata:common:createdby',
            'modifiedby' => 'privacy:metadata:common:modifiedby',
            'timecreated' => 'privacy:metadata:common:timecreated',
            'timemodified' => 'privacy:metadata:common:timemodified',
            'status' => 'privacy:metadata:common:status',
            'visibility' => 'privacy:metadata:common:visibility',
            'metadata' => 'privacy:metadata:common:metadata',
            'provenancehash' => 'privacy:metadata:common:provenancehash',
        ];

        $collection->add_database_table('uckkarchive', $commonfields + [
            'name' => 'privacy:metadata:uckkarchive:name',
            'intro' => 'privacy:metadata:uckkarchive:intro',
        ], 'privacy:metadata:uckkarchive');

        $collection->add_database_table('uckkarchive_item', $commonfields + [
            'title' => 'privacy:metadata:uckkarchive_item:title',
            'summary' => 'privacy:metadata:uckkarchive_item:summary',
            'content' => 'privacy:metadata:uckkarchive_item:content',
            'itemtype' => 'privacy:metadata:uckkarchive_item:itemtype',
            'sourcecomponent' => 'privacy:metadata:uckkarchive_item:sourcecomponent',
            'sourceid' => 'privacy:metadata:uckkarchive_item:sourceid',
            'sourceurl' => 'privacy:metadata:uckkarchive_item:sourceurl',
            'sourceauthor' => 'privacy:metadata:uckkarchive_item:sourceauthor',
            'sourcedate' => 'privacy:metadata:uckkarchive_item:sourcedate',
        ], 'privacy:metadata:uckkarchive_item');

        $collection->add_database_table('uckkarchive_kristal', $commonfields + [
            'title' => 'privacy:metadata:uckkarchive_kristal:title',
            'statement' => 'privacy:metadata:uckkarchive_kristal:statement',
            'contexttext' => 'privacy:metadata:uckkarchive_kristal:contexttext',
            'proof' => 'privacy:metadata:uckkarchive_kristal:proof',
        ], 'privacy:metadata:uckkarchive_kristal');

        $collection->add_database_table('uckkarchive_proof', $commonfields + [
            'archiveitemid' => 'privacy:metadata:uckkarchive_proof:archiveitemid',
            'prooftype' => 'privacy:metadata:uckkarchive_proof:prooftype',
            'content' => 'privacy:metadata:uckkarchive_proof:content',
            'sourceurl' => 'privacy:metadata:uckkarchive_proof:sourceurl',
        ], 'privacy:metadata:uckkarchive_proof');

        $collection->add_database_table('uckkarchive_prov', $commonfields + [
            'archiveitemid' => 'privacy:metadata:uckkarchive_prov:archiveitemid',
            'statement' => 'privacy:metadata:uckkarchive_prov:statement',
            'source' => 'privacy:metadata:uckkarchive_prov:source',
            'sourceurl' => 'privacy:metadata:uckkarchive_prov:sourceurl',
        ], 'privacy:metadata:uckkarchive_prov');

        $collection->add_database_table('uckkarchive_rev', $commonfields + [
            'archiveitemid' => 'privacy:metadata:uckkarchive_rev:archiveitemid',
            'reason' => 'privacy:metadata:uckkarchive_rev:reason',
            'notes' => 'privacy:metadata:uckkarchive_rev:notes',
            'contentbefore' => 'privacy:metadata:uckkarchive_rev:contentbefore',
            'contentafter' => 'privacy:metadata:uckkarchive_rev:contentafter',
        ], 'privacy:metadata:uckkarchive_rev');

        $collection->add_database_table('uckkarchive_export', $commonfields + [
            'exportformat' => 'privacy:metadata:uckkarchive_export:exportformat',
            'exportscope' => 'privacy:metadata:uckkarchive_export:exportscope',
            'reason' => 'privacy:metadata:uckkarchive_export:reason',
            'exportpath' => 'privacy:metadata:uckkarchive_export:exportpath',
        ], 'privacy:metadata:uckkarchive_export');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:files');

        return $collection;
    }

    /**
     * Get contexts containing personal data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        foreach (self::TABLES as $table => $config) {
            if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
                continue;
            }

            $conditions = [];
            $params = [];

            foreach ($config['userfields'] as $field) {
                if (!self::field_exists($table, $field)) {
                    continue;
                }

                $param = $table . '_' . $field;
                $conditions[] = "t.{$field} = :{$param}";
                $params[$param] = $userid;
            }

            if (!$conditions) {
                continue;
            }

            $sql = 'SELECT DISTINCT ctx.id
                      FROM {context} ctx
                      JOIN {' . $table . '} t ON t.contextid = ctx.id
                     WHERE ' . implode(' OR ', $conditions);

            $contextlist->add_from_sql($sql, $params);
        }

        return $contextlist;
    }

    /**
     * Export personal data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            $export = new stdClass();
            $export->archives = [];

            foreach (array_keys(self::TABLES) as $table) {
                $records = self::get_user_records_in_context($table, (int)$user->id, $context);

                if (!$records) {
                    continue;
                }

                $export->{$table} = array_map([self::class, 'prepare_record_for_export'], $records);
            }

            $subcontext = [get_string('privacy:path:archives', 'uckkarchive')];

            writer::with_context($context)->export_data($subcontext, $export);
            self::export_user_files($context, (int)$user->id, $subcontext);
        }
    }

    /**
     * Delete all personal archive data in a context.
     *
     * This is used when the context itself is being expired/deleted. It removes
     * child archive records and file areas in the context, while preserving the
     * activity instance shell where Moodle owns that lifecycle.
     *
     * @param context $context Context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        self::delete_all_archive_files_in_context($context);

        foreach (array_keys(self::TABLES) as $table) {
            if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
                continue;
            }

            if ($table === 'uckkarchive') {
                self::anonymise_all_user_references_in_context($table, $context);
                continue;
            }

            $DB->delete_records_select($table, 'contextid = :contextid', [
                'contextid' => $context->id,
            ]);
        }
    }

    /**
     * Delete personal data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            self::delete_user_data_in_context((int)$user->id, $context);
        }
    }

    /**
     * Add users with archive personal data in a context.
     *
     * @param userlist $userlist Userlist.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        foreach (self::TABLES as $table => $config) {
            if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
                continue;
            }

            foreach ($config['userfields'] as $field) {
                if (!self::field_exists($table, $field)) {
                    continue;
                }

                $sql = "SELECT {$field}
                          FROM {{$table}}
                         WHERE contextid = :contextid
                           AND {$field} <> 0";

                $userlist->add_from_sql($field, $sql, [
                    'contextid' => $context->id,
                ]);
            }
        }
    }

    /**
     * Delete personal data for multiple users in a context.
     *
     * @param approved_userlist $userlist Approved userlist.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();

        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_data_in_context((int)$userid, $context);
        }
    }

    /**
     * Export files attached to user-owned records.
     *
     * @param context $context Context.
     * @param int $userid User id.
     * @param array<int, string> $subcontext Subcontext.
     */
    private static function export_user_files(context $context, int $userid, array $subcontext): void {
        foreach (['uckkarchive_item', 'uckkarchive_kristal', 'uckkarchive_proof', 'uckkarchive_export'] as $table) {
            $records = self::get_user_records_in_context($table, $userid, $context);

            foreach ($records as $record) {
                if (empty($record->id)) {
                    continue;
                }

                foreach (self::get_file_areas_for_table($table) as $filearea) {
                    writer::with_context($context)->export_area_files(
                        array_merge($subcontext, [$table, (string)$record->id, $filearea]),
                        self::COMPONENT,
                        $filearea,
                        (int)$record->id
                    );
                }
            }
        }
    }

    /**
     * Delete or anonymise one user's archive data in a context.
     *
     * @param int $userid User id.
     * @param context $context Context.
     */
    private static function delete_user_data_in_context(int $userid, context $context): void {
        global $DB;

        foreach (self::TABLES as $table => $config) {
            if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
                continue;
            }

            [$select, $params] = self::get_user_select_for_context($table, $config['userfields'], $userid, $context);

            if ($select === '') {
                continue;
            }

            $records = $DB->get_records_select($table, $select, $params);

            if (!$records) {
                continue;
            }

            self::delete_files_for_records($context, $table, $records);
            self::anonymise_records($table, $config, $select, $params);
        }
    }

    /**
     * Build SQL selection for one user in one context.
     *
     * @param string $table Table name.
     * @param array<int, string> $userfields User id fields.
     * @param int $userid User id.
     * @param context $context Context.
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function get_user_select_for_context(
        string $table,
        array $userfields,
        int $userid,
        context $context
    ): array {
        $conditions = [];
        $params = [
            'contextid' => $context->id,
        ];

        foreach ($userfields as $field) {
            if (!self::field_exists($table, $field)) {
                continue;
            }

            $param = 'userid_' . $field;
            $conditions[] = "{$field} = :{$param}";
            $params[$param] = $userid;
        }

        if (!$conditions) {
            return ['', []];
        }

        return [
            'contextid = :contextid AND (' . implode(' OR ', $conditions) . ')',
            $params,
        ];
    }

    /**
     * Get user-owned records in a context.
     *
     * @param string $table Table name.
     * @param int $userid User id.
     * @param context $context Context.
     * @return array<int, stdClass>
     */
    private static function get_user_records_in_context(string $table, int $userid, context $context): array {
        global $DB;

        if (!self::table_exists($table) || !self::field_exists($table, 'contextid')) {
            return [];
        }

        $config = self::TABLES[$table] ?? null;

        if ($config === null) {
            return [];
        }

        [$select, $params] = self::get_user_select_for_context($table, $config['userfields'], $userid, $context);

        if ($select === '') {
            return [];
        }

        return $DB->get_records_select($table, $select, $params, 'id ASC');
    }

    /**
     * Prepare a record for privacy export.
     *
     * @param stdClass $record Record.
     * @return stdClass
     */
    private static function prepare_record_for_export(stdClass $record): stdClass {
        $data = clone $record;

        foreach (['timecreated', 'timemodified', 'timevalidated', 'sourcedate'] as $field) {
            if (!empty($data->{$field})) {
                $data->{$field . 'readable'} = userdate((int)$data->{$field});
            }
        }

        return $data;
    }

    /**
     * Anonymise records matched by a select statement.
     *
     * @param string $table Table name.
     * @param array<string, mixed> $config Table config.
     * @param string $select SQL select.
     * @param array<string, mixed> $params SQL params.
     */
    private static function anonymise_records(string $table, array $config, string $select, array $params): void {
        global $DB;

        foreach ($config['userfields'] as $field) {
            if (self::field_exists($table, $field)) {
                $DB->set_field_select($table, $field, 0, $select, $params);
            }
        }

        foreach ($config['textfields'] as $field) {
            if (!self::field_exists($table, $field)) {
                continue;
            }

            $value = $field === 'metadata'
                ? null
                : get_string('privacy:deleted', 'uckkarchive');

            $DB->set_field_select($table, $field, $value, $select, $params);
        }

        if (self::field_exists($table, 'timemodified')) {
            $DB->set_field_select($table, 'timemodified', time(), $select, $params);
        }
    }

    /**
     * Anonymise all user references in an instance record for a context.
     *
     * @param string $table Table name.
     * @param context $context Context.
     */
    private static function anonymise_all_user_references_in_context(string $table, context $context): void {
        global $DB;

        if (!isset(self::TABLES[$table])) {
            return;
        }

        $select = 'contextid = :contextid';
        $params = ['contextid' => $context->id];

        foreach (self::TABLES[$table]['userfields'] as $field) {
            if (self::field_exists($table, $field)) {
                $DB->set_field_select($table, $field, 0, $select, $params);
            }
        }

        if (self::field_exists($table, 'timemodified')) {
            $DB->set_field_select($table, 'timemodified', time(), $select, $params);
        }
    }

    /**
     * Delete files for matching records.
     *
     * @param context $context Context.
     * @param string $table Table name.
     * @param array<int, stdClass> $records Records.
     */
    private static function delete_files_for_records(context $context, string $table, array $records): void {
        $fs = get_file_storage();

        foreach ($records as $record) {
            if (empty($record->id)) {
                continue;
            }

            foreach (self::get_file_areas_for_table($table) as $filearea) {
                $fs->delete_area_files($context->id, self::COMPONENT, $filearea, (int)$record->id);
            }
        }
    }

    /**
     * Delete all archive files in a context.
     *
     * @param context $context Context.
     */
    private static function delete_all_archive_files_in_context(context $context): void {
        $fs = get_file_storage();

        foreach (self::FILE_AREAS as $filearea) {
            $fs->delete_area_files($context->id, self::COMPONENT, $filearea);
        }
    }

    /**
     * Get file areas associated with a table.
     *
     * @param string $table Table name.
     * @return array<int, string>
     */
    private static function get_file_areas_for_table(string $table): array {
        return match ($table) {
            'uckkarchive_item' => [
                'item_content',
                'proof_files',
                'decision_attachments',
                'minutes_files',
                'portfolio_files',
            ],
            'uckkarchive_kristal' => [
                'kristal_files',
            ],
            'uckkarchive_proof' => [
                'proof_files',
            ],
            'uckkarchive_export' => [
                'export_packages',
                'integrity_exports',
            ],
            default => [],
        };
    }

    /**
     * Check whether a database table exists.
     *
     * @param string $table Table name without prefix.
     * @return bool
     */
    private static function table_exists(string $table): bool {
        global $DB;

        return $DB->get_manager()->table_exists(new xmldb_table($table));
    }

    /**
     * Check whether a database field exists.
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

        return $DB->get_manager()->field_exists(new xmldb_table($table), new xmldb_field($field));
    }
}
```

Add these extra language strings to both `lang/en/uckkarchive.php` and `lang/fr/uckkarchive.php` if they are not already present.

English:

```php id="ejpl1v"
$string['privacy:deleted'] = '[Deleted personal data]';

$string['privacy:metadata:common:id'] = 'Record ID.';
$string['privacy:metadata:common:courseid'] = 'Related course ID.';
$string['privacy:metadata:common:cmid'] = 'Related course module ID.';
$string['privacy:metadata:common:contextid'] = 'Related Moodle context ID.';
$string['privacy:metadata:common:userid'] = 'User associated with the record.';
$string['privacy:metadata:common:createdby'] = 'User who created the record.';
$string['privacy:metadata:common:modifiedby'] = 'User who last modified the record.';
$string['privacy:metadata:common:timecreated'] = 'Time when the record was created.';
$string['privacy:metadata:common:timemodified'] = 'Time when the record was last modified.';
$string['privacy:metadata:common:status'] = 'Workflow status of the record.';
$string['privacy:metadata:common:visibility'] = 'Visibility level of the record.';
$string['privacy:metadata:common:metadata'] = 'Additional JSON metadata.';
$string['privacy:metadata:common:provenancehash'] = 'Optional provenance hash.';

$string['privacy:metadata:uckkarchive:name'] = 'Archive activity name.';
$string['privacy:metadata:uckkarchive:intro'] = 'Archive activity introduction.';

$string['privacy:metadata:uckkarchive_item:itemtype'] = 'Archive item type.';
$string['privacy:metadata:uckkarchive_item:sourcecomponent'] = 'Component that originated the archive item.';
$string['privacy:metadata:uckkarchive_item:sourceid'] = 'Source record ID.';
$string['privacy:metadata:uckkarchive_item:sourceurl'] = 'Source URL.';
$string['privacy:metadata:uckkarchive_item:sourceauthor'] = 'Source or author.';
$string['privacy:metadata:uckkarchive_item:sourcedate'] = 'Source date.';

$string['privacy:metadata:uckkarchive_kristal:title'] = 'Kristal title.';
$string['privacy:metadata:uckkarchive_kristal:statement'] = 'Kristal statement.';
$string['privacy:metadata:uckkarchive_kristal:contexttext'] = 'Kristal context.';
$string['privacy:metadata:uckkarchive_kristal:proof'] = 'Kristal proof.';

$string['privacy:metadata:uckkarchive_proof:archiveitemid'] = 'Related archive item ID.';
$string['privacy:metadata:uckkarchive_proof:prooftype'] = 'Proof type.';
$string['privacy:metadata:uckkarchive_proof:content'] = 'Proof content.';
$string['privacy:metadata:uckkarchive_proof:sourceurl'] = 'Proof source URL.';

$string['privacy:metadata:uckkarchive_prov:archiveitemid'] = 'Related archive item ID.';
$string['privacy:metadata:uckkarchive_prov:statement'] = 'Provenance statement.';
$string['privacy:metadata:uckkarchive_prov:source'] = 'Provenance source.';
$string['privacy:metadata:uckkarchive_prov:sourceurl'] = 'Provenance source URL.';

$string['privacy:metadata:uckkarchive_rev:archiveitemid'] = 'Related archive item ID.';
$string['privacy:metadata:uckkarchive_rev:reason'] = 'Revision reason.';
$string['privacy:metadata:uckkarchive_rev:notes'] = 'Revision notes.';
$string['privacy:metadata:uckkarchive_rev:contentbefore'] = 'Content before revision.';
$string['privacy:metadata:uckkarchive_rev:contentafter'] = 'Content after revision.';

$string['privacy:metadata:uckkarchive_export:exportformat'] = 'Export format.';
$string['privacy:metadata:uckkarchive_export:exportscope'] = 'Export scope.';
$string['privacy:metadata:uckkarchive_export:reason'] = 'Export reason.';
$string['privacy:metadata:uckkarchive_export:exportpath'] = 'Export file path.';
```

French:

```php id="hg6zt1"
$string['privacy:deleted'] = '[Données personnelles supprimées]';

$string['privacy:metadata:common:id'] = 'ID de la trace.';
$string['privacy:metadata:common:courseid'] = 'ID du cours lié.';
$string['privacy:metadata:common:cmid'] = 'ID du module de cours lié.';
$string['privacy:metadata:common:contextid'] = 'ID du contexte Moodle lié.';
$string['privacy:metadata:common:userid'] = 'Utilisateur associé à la trace.';
$string['privacy:metadata:common:createdby'] = 'Utilisateur ayant créé la trace.';
$string['privacy:metadata:common:modifiedby'] = 'Utilisateur ayant modifié la trace en dernier.';
$string['privacy:metadata:common:timecreated'] = 'Moment de création de la trace.';
$string['privacy:metadata:common:timemodified'] = 'Moment de dernière modification de la trace.';
$string['privacy:metadata:common:status'] = 'Statut de workflow de la trace.';
$string['privacy:metadata:common:visibility'] = 'Niveau de visibilité de la trace.';
$string['privacy:metadata:common:metadata'] = 'Métadonnées JSON supplémentaires.';
$string['privacy:metadata:common:provenancehash'] = 'Hash de provenance optionnel.';

$string['privacy:metadata:uckkarchive:name'] = 'Nom de l’activité Archive.';
$string['privacy:metadata:uckkarchive:intro'] = 'Introduction de l’activité Archive.';

$string['privacy:metadata:uckkarchive_item:itemtype'] = 'Type d’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:sourcecomponent'] = 'Composant ayant généré l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:sourceid'] = 'ID de la trace source.';
$string['privacy:metadata:uckkarchive_item:sourceurl'] = 'URL source.';
$string['privacy:metadata:uckkarchive_item:sourceauthor'] = 'Source ou auteur.';
$string['privacy:metadata:uckkarchive_item:sourcedate'] = 'Date de la source.';

$string['privacy:metadata:uckkarchive_kristal:title'] = 'Titre du Kristal.';
$string['privacy:metadata:uckkarchive_kristal:statement'] = 'Énoncé du Kristal.';
$string['privacy:metadata:uckkarchive_kristal:contexttext'] = 'Contexte du Kristal.';
$string['privacy:metadata:uckkarchive_kristal:proof'] = 'Preuve du Kristal.';

$string['privacy:metadata:uckkarchive_proof:archiveitemid'] = 'ID de l’élément d’archive lié.';
$string['privacy:metadata:uckkarchive_proof:prooftype'] = 'Type de preuve.';
$string['privacy:metadata:uckkarchive_proof:content'] = 'Contenu de preuve.';
$string['privacy:metadata:uckkarchive_proof:sourceurl'] = 'URL source de la preuve.';

$string['privacy:metadata:uckkarchive_prov:archiveitemid'] = 'ID de l’élément d’archive lié.';
$string['privacy:metadata:uckkarchive_prov:statement'] = 'Déclaration de provenance.';
$string['privacy:metadata:uckkarchive_prov:source'] = 'Source de provenance.';
$string['privacy:metadata:uckkarchive_prov:sourceurl'] = 'URL source de provenance.';

$string['privacy:metadata:uckkarchive_rev:archiveitemid'] = 'ID de l’élément d’archive lié.';
$string['privacy:metadata:uckkarchive_rev:reason'] = 'Raison de la révision.';
$string['privacy:metadata:uckkarchive_rev:notes'] = 'Notes de révision.';
$string['privacy:metadata:uckkarchive_rev:contentbefore'] = 'Contenu avant révision.';
$string['privacy:metadata:uckkarchive_rev:contentafter'] = 'Contenu après révision.';

$string['privacy:metadata:uckkarchive_export:exportformat'] = 'Format d’export.';
$string['privacy:metadata:uckkarchive_export:exportscope'] = 'Portée de l’export.';
$string['privacy:metadata:uckkarchive_export:reason'] = 'Raison de l’export.';
$string['privacy:metadata:uckkarchive_export:exportpath'] = 'Chemin du fichier d’export.';

