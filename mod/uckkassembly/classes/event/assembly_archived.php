<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when a UCKK assembly is archived.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a UCKK assembly is archived.
 *
 * This event records the archive transition for an Assembly activity. It does
 * not itself create archive items, validate decisions, resolve contestations,
 * certify competencies, or decide integrity outcomes.
 */
final class assembly_archived extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'uckkassembly';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventassemblyarchived', 'uckkassembly');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $archiveitemid = $this->other['archiveitemid'] ?? 0;
        $visibility = $this->other['visibility'] ?? 'unknown';
        $state = $this->other['state'] ?? 'archived';

        return "The user with id '{$this->userid}' archived the UCKK assembly with id " .
            "'{$this->objectid}' in course module id '{$this->contextinstanceid}'. " .
            "State: '{$state}'. Visibility: '{$visibility}'. Archive item id: '{$archiveitemid}'.";
    }

    /**
     * Return related URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkassembly/view.php', [
            'id' => $this->contextinstanceid,
        ]);
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkassembly',
            'restore' => 'uckkassembly',
        ];
    }

    /**
     * Return mappings for values stored in the other event field.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            'courseid' => [
                'db' => 'course',
                'restore' => 'course',
            ],
            'cmid' => [
                'db' => 'course_modules',
                'restore' => 'course_module',
            ],
            'archiveitemid' => [
                'db' => 'uckkarchive_item',
                'restore' => 'uckkarchive_item',
            ],
        ];
    }

    /**
     * Validate event data before triggering.
     *
     * Expected trigger pattern:
     *
     * $event = \mod_uckkassembly\event\assembly_archived::create([
     *     'objectid' => $assembly->id,
     *     'context' => $context,
     *     'other' => [
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'archiveitemid' => $archiveitemid,
     *         'visibility' => $assembly->visibility,
     *         'state' => 'archived',
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkassembly', $assembly);
     * $event->trigger();
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The assembly_archived event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The assembly_archived event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The assembly_archived event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The assembly_archived event requires cmid in other.');
        }

        if (!array_key_exists('visibility', $this->other)) {
            throw new \coding_exception('The assembly_archived event requires visibility in other.');
        }

        if (!array_key_exists('state', $this->other)) {
            throw new \coding_exception('The assembly_archived event requires state in other.');
        }
    }
}