<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace mod_uckkchallenge\event;

use coding_exception;
use context_module;
use core\event\base;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a UCKK challenge submission is evaluated.
 *
 * The event is an audit record only. Evaluation decisions, competency updates,
 * badge triggers, archive export decisions, and integrity transitions must be
 * handled by service classes before or after the event is triggered.
 *
 * Expected event data:
 *
 * [
 *     'context' => context_module::instance($cmid),
 *     'objectid' => $evaluationid,
 *     'relateduserid' => $learnerid,
 *     'other' => [
 *         'challengeid' => $challengeid,
 *         'submissionid' => $submissionid,
 *         'evaluatorid' => $evaluatorid,
 *         'status' => 'validated',
 *         'previousstatus' => 'under_review',
 *         'competencyrating' => 'met',
 *         'badgetriggered' => false,
 *         'integritystate' => 'human_reviewed',
 *     ],
 * ]
 *
 * @package    mod_uckkchallenge
 * @category   event
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_evaluated extends base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'uckkchallenge_eval';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventchallengeevaluated', 'uckkchallenge');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        $challengeid = (int)($this->other['challengeid'] ?? 0);
        $submissionid = (int)($this->other['submissionid'] ?? 0);
        $evaluatorid = (int)($this->other['evaluatorid'] ?? $this->userid);
        $status = (string)($this->other['status'] ?? '');

        return "The user with id '{$evaluatorid}' evaluated the UCKK challenge submission " .
            "with id '{$submissionid}' for the user with id '{$this->relateduserid}' " .
            "in the challenge with id '{$challengeid}'. The evaluation record id is " .
            "'{$this->objectid}' and the resulting status is '{$status}'.";
    }

    /**
     * Return URL related to this event.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $params = [
            'id' => $this->contextinstanceid,
        ];

        if (!empty($this->other['submissionid'])) {
            $params['submissionid'] = (int)$this->other['submissionid'];
        }

        if (!empty($this->objectid)) {
            $params['evaluationid'] = (int)$this->objectid;
        }

        return new moodle_url('/mod/uckkchallenge/evaluate.php', $params);
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkchallenge_eval',
            'restore' => 'uckkchallenge_eval',
        ];
    }

    /**
     * Return other-field mappings for backup and restore.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        $mapping = [
            'challengeid' => [
                'db' => 'uckkchallenge',
                'restore' => 'uckkchallenge',
            ],
            'submissionid' => [
                'db' => 'uckkchallenge_sub',
                'restore' => 'uckkchallenge_sub',
            ],
            'evaluatorid' => [
                'db' => 'user',
                'restore' => 'user',
            ],
        ];

        if (isset($this->other['integritycaseid'])) {
            $mapping['integritycaseid'] = [
                'db' => 'tool_uckki_case',
                'restore' => 'tool_uckki_case',
            ];
        }

        return $mapping;
    }

    /**
     * Validate event data before triggering.
     *
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new coding_exception('The challenge_evaluated event must be used in a module context.');
        }

        if (empty($this->objectid)) {
            throw new coding_exception('The challenge_evaluated event requires an evaluation object id.');
        }

        if (empty($this->relateduserid)) {
            throw new coding_exception('The challenge_evaluated event requires relateduserid for the evaluated learner.');
        }

        if (empty($this->other['challengeid'])) {
            throw new coding_exception('The challenge_evaluated event requires other[challengeid].');
        }

        if (empty($this->other['submissionid'])) {
            throw new coding_exception('The challenge_evaluated event requires other[submissionid].');
        }

        if (empty($this->other['evaluatorid'])) {
            throw new coding_exception('The challenge_evaluated event requires other[evaluatorid].');
        }

        if (empty($this->other['status'])) {
            throw new coding_exception('The challenge_evaluated event requires other[status].');
        }
    }

    /**
     * Create the event from canonical challenge records.
     *
     * This helper keeps controllers and services consistent when triggering the
     * event after an evaluation write has succeeded.
     *
     * Required record fields:
     * - $evaluation->id
     * - $evaluation->status
     * - $challenge->id
     * - $submission->id
     * - $submission->userid
     * - $cm->id
     *
     * Optional evaluation fields:
     * - previousstatus
     * - competencyrating
     * - badgetriggered
     * - integritystate
     * - integritycaseid
     *
     * @param stdClass $evaluation Evaluation record from uckkchallenge_eval.
     * @param stdClass $challenge Challenge record from uckkchallenge.
     * @param stdClass $submission Submission record from uckkchallenge_sub.
     * @param stdClass $cm Course module record.
     * @param stdClass $evaluator Moodle user who evaluated the submission.
     * @return self
     */
    public static function create_from_evaluation(
        stdClass $evaluation,
        stdClass $challenge,
        stdClass $submission,
        stdClass $cm,
        stdClass $evaluator
    ): self {
        $other = [
            'challengeid' => (int)$challenge->id,
            'submissionid' => (int)$submission->id,
            'evaluatorid' => (int)$evaluator->id,
            'status' => (string)$evaluation->status,
            'previousstatus' => (string)($evaluation->previousstatus ?? ''),
            'competencyrating' => (string)($evaluation->competencyrating ?? ''),
            'badgetriggered' => !empty($evaluation->badgetriggered),
            'integritystate' => (string)($evaluation->integritystate ?? ''),
        ];

        if (!empty($evaluation->integritycaseid)) {
            $other['integritycaseid'] = (int)$evaluation->integritycaseid;
        }

        /** @var self $event */
        $event = self::create([
            'context' => context_module::instance((int)$cm->id),
            'objectid' => (int)$evaluation->id,
            'relateduserid' => (int)$submission->userid,
            'userid' => (int)$evaluator->id,
            'other' => $other,
        ]);

        $event->add_record_snapshot('uckkchallenge_eval', $evaluation);
        $event->add_record_snapshot('uckkchallenge', $challenge);
        $event->add_record_snapshot('uckkchallenge_sub', $submission);

        return $event;
    }
}