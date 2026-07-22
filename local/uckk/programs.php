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
 * Public UCKK programs page.
 *
 * Thin public controller.
 *
 * Page setup, navigation, content definitions, cards, notices and visual
 * structure are owned by:
 *
 * - \local_uckk\local\public_pages
 * - \local_uckk\output\public_page
 * - local_uckk/public_page.mustache
 * - local_uckk/styles.css
 *
 * This controller must not create programs, mutate pathways, enrol users,
 * award badges, validate competencies, make accreditation claims, rebuild
 * the public page definition locally, or activate experimental visual
 * signature systems.
 *
 * Experimental visual signatures, if kept as dormant/zombie code, must remain
 * disabled upstream in the page definition/style layer and must not be toggled
 * from this controller.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'programs';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();