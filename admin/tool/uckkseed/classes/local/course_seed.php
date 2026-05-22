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
require_once($CFG->dirroot . '/course/modlib.php');

/**
 * Seed handler for courses.json.
 *
 * This class validates and applies Moodle course records from the UCKK academic
 * registry. It owns Moodle course shell creation/update plus template-driven
 * section and activity creation/update. It does not award badges, certify
 * competencies, run AI decisions, or bypass archive/human-validation workflows.
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
            $item = $this->normalise_item($this->apply_template_to_raw_item($rawitem, $index, $options), $index);
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
            $item = $this->normalise_item($this->apply_template_to_raw_item($rawitem, $index, $options), $index);
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

            $this->apply_course_format_options((int)$course->id, $item);
            $sectionstats = $this->apply_course_sections((int)$course->id, $item);

            if (($sectionstats['created'] + $sectionstats['updated'] + $sectionstats['skipped']) > 0) {
                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course sections applied: ' . $item['shortname'],
                    [
                        'courseid' => (int)$course->id,
                        'sectionscreated' => $sectionstats['created'],
                        'sectionsupdated' => $sectionstats['updated'],
                        'sectionskipped' => $sectionstats['skipped'],
                    ]
                );
            } else {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'No course sections found in merged template definition: ' . $item['shortname'],
                    [
                        'courseid' => (int)$course->id,
                        'template' => $item['template'],
                    ]
                );
            }

            $activitystats = $this->apply_course_activities((int)$course->id, $item);

            if (($activitystats['created'] + $activitystats['updated'] + $activitystats['skipped']) > 0) {
                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'Course activities applied: ' . $item['shortname'],
                    [
                        'courseid' => (int)$course->id,
                        'activitiescreated' => $activitystats['created'],
                        'activitiesupdated' => $activitystats['updated'],
                        'activitiesskipped' => $activitystats['skipped'],
                    ]
                );
            } else {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'No course activities found in merged template definition: ' . $item['shortname'],
                    [
                        'courseid' => (int)$course->id,
                        'template' => $item['template'],
                    ]
                );
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
            $item = $this->normalise_item($this->apply_template_to_raw_item($rawitem, $index, $options), $index);
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

        $sections = $this->normalise_sections($item['sections'] ?? []);
        $courseformatoptions = $this->normalise_assoc($item['courseformatoptions'] ?? $moodle['courseformatoptions'] ?? []);
        $numsections = $this->normalise_numsections($item['numsections'] ?? $moodle['numsections'] ?? 0, $sections);

        $visible = $this->normalise_visible($item['visible'] ?? $moodle['visible'] ?? 1, 1);
        $lang = clean_param($this->first_string($item['lang'] ?? null, $moodle['lang'] ?? null, self::DEFAULT_LANGUAGE), PARAM_ALPHANUMEXT);
        $enablecompletion = $this->normalise_bool($item['enablecompletion'] ?? $moodle['enablecompletion'] ?? true);
        $summaryformat = $this->normalise_int($item['summaryformat'] ?? $moodle['summaryformat'] ?? FORMAT_HTML, FORMAT_HTML);
        $startdate = $this->normalise_int($item['startdate'] ?? $moodle['startdate'] ?? 0, 0);
        $enddate = $this->normalise_int($item['enddate'] ?? $moodle['enddate'] ?? 0, 0);
        $sortorder = $this->normalise_int($item['sortorder'] ?? $moodle['sortorder'] ?? (($index + 1) * 10), (($index + 1) * 10));

        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;
        $metadata['source_preset'] = self::PRESET;

        if (!empty($item['template'])) {
            $metadata['template'] = (string)$item['template'];
        }

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
            'numsections' => $numsections,
            'sections' => $sections,
            'courseformatoptions' => $courseformatoptions,
            'activities' => $this->normalise_activities($item['activities'] ?? []),
            'completion' => $this->normalise_assoc($item['completion'] ?? []),
            'sortorder' => $sortorder,
            'metadata' => $metadata,
        ];
    }

    /**
     * Apply the course template referenced by a raw item before normalisation.
     *
     * @param mixed $rawitem Raw course row.
     * @param int $index Row index.
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    private function apply_template_to_raw_item(mixed $rawitem, int $index, array $options): array {
        if ($rawitem instanceof stdClass) {
            $rawitem = (array)$rawitem;
        }

        if (!is_array($rawitem)) {
            $rawitem = [];
        }

        $item = $rawitem;
        $moodle = $this->normalise_assoc($item['moodle'] ?? []);

        $templatekey = $this->first_string(
            $item['template'] ?? null,
            $moodle['template'] ?? null,
            $item['course_template'] ?? null,
            $moodle['course_template'] ?? null
        );

        if ($templatekey === '') {
            return $item;
        }

        $templates = $this->load_course_templates_from_preset($options);
        $template = $templates[$templatekey] ?? null;

        if (!is_array($template)) {
            return $item;
        }

        $defaults = $this->normalise_assoc($template['defaults'] ?? []);
        $defaultmoodle = $this->normalise_assoc($defaults['moodle'] ?? []);

        foreach ($defaults as $field => $value) {
            if ($field === 'moodle') {
                continue;
            }

            if ($field === 'courseformatoptions') {
                $current = $this->normalise_assoc($item['courseformatoptions'] ?? $moodle['courseformatoptions'] ?? []);
                $item['courseformatoptions'] = array_replace($this->normalise_assoc($value), $current);
                continue;
            }

            if (!$this->default_field_has_value($field, $item[$field] ?? null)) {
                $item[$field] = $value;
            }
        }

        if (!empty($defaultmoodle)) {
            foreach ($defaultmoodle as $field => $value) {
                if (!$this->default_field_has_value($field, $moodle[$field] ?? null)) {
                    $moodle[$field] = $value;
                }
            }

            $item['moodle'] = $moodle;
        }

        if (empty($item['sections']) && !empty($template['sections']) && is_array($template['sections'])) {
            $item['sections'] = $template['sections'];
        }

        if (empty($item['activities']) && !empty($template['activities']) && is_array($template['activities'])) {
            $item['activities'] = $template['activities'];
        }

        if (empty($item['completion']) && !empty($template['completion']) && is_array($template['completion'])) {
            $item['completion'] = $template['completion'];
        }

        $metadata = $this->normalise_assoc($item['metadata'] ?? []);
        $metadata['template'] = $templatekey;
        $metadata['template_applied'] = true;

        if (!empty($template['metadata']) && is_array($template['metadata'])) {
            $metadata['template_metadata'] = $template['metadata'];
        }

        $item['metadata'] = $metadata;
        $item['template'] = $templatekey;

        return $item;
    }

    /**
     * Whether a field value should prevent template default replacement.
     *
     * @param string $field Field name.
     * @param mixed $value Value.
     * @return bool
     */
    private function default_field_has_value(string $field, mixed $value): bool {
        if ($value === null) {
            return false;
        }

        if ($field === 'visible') {
            if (is_bool($value) || is_int($value)) {
                return (int)$value === 1;
            }

            if (is_string($value)) {
                return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'visible', 'active', 'public'], true);
            }

            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
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
     * Apply course-level format options.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $item Normalised item.
     */
    private function apply_course_format_options(int $courseid, array $item): void {
        $options = $this->normalise_assoc($item['courseformatoptions'] ?? []);

        foreach ($options as $name => $value) {
            $this->set_course_format_option($courseid, (string)$item['format'], 0, (string)$name, $value);
        }
    }

    /**
     * Create or update course sections from the merged template definition.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $item Normalised item.
     * @return array{created:int, updated:int, skipped:int}
     */
    private function apply_course_sections(int $courseid, array $item): array {
        global $DB;

        $sections = $this->normalise_sections($item['sections'] ?? []);

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        if (empty($sections)) {
            return $stats;
        }

        $sectionnumbers = [];

        foreach ($sections as $section) {
            $sectionnumbers[] = (int)$section['number'];
        }

        $sectionnumbers = array_values(array_unique($sectionnumbers));
        sort($sectionnumbers, SORT_NUMERIC);

        course_create_sections_if_missing($courseid, $sectionnumbers);

        $existing = $DB->get_records(
            'course_sections',
            ['course' => $courseid],
            'section ASC',
            'id,course,section,name,summary,summaryformat,sequence,visible,availability,timemodified'
        );

        $bysection = [];

        foreach ($existing as $record) {
            $bysection[(int)$record->section] = $record;
        }

        foreach ($sections as $section) {
            $sectionnumber = (int)$section['number'];

            if (isset($bysection[$sectionnumber])) {
                $record = $bysection[$sectionnumber];
                $created = false;
            } else {
                $record = $this->create_section_record($courseid, $sectionnumber);
                $created = true;
            }

            $summary = (string)$section['summary'];
            $summaryformat = (int)$section['summaryformat'];
            $name = trim((string)$section['name']);
            $visible = (int)$section['visible'];

            $record->name = $name !== '' ? $name : null;
            $record->summary = $summary;
            $record->summaryformat = $summaryformat;
            $record->visible = $visible;
            $record->timemodified = time();

            $DB->update_record('course_sections', $record);

            if (!empty($section['options']) && is_array($section['options'])) {
                foreach ($section['options'] as $optionname => $optionvalue) {
                    $this->set_course_format_option(
                        $courseid,
                        (string)$item['format'],
                        (int)$record->id,
                        (string)$optionname,
                        $optionvalue
                    );
                }
            }

            if ($created) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }
        }

        rebuild_course_cache($courseid, true);

        return $stats;
    }

    /**
     * Create or update course module activities from the merged template definition.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $item Normalised item.
     * @return array{created:int, updated:int, skipped:int}
     */
    private function apply_course_activities(int $courseid, array $item): array {
        global $DB;

        $activities = $this->normalise_activities($item['activities'] ?? []);

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        if (empty($activities)) {
            return $stats;
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        foreach ($activities as $activity) {
            $modname = $this->activity_component_to_modname((string)$activity['component']);

            if ($modname === '') {
                $stats['skipped']++;
                continue;
            }

            if (!$DB->record_exists('modules', ['name' => $modname])) {
                $stats['skipped']++;
                continue;
            }

            $sectionnumber = $this->resolve_activity_section_number($courseid, $activity, $item);
            $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnumber], '*', IGNORE_MISSING);

            if (!$section) {
                course_create_sections_if_missing($courseid, [$sectionnumber]);
                $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnumber], '*', IGNORE_MISSING);
            }

            if (!$section) {
                $stats['skipped']++;
                continue;
            }

            $existing = $this->get_existing_activity($courseid, $modname, (string)$activity['name']);

            if ($existing !== null) {
                $this->update_existing_activity_instance($existing, $activity, $item);
                $stats['updated']++;
                continue;
            }

            $moduleinfo = $this->build_activity_moduleinfo($courseid, $modname, $sectionnumber, $activity, $item);
            add_moduleinfo($moduleinfo, $course);
            $stats['created']++;
        }

        rebuild_course_cache($courseid, true);

        return $stats;
    }

    /**
     * Build a Moodle moduleinfo object for add_moduleinfo().
     *
     * @param int $courseid Course id.
     * @param string $modname Moodle module name.
     * @param int $sectionnumber Section number.
     * @param array<string, mixed> $activity Normalised activity.
     * @param array<string, mixed> $item Normalised course item.
     * @return stdClass
     */
    private function build_activity_moduleinfo(
        int $courseid,
        string $modname,
        int $sectionnumber,
        array $activity,
        array $item
    ): stdClass {
        global $DB;

        $module = $DB->get_record('modules', ['name' => $modname], '*', MUST_EXIST);
        $defaults = $this->normalise_assoc($activity['defaults'] ?? []);
        $metadata = [
            self::METADATA_MANAGED_BY => self::MANAGED_BY,
            'source_preset' => self::PRESET,
            'course_key' => (string)$item['key'],
            'course_shortname' => (string)$item['shortname'],
            'course_template' => (string)$item['template'],
            'activity_key' => (string)$activity['key'],
            'activity_component' => (string)$activity['component'],
            'activity_section' => (string)$activity['section'],
        ];

        $moduleinfo = new stdClass();
        $moduleinfo->course = $courseid;
        $moduleinfo->module = (int)$module->id;
        $moduleinfo->modulename = $modname;
        $moduleinfo->add = $modname;
        $moduleinfo->section = $sectionnumber;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->showdescription = 0;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->name = (string)$activity['name'];
        $moduleinfo->intro = (string)$activity['intro'];
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->completion = $this->activity_has_completion_rules($activity)
            ? $this->completion_constant('COMPLETION_TRACKING_AUTOMATIC', 2)
            : $this->completion_constant('COMPLETION_TRACKING_NONE', 0);
        $moduleinfo->completionexpected = 0;
        $moduleinfo->availabilityconditionsjson = '{"op":"&","c":[],"showc":[]}';
        $moduleinfo->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($defaults as $name => $value) {
            $moduleinfo->{$name} = $value;
        }

        if ($modname === 'uckkchallenge') {
            $moduleinfo->challengetype = $this->first_string($moduleinfo->challengetype ?? null, 'evidence');
            $moduleinfo->statement = $this->first_string($moduleinfo->statement ?? null, $moduleinfo->intro ?? null);
            $moduleinfo->statementformat = FORMAT_HTML;
            $moduleinfo->status = $this->first_string($moduleinfo->status ?? null, 'active');
            $moduleinfo->visibility = $this->first_string($moduleinfo->visibility ?? null, 'course');
            $moduleinfo->archivepolicy = $this->first_string($moduleinfo->archivepolicy ?? null, 'summary');
            $moduleinfo->completionrequiresubmission = $this->normalise_visible($moduleinfo->completionrequiresubmission ?? 0, 0);
            $moduleinfo->completionrequirevalidation = $this->normalise_visible($moduleinfo->completionrequirevalidation ?? 0, 0);
        } else if ($modname === 'uckkassembly') {
            $moduleinfo->assemblytype = $this->first_string($moduleinfo->assemblytype ?? null, 'savoirs');
            $moduleinfo->purpose = $this->first_string($moduleinfo->purpose ?? null, $moduleinfo->intro ?? null);
            $moduleinfo->status = $this->first_string($moduleinfo->status ?? null, 'active');
            $moduleinfo->visibility = $this->first_string($moduleinfo->visibility ?? null, 'course');
            $moduleinfo->archivepolicy = $this->first_string($moduleinfo->archivepolicy ?? null, 'summary');
        } else if ($modname === 'uckkarchive') {
            $moduleinfo->archivetype = $this->first_string($moduleinfo->archivetype ?? null, 'course');
            $moduleinfo->status = $this->first_string($moduleinfo->status ?? null, 'active');
            $moduleinfo->visibility = $this->first_string($moduleinfo->visibility ?? null, 'course');
            $moduleinfo->defaultvisibility = $this->first_string($moduleinfo->defaultvisibility ?? null, $moduleinfo->visibility ?? null, 'course');
            $moduleinfo->archivepolicy = $this->first_string($moduleinfo->archivepolicy ?? null, 'validated');

            if (isset($moduleinfo->completionadditem) && !isset($moduleinfo->completionrequireitem)) {
                $moduleinfo->completionrequireitem = $this->normalise_visible($moduleinfo->completionadditem, 0);
            }

            if (isset($moduleinfo->completionvalidateitem) && !isset($moduleinfo->completionrequirevalidation)) {
                $moduleinfo->completionrequirevalidation = $this->normalise_visible($moduleinfo->completionvalidateitem, 0);
            }

            $moduleinfo->completionrequireitem = $this->normalise_visible($moduleinfo->completionrequireitem ?? 0, 0);
            $moduleinfo->completionrequirevalidation = $this->normalise_visible($moduleinfo->completionrequirevalidation ?? 0, 0);
        }

        return $moduleinfo;
    }

    /**
     * Update an existing seeded activity instance without duplicating modules.
     *
     * @param stdClass $existing Existing activity data.
     * @param array<string, mixed> $activity Normalised activity.
     * @param array<string, mixed> $item Normalised course item.
     */
    private function update_existing_activity_instance(stdClass $existing, array $activity, array $item): void {
        global $DB;

        $modname = (string)$existing->modname;
        $table = $modname;
        $columns = $DB->get_columns($table);
        $record = new stdClass();
        $record->id = (int)$existing->instance;
        $record->name = (string)$activity['name'];

        if (isset($columns['intro'])) {
            $record->intro = (string)$activity['intro'];
        }

        if (isset($columns['introformat'])) {
            $record->introformat = FORMAT_HTML;
        }

        $defaults = $this->normalise_assoc($activity['defaults'] ?? []);

        foreach ($defaults as $field => $value) {
            if (isset($columns[$field])) {
                $record->{$field} = $value;
            }
        }

        if ($modname === 'uckkarchive') {
            if (isset($defaults['completionadditem']) && isset($columns['completionrequireitem'])) {
                $record->completionrequireitem = $this->normalise_visible($defaults['completionadditem'], 0);
            }

            if (isset($defaults['completionvalidateitem']) && isset($columns['completionrequirevalidation'])) {
                $record->completionrequirevalidation = $this->normalise_visible($defaults['completionvalidateitem'], 0);
            }

            if (isset($columns['defaultvisibility']) && !isset($record->defaultvisibility)) {
                $record->defaultvisibility = $this->first_string($defaults['visibility'] ?? null, 'course');
            }
        }

        if (isset($columns['metadata'])) {
            $metadata = [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_preset' => self::PRESET,
                'course_key' => (string)$item['key'],
                'course_shortname' => (string)$item['shortname'],
                'course_template' => (string)$item['template'],
                'activity_key' => (string)$activity['key'],
                'activity_component' => (string)$activity['component'],
                'activity_section' => (string)$activity['section'],
            ];
            $record->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (isset($columns['timemodified'])) {
            $record->timemodified = time();
        }

        $DB->update_record($table, $record);
    }

    /**
     * Find an existing activity instance by module type and name in a course.
     *
     * @param int $courseid Course id.
     * @param string $modname Module name.
     * @param string $name Activity name.
     * @return stdClass|null
     */
    private function get_existing_activity(int $courseid, string $modname, string $name): ?stdClass {
        global $DB;

        if (!$this->safe_activity_table($modname) || !$DB->get_manager()->table_exists($modname)) {
            return null;
        }

        $sql = "SELECT cm.id AS cmid,
                       cm.instance AS instance,
                       m.name AS modname,
                       a.name AS name
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {" . $modname . "} a ON a.id = cm.instance
                 WHERE cm.course = :courseid
                   AND m.name = :modname
                   AND a.name = :name";

        $record = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'modname' => $modname,
            'name' => $name,
        ], IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Resolve the course section number for an activity.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $activity Normalised activity.
     * @param array<string, mixed> $item Normalised item.
     * @return int
     */
    private function resolve_activity_section_number(int $courseid, array $activity, array $item): int {
        global $DB;

        $sectionref = trim((string)($activity['section'] ?? ''));

        if ($sectionref !== '' && is_numeric($sectionref)) {
            return max(0, (int)$sectionref);
        }

        $sectionkey = $this->normalise_key($sectionref);
        $aliases = [
            'orientation' => 0,
            'concepts' => 1,
            'matiere_canonique' => 2,
            'mati_re_canonique' => 2,
            'canon' => 2,
            'atelier' => 3,
            'preuves' => 4,
            'preuve' => 4,
            'evidence' => 4,
            'deliberation' => 5,
            'd_lib_ration' => 5,
            'assembly' => 5,
            'livrable' => 6,
            'deliverable' => 6,
            'evaluation' => 7,
            'archive' => 8,
        ];

        if ($sectionkey !== '' && isset($aliases[$sectionkey])) {
            return $aliases[$sectionkey];
        }

        foreach ($this->normalise_sections($item['sections'] ?? []) as $section) {
            $keys = [
                $this->normalise_key((string)($section['key'] ?? '')),
                $this->normalise_key((string)($section['name'] ?? '')),
                (string)($section['number'] ?? ''),
            ];

            if (in_array($sectionkey, $keys, true)) {
                return max(0, (int)$section['number']);
            }
        }

        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC', 'section,name');

        foreach ($sections as $section) {
            $namekey = $this->normalise_key((string)($section->name ?? ''));

            if ($sectionkey !== '' && $sectionkey === $namekey) {
                return max(0, (int)$section->section);
            }
        }

        return 0;
    }

    /**
     * Normalise activity list.
     *
     * @param mixed $value Raw activities.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_activities(mixed $value): array {
        $rawactivities = $this->normalise_list($value);
        $activities = [];

        foreach ($rawactivities as $index => $rawactivity) {
            $activity = $this->normalise_activity($rawactivity, $index);

            if ($activity['component'] === '' || $activity['name'] === '') {
                continue;
            }

            $activities[] = $activity;
        }

        return $activities;
    }

    /**
     * Normalise one activity definition.
     *
     * @param mixed $value Raw activity.
     * @param int $fallbackindex Fallback index.
     * @return array<string, mixed>
     */
    private function normalise_activity(mixed $value, int $fallbackindex): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            $value = [];
        }

        $component = $this->first_string($value['component'] ?? null, $value['module'] ?? null, $value['modname'] ?? null);
        $key = $this->first_string($value['key'] ?? null, $value['id'] ?? null, 'activity_' . $fallbackindex);
        $name = $this->first_string($value['name'] ?? null, $value['title'] ?? null, $key);
        $intro = $this->first_string($value['intro'] ?? null, $value['summary'] ?? null, $value['description'] ?? null);
        $section = $this->first_string($value['section'] ?? null, $value['sectionkey'] ?? null, $value['section_number'] ?? null, '0');

        return [
            'key' => clean_param($this->normalise_key($key), PARAM_ALPHANUMEXT),
            'component' => clean_param($component, PARAM_COMPONENT),
            'section' => clean_param($section, PARAM_TEXT),
            'name' => clean_param($name, PARAM_TEXT),
            'intro' => $intro,
            'required' => $this->normalise_bool($value['required'] ?? true),
            'defaults' => $this->normalise_assoc($value['defaults'] ?? []),
            'metadata' => $this->normalise_assoc($value['metadata'] ?? []),
        ];
    }

    /**
     * Convert registry component to Moodle module name.
     *
     * @param string $component Registry component.
     * @return string
     */
    private function activity_component_to_modname(string $component): string {
        $component = trim($component);

        if (str_starts_with($component, 'mod_')) {
            $component = substr($component, 4);
        }

        $component = clean_param($component, PARAM_PLUGIN);

        return $this->safe_activity_table($component) ? $component : '';
    }

    /**
     * Whether a module/table is an allowed UCKK activity table.
     *
     * @param string $modname Module name.
     * @return bool
     */
    private function safe_activity_table(string $modname): bool {
        return in_array($modname, ['uckkchallenge', 'uckkassembly', 'uckkarchive'], true);
    }

    /**
     * Whether activity should enable automatic completion tracking.
     *
     * @param array<string, mixed> $activity Normalised activity.
     * @return bool
     */
    private function activity_has_completion_rules(array $activity): bool {
        $defaults = $this->normalise_assoc($activity['defaults'] ?? []);

        foreach (array_keys($defaults) as $key) {
            if (str_starts_with((string)$key, 'completion')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a Moodle completion constant value with a safe fallback.
     *
     * @param string $name Constant name.
     * @param int $fallback Fallback value.
     * @return int
     */
    private function completion_constant(string $name, int $fallback): int {
        return defined($name) ? (int)constant($name) : $fallback;
    }

    /**
     * Create a missing course_sections record.
     *
     * @param int $courseid Course id.
     * @param int $sectionnumber Section number.
     * @return stdClass
     */
    private function create_section_record(int $courseid, int $sectionnumber): stdClass {
        global $DB;

        $record = new stdClass();
        $record->course = $courseid;
        $record->section = $sectionnumber;
        $record->name = null;
        $record->summary = '';
        $record->summaryformat = FORMAT_HTML;
        $record->sequence = '';
        $record->visible = 1;
        $record->availability = null;
        $record->timemodified = time();

        $record->id = $DB->insert_record('course_sections', $record);

        return $record;
    }

    /**
     * Upsert a course format option.
     *
     * @param int $courseid Course id.
     * @param string $format Course format.
     * @param int $sectionid Section id, or 0 for course-level options.
     * @param string $name Option name.
     * @param mixed $value Option value.
     */
    private function set_course_format_option(int $courseid, string $format, int $sectionid, string $name, mixed $value): void {
        global $DB;

        $name = clean_param($name, PARAM_ALPHANUMEXT);

        if ($name === '') {
            return;
        }

        $params = [
            'courseid' => $courseid,
            'format' => $format,
            'sectionid' => $sectionid,
            'name' => $name,
        ];

        $record = $DB->get_record('course_format_options', $params, '*', IGNORE_MISSING);

        if (!$record) {
            $record = (object)$params;
        }

        $record->value = $this->format_option_value_to_string($value);

        if (!empty($record->id)) {
            $DB->update_record('course_format_options', $record);
        } else {
            $DB->insert_record('course_format_options', $record);
        }
    }

    /**
     * Convert a format option value to Moodle's storage format.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function format_option_value_to_string(mixed $value): string {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if (is_string($value)) {
            return trim($value);
        }

        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
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
        $course->numsections = (int)$item['numsections'];
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
        global $CFG;

        if (!empty($options['allpresets']['categories']['items']) && is_array($options['allpresets']['categories']['items'])) {
            return $options['allpresets']['categories']['items'];
        }

        if (!empty($options['categories']['items']) && is_array($options['categories']['items'])) {
            return $options['categories']['items'];
        }

        if (!empty($options['presetdata']['categories']['items']) && is_array($options['presetdata']['categories']['items'])) {
            return $options['presetdata']['categories']['items'];
        }

        $candidatepaths = $this->build_registry_file_candidates($options, 'categories.json');

        $candidatepaths[] = $CFG->dirroot . DIRECTORY_SEPARATOR . 'academic_registry_json' . DIRECTORY_SEPARATOR . 'categories.json';
        $candidatepaths[] = getcwd() . DIRECTORY_SEPARATOR . 'academic_registry_json' . DIRECTORY_SEPARATOR . 'categories.json';

        foreach (array_unique($candidatepaths) as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $decoded = json_decode((string)file_get_contents($path), true);

            if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
                continue;
            }

            return $decoded['items'];
        }

        return [];
    }

    /**
     * Load course_templates.json from preset data/path when available.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, array<string, mixed>>
     */
    private function load_course_templates_from_preset(array $options): array {
        global $CFG;

        $items = [];

        if (!empty($options['allpresets']['course_templates']['items']) && is_array($options['allpresets']['course_templates']['items'])) {
            $items = $options['allpresets']['course_templates']['items'];
        } else if (!empty($options['course_templates']['items']) && is_array($options['course_templates']['items'])) {
            $items = $options['course_templates']['items'];
        } else if (!empty($options['presetdata']['course_templates']['items']) && is_array($options['presetdata']['course_templates']['items'])) {
            $items = $options['presetdata']['course_templates']['items'];
        } else {
            $candidatepaths = $this->build_registry_file_candidates($options, 'course_templates.json');

            $candidatepaths[] = $CFG->dirroot . DIRECTORY_SEPARATOR . 'academic_registry_json' . DIRECTORY_SEPARATOR . 'course_templates.json';
            $candidatepaths[] = getcwd() . DIRECTORY_SEPARATOR . 'academic_registry_json' . DIRECTORY_SEPARATOR . 'course_templates.json';

            foreach (array_unique($candidatepaths) as $path) {
                if (!is_readable($path)) {
                    continue;
                }

                $decoded = json_decode((string)file_get_contents($path), true);

                if (is_array($decoded) && !empty($decoded['items']) && is_array($decoded['items'])) {
                    $items = $decoded['items'];
                    break;
                }
            }
        }

        $templates = [];

        foreach ($items as $rawtemplate) {
            if ($rawtemplate instanceof stdClass) {
                $rawtemplate = (array)$rawtemplate;
            }

            if (!is_array($rawtemplate)) {
                continue;
            }

            $key = $this->first_string(
                $rawtemplate['key'] ?? null,
                $rawtemplate['id'] ?? null,
                $rawtemplate['code'] ?? null,
                $rawtemplate['name'] ?? null
            );

            if ($key === '') {
                continue;
            }

            $templates[$key] = $rawtemplate;
        }

        return $templates;
    }

    /**
     * Build candidate absolute paths for an academic registry file.
     *
     * @param array<string, mixed> $options Runtime options.
     * @param string $filename File name.
     * @return array<int, string>
     */
    private function build_registry_file_candidates(array $options, string $filename): array {
        $candidatepaths = [];

        foreach ([
            'presetpath',
            'preset_path',
            'academic_registry_json_path',
            'academicregistrypath',
            'registry_path',
            'jsonpath',
            'path',
        ] as $optionkey) {
            if (!empty($options[$optionkey]) && is_string($options[$optionkey])) {
                $candidatepaths[] = rtrim($options[$optionkey], DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . $filename;
            }
        }

        return $candidatepaths;
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
     * Normalise course sections.
     *
     * @param mixed $value Raw sections value.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_sections(mixed $value): array {
        $rawsections = $this->normalise_list($value);
        $sections = [];

        foreach ($rawsections as $index => $rawsection) {
            $sections[] = $this->normalise_section($rawsection, $index);
        }

        usort($sections, static function(array $left, array $right): int {
            return (int)$left['number'] <=> (int)$right['number'];
        });

        return $sections;
    }

    /**
     * Normalise one section definition.
     *
     * @param mixed $value Raw section value.
     * @param int $fallbacknumber Fallback section number.
     * @return array<string, mixed>
     */
    private function normalise_section(mixed $value, int $fallbacknumber): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            $value = [];
        }

        $number = $this->normalise_int($value['number'] ?? $value['section'] ?? $fallbacknumber, $fallbacknumber);
        $key = $this->first_string($value['key'] ?? null, $value['id'] ?? null, 'section_' . $number);
        $name = $this->first_string($value['name'] ?? null, $value['title'] ?? null, $key);
        $summary = $this->first_string($value['summary'] ?? null, $value['description'] ?? null, $value['intro'] ?? null);
        $summaryformat = $this->normalise_int($value['summaryformat'] ?? $value['summary_format'] ?? FORMAT_HTML, FORMAT_HTML);

        return [
            'number' => $number,
            'key' => clean_param($this->normalise_key($key), PARAM_ALPHANUMEXT),
            'name' => clean_param($name, PARAM_TEXT),
            'summary' => $summary,
            'summaryformat' => $summaryformat,
            'visible' => $this->normalise_visible($value['visible'] ?? 1, 1),
            'options' => $this->normalise_assoc($value['options'] ?? []),
        ];
    }

    /**
     * Resolve the effective Moodle numsections value.
     *
     * Moodle stores the highest numbered non-general section in this field for
     * legacy course formats. UCKK templates include section 0, so a template with
     * sections 0..8 should write numsections=8.
     *
     * @param mixed $value Raw numsections value.
     * @param array<int, array<string, mixed>> $sections Normalised sections.
     * @return int
     */
    private function normalise_numsections(mixed $value, array $sections): int {
        if (!empty($sections)) {
            $max = 0;

            foreach ($sections as $section) {
                $max = max($max, (int)$section['number']);
            }

            return $max;
        }

        return max(0, $this->normalise_int($value, 0));
    }

    /**
     * Normalise visibility.
     *
     * @param mixed $value Value.
     * @param int $default Default.
     * @return int
     */
    private function normalise_visible(mixed $value, int $default = 0): int {
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

        return $default ? 1 : 0;
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
    private function normalise_int(mixed $value, mixed $default): int {
        if (is_numeric($value)) {
            return (int)$value;
        }

        return (int)$default;
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
