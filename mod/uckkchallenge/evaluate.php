<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Evaluation form for UCKK challenge submissions.
 *
 * This form records a human mentor evaluation. It does not silently validate
 * integrity, award badges, certify competencies, or create archive records.
 */
final class mod_uckkchallenge_evaluate_form extends moodleform {
    /**
     * Define form elements.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $submission = $this->_customdata['submission'];
        $challenge = $this->_customdata['challenge'];
        $submitter = $this->_customdata['submitter'];
        $canvalidateintegrity = (bool)$this->_customdata['canvalidateintegrity'];
        $canarchive = (bool)$this->_customdata['canarchive'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'sid');
        $mform->setType('sid', PARAM_INT);

        $mform->addElement('header', 'submissionheader', get_string('evaluatesubmission', 'uckkchallenge'));

        $mform->addElement(
            'static',
            'challengename',
            get_string('challenge', 'uckkchallenge'),
            format_string($challenge->name)
        );

        $mform->addElement(
            'static',
            'submissiontitle',
            get_string('submissiontitle', 'uckkchallenge'),
            format_string($submission->title)
        );

        $mform->addElement(
            'static',
            'submittername',
            get_string('submitter', 'uckkchallenge'),
            fullname($submitter)
        );

        $decisions = [
            'under_review' => get_string('statusunderreview', 'uckkchallenge'),
            'revision_required' => get_string('statusrevisionrequired', 'uckkchallenge'),
            'validated' => get_string('statusvalidated', 'uckkchallenge'),
            'rejected' => get_string('statusrejected', 'uckkchallenge'),
            'contested' => get_string('statuscontested', 'uckkchallenge'),
        ];

        if ($canvalidateintegrity) {
            $decisions['integrity_review'] = get_string('statusintegrityreview', 'uckkchallenge');
        }

        $mform->addElement('select', 'decision', get_string('evaluationdecision', 'uckkchallenge'), $decisions);
        $mform->setDefault('decision', 'under_review');
        $mform->addRule('decision', null, 'required', null, 'client');

        $mform->addElement('text', 'grade', get_string('grade', 'uckkchallenge'), ['size' => 8]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->addHelpButton('grade', 'grade', 'uckkchallenge');

        $mform->addElement(
            'textarea',
            'mentorfeedback',
            get_string('mentorfeedback', 'uckkchallenge'),
            ['rows' => 10, 'cols' => 80]
        );
        $mform->setType('mentorfeedback', PARAM_RAW_TRIMMED);

        $validationstates = [
            'human_reviewed' => get_string('validationhumanreviewed', 'uckkchallenge'),
            'verified' => get_string('validationverified', 'uckkchallenge'),
            'contested' => get_string('validationcontested', 'uckkchallenge'),
            'invalidated' => get_string('validationinvalidated', 'uckkchallenge'),
        ];

        $mform->addElement(
            'select',
            'validationstate',
            get_string('validationstate', 'uckkchallenge'),
            $validationstates
        );
        $mform->setDefault('validationstate', 'human_reviewed');

        if (!empty($challenge->integrityrequired)) {
            $mform->addElement(
                'advcheckbox',
                'requestintegrityreview',
                get_string('requestintegrityreview', 'uckkchallenge'),
                get_string('requestintegrityreview_desc', 'uckkchallenge')
            );
            $mform->setDefault('requestintegrityreview', 1);
        }

        if ($canarchive) {
            $mform->addElement(
                'advcheckbox',
                'requestarchiveexport',
                get_string('requestarchiveexport', 'uckkchallenge'),
                get_string('requestarchiveexport_desc', 'uckkchallenge')
            );
        }

        $mform->addElement(
            'advcheckbox',
            'confirmhumanreview',
            get_string('confirmhumanreview', 'uckkchallenge'),
            get_string('confirmhumanreview_desc', 'uckkchallenge')
        );
        $mform->addRule('confirmhumanreview', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('saveevaluation', 'uckkchallenge'));
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ($data['grade'] !== '' && ((float)$data['grade'] < 0 || (float)$data['grade'] > 100)) {
            $errors['grade'] = get_string('gradeoutofrange', 'uckkchallenge');
        }

        $requiresfeedback = [
            'revision_required',
            'rejected',
            'contested',
            'integrity_review',
        ];

        if (in_array($data['decision'], $requiresfeedback, true) && trim((string)$data['mentorfeedback']) === '') {
            $errors['mentorfeedback'] = get_string('feedbackrequired', 'uckkchallenge');
        }

        if (empty($data['confirmhumanreview'])) {
            $errors['confirmhumanreview'] = get_string('humanreviewrequired', 'uckkchallenge');
        }

        return $errors;
    }
}

/**
 * Check whether a database table exists.
 *
 * @param string $table Table name without Moodle prefix.
 * @return bool
 */
function mod_uckkchallenge_table_exists(string $table): bool {
    global $DB;

    try {
        $DB->get_columns($table);
        return true;
    } catch (dml_exception $exception) {
        return false;
    }
}

/**
 * Keep only fields that exist in the target table.
 *
 * This keeps the page compatible with the canonical UCKK table family while
 * allowing install.xml to evolve without breaking the page script.
 *
 * @param string $table Table name without Moodle prefix.
 * @param array $fields Candidate field values.
 * @return stdClass
 */
function mod_uckkchallenge_record_for_table(string $table, array $fields): stdClass {
    global $DB;

    $columns = $DB->get_columns($table);
    $record = new stdClass();

    foreach ($fields as $field => $value) {
        if (array_key_exists($field, $columns)) {
            $record->{$field} = $value;
        }
    }

    return $record;
}

/**
 * Return a cleaned challenge status value.
 *
 * @param string $status Raw status.
 * @return string
 */
function mod_uckkchallenge_clean_status(string $status): string {
    $status = clean_param($status, PARAM_ALPHANUMEXT);

    $allowed = [
        'draft',
        'published',
        'open',
        'submitted',
        'under_review',
        'integrity_review',
        'revision_required',
        'resubmitted',
        'validated',
        'archived',
        'contested',
        'invalidated',
        'closed',
        'rejected',
        'withdrawn',
        'expired',
    ];

    return in_array($status, $allowed, true) ? $status : 'under_review';
}

/**
 * Resolve the final submission state after an evaluation.
 *
 * @param stdClass $data Submitted form data.
 * @param stdClass $challenge Challenge instance.
 * @param bool $canvalidateintegrity Whether current user may validate integrity.
 * @return string
 */
function mod_uckkchallenge_resolve_evaluation_status(
    stdClass $data,
    stdClass $challenge,
    bool $canvalidateintegrity
): string {
    if (!empty($data->requestintegrityreview)) {
        return 'integrity_review';
    }

    if ($data->decision === 'validated' && !empty($challenge->integrityrequired) && !$canvalidateintegrity) {
        return 'integrity_review';
    }

    return mod_uckkchallenge_clean_status((string)$data->decision);
}

/**
 * Return language label for a status.
 *
 * @param string $status Status.
 * @return string
 */
function mod_uckkchallenge_status_label(string $status): string {
    $key = 'status' . str_replace('_', '', $status);

    if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
        return get_string($key, 'uckkchallenge');
    }

    return s(ucfirst(str_replace('_', ' ', $status)));
}

/**
 * Render a submission list for this challenge.
 *
 * @param cm_info|stdClass $cm Course module.
 * @param stdClass $challenge Challenge instance.
 * @return string
 */
function mod_uckkchallenge_render_submission_list($cm, stdClass $challenge): string {
    global $DB, $OUTPUT;

    if (!mod_uckkchallenge_table_exists('uckkchallenge_sub')) {
        throw new moodle_exception('missingsubmissiontable', 'uckkchallenge');
    }

    $reviewablestatuses = [
        'submitted',
        'under_review',
        'integrity_review',
        'revision_required',
        'resubmitted',
        'contested',
    ];

    [$statussql, $params] = $DB->get_in_or_equal($reviewablestatuses, SQL_PARAMS_NAMED, 'status');
    $params['challengeid'] = $challenge->id;

    $submissions = $DB->get_records_select(
        'uckkchallenge_sub',
        "challengeid = :challengeid AND status {$statussql}",
        $params,
        'timemodified ASC, timecreated ASC',
        '*',
        0,
        200
    );

    if (!$submissions) {
        return $OUTPUT->notification(get_string('nosubmissionstoevaluate', 'uckkchallenge'), 'info');
    }

    $table = new html_table();
    $table->head = [
        get_string('submissiontitle', 'uckkchallenge'),
        get_string('submitter', 'uckkchallenge'),
        get_string('status', 'uckkchallenge'),
        get_string('timemodified', 'core'),
        get_string('actions', 'core'),
    ];
    $table->attributes['class'] = 'generaltable mod-uckkchallenge-evaluation-list';

    foreach ($submissions as $submission) {
        $submitter = $DB->get_record('user', ['id' => $submission->userid], '*', IGNORE_MISSING);
        $submittername = $submitter ? fullname($submitter) : get_string('unknownuser', 'uckkchallenge');

        $evaluateurl = new moodle_url('/mod/uckkchallenge/evaluate.php', [
            'id' => $cm->id,
            'sid' => $submission->id,
        ]);

        $table->data[] = new html_table_row([
            html_writer::link($evaluateurl, format_string($submission->title)),
            s($submittername),
            mod_uckkchallenge_status_label((string)$submission->status),
            !empty($submission->timemodified) ? userdate((int)$submission->timemodified) : '',
            html_writer::link($evaluateurl, get_string('evaluate', 'uckkchallenge'), ['class' => 'btn btn-primary btn-sm']),
        ]);
    }

    return html_writer::table($table);
}

/**
 * Trigger the evaluation event when the event class exists.
 *
 * @param context_module $context Module context.
 * @param int $evaluationid Evaluation record id.
 * @param stdClass $submission Submission record.
 * @param string $decision Evaluation decision.
 */
function mod_uckkchallenge_trigger_evaluation_event(
    context_module $context,
    int $evaluationid,
    stdClass $submission,
    string $decision
): void {
    $eventclass = '\\mod_uckkchallenge\\event\\evaluation_created';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'context' => $context,
        'objectid' => $evaluationid,
        'relateduserid' => (int)$submission->userid,
        'other' => [
            'submissionid' => (int)$submission->id,
            'decision' => $decision,
        ],
    ]);

    $event->trigger();
}

$id = required_param('id', PARAM_INT);
$sid = optional_param('sid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('uckkchallenge', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$challenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/uckkchallenge:evaluate', $context);

$canvalidateintegrity = has_capability('mod/uckkchallenge:validateintegrity', $context);
$canarchive = has_capability('mod/uckkchallenge:archive', $context);

$pageurlparams = ['id' => $cm->id];
if ($sid > 0) {
    $pageurlparams['sid'] = $sid;
}

$PAGE->set_url(new moodle_url('/mod/uckkchallenge/evaluate.php', $pageurlparams));
$PAGE->set_title(format_string($challenge->name) . ': ' . get_string('evaluate', 'uckkchallenge'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('evaluatechallenge', 'uckkchallenge'));

if ($sid <= 0) {
    echo html_writer::tag(
        'p',
        get_string('evaluatelistintro', 'uckkchallenge'),
        ['class' => 'mod-uckkchallenge-evaluate-intro']
    );

    echo mod_uckkchallenge_render_submission_list($cm, $challenge);

    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]),
            get_string('backtochallenge', 'uckkchallenge'),
            ['class' => 'btn btn-secondary']
        ),
        'mod-uckkchallenge-evaluate-actions'
    );

    echo $OUTPUT->footer();
    exit;
}

if (!mod_uckkchallenge_table_exists('uckkchallenge_sub')) {
    throw new moodle_exception('missingsubmissiontable', 'uckkchallenge');
}

if (!mod_uckkchallenge_table_exists('uckkchallenge_eval')) {
    throw new moodle_exception('missingevaluationtable', 'uckkchallenge');
}

$submission = $DB->get_record('uckkchallenge_sub', [
    'id' => $sid,
    'challengeid' => $challenge->id,
], '*', MUST_EXIST);

$submitter = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);

$form = new mod_uckkchallenge_evaluate_form(null, [
    'cm' => $cm,
    'course' => $course,
    'challenge' => $challenge,
    'submission' => $submission,
    'submitter' => $submitter,
    'canvalidateintegrity' => $canvalidateintegrity,
    'canarchive' => $canarchive,
]);

$form->set_data([
    'id' => $cm->id,
    'sid' => $submission->id,
    'grade' => $submission->grade ?? '',
    'mentorfeedback' => $submission->mentorfeedback ?? '',
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id]));
}

if ($data = $form->get_data()) {
    $now = time();
    $newstatus = mod_uckkchallenge_resolve_evaluation_status($data, $challenge, $canvalidateintegrity);
    $grade = ($data->grade === '' || $data->grade === null) ? null : (float)$data->grade;

    $metadata = [
        'source' => 'human',
        'component' => 'mod_uckkchallenge',
        'itemtype' => 'challenge_evaluation',
        'challengeid' => (int)$challenge->id,
        'submissionid' => (int)$submission->id,
        'submitterid' => (int)$submission->userid,
        'evaluatorid' => (int)$USER->id,
        'decision' => $data->decision,
        'finalstatus' => $newstatus,
        'validationstate' => $data->validationstate,
        'integrityrequired' => !empty($challenge->integrityrequired),
        'integrityreviewrequested' => !empty($data->requestintegrityreview),
        'archiveexportrequested' => $canarchive && !empty($data->requestarchiveexport),
        'humanreviewconfirmed' => !empty($data->confirmhumanreview),
        'timecreated' => $now,
    ];

    $provenancepayload = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $provenancehash = hash('sha256', $provenancepayload);

    $transaction = $DB->start_delegated_transaction();

    try {
        $evaluationrecord = mod_uckkchallenge_record_for_table('uckkchallenge_eval', [
            'challengeid' => (int)$challenge->id,
            'submissionid' => (int)$submission->id,
            'subid' => (int)$submission->id,
            'userid' => (int)$submission->userid,
            'evaluatorid' => (int)$USER->id,
            'contextid' => (int)$context->id,
            'grade' => $grade,
            'decision' => $data->decision,
            'status' => $newstatus,
            'validationstate' => $data->validationstate,
            'mentorfeedback' => $data->mentorfeedback,
            'feedback' => $data->mentorfeedback,
            'visibility' => 'course',
            'provenance' => 'human',
            'provenancehash' => $provenancehash,
            'metadata' => $provenancepayload,
            'createdby' => (int)$USER->id,
            'modifiedby' => (int)$USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $evaluationid = (int)$DB->insert_record('uckkchallenge_eval', $evaluationrecord);

        $submissionrecord = mod_uckkchallenge_record_for_table('uckkchallenge_sub', [
            'id' => (int)$submission->id,
            'status' => $newstatus,
            'grade' => $grade,
            'mentorfeedback' => $data->mentorfeedback,
            'modifiedby' => (int)$USER->id,
            'timemodified' => $now,
        ]);

        $DB->update_record('uckkchallenge_sub', $submissionrecord);

        if (mod_uckkchallenge_table_exists('uckkchallenge_state')) {
            $staterecord = mod_uckkchallenge_record_for_table('uckkchallenge_state', [
                'challengeid' => (int)$challenge->id,
                'submissionid' => (int)$submission->id,
                'subid' => (int)$submission->id,
                'evaluationid' => $evaluationid,
                'userid' => (int)$submission->userid,
                'actorid' => (int)$USER->id,
                'contextid' => (int)$context->id,
                'fromstatus' => (string)$submission->status,
                'tostatus' => $newstatus,
                'status' => $newstatus,
                'reason' => $data->mentorfeedback,
                'provenance' => 'human',
                'provenancehash' => $provenancehash,
                'metadata' => $provenancepayload,
                'createdby' => (int)$USER->id,
                'modifiedby' => (int)$USER->id,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);

            $DB->insert_record('uckkchallenge_state', $staterecord);
        }

        if (function_exists('uckkchallenge_update_grades')) {
            uckkchallenge_update_grades($challenge, (int)$submission->userid);
        }

        $transaction->allow_commit();

        mod_uckkchallenge_trigger_evaluation_event($context, $evaluationid, $submission, (string)$data->decision);

        redirect(
            new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id]),
            get_string('evaluationsaved', 'uckkchallenge'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $exception) {
        $transaction->rollback($exception);
    }
}

echo $OUTPUT->box_start('generalbox mod-uckkchallenge-submission-summary');

echo html_writer::tag('h3', get_string('submission', 'uckkchallenge'));
echo html_writer::tag('p', html_writer::tag('strong', get_string('submitter', 'uckkchallenge') . ': ') . s(fullname($submitter)));
echo html_writer::tag('p', html_writer::tag('strong', get_string('submissiontitle', 'uckkchallenge') . ': ') . format_string($submission->title));

if (!empty($submission->body)) {
    echo html_writer::tag('h4', get_string('submissionbody', 'uckkchallenge'));
    echo format_text($submission->body, FORMAT_PLAIN, ['context' => $context]);
}

if (!empty($submission->proofsummary)) {
    echo html_writer::tag('h4', get_string('proofsummary', 'uckkchallenge'));
    echo format_text($submission->proofsummary, FORMAT_PLAIN, ['context' => $context]);
}

echo $OUTPUT->box_end();

$form->display();

echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id]),
        get_string('backtoevaluationlist', 'uckkchallenge'),
        ['class' => 'btn btn-secondary']
    ),
    'mod-uckkchallenge-evaluate-actions'
);

echo $OUTPUT->footer();