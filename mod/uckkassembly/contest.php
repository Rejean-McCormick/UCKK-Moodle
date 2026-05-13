<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Contestation controller for a UCKK assembly.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkassembly\local\contest_service;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Assembly instance id.
$contestid = optional_param('contestid', 0, PARAM_INT);
$motionid = optional_param('motionid', 0, PARAM_INT);
$decisionid = optional_param('decisionid', 0, PARAM_INT);
$minutesid = optional_param('minutesid', 0, PARAM_INT);
$subjecttype = optional_param('subjecttype', 'decision', PARAM_ALPHAEXT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$visibility = optional_param('visibility', 'course', PARAM_ALPHAEXT);

if ($id) {
    $cm = get_coursemodule_from_id('uckkassembly', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $assembly = $DB->get_record('uckkassembly', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $assembly->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkassembly', $assembly->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkassembly:view', $context);

$pageurl = new moodle_url('/mod/uckkassembly/contest.php', ['id' => $cm->id]);
$viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]);
$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$allowedactions = [
    '',
    'open',
    'respond',
    'withdraw',
    'resolve',
    'dismiss',
    'archive',
];

$allowedsubjecttypes = [
    'motion',
    'amendment',
    'vote',
    'decision',
    'minutes',
    'publication',
];

$allowedvisibilities = [
    'private',
    'user',
    'group',
    'course',
    'cohort',
    'program',
    'institution',
    'public',
    'restricted',
    'restricted_integrity',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invalidcontestaction', 'uckkassembly');
}

if (!in_array($subjecttype, $allowedsubjecttypes, true)) {
    throw new moodle_exception('invalidcontestsubject', 'uckkassembly');
}

if (!in_array($visibility, $allowedvisibilities, true)) {
    throw new moodle_exception('invalidcontestvisibility', 'uckkassembly');
}

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new contest_service();

$renderactionform = static function (
    moodle_url $url,
    string $action,
    string $label,
    string $buttonclass,
    array $hidden,
    bool $requiresummary = true,
    bool $requirenotes = false
): void {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $url->out(false),
        'class' => 'uckkassembly-contest__action-form mb-3',
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $action,
    ]);

    foreach ($hidden as $name => $value) {
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ]);
    }

    if ($requiresummary) {
        $summaryid = 'id_' . clean_param($action, PARAM_ALPHANUMEXT) . '_summary';

        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('summary', 'uckkassembly'), [
            'for' => $summaryid,
        ]);
        echo html_writer::tag('textarea', '', [
            'id' => $summaryid,
            'name' => 'summary',
            'class' => 'form-control',
            'rows' => 4,
            'maxlength' => 4000,
            'required' => 'required',
        ]);
        echo html_writer::end_div();
    }

    if ($requirenotes) {
        $notesid = 'id_' . clean_param($action, PARAM_ALPHANUMEXT) . '_notes';

        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('notes', 'uckkassembly'), [
            'for' => $notesid,
        ]);
        echo html_writer::tag('textarea', '', [
            'id' => $notesid,
            'name' => 'notes',
            'class' => 'form-control',
            'rows' => 4,
            'maxlength' => 4000,
        ]);
        echo html_writer::end_div();
    }

    echo html_writer::tag('button', $label, [
        'type' => 'submit',
        'class' => $buttonclass,
    ]);

    echo html_writer::end_tag('form');
};

if ($action !== '') {
    require_sesskey();

    switch ($action) {
        case 'open':
            require_capability('mod/uckkassembly:contestdecision', $context);

            $summary = required_param('summary', PARAM_TEXT);
            $notes = optional_param('notes', '', PARAM_TEXT);

            if (!$service->can_open_contestation($assembly, $cm, $context, $USER, [
                'subjecttype' => $subjecttype,
                'motionid' => $motionid,
                'decisionid' => $decisionid,
                'minutesid' => $minutesid,
            ])) {
                throw new moodle_exception('cannotopencontest', 'uckkassembly');
            }

            $contest = $service->open_contestation($assembly, $cm, $course, $context, $USER, [
                'subjecttype' => $subjecttype,
                'motionid' => $motionid,
                'decisionid' => $decisionid,
                'minutesid' => $minutesid,
                'summary' => $summary,
                'notes' => $notes,
                'visibility' => $visibility,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contest->id,
                ]),
                get_string('contestopened', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'respond':
            require_capability('mod/uckkassembly:contestdecision', $context);

            if ($contestid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'contestid');
            }

            $summary = required_param('summary', PARAM_TEXT);
            $notes = optional_param('notes', '', PARAM_TEXT);

            if (!$service->can_respond_to_contestation($assembly, $cm, $context, $USER, $contestid)) {
                throw new moodle_exception('cannotrespondcontest', 'uckkassembly');
            }

            $service->respond_to_contestation($assembly, $cm, $course, $context, $USER, $contestid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contestid,
                ]),
                get_string('contestresponseadded', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'withdraw':
            require_capability('mod/uckkassembly:contestdecision', $context);

            if ($contestid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'contestid');
            }

            $summary = required_param('summary', PARAM_TEXT);

            if (!$service->can_withdraw_contestation($assembly, $cm, $context, $USER, $contestid)) {
                throw new moodle_exception('cannotwithdrawcontest', 'uckkassembly');
            }

            $service->withdraw_contestation($assembly, $cm, $course, $context, $USER, $contestid, [
                'summary' => $summary,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contestid,
                ]),
                get_string('contestwithdrawn', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'resolve':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if ($contestid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'contestid');
            }

            $summary = required_param('summary', PARAM_TEXT);
            $notes = optional_param('notes', '', PARAM_TEXT);

            if (!$service->can_resolve_contestation($assembly, $cm, $context, $USER, $contestid)) {
                throw new moodle_exception('cannotresolvecontest', 'uckkassembly');
            }

            $service->resolve_contestation($assembly, $cm, $course, $context, $USER, $contestid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contestid,
                ]),
                get_string('contestresolved', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'dismiss':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if ($contestid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'contestid');
            }

            $summary = required_param('summary', PARAM_TEXT);
            $notes = optional_param('notes', '', PARAM_TEXT);

            if (!$service->can_dismiss_contestation($assembly, $cm, $context, $USER, $contestid)) {
                throw new moodle_exception('cannotdismisscontest', 'uckkassembly');
            }

            $service->dismiss_contestation($assembly, $cm, $course, $context, $USER, $contestid, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contestid,
                ]),
                get_string('contestdismissed', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'archive':
            require_capability('mod/uckkassembly:archive', $context);

            if ($contestid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'contestid');
            }

            $summary = required_param('summary', PARAM_TEXT);

            if (!$service->can_archive_contestation($assembly, $cm, $context, $USER, $contestid)) {
                throw new moodle_exception('cannotarchivecontest', 'uckkassembly');
            }

            $archiveitem = $service->archive_contestation($assembly, $cm, $course, $context, $USER, $contestid, [
                'summary' => $summary,
                'visibility' => $visibility,
            ]);

            if (!empty($archiveitem->url)) {
                redirect(
                    new moodle_url($archiveitem->url),
                    get_string('contestarchived', 'uckkassembly'),
                    null,
                    notification::NOTIFY_SUCCESS
                );
            }

            redirect(
                new moodle_url('/mod/uckkassembly/contest.php', [
                    'id' => $cm->id,
                    'contestid' => $contestid,
                ]),
                get_string('contestarchived', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;
    }
}

$contestview = $service->get_contestation_view($assembly, $cm, $course, $context, $USER, [
    'contestid' => $contestid,
    'subjecttype' => $subjecttype,
    'motionid' => $motionid,
    'decisionid' => $decisionid,
    'minutesid' => $minutesid,
]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('assemblycontestation', 'uckkassembly'));

echo html_writer::start_div('uckkassembly-contest');

echo html_writer::start_div('uckkassembly-contest__summary card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('contestsummary', 'uckkassembly'), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkassembly-contest__facts']);

echo html_writer::tag('dt', get_string('assembly', 'uckkassembly'));
echo html_writer::tag('dd', format_string($assembly->name));

if (!empty($contestview->subjecttype)) {
    echo html_writer::tag('dt', get_string('contestsubject', 'uckkassembly'));
    echo html_writer::tag('dd', s($contestview->subjecttypelabel ?? $contestview->subjecttype));
}

if (!empty($contestview->subjecttitle)) {
    echo html_writer::tag('dt', get_string('subject', 'uckkassembly'));
    echo html_writer::tag('dd', format_string($contestview->subjecttitle));
}

if (!empty($contestview->status)) {
    echo html_writer::tag('dt', get_string('status'));
    echo html_writer::tag('dd', s($contestview->statuslabel ?? $contestview->status));
}

if (!empty($contestview->visibility)) {
    echo html_writer::tag('dt', get_string('visibility', 'uckkassembly'));
    echo html_writer::tag('dd', s($contestview->visibilitylabel ?? $contestview->visibility));
}

if (!empty($contestview->openedbylabel)) {
    echo html_writer::tag('dt', get_string('openedby', 'uckkassembly'));
    echo html_writer::tag('dd', s($contestview->openedbylabel));
}

if (!empty($contestview->timecreated)) {
    echo html_writer::tag('dt', get_string('timecreated', 'uckkassembly'));
    echo html_writer::tag('dd', userdate($contestview->timecreated));
}

if (!empty($contestview->timemodified)) {
    echo html_writer::tag('dt', get_string('timemodified', 'moodle'));
    echo html_writer::tag('dd', userdate($contestview->timemodified));
}

echo html_writer::end_tag('dl');

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($contestview->warnings)) {
    echo html_writer::start_div('alert alert-warning uckkassembly-contest__warnings', ['role' => 'status']);
    echo html_writer::tag('strong', get_string('contestwarnings', 'uckkassembly'));

    echo html_writer::start_tag('ul');
    foreach ($contestview->warnings as $warning) {
        echo html_writer::tag('li', s($warning));
    }
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
}

if (!empty($contestview->summary)) {
    echo html_writer::start_div('uckkassembly-contest__body card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('conteststatement', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::tag('p', s($contestview->summary));

    if (!empty($contestview->notes)) {
        echo html_writer::tag('h4', get_string('notes', 'uckkassembly'));
        echo html_writer::tag('p', s($contestview->notes));
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($contestview->entries)) {
    echo html_writer::start_div('uckkassembly-contest__entries card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('contesthistory', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ol', ['class' => 'uckkassembly-contest__entry-list']);

    foreach ($contestview->entries as $entry) {
        echo html_writer::start_tag('li', ['class' => 'uckkassembly-contest__entry']);

        echo html_writer::tag('strong', s($entry->typelabel ?? $entry->type ?? get_string('entry', 'uckkassembly')));

        if (!empty($entry->authorlabel)) {
            echo html_writer::span(' — ' . s($entry->authorlabel), 'uckkassembly-contest__entry-author');
        }

        if (!empty($entry->timecreated)) {
            echo html_writer::span(' — ' . userdate($entry->timecreated), 'uckkassembly-contest__entry-date');
        }

        if (!empty($entry->summary)) {
            echo html_writer::tag('p', s($entry->summary));
        }

        if (!empty($entry->notes)) {
            echo html_writer::tag('p', s($entry->notes), [
                'class' => 'uckkassembly-contest__entry-notes',
            ]);
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ol');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($contestview->relateditems)) {
    echo html_writer::start_div('uckkassembly-contest__related card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('relatedrecords', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul');

    foreach ($contestview->relateditems as $item) {
        echo html_writer::start_tag('li');

        if (!empty($item->url)) {
            echo html_writer::link(new moodle_url($item->url), format_string($item->title ?? ''));
        } else {
            echo format_string($item->title ?? '');
        }

        if (!empty($item->typelabel)) {
            echo html_writer::span(' — ' . s($item->typelabel), 'uckkassembly-contest__related-type');
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('uckkassembly-contest__actions card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('contestactions', 'uckkassembly'), [
    'class' => 'card-title',
]);

$hiddenbase = [
    'id' => $cm->id,
    'subjecttype' => $subjecttype,
    'motionid' => $motionid,
    'decisionid' => $decisionid,
    'minutesid' => $minutesid,
    'visibility' => $visibility,
];

if ($service->can_open_contestation($assembly, $cm, $context, $USER, [
    'subjecttype' => $subjecttype,
    'motionid' => $motionid,
    'decisionid' => $decisionid,
    'minutesid' => $minutesid,
])) {
    $renderactionform(
        $pageurl,
        'open',
        get_string('opencontest', 'uckkassembly'),
        'btn btn-primary',
        $hiddenbase,
        true,
        true
    );
}

if ($contestid > 0 && $service->can_respond_to_contestation($assembly, $cm, $context, $USER, $contestid)) {
    $renderactionform(
        $pageurl,
        'respond',
        get_string('respondtocontest', 'uckkassembly'),
        'btn btn-secondary',
        $hiddenbase + ['contestid' => $contestid],
        true,
        true
    );
}

if ($contestid > 0 && $service->can_withdraw_contestation($assembly, $cm, $context, $USER, $contestid)) {
    $renderactionform(
        $pageurl,
        'withdraw',
        get_string('withdrawcontest', 'uckkassembly'),
        'btn btn-secondary',
        $hiddenbase + ['contestid' => $contestid],
        true,
        false
    );
}

if ($contestid > 0 && $service->can_resolve_contestation($assembly, $cm, $context, $USER, $contestid)) {
    $renderactionform(
        $pageurl,
        'resolve',
        get_string('resolvecontest', 'uckkassembly'),
        'btn btn-primary',
        $hiddenbase + ['contestid' => $contestid],
        true,
        true
    );
}

if ($contestid > 0 && $service->can_dismiss_contestation($assembly, $cm, $context, $USER, $contestid)) {
    $renderactionform(
        $pageurl,
        'dismiss',
        get_string('dismisscontest', 'uckkassembly'),
        'btn btn-warning',
        $hiddenbase + ['contestid' => $contestid],
        true,
        true
    );
}

if ($contestid > 0 && $service->can_archive_contestation($assembly, $cm, $context, $USER, $contestid)) {
    $renderactionform(
        $pageurl,
        'archive',
        get_string('archivecontest', 'uckkassembly'),
        'btn btn-secondary',
        $hiddenbase + ['contestid' => $contestid],
        true,
        false
    );
}

echo html_writer::link($return, get_string('backtoassembly', 'uckkassembly'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    get_string('contestgovernancenotice', 'uckkassembly'),
    'alert alert-info uckkassembly-contest__notice',
    ['role' => 'status']
);

echo html_writer::end_div();

echo $OUTPUT->footer();