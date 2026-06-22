<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Public faculty page controller for local_uckk.
 *
 * This controller is intentionally thin:
 * - it accepts only the canonical faculty slug;
 * - it never accepts a file path from the request;
 * - it resolves the slug through the faculty manifest registry;
 * - it applies public/restricted/hidden access rules;
 * - it delegates page construction to faculty_page_builder;
 * - it renders through the faculty_page output object and Mustache template;
 * - it never writes Atlas, Faculty JSON, Moodle courses, grades, completions or enrolments.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $PAGE, $SITE;

use local_uckk\local\faculty\faculty_page_builder;
use local_uckk\local\faculty\faculty_registry;
use local_uckk\output\faculty_page;

/**
 * Reject a bad public faculty request without leaking filesystem details.
 *
 * @return never
 */
function local_uckk_faculty_not_found(): void {
    http_response_code(404);
    throw new moodle_exception('invalidparameter', 'error', '', 'slug');
}

/**
 * Validate the public slug format before it reaches any repository.
 *
 * Canonical slugs are lowercase ASCII identifiers separated by hyphens.
 *
 * @param string $slug Raw slug.
 * @return string Safe canonical slug.
 */
function local_uckk_faculty_require_safe_slug(string $slug): string {
    $slug = trim(\core_text::strtolower($slug));

    if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        local_uckk_faculty_not_found();
    }

    return $slug;
}

/**
 * Resolve the best public page title from built page data.
 *
 * @param array<string, mixed> $pagedata Built page data.
 * @param array<string, mixed> $manifestitem Faculty manifest item.
 * @return string
 */
function local_uckk_faculty_page_title(array $pagedata, array $manifestitem): string {
    if (!empty($pagedata['page']) && is_array($pagedata['page']) && !empty($pagedata['page']['seo_title'])) {
        return (string)$pagedata['page']['seo_title'];
    }

    if (!empty($pagedata['seo']) && is_array($pagedata['seo']) && !empty($pagedata['seo']['title'])) {
        return (string)$pagedata['seo']['title'];
    }

    if (!empty($pagedata['identity']) && is_array($pagedata['identity']) && !empty($pagedata['identity']['name'])) {
        return (string)$pagedata['identity']['name'];
    }

    if (!empty($pagedata['hero']) && is_array($pagedata['hero']) && !empty($pagedata['hero']['title'])) {
        return (string)$pagedata['hero']['title'];
    }

    if (!empty($pagedata['title'])) {
        return (string)$pagedata['title'];
    }

    if (!empty($manifestitem['slug'])) {
        return (string)$manifestitem['slug'];
    }

    return get_string('pluginname', 'local_uckk');
}

/**
 * Apply access rules documented for public faculty pages.
 *
 * Public published pages are anonymous-readable.
 * Restricted pages require login and the public faculty capability.
 * Hidden, draft or archived pages require faculty profile management capability.
 *
 * @param context_system $context System context.
 * @param array<string, mixed> $manifestitem Faculty manifest item.
 */
function local_uckk_faculty_require_access(context_system $context, array $manifestitem): void {
    $status = (string)($manifestitem['status'] ?? 'draft');
    $visibility = (string)($manifestitem['visibility'] ?? 'hidden');

    if ($status === 'published' && $visibility === 'public') {
        return;
    }

    require_login();

    if ($status === 'published' && $visibility === 'restricted') {
        require_capability('local/uckk:viewpublicfaculties', $context);
        return;
    }

    require_capability('local/uckk:managefacultyprofiles', $context);
}

/**
 * Trigger the public faculty viewed event when the event class is available.
 *
 * The event payload intentionally contains only identifiers and status metadata.
 *
 * @param context_system $context System context.
 * @param string $slug Faculty slug.
 * @param array<string, mixed> $manifestitem Faculty manifest item.
 */
function local_uckk_faculty_trigger_view_event(
    context_system $context,
    string $slug,
    array $manifestitem
): void {
    $eventclass = '\\local_uckk\\event\\faculty_profile_viewed';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'context' => $context,
        'other' => [
            'faculty_id' => (string)($manifestitem['faculty_id'] ?? ''),
            'voie_id' => (string)($manifestitem['voie_id'] ?? ''),
            'slug' => $slug,
            'status' => (string)($manifestitem['status'] ?? ''),
            'visibility' => (string)($manifestitem['visibility'] ?? ''),
        ],
    ]);

    $event->trigger();
}

$slug = required_param('slug', PARAM_ALPHANUMEXT);
$slug = local_uckk_faculty_require_safe_slug($slug);

$context = context_system::instance();
$url = new moodle_url('/local/uckk/faculty.php', ['slug' => $slug]);

$PAGE->set_context($context);
$PAGE->set_url($url);

// Use Moodle's standard layout for compatibility.
// The Faculty visual shell is activated by the body/root classes and styles.css.
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('local-uckk-public-page-host');
$PAGE->add_body_class('local-uckk-faculty-page-host');

if (!faculty_registry::exists_slug($slug)) {
    local_uckk_faculty_not_found();
}

$manifestitem = faculty_registry::get_by_slug($slug);
local_uckk_faculty_require_access($context, $manifestitem);

$builder = new faculty_page_builder();
$pagedata = $builder->build($slug);

$title = local_uckk_faculty_page_title($pagedata, $manifestitem);

$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($SITE->fullname));

$PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));
$PAGE->requires->js_call_amd('local_uckk/faculty_page', 'init');

local_uckk_faculty_trigger_view_event($context, $slug, $manifestitem);

echo $OUTPUT->header();
echo $OUTPUT->render(new faculty_page($pagedata));
echo $OUTPUT->footer();