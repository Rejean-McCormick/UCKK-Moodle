<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Evidence submission controller for a UCKK challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkchallenge\local\submission_service;

/**
 * Evidence submission form.
 *
 * This form collects learner proof and provenance metadata. Workflow,
 * validation, grading, badge awards, competency certification, archive export,
 * and integrity decisions must remain in capability-checked service classes.
 */
final class mod_uckkchallenge_submission_form extends moodleform {
    /**
     * Define the submission form.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $challenge = $customdata['challenge'];
        $context = $customdata['context'];
        $draftitemid = $customdata['draftitemid'];

        $mform->addElement('header', 'submissionheader', get_string('submitchallengeproof', 'uckkchallenge'));

        $mform->addElement('static', 'challengename', get_string('challenge', 'uckkchallenge'), format_string($challenge->name));

        $mform->addElement('select', 'prooftype', get_string('prooftype', 'uckkchallenge'), self::get_proof_type_options());
        $mform->setDefault('prooftype', 'text');
        $mform->addRule('prooftype', null, 'required', null, 'client');

        $mform->addElement('editor', 'submissiontext_editor', get_string('submissiontext', 'uckkchallenge'), null, self::get_editor_options($context));
        $mform->setType('submissiontext_editor', PARAM_RAW);
        $mform->addRule('submissiontext_editor', null, 'required', null, 'client');

        $mform->addElement('url', 'submissionurl', get_string('submissionurl', 'uckkchallenge'), [
            'size' => 80,
        ]);
        $mform->setType('submissionurl', PARAM_URL);
        $mform->addHelpButton('submissionurl', 'submissionurl', 'uckkchallenge');

        $mform->addElement('filemanager', 'prooffiles', get_string('prooffiles', 'uckkchallenge'), null, self::get_filemanager_options($context));
        $mform->setDefault('prooffiles', $draftitemid);
        $mform->addHelpButton('prooffiles', 'prooffiles', 'uckkchallenge');

        $mform->addElement('textarea', 'relationtocriteria', get_string('relationtocriteria', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('relationtocriteria', PARAM_RAW);
        $mform->addRule('relationtocriteria', null, 'required', null, 'client');
        $mform->addHelpButton('relationtocriteria', 'relationtocriteria', 'uckkchallenge');

        $mform->addElement('textarea', 'provenancestatement', get_string('provenancestatement', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('provenancestatement', PARAM_RAW);
        $mform->addRule('provenancestatement', null, 'required', null, 'client');
        $mform->addHelpButton('provenancestatement', 'provenancestatement', 'uckkchallenge');

        $mform->addElement('textarea', 'sourceauthor', get_string('sourceauthor', 'uckkchallenge'), [
            'rows' => 3,
            'cols' => 80,
        ]);
        $mform->setType('sourceauthor', PARAM_TEXT);
        $mform->addHelpButton('sourceauthor', 'sourceauthor', 'uckkchallenge');

        $mform->addElement('date_time_selector', 'sourcedate', get_string('sourcedate', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('sourcedate', 0);

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkchallenge'), self::get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('select', 'integritystate', get_string('integritystate', 'uckkchallenge'), self::get_integrity_state_options());
        $mform->setDefault('integritystate', 'unverified');
        $mform->addRule('integritystate', null, 'required', null, 'client');
        $mform->freeze('integritystate');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassisted', 'uckkchallenge'));
        $mform->setDefault('aiassisted', 0);
        $mform->addHelpButton('aiassisted', 'aiassisted', 'uckkchallenge');

        $mform->addElement('textarea', 'ailog', get_string('ailog', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');
        $mform->addHelpButton('ailog', 'ailog', 'uckkchallenge');

        $mform->addElement('textarea', 'uncertaintynotes', get_string('uncertaintynotes', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('uncertaintynotes', PARAM_RAW);
        $mform->addHelpButton('uncertaintynotes', 'uckkchallenge');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'submissionid');
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement('hidden', 'draftitemid');
        $mform->setType('draftitemid', PARAM_INT);
        $mform->setDefault('draftitemid', $draftitemid);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submitproof', 'uckkchallenge'));
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savedraft'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Validate submitted evidence.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Fieldname => error.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $text = '';
        if (!empty($data['submissiontext_editor']['text'])) {
            $text = trim((string)$data['submissiontext_editor']['text']);
        }

        $url = trim((string)($data['submissionurl'] ?? ''));
        $draftitemid = (int)($data['prooffiles'] ?? 0);
        $hasfiles = $draftitemid > 0 && self::draft_area_has_files($draftitemid);

        if ($text === '' && $url === '' && !$hasfiles) {
            $errors['submissiontext_editor'] = get_string('submissionrequiresproof', 'uckkchallenge');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkchallenge');
        }

        if (trim((string)($data['relationtocriteria'] ?? '')) === '') {
            $errors['relationtocriteria'] = get_string('required');
        }

        if (trim((string)($data['provenancestatement'] ?? '')) === '') {
            $errors['provenancestatement'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Check whether a Moodle draft file area contains uploaded files.
     *
     * @param int $draftitemid Draft item id.
     * @return bool
     */
    private static function draft_area_has_files(int $draftitemid): bool {
        global $USER;

        if ($draftitemid <= 0) {
            return false;
        }

        $fs = get_file_storage();
        $usercontext = context_user::instance((int)$USER->id);

        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

        return !empty($files);
    }

    /**
     * Editor options.
     *
     * @param context_module $context Module context.
     * @return array
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
     * @return array
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
     * Proof type options.
     *
     * @return array<string, string>
     */
    private static function get_proof_type_options(): array {
        return [
            'text' => get_string('prooftype:text', 'uckkchallenge'),
            'file' => get_string('prooftype:file', 'uckkchallenge'),
            'url' => get_string('prooftype:url', 'uckkchallenge'),
            'dataset' => get_string('prooftype:dataset', 'uckkchallenge'),
            'image' => get_string('prooftype:image', 'uckkchallenge'),
            'video' => get_string('prooftype:video', 'uckkchallenge'),
            'testimony' => get_string('prooftype:testimony', 'uckkchallenge'),
            'observation' => get_string('prooftype:observation', 'uckkchallenge'),
            'ai_log' => get_string('prooftype:ai_log', 'uckkchallenge'),
            'decision_record' => get_string('prooftype:decision_record', 'uckkchallenge'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private static function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkchallenge'),
            'user' => get_string('visibility:user', 'uckkchallenge'),
            'group' => get_string('visibility:group', 'uckkchallenge'),
            'course' => get_string('visibility:course', 'uckkchallenge'),
            'cohort' => get_string('visibility:cohort', 'uckkchallenge'),
            'program' => get_string('visibility:program', 'uckkchallenge'),
            'institution' => get_string('visibility:institution', 'uckkchallenge'),
            'public' => get_string('visibility:public', 'uckkchallenge'),
            'restricted' => get_string('visibility:restricted', 'uckkchallenge'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkchallenge'),
        ];
    }

    /**
     * Integrity state options.
     *
     * Learners submit unverified evidence. Human review updates this later.
     *
     * @return array<string, string>
     */
    private static function get_integrity_state_options(): array {
        return [
            'unverified' => get_string('integritystate:unverified', 'uckkchallenge'),
        ];
    }
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT); // Challenge instance id.
$submissionid = optional_param('submissionid', 0, PARAM_INT);
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
require_capability('mod/uckkchallenge:submitproof', $context);

$viewurl = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkchallenge/submit.php', ['id' => $cm->id]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($challenge->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new submission_service();

if (!$service->can_submit($challenge, $cm, $context, $USER)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($challenge->name));
    echo $OUTPUT->notification(get_string('cannotsubmitproof', 'uckkchallenge'), notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

$existing = $submissionid > 0
    ? $service->get_submission((int)$submissionid, $challenge, $cm, $context, $USER)
    : $service->get_current_user_submission($challenge, $cm, $context, $USER);

$draftitemid = file_get_submitted_draft_itemid('prooffiles');

if ($draftitemid <= 0) {
    $draftitemid = 0;

    if (!empty($existing->id)) {
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'mod_uckkchallenge',
            'proof_files',
            (int)$existing->id,
            [
                'subdirs' => 0,
                'maxbytes' => get_max_upload_file_size(),
                'maxfiles' => 20,
            ]
        );
    } else {
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'mod_uckkchallenge',
            'proof_files',
            0,
            [
                'subdirs' => 0,
                'maxbytes' => get_max_upload_file_size(),
                'maxfiles' => 20,
            ]
        );
    }
}

$form = new mod_uckkchallenge_submission_form($pageurl, [
    'challenge' => $challenge,
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
$defaultdata->submissionid = !empty($existing->id) ? (int)$existing->id : 0;
$defaultdata->draftitemid = $draftitemid;
$defaultdata->prooffiles = $draftitemid;
$defaultdata->prooftype = $existing->prooftype ?? 'text';
$defaultdata->submissionurl = $existing->submissionurl ?? '';
$defaultdata->relationtocriteria = $existing->relationtocriteria ?? '';
$defaultdata->provenancestatement = $existing->provenancestatement ?? '';
$defaultdata->sourceauthor = $existing->sourceauthor ?? '';
$defaultdata->sourcedate = $existing->sourcedate ?? 0;
$defaultdata->visibility = $existing->visibility ?? 'course';
$defaultdata->integritystate = 'unverified';
$defaultdata->aiassisted = !empty($existing->aiassisted) ? 1 : 0;
$defaultdata->ailog = $existing->ailog ?? '';
$defaultdata->uncertaintynotes = $existing->uncertaintynotes ?? '';

$defaultdata = file_prepare_standard_editor(
    $defaultdata,
    'submissiontext',
    [
        'context' => $context,
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => get_max_upload_file_size(),
        'trusttext' => false,
        'noclean' => false,
        'subdirs' => false,
    ],
    $context,
    'mod_uckkchallenge',
    'submission_text',
    !empty($existing->id) ? (int)$existing->id : 0
);

$form->set_data($defaultdata);

if ($data = $form->get_data()) {
    require_sesskey();

    $isdraft = isset($data->savedraft) && !isset($data->submitbutton);

    $submission = $service->save_submission($challenge, $cm, $course, $context, $USER, [
        'submissionid' => (int)($data->submissionid ?? 0),
        'prooftype' => $data->prooftype,
        'submissionurl' => $data->submissionurl,
        'relationtocriteria' => $data->relationtocriteria,
        'provenancestatement' => $data->provenancestatement,
        'sourceauthor' => $data->sourceauthor,
        'sourcedate' => (int)$data->sourcedate,
        'visibility' => $data->visibility,
        'integritystate' => 'unverified',
        'aiassisted' => !empty($data->aiassisted) ? 1 : 0,
        'ailog' => $data->ailog,
        'uncertaintynotes' => $data->uncertaintynotes,
        'status' => $isdraft ? 'draft' : 'submitted',
    ]);

    $data = file_postupdate_standard_editor(
        $data,
        'submissiontext',
        [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => get_max_upload_file_size(),
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => false,
        ],
        $context,
        'mod_uckkchallenge',
        'submission_text',
        (int)$submission->id
    );

    $service->update_submission_text($submission, $data->submissiontext, (int)$data->submissiontextformat, $USER);

    file_save_draft_area_files(
        (int)$data->prooffiles,
        $context->id,
        'mod_uckkchallenge',
        'proof_files',
        (int)$submission->id,
        [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
        ]
    );

    if ($isdraft) {
        $message = get_string('submissiondraftsaved', 'uckkchallenge');
    } else {
        $service->submit_for_review($submission, $challenge, $cm, $course, $context, $USER);
        $message = get_string('proofsubmitted', 'uckkchallenge');
    }

    redirect($viewurl, $message, null, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('submitchallengeproof', 'uckkchallenge'));

echo html_writer::div(
    get_string('submissionintegritynotice', 'uckkchallenge'),
    'alert alert-info uckkchallenge-submit__notice',
    ['role' => 'status']
);

if (!empty($challenge->evidencepolicy)) {
    echo html_writer::start_div('uckkchallenge-submit__policy card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', get_string('evidencepolicy', 'uckkchallenge'), ['class' => 'card-title']);
    echo format_text($challenge->evidencepolicy, FORMAT_HTML, ['context' => $context]);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

$form->display();

echo html_writer::link($return, get_string('backtochallenge', 'uckkchallenge'), [
    'class' => 'btn btn-secondary mt-3',
]);

echo $OUTPUT->footer();