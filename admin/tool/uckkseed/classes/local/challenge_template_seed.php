<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Challenge template preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use JsonSerializable;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Seeds and validates challenge template definitions.
 *
 * Challenge templates are registry/template definitions owned by
 * mod_uckkchallenge. They are not Moodle courses and must not require course
 * categories or be routed through course_seed.
 */
final class challenge_template_seed {
    /** Owning component. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'challenge_templates';

    /** Target type used in result messages. */
    private const TARGET_TYPE = 'challenge_template';

    /** Expected owning component in each item. */
    private const EXPECTED_COMPONENT = 'mod_uckkchallenge';

    /** Config key storing the template index. */
    private const INDEX_CONFIG = 'challenge_template_index';

    /** Config prefix storing individual template payloads. */
    private const TEMPLATE_CONFIG_PREFIX = 'challenge_template_';

    /** Status constants. */
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const STATUS_WARNING = 'warning';

    /** Severity constants. */
    private const SEVERITY_INFO = 'info';
    private const SEVERITY_SUCCESS = 'success';
    private const SEVERITY_WARNING = 'warning';
    private const SEVERITY_ERROR = 'error';
    private const SEVERITY_BLOCKER = 'blocker';

    /** Runtime modes. */
    private const MODE_DRY_RUN = 'dry_run';
    private const MODE_APPLY = 'apply';
    private const MODE_REPORT = 'report';
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Moodle technical role shortnames allowed in participant_roles. */
    private const TECHNICAL_ROLES = [
        'manager',
        'coursecreator',
        'editingteacher',
        'teacher',
        'student',
        'uckkmanager',
        'uckkmentor',
        'uckkplayer',
        'uckkarchivist',
        'uckkinquisitor',
        'uckkcartographer',
        'uckkarchitect',
    ];

    /** Allowed challenge default visibility values. */
    private const ALLOWED_VISIBILITY = [
        'course',
        'private',
        'group',
        'institution',
        'public',
    ];

    /** Allowed provenance values. */
    private const ALLOWED_PROVENANCE = [
        'system',
        'teacher',
        'student',
        'player',
        'cohort',
        'imported',
    ];

    /** Allowed validation states. */
    private const ALLOWED_VALIDATION_STATES = [
        'unverified',
        'pending',
        'human_review_required',
        'verified',
        'rejected',
    ];

    /**
     * Validate challenge template items.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Challenge template validation completed.');

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                'challenge_templates preset is empty.'
            );
            return $this->finish_result($result);
        }

        $seenkeys = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . ($index + 1);

            if ($item['key'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Template key is required.');
            }

            if ($item['name'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Template name is required.');
            }

            if ($item['component'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Template component is required.');
            } else if ($item['component'] !== self::EXPECTED_COMPONENT) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Challenge templates must use component ' . self::EXPECTED_COMPONENT . '.',
                    ['component' => $item['component']]
                );
            }

            if (empty($item['defaults'])) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Template defaults are required.');
            }

            if ($item['key'] !== '') {
                if (isset($seenkeys[$item['key']])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        $targetkey,
                        'Duplicate challenge template key: ' . $item['key'] . '.'
                    );
                }
                $seenkeys[$item['key']] = true;
            }

            $this->validate_defaults($result, $targetkey, $item['defaults']);
            $this->validate_sections($result, $targetkey, $item['sections']);
            $this->validate_activities($result, $targetkey, $item['activities']);
            $this->validate_completion($result, $targetkey, $item['completion']);

            $this->increment($result, 'skipped');
        }

        return $this->finish_result($result);
    }

    /**
     * Apply challenge template definitions.
     *
     * Applying a template stores the definition in plugin config only. It does
     * not create Moodle courses, activities, challenges, badges, competencies,
     * archives, or user records.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $validation = $this->validate($items, $options);

        if ($this->result_has_errors($validation)) {
            return $validation;
        }

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN
            || $mode === self::MODE_REPORT
            || $mode === self::MODE_ROLLBACK_PLAN
            || !empty($options['dryrun'])
            || !empty($options['dry_run']);

        $result = $this->new_result(
            $dryrun ? 'Challenge template dry run completed.' : 'Challenge templates applied.'
        );

        $index = $this->read_template_index();

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['key'] === '') {
                $this->increment($result, 'failed');
                continue;
            }

            $existing = $this->read_template($item['key']);

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $item['key'],
                    $existing === null
                        ? 'Challenge template would be created.'
                        : 'Challenge template would be updated.',
                    ['template' => $item]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $this->write_template($item);
            $index[$item['key']] = [
                'key' => $item['key'],
                'name' => $item['name'],
                'component' => $item['component'],
                'timemodified' => time(),
            ];

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $item['key'],
                $existing === null
                    ? 'Challenge template created.'
                    : 'Challenge template updated.'
            );

            $this->increment($result, $existing === null ? 'created' : 'updated');
        }

        if (!$dryrun) {
            $this->write_template_index($index);
        }

        return $this->finish_result($result);
    }

    /**
     * Reset stored challenge template definitions.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN
            || $mode === self::MODE_REPORT
            || $mode === self::MODE_ROLLBACK_PLAN
            || !empty($options['dryrun'])
            || !empty($options['dry_run']);
        $confirmed = !empty($options['confirm']) || !empty($options['force']);

        $result = $this->new_result(
            $dryrun ? 'Challenge template reset dry run completed.' : 'Challenge templates reset.'
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Reset requires confirm or force.'
            );
            return $this->finish_result($result);
        }

        $keys = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);
            if ($item['key'] !== '') {
                $keys[] = $item['key'];
            }
        }

        if (empty($keys)) {
            $keys = array_keys($this->read_template_index());
        }

        $keys = array_values(array_unique($keys));
        $index = $this->read_template_index();

        foreach ($keys as $key) {
            $existing = $this->read_template($key);

            if ($existing === null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Challenge template is already absent.'
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $key,
                    'Challenge template would be removed.'
                );
                $this->increment($result, 'skipped');
                continue;
            }

            unset_config(self::TEMPLATE_CONFIG_PREFIX . $key, self::COMPONENT);
            unset($index[$key]);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $key,
                'Challenge template removed.'
            );
            $this->increment($result, 'updated');
        }

        if (!$dryrun) {
            $this->write_template_index($index);
        }

        return $this->finish_result($result);
    }

    /**
     * Export stored templates or built-in defaults.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = [];

        if (empty($options['defaults'])) {
            foreach (array_keys($this->read_template_index()) as $key) {
                $item = $this->read_template($key);

                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        if (empty($items)) {
            $items = $this->default_templates();
        }

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => array_values(array_map(
                fn(array $item): array => $this->normalise_item($item),
                $items
            )),
        ];
    }

    /**
     * Validate defaults payload.
     *
     * @param validation_result $result Result.
     * @param string $targetkey Template key.
     * @param array<string, mixed> $defaults Defaults.
     */
    private function validate_defaults(validation_result $result, string $targetkey, array $defaults): void {
        if (empty($defaults)) {
            return;
        }

        if (empty($defaults['challenge_type'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'defaults.challenge_type is recommended for challenge templates.'
            );
        }

        if (isset($defaults['visibility']) && !in_array((string)$defaults['visibility'], self::ALLOWED_VISIBILITY, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected defaults.visibility value: ' . (string)$defaults['visibility'] . '.',
                ['visibility' => $defaults['visibility']]
            );
        }

        if (isset($defaults['provenance']) && !in_array((string)$defaults['provenance'], self::ALLOWED_PROVENANCE, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected defaults.provenance value: ' . (string)$defaults['provenance'] . '.',
                ['provenance' => $defaults['provenance']]
            );
        }

        if (isset($defaults['validationstate']) && !in_array((string)$defaults['validationstate'], self::ALLOWED_VALIDATION_STATES, true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                $targetkey,
                'Unexpected defaults.validationstate value: ' . (string)$defaults['validationstate'] . '.',
                ['validationstate' => $defaults['validationstate']]
            );
        }

        foreach (['badges', 'competencies', 'participant_roles', 'evidence_requirements', 'evaluation_criteria'] as $field) {
            if (isset($defaults[$field]) && !is_array($defaults[$field])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'defaults.' . $field . ' must be an array.',
                    ['field' => $field]
                );
            }
        }

        if (isset($defaults['participant_roles']) && is_array($defaults['participant_roles'])) {
            foreach ($defaults['participant_roles'] as $role) {
                if (!is_string($role) || trim($role) === '') {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        $targetkey,
                        'participant_roles must contain non-empty strings.'
                    );
                    continue;
                }

                $role = trim($role);
                if (!$this->is_symbolic_role($role) && !in_array($role, self::TECHNICAL_ROLES, true)) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_WARNING,
                        $targetkey,
                        'participant_roles contains a role that is not a known Moodle technical role. Symbolic roles should remain in metadata or narrative fields.',
                        ['role' => $role]
                    );
                }
            }
        }

        foreach (['allow_ai_decision', 'ai_decision_allowed'] as $field) {
            if (!empty($defaults[$field])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'AI decision authority must not be enabled in challenge templates.',
                    ['field' => $field]
                );
            }
        }
    }

    /**
     * Validate sections payload.
     *
     * @param validation_result $result Result.
     * @param string $targetkey Template key.
     * @param array<int, mixed> $sections Sections.
     */
    private function validate_sections(validation_result $result, string $targetkey, array $sections): void {
        $seenkeys = [];
        $seennumbers = [];

        foreach ($sections as $index => $section) {
            $section = $this->normalise_array($section);

            if (!empty($section['key'])) {
                $key = (string)$section['key'];
                if (isset($seenkeys[$key])) {
                    $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Duplicate section key: ' . $key . '.');
                }
                $seenkeys[$key] = true;
            }

            if (array_key_exists('number', $section)) {
                $number = (string)$section['number'];
                if (isset($seennumbers[$number])) {
                    $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Duplicate section number: ' . $number . '.');
                }
                $seennumbers[$number] = true;
            }

            if (empty($section['key']) && !array_key_exists('number', $section)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Section #' . ($index + 1) . ' has neither key nor number.'
                );
            }
        }
    }

    /**
     * Validate activities payload.
     *
     * @param validation_result $result Result.
     * @param string $targetkey Template key.
     * @param array<int, mixed> $activities Activities.
     */
    private function validate_activities(validation_result $result, string $targetkey, array $activities): void {
        foreach ($activities as $index => $activity) {
            $activity = $this->normalise_array($activity);
            $component = (string)($activity['component'] ?? $activity['module'] ?? $activity['modname'] ?? '');

            if ($component === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Activity #' . ($index + 1) . ' does not define component/module/modname.'
                );
                continue;
            }

            if (!$this->is_valid_activity_component($component)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Activity component does not look like a Moodle activity component: ' . $component . '.',
                    ['component' => $component]
                );
            }
        }
    }

    /**
     * Validate completion payload.
     *
     * @param validation_result $result Result.
     * @param string $targetkey Template key.
     * @param mixed $completion Completion payload.
     */
    private function validate_completion(validation_result $result, string $targetkey, mixed $completion): void {
        if (!is_array($completion)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                $targetkey,
                'completion must be an object or array.'
            );
        }
    }

    /**
     * Normalise one item.
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        $item = $this->normalise_array($item);

        $key = $this->normalise_key((string)($item['key'] ?? $item['id'] ?? $item['shortname'] ?? ''));
        $defaults = $this->normalise_array($item['defaults'] ?? []);
        $metadata = $this->normalise_array($item['metadata'] ?? []);

        $metadata['managedby'] = self::COMPONENT;
        $metadata['preset'] = self::PRESET;
        $metadata['template_only'] = true;

        return [
            'key' => $key,
            'name' => trim(clean_param((string)($item['name'] ?? $item['title'] ?? $this->fallback_name($key)), PARAM_TEXT)),
            'component' => clean_param((string)($item['component'] ?? self::EXPECTED_COMPONENT), PARAM_COMPONENT),
            'description' => trim(clean_param((string)($item['description'] ?? $item['summary'] ?? ''), PARAM_TEXT)),
            'defaults' => $this->normalise_defaults($defaults),
            'sections' => $this->normalise_list($item['sections'] ?? []),
            'activities' => $this->normalise_list($item['activities'] ?? []),
            'completion' => $this->normalise_array($item['completion'] ?? []),
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise defaults.
     *
     * @param array<string, mixed> $defaults Defaults.
     * @return array<string, mixed>
     */
    private function normalise_defaults(array $defaults): array {
        $defaults = $this->normalise_array($defaults);

        foreach ([
            'requires_human_validation',
            'allow_ai_assistance',
            'allow_ai_decision',
            'archive_required',
            'ai_decision_allowed',
        ] as $field) {
            if (array_key_exists($field, $defaults)) {
                $defaults[$field] = $this->normalise_bool($defaults[$field]);
            }
        }

        foreach (['participant_roles', 'evidence_requirements', 'evaluation_criteria', 'badges', 'competencies'] as $field) {
            if (array_key_exists($field, $defaults)) {
                $defaults[$field] = $this->normalise_list($defaults[$field]);
            }
        }

        return $defaults;
    }

    /**
     * Normalise a raw value into an associative array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private function normalise_array(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $normalised = [];
        foreach ($value as $key => $entry) {
            if ($entry instanceof stdClass) {
                $entry = (array)$entry;
            }
            if (is_array($entry)) {
                $entry = $this->normalise_nested($entry);
            }
            $normalised[(string)$key] = $entry;
        }

        return $normalised;
    }

    /**
     * Normalise list payload.
     *
     * @param mixed $value Raw value.
     * @return array<int, mixed>
     */
    private function normalise_list(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            return [$value];
        }

        $items = [];
        foreach ($value as $entry) {
            if ($entry instanceof stdClass) {
                $entry = (array)$entry;
            }
            if (is_array($entry)) {
                $entry = $this->normalise_nested($entry);
            }
            $items[] = $entry;
        }

        return $items;
    }

    /**
     * Recursively normalise nested arrays.
     *
     * @param array<mixed> $value Raw array.
     * @return array<mixed>
     */
    private function normalise_nested(array $value): array {
        $out = [];

        foreach ($value as $key => $entry) {
            if ($entry instanceof stdClass) {
                $entry = (array)$entry;
            }
            if (is_array($entry)) {
                $entry = $this->normalise_nested($entry);
            }
            $out[$key] = $entry;
        }

        return $out;
    }

    /**
     * Normalise key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private function normalise_key(string $key): string {
        $key = strtolower(trim($key));
        $key = preg_replace('/^[a-z]+:/', '', $key) ?? $key;
        $key = preg_replace('/[^a-z0-9_\-]+/', '_', $key) ?? $key;
        $key = trim($key, '_-');

        return clean_param($key, PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise bool.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
        }

        return false;
    }

    /**
     * Check if a role string looks symbolic rather than Moodle-facing.
     *
     * @param string $role Role ref.
     * @return bool
     */
    private function is_symbolic_role(string $role): bool {
        return str_starts_with($role, 'symbolic:')
            || str_contains($role, 'joueur')
            || str_contains($role, 'batisseur')
            || str_contains($role, 'archiviste')
            || str_contains($role, 'inquisiteur')
            || str_contains($role, 'cartographe')
            || str_contains($role, 'architecte');
    }

    /**
     * Check if an activity component value looks valid.
     *
     * @param string $component Component/module value.
     * @return bool
     */
    private function is_valid_activity_component(string $component): bool {
        return preg_match('/^(mod_)?[a-z][a-z0-9_]*$/', $component) === 1;
    }

    /**
     * Fallback display name.
     *
     * @param string $key Template key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $key = trim(str_replace(['_', '-'], ' ', $key));
        return $key === '' ? '' : ucfirst($key);
    }

    /**
     * Read template index.
     *
     * @return array<string, array<string, mixed>>
     */
    private function read_template_index(): array {
        $raw = get_config(self::COMPONENT, self::INDEX_CONFIG);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Write template index.
     *
     * @param array<string, array<string, mixed>> $index Index.
     */
    private function write_template_index(array $index): void {
        ksort($index);
        set_config(
            self::INDEX_CONFIG,
            json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Read one stored template.
     *
     * @param string $key Template key.
     * @return array<string, mixed>|null
     */
    private function read_template(string $key): ?array {
        $raw = get_config(self::COMPONENT, self::TEMPLATE_CONFIG_PREFIX . $key);

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Store one template.
     *
     * @param array<string, mixed> $item Template item.
     */
    private function write_template(array $item): void {
        set_config(
            self::TEMPLATE_CONFIG_PREFIX . $item['key'],
            json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            self::COMPONENT
        );
    }

    /**
     * Default export fallback.
     *
     * @return array<int, array<string, mixed>>
     */
    private function default_templates(): array {
        return [
            [
                'key' => 'internal_learning',
                'name' => 'Défi d’apprentissage interne',
                'component' => self::EXPECTED_COMPONENT,
                'description' => 'Template de défi interne UCKK.',
                'defaults' => [
                    'challenge_type' => 'internal_learning',
                    'status' => 'draft',
                    'visibility' => 'course',
                    'provenance' => 'system',
                    'validationstate' => 'unverified',
                    'requires_human_validation' => true,
                    'allow_ai_assistance' => true,
                    'allow_ai_decision' => false,
                    'archive_required' => true,
                    'participant_roles' => [],
                    'evidence_requirements' => [],
                    'evaluation_criteria' => [],
                    'badges' => [],
                    'competencies' => [],
                ],
                'sections' => [],
                'activities' => [],
                'completion' => [],
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'preset' => self::PRESET,
                    'template_only' => true,
                ],
            ],
        ];
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
     * Create a validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'ok' => true,
            'haserrors' => false,
            'haswarnings' => false,
            'summary' => $summary,
            'counts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'messages' => [],
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ];

        if (method_exists(validation_result::class, 'from_array')) {
            return validation_result::from_array($data);
        }

        if (method_exists(validation_result::class, 'from_data')) {
            return validation_result::from_data($data);
        }

        return new validation_result(self::STATUS_COMPLETED, $summary, $data['metadata']);
    }

    /**
     * Add a result message.
     *
     * @param validation_result $result Result.
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

        if ($severity === self::SEVERITY_ERROR || $severity === self::SEVERITY_BLOCKER) {
            $this->increment($result, 'failed');
            $this->increment($result, 'errors');
        } else if ($severity === self::SEVERITY_WARNING) {
            $this->increment($result, 'warnings');
        }
    }

    /**
     * Increment a result count.
     *
     * @param validation_result $result Result.
     * @param string $key Counter key.
     */
    private function increment(validation_result $result, string $key): void {
        if (method_exists($result, 'increment')) {
            $result->increment($key);
        }
    }

    /**
     * Return whether a result has errors.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_errors(validation_result $result): bool {
        if (method_exists($result, 'has_errors')) {
            return (bool)$result->has_errors();
        }

        $data = $this->result_to_array($result);

        return !empty($data['haserrors'])
            || !empty($data['counts']['errors'])
            || !empty($data['counts']['failed']);
    }

    /**
     * Return whether a result has warnings.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_warnings(validation_result $result): bool {
        if (method_exists($result, 'has_warnings')) {
            return (bool)$result->has_warnings();
        }

        $data = $this->result_to_array($result);

        return !empty($data['haswarnings']) || !empty($data['counts']['warnings']);
    }

    /**
     * Convert a result to array.
     *
     * @param validation_result $result Result.
     * @return array<string, mixed>
     */
    private function result_to_array(validation_result $result): array {
        if (method_exists($result, 'to_array')) {
            $data = $result->to_array();
            return is_array($data) ? $data : [];
        }

        if (method_exists($result, 'export')) {
            $data = $result->export();
            return is_array($data) ? $data : (array)$data;
        }

        if ($result instanceof JsonSerializable) {
            $data = $result->jsonSerialize();
            return is_array($data) ? $data : (array)$data;
        }

        return get_object_vars($result);
    }

    /**
     * Finalise result status.
     *
     * @param validation_result $result Result.
     * @return validation_result
     */
    private function finish_result(validation_result $result): validation_result {
        if ($this->result_has_errors($result)) {
            if (method_exists($result, 'set_status')) {
                $result->set_status(self::STATUS_FAILED);
            }
        } else if ($this->result_has_warnings($result)) {
            if (method_exists($result, 'set_status')) {
                $result->set_status(self::STATUS_WARNING);
            }
        } else if (method_exists($result, 'set_status')) {
            $result->set_status(self::STATUS_COMPLETED);
        }

        return $result;
    }
}