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
 * Public UCKK programs page.
 *
 * Thin public controller.
 *
 * Page setup, navigation, content definitions, cards, notices and visual
 * structure are owned by:
 *
 * - \local_uckk\local\public_pages
 * - \local_uckk\output\public_page
 * - local_uckk/public_page.mustache
 * - local_uckk/styles.css
 *
 * This controller reads active public program records for display only.
 * It must not create programs, mutate pathways, enrol users, award badges,
 * validate competencies, or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/xmldb/xmldb_object.php');

$slug = 'programs';
$context = context_system::instance();

\local_uckk\local\public_pages::setup_page($slug, $context);

// Explicit guard: setup_page() normally loads this stylesheet, but this keeps
// the programs controller safe if the helper changes or cache state is stale.
$PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));

$definition = \local_uckk\local\public_pages::definition($slug);
$definition = local_uckk_public_programs_enrich_definition($definition);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();

/**
 * Add live program cards from local_uckk_program to the public page definition.
 *
 * @param array<string, mixed> $definition Base page definition.
 * @return array<string, mixed>
 */
function local_uckk_public_programs_enrich_definition(array $definition): array {
    $cards = local_uckk_public_programs_get_cards('active');

    $definition['sections'] = [
        [
            'eyebrow' => 'Orientation canonique',
            'title' => 'Choisir une voie dans la cité d’apprentissage',
            'body' => 'Les Voies UCKK sont des structures internes de formation, de pratique et de reconnaissance. Elles relient les cours, les compétences, les défis, les preuves, les portfolios, les Assemblées et les passages de Niveau. Une voie n’est pas seulement une liste de cours : c’est un chemin qui aide à situer une personne dans un domaine d’action, à produire des traces vérifiables et à construire progressivement un dossier de passage.',
            'type' => 'intro',
        ],
        [
            'eyebrow' => 'Voies publiques',
            'title' => 'Tronc commun et voies actives',
            'body' => 'Chaque carte présente une voie ou un bloc de formation visible publiquement. Elle peut ouvrir vers les espaces de cours associés lorsque ceux-ci sont publiés. Les voies peuvent mener à des Niveaux et Parchemins UCKK internes ; ces reconnaissances ne constituent pas des diplômes publics accrédités.',
            'cards' => $cards,
            'type' => 'programs',
        ],
    ];

    // Program cards belong to the registry section above. Keeping them at page
    // level creates a second generic card section under the page content.
    $definition['cards'] = [];
    $definition['cardsheading'] = 'Repères publics';
    $definition['metadataheading'] = 'État du registre';

    $definition['metadata'] = [
        [
            'label' => 'Voies actives publiées',
            'value' => (string)count($cards),
        ],
    ];

    $definition['cta'] = [
        'title' => 'Passer des voies aux apprentissages',
        'body' => 'Ouvre les cours associés pour consulter les modules, activités, ressources, preuves attendues et repères de progression.',
        'url' => '/local/uckk/courses.php',
        'label' => 'Voir les cours associés',
    ];

    if (empty($cards)) {
        if (!isset($definition['notices']) || !is_array($definition['notices'])) {
            $definition['notices'] = [];
        }

        $definition['notices'][] = [
            'type' => 'warning',
            'title' => 'Aucune voie active publiée',
            'body' => 'Aucune voie ou bloc de formation n’est actuellement visible dans le registre public UCKK.',
        ];
    }

    return $definition;
}

/**
 * Build public cards from active UCKK program records.
 *
 * @param string $status Program status to display.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_public_programs_get_cards(string $status = 'active'): array {
    global $DB;

    $dbman = $DB->get_manager();
    $programtable = new xmldb_table('local_uckk_program');

    if (!$dbman->table_exists($programtable)) {
        return [];
    }

    $records = $DB->get_records_sql("
        SELECT
            p.id,
            p.shortname,
            p.fullname,
            p.programtype,
            p.description,
            p.descriptionformat,
            p.status,
            p.visibility,
            p.sortorder,
            p.categoryid,
            c.name AS categoryname,
            c.visible AS categoryvisible
          FROM {local_uckk_program} p
     LEFT JOIN {course_categories} c ON c.id = p.categoryid
         WHERE p.status = :status
           AND (
                p.visibility IS NULL
                OR p.visibility = ''
                OR p.visibility IN (:publicvisibility, :institutionvisibility)
           )
      ORDER BY p.sortorder, p.fullname
    ", [
        'status' => $status,
        'publicvisibility' => 'public',
        'institutionvisibility' => 'institution',
    ]);

    $cards = [];

    foreach ($records as $record) {
        $shortname = trim((string)($record->shortname ?? ''));
        $fullname = trim((string)($record->fullname ?? ''));
        $programtype = trim((string)($record->programtype ?? ''));
        $categoryvisible = (int)($record->categoryvisible ?? 0);
        $categoryid = (int)($record->categoryid ?? 0);

        $title = local_uckk_public_programs_public_title($fullname, $shortname);
        $typelabel = local_uckk_public_programs_type_label($programtype);

        $body = local_uckk_public_programs_plain_description((string)($record->description ?? ''));

        if ($body === '') {
            $body = local_uckk_public_programs_default_description($shortname, $programtype);
        }

        $url = '';
        $actionlabel = '';

        if ($categoryid > 0 && $categoryvisible === 1) {
            $url = (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false);
            $actionlabel = 'Voir les cours associés';
        }

        $cards[] = [
            'eyebrow' => $typelabel,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'actionlabel' => $actionlabel,
            'type' => $programtype,
        ];
    }

    return $cards;
}

/**
 * Build a shorter public title for program cards.
 *
 * @param string $fullname Full program name from registry.
 * @param string $shortname Program shortname.
 * @return string Public card title.
 */
 
 
 
function local_uckk_public_programs_public_title(string $fullname, string $shortname): string {
    $title = trim($fullname !== '' ? $fullname : $shortname);

    if ($title === '') {
        return '';
    }

    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = local_uckk_public_programs_normalise_visible_nomenclature($title);
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    $title = trim($title);

    // Remove old and current level suffixes from public cards.
    $title = preg_replace(
        '/\s*[—–-]\s*Niveau\s+de\s+(?:Magie\s+op[ée]rable|Puissance\s+op[ée]ratoire)\s*$/iu',
        '',
        $title
    ) ?? $title;

    // Special cases: old and current forms such as "Voie de Puissance opératoire en Architecture...".
    $title = preg_replace(
        '/^Voie\s+de\s+(?:Magie\s+op[ée]rable|Puissance\s+op[ée]ratoire)\s+en\s+/iu',
        '',
        $title
    ) ?? $title;

    // Remove legacy controlled public prefixes if still stored in DB.
    $title = preg_replace(
        '/^Baccalaur[ée]at\s+(?:UCKK\s*[—–-]\s*)?(?:du|de\s+la|de\s+l[’\']|des|en)?\s*/iu',
        '',
        $title
    ) ?? $title;

    $title = preg_replace(
        '/^Mineure\s+(?:UCKK\s*[—–-]\s*)?/iu',
        '',
        $title
    ) ?? $title;

    // Remove all public-card redundant "Voie ..." prefixes.
    $patterns = [
        '/^Voie\s+de\s+l[’\']\s*/iu',
        '/^Voie\s+d[’\']\s*/iu',
        '/^Voie\s+de\s+la\s+/iu',
        '/^Voie\s+du\s+/iu',
        '/^Voie\s+des\s+/iu',
        '/^Voie\s+de\s+/iu',
        '/^Voie\s+/iu',
    ];

    foreach ($patterns as $pattern) {
        $title = preg_replace($pattern, '', $title) ?? $title;
    }

    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

    return trim($title);
}

/**
 * Normalise legacy or controlled nomenclature for visible public text only.
 *
 * Technical DB values such as baccalaureat or mineure may remain in storage
 * until a controlled migration is performed. This helper prevents controlled
 * labels from leaking into the public programs page.
 *
 * @param string $text Visible text.
 * @return string Normalised visible text.
 */
function local_uckk_public_programs_normalise_visible_nomenclature(string $text): string {
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    $replacements = [
        '/\bBaccalaur[ée]at\s+UCKK\s*[—–-]\s*/iu' => 'Voie ',
        '/\bBaccalaur[ée]at\s+en\s+/iu' => 'Voie de ',
        '/\bBaccalaur[ée]at\s+du\s+/iu' => 'Voie du ',
        '/\bBaccalaur[ée]at\s+de\s+la\s+/iu' => 'Voie de la ',
        '/\bBaccalaur[ée]at\s+de\s+l[’\']/iu' => 'Voie de l’',
        '/\bBaccalaur[ée]at\s+des\s+/iu' => 'Voie des ',
        '/\bBaccalaur[ée]at\s+/iu' => 'Voie ',
        '/\bMineure\s+UCKK\s*[—–-]\s*/iu' => 'Voie ',
        '/\bMineure\s+/iu' => 'Voie ',
        '/\bPalier\b/u' => 'Niveau',
        '/\bPaliers\b/u' => 'Niveaux',
        '/\bMagie\s+op[ée]rable\b/iu' => 'Puissance opératoire',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text) ?? $text;
    }

    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

/**
 * Convert a program description to plain public text without shortening it.
 *
 * @param string $description Raw description.
 * @return string Plain full description.
 */
function local_uckk_public_programs_plain_description(string $description): string {
    $description = trim($description);

    if ($description === '') {
        return '';
    }

    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = local_uckk_public_programs_normalise_visible_nomenclature($description);
    $description = preg_replace('/\s+/u', ' ', $description) ?? $description;

    return trim($description);
}

/**
 * Fallback descriptions when the registry record has no description.
 *
 * @param string $shortname Program code.
 * @param string $programtype Program type.
 * @return string Description.
 */
function local_uckk_public_programs_default_description(string $shortname, string $programtype): string {
    $key = strtoupper(trim($shortname));

    $descriptions = [
        'TC' => 'Base pédagogique partagée par toutes les personnes en cheminement dans l’Univers-Cité King Klown, pour comprendre la cité d’apprentissage, documenter son parcours et produire des preuves.',
        'GJS' => 'Voie transversale principale de l’Univers-Cité King Klown. Elle forme des personnes capables de lire la société comme un système composé de règles, institutions, récits, pouvoirs, langages, technologies, ressources, symboles, preuves et mécanismes invisibles.',
        'KOA' => 'Voie formant des architectes sociotechniques capables de comprendre, documenter, opérer, adapter, auditer et déployer l’écosystème digital kOA comme infrastructure de connaissance, de délibération, de décision, d’exécution et de mémoire.',
        'AS' => 'Voie formant des personnes capables de comprendre, concevoir, déployer et auditer des systèmes combinant humains, technologies, institutions, données, règles, plateformes, workflows, décisions et boucles de mémoire.',
        'SP' => 'Voie formant des personnes capables de comprendre, analyser, critiquer et réformer les mécanismes du pouvoir dans le Grand Jeu social : institutions, médias, plateformes, réseaux, code, assemblées, votes, récits de légitimité et contre-pouvoirs.',
        'EC' => 'Voie formant des personnes capables de lire l’économie comme une architecture de règles, ressources, flux, incitatifs, intermédiaires, dépendances, captures et opportunités, afin de concevoir des modèles viables et des systèmes plus justes de circulation de valeur.',
        'ECO' => 'Voie consacrée aux systèmes vivants, aux milieux, aux interdépendances écologiques et aux responsabilités collectives.',
        'M' => 'Voie consacrée aux cadres de sens, aux hypothèses fondamentales, aux limites de l’explication, aux croyances, aux symboles et aux récits qui orientent l’action humaine.',
        'IA' => 'Voie formant des personnes capables d’utiliser, critiquer, auditer et gouverner l’intelligence artificielle comme outil d’assistance non souverain, en maintenant la responsabilité humaine, la preuve et l’intégrité.',
        'LI' => 'Voie consacrée au langage, aux signes, aux récits, à l’interprétation, à la clarté, à la manipulation et à la conception sémantique des systèmes humains et numériques.',
        'IS' => 'Voie consacrée à l’intervention sociale, aux systèmes humains, aux vulnérabilités, aux réseaux d’aide, aux conflits, aux milieux de vie et aux transformations situées.',
        'MV' => 'Voie consacrée aux médias vivants, au théâtre public, à la performance et à la responsabilité de l’expression publique.',
    ];

    if (isset($descriptions[$key])) {
        return $descriptions[$key];
    }

    if ($programtype === 'tronc_commun' || $programtype === 'tronccommun') {
        return 'Socle commun de préparation aux voies UCKK : comprendre, choisir, agir, documenter, corriger et transmettre.';
    }

    return 'Voie UCKK structurée autour de cours, compétences, preuves, archives et reconnaissance interne.';
}

/**
 * Human label for program type.
 *
 * @param string $programtype Program type.
 * @return string
 */
function local_uckk_public_programs_type_label(string $programtype): string {
    $labels = [
        'tronc_commun' => 'Tronc commun',
        'tronccommun' => 'Tronc commun',
        'voie_uckk' => 'Voie UCKK',
        'voie_secondaire' => 'Voie secondaire',
        'baccalaureat' => 'Voie UCKK — Niveau visé : Puissance opératoire',
        'baccalauréat' => 'Voie UCKK — Niveau visé : Puissance opératoire',
        'mineure' => 'Voie UCKK — Niveau visé : Initiation',
        'seminaire' => 'Séminaire / laboratoire',
        'laboratoire' => 'Laboratoire',
    ];

    if (isset($labels[$programtype])) {
        return $labels[$programtype];
    }

    $label = str_replace('_', ' ', $programtype);
    $label = trim($label);
    $label = local_uckk_public_programs_normalise_visible_nomenclature($label);

    return $label !== '' ? ucfirst($label) : '';
}