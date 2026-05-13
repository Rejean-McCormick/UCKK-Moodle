<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main view page for a UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use mod_uckkassembly\event\assembly_viewed;
use mod_uckkassembly\local\assembly_service;
use mod_uckkassembly\output\assembly_view;

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Assembly instance id.
$tab = optional_param('tab', 'overview', PARAM_ALPHAEXT);

if ($id) {
    $cm = get_coursemodule_from_id('uckkassembly', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $assembly = $DB->get_record('uckkassembly', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $assembly->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkassembly', $assembly->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkassembly:view', $context);

$allowedtabs = [
    'overview',
    'motions',
    'amendments',
    'votes',
    'decisions',
    'minutes',
    'contestations',
    'archive',
];

if (!in_array($tab, $allowedtabs, true)) {
    $tab = 'overview';
}

$url = new moodle_url('/mod/uckkassembly/view.php', [
    'id' => $cm->id,
    'tab' => $tab,
]);

$PAGE->set_url($url);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($assembly->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_attrs([
    'description' => '',
    'hidecompletion' => false,
]);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$event = assembly_viewed::create([
    'objectid' => $assembly->id,
    'context' => $context,
    'other' => [
        'courseid' => $course->id,
        'cmid' => $cm->id,
        'assemblytype' => $assembly->assemblytype ?? '',
        'status' => $assembly->status ?? '',
        'visibility' => $assembly->visibility ?? '',
    ],
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('uckkassembly', $assembly);
$event->trigger();

$service = new assembly_service();

$canproposemotion = has_capability('mod/uckkassembly:proposemotion', $context);
$canamendmotion = has_capability('mod/uckkassembly:amendmotion', $context);
$canvote = has_capability('mod/uckkassembly:vote', $context);
$canpublishdecision = has_capability('mod/uckkassembly:publishdecision', $context);
$cancontestdecision = has_capability('mod/uckkassembly:contestdecision', $context);
$canarchive = has_capability('mod/uckkassembly:archive', $context);
$cancreateassembly = has_capability('mod/uckkassembly:createassembly', $context);

$viewdata = $service->get_view_data(
    $assembly,
    $cm,
    $course,
    $context,
    $USER,
    [
        'tab' => $tab,
        'canproposemotion' => $canproposemotion,
        'canamendmotion' => $canamendmotion,
        'canvote' => $canvote,
        'canpublishdecision' => $canpublishdecision,
        'cancontestdecision' => $cancontestdecision,
        'canarchive' => $canarchive,
        'cancreateassembly' => $cancreateassembly,
    ]
);

$actionurls = [
    'proposemotionurl' => new moodle_url('/mod/uckkassembly/motion.php', [
        'id' => $cm->id,
        'action' => 'create',
    ]),
    'voteurl' => new moodle_url('/mod/uckkassembly/vote.php', [
        'id' => $cm->id,
    ]),
    'decisionurl' => new moodle_url('/mod/uckkassembly/decision.php', [
        'id' => $cm->id,
    ]),
    'contesturl' => new moodle_url('/mod/uckkassembly/contest.php', [
        'id' => $cm->id,
    ]),
    'minutesurl' => new moodle_url('/mod/uckkassembly/minutes.php', [
        'id' => $cm->id,
    ]),
    'archiveurl' => new moodle_url('/mod/uckkassembly/archive.php', [
        'id' => $cm->id,
    ]),
];

$tabs = [];
foreach ($allowedtabs as $tabkey) {
    $tabs[] = [
        'key' => $tabkey,
        'label' => get_string('tab:' . $tabkey, 'uckkassembly'),
        'url' => (new moodle_url('/mod/uckkassembly/view.php', [
            'id' => $cm->id,
            'tab' => $tabkey,
        ]))->out(false),
        'active' => $tabkey === $tab,
    ];
}

$renderable = new assembly_view(
    $assembly,
    $cm,
    $course,
    $context,
    $viewdata,
    [
        'active_tab' => $tab,
        'tabs' => $tabs,
        'actions' => [
            'canproposemotion' => $canproposemotion,
            'canamendmotion' => $canamendmotion,
            'canvote' => $canvote,
            'canpublishdecision' => $canpublishdecision,
            'cancontestdecision' => $cancontestdecision,
            'canarchive' => $canarchive,
            'cancreateassembly' => $cancreateassembly,
        ],
        'urls' => $actionurls,
    ]
);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template(
    'mod_uckkassembly/assembly_view',
    $renderable->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();