<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when a UCKK challenge is archived.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a UCKK challenge is archived.
 *
 * This event records the auditable transition of a challenge into archive
 * state. It does not itself create archive records, validate evidence, close
 * integrity cases, award badges, or certify competencies. Those actions remain
 * in capability-checked services.
 */
final class challenge_archived extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'uckkchallenge';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventchallengearchived', 'uckkchallenge');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $archiveitemid = $this->other['archiveitemid'] ?? 0;
        $previousstatus = $this->other['previousstatus'] ?? '';
        $newstatus = $this->other['newstatus'] ?? 'archived';

        $description = "The user with id '{$this->userid}' archived the UCKK challenge with id " .
            "'{$this->objectid}' in course module id '{$this->contextinstanceid}'.";

        if ($archiveitemid) {
            $description .= " The linked archive item id is '{$archiveitemid}'.";
        }

        if ($previousstatus !== '') {
            $description .= " The challenge status changed from '{$previousstatus}' to '{$newstatus}'.";
        }

        return $description;
    }

    /**
     * Return the URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        if (!empty($this->other['archiveurl'])) {
            return new \moodle_url($this->other['archiveurl']);
        }

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
     * Return mappings for ids stored in the other event field.
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
     * Expected event creation:
     *
     * $event = \mod_uckkchallenge\event\challenge_archived::create([
     *     'objectid' => $challenge->id,
     *     'context' => $context,
     *     'other' => [
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'archiveitemid' => $archiveitem->id,
     *         'archiveurl' => $archiveurl->out(false),
     *         'previousstatus' => $previousstatus,
     *         'newstatus' => 'archived',
     *         'visibility' => $archiveitem->visibility,
     *         'provenancehash' => $archiveitem->provenancehash,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkchallenge', $challenge);
     * $event->trigger();
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The challenge_archived event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The challenge_archived event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The challenge_archived event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The challenge_archived event requires cmid in other.');
        }

        if (!empty($this->other['newstatus']) && $this->other['newstatus'] !== 'archived') {
            throw new \coding_exception('The challenge_archived event newstatus must be archived.');
        }
    }
}