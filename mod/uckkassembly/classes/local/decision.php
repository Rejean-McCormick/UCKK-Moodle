<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK Assembly decision.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\local;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Local domain model for an Assembly decision.
 *
 * This class normalises and validates one decision record. It does not publish,
 * contest, archive, validate integrity, alter minutes, or decide legitimacy.
 * Those workflow actions belong in capability-checked service/controller code.
 */
final class decision {
    /** Decision type: information. */
    public const TYPE_INFORMATION = 'information';

    /** Decision type: recommendation. */
    public const TYPE_RECOMMENDATION = 'recommendation';

    /** Decision type: validation. */
    public const TYPE_VALIDATION = 'validation';

    /** Decision type: correction. */
    public const TYPE_CORRECTION = 'correction';

    /** Decision type: rejection. */
    public const TYPE_REJECTION = 'rejection';

    /** Decision type: archival. */
    public const TYPE_ARCHIVAL = 'archival';

    /** Decision type: integrity. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: pending. */
    public const STATUS_PENDING = 'pending';

    /** Status: published. */
    public const STATUS_PUBLISHED = 'published';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

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

    /** Provenance: AI-assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Provenance: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: integrity. */
    public const PROVENANCE_INTEGRITY = 'integrity';

    /** Integrity state: unverified. */
    public const INTEGRITY_UNVERIFIED = 'unverified';

    /** Integrity state: human reviewed. */
    public const INTEGRITY_HUMAN_REVIEWED = 'human_reviewed';

    /** Integrity state: verified. */
    public const INTEGRITY_VERIFIED = 'verified';

    /** Integrity state: contested. */
    public const INTEGRITY_CONTESTED = 'contested';

    /** Integrity state: invalidated. */
    public const INTEGRITY_INVALIDATED = 'invalidated';

    /** Integrity state: archived. */
    public const INTEGRITY_ARCHIVED = 'archived';

    /** Database id. */
    private int $id = 0;

    /** Parent Assembly instance id. */
    private int $assemblyid = 0;

    /** Optional source motion id. */
    private int $motionid = 0;

    /** Optional source amendment id. */
    private int $amendid = 0;

    /** Optional source minutes id. */
    private int $minutesid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Decision type. */
    private string $decisiontype = self::TYPE_INFORMATION;

    /** Decision title. */
    private string $title = '';

    /** Decision body. */
    private string $body = '';

    /** Public summary. */
    private string $publicsummary = '';

    /** Decision status. */
    private string $status = self::STATUS_DRAFT;

    /** Decision visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Integrity state. */
    private string $integritystate = self::INTEGRITY_UNVERIFIED;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Optional archive item id. */
    private int $archiveitemid = 0;

    /** Contestability deadline. */
    private int $contestuntil = 0;

    /** Whether contestation remains allowed. */
    private bool $contestable = true;

    /** Display/order value. */
    private int $sortorder = 0;

    /** Record version. */
    private int $versionno = 1;

    /** User who created this record. */
    private int $createdby = 0;

    /** User who last modified this record. */
    private int $modifiedby = 0;

    /** Creation timestamp. */
    private int $timecreated = 0;

    /** Modification timestamp. */
    private int $timemodified = 0;

    /** Variable JSON metadata. */
    private array $metadata = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass|null $data Initial data.
     */
    public function __construct(array|stdClass|null $data = null) {
        if ($data !== null) {
            $this->apply_data((array)$data);
        }
    }

    /**
     * Build a decision object from a DB record.
     *
     * @param stdClass $record Record from {uckkassembly_decision}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new decision.
     *
     * @param int $assemblyid Parent Assembly id.
     * @param string $title Decision title.
     * @param string $body Decision body.
     * @param string $decisiontype Decision type.
     * @return self
     */
    public static function create(
        int $assemblyid,
        string $title,
        string $body,
        string $decisiontype = self::TYPE_INFORMATION
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'title' => $title,
            'body' => $body,
            'decisiontype' => $decisiontype,
            'status' => self::STATUS_DRAFT,
            'visibility' => self::VISIBILITY_COURSE,
            'provenance' => self::PROVENANCE_HUMAN,
            'integritystate' => self::INTEGRITY_UNVERIFIED,
        ]);
    }

    /**
     * Apply raw data to the object.
     *
     * @param array<string, mixed> $data Raw data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->assemblyid = max(0, (int)($data['assemblyid'] ?? $this->assemblyid));
        $this->motionid = max(0, (int)($data['motionid'] ?? $this->motionid));
        $this->amendid = max(0, (int)($data['amendid'] ?? $data['amendmentid'] ?? $this->amendid));
        $this->minutesid = max(0, (int)($data['minutesid'] ?? $this->minutesid));

        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));

        $this->decisiontype = self::normalise_decision_type((string)($data['decisiontype'] ?? $data['type'] ?? $this->decisiontype));
        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->body = trim((string)($data['body'] ?? $data['decisionbody'] ?? $data['description'] ?? $this->body));
        $this->publicsummary = trim((string)($data['publicsummary'] ?? $data['summary'] ?? $this->publicsummary));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->integritystate = self::normalise_integrity_state((string)($data['integritystate'] ?? $this->integritystate));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));

        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);
        $this->archiveitemid = max(0, (int)($data['archiveitemid'] ?? $this->archiveitemid));
        $this->contestuntil = max(0, (int)($data['contestuntil'] ?? $this->contestuntil));
        $this->contestable = self::normalise_bool($data['contestable'] ?? $this->contestable);
        $this->sortorder = max(0, (int)($data['sortorder'] ?? $this->sortorder));
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
     * Validate this decision.
     *
     * @throws \coding_exception
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly decision requires a valid assemblyid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Assembly decision requires a title.');
        }

        if ($this->body === '') {
            throw new \coding_exception('Assembly decision requires a body.');
        }

        if (!in_array($this->decisiontype, self::get_allowed_decision_types(), true)) {
            throw new \coding_exception('Invalid Assembly decision type: ' . $this->decisiontype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid Assembly decision status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid Assembly decision visibility: ' . $this->visibility);
        }

        if (!in_array($this->integritystate, self::get_allowed_integrity_states(), true)) {
            throw new \coding_exception('Invalid Assembly decision integrity state: ' . $this->integritystate);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid Assembly decision provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to a DB record for {uckkassembly_decision}.
     *
     * @param int|null $userid Current user id.
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

        $record->assemblyid = $this->assemblyid;
        $record->motionid = $this->motionid ?: null;
        $record->amendid = $this->amendid ?: null;
        $record->minutesid = $this->minutesid ?: null;
        $record->courseid = $this->courseid ?: null;
        $record->cmid = $this->cmid ?: null;
        $record->contextid = $this->contextid;
        $record->decisiontype = $this->decisiontype;
        $record->title = $this->title;
        $record->body = $this->body;
        $record->publicsummary = $this->publicsummary;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->integritystate = $this->integritystate;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->archiveitemid = $this->archiveitemid ?: null;
        $record->contestuntil = $this->contestuntil;
        $record->contestable = $this->contestable ? 1 : 0;
        $record->sortorder = $this->sortorder;
        $record->versionno = $this->versionno;
        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->metadata = empty($this->metadata)
            ? null
            : json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Convert to export/template-safe data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->amendid = $this->amendid;
        $data->minutesid = $this->minutesid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->decisiontype = $this->decisiontype;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->publicsummary = $this->publicsummary;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->integritystate = $this->integritystate;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->archiveitemid = $this->archiveitemid;
        $data->contestuntil = $this->contestuntil;
        $data->contestable = $this->contestable;
        $data->sortorder = $this->sortorder;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        return $data;
    }

    /**
     * Whether this decision has been published.
     *
     * @return bool
     */
    public function is_published(): bool {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Whether this decision is contested.
     *
     * @return bool
     */
    public function is_contested(): bool {
        return $this->status === self::STATUS_CONTESTED
            || $this->integritystate === self::INTEGRITY_CONTESTED;
    }

    /**
     * Whether this decision is archived.
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === self::STATUS_ARCHIVED
            || $this->integritystate === self::INTEGRITY_ARCHIVED
            || $this->archiveitemid > 0;
    }

    /**
     * Whether contestation is currently open.
     *
     * @param int|null $now Current timestamp.
     * @return bool
     */
    public function is_contestable(?int $now = null): bool {
        if (!$this->contestable) {
            return false;
        }

        if ($this->status !== self::STATUS_PUBLISHED && $this->status !== self::STATUS_CONTESTED) {
            return false;
        }

        if ($this->contestuntil <= 0) {
            return true;
        }

        return ($now ?? time()) <= $this->contestuntil;
    }

    /**
     * Whether this decision requires restricted integrity handling.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->decisiontype === self::TYPE_INTEGRITY
            || $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || in_array($this->integritystate, [
                self::INTEGRITY_CONTESTED,
                self::INTEGRITY_INVALIDATED,
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
     * Return Assembly id.
     *
     * @return int
     */
    public function get_assemblyid(): int {
        return $this->assemblyid;
    }

    /**
     * Return source motion id.
     *
     * @return int
     */
    public function get_motionid(): int {
        return $this->motionid;
    }

    /**
     * Return decision type.
     *
     * @return string
     */
    public function get_decisiontype(): string {
        return $this->decisiontype;
    }

    /**
     * Return title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Return body.
     *
     * @return string
     */
    public function get_body(): string {
        return $this->body;
    }

    /**
     * Return public summary.
     *
     * @return string
     */
    public function get_publicsummary(): string {
        return $this->publicsummary;
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
    public function get_integritystate(): string {
        return $this->integritystate;
    }

    /**
     * Return archive item id.
     *
     * @return int
     */
    public function get_archiveitemid(): int {
        return $this->archiveitemid;
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
     * Return a metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Return a clone with id set.
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
     * Return a clone with context references set.
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
     * Return a clone with source motion id set.
     *
     * @param int $motionid Motion id.
     * @return self
     */
    public function with_motion(int $motionid): self {
        $clone = clone $this;
        $clone->motionid = max(0, $motionid);
        return $clone;
    }

    /**
     * Return a clone with status set.
     *
     * @param string $status Status.
     * @return self
     */
    public function with_status(string $status): self {
        $clone = clone $this;
        $clone->status = self::normalise_status($status);
        return $clone;
    }

    /**
     * Return a clone with visibility set.
     *
     * @param string $visibility Visibility.
     * @return self
     */
    public function with_visibility(string $visibility): self {
        $clone = clone $this;
        $clone->visibility = self::normalise_visibility($visibility);
        return $clone;
    }

    /**
     * Return a clone with integrity state set.
     *
     * @param string $integritystate Integrity state.
     * @return self
     */
    public function with_integrity_state(string $integritystate): self {
        $clone = clone $this;
        $clone->integritystate = self::normalise_integrity_state($integritystate);
        return $clone;
    }

    /**
     * Return a clone with archive item id set.
     *
     * @param int $archiveitemid Archive item id.
     * @return self
     */
    public function with_archive_item(int $archiveitemid): self {
        $clone = clone $this;
        $clone->archiveitemid = max(0, $archiveitemid);
        $clone->status = self::STATUS_ARCHIVED;
        $clone->integritystate = self::INTEGRITY_ARCHIVED;
        return $clone;
    }

    /**
     * Return a clone marked as modified.
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
     * Allowed decision types.
     *
     * @return string[]
     */
    public static function get_allowed_decision_types(): array {
        return [
            self::TYPE_INFORMATION,
            self::TYPE_RECOMMENDATION,
            self::TYPE_VALIDATION,
            self::TYPE_CORRECTION,
            self::TYPE_REJECTION,
            self::TYPE_ARCHIVAL,
            self::TYPE_INTEGRITY,
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
            self::STATUS_PENDING,
            self::STATUS_PUBLISHED,
            self::STATUS_CONTESTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CANCELLED,
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
     * Normalise decision type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_decision_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);
        return in_array($type, self::get_allowed_decision_types(), true) ? $type : self::TYPE_INFORMATION;
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
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata array, object, or JSON.
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

    /**
     * Normalise bool-like data.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private static function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}