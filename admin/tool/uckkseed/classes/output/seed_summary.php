<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Seed summary output object for the UCKK seed tool.
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
 * Renderable summary for one UCKK seed/reset/validate/export run.
 *
 * This class prepares display data only. It does not create courses, reset
 * content, validate presets, export presets, modify roles, assign capabilities,
 * award badges, or alter any UCKK workflow records.
 */
final class seed_summary implements renderable, templatable {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Action: seed. */
    private const ACTION_SEED = 'seed';

    /** Action: reset. */
    private const ACTION_RESET = 'reset';

    /** Action: validate. */
    private const ACTION_VALIDATE = 'validate';

    /** Action: export preset. */
    private const ACTION_EXPORT_PRESET = 'export_preset';

    /** Mode: apply. */
    private const MODE_APPLY = 'apply';

    /** Mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Mode: report. */
    private const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Status: pending. */
    private const STATUS_PENDING = 'pending';

    /** Status: running. */
    private const STATUS_RUNNING = 'running';

    /** Status: completed. */
    private const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    private const STATUS_FAILED = 'failed';

    /** Status: cancelled. */
    private const STATUS_CANCELLED = 'cancelled';

    /** Status: skipped. */
    private const STATUS_SKIPPED = 'skipped';

    /** Status: warning. */
    private const STATUS_WARNING = 'warning';

    /**
     * Result payload.
     *
     * @var array<string, mixed>
     */
    private array $result;

    /**
     * Run metadata.
     *
     * @var array<string, mixed>
     */
    private array $run;

    /**
     * Preset card rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $presetcards;

    /**
     * Action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Output options.
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param validation_result|array<string, mixed>|stdClass|null $result Result data.
     * @param array<string, mixed>|stdClass $run Run metadata.
     * @param array<int, array<string, mixed>|stdClass> $presetcards Preset card rows.
     * @param array<int, array<string, mixed>|stdClass> $actions Action rows.
     * @param array<string, mixed> $options Output options.
     */
    public function __construct(
        validation_result|array|stdClass|null $result = null,
        array|stdClass $run = [],
        array $presetcards = [],
        array $actions = [],
        array $options = []
    ) {
        $this->result = $this->normalise_result($result);
        $this->run = $this->normalise_assoc($run);
        $this->presetcards = array_map([$this, 'normalise_preset_card'], $presetcards);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
        $this->options = $options;
    }

    /**
     * Export data for the seed_summary Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $action = $this->normalise_action_key((string)($this->run['action'] ?? $this->result['action'] ?? self::ACTION_SEED));
        $mode = $this->normalise_mode((string)($this->run['mode'] ?? $this->result['mode'] ?? self::MODE_APPLY));
        $status = $this->normalise_status((string)($this->run['status'] ?? $this->result['status'] ?? self::STATUS_PENDING));

        $data = new stdClass();
        $data->uniqid = $this->get_uniqid();
        $data->component = self::COMPONENT;
        $data->heading = (string)($this->options['heading'] ?? get_string('seedsummary', self::COMPONENT));

        $data->action = $action;
        $data->actionlabel = $this->get_action_label($action);

        $data->mode = $mode;
        $data->modelabel = $this->get_mode_label($mode);
        $data->isdryrun = $mode === self::MODE_DRY_RUN;
        $data->isreport = $mode === self::MODE_REPORT;
        $data->isrollbackplan = $mode === self::MODE_ROLLBACK_PLAN;
        $data->isapply = $mode === self::MODE_APPLY;

        $data->status = $status;
        $data->statuslabel = $this->get_status_label($status);
        $data->statusclass = 'status-' . $this->css_key($status);
        $data->ok = $this->get_bool('ok', $status !== self::STATUS_FAILED);
        $data->haserrors = $this->get_bool('haserrors', false);
        $data->haswarnings = $this->get_bool('haswarnings', false);

        $data->runid = (int)($this->run['runid'] ?? $this->run['id'] ?? $this->result['runid'] ?? 0);
        $data->hasrunid = $data->runid > 0;

        $data->timecreated = (int)($this->run['timecreated'] ?? $this->result['timecreated'] ?? 0);
        $data->timemodified = (int)($this->run['timemodified'] ?? $this->result['timemodified'] ?? 0);
        $data->timecreatedlabel = $data->timecreated > 0 ? userdate($data->timecreated) : '';
        $data->timemodifiedlabel = $data->timemodified > 0 ? userdate($data->timemodified) : '';
        $data->hastimecreated = $data->timecreated > 0;
        $data->hastimemodified = $data->timemodified > 0;
        $data->durationlabel = $this->get_duration_label($data->timecreated, $data->timemodified);
        $data->hasduration = $data->durationlabel !== '';

        $data->summary = trim((string)($this->result['summary'] ?? $this->run['summary'] ?? ''));
        $data->hassummary = $data->summary !== '';

        $data->counts = $this->build_count_rows($this->result['counts'] ?? []);
        $data->hascounts = !empty($data->counts);

        $data->messages = $this->build_message_rows($this->result['messages'] ?? []);
        $data->hasmessages = !empty($data->messages);

        $data->presetcards = $this->build_preset_card_rows();
        $data->haspresetcards = !empty($data->presetcards);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->dryrunnotice = $data->isdryrun ? get_string('dryrunnotice', self::COMPONENT) : '';
        $data->hasdryrunnotice = $data->dryrunnotice !== '';

        $data->rollbackplannotice = $data->isrollbackplan ? get_string('rollbackplannotice', self::COMPONENT) : '';
        $data->hasrollbackplannotice = $data->rollbackplannotice !== '';

        $data->metadata = $this->normalise_metadata($this->result['metadata'] ?? []);
        $data->hasmetadata = !empty((array)$data->metadata);

        return $data;
    }

    /**
     * Normalise result data.
     *
     * @param validation_result|array<string, mixed>|stdClass|null $result Result data.
     * @return array<string, mixed>
     */
    private function normalise_result(validation_result|array|stdClass|null $result): array {
        if ($result === null) {
            return [
                'status' => self::STATUS_PENDING,
                'ok' => true,
                'haserrors' => false,
                'haswarnings' => false,
                'summary' => '',
                'counts' => [],
                'messages' => [],
                'metadata' => [],
            ];
        }

        if ($result instanceof validation_result) {
            if (method_exists($result, 'to_array')) {
                $data = $result->to_array();

                if (is_array($data)) {
                    return $data;
                }
            }

            if (method_exists($result, 'export')) {
                $data = $result->export();

                return is_array($data) ? $data : (array)$data;
            }

            if ($result instanceof \JsonSerializable) {
                $data = $result->jsonSerialize();

                return is_array($data) ? $data : (array)$data;
            }

            return get_object_vars($result);
        }

        return $this->normalise_assoc($result);
    }

    /**
     * Normalise array/stdClass into associative array.
     *
     * @param array<string, mixed>|stdClass $value Value.
     * @return array<string, mixed>
     */
    private function normalise_assoc(array|stdClass $value): array {
        if ($value instanceof stdClass) {
            return (array)$value;
        }

        return $value;
    }

    /**
     * Normalise one preset card row.
     *
     * @param array<string, mixed>|stdClass $card Raw row.
     * @return array<string, mixed>
     */
    private function normalise_preset_card(array|stdClass $card): array {
        $row = $this->normalise_assoc($card);
        $preset = clean_param((string)($row['preset'] ?? $row['key'] ?? ''), PARAM_ALPHANUMEXT);
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_PENDING));

        return [
            'preset' => $preset,
            'presetlabel' => format_string((string)($row['presetlabel'] ?? $this->get_preset_label($preset))),
            'filename' => format_string((string)($row['filename'] ?? ($preset !== '' ? $preset . '.json' : ''))),
            'description' => format_string((string)($row['description'] ?? '')),
            'itemcount' => max(0, (int)($row['itemcount'] ?? $row['count'] ?? 0)),
            'status' => $status,
            'statuslabel' => format_string((string)($row['statuslabel'] ?? $this->get_status_label($status))),
            'statusclass' => 'status-' . $this->css_key($status),
            'enabled' => array_key_exists('enabled', $row) ? !empty($row['enabled']) : true,
            'required' => !empty($row['required']),
            'component' => format_string((string)($row['component'] ?? self::COMPONENT)),
            'actions' => $this->normalise_action_rows($row['actions'] ?? []),
        ];
    }

    /**
     * Normalise one action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action.
     * @return array<string, mixed>
     */
    private function normalise_action(array|stdClass $action): array {
        $row = $this->normalise_assoc($action);
        $key = clean_param((string)($row['key'] ?? $row['action'] ?? ''), PARAM_ALPHANUMEXT);
        $method = strtoupper(clean_param((string)($row['method'] ?? 'get'), PARAM_ALPHA));

        if (!in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        return [
            'key' => $key,
            'label' => format_string((string)($row['label'] ?? $this->get_action_button_label($key))),
            'url' => $this->normalise_url($row['url'] ?? null),
            'method' => $method,
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
            'params' => is_array($row['params'] ?? null) ? $row['params'] : [],
        ];
    }

    /**
     * Normalise a list of action rows.
     *
     * @param mixed $actions Action rows.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_action_rows(mixed $actions): array {
        if (!is_array($actions)) {
            return [];
        }

        return array_map([$this, 'normalise_action'], $actions);
    }

    /**
     * Build count rows.
     *
     * @param mixed $counts Raw counts.
     * @return array<int, stdClass>
     */
    private function build_count_rows(mixed $counts): array {
        if ($counts instanceof stdClass) {
            $counts = (array)$counts;
        }

        if (!is_array($counts)) {
            return [];
        }

        $order = [
            'created',
            'updated',
            'skipped',
            'failed',
            'warnings',
            'errors',
        ];

        $rows = [];

        foreach ($order as $key) {
            if (!array_key_exists($key, $counts)) {
                continue;
            }

            $value = (int)$counts[$key];

            $rows[] = (object)[
                'key' => $key,
                'label' => $this->get_count_label($key),
                'value' => $value,
                'class' => 'count-' . $this->css_key($key),
                'hasvalue' => $value > 0,
            ];
        }

        foreach ($counts as $key => $value) {
            if (in_array((string)$key, $order, true)) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $rows[] = (object)[
                'key' => clean_param((string)$key, PARAM_ALPHANUMEXT),
                'label' => ucfirst(str_replace('_', ' ', (string)$key)),
                'value' => (int)$value,
                'class' => 'count-' . $this->css_key((string)$key),
                'hasvalue' => (int)$value > 0,
            ];
        }

        return $rows;
    }

    /**
     * Build message rows.
     *
     * @param mixed $messages Raw messages.
     * @return array<int, stdClass>
     */
    private function build_message_rows(mixed $messages): array {
        if (!is_array($messages)) {
            return [];
        }

        $rows = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $row = [
                    'severity' => 'info',
                    'component' => self::COMPONENT,
                    'preset' => '',
                    'targettype' => '',
                    'targetkey' => '',
                    'message' => $message,
                    'metadata' => [],
                ];
            } else {
                $row = $this->normalise_assoc((array)$message);
            }

            $text = trim((string)($row['message'] ?? ''));

            if ($text === '') {
                continue;
            }

            $severity = $this->normalise_severity((string)($row['severity'] ?? 'info'));

            $rows[] = (object)[
                'severity' => $severity,
                'severitylabel' => $this->get_severity_label($severity),
                'severityclass' => 'severity-' . $this->css_key($severity),
                'alertclass' => 'alert-' . $this->get_alert_class($severity),
                'component' => clean_param((string)($row['component'] ?? self::COMPONENT), PARAM_COMPONENT),
                'preset' => clean_param((string)($row['preset'] ?? ''), PARAM_ALPHANUMEXT),
                'targettype' => clean_param((string)($row['targettype'] ?? ''), PARAM_ALPHANUMEXT),
                'targetkey' => format_string((string)($row['targetkey'] ?? '')),
                'message' => format_string($text),
                'metadata' => $this->normalise_metadata($row['metadata'] ?? []),
                'haspreset' => !empty($row['preset']),
                'hastarget' => !empty($row['targetkey']),
            ];
        }

        return $rows;
    }

    /**
     * Build preset card rows.
     *
     * @return array<int, stdClass>
     */
    private function build_preset_card_rows(): array {
        $rows = [];

        foreach ($this->presetcards as $card) {
            if ($card['preset'] === '') {
                continue;
            }

            $actions = $this->build_action_rows_from_array($card['actions']);

            $rows[] = (object)[
                'preset' => $card['preset'],
                'presetlabel' => $card['presetlabel'],
                'filename' => $card['filename'],
                'description' => $card['description'],
                'hasdescription' => $card['description'] !== '',
                'itemcount' => $card['itemcount'],
                'status' => $card['status'],
                'statuslabel' => $card['statuslabel'],
                'statusclass' => $card['statusclass'],
                'enabled' => $card['enabled'],
                'required' => $card['required'],
                'component' => $card['component'],
                'actions' => $actions,
                'hasactions' => !empty($actions),
            ];
        }

        return $rows;
    }

    /**
     * Build top-level action rows.
     *
     * @return array<int, stdClass>
     */
    private function build_action_rows(): array {
        return $this->build_action_rows_from_array($this->actions);
    }

    /**
     * Build action rows from normalised array data.
     *
     * @param array<int, array<string, mixed>> $actions Action rows.
     * @return array<int, stdClass>
     */
    private function build_action_rows_from_array(array $actions): array {
        $rows = [];

        foreach ($actions as $action) {
            if ($action['key'] === '' || $action['url'] === '') {
                continue;
            }

            $rows[] = (object)[
                'key' => $action['key'],
                'label' => $action['label'],
                'url' => $action['url'],
                'method' => $action['method'],
                'isget' => $action['method'] === 'GET',
                'ispost' => $action['method'] === 'POST',
                'primary' => $action['primary'],
                'danger' => $action['danger'],
                'secondary' => !$action['primary'] && !$action['danger'],
                'disabled' => $action['disabled'],
                'disabledreason' => $action['disabledreason'],
                'hasdisabledreason' => $action['disabledreason'] !== '',
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hasconfirmmessage' => $action['confirmmessage'] !== '',
                'params' => $this->build_param_rows($action['params']),
                'hasparams' => !empty($action['params']),
            ];
        }

        return $rows;
    }

    /**
     * Build hidden parameter rows for POST forms.
     *
     * @param array<string, mixed> $params Parameters.
     * @return array<int, stdClass>
     */
    private function build_param_rows(array $params): array {
        $rows = [];

        foreach ($params as $name => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $rows[] = (object)[
                'name' => clean_param((string)$name, PARAM_ALPHANUMEXT),
                'value' => s((string)$value),
            ];
        }

        return $rows;
    }

    /**
     * Get boolean value from result.
     *
     * @param string $key Result key.
     * @param bool $default Default.
     * @return bool
     */
    private function get_bool(string $key, bool $default): bool {
        if (!array_key_exists($key, $this->result)) {
            return $default;
        }

        return (bool)$this->result[$key];
    }

    /**
     * Get a stable unique id for the rendered region.
     *
     * @return string
     */
    private function get_uniqid(): string {
        $provided = (string)($this->options['uniqid'] ?? $this->run['uniqid'] ?? '');

        if ($provided !== '') {
            return clean_param($provided, PARAM_ALPHANUMEXT);
        }

        $runid = (int)($this->run['runid'] ?? $this->run['id'] ?? 0);

        if ($runid > 0) {
            return 'tool-uckkseed-summary-' . $runid;
        }

        return 'tool-uckkseed-summary-' . uniqid();
    }

    /**
     * Return human duration label.
     *
     * @param int $start Start timestamp.
     * @param int $end End timestamp.
     * @return string
     */
    private function get_duration_label(int $start, int $end): string {
        if ($start <= 0 || $end <= 0 || $end < $start) {
            return '';
        }

        $seconds = $end - $start;

        if ($seconds < MINSECS) {
            return get_string('numseconds', 'moodle', $seconds);
        }

        return format_time($seconds);
    }

    /**
     * Normalise action key.
     *
     * @param string $action Raw action.
     * @return string
     */
    private function normalise_action_key(string $action): string {
        $action = clean_param($action, PARAM_ALPHANUMEXT);

        return in_array($action, [
            self::ACTION_SEED,
            self::ACTION_RESET,
            self::ACTION_VALIDATE,
            self::ACTION_EXPORT_PRESET,
        ], true) ? $action : self::ACTION_SEED;
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
        ], true) ? $mode : self::MODE_APPLY;
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
            'info',
            'success',
            'warning',
            'error',
            'blocker',
        ], true) ? $severity : 'info';
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Metadata.
     * @return stdClass
     */
    private function normalise_metadata(mixed $metadata): stdClass {
        if ($metadata instanceof stdClass) {
            return $metadata;
        }

        if (is_array($metadata)) {
            return (object)$metadata;
        }

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return (object)$decoded;
            }
        }

        return new stdClass();
    }

    /**
     * Normalise URL.
     *
     * @param mixed $url URL.
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

    /**
     * CSS-safe key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function css_key(string $value): string {
        return str_replace('_', '-', clean_param($value, PARAM_ALPHANUMEXT));
    }

    /**
     * Get action label.
     *
     * @param string $action Action.
     * @return string
     */
    private function get_action_label(string $action): string {
        return $this->get_component_string('action_' . $action, ucfirst(str_replace('_', ' ', $action)));
    }

    /**
     * Get mode label.
     *
     * @param string $mode Mode.
     * @return string
     */
    private function get_mode_label(string $mode): string {
        return $this->get_component_string('mode_' . $mode, ucfirst(str_replace('_', ' ', $mode)));
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return $this->get_component_string('status_' . $status, ucfirst(str_replace('_', ' ', $status)));
    }

    /**
     * Get count label.
     *
     * @param string $key Count key.
     * @return string
     */
    private function get_count_label(string $key): string {
        return $this->get_component_string($key, ucfirst(str_replace('_', ' ', $key)));
    }

    /**
     * Get preset label.
     *
     * @param string $preset Preset id.
     * @return string
     */
    private function get_preset_label(string $preset): string {
        if ($preset === '') {
            return '';
        }

        return $this->get_component_string('preset_' . $preset, ucfirst(str_replace('_', ' ', $preset)));
    }

    /**
     * Get severity label.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_severity_label(string $severity): string {
        return $this->get_component_string('severity_' . $severity, ucfirst(str_replace('_', ' ', $severity)));
    }

    /**
     * Get action button label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_button_label(string $key): string {
        if ($key === '') {
            return get_string('continue');
        }

        return $this->get_component_string('action_' . $key, ucfirst(str_replace('_', ' ', $key)));
    }

    /**
     * Map severity to Bootstrap alert class suffix.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_alert_class(string $severity): string {
        return match ($severity) {
            'success' => 'success',
            'warning' => 'warning',
            'error', 'blocker' => 'danger',
            default => 'info',
        };
    }

    /**
     * Get plugin string with fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function get_component_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, self::COMPONENT)) {
            return get_string($key, self::COMPONENT);
        }

        return $fallback;
    }
}
