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

/**
 * External function returning UCKK programs.
 *
 * This external function is read-only. It returns the program registry owned
 * by local_uckk, including the tronc commun, internal baccalauréats, mineures,
 * seminars and laboratories configured in the UCKK campus.
 *
 * It does not create courses, enrol users, assign pathways, issue badges,
 * validate competencies, modify archives or perform integrity decisions.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context_coursecat;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Get UCKK programs.
 *
 * This class can be exposed in local/uckk/db/services.php as:
 *
 * local_uckk_get_programs
 */
final class get_programs extends external_api {
    /** Program table. */
    private const TABLE_PROGRAM = 'local_uckk_program';

    /** Active status. */
    private const STATUS_ACTIVE = 'active';

    /** Draft status. */
    private const STATUS_DRAFT = 'draft';

    /** Hidden status. */
    private const STATUS_HIDDEN = 'hidden';

    /** Archived status. */
    private const STATUS_ARCHIVED = 'archived';

    /** Deleted status. */
    private const STATUS_DELETED = 'deleted';

    /**
     * Define input parameters.
     *
     * Top-level optional parameters must use VALUE_DEFAULT in Moodle external
     * functions. VALUE_OPTIONAL is only safe inside nested structures.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programtype' => new external_value(
                PARAM_ALPHANUMEXT,
                'Optional UCKK program type filter: tronccommun, baccalaureat, mineure, lab, seminar, transversal.',
                VALUE_DEFAULT,
                ''
            ),
            'status' => new external_value(
                PARAM_ALPHANUMEXT,
                'Optional status filter: draft, active, hidden, archived, deleted.',
                VALUE_DEFAULT,
                self::STATUS_ACTIVE
            ),
            'categoryid' => new external_value(
                PARAM_INT,
                'Optional Moodle course category id.',
                VALUE_DEFAULT,
                0
            ),
            'includehidden' => new external_value(
                PARAM_BOOL,
                'Whether hidden programs may be returned. Requires elevated UCKK permissions.',
                VALUE_DEFAULT,
                false
            ),
            'includearchived' => new external_value(
                PARAM_BOOL,
                'Whether archived programs may be returned.',
                VALUE_DEFAULT,
                false
            ),
            'includedeleted' => new external_value(
                PARAM_BOOL,
                'Whether soft-deleted programs may be returned. Requires elevated UCKK permissions.',
                VALUE_DEFAULT,
                false
            ),
            'query' => new external_value(
                PARAM_TEXT,
                'Optional search query matched against shortname, fullname and description.',
                VALUE_DEFAULT,
                ''
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of programs to return. Use 0 for no limit.',
                VALUE_DEFAULT,
                0
            ),
            'offset' => new external_value(
                PARAM_INT,
                'Offset for pagination.',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param string $programtype Optional program type.
     * @param string $status Optional status.
     * @param int $categoryid Optional Moodle category id.
     * @param bool $includehidden Include hidden programs.
     * @param bool $includearchived Include archived programs.
     * @param bool $includedeleted Include deleted programs.
     * @param string $query Search query.
     * @param int $limit Limit.
     * @param int $offset Offset.
     * @return array<string, mixed>
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function execute(
        string $programtype = '',
        string $status = self::STATUS_ACTIVE,
        int $categoryid = 0,
        bool $includehidden = false,
        bool $includearchived = false,
        bool $includedeleted = false,
        string $query = '',
        int $limit = 0,
        int $offset = 0
    ): array {
        global $DB;

        [
            'programtype' => $programtype,
            'status' => $status,
            'categoryid' => $categoryid,
            'includehidden' => $includehidden,
            'includearchived' => $includearchived,
            'includedeleted' => $includedeleted,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
        ] = self::validate_parameters(self::execute_parameters(), [
            'programtype' => $programtype,
            'status' => $status,
            'categoryid' => $categoryid,
            'includehidden' => $includehidden,
            'includearchived' => $includearchived,
            'includedeleted' => $includedeleted,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if ($categoryid > 0) {
            $context = context_coursecat::instance($categoryid);
        } else {
            $context = context_system::instance();
        }

        self::validate_context($context);
        require_capability('local/uckk:viewcampus', $context);

        if (($includehidden || $includedeleted) && !self::can_view_restricted_programs($context)) {
            throw new moodle_exception('error_permissiondenied', 'local_uckk');
        }

        $programtype = self::normalise_optional_alphanumext($programtype);
        $status = self::normalise_optional_alphanumext($status);
        $query = trim($query);

        if ($limit < 0) {
            throw new invalid_parameter_exception('Limit cannot be negative.');
        }

        if ($offset < 0) {
            throw new invalid_parameter_exception('Offset cannot be negative.');
        }

        if ($programtype !== '' && !self::is_supported_program_type($programtype)) {
            throw new invalid_parameter_exception('Invalid UCKK program type.');
        }

        if ($status !== '' && !self::is_supported_status($status)) {
            throw new invalid_parameter_exception('Invalid UCKK program status.');
        }

        $params = [];
        $where = [];

        if ($programtype !== '') {
            $where[] = 'programtype = :programtype';
            $params['programtype'] = $programtype;
        }

        if ($categoryid > 0) {
            $where[] = 'categoryid = :categoryid';
            $params['categoryid'] = $categoryid;
        }

        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if (!$includehidden) {
            $where[] = 'status <> :hiddenstatus';
            $params['hiddenstatus'] = self::STATUS_HIDDEN;
        }

        if (!$includearchived) {
            $where[] = 'status <> :archivedstatus';
            $params['archivedstatus'] = self::STATUS_ARCHIVED;
        }

        if (!$includedeleted) {
            $where[] = 'status <> :deletedstatus';
            $params['deletedstatus'] = self::STATUS_DELETED;
        }

        if ($query !== '') {
            $like = $DB->sql_like('shortname', ':queryshort', false)
                . ' OR ' . $DB->sql_like('fullname', ':queryfull', false)
                . ' OR ' . $DB->sql_like('description', ':querydesc', false);

            $where[] = '(' . $like . ')';
            $params['queryshort'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['queryfull'] = '%' . $DB->sql_like_escape($query) . '%';
            $params['querydesc'] = '%' . $DB->sql_like_escape($query) . '%';
        }

        $wheresql = $where ? implode(' AND ', $where) : '1 = 1';

        $totalcount = $DB->count_records_select(self::TABLE_PROGRAM, $wheresql, $params);

        $records = $DB->get_records_select(
            self::TABLE_PROGRAM,
            $wheresql,
            $params,
            'sortorder ASC, fullname ASC, id ASC',
            '*',
            $offset,
            $limit > 0 ? $limit : 0
        );

        $programs = [];
        foreach ($records as $record) {
            $programs[] = self::export_program($record, $context);
        }

        return [
            'programs' => $programs,
            'totalcount' => (int)$totalcount,
            'returnedcount' => count($programs),
            'filters' => [
                'programtype' => $programtype,
                'status' => $status,
                'categoryid' => $categoryid,
                'includehidden' => $includehidden,
                'includearchived' => $includearchived,
                'includedeleted' => $includedeleted,
                'query' => $query,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'warnings' => [],
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'programs' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Program id.'),
                    'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Stable UCKK program shortname.'),
                    'fullname' => new external_value(PARAM_TEXT, 'Program full name.'),
                    'programtype' => new external_value(PARAM_ALPHANUMEXT, 'Program type.'),
                    'programtypelabel' => new external_value(PARAM_TEXT, 'Program type display label.'),
                    'categoryid' => new external_value(PARAM_INT, 'Linked Moodle category id, or 0.'),
                    'categoryname' => new external_value(PARAM_TEXT, 'Linked Moodle category display name, or empty string.'),
                    'description' => new external_value(PARAM_RAW, 'Program description.'),
                    'status' => new external_value(PARAM_ALPHANUMEXT, 'Program status.'),
                    'statuslabel' => new external_value(PARAM_TEXT, 'Program status display label.'),
                    'sortorder' => new external_value(PARAM_INT, 'Program sort order.'),
                    'metadata' => new external_value(PARAM_RAW, 'Program metadata JSON object.'),
                    'internalrecognitionnotice' => new external_value(PARAM_TEXT, 'Internal recognition notice.'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp.'),
                    'timemodified' => new external_value(PARAM_INT, 'Last modification timestamp.'),
                ]),
                'UCKK programs.'
            ),
            'totalcount' => new external_value(PARAM_INT, 'Total matching programs before pagination.'),
            'returnedcount' => new external_value(PARAM_INT, 'Number of programs returned.'),
            'filters' => new external_single_structure([
                'programtype' => new external_value(PARAM_ALPHANUMEXT, 'Applied program type filter.'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Applied status filter.'),
                'categoryid' => new external_value(PARAM_INT, 'Applied category id filter.'),
                'includehidden' => new external_value(PARAM_BOOL, 'Whether hidden programs were included.'),
                'includearchived' => new external_value(PARAM_BOOL, 'Whether archived programs were included.'),
                'includedeleted' => new external_value(PARAM_BOOL, 'Whether deleted programs were included.'),
                'query' => new external_value(PARAM_TEXT, 'Applied search query.'),
                'limit' => new external_value(PARAM_INT, 'Applied limit.'),
                'offset' => new external_value(PARAM_INT, 'Applied offset.'),
            ]),
            'warnings' => new external_multiple_structure(
                new external_single_structure([
                    'item' => new external_value(PARAM_TEXT, 'Warning item.', VALUE_DEFAULT, ''),
                    'itemid' => new external_value(PARAM_INT, 'Warning item id.', VALUE_DEFAULT, 0),
                    'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'Warning code.'),
                    'message' => new external_value(PARAM_TEXT, 'Warning message.'),
                ]),
                'Warnings.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Export a program record.
     *
     * @param stdClass $record Program record.
     * @param \context $context Rendering and filtering context.
     * @return array<string, mixed>
     */
    private static function export_program(stdClass $record, \context $context): array {
        global $DB;

        $categoryid = !empty($record->categoryid) ? (int)$record->categoryid : 0;
        $categoryname = '';

        if ($categoryid > 0) {
            $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id, name', IGNORE_MISSING);
            if ($category) {
                $categorycontext = context_coursecat::instance($categoryid);
                $categoryname = format_string($category->name, true, ['context' => $categorycontext]);
            }
        }

        $programtype = self::normalise_optional_alphanumext($record->programtype ?? '');
        $status = self::normalise_optional_alphanumext($record->status ?? self::STATUS_DRAFT);

        return [
            'id' => (int)$record->id,
            'shortname' => self::normalise_optional_alphanumext($record->shortname ?? ''),
            'fullname' => format_string((string)($record->fullname ?? ''), true, ['context' => $context]),
            'programtype' => $programtype,
            'programtypelabel' => self::get_program_type_label($programtype),
            'categoryid' => $categoryid,
            'categoryname' => $categoryname,
            'description' => format_text(
                (string)($record->description ?? ''),
                FORMAT_HTML,
                [
                    'context' => $context,
                    'overflowdiv' => true,
                    'noclean' => false,
                ]
            ),
            'status' => $status,
            'statuslabel' => self::get_status_label($status),
            'sortorder' => (int)($record->sortorder ?? 0),
            'metadata' => self::normalise_metadata($record->metadata ?? '{}'),
            'internalrecognitionnotice' => get_string('warning_internalrecognition', 'local_uckk'),
            'timecreated' => (int)($record->timecreated ?? 0),
            'timemodified' => (int)($record->timemodified ?? 0),
        ];
    }

    /**
     * Determine whether current user can view hidden or deleted programs.
     *
     * @param \context $context Context.
     * @return bool
     */
    private static function can_view_restricted_programs(\context $context): bool {
        return has_any_capability([
            'local/uckk:manageprograms',
            'local/uckk:viewreports',
        ], $context);
    }

    /**
     * Get supported program types.
     *
     * @return string[]
     */
    private static function get_supported_program_types(): array {
        return [
            'tronccommun',
            'baccalaureat',
            'mineure',
            'lab',
            'seminar',
            'transversal',
        ];
    }

    /**
     * Check if a program type is supported.
     *
     * @param string $programtype Program type.
     * @return bool
     */
    private static function is_supported_program_type(string $programtype): bool {
        return in_array($programtype, self::get_supported_program_types(), true);
    }

    /**
     * Get supported statuses.
     *
     * @return string[]
     */
    private static function get_supported_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Check if a status is supported.
     *
     * @param string $status Status.
     * @return bool
     */
    private static function is_supported_status(string $status): bool {
        return in_array($status, self::get_supported_statuses(), true);
    }

    /**
     * Normalise an optional PARAM_ALPHANUMEXT-like value.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function normalise_optional_alphanumext($value): string {
        $value = trim(\core_text::strtolower((string)$value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise metadata JSON.
     *
     * @param mixed $metadata Metadata field.
     * @return string
     */
    private static function normalise_metadata($metadata): string {
        if (is_array($metadata)) {
            return json_encode((object)$metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($metadata) || trim($metadata) === '') {
            return '{}';
        }

        $decoded = json_decode($metadata, true);
        if (!is_array($decoded)) {
            return '{}';
        }

        return json_encode((object)$decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get program type label.
     *
     * @param string $programtype Program type.
     * @return string
     */
    private static function get_program_type_label(string $programtype): string {
        $stringkey = 'programtype_' . $programtype;

        if ($programtype !== '' && get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        if ($programtype === '') {
            return '';
        }

        return ucfirst(str_replace('_', ' ', $programtype));
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private static function get_status_label(string $status): string {
        $stringkey = 'status_' . $status;

        if ($status !== '' && get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        if ($status === '') {
            return '';
        }

        return ucfirst(str_replace('_', ' ', $status));
    }
}