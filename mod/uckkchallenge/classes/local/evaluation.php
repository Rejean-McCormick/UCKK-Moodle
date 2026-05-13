<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local domain model for a UCKK challenge evaluation.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

use coding_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Domain model for one mentor/Inquisiteur-aware challenge evaluation.
 *
 * This class normalises, validates, scores, and exports an evaluation record.
 * It does not check capabilities, write to the database, award badges, certify
 * competencies, archive records, or decide integrity cases. Those operations
 * must remain in service classes and capability-checked controllers.
 */
final class evaluation {
    /** Evaluation status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Evaluation status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Evaluation status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Evaluation status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Evaluation status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Evaluation status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Evaluation status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Evaluation status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Evaluation status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Integrity state: unverified. */
    public const INTEGRITY_UNVERIFIED = 'unverified';

    /** Integrity state: human reviewed. */
    public const INTEGRITY_HUMAN_REVIEWED = 'human_reviewed';

    /** Integrity state: verified. */
    public const INTEGRITY_VERIFIED = 'verified';

    /** Integrity state: contested. */
    public const INTEGRITY_CONTESTED = 'contested';

    /** Integrity state: invalidated. */
    public const INTEGRITY_INVALIDATED = 'invalidated';

    /** Integrity state: archived. */
    public const INTEGRITY_ARCHIVED = 'archived';

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

    /** Provenance: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI-assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Archive recommendation: none. */
    public const ARCHIVE_NONE = 'none';

    /** Archive recommendation: summary. */
    public const ARCHIVE_SUMMARY = 'summary';

    /** Archive recommendation: full. */
    public const ARCHIVE_FULL = 'full';

    /** Integrity recommendation: none. */
    public const RECOMMENDATION_NONE = 'none';

    /** Integrity recommendation: approve. */
    public const RECOMMENDATION_APPROVE = 'approve';

    /** Integrity recommendation: review. */
    public const RECOMMENDATION_REVIEW = 'review';

    /** Integrity recommendation: correction. */
    public const RECOMMENDATION_CORRECTION = 'correction';

    /** Integrity recommendation: contest. */
    public const RECOMMENDATION_CONTEST = 'contest';

    /** Integrity recommendation: invalidate. */
    public const RECOMMENDATION_INVALIDATE = 'invalidate';

    /** Database id. */
    private int $id = 0;

    /** Parent uckkchallenge id. */
    private int $challengeid = 0;

    /** Evaluated submission id. */
    private int $submissionid = 0;

    /** Evaluated proof id, when evaluation targets a specific proof. */
    private int $proofid = 0;

    /** Moodle course id. */
    private int $courseid = 0;

    /** Moodle course module id. */
    private int $cmid = 0;

    /** Moodle context id. */
    private int $contextid = 0;

    /** User being evaluated. */
    private int $userid = 0;

    /** Evaluator user id. */
    private int $evaluatorid = 0;

    /** Evaluation status. */
    private string $status = self::STATUS_DRAFT;

    /** Evaluation visibility. */
    private string $visibility = self::VISIBILITY_COURSE;

    /** Integrity state attached to this evaluation. */
    private string $integritystate = self::INTEGRITY_UNVERIFIED;

    /** Source provenance. */
    private string $provenance = self::PROVENANCE_HUMAN;

    /** Optional provenance hash. */
    private string $provenancehash = '';

    /** Rubric rows. */
    private array $rubric = [];

    /** Competency rating rows. */
    private array $competencies = [];

    /** Badge trigger rows. */
    private array $badges = [];

    /** Numeric total score. */
    private float $totalscore = 0.0;

    /** Numeric max score. */
    private float $maxscore = 0.0;

    /** Calculated percent. */
    private float $percent = 0.0;

    /** Passing threshold percentage. */
    private float $passthreshold = 60.0;

    /** Whether the evaluation passes the configured threshold. */
    private bool $passed = false;

    /** Mentor feedback visible to the learner. */
    private string $feedback = '';

    /** Public summary safe for selected visibility. */
    private string $publicsummary = '';

    /** Private feedback for permitted staff/integrity viewers. */
    private string $privatefeedback = '';

    /** Integrity recommendation. */
    private string $integrityrecommendation = self::RECOMMENDATION_NONE;

    /** Archive recommendation. */
    private string $archiverecommendation = self::ARCHIVE_SUMMARY;

    /** Correction instructions. */
    private string $correctionrequired = '';

    /** Optional linked integrity case id. */
    private int $integritycaseid = 0;

    /** Optional linked archive item id. */
    private int $archiveitemid = 0;

    /** Record version. */
    private int $versionno = 1;

    /** User who created the record. */
    private int $createdby = 0;

    /** User who last modified the record. */
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

        $this->recalculate();
    }

    /**
     * Create an evaluation from a Moodle database record.
     *
     * @param stdClass $record Database record from {uckkchallenge_eval}.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self($record);
    }

    /**
     * Create a new draft evaluation.
     *
     * @param int $challengeid Challenge id.
     * @param int $submissionid Submission id.
     * @param int $userid Evaluated user id.
     * @param int $evaluatorid Evaluator user id.
     * @return self
     */
    public static function create_draft(
        int $challengeid,
        int $submissionid,
        int $userid,
        int $evaluatorid
    ): self {
        return new self([
            'challengeid' => $challengeid,
            'submissionid' => $submissionid,
            'userid' => $userid,
            'evaluatorid' => $evaluatorid,
            'status' => self::STATUS_DRAFT,
            'visibility' => self::VISIBILITY_COURSE,
            'integritystate' => self::INTEGRITY_UNVERIFIED,
            'provenance' => self::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Apply raw data to the object.
     *
     * @param array<string, mixed> $data Raw data.
     */
    private function apply_data(array $data): void {
        $this->id = max(0, (int)($data['id'] ?? $this->id));
        $this->challengeid = max(0, (int)($data['challengeid'] ?? $data['uckkchallengeid'] ?? $this->challengeid));
        $this->submissionid = max(0, (int)($data['submissionid'] ?? $this->submissionid));
        $this->proofid = max(0, (int)($data['proofid'] ?? $this->proofid));
        $this->courseid = max(0, (int)($data['courseid'] ?? $this->courseid));
        $this->cmid = max(0, (int)($data['cmid'] ?? $this->cmid));
        $this->contextid = max(0, (int)($data['contextid'] ?? $this->contextid));
        $this->userid = max(0, (int)($data['userid'] ?? $this->userid));
        $this->evaluatorid = max(0, (int)($data['evaluatorid'] ?? $data['reviewerid'] ?? $this->evaluatorid));

        $this->status = self::normalise_status((string)($data['status'] ?? $this->status));
        $this->visibility = self::normalise_visibility((string)($data['visibility'] ?? $this->visibility));
        $this->integritystate = self::normalise_integrity_state((string)($data['integritystate'] ?? $this->integritystate));
        $this->provenance = self::normalise_provenance((string)($data['provenance'] ?? $this->provenance));
        $this->provenancehash = clean_param((string)($data['provenancehash'] ?? $this->provenancehash), PARAM_ALPHANUMEXT);

        $this->rubric = self::normalise_rows($data['rubric'] ?? $data['rubricjson'] ?? $this->rubric);
        $this->competencies = self::normalise_rows($data['competencies'] ?? $data['competencyjson'] ?? $this->competencies);
        $this->badges = self::normalise_rows($data['badges'] ?? $data['badgejson'] ?? $this->badges);

        $this->totalscore = self::normalise_float($data['totalscore'] ?? $this->totalscore);
        $this->maxscore = self::normalise_float($data['maxscore'] ?? $this->maxscore);
        $this->percent = self::normalise_float($data['percent'] ?? $this->percent);
        $this->passthreshold = self::normalise_float($data['passthreshold'] ?? $this->passthreshold);
        $this->passed = !empty($data['passed']) || !empty($data['passstate']) || $this->passed;

        $this->feedback = trim((string)($data['feedback'] ?? $this->feedback));
        $this->publicsummary = trim((string)($data['publicsummary'] ?? $this->publicsummary));
        $this->privatefeedback = trim((string)($data['privatefeedback'] ?? $this->privatefeedback));
        $this->correctionrequired = trim((string)($data['correctionrequired'] ?? $this->correctionrequired));

        $this->integrityrecommendation = self::normalise_recommendation(
            (string)($data['integrityrecommendation'] ?? $this->integrityrecommendation)
        );
        $this->archiverecommendation = self::normalise_archive_recommendation(
            (string)($data['archiverecommendation'] ?? $this->archiverecommendation)
        );

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
     * Validate this evaluation.
     *
     * @throws coding_exception If data is invalid.
     */
    public function validate(): void {
        if ($this->challengeid <= 0) {
            throw new coding_exception('Evaluation requires a valid challengeid.');
        }

        if ($this->submissionid <= 0) {
            throw new coding_exception('Evaluation requires a valid submissionid.');
        }

        if ($this->userid <= 0) {
            throw new coding_exception('Evaluation requires a valid evaluated userid.');
        }

        if ($this->evaluatorid <= 0) {
            throw new coding_exception('Evaluation requires a valid evaluatorid.');
        }

        if (!in_array($this->status, self::get_allowed_statuses(), true)) {
            throw new coding_exception('Invalid evaluation status: ' . $this->status);
        }

        if (!in_array($this->visibility, self::get_allowed_visibilities(), true)) {
            throw new coding_exception('Invalid evaluation visibility: ' . $this->visibility);
        }

        if (!in_array($this->integritystate, self::get_allowed_integrity_states(), true)) {
            throw new coding_exception('Invalid evaluation integrity state: ' . $this->integritystate);
        }

        if (!in_array($this->provenance, self::get_allowed_provenance_sources(), true)) {
            throw new coding_exception('Invalid evaluation provenance: ' . $this->provenance);
        }

        if ($this->is_submitted_or_final() && trim($this->feedback) === '') {
            throw new coding_exception('Submitted evaluations require learner-visible feedback.');
        }

        if ($this->integrityrecommendation === self::RECOMMENDATION_CORRECTION && trim($this->correctionrequired) === '') {
            throw new coding_exception('Correction recommendations require correction instructions.');
        }

        if ($this->integrityrecommendation === self::RECOMMENDATION_INVALIDATE && trim($this->privatefeedback) === '') {
            throw new coding_exception('Invalidation recommendations require private justification.');
        }
    }

    /**
     * Convert to a Moodle database record for {uckkchallenge_eval}.
     *
     * @param int|null $userid Current user id for createdby/modifiedby defaults.
     * @param int|null $now Current timestamp.
     * @return stdClass
     */
    public function to_record(?int $userid = null, ?int $now = null): stdClass {
        $this->recalculate();
        $this->validate();

        $now ??= time();
        $userid ??= 0;

        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->challengeid = $this->challengeid;
        $record->submissionid = $this->submissionid;
        $record->proofid = $this->proofid;
        $record->courseid = $this->courseid;
        $record->cmid = $this->cmid;
        $record->contextid = $this->contextid;
        $record->userid = $this->userid;
        $record->evaluatorid = $this->evaluatorid;
        $record->status = $this->status;
        $record->visibility = $this->visibility;
        $record->integritystate = $this->integritystate;
        $record->provenance = $this->provenance;
        $record->provenancehash = $this->provenancehash !== '' ? $this->provenancehash : null;
        $record->rubricjson = self::encode_json($this->rubric);
        $record->competencyjson = self::encode_json($this->competencies);
        $record->badgejson = self::encode_json($this->badges);
        $record->totalscore = $this->totalscore;
        $record->maxscore = $this->maxscore;
        $record->percent = $this->percent;
        $record->passthreshold = $this->passthreshold;
        $record->passed = $this->passed ? 1 : 0;
        $record->feedback = $this->feedback;
        $record->publicsummary = $this->publicsummary;
        $record->privatefeedback = $this->privatefeedback;
        $record->integrityrecommendation = $this->integrityrecommendation;
        $record->archiverecommendation = $this->archiverecommendation;
        $record->correctionrequired = $this->correctionrequired;
        $record->integritycaseid = $this->integritycaseid ?: null;
        $record->archiveitemid = $this->archiveitemid ?: null;
        $record->versionno = $this->versionno;
        $record->createdby = $this->createdby > 0 ? $this->createdby : $userid;
        $record->modifiedby = $userid;
        $record->timecreated = $this->timecreated > 0 ? $this->timecreated : $now;
        $record->timemodified = $now;
        $record->metadata = self::encode_json($this->metadata);

        return $record;
    }

    /**
     * Convert to safe export data for templates, services, and tests.
     *
     * @return stdClass
     */
    public function to_export(): stdClass {
        $this->recalculate();

        $data = new stdClass();
        $data->id = $this->id;
        $data->challengeid = $this->challengeid;
        $data->submissionid = $this->submissionid;
        $data->proofid = $this->proofid;
        $data->courseid = $this->courseid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->userid = $this->userid;
        $data->evaluatorid = $this->evaluatorid;
        $data->status = $this->status;
        $data->visibility = $this->visibility;
        $data->integritystate = $this->integritystate;
        $data->provenance = $this->provenance;
        $data->provenancehash = $this->provenancehash;
        $data->rubric = $this->rubric;
        $data->competencies = $this->competencies;
        $data->badges = $this->badges;
        $data->totalscore = $this->totalscore;
        $data->maxscore = $this->maxscore;
        $data->percent = $this->percent;
        $data->passthreshold = $this->passthreshold;
        $data->passed = $this->passed;
        $data->feedback = $this->feedback;
        $data->publicsummary = $this->publicsummary;
        $data->privatefeedback = $this->privatefeedback;
        $data->integrityrecommendation = $this->integrityrecommendation;
        $data->archiverecommendation = $this->archiverecommendation;
        $data->correctionrequired = $this->correctionrequired;
        $data->integritycaseid = $this->integritycaseid;
        $data->archiveitemid = $this->archiveitemid;
        $data->versionno = $this->versionno;
        $data->metadata = $this->metadata;
        $data->final = $this->is_final();
        $data->restricted = $this->is_restricted();
        $data->requiresintegrityreview = $this->requires_integrity_review();
        $data->cantriggerbadge = $this->can_trigger_badges();

        return $data;
    }

    /**
     * Recalculate numeric totals from rubric rows.
     */
    public function recalculate(): void {
        $total = 0.0;
        $max = 0.0;

        foreach ($this->rubric as $index => $row) {
            $score = self::normalise_float($row['score'] ?? 0);
            $maxscore = self::normalise_float($row['maxscore'] ?? $row['max'] ?? 0);
            $weight = self::normalise_float($row['weight'] ?? 1);

            if ($weight <= 0) {
                $weight = 1.0;
            }

            $weightedscore = $score * $weight;
            $weightedmax = $maxscore * $weight;

            $this->rubric[$index]['score'] = $score;
            $this->rubric[$index]['maxscore'] = $maxscore;
            $this->rubric[$index]['weight'] = $weight;
            $this->rubric[$index]['weightedscore'] = $weightedscore;
            $this->rubric[$index]['weightedmaxscore'] = $weightedmax;

            $total += $weightedscore;
            $max += $weightedmax;
        }

        $this->totalscore = round($total, 5);
        $this->maxscore = round($max, 5);
        $this->percent = $max > 0 ? round(($total / $max) * 100, 5) : 0.0;
        $this->passed = $this->maxscore > 0 && $this->percent >= $this->passthreshold;
    }

    /**
     * Whether this evaluation is submitted or final.
     *
     * @return bool
     */
    public function is_submitted_or_final(): bool {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Whether this evaluation is final.
     *
     * @return bool
     */
    public function is_final(): bool {
        return in_array($this->status, [
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ], true);
    }

    /**
     * Whether this evaluation is restricted.
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
     * Whether this evaluation requires integrity review.
     *
     * @return bool
     */
    public function requires_integrity_review(): bool {
        return in_array($this->integrityrecommendation, [
            self::RECOMMENDATION_REVIEW,
            self::RECOMMENDATION_CORRECTION,
            self::RECOMMENDATION_CONTEST,
            self::RECOMMENDATION_INVALIDATE,
        ], true) || in_array($this->integritystate, [
            self::INTEGRITY_CONTESTED,
            self::INTEGRITY_INVALIDATED,
        ], true);
    }

    /**
     * Whether badge triggers may be used.
     *
     * @return bool
     */
    public function can_trigger_badges(): bool {
        return $this->passed
            && $this->status === self::STATUS_VALIDATED
            && in_array($this->integritystate, [self::INTEGRITY_HUMAN_REVIEWED, self::INTEGRITY_VERIFIED], true)
            && !$this->requires_integrity_review()
            && !empty($this->badges);
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
     * Return challenge id.
     *
     * @return int
     */
    public function get_challengeid(): int {
        return $this->challengeid;
    }

    /**
     * Return submission id.
     *
     * @return int
     */
    public function get_submissionid(): int {
        return $this->submissionid;
    }

    /**
     * Return evaluated user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return evaluator id.
     *
     * @return int
     */
    public function get_evaluatorid(): int {
        return $this->evaluatorid;
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
     * Return integrity state.
     *
     * @return string
     */
    public function get_integritystate(): string {
        return $this->integritystate;
    }

    /**
     * Return rubric rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_rubric(): array {
        return $this->rubric;
    }

    /**
     * Return competency rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_competencies(): array {
        return $this->competencies;
    }

    /**
     * Return badge rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_badges(): array {
        return $this->badges;
    }

    /**
     * Return percent.
     *
     * @return float
     */
    public function get_percent(): float {
        $this->recalculate();
        return $this->percent;
    }

    /**
     * Return whether the evaluation passed.
     *
     * @return bool
     */
    public function has_passed(): bool {
        $this->recalculate();
        return $this->passed;
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
     * Return a copy with database id.
     *
     * @param int $id Database id.
     * @return self
     */
    public function with_id(int $id): self {
        $clone = clone $this;
        $clone->id = max(0, $id);
        return $clone;
    }

    /**
     * Return a copy with Moodle context references.
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
     * Return a copy with status.
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
     * Return a copy with integrity state.
     *
     * @param string $state Integrity state.
     * @return self
     */
    public function with_integrity_state(string $state): self {
        $clone = clone $this;
        $clone->integritystate = self::normalise_integrity_state($state);
        return $clone;
    }

    /**
     * Return a copy with visibility.
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
     * Return a copy with rubric rows.
     *
     * @param array<int, array<string, mixed>|stdClass> $rubric Rubric rows.
     * @return self
     */
    public function with_rubric(array $rubric): self {
        $clone = clone $this;
        $clone->rubric = self::normalise_rows($rubric);
        $clone->recalculate();
        return $clone;
    }

    /**
     * Return a copy with competency rows.
     *
     * @param array<int, array<string, mixed>|stdClass> $competencies Competency rows.
     * @return self
     */
    public function with_competencies(array $competencies): self {
        $clone = clone $this;
        $clone->competencies = self::normalise_rows($competencies);
        return $clone;
    }

    /**
     * Return a copy with badge rows.
     *
     * @param array<int, array<string, mixed>|stdClass> $badges Badge rows.
     * @return self
     */
    public function with_badges(array $badges): self {
        $clone = clone $this;
        $clone->badges = self::normalise_rows($badges);
        return $clone;
    }

    /**
     * Return a copy with textual feedback fields.
     *
     * @param string $feedback Learner-visible feedback.
     * @param string $publicsummary Public summary.
     * @param string $privatefeedback Private feedback.
     * @return self
     */
    public function with_feedback(
        string $feedback,
        string $publicsummary = '',
        string $privatefeedback = ''
    ): self {
        $clone = clone $this;
        $clone->feedback = trim($feedback);
        $clone->publicsummary = trim($publicsummary);
        $clone->privatefeedback = trim($privatefeedback);
        return $clone;
    }

    /**
     * Return a copy with integrity recommendation.
     *
     * @param string $recommendation Integrity recommendation.
     * @param string $correctionrequired Correction instructions.
     * @param int $caseid Optional linked integrity case id.
     * @return self
     */
    public function with_integrity_recommendation(
        string $recommendation,
        string $correctionrequired = '',
        int $caseid = 0
    ): self {
        $clone = clone $this;
        $clone->integrityrecommendation = self::normalise_recommendation($recommendation);
        $clone->correctionrequired = trim($correctionrequired);
        $clone->integritycaseid = max(0, $caseid);
        return $clone;
    }

    /**
     * Return a copy with archive recommendation.
     *
     * @param string $recommendation Archive recommendation.
     * @param int $archiveitemid Optional linked archive item id.
     * @return self
     */
    public function with_archive_recommendation(string $recommendation, int $archiveitemid = 0): self {
        $clone = clone $this;
        $clone->archiverecommendation = self::normalise_archive_recommendation($recommendation);
        $clone->archiveitemid = max(0, $archiveitemid);
        return $clone;
    }

    /**
     * Return a copy marked as modified.
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
     * Allowed evaluation statuses.
     *
     * @return string[]
     */
    public static function get_allowed_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Allowed integrity states.
     *
     * @return string[]
     */
    public static function get_allowed_integrity_states(): array {
        return [
            self::INTEGRITY_UNVERIFIED,
            self::INTEGRITY_HUMAN_REVIEWED,
            self::INTEGRITY_VERIFIED,
            self::INTEGRITY_CONTESTED,
            self::INTEGRITY_INVALIDATED,
            self::INTEGRITY_ARCHIVED,
        ];
    }

    /**
     * Allowed visibility values.
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
     * Allowed integrity recommendations.
     *
     * @return string[]
     */
    public static function get_allowed_recommendations(): array {
        return [
            self::RECOMMENDATION_NONE,
            self::RECOMMENDATION_APPROVE,
            self::RECOMMENDATION_REVIEW,
            self::RECOMMENDATION_CORRECTION,
            self::RECOMMENDATION_CONTEST,
            self::RECOMMENDATION_INVALIDATE,
        ];
    }

    /**
     * Allowed archive recommendations.
     *
     * @return string[]
     */
    public static function get_allowed_archive_recommendations(): array {
        return [
            self::ARCHIVE_NONE,
            self::ARCHIVE_SUMMARY,
            self::ARCHIVE_FULL,
        ];
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
     * Normalise integrity state.
     *
     * @param string $state Raw state.
     * @return string
     */
    private static function normalise_integrity_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);
        return in_array($state, self::get_allowed_integrity_states(), true) ? $state : self::INTEGRITY_UNVERIFIED;
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
     * Normalise integrity recommendation.
     *
     * @param string $recommendation Raw recommendation.
     * @return string
     */
    private static function normalise_recommendation(string $recommendation): string {
        $recommendation = clean_param($recommendation, PARAM_ALPHANUMEXT);
        return in_array($recommendation, self::get_allowed_recommendations(), true)
            ? $recommendation
            : self::RECOMMENDATION_NONE;
    }

    /**
     * Normalise archive recommendation.
     *
     * @param string $recommendation Raw recommendation.
     * @return string
     */
    private static function normalise_archive_recommendation(string $recommendation): string {
        $recommendation = clean_param($recommendation, PARAM_ALPHANUMEXT);
        return in_array($recommendation, self::get_allowed_archive_recommendations(), true)
            ? $recommendation
            : self::ARCHIVE_SUMMARY;
    }

    /**
     * Normalise rows from array, object, or JSON.
     *
     * @param mixed $rows Raw rows.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_rows(mixed $rows): array {
        if ($rows === null || $rows === '') {
            return [];
        }

        if (is_string($rows)) {
            $decoded = json_decode($rows, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            $rows = $decoded;
        }

        if ($rows instanceof stdClass) {
            $rows = (array)$rows;
        }

        if (!is_array($rows)) {
            return [];
        }

        $normalised = [];

        foreach ($rows as $row) {
            if ($row instanceof stdClass) {
                $row = (array)$row;
            }

            if (!is_array($row)) {
                continue;
            }

            $normalised[] = self::normalise_row($row);
        }

        return $normalised;
    }

    /**
     * Normalise one variable JSON row.
     *
     * @param array<string, mixed> $row Raw row.
     * @return array<string, mixed>
     */
    private static function normalise_row(array $row): array {
        $normalised = [];

        foreach ($row as $key => $value) {
            $cleankey = clean_param((string)$key, PARAM_ALPHANUMEXT);

            if ($cleankey === '') {
                continue;
            }

            if (is_string($value)) {
                $normalised[$cleankey] = trim($value);
            } else if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $normalised[$cleankey] = $value;
            } else if (is_array($value)) {
                $normalised[$cleankey] = self::normalise_metadata($value);
            } else if ($value instanceof stdClass) {
                $normalised[$cleankey] = self::normalise_metadata((array)$value);
            }
        }

        return $normalised;
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

    /**
     * Normalise float values.
     *
     * @param mixed $value Raw value.
     * @return float
     */
    private static function normalise_float(mixed $value): float {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (!is_numeric($value)) {
            return 0.0;
        }

        return (float)$value;
    }

    /**
     * Encode data as JSON for Moodle database records.
     *
     * @param mixed $data Data to encode.
     * @return string|null
     */
    private static function encode_json(mixed $data): ?string {
        if ($data === [] || $data === null) {
            return null;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }
}