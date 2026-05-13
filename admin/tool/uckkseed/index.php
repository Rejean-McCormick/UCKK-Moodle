<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Web controller for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use tool_uckkseed\form\reset_form;
use tool_uckkseed\form\seed_form;
use tool_uckkseed\local\seeder;
use tool_uckkseed\local\validation_result;
use tool_uckkseed\output\seed_summary;
use tool_uckkseed\output\validation_report;

/**
 * Tool component.
 */
const TOOL_UCKKSEED_COMPONENT = 'tool_uckkseed';

/**
 * Canonical actions.
 */
const TOOL_UCKKSEED_ACTION_DASHBOARD = 'dashboard';
const TOOL_UCKKSEED_ACTION_SEED = 'seed';
const TOOL_UCKKSEED_ACTION_RESET = 'reset';
const TOOL_UCKKSEED_ACTION_VALIDATE = 'validate';
const TOOL_UCKKSEED_ACTION_EXPORT_PRESET = 'export_preset';

/**
 * Canonical modes.
 */
const TOOL_UCKKSEED_MODE_APPLY = 'apply';
const TOOL_UCKKSEED_MODE_DRY_RUN = 'dry_run';
const TOOL_UCKKSEED_MODE_REPORT = 'report';
const TOOL_UCKKSEED_MODE_ROLLBACK_PLAN = 'rollback_plan';

/**
 * Return allowed web actions.
 *
 * @return string[]
 */
function tool_uckkseed_allowed_actions(): array {
    return [
        TOOL_UCKKSEED_ACTION_DASHBOARD,
        TOOL_UCKKSEED_ACTION_SEED,
        TOOL_UCKKSEED_ACTION_RESET,
        TOOL_UCKKSEED_ACTION_VALIDATE,
        TOOL_UCKKSEED_ACTION_EXPORT_PRESET,
    ];
}

/**
 * Return allowed seeder modes.
 *
 * @return string[]
 */
function tool_uckkseed_allowed_modes(): array {
    return [
        TOOL_UCKKSEED_MODE_APPLY,
        TOOL_UCKKSEED_MODE_DRY_RUN,
        TOOL_UCKKSEED_MODE_REPORT,
        TOOL_UCKKSEED_MODE_ROLLBACK_PLAN,
    ];
}

/**
 * Normalise requested action.
 *
 * @param string $action Raw action.
 * @return string
 */
function tool_uckkseed_normalise_action(string $action): string {
    $action = clean_param($action, PARAM_ALPHAEXT);

    return in_array($action, tool_uckkseed_allowed_actions(), true)
        ? $action
        : TOOL_UCKKSEED_ACTION_DASHBOARD;
}

/**
 * Normalise requested mode.
 *
 * @param string $mode Raw mode.
 * @return string
 */
function tool_uckkseed_normalise_mode(string $mode): string {
    $mode = clean_param($mode, PARAM_ALPHAEXT);

    return in_array($mode, tool_uckkseed_allowed_modes(), true)
        ? $mode
        : TOOL_UCKKSEED_MODE_DRY_RUN;
}

/**
 * Return capability required for one action.
 *
 * @param string $action Action.
 * @return string
 */
function tool_uckkseed_get_action_capability(string $action): string {
    return match ($action) {
        TOOL_UCKKSEED_ACTION_SEED => 'tool/uckkseed:seed',
        TOOL_UCKKSEED_ACTION_RESET => 'tool/uckkseed:reset',
        TOOL_UCKKSEED_ACTION_VALIDATE => 'tool/uckkseed:validate',
        TOOL_UCKKSEED_ACTION_EXPORT_PRESET => 'tool/uckkseed:exportpresets',
        default => 'tool/uckkseed:validate',
    };
}

/**
 * Require permission for one action.
 *
 * @param string $action Action.
 * @param context_system $context System context.
 */
function tool_uckkseed_require_action_capability(string $action, context_system $context): void {
    require_capability(tool_uckkseed_get_action_capability($action), $context);
}

/**
 * Return whether the current user can perform any seed-tool action.
 *
 * @param context_system $context System context.
 * @return bool
 */
function tool_uckkseed_can_access(context_system $context): bool {
    return has_any_capability([
        'tool/uckkseed:seed',
        'tool/uckkseed:reset',
        'tool/uckkseed:validate',
        'tool/uckkseed:exportpresets',
    ], $context);
}

/**
 * Convert a Moodle form data object to canonical seeder options.
 *
 * @param stdClass $data Submitted data.
 * @param string $action Canonical action.
 * @return array<string, mixed>
 */
function tool_uckkseed_options_from_form(stdClass $data, string $action): array {
    global $USER;

    $mode = tool_uckkseed_normalise_mode((string)($data->mode ?? TOOL_UCKKSEED_MODE_DRY_RUN));

    if (!empty($data->dryrun)) {
        $mode = TOOL_UCKKSEED_MODE_DRY_RUN;
    } else if (!empty($data->report)) {
        $mode = TOOL_UCKKSEED_MODE_REPORT;
    } else if (!empty($data->rollbackplan)) {
        $mode = TOOL_UCKKSEED_MODE_ROLLBACK_PLAN;
    }

    return [
        'source' => 'web',
        'userid' => (int)$USER->id,
        'action' => $action,
        'mode' => $mode,
        'presets' => tool_uckkseed_clean_list($data->presets ?? []),
        'components' => tool_uckkseed_clean_list($data->components ?? []),
        'preset' => clean_param((string)($data->preset ?? ''), PARAM_ALPHANUMEXT),
        'component' => clean_param((string)($data->component ?? ''), PARAM_COMPONENT),
        'target' => clean_param((string)($data->target ?? ''), PARAM_ALPHANUMEXT),
        'scope' => clean_param((string)($data->scope ?? ''), PARAM_ALPHANUMEXT),
        'force' => !empty($data->force),
        'confirm' => !empty($data->confirm),
        'returnurl' => clean_param((string)($data->returnurl ?? ''), PARAM_LOCALURL),
    ];
}

/**
 * Build canonical seeder options from request params.
 *
 * @param string $action Canonical action.
 * @return array<string, mixed>
 */
function tool_uckkseed_options_from_request(string $action): array {
    global $USER;

    $mode = tool_uckkseed_normalise_mode(optional_param('mode', TOOL_UCKKSEED_MODE_REPORT, PARAM_ALPHAEXT));

    if (optional_param('dryrun', 0, PARAM_BOOL)) {
        $mode = TOOL_UCKKSEED_MODE_DRY_RUN;
    } else if (optional_param('rollbackplan', 0, PARAM_BOOL)) {
        $mode = TOOL_UCKKSEED_MODE_ROLLBACK_PLAN;
    } else if (optional_param('report', 0, PARAM_BOOL)) {
        $mode = TOOL_UCKKSEED_MODE_REPORT;
    }

    return [
        'source' => 'web',
        'userid' => (int)$USER->id,
        'action' => $action,
        'mode' => $mode,
        'presets' => tool_uckkseed_clean_list(optional_param_array('presets', [], PARAM_ALPHANUMEXT)),
        'components' => tool_uckkseed_clean_list(optional_param_array('components', [], PARAM_COMPONENT)),
        'preset' => optional_param('preset', '', PARAM_ALPHANUMEXT),
        'component' => optional_param('component', '', PARAM_COMPONENT),
        'target' => optional_param('target', '', PARAM_ALPHANUMEXT),
        'scope' => optional_param('scope', '', PARAM_ALPHANUMEXT),
        'force' => optional_param('force', 0, PARAM_BOOL),
        'confirm' => optional_param('confirm', 0, PARAM_BOOL),
        'json' => optional_param('json', 0, PARAM_BOOL),
        'returnurl' => optional_param('returnurl', '', PARAM_LOCALURL),
    ];
}

/**
 * Clean a submitted list.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function tool_uckkseed_clean_list(mixed $value): array {
    if (!is_array($value)) {
        $value = [$value];
    }

    $cleaned = [];

    foreach ($value as $item) {
        $item = clean_param((string)$item, PARAM_ALPHANUMEXT);

        if ($item !== '') {
            $cleaned[] = $item;
        }
    }

    return array_values(array_unique($cleaned));
}

/**
 * Guard destructive reset requests.
 *
 * @param array<string, mixed> $options Seeder options.
 */
function tool_uckkseed_require_reset_confirmation(array $options): void {
    if (
        ($options['action'] ?? '') === TOOL_UCKKSEED_ACTION_RESET
        && ($options['mode'] ?? '') === TOOL_UCKKSEED_MODE_APPLY
        && empty($options['confirm'])
    ) {
        throw new moodle_exception('confirmationrequired', TOOL_UCKKSEED_COMPONENT);
    }
}

/**
 * Render tab navigation.
 *
 * @param moodle_url $baseurl Base URL.
 * @param string $activeaction Active action.
 * @param context_system $context System context.
 * @return string
 */
function tool_uckkseed_render_tabs(moodle_url $baseurl, string $activeaction, context_system $context): string {
    $tabs = [
        TOOL_UCKKSEED_ACTION_DASHBOARD => [
            'label' => get_string('seedsummary', TOOL_UCKKSEED_COMPONENT),
            'capability' => null,
        ],
        TOOL_UCKKSEED_ACTION_SEED => [
            'label' => get_string('seeddistribution', TOOL_UCKKSEED_COMPONENT),
            'capability' => 'tool/uckkseed:seed',
        ],
        TOOL_UCKKSEED_ACTION_RESET => [
            'label' => get_string('resetdistribution', TOOL_UCKKSEED_COMPONENT),
            'capability' => 'tool/uckkseed:reset',
        ],
        TOOL_UCKKSEED_ACTION_VALIDATE => [
            'label' => get_string('validatedistribution', TOOL_UCKKSEED_COMPONENT),
            'capability' => 'tool/uckkseed:validate',
        ],
        TOOL_UCKKSEED_ACTION_EXPORT_PRESET => [
            'label' => get_string('exportpreset', TOOL_UCKKSEED_COMPONENT),
            'capability' => 'tool/uckkseed:exportpresets',
        ],
    ];

    $items = [];

    foreach ($tabs as $action => $tab) {
        if ($tab['capability'] !== null && !has_capability($tab['capability'], $context)) {
            continue;
        }

        $url = new moodle_url($baseurl, ['action' => $action]);
        $active = $action === $activeaction;

        $items[] = html_writer::tag(
            'li',
            html_writer::link($url, $tab['label'], [
                'class' => 'nav-link' . ($active ? ' active' : ''),
                'aria-current' => $active ? 'page' : null,
            ]),
            ['class' => 'nav-item']
        );
    }

    return html_writer::tag(
        'nav',
        html_writer::tag('ul', implode('', $items), ['class' => 'nav nav-tabs mb-3']),
        [
            'class' => 'tool-uckkseed-tabs',
            'aria-label' => get_string('pluginname', TOOL_UCKKSEED_COMPONENT),
        ]
    );
}

/**
 * Render a small POST action form.
 *
 * @param moodle_url $url Action URL.
 * @param string $action Action.
 * @param string $label Button label.
 * @param string $buttonclass Button class.
 * @param array<string, scalar> $hidden Hidden fields.
 * @return string
 */
function tool_uckkseed_render_post_button(
    moodle_url $url,
    string $action,
    string $label,
    string $buttonclass,
    array $hidden = []
): string {
    $output = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $url->out(false),
        'class' => 'tool-uckkseed-inline-form d-inline-block mr-2 mb-2',
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $action,
    ]);

    foreach ($hidden as $name => $value) {
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ]);
    }

    $output .= html_writer::tag('button', $label, [
        'type' => 'submit',
        'class' => $buttonclass,
    ]);

    $output .= html_writer::end_tag('form');

    return $output;
}

/**
 * Render dashboard action cards.
 *
 * @param moodle_url $pageurl Page URL.
 * @param context_system $context System context.
 * @return string
 */
function tool_uckkseed_render_action_overview(moodle_url $pageurl, context_system $context): string {
    $cards = [];

    if (has_capability('tool/uckkseed:seed', $context)) {
        $cards[] = [
            'title' => get_string('seeddistribution', TOOL_UCKKSEED_COMPONENT),
            'description' => get_string('seeddistribution_desc', TOOL_UCKKSEED_COMPONENT),
            'url' => new moodle_url($pageurl, ['action' => TOOL_UCKKSEED_ACTION_SEED]),
            'button' => get_string('open', 'moodle'),
            'class' => 'btn btn-primary',
        ];
    }

    if (has_capability('tool/uckkseed:validate', $context)) {
        $cards[] = [
            'title' => get_string('validatedistribution', TOOL_UCKKSEED_COMPONENT),
            'description' => get_string('validatedistribution_desc', TOOL_UCKKSEED_COMPONENT),
            'post' => tool_uckkseed_render_post_button(
                $pageurl,
                TOOL_UCKKSEED_ACTION_VALIDATE,
                get_string('runvalidation', TOOL_UCKKSEED_COMPONENT),
                'btn btn-primary',
                [
                    'mode' => TOOL_UCKKSEED_MODE_REPORT,
                    'confirm' => 1,
                ]
            ),
        ];
    }

    if (has_capability('tool/uckkseed:exportpresets', $context)) {
        $cards[] = [
            'title' => get_string('exportpreset', TOOL_UCKKSEED_COMPONENT),
            'description' => get_string('exportpreset_desc', TOOL_UCKKSEED_COMPONENT),
            'url' => new moodle_url($pageurl, ['action' => TOOL_UCKKSEED_ACTION_EXPORT_PRESET]),
            'button' => get_string('open', 'moodle'),
            'class' => 'btn btn-secondary',
        ];
    }

    if (has_capability('tool/uckkseed:reset', $context)) {
        $cards[] = [
            'title' => get_string('resetdistribution', TOOL_UCKKSEED_COMPONENT),
            'description' => get_string('resetdistribution_desc', TOOL_UCKKSEED_COMPONENT),
            'url' => new moodle_url($pageurl, ['action' => TOOL_UCKKSEED_ACTION_RESET]),
            'button' => get_string('open', 'moodle'),
            'class' => 'btn btn-warning',
        ];
    }

    $output = html_writer::start_div('tool-uckkseed-action-grid row');

    foreach ($cards as $card) {
        $output .= html_writer::start_div('col-md-6 col-lg-3 mb-3');
        $output .= html_writer::start_div('card h-100');
        $output .= html_writer::start_div('card-body d-flex flex-column');

        $output .= html_writer::tag('h3', $card['title'], ['class' => 'h5 card-title']);
        $output .= html_writer::tag('p', $card['description'], ['class' => 'card-text flex-grow-1']);

        if (!empty($card['post'])) {
            $output .= $card['post'];
        } else {
            $output .= html_writer::link($card['url'], $card['button'], [
                'class' => $card['class'],
            ]);
        }

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
    }

    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render recent runs from tool_uckkseed_run.
 *
 * @return string
 */
function tool_uckkseed_render_recent_runs(): string {
    global $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists('tool_uckkseed_run')) {
        return '';
    }

    $runs = $DB->get_records('tool_uckkseed_run', null, 'timecreated DESC', '*', 0, 10);

    if (!$runs) {
        return html_writer::div(
            get_string('norecentruns', TOOL_UCKKSEED_COMPONENT),
            'alert alert-info',
            ['role' => 'status']
        );
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable tool-uckkseed-runs';
    $table->head = [
        get_string('action', TOOL_UCKKSEED_COMPONENT),
        get_string('mode', TOOL_UCKKSEED_COMPONENT),
        get_string('status', 'moodle'),
        get_string('summary', TOOL_UCKKSEED_COMPONENT),
        get_string('timecreated', TOOL_UCKKSEED_COMPONENT),
    ];

    foreach ($runs as $run) {
        $status = s($run->status ?? '');
        $statusclass = 'badge badge-secondary status-' . clean_param((string)($run->status ?? ''), PARAM_ALPHANUMEXT);

        $table->data[] = [
            s($run->action ?? ''),
            s($run->mode ?? ''),
            html_writer::span($status, $statusclass),
            s($run->summary ?? ''),
            !empty($run->timecreated) ? userdate((int)$run->timecreated) : '',
        ];
    }

    return html_writer::tag('h3', get_string('recentruns', TOOL_UCKKSEED_COMPONENT), ['class' => 'h4 mt-4'])
        . html_writer::table($table);
}

/**
 * Render an export preset form.
 *
 * @param moodle_url $pageurl Page URL.
 * @return string
 */
function tool_uckkseed_render_export_preset_form(moodle_url $pageurl): string {
    $presetoptions = [
        'categories' => get_string('preset_categories', TOOL_UCKKSEED_COMPONENT),
        'courses' => get_string('preset_courses', TOOL_UCKKSEED_COMPONENT),
        'cohorts' => get_string('preset_cohorts', TOOL_UCKKSEED_COMPONENT),
        'roles' => get_string('preset_roles', TOOL_UCKKSEED_COMPONENT),
        'capabilities' => get_string('preset_capabilities', TOOL_UCKKSEED_COMPONENT),
        'competencies' => get_string('preset_competencies', TOOL_UCKKSEED_COMPONENT),
        'badges' => get_string('preset_badges', TOOL_UCKKSEED_COMPONENT),
        'reports' => get_string('preset_reports', TOOL_UCKKSEED_COMPONENT),
        'course_templates' => get_string('preset_course_templates', TOOL_UCKKSEED_COMPONENT),
        'challenge_templates' => get_string('preset_challenge_templates', TOOL_UCKKSEED_COMPONENT),
        'assembly_templates' => get_string('preset_assembly_templates', TOOL_UCKKSEED_COMPONENT),
        'archive_templates' => get_string('preset_archive_templates', TOOL_UCKKSEED_COMPONENT),
    ];

    $output = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'tool-uckkseed-export-form card card-body mb-3',
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => TOOL_UCKKSEED_ACTION_EXPORT_PRESET,
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'mode',
        'value' => TOOL_UCKKSEED_MODE_REPORT,
    ]);

    $output .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'confirm',
        'value' => 1,
    ]);

    $output .= html_writer::start_div('form-group');
    $output .= html_writer::label(get_string('preset', TOOL_UCKKSEED_COMPONENT), 'id_preset');
    $output .= html_writer::select($presetoptions, 'preset', 'categories', false, [
        'id' => 'id_preset',
        'class' => 'custom-select form-control',
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::tag('button', get_string('exportpreset', TOOL_UCKKSEED_COMPONENT), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]);

    $output .= html_writer::end_tag('form');

    return $output;
}

/**
 * Render a validation run form.
 *
 * @param moodle_url $pageurl Page URL.
 * @return string
 */
function tool_uckkseed_render_validation_form(moodle_url $pageurl): string {
    return html_writer::div(
        html_writer::tag('p', get_string('validatedistribution_desc', TOOL_UCKKSEED_COMPONENT))
        . tool_uckkseed_render_post_button(
            $pageurl,
            TOOL_UCKKSEED_ACTION_VALIDATE,
            get_string('runvalidation', TOOL_UCKKSEED_COMPONENT),
            'btn btn-primary',
            [
                'mode' => TOOL_UCKKSEED_MODE_REPORT,
                'confirm' => 1,
            ]
        ),
        'card card-body mb-3'
    );
}

/**
 * Render a validation result through the plugin renderer.
 *
 * @param validation_result $result Result object.
 * @param string $action Action.
 * @param array<string, mixed> $options Options.
 * @return string
 */
function tool_uckkseed_render_result(validation_result $result, string $action, array $options): string {
    global $PAGE;

    $renderer = $PAGE->get_renderer(TOOL_UCKKSEED_COMPONENT);

    if ($action === TOOL_UCKKSEED_ACTION_VALIDATE) {
        return $renderer->render(new validation_report($result, [
            'action' => $action,
            'mode' => $options['mode'] ?? TOOL_UCKKSEED_MODE_REPORT,
            'component' => TOOL_UCKKSEED_COMPONENT,
        ]));
    }

    return $renderer->render(new seed_summary($result, [
        'action' => $action,
        'mode' => $options['mode'] ?? TOOL_UCKKSEED_MODE_DRY_RUN,
        'component' => TOOL_UCKKSEED_COMPONENT,
    ]));
}

$context = context_system::instance();
$action = tool_uckkseed_normalise_action(optional_param('action', TOOL_UCKKSEED_ACTION_DASHBOARD, PARAM_ALPHAEXT));
$pageurl = new moodle_url('/admin/tool/uckkseed/index.php');
$currenturl = new moodle_url($pageurl, ['action' => $action]);
$ispost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

admin_externalpage_setup('tool_uckkseed');

$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', TOOL_UCKKSEED_COMPONENT));
$PAGE->set_heading(get_string('pluginname', TOOL_UCKKSEED_COMPONENT));

if (!tool_uckkseed_can_access($context)) {
    require_capability('tool/uckkseed:validate', $context);
}

if ($action !== TOOL_UCKKSEED_ACTION_DASHBOARD) {
    tool_uckkseed_require_action_capability($action, $context);
}

$enabletool = (bool)get_config(TOOL_UCKKSEED_COMPONENT, 'enabletool');

if ($ispost && $action !== TOOL_UCKKSEED_ACTION_VALIDATE) {
    require_sesskey();

    if (!$enabletool) {
        throw new moodle_exception('tooldisabled', TOOL_UCKKSEED_COMPONENT);
    }
}

$result = null;
$resultaction = '';
$resultoptions = [];

$seeder = new seeder();

$seedform = null;
$resetform = null;

if ($action === TOOL_UCKKSEED_ACTION_SEED) {
    $seedform = new seed_form($currenturl, [
        'context' => $context,
        'action' => TOOL_UCKKSEED_ACTION_SEED,
    ]);

    if ($seedform->is_cancelled()) {
        redirect($pageurl);
    }

    if ($data = $seedform->get_data()) {
        require_sesskey();

        $resultaction = TOOL_UCKKSEED_ACTION_SEED;
        $resultoptions = tool_uckkseed_options_from_form($data, $resultaction);
        $result = $seeder->seed($resultoptions);
    }
} else if ($action === TOOL_UCKKSEED_ACTION_RESET) {
    $resetform = new reset_form($currenturl, [
        'context' => $context,
        'action' => TOOL_UCKKSEED_ACTION_RESET,
    ]);

    if ($resetform->is_cancelled()) {
        redirect($pageurl);
    }

    if ($data = $resetform->get_data()) {
        require_sesskey();

        $resultaction = TOOL_UCKKSEED_ACTION_RESET;
        $resultoptions = tool_uckkseed_options_from_form($data, $resultaction);
        tool_uckkseed_require_reset_confirmation($resultoptions);
        $result = $seeder->reset($resultoptions);
    }
} else if ($ispost && $action === TOOL_UCKKSEED_ACTION_VALIDATE) {
    require_sesskey();

    $resultaction = TOOL_UCKKSEED_ACTION_VALIDATE;
    $resultoptions = tool_uckkseed_options_from_request($resultaction);
    $result = $seeder->validate($resultoptions);
} else if ($ispost && $action === TOOL_UCKKSEED_ACTION_EXPORT_PRESET) {
    require_sesskey();

    $resultaction = TOOL_UCKKSEED_ACTION_EXPORT_PRESET;
    $resultoptions = tool_uckkseed_options_from_request($resultaction);
    $result = $seeder->export_preset($resultoptions);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', TOOL_UCKKSEED_COMPONENT));

if (!$enabletool) {
    echo $OUTPUT->notification(get_string('tooldisablednotice', TOOL_UCKKSEED_COMPONENT), notification::NOTIFY_WARNING);
}

echo tool_uckkseed_render_tabs($pageurl, $action, $context);

if ($result instanceof validation_result) {
    echo tool_uckkseed_render_result($result, $resultaction, $resultoptions);
}

switch ($action) {
    case TOOL_UCKKSEED_ACTION_SEED:
        echo html_writer::tag('h3', get_string('seeddistribution', TOOL_UCKKSEED_COMPONENT), ['class' => 'h4']);
        echo html_writer::div(get_string('seeddistribution_desc', TOOL_UCKKSEED_COMPONENT), 'alert alert-info');

        if ($seedform !== null) {
            $seedform->display();
        }
        break;

    case TOOL_UCKKSEED_ACTION_RESET:
        echo html_writer::tag('h3', get_string('resetdistribution', TOOL_UCKKSEED_COMPONENT), ['class' => 'h4']);
        echo html_writer::div(get_string('resetdistribution_desc', TOOL_UCKKSEED_COMPONENT), 'alert alert-warning');

        if ($resetform !== null) {
            $resetform->display();
        }
        break;

    case TOOL_UCKKSEED_ACTION_VALIDATE:
        echo html_writer::tag('h3', get_string('validatedistribution', TOOL_UCKKSEED_COMPONENT), ['class' => 'h4']);
        echo tool_uckkseed_render_validation_form($pageurl);
        break;

    case TOOL_UCKKSEED_ACTION_EXPORT_PRESET:
        echo html_writer::tag('h3', get_string('exportpreset', TOOL_UCKKSEED_COMPONENT), ['class' => 'h4']);
        echo html_writer::div(get_string('exportpreset_desc', TOOL_UCKKSEED_COMPONENT), 'alert alert-info');
        echo tool_uckkseed_render_export_preset_form($pageurl);
        break;

    case TOOL_UCKKSEED_ACTION_DASHBOARD:
    default:
        echo html_writer::div(get_string('seedtoolintro', TOOL_UCKKSEED_COMPONENT), 'alert alert-info');
        echo tool_uckkseed_render_action_overview($pageurl, $context);
        echo tool_uckkseed_render_recent_runs();
        break;
}

echo $OUTPUT->footer();