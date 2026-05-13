<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Upgrade steps for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute upgrade steps for mod_uckkchallenge.
 *
 * Clean installs use db/install.xml. This file is only used when Moodle detects
 * an already-installed older version of the plugin.
 *
 * Important:
 * - Do not call mod_uckkchallenge service classes from here.
 * - Use XMLDB/database manager operations for schema changes.
 * - Add one version-gated block per future schema/data migration.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_uckkchallenge_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051200) {
        // Initial stable release baseline.
        //
        // The complete initial schema is defined in db/install.xml and is used
        // for clean installs. This savepoint exists for development or
        // pre-release sites that may already have an earlier plugin version
        // recorded in Moodle before the stable 1.0.0 release.

        upgrade_mod_savepoint(true, 2026051200, 'uckkchallenge');
    }

    return true;
}