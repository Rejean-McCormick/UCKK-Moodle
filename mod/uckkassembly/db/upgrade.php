<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Upgrade code for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for mod_uckkassembly.
 *
 * The initial schema is defined in db/install.xml. This file is reserved for
 * migrations of existing installations between released plugin versions.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_uckkassembly_upgrade($oldversion): bool {
    if ($oldversion < 2026051200) {
        // Initial stable release. No upgrade steps are required because fresh
        // installations are created from db/install.xml.
        upgrade_mod_savepoint(true, 2026051200, 'uckkassembly');
    }

    return true;
}