<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive item controller for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkarchive\event\archive_item_exported;
use mod_uckkarchive\event\archive_item_revised;
use mod_uckkarchive\local\archive_item;
use mod_uckkarchive\output\archive_item_card;
use mod_uckkarchive\output\provenance_panel;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Archive instance id.
$itemid = optional_param('itemid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($itemid <= 0 && $id <= 0 && $a <= 0) {
    throw new moodle_exception('missingparam', 'error', '', 'itemid');
}

if ($itemid > 0) {
    $item = $DB->get_record('uckkarchive_item', ['id' => $itemid], '*', MUST_EXIST);

    if (!empty($item->cmid)) {
        $cm = get_coursemodule_from_id('uckkarchive', (int)$item->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $archive = $DB->get_record('uckkarchive', ['id' => $cm->instance], '*', MUST_EXIST);
    } else if (!empty($item->archiveid)) {
        $archive = $DB->get_record('uckkarchive', ['id' => $item->archiveid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
    } else if (!empty($item->courseid)) {
        $course = $DB->get_record('course', ['id' => $item->courseid], '*', MUST_EXIST);
        $cm = null;
        $archive = null;
    } else {
        throw new moodle_exception('invalidarchiveitemcontext', 'uckkarchive');
    }
} else if ($id > 0) {
    $cm = get_coursemodule_from_id('uckkarchive', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $archive = $DB->get_record('uckkarchive', ['id' => $cm->instance], '*', MUST_EXIST);
    $item = null;
} else {
    $archive = $DB->get_record('uckkarchive', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
    $item = null;
}

if ($cm) {
    $context = context_module::instance($cm->id);
    require_login($course, false, $cm);
} else {
    $context = context_course::instance($course->id);
    require_login($course);
}

require_capability('mod/uckkarchive:view', $context);

if ($item === null) {
    throw new moodle_exception('missingparam', 'error', '', 'itemid');
}

$itemid = (int)$item->id;

$itemvisibility = clean_param((string)($item->visibility ?? 'course'), PARAM_ALPHANUMEXT);
$restrictedvisibilities = [
    'private',
    'restricted',
    'restricted_integrity',
    'hidden',
];

if (in_array($itemvisibility, $restrictedvisibilities, true)) {
    require_capability('mod/uckkarchive:viewrestricted', $context);
}

$pageurl = new moodle_url('/mod/uckkarchive/item.php', [
    'itemid' => $itemid,
]);

$archiveurl = $cm
    ? new moodle_url('/mod/uckkarchive/view.php', ['id' => $cm->id])
    : new moodle_url('/mod/uckkarchive/index.php', ['id' => $course->id]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $archiveurl;

$allowedactions = [
    '',
    'markviewed',
    'revise',
    'requestvalidation',
    'export',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invalidarchiveitemaction', 'uckkarchive');
}

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
if ($cm) {
    $PAGE->set_cm($cm);
}
$PAGE->set_context($context);
$PAGE->set_title(format_string($item->title ?? $item->name ?? get_string('archiveitem', 'uckkarchive')));
$PAGE->set_heading(format_string($course->fullname));

if ($cm) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

if ($action !== '') {
    require_sesskey();

    switch ($action) {
        case 'markviewed':
            redirect($pageurl);
            break;

        case 'revise':
            require_capability('mod/uckkarchive:reviseitem', $context);

            $summary = required_param('summary', PARAM_TEXT);

            $revision = new stdClass();
            $revision->itemid = $itemid;
            $revision->courseid = $course->id;
            $revision->cmid = $cm ? $cm->id : 0;
            $revision->contextid = $context->id;
            $revision->userid = $USER->id;
            $revision->summary = $summary;
            $revision->status = 'pending_review';
            $revision->visibility = $itemvisibility;
            $revision->versionno = ((int)($item->versionno ?? 1)) + 1;
            $revision->createdby = $USER->id;
            $revision->modifiedby = $USER->id;
            $revision->timecreated = time();
            $revision->timemodified = time();
            $revision->metadata = null;

            $revisionid = $DB->insert_record('uckkarchive_rev', $revision);

            $DB->set_field('uckkarchive_item', 'status', 'pending_review', ['id' => $itemid]);
            $DB->set_field('uckkarchive_item', 'modifiedby', $USER->id, ['id' => $itemid]);
            $DB->set_field('uckkarchive_item', 'timemodified', time(), ['id' => $itemid]);

            $event = archive_item_revised::create([
                'objectid' => $itemid,
                'context' => $context,
                'other' => [
                    'courseid' => $course->id,
                    'cmid' => $cm ? $cm->id : 0,
                    'revisionid' => $revisionid,
                ],
            ]);
            $event->add_record_snapshot('course', $course);
            if ($cm) {
                $event->add_record_snapshot('course_modules', $cm);
            }
            $event->add_record_snapshot('uckkarchive_item', $item);
            $event->trigger();

            redirect($pageurl, get_string('archiveitemrevised', 'uckkarchive'), null, notification::NOTIFY_SUCCESS);
            break;

        case 'requestvalidation':
            require_capability('mod/uckkarchive:validateitem', $context);

            $DB->set_field('uckkarchive_item', 'status', 'pending_review', ['id' => $itemid]);
            $DB->set_field('uckkarchive_item', 'modifiedby', $USER->id, ['id' => $itemid]);
            $DB->set_field('uckkarchive_item', 'timemodified', time(), ['id' => $itemid]);

            redirect($pageurl, get_string('validationrequested', 'uckkarchive'), null, notification::NOTIFY_SUCCESS);
            break;

        case 'export':
            require_capability('mod/uckkarchive:export', $context);

            $export = new stdClass();
            $export->itemid = $itemid;
            $export->courseid = $course->id;
            $export->cmid = $cm ? $cm->id : 0;
            $export->contextid = $context->id;
            $export->userid = $USER->id;
            $export->exporttype = 'item_summary';
            $export->status = 'pending';
            $export->visibility = $itemvisibility;
            $export->versionno = 1;
            $export->createdby = $USER->id;
            $export->modifiedby = $USER->id;
            $export->timecreated = time();
            $export->timemodified = time();
            $export->metadata = json_encode([
                'source' => 'mod_uckkarchive/item.php',
                'itemid' => $itemid,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $exportid = $DB->insert_record('uckkarchive_export', $export);

            $event = archive_item_exported::create([
                'objectid' => $itemid,
                'context' => $context,
                'other' => [
                    'courseid' => $course->id,
                    'cmid' => $cm ? $cm->id : 0,
                    'exportid' => $exportid,
                    'exporttype' => 'item_summary',
                ],
            ]);
            $event->add_record_snapshot('course', $course);
            if ($cm) {
                $event->add_record_snapshot('course_modules', $cm);
            }
            $event->add_record_snapshot('uckkarchive_item', $item);
            $event->trigger();

            redirect(
                new moodle_url('/mod/uckkarchive/export.php', [
                    'id' => $cm ? $cm->id : 0,
                    'itemid' => $itemid,
                    'exportid' => $exportid,
                ]),
                get_string('archiveexportqueued', 'uckkarchive'),
                null,
                notification::NOTIFY_SUCCESS
            );
            break;
    }
}

$item = $DB->get_record('uckkarchive_item', ['id' => $itemid], '*', MUST_EXIST);

$proofs = $DB->get_records('uckkarchive_proof', ['itemid' => $itemid], 'timecreated ASC, id ASC');
$provenance = $DB->get_records('uckkarchive_prov', ['itemid' => $itemid], 'timecreated ASC, id ASC');
$revisions = $DB->get_records('uckkarchive_rev', ['itemid' => $itemid], 'versionno DESC, timecreated DESC, id DESC');
$exports = $DB->get_records('uckkarchive_export', ['itemid' => $itemid], 'timecreated DESC, id DESC');

$kristal = null;
if (!empty($item->kristalid)) {
    $kristal = $DB->get_record('uckkarchive_kristal', ['id' => $item->kristalid], '*', IGNORE_MISSING);
}

$itemmodel = archive_item::from_record($item);

$canrevise = has_capability('mod/uckkarchive:reviseitem', $context);
$canvalidate = has_capability('mod/uckkarchive:validateitem', $context);
$canexport = has_capability('mod/uckkarchive:export', $context);
$canviewrestricted = has_capability('mod/uckkarchive:viewrestricted', $context);

$actions = [];

if ($canrevise) {
    $actions[] = [
        'key' => 'revise',
        'label' => get_string('revisearchiveitem', 'uckkarchive'),
        'url' => $pageurl,
        'method' => 'POST',
        'primary' => false,
        'requiresummary' => true,
    ];
}

if ($canvalidate) {
    $actions[] = [
        'key' => 'requestvalidation',
        'label' => get_string('requestvalidation', 'uckkarchive'),
        'url' => $pageurl,
        'method' => 'POST',
        'primary' => true,
        'requiresummary' => false,
    ];
}

if ($canexport) {
    $actions[] = [
        'key' => 'export',
        'label' => get_string('exportarchiveitem', 'uckkarchive'),
        'url' => $pageurl,
        'method' => 'POST',
        'primary' => false,
        'requiresummary' => false,
    ];
}

$card = new archive_item_card(
    $itemmodel->to_export(),
    [
        'course' => $course,
        'cm' => $cm,
        'context' => $context,
        'proofs' => array_values($proofs),
        'kristal' => $kristal,
        'revisions' => array_values($revisions),
        'exports' => array_values($exports),
        'actions' => $actions,
        'canviewrestricted' => $canviewrestricted,
    ]
);

$provpanel = new provenance_panel(
    $itemid,
    $context->id,
    array_values($provenance),
    [
        'canviewrestricted' => $canviewrestricted,
    ]
);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($item->title ?? $item->name ?? get_string('archiveitem', 'uckkarchive')));

echo html_writer::start_div('uckkarchive-item-page', [
    'data-region' => 'uckkarchive-item-page',
    'data-item-id' => $itemid,
]);

echo $OUTPUT->render($card);

echo html_writer::start_div('uckkarchive-item-page__details card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('archiveitemdetails', 'uckkarchive'), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkarchive-item-page__facts']);

echo html_writer::tag('dt', get_string('status'));
echo html_writer::tag('dd', s($item->status ?? ''));

echo html_writer::tag('dt', get_string('visibility', 'uckkarchive'));
echo html_writer::tag('dd', s($item->visibility ?? ''));

echo html_writer::tag('dt', get_string('version', 'uckkarchive'));
echo html_writer::tag('dd', (string)($item->versionno ?? 1));

if (!empty($item->provenance)) {
    echo html_writer::tag('dt', get_string('provenance', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->provenance));
}

if (!empty($item->validationstate)) {
    echo html_writer::tag('dt', get_string('validationstate', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->validationstate));
}

if (!empty($item->timecreated)) {
    echo html_writer::tag('dt', get_string('timecreated', 'uckkarchive'));
    echo html_writer::tag('dd', userdate($item->timecreated));
}

if (!empty($item->timemodified)) {
    echo html_writer::tag('dt', get_string('timemodified', 'moodle'));
    echo html_writer::tag('dd', userdate($item->timemodified));
}

echo html_writer::end_tag('dl');

if (!empty($item->summary)) {
    echo html_writer::tag('h4', get_string('summary', 'uckkarchive'));
    echo format_text($item->summary, FORMAT_HTML, ['context' => $context]);
}

if (!empty($item->content)) {
    echo html_writer::tag('h4', get_string('archiveitemcontent', 'uckkarchive'));
    echo format_text($item->content, FORMAT_HTML, ['context' => $context]);
}

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($proofs)) {
    echo html_writer::start_div('uckkarchive-item-page__proofs card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('proofs', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkarchive-item-page__proof-list']);

    foreach ($proofs as $proof) {
        echo html_writer::start_tag('li', ['class' => 'uckkarchive-item-page__proof']);

        echo html_writer::tag('strong', format_string($proof->title ?? $proof->prooftype ?? get_string('proof', 'uckkarchive')));

        if (!empty($proof->status)) {
            echo html_writer::span(s($proof->status), 'badge badge-secondary ml-2');
        }

        if (!empty($proof->summary)) {
            echo html_writer::tag('p', s($proof->summary));
        }

        if (!empty($proof->sourceurl)) {
            echo html_writer::link(new moodle_url($proof->sourceurl), s($proof->sourceurl));
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo $OUTPUT->render($provpanel);

if (!empty($revisions)) {
    echo html_writer::start_div('uckkarchive-item-page__revisions card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('revisionhistory', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ol', ['class' => 'uckkarchive-item-page__revision-list']);

    foreach ($revisions as $revision) {
        echo html_writer::start_tag('li', ['class' => 'uckkarchive-item-page__revision']);

        echo html_writer::tag(
            'strong',
            get_string('versionx', 'uckkarchive', (object)['version' => (int)($revision->versionno ?? 1)])
        );

        if (!empty($revision->status)) {
            echo html_writer::span(s($revision->status), 'badge badge-secondary ml-2');
        }

        if (!empty($revision->summary)) {
            echo html_writer::tag('p', s($revision->summary));
        }

        if (!empty($revision->timecreated)) {
            echo html_writer::tag('p', userdate($revision->timecreated), [
                'class' => 'text-muted',
            ]);
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ol');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($exports) && $canexport) {
    echo html_writer::start_div('uckkarchive-item-page__exports card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('exports', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul');

    foreach ($exports as $export) {
        echo html_writer::start_tag('li');

        echo s($export->exporttype ?? get_string('export', 'uckkarchive'));

        if (!empty($export->status)) {
            echo html_writer::span(' — ' . s($export->status), 'text-muted');
        }

        if (!empty($export->timecreated)) {
            echo html_writer::span(' — ' . userdate($export->timecreated), 'text-muted');
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($actions)) {
    echo html_writer::start_div('uckkarchive-item-page__actions card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('archiveitemactions', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    foreach ($actions as $actionrow) {
        echo html_writer::start_tag('form', [
            'method' => strtolower((string)$actionrow['method']),
            'action' => $actionrow['url']->out(false),
            'class' => 'uckkarchive-item-page__action-form mb-3',
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'itemid',
            'value' => $itemid,
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => $actionrow['key'],
        ]);

        if (!empty($actionrow['requiresummary'])) {
            $summaryid = 'id_' . clean_param((string)$actionrow['key'], PARAM_ALPHANUMEXT) . '_summary';

            echo html_writer::start_div('form-group');
            echo html_writer::tag('label', get_string('summary', 'uckkarchive'), [
                'for' => $summaryid,
            ]);
            echo html_writer::tag('textarea', '', [
                'id' => $summaryid,
                'name' => 'summary',
                'class' => 'form-control',
                'rows' => 3,
                'maxlength' => 4000,
                'required' => 'required',
            ]);
            echo html_writer::end_div();
        }

        echo html_writer::tag('button', $actionrow['label'], [
            'type' => 'submit',
            'class' => !empty($actionrow['primary']) ? 'btn btn-primary' : 'btn btn-secondary',
        ]);

        echo html_writer::end_tag('form');
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::div(
    get_string('archiveitemgovernancenotice', 'uckkarchive'),
    'alert alert-info uckkarchive-item-page__notice',
    ['role' => 'status']
);

echo html_writer::link($return, get_string('backtoarchive', 'uckkarchive'), [
    'class' => 'btn btn-secondary',
]);

echo html_writer::end_div();

echo $OUTPUT->footer();