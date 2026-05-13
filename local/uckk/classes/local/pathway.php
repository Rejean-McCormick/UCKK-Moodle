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
 * Local pathway model for local_uckk.
 *
 * A pathway is an internal UCKK learning route. It connects a program to
 * courses, competencies, badges, portfolio expectations, archive requirements
 * and recognition rules.
 *
 * This class is a data model / value object. It must not:
 *
 * - write to the database;
 * - check Moodle capabilities;
 * - award badges;
 * - mark competencies complete;
 * - validate archives;
 * - open or close integrity cases;
 * - render HTML.
 *
 * Those responsibilities belong to APIs, Moodle subsystems, activities,
 * tools, reports or renderers.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK pathway value object.
 *
 * @package local_uckk
 */
final class pathway {
    /** Database table expected for pathways. */
    public const TABLE = 'local_uckk_pathway';

    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Pathway status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Pathway status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Pathway status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Pathway status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Pathway type: tronc commun. */
    public const TYPE_TRONC_COMMUN = 'tronc_commun';

    /** Pathway type: baccalauréat. */
    public const TYPE_BACCALAUREAT = 'baccalaureat';

    /** Pathway type: mineure. */
    public const TYPE_MINEURE = 'mineure';

    /** Pathway type: séminaire. */
    public const TYPE_SEMINAIRE = 'seminaire';

    /** Pathway type: laboratoire. */
    public const TYPE_LABORATOIRE = 'laboratoire';

    /** Recognition type: internal only. */
    public const RECOGNITION_INTERNAL = 'internal';

    /** Recognition type: portfolio based. */
    public const RECOGNITION_PORTFOLIO = 'portfolio';

    /** Recognition type: badge based. */
    public const RECOGNITION_BADGE = 'badge';

    /** Recognition type: archive based. */
    public const RECOGNITION_ARCHIVE = 'archive';

    /** Completion status: not started. */
    public const PROGRESS_NOT_STARTED = 'not_started';

    /** Completion status: in progress. */
    public const PROGRESS_IN_PROGRESS = 'in_progress';

    /** Completion status: pending review. */
    public const PROGRESS_PENDING_REVIEW = 'pending_review';

    /** Completion status: completed. */
    public const PROGRESS_COMPLETED = 'completed';

    /** Completion status: blocked. */
    public const PROGRESS_BLOCKED = 'blocked';

    /** @var int Pathway id. */
    private int $id = 0;

    /** @var int Related program id. */
    private int $programid = 0;

    /** @var string Stable shortname. */
    private string $shortname = '';

    /** @var string Optional idnumber. */
    private string $idnumber = '';

    /** @var string Display name. */
    private string $fullname = '';

    /** @var string Pathway type. */
    private string $pathwaytype = self::TYPE_BACCALAUREAT;

    /** @var string Description text. */
    private string $description = '';

    /** @var int Description format. */
    private int $descriptionformat = FORMAT_HTML;

    /** @var string Status. */
    private string $status = self::STATUS_DRAFT;

    /** @var string Visibility. */
    private string $visibility = self::VISIBILITY_INSTITUTION;

    /** @var int Sort order. */
    private int $sortorder = 0;

    /** @var array<int, int> Required Moodle course ids. */
    private array $requiredcourseids = [];

    /** @var array<int, string> Required course shortnames or idnumbers. */
    private array $requiredcourses = [];

    /** @var array<int, string> Required competency identifiers. */
    private array $requiredcompetencies = [];

    /** @var array<int, string> Required badge identifiers. */
    private array $requiredbadges = [];

    /** @var array<int, string> Required archive item types. */
    private array $requiredarchives = [];

    /** @var array<int, string> Required portfolio sections. */
    private array $requiredportfolioitems = [];

    /** @var array<int, string> Required challenge identifiers. */
    private array $requiredchallenges = [];

    /** @var bool Whether portfolio is required. */
    private bool $portfoliorequired = true;

    /** @var bool Whether archive output is required. */
    private bool $archiverequired = true;

    /** @var bool Whether integrity clearance is required. */
    private bool $integrityclearancerequired = true;

    /** @var string Recognition type. */
    private string $recognitiontype = self::RECOGNITION_INTERNAL;

    /** @var string Internal recognition label. */
    private string $recognitionlabel = '';

    /** @var string Internal notice. */
    private string $internalnotice = '';

    /** @var array<string, mixed> Flexible metadata. */
    private array $metadata = [];

    /** @var int Created timestamp. */
    private int $timecreated = 0;

    /** @var int Modified timestamp. */
    private int $timemodified = 0;

    /** @var int Created by user id. */
    private int $createdby = 0;

    /** @var int Modified by user id. */
    private int $modifiedby = 0;

    /**
     * Private constructor.
     *
     * Use from_record(), from_data(), or create_empty().
     */
    private function __construct() {
    }

    /**
     * Create an empty pathway.
     *
     * @return self
     */
    public static function create_empty(): self {
        $pathway = new self();
        $pathway->internalnotice = self::get_default_internal_notice();

        return $pathway;
    }

    /**
     * Create a pathway from a database-like record.
     *
     * @param stdClass $record Raw pathway record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        $pathway = new self();

        $pathway->id = (int)($record->id ?? 0);
        $pathway->programid = (int)($record->programid ?? 0);
        $pathway->shortname = self::normalise_shortname($record->shortname ?? '');
        $pathway->idnumber = self::clean_text($record->idnumber ?? '');
        $pathway->fullname = self::clean_required_text($record->fullname ?? '', 'fullname');
        $pathway->pathwaytype = self::clean_pathway_type($record->pathwaytype ?? $record->programtype ?? self::TYPE_BACCALAUREAT);
        $pathway->description = self::clean_raw_text($record->description ?? $record->summary ?? '');
        $pathway->descriptionformat = (int)($record->descriptionformat ?? $record->summaryformat ?? FORMAT_HTML);
        $pathway->status = self::clean_status($record->status ?? self::STATUS_DRAFT);
        $pathway->visibility = self::clean_visibility($record->visibility ?? self::VISIBILITY_INSTITUTION);
        $pathway->sortorder = (int)($record->sortorder ?? 0);

        $pathway->requiredcourseids = self::normalise_int_list(
            self::decode_list($record->requiredcourseids ?? $record->courseids ?? [])
        );
        $pathway->requiredcourses = self::normalise_string_list(
            self::decode_list($record->requiredcourses ?? [])
        );
        $pathway->requiredcompetencies = self::normalise_string_list(
            self::decode_list($record->requiredcompetencies ?? [])
        );
        $pathway->requiredbadges = self::normalise_string_list(
            self::decode_list($record->requiredbadges ?? [])
        );
        $pathway->requiredarchives = self::normalise_string_list(
            self::decode_list($record->requiredarchives ?? [])
        );
        $pathway->requiredportfolioitems = self::normalise_string_list(
            self::decode_list($record->requiredportfolioitems ?? [])
        );
        $pathway->requiredchallenges = self::normalise_string_list(
            self::decode_list($record->requiredchallenges ?? [])
        );

        $pathway->portfoliorequired = self::clean_bool($record->portfoliorequired ?? true);
        $pathway->archiverequired = self::clean_bool($record->archiverequired ?? true);
        $pathway->integrityclearancerequired = self::clean_bool($record->integrityclearancerequired ?? true);

        $pathway->recognitiontype = self::clean_recognition_type($record->recognitiontype ?? self::RECOGNITION_INTERNAL);
        $pathway->recognitionlabel = self::clean_text($record->recognitionlabel ?? '');
        $pathway->internalnotice = self::clean_text($record->internalnotice ?? self::get_default_internal_notice());

        $pathway->metadata = self::decode_assoc($record->metadata ?? []);
        $pathway->timecreated = (int)($record->timecreated ?? 0);
        $pathway->timemodified = (int)($record->timemodified ?? 0);
        $pathway->createdby = (int)($record->createdby ?? 0);
        $pathway->modifiedby = (int)($record->modifiedby ?? 0);

        $pathway->validate();

        return $pathway;
    }

    /**
     * Create a pathway from raw form/API data.
     *
     * @param array<string, mixed>|stdClass $data Raw data.
     * @return self
     */
    public static function from_data($data): self {
        return self::from_record((object)(array)$data);
    }

    /**
     * Create a pathway from a seed definition.
     *
     * @param array<string, mixed>|stdClass $seed Seed data.
     * @return self
     */
    public static function from_seed($seed): self {
        $data = (array)$seed;

        if (empty($data['status'])) {
            $data['status'] = self::STATUS_ACTIVE;
        }

        if (empty($data['visibility'])) {
            $data['visibility'] = self::VISIBILITY_INSTITUTION;
        }

        if (empty($data['recognitiontype'])) {
            $data['recognitiontype'] = self::RECOGNITION_INTERNAL;
        }

        if (empty($data['internalnotice'])) {
            $data['internalnotice'] = self::get_default_internal_notice();
        }

        return self::from_data($data);
    }

    /**
     * Return default pathway definitions used by the seed tool.
     *
     * These are pathway records, not course records and not accreditation claims.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_default_pathways(): array {
        return [
            [
                'shortname' => 'tronc_commun_obligatoire',
                'idnumber' => 'UCKK-PATH-TC',
                'fullname' => 'Tronc commun obligatoire',
                'pathwaytype' => self::TYPE_TRONC_COMMUN,
                'description' => 'Parcours commun de base : cartographie, preuve, IA non souveraine, délibération, mobilisation responsable, mémoire et intégrité.',
                'requiredcourses' => [
                    'UCKK-TC101',
                    'UCKK-TC102',
                    'UCKK-TC103',
                    'UCKK-TC104',
                    'UCKK-TC105',
                    'UCKK-TC106',
                    'UCKK-TC107',
                    'UCKK-TC108',
                ],
                'requiredcompetencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-002',
                    'UCKK-COMP-003',
                    'UCKK-COMP-004',
                    'UCKK-COMP-005',
                    'UCKK-COMP-006',
                    'UCKK-COMP-007',
                    'UCKK-COMP-008',
                    'UCKK-COMP-009',
                    'UCKK-COMP-010',
                    'UCKK-COMP-013',
                    'UCKK-COMP-014',
                ],
                'requiredbadges' => [
                    'joueur_initie',
                    'joueur_lucide',
                    'gardien_preuve',
                    'participant_assemblee',
                ],
                'requiredportfolioitems' => [
                    'system_map',
                    'ai_collaboration_journal',
                    'proof_package',
                    'assembly_participation',
                    'ethics_reflection',
                    'final_synthesis',
                ],
                'requiredarchives' => [
                    'portfolio_item',
                    'proof',
                    'reflection',
                ],
                'recognitionlabel' => 'Reconnaissance interne — Tronc commun UCKK',
                'sortorder' => 10,
            ],
            [
                'shortname' => 'grand_jeu_social',
                'idnumber' => 'UCKK-PATH-GJS',
                'fullname' => 'Baccalauréat interne du Grand Jeu social',
                'pathwaytype' => self::TYPE_BACCALAUREAT,
                'description' => 'Parcours interne pour apprendre à lire le Grand Jeu social, ses règles visibles et invisibles, ses institutions, ses récits, ses flux et ses possibilités de réparation.',
                'requiredcompetencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-002',
                    'UCKK-COMP-003',
                    'UCKK-COMP-005',
                    'UCKK-COMP-006',
                    'UCKK-COMP-007',
                    'UCKK-COMP-011',
                    'UCKK-COMP-013',
                ],
                'requiredbadges' => [
                    'grand_jeu_social',
                    'cartographe_systemes',
                    'gardien_preuve',
                    'participant_assemblee',
                ],
                'recognitionlabel' => 'Reconnaissance interne — Grand Jeu social',
                'sortorder' => 20,
            ],
            [
                'shortname' => 'architecture_ecosysteme_digital_koa',
                'idnumber' => 'UCKK-PATH-KOA-DIGITAL',
                'fullname' => 'Baccalauréat interne en Architecture de l’écosystème digital kOA',
                'pathwaytype' => self::TYPE_BACCALAUREAT,
                'description' => 'Parcours interne pour comprendre, concevoir et gouverner le kOA Digital Ecosystem comme infrastructure numérique opérable, sans confondre l’école UCKK avec toute l’infrastructure kOA.',
                'requiredcompetencies' => [
                    'UCKK-COMP-002',
                    'UCKK-COMP-004',
                    'UCKK-COMP-005',
                    'UCKK-COMP-008',
                    'UCKK-COMP-009',
                    'UCKK-COMP-012',
                    'UCKK-COMP-014',
                ],
                'requiredbadges' => [
                    'koa_digital_ecosystem',
                    'ia_gouvernable',
                    'batisseur_prototype',
                    'archiviste_decision',
                ],
                'recognitionlabel' => 'Reconnaissance interne — Architecture de l’écosystème digital kOA',
                'sortorder' => 30,
            ],
            [
                'shortname' => 'medias_vivants_theatre_public_responsable',
                'idnumber' => 'UCKK-PATH-MV',
                'fullname' => 'Mineure interne Médias vivants et théâtre public responsable',
                'pathwaytype' => self::TYPE_MINEURE,
                'description' => 'Parcours interne sur la scène, le récit, la satire, King Klown, les défis publics, la diffusion courte et les limites éthiques de la performance.',
                'requiredcompetencies' => [
                    'UCKK-COMP-003',
                    'UCKK-COMP-005',
                    'UCKK-COMP-007',
                    'UCKK-COMP-010',
                    'UCKK-COMP-013',
                ],
                'requiredbadges' => [
                    'defi_king_klown',
                    'gardien_preuve',
                    'participant_assemblee',
                ],
                'requiredportfolioitems' => [
                    'public_intervention_plan',
                    'fiction_fact_boundary',
                    'ethical_risk_grid',
                    'archive_report',
                ],
                'recognitionlabel' => 'Reconnaissance interne — Médias vivants et théâtre public responsable',
                'sortorder' => 120,
            ],
        ];
    }

    /**
     * Validate the pathway.
     *
     * @return void
     */
    public function validate(): void {
        if ($this->shortname === '') {
            throw new invalid_parameter_exception('Pathway shortname is required.');
        }

        if ($this->fullname === '') {
            throw new invalid_parameter_exception('Pathway fullname is required.');
        }

        if (!in_array($this->pathwaytype, self::get_allowed_pathway_types(), true)) {
            throw new invalid_parameter_exception('Invalid pathway type.');
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid pathway status.');
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new invalid_parameter_exception('Invalid pathway visibility.');
        }

        if (!in_array($this->recognitiontype, self::get_allowed_recognition_types(), true)) {
            throw new invalid_parameter_exception('Invalid pathway recognition type.');
        }

        if ($this->programid < 0) {
            throw new invalid_parameter_exception('Invalid pathway program id.');
        }

        if ($this->descriptionformat <= 0) {
            $this->descriptionformat = FORMAT_HTML;
        }
    }

    /**
     * Convert to a database-ready record.
     *
     * @param bool $includeid Whether to include id.
     * @return stdClass
     */
    public function to_record(bool $includeid = true): stdClass {
        $record = new stdClass();

        if ($includeid && $this->id > 0) {
            $record->id = $this->id;
        }

        $record->programid = $this->programid;
        $record->shortname = $this->shortname;
        $record->idnumber = $this->idnumber;
        $record->fullname = $this->fullname;
        $record->pathwaytype = $this->pathwaytype;
        $record->description = $this->description;
        $record->descriptionformat = $this->descriptionformat;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->sortorder = $this->sortorder;
        $record->requiredcourseids = self::encode_list($this->requiredcourseids);
        $record->requiredcourses = self::encode_list($this->requiredcourses);
        $record->requiredcompetencies = self::encode_list($this->requiredcompetencies);
        $record->requiredbadges = self::encode_list($this->requiredbadges);
        $record->requiredarchives = self::encode_list($this->requiredarchives);
        $record->requiredportfolioitems = self::encode_list($this->requiredportfolioitems);
        $record->requiredchallenges = self::encode_list($this->requiredchallenges);
        $record->portfoliorequired = $this->portfoliorequired ? 1 : 0;
        $record->archiverequired = $this->archiverequired ? 1 : 0;
        $record->integrityclearancerequired = $this->integrityclearancerequired ? 1 : 0;
        $record->recognitiontype = $this->recognitiontype;
        $record->recognitionlabel = $this->recognitionlabel;
        $record->internalnotice = $this->internalnotice;
        $record->metadata = self::encode_assoc($this->metadata);
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;
        $record->createdby = $this->createdby;
        $record->modifiedby = $this->modifiedby;

        return $record;
    }

    /**
     * Export for APIs, reports, external wrappers or templates.
     *
     * @return stdClass
     */
    public function export(): stdClass {
        $data = new stdClass();

        $data->id = $this->id;
        $data->programid = $this->programid;
        $data->shortname = $this->shortname;
        $data->idnumber = $this->idnumber;
        $data->fullname = format_string($this->fullname);
        $data->rawfullname = $this->fullname;
        $data->pathwaytype = $this->pathwaytype;
        $data->description = $this->description;
        $data->descriptionformat = $this->descriptionformat;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->sortorder = $this->sortorder;

        $data->requiredcourseids = $this->requiredcourseids;
        $data->requiredcourses = $this->requiredcourses;
        $data->requiredcompetencies = $this->requiredcompetencies;
        $data->requiredbadges = $this->requiredbadges;
        $data->requiredarchives = $this->requiredarchives;
        $data->requiredportfolioitems = $this->requiredportfolioitems;
        $data->requiredchallenges = $this->requiredchallenges;

        $data->coursecount = count($this->requiredcourseids) + count($this->requiredcourses);
        $data->competencycount = count($this->requiredcompetencies);
        $data->badgecount = count($this->requiredbadges);
        $data->archivecount = count($this->requiredarchives);
        $data->portfolioitemcount = count($this->requiredportfolioitems);
        $data->challengecount = count($this->requiredchallenges);

        $data->portfoliorequired = $this->portfoliorequired;
        $data->archiverequired = $this->archiverequired;
        $data->integrityclearancerequired = $this->integrityclearancerequired;
        $data->recognitiontype = $this->recognitiontype;
        $data->recognitionlabel = $this->recognitionlabel !== '' ? $this->recognitionlabel : $this->fullname;
        $data->internalnotice = $this->internalnotice !== '' ? $this->internalnotice : self::get_default_internal_notice();

        $data->metadata = $this->metadata;
        $data->timecreated = $this->timecreated;
        $data->timemodified = $this->timemodified;
        $data->createdby = $this->createdby;
        $data->modifiedby = $this->modifiedby;

        $data->active = $this->is_active();
        $data->draft = $this->is_draft();
        $data->hidden = $this->is_hidden();
        $data->archived = $this->is_archived();
        $data->public = $this->is_public();
        $data->internal = $this->recognitiontype === self::RECOGNITION_INTERNAL;

        return $data;
    }

    /**
     * Export as Mustache-friendly array.
     *
     * @param array<string, mixed> $overrides Optional overrides.
     * @return array<string, mixed>
     */
    public function export_for_template(array $overrides = []): array {
        $export = (array)$this->export();

        $export['hasdescription'] = trim($this->description) !== '';
        $export['hasidnumber'] = $this->idnumber !== '';
        $export['hasrecognitionlabel'] = $this->recognitionlabel !== '';
        $export['hascourses'] = !empty($this->requiredcourseids) || !empty($this->requiredcourses);
        $export['hascompetencies'] = !empty($this->requiredcompetencies);
        $export['hasbadges'] = !empty($this->requiredbadges);
        $export['hasarchives'] = !empty($this->requiredarchives);
        $export['hasportfolioitems'] = !empty($this->requiredportfolioitems);
        $export['haschallenges'] = !empty($this->requiredchallenges);

        $export['requiredcourseitems'] = self::strings_to_template_items($this->requiredcourses);
        $export['requiredcompetencyitems'] = self::strings_to_template_items($this->requiredcompetencies);
        $export['requiredbadgeitems'] = self::strings_to_template_items($this->requiredbadges);
        $export['requiredarchiveitems'] = self::strings_to_template_items($this->requiredarchives);
        $export['requiredportfolioitemslist'] = self::strings_to_template_items($this->requiredportfolioitems);
        $export['requiredchallengeitems'] = self::strings_to_template_items($this->requiredchallenges);

        return array_merge($export, $overrides);
    }

    /**
     * Calculate a simple progress summary from status counts.
     *
     * This helper does not query Moodle. The caller must provide already
     * computed counts from Moodle subsystems or APIs.
     *
     * @param int $completed Completed count.
     * @param int|null $required Required count. If null, required count is inferred.
     * @param int $pending Pending count.
     * @param int $blocked Blocked count.
     * @return array<string, mixed>
     */
    public function calculate_progress_summary(
        int $completed,
        ?int $required = null,
        int $pending = 0,
        int $blocked = 0
    ): array {
        $completed = max(0, $completed);
        $pending = max(0, $pending);
        $blocked = max(0, $blocked);

        if ($required === null) {
            $required = $this->get_total_requirement_count();
        }

        $required = max(0, $required);

        if ($required === 0) {
            $percent = 0.0;
            $status = self::PROGRESS_NOT_STARTED;
        } else {
            $percent = round(($completed / $required) * 100, 2);

            if ($blocked > 0) {
                $status = self::PROGRESS_BLOCKED;
            } else if ($completed >= $required) {
                $status = self::PROGRESS_COMPLETED;
            } else if ($completed > 0 || $pending > 0) {
                $status = self::PROGRESS_IN_PROGRESS;
            } else {
                $status = self::PROGRESS_NOT_STARTED;
            }
        }

        return [
            'status' => $status,
            'completionpercent' => min(100.0, $percent),
            'requiredcount' => $required,
            'completedcount' => $completed,
            'pendingcount' => $pending,
            'blockedcount' => $blocked,
            'lastupdated' => time(),
        ];
    }

    /**
     * Return total requirement count.
     *
     * @return int
     */
    public function get_total_requirement_count(): int {
        return count($this->requiredcourseids)
            + count($this->requiredcourses)
            + count($this->requiredcompetencies)
            + count($this->requiredbadges)
            + count($this->requiredarchives)
            + count($this->requiredportfolioitems)
            + count($this->requiredchallenges);
    }

    /**
     * Determine whether the pathway has any requirement.
     *
     * @return bool
     */
    public function has_requirements(): bool {
        return $this->get_total_requirement_count() > 0;
    }

    /**
     * Determine whether pathway is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Determine whether pathway is draft.
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Determine whether pathway is hidden.
     *
     * @return bool
     */
    public function is_hidden(): bool {
        return $this->status === self::STATUS_HIDDEN;
    }

    /**
     * Determine whether pathway is archived.
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Determine whether pathway is public.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Determine whether pathway is visible institutionally.
     *
     * @return bool
     */
    public function is_institution_visible(): bool {
        return $this->visibility === self::VISIBILITY_INSTITUTION || $this->is_public();
    }

    /**
     * Determine whether the pathway can be displayed in public lists.
     *
     * @return bool
     */
    public function can_show_in_catalogue(): bool {
        return $this->is_active() && $this->is_public();
    }

    /**
     * Get pathway id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get program id.
     *
     * @return int
     */
    public function get_programid(): int {
        return $this->programid;
    }

    /**
     * Get shortname.
     *
     * @return string
     */
    public function get_shortname(): string {
        return $this->shortname;
    }

    /**
     * Get idnumber.
     *
     * @return string
     */
    public function get_idnumber(): string {
        return $this->idnumber;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function get_fullname(): string {
        return $this->fullname;
    }

    /**
     * Get pathway type.
     *
     * @return string
     */
    public function get_pathwaytype(): string {
        return $this->pathwaytype;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Get visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return $this->visibility;
    }

    /**
     * Get required course ids.
     *
     * @return int[]
     */
    public function get_required_course_ids(): array {
        return $this->requiredcourseids;
    }

    /**
     * Get required course keys.
     *
     * @return string[]
     */
    public function get_required_courses(): array {
        return $this->requiredcourses;
    }

    /**
     * Get required competencies.
     *
     * @return string[]
     */
    public function get_required_competencies(): array {
        return $this->requiredcompetencies;
    }

    /**
     * Get required badges.
     *
     * @return string[]
     */
    public function get_required_badges(): array {
        return $this->requiredbadges;
    }

    /**
     * Get required archive types.
     *
     * @return string[]
     */
    public function get_required_archives(): array {
        return $this->requiredarchives;
    }

    /**
     * Get required portfolio items.
     *
     * @return string[]
     */
    public function get_required_portfolio_items(): array {
        return $this->requiredportfolioitems;
    }

    /**
     * Get required challenges.
     *
     * @return string[]
     */
    public function get_required_challenges(): array {
        return $this->requiredchallenges;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
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
     * Return allowed pathway types.
     *
     * @return string[]
     */
    public static function get_allowed_pathway_types(): array {
        return [
            self::TYPE_TRONC_COMMUN,
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_SEMINAIRE,
            self::TYPE_LABORATOIRE,
        ];
    }

    /**
     * Return allowed recognition types.
     *
     * @return string[]
     */
    public static function get_allowed_recognition_types(): array {
        return [
            self::RECOGNITION_INTERNAL,
            self::RECOGNITION_PORTFOLIO,
            self::RECOGNITION_BADGE,
            self::RECOGNITION_ARCHIVE,
        ];
    }

    /**
     * Return the default internal recognition notice.
     *
     * @return string
     */
    public static function get_default_internal_notice(): string {
        if (get_string_manager()->string_exists('pathway_internal_notice', self::COMPONENT)) {
            return get_string('pathway_internal_notice', self::COMPONENT);
        }

        return 'Les parcours UCKK structurent des reconnaissances internes. Ils ne constituent pas des diplômes publics accrédités, sauf reconnaissance officielle future.';
    }

    /**
     * Clean pathway type.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_pathway_type($value): string {
        $value = self::normalise_shortname($value);

        if (!in_array($value, self::get_allowed_pathway_types(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK pathway type.');
        }

        return $value;
    }

    /**
     * Clean status.
     *
     * @param mixed $value Raw status.
     * @return string
     */
    private static function clean_status($value): string {
        $value = self::normalise_shortname($value);

        if (!in_array($value, self::get_allowed_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK pathway status.');
        }

        return $value;
    }

    /**
     * Clean visibility.
     *
     * @param mixed $value Raw visibility.
     * @return string
     */
    private static function clean_visibility($value): string {
        $value = self::normalise_shortname($value);

        if (!in_array($value, self::get_allowed_visibilities(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK pathway visibility.');
        }

        return $value;
    }

    /**
     * Clean recognition type.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_recognition_type($value): string {
        $value = self::normalise_shortname($value);

        if (!in_array($value, self::get_allowed_recognition_types(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK pathway recognition type.');
        }

        return $value;
    }

    /**
     * Normalise a shortname-like value.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function normalise_shortname($value): string {
        $value = trim((string)$value);
        $value = \core_text::strtolower($value);
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = clean_param($value, PARAM_ALPHANUMEXT);
        $value = preg_replace('/_+/', '_', $value) ?? '';
        $value = trim($value, '_');

        return $value;
    }

    /**
     * Clean plain text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_text($value): string {
        return trim(clean_param((string)$value, PARAM_TEXT));
    }

    /**
     * Clean raw formatted text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_raw_text($value): string {
        return clean_param((string)$value, PARAM_RAW);
    }

    /**
     * Clean required text.
     *
     * @param mixed $value Raw value.
     * @param string $field Field name.
     * @return string
     */
    private static function clean_required_text($value, string $field): string {
        $value = self::clean_text($value);

        if ($value === '') {
            throw new invalid_parameter_exception("Missing required pathway field: {$field}");
        }

        return $value;
    }

    /**
     * Clean boolean-like value.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private static function clean_bool($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        $value = \core_text::strtolower(trim((string)$value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    /**
     * Decode a JSON list or accept an array.
     *
     * @param mixed $value Raw value.
     * @return array<int, mixed>
     */
    private static function decode_list($value): array {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_object($value)) {
            return array_values((array)$value);
        }

        $value = trim((string)$value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    /**
     * Decode an associative JSON object or accept an array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private static function decode_assoc($value): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array)$value;
        }

        $value = trim((string)$value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Encode a list as JSON.
     *
     * @param array<int, mixed> $list List.
     * @return string
     */
    private static function encode_list(array $list): string {
        $list = array_values($list);

        return json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    /**
     * Encode associative metadata as JSON.
     *
     * @param array<string, mixed> $data Data.
     * @return string
     */
    private static function encode_assoc(array $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Normalise a string list.
     *
     * @param array<int, mixed> $items Raw items.
     * @return string[]
     */
    private static function normalise_string_list(array $items): array {
        $clean = [];

        foreach ($items as $item) {
            $item = trim(clean_param((string)$item, PARAM_TEXT));

            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Normalise an int list.
     *
     * @param array<int, mixed> $items Raw items.
     * @return int[]
     */
    private static function normalise_int_list(array $items): array {
        $clean = [];

        foreach ($items as $item) {
            $item = (int)$item;

            if ($item > 0) {
                $clean[] = $item;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Convert strings to Mustache-friendly items.
     *
     * @param string[] $items Items.
     * @return array<int, array<string, string>>
     */
    private static function strings_to_template_items(array $items): array {
        $out = [];

        foreach ($items as $item) {
            $out[] = [
                'value' => $item,
                'label' => $item,
            ];
        }

        return $out;
    }
}

