<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin Atlas → Moodle sync controller for local_uckk.
 *
 * This controller is deliberately conservative:
 *
 * - it never accepts file paths from request parameters;
 * - it resolves Faculty and Atlas data only through manifests/repositories;
 * - it validates before diffing;
 * - it performs dry-run before apply;
 * - it refuses apply without sesskey and explicit confirmation;
 * - it never modifies JSON source files;
 * - it never modifies enrolments, grades, completions, role assignments or personal badge awards;
 * - it never logs raw JSON source payloads.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $OUTPUT, $PAGE, $SITE;

use local_uckk\local\atlas\voie_normalizer;
use local_uckk\local\atlas\voie_repository;
use local_uckk\local\atlas\voie_validator;
use local_uckk\local\faculty\faculty_cache;
use local_uckk\local\faculty\faculty_moodle_mapper;
use local_uckk\local\faculty\faculty_normalizer;
use local_uckk\local\faculty\faculty_registry;
use local_uckk\local\faculty\faculty_repository;
use local_uckk\local\faculty\faculty_validator;

const LOCAL_UCKK_FACULTY_SYNC_MODE_VALIDATE = 'validate';
const LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN = 'dry_run';
const LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY = 'apply';
const LOCAL_UCKK_FACULTY_SYNC_MODE_REPORT = 'report';

const LOCAL_UCKK_FACULTY_SYNC_STATUS_VALIDATED = 'validated';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_CLEAN = 'dry_run_clean';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_WARNINGS = 'dry_run_warnings';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_BLOCKED = 'dry_run_blocked';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED = 'applied';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED_WITH_WARNINGS = 'applied_with_warnings';
const LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED = 'failed';

/**
 * Validate sync mode.
 *
 * @param string $mode Raw mode.
 * @return string Safe mode.
 */
function local_uckk_faculty_sync_safe_mode(string $mode): string {
    $mode = trim(\core_text::strtolower($mode));

    $allowed = [
        LOCAL_UCKK_FACULTY_SYNC_MODE_VALIDATE,
        LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN,
        LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY,
        LOCAL_UCKK_FACULTY_SYNC_MODE_REPORT,
    ];

    if (!in_array($mode, $allowed, true)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'mode');
    }

    return $mode;
}

/**
 * Validate requested faculty slug.
 *
 * Only "all" or canonical slug format is accepted. This value is never used as
 * a path.
 *
 * @param string $faculty Raw faculty parameter.
 * @return string Safe faculty slug or all.
 */
function local_uckk_faculty_sync_safe_faculty(string $faculty): string {
    $faculty = trim(\core_text::strtolower($faculty));

    if ($faculty === '' || $faculty === 'all') {
        return 'all';
    }

    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $faculty)) {
        throw new moodle_exception('invalidparameter', 'error', '', 'faculty');
    }

    return $faculty;
}

/**
 * Call the first existing instance method.
 *
 * @param object $object Object.
 * @param array<int, string> $methods Candidate methods.
 * @param array<int, mixed> $arguments Arguments.
 * @return mixed
 */
function local_uckk_faculty_sync_call_first(object $object, array $methods, array $arguments = []) {
    foreach ($methods as $method) {
        if (method_exists($object, $method) && is_callable([$object, $method])) {
            return $object->{$method}(...$arguments);
        }
    }

    throw new coding_exception(
        'No supported method found on ' . get_class($object) . ': ' . implode(', ', $methods)
    );
}

/**
 * Call the first existing static method.
 *
 * @param class-string $class Class name.
 * @param array<int, string> $methods Candidate methods.
 * @param array<int, mixed> $arguments Arguments.
 * @return mixed
 */
function local_uckk_faculty_sync_call_static_first(string $class, array $methods, array $arguments = []) {
    foreach ($methods as $method) {
        if (method_exists($class, $method) && is_callable([$class, $method])) {
            return $class::{$method}(...$arguments);
        }
    }

    throw new coding_exception(
        'No supported static method found on ' . $class . ': ' . implode(', ', $methods)
    );
}

/**
 * Convert scalar-ish text to a safe short string.
 *
 * @param mixed $value Value.
 * @param string $default Default.
 * @return string
 */
function local_uckk_faculty_sync_text($value, string $default = ''): string {
    if (!is_scalar($value) && $value !== null) {
        return $default;
    }

    $text = trim((string)$value);

    if ($text === '') {
        return $default;
    }

    return clean_param($text, PARAM_TEXT);
}

/**
 * Convert a string to a Moodle plain-text summary.
 *
 * @param mixed $value Value.
 * @return string
 */
function local_uckk_faculty_sync_summary($value): string {
    if (!is_scalar($value) && $value !== null) {
        return '';
    }

    $text = trim((string)$value);

    if ($text === '') {
        return '';
    }

    return clean_param($text, PARAM_TEXT);
}

/**
 * Compute stable source hash without logging raw JSON.
 *
 * @param mixed $data Data.
 * @return string
 */
function local_uckk_faculty_sync_hash($data): string {
    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
    );

    if ($json === false) {
        return '';
    }

    return hash('sha256', $json);
}

/**
 * Convert validator result to a stable array.
 *
 * @param mixed $result Validator result.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_validation_result($result): array {
    if (is_array($result)) {
        return [
            'status' => (string)($result['status'] ?? 'unknown'),
            'errors' => array_values($result['errors'] ?? []),
            'warnings' => array_values($result['warnings'] ?? []),
            'hash' => (string)($result['hash'] ?? $result['source_hash'] ?? ''),
        ];
    }

    if (!is_object($result)) {
        return [
            'status' => 'unknown',
            'errors' => [],
            'warnings' => [],
            'hash' => '',
        ];
    }

    $errors = [];
    $warnings = [];
    $hash = '';

    if (method_exists($result, 'get_errors')) {
        $errors = (array)$result->get_errors();
    } else if (method_exists($result, 'errors')) {
        $errors = (array)$result->errors();
    } else if (property_exists($result, 'errors')) {
        $errors = (array)$result->errors;
    }

    if (method_exists($result, 'get_warnings')) {
        $warnings = (array)$result->get_warnings();
    } else if (method_exists($result, 'warnings')) {
        $warnings = (array)$result->warnings();
    } else if (property_exists($result, 'warnings')) {
        $warnings = (array)$result->warnings;
    }

    if (method_exists($result, 'get_hash')) {
        $hash = (string)$result->get_hash();
    } else if (property_exists($result, 'hash')) {
        $hash = (string)$result->hash;
    }

    $status = 'completed';

    if ($errors !== []) {
        $status = 'failed';
    } else if ($warnings !== []) {
        $status = 'warning';
    }

    if (method_exists($result, 'get_status')) {
        $status = (string)$result->get_status();
    } else if (property_exists($result, 'status')) {
        $status = (string)$result->status;
    }

    return [
        'status' => $status,
        'errors' => array_values($errors),
        'warnings' => array_values($warnings),
        'hash' => $hash,
    ];
}

/**
 * Convert a report message to safe display text.
 *
 * @param mixed $message Message.
 * @return string
 */
function local_uckk_faculty_sync_message_text($message): string {
    if (is_scalar($message) || $message === null) {
        return trim((string)$message);
    }

    if (is_array($message)) {
        if (isset($message['message'])) {
            return trim((string)$message['message']);
        }

        if (isset($message['code'])) {
            return trim((string)$message['code']);
        }
    }

    if (is_object($message)) {
        if (property_exists($message, 'message')) {
            return trim((string)$message->message);
        }

        if (method_exists($message, '__toString')) {
            return trim((string)$message);
        }
    }

    return 'Sync message';
}

/**
 * Resolve manifest items.
 *
 * @param string $faculty Faculty slug or all.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_faculty_sync_manifest_items(string $faculty): array {
    if ($faculty !== 'all') {
        if (!faculty_registry::exists_slug($faculty)) {
            throw new moodle_exception('invalidparameter', 'error', '', 'faculty');
        }

        return [
            faculty_registry::get_by_slug($faculty),
        ];
    }

    $items = local_uckk_faculty_sync_call_static_first(
        faculty_registry::class,
        ['get_all', 'all', 'items', 'list_all']
    );

    if (!is_array($items)) {
        throw new coding_exception('Faculty registry did not return manifest items.');
    }

    return array_values($items);
}

/**
 * Load a Faculty JSON profile.
 *
 * @param faculty_repository $repository Faculty repository.
 * @param array<string, mixed> $item Manifest item.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_load_profile(faculty_repository $repository, array $item): array {
    $slug = (string)($item['slug'] ?? '');

    $profile = local_uckk_faculty_sync_call_first(
        $repository,
        ['get_by_slug', 'load_by_slug', 'get', 'load'],
        [$slug]
    );

    if (!is_array($profile)) {
        throw new coding_exception('Faculty profile is not an array for slug: ' . $slug);
    }

    return $profile;
}

/**
 * Load an Atlas Voie JSON.
 *
 * @param voie_repository $repository Atlas repository.
 * @param array<string, mixed> $profile Faculty profile.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_load_voie(voie_repository $repository, array $profile): array {
    $voieid = (string)($profile['voie_id'] ?? '');

    $voie = local_uckk_faculty_sync_call_first(
        $repository,
        ['get_by_voie_id', 'load_by_voie_id', 'get_by_id', 'load_by_id', 'get', 'load'],
        [$voieid]
    );

    if (!is_array($voie)) {
        throw new coding_exception('Atlas Voie is not an array for voie_id: ' . $voieid);
    }

    return $voie;
}

/**
 * Build expected Moodle state from validated Faculty and Atlas data.
 *
 * This is not raw JSON output. It is an internal, reduced sync model.
 *
 * @param array<string, mixed> $item Manifest item.
 * @param array<string, mixed> $profile Normalised Faculty profile.
 * @param array<string, mixed> $voie Normalised Atlas Voie.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_expected_model(array $item, array $profile, array $voie): array {
    $moodle = is_array($profile['moodle'] ?? null) ? $profile['moodle'] : [];
    $identity = is_array($profile['identity'] ?? null) ? $profile['identity'] : [];
    $hero = is_array($profile['hero'] ?? null) ? $profile['hero'] : [];
    $projection = is_array($profile['atlas_projection'] ?? null) ? $profile['atlas_projection'] : [];

    $slug = local_uckk_faculty_sync_text($profile['slug'] ?? $item['slug'] ?? '');
    $facultyid = local_uckk_faculty_sync_text($profile['faculty_id'] ?? $item['faculty_id'] ?? '');
    $voieid = local_uckk_faculty_sync_text($profile['voie_id'] ?? $item['voie_id'] ?? '');
    $code = local_uckk_faculty_sync_text(
        $item['code'] ?? $moodle['course_prefix'] ?? $voie['code'] ?? ''
    );
    $courseprefix = local_uckk_faculty_sync_text($moodle['course_prefix'] ?? $item['course_prefix'] ?? $code);

    $categoryidnumber = local_uckk_faculty_sync_text(
        $moodle['category_idnumber'] ?? $item['category_idnumber'] ?? ('UCKK-' . $code)
    );
    $hubcourseidnumber = local_uckk_faculty_sync_text(
        $moodle['hub_course_idnumber'] ?? ($code . '-HUB')
    );

    $categoryname = local_uckk_faculty_sync_text(
        $identity['short_name'] ?? $identity['name'] ?? $voie['nom'] ?? $slug,
        $slug
    );
    $categorydescription = local_uckk_faculty_sync_summary(
        $hero['summary'] ?? $voie['definition_courte'] ?? ''
    );

    $status = local_uckk_faculty_sync_text($profile['status'] ?? 'draft', 'draft');
    $visibility = local_uckk_faculty_sync_text($profile['visibility'] ?? 'hidden', 'hidden');

    $visible = ($status === 'published' && in_array($visibility, ['public', 'restricted'], true)) ? 1 : 0;

    $courses = [];
    $showcourses = array_key_exists('show_courses', $projection) ? (bool)$projection['show_courses'] : true;
    $atlascourses = is_array($voie['cours_conceptuels'] ?? null) ? $voie['cours_conceptuels'] : [];

    if ($showcourses) {
        foreach ($atlascourses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $coursid = local_uckk_faculty_sync_text($course['cours_id'] ?? '');
            $coursname = local_uckk_faculty_sync_text($course['nom'] ?? $coursid, $coursid);

            if ($coursid === '') {
                continue;
            }

            $concept = is_array($course['concept_maitre'] ?? null) ? $course['concept_maitre'] : [];
            $artefact = is_array($course['artefact_maitrise'] ?? null) ? $course['artefact_maitrise'] : [];

            $courses[] = [
                'cours_id' => $coursid,
                'idnumber' => $coursid,
                'shortname' => $coursid,
                'fullname' => $coursid . ' — ' . $coursname,
                'summary' => local_uckk_faculty_sync_summary($course['description'] ?? ''),
                'ordre' => (int)($course['ordre'] ?? 0),
                'concept_maitre_id' => local_uckk_faculty_sync_text($concept['concept_id'] ?? ''),
                'concept_maitre_nom' => local_uckk_faculty_sync_text($concept['nom'] ?? ''),
                'artefact_type' => local_uckk_faculty_sync_text($artefact['type'] ?? ''),
                'artefact_nom' => local_uckk_faculty_sync_text($artefact['nom'] ?? ''),
                'visible' => $visible,
            ];
        }

        usort($courses, static function(array $a, array $b): int {
            return ((int)$a['ordre']) <=> ((int)$b['ordre']);
        });
    }

    return [
        'faculty_id' => $facultyid,
        'voie_id' => $voieid,
        'slug' => $slug,
        'code' => $code,
        'course_prefix' => $courseprefix,
        'status' => $status,
        'visibility' => $visibility,
        'category' => [
            'idnumber' => $categoryidnumber,
            'name' => $categoryname,
            'description' => $categorydescription,
            'visible' => $visible,
        ],
        'hub_course' => [
            'idnumber' => $hubcourseidnumber,
            'shortname' => $hubcourseidnumber,
            'fullname' => 'Hub — ' . $categoryname,
            'summary' => $categorydescription,
            'visible' => $visible,
        ],
        'courses' => $courses,
    ];
}

/**
 * Read current Moodle state for a reduced expected model.
 *
 * @param array<string, mixed> $expected Expected model.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_moodle_state(array $expected): array {
    global $DB;

    $categoryidnumber = (string)$expected['category']['idnumber'];
    $category = $DB->get_record('course_categories', ['idnumber' => $categoryidnumber]);

    $hubidnumber = (string)$expected['hub_course']['idnumber'];
    $hub = $DB->get_record('course', ['idnumber' => $hubidnumber]);

    $courses = [];
    foreach ($expected['courses'] as $course) {
        $idnumber = (string)$course['idnumber'];
        $courses[$idnumber] = $DB->get_record('course', ['idnumber' => $idnumber]);
    }

    return [
        'category' => $category ?: null,
        'hub_course' => $hub ?: null,
        'courses' => $courses,
    ];
}

/**
 * Build sync actions by comparing expected state to Moodle state.
 *
 * @param array<string, mixed> $expected Expected model.
 * @param array<string, mixed> $state Moodle state.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_diff(array $expected, array $state): array {
    $actions = [];
    $warnings = [];
    $blockers = [];

    $category = $state['category'];

    if (!$category) {
        $actions[] = [
            'type' => 'create_category',
            'target' => 'category',
            'idnumber' => $expected['category']['idnumber'],
            'message' => 'Moodle category would be created.',
        ];
        $warnings[] = [
            'code' => 'category_missing',
            'severity' => 'warning',
            'message' => 'Moodle category does not exist and would be created.',
        ];
    } else {
        $categoryupdates = [];

        if ((string)$category->name !== (string)$expected['category']['name']) {
            $categoryupdates[] = 'name';
        }

        if ((string)($category->description ?? '') !== (string)$expected['category']['description']) {
            $categoryupdates[] = 'description';
        }

        if ($categoryupdates !== []) {
            $actions[] = [
                'type' => 'update_category',
                'target' => 'category',
                'idnumber' => $expected['category']['idnumber'],
                'fields' => $categoryupdates,
                'message' => 'Moodle category would be updated.',
            ];
        } else {
            $actions[] = [
                'type' => 'noop',
                'target' => 'category',
                'idnumber' => $expected['category']['idnumber'],
                'message' => 'Moodle category already aligned.',
            ];
        }
    }

    if (count($expected['courses']) !== 10) {
        $blockers[] = [
            'code' => 'invalid_course_count',
            'severity' => 'blocker',
            'message' => 'Atlas Voie must expose exactly 10 conceptual courses for sync.',
        ];
    }

    $courseids = [];
    foreach ($expected['courses'] as $course) {
        $idnumber = (string)$course['idnumber'];

        if (isset($courseids[$idnumber])) {
            $blockers[] = [
                'code' => 'duplicate_course_idnumber',
                'severity' => 'blocker',
                'message' => 'Duplicate course idnumber: ' . $idnumber,
            ];
        }

        $courseids[$idnumber] = true;

        if ($expected['course_prefix'] !== '' && strpos($idnumber, (string)$expected['course_prefix']) !== 0) {
            $blockers[] = [
                'code' => 'course_prefix_mismatch',
                'severity' => 'blocker',
                'message' => 'Course idnumber does not match faculty course prefix: ' . $idnumber,
            ];
        }
    }

    $hub = $state['hub_course'];
    if (!$hub) {
        $actions[] = [
            'type' => 'create_hub_course',
            'target' => 'course',
            'idnumber' => $expected['hub_course']['idnumber'],
            'message' => 'Faculty hub course would be created.',
        ];
        $warnings[] = [
            'code' => 'hub_course_missing',
            'severity' => 'warning',
            'message' => 'Faculty hub course does not exist and would be created.',
        ];
    } else {
        $hubupdates = [];

        if ((string)$hub->fullname !== (string)$expected['hub_course']['fullname']) {
            $hubupdates[] = 'fullname';
        }

        if ((string)$hub->shortname !== (string)$expected['hub_course']['shortname']) {
            $hubupdates[] = 'shortname';
        }

        if ($category && (int)$hub->category !== (int)$category->id) {
            $hubupdates[] = 'category';
        }

        if ($hubupdates !== []) {
            $actions[] = [
                'type' => 'update_hub_course',
                'target' => 'course',
                'idnumber' => $expected['hub_course']['idnumber'],
                'fields' => $hubupdates,
                'message' => 'Faculty hub course would be updated.',
            ];
        } else {
            $actions[] = [
                'type' => 'noop',
                'target' => 'course',
                'idnumber' => $expected['hub_course']['idnumber'],
                'message' => 'Faculty hub course already aligned.',
            ];
        }
    }

    foreach ($expected['courses'] as $course) {
        $idnumber = (string)$course['idnumber'];
        $moodlecourse = $state['courses'][$idnumber] ?? null;

        if (!$moodlecourse) {
            $actions[] = [
                'type' => 'create_course',
                'target' => 'course',
                'idnumber' => $idnumber,
                'message' => 'Moodle course would be created.',
            ];
            continue;
        }

        $courseupdates = [];

        if ((string)$moodlecourse->fullname !== (string)$course['fullname']) {
            $courseupdates[] = 'fullname';
        }

        if ((string)$moodlecourse->shortname !== (string)$course['shortname']) {
            $courseupdates[] = 'shortname';
        }

        if ($category && (int)$moodlecourse->category !== (int)$category->id) {
            $courseupdates[] = 'category';
        }

        if ($courseupdates !== []) {
            $actions[] = [
                'type' => 'update_course',
                'target' => 'course',
                'idnumber' => $idnumber,
                'fields' => $courseupdates,
                'message' => 'Moodle course would be updated.',
            ];
        } else {
            $actions[] = [
                'type' => 'noop',
                'target' => 'course',
                'idnumber' => $idnumber,
                'message' => 'Moodle course already aligned.',
            ];
        }
    }

    return [
        'actions' => $actions,
        'warnings' => $warnings,
        'blockers' => $blockers,
    ];
}

/**
 * Build one item report.
 *
 * @param array<string, mixed> $item Manifest item.
 * @param faculty_repository $facultyrepository Faculty repository.
 * @param faculty_validator $facultyvalidator Faculty validator.
 * @param faculty_normalizer $facultynormalizer Faculty normalizer.
 * @param voie_repository $voierepository Atlas repository.
 * @param voie_validator $voievalidator Atlas validator.
 * @param voie_normalizer $voienormalizer Atlas normalizer.
 * @param faculty_moodle_mapper $mapper Faculty Moodle mapper.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_build_item(
    array $item,
    faculty_repository $facultyrepository,
    faculty_validator $facultyvalidator,
    faculty_normalizer $facultynormalizer,
    voie_repository $voierepository,
    voie_validator $voievalidator,
    voie_normalizer $voienormalizer,
    faculty_moodle_mapper $mapper
): array {
    $slug = (string)($item['slug'] ?? '');
    $facultyid = (string)($item['faculty_id'] ?? '');
    $voieid = (string)($item['voie_id'] ?? '');

    $report = [
        'faculty_id' => $facultyid,
        'voie_id' => $voieid,
        'slug' => $slug,
        'code' => (string)($item['code'] ?? ''),
        'category_idnumber' => (string)($item['category_idnumber'] ?? ''),
        'hub_course_idnumber' => '',
        'status' => LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_CLEAN,
        'source_hashes' => [
            'faculty_source_hash' => '',
            'atlas_source_hash' => '',
            'combined_source_hash' => '',
        ],
        'moodle_state' => [
            'category_exists' => false,
            'hub_course_exists' => false,
            'course_count_expected' => 0,
            'course_count_existing' => 0,
        ],
        'actions' => [],
        'warnings' => [],
        'blockers' => [],
        '_expected' => null,
    ];

    try {
        $profile = local_uckk_faculty_sync_load_profile($facultyrepository, $item);
        $voie = local_uckk_faculty_sync_load_voie($voierepository, $profile);

        $facultyvalidation = local_uckk_faculty_sync_validation_result(
            local_uckk_faculty_sync_call_first($facultyvalidator, ['validate', 'validate_profile'], [$profile])
        );
        $voievalidation = local_uckk_faculty_sync_validation_result(
            local_uckk_faculty_sync_call_first($voievalidator, ['validate', 'validate_voie'], [$voie])
        );

        $normalprofile = local_uckk_faculty_sync_call_first(
            $facultynormalizer,
            ['normalize', 'normalise'],
            [$profile]
        );
        $normalvoie = local_uckk_faculty_sync_call_first(
            $voienormalizer,
            ['normalize', 'normalise'],
            [$voie]
        );

        if (!is_array($normalprofile)) {
            $normalprofile = $profile;
        }

        if (!is_array($normalvoie)) {
            $normalvoie = $voie;
        }

        $facultyhash = $facultyvalidation['hash'] !== ''
            ? $facultyvalidation['hash']
            : local_uckk_faculty_sync_hash($normalprofile);
        $atlashash = $voievalidation['hash'] !== ''
            ? $voievalidation['hash']
            : local_uckk_faculty_sync_hash($normalvoie);

        $report['source_hashes'] = [
            'faculty_source_hash' => $facultyhash,
            'atlas_source_hash' => $atlashash,
            'combined_source_hash' => hash('sha256', $facultyhash . ':' . $atlashash),
        ];

        foreach ($facultyvalidation['errors'] as $error) {
            $report['blockers'][] = [
                'code' => 'faculty_validation_error',
                'severity' => 'blocker',
                'message' => local_uckk_faculty_sync_message_text($error),
            ];
        }

        foreach ($voievalidation['errors'] as $error) {
            $report['blockers'][] = [
                'code' => 'atlas_validation_error',
                'severity' => 'blocker',
                'message' => local_uckk_faculty_sync_message_text($error),
            ];
        }

        foreach ($facultyvalidation['warnings'] as $warning) {
            $report['warnings'][] = [
                'code' => 'faculty_validation_warning',
                'severity' => 'warning',
                'message' => local_uckk_faculty_sync_message_text($warning),
            ];
        }

        foreach ($voievalidation['warnings'] as $warning) {
            $report['warnings'][] = [
                'code' => 'atlas_validation_warning',
                'severity' => 'warning',
                'message' => local_uckk_faculty_sync_message_text($warning),
            ];
        }

        if ((string)($normalprofile['voie_id'] ?? '') !== (string)($normalvoie['voie_id'] ?? '')) {
            $report['blockers'][] = [
                'code' => 'voie_id_mismatch',
                'severity' => 'blocker',
                'message' => 'Faculty profile voie_id does not match Atlas voie_id.',
            ];
        }

        if (method_exists($mapper, 'build_expected_model')) {
            $expected = $mapper->build_expected_model($normalprofile, $normalvoie, $item);
        } else if (method_exists($mapper, 'map')) {
            $expected = $mapper->map($normalprofile, $normalvoie, $item);
        } else {
            $expected = local_uckk_faculty_sync_expected_model($item, $normalprofile, $normalvoie);
        }

        if (!is_array($expected)) {
            throw new coding_exception('Faculty Moodle mapper did not return an expected model array.');
        }

        $report['_expected'] = $expected;
        $report['category_idnumber'] = (string)($expected['category']['idnumber'] ?? '');
        $report['hub_course_idnumber'] = (string)($expected['hub_course']['idnumber'] ?? '');

        $state = local_uckk_faculty_sync_moodle_state($expected);
        $diff = local_uckk_faculty_sync_diff($expected, $state);

        $existingcourses = 0;
        foreach ($state['courses'] as $course) {
            if ($course) {
                $existingcourses++;
            }
        }

        $report['moodle_state'] = [
            'category_exists' => !empty($state['category']),
            'hub_course_exists' => !empty($state['hub_course']),
            'course_count_expected' => count($expected['courses'] ?? []),
            'course_count_existing' => $existingcourses,
        ];

        $report['actions'] = array_merge($report['actions'], $diff['actions']);
        $report['warnings'] = array_merge($report['warnings'], $diff['warnings']);
        $report['blockers'] = array_merge($report['blockers'], $diff['blockers']);

        if ($report['blockers'] !== []) {
            $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_BLOCKED;
        } else if ($report['warnings'] !== []) {
            $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_WARNINGS;
        } else {
            $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_CLEAN;
        }
    } catch (Throwable $exception) {
        $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED;
        $report['blockers'][] = [
            'code' => 'sync_exception',
            'severity' => 'blocker',
            'message' => $exception->getMessage(),
        ];
    }

    return $report;
}

/**
 * Build complete sync report.
 *
 * @param string $mode Sync mode.
 * @param string $faculty Faculty slug or all.
 * @return array<string, mixed>
 */
function local_uckk_faculty_sync_build_report(string $mode, string $faculty): array {
    $items = local_uckk_faculty_sync_manifest_items($faculty);

    $facultyrepository = new faculty_repository();
    $facultyvalidator = new faculty_validator();
    $facultynormalizer = new faculty_normalizer();

    $voierepository = new voie_repository();
    $voievalidator = new voie_validator();
    $voienormalizer = new voie_normalizer();

    $mapper = new faculty_moodle_mapper();

    $reports = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $reports[] = local_uckk_faculty_sync_build_item(
            $item,
            $facultyrepository,
            $facultyvalidator,
            $facultynormalizer,
            $voierepository,
            $voievalidator,
            $voienormalizer,
            $mapper
        );
    }

    $warnings = 0;
    $blockers = 0;
    $actions = 0;
    $hashes = [];

    foreach ($reports as $itemreport) {
        $warnings += count($itemreport['warnings']);
        $blockers += count($itemreport['blockers']);
        $actions += count(array_filter($itemreport['actions'], static function(array $action): bool {
            return ($action['type'] ?? '') !== 'noop';
        }));

        if (!empty($itemreport['source_hashes']['combined_source_hash'])) {
            $hashes[] = $itemreport['source_hashes']['combined_source_hash'];
        }
    }

    if ($mode === LOCAL_UCKK_FACULTY_SYNC_MODE_VALIDATE) {
        $status = $blockers > 0 ? LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED : LOCAL_UCKK_FACULTY_SYNC_STATUS_VALIDATED;
    } else {
        $status = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_CLEAN;

        if ($blockers > 0) {
            $status = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_BLOCKED;
        } else if ($warnings > 0) {
            $status = LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_WARNINGS;
        }
    }

    return [
        'schema_version' => 'UCKK-ATLAS-SYNC-REPORT-0.1',
        'mode' => $mode,
        'status' => $status,
        'generated_at' => gmdate('c'),
        'component' => 'local_uckk',
        'faculty' => $faculty,
        'counts' => [
            'faculties_total' => count($reports),
            'actions' => $actions,
            'warnings' => $warnings,
            'blockers' => $blockers,
        ],
        'items' => $reports,
        'warnings' => $warnings,
        'blockers' => $blockers,
        'hashes' => [
            'combined_manifest_hash' => hash('sha256', implode(':', $hashes)),
        ],
    ];
}

/**
 * Get or create Moodle category.
 *
 * @param array<string, mixed> $expected Expected model.
 * @param array<string, mixed> $applied Applied counters.
 * @return stdClass Moodle category record.
 */
function local_uckk_faculty_sync_apply_category(array $expected, array &$applied): stdClass {
    global $DB;

    $categoryexpected = $expected['category'];
    $idnumber = (string)$categoryexpected['idnumber'];
    $category = $DB->get_record('course_categories', ['idnumber' => $idnumber]);

    if (!$category) {
        $created = core_course_category::create([
            'name' => (string)$categoryexpected['name'],
            'idnumber' => $idnumber,
            'description' => (string)$categoryexpected['description'],
            'descriptionformat' => FORMAT_PLAIN,
            'parent' => 0,
            'visible' => (int)$categoryexpected['visible'],
        ]);

        $applied['categories_created']++;
        return $DB->get_record('course_categories', ['id' => $created->id], '*', MUST_EXIST);
    }

    $updates = [];

    if ((string)$category->name !== (string)$categoryexpected['name']) {
        $updates['name'] = (string)$categoryexpected['name'];
    }

    if ((string)($category->description ?? '') !== (string)$categoryexpected['description']) {
        $updates['description'] = (string)$categoryexpected['description'];
        $updates['descriptionformat'] = FORMAT_PLAIN;
    }

    if ($updates !== []) {
        $cat = core_course_category::get((int)$category->id, MUST_EXIST, true);
        $cat->update($updates);
        $applied['categories_updated']++;

        return $DB->get_record('course_categories', ['id' => $category->id], '*', MUST_EXIST);
    }

    return $category;
}

/**
 * Create or update a Moodle course shell.
 *
 * @param array<string, mixed> $course Expected course shell.
 * @param stdClass $category Target Moodle category.
 * @param bool $ishub Whether this is the faculty hub course.
 * @param array<string, mixed> $applied Applied counters.
 */
function local_uckk_faculty_sync_apply_course(
    array $course,
    stdClass $category,
    bool $ishub,
    array &$applied
): void {
    global $DB;

    $idnumber = (string)$course['idnumber'];
    $existing = $DB->get_record('course', ['idnumber' => $idnumber]);

    if (!$existing) {
        $record = new stdClass();
        $record->fullname = (string)$course['fullname'];
        $record->shortname = (string)$course['shortname'];
        $record->idnumber = $idnumber;
        $record->category = (int)$category->id;
        $record->summary = (string)($course['summary'] ?? '');
        $record->summaryformat = FORMAT_PLAIN;
        $record->visible = (int)($course['visible'] ?? 0);

        create_course($record);

        if ($ishub) {
            $applied['hub_courses_created']++;
        } else {
            $applied['courses_created']++;
        }

        return;
    }

    $record = new stdClass();
    $record->id = (int)$existing->id;
    $changed = false;

    if ((string)$existing->fullname !== (string)$course['fullname']) {
        $record->fullname = (string)$course['fullname'];
        $changed = true;
    }

    if ((string)$existing->shortname !== (string)$course['shortname']) {
        $record->shortname = (string)$course['shortname'];
        $changed = true;
    }

    if ((int)$existing->category !== (int)$category->id) {
        $record->category = (int)$category->id;
        $changed = true;
    }

    if ((string)($existing->summary ?? '') !== (string)($course['summary'] ?? '')) {
        $record->summary = (string)($course['summary'] ?? '');
        $record->summaryformat = FORMAT_PLAIN;
        $changed = true;
    }

    if ((int)$existing->visible !== (int)($course['visible'] ?? 0)) {
        $record->visible = (int)($course['visible'] ?? 0);
        $changed = true;
    }

    if ($changed) {
        update_course($record);

        if ($ishub) {
            $applied['hub_courses_updated']++;
        } else {
            $applied['courses_updated']++;
        }
    }
}

/**
 * Purge relevant faculty caches if available.
 *
 * @param string $slug Faculty slug.
 * @param array<string, mixed> $applied Applied counters.
 */
function local_uckk_faculty_sync_purge_cache(string $slug, array &$applied): void {
    if (!class_exists(faculty_cache::class)) {
        return;
    }

    if (method_exists(faculty_cache::class, 'purge_slug')) {
        faculty_cache::purge_slug($slug);
        $applied['cache_purges']++;
        return;
    }

    if (method_exists(faculty_cache::class, 'purge_faculty')) {
        faculty_cache::purge_faculty($slug);
        $applied['cache_purges']++;
        return;
    }

    if (method_exists(faculty_cache::class, 'purge_all')) {
        faculty_cache::purge_all();
        $applied['cache_purges']++;
    }
}

/**
 * Apply a validated dry-run report.
 *
 * @param array<string, mixed> $report Dry-run report.
 * @return array<string, mixed> Apply counters.
 */
function local_uckk_faculty_sync_apply_report(array &$report): array {
    $applied = [
        'categories_created' => 0,
        'categories_updated' => 0,
        'hub_courses_created' => 0,
        'hub_courses_updated' => 0,
        'courses_created' => 0,
        'courses_updated' => 0,
        'cache_purges' => 0,
        'errors' => 0,
    ];

    if (($report['counts']['blockers'] ?? 0) > 0) {
        throw new moodle_exception('error', 'error', '', null, 'Cannot apply a blocked Atlas sync report.');
    }

    foreach ($report['items'] as &$item) {
        if (($item['blockers'] ?? []) !== []) {
            continue;
        }

        $expected = $item['_expected'] ?? null;

        if (!is_array($expected)) {
            $item['blockers'][] = [
                'code' => 'missing_expected_model',
                'severity' => 'blocker',
                'message' => 'Cannot apply item without expected Moodle model.',
            ];
            $applied['errors']++;
            continue;
        }

        try {
            $category = local_uckk_faculty_sync_apply_category($expected, $applied);
            local_uckk_faculty_sync_apply_course($expected['hub_course'], $category, true, $applied);

            foreach ($expected['courses'] as $course) {
                local_uckk_faculty_sync_apply_course($course, $category, false, $applied);
            }

            local_uckk_faculty_sync_purge_cache((string)$expected['slug'], $applied);
            $item['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED;
        } catch (Throwable $exception) {
            $item['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED;
            $item['blockers'][] = [
                'code' => 'apply_exception',
                'severity' => 'blocker',
                'message' => $exception->getMessage(),
            ];
            $applied['errors']++;
        }
    }

    unset($item);

    return $applied;
}

/**
 * Strip internal keys before display or JSON output.
 *
 * @param mixed $value Value.
 * @return mixed
 */
function local_uckk_faculty_sync_public_value($value) {
    if (!is_array($value)) {
        return $value;
    }

    $clean = [];

    foreach ($value as $key => $subvalue) {
        if (is_string($key) && str_starts_with($key, '_')) {
            continue;
        }

        $clean[$key] = local_uckk_faculty_sync_public_value($subvalue);
    }

    return $clean;
}

/**
 * Trigger sync event with only safe metadata.
 *
 * @param context_system $context Context.
 * @param string $mode Mode.
 * @param array<string, mixed> $report Report.
 */
function local_uckk_faculty_sync_trigger_event(context_system $context, string $mode, array $report): void {
    $eventclass = $mode === LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY
        ? '\\local_uckk\\event\\atlas_sync_applied'
        : '\\local_uckk\\event\\atlas_sync_dryrun_completed';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'context' => $context,
        'other' => [
            'mode' => $mode,
            'status' => (string)($report['status'] ?? ''),
            'faculty_count' => (int)($report['counts']['faculties_total'] ?? 0),
            'action_count' => (int)($report['counts']['actions'] ?? 0),
            'warning_count' => (int)($report['counts']['warnings'] ?? 0),
            'blocker_count' => (int)($report['counts']['blockers'] ?? 0),
            'combined_manifest_hash' => (string)($report['hashes']['combined_manifest_hash'] ?? ''),
        ],
    ]);

    $event->trigger();
}

/**
 * Emit JSON report.
 *
 * @param array<string, mixed> $report Report.
 * @return never
 */
function local_uckk_faculty_sync_emit_json(array $report): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        local_uckk_faculty_sync_public_value($report),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

/**
 * Render status badge.
 *
 * @param string $status Status.
 * @return string HTML.
 */
function local_uckk_faculty_sync_status_badge(string $status): string {
    $class = 'badge-secondary';

    if (in_array($status, [
        LOCAL_UCKK_FACULTY_SYNC_STATUS_VALIDATED,
        LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_CLEAN,
        LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED,
    ], true)) {
        $class = 'badge-success';
    } else if (in_array($status, [
        LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_WARNINGS,
        LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED_WITH_WARNINGS,
    ], true)) {
        $class = 'badge-warning';
    } else if (in_array($status, [
        LOCAL_UCKK_FACULTY_SYNC_STATUS_DRY_RUN_BLOCKED,
        LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED,
    ], true)) {
        $class = 'badge-danger';
    }

    return html_writer::span(s($status), 'badge ' . $class);
}

/**
 * Render messages as list.
 *
 * @param array<int, mixed> $messages Messages.
 * @param string $class CSS class.
 * @return string HTML.
 */
function local_uckk_faculty_sync_render_messages(array $messages, string $class): string {
    if ($messages === []) {
        return html_writer::span('—', 'text-muted');
    }

    $items = [];

    foreach ($messages as $message) {
        $items[] = html_writer::tag('li', s(local_uckk_faculty_sync_message_text($message)));
    }

    return html_writer::tag('ul', implode('', $items), ['class' => $class]);
}

/**
 * Render mode selector and action controls.
 *
 * @param moodle_url $url Page URL.
 * @param string $faculty Current faculty.
 * @return string HTML.
 */
function local_uckk_faculty_sync_render_controls(moodle_url $url, string $faculty): string {
    $html = html_writer::start_div('card card-body mb-3');

    $html .= html_writer::tag('h3', 'Atlas → Moodle sync');

    $html .= html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $url->out(false),
        'class' => 'mb-3',
    ]);

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::label('Faculty slug', 'id_faculty');
    $html .= html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'faculty',
        'id' => 'id_faculty',
        'value' => s($faculty),
        'class' => 'form-control',
        'placeholder' => 'all',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('form-group');
    $html .= html_writer::label('Mode', 'id_mode');
    $html .= html_writer::select([
        LOCAL_UCKK_FACULTY_SYNC_MODE_VALIDATE => 'Validate',
        LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN => 'Dry-run',
        LOCAL_UCKK_FACULTY_SYNC_MODE_REPORT => 'Report',
    ], 'mode', LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN, false, [
        'id' => 'id_mode',
        'class' => 'custom-select form-control',
    ]);
    $html .= html_writer::end_div();

    $html .= html_writer::tag('button', 'Run', [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]);

    $html .= html_writer::end_tag('form');

    $html .= html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $url->out(false),
        'class' => 'border-top pt-3',
    ]);

    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'mode',
        'value' => LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY,
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'faculty',
        'value' => s($faculty),
    ]);
    $html .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'confirm',
        'value' => 1,
    ]);

    $html .= html_writer::tag(
        'p',
        'Apply runs a fresh dry-run first and refuses to proceed if blockers exist.',
        ['class' => 'text-muted']
    );

    $html .= html_writer::tag('button', 'Apply validated sync', [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]);

    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Render report.
 *
 * @param array<string, mixed> $report Report.
 * @param moodle_url $url Page URL.
 * @param string $faculty Current faculty.
 * @return string HTML.
 */
function local_uckk_faculty_sync_render_report(array $report, moodle_url $url, string $faculty): string {
    $report = local_uckk_faculty_sync_public_value($report);

    $html = local_uckk_faculty_sync_render_controls($url, $faculty);

    $summary = new html_table();
    $summary->attributes['class'] = 'generaltable';
    $summary->head = ['Metric', 'Value'];
    $summary->data = [
        ['Mode', s((string)$report['mode'])],
        ['Status', local_uckk_faculty_sync_status_badge((string)$report['status'])],
        ['Faculties', (string)$report['counts']['faculties_total']],
        ['Actions', (string)$report['counts']['actions']],
        ['Warnings', (string)$report['counts']['warnings']],
        ['Blockers', (string)$report['counts']['blockers']],
        ['Combined hash', html_writer::tag('code', s((string)$report['hashes']['combined_manifest_hash']))],
    ];

    if (!empty($report['apply_counts'])) {
        foreach ($report['apply_counts'] as $key => $value) {
            $summary->data[] = [s((string)$key), s((string)$value)];
        }
    }

    $html .= html_writer::tag('h3', 'Summary');
    $html .= html_writer::table($summary);

    $items = new html_table();
    $items->attributes['class'] = 'generaltable';
    $items->head = [
        'Faculty',
        'Voie',
        'Slug',
        'Status',
        'Moodle state',
        'Actions',
        'Warnings',
        'Blockers',
        'Hashes',
    ];
    $items->data = [];

    foreach ($report['items'] as $item) {
        $actiontexts = [];
        foreach ($item['actions'] as $action) {
            $actiontexts[] = html_writer::tag(
                'li',
                html_writer::tag('code', s((string)$action['type']))
                    . ' '
                    . s((string)($action['idnumber'] ?? ''))
                    . ' — '
                    . s((string)($action['message'] ?? ''))
            );
        }

        $items->data[] = [
            html_writer::tag('code', s((string)$item['faculty_id'])),
            html_writer::tag('code', s((string)$item['voie_id'])),
            html_writer::tag('code', s((string)$item['slug'])),
            local_uckk_faculty_sync_status_badge((string)$item['status']),
            html_writer::div('Category: ' . (!empty($item['moodle_state']['category_exists']) ? 'yes' : 'no'))
                . html_writer::div('Hub: ' . (!empty($item['moodle_state']['hub_course_exists']) ? 'yes' : 'no'))
                . html_writer::div(
                    'Courses: '
                    . s((string)$item['moodle_state']['course_count_existing'])
                    . '/'
                    . s((string)$item['moodle_state']['course_count_expected'])
                ),
            $actiontexts === []
                ? html_writer::span('—', 'text-muted')
                : html_writer::tag('ul', implode('', $actiontexts)),
            local_uckk_faculty_sync_render_messages($item['warnings'], 'text-warning'),
            local_uckk_faculty_sync_render_messages($item['blockers'], 'text-danger'),
            html_writer::div(
                'Faculty: ' . html_writer::tag('code', s((string)$item['source_hashes']['faculty_source_hash']))
            )
                . html_writer::div(
                    'Atlas: ' . html_writer::tag('code', s((string)$item['source_hashes']['atlas_source_hash']))
                ),
        ];
    }

    $html .= html_writer::tag('h3', 'Items');
    $html .= html_writer::table($items);

    return $html;
}

$context = context_system::instance();
$url = new moodle_url('/local/uckk/faculty_sync.php');

require_login();

$mode = local_uckk_faculty_sync_safe_mode(
    optional_param('mode', LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN, PARAM_ALPHANUMEXT)
);
$faculty = local_uckk_faculty_sync_safe_faculty(
    optional_param('faculty', 'all', PARAM_ALPHANUMEXT)
);
$format = optional_param('format', 'html', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if (!in_array($format, ['html', 'json'], true)) {
    throw new moodle_exception('invalidparameter', 'error', '', 'format');
}

if ($mode === LOCAL_UCKK_FACULTY_SYNC_MODE_REPORT) {
    require_capability('local/uckk:viewfacultysyncreport', $context);
} else {
    require_capability('local/uckk:syncatlasmoodle', $context);
}

if ($mode === LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY) {
    require_sesskey();

    if (!$confirm) {
        throw new moodle_exception('confirmsesskeybad', 'error');
    }
}

$PAGE->set_context($context);
$PAGE->set_url($url, [
    'mode' => $mode,
    'faculty' => $faculty,
    'format' => $format,
]);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('UCKK Atlas Moodle sync');
$PAGE->set_heading(format_string($SITE->fullname));

$effectivebuildmode = $mode === LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY
    ? LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN
    : $mode;

$report = local_uckk_faculty_sync_build_report($effectivebuildmode, $faculty);

if ($mode === LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY) {
    $applycounts = local_uckk_faculty_sync_apply_report($report);

    $report['mode'] = LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY;
    $report['apply_counts'] = $applycounts;

    if ($applycounts['errors'] > 0) {
        $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_FAILED;
    } else if (($report['counts']['warnings'] ?? 0) > 0) {
        $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED_WITH_WARNINGS;
    } else {
        $report['status'] = LOCAL_UCKK_FACULTY_SYNC_STATUS_APPLIED;
    }

    local_uckk_faculty_sync_trigger_event($context, LOCAL_UCKK_FACULTY_SYNC_MODE_APPLY, $report);
} else if ($mode === LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN) {
    local_uckk_faculty_sync_trigger_event($context, LOCAL_UCKK_FACULTY_SYNC_MODE_DRY_RUN, $report);
}

if ($format === 'json') {
    local_uckk_faculty_sync_emit_json($report);
}

echo $OUTPUT->header();
echo local_uckk_faculty_sync_render_report($report, $url, $faculty);
echo $OUTPUT->footer();

