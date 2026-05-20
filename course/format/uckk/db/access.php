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
 * governance, archive validation, challenge validation, Assembly decisions,
 * report execution, badge awarding, competency certification, or Inquisiteur
 * decisions.
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
     * This is a read-only display capability. It does not grant access to
     * hidden Moodle sections, restricted activities, private submissions,
     * archive records, integrity cases, grades, or non-visible activities.
     */
    'format/uckk:viewcoursemap' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View UCKK evidence indicators.
     *
     * Evidence indicators are presentation hints attached to UCKK course
     * sections or activities. They must not expose private submissions,
     * restricted files, hidden activities, integrity cases, grades, or
     * non-visible archive content.
     */
    'format/uckk:viewevidenceindicators' => [
        'riskbitmask' => 0,
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
     * View UCKK archive indicators.
     *
     * Archive indicators show that a section or activity participates in the
     * UCKK memory layer. Access to actual archive items is controlled by
     * mod_uckkarchive capabilities, not by this course format capability.
     */
    'format/uckk:viewarchiveindicators' => [
        'riskbitmask' => 0,
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
     * View UCKK integrity markers.
     *
     * Integrity markers are presentation-level warnings or status indicators
     * displayed by the course format. They must not expose restricted case
     * contents, sanctions, investigations, private evidence, or Inquisiteur
     * decision data. Detailed integrity authority stays in tool_uckkintegrity.
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
     * Configure UCKK course sections.
     *
     * Allows authorized course editors to configure UCKK section metadata,
     * section display labels, canonical section flags, and format-specific
     * course-format options.
     */
    'format/uckk:configuresections' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:update',
    ],

    /*
     * Manage UCKK course blueprint.
     *
     * Allows authorized course editors to manage the UCKK course blueprint,
     * including canonical layout assumptions, section map configuration and
     * format-level structure settings.
     *
     * This does not create courses, activities, badges, competencies, archives,
     * challenges or assemblies. Those remain owned by their respective plugins
     * and by tool_uckkseed where applicable.
     */
    'format/uckk:manageblueprint' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
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
     * Allows authorized course editors to restore canonical UCKK section names:
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
     * This is for debugging the UCKK course format configuration, section map,
     * format options and local display state. It must not expose private learner
     * data, submissions, grades, archive contents, hidden records, integrity
     * case contents, tokens or service credentials.
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