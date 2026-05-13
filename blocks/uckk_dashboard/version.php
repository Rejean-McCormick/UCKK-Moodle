<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Version metadata for the UCKK dashboard block.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2026051200;
$plugin->requires = 2025041400;
$plugin->component = 'block_uckk_dashboard';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
    'mod_uckkchallenge' => 2026051200,
    'mod_uckkassembly' => 2026051200,
    'mod_uckkarchive' => 2026051200,
    'tool_uckkintegrity' => 2026051200,
];