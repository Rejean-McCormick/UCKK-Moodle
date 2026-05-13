<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace mod_uckkchallenge\form;

use context;
use mod_uckkchallenge\local\challenge;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Reusable UCKK challenge create/edit form.
 *
 * This form is for domain workflows such as internal challenge creation,
 * challenge blueprint editing, seeded challenge review, or staff-side challenge
 * configuration pages.
 *
 * It is not the legacy Moodle activity instance form. The activity add/update
 * form remains mod/uckkchallenge/mod_form.php with class mod_uckkchallenge_mod_form.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_form extends \moodleform {
    /**
     * Define the form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $this->add_identity_section($mform);
        $this->add_design_section($mform);
        $this->add_evidence_section($mform);
        $this->add_timeline_section($mform);
        $this->add_integrity_section($mform);
        $this->add_archive_section($mform);
        $this->add_hidden_fields($mform);

        $this->add_action_buttons(true, get_string('savechallenge', 'uckkchallenge'));
    }

    /**
     * Add identity fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_identity_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'identityhdr', get_string('challengeidentity', 'uckkchallenge'));

        $mform->addElement('text', 'name', get_string('challengename', 'uckkchallenge'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'challengecode', get_string('challengecode', 'uckkchallenge'), [
            'size' => 32,
            'maxlength' => 100,
        ]);
        $mform->setType('challengecode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('challengecode', 'challengecode', 'uckkchallenge');

        $mform->addElement('select', 'challengetype', get_string('challengetype', 'uckkchallenge'), $this->get_challenge_type_options());
        $mform->setDefault('challengetype', challenge::TYPE_INTERNAL_LEARNING);
        $mform->addRule('challengetype', null, 'required', null, 'client');

        $mform->addElement('select', 'status', get_string('challengestatus', 'uckkchallenge'), $this->get_status_options());
        $mform->setDefault('status', challenge::STATUS_DRAFT);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkchallenge'), $this->get_visibility_options());
        $mform->setDefault('visibility', challenge::VISIBILITY_COURSE);
        $mform->addRule('visibility', null, 'required', null, 'client');
    }

    /**
     * Add challenge design fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_design_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'designhdr', get_string('challengedesign', 'uckkchallenge'));

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
        $mform->addElement('header', 'evidencehdr', get_string('evidenceandevaluation', 'uckkchallenge'));

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
        $mform->addElement('header', 'timelinehdr', get_string('timeline', 'uckkchallenge'));

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
     * Add integrity and AI governance fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_integrity_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'integrityhdr', get_string('governance', 'uckkchallenge'));

        $mform->addElement('advcheckbox', 'integrityrequired', get_string('integrityrequired', 'uckkchallenge'));
        $mform->setDefault('integrityrequired', 1);
        $mform->addHelpButton('integrityrequired', 'integrityrequired', 'uckkchallenge');

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

        $mform->addElement(
            'static',
            'ainotauthority',
            get_string('ainotauthority', 'uckkchallenge'),
            get_string('ainotauthority_desc', 'uckkchallenge')
        );
    }

    /**
     * Add archive and recognition fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_archive_section(MoodleQuickForm $mform): void {
        $mform->addElement('header', 'archivehdr', get_string('archiveandrecognition', 'uckkchallenge'));

        $mform->addElement('select', 'archivepolicy', get_string('archivepolicy', 'uckkchallenge'), $this->get_archive_policy_options());
        $mform->setDefault('archivepolicy', challenge::ARCHIVE_POLICY_SUMMARY);
        $mform->addRule('archivepolicy', null, 'required', null, 'client');

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

        $mform->addElement('textarea', 'metadata', get_string('metadata', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('metadata', PARAM_RAW);
        $mform->addHelpButton('metadata', 'metadata', 'uckkchallenge');
    }

    /**
     * Add hidden identity/context fields.
     *
     * @param MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(MoodleQuickForm $mform): void {
        foreach (['id', 'course', 'courseid', 'cmid', 'contextid', 'versionno'] as $field) {
            $mform->addElement('hidden', $field);
            $mform->setType($field, PARAM_INT);
        }

        $mform->setDefault('id', 0);
        $mform->setDefault('course', 0);
        $mform->setDefault('courseid', 0);
        $mform->setDefault('cmid', 0);
        $mform->setDefault('contextid', 0);
        $mform->setDefault('versionno', 1);
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $record = (object)$data;
        $domainerrors = challenge::validate_record($record);

        foreach ($domainerrors as $field => $errorcode) {
            if (isset($errors[$field])) {
                continue;
            }

            $errors[$field] = $this->get_validation_error_string($errorcode);
        }

        if (!empty($data['timeopen']) && !empty($data['timeclose']) && $data['timeclose'] <= $data['timeopen']) {
            $errors['timeclose'] = get_string('timeclosemustbeafteropen', 'uckkchallenge');
        }

        if (!empty($data['timeclose']) && !empty($data['timereviewby']) && $data['timereviewby'] < $data['timeclose']) {
            $errors['timereviewby'] = get_string('timereviewmustbeafterclose', 'uckkchallenge');
        }

        if (!empty($data['challengecode']) && !preg_match('/^[A-Za-z0-9_-]+$/', (string)$data['challengecode'])) {
            $errors['challengecode'] = get_string('invalidchallengecode', 'uckkchallenge');
        }

        if (($data['visibility'] ?? '') === challenge::VISIBILITY_PUBLIC && trim((string)($data['publicsummary'] ?? '')) === '') {
            $errors['publicsummary'] = get_string('publicsummaryrequired', 'uckkchallenge');
        }

        foreach (['corridors', 'competencylinks', 'badgelinks', 'metadata'] as $jsonorlistfield) {
            $value = trim((string)($data[$jsonorlistfield] ?? ''));

            if ($value !== '' && !challenge::is_valid_json_or_plain_list($value)) {
                $errors[$jsonorlistfield] = get_string('invalidjsonorlist', 'uckkchallenge');
            }
        }

        return $errors;
    }

    /**
     * Return submitted data as a normalised challenge record.
     *
     * @return stdClass|null
     */
    public function get_normalised_data(): ?stdClass {
        $data = $this->get_data();

        if (!$data) {
            return null;
        }

        return challenge::normalise_record($data);
    }

    /**
     * Prepare a record before set_data().
     *
     * @param stdClass $record Raw challenge record.
     * @return stdClass
     */
    public static function prepare_record_for_form(stdClass $record): stdClass {
        $record = challenge::normalise_record($record);

        if (!isset($record->course) && isset($record->courseid)) {
            $record->course = $record->courseid;
        }

        if (!isset($record->courseid) && isset($record->course)) {
            $record->courseid = $record->course;
        }

        return $record;
    }

    /**
     * Return challenge type options.
     *
     * @return array<string, string>
     */
    private function get_challenge_type_options(): array {
        return [
            challenge::TYPE_INTERNAL_LEARNING => get_string('challengetype:internal_learning', 'uckkchallenge'),
            challenge::TYPE_PUBLIC_PEDAGOGICAL => get_string('challengetype:public_pedagogical', 'uckkchallenge'),
            challenge::TYPE_INSTITUTIONAL_AUDIT => get_string('challengetype:institutional_audit', 'uckkchallenge'),
            challenge::TYPE_SYSTEM_MAPPING => get_string('challengetype:system_mapping', 'uckkchallenge'),
            challenge::TYPE_PROTOTYPE => get_string('challengetype:prototype', 'uckkchallenge'),
            challenge::TYPE_MOBILISATION => get_string('challengetype:mobilisation', 'uckkchallenge'),
            challenge::TYPE_CAPSTONE => get_string('challengetype:capstone', 'uckkchallenge'),
            challenge::TYPE_KING_KLOWN_PUBLIC => get_string('challengetype:king_klown_public', 'uckkchallenge'),
        ];
    }

    /**
     * Return status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            challenge::STATUS_DRAFT => get_string('status:draft', 'uckkchallenge'),
            challenge::STATUS_PUBLISHED => get_string('status:published', 'uckkchallenge'),
            challenge::STATUS_OPEN => get_string('status:open', 'uckkchallenge'),
            challenge::STATUS_SUBMITTED => get_string('status:submitted', 'uckkchallenge'),
            challenge::STATUS_UNDER_REVIEW => get_string('status:under_review', 'uckkchallenge'),
            challenge::STATUS_INTEGRITY_REVIEW => get_string('status:integrity_review', 'uckkchallenge'),
            challenge::STATUS_REVISION_REQUIRED => get_string('status:revision_required', 'uckkchallenge'),
            challenge::STATUS_RESUBMITTED => get_string('status:resubmitted', 'uckkchallenge'),
            challenge::STATUS_VALIDATED => get_string('status:validated', 'uckkchallenge'),
            challenge::STATUS_ARCHIVED => get_string('status:archived', 'uckkchallenge'),
            challenge::STATUS_CONTESTED => get_string('status:contested', 'uckkchallenge'),
            challenge::STATUS_INVALIDATED => get_string('status:invalidated', 'uckkchallenge'),
            challenge::STATUS_CLOSED => get_string('status:closed', 'uckkchallenge'),
            challenge::STATUS_WITHDRAWN => get_string('status:withdrawn', 'uckkchallenge'),
            challenge::STATUS_EXPIRED => get_string('status:expired', 'uckkchallenge'),
        ];
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            challenge::VISIBILITY_PRIVATE => get_string('visibility:private', 'uckkchallenge'),
            challenge::VISIBILITY_USER => get_string('visibility:user', 'uckkchallenge'),
            challenge::VISIBILITY_GROUP => get_string('visibility:group', 'uckkchallenge'),
            challenge::VISIBILITY_COURSE => get_string('visibility:course', 'uckkchallenge'),
            challenge::VISIBILITY_COHORT => get_string('visibility:cohort', 'uckkchallenge'),
            challenge::VISIBILITY_PROGRAM => get_string('visibility:program', 'uckkchallenge'),
            challenge::VISIBILITY_INSTITUTION => get_string('visibility:institution', 'uckkchallenge'),
            challenge::VISIBILITY_PUBLIC => get_string('visibility:public', 'uckkchallenge'),
            challenge::VISIBILITY_RESTRICTED => get_string('visibility:restricted', 'uckkchallenge'),
            challenge::VISIBILITY_RESTRICTED_INTEGRITY => get_string('visibility:restricted_integrity', 'uckkchallenge'),
            challenge::VISIBILITY_HIDDEN => get_string('visibility:hidden', 'uckkchallenge'),
            challenge::VISIBILITY_ARCHIVED => get_string('visibility:archived', 'uckkchallenge'),
        ];
    }

    /**
     * Return archive policy options.
     *
     * @return array<string, string>
     */
    private function get_archive_policy_options(): array {
        return [
            challenge::ARCHIVE_POLICY_NONE => get_string('archivepolicy:none', 'uckkchallenge'),
            challenge::ARCHIVE_POLICY_SUMMARY => get_string('archivepolicy:summary', 'uckkchallenge'),
            challenge::ARCHIVE_POLICY_FULL => get_string('archivepolicy:full', 'uckkchallenge'),
        ];
    }

    /**
     * Return maximum submission options.
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
     * Convert a domain validation code to a language string.
     *
     * @param string $errorcode Error code from challenge::validate_record().
     * @return string
     */
    private function get_validation_error_string(string $errorcode): string {
        if ($errorcode === 'required') {
            return get_string('required');
        }

        if (get_string_manager()->string_exists($errorcode, 'uckkchallenge')) {
            return get_string($errorcode, 'uckkchallenge');
        }

        return get_string('invalidformdata', 'error');
    }
}