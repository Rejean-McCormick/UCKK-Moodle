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
 * Plugin version and dependencies for the UCKK course format.
 *
 * The UCKK course format defines the canonical course structure used by
 * UCKK-Moodle:
 *
 * 0. Orientation
 * 1. Concepts
 * 2. Matière canonique
 * 3. Atelier
 * 4. Preuves
 * 5. Délibération
 * 6. Livrable
 * 7. Évaluation
 * 8. Archive
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'format_uckk';
$plugin->version = 2026051200;
$plugin->requires = 2025041400;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
    'mod_uckkarchive' => 2026051200,
];