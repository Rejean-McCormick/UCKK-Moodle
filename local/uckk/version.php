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
 * Version metadata for the UCKK institutional core plugin.
 *
 * The local_uckk plugin is the institutional kernel of UCKK-Moodle.
 * It owns the shared domain registry used by the UCKK plugin suite:
 *
 * - programs;
 * - pathways;
 * - symbolic roles;
 * - Joueur profiles;
 * - UCKK campus settings;
 * - shared provenance records;
 * - shared visibility rules;
 * - navigation registry;
 * - bridge services used by the dashboard, course format, challenges,
 *   assemblies, archives, reports, integrity tool and AI provider.
 *
 * This plugin must remain the foundation of the distribution. It must not
 * duplicate data owned by activity modules such as challenge submissions,
 * assembly motions, archive item content or integrity case records.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_uckk';
$plugin->version = 2026060300;
$plugin->requires = 2025041400;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.2';