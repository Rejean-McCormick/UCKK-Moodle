<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for report_uckk.
 *
 * report_uckk owns read-only UCKK report access and report export permission.
 * It must not grant seed, integrity-case, archive-validation, challenge,
 * assembly, or local registry administration authority.
 *
 * @package    report_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    /*
     * View UCKK reports available to the current user.
     *
     * This is the baseline report access capability. Report implementations
     * must still filter rows by Moodle context, enrolment, visibility,
     * ownership, privacy rules, and source-plugin permissions.
     */
    'report/uckk:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ],
    ],

    /*
     * View aggregate or cross-user UCKK reports.
     *
     * This permits broader reporting surfaces such as cohort, program,
     * pathway, competency, badge, archive-production, AI-usage, and
     * privacy/export summaries. It must not bypass restricted integrity
     * permissions or source-plugin visibility checks.
     */
    'report/uckk:viewall' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Export UCKK reports.
     *
     * This permits exporting report data from report_uckk. Export code must
     * still apply privacy, context, provenance, and source-plugin permission
     * checks before writing files or returning downloadable data.
     */
    'report/uckk:export' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];