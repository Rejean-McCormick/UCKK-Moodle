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
 * UCKK programs catalogue page.
 *
 * This page displays the canonical UCKK program catalogue inside Moodle.
 * It is a presentation/controller file for the local_uckk plugin.
 *
 * It must not create programs, mutate pathways, enrol users, award badges,
 * validate competencies, or make accreditation claims. Creation and seeding
 * belong to tool_uckkseed and the local_uckk service layer.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/uckk/programs.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(local_uckk_programs_string('programs:title', 'Programmes UCKK'));
$PAGE->set_heading(local_uckk_programs_string('uckkfullname', 'Univers-Cité King Klown'));

$canviewcampus = has_capability('local/uckk:viewcampus', $context);
$canmanageprograms = has_capability('local/uckk:manageprograms', $context);

if (!$canviewcampus && !$canmanageprograms) {
    require_capability('local/uckk:viewcampus', $context);
}

$programtype = optional_param('type', '', PARAM_ALPHANUMEXT);
$status = optional_param('status', 'active', PARAM_ALPHANUMEXT);

$programs = local_uckk_programs_get_programs($programtype, $status);

echo $OUTPUT->header();

echo html_writer::start_div('local-uckk local-uckk-programs', [
    'data-region' => 'local-uckk-programs',
]);

echo html_writer::tag(
    'h2',
    local_uckk_programs_string('programs:heading', 'Programmes UCKK'),
    ['class' => 'local-uckk-page-title']
);

echo html_writer::div(
    local_uckk_programs_string(
        'programs:intro',
        'Les programmes UCKK forment des joueurs lucides capables de lire les systèmes, vérifier les faits, produire des preuves, participer aux assemblées et transformer les règles lorsque le jeu doit être réparé.'
    ),
    'local-uckk-page-intro'
);

echo html_writer::div(
    local_uckk_programs_string(
        'programs:internalrecognitionnotice',
        'Les programmes, baccalauréats, badges et reconnaissances UCKK sont des structures internes de formation expérimentale. Ils ne doivent pas être présentés comme des diplômes publics accrédités, sauf reconnaissance officielle future.'
    ),
    'alert alert-light border local-uckk-internal-recognition-notice',
    ['role' => 'note']
);

echo local_uckk_programs_render_filters($programtype, $status);

if (empty($programs)) {
    echo html_writer::div(
        local_uckk_programs_string('programs:none', 'Aucun programme UCKK ne correspond aux filtres sélectionnés.'),
        'alert alert-info',
        ['role' => 'status']
    );
} else {
    echo html_writer::start_div('local-uckk-program-grid');

    foreach ($programs as $program) {
        echo local_uckk_programs_render_card($program, $canmanageprograms);
    }

    echo html_writer::end_div();
}

echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Return UCKK programs filtered by type and status.
 *
 * The database is used when the local_uckk_program table exists and contains
 * data. Otherwise, the canonical internal catalogue is used as a complete
 * fallback so the page remains functional during initial installation.
 *
 * @param string $type Program type filter.
 * @param string $status Status filter.
 * @return array<int, stdClass>
 */
function local_uckk_programs_get_programs(string $type = '', string $status = 'active'): array {
    global $DB;

    $programs = [];

    if ($DB->get_manager()->table_exists('local_uckk_program')) {
        $conditions = [];
        $params = [];

        if ($type !== '') {
            $conditions[] = 'programtype = :programtype';
            $params['programtype'] = $type;
        }

        if ($status !== '') {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1 = 1';

        $records = $DB->get_records_select(
            'local_uckk_program',
            $where,
            $params,
            'sortorder ASC, fullname ASC'
        );

        foreach ($records as $record) {
            $programs[] = local_uckk_programs_normalise_program($record);
        }
    }

    if (empty($programs)) {
        $programs = local_uckk_programs_get_canonical_programs();

        if ($type !== '') {
            $programs = array_values(array_filter($programs, static function(stdClass $program) use ($type): bool {
                return $program->programtype === $type;
            }));
        }

        if ($status !== '') {
            $programs = array_values(array_filter($programs, static function(stdClass $program) use ($status): bool {
                return $program->status === $status;
            }));
        }
    }

    return $programs;
}

/**
 * Normalise a database record into a display program object.
 *
 * @param stdClass $record Database record.
 * @return stdClass
 */
function local_uckk_programs_normalise_program(stdClass $record): stdClass {
    $program = new stdClass();

    $program->id = (int)($record->id ?? 0);
    $program->shortname = clean_param($record->shortname ?? '', PARAM_ALPHANUMEXT);
    $program->fullname = format_string($record->fullname ?? $record->shortname ?? '');
    $program->programtype = clean_param($record->programtype ?? 'program', PARAM_ALPHANUMEXT);
    $program->categoryid = (int)($record->categoryid ?? 0);
    $program->description = format_text($record->description ?? '', FORMAT_HTML);
    $program->status = clean_param($record->status ?? 'active', PARAM_ALPHANUMEXT);
    $program->sortorder = (int)($record->sortorder ?? 0);
    $program->symbolictitle = format_string($record->symbolictitle ?? '');
    $program->coursecount = (int)($record->coursecount ?? 0);
    $program->badgekey = clean_param($record->badgekey ?? '', PARAM_ALPHANUMEXT);
    $program->competencyfocus = [];

    if (!empty($record->competencyfocus)) {
        $decoded = json_decode($record->competencyfocus, true);
        if (is_array($decoded)) {
            $program->competencyfocus = array_values(array_filter(array_map('clean_param', $decoded, array_fill(0, count($decoded), PARAM_ALPHANUMEXT))));
        }
    }

    return $program;
}

/**
 * Return the canonical UCKK program catalogue.
 *
 * @return array<int, stdClass>
 */
function local_uckk_programs_get_canonical_programs(): array {
    $programs = [
        [
            'shortname' => 'tronc_commun',
            'fullname' => 'Tronc commun obligatoire',
            'programtype' => 'tronc_commun',
            'symbolictitle' => 'Fondation du Joueur lucide',
            'description' => 'Le tronc commun forme les bases UCKK : lire le Grand Jeu social, cartographier les systèmes, distinguer faits et interprétations, utiliser l’IA comme outil non souverain, produire des preuves, participer aux assemblées et agir avec intégrité.',
            'coursecount' => 8,
            'badgekey' => 'joueur_lucide',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-002',
                'UCKK-COMP-003',
                'UCKK-COMP-004',
                'UCKK-COMP-005',
                'UCKK-COMP-006',
                'UCKK-COMP-010',
                'UCKK-COMP-014',
            ],
            'sortorder' => 10,
        ],
        [
            'shortname' => 'grand_jeu_social',
            'fullname' => 'Baccalauréat du Grand Jeu social',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Architecte du Grand Jeu',
            'description' => 'Ce programme étudie les règles visibles et invisibles du monde social : institutions, récits, plateformes, pouvoirs, contraintes, failles, opportunités, assemblées, défis et transformation responsable des règles.',
            'coursecount' => 0,
            'badgekey' => 'grand_jeu_social',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-002',
                'UCKK-COMP-007',
                'UCKK-COMP-011',
                'UCKK-COMP-013',
            ],
            'sortorder' => 20,
        ],
        [
            'shortname' => 'architecture_ecosysteme_digital_koa',
            'fullname' => 'Baccalauréat en Architecture de l’écosystème digital kOA',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Architecte du kOA Digital Ecosystem',
            'description' => 'Ce programme forme les personnes capables de comprendre, déployer, auditer et gouverner le kOA Digital Ecosystem : modules, données, workflows, votes, preuves, artefacts, logs, tâches et mémoire.',
            'coursecount' => 0,
            'badgekey' => 'koa_digital_ecosystem',
            'competencyfocus' => [
                'UCKK-COMP-002',
                'UCKK-COMP-005',
                'UCKK-COMP-008',
                'UCKK-COMP-009',
                'UCKK-COMP-014',
            ],
            'sortorder' => 30,
        ],
        [
            'shortname' => 'architecture_sociotechnique',
            'fullname' => 'Baccalauréat en Architecture sociotechnique',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Architecte sociotechnique',
            'description' => 'Ce programme étudie les systèmes qui combinent humains, technologies, institutions, données, règles, interfaces, workflows, responsabilités et effets réels.',
            'coursecount' => 0,
            'badgekey' => 'architecture_sociotechnique',
            'competencyfocus' => [
                'UCKK-COMP-002',
                'UCKK-COMP-005',
                'UCKK-COMP-011',
                'UCKK-COMP-012',
                'UCKK-COMP-013',
            ],
            'sortorder' => 40,
        ],
        [
            'shortname' => 'sciences_politiques',
            'fullname' => 'Baccalauréat en Sciences politiques',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Lecteur des institutions',
            'description' => 'Ce programme étudie le pouvoir, les institutions, les assemblées, les lois, les partis, les mouvements, les votes, la légitimité et les mécanismes de décision.',
            'coursecount' => 0,
            'badgekey' => 'sciences_politiques',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-006',
                'UCKK-COMP-008',
                'UCKK-COMP-011',
                'UCKK-COMP-013',
            ],
            'sortorder' => 50,
        ],
        [
            'shortname' => 'economie',
            'fullname' => 'Baccalauréat en Économie',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Lecteur des flux de valeur',
            'description' => 'Ce programme étudie l’argent comme règle du jeu : ressources, incitatifs, marchés, philanthropie, subventions, plateformes, travail, valeur, extraction et redistribution.',
            'coursecount' => 0,
            'badgekey' => 'economie',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-002',
                'UCKK-COMP-005',
                'UCKK-COMP-011',
            ],
            'sortorder' => 60,
        ],
        [
            'shortname' => 'ecologie',
            'fullname' => 'Baccalauréat en Écologie',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Gardien des systèmes vivants',
            'description' => 'Ce programme étudie les systèmes vivants, territoires, ressources, dépendances matérielles, crises climatiques et liens entre société, environnement, institutions et résilience.',
            'coursecount' => 0,
            'badgekey' => 'gardien_systemes_vivants',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-002',
                'UCKK-COMP-005',
                'UCKK-COMP-007',
                'UCKK-COMP-012',
            ],
            'sortorder' => 70,
        ],
        [
            'shortname' => 'metaphysique',
            'fullname' => 'Baccalauréat en Métaphysique',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Lecteur des structures invisibles',
            'description' => 'Ce programme étudie les structures invisibles : vérité, sens, conscience, liberté, pouvoir, destin, ordre, chaos, langage, croyance, récit et symboles, sans devenir un programme dogmatique.',
            'coursecount' => 0,
            'badgekey' => 'metaphysique',
            'competencyfocus' => [
                'UCKK-COMP-003',
                'UCKK-COMP-010',
                'UCKK-COMP-011',
                'UCKK-COMP-013',
            ],
            'sortorder' => 80,
        ],
        [
            'shortname' => 'intelligence_artificielle_gouvernable',
            'fullname' => 'Baccalauréat en Intelligence artificielle gouvernable',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Gardien de l’IA non souveraine',
            'description' => 'Ce programme étudie l’IA comme outil de lecture, création, cartographie, simulation et accélération, jamais comme autorité finale.',
            'coursecount' => 0,
            'badgekey' => 'ia_gouvernable',
            'competencyfocus' => [
                'UCKK-COMP-002',
                'UCKK-COMP-003',
                'UCKK-COMP-004',
                'UCKK-COMP-005',
                'UCKK-COMP-013',
            ],
            'sortorder' => 90,
        ],
        [
            'shortname' => 'linguistique_architecture_du_sens',
            'fullname' => 'Baccalauréat en Linguistique et architecture du sens',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Architecte du sens',
            'description' => 'Ce programme étudie les langues comme infrastructures du monde social : mots, concepts, traductions, catégories, récits, identités, dictionnaires et pouvoir symbolique.',
            'coursecount' => 0,
            'badgekey' => 'architecte_sens',
            'competencyfocus' => [
                'UCKK-COMP-003',
                'UCKK-COMP-005',
                'UCKK-COMP-008',
                'UCKK-COMP-011',
                'UCKK-COMP-013',
            ],
            'sortorder' => 100,
        ],
        [
            'shortname' => 'intervention_sociale',
            'fullname' => 'Baccalauréat en Intervention sociale et systèmes humains',
            'programtype' => 'baccalaureat',
            'symbolictitle' => 'Gardien de la dignité dans les systèmes',
            'description' => 'Ce programme étudie les humains dans les systèmes : vulnérabilité, exclusion, trauma, pauvreté, famille, communauté, institutions, aide, dignité, réparation et transformation des institutions d’aide.',
            'coursecount' => 0,
            'badgekey' => 'intervention_sociale',
            'competencyfocus' => [
                'UCKK-COMP-001',
                'UCKK-COMP-005',
                'UCKK-COMP-007',
                'UCKK-COMP-010',
                'UCKK-COMP-013',
            ],
            'sortorder' => 110,
        ],
        [
            'shortname' => 'medias_vivants_theatre_public',
            'fullname' => 'Mineure Médias vivants et théâtre public responsable',
            'programtype' => 'mineure',
            'symbolictitle' => 'Metteur en scène responsable',
            'description' => 'Cette mineure étudie la scène, le récit, la satire, les médias sociaux, la diffusion, les défis publics et les limites éthiques de la performance.',
            'coursecount' => 0,
            'badgekey' => 'medias_vivants',
            'competencyfocus' => [
                'UCKK-COMP-003',
                'UCKK-COMP-005',
                'UCKK-COMP-007',
                'UCKK-COMP-010',
                'UCKK-COMP-013',
            ],
            'sortorder' => 120,
        ],
        [
            'shortname' => 'seminaires_avances_laboratoires',
            'fullname' => 'Séminaires avancés et laboratoires',
            'programtype' => 'laboratoire',
            'symbolictitle' => 'Bâtisseur de Kristals',
            'description' => 'Cet espace regroupe les séminaires, laboratoires, prototypes, expérimentations avancées, intégrations transversales, archives de recherche et Kristals pédagogiques.',
            'coursecount' => 0,
            'badgekey' => 'batisseur_prototype',
            'competencyfocus' => [
                'UCKK-COMP-005',
                'UCKK-COMP-008',
                'UCKK-COMP-009',
                'UCKK-COMP-012',
                'UCKK-COMP-014',
            ],
            'sortorder' => 130,
        ],
    ];

    return array_map(static function(array $data): stdClass {
        $program = (object)$data;
        $program->id = 0;
        $program->categoryid = 0;
        $program->status = 'active';

        return $program;
    }, $programs);
}

/**
 * Render program filters.
 *
 * @param string $selectedtype Selected type.
 * @param string $selectedstatus Selected status.
 * @return string
 */
function local_uckk_programs_render_filters(string $selectedtype, string $selectedstatus): string {
    $types = [
        '' => local_uckk_programs_string('programs:filter:alltypes', 'Tous les types'),
        'tronc_commun' => local_uckk_programs_string('programtype:tronc_commun', 'Tronc commun'),
        'baccalaureat' => local_uckk_programs_string('programtype:baccalaureat', 'Baccalauréat interne'),
        'mineure' => local_uckk_programs_string('programtype:mineure', 'Mineure'),
        'laboratoire' => local_uckk_programs_string('programtype:laboratoire', 'Laboratoire'),
    ];

    $statuses = [
        '' => local_uckk_programs_string('programs:filter:allstatuses', 'Tous les statuts'),
        'active' => local_uckk_programs_string('status:active', 'Actif'),
        'hidden' => local_uckk_programs_string('status:hidden', 'Masqué'),
        'archived' => local_uckk_programs_string('status:archived', 'Archivé'),
    ];

    $output = html_writer::start_tag('form', [
        'method' => 'get',
        'action' => new moodle_url('/local/uckk/programs.php'),
        'class' => 'local-uckk-program-filters mb-3',
        'data-region' => 'local-uckk-program-filters',
    ]);

    $output .= html_writer::start_div('form-row align-items-end');

    $output .= html_writer::start_div('form-group col-md-5');
    $output .= html_writer::label(
        local_uckk_programs_string('programs:filter:type', 'Type de programme'),
        'local-uckk-program-type'
    );
    $output .= html_writer::select(
        $types,
        'type',
        $selectedtype,
        false,
        [
            'id' => 'local-uckk-program-type',
            'class' => 'custom-select',
        ]
    );
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('form-group col-md-5');
    $output .= html_writer::label(
        local_uckk_programs_string('programs:filter:status', 'Statut'),
        'local-uckk-program-status'
    );
    $output .= html_writer::select(
        $statuses,
        'status',
        $selectedstatus,
        false,
        [
            'id' => 'local-uckk-program-status',
            'class' => 'custom-select',
        ]
    );
    $output .= html_writer::end_div();

    $output .= html_writer::start_div('form-group col-md-2');
    $output .= html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-primary btn-block',
        'value' => local_uckk_programs_string('programs:filter:apply', 'Filtrer'),
    ]);
    $output .= html_writer::end_div();

    $output .= html_writer::end_div();
    $output .= html_writer::end_tag('form');

    return $output;
}

/**
 * Render a program card.
 *
 * @param stdClass $program Program object.
 * @param bool $canmanage Whether current user can manage programs.
 * @return string
 */
function local_uckk_programs_render_card(stdClass $program, bool $canmanage = false): string {
    $classes = [
        'card',
        'local-uckk-program-card',
        'local-uckk-program-type-' . clean_param($program->programtype, PARAM_ALPHANUMEXT),
        'local-uckk-program-status-' . clean_param($program->status, PARAM_ALPHANUMEXT),
    ];

    $output = html_writer::start_div(implode(' ', $classes), [
        'data-region' => 'local-uckk-program-card',
        'data-program-shortname' => $program->shortname,
        'data-program-type' => $program->programtype,
        'data-program-status' => $program->status,
    ]);

    $output .= html_writer::start_div('card-body');

    $output .= html_writer::div(
        local_uckk_programs_get_program_type_label($program->programtype),
        'small text-muted font-weight-bold mb-1 local-uckk-program-type-label'
    );

    $output .= html_writer::tag(
        'h3',
        s($program->fullname),
        ['class' => 'h5 card-title mb-1']
    );

    if (!empty($program->symbolictitle)) {
        $output .= html_writer::div(
            s($program->symbolictitle),
            'local-uckk-symbolic-title mb-2'
        );
    }

    $output .= html_writer::div(
        $program->description,
        'card-text local-uckk-program-description'
    );

    $output .= html_writer::start_div('local-uckk-program-meta mt-3');

    $output .= local_uckk_programs_render_badge(
        local_uckk_programs_get_status_label($program->status),
        'status'
    );

    if (!empty($program->badgekey)) {
        $output .= local_uckk_programs_render_badge(
            local_uckk_programs_string('badge:' . $program->badgekey, local_uckk_programs_humanise_key($program->badgekey)),
            'badge'
        );
    }

    if (!empty($program->coursecount)) {
        $output .= local_uckk_programs_render_badge(
            get_string('courses') . ': ' . (int)$program->coursecount,
            'courses'
        );
    }

    $output .= html_writer::end_div();

    if (!empty($program->competencyfocus)) {
        $output .= html_writer::start_div('local-uckk-program-competencies mt-3');
        $output .= html_writer::div(
            local_uckk_programs_string('programs:competencyfocus', 'Compétences visées'),
            'small text-muted font-weight-bold mb-1'
        );

        foreach ($program->competencyfocus as $competency) {
            $output .= local_uckk_programs_render_badge($competency, 'competency');
        }

        $output .= html_writer::end_div();
    }

    $actions = [];

    if ($program->categoryid > 0) {
        $actions[] = html_writer::link(
            new moodle_url('/course/index.php', ['categoryid' => $program->categoryid]),
            local_uckk_programs_string('programs:viewcourses', 'Voir les cours'),
            ['class' => 'btn btn-outline-primary btn-sm']
        );
    }

    if ($canmanage && $program->id > 0) {
        $actions[] = html_writer::link(
            new moodle_url('/local/uckk/programs.php', ['edit' => $program->id]),
            local_uckk_programs_string('programs:manage', 'Gérer'),
            ['class' => 'btn btn-outline-secondary btn-sm']
        );
    }

    if (!empty($actions)) {
        $output .= html_writer::div(
            implode(' ', $actions),
            'local-uckk-program-actions mt-3'
        );
    }

    $output .= html_writer::end_div();
    $output .= html_writer::end_div();

    return $output;
}

/**
 * Render a small UCKK badge.
 *
 * @param string $label Badge label.
 * @param string $type Badge type.
 * @return string
 */
function local_uckk_programs_render_badge(string $label, string $type): string {
    return html_writer::span(
        s($label),
        'badge badge-light border mr-1 mb-1 local-uckk-program-badge local-uckk-program-badge-' . clean_param($type, PARAM_ALPHANUMEXT)
    );
}

/**
 * Return a program type label.
 *
 * @param string $type Program type.
 * @return string
 */
function local_uckk_programs_get_program_type_label(string $type): string {
    $labels = [
        'tronc_commun' => local_uckk_programs_string('programtype:tronc_commun', 'Tronc commun'),
        'baccalaureat' => local_uckk_programs_string('programtype:baccalaureat', 'Baccalauréat interne'),
        'mineure' => local_uckk_programs_string('programtype:mineure', 'Mineure'),
        'laboratoire' => local_uckk_programs_string('programtype:laboratoire', 'Laboratoire'),
    ];

    return $labels[$type] ?? local_uckk_programs_humanise_key($type);
}

/**
 * Return a status label.
 *
 * @param string $status Status key.
 * @return string
 */
function local_uckk_programs_get_status_label(string $status): string {
    $labels = [
        'active' => local_uckk_programs_string('status:active', 'Actif'),
        'hidden' => local_uckk_programs_string('status:hidden', 'Masqué'),
        'archived' => local_uckk_programs_string('status:archived', 'Archivé'),
    ];

    return $labels[$status] ?? local_uckk_programs_humanise_key($status);
}

/**
 * Safe wrapper around get_string with fallback.
 *
 * This keeps the page usable while language files are being generated.
 *
 * @param string $identifier Language string identifier.
 * @param string $fallback Fallback text.
 * @return string
 */
function local_uckk_programs_string(string $identifier, string $fallback): string {
    if (get_string_manager()->string_exists($identifier, 'local_uckk')) {
        return get_string($identifier, 'local_uckk');
    }

    if (get_string_manager()->string_exists($identifier, 'theme_uckk')) {
        return get_string($identifier, 'theme_uckk');
    }

    return $fallback;
}

/**
 * Convert a machine key to a readable label.
 *
 * @param string $key Machine key.
 * @return string
 */
function local_uckk_programs_humanise_key(string $key): string {
    $key = str_replace(['_', '-'], ' ', $key);
    $key = trim($key);

    if ($key === '') {
        return '';
    }

    return core_text::strtotitle($key);
}