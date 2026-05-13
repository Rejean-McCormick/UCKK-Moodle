<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../../config.php');

use tool_uckkintegrity\form\appeal_form;
use tool_uckkintegrity\local\appeal;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_policy;

require_login();

$id = required_param('id', PARAM_INT);

$case = integrity_case::get($id);
$context = context::instance_by_id($case->contextid, MUST_EXIST);

integrity_policy::require_can_view_case($case);

$url = new moodle_url('/admin/tool/uckkintegrity/appeal.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('appeal', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('pluginname', 'tool_uckkintegrity'));

$form = new appeal_form($url, [
    'case' => $case,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/uckkintegrity/case.php', [
        'id' => $id,
    ]));
}

if ($data = $form->get_data()) {
    appeal::create($case, $data);

    redirect(
        new moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $id]),
        get_string('appealrecorded', 'tool_uckkintegrity'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('appeal', 'tool_uckkintegrity'));

$form->display();

echo $OUTPUT->footer();