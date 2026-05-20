<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace mod_uckkchallenge\local;

use coding_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Domain helper for UCKK Challenge activity records.
 *
 * This class centralises the canonical vocabulary for Défis King Klown:
 * - challenge types;
 * - workflow statuses;
 * - visibility values;
 * - archive policies;
 * - AI disclosure rules;
 * - safe Moodle record preparation.
 *
 * It is intentionally not a database repository and not a permission service.
 * Database writes belong in service/lib functions. Capability checks belong in
 * controllers/services. Integrity decisions belong to the integrity workflow.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge {
    /**
     * Moodle component name.
     */
    public const COMPONENT = 'mod_uckkchallenge';

    /**
     * Main challenge table.
     */
    public const TABLE = 'uckkchallenge';

    /**
     * Challenge rule table.
     */
    public const TABLE_RULE = 'uckkchallenge_rule';

    /**
     * Challenge corridor table.
     */
    public const TABLE_CORRIDOR = 'uckkchallenge_corr';

    /**
     * Challenge submission table.
     */
    public const TABLE_SUBMISSION = 'uckkchallenge_sub';

    /**
     * Challenge proof table.
     */
    public const TABLE_PROOF = 'uckkchallenge_proof';

    /**
     * Challenge evaluation table.
     */
    public const TABLE_EVALUATION = 'uckkchallenge_eval';

    /**
     * Challenge state table.
     */
    public const TABLE_STATE = 'uckkchallenge_state';

    /**
     * Challenge types.
     */
    public const TYPE_INTERNAL_LEARNING = 'internal_learning';
    public const TYPE_PUBLIC_PEDAGOGICAL = 'public_pedagogical';
    public const TYPE_INSTITUTIONAL_AUDIT = 'institutional_audit';
    public const TYPE_SYSTEM_MAPPING = 'system_mapping';
    public const TYPE_PROTOTYPE = 'prototype';
    public const TYPE_MOBILISATION = 'mobilisation';
    public const TYPE_CAPSTONE = 'capstone';
    public const TYPE_KING_KLOWN_PUBLIC = 'king_klown_public';

    /**
     * Challenge workflow statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_OPEN = 'open';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_INTEGRITY_REVIEW = 'integrity_review';
    public const STATUS_REVISION_REQUIRED = 'revision_required';
    public const STATUS_RESUBMITTED = 'resubmitted';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_CLOSED = 'closed';

    /**
     * Alternative / exceptional statuses.
     */
    public const STATUS_CONTESTED = 'contested';
    public const STATUS_INVALIDATED = 'invalidated';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Progress statuses.
     */
    public const PROGRESS_NOT_STARTED = 'not_started';
    public const PROGRESS_IN_PROGRESS = 'in_progress';
    public const PROGRESS_PENDING_REVIEW = 'pending_review';
    public const PROGRESS_COMPLETED = 'completed';
    public const PROGRESS_BLOCKED = 'blocked';

    /**
     * Visibility values.
     */
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_USER = 'user';
    public const VISIBILITY_GROUP = 'group';
    public const VISIBILITY_COURSE = 'course';
    public const VISIBILITY_COHORT = 'cohort';
    public const VISIBILITY_PROGRAM = 'program';
    public const VISIBILITY_INSTITUTION = 'institution';
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_RESTRICTED = 'restricted';
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';
    public const VISIBILITY_HIDDEN = 'hidden';
    public const VISIBILITY_ARCHIVED = 'archived';

    /**
     * Provenance sources.
     */
    public const PROVENANCE_HUMAN = 'human';
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';
    public const PROVENANCE_IMPORTED = 'imported';
    public const PROVENANCE_SYSTEM = 'system';
    public const PROVENANCE_ARCHIVE = 'archive';
    public const PROVENANCE_ASSEMBLY = 'assembly';
    public const PROVENANCE_CHALLENGE = 'challenge';
    public const PROVENANCE_INTEGRITY = 'integrity';

    /**
     * Integrity / validation states.
     */
    public const VALIDATION_UNVERIFIED = 'unverified';
    public const VALIDATION_HUMAN_REVIEWED = 'human_reviewed';
    public const VALIDATION_VERIFIED = 'verified';
    public const VALIDATION_CONTESTED = 'contested';
    public const VALIDATION_INVALIDATED = 'invalidated';
    public const VALIDATION_ARCHIVED = 'archived';

    /**
     * Archive policies.
     */
    public const ARCHIVE_POLICY_NONE = 'none';
    public const ARCHIVE_POLICY_SUMMARY = 'summary';
    public const ARCHIVE_POLICY_FULL = 'full';

    /**
     * AI policy constants.
     */
    public const AI_NON_SOVEREIGN = true;
    public const AI_REQUIRES_HUMAN_VALIDATION = true;
    public const AI_ALLOW_DECISION_AUTOMATION = false;
    public const AI_LOG_PROMPTS = true;
    public const AI_LOG_OUTPUTS = true;
    public const AI_REQUIRE_PROVENANCE = true;
    public const AI_REQUIRE_UNCERTAINTY_LABEL = true;

    /**
     * Capability names used by this activity.
     */
    public const CAP_ADD_INSTANCE = 'mod/uckkchallenge:addinstance';
    public const CAP_VIEW = 'mod/uckkchallenge:view';
    public const CAP_CREATE = 'mod/uckkchallenge:createchallenge';
    public const CAP_SUBMIT_PROOF = 'mod/uckkchallenge:submitproof';
    public const CAP_EVALUATE = 'mod/uckkchallenge:evaluate';
    public const CAP_VALIDATE_INTEGRITY = 'mod/uckkchallenge:validateintegrity';
    public const CAP_ARCHIVE = 'mod/uckkchallenge:archive';

    /**
     * Wrapped challenge record.
     *
     * @var stdClass
     */
    private stdClass $record;

    /**
     * Constructor.
     *
     * @param stdClass $record Normalised challenge record.
     */
    private function __construct(stdClass $record) {
        $this->record = $record;
    }

    /**
     * Create a challenge domain object from a Moodle DB record.
     *
     * @param stdClass $record Challenge record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self(self::normalise_record($record));
    }

    /**
     * Return the wrapped challenge record.
     *
     * @return stdClass
     */
    public function get_record(): stdClass {
        return clone $this->record;
    }

    /**
     * Return the challenge id.
     *
     * @return int
     */
    public function get_id(): int {
        return (int)($this->record->id ?? 0);
    }

    /**
     * Return the course id.
     *
     * @return int
     */
    public function get_course_id(): int {
        return (int)($this->record->course ?? $this->record->courseid ?? 0);
    }

    /**
     * Return the challenge name.
     *
     * @return string
     */
    public function get_name(): string {
        return (string)($this->record->name ?? '');
    }

    /**
     * Return challenge type.
     *
     * @return string
     */
    public function get_type(): string {
        return (string)$this->record->challengetype;
    }

    /**
     * Return status.
     *
     * @return string
     */
    public function get_status(): string {
        return (string)$this->record->status;
    }

    /**
     * Return visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return (string)$this->record->visibility;
    }

    /**
     * Whether the challenge is open for learner submissions at the current time.
     *
     * @param int|null $now Optional timestamp override.
     * @return bool
     */
    public function is_open_for_submission(?int $now = null): bool {
        $now ??= time();

        if ($this->record->status !== self::STATUS_OPEN) {
            return false;
        }

        if (!empty($this->record->timeopen) && (int)$this->record->timeopen > $now) {
            return false;
        }

        if (!empty($this->record->timeclose) && (int)$this->record->timeclose < $now) {
            return false;
        }

        return true;
    }

    /**
     * Whether the challenge has not opened yet.
     *
     * @param int|null $now Optional timestamp override.
     * @return bool
     */
    public function is_before_open_time(?int $now = null): bool {
        $now ??= time();

        return !empty($this->record->timeopen) && (int)$this->record->timeopen > $now;
    }

    /**
     * Whether the challenge is past its close time.
     *
     * @param int|null $now Optional timestamp override.
     * @return bool
     */
    public function is_after_close_time(?int $now = null): bool {
        $now ??= time();

        return !empty($this->record->timeclose) && (int)$this->record->timeclose < $now;
    }

    /**
     * Whether the challenge is in a review state.
     *
     * @return bool
     */
    public function requires_review(): bool {
        return in_array($this->record->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_RESUBMITTED,
        ], true);
    }

    /**
     * Whether the challenge is in integrity review.
     *
     * @return bool
     */
    public function requires_integrity_review(): bool {
        return !empty($this->record->integrityrequired)
            || $this->record->status === self::STATUS_INTEGRITY_REVIEW
            || $this->record->visibility === self::VISIBILITY_RESTRICTED_INTEGRITY;
    }

    /**
     * Whether the challenge is terminal for normal learner action.
     *
     * @return bool
     */
    public function is_terminal(): bool {
        return in_array($this->record->status, [
            self::STATUS_ARCHIVED,
            self::STATUS_CLOSED,
            self::STATUS_INVALIDATED,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED,
        ], true);
    }

    /**
     * Whether the challenge may produce archive output.
     *
     * @return bool
     */
    public function can_produce_archive_output(): bool {
        return $this->record->archivepolicy !== self::ARCHIVE_POLICY_NONE
            && in_array($this->record->status, [
                self::STATUS_VALIDATED,
                self::STATUS_ARCHIVED,
                self::STATUS_CLOSED,
            ], true);
    }

    /**
     * Whether the challenge is public-facing.
     *
     * @return bool
     */
    public function is_public_facing(): bool {
        return $this->record->visibility === self::VISIBILITY_PUBLIC
            || $this->record->challengetype === self::TYPE_KING_KLOWN_PUBLIC
            || $this->record->challengetype === self::TYPE_PUBLIC_PEDAGOGICAL;
    }

    /**
     * Whether public-facing challenge safeguards should be enforced.
     *
     * @return bool
     */
    public function requires_public_safeguards(): bool {
        return $this->is_public_facing();
    }

    /**
     * Whether AI assistance is allowed for this challenge.
     *
     * AI can assist, but it cannot decide, grade, validate integrity, award
     * badges, certify competencies, erase evidence, or replace human review.
     *
     * @return bool
     */
    public function allows_ai_assistance(): bool {
        return true;
    }

    /**
     * Whether AI output can be treated as final authority.
     *
     * @return bool
     */
    public function allows_ai_final_authority(): bool {
        return self::AI_ALLOW_DECISION_AUTOMATION;
    }

    /**
     * Return canonical challenge types.
     *
     * @return string[]
     */
    public static function get_types(): array {
        return [
            self::TYPE_INTERNAL_LEARNING,
            self::TYPE_PUBLIC_PEDAGOGICAL,
            self::TYPE_INSTITUTIONAL_AUDIT,
            self::TYPE_SYSTEM_MAPPING,
            self::TYPE_PROTOTYPE,
            self::TYPE_MOBILISATION,
            self::TYPE_CAPSTONE,
            self::TYPE_KING_KLOWN_PUBLIC,
        ];
    }

    /**
     * Return canonical challenge statuses.
     *
     * @return string[]
     */
    public static function get_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_OPEN,
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_INTEGRITY_REVIEW,
            self::STATUS_REVISION_REQUIRED,
            self::STATUS_RESUBMITTED,
            self::STATUS_VALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CLOSED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED,
        ];
    }

    /**
     * Return visibility values.
     *
     * @return string[]
     */
    public static function get_visibilities(): array {
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
     * Return archive policy values.
     *
     * @return string[]
     */
    public static function get_archive_policies(): array {
        return [
            self::ARCHIVE_POLICY_NONE,
            self::ARCHIVE_POLICY_SUMMARY,
            self::ARCHIVE_POLICY_FULL,
        ];
    }

    /**
     * Return allowed state transitions.
     *
     * @return array<string, string[]>
     */
    public static function get_allowed_transitions(): array {
        return [
            self::STATUS_DRAFT => [
                self::STATUS_PUBLISHED,
                self::STATUS_WITHDRAWN,
            ],
            self::STATUS_PUBLISHED => [
                self::STATUS_DRAFT,
                self::STATUS_OPEN,
                self::STATUS_EXPIRED,
                self::STATUS_WITHDRAWN,
            ],
            self::STATUS_OPEN => [
                self::STATUS_SUBMITTED,
                self::STATUS_CLOSED,
                self::STATUS_CONTESTED,
                self::STATUS_EXPIRED,
                self::STATUS_WITHDRAWN,
            ],
            self::STATUS_SUBMITTED => [
                self::STATUS_UNDER_REVIEW,
                self::STATUS_REVISION_REQUIRED,
                self::STATUS_CONTESTED,
            ],
            self::STATUS_UNDER_REVIEW => [
                self::STATUS_INTEGRITY_REVIEW,
                self::STATUS_REVISION_REQUIRED,
                self::STATUS_VALIDATED,
                self::STATUS_CONTESTED,
                self::STATUS_INVALIDATED,
            ],
            self::STATUS_INTEGRITY_REVIEW => [
                self::STATUS_REVISION_REQUIRED,
                self::STATUS_VALIDATED,
                self::STATUS_CONTESTED,
                self::STATUS_INVALIDATED,
            ],
            self::STATUS_REVISION_REQUIRED => [
                self::STATUS_RESUBMITTED,
                self::STATUS_CONTESTED,
                self::STATUS_WITHDRAWN,
            ],
            self::STATUS_RESUBMITTED => [
                self::STATUS_UNDER_REVIEW,
                self::STATUS_INTEGRITY_REVIEW,
                self::STATUS_CONTESTED,
            ],
            self::STATUS_VALIDATED => [
                self::STATUS_ARCHIVED,
                self::STATUS_CONTESTED,
                self::STATUS_CLOSED,
            ],
            self::STATUS_ARCHIVED => [
                self::STATUS_CLOSED,
            ],
            self::STATUS_CONTESTED => [
                self::STATUS_UNDER_REVIEW,
                self::STATUS_INTEGRITY_REVIEW,
                self::STATUS_VALIDATED,
                self::STATUS_INVALIDATED,
                self::STATUS_CLOSED,
            ],
            self::STATUS_INVALIDATED => [
                self::STATUS_CONTESTED,
                self::STATUS_CLOSED,
            ],
            self::STATUS_EXPIRED => [
                self::STATUS_CLOSED,
            ],
            self::STATUS_WITHDRAWN => [
                self::STATUS_CLOSED,
            ],
            self::STATUS_CLOSED => [],
        ];
    }

    /**
     * Whether a transition is allowed by the canonical state machine.
     *
     * @param string $from Current status.
     * @param string $to Target status.
     * @return bool
     */
    public static function can_transition(string $from, string $to): bool {
        $from = self::normalise_status($from);
        $to = self::normalise_status($to);

        if ($from === $to) {
            return true;
        }

        $transitions = self::get_allowed_transitions();

        return in_array($to, $transitions[$from] ?? [], true);
    }

    /**
     * Assert that a status transition is valid.
     *
     * @param string $from Current status.
     * @param string $to Target status.
     * @throws coding_exception
     */
    public static function assert_can_transition(string $from, string $to): void {
        if (!self::can_transition($from, $to)) {
            throw new coding_exception("Invalid UCKK challenge transition from '{$from}' to '{$to}'.");
        }
    }

    /**
     * Normalise a challenge DB record.
     *
     * @param stdClass $record Raw record.
     * @return stdClass
     */
    public static function normalise_record(stdClass $record): stdClass {
        $record = clone $record;

        $record->name = trim((string)($record->name ?? ''));
        $record->challengecode = self::normalise_code((string)($record->challengecode ?? ''));
        $record->challengetype = self::normalise_type((string)($record->challengetype ?? self::TYPE_INTERNAL_LEARNING));
        $record->status = self::normalise_status((string)($record->status ?? self::STATUS_DRAFT));
        $record->visibility = self::normalise_visibility((string)($record->visibility ?? self::VISIBILITY_COURSE));
        $record->archivepolicy = self::normalise_archive_policy((string)($record->archivepolicy ?? self::ARCHIVE_POLICY_SUMMARY));

        $record->statement = (string)($record->statement ?? '');
        $record->contexttext = (string)($record->contexttext ?? '');
        $record->rules = (string)($record->rules ?? '');
        $record->corridors = (string)($record->corridors ?? '');
        $record->ethicalconstraints = (string)($record->ethicalconstraints ?? '');
        $record->evidencepolicy = (string)($record->evidencepolicy ?? '');
        $record->criteria = (string)($record->criteria ?? '');
        $record->integritynotes = (string)($record->integritynotes ?? '');
        $record->aipolicy = (string)($record->aipolicy ?? '');
        $record->publicsummary = (string)($record->publicsummary ?? '');
        $record->competencylinks = (string)($record->competencylinks ?? '');
        $record->badgelinks = (string)($record->badgelinks ?? '');
        $record->metadata = self::normalise_metadata($record->metadata ?? '');

        $record->teamsubmissions = !empty($record->teamsubmissions) ? 1 : 0;
        $record->allowresubmission = !empty($record->allowresubmission) ? 1 : 0;
        $record->integrityrequired = !empty($record->integrityrequired) ? 1 : 0;

        $record->maxsubmissions = isset($record->maxsubmissions) ? max(0, (int)$record->maxsubmissions) : 1;
        $record->timeopen = isset($record->timeopen) ? max(0, (int)$record->timeopen) : 0;
        $record->timeclose = isset($record->timeclose) ? max(0, (int)$record->timeclose) : 0;
        $record->timereviewby = isset($record->timereviewby) ? max(0, (int)$record->timereviewby) : 0;
        $record->versionno = isset($record->versionno) ? max(1, (int)$record->versionno) : 1;

        return $record;
    }

    /**
     * Prepare record for insert.
     *
     * @param stdClass $record Raw record.
     * @param int $userid Acting user id.
     * @param int|null $now Optional timestamp override.
     * @return stdClass
     */
    public static function prepare_for_insert(stdClass $record, int $userid, ?int $now = null): stdClass {
        $now ??= time();
        $record = self::normalise_record($record);

        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->createdby = $userid;
        $record->modifiedby = $userid;
        $record->versionno = 1;

        return $record;
    }

    /**
     * Prepare record for update.
     *
     * @param stdClass $record Raw record.
     * @param int $userid Acting user id.
     * @param int|null $now Optional timestamp override.
     * @return stdClass
     */
    public static function prepare_for_update(stdClass $record, int $userid, ?int $now = null): stdClass {
        $now ??= time();
        $record = self::normalise_record($record);

        $record->timemodified = $now;
        $record->modifiedby = $userid;
        $record->versionno = max(1, (int)($record->versionno ?? 1)) + 1;

        return $record;
    }

    /**
     * Validate challenge instance data.
     *
     * Returns string identifiers instead of throwing so forms/services can map
     * them to localised error messages.
     *
     * @param stdClass $record Challenge record.
     * @return array<string, string>
     */
    public static function validate_record(stdClass $record): array {
        $record = self::normalise_record($record);
        $errors = [];

        if ($record->name === '') {
            $errors['name'] = 'required';
        }

        if ($record->statement === '') {
            $errors['statement'] = 'required';
        }

        if ($record->rules === '') {
            $errors['rules'] = 'required';
        }

        if ($record->evidencepolicy === '') {
            $errors['evidencepolicy'] = 'required';
        }

        if ($record->criteria === '') {
            $errors['criteria'] = 'required';
        }

        if ($record->challengecode !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $record->challengecode)) {
            $errors['challengecode'] = 'invalidchallengecode';
        }

        if (!in_array($record->challengetype, self::get_types(), true)) {
            $errors['challengetype'] = 'invalidchallengetype';
        }

        if (!in_array($record->status, self::get_statuses(), true)) {
            $errors['status'] = 'invalidstatus';
        }

        if (!in_array($record->visibility, self::get_visibilities(), true)) {
            $errors['visibility'] = 'invalidvisibility';
        }

        if (!in_array($record->archivepolicy, self::get_archive_policies(), true)) {
            $errors['archivepolicy'] = 'invalidarchivepolicy';
        }

        if ($record->timeopen > 0 && $record->timeclose > 0 && $record->timeclose <= $record->timeopen) {
            $errors['timeclose'] = 'timeclosemustbeafteropen';
        }

        if ($record->timeclose > 0 && $record->timereviewby > 0 && $record->timereviewby < $record->timeclose) {
            $errors['timereviewby'] = 'timereviewmustbeafterclose';
        }

        foreach (['corridors', 'competencylinks', 'badgelinks', 'metadata'] as $field) {
            if (!self::is_valid_json_or_plain_list((string)$record->{$field})) {
                $errors[$field] = 'invalidjsonorlist';
            }
        }

        if ($record->visibility === self::VISIBILITY_PUBLIC && trim($record->publicsummary) === '') {
            $errors['publicsummary'] = 'publicsummaryrequired';
        }

        if ($record->integrityrequired && trim($record->integritynotes) === '') {
            $errors['integritynotes'] = 'integritynotesrecommended';
        }

        return $errors;
    }

    /**
     * Throw if challenge instance data is invalid.
     *
     * @param stdClass $record Challenge record.
     * @throws coding_exception
     */
    public static function assert_valid_record(stdClass $record): void {
        $errors = self::validate_record($record);

        if (!empty($errors)) {
            throw new coding_exception('Invalid UCKK challenge record: ' . json_encode($errors));
        }
    }

    /**
     * Normalise challenge type.
     *
     * @param string $type Raw type.
     * @return string
     */
    public static function normalise_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        return in_array($type, self::get_types(), true) ? $type : self::TYPE_INTERNAL_LEARNING;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    public static function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        return in_array($status, self::get_statuses(), true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    public static function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

        return in_array($visibility, self::get_visibilities(), true) ? $visibility : self::VISIBILITY_COURSE;
    }

    /**
     * Normalise archive policy.
     *
     * @param string $policy Raw policy.
     * @return string
     */
    public static function normalise_archive_policy(string $policy): string {
        $policy = clean_param($policy, PARAM_ALPHANUMEXT);

        return in_array($policy, self::get_archive_policies(), true) ? $policy : self::ARCHIVE_POLICY_SUMMARY;
    }

    /**
     * Normalise a stable challenge code.
     *
     * @param string $code Raw code.
     * @return string
     */
    public static function normalise_code(string $code): string {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        $code = preg_replace('/[^A-Za-z0-9_-]/', '_', $code);

        return substr((string)$code, 0, 100);
    }

    /**
     * Normalise metadata to JSON string or empty string.
     *
     * @param mixed $metadata Raw metadata.
     * @return string
     */
    public static function normalise_metadata(mixed $metadata): string {
        if (is_array($metadata) || is_object($metadata)) {
            $encoded = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false ? '' : $encoded;
        }

        $metadata = trim((string)$metadata);

        if ($metadata === '') {
            return '';
        }

        json_decode($metadata);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $metadata;
        }

        return json_encode(['text' => $metadata], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * Decode metadata from a challenge record.
     *
     * @param stdClass $record Challenge record.
     * @return array<string, mixed>
     */
    public static function decode_metadata(stdClass $record): array {
        $metadata = trim((string)($record->metadata ?? ''));

        if ($metadata === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accept valid JSON or a plain newline-separated/list-like value.
     *
     * @param string $value Raw value.
     * @return bool
     */
    public static function is_valid_json_or_plain_list(string $value): bool {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        json_decode($value);

        if (json_last_error() === JSON_ERROR_NONE) {
            return true;
        }

        return str_contains($value, "\n") || (!str_contains($value, '{') && !str_contains($value, '['));
    }

    /**
     * Build a minimal metadata object for challenge provenance.
     *
     * @param string $provenance Provenance source.
     * @param int $userid Acting user id.
     * @param array<string, mixed> $extra Extra metadata.
     * @return string JSON metadata.
     */
    public static function build_metadata(
        string $provenance = self::PROVENANCE_HUMAN,
        int $userid = 0,
        array $extra = []
    ): string {
        $metadata = [
            'provenance' => clean_param($provenance, PARAM_ALPHANUMEXT),
            'userid' => $userid,
            'timecreated' => time(),
            'ai_policy' => [
                'non_sovereign' => self::AI_NON_SOVEREIGN,
                'requires_human_validation' => self::AI_REQUIRES_HUMAN_VALIDATION,
                'allow_decision_automation' => self::AI_ALLOW_DECISION_AUTOMATION,
                'log_prompts' => self::AI_LOG_PROMPTS,
                'log_outputs' => self::AI_LOG_OUTPUTS,
                'require_provenance' => self::AI_REQUIRE_PROVENANCE,
                'require_uncertainty_label' => self::AI_REQUIRE_UNCERTAINTY_LABEL,
            ],
        ];

        $metadata = array_replace_recursive($metadata, $extra);

        return self::normalise_metadata($metadata);
    }
}

