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
 * Public UCKK assemblies page.
 *
 * Assemblies are presented through the shared public page system.
 * This controller owns no page-specific navigation, no local styling,
 * no handwritten page shell and no institutional workflow logic.
 *
 * Assembly activities, motions, votes, decisions, contestations and archive
 * records remain owned by their dedicated Moodle components.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'assemblies';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();