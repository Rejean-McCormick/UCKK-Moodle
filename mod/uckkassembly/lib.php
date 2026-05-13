<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Library callbacks for the UCKK Assembly activity module.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return whether a Moodle activity feature is supported.
 *
 * @param string $feature Feature constant.
 * @return bool|int|null
 */
function uckkassembly_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;

        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_RATE:
            return false;

        default:
            return null;
    }
}

/**
 * Add a new UCKK Assembly instance.
 *
 * @param stdClass $data Form data.
 * @param mod_uckkassembly_mod_form|null $mform Form instance.
 * @return int New instance id.
 */
function uckkassembly_add_instance(stdClass $data, $mform = null): int {
    global $DB, $USER;

    $now = time();

    $record = uckkassembly_normalise_instance_record($data, true);
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->createdby = (int)$USER->id;
    $record->modifiedby = (int)$USER->id;
    $record->versionno = 1;

    $id = $DB->insert_record('uckkassembly', $record);

    $data->id = $id;

    uckkassembly_update_calendar_events($data);
    uckkassembly_update_completion_rules($data);

    if (class_exists('\mod_uckkassembly\event\assembly_created')) {
        $cmid = (int)($data->coursemodule ?? 0);
        if ($cmid > 0) {
            $cm = get_coursemodule_from_id('uckkassembly', $cmid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $context = context_module::instance($cm->id);
                $event = \mod_uckkassembly\event\assembly_created::create([
                    'objectid' => $id,
                    'context' => $context,
                    'other' => [
                        'courseid' => (int)$data->course,
                        'cmid' => (int)$cm->id,
                        'assemblytype' => $record->assemblytype,
                        'status' => $record->status,
                        'visibility' => $record->visibility,
                    ],
                ]);
                $event->add_record_snapshot('uckkassembly', $DB->get_record('uckkassembly', ['id' => $id], '*', MUST_EXIST));
                $event->trigger();
            }
        }
    }

    return $id;
}

/**
 * Update an existing UCKK Assembly instance.
 *
 * @param stdClass $data Form data.
 * @param mod_uckkassembly_mod_form|null $mform Form instance.
 * @return bool
 */
function uckkassembly_update_instance(stdClass $data, $mform = null): bool {
    global $DB, $USER;

    $data->id = (int)$data->instance;

    $existing = $DB->get_record('uckkassembly', ['id' => $data->id], '*', MUST_EXIST);

    $record = uckkassembly_normalise_instance_record($data, false);
    $record->id = $data->id;
    $record->timecreated = (int)$existing->timecreated;
    $record->createdby = (int)$existing->createdby;
    $record->timemodified = time();
    $record->modifiedby = (int)$USER->id;
    $record->versionno = ((int)$existing->versionno) + 1;

    $DB->update_record('uckkassembly', $record);

    uckkassembly_update_calendar_events($data);
    uckkassembly_update_completion_rules($data);

    if (class_exists('\mod_uckkassembly\event\assembly_updated')) {
        $cmid = (int)($data->coursemodule ?? 0);
        if ($cmid > 0) {
            $cm = get_coursemodule_from_id('uckkassembly', $cmid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $context = context_module::instance($cm->id);
                $event = \mod_uckkassembly\event\assembly_updated::create([
                    'objectid' => $data->id,
                    'context' => $context,
                    'other' => [
                        'courseid' => (int)$data->course,
                        'cmid' => (int)$cm->id,
                        'assemblytype' => $record->assemblytype,
                        'status' => $record->status,
                        'visibility' => $record->visibility,
                    ],
                ]);
                $event->add_record_snapshot('uckkassembly', $DB->get_record('uckkassembly', ['id' => $data->id], '*', MUST_EXIST));
                $event->trigger();
            }
        }
    }

    return true;
}

/**
 * Delete an UCKK Assembly instance and its owned records.
 *
 * Archive and integrity records owned by other components must not be silently
 * deleted here. They must remain versioned and auditable in their owner plugin.
 *
 * @param int $id Assembly instance id.
 * @return bool
 */
function uckkassembly_delete_instance($id): bool {
    global $DB;

    $id = (int)$id;

    if (!$assembly = $DB->get_record('uckkassembly', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('uckkassembly', $id, (int)$assembly->course, false, IGNORE_MISSING);
    $context = $cm ? context_module::instance($cm->id) : null;

    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records('uckkassembly_contest', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_minutes', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_decision', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_vote', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_object', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_amend', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly_motion', ['assemblyid' => $id]);
    $DB->delete_records('uckkassembly', ['id' => $id]);

    if ($context) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_uckkassembly');
    }

    $DB->delete_records('event', [
        'modulename' => 'uckkassembly',
        'instance' => $id,
    ]);

    $transaction->allow_commit();

    if ($context && class_exists('\mod_uckkassembly\event\assembly_deleted')) {
        $event = \mod_uckkassembly\event\assembly_deleted::create([
            'objectid' => $id,
            'context' => $context,
            'other' => [
                'courseid' => (int)$assembly->course,
                'cmid' => (int)$cm->id,
                'assemblytype' => $assembly->assemblytype ?? '',
                'status' => $assembly->status ?? '',
                'visibility' => $assembly->visibility ?? '',
            ],
        ]);
        $event->add_record_snapshot('uckkassembly', $assembly);
        $event->trigger();
    }

    return true;
}

/**
 * Return information for Moodle course module cache.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|null
 */
function uckkassembly_get_coursemodule_info($coursemodule): ?cached_cm_info {
    global $DB;

    $assembly = $DB->get_record('uckkassembly', ['id' => $coursemodule->instance], 'id, name, intro, introformat, timeopen, timeclose, status, visibility');

    if (!$assembly) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = format_string($assembly->name, true);

    if (!empty($assembly->intro)) {
        $info->content = format_module_intro('uckkassembly', $assembly, $coursemodule->id, false);
    }

    if (!empty($assembly->timeopen) || !empty($assembly->timeclose)) {
        $customdata = [
            'timeopen' => (int)$assembly->timeopen,
            'timeclose' => (int)$assembly->timeclose,
            'status' => (string)$assembly->status,
            'visibility' => (string)$assembly->visibility,
        ];
        $info->customdata = json_encode($customdata);
    }

    return $info;
}

/**
 * Mark the activity viewed for completion tracking.
 *
 * @param stdClass $assembly Assembly instance.
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param stdClass|null $context Module context.
 */
function uckkassembly_view(stdClass $assembly, stdClass $course, stdClass $cm, ?stdClass $context = null): void {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    if (class_exists('\mod_uckkassembly\event\assembly_viewed')) {
        $modulecontext = $context instanceof context_module ? $context : context_module::instance($cm->id);

        $event = \mod_uckkassembly\event\assembly_viewed::create([
            'objectid' => (int)$assembly->id,
            'context' => $modulecontext,
            'other' => [
                'courseid' => (int)$course->id,
                'cmid' => (int)$cm->id,
                'assemblytype' => $assembly->assemblytype ?? '',
                'status' => $assembly->status ?? '',
                'visibility' => $assembly->visibility ?? '',
            ],
        ]);

        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('uckkassembly', $assembly);
        $event->trigger();
    }
}

/**
 * Return activity overview for a user.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param cm_info|stdClass $mod Course module object.
 * @param stdClass $assembly Assembly instance.
 * @return stdClass|null
 */
function uckkassembly_user_outline($course, $user, $mod, $assembly): ?stdClass {
    global $DB;

    $info = new stdClass();

    $motioncount = $DB->count_records('uckkassembly_motion', [
        'assemblyid' => $assembly->id,
        'createdby' => $user->id,
    ]);

    $votecount = $DB->count_records('uckkassembly_vote', [
        'assemblyid' => $assembly->id,
        'userid' => $user->id,
    ]);

    $contestcount = $DB->count_records('uckkassembly_contest', [
        'assemblyid' => $assembly->id,
        'createdby' => $user->id,
    ]);

    $parts = [];

    if ($motioncount > 0) {
        $parts[] = get_string('outline:motions', 'uckkassembly', $motioncount);
    }

    if ($votecount > 0) {
        $parts[] = get_string('outline:votes', 'uckkassembly', $votecount);
    }

    if ($contestcount > 0) {
        $parts[] = get_string('outline:contestations', 'uckkassembly', $contestcount);
    }

    if (empty($parts)) {
        return null;
    }

    $info->info = implode(', ', $parts);
    $info->time = uckkassembly_get_user_last_activity_time((int)$assembly->id, (int)$user->id);

    return $info;
}

/**
 * Print complete user participation data for reports.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param cm_info|stdClass $mod Course module object.
 * @param stdClass $assembly Assembly instance.
 */
function uckkassembly_user_complete($course, $user, $mod, $assembly): void {
    global $DB, $OUTPUT;

    $motioncount = $DB->count_records('uckkassembly_motion', [
        'assemblyid' => $assembly->id,
        'createdby' => $user->id,
    ]);

    $votecount = $DB->count_records('uckkassembly_vote', [
        'assemblyid' => $assembly->id,
        'userid' => $user->id,
    ]);

    $contestcount = $DB->count_records('uckkassembly_contest', [
        'assemblyid' => $assembly->id,
        'createdby' => $user->id,
    ]);

    $data = [
        get_string('motions', 'uckkassembly') => $motioncount,
        get_string('votes', 'uckkassembly') => $votecount,
        get_string('contestations', 'uckkassembly') => $contestcount,
    ];

    echo html_writer::start_tag('dl', ['class' => 'uckkassembly-user-complete']);

    foreach ($data as $label => $value) {
        echo html_writer::tag('dt', s($label));
        echo html_writer::tag('dd', (string)$value);
    }

    echo html_writer::end_tag('dl');
}

/**
 * Return file areas used by this module.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 * @return array<string, string>
 */
function uckkassembly_get_file_areas($course, $cm, $context): array {
    return [
        'intro' => get_string('filearea:intro', 'uckkassembly'),
        'motion_attachments' => get_string('filearea:motionattachments', 'uckkassembly'),
        'amendment_attachments' => get_string('filearea:amendmentattachments', 'uckkassembly'),
        'decision_attachments' => get_string('filearea:decisionattachments', 'uckkassembly'),
        'minutes_files' => get_string('filearea:minutesfiles', 'uckkassembly'),
        'contest_attachments' => get_string('filearea:contestattachments', 'uckkassembly'),
    ];
}

/**
 * Serve files from UCKK Assembly file areas.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 * @param string $filearea File area.
 * @param array $args File path args.
 * @param bool $forcedownload Force download.
 * @param array $options File serving options.
 * @return bool
 */
function uckkassembly_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    $allowedareas = [
        'intro',
        'motion_attachments',
        'amendment_attachments',
        'decision_attachments',
        'minutes_files',
        'contest_attachments',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    if (!has_capability('mod/uckkassembly:view', $context)) {
        return false;
    }

    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);

    if (
        uckkassembly_filearea_is_restricted($filearea)
        && !has_capability('mod/uckkassembly:viewrestricted', $context)
    ) {
        return false;
    }

    $itemid = array_shift($args);

    if ($itemid === null || $itemid === '') {
        return false;
    }

    $itemid = (int)$itemid;
    $filename = array_pop($args);

    if ($filename === null) {
        return false;
    }

    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_uckkassembly', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    if (!uckkassembly_user_can_access_file_item($assembly, $filearea, $itemid, $context, $USER)) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add custom completion rules to the activity form.
 *
 * @return array
 */
function uckkassembly_get_completion_active_rule_descriptions($cm): array {
    global $DB;

    $descriptions = [];

    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance]);

    if (!$assembly) {
        return $descriptions;
    }

    if (!empty($assembly->completionrequiresmotion)) {
        $descriptions[] = get_string('completiondetail:motion', 'uckkassembly');
    }

    if (!empty($assembly->completionrequirevote)) {
        $descriptions[] = get_string('completiondetail:vote', 'uckkassembly');
    }

    if (!empty($assembly->completionrequiresdecision)) {
        $descriptions[] = get_string('completiondetail:decision', 'uckkassembly');
    }

    return $descriptions;
}

/**
 * Check custom completion state.
 *
 * @param stdClass $course Course.
 * @param cm_info|stdClass $cm Course module.
 * @param int $userid User id.
 * @param bool $type Completion type.
 * @return bool
 */
function uckkassembly_get_completion_state($course, $cm, $userid, $type): bool {
    global $DB;

    $assembly = $DB->get_record('uckkassembly', ['id' => $cm->instance], '*', MUST_EXIST);

    $conditions = [];
    $conditions[] = !empty($assembly->completionrequiresmotion);
    $conditions[] = !empty($assembly->completionrequirevote);
    $conditions[] = !empty($assembly->completionrequiresdecision);

    if (!in_array(true, $conditions, true)) {
        return $type;
    }

    if (!empty($assembly->completionrequiresmotion)) {
        $motionexists = $DB->record_exists('uckkassembly_motion', [
            'assemblyid' => $assembly->id,
            'createdby' => $userid,
        ]);

        if (!$motionexists) {
            return false;
        }
    }

    if (!empty($assembly->completionrequirevote)) {
        $voteexists = $DB->record_exists('uckkassembly_vote', [
            'assemblyid' => $assembly->id,
            'userid' => $userid,
        ]);

        if (!$voteexists) {
            return false;
        }
    }

    if (!empty($assembly->completionrequiresdecision)) {
        $decisionexists = $DB->record_exists_select(
            'uckkassembly_decision',
            'assemblyid = :assemblyid AND status IN (:published, :archived)',
            [
                'assemblyid' => $assembly->id,
                'published' => 'published',
                'archived' => 'archived',
            ]
        );

        if (!$decisionexists) {
            return false;
        }
    }

    return true;
}

/**
 * Reset user data for this module.
 *
 * @param stdClass $data Reset data.
 * @return array<int, array<string, string>>
 */
function uckkassembly_reset_userdata($data): array {
    global $DB;

    $status = [];

    if (empty($data->reset_uckkassembly)) {
        return $status;
    }

    $courseid = (int)$data->courseid;

    $assemblies = $DB->get_records('uckkassembly', ['course' => $courseid], '', 'id');

    foreach ($assemblies as $assembly) {
        $DB->delete_records('uckkassembly_contest', ['assemblyid' => $assembly->id]);
        $DB->delete_records('uckkassembly_vote', ['assemblyid' => $assembly->id]);
        $DB->delete_records('uckkassembly_object', ['assemblyid' => $assembly->id]);
        $DB->delete_records('uckkassembly_amend', ['assemblyid' => $assembly->id]);
        $DB->delete_records('uckkassembly_motion', ['assemblyid' => $assembly->id]);
    }

    $status[] = [
        'component' => get_string('modulenameplural', 'uckkassembly'),
        'item' => get_string('resetuserdata', 'uckkassembly'),
        'error' => false,
    ];

    return $status;
}

/**
 * Add reset form definition.
 *
 * @param MoodleQuickForm $mform Form.
 */
function uckkassembly_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'uckkassemblyheader', get_string('modulenameplural', 'uckkassembly'));
    $mform->addElement('checkbox', 'reset_uckkassembly', get_string('resetuserdata', 'uckkassembly'));
}

/**
 * Return reset form defaults.
 *
 * @param stdClass $course Course.
 * @return array<string, int>
 */
function uckkassembly_reset_course_form_defaults($course): array {
    return [
        'reset_uckkassembly' => 0,
    ];
}

/**
 * Return recent activity records.
 *
 * @param array $activities Activities.
 * @param int $index Current index.
 * @param int $timestart Start time.
 * @param int $courseid Course id.
 * @param int $cmid Course module id.
 * @param int $userid User id.
 * @param int $groupid Group id.
 * @return bool
 */
function uckkassembly_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0): bool {
    global $DB;

    $params = [
        'courseid' => $courseid,
        'cmid' => $cmid,
        'timestart' => $timestart,
    ];

    $userselect = '';
    if ($userid) {
        $userselect = 'AND m.createdby = :userid';
        $params['userid'] = $userid;
    }

    $sql = "
        SELECT
            m.id,
            m.assemblyid,
            m.title,
            m.status,
            m.timecreated,
            m.createdby,
            a.name AS assemblyname,
            cm.id AS cmid
          FROM {uckkassembly_motion} m
          JOIN {uckkassembly} a ON a.id = m.assemblyid
          JOIN {course_modules} cm ON cm.instance = a.id
          JOIN {modules} md ON md.id = cm.module AND md.name = 'uckkassembly'
         WHERE a.course = :courseid
           AND cm.id = :cmid
           AND m.timecreated > :timestart
           {$userselect}
      ORDER BY m.timecreated ASC";

    $motions = $DB->get_records_sql($sql, $params);

    foreach ($motions as $motion) {
        $activity = new stdClass();
        $activity->type = 'uckkassembly';
        $activity->cmid = $motion->cmid;
        $activity->name = format_string($motion->assemblyname);
        $activity->sectionnum = 0;
        $activity->timestamp = $motion->timecreated;
        $activity->content = new stdClass();
        $activity->content->motionid = $motion->id;
        $activity->content->title = format_string($motion->title);
        $activity->content->status = $motion->status;
        $activity->user = core_user::get_user($motion->createdby);

        $activities[$index++] = $activity;
    }

    return true;
}

/**
 * Print recent activity.
 *
 * @param stdClass $activity Activity.
 * @param int $courseid Course id.
 * @param bool $detail Whether to print detail.
 * @param array $modnames Module names.
 * @param bool $viewfullnames Whether full names may be viewed.
 */
function uckkassembly_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames): void {
    $url = new moodle_url('/mod/uckkassembly/view.php', ['id' => $activity->cmid]);

    echo html_writer::start_div('uckkassembly-recent-activity');
    echo html_writer::link($url, s($activity->content->title));
    echo html_writer::span(userdate($activity->timestamp), 'uckkassembly-recent-activity__time');
    echo html_writer::end_div();
}

/**
 * Return recent activity summary.
 *
 * @param stdClass $course Course.
 * @param bool $viewfullnames Whether full names may be viewed.
 * @param int $timestart Start timestamp.
 * @return bool
 */
function uckkassembly_print_recent_activity($course, $viewfullnames, $timestart): bool {
    global $DB, $OUTPUT;

    $count = $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {uckkassembly_motion} m
           JOIN {uckkassembly} a ON a.id = m.assemblyid
          WHERE a.course = :courseid
            AND m.timecreated > :timestart",
        [
            'courseid' => $course->id,
            'timestart' => $timestart,
        ]
    );

    if ($count <= 0) {
        return false;
    }

    echo $OUTPUT->heading(get_string('recentmotions', 'uckkassembly', $count), 6);

    return true;
}

/**
 * Return action names considered viewing actions.
 *
 * @return array
 */
function uckkassembly_get_view_actions(): array {
    return [
        'view',
        'view all',
        'view motion',
        'view decision',
        'view minutes',
    ];
}

/**
 * Return action names considered posting/write actions.
 *
 * @return array
 */
function uckkassembly_get_post_actions(): array {
    return [
        'add motion',
        'add amendment',
        'add objection',
        'vote',
        'publish decision',
        'contest decision',
        'publish minutes',
        'archive assembly',
    ];
}

/**
 * Normalise instance data before insert/update.
 *
 * @param stdClass $data Form data.
 * @param bool $isnew Whether this is a new instance.
 * @return stdClass
 */
function uckkassembly_normalise_instance_record(stdClass $data, bool $isnew): stdClass {
    $record = new stdClass();

    $record->course = (int)$data->course;
    $record->name = trim((string)$data->name);
    $record->intro = $data->intro ?? '';
    $record->introformat = (int)($data->introformat ?? FORMAT_HTML);

    $record->assemblytype = clean_param((string)($data->assemblytype ?? 'savoirs'), PARAM_ALPHANUMEXT);
    $record->status = clean_param((string)($data->status ?? 'draft'), PARAM_ALPHANUMEXT);
    $record->visibility = clean_param((string)($data->visibility ?? 'course'), PARAM_ALPHANUMEXT);

    $record->timeopen = (int)($data->timeopen ?? 0);
    $record->timeclose = (int)($data->timeclose ?? 0);
    $record->contestuntil = (int)($data->contestuntil ?? 0);

    $record->votingmethod = clean_param((string)($data->votingmethod ?? 'readings'), PARAM_ALPHANUMEXT);
    $record->quorum = max(0, (int)($data->quorum ?? 0));

    $record->allowmotions = empty($data->allowmotions) ? 0 : 1;
    $record->allowamendments = empty($data->allowamendments) ? 0 : 1;
    $record->allowobjections = empty($data->allowobjections) ? 0 : 1;
    $record->allowcontestations = empty($data->allowcontestations) ? 0 : 1;

    $record->minutesformat = clean_param((string)($data->minutesformat ?? 'structured'), PARAM_ALPHANUMEXT);
    $record->archivepolicy = clean_param((string)($data->archivepolicy ?? 'summary'), PARAM_ALPHANUMEXT);
    $record->provenance = clean_param((string)($data->provenance ?? 'human'), PARAM_ALPHANUMEXT);

    $record->completionrequiresmotion = empty($data->completionrequiresmotion) ? 0 : 1;
    $record->completionrequirevote = empty($data->completionrequirevote) ? 0 : 1;
    $record->completionrequiresdecision = empty($data->completionrequiresdecision) ? 0 : 1;

    $metadata = $data->metadata ?? null;
    if (is_array($metadata) || is_object($metadata)) {
        $record->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else if (is_string($metadata) && trim($metadata) !== '') {
        json_decode($metadata);
        $record->metadata = json_last_error() === JSON_ERROR_NONE ? $metadata : null;
    } else {
        $record->metadata = null;
    }

    return $record;
}

/**
 * Update calendar events for an assembly.
 *
 * @param stdClass $assembly Assembly form/instance data.
 */
function uckkassembly_update_calendar_events(stdClass $assembly): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $instanceid = (int)($assembly->id ?? $assembly->instance ?? 0);

    if ($instanceid <= 0) {
        return;
    }

    $DB->delete_records('event', [
        'modulename' => 'uckkassembly',
        'instance' => $instanceid,
    ]);

    $cmid = (int)($assembly->coursemodule ?? 0);

    if (!empty($assembly->timeopen)) {
        uckkassembly_create_calendar_event(
            $assembly,
            $cmid,
            (int)$assembly->timeopen,
            get_string('calendarevent:opens', 'uckkassembly', $assembly->name),
            'open'
        );
    }

    if (!empty($assembly->timeclose)) {
        uckkassembly_create_calendar_event(
            $assembly,
            $cmid,
            (int)$assembly->timeclose,
            get_string('calendarevent:closes', 'uckkassembly', $assembly->name),
            'close'
        );
    }

    if (!empty($assembly->contestuntil)) {
        uckkassembly_create_calendar_event(
            $assembly,
            $cmid,
            (int)$assembly->contestuntil,
            get_string('calendarevent:contestuntil', 'uckkassembly', $assembly->name),
            'contestuntil'
        );
    }
}

/**
 * Create one calendar event.
 *
 * @param stdClass $assembly Assembly data.
 * @param int $cmid Course module id.
 * @param int $time Event time.
 * @param string $name Event name.
 * @param string $eventtype Event type suffix.
 */
function uckkassembly_create_calendar_event(stdClass $assembly, int $cmid, int $time, string $name, string $eventtype): void {
    global $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $event = new stdClass();
    $event->name = $name;
    $event->description = format_module_intro('uckkassembly', $assembly, $cmid, false);
    $event->format = FORMAT_HTML;
    $event->courseid = (int)$assembly->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'uckkassembly';
    $event->instance = (int)$assembly->id;
    $event->type = CALENDAR_EVENT_TYPE_ACTION;
    $event->eventtype = 'uckkassembly_' . $eventtype;
    $event->timestart = $time;
    $event->timeduration = 0;
    $event->visible = instance_is_visible('uckkassembly', $assembly);

    calendar_event::create($event, false);
}

/**
 * Update completion rules metadata after instance save.
 *
 * @param stdClass $assembly Assembly data.
 */
function uckkassembly_update_completion_rules(stdClass $assembly): void {
    if (empty($assembly->coursemodule) || empty($assembly->course)) {
        return;
    }

    $course = get_course((int)$assembly->course);
    $cm = get_coursemodule_from_id('uckkassembly', (int)$assembly->coursemodule, (int)$assembly->course, false, IGNORE_MISSING);

    if (!$cm) {
        return;
    }

    $completion = new completion_info($course);

    if ($completion->is_enabled($cm)) {
        $completion->reset_all_state($cm);
    }
}

/**
 * Return whether a file area is restricted.
 *
 * @param string $filearea File area.
 * @return bool
 */
function uckkassembly_filearea_is_restricted(string $filearea): bool {
    return in_array($filearea, [
        'contest_attachments',
    ], true);
}

/**
 * Check whether the current user can access one file item.
 *
 * @param stdClass $assembly Assembly record.
 * @param string $filearea File area.
 * @param int $itemid Item id.
 * @param context_module $context Context.
 * @param stdClass $user User.
 * @return bool
 */
function uckkassembly_user_can_access_file_item(
    stdClass $assembly,
    string $filearea,
    int $itemid,
    context_module $context,
    stdClass $user
): bool {
    global $DB;

    if (has_capability('mod/uckkassembly:publishdecision', $context)) {
        return true;
    }

    if ($filearea === 'intro') {
        return true;
    }

    $tablemap = [
        'motion_attachments' => 'uckkassembly_motion',
        'amendment_attachments' => 'uckkassembly_amend',
        'decision_attachments' => 'uckkassembly_decision',
        'minutes_files' => 'uckkassembly_minutes',
        'contest_attachments' => 'uckkassembly_contest',
    ];

    if (empty($tablemap[$filearea])) {
        return false;
    }

    $record = $DB->get_record($tablemap[$filearea], [
        'id' => $itemid,
        'assemblyid' => $assembly->id,
    ]);

    if (!$record) {
        return false;
    }

    if (!empty($record->visibility) && $record->visibility === 'restricted_integrity') {
        return has_capability('mod/uckkassembly:viewrestricted', $context);
    }

    if (!empty($record->createdby) && (int)$record->createdby === (int)$user->id) {
        return true;
    }

    return empty($record->visibility)
        || in_array($record->visibility, ['course', 'group', 'cohort', 'program', 'institution', 'public'], true);
}

/**
 * Get the latest user activity timestamp for this assembly.
 *
 * @param int $assemblyid Assembly id.
 * @param int $userid User id.
 * @return int
 */
function uckkassembly_get_user_last_activity_time(int $assemblyid, int $userid): int {
    global $DB;

    $times = [];

    foreach ([
        'uckkassembly_motion' => 'createdby',
        'uckkassembly_amend' => 'createdby',
        'uckkassembly_object' => 'createdby',
        'uckkassembly_vote' => 'userid',
        'uckkassembly_contest' => 'createdby',
    ] as $table => $userfield) {
        $time = $DB->get_field_sql(
            "SELECT MAX(timemodified)
               FROM {{$table}}
              WHERE assemblyid = :assemblyid
                AND {$userfield} = :userid",
            [
                'assemblyid' => $assemblyid,
                'userid' => $userid,
            ]
        );

        if ($time) {
            $times[] = (int)$time;
        }
    }

    return empty($times) ? 0 : max($times);
}