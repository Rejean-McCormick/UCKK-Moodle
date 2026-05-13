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
 * Course layout for the UCKK theme.
 *
 * This layout is intentionally Boost-compatible. It keeps Moodle's drawer
 * structure, course index, block drawer and standard output regions intact,
 * while adding UCKK-specific context values that may be consumed by theme
 * templates, renderers, JavaScript, SCSS or future layout overrides.
 *
 * The layout must remain presentation-oriented. It must not contain UCKK
 * business rules, grading logic, integrity decisions, challenge workflows,
 * assembly procedures or archive validation rules.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $OUTPUT, $SITE, $COURSE;

require_once($CFG->libdir . '/behat/lib.php');

// -----------------------------------------------------------------------------
// Standard Moodle / Boost layout context.
// -----------------------------------------------------------------------------

$bodyclasses = [
    'theme-uckk',
    'theme-uckk-course',
    'uckk-layout-course',
];

if (!empty($PAGE->course) && !empty($PAGE->course->format)) {
    $bodyclasses[] = 'uckk-course-format-' . clean_param($PAGE->course->format, PARAM_ALPHANUMEXT);
}

if (!empty($COURSE->idnumber)) {
    $bodyclasses[] = 'uckk-course-idnumber-' . clean_param($COURSE->idnumber, PARAM_ALPHANUMEXT);
}

$bodyattributes = $OUTPUT->body_attributes($bodyclasses);

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;

$courseindexopen = get_user_preferences('drawer-open-index', true);
$blockdraweropen = get_user_preferences('drawer-open-block');

$forceblockdraweropen = false;
if (method_exists($OUTPUT, 'firstview_fakeblocks')) {
    $forceblockdraweropen = $OUTPUT->firstview_fakeblocks();
}

$addblockbutton = '';
if (method_exists($OUTPUT, 'addblockbutton')) {
    $addblockbutton = $OUTPUT->addblockbutton();
}

$coursecontext = null;
if (!empty($COURSE->id) && (int)$COURSE->id !== SITEID) {
    $coursecontext = context_course::instance($COURSE->id);
} else {
    $coursecontext = context_course::instance(SITEID);
}

$sitename = format_string(
    $SITE->shortname,
    true,
    [
        'context' => context_course::instance(SITEID),
        'escape' => false,
    ]
);

$coursename = '';
$courseshortname = '';
$courseidnumber = '';
$courseformat = '';

if (!empty($COURSE->id) && (int)$COURSE->id !== SITEID) {
    $coursename = format_string(
        $COURSE->fullname,
        true,
        [
            'context' => $coursecontext,
            'escape' => false,
        ]
    );

    $courseshortname = format_string(
        $COURSE->shortname,
        true,
        [
            'context' => $coursecontext,
            'escape' => false,
        ]
    );

    $courseidnumber = clean_param($COURSE->idnumber ?? '', PARAM_TEXT);
    $courseformat = clean_param($COURSE->format ?? '', PARAM_ALPHANUMEXT);
}

// -----------------------------------------------------------------------------
// UCKK course identity context.
// -----------------------------------------------------------------------------

$isuckkcourse = false;
$isuckktronccommun = false;
$isuckkprogram = false;

if ($courseidnumber !== '') {
    $isuckkcourse = preg_match('/^UCKK-/i', $courseidnumber) === 1;
    $isuckktronccommun = preg_match('/^UCKK-TC/i', $courseidnumber) === 1;
}

if (!$isuckkcourse && $courseshortname !== '') {
    $isuckkcourse = preg_match('/^UCKK-/i', $courseshortname) === 1;
    $isuckktronccommun = preg_match('/^UCKK-TC/i', $courseshortname) === 1;
}

if ($isuckkcourse && !$isuckktronccommun) {
    $isuckkprogram = true;
}

$uckkcoursekind = 'standard';
if ($isuckktronccommun) {
    $uckkcoursekind = 'tronccommun';
} else if ($isuckkprogram) {
    $uckkcoursekind = 'program';
}

$uckkarchiveicon = $OUTPUT->pix_url('archive', 'theme')->out(false);
$uckkchallengeicon = $OUTPUT->pix_url('challenge', 'theme')->out(false);
$uckkassemblyicon = $OUTPUT->pix_url('assembly', 'theme')->out(false);
$uckkintegrityicon = $OUTPUT->pix_url('inquisiteur', 'theme')->out(false);

// -----------------------------------------------------------------------------
// UCKK navigation affordances.
// -----------------------------------------------------------------------------
//
// These URLs are intentionally conservative. They only use existing Moodle routes
// or plugin routes if the plugins are installed. Plugin-specific actions must be
// implemented by their own components.

$uckknavigation = [];

$uckknavigation[] = [
    'key' => 'mypath',
    'label' => get_string('nav_mypath', 'theme_uckk'),
    'url' => new moodle_url('/my/courses.php'),
    'active' => false,
];

if (!empty($COURSE->id) && (int)$COURSE->id !== SITEID) {
    $uckknavigation[] = [
        'key' => 'course',
        'label' => get_string('nav_courses', 'theme_uckk'),
        'url' => new moodle_url('/course/view.php', ['id' => $COURSE->id]),
        'active' => true,
    ];
}

if (core_component::get_plugin_directory('mod', 'uckkchallenge') !== null) {
    $uckknavigation[] = [
        'key' => 'challenges',
        'label' => get_string('nav_challenges', 'theme_uckk'),
        'url' => new moodle_url('/mod/uckkchallenge/index.php', ['id' => $COURSE->id]),
        'active' => false,
    ];
}

if (core_component::get_plugin_directory('mod', 'uckkassembly') !== null) {
    $uckknavigation[] = [
        'key' => 'assemblies',
        'label' => get_string('nav_assemblies', 'theme_uckk'),
        'url' => new moodle_url('/mod/uckkassembly/index.php', ['id' => $COURSE->id]),
        'active' => false,
    ];
}

if (core_component::get_plugin_directory('mod', 'uckkarchive') !== null) {
    $uckknavigation[] = [
        'key' => 'archives',
        'label' => get_string('nav_archives', 'theme_uckk'),
        'url' => new moodle_url('/mod/uckkarchive/index.php', ['id' => $COURSE->id]),
        'active' => false,
    ];
}

if (core_component::get_plugin_directory('tool', 'uckkintegrity') !== null) {
    $uckknavigation[] = [
        'key' => 'integrity',
        'label' => get_string('nav_integrity', 'theme_uckk'),
        'url' => new moodle_url('/admin/tool/uckkintegrity/index.php', ['courseid' => $COURSE->id]),
        'active' => false,
    ];
}

foreach ($uckknavigation as $key => $item) {
    if ($item['url'] instanceof moodle_url) {
        $uckknavigation[$key]['url'] = $item['url']->out(false);
    }
}

// -----------------------------------------------------------------------------
// Optional notices.
// -----------------------------------------------------------------------------

$showaccreditationnotice = true;
$showintegritynotice = true;
$showainotice = false;

if (function_exists('get_config')) {
    $showaccreditationnotice = (bool)get_config('theme_uckk', 'showaccreditationnotice');
    $showintegritynotice = (bool)get_config('theme_uckk', 'showintegritynotice');
}

$uckknotices = [
    'showaccreditationnotice' => $showaccreditationnotice,
    'accreditationnotice' => get_string('uckknotaccredited', 'theme_uckk'),
    'showintegritynotice' => $showintegritynotice,
    'integritynotice' => get_string('integrity_notice', 'theme_uckk'),
    'showainotice' => $showainotice,
    'ainotice' => get_string('ai_warning', 'theme_uckk'),
];

// -----------------------------------------------------------------------------
// Template context.
// -----------------------------------------------------------------------------

$templatecontext = [
    // Standard Boost / Moodle context.
    'sitename' => $sitename,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen ? 'open' : '',
    'blockdraweropen' => $blockdraweropen ? 'open' : '',
    'forceblockdraweropen' => $forceblockdraweropen,
    'addblockbutton' => $addblockbutton,

    // UCKK identity.
    'uckk' => [
        'name' => get_string('uckk', 'theme_uckk'),
        'fullname' => get_string('uckkfullname', 'theme_uckk'),
        'campus' => get_string('uckkcampus', 'theme_uckk'),
        'tagline' => get_string('uckktagline', 'theme_uckk'),
        'formula' => get_string('formula_governance', 'theme_uckk'),
        'formula_short' => get_string('formula_short', 'theme_uckk'),
        'formula_ai' => get_string('formula_ai', 'theme_uckk'),
        'boundarynotice' => get_string('footer_canonicalwarning', 'theme_uckk'),
    ],

    // UCKK course context.
    'uckkcourse' => [
        'isuckkcourse' => $isuckkcourse,
        'istronccommun' => $isuckktronccommun,
        'isprogram' => $isuckkprogram,
        'kind' => $uckkcoursekind,
        'fullname' => $coursename,
        'shortname' => $courseshortname,
        'idnumber' => $courseidnumber,
        'format' => $courseformat,
    ],

    // UCKK visual assets. Moodle pix_url is used without file extension.
    'uckkicons' => [
        'archive' => $uckkarchiveicon,
        'challenge' => $uckkchallengeicon,
        'assembly' => $uckkassemblyicon,
        'integrity' => $uckkintegrityicon,
    ],

    // UCKK navigation and notices.
    'uckknavigation' => $uckknavigation,
    'hasuckknavigation' => !empty($uckknavigation),
    'uckknotices' => $uckknotices,
];

// -----------------------------------------------------------------------------
// Render.
// -----------------------------------------------------------------------------
//
// The UCKK theme keeps Boost's drawers layout for compatibility. If a dedicated
// theme_uckk/course template is introduced later, this layout can switch to it
// while preserving the same $templatecontext keys.

echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);