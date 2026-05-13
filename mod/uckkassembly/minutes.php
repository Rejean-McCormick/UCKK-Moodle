<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Minutes controller for a UCKK assembly.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkassembly\local\minutes_service;

/**
 * Render a small POST action form.
 *
 * @param moodle_url $actionurl Form action URL.
 * @param string $action Action key.
 * @param string $label Button label.
 * @param string $buttonclass Button CSS classes.
 * @param array<string, scalar> $hidden Hidden fields.
 * @param bool $requiresummary Whether to render a summary textarea.
 */
function uckkassembly_render_minutes_action_form(
    moodle_url $actionurl,
    string $action,
    string $label,
    string $buttonclass,
    array $hidden = [],
    bool $requiresummary = false
): void {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $actionurl->out(false),
        'class' => 'uckkassembly-minutes__action-form',
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
        $id = 'id_' . clean_param($action, PARAM_ALPHANUMEXT) . '_summary';

        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', get_string('summary', 'uckkassembly'), [
            'for' => $id,
        ]);
        echo html_writer::tag('textarea', '', [
            'id' => $id,
            'name' => 'summary',
            'class' => 'form-control',
            'rows' => 4,
            'maxlength' => 4000,
            'required' => 'required',
        ]);
        echo html_writer::end_div();
    }

    echo html_writer::tag('button', $label, [
        'type' => 'submit',
        'class' => $buttonclass,
    ]);

    echo html_writer::end_tag('form');
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Assembly instance id.
$minutesid = optional_param('minutesid', 0, PARAM_INT);
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

$pageurl = new moodle_url('/mod/uckkassembly/minutes.php', ['id' => $cm->id]);
$viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]);
$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));

require_login($course, false, $cm);
require_capability('mod/uckkassembly:view', $context);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new minutes_service();

$allowedactions = [
    '',
    'generate',
    'publish',
    'archive',
    'unpublish',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invalidminutesaction', 'uckkassembly');
}

$allowedvisibilities = [
    'private',
    'course',
    'cohort',
    'program',
    'institution',
    'public',
    'restricted_integrity',
];

if (!in_array($visibility, $allowedvisibilities, true)) {
    throw new moodle_exception('invalidminutesvisibility', 'uckkassembly');
}

if ($action !== '') {
    require_sesskey();

    switch ($action) {
        case 'generate':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if (!$service->can_generate_minutes($assembly, $cm, $context, $USER)) {
                throw new moodle_exception('cannotgenerateminutes', 'uckkassembly');
            }

            $minutes = $service->generate_minutes($assembly, $cm, $course, $context, $USER, [
                'visibility' => $visibility,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/minutes.php', [
                    'id' => $cm->id,
                    'minutesid' => $minutes->id,
                ]),
                get_string('minutesgenerated', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'publish':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if ($minutesid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'minutesid');
            }

            if (!$service->can_publish_minutes($assembly, $cm, $context, $USER, $minutesid)) {
                throw new moodle_exception('cannotpublishminutes', 'uckkassembly');
            }

            $summary = required_param('summary', PARAM_TEXT);

            $service->publish_minutes($assembly, $cm, $course, $context, $USER, $minutesid, [
                'summary' => $summary,
                'visibility' => $visibility,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/minutes.php', [
                    'id' => $cm->id,
                    'minutesid' => $minutesid,
                ]),
                get_string('minutespublished', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'archive':
            require_capability('mod/uckkassembly:archive', $context);

            if ($minutesid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'minutesid');
            }

            if (!$service->can_archive_minutes($assembly, $cm, $context, $USER, $minutesid)) {
                throw new moodle_exception('cannotarchiveminutes', 'uckkassembly');
            }

            $summary = required_param('summary', PARAM_TEXT);

            $archiveitem = $service->archive_minutes($assembly, $cm, $course, $context, $USER, $minutesid, [
                'summary' => $summary,
                'visibility' => $visibility,
            ]);

            if (!empty($archiveitem->url)) {
                redirect(
                    new moodle_url($archiveitem->url),
                    get_string('minutesarchived', 'uckkassembly'),
                    null,
                    notification::NOTIFY_SUCCESS
                );
            }

            redirect(
                new moodle_url('/mod/uckkassembly/minutes.php', [
                    'id' => $cm->id,
                    'minutesid' => $minutesid,
                ]),
                get_string('minutesarchived', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'unpublish':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if ($minutesid <= 0) {
                throw new moodle_exception('missingparam', 'error', '', 'minutesid');
            }

            if (!$service->can_unpublish_minutes($assembly, $cm, $context, $USER, $minutesid)) {
                throw new moodle_exception('cannotunpublishminutes', 'uckkassembly');
            }

            $summary = required_param('summary', PARAM_TEXT);

            $service->unpublish_minutes($assembly, $cm, $course, $context, $USER, $minutesid, [
                'summary' => $summary,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/minutes.php', [
                    'id' => $cm->id,
                    'minutesid' => $minutesid,
                ]),
                get_string('minutesunpublished', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;
    }
}

$minutes = $service->get_minutes_view($assembly, $cm, $course, $context, $USER, $minutesid);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('assemblyminutes', 'uckkassembly'));

echo html_writer::start_div('uckkassembly-minutes');

echo html_writer::start_div('uckkassembly-minutes__header card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', format_string($assembly->name), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkassembly-minutes__facts']);

echo html_writer::tag('dt', get_string('assembly', 'uckkassembly'));
echo html_writer::tag('dd', format_string($assembly->name));

if (!empty($minutes->assemblytype)) {
    echo html_writer::tag('dt', get_string('assemblytype', 'uckkassembly'));
    echo html_writer::tag('dd', s($minutes->assemblytype));
}

if (!empty($minutes->status)) {
    echo html_writer::tag('dt', get_string('status'));
    echo html_writer::tag('dd', s($minutes->statuslabel ?? $minutes->status));
}

if (!empty($minutes->visibility)) {
    echo html_writer::tag('dt', get_string('visibility', 'uckkassembly'));
    echo html_writer::tag('dd', s($minutes->visibilitylabel ?? $minutes->visibility));
}

if (!empty($minutes->timecreated)) {
    echo html_writer::tag('dt', get_string('timecreated', 'uckkassembly'));
    echo html_writer::tag('dd', userdate($minutes->timecreated));
}

if (!empty($minutes->timemodified)) {
    echo html_writer::tag('dt', get_string('timemodified', 'moodle'));
    echo html_writer::tag('dd', userdate($minutes->timemodified));
}

echo html_writer::end_tag('dl');

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($minutes->warnings)) {
    echo html_writer::start_div('alert alert-warning uckkassembly-minutes__warnings', ['role' => 'status']);
    echo html_writer::tag('strong', get_string('minuteswarnings', 'uckkassembly'));

    echo html_writer::start_tag('ul');
    foreach ($minutes->warnings as $warning) {
        echo html_writer::tag('li', s($warning));
    }
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
}

if (!empty($minutes->contenthtml)) {
    echo html_writer::start_div('uckkassembly-minutes__content card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('minutescontent', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::div($minutes->contenthtml, 'uckkassembly-minutes__body');

    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::div(
        get_string('minutesnotgenerated', 'uckkassembly'),
        'alert alert-info uckkassembly-minutes__empty',
        ['role' => 'status']
    );
}

if (!empty($minutes->sections)) {
    echo html_writer::start_div('uckkassembly-minutes__sections');

    foreach ($minutes->sections as $section) {
        echo html_writer::start_div('uckkassembly-minutes__section card mb-3');
        echo html_writer::start_div('card-body');

        echo html_writer::tag('h3', format_string($section->title ?? ''), [
            'class' => 'card-title',
        ]);

        if (!empty($section->contenthtml)) {
            echo html_writer::div($section->contenthtml, 'uckkassembly-minutes__section-content');
        } else if (!empty($section->content)) {
            echo html_writer::tag('p', s($section->content));
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

if (!empty($minutes->motions)) {
    echo html_writer::start_div('uckkassembly-minutes__motions card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('motions', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkassembly-minutes__motion-list']);

    foreach ($minutes->motions as $motion) {
        echo html_writer::start_tag('li', ['class' => 'uckkassembly-minutes__motion']);

        if (!empty($motion->url)) {
            echo html_writer::link(new moodle_url($motion->url), format_string($motion->title ?? ''));
        } else {
            echo html_writer::tag('strong', format_string($motion->title ?? ''));
        }

        if (!empty($motion->statuslabel)) {
            echo html_writer::span(s($motion->statuslabel), 'badge badge-secondary ml-2');
        }

        if (!empty($motion->summary)) {
            echo html_writer::tag('p', s($motion->summary));
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($minutes->decisions)) {
    echo html_writer::start_div('uckkassembly-minutes__decisions card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('decisions', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkassembly-minutes__decision-list']);

    foreach ($minutes->decisions as $decision) {
        echo html_writer::start_tag('li', ['class' => 'uckkassembly-minutes__decision']);

        if (!empty($decision->url)) {
            echo html_writer::link(new moodle_url($decision->url), format_string($decision->title ?? ''));
        } else {
            echo html_writer::tag('strong', format_string($decision->title ?? ''));
        }

        if (!empty($decision->decisiontype)) {
            echo html_writer::span(s($decision->decisiontype), 'badge badge-info ml-2');
        }

        if (!empty($decision->summary)) {
            echo html_writer::tag('p', s($decision->summary));
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($minutes->minorityreports)) {
    echo html_writer::start_div('uckkassembly-minutes__minority-reports card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('minorityreports', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul');

    foreach ($minutes->minorityreports as $report) {
        echo html_writer::start_tag('li');

        echo html_writer::tag('strong', format_string($report->title ?? get_string('minorityreport', 'uckkassembly')));

        if (!empty($report->summary)) {
            echo html_writer::tag('p', s($report->summary));
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('uckkassembly-minutes__actions card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('minutesactions', 'uckkassembly'), [
    'class' => 'card-title',
]);

if ($service->can_generate_minutes($assembly, $cm, $context, $USER)) {
    echo html_writer::start_div('mb-3');

    echo html_writer::tag('label', get_string('minutesvisibility', 'uckkassembly'), [
        'for' => 'id_minutes_visibility',
        'class' => 'sr-only',
    ]);

    uckkassembly_render_minutes_action_form(
        $pageurl,
        'generate',
        get_string('generateminutes', 'uckkassembly'),
        'btn btn-primary',
        [
            'id' => $cm->id,
            'visibility' => $visibility,
        ]
    );

    echo html_writer::end_div();
}

if (!empty($minutes->id) && $service->can_publish_minutes($assembly, $cm, $context, $USER, (int)$minutes->id)) {
    echo html_writer::start_div('mb-3');

    uckkassembly_render_minutes_action_form(
        $pageurl,
        'publish',
        get_string('publishminutes', 'uckkassembly'),
        'btn btn-primary',
        [
            'id' => $cm->id,
            'minutesid' => (int)$minutes->id,
            'visibility' => $visibility,
        ],
        true
    );

    echo html_writer::end_div();
}

if (!empty($minutes->id) && $service->can_unpublish_minutes($assembly, $cm, $context, $USER, (int)$minutes->id)) {
    echo html_writer::start_div('mb-3');

    uckkassembly_render_minutes_action_form(
        $pageurl,
        'unpublish',
        get_string('unpublishminutes', 'uckkassembly'),
        'btn btn-secondary',
        [
            'id' => $cm->id,
            'minutesid' => (int)$minutes->id,
        ],
        true
    );

    echo html_writer::end_div();
}

if (!empty($minutes->id) && $service->can_archive_minutes($assembly, $cm, $context, $USER, (int)$minutes->id)) {
    echo html_writer::start_div('mb-3');

    uckkassembly_render_minutes_action_form(
        $pageurl,
        'archive',
        get_string('archiveminutes', 'uckkassembly'),
        'btn btn-secondary',
        [
            'id' => $cm->id,
            'minutesid' => (int)$minutes->id,
            'visibility' => $visibility,
        ],
        true
    );

    echo html_writer::end_div();
}

echo html_writer::link($return, get_string('backtoassembly', 'uckkassembly'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    get_string('minutesgovernancenotice', 'uckkassembly'),
    'alert alert-info uckkassembly-minutes__notice',
    ['role' => 'status']
);

echo html_writer::end_div();

echo $OUTPUT->footer();