<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../../config.php');

use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\output\case_list;

require_login();

$context = context_system::instance();
require_capability('tool/uckkintegrity:view', $context);

$status = optional_param('status', '', PARAM_ALPHAEXT);
$severity = optional_param('severity', '', PARAM_ALPHAEXT);
$casetype = optional_param('casetype', '', PARAM_ALPHANUMEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$url = new moodle_url('/admin/tool/uckkintegrity/index.php', [
    'status' => $status,
    'severity' => $severity,
    'casetype' => $casetype,
]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('cases', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('pluginname', 'tool_uckkintegrity'));

$filters = array_filter([
    'status' => $status,
    'severity' => $severity,
    'casetype' => $casetype,
]);

$cases = integrity_case::get_cases($filters, $page, $perpage);
$total = integrity_case::count_cases($filters);

$output = $PAGE->get_renderer('tool_uckkintegrity');

echo $output->header();
echo $output->heading(get_string('cases', 'tool_uckkintegrity'));

echo html_writer::div(
    html_writer::link(new moodle_url('/admin/tool/uckkintegrity/case.php', ['action' => 'create']),
        get_string('opencase', 'tool_uckkintegrity'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/admin/tool/uckkintegrity/report.php'),
        get_string('report', 'tool_uckkintegrity'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

echo $output->render(new case_list($cases, $filters, $total, $page, $perpage));
echo $output->paging_bar($total, $page, $perpage, $url);

echo $output->footer();