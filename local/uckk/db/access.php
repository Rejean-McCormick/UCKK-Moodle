<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Capability definitions for local_uckk.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK capability policy.
 *
 * Symbolic UCKK roles such as joueur_lucide, batisseur, archiviste,
 * inquisiteur, cartographe, architecte_sens, and similar distinctions are
 * intentionally not created here as Moodle roles.
 *
 * This file defines Moodle capabilities only. Role creation and role-to-
 * capability assignment for UCKK technical roles is handled by tool_uckkseed
 * and Moodle administration.
 *
 * Capability scope rule:
 * - local_uckk owns global campus, program, pathway, profile, canon,
 *   reporting-navigation, export, restricted-data, and integration permissions.
 * - Activity-specific authority stays in mod_uckkchallenge, mod_uckkassembly,
 *   and mod_uckkarchive.
 * - Course-format display authority stays in format_uckk.
 * - Report execution/export authority stays in report_uckk.
 * - Seed-tool authority stays in tool_uckkseed.
 */
$capabilities = [

    /*
     * View the UCKK campus layer.
     *
     * Baseline read capability for authenticated users to access UCKK campus
     * navigation, public program cards, pathway overview surfaces, and
     * non-restricted institutional information.
     */
    'local/uckk:viewcampus' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Manage UCKK programs.
     *
     * Allows creation, update, hiding, archiving, ordering, and category linkage
     * for canonical UCKK programs. Programs are global institutional registry
     * objects, even when they point to Moodle course categories.
     */
    'local/uckk:manageprograms' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Manage UCKK pathways.
     *
     * Allows configuration of pathway definitions, required courses, required
     * badges, required competencies, evidence requirements, ordering, and
     * pathway status. Pathways are global institutional registry objects.
     */
    'local/uckk:managepathways' => [
        'riskbitmask' => RISK_CONFIG | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Manage UCKK player profiles.
     *
     * Allows authorized staff to update UCKK profile metadata, symbolic-role
     * references, pathway assignment references, visibility settings, and
     * integrity flags where permitted by service-layer checks.
     *
     * This remains user-context scoped because profile data is ultimately tied
     * to users. System-level assignments may still grant this across users.
     */
    'local/uckk:manageprofiles' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Manage UCKK canon registry entries.
     *
     * Allows authorized users to manage canonical panels, institutional
     * formulas, registry entries, and canon metadata exposed by local_uckk.
     */
    'local/uckk:managecanon' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View UCKK reports exposed through local_uckk.
     *
     * This does not replace report_uckk capabilities. It only gates local_uckk
     * summary surfaces and shared report navigation.
     */
    'local/uckk:viewreports' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Export UCKK local data.
     *
     * Allows export of local_uckk registry, profile, pathway, provenance,
     * visibility, map, reflection, and status data where allowed by service-
     * layer checks.
     */
    'local/uckk:exportdata' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View restricted UCKK local data.
     *
     * Allows access to restricted local_uckk records such as restricted profile
     * metadata, restricted provenance summaries, hidden pathway status, local
     * integrity markers, or internal registry state. This must always be paired
     * with service-layer context checks.
     */
    'local/uckk:viewrestricted' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Manage UCKK integrations.
     *
     * Allows configuration of cross-plugin bridges and external integration
     * settings exposed by local_uckk. AI decision authority remains forbidden
     * by UCKK policy and must not be implemented through this capability.
     */
    'local/uckk:manageintegrations' => [
        'riskbitmask' => RISK_CONFIG | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];