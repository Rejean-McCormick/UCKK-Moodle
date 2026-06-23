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
 * Canon API facade for local_uckk.
 *
 * This class centralises the UCKK canon registry and safe interactions with
 * the local_uckk_canon table when that table exists.
 *
 * It does not replace archives, challenges, assemblies, integrity cases or
 * Moodle course content. It provides a stable institutional reference layer:
 *
 * - canonical source documents;
 * - canonical domain boundaries;
 * - document categories;
 * - seed-ready canon records;
 * - safe lookup and export methods;
 * - visibility and provenance helpers;
 * - confusion checks for UCKK/kOA/King Klown/kOA Digital Ecosystem boundaries.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use coding_exception;
use context;
use context_system;
use context_coursecat;
use context_course;
use dml_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/uckk/locallib.php');

/**
 * UCKK canon API.
 *
 * @package local_uckk
 */
final class canon_api {
    /** Canon table name. */
    public const TABLE = 'local_uckk_canon';

    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Canon registry version. */
    public const CANON_VERSION = '0.1';

    /** Canon status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Canon status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Canon status: deprecated. */
    public const STATUS_DEPRECATED = 'deprecated';

    /** Canon status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Canon category: index. */
    public const CATEGORY_INDEX = 'index';

    /** Canon category: glossary. */
    public const CATEGORY_GLOSSARY = 'glossary';

    /** Canon category: architecture. */
    public const CATEGORY_ARCHITECTURE = 'architecture';

    /** Canon category: movement. */
    public const CATEGORY_MOVEMENT = 'movement';

    /** Canon category: institution. */
    public const CATEGORY_INSTITUTION = 'institution';

    /** Canon category: governance. */
    public const CATEGORY_GOVERNANCE = 'governance';

    /** Canon category: academic. */
    public const CATEGORY_ACADEMIC = 'academic';

    /** Canon category: program. */
    public const CATEGORY_PROGRAM = 'program';

    /** Canon category: infrastructure. */
    public const CATEGORY_INFRASTRUCTURE = 'infrastructure';

    /** Canon category: narrative. */
    public const CATEGORY_NARRATIVE = 'narrative';

    /**
     * Return canonical domain boundaries.
     *
     * @return array<string, array<string, string>>
     */
    public static function get_boundary_model(): array {
        return [
            'koa' => [
                'key' => 'koa',
                'label' => 'kOA',
                'role' => 'movement',
                'description' => 'Mouvement, culture, méthode et horizon social plus large.',
                'moodleimplementation' => 'Référencé, enseigné, documenté et relié à UCKK, sans être absorbé par Moodle.',
            ],
            'uckk' => [
                'key' => 'uckk',
                'label' => 'UCKK',
                'role' => 'school',
                'description' => 'École, cité-école et branche éducative du mouvement kOA.',
                'moodleimplementation' => 'Campus Moodle principal, avec cours, parcours, compétences, badges, défis, assemblées et archives.',
            ],
            'koa_digital_ecosystem' => [
                'key' => 'koa_digital_ecosystem',
                'label' => 'kOA Digital Ecosystem',
                'role' => 'infrastructure',
                'description' => 'Infrastructure numérique enseignée, utilisée ou documentée par UCKK.',
                'moodleimplementation' => 'Objet d’apprentissage et d’intégration contrôlée, non confondu avec Moodle.',
            ],
            'king_klown' => [
                'key' => 'king_klown',
                'label' => 'King Klown',
                'role' => 'narrative',
                'description' => 'Figure narrative, pédagogique et mobilisatrice.',
                'moodleimplementation' => 'Branding, défis, symboles et théâtre public responsable, sans souveraineté institutionnelle.',
            ],
            'inquisiteur' => [
                'key' => 'inquisiteur',
                'label' => 'Inquisiteur',
                'role' => 'integrity_guardrail',
                'description' => 'Garde-fou éthique et méthodologique.',
                'moodleimplementation' => 'Système d’intégrité, contestation, correction, vérification et journalisation.',
            ],
            'assemblees' => [
                'key' => 'assemblees',
                'label' => 'Assemblées',
                'role' => 'collective_legitimacy',
                'description' => 'Espaces de légitimité collective et de décision documentée.',
                'moodleimplementation' => 'Activité dédiée mod_uckkassembly.',
            ],
            'archives' => [
                'key' => 'archives',
                'label' => 'Archives',
                'role' => 'memory',
                'description' => 'Mémoire institutionnelle, preuve, version, décision et Kristal pédagogique.',
                'moodleimplementation' => 'Activité dédiée mod_uckkarchive et références de provenance.',
            ],
        ];
    }

    /**
     * Return canonical control formula.
     *
     * @return string
     */
    public static function get_control_formula(): string {
        return 'King Klown attire. UCKK forme. kOA Digital Ecosystem opère. L’Inquisiteur vérifie. Les Assemblées légitiment. Les Archives se souviennent.';
    }

    /**
     * Return the canonical warning for confusing boundaries.
     *
     * @return string
     */
    public static function get_boundary_warning(): string {
        return 'Si un document rend la hiérarchie plus confuse, il doit être révisé. Si un document clarifie les rôles, les limites et les passages, il renforce le canon.';
    }

    /**
     * Return canonical canon categories.
     *
     * @return array<string, array<string, string>>
     */
    public static function get_categories(): array {
        return [
            self::CATEGORY_INDEX => [
                'key' => self::CATEGORY_INDEX,
                'label' => 'Index',
                'description' => 'Index général, carte documentaire et règles de cohérence du canon.',
            ],
            self::CATEGORY_GLOSSARY => [
                'key' => self::CATEGORY_GLOSSARY,
                'label' => 'Glossaire',
                'description' => 'Stabilisation des termes centraux utilisés par UCKK.',
            ],
            self::CATEGORY_ARCHITECTURE => [
                'key' => self::CATEGORY_ARCHITECTURE,
                'label' => 'Architecture générale',
                'description' => 'Clarification entre mouvement, école, infrastructure, récit et gouvernance.',
            ],
            self::CATEGORY_MOVEMENT => [
                'key' => self::CATEGORY_MOVEMENT,
                'label' => 'Mouvement kOA',
                'description' => 'Cadre large du mouvement, principes, limites et stratégie.',
            ],
            self::CATEGORY_INSTITUTION => [
                'key' => self::CATEGORY_INSTITUTION,
                'label' => 'Institution UCKK',
                'description' => 'Document fondateur, branding, identité, ton et positionnement.',
            ],
            self::CATEGORY_GOVERNANCE => [
                'key' => self::CATEGORY_GOVERNANCE,
                'label' => 'Gouvernance',
                'description' => 'Assemblées, Inquisiteur, intégrité, contestabilité et décisions.',
            ],
            self::CATEGORY_ACADEMIC => [
                'key' => self::CATEGORY_ACADEMIC,
                'label' => 'Académique',
                'description' => 'Catalogue, tronc commun, cours, compétences, badges et parcours.',
            ],
            self::CATEGORY_PROGRAM => [
                'key' => self::CATEGORY_PROGRAM,
                'label' => 'Programmes',
                'description' => 'Baccalauréats, mineures, séminaires et laboratoires internes UCKK.',
            ],
            self::CATEGORY_INFRASTRUCTURE => [
                'key' => self::CATEGORY_INFRASTRUCTURE,
                'label' => 'Infrastructure',
                'description' => 'kOA Digital Ecosystem, modules, audit, résilience et gouvernance technique.',
            ],
            self::CATEGORY_NARRATIVE => [
                'key' => self::CATEGORY_NARRATIVE,
                'label' => 'Narratif',
                'description' => 'King Klown, théâtre public responsable, récit, mobilisation et partenaires.',
            ],
        ];
    }

    /**
     * Return canonical UCKK source document registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_source_registry(): array {
        return [
            '00_index' => [
                'key' => '00_index',
                'path' => 'UCKK_Canon/00_index.md',
                'title' => 'Index général du canon UCKK',
                'category' => self::CATEGORY_INDEX,
                'role' => 'Index général, structure de la bibliothèque et règles de cohérence.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 10,
            ],
            '01_glossaire' => [
                'key' => '01_glossaire',
                'path' => 'UCKK_Canon/01_glossaire.md',
                'title' => 'Glossaire canonique UCKK',
                'category' => self::CATEGORY_GLOSSARY,
                'role' => 'Stabilisation des termes centraux UCKK.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 20,
            ],
            '02_architecture_generale' => [
                'key' => '02_architecture_generale',
                'path' => 'UCKK_Canon/02_architecture-generale-kOA-UCKK-digital-ecosystem.md',
                'title' => 'Architecture générale kOA / UCKK / Digital Ecosystem',
                'category' => self::CATEGORY_ARCHITECTURE,
                'role' => 'Clarification globale entre mouvement, école, infrastructure et récit.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 30,
            ],
            '10_koa_mouvement' => [
                'key' => '10_koa_mouvement',
                'path' => 'UCKK_Canon/10_kOA-mouvement.md',
                'title' => 'kOA — Mouvement',
                'category' => self::CATEGORY_MOVEMENT,
                'role' => 'Présentation du mouvement kOA comme cadre large.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 100,
            ],
            '11_koa_principes_limites_strategie' => [
                'key' => '11_koa_principes_limites_strategie',
                'path' => 'UCKK_Canon/11_kOA-principes-limites-strategie.md',
                'title' => 'kOA — Principes, limites et stratégie',
                'category' => self::CATEGORY_MOVEMENT,
                'role' => 'Principes, limites et stratégie du mouvement.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 110,
            ],
            '20_uckk_document_fondateur' => [
                'key' => '20_uckk_document_fondateur',
                'path' => 'UCKK_Canon/20_UCKK-document-fondateur.md',
                'title' => 'Document fondateur UCKK',
                'category' => self::CATEGORY_INSTITUTION,
                'role' => 'Fondation de l’Univers-Cité King Klown.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 200,
            ],
            '21_uckk_branding' => [
                'key' => '21_uckk_branding',
                'path' => 'UCKK_Canon/21_UCKK-branding.md',
                'title' => 'Branding UCKK',
                'category' => self::CATEGORY_INSTITUTION,
                'role' => 'Ton, symboles, slogans, palette et positionnement.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 210,
            ],
            '22_uckk_gouvernance' => [
                'key' => '22_uckk_gouvernance',
                'path' => 'UCKK_Canon/22_UCKK-gouvernance-assemblees-inquisiteur.md',
                'title' => 'Gouvernance, Assemblées et Inquisiteur',
                'category' => self::CATEGORY_GOVERNANCE,
                'role' => 'Gouvernance, décisions, assemblées, intégrité, contestation et garde-fous.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 220,
            ],
            '23_uckk_defis_theatre_public' => [
                'key' => '23_uckk_defis_theatre_public',
                'path' => 'UCKK_Canon/23_UCKK-defis-theatre-public.md',
                'title' => 'Défis et théâtre public responsable',
                'category' => self::CATEGORY_GOVERNANCE,
                'role' => 'Défis King Klown, scène publique, preuves et limites éthiques.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 230,
            ],
            '30_uckk_catalogue_academique' => [
                'key' => '30_uckk_catalogue_academique',
                'path' => 'UCKK_Canon/30_UCKK-catalogue-academique.md',
                'title' => 'Catalogue académique UCKK',
                'category' => self::CATEGORY_ACADEMIC,
                'role' => 'Structure générale des programmes internes UCKK.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 300,
            ],
            '31_uckk_tronc_commun' => [
                'key' => '31_uckk_tronc_commun',
                'path' => 'UCKK_Canon/31_UCKK-tronc-commun.md',
                'title' => 'Tronc commun UCKK',
                'category' => self::CATEGORY_ACADEMIC,
                'role' => 'Cours fondamentaux, compétences et orientation pédagogique commune.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 310,
            ],
            '42_uckk_liste_et_fiches_de_cours' => [
                'key' => '42_uckk_liste_et_fiches_de_cours',
                'path' => 'UCKK_Canon/42_UCKK-liste-et-fiches-de-cours.md',
                'title' => 'Liste officielle et fiches de cours',
                'category' => self::CATEGORY_ACADEMIC,
                'role' => 'Liste officielle des cours et fiches de cours UCKK.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 420,
            ],
            '50_koa_digital_ecosystem_document_maitre' => [
                'key' => '50_koa_digital_ecosystem_document_maitre',
                'path' => 'UCKK_Canon/50_kOA-digital-ecosystem-document-maitre.md',
                'title' => 'kOA Digital Ecosystem — Document maître',
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'role' => 'Vue d’ensemble de l’infrastructure numérique kOA.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 500,
            ],
            '51_koa_digital_ecosystem_architecture_modules' => [
                'key' => '51_koa_digital_ecosystem_architecture_modules',
                'path' => 'UCKK_Canon/51_kOA-digital-ecosystem-architecture-et-modules.md',
                'title' => 'kOA Digital Ecosystem — Architecture et modules',
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'role' => 'Modules, sidecars, résilience, gouvernance technique et relation avec UCKK.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 510,
            ],
            '52_koa_digital_ecosystem_gouvernance_audit_resilience' => [
                'key' => '52_koa_digital_ecosystem_gouvernance_audit_resilience',
                'path' => 'UCKK_Canon/52_kOA-digital-ecosystem-gouvernance-audit-resilience.md',
                'title' => 'kOA Digital Ecosystem — Gouvernance, audit et résilience',
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'role' => 'Auditabilité, logs, seuils, confidentialité, contestabilité et gouvernance technique.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 520,
            ],
            '60_king_klown_narrative_public_partenaires' => [
                'key' => '60_king_klown_narrative_public_partenaires',
                'path' => 'UCKK_Canon/60_king-klown-narrative-public-partenaires.md',
                'title' => 'King Klown — Narratif public et partenaires',
                'category' => self::CATEGORY_NARRATIVE,
                'role' => 'Persona narrative, théâtre public responsable, communication et partenaires.',
                'required' => true,
                'visibility' => self::VISIBILITY_PUBLIC,
                'sortorder' => 600,
            ],
        ];
    }

    /**
     * Return program canon source registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_program_source_registry(): array {
        return [
            '32_baccalaureat_grand_jeu_social' => [
                'key' => '32_baccalaureat_grand_jeu_social',
                'path' => 'UCKK_Canon/32_baccalaureat-grand-jeu-social.md',
                'title' => 'Baccalauréat du Grand Jeu social',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'grand_jeu_social',
                'sortorder' => 320,
            ],
            '33_baccalaureat_architecture_ecosysteme_digital_koa' => [
                'key' => '33_baccalaureat_architecture_ecosysteme_digital_koa',
                'path' => 'UCKK_Canon/33_baccalaureat-architecture-ecosysteme-digital-kOA.md',
                'title' => 'Voie de l’Architecture de l’écosystème digital kOA',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'architecture_ecosysteme_digital_koa',
                'sortorder' => 330,
            ],
            '34_baccalaureat_metaphysique' => [
                'key' => '34_baccalaureat_metaphysique',
                'path' => 'UCKK_Canon/34_baccalaureat-metaphysique.md',
                'title' => 'Voie de la Métaphysique',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'metaphysique',
                'sortorder' => 340,
            ],
            '35_baccalaureat_architecture_sociotechnique' => [
                'key' => '35_baccalaureat_architecture_sociotechnique',
                'path' => 'UCKK_Canon/35_baccalaureat-architecture-sociotechnique.md',
                'title' => 'Voie de l’Architecture sociotechnique',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'architecture_sociotechnique',
                'sortorder' => 350,
            ],
            '36_baccalaureat_sciences_politiques' => [
                'key' => '36_baccalaureat_sciences_politiques',
                'path' => 'UCKK_Canon/36_baccalaureat-sciences-politiques.md',
                'title' => 'Voie des Sciences politiques',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'sciences_politiques',
                'sortorder' => 360,
            ],
            '37_baccalaureat_economie' => [
                'key' => '37_baccalaureat_economie',
                'path' => 'UCKK_Canon/37_baccalaureat-economie.md',
                'title' => 'Voie de l’Économie',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'economie',
                'sortorder' => 370,
            ],
            '38_baccalaureat_ecologie' => [
                'key' => '38_baccalaureat_ecologie',
                'path' => 'UCKK_Canon/38_baccalaureat-ecologie.md',
                'title' => 'Voie de l’Écologie',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'ecologie',
                'sortorder' => 380,
            ],
            '39_baccalaureat_linguistique_architecture_du_sens' => [
                'key' => '39_baccalaureat_linguistique_architecture_du_sens',
                'path' => 'UCKK_Canon/39_baccalaureat-linguistique-architecture-du-sens.md',
                'title' => 'Voie de la Linguistique et de l’architecture du sens',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'linguistique_architecture_du_sens',
                'sortorder' => 390,
            ],
            '40_baccalaureat_ia_gouvernable' => [
                'key' => '40_baccalaureat_ia_gouvernable',
                'path' => 'UCKK_Canon/40_baccalaureat-intelligence-artificielle-gouvernable.md',
                'title' => 'Voie de la Production augmentée par l’IA',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'intelligence_artificielle_gouvernable',
                'sortorder' => 400,
            ],
            '41_baccalaureat_intervention_sociale' => [
                'key' => '41_baccalaureat_intervention_sociale',
                'path' => 'UCKK_Canon/41_baccalaureat-intervention-sociale-systemes-humains.md',
                'title' => 'Voie de l’Intervention sociale et des systèmes humains',
                'category' => self::CATEGORY_PROGRAM,
                'programkey' => 'intervention_sociale',
                'sortorder' => 410,
            ],
        ];
    }

    /**
     * Return the complete source registry.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_complete_source_registry(): array {
        return self::get_source_registry() + self::get_program_source_registry();
    }

    /**
     * Return one source definition by key.
     *
     * @param string $key Canon source key.
     * @return array<string, mixed>|null
     */
    public static function get_source_definition(string $key): ?array {
        $key = self::normalise_key($key);
        $registry = self::get_complete_source_registry();

        return $registry[$key] ?? null;
    }

    /**
     * Return whether the source key is canonical.
     *
     * @param string $key Canon source key.
     * @return bool
     */
    public static function is_known_source(string $key): bool {
        return self::get_source_definition($key) !== null;
    }

    /**
     * Return seed-ready canon records.
     *
     * @return array<int, stdClass>
     */
    public static function build_seed_records(): array {
        $records = [];

        foreach (self::get_complete_source_registry() as $definition) {
            $records[] = self::build_record_from_definition($definition);
        }

        return $records;
    }

    /**
     * Build a local_uckk_canon record from a source definition.
     *
     * @param array<string, mixed> $definition Source definition.
     * @return stdClass
     */
    public static function build_record_from_definition(array $definition): stdClass {
        $now = time();

        $record = new stdClass();
        $record->canonkey = self::normalise_key((string)$definition['key']);
        $record->path = (string)$definition['path'];
        $record->title = (string)$definition['title'];
        $record->category = self::normalise_key((string)$definition['category']);
        $record->summary = (string)($definition['role'] ?? '');
        $record->status = self::STATUS_ACTIVE;
        $record->visibility = self::normalise_visibility((string)($definition['visibility'] ?? self::VISIBILITY_PUBLIC));
        $record->version = self::CANON_VERSION;
        $record->sortorder = (int)($definition['sortorder'] ?? 999);
        $record->required = !empty($definition['required']) ? 1 : 0;
        $record->metadata = local_uckk_json_encode([
            'programkey' => $definition['programkey'] ?? null,
            'source' => 'UCKK_Canon',
            'seeded' => true,
            'boundarymodel' => false,
        ]);
        $record->timecreated = $now;
        $record->timemodified = $now;

        return $record;
    }

    /**
     * Return whether the canon table exists.
     *
     * @return bool
     */
    public static function table_exists(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(self::TABLE);
    }

    /**
     * Find a canon record by id.
     *
     * @param int $id Record id.
     * @return stdClass|null
     * @throws dml_exception
     */
    public static function get_record(int $id): ?stdClass {
        global $DB;

        if (!self::table_exists()) {
            return null;
        }

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Find a canon record by canonical key.
     *
     * @param string $key Canon key.
     * @return stdClass|null
     * @throws dml_exception
     */
    public static function get_record_by_key(string $key): ?stdClass {
        global $DB;

        if (!self::table_exists()) {
            return null;
        }

        $record = $DB->get_record(
            self::TABLE,
            ['canonkey' => self::normalise_key($key)],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Find canon records by category.
     *
     * @param string $category Canon category.
     * @param bool $onlyactive Whether to return only active records.
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function get_records_by_category(string $category, bool $onlyactive = true): array {
        global $DB;

        if (!self::table_exists()) {
            return [];
        }

        $conditions = [
            'category' => self::normalise_key($category),
        ];

        if ($onlyactive) {
            $conditions['status'] = self::STATUS_ACTIVE;
        }

        return $DB->get_records(self::TABLE, $conditions, 'sortorder ASC, title ASC');
    }

    /**
     * Return all canon records.
     *
     * @param bool $onlyactive Whether to return only active records.
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function get_records(bool $onlyactive = true): array {
        global $DB;

        if (!self::table_exists()) {
            return [];
        }

        $conditions = [];

        if ($onlyactive) {
            $conditions['status'] = self::STATUS_ACTIVE;
        }

        return $DB->get_records(self::TABLE, $conditions, 'sortorder ASC, category ASC, title ASC');
    }

    /**
     * Create or update a canon record by canonical key.
     *
     * @param stdClass $record Canon record.
     * @return int Record id.
     * @throws dml_exception
     */
    public static function upsert_record(stdClass $record): int {
        global $DB;

        self::require_manage_capability();

        if (!self::table_exists()) {
            throw new coding_exception('Cannot upsert canon record because table local_uckk_canon does not exist.');
        }

        if (empty($record->canonkey)) {
            throw new coding_exception('Canon record must include canonkey.');
        }

        $record = self::prepare_record($record);
        $existing = self::get_record_by_key($record->canonkey);

        if ($existing !== null) {
            $record->id = $existing->id;
            $record->timemodified = time();
            $DB->update_record(self::TABLE, $record);

            return (int)$existing->id;
        }

        $record->timecreated = $record->timecreated ?? time();
        $record->timemodified = $record->timemodified ?? time();

        return (int)$DB->insert_record(self::TABLE, $record);
    }

    /**
     * Seed all canonical records idempotently.
     *
     * @return array<string, int>
     * @throws dml_exception
     */
    public static function seed_canon_registry(): array {
        self::require_manage_capability();

        $ids = [];

        foreach (self::build_seed_records() as $record) {
            $ids[$record->canonkey] = self::upsert_record($record);
        }

        return $ids;
    }

    /**
     * Update record status.
     *
     * @param string $key Canon key.
     * @param string $status New status.
     * @return bool
     * @throws dml_exception
     */
    public static function set_status(string $key, string $status): bool {
        global $DB;

        self::require_manage_capability();

        $record = self::get_record_by_key($key);

        if ($record === null) {
            return false;
        }

        $record->status = self::normalise_status($status);
        $record->timemodified = time();

        return $DB->update_record(self::TABLE, $record);
    }

    /**
     * Update record visibility.
     *
     * @param string $key Canon key.
     * @param string $visibility New visibility.
     * @return bool
     * @throws dml_exception
     */
    public static function set_visibility(string $key, string $visibility): bool {
        global $DB;

        self::require_manage_capability();

        $record = self::get_record_by_key($key);

        if ($record === null) {
            return false;
        }

        $record->visibility = self::normalise_visibility($visibility);
        $record->timemodified = time();

        return $DB->update_record(self::TABLE, $record);
    }

    /**
     * Build a search result list for canon registry.
     *
     * @param string $query Search query.
     * @param string|null $category Optional category filter.
     * @param bool $onlyactive Whether to return only active records.
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, ?string $category = null, bool $onlyactive = true): array {
        $query = trim(\core_text::strtolower($query));
        $records = $category === null ? self::get_records($onlyactive) : self::get_records_by_category($category, $onlyactive);
        $results = [];

        foreach ($records as $record) {
            $haystack = \core_text::strtolower(
                implode(' ', [
                    $record->canonkey ?? '',
                    $record->title ?? '',
                    $record->summary ?? '',
                    $record->path ?? '',
                    $record->category ?? '',
                ])
            );

            if ($query === '' || strpos($haystack, $query) !== false) {
                $results[] = self::export_record_for_template($record);
            }
        }

        return $results;
    }

    /**
     * Export full canon registry for templates.
     *
     * @param bool $preferdb Whether to prefer database records when available.
     * @return array<string, mixed>
     */
    public static function export_registry_for_template(bool $preferdb = true): array {
        $categories = [];
        $records = $preferdb && self::table_exists()
            ? array_values(self::get_records(false))
            : self::build_seed_records();

        foreach (self::get_categories() as $categorykey => $category) {
            $categories[$categorykey] = [
                'key' => $categorykey,
                'label' => $category['label'],
                'description' => $category['description'],
                'items' => [],
                'hasitems' => false,
            ];
        }

        foreach ($records as $record) {
            $exported = self::export_record_for_template($record);
            $categorykey = $exported['category'];

            if (!isset($categories[$categorykey])) {
                $categories[$categorykey] = [
                    'key' => $categorykey,
                    'label' => ucfirst(str_replace('_', ' ', $categorykey)),
                    'description' => '',
                    'items' => [],
                    'hasitems' => false,
                ];
            }

            $categories[$categorykey]['items'][] = $exported;
            $categories[$categorykey]['hasitems'] = true;
        }

        return [
            'version' => self::CANON_VERSION,
            'formula' => self::get_control_formula(),
            'boundarywarning' => self::get_boundary_warning(),
            'categories' => array_values($categories),
            'hascategories' => !empty($categories),
            'boundaries' => array_values(self::get_boundary_model()),
            'hasboundaries' => true,
        ];
    }

    /**
     * Export one canon record for Mustache templates.
     *
     * @param stdClass $record Canon record.
     * @return array<string, mixed>
     */
    public static function export_record_for_template(stdClass $record): array {
        $metadata = local_uckk_json_decode_array($record->metadata ?? null);

        return [
            'id' => isset($record->id) ? (int)$record->id : 0,
            'canonkey' => (string)($record->canonkey ?? ''),
            'path' => (string)($record->path ?? ''),
            'title' => format_string((string)($record->title ?? ''), true),
            'summary' => format_text((string)($record->summary ?? ''), FORMAT_HTML),
            'category' => self::normalise_key((string)($record->category ?? '')),
            'categorylabel' => self::get_category_label((string)($record->category ?? '')),
            'status' => self::normalise_status((string)($record->status ?? self::STATUS_DRAFT)),
            'statuslabel' => self::get_status_label((string)($record->status ?? self::STATUS_DRAFT)),
            'visibility' => self::normalise_visibility((string)($record->visibility ?? self::VISIBILITY_PRIVATE)),
            'visibilitylabel' => self::get_visibility_label((string)($record->visibility ?? self::VISIBILITY_PRIVATE)),
            'version' => (string)($record->version ?? self::CANON_VERSION),
            'sortorder' => (int)($record->sortorder ?? 999),
            'required' => !empty($record->required),
            'metadata' => $metadata,
            'hasmetadata' => !empty($metadata),
            'programkey' => $metadata['programkey'] ?? '',
            'hasprogramkey' => !empty($metadata['programkey']),
            'timecreated' => isset($record->timecreated) ? (int)$record->timecreated : 0,
            'timemodified' => isset($record->timemodified) ? (int)$record->timemodified : 0,
        ];
    }

    /**
     * Export boundary model for templates.
     *
     * @return array<string, mixed>
     */
    public static function export_boundaries_for_template(): array {
        return [
            'formula' => self::get_control_formula(),
            'warning' => self::get_boundary_warning(),
            'boundaries' => array_values(self::get_boundary_model()),
            'hasboundaries' => true,
        ];
    }

    /**
     * Validate whether a text seems to confuse canon boundaries.
     *
     * This is not an AI validator and not an integrity decision. It only
     * returns conservative warnings for human review.
     *
     * @param string $text Text to inspect.
     * @return array<int, array<string, string>>
     */
    public static function detect_boundary_warnings(string $text): array {
        $warnings = [];
        $normalised = \core_text::strtolower($text);

        $patterns = [
            [
                'key' => 'uckk_is_all_koa',
                'pattern' => '/uckk\s+(est|=)\s+(tout\s+)?k[o0]a/u',
                'message' => 'UCKK ne doit pas être présentée comme la totalité du mouvement kOA.',
            ],
            [
                'key' => 'moodle_is_koa_ecosystem',
                'pattern' => '/moodle\s+(est|=)\s+(le\s+)?k[o0]a\s+digital\s+ecosystem/u',
                'message' => 'Moodle implémente le campus UCKK; il ne doit pas être confondu avec tout le kOA Digital Ecosystem.',
            ],
            [
                'key' => 'king_klown_sovereign',
                'pattern' => '/king\s+klown\s+(décide|decide|gouverne|commande|ordonne|est\s+souverain)/u',
                'message' => 'King Klown est une figure narrative et pédagogique, pas une autorité souveraine.',
            ],
            [
                'key' => 'ai_final_authority',
                'pattern' => '/(ia|ai)\s+(décide|decide|valide|certifie|sanctionne)\s+(seule|automatiquement|finalement)?/u',
                'message' => 'L’IA peut assister, mais elle ne doit pas devenir autorité finale.',
            ],
            [
                'key' => 'fiction_fact_confusion',
                'pattern' => '/(tout\s+est\s+fiction|les\s+preuves\s+sont\s+symboliques|les\s+faits\s+sont\s+optionnels)/u',
                'message' => 'La couche narrative doit rester distinguable des faits, règles, preuves et décisions.',
            ],
        ];

        foreach ($patterns as $definition) {
            if (preg_match($definition['pattern'], $normalised) === 1) {
                $warnings[] = [
                    'key' => $definition['key'],
                    'message' => $definition['message'],
                ];
            }
        }

        return $warnings;
    }

    /**
     * Determine whether a text passes the simple boundary warning scan.
     *
     * @param string $text Text to inspect.
     * @return bool
     */
    public static function passes_boundary_scan(string $text): bool {
        return empty(self::detect_boundary_warnings($text));
    }

    /**
     * Build a provenance descriptor for a canon source.
     *
     * @param string $key Canon source key.
     * @param string|null $sourcehash Optional external file hash.
     * @return array<string, mixed>
     */
    public static function build_provenance_descriptor(string $key, ?string $sourcehash = null): array {
        $definition = self::get_source_definition($key);

        if ($definition === null) {
            throw new coding_exception('Unknown UCKK canon source: ' . $key);
        }

        return [
            'component' => self::COMPONENT,
            'itemtype' => 'canon',
            'itemkey' => $definition['key'],
            'path' => $definition['path'],
            'title' => $definition['title'],
            'category' => $definition['category'],
            'source' => 'UCKK_Canon',
            'version' => self::CANON_VERSION,
            'sourcehash' => $sourcehash ?? '',
            'provenancehash' => local_uckk_build_provenance_hash(
                self::COMPONENT,
                'canon',
                $definition['key'],
                $definition['path']
            ),
        ];
    }

    /**
     * Build a canonical Moodle URL for a local canon page.
     *
     * The actual page may be implemented later. This helper keeps URL creation
     * consistent for renderers and reports.
     *
     * @param string|null $key Optional canon key.
     * @return moodle_url
     */
    public static function build_canon_url(?string $key = null): moodle_url {
        $params = [];

        if ($key !== null && trim($key) !== '') {
            $params['key'] = self::normalise_key($key);
        }

        return new moodle_url('/local/uckk/canon.php', $params);
    }

    /**
     * Prepare a record before insert/update.
     *
     * @param stdClass $record Raw record.
     * @return stdClass
     */
    private static function prepare_record(stdClass $record): stdClass {
        $record->canonkey = self::normalise_key((string)$record->canonkey);
        $record->path = clean_param((string)($record->path ?? ''), PARAM_TEXT);
        $record->title = clean_param((string)($record->title ?? ''), PARAM_TEXT);
        $record->category = self::normalise_key((string)($record->category ?? self::CATEGORY_INDEX));
        $record->summary = (string)($record->summary ?? '');
        $record->status = self::normalise_status((string)($record->status ?? self::STATUS_DRAFT));
        $record->visibility = self::normalise_visibility((string)($record->visibility ?? self::VISIBILITY_PRIVATE));
        $record->version = clean_param((string)($record->version ?? self::CANON_VERSION), PARAM_TEXT);
        $record->sortorder = (int)($record->sortorder ?? 999);
        $record->required = !empty($record->required) ? 1 : 0;

        if (!isset($record->metadata) || trim((string)$record->metadata) === '') {
            $record->metadata = local_uckk_json_encode([]);
        } else {
            $record->metadata = local_uckk_json_encode(local_uckk_json_decode_array((string)$record->metadata));
        }

        return $record;
    }

    /**
     * Require permission to manage canon.
     *
     * @param context|null $context Optional context.
     * @return void
     */
    private static function require_manage_capability(?context $context = null): void {
        require_capability('local/uckk:managecanon', $context ?? context_system::instance());
    }

    /**
     * Normalise canon key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private static function normalise_key(string $key): string {
        return local_uckk_normalise_key($key);
    }

    /**
     * Normalise canon status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalise_status(string $status): string {
        $status = self::normalise_key($status);
        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_DEPRECATED,
            self::STATUS_ARCHIVED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise canon visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = self::normalise_key($visibility);
        $allowed = [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
        ];

        return in_array($visibility, $allowed, true) ? $visibility : self::VISIBILITY_PRIVATE;
    }

    /**
     * Return category display label.
     *
     * @param string $category Category key.
     * @return string
     */
    private static function get_category_label(string $category): string {
        $category = self::normalise_key($category);
        $categories = self::get_categories();

        if (isset($categories[$category])) {
            return $categories[$category]['label'];
        }

        return ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Return status display label.
     *
     * @param string $status Status key.
     * @return string
     */
    private static function get_status_label(string $status): string {
        $status = self::normalise_status($status);

        $labels = [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_DEPRECATED => 'Déprécié',
            self::STATUS_ARCHIVED => 'Archivé',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Return visibility display label.
     *
     * @param string $visibility Visibility key.
     * @return string
     */
    private static function get_visibility_label(string $visibility): string {
        $visibility = self::normalise_visibility($visibility);

        $labels = [
            self::VISIBILITY_PRIVATE => 'Privé',
            self::VISIBILITY_INSTITUTION => 'Institution',
            self::VISIBILITY_PUBLIC => 'Public',
        ];

        return $labels[$visibility] ?? ucfirst(str_replace('_', ' ', $visibility));
    }

    /**
     * Resolve a context from optional scope values.
     *
     * @param string $contexttype Context type.
     * @param int|null $instanceid Context instance id.
     * @return context
     */
    public static function resolve_context(string $contexttype = 'system', ?int $instanceid = null): context {
        $contexttype = self::normalise_key($contexttype);

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

            case 'system':
            default:
                return context_system::instance();
        }
    }
}

