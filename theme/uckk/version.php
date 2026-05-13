<?php
// This file is part of UCKK-Moodle - https://moodle.org/
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// UCKK-Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with UCKK-Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version metadata for the UCKK Moodle theme.
 *
 * The UCKK theme provides the visual and navigational layer for the
 * Univers-Cité King Klown Moodle distribution. It must remain a Moodle
 * theme plugin and must not contain UCKK business logic, academic workflow
 * rules, integrity decisions, archives, challenge logic, assembly logic, or
 * AI governance logic. Those responsibilities belong to their own UCKK
 * plugins.
 *
 * @package    theme_uckk
 * @copyright  2026 Momus et Bouche Cousue
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_uckk';

$plugin->version = 2026051200;
$plugin->requires = 2026042000; // Moodle 5.2.0.
$plugin->supported = [502, 502];

$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
];