<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive export controller for a UCKK challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use mod_uckkchallenge\local\archive_service;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT);   // Challenge instance id.
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$visibility = optional_param('visibility', 'course', PARAM_ALPHAEXT);
$reason = optional_param('reason', '', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('uckkchallenge', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $challenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($u) {
    $challenge = $DB->get_record('uckkchallenge', ['id' => $u], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $challenge->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkchallenge', $challenge->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkchallenge:view', $context);
require_capability('mod/uckkchallenge:archive', $context);

$viewurl = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkchallenge/archive.php', ['id' => $cm->id]);

if ($returnurl !== '') {
    $return = new moodle_url($returnurl);
} else {
    $return = $viewurl;
}

$allowedvisibilities = [
    'private',
    'course',
    'cohort',
    'program',
    'institutional',
    'public',
    'restricted_integrity',
];

if (!in_array($visibility, $allowedvisibilities, true)) {
    throw new moodle_exception('invalidarchivevisibility', 'uckkchallenge');
}

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($challenge->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new archive_service();

if ($confirm) {
    require_sesskey();

    if (!$service->can_archive($challenge, $cm, $context, $USER)) {
        throw new moodle_exception('cannotarchivechallenge', 'uckkchallenge');
    }

    $archiveitem = $service->archive_challenge(
        $challenge,
        $cm,
        $course,
        $context,
        $USER,
        [
            'visibility' => $visibility,
            'reason' => $reason,
        ]
    );

    $message = get_string('challengearchived', 'uckkchallenge');

    if (!empty($archiveitem->url)) {
        redirect(new moodle_url($archiveitem->url), $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    redirect($return, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('archivechallenge', 'uckkchallenge'));

if (!$service->can_archive($challenge, $cm, $context, $USER)) {
    echo $OUTPUT->notification(
        get_string('cannotarchivechallenge', 'uckkchallenge'),
        \core\output\notification::NOTIFY_ERROR
    );

    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

$preview = $service->get_archive_preview($challenge, $cm, $course, $context, $USER);

echo html_writer::start_div('uckkchallenge-archive-confirmation');

echo html_writer::tag(
    'p',
    get_string('archivechallengeintro', 'uckkchallenge'),
    ['class' => 'uckkchallenge-archive-confirmation__intro']
);

echo html_writer::start_tag('dl', ['class' => 'uckkchallenge-archive-confirmation__summary']);

echo html_writer::tag('dt', get_string('challenge', 'uckkchallenge'));
echo html_writer::tag('dd', format_string($challenge->name));

echo html_writer::tag('dt', get_string('course'));
echo html_writer::tag('dd', format_string($course->fullname));

if (!empty($preview->submissioncount)) {
    echo html_writer::tag('dt', get_string('archivesubmissioncount', 'uckkchallenge'));
    echo html_writer::tag('dd', (string) $preview->submissioncount);
}

if (!empty($preview->proofcount)) {
    echo html_writer::tag('dt', get_string('archiveproofcount', 'uckkchallenge'));
    echo html_writer::tag('dd', (string) $preview->proofcount);
}

if (!empty($preview->evaluationcount)) {
    echo html_writer::tag('dt', get_string('archiveevaluationcount', 'uckkchallenge'));
    echo html_writer::tag('dd', (string) $preview->evaluationcount);
}

if (!empty($preview->integritystate)) {
    echo html_writer::tag('dt', get_string('integritystate', 'uckkchallenge'));
    echo html_writer::tag('dd', s($preview->integritystate));
}

echo html_writer::tag('dt', get_string('archivevisibility', 'uckkchallenge'));
echo html_writer::tag('dd', get_string('archivevisibility:' . $visibility, 'uckkchallenge'));

echo html_writer::end_tag('dl');

if (!empty($preview->warnings)) {
    echo html_writer::start_div('alert alert-warning uckkchallenge-archive-confirmation__warnings', ['role' => 'status']);
    echo html_writer::tag('strong', get_string('archivewarnings', 'uckkchallenge'));

    echo html_writer::start_tag('ul');
    foreach ($preview->warnings as $warning) {
        echo html_writer::tag('li', s($warning));
    }
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'uckkchallenge-archive-confirmation__form',
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $cm->id,
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'confirm',
    'value' => 1,
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'returnurl',
    'value' => $return->out_as_local_url(false),
]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('archivevisibility', 'uckkchallenge'), [
    'for' => 'id_visibility',
]);

$options = [];
foreach ($allowedvisibilities as $option) {
    $options[$option] = get_string('archivevisibility:' . $option, 'uckkchallenge');
}

echo html_writer::select($options, 'visibility', $visibility, false, [
    'id' => 'id_visibility',
    'class' => 'custom-select form-control',
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('archivereason', 'uckkchallenge'), [
    'for' => 'id_reason',
]);

echo html_writer::tag('', '', []);

echo html_writer::tag('textarea', s($reason), [
    'id' => 'id_reason',
    'name' => 'reason',
    'class' => 'form-control',
    'rows' => 4,
    'maxlength' => 2000,
]);
echo html_writer::end_div();

echo html_writer::start_div('uckkchallenge-archive-confirmation__actions');

echo html_writer::tag('button', get_string('confirmarchivechallenge', 'uckkchallenge'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::link($return, get_string('cancel'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();

echo html_writer::end_tag('form');

echo html_writer::end_div();

echo $OUTPUT->footer();