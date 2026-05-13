<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Privacy provider for the UCKK course format.
 *
 * The UCKK course format controls the presentation and pedagogical structure
 * of a course. It does not store personal user data.
 *
 * Course-level UCKK data belongs in course format options or in dedicated
 * UCKK plugins. Personal data belongs in the relevant activity, block, local
 * plugin, archive, challenge, assembly or integrity component.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for format_uckk.
 *
 * This course format does not store personal user data.
 *
 * If future versions of format_uckk store user preferences, per-user course
 * layout state, AI interaction logs, archive links, proof metadata or other
 * user-related records, this null provider must be replaced by a complete
 * Moodle Privacy API provider.
 *
 * @package format_uckk
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Return the language string identifier explaining why this plugin stores
     * no personal data.
     *
     * Required corresponding strings:
     * - course/format/uckk/lang/fr/format_uckk.php
     * - course/format/uckk/lang/en/format_uckk.php
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}