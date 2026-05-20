<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for the UCKK Integrity admin tool.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK integrity capability policy.
 *
 * The integrity tool owns integrity case visibility, case opening, review,
 * assignment, correction, invalidation, closure, and restricted integrity
 * access.
 *
 * It must not:
 * - award badges;
 * - certify competencies;
 * - make AI decisions authoritative;
 * - bypass archive validation;
 * - replace Assembly decisions;
 * - create symbolic UCKK roles as Moodle roles.
 *
 * Symbolic roles and UCKK governance meanings remain registry/canon concepts.
 * Moodle authority is granted only through capabilities and Moodle roles.
 */
$capabilities = [

    /*
     * View integrity information.
     *
     * Allows access to standard integrity surfaces, non-restricted integrity
     * markers, case summaries visible to the user, and integrity navigation.
     */
    'tool/uckkintegrity:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ],
    ],

    /*
     * Open an integrity case.
     *
     * Allows a user to open or report an integrity case. This does not grant
     * review, sanction, correction, invalidation, or closure authority.
     */
    'tool/uckkintegrity:opencase' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ],
    ],

    /*
     * Review an integrity case.
     *
     * Allows authorized reviewers to inspect case material, evaluate state,
     * record review notes, and participate in the integrity review workflow.
     */
    'tool/uckkintegrity:reviewcase' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Assign an integrity case.
     *
     * Allows assigning or reassigning integrity cases to authorized reviewers.
     * This is configuration-sensitive and must remain restricted.
     */
    'tool/uckkintegrity:assigncase' => [
        'riskbitmask' => RISK_PERSONAL | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Issue an integrity correction.
     *
     * Allows issuing a correction requirement or remediation instruction.
     * This does not delete evidence or invalidate archive material by itself.
     */
    'tool/uckkintegrity:issuecorrection' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Invalidate integrity-affected material.
     *
     * Allows invalidating an affected submission, proof, archive item, or
     * workflow state where the integrity service layer permits it.
     */
    'tool/uckkintegrity:invalidate' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Close an integrity case.
     *
     * Allows final closure of an integrity case after review, correction,
     * invalidation, or no-action resolution.
     */
    'tool/uckkintegrity:closecase' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View restricted integrity information.
     *
     * Allows access to restricted case details, internal review notes,
     * protected integrity state, restricted provenance, and sensitive audit
     * information. Service-layer checks must still enforce user, course,
     * module, and case-specific boundaries.
     */
    'tool/uckkintegrity:viewrestricted' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];