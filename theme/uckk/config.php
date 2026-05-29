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
 * - public visual polish;
 * - the Moodle root layout adapter that prevents the native course-list
 *   frontpage from being exposed at /.
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
 * Default Moodle screens delegate to Boost drawers.
 * Public UCKK plugin pages also delegate to Boost drawers so the Moodle top bar,
 * user menu, language menu and standard navigation remain consistent.
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

$uckkpublic = $uckkdrawersnoregions + [
    'options' => [
        'nonavbar' => false,
        'langmenu' => true,
    ],
];

$uckkfrontpage = [
    'theme' => 'uckk',
    'file' => 'frontpage.php',
    'regions' => [],
    'options' => [
        'nonavbar' => true,
        'langmenu' => true,
    ],
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
     * Moodle root page (/).
     *
     * This must use the UCKK frontpage adapter, not Boost drawers.php, because
     * Moodle's native frontpage body may expose the configured course list.
     *
     * The adapter renders the local_uckk public home page and keeps
     * $OUTPUT->main_content() only as a hidden layout-contract fallback.
     */
    'frontpage' => $uckkfrontpage,

    /*
     * Public institutional pages from local_uckk.
     *
     * Controllers or \local_uckk\local\public_pages::setup_page() may set:
     * $PAGE->set_pagelayout('local_uckk_public');
     *
     * Keep plugin public pages on Boost drawers.php so they retain the Moodle
     * top bar and shell.
     */
    'local_uckk_public' => $uckkpublic,

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

unset($uckkdrawers, $uckkdrawersnoregions, $uckkcolumns1, $uckkpublic, $uckkfrontpage);