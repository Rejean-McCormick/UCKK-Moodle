<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin cache purge controller for UCKK faculty public pages.
 *
 * This controller is intentionally limited:
 *
 * - it never accepts file paths;
 * - it never loads raw JSON from request parameters;
 * - it resolves faculty slugs through the registry;
 * - it requires an admin capability;
 * - it requires sesskey + explicit confirmation before purge;
 * - it delegates purge operations to faculty_cache when available;
 * - it logs only scope, slug, counts and status.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $PAGE, $SITE;

use local_uckk\local\faculty\faculty_cache;
use local_uckk\local\faculty\faculty_registry;

const LOCAL_UCKK_FACULTY_CACHE_ACTION_PREVIEW = 'preview';
const LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE = 'purge';

const LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL = 'all';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_FACULTY = 'faculty';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_PAGE = 'page';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_PROFILE = 'profile';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_DYNAMIC_BLOCKS = 'dynamic_blocks';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS = 'manifests';
const LOCAL_UCKK_FACULTY_CACHE_SCOPE_SYNC_REPORT = 'sync_report';

/**
 * Validate cache action.
 *
 * @param string $action Raw action.
 * @return string Safe action.
 */
function local_uckk_faculty_cache_safe_action(string $action): string {
    $action = trim(\core_text::strtolower($action));

    $allowed = [
        LOCAL_UCKK_FACULTY_CACHE_ACTION_PREVIEW,
        LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE,
    ];

    if (!in_array($action, $allowed, true)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'action');
    }

    return $action;
}

/**
 * Validate purge scope.
 *
 * @param string $scope Raw scope.
 * @return string Safe scope.
 */
function local_uckk_faculty_cache_safe_scope(string $scope): string {
    $scope = trim(\core_text::strtolower($scope));

    $allowed = [
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_FACULTY,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_PAGE,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_PROFILE,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_DYNAMIC_BLOCKS,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS,
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_SYNC_REPORT,
    ];

    if (!in_array($scope, $allowed, true)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'scope');
    }

    return $scope;
}

/**
 * Validate faculty slug parameter.
 *
 * Only "all" or canonical slug format is accepted.
 * The slug is never treated as a filesystem path.
 *
 * @param string $faculty Raw faculty parameter.
 * @return string Safe faculty slug or all.
 */
function local_uckk_faculty_cache_safe_faculty(string $faculty): string {
    $faculty = trim(\core_text::strtolower($faculty));

    if ($faculty === '' || $faculty === 'all') {
        return 'all';
    }

    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $faculty)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'faculty');
    }

    if (!faculty_registry::exists_slug($faculty)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'faculty');
    }

    return $faculty;
}

/**
 * Validate output format.
 *
 * @param string $format Raw format.
 * @return string Safe format.
 */
function local_uckk_faculty_cache_safe_format(string $format): string {
    $format = trim(\core_text::strtolower($format));

    if (!in_array($format, ['html', 'json'], true)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'format');
    }

    return $format;
}

/**
 * Call the first available static method.
 *
 * @param class-string $class Class name.
 * @param array<int, string> $methods Candidate method names.
 * @param array<int, mixed> $arguments Arguments.
 * @return mixed
 */
function local_uckk_faculty_cache_call_static_first(string $class, array $methods, array $arguments = []) {
    foreach ($methods as $method) {
        if (method_exists($class, $method) && is_callable([$class, $method])) {
            return $class::{$method}(...$arguments);
        }
    }

    throw new coding_exception(
        'No supported static method found on ' . $class . ': ' . implode(', ', $methods)
    );
}

/**
 * Check whether a static method exists.
 *
 * @param class-string $class Class name.
 * @param string $method Method name.
 * @return bool
 */
function local_uckk_faculty_cache_has_static(string $class, string $method): bool {
    return method_exists($class, $method) && is_callable([$class, $method]);
}

/**
 * Get all known faculty slugs through the registry.
 *
 * @return array<int, string>
 */
function local_uckk_faculty_cache_all_slugs(): array {
    $items = local_uckk_faculty_cache_call_static_first(
        faculty_registry::class,
        ['get_all', 'all', 'items', 'list_all']
    );

    if (!is_array($items)) {
        throw new coding_exception('Faculty registry did not return manifest items.');
    }

    $slugs = [];

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['slug'])) {
            continue;
        }

        $slug = (string)$item['slug'];

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $slugs[] = $slug;
        }
    }

    sort($slugs);

    return array_values(array_unique($slugs));
}

/**
 * Purge a Moodle cache area if it exists.
 *
 * This fallback is used only when the faculty_cache service class does not
 * expose a more specific method.
 *
 * @param string $area Cache area.
 * @return bool Whether purge was attempted successfully.
 */
function local_uckk_faculty_cache_purge_area(string $area): bool {
    try {
        $cache = cache::make('local_uckk', $area);
        $cache->purge();

        return true;
    } catch (Throwable $exception) {
        debugging(
            'local_uckk cache area purge failed for ' . $area . ': ' . $exception->getMessage(),
            DEBUG_DEVELOPER
        );

        return false;
    }
}

/**
 * Purge cache by slug through faculty_cache or fallback cache stores.
 *
 * @param string $slug Faculty slug.
 * @param string $scope Scope.
 * @return array<string, mixed>
 */
function local_uckk_faculty_cache_purge_slug(string $slug, string $scope): array {
    $result = [
        'slug' => $slug,
        'scope' => $scope,
        'status' => 'completed',
        'purged_areas' => [],
        'warnings' => [],
    ];

    if (class_exists(faculty_cache::class)) {
        $scopeclassmethods = [
            LOCAL_UCKK_FACULTY_CACHE_SCOPE_FACULTY => ['purge_faculty', 'purge_slug'],
            LOCAL_UCKK_FACULTY_CACHE_SCOPE_PAGE => ['purge_page', 'purge_faculty_page'],
            LOCAL_UCKK_FACULTY_CACHE_SCOPE_PROFILE => ['purge_profile', 'purge_faculty_profile'],
            LOCAL_UCKK_FACULTY_CACHE_SCOPE_DYNAMIC_BLOCKS => ['purge_dynamic_blocks', 'purge_faculty_dynamic_blocks'],
            LOCAL_UCKK_FACULTY_CACHE_SCOPE_SYNC_REPORT => ['purge_sync_report', 'purge_faculty_sync_report'],
        ];

        if ($scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL) {
            foreach ([
                'purge_faculty',
                'purge_slug',
                'purge_page',
                'purge_profile',
                'purge_dynamic_blocks',
                'purge_sync_report',
            ] as $method) {
                if (local_uckk_faculty_cache_has_static(faculty_cache::class, $method)) {
                    faculty_cache::{$method}($slug);
                    $result['purged_areas'][] = $method;
                }
            }

            if ($result['purged_areas'] !== []) {
                return $result;
            }
        }

        if (isset($scopeclassmethods[$scope])) {
            foreach ($scopeclassmethods[$scope] as $method) {
                if (local_uckk_faculty_cache_has_static(faculty_cache::class, $method)) {
                    faculty_cache::{$method}($slug);
                    $result['purged_areas'][] = $method;

                    return $result;
                }
            }
        }
    }

    $fallbackareas = [];

    if (in_array($scope, [LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL, LOCAL_UCKK_FACULTY_CACHE_SCOPE_PAGE], true)) {
        $fallbackareas[] = 'faculty_page';
    }

    if (in_array($scope, [LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL, LOCAL_UCKK_FACULTY_CACHE_SCOPE_PROFILE], true)) {
        $fallbackareas[] = 'faculty_profile';
    }

    if (in_array($scope, [LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL, LOCAL_UCKK_FACULTY_CACHE_SCOPE_DYNAMIC_BLOCKS], true)) {
        $fallbackareas[] = 'faculty_dynamic_block';
    }

    if (in_array($scope, [LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL, LOCAL_UCKK_FACULTY_CACHE_SCOPE_SYNC_REPORT], true)) {
        $fallbackareas[] = 'faculty_sync_report';
    }

    foreach (array_unique($fallbackareas) as $area) {
        if (local_uckk_faculty_cache_purge_area($area)) {
            $result['purged_areas'][] = $area;
        } else {
            $result['warnings'][] = 'Cache area could not be purged: ' . $area;
        }
    }

    if ($result['purged_areas'] === []) {
        $result['status'] = 'warning';
        $result['warnings'][] = 'No matching faculty cache purge method or cache area was available.';
    }

    return $result;
}

/**
 * Purge manifest-level caches.
 *
 * @return array<string, mixed>
 */
function local_uckk_faculty_cache_purge_manifests(): array {
    $result = [
        'slug' => 'all',
        'scope' => LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS,
        'status' => 'completed',
        'purged_areas' => [],
        'warnings' => [],
    ];

    if (class_exists(faculty_cache::class)) {
        foreach (['purge_manifests', 'purge_manifest', 'purge_registry'] as $method) {
            if (local_uckk_faculty_cache_has_static(faculty_cache::class, $method)) {
                faculty_cache::{$method}();
                $result['purged_areas'][] = $method;

                return $result;
            }
        }
    }

    foreach (['faculty_manifest', 'atlas_manifest'] as $area) {
        if (local_uckk_faculty_cache_purge_area($area)) {
            $result['purged_areas'][] = $area;
        } else {
            $result['warnings'][] = 'Cache area could not be purged: ' . $area;
        }
    }

    if ($result['purged_areas'] === []) {
        $result['status'] = 'warning';
        $result['warnings'][] = 'No manifest cache area was purged.';
    }

    return $result;
}

/**
 * Purge all faculty-related caches.
 *
 * @return array<string, mixed>
 */
function local_uckk_faculty_cache_purge_all(): array {
    $result = [
        'slug' => 'all',
        'scope' => LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL,
        'status' => 'completed',
        'purged_areas' => [],
        'warnings' => [],
    ];

    if (class_exists(faculty_cache::class)) {
        foreach (['purge_all', 'purge'] as $method) {
            if (local_uckk_faculty_cache_has_static(faculty_cache::class, $method)) {
                faculty_cache::{$method}();
                $result['purged_areas'][] = $method;

                return $result;
            }
        }
    }

    $areas = [
        'atlas_manifest',
        'atlas_voie',
        'faculty_manifest',
        'faculty_profile',
        'faculty_page',
        'faculty_dynamic_block',
        'faculty_sync_report',
    ];

    foreach ($areas as $area) {
        if (local_uckk_faculty_cache_purge_area($area)) {
            $result['purged_areas'][] = $area;
        } else {
            $result['warnings'][] = 'Cache area could not be purged: ' . $area;
        }
    }

    if ($result['purged_areas'] === []) {
        $result['status'] = 'warning';
        $result['warnings'][] = 'No cache area was purged.';
    }

    return $result;
}

/**
 * Build the preview or purge report.
 *
 * @param string $action Action.
 * @param string $scope Scope.
 * @param string $faculty Faculty slug or all.
 * @param bool $confirmed Whether purge is confirmed.
 * @return array<string, mixed>
 */
function local_uckk_faculty_cache_build_report(
    string $action,
    string $scope,
    string $faculty,
    bool $confirmed
): array {
    $slugs = $faculty === 'all' ? local_uckk_faculty_cache_all_slugs() : [$faculty];

    $report = [
        'schema_version' => 'UCKK-FACULTY-CACHE-REPORT-0.1',
        'component' => 'local_uckk',
        'action' => $action,
        'scope' => $scope,
        'faculty' => $faculty,
        'confirmed' => $confirmed,
        'generated_at' => gmdate('c'),
        'status' => 'preview',
        'counts' => [
            'faculties_total' => count($slugs),
            'items_total' => 0,
            'purged_items' => 0,
            'warnings' => 0,
        ],
        'items' => [],
    ];

    if ($action === LOCAL_UCKK_FACULTY_CACHE_ACTION_PREVIEW || !$confirmed) {
        foreach ($slugs as $slug) {
            $report['items'][] = [
                'slug' => $slug,
                'scope' => $scope,
                'status' => 'pending',
                'purged_areas' => [],
                'warnings' => [],
            ];
        }

        if ($scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS || $scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL) {
            $report['items'][] = [
                'slug' => 'all',
                'scope' => $scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL ? 'global' : LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS,
                'status' => 'pending',
                'purged_areas' => [],
                'warnings' => [],
            ];
        }

        $report['counts']['items_total'] = count($report['items']);

        return $report;
    }

    if ($faculty === 'all' && $scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL) {
        $report['items'][] = local_uckk_faculty_cache_purge_all();
    } else if ($scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS) {
        $report['items'][] = local_uckk_faculty_cache_purge_manifests();
    } else {
        foreach ($slugs as $slug) {
            $report['items'][] = local_uckk_faculty_cache_purge_slug($slug, $scope);
        }

        if ($scope === LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL) {
            $report['items'][] = local_uckk_faculty_cache_purge_manifests();
        }
    }

    $warnings = 0;
    $purged = 0;

    foreach ($report['items'] as $item) {
        if (!empty($item['purged_areas'])) {
            $purged++;
        }

        $warnings += count($item['warnings'] ?? []);
    }

    $report['status'] = $warnings > 0 ? 'warning' : 'completed';
    $report['counts']['items_total'] = count($report['items']);
    $report['counts']['purged_items'] = $purged;
    $report['counts']['warnings'] = $warnings;

    return $report;
}

/**
 * Trigger cache purged event without exposing private data.
 *
 * @param context_system $context System context.
 * @param array<string, mixed> $report Report.
 */
function local_uckk_faculty_cache_trigger_event(context_system $context, array $report): void {
    $eventclass = '\\local_uckk\\event\\faculty_cache_purged';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'context' => $context,
        'other' => [
            'action' => (string)$report['action'],
            'scope' => (string)$report['scope'],
            'faculty' => (string)$report['faculty'],
            'status' => (string)$report['status'],
            'faculties_total' => (int)$report['counts']['faculties_total'],
            'purged_items' => (int)$report['counts']['purged_items'],
            'warning_count' => (int)$report['counts']['warnings'],
        ],
    ]);

    $event->trigger();
}

/**
 * Emit JSON report.
 *
 * @param array<string, mixed> $report Report.
 * @return never
 */
function local_uckk_faculty_cache_emit_json(array $report): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Render a status badge.
 *
 * @param string $status Status.
 * @return string HTML.
 */
function local_uckk_faculty_cache_status_badge(string $status): string {
    $class = 'badge-secondary';

    if ($status === 'completed') {
        $class = 'badge-success';
    } else if ($status === 'warning') {
        $class = 'badge-warning';
    } else if ($status === 'pending' || $status === 'preview') {
        $class = 'badge-info';
    }

    return html_writer::span(s($status), 'badge ' . $class);
}

/**
 * Render messages.
 *
 * @param array<int, string> $messages Messages.
 * @param string $class CSS class.
 * @return string HTML.
 */
function local_uckk_faculty_cache_render_messages(array $messages, string $class): string {
    if ($messages === []) {
        return html_writer::span('—', 'text-muted');
    }

    $items = [];

    foreach ($messages as $message) {
        $items[] = html_writer::tag('li', s($message));
    }

    return html_writer::tag('ul', implode('', $items), ['class' => $class]);
}

/**
 * Render controls.
 *
 * @param moodle_url $url Page URL.
 * @param string $scope Current scope.
 * @param string $faculty Current faculty.
 * @return string HTML.
 */
function local_uckk_faculty_cache_render_controls(moodle_url $url, string $scope, string $faculty): string {
    $scopes = [
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL => 'All faculty caches',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_FACULTY => 'Faculty page set',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_PAGE => 'Rendered faculty page',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_PROFILE => 'Faculty profile',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_DYNAMIC_BLOCKS => 'Dynamic blocks',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_MANIFESTS => 'Manifests',
        LOCAL_UCKK_FACULTY_CACHE_SCOPE_SYNC_REPORT => 'Sync report',
    ];

    $html = html_writer::start_div('card card-body mb-3');
    $html .= html_writer::tag('h3', 'Faculty cache purge');

    $html .= html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $url->out(false),
        'class' => 'mb-3',
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => LOCAL_UCKK_FACULTY_CACHE_ACTION_PREVIEW,
    ]);

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::label('Faculty slug', 'id_faculty');
    $html .= html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'faculty',
        'id' => 'id_faculty',
        'value' => s($faculty),
        'class' => 'form-control',
        'placeholder' => 'all',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::label('Scope', 'id_scope');
    $html .= html_writer::select($scopes, 'scope', $scope, false, [
        'id' => 'id_scope',
        'class' => 'custom-select form-control',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::tag('button', 'Preview purge', [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]);

    $html .= html_writer::end_tag('form');

    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $url->out(false),
        'class' => 'border-top pt-3',
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE,
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'faculty',
        'value' => s($faculty),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'scope',
        'value' => s($scope),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'confirm',
        'value' => 1,
    ]);

    $html .= html_writer::tag(
        'p',
        'Purge requires sesskey and explicit confirmation. It does not modify JSON, Moodle courses, grades, completions or enrolments.',
        ['class' => 'text-muted']
    );

    $html .= html_writer::tag('button', 'Purge selected cache', [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]);

    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Render HTML report.
 *
 * @param array<string, mixed> $report Report.
 * @param moodle_url $url Page URL.
 * @return string HTML.
 */
function local_uckk_faculty_cache_render_report(array $report, moodle_url $url): string {
    $html = local_uckk_faculty_cache_render_controls(
        $url,
        (string)$report['scope'],
        (string)$report['faculty']
    );

    $summary = new html_table();
    $summary->attributes['class'] = 'generaltable';
    $summary->head = ['Metric', 'Value'];
    $summary->data = [
        ['Action', s((string)$report['action'])],
        ['Scope', s((string)$report['scope'])],
        ['Faculty', html_writer::tag('code', s((string)$report['faculty']))],
        ['Status', local_uckk_faculty_cache_status_badge((string)$report['status'])],
        ['Faculties', s((string)$report['counts']['faculties_total'])],
        ['Items', s((string)$report['counts']['items_total'])],
        ['Purged items', s((string)$report['counts']['purged_items'])],
        ['Warnings', s((string)$report['counts']['warnings'])],
    ];

    $html .= html_writer::tag('h3', 'Summary');
    $html .= html_writer::table($summary);

    $items = new html_table();
    $items->attributes['class'] = 'generaltable';
    $items->head = ['Slug', 'Scope', 'Status', 'Purged areas', 'Warnings'];
    $items->data = [];

    foreach ($report['items'] as $item) {
        $areas = $item['purged_areas'] ?? [];

        $items->data[] = [
            html_writer::tag('code', s((string)$item['slug'])),
            s((string)$item['scope']),
            local_uckk_faculty_cache_status_badge((string)$item['status']),
            $areas === []
                ? html_writer::span('—', 'text-muted')
                : html_writer::tag(
                    'ul',
                    implode('', array_map(static function(string $area): string {
                        return html_writer::tag('li', html_writer::tag('code', s($area)));
                    }, $areas))
                ),
            local_uckk_faculty_cache_render_messages($item['warnings'] ?? [], 'text-warning'),
        ];
    }

    $html .= html_writer::tag('h3', 'Items');
    $html .= html_writer::table($items);

    return $html;
}

$context = context_system::instance();
$url = new moodle_url('/local/uckk/faculty_cache.php');

require_login();
require_capability('local/uckk:purgefacultycache', $context);

$action = local_uckk_faculty_cache_safe_action(
    optional_param('action', LOCAL_UCKK_FACULTY_CACHE_ACTION_PREVIEW, PARAM_ALPHANUMEXT)
);
$scope = local_uckk_faculty_cache_safe_scope(
    optional_param('scope', LOCAL_UCKK_FACULTY_CACHE_SCOPE_ALL, PARAM_ALPHANUMEXT)
);
$faculty = local_uckk_faculty_cache_safe_faculty(
    optional_param('faculty', 'all', PARAM_ALPHANUMEXT)
);
$format = local_uckk_faculty_cache_safe_format(
    optional_param('format', 'html', PARAM_ALPHA)
);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($action === LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE) {
    require_sesskey();

    if (!$confirm) {
        throw new moodle_exception('confirmsesskeybad', 'error');
    }
}

$PAGE->set_context($context);
$PAGE->set_url($url, [
    'action' => $action,
    'scope' => $scope,
    'faculty' => $faculty,
    'format' => $format,
]);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('UCKK faculty cache');
$PAGE->set_heading(format_string($SITE->fullname));

$report = local_uckk_faculty_cache_build_report(
    $action,
    $scope,
    $faculty,
    $action === LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE && (bool)$confirm
);

if ($action === LOCAL_UCKK_FACULTY_CACHE_ACTION_PURGE) {
    local_uckk_faculty_cache_trigger_event($context, $report);
}

if ($format === 'json') {
    local_uckk_faculty_cache_emit_json($report);
}

echo $OUTPUT->header();
echo local_uckk_faculty_cache_render_report($report, $url);
echo $OUTPUT->footer();