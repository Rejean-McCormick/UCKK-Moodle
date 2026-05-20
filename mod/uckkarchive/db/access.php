<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK Archive capability policy.
 *
 * mod_uckkarchive owns archive activity permissions only.
 *
 * It does not own:
 * - global UCKK registry permissions, which belong to local_uckk;
 * - course-format display permissions, which belong to format_uckk;
 * - challenge workflow permissions, which belong to mod_uckkchallenge;
 * - assembly workflow permissions, which belong to mod_uckkassembly;
 * - integrity case permissions, which belong to tool_uckkintegrity;
 * - reporting permissions, which belong to report_uckk.
 *
 * Archive service code must still enforce ownership, provenance, validation
 * state, visibility, retention, privacy, and workflow rules. Capabilities are
 * permission gates, not automatic authority to bypass archive policy.
 */
$capabilities = [

    /*
     * Add a UCKK Archive activity to a course.
     *
     * This is a course-level activity-creation capability, matching Moodle's
     * standard module add-instance pattern.
     */
    'mod/uckkarchive:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    /*
     * View the archive activity and non-restricted archive items.
     *
     * This grants access to the archive activity shell and public/non-restricted
     * archive items. Restricted records remain protected by
     * mod/uckkarchive:viewrestricted and service-layer checks.
     */
    'mod/uckkarchive:view' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Add an archive item, proof record, Kristal, or portfolio-linked memory
     * item inside the archive activity.
     *
     * Services must still enforce item ownership, context, provenance,
     * visibility, workflow state, accepted file types, and submission rules.
     */
    'mod/uckkarchive:additem' => [
        'riskbitmask' => RISK_XSS | RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Validate archive items and mark evidence as human-reviewed or verified.
     *
     * This is intended for Archiviste, Mentor, Gestionnaire UCKK, or similarly
     * trusted technical roles configured by administrators. This capability does
     * not allow automated validation or AI-only validation.
     */
    'mod/uckkarchive:validateitem' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Revise archive items and create new archive revision records.
     *
     * Students may receive this by default so they can revise their own
     * submitted archive records. Service code must still check ownership,
     * validation state, lock state, and workflow state before accepting
     * revisions.
     */
    'mod/uckkarchive:reviseitem' => [
        'riskbitmask' => RISK_XSS | RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View restricted, private, hidden, or restricted-integrity archive data.
     *
     * This capability must not be granted broadly. It protects sensitive
     * evidence, integrity-linked records, privacy-sensitive material, hidden
     * provenance, and restricted institutional memory.
     */
    'mod/uckkarchive:viewrestricted' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Export archive items, evidence packages, or archive bundles.
     *
     * Export services must still enforce validation state, provenance,
     * visibility, retention, privacy, restricted-data, and redaction rules.
     */
    'mod/uckkarchive:export' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];