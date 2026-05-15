<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);


namespace mod_uckkchallenge\event;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK Challenge evaluation updated.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_updated extends \core\event\base {
    protected function init(): void {
        $this->data['objecttable'] = 'uckkchallenge_eval';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function get_name(): string {
        $manager = get_string_manager();
        if ($manager->string_exists('eventevaluationupdated', 'uckkchallenge')) {
            return get_string('eventevaluationupdated', 'uckkchallenge');
        }

        return 'UCKK Challenge evaluation updated';
    }

    public function get_description(): string {
        return "The user with id '{${this->userid}}' updated an evaluation for the UCKK challenge submission with id '{${this->objectid}}' in the course module with id '{${this->contextinstanceid}}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $this->contextinstanceid]);
    }

    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE for mod_uckkchallenge events.');
        }
    }
}
