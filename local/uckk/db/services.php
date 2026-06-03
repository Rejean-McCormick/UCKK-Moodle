<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * External service declarations for local_uckk.
 *
 * This file only declares Moodle external functions.
 * Business logic belongs in local_uckk\external\* classes and local_uckk\service\* classes.
 *
 * Function names follow Moodle frankenstyle web service naming:
 * local_uckk_[verb]_[noun]
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
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
        'description' => 'Returns visible UCKK canon items for the requested context.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_get_status_options' => [
        'classname' => 'local_uckk\external\get_status_options',
        'description' => 'Returns canonical UCKK status, visibility, provenance, and validation options.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/uckk:viewcampus',
    ],

    'local_uckk_search_public_courses' => [
        'classname' => 'local_uckk\external\search_public_courses',
        'description' => 'Searches visible public UCKK courses for the public course explorer.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false,
    ],
];

$services = [
    'UCKK local read API' => [
        'functions' => [
            'local_uckk_get_player_dashboard',
            'local_uckk_get_programs',
            'local_uckk_get_pathways',
            'local_uckk_get_pathway_map',
            'local_uckk_get_player_profile',
            'local_uckk_get_canon_items',
            'local_uckk_get_status_options',
            'local_uckk_search_public_courses',
        ],
        'requiredcapability' => 'local/uckk:manageintegrations',
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'local_uckk_read',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];