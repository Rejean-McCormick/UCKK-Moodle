`mod/uckkassembly/classes/local/minutes.php`

Assemblies must support decision records, minutes/procès-verbaux, contestability, integrity review, and archive output.  UCKK-owned records should keep stable fields as first-class columns and use JSON `metadata` only for variable data. 

```php
<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for UCKK Assembly minutes.
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
 * Domain model for one Assembly minutes/procès-verbal record.
 *
 * This class normalises and validates minutes data before service/database
 * layers insert, update, render, archive, export, or privacy-filter it.
 *
 * It must not publish decisions, resolve contestations, validate integrity,
 * or alter archive history.
 */
final class minutes {
    /** Table name without Moodle prefix. */
    public const TABLE = 'uckkassembly_minutes';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Status: archived. */
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

    /** Minutes type: working notes. */
    public const TYPE_WORKING = 'working';

    /** Minutes type: official record. */
    public const TYPE_OFFICIAL = 'official';

    /** Minutes type: public summary. */
    public const TYPE_PUBLIC_SUMMARY = 'public_summary';

    /** Minutes type: restricted integrity summary. */
    public const TYPE_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Database id. */
    private int $id = 0;

    /** Parent assembly id. */
    private int $assemblyid = 0;

    /** Optional linked motion id. */
    private int $motionid = 0;

    /** Optional linked decision id. */
    private int $decisionid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Optional user owner / responsible recorder. */
    private int $userid = 0;

    /** Minutes type. */
    private string $minutestype = self::TYPE_WORKING;

    /** Title. */
    private string $title = '';

    /** Main minutes body. */
    private string $body = '';

    /** Safe public summary. */
    private string $publicsummary = '';

    /** Restricted notes. */
    private string $privatenotes = '';

    /** Decision summary. */
    private string $decisionsummary = '';

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

    /** Creator user id. */
    private int $createdby = 0;

    /** Last modifier user id. */
    private int $modifiedby = 0;

    /** Creation timestamp. */
    private int $timecreated = 0;

    /** Modification timestamp. */
    private int $timemodified = 0;

    /** Optional publication timestamp. */
    private int $timepublished = 0;

    /** JSON metadata. */
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
     * Create from a database record.
     *
     * @param stdClass $record DB record from {uckkassembly_minutes}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Create new minutes for an assembly.
     *
     * @param int $assemblyid Assembly id.
     * @param string $title Title.
     * @param string $body Minutes body.
     * @param string $minutestype Minutes type.
     * @return self
     */
    public static function create(
        int $assemblyid,
        string $title,
        string $body,
        string $minutestype = self::TYPE_WORKING
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'title' => $title,
            'body' => $body,
            'minutestype' => $minutestype,
        ]);
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
        $this->decisionid = max(0, (int)($data['decisionid'] ?? $this->decisionid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));

        $this->minutestype = self::normalise_minutes_type((string)($data['minutestype'] ?? $data['type'] ?? $this->minutestype));
        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->body = trim((string)($data['body'] ?? $data['minutesbody'] ?? $this->body));
        $this->publicsummary = trim((string)($data['publicsummary'] ?? $this->publicsummary));
        $this->privatenotes = trim((string)($data['privatenotes'] ?? $this->privatenotes));
        $this->decisionsummary = trim((string)($data['decisionsummary'] ?? $this->decisionsummary));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        $this->versionno = max(1, (int)($data['versionno'] ?? $this->versionno));
        $this->createdby = max(0, (int)($data['createdby'] ?? $this->createdby));
        $this->modifiedby = max(0, (int)($data['modifiedby'] ?? $this->modifiedby));
        $this->timecreated = max(0, (int)($data['timecreated'] ?? $this->timecreated));
        $this->timemodified = max(0, (int)($data['timemodified'] ?? $this->timemodified));
        $this->timepublished = max(0, (int)($data['timepublished'] ?? $this->timepublished));

        if (array_key_exists('metadata', $data)) {
            $this->metadata = self::normalise_metadata($data['metadata']);
        }
    }

    /**
     * Validate this minutes object.
     *
     * @throws \coding_exception If invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly minutes require a valid assemblyid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Assembly minutes require a title.');
        }

        if ($this->body === '') {
            throw new \coding_exception('Assembly minutes require a body.');
        }

        if (!in_array($this->minutestype, self::get_allowed_minutes_types(), true)) {
            throw new \coding_exception('Invalid Assembly minutes type: ' . $this->minutestype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid Assembly minutes status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid Assembly minutes visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid Assembly minutes provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to a DB record for {uckkassembly_minutes}.
     *
     * @param int|null $userid Current user id.
     * @param int|null $now Timestamp.
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
        $record->decisionid = $this->decisionid ?: null;
        $record->courseid = $this->courseid ?: null;
        $record->cmid = $this->cmid ?: null;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid ?: null;
        $record->minutestype = $this->minutestype;
        $record->title = $this->title;
        $record->body = $this->body;
        $record->publicsummary = $this->publicsummary;
        $record->privatenotes = $this->privatenotes;
        $record->decisionsummary = $this->decisionsummary;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->versionno = $this->versionno;
        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->timepublished = $this->timepublished ?: null;
        $record->metadata = $this->metadata === []
            ? null
            : json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Convert to template/export-safe data.
     *
     * @param bool $includerestricted Whether restricted fields may be exported.
     * @return stdClass
     */
    public function to_export(bool $includerestricted = false): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->decisionid = $this->decisionid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->minutestype = $this->minutestype;
        $data->title = $this->title;
        $data->body = $this->body;
        $data->publicsummary = $this->publicsummary;
        $data->decisionsummary = $this->decisionsummary;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->timecreated = $this->timecreated;
        $data->timemodified = $this->timemodified;
        $data->timepublished = $this->timepublished;
        $data->metadata = $this->metadata;

        if ($includerestricted) {
            $data->privatenotes = $this->privatenotes;
            $data->createdby = $this->createdby;
            $data->modifiedby = $this->modifiedby;
        } else {
            $data->privatenotes = '';
        }

        return $data;
    }

    /**
     * Return whether minutes are publishable.
     *
     * @return bool
     */
    public function is_publishable(): bool {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_VALIDATED,
        ], true);
    }

    /**
     * Return whether minutes are already published.
     *
     * @return bool
     */
    public function is_published(): bool {
        return $this->timepublished > 0
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_VALIDATED, self::STATUS_CLOSED], true);
    }

    /**
     * Return whether minutes contain restricted material.
     *
     * @return bool
     */
    public function is_restricted(): bool {
        return in_array($this->visibility, [
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
            self::VISIBILITY_PRIVATE,
        ], true);
    }

    /**
     * Return whether minutes are integrity-sensitive.
     *
     * @return bool
     */
    public function is_integrity_sensitive(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->minutestype === self::TYPE_RESTRICTED_INTEGRITY;
    }

    /**
     * Return whether minutes are archived.
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === self::STATUS_ARCHIVED;
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
     * Return motion id.
     *
     * @return int
     */
    public function get_motionid(): int {
        return $this->motionid;
    }

    /**
     * Return decision id.
     *
     * @return int
     */
    public function get_decisionid(): int {
        return $this->decisionid;
    }

    /**
     * Return minutes type.
     *
     * @return string
     */
    public function get_minutestype(): string {
        return $this->minutestype;
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
     * Return private notes.
     *
     * @return string
     */
    public function get_privatenotes(): string {
        return $this->privatenotes;
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
     * Clone with id.
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
     * Clone with Moodle context references.
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
     * Clone with linked motion.
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
     * Clone with linked decision.
     *
     * @param int $decisionid Decision id.
     * @return self
     */
    public function with_decision(int $decisionid): self {
        $clone = clone $this;
        $clone->decisionid = max(0, $decisionid);
        return $clone;
    }

    /**
     * Clone with status.
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
     * Clone with visibility.
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
     * Clone with public summary.
     *
     * @param string $publicsummary Public summary.
     * @return self
     */
    public function with_publicsummary(string $publicsummary): self {
        $clone = clone $this;
        $clone->publicsummary = trim($publicsummary);
        return $clone;
    }

    /**
     * Clone with metadata.
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
     * Mark as published.
     *
     * @param int|null $now Timestamp.
     * @return self
     */
    public function mark_published(?int $now = null): self {
        $clone = clone $this;
        $clone->timepublished = $now ?? time();

        if ($clone->status === self::STATUS_DRAFT || $clone->status === self::STATUS_PENDING_REVIEW) {
            $clone->status = self::STATUS_ACTIVE;
        }

        return $clone;
    }

    /**
     * Mark as modified.
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
     * Allowed minutes types.
     *
     * @return string[]
     */
    public static function get_allowed_minutes_types(): array {
        return [
            self::TYPE_WORKING,
            self::TYPE_OFFICIAL,
            self::TYPE_PUBLIC_SUMMARY,
            self::TYPE_RESTRICTED_INTEGRITY,
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
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_CLOSED,
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
        ];
    }

    /**
     * Normalise minutes type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_minutes_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        return in_array($type, self::get_allowed_minutes_types(), true)
            ? $type
            : self::TYPE_WORKING;
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
```
