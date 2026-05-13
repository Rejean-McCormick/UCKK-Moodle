<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Event observers for the UCKK local plugin.
 *
 * This file declares subscriptions to Moodle core events that affect
 * local_uckk-owned registry data: programs, pathways, player profiles,
 * symbolic roles, provenance records, visibility records, and pathway stats.
 *
 * UCKK domain events emitted by this plugin are defined separately in:
 *
 * - local/uckk/classes/event/program_created.php
 * - local/uckk/classes/event/pathway_assigned.php
 * - local/uckk/classes/event/profile_updated.php
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_uckk\observer\user_observer::user_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_uckk\observer\course_observer::course_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\course_category_deleted',
        'callback' => '\local_uckk\observer\category_observer::course_category_deleted',
        'internal' => false,
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\local_uckk\observer\completion_observer::course_completed',
        'internal' => false,
        'priority' => 9999,
    ],
];