<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * External service declarations for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_uckkarchive_get_archive' => [
        'classname' => 'mod_uckkarchive\external\get_archive',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered UCKK archive activity view model.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_archive_items' => [
        'classname' => 'mod_uckkarchive\external\get_archive_items',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return permission-filtered archive items for a UCKK archive activity.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_archive_item' => [
        'classname' => 'mod_uckkarchive\external\get_archive_item',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered UCKK archive item view model.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_archive_item_card' => [
        'classname' => 'mod_uckkarchive\external\get_archive_item_card',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return rendered card data for one permission-filtered UCKK archive item.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_proofs' => [
        'classname' => 'mod_uckkarchive\external\get_proofs',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return permission-filtered proof records linked to a UCKK archive item.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_provenance_panel' => [
        'classname' => 'mod_uckkarchive\external\get_provenance_panel',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return provenance, source, validation, and visibility data for a UCKK archive item.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_kristal' => [
        'classname' => 'mod_uckkarchive\external\get_kristal',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered UCKK Kristal view model.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_revisions' => [
        'classname' => 'mod_uckkarchive\external\get_revisions',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return permission-filtered revision history for a UCKK archive item.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_restricted_item' => [
        'classname' => 'mod_uckkarchive\external\get_restricted_item',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a restricted UCKK archive item for authorised restricted viewers.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:viewrestricted',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_save_item_draft' => [
        'classname' => 'mod_uckkarchive\external\save_item_draft',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Save a draft UCKK archive item without validating or publishing it.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:additem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_add_item' => [
        'classname' => 'mod_uckkarchive\external\add_item',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create a UCKK archive item with provenance, visibility, validation, and context metadata.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:additem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_add_proof' => [
        'classname' => 'mod_uckkarchive\external\add_proof',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Attach a proof record to a UCKK archive item.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:additem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_update_provenance' => [
        'classname' => 'mod_uckkarchive\external\update_provenance',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Update provenance metadata for a UCKK archive item or proof record.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:reviseitem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_validate_item' => [
        'classname' => 'mod_uckkarchive\external\validate_item',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Record authorised human validation for a UCKK archive item.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:validateitem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_revise_item' => [
        'classname' => 'mod_uckkarchive\external\revise_item',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create a versioned revision of a UCKK archive item without deleting prior evidence.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:reviseitem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_create_kristal' => [
        'classname' => 'mod_uckkarchive\external\create_kristal',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create a UCKK Kristal from selected archive items.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:validateitem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_update_kristal' => [
        'classname' => 'mod_uckkarchive\external\update_kristal',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Update a UCKK Kristal through authorised archive workflow.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:reviseitem',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_export_preview' => [
        'classname' => 'mod_uckkarchive\external\get_export_preview',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return a permission-filtered preview of a UCKK archive export package.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:export',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_export_items' => [
        'classname' => 'mod_uckkarchive\external\export_items',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Create a UCKK archive export package for authorised archive items.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:export',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],

    'mod_uckkarchive_get_export_status' => [
        'classname' => 'mod_uckkarchive\external\get_export_status',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Return the status of a UCKK archive export package.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/uckkarchive:export',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
];

$services = [
    'UCKK archive service' => [
        'functions' => [
            'mod_uckkarchive_get_archive',
            'mod_uckkarchive_get_archive_items',
            'mod_uckkarchive_get_archive_item',
            'mod_uckkarchive_get_archive_item_card',
            'mod_uckkarchive_get_proofs',
            'mod_uckkarchive_get_provenance_panel',
            'mod_uckkarchive_get_kristal',
            'mod_uckkarchive_get_revisions',
            'mod_uckkarchive_get_restricted_item',
            'mod_uckkarchive_save_item_draft',
            'mod_uckkarchive_add_item',
            'mod_uckkarchive_add_proof',
            'mod_uckkarchive_update_provenance',
            'mod_uckkarchive_validate_item',
            'mod_uckkarchive_revise_item',
            'mod_uckkarchive_create_kristal',
            'mod_uckkarchive_update_kristal',
            'mod_uckkarchive_get_export_preview',
            'mod_uckkarchive_export_items',
            'mod_uckkarchive_get_export_status',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'uckkarchive',
        'downloadfiles' => 1,
        'uploadfiles' => 1,
    ],
];