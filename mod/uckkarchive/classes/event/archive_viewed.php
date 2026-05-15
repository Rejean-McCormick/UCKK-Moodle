<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive viewed
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_uckkarchive\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Archive viewed
 */
class archive_viewed extends \core\event\base {
    protected function init(): void {
        $this->data['objecttable'] = 'uckkarchive';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = \core\event\base::LEVEL_PARTICIPATING;
    }

    public static function get_name(): string {
        $key = 'event:archiveviewed';
        if (\get_string_manager()->string_exists($key, 'uckkarchive')) {
            return \get_string($key, 'uckkarchive');
        }
        return 'Archive viewed';
    }

    public function get_description(): string {
        return "The user viewed the archive activity.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkarchive/view.php', ['id' => (int)($this->other['cmid'] ?? 0)]);
    }

    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->data['context'])) {
            throw new \coding_exception('Context must be set for ' . static::class);
        }
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'uckkarchive', 'restore' => 'uckkarchive'];
    }

    public static function get_other_mapping(): array {
        return [
            'archiveid' => ['db' => 'uckkarchive', 'restore' => 'uckkarchive'],
            'cmid' => ['db' => 'course_modules', 'restore' => 'course_module'],
            'courseid' => ['db' => 'course', 'restore' => 'course'],
        ];
    }
}
