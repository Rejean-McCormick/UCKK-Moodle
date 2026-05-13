<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/**
 * Local library for mod_uckkchallenge.
 *
 * This file contains activity-level helper functions for Défis King Klown:
 * status validation, transition rules, visibility checks, provenance helpers,
 * metadata handling, summary helpers, and status updates.
 *
 * It deliberately does not replace class-based services, renderers, events,
 * privacy providers, or Moodle core APIs.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('UCKKCHALLENGE_STATUS_DRAFT') || define('UCKKCHALLENGE_STATUS_DRAFT', 'draft');
defined('UCKKCHALLENGE_STATUS_PUBLISHED') || define('UCKKCHALLENGE_STATUS_PUBLISHED', 'published');
defined('UCKKCHALLENGE_STATUS_OPEN') || define('UCKKCHALLENGE_STATUS_OPEN', 'open');
defined('UCKKCHALLENGE_STATUS_SUBMITTED') || define('UCKKCHALLENGE_STATUS_SUBMITTED', 'submitted');
defined('UCKKCHALLENGE_STATUS_UNDER_REVIEW') || define('UCKKCHALLENGE_STATUS_UNDER_REVIEW', 'under_review');
defined('UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW') || define('UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW', 'integrity_review');
defined('UCKKCHALLENGE_STATUS_REVISION_REQUIRED') || define('UCKKCHALLENGE_STATUS_REVISION_REQUIRED', 'revision_required');
defined('UCKKCHALLENGE_STATUS_RESUBMITTED') || define('UCKKCHALLENGE_STATUS_RESUBMITTED', 'resubmitted');
defined('UCKKCHALLENGE_STATUS_VALIDATED') || define('UCKKCHALLENGE_STATUS_VALIDATED', 'validated');
defined('UCKKCHALLENGE_STATUS_ARCHIVED') || define('UCKKCHALLENGE_STATUS_ARCHIVED', 'archived');
defined('UCKKCHALLENGE_STATUS_CONTESTED') || define('UCKKCHALLENGE_STATUS_CONTESTED', 'contested');
defined('UCKKCHALLENGE_STATUS_INVALIDATED') || define('UCKKCHALLENGE_STATUS_INVALIDATED', 'invalidated');
defined('UCKKCHALLENGE_STATUS_CLOSED') || define('UCKKCHALLENGE_STATUS_CLOSED', 'closed');
defined('UCKKCHALLENGE_STATUS_EXPIRED') || define('UCKKCHALLENGE_STATUS_EXPIRED', 'expired');
defined('UCKKCHALLENGE_STATUS_WITHDRAWN') || define('UCKKCHALLENGE_STATUS_WITHDRAWN', 'withdrawn');

defined('UCKKCHALLENGE_VISIBILITY_PRIVATE') || define('UCKKCHALLENGE_VISIBILITY_PRIVATE', 'private');
defined('UCKKCHALLENGE_VISIBILITY_USER') || define('UCKKCHALLENGE_VISIBILITY_USER', 'user');
defined('UCKKCHALLENGE_VISIBILITY_GROUP') || define('UCKKCHALLENGE_VISIBILITY_GROUP', 'group');
defined('UCKKCHALLENGE_VISIBILITY_COURSE') || define('UCKKCHALLENGE_VISIBILITY_COURSE', 'course');
defined('UCKKCHALLENGE_VISIBILITY_COHORT') || define('UCKKCHALLENGE_VISIBILITY_COHORT', 'cohort');
defined('UCKKCHALLENGE_VISIBILITY_PROGRAM') || define('UCKKCHALLENGE_VISIBILITY_PROGRAM', 'program');
defined('UCKKCHALLENGE_VISIBILITY_INSTITUTION') || define('UCKKCHALLENGE_VISIBILITY_INSTITUTION', 'institution');
defined('UCKKCHALLENGE_VISIBILITY_PUBLIC') || define('UCKKCHALLENGE_VISIBILITY_PUBLIC', 'public');
defined('UCKKCHALLENGE_VISIBILITY_RESTRICTED') || define('UCKKCHALLENGE_VISIBILITY_RESTRICTED', 'restricted');
defined('UCKKCHALLENGE_VISIBILITY_RESTRICTED_INTEGRITY') || define('UCKKCHALLENGE_VISIBILITY_RESTRICTED_INTEGRITY', 'restricted_integrity');
defined('UCKKCHALLENGE_VISIBILITY_HIDDEN') || define('UCKKCHALLENGE_VISIBILITY_HIDDEN', 'hidden');
defined('UCKKCHALLENGE_VISIBILITY_ARCHIVED') || define('UCKKCHALLENGE_VISIBILITY_ARCHIVED', 'archived');

defined('UCKKCHALLENGE_PROVENANCE_HUMAN') || define('UCKKCHALLENGE_PROVENANCE_HUMAN', 'human');
defined('UCKKCHALLENGE_PROVENANCE_AI_ASSISTED') || define('UCKKCHALLENGE_PROVENANCE_AI_ASSISTED', 'ai_assisted');
defined('UCKKCHALLENGE_PROVENANCE_IMPORTED') || define('UCKKCHALLENGE_PROVENANCE_IMPORTED', 'imported');
defined('UCKKCHALLENGE_PROVENANCE_SYSTEM') || define('UCKKCHALLENGE_PROVENANCE_SYSTEM', 'system');
defined('UCKKCHALLENGE_PROVENANCE_ARCHIVE') || define('UCKKCHALLENGE_PROVENANCE_ARCHIVE', 'archive');
defined('UCKKCHALLENGE_PROVENANCE_ASSEMBLY') || define('UCKKCHALLENGE_PROVENANCE_ASSEMBLY', 'assembly');
defined('UCKKCHALLENGE_PROVENANCE_CHALLENGE') || define('UCKKCHALLENGE_PROVENANCE_CHALLENGE', 'challenge');
defined('UCKKCHALLENGE_PROVENANCE_INTEGRITY') || define('UCKKCHALLENGE_PROVENANCE_INTEGRITY', 'integrity');

defined('UCKKCHALLENGE_PROOF_TEXT') || define('UCKKCHALLENGE_PROOF_TEXT', 'text');
defined('UCKKCHALLENGE_PROOF_FILE') || define('UCKKCHALLENGE_PROOF_FILE', 'file');
defined('UCKKCHALLENGE_PROOF_URL') || define('UCKKCHALLENGE_PROOF_URL', 'url');
defined('UCKKCHALLENGE_PROOF_DATASET') || define('UCKKCHALLENGE_PROOF_DATASET', 'dataset');
defined('UCKKCHALLENGE_PROOF_IMAGE') || define('UCKKCHALLENGE_PROOF_IMAGE', 'image');
defined('UCKKCHALLENGE_PROOF_VIDEO') || define('UCKKCHALLENGE_PROOF_VIDEO', 'video');
defined('UCKKCHALLENGE_PROOF_TESTIMONY') || define('UCKKCHALLENGE_PROOF_TESTIMONY', 'testimony');
defined('UCKKCHALLENGE_PROOF_OBSERVATION') || define('UCKKCHALLENGE_PROOF_OBSERVATION', 'observation');
defined('UCKKCHALLENGE_PROOF_AI_LOG') || define('UCKKCHALLENGE_PROOF_AI_LOG', 'ai_log');
defined('UCKKCHALLENGE_PROOF_DECISION_RECORD') || define('UCKKCHALLENGE_PROOF_DECISION_RECORD', 'decision_record');

defined('UCKKCHALLENGE_FILEAREA_PROOF') || define('UCKKCHALLENGE_FILEAREA_PROOF', 'proof_files');
defined('UCKKCHALLENGE_FILEAREA_FEEDBACK') || define('UCKKCHALLENGE_FILEAREA_FEEDBACK', 'feedback_files');
defined('UCKKCHALLENGE_FILEAREA_ARCHIVE_EXPORT') || define('UCKKCHALLENGE_FILEAREA_ARCHIVE_EXPORT', 'archive_exports');

/**
 * Return canonical challenge statuses.
 *
 * @return array<int, string>
 */
function uckkchallenge_get_statuses(): array {
    return [
        UCKKCHALLENGE_STATUS_DRAFT,
        UCKKCHALLENGE_STATUS_PUBLISHED,
        UCKKCHALLENGE_STATUS_OPEN,
        UCKKCHALLENGE_STATUS_SUBMITTED,
        UCKKCHALLENGE_STATUS_UNDER_REVIEW,
        UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW,
        UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
        UCKKCHALLENGE_STATUS_RESUBMITTED,
        UCKKCHALLENGE_STATUS_VALIDATED,
        UCKKCHALLENGE_STATUS_ARCHIVED,
        UCKKCHALLENGE_STATUS_CONTESTED,
        UCKKCHALLENGE_STATUS_INVALIDATED,
        UCKKCHALLENGE_STATUS_CLOSED,
        UCKKCHALLENGE_STATUS_EXPIRED,
        UCKKCHALLENGE_STATUS_WITHDRAWN,
    ];
}

/**
 * Return canonical visibility values.
 *
 * @return array<int, string>
 */
function uckkchallenge_get_visibility_values(): array {
    return [
        UCKKCHALLENGE_VISIBILITY_PRIVATE,
        UCKKCHALLENGE_VISIBILITY_USER,
        UCKKCHALLENGE_VISIBILITY_GROUP,
        UCKKCHALLENGE_VISIBILITY_COURSE,
        UCKKCHALLENGE_VISIBILITY_COHORT,
        UCKKCHALLENGE_VISIBILITY_PROGRAM,
        UCKKCHALLENGE_VISIBILITY_INSTITUTION,
        UCKKCHALLENGE_VISIBILITY_PUBLIC,
        UCKKCHALLENGE_VISIBILITY_RESTRICTED,
        UCKKCHALLENGE_VISIBILITY_RESTRICTED_INTEGRITY,
        UCKKCHALLENGE_VISIBILITY_HIDDEN,
        UCKKCHALLENGE_VISIBILITY_ARCHIVED,
    ];
}

/**
 * Return allowed proof types.
 *
 * @return array<int, string>
 */
function uckkchallenge_get_proof_types(): array {
    return [
        UCKKCHALLENGE_PROOF_TEXT,
        UCKKCHALLENGE_PROOF_FILE,
        UCKKCHALLENGE_PROOF_URL,
        UCKKCHALLENGE_PROOF_DATASET,
        UCKKCHALLENGE_PROOF_IMAGE,
        UCKKCHALLENGE_PROOF_VIDEO,
        UCKKCHALLENGE_PROOF_TESTIMONY,
        UCKKCHALLENGE_PROOF_OBSERVATION,
        UCKKCHALLENGE_PROOF_AI_LOG,
        UCKKCHALLENGE_PROOF_DECISION_RECORD,
    ];
}

/**
 * Normalize a status value.
 *
 * @param string $status Raw status.
 * @return string Canonical status.
 */
function uckkchallenge_normalise_status(string $status): string {
    $status = clean_param($status, PARAM_ALPHANUMEXT);

    if (in_array($status, uckkchallenge_get_statuses(), true)) {
        return $status;
    }

    return UCKKCHALLENGE_STATUS_DRAFT;
}

/**
 * Normalize a visibility value.
 *
 * @param string $visibility Raw visibility.
 * @return string Canonical visibility.
 */
function uckkchallenge_normalise_visibility(string $visibility): string {
    $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

    if (in_array($visibility, uckkchallenge_get_visibility_values(), true)) {
        return $visibility;
    }

    return UCKKCHALLENGE_VISIBILITY_COURSE;
}

/**
 * Return the challenge workflow transition map.
 *
 * @return array<string, array<int, string>>
 */
function uckkchallenge_get_transition_map(): array {
    return [
        UCKKCHALLENGE_STATUS_DRAFT => [
            UCKKCHALLENGE_STATUS_PUBLISHED,
            UCKKCHALLENGE_STATUS_WITHDRAWN,
        ],
        UCKKCHALLENGE_STATUS_PUBLISHED => [
            UCKKCHALLENGE_STATUS_OPEN,
            UCKKCHALLENGE_STATUS_WITHDRAWN,
        ],
        UCKKCHALLENGE_STATUS_OPEN => [
            UCKKCHALLENGE_STATUS_SUBMITTED,
            UCKKCHALLENGE_STATUS_EXPIRED,
            UCKKCHALLENGE_STATUS_WITHDRAWN,
        ],
        UCKKCHALLENGE_STATUS_SUBMITTED => [
            UCKKCHALLENGE_STATUS_UNDER_REVIEW,
            UCKKCHALLENGE_STATUS_CONTESTED,
        ],
        UCKKCHALLENGE_STATUS_UNDER_REVIEW => [
            UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
            UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW,
            UCKKCHALLENGE_STATUS_VALIDATED,
            UCKKCHALLENGE_STATUS_CONTESTED,
        ],
        UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW => [
            UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
            UCKKCHALLENGE_STATUS_VALIDATED,
            UCKKCHALLENGE_STATUS_INVALIDATED,
            UCKKCHALLENGE_STATUS_CONTESTED,
        ],
        UCKKCHALLENGE_STATUS_REVISION_REQUIRED => [
            UCKKCHALLENGE_STATUS_RESUBMITTED,
            UCKKCHALLENGE_STATUS_WITHDRAWN,
            UCKKCHALLENGE_STATUS_CONTESTED,
        ],
        UCKKCHALLENGE_STATUS_RESUBMITTED => [
            UCKKCHALLENGE_STATUS_UNDER_REVIEW,
            UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW,
        ],
        UCKKCHALLENGE_STATUS_VALIDATED => [
            UCKKCHALLENGE_STATUS_ARCHIVED,
            UCKKCHALLENGE_STATUS_CONTESTED,
        ],
        UCKKCHALLENGE_STATUS_ARCHIVED => [
            UCKKCHALLENGE_STATUS_CLOSED,
        ],
        UCKKCHALLENGE_STATUS_CONTESTED => [
            UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW,
            UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
            UCKKCHALLENGE_STATUS_INVALIDATED,
            UCKKCHALLENGE_STATUS_VALIDATED,
        ],
        UCKKCHALLENGE_STATUS_EXPIRED => [
            UCKKCHALLENGE_STATUS_CLOSED,
        ],
        UCKKCHALLENGE_STATUS_WITHDRAWN => [
            UCKKCHALLENGE_STATUS_CLOSED,
        ],
        UCKKCHALLENGE_STATUS_INVALIDATED => [
            UCKKCHALLENGE_STATUS_CLOSED,
        ],
        UCKKCHALLENGE_STATUS_CLOSED => [],
    ];
}

/**
 * Check whether a status transition is allowed by the state machine.
 *
 * @param string $from Current status.
 * @param string $to Target status.
 * @return bool
 */
function uckkchallenge_can_transition_status(string $from, string $to): bool {
    $from = uckkchallenge_normalise_status($from);
    $to = uckkchallenge_normalise_status($to);

    if ($from === $to) {
        return true;
    }

    $map = uckkchallenge_get_transition_map();

    return in_array($to, $map[$from] ?? [], true);
}

/**
 * Return the capability required to perform a status transition.
 *
 * @param string $from Current status.
 * @param string $to Target status.
 * @return string
 */
function uckkchallenge_get_transition_capability(string $from, string $to): string {
    $from = uckkchallenge_normalise_status($from);
    $to = uckkchallenge_normalise_status($to);

    if (in_array($to, [
        UCKKCHALLENGE_STATUS_SUBMITTED,
        UCKKCHALLENGE_STATUS_RESUBMITTED,
    ], true)) {
        return 'mod/uckkchallenge:submitproof';
    }

    if (in_array($to, [
        UCKKCHALLENGE_STATUS_UNDER_REVIEW,
        UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
        UCKKCHALLENGE_STATUS_VALIDATED,
    ], true)) {
        return 'mod/uckkchallenge:evaluate';
    }

    if (in_array($to, [
        UCKKCHALLENGE_STATUS_INTEGRITY_REVIEW,
        UCKKCHALLENGE_STATUS_INVALIDATED,
    ], true)) {
        return 'mod/uckkchallenge:validateintegrity';
    }

    if ($to === UCKKCHALLENGE_STATUS_CONTESTED) {
        return 'mod/uckkchallenge:contest';
    }

    if (in_array($to, [
        UCKKCHALLENGE_STATUS_ARCHIVED,
        UCKKCHALLENGE_STATUS_CLOSED,
    ], true)) {
        return 'mod/uckkchallenge:archive';
    }

    return 'mod/uckkchallenge:createchallenge';
}

/**
 * Require permission for a status transition.
 *
 * @param string $from Current status.
 * @param string $to Target status.
 * @param context_module $context Module context.
 * @param int|null $userid Optional user id.
 */
function uckkchallenge_require_transition_allowed(
    string $from,
    string $to,
    context_module $context,
    ?int $userid = null
): void {
    if (!uckkchallenge_can_transition_status($from, $to)) {
        throw new moodle_exception('invalidstatustransition', 'mod_uckkchallenge', '', (object) [
            'from' => $from,
            'to' => $to,
        ]);
    }

    $capability = uckkchallenge_get_transition_capability($from, $to);
    require_capability($capability, $context, $userid);
}

/**
 * Return a localized status label.
 *
 * @param string $status Canonical status.
 * @return string
 */
function uckkchallenge_get_status_label(string $status): string {
    $status = uckkchallenge_normalise_status($status);
    $stringkey = 'status:' . str_replace('_', '', $status);

    if (get_string_manager()->string_exists($stringkey, 'mod_uckkchallenge')) {
        return get_string($stringkey, 'mod_uckkchallenge');
    }

    return ucfirst(str_replace('_', ' ', $status));
}

/**
 * Return a CSS-safe status class suffix.
 *
 * @param string $status Canonical status.
 * @return string
 */
function uckkchallenge_get_status_class(string $status): string {
    $status = uckkchallenge_normalise_status($status);

    return 'status-' . str_replace('_', '-', $status);
}

/**
 * Return a localized visibility label.
 *
 * @param string $visibility Canonical visibility.
 * @return string
 */
function uckkchallenge_get_visibility_label(string $visibility): string {
    $visibility = uckkchallenge_normalise_visibility($visibility);
    $stringkey = 'visibility:' . str_replace('_', '', $visibility);

    if (get_string_manager()->string_exists($stringkey, 'mod_uckkchallenge')) {
        return get_string($stringkey, 'mod_uckkchallenge');
    }

    return ucfirst(str_replace('_', ' ', $visibility));
}

/**
 * Load course module, course, challenge instance and context by course module id.
 *
 * @param int $cmid Course module id.
 * @return array{0: stdClass, 1: stdClass, 2: stdClass, 3: context_module}
 */
function uckkchallenge_get_cm_course_instance_context(int $cmid): array {
    global $DB;

    $cm = get_coursemodule_from_id('uckkchallenge', $cmid, 0, false, MUST_EXIST);
    $course = get_course((int) $cm->course);
    $challenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);
    $context = context_module::instance((int) $cm->id);

    return [$cm, $course, $challenge, $context];
}

/**
 * Load course module, course, challenge instance and context by challenge instance id.
 *
 * @param int $challengeid Challenge instance id.
 * @return array{0: stdClass, 1: stdClass, 2: stdClass, 3: context_module}
 */
function uckkchallenge_get_cm_course_instance_context_from_instance(int $challengeid): array {
    global $DB;

    $challenge = $DB->get_record('uckkchallenge', ['id' => $challengeid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkchallenge', $challengeid, (int) $challenge->course, false, MUST_EXIST);
    $course = get_course((int) $cm->course);
    $context = context_module::instance((int) $cm->id);

    return [$cm, $course, $challenge, $context];
}

/**
 * Require that a user can view a challenge.
 *
 * @param stdClass $challenge Challenge record.
 * @param context_module $context Module context.
 * @param int|null $userid Optional user id.
 */
function uckkchallenge_require_view(stdClass $challenge, context_module $context, ?int $userid = null): void {
    require_capability('mod/uckkchallenge:view', $context, $userid);

    if (!uckkchallenge_can_view_visibility($challenge, $context, $userid)) {
        throw new required_capability_exception(
            $context,
            'mod/uckkchallenge:view',
            'nopermissions',
            ''
        );
    }
}

/**
 * Check whether the current viewer can access a challenge visibility level.
 *
 * @param stdClass $challenge Challenge record.
 * @param context_module $context Module context.
 * @param int|null $userid Optional user id.
 * @return bool
 */
function uckkchallenge_can_view_visibility(stdClass $challenge, context_module $context, ?int $userid = null): bool {
    global $USER;

    $userid = $userid ?? (int) $USER->id;
    $visibility = uckkchallenge_normalise_visibility((string)($challenge->visibility ?? UCKKCHALLENGE_VISIBILITY_COURSE));

    if ($visibility === UCKKCHALLENGE_VISIBILITY_HIDDEN) {
        return has_capability('mod/uckkchallenge:createchallenge', $context, $userid);
    }

    if ($visibility === UCKKCHALLENGE_VISIBILITY_PRIVATE) {
        $createdby = (int)($challenge->createdby ?? 0);
        $ownerid = (int)($challenge->userid ?? 0);

        return $userid === $createdby
            || $userid === $ownerid
            || has_capability('mod/uckkchallenge:evaluate', $context, $userid);
    }

    if (in_array($visibility, [
        UCKKCHALLENGE_VISIBILITY_RESTRICTED,
        UCKKCHALLENGE_VISIBILITY_RESTRICTED_INTEGRITY,
    ], true)) {
        return has_capability('mod/uckkchallenge:validateintegrity', $context, $userid);
    }

    if ($visibility === UCKKCHALLENGE_VISIBILITY_ARCHIVED) {
        return has_capability('mod/uckkchallenge:archive', $context, $userid)
            || has_capability('mod/uckkchallenge:view', $context, $userid);
    }

    return has_capability('mod/uckkchallenge:view', $context, $userid);
}

/**
 * Decode a JSON metadata field into an array.
 *
 * @param string|null $metadata JSON metadata.
 * @return array<string, mixed>
 */
function uckkchallenge_decode_metadata(?string $metadata): array {
    if ($metadata === null || trim($metadata) === '') {
        return [];
    }

    try {
        $decoded = json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        debugging('Invalid mod_uckkchallenge metadata JSON: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

/**
 * Encode metadata as JSON.
 *
 * @param array<string, mixed> $metadata Metadata.
 * @return string
 */
function uckkchallenge_encode_metadata(array $metadata): string {
    return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

/**
 * Merge metadata from an existing record with a patch.
 *
 * @param stdClass $record Record with metadata field.
 * @param array<string, mixed> $patch Metadata patch.
 * @return string Encoded merged metadata.
 */
function uckkchallenge_merge_metadata(stdClass $record, array $patch): string {
    $existing = uckkchallenge_decode_metadata($record->metadata ?? null);
    $merged = array_replace_recursive($existing, $patch);

    return uckkchallenge_encode_metadata($merged);
}

/**
 * Build a stable provenance hash for important challenge data.
 *
 * @param array<string, mixed>|stdClass $data Provenance payload.
 * @return string
 */
function uckkchallenge_build_provenance_hash(array|stdClass $data): string {
    $payload = is_array($data) ? $data : (array) $data;
    ksort($payload);

    return hash('sha256', uckkchallenge_encode_metadata($payload));
}

/**
 * Update challenge status and write a state history record.
 *
 * @param int $challengeid Challenge instance id.
 * @param string $newstatus New canonical status.
 * @param string $reason Reason or short note.
 * @param array<string, mixed> $metadata Additional state metadata.
 * @param int|null $actorid Actor user id.
 * @return stdClass Updated challenge record.
 */
function uckkchallenge_update_status(
    int $challengeid,
    string $newstatus,
    string $reason = '',
    array $metadata = [],
    ?int $actorid = null
): stdClass {
    global $DB, $USER;

    [$cm, $course, $challenge, $context] = uckkchallenge_get_cm_course_instance_context_from_instance($challengeid);

    $actorid = $actorid ?? (int) $USER->id;
    $oldstatus = uckkchallenge_normalise_status((string)($challenge->status ?? UCKKCHALLENGE_STATUS_DRAFT));
    $newstatus = uckkchallenge_normalise_status($newstatus);

    uckkchallenge_require_transition_allowed($oldstatus, $newstatus, $context, $actorid);

    if ($oldstatus === $newstatus) {
        return $challenge;
    }

    $transaction = $DB->start_delegated_transaction();

    $now = time();

    $update = new stdClass();
    $update->id = $challengeid;
    $update->status = $newstatus;
    $update->modifiedby = $actorid;
    $update->timemodified = $now;
    $update->versionno = ((int)($challenge->versionno ?? 1)) + 1;
    $update->metadata = uckkchallenge_merge_metadata($challenge, [
        'lasttransition' => [
            'from' => $oldstatus,
            'to' => $newstatus,
            'reason' => $reason,
            'userid' => $actorid,
            'timecreated' => $now,
        ],
    ]);

    $update->provenancehash = uckkchallenge_build_provenance_hash([
        'component' => 'mod_uckkchallenge',
        'challengeid' => $challengeid,
        'from' => $oldstatus,
        'to' => $newstatus,
        'userid' => $actorid,
        'timecreated' => $now,
        'metadata' => $metadata,
    ]);

    $DB->update_record('uckkchallenge', $update);

    $state = new stdClass();
    $state->challengeid = $challengeid;
    $state->courseid = (int) $course->id;
    $state->cmid = (int) $cm->id;
    $state->contextid = (int) $context->id;
    $state->fromstatus = $oldstatus;
    $state->tostatus = $newstatus;
    $state->reason = $reason;
    $state->userid = $actorid;
    $state->createdby = $actorid;
    $state->timecreated = $now;
    $state->metadata = uckkchallenge_encode_metadata($metadata);
    $state->provenancehash = $update->provenancehash;

    $DB->insert_record('uckkchallenge_state', $state);

    $transaction->allow_commit();

    return $DB->get_record('uckkchallenge', ['id' => $challengeid], '*', MUST_EXIST);
}

/**
 * Count challenge submissions by status.
 *
 * @param int $challengeid Challenge instance id.
 * @return array<string, int>
 */
function uckkchallenge_count_submissions_by_status(int $challengeid): array {
    global $DB;

    $records = $DB->get_records_sql_menu(
        "SELECT status, COUNT(1)
           FROM {uckkchallenge_sub}
          WHERE challengeid = :challengeid
       GROUP BY status",
        ['challengeid' => $challengeid]
    );

    $counts = [];

    foreach (uckkchallenge_get_statuses() as $status) {
        $counts[$status] = isset($records[$status]) ? (int) $records[$status] : 0;
    }

    return $counts;
}

/**
 * Count proof records for a challenge.
 *
 * @param int $challengeid Challenge instance id.
 * @param int|null $userid Optional user filter.
 * @return int
 */
function uckkchallenge_count_proofs(int $challengeid, ?int $userid = null): int {
    global $DB;

    $params = ['challengeid' => $challengeid];
    $where = 'challengeid = :challengeid';

    if ($userid !== null) {
        $where .= ' AND userid = :userid';
        $params['userid'] = $userid;
    }

    return (int) $DB->count_records_select('uckkchallenge_proof', $where, $params);
}

/**
 * Count evaluation records for a challenge.
 *
 * @param int $challengeid Challenge instance id.
 * @return int
 */
function uckkchallenge_count_evaluations(int $challengeid): int {
    global $DB;

    return (int) $DB->count_records('uckkchallenge_eval', ['challengeid' => $challengeid]);
}

/**
 * Build a compact challenge summary suitable for dashboards and renderables.
 *
 * @param stdClass $challenge Challenge record.
 * @param context_module|null $context Optional context.
 * @return stdClass
 */
function uckkchallenge_build_summary(stdClass $challenge, ?context_module $context = null): stdClass {
    $status = uckkchallenge_normalise_status((string)($challenge->status ?? UCKKCHALLENGE_STATUS_DRAFT));
    $visibility = uckkchallenge_normalise_visibility((string)($challenge->visibility ?? UCKKCHALLENGE_VISIBILITY_COURSE));
    $metadata = uckkchallenge_decode_metadata($challenge->metadata ?? null);

    $summary = new stdClass();
    $summary->id = (int) $challenge->id;
    $summary->courseid = (int)($challenge->course ?? $challenge->courseid ?? 0);
    $summary->cmid = (int)($challenge->cmid ?? 0);
    $summary->name = format_string((string)($challenge->name ?? ''));
    $summary->status = $status;
    $summary->statuslabel = uckkchallenge_get_status_label($status);
    $summary->statusclass = uckkchallenge_get_status_class($status);
    $summary->visibility = $visibility;
    $summary->visibilitylabel = uckkchallenge_get_visibility_label($visibility);
    $summary->type = clean_param((string)($challenge->challengetype ?? $metadata['type'] ?? ''), PARAM_ALPHANUMEXT);
    $summary->timecreated = (int)($challenge->timecreated ?? 0);
    $summary->timemodified = (int)($challenge->timemodified ?? 0);
    $summary->duedate = (int)($challenge->duedate ?? $metadata['duedate'] ?? 0);
    $summary->hasduedate = $summary->duedate > 0;
    $summary->isoverdue = $summary->hasduedate
        && $summary->duedate < time()
        && !in_array($status, [
            UCKKCHALLENGE_STATUS_VALIDATED,
            UCKKCHALLENGE_STATUS_ARCHIVED,
            UCKKCHALLENGE_STATUS_CLOSED,
            UCKKCHALLENGE_STATUS_INVALIDATED,
            UCKKCHALLENGE_STATUS_WITHDRAWN,
        ], true);

    $summary->proofcount = uckkchallenge_count_proofs((int) $challenge->id);
    $summary->evaluationcount = uckkchallenge_count_evaluations((int) $challenge->id);
    $summary->submissioncounts = uckkchallenge_count_submissions_by_status((int) $challenge->id);

    if ($context !== null) {
        $summary->canview = uckkchallenge_can_view_visibility($challenge, $context);
        $summary->cansubmit = has_capability('mod/uckkchallenge:submitproof', $context);
        $summary->canevaluate = has_capability('mod/uckkchallenge:evaluate', $context);
        $summary->canvalidateintegrity = has_capability('mod/uckkchallenge:validateintegrity', $context);
        $summary->cancontest = has_capability('mod/uckkchallenge:contest', $context);
        $summary->canarchive = has_capability('mod/uckkchallenge:archive', $context);
    } else {
        $summary->canview = false;
        $summary->cansubmit = false;
        $summary->canevaluate = false;
        $summary->canvalidateintegrity = false;
        $summary->cancontest = false;
        $summary->canarchive = false;
    }

    return $summary;
}

/**
 * Return whether a challenge is accepting submissions.
 *
 * @param stdClass $challenge Challenge record.
 * @return bool
 */
function uckkchallenge_is_open_for_submission(stdClass $challenge): bool {
    $status = uckkchallenge_normalise_status((string)($challenge->status ?? UCKKCHALLENGE_STATUS_DRAFT));

    if (!in_array($status, [
        UCKKCHALLENGE_STATUS_OPEN,
        UCKKCHALLENGE_STATUS_REVISION_REQUIRED,
    ], true)) {
        return false;
    }

    $duedate = (int)($challenge->duedate ?? 0);

    return $duedate <= 0 || $duedate >= time();
}

/**
 * Require that a user can submit proof to a challenge.
 *
 * @param stdClass $challenge Challenge record.
 * @param context_module $context Module context.
 * @param int|null $userid Optional user id.
 */
function uckkchallenge_require_can_submit(stdClass $challenge, context_module $context, ?int $userid = null): void {
    require_capability('mod/uckkchallenge:submitproof', $context, $userid);

    if (!uckkchallenge_is_open_for_submission($challenge)) {
        throw new moodle_exception('challengenotopenforsubmission', 'mod_uckkchallenge');
    }
}

/**
 * Return whether a proof type is valid.
 *
 * @param string $prooftype Proof type.
 * @return bool
 */
function uckkchallenge_is_valid_proof_type(string $prooftype): bool {
    $prooftype = clean_param($prooftype, PARAM_ALPHANUMEXT);

    return in_array($prooftype, uckkchallenge_get_proof_types(), true);
}

/**
 * Validate a proof payload before storage.
 *
 * @param array<string, mixed> $payload Proof payload.
 * @return array<string, mixed> Cleaned payload.
 */
function uckkchallenge_clean_proof_payload(array $payload): array {
    $type = clean_param((string)($payload['type'] ?? UCKKCHALLENGE_PROOF_TEXT), PARAM_ALPHANUMEXT);

    if (!uckkchallenge_is_valid_proof_type($type)) {
        throw new moodle_exception('invalidprooftype', 'mod_uckkchallenge', '', $type);
    }

    $visibility = uckkchallenge_normalise_visibility((string)($payload['visibility'] ?? UCKKCHALLENGE_VISIBILITY_COURSE));
    $source = clean_param((string)($payload['source'] ?? UCKKCHALLENGE_PROVENANCE_HUMAN), PARAM_ALPHANUMEXT);

    return [
        'type' => $type,
        'title' => clean_param((string)($payload['title'] ?? ''), PARAM_TEXT),
        'description' => clean_param((string)($payload['description'] ?? ''), PARAM_RAW_TRIMMED),
        'url' => clean_param((string)($payload['url'] ?? ''), PARAM_URL),
        'visibility' => $visibility,
        'source' => $source,
        'relationtocriteria' => clean_param((string)($payload['relationtocriteria'] ?? ''), PARAM_RAW_TRIMMED),
        'integritystate' => clean_param((string)($payload['integritystate'] ?? 'unverified'), PARAM_ALPHANUMEXT),
        'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
    ];
}

/**
 * Add a proof record to a challenge.
 *
 * @param int $challengeid Challenge instance id.
 * @param int $userid Author user id.
 * @param array<string, mixed> $payload Proof payload.
 * @return int Proof id.
 */
function uckkchallenge_add_proof(int $challengeid, int $userid, array $payload): int {
    global $DB, $USER;

    [$cm, $course, $challenge, $context] = uckkchallenge_get_cm_course_instance_context_from_instance($challengeid);
    uckkchallenge_require_can_submit($challenge, $context, $userid);

    $clean = uckkchallenge_clean_proof_payload($payload);
    $now = time();

    $record = new stdClass();
    $record->challengeid = $challengeid;
    $record->courseid = (int) $course->id;
    $record->cmid = (int) $cm->id;
    $record->contextid = (int) $context->id;
    $record->userid = $userid;
    $record->createdby = (int) $USER->id;
    $record->modifiedby = (int) $USER->id;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->prooftype = $clean['type'];
    $record->title = $clean['title'];
    $record->description = $clean['description'];
    $record->source = $clean['source'];
    $record->visibility = $clean['visibility'];
    $record->relationtocriteria = $clean['relationtocriteria'];
    $record->integritystate = $clean['integritystate'];
    $record->status = UCKKCHALLENGE_STATUS_SUBMITTED;
    $record->versionno = 1;
    $record->metadata = uckkchallenge_encode_metadata($clean['metadata']);
    $record->provenancehash = uckkchallenge_build_provenance_hash([
        'component' => 'mod_uckkchallenge',
        'challengeid' => $challengeid,
        'userid' => $userid,
        'type' => $clean['type'],
        'title' => $clean['title'],
        'timecreated' => $now,
    ]);

    if ($clean['url'] !== '') {
        $record->url = $clean['url'];
    }

    return (int) $DB->insert_record('uckkchallenge_proof', $record);
}

/**
 * Return a default public safety notice for challenge pages.
 *
 * @return string
 */
function uckkchallenge_get_public_safety_notice(): string {
    if (get_string_manager()->string_exists('publicsafetynotice', 'mod_uckkchallenge')) {
        return get_string('publicsafetynotice', 'mod_uckkchallenge');
    }

    return 'Humour, theatre, and challenge are permitted. Harassment, humiliation, fabricated evidence, and abuse are not.';
}

/**
 * Return the non-sovereign AI notice for challenge assistance.
 *
 * @return string
 */
function uckkchallenge_get_ai_notice(): string {
    if (get_string_manager()->string_exists('ainonsovereignnotice', 'mod_uckkchallenge')) {
        return get_string('ainonsovereignnotice', 'mod_uckkchallenge');
    }

    return 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';
}