<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Category preset seeder for tool_uckkseed.
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
 * Seeds Moodle course categories from admin/tool/uckkseed/presets/categories.json.
 *
 * Expected preset item shape:
 *
 * [
 *     'key' => '01_tronc_commun_obligatoire',
 *     'name' => '01_Tronc_commun_obligatoire',
 *     'idnumber' => 'uckk_cat_01_tronc_commun_obligatoire',
 *     'parent' => 'uckk',
 *     'description' => '...',
 *     'sortorder' => 10,
 *     'visible' => true,
 *     'metadata' => [],
 * ]
 *
 * Idempotency key:
 * - course_categories.idnumber
 *
 * This class does not create courses, cohorts, roles, capabilities,
 * competencies, badges, reports, activities, or archive records.
 */
final class category_seed {
    /** Preset id handled by this class. */
    public const PRESET = 'categories';

    /** Target type used in logs/results. */
    public const TARGET_TYPE = 'category';

    /** Component name. */
    public const COMPONENT = 'tool_uckkseed';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Result severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Result severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Result severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Result severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Result severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /**
     * Validate category preset rows.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $messages = [];
        $counts = $this->empty_counts();
        $seenidnumbers = [];
        $seenkeys = [];

        if (empty($items)) {
            $messages[] = $this->message(
                self::SEVERITY_WARNING,
                '',
                'categories',
                get_string('seedpresetempty', 'tool_uckkseed')
            );
            $counts['warnings']++;

            return $this->build_result($messages, $counts, [
                'summary' => get_string('validationcompletedwithwarnings', 'tool_uckkseed'),
            ]);
        }

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'index_' . $index;

            if ($item['key'] === '') {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategorymissingkey', 'tool_uckkseed')
                );
                $counts['errors']++;
            }

            if ($item['name'] === '') {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategorymissingname', 'tool_uckkseed')
                );
                $counts['errors']++;
            }

            if ($item['idnumber'] === '') {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategorymissingidnumber', 'tool_uckkseed')
                );
                $counts['errors']++;
            }

            if ($item['key'] !== '' && in_array($item['key'], $seenkeys, true)) {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategoryduplicatekey', 'tool_uckkseed', $item['key'])
                );
                $counts['errors']++;
            }

            if ($item['idnumber'] !== '' && in_array($item['idnumber'], $seenidnumbers, true)) {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategoryduplicateidnumber', 'tool_uckkseed', $item['idnumber'])
                );
                $counts['errors']++;
            }

            if ($item['parent'] !== '' && !$this->parent_exists_in_preset($item['parent'], $items)) {
                $messages[] = $this->message(
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'categories',
                    get_string('seedcategoryparentnotinpreset', 'tool_uckkseed', $item['parent'])
                );
                $counts['warnings']++;
            }

            if ($item['key'] !== '') {
                $seenkeys[] = $item['key'];
            }

            if ($item['idnumber'] !== '') {
                $seenidnumbers[] = $item['idnumber'];
            }
        }

        if ($counts['errors'] === 0 && $counts['warnings'] === 0) {
            $messages[] = $this->message(
                self::SEVERITY_SUCCESS,
                '',
                'categories',
                get_string('seedcategoryvalidationok', 'tool_uckkseed')
            );
        }

        return $this->build_result($messages, $counts, [
            'summary' => $counts['errors'] > 0
                ? get_string('validationfailed', 'tool_uckkseed')
                : get_string('validationcompleted', 'tool_uckkseed'),
        ]);
    }

    /**
     * Apply category preset rows.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $messages = [];
        $counts = $this->empty_counts();

        $validation = $this->validate($items, $options);
        $validationdata = $this->result_to_array($validation);

        if (!empty($validationdata['haserrors'])) {
            return $validation;
        }

        $items = $this->sort_items_by_parent($items);

        $categorymap = $this->build_existing_category_map();

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : $item['idnumber'];

            if ($item['idnumber'] === '') {
                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategorymissingidnumber', 'tool_uckkseed')
                );
                $counts['errors']++;
                $counts['failed']++;
                continue;
            }

            $existing = $this->find_category_by_idnumber($item['idnumber']);

            if ($mode !== self::MODE_APPLY) {
                $messages[] = $this->message(
                    self::SEVERITY_INFO,
                    $targetkey,
                    'categories',
                    $existing
                        ? get_string('seedcategorywouldupdate', 'tool_uckkseed', $item['idnumber'])
                        : get_string('seedcategorywouldcreate', 'tool_uckkseed', $item['idnumber'])
                );
                $counts['skipped']++;
                continue;
            }

            $parentid = $this->resolve_parent_id($item, $categorymap);

            try {
                if ($existing) {
                    $changed = $this->update_category($existing, $item, $parentid);

                    if ($changed) {
                        $counts['updated']++;
                        $messages[] = $this->message(
                            self::SEVERITY_SUCCESS,
                            $targetkey,
                            'categories',
                            get_string('seedcategoryupdated', 'tool_uckkseed', $item['idnumber'])
                        );
                    } else {
                        $counts['skipped']++;
                        $messages[] = $this->message(
                            self::SEVERITY_INFO,
                            $targetkey,
                            'categories',
                            get_string('seedcategoryunchanged', 'tool_uckkseed', $item['idnumber'])
                        );
                    }

                    $categorymap[$item['key']] = (int)$existing->id;
                    $categorymap[$item['idnumber']] = (int)$existing->id;
                    continue;
                }

                $category = $this->create_category($item, $parentid);
                $counts['created']++;

                $categorymap[$item['key']] = (int)$category->id;
                $categorymap[$item['idnumber']] = (int)$category->id;

                $messages[] = $this->message(
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'categories',
                    get_string('seedcategorycreated', 'tool_uckkseed', $item['idnumber'])
                );
            } catch (\Throwable $exception) {
                $counts['failed']++;
                $counts['errors']++;

                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategoryfailed', 'tool_uckkseed', [
                        'idnumber' => $item['idnumber'],
                        'message' => $exception->getMessage(),
                    ]),
                    [
                        'exception' => get_class($exception),
                    ]
                );
            }
        }

        // Repair Moodle category ordering after changes. This uses Moodle core's
        // own ordering repair rather than writing sortorder directly.
        if ($mode === self::MODE_APPLY && function_exists('fix_course_sortorder')) {
            fix_course_sortorder();
        }

        $summary = $counts['errors'] > 0
            ? get_string('seedcompletedwitherrors', 'tool_uckkseed')
            : get_string('seedcompleted', 'tool_uckkseed');

        return $this->build_result($messages, $counts, [
            'summary' => $summary,
            'metadata' => [
                'mode' => $mode,
                'preset' => self::PRESET,
                'table' => 'course_categories',
            ],
        ]);
    }

    /**
     * Reset seeded categories.
     *
     * This method is conservative. It does not delete categories that contain
     * courses or child categories unless force is explicitly provided.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $force = !empty($options['force']);
        $confirm = !empty($options['confirm']);
        $messages = [];
        $counts = $this->empty_counts();

        if (!$confirm) {
            $messages[] = $this->message(
                self::SEVERITY_BLOCKER,
                '',
                'categories',
                get_string('resetrequiresconfirmation', 'tool_uckkseed')
            );
            $counts['errors']++;

            return $this->build_result($messages, $counts, [
                'summary' => get_string('resetblocked', 'tool_uckkseed'),
            ]);
        }

        $items = array_reverse($this->sort_items_by_parent($items));

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : $item['idnumber'];

            if ($item['idnumber'] === '') {
                $counts['skipped']++;
                continue;
            }

            $category = $this->find_category_by_idnumber($item['idnumber']);

            if (!$category) {
                $counts['skipped']++;
                $messages[] = $this->message(
                    self::SEVERITY_INFO,
                    $targetkey,
                    'categories',
                    get_string('seedcategorynotfound', 'tool_uckkseed', $item['idnumber'])
                );
                continue;
            }

            $coursecount = $DB->count_records('course', ['category' => $category->id]);
            $childcount = $DB->count_records('course_categories', ['parent' => $category->id]);

            if (($coursecount > 0 || $childcount > 0) && !$force) {
                $counts['skipped']++;
                $counts['warnings']++;

                $messages[] = $this->message(
                    self::SEVERITY_WARNING,
                    $targetkey,
                    'categories',
                    get_string('seedcategoryresetblockednotempty', 'tool_uckkseed', [
                        'idnumber' => $item['idnumber'],
                        'courses' => $coursecount,
                        'children' => $childcount,
                    ])
                );
                continue;
            }

            if ($mode !== self::MODE_APPLY) {
                $counts['skipped']++;
                $messages[] = $this->message(
                    self::SEVERITY_INFO,
                    $targetkey,
                    'categories',
                    get_string('seedcategorywoulddelete', 'tool_uckkseed', $item['idnumber'])
                );
                continue;
            }

            try {
                $categoryobject = core_course_category::get((int)$category->id, MUST_EXIST, true);
                $categoryobject->delete_full(false);
                $counts['updated']++;

                $messages[] = $this->message(
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    'categories',
                    get_string('seedcategorydeleted', 'tool_uckkseed', $item['idnumber'])
                );
            } catch (\Throwable $exception) {
                $counts['failed']++;
                $counts['errors']++;

                $messages[] = $this->message(
                    self::SEVERITY_ERROR,
                    $targetkey,
                    'categories',
                    get_string('seedcategorydeletefailed', 'tool_uckkseed', [
                        'idnumber' => $item['idnumber'],
                        'message' => $exception->getMessage(),
                    ])
                );
            }
        }

        return $this->build_result($messages, $counts, [
            'summary' => $counts['errors'] > 0
                ? get_string('resetcompletedwitherrors', 'tool_uckkseed')
                : get_string('resetcompleted', 'tool_uckkseed'),
            'metadata' => [
                'mode' => $mode,
                'preset' => self::PRESET,
                'force' => $force,
            ],
        ]);
    }

    /**
     * Export existing UCKK categories to canonical preset shape.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $prefix = (string)($options['idnumberprefix'] ?? 'uckk');
        $categories = $DB->get_records_select(
            'course_categories',
            $DB->sql_like('idnumber', ':prefix', false),
            [
                'prefix' => $prefix . '%',
            ],
            'sortorder ASC, name ASC',
            'id, name, idnumber, parent, description, descriptionformat, visible, sortorder, timecreated, timemodified'
        );

        $parentmap = [];
        foreach ($categories as $category) {
            $parentmap[(int)$category->id] = (string)$category->idnumber;
        }

        $items = [];
        foreach ($categories as $category) {
            $key = $this->key_from_idnumber((string)$category->idnumber);

            $items[] = [
                'key' => $key,
                'name' => (string)$category->name,
                'idnumber' => (string)$category->idnumber,
                'parent' => !empty($category->parent) && isset($parentmap[(int)$category->parent])
                    ? $parentmap[(int)$category->parent]
                    : '',
                'description' => (string)$category->description,
                'sortorder' => (int)$category->sortorder,
                'visible' => (bool)$category->visible,
                'metadata' => [
                    'source' => 'tool_uckkseed_export',
                    'timecreated' => (int)$category->timecreated,
                    'timemodified' => (int)$category->timemodified,
                ],
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
     * Create a Moodle category.
     *
     * @param array<string, mixed> $item Normalised preset item.
     * @param int $parentid Parent category id.
     * @return stdClass|core_course_category
     */
    private function create_category(array $item, int $parentid): stdClass|core_course_category {
        $data = new stdClass();
        $data->name = $item['name'];
        $data->idnumber = $item['idnumber'];
        $data->parent = $parentid;
        $data->description = $item['description'];
        $data->descriptionformat = FORMAT_HTML;
        $data->visible = $item['visible'] ? 1 : 0;

        $category = create_course_category($data);

        $this->write_metadata((int)$category->id, $item);

        return $category;
    }

    /**
     * Update a Moodle category if needed.
     *
     * @param stdClass $existing Existing category record.
     * @param array<string, mixed> $item Normalised preset item.
     * @param int $parentid Parent category id.
     * @return bool Whether the category changed.
     */
    private function update_category(stdClass $existing, array $item, int $parentid): bool {
        $changed = false;

        $data = new stdClass();
        $data->id = (int)$existing->id;

        if ((string)$existing->name !== $item['name']) {
            $data->name = $item['name'];
            $changed = true;
        }

        if ((string)$existing->idnumber !== $item['idnumber']) {
            $data->idnumber = $item['idnumber'];
            $changed = true;
        }

        if ((int)$existing->parent !== $parentid) {
            $data->parent = $parentid;
            $changed = true;
        }

        if ((string)$existing->description !== $item['description']) {
            $data->description = $item['description'];
            $data->descriptionformat = FORMAT_HTML;
            $changed = true;
        }

        if ((int)$existing->visible !== ($item['visible'] ? 1 : 0)) {
            $data->visible = $item['visible'] ? 1 : 0;
            $changed = true;
        }

        if ($changed) {
            update_course_category($data);
        }

        $metadatachanged = $this->write_metadata((int)$existing->id, $item);

        return $changed || $metadatachanged;
    }

    /**
     * Store stable UCKK seed metadata in Moodle custom fields when available.
     *
     * For Moodle portability, this currently stores metadata only if future
     * custom-field integration adds a handler. Stable matching still uses
     * course_categories.idnumber.
     *
     * @param int $categoryid Category id.
     * @param array<string, mixed> $item Normalised item.
     * @return bool Whether metadata changed.
     */
    private function write_metadata(int $categoryid, array $item): bool {
        // Course category custom fields are optional and site-specific.
        // The canonical, portable seed key is course_categories.idnumber.
        // Keep this method as the alignment point for future metadata handling.
        return false;
    }

    /**
     * Resolve parent category id from preset parent key/idnumber.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param array<string, int> $categorymap Known key/idnumber => id map.
     * @return int
     */
    private function resolve_parent_id(array $item, array $categorymap): int {
        if ($item['parent'] === '') {
            return 0;
        }

        if (isset($categorymap[$item['parent']])) {
            return (int)$categorymap[$item['parent']];
        }

        $parent = $this->find_category_by_idnumber($item['parent']);

        if ($parent) {
            return (int)$parent->id;
        }

        return 0;
    }

    /**
     * Build existing category map by idnumber.
     *
     * @return array<string, int>
     */
    private function build_existing_category_map(): array {
        global $DB;

        $map = [];
        $categories = $DB->get_records_select(
            'course_categories',
            "idnumber <> ''",
            [],
            '',
            'id, idnumber'
        );

        foreach ($categories as $category) {
            $map[(string)$category->idnumber] = (int)$category->id;
            $map[$this->key_from_idnumber((string)$category->idnumber)] = (int)$category->id;
        }

        return $map;
    }

    /**
     * Find category by idnumber.
     *
     * @param string $idnumber Category idnumber.
     * @return stdClass|null
     */
    private function find_category_by_idnumber(string $idnumber): ?stdClass {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        $record = $DB->get_record(
            'course_categories',
            ['idnumber' => $idnumber],
            'id, name, idnumber, parent, description, descriptionformat, visible, sortorder',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Sort categories so parents are processed before children.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Raw items.
     * @return array<int, array<string, mixed>>
     */
    private function sort_items_by_parent(array $items): array {
        $normalised = array_map([$this, 'normalise_item'], $items);
        $bykey = [];

        foreach ($normalised as $item) {
            $bykey[$item['key']] = $item;
            $bykey[$item['idnumber']] = $item;
        }

        usort($normalised, static function (array $a, array $b) use ($bykey): int {
            if ($a['parent'] === '' && $b['parent'] !== '') {
                return -1;
            }

            if ($a['parent'] !== '' && $b['parent'] === '') {
                return 1;
            }

            if ($a['parent'] === $b['key'] || $a['parent'] === $b['idnumber']) {
                return 1;
            }

            if ($b['parent'] === $a['key'] || $b['parent'] === $a['idnumber']) {
                return -1;
            }

            return $a['sortorder'] <=> $b['sortorder'];
        });

        return $normalised;
    }

    /**
     * Whether a parent reference exists inside the preset item list.
     *
     * @param string $parent Parent reference.
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @return bool
     */
    private function parent_exists_in_preset(string $parent, array $items): bool {
        if ($parent === '') {
            return true;
        }

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['key'] === $parent || $item['idnumber'] === $parent) {
                return true;
            }
        }

        return $this->find_category_by_idnumber($parent) !== null;
    }

    /**
     * Normalise one preset item.
     *
     * @param array<string, mixed>|stdClass $rawitem Raw preset row.
     * @return array<string, mixed>
     */
    private function normalise_item(array|stdClass $rawitem): array {
        $item = (array)$rawitem;

        $key = clean_param((string)($item['key'] ?? ''), PARAM_ALPHANUMEXT);
        $idnumber = clean_param((string)($item['idnumber'] ?? $item['shortname'] ?? ''), PARAM_TEXT);

        if ($key === '' && $idnumber !== '') {
            $key = $this->key_from_idnumber($idnumber);
        }

        $visible = $item['visible'] ?? true;

        if (is_string($visible)) {
            $visible = !in_array(strtolower($visible), ['0', 'false', 'no', 'hidden'], true);
        }

        return [
            'key' => $key,
            'name' => trim(clean_param((string)($item['name'] ?? ''), PARAM_TEXT)),
            'idnumber' => trim($idnumber),
            'parent' => clean_param((string)($item['parent'] ?? ''), PARAM_TEXT),
            'description' => clean_text((string)($item['description'] ?? ''), FORMAT_HTML),
            'sortorder' => max(0, (int)($item['sortorder'] ?? 0)),
            'visible' => (bool)$visible,
            'metadata' => $this->normalise_metadata($item['metadata'] ?? []),
        ];
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return [];
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return [];
    }

    /**
     * Convert an idnumber to a stable preset key.
     *
     * @param string $idnumber Category idnumber.
     * @return string
     */
    private function key_from_idnumber(string $idnumber): string {
        $key = strtolower(trim($idnumber));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? $key;
        $key = trim($key, '_');

        return clean_param($key, PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        return in_array($mode, [
            self::MODE_DRY_RUN,
            self::MODE_APPLY,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ], true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Empty canonical counts.
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
     * Build a canonical message row.
     *
     * @param string $severity Severity.
     * @param string $targetkey Target key.
     * @param string $preset Preset id.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     * @return array<string, mixed>
     */
    private function message(
        string $severity,
        string $targetkey,
        string $preset,
        string $message,
        array $metadata = []
    ): array {
        return [
            'severity' => $severity,
            'component' => self::COMPONENT,
            'preset' => $preset,
            'targettype' => self::TARGET_TYPE,
            'targetkey' => $targetkey,
            'message' => $message,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build a validation_result while keeping one construction point.
     *
     * The companion validation_result class should support from_array().
     *
     * @param array<int, array<string, mixed>> $messages Message rows.
     * @param array<string, int> $counts Counts.
     * @param array<string, mixed> $extra Extra result data.
     * @return validation_result
     */
    private function build_result(array $messages, array $counts, array $extra = []): validation_result {
        $haserrors = $counts['errors'] > 0;
        $haswarnings = $counts['warnings'] > 0;

        $data = array_merge([
            'status' => $haserrors ? 'failed' : ($haswarnings ? 'warning' : 'completed'),
            'ok' => !$haserrors,
            'haserrors' => $haserrors,
            'haswarnings' => $haswarnings,
            'summary' => '',
            'counts' => $counts,
            'messages' => $messages,
            'created' => $counts['created'],
            'updated' => $counts['updated'],
            'skipped' => $counts['skipped'],
            'failed' => $counts['failed'],
            'metadata' => [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ],
        ], $extra);

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
            return $result->to_array();
        }

        if (method_exists($result, 'to_export')) {
            return (array)$result->to_export();
        }

        return get_object_vars($result);
    }
}
```

Required language strings for `admin/tool/uckkseed/lang/en/tool_uckkseed.php`:

```php
$string['resetblocked'] = 'Reset blocked.';
$string['resetcompleted'] = 'Reset completed.';
$string['resetcompletedwitherrors'] = 'Reset completed with errors.';
$string['resetrequiresconfirmation'] = 'Reset requires explicit confirmation.';
$string['seedcategorycreated'] = 'Category created: {$a}';
$string['seedcategorydeleted'] = 'Category deleted: {$a}';
$string['seedcategorydeletefailed'] = 'Could not delete category {$a->idnumber}: {$a->message}';
$string['seedcategoryduplicateidnumber'] = 'Duplicate category idnumber in preset: {$a}';
$string['seedcategoryduplicatekey'] = 'Duplicate category key in preset: {$a}';
$string['seedcategoryfailed'] = 'Could not seed category {$a->idnumber}: {$a->message}';
$string['seedcategorymissingidnumber'] = 'A category preset item is missing idnumber.';
$string['seedcategorymissingkey'] = 'A category preset item is missing key.';
$string['seedcategorymissingname'] = 'A category preset item is missing name.';
$string['seedcategorynotfound'] = 'Category not found: {$a}';
$string['seedcategoryparentnotinpreset'] = 'Category parent is not present in the preset and does not currently exist: {$a}';
$string['seedcategoryresetblockednotempty'] = 'Category {$a->idnumber} was not deleted because it contains {$a->courses} course(s) and {$a->children} child category/categories.';
$string['seedcategoryunchanged'] = 'Category already up to date: {$a}';
$string['seedcategoryupdated'] = 'Category updated: {$a}';
$string['seedcategoryvalidationok'] = 'Category preset validation passed.';
$string['seedcategorywouldcreate'] = 'Category would be created: {$a}';
$string['seedcategorywoulddelete'] = 'Category would be deleted: {$a}';
$string['seedcategorywouldupdate'] = 'Category would be updated: {$a}';
$string['seedcompleted'] = 'Seed completed.';
$string['seedcompletedwitherrors'] = 'Seed completed with errors.';
$string['seedpresetempty'] = 'The preset contains no items.';
$string['validationcompleted'] = 'Validation completed.';
$string['validationcompletedwithwarnings'] = 'Validation completed with warnings.';
$string['validationfailed'] = 'Validation failed.';
```

French strings for `admin/tool/uckkseed/lang/fr/tool_uckkseed.php`:

```php
$string['resetblocked'] = 'Réinitialisation bloquée.';
$string['resetcompleted'] = 'Réinitialisation terminée.';
$string['resetcompletedwitherrors'] = 'Réinitialisation terminée avec erreurs.';
$string['resetrequiresconfirmation'] = 'La réinitialisation exige une confirmation explicite.';
$string['seedcategorycreated'] = 'Catégorie créée : {$a}';
$string['seedcategorydeleted'] = 'Catégorie supprimée : {$a}';
$string['seedcategorydeletefailed'] = 'Impossible de supprimer la catégorie {$a->idnumber} : {$a->message}';
$string['seedcategoryduplicateidnumber'] = 'Identifiant de catégorie dupliqué dans le preset : {$a}';
$string['seedcategoryduplicatekey'] = 'Clé de catégorie dupliquée dans le preset : {$a}';
$string['seedcategoryfailed'] = 'Impossible de semer la catégorie {$a->idnumber} : {$a->message}';
$string['seedcategorymissingidnumber'] = 'Un élément de preset de catégorie n’a pas d’idnumber.';
$string['seedcategorymissingkey'] = 'Un élément de preset de catégorie n’a pas de clé.';
$string['seedcategorymissingname'] = 'Un élément de preset de catégorie n’a pas de nom.';
$string['seedcategorynotfound'] = 'Catégorie introuvable : {$a}';
$string['seedcategoryparentnotinpreset'] = 'La catégorie parente n’est pas présente dans le preset et n’existe pas encore : {$a}';
$string['seedcategoryresetblockednotempty'] = 'La catégorie {$a->idnumber} n’a pas été supprimée parce qu’elle contient {$a->courses} cours et {$a->children} sous-catégorie(s).';
$string['seedcategoryunchanged'] = 'Catégorie déjà à jour : {$a}';
$string['seedcategoryupdated'] = 'Catégorie mise à jour : {$a}';
$string['seedcategoryvalidationok'] = 'La validation du preset des catégories a réussi.';
$string['seedcategorywouldcreate'] = 'La catégorie serait créée : {$a}';
$string['seedcategorywoulddelete'] = 'La catégorie serait supprimée : {$a}';
$string['seedcategorywouldupdate'] = 'La catégorie serait mise à jour : {$a}';
$string['seedcompleted'] = 'Seed terminé.';
$string['seedcompletedwitherrors'] = 'Seed terminé avec erreurs.';
$string['seedpresetempty'] = 'Le preset ne contient aucun élément.';
$string['validationcompleted'] = 'Validation terminée.';
$string['validationcompletedwithwarnings'] = 'Validation terminée avec avertissements.';
$string['validationfailed'] = 'Validation échouée.';

