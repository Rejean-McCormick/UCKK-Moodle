<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Scheduled task for UCKK distribution seeding.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\task;

use core\task\scheduled_task;
use tool_uckkseed\local\seeder;
use tool_uckkseed\local\validation_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task that validates or applies the UCKK seed distribution.
 *
 * This task is guarded by admin settings. It never resets content, deletes
 * records, validates archive evidence, awards badges, certifies competencies,
 * or performs integrity decisions. It delegates seed work to the seeder service.
 */
final class seed_distribution extends scheduled_task {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Canonical task source. */
    private const SOURCE = 'scheduled_task';

    /** Canonical action. */
    private const ACTION_SEED = 'seed';

    /** Default academic registry JSON folder, relative to Moodle root. */
    private const DEFAULT_PRESET_PATH = 'academic_registry_json';

    /** Apply mode. */
    private const MODE_APPLY = 'apply';

    /** Dry-run mode. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Report mode. */
    private const MODE_REPORT = 'report';

    /** Rollback-plan mode. */
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Completed status. */
    private const STATUS_COMPLETED = 'completed';

    /** Failed status. */
    private const STATUS_FAILED = 'failed';

    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_seed_distribution', self::COMPONENT);
    }

    /**
     * Execute scheduled seed validation/application.
     *
     * @return void
     */
    public function execute(): void {
        if (!$this->is_tool_enabled()) {
            mtrace('[tool_uckkseed] Seed distribution task skipped: tool disabled.');
            return;
        }

        if (!$this->is_autoseed_enabled()) {
            mtrace('[tool_uckkseed] Seed distribution task skipped: automatic seeding disabled.');
            return;
        }

        $mode = $this->get_task_mode();
        $presetpath = $this->resolve_preset_path();

        mtrace('[tool_uckkseed] Starting UCKK seed distribution task.');
        mtrace('[tool_uckkseed] Mode: ' . $mode);
        mtrace('[tool_uckkseed] Academic registry JSON path: ' . $presetpath);

        $this->ensure_preset_path_is_readable($presetpath);

        $options = [
            'action' => self::ACTION_SEED,
            'mode' => $mode,
            'source' => self::SOURCE,
            'scheduled' => true,
            'presetpath' => $presetpath,
            'preset' => '',
            'presets' => [],
            'component' => '',
            'components' => [],
            'target' => '',
            'force' => false,
            'confirm' => false,
        ];

        try {
            $seeder = new seeder($presetpath);
            $result = $seeder->seed($options);
            $data = $this->result_to_array($result);

            $this->trace_result($data);

            if (!$this->result_is_successful($data)) {
                throw new \moodle_exception(
                    'task_seed_distribution_failed',
                    self::COMPONENT,
                    '',
                    null,
                    (string)($data['summary'] ?? '')
                );
            }

            mtrace('[tool_uckkseed] UCKK seed distribution task completed.');
        } catch (\Throwable $exception) {
            mtrace('[tool_uckkseed] UCKK seed distribution task failed.');
            mtrace('[tool_uckkseed] ' . $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Whether the seed tool is enabled.
     *
     * @return bool
     */
    private function is_tool_enabled(): bool {
        $enabled = get_config(self::COMPONENT, 'enabletool');

        return $enabled === false || (int)$enabled === 1;
    }

    /**
     * Whether automatic scheduled seeding is enabled.
     *
     * @return bool
     */
    private function is_autoseed_enabled(): bool {
        $enabled = get_config(self::COMPONENT, 'autoseedoninstall');

        return $enabled !== false && (int)$enabled === 1;
    }

    /**
     * Get scheduled task mode from configuration.
     *
     * @return string
     */
    private function get_task_mode(): string {
        $configured = get_config(self::COMPONENT, 'defaultmode');
        $mode = clean_param((string)($configured ?: self::MODE_DRY_RUN), PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        if (!in_array($mode, $allowed, true)) {
            return self::MODE_DRY_RUN;
        }

        // A scheduled seed task must never execute rollback logic. If the
        // configured default is rollback_plan, keep the task non-destructive.
        if ($mode === self::MODE_ROLLBACK_PLAN) {
            return self::MODE_DRY_RUN;
        }

        return $mode;
    }

    /**
     * Resolve the academic registry JSON path.
     *
     * The admin setting may be:
     * - empty: use academic_registry_json at Moodle root;
     * - relative: resolve from Moodle root;
     * - absolute: use as-is.
     *
     * @return string Absolute path.
     */
    private function resolve_preset_path(): string {
        global $CFG;

        $configured = get_config(self::COMPONENT, 'presetpath');

        if ($configured === false || trim((string)$configured) === '') {
            $configured = self::DEFAULT_PRESET_PATH;
        }

        $configured = $this->clean_path((string)$configured);

        if ($configured === '') {
            $configured = self::DEFAULT_PRESET_PATH;
        }

        if ($this->is_absolute_path($configured)) {
            return rtrim($configured, DIRECTORY_SEPARATOR);
        }

        return rtrim($CFG->dirroot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . trim($configured, "\\/");
    }

    /**
     * Basic path cleanup for an admin-controlled path setting.
     *
     * @param string $path Path.
     * @return string Cleaned path.
     */
    private function clean_path(string $path): string {
        $path = str_replace("\0", '', $path);
        $path = trim($path);

        // Keep Windows backslashes allowed, but normalize repeated trailing separators later.
        return $path;
    }

    /**
     * Whether a path is absolute on Windows or Unix-like systems.
     *
     * @param string $path Path.
     * @return bool
     */
    private function is_absolute_path(string $path): bool {
        if ($path === '') {
            return false;
        }

        // Unix/Linux/macOS absolute path: /var/www/moodle/academic_registry_json.
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        // Windows absolute path: C:\path or C:/path.
        return (bool)preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    /**
     * Fail early if the academic registry JSON folder is not readable.
     *
     * @param string $presetpath Absolute path.
     * @return void
     */
    private function ensure_preset_path_is_readable(string $presetpath): void {
        if (!is_dir($presetpath)) {
            throw new \moodle_exception(
                'presetpathnotfound',
                self::COMPONENT,
                '',
                $presetpath
            );
        }

        if (!is_readable($presetpath)) {
            throw new \moodle_exception(
                'presetpathnotreadable',
                self::COMPONENT,
                '',
                $presetpath
            );
        }
    }

    /**
     * Convert result object to a stable array.
     *
     * @param mixed $result Result object.
     * @return array<string, mixed>
     */
    private function result_to_array(mixed $result): array {
        if ($result instanceof validation_result && method_exists($result, 'to_array')) {
            return $result->to_array();
        }

        if (is_object($result) && method_exists($result, 'to_array')) {
            return $result->to_array();
        }

        if (is_object($result)) {
            $data = get_object_vars($result);
        } else if (is_array($result)) {
            $data = $result;
        } else {
            $data = [
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'summary' => (string)$result,
            ];
        }

        return [
            'status' => (string)($data['status'] ?? self::STATUS_COMPLETED),
            'ok' => (bool)($data['ok'] ?? empty($data['haserrors'])),
            'haserrors' => (bool)($data['haserrors'] ?? false),
            'haswarnings' => (bool)($data['haswarnings'] ?? false),
            'summary' => (string)($data['summary'] ?? ''),
            'counts' => $data['counts'] ?? [],
            'messages' => $data['messages'] ?? [],
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    /**
     * Whether a result is successful.
     *
     * @param array<string, mixed> $data Result data.
     * @return bool
     */
    private function result_is_successful(array $data): bool {
        return !empty($data['ok']) && empty($data['haserrors']);
    }

    /**
     * Trace task result details.
     *
     * @param array<string, mixed> $data Result data.
     * @return void
     */
    private function trace_result(array $data): void {
        $status = (string)($data['status'] ?? self::STATUS_COMPLETED);
        $summary = (string)($data['summary'] ?? '');

        mtrace('[tool_uckkseed] Status: ' . $status);

        if ($summary !== '') {
            mtrace('[tool_uckkseed] Summary: ' . $summary);
        }

        if (!empty($data['counts']) && is_array($data['counts'])) {
            foreach ($data['counts'] as $key => $value) {
                mtrace('[tool_uckkseed] Count ' . $key . ': ' . $value);
            }
        }

        if (!empty($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $message) {
                $this->trace_message($message);
            }
        }
    }

    /**
     * Trace one validation message.
     *
     * @param mixed $message Message row.
     * @return void
     */
    private function trace_message(mixed $message): void {
        if (is_object($message)) {
            $message = get_object_vars($message);
        }

        if (!is_array($message)) {
            mtrace('[tool_uckkseed] Message: ' . (string)$message);
            return;
        }

        $severity = strtoupper((string)($message['severity'] ?? 'info'));
        $preset = (string)($message['preset'] ?? '');
        $targettype = (string)($message['targettype'] ?? '');
        $targetkey = (string)($message['targetkey'] ?? '');
        $text = (string)($message['message'] ?? '');

        $prefix = '[tool_uckkseed] [' . $severity . ']';

        if ($preset !== '') {
            $prefix .= ' [' . $preset . ']';
        }

        if ($targettype !== '' || $targetkey !== '') {
            $prefix .= ' [' . trim($targettype . ':' . $targetkey, ':') . ']';
        }

        mtrace($prefix . ' ' . $text);
    }
}