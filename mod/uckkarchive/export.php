<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Export controller for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use core\output\notification;

/**
 * Allowed archive export formats.
 *
 * @return string[]
 */
function uckkarchive_export_allowed_formats(): array {
    return [
        'json',
        'html',
        'csv',
    ];
}

/**
 * Allowed archive export scopes.
 *
 * @return string[]
 */
function uckkarchive_export_allowed_scopes(): array {
    return [
        'archive',
        'item',
        'portfolio',
    ];
}

/**
 * Allowed archive export visibilities.
 *
 * @return string[]
 */
function uckkarchive_export_allowed_visibilities(): array {
    return [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'program',
        'institution',
        'public',
        'restricted',
        'restricted_integrity',
    ];
}

/**
 * Normalise one key against an allow-list.
 *
 * @param string $value Raw value.
 * @param string[] $allowed Allowed values.
 * @param string $default Default value.
 * @return string
 */
function uckkarchive_export_normalise_choice(string $value, array $allowed, string $default): string {
    $value = clean_param($value, PARAM_ALPHANUMEXT);

    return in_array($value, $allowed, true) ? $value : $default;
}

/**
 * Return whether a visibility is restricted.
 *
 * @param string $visibility Visibility key.
 * @return bool
 */
function uckkarchive_export_visibility_is_restricted(string $visibility): bool {
    return in_array($visibility, ['restricted', 'restricted_integrity', 'private'], true);
}

/**
 * Require restricted archive capability when needed.
 *
 * @param string $visibility Visibility key.
 * @param context_module $context Module context.
 */
function uckkarchive_export_require_visibility_capability(string $visibility, context_module $context): void {
    if (uckkarchive_export_visibility_is_restricted($visibility)) {
        require_capability('mod/uckkarchive:viewrestricted', $context);
    }
}

/**
 * Fetch one archive item with visibility protection.
 *
 * @param int $itemid Archive item id.
 * @param stdClass $archive Archive instance.
 * @param context_module $context Module context.
 * @return stdClass
 */
function uckkarchive_export_get_item(int $itemid, stdClass $archive, context_module $context): stdClass {
    global $DB;

    $item = $DB->get_record('uckkarchive_item', [
        'id' => $itemid,
        'archiveid' => $archive->id,
    ], '*', MUST_EXIST);

    uckkarchive_export_require_visibility_capability((string)($item->visibility ?? 'course'), $context);

    return $item;
}

/**
 * Fetch exportable archive items.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Module context.
 * @param int $itemid Optional single item id.
 * @param bool $includerestricted Whether restricted data is included.
 * @return stdClass[]
 */
function uckkarchive_export_get_items(
    stdClass $archive,
    context_module $context,
    int $itemid = 0,
    bool $includerestricted = false
): array {
    global $DB;

    if ($itemid > 0) {
        return [uckkarchive_export_get_item($itemid, $archive, $context)];
    }

    $items = $DB->get_records('uckkarchive_item', [
        'archiveid' => $archive->id,
    ], 'timecreated ASC, id ASC');

    $exportable = [];

    foreach ($items as $item) {
        $visibility = (string)($item->visibility ?? 'course');

        if (uckkarchive_export_visibility_is_restricted($visibility) && !$includerestricted) {
            continue;
        }

        if (uckkarchive_export_visibility_is_restricted($visibility)) {
            require_capability('mod/uckkarchive:viewrestricted', $context);
        }

        $exportable[] = $item;
    }

    return $exportable;
}

/**
 * Fetch provenance records for an item.
 *
 * @param int $itemid Archive item id.
 * @return stdClass[]
 */
function uckkarchive_export_get_provenance(int $itemid): array {
    global $DB;

    if (!$DB->get_manager()->table_exists('uckkarchive_prov')) {
        return [];
    }

    return array_values($DB->get_records('uckkarchive_prov', [
        'itemid' => $itemid,
    ], 'timecreated ASC, id ASC'));
}

/**
 * Fetch revision records for an item.
 *
 * @param int $itemid Archive item id.
 * @return stdClass[]
 */
function uckkarchive_export_get_revisions(int $itemid): array {
    global $DB;

    if (!$DB->get_manager()->table_exists('uckkarchive_rev')) {
        return [];
    }

    return array_values($DB->get_records('uckkarchive_rev', [
        'itemid' => $itemid,
    ], 'timecreated ASC, id ASC'));
}

/**
 * Fetch proof records for an item.
 *
 * @param int $itemid Archive item id.
 * @return stdClass[]
 */
function uckkarchive_export_get_proofs(int $itemid): array {
    global $DB;

    if (!$DB->get_manager()->table_exists('uckkarchive_proof')) {
        return [];
    }

    return array_values($DB->get_records('uckkarchive_proof', [
        'itemid' => $itemid,
    ], 'timecreated ASC, id ASC'));
}

/**
 * Convert a database record to a clean export array.
 *
 * @param stdClass $record Record.
 * @param bool $includemetadata Whether to include metadata JSON.
 * @return array<string, mixed>
 */
function uckkarchive_export_record_to_array(stdClass $record, bool $includemetadata = true): array {
    $data = [];

    foreach ((array)$record as $key => $value) {
        if (!$includemetadata && $key === 'metadata') {
            continue;
        }

        if ($key === 'metadata' && is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $data[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            continue;
        }

        $data[$key] = $value;
    }

    return $data;
}

/**
 * Build export package data.
 *
 * @param stdClass $archive Archive instance.
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module record.
 * @param context_module $context Module context.
 * @param array<string, mixed> $options Export options.
 * @return array<string, mixed>
 */
function uckkarchive_export_build_package(
    stdClass $archive,
    stdClass $course,
    $cm,
    context_module $context,
    array $options
): array {
    $itemid = (int)($options['itemid'] ?? 0);
    $includerestricted = !empty($options['includerestricted']);
    $includeprovenance = !empty($options['includeprovenance']);
    $includerevisions = !empty($options['includerevisions']);
    $includeproofs = !empty($options['includeproofs']);
    $includemetadata = !empty($options['includemetadata']);

    $items = uckkarchive_export_get_items($archive, $context, $itemid, $includerestricted);

    $exportitems = [];

    foreach ($items as $item) {
        $visibility = (string)($item->visibility ?? 'course');
        uckkarchive_export_require_visibility_capability($visibility, $context);

        $exportitem = uckkarchive_export_record_to_array($item, $includemetadata);

        if ($includeprovenance) {
            $exportitem['provenance_records'] = array_map(
                static fn(stdClass $record): array => uckkarchive_export_record_to_array($record, $includemetadata),
                uckkarchive_export_get_provenance((int)$item->id)
            );
        }

        if ($includerevisions) {
            $exportitem['revision_records'] = array_map(
                static fn(stdClass $record): array => uckkarchive_export_record_to_array($record, $includemetadata),
                uckkarchive_export_get_revisions((int)$item->id)
            );
        }

        if ($includeproofs) {
            $exportitem['proof_records'] = array_map(
                static fn(stdClass $record): array => uckkarchive_export_record_to_array($record, $includemetadata),
                uckkarchive_export_get_proofs((int)$item->id)
            );
        }

        $exportitems[] = $exportitem;
    }

    return [
        'schema' => 'uckkarchive_export_v1',
        'component' => 'mod_uckkarchive',
        'product' => 'UCKK-Moodle',
        'generated_at' => time(),
        'generated_at_label' => userdate(time()),
        'course' => [
            'id' => (int)$course->id,
            'shortname' => $course->shortname,
            'fullname' => format_string($course->fullname),
        ],
        'archive' => [
            'id' => (int)$archive->id,
            'cmid' => (int)$cm->id,
            'name' => format_string($archive->name),
            'status' => $archive->status ?? 'active',
            'visibility' => $archive->visibility ?? 'course',
        ],
        'options' => [
            'scope' => $options['scope'],
            'format' => $options['format'],
            'visibility' => $options['visibility'],
            'itemid' => $itemid,
            'includeprovenance' => $includeprovenance,
            'includerevisions' => $includerevisions,
            'includeproofs' => $includeproofs,
            'includemetadata' => $includemetadata,
            'includerestricted' => $includerestricted,
        ],
        'item_count' => count($exportitems),
        'items' => $exportitems,
    ];
}

/**
 * Encode export package as JSON.
 *
 * @param array<string, mixed> $package Export package.
 * @return string
 */
function uckkarchive_export_as_json(array $package): string {
    return json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Encode export package as HTML.
 *
 * @param array<string, mixed> $package Export package.
 * @return string
 */
function uckkarchive_export_as_html(array $package): string {
    $html = '<!doctype html>';
    $html .= '<html lang="en">';
    $html .= '<head>';
    $html .= '<meta charset="utf-8">';
    $html .= '<title>' . s($package['archive']['name']) . '</title>';
    $html .= '</head>';
    $html .= '<body>';
    $html .= '<h1>' . s($package['archive']['name']) . '</h1>';
    $html .= '<p><strong>' . s(get_string('generatedat', 'uckkarchive')) . ':</strong> ' . s($package['generated_at_label']) . '</p>';
    $html .= '<p><strong>' . s(get_string('course')) . ':</strong> ' . s($package['course']['fullname']) . '</p>';
    $html .= '<p><strong>' . s(get_string('itemcount', 'uckkarchive')) . ':</strong> ' . (int)$package['item_count'] . '</p>';

    foreach ($package['items'] as $item) {
        $html .= '<article>';
        $html .= '<h2>' . s($item['title'] ?? $item['name'] ?? get_string('archiveitem', 'uckkarchive')) . '</h2>';

        if (!empty($item['summary'])) {
            $html .= '<p>' . s($item['summary']) . '</p>';
        }

        $html .= '<dl>';

        foreach (['status', 'visibility', 'validationstate', 'provenance', 'timecreated', 'timemodified'] as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                $value = in_array($key, ['timecreated', 'timemodified'], true) ? userdate((int)$item[$key]) : (string)$item[$key];
                $html .= '<dt>' . s($key) . '</dt><dd>' . s($value) . '</dd>';
            }
        }

        $html .= '</dl>';

        if (!empty($item['provenance_records'])) {
            $html .= '<h3>' . s(get_string('provenance', 'uckkarchive')) . '</h3><ul>';
            foreach ($item['provenance_records'] as $record) {
                $html .= '<li>' . s($record['summary'] ?? $record['source'] ?? json_encode($record)) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($item['revision_records'])) {
            $html .= '<h3>' . s(get_string('revisions', 'uckkarchive')) . '</h3><ul>';
            foreach ($item['revision_records'] as $record) {
                $html .= '<li>' . s($record['summary'] ?? $record['status'] ?? json_encode($record)) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($item['proof_records'])) {
            $html .= '<h3>' . s(get_string('proofs', 'uckkarchive')) . '</h3><ul>';
            foreach ($item['proof_records'] as $record) {
                $html .= '<li>' . s($record['summary'] ?? $record['prooftype'] ?? json_encode($record)) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</article>';
    }

    $html .= '</body>';
    $html .= '</html>';

    return $html;
}

/**
 * Encode export package as CSV.
 *
 * @param array<string, mixed> $package Export package.
 * @return string
 */
function uckkarchive_export_as_csv(array $package): string {
    $handle = fopen('php://temp', 'r+');

    fputcsv($handle, [
        'id',
        'title',
        'status',
        'visibility',
        'validationstate',
        'provenance',
        'timecreated',
        'timemodified',
    ]);

    foreach ($package['items'] as $item) {
        fputcsv($handle, [
            $item['id'] ?? '',
            $item['title'] ?? $item['name'] ?? '',
            $item['status'] ?? '',
            $item['visibility'] ?? '',
            $item['validationstate'] ?? '',
            $item['provenance'] ?? '',
            !empty($item['timecreated']) ? userdate((int)$item['timecreated']) : '',
            !empty($item['timemodified']) ? userdate((int)$item['timemodified']) : '',
        ]);
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv === false ? '' : $csv;
}

/**
 * Render an export package according to format.
 *
 * @param array<string, mixed> $package Export package.
 * @param string $format Export format.
 * @return string
 */
function uckkarchive_export_render_package(array $package, string $format): string {
    return match ($format) {
        'html' => uckkarchive_export_as_html($package),
        'csv' => uckkarchive_export_as_csv($package),
        default => uckkarchive_export_as_json($package),
    };
}

/**
 * Return download MIME type for export format.
 *
 * @param string $format Export format.
 * @return string
 */
function uckkarchive_export_mimetype(string $format): string {
    return match ($format) {
        'html' => 'text/html; charset=utf-8',
        'csv' => 'text/csv; charset=utf-8',
        default => 'application/json; charset=utf-8',
    };
}

/**
 * Return file extension for export format.
 *
 * @param string $format Export format.
 * @return string
 */
function uckkarchive_export_extension(string $format): string {
    return match ($format) {
        'html' => 'html',
        'csv' => 'csv',
        default => 'json',
    };
}

/**
 * Build a safe export filename.
 *
 * @param stdClass $archive Archive record.
 * @param string $format Export format.
 * @param int $itemid Optional item id.
 * @return string
 */
function uckkarchive_export_filename(stdClass $archive, string $format, int $itemid = 0): string {
    $base = clean_filename('uckkarchive-' . $archive->id . ($itemid ? '-item-' . $itemid : ''));

    return $base . '-' . date('Ymd-His') . '.' . uckkarchive_export_extension($format);
}

/**
 * Save export audit record if the export table exists.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Module context.
 * @param array<string, mixed> $options Export options.
 * @param string $filename Filename.
 * @param string $payload Export payload.
 * @return int Export record id, or 0 when table unavailable.
 */
function uckkarchive_export_record_export(
    stdClass $archive,
    context_module $context,
    array $options,
    string $filename,
    string $payload
): int {
    global $DB, $USER;

    if (!$DB->get_manager()->table_exists('uckkarchive_export')) {
        return 0;
    }

    $now = time();

    $record = (object)[
        'archiveid' => (int)$archive->id,
        'itemid' => (int)($options['itemid'] ?? 0) ?: null,
        'courseid' => (int)$archive->course,
        'cmid' => (int)($options['cmid'] ?? 0),
        'contextid' => $context->id,
        'userid' => $USER->id,
        'exportformat' => $options['format'],
        'scope' => $options['scope'],
        'filename' => $filename,
        'filesize' => strlen($payload),
        'contenthash' => sha1($payload),
        'status' => 'validated',
        'visibility' => $options['visibility'],
        'provenance' => 'system',
        'createdby' => $USER->id,
        'modifiedby' => $USER->id,
        'timecreated' => $now,
        'timemodified' => $now,
        'versionno' => 1,
        'metadata' => json_encode([
            'includeprovenance' => !empty($options['includeprovenance']),
            'includerevisions' => !empty($options['includerevisions']),
            'includeproofs' => !empty($options['includeproofs']),
            'includemetadata' => !empty($options['includemetadata']),
            'includerestricted' => !empty($options['includerestricted']),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    return (int)$DB->insert_record('uckkarchive_export', $record);
}

/**
 * Trigger archive item exported event when available.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Module context.
 * @param int $itemid Optional item id.
 * @param int $exportid Optional export record id.
 * @param array<string, mixed> $options Export options.
 */
function uckkarchive_export_trigger_event(
    stdClass $archive,
    context_module $context,
    int $itemid,
    int $exportid,
    array $options
): void {
    $eventclass = '\mod_uckkarchive\event\archive_item_exported';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'objectid' => $itemid > 0 ? $itemid : (int)$archive->id,
        'context' => $context,
        'other' => [
            'archiveid' => (int)$archive->id,
            'exportid' => $exportid,
            'scope' => $options['scope'],
            'format' => $options['format'],
            'visibility' => $options['visibility'],
        ],
    ]);

    $event->add_record_snapshot('uckkarchive', $archive);
    $event->trigger();
}

/**
 * Send export payload to browser.
 *
 * @param string $filename Filename.
 * @param string $format Export format.
 * @param string $payload Export payload.
 */
function uckkarchive_export_send_file(string $filename, string $format, string $payload): void {
    @header('Content-Type: ' . uckkarchive_export_mimetype($format));
    @header('Content-Disposition: attachment; filename="' . $filename . '"');
    @header('Content-Length: ' . strlen($payload));
    @header('Cache-Control: private, max-age=0, must-revalidate');
    @header('Pragma: public');

    echo $payload;
    die;
}

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$a = optional_param('a', 0, PARAM_INT); // Archive instance id.
$itemid = optional_param('itemid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$scope = optional_param('scope', $itemid > 0 ? 'item' : 'archive', PARAM_ALPHAEXT);
$format = optional_param('format', 'json', PARAM_ALPHAEXT);
$visibility = optional_param('visibility', 'course', PARAM_ALPHAEXT);

$includeprovenance = optional_param('includeprovenance', 1, PARAM_BOOL);
$includerevisions = optional_param('includerevisions', 1, PARAM_BOOL);
$includeproofs = optional_param('includeproofs', 1, PARAM_BOOL);
$includemetadata = optional_param('includemetadata', 1, PARAM_BOOL);
$includerestricted = optional_param('includerestricted', 0, PARAM_BOOL);

if ($id) {
    $cm = get_coursemodule_from_id('uckkarchive', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $archive = $DB->get_record('uckkarchive', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $archive = $DB->get_record('uckkarchive', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkarchive:view', $context);
require_capability('mod/uckkarchive:export', $context);

$scope = uckkarchive_export_normalise_choice($scope, uckkarchive_export_allowed_scopes(), 'archive');
$format = uckkarchive_export_normalise_choice($format, uckkarchive_export_allowed_formats(), 'json');
$visibility = uckkarchive_export_normalise_choice($visibility, uckkarchive_export_allowed_visibilities(), 'course');

if ($includerestricted || uckkarchive_export_visibility_is_restricted($visibility)) {
    require_capability('mod/uckkarchive:viewrestricted', $context);
}

if ($itemid > 0) {
    uckkarchive_export_get_item($itemid, $archive, $context);
}

$viewurl = new moodle_url('/mod/uckkarchive/view.php', ['id' => $cm->id]);
$pageurl = new moodle_url('/mod/uckkarchive/export.php', [
    'id' => $cm->id,
    'itemid' => $itemid,
]);

$return = $returnurl !== '' ? new moodle_url($returnurl) : $viewurl;

$PAGE->set_url($pageurl);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_title(format_string($archive->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$allowedactions = [
    '',
    'download',
];

if (!in_array($action, $allowedactions, true)) {
    throw new moodle_exception('invalidexportaction', 'uckkarchive');
}

$options = [
    'cmid' => (int)$cm->id,
    'itemid' => $itemid,
    'scope' => $scope,
    'format' => $format,
    'visibility' => $visibility,
    'includeprovenance' => $includeprovenance,
    'includerevisions' => $includerevisions,
    'includeproofs' => $includeproofs,
    'includemetadata' => $includemetadata,
    'includerestricted' => $includerestricted,
];

if ($action === 'download') {
    require_sesskey();

    $package = uckkarchive_export_build_package($archive, $course, $cm, $context, $options);

    if (empty($package['items'])) {
        redirect(
            $pageurl,
            get_string('exportempty', 'uckkarchive'),
            null,
            notification::NOTIFY_WARNING
        );
    }

    $payload = uckkarchive_export_render_package($package, $format);
    $filename = uckkarchive_export_filename($archive, $format, $itemid);
    $exportid = uckkarchive_export_record_export($archive, $context, $options, $filename, $payload);

    uckkarchive_export_trigger_event($archive, $context, $itemid, $exportid, $options);
    uckkarchive_export_send_file($filename, $format, $payload);
}

$preview = uckkarchive_export_build_package($archive, $course, $cm, $context, $options);
$itemcount = count($preview['items']);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('exportarchive', 'uckkarchive'));

echo html_writer::start_div('uckkarchive-export');

echo html_writer::start_div('uckkarchive-export__summary card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h3', get_string('exportsummary', 'uckkarchive'), [
    'class' => 'card-title',
]);

echo html_writer::start_tag('dl', ['class' => 'uckkarchive-export__facts']);

echo html_writer::tag('dt', get_string('archive', 'uckkarchive'));
echo html_writer::tag('dd', format_string($archive->name));

echo html_writer::tag('dt', get_string('course'));
echo html_writer::tag('dd', format_string($course->fullname));

echo html_writer::tag('dt', get_string('scope', 'uckkarchive'));
echo html_writer::tag('dd', get_string('exportscope:' . $scope, 'uckkarchive'));

echo html_writer::tag('dt', get_string('format', 'uckkarchive'));
echo html_writer::tag('dd', get_string('exportformat:' . $format, 'uckkarchive'));

echo html_writer::tag('dt', get_string('visibility', 'uckkarchive'));
echo html_writer::tag('dd', get_string('visibility:' . $visibility, 'uckkarchive'));

echo html_writer::tag('dt', get_string('itemcount', 'uckkarchive'));
echo html_writer::tag('dd', (string)$itemcount);

echo html_writer::end_tag('dl');

echo html_writer::end_div();
echo html_writer::end_div();

if ($itemcount === 0) {
    echo $OUTPUT->notification(get_string('exportempty', 'uckkarchive'), notification::NOTIFY_WARNING);
}

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'uckkarchive-export__options card mb-3',
]);

echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('exportoptions', 'uckkarchive'), ['class' => 'card-title']);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);

if ($itemid > 0) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'itemid', 'value' => $itemid]);
}

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('scope', 'uckkarchive'), ['for' => 'id_scope']);
echo html_writer::select([
    'archive' => get_string('exportscope:archive', 'uckkarchive'),
    'item' => get_string('exportscope:item', 'uckkarchive'),
    'portfolio' => get_string('exportscope:portfolio', 'uckkarchive'),
], 'scope', $scope, false, [
    'id' => 'id_scope',
    'class' => 'custom-select form-control',
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('format', 'uckkarchive'), ['for' => 'id_format']);
echo html_writer::select([
    'json' => get_string('exportformat:json', 'uckkarchive'),
    'html' => get_string('exportformat:html', 'uckkarchive'),
    'csv' => get_string('exportformat:csv', 'uckkarchive'),
], 'format', $format, false, [
    'id' => 'id_format',
    'class' => 'custom-select form-control',
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('visibility', 'uckkarchive'), ['for' => 'id_visibility']);
echo html_writer::select([
    'private' => get_string('visibility:private', 'uckkarchive'),
    'user' => get_string('visibility:user', 'uckkarchive'),
    'group' => get_string('visibility:group', 'uckkarchive'),
    'course' => get_string('visibility:course', 'uckkarchive'),
    'cohort' => get_string('visibility:cohort', 'uckkarchive'),
    'program' => get_string('visibility:program', 'uckkarchive'),
    'institution' => get_string('visibility:institution', 'uckkarchive'),
    'public' => get_string('visibility:public', 'uckkarchive'),
    'restricted' => get_string('visibility:restricted', 'uckkarchive'),
    'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkarchive'),
], 'visibility', $visibility, false, [
    'id' => 'id_visibility',
    'class' => 'custom-select form-control',
]);
echo html_writer::end_div();

$checkboxes = [
    'includeprovenance' => $includeprovenance,
    'includerevisions' => $includerevisions,
    'includeproofs' => $includeproofs,
    'includemetadata' => $includemetadata,
];

if (has_capability('mod/uckkarchive:viewrestricted', $context)) {
    $checkboxes['includerestricted'] = $includerestricted;
}

foreach ($checkboxes as $name => $checked) {
    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'id_' . $name,
        'name' => $name,
        'value' => 1,
        'class' => 'form-check-input',
        'checked' => $checked ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string($name, 'uckkarchive'), [
        'for' => 'id_' . $name,
        'class' => 'form-check-label',
    ]);
    echo html_writer::end_div();
}

echo html_writer::tag('button', get_string('updatepreview', 'uckkarchive'), [
    'type' => 'submit',
    'class' => 'btn btn-secondary mt-3',
]);

echo html_writer::end_div();
echo html_writer::end_tag('form');

if (!empty($preview['items'])) {
    echo html_writer::start_div('uckkarchive-export__preview card mb-3');
    echo html_writer::start_div('card-body');

    echo html_writer::tag('h3', get_string('exportpreview', 'uckkarchive'), [
        'class' => 'card-title',
    ]);

    echo html_writer::start_tag('ul', ['class' => 'uckkarchive-export__item-list']);

    foreach ($preview['items'] as $item) {
        echo html_writer::start_tag('li', ['class' => 'uckkarchive-export__item']);
        echo html_writer::tag('strong', s($item['title'] ?? $item['name'] ?? get_string('archiveitem', 'uckkarchive')));

        if (!empty($item['status'])) {
            echo html_writer::span(' — ' . s($item['status']), 'uckkarchive-export__item-status');
        }

        if (!empty($item['visibility'])) {
            echo html_writer::span(' — ' . s($item['visibility']), 'uckkarchive-export__item-visibility');
        }

        if (!empty($item['summary'])) {
            echo html_writer::tag('p', s($item['summary']));
        }

        echo html_writer::end_tag('li');
    }

    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'uckkarchive-export__download',
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'download']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'scope', 'value' => $scope]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'format', 'value' => $format]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'visibility', 'value' => $visibility]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'itemid', 'value' => $itemid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'includeprovenance', 'value' => $includeprovenance ? 1 : 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'includerevisions', 'value' => $includerevisions ? 1 : 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'includeproofs', 'value' => $includeproofs ? 1 : 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'includemetadata', 'value' => $includemetadata ? 1 : 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'includerestricted', 'value' => $includerestricted ? 1 : 0]);

echo html_writer::tag('button', get_string('downloadexport', 'uckkarchive'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'disabled' => $itemcount === 0 ? 'disabled' : null,
]);

echo html_writer::link($return, get_string('cancel'), [
    'class' => 'btn btn-secondary ml-2',
]);

echo html_writer::end_tag('form');

echo html_writer::div(
    get_string('exportgovernancenotice', 'uckkarchive'),
    'alert alert-info uckkarchive-export__notice mt-3',
    ['role' => 'status']
);

echo html_writer::end_div();

echo $OUTPUT->footer();