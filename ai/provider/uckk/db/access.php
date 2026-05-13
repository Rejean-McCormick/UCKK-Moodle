<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Capabilities for the governed UCKK AI provider.
 *
 * AI is assistive only. These capabilities must not be used to grant grading,
 * integrity validation, archive validation, sanctioning, or final decision power.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = [
    /*
     * Configure provider endpoint, model, logging, redaction, retention,
     * integrity-context availability, and public-challenge availability.
     */
    'aiprovider/uckk:configure' => [
        'riskbitmask' => RISK_CONFIG | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Request governed AI assistance.
     *
     * This permits use of supported actions such as summarising material,
     * mapping problems, extracting uncertainties, drafting reflections,
     * summarising assemblies, critiquing AI output, and preparing integrity
     * review briefs. It does not permit final decisions.
     */
    'aiprovider/uckk:use' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ],
    ],

    /*
     * View AI prompt/response logs when logging is enabled.
     *
     * Logs may contain personal, pedagogical, or integrity-sensitive data,
     * depending on configuration and redaction settings.
     */
    'aiprovider/uckk:viewlogs' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];