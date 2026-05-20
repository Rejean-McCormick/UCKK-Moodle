<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for the UCKK Assembly activity module.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK Assembly capability policy.
 *
 * The Assembly module owns Moodle-side deliberation, motions, amendments,
 * voting, decision publication, contestation, and archival handoff.
 *
 * Authority boundaries:
 * - Assembly decisions are human/institutional decisions.
 * - External systems and AI may inform, but must not decide.
 * - Archive persistence belongs to mod_uckkarchive or an archive service.
 * - Integrity review belongs to tool_uckkintegrity.
 * - Reports belong to report_uckk.
 * - Seed-time role assignment belongs to tool_uckkseed.
 */
$capabilities = [

    /*
     * Add a UCKK Assembly activity to a course.
     *
     * This is the standard Moodle addinstance capability. It must stay at
     * CONTEXT_COURSE because adding an activity is a course-level operation.
     */
    'mod/uckkassembly:addinstance' => [
        'riskbitmask' => RISK_XSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    /*
     * View a UCKK Assembly activity.
     *
     * Allows participants to open the activity, read visible motions,
     * amendments, decisions, contestations, and published assembly state.
     */
    'mod/uckkassembly:view' => [
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
     * Create or configure an Assembly instance workflow.
     *
     * Allows authorized teachers/managers to prepare the assembly structure,
     * deliberation settings, voting windows, amendment policy, and publication
     * configuration inside an existing activity instance.
     */
    'mod/uckkassembly:createassembly' => [
        'riskbitmask' => RISK_XSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    /*
     * Propose a motion.
     *
     * Allows eligible participants to submit motions to the assembly workflow.
     * Service-layer checks must still enforce timing, eligibility, moderation,
     * duplication rules, and any integrity restrictions.
     */
    'mod/uckkassembly:proposemotion' => [
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
     * Amend a motion.
     *
     * Allows eligible participants to submit amendments. Service-layer checks
     * must enforce amendment windows, ownership rules, moderation, quorum, and
     * any local assembly constraints.
     */
    'mod/uckkassembly:amendmotion' => [
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
     * Vote in an Assembly.
     *
     * Allows eligible participants to cast votes. This capability only grants
     * access to the vote action; service-layer checks must enforce eligibility,
     * vote windows, quorum, one-person/one-vote rules, anonymity policy, and
     * audit constraints.
     */
    'mod/uckkassembly:vote' => [
        'riskbitmask' => RISK_PERSONAL,
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
     * Publish an Assembly decision.
     *
     * Allows authorized staff to finalize and publish the institutional
     * decision of the Assembly. This is intentionally restricted because it can
     * expose personal data, finalize governance state, and trigger archive or
     * reporting consequences.
     */
    'mod/uckkassembly:publishdecision' => [
        'riskbitmask' => RISK_XSS | RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Contest an Assembly decision.
     *
     * Allows eligible participants to contest, object to, or request review of
     * a published decision. Service-layer checks must enforce deadlines,
     * standing, evidence requirements, and escalation rules.
     */
    'mod/uckkassembly:contestdecision' => [
        'riskbitmask' => RISK_XSS | RISK_PERSONAL,
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
     * Archive Assembly material.
     *
     * Allows authorized staff to prepare or trigger archival handoff for
     * motions, amendments, votes, published decisions, minority reports,
     * contestations, and provenance records. Actual archive storage remains
     * governed by mod_uckkarchive and its own capabilities.
     */
    'mod/uckkassembly:archive' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];