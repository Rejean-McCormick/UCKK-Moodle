<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Upgrade steps for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute upgrade steps for tool_uckkseed.
 *
 * Clean installs use db/install.xml. This file is only used when Moodle detects
 * an already-installed older version of the plugin.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_tool_uckkseed_upgrade($oldversion): bool {
    if ($oldversion < 2026051200) {
        // Initial stable release baseline.
        //
        // The complete initial schema is defined in db/install.xml:
        // - {tool_uckkseed_run}
        // - {tool_uckkseed_log}
        //
        // Fresh installs use install.xml. This block only protects development
        // or pre-release sites that may already have an older tool_uckkseed
        // version recorded before the stable 1.0.0 release.

        tool_uckkseed_upgrade_ensure_initial_tables();
        tool_uckkseed_upgrade_repair_initial_fields();
        tool_uckkseed_upgrade_repair_initial_indexes();
        tool_uckkseed_upgrade_drop_unsafe_target_lookup_index();

        upgrade_plugin_savepoint(true, 2026051200, 'tool', 'uckkseed');
    }

    if ($oldversion < 2026051201) {
        // Remove unsafe composite index from development/pre-release schemas.
        //
        // The composite index target_lookup(targettype, targetkey) can exceed
        // Moodle/MySQL index limits because targettype is char(100) and
        // targetkey is char(255). The single-column indexes targettype and
        // targetkey are kept instead.
        tool_uckkseed_upgrade_drop_unsafe_target_lookup_index();
        tool_uckkseed_upgrade_repair_initial_indexes();

        upgrade_plugin_savepoint(true, 2026051201, 'tool', 'uckkseed');
    }

    return true;
}

/**
 * Ensure the two canonical seed tables exist for development/pre-release upgrades.
 *
 * @return void
 */
function tool_uckkseed_upgrade_ensure_initial_tables(): void {
    global $DB;

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists('tool_uckkseed_run')) {
        $table = new xmldb_table('tool_uckkseed_run');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('action', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'seed');
        $table->add_field('mode', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'dry_run');
        $table->add_field('status', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'tool_uckkseed');
        $table->add_field('source', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'web');
        $table->add_field('preset', XMLDB_TYPE_CHAR, '100');
        $table->add_field('presets', XMLDB_TYPE_TEXT);
        $table->add_field('components', XMLDB_TYPE_TEXT);
        $table->add_field('summary', XMLDB_TYPE_TEXT);
        $table->add_field('details', XMLDB_TYPE_TEXT);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('skipped', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('failed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('warnings', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('errors', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timefinished', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('metadata', XMLDB_TYPE_TEXT);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
    }

    if (!$dbman->table_exists('tool_uckkseed_log')) {
        $table = new xmldb_table('tool_uckkseed_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('runid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('level', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'info');
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('action', XMLDB_TYPE_CHAR, '64');
        $table->add_field('mode', XMLDB_TYPE_CHAR, '64');
        $table->add_field('status', XMLDB_TYPE_CHAR, '64');
        $table->add_field('component', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'tool_uckkseed');
        $table->add_field('preset', XMLDB_TYPE_CHAR, '100');
        $table->add_field('targettype', XMLDB_TYPE_CHAR, '100');
        $table->add_field('targetkey', XMLDB_TYPE_CHAR, '255');
        $table->add_field('targetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('summary', XMLDB_TYPE_TEXT);
        $table->add_field('details', XMLDB_TYPE_TEXT);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('metadata', XMLDB_TYPE_TEXT);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('runid', XMLDB_KEY_FOREIGN, ['runid'], 'tool_uckkseed_run', ['id']);

        $dbman->create_table($table);
    }
}

/**
 * Repair initial fields expected by the stable schema.
 *
 * This is conservative: it adds missing fields only when the table already
 * exists. It does not remove old fields or transform seed data destructively.
 *
 * @return void
 */
function tool_uckkseed_upgrade_repair_initial_fields(): void {
    $runfields = [
        'action' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64', 'notnull' => true, 'default' => 'seed'],
        'mode' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64', 'notnull' => true, 'default' => 'dry_run'],
        'status' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64', 'notnull' => true, 'default' => 'pending'],
        'component' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100', 'notnull' => true, 'default' => 'tool_uckkseed'],
        'source' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100', 'notnull' => true, 'default' => 'web'],
        'preset' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100'],
        'presets' => ['type' => XMLDB_TYPE_TEXT],
        'components' => ['type' => XMLDB_TYPE_TEXT],
        'summary' => ['type' => XMLDB_TYPE_TEXT],
        'details' => ['type' => XMLDB_TYPE_TEXT],
        'created' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'updated' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'skipped' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'failed' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'warnings' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'errors' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'userid' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'createdby' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'modifiedby' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timecreated' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timemodified' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timestarted' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timefinished' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'duration' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'metadata' => ['type' => XMLDB_TYPE_TEXT],
    ];

    $logfields = [
        'runid' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true],
        'level' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64', 'notnull' => true, 'default' => 'info'],
        'message' => ['type' => XMLDB_TYPE_TEXT, 'notnull' => true],
        'action' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64'],
        'mode' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64'],
        'status' => ['type' => XMLDB_TYPE_CHAR, 'length' => '64'],
        'component' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100', 'notnull' => true, 'default' => 'tool_uckkseed'],
        'preset' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100'],
        'targettype' => ['type' => XMLDB_TYPE_CHAR, 'length' => '100'],
        'targetkey' => ['type' => XMLDB_TYPE_CHAR, 'length' => '255'],
        'targetid' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'summary' => ['type' => XMLDB_TYPE_TEXT],
        'details' => ['type' => XMLDB_TYPE_TEXT],
        'userid' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'createdby' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'modifiedby' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timecreated' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'timemodified' => ['type' => XMLDB_TYPE_INTEGER, 'length' => '10', 'notnull' => true, 'default' => '0'],
        'metadata' => ['type' => XMLDB_TYPE_TEXT],
    ];

    tool_uckkseed_upgrade_add_missing_fields('tool_uckkseed_run', $runfields);
    tool_uckkseed_upgrade_add_missing_fields('tool_uckkseed_log', $logfields);
}

/**
 * Add missing fields to a table.
 *
 * @param string $tablename Table name without braces.
 * @param array<string, array<string, mixed>> $fields Field definitions.
 * @return void
 */
function tool_uckkseed_upgrade_add_missing_fields(string $tablename, array $fields): void {
    global $DB;

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists($tablename)) {
        return;
    }

    $table = new xmldb_table($tablename);

    foreach ($fields as $fieldname => $definition) {
        $field = new xmldb_field(
            $fieldname,
            $definition['type'],
            $definition['length'] ?? null,
            null,
            !empty($definition['notnull']) ? XMLDB_NOTNULL : null,
            null,
            $definition['default'] ?? null
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
}

/**
 * Repair indexes expected by the stable schema.
 *
 * The unsafe composite index target_lookup(targettype, targetkey) is
 * intentionally not created here. targettype and targetkey are indexed
 * separately to remain portable across Moodle-supported databases.
 *
 * @return void
 */
function tool_uckkseed_upgrade_repair_initial_indexes(): void {
    global $DB;

    $dbman = $DB->get_manager();

    $indexes = [
        'tool_uckkseed_run' => [
            ['action', ['action']],
            ['mode', ['mode']],
            ['status', ['status']],
            ['component', ['component']],
            ['source', ['source']],
            ['preset', ['preset']],
            ['userid', ['userid']],
            ['createdby', ['createdby']],
            ['timecreated', ['timecreated']],
            ['timemodified', ['timemodified']],
            ['action_status', ['action', 'status']],
            ['mode_status', ['mode', 'status']],
        ],
        'tool_uckkseed_log' => [
            ['level', ['level']],
            ['action', ['action']],
            ['mode', ['mode']],
            ['status', ['status']],
            ['component', ['component']],
            ['preset', ['preset']],
            ['targettype', ['targettype']],
            ['targetkey', ['targetkey']],
            ['targetid', ['targetid']],
            ['userid', ['userid']],
            ['createdby', ['createdby']],
            ['timecreated', ['timecreated']],
            ['run_level', ['runid', 'level']],
            ['run_preset', ['runid', 'preset']],
        ],
    ];

    foreach ($indexes as $tablename => $tableindexes) {
        if (!$dbman->table_exists($tablename)) {
            continue;
        }

        $table = new xmldb_table($tablename);

        foreach ($tableindexes as [$indexname, $fields]) {
            if (!tool_uckkseed_upgrade_table_has_fields($tablename, $fields)) {
                continue;
            }

            $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, $fields);

            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
    }

    if (
        $dbman->table_exists('tool_uckkseed_log')
        && $dbman->table_exists('tool_uckkseed_run')
        && tool_uckkseed_upgrade_table_has_fields('tool_uckkseed_log', ['runid'])
    ) {
        $table = new xmldb_table('tool_uckkseed_log');
        $key = new xmldb_key('runid', XMLDB_KEY_FOREIGN, ['runid'], 'tool_uckkseed_run', ['id']);

        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }
    }
}

/**
 * Drop the unsafe composite target lookup index if it exists.
 *
 * @return void
 */
function tool_uckkseed_upgrade_drop_unsafe_target_lookup_index(): void {
    global $DB;

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists('tool_uckkseed_log')) {
        return;
    }

    if (!tool_uckkseed_upgrade_table_has_fields('tool_uckkseed_log', ['targettype', 'targetkey'])) {
        return;
    }

    $table = new xmldb_table('tool_uckkseed_log');
    $index = new xmldb_index('target_lookup', XMLDB_INDEX_NOTUNIQUE, ['targettype', 'targetkey']);

    if ($dbman->index_exists($table, $index)) {
        $dbman->drop_index($table, $index);
    }
}

/**
 * Return whether a table contains all requested fields.
 *
 * @param string $tablename Table name without braces.
 * @param string[] $fields Field names.
 * @return bool
 */
function tool_uckkseed_upgrade_table_has_fields(string $tablename, array $fields): bool {
    global $DB;

    if (!$DB->get_manager()->table_exists($tablename)) {
        return false;
    }

    $columns = $DB->get_columns($tablename);

    foreach ($fields as $field) {
        if (!array_key_exists($field, $columns)) {
            return false;
        }
    }

    return true;
}