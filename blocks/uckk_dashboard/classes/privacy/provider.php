<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for the UCKK dashboard block.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uckk_dashboard\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for block_uckk_dashboard.
 *
 * The dashboard block does not own UCKK personal records. It renders data held
 * by Moodle core and by UCKK source components such as local_uckk,
 * mod_uckkchallenge, mod_uckkassembly, mod_uckkarchive, and tool_uckkintegrity.
 *
 * If this block later stores user-specific dashboard preferences, this provider
 * must be upgraded to implement the relevant metadata and request providers.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Explain why this plugin stores no personal data.
     *
     * @return string Language string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}