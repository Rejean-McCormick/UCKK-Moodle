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
 * Public integrity page for UCKK.
 *
 * This controller is intentionally thin. It delegates page setup, navigation,
 * content definition and rendering to the central local_uckk public page
 * system.
 *
 * It must not:
 * - render custom page sections directly;
 * - duplicate the public navigation;
 * - define local CSS conventions;
 * - create integrity cases;
 * - decide sanctions;
 * - validate recognitions;
 * - mutate records;
 * - perform administrative review.
 *
 * Integrity case handling belongs to tool_uckkintegrity and the relevant UCKK
 * service layers. This page is public presentation only.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'integrity';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();