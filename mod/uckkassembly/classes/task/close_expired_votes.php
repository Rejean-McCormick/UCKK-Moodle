<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Scheduled task to close expired UCKK Assembly vote windows.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\task;

use core\lock\lock_config;
use core\task\scheduled_task;
use mod_uckkassembly\local\vote_service;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Close expired Assembly votes/readings.
 *
 * This task is orchestration only. It must not silently publish decisions,
 * rewrite minutes, modify archives, erase objections, or resolve contestations.
 * It finds vote windows that have expired and delegates closure to the
 * capability-aware Assembly vote service.
 */
final class close_expired_votes extends scheduled_task {
    /**
     * Default number of vote windows to process per cron run.
     */
    private const DEFAULT_BATCH_LIMIT = 100;

    /**
     * Lock namespace.
     */
    private const LOCK_TYPE = 'mod_uckkassembly_close_expired_votes';

    /**
     * Lock resource name.
     */
    private const LOCK_RESOURCE = 'global';

    /**
     * Lock wait timeout in seconds.
     */
    private const LOCK_TIMEOUT = 10;

    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcloseexpiredvotes', 'uckkassembly');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute(): void {
        $lockfactory = lock_config::get_lock_factory(self::LOCK_TYPE);
        $lock = $lockfactory->get_lock(self::LOCK_RESOURCE, self::LOCK_TIMEOUT);

        if (!$lock) {
            mtrace('[mod_uckkassembly] close_expired_votes skipped: another process holds the lock.');
            return;
        }

        try {
            $this->close_expired_vote_windows();
        } finally {
            $lock->release();
        }
    }

    /**
     * Close expired vote windows through the service layer.
     */
    private function close_expired_vote_windows(): void {
        $now = time();
        $batchlimit = $this->get_batch_limit();

        mtrace('[mod_uckkassembly] close_expired_votes started.');
        mtrace('[mod_uckkassembly] Batch limit: ' . $batchlimit);

        $service = new vote_service();

        $candidates = $service->get_expired_vote_windows($now, $batchlimit);

        if (empty($candidates)) {
            mtrace('[mod_uckkassembly] No expired Assembly votes found.');
            return;
        }

        $processed = 0;
        $closed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            $processed++;

            try {
                $result = $service->close_expired_vote_window($candidate, [
                    'source' => 'scheduled_task',
                    'task' => self::class,
                    'timeclosed' => $now,
                ]);

                if (!empty($result->closed)) {
                    $closed++;
                    mtrace($this->format_success_trace($candidate, $result));
                    continue;
                }

                $skipped++;
                mtrace($this->format_skip_trace($candidate, $result));
            } catch (Throwable $exception) {
                $failed++;
                mtrace($this->format_failure_trace($candidate, $exception));
            }
        }

        mtrace('[mod_uckkassembly] close_expired_votes finished.');
        mtrace('[mod_uckkassembly] Processed: ' . $processed);
        mtrace('[mod_uckkassembly] Closed: ' . $closed);
        mtrace('[mod_uckkassembly] Skipped: ' . $skipped);
        mtrace('[mod_uckkassembly] Failed: ' . $failed);
    }

    /**
     * Get configured batch limit.
     *
     * @return int
     */
    private function get_batch_limit(): int {
        $configured = get_config('mod_uckkassembly', 'closeexpiredvotes_batchlimit');

        if ($configured === false || $configured === null || $configured === '') {
            return self::DEFAULT_BATCH_LIMIT;
        }

        $limit = (int)$configured;

        if ($limit <= 0) {
            return self::DEFAULT_BATCH_LIMIT;
        }

        return min($limit, 1000);
    }

    /**
     * Format a successful closure trace.
     *
     * @param object $candidate Expired vote candidate.
     * @param object $result Closure result.
     * @return string
     */
    private function format_success_trace(object $candidate, object $result): string {
        return sprintf(
            '[mod_uckkassembly] Closed vote window: assemblyid=%d motionid=%d voteid=%d decisionid=%d status=%s',
            (int)($candidate->assemblyid ?? 0),
            (int)($candidate->motionid ?? 0),
            (int)($candidate->voteid ?? $candidate->id ?? 0),
            (int)($result->decisionid ?? 0),
            (string)($result->status ?? 'decision_draft')
        );
    }

    /**
     * Format a skipped closure trace.
     *
     * @param object $candidate Expired vote candidate.
     * @param object $result Closure result.
     * @return string
     */
    private function format_skip_trace(object $candidate, object $result): string {
        $reason = (string)($result->reason ?? 'not closable');

        return sprintf(
            '[mod_uckkassembly] Skipped vote window: assemblyid=%d motionid=%d voteid=%d reason=%s',
            (int)($candidate->assemblyid ?? 0),
            (int)($candidate->motionid ?? 0),
            (int)($candidate->voteid ?? $candidate->id ?? 0),
            $reason
        );
    }

    /**
     * Format a failed closure trace.
     *
     * @param object $candidate Expired vote candidate.
     * @param Throwable $exception Exception.
     * @return string
     */
    private function format_failure_trace(object $candidate, Throwable $exception): string {
        return sprintf(
            '[mod_uckkassembly] Failed to close vote window: assemblyid=%d motionid=%d voteid=%d error=%s',
            (int)($candidate->assemblyid ?? 0),
            (int)($candidate->motionid ?? 0),
            (int)($candidate->voteid ?? $candidate->id ?? 0),
            $exception->getMessage()
        );
    }
}