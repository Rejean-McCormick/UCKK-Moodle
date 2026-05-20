<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability assignment preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Seeds role-to-capability assignments from capabilities.json.
 *
 * This handler does not declare new Moodle capabilities. Capability declaration
 * remains owned by each plugin's db/access.php file and Moodle upgrade.
 *
 * Expected item shape:
 * [
 *     'role' => 'uckkmanager',
 *     'capability' => 'local/uckk:viewcampus',
 *     'permission' => 'allow',
 *     'context' => 'system',
 *     'component' => 'local_uckk',
 *     'metadata' => []
 * ]
 */
final class capability_seed {
    /** Component owning this seeder. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'capabilities';

    /** Target type for logs/results. */
    private const TARGET_TYPE = 'capability';

    /** Seed schema id. */
    private const SCHEMA = 'uckkseed.preset.v1';

    /** Seed preset version. */
    private const VERSION = 2026051200;

    /** Mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    private const MODE_APPLY = 'apply';

    /** Mode: report. */
    private const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Validation severity: info. */
    private const SEVERITY_INFO = 'info';

    /** Validation severity: success. */
    private const SEVERITY_SUCCESS = 'success';

    /** Validation severity: warning. */
    private const SEVERITY_WARNING = 'warning';

    /** Validation severity: error. */
    private const SEVERITY_ERROR = 'error';

    /** Validation severity: blocker. */
    private const SEVERITY_BLOCKER = 'blocker';

    /** Permission value: allow. */
    private const PERMISSION_ALLOW = 'allow';

    /** Permission value: prevent. */
    private const PERMISSION_PREVENT = 'prevent';

    /** Permission value: prohibit. */
    private const PERMISSION_PROHIBIT = 'prohibit';

    /** Permission value: inherit. */
    private const PERMISSION_INHERIT = 'inherit';

    /**
     * Technical UCKK role shortnames.
     */
    private const TECHNICAL_ROLES = [
        'uckkmanager',
        'uckkmentor',
        'uckkplayer',
        'uckkarchivist',
        'uckkinquisitor',
        'uckkobserver',
        'uckkpublicguest',
    ];

    /**
     * Capability prefixes allowed in the UCKK registry.
     */
    private const ALLOWED_CAPABILITY_PREFIXES = [
        'local/uckk:',
        'format/uckk:',
        'block/uckk_dashboard:',
        'mod/uckkchallenge:',
        'mod/uckkassembly:',
        'mod/uckkarchive:',
        'tool/uckkseed:',
        'tool/uckkintegrity:',
        'report/uckk:',
        'aiprovider/uckk:',
    ];

    /**
     * Cached Moodle capability registry.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $capabilityregistry = null;

    /**
     * Validate capability assignment preset items.
     *
     * @param array<int, array<string, mixed>|stdClass|string> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => 'Capability preset validation complete.',
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
                'mode' => $options['mode'] ?? self::MODE_REPORT,
            ],
        ]);

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Capability preset is empty.',
                '',
                []
            );
            $this->finalise_result($result);
            return $result;
        }

        $seen = [];

        foreach ($items as $position => $item) {
            $row = $this->normalise_item($item);
            $targetkey = $this->target_key($row, $position);

            if ($row['role'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Capability assignment is missing role.',
                    $targetkey,
                    $row
                );
            } else if (!in_array($row['role'], self::TECHNICAL_ROLES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Capability assignment references a non-canonical UCKK technical role: ' . $row['role'],
                    $targetkey,
                    $row
                );
            }

            if ($row['capability'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Capability assignment is missing capability.',
                    $targetkey,
                    $row
                );
                continue;
            }

            if (!$this->is_allowed_uckk_capability($row['capability'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Capability is not part of the UCKK capability allow-list: ' . $row['capability'],
                    $targetkey,
                    $row
                );
            }

            if (!$this->capability_exists($row['capability'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Capability is not declared in Moodle capability registry: ' . $row['capability'],
                    $targetkey,
                    [
                        ...$row,
                        'reason' => 'Check the owning plugin db/access.php and run Moodle upgrade.',
                    ]
                );
            }

            if (!in_array($row['permission'], $this->allowed_permissions(), true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Invalid capability permission "' . $row['permission'] . '" for ' . $row['capability'],
                    $targetkey,
                    $row
                );
            }

            if ($this->context_level_to_constant($row['context']) === null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Invalid capability context "' . $row['context'] . '" for ' . $row['capability'],
                    $targetkey,
                    $row
                );
            }

            $expectedcomponent = $this->capability_component($row['capability']);

            if ($row['component'] !== '' && $expectedcomponent !== '' && $row['component'] !== $expectedcomponent) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Capability component mismatch for ' . $row['capability'],
                    $targetkey,
                    [
                        ...$row,
                        'expectedcomponent' => $expectedcomponent,
                    ]
                );
            }

            $declaredcontext = $this->declared_capability_context($row['capability']);

            if ($declaredcontext !== null && $declaredcontext !== $row['context']) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Capability context differs from db/access.php declaration: ' . $row['capability'],
                    $targetkey,
                    [
                        ...$row,
                        'presetcontext' => $row['context'],
                        'declaredcontext' => $declaredcontext,
                    ]
                );
            }

            $dedupekey = $row['role'] . '|' . $row['capability'] . '|' . $row['context'];

            if (isset($seen[$dedupekey])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Duplicate capability assignment: ' . $dedupekey,
                    $targetkey,
                    $row
                );
            }

            $seen[$dedupekey] = true;

            $this->increment($result, 'skipped');
        }

        $this->finalise_result($result);

        return $result;
    }

    /**
     * Apply capability assignments.
     *
     * Capabilities are assigned at system context. The preset context is kept as
     * an audit/validation field. This mirrors role_seed behaviour and avoids
     * requiring one concrete course/module/block instance per canonical role
     * capability.
     *
     * @param array<int, array<string, mixed>|stdClass|string> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $dryrun = $this->is_dry_run($options);

        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $result = validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => $dryrun
                ? 'Capability assignment dry run complete.'
                : 'Capability assignments seeded.',
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
                'mode' => $options['mode'] ?? self::MODE_APPLY,
            ],
        ]);

        foreach ($items as $position => $item) {
            $row = $this->normalise_item($item);
            $targetkey = $this->target_key($row, $position);

            if ($row['role'] === '' || $row['capability'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Cannot apply incomplete capability assignment.',
                    $targetkey,
                    $row
                );
                continue;
            }

            $role = $this->get_role($row['role']);

            if (!$role) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Role does not exist yet: ' . $row['role'],
                    $targetkey,
                    [
                        ...$row,
                        'hint' => 'Run/apply roles before capabilities.',
                    ]
                );
                continue;
            }

            $context = $this->context_for_capability_assignment($row['context']);
            $permissionconstant = $this->permission_to_constant($row['permission']);

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Would assign capability ' . $row['capability'] . ' to role ' . $row['role'],
                    $targetkey,
                    [
                        ...$row,
                        'roleid' => (int)$role->id,
                        'contextid' => $context->id,
                        'permissionconstant' => $permissionconstant,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            assign_capability(
                $row['capability'],
                $permissionconstant,
                (int)$role->id,
                $context->id,
                true
            );

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                'Assigned capability ' . $row['capability'] . ' to role ' . $row['role'],
                $targetkey,
                [
                    ...$row,
                    'roleid' => (int)$role->id,
                    'contextid' => $context->id,
                ]
            );

            $this->increment($result, 'updated');
        }

        $this->clear_access_caches();
        $this->finalise_result($result);

        return $result;
    }

    /**
     * Reset capability assignments.
     *
     * Without confirm+force this only reports what would be removed.
     *
     * @param array<int, array<string, mixed>|stdClass|string> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $dryrun = $this->is_dry_run($options);
        $confirmed = !empty($options['confirm']);
        $force = !empty($options['force']);
        $canwrite = !$dryrun && $confirmed && $force;

        $result = validation_result::from_data([
            'status' => validation_result::STATUS_COMPLETED,
            'ok' => true,
            'summary' => $canwrite
                ? 'Capability assignments reset complete.'
                : 'Capability assignment reset dry run complete.',
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
                'mode' => $options['mode'] ?? self::MODE_DRY_RUN,
            ],
        ]);

        if (!$canwrite) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Reset is dry-run only unless confirm and force are both supplied.',
                '',
                [
                    'dryrun' => $dryrun,
                    'confirm' => $confirmed,
                    'force' => $force,
                ]
            );
        }

        foreach ($items as $position => $item) {
            $row = $this->normalise_item($item);
            $targetkey = $this->target_key($row, $position);

            if ($row['role'] === '' || $row['capability'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Cannot reset incomplete capability assignment.',
                    $targetkey,
                    $row
                );
                continue;
            }

            $role = $this->get_role($row['role']);

            if (!$role) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Role not found; capability reset skipped: ' . $row['role'],
                    $targetkey,
                    $row
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $context = $this->context_for_capability_assignment($row['context']);

            if (!$canwrite) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Would unassign capability ' . $row['capability'] . ' from role ' . $row['role'],
                    $targetkey,
                    [
                        ...$row,
                        'roleid' => (int)$role->id,
                        'contextid' => $context->id,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            unassign_capability(
                $row['capability'],
                (int)$role->id,
                $context->id
            );

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                'Unassigned capability ' . $row['capability'] . ' from role ' . $row['role'],
                $targetkey,
                [
                    ...$row,
                    'roleid' => (int)$role->id,
                    'contextid' => $context->id,
                ]
            );

            $this->increment($result, 'updated');
        }

        $this->clear_access_caches();
        $this->finalise_result($result);

        return $result;
    }

    /**
     * Export current UCKK role capability assignments.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];
        $systemcontext = context_system::instance();

        foreach (self::TECHNICAL_ROLES as $shortname) {
            $role = $this->get_role($shortname);

            if (!$role) {
                continue;
            }

            $rows = $DB->get_records(
                'role_capabilities',
                [
                    'roleid' => (int)$role->id,
                    'contextid' => $systemcontext->id,
                ],
                'capability ASC'
            );

            foreach ($rows as $row) {
                $capability = (string)$row->capability;

                if (!$this->is_allowed_uckk_capability($capability)) {
                    continue;
                }

                $items[] = [
                    'role' => $shortname,
                    'capability' => $capability,
                    'permission' => $this->permission_constant_to_name((int)$row->permission),
                    'context' => $this->declared_capability_context($capability) ?? 'system',
                    'component' => $this->capability_component($capability),
                    'metadata' => [
                        'exportedby' => self::COMPONENT,
                        'timeexported' => time(),
                    ],
                ];
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
     * Normalise one capability assignment row.
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        if (is_string($item)) {
            $capability = clean_param($item, PARAM_CAPABILITY);

            return [
                'role' => '',
                'capability' => $capability,
                'permission' => self::PERMISSION_ALLOW,
                'context' => 'system',
                'component' => $this->capability_component($capability),
                'metadata' => [],
            ];
        }

        if ($item instanceof stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $role = clean_param((string)($item['role'] ?? $item['shortname'] ?? ''), PARAM_ALPHANUMEXT);
        $capability = clean_param((string)($item['capability'] ?? ''), PARAM_CAPABILITY);
        $permission = clean_param((string)($item['permission'] ?? self::PERMISSION_ALLOW), PARAM_ALPHANUMEXT);
        $context = $this->normalise_context_level($item['context'] ?? 'system');
        $component = clean_param(
            (string)($item['component'] ?? $this->capability_component($capability)),
            PARAM_COMPONENT
        );

        $metadata = $item['metadata'] ?? [];

        if ($metadata instanceof stdClass) {
            $metadata = (array)$metadata;
        }

        if (!is_array($metadata)) {
            $metadata = [];
        }

        return [
            'role' => $role,
            'capability' => $capability,
            'permission' => $permission,
            'context' => $context,
            'component' => $component,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build stable target key for messages/logging.
     *
     * @param array<string, mixed> $row Normalised row.
     * @param int|string $position Position fallback.
     * @return string
     */
    private function target_key(array $row, int|string $position = 0): string {
        if (($row['role'] ?? '') !== '' && ($row['capability'] ?? '') !== '') {
            return $row['role'] . ':' . $row['capability'];
        }

        if (($row['capability'] ?? '') !== '') {
            return (string)$row['capability'];
        }

        return 'row_' . (string)$position;
    }

    /**
     * Convert context aliases to canonical seed names.
     *
     * @param mixed $contextlevel Raw context level.
     * @return string
     */
    private function normalise_context_level(mixed $contextlevel): string {
        if (is_int($contextlevel)) {
            return match ($contextlevel) {
                CONTEXT_SYSTEM => 'system',
                CONTEXT_COURSECAT => 'course_category',
                CONTEXT_COURSE => 'course',
                CONTEXT_MODULE => 'module',
                CONTEXT_BLOCK => 'block',
                CONTEXT_USER => 'user',
                default => '',
            };
        }

        $contextlevel = strtolower(trim((string)$contextlevel));
        $contextlevel = str_replace(['-', ' '], '_', $contextlevel);

        return match ($contextlevel) {
            'system', 'context_system' => 'system',
            'coursecat', 'course_category', 'coursecategory', 'category', 'context_coursecat' => 'course_category',
            'course', 'context_course' => 'course',
            'module', 'cm', 'activity', 'context_module' => 'module',
            'block', 'context_block' => 'block',
            'user', 'context_user' => 'user',
            default => clean_param($contextlevel, PARAM_ALPHANUMEXT),
        };
    }

    /**
     * Convert canonical context name to Moodle constant.
     *
     * @param string $contextlevel Context level.
     * @return int|null
     */
    private function context_level_to_constant(string $contextlevel): ?int {
        return match ($this->normalise_context_level($contextlevel)) {
            'system' => CONTEXT_SYSTEM,
            'course_category' => CONTEXT_COURSECAT,
            'course' => CONTEXT_COURSE,
            'module' => CONTEXT_MODULE,
            'block' => CONTEXT_BLOCK,
            'user' => CONTEXT_USER,
            default => null,
        };
    }

    /**
     * Return context used for capability assignment.
     *
     * @param string $contextlevel Context level.
     * @return \context
     */
    private function context_for_capability_assignment(string $contextlevel): \context {
        return context_system::instance();
    }

    /**
     * Convert permission name to Moodle constant.
     *
     * @param string $permission Permission name.
     * @return int
     */
    private function permission_to_constant(string $permission): int {
        return match ($permission) {
            self::PERMISSION_PREVENT => CAP_PREVENT,
            self::PERMISSION_PROHIBIT => CAP_PROHIBIT,
            self::PERMISSION_INHERIT => CAP_INHERIT,
            default => CAP_ALLOW,
        };
    }

    /**
     * Convert Moodle permission constant to seed permission name.
     *
     * @param int $permission Permission constant.
     * @return string
     */
    private function permission_constant_to_name(int $permission): string {
        return match ($permission) {
            CAP_PREVENT => self::PERMISSION_PREVENT,
            CAP_PROHIBIT => self::PERMISSION_PROHIBIT,
            CAP_INHERIT => self::PERMISSION_INHERIT,
            default => self::PERMISSION_ALLOW,
        };
    }

    /**
     * Return supported permission names.
     *
     * @return string[]
     */
    private function allowed_permissions(): array {
        return [
            self::PERMISSION_ALLOW,
            self::PERMISSION_PREVENT,
            self::PERMISSION_PROHIBIT,
            self::PERMISSION_INHERIT,
        ];
    }

    /**
     * Return whether capability has an allowed UCKK prefix.
     *
     * @param string $capability Capability name.
     * @return bool
     */
    private function is_allowed_uckk_capability(string $capability): bool {
        foreach (self::ALLOWED_CAPABILITY_PREFIXES as $prefix) {
            if (str_starts_with($capability, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return whether capability exists in Moodle's registry.
     *
     * @param string $capability Capability name.
     * @return bool
     */
    private function capability_exists(string $capability): bool {
        $registry = $this->get_capability_registry();

        return array_key_exists($capability, $registry);
    }

    /**
     * Return canonical context from db/access.php for a capability.
     *
     * @param string $capability Capability name.
     * @return string|null
     */
    private function declared_capability_context(string $capability): ?string {
        $registry = $this->get_capability_registry();

        if (empty($registry[$capability]['contextlevel'])) {
            return null;
        }

        return $this->normalise_context_level((int)$registry[$capability]['contextlevel']);
    }

    /**
     * Return Moodle capability registry.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_capability_registry(): array {
        if ($this->capabilityregistry !== null) {
            return $this->capabilityregistry;
        }

        $capabilities = get_all_capabilities();

        if (!is_array($capabilities)) {
            $capabilities = [];
        }

        $this->capabilityregistry = $capabilities;

        return $this->capabilityregistry;
    }

    /**
     * Extract Frankenstyle component from capability name.
     *
     * @param string $capability Capability name.
     * @return string
     */
    private function capability_component(string $capability): string {
        if (!str_contains($capability, ':')) {
            return '';
        }

        $prefix = explode(':', $capability, 2)[0];

        return str_replace('/', '_', $prefix);
    }

    /**
     * Get Moodle role record by shortname.
     *
     * @param string $shortname Role shortname.
     * @return stdClass|null
     */
    private function get_role(string $shortname): ?stdClass {
        global $DB;

        if ($shortname === '') {
            return null;
        }

        $role = $DB->get_record('role', ['shortname' => $shortname], '*', IGNORE_MISSING);

        return $role ?: null;
    }

    /**
     * Whether runtime options request a dry run/report-only mode.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return bool
     */
    private function is_dry_run(array $options): bool {
        return !empty($options['dryrun'])
            || !empty($options['dry_run'])
            || (($options['mode'] ?? '') === self::MODE_DRY_RUN)
            || (($options['mode'] ?? '') === self::MODE_REPORT)
            || (($options['mode'] ?? '') === self::MODE_ROLLBACK_PLAN);
    }

    /**
     * Add message to result.
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
        }
    }

    /**
     * Increment result counter.
     *
     * @param validation_result $result Result.
     * @param string $counter Counter key.
     */
    private function increment(validation_result $result, string $counter): void {
        $result->increment($counter);
    }

    /**
     * Clear access caches after capability changes.
     */
    private function clear_access_caches(): void {
        if (function_exists('accesslib_clear_all_caches')) {
            accesslib_clear_all_caches(false);
            return;
        }

        if (function_exists('accesslib_clear_all_caches_for_unit_testing')) {
            accesslib_clear_all_caches_for_unit_testing();
        }
    }

    /**
     * Finalise result status flags.
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
}