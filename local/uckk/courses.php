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

$definition = \local_uckk\local\public_pages::definition($slug);
$definition = local_uckk_public_courses_enrich_definition($definition);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();

/**
 * Add live visible Moodle course cards to the public page definition.
 *
 * @param array<string, mixed> $definition Base page definition.
 * @return array<string, mixed>
 */
function local_uckk_public_courses_enrich_definition(array $definition): array {
    $cards = local_uckk_public_courses_get_cards();

    $definition['sections'] = [
        [
            'eyebrow' => 'Répertoire public',
            'title' => 'Cours disponibles',
            'body' => 'Les cours ci-dessous sont visibles publiquement et accessibles en consultation. Ils constituent les unités d’apprentissage du campus UCKK.',
            'cards' => $cards,
            'type' => 'courses',
        ],
    ];

    $definition['cards'] = [];
    $definition['cardsheading'] = 'Cours publics';

    $definition['metadata'] = [
        [
            'label' => 'Cours publics affichés',
            'value' => (string)count($cards),
        ],
    ];

    $definition['cta'] = [
        'title' => 'Index Moodle des cours',
        'body' => 'L’index Moodle permet aussi de parcourir les catégories de cours.',
        'url' => '/course/index.php',
        'label' => 'Ouvrir l’index',
    ];

    if (empty($cards)) {
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
            cc.name AS categoryname,
            cc.idnumber AS categoryidnumber,
            cc.visible AS categoryvisible
          FROM {course} c
          JOIN {course_categories} cc ON cc.id = c.category
         WHERE c.id <> 1
           AND c.visible = 1
           AND cc.visible = 1
           AND (
                c.shortname LIKE :courseshortname
                OR c.idnumber LIKE :courseidnumber
                OR cc.idnumber LIKE :categoryidnumber
           )
      ORDER BY cc.sortorder, c.sortorder, c.shortname
    ", [
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

        $summary = local_uckk_public_courses_plain_summary($record);

        if ($summary === '') {
            $summary = 'Cours public UCKK disponible en consultation dans le campus Moodle.';
        }

        $cards[] = [
            'eyebrow' => $categoryname !== '' ? $categoryname : $categoryidnumber,
            'title' => $fullname !== '' ? $fullname : $shortname,
            'body' => $summary,
            'url' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            'actionlabel' => 'Ouvrir le cours',
            'type' => 'course',
            'metadata' => [
                [
                    'label' => 'Code',
                    'value' => $shortname,
                ],
                [
                    'label' => 'Catégorie',
                    'value' => $categoryname !== '' ? $categoryname : $categoryidnumber,
                ],
            ],
        ];
    }

    return $cards;
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
