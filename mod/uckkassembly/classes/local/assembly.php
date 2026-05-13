<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for a UCKK assembly instance.
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
 * Local domain model for one Assemblée.
 *
 * This class normalises and validates an assembly record. It does not record
 * votes, publish decisions, resolve contests, archive minutes, or perform
 * integrity actions. Those operations belong to capability-checked services.
 */
final class assembly {
    /** Assembly type: knowledge assembly. */
    public const TYPE_SAVOIRS = 'savoirs';

    /** Assembly type: challenge assembly. */
    public const TYPE_DEFIS = 'defis';

    /** Assembly type: player assembly. */
    public const TYPE_JOUEURS = 'joueurs';

    /** Assembly type: builder assembly. */
    public const TYPE_BATISSEURS = 'batisseurs';

    /** Assembly type: inquisitor assembly. */
    public const TYPE_INQUISITEURS = 'inquisiteurs';

    /** Assembly type: Grand Jeu assembly. */
    public const TYPE_GRAND_JEU = 'grand_jeu';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

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

    /** Archive policy: none. */
    public const ARCHIVE_NONE = 'none';

    /** Archive policy: summary. */
    public const ARCHIVE_SUMMARY = 'summary';

    /** Archive policy: full. */
    public const ARCHIVE_FULL = 'full';

    /** Voting method: simple majority. */
    public const VOTING_SIMPLE_MAJORITY = 'simple_majority';

    /** Voting method: qualified majority. */
    public const VOTING_QUALIFIED_MAJORITY = 'qualified_majority';

    /** Voting method: consensus. */
    public const VOTING_CONSENSUS = 'consensus';

    /** Voting method: consent. */
    public const VOTING_CONSENT = 'consent';

    /** Database id. */
    private int $id = 0;

    /** Moodle course id. Stored in the activity table as `course`. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Assembly name. */
    private string $name = '';

    /** Assembly intro. */
    private string $intro = '';

    /** Assembly intro format. */
    private int $introformat = FORMAT_HTML;

    /** Canonical assembly type. */
    private string $assemblytype = self::TYPE_SAVOIRS;

    /** Assembly purpose. */
    private string $purpose = '';

    /** Status. */
    private string $status = self::STATUS_DRAFT;

    /** Visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Opening timestamp. */
    private int $timeopen = 0;

    /** Closing timestamp. */
    private int $timeclose = 0;

    /** Motion proposal deadline. */
    private int $timemotionclose = 0;

    /** Voting opening timestamp. */
    private int $timevoteopen = 0;

    /** Voting closing timestamp. */
    private int $timevoteclose = 0;

    /** Minimum participants or votes required. */
    private int $quorum = 0;

    /** Voting method. */
    private string $votingmethod = self::VOTING_SIMPLE_MAJORITY;

    /** Decision threshold as percentage, 0..100. */
    private float $decisionthreshold = 50.0;

    /** Whether participants may propose motions. */
    private bool $allowmotions = true;

    /** Whether participants may amend motions. */
    private bool $allowamendments = true;

    /** Whether decisions may be contested. */
    private bool $allowcontests = true;

    /** Whether integrity review is required. */
    private bool $integrityrequired = true;

    /** Archive policy. */
    private string $archivepolicy = self::ARCHIVE_SUMMARY;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Record version. */
    private int $versionno = 1;

    /** Creator user id. */
    private int $createdby = 0;

    /** Modifier user id. */
    private int $modifiedby = 0;

    /** Creation timestamp. */
    private int $timecreated = 0;

    /** Modification timestamp. */
    private int $timemodified = 0;

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
     * Build an assembly object from a database record.
     *
     * @param stdClass $record Record from {uckkassembly}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new assembly object.
     *
     * @param int $courseid Moodle course id.
     * @param string $name Assembly name.
     * @param string $assemblytype Canonical assembly type.
     * @return self
     */
    public static function create(
        int $courseid,
        string $name,
        string $assemblytype = self::TYPE_SAVOIRS
    ): self {
        return new self([
            'course' => $courseid,
            'name' => $name,
            'assemblytype' => $assemblytype,
            'status' => self::STATUS_DRAFT,
            'visibility' => self::VISIBILITY_COURSE,
            'archivepolicy' => self::ARCHIVE_SUMMARY,
            'provenance' => self::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Apply raw data to this object.
     *
     * @param array<string, mixed> $data Raw input.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->courseid = max(0, (int)($data['course'] ?? $data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));

        $this->name = self::normalise_name((string)($data['name'] ?? $this->name));
        $this->intro = trim((string)($data['intro'] ?? $this->intro));
        $this->introformat = (int)($data['introformat'] ?? $this->introformat);

        $this->assemblytype = self::normalise_assembly_type((string)($data['assemblytype'] ?? $data['type'] ?? $this->assemblytype));
        $this->purpose = trim((string)($data['purpose'] ?? $this->purpose));
        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));

        $this->timeopen = max(0, (int)($data['timeopen'] ?? $this->timeopen));
        $this->timeclose = max(0, (int)($data['timeclose'] ?? $this->timeclose));
        $this->timemotionclose = max(0, (int)($data['timemotionclose'] ?? $this->timemotionclose));
        $this->timevoteopen = max(0, (int)($data['timevoteopen'] ?? $this->timevoteopen));
        $this->timevoteclose = max(0, (int)($data['timevoteclose'] ?? $this->timevoteclose));

        $this->quorum = max(0, (int)($data['quorum'] ?? $this->quorum));
        $this->votingmethod = self::normalise_voting_method((string)($data['votingmethod'] ?? $this->votingmethod));
        $this->decisionthreshold = self::normalise_threshold($data['decisionthreshold'] ?? $this->decisionthreshold);

        $this->allowmotions = self::normalise_bool($data['allowmotions'] ?? $this->allowmotions);
        $this->allowamendments = self::normalise_bool($data['allowamendments'] ?? $this->allowamendments);
        $this->allowcontests = self::normalise_bool($data['allowcontests'] ?? $this->allowcontests);
        $this->integrityrequired = self::normalise_bool($data['integrityrequired'] ?? $this->integrityrequired);

        $this->archivepolicy = self::normalise_archive_policy((string)($data['archivepolicy'] ?? $this->archivepolicy));
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
     * Validate this assembly.
     *
     * @throws \coding_exception If invalid.
     */
    public function validate(): void {
        if ($this->courseid <= 0) {
            throw new \coding_exception('UCKK assembly requires a valid course id.');
        }

        if ($this->name === '') {
            throw new \coding_exception('UCKK assembly requires a name.');
        }

        if (!in_array($this->assemblytype, self::get_allowed_assembly_types(), true)) {
            throw new \coding_exception('Invalid UCKK assembly type: ' . $this->assemblytype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid UCKK assembly status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid UCKK assembly visibility: ' . $this->visibility);
        }

        if (!in_array($this->archivepolicy, self::get_allowed_archive_policies(), true)) {
            throw new \coding_exception('Invalid UCKK assembly archive policy: ' . $this->archivepolicy);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid UCKK assembly provenance: ' . $this->provenance);
        }

        if ($this->timeopen > 0 && $this->timeclose > 0 && $this->timeclose <= $this->timeopen) {
            throw new \coding_exception('UCKK assembly close time must be after open time.');
        }

        if ($this->timevoteopen > 0 && $this->timevoteclose > 0 && $this->timevoteclose <= $this->timevoteopen) {
            throw new \coding_exception('UCKK assembly vote close time must be after vote open time.');
        }
    }

    /**
     * Convert to a Moodle database record for {uckkassembly}.
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

        $record->course = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->name = $this->name;
        $record->intro = $this->intro;
        $record->introformat = $this->introformat;
        $record->assemblytype = $this->assemblytype;
        $record->purpose = $this->purpose;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->timeopen = $this->timeopen;
        $record->timeclose = $this->timeclose;
        $record->timemotionclose = $this->timemotionclose;
        $record->timevoteopen = $this->timevoteopen;
        $record->timevoteclose = $this->timevoteclose;
        $record->quorum = $this->quorum;
        $record->votingmethod = $this->votingmethod;
        $record->decisionthreshold = $this->decisionthreshold;
        $record->allowmotions = $this->allowmotions ? 1 : 0;
        $record->allowamendments = $this->allowamendments ? 1 : 0;
        $record->allowcontests = $this->allowcontests ? 1 : 0;
        $record->integrityrequired = $this->integrityrequired ? 1 : 0;
        $record->archivepolicy = $this->archivepolicy;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
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
     * Convert to exportable structured data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        return (object)[
            'id' => $this->id,
            'courseid' => $this->courseid,
            'cmid' => $this->cmid,
            'contextid' => $this->contextid,
            'name' => $this->name,
            'intro' => $this->intro,
            'introformat' => $this->introformat,
            'assemblytype' => $this->assemblytype,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'timeopen' => $this->timeopen,
            'timeclose' => $this->timeclose,
            'timemotionclose' => $this->timemotionclose,
            'timevoteopen' => $this->timevoteopen,
            'timevoteclose' => $this->timevoteclose,
            'quorum' => $this->quorum,
            'votingmethod' => $this->votingmethod,
            'decisionthreshold' => $this->decisionthreshold,
            'allowmotions' => $this->allowmotions,
            'allowamendments' => $this->allowamendments,
            'allowcontests' => $this->allowcontests,
            'integrityrequired' => $this->integrityrequired,
            'archivepolicy' => $this->archivepolicy,
            'provenance' => $this->provenance,
            'provenancehash' => $this->provenancehash,
            'versionno' => $this->versionno,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Whether the assembly is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether the assembly is closed.
     *
     * @return bool
     */
    public function is_closed(): bool {
        return in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
            self::STATUS_CANCELLED,
        ], true);
    }

    /**
     * Whether motions can currently be proposed.
     *
     * @param int|null $now Current timestamp.
     * @return bool
     */
    public function is_open_for_motions(?int $now = null): bool {
        $now ??= time();

        if (!$this->allowmotions || !$this->is_active()) {
            return false;
        }

        if ($this->timeopen > 0 && $now < $this->timeopen) {
            return false;
        }

        if ($this->timemotionclose > 0 && $now > $this->timemotionclose) {
            return false;
        }

        if ($this->timeclose > 0 && $now > $this->timeclose) {
            return false;
        }

        return true;
    }

    /**
     * Whether voting can currently occur.
     *
     * @param int|null $now Current timestamp.
     * @return bool
     */
    public function is_open_for_voting(?int $now = null): bool {
        $now ??= time();

        if (!$this->is_active()) {
            return false;
        }

        if ($this->timevoteopen > 0 && $now < $this->timevoteopen) {
            return false;
        }

        if ($this->timevoteclose > 0 && $now > $this->timevoteclose) {
            return false;
        }

        if ($this->timeclose > 0 && $now > $this->timeclose) {
            return false;
        }

        return true;
    }

    /**
     * Whether decisions may be contested.
     *
     * @return bool
     */
    public function allows_contests(): bool {
        return $this->allowcontests;
    }

    /**
     * Whether integrity data requires restricted handling.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY || $this->integrityrequired;
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
     * Return Moodle course id.
     *
     * @return int
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Return course module id.
     *
     * @return int
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * Return context id.
     *
     * @return int
     */
    public function get_contextid(): int {
        return $this->contextid;
    }

    /**
     * Return assembly name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Return assembly type.
     *
     * @return string
     */
    public function get_assemblytype(): string {
        return $this->assemblytype;
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
     * Set id immutably.
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
     * Set context references immutably.
     *
     * @param int $cmid Course module id.
     * @param int $contextid Context id.
     * @return self
     */
    public function with_context(int $cmid, int $contextid): self {
        $clone = clone $this;
        $clone->cmid = max(0, $cmid);
        $clone->contextid = max(0, $contextid);
        return $clone;
    }

    /**
     * Set status immutably.
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
     * Set visibility immutably.
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
     * Set metadata immutably.
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
     * Mark modified.
     *
     * @param int $userid Modifier user id.
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
     * Allowed assembly types.
     *
     * @return string[]
     */
    public static function get_allowed_assembly_types(): array {
        return [
            self::TYPE_SAVOIRS,
            self::TYPE_DEFIS,
            self::TYPE_JOUEURS,
            self::TYPE_BATISSEURS,
            self::TYPE_INQUISITEURS,
            self::TYPE_GRAND_JEU,
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
            self::STATUS_HIDDEN,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_CONTESTED,
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
     * Allowed archive policies.
     *
     * @return string[]
     */
    public static function get_allowed_archive_policies(): array {
        return [
            self::ARCHIVE_NONE,
            self::ARCHIVE_SUMMARY,
            self::ARCHIVE_FULL,
        ];
    }

    /**
     * Allowed voting methods.
     *
     * @return string[]
     */
    public static function get_allowed_voting_methods(): array {
        return [
            self::VOTING_SIMPLE_MAJORITY,
            self::VOTING_QUALIFIED_MAJORITY,
            self::VOTING_CONSENSUS,
            self::VOTING_CONSENT,
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
     * Normalise assembly type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_assembly_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);
        return in_array($type, self::get_allowed_assembly_types(), true) ? $type : self::TYPE_SAVOIRS;
    }

    /**
     * Normalise name.
     *
     * @param string $name Raw name.
     * @return string
     */
    private static function normalise_name(string $name): string {
        return trim(clean_param($name, PARAM_TEXT));
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
     * Normalise archive policy.
     *
     * @param string $archivepolicy Raw archive policy.
     * @return string
     */
    private static function normalise_archive_policy(string $archivepolicy): string {
        $archivepolicy = clean_param($archivepolicy, PARAM_ALPHANUMEXT);
        return in_array($archivepolicy, self::get_allowed_archive_policies(), true) ? $archivepolicy : self::ARCHIVE_SUMMARY;
    }

    /**
     * Normalise voting method.
     *
     * @param string $method Raw voting method.
     * @return string
     */
    private static function normalise_voting_method(string $method): string {
        $method = clean_param($method, PARAM_ALPHANUMEXT);
        return in_array($method, self::get_allowed_voting_methods(), true) ? $method : self::VOTING_SIMPLE_MAJORITY;
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
     * Normalise threshold.
     *
     * @param mixed $threshold Raw threshold.
     * @return float
     */
    private static function normalise_threshold(mixed $threshold): float {
        $threshold = (float)$threshold;

        if ($threshold < 0) {
            return 0.0;
        }

        if ($threshold > 100) {
            return 100.0;
        }

        return $threshold;
    }

    /**
     * Normalise bool-like value.
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

        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
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
}
