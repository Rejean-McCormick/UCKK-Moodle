<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class appeal_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'];

        $mform->addElement('hidden', 'id', $case->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('textarea', 'body', get_string('body', 'tool_uckkintegrity'), ['rows' => 8, 'cols' => 80]);
        $mform->setType('body', PARAM_TEXT);
        $mform->addRule('body', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submitappeal', 'tool_uckkintegrity'));
    }
}