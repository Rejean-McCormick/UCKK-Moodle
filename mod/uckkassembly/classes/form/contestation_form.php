<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Contestation form for UCKK assembly decisions and motions.
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
 * Server-side form for assembly contestations.
 *
 * This form collects contestation data only. It does not resolve the
 * contestation, alter decisions, publish corrections, open or close integrity
 * cases, archive records, or redact evidence. Those actions must remain in
 * capability-checked service classes.
 */
final class contestation_form extends moodleform {
    /**
     * Subject type: motion.
     */
    private const SUBJECT_MOTION = 'motion';

    /**
     * Subject type: amendment.
     */
    private const SUBJECT_AMENDMENT = 'amendment';

    /**
     * Subject type: objection.
     */
    private const SUBJECT_OBJECTION = 'objection';

    /**
     * Subject type: vote.
     */
    private const SUBJECT_VOTE = 'vote';

    /**
     * Subject type: decision.
     */
    private const SUBJECT_DECISION = 'decision';

    /**
     * Subject type: minutes.
     */
    private const SUBJECT_MINUTES = 'minutes';

    /**
     * Contestation type: procedure.
     */
    private const CONTESTATION_PROCEDURE = 'procedure';

    /**
     * Contestation type: evidence.
     */
    private const CONTESTATION_EVIDENCE = 'evidence';

    /**
     * Contestation type: interpretation.
     */
    private const CONTESTATION_INTERPRETATION = 'interpretation';

    /**
     * Contestation type: integrity.
     */
    private const CONTESTATION_INTEGRITY = 'integrity';

    /**
     * Contestation type: privacy.
     */
    private const CONTESTATION_PRIVACY = 'privacy';

    /**
     * Contestation type: minority report.
     */
    private const CONTESTATION_MINORITY_REPORT = 'minority_report';

    /**
     * Contestation type: appeal.
     */
    private const CONTESTATION_APPEAL = 'appeal';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $assembly = $this->get_custom_record('assembly');
        $subject = $this->get_custom_record('subject');
        $context = $this->get_context();
        $draftitemid = $this->get_draft_itemid();

        $mform->addElement('header', 'contestationheader', get_string('contestdecision', 'uckkassembly'));

        if (!empty($assembly->name)) {
            $mform->addElement(
                'static',
                'assemblyname',
                get_string('assembly', 'uckkassembly'),
                format_string($assembly->name)
            );
        }

        if (!empty($subject->title)) {
            $mform->addElement(
                'static',
                'subjecttitle',
                get_string('contestationsubject', 'uckkassembly'),
                format_string($subject->title)
            );
        }

        $mform->addElement('text', 'title', get_string('contestationtitle', 'uckkassembly'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'subjecttype', get_string('subjecttype', 'uckkassembly'), $this->get_subject_type_options());
        $mform->setDefault('subjecttype', self::SUBJECT_DECISION);
        $mform->addRule('subjecttype', null, 'required', null, 'client');

        $mform->addElement('select', 'contestationtype', get_string('contestationtype', 'uckkassembly'), $this->get_contestation_type_options());
        $mform->setDefault('contestationtype', self::CONTESTATION_PROCEDURE);
        $mform->addRule('contestationtype', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'summary_editor',
            get_string('contestationsummary', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('summary_editor', PARAM_RAW);
        $mform->addRule('summary_editor', null, 'required', null, 'client');
        $mform->addHelpButton('summary_editor', 'contestationsummary', 'uckkassembly');

        $mform->addElement(
            'editor',
            'grounds_editor',
            get_string('contestationgrounds', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('grounds_editor', PARAM_RAW);
        $mform->addRule('grounds_editor', null, 'required', null, 'client');
        $mform->addHelpButton('grounds_editor', 'contestationgrounds', 'uckkassembly');

        $mform->addElement(
            'editor',
            'requestedoutcome_editor',
            get_string('requestedoutcome', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('requestedoutcome_editor', PARAM_RAW);
        $mform->addRule('requestedoutcome_editor', null, 'required', null, 'client');
        $mform->addHelpButton('requestedoutcome_editor', 'requestedoutcome', 'uckkassembly');

        $mform->addElement('textarea', 'evidencesummary', get_string('evidencesummary', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('evidencesummary', PARAM_RAW);
        $mform->addHelpButton('evidencesummary', 'evidencesummary', 'uckkassembly');

        $mform->addElement(
            'filemanager',
            'evidencefiles',
            get_string('contestationfiles', 'uckkassembly'),
            null,
            $this->get_filemanager_options($context)
        );
        $mform->setDefault('evidencefiles', $draftitemid);
        $mform->addHelpButton('evidencefiles', 'contestationfiles', 'uckkassembly');

        $mform->addElement('textarea', 'affectedparties', get_string('affectedparties', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('affectedparties', PARAM_RAW);
        $mform->addHelpButton('affectedparties', 'affectedparties', 'uckkassembly');

        $mform->addElement('textarea', 'privacyconcerns', get_string('privacyconcerns', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('privacyconcerns', PARAM_RAW);
        $mform->addHelpButton('privacyconcerns', 'privacyconcerns', 'uckkassembly');
        $mform->hideIf('privacyconcerns', 'contestationtype', 'neq', self::CONTESTATION_PRIVACY);

        $mform->addElement('textarea', 'minorityposition', get_string('minorityposition', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('minorityposition', PARAM_RAW);
        $mform->addHelpButton('minorityposition', 'minorityposition', 'uckkassembly');
        $mform->hideIf('minorityposition', 'contestationtype', 'neq', self::CONTESTATION_MINORITY_REPORT);

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('select', 'provenance', get_string('provenance', 'uckkassembly'), $this->get_provenance_options());
        $mform->setDefault('provenance', 'human');
        $mform->addRule('provenance', null, 'required', null, 'client');

        $mform->addElement('select', 'urgency', get_string('contestationurgency', 'uckkassembly'), $this->get_urgency_options());
        $mform->setDefault('urgency', 'normal');
        $mform->addRule('urgency', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'requestpause', get_string('requestdecisionpause', 'uckkassembly'));
        $mform->setDefault('requestpause', 0);
        $mform->addHelpButton('requestpause', 'requestdecisionpause', 'uckkassembly');

        $mform->addElement('advcheckbox', 'requestintegrityreview', get_string('requestintegrityreview', 'uckkassembly'));
        $mform->setDefault('requestintegrityreview', 0);
        $mform->addHelpButton('requestintegrityreview', 'requestintegrityreview', 'uckkassembly');

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

        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savecontestationdraft', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submitcontestation', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Validate submitted contestation data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $title = trim((string)($data['title'] ?? ''));
        $summary = trim((string)($data['summary_editor']['text'] ?? ''));
        $grounds = trim((string)($data['grounds_editor']['text'] ?? ''));
        $requestedoutcome = trim((string)($data['requestedoutcome_editor']['text'] ?? ''));
        $contestationtype = (string)($data['contestationtype'] ?? '');

        if ($title === '') {
            $errors['title'] = get_string('required');
        }

        if ($summary === '') {
            $errors['summary_editor'] = get_string('required');
        }

        if ($grounds === '') {
            $errors['grounds_editor'] = get_string('required');
        }

        if ($requestedoutcome === '') {
            $errors['requestedoutcome_editor'] = get_string('required');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkassembly');
        }

        if ($contestationtype === self::CONTESTATION_PRIVACY && trim((string)($data['privacyconcerns'] ?? '')) === '') {
            $errors['privacyconcerns'] = get_string('privacyconcernsrequired', 'uckkassembly');
        }

        if ($contestationtype === self::CONTESTATION_MINORITY_REPORT && trim((string)($data['minorityposition'] ?? '')) === '') {
            $errors['minorityposition'] = get_string('minoritypositionrequired', 'uckkassembly');
        }

        if (
            in_array($contestationtype, [self::CONTESTATION_INTEGRITY, self::CONTESTATION_PRIVACY], true)
            && empty($data['requestintegrityreview'])
        ) {
            $errors['requestintegrityreview'] = get_string('integrityreviewrequiredforcontestation', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Return whether this submission is a draft save.
     *
     * @return bool
     */
    public function is_draft_save(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->savedraft) && empty($data->submitbutton);
    }

    /**
     * Add hidden identifier fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform): void {
        foreach ([
            'id',
            'assemblyid',
            'subjectid',
            'contestationid',
            'draftitemid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('assemblyid', PARAM_INT);
        $mform->setType('subjectid', PARAM_INT);
        $mform->setType('contestationid', PARAM_INT);
        $mform->setType('draftitemid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->setDefault('draftitemid', $this->get_draft_itemid());
    }

    /**
     * Get module context from custom data.
     *
     * @return context_module
     */
    private function get_context(): context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        throw new \coding_exception('contestation_form requires a module context in customdata.');
    }

    /**
     * Get draft item id from custom data.
     *
     * @return int
     */
    private function get_draft_itemid(): int {
        return max(0, (int)($this->_customdata['draftitemid'] ?? 0));
    }

    /**
     * Get a custom data record.
     *
     * @param string $key Custom data key.
     * @return stdClass
     */
    private function get_custom_record(string $key): stdClass {
        if (empty($this->_customdata[$key])) {
            return new stdClass();
        }

        return (object)$this->_customdata[$key];
    }

    /**
     * Editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_editor_options(context_module $context): array {
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
     * Filemanager options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_filemanager_options(context_module $context): array {
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
     * Subject type options.
     *
     * @return array<string, string>
     */
    private function get_subject_type_options(): array {
        return [
            self::SUBJECT_MOTION => get_string('subjecttype:motion', 'uckkassembly'),
            self::SUBJECT_AMENDMENT => get_string('subjecttype:amendment', 'uckkassembly'),
            self::SUBJECT_OBJECTION => get_string('subjecttype:objection', 'uckkassembly'),
            self::SUBJECT_VOTE => get_string('subjecttype:vote', 'uckkassembly'),
            self::SUBJECT_DECISION => get_string('subjecttype:decision', 'uckkassembly'),
            self::SUBJECT_MINUTES => get_string('subjecttype:minutes', 'uckkassembly'),
        ];
    }

    /**
     * Contestation type options.
     *
     * @return array<string, string>
     */
    private function get_contestation_type_options(): array {
        return [
            self::CONTESTATION_PROCEDURE => get_string('contestationtype:procedure', 'uckkassembly'),
            self::CONTESTATION_EVIDENCE => get_string('contestationtype:evidence', 'uckkassembly'),
            self::CONTESTATION_INTERPRETATION => get_string('contestationtype:interpretation', 'uckkassembly'),
            self::CONTESTATION_INTEGRITY => get_string('contestationtype:integrity', 'uckkassembly'),
            self::CONTESTATION_PRIVACY => get_string('contestationtype:privacy', 'uckkassembly'),
            self::CONTESTATION_MINORITY_REPORT => get_string('contestationtype:minorityreport', 'uckkassembly'),
            self::CONTESTATION_APPEAL => get_string('contestationtype:appeal', 'uckkassembly'),
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
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ];
    }

    /**
     * Provenance options.
     *
     * @return array<string, string>
     */
    private function get_provenance_options(): array {
        return [
            'human' => get_string('provenance:human', 'uckkassembly'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'uckkassembly'),
            'imported' => get_string('provenance:imported', 'uckkassembly'),
            'system' => get_string('provenance:system', 'uckkassembly'),
            'archive' => get_string('provenance:archive', 'uckkassembly'),
            'challenge' => get_string('provenance:challenge', 'uckkassembly'),
            'assembly' => get_string('provenance:assembly', 'uckkassembly'),
            'integrity' => get_string('provenance:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Urgency options.
     *
     * @return array<string, string>
     */
    private function get_urgency_options(): array {
        return [
            'low' => get_string('urgency:low', 'uckkassembly'),
            'normal' => get_string('urgency:normal', 'uckkassembly'),
            'high' => get_string('urgency:high', 'uckkassembly'),
            'critical' => get_string('urgency:critical', 'uckkassembly'),
        ];
    }
}