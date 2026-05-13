<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event observers for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK Archive event observers.
 *
 * These observers are intentionally limited to stable Moodle lifecycle events.
 * Cross-plugin archival actions from challenges, assemblies, integrity reviews,
 * reports, or seed tools should call mod_uckkarchive services directly unless
 * the source plugin event classes are guaranteed to exist.
 */
$observers = [
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\mod_uckkarchive\observer::course_module_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\mod_uckkarchive\observer::course_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\mod_uckkarchive\observer::user_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
];