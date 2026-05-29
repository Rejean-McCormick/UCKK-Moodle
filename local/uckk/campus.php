<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Authenticated UCKK campus hub.
 *
 * This page is the internal authenticated campus hub for the local_uckk plugin.
 * It provides a coherent campus hub for UCKK-Moodle without performing
 * business workflow actions directly. The public home page lives in
 * /local/uckk/index.php.
 *
 * It must not:
 * - create or modify programs;
 * - validate challenges;
 * - publish assembly decisions;
 * - validate archive items;
 * - open or close integrity cases;
 * - make AI decisions;
 * - replace Moodle enrolment, completion, badge, competency or role systems.
 *
 * Those responsibilities belong to their respective plugins, services,
 * capabilities and workflows.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$url = new moodle_url('/local/uckk/campus.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_uckk'));
$PAGE->set_heading(get_string('uckkfullname', 'local_uckk'));

require_capability('local/uckk:viewcampus', $context);

$output = $PAGE->get_renderer('local_uckk');

$pluginmanager = core_plugin_manager::instance();

$haschallenge = core_component::get_plugin_directory('mod', 'uckkchallenge') !== null;
$hasassembly = core_component::get_plugin_directory('mod', 'uckkassembly') !== null;
$hasarchive = core_component::get_plugin_directory('mod', 'uckkarchive') !== null;
$hasintegrity = core_component::get_plugin_directory('tool', 'uckkintegrity') !== null;
$hasseed = core_component::get_plugin_directory('tool', 'uckkseed') !== null;
$hasreport = core_component::get_plugin_directory('report', 'uckk') !== null;
$hasdashboard = core_component::get_plugin_directory('block', 'uckk_dashboard') !== null;
$hasformat = core_component::get_plugin_directory('format', 'uckk') !== null;
$hastheme = core_component::get_plugin_directory('theme', 'uckk') !== null;
$hasai = core_component::get_plugin_directory('aiprovider', 'uckk') !== null;

$canmanageprograms = has_capability('local/uckk:manageprograms', $context);
$canmanagepathways = has_capability('local/uckk:managepathways', $context);
$canmanageprofiles = has_capability('local/uckk:manageprofiles', $context);
$canmanagecanon = has_capability('local/uckk:managecanon', $context);
$canviewreports = has_capability('local/uckk:viewreports', $context);
$canexportdata = has_capability('local/uckk:exportdata', $context);

$cards = [
    [
        'key' => 'programs',
        'title' => get_string('campus_programs_title', 'local_uckk'),
        'description' => get_string('campus_programs_desc', 'local_uckk'),
        'url' => (new moodle_url('/local/uckk/programs.php'))->out(false),
        'enabled' => $canmanageprograms || $canmanagepathways || has_capability('local/uckk:viewcampus', $context),
        'primary' => true,
        'status' => get_string('status_available', 'local_uckk'),
        'icon' => 'programs',
    ],
    [
        'key' => 'pathways',
        'title' => get_string('campus_pathways_title', 'local_uckk'),
        'description' => get_string('campus_pathways_desc', 'local_uckk'),
        'url' => (new moodle_url('/local/uckk/pathways.php'))->out(false),
        'enabled' => $canmanagepathways || has_capability('local/uckk:viewcampus', $context),
        'primary' => true,
        'status' => get_string('status_available', 'local_uckk'),
        'icon' => 'pathways',
    ],
    [
        'key' => 'canon',
        'title' => get_string('campus_canon_title', 'local_uckk'),
        'description' => get_string('campus_canon_desc', 'local_uckk'),
        'url' => (new moodle_url('/local/uckk/canon.php'))->out(false),
        'enabled' => $canmanagecanon || has_capability('local/uckk:viewcampus', $context),
        'primary' => true,
        'status' => get_string('status_available', 'local_uckk'),
        'icon' => 'canon',
    ],
    [
        'key' => 'dashboard',
        'title' => get_string('campus_dashboard_title', 'local_uckk'),
        'description' => get_string('campus_dashboard_desc', 'local_uckk'),
        'url' => (new moodle_url('/my/'))->out(false),
        'enabled' => $hasdashboard,
        'primary' => false,
        'status' => $hasdashboard ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'dashboard',
    ],
    [
        'key' => 'format',
        'title' => get_string('campus_format_title', 'local_uckk'),
        'description' => get_string('campus_format_desc', 'local_uckk'),
        'url' => (new moodle_url('/course/'))->out(false),
        'enabled' => $hasformat,
        'primary' => false,
        'status' => $hasformat ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'course',
    ],
    [
        'key' => 'challenges',
        'title' => get_string('campus_challenges_title', 'local_uckk'),
        'description' => get_string('campus_challenges_desc', 'local_uckk'),
        'url' => $haschallenge ? (new moodle_url('/mod/uckkchallenge/index.php'))->out(false) : '',
        'enabled' => $haschallenge,
        'primary' => false,
        'status' => $haschallenge ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'challenge',
    ],
    [
        'key' => 'assemblies',
        'title' => get_string('campus_assemblies_title', 'local_uckk'),
        'description' => get_string('campus_assemblies_desc', 'local_uckk'),
        'url' => $hasassembly ? (new moodle_url('/mod/uckkassembly/index.php'))->out(false) : '',
        'enabled' => $hasassembly,
        'primary' => false,
        'status' => $hasassembly ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'assembly',
    ],
    [
        'key' => 'archives',
        'title' => get_string('campus_archives_title', 'local_uckk'),
        'description' => get_string('campus_archives_desc', 'local_uckk'),
        'url' => $hasarchive ? (new moodle_url('/mod/uckkarchive/index.php'))->out(false) : '',
        'enabled' => $hasarchive,
        'primary' => false,
        'status' => $hasarchive ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'archive',
    ],
    [
        'key' => 'integrity',
        'title' => get_string('campus_integrity_title', 'local_uckk'),
        'description' => get_string('campus_integrity_desc', 'local_uckk'),
        'url' => $hasintegrity ? (new moodle_url('/admin/tool/uckkintegrity/index.php'))->out(false) : '',
        'enabled' => $hasintegrity,
        'primary' => false,
        'status' => $hasintegrity ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'integrity',
    ],
    [
        'key' => 'reports',
        'title' => get_string('campus_reports_title', 'local_uckk'),
        'description' => get_string('campus_reports_desc', 'local_uckk'),
        'url' => $hasreport ? (new moodle_url('/report/uckk/index.php'))->out(false) : '',
        'enabled' => $hasreport && $canviewreports,
        'primary' => false,
        'status' => $hasreport ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'reports',
    ],
    [
        'key' => 'seed',
        'title' => get_string('campus_seed_title', 'local_uckk'),
        'description' => get_string('campus_seed_desc', 'local_uckk'),
        'url' => $hasseed ? (new moodle_url('/admin/tool/uckkseed/index.php'))->out(false) : '',
        'enabled' => $hasseed && is_siteadmin(),
        'primary' => false,
        'status' => $hasseed ? get_string('status_available', 'local_uckk') : get_string('status_missingplugin', 'local_uckk'),
        'icon' => 'seed',
    ],
];

$quicklinks = [
    [
        'label' => get_string('quicklink_mycourses', 'local_uckk'),
        'url' => (new moodle_url('/my/courses.php'))->out(false),
    ],
    [
        'label' => get_string('quicklink_preferences', 'local_uckk'),
        'url' => (new moodle_url('/user/preferences.php'))->out(false),
    ],
];

if ($canexportdata) {
    $quicklinks[] = [
        'label' => get_string('quicklink_export', 'local_uckk'),
        'url' => (new moodle_url('/local/uckk/export.php'))->out(false),
    ];
}

$integrations = [
    [
        'key' => 'theme',
        'label' => get_string('integration_theme', 'local_uckk'),
        'installed' => $hastheme,
    ],
    [
        'key' => 'courseformat',
        'label' => get_string('integration_courseformat', 'local_uckk'),
        'installed' => $hasformat,
    ],
    [
        'key' => 'dashboardblock',
        'label' => get_string('integration_dashboardblock', 'local_uckk'),
        'installed' => $hasdashboard,
    ],
    [
        'key' => 'challenge',
        'label' => get_string('integration_challenge', 'local_uckk'),
        'installed' => $haschallenge,
    ],
    [
        'key' => 'assembly',
        'label' => get_string('integration_assembly', 'local_uckk'),
        'installed' => $hasassembly,
    ],
    [
        'key' => 'archive',
        'label' => get_string('integration_archive', 'local_uckk'),
        'installed' => $hasarchive,
    ],
    [
        'key' => 'integrity',
        'label' => get_string('integration_integrity', 'local_uckk'),
        'installed' => $hasintegrity,
    ],
    [
        'key' => 'reports',
        'label' => get_string('integration_reports', 'local_uckk'),
        'installed' => $hasreport,
    ],
    [
        'key' => 'ai',
        'label' => get_string('integration_ai', 'local_uckk'),
        'installed' => $hasai,
    ],
];

$templatecontext = [
    'title' => get_string('campus_title', 'local_uckk'),
    'subtitle' => get_string('campus_subtitle', 'local_uckk'),
    'intro' => get_string('campus_intro', 'local_uckk'),
    'tagline' => get_string('campus_tagline', 'local_uckk'),
    'formula' => get_string('campus_formula', 'local_uckk'),
    'boundarynotice' => get_string('campus_boundary_notice', 'local_uckk'),
    'recognitionnotice' => get_string('campus_recognition_notice', 'local_uckk'),
    'cards' => $cards,
    'hascards' => !empty($cards),
    'quicklinks' => $quicklinks,
    'hasquicklinks' => !empty($quicklinks),
    'integrations' => $integrations,
    'hasintegrations' => !empty($integrations),
    'canmanageprograms' => $canmanageprograms,
    'canmanagepathways' => $canmanagepathways,
    'canmanageprofiles' => $canmanageprofiles,
    'canmanagecanon' => $canmanagecanon,
    'canviewreports' => $canviewreports,
    'canexportdata' => $canexportdata,
];

echo $output->header();
echo $output->render_from_template('local_uckk/index', $templatecontext);
echo $output->footer();