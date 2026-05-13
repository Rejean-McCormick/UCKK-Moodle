<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Domain model for a UCKK challenge submission.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use stdClass;

/**
 * Domain model for records stored in uckkchallenge_sub.
 *
 * This class normalises and validates submission data. It does not:
 * - decide final challenge validation;
 * - certify competencies;
 * - award badges;
 * - close integrity cases;
 * - archive evidence;
 * - replace mentor or Inquisiteur review.
 */
final class submission {
    /** Submission status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Submission status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Submission status: resubmitted after correction. */
    public const STATUS_RESUBMITTED = 'resubmitted';

    /** Submission status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Submission status: under review. */
    public const STATUS_UNDER_REVIEW = 'under_review';

    /** Submission status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Submission status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Submission status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Submission status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Submission status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Submission status: archived. */
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

    /** Visibility: restricted to integrity workflow. */
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Provenance source: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance source: AI-assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance source: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance source: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Provenance source: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance source: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance source: challenge. */
    public const PROVENANCE_CHALLENGE = 'challenge';

    /** Provenance source: integrity. */
    public const PROVENANCE_INTEGRITY = 'integrity';

    /** Validation state: unverified. */
    public const INTEGRITY_UNVERIFIED = 'unverified';

    /** Validation state: human reviewed. */
    public const INTEGRITY_HUMAN_REVIEWED = 'human_reviewed';

    /** Validation state: verified. */
    public const INTEGRITY_VERIFIED = 'verified';

    /** Validation state: contested. */
    public const INTEGRITY_CONTESTED = 'contested';

    /** Validation state: invalidated. */
    public const INTEGRITY_INVALIDATED = 'invalidated';

    /** Validation state: archived. */
    public const INTEGRITY_ARCHIVED = 'archived';

    /** Proof type: text. */
    public const PROOF_TEXT = 'text';

    /** Proof type: file. */
    public const PROOF_FILE = 'file';

    /** Proof type: URL. */
    public const PROOF_URL = 'url';

    /** Proof type: dataset. */
    public const PROOF_DATASET = 'dataset';

    /** Proof type: image. */
    public const PROOF_IMAGE = 'image';

    /** Proof type: video. */
    public const PROOF_VIDEO = 'video';

    /** Proof type: testimony. */
    public const PROOF_TESTIMONY = 'testimony';

    /** Proof type: observation. */
    public const PROOF_OBSERVATION = 'observation';

    /** Proof type: AI log. */
    public const PROOF_AI_LOG = 'ai_log';

    /** Proof type: decision record. */
    public const PROOF_DECISION_RECORD = 'decision_record';

    /**
     * Submission id.
     *
     * @var int
     */
    private int $id = 0;

    /**
     * Challenge instance id.
     *
     * @var int
     */
    private int $challengeid = 0;

    /**
     * Course id.
     *
     * @var int
     */
    private int $courseid = 0;

    /**
     * Course module id.
     *
     * @var int
     */
    private int $cmid = 0;

    /**
     * Context id.
     *
     * @var int
     */
    private int $contextid = 0;

    /**
     * Submitting user id.
     *
     * @var int
     */
    private int $userid = 0;

    /**
     * Optional group id.
     *
     * @var int
     */
    private int $groupid = 0;

    /**
     * Attempt number.
     *
     * @var int
     */
    private int $attemptno = 1;

    /**
     * Main proof type.
     *
     * @var string
     */
    private string $prooftype = self::PROOF_TEXT;

    /**
     * Submission text.
     *
     * @var string
     */
    private string $submissiontext = '';

    /**
     * Submission text format.
     *
     * @var int
     */
    private int $submissiontextformat = FORMAT_HTML;

    /**
     * Optional submission URL.
     *
     * @var string
     */
    private string $submissionurl = '';

    /**
     * Relation to criteria.
     *
     * @var string
     */
    private string $relationtocriteria = '';

    /**
     * Provenance statement.
     *
     * @var string
     */
    private string $provenancestatement = '';

    /**
     * Source or author.
     *
     * @var string
     */
    private string $sourceauthor = '';

    /**
     * Source date.
     *
     * @var int
     */
    private int $sourcedate = 0;

    /**
     * Visibility.
     *
     * @var string
     */
    private string $visibility = self::VISIBILITY_COURSE;

    /**
     * Submission status.
     *
     * @var string
     */
    private string $status = self::STATUS_DRAFT;

    /**
     * Integrity state.
     *
     * @var string
     */
    private string $integritystate = self::INTEGRITY_UNVERIFIED;

    /**
     * Provenance source.
     *
     * @var string
     */
    private string $provenance = self::PROVENANCE_HUMAN;

    /**
     * Whether AI assisted this submission.
     *
     * @var bool
     */
    private bool $aiassisted = false;

    /**
     * AI collaboration log.
     *
     * @var string
     */
    private string $ailog = '';

    /**
     * Uncertainty notes.
     *
     * @var string
     */
    private string $uncertaintynotes = '';

    /**
     * Creator user id.
     *
     * @var int
     */
    private int $createdby = 0;

    /**
     * Last modifier user id.
     *
     * @var int
     */
    private int $modifiedby = 0;

    /**
     * Creation timestamp.
     *
     * @var int
     */
    private int $timecreated = 0;

    /**
     * Modified timestamp.
     *
     * @var int
     */
    private int $timemodified = 0;

    /**
     * Submitted timestamp.
     *
     * @var int
     */
    private int $timesubmitted = 0;

    /**
     * Version number.
     *
     * @var int
     */
    private int $versionno = 1;

    /**
     * Optional provenance hash.
     *
     * @var string
     */
    private string $provenancehash = '';

    /**
     * Variable JSON metadata.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    /**
     * Build a submission model.
     *
     * @param array<string, mixed>|stdClass $data Submission data.
     */
    public function __construct(array|stdClass $data = []) {
        $this->load((array)$data);
    }

    /**
     * Create a submission model from a database record.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Create a new draft submission for a challenge/user pair.
     *
     * @param int $challengeid Challenge id.
     * @param int $courseid Course id.
     * @param int $cmid Course module id.
     * @param int $contextid Context id.
     * @param int $userid Owner user id.
     * @param int $actorid Acting user id.
     * @param array<string, mixed> $data Optional submitted data.
     * @return self
     */
    public static function create_draft(
        int $challengeid,
        int $courseid,
        int $cmid,
        int $contextid,
        int $userid,
        int $actorid,
        array $data = []
    ): self {
        $now = time();

        return new self(array_merge($data, [
            'challengeid' => $challengeid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'contextid' => $contextid,
            'userid' => $userid,
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'status' => self::STATUS_DRAFT,
            'integritystate' => self::INTEGRITY_UNVERIFIED,
        ]));
    }

    /**
     * Load data into this model.
     *
     * @param array<string, mixed> $data Raw data.
     */
    public function load(array $data): void {
        $this->id = $this->int_value($data, 'id', $this->id);
        $this->challengeid = $this->int_value($data, 'challengeid', $this->challengeid);
        $this->courseid = $this->int_value($data, 'courseid', $this->courseid);
        $this->cmid = $this->int_value($data, 'cmid', $this->cmid);
        $this->contextid = $this->int_value($data, 'contextid', $this->contextid);
        $this->userid = $this->int_value($data, 'userid', $this->userid);
        $this->groupid = $this->int_value($data, 'groupid', $this->groupid);
        $this->attemptno = max(1, $this->int_value($data, 'attemptno', $this->attemptno));

        $this->prooftype = $this->normalise_proof_type((string)($data['prooftype'] ?? $this->prooftype));
        $this->submissiontext = $this->clean_text((string)($data['submissiontext'] ?? $this->submissiontext));
        $this->submissiontextformat = $this->int_value($data, 'submissiontextformat', $this->submissiontextformat);
        $this->submissionurl = $this->normalise_url((string)($data['submissionurl'] ?? $this->submissionurl));

        $this->relationtocriteria = $this->clean_text((string)($data['relationtocriteria'] ?? $this->relationtocriteria));
        $this->provenancestatement = $this->clean_text((string)($data['provenancestatement'] ?? $this->provenancestatement));
        $this->sourceauthor = clean_param((string)($data['sourceauthor'] ?? $this->sourceauthor), PARAM_TEXT);
        $this->sourcedate = $this->int_value($data, 'sourcedate', $this->sourcedate);

        $this->visibility = $this->normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->status = $this->normalise_status((string)($data['status'] ?? $this->status));
        $this->integritystate = $this->normalise_integrity_state((string)($data['integritystate'] ?? $this->integritystate));
        $this->provenance = $this->normalise_provenance((string)($data['provenance'] ?? $this->provenance));

        $this->aiassisted = !empty($data['aiassisted']);
        $this->ailog = $this->clean_text((string)($data['ailog'] ?? $this->ailog));
        $this->uncertaintynotes = $this->clean_text((string)($data['uncertaintynotes'] ?? $this->uncertaintynotes));

        $this->createdby = $this->int_value($data, 'createdby', $this->createdby);
        $this->modifiedby = $this->int_value($data, 'modifiedby', $this->modifiedby);
        $this->timecreated = $this->int_value($data, 'timecreated', $this->timecreated);
        $this->timemodified = $this->int_value($data, 'timemodified', $this->timemodified);
        $this->timesubmitted = $this->int_value($data, 'timesubmitted', $this->timesubmitted);
        $this->versionno = max(1, $this->int_value($data, 'versionno', $this->versionno));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        if (array_key_exists('metadata', $data)) {
            $this->metadata = $this->decode_metadata($data['metadata']);
        }

        $this->validate();
    }

    /**
     * Convert this model to a database-ready record.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->challengeid = $this->challengeid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->groupid = $this->groupid;
        $record->attemptno = $this->attemptno;
        $record->prooftype = $this->prooftype;
        $record->submissiontext = $this->submissiontext;
        $record->submissiontextformat = $this->submissiontextformat;
        $record->submissionurl = $this->submissionurl;
        $record->relationtocriteria = $this->relationtocriteria;
        $record->provenancestatement = $this->provenancestatement;
        $record->sourceauthor = $this->sourceauthor;
        $record->sourcedate = $this->sourcedate;
        $record->visibility = $this->visibility;
        $record->status = $this->status;
        $record->integritystate = $this->integritystate;
        $record->provenance = $this->provenance;
        $record->aiassisted = $this->aiassisted ? 1 : 0;
        $record->ailog = $this->ailog;
        $record->uncertaintynotes = $this->uncertaintynotes;
        $record->createdby = $this->createdby;
        $record->modifiedby = $this->modifiedby;
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;
        $record->timesubmitted = $this->timesubmitted;
        $record->versionno = $this->versionno;
        $record->provenancehash = $this->provenancehash;
        $record->metadata = json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Mark this submission as submitted or resubmitted.
     *
     * @param int $actorid Acting user id.
     * @param bool $isresubmission Whether this is a resubmission.
     */
    public function submit(int $actorid, bool $isresubmission = false): void {
        if (!$this->has_minimum_evidence()) {
            throw new coding_exception('A submission requires text, URL, or proof file metadata before submission.');
        }

        if ($this->aiassisted && trim($this->ailog) === '') {
            throw new coding_exception('AI-assisted submissions require an AI collaboration log.');
        }

        $now = time();

        $this->status = $isresubmission ? self::STATUS_RESUBMITTED : self::STATUS_SUBMITTED;
        $this->integritystate = self::INTEGRITY_UNVERIFIED;
        $this->modifiedby = $actorid;
        $this->timemodified = $now;
        $this->timesubmitted = $now;
        $this->versionno++;
    }

    /**
     * Mark this submission as pending review.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_pending_review(int $actorid): void {
        $this->set_status(self::STATUS_PENDING_REVIEW, $actorid);
    }

    /**
     * Mark this submission as under review.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_under_review(int $actorid): void {
        $this->set_status(self::STATUS_UNDER_REVIEW, $actorid);
    }

    /**
     * Mark this submission as correction required.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_correction_required(int $actorid): void {
        $this->set_status(self::STATUS_CORRECTION_REQUIRED, $actorid);
    }

    /**
     * Mark this submission as validated.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_validated(int $actorid): void {
        $this->set_status(self::STATUS_VALIDATED, $actorid);
        $this->integritystate = self::INTEGRITY_HUMAN_REVIEWED;
    }

    /**
     * Mark this submission as contested.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_contested(int $actorid): void {
        $this->set_status(self::STATUS_CONTESTED, $actorid);
        $this->integritystate = self::INTEGRITY_CONTESTED;
    }

    /**
     * Mark this submission as invalidated.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_invalidated(int $actorid): void {
        $this->set_status(self::STATUS_INVALIDATED, $actorid);
        $this->integritystate = self::INTEGRITY_INVALIDATED;
    }

    /**
     * Mark this submission as archived.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_archived(int $actorid): void {
        $this->set_status(self::STATUS_ARCHIVED, $actorid);
        $this->integritystate = self::INTEGRITY_ARCHIVED;
    }

    /**
     * Set submission text.
     *
     * @param string $text Text.
     * @param int $format Moodle text format.
     * @param int $actorid Acting user id.
     */
    public function set_submission_text(string $text, int $format, int $actorid): void {
        $this->submissiontext = $this->clean_text($text);
        $this->submissiontextformat = $format;
        $this->touch($actorid);
    }

    /**
     * Set provenance hash.
     *
     * @param string $hash Hash.
     * @param int $actorid Acting user id.
     */
    public function set_provenance_hash(string $hash, int $actorid): void {
        $this->provenancehash = clean_param($hash, PARAM_ALPHANUMEXT);
        $this->touch($actorid);
    }

    /**
     * Set metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @param int $actorid Acting user id.
     */
    public function set_metadata(array $metadata, int $actorid): void {
        $this->metadata = $metadata;
        $this->touch($actorid);
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
     * Return submission id.
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
     * Return user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return status.
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
    public function get_integrity_state(): string {
        return $this->integritystate;
    }

    /**
     * Return whether this is a draft.
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Return whether this submission is currently active in review workflow.
     *
     * @return bool
     */
    public function is_in_review(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_RESUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_UNDER_REVIEW,
        ], true);
    }

    /**
     * Return whether this submission has reached a terminal state.
     *
     * @return bool
     */
    public function is_terminal(): bool {
        return in_array($this->status, [
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Return whether this submission includes enough evidence to be submitted.
     *
     * File evidence should be represented by metadata['hasfiles'] or proof rows.
     *
     * @return bool
     */
    public function has_minimum_evidence(): bool {
        if (trim(strip_tags($this->submissiontext)) !== '') {
            return true;
        }

        if ($this->submissionurl !== '') {
            return true;
        }

        return !empty($this->metadata['hasfiles']) || !empty($this->metadata['proofcount']);
    }

    /**
     * Validate required fields and canonical values.
     */
    public function validate(): void {
        if ($this->id < 0) {
            throw new coding_exception('Submission id cannot be negative.');
        }

        if ($this->challengeid < 0 || $this->courseid < 0 || $this->cmid < 0 || $this->contextid < 0 || $this->userid < 0) {
            throw new coding_exception('Submission identifiers cannot be negative.');
        }

        if ($this->attemptno < 1) {
            throw new coding_exception('Submission attempt number must be at least 1.');
        }

        if ($this->aiassisted && trim($this->ailog) === '' && !$this->is_draft()) {
            throw new coding_exception('AI-assisted non-draft submissions require an AI log.');
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC && $this->status === self::STATUS_DRAFT) {
            throw new coding_exception('Draft submissions cannot be public.');
        }

        if ($this->status === self::STATUS_VALIDATED && $this->integritystate === self::INTEGRITY_UNVERIFIED) {
            throw new coding_exception('Validated submissions cannot remain unverified.');
        }
    }

    /**
     * Touch modification fields.
     *
     * @param int $actorid Acting user id.
     */
    private function touch(int $actorid): void {
        $this->modifiedby = $actorid;
        $this->timemodified = time();
        $this->versionno++;
    }

    /**
     * Set status and update modification fields.
     *
     * @param string $status Status.
     * @param int $actorid Acting user id.
     */
    private function set_status(string $status, int $actorid): void {
        $this->status = $this->normalise_status($status);
        $this->touch($actorid);
        $this->validate();
    }

    /**
     * Get an integer value from raw data.
     *
     * @param array<string, mixed> $data Raw data.
     * @param string $key Data key.
     * @param int $default Default value.
     * @return int
     */
    private function int_value(array $data, string $key, int $default): int {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return (int)$data[$key];
    }

    /**
     * Clean rich text.
     *
     * @param string $text Raw text.
     * @return string
     */
    private function clean_text(string $text): string {
        return clean_param($text, PARAM_RAW);
    }

    /**
     * Normalise URL.
     *
     * @param string $url Raw URL.
     * @return string
     */
    private function normalise_url(string $url): string {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        return clean_param($url, PARAM_URL);
    }

    /**
     * Decode metadata from JSON/string/array.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function decode_metadata(mixed $metadata): array {
        if (is_array($metadata)) {
            return $metadata;
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (!is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Normalise submission status.
     *
     * @param string $status Status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_RESUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ];

        if (!in_array($status, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge submission status: {$status}");
        }

        return $status;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

        $allowed = [
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

        if (!in_array($visibility, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge submission visibility: {$visibility}");
        }

        return $visibility;
    }

    /**
     * Normalise provenance source.
     *
     * @param string $provenance Provenance.
     * @return string
     */
    private function normalise_provenance(string $provenance): string {
        $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);

        $allowed = [
            self::PROVENANCE_HUMAN,
            self::PROVENANCE_AI_ASSISTED,
            self::PROVENANCE_IMPORTED,
            self::PROVENANCE_SYSTEM,
            self::PROVENANCE_ARCHIVE,
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_CHALLENGE,
            self::PROVENANCE_INTEGRITY,
        ];

        if (!in_array($provenance, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge submission provenance: {$provenance}");
        }

        return $provenance;
    }

    /**
     * Normalise integrity state.
     *
     * @param string $integritystate Integrity state.
     * @return string
     */
    private function normalise_integrity_state(string $integritystate): string {
        $integritystate = clean_param($integritystate, PARAM_ALPHANUMEXT);

        $allowed = [
            self::INTEGRITY_UNVERIFIED,
            self::INTEGRITY_HUMAN_REVIEWED,
            self::INTEGRITY_VERIFIED,
            self::INTEGRITY_CONTESTED,
            self::INTEGRITY_INVALIDATED,
            self::INTEGRITY_ARCHIVED,
        ];

        if (!in_array($integritystate, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge submission integrity state: {$integritystate}");
        }

        return $integritystate;
    }

    /**
     * Normalise proof type.
     *
     * @param string $prooftype Proof type.
     * @return string
     */
    private function normalise_proof_type(string $prooftype): string {
        $prooftype = clean_param($prooftype, PARAM_ALPHANUMEXT);

        $allowed = [
            self::PROOF_TEXT,
            self::PROOF_FILE,
            self::PROOF_URL,
            self::PROOF_DATASET,
            self::PROOF_IMAGE,
            self::PROOF_VIDEO,
            self::PROOF_TESTIMONY,
            self::PROOF_OBSERVATION,
            self::PROOF_AI_LOG,
            self::PROOF_DECISION_RECORD,
        ];

        if (!in_array($prooftype, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge proof type: {$prooftype}");
        }

        return $prooftype;
    }
}