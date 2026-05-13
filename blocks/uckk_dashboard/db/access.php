<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Capability definitions for block_uckk_dashboard.
 *
 * The dashboard block is a summary surface. It does not grant authority over
 * challenges, assemblies, archives, integrity cases, reports, badges, or
 * competencies. Those permissions remain in their owning plugins.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = [
    /*
     * Standard Moodle block capability.
     *
     * Allows a user to add the UCKK dashboard block to contexts where blocks
     * can be added, subject to normal Moodle page/block rules.
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
     * Standard Moodle block capability.
     *
     * Allows a user to add the block to their personal dashboard where Moodle
     * supports user-managed dashboard blocks.
     */
    'block/uckk_dashboard:myaddinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],

    /*
     * UCKK dashboard view capability.
     *
     * Allows a user to view their own dashboard summary. Data returned by the
     * block must still be filtered by context, enrolment, visibility, and the
     * source plugin permissions.
     */
    'block/uckk_dashboard:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'user' => CAP_ALLOW,
        ],
    ],

    /*
     * UCKK delegated dashboard viewing capability.
     *
     * Allows staff to view dashboard summaries for users they are permitted to
     * supervise. It must not bypass restricted integrity, archive, report, or
     * profile visibility checks.
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
     * UCKK dashboard configuration capability.
     *
     * Allows configuration of block-level options only. It does not authorize
     * UCKK program, pathway, challenge, assembly, archive, integrity, AI, or
     * reporting administration.
     */
    'block/uckk_dashboard:configure' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];