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

    /** Base classes for public course result cards. */
    private const COURSE_CARD_BASE_CLASSES = 'local-uckk-public-card local-uckk-public-card--course local-uckk-public-card--linked local-uckk-course-card local-uckk-course-card--voie';

    /** Canonical visual signatures for UCKK Voies keyed by course/category code. */
    private const VOIE_SIGNATURES = [
        'GJS' => [
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
        ],
        'EC' => [
            'voie_id' => 'voie_economie',
            'slug' => 'economie',
        ],
        'ECL' => [
            'voie_id' => 'voie_ecologie',
            'slug' => 'ecologie',
        ],
        'SP' => [
            'voie_id' => 'voie_sciences_politiques',
            'slug' => 'sciences-politiques',
        ],
        'LI' => [
            'voie_id' => 'voie_linguistique_architecture_du_sens',
            'slug' => 'linguistique-architecture-du-sens',
        ],
        'ME' => [
            'voie_id' => 'voie_metaphysique',
            'slug' => 'metaphysique',
        ],
        'IA' => [
            'voie_id' => 'voie_ia_gouvernable',
            'slug' => 'ia-gouvernable',
        ],
        'IS' => [
            'voie_id' => 'voie_intervention_sociale_systemes_humains',
            'slug' => 'intervention-sociale-systemes-humains',
        ],
        'AS' => [
            'voie_id' => 'voie_architecture_sociotechnique',
            'slug' => 'architecture-sociotechnique',
        ],
        'KOA' => [
            'voie_id' => 'voie_ecosysteme_digital_koa',
            'slug' => 'ecosysteme-digital-koa',
        ],
    ];

    /**
     * Define parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'q' => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            'category' => new external_value(PARAM_TEXT, 'Public category key', VALUE_DEFAULT, ''),
            'sort' => new external_value(PARAM_TEXT, 'Sort mode', VALUE_DEFAULT, self::SORT_PEDAGOGICAL),
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

        $context = context_system::instance();

        if ($params['contextid'] > 0) {
            $candidate = context::instance_by_id((int)$params['contextid'], IGNORE_MISSING);

            if ($candidate instanceof context) {
                $context = $candidate;
            }
        }

        // This endpoint powers a public page. Do not force login for guests.
        if (isloggedin() && !isguestuser()) {
            self::validate_context($context);
        }

        $query = self::safe_param_text((string)$params['q']);
        $categorykey = self::normalise_category_key((string)$params['category']);
        $sortmode = self::normalise_sort((string)$params['sort']);
        $page = max(1, (int)$params['page']);
        $perpage = min(self::MAX_PER_PAGE, max(1, (int)$params['perpage']));

        $records = self::get_public_course_records($query);
        $filters = self::build_category_filters($records, $categorykey);

        if ($categorykey !== '') {
            $records = array_values(array_filter($records, static function(stdClass $record) use ($categorykey): bool {
                return in_array($categorykey, self::course_category_keys($record), true);
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
            'category' => $categorykey === '' ? 'all' : $categorykey,
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
            'key' => new external_value(PARAM_TEXT, 'Filter key'),
            'label' => new external_value(PARAM_TEXT, 'Filter label'),
            'count' => new external_value(PARAM_INT, 'Number of matching courses'),
            'active' => new external_value(PARAM_BOOL, 'Whether the filter is active'),
        ]);

        $sortstructure = new external_single_structure([
            'key' => new external_value(PARAM_TEXT, 'Sort key'),
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
            'url' => new external_value(PARAM_TEXT, 'Course URL'),
            'categorykey' => new external_value(PARAM_TEXT, 'Public category key'),
            'categorylabel' => new external_value(PARAM_TEXT, 'Public category label'),
            'categoryname' => new external_value(PARAM_TEXT, 'Original Moodle category name'),
            'categoryidnumber' => new external_value(PARAM_TEXT, 'Original Moodle category idnumber'),
            'voieid' => new external_value(PARAM_TEXT, 'Canonical UCKK Voie id'),
            'voieslug' => new external_value(PARAM_TEXT, 'Canonical UCKK Voie visual slug'),
            'classes' => new external_value(PARAM_TEXT, 'CSS classes for public course card rendering'),
            'type' => new external_value(PARAM_TEXT, 'Result type'),
            'metadata' => new external_multiple_structure($metadatastructure),
        ]);

        return new external_single_structure([
            'query' => new external_value(PARAM_TEXT, 'Search query'),
            'category' => new external_value(PARAM_TEXT, 'Active category key'),
            'sort' => new external_value(PARAM_TEXT, 'Active sort mode'),
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
            $needle = '%' . $DB->sql_like_escape($query) . '%';

            $conditions[] = '('
                . $DB->sql_like('c.shortname', ':qshortname', false, false)
                . ' OR '
                . $DB->sql_like('c.fullname', ':qfullname', false, false)
                . ' OR '
                . $DB->sql_like('c.summary', ':qsummary', false, false)
                . ' OR '
                . $DB->sql_like('cc.name', ':qcategoryname', false, false)
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
                'key' => 'all',
                'label' => 'Toutes les voies',
                'count' => count($records),
                'active' => $activekey === '',
            ],
        ];

        $buckets = [];

        foreach ($records as $record) {
            $key = self::course_category_key($record);
            $keys = self::course_category_keys($record);
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
                    'active' => in_array($activekey, $keys, true),
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
        $shortname = self::safe_param_text((string)$record->shortname);
        $fullname = self::safe_param_text((string)$record->fullname);
        $categoryname = self::safe_param_text((string)($record->categoryname ?? ''));
        $categoryidnumber = self::safe_param_text((string)($record->categoryidnumber ?? ''));
        $categorylabel = self::public_category_label($categoryname, $categoryidnumber);
        $signature = self::course_voie_signature($record, $shortname, $fullname, $categoryname, $categoryidnumber);
        $classes = self::course_card_classes($signature);

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
            'voieid' => $signature['voie_id'],
            'voieslug' => $signature['slug'],
            'classes' => $classes,
            'type' => 'course',
            'metadata' => [
                [
                    'label' => 'Code',
                    'value' => $shortname,
                ],
                [
                    'label' => 'Voie',
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
            self::SORT_CATEGORY => 'Voie',
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
     * Return the primary public category key for a course record.
     *
     * @param stdClass $record Course record.
     * @return string
     */
    private static function course_category_key(stdClass $record): string {
        $keys = self::course_category_keys($record);

        if (!empty($keys)) {
            return $keys[0];
        }

        return 'category-' . (int)($record->category ?? 0);
    }

    /**
     * Return all accepted public category keys for a course record.
     *
     * The public page may send either a visible category-name slug
     * or an idnumber-derived slug such as "koa".
     *
     * @param stdClass $record Course record.
     * @return array<int, string>
     */
    private static function course_category_keys(stdClass $record): array {
        $categoryname = trim((string)($record->categoryname ?? ''));
        $categoryidnumber = trim((string)($record->categoryidnumber ?? ''));
        $publiclabel = self::public_category_label($categoryname, $categoryidnumber);

        $sources = [
            $publiclabel,
            $categoryname,
            $categoryidnumber,
        ];

        $keys = [];

        foreach ($sources as $source) {
            $key = self::normalise_key($source);

            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        if (empty($keys)) {
            $keys[] = 'category-' . (int)($record->category ?? 0);
        }

        return $keys;
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
     * Return the visual signature associated with a course record.
     *
     * The signature is derived from stable public identifiers already present
     * in Moodle course/category records. It does not require custom fields,
     * hidden enrolment data, grades, completion data or Faculty page data.
     *
     * @param stdClass $record Course record.
     * @param string $shortname Safe course shortname.
     * @param string $fullname Safe course fullname.
     * @param string $categoryname Safe category name.
     * @param string $categoryidnumber Safe category idnumber.
     * @return array{code: string, voie_id: string, slug: string}
     */
    private static function course_voie_signature(
        stdClass $record,
        string $shortname,
        string $fullname,
        string $categoryname,
        string $categoryidnumber
    ): array {
        $sources = [
            $categoryidnumber,
            (string)($record->idnumber ?? ''),
            $shortname,
            $fullname,
            $categoryname,
        ];

        $code = self::voie_code_from_identifiers($sources);

        if ($code === '' || !isset(self::VOIE_SIGNATURES[$code])) {
            return [
                'code' => '',
                'voie_id' => '',
                'slug' => '',
            ];
        }

        return [
            'code' => $code,
            'voie_id' => self::VOIE_SIGNATURES[$code]['voie_id'],
            'slug' => self::VOIE_SIGNATURES[$code]['slug'],
        ];
    }

    /**
     * Derive a Voie code from course/category identifiers.
     *
     * @param array<int, string> $sources Candidate source strings.
     * @return string
     */
    private static function voie_code_from_identifiers(array $sources): string {
        $codes = array_keys(self::VOIE_SIGNATURES);

        // Prefer longer codes first so ECL cannot be confused with EC.
        usort($codes, static function(string $left, string $right): int {
            return strlen($right) <=> strlen($left);
        });

        foreach ($sources as $source) {
            $value = strtoupper(self::safe_utf8($source));

            if ($value === '') {
                continue;
            }

            $value = preg_replace('/[^A-Z0-9_-]+/', ' ', $value) ?: '';
            $tokens = preg_split('/\s+/', trim($value)) ?: [];

            foreach ($tokens as $token) {
                $token = trim($token);

                if ($token === '') {
                    continue;
                }

                foreach ($codes as $code) {
                    if (preg_match('/^(?:UCKK[-_])?' . preg_quote($code, '/') . '(?:$|[-_0-9])/', $token)) {
                        return $code;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Build safe CSS classes for a public course card.
     *
     * @param array{code: string, voie_id: string, slug: string} $signature Visual signature.
     * @return string
     */
    private static function course_card_classes(array $signature): string {
        $classes = preg_split('/\s+/', self::COURSE_CARD_BASE_CLASSES) ?: [];
        $slug = self::normalise_key((string)($signature['slug'] ?? ''));

        if ($slug !== '') {
            $classes[] = 'local-uckk-public-card--voie-' . $slug;
            $classes[] = 'local-uckk-course-card--voie-' . $slug;
        } else {
            $classes[] = 'local-uckk-course-card--voie-unknown';
        }

        return implode(' ', array_values(array_unique(array_filter($classes, static function(string $class): bool {
            return $class !== '' && clean_param($class, PARAM_ALPHANUMEXT) === $class;
        }))));
    }

    /**
     * Return a public-facing course category label.
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

        return self::safe_param_text((string)$label);
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
        $text = self::safe_param_text($text);

        if (\core_text::strlen($text) > 420) {
            $text = \core_text::substr($text, 0, 417) . '…';
        }

        return self::safe_param_text($text);
    }

    /**
     * Make text safe for PARAM_TEXT external return validation.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function safe_param_text(string $value): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!preg_match('//u', $value)) {
            $cleaned = function_exists('iconv') ? @iconv('UTF-8', 'UTF-8//IGNORE', $value) : '';

            if (is_string($cleaned)) {
                $value = $cleaned;
            } else {
                $value = '';
            }
        }

        // Remove Unicode replacement characters.
        $value = str_replace("\xEF\xBF\xBD", '', $value);
        $value = str_replace('�', '', $value);

        // Remove control characters.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?: '';

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim((string)clean_param($value, PARAM_TEXT));
    }

    /**
     * Normalise category key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_category_key(string $value): string {
        $key = self::normalise_key($value);

        if ($key === 'all') {
            return '';
        }

        return $key;
    }

    /**
     * Normalise key for filters and sorting.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_key(string $value): string {
        $value = self::safe_utf8($value);

        if ($value === '') {
            return '';
        }

        // Stable UTF-8 transliteration for French labels.
        // Avoid iconv ASCII transliteration because it produced broken slugs
        // such as "ecosyst-eme", "m-etaphysique", "m-edias" and "th-e-atre".
        $value = strtr($value, [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'Æ' => 'AE', 'æ' => 'ae',
            'Ç' => 'C', 'ç' => 'c',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ñ' => 'N', 'ñ' => 'n',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Œ' => 'OE', 'œ' => 'oe',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ý' => 'Y', 'Ÿ' => 'Y',
            'ý' => 'y', 'ÿ' => 'y',
            '’' => "'", '‘' => "'", 'ʼ' => "'", '`' => "'", '´' => "'",
            '—' => '-', '–' => '-',
        ]);

        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\bobligatoire\b/i', '', $value);
        $value = preg_replace('/^uckk[\s\-_]*/i', '', (string)$value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', (string)$value);
        $value = trim((string)$value, '-');

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Make a string valid UTF-8 without applying PARAM_TEXT semantics.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function safe_utf8(string $value): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!preg_match('//u', $value)) {
            $cleaned = function_exists('iconv') ? @iconv('UTF-8', 'UTF-8//IGNORE', $value) : '';

            if (is_string($cleaned)) {
                $value = $cleaned;
            } else {
                return '';
            }
        }

        $value = str_replace("\xEF\xBF\xBD", '', $value);
        $value = str_replace('�', '', $value);

        return trim($value);
    }

    /**
     * Normalise sort mode.
     *
     * @param string $sort Sort mode.
     * @return string
     */
    private static function normalise_sort(string $sort): string {
        $sort = self::normalise_key($sort);

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