<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for tool_uckkintegrity.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the UCKK integrity tool.
 *
 * Fresh installs are handled by db/install.xml. This file protects existing
 * installations by adding any schema pieces introduced before the current
 * release savepoint.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_tool_uckkintegrity_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051200) {
        $casetable = new xmldb_table('tool_uckkintegrity_case');

        if (!$dbman->table_exists($casetable)) {
            $casetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $casetable->add_field('casetype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $casetable->add_field('subjectcomponent', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $casetable->add_field('subjectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $casetable->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $casetable->add_field('openedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $casetable->add_field('assignedto', XMLDB_TYPE_INTEGER, '10');
            $casetable->add_field('severity', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'normal');
            $casetable->add_field('status', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'opened');
            $casetable->add_field('summary', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $casetable->add_field('decision', XMLDB_TYPE_TEXT);
            $casetable->add_field('correction', XMLDB_TYPE_TEXT);
            $casetable->add_field('appealpath', XMLDB_TYPE_TEXT);
            $casetable->add_field('archivesummary', XMLDB_TYPE_TEXT);
            $casetable->add_field('archiveitemid', XMLDB_TYPE_INTEGER, '10');
            $casetable->add_field('visibility', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'restricted');
            $casetable->add_field('versionno', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $casetable->add_field('provenancehash', XMLDB_TYPE_CHAR, '128');
            $casetable->add_field('metadata', XMLDB_TYPE_TEXT);
            $casetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $casetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $casetable->add_field('timeclosed', XMLDB_TYPE_INTEGER, '10');

            $casetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $casetable->add_index('contextid', XMLDB_INDEX_NOTUNIQUE, ['contextid']);
            $casetable->add_index('openedby', XMLDB_INDEX_NOTUNIQUE, ['openedby']);
            $casetable->add_index('assignedto', XMLDB_INDEX_NOTUNIQUE, ['assignedto']);
            $casetable->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $casetable->add_index('casetype', XMLDB_INDEX_NOTUNIQUE, ['casetype']);
            $casetable->add_index('subject', XMLDB_INDEX_NOTUNIQUE, ['subjectcomponent', 'subjectid']);

            $dbman->create_table($casetable);
        } else {
            $fields = [
                new xmldb_field('correction', XMLDB_TYPE_TEXT),
                new xmldb_field('appealpath', XMLDB_TYPE_TEXT),
                new xmldb_field('archivesummary', XMLDB_TYPE_TEXT),
                new xmldb_field('archiveitemid', XMLDB_TYPE_INTEGER, '10'),
                new xmldb_field('visibility', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'restricted'),
                new xmldb_field('versionno', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1'),
                new xmldb_field('provenancehash', XMLDB_TYPE_CHAR, '128'),
                new xmldb_field('metadata', XMLDB_TYPE_TEXT),
                new xmldb_field('timeclosed', XMLDB_TYPE_INTEGER, '10'),
            ];

            foreach ($fields as $field) {
                if (!$dbman->field_exists($casetable, $field)) {
                    $dbman->add_field($casetable, $field);
                }
            }

            $indexes = [
                new xmldb_index('contextid', XMLDB_INDEX_NOTUNIQUE, ['contextid']),
                new xmldb_index('openedby', XMLDB_INDEX_NOTUNIQUE, ['openedby']),
                new xmldb_index('assignedto', XMLDB_INDEX_NOTUNIQUE, ['assignedto']),
                new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']),
                new xmldb_index('casetype', XMLDB_INDEX_NOTUNIQUE, ['casetype']),
                new xmldb_index('subject', XMLDB_INDEX_NOTUNIQUE, ['subjectcomponent', 'subjectid']),
            ];

            foreach ($indexes as $index) {
                if (!$dbman->index_exists($casetable, $index)) {
                    $dbman->add_index($casetable, $index);
                }
            }
        }

        $notetable = new xmldb_table('tool_uckkintegrity_note');

        if (!$dbman->table_exists($notetable)) {
            $notetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $notetable->add_field('caseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $notetable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $notetable->add_field('notetype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'observation');
            $notetable->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $notetable->add_field('visibility', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'restricted');
            $notetable->add_field('metadata', XMLDB_TYPE_TEXT);
            $notetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $notetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $notetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $notetable->add_key(
                'casefk',
                XMLDB_KEY_FOREIGN,
                ['caseid'],
                'tool_uckkintegrity_case',
                ['id']
            );

            $notetable->add_index('caseid', XMLDB_INDEX_NOTUNIQUE, ['caseid']);
            $notetable->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $notetable->add_index('visibility', XMLDB_INDEX_NOTUNIQUE, ['visibility']);

            $dbman->create_table($notetable);
        } else {
            $fields = [
                new xmldb_field('visibility', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'restricted'),
                new xmldb_field('metadata', XMLDB_TYPE_TEXT),
                new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
            ];

            foreach ($fields as $field) {
                if (!$dbman->field_exists($notetable, $field)) {
                    $dbman->add_field($notetable, $field);
                }
            }

            $indexes = [
                new xmldb_index('caseid', XMLDB_INDEX_NOTUNIQUE, ['caseid']),
                new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']),
                new xmldb_index('visibility', XMLDB_INDEX_NOTUNIQUE, ['visibility']),
            ];

            foreach ($indexes as $index) {
                if (!$dbman->index_exists($notetable, $index)) {
                    $dbman->add_index($notetable, $index);
                }
            }
        }

        $appealtable = new xmldb_table('tool_uckkintegrity_appeal');

        if (!$dbman->table_exists($appealtable)) {
            $appealtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $appealtable->add_field('caseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $appealtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $appealtable->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $appealtable->add_field('status', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'submitted');
            $appealtable->add_field('decision', XMLDB_TYPE_TEXT);
            $appealtable->add_field('decidedby', XMLDB_TYPE_INTEGER, '10');
            $appealtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $appealtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $appealtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $appealtable->add_key(
                'casefk',
                XMLDB_KEY_FOREIGN,
                ['caseid'],
                'tool_uckkintegrity_case',
                ['id']
            );

            $appealtable->add_index('caseid', XMLDB_INDEX_NOTUNIQUE, ['caseid']);
            $appealtable->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $appealtable->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

            $dbman->create_table($appealtable);
        }

        upgrade_plugin_savepoint(true, 2026051200, 'tool', 'uckkintegrity');
    }

    return true;
}