<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive item validation controller.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkarchive\event\archive_item_validated;
use mod_uckkarchive\form\validation_form;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Archive activity instance id.
$itemid = required_param('itemid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($id) {
    $cm = get_coursemodule_from_id('uckkarchive', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $archive = $DB->get_record('uckkarchive', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $archive = $DB->get_record('uckkarchive', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkarchive:view', $context);
require_capability('mod/uckkarchive:validateitem', $context);

$item = $DB->get_record('uckkarchive_item', [
    'id' => $itemid,
    'archiveid' => $archive->id,
], '*', MUST_EXIST);

$pageurl = new moodle_url('/mod/uckkarchive/validate.php', [
    'id' => $cm->id,
    'itemid' => $item->id,
]);

$itemurl = new moodle_url('/mod/uckkarchive/item.php', [
    'id' => $cm->id,
    'itemid' => $item->id,
]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $itemurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($item->title ?? $archive->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if (!uckkarchive_can_validate_item($archive, $item, $cm, $context, $USER)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('validateitem', 'uckkarchive'));
    echo $OUTPUT->notification(get_string('cannotvalidateitem', 'uckkarchive'), notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

$proofs = uckkarchive_get_item_proofs($item, $context, $USER);
$provenance = uckkarchive_get_item_provenance($item, $context, $USER);
$revisions = uckkarchive_get_item_revisions($item, $context, $USER);
$warnings = uckkarchive_get_validation_warnings($archive, $item, $proofs, $provenance, $context, $USER);

$form = new validation_form($pageurl, [
    'archive' => $archive,
    'item' => $item,
    'course' => $course,
    'cm' => $cm,
    'context' => $context,
    'proofs' => $proofs,
    'provenance' => $provenance,
    'revisions' => $revisions,
    'warnings' => $warnings,
    'returnurl' => $return->out_as_local_url(false),
]);

if ($form->is_cancelled()) {
    redirect($return);
}

$defaultdata = new stdClass();
$defaultdata->id = $cm->id;
$defaultdata->itemid = $item->id;
$defaultdata->returnurl = $return->out_as_local_url(false);
$defaultdata->validationstate = $item->validationstate ?? 'unverified';
$defaultdata->status = $item->status ?? 'pending_review';
$defaultdata->visibility = $item->visibility ?? 'course';
$defaultdata->provenancecheck = 0;
$defaultdata->proofcheck = 0;
$defaultdata->privacycheck = 0;
$defaultdata->integritycheck = 0;
$defaultdata->archivepolicy = $item->archivepolicy ?? 'standard';
$defaultdata->validationnotes = '';
$defaultdata->publicsummary = $item->publicsummary ?? '';
$defaultdata->restrictednotes = '';

$form->set_data($defaultdata);

if ($data = $form->get_data()) {
    require_sesskey();

    $validationdata = [
        'validationstate' => clean_param((string)$data->validationstate, PARAM_ALPHANUMEXT),
        'status' => clean_param((string)$data->status, PARAM_ALPHANUMEXT),
        'visibility' => clean_param((string)$data->visibility, PARAM_ALPHANUMEXT),
        'archivepolicy' => clean_param((string)$data->archivepolicy, PARAM_ALPHANUMEXT),
        'provenancecheck' => !empty($data->provenancecheck) ? 1 : 0,
        'proofcheck' => !empty($data->proofcheck) ? 1 : 0,
        'privacycheck' => !empty($data->privacycheck) ? 1 : 0,
        'integritycheck' => !empty($data->integritycheck) ? 1 : 0,
        'validationnotes' => (string)($data->validationnotes ?? ''),
        'publicsummary' => (string)($data->publicsummary ?? ''),
        'restrictednotes' => (string)($data->restrictednotes ?? ''),
    ];

    $validateditem = uckkarchive_validate_item(
        $archive,
        $item,
        $cm,
        $course,
        $context,
        $USER,
        $validationdata
    );

    $event = archive_item_validated::create([
        'objectid' => $validateditem->id,
        'context' => $context,
        'other' => [
            'archiveid' => $archive->id,
            'cmid' => $cm->id,
            'courseid' => $course->id,
            'status' => $validateditem->status,
            'validationstate' => $validateditem->validationstate ?? $validationdata['validationstate'],
            'visibility' => $validateditem->visibility,
        ],
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('uckkarchive', $archive);
    $event->add_record_snapshot('uckkarchive_item', $validateditem);
    $event->trigger();

    redirect(
        new moodle_url('/mod/uckkarchive/item.php', [
            'id' => $cm->id,
            'itemid' => $validateditem->id,
        ]),
        get_string('itemvalidated', 'uckkarchive'),
        null,
        notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('validateitem', 'uckkarchive'));

echo html_writer::start_div('uckkarchive-validate');

echo html_writer::start_div('uckkarchive-validate__summary card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', format_string($item->title ?? get_string('archiveitem', 'uckkarchive')), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkarchive-validate__facts']);

echo html_writer::tag('dt', get_string('archive', 'uckkarchive'));
echo html_writer::tag('dd', format_string($archive->name));

echo html_writer::tag('dt', get_string('archiveitem', 'uckkarchive'));
echo html_writer::tag('dd', format_string($item->title ?? ''));

if (!empty($item->itemtype)) {
    echo html_writer::tag('dt', get_string('itemtype', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->itemtype));
}

if (!empty($item->status)) {
    echo html_writer::tag('dt', get_string('status'));
    echo html_writer::tag('dd', s($item->status));
}

if (!empty($item->validationstate)) {
    echo html_writer::tag('dt', get_string('validationstate', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->validationstate));
}

if (!empty($item->visibility)) {
    echo html_writer::tag('dt', get_string('visibility', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->visibility));
}

if (!empty($item->provenance)) {
    echo html_writer::tag('dt', get_string('provenance', 'uckkarchive'));
    echo html_writer::tag('dd', s($item->provenance));
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

echo html_writer::end_div();
echo html_writer::end_div();

if (!empty($warnings)) {
    echo html_writer::start_div('uckkarchive-validate__warnings', ['role' => 'status']);

    foreach ($warnings as $warning) {
        $message = is_object($warning) ? ($warning->message ?? '') : (string)$warning;
        $severity = is_object($warning) ? ($warning->severity ?? 'warning') : 'warning';

        $class = match ($severity) {
            'danger', 'error' => 'alert-danger',
            'info', 'notice' => 'alert-info',
            'success', 'clear' => 'alert-success',
            default => 'alert-warning',
        };

        if (trim($message) !== '') {
            echo html_writer::div(s($message), 'alert ' . $class);
        }
    }

    echo html_writer::end_div();
}

if (!empty($proofs)) {
    echo html_writer::start_div('uckkarchive-validate__proofs card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('proofrecords', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkarchive-validate__proof-list']);

    foreach ($proofs as $proof) {
        echo html_writer::start_tag('li');

        if (!empty($proof->url)) {
            echo html_writer::link(new moodle_url($proof->url), format_string($proof->title ?? get_string('proof', 'uckkarchive')));
        } else {
            echo html_writer::tag('strong', format_string($proof->title ?? get_string('proof', 'uckkarchive')));
        }

        if (!empty($proof->prooftype)) {
            echo html_writer::span(' — ' . s($proof->prooftype), 'uckkarchive-validate__proof-type');
        }

        if (!empty($proof->validationstate)) {
            echo html_writer::span(' — ' . s($proof->validationstate), 'uckkarchive-validate__proof-validation');
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

if (!empty($provenance)) {
    echo html_writer::start_div('uckkarchive-validate__provenance card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('provenancerecords', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkarchive-validate__provenance-list']);

    foreach ($provenance as $record) {
        echo html_writer::start_tag('li');

        echo html_writer::tag('strong', s($record->sourcetype ?? get_string('provenance', 'uckkarchive')));

        if (!empty($record->summary)) {
            echo html_writer::tag('p', s($record->summary));
        }

        if (!empty($record->validationstate)) {
            echo html_writer::span(s($record->validationstate), 'badge badge-secondary');
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

$form->display();

echo html_writer::link($return, get_string('backtoarchiveitem', 'uckkarchive'), [
    'class' => 'btn btn-secondary mt-3',
]);

echo html_writer::div(
    get_string('validationgovernancenotice', 'uckkarchive'),
    'alert alert-info uckkarchive-validate__notice mt-3',
    ['role' => 'status']
);

echo html_writer::end_div();

echo $OUTPUT->footer();