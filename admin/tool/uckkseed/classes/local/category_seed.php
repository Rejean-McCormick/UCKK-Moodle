<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Category preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use core_course_category;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Seeds Moodle course categories from academic_registry_json/categories.json.
 *
 * Expected runtime item shape:
 *
 * [
 *     'key' => 'uckk_tc',
 *     'name' => 'Tronc commun',
 *     'idnumber' => 'UCKK-TC',
 *     'parent' => '',
 *     'parent_idnumber' => '',
 *     'description' => '...',
 *     'sortorder' => 10,
 *     'visible' => 1,
 *     'metadata' => [],
 * ]
 *
 * Idempotency key:
 * - course_categories.idnumber
 *
 * This class owns Moodle course category creation/update only. It must not
 * create courses, cohorts, roles, capabilities, competencies, badges, reports,
 * activities, archive records, program records, or pathway records.
 */
final class category_seed {
    /** Component name. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id handled by this class. */
    public const PRESET = 'categories';

    /** Target type used in validation/log messages. */
    public const TARGET_TYPE = 'category';

    /** Runtime preset schema. */
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

    /** Result status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Result status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Result status: warning. */
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

    /** Metadata marker key. */
    private const METADATA_MANAGED_BY = 'managedby';

    /** Metadata marker value. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** @var seeder|null Parent seeder, when injected by the orchestration layer. */
    private ?seeder $seeder;

    /**
     * Constructor.
     *
     * @param seeder|null $seeder Parent seeder, optional.
     */
    public function __construct(?seeder $seeder = null) {
        $this->seeder = $seeder;
    }

    /**
     * Validate category preset rows without writing database records.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('Category preset validation started.');

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Category preset is empty.',
                self::PRESET
            );

            $this->finalise_result($result, 'Category preset validation completed with warnings.');
            return $result;
        }

        $seenkeys = [];
        $seenidnumbers = [];
        $presetrefs = $this->build_preset_reference_map($items);

        foreach ($items as $index => $rawitem) {
            $beforeerrors = $this->count_errors($result);
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . ($index + 1);

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Category row is missing required field: key.',
                    $targetkey,
                    ['row' => $index + 1]
                );
            }

            if ($item['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Category row is missing required field: name.',
                    $targetkey,
                    ['row' => $index + 1]
                );
            }

            if ($item['idnumber'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Category row is missing required field: idnumber.',
                    $targetkey,
                    ['row' => $index + 1]
                );
            }

            if ($item['idnumber'] !== '' && \core_text::strlen($item['idnumber']) > 100) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Category idnumber exceeds Moodle course_categories.idnumber length.',
                    $targetkey,
                    [
                        'idnumber' => $item['idnumber'],
                        'length' => \core_text::strlen($item['idnumber']),
                        'max' => 100,
                    ]
                );
            }

            if ($item['name'] !== '' && \core_text::strlen($item['name']) > 255) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Category name exceeds Moodle course_categories.name length.',
                    $targetkey,
                    [
                        'name' => $item['name'],
                        'length' => \core_text::strlen($item['name']),
                        'max' => 255,
                    ]
                );
            }

            if ($item['key'] !== '') {
                if (isset($seenkeys[$item['key']])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Duplicate category key.',
                        $targetkey,
                        ['key' => $item['key']]
                    );
                }

                $seenkeys[$item['key']] = true;
            }

            if ($item['idnumber'] !== '') {
                if (isset($seenidnumbers[$item['idnumber']])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Duplicate category idnumber.',
                        $targetkey,
                        ['idnumber' => $item['idnumber']]
                    );
                }

                $seenidnumbers[$item['idnumber']] = true;
            }

            if (!$this->is_root_parent($item['parent'])) {
                $parent = $item['parent'];

                if (
                    !isset($presetrefs[$parent])
                    && $this->resolve_category_id($parent) <= 0
                ) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Category parent does not exist in Moodle and is not present in the category preset.',
                        $targetkey,
                        ['parent' => $parent]
                    );
                }
            }

            if ($this->count_errors($result) === $beforeerrors) {
                $this->increment($result, 'skipped');
            }
        }

        $this->finalise_result($result, 'Category preset validation completed.');
        return $result;
    }

    /**
     * Apply category preset rows.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $this->is_dry_run($options) || $mode === self::MODE_REPORT;
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);

        $result = $this->new_result(
            ($dryrun || $rollbackplan)
                ? 'Category seed dry run completed.'
                : 'Category seed apply completed.'
        );

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Category preset is empty.',
                self::PRESET
            );

            $this->finalise_result($result);
            return $result;
        }

        $remaining = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['idnumber'] !== '') {
                $remaining[] = $item;
            }
        }

        $createdorupdated = [];
        $guard = 0;

        while (!empty($remaining) && $guard < 1000) {
            $guard++;
            $progress = false;

            foreach ($remaining as $offset => $item) {
                if (!$this->parent_is_resolvable($item, $createdorupdated)) {
                    continue;
                }

                $this->apply_one_category($result, $item, $dryrun || $rollbackplan);
                $createdorupdated[$item['key']] = true;
                $createdorupdated[$item['idnumber']] = true;

                unset($remaining[$offset]);
                $progress = true;
            }

            $remaining = array_values($remaining);

            if (!$progress) {
                break;
            }
        }

        foreach ($remaining as $item) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Category could not be applied because its parent could not be resolved.',
                $item['key'] !== '' ? $item['key'] : $item['idnumber'],
                [
                    'parent' => $item['parent'],
                    'idnumber' => $item['idnumber'],
                ]
            );
        }

        $this->finalise_result($result);
        return $result;
    }

    /**
     * Reset seed-managed categories.
     *
     * Reset is conservative. By default, it refuses to delete categories that
     * contain courses or child categories.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $this->is_dry_run($options) || $mode !== self::MODE_APPLY;
        $confirmed = !empty($options['confirm']);
        $force = !empty($options['force']);

        $result = $this->new_result(
            $dryrun ? 'Category reset dry run completed.' : 'Category reset completed.'
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                'Reset requires explicit confirmation.',
                self::PRESET
            );

            $this->finalise_result($result);
            return $result;
        }

        $targets = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['idnumber'] !== '') {
                $targets[$item['idnumber']] = $item;
            }
        }

        if (empty($targets)) {
            $records = $DB->get_records_select(
                'course_categories',
                $DB->sql_like('idnumber', ':prefix', false, false),
                ['prefix' => 'UCKK-%'],
                'sortorder DESC, id DESC',
                'id, name, idnumber, parent'
            );

            foreach ($records as $record) {
                $targets[(string)$record->idnumber] = [
                    'key' => clean_param((string)$record->idnumber, PARAM_ALPHANUMEXT),
                    'name' => (string)$record->name,
                    'idnumber' => (string)$record->idnumber,
                    'parent' => '',
                    'description' => '',
                    'descriptionformat' => FORMAT_HTML,
                    'sortorder' => 0,
                    'visible' => 1,
                    'metadata' => [],
                ];
            }
        }

        foreach ($targets as $item) {
            $category = $this->get_existing_category($item);

            if (!$category) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Category already absent.',
                    $item['idnumber']
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $haschildren = $DB->record_exists('course_categories', ['parent' => (int)$category->id]);
            $hascourses = $DB->record_exists('course', ['category' => (int)$category->id]);

            if (($haschildren || $hascourses) && !$force) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Category reset skipped because category is not empty.',
                    $item['idnumber'],
                    [
                        'categoryid' => (int)$category->id,
                        'haschildren' => $haschildren,
                        'hascourses' => $hascourses,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Category would be deleted.',
                    $item['idnumber'],
                    ['categoryid' => (int)$category->id]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $coursecat = core_course_category::get((int)$category->id, MUST_EXIST, true);

            if (method_exists($coursecat, 'delete_full')) {
                $coursecat->delete_full(false);
                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    'Category deleted.',
                    $item['idnumber'],
                    ['categoryid' => (int)$category->id]
                );
                $this->increment($result, 'updated');
                continue;
            }

            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                'Category delete API is unavailable.',
                $item['idnumber'],
                ['categoryid' => (int)$category->id]
            );
        }

        $this->finalise_result($result);
        return $result;
    }

    /**
     * Export category preset.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];

        $records = $DB->get_records_select(
            'course_categories',
            "idnumber <> ''",
            [],
            'sortorder ASC, name ASC',
            'id, name, idnumber, description, descriptionformat, parent, visible, sortorder'
        );

        foreach ($records as $record) {
            $idnumber = (string)$record->idnumber;

            if (empty($options['all']) && !preg_match('/^UCKK[-_]/i', $idnumber)) {
                continue;
            }

            $parentidnumber = '';

            if ((int)$record->parent > 0) {
                $parentidnumber = (string)$DB->get_field(
                    'course_categories',
                    'idnumber',
                    ['id' => (int)$record->parent],
                    IGNORE_MISSING
                );
            }

            $items[] = [
                'key' => $this->normalise_key($idnumber),
                'name' => (string)$record->name,
                'idnumber' => $idnumber,
                'parent' => $parentidnumber,
                'parent_idnumber' => $parentidnumber,
                'description' => (string)($record->description ?? ''),
                'descriptionformat' => (int)($record->descriptionformat ?? FORMAT_HTML),
                'sortorder' => (int)($record->sortorder ?? 0),
                'visible' => (int)($record->visible ?? 1),
                'metadata' => [
                    self::METADATA_MANAGED_BY => self::MANAGED_BY,
                    'source' => 'moodle_course_categories',
                    'categoryid' => (int)$record->id,
                ],
            ];
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => self::VERSION,
            'items' => array_values($items),
        ];
    }

    /**
     * Apply one category row.
     *
     * @param validation_result $result Result.
     * @param array<string, mixed> $item Normalised item.
     * @param bool $dryrun Whether this is dry-run/report/rollback-plan mode.
     */
    private function apply_one_category(validation_result $result, array $item, bool $dryrun): void {
        $existing = $this->get_existing_category($item);
        $parentid = $this->resolve_parent_id($item['parent']);

        if (!$existing) {
            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Category would be created.',
                    $item['idnumber'],
                    ['proposed' => $item]
                );
                $this->increment($result, 'skipped');
                return;
            }

            $category = core_course_category::create([
                'name' => $item['name'],
                'idnumber' => $item['idnumber'],
                'description' => $item['description'],
                'descriptionformat' => $item['descriptionformat'],
                'parent' => $parentid,
                'visible' => $item['visible'],
            ]);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                'Category created.',
                $item['idnumber'],
                ['categoryid' => (int)$category->id]
            );
            $this->increment($result, 'created');
            return;
        }

        $changes = $this->category_changes($existing, $item, $parentid);

        if (empty($changes)) {
            $this->add_message(
                $result,
                self::SEVERITY_INFO,
                'Category unchanged.',
                $item['idnumber'],
                ['categoryid' => (int)$existing->id]
            );
            $this->increment($result, 'skipped');
            return;
        }

        if ($dryrun) {
            $this->add_message(
                $result,
                self::SEVERITY_INFO,
                'Category would be updated.',
                $item['idnumber'],
                [
                    'categoryid' => (int)$existing->id,
                    'changes' => $changes,
                ]
            );
            $this->increment($result, 'skipped');
            return;
        }

        $coursecat = core_course_category::get((int)$existing->id, MUST_EXIST, true);

        $updatedata = [
            'name' => $item['name'],
            'idnumber' => $item['idnumber'],
            'description' => $item['description'],
            'descriptionformat' => $item['descriptionformat'],
            'visible' => $item['visible'],
        ];

        if (method_exists($coursecat, 'update')) {
            $coursecat->update($updatedata);
        }

        if ((int)$existing->parent !== $parentid && method_exists($coursecat, 'change_parent')) {
            $coursecat->change_parent($parentid);
        }

        $this->add_message(
            $result,
            self::SEVERITY_SUCCESS,
            'Category updated.',
            $item['idnumber'],
            [
                'categoryid' => (int)$existing->id,
                'changes' => $changes,
            ]
        );
        $this->increment($result, 'updated');
    }

    /**
     * Calculate update changes for an existing category.
     *
     * @param stdClass $existing Existing category record.
     * @param array<string, mixed> $item Normalised preset item.
     * @param int $parentid Resolved parent id.
     * @return array<string, array<string, mixed>>
     */
    private function category_changes(stdClass $existing, array $item, int $parentid): array {
        $changes = [];

        $checks = [
            'name' => $item['name'],
            'idnumber' => $item['idnumber'],
            'description' => $item['description'],
            'descriptionformat' => $item['descriptionformat'],
            'visible' => $item['visible'],
            'parent' => $parentid,
        ];

        foreach ($checks as $field => $after) {
            $before = $existing->{$field} ?? null;

            if ($field === 'description') {
                $before = (string)$before;
                $after = (string)$after;
            } else {
                $before = is_numeric($before) ? (int)$before : $before;
                $after = is_numeric($after) ? (int)$after : $after;
            }

            if ($before !== $after) {
                $changes[$field] = [
                    'before' => $before,
                    'after' => $after,
                ];
            }
        }

        return $changes;
    }

    /**
     * Return existing Moodle category for a seed item.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return stdClass|null
     */
    private function get_existing_category(array $item): ?stdClass {
        global $DB;

        if ($item['idnumber'] !== '') {
            $record = $DB->get_record(
                'course_categories',
                ['idnumber' => $item['idnumber']],
                '*',
                IGNORE_MISSING
            );

            if ($record) {
                return $record;
            }
        }

        if ($item['key'] !== '') {
            $record = $DB->get_record(
                'course_categories',
                ['idnumber' => $item['key']],
                '*',
                IGNORE_MISSING
            );

            if ($record) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Resolve a category reference to a Moodle course category id.
     *
     * @param string $reference Category id, idnumber, key, or name.
     * @return int Category id, or 0 for root/unresolved.
     */
    private function resolve_category_id(string $reference): int {
        global $DB;

        $reference = trim($reference);

        if ($this->is_root_parent($reference)) {
            return 0;
        }

        if (ctype_digit($reference)) {
            $id = (int)$reference;

            if ($id > 0 && $DB->record_exists('course_categories', ['id' => $id])) {
                return $id;
            }
        }

        $id = $DB->get_field('course_categories', 'id', ['idnumber' => $reference], IGNORE_MISSING);

        if ($id) {
            return (int)$id;
        }

        $id = $DB->get_field('course_categories', 'id', ['name' => $reference], IGNORE_MISSING);

        return $id ? (int)$id : 0;
    }

    /**
     * Resolve parent id.
     *
     * @param string $parent Parent reference.
     * @return int Parent id, or 0 for root.
     */
    private function resolve_parent_id(string $parent): int {
        if ($this->is_root_parent($parent)) {
            return 0;
        }

        return $this->resolve_category_id($parent);
    }

    /**
     * Return whether an item parent can be resolved now.
     *
     * @param array<string, mixed> $item Normalised category item.
     * @param array<string, bool> $createdorupdated References already processed this run.
     * @return bool
     */
    private function parent_is_resolvable(array $item, array $createdorupdated): bool {
        if ($this->is_root_parent($item['parent'])) {
            return true;
        }

        if ($this->resolve_parent_id($item['parent']) > 0) {
            return true;
        }

        return !empty($createdorupdated[$item['parent']]);
    }

    /**
     * Return whether a parent reference means Moodle root.
     *
     * @param string $parent Parent reference.
     * @return bool
     */
    private function is_root_parent(string $parent): bool {
        $parent = strtolower(trim($parent));

        return $parent === ''
            || $parent === '0'
            || $parent === 'root'
            || $parent === 'top'
            || $parent === 'system'
            || $parent === 'site';
    }

    /**
     * Build references available inside the current category preset.
     *
     * @param array<int, mixed> $items Raw items.
     * @return array<string, bool>
     */
    private function build_preset_reference_map(array $items): array {
        $refs = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            foreach (['key', 'idnumber', 'name'] as $field) {
                if ($item[$field] !== '') {
                    $refs[$item[$field]] = true;
                }
            }
        }

        return $refs;
    }

    /**
     * Normalise a raw category item.
     *
     * Supports canonical runtime fields and legacy aliases:
     * - parent is canonical.
     * - parent_idnumber may populate parent.
     *
     * @param mixed $rawitem Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $rawitem): array {
        if ($rawitem instanceof stdClass) {
            $rawitem = (array)$rawitem;
        }

        if (!is_array($rawitem)) {
            $rawitem = [];
        }

        $metadata = $this->normalise_metadata($rawitem['metadata'] ?? []);

        $key = $this->string_value($rawitem, ['key', 'shortname', 'idnumber', 'code']);
        $idnumber = $this->string_value($rawitem, ['idnumber', 'id_number', 'category_idnumber', 'category']);
        $name = $this->string_value($rawitem, ['name', 'fullname', 'title', 'displayname']);
        $parent = $this->string_value($rawitem, ['parent', 'parent_idnumber', 'parentkey', 'parent_key', 'parentidnumber']);
        $description = $this->string_value($rawitem, ['description', 'summary', 'intro']);

        if ($key === '' && $idnumber !== '') {
            $key = $this->normalise_key($idnumber);
        }

        if ($idnumber === '' && $key !== '') {
            $idnumber = $key;
        }

        if ($name === '' && $key !== '') {
            $name = $this->fallback_name($key);
        }

        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;

        if (!array_key_exists('seeded_by', $metadata)) {
            $metadata['seeded_by'] = self::MANAGED_BY;
        }

        if (!array_key_exists('source_preset', $metadata)) {
            $metadata['source_preset'] = self::PRESET;
        }

        return [
            'key' => clean_param($this->normalise_key($key), PARAM_ALPHANUMEXT),
            'name' => clean_param($name, PARAM_TEXT),
            'idnumber' => clean_param($idnumber, PARAM_TEXT),
            'parent' => clean_param($parent, PARAM_TEXT),
            'parent_idnumber' => clean_param(
                $this->string_value($rawitem, ['parent_idnumber', 'parent']),
                PARAM_TEXT
            ),
            'description' => clean_param($description, PARAM_RAW),
            'descriptionformat' => (int)($rawitem['descriptionformat'] ?? $rawitem['summaryformat'] ?? FORMAT_HTML),
            'sortorder' => (int)($rawitem['sortorder'] ?? 0),
            'visible' => $this->normalise_visible($rawitem['visible'] ?? $rawitem['visibility'] ?? 1),
            'metadata' => $metadata,
        ];
    }

    /**
     * Get the first non-empty string value from a row.
     *
     * @param array<string, mixed> $row Row.
     * @param string[] $keys Candidate keys.
     * @return string
     */
    private function string_value(array $row, array $keys): string {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];

            if ($value === null || $value === '') {
                continue;
            }

            if (is_scalar($value)) {
                return trim((string)$value);
            }
        }

        return '';
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Normalise a key/idnumber-like string.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_:-]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Generate fallback name.
     *
     * @param string $key Key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $name = str_replace(['_', '-'], ' ', $key);
        $name = trim($name);

        return $name === '' ? '' : \core_text::strtotitle($name);
    }

    /**
     * Normalise visibility to Moodle integer 0/1.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function normalise_visible(mixed $value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value > 0 ? 1 : 0;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            if (in_array($value, ['1', 'true', 'yes', 'y', 'visible', 'public', 'course', 'institution', 'active'], true)) {
                return 1;
            }

            if (in_array($value, ['0', 'false', 'no', 'n', 'hidden', 'private', 'draft', 'inactive'], true)) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Normalise execution mode.
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
     * Return whether options request dry run.
     *
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    private function is_dry_run(array $options): bool {
        return !empty($options['dryrun'])
            || !empty($options['dry_run'])
            || (($options['mode'] ?? '') === self::MODE_DRY_RUN);
    }

    /**
     * Create a validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'ok' => true,
            'haserrors' => false,
            'haswarnings' => false,
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
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ];

        if (method_exists(validation_result::class, 'from_array')) {
            return validation_result::from_array($data);
        }

        if (method_exists(validation_result::class, 'from_data')) {
            return validation_result::from_data($data);
        }

        return new validation_result(self::STATUS_COMPLETED, $summary, $data['metadata']);
    }

    /**
     * Add a result message.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $message Message.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $message,
        string $targetkey = '',
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
            $this->increment($result, 'failed');
            $this->increment($result, 'errors');
        } else if ($severity === self::SEVERITY_WARNING) {
            $this->increment($result, 'warnings');
        }
    }

    /**
     * Increment a result counter.
     *
     * @param validation_result $result Result.
     * @param string $counter Counter key.
     */
    private function increment(validation_result $result, string $counter): void {
        if (method_exists($result, 'increment')) {
            $result->increment($counter);
        }
    }

    /**
     * Count errors from result payload.
     *
     * @param validation_result $result Result.
     * @return int
     */
    private function count_errors(validation_result $result): int {
        $data = $this->result_to_array($result);

        return (int)($data['counts']['errors'] ?? $data['errors'] ?? 0);
    }

    /**
     * Finalise result status and optional summary.
     *
     * @param validation_result $result Result.
     * @param string|null $summary Optional summary.
     */
    private function finalise_result(validation_result $result, ?string $summary = null): void {
        if ($result->has_errors()) {
            if (method_exists($result, 'set_status')) {
                $result->set_status(self::STATUS_FAILED);
            }
        } else if ($result->has_warnings()) {
            if (method_exists($result, 'set_status')) {
                $result->set_status(self::STATUS_WARNING);
            }
        } else if (method_exists($result, 'set_status')) {
            $result->set_status(self::STATUS_COMPLETED);
        }

        if ($summary !== null && method_exists($result, 'set_summary')) {
            $result->set_summary($summary);
        } else if (method_exists($result, 'complete')) {
            $result->complete($summary ?? $result->get_summary());
        }
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


