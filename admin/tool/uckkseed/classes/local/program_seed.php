<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Program preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use context_coursecat;
use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Seeds UCKK program registry records.
 *
 * Programs are internal UCKK academic registry objects. This seeder does not
 * create Moodle courses, assign users, award badges, certify competencies, or
 * create Moodle roles.
 */
final class program_seed {
    /** Component owning this seeder. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'programs';

    /** Target type used in validation messages. */
    public const TARGET_TYPE = 'program';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Preset version. */
    public const VERSION = 2026051200;

    /** local_uckk program table. */
    private const TABLE = 'local_uckk_program';

    /** Metadata owner marker. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

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

    /** Allowed execution modes. */
    private const MODES = [
        self::MODE_DRY_RUN,
        self::MODE_APPLY,
        self::MODE_REPORT,
        self::MODE_ROLLBACK_PLAN,
    ];

    /**
     * Validate program preset items.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = validation_result::success('Program preset validated.', [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
        ]);

        if (!$this->table_exists()) {
            $result->add_message(
                self::SEVERITY_BLOCKER,
                'Required table local_uckk_program does not exist.',
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                ''
            );
            $result->complete('Program validation failed.');
            return $result;
        }

        if (empty($items)) {
            $result->add_message(
                self::SEVERITY_WARNING,
                'Preset programs is empty.',
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                ''
            );
            $result->complete('Program validation completed with warnings.');
            return $result;
        }

        $seenkeys = [];
        $seenshortnames = [];
        $seenidnumbers = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;

            if ($item['key'] === '') {
                $this->add_error($result, $targetkey, 'Program key is required.');
            } else if (isset($seenkeys[$item['key']])) {
                $this->add_error($result, $targetkey, 'Duplicate program key: ' . $item['key']);
            } else {
                $seenkeys[$item['key']] = true;
            }

            if ($item['shortname'] === '') {
                $this->add_error($result, $targetkey, 'Program shortname is required.');
            } else if (isset($seenshortnames[$item['shortname']])) {
                $this->add_error($result, $targetkey, 'Duplicate program shortname: ' . $item['shortname']);
            } else {
                $seenshortnames[$item['shortname']] = true;
            }

            if ($item['fullname'] === '') {
                $this->add_error($result, $targetkey, 'Program fullname is required.');
            }

            if ($item['idnumber'] === '') {
                $this->add_error($result, $targetkey, 'Program idnumber is required.');
            } else if (isset($seenidnumbers[$item['idnumber']])) {
                $this->add_error($result, $targetkey, 'Duplicate program idnumber: ' . $item['idnumber']);
            } else {
                $seenidnumbers[$item['idnumber']] = true;
            }

            if ($item['programtype'] === '') {
                $this->add_error($result, $targetkey, 'Program type is required.');
            }

            if ($item['category'] === '' && $item['categoryidnumber'] === '') {
                $result->add_message(
                    self::SEVERITY_WARNING,
                    'Program has no category/category_idnumber. It will be stored at system context.',
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    ['shortname' => $item['shortname']]
                );
            } else if ($this->resolve_category_id($item) === 0) {
                $result->add_message(
                    self::SEVERITY_WARNING,
                    'Program category is not present in Moodle yet: ' . ($item['category'] ?: $item['categoryidnumber']),
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    [
                        'category' => $item['category'],
                        'category_idnumber' => $item['categoryidnumber'],
                    ]
                );
            }

            if (!$this->valid_status($item['status'])) {
                $this->add_error($result, $targetkey, 'Invalid program status: ' . $item['status']);
            }

            if (!$this->valid_visibility($item['visibility'])) {
                $this->add_error($result, $targetkey, 'Invalid program visibility: ' . $item['visibility']);
            }

            $result->add_skipped(self::TARGET_TYPE, $targetkey, 0, [
                'shortname' => $item['shortname'],
                'idnumber' => $item['idnumber'],
            ]);
        }

        $result->complete($result->has_errors() ? 'Program validation failed.' : 'Program validation completed.');
        return $result;
    }

    /**
     * Apply program preset items.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN
            || $mode === self::MODE_ROLLBACK_PLAN
            || !empty($options['dryrun'])
            || !empty($options['dry_run']);

        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $result = validation_result::success(
            $dryrun ? 'Program dry run completed.' : 'Program seed completed.',
            [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ]
        );

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;
            $record = $this->build_record($item);
            $existing = $this->get_existing_program($item);

            if ($dryrun) {
                $result->add_message(
                    self::SEVERITY_INFO,
                    $existing ? 'Program would be updated.' : 'Program would be created.',
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    [
                        'existingid' => $existing ? (int)$existing->id : 0,
                        'record' => $record,
                    ]
                );
                $result->add_skipped(self::TARGET_TYPE, $targetkey, $existing ? (int)$existing->id : 0);
                continue;
            }

            if ($existing) {
                $record->id = (int)$existing->id;
                $this->update_program_record($record);

                $result->add_message(
                    self::SEVERITY_SUCCESS,
                    'Program updated: ' . $item['shortname'],
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    ['programid' => (int)$existing->id]
                );
                $result->add_updated(self::TARGET_TYPE, $targetkey, (int)$existing->id);
            } else {
                $programid = $this->insert_program_record($record);

                $result->add_message(
                    self::SEVERITY_SUCCESS,
                    'Program created: ' . $item['shortname'],
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    ['programid' => $programid]
                );
                $result->add_created(self::TARGET_TYPE, $targetkey, $programid);
            }
        }

        $result->complete($dryrun ? 'Program dry run completed.' : 'Program seed completed.');
        return $result;
    }

    /**
     * Reset seed-managed programs.
     *
     * Reset is conservative: it archives records managed by tool_uckkseed by
     * default. Hard deletion is not used because pathways can reference programs.
     *
     * @param array<int, mixed> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB, $USER;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $confirmed = !empty($options['confirm']);

        $result = validation_result::success(
            $dryrun ? 'Program reset dry run completed.' : 'Program reset completed.',
            [
                'component' => self::COMPONENT,
                'preset' => self::PRESET,
                'targettype' => self::TARGET_TYPE,
            ]
        );

        if (!$this->table_exists()) {
            $result->add_message(
                self::SEVERITY_BLOCKER,
                'Required table local_uckk_program does not exist.',
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                ''
            );
            $result->complete('Program reset failed.');
            return $result;
        }

        if (!$dryrun && !$confirmed) {
            $result->add_message(
                self::SEVERITY_BLOCKER,
                'Reset requires confirm=1.',
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                ''
            );
            $result->complete('Program reset failed.');
            return $result;
        }

        $keys = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);

            if ($item['shortname'] !== '') {
                $keys[$item['shortname']] = true;
            }
        }

        if (empty($keys)) {
            $records = $DB->get_records(self::TABLE);
        } else {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($keys), SQL_PARAMS_NAMED);
            $records = $DB->get_records_select(self::TABLE, "shortname {$insql}", $params);
        }

        foreach ($records as $record) {
            if (!$this->is_seed_managed($record) && !isset($keys[(string)$record->shortname])) {
                continue;
            }

            $targetkey = (string)$record->shortname;

            if ($dryrun) {
                $result->add_message(
                    self::SEVERITY_INFO,
                    'Program would be archived: ' . $targetkey,
                    self::COMPONENT,
                    self::PRESET,
                    self::TARGET_TYPE,
                    $targetkey,
                    ['programid' => (int)$record->id]
                );
                $result->add_skipped(self::TARGET_TYPE, $targetkey, (int)$record->id);
                continue;
            }

            $update = new stdClass();
            $update->id = (int)$record->id;
            $update->status = 'archived';
            $update->modifiedby = (int)($USER->id ?? 0);
            $update->timemodified = time();

            $this->safe_update_record(self::TABLE, $update);

            $result->add_message(
                self::SEVERITY_SUCCESS,
                'Program archived: ' . $targetkey,
                self::COMPONENT,
                self::PRESET,
                self::TARGET_TYPE,
                $targetkey,
                ['programid' => (int)$record->id]
            );
            $result->add_updated(self::TARGET_TYPE, $targetkey, (int)$record->id);
        }

        $result->complete($dryrun ? 'Program reset dry run completed.' : 'Program reset completed.');
        return $result;
    }

    /**
     * Export seed-managed program rows.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];

        if ($this->table_exists()) {
            $records = $DB->get_records(self::TABLE, [], 'sortorder ASC, fullname ASC');

            foreach ($records as $record) {
                if (!empty($options['managedonly']) && !$this->is_seed_managed($record)) {
                    continue;
                }

                $items[] = $this->record_to_item($record);
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
     * Normalise one preset item.
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

        $moodle = $this->normalise_metadata($item['moodle'] ?? []);
        $metadata = $this->normalise_metadata($item['metadata'] ?? []);

        foreach ([
            'id',
            'code',
            'title',
            'short_title',
            'domain',
            'palier',
            'recognition',
            'public_status_notice',
            'category_path',
            'source',
            'tags',
        ] as $field) {
            if (array_key_exists($field, $item) && !array_key_exists($field, $metadata)) {
                $metadata[$field] = $item[$field];
            }
        }

        $key = $this->normalise_key((string)($item['key'] ?? $item['code'] ?? $item['shortname'] ?? $item['idnumber'] ?? 'program_' . $index));
        $shortname = $this->normalise_shortname((string)($item['shortname'] ?? $item['code'] ?? $key));
        $fullname = trim((string)($item['fullname'] ?? $item['name'] ?? $item['title'] ?? $item['short_title'] ?? $shortname));
        $idnumber = trim((string)($item['idnumber'] ?? ($shortname !== '' ? 'UCKK-PROG-' . strtoupper($shortname) : '')));
        $category = trim((string)($item['category'] ?? $item['category_idnumber'] ?? $moodle['category_idnumber'] ?? ''));
        $categoryidnumber = trim((string)($item['category_idnumber'] ?? $moodle['category_idnumber'] ?? $category));

        $metadata['managedby'] = self::MANAGED_BY;
        $metadata['source_preset'] = self::PRESET;
        $metadata['key'] = $key;
        $metadata['idnumber'] = $idnumber;
        $metadata['category'] = $category;
        $metadata['category_idnumber'] = $categoryidnumber;
        $metadata['visible'] = $this->normalise_visible($item['visible'] ?? $moodle['visible'] ?? 1);

        return [
            'key' => $key,
            'shortname' => $shortname,
            'fullname' => $fullname,
            'name' => trim((string)($item['name'] ?? $fullname)),
            'idnumber' => $idnumber,
            'programtype' => $this->normalise_program_type((string)($item['programtype'] ?? $item['program_type'] ?? '')),
            'category' => $category,
            'categoryidnumber' => $categoryidnumber,
            'description' => (string)($item['description'] ?? $item['summary'] ?? ''),
            'descriptionformat' => (int)($item['descriptionformat'] ?? FORMAT_HTML),
            'sortorder' => (int)($item['sortorder'] ?? (($index + 1) * 10)),
            'status' => $this->normalise_status((string)($item['status'] ?? 'active')),
            'visibility' => $this->normalise_visibility((string)($item['visibility'] ?? 'institution')),
            'metadata' => $metadata,
        ];
    }

    /**
     * Build a DB record from a normalised item.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return stdClass
     */
    private function build_record(array $item): stdClass {
        global $USER;

        $now = time();
        $categoryid = $this->resolve_category_id($item);
        $contextid = $categoryid > 0 ? context_coursecat::instance($categoryid)->id : context_system::instance()->id;

        $record = new stdClass();
        $record->shortname = $item['shortname'];
        $record->fullname = $item['fullname'];
        $record->programtype = $item['programtype'];
        $record->categoryid = $categoryid > 0 ? $categoryid : null;
        $record->description = $item['description'];
        $record->descriptionformat = $item['descriptionformat'];
        $record->summary = $item['description'];
        $record->summaryformat = $item['descriptionformat'];
        $record->sortorder = $item['sortorder'];
        $record->courseid = null;
        $record->cmid = null;
        $record->contextid = $contextid;
        $record->userid = null;
        $record->createdby = (int)($USER->id ?? 0);
        $record->modifiedby = (int)($USER->id ?? 0);
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->status = $item['status'];
        $record->visibility = $item['visibility'];
        $record->versionno = 1;
        $record->provenancehash = $this->build_hash($item);
        $record->metadata = $this->encode_json($item['metadata']);

        // Older local_uckk program_api expects these columns in some builds. The
        // safe insert/update helpers remove them if the installed table does not
        // have the columns.
        $record->idnumber = $item['idnumber'];

        return $record;
    }

    /**
     * Insert a record using only fields that exist.
     *
     * @param stdClass $record Record.
     * @return int Insert id.
     */
    private function insert_program_record(stdClass $record): int {
        global $DB;

        $record = $this->filter_record_fields(self::TABLE, $record);

        return (int)$DB->insert_record(self::TABLE, $record);
    }

    /**
     * Update a record using only fields that exist.
     *
     * @param stdClass $record Record.
     */
    private function update_program_record(stdClass $record): void {
        global $DB, $USER;

        $current = $DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST);
        $record->timecreated = (int)($current->timecreated ?? time());
        $record->createdby = (int)($current->createdby ?? ($USER->id ?? 0));
        $record->versionno = (int)($current->versionno ?? 1) + 1;

        $this->safe_update_record(self::TABLE, $record);
    }

    /**
     * Get existing program by shortname.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return stdClass|null
     */
    private function get_existing_program(array $item): ?stdClass {
        global $DB;

        if (!$this->table_exists() || $item['shortname'] === '') {
            return null;
        }

        $record = $DB->get_record(self::TABLE, ['shortname' => $item['shortname']], '*', IGNORE_MISSING);

        if ($record) {
            return $record;
        }

        if ($item['idnumber'] !== '' && $this->table_has_field(self::TABLE, 'idnumber')) {
            return $DB->get_record(self::TABLE, ['idnumber' => $item['idnumber']], '*', IGNORE_MISSING) ?: null;
        }

        return null;
    }

    /**
     * Resolve a Moodle course category id from category/idnumber.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return int Category id or 0.
     */
    private function resolve_category_id(array $item): int {
        global $DB;

        $candidates = array_values(array_unique(array_filter([
            (string)($item['category'] ?? ''),
            (string)($item['categoryidnumber'] ?? ''),
        ])));

        foreach ($candidates as $candidate) {
            if (ctype_digit($candidate)) {
                $exists = $DB->record_exists('course_categories', ['id' => (int)$candidate]);

                if ($exists) {
                    return (int)$candidate;
                }
            }

            $id = $DB->get_field('course_categories', 'id', ['idnumber' => $candidate], IGNORE_MISSING);

            if ($id) {
                return (int)$id;
            }
        }

        return 0;
    }

    /**
     * Check whether this record was seed-managed.
     *
     * @param stdClass $record DB record.
     * @return bool
     */
    private function is_seed_managed(stdClass $record): bool {
        $metadata = $this->normalise_metadata($record->metadata ?? []);

        return ($metadata['managedby'] ?? '') === self::MANAGED_BY
            || ($metadata['seeded_by'] ?? '') === self::MANAGED_BY
            || ($metadata['source_preset'] ?? '') === self::PRESET;
    }

    /**
     * Convert a DB record to a preset item.
     *
     * @param stdClass $record DB record.
     * @return array<string, mixed>
     */
    private function record_to_item(stdClass $record): array {
        $metadata = $this->normalise_metadata($record->metadata ?? []);

        return [
            'key' => (string)($metadata['key'] ?? $record->shortname),
            'name' => (string)$record->fullname,
            'shortname' => (string)$record->shortname,
            'fullname' => (string)$record->fullname,
            'idnumber' => (string)($record->idnumber ?? $metadata['idnumber'] ?? ''),
            'program_type' => (string)$record->programtype,
            'category' => (string)($metadata['category'] ?? ''),
            'category_idnumber' => (string)($metadata['category_idnumber'] ?? ''),
            'description' => (string)($record->description ?? $record->summary ?? ''),
            'status' => (string)($record->status ?? 'active'),
            'visibility' => (string)($record->visibility ?? 'institution'),
            'sortorder' => (int)($record->sortorder ?? 0),
            'metadata' => $metadata,
        ];
    }

    /**
     * Filter record fields to fields that exist in the current DB table.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     * @return stdClass
     */
    private function filter_record_fields(string $table, stdClass $record): stdClass {
        global $DB;

        $columns = $DB->get_columns($table);
        $filtered = new stdClass();

        foreach ((array)$record as $field => $value) {
            if (array_key_exists($field, $columns)) {
                $filtered->{$field} = $value;
            }
        }

        return $filtered;
    }

    /**
     * Update a record using only existing fields.
     *
     * @param string $table Table.
     * @param stdClass $record Record.
     */
    private function safe_update_record(string $table, stdClass $record): void {
        global $DB;

        if (empty($record->id) || !$this->table_exists($table)) {
            return;
        }

        $record = $this->filter_record_fields($table, $record);

        if (!empty($record->id)) {
            $DB->update_record($table, $record);
        }
    }

    /**
     * Whether a DB table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    private function table_exists(string $table = self::TABLE): bool {
        global $DB;

        return $DB->get_manager()->table_exists($table);
    }

    /**
     * Whether a table has a field.
     *
     * @param string $table Table name.
     * @param string $field Field name.
     * @return bool
     */
    private function table_has_field(string $table, string $field): bool {
        global $DB;

        if (!$this->table_exists($table)) {
            return false;
        }

        return array_key_exists($field, $DB->get_columns($table));
    }

    /**
     * Add an error and failed target.
     *
     * @param validation_result $result Result.
     * @param string $targetkey Target key.
     * @param string $message Message.
     */
    private function add_error(validation_result $result, string $targetkey, string $message): void {
        $result->add_message(
            self::SEVERITY_ERROR,
            $message,
            self::COMPONENT,
            self::PRESET,
            self::TARGET_TYPE,
            $targetkey
        );
        $result->add_failed(self::TARGET_TYPE, $targetkey);
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise key.
     *
     * @param string $value Raw key.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/^[a-z]+:/', '', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Normalise shortname.
     *
     * @param string $value Raw shortname.
     * @return string
     */
    private function normalise_shortname(string $value): string {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Normalise program type.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_program_type(string $value): string {
        $value = strtolower(trim($value));
        $value = str_replace(['-', ' ', ':'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = strtolower(trim($status));
        $status = str_replace(['-', ' ', ':'], '_', $status);
        $status = preg_replace('/[^a-z0-9_]+/', '_', $status) ?? $status;

        return $status === '' ? 'active' : $status;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private function normalise_visibility(string $visibility): string {
        $visibility = strtolower(trim($visibility));
        $visibility = str_replace(['-', ' ', ':'], '_', $visibility);
        $visibility = preg_replace('/[^a-z0-9_]+/', '_', $visibility) ?? $visibility;

        return $visibility === '' ? 'institution' : $visibility;
    }

    /**
     * Validate status.
     *
     * @param string $status Status.
     * @return bool
     */
    private function valid_status(string $status): bool {
        return in_array($status, [
            'draft',
            'active',
            'hidden',
            'archived',
            'pending',
            'pending_review',
            'validated',
            'rejected',
            'correction_required',
            'contested',
            'invalidated',
            'closed',
            'cancelled',
            'completed',
            'blocked',
        ], true);
    }

    /**
     * Validate visibility.
     *
     * @param string $visibility Visibility.
     * @return bool
     */
    private function valid_visibility(string $visibility): bool {
        return in_array($visibility, [
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
            'hidden',
            'archived',
        ], true);
    }

    /**
     * Normalise visible value.
     *
     * @param mixed $value Raw visible.
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

            if (in_array($value, ['1', 'true', 'yes', 'visible', 'public', 'active'], true)) {
                return 1;
            }

            if (in_array($value, ['0', 'false', 'no', 'hidden', 'private', 'draft'], true)) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Normalise metadata from array/object/json.
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
     * Encode JSON safely.
     *
     * @param mixed $data Data.
     * @return string
     */
    private function encode_json(mixed $data): string {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Build provenance hash.
     *
     * @param array<string, mixed> $item Item.
     * @return string
     */
    private function build_hash(array $item): string {
        return sha1(self::COMPONENT . ':' . self::PRESET . ':' . $item['shortname'] . ':' . $this->encode_json($item['metadata']));
    }
}