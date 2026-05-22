<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main view page for a UCKK Challenge activity.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/uckkchallenge/lib.php');

use mod_uckkchallenge\output\challenge_view;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT);   // UCKK challenge instance id.

if ($id) {
    $cm = get_coursemodule_from_id('uckkchallenge', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $uckkchallenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($u) {
    $uckkchallenge = $DB->get_record('uckkchallenge', ['id' => $u], '*', MUST_EXIST);
    $course = get_course($uckkchallenge->course);
    $cm = get_coursemodule_from_instance('uckkchallenge', $uckkchallenge->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_uckkchallenge');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/uckkchallenge:view', $context);

$pageurl = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_course($course);
$PAGE->set_title(format_string($uckkchallenge->name, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]));
$PAGE->set_pagelayout('incourse');

if (class_exists('completion_info')) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

if (class_exists('\\mod_uckkchallenge\\event\\course_module_viewed')) {
    $event = \mod_uckkchallenge\event\course_module_viewed::create([
        'objectid' => (int)$uckkchallenge->id,
        'context' => $context,
        'other' => [
            'courseid' => (int)$course->id,
            'cmid' => (int)$cm->id,
        ],
    ]);

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('uckkchallenge', $uckkchallenge);
    $event->trigger();
} else if (class_exists('\\mod_uckkchallenge\\event\\challenge_viewed')) {
    $event = \mod_uckkchallenge\event\challenge_viewed::create([
        'objectid' => (int)$uckkchallenge->id,
        'context' => $context,
        'other' => [
            'courseid' => (int)$course->id,
            'cmid' => (int)$cm->id,
        ],
    ]);

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('uckkchallenge', $uckkchallenge);
    $event->trigger();
}

$output = $PAGE->get_renderer('mod_uckkchallenge');

$page = new challenge_view(
    $uckkchallenge,
    $course,
    $cm,
    $context,
    [
        'userid' => (int)$USER->id,
        'viewurl' => $pageurl,
        'editurl' => new moodle_url('/course/modedit.php', ['update' => $cm->id]),
        'submiturl' => new moodle_url('/mod/uckkchallenge/submit.php', ['id' => $cm->id]),
        'evaluateurl' => new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id]),
        'integrityurl' => new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]),
        'archiveurl' => new moodle_url('/mod/uckkchallenge/archive.php', ['id' => $cm->id]),

        'canview' => has_capability('mod/uckkchallenge:view', $context),
        'canedit' => has_capability('moodle/course:manageactivities', context_course::instance($course->id)),
        'cancreatechallenge' => has_capability('mod/uckkchallenge:createchallenge', $context),
        'cansubmit' => has_capability('mod/uckkchallenge:submitproof', $context),
        'cansubmitproof' => has_capability('mod/uckkchallenge:submitproof', $context),
        'canevaluate' => has_capability('mod/uckkchallenge:evaluate', $context),
        'canvalidateintegrity' => has_capability('mod/uckkchallenge:validateintegrity', $context),
        'cancontest' => has_capability('mod/uckkchallenge:contest', $context),
        'canarchive' => has_capability('mod/uckkchallenge:archive', $context),

        'showoverview' => true,
        'showevidence' => true,
        'showevaluation' => true,
        'showintegrity' => true,
        'showarchive' => true,
        'showrecognition' => true,
    ]
);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_uckkchallenge/challenge_view', $page->export_for_template($output));
echo $OUTPUT->footer();
