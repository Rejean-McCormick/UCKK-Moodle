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
 * Course page renderer entry point for the UCKK course format.
 *
 * This file is included by /course/view.php when a course uses the
 * "uckk" course format.
 *
 * Its responsibility is intentionally narrow:
 * - obtain the course format instance;
 * - tell the format which section is currently requested;
 * - instantiate the course content output class;
 * - delegate rendering to the format renderer.
 *
 * UCKK pedagogical rules, section semantics, archive logic, integrity rules,
 * challenge workflows and assembly workflows must live in the format base class,
 * output classes, templates, activity modules or dedicated UCKK plugins.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $PAGE, $COURSE;

// -----------------------------------------------------------------------------
// Validate expected Moodle context.
// -----------------------------------------------------------------------------
//
// Moodle includes this file from /course/view.php. The $course variable is
// expected to be available in that scope. $section is also commonly provided
// by course/view.php to indicate a single section request.

if (empty($course) || empty($course->id)) {
    throw new coding_exception('format_uckk/format.php must be included from a valid course context.');
}

$sectionnumber = 0;
if (isset($section)) {
    $sectionnumber = (int) $section;
}

// Keep the current global course aligned with the course being rendered.
if (!empty($COURSE->id) && (int) $COURSE->id !== (int) $course->id) {
    $COURSE = $course;
}

// -----------------------------------------------------------------------------
// Prepare page identity.
// -----------------------------------------------------------------------------

$PAGE->add_body_class('format-uckk');
$PAGE->add_body_class('uckk-course-page');

if (!empty($course->idnumber) && preg_match('/^UCKK-/i', $course->idnumber)) {
    $PAGE->add_body_class('uckk-course-canonical');
}

if (!empty($course->idnumber) && preg_match('/^UCKK-TC/i', $course->idnumber)) {
    $PAGE->add_body_class('uckk-course-tronccommun');
}

// -----------------------------------------------------------------------------
// Create and configure the course format instance.
// -----------------------------------------------------------------------------
//
// In Moodle 4+ and 5+, course formats are implemented through the
// core_courseformat subsystem. The format base instance becomes the exchange
// object used by output classes and renderers.

$format = core_courseformat\base::instance($course);

// The section number is still the central request variable for course formats.
// Output classes can use this to decide whether to render the whole course or a
// single section view.
$format->set_section_number($sectionnumber);

// -----------------------------------------------------------------------------
// Optional AMD initialisation.
// -----------------------------------------------------------------------------
//
// The UCKK format may provide amd/src/courseformat.js for small UI behaviours.
// This call must remain non-critical: all course content must render correctly
// without JavaScript.

$PAGE->requires->js_call_amd('format_uckk/courseformat', 'init', [
    [
        'courseid' => (int) $course->id,
        'section' => $sectionnumber,
        'format' => 'uckk',
    ],
]);

// -----------------------------------------------------------------------------
// Render course content.
// -----------------------------------------------------------------------------
//
// get_output_classname('content') returns the UCKK override when present:
//   format_uckk\output\courseformat\content
//
// If no override exists, Moodle falls back to the core courseformat output.
// The renderer then renders the named_templatable output object.

$outputclass = $format->get_output_classname('content');

if (!class_exists($outputclass)) {
    throw new coding_exception('Unable to resolve UCKK course format content output class.');
}

$content = new $outputclass($format);
$renderer = $format->get_renderer($PAGE);

echo $renderer->render($content);