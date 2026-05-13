<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when a vote is cast in an UCKK Assembly.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a participant casts a vote.
 *
 * This event records the procedural fact that a vote was cast. It does not
 * decide the motion, publish a decision, close contestability, modify minutes,
 * or archive the Assembly record.
 */
final class vote_cast extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'uckkassembly_vote';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventvotecast', 'uckkassembly');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $assemblyid = $this->other['assemblyid'] ?? 0;
        $motionid = $this->other['motionid'] ?? 0;

        return "The user with id '{$this->userid}' cast vote id '{$this->objectid}' " .
            "in UCKK Assembly id '{$assemblyid}' for motion id '{$motionid}' " .
            "in course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Return related URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $params = [
            'id' => $this->contextinstanceid,
        ];

        if (!empty($this->other['motionid'])) {
            $params['motionid'] = $this->other['motionid'];
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
            'db' => 'uckkassembly_vote',
            'restore' => 'uckkassembly_vote',
        ];
    }

    /**
     * Return other field mappings for backup and restore.
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
     * Validate event data.
     *
     * Expected trigger:
     *
     * $event = \mod_uckkassembly\event\vote_cast::create([
     *     'objectid' => $vote->id,
     *     'context' => $context,
     *     'other' => [
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'assemblyid' => $assembly->id,
     *         'motionid' => $motion->id,
     *         'votemethod' => $assembly->votemethod,
     *         'visibility' => $vote->visibility,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkassembly', $assembly);
     * $event->add_record_snapshot('uckkassembly_motion', $motion);
     * $event->add_record_snapshot('uckkassembly_vote', $vote);
     * $event->trigger();
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The vote_cast event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The vote_cast event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The vote_cast event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The vote_cast event requires cmid in other.');
        }

        if (empty($this->other['assemblyid'])) {
            throw new \coding_exception('The vote_cast event requires assemblyid in other.');
        }

        if (empty($this->other['motionid'])) {
            throw new \coding_exception('The vote_cast event requires motionid in other.');
        }
    }
}