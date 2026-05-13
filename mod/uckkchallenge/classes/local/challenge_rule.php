<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for one UCKK challenge rule.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Local domain model for a challenge rule.
 *
 * This class is intentionally not a controller and not a persistence service.
 * It normalises and validates one rule record before service/database layers
 * insert, update, render, archive, or export it.
 */
final class challenge_rule {
    /** Rule type: general rule. */
    public const TYPE_RULE = 'rule';

    /** Rule type: corridor of action. */
    public const TYPE_CORRIDOR = 'corridor';

    /** Rule type: evidence requirement. */
    public const TYPE_EVIDENCE = 'evidence';

    /** Rule type: evaluation criterion. */
    public const TYPE_CRITERION = 'criterion';

    /** Rule type: ethical constraint. */
    public const TYPE_ETHICAL_CONSTRAINT = 'ethical_constraint';

    /** Rule type: integrity guardrail. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Rule type: archive requirement. */
    public const TYPE_ARCHIVE = 'archive';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: group. */
    public const VISIBILITY_GROUP = 'group';

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

    /** Database id. */
    private int $id = 0;

    /** Parent uckkchallenge id. */
    private int $challengeid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** Stable rule type. */
    private string $ruletype = self::TYPE_RULE;

    /** Rule title. */
    private string $title = '';

    /** Rule body. */
    private string $description = '';

    /** Rule status. */
    private string $status = self::STATUS_DRAFT;

    /** Rule visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Whether the rule is mandatory. */
    private bool $required = true;

    /** Display order. */
    private int $sortorder = 0;

    /** Provenance source. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Record version. */
    private int $versionno = 1;

    /** User who created the rule. */
    private int $createdby = 0;

    /** User who last modified the rule. */
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
     * @param array<string, mixed>|stdClass|null $data Initial rule data.
     */
    public function __construct(array|stdClass|null $data = null) {
        if ($data !== null) {
            $this->apply_data((array)$data);
        }
    }

    /**
     * Build a rule object from a Moodle database record.
     *
     * @param stdClass $record Database record from {uckkchallenge_rule}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Build a new rule for a challenge.
     *
     * @param int $challengeid Parent challenge id.
     * @param string $title Rule title.
     * @param string $description Rule description.
     * @param string $ruletype Rule type.
     * @param array<string, mixed> $metadata Optional metadata.
     * @return self
     */
    public static function create(
        int $challengeid,
        string $title,
        string $description,
        string $ruletype = self::TYPE_RULE,
        array $metadata = []
    ): self {
        return new self([
            'challengeid' => $challengeid,
            'title' => $title,
            'description' => $description,
            'ruletype' => $ruletype,
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
        $this->challengeid = max(0, (int)($data['challengeid'] ?? $data['uckkchallengeid'] ?? $this->challengeid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));

        $this->ruletype = self::normalise_rule_type((string)($data['ruletype'] ?? $data['type'] ?? $this->ruletype));
        $this->title = self::normalise_title((string)($data['title'] ?? $data['name'] ?? $this->title));
        $this->description = trim((string)($data['description'] ?? $data['ruletext'] ?? $this->description));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->required = !empty($data['required']) || !empty($data['isrequired']) || $this->required;
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
     * Validate this rule.
     *
     * @throws \coding_exception If the rule is invalid.
     */
    public function validate(): void {
        if ($this->challengeid <= 0) {
            throw new \coding_exception('Challenge rule requires a valid challengeid.');
        }

        if ($this->title === '') {
            throw new \coding_exception('Challenge rule requires a title.');
        }

        if ($this->description === '') {
            throw new \coding_exception('Challenge rule requires a description.');
        }

        if (!in_array($this->ruletype, self::get_allowed_rule_types(), true)) {
            throw new \coding_exception('Invalid UCKK challenge rule type: ' . $this->ruletype);
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new \coding_exception('Invalid UCKK challenge rule status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new \coding_exception('Invalid UCKK challenge rule visibility: ' . $this->visibility);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new \coding_exception('Invalid UCKK challenge rule provenance: ' . $this->provenance);
        }
    }

    /**
     * Convert to a database record for {uckkchallenge_rule}.
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

        $record->challengeid = $this->challengeid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->ruletype = $this->ruletype;
        $record->title = $this->title;
        $record->description = $this->description;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->required = $this->required ? 1 : 0;
        $record->sortorder = $this->sortorder;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->versionno = $this->versionno;

        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->metadata = $this->metadata === [] ? null : json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
        $data->challengeid = $this->challengeid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->ruletype = $this->ruletype;
        $data->title = $this->title;
        $data->description = $this->description;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->required = $this->required;
        $data->sortorder = $this->sortorder;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        return $data;
    }

    /**
     * Return whether this rule is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Return whether this rule is visible to ordinary course participants.
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
     * Return whether this rule requires restricted integrity handling.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY
            || $this->ruletype === self::TYPE_INTEGRITY;
    }

    /**
     * Return whether this rule must be satisfied by the participant.
     *
     * @return bool
     */
    public function is_required(): bool {
        return $this->required;
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
     * Return parent challenge id.
     *
     * @return int
     */
    public function get_challengeid(): int {
        return $this->challengeid;
    }

    /**
     * Return rule type.
     *
     * @return string
     */
    public function get_ruletype(): string {
        return $this->ruletype;
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
     * Return description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
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
     * Mark this rule as modified by a user.
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
     * Allowed rule types.
     *
     * @return string[]
     */
    public static function get_allowed_rule_types(): array {
        return [
            self::TYPE_RULE,
            self::TYPE_CORRIDOR,
            self::TYPE_EVIDENCE,
            self::TYPE_CRITERION,
            self::TYPE_ETHICAL_CONSTRAINT,
            self::TYPE_INTEGRITY,
            self::TYPE_ARCHIVE,
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
            self::VISIBILITY_GROUP,
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
     * Normalise rule type.
     *
     * @param string $ruletype Raw type.
     * @return string
     */
    private static function normalise_rule_type(string $ruletype): string {
        $ruletype = clean_param($ruletype, PARAM_ALPHANUMEXT);
        return in_array($ruletype, self::get_allowed_rule_types(), true) ? $ruletype : self::TYPE_RULE;
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