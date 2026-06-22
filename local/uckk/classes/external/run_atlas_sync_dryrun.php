<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * External service running a read-only UCKK Atlas to Moodle sync dry-run.
 *
 * @package    local_uckk
 * @category   external
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use invalid_parameter_exception;

/**
 * External service running a read-only UCKK Atlas to Moodle sync dry-run.
 *
 * This service MUST NOT create categories, create courses, update courses,
 * hide courses, enrol users, change visibility, purge caches, write JSON files,
 * or modify Moodle state. It only compares the canonical JSON files against
 * Moodle category/course state and returns planned actions.
 *
 * Target service name:
 *
 * ```text
 * local_uckk_run_atlas_sync_dryrun
 * ```
 */
final class run_atlas_sync_dryrun extends external_api {
    /** Required capability to run Atlas sync dry-runs. */
    private const CAPABILITY = 'local/uckk:syncatlasmoodle';

    /** Faculty JSON directory, relative to Moodle dirroot. */
    private const FACULTY_DIR = '/local/uckk/content/faculties';

    /** Atlas Voies JSON directory, relative to Moodle dirroot. */
    private const ATLAS_VOIES_DIR = '/local/uckk/atlas/voies';

    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slug' => new external_value(
                PARAM_ALPHANUMEXT,
                'Optional faculty slug. Empty string dry-runs all faculty profiles.',
                VALUE_DEFAULT,
                ''
            ),
            'scope' => new external_value(
                PARAM_ALPHANUMEXT,
                'Dry-run scope: all, single_voie or selected.',
                VALUE_DEFAULT,
                'all'
            ),
            'includeitems' => new external_value(
                PARAM_BOOL,
                'Whether to include per-faculty dry-run items.',
                VALUE_DEFAULT,
                true
            ),
        ]);
    }

    /**
     * Execute the read-only Atlas sync dry-run.
     *
     * @param string $slug Optional faculty slug.
     * @param string $scope Dry-run scope.
     * @param bool $includeitems Whether to include item details.
     * @return array
     */
    public static function execute(string $slug = '', string $scope = 'all', bool $includeitems = true): array {
        global $CFG;

        [
            'slug' => $slug,
            'scope' => $scope,
            'includeitems' => $includeitems,
        ] = self::validate_parameters(self::execute_parameters(), [
            'slug' => $slug,
            'scope' => $scope,
            'includeitems' => $includeitems,
        ]);

        $slug = trim((string)$slug);
        $scope = trim((string)$scope);
        $includeitems = (bool)$includeitems;

        if ($slug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new invalid_parameter_exception('Invalid faculty slug.');
        }

        if (!in_array($scope, ['all', 'single_voie', 'selected'], true)) {
            throw new invalid_parameter_exception('Invalid dry-run scope.');
        }

        if ($slug !== '' && $scope === 'all') {
            $scope = 'single_voie';
        }

        if ($slug === '' && $scope === 'single_voie') {
            throw new invalid_parameter_exception('A slug is required when scope is single_voie.');
        }

        $context = context_system::instance();
        self::validate_context($context);
        require_capability(self::CAPABILITY, $context);

        $facultydir = $CFG->dirroot . self::FACULTY_DIR;
        $atlasdir = $CFG->dirroot . self::ATLAS_VOIES_DIR;

        $warnings = [];
        $errors = [];
        $items = [];

        if (!is_dir($facultydir)) {
            $errors[] = self::warning(
                'atlas_sync_dryrun',
                0,
                'facultydirnotfound',
                'Faculty profile directory not found.'
            );

            $response = self::build_response(
                'failed',
                $scope,
                [],
                $warnings,
                $errors,
                $includeitems
            );

            self::trigger_dryrun_event($response, $scope, 'run_atlas_sync_dryrun');

            return $response;
        }

        if (!is_dir($atlasdir)) {
            $warnings[] = self::warning(
                'atlas_sync_dryrun',
                0,
                'atlasdirnotfound',
                'Atlas Voies directory not found.'
            );
        }

        $facultyfiles = glob($facultydir . '/*.faculty.json') ?: [];
        sort($facultyfiles);

        foreach ($facultyfiles as $facultyfile) {
            $item = self::build_dryrun_item($facultyfile, $atlasdir);

            if ($slug !== '' && $item['slug'] !== $slug) {
                continue;
            }

            $items[] = $item;
        }

        if ($slug !== '' && count($items) === 0) {
            $warnings[] = self::warning(
                'atlas_sync_dryrun',
                0,
                'facultynotfound',
                'No faculty profile matched the requested slug.'
            );
        }

        $response = self::build_response(
            self::derive_sync_result($items, $warnings, $errors),
            $scope,
            $items,
            $warnings,
            $errors,
            $includeitems
        );

        self::trigger_dryrun_event($response, $scope, 'run_atlas_sync_dryrun');

        return $response;
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sync_result' => new external_value(
                PARAM_ALPHANUMEXT,
                'Dry-run result: completed, completed_with_warnings, or failed.'
            ),
            'scope' => new external_value(PARAM_ALPHANUMEXT, 'Dry-run scope.'),
            'generatedat' => new external_value(PARAM_INT, 'Unix timestamp.'),
            'report_hash' => new external_value(PARAM_RAW, 'SHA-256 hash of the dry-run report.'),

            'voie_count' => new external_value(PARAM_INT, 'Number of Atlas Voie links inspected.'),
            'faculty_count' => new external_value(PARAM_INT, 'Number of faculty profiles inspected.'),
            'category_count' => new external_value(PARAM_INT, 'Number of existing Moodle categories found.'),
            'course_count' => new external_value(PARAM_INT, 'Number of existing Moodle courses counted.'),

            'create_count' => new external_value(PARAM_INT, 'Number of create actions that would be needed.'),
            'update_count' => new external_value(PARAM_INT, 'Number of update actions that would be needed.'),
            'hide_count' => new external_value(PARAM_INT, 'Number of hide actions that would be needed.'),
            'noop_count' => new external_value(PARAM_INT, 'Number of items requiring no action.'),
            'review_count' => new external_value(PARAM_INT, 'Number of items requiring human review.'),

            'error_count' => new external_value(PARAM_INT, 'Number of dry-run errors.'),
            'warning_count' => new external_value(PARAM_INT, 'Number of dry-run warnings.'),

            'items' => new external_multiple_structure(
                new external_single_structure([
                    'faculty_id' => new external_value(PARAM_ALPHANUMEXT, 'Faculty id.'),
                    'voie_id' => new external_value(PARAM_ALPHANUMEXT, 'Atlas Voie id.'),
                    'slug' => new external_value(PARAM_ALPHANUMEXT, 'Faculty slug.'),
                    'faculty_file' => new external_value(PARAM_FILE, 'Faculty JSON filename.'),
                    'atlas_file' => new external_value(PARAM_FILE, 'Atlas JSON filename.'),

                    'category_idnumber' => new external_value(PARAM_RAW, 'Expected Moodle category idnumber.'),
                    'category_exists' => new external_value(PARAM_BOOL, 'Whether the Moodle category exists.'),
                    'category_id' => new external_value(PARAM_INT, 'Moodle category id, or 0.'),

                    'course_prefix' => new external_value(PARAM_ALPHANUMEXT, 'Expected Moodle course idnumber prefix.'),
                    'course_count' => new external_value(PARAM_INT, 'Current Moodle course count for the faculty.'),

                    'hub_course_idnumber' => new external_value(PARAM_RAW, 'Expected hub course idnumber.'),
                    'hub_exists' => new external_value(PARAM_BOOL, 'Whether the hub course exists.'),
                    'hub_course_id' => new external_value(PARAM_INT, 'Hub course id, or 0.'),

                    'atlas_exists' => new external_value(PARAM_BOOL, 'Whether the linked Atlas file exists.'),
                    'planned_action' => new external_value(PARAM_ALPHANUMEXT, 'Planned action.'),
                    'would_write' => new external_value(PARAM_BOOL, 'Whether an apply sync would write changes.'),
                    'summary' => new external_value(PARAM_TEXT, 'Short item summary.'),

                    'warnings' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Item warning.'),
                        'Item warnings.'
                    ),
                    'errors' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Item error.'),
                        'Item errors.'
                    ),
                ]),
                'Per-faculty dry-run items.'
            ),

            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Build one dry-run item.
     *
     * @param string $facultyfile Absolute faculty JSON path.
     * @param string $atlasdir Absolute Atlas Voies directory.
     * @return array
     */
    private static function build_dryrun_item(string $facultyfile, string $atlasdir): array {
        $itemwarnings = [];
        $itemerrors = [];

        $facultyfilename = basename($facultyfile);
        $faculty = self::read_json_file($facultyfile, $itemerrors);

        $facultyid = self::get_string($faculty, ['faculty_id']);
        $voieid = self::get_string($faculty, ['voie_id']);
        $slug = self::get_string($faculty, ['slug']);

        $atlasfile = self::get_string($faculty, ['source_atlas', 'file']);
        $categoryidnumber = self::get_string($faculty, ['moodle', 'category_idnumber']);
        $courseprefix = self::get_string($faculty, ['moodle', 'course_prefix']);
        $hubcourseidnumber = self::get_string($faculty, ['moodle', 'hub_course_idnumber']);

        if ($facultyid === '') {
            $itemerrors[] = 'Missing faculty_id.';
        }

        if ($voieid === '') {
            $itemerrors[] = 'Missing voie_id.';
        }

        if ($slug === '') {
            $itemerrors[] = 'Missing slug.';
        }

        if ($atlasfile === '') {
            $itemwarnings[] = 'Missing source_atlas.file.';
        }

        if ($categoryidnumber === '') {
            $itemwarnings[] = 'Missing moodle.category_idnumber.';
        }

        if ($hubcourseidnumber === '') {
            $itemwarnings[] = 'Missing moodle.hub_course_idnumber.';
        }

        $atlasexists = false;
        if ($atlasfile !== '') {
            $atlasexists = is_readable($atlasdir . '/' . $atlasfile);
            if (!$atlasexists) {
                $itemwarnings[] = 'Linked Atlas Voie file is missing or unreadable.';
            }
        }

        $category = self::get_category_by_idnumber($categoryidnumber);
        $categoryid = $category ? (int)$category->id : 0;
        $categoryexists = $categoryid > 0;

        if ($categoryidnumber !== '' && !$categoryexists) {
            $itemwarnings[] = 'Moodle category would need to be created by apply sync.';
        }

        $coursecount = $categoryexists
            ? self::count_courses_for_category($categoryid, $courseprefix)
            : 0;

        $hubcourse = self::get_course_by_idnumber($hubcourseidnumber);
        $hubcourseid = $hubcourse ? (int)$hubcourse->id : 0;
        $hubexists = $hubcourseid > 0;

        if ($hubcourseidnumber !== '' && !$hubexists) {
            $itemwarnings[] = 'Faculty hub course would need to be created by apply sync.';
        }

        $plannedaction = self::derive_planned_action(
            $itemerrors,
            $atlasexists,
            $categoryexists,
            $hubexists
        );

        return [
            'faculty_id' => $facultyid,
            'voie_id' => $voieid,
            'slug' => $slug,
            'faculty_file' => $facultyfilename,
            'atlas_file' => $atlasfile,

            'category_idnumber' => $categoryidnumber,
            'category_exists' => $categoryexists,
            'category_id' => $categoryid,

            'course_prefix' => $courseprefix,
            'course_count' => $coursecount,

            'hub_course_idnumber' => $hubcourseidnumber,
            'hub_exists' => $hubexists,
            'hub_course_id' => $hubcourseid,

            'atlas_exists' => $atlasexists,
            'planned_action' => $plannedaction,
            'would_write' => in_array($plannedaction, ['create_category', 'create_hub', 'update_courses', 'hide_courses'], true),
            'summary' => self::build_item_summary($plannedaction, $itemwarnings, $itemerrors),

            'warnings' => $itemwarnings,
            'errors' => $itemerrors,
        ];
    }

    /**
     * Build final response.
     *
     * @param string $syncresult Sync result.
     * @param string $scope Scope.
     * @param array $items Dry-run items.
     * @param array $warnings Top-level warnings.
     * @param array $errors Top-level errors.
     * @param bool $includeitems Whether to include items.
     * @return array
     */
    private static function build_response(
        string $syncresult,
        string $scope,
        array $items,
        array $warnings,
        array $errors,
        bool $includeitems
    ): array {
        $summary = self::summarise_items($items);
        $summary['error_count'] += count($errors);
        $summary['warning_count'] += count($warnings);

        $payloadforhash = [
            'sync_result' => $syncresult,
            'scope' => $scope,
            'summary' => $summary,
            'items' => $items,
        ];

        $reporthash = 'sha256:' . hash(
            'sha256',
            json_encode($payloadforhash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return [
            'sync_result' => $syncresult,
            'scope' => $scope,
            'generatedat' => time(),
            'report_hash' => $reporthash,

            'voie_count' => $summary['voie_count'],
            'faculty_count' => $summary['faculty_count'],
            'category_count' => $summary['category_count'],
            'course_count' => $summary['course_count'],

            'create_count' => $summary['create_count'],
            'update_count' => $summary['update_count'],
            'hide_count' => $summary['hide_count'],
            'noop_count' => $summary['noop_count'],
            'review_count' => $summary['review_count'],

            'error_count' => $summary['error_count'],
            'warning_count' => $summary['warning_count'],

            'items' => $includeitems ? $items : [],
            'warnings' => array_merge($warnings, $errors),
        ];
    }

    /**
     * Trigger the dry-run completed event.
     *
     * @param array $response Dry-run response.
     * @param string $scope Dry-run scope.
     * @param string $source Event source.
     * @return void
     */
    private static function trigger_dryrun_event(array $response, string $scope, string $source): void {
        $event = \local_uckk\event\atlas_sync_dryrun_completed::create([
            'context' => context_system::instance(),
            'other' => [
                'sync_result' => $response['sync_result'],
                'scope' => $scope,
                'voie_count' => $response['voie_count'],
                'category_count' => $response['category_count'],
                'course_count' => $response['course_count'],
                'create_count' => $response['create_count'],
                'update_count' => $response['update_count'],
                'hide_count' => $response['hide_count'],
                'noop_count' => $response['noop_count'],
                'error_count' => $response['error_count'],
                'warning_count' => $response['warning_count'],
                'report_hash' => $response['report_hash'],
                'source' => $source,
            ],
        ]);

        $event->trigger();
    }

    /**
     * Read JSON file.
     *
     * @param string $path Absolute path.
     * @param array $errors Error accumulator.
     * @return array
     */
    private static function read_json_file(string $path, array &$errors): array {
        if (!is_readable($path)) {
            $errors[] = 'Faculty JSON file is not readable.';
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $errors[] = 'Faculty JSON file could not be read.';
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $errors[] = 'Faculty JSON file is invalid.';
            return [];
        }

        return $decoded;
    }

    /**
     * Get nested scalar string.
     *
     * @param array $data Source data.
     * @param array $path Nested keys.
     * @return string
     */
    private static function get_string(array $data, array $path): string {
        $value = $data;

        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return '';
            }

            $value = $value[$key];
        }

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * Get Moodle category by idnumber.
     *
     * @param string $idnumber Category idnumber.
     * @return object|null
     */
    private static function get_category_by_idnumber(string $idnumber): ?object {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        $record = $DB->get_record(
            'course_categories',
            ['idnumber' => $idnumber],
            'id, idnumber, name, visible',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Get Moodle course by idnumber.
     *
     * @param string $idnumber Course idnumber.
     * @return object|null
     */
    private static function get_course_by_idnumber(string $idnumber): ?object {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        $record = $DB->get_record(
            'course',
            ['idnumber' => $idnumber],
            'id, idnumber, fullname, visible',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Count courses in a category, optionally filtered by idnumber prefix.
     *
     * @param int $categoryid Course category id.
     * @param string $courseprefix Course idnumber prefix.
     * @return int
     */
    private static function count_courses_for_category(int $categoryid, string $courseprefix): int {
        global $DB;

        if ($categoryid <= 0) {
            return 0;
        }

        if ($courseprefix === '') {
            return (int)$DB->count_records('course', ['category' => $categoryid]);
        }

        $params = [
            'categoryid' => $categoryid,
            'prefix' => $DB->sql_like_escape($courseprefix) . '%',
        ];

        return (int)$DB->count_records_select(
            'course',
            'category = :categoryid AND ' . $DB->sql_like('idnumber', ':prefix', false, false),
            $params
        );
    }

    /**
     * Derive planned sync action.
     *
     * @param array $errors Item errors.
     * @param bool $atlasexists Whether Atlas file exists.
     * @param bool $categoryexists Whether category exists.
     * @param bool $hubexists Whether hub course exists.
     * @return string
     */
    private static function derive_planned_action(
        array $errors,
        bool $atlasexists,
        bool $categoryexists,
        bool $hubexists
    ): string {
        if (count($errors) > 0) {
            return 'review';
        }

        if (!$atlasexists) {
            return 'review_atlas';
        }

        if (!$categoryexists) {
            return 'create_category';
        }

        if (!$hubexists) {
            return 'create_hub';
        }

        return 'noop';
    }

    /**
     * Build item summary.
     *
     * @param string $plannedaction Planned action.
     * @param array $warnings Warnings.
     * @param array $errors Errors.
     * @return string
     */
    private static function build_item_summary(string $plannedaction, array $warnings, array $errors): string {
        if (count($errors) > 0) {
            return 'Review required before sync can be applied.';
        }

        if ($plannedaction === 'noop' && count($warnings) === 0) {
            return 'No sync action required.';
        }

        if ($plannedaction === 'noop') {
            return 'No write action planned, but warnings should be reviewed.';
        }

        return 'Dry-run planned action: ' . $plannedaction . '.';
    }

    /**
     * Summarise items.
     *
     * @param array $items Dry-run items.
     * @return array
     */
    private static function summarise_items(array $items): array {
        $summary = [
            'voie_count' => 0,
            'faculty_count' => count($items),
            'category_count' => 0,
            'course_count' => 0,
            'create_count' => 0,
            'update_count' => 0,
            'hide_count' => 0,
            'noop_count' => 0,
            'review_count' => 0,
            'error_count' => 0,
            'warning_count' => 0,
        ];

        foreach ($items as $item) {
            if (!empty($item['atlas_exists'])) {
                $summary['voie_count']++;
            }

            if (!empty($item['category_exists'])) {
                $summary['category_count']++;
            }

            $summary['course_count'] += (int)($item['course_count'] ?? 0);
            $summary['error_count'] += count($item['errors'] ?? []);
            $summary['warning_count'] += count($item['warnings'] ?? []);

            switch ($item['planned_action'] ?? 'review') {
                case 'create_category':
                case 'create_hub':
                    $summary['create_count']++;
                    break;

                case 'update_courses':
                    $summary['update_count']++;
                    break;

                case 'hide_courses':
                    $summary['hide_count']++;
                    break;

                case 'noop':
                    $summary['noop_count']++;
                    break;

                default:
                    $summary['review_count']++;
                    break;
            }
        }

        return $summary;
    }

    /**
     * Derive dry-run result.
     *
     * @param array $items Dry-run items.
     * @param array $warnings Top-level warnings.
     * @param array $errors Top-level errors.
     * @return string
     */
    private static function derive_sync_result(array $items, array $warnings, array $errors): string {
        if (count($errors) > 0) {
            return 'failed';
        }

        foreach ($items as $item) {
            if (count($item['errors'] ?? []) > 0) {
                return 'failed';
            }
        }

        if (count($warnings) > 0) {
            return 'completed_with_warnings';
        }

        foreach ($items as $item) {
            if (count($item['warnings'] ?? []) > 0) {
                return 'completed_with_warnings';
            }
        }

        return 'completed';
    }

    /**
     * Build an external warning.
     *
     * @param string $item Warning item.
     * @param int $itemid Related item id.
     * @param string $warningcode Warning code.
     * @param string $message Warning message.
     * @return array
     */
    private static function warning(string $item, int $itemid, string $warningcode, string $message): array {
        return [
            'item' => $item,
            'itemid' => $itemid,
            'warningcode' => clean_param($warningcode, PARAM_ALPHANUMEXT),
            'message' => $message,
        ];
    }
}