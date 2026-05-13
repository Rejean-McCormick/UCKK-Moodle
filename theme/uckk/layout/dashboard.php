<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Dashboard layout for the UCKK theme.
 *
 * This layout prepares the Moodle dashboard page for the Univers-Cité King
 * Klown visual language while keeping institutional logic out of the theme.
 *
 * The dashboard layout may expose UCKK navigation labels and visual regions,
 * but must not implement:
 *
 * - grading logic;
 * - pathway calculations;
 * - badge awarding;
 * - challenge workflow;
 * - assembly workflow;
 * - archive validation;
 * - integrity decisions;
 * - AI authority.
 *
 * Those responsibilities belong to the appropriate Moodle plugins:
 *
 * - local_uckk;
 * - block_uckk_dashboard;
 * - mod_uckkchallenge;
 * - mod_uckkassembly;
 * - mod_uckkarchive;
 * - tool_uckkintegrity;
 * - report_uckk;
 * - aiprovider_uckk.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $OUTPUT, $SITE, $USER;

require_once($CFG->libdir . '/behat/lib.php');

$context = context_course::instance(SITEID);

$bodyclasses = [
    'theme-uckk',
    'theme-uckk-dashboard',
    'uckk-campus-dashboard',
];

$bodyattributes = $OUTPUT->body_attributes($bodyclasses);

$sidepreblocks = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($sidepreblocks, 'data-block=') !== false;

$sitefullname = format_string(
    $SITE->fullname,
    true,
    [
        'context' => $context,
        'escape' => false,
    ]
);

$sitename = format_string(
    $SITE->shortname,
    true,
    [
        'context' => $context,
        'escape' => false,
    ]
);

$fullname = fullname($USER);
$dashboardtitle = get_string('dashboard', 'theme_uckk');
$dashboardtagline = get_string('dashboardtagline', 'theme_uckk');
$dashboardboundary = get_string('dashboardboundary', 'theme_uckk');

$mainnavitems = [
    [
        'key' => 'mycampus',
        'label' => get_string('navmycampus', 'theme_uckk'),
        'url' => (new moodle_url('/my/'))->out(false),
        'classes' => 'uckk-navitem uckk-navitem-campus',
    ],
    [
        'key' => 'courses',
        'label' => get_string('navcourses', 'theme_uckk'),
        'url' => (new moodle_url('/course/index.php'))->out(false),
        'classes' => 'uckk-navitem uckk-navitem-courses',
    ],
    [
        'key' => 'badges',
        'label' => get_string('navbadges', 'theme_uckk'),
        'url' => (new moodle_url('/badges/mybadges.php'))->out(false),
        'classes' => 'uckk-navitem uckk-navitem-badges',
    ],
    [
        'key' => 'grades',
        'label' => get_string('navgrades', 'theme_uckk'),
        'url' => (new moodle_url('/grade/report/overview/index.php'))->out(false),
        'classes' => 'uckk-navitem uckk-navitem-grades',
    ],
    [
        'key' => 'files',
        'label' => get_string('navfiles', 'theme_uckk'),
        'url' => (new moodle_url('/user/files.php'))->out(false),
        'classes' => 'uckk-navitem uckk-navitem-files',
    ],
];

$uckkquicklinks = [
    [
        'key' => 'pathway',
        'label' => get_string('quickpathway', 'theme_uckk'),
        'summary' => get_string('quickpathwaysummary', 'theme_uckk'),
        'url' => (new moodle_url('/local/uckk/pathways.php'))->out(false),
        'classes' => 'uckk-quicklink uckk-quicklink-pathway',
    ],
    [
        'key' => 'challenges',
        'label' => get_string('quickchallenges', 'theme_uckk'),
        'summary' => get_string('quickchallengessummary', 'theme_uckk'),
        'url' => (new moodle_url('/mod/uckkchallenge/index.php', ['id' => SITEID]))->out(false),
        'classes' => 'uckk-quicklink uckk-quicklink-challenges',
    ],
    [
        'key' => 'assemblies',
        'label' => get_string('quickassemblies', 'theme_uckk'),
        'summary' => get_string('quickassembliessummary', 'theme_uckk'),
        'url' => (new moodle_url('/mod/uckkassembly/index.php', ['id' => SITEID]))->out(false),
        'classes' => 'uckk-quicklink uckk-quicklink-assemblies',
    ],
    [
        'key' => 'archives',
        'label' => get_string('quickarchives', 'theme_uckk'),
        'summary' => get_string('quickarchivessummary', 'theme_uckk'),
        'url' => (new moodle_url('/mod/uckkarchive/index.php', ['id' => SITEID]))->out(false),
        'classes' => 'uckk-quicklink uckk-quicklink-archives',
    ],
    [
        'key' => 'integrity',
        'label' => get_string('quickintegrity', 'theme_uckk'),
        'summary' => get_string('quickintegritysummary', 'theme_uckk'),
        'url' => (new moodle_url('/admin/tool/uckkintegrity/index.php'))->out(false),
        'classes' => 'uckk-quicklink uckk-quicklink-integrity',
    ],
];

$uckkprinciples = [
    [
        'key' => 'understand',
        'label' => get_string('principleunderstand', 'theme_uckk'),
        'text' => get_string('principleunderstandtext', 'theme_uckk'),
    ],
    [
        'key' => 'play',
        'label' => get_string('principleplay', 'theme_uckk'),
        'text' => get_string('principleplaytext', 'theme_uckk'),
    ],
    [
        'key' => 'change',
        'label' => get_string('principlechange', 'theme_uckk'),
        'text' => get_string('principlechangetext', 'theme_uckk'),
    ],
];

$templatecontext = [
    'sitename' => $sitename,
    'sitefullname' => $sitefullname,
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,

    'hasblocks' => $hasblocks,
    'sidepreblocks' => $sidepreblocks,

    'dashboardtitle' => $dashboardtitle,
    'dashboardtagline' => $dashboardtagline,
    'dashboardboundary' => $dashboardboundary,
    'userfullname' => $fullname,

    'mainnavitems' => $mainnavitems,
    'uckkquicklinks' => $uckkquicklinks,
    'uckkprinciples' => $uckkprinciples,

    'hasquicklinks' => !empty($uckkquicklinks),
    'hasprinciples' => !empty($uckkprinciples),

    'isloggedin' => isloggedin() && !isguestuser(),
    'isguestuser' => isguestuser(),

    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->render_from_template('theme_uckk/dashboard', $templatecontext);