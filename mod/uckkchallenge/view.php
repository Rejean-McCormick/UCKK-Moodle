<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/uckkchallenge/lib.php');

use mod_uckkchallenge\output\view_page;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT);   // UCKK challenge instance id.

if ($id) {
    $cm = get_coursemodule_from_id('uckkchallenge', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $uckkchallenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($u) {
    $uckkchallenge = $DB->get_record('uckkchallenge', ['id' => $u], '*', MUST_EXIST);
    $course = get_course($uckkchallenge->course);
    $cm = get_coursemodule_from_instance(
        'uckkchallenge',
        $uckkchallenge->id,
        $course->id,
        false,
        MUST_EXIST
    );
} else {
    throw new moodle_exception('missingidandcmid', 'mod_uckkchallenge');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/uckkchallenge:view', $context);

$PAGE->set_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(format_string($uckkchallenge->name, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]));
$PAGE->set_pagelayout('incourse');

if (class_exists('\completion_info')) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

$event = \mod_uckkchallenge\event\course_module_viewed::create([
    'objectid' => $uckkchallenge->id,
    'context' => $context,
]);

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('uckkchallenge', $uckkchallenge);
$event->trigger();

$output = $PAGE->get_renderer('mod_uckkchallenge');

$page = new view_page(
    $course,
    $cm,
    $context,
    $uckkchallenge,
    [
        'canview' => has_capability('mod/uckkchallenge:view', $context),
        'cancreatechallenge' => has_capability('mod/uckkchallenge:createchallenge', $context),
        'cansubmitproof' => has_capability('mod/uckkchallenge:submitproof', $context),
        'canevaluate' => has_capability('mod/uckkchallenge:evaluate', $context),
        'canvalidateintegrity' => has_capability('mod/uckkchallenge:validateintegrity', $context),
        'cancontest' => has_capability('mod/uckkchallenge:contest', $context),
        'canarchive' => has_capability('mod/uckkchallenge:archive', $context),
    ]
);

echo $output->header();
echo $output->render($page);
echo $output->footer();