<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../../config.php');

use tool_uckkintegrity\form\case_form;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_policy;
use tool_uckkintegrity\output\case_view;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', $id ? 'view' : 'create', PARAM_ALPHA);
$contextid = optional_param('contextid', context_system::instance()->id, PARAM_INT);

if (!get_config('tool_uckkintegrity', 'enabled')) {
    throw new moodle_exception('pluginisdisabled', 'tool_uckkintegrity');
}

if ($id) {
    $case = integrity_case::get($id);
    $context = context::instance_by_id($case->contextid, MUST_EXIST);
    integrity_policy::require_can_view_case($case);
} else {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    require_capability('tool/uckkintegrity:opencase', $context);
    $case = null;
}

$urlparams = [
    'action' => $action,
];

if ($id) {
    $urlparams['id'] = $id;
} else {
    $urlparams['contextid'] = $context->id;
}

$url = new moodle_url('/admin/tool/uckkintegrity/case.php', $urlparams);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('case:view', 'tool_uckkintegrity') : get_string('opencase', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('pluginname', 'tool_uckkintegrity'));

$PAGE->navbar->add(
    get_string('cases', 'tool_uckkintegrity'),
    new moodle_url('/admin/tool/uckkintegrity/index.php')
);

if ($id) {
    $PAGE->navbar->add(get_string('case', 'tool_uckkintegrity') . ' #' . $id, $url);
} else {
    $PAGE->navbar->add(get_string('opencase', 'tool_uckkintegrity'), $url);
}

if ($action === 'create') {
    require_capability('tool/uckkintegrity:opencase', $context);

    $form = new case_form($url, [
        'context' => $context,
    ]);

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/admin/tool/uckkintegrity/index.php'));
    }

    if ($data = $form->get_data()) {
        $data->contextid = $context->id;

        $caseid = integrity_case::create($data);

        redirect(
            new moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $caseid]),
            get_string('caseopened', 'tool_uckkintegrity'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('opencase', 'tool_uckkintegrity'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

if (!$id) {
    redirect(new moodle_url('/admin/tool/uckkintegrity/case.php', [
        'action' => 'create',
        'contextid' => $context->id,
    ]));
}

$output = $PAGE->get_renderer('tool_uckkintegrity');

echo $output->header();
echo $output->render(new case_view($case));
echo $output->footer();