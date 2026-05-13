<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for mod_uckkchallenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /*
     * Required Moodle activity-module capability.
     *
     * Allows users to add a UCKK Challenge activity to a course.
     */
    'mod/uckkchallenge:addinstance' => [
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
     * View a UCKK Challenge activity.
     *
     * This only permits access to the activity shell and visible challenge
     * content. Submission, evaluation, integrity, restricted data, and archive
     * actions require separate capabilities.
     */
    'mod/uckkchallenge:view' => [
        'riskbitmask' => RISK_PERSONAL,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Create or configure challenge content.
     *
     * This covers challenge statement, rules, corridors of action, evidence
     * requirements, evaluation criteria, timeline, visibility, and governance
     * configuration.
     */
    'mod/uckkchallenge:createchallenge' => [
        'riskbitmask' => RISK_XSS | RISK_PERSONAL,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Submit proof for a challenge.
     *
     * Learners use this to submit evidence. Submitted proof remains subject to
     * mentor review, integrity review when required, provenance checks, and
     * human validation.
     */
    'mod/uckkchallenge:submitproof' => [
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
     * Evaluate challenge submissions.
     *
     * Mentors and managers use this for rubric review, feedback, competency
     * rating proposals, correction requests, and validation recommendations.
     * This does not bypass integrity validation where required.
     */
    'mod/uckkchallenge:evaluate' => [
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
     * Validate challenge integrity.
     *
     * Intended for Inquisiteur-style integrity workflows and restricted review
     * roles. Custom UCKK roles should receive this during seed/configuration;
     * only manager receives it by default here.
     */
    'mod/uckkchallenge:validateintegrity' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Archive challenge output.
     *
     * Allows permitted users to create or request archive output from validated
     * challenge records. The archive plugin remains the owner of archive items,
     * versions, provenance, and restricted archive visibility.
     */
    'mod/uckkchallenge:archive' => [
        'riskbitmask' => RISK_PERSONAL | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];