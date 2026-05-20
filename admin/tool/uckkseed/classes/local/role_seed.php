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
 *     'contextlevels' => ['system', 'course_category', 'course', 'module', 'block', 'user'],
 *     'capabilities' => [
 *         [
 *             'capability' => 'local/uckk:viewcampus',
 *             'permission' => 'allow',
 *             'context' => 'system',
 *             'component' => 'local_uckk',
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
     * Cached Moodle capability registry.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $capabilityregistry = null;

    /**
     * Validate role preset items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = validation_result::from_data([
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
            $this->finalise_result($result);
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
                if ($this->context_level_to_constant($contextlevel) === null) {
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

            if (!$this->result_has_blockers_or_errors($result)) {
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

        $result = validation_result::from_data([
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

        $this->clear_access_caches();
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

        $result = validation_result::from_data([
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

        $this->clear_access_caches();
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
                    'context' => $contextlevel,
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
        $context = $this->normalise_context_level($capabilityrow['context'] ?? 'system');
        $component = clean_param((string)($capabilityrow['component'] ?? ''), PARAM_COMPONENT);
        $expectedcomponent = $this->capability_component($capability);

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

        if (!$this->capability_exists($capability)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                get_string('capabilitymissing', self::COMPONENT, $capability),
                $shortname,
                [
                    ...$capabilityrow,
                    'capability' => $capability,
                    'reason' => 'Capability is not declared in Moodle capability registry. Check db/access.php and run upgrade.',
                ]
            );
        }

        if ($component !== '' && $expectedcomponent !== '' && $component !== $expectedcomponent) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Capability component mismatch: ' . $capability,
                $shortname,
                [
                    ...$capabilityrow,
                    'capability' => $capability,
                    'component' => $component,
                    'expectedcomponent' => $expectedcomponent,
                ]
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

        if ($this->context_level_to_constant($context) === null) {
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
            return;
        }

        $declaredcontext = $this->declared_capability_context($capability);

        if ($declaredcontext !== null && $declaredcontext !== $context) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Capability context differs from db/access.php declaration: ' . $capability,
                $shortname,
                [
                    ...$capabilityrow,
                    'capability' => $capability,
                    'presetcontext' => $context,
                    'declaredcontext' => $declaredcontext,
                ]
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
                'metadata' => [],
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
                CONTEXT_BLOCK => 'block',
                CONTEXT_USER => 'user',
                default => '',
            };
        }

        $contextlevel = strtolower(trim((string)$contextlevel));
        $contextlevel = str_replace('-', '_', $contextlevel);

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
     * Return whether the capability exists in Moodle's registered capability map.
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
     * Return Moodle's capability registry.
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
     * Increment a result counter.
     *
     * @param validation_result $result Result object.
     * @param string $counter Counter key.
     */
    private function increment(validation_result $result, string $counter): void {
        $result->increment($counter);
    }

    /**
     * Merge one validation result into another.
     *
     * @param validation_result $target Target result.
     * @param validation_result $source Source result.
     */
    private function merge_result(validation_result $target, validation_result $source): void {
        $target->merge($source);
    }

    /**
     * Return whether a validation result has blockers or errors.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_blockers_or_errors(validation_result $result): bool {
        return $result->has_errors();
    }

    /**
     * Clear Moodle access caches safely.
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