<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for the UCKK seed admin tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /*
     * Run seed operations.
     *
     * This capability allows a user to create or update seed-managed objects
     * through tool_uckkseed. It is intentionally system-scoped because the seed
     * tool coordinates multiple Moodle subsystems and UCKK plugins.
     */
    'tool/uckkseed:seed' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Run reset operations.
     *
     * This capability allows destructive or rollback-style seed operations.
     * Keep restricted to trusted administrators/managers.
     */
    'tool/uckkseed:reset' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Validate seed presets.
     *
     * This capability allows reading and validating preset structure, routing,
     * dependencies and seed-readiness without applying changes.
     */
    'tool/uckkseed:validate' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Export seed presets.
     *
     * This capability allows exporting canonical preset data from Moodle state.
     */
    'tool/uckkseed:exportpresets' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];