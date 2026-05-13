<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Course index page for UCKK assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/uckkassembly/lib.php');

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login($course);

$PAGE->set_url(new moodle_url('/mod/uckkassembly/index.php', ['id' => $course->id]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);
$PAGE->set_title(get_string('assembliesincourse', 'uckkassembly', format_string($course->fullname)));
$PAGE->set_heading(format_string($course->fullname));

$assemblies = get_all_instances_in_course('uckkassembly', $course);
$modinfo = get_fast_modinfo($course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'uckkassembly'));

if (empty($assemblies)) {
    echo $OUTPUT->notification(
        get_string('noassemblies', 'uckkassembly'),
        \core\output\notification::NOTIFY_INFO
    );
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $course->id]));
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod-uckkassembly-index';
$table->head = [
    get_string('assemblyname', 'uckkassembly'),
    get_string('assemblytype', 'uckkassembly'),
    get_string('status'),
    get_string('timeopen', 'uckkassembly'),
    get_string('timeclose', 'uckkassembly'),
    get_string('motions', 'uckkassembly'),
    get_string('decisions', 'uckkassembly'),
    get_string('archive', 'uckkassembly'),
];
$table->colclasses = [
    'mod-uckkassembly-index__name',
    'mod-uckkassembly-index__type',
    'mod-uckkassembly-index__status',
    'mod-uckkassembly-index__timeopen',
    'mod-uckkassembly-index__timeclose',
    'mod-uckkassembly-index__motions',
    'mod-uckkassembly-index__decisions',
    'mod-uckkassembly-index__archive',
];

foreach ($assemblies as $assembly) {
    if (empty($assembly->coursemodule)) {
        continue;
    }

    $cmid = (int) $assembly->coursemodule;

    if (empty($modinfo->cms[$cmid])) {
        continue;
    }

    $cm = $modinfo->cms[$cmid];

    if (!$cm->uservisible) {
        continue;
    }

    $modulecontext = context_module::instance($cmid);

    if (!has_capability('mod/uckkassembly:view', $modulecontext)) {
        continue;
    }

    $viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $cmid]);

    $name = html_writer::link(
        $viewurl,
        format_string($assembly->name, true, ['context' => $modulecontext])
    );

    if (!$cm->visible) {
        $name .= html_writer::span(
            get_string('hiddenfromstudents'),
            'badge badge-secondary ml-2'
        );
    }

    $type = !empty($assembly->assemblytype)
        ? get_string('assemblytype:' . $assembly->assemblytype, 'uckkassembly')
        : get_string('unknown', 'uckkassembly');

    $status = !empty($assembly->status)
        ? get_string('status:' . $assembly->status, 'uckkassembly')
        : get_string('status:active', 'uckkassembly');

    $timeopen = !empty($assembly->timeopen)
        ? userdate((int) $assembly->timeopen)
        : get_string('notset', 'uckkassembly');

    $timeclose = !empty($assembly->timeclose)
        ? userdate((int) $assembly->timeclose)
        : get_string('notset', 'uckkassembly');

    $motioncount = $DB->count_records('uckkassembly_motion', [
        'assemblyid' => $assembly->id,
    ]);

    $decisioncount = $DB->count_records('uckkassembly_decision', [
        'assemblyid' => $assembly->id,
    ]);

    $archivecell = '-';

    if (has_capability('mod/uckkassembly:archive', $modulecontext)) {
        $archiveurl = new moodle_url('/mod/uckkassembly/archive.php', ['id' => $cmid]);
        $archivecell = html_writer::link($archiveurl, get_string('archive', 'uckkassembly'));
    }

    $table->data[] = [
        $name,
        $type,
        $status,
        $timeopen,
        $timeclose,
        (string) $motioncount,
        (string) $decisioncount,
        $archivecell,
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification(
        get_string('noassembliesvisible', 'uckkassembly'),
        \core\output\notification::NOTIFY_INFO
    );
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();