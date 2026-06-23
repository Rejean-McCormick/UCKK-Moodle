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
 * Local helper library for local_uckk.
 *
 * This file contains small, stable, procedural helper functions used by
 * UCKK-Moodle plugins and legacy Moodle callbacks.
 *
 * It must not become the whole business layer. Complex behaviour belongs in
 * autoloaded service classes under local/uckk/classes/.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK component name.
 */
define('LOCAL_UCKK_COMPONENT', 'local_uckk');

/**
 * Canonical plugin release line.
 */
define('LOCAL_UCKK_RELEASE', '1.0.0');

/**
 * Return the canonical UCKK component registry.
 *
 * @return array<string, array<string, string>>
 */
function local_uckk_get_component_registry(): array {
    return [
        'theme' => [
            'component' => 'theme_uckk',
            'path' => 'theme/uckk',
            'name' => 'UCKK theme',
        ],
        'format' => [
            'component' => 'format_uckk',
            'path' => 'course/format/uckk',
            'name' => 'UCKK course format',
        ],
        'local' => [
            'component' => 'local_uckk',
            'path' => 'local/uckk',
            'name' => 'UCKK core registry',
        ],
        'dashboard' => [
            'component' => 'block_uckk_dashboard',
            'path' => 'blocks/uckk_dashboard',
            'name' => 'UCKK dashboard block',
        ],
        'challenge' => [
            'component' => 'mod_uckkchallenge',
            'path' => 'mod/uckkchallenge',
            'name' => 'UCKK challenge activity',
        ],
        'assembly' => [
            'component' => 'mod_uckkassembly',
            'path' => 'mod/uckkassembly',
            'name' => 'UCKK assembly activity',
        ],
        'archive' => [
            'component' => 'mod_uckkarchive',
            'path' => 'mod/uckkarchive',
            'name' => 'UCKK archive activity',
        ],
        'seed' => [
            'component' => 'tool_uckkseed',
            'path' => 'admin/tool/uckkseed',
            'name' => 'UCKK seed tool',
        ],
        'integrity' => [
            'component' => 'tool_uckkintegrity',
            'path' => 'admin/tool/uckkintegrity',
            'name' => 'UCKK integrity tool',
        ],
        'report' => [
            'component' => 'report_uckk',
            'path' => 'report/uckk',
            'name' => 'UCKK institutional report',
        ],
        'ai' => [
            'component' => 'aiprovider_uckk',
            'path' => 'ai/provider/uckk',
            'name' => 'UCKK AI provider',
        ],
    ];
}

/**
 * Return canonical dependencies between UCKK components.
 *
 * @return array<string, string[]>
 */
function local_uckk_get_component_dependencies(): array {
    return [
        'theme_uckk' => [
            'local_uckk',
        ],
        'format_uckk' => [
            'local_uckk',
            'mod_uckkarchive',
        ],
        'block_uckk_dashboard' => [
            'local_uckk',
            'mod_uckkchallenge',
            'mod_uckkassembly',
            'mod_uckkarchive',
            'tool_uckkintegrity',
        ],
        'mod_uckkchallenge' => [
            'local_uckk',
            'mod_uckkarchive',
            'tool_uckkintegrity',
        ],
        'mod_uckkassembly' => [
            'local_uckk',
            'mod_uckkarchive',
            'tool_uckkintegrity',
        ],
        'mod_uckkarchive' => [
            'local_uckk',
        ],
        'tool_uckkseed' => [
            'local_uckk',
            'theme_uckk',
            'format_uckk',
            'block_uckk_dashboard',
            'mod_uckkchallenge',
            'mod_uckkassembly',
            'mod_uckkarchive',
            'tool_uckkintegrity',
            'report_uckk',
        ],
        'tool_uckkintegrity' => [
            'local_uckk',
            'mod_uckkarchive',
            'mod_uckkchallenge',
            'mod_uckkassembly',
        ],
        'report_uckk' => [
            'local_uckk',
            'mod_uckkchallenge',
            'mod_uckkassembly',
            'mod_uckkarchive',
            'tool_uckkintegrity',
        ],
        'aiprovider_uckk' => [
            'local_uckk',
        ],
    ];
}

/**
 * Determine whether a Moodle component is installed.
 *
 * @param string $component Component name, for example local_uckk.
 * @return bool
 */
function local_uckk_component_installed(string $component): bool {
    [$type, $name] = core_component::normalize_component($component);

    if ($type === 'core' || $name === null) {
        return false;
    }

    return core_component::get_plugin_directory($type, $name) !== null;
}

/**
 * Return installed state for all UCKK components.
 *
 * @return array<string, array<string, mixed>>
 */
function local_uckk_get_component_statuses(): array {
    $statuses = [];

    foreach (local_uckk_get_component_registry() as $key => $component) {
        $statuses[$key] = [
            'key' => $key,
            'component' => $component['component'],
            'path' => $component['path'],
            'name' => $component['name'],
            'installed' => local_uckk_component_installed($component['component']),
        ];
    }

    return $statuses;
}

/**
 * Return missing dependencies for a component.
 *
 * @param string $component Component name.
 * @return string[]
 */
function local_uckk_get_missing_dependencies(string $component): array {
    $dependencies = local_uckk_get_component_dependencies();
    $missing = [];

    foreach ($dependencies[$component] ?? [] as $dependency) {
        if (!local_uckk_component_installed($dependency)) {
            $missing[] = $dependency;
        }
    }

    return $missing;
}

/**
 * Return canonical status keys.
 *
 * @return string[]
 */
function local_uckk_get_statuses(): array {
    return [
        'draft',
        'active',
        'pending',
        'pending_review',
        'validated',
        'rejected',
        'correction_required',
        'contested',
        'invalidated',
        'closed',
        'archived',
        'cancelled',
    ];
}

/**
 * Return canonical validation state keys.
 *
 * @return string[]
 */
function local_uckk_get_validation_states(): array {
    return [
        'unverified',
        'human_reviewed',
        'verified',
        'contested',
        'invalidated',
        'archived',
    ];
}

/**
 * Return canonical visibility keys.
 *
 * @return string[]
 */
function local_uckk_get_visibilities(): array {
    return [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'institution',
        'public',
        'restricted_integrity',
    ];
}

/**
 * Return canonical provenance source keys.
 *
 * @return string[]
 */
function local_uckk_get_provenance_sources(): array {
    return [
        'human',
        'ai_assisted',
        'imported',
        'system',
        'archive',
        'assembly',
        'challenge',
        'integrity',
    ];
}

/**
 * Return canonical UCKK object types.
 *
 * @return string[]
 */
function local_uckk_get_object_types(): array {
    return [
        'program',
        'pathway',
        'course',
        'challenge',
        'assembly',
        'archive_item',
        'kristal',
        'proof',
        'decision',
        'integrity_case',
        'reflection',
        'portfolio_item',
        'badge',
        'competency',
    ];
}

/**
 * Return canonical technical UCKK role shortnames.
 *
 * These are Moodle role shortnames, not symbolic titles.
 *
 * @return array<string, string>
 */
function local_uckk_get_role_shortnames(): array {
    return [
        'manager' => 'uckkmanager',
        'mentor' => 'uckkmentor',
        'player' => 'uckkplayer',
        'archivist' => 'uckkarchivist',
        'inquisitor' => 'uckkinquisitor',
        'observer' => 'uckkobserver',
        'publicguest' => 'uckkpublicguest',
    ];
}

/**
 * Return symbolic UCKK role keys.
 *
 * These must not be treated as automatic Moodle technical roles.
 *
 * @return string[]
 */
function local_uckk_get_symbolic_roles(): array {
    return [
        'joueur',
        'joueur_lucide',
        'batisseur',
        'archiviste',
        'inquisiteur',
        'cartographe',
        'architecte_sens',
        'architecte_opportunites',
        'gardien_systemes_vivants',
    ];
}

/**
 * Return canonical UCKK program definitions.
 *
 * @return array<string, array<string, string>>
 */
function local_uckk_get_program_registry(): array {
    // NOTE: the internal `type` values below are technical compatibility keys.
    // They must not be displayed as public diploma labels. Public labels are
    // carried by `fullname` and must follow the UCKK Voie/Niveau nomenclature.
    return [
        'tronc_commun' => [
            'shortname' => 'tronc_commun',
            'idnumber' => 'UCKK-PROG-TC',
            'fullname' => 'Tronc commun obligatoire',
            'type' => 'tronc_commun',
        ],
        'grand_jeu_social' => [
            'shortname' => 'grand_jeu_social',
            'idnumber' => 'UCKK-PROG-GJS',
            'fullname' => 'Voie du Grand Jeu social',
            'type' => 'baccalaureat',
        ],
        'architecture_ecosysteme_digital_koa' => [
            'shortname' => 'architecture_ecosysteme_digital_koa',
            'idnumber' => 'UCKK-PROG-KOA',
            'fullname' => 'Voie de l’Architecture de l’écosystème digital kOA',
            'type' => 'baccalaureat',
        ],
        'architecture_sociotechnique' => [
            'shortname' => 'architecture_sociotechnique',
            'idnumber' => 'UCKK-PROG-AS',
            'fullname' => 'Voie de l’Architecture sociotechnique',
            'type' => 'baccalaureat',
        ],
        'sciences_politiques' => [
            'shortname' => 'sciences_politiques',
            'idnumber' => 'UCKK-PROG-SP',
            'fullname' => 'Voie des Sciences politiques',
            'type' => 'baccalaureat',
        ],
        'economie' => [
            'shortname' => 'economie',
            'idnumber' => 'UCKK-PROG-EC',
            'fullname' => 'Voie de l’Économie',
            'type' => 'baccalaureat',
        ],
        'ecologie' => [
            'shortname' => 'ecologie',
            'idnumber' => 'UCKK-PROG-ECO',
            'fullname' => 'Voie de l’Écologie',
            'type' => 'baccalaureat',
        ],
        'metaphysique' => [
            'shortname' => 'metaphysique',
            'idnumber' => 'UCKK-PROG-META',
            'fullname' => 'Voie de la Métaphysique',
            'type' => 'baccalaureat',
        ],
        'intelligence_artificielle_gouvernable' => [
            'shortname' => 'intelligence_artificielle_gouvernable',
            'idnumber' => 'UCKK-PROG-IA',
            'fullname' => 'Voie de la Production augmentée par l’IA',
            'type' => 'baccalaureat',
        ],
        'linguistique_architecture_du_sens' => [
            'shortname' => 'linguistique_architecture_du_sens',
            'idnumber' => 'UCKK-PROG-LING',
            'fullname' => 'Voie de la Linguistique et de l’architecture du sens',
            'type' => 'baccalaureat',
        ],
        'intervention_sociale' => [
            'shortname' => 'intervention_sociale',
            'idnumber' => 'UCKK-PROG-IS',
            'fullname' => 'Voie de l’Intervention sociale et des systèmes humains',
            'type' => 'baccalaureat',
        ],
        'medias_vivants_theatre_public' => [
            'shortname' => 'medias_vivants_theatre_public',
            'idnumber' => 'UCKK-PROG-MV',
            'fullname' => 'Voie des Médias vivants et du théâtre public',
            'type' => 'mineure',
        ],
        'seminaires_avances_laboratoires' => [
            'shortname' => 'seminaires_avances_laboratoires',
            'idnumber' => 'UCKK-PROG-LABS',
            'fullname' => 'Laboratoires avancés et chantiers',
            'type' => 'laboratoire',
        ],
    ];
}

/**
 * Return canonical tronc commun courses.
 *
 * @return array<string, array<string, string>>
 */
function local_uckk_get_tronc_commun_courses(): array {
    return [
        'UCKK-TC101' => [
            'shortname' => 'UCKK-TC101',
            'idnumber' => 'UCKK-TC101',
            'fullname' => 'UCKK-TC101 — Cartographie des idées avec l’IA',
        ],
        'UCKK-TC102' => [
            'shortname' => 'UCKK-TC102',
            'idnumber' => 'UCKK-TC102',
            'fullname' => 'UCKK-TC102 — Intelligence collective, expertise située et décision légitime',
        ],
        'UCKK-TC103' => [
            'shortname' => 'UCKK-TC103',
            'idnumber' => 'UCKK-TC103',
            'fullname' => 'UCKK-TC103 — Agitation institutionnelle et mesure de l’utilité réelle',
        ],
        'UCKK-TC104' => [
            'shortname' => 'UCKK-TC104',
            'idnumber' => 'UCKK-TC104',
            'fullname' => 'UCKK-TC104 — Société des flux : argent, information et pouvoir',
        ],
        'UCKK-TC105' => [
            'shortname' => 'UCKK-TC105',
            'idnumber' => 'UCKK-TC105',
            'fullname' => 'UCKK-TC105 — Fiction fondatrice, vérité morale et récits symboliques',
        ],
        'UCKK-TC106' => [
            'shortname' => 'UCKK-TC106',
            'idnumber' => 'UCKK-TC106',
            'fullname' => 'UCKK-TC106 — Mobilisation multi-corridor et coopération pratique',
        ],
        'UCKK-TC107' => [
            'shortname' => 'UCKK-TC107',
            'idnumber' => 'UCKK-TC107',
            'fullname' => 'UCKK-TC107 — Introduction à kOA : connaissance, décision, action, mémoire',
        ],
        'UCKK-TC108' => [
            'shortname' => 'UCKK-TC108',
            'idnumber' => 'UCKK-TC108',
            'fullname' => 'UCKK-TC108 — Éthique, intégrité et Inquisiteur méthodologique',
        ],
    ];
}

/**
 * Return canonical UCKK course sections.
 *
 * This mirrors format_uckk section semantics without depending on that plugin.
 *
 * @return array<int, array<string, mixed>>
 */
function local_uckk_get_course_sections(): array {
    return [
        0 => [
            'key' => 'orientation',
            'title' => 'Orientation',
            'evidencebearing' => false,
            'deliberative' => false,
            'archival' => false,
        ],
        1 => [
            'key' => 'concepts',
            'title' => 'Concepts',
            'evidencebearing' => false,
            'deliberative' => false,
            'archival' => false,
        ],
        2 => [
            'key' => 'canon',
            'title' => 'Matière canonique',
            'evidencebearing' => false,
            'deliberative' => false,
            'archival' => false,
        ],
        3 => [
            'key' => 'atelier',
            'title' => 'Atelier',
            'evidencebearing' => true,
            'deliberative' => false,
            'archival' => false,
        ],
        4 => [
            'key' => 'preuves',
            'title' => 'Preuves',
            'evidencebearing' => true,
            'deliberative' => false,
            'archival' => false,
        ],
        5 => [
            'key' => 'deliberation',
            'title' => 'Délibération',
            'evidencebearing' => true,
            'deliberative' => true,
            'archival' => false,
        ],
        6 => [
            'key' => 'livrable',
            'title' => 'Livrable',
            'evidencebearing' => true,
            'deliberative' => false,
            'archival' => false,
        ],
        7 => [
            'key' => 'evaluation',
            'title' => 'Évaluation',
            'evidencebearing' => true,
            'deliberative' => false,
            'archival' => false,
        ],
        8 => [
            'key' => 'archive',
            'title' => 'Archive',
            'evidencebearing' => true,
            'deliberative' => false,
            'archival' => true,
        ],
    ];
}

/**
 * Return canonical UCKK competencies.
 *
 * @return array<string, string>
 */
function local_uckk_get_competency_registry(): array {
    return [
        'UCKK-COMP-001' => 'Lire le Grand Jeu social',
        'UCKK-COMP-002' => 'Cartographier un système',
        'UCKK-COMP-003' => 'Distinguer fait, hypothèse, interprétation, récit et décision',
        'UCKK-COMP-004' => 'Utiliser l’IA comme outil non souverain',
        'UCKK-COMP-005' => 'Produire une preuve vérifiable',
        'UCKK-COMP-006' => 'Participer à une assemblée structurée',
        'UCKK-COMP-007' => 'Concevoir une mobilisation responsable',
        'UCKK-COMP-008' => 'Documenter une décision',
        'UCKK-COMP-009' => 'Archiver un apprentissage',
        'UCKK-COMP-010' => 'Appliquer l’éthique UCKK',
        'UCKK-COMP-011' => 'Détecter l’autorité cachée',
        'UCKK-COMP-012' => 'Construire un artefact utile',
        'UCKK-COMP-013' => 'Assurer la contestabilité',
        'UCKK-COMP-014' => 'Relier connaissance, décision, action et mémoire',
    ];
}

/**
 * Return canonical UCKK badge keys and names.
 *
 * @return array<string, string>
 */
function local_uckk_get_badge_registry(): array {
    return [
        'joueur_initie' => 'Joueur initié',
        'joueur_lucide' => 'Joueur lucide',
        'cartographe_systemes' => 'Cartographe de systèmes',
        'gardien_preuve' => 'Gardien de la preuve',
        'participant_assemblee' => 'Participant d’Assemblée',
        'batisseur_prototype' => 'Bâtisseur de prototype',
        'archiviste_decision' => 'Archiviste de décision',
        'defi_king_klown' => 'Défi King Klown validé',
        'inquisition_methodologique' => 'Inquisition méthodologique réussie',
        'architecte_sens' => 'Architecte du sens',
        'architecte_opportunites' => 'Architecte d’opportunités',
        'gardien_systemes_vivants' => 'Gardien des systèmes vivants',
        'ia_gouvernable' => 'Production IA',
        'grand_jeu_social' => 'Grand Jeu social',
        'koa_digital_ecosystem' => 'kOA Digital Ecosystem',
    ];
}

/**
 * Normalise a UCKK key.
 *
 * @param string $value Raw value.
 * @return string
 */
function local_uckk_normalise_key(string $value): string {
    $value = trim(\core_text::strtolower($value));
    $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
    $value = preg_replace('/[^a-z0-9_]/', '', $value);
    $value = preg_replace('/_+/', '_', $value);

    return trim((string)$value, '_');
}

/**
 * Normalise a UCKK status value.
 *
 * @param string|null $status Raw status.
 * @param string $default Default status.
 * @return string
 */
function local_uckk_normalise_status(?string $status, string $default = 'draft'): string {
    $status = local_uckk_normalise_key((string)$status);

    if (in_array($status, local_uckk_get_statuses(), true)) {
        return $status;
    }

    return in_array($default, local_uckk_get_statuses(), true) ? $default : 'draft';
}

/**
 * Normalise a UCKK visibility value.
 *
 * @param string|null $visibility Raw visibility.
 * @param string $default Default visibility.
 * @return string
 */
function local_uckk_normalise_visibility(?string $visibility, string $default = 'private'): string {
    $visibility = local_uckk_normalise_key((string)$visibility);

    if (in_array($visibility, local_uckk_get_visibilities(), true)) {
        return $visibility;
    }

    return in_array($default, local_uckk_get_visibilities(), true) ? $default : 'private';
}

/**
 * Determine whether a value is a known symbolic role.
 *
 * @param string $role Symbolic role key.
 * @return bool
 */
function local_uckk_is_symbolic_role(string $role): bool {
    return in_array(local_uckk_normalise_key($role), local_uckk_get_symbolic_roles(), true);
}

/**
 * Determine whether a value is a known status.
 *
 * @param string $status Status key.
 * @return bool
 */
function local_uckk_is_status(string $status): bool {
    return in_array(local_uckk_normalise_key($status), local_uckk_get_statuses(), true);
}

/**
 * Determine whether a value is a known visibility.
 *
 * @param string $visibility Visibility key.
 * @return bool
 */
function local_uckk_is_visibility(string $visibility): bool {
    return in_array(local_uckk_normalise_key($visibility), local_uckk_get_visibilities(), true);
}

/**
 * Determine whether a course appears to belong to UCKK.
 *
 * @param stdClass $course Moodle course record.
 * @return bool
 */
function local_uckk_is_uckk_course(stdClass $course): bool {
    $shortname = isset($course->shortname) ? (string)$course->shortname : '';
    $idnumber = isset($course->idnumber) ? (string)$course->idnumber : '';

    return preg_match('/^UCKK-/i', $shortname) === 1 || preg_match('/^UCKK-/i', $idnumber) === 1;
}

/**
 * Determine whether a course appears to be a UCKK tronc commun course.
 *
 * @param stdClass $course Moodle course record.
 * @return bool
 */
function local_uckk_is_tronc_commun_course(stdClass $course): bool {
    $shortname = isset($course->shortname) ? (string)$course->shortname : '';
    $idnumber = isset($course->idnumber) ? (string)$course->idnumber : '';

    return preg_match('/^UCKK-TC/i', $shortname) === 1 || preg_match('/^UCKK-TC/i', $idnumber) === 1;
}

/**
 * Get a Moodle context from a flexible context descriptor.
 *
 * @param string $contexttype Context type: system, coursecat, course, module, user.
 * @param int|null $instanceid Instance id where required.
 * @return context
 */
function local_uckk_get_context(string $contexttype = 'system', ?int $instanceid = null): context {
    $contexttype = local_uckk_normalise_key($contexttype);

    switch ($contexttype) {
        case 'coursecat':
        case 'course_category':
        case 'category':
            if ($instanceid === null) {
                throw new coding_exception('Course category context requires an instance id.');
            }
            return context_coursecat::instance($instanceid);

        case 'course':
            if ($instanceid === null) {
                throw new coding_exception('Course context requires an instance id.');
            }
            return context_course::instance($instanceid);

        case 'module':
        case 'cm':
            if ($instanceid === null) {
                throw new coding_exception('Module context requires an instance id.');
            }
            return context_module::instance($instanceid);

        case 'user':
            if ($instanceid === null) {
                throw new coding_exception('User context requires an instance id.');
            }
            return context_user::instance($instanceid);

        case 'system':
        default:
            return context_system::instance();
    }
}

/**
 * Check a UCKK capability in a flexible context.
 *
 * @param string $capability Capability name.
 * @param context|null $context Context. Defaults to system context.
 * @param int|null $userid Optional user id.
 * @param bool $doanything Whether doanything should be considered.
 * @return bool
 */
function local_uckk_has_capability(
    string $capability,
    ?context $context = null,
    ?int $userid = null,
    bool $doanything = true
): bool {
    $context = $context ?? context_system::instance();

    return has_capability($capability, $context, $userid, $doanything);
}

/**
 * Require a UCKK capability in a flexible context.
 *
 * @param string $capability Capability name.
 * @param context|null $context Context. Defaults to system context.
 * @return void
 */
function local_uckk_require_capability(string $capability, ?context $context = null): void {
    require_capability($capability, $context ?? context_system::instance());
}

/**
 * Return a language string when it exists, otherwise a fallback.
 *
 * @param string $identifier String identifier.
 * @param string $component Component name.
 * @param string|null $fallback Fallback.
 * @param mixed $a Optional string data.
 * @return string
 */
function local_uckk_get_string_or_fallback(
    string $identifier,
    string $component = LOCAL_UCKK_COMPONENT,
    ?string $fallback = null,
    $a = null
): string {
    if (get_string_manager()->string_exists($identifier, $component)) {
        return get_string($identifier, $component, $a);
    }

    return $fallback ?? $identifier;
}

/**
 * Return display label for a status.
 *
 * @param string $status Status key.
 * @return string
 */
function local_uckk_get_status_label(string $status): string {
    $status = local_uckk_normalise_status($status);
    $identifier = 'status_' . str_replace('_', '', $status);

    return local_uckk_get_string_or_fallback(
        $identifier,
        LOCAL_UCKK_COMPONENT,
        ucfirst(str_replace('_', ' ', $status))
    );
}

/**
 * Return display label for a visibility key.
 *
 * @param string $visibility Visibility key.
 * @return string
 */
function local_uckk_get_visibility_label(string $visibility): string {
    $visibility = local_uckk_normalise_visibility($visibility);
    $identifier = 'visibility_' . str_replace('_', '', $visibility);

    return local_uckk_get_string_or_fallback(
        $identifier,
        LOCAL_UCKK_COMPONENT,
        ucfirst(str_replace('_', ' ', $visibility))
    );
}

/**
 * Return display label for a program key.
 *
 * @param string $programkey Program key.
 * @return string
 */
function local_uckk_get_program_label(string $programkey): string {
    $programkey = local_uckk_normalise_key($programkey);
    $programs = local_uckk_get_program_registry();

    if (isset($programs[$programkey])) {
        return $programs[$programkey]['fullname'];
    }

    return ucfirst(str_replace('_', ' ', $programkey));
}

/**
 * Decode JSON safely.
 *
 * @param string|null $json JSON value.
 * @param array<mixed> $default Default value.
 * @return array<mixed>
 */
function local_uckk_json_decode_array(?string $json, array $default = []): array {
    if ($json === null || trim($json) === '') {
        return $default;
    }

    $decoded = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return $default;
    }

    return $decoded;
}

/**
 * Encode data as JSON for storage.
 *
 * @param mixed $data Data to encode.
 * @return string
 */
function local_uckk_json_encode($data): string {
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        throw new coding_exception('Unable to encode UCKK data as JSON: ' . json_last_error_msg());
    }

    return $encoded;
}

/**
 * Build a provenance hash for an object or evidence record.
 *
 * The hash is not a security signature. It is a stable traceability checksum.
 *
 * @param string $component Component name.
 * @param string $itemtype Object type.
 * @param int|string $itemid Object id.
 * @param string $source Source value.
 * @param int|null $timecreated Creation time.
 * @return string
 */
function local_uckk_build_provenance_hash(
    string $component,
    string $itemtype,
    $itemid,
    string $source,
    ?int $timecreated = null
): string {
    $payload = [
        'component' => $component,
        'itemtype' => $itemtype,
        'itemid' => (string)$itemid,
        'source' => $source,
        'timecreated' => $timecreated ?? time(),
    ];

    return hash('sha256', local_uckk_json_encode($payload));
}

/**
 * Return the canonical AI non-sovereignty warning.
 *
 * @return string
 */
function local_uckk_get_ai_warning(): string {
    return local_uckk_get_string_or_fallback(
        'ai_warning',
        LOCAL_UCKK_COMPONENT,
        'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.'
    );
}

/**
 * Return whether AI may be treated as final authority.
 *
 * This must remain false. The helper exists to make the rule explicit.
 *
 * @return bool
 */
function local_uckk_ai_allows_final_authority(): bool {
    return false;
}

/**
 * Return canonical UCKK navigation items.
 *
 * URLs are conservative Moodle routes. Plugin-specific pages should override
 * them in renderer/exporter layers only when the plugin is installed.
 *
 * @param int|null $courseid Optional course id.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_get_navigation_items(?int $courseid = null): array {
    $items = [
        [
            'key' => 'home',
            'label' => local_uckk_get_string_or_fallback('nav_home', 'theme_uckk', 'Accueil'),
            'url' => new moodle_url('/'),
        ],
        [
            'key' => 'mypath',
            'label' => local_uckk_get_string_or_fallback('nav_mypath', 'theme_uckk', 'Mon parcours'),
            'url' => new moodle_url('/my/courses.php'),
        ],
    ];

    if ($courseid !== null && $courseid > 0) {
        $items[] = [
            'key' => 'course',
            'label' => local_uckk_get_string_or_fallback('nav_courses', 'theme_uckk', 'Cours'),
            'url' => new moodle_url('/course/view.php', ['id' => $courseid]),
        ];
    }

    if (local_uckk_component_installed('mod_uckkchallenge') && $courseid !== null && $courseid > 0) {
        $items[] = [
            'key' => 'challenges',
            'label' => local_uckk_get_string_or_fallback('nav_challenges', 'theme_uckk', 'Défis'),
            'url' => new moodle_url('/mod/uckkchallenge/index.php', ['id' => $courseid]),
        ];
    }

    if (local_uckk_component_installed('mod_uckkassembly') && $courseid !== null && $courseid > 0) {
        $items[] = [
            'key' => 'assemblies',
            'label' => local_uckk_get_string_or_fallback('nav_assemblies', 'theme_uckk', 'Assemblées'),
            'url' => new moodle_url('/mod/uckkassembly/index.php', ['id' => $courseid]),
        ];
    }

    if (local_uckk_component_installed('mod_uckkarchive') && $courseid !== null && $courseid > 0) {
        $items[] = [
            'key' => 'archives',
            'label' => local_uckk_get_string_or_fallback('nav_archives', 'theme_uckk', 'Archives'),
            'url' => new moodle_url('/mod/uckkarchive/index.php', ['id' => $courseid]),
        ];
    }

    if (local_uckk_component_installed('tool_uckkintegrity')) {
        $params = $courseid !== null && $courseid > 0 ? ['courseid' => $courseid] : [];
        $items[] = [
            'key' => 'integrity',
            'label' => local_uckk_get_string_or_fallback('nav_integrity', 'theme_uckk', 'Inquisiteur'),
            'url' => new moodle_url('/admin/tool/uckkintegrity/index.php', $params),
        ];
    }

    foreach ($items as $index => $item) {
        if ($item['url'] instanceof moodle_url) {
            $items[$index]['url'] = $item['url']->out(false);
        }
    }

    return $items;
}

/**
 * Return basic UCKK dashboard summary for a user.
 *
 * This is intentionally conservative. Full dashboard logic belongs to
 * block_uckk_dashboard and service classes.
 *
 * @param int|null $userid User id. Defaults to current user.
 * @return array<string, mixed>
 */
function local_uckk_get_basic_user_summary(?int $userid = null): array {
    global $USER;

    $userid = $userid ?? (int)$USER->id;

    return [
        'userid' => $userid,
        'profileurl' => (new moodle_url('/user/profile.php', ['id' => $userid]))->out(false),
        'symbolicroles' => [],
        'activepathways' => [],
        'badges' => [],
        'competencies' => [],
    ];
}

/**
 * Get a local_uckk record by id when the table exists.
 *
 * @param string $table Table name without prefix.
 * @param int $id Record id.
 * @param string $fields Fields to return.
 * @return stdClass|null
 */
function local_uckk_get_record_if_table_exists(string $table, int $id, string $fields = '*'): ?stdClass {
    global $DB;

    $table = clean_param($table, PARAM_ALPHANUMEXT);

    if (!$DB->get_manager()->table_exists($table)) {
        return null;
    }

    $record = $DB->get_record($table, ['id' => $id], $fields, IGNORE_MISSING);

    return $record ?: null;
}

/**
 * Get records from a table when it exists.
 *
 * @param string $table Table name without prefix.
 * @param array<string, mixed> $conditions Conditions.
 * @param string $sort Sort clause.
 * @param string $fields Fields to return.
 * @param int $limitfrom Offset.
 * @param int $limitnum Limit.
 * @return stdClass[]
 */
function local_uckk_get_records_if_table_exists(
    string $table,
    array $conditions = [],
    string $sort = '',
    string $fields = '*',
    int $limitfrom = 0,
    int $limitnum = 0
): array {
    global $DB;

    $table = clean_param($table, PARAM_ALPHANUMEXT);

    if (!$DB->get_manager()->table_exists($table)) {
        return [];
    }

    return $DB->get_records($table, $conditions, $sort, $fields, $limitfrom, $limitnum);
}

/**
 * Return whether a UCKK table exists.
 *
 * @param string $table Table name without prefix.
 * @return bool
 */
function local_uckk_table_exists(string $table): bool {
    global $DB;

    $table = clean_param($table, PARAM_ALPHANUMEXT);

    return $DB->get_manager()->table_exists($table);
}

/**
 * Build a standard exported object reference.
 *
 * @param string $component Component name.
 * @param string $type Object type.
 * @param int|string $id Object id.
 * @param string|null $name Display name.
 * @param string|null $url URL.
 * @return array<string, mixed>
 */
function local_uckk_export_reference(
    string $component,
    string $type,
    $id,
    ?string $name = null,
    ?string $url = null
): array {
    return [
        'component' => $component,
        'type' => local_uckk_normalise_key($type),
        'id' => (string)$id,
        'name' => $name ?? '',
        'url' => $url ?? '',
        'hasurl' => !empty($url),
    ];
}

/**
 * Build an exported status object.
 *
 * @param string $status Status key.
 * @return array<string, string>
 */
function local_uckk_export_status(string $status): array {
    $status = local_uckk_normalise_status($status);

    return [
        'status' => $status,
        'label' => local_uckk_get_status_label($status),
        'cssclass' => 'uckk-status-' . $status,
    ];
}

/**
 * Build an exported visibility object.
 *
 * @param string $visibility Visibility key.
 * @return array<string, string>
 */
function local_uckk_export_visibility(string $visibility): array {
    $visibility = local_uckk_normalise_visibility($visibility);

    return [
        'visibility' => $visibility,
        'label' => local_uckk_get_visibility_label($visibility),
        'cssclass' => 'uckk-visibility-' . $visibility,
    ];
}

/**
 * Convert an associative array to a Mustache-friendly attribute list.
 *
 * @param array<string, mixed> $attributes Attributes.
 * @return array<int, array{name: string, value: string|int}>
 */
function local_uckk_attributes_for_template(array $attributes): array {
    $output = [];

    foreach ($attributes as $name => $value) {
        if ($value === null || $value === false) {
            continue;
        }

        $output[] = [
            'name' => clean_param((string)$name, PARAM_TEXT),
            'value' => is_bool($value) ? 1 : (string)$value,
        ];
    }

    return $output;
}

/**
 * Return a safe timestamp.
 *
 * @param int|null $time Timestamp.
 * @return int
 */
function local_uckk_time(?int $time = null): int {
    return $time ?? time();
}

/**
 * Return a formatted date for UI display.
 *
 * @param int|null $time Timestamp.
 * @param string|null $format Moodle date format string key or raw format.
 * @return string
 */
function local_uckk_format_time(?int $time, ?string $format = null): string {
    if (empty($time)) {
        return '';
    }

    return userdate($time, $format ?? get_string('strftimedatetimeshort', 'core_langconfig'));
}

/**
 * Return an internal recognition notice.
 *
 * @return string
 */
function local_uckk_get_internal_recognition_notice(): string {
    return local_uckk_get_string_or_fallback(
        'uckknotaccredited',
        'theme_uckk',
        'Reconnaissance interne UCKK — ne constitue pas un diplôme public accrédité.'
    );
}

/**
 * Return the canonical boundary notice.
 *
 * @return string
 */
function local_uckk_get_boundary_notice(): string {
    return local_uckk_get_string_or_fallback(
        'footer_canonicalwarning',
        'theme_uckk',
        'UCKK-Moodle est le campus pédagogique de l’UCKK, non la totalité du mouvement kOA.'
    );
}

/**
 * Return whether a user can manage UCKK at system level.
 *
 * @param int|null $userid Optional user id.
 * @return bool
 */
function local_uckk_can_manage(?int $userid = null): bool {
    return local_uckk_has_capability(
        'local/uckk:manageprograms',
        context_system::instance(),
        $userid
    );
}

/**
 * Return whether a user can view UCKK campus.
 *
 * @param int|null $userid Optional user id.
 * @return bool
 */
function local_uckk_can_view_campus(?int $userid = null): bool {
    return local_uckk_has_capability(
        'local/uckk:viewcampus',
        context_system::instance(),
        $userid
    );
}

/**
 * Return whether a user can manage UCKK pathways.
 *
 * @param context|null $context Context.
 * @param int|null $userid Optional user id.
 * @return bool
 */
function local_uckk_can_manage_pathways(?context $context = null, ?int $userid = null): bool {
    return local_uckk_has_capability(
        'local/uckk:managepathways',
        $context ?? context_system::instance(),
        $userid
    );
}

/**
 * Return whether a user can manage UCKK canon records.
 *
 * @param int|null $userid Optional user id.
 * @return bool
 */
function local_uckk_can_manage_canon(?int $userid = null): bool {
    return local_uckk_has_capability(
        'local/uckk:managecanon',
        context_system::instance(),
        $userid
    );
}

/**
 * Return whether a user can view UCKK reports.
 *
 * @param context|null $context Context.
 * @param int|null $userid Optional user id.
 * @return bool
 */
function local_uckk_can_view_reports(?context $context = null, ?int $userid = null): bool {
    return local_uckk_has_capability(
        'local/uckk:viewreports',
        $context ?? context_system::instance(),
        $userid
    );
}

/**
 * Build a minimal UCKK page setup.
 *
 * This helper is useful for local_uckk admin pages. It does not output content.
 *
 * @param string $title Page title.
 * @param moodle_url $url Page URL.
 * @param context|null $context Page context.
 * @return void
 */
function local_uckk_setup_page(string $title, moodle_url $url, ?context $context = null): void {
    global $PAGE;

    $context = $context ?? context_system::instance();

    $PAGE->set_context($context);
    $PAGE->set_url($url);
    $PAGE->set_title($title);
    $PAGE->set_heading(get_string('pluginname', LOCAL_UCKK_COMPONENT));
}

/**
 * Create a standard page heading context for Mustache.
 *
 * @param string $title Title.
 * @param string|null $subtitle Optional subtitle.
 * @param string|null $description Optional description.
 * @return array<string, mixed>
 */
function local_uckk_export_heading_context(
    string $title,
    ?string $subtitle = null,
    ?string $description = null
): array {
    return [
        'title' => $title,
        'subtitle' => $subtitle ?? '',
        'hassubtitle' => !empty($subtitle),
        'description' => $description ?? '',
        'hasdescription' => !empty($description),
        'tagline' => local_uckk_get_string_or_fallback(
            'uckktagline',
            'theme_uckk',
            'Comprendre le jeu. Jouer avec lucidité. Changer les règles.'
        ),
        'boundarynotice' => local_uckk_get_boundary_notice(),
    ];
}