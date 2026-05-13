<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Output renderer for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\output;

use plugin_renderer_base;
use renderable;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for UCKK seed tool output components.
 *
 * This renderer is presentation-only. It must not seed data, reset data,
 * validate records, export presets, or alter the seed audit log. Those actions
 * belong to tool_uckkseed\local\seeder and related local classes.
 */
final class renderer extends plugin_renderer_base {
    /**
     * Render a seed execution summary.
     *
     * @param seed_summary $summary Seed summary output object.
     * @return string
     */
    public function render_seed_summary(seed_summary $summary): string {
        $data = $summary->export_for_template($this);

        $data = $this->normalise_common_context($data);
        $data = $this->normalise_counts($data);
        $data = $this->normalise_messages($data);
        $data = $this->normalise_actions($data);
        $data = $this->normalise_preset_cards($data);

        return $this->render_from_template('tool_uckkseed/seed_summary', $data);
    }

    /**
     * Render a validation report.
     *
     * @param validation_report $report Validation report output object.
     * @return string
     */
    public function render_validation_report(validation_report $report): string {
        $data = $report->export_for_template($this);

        $data = $this->normalise_common_context($data);
        $data = $this->normalise_counts($data);
        $data = $this->normalise_messages($data);
        $data = $this->normalise_groups($data);
        $data = $this->normalise_actions($data);

        return $this->render_from_template('tool_uckkseed/validation_report', $data);
    }

    /**
     * Render one preset card.
     *
     * This is useful when a page or Ajax callback wants only one preset card
     * without rendering a complete seed summary.
     *
     * @param array<string, mixed>|stdClass $preset Preset card data.
     * @return string
     */
    public function render_preset_card(array|stdClass $preset): string {
        $data = $this->normalise_preset_card((object)$preset);

        return $this->render_from_template('tool_uckkseed/preset_card', $data);
    }

    /**
     * Render a generic tool_uckkseed renderable through its matching template.
     *
     * Moodle usually dispatches to render_seed_summary() or
     * render_validation_report(). This helper is intentionally narrow and only
     * supports objects that implement templatable.
     *
     * @param renderable&templatable $output Output object.
     * @param string $templatename Template name.
     * @return string
     */
    public function render_templatable(renderable $output, string $templatename): string {
        if (!$output instanceof templatable) {
            throw new \coding_exception('tool_uckkseed renderer expected a templatable renderable.');
        }

        $data = $output->export_for_template($this);
        $data = $this->normalise_common_context($data);

        return $this->render_from_template($templatename, $data);
    }

    /**
     * Normalise common template context keys.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_common_context(stdClass $data): stdClass {
        $data->component = $data->component ?? 'tool_uckkseed';
        $data->uniqid = $data->uniqid ?? html_writer::random_id('tool_uckkseed_');

        $data->heading = $data->heading ?? get_string('pluginname', 'tool_uckkseed');
        $data->summary = $data->summary ?? '';

        $data->action = clean_param((string)($data->action ?? ''), PARAM_ALPHANUMEXT);
        $data->mode = clean_param((string)($data->mode ?? ''), PARAM_ALPHANUMEXT);
        $data->status = clean_param((string)($data->status ?? ''), PARAM_ALPHANUMEXT);

        $data->statuslabel = $data->statuslabel ?? $this->get_status_label($data->status);
        $data->statusclass = $data->statusclass ?? $this->get_status_class($data->status);

        $data->haserrors = !empty($data->haserrors);
        $data->haswarnings = !empty($data->haswarnings);
        $data->ok = isset($data->ok) ? (bool)$data->ok : (!$data->haserrors);

        $data->runid = isset($data->runid) ? (int)$data->runid : 0;
        $data->hasrunid = $data->runid > 0;

        $data->timecreatedlabel = $data->timecreatedlabel ?? '';
        $data->timemodifiedlabel = $data->timemodifiedlabel ?? '';
        $data->durationlabel = $data->durationlabel ?? '';

        $data->hastimecreated = $data->timecreatedlabel !== '';
        $data->hastimemodified = $data->timemodifiedlabel !== '';
        $data->hasduration = $data->durationlabel !== '';

        return $data;
    }

    /**
     * Normalise count rows.
     *
     * Expected input:
     * - counts as stdClass or associative array; or
     * - counts as already prepared list of rows.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_counts(stdClass $data): stdClass {
        $counts = $data->counts ?? [];

        if ($counts instanceof stdClass) {
            $counts = (array)$counts;
        }

        if (!is_array($counts)) {
            $counts = [];
        }

        $prepared = [];

        if ($this->is_assoc($counts)) {
            foreach ($counts as $key => $value) {
                $key = clean_param((string)$key, PARAM_ALPHANUMEXT);

                $prepared[] = (object)[
                    'key' => $key,
                    'label' => $this->get_count_label($key),
                    'value' => (int)$value,
                    'class' => $this->get_count_class($key, (int)$value),
                ];
            }
        } else {
            foreach ($counts as $row) {
                $row = (object)$row;
                $key = clean_param((string)($row->key ?? ''), PARAM_ALPHANUMEXT);
                $value = (int)($row->value ?? 0);

                $prepared[] = (object)[
                    'key' => $key,
                    'label' => $row->label ?? $this->get_count_label($key),
                    'value' => $value,
                    'class' => $row->class ?? $this->get_count_class($key, $value),
                ];
            }
        }

        $data->counts = $prepared;
        $data->hascounts = !empty($prepared);

        return $data;
    }

    /**
     * Normalise message rows.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_messages(stdClass $data): stdClass {
        $messages = $data->messages ?? [];

        if (!is_array($messages)) {
            $messages = [];
        }

        $prepared = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $row = (object)[
                    'severity' => 'info',
                    'message' => $message,
                ];
            } else {
                $row = (object)$message;
            }

            $severity = clean_param((string)($row->severity ?? 'info'), PARAM_ALPHANUMEXT);
            $text = trim((string)($row->message ?? ''));

            if ($text === '') {
                continue;
            }

            $prepared[] = (object)[
                'severity' => $severity,
                'severitylabel' => $row->severitylabel ?? $this->get_severity_label($severity),
                'class' => $row->class ?? $this->get_severity_class($severity),
                'component' => clean_param((string)($row->component ?? 'tool_uckkseed'), PARAM_COMPONENT),
                'preset' => clean_param((string)($row->preset ?? ''), PARAM_ALPHANUMEXT),
                'targettype' => clean_param((string)($row->targettype ?? ''), PARAM_ALPHANUMEXT),
                'targetkey' => s((string)($row->targetkey ?? '')),
                'message' => s($text),
                'haspreset' => !empty($row->preset),
                'hastarget' => !empty($row->targettype) || !empty($row->targetkey),
                'metadata' => $this->normalise_metadata_for_template($row->metadata ?? []),
            ];
        }

        $data->messages = $prepared;
        $data->hasmessages = !empty($prepared);

        return $data;
    }

    /**
     * Normalise grouped validation messages.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_groups(stdClass $data): stdClass {
        $groups = $data->groups ?? [];

        if (!is_array($groups)) {
            $groups = [];
        }

        $prepared = [];

        foreach ($groups as $group) {
            $group = (object)$group;

            $messages = $group->messages ?? [];

            if (!is_array($messages)) {
                $messages = [];
            }

            $messagedata = new stdClass();
            $messagedata->messages = $messages;
            $messagedata = $this->normalise_messages($messagedata);

            $prepared[] = (object)[
                'key' => clean_param((string)($group->key ?? ''), PARAM_ALPHANUMEXT),
                'title' => format_string((string)($group->title ?? $group->label ?? '')),
                'summary' => format_string((string)($group->summary ?? '')),
                'hassummary' => trim((string)($group->summary ?? '')) !== '',
                'status' => clean_param((string)($group->status ?? ''), PARAM_ALPHANUMEXT),
                'statuslabel' => $group->statuslabel ?? $this->get_status_label((string)($group->status ?? '')),
                'statusclass' => $group->statusclass ?? $this->get_status_class((string)($group->status ?? '')),
                'messages' => $messagedata->messages,
                'hasmessages' => $messagedata->hasmessages,
            ];
        }

        $data->groups = $prepared;
        $data->hasgroups = !empty($prepared);

        return $data;
    }

    /**
     * Normalise action rows.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_actions(stdClass $data): stdClass {
        $actions = $data->actions ?? [];

        if (!is_array($actions)) {
            $actions = [];
        }

        $prepared = [];

        foreach ($actions as $action) {
            $action = (object)$action;

            $key = clean_param((string)($action->key ?? $action->action ?? ''), PARAM_ALPHANUMEXT);
            $url = (string)($action->url ?? '');

            if ($key === '' || $url === '') {
                continue;
            }

            $method = strtoupper(clean_param((string)($action->method ?? 'GET'), PARAM_ALPHA));

            if (!in_array($method, ['GET', 'POST'], true)) {
                $method = 'GET';
            }

            $prepared[] = (object)[
                'key' => $key,
                'label' => format_string((string)($action->label ?? $this->get_action_label($key))),
                'url' => $url,
                'method' => $method,
                'ispost' => $method === 'POST',
                'isget' => $method === 'GET',
                'sesskey' => sesskey(),
                'primary' => !empty($action->primary),
                'danger' => !empty($action->danger),
                'secondary' => empty($action->primary) && empty($action->danger),
                'disabled' => !empty($action->disabled),
                'disabledreason' => format_string((string)($action->disabledreason ?? '')),
                'hasdisabledreason' => trim((string)($action->disabledreason ?? '')) !== '',
                'requiresconfirmation' => !empty($action->requiresconfirmation),
                'confirmmessage' => format_string((string)($action->confirmmessage ?? '')),
                'hasconfirmmessage' => trim((string)($action->confirmmessage ?? '')) !== '',
            ];
        }

        $data->actions = $prepared;
        $data->hasactions = !empty($prepared);

        return $data;
    }

    /**
     * Normalise preset cards on a larger page context.
     *
     * @param stdClass $data Template data.
     * @return stdClass
     */
    private function normalise_preset_cards(stdClass $data): stdClass {
        $cards = $data->presetcards ?? [];

        if (!is_array($cards)) {
            $cards = [];
        }

        $prepared = [];

        foreach ($cards as $card) {
            $prepared[] = $this->normalise_preset_card((object)$card);
        }

        $data->presetcards = $prepared;
        $data->haspresetcards = !empty($prepared);

        return $data;
    }

    /**
     * Normalise one preset card.
     *
     * @param stdClass $preset Preset card data.
     * @return stdClass
     */
    private function normalise_preset_card(stdClass $preset): stdClass {
        $presetid = clean_param((string)($preset->preset ?? $preset->key ?? ''), PARAM_ALPHANUMEXT);
        $status = clean_param((string)($preset->status ?? 'pending'), PARAM_ALPHANUMEXT);

        $actionsdata = new stdClass();
        $actionsdata->actions = $preset->actions ?? [];
        $actionsdata = $this->normalise_actions($actionsdata);

        return (object)[
            'preset' => $presetid,
            'presetlabel' => format_string((string)($preset->presetlabel ?? $this->get_preset_label($presetid))),
            'filename' => format_string((string)($preset->filename ?? ($presetid !== '' ? $presetid . '.json' : ''))),
            'description' => format_string((string)($preset->description ?? '')),
            'hasdescription' => trim((string)($preset->description ?? '')) !== '',
            'itemcount' => (int)($preset->itemcount ?? 0),
            'status' => $status,
            'statuslabel' => format_string((string)($preset->statuslabel ?? $this->get_status_label($status))),
            'statusclass' => $preset->statusclass ?? $this->get_status_class($status),
            'enabled' => isset($preset->enabled) ? (bool)$preset->enabled : true,
            'required' => !empty($preset->required),
            'component' => clean_param((string)($preset->component ?? 'tool_uckkseed'), PARAM_COMPONENT),
            'actions' => $actionsdata->actions,
            'hasactions' => $actionsdata->hasactions,
        ];
    }

    /**
     * Convert metadata to template-friendly rows.
     *
     * @param mixed $metadata Metadata.
     * @return array<int, stdClass>
     */
    private function normalise_metadata_for_template(mixed $metadata): array {
        if ($metadata instanceof stdClass) {
            $metadata = (array)$metadata;
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($metadata)) {
            return [];
        }

        $rows = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value) || $value instanceof stdClass) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $rows[] = (object)[
                'key' => s((string)$key),
                'value' => s((string)$value),
            ];
        }

        return $rows;
    }

    /**
     * Return whether an array is associative.
     *
     * @param array $array Array.
     * @return bool
     */
    private function is_assoc(array $array): bool {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Get translated action label.
     *
     * @param string $action Action key.
     * @return string
     */
    private function get_action_label(string $action): string {
        $key = 'action_' . $action;

        if (get_string_manager()->string_exists($key, 'tool_uckkseed')) {
            return get_string($key, 'tool_uckkseed');
        }

        return ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * Get translated count label.
     *
     * @param string $key Count key.
     * @return string
     */
    private function get_count_label(string $key): string {
        if ($key !== '' && get_string_manager()->string_exists($key, 'tool_uckkseed')) {
            return get_string($key, 'tool_uckkseed');
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Get translated preset label.
     *
     * @param string $preset Preset id.
     * @return string
     */
    private function get_preset_label(string $preset): string {
        $key = 'preset_' . $preset;

        if ($preset !== '' && get_string_manager()->string_exists($key, 'tool_uckkseed')) {
            return get_string($key, 'tool_uckkseed');
        }

        return ucfirst(str_replace('_', ' ', $preset));
    }

    /**
     * Get translated status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        $key = 'status_' . $status;

        if ($status !== '' && get_string_manager()->string_exists($key, 'tool_uckkseed')) {
            return get_string($key, 'tool_uckkseed');
        }

        return $status !== '' ? ucfirst(str_replace('_', ' ', $status)) : '';
    }

    /**
     * Get translated severity label.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_severity_label(string $severity): string {
        if ($severity !== '' && get_string_manager()->string_exists($severity, 'tool_uckkseed')) {
            return get_string($severity, 'tool_uckkseed');
        }

        return ucfirst(str_replace('_', ' ', $severity));
    }

    /**
     * Get CSS class for status.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        return match ($status) {
            'completed', 'success', 'ok' => 'status-completed',
            'failed', 'error', 'blocker' => 'status-failed',
            'warning', 'warnings' => 'status-warning',
            'running' => 'status-running',
            'cancelled' => 'status-cancelled',
            'skipped' => 'status-skipped',
            default => 'status-pending',
        };
    }

    /**
     * Get CSS class for severity.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_severity_class(string $severity): string {
        return match ($severity) {
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'error', 'blocker' => 'alert-danger',
            default => 'alert-info',
        };
    }

    /**
     * Get CSS class for count row.
     *
     * @param string $key Count key.
     * @param int $value Count value.
     * @return string
     */
    private function get_count_class(string $key, int $value): string {
        if ($value <= 0) {
            return 'count-empty';
        }

        return match ($key) {
            'failed', 'errors' => 'count-danger',
            'warnings' => 'count-warning',
            'created', 'updated' => 'count-success',
            'skipped' => 'count-muted',
            default => 'count-active',
        };
    }
}
```

No new language keys are strictly required beyond the keys already planned for `tool_uckkseed`, but this renderer expects these template names to exist:

```text id="f8jzfy"
tool_uckkseed/seed_summary
tool_uckkseed/validation_report
tool_uckkseed/preset_card

