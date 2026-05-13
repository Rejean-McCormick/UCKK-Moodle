<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Scheduled task to close expired UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Close expired UCKK challenges.
 *
 * This task moves eligible challenge instances from published/open to expired
 * when their closing time has passed. It does not evaluate submissions, validate
 * proof, award badges, certify competencies, archive evidence, or resolve
 * integrity state.
 */
final class close_expired_challenges extends \core\task\scheduled_task {
    /**
     * Challenge can be configured but is not yet active.
     */
    private const STATUS_DRAFT = 'draft';

    /**
     * Challenge is published but not necessarily accepting new work.
     */
    private const STATUS_PUBLISHED = 'published';

    /**
     * Challenge is open for participation.
     */
    private const STATUS_OPEN = 'open';

    /**
     * Challenge has received a submission.
     */
    private const STATUS_SUBMITTED = 'submitted';

    /**
     * Challenge is under ordinary review.
     */
    private const STATUS_UNDER_REVIEW = 'under_review';

    /**
     * Challenge is under integrity review.
     */
    private const STATUS_INTEGRITY_REVIEW = 'integrity_review';

    /**
     * Challenge requires correction.
     */
    private const STATUS_REVISION_REQUIRED = 'revision_required';

    /**
     * Challenge has been resubmitted after correction.
     */
    private const STATUS_RESUBMITTED = 'resubmitted';

    /**
     * Challenge has been validated.
     */
    private const STATUS_VALIDATED = 'validated';

    /**
     * Challenge has been archived.
     */
    private const STATUS_ARCHIVED = 'archived';

    /**
     * Challenge has been closed.
     */
    private const STATUS_CLOSED = 'closed';

    /**
     * Challenge has been contested.
     */
    private const STATUS_CONTESTED = 'contested';

    /**
     * Challenge has been invalidated.
     */
    private const STATUS_INVALIDATED = 'invalidated';

    /**
     * Challenge was withdrawn.
     */
    private const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Challenge has expired.
     */
    private const STATUS_EXPIRED = 'expired';

    /**
     * Maximum records processed in one task run.
     */
    private const DEFAULT_BATCH_SIZE = 200;

    /**
     * Return localised task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:closeexpiredchallenges', 'uckkchallenge');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute(): void {
        global $DB;

        $now = time();
        $processed = 0;
        $expired = 0;
        $skipped = 0;

        $eligible = [
            self::STATUS_PUBLISHED,
            self::STATUS_OPEN,
        ];

        [$statussql, $statusparams] = $DB->get_in_or_equal($eligible, SQL_PARAMS_NAMED, 'status');

        $params = $statusparams + [
            'now' => $now,
        ];

        $sql = "
            SELECT c.*
              FROM {uckkchallenge} c
             WHERE c.status {$statussql}
               AND c.timeclose > 0
               AND c.timeclose < :now
          ORDER BY c.timeclose ASC, c.id ASC
        ";

        $recordset = $DB->get_recordset_sql($sql, $params, 0, self::DEFAULT_BATCH_SIZE);

        foreach ($recordset as $challenge) {
            $processed++;

            if (!$this->can_expire_challenge($challenge, $now)) {
                $skipped++;
                continue;
            }

            $transaction = $DB->start_delegated_transaction();

            try {
                $before = clone $challenge;

                $challenge->status = self::STATUS_EXPIRED;
                $challenge->timemodified = $now;

                if (property_exists($challenge, 'modifiedby')) {
                    $challenge->modifiedby = 0;
                }

                if (property_exists($challenge, 'versionno')) {
                    $challenge->versionno = ((int)$challenge->versionno) + 1;
                }

                $DB->update_record('uckkchallenge', $challenge);

                $this->trigger_expired_event_if_available($before, $challenge);

                $transaction->allow_commit();
                $expired++;
            } catch (\Throwable $exception) {
                $transaction->rollback($exception);
            }
        }

        $recordset->close();

        mtrace(get_string('task:closeexpiredchallengesresult', 'uckkchallenge', (object)[
            'processed' => $processed,
            'expired' => $expired,
            'skipped' => $skipped,
        ]));
    }

    /**
     * Check whether this challenge can safely be expired by the scheduled task.
     *
     * The task only expires challenges that are still published/open and past
     * their closing time. It deliberately avoids all review, integrity,
     * validation, archive, contestation, and final states.
     *
     * @param \stdClass $challenge Challenge record.
     * @param int $now Current timestamp.
     * @return bool
     */
    private function can_expire_challenge(\stdClass $challenge, int $now): bool {
        if (empty($challenge->id)) {
            return false;
        }

        if (empty($challenge->timeclose) || (int)$challenge->timeclose >= $now) {
            return false;
        }

        if (!in_array((string)$challenge->status, [
            self::STATUS_PUBLISHED,
            self::STATUS_OPEN,
        ], true)) {
            return false;
        }

        if ($this->has_any_submission((int)$challenge->id)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the challenge already has submissions.
     *
     * A challenge with submissions should move through review, correction,
     * validation, contestation, archive, or integrity workflows instead of being
     * silently expired by a scheduled task.
     *
     * @param int $challengeid Challenge id.
     * @return bool
     */
    private function has_any_submission(int $challengeid): bool {
        global $DB;

        if ($challengeid <= 0) {
            return false;
        }

        if (!$DB->get_manager()->table_exists('uckkchallenge_sub')) {
            return false;
        }

        return $DB->record_exists('uckkchallenge_sub', [
            'challengeid' => $challengeid,
        ]);
    }

    /**
     * Trigger a challenge_expired event when the event class exists.
     *
     * This keeps the task compatible while the event family is being completed.
     * Once mod_uckkchallenge\event\challenge_expired is present, expiry becomes
     * visible in the Moodle event log.
     *
     * @param \stdClass $before Challenge before update.
     * @param \stdClass $after Challenge after update.
     */
    private function trigger_expired_event_if_available(\stdClass $before, \stdClass $after): void {
        global $DB;

        $eventclass = '\\mod_uckkchallenge\\event\\challenge_expired';

        if (!class_exists($eventclass)) {
            return;
        }

        if (empty($after->course)) {
            return;
        }

        $cm = get_coursemodule_from_instance('uckkchallenge', (int)$after->id, (int)$after->course, false, IGNORE_MISSING);

        if (!$cm) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $after->course], '*', IGNORE_MISSING);

        if (!$course) {
            return;
        }

        $context = \context_module::instance((int)$cm->id);

        $event = $eventclass::create([
            'objectid' => (int)$after->id,
            'context' => $context,
            'other' => [
                'courseid' => (int)$course->id,
                'cmid' => (int)$cm->id,
                'previousstatus' => (string)$before->status,
                'newstatus' => (string)$after->status,
                'timeclose' => (int)$after->timeclose,
            ],
        ]);

        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkchallenge', $after);
        $event->trigger();
    }
}