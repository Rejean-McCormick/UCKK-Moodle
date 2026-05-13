<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Vote controller for UCKK assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use mod_uckkassembly\local\vote_service;

/**
 * Assembly vote form.
 *
 * This form collects a participant vote and its rationale. It does not decide
 * motion outcomes, publish decisions, archive minutes, or resolve contests.
 * Those operations belong to capability-checked services.
 */
final class mod_uckkassembly_vote_form extends moodleform {
    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $assembly = (object)$this->_customdata['assembly'];
        $motion = (object)$this->_customdata['motion'];
        $existingvote = (object)($this->_customdata['existingvote'] ?? []);
        $context = $this->_customdata['context'];

        $mform->addElement('header', 'voteheader', get_string('voteonmotion', 'uckkassembly'));

        $mform->addElement(
            'static',
            'assemblyname',
            get_string('assembly', 'uckkassembly'),
            format_string($assembly->name)
        );

        $mform->addElement(
            'static',
            'motiontitle',
            get_string('motion', 'uckkassembly'),
            format_string($motion->title ?? $motion->name ?? '')
        );

        if (!empty($motion->summary)) {
            $mform->addElement(
                'static',
                'motionsummary',
                get_string('motionsummary', 'uckkassembly'),
                format_text($motion->summary, FORMAT_HTML, ['context' => $context])
            );
        }

        if (!empty($motion->decisiontype)) {
            $mform->addElement(
                'static',
                'decisiontype',
                get_string('decisiontype', 'uckkassembly'),
                get_string('decisiontype:' . $motion->decisiontype, 'uckkassembly')
            );
        }

        $mform->addElement('select', 'votechoice', get_string('votechoice', 'uckkassembly'), self::get_vote_choice_options());
        $mform->setDefault('votechoice', $existingvote->votechoice ?? 'abstain');
        $mform->addRule('votechoice', null, 'required', null, 'client');

        $mform->addElement('textarea', 'rationale', get_string('voterationale', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('rationale', PARAM_RAW);
        $mform->addHelpButton('rationale', 'voterationale', 'uckkassembly');
        $mform->setDefault('rationale', $existingvote->rationale ?? '');

        $mform->addElement('select', 'confidence', get_string('voteconfidence', 'uckkassembly'), self::get_confidence_options());
        $mform->setDefault('confidence', $existingvote->confidence ?? 'medium');

        $mform->addElement('select', 'visibility', get_string('votevisibility', 'uckkassembly'), self::get_visibility_options());
        $mform->setDefault('visibility', $existingvote->visibility ?? 'course');

        $mform->addElement('advcheckbox', 'conflictdeclared', get_string('conflictdeclared', 'uckkassembly'));
        $mform->setDefault('conflictdeclared', !empty($existingvote->conflictdeclared) ? 1 : 0);
        $mform->addHelpButton('conflictdeclared', 'conflictdeclared', 'uckkassembly');

        $mform->addElement('textarea', 'conflictnotes', get_string('conflictnotes', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('conflictnotes', PARAM_RAW);
        $mform->setDefault('conflictnotes', $existingvote->conflictnotes ?? '');
        $mform->hideIf('conflictnotes', 'conflictdeclared', 'notchecked');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassistedreflection', 'uckkassembly'));
        $mform->setDefault('aiassisted', !empty($existingvote->aiassisted) ? 1 : 0);
        $mform->addHelpButton('aiassisted', 'aiassistedreflection', 'uckkassembly');

        $mform->addElement('textarea', 'ailog', get_string('aicollaborationlog', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->setDefault('ailog', $existingvote->ailog ?? '');
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');

        $mform->addElement(
            'static',
            'authoritynotice',
            get_string('authoritynotice', 'uckkassembly'),
            get_string('votenonsovereignnotice', 'uckkassembly')
        );

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'motionid');
        $mform->setType('motionid', PARAM_INT);

        $mform->addElement('hidden', 'voteid');
        $mform->setType('voteid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('submitvote', 'uckkassembly'));
    }

    /**
     * Validate vote input.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $allowedchoices = array_keys(self::get_vote_choice_options());
        $choice = (string)($data['votechoice'] ?? '');

        if (!in_array($choice, $allowedchoices, true)) {
            $errors['votechoice'] = get_string('invalidvotechoice', 'uckkassembly');
        }

        if (in_array($choice, ['oppose', 'block', 'request_amendment'], true)) {
            if (trim((string)($data['rationale'] ?? '')) === '') {
                $errors['rationale'] = get_string('voterationalerequired', 'uckkassembly');
            }
        }

        if (!empty($data['conflictdeclared']) && trim((string)($data['conflictnotes'] ?? '')) === '') {
            $errors['conflictnotes'] = get_string('conflictnotesrequired', 'uckkassembly');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Vote choice options.
     *
     * @return array<string, string>
     */
    private static function get_vote_choice_options(): array {
        return [
            'support' => get_string('votechoice:support', 'uckkassembly'),
            'oppose' => get_string('votechoice:oppose', 'uckkassembly'),
            'abstain' => get_string('votechoice:abstain', 'uckkassembly'),
            'request_amendment' => get_string('votechoice:requestamendment', 'uckkassembly'),
            'block' => get_string('votechoice:block', 'uckkassembly'),
        ];
    }

    /**
     * Confidence options.
     *
     * @return array<string, string>
     */
    private static function get_confidence_options(): array {
        return [
            'low' => get_string('confidence:low', 'uckkassembly'),
            'medium' => get_string('confidence:medium', 'uckkassembly'),
            'high' => get_string('confidence:high', 'uckkassembly'),
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
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Assembly instance id.
$motionid = required_param('motionid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

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
require_capability('mod/uckkassembly:vote', $context);

$motion = $DB->get_record('uckkassembly_motion', [
    'id' => $motionid,
    'assemblyid' => $assembly->id,
], '*', MUST_EXIST);

$viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkassembly/vote.php', [
    'id' => $cm->id,
    'motionid' => $motion->id,
]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$service = new vote_service();

if (!$service->can_vote($assembly, $motion, $cm, $context, $USER)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('voteonmotion', 'uckkassembly'));
    echo $OUTPUT->notification(get_string('cannotvoteonmotion', 'uckkassembly'), notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

$existingvote = $service->get_existing_vote($assembly, $motion, $cm, $context, $USER);

$form = new mod_uckkassembly_vote_form($pageurl, [
    'assembly' => $assembly,
    'motion' => $motion,
    'existingvote' => $existingvote,
    'context' => $context,
]);

$defaultdata = new stdClass();
$defaultdata->id = $cm->id;
$defaultdata->motionid = $motion->id;
$defaultdata->voteid = !empty($existingvote->id) ? (int)$existingvote->id : 0;
$defaultdata->returnurl = $return->out_as_local_url(false);

if (!empty($existingvote)) {
    $defaultdata->votechoice = $existingvote->votechoice ?? 'abstain';
    $defaultdata->rationale = $existingvote->rationale ?? '';
    $defaultdata->confidence = $existingvote->confidence ?? 'medium';
    $defaultdata->visibility = $existingvote->visibility ?? 'course';
    $defaultdata->conflictdeclared = !empty($existingvote->conflictdeclared) ? 1 : 0;
    $defaultdata->conflictnotes = $existingvote->conflictnotes ?? '';
    $defaultdata->aiassisted = !empty($existingvote->aiassisted) ? 1 : 0;
    $defaultdata->ailog = $existingvote->ailog ?? '';
}

$form->set_data($defaultdata);

if ($form->is_cancelled()) {
    redirect($return);
}

if ($data = $form->get_data()) {
    require_sesskey();

    $vote = $service->record_vote($assembly, $motion, $cm, $course, $context, $USER, [
        'voteid' => (int)($data->voteid ?? 0),
        'votechoice' => $data->votechoice,
        'rationale' => $data->rationale,
        'confidence' => $data->confidence,
        'visibility' => $data->visibility,
        'conflictdeclared' => !empty($data->conflictdeclared) ? 1 : 0,
        'conflictnotes' => $data->conflictnotes,
        'aiassisted' => !empty($data->aiassisted) ? 1 : 0,
        'ailog' => $data->ailog,
        'status' => 'validated',
        'provenance' => !empty($data->aiassisted) ? 'ai_assisted' : 'human',
    ]);

    $service->refresh_motion_vote_state($assembly, $motion, $cm, $course, $context, $USER, $vote);

    redirect(
        $return,
        get_string('votesubmitted', 'uckkassembly'),
        null,
        notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('voteonmotion', 'uckkassembly'));

echo html_writer::div(
    get_string('votenonsovereignnotice', 'uckkassembly'),
    'alert alert-info uckkassembly-vote__notice',
    ['role' => 'status']
);

if (!empty($existingvote->id)) {
    echo html_writer::div(
        get_string('existingvotenotice', 'uckkassembly'),
        'alert alert-warning uckkassembly-vote__existing',
        ['role' => 'status']
    );
}

if (!empty($motion->timeclose) && (int)$motion->timeclose < time()) {
    echo html_writer::div(
        get_string('motionvotingclosed', 'uckkassembly'),
        'alert alert-secondary uckkassembly-vote__closed',
        ['role' => 'status']
    );
}

$form->display();

echo html_writer::link($return, get_string('backtoassembly', 'uckkassembly'), [
    'class' => 'btn btn-secondary mt-3',
]);

echo $OUTPUT->footer();