<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Activity instance form for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * UCKK Assembly activity form.
 *
 * This form configures one Assemblée activity instance. It does not publish
 * decisions, count votes authoritatively, archive records, decide integrity
 * cases, or resolve contestations. Those actions must remain in service classes
 * and capability-checked controllers.
 */
class mod_uckkassembly_mod_form extends moodleform_mod {
    /**
     * Define the activity settings form.
     */
    public function definition(): void {
        $mform = $this->_form;

        $this->add_general_section($mform);
        $this->add_scope_section($mform);
        $this->add_deliberation_section($mform);
        $this->add_motion_section($mform);
        $this->add_voting_section($mform);
        $this->add_decision_section($mform);
        $this->add_contestability_section($mform);
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

        $mform->addElement('text', 'name', get_string('assemblyname', 'uckkassembly'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('assemblyintro', 'uckkassembly'));

        $mform->addElement('text', 'assemblycode', get_string('assemblycode', 'uckkassembly'), [
            'size' => 32,
            'maxlength' => 100,
        ]);
        $mform->setType('assemblycode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('assemblycode', 'assemblycode', 'uckkassembly');

        $mform->addElement('select', 'assemblytype', get_string('assemblytype', 'uckkassembly'), $this->get_assembly_type_options());
        $mform->setDefault('assemblytype', 'savoirs');
        $mform->addRule('assemblytype', null, 'required', null, 'client');

        $mform->addElement('select', 'status', get_string('assemblystatus', 'uckkassembly'), $this->get_status_options());
        $mform->setDefault('status', 'planned');
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');
    }

    /**
     * Add scope and participation settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_scope_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'assemblyscopeheader', get_string('assemblyscope', 'uckkassembly'));

        $mform->addElement('textarea', 'scope', get_string('scope', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('scope', PARAM_RAW);
        $mform->addRule('scope', null, 'required', null, 'client');
        $mform->addHelpButton('scope', 'scope', 'uckkassembly');

        $mform->addElement('textarea', 'participantpolicy', get_string('participantpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('participantpolicy', PARAM_RAW);
        $mform->addHelpButton('participantpolicy', 'participantpolicy', 'uckkassembly');

        $mform->addElement('advcheckbox', 'allowobservers', get_string('allowobservers', 'uckkassembly'));
        $mform->setDefault('allowobservers', 1);

        $mform->addElement('advcheckbox', 'allowpublicsummary', get_string('allowpublicsummary', 'uckkassembly'));
        $mform->setDefault('allowpublicsummary', 0);
        $mform->addHelpButton('allowpublicsummary', 'allowpublicsummary', 'uckkassembly');
    }

    /**
     * Add agenda, rules, and deliberation settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_deliberation_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'deliberationheader', get_string('deliberationdesign', 'uckkassembly'));

        $mform->addElement('textarea', 'agenda', get_string('agenda', 'uckkassembly'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('agenda', PARAM_RAW);
        $mform->addRule('agenda', null, 'required', null, 'client');
        $mform->addHelpButton('agenda', 'agenda', 'uckkassembly');

        $mform->addElement('textarea', 'rules', get_string('assemblyrules', 'uckkassembly'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('rules', PARAM_RAW);
        $mform->addRule('rules', null, 'required', null, 'client');
        $mform->addHelpButton('rules', 'assemblyrules', 'uckkassembly');

        $mform->addElement('textarea', 'argumentpolicy', get_string('argumentpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('argumentpolicy', PARAM_RAW);
        $mform->addHelpButton('argumentpolicy', 'argumentpolicy', 'uckkassembly');

        $mform->addElement('textarea', 'objectionpolicy', get_string('objectionpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('objectionpolicy', PARAM_RAW);
        $mform->addHelpButton('objectionpolicy', 'objectionpolicy', 'uckkassembly');

        $mform->addElement('textarea', 'minorityreportpolicy', get_string('minorityreportpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('minorityreportpolicy', PARAM_RAW);
        $mform->addHelpButton('minorityreportpolicy', 'minorityreportpolicy', 'uckkassembly');
    }

    /**
     * Add motion and amendment settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_motion_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'motionheader', get_string('motionsandamendments', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'allowmotions', get_string('allowmotions', 'uckkassembly'));
        $mform->setDefault('allowmotions', 1);

        $mform->addElement('date_time_selector', 'motionopen', get_string('motionopen', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('motionopen', 0);

        $mform->addElement('date_time_selector', 'motionclose', get_string('motionclose', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('motionclose', 0);

        $mform->addElement('advcheckbox', 'allowamendments', get_string('allowamendments', 'uckkassembly'));
        $mform->setDefault('allowamendments', 1);

        $mform->addElement('date_time_selector', 'amendmentclose', get_string('amendmentclose', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('amendmentclose', 0);

        $mform->addElement('select', 'motionapproval', get_string('motionapproval', 'uckkassembly'), [
            'auto_accept' => get_string('motionapproval:autoaccept', 'uckkassembly'),
            'facilitator_review' => get_string('motionapproval:facilitatorreview', 'uckkassembly'),
            'mentor_review' => get_string('motionapproval:mentorreview', 'uckkassembly'),
            'inquisiteur_review' => get_string('motionapproval:inquisiteurreview', 'uckkassembly'),
        ]);
        $mform->setDefault('motionapproval', 'facilitator_review');
    }

    /**
     * Add voting / readings settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_voting_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'votingheader', get_string('votingandreadings', 'uckkassembly'));

        $mform->addElement('select', 'decisionmethod', get_string('decisionmethod', 'uckkassembly'), $this->get_decision_method_options());
        $mform->setDefault('decisionmethod', 'rough_consensus');
        $mform->addRule('decisionmethod', null, 'required', null, 'client');
        $mform->addHelpButton('decisionmethod', 'decisionmethod', 'uckkassembly');

        $mform->addElement('date_time_selector', 'votingopen', get_string('votingopen', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('votingopen', 0);

        $mform->addElement('date_time_selector', 'votingclose', get_string('votingclose', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('votingclose', 0);

        $mform->addElement('advcheckbox', 'allowmultiplereadings', get_string('allowmultiplereadings', 'uckkassembly'));
        $mform->setDefault('allowmultiplereadings', 1);
        $mform->addHelpButton('allowmultiplereadings', 'allowmultiplereadings', 'uckkassembly');

        $mform->addElement('advcheckbox', 'showrawcount', get_string('showrawcount', 'uckkassembly'));
        $mform->setDefault('showrawcount', 1);

        $mform->addElement('advcheckbox', 'showrolereading', get_string('showrolereading', 'uckkassembly'));
        $mform->setDefault('showrolereading', 1);

        $mform->addElement('advcheckbox', 'showminorityreading', get_string('showminorityreading', 'uckkassembly'));
        $mform->setDefault('showminorityreading', 1);

        $mform->addElement('advcheckbox', 'showintegrityreading', get_string('showintegrityreading', 'uckkassembly'));
        $mform->setDefault('showintegrityreading', 1);
    }

    /**
     * Add decision-publication settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_decision_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'decisionheader', get_string('decisionpublication', 'uckkassembly'));

        $mform->addElement('select', 'decisiontype', get_string('decisiontype', 'uckkassembly'), $this->get_decision_type_options());
        $mform->setDefault('decisiontype', 'recommendation');

        $mform->addElement('textarea', 'decisiontemplate', get_string('decisiontemplate', 'uckkassembly'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('decisiontemplate', PARAM_RAW);
        $mform->addHelpButton('decisiontemplate', 'decisiontemplate', 'uckkassembly');

        $mform->addElement('advcheckbox', 'requirefacilitatorapproval', get_string('requirefacilitatorapproval', 'uckkassembly'));
        $mform->setDefault('requirefacilitatorapproval', 1);

        $mform->addElement('advcheckbox', 'requireminutesbeforedecision', get_string('requireminutesbeforedecision', 'uckkassembly'));
        $mform->setDefault('requireminutesbeforedecision', 1);

        $mform->addElement('textarea', 'publicationpolicy', get_string('publicationpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('publicationpolicy', PARAM_RAW);
        $mform->addHelpButton('publicationpolicy', 'publicationpolicy', 'uckkassembly');
    }

    /**
     * Add contestability settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_contestability_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'contestabilityheader', get_string('contestability', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'allowcontestations', get_string('allowcontestations', 'uckkassembly'));
        $mform->setDefault('allowcontestations', 1);

        $mform->addElement('text', 'contestwindowdays', get_string('contestwindowdays', 'uckkassembly'), [
            'size' => 6,
            'maxlength' => 4,
        ]);
        $mform->setType('contestwindowdays', PARAM_INT);
        $mform->setDefault('contestwindowdays', 7);
        $mform->disabledIf('contestwindowdays', 'allowcontestations', 'notchecked');

        $mform->addElement('textarea', 'contestationpolicy', get_string('contestationpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('contestationpolicy', PARAM_RAW);
        $mform->disabledIf('contestationpolicy', 'allowcontestations', 'notchecked');
        $mform->addHelpButton('contestationpolicy', 'contestationpolicy', 'uckkassembly');

        $mform->addElement('advcheckbox', 'integritypauseallowed', get_string('integritypauseallowed', 'uckkassembly'));
        $mform->setDefault('integritypauseallowed', 1);
        $mform->addHelpButton('integritypauseallowed', 'integritypauseallowed', 'uckkassembly');
    }

    /**
     * Add archive settings.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_archive_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'archiveheader', get_string('archiveoutput', 'uckkassembly'));

        $mform->addElement('select', 'archivepolicy', get_string('archivepolicy', 'uckkassembly'), [
            'none' => get_string('archivepolicy:none', 'uckkassembly'),
            'minutes_only' => get_string('archivepolicy:minutesonly', 'uckkassembly'),
            'decision_summary' => get_string('archivepolicy:decisionsummary', 'uckkassembly'),
            'full' => get_string('archivepolicy:full', 'uckkassembly'),
            'restricted_integrity' => get_string('archivepolicy:restrictedintegrity', 'uckkassembly'),
        ]);
        $mform->setDefault('archivepolicy', 'decision_summary');
        $mform->addRule('archivepolicy', null, 'required', null, 'client');

        $mform->addElement('textarea', 'archivesummarypolicy', get_string('archivesummarypolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('archivesummarypolicy', PARAM_RAW);
        $mform->addHelpButton('archivesummarypolicy', 'archivesummarypolicy', 'uckkassembly');

        $mform->addElement('advcheckbox', 'requirearchivevalidation', get_string('requirearchivevalidation', 'uckkassembly'));
        $mform->setDefault('requirearchivevalidation', 1);
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string> Field errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!empty($data['assemblycode']) && !preg_match('/^[A-Za-z0-9_-]+$/', (string)$data['assemblycode'])) {
            $errors['assemblycode'] = get_string('invalidassemblycode', 'uckkassembly');
        }

        if (!empty($data['motionopen']) && !empty($data['motionclose']) && $data['motionclose'] <= $data['motionopen']) {
            $errors['motionclose'] = get_string('motionclosemustbeafteropen', 'uckkassembly');
        }

        if (!empty($data['motionclose']) && !empty($data['amendmentclose']) && $data['amendmentclose'] < $data['motionclose']) {
            $errors['amendmentclose'] = get_string('amendmentclosemustbeaftermotionclose', 'uckkassembly');
        }

        if (!empty($data['votingopen']) && !empty($data['votingclose']) && $data['votingclose'] <= $data['votingopen']) {
            $errors['votingclose'] = get_string('votingclosemustbeafteropen', 'uckkassembly');
        }

        if (!empty($data['contestwindowdays']) && (int)$data['contestwindowdays'] < 0) {
            $errors['contestwindowdays'] = get_string('contestwindowdaysmustbepositive', 'uckkassembly');
        }

        foreach (['scope', 'agenda', 'rules'] as $requiredfield) {
            if (trim((string)($data[$requiredfield] ?? '')) === '') {
                $errors[$requiredfield] = get_string('required');
            }
        }

        return $errors;
    }

    /**
     * Preprocess default values when editing an existing instance.
     *
     * @param stdClass|array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        foreach ([
            'scope',
            'participantpolicy',
            'agenda',
            'rules',
            'argumentpolicy',
            'objectionpolicy',
            'minorityreportpolicy',
            'decisiontemplate',
            'publicationpolicy',
            'contestationpolicy',
            'archivesummarypolicy',
        ] as $field) {
            if (!isset($defaultvalues[$field])) {
                $defaultvalues[$field] = '';
            }
        }

        if (!isset($defaultvalues['status'])) {
            $defaultvalues['status'] = 'planned';
        }

        if (!isset($defaultvalues['assemblytype'])) {
            $defaultvalues['assemblytype'] = 'savoirs';
        }

        if (!isset($defaultvalues['visibility'])) {
            $defaultvalues['visibility'] = 'course';
        }

        if (!isset($defaultvalues['decisionmethod'])) {
            $defaultvalues['decisionmethod'] = 'rough_consensus';
        }

        if (!isset($defaultvalues['decisiontype'])) {
            $defaultvalues['decisiontype'] = 'recommendation';
        }

        if (!isset($defaultvalues['archivepolicy'])) {
            $defaultvalues['archivepolicy'] = 'decision_summary';
        }

        if (!isset($defaultvalues['contestwindowdays'])) {
            $defaultvalues['contestwindowdays'] = 7;
        }
    }

    /**
     * Add custom completion rules.
     *
     * @return array<int, string> Names of added completion rule elements.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;

        $motiongroup = [
            $mform->createElement(
                'checkbox',
                $this->get_suffixed_name('completionmotionenabled'),
                ' ',
                get_string('completionmotionenabled', 'uckkassembly')
            ),
            $mform->createElement(
                'text',
                $this->get_suffixed_name('completionmotioncount'),
                ' ',
                ['size' => 3]
            ),
        ];

        $mform->addGroup(
            $motiongroup,
            $this->get_suffixed_name('completionmotiongroup'),
            get_string('completionmotiongroup', 'uckkassembly'),
            [' '],
            false
        );
        $mform->setType($this->get_suffixed_name('completionmotioncount'), PARAM_INT);
        $mform->setDefault($this->get_suffixed_name('completionmotioncount'), 1);
        $mform->disabledIf(
            $this->get_suffixed_name('completionmotioncount'),
            $this->get_suffixed_name('completionmotionenabled'),
            'notchecked'
        );

        $participationgroup = [
            $mform->createElement(
                'checkbox',
                $this->get_suffixed_name('completionparticipationenabled'),
                ' ',
                get_string('completionparticipationenabled', 'uckkassembly')
            ),
            $mform->createElement(
                'text',
                $this->get_suffixed_name('completionparticipationcount'),
                ' ',
                ['size' => 3]
            ),
        ];

        $mform->addGroup(
            $participationgroup,
            $this->get_suffixed_name('completionparticipationgroup'),
            get_string('completionparticipationgroup', 'uckkassembly'),
            [' '],
            false
        );
        $mform->setType($this->get_suffixed_name('completionparticipationcount'), PARAM_INT);
        $mform->setDefault($this->get_suffixed_name('completionparticipationcount'), 1);
        $mform->disabledIf(
            $this->get_suffixed_name('completionparticipationcount'),
            $this->get_suffixed_name('completionparticipationenabled'),
            'notchecked'
        );

        $votegroup = [
            $mform->createElement(
                'checkbox',
                $this->get_suffixed_name('completionvoteenabled'),
                ' ',
                get_string('completionvoteenabled', 'uckkassembly')
            ),
            $mform->createElement(
                'text',
                $this->get_suffixed_name('completionvotecount'),
                ' ',
                ['size' => 3]
            ),
        ];

        $mform->addGroup(
            $votegroup,
            $this->get_suffixed_name('completionvotegroup'),
            get_string('completionvotegroup', 'uckkassembly'),
            [' '],
            false
        );
        $mform->setType($this->get_suffixed_name('completionvotecount'), PARAM_INT);
        $mform->setDefault($this->get_suffixed_name('completionvotecount'), 1);
        $mform->disabledIf(
            $this->get_suffixed_name('completionvotecount'),
            $this->get_suffixed_name('completionvoteenabled'),
            'notchecked'
        );

        return [
            $this->get_suffixed_name('completionmotiongroup'),
            $this->get_suffixed_name('completionparticipationgroup'),
            $this->get_suffixed_name('completionvotegroup'),
        ];
    }

    /**
     * Check whether at least one custom completion rule is enabled.
     *
     * @param array $data Submitted form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return (
            !empty($data[$this->get_suffixed_name('completionmotionenabled')])
            && !empty($data[$this->get_suffixed_name('completionmotioncount')])
        ) || (
            !empty($data[$this->get_suffixed_name('completionparticipationenabled')])
            && !empty($data[$this->get_suffixed_name('completionparticipationcount')])
        ) || (
            !empty($data[$this->get_suffixed_name('completionvoteenabled')])
            && !empty($data[$this->get_suffixed_name('completionvotecount')])
        );
    }

    /**
     * Get suffixed completion field name for Moodle 4.3+ completion forms.
     *
     * @param string $fieldname Base field name.
     * @return string
     */
    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }

    /**
     * Assembly type options.
     *
     * @return array<string, string>
     */
    private function get_assembly_type_options(): array {
        return [
            'savoirs' => get_string('assemblytype:savoirs', 'uckkassembly'),
            'defis' => get_string('assemblytype:defis', 'uckkassembly'),
            'joueurs' => get_string('assemblytype:joueurs', 'uckkassembly'),
            'batisseurs' => get_string('assemblytype:batisseurs', 'uckkassembly'),
            'inquisiteurs' => get_string('assemblytype:inquisiteurs', 'uckkassembly'),
            'grand_jeu' => get_string('assemblytype:grandjeu', 'uckkassembly'),
        ];
    }

    /**
     * Assembly status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            'planned' => get_string('status:planned', 'uckkassembly'),
            'open' => get_string('status:open', 'uckkassembly'),
            'motions_open' => get_string('status:motionsopen', 'uckkassembly'),
            'deliberation' => get_string('status:deliberation', 'uckkassembly'),
            'voting_or_reading' => get_string('status:votingorreading', 'uckkassembly'),
            'decision_draft' => get_string('status:decisiondraft', 'uckkassembly'),
            'decision_published' => get_string('status:decisionpublished', 'uckkassembly'),
            'contestability_window' => get_string('status:contestabilitywindow', 'uckkassembly'),
            'paused_for_integrity' => get_string('status:pausedforintegrity', 'uckkassembly'),
            'contested' => get_string('status:contested', 'uckkassembly'),
            'reopened' => get_string('status:reopened', 'uckkassembly'),
            'invalidated' => get_string('status:invalidated', 'uckkassembly'),
            'archived' => get_string('status:archived', 'uckkassembly'),
            'closed' => get_string('status:closed', 'uckkassembly'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'group' => get_string('visibility:group', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restrictedintegrity', 'uckkassembly'),
        ];
    }

    /**
     * Decision method options.
     *
     * @return array<string, string>
     */
    private function get_decision_method_options(): array {
        return [
            'rough_consensus' => get_string('decisionmethod:roughconsensus', 'uckkassembly'),
            'consensus' => get_string('decisionmethod:consensus', 'uckkassembly'),
            'consent' => get_string('decisionmethod:consent', 'uckkassembly'),
            'majority_vote' => get_string('decisionmethod:majorityvote', 'uckkassembly'),
            'qualified_majority' => get_string('decisionmethod:qualifiedmajority', 'uckkassembly'),
            'multiple_readings' => get_string('decisionmethod:multiplereadings', 'uckkassembly'),
            'facilitator_synthesis' => get_string('decisionmethod:facilitatorsynthesis', 'uckkassembly'),
        ];
    }

    /**
     * Decision type options.
     *
     * @return array<string, string>
     */
    private function get_decision_type_options(): array {
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
}