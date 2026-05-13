<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form for opening a UCKK integrity case.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uckkintegrity\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

use tool_uckkintegrity\local\confidentiality;
use tool_uckkintegrity\local\integrity_policy;
use tool_uckkintegrity\local\severity;

/**
 * Integrity case opening form.
 */
class case_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $context = $this->_customdata['context'] ?? \context_system::instance();

        $mform->addElement('hidden', 'contextid', $context->id);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement(
            'select',
            'casetype',
            get_string('casetype', 'tool_uckkintegrity'),
            integrity_policy::case_type_menu()
        );
        $mform->addRule('casetype', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'subjectcomponent',
            get_string('subjectcomponent', 'tool_uckkintegrity'),
            ['size' => 40]
        );
        $mform->setType('subjectcomponent', PARAM_COMPONENT);
        $mform->addRule('subjectcomponent', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'subjectid',
            get_string('subjectid', 'tool_uckkintegrity'),
            ['size' => 12]
        );
        $mform->setType('subjectid', PARAM_INT);
        $mform->setDefault('subjectid', 0);

        $mform->addElement(
            'select',
            'severity',
            get_string('severity', 'tool_uckkintegrity'),
            severity::menu()
        );
        $mform->setDefault(
            'severity',
            get_config('tool_uckkintegrity', 'defaultseverity') ?: severity::NORMAL
        );

        $mform->addElement(
            'select',
            'visibility',
            get_string('visibility', 'tool_uckkintegrity'),
            confidentiality::menu()
        );
        $mform->setDefault('visibility', confidentiality::RESTRICTED);

        $mform->addElement(
            'text',
            'assignedto',
            get_string('assignedto', 'tool_uckkintegrity'),
            ['size' => 12]
        );
        $mform->setType('assignedto', PARAM_INT);
        $mform->setDefault('assignedto', 0);

        $mform->addElement(
            'textarea',
            'summary',
            get_string('summary', 'tool_uckkintegrity'),
            ['rows' => 8, 'cols' => 80]
        );
        $mform->setType('summary', PARAM_TEXT);
        $mform->addRule('summary', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'parties',
            get_string('parties', 'tool_uckkintegrity'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('parties', PARAM_TEXT);
        $mform->addHelpButton('parties', 'parties', 'tool_uckkintegrity');

        $mform->addElement(
            'textarea',
            'evidencelinks',
            get_string('evidencelinks', 'tool_uckkintegrity'),
            ['rows' => 5, 'cols' => 80]
        );
        $mform->setType('evidencelinks', PARAM_TEXT);
        $mform->addHelpButton('evidencelinks', 'evidencelinks', 'tool_uckkintegrity');

        $mform->addElement(
            'textarea',
            'metadata',
            get_string('metadata', 'tool_uckkintegrity'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('metadata', PARAM_RAW);
        $mform->addHelpButton('metadata', 'metadata', 'tool_uckkintegrity');

        $draftitemid = file_get_submitted_draft_itemid('casefiles');

        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'tool_uckkintegrity',
            'case',
            0,
            [
                'subdirs' => 0,
                'maxfiles' => 20,
                'maxbytes' => 0,
            ]
        );

        $mform->addElement(
            'filemanager',
            'casefiles',
            get_string('casefiles', 'tool_uckkintegrity'),
            null,
            [
                'subdirs' => 0,
                'maxfiles' => 20,
                'maxbytes' => 0,
                'accepted_types' => '*',
            ]
        );
        $mform->setDefault('casefiles', $draftitemid);

        $this->add_action_buttons(true, get_string('opencase', 'tool_uckkintegrity'));
    }

    /**
     * Additional server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['casetype'])
                || !array_key_exists($data['casetype'], integrity_policy::case_type_menu())) {
            $errors['casetype'] = get_string('unknowntype', 'tool_uckkintegrity');
        }

        if (!empty($data['severity']) && !in_array($data['severity'], severity::values(), true)) {
            $errors['severity'] = get_string('invaliddata', 'error');
        }

        if (!empty($data['visibility']) && !in_array($data['visibility'], confidentiality::values(), true)) {
            $errors['visibility'] = get_string('invaliddata', 'error');
        }

        if (!empty($data['assignedto']) && (int) $data['assignedto'] < 0) {
            $errors['assignedto'] = get_string('invaliddata', 'error');
        }

        if (!empty($data['metadata'])) {
            json_decode($data['metadata']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadata'] = get_string('invalidjson', 'tool_uckkintegrity');
            }
        }

        return $errors;
    }

    /**
     * Return submitted data with parties/evidence merged into metadata JSON.
     *
     * @return \stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();

        if (!$data) {
            return null;
        }

        $metadata = [];

        if (!empty($data->metadata)) {
            $decoded = json_decode($data->metadata, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        if (!empty($data->parties)) {
            $metadata['parties'] = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $data->parties)
            )));
        }

        if (!empty($data->evidencelinks)) {
            $metadata['evidence_links'] = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $data->evidencelinks)
            )));
        }

        $data->metadata = $metadata
            ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        if (empty($data->assignedto)) {
            $data->assignedto = null;
        }

        return $data;
    }
}