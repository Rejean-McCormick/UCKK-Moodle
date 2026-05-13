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
 * Event triggered when an integrity case is closed.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 UCKK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_closed extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['objecttable'] = 'tool_uckkintegrity_case';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Human-readable event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcaseclosed', 'tool_uckkintegrity');
    }

    /**
     * Event description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' closed integrity case with id '{$this->objectid}'.";
    }

    /**
     * Link to the integrity case.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/uckkintegrity/case.php', [
            'id' => $this->objectid,
        ]);
    }

    /**
     * Validate event data.
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('The objectid must be set for case_closed events.');
        }
    }

    /**
     * Restore mapping for backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'tool_uckkintegrity_case',
            'restore' => 'tool_uckkintegrity_case',
        ];
    }

    /**
     * Related user mapping.
     *
     * @return array|false
     */
    public static function get_other_mapping(): array|false {
        return false;
    }
}