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

declare(strict_types=1);

/**
 * External function returning one public UCKK faculty page context.
 *
 * This service is read-only. It returns the already built public Mustache
 * context for a canonical Faculty Profile slug.
 *
 * It does not:
 * - accept filesystem paths;
 * - create or update Moodle categories, courses, badges or custom fields;
 * - expose completion, enrolment, grades or learner-private data;
 * - render business logic in templates;
 * - launch Atlas/Moodle sync.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_uckk\event\faculty_profile_viewed;
use local_uckk\local\faculty\faculty_page_builder;
use local_uckk\local\faculty\faculty_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Return one UCKK public faculty page context.
 *
 * Exposed service name:
 *
 * local_uckk_get_faculty_public_page
 */
final class get_faculty_public_page extends external_api {
    /**
     * Capability required for restricted public faculty pages.
     */
    private const CAPABILITY_VIEW_RESTRICTED = 'local/uckk:viewpublicfaculties';

    /**
     * Capability required to preview non-published faculty profiles.
     */
    private const CAPABILITY_MANAGE = 'local/uckk:managefacultyprofiles';

    /**
     * Published status.
     */
    private const STATUS_PUBLISHED = 'published';

    /**
     * Public visibility.
     */
    private const VISIBILITY_PUBLIC = 'public';

    /**
     * Restricted visibility.
     */
    private const VISIBILITY_RESTRICTED = 'restricted';

    /**
     * Canonical template name.
     */
    private const TEMPLATE = 'local_uckk/faculty_page';

    /**
     * Top-level Mustache variables allowed by DOC_12.
     */
    private const ALLOWED_PAGE_KEYS = [
        'page',
        'hero',
        'navigation',
        'identity',
        'sections',
        'atlas',
        'courses',
        'project_final',
        'limits',
        'relations',
        'dynamic_blocks',
        'featured_blocks',
        'faq',
        'contact',
        'notices',
        'metadata',
    ];

    /**
     * Define external parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slug' => new external_value(
                PARAM_ALPHANUMEXT,
                'Canonical public faculty slug.',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param string $slug Canonical public faculty slug.
     * @return array<string, mixed>
     */
    public static function execute(string $slug): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'slug' => $slug,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        $slug = self::clean_slug((string)$params['slug']);

        if ($slug === '') {
            throw new invalid_parameter_exception('Faculty slug is required.');
        }

        try {
            $profile = self::load_profile($slug);
        } catch (\Throwable $exception) {
            return self::not_found_response(
                $slug,
                self::make_issue(
                    'slug',
                    'faculty_not_found',
                    'The requested faculty profile was not found.'
                )
            );
        }

        $status = self::get_profile_status($profile);
        $visibility = self::get_profile_visibility($profile);

        self::enforce_access($context, $status, $visibility);

        try {
            $page = self::build_page($slug);
        } catch (\Throwable $exception) {
            return self::not_found_response(
                $slug,
                self::make_issue(
                    'slug',
                    'faculty_page_unavailable',
                    'The requested faculty page could not be built.'
                )
            );
        }

        $page = self::filter_page_context(self::normalise_array($page));

        $pageinfo = self::get_array_value($page, 'page');
        $resolvedslug = self::get_string_value($pageinfo, 'slug', $slug);
        $facultyid = self::get_string_value($pageinfo, 'faculty_id', self::get_string_value($profile, 'faculty_id'));
        $voieid = self::get_string_value($pageinfo, 'voie_id', self::get_string_value($profile, 'voie_id'));
        $pagestatus = self::get_string_value($pageinfo, 'status', $status);
        $pagevisibility = self::get_string_value($pageinfo, 'visibility', $visibility);

        $checksum = self::hash_page($page);
        $pagejson = self::encode_json($page);

        self::trigger_view_event(
            $context,
            $resolvedslug,
            $facultyid,
            $voieid,
            $pagestatus,
            $pagevisibility,
            $checksum
        );

        return [
            'found' => true,
            'slug' => $resolvedslug,
            'faculty_id' => $facultyid,
            'voie_id' => $voieid,
            'status' => $pagestatus,
            'visibility' => $pagevisibility,
            'template' => self::TEMPLATE,
            'checksum' => $checksum,
            'pagejson' => $pagejson,
            'warnings' => [],
        ];
    }

    /**
     * Define external return structure.
     *
     * The full page context is returned as JSON because the Faculty Page context
     * intentionally contains nested arrays for Atlas projections, courses,
     * dynamic blocks, FAQ, relations and notices.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'found' => new external_value(PARAM_BOOL, 'Whether a page was found and returned.'),
            'slug' => new external_value(PARAM_ALPHANUMEXT, 'Canonical faculty slug.'),
            'faculty_id' => new external_value(PARAM_ALPHANUMEXT, 'Canonical faculty id.'),
            'voie_id' => new external_value(PARAM_ALPHANUMEXT, 'Canonical Atlas voie id.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Faculty profile status.'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Faculty page visibility.'),
            'template' => new external_value(PARAM_TEXT, 'Canonical Mustache template name.'),
            'checksum' => new external_value(PARAM_ALPHANUM, 'SHA-256 checksum of the returned public page context.'),
            'pagejson' => new external_value(PARAM_RAW, 'JSON-encoded public faculty page Mustache context.'),
            'warnings' => new external_multiple_structure(
                self::issue_return_structure(),
                'Non-fatal warnings.'
            ),
        ]);
    }

    /**
     * Shared return structure for warnings.
     *
     * @return external_single_structure
     */
    private static function issue_return_structure(): external_single_structure {
        return new external_single_structure([
            'path' => new external_value(PARAM_TEXT, 'Field or JSON path.'),
            'code' => new external_value(PARAM_ALPHANUMEXT, 'Machine-readable warning code.'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable warning message.'),
        ]);
    }

    /**
     * Clean and validate a canonical slug.
     *
     * The slug is an identifier only. It must never be interpreted as a path.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return '';
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new invalid_parameter_exception('Invalid faculty slug.');
        }

        return $slug;
    }

    /**
     * Load the canonical Faculty Profile before building the page.
     *
     * This is used to enforce visibility before the full page context is
     * returned to the caller.
     *
     * @param string $slug Canonical slug.
     * @return array<string, mixed>
     */
    private static function load_profile(string $slug): array {
        if (!class_exists(faculty_repository::class)) {
            throw new \coding_exception('The faculty_repository class is required.');
        }

        $repository = new faculty_repository();

        if (!method_exists($repository, 'get_by_slug')) {
            throw new \coding_exception('faculty_repository must expose get_by_slug(string $slug).');
        }

        $profile = $repository->get_by_slug($slug);

        return self::normalise_array($profile);
    }

    /**
     * Build the public Faculty Page context.
     *
     * @param string $slug Canonical slug.
     * @return array<string, mixed>
     */
    private static function build_page(string $slug): array {
        if (!class_exists(faculty_page_builder::class)) {
            throw new \coding_exception('The faculty_page_builder class is required.');
        }

        $builder = new faculty_page_builder();

        if (!method_exists($builder, 'build')) {
            throw new \coding_exception('faculty_page_builder must expose build(string $slug).');
        }

        $page = $builder->build($slug);

        return self::normalise_array($page);
    }

    /**
     * Enforce access rules for a Faculty Profile.
     *
     * - published + public: anonymous access allowed;
     * - restricted: login + view restricted faculty capability;
     * - draft, hidden, archived or any other non-public state: manage capability.
     *
     * @param context_system $context System context.
     * @param string $status Profile status.
     * @param string $visibility Profile visibility.
     * @return void
     */
    private static function enforce_access(context_system $context, string $status, string $visibility): void {
        if ($status === self::STATUS_PUBLISHED && $visibility === self::VISIBILITY_PUBLIC) {
            return;
        }

        if (!isloggedin() || isguestuser()) {
            require_login();
        }

        if ($status === self::STATUS_PUBLISHED && $visibility === self::VISIBILITY_RESTRICTED) {
            require_capability(self::CAPABILITY_VIEW_RESTRICTED, $context);
            return;
        }

        require_capability(self::CAPABILITY_MANAGE, $context);
    }

    /**
     * Return a safe not-found response.
     *
     * @param string $slug Requested slug.
     * @param array<string, string> $warning Warning.
     * @return array<string, mixed>
     */
    private static function not_found_response(string $slug, array $warning): array {
        return [
            'found' => false,
            'slug' => $slug,
            'faculty_id' => '',
            'voie_id' => '',
            'status' => '',
            'visibility' => '',
            'template' => self::TEMPLATE,
            'checksum' => hash('sha256', ''),
            'pagejson' => '{}',
            'warnings' => [$warning],
        ];
    }

    /**
     * Filter the builder result to the top-level Mustache variables documented
     * for the Faculty Page template.
     *
     * @param array<string, mixed> $page Raw page context.
     * @return array<string, mixed>
     */
    private static function filter_page_context(array $page): array {
        $filtered = [];

        foreach (self::ALLOWED_PAGE_KEYS as $key) {
            if (array_key_exists($key, $page)) {
                $filtered[$key] = $page[$key];
            }
        }

        return $filtered;
    }

    /**
     * Get profile status.
     *
     * @param array<string, mixed> $profile Faculty Profile.
     * @return string
     */
    private static function get_profile_status(array $profile): string {
        return self::get_string_value($profile, 'status', '');
    }

    /**
     * Get profile visibility.
     *
     * @param array<string, mixed> $profile Faculty Profile.
     * @return string
     */
    private static function get_profile_visibility(array $profile): string {
        return self::get_string_value($profile, 'visibility', '');
    }

    /**
     * Normalize mixed data into an array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private static function normalise_array($value): array {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \stdClass) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                return [];
            }

            $decoded = json_decode($encoded, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get an array value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Field key.
     * @return array<string, mixed>
     */
    private static function get_array_value(array $data, string $key): array {
        if (!isset($data[$key])) {
            return [];
        }

        return is_array($data[$key]) ? $data[$key] : self::normalise_array($data[$key]);
    }

    /**
     * Get a string value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Field key.
     * @param string $fallback Fallback value.
     * @return string
     */
    private static function get_string_value(array $data, string $key, string $fallback = ''): string {
        if (!array_key_exists($key, $data)) {
            return $fallback;
        }

        $value = $data[$key];

        if (is_scalar($value)) {
            return trim((string)$value);
        }

        return $fallback;
    }

    /**
     * JSON encode the public page context.
     *
     * @param array<string, mixed> $page Public page context.
     * @return string
     */
    private static function encode_json(array $page): string {
        $encoded = json_encode($page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            return '{}';
        }

        return $encoded;
    }

    /**
     * Compute a stable SHA-256 hash for the public page context.
     *
     * @param array<string, mixed> $page Public page context.
     * @return string
     */
    private static function hash_page(array $page): string {
        $copy = $page;
        self::recursive_ksort($copy);

        $encoded = json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            $encoded = '';
        }

        return hash('sha256', $encoded);
    }

    /**
     * Sort associative arrays recursively for stable hashing.
     *
     * @param mixed $value Value to sort.
     * @return void
     */
    private static function recursive_ksort(&$value): void {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as &$child) {
            self::recursive_ksort($child);
        }
        unset($child);

        if (!self::is_list_array($value)) {
            ksort($value);
        }
    }

    /**
     * Test whether an array is list-like.
     *
     * This replaces array_is_list() for compatibility with older supported
     * Moodle PHP baselines.
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool
     */
    private static function is_list_array(array $array): bool {
        $expected = 0;

        foreach (array_keys($array) as $key) {
            if ($key !== $expected) {
                return false;
            }

            $expected++;
        }

        return true;
    }

    /**
     * Create a sanitized warning issue.
     *
     * @param string $path Field or JSON path.
     * @param string $code Machine-readable code.
     * @param string $message Human-readable message.
     * @return array<string, string>
     */
    private static function make_issue(string $path, string $code, string $message): array {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_-]+/', '_', $code);
        $code = trim((string)$code, '_');

        if ($code === '') {
            $code = 'warning';
        }

        return [
            'path' => clean_param($path, PARAM_TEXT),
            'code' => clean_param($code, PARAM_ALPHANUMEXT),
            'message' => clean_param($message, PARAM_TEXT),
        ];
    }

    /**
     * Trigger the faculty profile viewed event when available.
     *
     * The event intentionally receives only ids, status, visibility and hash.
     * It must not receive the full page context or source JSON payload.
     *
     * @param context_system $context System context.
     * @param string $slug Faculty slug.
     * @param string $facultyid Faculty id.
     * @param string $voieid Atlas voie id.
     * @param string $status Profile status.
     * @param string $visibility Profile visibility.
     * @param string $checksum Returned page context checksum.
     * @return void
     */
    private static function trigger_view_event(
        context_system $context,
        string $slug,
        string $facultyid,
        string $voieid,
        string $status,
        string $visibility,
        string $checksum
    ): void {
        if (!class_exists(faculty_profile_viewed::class)) {
            return;
        }

        try {
            $event = faculty_profile_viewed::create([
                'context' => $context,
                'other' => [
                    'slug' => $slug,
                    'facultyid' => $facultyid,
                    'voieid' => $voieid,
                    'status' => $status,
                    'visibility' => $visibility,
                    'checksum' => $checksum,
                    'source' => 'external_service',
                ],
            ]);

            $event->trigger();
        } catch (\Throwable $exception) {
            debugging(
                'Unable to trigger faculty_profile_viewed event: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}