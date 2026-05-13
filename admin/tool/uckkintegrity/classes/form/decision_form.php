<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use tool_uckkintegrity\local\integrity_policy;

class decision_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'];

        $mform->addElement('hidden', 'id', $case->id);
        $mform->setType('id', PARAM_INT);

        $finalstatuses = array_intersect_key(integrity_policy::status_menu(), array_flip([
            'resolved', 'dismissed', 'escalated', 'archived', 'closed', 'correction_required', 'paused'
        ]));
        $mform->addElement('select', 'status', get_string('status', 'tool_uckkintegrity'), $finalstatuses);
        $mform->setDefault('status', 'resolved');

        $mform->addElement('textarea', 'decision', get_string('decisiontext', 'tool_uckkintegrity'), ['rows' => 8, 'cols' => 80]);
        $mform->setType('decision', PARAM_TEXT);
        $mform->addRule('decision', null, 'required', null, 'client');

        $mform->addElement('textarea', 'correction', get_string('correction', 'tool_uckkintegrity'), ['rows' => 5, 'cols' => 80]);
        $mform->setType('correction', PARAM_TEXT);

        $mform->addElement('textarea', 'appealpath', get_string('appealpath', 'tool_uckkintegrity'), ['rows' => 4, 'cols' => 80]);
        $mform->setType('appealpath', PARAM_TEXT);

        $mform->addElement('textarea', 'archivesummary', get_string('archivesummary', 'tool_uckkintegrity'), ['rows' => 4, 'cols' => 80]);
        $mform->setType('archivesummary', PARAM_TEXT);

        $mform->addElement('text', 'archiveitemid', get_string('archiveitemid', 'tool_uckkintegrity'), ['size' => 12]);
        $mform->setType('archiveitemid', PARAM_INT);

        $mform->addElement('advcheckbox', 'invalidateitem', get_string('uckkintegrity:invalidate', 'tool_uckkintegrity'));
        $mform->setType('invalidateitem', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('recorddecision', 'tool_uckkintegrity'));
    }
}