<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use tool_uckkintegrity\local\confidentiality;
use tool_uckkintegrity\local\integrity_policy;

/**
 * Review form for Inquisiteur actions.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class review_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'] ?? null;

        $mform->addElement('header', 'reviewhdr', get_string('reviewcase', 'tool_uckkintegrity'));

        if ($case) {
            $mform->addElement('static', 'caseid_static', get_string('case', 'tool_uckkintegrity'), '#' . (int) $case->id);
            $mform->addElement('static', 'summary_static', get_string('summary', 'tool_uckkintegrity'), format_string((string) $case->summary));
        }

        $mform->addElement('select', 'status', get_string('status', 'tool_uckkintegrity'), integrity_policy::status_menu(), [
            'data-tool-uckkintegrity-status' => '1',
        ]);
        $mform->setDefault('status', $case->status ?? 'under_review');

        $mform->addElement('text', 'assignedto', get_string('assignedto', 'tool_uckkintegrity'));
        $mform->setType('assignedto', PARAM_INT);
        $mform->setDefault('assignedto', $case->assignedto ?? 0);

        $mform->addElement('select', 'notetype', get_string('notetype', 'tool_uckkintegrity'), integrity_policy::note_type_menu(), [
            'data-tool-uckkintegrity-note-type' => '1',
        ]);
        $mform->setDefault('notetype', 'observation');

        $mform->addElement('textarea', 'body', get_string('body', 'tool_uckkintegrity'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('body', PARAM_TEXT);
        $mform->addRule('body', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'tool_uckkintegrity'), confidentiality::options());
        $mform->setDefault('visibility', confidentiality::RESTRICTED);

        $this->add_action_buttons(true, get_string('reviewcase', 'tool_uckkintegrity'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!array_key_exists($data['status'], integrity_policy::status_menu())) {
            $errors['status'] = get_string('unknownstatus', 'tool_uckkintegrity');
        }

        if (!array_key_exists($data['notetype'], integrity_policy::note_type_menu())) {
            $errors['notetype'] = get_string('required');
        }

        if (trim((string) $data['body']) === '') {
            $errors['body'] = get_string('required');
        }

        if (!array_key_exists($data['visibility'], confidentiality::options())) {
            $errors['visibility'] = get_string('invalidvisibility', 'tool_uckkintegrity');
        }

        return $errors;
    }
}
