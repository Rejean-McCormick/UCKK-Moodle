<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK Assembly contestation.
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
 * Domain object for an Assembly decision contestation.
 *
 * This class normalises and validates a contestation record. It does not decide
 * the outcome, mutate the decision, publish minutes, create archive records, or
 * close integrity cases. Those actions belong to service-layer workflows.
 */
final class contestation {
    /** Contest type: procedural error. */
    public const TYPE_PROCEDURAL_ERROR = 'procedural_error';

    /** Contest type: missing evidence. */
    public const TYPE_MISSING_EVIDENCE = 'missing_evidence';

    /** Contest type: unresolved objection. */
    public const TYPE_UNRESOLVED_OBJECTION = 'unresolved_objection';

    /** Contest type: minority report. */
    public const TYPE_MINORITY_REPORT = 'minority_report';

    /** Contest type: integrity concern. */
    public const TYPE_INTEGRITY_CONCERN = 'integrity_concern';

    /** Contest type: privacy concern. */
    public const TYPE_PRIVACY_CONCERN = 'privacy_concern';

    /** Contest type: authority capture. */
    public const TYPE_AUTHORITY_CAPTURE = 'authority_capture';

    /** Contest type: other. */
    public const TYPE_OTHER = 'other';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

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

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: program. */
    public const VISIBILITY_PROGRAM = 'program';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: restricted integrity. */
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Provenance: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Database id. */
    private int $id = 0;

    /** Parent Assembly id. */
    private int $assemblyid = 0;

    /** Related decision id. */
    private int $decisionid = 0;

    /** Optional related motion id. */
    private int $motionid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** User who submitted the contestation. */
    private int $userid = 0;

    /** Contestation type. */
    private string $contesttype = self::TYPE_PROCEDURAL_ERROR;

    /** Short contestation summary. */
    private string $summary = '';

    /** Detailed contestation grounds. */
    private string $grounds = '';

    /** Requested remedy. */
    private string $requestedremedy = '';

    /** Optional evidence URL. */
    private string $evidenceurl = '';

    /** Optional evidence notes. */
    private string $evidencenotes = '';

    /** Whether this contestation includes a minority report. */
    private bool $minorityreport = false;

    /** Minority report text. */
    private string $minorityreporttext = '';

    /** Whether an integrity case should be opened or linked. */
    private bool $requestintegritycase = true;

    /** Linked tool_uckkintegrity case id, when available. */
    private int $integritycaseid = 0;

    /** Contestation status. */
    private string $status = self::STATUS_PENDING_REVIEW;

    /** Contestation visibility. */
    private string $visibility = self::VISIBILITY_RESTRICTED_INTEGRITY;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Record version. */
    private int $versionno = 1;

    /** Created by user id. */
    private int $createdby = 0;

    /** Modified by user id. */
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
     * Build from a database record.
     *
     * @param stdClass $record Record from {uckkassembly_contest}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Create a new contestation object.
     *
     * @param int $assemblyid Assembly id.
     * @param int $decisionid Decision id.
     * @param int $userid Contesting user id.
     * @param string $summary Short summary.
     * @param string $grounds Detailed grounds.
     * @param string $requestedremedy Requested remedy.
     * @param string $contesttype Contest type.
     * @return self
     */
    public static function create(
        int $assemblyid,
        int $decisionid,
        int $userid,
        string $summary,
        string $grounds,
        string $requestedremedy,
        string $contesttype = self::TYPE_PROCEDURAL_ERROR
    ): self {
        return new self([
            'assemblyid' => $assemblyid,
            'decisionid' => $decisionid,
            'userid' => $userid,
            'summary' => $summary,
            'grounds' => $grounds,
            'requestedremedy' => $requestedremedy,
            'contesttype' => $contesttype,
            'status' => self::STATUS_PENDING_REVIEW,
            'visibility' => self::VISIBILITY_RESTRICTED_INTEGRITY,
            'provenance' => self::PROVENANCE_HUMAN,
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
        $this->decisionid = max(0, (int)($data['decisionid'] ?? $this->decisionid));
        $this->motionid = max(0, (int)($data['motionid'] ?? $this->motionid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));

        $this->contesttype = self::normalise_contest_type((string)($data['contesttype'] ?? $data['type'] ?? $this->contesttype));
        $this->summary = self::normalise_text((string)($data['summary'] ?? $this->summary));
        $this->grounds = trim((string)($data['grounds'] ?? $this->grounds));
        $this->requestedremedy = trim((string)($data['requestedremedy'] ?? $this->requestedremedy));
        $this->evidenceurl = self::normalise_url((string)($data['evidenceurl'] ?? $this->evidenceurl));
        $this->evidencenotes = trim((string)($data['evidencenotes'] ?? $this->evidencenotes));

        $this->minorityreport = self::normalise_bool($data['minorityreport'] ?? $this->minorityreport);
        $this->minorityreporttext = trim((string)($data['minorityreporttext'] ?? $this->minorityreporttext));

        $this->requestintegritycase = self::normalise_bool($data['requestintegritycase'] ?? $this->requestintegritycase);
        $this->integritycaseid = max(0, (int)($data['integritycaseid'] ?? $this->integritycaseid));

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
     * Validate this contestation.
     *
     * @throws \coding_exception When invalid.
     */
    public function validate(): void {
        if ($this->assemblyid <= 0) {
            throw new \coding_exception('Assembly contestation requires a valid assemblyid.');
        }

        if ($this->decisionid <= 0) {
            throw new \coding_exception('Assembly contestation requires a valid decisionid.');
        }

        if ($this->userid <= 0) {
            throw new \coding_exception('Assembly contestation requires a valid userid.');
        }

        if ($this->summary === '') {
            throw new \coding_exception('Assembly contestation requires a summary.');
        }

        if ($this->grounds === '') {
            throw new \coding_exception('Assembly contestation requires grounds.');
        }

        if ($this->requestedremedy === '') {
            throw new \coding_exception('Assembly contestation requires a requested remedy.');
        }

        if ($this->minorityreport && $this->minorityreporttext === '') {
            throw new \coding_exception('Assembly contestation minority report text is required when minorityreport is enabled.');
        }

        if (!in_array($this->contesttype, self::get_allowed_contest_types(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly contestation type: ' . $this->contesttype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly contestation status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly contestation visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid UCKK Assembly contestation provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to database record for {uckkassembly_contest}.
     *
     * @param int|null $userid Current user id.
     * @param int|null $now Current timestamp.
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
        $record->decisionid = $this->decisionid;
        $record->motionid = $this->motionid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;

        $record->contesttype = $this->contesttype;
        $record->summary = $this->summary;
        $record->grounds = $this->grounds;
        $record->requestedremedy = $this->requestedremedy;
        $record->evidenceurl = $this->evidenceurl;
        $record->evidencenotes = $this->evidencenotes;
        $record->minorityreport = $this->minorityreport ? 1 : 0;
        $record->minorityreporttext = $this->minorityreporttext;
        $record->requestintegritycase = $this->requestintegritycase ? 1 : 0;
        $record->integritycaseid = $this->integritycaseid;

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
     * Export safe structured data.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $data = new stdClass();

        $data->id = $this->id;
        $data->assemblyid = $this->assemblyid;
        $data->decisionid = $this->decisionid;
        $data->motionid = $this->motionid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;

        $data->contesttype = $this->contesttype;
        $data->summary = $this->summary;
        $data->grounds = $this->grounds;
        $data->requestedremedy = $this->requestedremedy;
        $data->evidenceurl = $this->evidenceurl;
        $data->evidencenotes = $this->evidencenotes;
        $data->minorityreport = $this->minorityreport;
        $data->minorityreporttext = $this->minorityreporttext;
        $data->requestintegritycase = $this->requestintegritycase;
        $data->integritycaseid = $this->integritycaseid;

        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;

        $data->isopen = $this->is_open();
        $data->isclosed = $this->is_closed();
        $data->isrestricted = $this->is_restricted();
        $data->hasintegritycase = $this->has_integrity_case();

        return $data;
    }

    /**
     * Whether this contestation is still open procedurally.
     *
     * @return bool
     */
    public function is_open(): bool {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
        ], true);
    }

    /**
     * Whether this contestation is closed procedurally.
     *
     * @return bool
     */
    public function is_closed(): bool {
        return in_array($this->status, [
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Whether this contestation is restricted.
     *
     * @return bool
     */
    public function is_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->contesttype === self::TYPE_INTEGRITY_CONCERN
            || $this->contesttype === self::TYPE_PRIVACY_CONCERN;
    }

    /**
     * Whether this contestation has an integrity case link.
     *
     * @return bool
     */
    public function has_integrity_case(): bool {
        return $this->integritycaseid > 0;
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
     * Return decision id.
     *
     * @return int
     */
    public function get_decisionid(): int {
        return $this->decisionid;
    }

    /**
     * Return contesting user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return contestation type.
     *
     * @return string
     */
    public function get_contesttype(): string {
        return $this->contesttype;
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
     * Return a clone with id.
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
     * Return a clone with context references.
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
     * Return a clone with motion id.
     *
     * @param int $motionid Motion id.
     * @return self
     */
    public function with_motionid(int $motionid): self {
        $clone = clone $this;
        $clone->motionid = max(0, $motionid);
        return $clone;
    }

    /**
     * Return a clone with status.
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
     * Return a clone linked to an integrity case.
     *
     * @param int $caseid Integrity case id.
     * @return self
     */
    public function with_integritycaseid(int $caseid): self {
        $clone = clone $this;
        $clone->integritycaseid = max(0, $caseid);
        $clone->requestintegritycase = $caseid <= 0 ? $clone->requestintegritycase : true;
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
     * Allowed contestation types.
     *
     * @return string[]
     */
    public static function get_allowed_contest_types(): array {
        return [
            self::TYPE_PROCEDURAL_ERROR,
            self::TYPE_MISSING_EVIDENCE,
            self::TYPE_UNRESOLVED_OBJECTION,
            self::TYPE_MINORITY_REPORT,
            self::TYPE_INTEGRITY_CONCERN,
            self::TYPE_PRIVACY_CONCERN,
            self::TYPE_AUTHORITY_CAPTURE,
            self::TYPE_OTHER,
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
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
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
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
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
            self::PROVENANCE_IMPORTED,
            self::PROVENANCE_SYSTEM,
        ];
    }

    /**
     * Normalise contest type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private static function normalise_contest_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        return in_array($type, self::get_allowed_contest_types(), true)
            ? $type
            : self::TYPE_OTHER;
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
            : self::STATUS_PENDING_REVIEW;
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
            : self::VISIBILITY_RESTRICTED_INTEGRITY;
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
     * Normalise plain text.
     *
     * @param string $text Raw text.
     * @return string
     */
    private static function normalise_text(string $text): string {
        return trim(clean_param($text, PARAM_TEXT));
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
     * Normalise boolean-like data.
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