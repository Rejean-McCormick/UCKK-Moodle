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
 * Public UCKK home page.
 *
 * This controller intentionally remains thin. Page metadata, navigation,
 * content sections, cards, notices and calls to action are owned by
 * \local_uckk\local\public_pages and rendered through
 * \local_uckk\output\public_page.
 *
 * The authenticated internal campus hub lives in /local/uckk/campus.php.
 *
 * It must not:
 * - require authentication;
 * - require local/uckk:viewcampus;
 * - create or modify programs;
 * - validate challenges;
 * - publish assembly decisions;
 * - validate archive items;
 * - open or close integrity cases;
 * - make AI decisions;
 * - duplicate public navigation;
 * - define page-local styling helpers.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'home';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();