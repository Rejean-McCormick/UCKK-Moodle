<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// later.

/**
 * Upgrade steps for the UCKK local plugin.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_uckk upgrade steps.
 *
 * Fresh installations are created from db/install.xml. This function is only
 * used when an existing local_uckk installation is upgraded from an older
 * version to the code version declared in version.php.
 *
 * @param int $oldversion The currently installed plugin version.
 * @return bool
 */
function xmldb_local_uckk_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // Initial stable release: 2026051200.
    //
    // No upgrade step is required for a clean installation because Moodle uses
    // db/install.xml for the initial schema. Add future schema/data migrations
    // below using monotonically increasing plugin versions and finish each step
    // with upgrade_plugin_savepoint().
    //
    // Example future pattern:
    //
    // if ($oldversion < 2026051201) {
    //     // XMLDB-generated schema changes or safe DML migration here.
    //
    //     upgrade_plugin_savepoint(true, 2026051201, 'local', 'uckk');
    // }

    return true;
}