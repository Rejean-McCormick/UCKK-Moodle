<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Legacy helper library for the UCKK Assembly activity.
 *
 * This file contains lightweight procedural helpers for page controllers,
 * renderables, forms, and lib.php callbacks. Workflow persistence and privileged
 * actions should remain in classes/service objects.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Assembly types.
const UCKKASSEMBLY_TYPE_SAVOIRS = 'savoirs';
const UCKKASSEMBLY_TYPE_DEFIS = 'defis';
const UCKKASSEMBLY_TYPE_JOUEURS = 'joueurs';
const UCKKASSEMBLY_TYPE_BATISSEURS = 'batisseurs';
const UCKKASSEMBLY_TYPE_INQUISITEURS = 'inquisiteurs';
const UCKKASSEMBLY_TYPE_GRAND_JEU = 'grand_jeu';

// Common statuses.
const UCKKASSEMBLY_STATUS_DRAFT = 'draft';
const UCKKASSEMBLY_STATUS_ACTIVE = 'active';
const UCKKASSEMBLY_STATUS_PENDING = 'pending';
const UCKKASSEMBLY_STATUS_PENDING_REVIEW = 'pending_review';
const UCKKASSEMBLY_STATUS_VALIDATED = 'validated';
const UCKKASSEMBLY_STATUS_REJECTED = 'rejected';
const UCKKASSEMBLY_STATUS_CONTESTED = 'contested';
const UCKKASSEMBLY_STATUS_CLOSED = 'closed';
const UCKKASSEMBLY_STATUS_ARCHIVED = 'archived';
const UCKKASSEMBLY_STATUS_CANCELLED = 'cancelled';

// Assembly workflow statuses.
const UCKKASSEMBLY_STATE_DRAFT = 'draft';
const UCKKASSEMBLY_STATE_SCHEDULED = 'scheduled';
const UCKKASSEMBLY_STATE_OPEN = 'open';
const UCKKASSEMBLY_STATE_DELIBERATING = 'deliberating';
const UCKKASSEMBLY_STATE_VOTING = 'voting';
const UCKKASSEMBLY_STATE_DECIDED = 'decided';
const UCKKASSEMBLY_STATE_CONTESTATION = 'contestation';
const UCKKASSEMBLY_STATE_ARCHIVED = 'archived';
const UCKKASSEMBLY_STATE_CLOSED = 'closed';
const UCKKASSEMBLY_STATE_CANCELLED = 'cancelled';

// Motion statuses.
const UCKKASSEMBLY_MOTION_DRAFT = 'draft';
const UCKKASSEMBLY_MOTION_SUBMITTED = 'submitted';
const UCKKASSEMBLY_MOTION_OPEN = 'open';
const UCKKASSEMBLY_MOTION_AMENDED = 'amended';
const UCKKASSEMBLY_MOTION_ACCEPTED = 'accepted';
const UCKKASSEMBLY_MOTION_REJECTED = 'rejected';
const UCKKASSEMBLY_MOTION_WITHDRAWN = 'withdrawn';
const UCKKASSEMBLY_MOTION_CONTESTED = 'contested';
const UCKKASSEMBLY_MOTION_ARCHIVED = 'archived';

// Decision types.
const UCKKASSEMBLY_DECISION_INFORMATION = 'information';
const UCKKASSEMBLY_DECISION_RECOMMENDATION = 'recommendation';
const UCKKASSEMBLY_DECISION_VALIDATION = 'validation';
const UCKKASSEMBLY_DECISION_CORRECTION = 'correction';
const UCKKASSEMBLY_DECISION_REJECTION = 'rejection';
const UCKKASSEMBLY_DECISION_ARCHIVAL = 'archival';
const UCKKASSEMBLY_DECISION_INTEGRITY = 'integrity';

// Vote values.
const UCKKASSEMBLY_VOTE_FOR = 'for';
const UCKKASSEMBLY_VOTE_AGAINST = 'against';
const UCKKASSEMBLY_VOTE_ABSTAIN = 'abstain';
const UCKKASSEMBLY_VOTE_BLOCK = 'block';

// Visibility values.
const UCKKASSEMBLY_VISIBILITY_PRIVATE = 'private';
const UCKKASSEMBLY_VISIBILITY_GROUP = 'group';
const UCKKASSEMBLY_VISIBILITY_COURSE = 'course';
const UCKKASSEMBLY_VISIBILITY_COHORT = 'cohort';
const UCKKASSEMBLY_VISIBILITY_PROGRAM = 'program';
const UCKKASSEMBLY_VISIBILITY_INSTITUTION = 'institution';
const UCKKASSEMBLY_VISIBILITY_PUBLIC = 'public';
const UCKKASSEMBLY_VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

// File areas.
const UCKKASSEMBLY_FILEAREA_MOTION = 'motion_attachments';
const UCKKASSEMBLY_FILEAREA_AMENDMENT = 'amendment_attachments';
const UCKKASSEMBLY_FILEAREA_DECISION = 'decision_attachments';
const UCKKASSEMBLY_FILEAREA_MINUTES = 'minutes_files';
const UCKKASSEMBLY_FILEAREA_CONTEST = 'contest_attachments';

/**
 * Load a UCKK assembly instance by course module id.
 *
 * @param int $cmid Course module id.
 * @return array{0:stdClass,1:stdClass,2:stdClass,3:context_module}
 */
function uckkassembly_get_instance_from_cmid(int $cmid): array {
    global $DB;

    $cm = get_coursemodule_from_id('uckkassembly', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

    return [$course, $cm, $assembly, $context];
}

/**
 * Load a UCKK assembly instance by activity instance id.
 *
 * @param int $instanceid Assembly instance id.
 * @return array{0:stdClass,1:stdClass,2:stdClass,3:context_module}
 */
function uckkassembly_get_instance_from_id(int $instanceid): array {
    global $DB;

    $assembly = $DB->get_record('uckkassembly', ['id' => $instanceid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $assembly->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('uckkassembly', $assembly->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    return [$course, $cm, $assembly, $context];
}

/**
 * Load an assembly instance using either a cm id or instance id.
 *
 * @param int $cmid Course module id.
 * @param int $instanceid Activity instance id.
 * @return array{0:stdClass,1:stdClass,2:stdClass,3:context_module}
 */
function uckkassembly_get_instance(int $cmid = 0, int $instanceid = 0): array {
    if ($cmid > 0) {
        return uckkassembly_get_instance_from_cmid($cmid);
    }

    if ($instanceid > 0) {
        return uckkassembly_get_instance_from_id($instanceid);
    }

    throw new moodle_exception('missingparam', 'error', '', 'id');
}

/**
 * Require login and view capability for an assembly.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 */
function uckkassembly_require_view(stdClass $course, stdClass $cm, context_module $context): void {
    require_login($course, false, $cm);
    require_capability('mod/uckkassembly:view', $context);
}

/**
 * Return canonical assembly type options.
 *
 * @return array<string,string>
 */
function uckkassembly_get_assembly_type_options(): array {
    return [
        UCKKASSEMBLY_TYPE_SAVOIRS => get_string('assemblytype:savoirs', 'uckkassembly'),
        UCKKASSEMBLY_TYPE_DEFIS => get_string('assemblytype:defis', 'uckkassembly'),
        UCKKASSEMBLY_TYPE_JOUEURS => get_string('assemblytype:joueurs', 'uckkassembly'),
        UCKKASSEMBLY_TYPE_BATISSEURS => get_string('assemblytype:batisseurs', 'uckkassembly'),
        UCKKASSEMBLY_TYPE_INQUISITEURS => get_string('assemblytype:inquisiteurs', 'uckkassembly'),
        UCKKASSEMBLY_TYPE_GRAND_JEU => get_string('assemblytype:grand_jeu', 'uckkassembly'),
    ];
}

/**
 * Return canonical assembly state options.
 *
 * @return array<string,string>
 */
function uckkassembly_get_state_options(): array {
    return [
        UCKKASSEMBLY_STATE_DRAFT => get_string('state:draft', 'uckkassembly'),
        UCKKASSEMBLY_STATE_SCHEDULED => get_string('state:scheduled', 'uckkassembly'),
        UCKKASSEMBLY_STATE_OPEN => get_string('state:open', 'uckkassembly'),
        UCKKASSEMBLY_STATE_DELIBERATING => get_string('state:deliberating', 'uckkassembly'),
        UCKKASSEMBLY_STATE_VOTING => get_string('state:voting', 'uckkassembly'),
        UCKKASSEMBLY_STATE_DECIDED => get_string('state:decided', 'uckkassembly'),
        UCKKASSEMBLY_STATE_CONTESTATION => get_string('state:contestation', 'uckkassembly'),
        UCKKASSEMBLY_STATE_ARCHIVED => get_string('state:archived', 'uckkassembly'),
        UCKKASSEMBLY_STATE_CLOSED => get_string('state:closed', 'uckkassembly'),
        UCKKASSEMBLY_STATE_CANCELLED => get_string('state:cancelled', 'uckkassembly'),
    ];
}

/**
 * Return canonical decision type options.
 *
 * @return array<string,string>
 */
function uckkassembly_get_decision_type_options(): array {
    return [
        UCKKASSEMBLY_DECISION_INFORMATION => get_string('decisiontype:information', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_RECOMMENDATION => get_string('decisiontype:recommendation', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_VALIDATION => get_string('decisiontype:validation', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_CORRECTION => get_string('decisiontype:correction', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_REJECTION => get_string('decisiontype:rejection', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_ARCHIVAL => get_string('decisiontype:archival', 'uckkassembly'),
        UCKKASSEMBLY_DECISION_INTEGRITY => get_string('decisiontype:integrity', 'uckkassembly'),
    ];
}

/**
 * Return vote value options.
 *
 * @return array<string,string>
 */
function uckkassembly_get_vote_options(): array {
    return [
        UCKKASSEMBLY_VOTE_FOR => get_string('vote:for', 'uckkassembly'),
        UCKKASSEMBLY_VOTE_AGAINST => get_string('vote:against', 'uckkassembly'),
        UCKKASSEMBLY_VOTE_ABSTAIN => get_string('vote:abstain', 'uckkassembly'),
        UCKKASSEMBLY_VOTE_BLOCK => get_string('vote:block', 'uckkassembly'),
    ];
}

/**
 * Return visibility options.
 *
 * @return array<string,string>
 */
function uckkassembly_get_visibility_options(): array {
    return [
        UCKKASSEMBLY_VISIBILITY_PRIVATE => get_string('visibility:private', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_GROUP => get_string('visibility:group', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_COURSE => get_string('visibility:course', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_COHORT => get_string('visibility:cohort', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_PROGRAM => get_string('visibility:program', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_INSTITUTION => get_string('visibility:institution', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_PUBLIC => get_string('visibility:public', 'uckkassembly'),
        UCKKASSEMBLY_VISIBILITY_RESTRICTED_INTEGRITY => get_string('visibility:restricted_integrity', 'uckkassembly'),
    ];
}

/**
 * Normalize an assembly type.
 *
 * @param string $type Raw type.
 * @return string
 */
function uckkassembly_normalise_type(string $type): string {
    $type = clean_param($type, PARAM_ALPHANUMEXT);

    return array_key_exists($type, uckkassembly_get_assembly_type_options())
        ? $type
        : UCKKASSEMBLY_TYPE_SAVOIRS;
}

/**
 * Normalize an assembly state.
 *
 * @param string $state Raw state.
 * @return string
 */
function uckkassembly_normalise_state(string $state): string {
    $state = clean_param($state, PARAM_ALPHANUMEXT);

    return array_key_exists($state, uckkassembly_get_state_options())
        ? $state
        : UCKKASSEMBLY_STATE_DRAFT;
}

/**
 * Normalize a decision type.
 *
 * @param string $type Raw decision type.
 * @return string
 */
function uckkassembly_normalise_decision_type(string $type): string {
    $type = clean_param($type, PARAM_ALPHANUMEXT);

    return array_key_exists($type, uckkassembly_get_decision_type_options())
        ? $type
        : UCKKASSEMBLY_DECISION_INFORMATION;
}

/**
 * Normalize a visibility value.
 *
 * @param string $visibility Raw visibility.
 * @return string
 */
function uckkassembly_normalise_visibility(string $visibility): string {
    $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

    return array_key_exists($visibility, uckkassembly_get_visibility_options())
        ? $visibility
        : UCKKASSEMBLY_VISIBILITY_COURSE;
}

/**
 * Return whether the assembly accepts motions.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_accepts_motions(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    return in_array($state, [
        UCKKASSEMBLY_STATE_OPEN,
        UCKKASSEMBLY_STATE_DELIBERATING,
    ], true);
}

/**
 * Return whether the assembly accepts amendments.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_accepts_amendments(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    return in_array($state, [
        UCKKASSEMBLY_STATE_OPEN,
        UCKKASSEMBLY_STATE_DELIBERATING,
    ], true);
}

/**
 * Return whether the assembly accepts votes.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_accepts_votes(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    return $state === UCKKASSEMBLY_STATE_VOTING;
}

/**
 * Return whether decisions may be published.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_accepts_decision_publication(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    return in_array($state, [
        UCKKASSEMBLY_STATE_VOTING,
        UCKKASSEMBLY_STATE_DECIDED,
    ], true);
}

/**
 * Return whether the assembly is in a contestable state.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_is_contestable(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    if (!in_array($state, [
        UCKKASSEMBLY_STATE_DECIDED,
        UCKKASSEMBLY_STATE_CONTESTATION,
    ], true)) {
        return false;
    }

    if (!empty($assembly->contestuntil) && (int)$assembly->contestuntil < time()) {
        return false;
    }

    return true;
}

/**
 * Return whether the assembly may be archived.
 *
 * @param stdClass $assembly Assembly record.
 * @return bool
 */
function uckkassembly_is_archive_ready(stdClass $assembly): bool {
    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));

    return in_array($state, [
        UCKKASSEMBLY_STATE_DECIDED,
        UCKKASSEMBLY_STATE_CONTESTATION,
        UCKKASSEMBLY_STATE_CLOSED,
    ], true);
}

/**
 * Check if user can create or configure an assembly.
 *
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_create(context_module $context, stdClass|int|null $user = null): bool {
    return has_capability('mod/uckkassembly:createassembly', $context, $user);
}

/**
 * Check if user can propose motions.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_propose_motion(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_accepts_motions($assembly)
        && has_capability('mod/uckkassembly:proposemotion', $context, $user);
}

/**
 * Check if user can amend motions.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_amend_motion(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_accepts_amendments($assembly)
        && has_capability('mod/uckkassembly:amendmotion', $context, $user);
}

/**
 * Check if user can vote.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_vote(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_accepts_votes($assembly)
        && has_capability('mod/uckkassembly:vote', $context, $user);
}

/**
 * Check if user can publish decisions.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_publish_decision(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_accepts_decision_publication($assembly)
        && has_capability('mod/uckkassembly:publishdecision', $context, $user);
}

/**
 * Check if user can contest decisions.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_contest_decision(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_is_contestable($assembly)
        && has_capability('mod/uckkassembly:contestdecision', $context, $user);
}

/**
 * Check if user can archive the assembly.
 *
 * @param stdClass $assembly Assembly record.
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_archive(stdClass $assembly, context_module $context, stdClass|int|null $user = null): bool {
    return uckkassembly_is_archive_ready($assembly)
        && has_capability('mod/uckkassembly:archive', $context, $user);
}

/**
 * Check whether a user may view restricted assembly data.
 *
 * @param context_module $context Module context.
 * @param stdClass|int|null $user User record or id.
 * @return bool
 */
function uckkassembly_can_view_restricted(context_module $context, stdClass|int|null $user = null): bool {
    return has_capability('mod/uckkassembly:publishdecision', $context, $user)
        || has_capability('tool/uckkintegrity:viewrestricted', $context, $user);
}

/**
 * Format an assembly state label.
 *
 * @param string $state Assembly state.
 * @return string
 */
function uckkassembly_format_state(string $state): string {
    $state = uckkassembly_normalise_state($state);
    $options = uckkassembly_get_state_options();

    return $options[$state] ?? ucfirst(str_replace('_', ' ', $state));
}

/**
 * Format an assembly type label.
 *
 * @param string $type Assembly type.
 * @return string
 */
function uckkassembly_format_type(string $type): string {
    $type = uckkassembly_normalise_type($type);
    $options = uckkassembly_get_assembly_type_options();

    return $options[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * Format a decision type label.
 *
 * @param string $type Decision type.
 * @return string
 */
function uckkassembly_format_decision_type(string $type): string {
    $type = uckkassembly_normalise_decision_type($type);
    $options = uckkassembly_get_decision_type_options();

    return $options[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * Format a visibility label.
 *
 * @param string $visibility Visibility value.
 * @return string
 */
function uckkassembly_format_visibility(string $visibility): string {
    $visibility = uckkassembly_normalise_visibility($visibility);
    $options = uckkassembly_get_visibility_options();

    return $options[$visibility] ?? ucfirst(str_replace('_', ' ', $visibility));
}

/**
 * Build standard URLs for an assembly instance.
 *
 * @param stdClass $cm Course module record.
 * @return array<string,moodle_url>
 */
function uckkassembly_get_urls(stdClass $cm): array {
    return [
        'view' => new moodle_url('/mod/uckkassembly/view.php', ['id' => $cm->id]),
        'motion' => new moodle_url('/mod/uckkassembly/motion.php', ['id' => $cm->id]),
        'vote' => new moodle_url('/mod/uckkassembly/vote.php', ['id' => $cm->id]),
        'decision' => new moodle_url('/mod/uckkassembly/decision.php', ['id' => $cm->id]),
        'contest' => new moodle_url('/mod/uckkassembly/contest.php', ['id' => $cm->id]),
        'minutes' => new moodle_url('/mod/uckkassembly/minutes.php', ['id' => $cm->id]),
        'archive' => new moodle_url('/mod/uckkassembly/archive.php', ['id' => $cm->id]),
    ];
}

/**
 * Decode JSON metadata safely.
 *
 * @param mixed $value Raw metadata.
 * @return array<string,mixed>
 */
function uckkassembly_decode_metadata(mixed $value): array {
    if ($value === null || $value === '') {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    if ($value instanceof stdClass) {
        return (array)$value;
    }

    if (!is_string($value)) {
        return [];
    }

    $decoded = json_decode($value, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return [];
    }

    return $decoded;
}

/**
 * Encode metadata as JSON for storage.
 *
 * @param array<string,mixed> $metadata Metadata.
 * @return string|null
 */
function uckkassembly_encode_metadata(array $metadata): ?string {
    if ($metadata === []) {
        return null;
    }

    return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Return counts used by the assembly dashboard/view page.
 *
 * @param int $assemblyid Assembly id.
 * @return stdClass
 */
function uckkassembly_get_summary_counts(int $assemblyid): stdClass {
    global $DB;

    $counts = new stdClass();
    $counts->motions = 0;
    $counts->amendments = 0;
    $counts->objections = 0;
    $counts->votes = 0;
    $counts->decisions = 0;
    $counts->contests = 0;

    if ($assemblyid <= 0) {
        return $counts;
    }

    if ($DB->get_manager()->table_exists('uckkassembly_motion')) {
        $counts->motions = $DB->count_records('uckkassembly_motion', ['assemblyid' => $assemblyid]);
    }

    if ($DB->get_manager()->table_exists('uckkassembly_amend')) {
        $counts->amendments = $DB->count_records('uckkassembly_amend', ['assemblyid' => $assemblyid]);
    }

    if ($DB->get_manager()->table_exists('uckkassembly_object')) {
        $counts->objections = $DB->count_records('uckkassembly_object', ['assemblyid' => $assemblyid]);
    }

    if ($DB->get_manager()->table_exists('uckkassembly_vote')) {
        $counts->votes = $DB->count_records('uckkassembly_vote', ['assemblyid' => $assemblyid]);
    }

    if ($DB->get_manager()->table_exists('uckkassembly_decision')) {
        $counts->decisions = $DB->count_records('uckkassembly_decision', ['assemblyid' => $assemblyid]);
    }

    if ($DB->get_manager()->table_exists('uckkassembly_contest')) {
        $counts->contests = $DB->count_records('uckkassembly_contest', ['assemblyid' => $assemblyid]);
    }

    return $counts;
}

/**
 * Return visible motions for an assembly.
 *
 * @param int $assemblyid Assembly id.
 * @param context_module $context Module context.
 * @param int $limit Limit.
 * @return array<int,stdClass>
 */
function uckkassembly_get_visible_motions(int $assemblyid, context_module $context, int $limit = 20): array {
    global $DB;

    if ($assemblyid <= 0 || !$DB->get_manager()->table_exists('uckkassembly_motion')) {
        return [];
    }

    $params = ['assemblyid' => $assemblyid];
    $restricted = UCKKASSEMBLY_VISIBILITY_RESTRICTED_INTEGRITY;

    if (!uckkassembly_can_view_restricted($context)) {
        $where = 'assemblyid = :assemblyid AND visibility <> :restricted';
        $params['restricted'] = $restricted;
    } else {
        $where = 'assemblyid = :assemblyid';
    }

    return $DB->get_records_select(
        'uckkassembly_motion',
        $where,
        $params,
        'sortorder ASC, timecreated ASC',
        '*',
        0,
        $limit
    );
}

/**
 * Return visible decisions for an assembly.
 *
 * @param int $assemblyid Assembly id.
 * @param context_module $context Module context.
 * @param int $limit Limit.
 * @return array<int,stdClass>
 */
function uckkassembly_get_visible_decisions(int $assemblyid, context_module $context, int $limit = 20): array {
    global $DB;

    if ($assemblyid <= 0 || !$DB->get_manager()->table_exists('uckkassembly_decision')) {
        return [];
    }

    $params = ['assemblyid' => $assemblyid];
    $restricted = UCKKASSEMBLY_VISIBILITY_RESTRICTED_INTEGRITY;

    if (!uckkassembly_can_view_restricted($context)) {
        $where = 'assemblyid = :assemblyid AND visibility <> :restricted';
        $params['restricted'] = $restricted;
    } else {
        $where = 'assemblyid = :assemblyid';
    }

    return $DB->get_records_select(
        'uckkassembly_decision',
        $where,
        $params,
        'timecreated DESC',
        '*',
        0,
        $limit
    );
}

/**
 * Return vote reading summary for a motion.
 *
 * These are readings, not automatic truth or final legitimacy.
 *
 * @param int $motionid Motion id.
 * @return stdClass
 */
function uckkassembly_get_vote_reading(int $motionid): stdClass {
    global $DB;

    $reading = new stdClass();
    $reading->motionid = $motionid;
    $reading->for = 0;
    $reading->against = 0;
    $reading->abstain = 0;
    $reading->block = 0;
    $reading->total = 0;

    if ($motionid <= 0 || !$DB->get_manager()->table_exists('uckkassembly_vote')) {
        return $reading;
    }

    $votes = $DB->get_records('uckkassembly_vote', ['motionid' => $motionid]);

    foreach ($votes as $vote) {
        $value = clean_param((string)($vote->votevalue ?? $vote->value ?? ''), PARAM_ALPHANUMEXT);

        if ($value === UCKKASSEMBLY_VOTE_FOR) {
            $reading->for++;
        } else if ($value === UCKKASSEMBLY_VOTE_AGAINST) {
            $reading->against++;
        } else if ($value === UCKKASSEMBLY_VOTE_ABSTAIN) {
            $reading->abstain++;
        } else if ($value === UCKKASSEMBLY_VOTE_BLOCK) {
            $reading->block++;
        }

        $reading->total++;
    }

    return $reading;
}

/**
 * Prepare an assembly record for output.
 *
 * @param stdClass $assembly Assembly record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 * @return stdClass
 */
function uckkassembly_prepare_output_data(stdClass $assembly, stdClass $cm, context_module $context): stdClass {
    $data = clone $assembly;

    $state = uckkassembly_normalise_state((string)($assembly->state ?? $assembly->status ?? ''));
    $type = uckkassembly_normalise_type((string)($assembly->assemblytype ?? $assembly->type ?? ''));
    $visibility = uckkassembly_normalise_visibility((string)($assembly->visibility ?? UCKKASSEMBLY_VISIBILITY_COURSE));

    $data->cmid = (int)$cm->id;
    $data->contextid = (int)$context->id;
    $data->state = $state;
    $data->statelabel = uckkassembly_format_state($state);
    $data->stateclass = 'state-' . str_replace('_', '-', $state);
    $data->assemblytype = $type;
    $data->assemblytypelabel = uckkassembly_format_type($type);
    $data->visibility = $visibility;
    $data->visibilitylabel = uckkassembly_format_visibility($visibility);
    $data->summarycounts = uckkassembly_get_summary_counts((int)$assembly->id);

    $urls = uckkassembly_get_urls($cm);
    foreach ($urls as $key => $url) {
        $data->{$key . 'url'} = $url->out(false);
    }

    $data->canproposemotion = uckkassembly_can_propose_motion($assembly, $context);
    $data->canamendmotion = uckkassembly_can_amend_motion($assembly, $context);
    $data->canvote = uckkassembly_can_vote($assembly, $context);
    $data->canpublishdecision = uckkassembly_can_publish_decision($assembly, $context);
    $data->cancontestdecision = uckkassembly_can_contest_decision($assembly, $context);
    $data->canarchive = uckkassembly_can_archive($assembly, $context);
    $data->canviewrestricted = uckkassembly_can_view_restricted($context);

    return $data;
}

/**
 * Create a standard page title for an assembly.
 *
 * @param stdClass $assembly Assembly record.
 * @return string
 */
function uckkassembly_format_page_title(stdClass $assembly): string {
    return format_string((string)($assembly->name ?? get_string('pluginname', 'uckkassembly')));
}

/**
 * Return whether a visibility value requires restricted access.
 *
 * @param string $visibility Visibility.
 * @return bool
 */
function uckkassembly_visibility_is_restricted(string $visibility): bool {
    return uckkassembly_normalise_visibility($visibility) === UCKKASSEMBLY_VISIBILITY_RESTRICTED_INTEGRITY;
}

/**
 * Clean a status-like key for CSS class usage.
 *
 * @param string $value Raw value.
 * @return string
 */
function uckkassembly_css_key(string $value): string {
    $value = clean_param($value, PARAM_ALPHANUMEXT);

    return str_replace('_', '-', $value);
}