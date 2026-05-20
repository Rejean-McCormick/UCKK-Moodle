<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Assembly template preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Seed handler for assembly_templates.json.
 *
 * Assembly templates are not Moodle courses and must not be routed through
 * course_seed. This handler validates and stores canonical UCKK assembly
 * template definitions for later use by course/module provisioning workflows.
 *
 * This class does not:
 * - create Moodle courses;
 * - create assembly activity instances directly;
 * - award badges;
 * - validate evidence;
 * - publish assembly decisions;
 * - bypass archive or integrity workflows.
 */
final class assembly_template_seed {
    /** Component owning this seeder. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'assembly_templates';

    /** Target type for validation messages. */
    public const TARGET_TYPE = 'assembly_template';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Preset version. */
    public const VERSION = 2026051200;

    /** Expected component for this template family. */
    private const EXPECTED_COMPONENT = 'mod_uckkassembly';

    /** Config prefix for stored template definitions. */
    private const CONFIG_PREFIX = 'assembly_template_';

    /** Config key for stored template index. */
    private const CONFIG_INDEX = 'assembly_template_index';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    public const STATUS_FAILED = 'failed';

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

    /** Allowed assembly visibility values. */
    private const ALLOWED_VISIBILITY = [
        'public',
        'institution',
        'course',
        'cohort',
        'restricted',
        'private',
        'hidden',
    ];

    /** Allowed template statuses. */
    private const ALLOWED_STATUS = [
        'active',
        'draft',
        'archived',
        'disabled',
        'hidden',
    ];

    /** Allowed validation states. */
    private const ALLOWED_VALIDATION_STATES = [
        'unverified',
        'pending',
        'validated',
        'rejected',
        'archived',
    ];

    /** Optional seeder reference. */
    private mixed $seeder;

    /**
     * Constructor.
     *
     * The main seeder instantiates handlers with itself as an argument. This
     * handler does not require that reference, but accepts it for compatibility.
     *
     * @param mixed $seeder Parent seeder.
     */
    public function __construct(mixed $seeder = null) {
        $this->seeder = $seeder;
    }

    /**
     * Validate assembly template items.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Assembly templates validated.');

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                'assembly_templates.json is empty.'
            );

            return $this->finish_result($result);
        }

        $seenkeys = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template is missing required field: key.'
                );
            }

            if ($item['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template is missing required field: name.'
                );
            }

            if ($item['component'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template is missing required field: component.'
                );
            } else if ($item['component'] !== self::EXPECTED_COMPONENT) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template component must be ' . self::EXPECTED_COMPONENT . '.',
                    [
                        'component' => $item['component'],
                        'expected' => self::EXPECTED_COMPONENT,
                    ]
                );
            }

            if (!is_array($item['defaults']) || empty($item['defaults'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template is missing required object: defaults.'
                );
            } else {
                $this->validate_defaults($result, $targetkey, $item['defaults']);
            }

            if (!is_array($item['sections'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template sections must be an array.'
                );
            } else {
                $this->validate_sections($result, $targetkey, $item['sections']);
            }

            if (!is_array($item['activities'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template activities must be an array.'
                );
            } else {
                $this->validate_activities($result, $targetkey, $item['activities']);
            }

            if (!is_array($item['completion'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template completion must be an object.'
                );
            }

            if (!is_array($item['metadata'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template metadata must be an object.'
                );
            }

            if ($item['key'] !== '') {
                if (isset($seenkeys[$item['key']])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        $targetkey,
                        'Duplicate assembly template key: ' . $item['key'] . '.'
                    );
                }

                $seenkeys[$item['key']] = true;
            }

            $this->increment($result, 'skipped');
        }

        return $this->finish_result($result);
    }

    /**
     * Apply assembly template items.
     *
     * Templates are stored as plugin config records. This gives the seed system
     * an idempotent registry without pretending that templates are courses or
     * activity instances.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);

        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $result = $this->new_result(
            $dryrun || $rollbackplan
                ? 'Assembly template dry run completed.'
                : 'Assembly templates seeded.'
        );

        $storedkeys = $this->get_stored_index();

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['key'] === '') {
                $this->increment($result, 'failed');
                continue;
            }

            $existing = get_config(self::COMPONENT, self::CONFIG_PREFIX . $item['key']);
            $exists = $existing !== false && trim((string)$existing) !== '';

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $item['key'],
                    $exists
                        ? 'Assembly template would be updated: ' . $item['key'] . '.'
                        : 'Assembly template would be created: ' . $item['key'] . '.',
                    [
                        'existing' => $exists,
                        'template' => $item,
                    ]
                );

                $this->increment($result, 'skipped');
                continue;
            }

            set_config(
                self::CONFIG_PREFIX . $item['key'],
                json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                self::COMPONENT
            );

            $storedkeys[$item['key']] = true;
            $this->store_index(array_keys($storedkeys));

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $item['key'],
                $exists
                    ? 'Assembly template updated: ' . $item['key'] . '.'
                    : 'Assembly template created: ' . $item['key'] . '.'
            );

            $this->increment($result, $exists ? 'updated' : 'created');
        }

        return $this->finish_result($result);
    }

    /**
     * Reset seed-managed assembly templates.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $confirmed = !empty($options['confirm']);

        $result = $this->new_result(
            $dryrun
                ? 'Assembly template reset dry run completed.'
                : 'Assembly templates reset completed.'
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Confirmation is required to reset assembly templates.'
            );

            return $this->finish_result($result);
        }

        $keys = $this->get_reset_keys($items);

        foreach ($keys as $key) {
            $existing = get_config(self::COMPONENT, self::CONFIG_PREFIX . $key);
            $exists = $existing !== false && trim((string)$existing) !== '';

            if (!$exists) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Assembly template already absent: ' . $key . '.'
                );

                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Assembly template would be removed: ' . $key . '.'
                );

                $this->increment($result, 'skipped');
                continue;
            }

            unset_config(self::CONFIG_PREFIX . $key, self::COMPONENT);

            $storedkeys = $this->get_stored_index();
            unset($storedkeys[$key]);
            $this->store_index(array_keys($storedkeys));

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $key,
                'Assembly template removed: ' . $key . '.'
            );

            $this->increment($result, 'updated');
        }

        return $this->finish_result($result);
    }

    /**
     * Export stored assembly templates.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = [];

        foreach (array_keys($this->get_stored_index()) as $key) {
            $stored = get_config(self::COMPONENT, self::CONFIG_PREFIX . $key);

            if ($stored === false || trim((string)$stored) === '') {
                continue;
            }

            $decoded = json_decode((string)$stored, true);

            if (is_array($decoded)) {
                $items[] = $this->normalise_item($decoded);
            }
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => self::VERSION,
            'items' => $items,
        ];
    }

    /**
     * Validate defaults object.
     *
     * @param validation_result $result Result object.
     * @param string $targetkey Template key.
     * @param array<string, mixed> $defaults Defaults object.
     */
    private function validate_defaults(validation_result $result, string $targetkey, array $defaults): void {
        $assemblytype = trim((string)($defaults['assemblytype'] ?? ''));

        if ($assemblytype === '') {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Assembly template defaults should define assemblytype.'
            );
        }

        $status = trim((string)($defaults['status'] ?? 'active'));

        if ($status !== '' && !in_array($status, self::ALLOWED_STATUS, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected assembly template status: ' . $status . '.',
                ['status' => $status]
            );
        }

        $visibility = trim((string)($defaults['visibility'] ?? 'course'));

        if ($visibility !== '' && !in_array($visibility, self::ALLOWED_VISIBILITY, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected assembly template visibility: ' . $visibility . '.',
                ['visibility' => $visibility]
            );
        }

        $validationstate = trim((string)($defaults['validationstate'] ?? ''));

        if ($validationstate !== '' && !in_array($validationstate, self::ALLOWED_VALIDATION_STATES, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected assembly validation state: ' . $validationstate . '.',
                ['validationstate' => $validationstate]
            );
        }

        foreach ([
            'allowmotions',
            'allowamendments',
            'allowobjections',
            'allowvotes',
            'allowminorityreports',
            'allowcontestations',
            'requireminutes',
            'requirearchive',
        ] as $boolfield) {
            if (array_key_exists($boolfield, $defaults) && !is_bool($defaults[$boolfield])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Assembly template default should be boolean: ' . $boolfield . '.',
                    [
                        'field' => $boolfield,
                        'value' => $defaults[$boolfield],
                    ]
                );
            }
        }

        if (isset($defaults['quorum']) && !is_array($defaults['quorum'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Assembly template quorum should be an object.'
            );
        }

        if (isset($defaults['threshold']) && !is_array($defaults['threshold'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Assembly template threshold should be an object.'
            );
        }

        if (isset($defaults['archive']) && !is_array($defaults['archive'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Assembly template archive should be an object.'
            );
        }
    }

    /**
     * Validate sections array.
     *
     * @param validation_result $result Result object.
     * @param string $targetkey Template key.
     * @param array<int, mixed> $sections Sections.
     */
    private function validate_sections(validation_result $result, string $targetkey, array $sections): void {
        $seen = [];

        foreach ($sections as $index => $section) {
            if (!is_array($section)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template section must be an object at index ' . $index . '.'
                );
                continue;
            }

            $key = trim((string)($section['key'] ?? ''));
            $name = trim((string)($section['name'] ?? ''));

            if ($key === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template section is missing key at index ' . $index . '.'
                );
            }

            if ($name === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Assembly template section is missing name at index ' . $index . '.'
                );
            }

            if ($key !== '' && isset($seen[$key])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Duplicate assembly template section key: ' . $key . '.'
                );
            }

            if ($key !== '') {
                $seen[$key] = true;
            }
        }
    }

    /**
     * Validate activities array.
     *
     * @param validation_result $result Result object.
     * @param string $targetkey Template key.
     * @param array<int, mixed> $activities Activities.
     */
    private function validate_activities(validation_result $result, string $targetkey, array $activities): void {
        $seen = [];

        foreach ($activities as $index => $activity) {
            if (!is_array($activity)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template activity must be an object at index ' . $index . '.'
                );
                continue;
            }

            $key = trim((string)($activity['key'] ?? ''));

            if ($key === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Assembly template activity is missing key at index ' . $index . '.'
                );
            }

            if ($key !== '' && isset($seen[$key])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Duplicate assembly template activity key: ' . $key . '.'
                );
            }

            if ($key !== '') {
                $seen[$key] = true;
            }

            $component = trim((string)($activity['component'] ?? ''));

            if ($component !== '' && !$this->looks_like_component($component)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Assembly template activity has invalid component format: ' . $component . '.'
                );
            }
        }
    }

    /**
     * Normalise one template item.
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

        $metadata = $this->normalise_assoc_array($item['metadata'] ?? []);
        $defaults = $this->normalise_assoc_array($item['defaults'] ?? []);
        $completion = $this->normalise_assoc_array($item['completion'] ?? []);

        $key = $this->normalise_key((string)($item['key'] ?? $item['id'] ?? $item['shortname'] ?? ''));
        $component = clean_param((string)($item['component'] ?? self::EXPECTED_COMPONENT), PARAM_COMPONENT);

        $defaults['visible'] = $this->normalise_visible($defaults['visible'] ?? 1);

        $metadata['seeded_by'] = self::COMPONENT;
        $metadata['source_preset'] = self::PRESET;
        $metadata['template_only'] = true;

        return [
            'key' => $key,
            'name' => trim(clean_param((string)($item['name'] ?? $item['title'] ?? $this->fallback_name($key)), PARAM_TEXT)),
            'component' => $component,
            'description' => trim(clean_param((string)($item['description'] ?? ''), PARAM_TEXT)),
            'defaults' => $defaults,
            'sections' => $this->normalise_list_of_arrays($item['sections'] ?? []),
            'activities' => $this->normalise_list_of_arrays($item['activities'] ?? []),
            'completion' => $completion,
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise a key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Build fallback name.
     *
     * @param string $key Key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $name = trim(str_replace('_', ' ', $key));

        if ($name === '') {
            return '';
        }

        return ucfirst($name);
    }

    /**
     * Normalise an associative array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private function normalise_assoc_array(mixed $value): array {
        if ($value instanceof \stdClass) {
            $value = (array)$value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * Normalise list of associative arrays.
     *
     * @param mixed $value Raw value.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_list_of_arrays(mixed $value): array {
        if ($value instanceof \stdClass) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if ($item instanceof \stdClass) {
                $item = (array)$item;
            }

            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Normalise visible to Moodle-style int.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function normalise_visible(mixed $value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value ? 1 : 0;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            if (in_array($value, ['1', 'true', 'yes', 'visible', 'active', 'public', 'course', 'institution'], true)) {
                return 1;
            }

            if (in_array($value, ['0', 'false', 'no', 'hidden', 'disabled', 'draft', 'private'], true)) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Check component string shape.
     *
     * @param string $component Component name.
     * @return bool
     */
    private function looks_like_component(string $component): bool {
        return preg_match('/^[a-z][a-z0-9]*_[a-z][a-z0-9_]*$/', $component) === 1;
    }

    /**
     * Get keys to reset.
     *
     * @param array<int, mixed> $items Preset items.
     * @return string[]
     */
    private function get_reset_keys(array $items): array {
        $keys = [];

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['key'] !== '') {
                $keys[] = $item['key'];
            }
        }

        if (empty($keys)) {
            $keys = array_keys($this->get_stored_index());
        }

        return array_values(array_unique($keys));
    }

    /**
     * Get stored template index.
     *
     * @return array<string, bool>
     */
    private function get_stored_index(): array {
        $stored = get_config(self::COMPONENT, self::CONFIG_INDEX);

        if ($stored === false || trim((string)$stored) === '') {
            return [];
        }

        $decoded = json_decode((string)$stored, true);

        if (!is_array($decoded)) {
            return [];
        }

        $index = [];

        foreach ($decoded as $key) {
            $key = $this->normalise_key((string)$key);

            if ($key !== '') {
                $index[$key] = true;
            }
        }

        return $index;
    }

    /**
     * Store template index.
     *
     * @param string[] $keys Template keys.
     */
    private function store_index(array $keys): void {
        $clean = [];

        foreach ($keys as $key) {
            $key = $this->normalise_key((string)$key);

            if ($key !== '') {
                $clean[] = $key;
            }
        }

        $clean = array_values(array_unique($clean));
        sort($clean);

        set_config(
            self::CONFIG_INDEX,
            json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param(trim($mode), PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Create a validation result.
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
     * Add a message to a validation result.
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
    }

    /**
     * Increment a result count.
     *
     * @param validation_result $result Result object.
     * @param string $key Count key.
     */
    private function increment(validation_result $result, string $key): void {
        $result->increment($key);
    }

    /**
     * Finalise result status.
     *
     * @param validation_result $result Result.
     * @return validation_result
     */
    private function finish_result(validation_result $result): validation_result {
        if ($result->has_errors()) {
            $result->set_status(self::STATUS_FAILED);
        } else if ($result->has_warnings()) {
            $result->set_status(self::STATUS_WARNING);
        } else {
            $result->set_status(self::STATUS_COMPLETED);
        }

        return $result;
    }
}

