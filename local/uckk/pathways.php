<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Pathway management page for local_uckk.
 *
 * This page lists, filters and manages UCKK pathways.
 *
 * A pathway is an internal UCKK learning route connecting:
 * - a program;
 * - courses;
 * - competencies;
 * - badges;
 * - portfolio expectations;
 * - archive requirements;
 * - internal recognition rules.
 *
 * This page intentionally delegates business logic to local_uckk\api\pathway_api.
 * It must not duplicate pathway calculation, completion logic, badge award logic,
 * competency logic, integrity review, archive validation, or seed logic.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_uckk\api\pathway_api;
use local_uckk\api\program_api;

global $OUTPUT, $PAGE, $SITE, $USER;

require_login();

$context = context_system::instance();

$viewcapability = 'local/uckk:viewcampus';
$managecapability = 'local/uckk:managepathways';

if (!has_capability($managecapability, $context)) {
    require_capability($viewcapability, $context);
}

$canmanage = has_capability($managecapability, $context);

$url = new moodle_url('/local/uckk/pathways.php');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$id = optional_param('id', 0, PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$visibility = optional_param('visibility', '', PARAM_ALPHANUMEXT);
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$perpage = max(5, min(100, $perpage));
$page = max(0, $page);

$params = [];

if ($programid > 0) {
    $params['programid'] = $programid;
}

if ($status !== '') {
    $params['status'] = $status;
}

if ($visibility !== '') {
    $params['visibility'] = $visibility;
}

if ($search !== '') {
    $params['search'] = $search;
}

if ($page > 0) {
    $params['page'] = $page;
}

if ($perpage !== 25) {
    $params['perpage'] = $perpage;
}

$PAGE->set_context($context);
$PAGE->set_url($url, $params);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pathways', 'local_uckk'));
$PAGE->set_heading(format_string($SITE->fullname));

/**
 * Ensure the pathway API is available.
 *
 * @return void
 * @throws moodle_exception
 */
function local_uckk_pathways_require_api(): void {
    if (!class_exists(pathway_api::class)) {
        throw new moodle_exception('missingpathwayapi', 'local_uckk');
    }
}

/**
 * Return true if a string exists in local_uckk.
 *
 * @param string $identifier String identifier.
 * @return bool
 */
function local_uckk_pathways_string_exists(string $identifier): bool {
    return get_string_manager()->string_exists($identifier, 'local_uckk');
}

/**
 * Get a local_uckk string with fallback.
 *
 * @param string $identifier String identifier.
 * @param string $fallback Fallback text.
 * @param mixed $a Optional string parameter.
 * @return string
 */
function local_uckk_pathways_get_string(string $identifier, string $fallback, $a = null): string {
    if (local_uckk_pathways_string_exists($identifier)) {
        return get_string($identifier, 'local_uckk', $a);
    }

    return $fallback;
}

/**
 * Build the current page URL with overrides.
 *
 * @param array<string, mixed> $overrides Parameter overrides.
 * @return moodle_url
 */
function local_uckk_pathways_url(array $overrides = []): moodle_url {
    $base = [
        'programid' => optional_param('programid', 0, PARAM_INT),
        'status' => optional_param('status', '', PARAM_ALPHANUMEXT),
        'visibility' => optional_param('visibility', '', PARAM_ALPHANUMEXT),
        'search' => optional_param('search', '', PARAM_TEXT),
        'page' => optional_param('page', 0, PARAM_INT),
        'perpage' => optional_param('perpage', 25, PARAM_INT),
    ];

    $params = array_merge($base, $overrides);

    foreach ($params as $key => $value) {
        if ($value === '' || $value === 0 || $value === null) {
            unset($params[$key]);
        }
    }

    return new moodle_url('/local/uckk/pathways.php', $params);
}

/**
 * Get pathway filters from request parameters.
 *
 * @return array<string, mixed>
 */
function local_uckk_pathways_get_filters(): array {
    $filters = [];

    $programid = optional_param('programid', 0, PARAM_INT);
    $status = optional_param('status', '', PARAM_ALPHANUMEXT);
    $visibility = optional_param('visibility', '', PARAM_ALPHANUMEXT);
    $search = optional_param('search', '', PARAM_TEXT);

    if ($programid > 0) {
        $filters['programid'] = $programid;
    }

    if ($status !== '') {
        $filters['status'] = $status;
    }

    if ($visibility !== '') {
        $filters['visibility'] = $visibility;
    }

    if ($search !== '') {
        $filters['search'] = $search;
    }

    return $filters;
}

/**
 * Load UCKK programs for filter dropdown.
 *
 * @param context_system $context System context.
 * @return array<int, string>
 */
function local_uckk_pathways_get_program_options(context_system $context): array {
    $options = [
        0 => local_uckk_pathways_get_string('allprograms', 'Tous les programmes'),
    ];

    if (!class_exists(program_api::class)) {
        return $options;
    }

    try {
        $programs = program_api::get_programs([], $context);
    } catch (Throwable $exception) {
        return $options;
    }

    foreach ($programs as $program) {
        $options[(int)$program->id] = format_string($program->fullname);
    }

    return $options;
}

/**
 * Return status filter options.
 *
 * @return array<string, string>
 */
function local_uckk_pathways_get_status_options(): array {
    $options = [
        '' => local_uckk_pathways_get_string('allstatuses', 'Tous les statuts'),
    ];

    if (class_exists(pathway_api::class) && method_exists(pathway_api::class, 'get_allowed_statuses')) {
        foreach (pathway_api::get_allowed_statuses() as $status) {
            $options[$status] = local_uckk_pathways_status_label($status);
        }

        return $options;
    }

    foreach (['draft', 'active', 'hidden', 'archived'] as $status) {
        $options[$status] = local_uckk_pathways_status_label($status);
    }

    return $options;
}

/**
 * Return visibility filter options.
 *
 * @return array<string, string>
 */
function local_uckk_pathways_get_visibility_options(): array {
    $options = [
        '' => local_uckk_pathways_get_string('allvisibilities', 'Toutes les visibilités'),
    ];

    if (class_exists(pathway_api::class) && method_exists(pathway_api::class, 'get_allowed_visibilities')) {
        foreach (pathway_api::get_allowed_visibilities() as $visibility) {
            $options[$visibility] = local_uckk_pathways_visibility_label($visibility);
        }

        return $options;
    }

    foreach (['private', 'institution', 'public'] as $visibility) {
        $options[$visibility] = local_uckk_pathways_visibility_label($visibility);
    }

    return $options;
}

/**
 * Return a display label for a pathway status.
 *
 * @param string $status Status key.
 * @return string
 */
function local_uckk_pathways_status_label(string $status): string {
    $identifier = 'status_' . str_replace('-', '', $status);

    if (local_uckk_pathways_string_exists($identifier)) {
        return get_string($identifier, 'local_uckk');
    }

    return ucfirst(str_replace(['_', '-'], ' ', $status));
}

/**
 * Return a display label for a visibility key.
 *
 * @param string $visibility Visibility key.
 * @return string
 */
function local_uckk_pathways_visibility_label(string $visibility): string {
    $identifier = 'visibility_' . str_replace('-', '', $visibility);

    if (local_uckk_pathways_string_exists($identifier)) {
        return get_string($identifier, 'local_uckk');
    }

    return ucfirst(str_replace(['_', '-'], ' ', $visibility));
}

/**
 * Return CSS class for a pathway status badge.
 *
 * @param string $status Status key.
 * @return string
 */
function local_uckk_pathways_status_badge_class(string $status): string {
    switch ($status) {
        case 'active':
            return 'badge badge-success';
        case 'draft':
            return 'badge badge-secondary';
        case 'hidden':
            return 'badge badge-warning';
        case 'archived':
            return 'badge badge-info';
        default:
            return 'badge badge-light';
    }
}

/**
 * Return CSS class for a visibility badge.
 *
 * @param string $visibility Visibility key.
 * @return string
 */
function local_uckk_pathways_visibility_badge_class(string $visibility): string {
    switch ($visibility) {
        case 'public':
            return 'badge badge-primary';
        case 'institution':
            return 'badge badge-secondary';
        case 'private':
            return 'badge badge-dark';
        default:
            return 'badge badge-light';
    }
}

/**
 * Render pathway filters.
 *
 * @param context_system $context System context.
 * @return string
 */
function local_uckk_pathways_render_filters(context_system $context): string {
    $programid = optional_param('programid', 0, PARAM_INT);
    $status = optional_param('status', '', PARAM_ALPHANUMEXT);
    $visibility = optional_param('visibility', '', PARAM_ALPHANUMEXT);
    $search = optional_param('search', '', PARAM_TEXT);
    $perpage = optional_param('perpage', 25, PARAM_INT);

    $programoptions = local_uckk_pathways_get_program_options($context);
    $statusoptions = local_uckk_pathways_get_status_options();
    $visibilityoptions = local_uckk_pathways_get_visibility_options();

    $output = html_writer::start_tag('form', [
        'method' => 'get',
        'action' => (new moodle_url('/local/uckk/pathways.php'))->out(false),
        'class' => 'local-uckk-pathway-filters mb-3',
    ]);

    $output .= html_writer::start_div('card');
    $output .= html_writer::start_div('card-body');
    $output .= html_writer::start_div('row');

    $output .= html_writer::start_div('col-md-3 mb-2');
    $output .= html_writer::label(
        local_uckk_pathways_get_string('program', 'Programme'),
        'id_programid',
        false,
        ['class' => 'form-label']
    );
    $output .= html_writer::select($programoptions, 'programid', $programid, false, [
        'id' => 'id_programid',
        'class' => 'custom-select form-control',
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('col-md-2 mb-2');
    $output .= html_writer::label(
        local_uckk_pathways_get_string('status', 'Statut'),
        'id_status',
        false,
        ['class' => 'form-label']
    );
    $output .= html_writer::select($statusoptions, 'status', $status, false, [
        'id' => 'id_status',
        'class' => 'custom-select form-control',
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('col-md-2 mb-2');
    $output .= html_writer::label(
        local_uckk_pathways_get_string('visibility', 'Visibilité'),
        'id_visibility',
        false,
        ['class' => 'form-label']
    );
    $output .= html_writer::select($visibilityoptions, 'visibility', $visibility, false, [
        'id' => 'id_visibility',
        'class' => 'custom-select form-control',
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('col-md-3 mb-2');
    $output .= html_writer::label(
        get_string('search'),
        'id_search',
        false,
        ['class' => 'form-label']
    );
    $output .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'id_search',
        'name' => 'search',
        'value' => s($search),
        'class' => 'form-control',
        'placeholder' => local_uckk_pathways_get_string('searchpathways', 'Rechercher un parcours'),
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('col-md-2 mb-2');
    $output .= html_writer::label(
        local_uckk_pathways_get_string('perpage', 'Par page'),
        'id_perpage',
        false,
        ['class' => 'form-label']
    );
    $output .= html_writer::select([10 => 10, 25 => 25, 50 => 50, 100 => 100], 'perpage', $perpage, false, [
        'id' => 'id_perpage',
        'class' => 'custom-select form-control',
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::end_div();

    $output .= html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
    $output .= html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter'),
        'class' => 'btn btn-primary mr-2',
    ]);
    $output .= html_writer::link(
        new moodle_url('/local/uckk/pathways.php'),
        get_string('reset'),
        ['class' => 'btn btn-outline-secondary']
    );
    $output .= html_writer::end_div();

    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    $output .= html_writer::end_tag('form');

    return $output;
}

/**
 * Render top action links.
 *
 * @param bool $canmanage Whether the user can manage pathways.
 * @return string
 */
function local_uckk_pathways_render_actions(bool $canmanage): string {
    $links = [];

    if ($canmanage) {
        $links[] = html_writer::link(
            new moodle_url('/local/uckk/pathways.php', ['action' => 'create']),
            local_uckk_pathways_get_string('createpathway', 'Créer un parcours'),
            ['class' => 'btn btn-primary']
        );

        if (core_component::get_plugin_directory('tool', 'uckkseed') !== null) {
            $links[] = html_writer::link(
                new moodle_url('/admin/tool/uckkseed/index.php'),
                local_uckk_pathways_get_string('seedtool', 'Outil de génération UCKK'),
                ['class' => 'btn btn-outline-secondary']
            );
        }
    }

    $links[] = html_writer::link(
        new moodle_url('/local/uckk/programs.php'),
        local_uckk_pathways_get_string('programs', 'Programmes'),
        ['class' => 'btn btn-outline-secondary']
    );

    $links[] = html_writer::link(
        new moodle_url('/local/uckk/canon.php'),
        local_uckk_pathways_get_string('canon', 'Canon UCKK'),
        ['class' => 'btn btn-outline-secondary']
    );

    return html_writer::div(implode(' ', $links), 'mb-3 local-uckk-pathway-actions');
}

/**
 * Render a management placeholder for create/edit actions.
 *
 * This page delegates form handling to future form classes. Until those forms
 * are available, it refuses unsafe write operations rather than creating partial
 * records from raw request data.
 *
 * @param string $action Requested action.
 * @return string
 */
function local_uckk_pathways_render_management_notice(string $action): string {
    $message = $action === 'create'
        ? local_uckk_pathways_get_string(
            'pathwaycreateformmissing',
            'La création de parcours doit passer par la classe de formulaire dédiée.'
        )
        : local_uckk_pathways_get_string(
            'pathwayeditformmissing',
            'La modification de parcours doit passer par la classe de formulaire dédiée.'
        );

    return html_writer::div(
        html_writer::tag('strong', local_uckk_pathways_get_string('actionnotavailable', 'Action non disponible'), [
            'class' => 'd-block',
        ]) . s($message),
        'alert alert-warning',
        ['role' => 'alert']
    );
}

/**
 * Handle safe mutating actions.
 *
 * @param string $action Action key.
 * @param int $id Pathway id.
 * @param context_system $context System context.
 * @param bool $canmanage Whether user can manage pathways.
 * @return void
 */
function local_uckk_pathways_handle_action(string $action, int $id, context_system $context, bool $canmanage): void {
    if ($action === '' || in_array($action, ['create', 'edit'], true)) {
        return;
    }

    if (!$canmanage) {
        throw new required_capability_exception($context, 'local/uckk:managepathways', 'nopermissions', '');
    }

    require_sesskey();
    local_uckk_pathways_require_api();

    if ($id <= 0) {
        throw new invalid_parameter_exception('Invalid pathway id.');
    }

    if ($action === 'archive') {
        if (!method_exists(pathway_api::class, 'archive_pathway')) {
            throw new moodle_exception('missingpathwaymethod', 'local_uckk', '', 'archive_pathway');
        }

        pathway_api::archive_pathway($id, $context);

        redirect(
            local_uckk_pathways_url(['action' => null, 'id' => null, 'sesskey' => null]),
            local_uckk_pathways_get_string('pathwayarchived', 'Parcours archivé.'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($action === 'activate') {
        if (!method_exists(pathway_api::class, 'update_pathway')) {
            throw new moodle_exception('missingpathwaymethod', 'local_uckk', '', 'update_pathway');
        }

        pathway_api::update_pathway($id, ['status' => 'active'], $context);

        redirect(
            local_uckk_pathways_url(['action' => null, 'id' => null, 'sesskey' => null]),
            local_uckk_pathways_get_string('pathwayactivated', 'Parcours activé.'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    throw new moodle_exception('unknownaction', 'local_uckk', '', $action);
}

/**
 * Load pathways through pathway_api.
 *
 * @param array<string, mixed> $filters Filters.
 * @param int $page Page number.
 * @param int $perpage Per page count.
 * @param context_system $context System context.
 * @return array{0: array<int, stdClass>, 1: int}
 */
function local_uckk_pathways_load(array $filters, int $page, int $perpage, context_system $context): array {
    local_uckk_pathways_require_api();

    if (!method_exists(pathway_api::class, 'get_pathways')) {
        throw new moodle_exception('missingpathwaymethod', 'local_uckk', '', 'get_pathways');
    }

    $options = [
        'offset' => $page * $perpage,
        'limit' => $perpage,
    ];

    $pathways = pathway_api::get_pathways($filters, $context, $options);

    if (method_exists(pathway_api::class, 'count_pathways')) {
        $total = pathway_api::count_pathways($filters, $context);
    } else {
        $total = count($pathways);
    }

    return [$pathways, $total];
}

/**
 * Render a pathway table.
 *
 * @param array<int, stdClass> $pathways Pathway records.
 * @param bool $canmanage Whether the user can manage pathways.
 * @return string
 */
function local_uckk_pathways_render_table(array $pathways, bool $canmanage): string {
    if (empty($pathways)) {
        return html_writer::div(
            local_uckk_pathways_get_string('nopathwaysfound', 'Aucun parcours trouvé.'),
            'alert alert-info',
            ['role' => 'status']
        );
    }

    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover local-uckk-pathways-table';
    $table->head = [
        local_uckk_pathways_get_string('pathway', 'Parcours'),
        local_uckk_pathways_get_string('program', 'Programme'),
        local_uckk_pathways_get_string('requirements', 'Exigences'),
        local_uckk_pathways_get_string('status', 'Statut'),
        local_uckk_pathways_get_string('visibility', 'Visibilité'),
        local_uckk_pathways_get_string('updated', 'Mise à jour'),
        get_string('actions'),
    ];

    foreach ($pathways as $pathway) {
        $table->data[] = local_uckk_pathways_render_row($pathway, $canmanage);
    }

    return html_writer::table($table);
}

/**
 * Render a single pathway table row.
 *
 * @param stdClass $pathway Pathway record.
 * @param bool $canmanage Whether the user can manage pathways.
 * @return array<int, string>
 */
function local_uckk_pathways_render_row(stdClass $pathway, bool $canmanage): array {
    $name = format_string($pathway->fullname ?? '');
    $shortname = s($pathway->shortname ?? '');

    $title = html_writer::tag('strong', $name, ['class' => 'd-block']);
    $title .= html_writer::tag('code', $shortname);

    if (!empty($pathway->description)) {
        $title .= html_writer::tag(
            'div',
            format_text($pathway->description, $pathway->descriptionformat ?? FORMAT_HTML),
            ['class' => 'small text-muted mt-1']
        );
    }

    $program = '';
    if (!empty($pathway->programfullname)) {
        $program .= html_writer::tag('strong', format_string($pathway->programfullname), ['class' => 'd-block']);
    }

    if (!empty($pathway->programshortname)) {
        $program .= html_writer::tag('code', s($pathway->programshortname));
    }

    if ($program === '') {
        $program = html_writer::span('-', 'text-muted');
    }

    $requirements = local_uckk_pathways_render_requirements_summary($pathway);

    $status = (string)($pathway->status ?? 'draft');
    $statusbadge = html_writer::span(
        local_uckk_pathways_status_label($status),
        local_uckk_pathways_status_badge_class($status)
    );

    $visibility = (string)($pathway->visibility ?? 'institution');
    $visibilitybadge = html_writer::span(
        local_uckk_pathways_visibility_label($visibility),
        local_uckk_pathways_visibility_badge_class($visibility)
    );

    $updated = !empty($pathway->timemodified)
        ? userdate((int)$pathway->timemodified)
        : html_writer::span('-', 'text-muted');

    $actions = local_uckk_pathways_render_row_actions($pathway, $canmanage);

    return [
        $title,
        $program,
        $requirements,
        $statusbadge,
        $visibilitybadge,
        $updated,
        $actions,
    ];
}

/**
 * Render a requirements summary.
 *
 * @param stdClass $pathway Pathway record.
 * @return string
 */
function local_uckk_pathways_render_requirements_summary(stdClass $pathway): string {
    $items = [];

    $coursecount = (int)($pathway->coursecount ?? $pathway->requiredcoursecount ?? 0);
    $competencycount = (int)($pathway->competencycount ?? $pathway->requiredcompetencycount ?? 0);
    $badgecount = (int)($pathway->badgecount ?? $pathway->requiredbadgecount ?? 0);

    if ($coursecount > 0) {
        $items[] = html_writer::span(
            $coursecount . ' ' . local_uckk_pathways_get_string('courses', 'cours'),
            'badge badge-light border mr-1'
        );
    }

    if ($competencycount > 0) {
        $items[] = html_writer::span(
            $competencycount . ' ' . local_uckk_pathways_get_string('competencies', 'compétences'),
            'badge badge-light border mr-1'
        );
    }

    if ($badgecount > 0) {
        $items[] = html_writer::span(
            $badgecount . ' ' . local_uckk_pathways_get_string('badges', 'badges'),
            'badge badge-light border mr-1'
        );
    }

    if (empty($items)) {
        return html_writer::span(
            local_uckk_pathways_get_string('norequirements', 'Aucune exigence définie'),
            'text-muted'
        );
    }

    return implode(' ', $items);
}

/**
 * Render row actions.
 *
 * @param stdClass $pathway Pathway record.
 * @param bool $canmanage Whether user can manage pathways.
 * @return string
 */
function local_uckk_pathways_render_row_actions(stdClass $pathway, bool $canmanage): string {
    $id = (int)($pathway->id ?? 0);
    $actions = [];

    if ($id <= 0) {
        return '';
    }

    if (method_exists(pathway_api::class, 'get_pathway_url')) {
        try {
            $viewurl = pathway_api::get_pathway_url($id);
        } catch (Throwable $exception) {
            $viewurl = new moodle_url('/local/uckk/pathways.php', ['id' => $id]);
        }
    } else {
        $viewurl = new moodle_url('/local/uckk/pathways.php', ['id' => $id]);
    }

    $actions[] = html_writer::link(
        $viewurl,
        local_uckk_pathways_get_string('view', 'Voir'),
        ['class' => 'btn btn-sm btn-outline-secondary']
    );

    if ($canmanage) {
        $actions[] = html_writer::link(
            new moodle_url('/local/uckk/pathways.php', ['action' => 'edit', 'id' => $id]),
            get_string('edit'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );

        if (($pathway->status ?? '') !== 'active') {
            $actions[] = html_writer::link(
                new moodle_url('/local/uckk/pathways.php', [
                    'action' => 'activate',
                    'id' => $id,
                    'sesskey' => sesskey(),
                ]),
                local_uckk_pathways_get_string('activate', 'Activer'),
                ['class' => 'btn btn-sm btn-outline-success']
            );
        }

        if (($pathway->status ?? '') !== 'archived') {
            $actions[] = html_writer::link(
                new moodle_url('/local/uckk/pathways.php', [
                    'action' => 'archive',
                    'id' => $id,
                    'sesskey' => sesskey(),
                ]),
                local_uckk_pathways_get_string('archive', 'Archiver'),
                ['class' => 'btn btn-sm btn-outline-warning']
            );
        }
    }

    return html_writer::div(implode(' ', $actions), 'btn-group btn-group-sm');
}

/**
 * Render a pathway detail card.
 *
 * @param int $id Pathway id.
 * @param context_system $context System context.
 * @param bool $canmanage Whether the user can manage pathways.
 * @return string
 */
function local_uckk_pathways_render_detail(int $id, context_system $context, bool $canmanage): string {
    if ($id <= 0) {
        return '';
    }

    local_uckk_pathways_require_api();

    if (!method_exists(pathway_api::class, 'get_pathway')) {
        return html_writer::div(
            local_uckk_pathways_get_string('missingpathwaymethod', 'La méthode de lecture du parcours est absente.'),
            'alert alert-warning'
        );
    }

    $pathway = pathway_api::get_pathway($id, $context);

    $content = html_writer::tag('h3', format_string($pathway->fullname), ['class' => 'h4']);
    $content .= html_writer::tag('p', s($pathway->shortname), ['class' => 'text-muted']);

    if (!empty($pathway->description)) {
        $content .= html_writer::tag(
            'div',
            format_text($pathway->description, $pathway->descriptionformat ?? FORMAT_HTML),
            ['class' => 'mb-3']
        );
    }

    $content .= html_writer::div(
        local_uckk_pathways_get_string(
            'pathwayinternalnotice',
            'Ce parcours structure une reconnaissance interne UCKK : cours, compétences, badges, preuves, portfolio et archives.'
        ),
        'alert alert-light border',
        ['role' => 'note']
    );

    $content .= html_writer::link(
        local_uckk_pathways_url(['id' => null]),
        get_string('back'),
        ['class' => 'btn btn-secondary']
    );

    if ($canmanage) {
        $content .= ' ' . html_writer::link(
            new moodle_url('/local/uckk/pathways.php', ['action' => 'edit', 'id' => $id]),
            get_string('edit'),
            ['class' => 'btn btn-primary']
        );
    }

    return html_writer::div($content, 'card card-body mb-3 local-uckk-pathway-detail');
}

// Execute safe mutating actions before rendering.
local_uckk_pathways_handle_action($action, $id, $context, $canmanage);

// Render page.
echo $OUTPUT->header();

echo html_writer::start_div('local-uckk local-uckk-pathways');

echo html_writer::tag(
    'h2',
    local_uckk_pathways_get_string('pathways', 'Parcours UCKK'),
    ['class' => 'mb-2']
);

echo html_writer::tag(
    'p',
    local_uckk_pathways_get_string(
        'pathways_intro',
        'Les parcours UCKK relient programmes, cours, compétences, badges, preuves, portfolios et reconnaissances internes.'
    ),
    ['class' => 'lead']
);

echo html_writer::div(
    html_writer::tag(
        'strong',
        local_uckk_pathways_get_string('internalrecognitionnotice_title', 'Reconnaissance interne UCKK'),
        ['class' => 'd-block']
    )
    . local_uckk_pathways_get_string(
        'internalrecognitionnotice_text',
        'Les parcours UCKK ne constituent pas des diplômes publics accrédités. Ils structurent des apprentissages, preuves, badges, portfolios et attestations internes.'
    ),
    'alert alert-light border',
    ['role' => 'note']
);

echo local_uckk_pathways_render_actions($canmanage);

if ($action === 'create' || $action === 'edit') {
    require_capability($managecapability, $context);
    echo local_uckk_pathways_render_management_notice($action);
}

if ($id > 0 && $action === '') {
    echo local_uckk_pathways_render_detail($id, $context, $canmanage);
}

echo local_uckk_pathways_render_filters($context);

try {
    [$pathways, $total] = local_uckk_pathways_load(local_uckk_pathways_get_filters(), $page, $perpage, $context);

    echo html_writer::div(
        get_string('total') . ': ' . (int)$total,
        'small text-muted mb-2'
    );

    echo local_uckk_pathways_render_table($pathways, $canmanage);

    echo $OUTPUT->paging_bar(
        $total,
        $page,
        $perpage,
        local_uckk_pathways_url(['page' => null])
    );
} catch (Throwable $exception) {
    echo $OUTPUT->notification(
        s($exception->getMessage()),
        \core\output\notification::NOTIFY_ERROR
    );
}

echo html_writer::end_div();

echo $OUTPUT->footer();

