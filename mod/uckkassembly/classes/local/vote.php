<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK assembly vote or reading.
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
 * Domain model for one Assembly vote or reading.
 *
 * A vote is a recorded contribution to collective reading. It does not itself
 * publish a decision, close a contestation, certify legitimacy, or archive the
 * Assembly. Those actions belong to service-layer workflows.
 */
final class vote {
    /** Vote value: for. */
    public const VALUE_FOR = 'for';

    /** Vote value: against. */
    public const VALUE_AGAINST = 'against';

    /** Vote value: abstain. */
    public const VALUE_ABSTAIN = 'abstain';

    /** Vote value: block. */
    public const VALUE_BLOCK = 'block';

    /** Vote status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Vote status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Vote status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Vote status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Vote status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Vote status: archived. */
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

    /** Provenance: AI assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Database id. */
    private int $id = 0;

    /** Parent assembly id. */
    private int $assemblyid = 0;

    /** Related motion id. */
    private int $motionid = 0;

    /** Optional related amendment id. */
    private int $amendmentid = 0;

    /** Optional related decision id. */
    private int $decisionid = 0;

    /** Course id. */
    private int $courseid = 0;

    /** Course module id. */
    private int $cmid = 0;

    /** Module context id. */
    private int $contextid = 0;

    /** Voting user id. */
    private int $userid = 0;

    /** Vote value. */
    private string $votevalue = self::VALUE_ABSTAIN;

    /** Optional rationale. */
    private string $rationale = '';

    /** Vote status. */
    private string $status = self::STATUS_DRAFT;

    /** Vote visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Version number. */
    private int $versionno = 1;

    /** User who created the vote. */
    private int $createdby = 0;

    /** User who last modified the vote. */
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
     * Build a vote from a database record.
     *
     * @param stdClass $record Record from {uckkassembly_vote}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new vote.
     *
     * @param int $assemblyid Assembly id.
     * @param int $motionid Motion id.
     * @param int $userid User id.
     * @param string $votevalue Vote value.
     * @param string $rationale Optional rationale.
     * @return self
     */
    public static function create(
        int $assemblyid,
        int $motionid,
        int $userid,
        string $votevalue,
        string $rationale = ''
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'motionid' => $motionid,
            'userid' => $userid,
            'votevalue' => $votevalue,
            'rationale' => $rationale,
            'status' => self::STATUS_SUBMITTED,
            'visibility' => self::VISIBILITY_COURSE,
            'provenance' => self::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Apply raw data to this vote object.
     *
     * @param array<string, mixed> $data Input data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->assemblyid = max(0, (int)($data['assemblyid'] ?? $data['uckkassemblyid'] ?? $this->assemblyid));
        $this->motionid = max(0, (int)($data['motionid'] ?? $this->motionid));
        $this->amendmentid = max(0, (int)($data['amendmentid'] ?? $data['amendid'] ?? $this->amendmentid));
        $this->decisionid = max(0, (int)($data['decisionid'] ?? $this->decisionid));

        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $data['votedby'] ?? $this->userid));

        $this->votevalue = self::normalise_vote_value((string)($data['votevalue'] ?? $data['value'] ?? $this->votevalue));
        $this->rationale = trim((string)($data['rationale'] ?? $data['reason'] ?? $this->rationale));
        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
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
     * Validate this vote.
     *
     * @throws \coding_exception If invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly vote requires a valid assemblyid.');
        }

        if ($this->motionid <= 0 && $this->amendmentid <= 0 && $this->decisionid <= 0) {
            throw new \coding_exception('Assembly vote requires a motionid, amendmentid, or decisionid.');
        }

        if ($this->userid <= 0) {
            throw new \coding_exception('Assembly vote requires a valid userid.');
        }

        if (!in_array($this->votevalue, self::get_allowed_vote_values(), true)) {
            throw new \coding_exception('Invalid UCKK assembly vote value: ' . $this->votevalue);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid UCKK assembly vote status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid UCKK assembly vote visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid UCKK assembly vote provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert this vote to a database record for {uckkassembly_vote}.
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
        $record->amendmentid = $this->amendmentid ?: null;
        $record->decisionid = $this->decisionid ?: null;
        $record->courseid = $this->courseid ?: null;
        $record->cmid = $this->cmid ?: null;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->votedby = $this->userid;
        $record->votevalue = $this->votevalue;
        $record->rationale = $this->rationale;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
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
     * Convert this vote to safe export data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();
        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->amendmentid = $this->amendmentid;
        $data->decisionid = $this->decisionid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->votevalue = $this->votevalue;
        $data->votelabel = self::get_vote_label($this->votevalue);
        $data->rationale = $this->rationale;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        $data->isblock = $this->is_block();
        $data->isabstention = $this->is_abstention();
        $data->iscountable = $this->is_countable();
        return $data;
    }

    /**
     * Return whether this is a blocking vote.
     *
     * @return bool
     */
    public function is_block(): bool {
        return $this->votevalue === self::VALUE_BLOCK;
    }

    /**
     * Return whether this is an abstention.
     *
     * @return bool
     */
    public function is_abstention(): bool {
        return $this->votevalue === self::VALUE_ABSTAIN;
    }

    /**
     * Return whether this vote should count in readings.
     *
     * @return bool
     */
    public function is_countable(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_VALIDATED,
            self::STATUS_CONTESTED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Return whether this vote is restricted.
     *
     * @return bool
     */
    public function is_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY;
    }

    /**
     * Return vote id.
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
     * Return amendment id.
     *
     * @return int
     */
    public function get_amendmentid(): int {
        return $this->amendmentid;
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
     * Return user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return vote value.
     *
     * @return string
     */
    public function get_votevalue(): string {
        return $this->votevalue;
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
     * Return a copy with id set.
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
     * Return a copy with Moodle context references set.
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
     * Return a copy with status set.
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
     * Return a copy with visibility set.
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
     * Return a copy with metadata set.
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
     * Return a copy marked modified.
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
     * Allowed vote values.
     *
     * @return string[]
     */
    public static function get_allowed_vote_values(): array {
        return [
            self::VALUE_FOR,
            self::VALUE_AGAINST,
            self::VALUE_ABSTAIN,
            self::VALUE_BLOCK,
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
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CONTESTED,
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
        ];
    }

    /**
     * Return localised label for a vote value.
     *
     * @param string $votevalue Vote value.
     * @return string
     */
    public static function get_vote_label(string $votevalue): string {
        $votevalue = self::normalise_vote_value($votevalue);
        $key = 'vote:' . $votevalue;

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $votevalue));
    }

    /**
     * Normalise vote value.
     *
     * @param string $votevalue Raw value.
     * @return string
     */
    private static function normalise_vote_value(string $votevalue): string {
        $votevalue = clean_param($votevalue, PARAM_ALPHANUMEXT);

        return in_array($votevalue, self::get_allowed_vote_values(), true)
            ? $votevalue
            : self::VALUE_ABSTAIN;
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

