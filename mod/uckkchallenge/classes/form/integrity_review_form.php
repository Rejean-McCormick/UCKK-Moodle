<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Integrity review form for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form used to review, contest, correct, invalidate, or close challenge integrity cases.
 *
 * This form collects structured reviewer input only. It must not directly mutate
 * challenge state, integrity cases, grades, archive records, badges, or competencies.
 * Controllers and service classes remain responsible for capability checks,
 * transactions, events, provenance, and audit records.
 */
final class integrity_review_form extends \moodleform {
    /** @var string Action: open a new integrity case. */
    public const ACTION_OPEN = 'open';

    /** @var string Action: contest a challenge or integrity decision. */
    public const ACTION_CONTEST = 'contest';

    /** @var string Action: record an integrity review. */
    public const ACTION_REVIEW = 'review';

    /** @var string Action: issue a correction request. */
    public const ACTION_CORRECTION = 'correction';

    /** @var string Action: invalidate a challenge submission or result. */
    public const ACTION_INVALIDATE = 'invalidate';

    /** @var string Action: close an integrity case. */
    public const ACTION_CLOSE = 'close';

    /** @var string Case type: proof quality. */
    public const CASE_PROOF_QUALITY = 'proof_quality';

    /** @var string Case type: fiction/fact confusion. */
    public const CASE_FICTION_FACT_CONFUSION = 'fiction_fact_confusion';

    /** @var string Case type: AI misuse. */
    public const CASE_AI_MISUSE = 'ai_misuse';

    /** @var string Case type: harassment or humiliation. */
    public const CASE_HARASSMENT_OR_HUMILIATION = 'harassment_or_humiliation';

    /** @var string Case type: dignity violation. */
    public const CASE_DIGNITY_VIOLATION = 'dignity_violation';

    /** @var string Case type: authority capture. */
    public const CASE_AUTHORITY_CAPTURE = 'authority_capture';

    /** @var string Case type: assessment dispute. */
    public const CASE_ASSESSMENT_DISPUTE = 'assessment_dispute';

    /** @var string Case type: challenge dispute. */
    public const CASE_CHALLENGE_DISPUTE = 'challenge_dispute';

    /** @var string Decision: no action. */
    public const DECISION_NO_ACTION = 'no_action';

    /** @var string Decision: correction required. */
    public const DECISION_CORRECTION_REQUIRED = 'correction_required';

    /** @var string Decision: validation allowed. */
    public const DECISION_VALIDATION_ALLOWED = 'validation_allowed';

    /** @var string Decision: pause validation. */
    public const DECISION_PAUSE_VALIDATION = 'pause_validation';

    /** @var string Decision: recommend invalidation. */
    public const DECISION_RECOMMEND_INVALIDATION = 'recommend_invalidation';

    /** @var string Decision: invalidate. */
    public const DECISION_INVALIDATE = 'invalidate';

    /** @var string Decision: close case. */
    public const DECISION_CLOSE = 'close';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->get_customdata();

        $this->add_hidden_context_fields($mform, $customdata);
        $this->add_case_summary_section($mform, $customdata);
        $this->add_review_section($mform, $customdata);
        $this->add_correction_section($mform, $customdata);
        $this->add_invalidation_section($mform, $customdata);
        $this->add_privacy_section($mform, $customdata);
        $this->add_confirmation_section($mform, $customdata);
        $this->add_action_buttons(true, $this->get_submit_label($customdata['action']));
    }

    /**
     * Validate submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Field errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $action = (string)($data['action'] ?? '');
        $summary = trim((string)($data['summary'] ?? ''));
        $rationale = trim((string)($data['rationale'] ?? ''));
        $correction = trim((string)($data['correctioninstructions'] ?? ''));
        $impact = trim((string)($data['impactsummary'] ?? ''));
        $close = trim((string)($data['closingsummary'] ?? ''));

        if (!array_key_exists($action, self::get_action_options())) {
            $errors['action'] = get_string('invalidintegrityaction', 'uckkchallenge');
        }

        if ($summary === '') {
            $errors['summary'] = get_string('required');
        }

        if (in_array($action, [self::ACTION_REVIEW, self::ACTION_INVALIDATE, self::ACTION_CLOSE], true) && $rationale === '') {
            $errors['rationale'] = get_string('required');
        }

        if ($action === self::ACTION_CORRECTION && $correction === '') {
            $errors['correctioninstructions'] = get_string('required');
        }

        if ($action === self::ACTION_INVALIDATE && $impact === '') {
            $errors['impactsummary'] = get_string('required');
        }

        if ($action === self::ACTION_CLOSE && $close === '') {
            $errors['closingsummary'] = get_string('required');
        }

        if (!empty($data['publishsummary']) && trim((string)($data['publicsummary'] ?? '')) === '') {
            $errors['publicsummary'] = get_string('publicsummaryrequired', 'uckkchallenge');
        }

        if (!empty($data['containsrestricteddata']) && empty($data['restrictedreason'])) {
            $errors['restrictedreason'] = get_string('restrictedreasonrequired', 'uckkchallenge');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkchallenge');
        }

        return $errors;
    }

    /**
     * Add hidden routing and subject fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_hidden_context_fields(\MoodleQuickForm $mform, array $customdata): void {
        foreach ([
            'id' => PARAM_INT,
            'cmid' => PARAM_INT,
            'challengeid' => PARAM_INT,
            'submissionid' => PARAM_INT,
            'caseid' => PARAM_INT,
            'returnurl' => PARAM_LOCALURL,
        ] as $field => $type) {
            $mform->addElement('hidden', $field, $customdata[$field]);
            $mform->setType($field, $type);
        }

        $mform->addElement('hidden', 'action', $customdata['action']);
        $mform->setType('action', PARAM_ALPHAEXT);
    }

    /**
     * Add integrity case identity and summary fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_case_summary_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'caseheader', get_string('integritycase', 'uckkchallenge'));

        if (!empty($customdata['challengename'])) {
            $mform->addElement('static', 'challengename_display', get_string('challenge', 'uckkchallenge'), s($customdata['challengename']));
        }

        if (!empty($customdata['caseid'])) {
            $mform->addElement('static', 'caseid_display', get_string('caseid', 'uckkchallenge'), (string)$customdata['caseid']);
        }

        $mform->addElement('select', 'casetype', get_string('casetype', 'uckkchallenge'), self::get_case_type_options());
        $mform->setDefault('casetype', $customdata['casetype']);
        $mform->setType('casetype', PARAM_ALPHAEXT);
        $mform->addRule('casetype', null, 'required', null, 'client');

        $mform->addElement('text', 'summary', get_string('summary', 'uckkchallenge'), [
            'maxlength' => 255,
            'size' => 80,
        ]);
        $mform->setType('summary', PARAM_TEXT);
        $mform->setDefault('summary', $customdata['summary']);
        $mform->addRule('summary', null, 'required', null, 'client');

        $mform->addElement('textarea', 'notes', get_string('notes', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('notes', PARAM_RAW);
        $mform->setDefault('notes', $customdata['notes']);
        $mform->addHelpButton('notes', 'integritynotes', 'uckkchallenge');

        $mform->addElement('textarea', 'evidencelinks', get_string('evidencelinks', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('evidencelinks', PARAM_RAW);
        $mform->setDefault('evidencelinks', $customdata['evidencelinks']);
        $mform->addHelpButton('evidencelinks', 'evidencelinks', 'uckkchallenge');
    }

    /**
     * Add review decision fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_review_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'reviewheader', get_string('integrityreview', 'uckkchallenge'));

        $mform->addElement('select', 'decision', get_string('integritydecision', 'uckkchallenge'), self::get_decision_options());
        $mform->setDefault('decision', $customdata['decision']);
        $mform->setType('decision', PARAM_ALPHAEXT);
        $mform->addRule('decision', null, 'required', null, 'client');

        $mform->addElement('textarea', 'rationale', get_string('decisionrationale', 'uckkchallenge'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('rationale', PARAM_RAW);
        $mform->setDefault('rationale', $customdata['rationale']);
        $mform->addHelpButton('rationale', 'decisionrationale', 'uckkchallenge');

        $mform->addElement('select', 'challengeintegritystate', get_string('challengeintegritystate', 'uckkchallenge'), self::get_integrity_state_options());
        $mform->setDefault('challengeintegritystate', $customdata['challengeintegritystate']);
        $mform->setType('challengeintegritystate', PARAM_ALPHAEXT);

        $mform->addElement('select', 'recommendedstatus', get_string('recommendedchallengestatus', 'uckkchallenge'), self::get_challenge_status_options());
        $mform->setDefault('recommendedstatus', $customdata['recommendedstatus']);
        $mform->setType('recommendedstatus', PARAM_ALPHAEXT);

        $mform->addElement('advcheckbox', 'pausevalidation', get_string('pausevalidation', 'uckkchallenge'));
        $mform->setDefault('pausevalidation', $customdata['pausevalidation']);

        $mform->addElement('advcheckbox', 'requireshumanfollowup', get_string('requireshumanfollowup', 'uckkchallenge'));
        $mform->setDefault('requireshumanfollowup', $customdata['requireshumanfollowup']);
    }

    /**
     * Add correction-specific fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_correction_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'correctionheader', get_string('correctionrequest', 'uckkchallenge'));

        $mform->addElement('textarea', 'correctioninstructions', get_string('correctioninstructions', 'uckkchallenge'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('correctioninstructions', PARAM_RAW);
        $mform->setDefault('correctioninstructions', $customdata['correctioninstructions']);

        $mform->addElement('date_time_selector', 'correctiondue', get_string('correctiondue', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('correctiondue', $customdata['correctiondue']);

        $mform->addElement('advcheckbox', 'notifyaffectedusers', get_string('notifyaffectedusers', 'uckkchallenge'));
        $mform->setDefault('notifyaffectedusers', $customdata['notifyaffectedusers']);

        $mform->hideIf('correctioninstructions', 'action', 'neq', self::ACTION_CORRECTION);
        $mform->hideIf('correctiondue', 'action', 'neq', self::ACTION_CORRECTION);
        $mform->hideIf('notifyaffectedusers', 'action', 'neq', self::ACTION_CORRECTION);
    }

    /**
     * Add invalidation and closing fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_invalidation_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'invalidationheader', get_string('invalidationandclosure', 'uckkchallenge'));

        $mform->addElement('select', 'impactscope', get_string('impactscope', 'uckkchallenge'), self::get_impact_scope_options());
        $mform->setDefault('impactscope', $customdata['impactscope']);
        $mform->setType('impactscope', PARAM_ALPHAEXT);

        $mform->addElement('textarea', 'impactsummary', get_string('impactsummary', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('impactsummary', PARAM_RAW);
        $mform->setDefault('impactsummary', $customdata['impactsummary']);

        $mform->addElement('textarea', 'closingsummary', get_string('closingsummary', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('closingsummary', PARAM_RAW);
        $mform->setDefault('closingsummary', $customdata['closingsummary']);

        $mform->hideIf('impactscope', 'action', 'neq', self::ACTION_INVALIDATE);
        $mform->hideIf('impactsummary', 'action', 'neq', self::ACTION_INVALIDATE);
        $mform->hideIf('closingsummary', 'action', 'neq', self::ACTION_CLOSE);
    }

    /**
     * Add privacy, public summary and AI disclosure fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_privacy_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'privacyheader', get_string('privacyandprovenance', 'uckkchallenge'));

        $mform->addElement('advcheckbox', 'containsrestricteddata', get_string('containsrestricteddata', 'uckkchallenge'));
        $mform->setDefault('containsrestricteddata', $customdata['containsrestricteddata']);

        $mform->addElement('textarea', 'restrictedreason', get_string('restrictedreason', 'uckkchallenge'), [
            'rows' => 3,
            'cols' => 80,
        ]);
        $mform->setType('restrictedreason', PARAM_RAW);
        $mform->setDefault('restrictedreason', $customdata['restrictedreason']);
        $mform->hideIf('restrictedreason', 'containsrestricteddata', 'notchecked');

        $mform->addElement('advcheckbox', 'publishsummary', get_string('publishpublicsummary', 'uckkchallenge'));
        $mform->setDefault('publishsummary', $customdata['publishsummary']);

        $mform->addElement('textarea', 'publicsummary', get_string('publicsummary', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('publicsummary', PARAM_RAW);
        $mform->setDefault('publicsummary', $customdata['publicsummary']);
        $mform->hideIf('publicsummary', 'publishsummary', 'notchecked');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassistedreview', 'uckkchallenge'));
        $mform->setDefault('aiassisted', $customdata['aiassisted']);
        $mform->addHelpButton('aiassisted', 'aiassistedreview', 'uckkchallenge');

        $mform->addElement('textarea', 'ailog', get_string('ailog', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->setDefault('ailog', $customdata['ailog']);
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');
    }

    /**
     * Add confirmation fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param array $customdata Normalised custom data.
     */
    private function add_confirmation_section(\MoodleQuickForm $mform, array $customdata): void {
        $mform->addElement('header', 'confirmationheader', get_string('confirmation', 'uckkchallenge'));

        $mform->addElement('advcheckbox', 'confirmhumanreview', get_string('confirmhumanreview', 'uckkchallenge'));
        $mform->setDefault('confirmhumanreview', $customdata['confirmhumanreview']);
        $mform->addRule('confirmhumanreview', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'confirmnodeletion', get_string('confirmnodeletion', 'uckkchallenge'));
        $mform->setDefault('confirmnodeletion', $customdata['confirmnodeletion']);
        $mform->addRule('confirmnodeletion', get_string('required'), 'required', null, 'client');
    }

    /**
     * Normalise form custom data.
     *
     * @return array
     */
    private function get_customdata(): array {
        $customdata = is_array($this->_customdata) ? $this->_customdata : [];

        $defaults = [
            'id' => 0,
            'cmid' => 0,
            'challengeid' => 0,
            'submissionid' => 0,
            'caseid' => 0,
            'returnurl' => '',
            'action' => self::ACTION_REVIEW,
            'challengename' => '',
            'casetype' => self::CASE_CHALLENGE_DISPUTE,
            'summary' => '',
            'notes' => '',
            'evidencelinks' => '',
            'decision' => self::DECISION_NO_ACTION,
            'rationale' => '',
            'challengeintegritystate' => 'human_reviewed',
            'recommendedstatus' => 'under_review',
            'pausevalidation' => 0,
            'requireshumanfollowup' => 1,
            'correctioninstructions' => '',
            'correctiondue' => 0,
            'notifyaffectedusers' => 1,
            'impactscope' => 'submission',
            'impactsummary' => '',
            'closingsummary' => '',
            'containsrestricteddata' => 0,
            'restrictedreason' => '',
            'publishsummary' => 0,
            'publicsummary' => '',
            'aiassisted' => 0,
            'ailog' => '',
            'confirmhumanreview' => 0,
            'confirmnodeletion' => 0,
        ];

        $customdata = array_merge($defaults, $customdata);

        if (!array_key_exists((string)$customdata['action'], self::get_action_options())) {
            $customdata['action'] = self::ACTION_REVIEW;
        }

        return $customdata;
    }

    /**
     * Get submit button label for the current action.
     *
     * @param string $action Action.
     * @return string
     */
    private function get_submit_label(string $action): string {
        return match ($action) {
            self::ACTION_OPEN => get_string('openintegritycase', 'uckkchallenge'),
            self::ACTION_CONTEST => get_string('contestchallenge', 'uckkchallenge'),
            self::ACTION_CORRECTION => get_string('issuecorrection', 'uckkchallenge'),
            self::ACTION_INVALIDATE => get_string('invalidatechallenge', 'uckkchallenge'),
            self::ACTION_CLOSE => get_string('closeintegritycase', 'uckkchallenge'),
            default => get_string('submitintegrityreview', 'uckkchallenge'),
        };
    }

    /**
     * Integrity action options.
     *
     * @return array<string, string>
     */
    public static function get_action_options(): array {
        return [
            self::ACTION_OPEN => get_string('integrityaction:open', 'uckkchallenge'),
            self::ACTION_CONTEST => get_string('integrityaction:contest', 'uckkchallenge'),
            self::ACTION_REVIEW => get_string('integrityaction:review', 'uckkchallenge'),
            self::ACTION_CORRECTION => get_string('integrityaction:correction', 'uckkchallenge'),
            self::ACTION_INVALIDATE => get_string('integrityaction:invalidate', 'uckkchallenge'),
            self::ACTION_CLOSE => get_string('integrityaction:close', 'uckkchallenge'),
        ];
    }

    /**
     * Case type options.
     *
     * @return array<string, string>
     */
    public static function get_case_type_options(): array {
        return [
            self::CASE_PROOF_QUALITY => get_string('casetype:proof_quality', 'uckkchallenge'),
            self::CASE_FICTION_FACT_CONFUSION => get_string('casetype:fiction_fact_confusion', 'uckkchallenge'),
            self::CASE_AI_MISUSE => get_string('casetype:ai_misuse', 'uckkchallenge'),
            self::CASE_HARASSMENT_OR_HUMILIATION => get_string('casetype:harassment_or_humiliation', 'uckkchallenge'),
            self::CASE_DIGNITY_VIOLATION => get_string('casetype:dignity_violation', 'uckkchallenge'),
            self::CASE_AUTHORITY_CAPTURE => get_string('casetype:authority_capture', 'uckkchallenge'),
            self::CASE_ASSESSMENT_DISPUTE => get_string('casetype:assessment_dispute', 'uckkchallenge'),
            self::CASE_CHALLENGE_DISPUTE => get_string('casetype:challenge_dispute', 'uckkchallenge'),
        ];
    }

    /**
     * Decision options.
     *
     * @return array<string, string>
     */
    public static function get_decision_options(): array {
        return [
            self::DECISION_NO_ACTION => get_string('decision:no_action', 'uckkchallenge'),
            self::DECISION_CORRECTION_REQUIRED => get_string('decision:correction_required', 'uckkchallenge'),
            self::DECISION_VALIDATION_ALLOWED => get_string('decision:validation_allowed', 'uckkchallenge'),
            self::DECISION_PAUSE_VALIDATION => get_string('decision:pause_validation', 'uckkchallenge'),
            self::DECISION_RECOMMEND_INVALIDATION => get_string('decision:recommend_invalidation', 'uckkchallenge'),
            self::DECISION_INVALIDATE => get_string('decision:invalidate', 'uckkchallenge'),
            self::DECISION_CLOSE => get_string('decision:close', 'uckkchallenge'),
        ];
    }

    /**
     * Integrity state options.
     *
     * @return array<string, string>
     */
    public static function get_integrity_state_options(): array {
        return [
            'unverified' => get_string('integritystate:unverified', 'uckkchallenge'),
            'human_reviewed' => get_string('integritystate:human_reviewed', 'uckkchallenge'),
            'verified' => get_string('integritystate:verified', 'uckkchallenge'),
            'contested' => get_string('integritystate:contested', 'uckkchallenge'),
            'invalidated' => get_string('integritystate:invalidated', 'uckkchallenge'),
            'archived' => get_string('integritystate:archived', 'uckkchallenge'),
        ];
    }

    /**
     * Recommended challenge status options.
     *
     * @return array<string, string>
     */
    public static function get_challenge_status_options(): array {
        return [
            'under_review' => get_string('status:under_review', 'uckkchallenge'),
            'integrity_review' => get_string('status:integrity_review', 'uckkchallenge'),
            'revision_required' => get_string('status:revision_required', 'uckkchallenge'),
            'validated' => get_string('status:validated', 'uckkchallenge'),
            'contested' => get_string('status:contested', 'uckkchallenge'),
            'invalidated' => get_string('status:invalidated', 'uckkchallenge'),
            'closed' => get_string('status:closed', 'uckkchallenge'),
        ];
    }

    /**
     * Impact scope options.
     *
     * @return array<string, string>
     */
    public static function get_impact_scope_options(): array {
        return [
            'submission' => get_string('impactscope:submission', 'uckkchallenge'),
            'challenge' => get_string('impactscope:challenge', 'uckkchallenge'),
            'badge' => get_string('impactscope:badge', 'uckkchallenge'),
            'competency' => get_string('impactscope:competency', 'uckkchallenge'),
            'archive' => get_string('impactscope:archive', 'uckkchallenge'),
            'multiple' => get_string('impactscope:multiple', 'uckkchallenge'),
        ];
    }
}