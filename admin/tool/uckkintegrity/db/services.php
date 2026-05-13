<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * External service declarations for tool_uckkintegrity.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 UCKK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'tool_uckkintegrity_open_case' => [
        'classname' => 'tool_uckkintegrity\external\open_case',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Open a UCKK integrity case.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/uckkintegrity:opencase',
    ],
];

$services = [
    'UCKK integrity services' => [
        'functions' => [
            'tool_uckkintegrity_open_case',
        ],
        'restrictedusers' => 1,
        'enabled' => 0,
        'shortname' => 'uckkintegrity',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];