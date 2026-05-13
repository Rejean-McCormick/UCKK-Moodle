<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Version information for the UCKK Seed admin tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'tool_uckkseed';
$plugin->version = 2026051200;
$plugin->requires = 2025041400;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
    'theme_uckk' => 2026051200,
    'format_uckk' => 2026051200,
    'block_uckk_dashboard' => 2026051200,
    'mod_uckkchallenge' => 2026051200,
    'mod_uckkassembly' => 2026051200,
    'mod_uckkarchive' => 2026051200,
    'tool_uckkintegrity' => 2026051200,
    'report_uckk' => 2026051200,
];