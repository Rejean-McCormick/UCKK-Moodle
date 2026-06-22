<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * External service returning a read-only UCKK Faculty / Atlas / Moodle sync report.
 *
 * @package    local_uckk
 * @category   external
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\external;

use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * External service returning a read-only UCKK Faculty / Atlas / Moodle sync report.
 *
 * This service does not apply any synchronisation. It only compares:
 *
 * - local/uckk/content/faculties/*.faculty.json
 * - local/uckk/atlas/voies/voie_*.json
 * - Moodle course categories and hub courses
 *
 * The returned payload is intentionally limited to ids, filenames, status,
 * counts, booleans, warnings and hashes. It must not expose full Faculty JSON,
 * full Atlas JSON, private user data, grades, progress, enrolments or hidden
 * operational metadata.
 */
final class get_faculty_sync_report extends external_api {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Required capability for read-only sync reports. */
    private const CAPABILITY = 'local/uckk:viewreports';

    /** Faculty JSON directory, relative to Moodle dirroot. */
    private const FACULTY_DIR = '/local/uckk/content/faculties';

    /** Atlas Voies JSON directory, relative to Moodle dirroot. */
    private const ATLAS_VOIES_DIR = '/local/uckk/atlas/voies';

    /**
     * Define external parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slug' => new external_value(
                PARAM_ALPHANUMEXT,
                'Optional faculty slug. Empty string returns all faculties.',
                VALUE_DEFAULT,
                ''
            ),
            'includeitems' => new external_value(
                PARAM_BOOL,
                'Whether to include per-faculty report items.',
                VALUE_DEFAULT,
                true
            ),
        ]);
    }

    /**
     * Execute the read-only sync report.
     *
     * @param string $slug Optional faculty slug.
     * @param bool $includeitems Whether to include per-faculty items.
     * @return array
     */
    public static function execute(string $slug = '', bool $includeitems = true): array {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'slug' => $slug,
            'includeitems' => $includeitems,
        ]);

        $slug = trim((string)$params['slug']);
        $includeitems = (bool)$params['includeitems'];

        if ($slug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new invalid_parameter_exception('Invalid faculty slug.');
        }

        $context = context_system::instance();
        self::validate_context($context);
        require_capability(self::CAPABILITY, $context);

        $facultydir = $CFG->dirroot . self::FACULTY_DIR;
        $atlasdir = $CFG->dirroot . self::ATLAS_VOIES_DIR;

        $items = [];
        $warnings = [];
        $errors = [];

        if (!is_dir($facultydir)) {
            $errors[] = self::make_warning(
                'facultydirnotfound',
                0,
                'Faculty profile directory not found.'
            );

            return self::build_response(
                'error',
                'Faculty profile directory not found.',
                $slug === '' ? 'all' : 'single',
                [],
                $warnings,
                $errors
            );
        }

        if (!is_dir($atlasdir)) {
            $warnings[] = self::make_warning(
                'atlasdirnotfound',
                0,
                'Atlas Voies directory not found.'
            );
        }

        $facultyfiles = glob($facultydir . '/*.faculty.json') ?: [];
        sort($facultyfiles);

        foreach ($facultyfiles as $facultyfile) {
            $item = self::build_faculty_item($facultyfile, $atlasdir);

            if ($slug !== '' && $item['slug'] !== $slug) {
                continue;
            }

            $items[] = $item;
        }

        if ($slug !== '' && count($items) === 0) {
            $warnings[] = self::make_warning(
                'facultynotfound',
                0,
                'No faculty profile matched the requested slug.'
            );
        }

        return self::build_response(
            self::derive_status($items, $warnings, $errors),
            '',
            $slug === '' ? 'all' : 'single',
            $includeitems ? $items : [],
            $warnings,
            $errors
        );
    }

    /**
     * Define external return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Report status: ok, warning, or error.'),
            'message' => new external_value(PARAM_TEXT, 'Optional report message.', VALUE_DEFAULT, ''),
            'generatedat' => new external_value(PARAM_INT, 'Unix timestamp at which the report was generated.'),
            'scope' => new external_value(PARAM_ALPHANUMEXT, 'Report scope: all or single.'),

            'facultycount' => new external_value(PARAM_INT, 'Number of faculty profiles inspected.'),
            'voiecount' => new external_value(PARAM_INT, 'Number of linked Atlas Voie files found.'),
            'categorycount' => new external_value(PARAM_INT, 'Number of linked Moodle categories found.'),
            'coursecount' => new external_value(PARAM_INT, 'Number of linked Moodle courses counted.'),
            'missingcategorycount' => new external_value(PARAM_INT, 'Number of missing Moodle categories.'),
            'missinghubcount' => new external_value(PARAM_INT, 'Number of missing faculty hub courses.'),
            'errorcount' => new external_value(PARAM_INT, 'Number of report errors.'),
            'warningcount' => new external_value(PARAM_INT, 'Number of report warnings.'),
            'reporthash' => new external_value(PARAM_RAW, 'SHA-256 hash of the report payload.'),

            'items' => new external_multiple_structure(
                new external_single_structure([
                    'faculty_id' => new external_value(PARAM_ALPHANUMEXT, 'Faculty id.'),
                    'voie_id' => new external_value(PARAM_ALPHANUMEXT, 'Atlas Voie id.'),
                    'slug' => new external_value(PARAM_ALPHANUMEXT, 'Faculty slug.'),
                    'faculty_file' => new external_value(PARAM_FILE, 'Faculty JSON filename.'),
                    'atlas_file' => new external_value(PARAM_FILE, 'Atlas JSON filename.'),
                    'schema_version' => new external_value(PARAM_RAW, 'Faculty schema version.'),
                    'atlas_schema_version_expected' => new external_value(PARAM_RAW, 'Expected Atlas schema version.'),

                    'status' => new external_value(PARAM_ALPHANUMEXT, 'Faculty editorial status.'),
                    'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Faculty visibility.'),

                    'category_idnumber' => new external_value(PARAM_RAW, 'Expected Moodle category idnumber.'),
                    'category_exists' => new external_value(PARAM_BOOL, 'Whether the Moodle category exists.'),
                    'category_id' => new external_value(PARAM_INT, 'Moodle category id, or 0 when absent.'),

                    'course_prefix' => new external_value(PARAM_ALPHANUMEXT, 'Expected Moodle course idnumber prefix.'),
                    'course_count' => new external_value(PARAM_INT, 'Number of Moodle courses counted for this faculty.'),

                    'hub_course_idnumber' => new external_value(PARAM_RAW, 'Expected hub course idnumber.'),
                    'hub_exists' => new external_value(PARAM_BOOL, 'Whether the hub course exists.'),
                    'hub_course_id' => new external_value(PARAM_INT, 'Hub course id, or 0 when absent.'),

                    'atlas_exists' => new external_value(PARAM_BOOL, 'Whether the linked Atlas file exists.'),
                    'planned_action' => new external_value(PARAM_ALPHANUMEXT, 'Read-only suggested sync action.'),
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
                'Per-faculty report items.'
            ),

            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Build one report item from a faculty JSON file.
     *
     * @param string $facultyfile Absolute faculty file path.
     * @param string $atlasdir Absolute Atlas Voies directory path.
     * @return array
     */
    private static function build_faculty_item(string $facultyfile, string $atlasdir): array {
        $itemwarnings = [];
        $itemerrors = [];

        $facultyfilename = basename($facultyfile);
        $faculty = self::read_json_file($facultyfile, $itemerrors);

        $facultyid = self::get_string($faculty, ['faculty_id']);
        $voieid = self::get_string($faculty, ['voie_id']);
        $slug = self::get_string($faculty, ['slug']);
        $status = self::get_string($faculty, ['status']);
        $visibility = self::get_string($faculty, ['visibility']);
        $schemaversion = self::get_string($faculty, ['schema_version']);

        $atlasfile = self::get_string($faculty, ['source_atlas', 'file']);
        $atlasschemaexpected = self::get_string($faculty, ['source_atlas', 'schema_version_expected']);

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
            $itemwarnings[] = 'Moodle category is missing.';
        }

        $coursecount = $categoryexists
            ? self::count_courses_for_category($categoryid, $courseprefix)
            : 0;

        $hubcourse = self::get_course_by_idnumber($hubcourseidnumber);
        $hubcourseid = $hubcourse ? (int)$hubcourse->id : 0;
        $hubexists = $hubcourseid > 0;

        if ($hubcourseidnumber !== '' && !$hubexists) {
            $itemwarnings[] = 'Faculty hub course is missing.';
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
            'schema_version' => $schemaversion,
            'atlas_schema_version_expected' => $atlasschemaexpected,

            'status' => $status,
            'visibility' => $visibility,

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
            'summary' => self::build_item_summary($plannedaction, $itemwarnings, $itemerrors),

            'warnings' => $itemwarnings,
            'errors' => $itemerrors,
        ];
    }

    /**
     * Build final report response.
     *
     * @param string $status Report status.
     * @param string $message Optional message.
     * @param string $scope all or single.
     * @param array $items Report items.
     * @param array $warnings Moodle external warnings.
     * @param array $errors Moodle external warnings used as errors.
     * @return array
     */
    private static function build_response(
        string $status,
        string $message,
        string $scope,
        array $items,
        array $warnings,
        array $errors
    ): array {
        $summary = self::summarise_items($items);

        $payloadforhash = [
            'status' => $status,
            'scope' => $scope,
            'summary' => $summary,
            'items' => $items,
        ];

        $reporthash = 'sha256:' . hash(
            'sha256',
            json_encode($payloadforhash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return [
            'status' => $status,
            'message' => $message,
            'generatedat' => time(),
            'scope' => $scope,

            'facultycount' => $summary['facultycount'],
            'voiecount' => $summary['voiecount'],
            'categorycount' => $summary['categorycount'],
            'coursecount' => $summary['coursecount'],
            'missingcategorycount' => $summary['missingcategorycount'],
            'missinghubcount' => $summary['missinghubcount'],
            'errorcount' => $summary['errorcount'] + count($errors),
            'warningcount' => $summary['warningcount'] + count($warnings),
            'reporthash' => $reporthash,

            'items' => $items,
            'warnings' => array_merge($warnings, $errors),
        ];
    }

    /**
     * Read a JSON file as an associative array.
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
     * Return a nested string value from an array.
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
     * @param int $categoryid Moodle course category id.
     * @param string $courseprefix Optional course idnumber prefix.
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
     * Derive read-only suggested sync action.
     *
     * @param array $errors Item errors.
     * @param bool $atlasexists Whether Atlas file exists.
     * @param bool $categoryexists Whether Moodle category exists.
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
     * Build short item summary.
     *
     * @param string $plannedaction Planned action.
     * @param array $warnings Item warnings.
     * @param array $errors Item errors.
     * @return string
     */
    private static function build_item_summary(string $plannedaction, array $warnings, array $errors): string {
        if (count($errors) > 0) {
            return 'Review required before sync.';
        }

        if (count($warnings) > 0) {
            return 'Sync report contains warnings.';
        }

        if ($plannedaction === 'noop') {
            return 'No sync action required.';
        }

        return 'Sync action suggested: ' . $plannedaction . '.';
    }

    /**
     * Summarise report items.
     *
     * @param array $items Report items.
     * @return array
     */
    private static function summarise_items(array $items): array {
        $summary = [
            'facultycount' => count($items),
            'voiecount' => 0,
            'categorycount' => 0,
            'coursecount' => 0,
            'missingcategorycount' => 0,
            'missinghubcount' => 0,
            'errorcount' => 0,
            'warningcount' => 0,
        ];

        foreach ($items as $item) {
            if (!empty($item['atlas_exists'])) {
                $summary['voiecount']++;
            }

            if (!empty($item['category_exists'])) {
                $summary['categorycount']++;
            } else {
                $summary['missingcategorycount']++;
            }

            if (empty($item['hub_exists'])) {
                $summary['missinghubcount']++;
            }

            $summary['coursecount'] += (int)($item['course_count'] ?? 0);
            $summary['errorcount'] += count($item['errors'] ?? []);
            $summary['warningcount'] += count($item['warnings'] ?? []);
        }

        return $summary;
    }

    /**
     * Derive report status.
     *
     * @param array $items Report items.
     * @param array $warnings Top-level warnings.
     * @param array $errors Top-level errors.
     * @return string
     */
    private static function derive_status(array $items, array $warnings, array $errors): string {
        if (count($errors) > 0) {
            return 'error';
        }

        foreach ($items as $item) {
            if (count($item['errors'] ?? []) > 0) {
                return 'error';
            }
        }

        if (count($warnings) > 0) {
            return 'warning';
        }

        foreach ($items as $item) {
            if (count($item['warnings'] ?? []) > 0) {
                return 'warning';
            }
        }

        return 'ok';
    }

    /**
     * Build a Moodle external warning item.
     *
     * @param string $code Warning code.
     * @param int $itemid Related item id, if any.
     * @param string $message Warning message.
     * @return array
     */
    private static function make_warning(string $code, int $itemid, string $message): array {
        return [
            'item' => 'faculty_sync_report',
            'itemid' => $itemid,
            'warningcode' => $code,
            'message' => $message,
        ];
    }
}