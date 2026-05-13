<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Motion form for UCKK Assemblies.
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
 * Server-side form for creating or editing an Assembly motion.
 *
 * This form only collects and validates submitted form fields. It must not
 * decide whether a motion is adopted, publish a decision, validate integrity,
 * archive the motion, or change vote results.
 */
final class motion_form extends moodleform {
    /** Motion type: information. */
    private const TYPE_INFORMATION = 'information';

    /** Motion type: recommendation. */
    private const TYPE_RECOMMENDATION = 'recommendation';

    /** Motion type: validation. */
    private const TYPE_VALIDATION = 'validation';

    /** Motion type: correction. */
    private const TYPE_CORRECTION = 'correction';

    /** Motion type: rejection. */
    private const TYPE_REJECTION = 'rejection';

    /** Motion type: archival. */
    private const TYPE_ARCHIVAL = 'archival';

    /** Motion type: integrity. */
    private const TYPE_INTEGRITY = 'integrity';

    /** Status: draft. */
    private const STATUS_DRAFT = 'draft';

    /** Status: pending. */
    private const STATUS_PENDING = 'pending';

    /** Status: active. */
    private const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    private const STATUS_HIDDEN = 'hidden';

    /** Provenance: human. */
    private const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI-assisted. */
    private const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    private const PROVENANCE_IMPORTED = 'imported';

    /**
     * Define the motion form.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $assembly = $this->get_custom_record('assembly');
        $context = $this->get_context();

        $mform->addElement('header', 'motionheader', get_string('proposemotion', 'uckkassembly'));

        if (!empty($assembly->name)) {
            $mform->addElement(
                'static',
                'assemblyname',
                get_string('assembly', 'uckkassembly'),
                format_string($assembly->name)
            );
        }

        $this->add_identity_section($mform);
        $this->add_motion_body_section($mform, $context);
        $this->add_governance_section($mform);
        $this->add_provenance_section($mform, $context);
        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savemotiondraft', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submitmotion', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Add identity fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_identity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('text', 'title', get_string('motiontitle', 'uckkassembly'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'motiontype', get_string('motiontype', 'uckkassembly'), $this->get_motion_type_options());
        $mform->setDefault('motiontype', self::TYPE_RECOMMENDATION);
        $mform->addRule('motiontype', null, 'required', null, 'client');

        $mform->addElement('select', 'status', get_string('motionstatus', 'uckkassembly'), $this->get_status_options());
        $mform->setDefault('status', self::STATUS_DRAFT);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');
    }

    /**
     * Add body and rationale fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_motion_body_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'motionbodyheader', get_string('motionbody', 'uckkassembly'));

        $mform->addElement(
            'editor',
            'body_editor',
            get_string('motionbody', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');
        $mform->addHelpButton('body_editor', 'motionbody', 'uckkassembly');

        $mform->addElement(
            'editor',
            'rationale_editor',
            get_string('motionrationale', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('rationale_editor', PARAM_RAW);
        $mform->addHelpButton('rationale_editor', 'motionrationale', 'uckkassembly');

        $mform->addElement(
            'editor',
            'expectedimpact_editor',
            get_string('expectedimpact', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('expectedimpact_editor', PARAM_RAW);
        $mform->addHelpButton('expectedimpact_editor', 'expectedimpact', 'uckkassembly');

        $mform->addElement('filemanager', 'attachments', get_string('motionattachments', 'uckkassembly'), null, [
            'context' => $context,
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        ]);
        $mform->addHelpButton('attachments', 'motionattachments', 'uckkassembly');
    }

    /**
     * Add governance and decision-linking fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_governance_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'governanceheader', get_string('governance', 'uckkassembly'));

        $mform->addElement('select', 'requesteddecisiontype', get_string('requesteddecisiontype', 'uckkassembly'), $this->get_decision_type_options());
        $mform->setDefault('requesteddecisiontype', self::TYPE_RECOMMENDATION);
        $mform->addHelpButton('requesteddecisiontype', 'requesteddecisiontype', 'uckkassembly');

        $mform->addElement('advcheckbox', 'allowamendments', get_string('allowamendments', 'uckkassembly'));
        $mform->setDefault('allowamendments', 1);

        $mform->addElement('advcheckbox', 'allowobjections', get_string('allowobjections', 'uckkassembly'));
        $mform->setDefault('allowobjections', 1);

        $mform->addElement('advcheckbox', 'allowvotes', get_string('allowvotes', 'uckkassembly'));
        $mform->setDefault('allowvotes', 1);

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timeopen', 0);

        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timeclose', 0);

        $mform->addElement('textarea', 'constraints', get_string('motionconstraints', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('constraints', PARAM_RAW);
        $mform->addHelpButton('constraints', 'motionconstraints', 'uckkassembly');
    }

    /**
     * Add provenance, AI disclosure, and integrity fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_provenance_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'provenanceheader', get_string('provenance', 'uckkassembly'));

        $mform->addElement('select', 'provenance', get_string('provenance', 'uckkassembly'), [
            self::PROVENANCE_HUMAN => get_string('provenance:human', 'uckkassembly'),
            self::PROVENANCE_AI_ASSISTED => get_string('provenance:ai_assisted', 'uckkassembly'),
            self::PROVENANCE_IMPORTED => get_string('provenance:imported', 'uckkassembly'),
        ]);
        $mform->setDefault('provenance', self::PROVENANCE_HUMAN);
        $mform->addRule('provenance', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'provenancestatement_editor',
            get_string('provenancestatement', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('provenancestatement_editor', PARAM_RAW);
        $mform->addHelpButton('provenancestatement_editor', 'provenancestatement', 'uckkassembly');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassisted', 'uckkassembly'));
        $mform->setDefault('aiassisted', 0);

        $mform->addElement('textarea', 'ailog', get_string('ailog', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');

        $mform->addElement('advcheckbox', 'requiresintegrityreview', get_string('requiresintegrityreview', 'uckkassembly'));
        $mform->setDefault('requiresintegrityreview', 0);

        $mform->addElement('textarea', 'integritynotes', get_string('integritynotes', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('integritynotes', PARAM_RAW);
        $mform->hideIf('integritynotes', 'requiresintegrityreview', 'notchecked');
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
            'motionid',
            'courseid',
            'cmid',
            'userid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('assemblyid', PARAM_INT);
        $mform->setType('motionid', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('cmid', PARAM_INT);
        $mform->setType('userid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    /**
     * Validate form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $title = trim((string)($data['title'] ?? ''));
        $body = trim((string)($data['body_editor']['text'] ?? ''));
        $timeopen = (int)($data['timeopen'] ?? 0);
        $timeclose = (int)($data['timeclose'] ?? 0);

        if ($title === '') {
            $errors['title'] = get_string('err_requiredtitle', 'uckkassembly');
        }

        if ($body === '') {
            $errors['body_editor'] = get_string('err_requiredbody', 'uckkassembly');
        }

        if ($timeopen > 0 && $timeclose > 0 && $timeclose <= $timeopen) {
            $errors['timeclose'] = get_string('err_timeclosebeforeopen', 'uckkassembly');
        }

        if (!array_key_exists((string)($data['motiontype'] ?? ''), $this->get_motion_type_options())) {
            $errors['motiontype'] = get_string('err_invalidtype', 'uckkassembly');
        }

        if (!array_key_exists((string)($data['status'] ?? ''), $this->get_status_options())) {
            $errors['status'] = get_string('err_invalidstatus', 'uckkassembly');
        }

        if (!array_key_exists((string)($data['visibility'] ?? ''), $this->get_visibility_options())) {
            $errors['visibility'] = get_string('err_invalidvisibility', 'uckkassembly');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkassembly');
        }

        if (!empty($data['requiresintegrityreview']) && trim((string)($data['integritynotes'] ?? '')) === '') {
            $errors['integritynotes'] = get_string('integritynotesrequired', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Preprocess default values for editor fields.
     *
     * @param array|stdClass $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        $defaults = is_array($defaultvalues) ? $defaultvalues : (array)$defaultvalues;

        foreach ([
            'body',
            'rationale',
            'expectedimpact',
            'provenancestatement',
        ] as $field) {
            if (!array_key_exists($field, $defaults)) {
                $defaults[$field] = '';
                $defaults[$field . 'format'] = FORMAT_HTML;
            }
        }

        if (is_array($defaultvalues)) {
            $defaultvalues = $defaults;
        } else {
            foreach ($defaults as $key => $value) {
                $defaultvalues->{$key} = $value;
            }
        }
    }

    /**
     * Return whether the form was submitted as a draft save.
     *
     * @return bool
     */
    public function is_draft_save(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->savedraft) && empty($data->submitbutton);
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

        throw new \coding_exception('motion_form requires a module context in customdata.');
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
     * Motion type options.
     *
     * @return array<string, string>
     */
    private function get_motion_type_options(): array {
        return [
            self::TYPE_INFORMATION => get_string('motiontype:information', 'uckkassembly'),
            self::TYPE_RECOMMENDATION => get_string('motiontype:recommendation', 'uckkassembly'),
            self::TYPE_VALIDATION => get_string('motiontype:validation', 'uckkassembly'),
            self::TYPE_CORRECTION => get_string('motiontype:correction', 'uckkassembly'),
            self::TYPE_REJECTION => get_string('motiontype:rejection', 'uckkassembly'),
            self::TYPE_ARCHIVAL => get_string('motiontype:archival', 'uckkassembly'),
            self::TYPE_INTEGRITY => get_string('motiontype:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Decision type options.
     *
     * @return array<string, string>
     */
    private function get_decision_type_options(): array {
        return [
            self::TYPE_INFORMATION => get_string('decisiontype:information', 'uckkassembly'),
            self::TYPE_RECOMMENDATION => get_string('decisiontype:recommendation', 'uckkassembly'),
            self::TYPE_VALIDATION => get_string('decisiontype:validation', 'uckkassembly'),
            self::TYPE_CORRECTION => get_string('decisiontype:correction', 'uckkassembly'),
            self::TYPE_REJECTION => get_string('decisiontype:rejection', 'uckkassembly'),
            self::TYPE_ARCHIVAL => get_string('decisiontype:archival', 'uckkassembly'),
            self::TYPE_INTEGRITY => get_string('decisiontype:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Motion status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            self::STATUS_DRAFT => get_string('status:draft', 'uckkassembly'),
            self::STATUS_PENDING => get_string('status:pending', 'uckkassembly'),
            self::STATUS_ACTIVE => get_string('status:active', 'uckkassembly'),
            self::STATUS_HIDDEN => get_string('status:hidden', 'uckkassembly'),
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
}