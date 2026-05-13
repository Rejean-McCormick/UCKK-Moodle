<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

use report_uckk\local\exporter;
use report_uckk\local\filters;
use report_uckk\local\report_source;
use report_uckk\output\report_dashboard;

require_login();

$context = context_system::instance();
require_capability('report/uckk:view', $context);

$sources = report_source::all();
$filters = filters::from_request();

if (!array_key_exists($filters->report, $sources)) {
    $filters = $filters->with_report(report_source::DEFAULT_REPORT);
}

$current = $sources[$filters->report];

// If the selected report requires stronger permissions, fall back to the first
// visible source instead of exposing restricted institutional data.
if (!$current->can_view($context)) {
    $fallback = null;

    foreach ($sources as $source) {
        if ($source->can_view($context)) {
            $fallback = $source;
            break;
        }
    }

    if ($fallback === null) {
        throw new required_capability_exception($context, 'report/uckk:view', 'nopermissions', '');
    }

    $current = $fallback;
    $filters = $filters->with_report($current->get_key());
}

$pageurl = new moodle_url('/report/uckk/index.php', $filters->url_params());

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_uckk'));
$PAGE->set_heading(get_string('pluginname', 'report_uckk'));
$PAGE->navbar->add(get_string('pluginname', 'report_uckk'), $pageurl);

$PAGE->requires->js_call_amd('report_uckk/report', 'init');
$PAGE->requires->js_call_amd('report_uckk/filters', 'init');

if ($filters->format !== filters::FORMAT_HTML) {
    require_sesskey();
    require_capability('report/uckk:export', $context);

    $exporter = new exporter($current, $filters, $context);
    $exporter->send();
}

$renderer = $PAGE->get_renderer('report_uckk');
$dashboard = new report_dashboard($sources, $current, $filters, $context);

echo $OUTPUT->header();
echo $renderer->render($dashboard);
echo $OUTPUT->footer();