<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Course template preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates and stores UCKK course template preset definitions.
 *
 * Course templates are not Moodle courses. They must not require a category and
 * must not create course records directly. They are seed-time template registry
 * definitions consumed by course_seed and format_uckk-aware course creation.
 */
final class course_template_seed {
    /** Owning component. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'course_templates';

    /** Target type for messages. */
    private const TARGET_TYPE = 'course_template';

    /** Expected template owner component. */
    private const EXPECTED_COMPONENT = 'format_uckk';

    /** Stored registry config key. */
    private const CONFIG_REGISTRY = 'course_template_registry';

    /** Stored template config prefix. */
    private const CONFIG_TEMPLATE_PREFIX = 'course_template_';

    /** Status: completed. */
    private const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    private const STATUS_FAILED = 'failed';

    /** Status: warning. */
    private const STATUS_WARNING = 'warning';

    /** Severity: info. */
    private const SEVERITY_INFO = 'info';

    /** Severity: success. */
    private const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    private const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    private const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    private const SEVERITY_BLOCKER = 'blocker';

    /** Mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Mode: rollback plan. */
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /**
     * Validate preset items.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Course template validation completed.');

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Course template preset is empty.',
                '',
                ['preset' => self::PRESET]
            );

            return $this->finalise_result($result);
        }

        $seenkeys = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . ((int)$index + 1);

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template is missing required field: key.',
                    $targetkey
                );
            }

            if ($item['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template is missing required field: name.',
                    $targetkey
                );
            }

            if ($item['component'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template is missing required field: component.',
                    $targetkey
                );
            } else if ($item['component'] !== self::EXPECTED_COMPONENT) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template component must be format_uckk.',
                    $targetkey,
                    [
                        'component' => $item['component'],
                        'expected' => self::EXPECTED_COMPONENT,
                    ]
                );
            }

            if (empty($item['defaults']) || !is_array($item['defaults'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template is missing required object: defaults.',
                    $targetkey
                );
            } else {
                $this->validate_defaults($result, $item, $targetkey);
            }

            if ($item['key'] !== '') {
                if (isset($seenkeys[$item['key']])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Duplicate course template key.',
                        $targetkey,
                        ['key' => $item['key']]
                    );
                }

                $seenkeys[$item['key']] = true;
            }

            $this->validate_sections($result, $item, $targetkey);
            $this->validate_activities($result, $item, $targetkey);

            if (!is_array($item['completion'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template completion must be an object.',
                    $targetkey
                );
            }

            if (!is_array($item['metadata'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template metadata must be an object.',
                    $targetkey
                );
            }

            if (array_key_exists('category', $item) || array_key_exists('category_idnumber', $item)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Course template contains a category field. Templates are not courses; category is ignored.',
                    $targetkey
                );
            }

            $this->increment($result, 'skipped');
        }

        return $this->finalise_result($result);
    }

    /**
     * Apply preset items.
     *
     * This stores course template definitions in tool_uckkseed config so
     * course_seed can resolve them by key. It does not create Moodle courses.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $dryrun = $this->is_dry_run($options);
        $rollbackplan = (($options['mode'] ?? '') === self::MODE_ROLLBACK_PLAN);

        $result = $this->new_result(
            $dryrun || $rollbackplan
                ? 'Course template dry-run completed.'
                : 'Course templates stored.'
        );

        $registry = $this->read_registry();

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['key'] === '') {
                $this->increment($result, 'failed');
                continue;
            }

            $existing = $registry[$item['key']] ?? null;

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $existing === null
                        ? 'Course template would be created.'
                        : 'Course template would be updated.',
                    $item['key'],
                    [
                        'existing' => $existing,
                        'proposed' => $item,
                    ]
                );

                $this->increment($result, 'skipped');
                continue;
            }

            $registry[$item['key']] = $item;

            set_config(
                self::CONFIG_TEMPLATE_PREFIX . $item['key'],
                $this->encode_json($item),
                self::COMPONENT
            );

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $existing === null
                    ? 'Course template created.'
                    : 'Course template updated.',
                $item['key'],
                ['key' => $item['key']]
            );

            $this->increment($result, $existing === null ? 'created' : 'updated');
        }

        if (!$dryrun && !$rollbackplan) {
            ksort($registry);
            set_config(self::CONFIG_REGISTRY, $this->encode_json($registry), self::COMPONENT);
        }

        return $this->finalise_result($result);
    }

    /**
     * Reset seed-managed course template definitions.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $dryrun = $this->is_dry_run($options);
        $confirmed = !empty($options['confirm']);

        $result = $this->new_result(
            $dryrun
                ? 'Course template reset dry-run completed.'
                : 'Course template reset completed.'
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                'Reset requires confirm=true.',
                '',
                ['preset' => self::PRESET]
            );

            return $this->finalise_result($result);
        }

        $registry = $this->read_registry();
        $keys = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['key'] !== '') {
                $keys[] = $item['key'];
            }
        }

        if (empty($keys)) {
            $keys = array_keys($registry);
        }

        foreach (array_unique($keys) as $key) {
            if (!isset($registry[$key])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Course template already absent.',
                    $key
                );

                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Course template would be removed.',
                    $key,
                    ['template' => $registry[$key]]
                );

                $this->increment($result, 'skipped');
                continue;
            }

            unset($registry[$key]);
            unset_config(self::CONFIG_TEMPLATE_PREFIX . $key, self::COMPONENT);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                'Course template removed.',
                $key
            );

            $this->increment($result, 'updated');
        }

        if (!$dryrun) {
            ksort($registry);
            set_config(self::CONFIG_REGISTRY, $this->encode_json($registry), self::COMPONENT);
        }

        return $this->finalise_result($result);
    }

    /**
     * Export currently stored templates.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $registry = $this->read_registry();

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => array_values($registry),
        ];
    }

    /**
     * Validate defaults object.
     *
     * @param validation_result $result Result.
     * @param array<string, mixed> $item Normalised item.
     * @param string $targetkey Target key.
     */
    private function validate_defaults(validation_result $result, array $item, string $targetkey): void {
        $defaults = $item['defaults'];

        if (!isset($defaults['format']) || (string)$defaults['format'] === '') {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Course template defaults should include format.',
                $targetkey
            );
        } else if ((string)$defaults['format'] !== 'uckk') {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Course template defaults.format is not uckk.',
                $targetkey,
                ['format' => (string)$defaults['format']]
            );
        }

        if (isset($defaults['numsections']) && (!is_numeric($defaults['numsections']) || (int)$defaults['numsections'] < 0)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Course template defaults.numsections must be a non-negative integer.',
                $targetkey,
                ['numsections' => $defaults['numsections']]
            );
        }

        if (isset($defaults['courseformatoptions']) && !is_array($defaults['courseformatoptions'])) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Course template defaults.courseformatoptions must be an object.',
                $targetkey
            );
        }
    }

    /**
     * Validate sections.
     *
     * @param validation_result $result Result.
     * @param array<string, mixed> $item Normalised item.
     * @param string $targetkey Target key.
     */
    private function validate_sections(validation_result $result, array $item, string $targetkey): void {
        if (!is_array($item['sections'])) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Course template sections must be an array.',
                $targetkey
            );

            return;
        }

        $seennumbers = [];
        $seenkeys = [];

        foreach ($item['sections'] as $index => $section) {
            if (!is_array($section)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template section must be an object.',
                    $targetkey,
                    ['sectionindex' => $index]
                );

                continue;
            }

            if (!array_key_exists('number', $section)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template section is missing number.',
                    $targetkey,
                    ['sectionindex' => $index]
                );
            } else {
                $number = (string)$section['number'];

                if (isset($seennumbers[$number])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Duplicate section number inside course template.',
                        $targetkey,
                        ['number' => $number]
                    );
                }

                $seennumbers[$number] = true;
            }

            $sectionkey = trim((string)($section['key'] ?? ''));

            if ($sectionkey === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Course template section is missing key.',
                    $targetkey,
                    ['sectionindex' => $index]
                );
            } else {
                if (isset($seenkeys[$sectionkey])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Duplicate section key inside course template.',
                        $targetkey,
                        ['sectionkey' => $sectionkey]
                    );
                }

                $seenkeys[$sectionkey] = true;
            }
        }
    }

    /**
     * Validate activities.
     *
     * @param validation_result $result Result.
     * @param array<string, mixed> $item Normalised item.
     * @param string $targetkey Target key.
     */
    private function validate_activities(validation_result $result, array $item, string $targetkey): void {
        if (!is_array($item['activities'])) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Course template activities must be an array.',
                $targetkey
            );

            return;
        }

        foreach ($item['activities'] as $index => $activity) {
            if (!is_array($activity)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Course template activity must be an object.',
                    $targetkey,
                    ['activityindex' => $index]
                );

                continue;
            }

            $component = (string)($activity['component'] ?? $activity['module'] ?? '');

            if ($component === '') {
                continue;
            }

            if (!$this->is_valid_activity_component($component)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Course template activity component does not look like a valid Moodle activity component.',
                    $targetkey,
                    [
                        'activityindex' => $index,
                        'component' => $component,
                    ]
                );
            }
        }
    }

    /**
     * Normalise one item.
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        if ($item instanceof \stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $key = trim((string)($item['key'] ?? $item['id'] ?? $item['shortname'] ?? ''));
        $key = $this->normalise_key($key);

        $defaults = $item['defaults'] ?? [];
        $sections = $item['sections'] ?? [];
        $activities = $item['activities'] ?? [];
        $completion = $item['completion'] ?? [];
        $metadata = $item['metadata'] ?? [];

        if ($defaults instanceof \stdClass) {
            $defaults = (array)$defaults;
        }

        if ($completion instanceof \stdClass) {
            $completion = (array)$completion;
        }

        if ($metadata instanceof \stdClass) {
            $metadata = (array)$metadata;
        }

        if (!is_array($sections)) {
            $sections = [];
        }

        if (!is_array($activities)) {
            $activities = [];
        }

        if (!is_array($defaults)) {
            $defaults = [];
        }

        if (!is_array($completion)) {
            $completion = [];
        }

        if (!is_array($metadata)) {
            $metadata = [];
        }

        $metadata['managedby'] = self::COMPONENT;
        $metadata['template_only'] = true;

        return [
            'key' => $key,
            'name' => trim((string)($item['name'] ?? $item['title'] ?? $this->fallback_name($key))),
            'component' => trim((string)($item['component'] ?? self::EXPECTED_COMPONENT)),
            'description' => trim((string)($item['description'] ?? $item['summary'] ?? '')),
            'defaults' => $defaults,
            'sections' => array_values($sections),
            'activities' => array_values($activities),
            'completion' => $completion,
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise a key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private function normalise_key(string $key): string {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? $key;
        $key = trim($key, '_');

        return clean_param($key, PARAM_ALPHANUMEXT);
    }

    /**
     * Generate fallback display name.
     *
     * @param string $key Key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $name = trim(str_replace('_', ' ', $key));

        return $name === '' ? '' : ucfirst($name);
    }

    /**
     * Whether activity component looks valid.
     *
     * @param string $component Component.
     * @return bool
     */
    private function is_valid_activity_component(string $component): bool {
        if (preg_match('/^mod_[a-z][a-z0-9_]*$/', $component) === 1) {
            return true;
        }

        if (preg_match('/^[a-z][a-z0-9_]*$/', $component) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Read stored registry.
     *
     * @return array<string, array<string, mixed>>
     */
    private function read_registry(): array {
        $raw = get_config(self::COMPONENT, self::CONFIG_REGISTRY);

        if ($raw === false || $raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string)$raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $registry = [];

        foreach ($decoded as $key => $item) {
            if (is_array($item)) {
                $normalised = $this->normalise_item($item);

                if ($normalised['key'] !== '') {
                    $registry[$normalised['key']] = $normalised;
                } else if (is_string($key) && $key !== '') {
                    $normalised['key'] = $this->normalise_key($key);
                    $registry[$normalised['key']] = $normalised;
                }
            }
        }

        return $registry;
    }

    /**
     * Encode JSON consistently.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function encode_json(mixed $value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Whether this is dry-run.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return bool
     */
    private function is_dry_run(array $options): bool {
        return !empty($options['dryrun'])
            || !empty($options['dry_run'])
            || (($options['mode'] ?? '') === self::MODE_DRY_RUN);
    }

    /**
     * Create result object.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return new validation_result(self::STATUS_COMPLETED, $summary, [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
        ]);
    }

    /**
     * Add a message.
     *
     * @param validation_result $result Result.
     * @param string $severity Severity.
     * @param string $message Message.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $message,
        string $targetkey = '',
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

        if ($severity === self::SEVERITY_ERROR || $severity === self::SEVERITY_BLOCKER) {
            $result->increment('failed');
            $result->increment('errors');
        } else if ($severity === self::SEVERITY_WARNING) {
            $result->increment('warnings');
        }
    }

    /**
     * Increment a result counter.
     *
     * @param validation_result $result Result.
     * @param string $counter Counter key.
     */
    private function increment(validation_result $result, string $counter): void {
        $result->increment($counter);
    }

    /**
     * Finalise result status.
     *
     * @param validation_result $result Result.
     * @return validation_result
     */
    private function finalise_result(validation_result $result): validation_result {
        if ($result->has_errors()) {
            $result->set_status(validation_result::STATUS_FAILED);
        } else if ($result->has_warnings()) {
            $result->set_status(validation_result::STATUS_WARNING);
        } else {
            $result->set_status(validation_result::STATUS_COMPLETED);
        }

        if (method_exists($result, 'complete')) {
            $result->complete($result->get_summary());
        }

        return $result;
    }
}