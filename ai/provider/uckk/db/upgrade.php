<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade hook for aiprovider_uckk.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_aiprovider_uckk_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051200) {
        $table = new xmldb_table('aiprovider_uckk_log');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('action', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('recordtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('contextid', XMLDB_INDEX_NOTUNIQUE, ['contextid']);
            $table->add_index('action', XMLDB_INDEX_NOTUNIQUE, ['action']);
            $table->add_index('recordtype', XMLDB_INDEX_NOTUNIQUE, ['recordtype']);
            $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051200, 'aiprovider', 'uckk');
    }

    return true;
}