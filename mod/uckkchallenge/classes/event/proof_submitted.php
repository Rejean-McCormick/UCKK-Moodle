<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when proof is submitted for a UCKK challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when proof is submitted for a UCKK challenge.
 *
 * This event records the creation of a proof/evidence record. It does not
 * validate integrity, award badges, certify competencies, archive evidence,
 * or close the challenge workflow.
 */
final class proof_submitted extends \core\event\base {
    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'uckkchallenge_proof';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventproofsubmitted', 'uckkchallenge');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted proof with id '{$this->objectid}' " .
            "for UCKK challenge with id '{$this->other['challengeid']}' " .
            "and submission id '{$this->other['submissionid']}' " .
            "in course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Return related event URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkchallenge/view.php', [
            'id' => $this->contextinstanceid,
        ], 'proof-' . $this->objectid);
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkchallenge_proof',
            'restore' => 'uckkchallenge_proof',
        ];
    }

    /**
     * Return mappings for values stored in the other event field.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            'challengeid' => [
                'db' => 'uckkchallenge',
                'restore' => 'uckkchallenge',
            ],
            'submissionid' => [
                'db' => 'uckkchallenge_sub',
                'restore' => 'uckkchallenge_sub',
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
     * Expected event creation:
     *
     * $event = \mod_uckkchallenge\event\proof_submitted::create([
     *     'objectid' => $proof->id,
     *     'context' => $context,
     *     'relateduserid' => $proof->userid,
     *     'other' => [
     *         'challengeid' => $challenge->id,
     *         'submissionid' => $submission->id,
     *         'courseid' => $course->id,
     *         'cmid' => $cm->id,
     *         'prooftype' => $proof->prooftype,
     *         'visibility' => $proof->visibility,
     *         'integritystate' => $proof->integritystate,
     *         'provenance' => $proof->provenance,
     *     ],
     * ]);
     * $event->add_record_snapshot('course', $course);
     * $event->add_record_snapshot('course_modules', $cm);
     * $event->add_record_snapshot('uckkchallenge', $challenge);
     * $event->add_record_snapshot('uckkchallenge_sub', $submission);
     * $event->add_record_snapshot('uckkchallenge_proof', $proof);
     * $event->trigger();
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The proof_submitted event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The proof_submitted event requires objectid.');
        }

        if (empty($this->relateduserid)) {
            throw new \coding_exception('The proof_submitted event requires relateduserid.');
        }

        if (empty($this->other['challengeid'])) {
            throw new \coding_exception('The proof_submitted event requires challengeid in other.');
        }

        if (empty($this->other['submissionid'])) {
            throw new \coding_exception('The proof_submitted event requires submissionid in other.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The proof_submitted event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The proof_submitted event requires cmid in other.');
        }

        if (empty($this->other['prooftype'])) {
            throw new \coding_exception('The proof_submitted event requires prooftype in other.');
        }

        if (empty($this->other['visibility'])) {
            throw new \coding_exception('The proof_submitted event requires visibility in other.');
        }

        if (empty($this->other['integritystate'])) {
            throw new \coding_exception('The proof_submitted event requires integritystate in other.');
        }
    }
}