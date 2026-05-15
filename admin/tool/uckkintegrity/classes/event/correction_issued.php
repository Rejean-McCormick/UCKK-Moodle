<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event class for correction_issued.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class correction_issued extends \core\event\base {
    protected function init(): void {
        $this->data['objecttable'] = 'tool_uckkintegrity_case';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name(): string {
        return get_string('eventcorrectionissued', 'tool_uckkintegrity');
    }

    public function get_description(): string {
        $userid = (int) ($this->userid ?? 0);
        $caseid = (int) ($this->objectid ?? 0);
        $status = (string) ($this->other['status'] ?? '');

        return "User {$userid} performed integrity action 'issue correction' on case {$caseid} with status '{status}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => (int) $this->objectid]);
    }

    protected function get_legacy_logdata(): array {
        return [
            SITEID,
            'tool_uckkintegrity',
            'issue correction',
            $this->get_url()->out(false),
            (int) $this->objectid,
            $this->contextinstanceid,
        ];
    }

    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('correction_issued must contain an objectid.');
        }
    }
}
