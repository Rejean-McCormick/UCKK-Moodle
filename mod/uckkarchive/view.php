<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main view page for a UCKK Archive activity.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/uckkarchive/lib.php');
require_once($CFG->dirroot . '/mod/uckkarchive/locallib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Return true when a table exists.
 *
 * @param string $tablename Moodle table name without braces.
 * @return bool
 */
function mod_uckkarchive_view_table_exists(string $tablename): bool {
    global $DB;

    $dbman = $DB->get_manager();
    return $dbman->table_exists($tablename);
}

/**
 * Return a field from an object using the first available candidate.
 *
 * @param stdClass $record Record.
 * @param string[] $fields Candidate fields.
 * @param mixed $default Default value.
 * @return mixed
 */
function mod_uckkarchive_view_first_field(stdClass $record, array $fields, mixed $default = null): mixed {
    foreach ($fields as $field) {
        if (property_exists($record, $field) && $record->{$field} !== null && $record->{$field} !== '') {
            return $record->{$field};
        }
    }

    return $default;
}

/**
 * Normalise archive item status for CSS/template output.
 *
 * @param string $status Raw status.
 * @return string
 */
function mod_uckkarchive_view_normalise_status(string $status): string {
    $status = clean_param($status, PARAM_ALPHANUMEXT);

    $allowed = [
        'draft',
        'submitted',
        'under_review',
        'validated',
        'published',
        'restricted',
        'contested',
        'invalidated',
        'superseded',
        'archived',
        'hidden',
    ];

    return in_array($status, $allowed, true) ? $status : 'draft';
}

/**
 * Normalise archive item visibility.
 *
 * @param string $visibility Raw visibility.
 * @return string
 */
function mod_uckkarchive_view_normalise_visibility(string $visibility): string {
    $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

    $allowed = [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'program',
        'institution',
        'institutional',
        'public',
        'restricted',
        'restricted_integrity',
        'hidden',
        'archived',
    ];

    return in_array($visibility, $allowed, true) ? $visibility : 'course';
}

/**
 * Return whether the current user may see one archive item.
 *
 * This controller keeps only the final UI gate here. Detailed archive visibility
 * decisions should also be enforced by service/output classes.
 *
 * @param stdClass $item Archive item.
 * @param context_module $context Module context.
 * @return bool
 */
function mod_uckkarchive_view_can_see_item(stdClass $item, context_module $context): bool {
    global $USER;

    $visibility = mod_uckkarchive_view_normalise_visibility((string)($item->visibility ?? 'course'));
    $status = mod_uckkarchive_view_normalise_status((string)($item->status ?? 'draft'));

    if ($status === 'hidden' && !has_capability('mod/uckkarchive:validateitem', $context)) {
        return false;
    }

    if ($visibility === 'private') {
        $ownerid = (int)($item->userid ?? $item->authorid ?? $item->createdby ?? 0);

        return $ownerid === (int)$USER->id
            || has_capability('mod/uckkarchive:validateitem', $context)
            || has_capability('mod/uckkarchive:viewrestricted', $context);
    }

    if (in_array($visibility, ['restricted', 'restricted_integrity'], true)) {
        return has_capability('mod/uckkarchive:viewrestricted', $context);
    }

    return true;
}

/**
 * Convert an archive item record into template data.
 *
 * @param stdClass $item Archive item record.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 * @return stdClass
 */
function mod_uckkarchive_view_export_item(stdClass $item, cm_info|stdClass $cm, context_module $context): stdClass {
    $status = mod_uckkarchive_view_normalise_status((string)($item->status ?? 'draft'));
    $visibility = mod_uckkarchive_view_normalise_visibility((string)($item->visibility ?? 'course'));
    $itemtype = clean_param((string)($item->itemtype ?? $item->type ?? 'proof'), PARAM_ALPHANUMEXT);

    $title = (string)mod_uckkarchive_view_first_field($item, ['title', 'name', 'subject'], get_string('archiveitem', 'uckkarchive'));
    $summary = (string)mod_uckkarchive_view_first_field($item, ['summary', 'publicsummary', 'description'], '');

    $data = new stdClass();
    $data->id = (int)($item->id ?? 0);
    $data->archiveid = (int)($item->archiveid ?? $item->uckkarchiveid ?? 0);
    $data->title = format_string($title);
    $data->summary = format_text($summary, FORMAT_HTML, ['context' => $context]);
    $data->hassummary = trim(strip_tags($summary)) !== '';
    $data->itemtype = $itemtype;
    $data->itemtypelabel = get_string_manager()->string_exists('itemtype:' . str_replace('_', '', $itemtype), 'uckkarchive')
        ? get_string('itemtype:' . str_replace('_', '', $itemtype), 'uckkarchive')
        : ucfirst(str_replace('_', ' ', $itemtype));
    $data->status = $status;
    $data->statuslabel = get_string_manager()->string_exists('status:' . str_replace('_', '', $status), 'uckkarchive')
        ? get_string('status:' . str_replace('_', '', $status), 'uckkarchive')
        : ucfirst(str_replace('_', ' ', $status));
    $data->statusclass = 'status-' . str_replace('_', '-', $status);
    $data->visibility = $visibility;
    $data->visibilitylabel = get_string_manager()->string_exists('visibility:' . str_replace('_', '', $visibility), 'uckkarchive')
        ? get_string('visibility:' . str_replace('_', '', $visibility), 'uckkarchive')
        : ucfirst(str_replace('_', ' ', $visibility));
    $data->url = (new moodle_url('/mod/uckkarchive/item.php', [
        'id' => $cm->id,
        'itemid' => $data->id,
    ]))->out(false);

    $data->timecreated = (int)($item->timecreated ?? 0);
    $data->timecreatedlabel = $data->timecreated > 0 ? userdate($data->timecreated) : '';
    $data->hastimecreated = $data->timecreated > 0;

    $data->timemodified = (int)($item->timemodified ?? 0);
    $data->timemodifiedlabel = $data->timemodified > 0 ? userdate($data->timemodified) : '';
    $data->hastimemodified = $data->timemodified > 0;

    $data->versionno = (int)($item->versionno ?? 1);
    $data->hasversion = $data->versionno > 1;
    $data->versionlabel = get_string('versionx', 'uckkarchive', $data->versionno);

    $data->hasprovenance = !empty($item->provenance)
        || !empty($item->origincomponent)
        || !empty($item->sourcecomponent)
        || !empty($item->provenancehash);

    $data->provenance = [
        'sourcecomponent' => (string)($item->sourcecomponent ?? $item->origincomponent ?? $item->provenance ?? ''),
        'sourceid' => (int)($item->sourceid ?? $item->originobjectid ?? 0),
        'hash' => (string)($item->provenancehash ?? ''),
    ];

    return $data;
}

/**
 * Load visible archive items for this activity.
 *
 * @param stdClass $archive Archive instance.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 * @param int $limit Maximum items.
 * @return stdClass[]
 */
function mod_uckkarchive_view_get_items(stdClass $archive, cm_info|stdClass $cm, context_module $context, int $limit = 50): array {
    global $DB;

    if (!mod_uckkarchive_view_table_exists('uckkarchive_item')) {
        return [];
    }

    $conditions = [];

    if ($DB->get_columns('uckkarchive_item') && array_key_exists('archiveid', $DB->get_columns('uckkarchive_item'))) {
        $conditions['archiveid'] = (int)$archive->id;
    } else if (array_key_exists('uckkarchiveid', $DB->get_columns('uckkarchive_item'))) {
        $conditions['uckkarchiveid'] = (int)$archive->id;
    }

    if (empty($conditions)) {
        return [];
    }

    $sort = 'timemodified DESC, id DESC';
    $items = $DB->get_records('uckkarchive_item', $conditions, $sort, '*', 0, $limit);

    $visible = [];

    foreach ($items as $item) {
        if (!mod_uckkarchive_view_can_see_item($item, $context)) {
            continue;
        }

        $visible[] = mod_uckkarchive_view_export_item($item, $cm, $context);
    }

    return $visible;
}

/**
 * Load visible Kristal rows for this archive activity.
 *
 * @param stdClass $archive Archive instance.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 * @param int $limit Maximum rows.
 * @return stdClass[]
 */
function mod_uckkarchive_view_get_kristals(stdClass $archive, cm_info|stdClass $cm, context_module $context, int $limit = 20): array {
    global $DB;

    if (!mod_uckkarchive_view_table_exists('uckkarchive_kristal')) {
        return [];
    }

    $columns = $DB->get_columns('uckkarchive_kristal');
    $conditions = [];

    if (array_key_exists('archiveid', $columns)) {
        $conditions['archiveid'] = (int)$archive->id;
    } else if (array_key_exists('uckkarchiveid', $columns)) {
        $conditions['uckkarchiveid'] = (int)$archive->id;
    }

    if (empty($conditions)) {
        return [];
    }

    $records = $DB->get_records('uckkarchive_kristal', $conditions, 'timemodified DESC, id DESC', '*', 0, $limit);
    $rows = [];

    foreach ($records as $record) {
        $status = mod_uckkarchive_view_normalise_status((string)($record->status ?? 'draft'));
        $visibility = mod_uckkarchive_view_normalise_visibility((string)($record->visibility ?? 'course'));

        if ($visibility === 'restricted_integrity' && !has_capability('mod/uckkarchive:viewrestricted', $context)) {
            continue;
        }

        $title = (string)mod_uckkarchive_view_first_field($record, ['title', 'name', 'concept'], get_string('kristal', 'uckkarchive'));
        $summary = (string)mod_uckkarchive_view_first_field($record, ['summary', 'description', 'body'], '');

        $row = new stdClass();
        $row->id = (int)$record->id;
        $row->title = format_string($title);
        $row->summary = format_text($summary, FORMAT_HTML, ['context' => $context]);
        $row->hassummary = trim(strip_tags($summary)) !== '';
        $row->status = $status;
        $row->statuslabel = get_string_manager()->string_exists('status:' . str_replace('_', '', $status), 'uckkarchive')
            ? get_string('status:' . str_replace('_', '', $status), 'uckkarchive')
            : ucfirst(str_replace('_', ' ', $status));
        $row->visibility = $visibility;
        $row->url = (new moodle_url('/mod/uckkarchive/item.php', [
            'id' => $cm->id,
            'kristalid' => $row->id,
        ]))->out(false);

        $rows[] = $row;
    }

    return $rows;
}

/**
 * Count archive items by status.
 *
 * @param stdClass[] $items Exported item rows.
 * @return stdClass[]
 */
function mod_uckkarchive_view_get_status_counts(array $items): array {
    $counts = [];

    foreach ($items as $item) {
        $key = (string)$item->status;

        if (!isset($counts[$key])) {
            $label = get_string_manager()->string_exists('status:' . str_replace('_', '', $key), 'uckkarchive')
                ? get_string('status:' . str_replace('_', '', $key), 'uckkarchive')
                : ucfirst(str_replace('_', ' ', $key));

            $counts[$key] = (object)[
                'status' => $key,
                'label' => $label,
                'count' => 0,
                'class' => 'status-' . str_replace('_', '-', $key),
            ];
        }

        $counts[$key]->count++;
    }

    return array_values($counts);
}

/**
 * Build archive view template context.
 *
 * @param stdClass $archive Archive instance.
 * @param stdClass $course Course.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 * @param string $tab Active tab.
 * @return stdClass
 */
function mod_uckkarchive_view_build_context(
    stdClass $archive,
    stdClass $course,
    cm_info|stdClass $cm,
    context_module $context,
    string $tab
): stdClass {
    global $USER;

    $items = mod_uckkarchive_view_get_items($archive, $cm, $context);
    $kristals = mod_uckkarchive_view_get_kristals($archive, $cm, $context);

    $intro = '';
    if (!empty($archive->intro)) {
        $intro = format_module_intro('uckkarchive', $archive, $cm->id);
    }

    $status = mod_uckkarchive_view_normalise_status((string)($archive->status ?? 'active'));
    $visibility = mod_uckkarchive_view_normalise_visibility((string)($archive->visibility ?? 'course'));

    $contextdata = new stdClass();
    $contextdata->uniqid = 'uckkarchive-view-' . $cm->id;
    $contextdata->id = (int)$archive->id;
    $contextdata->cmid = (int)$cm->id;
    $contextdata->courseid = (int)$course->id;
    $contextdata->contextid = (int)$context->id;
    $contextdata->title = format_string($archive->name);
    $contextdata->intro = $intro;
    $contextdata->hasintro = trim(strip_tags($intro)) !== '';

    $contextdata->status = $status;
    $contextdata->statuslabel = get_string_manager()->string_exists('status:' . str_replace('_', '', $status), 'uckkarchive')
        ? get_string('status:' . str_replace('_', '', $status), 'uckkarchive')
        : ucfirst(str_replace('_', ' ', $status));
    $contextdata->statusclass = 'status-' . str_replace('_', '-', $status);

    $contextdata->visibility = $visibility;
    $contextdata->visibilitylabel = get_string_manager()->string_exists('visibility:' . str_replace('_', '', $visibility), 'uckkarchive')
        ? get_string('visibility:' . str_replace('_', '', $visibility), 'uckkarchive')
        : ucfirst(str_replace('_', ' ', $visibility));

    $contextdata->activeitems = $tab === 'items';
    $contextdata->activekristals = $tab === 'kristals';
    $contextdata->activeprovenance = $tab === 'provenance';
    $contextdata->activevalidation = $tab === 'validation';
    $contextdata->activeexports = $tab === 'exports';

    $contextdata->items = $items;
    $contextdata->hasitems = !empty($items);
    $contextdata->itemcount = count($items);

    $contextdata->kristals = $kristals;
    $contextdata->haskristals = !empty($kristals);
    $contextdata->kristalcount = count($kristals);

    $contextdata->statuscounts = mod_uckkarchive_view_get_status_counts($items);
    $contextdata->hasstatuscounts = !empty($contextdata->statuscounts);

    $contextdata->canadditem = has_capability('mod/uckkarchive:additem', $context);
    $contextdata->canvalidate = has_capability('mod/uckkarchive:validateitem', $context);
    $contextdata->canrevise = has_capability('mod/uckkarchive:reviseitem', $context);
    $contextdata->canviewrestricted = has_capability('mod/uckkarchive:viewrestricted', $context);
    $contextdata->canexport = has_capability('mod/uckkarchive:export', $context);

    $contextdata->additemurl = (new moodle_url('/mod/uckkarchive/add.php', ['id' => $cm->id]))->out(false);
    $contextdata->validateurl = (new moodle_url('/mod/uckkarchive/validate.php', ['id' => $cm->id]))->out(false);
    $contextdata->exporturl = (new moodle_url('/mod/uckkarchive/export.php', ['id' => $cm->id]))->out(false);

    $contextdata->actions = [];
    if ($contextdata->canadditem) {
        $contextdata->actions[] = (object)[
            'key' => 'additem',
            'label' => get_string('addarchiveitem', 'uckkarchive'),
            'url' => $contextdata->additemurl,
            'primary' => true,
            'secondary' => false,
            'danger' => false,
        ];
    }

    if ($contextdata->canvalidate) {
        $contextdata->actions[] = (object)[
            'key' => 'validate',
            'label' => get_string('validatearchiveitems', 'uckkarchive'),
            'url' => $contextdata->validateurl,
            'primary' => false,
            'secondary' => true,
            'danger' => false,
        ];
    }

    if ($contextdata->canexport) {
        $contextdata->actions[] = (object)[
            'key' => 'export',
            'label' => get_string('exportarchive', 'uckkarchive'),
            'url' => $contextdata->exporturl,
            'primary' => false,
            'secondary' => true,
            'danger' => false,
        ];
    }

    $contextdata->hasactions = !empty($contextdata->actions);

    $contextdata->provenance = (object)[
        'component' => 'mod_uckkarchive',
        'objectid' => (int)$archive->id,
        'contextid' => (int)$context->id,
        'hasitemswithprovenance' => !empty(array_filter($items, static fn(stdClass $item): bool => !empty($item->hasprovenance))),
    ];

    $contextdata->validation = (object)[
        'pendingcount' => count(array_filter($items, static fn(stdClass $item): bool => in_array($item->status, ['submitted', 'under_review'], true))),
        'validatedcount' => count(array_filter($items, static fn(stdClass $item): bool => in_array($item->status, ['validated', 'published', 'archived'], true))),
        'contestedcount' => count(array_filter($items, static fn(stdClass $item): bool => $item->status === 'contested')),
    ];

    $contextdata->exports = (object)[
        'canexport' => $contextdata->canexport,
        'exporturl' => $contextdata->exporturl,
    ];

    $contextdata->notice = get_string('archivememorynotice', 'uckkarchive');
    $contextdata->restrictednotice = $contextdata->canviewrestricted
        ? get_string('restrictedvisibletoyou', 'uckkarchive')
        : '';

    $contextdata->userid = (int)$USER->id;

    return $contextdata;
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$u = optional_param('u', 0, PARAM_INT); // Archive instance id.
$tab = optional_param('tab', 'items', PARAM_ALPHAEXT);

if ($id) {
    $cm = get_coursemodule_from_id('uckkarchive', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $archive = $DB->get_record('uckkarchive', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($u) {
    $archive = $DB->get_record('uckkarchive', ['id' => $u], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkarchive:view', $context);

$allowedtabs = [
    'items',
    'kristals',
    'provenance',
    'validation',
    'exports',
];

if (!in_array($tab, $allowedtabs, true)) {
    throw new moodle_exception('invalidarchivetab', 'uckkarchive');
}

$pageurl = new moodle_url('/mod/uckkarchive/view.php', [
    'id' => $cm->id,
    'tab' => $tab,
]);

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($archive->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Trigger a view event only if the event class exists in this build.
if (class_exists('\mod_uckkarchive\event\archive_viewed')) {
    $event = \mod_uckkarchive\event\archive_viewed::create([
        'objectid' => (int)$archive->id,
        'context' => $context,
        'other' => [
            'courseid' => (int)$course->id,
            'cmid' => (int)$cm->id,
        ],
    ]);

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('uckkarchive', $archive);
    $event->trigger();
}

$templatecontext = mod_uckkarchive_view_build_context($archive, $course, $cm, $context, $tab);

echo $OUTPUT->header();

if (class_exists('\mod_uckkarchive\output\archive_view')) {
    $renderable = new \mod_uckkarchive\output\archive_view($templatecontext);
    echo $OUTPUT->render($renderable);
} else {
    echo $OUTPUT->render_from_template('mod_uckkarchive/archive_view', $templatecontext);
}

echo $OUTPUT->footer();