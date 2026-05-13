<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Integrity controller for a UCKK challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkchallenge\local\integrity_service;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT); // Challenge instance id.
$action = optional_param('action', '', PARAM_ALPHAEXT);
$caseid = optional_param('caseid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

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

$viewurl = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($challenge->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new integrity_service();

$allowedactions = [
    '',
    'open',
    'contest',
    'correction',
    'invalidate',
    'close',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invalidintegrityaction', 'uckkchallenge');
}

if ($action !== '') {
    require_sesskey();

    $summary = required_param('summary', PARAM_TEXT);
    $notes = optional_param('notes', '', PARAM_TEXT);

    switch ($action) {
        case 'open':
            require_capability('tool/uckkintegrity:opencase', $context);

            if (!$service->can_open_case($challenge, $cm, $context, $USER)) {
                throw new moodle_exception('cannotopenintegritycase', 'uckkchallenge');
            }

            $case = $service->open_case($challenge, $cm, $course, $context, $USER, [
                'type' => 'challenge_dispute',
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $case->id]),
                get_string('integritycaseopened', 'uckkchallenge'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'contest':
            require_capability('mod/uckkchallenge:contest', $context);

            if (!$service->can_contest($challenge, $cm, $context, $USER)) {
                throw new moodle_exception('cannotcontestchallenge', 'uckkchallenge');
            }

            $case = $service->contest_challenge($challenge, $cm, $course, $context, $USER, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $case->id]),
                get_string('challengecontested', 'uckkchallenge'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'correction':
            require_capability('mod/uckkchallenge:validateintegrity', $context);
            require_capability('tool/uckkintegrity:issuecorrection', $context);

            if (!$caseid) {
                throw new moodle_exception('missingparam', 'error', '', 'caseid');
            }

            if (!$service->can_issue_correction($challenge, $cm, $context, $USER, $caseid)) {
                throw new moodle_exception('cannotissuecorrection', 'uckkchallenge');
            }

            $service->issue_correction($challenge, $cm, $course, $context, $USER, $caseid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $caseid]),
                get_string('correctionissued', 'uckkchallenge'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'invalidate':
            require_capability('mod/uckkchallenge:validateintegrity', $context);
            require_capability('tool/uckkintegrity:invalidate', $context);

            if (!$caseid) {
                throw new moodle_exception('missingparam', 'error', '', 'caseid');
            }

            if (!$service->can_invalidate($challenge, $cm, $context, $USER, $caseid)) {
                throw new moodle_exception('cannotinvalidatechallenge', 'uckkchallenge');
            }

            $service->invalidate_challenge($challenge, $cm, $course, $context, $USER, $caseid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $caseid]),
                get_string('challengeinvalidated', 'uckkchallenge'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'close':
            require_capability('tool/uckkintegrity:closecase', $context);

            if (!$caseid) {
                throw new moodle_exception('missingparam', 'error', '', 'caseid');
            }

            if (!$service->can_close_case($challenge, $cm, $context, $USER, $caseid)) {
                throw new moodle_exception('cannotcloseintegritycase', 'uckkchallenge');
            }

            $service->close_case($challenge, $cm, $course, $context, $USER, $caseid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $caseid]),
                get_string('integritycaseclosed', 'uckkchallenge'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;
    }
}

require_capability('tool/uckkintegrity:view', $context);

$integrity = $service->get_integrity_summary($challenge, $cm, $course, $context, $USER, $caseid);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('integrityreview', 'uckkchallenge'));

echo html_writer::start_div('uckkchallenge-integrity');

echo html_writer::start_div('uckkchallenge-integrity__summary card');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('challengeintegritysummary', 'uckkchallenge'), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkchallenge-integrity__facts']);

echo html_writer::tag('dt', get_string('challenge', 'uckkchallenge'));
echo html_writer::tag('dd', format_string($challenge->name));

echo html_writer::tag('dt', get_string('challengestatus', 'uckkchallenge'));
echo html_writer::tag('dd', s($integrity->challengestatus ?? $challenge->status ?? ''));

echo html_writer::tag('dt', get_string('integritystate', 'uckkchallenge'));
echo html_writer::tag('dd', s($integrity->integritystate ?? get_string('integritystate:unverified', 'uckkchallenge')));

echo html_writer::tag('dt', get_string('openintegritycases', 'uckkchallenge'));
echo html_writer::tag('dd', (string)($integrity->opencasecount ?? 0));

echo html_writer::tag('dt', get_string('restrictedvisibility', 'uckkchallenge'));
echo html_writer::tag('dd', !empty($integrity->hasrestricteddata) ? get_string('yes') : get_string('no'));

echo html_writer::end_tag('dl');

if (!empty($integrity->warnings)) {
    echo html_writer::start_div('alert alert-warning', ['role' => 'status']);
    echo html_writer::tag('strong', get_string('integritywarnings', 'uckkchallenge'));

    echo html_writer::start_tag('ul');
    foreach ($integrity->warnings as $warning) {
        echo html_writer::tag('li', s($warning));
    }
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($integrity->cases)) {
    echo html_writer::start_div('uckkchallenge-integrity__cases');
    echo html_writer::tag('h3', get_string('integritycases', 'uckkchallenge'));

    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('caseid', 'uckkchallenge'));
    echo html_writer::tag('th', get_string('casetype', 'uckkchallenge'));
    echo html_writer::tag('th', get_string('status'));
    echo html_writer::tag('th', get_string('summary', 'uckkchallenge'));
    echo html_writer::tag('th', get_string('timemodified', 'moodle'));
    echo html_writer::tag('th', get_string('actions'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($integrity->cases as $case) {
        $caseurl = new moodle_url('/mod/uckkchallenge/integrity.php', [
            'id' => $cm->id,
            'caseid' => $case->id,
        ]);

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', (string)$case->id);
        echo html_writer::tag('td', s($case->casetype ?? 'challenge_dispute'));
        echo html_writer::tag('td', s($case->status ?? 'opened'));
        echo html_writer::tag('td', s($case->summary ?? ''));
        echo html_writer::tag('td', !empty($case->timemodified) ? userdate($case->timemodified) : '-');
        echo html_writer::tag('td', html_writer::link($caseurl, get_string('view')));
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::start_div('uckkchallenge-integrity__actions');

echo html_writer::tag('h3', get_string('integrityactions', 'uckkchallenge'));

if ($service->can_open_case($challenge, $cm, $context, $USER)) {
    echo html_writer::start_div('uckkchallenge-integrity__action card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h4', get_string('openintegritycase', 'uckkchallenge'));

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]);

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'open']);

    echo html_writer::tag('label', get_string('summary', 'uckkchallenge'), ['for' => 'id_open_summary']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'id_open_summary',
        'name' => 'summary',
        'class' => 'form-control',
        'maxlength' => 255,
        'required' => 'required',
    ]);

    echo html_writer::tag('label', get_string('notes', 'uckkchallenge'), ['for' => 'id_open_notes']);
    echo html_writer::tag('textarea', '', [
        'id' => 'id_open_notes',
        'name' => 'notes',
        'class' => 'form-control',
        'rows' => 4,
        'maxlength' => 4000,
    ]);

    echo html_writer::tag('button', get_string('openintegritycase', 'uckkchallenge'), [
        'type' => 'submit',
        'class' => 'btn btn-primary mt-2',
    ]);

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($service->can_contest($challenge, $cm, $context, $USER)) {
    echo html_writer::start_div('uckkchallenge-integrity__action card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h4', get_string('contestchallenge', 'uckkchallenge'));

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]);

    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'contest']);

    echo html_writer::tag('label', get_string('summary', 'uckkchallenge'), ['for' => 'id_contest_summary']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'id_contest_summary',
        'name' => 'summary',
        'class' => 'form-control',
        'maxlength' => 255,
        'required' => 'required',
    ]);

    echo html_writer::tag('label', get_string('notes', 'uckkchallenge'), ['for' => 'id_contest_notes']);
    echo html_writer::tag('textarea', '', [
        'id' => 'id_contest_notes',
        'name' => 'notes',
        'class' => 'form-control',
        'rows' => 4,
        'maxlength' => 4000,
    ]);

    echo html_writer::tag('button', get_string('contestchallenge', 'uckkchallenge'), [
        'type' => 'submit',
        'class' => 'btn btn-secondary mt-2',
    ]);

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($caseid > 0 && has_capability('mod/uckkchallenge:validateintegrity', $context)) {
    echo html_writer::start_div('uckkchallenge-integrity__privileged-actions card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h4', get_string('inquisiteuractions', 'uckkchallenge'));

    $privilegedactions = [
        'correction' => get_string('issuecorrection', 'uckkchallenge'),
        'invalidate' => get_string('invalidatechallenge', 'uckkchallenge'),
        'close' => get_string('closeintegritycase', 'uckkchallenge'),
    ];

    foreach ($privilegedactions as $privilegedaction => $label) {
        $allowed = false;

        if ($privilegedaction === 'correction') {
            $allowed = $service->can_issue_correction($challenge, $cm, $context, $USER, $caseid);
        } else if ($privilegedaction === 'invalidate') {
            $allowed = $service->can_invalidate($challenge, $cm, $context, $USER, $caseid);
        } else if ($privilegedaction === 'close') {
            $allowed = $service->can_close_case($challenge, $cm, $context, $USER, $caseid);
        }

        if (!$allowed) {
            continue;
        }

        echo html_writer::start_div('uckkchallenge-integrity__privileged-action');
        echo html_writer::tag('h5', $label);

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
        ]);

        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'caseid', 'value' => $caseid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $privilegedaction]);

        echo html_writer::tag('label', get_string('summary', 'uckkchallenge'), [
            'for' => 'id_' . $privilegedaction . '_summary',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => 'id_' . $privilegedaction . '_summary',
            'name' => 'summary',
            'class' => 'form-control',
            'maxlength' => 255,
            'required' => 'required',
        ]);

        echo html_writer::tag('label', get_string('notes', 'uckkchallenge'), [
            'for' => 'id_' . $privilegedaction . '_notes',
        ]);
        echo html_writer::tag('textarea', '', [
            'id' => 'id_' . $privilegedaction . '_notes',
            'name' => 'notes',
            'class' => 'form-control',
            'rows' => 4,
            'maxlength' => 4000,
        ]);

        $buttonclass = $privilegedaction === 'invalidate' ? 'btn btn-danger mt-2' : 'btn btn-primary mt-2';

        echo html_writer::tag('button', $label, [
            'type' => 'submit',
            'class' => $buttonclass,
        ]);

        echo html_writer::end_tag('form');
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();

echo html_writer::div(
    get_string('integritynonsovereignnotice', 'uckkchallenge'),
    'alert alert-info uckkchallenge-integrity__notice',
    ['role' => 'status']
);

echo html_writer::link($return, get_string('backtochallenge', 'uckkchallenge'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();

echo $OUTPUT->footer();