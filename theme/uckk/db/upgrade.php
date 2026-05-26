<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for the UCKK theme.
 *
 * This file must remain self-contained. Files in Moodle plugin db/ directories
 * must not include other files or call plugin-local functions during upgrade.
 *
 * The UCKK theme is presentation-only. Upgrade steps here may migrate theme
 * configuration values, rename stored theme settings, or clean obsolete
 * presentation settings. They must not perform academic, challenge, assembly,
 * archive, integrity, grading, or AI workflow migrations.
 *
 * @package    theme_uckk
 * @copyright  2026 Réjean McCormick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the UCKK theme.
 *
 * @param int $oldversion The currently installed plugin version.
 * @return bool True on success.
 */
function xmldb_theme_uckk_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    /*
     * Initial stable release.
     *
     * Version: 2026051200
     *
     * No database schema is owned by theme_uckk at this stage.
     * No data migration is required.
     *
     * This savepoint exists so pre-release installations of theme_uckk can
     * move cleanly to the first stable version without running presentation
     * logic from lib.php, config.php, renderers, templates, or AMD modules.
     */
    if ($oldversion < 2026051200) {
        upgrade_plugin_savepoint(true, 2026051200, 'theme', 'uckk');
    }

    /*
     * Future upgrade blocks must follow this pattern:
     *
     * if ($oldversion < YYYYMMDDXX) {
     *     // Use Moodle core APIs that are safe during plugin upgrade.
     *     // Do not call theme_uckk_* functions.
     *     // Do not include files.
     *     // Do not move workflow data belonging to local_uckk,
     *     // mod_uckkchallenge, mod_uckkassembly, mod_uckkarchive,
     *     // tool_uckkintegrity, report_uckk, or aiprovider_uckk.
     *
     *     upgrade_plugin_savepoint(true, YYYYMMDDXX, 'theme', 'uckk');
     * }
     */

    return true;
}