<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Upgrade steps for the UCKK Archive activity module.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute upgrade steps for mod_uckkarchive.
 *
 * Clean installs use db/install.xml. This file is only used when Moodle detects
 * an already-installed older version of the plugin.
 *
 * Important:
 * - Do not call mod_uckkarchive service classes from here.
 * - Do not render output from here.
 * - Do not run archive validation, export generation, privacy deletion, or
 *   integrity workflows from here.
 * - Use XMLDB/database manager operations for schema changes.
 * - Add one version-gated block per future schema/data migration.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_uckkarchive_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051200) {
        // Initial stable release baseline.
        //
        // The complete initial schema is defined in db/install.xml and is used
        // for clean installs. This savepoint exists for development or
        // pre-release sites that may already have an earlier plugin version
        // recorded in Moodle before the stable 1.0.0 release.
        //
        // If a pre-release database is missing a final table or field, keep the
        // repair helpers below available for future targeted migrations, but do
        // not duplicate the whole install.xml schema here.

        uckkarchive_upgrade_repair_initial_indexes();

        upgrade_mod_savepoint(true, 2026051200, 'uckkarchive');
    }

    return true;
}

/**
 * Repair indexes expected by the initial archive schema when upgrading from an
 * early development snapshot.
 *
 * This function is intentionally conservative. It only adds indexes when the
 * target table and target fields already exist. Fresh installs are handled by
 * db/install.xml.
 *
 * @return void
 */
function uckkarchive_upgrade_repair_initial_indexes(): void {
    global $DB;

    $dbman = $DB->get_manager();

    $indexes = [
        'uckkarchive_item' => [
            ['uckkarchive_item_archiveid_ix', ['archiveid']],
            ['uckkarchive_item_status_ix', ['status']],
            ['uckkarchive_item_visibility_ix', ['visibility']],
            ['uckkarchive_item_source_ix', ['sourcecomponent', 'sourceid']],
            ['uckkarchive_item_user_ix', ['userid']],
            ['uckkarchive_item_createdby_ix', ['createdby']],
        ],
        'uckkarchive_kristal' => [
            ['uckkarchive_kristal_archiveid_ix', ['archiveid']],
            ['uckkarchive_kristal_itemid_ix', ['itemid']],
            ['uckkarchive_kristal_proofid_ix', ['proofid']],
            ['uckkarchive_kristal_status_ix', ['status']],
            ['uckkarchive_kristal_visibility_ix', ['visibility']],
            ['uckkarchive_kristal_validation_ix', ['validationstate']],
            ['uckkarchive_kristal_source_ix', ['sourcecomponent', 'sourceid']],
        ],
        'uckkarchive_proof' => [
            ['uckkarchive_proof_archiveid_ix', ['archiveid']],
            ['uckkarchive_proof_itemid_ix', ['itemid']],
            ['uckkarchive_proof_status_ix', ['status']],
            ['uckkarchive_proof_visibility_ix', ['visibility']],
            ['uckkarchive_proof_provenance_ix', ['provenance']],
            ['uckkarchive_proof_user_ix', ['userid']],
        ],
        'uckkarchive_prov' => [
            ['uckkarchive_prov_archiveid_ix', ['archiveid']],
            ['uckkarchive_prov_itemid_ix', ['itemid']],
            ['uckkarchive_prov_source_ix', ['sourcecomponent', 'sourceid']],
            ['uckkarchive_prov_provenance_ix', ['provenance']],
        ],
        'uckkarchive_rev' => [
            ['uckkarchive_rev_archiveid_ix', ['archiveid']],
            ['uckkarchive_rev_itemid_ix', ['itemid']],
            ['uckkarchive_rev_version_ix', ['versionno']],
            ['uckkarchive_rev_createdby_ix', ['createdby']],
        ],
        'uckkarchive_export' => [
            ['uckkarchive_export_archiveid_ix', ['archiveid']],
            ['uckkarchive_export_status_ix', ['status']],
            ['uckkarchive_export_visibility_ix', ['visibility']],
            ['uckkarchive_export_createdby_ix', ['createdby']],
        ],
    ];

    foreach ($indexes as $tablename => $tableindexes) {
        if (!$dbman->table_exists($tablename)) {
            continue;
        }

        $table = new xmldb_table($tablename);

        foreach ($tableindexes as [$indexname, $fields]) {
            if (!uckkarchive_upgrade_table_has_fields($tablename, $fields)) {
                continue;
            }

            $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, $fields);

            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
    }
}

/**
 * Return whether a table contains all requested fields.
 *
 * @param string $tablename Table name without braces.
 * @param string[] $fields Field names.
 * @return bool
 */
function uckkarchive_upgrade_table_has_fields(string $tablename, array $fields): bool {
    global $DB;

    $columns = $DB->get_columns($tablename);

    foreach ($fields as $field) {
        if (!array_key_exists($field, $columns)) {
            return false;
        }
    }

    return true;
}