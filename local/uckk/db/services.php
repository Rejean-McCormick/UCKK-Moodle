<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * External service declarations for local_uckk.
 *
 * This file only declares Moodle external functions.
 * Business logic belongs in local_uckk\external\* classes and local_uckk\local\* classes.
 *
 * Function names follow Moodle frankenstyle web service naming:
 * local_uckk_[verb]_[noun]
 *
 * Every function declared here must have a matching class in:
 *
 * local/uckk/classes/external/
 *
 * Each external class must define:
 * - execute_parameters()
 * - execute()
 * - execute_returns()
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // -------------------------------------------------------------------------
    // Existing standalone-core local_uckk services.
    // -------------------------------------------------------------------------

    'local_uckk_get_player_dashboard' => [
        'classname' => 'local_uckk\external\get_player_dashboard',
        'description' => 'Returns the current user UCKK dashboard data.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_programs' => [
        'classname' => 'local_uckk\external\get_programs',
        'description' => 'Returns visible UCKK program registry entries.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_pathways' => [
        'classname' => 'local_uckk\external\get_pathways',
        'description' => 'Returns visible UCKK pathways for the requested context.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_pathway_map' => [
        'classname' => 'local_uckk\external\get_pathway_map',
        'description' => 'Returns a structured UCKK pathway map for display.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_player_profile' => [
        'classname' => 'local_uckk\external\get_player_profile',
        'description' => 'Returns a UCKK player profile visible to the requesting user.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_update_player_profile' => [
        'classname' => 'local_uckk\external\update_player_profile',
        'description' => 'Updates permitted UCKK player profile fields.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/uckk:manageprofiles',
    ],

    'local_uckk_get_canon_items' => [
        'classname' => 'local_uckk\external\get_canon_items',
        'description' => 'Returns visible UCKK canon entries.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_status_options' => [
        'classname' => 'local_uckk\external\get_status_options',
        'description' => 'Returns supported UCKK status and visibility options.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_search_public_courses' => [
        'classname' => 'local_uckk\external\search_public_courses',
        'description' => 'Searches visible public UCKK Moodle courses.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_submit_reflection' => [
        'classname' => 'local_uckk\external\submit_reflection',
        'description' => 'Submits a permitted UCKK learner reflection.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    // -------------------------------------------------------------------------
    // DOC_12 Faculty/Atlas public page and validation services.
    // -------------------------------------------------------------------------

    'local_uckk_validate_faculty_profile' => [
        'classname' => 'local_uckk\external\validate_faculty_profile',
        'description' => 'Validates a UCKK public faculty profile JSON document.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/uckk:managefacultyprofiles',
    ],

    'local_uckk_validate_atlas_voie' => [
        'classname' => 'local_uckk\external\validate_atlas_voie',
        'description' => 'Validates a UCKK Atlas Voie JSON document.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/uckk:validateatlasjson',
    ],

    'local_uckk_get_faculty_public_page' => [
        'classname' => 'local_uckk\external\get_faculty_public_page',
        'description' => 'Returns the render-ready public UCKK faculty page payload for a slug.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewpublicfaculties',
    ],

    'local_uckk_get_faculty_sync_report' => [
        'classname' => 'local_uckk\external\get_faculty_sync_report',
        'description' => 'Returns a UCKK Faculty/Atlas/Moodle mapping and sync report.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewfacultysyncreport',
    ],

    'local_uckk_run_atlas_sync_dryrun' => [
        'classname' => 'local_uckk\external\run_atlas_sync_dryrun',
        'description' => 'Runs a read-only dry-run of the UCKK Atlas to Moodle sync.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/uckk:syncatlasmoodle',
    ],
];

$services = [
    /*
     * Restricted read-only integration service.
     *
     * This service is disabled by default and restricted to explicitly assigned
     * service users. It is not required for public faculty page rendering.
     */
    'UCKK local read service' => [
        'functions' => [
            'local_uckk_get_player_dashboard',
            'local_uckk_get_programs',
            'local_uckk_get_pathways',
            'local_uckk_get_pathway_map',
            'local_uckk_get_player_profile',
            'local_uckk_get_canon_items',
            'local_uckk_get_status_options',
            'local_uckk_search_public_courses',
            'local_uckk_get_faculty_public_page',
        ],
        'requiredcapability' => 'local/uckk:manageintegrations',
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'local_uckk_read',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],

    /*
     * Restricted administration and validation service.
     *
     * This service is disabled by default and intentionally excludes any apply
     * sync operation. The current DOC_12 web-service surface only exposes
     * validation, public-page read, sync report, and dry-run.
     */
    'UCKK Faculty Atlas administration service' => [
        'functions' => [
            'local_uckk_validate_faculty_profile',
            'local_uckk_validate_atlas_voie',
            'local_uckk_get_faculty_sync_report',
            'local_uckk_run_atlas_sync_dryrun',
        ],
        'requiredcapability' => 'local/uckk:manageintegrations',
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'local_uckk_faculty_atlas_admin',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];