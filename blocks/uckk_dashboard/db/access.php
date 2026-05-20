<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for block_uckk_dashboard.
 *
 * The dashboard block is a summary surface. It does not grant authority over
 * challenges, assemblies, archives, integrity cases, reports, badges,
 * competencies, profiles, programs, pathways, or seed operations.
 *
 * Those permissions remain in their owning plugins:
 * - local_uckk
 * - format_uckk
 * - mod_uckkchallenge
 * - mod_uckkassembly
 * - mod_uckkarchive
 * - tool_uckkseed
 * - tool_uckkintegrity
 * - report_uckk
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    /*
     * Add a UCKK dashboard block instance.
     *
     * Standard Moodle block capability. Allows a user to add this block in
     * contexts where blocks can be added, subject to normal Moodle page and
     * block rules.
     */
    'block/uckk_dashboard:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ],

    /*
     * Add a UCKK dashboard block to My Moodle / personal dashboard.
     *
     * Standard Moodle personal-dashboard block capability.
     */
    'block/uckk_dashboard:myaddinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/my:manageblocks',
    ],

    /*
     * View own UCKK dashboard summary.
     *
     * Allows a user to view dashboard information available to them. The block
     * must still filter all displayed data by enrolment, visibility, context,
     * ownership, and source-plugin capabilities.
     */
    'block/uckk_dashboard:view' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View dashboard summaries for other users.
     *
     * Allows staff to view delegated dashboard summaries for users they are
     * permitted to supervise. It must not bypass restricted profile, integrity,
     * archive, report, badge, competency, or privacy checks.
     */
    'block/uckk_dashboard:viewothers' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Configure UCKK dashboard block instance.
     *
     * Allows configuration of block-level display options only. It does not
     * authorize UCKK program, pathway, course-format, challenge, assembly,
     * archive, integrity, AI, badge, competency, report, seed, or privacy
     * administration.
     */
    'block/uckk_dashboard:configure' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ],
];