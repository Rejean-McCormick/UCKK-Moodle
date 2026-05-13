<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Role preset seeder for UCKK-Moodle.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

defined('MOODLE_INTERNAL') || die();

use context_system;
use stdClass;

/**
 * Seeds UCKK technical Moodle roles and capability assignments.
 *
 * This class creates permission roles only. It must not create Moodle roles for
 * symbolic UCKK identities such as joueur_lucide, batisseur, cartographe,
 * architecte_sens, architecte_opportunites, or gardien_systemes_vivants.
 *
 * Preset item shape:
 *
 * [
 *     'shortname' => 'uckkmanager',
 *     'name' => 'Gestionnaire UCKK',
 *     'description' => '...',
 *     'archetype' => 'manager',
 *     'contextlevels' => ['system', 'course_category', 'course'],
 *     'capabilities' => [
 *         [
 *             'capability' => 'local/uckk:viewcampus',
 *             'permission' => 'allow',
 *             'context' => 'system',
 *         ],
 *     ],
 *     'metadata' => [],
 * ]
 */
final class role_seed {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'roles';

    /** Target type used in result messages. */
    private const TARGET_TYPE = 'role';

    /** Dry-run mode. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Apply mode. */
    private const MODE_APPLY = 'apply';

    /** Report mode. */
    private const MODE_REPORT = 'report';

    /** Rollback-plan mode. */
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
     * Symbolic role shortnames that must not be created as Moodle roles.
     */
    private const SYMBOLIC_ROLES = [
        'joueur',
        'joueur_lucide',
        'batisseur',
        'archiviste',
        'inquisiteur',
        'cartographe',
        'architecte_sens',
        'architecte_opportunites',
        'gardien_systemes_vivants',
        'cartographe_systemes',
        'gardien_preuve',
        'participant_assemblee',
        'batisseur_prototype',
        'defi_king_klown',
        'inquisition_methodologique',
        'ia_gouvernable',
        'grand_jeu_social',
        'koa_digital_ecosystem',
    ];

    /**
     * Capability prefixes allowed in UCKK role presets.
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
     * Validate role preset items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = validation_result::create([
            'status' => 'completed',
            'ok' => true,
            'summary' => get_string('rolesvalidationcomplete', self::COMPONENT),
            'metadata' => [
                'preset' => self::PRESET,
                'mode' => $options['mode'] ?? self::MODE_REPORT,
            ],
        ]);

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                get_string('rolespresetempty', self::COMPONENT),
                '',
                []
            );
            return $result;
        }

        $seen = [];

        foreach ($items as $item) {
            $role = $this->normalise_role_item($item);
            $shortname = $role['shortname'];

            if ($shortname === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('roleshortnamemissing', self::COMPONENT),
                    '',
                    $role
                );
                continue;
            }

            if (isset($seen[$shortname])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('roleduplicated', self::COMPONENT, $shortname),
                    $shortname,
                    $role
                );
                continue;
            }

            $seen[$shortname] = true;

            if (in_array($shortname, self::SYMBOLIC_ROLES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_BLOCKER,
                    get_string('rolesymbolicnotallowed', self::COMPONENT, $shortname),
                    $shortname,
                    $role
                );
                continue;
            }

            if (!in_array($shortname, self::TECHNICAL_ROLES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('rolenotcanonicaltechnical', self::COMPONENT, $shortname),
                    $shortname,
                    $role
                );
                continue;
            }

            if ($role['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('rolenamemissing', self::COMPONENT, $shortname),
                    $shortname,
                    $role
                );
            }

            if (empty($role['contextlevels'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('rolecontextlevelsmissing', self::COMPONENT, $shortname),
                    $shortname,
                    $role
                );
            }

            foreach ($role['contextlevels'] as $contextlevel) {
                if (!$this->context_level_to_constant($contextlevel)) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        get_string('roleinvalidcontextlevel', self::COMPONENT, [
                            'role' => $shortname,
                            'context' => $contextlevel,
                        ]),
                        $shortname,
                        $role
                    );
                }
            }

            foreach ($role['capabilities'] as $capabilityrow) {
                $this->validate_capability_row($result, $shortname, $capabilityrow);
            }

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                get_string('rolepresetvalid', self::COMPONENT, $shortname),
                $shortname,
                [
                    'shortname' => $shortname,
                ]
            );
        }

        $this->finalise_result($result);

        return $result;
    }

    /**
     * Apply role preset items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = (string)($options['mode'] ?? self::MODE_APPLY);
        $dryrun = $this->is_dry_run($options);

        $result = validation_result::create([
            'status' => 'completed',
            'ok' => true,
            'summary' => $dryrun
                ? get_string('rolesdryruncomplete', self::COMPONENT)
                : get_string('rolesseedcomplete', self::COMPONENT),
            'metadata' => [
                'preset' => self::PRESET,
                'mode' => $mode,
            ],
        ]);

        $validation = $this->validate($items, $options);

        if ($this->result_has_blockers_or_errors($validation)) {
            $this->merge_result($result, $validation);
            $this->finalise_result($result);
            return $result;
        }

        foreach ($items as $item) {
            $role = $this->normalise_role_item($item);
            $shortname = $role['shortname'];

            if (!in_array($shortname, self::TECHNICAL_ROLES, true)) {
                continue;
            }

            if ($dryrun || $mode === self::MODE_REPORT || $mode === self::MODE_ROLLBACK_PLAN) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    get_string('rolewouldseed', self::COMPONENT, $shortname),
                    $shortname,
                    [
                        'shortname' => $shortname,
                        'mode' => $mode,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $roleid = $this->create_or_update_role($role, $result);
            $this->set_role_context_levels($roleid, $role, $result);
            $this->apply_role_capabilities($roleid, $role, $result);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                get_string('roleseeded', self::COMPONENT, $shortname),
                $shortname,
                [
                    'roleid' => $roleid,
                    'shortname' => $shortname,
                ]
            );
        }

        $this->finalise_result($result);

        return $result;
    }

    /**
     * Reset seeded role capabilities conservatively.
     *
     * This does not delete Moodle roles by default. It removes only the
     * capability assignments represented by the preset when confirm=true and
     * force=true are provided. This prevents accidental deletion of roles that
     * may already be used by enrolments or manual administration.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $confirmed = !empty($options['confirm']);
        $force = !empty($options['force']);
        $dryrun = $this->is_dry_run($options);

        $result = validation_result::create([
            'status' => 'completed',
            'ok' => true,
            'summary' => get_string('rolesresetcomplete', self::COMPONENT),
            'metadata' => [
                'preset' => self::PRESET,
                'mode' => $options['mode'] ?? self::MODE_ROLLBACK_PLAN,
            ],
        ]);

        if (!$confirmed || !$force) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                get_string('rolesresetrequiresconfirmation', self::COMPONENT),
                '',
                [
                    'confirm' => $confirmed,
                    'force' => $force,
                ]
            );
            $this->finalise_result($result);
            return $result;
        }

        foreach ($items as $item) {
            $role = $this->normalise_role_item($item);
            $shortname = $role['shortname'];

            if (!in_array($shortname, self::TECHNICAL_ROLES, true)) {
                continue;
            }

            $existing = $DB->get_record('role', ['shortname' => $shortname], '*', IGNORE_MISSING);

            if (!$existing) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    get_string('rolenotfoundskipped', self::COMPONENT, $shortname),
                    $shortname,
                    []
                );
                $this->increment($result, 'skipped');
                continue;
            }

            foreach ($role['capabilities'] as $capabilityrow) {
                $capability = (string)($capabilityrow['capability'] ?? '');

                if ($capability === '') {
                    continue;
                }

                $contextlevel = (string)($capabilityrow['context'] ?? 'system');
                $context = $this->context_for_capability_assignment($contextlevel);

                if ($dryrun) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_INFO,
                        get_string('capabilitywouldreset', self::COMPONENT, [
                            'capability' => $capability,
                            'role' => $shortname,
                        ]),
                        $shortname,
                        [
                            'capability' => $capability,
                            'context' => $contextlevel,
                        ]
                    );
                    $this->increment($result, 'skipped');
                    continue;
                }

                unassign_capability($capability, (int)$existing->id, $context->id);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    get_string('capabilityreset', self::COMPONENT, [
                        'capability' => $capability,
                        'role' => $shortname,
                    ]),
                    $shortname,
                    [
                        'capability' => $capability,
                        'contextid' => $context->id,
                    ]
                );
                $this->increment($result, 'updated');
            }
        }

        accesslib_clear_all_caches_for_unit_testing();
        $this->finalise_result($result);

        return $result;
    }

    /**
     * Export current technical roles in canonical preset shape.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];

        foreach (self::TECHNICAL_ROLES as $shortname) {
            $role = $DB->get_record('role', ['shortname' => $shortname], '*', IGNORE_MISSING);

            if (!$role) {
                continue;
            }

            $items[] = [
                'shortname' => $role->shortname,
                'name' => $role->name,
                'description' => $role->description,
                'archetype' => $role->archetype ?? '',
                'contextlevels' => $this->export_context_levels((int)$role->id),
                'capabilities' => $this->export_capabilities((int)$role->id),
                'metadata' => [
                    'exportedby' => self::COMPONENT,
                    'timeexported' => time(),
                ],
            ];
        }

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $items,
        ];
    }

    /**
     * Create or update a Moodle role.
     *
     * @param array<string, mixed> $role Role data.
     * @param validation_result $result Result.
     * @return int Role id.
     */
    private function create_or_update_role(array $role, validation_result $result): int {
        global $DB;

        $existing = $DB->get_record('role', ['shortname' => $role['shortname']], '*', IGNORE_MISSING);

        if ($existing) {
            update_role(
                (int)$existing->id,
                $role['name'],
                $role['shortname'],
                $role['description'],
                $role['archetype']
            );

            $this->increment($result, 'updated');

            return (int)$existing->id;
        }

        $roleid = create_role(
            $role['name'],
            $role['shortname'],
            $role['description'],
            $role['archetype']
        );

        $this->increment($result, 'created');

        return (int)$roleid;
    }

    /**
     * Set role context levels.
     *
     * @param int $roleid Role id.
     * @param array<string, mixed> $role Role data.
     * @param validation_result $result Result.
     */
    private function set_role_context_levels(int $roleid, array $role, validation_result $result): void {
        $contextlevels = [];

        foreach ($role['contextlevels'] as $level) {
            $constant = $this->context_level_to_constant($level);

            if ($constant !== null) {
                $contextlevels[] = $constant;
            }
        }

        $contextlevels = array_values(array_unique($contextlevels));

        if (empty($contextlevels)) {
            $contextlevels = [CONTEXT_SYSTEM];
        }

        set_role_contextlevels($roleid, $contextlevels);

        $this->add_message(
            $result,
            self::SEVERITY_INFO,
            get_string('rolecontextlevelsupdated', self::COMPONENT, $role['shortname']),
            $role['shortname'],
            [
                'roleid' => $roleid,
                'contextlevels' => $role['contextlevels'],
            ]
        );
    }

    /**
     * Apply capability assignments for a role.
     *
     * @param int $roleid Role id.
     * @param array<string, mixed> $role Role data.
     * @param validation_result $result Result.
     */
    private function apply_role_capabilities(int $roleid, array $role, validation_result $result): void {
        foreach ($role['capabilities'] as $capabilityrow) {
            $capability = (string)($capabilityrow['capability'] ?? '');
            $permission = (string)($capabilityrow['permission'] ?? self::PERMISSION_ALLOW);
            $contextlevel = (string)($capabilityrow['context'] ?? 'system');

            if ($capability === '') {
                continue;
            }

            $context = $this->context_for_capability_assignment($contextlevel);
            $permissionconstant = $this->permission_to_constant($permission);

            assign_capability(
                $capability,
                $permissionconstant,
                $roleid,
                $context->id,
                true
            );

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                get_string('capabilityassigned', self::COMPONENT, [
                    'capability' => $capability,
                    'role' => $role['shortname'],
                ]),
                $role['shortname'],
                [
                    'roleid' => $roleid,
                    'capability' => $capability,
                    'permission' => $permission,
                    'contextid' => $context->id,
                ]
            );

            $this->increment($result, 'updated');
        }
    }

    /**
     * Validate a role capability row.
     *
     * @param validation_result $result Result.
     * @param string $shortname Role shortname.
     * @param array<string, mixed> $capabilityrow Capability row.
     */
    private function validate_capability_row(validation_result $result, string $shortname, array $capabilityrow): void {
        $capability = clean_param((string)($capabilityrow['capability'] ?? ''), PARAM_CAPABILITY);
        $permission = clean_param((string)($capabilityrow['permission'] ?? self::PERMISSION_ALLOW), PARAM_ALPHANUMEXT);
        $context = clean_param((string)($capabilityrow['context'] ?? 'system'), PARAM_ALPHANUMEXT);

        if ($capability === '') {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                get_string('capabilitymissing', self::COMPONENT, $shortname),
                $shortname,
                $capabilityrow
            );
            return;
        }

        if (!$this->is_allowed_uckk_capability($capability)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                get_string('capabilitynotuckk', self::COMPONENT, $capability),
                $shortname,
                $capabilityrow
            );
        }

        if (!in_array($permission, $this->allowed_permissions(), true)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                get_string('capabilityinvalidpermission', self::COMPONENT, [
                    'capability' => $capability,
                    'permission' => $permission,
                ]),
                $shortname,
                $capabilityrow
            );
        }

        if (!$this->context_level_to_constant($context)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                get_string('capabilityinvalidcontext', self::COMPONENT, [
                    'capability' => $capability,
                    'context' => $context,
                ]),
                $shortname,
                $capabilityrow
            );
        }
    }

    /**
     * Normalise one role preset item.
     *
     * @param array<string, mixed>|stdClass $item Raw preset item.
     * @return array<string, mixed>
     */
    private function normalise_role_item(array|stdClass $item): array {
        $item = (array)$item;

        $shortname = clean_param((string)($item['shortname'] ?? $item['key'] ?? ''), PARAM_ALPHANUMEXT);
        $name = trim(clean_param((string)($item['name'] ?? $shortname), PARAM_TEXT));
        $description = trim((string)($item['description'] ?? ''));
        $archetype = clean_param((string)($item['archetype'] ?? ''), PARAM_ALPHANUMEXT);

        $contextlevels = $item['contextlevels'] ?? [CONTEXT_SYSTEM];

        if (!is_array($contextlevels)) {
            $contextlevels = [$contextlevels];
        }

        $contextlevels = array_values(array_filter(array_map(
            [$this, 'normalise_context_level'],
            $contextlevels
        )));

        $capabilities = $item['capabilities'] ?? [];

        if (!is_array($capabilities)) {
            $capabilities = [];
        }

        $normalisedcapabilities = [];

        foreach ($capabilities as $capabilityrow) {
            $normalisedcapabilities[] = $this->normalise_capability_row($capabilityrow);
        }

        $metadata = $item['metadata'] ?? [];

        if ($metadata instanceof stdClass) {
            $metadata = (array)$metadata;
        }

        if (!is_array($metadata)) {
            $metadata = [];
        }

        return [
            'shortname' => $shortname,
            'name' => $name,
            'description' => $description,
            'archetype' => $archetype,
            'contextlevels' => $contextlevels,
            'capabilities' => $normalisedcapabilities,
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise one capability row.
     *
     * @param mixed $capabilityrow Raw capability row.
     * @return array<string, mixed>
     */
    private function normalise_capability_row(mixed $capabilityrow): array {
        if (is_string($capabilityrow)) {
            return [
                'capability' => clean_param($capabilityrow, PARAM_CAPABILITY),
                'permission' => self::PERMISSION_ALLOW,
                'context' => 'system',
                'component' => $this->capability_component($capabilityrow),
            ];
        }

        $row = (array)$capabilityrow;
        $capability = clean_param((string)($row['capability'] ?? ''), PARAM_CAPABILITY);

        return [
            'capability' => $capability,
            'permission' => clean_param((string)($row['permission'] ?? self::PERMISSION_ALLOW), PARAM_ALPHANUMEXT),
            'context' => $this->normalise_context_level($row['context'] ?? 'system'),
            'component' => clean_param(
                (string)($row['component'] ?? $this->capability_component($capability)),
                PARAM_COMPONENT
            ),
            'metadata' => is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
        ];
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
                CONTEXT_USER => 'user',
                default => '',
            };
        }

        $contextlevel = strtolower(trim((string)$contextlevel));
        $contextlevel = str_replace('-', '_', $contextlevel);

        return match ($contextlevel) {
            'system', 'context_system' => 'system',
            'coursecat', 'course_category', 'category', 'context_coursecat' => 'course_category',
            'course', 'context_course' => 'course',
            'module', 'cm', 'activity', 'context_module' => 'module',
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
            'user' => CONTEXT_USER,
            default => null,
        };
    }

    /**
     * Return context used for role capability assignment.
     *
     * Seed presets normally assign role capabilities at system context so the
     * role can later be assigned in the declared context levels. The context
     * field is kept for audit/validation but falls back to system for assignment
     * because Moodle role capability definitions are usually global.
     *
     * @param string $contextlevel Context level.
     * @return \context
     */
    private function context_for_capability_assignment(string $contextlevel): \context {
        // Site-wide role capability configuration belongs to system context.
        // Specific enrolments/role assignments are not performed by this class.
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
     * Allowed permission names.
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
     * Check capability prefix.
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
     * Extract component name from a capability string.
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
     * Export context levels for one role.
     *
     * @param int $roleid Role id.
     * @return string[]
     */
    private function export_context_levels(int $roleid): array {
        $levels = [];

        foreach (get_role_contextlevels($roleid) as $level) {
            $normalised = $this->normalise_context_level((int)$level);

            if ($normalised !== '') {
                $levels[] = $normalised;
            }
        }

        return array_values(array_unique($levels));
    }

    /**
     * Export UCKK capability assignments for one role.
     *
     * @param int $roleid Role id.
     * @return array<int, array<string, mixed>>
     */
    private function export_capabilities(int $roleid): array {
        global $DB;

        $rows = $DB->get_records('role_capabilities', ['roleid' => $roleid], 'capability ASC');
        $capabilities = [];

        foreach ($rows as $row) {
            if (!$this->is_allowed_uckk_capability((string)$row->capability)) {
                continue;
            }

            $capabilities[] = [
                'capability' => $row->capability,
                'permission' => $this->permission_constant_to_name((int)$row->permission),
                'context' => 'system',
                'component' => $this->capability_component((string)$row->capability),
            ];
        }

        return $capabilities;
    }

    /**
     * Convert Moodle permission constant to canonical seed name.
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
     * Whether runtime options request a dry run.
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
     * Add a result message.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $message Message text.
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
        $result->add_message([
            'severity' => $severity,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'targetkey' => $targetkey,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        if ($severity === self::SEVERITY_ERROR || $severity === self::SEVERITY_BLOCKER) {
            $this->increment($result, 'failed');
        } else if ($severity === self::SEVERITY_WARNING) {
            $this->increment($result, 'warnings');
        }
    }

    /**
     * Increment a result counter.
     *
     * @param validation_result $result Result object.
     * @param string $counter Counter key.
     */
    private function increment(validation_result $result, string $counter): void {
        if (method_exists($result, 'increment')) {
            $result->increment($counter);
            return;
        }

        $result->counts[$counter] = (int)($result->counts[$counter] ?? 0) + 1;
    }

    /**
     * Merge one validation result into another.
     *
     * @param validation_result $target Target result.
     * @param validation_result $source Source result.
     */
    private function merge_result(validation_result $target, validation_result $source): void {
        if (method_exists($target, 'merge')) {
            $target->merge($source);
            return;
        }

        foreach ($source->messages ?? [] as $message) {
            $target->add_message((array)$message);
        }

        foreach ($source->counts ?? [] as $key => $value) {
            $target->counts[$key] = (int)($target->counts[$key] ?? 0) + (int)$value;
        }
    }

    /**
     * Return whether a validation result has blockers or errors.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_blockers_or_errors(validation_result $result): bool {
        if (method_exists($result, 'has_errors')) {
            return $result->has_errors();
        }

        foreach ($result->messages ?? [] as $message) {
            $severity = is_array($message)
                ? ($message['severity'] ?? '')
                : ($message->severity ?? '');

            if ($severity === self::SEVERITY_ERROR || $severity === self::SEVERITY_BLOCKER) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finalise result status flags.
     *
     * @param validation_result $result Result object.
     */
    private function finalise_result(validation_result $result): void {
        if (method_exists($result, 'finalise')) {
            $result->finalise();
            return;
        }

        $haserrors = $this->result_has_blockers_or_errors($result);

        $result->haserrors = $haserrors;
        $result->ok = !$haserrors;
        $result->status = $haserrors ? 'failed' : 'completed';
    }
}
```

Add these strings to `admin/tool/uckkseed/lang/en/tool_uckkseed.php`:

```php id="uxgo0y"
$string['capabilityassigned'] = 'Assigned {$a->capability} to {$a->role}.';
$string['capabilityinvalidcontext'] = 'Capability {$a->capability} uses invalid context {$a->context}.';
$string['capabilityinvalidpermission'] = 'Capability {$a->capability} uses invalid permission {$a->permission}.';
$string['capabilitymissing'] = 'Role {$a} has a capability row without a capability name.';
$string['capabilitynotuckk'] = 'Capability {$a} is not part of the UCKK component suite.';
$string['capabilityreset'] = 'Removed {$a->capability} from {$a->role}.';
$string['capabilitywouldreset'] = 'Would remove {$a->capability} from {$a->role}.';
$string['rolecontextlevelsmissing'] = 'Role {$a} has no context levels.';
$string['rolecontextlevelsupdated'] = 'Updated context levels for role {$a}.';
$string['roleduplicated'] = 'Role {$a} appears more than once in the preset.';
$string['roledryruncomplete'] = 'Role seed dry run completed.';
$string['rolenamemissing'] = 'Role {$a} has no display name.';
$string['rolenotcanonicaltechnical'] = 'Role {$a} is not a canonical UCKK technical Moodle role.';
$string['rolenotfoundskipped'] = 'Role {$a} was not found and was skipped.';
$string['rolepresetvalid'] = 'Role {$a} is valid.';
$string['rolesdryruncomplete'] = 'Role seed dry run completed.';
$string['rolespresetempty'] = 'The roles preset is empty.';
$string['rolesresetcomplete'] = 'Role reset completed.';
$string['rolesresetrequiresconfirmation'] = 'Role reset requires explicit confirmation and force options.';
$string['roleseedcomplete'] = 'Role seed completed.';
$string['roleseeded'] = 'Seeded role {$a}.';
$string['roleshortnamemissing'] = 'A role preset item is missing its shortname.';
$string['rolesymbolicnotallowed'] = 'Symbolic identity {$a} must not be created as a Moodle role.';
$string['rolesvalidationcomplete'] = 'Role preset validation completed.';
$string['roleinvalidcontextlevel'] = 'Role {$a->role} uses invalid context level {$a->context}.';
$string['rolewouldseed'] = 'Would seed role {$a}.';
```

Add these strings to `admin/tool/uckkseed/lang/fr/tool_uckkseed.php`:

```php id="ikbvp9"
$string['capabilityassigned'] = '{$a->capability} attribuée à {$a->role}.';
$string['capabilityinvalidcontext'] = 'La capacité {$a->capability} utilise le contexte invalide {$a->context}.';
$string['capabilityinvalidpermission'] = 'La capacité {$a->capability} utilise la permission invalide {$a->permission}.';
$string['capabilitymissing'] = 'Le rôle {$a} contient une ligne de capacité sans nom de capacité.';
$string['capabilitynotuckk'] = 'La capacité {$a} ne fait pas partie de la suite de composants UCKK.';
$string['capabilityreset'] = '{$a->capability} retirée de {$a->role}.';
$string['capabilitywouldreset'] = 'Retirerait {$a->capability} de {$a->role}.';
$string['rolecontextlevelsmissing'] = 'Le rôle {$a} n’a aucun niveau de contexte.';
$string['rolecontextlevelsupdated'] = 'Niveaux de contexte mis à jour pour le rôle {$a}.';
$string['roleduplicated'] = 'Le rôle {$a} apparaît plus d’une fois dans le préréglage.';
$string['roledryruncomplete'] = 'Simulation de semis des rôles terminée.';
$string['rolenamemissing'] = 'Le rôle {$a} n’a aucun nom d’affichage.';
$string['rolenotcanonicaltechnical'] = 'Le rôle {$a} n’est pas un rôle technique Moodle UCKK canonique.';
$string['rolenotfoundskipped'] = 'Le rôle {$a} est introuvable et a été ignoré.';
$string['rolepresetvalid'] = 'Le rôle {$a} est valide.';
$string['rolesdryruncomplete'] = 'Simulation de semis des rôles terminée.';
$string['rolespresetempty'] = 'Le préréglage des rôles est vide.';
$string['rolesresetcomplete'] = 'Réinitialisation des rôles terminée.';
$string['rolesresetrequiresconfirmation'] = 'La réinitialisation des rôles exige une confirmation explicite et l’option force.';
$string['roleseedcomplete'] = 'Semis des rôles terminé.';
$string['roleseeded'] = 'Rôle {$a} semé.';
$string['roleshortnamemissing'] = 'Un élément du préréglage de rôles n’a pas de shortname.';
$string['rolesymbolicnotallowed'] = 'L’identité symbolique {$a} ne doit pas être créée comme rôle Moodle.';
$string['rolesvalidationcomplete'] = 'Validation du préréglage des rôles terminée.';
$string['roleinvalidcontextlevel'] = 'Le rôle {$a->role} utilise le niveau de contexte invalide {$a->context}.';
$string['rolewouldseed'] = 'Sèmerait le rôle {$a}.';

