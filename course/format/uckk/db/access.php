<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Capability definitions for the UCKK course format.
 *
 * These capabilities are intentionally limited to the course context.
 * The course format may display UCKK section metadata, evidence indicators,
 * archive indicators and integrity markers, but it must not implement global
 * governance, archive validation, challenge validation or Inquisiteur decisions.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /*
     * View the UCKK course map.
     *
     * This allows users to see the semantic structure of a UCKK course:
     * Orientation, Concepts, Matière canonique, Atelier, Preuves,
     * Délibération, Livrable, Évaluation and Archive.
     *
     * This is a read-only capability and does not grant access to hidden
     * Moodle sections, restricted activities, archive records or integrity data.
     */
    'format/uckk:viewcoursemap' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View evidence indicators.
     *
     * Evidence indicators are presentation hints attached to UCKK course
     * sections or activities. They must not expose private submissions,
     * restricted files, integrity cases or non-visible activities.
     */
    'format/uckk:viewevidenceindicators' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View archive indicators.
     *
     * Archive indicators show that a section or activity participates in the
     * UCKK memory layer. Access to actual archive items is controlled by
     * mod_uckkarchive capabilities, not by this course format capability.
     */
    'format/uckk:viewarchiveindicators' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View integrity markers.
     *
     * Integrity markers are sensitive because they may signal contestation,
     * correction requirements or Inquisiteur review states. This capability
     * is therefore not granted to students by default.
     *
     * Detailed integrity case access is controlled by tool_uckkintegrity.
     */
    'format/uckk:viewintegritymarkers' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Configure UCKK section semantics.
     *
     * This allows course editors to configure visual and semantic behaviour
     * of the UCKK format inside a course. It must not allow plugin-wide
     * configuration, role assignment, grade manipulation, archive validation
     * or integrity decisions.
     */
    'format/uckk:configuresections' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    /*
     * Manage the UCKK course blueprint.
     *
     * This capability is reserved for users who may apply or refresh the
     * canonical UCKK course structure inside a course. It is intentionally
     * stricter than configuresections because blueprint operations may affect
     * several sections at once.
     */
    'format/uckk:manageblueprint' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    /*
     * Reset UCKK section names.
     *
     * This allows authorized course editors to restore canonical section names:
     * Orientation, Concepts, Matière canonique, Atelier, Preuves,
     * Délibération, Livrable, Évaluation and Archive.
     */
    'format/uckk:resetsectionnames' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    /*
     * View UCKK diagnostic information for a course.
     *
     * This is for debugging the course format configuration and section map.
     * It should not expose private learner data, submissions, grades, archive
     * contents or integrity cases.
     */
    'format/uckk:viewdiagnostics' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];