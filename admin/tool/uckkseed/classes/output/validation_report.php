<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Validation report output object for tool_uckkseed.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use tool_uckkseed\local\validation_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable validation report for UCKK seed operations.
 *
 * This class prepares template context only. It does not validate presets,
 * create categories, seed courses, assign capabilities, award badges, reset
 * records, or export preset files.
 */
final class validation_report implements renderable, templatable {
    /** Component name. */
    public const COMPONENT = 'tool_uckkseed';

    /** Status: pending. */
    public const STATUS_PENDING = 'pending';

    /** Status: running. */
    public const STATUS_RUNNING = 'running';

    /** Status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Status: skipped. */
    public const STATUS_SKIPPED = 'skipped';

    /** Status: warning. */
    public const STATUS_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /** Action: seed. */
    public const ACTION_SEED = 'seed';

    /** Action: reset. */
    public const ACTION_RESET = 'reset';

    /** Action: validate. */
    public const ACTION_VALIDATE = 'validate';

    /** Action: export preset. */
    public const ACTION_EXPORT_PRESET = 'export_preset';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /**
     * Unique DOM id.
     *
     * @var string
     */
    private string $uniqid;

    /**
     * Normalised result data.
     *
     * @var array<string, mixed>
     */
    private array $result;

    /**
     * Runtime options.
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param validation_result|array<string, mixed>|stdClass|null $result Validation result.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct(validation_result|array|stdClass|null $result = null, array $options = []) {
        $this->uniqid = clean_param((string)($options['uniqid'] ?? uniqid('tool_uckkseed_validation_', false)), PARAM_ALPHANUMEXT);
        $this->result = $this->normalise_result($result);
        $this->options = $options;
    }

    /**
     * Export context for validation_report.mustache.
     *
     * Canonical template keys:
     * - uniqid
     * - component
     * - heading
     * - status
     * - statuslabel
     * - statusclass
     * - ok
     * - haserrors
     * - haswarnings
     * - summary
     * - messages
     * - hasmessages
     * - groups
     * - hasgroups
     * - counts
     * - hascounts
     * - actions
     * - hasactions
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $status = $this->normalise_status((string)($this->result['status'] ?? self::STATUS_PENDING));
        $messages = $this->normalise_messages($this->result['messages'] ?? []);
        $counts = $this->normalise_counts($this->result['counts'] ?? []);

        $haserrors = $this->resolve_has_errors($messages, $counts);
        $haswarnings = $this->resolve_has_warnings($messages, $counts);
        $ok = !$haserrors && (bool)($this->result['ok'] ?? true);

        $data = new stdClass();
        $data->uniqid = $this->uniqid;
        $data->component = self::COMPONENT;
        $data->heading = (string)($this->options['heading'] ?? get_string('validationreport', 'tool_uckkseed'));

        $data->action = $this->normalise_action_name((string)($this->result['action'] ?? $this->options['action'] ?? self::ACTION_VALIDATE));
        $data->actionlabel = $this->get_action_label($data->action);

        $data->mode = $this->normalise_mode((string)($this->result['mode'] ?? $this->options['mode'] ?? self::MODE_DRY_RUN));
        $data->modelabel = $this->get_mode_label($data->mode);

        $data->runid = max(0, (int)($this->result['runid'] ?? $this->options['runid'] ?? 0));
        $data->hasrunid = $data->runid > 0;

        $data->status = $status;
        $data->statuslabel = $this->get_status_label($status);
        $data->statusclass = 'status-' . str_replace('_', '-', $status);

        $data->ok = $ok;
        $data->haserrors = $haserrors;
        $data->haswarnings = $haswarnings;
        $data->hasissues = $haserrors || $haswarnings;

        $data->summary = trim((string)($this->result['summary'] ?? ''));
        $data->hassummary = $data->summary !== '';

        $data->messages = $messages;
        $data->hasmessages = !empty($messages);

        $data->groups = $this->build_groups($messages);
        $data->hasgroups = !empty($data->groups);

        $data->counts = $this->build_count_rows($counts);
        $data->countmap = (object)$counts;
        $data->hascounts = !empty($data->counts);

        $data->created = (int)($counts['created'] ?? 0);
        $data->updated = (int)($counts['updated'] ?? 0);
        $data->skipped = (int)($counts['skipped'] ?? 0);
        $data->failed = (int)($counts['failed'] ?? 0);
        $data->warnings = (int)($counts['warnings'] ?? 0);
        $data->errors = (int)($counts['errors'] ?? 0);

        $data->timecreated = max(0, (int)($this->result['timecreated'] ?? $this->options['timecreated'] ?? 0));
        $data->timemodified = max(0, (int)($this->result['timemodified'] ?? $this->options['timemodified'] ?? 0));
        $data->timecreatedlabel = $data->timecreated > 0 ? userdate($data->timecreated) : '';
        $data->timemodifiedlabel = $data->timemodified > 0 ? userdate($data->timemodified) : '';
        $data->hastimecreated = $data->timecreated > 0;
        $data->hastimemodified = $data->timemodified > 0;

        $data->duration = max(0, (int)($this->result['duration'] ?? $this->options['duration'] ?? 0));
        $data->durationlabel = $data->duration > 0 ? format_time($data->duration) : '';
        $data->hasduration = $data->duration > 0;

        $data->metadata = $this->normalise_metadata($this->result['metadata'] ?? $this->options['metadata'] ?? []);
        $data->hasmetadata = !empty($data->metadata);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->isapplymode = $data->mode === self::MODE_APPLY;
        $data->isdryrun = $data->mode === self::MODE_DRY_RUN;
        $data->isreportmode = $data->mode === self::MODE_REPORT;
        $data->isrollbackplan = $data->mode === self::MODE_ROLLBACK_PLAN;

        $data->notice = $this->get_notice($data->mode, $ok, $haserrors, $haswarnings);
        $data->hasnotice = $data->notice !== '';

        return $data;
    }

    /**
     * Normalise a validation result object/array.
     *
     * @param validation_result|array<string, mixed>|stdClass|null $result Result.
     * @return array<string, mixed>
     */
    private function normalise_result(validation_result|array|stdClass|null $result): array {
        if ($result === null) {
            return [
                'status' => self::STATUS_PENDING,
                'ok' => false,
                'haserrors' => false,
                'haswarnings' => false,
                'summary' => '',
                'counts' => [],
                'messages' => [],
                'metadata' => [],
            ];
        }

        if ($result instanceof validation_result && method_exists($result, 'to_array')) {
            return $result->to_array();
        }

        if (is_object($result) && method_exists($result, 'to_export')) {
            return (array)$result->to_export();
        }

        if ($result instanceof stdClass) {
            return get_object_vars($result);
        }

        return $result;
    }

    /**
     * Normalise messages.
     *
     * @param mixed $messages Raw messages.
     * @return array<int, stdClass>
     */
    private function normalise_messages(mixed $messages): array {
        if (!is_array($messages)) {
            return [];
        }

        $rows = [];

        foreach ($messages as $index => $message) {
            $row = is_object($message) ? get_object_vars($message) : (array)$message;

            $text = trim((string)($row['message'] ?? $row['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $severity = $this->normalise_severity((string)($row['severity'] ?? self::SEVERITY_INFO));
            $preset = clean_param((string)($row['preset'] ?? ''), PARAM_ALPHANUMEXT);
            $component = clean_param((string)($row['component'] ?? self::COMPONENT), PARAM_COMPONENT);
            $targettype = clean_param((string)($row['targettype'] ?? ''), PARAM_ALPHANUMEXT);
            $targetkey = clean_param((string)($row['targetkey'] ?? ''), PARAM_TEXT);

            $rows[] = (object)[
                'index' => $index,
                'severity' => $severity,
                'severitylabel' => $this->get_severity_label($severity),
                'severityclass' => 'severity-' . str_replace('_', '-', $severity),
                'alertclass' => 'alert-' . $this->get_alert_class($severity),
                'component' => $component,
                'hascomponent' => $component !== '',
                'preset' => $preset,
                'presetlabel' => $preset !== '' ? $this->get_preset_label($preset) : '',
                'haspreset' => $preset !== '',
                'targettype' => $targettype,
                'targettypelabel' => $targettype !== '' ? ucfirst(str_replace('_', ' ', $targettype)) : '',
                'hastargettype' => $targettype !== '',
                'targetkey' => $targetkey,
                'hastargetkey' => $targetkey !== '',
                'message' => $text,
                'metadata' => $this->normalise_metadata($row['metadata'] ?? []),
                'hasmetadata' => !empty($row['metadata']),
                'isinfo' => $severity === self::SEVERITY_INFO,
                'issuccess' => $severity === self::SEVERITY_SUCCESS,
                'iswarning' => $severity === self::SEVERITY_WARNING,
                'iserror' => $severity === self::SEVERITY_ERROR,
                'isblocker' => $severity === self::SEVERITY_BLOCKER,
            ];
        }

        return $rows;
    }

    /**
     * Build grouped report rows.
     *
     * @param array<int, stdClass> $messages Normalised messages.
     * @return array<int, stdClass>
     */
    private function build_groups(array $messages): array {
        $groups = [];

        foreach ($messages as $message) {
            $groupkey = $message->preset !== ''
                ? 'preset:' . $message->preset
                : 'component:' . $message->component;

            if (!isset($groups[$groupkey])) {
                $groups[$groupkey] = [
                    'key' => $groupkey,
                    'preset' => $message->preset,
                    'presetlabel' => $message->presetlabel,
                    'component' => $message->component,
                    'heading' => $message->presetlabel !== ''
                        ? $message->presetlabel
                        : $message->component,
                    'messages' => [],
                    'counts' => [
                        self::SEVERITY_INFO => 0,
                        self::SEVERITY_SUCCESS => 0,
                        self::SEVERITY_WARNING => 0,
                        self::SEVERITY_ERROR => 0,
                        self::SEVERITY_BLOCKER => 0,
                    ],
                ];
            }

            $groups[$groupkey]['messages'][] = $message;
            $groups[$groupkey]['counts'][$message->severity]++;
        }

        $rows = [];

        foreach ($groups as $group) {
            $haserrors = $group['counts'][self::SEVERITY_ERROR] > 0 || $group['counts'][self::SEVERITY_BLOCKER] > 0;
            $haswarnings = $group['counts'][self::SEVERITY_WARNING] > 0;
            $severity = $haserrors
                ? self::SEVERITY_ERROR
                : ($haswarnings ? self::SEVERITY_WARNING : self::SEVERITY_SUCCESS);

            $rows[] = (object)[
                'key' => $group['key'],
                'heading' => $group['heading'],
                'preset' => $group['preset'],
                'presetlabel' => $group['presetlabel'],
                'haspreset' => $group['preset'] !== '',
                'component' => $group['component'],
                'messages' => $group['messages'],
                'hasmessages' => !empty($group['messages']),
                'messagecount' => count($group['messages']),
                'severity' => $severity,
                'severitylabel' => $this->get_severity_label($severity),
                'severityclass' => 'severity-' . str_replace('_', '-', $severity),
                'infocount' => $group['counts'][self::SEVERITY_INFO],
                'successcount' => $group['counts'][self::SEVERITY_SUCCESS],
                'warningcount' => $group['counts'][self::SEVERITY_WARNING],
                'errorcount' => $group['counts'][self::SEVERITY_ERROR],
                'blockercount' => $group['counts'][self::SEVERITY_BLOCKER],
                'haserrors' => $haserrors,
                'haswarnings' => $haswarnings,
            ];
        }

        return array_values($rows);
    }

    /**
     * Normalise counts.
     *
     * @param mixed $counts Raw counts.
     * @return array<string, int>
     */
    private function normalise_counts(mixed $counts): array {
        $defaults = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];

        if (!is_array($counts) && !$counts instanceof stdClass) {
            return $defaults;
        }

        $counts = (array)$counts;

        foreach ($defaults as $key => $default) {
            $defaults[$key] = max(0, (int)($counts[$key] ?? $default));
        }

        return $defaults;
    }

    /**
     * Build count rows for Mustache.
     *
     * @param array<string, int> $counts Counts.
     * @return array<int, stdClass>
     */
    private function build_count_rows(array $counts): array {
        $rows = [];

        foreach ([
            'created',
            'updated',
            'skipped',
            'failed',
            'warnings',
            'errors',
        ] as $key) {
            $value = (int)($counts[$key] ?? 0);

            $rows[] = (object)[
                'key' => $key,
                'label' => $this->get_count_label($key),
                'value' => $value,
                'hasvalue' => $value > 0,
                'class' => 'count-' . $key,
            ];
        }

        return $rows;
    }

    /**
     * Build action rows.
     *
     * @return array<int, stdClass>
     */
    private function build_action_rows(): array {
        $actions = $this->options['actions'] ?? [];

        if (!is_array($actions)) {
            return [];
        }

        $rows = [];

        foreach ($actions as $action) {
            $row = is_object($action) ? get_object_vars($action) : (array)$action;
            $key = clean_param((string)($row['key'] ?? $row['action'] ?? ''), PARAM_ALPHANUMEXT);
            $url = $this->normalise_url($row['url'] ?? null);

            if ($key === '' || $url === '') {
                continue;
            }

            $method = strtoupper(clean_param((string)($row['method'] ?? 'get'), PARAM_ALPHA));
            if (!in_array($method, ['GET', 'POST'], true)) {
                $method = 'GET';
            }

            $rows[] = (object)[
                'key' => $key,
                'label' => format_string((string)($row['label'] ?? $this->get_action_label($key))),
                'url' => $url,
                'method' => $method,
                'ispost' => $method === 'POST',
                'isget' => $method === 'GET',
                'primary' => !empty($row['primary']),
                'danger' => !empty($row['danger']),
                'secondary' => empty($row['primary']) && empty($row['danger']),
                'disabled' => !empty($row['disabled']),
                'requiresconfirmation' => !empty($row['requiresconfirmation']),
                'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
                'hasconfirmmessage' => trim((string)($row['confirmmessage'] ?? '')) !== '',
            ];
        }

        return $rows;
    }

    /**
     * Resolve whether result has errors.
     *
     * @param array<int, stdClass> $messages Messages.
     * @param array<string, int> $counts Counts.
     * @return bool
     */
    private function resolve_has_errors(array $messages, array $counts): bool {
        if (!empty($this->result['haserrors']) || (int)($counts['errors'] ?? 0) > 0 || (int)($counts['failed'] ?? 0) > 0) {
            return true;
        }

        foreach ($messages as $message) {
            if (in_array($message->severity, [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve whether result has warnings.
     *
     * @param array<int, stdClass> $messages Messages.
     * @param array<string, int> $counts Counts.
     * @return bool
     */
    private function resolve_has_warnings(array $messages, array $counts): bool {
        if (!empty($this->result['haswarnings']) || (int)($counts['warnings'] ?? 0) > 0) {
            return true;
        }

        foreach ($messages as $message) {
            if ($message->severity === self::SEVERITY_WARNING) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise metadata to template rows.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<int, stdClass>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata instanceof stdClass) {
            $metadata = get_object_vars($metadata);
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        if (!is_array($metadata)) {
            return [];
        }

        $rows = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $rows[] = (object)[
                'key' => clean_param((string)$key, PARAM_TEXT),
                'value' => $value === null ? '' : clean_param((string)$value, PARAM_TEXT),
            ];
        }

        return $rows;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        return in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_SKIPPED,
            self::STATUS_WARNING,
        ], true) ? $status : self::STATUS_PENDING;
    }

    /**
     * Normalise severity.
     *
     * @param string $severity Raw severity.
     * @return string
     */
    private function normalise_severity(string $severity): string {
        $severity = clean_param($severity, PARAM_ALPHANUMEXT);

        return in_array($severity, [
            self::SEVERITY_INFO,
            self::SEVERITY_SUCCESS,
            self::SEVERITY_WARNING,
            self::SEVERITY_ERROR,
            self::SEVERITY_BLOCKER,
        ], true) ? $severity : self::SEVERITY_INFO;
    }

    /**
     * Normalise action name.
     *
     * @param string $action Raw action.
     * @return string
     */
    private function normalise_action_name(string $action): string {
        $action = clean_param($action, PARAM_ALPHANUMEXT);

        return in_array($action, [
            self::ACTION_SEED,
            self::ACTION_RESET,
            self::ACTION_VALIDATE,
            self::ACTION_EXPORT_PRESET,
        ], true) ? $action : self::ACTION_VALIDATE;
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        return in_array($mode, [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ], true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return $this->component_string('status_' . $status, ucfirst(str_replace('_', ' ', $status)));
    }

    /**
     * Get severity label.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_severity_label(string $severity): string {
        return $this->component_string('severity_' . $severity, ucfirst(str_replace('_', ' ', $severity)));
    }

    /**
     * Get action label.
     *
     * @param string $action Action.
     * @return string
     */
    private function get_action_label(string $action): string {
        return $this->component_string('action_' . $action, ucfirst(str_replace('_', ' ', $action)));
    }

    /**
     * Get mode label.
     *
     * @param string $mode Mode.
     * @return string
     */
    private function get_mode_label(string $mode): string {
        return $this->component_string('mode_' . $mode, ucfirst(str_replace('_', ' ', $mode)));
    }

    /**
     * Get preset label.
     *
     * @param string $preset Preset id.
     * @return string
     */
    private function get_preset_label(string $preset): string {
        return $this->component_string('preset_' . $preset, ucfirst(str_replace('_', ' ', $preset)));
    }

    /**
     * Get count label.
     *
     * @param string $key Count key.
     * @return string
     */
    private function get_count_label(string $key): string {
        return $this->component_string($key, ucfirst(str_replace('_', ' ', $key)));
    }

    /**
     * Get component string with fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function component_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, self::COMPONENT)) {
            return get_string($key, self::COMPONENT);
        }

        return $fallback;
    }

    /**
     * Map severity to Bootstrap alert class suffix.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_alert_class(string $severity): string {
        return match ($severity) {
            self::SEVERITY_SUCCESS => 'success',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_ERROR,
            self::SEVERITY_BLOCKER => 'danger',
            default => 'info',
        };
    }

    /**
     * Get mode/result notice.
     *
     * @param string $mode Mode.
     * @param bool $ok Whether report is OK.
     * @param bool $haserrors Whether report has errors.
     * @param bool $haswarnings Whether report has warnings.
     * @return string
     */
    private function get_notice(string $mode, bool $ok, bool $haserrors, bool $haswarnings): string {
        if ($haserrors) {
            return get_string('validationreport_haserrors', 'tool_uckkseed');
        }

        if ($haswarnings) {
            return get_string('validationreport_haswarnings', 'tool_uckkseed');
        }

        if ($mode === self::MODE_DRY_RUN) {
            return get_string('dryrunnotice', 'tool_uckkseed');
        }

        if ($mode === self::MODE_ROLLBACK_PLAN) {
            return get_string('rollbackplannotice', 'tool_uckkseed');
        }

        if ($ok) {
            return get_string('validationreport_ok', 'tool_uckkseed');
        }

        return '';
    }

    /**
     * Normalise URL.
     *
     * @param mixed $url Raw URL.
     * @return string
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return (new moodle_url($url))->out(false);
        }

        return '';
    }
}
