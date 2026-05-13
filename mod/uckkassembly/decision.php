<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Decision controller for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkassembly\local\decision_service;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT); // Assembly instance id.
$decisionid = optional_param('decisionid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($id) {
    $cm = get_coursemodule_from_id('uckkassembly', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($u) {
    $assembly = $DB->get_record('uckkassembly', ['id' => $u], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $assembly->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkassembly', $assembly->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkassembly:view', $context);

$viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkassembly/decision.php', ['id' => $cm->id]);

if ($decisionid > 0) {
    $pageurl->param('decisionid', $decisionid);
}

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new decision_service();

$allowedactions = [
    '',
    'publish',
    'contest',
    'archive',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invaliddecisionaction', 'uckkassembly');
}

$decision = $decisionid > 0
    ? $service->get_decision($decisionid, $assembly, $cm, $context, $USER)
    : $service->get_current_decision($assembly, $cm, $context, $USER);

if (!empty($decision->id)) {
    $decisionid = (int)$decision->id;
    $pageurl->param('decisionid', $decisionid);
}

if ($action !== '') {
    require_sesskey();

    if (empty($decision->id)) {
        throw new moodle_exception('missingdecision', 'uckkassembly');
    }

    $summary = required_param('summary', PARAM_TEXT);
    $notes = optional_param('notes', '', PARAM_TEXT);

    switch ($action) {
        case 'publish':
            require_capability('mod/uckkassembly:publishdecision', $context);

            if (!$confirm) {
                break;
            }

            if (!$service->can_publish_decision($decision, $assembly, $cm, $context, $USER)) {
                throw new moodle_exception('cannotpublishdecision', 'uckkassembly');
            }

            $published = $service->publish_decision($decision, $assembly, $cm, $course, $context, $USER, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/decision.php', [
                    'id' => $cm->id,
                    'decisionid' => $published->id,
                ]),
                get_string('decisionpublished', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'contest':
            require_capability('mod/uckkassembly:contestdecision', $context);

            if (!$confirm) {
                break;
            }

            if (!$service->can_contest_decision($decision, $assembly, $cm, $context, $USER)) {
                throw new moodle_exception('cannotcontestdecision', 'uckkassembly');
            }

            $contestation = $service->contest_decision($decision, $assembly, $cm, $course, $context, $USER, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            redirect(
                new moodle_url('/mod/uckkassembly/decision.php', [
                    'id' => $cm->id,
                    'decisionid' => $decision->id,
                ]),
                get_string('decisioncontested', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;

        case 'archive':
            require_capability('mod/uckkassembly:archive', $context);

            if (!$confirm) {
                break;
            }

            if (!$service->can_archive_decision($decision, $assembly, $cm, $context, $USER)) {
                throw new moodle_exception('cannotarchivedecision', 'uckkassembly');
            }

            $archiveitem = $service->archive_decision($decision, $assembly, $cm, $course, $context, $USER, [
                'summary' => $summary,
                'notes' => $notes,
            ]);

            if (!empty($archiveitem->url)) {
                redirect(
                    new moodle_url($archiveitem->url),
                    get_string('decisionarchived', 'uckkassembly'),
                    null,
                    notification::NOTIFY_SUCCESS
                );
            }

            redirect(
                new moodle_url('/mod/uckkassembly/decision.php', [
                    'id' => $cm->id,
                    'decisionid' => $decision->id,
                ]),
                get_string('decisionarchived', 'uckkassembly'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('assemblydecision', 'uckkassembly'));

echo html_writer::start_div('uckkassembly-decision');

if (empty($decision->id)) {
    echo $OUTPUT->notification(
        get_string('nodecisionavailable', 'uckkassembly'),
        notification::NOTIFY_INFO
    );

    echo $OUTPUT->continue_button($return);
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

if (!$service->can_view_decision($decision, $assembly, $cm, $context, $USER)) {
    echo $OUTPUT->notification(
        get_string('cannotviewdecision', 'uckkassembly'),
        notification::NOTIFY_ERROR
    );

    echo $OUTPUT->continue_button($return);
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

$decisiondata = $service->get_decision_view_data($decision, $assembly, $cm, $course, $context, $USER);

echo html_writer::start_div('uckkassembly-decision__summary card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', format_string($decisiondata->title ?? get_string('decision', 'uckkassembly')), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkassembly-decision__facts']);

echo html_writer::tag('dt', get_string('assembly', 'uckkassembly'));
echo html_writer::tag('dd', format_string($assembly->name));

if (!empty($decisiondata->motiontitle)) {
    echo html_writer::tag('dt', get_string('motion', 'uckkassembly'));
    echo html_writer::tag('dd', format_string($decisiondata->motiontitle));
}

if (!empty($decisiondata->decisionmethod)) {
    echo html_writer::tag('dt', get_string('decisionmethod', 'uckkassembly'));
    echo html_writer::tag('dd', s($decisiondata->decisionmethod));
}

if (!empty($decisiondata->statuslabel)) {
    echo html_writer::tag('dt', get_string('status'));
    echo html_writer::tag('dd', s($decisiondata->statuslabel));
}

if (!empty($decisiondata->visibilitylabel)) {
    echo html_writer::tag('dt', get_string('visibility', 'uckkassembly'));
    echo html_writer::tag('dd', s($decisiondata->visibilitylabel));
}

if (!empty($decisiondata->timepublished)) {
    echo html_writer::tag('dt', get_string('timepublished', 'uckkassembly'));
    echo html_writer::tag('dd', userdate((int)$decisiondata->timepublished));
}

if (!empty($decisiondata->contestabilityuntil)) {
    echo html_writer::tag('dt', get_string('contestabilityuntil', 'uckkassembly'));
    echo html_writer::tag('dd', userdate((int)$decisiondata->contestabilityuntil));
}

echo html_writer::end_tag('dl');

if (!empty($decisiondata->warnings)) {
    echo html_writer::start_div('alert alert-warning', ['role' => 'status']);
    echo html_writer::tag('strong', get_string('decisionwarnings', 'uckkassembly'));

    echo html_writer::start_tag('ul');
    foreach ($decisiondata->warnings as $warning) {
        echo html_writer::tag('li', s($warning));
    }
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('uckkassembly-decision__content card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('decisiontext', 'uckkassembly'), ['class' => 'card-title']);

if (!empty($decisiondata->decisiontext)) {
    echo html_writer::div(
        format_text($decisiondata->decisiontext, $decisiondata->decisiontextformat ?? FORMAT_HTML, ['context' => $context]),
        'uckkassembly-decision__text'
    );
} else {
    echo html_writer::tag('p', get_string('decisiontextempty', 'uckkassembly'), [
        'class' => 'text-muted',
    ]);
}

if (!empty($decisiondata->reasoning)) {
    echo html_writer::tag('h4', get_string('reasoning', 'uckkassembly'));
    echo html_writer::div(
        format_text($decisiondata->reasoning, $decisiondata->reasoningformat ?? FORMAT_HTML, ['context' => $context]),
        'uckkassembly-decision__reasoning'
    );
}

if (!empty($decisiondata->resultsummary)) {
    echo html_writer::tag('h4', get_string('resultsummary', 'uckkassembly'));
    echo html_writer::tag('p', s($decisiondata->resultsummary));
}

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($decisiondata->readings)) {
    echo html_writer::start_div('uckkassembly-decision__readings card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('decisionreadings', 'uckkassembly'), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('decisionreadingsnotice', 'uckkassembly'), ['class' => 'text-muted']);

    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('reading', 'uckkassembly'));
    echo html_writer::tag('th', get_string('result', 'uckkassembly'));
    echo html_writer::tag('th', get_string('interpretation', 'uckkassembly'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($decisiondata->readings as $reading) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($reading->label ?? ''));
        echo html_writer::tag('td', s($reading->result ?? ''));
        echo html_writer::tag('td', s($reading->interpretation ?? ''));
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($decisiondata->objections) || !empty($decisiondata->minorityreport)) {
    echo html_writer::start_div('uckkassembly-decision__objections card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('objectionsandminorityreport', 'uckkassembly'), [
        'class' => 'card-title',
    ]);

    if (!empty($decisiondata->objections)) {
        echo html_writer::tag('h4', get_string('unresolvedobjections', 'uckkassembly'));
        echo html_writer::start_tag('ul');

        foreach ($decisiondata->objections as $objection) {
            echo html_writer::tag('li', s($objection->summary ?? $objection));
        }

        echo html_writer::end_tag('ul');
    }

    if (!empty($decisiondata->minorityreport)) {
        echo html_writer::tag('h4', get_string('minorityreport', 'uckkassembly'));
        echo html_writer::div(
            format_text($decisiondata->minorityreport, $decisiondata->minorityreportformat ?? FORMAT_HTML, ['context' => $context]),
            'uckkassembly-decision__minority-report'
        );
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($decisiondata->evidence)) {
    echo html_writer::start_div('uckkassembly-decision__evidence card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('evidenceused', 'uckkassembly'), ['class' => 'card-title']);

    echo html_writer::start_tag('ul', ['class' => 'uckkassembly-decision__evidence-list']);

    foreach ($decisiondata->evidence as $evidence) {
        echo html_writer::start_tag('li');

        if (!empty($evidence->url)) {
            echo html_writer::link(new moodle_url($evidence->url), s($evidence->title ?? $evidence->url));
        } else {
            echo s($evidence->title ?? $evidence->summary ?? '');
        }

        if (!empty($evidence->provenance)) {
            echo html_writer::span(' — ' . s($evidence->provenance), 'uckkassembly-decision__evidence-provenance');
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('uckkassembly-decision__actions card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('decisionactions', 'uckkassembly'), ['class' => 'card-title']);

$actionsrendered = false;

if ($service->can_publish_decision($decision, $assembly, $cm, $context, $USER)) {
    $actionsrendered = true;
    echo mod_uckkassembly_render_decision_action_form(
        $pageurl,
        $cm->id,
        $decisionid,
        'publish',
        get_string('publishdecision', 'uckkassembly'),
        get_string('publishdecision_help', 'uckkassembly'),
        'btn btn-primary'
    );
}

if ($service->can_contest_decision($decision, $assembly, $cm, $context, $USER)) {
    $actionsrendered = true;
    echo mod_uckkassembly_render_decision_action_form(
        $pageurl,
        $cm->id,
        $decisionid,
        'contest',
        get_string('contestdecision', 'uckkassembly'),
        get_string('contestdecision_help', 'uckkassembly'),
        'btn btn-secondary'
    );
}

if ($service->can_archive_decision($decision, $assembly, $cm, $context, $USER)) {
    $actionsrendered = true;
    echo mod_uckkassembly_render_decision_action_form(
        $pageurl,
        $cm->id,
        $decisionid,
        'archive',
        get_string('archivedecision', 'uckkassembly'),
        get_string('archivedecision_help', 'uckkassembly'),
        'btn btn-secondary'
    );
}

if (!$actionsrendered) {
    echo html_writer::tag('p', get_string('nodecisionactionsavailable', 'uckkassembly'), [
        'class' => 'text-muted',
    ]);
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    get_string('decisionnonsovereignnotice', 'uckkassembly'),
    'alert alert-info uckkassembly-decision__notice',
    ['role' => 'status']
);

echo html_writer::link($return, get_string('backtoassembly', 'uckkassembly'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Render one decision action form.
 *
 * @param moodle_url $pageurl Current page URL.
 * @param int $cmid Course module id.
 * @param int $decisionid Decision id.
 * @param string $action Action key.
 * @param string $title Form title.
 * @param string $description Form description.
 * @param string $buttonclass Submit button CSS class.
 * @return string HTML.
 */
function mod_uckkassembly_render_decision_action_form(
    moodle_url $pageurl,
    int $cmid,
    int $decisionid,
    string $action,
    string $title,
    string $description,
    string $buttonclass
): string {
    $summaryid = 'id_' . $action . '_summary';
    $notesid = 'id_' . $action . '_notes';

    $html = html_writer::start_div('uckkassembly-decision__action uckkassembly-decision__action--' . $action);
    $html .= html_writer::tag('h4', $title);
    $html .= html_writer::tag('p', $description, ['class' => 'text-muted']);

    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'uckkassembly-decision__action-form',
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'id',
        'value' => $cmid,
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'decisionid',
        'value' => $decisionid,
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $action,
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'confirm',
        'value' => 1,
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::tag('label', get_string('summary', 'uckkassembly'), [
        'for' => $summaryid,
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => $summaryid,
        'name' => 'summary',
        'class' => 'form-control',
        'maxlength' => 255,
        'required' => 'required',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::tag('label', get_string('notes', 'uckkassembly'), [
        'for' => $notesid,
    ]);
    $html .= html_writer::tag('textarea', '', [
        'id' => $notesid,
        'name' => 'notes',
        'class' => 'form-control',
        'rows' => 4,
        'maxlength' => 4000,
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::tag('button', $title, [
        'type' => 'submit',
        'class' => $buttonclass,
    ]);

    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_div();

    return $html;
}