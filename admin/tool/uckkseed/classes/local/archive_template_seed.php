<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive template preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use core_component;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Seed handler for archive_templates.json.
 *
 * Archive templates are template definitions for mod_uckkarchive defaults.
 * They are not courses, and they do not create uckkarchive_item rows.
 *
 * The handler validates and registers seed-managed archive template definitions
 * in tool_uckkseed config so that course/template enrichment code can retrieve
 * them later without routing archive_templates through course_seed.
 */
final class archive_template_seed {
    /** Component owning this seeder. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'archive_templates';

    /** Target component. */
    public const TARGET_COMPONENT = 'mod_uckkarchive';

    /** Target type for logs/results. */
    public const TARGET_TYPE = 'archive_template';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Preset version. */
    public const VERSION = 2026051200;

    /** Config key storing template definitions. */
    public const CONFIG_TEMPLATES = 'seeded_archive_templates';

    /** Config key storing template key index. */
    public const CONFIG_TEMPLATE_INDEX = 'seeded_archive_template_index';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

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

    /** Managed-by metadata key. */
    private const METADATA_MANAGED_BY = 'managedby';

    /** Managed-by metadata value. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Expected module name inside defaults. */
    private const EXPECTED_MODULE = 'uckkarchive';

    /** Allowed archive template visibility values. */
    private const ALLOWED_VISIBILITY = [
        'course',
        'institution',
        'private',
        'public',
        'hidden',
    ];

    /** Allowed archive template validation states. */
    private const ALLOWED_VALIDATION_STATES = [
        'unverified',
        'human_reviewed',
        'verified',
        'contested',
        'rejected',
        'invalidated',
    ];

    /** Allowed archive template statuses. */
    private const ALLOWED_STATUS = [
        'draft',
        'active',
        'hidden',
        'archived',
    ];

    /**
     * Validate archive template preset items.
     *
     * @param array<int, mixed> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Archive templates validated.');

        if (!$this->component_exists(self::TARGET_COMPONENT)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                'Target component mod_uckkarchive is not installed or not discoverable.',
                ['component' => self::TARGET_COMPONENT]
            );
        }

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                'archive_templates preset is empty.'
            );
            $this->finalise_result($result);
            return $result;
        }

        $seen = [];

        foreach ($items as $position => $item) {
            $row = $this->normalise_item($item);
            $targetkey = $row['key'] !== '' ? $row['key'] : 'row_' . ((int)$position + 1);
            $rowerrors = 0;

            if ($row['key'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Missing archive template key.');
                $rowerrors++;
            }

            if ($row['name'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Missing archive template name.');
                $rowerrors++;
            }

            if ($row['component'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Missing archive template component.');
                $rowerrors++;
            } else if ($row['component'] !== self::TARGET_COMPONENT) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Archive template component must be mod_uckkarchive.',
                    ['actual' => $row['component'], 'expected' => self::TARGET_COMPONENT]
                );
                $rowerrors++;
            }

            if (isset($seen[$row['key']]) && $row['key'] !== '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Duplicate archive template key.',
                    ['key' => $row['key']]
                );
                $rowerrors++;
            }

            if ($row['key'] !== '') {
                $seen[$row['key']] = true;
            }

            if (empty($row['defaults'])) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Archive template defaults object is required.');
                $rowerrors++;
            } else {
                $rowerrors += $this->validate_defaults($result, $row, $targetkey);
            }

            foreach (['sections', 'activities'] as $field) {
                if (!is_array($row[$field])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        $targetkey,
                        'Archive template ' . $field . ' must be an array.'
                    );
                    $rowerrors++;
                }
            }

            if (!is_array($row['completion'])) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Archive template completion must be an object.');
                $rowerrors++;
            }

            if (!is_array($row['metadata'])) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Archive template metadata must be an object.');
                $rowerrors++;
            }

            if ($rowerrors === 0) {
                $result->increment('skipped');
                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Archive template is valid.',
                    ['key' => $row['key']]
                );
            }
        }

        $this->finalise_result($result);
        return $result;
    }

    /**
     * Apply archive template preset items idempotently.
     *
     * Apply stores template definitions in tool_uckkseed config. It does not
     * create archive activities and does not create archive items/proofs.
     *
     * @param array<int, mixed> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $result = $this->new_result('Archive templates seeded.');
        $dryrun = in_array($mode, [self::MODE_DRY_RUN, self::MODE_REPORT, self::MODE_ROLLBACK_PLAN], true);
        $templates = $this->get_seeded_templates();
        $index = $this->get_seeded_template_index();

        foreach ($items as $item) {
            $row = $this->normalise_item($item);
            $key = $row['key'];

            if ($key === '') {
                continue;
            }

            $canonical = $this->to_storage_row($row);
            $existing = $templates[$key] ?? null;

            if ($existing !== null && $this->same_template_definition($existing, $canonical)) {
                $result->increment('skipped');
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Archive template already matches seeded definition.'
                );
                continue;
            }

            if ($dryrun) {
                $result->increment($existing === null ? 'created' : 'updated');
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    $existing === null
                        ? 'Dry run: archive template would be registered.'
                        : 'Dry run: archive template would be updated.',
                    ['template' => $canonical]
                );
                continue;
            }

            $templates[$key] = $canonical;
            $index[$key] = [
                'key' => $key,
                'name' => $canonical['name'],
                'component' => $canonical['component'],
                'module' => $canonical['defaults']['module'] ?? self::EXPECTED_MODULE,
                'timemodified' => time(),
            ];

            $result->increment($existing === null ? 'created' : 'updated');
            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $key,
                $existing === null ? 'Archive template registered.' : 'Archive template updated.',
                ['template' => $canonical]
            );
        }

        if (!$dryrun) {
            $this->set_seeded_templates($templates);
            $this->set_seeded_template_index($index);
        }

        $this->finalise_result($result);
        return $result;
    }

    /**
     * Reset seed-managed archive template definitions.
     *
     * @param array<int, mixed> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = in_array($mode, [self::MODE_DRY_RUN, self::MODE_REPORT, self::MODE_ROLLBACK_PLAN], true);
        $confirmed = !empty($options['confirm']) || !empty($options['force']);

        $result = $this->new_result('Archive template reset completed.');

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Confirmation is required to reset seed-managed archive templates.'
            );
            $this->finalise_result($result);
            return $result;
        }

        $templates = $this->get_seeded_templates();
        $index = $this->get_seeded_template_index();

        if (empty($items)) {
            $keys = array_keys($templates);
        } else {
            $keys = [];
            foreach ($items as $item) {
                $row = $this->normalise_item($item);
                if ($row['key'] !== '') {
                    $keys[] = $row['key'];
                }
            }
            $keys = array_values(array_unique($keys));
        }

        if (empty($keys)) {
            $this->add_message(
                $result,
                self::SEVERITY_INFO,
                '',
                'No seed-managed archive templates found to reset.'
            );
            $this->finalise_result($result);
            return $result;
        }

        foreach ($keys as $key) {
            if (!isset($templates[$key])) {
                $result->increment('skipped');
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Archive template is already absent.'
                );
                continue;
            }

            if ($dryrun) {
                $result->increment('skipped');
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Dry run: archive template would be removed.'
                );
                continue;
            }

            unset($templates[$key], $index[$key]);
            $result->increment('updated');
            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $key,
                'Archive template removed.'
            );
        }

        if (!$dryrun) {
            $this->set_seeded_templates($templates);
            $this->set_seeded_template_index($index);
        }

        $this->finalise_result($result);
        return $result;
    }

    /**
     * Export registered archive template definitions.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = array_values($this->get_seeded_templates());

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => self::VERSION,
            'items' => $items,
            'metadata' => [
                'targetcomponent' => self::TARGET_COMPONENT,
                'targettype' => self::TARGET_TYPE,
                'exportedat' => time(),
            ],
        ];
    }

    /**
     * Validate defaults payload.
     *
     * @param validation_result $result Result object.
     * @param array<string, mixed> $row Normalised item.
     * @param string $targetkey Target key.
     * @return int Number of validation errors added.
     */
    private function validate_defaults(validation_result $result, array $row, string $targetkey): int {
        $errors = 0;
        $defaults = $row['defaults'];

        $module = (string)($defaults['module'] ?? '');
        if ($module === '') {
            $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'defaults.module is required.');
            $errors++;
        } else if ($module !== self::EXPECTED_MODULE) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                $targetkey,
                'defaults.module must be uckkarchive.',
                ['actual' => $module, 'expected' => self::EXPECTED_MODULE]
            );
            $errors++;
        }

        if (array_key_exists('name', $defaults) && trim((string)$defaults['name']) === '') {
            $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'defaults.name cannot be empty.');
            $errors++;
        }

        if (array_key_exists('introformat', $defaults) && !is_numeric($defaults['introformat'])) {
            $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'defaults.introformat must be numeric.');
            $errors++;
        }

        if (isset($defaults['status']) && !in_array((string)$defaults['status'], self::ALLOWED_STATUS, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'defaults.status is outside the expected archive template vocabulary.',
                ['status' => $defaults['status'], 'allowed' => self::ALLOWED_STATUS]
            );
        }

        if (isset($defaults['visibility']) && !in_array((string)$defaults['visibility'], self::ALLOWED_VISIBILITY, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'defaults.visibility is outside the expected archive template vocabulary.',
                ['visibility' => $defaults['visibility'], 'allowed' => self::ALLOWED_VISIBILITY]
            );
        }

        if (isset($defaults['validationstate']) && !in_array((string)$defaults['validationstate'], self::ALLOWED_VALIDATION_STATES, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'defaults.validationstate is outside the expected archive validation-state vocabulary.',
                ['validationstate' => $defaults['validationstate'], 'allowed' => self::ALLOWED_VALIDATION_STATES]
            );
        }

        foreach (['fileareas', 'allowed_itemtypes', 'allowed_prooftypes'] as $field) {
            if (array_key_exists($field, $defaults) && !is_array($defaults[$field])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'defaults.' . $field . ' must be an array.'
                );
                $errors++;
            }
        }

        if (array_key_exists('requires_human_validation', $defaults) && !is_bool($defaults['requires_human_validation'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'defaults.requires_human_validation should be boolean.'
            );
        }

        return $errors;
    }

    /**
     * Normalise one template row.
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        if ($item instanceof stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $defaults = $this->normalise_array($item['defaults'] ?? []);
        $metadata = $this->normalise_array($item['metadata'] ?? []);
        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;
        $metadata['template_only'] = true;

        return [
            'key' => $this->normalise_key((string)($item['key'] ?? $item['id'] ?? '')),
            'name' => trim(clean_param((string)($item['name'] ?? $item['title'] ?? ''), PARAM_TEXT)),
            'component' => clean_param((string)($item['component'] ?? self::TARGET_COMPONENT), PARAM_COMPONENT),
            'description' => trim(clean_param((string)($item['description'] ?? $item['summary'] ?? ''), PARAM_TEXT)),
            'defaults' => $this->normalise_defaults($defaults),
            'sections' => $this->normalise_list($item['sections'] ?? []),
            'activities' => $this->normalise_list($item['activities'] ?? []),
            'completion' => $this->normalise_array($item['completion'] ?? []),
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise defaults object.
     *
     * @param array<string, mixed> $defaults Defaults.
     * @return array<string, mixed>
     */
    private function normalise_defaults(array $defaults): array {
        if (empty($defaults)) {
            return [];
        }

        if (isset($defaults['module'])) {
            $defaults['module'] = clean_param((string)$defaults['module'], PARAM_ALPHANUMEXT);
        }

        if (isset($defaults['name'])) {
            $defaults['name'] = trim(clean_param((string)$defaults['name'], PARAM_TEXT));
        }

        if (isset($defaults['intro'])) {
            $defaults['intro'] = (string)$defaults['intro'];
        }

        if (isset($defaults['introformat'])) {
            $defaults['introformat'] = (int)$defaults['introformat'];
        }

        if (isset($defaults['status'])) {
            $defaults['status'] = clean_param((string)$defaults['status'], PARAM_ALPHANUMEXT);
        }

        if (isset($defaults['visibility'])) {
            $defaults['visibility'] = clean_param((string)$defaults['visibility'], PARAM_ALPHANUMEXT);
        }

        if (isset($defaults['validationstate'])) {
            $defaults['validationstate'] = clean_param((string)$defaults['validationstate'], PARAM_ALPHANUMEXT);
        }

        if (isset($defaults['archivepolicy'])) {
            $defaults['archivepolicy'] = clean_param((string)$defaults['archivepolicy'], PARAM_ALPHANUMEXT);
        }

        if (isset($defaults['visible'])) {
            $defaults['visible'] = $this->normalise_visible($defaults['visible']);
        }

        if (isset($defaults['requires_human_validation'])) {
            $defaults['requires_human_validation'] = $this->normalise_bool($defaults['requires_human_validation']);
        }

        foreach (['fileareas', 'allowed_itemtypes', 'allowed_prooftypes'] as $field) {
            if (isset($defaults[$field]) && is_array($defaults[$field])) {
                $defaults[$field] = array_values(array_map(
                    static fn($value): string => clean_param((string)$value, PARAM_ALPHANUMEXT),
                    $defaults[$field]
                ));
            }
        }

        return $defaults;
    }

    /**
     * Convert item to storage row.
     *
     * @param array<string, mixed> $row Normalised item.
     * @return array<string, mixed>
     */
    private function to_storage_row(array $row): array {
        return [
            'key' => $row['key'],
            'name' => $row['name'],
            'component' => $row['component'],
            'description' => $row['description'],
            'defaults' => $row['defaults'],
            'sections' => $row['sections'],
            'activities' => $row['activities'],
            'completion' => $row['completion'],
            'metadata' => [
                ...$row['metadata'],
                'targetcomponent' => self::TARGET_COMPONENT,
                'targettype' => self::TARGET_TYPE,
                'timemodified' => time(),
            ],
        ];
    }

    /**
     * Get seeded templates.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_seeded_templates(): array {
        $raw = get_config(self::COMPONENT, self::CONFIG_TEMPLATES);

        if ($raw === false || $raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string)$raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set seeded templates.
     *
     * @param array<string, array<string, mixed>> $templates Templates.
     */
    private function set_seeded_templates(array $templates): void {
        ksort($templates);
        set_config(
            self::CONFIG_TEMPLATES,
            json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Get template index.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_seeded_template_index(): array {
        $raw = get_config(self::COMPONENT, self::CONFIG_TEMPLATE_INDEX);

        if ($raw === false || $raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string)$raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set template index.
     *
     * @param array<string, array<string, mixed>> $index Index.
     */
    private function set_seeded_template_index(array $index): void {
        ksort($index);
        set_config(
            self::CONFIG_TEMPLATE_INDEX,
            json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Compare canonical template definitions.
     *
     * @param array<string, mixed> $left Left row.
     * @param array<string, mixed> $right Right row.
     * @return bool
     */
    private function same_template_definition(array $left, array $right): bool {
        unset($left['metadata']['timemodified'], $right['metadata']['timemodified']);

        return json_encode($left, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            === json_encode($right, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check whether component exists.
     *
     * @param string $component Component frankenstyle name.
     * @return bool
     */
    private function component_exists(string $component): bool {
        if ($component === self::TARGET_COMPONENT) {
            return !empty(core_component::get_plugin_directory('mod', 'uckkarchive'));
        }

        if (!str_contains($component, '_')) {
            return false;
        }

        [$plugintype, $pluginname] = explode('_', $component, 2);

        return !empty(core_component::get_plugin_directory($plugintype, $pluginname));
    }

    /**
     * Add result message.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $targetkey Target key.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $result->add_message(
            $severity,
            $message,
            self::COMPONENT,
            self::PRESET,
            self::TARGET_TYPE,
            $targetkey,
            $metadata
        );

        if (in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
            $result->increment('failed');
        }
    }

    /**
     * Create base result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => $summary,
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
                'targetcomponent' => self::TARGET_COMPONENT,
            ],
        ]);
    }

    /**
     * Finalise result status.
     *
     * @param validation_result $result Result object.
     */
    private function finalise_result(validation_result $result): void {
        if ($result->has_errors()) {
            $result->set_status(validation_result::STATUS_FAILED);
        } else if ($result->has_warnings()) {
            $result->set_status(validation_result::STATUS_WARNING);
        } else {
            $result->set_status(validation_result::STATUS_COMPLETED);
        }
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise key.
     *
     * @param string $value Raw key.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Normalise mixed value to array/object shape.
     *
     * @param mixed $value Value.
     * @return array<string, mixed>
     */
    private function normalise_array(mixed $value): array {
        if ($value instanceof stdClass) {
            return (array)$value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Normalise mixed value to a list.
     *
     * @param mixed $value Value.
     * @return array<int, mixed>
     */
    private function normalise_list(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return array_values($value);
        }

        return array_values($value);
    }

    /**
     * Normalise visible value.
     *
     * @param mixed $value Value.
     * @return int
     */
    private function normalise_visible(mixed $value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int)$value) > 0 ? 1 : 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'visible', 'public', 'course'], true) ? 1 : 0;
        }

        return 1;
    }

    /**
     * Normalise boolean value.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int)$value) === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
        }

        return false;
    }
}