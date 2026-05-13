<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event observers for the UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers.
 *
 * This file only declares observer wiring. The actual logic belongs in
 * \mod_uckkassembly\observer and service classes.
 */
$observers = [
    [
        'eventname' => '\mod_uckkassembly\event\assembly_created',
        'callback' => '\mod_uckkassembly\observer::assembly_created',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\assembly_viewed',
        'callback' => '\mod_uckkassembly\observer::assembly_viewed',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\assembly_updated',
        'callback' => '\mod_uckkassembly\observer::assembly_updated',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\assembly_archived',
        'callback' => '\mod_uckkassembly\observer::assembly_archived',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\motion_created',
        'callback' => '\mod_uckkassembly\observer::motion_created',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\motion_amended',
        'callback' => '\mod_uckkassembly\observer::motion_amended',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\motion_withdrawn',
        'callback' => '\mod_uckkassembly\observer::motion_withdrawn',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\objection_created',
        'callback' => '\mod_uckkassembly\observer::objection_created',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\vote_cast',
        'callback' => '\mod_uckkassembly\observer::vote_cast',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\decision_published',
        'callback' => '\mod_uckkassembly\observer::decision_published',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\decision_contested',
        'callback' => '\mod_uckkassembly\observer::decision_contested',
    ],
    [
        'eventname' => '\mod_uckkassembly\event\minutes_published',
        'callback' => '\mod_uckkassembly\observer::minutes_published',
    ],

    // Archive bridge.
    [
        'eventname' => '\mod_uckkarchive\event\archive_item_validated',
        'callback' => '\mod_uckkassembly\observer::archive_item_validated',
    ],
    [
        'eventname' => '\mod_uckkarchive\event\archive_item_revised',
        'callback' => '\mod_uckkassembly\observer::archive_item_revised',
    ],

    // Integrity bridge.
    [
        'eventname' => '\tool_uckkintegrity\event\case_opened',
        'callback' => '\mod_uckkassembly\observer::integrity_case_opened',
    ],
    [
        'eventname' => '\tool_uckkintegrity\event\correction_issued',
        'callback' => '\mod_uckkassembly\observer::integrity_correction_issued',
    ],
    [
        'eventname' => '\tool_uckkintegrity\event\case_closed',
        'callback' => '\mod_uckkassembly\observer::integrity_case_closed',
    ],
];