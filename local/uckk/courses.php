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
 * Public UCKK courses page.
 *
 * Thin public controller.
 *
 * Page setup, navigation, visual structure and rendering are owned by:
 *
 * - \local_uckk\local\public_pages
 * - \local_uckk\output\public_page
 * - local_uckk/public_page.mustache
 * - local_uckk/styles.css
 *
 * This controller reads visible Moodle courses for public display only.
 * It does not create courses, enrol users, award recognitions, validate work,
 * or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$slug = 'courses';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

// Explicit guard: setup_page() normally loads this stylesheet.
$PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));

$explorerstate = local_uckk_public_courses_request_state();

$definition = \local_uckk\local\public_pages::definition($slug);
$definition = local_uckk_public_courses_enrich_definition($definition, $explorerstate);

$PAGE->requires->js_call_amd('local_uckk/course_explorer', 'init', [$definition['course_explorer']['initialstate']]);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();

/**
 * Read the public explorer request state.
 *
 * @return array{q: string, category: string, sort: string}
 */
function local_uckk_public_courses_request_state(): array {
    $q = trim(optional_param('q', '', PARAM_TEXT));
    $category = trim(optional_param('category', '', PARAM_ALPHANUMEXT));
    $sort = trim(optional_param('sort', 'pedagogical', PARAM_ALPHANUMEXT));

    if (!in_array($sort, ['pedagogical', 'title', 'category'], true)) {
        $sort = 'pedagogical';
    }

    return [
        'q' => $q,
        'category' => $category,
        'sort' => $sort,
    ];
}

/**
 * Add live visible Moodle courses to the public page definition.
 *
 * @param array<string, mixed> $definition Base page definition.
 * @param array{q: string, category: string, sort: string} $state Public explorer state.
 * @return array<string, mixed>
 */
function local_uckk_public_courses_enrich_definition(array $definition, array $state): array {
    $allcards = local_uckk_public_courses_get_cards();
    $filters = local_uckk_public_courses_category_filters($allcards, $state['category']);

    if ($state['category'] !== '' && !local_uckk_public_courses_filter_exists($filters, $state['category'])) {
        $state['category'] = '';
        $filters = local_uckk_public_courses_category_filters($allcards, '');
    }

    $cards = local_uckk_public_courses_filter_cards($allcards, $state);
    $cards = local_uckk_public_courses_sort_cards($cards, $state['sort']);

    $definition['sections'] = [
        [
            'eyebrow' => 'Répertoire public',
            'title' => 'Explorer les cours',
            'body' => 'Les cours ci-dessous sont visibles publiquement et accessibles en consultation. Ils constituent les unités d’apprentissage du campus UCKK.',
            'type' => 'courses-intro',
        ],
    ];

    $definition['cards'] = [];
    $definition['cardsheading'] = 'Cours publics';

    $definition['has_course_explorer'] = true;
    $definition['course_explorer'] = local_uckk_public_courses_explorer_context($state, $filters, $cards, count($allcards));

    $definition['metadata'] = [
        [
            'label' => 'Cours affichés',
            'value' => count($cards) . ' / ' . count($allcards),
        ],
    ];

    $definition['cta'] = [
        'title' => 'Index Moodle des cours',
        'body' => 'L’index Moodle permet aussi de parcourir les catégories de cours.',
        'url' => '/course/index.php',
        'label' => 'Ouvrir l’index',
    ];

    if (empty($allcards)) {
        if (!isset($definition['notices']) || !is_array($definition['notices'])) {
            $definition['notices'] = [];
        }

        $definition['notices'][] = [
            'type' => 'warning',
            'title' => 'Aucun cours public',
            'body' => 'Aucun cours visible n’est actuellement disponible dans le campus UCKK.',
        ];
    }

    return $definition;
}

/**
 * Build the template context for the public course explorer.
 *
 * @param array{q: string, category: string, sort: string} $state Public explorer state.
 * @param array<int, array<string, mixed>> $filters Category filters.
 * @param array<int, array<string, mixed>> $cards Filtered cards.
 * @param int $total Total available courses before filtering.
 * @return array<string, mixed>
 */
function local_uckk_public_courses_explorer_context(array $state, array $filters, array $cards, int $total): array {
    $actionurl = (new moodle_url('/local/uckk/courses.php'))->out(false);
    $hasquery = $state['q'] !== '';
    $hascategory = $state['category'] !== '';
    $hassort = $state['sort'] !== 'pedagogical';
    $hasactivefilters = $hasquery || $hascategory || $hassort;

    $initialstate = [
        'rootId' => 'local-uckk-course-explorer',
        'service' => 'local_uckk_search_public_courses',
        'actionUrl' => $actionurl,
        'q' => $state['q'],
        'query' => $state['q'],
        'category' => $state['category'],
        'sort' => $state['sort'],
        'page' => 1,
        'perpage' => 12,
        'total' => $total,
        'visible' => count($cards),
    ];

    return [
        'id' => 'local-uckk-course-explorer',
        'actionurl' => $actionurl,
        'query' => $state['q'],
        'category' => $state['category'],
        'sort' => $state['sort'],
        'total' => $total,
        'visiblecount' => count($cards),
        'resultsummary' => local_uckk_public_courses_result_summary(count($cards), $total),
        'hasquery' => $hasquery,
        'hascategory' => $hascategory,
        'hassort' => $hassort,
        'hasactivefilters' => $hasactivefilters,
        'hasresults' => !empty($cards),
        'emptytitle' => 'Aucun cours trouvé',
        'emptybody' => 'Aucun cours public ne correspond aux filtres actuels.',
        'filters' => $filters,
        'sortoptions' => local_uckk_public_courses_sort_options($state['sort']),
        'results' => array_values($cards),
        'initialstate' => $initialstate,
        'initialstatejson' => json_encode($initialstate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
}

/**
 * Build public cards from visible Moodle courses.
 *
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_courses_get_cards(): array {
    global $DB;

    $records = $DB->get_records_sql("
        SELECT
            c.id,
            c.shortname,
            c.fullname,
            c.summary,
            c.summaryformat,
            c.visible,
            c.category,
            c.sortorder,
            c.idnumber,
            cc.name AS categoryname,
            cc.idnumber AS categoryidnumber,
            cc.visible AS categoryvisible,
            cc.sortorder AS categorysortorder
          FROM {course} c
          JOIN {course_categories} cc ON cc.id = c.category
         WHERE c.id <> :siteid
           AND c.visible = 1
           AND cc.visible = 1
           AND (
                c.shortname LIKE :courseshortname
                OR c.idnumber LIKE :courseidnumber
                OR cc.idnumber LIKE :categoryidnumber
           )
      ORDER BY cc.sortorder, c.sortorder, c.shortname
    ", [
        'siteid' => defined('SITEID') ? SITEID : 1,
        'courseshortname' => 'UCKK-%',
        'courseidnumber' => 'UCKK-%',
        'categoryidnumber' => 'UCKK%',
    ]);

    $cards = [];

    foreach ($records as $record) {
        $courseid = (int)$record->id;
        $shortname = trim((string)$record->shortname);
        $fullname = trim((string)$record->fullname);
        $categoryname = trim((string)($record->categoryname ?? ''));
        $categoryidnumber = trim((string)($record->categoryidnumber ?? ''));
        $categorylabel = local_uckk_public_courses_public_category_label($categoryname, $categoryidnumber);
        $categorykey = local_uckk_public_courses_slug($categorylabel !== '' ? $categorylabel : $categoryidnumber);

        $summary = local_uckk_public_courses_plain_summary($record);

        if ($summary === '') {
            $summary = 'Cours public UCKK disponible en consultation dans le campus Moodle.';
        }

        $title = $fullname !== '' ? $fullname : $shortname;

        $cards[] = [
            'id' => $courseid,
            'eyebrow' => $categorylabel,
            'title' => $title,
            'body' => $summary,
            'url' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            'type' => 'course',
            'categorykey' => $categorykey,
            'categorylabel' => $categorylabel,
            'shortname' => $shortname,
            'sorttitle' => core_text::strtolower($title),
            'sortcategory' => core_text::strtolower($categorylabel),
            'searchtext' => core_text::strtolower(trim($title . ' ' . $shortname . ' ' . $categorylabel . ' ' . $summary)),
            'metadata' => [
                [
                    'label' => 'Code',
                    'value' => $shortname,
                ],
            ],
        ];
    }

    return $cards;
}

/**
 * Filter course cards from the current explorer state.
 *
 * @param array<int, array<string, mixed>> $cards Course cards.
 * @param array{q: string, category: string, sort: string} $state Public explorer state.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_courses_filter_cards(array $cards, array $state): array {
    $query = core_text::strtolower(trim($state['q']));
    $category = trim($state['category']);

    return array_values(array_filter($cards, static function(array $card) use ($query, $category): bool {
        if ($category !== '' && (string)($card['categorykey'] ?? '') !== $category) {
            return false;
        }

        if ($query !== '') {
            $haystack = (string)($card['searchtext'] ?? '');

            if (core_text::strpos($haystack, $query) === false) {
                return false;
            }
        }

        return true;
    }));
}

/**
 * Sort course cards.
 *
 * @param array<int, array<string, mixed>> $cards Course cards.
 * @param string $sort Sort key.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_courses_sort_cards(array $cards, string $sort): array {
    if ($sort === 'pedagogical') {
        return array_values($cards);
    }

    usort($cards, static function(array $a, array $b) use ($sort): int {
        if ($sort === 'category') {
            $categorycompare = strcmp((string)($a['sortcategory'] ?? ''), (string)($b['sortcategory'] ?? ''));

            if ($categorycompare !== 0) {
                return $categorycompare;
            }
        }

        return strcmp((string)($a['sorttitle'] ?? ''), (string)($b['sorttitle'] ?? ''));
    });

    return array_values($cards);
}

/**
 * Build category filters from available course cards.
 *
 * @param array<int, array<string, mixed>> $cards Course cards.
 * @param string $activekey Active category key.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_courses_category_filters(array $cards, string $activekey): array {
    $counts = [];
    $labels = [];

    foreach ($cards as $card) {
        $key = (string)($card['categorykey'] ?? '');
        $label = (string)($card['categorylabel'] ?? '');

        if ($key === '' || $label === '') {
            continue;
        }

        $counts[$key] = ($counts[$key] ?? 0) + 1;
        $labels[$key] = $label;
    }

    natcasesort($labels);

    $filters = [
        [
            'key' => '',
            'label' => 'Tous les cours',
            'count' => count($cards),
            'active' => $activekey === '',
        ],
    ];

    foreach ($labels as $key => $label) {
        $filters[] = [
            'key' => $key,
            'label' => $label,
            'count' => $counts[$key] ?? 0,
            'active' => $activekey === $key,
        ];
    }

    return $filters;
}

/**
 * Check whether a category filter exists.
 *
 * @param array<int, array<string, mixed>> $filters Category filters.
 * @param string $key Filter key.
 * @return bool
 */
function local_uckk_public_courses_filter_exists(array $filters, string $key): bool {
    foreach ($filters as $filter) {
        if ((string)($filter['key'] ?? '') === $key) {
            return true;
        }
    }

    return false;
}

/**
 * Build sort options.
 *
 * @param string $active Active sort key.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_courses_sort_options(string $active): array {
    $options = [
        'pedagogical' => 'Ordre pédagogique',
        'title' => 'Titre A-Z',
        'category' => 'Catégorie',
    ];

    $sortoptions = [];

    foreach ($options as $key => $label) {
        $sortoptions[] = [
            'key' => $key,
            'label' => $label,
            'active' => $active === $key,
        ];
    }

    return $sortoptions;
}

/**
 * Return a public-facing course category label.
 *
 * Moodle category names may include internal operational markers such as
 * "obligatoire". The public courses page exposes the academic block label,
 * not the internal requirement status.
 *
 * @param string $categoryname Moodle category name.
 * @param string $categoryidnumber Moodle category idnumber.
 * @return string
 */
function local_uckk_public_courses_public_category_label(string $categoryname, string $categoryidnumber): string {
    $label = trim($categoryname !== '' ? $categoryname : $categoryidnumber);

    if ($label === '') {
        return '';
    }

    $label = preg_replace('/\s+obligatoire\b/iu', '', $label);
    $label = preg_replace('/\s{2,}/u', ' ', (string)$label);

    return trim((string)$label);
}

/**
 * Build a public result summary.
 *
 * @param int $visible Visible course count after filtering.
 * @param int $total Total course count before filtering.
 * @return string
 */
function local_uckk_public_courses_result_summary(int $visible, int $total): string {
    if ($total === 0) {
        return 'Aucun cours public disponible.';
    }

    if ($visible === $total) {
        return $total === 1 ? '1 cours public.' : $total . ' cours publics.';
    }

    return $visible . ' cours affichés sur ' . $total . '.';
}

/**
 * Create a stable ASCII-ish slug for public filters.
 *
 * @param string $value Raw value.
 * @return string
 */
function local_uckk_public_courses_slug(string $value): string {
    $value = core_text::strtolower(trim($value));
    $value = strtr($value, [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'œ' => 'oe', 'æ' => 'ae',
    ]);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
    $value = trim((string)$value, '-');

    return $value !== '' ? $value : 'category';
}

/**
 * Convert Moodle course summary to safe plain public text.
 *
 * @param stdClass $record Course record.
 * @return string
 */
function local_uckk_public_courses_plain_summary(stdClass $record): string {
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