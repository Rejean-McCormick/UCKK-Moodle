<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Theme configuration for UCKK.
 *
 * UCKK remains a thin Boost child theme. Boost owns standard Moodle UX:
 * drawers, editing controls, reports, forms, grading screens and activity UI.
 *
 * theme_uckk owns:
 * - global UCKK visual identity tokens;
 * - SCSS loading;
 * - renderer overrides;
 * - the public campus gate layout;
 * - the dedicated local_uckk public page layout shell.
 *
 * It must not contain grading logic, permission logic, workflow logic,
 * archive logic, integrity logic or data ownership.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$THEME->name = 'uckk';
$THEME->parents = ['boost'];

$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usefallback = true;

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

/*
 * SCSS callbacks.
 *
 * These callbacks are implemented in theme/uckk/lib.php.
 * Do not wrap them in function_exists() here: Moodle loads the theme library
 * before executing the callbacks, but not necessarily before reading config.php.
 */
$THEME->scss = function($theme) {
    return theme_uckk_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_uckk_get_pre_scss';
$THEME->extrascsscallback = 'theme_uckk_get_extra_scss';

/*
 * Layout contracts.
 *
 * The default Moodle screens deliberately delegate to Boost drawers.
 * UCKK-owned public pages use the custom local_uckk_public layout.
 */
$uckkdrawers = [
    'theme' => 'boost',
    'file' => 'drawers.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
];

$uckkdrawersnoregions = [
    'theme' => 'boost',
    'file' => 'drawers.php',
    'regions' => [],
];

$uckkcolumns1 = [
    'theme' => 'boost',
    'file' => 'columns1.php',
    'regions' => [],
];

$THEME->layouts = [
    'base' => $uckkdrawersnoregions,

    'standard' => $uckkdrawers,

    'course' => $uckkdrawers + [
        'options' => [
            'langmenu' => true,
        ],
    ],

    'coursecategory' => $uckkdrawers,

    'incourse' => $uckkdrawers,

    /*
     * UCKK public campus gate.
     *
     * This is the only standard Moodle layout owned by theme_uckk directly.
     */
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => [
            'nonavbar' => false,
            'langmenu' => true,
        ],
    ],

    /*
     * Public institutional pages from local_uckk.
     *
     * Controllers or \local_uckk\local\public_pages::setup_page() may set:
     * $PAGE->set_pagelayout('local_uckk_public');
     *
     * Baseline recovery:
     * keep this on Boost drawers.php for now. This restores the previous
     * working state. Fix the mosaic through local_uckk/styles.css and asset
     * paths before attempting a custom layout shell again.
     */
    'local_uckk_public' => [
        'theme' => 'boost',
        'file' => 'drawers.php',
        'regions' => [],
        'options' => [
            'nonavbar' => false,
            'langmenu' => true,
        ],
    ],

    'mydashboard' => $uckkdrawers + [
        'options' => [
            'nonavbar' => true,
            'langmenu' => true,
        ],
    ],

    'mycourses' => $uckkdrawers + [
        'options' => [
            'nonavbar' => true,
        ],
    ],

    'mypublic' => $uckkdrawers,

    'admin' => $uckkdrawers,

    'report' => $uckkdrawers,

    'login' => [
        'theme' => 'boost',
        'file' => 'login.php',
        'regions' => [],
        'options' => [
            'langmenu' => true,
        ],
    ],

    'popup' => $uckkcolumns1 + [
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
            'activityheader' => [
                'notitle' => true,
                'nocompletion' => true,
                'nodescription' => true,
            ],
        ],
    ],

    'frametop' => $uckkcolumns1 + [
        'options' => [
            'nofooter' => true,
            'nocoursefooter' => true,
            'activityheader' => [
                'nocompletion' => true,
            ],
        ],
    ],

    'embedded' => [
        'theme' => 'boost',
        'file' => 'embedded.php',
        'regions' => [],
    ],

    'maintenance' => [
        'theme' => 'boost',
        'file' => 'maintenance.php',
        'regions' => [],
    ],

    'secure' => $uckkdrawers,

    'print' => $uckkcolumns1 + [
        'options' => [
            'nofooter' => true,
            'nonavbar' => false,
            'noactivityheader' => true,
        ],
    ],

    'redirect' => [
        'theme' => 'boost',
        'file' => 'embedded.php',
        'regions' => [],
    ],
];

unset($uckkdrawers, $uckkdrawersnoregions, $uckkcolumns1);
