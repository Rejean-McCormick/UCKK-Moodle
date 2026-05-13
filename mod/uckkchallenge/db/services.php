<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * External service declarations for Défis King Klown.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_uckkchallenge_get_challenge' => [
        'classname' => 'mod_uckkchallenge\external\get_challenge',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered UCKK challenge view model.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_get_proof_list' => [
        'classname' => 'mod_uckkchallenge\external\get_proof_list',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the permission-filtered proof list for a UCKK challenge.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_get_submission_status' => [
        'classname' => 'mod_uckkchallenge\external\get_submission_status',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the current user submission status for a UCKK challenge.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:view,mod/uckkchallenge:submitproof',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_save_proof_draft' => [
        'classname' => 'mod_uckkchallenge\external\save_proof_draft',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Save a draft proof submission for a UCKK challenge.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:submitproof',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_submit_proof' => [
        'classname' => 'mod_uckkchallenge\external\submit_proof',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Submit proof for a UCKK challenge.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:submitproof',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_get_evaluation_panel' => [
        'classname' => 'mod_uckkchallenge\external\get_evaluation_panel',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the permission-filtered evaluation panel for a UCKK challenge proof.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:evaluate',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_evaluate_proof' => [
        'classname' => 'mod_uckkchallenge\external\evaluate_proof',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Save mentor evaluation for a UCKK challenge proof.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:evaluate',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_get_integrity_summary' => [
        'classname' => 'mod_uckkchallenge\external\get_integrity_summary',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the permission-filtered integrity summary for a UCKK challenge.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/uckkintegrity:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_open_integrity_case' => [
        'classname' => 'mod_uckkchallenge\external\open_integrity_case',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Open an integrity case linked to a UCKK challenge.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/uckkintegrity:opencase',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_contest_challenge' => [
        'classname' => 'mod_uckkchallenge\external\contest_challenge',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Contest a UCKK challenge outcome or proof decision.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:contest',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_validate_integrity' => [
        'classname' => 'mod_uckkchallenge\external\validate_integrity',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Record integrity validation for a UCKK challenge proof or challenge state.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:validateintegrity',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_get_archive_preview' => [
        'classname' => 'mod_uckkchallenge\external\get_archive_preview',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a preview of the archive package for a UCKK challenge.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:archive',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkchallenge_archive_challenge' => [
        'classname' => 'mod_uckkchallenge\external\archive_challenge',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create an archive item from a validated UCKK challenge.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkchallenge:archive',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
];

$services = [
    'UCKK challenge service' => [
        'functions' => [
            'mod_uckkchallenge_get_challenge',
            'mod_uckkchallenge_get_proof_list',
            'mod_uckkchallenge_get_submission_status',
            'mod_uckkchallenge_save_proof_draft',
            'mod_uckkchallenge_submit_proof',
            'mod_uckkchallenge_get_evaluation_panel',
            'mod_uckkchallenge_evaluate_proof',
            'mod_uckkchallenge_get_integrity_summary',
            'mod_uckkchallenge_open_integrity_case',
            'mod_uckkchallenge_contest_challenge',
            'mod_uckkchallenge_validate_integrity',
            'mod_uckkchallenge_get_archive_preview',
            'mod_uckkchallenge_archive_challenge',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'uckkchallenge',
        'downloadfiles' => 1,
        'uploadfiles' => 1,
    ],
];