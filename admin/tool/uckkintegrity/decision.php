<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Record an Inquisiteur decision for a UCKK integrity case.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use tool_uckkintegrity\form\decision_form;
use tool_uckkintegrity\local\decision;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_policy;

require_login();

$id = required_param('id', PARAM_INT);

$case = integrity_case::get($id);
$context = context::instance_by_id($case->contextid, MUST_EXIST);

integrity_policy::require_can_view_case($case);
require_capability('tool/uckkintegrity:closecase', $context);

$url = new moodle_url('/admin/tool/uckkintegrity/decision.php', ['id' => $id]);
$returnurl = new moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('decision', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('pluginname', 'tool_uckkintegrity'));

$form = new decision_form($url, ['case' => $case]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    require_sesskey();

    if (!empty($data->correction)) {
        require_capability('tool/uckkintegrity:issuecorrection', $context);
    }

    if (!empty($data->invalidateitem)) {
        require_capability('tool/uckkintegrity:invalidate', $context);
    }

    decision::record($case, $data);

    redirect(
        $returnurl,
        get_string('decisionrecorded', 'tool_uckkintegrity'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('decision', 'tool_uckkintegrity'));

echo html_writer::div(
    html_writer::link(
        $returnurl,
        get_string('case:view', 'tool_uckkintegrity'),
        ['class' => 'btn btn-secondary mb-3']
    ),
    'tool-uckkintegrity-navigation'
);

$form->display();

echo $OUTPUT->footer();