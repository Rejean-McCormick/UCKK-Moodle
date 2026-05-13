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
 * Competency API facade for local_uckk.
 *
 * This class centralises UCKK competency definitions and safe interactions
 * with Moodle's native competency system.
 *
 * It must not replace Moodle's competency subsystem. It provides:
 *
 * - canonical UCKK competency definitions;
 * - stable framework metadata;
 * - mapping between tronc commun courses and UCKK competencies;
 * - helper methods for locating Moodle competency framework records;
 * - helper methods for locating Moodle competency records;
 * - safe export structures for dashboards, reports, seeders and tests;
 * - optional delegation to core_competency API when available.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use coding_exception;
use context;
use context_course;
use context_module;
use context_system;
use context_user;
use core_component;
use dml_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/uckk/locallib.php');

/**
 * UCKK competency API facade.
 *
 * @package local_uckk
 */
final class competency_api {
    /** UCKK competency framework idnumber. */
    public const FRAMEWORK_IDNUMBER = 'UCKK-COMP-FRAMEWORK';

    /** UCKK competency framework shortname. */
    public const FRAMEWORK_SHORTNAME = 'UCKK — Compétences fondamentales';

    /** UCKK competency framework description. */
    public const FRAMEWORK_DESCRIPTION = 'Référentiel fondamental des compétences UCKK : Grand Jeu social, preuve, IA non souveraine, assemblées, archives, éthique et contestabilité.';

    /** Competency scale idnumber used by the seed tool when creating a custom scale. */
    public const SCALE_IDNUMBER = 'UCKK-COMP-SCALE-0-5';

    /** Competency scale name. */
    public const SCALE_NAME = 'UCKK — Démonstration 0–5';

    /** Minimum rating considered demonstrated. */
    public const RATING_DEMONSTRATED = 3;

    /** Minimum rating considered public-quality evidence. */
    public const RATING_PUBLIC_QUALITY = 4;

    /** Minimum rating considered archived as reusable Kristal. */
    public const RATING_ARCHIVED_KRISTAL = 5;

    /** Moodle component name. */
    private const COMPONENT = 'local_uckk';

    /**
     * Return canonical UCKK competency framework metadata.
     *
     * @return array<string, mixed>
     */
    public static function get_framework_definition(): array {
        return [
            'idnumber' => self::FRAMEWORK_IDNUMBER,
            'shortname' => self::FRAMEWORK_SHORTNAME,
            'description' => self::FRAMEWORK_DESCRIPTION,
            'descriptionformat' => FORMAT_HTML,
            'visible' => 1,
            'contextid' => context_system::instance()->id,
            'scaleidnumber' => self::SCALE_IDNUMBER,
            'taxonomies' => 'competency,competency,competency,competency',
        ];
    }

    /**
     * Return canonical UCKK competency scale values.
     *
     * These values are stored as an ordered reference for the seed tool and
     * reports. Actual Moodle scale creation is handled by tool_uckkseed or
     * administrator configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_scale_definition(): array {
        return [
            0 => [
                'value' => 0,
                'key' => 'not_observed',
                'label' => 'Non observé',
                'demonstrated' => false,
                'publicquality' => false,
                'archivedkristal' => false,
            ],
            1 => [
                'value' => 1,
                'key' => 'attempted',
                'label' => 'Tenté',
                'demonstrated' => false,
                'publicquality' => false,
                'archivedkristal' => false,
            ],
            2 => [
                'value' => 2,
                'key' => 'demonstrated_with_support',
                'label' => 'Démontré avec soutien',
                'demonstrated' => false,
                'publicquality' => false,
                'archivedkristal' => false,
            ],
            3 => [
                'value' => 3,
                'key' => 'demonstrated_independently',
                'label' => 'Démontré de façon autonome',
                'demonstrated' => true,
                'publicquality' => false,
                'archivedkristal' => false,
            ],
            4 => [
                'value' => 4,
                'key' => 'public_quality_evidence',
                'label' => 'Démontré avec preuve de qualité publique',
                'demonstrated' => true,
                'publicquality' => true,
                'archivedkristal' => false,
            ],
            5 => [
                'value' => 5,
                'key' => 'archived_as_reusable_kristal',
                'label' => 'Démontré et archivé comme Kristal réutilisable',
                'demonstrated' => true,
                'publicquality' => true,
                'archivedkristal' => true,
            ],
        ];
    }

    /**
     * Return canonical UCKK competency definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_competency_definitions(): array {
        return [
            'UCKK-COMP-001' => [
                'idnumber' => 'UCKK-COMP-001',
                'shortname' => 'Lire le Grand Jeu social',
                'description' => 'Identifier règles visibles, règles implicites, acteurs, incitatifs, flux, asymétries et effets de pouvoir dans une situation sociale.',
                'sortorder' => 1,
                'category' => 'grand_jeu_social',
            ],
            'UCKK-COMP-002' => [
                'idnumber' => 'UCKK-COMP-002',
                'shortname' => 'Cartographier un système',
                'description' => 'Produire une carte intelligible d’un système : acteurs, relations, ressources, contraintes, conflits, leviers et rétroactions.',
                'sortorder' => 2,
                'category' => 'cartographie',
            ],
            'UCKK-COMP-003' => [
                'idnumber' => 'UCKK-COMP-003',
                'shortname' => 'Distinguer fait, hypothèse, interprétation, récit et décision',
                'description' => 'Séparer explicitement ce qui est observé, supposé, interprété, raconté et décidé afin de réduire la confusion et l’autorité cachée.',
                'sortorder' => 3,
                'category' => 'preuve',
            ],
            'UCKK-COMP-004' => [
                'idnumber' => 'UCKK-COMP-004',
                'shortname' => 'Utiliser l’IA comme outil non souverain',
                'description' => 'Utiliser l’IA pour chercher, comparer, reformuler, critiquer et cartographier sans lui déléguer l’autorité finale.',
                'sortorder' => 4,
                'category' => 'ia_gouvernable',
            ],
            'UCKK-COMP-005' => [
                'idnumber' => 'UCKK-COMP-005',
                'shortname' => 'Produire une preuve vérifiable',
                'description' => 'Produire, relier, documenter et présenter des preuves vérifiables, proportionnées et contextualisées.',
                'sortorder' => 5,
                'category' => 'preuve',
            ],
            'UCKK-COMP-006' => [
                'idnumber' => 'UCKK-COMP-006',
                'shortname' => 'Participer à une assemblée structurée',
                'description' => 'Participer à une délibération structurée : propositions, objections, critères, décisions, responsabilités et mémoire.',
                'sortorder' => 6,
                'category' => 'assemblees',
            ],
            'UCKK-COMP-007' => [
                'idnumber' => 'UCKK-COMP-007',
                'shortname' => 'Concevoir une mobilisation responsable',
                'description' => 'Concevoir une mobilisation sans harcèlement, humiliation, manipulation, confusion volontaire ou usage abusif du théâtre public.',
                'sortorder' => 7,
                'category' => 'mobilisation',
            ],
            'UCKK-COMP-008' => [
                'idnumber' => 'UCKK-COMP-008',
                'shortname' => 'Documenter une décision',
                'description' => 'Documenter la décision, ses raisons, ses preuves, ses objections, ses limites, son contexte et sa contestabilité.',
                'sortorder' => 8,
                'category' => 'decision',
            ],
            'UCKK-COMP-009' => [
                'idnumber' => 'UCKK-COMP-009',
                'shortname' => 'Archiver un apprentissage',
                'description' => 'Transformer un travail, une décision ou une preuve en mémoire réutilisable : archive, version, provenance et synthèse.',
                'sortorder' => 9,
                'category' => 'archives',
            ],
            'UCKK-COMP-010' => [
                'idnumber' => 'UCKK-COMP-010',
                'shortname' => 'Appliquer l’éthique UCKK',
                'description' => 'Appliquer les limites éthiques UCKK : dignité, non-domination, non-manipulation, responsabilité, proportionnalité et recours.',
                'sortorder' => 10,
                'category' => 'ethique',
            ],
            'UCKK-COMP-011' => [
                'idnumber' => 'UCKK-COMP-011',
                'shortname' => 'Détecter l’autorité cachée',
                'description' => 'Repérer les formes de pouvoir non déclarées : cadrage, métriques, expertise, interface, procédure, récit ou automatisation.',
                'sortorder' => 11,
                'category' => 'pouvoir',
            ],
            'UCKK-COMP-012' => [
                'idnumber' => 'UCKK-COMP-012',
                'shortname' => 'Construire un artefact utile',
                'description' => 'Transformer une analyse en artefact utilisable : carte, protocole, outil, document, prototype, rituel ou procédure.',
                'sortorder' => 12,
                'category' => 'construction',
            ],
            'UCKK-COMP-013' => [
                'idnumber' => 'UCKK-COMP-013',
                'shortname' => 'Assurer la contestabilité',
                'description' => 'Rendre une affirmation, décision, preuve ou procédure contestable : critères visibles, recours, correction et mémoire.',
                'sortorder' => 13,
                'category' => 'integrite',
            ],
            'UCKK-COMP-014' => [
                'idnumber' => 'UCKK-COMP-014',
                'shortname' => 'Relier connaissance, décision, action et mémoire',
                'description' => 'Relier le cycle kOA : connaître, choisir, agir et se souvenir, sans confondre école, mouvement, plateforme, récit et infrastructure.',
                'sortorder' => 14,
                'category' => 'koa',
            ],
        ];
    }

    /**
     * Return tronc commun competency mappings.
     *
     * @return array<string, string[]>
     */
    public static function get_tronc_commun_mapping(): array {
        return [
            'UCKK-TC101' => [
                'UCKK-COMP-002',
                'UCKK-COMP-003',
                'UCKK-COMP-004',
                'UCKK-COMP-005',
            ],
            'UCKK-TC102' => [
                'UCKK-COMP-006',
                'UCKK-COMP-008',
                'UCKK-COMP-013',
            ],
            'UCKK-TC103' => [
                'UCKK-COMP-001',
                'UCKK-COMP-005',
                'UCKK-COMP-011',
                'UCKK-COMP-012',
            ],
            'UCKK-TC104' => [
                'UCKK-COMP-001',
                'UCKK-COMP-002',
                'UCKK-COMP-011',
            ],
            'UCKK-TC105' => [
                'UCKK-COMP-003',
                'UCKK-COMP-010',
                'UCKK-COMP-013',
            ],
            'UCKK-TC106' => [
                'UCKK-COMP-006',
                'UCKK-COMP-007',
                'UCKK-COMP-010',
            ],
            'UCKK-TC107' => [
                'UCKK-COMP-014',
                'UCKK-COMP-001',
                'UCKK-COMP-008',
            ],
            'UCKK-TC108' => [
                'UCKK-COMP-010',
                'UCKK-COMP-013',
                'UCKK-COMP-005',
            ],
        ];
    }

    /**
     * Return one canonical competency definition.
     *
     * @param string $idnumber Competency idnumber.
     * @return array<string, mixed>|null
     */
    public static function get_definition(string $idnumber): ?array {
        $idnumber = self::normalise_idnumber($idnumber);
        $definitions = self::get_competency_definitions();

        return $definitions[$idnumber] ?? null;
    }

    /**
     * Return whether an idnumber is a canonical UCKK competency.
     *
     * @param string $idnumber Competency idnumber.
     * @return bool
     */
    public static function is_canonical_competency(string $idnumber): bool {
        return self::get_definition($idnumber) !== null;
    }

    /**
     * Return all canonical competency idnumbers.
     *
     * @return string[]
     */
    public static function get_idnumbers(): array {
        return array_keys(self::get_competency_definitions());
    }

    /**
     * Return the expected competency idnumbers for a course.
     *
     * @param string|stdClass $course Course shortname, idnumber, or Moodle course record.
     * @return string[]
     */
    public static function get_expected_for_course($course): array {
        $coursekeys = self::extract_course_keys($course);
        $mapping = self::get_tronc_commun_mapping();

        foreach ($coursekeys as $coursekey) {
            if (isset($mapping[$coursekey])) {
                return $mapping[$coursekey];
            }
        }

        return [];
    }

    /**
     * Return expected competency definitions for a course.
     *
     * @param string|stdClass $course Course shortname, idnumber, or Moodle course record.
     * @return array<int, array<string, mixed>>
     */
    public static function get_expected_definitions_for_course($course): array {
        $definitions = [];

        foreach (self::get_expected_for_course($course) as $idnumber) {
            $definition = self::get_definition($idnumber);

            if ($definition !== null) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * Find the Moodle competency framework for UCKK.
     *
     * @return stdClass|null
     * @throws dml_exception
     */
    public static function find_framework_record(): ?stdClass {
        global $DB;

        if (!$DB->get_manager()->table_exists('competency_framework')) {
            return null;
        }

        $record = $DB->get_record(
            'competency_framework',
            ['idnumber' => self::FRAMEWORK_IDNUMBER],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Find a Moodle competency record by UCKK idnumber.
     *
     * @param string $idnumber Competency idnumber.
     * @return stdClass|null
     * @throws dml_exception
     */
    public static function find_competency_record(string $idnumber): ?stdClass {
        global $DB;

        if (!$DB->get_manager()->table_exists('competency')) {
            return null;
        }

        $idnumber = self::normalise_idnumber($idnumber);

        $record = $DB->get_record(
            'competency',
            ['idnumber' => $idnumber],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Find all Moodle competency records belonging to the UCKK framework.
     *
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function find_competency_records(): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('competency')) {
            return [];
        }

        $framework = self::find_framework_record();

        if ($framework === null) {
            return [];
        }

        return $DB->get_records(
            'competency',
            ['competencyframeworkid' => $framework->id],
            'sortorder ASC, idnumber ASC'
        );
    }

    /**
     * Return whether the UCKK framework exists in Moodle.
     *
     * @return bool
     */
    public static function framework_exists(): bool {
        return self::find_framework_record() !== null;
    }

    /**
     * Return canonical definitions with current Moodle record status.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function export_canonical_status(): array {
        $output = [];

        foreach (self::get_competency_definitions() as $idnumber => $definition) {
            $record = self::find_competency_record($idnumber);

            $output[] = [
                'idnumber' => $idnumber,
                'shortname' => $definition['shortname'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'sortorder' => $definition['sortorder'],
                'exists' => $record !== null,
                'moodleid' => $record->id ?? null,
                'frameworkid' => $record->competencyframeworkid ?? null,
            ];
        }

        return $output;
    }

    /**
     * Build a Moodle framework record for creation.
     *
     * This method does not create anything. It returns the intended record shape
     * for tool_uckkseed or an administrative service.
     *
     * @param int|null $scaleid Moodle scale id.
     * @param int|null $contextid Context id.
     * @return stdClass
     */
    public static function build_framework_record(?int $scaleid = null, ?int $contextid = null): stdClass {
        $definition = self::get_framework_definition();

        $record = new stdClass();
        $record->shortname = $definition['shortname'];
        $record->idnumber = $definition['idnumber'];
        $record->description = $definition['description'];
        $record->descriptionformat = $definition['descriptionformat'];
        $record->visible = $definition['visible'];
        $record->contextid = $contextid ?? context_system::instance()->id;

        if ($scaleid !== null) {
            $record->scaleid = $scaleid;
        }

        if (isset($definition['taxonomies'])) {
            $record->taxonomies = $definition['taxonomies'];
        }

        return $record;
    }

    /**
     * Build a Moodle competency record for creation.
     *
     * This method does not create anything. It returns the intended record shape
     * for tool_uckkseed or an administrative service.
     *
     * @param string $idnumber Competency idnumber.
     * @param int $frameworkid Moodle competency framework id.
     * @param int|null $parentid Optional parent competency id.
     * @return stdClass
     */
    public static function build_competency_record(string $idnumber, int $frameworkid, ?int $parentid = null): stdClass {
        $definition = self::get_definition($idnumber);

        if ($definition === null) {
            throw new coding_exception('Unknown UCKK competency idnumber: ' . $idnumber);
        }

        $record = new stdClass();
        $record->competencyframeworkid = $frameworkid;
        $record->shortname = $definition['shortname'];
        $record->idnumber = $definition['idnumber'];
        $record->description = $definition['description'];
        $record->descriptionformat = FORMAT_HTML;
        $record->sortorder = $definition['sortorder'];

        if ($parentid !== null) {
            $record->parentid = $parentid;
        }

        return $record;
    }

    /**
     * Create the UCKK competency framework using Moodle's Competency API.
     *
     * This method intentionally delegates to Moodle's API and refuses to insert
     * competency records directly.
     *
     * @param int|null $scaleid Moodle scale id.
     * @return mixed
     */
    public static function create_framework(?int $scaleid = null) {
        self::require_competency_api_method('create_framework');

        $existing = self::find_framework_record();
        if ($existing !== null) {
            return $existing;
        }

        $record = self::build_framework_record($scaleid);

        return \core_competency\api::create_framework($record);
    }

    /**
     * Create one UCKK competency using Moodle's Competency API.
     *
     * @param string $idnumber Competency idnumber.
     * @param int|null $frameworkid Moodle framework id. Defaults to existing UCKK framework.
     * @return mixed
     */
    public static function create_competency(string $idnumber, ?int $frameworkid = null) {
        self::require_competency_api_method('create_competency');

        $existing = self::find_competency_record($idnumber);
        if ($existing !== null) {
            return $existing;
        }

        if ($frameworkid === null) {
            $framework = self::find_framework_record();

            if ($framework === null) {
                throw new coding_exception('UCKK competency framework does not exist.');
            }

            $frameworkid = (int)$framework->id;
        }

        $record = self::build_competency_record($idnumber, $frameworkid);

        return \core_competency\api::create_competency($record);
    }

    /**
     * Create all canonical UCKK competencies using Moodle's Competency API.
     *
     * @param int|null $frameworkid Moodle framework id.
     * @return array<string, mixed>
     */
    public static function create_all_competencies(?int $frameworkid = null): array {
        $created = [];

        foreach (self::get_idnumbers() as $idnumber) {
            $created[$idnumber] = self::create_competency($idnumber, $frameworkid);
        }

        return $created;
    }

    /**
     * Link a competency to a course using Moodle's Competency API.
     *
     * @param int $courseid Course id.
     * @param string|int $competency Competency idnumber or Moodle competency id.
     * @param int $ruleoutcome Moodle rule outcome.
     * @return mixed
     */
    public static function add_competency_to_course(int $courseid, $competency, int $ruleoutcome = 0) {
        self::require_competency_api_method('add_competency_to_course');

        $competencyid = self::resolve_competency_id($competency);

        if ($competencyid === null) {
            throw new coding_exception('Cannot resolve competency: ' . (string)$competency);
        }

        return \core_competency\api::add_competency_to_course($courseid, $competencyid, $ruleoutcome);
    }

    /**
     * Link canonical tronc commun competencies to a course.
     *
     * @param stdClass $course Moodle course record.
     * @return array<string, mixed>
     */
    public static function add_expected_competencies_to_course(stdClass $course): array {
        $results = [];

        if (empty($course->id)) {
            throw new coding_exception('Course record must include id.');
        }

        foreach (self::get_expected_for_course($course) as $idnumber) {
            $results[$idnumber] = self::add_competency_to_course((int)$course->id, $idnumber);
        }

        return $results;
    }

    /**
     * Link a competency to a course module using Moodle's Competency API.
     *
     * @param int $cmid Course module id.
     * @param string|int $competency Competency idnumber or Moodle competency id.
     * @return mixed
     */
    public static function add_competency_to_module(int $cmid, $competency) {
        self::require_competency_api_method('add_competency_to_course_module');

        $competencyid = self::resolve_competency_id($competency);

        if ($competencyid === null) {
            throw new coding_exception('Cannot resolve competency: ' . (string)$competency);
        }

        return \core_competency\api::add_competency_to_course_module($cmid, $competencyid);
    }

    /**
     * Add evidence for a user competency if Moodle's API supports it.
     *
     * This method passes only minimal, explicit data. It does not grade, award
     * badges, validate archives or close integrity cases.
     *
     * @param int $userid User id.
     * @param string|int $competency Competency idnumber or Moodle competency id.
     * @param context $context Evidence context.
     * @param string $action Action identifier.
     * @param string $descriptionidentifier Language string identifier or plain key.
     * @param string $descriptioncomponent Language component.
     * @param mixed $descriptiona Optional description data.
     * @param moodle_url|null $url Optional evidence URL.
     * @param int|null $grade Optional grade/rating.
     * @param string|null $note Optional note.
     * @return mixed
     */
    public static function add_evidence(
        int $userid,
        $competency,
        context $context,
        string $action,
        string $descriptionidentifier,
        string $descriptioncomponent = self::COMPONENT,
        $descriptiona = null,
        ?moodle_url $url = null,
        ?int $grade = null,
        ?string $note = null
    ) {
        self::require_competency_api_method('add_evidence');

        $competencyid = self::resolve_competency_id($competency);

        if ($competencyid === null) {
            throw new coding_exception('Cannot resolve competency: ' . (string)$competency);
        }

        return \core_competency\api::add_evidence(
            $userid,
            $competencyid,
            $context,
            $action,
            $descriptionidentifier,
            $descriptioncomponent,
            $descriptiona,
            $url,
            $grade,
            $note
        );
    }

    /**
     * Build an evidence descriptor without writing to Moodle.
     *
     * Useful for archive, challenge, assembly and integrity plugins before
     * calling add_evidence().
     *
     * @param int $userid User id.
     * @param string $competencyidnumber UCKK competency idnumber.
     * @param string $sourcecomponent Origin component.
     * @param string $sourcetype Source type.
     * @param int|string $sourceid Source id.
     * @param string $summary Evidence summary.
     * @param int|null $rating Optional UCKK 0–5 rating.
     * @param string|null $url Optional evidence URL.
     * @return array<string, mixed>
     */
    public static function build_evidence_descriptor(
        int $userid,
        string $competencyidnumber,
        string $sourcecomponent,
        string $sourcetype,
        $sourceid,
        string $summary,
        ?int $rating = null,
        ?string $url = null
    ): array {
        $competencyidnumber = self::normalise_idnumber($competencyidnumber);

        if (!self::is_canonical_competency($competencyidnumber)) {
            throw new coding_exception('Unknown UCKK competency idnumber: ' . $competencyidnumber);
        }

        return [
            'userid' => $userid,
            'competencyidnumber' => $competencyidnumber,
            'competency' => self::get_definition($competencyidnumber),
            'sourcecomponent' => $sourcecomponent,
            'sourcetype' => local_uckk_normalise_key($sourcetype),
            'sourceid' => (string)$sourceid,
            'summary' => $summary,
            'rating' => $rating,
            'hasrating' => $rating !== null,
            'demonstrated' => $rating !== null && $rating >= self::RATING_DEMONSTRATED,
            'publicquality' => $rating !== null && $rating >= self::RATING_PUBLIC_QUALITY,
            'archivedkristal' => $rating !== null && $rating >= self::RATING_ARCHIVED_KRISTAL,
            'url' => $url ?? '',
            'hasurl' => !empty($url),
            'provenancehash' => local_uckk_build_provenance_hash(
                $sourcecomponent,
                $sourcetype,
                $sourceid,
                $competencyidnumber
            ),
        ];
    }

    /**
     * Export the competency framework and all competencies for templates.
     *
     * @return array<string, mixed>
     */
    public static function export_framework_for_template(): array {
        $framework = self::find_framework_record();
        $definitions = [];

        foreach (self::export_canonical_status() as $definition) {
            $definitions[] = [
                'idnumber' => $definition['idnumber'],
                'shortname' => $definition['shortname'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'sortorder' => $definition['sortorder'],
                'exists' => $definition['exists'],
                'moodleid' => $definition['moodleid'],
                'hasmoodleid' => !empty($definition['moodleid']),
            ];
        }

        return [
            'idnumber' => self::FRAMEWORK_IDNUMBER,
            'shortname' => self::FRAMEWORK_SHORTNAME,
            'description' => self::FRAMEWORK_DESCRIPTION,
            'exists' => $framework !== null,
            'moodleid' => $framework->id ?? null,
            'hasmoodleid' => $framework !== null,
            'competencies' => $definitions,
            'hascompetencies' => !empty($definitions),
            'scale' => array_values(self::get_scale_definition()),
        ];
    }

    /**
     * Export expected competency data for a course template.
     *
     * @param string|stdClass $course Course shortname, idnumber, or Moodle course record.
     * @return array<string, mixed>
     */
    public static function export_course_competencies_for_template($course): array {
        $coursekeys = self::extract_course_keys($course);
        $competencies = [];

        foreach (self::get_expected_definitions_for_course($course) as $definition) {
            $record = self::find_competency_record($definition['idnumber']);

            $competencies[] = [
                'idnumber' => $definition['idnumber'],
                'shortname' => $definition['shortname'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'exists' => $record !== null,
                'moodleid' => $record->id ?? null,
                'hasmoodleid' => $record !== null,
            ];
        }

        return [
            'coursekeys' => $coursekeys,
            'competencies' => $competencies,
            'hascompetencies' => !empty($competencies),
        ];
    }

    /**
     * Resolve a competency idnumber or Moodle id to a Moodle competency id.
     *
     * @param string|int $competency Competency idnumber or id.
     * @return int|null
     */
    public static function resolve_competency_id($competency): ?int {
        if (is_int($competency) || ctype_digit((string)$competency)) {
            return (int)$competency;
        }

        $record = self::find_competency_record((string)$competency);

        if ($record === null) {
            return null;
        }

        return (int)$record->id;
    }

    /**
     * Return whether Moodle's competency subsystem appears available.
     *
     * @return bool
     */
    public static function competency_subsystem_available(): bool {
        return class_exists('\\core_competency\\api');
    }

    /**
     * Return whether a Moodle competency API method exists.
     *
     * @param string $method Method name.
     * @return bool
     */
    public static function competency_api_method_exists(string $method): bool {
        return self::competency_subsystem_available() && method_exists('\\core_competency\\api', $method);
    }

    /**
     * Require Moodle's competency API method.
     *
     * @param string $method Method name.
     * @return void
     */
    private static function require_competency_api_method(string $method): void {
        if (!self::competency_api_method_exists($method)) {
            throw new coding_exception('Moodle Competency API method is not available: ' . $method);
        }
    }

    /**
     * Extract stable course keys from a string or course record.
     *
     * @param string|stdClass $course Course shortname, idnumber, or Moodle course record.
     * @return string[]
     */
    private static function extract_course_keys($course): array {
        if (is_string($course)) {
            return [self::normalise_course_code($course)];
        }

        $keys = [];

        if (isset($course->shortname) && $course->shortname !== '') {
            $keys[] = self::normalise_course_code((string)$course->shortname);
        }

        if (isset($course->idnumber) && $course->idnumber !== '') {
            $keys[] = self::normalise_course_code((string)$course->idnumber);
        }

        if (isset($course->fullname) && $course->fullname !== '') {
            if (preg_match('/UCKK-TC[0-9]{3}/i', (string)$course->fullname, $matches)) {
                $keys[] = self::normalise_course_code($matches[0]);
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Normalise a UCKK competency idnumber.
     *
     * @param string $idnumber Raw idnumber.
     * @return string
     */
    private static function normalise_idnumber(string $idnumber): string {
        $idnumber = trim($idnumber);
        $idnumber = str_replace('_', '-', $idnumber);
        $idnumber = strtoupper($idnumber);

        return $idnumber;
    }

    /**
     * Normalise a course code.
     *
     * @param string $coursecode Raw course code.
     * @return string
     */
    private static function normalise_course_code(string $coursecode): string {
        $coursecode = trim($coursecode);
        $coursecode = strtoupper($coursecode);

        if (preg_match('/UCKK-TC[0-9]{3}/', $coursecode, $matches)) {
            return $matches[0];
        }

        return $coursecode;
    }

    /**
     * Build a context object from a source component and id when possible.
     *
     * This is intentionally conservative and only handles common contexts.
     *
     * @param string $contexttype Context type: system, course, module, user.
     * @param int|null $instanceid Instance id.
     * @return context
     */
    public static function resolve_context(string $contexttype = 'system', ?int $instanceid = null): context {
        $contexttype = local_uckk_normalise_key($contexttype);

        switch ($contexttype) {
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
}