<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade hook for report_uckk.
 *
 * report_uckk owns no primary report data tables. It reads derived data from
 * UCKK source plugins and Moodle core tables, so this upgrade file currently
 * only records plugin savepoints. Future setting migrations or audit-table
 * migrations must be added here.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_report_uckk_upgrade(int $oldversion): bool {
    if ($oldversion < 2026051200) {
        upgrade_plugin_savepoint(true, 2026051200, 'report', 'uckk');
    }

    return true;
}