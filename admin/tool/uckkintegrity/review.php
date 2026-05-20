<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../../config.php');

use tool_uckkintegrity\form\review_form;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_review;

require_login();

$id = required_param('id', PARAM_INT);

$case = integrity_case::get($id);
$context = context::instance_by_id($case->contextid, MUST_EXIST);

require_capability('tool/uckkintegrity:reviewcase', $context);

$url = new moodle_url('/admin/tool/uckkintegrity/review.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('reviewcase', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('pluginname', 'tool_uckkintegrity'));

$form = new review_form($url, [
    'case' => $case,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/uckkintegrity/case.php', [
        'id' => $id,
    ]));
}

if ($data = $form->get_data()) {
    integrity_review::review($case, $data);

    redirect(
        new moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $id]),
        get_string('reviewrecorded', 'tool_uckkintegrity'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reviewcase', 'tool_uckkintegrity'));
$form->display();
echo $OUTPUT->footer();