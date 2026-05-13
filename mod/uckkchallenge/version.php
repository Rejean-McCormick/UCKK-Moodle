<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Version metadata for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->component = 'mod_uckkchallenge';
$plugin->version = 2026051200;
$plugin->requires = 2025041400;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
    'mod_uckkarchive' => 2026051200,
    'tool_uckkintegrity' => 2026051200,
];