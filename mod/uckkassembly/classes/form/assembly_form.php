<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Assembly domain form for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use context_module;
use moodleform;
use stdClass;

/**
 * Reusable form for editing Assembly governance/domain fields.
 *
 * This form collects structured Assembly configuration and publication data.
 * It does not publish decisions, resolve contestations, validate integrity,
 * count votes authoritatively, or create archive records. Those actions must
 * remain in capability-checked service classes.
 */
final class assembly_form extends moodleform {
    /**
     * Status: planned.
     */
    private const STATUS_PLANNED = 'planned';

    /**
     * Status: open.
     */
    private const STATUS_OPEN = 'open';

    /**
     * Status: motions open.
     */
    private const STATUS_MOTIONS_OPEN = 'motions_open';

    /**
     * Status: deliberation.
     */
    private const STATUS_DELIBERATION = 'deliberation';

    /**
     * Status: voting or reading.
     */
    private const STATUS_VOTING_OR_READING = 'voting_or_reading';

    /**
     * Status: decision draft.
     */
    private const STATUS_DECISION_DRAFT = 'decision_draft';

    /**
     * Status: decision published.
     */
    private const STATUS_DECISION_PUBLISHED = 'decision_published';

    /**
     * Status: contestability window.
     */
    private const STATUS_CONTESTABILITY_WINDOW = 'contestability_window';

    /**
     * Status: paused for integrity.
     */
    private const STATUS_PAUSED_FOR_INTEGRITY = 'paused_for_integrity';

    /**
     * Status: contested.
     */
    private const STATUS_CONTESTED = 'contested';

    /**
     * Status: reopened.
     */
    private const STATUS_REOPENED = 'reopened';

    /**
     * Status: invalidated.
     */
    private const STATUS_INVALIDATED = 'invalidated';

    /**
     * Status: archived.
     */
    private const STATUS_ARCHIVED = 'archived';

    /**
     * Status: closed.
     */
    private const STATUS_CLOSED = 'closed';

    /**
     * Define form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $this->add_identity_section($mform);
        $this->add_scope_section($mform);
        $this->add_deliberation_section($mform);
        $this->add_motion_section($mform);
        $this->add_voting_section($mform);
        $this->add_decision_section($mform);
        $this->add_contestability_section($mform);
        $this->add_integrity_section($mform);
        $this->add_archive_section($mform);
        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savedraft'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Add identity section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_identity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'identityheader', get_string('assemblyidentity', 'uckkassembly'));

        $mform->addElement('text', 'name', get_string('assemblyname', 'uckkassembly'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

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
        $mform->setDefault('status', self::STATUS_PLANNED);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('textarea', 'summary', get_string('assemblysummary', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('summary', PARAM_RAW);
        $mform->addHelpButton('summary', 'assemblysummary', 'uckkassembly');
    }

    /**
     * Add scope section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_scope_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'scopeheader', get_string('assemblyscope', 'uckkassembly'));

        $mform->addElement('textarea', 'scope', get_string('scope', 'uckkassembly'), [
            'rows' => 6,
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

        $mform->addElement('textarea', 'facilitatornotes', get_string('facilitatornotes', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('facilitatornotes', PARAM_RAW);
        $mform->addHelpButton('facilitatornotes', 'facilitatornotes', 'uckkassembly');

        $mform->addElement('advcheckbox', 'allowobservers', get_string('allowobservers', 'uckkassembly'));
        $mform->setDefault('allowobservers', 1);

        $mform->addElement('advcheckbox', 'allowpublicsummary', get_string('allowpublicsummary', 'uckkassembly'));
        $mform->setDefault('allowpublicsummary', 0);
    }

    /**
     * Add deliberation section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_deliberation_section(\MoodleQuickForm $mform): void {
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

        $mform->addElement('textarea', 'objectionpolicy', get_string('objectionpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('objectionpolicy', PARAM_RAW);

        $mform->addElement('textarea', 'minorityreportpolicy', get_string('minorityreportpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('minorityreportpolicy', PARAM_RAW);
    }

    /**
     * Add motion and amendment settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_motion_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'motionheader', get_string('motionsandamendments', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'allowmotions', get_string('allowmotions', 'uckkassembly'));
        $mform->setDefault('allowmotions', 1);

        $mform->addElement('date_time_selector', 'motionopen', get_string('motionopen', 'uckkassembly'), [
            'optional' => true,
        ]);

        $mform->addElement('date_time_selector', 'motionclose', get_string('motionclose', 'uckkassembly'), [
            'optional' => true,
        ]);

        $mform->addElement('advcheckbox', 'allowamendments', get_string('allowamendments', 'uckkassembly'));
        $mform->setDefault('allowamendments', 1);

        $mform->addElement('date_time_selector', 'amendmentclose', get_string('amendmentclose', 'uckkassembly'), [
            'optional' => true,
        ]);

        $mform->addElement('select', 'motionapproval', get_string('motionapproval', 'uckkassembly'), [
            'auto_accept' => get_string('motionapproval:autoaccept', 'uckkassembly'),
            'facilitator_review' => get_string('motionapproval:facilitatorreview', 'uckkassembly'),
            'mentor_review' => get_string('motionapproval:mentorreview', 'uckkassembly'),
            'inquisiteur_review' => get_string('motionapproval:inquisiteurreview', 'uckkassembly'),
        ]);
        $mform->setDefault('motionapproval', 'facilitator_review');
    }

    /**
     * Add voting and reading settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_voting_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'votingheader', get_string('votingandreadings', 'uckkassembly'));

        $mform->addElement('select', 'decisionmethod', get_string('decisionmethod', 'uckkassembly'), $this->get_decision_method_options());
        $mform->setDefault('decisionmethod', 'rough_consensus');
        $mform->addRule('decisionmethod', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'votingopen', get_string('votingopen', 'uckkassembly'), [
            'optional' => true,
        ]);

        $mform->addElement('date_time_selector', 'votingclose', get_string('votingclose', 'uckkassembly'), [
            'optional' => true,
        ]);

        $mform->addElement('advcheckbox', 'allowmultiplereadings', get_string('allowmultiplereadings', 'uckkassembly'));
        $mform->setDefault('allowmultiplereadings', 1);

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
     * Add decision and publication settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_decision_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'decisionheader', get_string('decisionpublication', 'uckkassembly'));

        $mform->addElement('select', 'decisiontype', get_string('decisiontype', 'uckkassembly'), $this->get_decision_type_options());
        $mform->setDefault('decisiontype', 'recommendation');

        $mform->addElement('textarea', 'decisiontemplate', get_string('decisiontemplate', 'uckkassembly'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('decisiontemplate', PARAM_RAW);

        $mform->addElement('textarea', 'publicationpolicy', get_string('publicationpolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('publicationpolicy', PARAM_RAW);

        $mform->addElement('advcheckbox', 'requirefacilitatorapproval', get_string('requirefacilitatorapproval', 'uckkassembly'));
        $mform->setDefault('requirefacilitatorapproval', 1);

        $mform->addElement('advcheckbox', 'requireminutesbeforedecision', get_string('requireminutesbeforedecision', 'uckkassembly'));
        $mform->setDefault('requireminutesbeforedecision', 1);

        $mform->addElement('textarea', 'minutespolicy', get_string('minutespolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('minutespolicy', PARAM_RAW);
        $mform->addHelpButton('minutespolicy', 'minutespolicy', 'uckkassembly');
    }

    /**
     * Add contestability settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_contestability_section(\MoodleQuickForm $mform): void {
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
    }

    /**
     * Add integrity settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_integrity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'integrityheader', get_string('integritysafeguards', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'integritypauseallowed', get_string('integritypauseallowed', 'uckkassembly'));
        $mform->setDefault('integritypauseallowed', 1);

        $mform->addElement('advcheckbox', 'requiresintegrityreview', get_string('requiresintegrityreview', 'uckkassembly'));
        $mform->setDefault('requiresintegrityreview', 0);

        $mform->addElement('textarea', 'integritypolicy', get_string('integritypolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('integritypolicy', PARAM_RAW);
        $mform->addHelpButton('integritypolicy', 'integritypolicy', 'uckkassembly');

        $mform->addElement('textarea', 'privacypolicy', get_string('privacypolicy', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('privacypolicy', PARAM_RAW);
        $mform->addHelpButton('privacypolicy', 'privacypolicy', 'uckkassembly');
    }

    /**
     * Add archive settings.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_archive_section(\MoodleQuickForm $mform): void {
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

        $mform->addElement('advcheckbox', 'requirearchivevalidation', get_string('requirearchivevalidation', 'uckkassembly'));
        $mform->setDefault('requirearchivevalidation', 1);

        $mform->addElement('textarea', 'metadatajson', get_string('metadatajson', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('metadatajson', PARAM_RAW);
        $mform->addHelpButton('metadatajson', 'metadatajson', 'uckkassembly');
    }

    /**
     * Add hidden fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform): void {
        foreach ([
            'id',
            'courseid',
            'cmid',
            'contextid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('cmid', PARAM_INT);
        $mform->setType('contextid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    /**
     * Validate submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (trim((string)($data['name'] ?? '')) === '') {
            $errors['name'] = get_string('required');
        }

        if (!empty($data['assemblycode']) && !preg_match('/^[A-Za-z0-9_-]+$/', (string)$data['assemblycode'])) {
            $errors['assemblycode'] = get_string('invalidassemblycode', 'uckkassembly');
        }

        foreach (['scope', 'agenda', 'rules'] as $requiredfield) {
            if (trim((string)($data[$requiredfield] ?? '')) === '') {
                $errors[$requiredfield] = get_string('required');
            }
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

        if (isset($data['contestwindowdays']) && (int)$data['contestwindowdays'] < 0) {
            $errors['contestwindowdays'] = get_string('contestwindowdaysmustbepositive', 'uckkassembly');
        }

        if (
            !empty($data['allowcontestations'])
            && (int)($data['contestwindowdays'] ?? 0) === 0
            && trim((string)($data['contestationpolicy'] ?? '')) === ''
        ) {
            $errors['contestationpolicy'] = get_string('contestationpolicyrequired', 'uckkassembly');
        }

        if (!empty($data['requiresintegrityreview']) && trim((string)($data['integritypolicy'] ?? '')) === '') {
            $errors['integritypolicy'] = get_string('integritypolicyrequired', 'uckkassembly');
        }

        $metadatajson = trim((string)($data['metadatajson'] ?? ''));
        if ($metadatajson !== '') {
            json_decode($metadatajson);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadatajson'] = get_string('invalidjson', 'uckkassembly');
            }
        }

        return $errors;
    }

    /**
     * Return whether the user clicked the draft button.
     *
     * @return bool
     */
    public function is_draft_save(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->savedraft) && empty($data->savechanges);
    }

    /**
     * Normalise submitted data for service-layer consumption.
     *
     * @param stdClass $data Submitted form data.
     * @return array<string, mixed>
     */
    public static function normalise_for_service(stdClass $data): array {
        $metadata = [];

        if (!empty($data->metadatajson)) {
            $decoded = json_decode((string)$data->metadatajson, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => (int)($data->id ?? 0),
            'courseid' => (int)($data->courseid ?? 0),
            'cmid' => (int)($data->cmid ?? 0),
            'contextid' => (int)($data->contextid ?? 0),
            'name' => trim((string)($data->name ?? '')),
            'assemblycode' => clean_param((string)($data->assemblycode ?? ''), PARAM_ALPHANUMEXT),
            'assemblytype' => clean_param((string)($data->assemblytype ?? 'savoirs'), PARAM_ALPHANUMEXT),
            'status' => clean_param((string)($data->status ?? self::STATUS_PLANNED), PARAM_ALPHANUMEXT),
            'visibility' => clean_param((string)($data->visibility ?? 'course'), PARAM_ALPHANUMEXT),
            'summary' => (string)($data->summary ?? ''),
            'scope' => (string)($data->scope ?? ''),
            'participantpolicy' => (string)($data->participantpolicy ?? ''),
            'facilitatornotes' => (string)($data->facilitatornotes ?? ''),
            'allowobservers' => !empty($data->allowobservers) ? 1 : 0,
            'allowpublicsummary' => !empty($data->allowpublicsummary) ? 1 : 0,
            'agenda' => (string)($data->agenda ?? ''),
            'rules' => (string)($data->rules ?? ''),
            'argumentpolicy' => (string)($data->argumentpolicy ?? ''),
            'objectionpolicy' => (string)($data->objectionpolicy ?? ''),
            'minorityreportpolicy' => (string)($data->minorityreportpolicy ?? ''),
            'allowmotions' => !empty($data->allowmotions) ? 1 : 0,
            'motionopen' => (int)($data->motionopen ?? 0),
            'motionclose' => (int)($data->motionclose ?? 0),
            'allowamendments' => !empty($data->allowamendments) ? 1 : 0,
            'amendmentclose' => (int)($data->amendmentclose ?? 0),
            'motionapproval' => clean_param((string)($data->motionapproval ?? 'facilitator_review'), PARAM_ALPHANUMEXT),
            'decisionmethod' => clean_param((string)($data->decisionmethod ?? 'rough_consensus'), PARAM_ALPHANUMEXT),
            'votingopen' => (int)($data->votingopen ?? 0),
            'votingclose' => (int)($data->votingclose ?? 0),
            'allowmultiplereadings' => !empty($data->allowmultiplereadings) ? 1 : 0,
            'showrawcount' => !empty($data->showrawcount) ? 1 : 0,
            'showrolereading' => !empty($data->showrolereading) ? 1 : 0,
            'showminorityreading' => !empty($data->showminorityreading) ? 1 : 0,
            'showintegrityreading' => !empty($data->showintegrityreading) ? 1 : 0,
            'decisiontype' => clean_param((string)($data->decisiontype ?? 'recommendation'), PARAM_ALPHANUMEXT),
            'decisiontemplate' => (string)($data->decisiontemplate ?? ''),
            'publicationpolicy' => (string)($data->publicationpolicy ?? ''),
            'requirefacilitatorapproval' => !empty($data->requirefacilitatorapproval) ? 1 : 0,
            'requireminutesbeforedecision' => !empty($data->requireminutesbeforedecision) ? 1 : 0,
            'minutespolicy' => (string)($data->minutespolicy ?? ''),
            'allowcontestations' => !empty($data->allowcontestations) ? 1 : 0,
            'contestwindowdays' => max(0, (int)($data->contestwindowdays ?? 0)),
            'contestationpolicy' => (string)($data->contestationpolicy ?? ''),
            'integritypauseallowed' => !empty($data->integritypauseallowed) ? 1 : 0,
            'requiresintegrityreview' => !empty($data->requiresintegrityreview) ? 1 : 0,
            'integritypolicy' => (string)($data->integritypolicy ?? ''),
            'privacypolicy' => (string)($data->privacypolicy ?? ''),
            'archivepolicy' => clean_param((string)($data->archivepolicy ?? 'decision_summary'), PARAM_ALPHANUMEXT),
            'archivesummarypolicy' => (string)($data->archivesummarypolicy ?? ''),
            'requirearchivevalidation' => !empty($data->requirearchivevalidation) ? 1 : 0,
            'metadata' => $metadata,
        ];
    }

    /**
     * Get context from custom data when provided.
     *
     * @return context_module|null
     */
    private function get_context(): ?context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        return null;
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
     * Status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            self::STATUS_PLANNED => get_string('status:planned', 'uckkassembly'),
            self::STATUS_OPEN => get_string('status:open', 'uckkassembly'),
            self::STATUS_MOTIONS_OPEN => get_string('status:motionsopen', 'uckkassembly'),
            self::STATUS_DELIBERATION => get_string('status:deliberation', 'uckkassembly'),
            self::STATUS_VOTING_OR_READING => get_string('status:votingorreading', 'uckkassembly'),
            self::STATUS_DECISION_DRAFT => get_string('status:decisiondraft', 'uckkassembly'),
            self::STATUS_DECISION_PUBLISHED => get_string('status:decisionpublished', 'uckkassembly'),
            self::STATUS_CONTESTABILITY_WINDOW => get_string('status:contestabilitywindow', 'uckkassembly'),
            self::STATUS_PAUSED_FOR_INTEGRITY => get_string('status:pausedforintegrity', 'uckkassembly'),
            self::STATUS_CONTESTED => get_string('status:contested', 'uckkassembly'),
            self::STATUS_REOPENED => get_string('status:reopened', 'uckkassembly'),
            self::STATUS_INVALIDATED => get_string('status:invalidated', 'uckkassembly'),
            self::STATUS_ARCHIVED => get_string('status:archived', 'uckkassembly'),
            self::STATUS_CLOSED => get_string('status:closed', 'uckkassembly'),
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
