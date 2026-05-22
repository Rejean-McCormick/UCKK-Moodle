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
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $PAGE, $COURSE;

if (empty($course) || empty($course->id)) {
    throw new coding_exception('format_uckk/format.php must be included from a valid course context.');
}

$sectionnumber = 0;
if (isset($section)) {
    $sectionnumber = (int) $section;
}

// Keep global course aligned with the rendered course.
if (empty($COURSE->id) || (int) $COURSE->id !== (int) $course->id) {
    $COURSE = $course;
}

// Do not call $PAGE->add_body_class() here.
// In Moodle course format files, output may already have started.
// Use a wrapper div instead.
$wrapperclasses = [
    'format-uckk',
    'uckk-course-page',
];

$courseidentity = '';
if (!empty($course->idnumber)) {
    $courseidentity = (string) $course->idnumber;
} else if (!empty($course->shortname)) {
    $courseidentity = (string) $course->shortname;
}

if ($courseidentity !== '' && preg_match('/^UCKK-/i', $courseidentity)) {
    $wrapperclasses[] = 'uckk-course-canonical';
}

if ($courseidentity !== '' && preg_match('/^UCKK-TC/i', $courseidentity)) {
    $wrapperclasses[] = 'uckk-course-tronccommun';
}

// Use Moodle's course_get_format() helper.
// This gives the real course format instance for this course.
$format = course_get_format($course);

// Moodle versions differ on the section setter name.
if (method_exists($format, 'set_sectionnum')) {
    $format->set_sectionnum($sectionnumber);
} else if (method_exists($format, 'set_section_number')) {
    $format->set_section_number($sectionnumber);
}

// Resolve the content output class.
// Expected override when present:
//   format_uckk\output\courseformat\content
$outputclass = $format->get_output_classname('content');

if (!class_exists($outputclass)) {
    throw new coding_exception('Unable to resolve UCKK course format content output class: ' . $outputclass);
}

$content = new $outputclass($format);

// This requires:
//   course/format/uckk/classes/output/renderer.php
//
// Do not bypass this. Moodle 4+ expects course formats to define their renderer.
$renderer = $format->get_renderer($PAGE);

echo html_writer::start_div(
    implode(' ', $wrapperclasses),
    [
        'data-courseid' => (int) $course->id,
        'data-section' => $sectionnumber,
        'data-format' => 'uckk',
    ]
);

echo $renderer->render($content);

echo html_writer::end_div();