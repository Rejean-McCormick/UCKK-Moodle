<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * External services for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_uckkassembly_get_assembly_state' => [
        'classname' => 'mod_uckkassembly\external\get_assembly_state',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the permission-filtered state of a UCKK Assembly.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_get_motion_list' => [
        'classname' => 'mod_uckkassembly\external\get_motion_list',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return visible motions for a UCKK Assembly.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_get_motion' => [
        'classname' => 'mod_uckkassembly\external\get_motion',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return one visible UCKK Assembly motion.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_submit_motion' => [
        'classname' => 'mod_uckkassembly\external\submit_motion',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit or update a UCKK Assembly motion.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:proposemotion',
    ],

    'mod_uckkassembly_submit_amendment' => [
        'classname' => 'mod_uckkassembly\external\submit_amendment',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit an amendment to a UCKK Assembly motion.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:amendmotion',
    ],

    'mod_uckkassembly_submit_objection' => [
        'classname' => 'mod_uckkassembly\external\submit_objection',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit an objection to a UCKK Assembly motion.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:proposemotion',
    ],

    'mod_uckkassembly_submit_vote' => [
        'classname' => 'mod_uckkassembly\external\submit_vote',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit or update a UCKK Assembly vote.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:vote',
    ],

    'mod_uckkassembly_get_vote_results' => [
        'classname' => 'mod_uckkassembly\external\get_vote_results',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return permission-filtered vote results for a UCKK Assembly motion.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_get_decision' => [
        'classname' => 'mod_uckkassembly\external\get_decision',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return one visible UCKK Assembly decision.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_publish_decision' => [
        'classname' => 'mod_uckkassembly\external\publish_decision',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Publish a UCKK Assembly decision from a motion or deliberation record.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:publishdecision',
    ],

    'mod_uckkassembly_contest_decision' => [
        'classname' => 'mod_uckkassembly\external\contest_decision',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Contest a published UCKK Assembly decision.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:contestdecision',
    ],

    'mod_uckkassembly_get_minutes_panel' => [
        'classname' => 'mod_uckkassembly\external\get_minutes_panel',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return rendered or structured minutes panel data for a UCKK Assembly.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_save_minutes' => [
        'classname' => 'mod_uckkassembly\external\save_minutes',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create or update UCKK Assembly minutes.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:publishdecision',
    ],

    'mod_uckkassembly_publish_minutes' => [
        'classname' => 'mod_uckkassembly\external\publish_minutes',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Publish UCKK Assembly minutes.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:publishdecision',
    ],

    'mod_uckkassembly_get_integrity_panel' => [
        'classname' => 'mod_uckkassembly\external\get_integrity_panel',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return permission-filtered integrity panel data for a UCKK Assembly.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:view',
    ],

    'mod_uckkassembly_open_integrity_case' => [
        'classname' => 'mod_uckkassembly\external\open_integrity_case',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Open an integrity case linked to a UCKK Assembly object.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/uckkintegrity:opencase',
    ],

    'mod_uckkassembly_get_archive_preview' => [
        'classname' => 'mod_uckkassembly\external\get_archive_preview',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered archive preview for a UCKK Assembly.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:archive',
    ],

    'mod_uckkassembly_archive_assembly' => [
        'classname' => 'mod_uckkassembly\external\archive_assembly',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create an archive record from a UCKK Assembly.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkassembly:archive',
    ],
];

$services = [
    'UCKK Assembly services' => [
        'functions' => [
            'mod_uckkassembly_get_assembly_state',
            'mod_uckkassembly_get_motion_list',
            'mod_uckkassembly_get_motion',
            'mod_uckkassembly_submit_motion',
            'mod_uckkassembly_submit_amendment',
            'mod_uckkassembly_submit_objection',
            'mod_uckkassembly_submit_vote',
            'mod_uckkassembly_get_vote_results',
            'mod_uckkassembly_get_decision',
            'mod_uckkassembly_publish_decision',
            'mod_uckkassembly_contest_decision',
            'mod_uckkassembly_get_minutes_panel',
            'mod_uckkassembly_save_minutes',
            'mod_uckkassembly_publish_minutes',
            'mod_uckkassembly_get_integrity_panel',
            'mod_uckkassembly_open_integrity_case',
            'mod_uckkassembly_get_archive_preview',
            'mod_uckkassembly_archive_assembly',
        ],
        'enabled' => 0,
        'restrictedusers' => 1,
        'shortname' => 'uckkassembly',
        'downloadfiles' => 0,
        'uploadfiles' => 1,
    ],
];