<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Activity instance form for Défis King Klown.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * UCKK Challenge activity form.
 *
 * This form defines the editable configuration for one Défi King Klown
 * activity instance. Workflow transitions, review decisions, validation,
 * archive export, badge awards, and integrity actions must remain in service
 * classes and capability-checked controllers, not in this form.
 */
class mod_uckkchallenge_mod_form extends moodleform_mod {
    /**
     * Define the activity form.
     */
    public function definition(): void {
        $mform = $this->_form;

        $this->add_general_section($mform);
        $this->add_challenge_design_section($mform);
        $this->add_evidence_section($mform);
        $this->add_timeline_section($mform);
        $this->add_governance_section($mform);
        $this->add_archive_section($mform);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add general activity settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_general_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('challengename', 'uckkchallenge'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('challengeintro', 'uckkchallenge'));

        $mform->addElement('text', 'challengecode', get_string('challengecode', 'uckkchallenge'), [
            'size' => 32,
            'maxlength' => 100,
        ]);
        $mform->setType('challengecode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('challengecode', 'challengecode', 'uckkchallenge');

        $mform->addElement('select', 'challengetype', get_string('challengetype', 'uckkchallenge'), $this->get_challenge_type_options());
        $mform->setDefault('challengetype', 'internal_learning');
        $mform->addRule('challengetype', null, 'required', null, 'client');

        $mform->addElement('select', 'status', get_string('challengestatus', 'uckkchallenge'), $this->get_status_options());
        $mform->setDefault('status', 'draft');
        $mform->addRule('status', null, 'required', null, 'client');
    }

    /**
     * Add challenge design fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_challenge_design_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'challengedesign', get_string('challengedesign', 'uckkchallenge'));

        $mform->addElement('textarea', 'statement', get_string('challengestatement', 'uckkchallenge'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('statement', PARAM_RAW);
        $mform->addRule('statement', null, 'required', null, 'client');
        $mform->addHelpButton('statement', 'challengestatement', 'uckkchallenge');

        $mform->addElement('textarea', 'contexttext', get_string('challengecontext', 'uckkchallenge'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('contexttext', PARAM_RAW);
        $mform->addHelpButton('contexttext', 'challengecontext', 'uckkchallenge');

        $mform->addElement('textarea', 'rules', get_string('challengerules', 'uckkchallenge'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('rules', PARAM_RAW);
        $mform->addRule('rules', null, 'required', null, 'client');
        $mform->addHelpButton('rules', 'challengerules', 'uckkchallenge');

        $mform->addElement('textarea', 'corridors', get_string('challengecorridors', 'uckkchallenge'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('corridors', PARAM_RAW);
        $mform->addHelpButton('corridors', 'challengecorridors', 'uckkchallenge');

        $mform->addElement('textarea', 'ethicalconstraints', get_string('ethicalconstraints', 'uckkchallenge'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('ethicalconstraints', PARAM_RAW);
        $mform->addHelpButton('ethicalconstraints', 'ethicalconstraints', 'uckkchallenge');
    }

    /**
     * Add evidence and evaluation fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_evidence_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'evidenceandevaluation', get_string('evidenceandevaluation', 'uckkchallenge'));

        $mform->addElement('textarea', 'evidencepolicy', get_string('evidencepolicy', 'uckkchallenge'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('evidencepolicy', PARAM_RAW);
        $mform->addRule('evidencepolicy', null, 'required', null, 'client');
        $mform->addHelpButton('evidencepolicy', 'evidencepolicy', 'uckkchallenge');

        $mform->addElement('textarea', 'criteria', get_string('evaluationcriteria', 'uckkchallenge'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('criteria', PARAM_RAW);
        $mform->addRule('criteria', null, 'required', null, 'client');
        $mform->addHelpButton('criteria', 'evaluationcriteria', 'uckkchallenge');

        $mform->addElement('advcheckbox', 'teamsubmissions', get_string('teamsubmissions', 'uckkchallenge'));
        $mform->setDefault('teamsubmissions', 0);
        $mform->addHelpButton('teamsubmissions', 'teamsubmissions', 'uckkchallenge');

        $mform->addElement('select', 'maxsubmissions', get_string('maxsubmissions', 'uckkchallenge'), $this->get_max_submission_options());
        $mform->setDefault('maxsubmissions', 1);

        $mform->addElement('advcheckbox', 'allowresubmission', get_string('allowresubmission', 'uckkchallenge'));
        $mform->setDefault('allowresubmission', 1);
    }

    /**
     * Add timeline fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_timeline_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'timeline', get_string('timeline', 'uckkchallenge'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('timeopen', 0);

        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('timeclose', 0);

        $mform->addElement('date_time_selector', 'timereviewby', get_string('timereviewby', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('timereviewby', 0);
        $mform->addHelpButton('timereviewby', 'timereviewby', 'uckkchallenge');
    }

    /**
     * Add governance and integrity fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_governance_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'governance', get_string('governance', 'uckkchallenge'));

        $mform->addElement('advcheckbox', 'integrityrequired', get_string('integrityrequired', 'uckkchallenge'));
        $mform->setDefault('integrityrequired', 1);
        $mform->addHelpButton('integrityrequired', 'integrityrequired', 'uckkchallenge');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkchallenge'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('textarea', 'integritynotes', get_string('integritynotes', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('integritynotes', PARAM_RAW);
        $mform->addHelpButton('integritynotes', 'integritynotes', 'uckkchallenge');

        $mform->addElement('textarea', 'aipolicy', get_string('aipolicy', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('aipolicy', PARAM_RAW);
        $mform->setDefault('aipolicy', get_string('defaultaipolicy', 'uckkchallenge'));
        $mform->addHelpButton('aipolicy', 'aipolicy', 'uckkchallenge');
    }

    /**
     * Add archive and recognition fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_archive_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'archiveandrecognition', get_string('archiveandrecognition', 'uckkchallenge'));

        $mform->addElement('select', 'archivepolicy', get_string('archivepolicy', 'uckkchallenge'), $this->get_archive_policy_options());
        $mform->setDefault('archivepolicy', 'summary');
        $mform->addRule('archivepolicy', null, 'required', null, 'client');
        $mform->addHelpButton('archivepolicy', 'archivepolicy', 'uckkchallenge');

        $mform->addElement('textarea', 'publicsummary', get_string('publicsummary', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('publicsummary', PARAM_RAW);
        $mform->addHelpButton('publicsummary', 'publicsummary', 'uckkchallenge');

        $mform->addElement('textarea', 'competencylinks', get_string('competencylinks', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('competencylinks', PARAM_RAW);
        $mform->addHelpButton('competencylinks', 'competencylinks', 'uckkchallenge');

        $mform->addElement('textarea', 'badgelinks', get_string('badgelinks', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('badgelinks', PARAM_RAW);
        $mform->addHelpButton('badgelinks', 'badgelinks', 'uckkchallenge');
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Fieldname => error message.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!empty($data['timeopen']) && !empty($data['timeclose']) && $data['timeclose'] <= $data['timeopen']) {
            $errors['timeclose'] = get_string('timeclosemustbeafteropen', 'uckkchallenge');
        }

        if (!empty($data['timeclose']) && !empty($data['timereviewby']) && $data['timereviewby'] < $data['timeclose']) {
            $errors['timereviewby'] = get_string('timereviewmustbeafterclose', 'uckkchallenge');
        }

        if (!empty($data['challengecode']) && !preg_match('/^[A-Za-z0-9_-]+$/', $data['challengecode'])) {
            $errors['challengecode'] = get_string('invalidchallengecode', 'uckkchallenge');
        }

        foreach (['statement', 'rules', 'evidencepolicy', 'criteria'] as $requiredfield) {
            if (trim((string)($data[$requiredfield] ?? '')) === '') {
                $errors[$requiredfield] = get_string('required');
            }
        }

        foreach (['corridors', 'competencylinks', 'badgelinks'] as $jsonfield) {
            $value = trim((string)($data[$jsonfield] ?? ''));

            if ($value !== '' && !$this->is_valid_json_or_plain_list($value)) {
                $errors[$jsonfield] = get_string('invalidjsonorlist', 'uckkchallenge');
            }
        }

        return $errors;
    }

    /**
     * Preprocess defaults when editing an existing instance.
     *
     * @param stdClass $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        foreach ([
            'statement',
            'contexttext',
            'rules',
            'corridors',
            'ethicalconstraints',
            'evidencepolicy',
            'criteria',
            'integritynotes',
            'aipolicy',
            'publicsummary',
            'competencylinks',
            'badgelinks',
        ] as $field) {
            if (!isset($defaultvalues[$field])) {
                $defaultvalues[$field] = '';
            }
        }

        if (!isset($defaultvalues['status'])) {
            $defaultvalues['status'] = 'draft';
        }

        if (!isset($defaultvalues['visibility'])) {
            $defaultvalues['visibility'] = 'course';
        }

        if (!isset($defaultvalues['archivepolicy'])) {
            $defaultvalues['archivepolicy'] = 'summary';
        }

        if (!isset($defaultvalues['integrityrequired'])) {
            $defaultvalues['integrityrequired'] = 1;
        }
    }

    /**
     * Completion rule fields used by Moodle completion settings.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;

        $mform->addElement('advcheckbox', 'completionrequiresubmission', '', get_string('completionrequiresubmission', 'uckkchallenge'));
        $mform->setDefault('completionrequiresubmission', 0);

        $mform->addElement('advcheckbox', 'completionrequirevalidation', '', get_string('completionrequirevalidation', 'uckkchallenge'));
        $mform->setDefault('completionrequirevalidation', 0);

        return [
            'completionrequiresubmission',
            'completionrequirevalidation',
        ];
    }

    /**
     * Whether completion rules are enabled.
     *
     * @param array $data Submitted form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionrequiresubmission']) || !empty($data['completionrequirevalidation']);
    }

    /**
     * Challenge type options.
     *
     * @return array<string, string>
     */
    private function get_challenge_type_options(): array {
        return [
            'internal_learning' => get_string('challengetype:internal_learning', 'uckkchallenge'),
            'public_pedagogical' => get_string('challengetype:public_pedagogical', 'uckkchallenge'),
            'institutional_audit' => get_string('challengetype:institutional_audit', 'uckkchallenge'),
            'system_mapping' => get_string('challengetype:system_mapping', 'uckkchallenge'),
            'prototype' => get_string('challengetype:prototype', 'uckkchallenge'),
            'mobilisation' => get_string('challengetype:mobilisation', 'uckkchallenge'),
            'capstone' => get_string('challengetype:capstone', 'uckkchallenge'),
            'king_klown_public' => get_string('challengetype:king_klown_public', 'uckkchallenge'),
        ];
    }

    /**
     * Workflow status options available at instance configuration time.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            'draft' => get_string('status:draft', 'uckkchallenge'),
            'published' => get_string('status:published', 'uckkchallenge'),
            'open' => get_string('status:open', 'uckkchallenge'),
            'closed' => get_string('status:closed', 'uckkchallenge'),
            'archived' => get_string('status:archived', 'uckkchallenge'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkchallenge'),
            'course' => get_string('visibility:course', 'uckkchallenge'),
            'cohort' => get_string('visibility:cohort', 'uckkchallenge'),
            'program' => get_string('visibility:program', 'uckkchallenge'),
            'institution' => get_string('visibility:institution', 'uckkchallenge'),
            'public' => get_string('visibility:public', 'uckkchallenge'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkchallenge'),
        ];
    }

    /**
     * Archive policy options.
     *
     * @return array<string, string>
     */
    private function get_archive_policy_options(): array {
        return [
            'none' => get_string('archivepolicy:none', 'uckkchallenge'),
            'summary' => get_string('archivepolicy:summary', 'uckkchallenge'),
            'full' => get_string('archivepolicy:full', 'uckkchallenge'),
        ];
    }

    /**
     * Maximum submission options.
     *
     * @return array<int, string>
     */
    private function get_max_submission_options(): array {
        return [
            1 => '1',
            2 => '2',
            3 => '3',
            5 => '5',
            10 => '10',
            0 => get_string('unlimited'),
        ];
    }

    /**
     * Accept either JSON or a plain newline-separated list.
     *
     * This allows a form-friendly entry during editing while still supporting
     * JSON storage in the service/lib layer.
     *
     * @param string $value Submitted value.
     * @return bool
     */
    private function is_valid_json_or_plain_list(string $value): bool {
        if ($value === '') {
            return true;
        }

        json_decode($value);

        if (json_last_error() === JSON_ERROR_NONE) {
            return true;
        }

        return str_contains($value, "\n") || !str_contains($value, '{') && !str_contains($value, '[');
    }
}