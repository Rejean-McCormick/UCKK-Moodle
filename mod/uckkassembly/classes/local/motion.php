<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for an Assembly motion.
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
 * Local domain model for one UCKK Assembly motion.
 *
 * This class normalises and validates a motion before service/database layers
 * insert, update, render, decide, contest, or archive it.
 *
 * It is intentionally not a controller and not a persistence service.
 */
final class motion {
    /** Motion type: ordinary motion. */
    public const TYPE_MOTION = 'motion';

    /** Motion type: proposal. */
    public const TYPE_PROPOSAL = 'proposal';

    /** Motion type: amendment. */
    public const TYPE_AMENDMENT = 'amendment';

    /** Motion type: objection. */
    public const TYPE_OBJECTION = 'objection';

    /** Motion type: procedural motion. */
    public const TYPE_PROCEDURAL = 'procedural';

    /** Motion type: archive motion. */
    public const TYPE_ARCHIVE = 'archive';

    /** Motion type: integrity motion. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Motion status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Motion status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Motion status: accepted. */
    public const STATUS_ACCEPTED = 'accepted';

    /** Motion status: amended. */
    public const STATUS_AMENDED = 'amended';

    /** Motion status: under deliberation. */
    public const STATUS_UNDER_DELIBERATION = 'under_deliberation';

    /** Motion status: voting. */
    public const STATUS_VOTING = 'voting';

    /** Motion status: decided. */
    public const STATUS_DECIDED = 'decided';

    /** Motion status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Motion status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Motion status: withdrawn. */
    public const STATUS_WITHDRAWN = 'withdrawn';

    /** Motion status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

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

    /** Provenance: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: challenge. */
    public const PROVENANCE_CHALLENGE = 'challenge';

    /** Database id. */
    private int $id = 0;

    /** Parent uckkassembly id. */
    private int $assemblyid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Proposer user id. */
    private int $proposerid = 0;

    /** Optional parent motion id, used for amendments/objections. */
    private int $parentmotionid = 0;

    /** Optional linked decision id. */
    private int $decisionid = 0;

    /** Stable motion type. */
    private string $motiontype = self::TYPE_MOTION;

    /** Motion title. */
    private string $title = '';

    /** Motion body. */
    private string $body = '';

    /** Optional rationale. */
    private string $rationale = '';

    /** Optional requested decision method. */
    private string $decisionmethod = '';

    /** Motion status. */
    private string $status = self::STATUS_DRAFT;

    /** Motion visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Display order. */
    private int $sortorder = 0;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

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

    /** Variable metadata. */
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
     * Build a motion object from a Moodle database record.
     *
     * @param stdClass $record Database record from {uckkassembly_motion}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new motion.
     *
     * @param int $assemblyid Parent Assembly id.
     * @param int $proposerid Proposer user id.
     * @param string $title Motion title.
     * @param string $body Motion body.
     * @param string $motiontype Motion type.
     * @param array<string, mixed> $metadata Optional metadata.
     * @return self
     */
    public static function create(
        int $assemblyid,
        int $proposerid,
        string $title,
        string $body,
        string $motiontype = self::TYPE_MOTION,
        array $metadata = []
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'proposerid' => $proposerid,
            'title' => $title,
            'body' => $body,
            'motiontype' => $motiontype,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Apply raw data to this object.
     *
     * @param array<string, mixed> $data Input data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->assemblyid = max(0, (int)($data['assemblyid'] ?? $data['uckkassemblyid'] ?? $this->assemblyid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $data['course'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->proposerid = max(0, (int)($data['proposerid'] ?? $data['userid'] ?? $this->proposerid));
        $this->parentmotionid = max(0, (int)($data['parentmotionid'] ?? $data['parentid'] ?? $this->parentmotionid));
        $this->decisionid = max(0, (int)($data['decisionid'] ?? $this->decisionid));

        $this->motiontype = self::normalise_motion_type((string)($data['motiontype'] ?? $data['type'] ?? $this->motiontype));
        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->body = trim((string)($data['body'] ?? $data['motion'] ?? $this->body));
        $this->rationale = trim((string)($data['rationale'] ?? $this->rationale));
        $this->decisionmethod = self::normalise_key((string)($data['decisionmethod'] ?? $this->decisionmethod));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->sortorder = max(0, (int)($data['sortorder'] ?? $this->sortorder));

        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);
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
     * Validate this motion.
     *
     * @throws \coding_exception If the motion is invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly motion requires a valid assemblyid.');
        }

        if ($this->proposerid <= 0) {
            throw new \coding_exception('Assembly motion requires a valid proposerid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Assembly motion requires a title.');
        }

        if ($this->body === '') {
            throw new \coding_exception('Assembly motion requires a body.');
        }

        if (!in_array($this->motiontype, self::get_allowed_motion_types(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly motion type: ' . $this->motiontype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly motion status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly motion visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly motion provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to a database record for {uckkassembly_motion}.
     *
     * @param int|null $userid Current user id for createdby/modifiedby defaults.
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
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->proposerid = $this->proposerid;
        $record->parentmotionid = $this->parentmotionid > 0 ? $this->parentmotionid : null;
        $record->decisionid = $this->decisionid > 0 ? $this->decisionid : null;
        $record->motiontype = $this->motiontype;
        $record->title = $this->title;
        $record->body = $this->body;
        $record->rationale = $this->rationale !== '' ? $this->rationale : null;
        $record->decisionmethod = $this->decisionmethod !== '' ? $this->decisionmethod : null;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->sortorder = $this->sortorder;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
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
     * Convert to safe external/export data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->proposerid = $this->proposerid;
        $data->parentmotionid = $this->parentmotionid;
        $data->decisionid = $this->decisionid;
        $data->motiontype = $this->motiontype;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->rationale = $this->rationale;
        $data->decisionmethod = $this->decisionmethod;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->sortorder = $this->sortorder;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        return $data;
    }

    /**
     * Return whether this motion is an amendment.
     *
     * @return bool
     */
    public function is_amendment(): bool {
        return $this->motiontype === self::TYPE_AMENDMENT || $this->parentmotionid > 0;
    }

    /**
     * Return whether this motion is an objection.
     *
     * @return bool
     */
    public function is_objection(): bool {
        return $this->motiontype === self::TYPE_OBJECTION;
    }

    /**
     * Return whether this motion is currently deliberable.
     *
     * @return bool
     */
    public function is_deliberable(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_ACCEPTED,
            self::STATUS_AMENDED,
            self::STATUS_UNDER_DELIBERATION,
        ], true);
    }

    /**
     * Return whether this motion has reached a decision.
     *
     * @return bool
     */
    public function is_decided(): bool {
        return $this->status === self::STATUS_DECIDED || $this->decisionid > 0;
    }

    /**
     * Return whether this motion is integrity restricted.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->motiontype === self::TYPE_INTEGRITY;
    }

    /**
     * Return whether this motion is visible to ordinary course participants.
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
     * Return proposer id.
     *
     * @return int
     */
    public function get_proposerid(): int {
        return $this->proposerid;
    }

    /**
     * Return parent motion id.
     *
     * @return int
     */
    public function get_parentmotionid(): int {
        return $this->parentmotionid;
    }

    /**
     * Return linked decision id.
     *
     * @return int
     */
    public function get_decisionid(): int {
        return $this->decisionid;
    }

    /**
     * Return motion type.
     *
     * @return string
     */
    public function get_motiontype(): string {
        return $this->motiontype;
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
     * Return rationale.
     *
     * @return string
     */
    public function get_rationale(): string {
        return $this->rationale;
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
     * Return sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
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
     * Return one metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set database id.
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
     * Set context references.
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
     * Set parent motion.
     *
     * @param int $parentmotionid Parent motion id.
     * @return self
     */
    public function with_parent_motion(int $parentmotionid): self {
        $clone = clone $this;
        $clone->parentmotionid = max(0, $parentmotionid);
        return $clone;
    }

    /**
     * Link this motion to a decision.
     *
     * @param int $decisionid Decision id.
     * @return self
     */
    public function with_decision(int $decisionid): self {
        $clone = clone $this;
        $clone->decisionid = max(0, $decisionid);
        $clone->status = self::STATUS_DECIDED;
        return $clone;
    }

    /**
     * Set status.
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
     * Set visibility.
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
     * Set metadata.
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
     * Mark this motion as modified by a user.
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
     * Allowed motion types.
     *
     * @return string[]
     */
    public static function get_allowed_motion_types(): array {
        return [
            self::TYPE_MOTION,
            self::TYPE_PROPOSAL,
            self::TYPE_AMENDMENT,
            self::TYPE_OBJECTION,
            self::TYPE_PROCEDURAL,
            self::TYPE_ARCHIVE,
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
            self::STATUS_SUBMITTED,
            self::STATUS_ACCEPTED,
            self::STATUS_AMENDED,
            self::STATUS_UNDER_DELIBERATION,
            self::STATUS_VOTING,
            self::STATUS_DECIDED,
            self::STATUS_REJECTED,
            self::STATUS_CONTESTED,
            self::STATUS_WITHDRAWN,
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
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
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
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_CHALLENGE,
        ];
    }

    /**
     * Normalise motion type.
     *
     * @param string $motiontype Raw type.
     * @return string
     */
    private static function normalise_motion_type(string $motiontype): string {
        $motiontype = clean_param($motiontype, PARAM_ALPHANUMEXT);

        return in_array($motiontype, self::get_allowed_motion_types(), true)
            ? $motiontype
            : self::TYPE_MOTION;
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
     * Normalise a generic key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private static function normalise_key(string $key): string {
        return clean_param($key, PARAM_ALPHANUMEXT);
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