<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Report preset seeder for the UCKK seed tool.
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
 * Seed handler for reports.json.
 *
 * This class validates and registers seed-managed report definitions.
 *
 * It does not:
 * - execute reports;
 * - own report query logic;
 * - bypass report permissions;
 * - create report result data;
 * - export report data.
 *
 * The actual reporting implementation remains owned by report_uckk and its
 * report source/export classes.
 */
final class report_seed {
    /** Preset id. */
    public const PRESET = 'reports';

    /** Component. */
    public const COMPONENT = 'tool_uckkseed';

    /** Target component. */
    public const TARGET_COMPONENT = 'report_uckk';

    /** Seed object type. */
    public const TARGET_TYPE = 'report';

    /** Config key storing report definitions. */
    public const CONFIG_REPORTS = 'seeded_reports';

    /** Config key storing report definition index. */
    public const CONFIG_REPORT_INDEX = 'seeded_report_index';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Validation severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Validation severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Validation severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Validation severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Validation severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /**
     * Validate report preset items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => '',
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ]);

        if (empty($items)) {
            $this->add_message($result,
                self::SEVERITY_WARNING,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                '',
                get_string('presetempty', 'tool_uckkseed')
            );

            return $result;
        }

        $seen = [];

        foreach ($items as $position => $item) {
            $row = $this->normalise_item($item);
            $key = $row['key'];

            if ($key === '') {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    '',
                    get_string('reportseed:error_missingkey', 'tool_uckkseed', $position + 1)
                );
                continue;
            }

            if (isset($seen[$key])) {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_duplicatekey', 'tool_uckkseed', $key)
                );
                continue;
            }

            $seen[$key] = true;

            if ($row['name'] === '') {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_missingname', 'tool_uckkseed', $key)
                );
            }

            if ($row['component'] === '') {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_missingcomponent', 'tool_uckkseed', $key)
                );
            } else if (!$this->component_exists($row['component'])) {
                $this->add_message($result,
                    self::SEVERITY_WARNING,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:warning_componentmissing', 'tool_uckkseed', $row['component'])
                );
            }

            if ($row['capability'] === '') {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_missingcapability', 'tool_uckkseed', $key)
                );
            } else if (!$this->is_allowed_report_capability($row['capability'])) {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_invalidcapability', 'tool_uckkseed', $row['capability'])
                );
            }

            if ($row['source'] === '') {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_missingsource', 'tool_uckkseed', $key)
                );
            } else if (!$this->is_allowed_report_source($row['source'])) {
                $this->add_message($result,
                    self::SEVERITY_WARNING,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:warning_unknownsource', 'tool_uckkseed', $row['source'])
                );
            }

            if (!$this->is_boolish($row['enabled'])) {
                $this->add_message($result,
                    self::SEVERITY_ERROR,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('reportseed:error_invalidenabled', 'tool_uckkseed', $key)
                );
            }
        }

        if (!$result->has_errors()) {
            $this->add_message($result,
                self::SEVERITY_SUCCESS,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                '',
                get_string('reportseed:validationok', 'tool_uckkseed', count($items))
            );
        }

        return $result;
    }

    /**
     * Apply report preset items idempotently.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $result = $this->validate($items, $options);

        if ($result->has_errors()) {
            return $result;
        }

        $current = $this->get_seeded_reports();
        $index = $this->get_seeded_report_index();

        foreach ($items as $item) {
            $row = $this->normalise_item($item);
            $key = $row['key'];

            if ($key === '') {
                continue;
            }

            $existing = $current[$key] ?? null;
            $canonical = $this->to_storage_row($row);

            if ($existing !== null && $this->same_report_definition($existing, $canonical)) {
                $result->increment('skipped');
                $this->add_message($result,
                    self::SEVERITY_INFO,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('idempotentmatch', 'tool_uckkseed')
                );
                continue;
            }

            if ($mode === self::MODE_DRY_RUN || $mode === self::MODE_REPORT || $mode === self::MODE_ROLLBACK_PLAN) {
                $result->increment($existing === null ? 'created' : 'updated');
                $this->add_message($result,
                    self::SEVERITY_INFO,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    $existing === null
                        ? get_string('dryrunwouldcreate', 'tool_uckkseed')
                        : get_string('dryrunwouldupdate', 'tool_uckkseed'),
                    [
                        'report' => $canonical,
                    ]
                );
                continue;
            }

            $current[$key] = $canonical;
            $index[$key] = [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
                'targetkey' => $key,
                'targetcomponent' => $canonical['component'],
                'timecreated' => $existing['timecreated'] ?? time(),
                'timemodified' => time(),
            ];

            $result->increment($existing === null ? 'created' : 'updated');
            $this->add_message($result,
                self::SEVERITY_SUCCESS,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                $key,
                $existing === null
                    ? get_string('reportseed:created', 'tool_uckkseed', $key)
                    : get_string('reportseed:updated', 'tool_uckkseed', $key)
            );
        }

        if ($mode === self::MODE_APPLY) {
            $this->set_seeded_reports($current);
            $this->set_seeded_report_index($index);
        }

        return $result;
    }

    /**
     * Reset seed-managed report definitions.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset rows used to determine managed keys.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $confirm = !empty($options['confirm']) || !empty($options['force']);
        $result = validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => '',
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ]);

        if (!$confirm && $mode === self::MODE_APPLY) {
            $this->add_message($result,
                self::SEVERITY_BLOCKER,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                '',
                get_string('error_missingconfirmation', 'tool_uckkseed')
            );

            return $result;
        }

        $current = $this->get_seeded_reports();
        $index = $this->get_seeded_report_index();
        $keys = $this->get_reset_keys($items, $options);

        foreach ($keys as $key) {
            if (!isset($current[$key]) && !isset($index[$key])) {
                $result->increment('skipped');
                $this->add_message($result,
                    self::SEVERITY_INFO,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('stepskipped', 'tool_uckkseed')
                );
                continue;
            }

            if ($mode === self::MODE_DRY_RUN || $mode === self::MODE_REPORT || $mode === self::MODE_ROLLBACK_PLAN) {
                $result->increment('updated');
                $this->add_message($result,
                    self::SEVERITY_INFO,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $key,
                    get_string('dryrunwouldreset', 'tool_uckkseed')
                );
                continue;
            }

            unset($current[$key], $index[$key]);

            $result->increment('updated');
            $this->add_message($result,
                self::SEVERITY_SUCCESS,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                $key,
                get_string('reportseed:reset', 'tool_uckkseed', $key)
            );
        }

        if ($mode === self::MODE_APPLY) {
            $this->set_seeded_reports($current);
            $this->set_seeded_report_index($index);
        }

        return $result;
    }

    /**
     * Export current or default report preset rows.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $reports = $this->get_seeded_reports();

        if (empty($reports) || !empty($options['defaults'])) {
            $reports = $this->get_default_reports();
        }

        $items = [];

        foreach ($reports as $report) {
            $items[] = $this->to_preset_row($report);
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp($a['key'], $b['key']);
        });

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $items,
        ];
    }

    /**
     * Add a result message using the current validation_result API.
     *
     * This wrapper keeps this handler readable while adapting the older
     * report_seed call shape to validation_result::add_message().
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $component Component.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param string $message Message text.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $component,
        string $preset,
        string $targettype,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $result->add_message(
            $severity,
            $message,
            $component,
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Normalise a report preset item.
     *
     * @param array<string, mixed>|stdClass $item Raw row.
     * @return array<string, mixed>
     */
    private function normalise_item(array|stdClass $item): array {
        $row = (array)$item;

        $key = $this->clean_key((string)($row['key'] ?? $row['shortname'] ?? ''));
        $component = clean_param((string)($row['component'] ?? self::TARGET_COMPONENT), PARAM_COMPONENT);
        $capability = clean_param((string)($row['capability'] ?? 'report/uckk:view'), PARAM_CAPABILITY);
        $source = $this->normalise_report_source((string)($row['source'] ?? $key));
        $enabled = $row['enabled'] ?? true;

        return [
            'key' => $key,
            'name' => trim(clean_param((string)($row['name'] ?? ''), PARAM_TEXT)),
            'shortname' => $this->clean_key((string)($row['shortname'] ?? $key)),
            'description' => trim(clean_param((string)($row['description'] ?? ''), PARAM_TEXT)),
            'component' => $component,
            'capability' => $capability,
            'source' => $source,
            'enabled' => $enabled,
            'sortorder' => max(0, (int)($row['sortorder'] ?? 0)),
            'metadata' => $this->normalise_metadata($row['metadata'] ?? []),
        ];
    }

    /**
     * Convert normalised row to persistent storage row.
     *
     * @param array<string, mixed> $row Normalised row.
     * @return array<string, mixed>
     */
    private function to_storage_row(array $row): array {
        return [
            'key' => $row['key'],
            'name' => $row['name'],
            'shortname' => $row['shortname'] !== '' ? $row['shortname'] : $row['key'],
            'description' => $row['description'],
            'component' => $row['component'] !== '' ? $row['component'] : self::TARGET_COMPONENT,
            'capability' => $row['capability'] !== '' ? $row['capability'] : 'report/uckk:view',
            'source' => $row['source'],
            'enabled' => $this->to_bool($row['enabled']),
            'sortorder' => (int)$row['sortorder'],
            'seededby' => self::COMPONENT,
            'preset' => self::PRESET,
            'timecreated' => time(),
            'timemodified' => time(),
            'metadata' => $row['metadata'],
        ];
    }

    /**
     * Convert storage row to preset row.
     *
     * @param array<string, mixed>|stdClass $report Stored report row.
     * @return array<string, mixed>
     */
    private function to_preset_row(array|stdClass $report): array {
        $row = (array)$report;

        return [
            'key' => $this->clean_key((string)($row['key'] ?? '')),
            'name' => (string)($row['name'] ?? ''),
            'shortname' => $this->clean_key((string)($row['shortname'] ?? $row['key'] ?? '')),
            'description' => (string)($row['description'] ?? ''),
            'component' => clean_param((string)($row['component'] ?? self::TARGET_COMPONENT), PARAM_COMPONENT),
            'capability' => clean_param((string)($row['capability'] ?? 'report/uckk:view'), PARAM_CAPABILITY),
            'source' => $this->normalise_report_source((string)($row['source'] ?? $row['key'] ?? '')),
            'enabled' => $this->to_bool($row['enabled'] ?? true),
            'sortorder' => max(0, (int)($row['sortorder'] ?? 0)),
            'metadata' => $this->normalise_metadata($row['metadata'] ?? []),
        ];
    }

    /**
     * Return keys to reset.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return string[]
     */
    private function get_reset_keys(array $items, array $options): array {
        if (!empty($options['target'])) {
            return [$this->clean_key((string)$options['target'])];
        }

        if (!empty($options['targetkey'])) {
            return [$this->clean_key((string)$options['targetkey'])];
        }

        $keys = [];

        foreach ($items as $item) {
            $row = $this->normalise_item($item);

            if ($row['key'] !== '') {
                $keys[] = $row['key'];
            }
        }

        if (!empty($keys)) {
            return array_values(array_unique($keys));
        }

        return array_keys($this->get_seeded_reports());
    }

    /**
     * Return currently stored seed-managed report definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_seeded_reports(): array {
        $json = get_config(self::COMPONENT, self::CONFIG_REPORTS);

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        $reports = [];

        foreach ($decoded as $key => $report) {
            if (!is_array($report)) {
                continue;
            }

            $normalised = $this->to_preset_row($report);

            if ($normalised['key'] === '') {
                $normalised['key'] = $this->clean_key((string)$key);
            }

            if ($normalised['key'] !== '') {
                $reports[$normalised['key']] = $normalised;
            }
        }

        return $reports;
    }

    /**
     * Store seed-managed report definitions.
     *
     * @param array<string, array<string, mixed>> $reports Report definitions.
     */
    private function set_seeded_reports(array $reports): void {
        ksort($reports);
        set_config(self::CONFIG_REPORTS, json_encode($reports, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::COMPONENT);
    }

    /**
     * Return report seed index.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_seeded_report_index(): array {
        $json = get_config(self::COMPONENT, self::CONFIG_REPORT_INDEX);

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Store report seed index.
     *
     * @param array<string, array<string, mixed>> $index Index rows.
     */
    private function set_seeded_report_index(array $index): void {
        ksort($index);
        set_config(self::CONFIG_REPORT_INDEX, json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::COMPONENT);
    }

    /**
     * Compare two report definitions, ignoring volatile timestamps.
     *
     * @param array<string, mixed> $existing Existing definition.
     * @param array<string, mixed> $candidate Candidate definition.
     * @return bool
     */
    private function same_report_definition(array $existing, array $candidate): bool {
        unset(
            $existing['timecreated'],
            $existing['timemodified'],
            $candidate['timecreated'],
            $candidate['timemodified']
        );

        ksort($existing);
        ksort($candidate);

        return $existing == $candidate;
    }

    /**
     * Return default reports when no stored reports exist.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_default_reports(): array {
        $reports = [
            [
                'key' => 'player_progress',
                'name' => get_string('reportseed:default_player_progress', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_player_progress_desc', 'tool_uckkseed'),
                'source' => 'player_progress',
                'capability' => 'report/uckk:view',
                'sortorder' => 10,
            ],
            [
                'key' => 'cohort_progress',
                'name' => get_string('reportseed:default_cohort_progress', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_cohort_progress_desc', 'tool_uckkseed'),
                'source' => 'cohort_progress',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 20,
            ],
            [
                'key' => 'program_progress',
                'name' => get_string('reportseed:default_program_progress', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_program_progress_desc', 'tool_uckkseed'),
                'source' => 'program_progress',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 30,
            ],
            [
                'key' => 'competency_matrix',
                'name' => get_string('reportseed:default_competency_matrix', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_competency_matrix_desc', 'tool_uckkseed'),
                'source' => 'competency_matrix',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 40,
            ],
            [
                'key' => 'badge_awards',
                'name' => get_string('reportseed:default_badge_awards', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_badge_awards_desc', 'tool_uckkseed'),
                'source' => 'badge_awards',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 50,
            ],
            [
                'key' => 'challenge_status',
                'name' => get_string('reportseed:default_challenge_status', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_challenge_status_desc', 'tool_uckkseed'),
                'source' => 'challenge_status',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 60,
            ],
            [
                'key' => 'assembly_decisions',
                'name' => get_string('reportseed:default_assembly_decisions', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_assembly_decisions_desc', 'tool_uckkseed'),
                'source' => 'assembly_decisions',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 70,
            ],
            [
                'key' => 'archive_production',
                'name' => get_string('reportseed:default_archive_production', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_archive_production_desc', 'tool_uckkseed'),
                'source' => 'archive_production',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 80,
            ],
            [
                'key' => 'integrity_cases',
                'name' => get_string('reportseed:default_integrity_cases', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_integrity_cases_desc', 'tool_uckkseed'),
                'source' => 'integrity_cases',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 90,
            ],
            [
                'key' => 'seed_execution',
                'name' => get_string('reportseed:default_seed_execution', 'tool_uckkseed'),
                'description' => get_string('reportseed:default_seed_execution_desc', 'tool_uckkseed'),
                'source' => 'seed_execution',
                'capability' => 'report/uckk:viewall',
                'sortorder' => 100,
            ],
        ];

        $indexed = [];

        foreach ($reports as $report) {
            $row = $this->to_storage_row($this->normalise_item($report));
            $indexed[$row['key']] = $row;
        }

        return $indexed;
    }

    /**
     * Normalise report source names while allowing documented aliases.
     *
     * @param string $source Raw source.
     * @return string
     */
    private function normalise_report_source(string $source): string {
        $source = $this->clean_key($source);

        $aliases = [
            'joueur_progress' => 'player_progress',
            'competency_report' => 'competency_matrix',
            'badge_report' => 'badge_awards',
            'challenge_report' => 'challenge_status',
            'assembly_report' => 'assembly_decisions',
            'archive_report' => 'archive_production',
            'integrity_report' => 'integrity_cases',
        ];

        return $aliases[$source] ?? $source;
    }

    /**
     * Return whether a report source is known.
     *
     * @param string $source Source.
     * @return bool
     */
    private function is_allowed_report_source(string $source): bool {
        return in_array($source, [
            'player_progress',
            'cohort_progress',
            'program_progress',
            'pathway_progress',
            'competency_matrix',
            'badge_awards',
            'challenge_status',
            'challenge_evidence',
            'assembly_decisions',
            'archive_production',
            'integrity_cases',
            'ai_usage',
            'privacy_exports',
            'seed_execution',
        ], true);
    }

    /**
     * Return whether a capability is an allowed report capability.
     *
     * @param string $capability Capability.
     * @return bool
     */
    private function is_allowed_report_capability(string $capability): bool {
        return in_array($capability, [
            'report/uckk:view',
            'report/uckk:viewall',
            'report/uckk:export',
        ], true);
    }

    /**
     * Return whether component appears to exist.
     *
     * @param string $component Component name.
     * @return bool
     */
    private function component_exists(string $component): bool {
        if ($component === '') {
            return false;
        }

        [$type, $name] = core_component::normalize_component($component);

        if ($type === 'core' || $name === null) {
            return $component === 'core';
        }

        return core_component::get_plugin_directory($type, $name) !== null;
    }

    /**
     * Clean a stable seed key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private function clean_key(string $key): string {
        $key = strtolower(trim($key));
        $key = str_replace(['-', ' ', ':', '/'], '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key) ?? '';

        return trim($key, '_');
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = $this->clean_key($mode);

        return in_array($mode, [
            self::MODE_DRY_RUN,
            self::MODE_APPLY,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ], true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Return whether a value is boolean-like.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private function is_boolish(mixed $value): bool {
        if (is_bool($value) || is_int($value)) {
            return true;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['0', '1', 'true', 'false', 'yes', 'no'], true);
        }

        return false;
    }

    /**
     * Convert a bool-like value to bool.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private function to_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }

        return false;
    }
}
