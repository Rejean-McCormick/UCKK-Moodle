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

use tool_uckkintegrity\local\integrity_policy;

/**
 * Decision form for case closure and correction.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class decision_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'] ?? null;

        $mform->addElement('header', 'decisionhdr', get_string('recorddecision', 'tool_uckkintegrity'));

        if ($case) {
            $mform->addElement('static', 'caseid_static', get_string('case', 'tool_uckkintegrity'), '#' . (int) $case->id);
        }

        $mform->addElement('select', 'status', get_string('status', 'tool_uckkintegrity'), integrity_policy::decision_status_menu(), [
            'data-tool-uckkintegrity-status' => '1',
        ]);
        $mform->setDefault('status', 'resolved');

        $mform->addElement('textarea', 'decision', get_string('decisiontext', 'tool_uckkintegrity'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('decision', PARAM_TEXT);
        $mform->addRule('decision', null, 'required', null, 'client');

        $mform->addElement('textarea', 'correction', get_string('correction', 'tool_uckkintegrity'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('correction', PARAM_TEXT);

        $mform->addElement('textarea', 'appealpath', get_string('appealpath', 'tool_uckkintegrity'), [
            'rows' => 3,
            'cols' => 80,
        ]);
        $mform->setType('appealpath', PARAM_TEXT);

        $mform->addElement('textarea', 'archivesummary', get_string('archivesummary', 'tool_uckkintegrity'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('archivesummary', PARAM_TEXT);

        $mform->addElement('text', 'archiveitemid', get_string('archiveitemid', 'tool_uckkintegrity'));
        $mform->setType('archiveitemid', PARAM_INT);
        $mform->setDefault('archiveitemid', 0);

        $mform->addElement('advcheckbox', 'invalidateitem', get_string('iteminvalidated', 'tool_uckkintegrity'));

        $this->add_action_buttons(true, get_string('recorddecision', 'tool_uckkintegrity'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!array_key_exists($data['status'], integrity_policy::decision_status_menu())) {
            $errors['status'] = get_string('unknownstatus', 'tool_uckkintegrity');
        }

        if (trim((string) $data['decision']) === '') {
            $errors['decision'] = get_string('required');
        }

        if ($data['status'] === 'correction_required' && trim((string) $data['correction']) === '') {
            $errors['correction'] = get_string('required');
        }

        return $errors;
    }
}
