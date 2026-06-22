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
 * Scheduled task to synchronise UCKK pathway progress.
 *
 * This task refreshes derived pathway progress records for active UCKK players.
 *
 * It must remain an orchestration task:
 *
 * - it may find users and pathways to synchronise;
 * - it may batch work to avoid large cron runs;
 * - it may call local_uckk APIs;
 * - it may persist derived progress summaries;
 * - it may log safe operational information.
 *
 * It must not:
 *
 * - award badges directly;
 * - mark competencies proficient directly;
 * - validate archive items;
 * - close integrity cases;
 * - create public recognitions;
 * - decide whether a user has earned an internal title;
 * - replace human validation.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\task;

use context_system;
use core\task\scheduled_task;
use core_text;
use local_uckk\api\pathway_api;
use moodle_exception;
use stdClass;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Synchronise UCKK pathway progress.
 *
 * This task expects these local_uckk tables to exist:
 *
 * - local_uckk_pathway
 * - local_uckk_player
 * - local_uckk_pathway_stat
 *
 * The table local_uckk_pathway_stat stores derived status only. It is not the
 * source of truth for courses, completion, competencies, badges, archive items
 * or integrity cases.
 *
 * @package local_uckk
 */
final class sync_pathway_progress extends scheduled_task {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Pathway table. */
    private const TABLE_PATHWAY = 'local_uckk_pathway';

    /** Player table. */
    private const TABLE_PLAYER = 'local_uckk_player';

    /** Derived pathway progress table. */
    private const TABLE_PATHWAY_STATUS = 'local_uckk_pathway_stat';

    /** Default batch size. */
    private const DEFAULT_BATCH_SIZE = 200;

    /** Minimum batch size. */
    private const MIN_BATCH_SIZE = 25;

    /** Maximum batch size. */
    private const MAX_BATCH_SIZE = 1000;

    /** Status: active. */
    private const STATUS_ACTIVE = 'active';

    /** Progress status: not started. */
    private const PROGRESS_NOT_STARTED = 'not_started';

    /** Progress status: in progress. */
    private const PROGRESS_IN_PROGRESS = 'in_progress';

    /** Progress status: pending review. */
    private const PROGRESS_PENDING_REVIEW = 'pending_review';

    /** Progress status: completed. */
    private const PROGRESS_COMPLETED = 'completed';

    /** Progress status: blocked. */
    private const PROGRESS_BLOCKED = 'blocked';

    /**
     * Return the task name shown in Moodle admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        if (get_string_manager()->string_exists('task_sync_pathway_progress', self::COMPONENT)) {
            return get_string('task_sync_pathway_progress', self::COMPONENT);
        }

        return 'Synchronise UCKK pathway progress';
    }

    /**
     * Execute the scheduled task.
     *
     * @return void
     */
    public function execute(): void {
        $start = time();

        mtrace('[local_uckk] Starting pathway progress synchronisation.');

        if (!$this->required_tables_exist()) {
            mtrace('[local_uckk] Required tables are missing. Pathway progress synchronisation skipped.');
            return;
        }

        if (!class_exists(pathway_api::class)) {
            mtrace('[local_uckk] local_uckk\\api\\pathway_api is missing. Pathway progress synchronisation skipped.');
            return;
        }

        $batchsize = $this->get_batch_size();
        $context = context_system::instance();

        $stats = [
            'players' => 0,
            'pathways' => 0,
            'pairs' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        try {
            $activepathways = $this->get_active_pathways();
            $stats['pathways'] = count($activepathways);

            if (empty($activepathways)) {
                mtrace('[local_uckk] No active pathways found.');
                return;
            }

            $offset = 0;

            do {
                $players = $this->get_active_players($offset, $batchsize);
                $playercount = count($players);

                if ($playercount === 0) {
                    break;
                }

                $stats['players'] += $playercount;

                foreach ($players as $player) {
                    foreach ($activepathways as $pathway) {
                        $stats['pairs']++;

                        try {
                            $result = $this->sync_one($player, $pathway, $context);
                            $stats[$result] = ($stats[$result] ?? 0) + 1;
                        } catch (Throwable $exception) {
                            $stats['errors']++;
                            $this->trace_exception($exception, $player, $pathway);
                        }
                    }
                }

                $offset += $batchsize;

                mtrace(
                    '[local_uckk] Batch complete. Players processed so far: '
                    . $stats['players']
                    . ', pairs: '
                    . $stats['pairs']
                    . ', errors: '
                    . $stats['errors']
                    . '.'
                );
            } while ($playercount === $batchsize);
        } catch (Throwable $exception) {
            mtrace('[local_uckk] Fatal error during pathway progress synchronisation: ' . $exception->getMessage());
            throw $exception;
        } finally {
            $duration = time() - $start;

            mtrace(
                '[local_uckk] Pathway progress synchronisation finished in '
                . $duration
                . 's. Players='
                . $stats['players']
                . ', pathways='
                . $stats['pathways']
                . ', pairs='
                . $stats['pairs']
                . ', created='
                . $stats['created']
                . ', updated='
                . $stats['updated']
                . ', unchanged='
                . $stats['unchanged']
                . ', skipped='
                . $stats['skipped']
                . ', errors='
                . $stats['errors']
                . '.'
            );
        }
    }

    /**
     * Synchronise one player/pathway pair.
     *
     * @param stdClass $player Player record.
     * @param stdClass $pathway Pathway record.
     * @param context_system $context System context.
     * @return string One of created, updated, unchanged, skipped.
     */
    private function sync_one(stdClass $player, stdClass $pathway, context_system $context): string {
        global $DB;

        $userid = (int)($player->userid ?? 0);
        $pathwayid = (int)($pathway->id ?? 0);

        if ($userid <= 0 || $pathwayid <= 0) {
            return 'skipped';
        }

        if (!$this->player_is_assigned_to_pathway($player, $pathway)) {
            return 'skipped';
        }

        $status = $this->calculate_status($userid, $pathway, $context);
        $record = $this->build_status_record($userid, $pathwayid, $status);

        $existing = $DB->get_record(self::TABLE_PATHWAY_STATUS, [
            'userid' => $userid,
            'pathwayid' => $pathwayid,
        ]);

        if (!$existing) {
            $record->timecreated = time();
            $record->timemodified = time();

            $DB->insert_record(self::TABLE_PATHWAY_STATUS, $record);

            return 'created';
        }

        $record->id = (int)$existing->id;
        $record->timecreated = (int)($existing->timecreated ?? time());
        $record->timemodified = time();

        if (!$this->status_has_changed($existing, $record)) {
            return 'unchanged';
        }

        $DB->update_record(self::TABLE_PATHWAY_STATUS, $record);

        return 'updated';
    }

    /**
     * Calculate pathway status for a user.
     *
     * The preferred path is local_uckk\api\pathway_api because that API owns
     * the pathway calculation policy. This task only normalises the result for
     * persistence.
     *
     * @param int $userid User id.
     * @param stdClass $pathway Pathway record.
     * @param context_system $context System context.
     * @return array<string, mixed>
     */
    private function calculate_status(int $userid, stdClass $pathway, context_system $context): array {
        if (method_exists(pathway_api::class, 'calculate_user_pathway_progress')) {
            $status = pathway_api::calculate_user_pathway_progress(
                $userid,
                (int)$pathway->id,
                [
                    'includeprogram' => true,
                    'includecourses' => true,
                    'includecompetencies' => true,
                    'includebadges' => true,
                    'includeportfolio' => true,
                    'includearchives' => true,
                    'includeintegrity' => true,
                    'source' => 'scheduled_task',
                ],
                $context
            );

            return $this->normalise_status($status, $pathway);
        }

        if (method_exists(pathway_api::class, 'get_user_pathway_status')) {
            $status = pathway_api::get_user_pathway_status(
                $userid,
                (int)$pathway->id,
                [
                    'includeprogram' => true,
                    'includecourses' => true,
                    'includecompetencies' => true,
                    'includebadges' => true,
                    'includeportfolio' => true,
                    'includearchives' => true,
                    'includeintegrity' => true,
                    'source' => 'scheduled_task',
                ],
                $context
            );

            return $this->normalise_status($status, $pathway);
        }

        throw new moodle_exception('missingpathwaymethod', self::COMPONENT, '', 'calculate_user_pathway_progress');
    }

    /**
     * Normalise a status returned by pathway_api.
     *
     * @param mixed $status Raw status.
     * @param stdClass $pathway Pathway record.
     * @return array<string, mixed>
     */
    private function normalise_status($status, stdClass $pathway): array {
        $status = $this->to_array($status);

        $progress = $this->to_array($status['progress'] ?? []);
        $portfolio = $this->to_array($status['portfolio'] ?? []);
        $integrity = $this->to_array($status['integrity'] ?? []);

        $requiredcount = (int)($progress['requiredcount'] ?? $status['requiredcount'] ?? 0);
        $completedcount = (int)($progress['completedcount'] ?? $status['completedcount'] ?? 0);
        $pendingcount = (int)($progress['pendingcount'] ?? $status['pendingcount'] ?? 0);
        $blockedcount = (int)($progress['blockedcount'] ?? $status['blockedcount'] ?? 0);

        if ($requiredcount <= 0) {
            $requiredcount = $this->infer_required_count($pathway);
        }

        $completionpercent = (float)($progress['completionpercent'] ?? $status['completionpercent'] ?? 0.0);
        if ($completionpercent <= 0.0 && $requiredcount > 0) {
            $completionpercent = round(($completedcount / $requiredcount) * 100, 2);
        }

        $progressstatus = (string)($progress['status'] ?? $status['progressstatus'] ?? '');
        if ($progressstatus === '') {
            $progressstatus = $this->infer_progress_status($requiredcount, $completedcount, $pendingcount, $blockedcount);
        }

        $hasopenintegrity = (bool)($integrity['hasopenissues'] ?? $status['hasopenintegrity'] ?? false);
        $opencasecount = (int)($integrity['opencount'] ?? $status['integrityopencount'] ?? 0);

        if ($hasopenintegrity || $opencasecount > 0) {
            $progressstatus = self::PROGRESS_BLOCKED;
            $blockedcount = max(1, $blockedcount);
        }

        return [
            'progressstatus' => $progressstatus,
            'completionpercent' => $this->clamp_percent($completionpercent),
            'requiredcount' => max(0, $requiredcount),
            'completedcount' => max(0, $completedcount),
            'pendingcount' => max(0, $pendingcount),
            'blockedcount' => max(0, $blockedcount),
            'coursecount' => count($this->to_array($status['courses'] ?? [])),
            'competencycount' => count($this->to_array($status['competencies'] ?? [])),
            'badgecount' => count($this->to_array($status['badges'] ?? [])),
            'archivecount' => count($this->to_array($status['archives'] ?? [])),
            'portfolioitemcount' => (int)($portfolio['itemcount'] ?? $status['portfolioitemcount'] ?? 0),
            'validatedportfolioitemcount' => (int)($portfolio['validatedcount'] ?? 0),
            'pendingportfolioitemcount' => (int)($portfolio['pendingcount'] ?? 0),
            'hasopenintegrity' => $hasopenintegrity,
            'integrityopencount' => $opencasecount,
            'lastcalculated' => time(),
            'metadata' => [
                'source' => 'local_uckk\\task\\sync_pathway_progress',
                'pathwayidnumber' => (string)($pathway->idnumber ?? ''),
                'pathwayshortname' => (string)($pathway->shortname ?? ''),
                'internalrecognition' => true,
            ],
        ];
    }

    /**
     * Build a DB status record from normalised data.
     *
     * @param int $userid User id.
     * @param int $pathwayid Pathway id.
     * @param array<string, mixed> $status Normalised status.
     * @return stdClass
     */
    private function build_status_record(int $userid, int $pathwayid, array $status): stdClass {
        $record = new stdClass();

        $record->userid = $userid;
        $record->pathwayid = $pathwayid;
        $record->progressstatus = (string)$status['progressstatus'];
        $record->completionpercent = (float)$status['completionpercent'];
        $record->requiredcount = (int)$status['requiredcount'];
        $record->completedcount = (int)$status['completedcount'];
        $record->pendingcount = (int)$status['pendingcount'];
        $record->blockedcount = (int)$status['blockedcount'];
        $record->coursecount = (int)$status['coursecount'];
        $record->competencycount = (int)$status['competencycount'];
        $record->badgecount = (int)$status['badgecount'];
        $record->archivecount = (int)$status['archivecount'];
        $record->portfolioitemcount = (int)$status['portfolioitemcount'];
        $record->validatedportfolioitemcount = (int)$status['validatedportfolioitemcount'];
        $record->pendingportfolioitemcount = (int)$status['pendingportfolioitemcount'];
        $record->hasopenintegrity = !empty($status['hasopenintegrity']) ? 1 : 0;
        $record->integrityopencount = (int)$status['integrityopencount'];
        $record->lastcalculated = (int)$status['lastcalculated'];
        $record->metadata = $this->encode_json($status['metadata'] ?? []);

        return $record;
    }

    /**
     * Determine whether stored status differs from a new record.
     *
     * @param stdClass $existing Existing DB record.
     * @param stdClass $new New DB record.
     * @return bool
     */
    private function status_has_changed(stdClass $existing, stdClass $new): bool {
        $fields = [
            'progressstatus',
            'completionpercent',
            'requiredcount',
            'completedcount',
            'pendingcount',
            'blockedcount',
            'coursecount',
            'competencycount',
            'badgecount',
            'archivecount',
            'portfolioitemcount',
            'validatedportfolioitemcount',
            'pendingportfolioitemcount',
            'hasopenintegrity',
            'integrityopencount',
            'metadata',
        ];

        foreach ($fields as $field) {
            $old = $existing->{$field} ?? null;
            $fresh = $new->{$field} ?? null;

            if ($field === 'completionpercent') {
                if (round((float)$old, 2) !== round((float)$fresh, 2)) {
                    return true;
                }
                continue;
            }

            if ((string)$old !== (string)$fresh) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get active pathways.
     *
     * @return stdClass[]
     */
    private function get_active_pathways(): array {
        global $DB;

        return array_values($DB->get_records(
            self::TABLE_PATHWAY,
            ['status' => self::STATUS_ACTIVE],
            'sortorder ASC, fullname ASC'
        ));
    }

    /**
     * Get active players in batches.
     *
     * The player table is expected to map UCKK player profiles to Moodle users.
     *
     * @param int $offset Offset.
     * @param int $limit Limit.
     * @return stdClass[]
     */
    private function get_active_players(int $offset, int $limit): array {
        global $DB;

        $sql = "SELECT p.*
                  FROM {" . self::TABLE_PLAYER . "} p
                  JOIN {user} u ON u.id = p.userid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND (p.status = :active OR p.status IS NULL OR p.status = '')
              ORDER BY p.userid ASC";

        return array_values($DB->get_records_sql($sql, ['active' => self::STATUS_ACTIVE], $offset, $limit));
    }

    /**
     * Determine whether a player should be synchronised for a pathway.
     *
     * @param stdClass $player Player record.
     * @param stdClass $pathway Pathway record.
     * @return bool
     */
    private function player_is_assigned_to_pathway(stdClass $player, stdClass $pathway): bool {
        $pathwayid = (int)($pathway->id ?? 0);
        $shortname = (string)($pathway->shortname ?? '');

        if ($pathwayid <= 0) {
            return false;
        }

        $activeids = $this->decode_list($player->activepathwayids ?? $player->pathwayids ?? []);
        $activekeys = $this->decode_list($player->activepathways ?? $player->pathways ?? []);

        if (empty($activeids) && empty($activekeys)) {
            return true;
        }

        foreach ($activeids as $id) {
            if ((int)$id === $pathwayid) {
                return true;
            }
        }

        foreach ($activekeys as $key) {
            if ($this->normalise_key($key) === $this->normalise_key($shortname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer required count from pathway JSON fields when API does not provide it.
     *
     * @param stdClass $pathway Pathway record.
     * @return int
     */
    private function infer_required_count(stdClass $pathway): int {
        $fields = [
            'requiredcourseids',
            'requiredcourses',
            'requiredcompetencies',
            'requiredbadges',
            'requiredarchives',
            'requiredportfolioitems',
            'requiredchallenges',
        ];

        $count = 0;

        foreach ($fields as $field) {
            $count += count($this->decode_list($pathway->{$field} ?? []));
        }

        return $count;
    }

    /**
     * Infer progress status from counts.
     *
     * @param int $required Required count.
     * @param int $completed Completed count.
     * @param int $pending Pending count.
     * @param int $blocked Blocked count.
     * @return string
     */
    private function infer_progress_status(int $required, int $completed, int $pending, int $blocked): string {
        if ($blocked > 0) {
            return self::PROGRESS_BLOCKED;
        }

        if ($required > 0 && $completed >= $required) {
            return self::PROGRESS_COMPLETED;
        }

        if ($pending > 0) {
            return self::PROGRESS_PENDING_REVIEW;
        }

        if ($completed > 0) {
            return self::PROGRESS_IN_PROGRESS;
        }

        return self::PROGRESS_NOT_STARTED;
    }

    /**
     * Get configured batch size.
     *
     * @return int
     */
    private function get_batch_size(): int {
        $configured = (int)get_config(self::COMPONENT, 'pathwayprogressbatchsize');

        if ($configured <= 0) {
            $configured = self::DEFAULT_BATCH_SIZE;
        }

        return max(self::MIN_BATCH_SIZE, min(self::MAX_BATCH_SIZE, $configured));
    }

    /**
     * Check whether all required tables exist.
     *
     * @return bool
     */
    private function required_tables_exist(): bool {
        global $DB;

        $dbman = $DB->get_manager();

        foreach ([self::TABLE_PATHWAY, self::TABLE_PLAYER, self::TABLE_PATHWAY_STATUS] as $tablename) {
            if (!$dbman->table_exists($tablename)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trace a non-fatal exception.
     *
     * @param Throwable $exception Exception.
     * @param stdClass $player Player record.
     * @param stdClass $pathway Pathway record.
     * @return void
     */
    private function trace_exception(Throwable $exception, stdClass $player, stdClass $pathway): void {
        $userid = (int)($player->userid ?? 0);
        $pathwayid = (int)($pathway->id ?? 0);
        $pathwayshortname = (string)($pathway->shortname ?? '');

        mtrace(
            '[local_uckk] Error syncing userid='
            . $userid
            . ', pathwayid='
            . $pathwayid
            . ', pathwayshortname='
            . $pathwayshortname
            . ': '
            . $exception->getMessage()
        );
    }

    /**
     * Convert mixed value to array.
     *
     * @param mixed $value Raw value.
     * @return array<mixed>
     */
    private function to_array($value): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }

        return [];
    }

    /**
     * Decode a JSON list or accept an array.
     *
     * @param mixed $value Raw value.
     * @return array<int, mixed>
     */
    private function decode_list($value): array {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_object($value)) {
            return array_values((array)$value);
        }

        $value = trim((string)$value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    /**
     * Encode data as JSON.
     *
     * @param mixed $data Data.
     * @return string
     */
    private function encode_json($data): string {
        if (!is_array($data) && !is_object($data)) {
            $data = [];
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Clamp percentage to the 0-100 range.
     *
     * @param float $value Raw value.
     * @return float
     */
    private function clamp_percent(float $value): float {
        if ($value < 0.0) {
            return 0.0;
        }

        if ($value > 100.0) {
            return 100.0;
        }

        return round($value, 2);
    }

    /**
     * Normalise a stable key.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function normalise_key($value): string {
        $value = trim((string)$value);
        $value = core_text::strtolower($value);
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = clean_param($value, PARAM_ALPHANUMEXT);
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}