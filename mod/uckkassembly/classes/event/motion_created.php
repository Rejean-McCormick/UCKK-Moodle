<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when an Assembly motion is created.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when an Assembly motion is created.
 *
 * This event records the creation of a motion in a UCKK Assembly. It does not
 * imply that the motion is accepted, amended, voted on, converted into a
 * decision, archived, or validated by integrity review.
 */
final class motion_created extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'uckkassembly_motion';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventmotioncreated', 'uckkassembly');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $assemblyid = $this->other['assemblyid'] ?? 0;
        $cmid = $this->other['cmid'] ?? $this->contextinstanceid;
        $status = $this->other['status'] ?? '';

        return "The user with id '{$this->userid}' created the UCKK Assembly motion with id " .
            "'{$this->objectid}' in Assembly id '{$assemblyid}' using course module id '{$cmid}'" .
            ($status !== '' ? " with status '{$status}'." : '.');
    }

    /**
     * Return the URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $params = [
            'id' => $this->other['cmid'] ?? $this->contextinstanceid,
        ];

        if (!empty($this->objectid)) {
            $params['motionid'] = $this->objectid;
        }

        return new \moodle_url('/mod/uckkassembly/motion.php', $params);
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkassembly_motion',
            'restore' => 'uckkassembly_motion',
        ];
    }

    /**
     * Return mappings for values stored in the other event field.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            'assemblyid' => [
                'db' => 'uckkassembly',
                'restore' => 'uckkassembly',
            ],
            'courseid' => [
                'db' => 'course',
                'restore' => 'course',
            ],
            'cmid' => [
                'db' => 'course_modules',
                'restore' => 'course_module',
            ],
        ];
    }

    /**
     * Validate event data before triggering.
     *
     * Expected use:
     *
     * $event = \mod_uckkassembly\event\motion_created::create([
     *     'objectid' => $motion->id,
     *     'context' => $context,
     *     'other' => [
     *         'assemblyid' => $assembly->id,
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'status' => $motion->status,
     *         'visibility' => $motion->visibility,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkassembly', $assembly);
     * $event->add_record_snapshot('uckkassembly_motion', $motion);
     * $event->trigger();
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The motion_created event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The motion_created event requires objectid.');
        }

        if (empty($this->other['assemblyid'])) {
            throw new \coding_exception('The motion_created event requires assemblyid in other.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The motion_created event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The motion_created event requires cmid in other.');
        }
    }
}