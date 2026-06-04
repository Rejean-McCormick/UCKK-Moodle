<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Direct shortcut to add a media record to the central UCKK Médiathèque.
 *
 * This route intentionally belongs to local_uckk because it is a stable UCKK
 * shortcut surface. It does not create media records directly and does not
 * bypass mod_uckkarchive policy.
 *
 * It resolves the central Médiathèque container, verifies the module
 * capability, then redirects to the canonical mod_uckkarchive media editor:
 *
 *     /mod/uckkarchive/media.php?id=CMID&action=add
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Central Médiathèque course fullname.
 */
const LOCAL_UCKK_MEDIATHEQUE_COURSE_FULLNAME = 'Médiathèque centrale UCKK';

/**
 * Resolve the central Médiathèque course.
 *
 * @return stdClass Moodle course record.
 */
function local_uckk_mediatheque_add_resolve_course(): stdClass {
    global $DB;

    $courseid = optional_param('courseid', 0, PARAM_INT);

    if ($courseid > 0) {
        return get_course($courseid);
    }

    $course = $DB->get_record(
        'course',
        ['fullname' => LOCAL_UCKK_MEDIATHEQUE_COURSE_FULLNAME],
        '*',
        IGNORE_MISSING
    );

    if ($course) {
        return $course;
    }

    $courses = $DB->get_records_select(
        'course',
        'fullname LIKE :fullname',
        ['fullname' => '%Médiathèque centrale%'],
        'id ASC',
        '*',
        0,
        1
    );

    if (!empty($courses)) {
        return reset($courses);
    }

    throw new moodle_exception(
        'invalidcourseid',
        'error',
        '',
        null,
        'Aucun cours conteneur Médiathèque centrale UCKK trouvé.'
    );
}

/**
 * Resolve the target uckkarchive course module.
 *
 * @param stdClass $course Moodle course record.
 * @return stdClass Moodle course_modules record.
 */
function local_uckk_mediatheque_add_resolve_cm(stdClass $course): stdClass {
    $cmid = optional_param('cmid', 0, PARAM_INT);

    if ($cmid > 0) {
        return get_coursemodule_from_id('uckkarchive', $cmid, 0, false, MUST_EXIST);
    }

    $modinfo = get_fast_modinfo($course);
    $preferredcmid = 0;
    $fallbackcmid = 0;

    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname !== 'uckkarchive') {
            continue;
        }

        if (!empty($cm->deletioninprogress)) {
            continue;
        }

        if ($fallbackcmid <= 0) {
            $fallbackcmid = (int)$cm->id;
        }

        $name = trim((string)$cm->name);

        if ($name === 'Médiathèque centrale') {
            $preferredcmid = (int)$cm->id;
            break;
        }

        if (str_contains($name, 'Médiathèque centrale')) {
            $preferredcmid = (int)$cm->id;
        }
    }

    $targetcmid = $preferredcmid > 0 ? $preferredcmid : $fallbackcmid;

    if ($targetcmid <= 0) {
        throw new moodle_exception(
            'invalidcoursemodule',
            'error',
            '',
            null,
            'Aucune activité Médiathèque trouvée dans le cours conteneur.'
        );
    }

    return get_coursemodule_from_id('uckkarchive', $targetcmid, (int)$course->id, false, MUST_EXIST);
}

$course = local_uckk_mediatheque_add_resolve_course();
$cm = local_uckk_mediatheque_add_resolve_cm($course);
$context = context_module::instance((int)$cm->id);

require_login($course, false, $cm);
require_capability('mod/uckkarchive:view', $context);
require_capability('mod/uckkarchive:addmedia', $context);

$params = [
    'id' => (int)$cm->id,
    'action' => 'add',
];

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($returnurl !== '') {
    $params['returnurl'] = $returnurl;
}

redirect(new moodle_url('/mod/uckkarchive/media.php', $params));