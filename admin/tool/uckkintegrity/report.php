<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\severity;

admin_externalpage_setup('tool_uckkintegrity_report');

$context = context_system::instance();
require_capability('tool/uckkintegrity:view', $context);

$status = optional_param('status', '', PARAM_ALPHAEXT);
$casetype = optional_param('casetype', '', PARAM_ALPHAEXT);
$severityfilter = optional_param('severity', '', PARAM_ALPHAEXT);
$from = optional_param('from', 0, PARAM_INT);
$to = optional_param('to', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$url = new moodle_url('/admin/tool/uckkintegrity/report.php');
if ($status !== '') {
    $url->param('status', $status);
}
if ($casetype !== '') {
    $url->param('casetype', $casetype);
}
if ($severityfilter !== '') {
    $url->param('severity', $severityfilter);
}
if ($from > 0) {
    $url->param('from', $from);
}
if ($to > 0) {
    $url->param('to', $to);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('report', 'tool_uckkintegrity'));
$PAGE->set_heading(get_string('report', 'tool_uckkintegrity'));

$conditions = [];
$params = [];

if ($status !== '') {
    $conditions[] = 'status = :status';
    $params['status'] = $status;
}
if ($casetype !== '') {
    $conditions[] = 'casetype = :casetype';
    $params['casetype'] = $casetype;
}
if ($severityfilter !== '') {
    $conditions[] = 'severity = :severity';
    $params['severity'] = $severityfilter;
}
if ($from > 0) {
    $conditions[] = 'timecreated >= :fromtime';
    $params['fromtime'] = $from;
}
if ($to > 0) {
    $conditions[] = 'timecreated <= :totime';
    $params['totime'] = $to;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$casesql = "SELECT *
              FROM {tool_uckkintegrity_case}
              $where
          ORDER BY timecreated DESC";

$countsql = "SELECT COUNT(1)
               FROM {tool_uckkintegrity_case}
               $where";

$total = $DB->count_records_sql($countsql, $params);
$cases = $DB->get_records_sql($casesql, $params, 0, 100);

if ($download === 'csv') {
    require_capability('tool/uckkintegrity:viewrestricted', $context);

    $filename = 'uckk-integrity-report-' . userdate(time(), '%Y%m%d-%H%M%S') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'id',
        'casetype',
        'subjectcomponent',
        'subjectid',
        'openedby',
        'assignedto',
        'severity',
        'status',
        'summary',
        'timecreated',
        'timemodified',
    ]);

    foreach ($cases as $case) {
        fputcsv($out, [
            $case->id,
            $case->casetype,
            $case->subjectcomponent,
            $case->subjectid,
            $case->openedby,
            $case->assignedto,
            $case->severity,
            $case->status,
            format_string($case->summary),
            $case->timecreated,
            $case->timemodified,
        ]);
    }

    fclose($out);
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('report', 'tool_uckkintegrity'));

echo html_writer::start_tag('div', ['class' => 'tool-uckkintegrity-report']);

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $PAGE->url->out(false),
    'class' => 'mb-4',
]);

echo html_writer::start_tag('div', ['class' => 'row']);

echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::label(get_string('status', 'tool_uckkintegrity'), 'id_status');
$statusoptions = ['' => get_string('all')] + integrity_case::status_options();
echo html_writer::select($statusoptions, 'status', $status, false, ['id' => 'id_status', 'class' => 'form-control']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::label(get_string('casetype', 'tool_uckkintegrity'), 'id_casetype');
$typeoptions = ['' => get_string('all')] + integrity_case::type_options();
echo html_writer::select($typeoptions, 'casetype', $casetype, false, ['id' => 'id_casetype', 'class' => 'form-control']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-3']);
echo html_writer::label(get_string('severity', 'tool_uckkintegrity'), 'id_severity');
$severityoptions = ['' => get_string('all')] + severity::options();
echo html_writer::select($severityoptions, 'severity', $severityfilter, false, ['id' => 'id_severity', 'class' => 'form-control']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-3 d-flex align-items-end']);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('filter'),
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

echo html_writer::tag('p', get_string('total') . ': ' . $total);

$exporturl = new moodle_url($url, ['download' => 'csv']);
if (has_capability('tool/uckkintegrity:viewrestricted', $context)) {
    echo html_writer::link($exporturl, get_string('downloadtext'), ['class' => 'btn btn-secondary mb-3']);
}

$statuscounts = $DB->get_records_sql_menu(
    "SELECT status, COUNT(1)
       FROM {tool_uckkintegrity_case}
       $where
   GROUP BY status
   ORDER BY status",
    $params
);

$severitycounts = $DB->get_records_sql_menu(
    "SELECT severity, COUNT(1)
       FROM {tool_uckkintegrity_case}
       $where
   GROUP BY severity
   ORDER BY severity",
    $params
);

echo html_writer::start_tag('div', ['class' => 'row mb-4']);

echo html_writer::start_tag('div', ['class' => 'col-md-6']);
echo $OUTPUT->heading(get_string('statuscounts', 'tool_uckkintegrity'), 3);
echo html_writer::start_tag('ul');
foreach ($statuscounts as $key => $count) {
    $label = get_string_manager()->string_exists('status:' . $key, 'tool_uckkintegrity')
        ? get_string('status:' . $key, 'tool_uckkintegrity')
        : s($key);
    echo html_writer::tag('li', $label . ': ' . (int)$count);
}
echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'col-md-6']);
echo $OUTPUT->heading(get_string('severitycounts', 'tool_uckkintegrity'), 3);
echo html_writer::start_tag('ul');
foreach ($severitycounts as $key => $count) {
    $label = get_string_manager()->string_exists('severity:' . $key, 'tool_uckkintegrity')
        ? get_string('severity:' . $key, 'tool_uckkintegrity')
        : s($key);
    echo html_writer::tag('li', $label . ': ' . (int)$count);
}
echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

echo $OUTPUT->heading(get_string('recentcases', 'tool_uckkintegrity'), 3);

if (empty($cases)) {
    echo $OUTPUT->notification(get_string('noresults', 'tool_uckkintegrity'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('case', 'tool_uckkintegrity'),
        get_string('casetype', 'tool_uckkintegrity'),
        get_string('severity', 'tool_uckkintegrity'),
        get_string('status', 'tool_uckkintegrity'),
        get_string('openedby', 'tool_uckkintegrity'),
        get_string('created', 'tool_uckkintegrity'),
        get_string('actions', 'tool_uckkintegrity'),
    ];
    $table->attributes['class'] = 'generaltable tool-uckkintegrity-cases';

    foreach ($cases as $case) {
        $caseurl = new moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $case->id]);

        $typekey = 'type:' . $case->casetype;
        $type = get_string_manager()->string_exists($typekey, 'tool_uckkintegrity')
            ? get_string($typekey, 'tool_uckkintegrity')
            : s($case->casetype);

        $severitykey = 'severity:' . $case->severity;
        $severitylabel = get_string_manager()->string_exists($severitykey, 'tool_uckkintegrity')
            ? get_string($severitykey, 'tool_uckkintegrity')
            : s($case->severity);

        $statuskey = 'status:' . $case->status;
        $statuslabel = get_string_manager()->string_exists($statuskey, 'tool_uckkintegrity')
            ? get_string($statuskey, 'tool_uckkintegrity')
            : s($case->status);

        $userlink = '-';
        if (!empty($case->openedby)) {
            $user = $DB->get_record('user', ['id' => $case->openedby], 'id, firstname, lastname, email, deleted', IGNORE_MISSING);
            if ($user && !$user->deleted) {
                $userlink = html_writer::link(new moodle_url('/user/profile.php', ['id' => $user->id]), fullname($user));
            }
        }

        $table->data[] = [
            html_writer::link($caseurl, '#' . $case->id . ' ' . format_string($case->summary)),
            $type,
            $severitylabel,
            $statuslabel,
            $userlink,
            userdate($case->timecreated),
            html_writer::link($caseurl, get_string('viewcase', 'tool_uckkintegrity')),
        ];
    }

    echo html_writer::table($table);
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();