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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Applies, validates, resets, and exports UCKK course seed data.
 *
 * This class owns only seed-time course creation/update coordination. It does
 * not become the owner of course pedagogy, challenge workflow, archive
 * validation, assembly decisions, integrity cases, reports, badges, or
 * competencies after the objects exist.
 */
final class course_seed {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'courses';

    /** Seed target type. */
    private const TARGET_TYPE = 'course';

    /** Canonical UCKK course format value. */
    private const COURSE_FORMAT = 'uckk';

    /** Default course visibility. */
    private const DEFAULT_VISIBLE = 1;

    /** Default section count expected by format_uckk. */
    private const DEFAULT_NUMSECTIONS = 9;

    /** Metadata marker used to identify seed-managed courses. */
    private const METADATA_SEEDED_BY = 'tool_uckkseed';

    /** Validation severity: info. */
    private const SEVERITY_INFO = 'info';

    /** Validation severity: success. */
    private const SEVERITY_SUCCESS = 'success';

    /** Validation severity: warning. */
    private const SEVERITY_WARNING = 'warning';

    /** Validation severity: error. */
    private const SEVERITY_ERROR = 'error';

    /** Result status: completed. */
    private const STATUS_COMPLETED = 'completed';

    /** Result status: failed. */
    private const STATUS_FAILED = 'failed';

    /** Result status: warning. */
    private const STATUS_WARNING = 'warning';

    /** Mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    private const MODE_APPLY = 'apply';

    /**
     * Validate course preset rows.
     *
     * @param array<int, array<string, mixed>|\stdClass> $items Course preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $messages = [];
        $seenkeys = [];
        $seenshortnames = [];
        $seenidnumbers = [];
        $counts = $this->empty_counts();

        foreach ($items as $index => $item) {
            $row = $this->normalise_item($item);
            $key = $row['key'];
            $targetkey = $key !== '' ? $key : 'row_' . $index;

            if ($key === '') {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_missing_key', self::COMPONENT)
                );
                $counts['errors']++;
            }

            if ($row['fullname'] === '') {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_missing_fullname', self::COMPONENT)
                );
                $counts['errors']++;
            }

            if ($row['shortname'] === '') {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_missing_shortname', self::COMPONENT)
                );
                $counts['errors']++;
            }

            if ($row['category'] === '') {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_missing_category', self::COMPONENT)
                );
                $counts['errors']++;
            }

            if ($row['format'] !== self::COURSE_FORMAT) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('course_seed_format_forced', self::COMPONENT, self::COURSE_FORMAT)
                );
                $counts['warnings']++;
            }

            if ($key !== '' && isset($seenkeys[$key])) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_duplicate_key', self::COMPONENT, $key)
                );
                $counts['errors']++;
            }

            if ($row['shortname'] !== '' && isset($seenshortnames[$row['shortname']])) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_duplicate_shortname', self::COMPONENT, $row['shortname'])
                );
                $counts['errors']++;
            }

            if ($row['idnumber'] !== '' && isset($seenidnumbers[$row['idnumber']])) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_duplicate_idnumber', self::COMPONENT, $row['idnumber'])
                );
                $counts['errors']++;
            }

            if ($row['category'] !== '' && !$this->category_exists($row['category'])) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_unknown_category', self::COMPONENT, $row['category'])
                );
                $counts['errors']++;
            }

            if ($row['template'] !== '' && !$this->template_reference_is_valid($row['template'], $options)) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('course_seed_unknown_template', self::COMPONENT, $row['template'])
                );
                $counts['warnings']++;
            }

            if ($key !== '') {
                $seenkeys[$key] = true;
            }

            if ($row['shortname'] !== '') {
                $seenshortnames[$row['shortname']] = true;
            }

            if ($row['idnumber'] !== '') {
                $seenidnumbers[$row['idnumber']] = true;
            }

            if ($counts['errors'] === 0) {
                $counts['skipped']++;
            }
        }

        if ($counts['errors'] === 0 && empty($messages)) {
            $this->add_message(
                $messages,
                self::SEVERITY_SUCCESS,
                self::PRESET,
                get_string('course_seed_validation_ok', self::COMPONENT)
            );
        }

        return $this->make_result(
            $counts['errors'] > 0 ? self::STATUS_FAILED : ($counts['warnings'] > 0 ? self::STATUS_WARNING : self::STATUS_COMPLETED),
            get_string('course_seed_validation_summary', self::COMPONENT, count($items)),
            $counts,
            $messages
        );
    }

    /**
     * Apply course preset rows.
     *
     * @param array<int, array<string, mixed>|\stdClass> $items Course preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $force = !empty($options['force']);

        $validation = $this->validate($items, $options);
        $validationdata = $this->result_to_array($validation);

        if (!empty($validationdata['haserrors'])) {
            return $validation;
        }

        $messages = [];
        $counts = $this->empty_counts();

        foreach ($items as $item) {
            $row = $this->normalise_item($item);
            $targetkey = $row['key'] !== '' ? $row['key'] : $row['shortname'];

            $categoryid = $this->resolve_category_id($row['category']);

            if ($categoryid <= 0) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('course_seed_unknown_category', self::COMPONENT, $row['category'])
                );
                $counts['failed']++;
                $counts['errors']++;
                continue;
            }

            $existing = $this->find_existing_course($row);

            if ($dryrun) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_INFO,
                    $targetkey,
                    $existing
                        ? get_string('course_seed_dryrun_update', self::COMPONENT, $row['shortname'])
                        : get_string('course_seed_dryrun_create', self::COMPONENT, $row['shortname'])
                );
                $counts['skipped']++;
                continue;
            }

            $coursedata = $this->build_course_record($row, $categoryid, $existing);

            if ($existing) {
                if (!$force && !$this->course_is_seed_managed((int)$existing->id)) {
                    $this->add_message(
                        $messages,
                        self::SEVERITY_WARNING,
                        $targetkey,
                        get_string('course_seed_existing_not_seeded', self::COMPONENT, $row['shortname'])
                    );
                    $counts['skipped']++;
                    $counts['warnings']++;
                    continue;
                }

                $coursedata->id = (int)$existing->id;
                update_course($coursedata);

                $this->mark_course_seeded((int)$existing->id, $row);

                $this->add_message(
                    $messages,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    get_string('course_seed_updated', self::COMPONENT, $row['shortname'])
                );
                $counts['updated']++;
                continue;
            }

            $course = create_course($coursedata);

            $this->mark_course_seeded((int)$course->id, $row);

            if (!empty($row['sections'])) {
                $this->apply_section_names((int)$course->id, $row['sections']);
            }

            $this->add_message(
                $messages,
                self::SEVERITY_SUCCESS,
                $targetkey,
                get_string('course_seed_created', self::COMPONENT, $row['shortname'])
            );
            $counts['created']++;
        }

        $status = $counts['errors'] > 0
            ? self::STATUS_FAILED
            : ($counts['warnings'] > 0 ? self::STATUS_WARNING : self::STATUS_COMPLETED);

        // Rebuild course cache after creating/updating several courses.
        if (!$dryrun && ($counts['created'] > 0 || $counts['updated'] > 0)) {
            rebuild_course_cache(0, true);
        }

        $summary = $dryrun
            ? get_string('course_seed_apply_dryrun_summary', self::COMPONENT, count($items))
            : get_string('course_seed_apply_summary', self::COMPONENT, [
                'created' => $counts['created'],
                'updated' => $counts['updated'],
                'skipped' => $counts['skipped'],
                'failed' => $counts['failed'],
            ]);

        return $this->make_result($status, $summary, $counts, $messages, [
            'mode' => $mode,
            'dryrun' => $dryrun,
            'coursecount' => $DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID]),
        ]);
    }

    /**
     * Reset seed-managed courses.
     *
     * This method only resets courses marked by tool_uckkseed metadata, unless
     * force is set. It never deletes arbitrary Moodle courses by default.
     *
     * @param array<int, array<string, mixed>|\stdClass> $items Course preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $force = !empty($options['force']);
        $confirm = !empty($options['confirm']);

        $messages = [];
        $counts = $this->empty_counts();

        if (!$dryrun && !$confirm) {
            $this->add_message(
                $messages,
                self::SEVERITY_ERROR,
                self::PRESET,
                get_string('course_seed_reset_requires_confirm', self::COMPONENT)
            );
            $counts['errors']++;
            $counts['failed']++;

            return $this->make_result(
                self::STATUS_FAILED,
                get_string('course_seed_reset_not_confirmed', self::COMPONENT),
                $counts,
                $messages
            );
        }

        $targets = [];

        foreach ($items as $item) {
            $row = $this->normalise_item($item);
            $course = $this->find_existing_course($row);

            if ($course) {
                $targets[(int)$course->id] = $course;
            }
        }

        if (empty($items)) {
            $targets = $this->get_seed_managed_courses();
        }

        foreach ($targets as $course) {
            $targetkey = !empty($course->idnumber) ? $course->idnumber : $course->shortname;

            if ((int)$course->id === SITEID) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('course_seed_reset_skip_site_course', self::COMPONENT)
                );
                $counts['skipped']++;
                $counts['warnings']++;
                continue;
            }

            if (!$force && !$this->course_is_seed_managed((int)$course->id)) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('course_seed_reset_skip_unmanaged', self::COMPONENT, $course->shortname)
                );
                $counts['skipped']++;
                $counts['warnings']++;
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $messages,
                    self::SEVERITY_INFO,
                    $targetkey,
                    get_string('course_seed_reset_dryrun_delete', self::COMPONENT, $course->shortname)
                );
                $counts['skipped']++;
                continue;
            }

            delete_course($course, false);

            $this->add_message(
                $messages,
                self::SEVERITY_SUCCESS,
                $targetkey,
                get_string('course_seed_reset_deleted', self::COMPONENT, $course->shortname)
            );
            $counts['updated']++;
        }

        if (!$dryrun && $counts['updated'] > 0) {
            rebuild_course_cache(0, true);
        }

        $status = $counts['errors'] > 0
            ? self::STATUS_FAILED
            : ($counts['warnings'] > 0 ? self::STATUS_WARNING : self::STATUS_COMPLETED);

        return $this->make_result(
            $status,
            get_string('course_seed_reset_summary', self::COMPONENT, [
                'deleted' => $counts['updated'],
                'skipped' => $counts['skipped'],
            ]),
            $counts,
            $messages,
            [
                'mode' => $mode,
                'dryrun' => $dryrun,
                'force' => $force,
            ]
        );
    }

    /**
     * Export current seed-managed courses into canonical preset shape.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = [];

        foreach ($this->get_seed_managed_courses() as $course) {
            $categorykey = $this->get_category_key((int)$course->category);

            $items[] = [
                'key' => !empty($course->idnumber) ? $course->idnumber : $course->shortname,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'idnumber' => $course->idnumber,
                'category' => $categorykey,
                'format' => $course->format,
                'template' => $this->get_course_metadata_value((int)$course->id, 'template', ''),
                'summary' => $course->summary,
                'visible' => (int)$course->visible,
                'startdate' => (int)$course->startdate,
                'enddate' => (int)$course->enddate,
                'sections' => $this->export_sections((int)$course->id),
                'completion' => [
                    'enabled' => (int)$course->enablecompletion === 1,
                ],
                'metadata' => $this->get_course_seed_metadata((int)$course->id),
            ];
        }

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $items,
        ];
    }

    /**
     * Normalise one course preset item.
     *
     * @param array<string, mixed>|\stdClass $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(array|\stdClass $item): array {
        $row = (array)$item;

        $key = trim((string)($row['key'] ?? $row['idnumber'] ?? $row['shortname'] ?? ''));
        $shortname = trim((string)($row['shortname'] ?? $key));
        $idnumber = trim((string)($row['idnumber'] ?? $key));
        $format = trim((string)($row['format'] ?? self::COURSE_FORMAT));

        $sections = $row['sections'] ?? [];
        $completion = $row['completion'] ?? [];
        $metadata = $row['metadata'] ?? [];

        if ($metadata instanceof \stdClass) {
            $metadata = (array)$metadata;
        }

        if ($completion instanceof \stdClass) {
            $completion = (array)$completion;
        }

        if (!is_array($sections)) {
            $sections = [];
        }

        if (!is_array($completion)) {
            $completion = [];
        }

        if (!is_array($metadata)) {
            $metadata = [];
        }

        return [
            'key' => clean_param($key, PARAM_TEXT),
            'fullname' => trim((string)($row['fullname'] ?? $row['name'] ?? '')),
            'shortname' => clean_param($shortname, PARAM_TEXT),
            'idnumber' => clean_param($idnumber, PARAM_TEXT),
            'category' => clean_param(trim((string)($row['category'] ?? $row['categorykey'] ?? '')), PARAM_TEXT),
            'format' => clean_param($format, PARAM_PLUGIN),
            'template' => clean_param(trim((string)($row['template'] ?? '')), PARAM_TEXT),
            'summary' => trim((string)($row['summary'] ?? $row['description'] ?? '')),
            'visible' => isset($row['visible']) ? (int)(bool)$row['visible'] : self::DEFAULT_VISIBLE,
            'startdate' => $this->normalise_time($row['startdate'] ?? 0),
            'enddate' => $this->normalise_time($row['enddate'] ?? 0),
            'sections' => $sections,
            'completion' => $completion,
            'metadata' => $metadata,
            'sortorder' => (int)($row['sortorder'] ?? 0),
        ];
    }

    /**
     * Build Moodle course data for create_course/update_course.
     *
     * @param array<string, mixed> $row Normalised row.
     * @param int $categoryid Category id.
     * @param \stdClass|null $existing Existing course.
     * @return \stdClass
     */
    private function build_course_record(array $row, int $categoryid, ?\stdClass $existing): \stdClass {
        $course = new \stdClass();
        $course->fullname = $row['fullname'];
        $course->shortname = $row['shortname'];
        $course->idnumber = $row['idnumber'];
        $course->category = $categoryid;
        $course->format = self::COURSE_FORMAT;
        $course->summary = $row['summary'];
        $course->summaryformat = FORMAT_HTML;
        $course->visible = $row['visible'];
        $course->startdate = $row['startdate'];
        $course->enddate = $row['enddate'];
        $course->enablecompletion = !empty($row['completion']['enabled']) ? 1 : 0;

        if ($existing) {
            $course->id = (int)$existing->id;
            $course->sortorder = (int)$existing->sortorder;
            $course->numsections = (int)($existing->numsections ?? self::DEFAULT_NUMSECTIONS);
        } else {
            $course->numsections = (int)($row['completion']['numsections'] ?? self::DEFAULT_NUMSECTIONS);
        }

        return $course;
    }

    /**
     * Find an existing Moodle course for a seed row.
     *
     * @param array<string, mixed> $row Normalised row.
     * @return \stdClass|null
     */
    private function find_existing_course(array $row): ?\stdClass {
        global $DB;

        if ($row['idnumber'] !== '') {
            $course = $DB->get_record('course', ['idnumber' => $row['idnumber']], '*', IGNORE_MULTIPLE);

            if ($course) {
                return $course;
            }
        }

        if ($row['shortname'] !== '') {
            $course = $DB->get_record('course', ['shortname' => $row['shortname']], '*', IGNORE_MULTIPLE);

            if ($course) {
                return $course;
            }
        }

        return null;
    }

    /**
     * Resolve category id from seed category key/idnumber/name.
     *
     * @param string $categorykey Category key.
     * @return int
     */
    private function resolve_category_id(string $categorykey): int {
        global $DB;

        $categorykey = trim($categorykey);

        if ($categorykey === '') {
            return 0;
        }

        if (ctype_digit($categorykey) && $DB->record_exists('course_categories', ['id' => (int)$categorykey])) {
            return (int)$categorykey;
        }

        foreach (['idnumber', 'name'] as $field) {
            $category = $DB->get_record('course_categories', [$field => $categorykey], 'id', IGNORE_MULTIPLE);

            if ($category) {
                return (int)$category->id;
            }
        }

        return 0;
    }

    /**
     * Return whether a category exists.
     *
     * @param string $categorykey Category key.
     * @return bool
     */
    private function category_exists(string $categorykey): bool {
        return $this->resolve_category_id($categorykey) > 0;
    }

    /**
     * Return whether a template reference looks valid.
     *
     * The course_template seeder owns actual template creation. This class only
     * verifies references when template preset data was passed in options.
     *
     * @param string $template Template key.
     * @param array<string, mixed> $options Runtime options.
     * @return bool
     */
    private function template_reference_is_valid(string $template, array $options): bool {
        if ($template === '') {
            return true;
        }

        if (empty($options['course_templates']) || !is_array($options['course_templates'])) {
            return true;
        }

        foreach ($options['course_templates'] as $item) {
            $row = (array)$item;

            if ((string)($row['key'] ?? '') === $template) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a course as managed by tool_uckkseed.
     *
     * Uses Moodle customfield-free config table storage to avoid inventing an
     * extra course metadata table in tool_uckkseed.
     *
     * @param int $courseid Course id.
     * @param array<string, mixed> $row Normalised row.
     */
    private function mark_course_seeded(int $courseid, array $row): void {
        global $DB;

        $metadata = $row['metadata'];
        $metadata['seededby'] = self::METADATA_SEEDED_BY;
        $metadata['preset'] = self::PRESET;
        $metadata['key'] = $row['key'];
        $metadata['template'] = $row['template'];
        $metadata['timemodified'] = time();

        set_config(
            'course_' . $courseid,
            json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );

        // Store a lightweight marker in the course summary when metadata is not
        // enough for external inspection is intentionally avoided. Summary stays
        // user-facing and remains owned by the preset row.
        $DB->set_field('course', 'timemodified', time(), ['id' => $courseid]);
    }

    /**
     * Return whether a course is seed-managed.
     *
     * @param int $courseid Course id.
     * @return bool
     */
    private function course_is_seed_managed(int $courseid): bool {
        $metadata = $this->get_course_seed_metadata($courseid);

        return ($metadata['seededby'] ?? '') === self::METADATA_SEEDED_BY;
    }

    /**
     * Return seed-managed courses.
     *
     * @return array<int, \stdClass>
     */
    private function get_seed_managed_courses(): array {
        global $DB;

        $courses = $DB->get_records_select('course', 'id <> :siteid', ['siteid' => SITEID], 'sortorder ASC, fullname ASC');
        $managed = [];

        foreach ($courses as $course) {
            if ($this->course_is_seed_managed((int)$course->id)) {
                $managed[(int)$course->id] = $course;
            }
        }

        return $managed;
    }

    /**
     * Get stored course seed metadata.
     *
     * @param int $courseid Course id.
     * @return array<string, mixed>
     */
    private function get_course_seed_metadata(int $courseid): array {
        $raw = get_config(self::COMPONENT, 'course_' . $courseid);

        if (!$raw || !is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get one metadata value for a seed-managed course.
     *
     * @param int $courseid Course id.
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    private function get_course_metadata_value(int $courseid, string $key, mixed $default = null): mixed {
        $metadata = $this->get_course_seed_metadata($courseid);

        return $metadata[$key] ?? $default;
    }

    /**
     * Apply section names when sections are provided by the preset.
     *
     * @param int $courseid Course id.
     * @param array<int, array<string, mixed>|\stdClass|string> $sections Sections.
     */
    private function apply_section_names(int $courseid, array $sections): void {
        global $DB;

        foreach ($sections as $index => $section) {
            $row = is_array($section) || $section instanceof \stdClass ? (array)$section : ['name' => (string)$section];

            $sectionnum = (int)($row['section'] ?? $row['num'] ?? $index);
            $name = trim((string)($row['name'] ?? $row['title'] ?? ''));

            if ($sectionnum < 0 || $name === '') {
                continue;
            }

            $record = $DB->get_record('course_sections', [
                'course' => $courseid,
                'section' => $sectionnum,
            ], 'id', IGNORE_MISSING);

            if (!$record) {
                continue;
            }

            $DB->set_field('course_sections', 'name', $name, ['id' => $record->id]);
            $DB->set_field('course_sections', 'timemodified', time(), ['id' => $record->id]);
        }
    }

    /**
     * Export course section names.
     *
     * @param int $courseid Course id.
     * @return array<int, array<string, mixed>>
     */
    private function export_sections(int $courseid): array {
        global $DB;

        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC', 'section, name, visible');
        $export = [];

        foreach ($sections as $section) {
            $export[] = [
                'section' => (int)$section->section,
                'name' => (string)$section->name,
                'visible' => (int)$section->visible === 1,
            ];
        }

        return $export;
    }

    /**
     * Get a stable category key.
     *
     * @param int $categoryid Category id.
     * @return string
     */
    private function get_category_key(int $categoryid): string {
        global $DB;

        $category = $DB->get_record('course_categories', ['id' => $categoryid], 'idnumber, name', IGNORE_MISSING);

        if (!$category) {
            return '';
        }

        return !empty($category->idnumber) ? $category->idnumber : $category->name;
    }

    /**
     * Normalise a timestamp value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function normalise_time(mixed $value): int {
        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_numeric($value)) {
            return max(0, (int)$value);
        }

        if (is_string($value) && trim($value) !== '') {
            $timestamp = strtotime($value);

            return $timestamp === false ? 0 : $timestamp;
        }

        return 0;
    }

    /**
     * Normalise seed mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        return in_array($mode, [
            self::MODE_DRY_RUN,
            self::MODE_APPLY,
        ], true) ? $mode : self::MODE_APPLY;
    }

    /**
     * Empty count shape.
     *
     * @return array<string, int>
     */
    private function empty_counts(): array {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Add a canonical result message.
     *
     * @param array<int, array<string, mixed>> $messages Messages.
     * @param string $severity Severity.
     * @param string $targetkey Target key.
     * @param string $message Message text.
     * @param array<string, mixed> $metadata Optional metadata.
     */
    private function add_message(
        array &$messages,
        string $severity,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $messages[] = [
            'severity' => $severity,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'targetkey' => $targetkey,
            'message' => $message,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build a validation result.
     *
     * This expects validation_result to expose from_array(). The fallback
     * constructor shape should be kept compatible when validation_result.php is
     * generated.
     *
     * @param string $status Result status.
     * @param string $summary Human summary.
     * @param array<string, int> $counts Counts.
     * @param array<int, array<string, mixed>> $messages Messages.
     * @param array<string, mixed> $metadata Metadata.
     * @return validation_result
     */
    private function make_result(
        string $status,
        string $summary,
        array $counts,
        array $messages,
        array $metadata = []
    ): validation_result {
        $data = [
            'status' => $status,
            'ok' => $status !== self::STATUS_FAILED,
            'haserrors' => $counts['errors'] > 0,
            'haswarnings' => $counts['warnings'] > 0,
            'summary' => $summary,
            'counts' => $counts,
            'messages' => $messages,
            'created' => $counts['created'],
            'updated' => $counts['updated'],
            'skipped' => $counts['skipped'],
            'failed' => $counts['failed'],
            'metadata' => $metadata,
        ];

        if (method_exists(validation_result::class, 'from_array')) {
            return validation_result::from_array($data);
        }

        return new validation_result($data);
    }

    /**
     * Convert validation_result to array.
     *
     * @param validation_result $result Result.
     * @return array<string, mixed>
     */
    private function result_to_array(validation_result $result): array {
        if (method_exists($result, 'to_array')) {
            $data = $result->to_array();

            return is_array($data) ? $data : [];
        }

        if (method_exists($result, 'export')) {
            $data = $result->export();

            return is_array($data) ? $data : (array)$data;
        }

        if ($result instanceof \JsonSerializable) {
            $data = $result->jsonSerialize();

            return is_array($data) ? $data : (array)$data;
        }

        return get_object_vars($result);
    }
}
```

Add these strings to `admin/tool/uckkseed/lang/en/tool_uckkseed.php` later:

```php id="e5ev24"
$string['course_seed_apply_dryrun_summary'] = 'Dry run completed for {$a} course preset item(s).';
$string['course_seed_apply_summary'] = 'Course seeding completed: {$a->created} created, {$a->updated} updated, {$a->skipped} skipped, {$a->failed} failed.';
$string['course_seed_created'] = 'Created course {$a}.';
$string['course_seed_dryrun_create'] = 'Would create course {$a}.';
$string['course_seed_dryrun_update'] = 'Would update course {$a}.';
$string['course_seed_duplicate_idnumber'] = 'Duplicate course idnumber: {$a}.';
$string['course_seed_duplicate_key'] = 'Duplicate course key: {$a}.';
$string['course_seed_duplicate_shortname'] = 'Duplicate course shortname: {$a}.';
$string['course_seed_existing_not_seeded'] = 'Course {$a} already exists but is not marked as managed by tool_uckkseed.';
$string['course_seed_format_forced'] = 'Course format will be normalised to {$a}.';
$string['course_seed_missing_category'] = 'Course category is required.';
$string['course_seed_missing_fullname'] = 'Course fullname is required.';
$string['course_seed_missing_key'] = 'Course key is required.';
$string['course_seed_missing_shortname'] = 'Course shortname is required.';
$string['course_seed_reset_deleted'] = 'Deleted seed-managed course {$a}.';
$string['course_seed_reset_dryrun_delete'] = 'Would delete seed-managed course {$a}.';
$string['course_seed_reset_not_confirmed'] = 'Course reset was not confirmed.';
$string['course_seed_reset_requires_confirm'] = 'Course reset requires explicit confirmation.';
$string['course_seed_reset_skip_site_course'] = 'The site course cannot be reset by tool_uckkseed.';
$string['course_seed_reset_skip_unmanaged'] = 'Skipped unmanaged course {$a}.';
$string['course_seed_reset_summary'] = 'Course reset completed: {$a->deleted} deleted, {$a->skipped} skipped.';
$string['course_seed_unknown_category'] = 'Unknown course category: {$a}.';
$string['course_seed_unknown_template'] = 'Unknown course template: {$a}.';
$string['course_seed_updated'] = 'Updated course {$a}.';
$string['course_seed_validation_ok'] = 'Course preset validation passed.';
$string['course_seed_validation_summary'] = 'Validated {$a} course preset item(s).';
```

Add these strings to `admin/tool/uckkseed/lang/fr/tool_uckkseed.php` later:

```php id="x029gf"
$string['course_seed_apply_dryrun_summary'] = 'Simulation terminée pour {$a} élément(s) de préréglage de cours.';
$string['course_seed_apply_summary'] = 'Amorçage des cours terminé : {$a->created} créés, {$a->updated} mis à jour, {$a->skipped} ignorés, {$a->failed} échoués.';
$string['course_seed_created'] = 'Cours {$a} créé.';
$string['course_seed_dryrun_create'] = 'Créerait le cours {$a}.';
$string['course_seed_dryrun_update'] = 'Mettrait à jour le cours {$a}.';
$string['course_seed_duplicate_idnumber'] = 'Identifiant de cours en double : {$a}.';
$string['course_seed_duplicate_key'] = 'Clé de cours en double : {$a}.';
$string['course_seed_duplicate_shortname'] = 'Nom abrégé de cours en double : {$a}.';
$string['course_seed_existing_not_seeded'] = 'Le cours {$a} existe déjà mais n’est pas marqué comme géré par tool_uckkseed.';
$string['course_seed_format_forced'] = 'Le format de cours sera normalisé à {$a}.';
$string['course_seed_missing_category'] = 'La catégorie du cours est requise.';
$string['course_seed_missing_fullname'] = 'Le nom complet du cours est requis.';
$string['course_seed_missing_key'] = 'La clé du cours est requise.';
$string['course_seed_missing_shortname'] = 'Le nom abrégé du cours est requis.';
$string['course_seed_reset_deleted'] = 'Cours amorcé {$a} supprimé.';
$string['course_seed_reset_dryrun_delete'] = 'Supprimerait le cours amorcé {$a}.';
$string['course_seed_reset_not_confirmed'] = 'La réinitialisation des cours n’a pas été confirmée.';
$string['course_seed_reset_requires_confirm'] = 'La réinitialisation des cours exige une confirmation explicite.';
$string['course_seed_reset_skip_site_course'] = 'Le cours du site ne peut pas être réinitialisé par tool_uckkseed.';
$string['course_seed_reset_skip_unmanaged'] = 'Cours non géré {$a} ignoré.';
$string['course_seed_reset_summary'] = 'Réinitialisation des cours terminée : {$a->deleted} supprimés, {$a->skipped} ignorés.';
$string['course_seed_unknown_category'] = 'Catégorie de cours inconnue : {$a}.';
$string['course_seed_unknown_template'] = 'Modèle de cours inconnu : {$a}.';
$string['course_seed_updated'] = 'Cours {$a} mis à jour.';
$string['course_seed_validation_ok'] = 'Validation du préréglage de cours réussie.';
$string['course_seed_validation_summary'] = '{$a} élément(s) de préréglage de cours validés.';

