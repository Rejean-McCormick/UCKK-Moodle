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

/**
 * Appeal / contestation form.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class appeal_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $case = $this->_customdata['case'] ?? null;

        $mform->addElement('header', 'appealhdr', get_string('submitappeal', 'tool_uckkintegrity'));

        if ($case && !empty($case->appealpath)) {
            $mform->addElement('static', 'appealpath_static', get_string('appealpath', 'tool_uckkintegrity'), format_text((string) $case->appealpath, FORMAT_PLAIN));
        }

        $mform->addElement('textarea', 'body', get_string('body', 'tool_uckkintegrity'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('body', PARAM_TEXT);
        $mform->addRule('body', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submitappeal', 'tool_uckkintegrity'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (trim((string) $data['body']) === '') {
            $errors['body'] = get_string('required');
        }

        return $errors;
    }
}
