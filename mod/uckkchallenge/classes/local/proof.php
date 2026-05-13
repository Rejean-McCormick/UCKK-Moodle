<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK challenge proof.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

use coding_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Domain object for a proof/evidence record attached to a UCKK challenge.
 *
 * This class normalises and validates one proof record. It does not save to the
 * database directly, grade work, validate integrity, award badges, certify
 * competencies, or archive evidence. Those actions belong to services and
 * capability-checked workflows.
 */
final class proof {
    /** Proof type: text. */
    public const TYPE_TEXT = 'text';

    /** Proof type: file. */
    public const TYPE_FILE = 'file';

    /** Proof type: URL. */
    public const TYPE_URL = 'url';

    /** Proof type: dataset. */
    public const TYPE_DATASET = 'dataset';

    /** Proof type: image. */
    public const TYPE_IMAGE = 'image';

    /** Proof type: video. */
    public const TYPE_VIDEO = 'video';

    /** Proof type: testimony. */
    public const TYPE_TESTIMONY = 'testimony';

    /** Proof type: observation. */
    public const TYPE_OBSERVATION = 'observation';

    /** Proof type: AI log. */
    public const TYPE_AI_LOG = 'ai_log';

    /** Proof type: decision record. */
    public const TYPE_DECISION_RECORD = 'decision_record';

    /** Proof type: archive item. */
    public const TYPE_ARCHIVE_ITEM = 'archive_item';

    /** Proof type: portfolio item. */
    public const TYPE_PORTFOLIO_ITEM = 'portfolio_item';

    /** Proof type: assembly decision. */
    public const TYPE_ASSEMBLY_DECISION = 'assembly_decision';

    /** Proof type: mentor observation. */
    public const TYPE_MENTOR_OBSERVATION = 'mentor_observation';

    /** Proof type: Inquisiteur note. */
    public const TYPE_INQUISITEUR_NOTE = 'inquisiteur_note';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: user. */
    public const VISIBILITY_USER = 'user';

    /** Visibility: group. */
    public const VISIBILITY_GROUP = 'group';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: program. */
    public const VISIBILITY_PROGRAM = 'program';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Visibility: restricted. */
    public const VISIBILITY_RESTRICTED = 'restricted';

    /** Visibility: restricted integrity. */
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Provenance: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Provenance: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: challenge. */
    public const PROVENANCE_CHALLENGE = 'challenge';

    /** Provenance: integrity. */
    public const PROVENANCE_INTEGRITY = 'integrity';

    /** Integrity/validation state: unverified. */
    public const INTEGRITY_UNVERIFIED = 'unverified';

    /** Integrity/validation state: human reviewed. */
    public const INTEGRITY_HUMAN_REVIEWED = 'human_reviewed';

    /** Integrity/validation state: verified. */
    public const INTEGRITY_VERIFIED = 'verified';

    /** Integrity/validation state: contested. */
    public const INTEGRITY_CONTESTED = 'contested';

    /** Integrity/validation state: invalidated. */
    public const INTEGRITY_INVALIDATED = 'invalidated';

    /** Integrity/validation state: archived. */
    public const INTEGRITY_ARCHIVED = 'archived';

    /** Database id. */
    private int $id = 0;

    /** Parent uckkchallenge id. */
    private int $challengeid = 0;

    /** Parent submission id, if any. */
    private int $submissionid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Owner/submitting user id. */
    private int $userid = 0;

    /** Stable proof type. */
    private string $prooftype = self::TYPE_TEXT;

    /** Proof title. */
    private string $title = '';

    /** Proof content or textual summary. */
    private string $content = '';

    /** Optional external URL. */
    private string $url = '';

    /** Source description. */
    private string $source = '';

    /** Source author. */
    private string $author = '';

    /** Source date as Unix timestamp. */
    private int $sourcedate = 0;

    /** Relation to criteria. */
    private string $relationtocriteria = '';

    /** Provenance statement. */
    private string $provenancestatement = '';

    /** Proof status. */
    private string $status = self::STATUS_DRAFT;

    /** Visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Integrity state. */
    private string $integritystate = self::INTEGRITY_UNVERIFIED;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Whether AI assisted this proof. */
    private bool $aiassisted = false;

    /** AI collaboration log, if any. */
    private string $ailog = '';

    /** Uncertainty notes. */
    private string $uncertaintynotes = '';

    /** Moodle file component. */
    private string $filecomponent = 'mod_uckkchallenge';

    /** Moodle file area. */
    private string $filearea = 'proof_files';

    /** Moodle file item id. */
    private int $fileitemid = 0;

    /** Record version. */
    private int $versionno = 1;

    /** Created by user id. */
    private int $createdby = 0;

    /** Modified by user id. */
    private int $modifiedby = 0;

    /** Creation timestamp. */
    private int $timecreated = 0;

    /** Modification timestamp. */
    private int $timemodified = 0;

    /** Variable metadata. */
    private array $metadata = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass|null $data Initial proof data.
     */
    public function __construct(array|stdClass|null $data = null) {
        if ($data !== null) {
            $this->apply_data((array)$data);
        }
    }

    /**
     * Build a proof object from a Moodle database record.
     *
     * @param stdClass $record Database record from {uckkchallenge_proof}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new proof object.
     *
     * @param int $challengeid Parent challenge id.
     * @param int $userid Submitting user id.
     * @param string $prooftype Proof type.
     * @param string $title Proof title.
     * @param string $content Proof content.
     * @return self
     */
    public static function create(
        int $challengeid,
        int $userid,
        string $prooftype,
        string $title,
        string $content = ''
    ): self {
        return new self([
            'challengeid' => $challengeid,
            'userid' => $userid,
            'prooftype' => $prooftype,
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * Apply raw data to this proof.
     *
     * @param array<string, mixed> $data Raw data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->challengeid = max(0, (int)($data['challengeid'] ?? $data['uckkchallengeid'] ?? $this->challengeid));
        $this->submissionid = max(0, (int)($data['submissionid'] ?? $this->submissionid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));

        $this->prooftype = self::normalise_proof_type((string)($data['prooftype'] ?? $data['type'] ?? $this->prooftype));
        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->content = trim((string)($data['content'] ?? $data['prooftext'] ?? $this->content));
        $this->url = self::normalise_url((string)($data['url'] ?? $data['proofurl'] ?? $this->url));

        $this->source = trim(clean_param((string)($data['source'] ?? $this->source), PARAM_TEXT));
        $this->author = trim(clean_param((string)($data['author'] ?? $data['sourceauthor'] ?? $this->author), PARAM_TEXT));
        $this->sourcedate = max(0, (int)($data['sourcedate'] ?? $data['date'] ?? $this->sourcedate));
        $this->relationtocriteria = trim((string)($data['relationtocriteria'] ?? $this->relationtocriteria));
        $this->provenancestatement = trim((string)($data['provenancestatement'] ?? $this->provenancestatement));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->integritystate = self::normalise_integrity_state((string)($data['integritystate'] ?? $data['validationstate'] ?? $this->integritystate));

        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);
        $this->aiassisted = !empty($data['aiassisted']);
        $this->ailog = trim((string)($data['ailog'] ?? $this->ailog));
        $this->uncertaintynotes = trim((string)($data['uncertaintynotes'] ?? $this->uncertaintynotes));

        $this->filecomponent = clean_param((string)($data['filecomponent'] ?? $this->filecomponent), PARAM_COMPONENT);
        $this->filearea = clean_param((string)($data['filearea'] ?? $this->filearea), PARAM_AREA);
        $this->fileitemid = max(0, (int)($data['fileitemid'] ?? $data['itemid'] ?? $this->fileitemid));

        $this->versionno = max(1, (int)($data['versionno'] ?? $this->versionno));
        $this->createdby = max(0, (int)($data['createdby'] ?? $this->createdby));
        $this->modifiedby = max(0, (int)($data['modifiedby'] ?? $this->modifiedby));
        $this->timecreated = max(0, (int)($data['timecreated'] ?? $this->timecreated));
        $this->timemodified = max(0, (int)($data['timemodified'] ?? $this->timemodified));

        if (array_key_exists('metadata', $data)) {
            $this->metadata = self::normalise_metadata($data['metadata']);
        }
    }

    /**
     * Validate this proof object.
     *
     * @throws coding_exception If the proof is invalid.
     */
    public function validate(): void {
        if ($this->challengeid <= 0) {
            throw new coding_exception('Challenge proof requires a valid challengeid.');
        }

        if ($this->userid <= 0) {
            throw new coding_exception('Challenge proof requires a valid userid.');
        }

        if ($this->title === '') {
            throw new coding_exception('Challenge proof requires a title.');
        }

        if (!in_array($this->prooftype, self::get_allowed_proof_types(), true)) {
            throw new coding_exception('Invalid UCKK challenge proof type: ' . $this->prooftype);
        }

        if ($this->requires_url() && $this->url === '') {
            throw new coding_exception('This UCKK proof type requires a URL.');
        }

        if ($this->requires_text_content() && $this->content === '') {
            throw new coding_exception('This UCKK proof type requires content.');
        }

        if ($this->source === '') {
            throw new coding_exception('Challenge proof requires a source.');
        }

        if ($this->author === '') {
            throw new coding_exception('Challenge proof requires an author.');
        }

        if ($this->relationtocriteria === '') {
            throw new coding_exception('Challenge proof requires relation to criteria.');
        }

        if ($this->provenancestatement === '') {
            throw new coding_exception('Challenge proof requires a provenance statement.');
        }

        if ($this->aiassisted && $this->ailog === '') {
            throw new coding_exception('AI-assisted proof requires an AI collaboration log.');
        }
    }

    /**
     * Convert to database record for {uckkchallenge_proof}.
     *
     * @param int|null $userid Current user id for audit defaults.
     * @param int|null $now Current timestamp.
     * @return stdClass
     */
    public function to_record(?int $userid = null, ?int $now = null): stdClass {
        $this->validate();

        $now ??= time();
        $userid ??= 0;

        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->challengeid = $this->challengeid;
        $record->submissionid = $this->submissionid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->prooftype = $this->prooftype;
        $record->title = $this->title;
        $record->content = $this->content;
        $record->url = $this->url;
        $record->source = $this->source;
        $record->author = $this->author;
        $record->sourcedate = $this->sourcedate;
        $record->relationtocriteria = $this->relationtocriteria;
        $record->provenancestatement = $this->provenancestatement;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->provenance = $this->provenance;
        $record->integritystate = $this->integritystate;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->aiassisted = $this->aiassisted ? 1 : 0;
        $record->ailog = $this->ailog;
        $record->uncertaintynotes = $this->uncertaintynotes;
        $record->filecomponent = $this->filecomponent;
        $record->filearea = $this->filearea;
        $record->fileitemid = $this->fileitemid;
        $record->versionno = $this->versionno;
        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->metadata = $this->metadata === []
            ? null
            : json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Convert to safe export data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->challengeid = $this->challengeid;
        $data->submissionid = $this->submissionid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->prooftype = $this->prooftype;
        $data->title = $this->title;
        $data->content = $this->content;
        $data->url = $this->url;
        $data->source = $this->source;
        $data->author = $this->author;
        $data->sourcedate = $this->sourcedate;
        $data->relationtocriteria = $this->relationtocriteria;
        $data->provenancestatement = $this->provenancestatement;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->integritystate = $this->integritystate;
        $data->aiassisted = $this->aiassisted;
        $data->ailog = $this->ailog;
        $data->uncertaintynotes = $this->uncertaintynotes;
        $data->filecomponent = $this->filecomponent;
        $data->filearea = $this->filearea;
        $data->fileitemid = $this->fileitemid;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;

        return $data;
    }

    /**
     * Return whether this proof is submitted.
     *
     * @return bool
     */
    public function is_submitted(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Return whether this proof is validated.
     *
     * @return bool
     */
    public function is_validated(): bool {
        return $this->status === self::STATUS_VALIDATED
            && $this->integritystate === self::INTEGRITY_VERIFIED;
    }

    /**
     * Return whether this proof is restricted.
     *
     * @return bool
     */
    public function is_restricted(): bool {
        return in_array($this->visibility, [
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
        ], true);
    }

    /**
     * Return whether this proof needs integrity review.
     *
     * @return bool
     */
    public function requires_integrity_review(): bool {
        return $this->integritystate === self::INTEGRITY_CONTESTED
            || $this->status === self::STATUS_CONTESTED
            || $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->provenance === self::PROVENANCE_AI_ASSISTED;
    }

    /**
     * Return whether this proof type requires a URL.
     *
     * @return bool
     */
    public function requires_url(): bool {
        return in_array($this->prooftype, [
            self::TYPE_URL,
            self::TYPE_DATASET,
            self::TYPE_VIDEO,
            self::TYPE_ARCHIVE_ITEM,
            self::TYPE_PORTFOLIO_ITEM,
            self::TYPE_ASSEMBLY_DECISION,
        ], true);
    }

    /**
     * Return whether this proof type requires text content.
     *
     * @return bool
     */
    public function requires_text_content(): bool {
        return in_array($this->prooftype, [
            self::TYPE_TEXT,
            self::TYPE_TESTIMONY,
            self::TYPE_OBSERVATION,
            self::TYPE_AI_LOG,
            self::TYPE_DECISION_RECORD,
            self::TYPE_MENTOR_OBSERVATION,
            self::TYPE_INQUISITEUR_NOTE,
        ], true);
    }

    /**
     * Return whether this proof type normally uses Moodle files.
     *
     * @return bool
     */
    public function supports_files(): bool {
        return in_array($this->prooftype, [
            self::TYPE_FILE,
            self::TYPE_DATASET,
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
        ], true);
    }

    /**
     * Return id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return challenge id.
     *
     * @return int
     */
    public function get_challengeid(): int {
        return $this->challengeid;
    }

    /**
     * Return submission id.
     *
     * @return int
     */
    public function get_submissionid(): int {
        return $this->submissionid;
    }

    /**
     * Return user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return proof type.
     *
     * @return string
     */
    public function get_prooftype(): string {
        return $this->prooftype;
    }

    /**
     * Return proof title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Return proof status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Return visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return $this->visibility;
    }

    /**
     * Return integrity state.
     *
     * @return string
     */
    public function get_integritystate(): string {
        return $this->integritystate;
    }

    /**
     * Return metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Return metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Return a copy with record id.
     *
     * @param int $id Record id.
     * @return self
     */
    public function with_id(int $id): self {
        $clone = clone $this;
        $clone->id = max(0, $id);
        return $clone;
    }

    /**
     * Return a copy with submission id.
     *
     * @param int $submissionid Submission id.
     * @return self
     */
    public function with_submissionid(int $submissionid): self {
        $clone = clone $this;
        $clone->submissionid = max(0, $submissionid);
        return $clone;
    }

    /**
     * Return a copy with context references.
     *
     * @param int $courseid Course id.
     * @param int $cmid Course module id.
     * @param int $contextid Context id.
     * @return self
     */
    public function with_context(int $courseid, int $cmid, int $contextid): self {
        $clone = clone $this;
        $clone->courseid = max(0, $courseid);
        $clone->cmid = max(0, $cmid);
        $clone->contextid = max(0, $contextid);
        return $clone;
    }

    /**
     * Return a copy with file references.
     *
     * @param string $component File component.
     * @param string $area File area.
     * @param int $itemid File item id.
     * @return self
     */
    public function with_file_reference(string $component, string $area, int $itemid): self {
        $clone = clone $this;
        $clone->filecomponent = clean_param($component, PARAM_COMPONENT);
        $clone->filearea = clean_param($area, PARAM_AREA);
        $clone->fileitemid = max(0, $itemid);
        return $clone;
    }

    /**
     * Return a copy with status.
     *
     * @param string $status New status.
     * @return self
     */
    public function with_status(string $status): self {
        $clone = clone $this;
        $clone->status = self::normalise_status($status);
        return $clone;
    }

    /**
     * Return a copy with visibility.
     *
     * @param string $visibility New visibility.
     * @return self
     */
    public function with_visibility(string $visibility): self {
        $clone = clone $this;
        $clone->visibility = self::normalise_visibility($visibility);
        return $clone;
    }

    /**
     * Return a copy with integrity state.
     *
     * @param string $integritystate New integrity state.
     * @return self
     */
    public function with_integrity_state(string $integritystate): self {
        $clone = clone $this;
        $clone->integritystate = self::normalise_integrity_state($integritystate);
        return $clone;
    }

    /**
     * Return a copy with metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function with_metadata(array $metadata): self {
        $clone = clone $this;
        $clone->metadata = self::normalise_metadata($metadata);
        return $clone;
    }

    /**
     * Return a modified copy with incremented version.
     *
     * @param int $userid User id.
     * @param int|null $now Timestamp.
     * @return self
     */
    public function mark_modified(int $userid, ?int $now = null): self {
        $clone = clone $this;
        $clone->modifiedby = max(0, $userid);
        $clone->timemodified = $now ?? time();
        $clone->versionno++;
        return $clone;
    }

    /**
     * Allowed proof types.
     *
     * @return string[]
     */
    public static function get_allowed_proof_types(): array {
        return [
            self::TYPE_TEXT,
            self::TYPE_FILE,
            self::TYPE_URL,
            self::TYPE_DATASET,
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
            self::TYPE_TESTIMONY,
            self::TYPE_OBSERVATION,
            self::TYPE_AI_LOG,
            self::TYPE_DECISION_RECORD,
            self::TYPE_ARCHIVE_ITEM,
            self::TYPE_PORTFOLIO_ITEM,
            self::TYPE_ASSEMBLY_DECISION,
            self::TYPE_MENTOR_OBSERVATION,
            self::TYPE_INQUISITEUR_NOTE,
        ];
    }

    /**
     * Allowed statuses.
     *
     * @return string[]
     */
    public static function get_allowed_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Allowed visibilities.
     *
     * @return string[]
     */
    public static function get_allowed_visibilities(): array {
        return [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_USER,
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
        ];
    }

    /**
     * Allowed provenance sources.
     *
     * @return string[]
     */
    public static function get_allowed_provenance_sources(): array {
        return [
            self::PROVENANCE_HUMAN,
            self::PROVENANCE_AI_ASSISTED,
            self::PROVENANCE_IMPORTED,
            self::PROVENANCE_SYSTEM,
            self::PROVENANCE_ARCHIVE,
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_CHALLENGE,
            self::PROVENANCE_INTEGRITY,
        ];
    }

    /**
     * Allowed integrity states.
     *
     * @return string[]
     */
    public static function get_allowed_integrity_states(): array {
        return [
            self::INTEGRITY_UNVERIFIED,
            self::INTEGRITY_HUMAN_REVIEWED,
            self::INTEGRITY_VERIFIED,
            self::INTEGRITY_CONTESTED,
            self::INTEGRITY_INVALIDATED,
            self::INTEGRITY_ARCHIVED,
        ];
    }

    /**
     * Normalise proof type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_proof_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);
        return in_array($type, self::get_allowed_proof_types(), true) ? $type : self::TYPE_TEXT;
    }

    /**
     * Normalise title.
     *
     * @param string $title Raw title.
     * @return string
     */
    private static function normalise_title(string $title): string {
        return trim(clean_param($title, PARAM_TEXT));
    }

    /**
     * Normalise URL.
     *
     * @param string $url Raw URL.
     * @return string
     */
    private static function normalise_url(string $url): string {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        return clean_param($url, PARAM_URL);
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);
        return in_array($status, self::get_allowed_statuses(), true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);
        return in_array($visibility, self::get_allowed_visibilities(), true) ? $visibility : self::VISIBILITY_COURSE;
    }

    /**
     * Normalise provenance.
     *
     * @param string $provenance Raw provenance.
     * @return string
     */
    private static function normalise_provenance(string $provenance): string {
        $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);
        return in_array($provenance, self::get_allowed_provenance_sources(), true) ? $provenance : self::PROVENANCE_HUMAN;
    }

    /**
     * Normalise integrity state.
     *
     * @param string $state Raw state.
     * @return string
     */
    private static function normalise_integrity_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);
        return in_array($state, self::get_allowed_integrity_states(), true) ? $state : self::INTEGRITY_UNVERIFIED;
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private static function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            return $decoded;
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return [];
    }
}