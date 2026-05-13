<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK Assembly objection.
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
 * Domain model for an Assembly objection.
 *
 * This class normalises and validates one objection record. It does not decide
 * permissions, publish decisions, close contestations, or archive records.
 */
final class objection {
    /** Database table for objections. */
    public const TABLE = 'uckkassembly_object';

    /** Objection type: procedural. */
    public const TYPE_PROCEDURAL = 'procedural';

    /** Objection type: factual. */
    public const TYPE_FACTUAL = 'factual';

    /** Objection type: ethical. */
    public const TYPE_ETHICAL = 'ethical';

    /** Objection type: evidence. */
    public const TYPE_EVIDENCE = 'evidence';

    /** Objection type: feasibility. */
    public const TYPE_FEASIBILITY = 'feasibility';

    /** Objection type: minority report. */
    public const TYPE_MINORITY = 'minority';

    /** Objection type: legitimacy. */
    public const TYPE_LEGITIMACY = 'legitimacy';

    /** Objection type: integrity. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Target type: motion. */
    public const TARGET_MOTION = 'motion';

    /** Target type: amendment. */
    public const TARGET_AMENDMENT = 'amendment';

    /** Target type: decision. */
    public const TARGET_DECISION = 'decision';

    /** Target type: minutes. */
    public const TARGET_MINUTES = 'minutes';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: accepted. */
    public const STATUS_ACCEPTED = 'accepted';

    /** Status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: resolved. */
    public const STATUS_RESOLVED = 'resolved';

    /** Status: withdrawn. */
    public const STATUS_WITHDRAWN = 'withdrawn';

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

    /** Primary key. */
    private int $id = 0;

    /** Parent assembly id. */
    private int $assemblyid = 0;

    /** Linked motion id, if any. */
    private int $motionid = 0;

    /** Linked amendment id, if any. */
    private int $amendid = 0;

    /** Linked decision id, if any. */
    private int $decisionid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Author user id. */
    private int $userid = 0;

    /** Objection target type. */
    private string $targettype = self::TARGET_MOTION;

    /** Objection type. */
    private string $objectiontype = self::TYPE_PROCEDURAL;

    /** Short title. */
    private string $title = '';

    /** Objection body. */
    private string $body = '';

    /** Proposed correction or resolution. */
    private string $proposedresolution = '';

    /** Current status. */
    private string $status = self::STATUS_DRAFT;

    /** Visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Optional linked integrity case id. */
    private int $integritycaseid = 0;

    /** Optional linked archive item id. */
    private int $archiveitemid = 0;

    /** Record version. */
    private int $versionno = 1;

    /** Creator user id. */
    private int $createdby = 0;

    /** Modifier user id. */
    private int $modifiedby = 0;

    /** Created timestamp. */
    private int $timecreated = 0;

    /** Modified timestamp. */
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
     * Create an objection from a database record.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Create a new objection object.
     *
     * @param int $assemblyid Assembly id.
     * @param int $userid Author user id.
     * @param string $title Objection title.
     * @param string $body Objection body.
     * @param string $targettype Target type.
     * @param int $targetid Target id.
     * @param string $objectiontype Objection type.
     * @return self
     */
    public static function create(
        int $assemblyid,
        int $userid,
        string $title,
        string $body,
        string $targettype = self::TARGET_MOTION,
        int $targetid = 0,
        string $objectiontype = self::TYPE_PROCEDURAL
    ): self {
        $data = [
            'assemblyid' => $assemblyid,
            'userid' => $userid,
            'title' => $title,
            'body' => $body,
            'targettype' => $targettype,
            'objectiontype' => $objectiontype,
        ];

        if ($targettype === self::TARGET_MOTION) {
            $data['motionid'] = $targetid;
        } else if ($targettype === self::TARGET_AMENDMENT) {
            $data['amendid'] = $targetid;
        } else if ($targettype === self::TARGET_DECISION) {
            $data['decisionid'] = $targetid;
        }

        return new self($data);
    }

    /**
     * Apply raw data.
     *
     * @param array<string, mixed> $data Raw data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->assemblyid = max(0, (int)($data['assemblyid'] ?? $this->assemblyid));
        $this->motionid = max(0, (int)($data['motionid'] ?? $this->motionid));
        $this->amendid = max(0, (int)($data['amendid'] ?? $data['amendmentid'] ?? $this->amendid));
        $this->decisionid = max(0, (int)($data['decisionid'] ?? $this->decisionid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));

        $this->targettype = self::normalise_target_type((string)($data['targettype'] ?? $this->targettype));
        $this->objectiontype = self::normalise_objection_type((string)($data['objectiontype'] ?? $data['type'] ?? $this->objectiontype));

        $this->title = self::normalise_title((string)($data['title'] ?? $data['subject'] ?? $this->title));
        $this->body = trim((string)($data['body'] ?? $data['description'] ?? $this->body));
        $this->proposedresolution = trim((string)($data['proposedresolution'] ?? $data['resolution'] ?? $this->proposedresolution));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        $this->integritycaseid = max(0, (int)($data['integritycaseid'] ?? $this->integritycaseid));
        $this->archiveitemid = max(0, (int)($data['archiveitemid'] ?? $this->archiveitemid));
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
     * Validate objection data.
     *
     * @throws \coding_exception If invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly objection requires assemblyid.');
        }

        if ($this->userid <= 0) {
            throw new \coding_exception('Assembly objection requires userid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Assembly objection requires title.');
        }

        if ($this->body === '') {
            throw new \coding_exception('Assembly objection requires body.');
        }

        if (!$this->has_target()) {
            throw new \coding_exception('Assembly objection requires a linked motion, amendment, decision, or minutes target.');
        }

        if (!in_array($this->targettype, self::get_allowed_target_types(), true)) {
            throw new \coding_exception('Invalid objection target type: ' . $this->targettype);
        }

        if (!in_array($this->objectiontype, self::get_allowed_objection_types(), true)) {
            throw new \coding_exception('Invalid objection type: ' . $this->objectiontype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid objection status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid objection visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid objection provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to a database record for {uckkassembly_object}.
     *
     * @param int|null $userid Current user id.
     * @param int|null $now Timestamp.
     * @return stdClass
     */
    public function to_record(?int $userid = null, ?int $now = null): stdClass {
        $this->validate();

        $userid ??= 0;
        $now ??= time();

        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->assemblyid = $this->assemblyid;
        $record->motionid = $this->motionid ?: null;
        $record->amendid = $this->amendid ?: null;
        $record->decisionid = $this->decisionid ?: null;
        $record->courseid = $this->courseid ?: null;
        $record->cmid = $this->cmid ?: null;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->targettype = $this->targettype;
        $record->objectiontype = $this->objectiontype;
        $record->title = $this->title;
        $record->body = $this->body;
        $record->proposedresolution = $this->proposedresolution !== '' ? $this->proposedresolution : null;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->integritycaseid = $this->integritycaseid ?: null;
        $record->archiveitemid = $this->archiveitemid ?: null;
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
     * Convert to export-safe data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->amendid = $this->amendid;
        $data->decisionid = $this->decisionid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->targettype = $this->targettype;
        $data->objectiontype = $this->objectiontype;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->proposedresolution = $this->proposedresolution;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->integritycaseid = $this->integritycaseid;
        $data->archiveitemid = $this->archiveitemid;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        return $data;
    }

    /**
     * Whether the objection has a valid target.
     *
     * @return bool
     */
    public function has_target(): bool {
        return match ($this->targettype) {
            self::TARGET_MOTION => $this->motionid > 0,
            self::TARGET_AMENDMENT => $this->amendid > 0,
            self::TARGET_DECISION => $this->decisionid > 0,
            self::TARGET_MINUTES => $this->assemblyid > 0,
            default => false,
        };
    }

    /**
     * Whether this objection is integrity-restricted.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->objectiontype === self::TYPE_INTEGRITY
            || $this->integritycaseid > 0;
    }

    /**
     * Whether this objection is public-facing.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Whether this objection is still open.
     *
     * @return bool
     */
    public function is_open(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
        ], true);
    }

    /**
     * Whether this objection has been resolved.
     *
     * @return bool
     */
    public function is_resolved(): bool {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_RESOLVED,
            self::STATUS_WITHDRAWN,
            self::STATUS_ARCHIVED,
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
     * Return assembly id.
     *
     * @return int
     */
    public function get_assemblyid(): int {
        return $this->assemblyid;
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
     * Return target type.
     *
     * @return string
     */
    public function get_targettype(): string {
        return $this->targettype;
    }

    /**
     * Return objection type.
     *
     * @return string
     */
    public function get_objectiontype(): string {
        return $this->objectiontype;
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
     * Return proposed resolution.
     *
     * @return string
     */
    public function get_proposedresolution(): string {
        return $this->proposedresolution;
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
     * Return copy with id.
     *
     * @param int $id Id.
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
     * Return copy with status.
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
     * Return copy with visibility.
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
     * Return copy with proposed resolution.
     *
     * @param string $resolution Proposed resolution.
     * @return self
     */
    public function with_proposedresolution(string $resolution): self {
        $clone = clone $this;
        $clone->proposedresolution = trim($resolution);
        return $clone;
    }

    /**
     * Return copy linked to an integrity case.
     *
     * @param int $caseid Integrity case id.
     * @return self
     */
    public function with_integrity_case(int $caseid): self {
        $clone = clone $this;
        $clone->integritycaseid = max(0, $caseid);

        if ($clone->integritycaseid > 0 && $clone->visibility !== self::VISIBILITY_RESTRICTED_INTEGRITY) {
            $clone->visibility = self::VISIBILITY_RESTRICTED_INTEGRITY;
        }

        return $clone;
    }

    /**
     * Return copy linked to an archive item.
     *
     * @param int $archiveitemid Archive item id.
     * @return self
     */
    public function with_archive_item(int $archiveitemid): self {
        $clone = clone $this;
        $clone->archiveitemid = max(0, $archiveitemid);
        return $clone;
    }

    /**
     * Return copy with metadata.
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
     * Return copy marked as modified.
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
     * Allowed target types.
     *
     * @return string[]
     */
    public static function get_allowed_target_types(): array {
        return [
            self::TARGET_MOTION,
            self::TARGET_AMENDMENT,
            self::TARGET_DECISION,
            self::TARGET_MINUTES,
        ];
    }

    /**
     * Allowed objection types.
     *
     * @return string[]
     */
    public static function get_allowed_objection_types(): array {
        return [
            self::TYPE_PROCEDURAL,
            self::TYPE_FACTUAL,
            self::TYPE_ETHICAL,
            self::TYPE_EVIDENCE,
            self::TYPE_FEASIBILITY,
            self::TYPE_MINORITY,
            self::TYPE_LEGITIMACY,
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
            self::STATUS_PENDING_REVIEW,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_RESOLVED,
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
            self::VISIBILITY_USER,
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COURSE,
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
        ];
    }

    /**
     * Normalise target type.
     *
     * @param string $targettype Raw target type.
     * @return string
     */
    private static function normalise_target_type(string $targettype): string {
        $targettype = clean_param($targettype, PARAM_ALPHANUMEXT);
        return in_array($targettype, self::get_allowed_target_types(), true)
            ? $targettype
            : self::TARGET_MOTION;
    }

    /**
     * Normalise objection type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_objection_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);
        return in_array($type, self::get_allowed_objection_types(), true)
            ? $type
            : self::TYPE_PROCEDURAL;
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

