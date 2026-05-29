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
 * Public about page for UCKK.
 *
 * This controller is intentionally thin. Public page metadata, navigation,
 * sections, cards, notices and rendering context are owned by:
 *
 * - local_uckk\local\public_pages
 * - local_uckk\output\public_page
 * - local_uckk/public_page.mustache
 * - local_uckk/styles.css
 *
 * It must not duplicate navigation, inline HTML structures, local CSS classes,
 * recognition logic, archive logic, enrolment logic, accreditation claims,
 * integrity workflows or Moodle administration behaviour.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'about';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();