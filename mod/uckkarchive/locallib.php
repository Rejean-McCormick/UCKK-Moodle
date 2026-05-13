<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Local helper library for mod_uckkarchive.
 *
 * This file contains stable procedural helpers used by Moodle callbacks,
 * controllers, forms, external functions, backup/restore code, and tests.
 *
 * It must not become the full business layer. Complex archive validation,
 * export packaging, provenance calculation, privacy export, integrity linking,
 * and long-running tasks belong in autoloaded classes under:
 *
 * - mod_uckkarchive\classes\local
 * - mod_uckkarchive\classes\form
 * - mod_uckkarchive\classes\output
 * - mod_uckkarchive\classes\task
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Component name.
 */
define('UCKKARCHIVE_COMPONENT', 'mod_uckkarchive');

/**
 * Main activity table.
 */
define('UCKKARCHIVE_TABLE', 'uckkarchive');

/**
 * Archive item table.
 */
define('UCKKARCHIVE_ITEM_TABLE', 'uckkarchive_item');

/**
 * Kristal table.
 */
define('UCKKARCHIVE_KRISTAL_TABLE', 'uckkarchive_kristal');

/**
 * Proof table.
 */
define('UCKKARCHIVE_PROOF_TABLE', 'uckkarchive_proof');

/**
 * Provenance table.
 */
define('UCKKARCHIVE_PROVENANCE_TABLE', 'uckkarchive_prov');

/**
 * Revision table.
 */
define('UCKKARCHIVE_REVISION_TABLE', 'uckkarchive_rev');

/**
 * Export package table.
 */
define('UCKKARCHIVE_EXPORT_TABLE', 'uckkarchive_export');

/**
 * Archive item status: draft.
 */
define('UCKKARCHIVE_STATUS_DRAFT', 'draft');

/**
 * Archive item status: submitted.
 */
define('UCKKARCHIVE_STATUS_SUBMITTED', 'submitted');

/**
 * Archive item status: under review.
 */
define('UCKKARCHIVE_STATUS_UNDER_REVIEW', 'under_review');

/**
 * Archive item status: validated.
 */
define('UCKKARCHIVE_STATUS_VALIDATED', 'validated');

/**
 * Archive item status: published.
 */
define('UCKKARCHIVE_STATUS_PUBLISHED', 'published');

/**
 * Archive item status: restricted.
 */
define('UCKKARCHIVE_STATUS_RESTRICTED', 'restricted');

/**
 * Archive item status: contested.
 */
define('UCKKARCHIVE_STATUS_CONTESTED', 'contested');

/**
 * Archive item status: invalidated.
 */
define('UCKKARCHIVE_STATUS_INVALIDATED', 'invalidated');

/**
 * Archive item status: superseded.
 */
define('UCKKARCHIVE_STATUS_SUPERSEDED', 'superseded');

/**
 * Archive item status: archived.
 */
define('UCKKARCHIVE_STATUS_ARCHIVED', 'archived');

/**
 * Validation state: unverified.
 */
define('UCKKARCHIVE_VALIDATION_UNVERIFIED', 'unverified');

/**
 * Validation state: human reviewed.
 */
define('UCKKARCHIVE_VALIDATION_HUMAN_REVIEWED', 'human_reviewed');

/**
 * Validation state: verified.
 */
define('UCKKARCHIVE_VALIDATION_VERIFIED', 'verified');

/**
 * Validation state: contested.
 */
define('UCKKARCHIVE_VALIDATION_CONTESTED', 'contested');

/**
 * Validation state: invalidated.
 */
define('UCKKARCHIVE_VALIDATION_INVALIDATED', 'invalidated');

/**
 * Validation state: archived.
 */
define('UCKKARCHIVE_VALIDATION_ARCHIVED', 'archived');

/**
 * Archive visibility: private.
 */
define('UCKKARCHIVE_VISIBILITY_PRIVATE', 'private');

/**
 * Archive visibility: course.
 */
define('UCKKARCHIVE_VISIBILITY_COURSE', 'course');

/**
 * Archive visibility: cohort.
 */
define('UCKKARCHIVE_VISIBILITY_COHORT', 'cohort');

/**
 * Archive visibility: program.
 */
define('UCKKARCHIVE_VISIBILITY_PROGRAM', 'program');

/**
 * Archive visibility: institution.
 */
define('UCKKARCHIVE_VISIBILITY_INSTITUTION', 'institution');

/**
 * Archive visibility: public.
 */
define('UCKKARCHIVE_VISIBILITY_PUBLIC', 'public');

/**
 * Archive visibility: restricted integrity.
 */
define('UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY', 'restricted_integrity');

/**
 * Archive item type: proof.
 */
define('UCKKARCHIVE_TYPE_PROOF', 'proof');

/**
 * Archive item type: decision.
 */
define('UCKKARCHIVE_TYPE_DECISION', 'decision');

/**
 * Archive item type: minutes.
 */
define('UCKKARCHIVE_TYPE_MINUTES', 'minutes');

/**
 * Archive item type: challenge result.
 */
define('UCKKARCHIVE_TYPE_CHALLENGE_RESULT', 'challenge_result');

/**
 * Archive item type: course work.
 */
define('UCKKARCHIVE_TYPE_COURSE_WORK', 'course_work');

/**
 * Archive item type: portfolio item.
 */
define('UCKKARCHIVE_TYPE_PORTFOLIO_ITEM', 'portfolio_item');

/**
 * Archive item type: Kristal.
 */
define('UCKKARCHIVE_TYPE_KRISTAL', 'kristal');

/**
 * Archive item type: integrity summary.
 */
define('UCKKARCHIVE_TYPE_INTEGRITY_SUMMARY', 'integrity_summary');

/**
 * Archive item type: public summary.
 */
define('UCKKARCHIVE_TYPE_PUBLIC_SUMMARY', 'public_summary');

/**
 * Provenance source: human.
 */
define('UCKKARCHIVE_PROVENANCE_HUMAN', 'human');

/**
 * Provenance source: AI-assisted.
 */
define('UCKKARCHIVE_PROVENANCE_AI_ASSISTED', 'ai_assisted');

/**
 * Provenance source: imported.
 */
define('UCKKARCHIVE_PROVENANCE_IMPORTED', 'imported');

/**
 * Provenance source: system.
 */
define('UCKKARCHIVE_PROVENANCE_SYSTEM', 'system');

/**
 * Provenance source: archive.
 */
define('UCKKARCHIVE_PROVENANCE_ARCHIVE', 'archive');

/**
 * Provenance source: assembly.
 */
define('UCKKARCHIVE_PROVENANCE_ASSEMBLY', 'assembly');

/**
 * Provenance source: challenge.
 */
define('UCKKARCHIVE_PROVENANCE_CHALLENGE', 'challenge');

/**
 * Provenance source: integrity.
 */
define('UCKKARCHIVE_PROVENANCE_INTEGRITY', 'integrity');

/**
 * Return canonical archive item statuses.
 *
 * @return string[]
 */
function uckkarchive_get_statuses(): array {
    return [
        UCKKARCHIVE_STATUS_DRAFT,
        UCKKARCHIVE_STATUS_SUBMITTED,
        UCKKARCHIVE_STATUS_UNDER_REVIEW,
        UCKKARCHIVE_STATUS_VALIDATED,
        UCKKARCHIVE_STATUS_PUBLISHED,
        UCKKARCHIVE_STATUS_RESTRICTED,
        UCKKARCHIVE_STATUS_CONTESTED,
        UCKKARCHIVE_STATUS_INVALIDATED,
        UCKKARCHIVE_STATUS_SUPERSEDED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ];
}

/**
 * Return archive status labels.
 *
 * @return array<string, string>
 */
function uckkarchive_get_status_options(): array {
    $options = [];

    foreach (uckkarchive_get_statuses() as $status) {
        $options[$status] = uckkarchive_get_string_if_exists(
            'status:' . str_replace('_', '', $status),
            ucfirst(str_replace('_', ' ', $status))
        );
    }

    return $options;
}

/**
 * Return validation states.
 *
 * @return string[]
 */
function uckkarchive_get_validation_states(): array {
    return [
        UCKKARCHIVE_VALIDATION_UNVERIFIED,
        UCKKARCHIVE_VALIDATION_HUMAN_REVIEWED,
        UCKKARCHIVE_VALIDATION_VERIFIED,
        UCKKARCHIVE_VALIDATION_CONTESTED,
        UCKKARCHIVE_VALIDATION_INVALIDATED,
        UCKKARCHIVE_VALIDATION_ARCHIVED,
    ];
}

/**
 * Return validation state labels.
 *
 * @return array<string, string>
 */
function uckkarchive_get_validation_state_options(): array {
    $options = [];

    foreach (uckkarchive_get_validation_states() as $state) {
        $options[$state] = uckkarchive_get_string_if_exists(
            'validation:' . str_replace('_', '', $state),
            ucfirst(str_replace('_', ' ', $state))
        );
    }

    return $options;
}

/**
 * Return archive visibilities.
 *
 * @return string[]
 */
function uckkarchive_get_visibilities(): array {
    return [
        UCKKARCHIVE_VISIBILITY_PRIVATE,
        UCKKARCHIVE_VISIBILITY_COURSE,
        UCKKARCHIVE_VISIBILITY_COHORT,
        UCKKARCHIVE_VISIBILITY_PROGRAM,
        UCKKARCHIVE_VISIBILITY_INSTITUTION,
        UCKKARCHIVE_VISIBILITY_PUBLIC,
        UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY,
    ];
}

/**
 * Return archive visibility labels.
 *
 * @return array<string, string>
 */
function uckkarchive_get_visibility_options(): array {
    $options = [];

    foreach (uckkarchive_get_visibilities() as $visibility) {
        $options[$visibility] = uckkarchive_get_string_if_exists(
            'visibility:' . str_replace('_', '', $visibility),
            ucfirst(str_replace('_', ' ', $visibility))
        );
    }

    return $options;
}

/**
 * Return archive item types.
 *
 * @return string[]
 */
function uckkarchive_get_item_types(): array {
    return [
        UCKKARCHIVE_TYPE_PROOF,
        UCKKARCHIVE_TYPE_DECISION,
        UCKKARCHIVE_TYPE_MINUTES,
        UCKKARCHIVE_TYPE_CHALLENGE_RESULT,
        UCKKARCHIVE_TYPE_COURSE_WORK,
        UCKKARCHIVE_TYPE_PORTFOLIO_ITEM,
        UCKKARCHIVE_TYPE_KRISTAL,
        UCKKARCHIVE_TYPE_INTEGRITY_SUMMARY,
        UCKKARCHIVE_TYPE_PUBLIC_SUMMARY,
    ];
}

/**
 * Return archive item type labels.
 *
 * @return array<string, string>
 */
function uckkarchive_get_item_type_options(): array {
    $options = [];

    foreach (uckkarchive_get_item_types() as $type) {
        $options[$type] = uckkarchive_get_string_if_exists(
            'itemtype:' . str_replace('_', '', $type),
            ucfirst(str_replace('_', ' ', $type))
        );
    }

    return $options;
}

/**
 * Return provenance sources.
 *
 * @return string[]
 */
function uckkarchive_get_provenance_sources(): array {
    return [
        UCKKARCHIVE_PROVENANCE_HUMAN,
        UCKKARCHIVE_PROVENANCE_AI_ASSISTED,
        UCKKARCHIVE_PROVENANCE_IMPORTED,
        UCKKARCHIVE_PROVENANCE_SYSTEM,
        UCKKARCHIVE_PROVENANCE_ARCHIVE,
        UCKKARCHIVE_PROVENANCE_ASSEMBLY,
        UCKKARCHIVE_PROVENANCE_CHALLENGE,
        UCKKARCHIVE_PROVENANCE_INTEGRITY,
    ];
}

/**
 * Return provenance source labels.
 *
 * @return array<string, string>
 */
function uckkarchive_get_provenance_options(): array {
    $options = [];

    foreach (uckkarchive_get_provenance_sources() as $source) {
        $options[$source] = uckkarchive_get_string_if_exists(
            'provenance:' . str_replace('_', '', $source),
            ucfirst(str_replace('_', ' ', $source))
        );
    }

    return $options;
}

/**
 * Return true if the value is a valid archive status.
 *
 * @param string $status Status.
 * @return bool
 */
function uckkarchive_is_valid_status(string $status): bool {
    return in_array($status, uckkarchive_get_statuses(), true);
}

/**
 * Return true if the value is a valid validation state.
 *
 * @param string $state Validation state.
 * @return bool
 */
function uckkarchive_is_valid_validation_state(string $state): bool {
    return in_array($state, uckkarchive_get_validation_states(), true);
}

/**
 * Return true if the value is a valid archive visibility.
 *
 * @param string $visibility Visibility.
 * @return bool
 */
function uckkarchive_is_valid_visibility(string $visibility): bool {
    return in_array($visibility, uckkarchive_get_visibilities(), true);
}

/**
 * Return true if the value is a valid archive item type.
 *
 * @param string $type Item type.
 * @return bool
 */
function uckkarchive_is_valid_item_type(string $type): bool {
    return in_array($type, uckkarchive_get_item_types(), true);
}

/**
 * Return true if the value is a valid provenance source.
 *
 * @param string $source Provenance source.
 * @return bool
 */
function uckkarchive_is_valid_provenance_source(string $source): bool {
    return in_array($source, uckkarchive_get_provenance_sources(), true);
}

/**
 * Normalise an archive status.
 *
 * @param string|null $status Raw status.
 * @return string
 */
function uckkarchive_normalise_status(?string $status): string {
    $status = clean_param((string)$status, PARAM_ALPHANUMEXT);

    return uckkarchive_is_valid_status($status) ? $status : UCKKARCHIVE_STATUS_DRAFT;
}

/**
 * Normalise a validation state.
 *
 * @param string|null $state Raw validation state.
 * @return string
 */
function uckkarchive_normalise_validation_state(?string $state): string {
    $state = clean_param((string)$state, PARAM_ALPHANUMEXT);

    return uckkarchive_is_valid_validation_state($state) ? $state : UCKKARCHIVE_VALIDATION_UNVERIFIED;
}

/**
 * Normalise visibility.
 *
 * @param string|null $visibility Raw visibility.
 * @return string
 */
function uckkarchive_normalise_visibility(?string $visibility): string {
    $visibility = clean_param((string)$visibility, PARAM_ALPHANUMEXT);

    return uckkarchive_is_valid_visibility($visibility) ? $visibility : UCKKARCHIVE_VISIBILITY_COURSE;
}

/**
 * Normalise archive item type.
 *
 * @param string|null $type Raw item type.
 * @return string
 */
function uckkarchive_normalise_item_type(?string $type): string {
    $type = clean_param((string)$type, PARAM_ALPHANUMEXT);

    return uckkarchive_is_valid_item_type($type) ? $type : UCKKARCHIVE_TYPE_PROOF;
}

/**
 * Normalise provenance source.
 *
 * @param string|null $source Raw provenance source.
 * @return string
 */
function uckkarchive_normalise_provenance_source(?string $source): string {
    $source = clean_param((string)$source, PARAM_ALPHANUMEXT);

    return uckkarchive_is_valid_provenance_source($source) ? $source : UCKKARCHIVE_PROVENANCE_HUMAN;
}

/**
 * Return a translated string if it exists, otherwise a fallback.
 *
 * @param string $key String key.
 * @param string $fallback Fallback.
 * @return string
 */
function uckkarchive_get_string_if_exists(string $key, string $fallback): string {
    if (get_string_manager()->string_exists($key, 'uckkarchive')) {
        return get_string($key, 'uckkarchive');
    }

    return $fallback;
}

/**
 * Build a module URL.
 *
 * @param string $script Script filename without leading slash.
 * @param array<string, mixed> $params URL params.
 * @return moodle_url
 */
function uckkarchive_url(string $script = 'view.php', array $params = []): moodle_url {
    $script = clean_param($script, PARAM_FILE);

    if ($script === '') {
        $script = 'view.php';
    }

    return new moodle_url('/mod/uckkarchive/' . $script, $params);
}

/**
 * Build the activity view URL.
 *
 * @param int $cmid Course module id.
 * @return moodle_url
 */
function uckkarchive_view_url(int $cmid): moodle_url {
    return uckkarchive_url('view.php', ['id' => $cmid]);
}

/**
 * Build the activity index URL.
 *
 * @param int $courseid Course id.
 * @return moodle_url
 */
function uckkarchive_index_url(int $courseid): moodle_url {
    return uckkarchive_url('index.php', ['id' => $courseid]);
}

/**
 * Build archive item URL.
 *
 * @param int $cmid Course module id.
 * @param int $itemid Item id.
 * @return moodle_url
 */
function uckkarchive_item_url(int $cmid, int $itemid): moodle_url {
    return uckkarchive_url('item.php', [
        'id' => $cmid,
        'itemid' => $itemid,
    ]);
}

/**
 * Build add item URL.
 *
 * @param int $cmid Course module id.
 * @param array<string, mixed> $params Extra params.
 * @return moodle_url
 */
function uckkarchive_add_url(int $cmid, array $params = []): moodle_url {
    return uckkarchive_url('add.php', ['id' => $cmid] + $params);
}

/**
 * Build validation URL.
 *
 * @param int $cmid Course module id.
 * @param int $itemid Item id.
 * @return moodle_url
 */
function uckkarchive_validate_url(int $cmid, int $itemid): moodle_url {
    return uckkarchive_url('validate.php', [
        'id' => $cmid,
        'itemid' => $itemid,
    ]);
}

/**
 * Build export URL.
 *
 * @param int $cmid Course module id.
 * @param array<string, mixed> $params Extra params.
 * @return moodle_url
 */
function uckkarchive_export_url(int $cmid, array $params = []): moodle_url {
    return uckkarchive_url('export.php', ['id' => $cmid] + $params);
}

/**
 * Resolve course, course module, archive instance, and module context.
 *
 * @param int $cmid Course module id.
 * @param int $archiveid Archive instance id.
 * @return array{0: stdClass, 1: stdClass, 2: stdClass, 3: context_module}
 */
function uckkarchive_get_page_records(int $cmid = 0, int $archiveid = 0): array {
    global $DB;

    if ($cmid > 0) {
        $cm = get_coursemodule_from_id('uckkarchive', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $archive = $DB->get_record(UCKKARCHIVE_TABLE, ['id' => $cm->instance], '*', MUST_EXIST);
    } else if ($archiveid > 0) {
        $archive = $DB->get_record(UCKKARCHIVE_TABLE, ['id' => $archiveid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $archive->course], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('uckkarchive', $archive->id, $course->id, false, MUST_EXIST);
    } else {
        throw new moodle_exception('missingparam', 'error', '', 'id');
    }

    $context = context_module::instance($cm->id);

    return [$course, $cm, $archive, $context];
}

/**
 * Require login and view capability for an archive activity page.
 *
 * @param int $cmid Course module id.
 * @param int $archiveid Archive instance id.
 * @return array{0: stdClass, 1: stdClass, 2: stdClass, 3: context_module}
 */
function uckkarchive_require_page(int $cmid = 0, int $archiveid = 0): array {
    [$course, $cm, $archive, $context] = uckkarchive_get_page_records($cmid, $archiveid);

    require_login($course, false, $cm);
    require_capability('mod/uckkarchive:view', $context);

    return [$course, $cm, $archive, $context];
}

/**
 * Configure PAGE for an archive page.
 *
 * @param moodle_page $page Moodle page.
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param context_module $context Context.
 * @param moodle_url $url Page URL.
 * @param string $title Page title.
 * @return void
 */
function uckkarchive_setup_page(
    moodle_page $page,
    stdClass $course,
    stdClass $cm,
    context_module $context,
    moodle_url $url,
    string $title
): void {
    $page->set_url($url);
    $page->set_course($course);
    $page->set_cm($cm);
    $page->set_context($context);
    $page->set_title($title);
    $page->set_heading(format_string($course->fullname));
}

/**
 * Mark the archive module as viewed.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @return void
 */
function uckkarchive_mark_viewed(stdClass $course, stdClass $cm): void {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Return whether the user can add archive items.
 *
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_add_item(context_module $context, ?stdClass $user = null): bool {
    return has_capability('mod/uckkarchive:additem', $context, $user);
}

/**
 * Return whether the user can validate archive items.
 *
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_validate_item(context_module $context, ?stdClass $user = null): bool {
    return has_capability('mod/uckkarchive:validateitem', $context, $user);
}

/**
 * Return whether the user can revise archive items.
 *
 * Canonical UCKK fixed variable list uses reviseitem. If legacy docs mention
 * versionitem, keep the generated plugin aligned on reviseitem.
 *
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_revise_item(context_module $context, ?stdClass $user = null): bool {
    return has_capability('mod/uckkarchive:reviseitem', $context, $user);
}

/**
 * Return whether the user can see restricted archive data.
 *
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_view_restricted(context_module $context, ?stdClass $user = null): bool {
    return has_capability('mod/uckkarchive:viewrestricted', $context, $user);
}

/**
 * Return whether the user can export archive data.
 *
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_export(context_module $context, ?stdClass $user = null): bool {
    return has_capability('mod/uckkarchive:export', $context, $user);
}

/**
 * Return whether a user can view an archive item.
 *
 * This is a lightweight guard for controllers and output preparation. The
 * service layer remains responsible for full visibility and integrity policy.
 *
 * @param stdClass $item Archive item record.
 * @param context_module $context Module context.
 * @param stdClass|null $user User.
 * @return bool
 */
function uckkarchive_can_view_item(stdClass $item, context_module $context, ?stdClass $user = null): bool {
    global $USER;

    $user ??= $USER;

    if (!has_capability('mod/uckkarchive:view', $context, $user)) {
        return false;
    }

    $visibility = uckkarchive_normalise_visibility($item->visibility ?? null);
    $status = uckkarchive_normalise_status($item->status ?? null);

    if (in_array($status, [
        UCKKARCHIVE_STATUS_INVALIDATED,
        UCKKARCHIVE_STATUS_SUPERSEDED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ], true) && !uckkarchive_can_view_restricted($context, $user)) {
        return false;
    }

    if ($visibility === UCKKARCHIVE_VISIBILITY_PRIVATE) {
        return !empty($item->userid) && (int)$item->userid === (int)$user->id;
    }

    if ($visibility === UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY) {
        return uckkarchive_can_view_restricted($context, $user);
    }

    return true;
}

/**
 * Return common export context for permission-filtered item cards.
 *
 * @param stdClass $item Archive item record.
 * @param stdClass $cm Course module.
 * @param context_module $context Context.
 * @return stdClass
 */
function uckkarchive_prepare_item_export(stdClass $item, stdClass $cm, context_module $context): stdClass {
    $data = new stdClass();

    $data->id = (int)($item->id ?? 0);
    $data->cmid = (int)$cm->id;
    $data->contextid = (int)$context->id;
    $data->title = format_string((string)($item->title ?? $item->name ?? ''));
    $data->itemtype = uckkarchive_normalise_item_type($item->itemtype ?? $item->type ?? null);
    $data->itemtypelabel = uckkarchive_get_item_type_options()[$data->itemtype] ?? $data->itemtype;
    $data->status = uckkarchive_normalise_status($item->status ?? null);
    $data->statuslabel = uckkarchive_get_status_options()[$data->status] ?? $data->status;
    $data->statusclass = 'status-' . str_replace('_', '-', $data->status);
    $data->visibility = uckkarchive_normalise_visibility($item->visibility ?? null);
    $data->visibilitylabel = uckkarchive_get_visibility_options()[$data->visibility] ?? $data->visibility;
    $data->validationstate = uckkarchive_normalise_validation_state($item->validationstate ?? null);
    $data->validationlabel = uckkarchive_get_validation_state_options()[$data->validationstate] ?? $data->validationstate;
    $data->versionno = max(1, (int)($item->versionno ?? 1));
    $data->summary = format_string((string)($item->summary ?? $item->publicsummary ?? ''));
    $data->hassummary = trim($data->summary) !== '';
    $data->url = uckkarchive_item_url((int)$cm->id, $data->id)->out(false);
    $data->hasurl = $data->id > 0;
    $data->timecreated = (int)($item->timecreated ?? 0);
    $data->timecreatedlabel = $data->timecreated > 0 ? userdate($data->timecreated) : '';
    $data->timemodified = (int)($item->timemodified ?? 0);
    $data->timemodifiedlabel = $data->timemodified > 0 ? userdate($data->timemodified) : '';
    $data->metadata = uckkarchive_decode_metadata($item->metadata ?? null);

    return $data;
}

/**
 * Load one archive item.
 *
 * @param int $itemid Archive item id.
 * @param int|null $archiveid Optional archive instance id.
 * @return stdClass
 */
function uckkarchive_get_item(int $itemid, ?int $archiveid = null): stdClass {
    global $DB;

    $conditions = ['id' => $itemid];

    if ($archiveid !== null) {
        $conditions['archiveid'] = $archiveid;
    }

    return $DB->get_record(UCKKARCHIVE_ITEM_TABLE, $conditions, '*', MUST_EXIST);
}

/**
 * Load visible archive items for a module instance.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @param array<string, mixed> $filters Filters.
 * @param int $limit Limit.
 * @param int $offset Offset.
 * @return stdClass[]
 */
function uckkarchive_get_visible_items(
    stdClass $archive,
    context_module $context,
    ?stdClass $user = null,
    array $filters = [],
    int $limit = 0,
    int $offset = 0
): array {
    global $DB, $USER;

    $user ??= $USER;

    $conditions = ['archiveid' => (int)$archive->id];
    $params = [];

    if (!empty($filters['status'])) {
        $conditions['status'] = uckkarchive_normalise_status((string)$filters['status']);
    }

    if (!empty($filters['itemtype'])) {
        $conditions['itemtype'] = uckkarchive_normalise_item_type((string)$filters['itemtype']);
    }

    if (!empty($filters['validationstate'])) {
        $conditions['validationstate'] = uckkarchive_normalise_validation_state((string)$filters['validationstate']);
    }

    [$where, $params] = $DB->get_in_or_equal(array_values($conditions), SQL_PARAMS_NAMED, 'p');

    // get_in_or_equal is not useful for associative equality, so use simple SQL.
    $sqlwhere = [];
    $params = [];

    foreach ($conditions as $field => $value) {
        $param = 'p_' . $field;
        $sqlwhere[] = $field . ' = :' . $param;
        $params[$param] = $value;
    }

    if (!uckkarchive_can_view_restricted($context, $user)) {
        $sqlwhere[] = 'visibility <> :restrictedvisibility';
        $params['restrictedvisibility'] = UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY;
        $sqlwhere[] = 'status <> :invalidatedstatus';
        $params['invalidatedstatus'] = UCKKARCHIVE_STATUS_INVALIDATED;
    }

    $sql = 'SELECT *
              FROM {' . UCKKARCHIVE_ITEM_TABLE . '}
             WHERE ' . implode(' AND ', $sqlwhere) . '
          ORDER BY timemodified DESC, id DESC';

    return $DB->get_records_sql($sql, $params, $offset, $limit);
}

/**
 * Count visible archive items for a module instance.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @param array<string, mixed> $filters Filters.
 * @return int
 */
function uckkarchive_count_visible_items(
    stdClass $archive,
    context_module $context,
    ?stdClass $user = null,
    array $filters = []
): int {
    return count(uckkarchive_get_visible_items($archive, $context, $user, $filters, 0, 0));
}

/**
 * Decode JSON metadata safely.
 *
 * @param mixed $metadata Raw metadata.
 * @return array<string, mixed>
 */
function uckkarchive_decode_metadata(mixed $metadata): array {
    if ($metadata === null || $metadata === '') {
        return [];
    }

    if (is_array($metadata)) {
        return $metadata;
    }

    if ($metadata instanceof stdClass) {
        return (array)$metadata;
    }

    if (!is_string($metadata)) {
        return [];
    }

    $decoded = json_decode($metadata, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return [];
    }

    return $decoded;
}

/**
 * Encode metadata as JSON or null.
 *
 * @param array<string, mixed>|stdClass|null $metadata Metadata.
 * @return string|null
 */
function uckkarchive_encode_metadata(array|stdClass|null $metadata): ?string {
    if ($metadata instanceof stdClass) {
        $metadata = (array)$metadata;
    }

    if (empty($metadata)) {
        return null;
    }

    return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Compute a deterministic provenance hash.
 *
 * @param array<string, mixed>|stdClass|string $payload Hash payload.
 * @return string
 */
function uckkarchive_compute_provenance_hash(array|stdClass|string $payload): string {
    if ($payload instanceof stdClass) {
        $payload = (array)$payload;
    }

    if (is_array($payload)) {
        ksort($payload);
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return hash('sha256', (string)$payload);
}

/**
 * Create a revision record from previous and new item state.
 *
 * This helper writes only the revision record. The caller is responsible for
 * updating the archive item itself inside the same delegated transaction when
 * atomicity is required.
 *
 * @param stdClass $olditem Previous item state.
 * @param stdClass $newitem New item state.
 * @param string $reason Change reason.
 * @param int $userid Acting user id.
 * @param int|null $integritycaseid Linked integrity case id.
 * @return int Revision id.
 */
function uckkarchive_create_revision(
    stdClass $olditem,
    stdClass $newitem,
    string $reason,
    int $userid,
    ?int $integritycaseid = null
): int {
    global $DB;

    $now = time();

    $record = new stdClass();
    $record->archiveid = (int)($newitem->archiveid ?? $olditem->archiveid ?? 0);
    $record->itemid = (int)($newitem->id ?? $olditem->id ?? 0);
    $record->courseid = (int)($newitem->courseid ?? $olditem->courseid ?? 0);
    $record->cmid = (int)($newitem->cmid ?? $olditem->cmid ?? 0);
    $record->contextid = (int)($newitem->contextid ?? $olditem->contextid ?? 0);
    $record->userid = (int)($newitem->userid ?? $olditem->userid ?? 0);
    $record->createdby = $userid;
    $record->modifiedby = $userid;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->status = UCKKARCHIVE_STATUS_ACTIVE ?? 'active';
    $record->visibility = (string)($newitem->visibility ?? $olditem->visibility ?? UCKKARCHIVE_VISIBILITY_COURSE);
    $record->versionno = max(1, (int)($newitem->versionno ?? 1));
    $record->provenancehash = uckkarchive_compute_provenance_hash([
        'old' => $olditem,
        'new' => $newitem,
        'reason' => $reason,
        'userid' => $userid,
        'timecreated' => $now,
    ]);

    $record->previousstate = uckkarchive_encode_metadata((array)$olditem);
    $record->newstate = uckkarchive_encode_metadata((array)$newitem);
    $record->changereason = $reason;
    $record->integritycaseid = $integritycaseid;
    $record->metadata = uckkarchive_encode_metadata([
        'origin' => 'mod_uckkarchive',
        'action' => 'revision',
    ]);

    return (int)$DB->insert_record(UCKKARCHIVE_REVISION_TABLE, $record);
}

/**
 * Insert a provenance record for an archive item.
 *
 * @param int $archiveid Archive instance id.
 * @param int $itemid Archive item id.
 * @param int $contextid Context id.
 * @param int $userid User id.
 * @param string $origincomponent Origin component.
 * @param int $originobjectid Origin object id.
 * @param string $source Description of source.
 * @param string $provenance Provenance source.
 * @param array<string, mixed> $metadata Additional metadata.
 * @return int Provenance record id.
 */
function uckkarchive_create_provenance_record(
    int $archiveid,
    int $itemid,
    int $contextid,
    int $userid,
    string $origincomponent,
    int $originobjectid,
    string $source,
    string $provenance = UCKKARCHIVE_PROVENANCE_HUMAN,
    array $metadata = []
): int {
    global $DB;

    $now = time();

    $record = new stdClass();
    $record->archiveid = $archiveid;
    $record->itemid = $itemid;
    $record->courseid = (int)($metadata['courseid'] ?? 0);
    $record->cmid = (int)($metadata['cmid'] ?? 0);
    $record->contextid = $contextid;
    $record->userid = $userid;
    $record->createdby = $userid;
    $record->modifiedby = $userid;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->status = UCKKARCHIVE_STATUS_VALIDATED;
    $record->visibility = uckkarchive_normalise_visibility($metadata['visibility'] ?? UCKKARCHIVE_VISIBILITY_COURSE);
    $record->versionno = 1;

    $record->origincomponent = clean_param($origincomponent, PARAM_COMPONENT);
    $record->originobjectid = $originobjectid;
    $record->source = $source;
    $record->provenance = uckkarchive_normalise_provenance_source($provenance);
    $record->provenancehash = uckkarchive_compute_provenance_hash([
        'archiveid' => $archiveid,
        'itemid' => $itemid,
        'origincomponent' => $origincomponent,
        'originobjectid' => $originobjectid,
        'source' => $source,
        'userid' => $userid,
    ]);
    $record->metadata = uckkarchive_encode_metadata($metadata);

    return (int)$DB->insert_record(UCKKARCHIVE_PROVENANCE_TABLE, $record);
}

/**
 * Return whether an item status is terminal.
 *
 * @param string $status Status.
 * @return bool
 */
function uckkarchive_is_terminal_status(string $status): bool {
    $status = uckkarchive_normalise_status($status);

    return in_array($status, [
        UCKKARCHIVE_STATUS_PUBLISHED,
        UCKKARCHIVE_STATUS_INVALIDATED,
        UCKKARCHIVE_STATUS_SUPERSEDED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ], true);
}

/**
 * Return whether an item can be submitted for review.
 *
 * @param stdClass $item Item.
 * @return bool
 */
function uckkarchive_item_can_submit(stdClass $item): bool {
    $status = uckkarchive_normalise_status($item->status ?? null);

    return in_array($status, [
        UCKKARCHIVE_STATUS_DRAFT,
        UCKKARCHIVE_STATUS_CONTESTED,
    ], true);
}

/**
 * Return whether an item can be validated.
 *
 * @param stdClass $item Item.
 * @return bool
 */
function uckkarchive_item_can_validate(stdClass $item): bool {
    $status = uckkarchive_normalise_status($item->status ?? null);

    return in_array($status, [
        UCKKARCHIVE_STATUS_SUBMITTED,
        UCKKARCHIVE_STATUS_UNDER_REVIEW,
        UCKKARCHIVE_STATUS_CONTESTED,
    ], true);
}

/**
 * Return whether an item can be revised.
 *
 * @param stdClass $item Item.
 * @return bool
 */
function uckkarchive_item_can_revise(stdClass $item): bool {
    $status = uckkarchive_normalise_status($item->status ?? null);

    return !in_array($status, [
        UCKKARCHIVE_STATUS_INVALIDATED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ], true);
}

/**
 * Return whether an item can be exported.
 *
 * @param stdClass $item Item.
 * @return bool
 */
function uckkarchive_item_can_export(stdClass $item): bool {
    $status = uckkarchive_normalise_status($item->status ?? null);

    return in_array($status, [
        UCKKARCHIVE_STATUS_VALIDATED,
        UCKKARCHIVE_STATUS_PUBLISHED,
        UCKKARCHIVE_STATUS_RESTRICTED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ], true);
}

/**
 * Build a compact archive summary for dashboards/reports.
 *
 * @param stdClass $archive Archive instance.
 * @param context_module $context Context.
 * @param stdClass|null $user User.
 * @return stdClass
 */
function uckkarchive_get_summary(stdClass $archive, context_module $context, ?stdClass $user = null): stdClass {
    $summary = new stdClass();

    $summary->archiveid = (int)$archive->id;
    $summary->contextid = (int)$context->id;
    $summary->totalitems = uckkarchive_count_visible_items($archive, $context, $user);
    $summary->validateditems = uckkarchive_count_visible_items($archive, $context, $user, [
        'status' => UCKKARCHIVE_STATUS_VALIDATED,
    ]);
    $summary->publisheditems = uckkarchive_count_visible_items($archive, $context, $user, [
        'status' => UCKKARCHIVE_STATUS_PUBLISHED,
    ]);
    $summary->contesteditems = uckkarchive_count_visible_items($archive, $context, $user, [
        'status' => UCKKARCHIVE_STATUS_CONTESTED,
    ]);
    $summary->restrictedvisible = uckkarchive_can_view_restricted($context, $user);

    return $summary;
}