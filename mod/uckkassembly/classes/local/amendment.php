<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for a UCKK assembly amendment.
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
 * Domain object representing one amendment to an assembly motion.
 *
 * This class normalises and validates amendment data before service/database
 * layers insert, update, publish, contest, vote on, minute, or archive it.
 *
 * It does not decide permissions, publish decisions, count votes, or write to
 * the database directly.
 */
final class amendment {
    /** Amendment type: addition. */
    public const TYPE_ADDITION = 'addition';

    /** Amendment type: deletion. */
    public const TYPE_DELETION = 'deletion';

    /** Amendment type: replacement. */
    public const TYPE_REPLACEMENT = 'replacement';

    /** Amendment type: clarification. */
    public const TYPE_CLARIFICATION = 'clarification';

    /** Amendment type: procedural. */
    public const TYPE_PROCEDURAL = 'procedural';

    /** Amendment type: friendly amendment. */
    public const TYPE_FRIENDLY = 'friendly';

    /** Amendment type: counterproposal. */
    public const TYPE_COUNTERPROPOSAL = 'counterproposal';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: pending. */
    public const STATUS_PENDING = 'pending';

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

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

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

    /** Visibility: hidden. */
    public const VISIBILITY_HIDDEN = 'hidden';

    /** Visibility: archived. */
    public const VISIBILITY_ARCHIVED = 'archived';

    /** Provenance: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI-assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Provenance: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Database id. */
    private int $id = 0;

    /** Parent assembly id. */
    private int $assemblyid = 0;

    /** Parent motion id. */
    private int $motionid = 0;

    /** Optional parent amendment id for nested/counter amendments. */
    private int $parentid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle module context id. */
    private int $contextid = 0;

    /** Amendment author user id. */
    private int $userid = 0;

    /** Amendment type. */
    private string $amendmenttype = self::TYPE_ADDITION;

    /** Amendment title. */
    private string $title = '';

    /** Proposed amendment body. */
    private string $body = '';

    /** Rationale for the amendment. */
    private string $rationale = '';

    /** Status. */
    private string $status = self::STATUS_DRAFT;

    /** Visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Provenance. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Version number. */
    private int $versionno = 1;

    /** Sort order. */
    private int $sortorder = 0;

    /** User who created this record. */
    private int $createdby = 0;

    /** User who last modified this record. */
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
     * @param array<string, mixed>|stdClass|null $data Initial amendment data.
     */
    public function __construct(array|stdClass|null $data = null) {
        if ($data !== null) {
            $this->apply_data((array)$data);
        }
    }

    /**
     * Build an amendment object from a Moodle DB record.
     *
     * @param stdClass $record Record from {uckkassembly_amend}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new amendment for a motion.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param int $userid Author user id.
     * @param string $title Amendment title.
     * @param string $body Amendment body.
     * @param string $amendmenttype Amendment type.
     * @return self
     */
    public static function create(
        int $assemblyid,
        int $motionid,
        int $userid,
        string $title,
        string $body,
        string $amendmenttype = self::TYPE_ADDITION
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'userid' => $userid,
            'title' => $title,
            'body' => $body,
            'amendmenttype' => $amendmenttype,
            'status' => self::STATUS_DRAFT,
            'visibility' => self::VISIBILITY_COURSE,
            'provenance' => self::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Apply raw data to this object.
     *
     * @param array<string, mixed> $data Raw input data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->assemblyid = max(0, (int)($data['assemblyid'] ?? $data['uckkassemblyid'] ?? $this->assemblyid));
        $this->motionid = max(0, (int)($data['motionid'] ?? $this->motionid));
        $this->parentid = max(0, (int)($data['parentid'] ?? $data['parentamendmentid'] ?? $this->parentid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));

        $this->amendmenttype = self::normalise_amendment_type(
            (string)($data['amendmenttype'] ?? $data['type'] ?? $this->amendmenttype)
        );

        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->body = trim((string)($data['body'] ?? $data['content'] ?? $data['amendmenttext'] ?? $this->body));
        $this->rationale = trim((string)($data['rationale'] ?? $data['reason'] ?? $this->rationale));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        $this->versionno = max(1, (int)($data['versionno'] ?? $this->versionno));
        $this->sortorder = max(0, (int)($data['sortorder'] ?? $this->sortorder));

        $this->createdby = max(0, (int)($data['createdby'] ?? $this->createdby));
        $this->modifiedby = max(0, (int)($data['modifiedby'] ?? $this->modifiedby));
        $this->timecreated = max(0, (int)($data['timecreated'] ?? $this->timecreated));
        $this->timemodified = max(0, (int)($data['timemodified'] ?? $this->timemodified));

        if (array_key_exists('metadata', $data)) {
            $this->metadata = self::normalise_metadata($data['metadata']);
        }
    }

    /**
     * Validate the amendment.
     *
     * @throws \coding_exception If the amendment is invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly amendment requires a valid assemblyid.');
        }

        if ($this->motionid <= 0) {
            throw new \coding_exception('Assembly amendment requires a valid motionid.');
        }

        if ($this->userid <= 0) {
            throw new \coding_exception('Assembly amendment requires a valid userid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Assembly amendment requires a title.');
        }

        if ($this->body === '') {
            throw new \coding_exception('Assembly amendment requires a body.');
        }

        if (!in_array($this->amendmenttype, self::get_allowed_amendment_types(), true)) {
            throw new \coding_exception('Invalid assembly amendment type: ' . $this->amendmenttype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid assembly amendment status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid assembly amendment visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid assembly amendment provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to database record for {uckkassembly_amend}.
     *
     * @param int|null $userid Acting user id.
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
        $record->motionid = $this->motionid;
        $record->parentid = $this->parentid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->amendmenttype = $this->amendmenttype;
        $record->title = $this->title;
        $record->body = $this->body;
        $record->rationale = $this->rationale;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->versionno = $this->versionno;
        $record->sortorder = $this->sortorder;

        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid > 0 ? $userid : $this->modifiedby;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->metadata = $this->metadata === []
            ? null
            : json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Convert to export-safe data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->parentid = $this->parentid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->amendmenttype = $this->amendmenttype;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->rationale = $this->rationale;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->sortorder = $this->sortorder;
        $data->metadata = $this->metadata;
        return $data;
    }

    /**
     * Whether the amendment is still editable by workflow services.
     *
     * @return bool
     */
    public function is_editable(): bool {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_CORRECTION_REQUIRED,
        ], true);
    }

    /**
     * Whether the amendment is active in deliberation.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE || $this->status === self::STATUS_PENDING_REVIEW;
    }

    /**
     * Whether the amendment has reached a terminal state.
     *
     * @return bool
     */
    public function is_terminal(): bool {
        return in_array($this->status, [
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
            self::STATUS_CANCELLED,
        ], true);
    }

    /**
     * Whether the amendment is contested.
     *
     * @return bool
     */
    public function is_contested(): bool {
        return $this->status === self::STATUS_CONTESTED;
    }

    /**
     * Whether this amendment must be restricted to integrity-capable viewers.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY;
    }

    /**
     * Whether this amendment can be shown to ordinary course participants.
     *
     * @return bool
     */
    public function is_course_visible(): bool {
        return in_array($this->visibility, [
            self::VISIBILITY_COURSE,
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
        ], true);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get assembly id.
     *
     * @return int
     */
    public function get_assemblyid(): int {
        return $this->assemblyid;
    }

    /**
     * Get motion id.
     *
     * @return int
     */
    public function get_motionid(): int {
        return $this->motionid;
    }

    /**
     * Get parent amendment id.
     *
     * @return int
     */
    public function get_parentid(): int {
        return $this->parentid;
    }

    /**
     * Get author user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Get amendment type.
     *
     * @return string
     */
    public function get_amendmenttype(): string {
        return $this->amendmenttype;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function get_body(): string {
        return $this->body;
    }

    /**
     * Get rationale.
     *
     * @return string
     */
    public function get_rationale(): string {
        return $this->rationale;
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
     * Get sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
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
     * Get a metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Return copy with database id.
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
     * Return copy with Moodle context references.
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
     * Return copy with parent amendment id.
     *
     * @param int $parentid Parent amendment id.
     * @return self
     */
    public function with_parentid(int $parentid): self {
        $clone = clone $this;
        $clone->parentid = max(0, $parentid);
        return $clone;
    }

    /**
     * Return copy with updated status.
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
     * Return copy with updated visibility.
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
     * Return copy with updated metadata.
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
     * Return copy marked modified by a user.
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
     * Allowed amendment types.
     *
     * @return string[]
     */
    public static function get_allowed_amendment_types(): array {
        return [
            self::TYPE_ADDITION,
            self::TYPE_DELETION,
            self::TYPE_REPLACEMENT,
            self::TYPE_CLARIFICATION,
            self::TYPE_PROCEDURAL,
            self::TYPE_FRIENDLY,
            self::TYPE_COUNTERPROPOSAL,
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
            self::STATUS_ACTIVE,
            self::STATUS_PENDING,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
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
            self::VISIBILITY_HIDDEN,
            self::VISIBILITY_ARCHIVED,
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
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_ARCHIVE,
        ];
    }

    /**
     * Normalise amendment type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_amendment_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        return in_array($type, self::get_allowed_amendment_types(), true)
            ? $type
            : self::TYPE_ADDITION;
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

        return in_array($status, self::get_allowed_statuses(), true)
            ? $status
            : self::STATUS_DRAFT;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

        return in_array($visibility, self::get_allowed_visibilities(), true)
            ? $visibility
            : self::VISIBILITY_COURSE;
    }

    /**
     * Normalise provenance.
     *
     * @param string $provenance Raw provenance.
     * @return string
     */
    private static function normalise_provenance(string $provenance): string {
        $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);

        return in_array($provenance, self::get_allowed_provenance_sources(), true)
            ? $provenance
            : self::PROVENANCE_HUMAN;
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata array, object, or JSON string.
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

