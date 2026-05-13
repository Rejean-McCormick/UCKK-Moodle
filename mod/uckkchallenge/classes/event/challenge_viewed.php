<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when a UCKK challenge is viewed.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a UCKK challenge is viewed.
 *
 * This is a read-only module-context event. It records access to the challenge
 * activity without deciding completion, validation, integrity state, archive
 * state, badge awards, or competency certification.
 */
final class challenge_viewed extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'uckkchallenge';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventchallengeviewed', 'uckkchallenge');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the UCKK challenge with id " .
            "'{$this->objectid}' in course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Return the URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkchallenge/view.php', [
            'id' => $this->contextinstanceid,
        ]);
    }

    /**
     * Return the object id mapping used by backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkchallenge',
            'restore' => 'uckkchallenge',
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
        ];
    }

    /**
     * Validate event data before triggering.
     *
     * Expected event creation:
     *
     * $event = \mod_uckkchallenge\event\challenge_viewed::create([
     *     'objectid' => $challenge->id,
     *     'context' => $context,
     *     'other' => [
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'status' => $challenge->status,
     *         'visibility' => $challenge->visibility,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkchallenge', $challenge);
     * $event->trigger();
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The challenge_viewed event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The challenge_viewed event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The challenge_viewed event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The challenge_viewed event requires cmid in other.');
        }
    }
}