<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Challenge-to-archive link value object.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodle_url;
use stdClass;

/**
 * Value object describing a link between a UCKK challenge record and an archive item.
 *
 * This class does not create archive records. Archive creation, validation,
 * versioning, privacy handling, public summaries, and restricted access must
 * remain owned by mod_uckkarchive and integrity-aware services.
 */
final class archive_link {
    /** Source type: challenge instance. */
    public const SOURCE_CHALLENGE = 'challenge';

    /** Source type: challenge submission. */
    public const SOURCE_SUBMISSION = 'submission';

    /** Source type: challenge proof. */
    public const SOURCE_PROOF = 'proof';

    /** Source type: challenge evaluation. */
    public const SOURCE_EVALUATION = 'evaluation';

    /** Source type: challenge integrity summary. */
    public const SOURCE_INTEGRITY = 'integrity';

    /** Source type: challenge public summary. */
    public const SOURCE_PUBLIC_SUMMARY = 'public_summary';

    /** Link status: pending archive creation or validation. */
    public const STATUS_PENDING = 'pending';

    /** Link status: archive item created. */
    public const STATUS_CREATED = 'created';

    /** Link status: archive item validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Link status: archive item rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Link status: archive item revision required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Link status: archive link contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Link status: archive item invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Link status: archive item superseded by a later version. */
    public const STATUS_SUPERSEDED = 'superseded';

    /** Link status: archive link closed. */
    public const STATUS_CLOSED = 'closed';

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

    /** Provenance source: challenge. */
    public const PROVENANCE_CHALLENGE = 'challenge';

    /** Provenance source: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance source: integrity. */
    public const PROVENANCE_INTEGRITY = 'integrity';

    /** Provenance source: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance source: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /**
     * Link id, when persisted by a service.
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
     * Moodle context id.
     *
     * @var int
     */
    private int $contextid = 0;

    /**
     * Source type inside the challenge workflow.
     *
     * @var string
     */
    private string $sourcetype = self::SOURCE_CHALLENGE;

    /**
     * Source object id.
     *
     * @var int
     */
    private int $sourceid = 0;

    /**
     * Archive item id from mod_uckkarchive.
     *
     * @var int
     */
    private int $archiveitemid = 0;

    /**
     * Optional archive module course module id.
     *
     * @var int
     */
    private int $archivecmid = 0;

    /**
     * Optional archive revision id.
     *
     * @var int
     */
    private int $archiverevisionid = 0;

    /**
     * User who owns or produced the source evidence, when applicable.
     *
     * @var int
     */
    private int $userid = 0;

    /**
     * Link title.
     *
     * @var string
     */
    private string $title = '';

    /**
     * Public or internal summary.
     *
     * @var string
     */
    private string $summary = '';

    /**
     * Link status.
     *
     * @var string
     */
    private string $status = self::STATUS_PENDING;

    /**
     * Link visibility.
     *
     * @var string
     */
    private string $visibility = self::VISIBILITY_COURSE;

    /**
     * Provenance source.
     *
     * @var string
     */
    private string $provenance = self::PROVENANCE_CHALLENGE;

    /**
     * Optional provenance hash.
     *
     * @var string
     */
    private string $provenancehash = '';

    /**
     * Creator user id.
     *
     * @var int
     */
    private int $createdby = 0;

    /**
     * Modifier user id.
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
     * Modification timestamp.
     *
     * @var int
     */
    private int $timemodified = 0;

    /**
     * Archive timestamp.
     *
     * @var int
     */
    private int $timearchived = 0;

    /**
     * Version number.
     *
     * @var int
     */
    private int $versionno = 1;

    /**
     * Variable JSON metadata.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass $data Raw data.
     */
    public function __construct(array|stdClass $data = []) {
        $this->load((array)$data);
    }

    /**
     * Create a link from a challenge-level archive item.
     *
     * @param int $challengeid Challenge id.
     * @param int $courseid Course id.
     * @param int $cmid Challenge course module id.
     * @param int $contextid Challenge module context id.
     * @param int $archiveitemid Archive item id.
     * @param int $actorid Acting user id.
     * @param array<string, mixed> $data Optional extra data.
     * @return self
     */
    public static function from_challenge(
        int $challengeid,
        int $courseid,
        int $cmid,
        int $contextid,
        int $archiveitemid,
        int $actorid,
        array $data = []
    ): self {
        $now = time();

        return new self(array_merge($data, [
            'challengeid' => $challengeid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'contextid' => $contextid,
            'sourcetype' => self::SOURCE_CHALLENGE,
            'sourceid' => $challengeid,
            'archiveitemid' => $archiveitemid,
            'status' => $archiveitemid > 0 ? self::STATUS_CREATED : self::STATUS_PENDING,
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'timearchived' => $archiveitemid > 0 ? $now : 0,
        ]));
    }

    /**
     * Create a link from a challenge submission.
     *
     * @param int $challengeid Challenge id.
     * @param int $submissionid Submission id.
     * @param int $courseid Course id.
     * @param int $cmid Challenge course module id.
     * @param int $contextid Challenge module context id.
     * @param int $userid Source user id.
     * @param int $archiveitemid Archive item id.
     * @param int $actorid Acting user id.
     * @param array<string, mixed> $data Optional extra data.
     * @return self
     */
    public static function from_submission(
        int $challengeid,
        int $submissionid,
        int $courseid,
        int $cmid,
        int $contextid,
        int $userid,
        int $archiveitemid,
        int $actorid,
        array $data = []
    ): self {
        $now = time();

        return new self(array_merge($data, [
            'challengeid' => $challengeid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'contextid' => $contextid,
            'sourcetype' => self::SOURCE_SUBMISSION,
            'sourceid' => $submissionid,
            'userid' => $userid,
            'archiveitemid' => $archiveitemid,
            'status' => $archiveitemid > 0 ? self::STATUS_CREATED : self::STATUS_PENDING,
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'timearchived' => $archiveitemid > 0 ? $now : 0,
        ]));
    }

    /**
     * Create a link from a challenge proof.
     *
     * @param int $challengeid Challenge id.
     * @param int $proofid Proof id.
     * @param int $courseid Course id.
     * @param int $cmid Challenge course module id.
     * @param int $contextid Challenge module context id.
     * @param int $userid Source user id.
     * @param int $archiveitemid Archive item id.
     * @param int $actorid Acting user id.
     * @param array<string, mixed> $data Optional extra data.
     * @return self
     */
    public static function from_proof(
        int $challengeid,
        int $proofid,
        int $courseid,
        int $cmid,
        int $contextid,
        int $userid,
        int $archiveitemid,
        int $actorid,
        array $data = []
    ): self {
        $now = time();

        return new self(array_merge($data, [
            'challengeid' => $challengeid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'contextid' => $contextid,
            'sourcetype' => self::SOURCE_PROOF,
            'sourceid' => $proofid,
            'userid' => $userid,
            'archiveitemid' => $archiveitemid,
            'status' => $archiveitemid > 0 ? self::STATUS_CREATED : self::STATUS_PENDING,
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'timearchived' => $archiveitemid > 0 ? $now : 0,
        ]));
    }

    /**
     * Create a model from a persisted record.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Load raw data into this object.
     *
     * @param array<string, mixed> $data Raw data.
     */
    public function load(array $data): void {
        $this->id = $this->int_value($data, 'id', $this->id);
        $this->challengeid = $this->int_value($data, 'challengeid', $this->challengeid);
        $this->courseid = $this->int_value($data, 'courseid', $this->courseid);
        $this->cmid = $this->int_value($data, 'cmid', $this->cmid);
        $this->contextid = $this->int_value($data, 'contextid', $this->contextid);
        $this->sourcetype = $this->normalise_source_type((string)($data['sourcetype'] ?? $this->sourcetype));
        $this->sourceid = $this->int_value($data, 'sourceid', $this->sourceid);
        $this->archiveitemid = $this->int_value($data, 'archiveitemid', $this->archiveitemid);
        $this->archivecmid = $this->int_value($data, 'archivecmid', $this->archivecmid);
        $this->archiverevisionid = $this->int_value($data, 'archiverevisionid', $this->archiverevisionid);
        $this->userid = $this->int_value($data, 'userid', $this->userid);

        $this->title = clean_param((string)($data['title'] ?? $this->title), PARAM_TEXT);
        $this->summary = clean_param((string)($data['summary'] ?? $this->summary), PARAM_RAW);

        $this->status = $this->normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = $this->normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->provenance = $this->normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        $this->createdby = $this->int_value($data, 'createdby', $this->createdby);
        $this->modifiedby = $this->int_value($data, 'modifiedby', $this->modifiedby);
        $this->timecreated = $this->int_value($data, 'timecreated', $this->timecreated);
        $this->timemodified = $this->int_value($data, 'timemodified', $this->timemodified);
        $this->timearchived = $this->int_value($data, 'timearchived', $this->timearchived);
        $this->versionno = max(1, $this->int_value($data, 'versionno', $this->versionno));

        if (array_key_exists('metadata', $data)) {
            $this->metadata = $this->decode_metadata($data['metadata']);
        }

        $this->validate();
    }

    /**
     * Convert this value object to a persistence-ready record.
     *
     * The service layer decides where this record is stored.
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
        $record->sourcetype = $this->sourcetype;
        $record->sourceid = $this->sourceid;
        $record->archiveitemid = $this->archiveitemid;
        $record->archivecmid = $this->archivecmid;
        $record->archiverevisionid = $this->archiverevisionid;
        $record->userid = $this->userid;
        $record->title = $this->title;
        $record->summary = $this->summary;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash;
        $record->createdby = $this->createdby;
        $record->modifiedby = $this->modifiedby;
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;
        $record->timearchived = $this->timearchived;
        $record->versionno = $this->versionno;
        $record->metadata = json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $record;
    }

    /**
     * Mark the linked archive item as created.
     *
     * @param int $archiveitemid Archive item id.
     * @param int $actorid Acting user id.
     * @param int $archivecmid Optional archive module cmid.
     * @param int $archiverevisionid Optional archive revision id.
     */
    public function mark_created(
        int $archiveitemid,
        int $actorid,
        int $archivecmid = 0,
        int $archiverevisionid = 0
    ): void {
        if ($archiveitemid <= 0) {
            throw new coding_exception('Archive item id must be greater than zero.');
        }

        $this->archiveitemid = $archiveitemid;
        $this->archivecmid = max(0, $archivecmid);
        $this->archiverevisionid = max(0, $archiverevisionid);
        $this->timearchived = time();
        $this->set_status(self::STATUS_CREATED, $actorid);
    }

    /**
     * Mark archive link as validated.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_validated(int $actorid): void {
        $this->set_status(self::STATUS_VALIDATED, $actorid);
    }

    /**
     * Mark archive link as rejected.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_rejected(int $actorid): void {
        $this->set_status(self::STATUS_REJECTED, $actorid);
    }

    /**
     * Mark archive link as requiring correction.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_correction_required(int $actorid): void {
        $this->set_status(self::STATUS_CORRECTION_REQUIRED, $actorid);
    }

    /**
     * Mark archive link as contested.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_contested(int $actorid): void {
        $this->set_status(self::STATUS_CONTESTED, $actorid);
    }

    /**
     * Mark archive link as invalidated.
     *
     * @param int $actorid Acting user id.
     */
    public function mark_invalidated(int $actorid): void {
        $this->set_status(self::STATUS_INVALIDATED, $actorid);
    }

    /**
     * Mark archive link as superseded by a newer archive version.
     *
     * @param int $actorid Acting user id.
     * @param int $replacementarchiveitemid Replacement archive item id.
     */
    public function mark_superseded(int $actorid, int $replacementarchiveitemid): void {
        if ($replacementarchiveitemid <= 0) {
            throw new coding_exception('Replacement archive item id must be greater than zero.');
        }

        $this->metadata['replacementarchiveitemid'] = $replacementarchiveitemid;
        $this->set_status(self::STATUS_SUPERSEDED, $actorid);
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
     * Add or replace one metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $value Metadata value.
     * @param int $actorid Acting user id.
     */
    public function set_metadata_value(string $key, mixed $value, int $actorid): void {
        $key = clean_param($key, PARAM_ALPHANUMEXT);

        if ($key === '') {
            throw new coding_exception('Metadata key cannot be empty.');
        }

        $this->metadata[$key] = $value;
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
     * Return link id.
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
     * Return source type.
     *
     * @return string
     */
    public function get_sourcetype(): string {
        return $this->sourcetype;
    }

    /**
     * Return source id.
     *
     * @return int
     */
    public function get_sourceid(): int {
        return $this->sourceid;
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
     * Return whether this link has an archive item.
     *
     * @return bool
     */
    public function has_archive_item(): bool {
        return $this->archiveitemid > 0;
    }

    /**
     * Return whether this link is visible to public contexts.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Return whether this link is restricted.
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
     * Return an archive URL if enough information is known.
     *
     * @return moodle_url|null
     */
    public function get_archive_url(): ?moodle_url {
        if ($this->archivecmid > 0) {
            return new moodle_url('/mod/uckkarchive/view.php', ['id' => $this->archivecmid]);
        }

        if ($this->archiveitemid > 0) {
            return new moodle_url('/mod/uckkarchive/item.php', ['itemid' => $this->archiveitemid]);
        }

        return null;
    }

    /**
     * Validate this archive link.
     */
    public function validate(): void {
        foreach ([
            'id' => $this->id,
            'challengeid' => $this->challengeid,
            'courseid' => $this->courseid,
            'cmid' => $this->cmid,
            'contextid' => $this->contextid,
            'sourceid' => $this->sourceid,
            'archiveitemid' => $this->archiveitemid,
            'archivecmid' => $this->archivecmid,
            'archiverevisionid' => $this->archiverevisionid,
            'userid' => $this->userid,
            'createdby' => $this->createdby,
            'modifiedby' => $this->modifiedby,
        ] as $field => $value) {
            if ($value < 0) {
                throw new coding_exception("Archive link field {$field} cannot be negative.");
            }
        }

        if ($this->challengeid === 0 && $this->sourceid > 0) {
            throw new coding_exception('Archive links with a source id require a challenge id.');
        }

        if ($this->status !== self::STATUS_PENDING && $this->archiveitemid <= 0) {
            throw new coding_exception('Non-pending archive links require an archive item id.');
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC && $this->status === self::STATUS_PENDING) {
            throw new coding_exception('Pending archive links cannot be public.');
        }

        if ($this->versionno < 1) {
            throw new coding_exception('Archive link version number must be at least 1.');
        }
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
     * Return an integer value from raw data.
     *
     * @param array<string, mixed> $data Raw data.
     * @param string $key Key.
     * @param int $default Default.
     * @return int
     */
    private function int_value(array $data, string $key, int $default): int {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return (int)$data[$key];
    }

    /**
     * Decode metadata.
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
     * Normalise source type.
     *
     * @param string $sourcetype Source type.
     * @return string
     */
    private function normalise_source_type(string $sourcetype): string {
        $sourcetype = clean_param($sourcetype, PARAM_ALPHANUMEXT);

        $allowed = [
            self::SOURCE_CHALLENGE,
            self::SOURCE_SUBMISSION,
            self::SOURCE_PROOF,
            self::SOURCE_EVALUATION,
            self::SOURCE_INTEGRITY,
            self::SOURCE_PUBLIC_SUMMARY,
        ];

        if (!in_array($sourcetype, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge archive source type: {$sourcetype}");
        }

        return $sourcetype;
    }

    /**
     * Normalise status.
     *
     * @param string $status Status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_PENDING,
            self::STATUS_CREATED,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_SUPERSEDED,
            self::STATUS_CLOSED,
        ];

        if (!in_array($status, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge archive link status: {$status}");
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
            throw new coding_exception("Invalid UCKK challenge archive link visibility: {$visibility}");
        }

        return $visibility;
    }

    /**
     * Normalise provenance.
     *
     * @param string $provenance Provenance.
     * @return string
     */
    private function normalise_provenance(string $provenance): string {
        $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);

        $allowed = [
            self::PROVENANCE_CHALLENGE,
            self::PROVENANCE_ARCHIVE,
            self::PROVENANCE_INTEGRITY,
            self::PROVENANCE_HUMAN,
            self::PROVENANCE_SYSTEM,
        ];

        if (!in_array($provenance, $allowed, true)) {
            throw new coding_exception("Invalid UCKK challenge archive link provenance: {$provenance}");
        }

        return $provenance;
    }
}