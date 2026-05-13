<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Motion proposal controller for UCKK assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkassembly\local\motion_service;

/**
 * Motion proposal form.
 *
 * This form collects a proposed motion and supporting context. It does not
 * publish decisions, count votes, close deliberations, archive decisions, or
 * validate integrity. Those actions remain in capability-checked services.
 */
final class mod_uckkassembly_propose_form extends moodleform {
    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $assembly = $customdata['assembly'];
        $context = $customdata['context'];
        $draftitemid = (int)$customdata['draftitemid'];

        $mform->addElement('header', 'proposalheader', get_string('proposemotion', 'uckkassembly'));

        $mform->addElement('static', 'assemblyname', get_string('assembly', 'uckkassembly'), format_string($assembly->name));

        $mform->addElement('text', 'title', get_string('motiontitle', 'uckkassembly'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'motiontype', get_string('motiontype', 'uckkassembly'), self::get_motion_type_options());
        $mform->setDefault('motiontype', 'recommendation');
        $mform->addRule('motiontype', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'motiontext_editor',
            get_string('motiontext', 'uckkassembly'),
            null,
            self::get_editor_options($context)
        );
        $mform->setType('motiontext_editor', PARAM_RAW);
        $mform->addRule('motiontext_editor', null, 'required', null, 'client');
        $mform->addHelpButton('motiontext_editor', 'motiontext', 'uckkassembly');

        $mform->addElement(
            'editor',
            'rationale_editor',
            get_string('motionrationale', 'uckkassembly'),
            null,
            self::get_editor_options($context)
        );
        $mform->setType('rationale_editor', PARAM_RAW);
        $mform->addRule('rationale_editor', null, 'required', null, 'client');
        $mform->addHelpButton('rationale_editor', 'motionrationale', 'uckkassembly');

        $mform->addElement('textarea', 'evidencesummary', get_string('evidencesummary', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('evidencesummary', PARAM_RAW);
        $mform->addHelpButton('evidencesummary', 'evidencesummary', 'uckkassembly');

        $mform->addElement('filemanager', 'motionfiles', get_string('motionfiles', 'uckkassembly'), null, self::get_filemanager_options($context));
        $mform->setDefault('motionfiles', $draftitemid);
        $mform->addHelpButton('motionfiles', 'motionfiles', 'uckkassembly');

        $mform->addElement('textarea', 'expectedoutcome', get_string('expectedoutcome', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('expectedoutcome', PARAM_RAW);
        $mform->addHelpButton('expectedoutcome', 'expectedoutcome', 'uckkassembly');

        $mform->addElement('select', 'decisiontype', get_string('decisiontype', 'uckkassembly'), self::get_decision_type_options());
        $mform->setDefault('decisiontype', 'recommendation');
        $mform->addRule('decisiontype', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), self::get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('select', 'provenance', get_string('provenance', 'uckkassembly'), self::get_provenance_options());
        $mform->setDefault('provenance', 'human');
        $mform->addRule('provenance', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassisted', 'uckkassembly'));
        $mform->setDefault('aiassisted', 0);
        $mform->addHelpButton('aiassisted', 'aiassisted', 'uckkassembly');

        $mform->addElement('textarea', 'ailog', get_string('ailog', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');
        $mform->addHelpButton('ailog', 'ailog', 'uckkassembly');

        $mform->addElement('advcheckbox', 'requestintegrityreview', get_string('requestintegrityreview', 'uckkassembly'));
        $mform->setDefault('requestintegrityreview', 0);
        $mform->addHelpButton('requestintegrityreview', 'requestintegrityreview', 'uckkassembly');

        $mform->addElement('advcheckbox', 'urgent', get_string('urgentmotion', 'uckkassembly'));
        $mform->setDefault('urgent', 0);

        $mform->addElement('date_time_selector', 'timedeliberationstarts', get_string('timedeliberationstarts', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timedeliberationstarts', 0);

        $mform->addElement('date_time_selector', 'timevotingstarts', get_string('timevotingstarts', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timevotingstarts', 0);

        $mform->addElement('date_time_selector', 'timevotingends', get_string('timevotingends', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timevotingends', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'motionid');
        $mform->setType('motionid', PARAM_INT);

        $mform->addElement('hidden', 'draftitemid');
        $mform->setType('draftitemid', PARAM_INT);
        $mform->setDefault('draftitemid', $draftitemid);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submitmotion', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savedraft'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Validate proposal form.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $title = trim((string)($data['title'] ?? ''));
        $motiontext = trim((string)($data['motiontext_editor']['text'] ?? ''));
        $rationale = trim((string)($data['rationale_editor']['text'] ?? ''));

        if ($title === '') {
            $errors['title'] = get_string('required');
        }

        if ($motiontext === '') {
            $errors['motiontext_editor'] = get_string('required');
        }

        if ($rationale === '') {
            $errors['rationale_editor'] = get_string('required');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkassembly');
        }

        if (!empty($data['timevotingstarts']) && !empty($data['timevotingends']) && $data['timevotingends'] <= $data['timevotingstarts']) {
            $errors['timevotingends'] = get_string('timevotingendsmustbeafterstart', 'uckkassembly');
        }

        if (!empty($data['timedeliberationstarts']) && !empty($data['timevotingstarts']) && $data['timevotingstarts'] < $data['timedeliberationstarts']) {
            $errors['timevotingstarts'] = get_string('timevotingstartsmustbeafterdeliberation', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private static function get_editor_options(context_module $context): array {
        return [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => get_max_upload_file_size(),
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => false,
        ];
    }

    /**
     * File manager options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private static function get_filemanager_options(context_module $context): array {
        return [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            'context' => $context,
        ];
    }

    /**
     * Motion type options.
     *
     * @return array<string, string>
     */
    private static function get_motion_type_options(): array {
        return [
            'information' => get_string('motiontype:information', 'uckkassembly'),
            'recommendation' => get_string('motiontype:recommendation', 'uckkassembly'),
            'validation' => get_string('motiontype:validation', 'uckkassembly'),
            'correction' => get_string('motiontype:correction', 'uckkassembly'),
            'rejection' => get_string('motiontype:rejection', 'uckkassembly'),
            'archival' => get_string('motiontype:archival', 'uckkassembly'),
            'integrity' => get_string('motiontype:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Decision type options.
     *
     * @return array<string, string>
     */
    private static function get_decision_type_options(): array {
        return [
            'information' => get_string('decisiontype:information', 'uckkassembly'),
            'recommendation' => get_string('decisiontype:recommendation', 'uckkassembly'),
            'validation' => get_string('decisiontype:validation', 'uckkassembly'),
            'correction' => get_string('decisiontype:correction', 'uckkassembly'),
            'rejection' => get_string('decisiontype:rejection', 'uckkassembly'),
            'archival' => get_string('decisiontype:archival', 'uckkassembly'),
            'integrity' => get_string('decisiontype:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private static function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'group' => get_string('visibility:group', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ];
    }

    /**
     * Provenance options.
     *
     * @return array<string, string>
     */
    private static function get_provenance_options(): array {
        return [
            'human' => get_string('provenance:human', 'uckkassembly'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'uckkassembly'),
            'imported' => get_string('provenance:imported', 'uckkassembly'),
            'system' => get_string('provenance:system', 'uckkassembly'),
            'archive' => get_string('provenance:archive', 'uckkassembly'),
            'challenge' => get_string('provenance:challenge', 'uckkassembly'),
            'integrity' => get_string('provenance:integrity', 'uckkassembly'),
        ];
    }
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT); // Assembly instance id.
$motionid = optional_param('motionid', 0, PARAM_INT);
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
require_capability('mod/uckkassembly:proposemotion', $context);

$viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkassembly/propose.php', ['id' => $cm->id]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new motion_service();

if (!$service->can_propose($assembly, $cm, $context, $USER)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($assembly->name));
    echo $OUTPUT->notification(get_string('cannotproposemotion', 'uckkassembly'), notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

$existing = $motionid > 0
    ? $service->get_motion((int)$motionid, $assembly, $cm, $context, $USER)
    : null;

$draftitemid = file_get_submitted_draft_itemid('motionfiles');

if ($draftitemid <= 0) {
    $draftitemid = 0;

    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'mod_uckkassembly',
        'motion_files',
        !empty($existing->id) ? (int)$existing->id : 0,
        [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
        ]
    );
}

$form = new mod_uckkassembly_propose_form($pageurl, [
    'assembly' => $assembly,
    'course' => $course,
    'cm' => $cm,
    'context' => $context,
    'draftitemid' => $draftitemid,
]);

if ($form->is_cancelled()) {
    redirect($return);
}

$defaultdata = new stdClass();
$defaultdata->id = $cm->id;
$defaultdata->motionid = !empty($existing->id) ? (int)$existing->id : 0;
$defaultdata->draftitemid = $draftitemid;
$defaultdata->motionfiles = $draftitemid;
$defaultdata->title = $existing->title ?? '';
$defaultdata->motiontype = $existing->motiontype ?? 'recommendation';
$defaultdata->decisiontype = $existing->decisiontype ?? 'recommendation';
$defaultdata->visibility = $existing->visibility ?? 'course';
$defaultdata->provenance = $existing->provenance ?? 'human';
$defaultdata->evidencesummary = $existing->evidencesummary ?? '';
$defaultdata->expectedoutcome = $existing->expectedoutcome ?? '';
$defaultdata->aiassisted = !empty($existing->aiassisted) ? 1 : 0;
$defaultdata->ailog = $existing->ailog ?? '';
$defaultdata->requestintegrityreview = !empty($existing->requestintegrityreview) ? 1 : 0;
$defaultdata->urgent = !empty($existing->urgent) ? 1 : 0;
$defaultdata->timedeliberationstarts = $existing->timedeliberationstarts ?? 0;
$defaultdata->timevotingstarts = $existing->timevotingstarts ?? 0;
$defaultdata->timevotingends = $existing->timevotingends ?? 0;

$editoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => get_max_upload_file_size(),
    'trusttext' => false,
    'noclean' => false,
    'subdirs' => false,
];

$defaultdata = file_prepare_standard_editor(
    $defaultdata,
    'motiontext',
    $editoroptions,
    $context,
    'mod_uckkassembly',
    'motion_text',
    !empty($existing->id) ? (int)$existing->id : 0
);

$defaultdata = file_prepare_standard_editor(
    $defaultdata,
    'rationale',
    $editoroptions,
    $context,
    'mod_uckkassembly',
    'motion_rationale',
    !empty($existing->id) ? (int)$existing->id : 0
);

$form->set_data($defaultdata);

if ($data = $form->get_data()) {
    require_sesskey();

    $isdraft = isset($data->savedraft) && !isset($data->submitbutton);

    $motion = $service->save_motion($assembly, $cm, $course, $context, $USER, [
        'motionid' => (int)($data->motionid ?? 0),
        'title' => $data->title,
        'motiontype' => $data->motiontype,
        'decisiontype' => $data->decisiontype,
        'visibility' => $data->visibility,
        'provenance' => $data->provenance,
        'evidencesummary' => $data->evidencesummary,
        'expectedoutcome' => $data->expectedoutcome,
        'aiassisted' => !empty($data->aiassisted) ? 1 : 0,
        'ailog' => $data->ailog,
        'requestintegrityreview' => !empty($data->requestintegrityreview) ? 1 : 0,
        'urgent' => !empty($data->urgent) ? 1 : 0,
        'timedeliberationstarts' => (int)$data->timedeliberationstarts,
        'timevotingstarts' => (int)$data->timevotingstarts,
        'timevotingends' => (int)$data->timevotingends,
        'status' => $isdraft ? 'draft' : 'pending',
    ]);

    $data = file_postupdate_standard_editor(
        $data,
        'motiontext',
        $editoroptions,
        $context,
        'mod_uckkassembly',
        'motion_text',
        (int)$motion->id
    );

    $data = file_postupdate_standard_editor(
        $data,
        'rationale',
        $editoroptions,
        $context,
        'mod_uckkassembly',
        'motion_rationale',
        (int)$motion->id
    );

    $service->update_motion_texts(
        $motion,
        $data->motiontext,
        (int)$data->motiontextformat,
        $data->rationale,
        (int)$data->rationaleformat,
        $USER
    );

    file_save_draft_area_files(
        (int)$data->motionfiles,
        $context->id,
        'mod_uckkassembly',
        'motion_files',
        (int)$motion->id,
        [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
        ]
    );

    if ($isdraft) {
        $message = get_string('motiondraftsaved', 'uckkassembly');
    } else {
        $service->submit_motion($motion, $assembly, $cm, $course, $context, $USER);
        $message = get_string('motionsubmitted', 'uckkassembly');
    }

    redirect($viewurl, $message, null, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('proposemotion', 'uckkassembly'));

echo html_writer::div(
    get_string('proposalnotice', 'uckkassembly'),
    'alert alert-info uckkassembly-propose__notice',
    ['role' => 'status']
);

if (!empty($assembly->description)) {
    echo html_writer::start_div('uckkassembly-propose__assembly card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', format_string($assembly->name), ['class' => 'card-title']);
    echo format_text($assembly->description, FORMAT_HTML, ['context' => $context]);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

$form->display();

echo html_writer::link($return, get_string('backtoassembly', 'uckkassembly'), [
    'class' => 'btn btn-secondary mt-3',
]);

echo $OUTPUT->footer();