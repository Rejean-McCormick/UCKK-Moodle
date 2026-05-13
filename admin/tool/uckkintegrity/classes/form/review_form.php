<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use tool_uckkintegrity\local\confidentiality;
use tool_uckkintegrity\local\integrity_policy;

class review_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'];

        $mform->addElement('hidden', 'id', $case->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'select',
            'status',
            get_string('status', 'tool_uckkintegrity'),
            integrity_policy::status_menu()
        );
        $mform->setDefault('status', $case->status);

        $mform->addElement(
            'text',
            'assignedto',
            get_string('assignedto', 'tool_uckkintegrity'),
            ['size' => 12]
        );
        $mform->setType('assignedto', PARAM_INT);
        $mform->setDefault('assignedto', $case->assignedto ?? 0);

        $mform->addElement(
            'select',
            'notetype',
            get_string('notetype', 'tool_uckkintegrity'),
            integrity_policy::note_type_menu()
        );
        $mform->setDefault('notetype', 'observation');

        $mform->addElement(
            'select',
            'visibility',
            get_string('visibility', 'tool_uckkintegrity'),
            confidentiality::menu()
        );
        $mform->setDefault('visibility', confidentiality::RESTRICTED);

        $mform->addElement(
            'textarea',
            'body',
            get_string('body', 'tool_uckkintegrity'),
            ['rows' => 8, 'cols' => 80]
        );
        $mform->setType('body', PARAM_TEXT);
        $mform->addRule('body', null, 'required', null, 'client');

        $this->add_action_buttons(
            true,
            get_string('reviewcase', 'tool_uckkintegrity')
        );
    }
}