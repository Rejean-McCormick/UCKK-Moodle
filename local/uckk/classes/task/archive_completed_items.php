<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Scheduled task for archiving completed UCKK items.
 *
 * This task processes a lightweight queue of completed UCKK items that should
 * be transferred into the archive layer.
 *
 * The task does not decide whether an item deserves to be archived. That
 * decision must already have been made by the owning workflow:
 *
 * - local_uckk services;
 * - mod_uckkchallenge;
 * - mod_uckkassembly;
 * - mod_uckkarchive;
 * - tool_uckkintegrity;
 * - observers with explicit capability and validation checks.
 *
 * This task only performs delayed archival orchestration.
 *
 * Expected queue table:
 *
 * local_uckk_archive_queue
 *
 * Expected fields:
 *
 * - id
 * - component
 * - itemtype
 * - itemid
 * - userid
 * - contextid
 * - courseid
 * - title
 * - summary
 * - status
 * - visibility
 * - attempts
 * - maxattempts
 * - nextruntime
 * - lasterror
 * - archiveitemid
 * - timecreated
 * - timemodified
 *
 * Queue statuses:
 *
 * - pending
 * - processing
 * - archived
 * - failed
 * - skipped
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\task;

use context;
use core_component;
use core\task\scheduled_task;
use dml_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Archive completed UCKK items.
 *
 * @package local_uckk
 */
class archive_completed_items extends scheduled_task {
    /** Queue table name. */
    private const TABLE_QUEUE = 'local_uckk_archive_queue';

    /** Queue status: pending. */
    private const STATUS_PENDING = 'pending';

    /** Queue status: processing. */
    private const STATUS_PROCESSING = 'processing';

    /** Queue status: archived. */
    private const STATUS_ARCHIVED = 'archived';

    /** Queue status: failed. */
    private const STATUS_FAILED = 'failed';

    /** Queue status: skipped. */
    private const STATUS_SKIPPED = 'skipped';

    /** Default batch size. */
    private const DEFAULT_BATCH_SIZE = 50;

    /** Default maximum attempts. */
    private const DEFAULT_MAX_ATTEMPTS = 5;

    /** Retry delay after failure, in seconds. */
    private const RETRY_DELAY = 3600;

    /** Archive component. */
    private const ARCHIVE_COMPONENT = 'mod_uckkarchive';

    /**
     * Return the localized task name.
     *
     * Required language string:
     * - task_archive_completed_items
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_archive_completed_items', 'local_uckk');
    }

    /**
     * Execute the scheduled task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        mtrace('[local_uckk] Starting archive_completed_items task.');

        if (!$this->queue_table_exists()) {
            mtrace('[local_uckk] Queue table local_uckk_archive_queue does not exist. Nothing to archive.');
            return;
        }

        if (!$this->archive_plugin_available()) {
            mtrace('[local_uckk] mod_uckkarchive is not installed. Archival queue will not be processed.');
            return;
        }

        $batchsize = $this->get_batch_size();
        $now = time();

        $records = $this->get_pending_records($batchsize, $now);

        if (empty($records)) {
            mtrace('[local_uckk] No completed UCKK items waiting for archive.');
            return;
        }

        $processed = 0;
        $archived = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($records as $record) {
            $processed++;

            try {
                $result = $this->process_record($record);

                if ($result === self::STATUS_ARCHIVED) {
                    $archived++;
                } else if ($result === self::STATUS_SKIPPED) {
                    $skipped++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $exception) {
                $failed++;
                $this->mark_failed($record, $exception->getMessage());
            }
        }

        mtrace('[local_uckk] Archive task completed.');
        mtrace('[local_uckk] Processed: ' . $processed);
        mtrace('[local_uckk] Archived: ' . $archived);
        mtrace('[local_uckk] Skipped: ' . $skipped);
        mtrace('[local_uckk] Failed: ' . $failed);
    }

    /**
     * Process one archive queue record.
     *
     * @param stdClass $record Queue record.
     * @return string Final queue status.
     */
    private function process_record(stdClass $record): string {
        global $DB;

        if (!$this->can_attempt($record)) {
            $this->mark_skipped($record, 'Maximum archive attempts reached.');
            return self::STATUS_SKIPPED;
        }

        $this->mark_processing($record);

        if (!$this->record_has_required_fields($record)) {
            $this->mark_failed($record, 'Queue record is missing required fields.');
            return self::STATUS_FAILED;
        }

        if (!$this->context_exists((int)$record->contextid)) {
            $this->mark_failed($record, 'Queue record context does not exist.');
            return self::STATUS_FAILED;
        }

        if (!$this->source_component_available((string)$record->component)) {
            $this->mark_skipped($record, 'Source component is not installed: ' . $record->component);
            return self::STATUS_SKIPPED;
        }

        $archiveitemid = $this->create_archive_item($record);

        if ($archiveitemid <= 0) {
            $this->mark_failed($record, 'Archive service did not return an archive item id.');
            return self::STATUS_FAILED;
        }

        $updated = new stdClass();
        $updated->id = $record->id;
        $updated->status = self::STATUS_ARCHIVED;
        $updated->archiveitemid = $archiveitemid;
        $updated->lasterror = null;
        $updated->timemodified = time();

        $DB->update_record(self::TABLE_QUEUE, $updated);

        mtrace('[local_uckk] Archived queue item #' . $record->id . ' as archive item #' . $archiveitemid . '.');

        return self::STATUS_ARCHIVED;
    }

    /**
     * Create an archive item from a queue record.
     *
     * This method delegates to mod_uckkarchive when its service class is
     * available. It intentionally avoids writing directly to mod_uckkarchive
     * tables because archive ownership belongs to mod_uckkarchive.
     *
     * Supported service signatures, checked in order:
     *
     * - \mod_uckkarchive\local\archive_item::create_from_queue_record(stdClass $record)
     * - \mod_uckkarchive\local\archive_item::create_from_source(array $data)
     * - \mod_uckkarchive\api\archive_api::create_from_source(array $data)
     *
     * @param stdClass $record Queue record.
     * @return int Archive item id.
     * @throws moodle_exception
     */
    private function create_archive_item(stdClass $record): int {
        $data = $this->build_archive_data($record);

        $archiveitemclass = '\\mod_uckkarchive\\local\\archive_item';
        if (class_exists($archiveitemclass) && method_exists($archiveitemclass, 'create_from_queue_record')) {
            $result = $archiveitemclass::create_from_queue_record($record);
            return $this->normalise_archive_result($result);
        }

        if (class_exists($archiveitemclass) && method_exists($archiveitemclass, 'create_from_source')) {
            $result = $archiveitemclass::create_from_source($data);
            return $this->normalise_archive_result($result);
        }

        $archiveapiclass = '\\mod_uckkarchive\\api\\archive_api';
        if (class_exists($archiveapiclass) && method_exists($archiveapiclass, 'create_from_source')) {
            $result = $archiveapiclass::create_from_source($data);
            return $this->normalise_archive_result($result);
        }

        throw new moodle_exception('error_archive_service_missing', 'local_uckk');
    }

    /**
     * Build archive item data from a queue record.
     *
     * @param stdClass $record Queue record.
     * @return array<string, mixed>
     */
    private function build_archive_data(stdClass $record): array {
        return [
            'sourcecomponent' => (string)$record->component,
            'sourceitemtype' => (string)$record->itemtype,
            'sourceitemid' => (int)$record->itemid,
            'userid' => isset($record->userid) ? (int)$record->userid : 0,
            'contextid' => (int)$record->contextid,
            'courseid' => isset($record->courseid) ? (int)$record->courseid : 0,
            'title' => isset($record->title) ? (string)$record->title : $this->build_fallback_title($record),
            'summary' => isset($record->summary) ? (string)$record->summary : '',
            'visibility' => isset($record->visibility) ? (string)$record->visibility : 'course',
            'status' => 'submitted',
            'provenance' => [
                'origin' => 'local_uckk_archive_queue',
                'queueid' => (int)$record->id,
                'component' => (string)$record->component,
                'itemtype' => (string)$record->itemtype,
                'itemid' => (int)$record->itemid,
                'timequeued' => isset($record->timecreated) ? (int)$record->timecreated : null,
                'timearchived' => time(),
            ],
            'metadata' => [
                'archivedbytask' => self::class,
                'attempts' => isset($record->attempts) ? (int)$record->attempts : 0,
            ],
        ];
    }

    /**
     * Normalise the result returned by archive services.
     *
     * @param mixed $result Service result.
     * @return int Archive item id.
     */
    private function normalise_archive_result($result): int {
        if (is_int($result)) {
            return $result;
        }

        if (is_numeric($result)) {
            return (int)$result;
        }

        if (is_object($result) && isset($result->id)) {
            return (int)$result->id;
        }

        if (is_array($result) && isset($result['id'])) {
            return (int)$result['id'];
        }

        return 0;
    }

    /**
     * Get pending queue records.
     *
     * @param int $limit Batch size.
     * @param int $now Current timestamp.
     * @return stdClass[]
     */
    private function get_pending_records(int $limit, int $now): array {
        global $DB;

        $sql = "status = :status
            AND (nextruntime IS NULL OR nextruntime = 0 OR nextruntime <= :now)
            AND (archiveitemid IS NULL OR archiveitemid = 0)";

        $params = [
            'status' => self::STATUS_PENDING,
            'now' => $now,
        ];

        return $DB->get_records_select(
            self::TABLE_QUEUE,
            $sql,
            $params,
            'timecreated ASC, id ASC',
            '*',
            0,
            $limit
        );
    }

    /**
     * Mark a queue record as processing.
     *
     * @param stdClass $record Queue record.
     * @return void
     */
    private function mark_processing(stdClass $record): void {
        global $DB;

        $updated = new stdClass();
        $updated->id = $record->id;
        $updated->status = self::STATUS_PROCESSING;
        $updated->attempts = isset($record->attempts) ? ((int)$record->attempts + 1) : 1;
        $updated->timemodified = time();

        $DB->update_record(self::TABLE_QUEUE, $updated);

        $record->status = self::STATUS_PROCESSING;
        $record->attempts = $updated->attempts;
        $record->timemodified = $updated->timemodified;
    }

    /**
     * Mark a queue record as failed.
     *
     * @param stdClass $record Queue record.
     * @param string $reason Failure reason.
     * @return void
     */
    private function mark_failed(stdClass $record, string $reason): void {
        global $DB;

        if (!$this->queue_table_exists()) {
            return;
        }

        $attempts = isset($record->attempts) ? (int)$record->attempts : 0;
        $maxattempts = isset($record->maxattempts) && (int)$record->maxattempts > 0
            ? (int)$record->maxattempts
            : self::DEFAULT_MAX_ATTEMPTS;

        $status = $attempts >= $maxattempts ? self::STATUS_FAILED : self::STATUS_PENDING;
        $nextruntime = $status === self::STATUS_PENDING ? time() + self::RETRY_DELAY : 0;

        $updated = new stdClass();
        $updated->id = $record->id;
        $updated->status = $status;
        $updated->nextruntime = $nextruntime;
        $updated->lasterror = $this->truncate_error($reason);
        $updated->timemodified = time();

        try {
            $DB->update_record(self::TABLE_QUEUE, $updated);
        } catch (dml_exception $exception) {
            mtrace('[local_uckk] Could not update failed archive queue record #' . $record->id . ': ' . $exception->getMessage());
        }

        mtrace('[local_uckk] Archive queue item #' . $record->id . ' failed: ' . $reason);
    }

    /**
     * Mark a queue record as skipped.
     *
     * @param stdClass $record Queue record.
     * @param string $reason Skip reason.
     * @return void
     */
    private function mark_skipped(stdClass $record, string $reason): void {
        global $DB;

        $updated = new stdClass();
        $updated->id = $record->id;
        $updated->status = self::STATUS_SKIPPED;
        $updated->lasterror = $this->truncate_error($reason);
        $updated->timemodified = time();

        $DB->update_record(self::TABLE_QUEUE, $updated);

        mtrace('[local_uckk] Archive queue item #' . $record->id . ' skipped: ' . $reason);
    }

    /**
     * Determine whether a record can be attempted.
     *
     * @param stdClass $record Queue record.
     * @return bool
     */
    private function can_attempt(stdClass $record): bool {
        $attempts = isset($record->attempts) ? (int)$record->attempts : 0;
        $maxattempts = isset($record->maxattempts) && (int)$record->maxattempts > 0
            ? (int)$record->maxattempts
            : self::DEFAULT_MAX_ATTEMPTS;

        return $attempts < $maxattempts;
    }

    /**
     * Validate required queue fields.
     *
     * @param stdClass $record Queue record.
     * @return bool
     */
    private function record_has_required_fields(stdClass $record): bool {
        if (empty($record->component)) {
            return false;
        }

        if (empty($record->itemtype)) {
            return false;
        }

        if (empty($record->itemid)) {
            return false;
        }

        if (empty($record->contextid)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether a Moodle context exists.
     *
     * @param int $contextid Context id.
     * @return bool
     */
    private function context_exists(int $contextid): bool {
        if ($contextid <= 0) {
            return false;
        }

        try {
            context::instance_by_id($contextid, MUST_EXIST);
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Determine whether source component is available.
     *
     * @param string $component Component name.
     * @return bool
     */
    private function source_component_available(string $component): bool {
        if ($component === 'local_uckk') {
            return true;
        }

        return core_component::get_component_directory($component) !== null;
    }

    /**
     * Determine whether mod_uckkarchive is installed.
     *
     * @return bool
     */
    private function archive_plugin_available(): bool {
        return core_component::get_plugin_directory('mod', 'uckkarchive') !== null;
    }

    /**
     * Determine whether the queue table exists.
     *
     * @return bool
     */
    private function queue_table_exists(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(self::TABLE_QUEUE);
    }

    /**
     * Return configured batch size.
     *
     * @return int
     */
    private function get_batch_size(): int {
        $configured = get_config('local_uckk', 'archive_task_batch_size');

        if ($configured === false || $configured === null || $configured === '') {
            return self::DEFAULT_BATCH_SIZE;
        }

        $configured = (int)$configured;

        if ($configured < 1) {
            return 1;
        }

        if ($configured > 500) {
            return 500;
        }

        return $configured;
    }

    /**
     * Build fallback title for archive item.
     *
     * @param stdClass $record Queue record.
     * @return string
     */
    private function build_fallback_title(stdClass $record): string {
        $component = isset($record->component) ? (string)$record->component : 'unknown';
        $itemtype = isset($record->itemtype) ? (string)$record->itemtype : 'item';
        $itemid = isset($record->itemid) ? (int)$record->itemid : 0;

        return 'Archived ' . $component . ' ' . $itemtype . ' #' . $itemid;
    }

    /**
     * Truncate error text for storage.
     *
     * @param string $message Error message.
     * @return string
     */
    private function truncate_error(string $message): string {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        return \core_text::substr($message, 0, 2000);
    }
}