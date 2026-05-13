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
 * Canon overview page for local_uckk.
 *
 * This page presents the canonical UCKK reference map inside Moodle.
 * It is intentionally conservative:
 *
 * - it does not modify the canon;
 * - it does not write to the database;
 * - it does not validate archive items;
 * - it does not decide integrity cases;
 * - it does not turn narrative figures into technical authority.
 *
 * Canon editing, import, versioning and provenance workflows should be handled
 * by dedicated local_uckk service classes and forms.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

global $CFG, $OUTPUT, $PAGE, $SITE;

require_login();

$context = context_system::instance();

$requiredcapability = 'local/uckk:managecanon';
$viewcapability = 'local/uckk:viewcampus';

if (has_capability($requiredcapability, $context)) {
    // Full canon management visibility.
} else {
    require_capability($viewcapability, $context);
}

$url = new moodle_url('/local/uckk/canon.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('canon', 'local_uckk'));
$PAGE->set_heading(format_string($SITE->fullname));

/**
 * Return the canonical UCKK relationship map.
 *
 * @return array<int, array<string, string>>
 */
function local_uckk_get_canon_relationships(): array {
    return [
        [
            'key' => 'koa',
            'label' => get_string('canon_koa_label', 'local_uckk'),
            'role' => get_string('canon_koa_role', 'local_uckk'),
            'summary' => get_string('canon_koa_summary', 'local_uckk'),
        ],
        [
            'key' => 'uckk',
            'label' => get_string('canon_uckk_label', 'local_uckk'),
            'role' => get_string('canon_uckk_role', 'local_uckk'),
            'summary' => get_string('canon_uckk_summary', 'local_uckk'),
        ],
        [
            'key' => 'digitalecosystem',
            'label' => get_string('canon_digitalecosystem_label', 'local_uckk'),
            'role' => get_string('canon_digitalecosystem_role', 'local_uckk'),
            'summary' => get_string('canon_digitalecosystem_summary', 'local_uckk'),
        ],
        [
            'key' => 'kingklown',
            'label' => get_string('canon_kingklown_label', 'local_uckk'),
            'role' => get_string('canon_kingklown_role', 'local_uckk'),
            'summary' => get_string('canon_kingklown_summary', 'local_uckk'),
        ],
        [
            'key' => 'inquisiteur',
            'label' => get_string('canon_inquisiteur_label', 'local_uckk'),
            'role' => get_string('canon_inquisiteur_role', 'local_uckk'),
            'summary' => get_string('canon_inquisiteur_summary', 'local_uckk'),
        ],
        [
            'key' => 'assemblees',
            'label' => get_string('canon_assemblees_label', 'local_uckk'),
            'role' => get_string('canon_assemblees_role', 'local_uckk'),
            'summary' => get_string('canon_assemblees_summary', 'local_uckk'),
        ],
        [
            'key' => 'archives',
            'label' => get_string('canon_archives_label', 'local_uckk'),
            'role' => get_string('canon_archives_role', 'local_uckk'),
            'summary' => get_string('canon_archives_summary', 'local_uckk'),
        ],
    ];
}

/**
 * Return the canonical ethical boundaries.
 *
 * @return array<int, array<string, string>>
 */
function local_uckk_get_canon_boundaries(): array {
    return [
        [
            'key' => 'fictionfact',
            'label' => get_string('canon_boundary_fictionfact_label', 'local_uckk'),
            'summary' => get_string('canon_boundary_fictionfact_summary', 'local_uckk'),
        ],
        [
            'key' => 'nomisuse',
            'label' => get_string('canon_boundary_nomisemise_label', 'local_uckk'),
            'summary' => get_string('canon_boundary_nomisemise_summary', 'local_uckk'),
        ],
        [
            'key' => 'nonauthority',
            'label' => get_string('canon_boundary_nonauthority_label', 'local_uckk'),
            'summary' => get_string('canon_boundary_nonauthority_summary', 'local_uckk'),
        ],
        [
            'key' => 'evidence',
            'label' => get_string('canon_boundary_evidence_label', 'local_uckk'),
            'summary' => get_string('canon_boundary_evidence_summary', 'local_uckk'),
        ],
        [
            'key' => 'contestability',
            'label' => get_string('canon_boundary_contestability_label', 'local_uckk'),
            'summary' => get_string('canon_boundary_contestability_summary', 'local_uckk'),
        ],
    ];
}

/**
 * Return the canonical document groups.
 *
 * @return array<int, array<string, mixed>>
 */
function local_uckk_get_canon_document_groups(): array {
    return [
        [
            'key' => 'index',
            'label' => get_string('canon_group_index', 'local_uckk'),
            'documents' => [
                [
                    'path' => 'UCKK_Canon/00_index.md',
                    'role' => get_string('canon_doc_index_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/01_glossaire.md',
                    'role' => get_string('canon_doc_glossary_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/02_architecture-generale-kOA-UCKK-digital-ecosystem.md',
                    'role' => get_string('canon_doc_architecture_role', 'local_uckk'),
                ],
            ],
        ],
        [
            'key' => 'koa',
            'label' => get_string('canon_group_koa', 'local_uckk'),
            'documents' => [
                [
                    'path' => 'UCKK_Canon/10_kOA-mouvement.md',
                    'role' => get_string('canon_doc_koa_movement_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/11_kOA-principes-limites-strategie.md',
                    'role' => get_string('canon_doc_koa_limits_role', 'local_uckk'),
                ],
            ],
        ],
        [
            'key' => 'institution',
            'label' => get_string('canon_group_institution', 'local_uckk'),
            'documents' => [
                [
                    'path' => 'UCKK_Canon/20_UCKK-document-fondateur.md',
                    'role' => get_string('canon_doc_foundation_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/21_UCKK-branding.md',
                    'role' => get_string('canon_doc_branding_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/22_UCKK-gouvernance-assemblees-inquisiteur.md',
                    'role' => get_string('canon_doc_governance_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/23_UCKK-defis-theatre-public.md',
                    'role' => get_string('canon_doc_challenges_role', 'local_uckk'),
                ],
            ],
        ],
        [
            'key' => 'academic',
            'label' => get_string('canon_group_academic', 'local_uckk'),
            'documents' => [
                [
                    'path' => 'UCKK_Canon/30_UCKK-catalogue-academique.md',
                    'role' => get_string('canon_doc_catalogue_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/31_UCKK-tronc-commun.md',
                    'role' => get_string('canon_doc_tronccommun_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/42_UCKK-liste-et-fiches-de-cours.md',
                    'role' => get_string('canon_doc_cours_role', 'local_uckk'),
                ],
            ],
        ],
        [
            'key' => 'ecosystem',
            'label' => get_string('canon_group_ecosystem', 'local_uckk'),
            'documents' => [
                [
                    'path' => 'UCKK_Canon/50_kOA-digital-ecosystem-document-maitre.md',
                    'role' => get_string('canon_doc_ecosystem_master_role', 'local_uckk'),
                ],
                [
                    'path' => 'UCKK_Canon/51_kOA-digital-ecosystem-modules.md',
                    'role' => get_string('canon_doc_ecosystem_modules_role', 'local_uckk'),
                ],
            ],
        ],
    ];
}

/**
 * Return a canonical formula list.
 *
 * @return array<int, array<string, string>>
 */
function local_uckk_get_canon_formulas(): array {
    return [
        [
            'key' => 'foundational',
            'label' => get_string('canon_formula_foundational_label', 'local_uckk'),
            'text' => get_string('canon_formula_foundational_text', 'local_uckk'),
        ],
        [
            'key' => 'relationship',
            'label' => get_string('canon_formula_relationship_label', 'local_uckk'),
            'text' => get_string('canon_formula_relationship_text', 'local_uckk'),
        ],
        [
            'key' => 'governance',
            'label' => get_string('canon_formula_governance_label', 'local_uckk'),
            'text' => get_string('canon_formula_governance_text', 'local_uckk'),
        ],
        [
            'key' => 'publictheatre',
            'label' => get_string('canon_formula_publictheatre_label', 'local_uckk'),
            'text' => get_string('canon_formula_publictheatre_text', 'local_uckk'),
        ],
        [
            'key' => 'method',
            'label' => get_string('canon_formula_method_label', 'local_uckk'),
            'text' => get_string('canon_formula_method_text', 'local_uckk'),
        ],
        [
            'key' => 'cycle',
            'label' => get_string('canon_formula_cycle_label', 'local_uckk'),
            'text' => get_string('canon_formula_cycle_text', 'local_uckk'),
        ],
    ];
}

/**
 * Render a simple card.
 *
 * @param string $title Card title.
 * @param string $body Card body HTML.
 * @param string $classes Extra CSS classes.
 * @return string
 */
function local_uckk_render_card(string $title, string $body, string $classes = ''): string {
    $output = html_writer::start_div('card mb-3 local-uckk-card ' . $classes);
    $output .= html_writer::start_div('card-body');
    $output .= html_writer::tag('h3', s($title), ['class' => 'h5 card-title']);
    $output .= $body;
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render the relationship map.
 *
 * @return string
 */
function local_uckk_render_relationships(): string {
    $items = local_uckk_get_canon_relationships();

    $output = html_writer::start_div('row local-uckk-canon-relationships');

    foreach ($items as $item) {
        $body = html_writer::tag('p', s($item['role']), ['class' => 'font-weight-bold mb-1']);
        $body .= html_writer::tag('p', s($item['summary']), ['class' => 'mb-0 text-muted']);

        $output .= html_writer::start_div('col-md-6 col-lg-4');
        $output .= local_uckk_render_card($item['label'], $body, 'local-uckk-canon-card-' . $item['key']);
        $output .= html_writer::end_div();
    }

    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render canonical formulas.
 *
 * @return string
 */
function local_uckk_render_formulas(): string {
    $formulas = local_uckk_get_canon_formulas();
    $output = html_writer::start_tag('dl', ['class' => 'local-uckk-canon-formulas']);

    foreach ($formulas as $formula) {
        $output .= html_writer::tag('dt', s($formula['label']));
        $output .= html_writer::tag('dd', s($formula['text']), ['class' => 'mb-3']);
    }

    $output .= html_writer::end_tag('dl');

    return $output;
}

/**
 * Render canonical boundaries.
 *
 * @return string
 */
function local_uckk_render_boundaries(): string {
    $boundaries = local_uckk_get_canon_boundaries();
    $items = [];

    foreach ($boundaries as $boundary) {
        $content = html_writer::tag('strong', s($boundary['label']), ['class' => 'd-block']);
        $content .= html_writer::span(s($boundary['summary']), 'text-muted');
        $items[] = html_writer::tag('li', $content, ['class' => 'mb-2']);
    }

    return html_writer::tag('ul', implode('', $items), ['class' => 'local-uckk-canon-boundaries']);
}

/**
 * Render canonical document groups.
 *
 * @return string
 */
function local_uckk_render_document_groups(): string {
    $groups = local_uckk_get_canon_document_groups();
    $output = '';

    foreach ($groups as $group) {
        $rows = '';

        foreach ($group['documents'] as $document) {
            $rows .= html_writer::start_tag('tr');
            $rows .= html_writer::tag('td', html_writer::tag('code', s($document['path'])));
            $rows .= html_writer::tag('td', s($document['role']));
            $rows .= html_writer::end_tag('tr');
        }

        $table = html_writer::start_tag('div', ['class' => 'table-responsive']);
        $table .= html_writer::start_tag('table', ['class' => 'table table-sm table-striped']);
        $table .= html_writer::start_tag('thead');
        $table .= html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('canon_table_path', 'local_uckk'))
            . html_writer::tag('th', get_string('canon_table_role', 'local_uckk'))
        );
        $table .= html_writer::end_tag('thead');
        $table .= html_writer::tag('tbody', $rows);
        $table .= html_writer::end_tag('table');
        $table .= html_writer::end_tag('div');

        $output .= local_uckk_render_card($group['label'], $table);
    }

    return $output;
}

/**
 * Render management links if the user has management capability.
 *
 * @param context_system $context System context.
 * @return string
 */
function local_uckk_render_management_links(context_system $context): string {
    if (!has_capability('local/uckk:managecanon', $context)) {
        return '';
    }

    $links = [];

    $links[] = html_writer::link(
        new moodle_url('/local/uckk/programs.php'),
        get_string('programs', 'local_uckk'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );

    $links[] = html_writer::link(
        new moodle_url('/local/uckk/pathways.php'),
        get_string('pathways', 'local_uckk'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );

    if (core_component::get_plugin_directory('tool', 'uckkseed') !== null) {
        $links[] = html_writer::link(
            new moodle_url('/admin/tool/uckkseed/index.php'),
            get_string('seedtool', 'local_uckk'),
            ['class' => 'btn btn-outline-secondary btn-sm']
        );
    }

    return html_writer::div(implode(' ', $links), 'mb-3 local-uckk-management-links');
}

echo $OUTPUT->header();

echo html_writer::start_div('local-uckk local-uckk-canon');

echo html_writer::tag(
    'h2',
    get_string('canon_title', 'local_uckk'),
    ['class' => 'mb-2']
);

echo html_writer::tag(
    'p',
    get_string('canon_intro', 'local_uckk'),
    ['class' => 'lead']
);

echo local_uckk_render_management_links($context);

echo html_writer::div(
    html_writer::tag('strong', get_string('canon_notice_title', 'local_uckk'), ['class' => 'd-block'])
    . html_writer::span(get_string('canon_notice_text', 'local_uckk')),
    'alert alert-light border mb-4',
    ['role' => 'note']
);

echo local_uckk_render_card(
    get_string('canon_relationships_title', 'local_uckk'),
    html_writer::tag('p', get_string('canon_relationships_intro', 'local_uckk'), ['class' => 'text-muted'])
    . local_uckk_render_relationships()
);

echo local_uckk_render_card(
    get_string('canon_formulas_title', 'local_uckk'),
    local_uckk_render_formulas()
);

echo local_uckk_render_card(
    get_string('canon_boundaries_title', 'local_uckk'),
    local_uckk_render_boundaries()
);

echo local_uckk_render_card(
    get_string('canon_documents_title', 'local_uckk'),
    html_writer::tag('p', get_string('canon_documents_intro', 'local_uckk'), ['class' => 'text-muted'])
    . local_uckk_render_document_groups()
);

echo html_writer::div(
    get_string('canon_footer_note', 'local_uckk'),
    'small text-muted mt-4'
);

echo html_writer::end_div();

echo $OUTPUT->footer();