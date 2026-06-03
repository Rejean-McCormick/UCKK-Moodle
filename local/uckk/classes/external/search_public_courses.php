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
 * External function searching public UCKK courses.
 *
 * This external function is read-only. It returns visible Moodle courses
 * intended for the public UCKK course explorer.
 *
 * It does not create courses, enrol users, award recognitions, validate work,
 * mutate course records, bypass Moodle visibility, or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context;
use context_course;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_text;
use moodle_url;
use stdClass;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Search public UCKK courses.
 *
 * This class can be exposed in local/uckk/db/services.php as:
 *
 * local_uckk_search_public_courses
 *
 * @package local_uckk
 */
final class search_public_courses extends external_api {
    /** Default page size. */
    private const DEFAULT_PER_PAGE = 24;

    /** Maximum page size. */
    private const MAX_PER_PAGE = 60;

    /** Sort by Moodle pedagogical order. */
    private const SORT_PEDAGOGICAL = 'pedagogical';

    /** Sort by course title. */
    private const SORT_TITLE = 'title';

    /** Sort by public category label. */
    private const SORT_CATEGORY = 'category';

    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'q' => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            'category' => new external_value(PARAM_ALPHANUMEXT, 'Public category key', VALUE_DEFAULT, ''),
            'sort' => new external_value(PARAM_ALPHANUMEXT, 'Sort mode', VALUE_DEFAULT, self::SORT_PEDAGOGICAL),
            'page' => new external_value(PARAM_INT, 'Page number, starting at 1', VALUE_DEFAULT, 1),
            'perpage' => new external_value(PARAM_INT, 'Results per page', VALUE_DEFAULT, self::DEFAULT_PER_PAGE),
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the search.
     *
     * @param string $q Search query.
     * @param string $category Public category key.
     * @param string $sort Sort mode.
     * @param int $page Page number.
     * @param int $perpage Results per page.
     * @param int $contextid Context id.
     * @return array<string, mixed>
     */
    public static function execute(
        string $q = '',
        string $category = '',
        string $sort = self::SORT_PEDAGOGICAL,
        int $page = 1,
        int $perpage = self::DEFAULT_PER_PAGE,
        int $contextid = 0
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'q' => $q,
            'category' => $category,
            'sort' => $sort,
            'page' => $page,
            'perpage' => $perpage,
            'contextid' => $contextid,
        ]);

        $context = $params['contextid'] > 0
            ? context::instance_by_id($params['contextid'])
            : context_system::instance();

        self::validate_context($context);

        $query = trim((string)$params['q']);
        $categorykey = self::normalise_key((string)$params['category']);
        $sortmode = self::normalise_sort((string)$params['sort']);
        $page = max(1, (int)$params['page']);
        $perpage = min(self::MAX_PER_PAGE, max(1, (int)$params['perpage']));

        $records = self::get_public_course_records($query);
        $filters = self::build_category_filters($records, $categorykey);

        if ($categorykey !== '') {
            $records = array_values(array_filter($records, static function(stdClass $record) use ($categorykey): bool {
                return self::course_category_key($record) === $categorykey;
            }));
        }

        self::sort_records($records, $sortmode);

        $total = count($records);
        $totalpages = $total > 0 ? (int)ceil($total / $perpage) : 1;

        if ($page > $totalpages) {
            $page = $totalpages;
        }

        $offset = ($page - 1) * $perpage;
        $pagedrecords = array_slice($records, $offset, $perpage);

        $results = array_map(static function(stdClass $record): array {
            return self::course_record_to_result($record);
        }, $pagedrecords);

        return [
            'query' => $query,
            'category' => $categorykey,
            'sort' => $sortmode,
            'page' => $page,
            'perpage' => $perpage,
            'total' => $total,
            'totalpages' => $totalpages,
            'hasmore' => $page < $totalpages,
            'filters' => $filters,
            'sorts' => self::sort_options($sortmode),
            'results' => $results,
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $filterstructure = new external_single_structure([
            'key' => new external_value(PARAM_ALPHANUMEXT, 'Filter key'),
            'label' => new external_value(PARAM_TEXT, 'Filter label'),
            'count' => new external_value(PARAM_INT, 'Number of matching courses'),
            'active' => new external_value(PARAM_BOOL, 'Whether the filter is active'),
        ]);

        $sortstructure = new external_single_structure([
            'key' => new external_value(PARAM_ALPHANUMEXT, 'Sort key'),
            'label' => new external_value(PARAM_TEXT, 'Sort label'),
            'active' => new external_value(PARAM_BOOL, 'Whether the sort is active'),
        ]);

        $metadatastructure = new external_single_structure([
            'label' => new external_value(PARAM_TEXT, 'Metadata label'),
            'value' => new external_value(PARAM_TEXT, 'Metadata value'),
        ]);

        $resultstructure = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course id'),
            'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
            'title' => new external_value(PARAM_TEXT, 'Public course title'),
            'summary' => new external_value(PARAM_TEXT, 'Plain public summary'),
            'url' => new external_value(PARAM_URL, 'Course URL'),
            'categorykey' => new external_value(PARAM_ALPHANUMEXT, 'Public category key'),
            'categorylabel' => new external_value(PARAM_TEXT, 'Public category label'),
            'categoryname' => new external_value(PARAM_TEXT, 'Original Moodle category name'),
            'categoryidnumber' => new external_value(PARAM_TEXT, 'Original Moodle category idnumber'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Result type'),
            'metadata' => new external_multiple_structure($metadatastructure),
        ]);

        return new external_single_structure([
            'query' => new external_value(PARAM_TEXT, 'Search query'),
            'category' => new external_value(PARAM_ALPHANUMEXT, 'Active category key'),
            'sort' => new external_value(PARAM_ALPHANUMEXT, 'Active sort mode'),
            'page' => new external_value(PARAM_INT, 'Current page'),
            'perpage' => new external_value(PARAM_INT, 'Results per page'),
            'total' => new external_value(PARAM_INT, 'Total matching courses'),
            'totalpages' => new external_value(PARAM_INT, 'Total pages'),
            'hasmore' => new external_value(PARAM_BOOL, 'Whether more pages exist'),
            'filters' => new external_multiple_structure($filterstructure),
            'sorts' => new external_multiple_structure($sortstructure),
            'results' => new external_multiple_structure($resultstructure),
        ]);
    }

    /**
     * Fetch visible public UCKK course records.
     *
     * @param string $query Search query.
     * @return array<int, stdClass>
     */
    private static function get_public_course_records(string $query): array {
        global $DB;

        $conditions = [
            'c.id <> :siteid',
            'c.visible = 1',
            'cc.visible = 1',
            '(
                c.shortname LIKE :courseshortname
                OR c.idnumber LIKE :courseidnumber
                OR cc.idnumber LIKE :categoryidnumber
            )',
        ];

        $params = [
            'siteid' => defined('SITEID') ? SITEID : 1,
            'courseshortname' => 'UCKK-%',
            'courseidnumber' => 'UCKK-%',
            'categoryidnumber' => 'UCKK%',
        ];

        if ($query !== '') {
            $needle = '%' . $DB->sql_like_escape(core_text::strtolower($query)) . '%';

            $conditions[] = '('
                . $DB->sql_like($DB->sql_lower('c.shortname'), ':qshortname', false, false)
                . ' OR '
                . $DB->sql_like($DB->sql_lower('c.fullname'), ':qfullname', false, false)
                . ' OR '
                . $DB->sql_like($DB->sql_lower('c.summary'), ':qsummary', false, false)
                . ' OR '
                . $DB->sql_like($DB->sql_lower('cc.name'), ':qcategoryname', false, false)
                . ')';

            $params['qshortname'] = $needle;
            $params['qfullname'] = $needle;
            $params['qsummary'] = $needle;
            $params['qcategoryname'] = $needle;
        }

        $wheresql = implode("\n           AND ", $conditions);

        return array_values($DB->get_records_sql("
            SELECT
                c.id,
                c.shortname,
                c.fullname,
                c.idnumber,
                c.summary,
                c.summaryformat,
                c.visible,
                c.category,
                c.sortorder,
                cc.name AS categoryname,
                cc.idnumber AS categoryidnumber,
                cc.visible AS categoryvisible,
                cc.sortorder AS categorysortorder
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
             WHERE {$wheresql}
          ORDER BY cc.sortorder, c.sortorder, c.shortname
        ", $params));
    }

    /**
     * Build category filters from the current query result set.
     *
     * @param array<int, stdClass> $records Course records.
     * @param string $activekey Active category key.
     * @return array<int, array<string, mixed>>
     */
    private static function build_category_filters(array $records, string $activekey): array {
        $filters = [
            [
                'key' => '',
                'label' => 'Tous les cours',
                'count' => count($records),
                'active' => $activekey === '',
            ],
        ];

        $buckets = [];

        foreach ($records as $record) {
            $key = self::course_category_key($record);
            $label = self::public_category_label(
                trim((string)($record->categoryname ?? '')),
                trim((string)($record->categoryidnumber ?? ''))
            );

            if ($key === '') {
                continue;
            }

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'count' => 0,
                    'active' => $activekey === $key,
                    '_sortorder' => (int)($record->categorysortorder ?? 0),
                ];
            }

            $buckets[$key]['count']++;
        }

        uasort($buckets, static function(array $a, array $b): int {
            $sort = ((int)$a['_sortorder']) <=> ((int)$b['_sortorder']);

            if ($sort !== 0) {
                return $sort;
            }

            return strcasecmp((string)$a['label'], (string)$b['label']);
        });

        foreach ($buckets as $bucket) {
            unset($bucket['_sortorder']);
            $filters[] = $bucket;
        }

        return $filters;
    }

    /**
     * Convert one course record to public explorer result.
     *
     * @param stdClass $record Course record.
     * @return array<string, mixed>
     */
    private static function course_record_to_result(stdClass $record): array {
        $courseid = (int)$record->id;
        $shortname = trim((string)$record->shortname);
        $fullname = trim((string)$record->fullname);
        $categoryname = trim((string)($record->categoryname ?? ''));
        $categoryidnumber = trim((string)($record->categoryidnumber ?? ''));
        $categorylabel = self::public_category_label($categoryname, $categoryidnumber);

        $summary = self::plain_summary($record);

        if ($summary === '') {
            $summary = 'Cours public UCKK disponible en consultation dans le campus Moodle.';
        }

        return [
            'id' => $courseid,
            'shortname' => $shortname,
            'fullname' => $fullname,
            'title' => $fullname !== '' ? $fullname : $shortname,
            'summary' => $summary,
            'url' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            'categorykey' => self::course_category_key($record),
            'categorylabel' => $categorylabel,
            'categoryname' => $categoryname,
            'categoryidnumber' => $categoryidnumber,
            'type' => 'course',
            'metadata' => [
                [
                    'label' => 'Code',
                    'value' => $shortname,
                ],
                [
                    'label' => 'Catégorie',
                    'value' => $categorylabel,
                ],
            ],
        ];
    }

    /**
     * Sort records.
     *
     * @param array<int, stdClass> $records Records, mutated in place.
     * @param string $sortmode Sort mode.
     * @return void
     */
    private static function sort_records(array &$records, string $sortmode): void {
        if ($sortmode === self::SORT_PEDAGOGICAL) {
            return;
        }

        usort($records, static function(stdClass $a, stdClass $b) use ($sortmode): int {
            if ($sortmode === self::SORT_CATEGORY) {
                $category = strcasecmp(self::course_category_label($a), self::course_category_label($b));

                if ($category !== 0) {
                    return $category;
                }
            }

            return strcasecmp((string)$a->fullname, (string)$b->fullname);
        });
    }

    /**
     * Return sort options.
     *
     * @param string $active Active sort mode.
     * @return array<int, array<string, mixed>>
     */
    private static function sort_options(string $active): array {
        $options = [
            self::SORT_PEDAGOGICAL => 'Ordre pédagogique',
            self::SORT_TITLE => 'Titre A-Z',
            self::SORT_CATEGORY => 'Catégorie',
        ];

        $items = [];

        foreach ($options as $key => $label) {
            $items[] = [
                'key' => $key,
                'label' => $label,
                'active' => $active === $key,
            ];
        }

        return $items;
    }

    /**
     * Return the public category key for a course record.
     *
     * @param stdClass $record Course record.
     * @return string
     */
    private static function course_category_key(stdClass $record): string {
        $categoryname = trim((string)($record->categoryname ?? ''));
        $categoryidnumber = trim((string)($record->categoryidnumber ?? ''));

        $source = $categoryidnumber !== '' ? $categoryidnumber : $categoryname;

        $key = self::normalise_key($source);

        if ($key === '') {
            $key = 'category_' . (int)($record->category ?? 0);
        }

        return $key;
    }

    /**
     * Return the public category label for a course record.
     *
     * @param stdClass $record Course record.
     * @return string
     */
    private static function course_category_label(stdClass $record): string {
        return self::public_category_label(
            trim((string)($record->categoryname ?? '')),
            trim((string)($record->categoryidnumber ?? ''))
        );
    }

    /**
     * Return a public-facing course category label.
     *
     * Moodle category names may include internal operational markers such as
     * "obligatoire". The public courses explorer exposes the academic block
     * label, not the internal requirement status.
     *
     * @param string $categoryname Moodle category name.
     * @param string $categoryidnumber Moodle category idnumber.
     * @return string
     */
    private static function public_category_label(string $categoryname, string $categoryidnumber): string {
        $label = trim($categoryname !== '' ? $categoryname : $categoryidnumber);

        if ($label === '') {
            return '';
        }

        $label = preg_replace('/\s+obligatoire\b/iu', '', $label);
        $label = preg_replace('/\s{2,}/u', ' ', (string)$label);

        return trim((string)$label);
    }

    /**
     * Convert Moodle course summary to safe plain public text.
     *
     * @param stdClass $record Course record.
     * @return string
     */
    private static function plain_summary(stdClass $record): string {
        $summary = trim((string)($record->summary ?? ''));

        if ($summary === '') {
            return '';
        }

        $format = (int)($record->summaryformat ?? FORMAT_HTML);

        try {
            $context = context_course::instance((int)$record->id, IGNORE_MISSING);
            $html = format_text($summary, $format, [
                'context' => $context ?: context_system::instance(),
                'trusted' => false,
                'noclean' => false,
                'filter' => true,
            ]);
        } catch (Throwable $e) {
            $html = $summary;
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$html)) ?: '');

        if (core_text::strlen($text) > 420) {
            $text = core_text::substr($text, 0, 417) . '…';
        }

        return $text;
    }

    /**
     * Normalise key for filters and sorting.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_key(string $value): string {
        $value = trim(core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\bobligatoire\b/iu', '', $value);
        $value = preg_replace('/^uckk[\s\-_]*/iu', '', (string)$value);
        $value = preg_replace('/[^a-z0-9]+/u', '_', (string)$value);
        $value = trim((string)$value, '_');

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise sort mode.
     *
     * @param string $sort Sort mode.
     * @return string
     */
    private static function normalise_sort(string $sort): string {
        $sort = clean_param($sort, PARAM_ALPHANUMEXT);

        $allowed = [
            self::SORT_PEDAGOGICAL,
            self::SORT_TITLE,
            self::SORT_CATEGORY,
        ];

        if (!in_array($sort, $allowed, true)) {
            return self::SORT_PEDAGOGICAL;
        }

        return $sort;
    }
}