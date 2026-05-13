<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event observers for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers.
 *
 * Observer methods must live in:
 *
 * mod/uckkchallenge/classes/observer.php
 *
 * with static methods such as:
 *
 * \mod_uckkchallenge\observer::challenge_viewed()
 * \mod_uckkchallenge\observer::submission_submitted()
 * \mod_uckkchallenge\observer::challenge_archived()
 *
 * Observers must delegate to services. They must not perform permission
 * decisions, workflow transitions, grading, badge awards, archive mutation, or
 * integrity decisions directly inside the observer method.
 */
$observers = [
    [
        'eventname' => \mod_uckkchallenge\event\challenge_viewed::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_viewed',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_created::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_created',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_updated::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_updated',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_published::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_published',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\submission_created::class,
        'callback' => \mod_uckkchallenge\observer::class . '::submission_created',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\submission_updated::class,
        'callback' => \mod_uckkchallenge\observer::class . '::submission_updated',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\submission_submitted::class,
        'callback' => \mod_uckkchallenge\observer::class . '::submission_submitted',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\evaluation_created::class,
        'callback' => \mod_uckkchallenge\observer::class . '::evaluation_created',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\evaluation_updated::class,
        'callback' => \mod_uckkchallenge\observer::class . '::evaluation_updated',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\integrity_review_started::class,
        'callback' => \mod_uckkchallenge\observer::class . '::integrity_review_started',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\correction_required::class,
        'callback' => \mod_uckkchallenge\observer::class . '::correction_required',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_contested::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_contested',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_invalidated::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_invalidated',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_validated::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_validated',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_archived::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_archived',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \mod_uckkchallenge\event\challenge_closed::class,
        'callback' => \mod_uckkchallenge\observer::class . '::challenge_closed',
        'priority' => 9999,
        'internal' => false,
    ],

    [
        'eventname' => \core\event\course_module_deleted::class,
        'callback' => \mod_uckkchallenge\observer::class . '::course_module_deleted',
        'priority' => 9999,
        'internal' => false,
    ],
];