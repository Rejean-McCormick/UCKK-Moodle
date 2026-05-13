<?php
// This file is part of UCKK-Moodle.
//
// UCKK-Moodle is built as a Moodle-native plugin distribution. The local_uckk
// plugin owns the shared institutional registry and service layer for UCKK.

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callback declarations for local_uckk.
 *
 * Moodle registers hook listeners through db/hooks.php. The callback target is
 * intentionally kept in local_uckk\hook_listener so this file stays declarative.
 *
 * The listener must not perform database work during registration because hooks
 * can be discovered during install and upgrade.
 */
$callbacks = [
    [
        'hook' => \core\hook\di_configuration::class,
        'callback' => \local_uckk\hook_listener::class . '::configure_container',
        'priority' => 0,
    ],
];