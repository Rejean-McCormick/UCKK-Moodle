<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Course preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Seed handler for courses.json.
 *
 * This class validates and applies Moodle course records from the UCKK academic
 * registry. It only owns Moodle course shell creation/update. It does not create
 * activities, award badges, certify competencies, run AI decisions, or bypass
 * archive/human-validation workflows.
 */
final class course_seed {
    /** Component owning this handler. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'courses';

    /** Target type. */
    public const TARGET_TYPE = 'course';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Preset version. */
    public const VERSION = 2026051200;

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Status: warning. */
    public const STATUS_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /** Default course language. */
    private const DEFAULT_LANGUAGE = 'fr';

    /** Default course format. */
    private const DEFAULT_FORMAT = 'uckk';

    /** Metadata manager marker. */
    private const METADATA_MANAGED_BY = 'managedby';

    /** Manager marker value. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Config prefix for seeded course definitions. */
    private const CONFIG_PREFIX = 'course_';

    /**
     * Validate course preset items.
     *
     * @param array<int, mixed> $items Course preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Course preset validation completed.');
        $categorymap = $this->build_category_map($options);

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                'Course preset is empty.',
                ['preset' => self::PRESET]
            );

            return $this->finish_result($result);
        }

        $seenkeys = [];
        $seenshortnames = [];
        $seenidnumbers = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;

            if ($item['key'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing key.');
            }

            if ($item['fullname'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing fullname.');
            }

            if ($item['shortname'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing shortname.');
            }

            if ($item['idnumber'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing idnumber.');
            }

            if ($item['category'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing category.');
            } else if (!$this->category_known($item['category'], $categorymap)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Unknown course category: ' . $item['category'],
                    ['category' => $item['category']]
                );
            }

            if ($item['format'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Course is missing format.');
            } else if (!$this->course_format_exists($item['format'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'Course format is not installed or not discoverable yet: ' . $item['format'],
                    ['format' => $item['format']]
                );
            }

            $this->check_duplicate($result, $seenkeys, 'key', $item['key'], $targetkey);
            $this->check_duplicate($result, $seenshortnames, 'shortname', $item['shortname'], $targetkey);
            $this->check_duplicate($result, $seenidnumbers, 'idnumber', $item['idnumber'], $targetkey);

            $existing = $this->get_existing_course($item);

            if ($existing !== null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    'Course exists and would be updated: ' . $item['shortname'],
                    ['courseid' => (int)$existing->id]
                );
            } else {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    'Course would be created: ' . $item['shortname']
                );
            }

            $this->increment($result, 'skipped');
        }

        return $this->finish_result($result);
    }

    /**
     * Apply course preset items.
     *
     * @param array<int, mixed> $items Course preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);

        $validation = $this->validate($items, $options);

        if ($this->result_has_errors($validation)) {
            return $validation;
        }

        $result = $this->new_result(
            ($dryrun || $rollbackplan)
                ? 'Course seed dry run completed.'
                : 'Course seed completed.'
        );

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;
            $categoryid = $this->resolve_category_id($item['category']);

            if ($categoryid <= 0) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'Cannot apply course because category does not exist in Moodle: ' . $item['category'],
                    ['category' => $item['category']]
                );
                continue;
            }

            $existing = $this->get_existing_course($item);

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    $existing === null
                        ? 'Course would be created: ' . $item['shortname']
                        : 'Course would be updated: ' . $item['shortname'],
                    [
                        'existingid' => $existing ? (int)$existing->id : 0,
                        'course' => $item,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($existing === null) {
                $course = $this->create_course($item, $categoryid);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course created: ' . $item['shortname'],
                    ['courseid' => (int)$course->id]
                );

                $this->increment($result, 'created');
            } else {
                $course = $this->update_course((int)$existing->id, $item, $categoryid);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course updated: ' . $item['shortname'],
                    ['courseid' => (int)$course->id]
                );

                $this->increment($result, 'updated');
            }

            $this->set_config_marker($item, (int)$course->id);
        }

        return $this->finish_result($result);
    }

    /**
     * Reset seed-managed courses.
     *
     * This reset is intentionally conservative. It hides courses by default and
     * only deletes them when delete=true and confirm=true are both supplied.
     *
     * @param array<int, mixed> $items Course preset rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $delete = !empty($options['delete']);
        $confirmed = !empty($options['confirm']);

        $result = $this->new_result(
            $dryrun ? 'Course reset dry run completed.' : 'Course reset completed.'
        );

        if ($delete && !$confirmed && !$dryrun) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Deleting courses requires confirm=true.'
            );

            return $this->finish_result($result);
        }

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;
            $course = $this->get_existing_course($item);

            if ($course === null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    'Course already absent: ' . $item['shortname']
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    $delete
                        ? 'Course would be deleted: ' . $item['shortname']
                        : 'Course would be hidden: ' . $item['shortname'],
                    ['courseid' => (int)$course->id]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($delete) {
                delete_course((int)$course->id, false);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course deleted: ' . $item['shortname'],
                    ['courseid' => (int)$course->id]
                );
            } else {
                $record = new stdClass();
                $record->id = (int)$course->id;
                $record->visible = 0;
                $record->timemodified = time();
                $DB->update_record('course', $record);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course hidden: ' . $item['shortname'],
                    ['courseid' => (int)$course->id]
                );
            }

            unset_config(self::CONFIG_PREFIX . $item['key'] . '_id', self::COMPONENT);
            unset_config(self::CONFIG_PREFIX . $item['key'] . '_definition', self::COMPONENT);
            $this->increment($result, 'updated');
        }

        return $this->finish_result($result);
    }

    /**
     * Export seed-managed course definitions.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];

        $records = $DB->get_records('course', null, 'sortorder ASC, shortname ASC', '*', 0, 10000);

        foreach ($records as $course) {
            if ((int)$course->id === SITEID) {
                continue;
            }

            $key = $this->key_from_course_record($course);
            $stored = (string)get_config(self::COMPONENT, self::CONFIG_PREFIX . $key . '_definition');
            $decoded = json_decode($stored, true);

            if (is_array($decoded)) {
                $items[] = $this->normalise_item($decoded);
                continue;
            }

            if ($this->looks_like_uckk_course($course)) {
                $items[] = $this->course_record_to_item($course);
            }
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => self::VERSION,
            'items' => $items,
        ];
    }

    /**
     * Normalise a raw course item.
     *
     * @param mixed $item Raw item.
     * @param int $index Row index.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item, int $index = 0): array {
        if ($item instanceof stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $moodle = $this->normalise_assoc($item['moodle'] ?? []);
        $metadata = $this->normalise_assoc($item['metadata'] ?? []);

        foreach ([
            'id',
            'code',
            'title',
            'short_title',
            'course_type',
            'course_type_label',
            'status',
            'requirement_default',
            'catalog_status_label',
            'academic_block',
            'learning_outcomes',
            'assessment',
            'ai_metadata',
            'source',
            'source_additional',
        ] as $field) {
            if (array_key_exists($field, $item) && !array_key_exists($field, $metadata)) {
                $metadata[$field] = $item[$field];
            }
        }

        $shortname = $this->first_string(
            $item['shortname'] ?? null,
            $moodle['shortname'] ?? null,
            $item['code'] ?? null,
            $item['idnumber'] ?? null
        );

        $idnumber = $this->first_string(
            $item['idnumber'] ?? null,
            $moodle['idnumber'] ?? null,
            $item['code'] ?? null,
            $shortname
        );

        $fullname = $this->first_string(
            $item['fullname'] ?? null,
            $moodle['fullname'] ?? null,
            $item['title'] ?? null,
            $item['name'] ?? null,
            $item['short_title'] ?? null,
            $shortname
        );

        $key = $this->first_string(
            $item['key'] ?? null,
            $item['id'] ?? null,
            $item['code'] ?? null,
            $shortname,
            $idnumber,
            'course_' . $index
        );

        $category = $this->first_string(
            $item['category'] ?? null,
            $item['category_idnumber'] ?? null,
            $moodle['category'] ?? null,
            $moodle['category_idnumber'] ?? null,
            $item['category_path'] ?? null,
            $moodle['category_path'] ?? null
        );

        $summary = $this->first_string(
            $item['summary'] ?? null,
            $moodle['summary'] ?? null,
            $item['description'] ?? null
        );

        $format = $this->first_string(
            $item['format'] ?? null,
            $moodle['format'] ?? null,
            self::DEFAULT_FORMAT
        );

        $visible = $this->normalise_visible($item['visible'] ?? $moodle['visible'] ?? 0);
        $lang = clean_param($this->first_string($item['lang'] ?? null, $moodle['lang'] ?? null, self::DEFAULT_LANGUAGE), PARAM_ALPHANUMEXT);
        $enablecompletion = $this->normalise_bool($item['enablecompletion'] ?? $moodle['enablecompletion'] ?? true);
        $summaryformat = $this->normalise_int($item['summaryformat'] ?? $moodle['summaryformat'] ?? FORMAT_HTML, FORMAT_HTML);
        $startdate = $this->normalise_int($item['startdate'] ?? $moodle['startdate'] ?? 0, 0);
        $enddate = $this->normalise_int($item['enddate'] ?? $moodle['enddate'] ?? 0, 0);
        $sortorder = $this->normalise_int($item['sortorder'] ?? $moodle['sortorder'] ?? (($index + 1) * 10), (($index + 1) * 10));

        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;
        $metadata['source_preset'] = self::PRESET;

        return [
            'key' => clean_param($this->normalise_key($key), PARAM_ALPHANUMEXT),
            'fullname' => clean_param($fullname, PARAM_TEXT),
            'shortname' => clean_param($shortname, PARAM_TEXT),
            'idnumber' => clean_param($idnumber, PARAM_TEXT),
            'category' => clean_param($category, PARAM_TEXT),
            'category_idnumber' => clean_param($this->first_string($item['category_idnumber'] ?? null, $moodle['category_idnumber'] ?? null, $category), PARAM_TEXT),
            'category_path' => clean_param($this->first_string($item['category_path'] ?? null, $moodle['category_path'] ?? null), PARAM_TEXT),
            'format' => clean_param($format, PARAM_PLUGIN),
            'summary' => $summary,
            'summaryformat' => $summaryformat,
            'visible' => $visible,
            'lang' => $lang,
            'enablecompletion' => $enablecompletion,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'template' => clean_param($this->first_string($item['template'] ?? null, $moodle['template'] ?? null), PARAM_ALPHANUMEXT),
            'sections' => $this->normalise_list($item['sections'] ?? []),
            'completion' => $this->normalise_assoc($item['completion'] ?? []),
            'sortorder' => $sortorder,
            'metadata' => $metadata,
        ];
    }

    /**
     * Create a Moodle course.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $categoryid Category id.
     * @return stdClass
     */
    private function create_course(array $item, int $categoryid): stdClass {
        $course = $this->course_data_object($item, $categoryid);
        $created = create_course($course);

        return is_object($created) ? $created : (object)['id' => (int)$created];
    }

    /**
     * Update a Moodle course.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $item Normalised item.
     * @param int $categoryid Category id.
     * @return stdClass
     */
    private function update_course(int $courseid, array $item, int $categoryid): stdClass {
        global $DB;

        $course = $this->course_data_object($item, $categoryid);
        $course->id = $courseid;

        update_course($course);

        return $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    }

    /**
     * Build a Moodle course data object.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $categoryid Category id.
     * @return stdClass
     */
    private function course_data_object(array $item, int $categoryid): stdClass {
        $course = new stdClass();

        $course->fullname = $item['fullname'];
        $course->shortname = $item['shortname'];
        $course->idnumber = $item['idnumber'];
        $course->category = $categoryid;
        $course->summary = $item['summary'];
        $course->summaryformat = $item['summaryformat'];
        $course->format = $item['format'];
        $course->visible = $item['visible'];
        $course->lang = $item['lang'];
        $course->enablecompletion = $item['enablecompletion'] ? 1 : 0;
        $course->startdate = $item['startdate'];
        $course->enddate = $item['enddate'];

        // Avoid unintended defaults when running from CLI.
        $course->newsitems = 0;
        $course->numsections = 0;
        $course->showgrades = 1;
        $course->showreports = 0;
        $course->maxbytes = 0;
        $course->groupmode = 0;
        $course->groupmodeforce = 0;
        $course->defaultgroupingid = 0;

        return $course;
    }

    /**
     * Get an existing course by idnumber or shortname.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return stdClass|null
     */
    private function get_existing_course(array $item): ?stdClass {
        global $DB;

        if ($item['idnumber'] !== '') {
            $course = $DB->get_record('course', ['idnumber' => $item['idnumber']], '*', IGNORE_MISSING);

            if ($course) {
                return $course;
            }
        }

        if ($item['shortname'] !== '') {
            $course = $DB->get_record('course', ['shortname' => $item['shortname']], '*', IGNORE_MISSING);

            if ($course) {
                return $course;
            }
        }

        return null;
    }

    /**
     * Resolve a category id by idnumber/name/path.
     *
     * @param string $category Category reference.
     * @return int
     */
    private function resolve_category_id(string $category): int {
        global $DB;

        $category = trim($category);

        if ($category === '') {
            return 0;
        }

        $record = $DB->get_record('course_categories', ['idnumber' => $category], 'id', IGNORE_MISSING);

        if ($record) {
            return (int)$record->id;
        }

        $record = $DB->get_record('course_categories', ['name' => $category], 'id', IGNORE_MISSING);

        if ($record) {
            return (int)$record->id;
        }

        if (str_contains($category, '/')) {
            $parts = array_values(array_filter(array_map('trim', explode('/', $category))));

            if (!empty($parts)) {
                $record = $DB->get_record('course_categories', ['name' => end($parts)], 'id', IGNORE_MISSING);

                if ($record) {
                    return (int)$record->id;
                }
            }
        }

        return 0;
    }

    /**
     * Build category map from database and categories.json.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, true>
     */
    private function build_category_map(array $options): array {
        global $DB;

        $map = [];

        if ($DB->get_manager()->table_exists('course_categories')) {
            $records = $DB->get_records('course_categories');

            foreach ($records as $record) {
                foreach (['idnumber', 'name'] as $field) {
                    if (!empty($record->{$field})) {
                        $map[(string)$record->{$field}] = true;
                    }
                }
            }
        }

        foreach ($this->load_categories_from_preset($options) as $category) {
            foreach (['idnumber', 'key', 'id', 'name', 'path', 'legacy_idnumber'] as $field) {
                if (!empty($category[$field])) {
                    $map[(string)$category[$field]] = true;
                }
            }

            if (!empty($category['metadata']) && is_array($category['metadata'])) {
                foreach (['legacy_idnumber', 'course_prefix'] as $field) {
                    if (!empty($category['metadata'][$field])) {
                        $map[(string)$category['metadata'][$field]] = true;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Load categories.json from preset data/path when available.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<int, array<string, mixed>>
     */
    private function load_categories_from_preset(array $options): array {
        if (!empty($options['allpresets']['categories']['items']) && is_array($options['allpresets']['categories']['items'])) {
            return $options['allpresets']['categories']['items'];
        }

        if (!empty($options['categories']['items']) && is_array($options['categories']['items'])) {
            return $options['categories']['items'];
        }

        if (!empty($options['presetdata']['categories']['items']) && is_array($options['presetdata']['categories']['items'])) {
            return $options['presetdata']['categories']['items'];
        }

        $presetpath = (string)($options['presetpath'] ?? '');

        if ($presetpath === '') {
            return [];
        }

        $path = rtrim($presetpath, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . 'categories.json';

        if (!is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string)file_get_contents($path), true);

        if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
            return [];
        }

        return $decoded['items'];
    }

    /**
     * Whether a category is known.
     *
     * @param string $category Category reference.
     * @param array<string, true> $categorymap Category map.
     * @return bool
     */
    private function category_known(string $category, array $categorymap): bool {
        $category = trim($category);

        if ($category === '') {
            return false;
        }

        if (isset($categorymap[$category])) {
            return true;
        }

        return $this->resolve_category_id($category) > 0;
    }

    /**
     * Whether a course format exists.
     *
     * @param string $format Format plugin name.
     * @return bool
     */
    private function course_format_exists(string $format): bool {
        global $CFG;

        $format = trim($format);

        if ($format === '') {
            return false;
        }

        if ($format === 'topics' || $format === 'weeks' || $format === 'singleactivity' || $format === 'social' || $format === 'site') {
            return true;
        }

        return is_readable($CFG->dirroot . '/course/format/' . $format . '/format.php')
            || is_readable($CFG->dirroot . '/course/format/' . $format . '/lib.php')
            || is_dir($CFG->dirroot . '/course/format/' . $format);
    }

    /**
     * Convert a Moodle course record to seed item.
     *
     * @param stdClass $course Course record.
     * @return array<string, mixed>
     */
    private function course_record_to_item(stdClass $course): array {
        global $DB;

        $category = $DB->get_record('course_categories', ['id' => $course->category], '*', IGNORE_MISSING);
        $categoryref = $category ? ((string)$category->idnumber !== '' ? (string)$category->idnumber : (string)$category->name) : '';

        return [
            'key' => $this->key_from_course_record($course),
            'fullname' => (string)$course->fullname,
            'shortname' => (string)$course->shortname,
            'idnumber' => (string)$course->idnumber,
            'category' => $categoryref,
            'category_idnumber' => $categoryref,
            'format' => (string)$course->format,
            'summary' => (string)$course->summary,
            'summaryformat' => (int)$course->summaryformat,
            'visible' => (int)$course->visible,
            'lang' => (string)($course->lang ?? self::DEFAULT_LANGUAGE),
            'enablecompletion' => !empty($course->enablecompletion),
            'startdate' => (int)($course->startdate ?? 0),
            'enddate' => (int)($course->enddate ?? 0),
            'sections' => [],
            'completion' => [],
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_preset' => self::PRESET,
            ],
        ];
    }

    /**
     * Determine whether a course appears to be UCKK-owned.
     *
     * @param stdClass $course Course record.
     * @return bool
     */
    private function looks_like_uckk_course(stdClass $course): bool {
        $shortname = strtoupper((string)($course->shortname ?? ''));
        $idnumber = strtoupper((string)($course->idnumber ?? ''));

        return str_starts_with($shortname, 'UCKK-')
            || str_starts_with($idnumber, 'UCKK-');
    }

    /**
     * Build key from course record.
     *
     * @param stdClass $course Course record.
     * @return string
     */
    private function key_from_course_record(stdClass $course): string {
        $source = (string)($course->idnumber ?: $course->shortname ?: ('course_' . $course->id));

        return $this->normalise_key($source);
    }

    /**
     * Store a seed-managed course config marker.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $courseid Course id.
     */
    private function set_config_marker(array $item, int $courseid): void {
        if ($item['key'] === '') {
            return;
        }

        set_config(self::CONFIG_PREFIX . $item['key'] . '_id', $courseid, self::COMPONENT);
        set_config(
            self::CONFIG_PREFIX . $item['key'] . '_definition',
            json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Check duplicate value.
     *
     * @param validation_result $result Result.
     * @param array<string, true> $seen Seen values.
     * @param string $field Field.
     * @param string $value Value.
     * @param string $targetkey Target key.
     */
    private function check_duplicate(validation_result $result, array &$seen, string $field, string $value, string $targetkey): void {
        if ($value === '') {
            return;
        }

        if (isset($seen[$value])) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                $targetkey,
                'Duplicate course ' . $field . ': ' . $value,
                [$field => $value]
            );
        }

        $seen[$value] = true;
    }

    /**
     * Get first non-empty string.
     *
     * @param mixed ...$values Values.
     * @return string
     */
    private function first_string(mixed ...$values): string {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (is_scalar($value)) {
                $text = trim((string)$value);

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    /**
     * Normalise key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/^[a-z]+:/', '', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Normalise associative data.
     *
     * @param mixed $value Value.
     * @return array<string, mixed>
     */
    private function normalise_assoc(mixed $value): array {
        if ($value instanceof stdClass) {
            return (array)$value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Normalise list.
     *
     * @param mixed $value Value.
     * @return array<int, mixed>
     */
    private function normalise_list(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    /**
     * Normalise visibility.
     *
     * @param mixed $value Value.
     * @return int
     */
    private function normalise_visible(mixed $value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value ? 1 : 0;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            if (in_array($value, ['1', 'true', 'yes', 'visible', 'active', 'public'], true)) {
                return 1;
            }

            if (in_array($value, ['0', 'false', 'no', 'hidden', 'inactive', 'draft'], true)) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * Normalise boolean.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'enabled', 'on'], true);
        }

        return false;
    }

    /**
     * Normalise integer.
     *
     * @param mixed $value Value.
     * @param int $default Default.
     * @return int
     */
    private function normalise_int(mixed $value, int $default): int {
        if (is_numeric($value)) {
            return (int)$value;
        }

        return $default;
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Create validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return validation_result::from_data([
            'status' => self::STATUS_COMPLETED,
            'summary' => $summary,
            'counts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'messages' => [],
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ]);
    }

    /**
     * Add message.
     *
     * @param validation_result $result Result.
     * @param string $severity Severity.
     * @param string $targetkey Target key.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $result->add_message(
            $severity,
            $message,
            self::COMPONENT,
            self::PRESET,
            self::TARGET_TYPE,
            $targetkey,
            $metadata
        );

        if ($severity === self::SEVERITY_ERROR || $severity === self::SEVERITY_BLOCKER) {
            $result->increment('failed');
        }
    }

    /**
     * Increment count.
     *
     * @param validation_result $result Result.
     * @param string $key Count key.
     */
    private function increment(validation_result $result, string $key): void {
        $result->increment($key);
    }

    /**
     * Result has errors.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_errors(validation_result $result): bool {
        return $result->has_errors();
    }

    /**
     * Finalise result.
     *
     * @param validation_result $result Result.
     * @return validation_result
     */
    private function finish_result(validation_result $result): validation_result {
        return $result->complete($result->get_summary());
    }
}