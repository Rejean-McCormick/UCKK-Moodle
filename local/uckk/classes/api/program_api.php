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
 * Program API for local_uckk.
 *
 * This class provides the internal service layer for UCKK programs:
 * tronc commun, baccalauréats, mineures, seminars and laboratories.
 *
 * It is intentionally not an external web service class. External functions
 * should live in local_uckk\external and may call this API after parameter
 * validation, context validation and capability checks.
 *
 * This API does not create Moodle courses, cohorts, competencies or badges.
 * It manages the UCKK program registry only. Seeders and other services may
 * then use these records to create Moodle objects.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use context;
use context_coursecat;
use context_system;
use core_component;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK program registry API.
 *
 * @package local_uckk
 */
final class program_api {
    /** Main program table. */
    public const TABLE = 'local_uckk_program';

    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Capability required to view the campus registry. */
    public const CAP_VIEW = 'local/uckk:viewcampus';

    /** Capability required to manage programs. */
    public const CAP_MANAGE = 'local/uckk:manageprograms';

    /** Program type: tronc commun. */
    public const TYPE_TRONC_COMMUN = 'tronc_commun';

    /** Program type: baccalauréat. */
    public const TYPE_BACCALAUREAT = 'baccalaureat';

    /** Program type: mineure. */
    public const TYPE_MINEURE = 'mineure';

    /** Program type: séminaires avancés. */
    public const TYPE_SEMINAIRE = 'seminaire';

    /** Program type: laboratory. */
    public const TYPE_LABORATOIRE = 'laboratoire';

    /** Program status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Program status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Program status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Program status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * Return allowed program types.
     *
     * @return string[]
     */
    public static function get_allowed_types(): array {
        return [
            self::TYPE_TRONC_COMMUN,
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_SEMINAIRE,
            self::TYPE_LABORATOIRE,
        ];
    }

    /**
     * Return allowed statuses.
     *
     * @return string[]
     */
    public static function get_allowed_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Return allowed visibility values.
     *
     * @return string[]
     */
    public static function get_allowed_visibilities(): array {
        return [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
        ];
    }

    /**
     * Return default UCKK programs.
     *
     * These records are used by tool_uckkseed and tests. They are canonical
     * seed definitions, not Moodle course records and not public accreditation
     * claims.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_default_programs(): array {
        return [
            [
                'shortname' => 'tronc_commun',
                'idnumber' => 'UCKK-PROG-TC',
                'fullname' => 'Tronc commun obligatoire',
                'programtype' => self::TYPE_TRONC_COMMUN,
                'summary' => 'Base commune UCKK : cartographie, preuve, IA non souveraine, délibération, mobilisation responsable, mémoire et intégrité.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 10,
            ],
            [
                'shortname' => 'grand_jeu_social',
                'idnumber' => 'UCKK-PROG-GJS',
                'fullname' => 'Baccalauréat du Grand Jeu social',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne consacré à la lecture du Grand Jeu social, ses institutions, ses récits, ses plateformes, ses pouvoirs et ses failles.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 20,
            ],
            [
                'shortname' => 'architecture_ecosysteme_digital_koa',
                'idnumber' => 'UCKK-PROG-KOA-DIGITAL',
                'fullname' => 'Voie de l’Architecture de l’écosystème digital kOA',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne pour comprendre, déployer, auditer et gouverner le kOA Digital Ecosystem.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 30,
            ],
            [
                'shortname' => 'architecture_sociotechnique',
                'idnumber' => 'UCKK-PROG-SOCIOTECH',
                'fullname' => 'Voie de l’Architecture sociotechnique',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur les systèmes combinant humains, technologies, institutions, données, règles, rôles, permissions, workflows et mémoire.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 40,
            ],
            [
                'shortname' => 'sciences_politiques',
                'idnumber' => 'UCKK-PROG-POL',
                'fullname' => 'Voie des Sciences politiques',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur le pouvoir, les institutions, les assemblées, les lois, les partis, les mouvements, les votes et la légitimité.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 50,
            ],
            [
                'shortname' => 'economie',
                'idnumber' => 'UCKK-PROG-ECO',
                'fullname' => 'Voie de l’Économie',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur l’argent comme règle du jeu : ressources, incitatifs, marchés, travail, valeur, extraction et redistribution.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 60,
            ],
            [
                'shortname' => 'ecologie',
                'idnumber' => 'UCKK-PROG-ECOLOGIE',
                'fullname' => 'Voie de l’Écologie',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur les systèmes vivants, territoires, ressources, crises climatiques et liens entre société et environnement.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 70,
            ],
            [
                'shortname' => 'metaphysique',
                'idnumber' => 'UCKK-PROG-META',
                'fullname' => 'Voie de la Métaphysique',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne d’interprétation, de discernement et de méthode sur vérité, sens, conscience, liberté, pouvoir, ordre, chaos et croyance.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 80,
            ],
            [
                'shortname' => 'intelligence_artificielle_gouvernable',
                'idnumber' => 'UCKK-PROG-IA',
                'fullname' => 'Voie de l’Intelligence artificielle gouvernable',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur l’IA comme outil de lecture, création, cartographie, simulation et accélération, jamais comme autorité finale.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 90,
            ],
            [
                'shortname' => 'linguistique_architecture_du_sens',
                'idnumber' => 'UCKK-PROG-LING',
                'fullname' => 'Voie de la Linguistique et de l’architecture du sens',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur les langues comme infrastructures du monde social : mots, concepts, traductions, catégories, récits et pouvoir symbolique.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 100,
            ],
            [
                'shortname' => 'intervention_sociale_systemes_humains',
                'idnumber' => 'UCKK-PROG-ISH',
                'fullname' => 'Voie de l’Intervention sociale et des systèmes humains',
                'programtype' => self::TYPE_BACCALAUREAT,
                'summary' => 'Programme interne sur les humains dans les systèmes : vulnérabilité, exclusion, trauma, pauvreté, communauté, institutions, aide, dignité et réparation.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 110,
            ],
            [
                'shortname' => 'medias_vivants_theatre_public_responsable',
                'idnumber' => 'UCKK-PROG-MV',
                'fullname' => 'Mineure Médias vivants et théâtre public responsable',
                'programtype' => self::TYPE_MINEURE,
                'summary' => 'Mineure interne sur la scène, le récit, la satire, les médias sociaux, la diffusion, les défis publics et les limites éthiques de la performance.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 120,
            ],
            [
                'shortname' => 'seminaires_avances_laboratoires',
                'idnumber' => 'UCKK-PROG-LABS',
                'fullname' => 'Séminaires avancés et laboratoires',
                'programtype' => self::TYPE_LABORATOIRE,
                'summary' => 'Espace interne pour les séminaires avancés, laboratoires, prototypes, synthèses, audits, recherches appliquées et projets expérimentaux.',
                'status' => self::STATUS_ACTIVE,
                'visibility' => self::VISIBILITY_INSTITUTION,
                'sortorder' => 130,
            ],
        ];
    }

    /**
     * Return all programs visible to the current user.
     *
     * @param array<string, mixed> $filters Optional filters.
     * @param context|null $context Optional context.
     * @return stdClass[]
     */
    public static function get_programs(array $filters = [], ?context $context = null): array {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_view($context);

        [$where, $params] = self::build_filter_sql($filters);

        $sql = "SELECT *
                  FROM {" . self::TABLE . "}
                 $where
              ORDER BY sortorder ASC, fullname ASC";

        $records = $DB->get_records_sql($sql, $params);

        return array_map([self::class, 'export_program'], array_values($records));
    }

    /**
     * Count programs matching filters.
     *
     * @param array<string, mixed> $filters Optional filters.
     * @param context|null $context Optional context.
     * @return int
     */
    public static function count_programs(array $filters = [], ?context $context = null): int {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_view($context);

        [$where, $params] = self::build_filter_sql($filters);

        return (int)$DB->count_records_sql("SELECT COUNT(1) FROM {" . self::TABLE . "} $where", $params);
    }

    /**
     * Get a program by id.
     *
     * @param int $id Program id.
     * @param context|null $context Optional context.
     * @param int $strictness IGNORE_MISSING or MUST_EXIST.
     * @return stdClass|null
     */
    public static function get_program(int $id, ?context $context = null, int $strictness = MUST_EXIST): ?stdClass {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_view($context);

        if ($id <= 0) {
            throw new invalid_parameter_exception('Invalid UCKK program id.');
        }

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', $strictness);

        if (!$record) {
            return null;
        }

        return self::export_program($record);
    }

    /**
     * Get a program by shortname.
     *
     * @param string $shortname Program shortname.
     * @param context|null $context Optional context.
     * @param int $strictness IGNORE_MISSING or MUST_EXIST.
     * @return stdClass|null
     */
    public static function get_program_by_shortname(
        string $shortname,
        ?context $context = null,
        int $strictness = MUST_EXIST
    ): ?stdClass {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_view($context);

        $shortname = self::normalise_shortname($shortname);

        if ($shortname === '') {
            throw new invalid_parameter_exception('Invalid UCKK program shortname.');
        }

        $record = $DB->get_record(self::TABLE, ['shortname' => $shortname], '*', $strictness);

        if (!$record) {
            return null;
        }

        return self::export_program($record);
    }

    /**
     * Determine whether a program exists by shortname.
     *
     * @param string $shortname Program shortname.
     * @return bool
     */
    public static function exists(string $shortname): bool {
        global $DB;

        $shortname = self::normalise_shortname($shortname);

        if ($shortname === '') {
            return false;
        }

        return $DB->record_exists(self::TABLE, ['shortname' => $shortname]);
    }

    /**
     * Create a program.
     *
     * @param array<string, mixed>|stdClass $data Program data.
     * @param context|null $context Optional context.
     * @return stdClass Created program.
     */
    public static function create_program($data, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage($context);

        $record = self::prepare_record($data, false);

        if ($DB->record_exists(self::TABLE, ['shortname' => $record->shortname])) {
            throw new moodle_exception('programshortnameexists', self::COMPONENT, '', $record->shortname);
        }

        if (!empty($record->idnumber) && $DB->record_exists(self::TABLE, ['idnumber' => $record->idnumber])) {
            throw new moodle_exception('programidnumberexists', self::COMPONENT, '', $record->idnumber);
        }

        $time = time();

        $record->timecreated = $time;
        $record->timemodified = $time;
        $record->createdby = $USER->id;
        $record->modifiedby = $USER->id;

        $record->id = $DB->insert_record(self::TABLE, $record);

        self::trigger_event('program_created', $record, $context);

        return self::export_program($DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST));
    }

    /**
     * Update a program.
     *
     * @param int $id Program id.
     * @param array<string, mixed>|stdClass $data Program data.
     * @param context|null $context Optional context.
     * @return stdClass Updated program.
     */
    public static function update_program(int $id, $data, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage($context);

        if ($id <= 0) {
            throw new invalid_parameter_exception('Invalid UCKK program id.');
        }

        $current = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $record = self::prepare_record($data, true, $current);

        $record->id = $id;
        $record->timecreated = $current->timecreated;
        $record->createdby = $current->createdby;
        $record->timemodified = time();
        $record->modifiedby = $USER->id;

        if ($record->shortname !== $current->shortname && $DB->record_exists(self::TABLE, ['shortname' => $record->shortname])) {
            throw new moodle_exception('programshortnameexists', self::COMPONENT, '', $record->shortname);
        }

        if (
            !empty($record->idnumber)
            && $record->idnumber !== $current->idnumber
            && $DB->record_exists(self::TABLE, ['idnumber' => $record->idnumber])
        ) {
            throw new moodle_exception('programidnumberexists', self::COMPONENT, '', $record->idnumber);
        }

        $DB->update_record(self::TABLE, $record);

        self::trigger_event('program_updated', $record, $context);

        return self::export_program($DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST));
    }

    /**
     * Archive a program.
     *
     * This is the preferred non-destructive removal path.
     *
     * @param int $id Program id.
     * @param context|null $context Optional context.
     * @return stdClass Updated program.
     */
    public static function archive_program(int $id, ?context $context = null): stdClass {
        return self::update_program($id, ['status' => self::STATUS_ARCHIVED], $context);
    }

    /**
     * Delete a program.
     *
     * Hard deletion is intentionally strict. It should be used only by seed
     * rollback, tests, or explicit administrator actions. Prefer archive_program.
     *
     * @param int $id Program id.
     * @param context|null $context Optional context.
     * @return bool
     */
    public static function delete_program(int $id, ?context $context = null): bool {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_manage($context);

        if ($id <= 0) {
            throw new invalid_parameter_exception('Invalid UCKK program id.');
        }

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);

        $deleted = $DB->delete_records(self::TABLE, ['id' => $id]);

        if ($deleted) {
            self::trigger_event('program_deleted', $record, $context);
        }

        return $deleted;
    }

    /**
     * Ensure a program exists, creating it if necessary and optionally updating it.
     *
     * @param array<string, mixed>|stdClass $data Program data.
     * @param bool $updateifexists Whether to update existing record.
     * @param context|null $context Optional context.
     * @return stdClass Program record.
     */
    public static function ensure_program($data, bool $updateifexists = true, ?context $context = null): stdClass {
        global $DB;

        $context = $context ?? context_system::instance();
        self::require_manage($context);

        $prepared = self::prepare_record($data, false);
        $existing = $DB->get_record(self::TABLE, ['shortname' => $prepared->shortname], '*', IGNORE_MISSING);

        if (!$existing) {
            return self::create_program($prepared, $context);
        }

        if ($updateifexists) {
            return self::update_program((int)$existing->id, $prepared, $context);
        }

        return self::export_program($existing);
    }

    /**
     * Seed the default UCKK program registry.
     *
     * @param bool $updateifexists Whether to update existing records.
     * @param context|null $context Optional context.
     * @return array<string, mixed>
     */
    public static function seed_default_programs(bool $updateifexists = true, ?context $context = null): array {
        $context = $context ?? context_system::instance();
        self::require_manage($context);

        $created = [];
        $updated = [];
        $unchanged = [];
        $errors = [];

        foreach (self::get_default_programs() as $program) {
            try {
                $exists = self::exists($program['shortname']);
                $record = self::ensure_program($program, $updateifexists, $context);

                if (!$exists) {
                    $created[] = $record->shortname;
                } else if ($updateifexists) {
                    $updated[] = $record->shortname;
                } else {
                    $unchanged[] = $record->shortname;
                }
            } catch (\Throwable $exception) {
                $errors[] = [
                    'shortname' => $program['shortname'] ?? '',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'errors' => $errors,
            'success' => empty($errors),
        ];
    }

    /**
     * Build a category context from a program category id, if available.
     *
     * @param stdClass $program Program record.
     * @return context
     */
    public static function get_program_context(stdClass $program): context {
        if (!empty($program->categoryid)) {
            try {
                return context_coursecat::instance((int)$program->categoryid);
            } catch (\Throwable $exception) {
                // Fall through to system context if the category does not exist.
            }
        }

        return context_system::instance();
    }

    /**
     * Export a program for output, external API wrappers, tests or seed reports.
     *
     * @param stdClass $record Raw DB record.
     * @return stdClass
     */
    public static function export_program(stdClass $record): stdClass {
        $program = new stdClass();

        $program->id = (int)$record->id;
        $program->shortname = (string)$record->shortname;
        $program->idnumber = (string)($record->idnumber ?? '');
        $program->fullname = format_string((string)$record->fullname);
        $program->rawfullname = (string)$record->fullname;
        $program->programtype = (string)$record->programtype;
        $program->summary = (string)($record->summary ?? '');
        $program->summaryformat = (int)($record->summaryformat ?? FORMAT_HTML);
        $program->categoryid = (int)($record->categoryid ?? 0);
        $program->status = (string)($record->status ?? self::STATUS_DRAFT);
        $program->visibility = (string)($record->visibility ?? self::VISIBILITY_INSTITUTION);
        $program->sortorder = (int)($record->sortorder ?? 0);
        $program->metadata = self::decode_json((string)($record->metadata ?? ''));
        $program->timecreated = (int)($record->timecreated ?? 0);
        $program->timemodified = (int)($record->timemodified ?? 0);
        $program->createdby = (int)($record->createdby ?? 0);
        $program->modifiedby = (int)($record->modifiedby ?? 0);

        $program->active = $program->status === self::STATUS_ACTIVE;
        $program->archived = $program->status === self::STATUS_ARCHIVED;
        $program->public = $program->visibility === self::VISIBILITY_PUBLIC;
        $program->internalnotice = get_string('program_internal_notice', self::COMPONENT);

        return $program;
    }

    /**
     * Prepare a database record.
     *
     * @param array<string, mixed>|stdClass $data Raw data.
     * @param bool $isupdate Whether this is an update.
     * @param stdClass|null $current Existing record for update defaults.
     * @return stdClass
     */
    private static function prepare_record($data, bool $isupdate = false, ?stdClass $current = null): stdClass {
        $data = (object)(array)$data;
        $record = new stdClass();

        $record->shortname = self::normalise_shortname($data->shortname ?? $current->shortname ?? '');
        $record->idnumber = self::normalise_idnumber($data->idnumber ?? $current->idnumber ?? '');
        $record->fullname = self::clean_required_text($data->fullname ?? $current->fullname ?? '', 'fullname');
        $record->programtype = self::clean_programtype($data->programtype ?? $current->programtype ?? self::TYPE_BACCALAUREAT);
        $record->summary = clean_param($data->summary ?? $current->summary ?? '', PARAM_RAW);
        $record->summaryformat = (int)($data->summaryformat ?? $current->summaryformat ?? FORMAT_HTML);
        $record->categoryid = (int)($data->categoryid ?? $current->categoryid ?? 0);
        $record->status = self::clean_status($data->status ?? $current->status ?? self::STATUS_DRAFT);
        $record->visibility = self::clean_visibility($data->visibility ?? $current->visibility ?? self::VISIBILITY_INSTITUTION);
        $record->sortorder = (int)($data->sortorder ?? $current->sortorder ?? 0);
        $record->metadata = self::encode_json($data->metadata ?? $current->metadata ?? []);

        if ($record->shortname === '') {
            throw new invalid_parameter_exception('Program shortname is required.');
        }

        if ($record->fullname === '') {
            throw new invalid_parameter_exception('Program fullname is required.');
        }

        if ($record->categoryid < 0) {
            throw new invalid_parameter_exception('Invalid program category id.');
        }

        if ($record->summaryformat <= 0) {
            $record->summaryformat = FORMAT_HTML;
        }

        return $record;
    }

    /**
     * Build SQL filter clauses.
     *
     * @param array<string, mixed> $filters Filters.
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function build_filter_sql(array $filters): array {
        $where = [];
        $params = [];

        if (!empty($filters['programtype'])) {
            $where[] = 'programtype = :programtype';
            $params['programtype'] = self::clean_programtype($filters['programtype']);
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = self::clean_status($filters['status']);
        }

        if (!empty($filters['visibility'])) {
            $where[] = 'visibility = :visibility';
            $params['visibility'] = self::clean_visibility($filters['visibility']);
        }

        if (!empty($filters['categoryid'])) {
            $where[] = 'categoryid = :categoryid';
            $params['categoryid'] = (int)$filters['categoryid'];
        }

        if (!empty($filters['shortname'])) {
            $where[] = 'shortname = :shortname';
            $params['shortname'] = self::normalise_shortname($filters['shortname']);
        }

        if (!empty($filters['search'])) {
            $where[] = '(fullname LIKE :searchfullname OR shortname LIKE :searchshortname OR idnumber LIKE :searchidnumber)';
            $search = '%' . clean_param($filters['search'], PARAM_TEXT) . '%';
            $params['searchfullname'] = $search;
            $params['searchshortname'] = $search;
            $params['searchidnumber'] = $search;
        }

        if (empty($where)) {
            return ['', []];
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    /**
     * Require view capability.
     *
     * @param context $context Context.
     * @return void
     */
    private static function require_view(context $context): void {
        if (has_capability(self::CAP_VIEW, $context) || has_capability(self::CAP_MANAGE, $context)) {
            return;
        }

        require_capability(self::CAP_VIEW, $context);
    }

    /**
     * Require manage capability.
     *
     * @param context $context Context.
     * @return void
     */
    private static function require_manage(context $context): void {
        require_capability(self::CAP_MANAGE, $context);
    }

    /**
     * Clean required text.
     *
     * @param mixed $value Raw value.
     * @param string $field Field name.
     * @return string
     */
    private static function clean_required_text($value, string $field): string {
        $value = trim(clean_param((string)$value, PARAM_TEXT));

        if ($value === '') {
            throw new invalid_parameter_exception("Missing required field: {$field}");
        }

        return $value;
    }

    /**
     * Normalise shortname.
     *
     * @param mixed $shortname Shortname.
     * @return string
     */
    private static function normalise_shortname($shortname): string {
        $shortname = trim((string)$shortname);
        $shortname = \core_text::strtolower($shortname);
        $shortname = str_replace([' ', '-', '.'], '_', $shortname);
        $shortname = clean_param($shortname, PARAM_ALPHANUMEXT);
        $shortname = preg_replace('/_+/', '_', $shortname) ?? '';
        $shortname = trim($shortname, '_');

        return $shortname;
    }

    /**
     * Normalise idnumber.
     *
     * @param mixed $idnumber Idnumber.
     * @return string
     */
    private static function normalise_idnumber($idnumber): string {
        return trim(clean_param((string)$idnumber, PARAM_TEXT));
    }

    /**
     * Clean program type.
     *
     * @param mixed $programtype Program type.
     * @return string
     */
    private static function clean_programtype($programtype): string {
        $programtype = self::normalise_shortname($programtype);

        if (!in_array($programtype, self::get_allowed_types(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program type.');
        }

        return $programtype;
    }

    /**
     * Clean status.
     *
     * @param mixed $status Status.
     * @return string
     */
    private static function clean_status($status): string {
        $status = self::normalise_shortname($status);

        if (!in_array($status, self::get_allowed_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program status.');
        }

        return $status;
    }

    /**
     * Clean visibility.
     *
     * @param mixed $visibility Visibility.
     * @return string
     */
    private static function clean_visibility($visibility): string {
        $visibility = self::normalise_shortname($visibility);

        if (!in_array($visibility, self::get_allowed_visibilities(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program visibility.');
        }

        return $visibility;
    }

    /**
     * Encode metadata.
     *
     * @param mixed $metadata Metadata.
     * @return string
     */
    private static function encode_json($metadata): string {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $metadata = $decoded;
            } else {
                $metadata = [];
            }
        }

        if (!is_array($metadata) && !is_object($metadata)) {
            $metadata = [];
        }

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Decode JSON metadata.
     *
     * @param string $json JSON string.
     * @return array<string, mixed>
     */
    private static function decode_json(string $json): array {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Trigger a local_uckk event if its event class exists.
     *
     * This keeps the API safe while event classes are generated one by one.
     *
     * @param string $eventname Event name without namespace.
     * @param stdClass $record Program record.
     * @param context $context Context.
     * @return void
     */
    private static function trigger_event(string $eventname, stdClass $record, context $context): void {
        $classname = '\\local_uckk\\event\\' . $eventname;

        if (!class_exists($classname) || !method_exists($classname, 'create')) {
            return;
        }

        $event = $classname::create([
            'context' => $context,
            'objectid' => $record->id,
            'other' => [
                'shortname' => $record->shortname,
                'programtype' => $record->programtype,
                'status' => $record->status,
            ],
        ]);

        $event->trigger();
    }
}
