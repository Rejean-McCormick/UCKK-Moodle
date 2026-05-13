<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme configuration for UCKK.
 *
 * UCKK-Moodle is the Moodle campus implementation of the
 * Univers-Cité King Klown. This theme owns visual identity,
 * layout selection, SCSS loading and renderer overrides only.
 *
 * It must not contain grading logic, permission logic, workflow
 * logic, archive logic, integrity logic, or data ownership.
 *
 * @package    theme_uckk
 * @copyright  2026 Momus et Bouche Cousue
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'uckk';

/*
 * UCKK is implemented as a Moodle-native theme layer.
 *
 * Boost remains the parent theme to preserve Moodle compatibility,
 * accessibility behaviour, navigation structure, drawer behaviour,
 * course rendering conventions and future upgrade safety.
 */
$THEME->parents = ['boost'];

/*
 * No legacy stylesheet loading.
 *
 * CSS must be produced through SCSS in lib.php via
 * theme_uckk_get_main_scss_content().
 */
$THEME->sheets = [];
$THEME->editor_sheets = [];

/*
 * UCKK uses its own SCSS pipeline while inheriting the Boost base.
 *
 * The implementation function belongs in:
 * theme/uckk/lib.php
 */
$THEME->scss = function($theme) {
    return theme_uckk_get_main_scss_content($theme);
};

/*
 * Moodle should fall back to parent theme templates/renderers
 * when UCKK does not override them.
 */
$THEME->usefallback = true;

/*
 * UCKK can override renderers where presentation requires it.
 *
 * Business logic must remain in plugins and services:
 * - local_uckk
 * - mod_uckkchallenge
 * - mod_uckkassembly
 * - mod_uckkarchive
 * - tool_uckkintegrity
 * - report_uckk
 */
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

/*
 * Post-process CSS through the Boost-compatible postprocessor.
 *
 * The function is provided by the Boost parent theme and keeps
 * UCKK aligned with Moodle's standard theme processing pipeline.
 */
$THEME->csspostprocess = 'theme_boost_csspostprocess';

/*
 * UCKK layout strategy.
 *
 * The theme provides a small set of explicit layout files:
 *
 * - columns2.php   : standard Moodle pages with side-pre block region.
 * - frontpage.php  : UCKK campus public/front page.
 * - course.php     : course pages, including format_uckk.
 * - dashboard.php  : dashboards and user-centric UCKK cockpit pages.
 * - embedded.php   : minimal embedded/frametop/pop-up pages.
 *
 * Layout files must remain presentation-only. They must not decide
 * academic status, challenge state, assembly state, archive validation,
 * or integrity outcomes.
 */
$THEME->layouts = [
    /*
     * Base layout without blocks.
     */
    'base' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],

    /*
     * Standard site pages.
     */
    'standard' => [
        'file' => 'columns2.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    /*
     * Main course page.
     */
    'course' => [
        'file' => 'course.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => [
            'langmenu' => true,
        ],
    ],

    /*
     * Course category pages.
     */
    'coursecategory' => [
        'file' => 'columns2.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    /*
     * Incourse pages such as activities, resources and plugin pages.
     */
    'incourse' => [
        'file' => 'course.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    /*
     * The site front page becomes the UCKK campus gate.
     *
     * This layout may display:
     * - Univers-Cité King Klown identity;
     * - entry points to courses;
     * - Défis;
     * - Assemblées;
     * - Archives;
     * - Inquisiteur orientation;
     * - non-accreditation clarity where needed.
     */
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => [
            'nonavbar' => false,
        ],
    ],

    /*
     * Dashboard pages.
     *
     * Used for Moodle dashboard pages and UCKK user cockpit pages.
     */
    'mydashboard' => [
        'file' => 'dashboard.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => [
            'nonavbar' => false,
            'langmenu' => true,
        ],
    ],

    /*
     * My courses page.
     */
    'mycourses' => [
        'file' => 'dashboard.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => [
            'nonavbar' => false,
            'langmenu' => true,
        ],
    ],

    /*
     * Admin pages.
     *
     * UCKK does not replace Moodle administration. It only adds
     * UCKK-specific tools through standard plugins.
     */
    'admin' => [
        'file' => 'columns2.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    /*
     * Report pages.
     */
    'report' => [
        'file' => 'columns2.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    /*
     * Login page.
     */
    'login' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'langmenu' => true,
        ],
    ],

    /*
     * Popup windows.
     */
    'popup' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],

    /*
     * Embedded pages.
     */
    'embedded' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],

    /*
     * Maintenance pages.
     */
    'maintenance' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],

    /*
     * Secure pages such as quiz attempts or other locked contexts.
     *
     * These pages intentionally avoid UCKK theatrical elements that could
     * distract from assessment, privacy, or procedural clarity.
     */
    'secure' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],

    /*
     * Printable pages.
     */
    'print' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => false,
        ],
    ],

    /*
     * Redirect and frametop pages.
     */
    'redirect' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],

    'frametop' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => [
            'nofooter' => true,
            'nonavbar' => true,
        ],
    ],
];

/*
 * Block regions.
 *
 * UCKK keeps the Moodle-standard side-pre region for compatibility with
 * course pages, dashboards, reports and administration pages.
 */
$THEME->blockrtlmanipulations = [];

/*
 * Required by modern Moodle theme behaviour.
 *
 * The icon system is inherited from Boost unless overridden by UCKK
 * through pix, pix_core, or pix_plugins.
 */
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;

/*
 * Preset support.
 *
 * The default SCSS preset is expected at:
 * theme/uckk/scss/preset/default.scss
 */
$THEME->prescsscallback = 'theme_uckk_get_pre_scss';
$THEME->extrascsscallback = 'theme_uckk_get_extra_scss';

/*
 * H5P and other embedded content should inherit the final compiled
 * theme styling where Moodle supports it.
 */
$THEME->haseditswitch = true;

/*
 * Activity navigation and course editing must remain Moodle-compatible.
 *
 * UCKK visual identity must never break Moodle's core course editing,
 * activity chooser, navigation drawer, or accessibility behaviour.
 */
$THEME->activityheaderconfig = [
    'notitle' => false,
];