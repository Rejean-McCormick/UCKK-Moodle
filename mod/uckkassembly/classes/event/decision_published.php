<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when an Assembly decision is published.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when an Assembly decision is published.
 *
 * This event records a governance publication action. It does not itself
 * validate integrity, archive the decision, change grades, certify badges,
 * or resolve contestations.
 */
final class decision_published extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'uckkassembly_decision';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventdecisionpublished', 'uckkassembly');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $assemblyid = $this->other['assemblyid'] ?? 0;
        $motionid = $this->other['motionid'] ?? 0;
        $decisiontype = $this->other['decisiontype'] ?? '';
        $status = $this->other['status'] ?? '';

        return "The user with id '{$this->userid}' published the UCKK Assembly decision with id " .
            "'{$this->objectid}' for assembly id '{$assemblyid}', motion id '{$motionid}', " .
            "decision type '{$decisiontype}', and status '{$status}' in course module id " .
            "'{$this->contextinstanceid}'.";
    }

    /**
     * Return URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $params = [
            'id' => $this->contextinstanceid,
        ];

        if (!empty($this->objectid)) {
            $params['decisionid'] = $this->objectid;
        }

        return new \moodle_url('/mod/uckkassembly/view.php', $params);
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkassembly_decision',
            'restore' => 'uckkassembly_decision',
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
            'assemblyid' => [
                'db' => 'uckkassembly',
                'restore' => 'uckkassembly',
            ],
            'motionid' => [
                'db' => 'uckkassembly_motion',
                'restore' => 'uckkassembly_motion',
            ],
        ];
    }

    /**
     * Validate event data before triggering.
     *
     * Expected usage:
     *
     * $event = \mod_uckkassembly\event\decision_published::create([
     *     'objectid' => $decision->id,
     *     'context' => $context,
     *     'other' => [
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'assemblyid' => $assembly->id,
     *         'motionid' => $decision->motionid,
     *         'decisiontype' => $decision->decisiontype,
     *         'status' => $decision->status,
     *         'visibility' => $decision->visibility,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkassembly', $assembly);
     * $event->add_record_snapshot('uckkassembly_decision', $decision);
     * $event->trigger();
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The decision_published event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The decision_published event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The decision_published event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The decision_published event requires cmid in other.');
        }

        if (empty($this->other['assemblyid'])) {
            throw new \coding_exception('The decision_published event requires assemblyid in other.');
        }

        if (empty($this->other['decisiontype'])) {
            throw new \coding_exception('The decision_published event requires decisiontype in other.');
        }

        if (empty($this->other['status'])) {
            throw new \coding_exception('The decision_published event requires status in other.');
        }
    }
}