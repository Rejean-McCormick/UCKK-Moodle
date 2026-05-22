<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Library callbacks for the UCKK Archive activity module.
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
 * Main instance table.
 */
define('UCKKARCHIVE_TABLE', 'uckkarchive');

/**
 * Common statuses.
 */
define('UCKKARCHIVE_STATUS_DRAFT', 'draft');
define('UCKKARCHIVE_STATUS_ACTIVE', 'active');
define('UCKKARCHIVE_STATUS_PENDING_REVIEW', 'pending_review');
define('UCKKARCHIVE_STATUS_VALIDATED', 'validated');
define('UCKKARCHIVE_STATUS_REJECTED', 'rejected');
define('UCKKARCHIVE_STATUS_CORRECTION_REQUIRED', 'correction_required');
define('UCKKARCHIVE_STATUS_CONTESTED', 'contested');
define('UCKKARCHIVE_STATUS_INVALIDATED', 'invalidated');
define('UCKKARCHIVE_STATUS_CLOSED', 'closed');
define('UCKKARCHIVE_STATUS_ARCHIVED', 'archived');

/**
 * Common visibilities.
 */
define('UCKKARCHIVE_VISIBILITY_PRIVATE', 'private');
define('UCKKARCHIVE_VISIBILITY_USER', 'user');
define('UCKKARCHIVE_VISIBILITY_GROUP', 'group');
define('UCKKARCHIVE_VISIBILITY_COURSE', 'course');
define('UCKKARCHIVE_VISIBILITY_COHORT', 'cohort');
define('UCKKARCHIVE_VISIBILITY_PROGRAM', 'program');
define('UCKKARCHIVE_VISIBILITY_INSTITUTION', 'institution');
define('UCKKARCHIVE_VISIBILITY_PUBLIC', 'public');
define('UCKKARCHIVE_VISIBILITY_RESTRICTED', 'restricted');
define('UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY', 'restricted_integrity');
define('UCKKARCHIVE_VISIBILITY_HIDDEN', 'hidden');
define('UCKKARCHIVE_VISIBILITY_ARCHIVED', 'archived');

/**
 * Required archive file areas.
 */
define('UCKKARCHIVE_FILEAREA_PROOF_FILES', 'proof_files');
define('UCKKARCHIVE_FILEAREA_DECISION_ATTACHMENTS', 'decision_attachments');
define('UCKKARCHIVE_FILEAREA_MINUTES_FILES', 'minutes_files');
define('UCKKARCHIVE_FILEAREA_KRISTAL_FILES', 'kristal_files');
define('UCKKARCHIVE_FILEAREA_PORTFOLIO_FILES', 'portfolio_files');
define('UCKKARCHIVE_FILEAREA_INTEGRITY_EXPORTS', 'integrity_exports');

/**
 * Return supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return mixed
 */
function uckkarchive_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_RATE:
        case FEATURE_PLAGIARISM:
            return false;

        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;

        default:
            return null;
    }
}

/**
 * Add a new UCKK archive instance.
 *
 * @param stdClass $archive Submitted module data.
 * @param mod_uckkarchive_mod_form|null $mform Module form.
 * @return int New instance id.
 */
function uckkarchive_add_instance(stdClass $archive, $mform = null): int {
    global $DB, $USER;

    $now = time();

    $archive->timecreated = $now;
    $archive->timemodified = $now;
    $archive->createdby = (int)$USER->id;
    $archive->modifiedby = (int)$USER->id;

    $record = uckkarchive_prepare_instance_record($archive, true);
    $record->id = $DB->insert_record(UCKKARCHIVE_TABLE, $record);

    $archive->id = (int)$record->id;
    $archive->completionrequireitem = (int)$record->completionrequireitem;
    $archive->completionrequirevalidation = (int)$record->completionrequirevalidation;

    uckkarchive_update_calendar_events($archive);
    uckkarchive_update_completion_metadata($archive);

    return (int)$archive->id;
}

/**
 * Update an existing UCKK archive instance.
 *
 * @param stdClass $archive Submitted module data.
 * @param mod_uckkarchive_mod_form|null $mform Module form.
 * @return bool
 */
function uckkarchive_update_instance(stdClass $archive, $mform = null): bool {
    global $DB, $USER;

    $archive->id = (int)$archive->instance;
    $archive->timemodified = time();
    $archive->modifiedby = (int)$USER->id;

    $record = uckkarchive_prepare_instance_record($archive, false);
    $result = $DB->update_record(UCKKARCHIVE_TABLE, $record);

    $archive->completionrequireitem = (int)$record->completionrequireitem;
    $archive->completionrequirevalidation = (int)$record->completionrequirevalidation;

    uckkarchive_update_calendar_events($archive);
    uckkarchive_update_completion_metadata($archive);

    return $result;
}

/**
 * Delete an archive instance and owned archive records.
 *
 * This removes records owned by this activity instance. Cross-plugin source
 * records remain owned by their original components.
 *
 * @param int $id Archive instance id.
 * @return bool
 */
function uckkarchive_delete_instance($id): bool {
    global $DB;

    $id = (int)$id;

    if (!$archive = $DB->get_record(UCKKARCHIVE_TABLE, ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('uckkarchive', $id, $archive->course ?? 0, false, IGNORE_MISSING);

    if ($cm) {
        $context = context_module::instance($cm->id, IGNORE_MISSING);

        if ($context) {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, UCKKARCHIVE_COMPONENT);
        }
    }

    foreach ([
        'uckkarchive_export',
        'uckkarchive_rev',
        'uckkarchive_prov',
        'uckkarchive_proof',
        'uckkarchive_kristal',
        'uckkarchive_item',
    ] as $table) {
        $DB->delete_records($table, ['archiveid' => $id]);
    }

    $DB->delete_records('event', [
        'modulename' => 'uckkarchive',
        'instance' => $id,
    ]);

    $DB->delete_records(UCKKARCHIVE_TABLE, ['id' => $id]);

    return true;
}

/**
 * Return a brief user activity outline for participation reports.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param cm_info|stdClass $mod Course module.
 * @param stdClass $archive Archive instance.
 * @return stdClass|null
 */
function uckkarchive_user_outline($course, $user, $mod, $archive): ?stdClass {
    global $DB;

    $archiveid = (int)$archive->id;
    $userid = (int)$user->id;

    $itemcount = $DB->count_records_select(
        'uckkarchive_item',
        'archiveid = :archiveid AND (userid = :userid OR createdby = :createdby)',
        [
            'archiveid' => $archiveid,
            'userid' => $userid,
            'createdby' => $userid,
        ]
    );

    $proofcount = $DB->count_records_select(
        'uckkarchive_proof',
        'archiveid = :archiveid AND (userid = :userid OR createdby = :createdby)',
        [
            'archiveid' => $archiveid,
            'userid' => $userid,
            'createdby' => $userid,
        ]
    );

    $lastactivity = uckkarchive_get_user_last_activity_time($archiveid, $userid);

    if ($itemcount === 0 && $proofcount === 0 && $lastactivity === 0) {
        return null;
    }

    $result = new stdClass();
    $result->info = get_string('useroutline', 'uckkarchive', [
        'items' => $itemcount,
        'proofs' => $proofcount,
    ]);
    $result->time = $lastactivity;

    return $result;
}

/**
 * Print detailed user activity for participation reports.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param cm_info|stdClass $mod Course module.
 * @param stdClass $archive Archive instance.
 */
function uckkarchive_user_complete($course, $user, $mod, $archive): void {
    global $DB, $OUTPUT;

    $archiveid = (int)$archive->id;
    $userid = (int)$user->id;

    $items = $DB->get_records_select(
        'uckkarchive_item',
        'archiveid = :archiveid AND (userid = :userid OR createdby = :createdby)',
        [
            'archiveid' => $archiveid,
            'userid' => $userid,
            'createdby' => $userid,
        ],
        'timemodified DESC',
        'id, title, status, visibility, timemodified',
        0,
        10
    );

    if (!$items) {
        echo $OUTPUT->notification(get_string('nouserarchiveitems', 'uckkarchive'), 'info');
        return;
    }

    echo html_writer::start_tag('ul', ['class' => 'uckkarchive-user-complete']);

    foreach ($items as $item) {
        $label = format_string($item->title ?? get_string('archiveitem', 'uckkarchive'));

        if (!empty($item->status)) {
            $label .= ' — ' . s($item->status);
        }

        if (!empty($item->timemodified)) {
            $label .= ' — ' . userdate((int)$item->timemodified);
        }

        echo html_writer::tag('li', $label);
    }

    echo html_writer::end_tag('ul');
}

/**
 * Return cached course-module information.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|null
 */
function uckkarchive_get_coursemodule_info(stdClass $coursemodule): ?cached_cm_info {
    global $DB;

    $archive = $DB->get_record(
        UCKKARCHIVE_TABLE,
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat, status, visibility, defaultvisibility, archivepolicy, timemodified',
        IGNORE_MISSING
    );

    if (!$archive) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = format_string($archive->name);

    if (!empty($archive->intro)) {
        $info->content = format_module_intro('uckkarchive', $archive, $coursemodule->id, false);
    }

    $customdata = [
        'status' => $archive->status ?? UCKKARCHIVE_STATUS_ACTIVE,
        'visibility' => $archive->visibility ?? UCKKARCHIVE_VISIBILITY_COURSE,
        'defaultvisibility' => $archive->defaultvisibility ?? UCKKARCHIVE_VISIBILITY_COURSE,
        'archivepolicy' => $archive->archivepolicy ?? 'validated',
        'timemodified' => (int)($archive->timemodified ?? 0),
    ];

    $info->customdata = $customdata;

    return $info;
}

/**
 * Serve files from the UCKK archive file areas.
 *
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module.
 * @param context $context Module context.
 * @param string $filearea File area.
 * @param array $args Path arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional send options.
 * @return bool
 */
function uckkarchive_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    require_capability('mod/uckkarchive:view', $context);

    $validfileareas = uckkarchive_get_fileareas();

    if ($filearea === 'intro') {
        $itemid = array_shift($args);

        if ((int)$itemid !== 0) {
            return false;
        }
    } else {
        if (!in_array($filearea, $validfileareas, true)) {
            return false;
        }

        if (empty($args)) {
            return false;
        }

        $itemid = (int)array_shift($args);

        if (!uckkarchive_can_view_filearea_item($cm, $context, $filearea, $itemid)) {
            return false;
        }
    }

    if (empty($args)) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    if ($filepath === '//') {
        $filepath = '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        UCKKARCHIVE_COMPONENT,
        $filearea,
        (int)$itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Return custom completion state.
 *
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module.
 * @param int $userid User id.
 * @param int $type Completion aggregation type.
 * @return bool
 */
function uckkarchive_get_completion_state($course, $cm, $userid, $type): bool {
    global $DB;

    $archive = $DB->get_record(
        UCKKARCHIVE_TABLE,
        ['id' => $cm->instance],
        'id, completionrequireitem, completionrequirevalidation',
        IGNORE_MISSING
    );

    if (!$archive) {
        return (bool)$type;
    }

    $conditions = [];

    if (!empty($archive->completionrequireitem)) {
        $conditions[] = $DB->record_exists_select(
            'uckkarchive_item',
            'archiveid = :archiveid AND (userid = :userid OR createdby = :createdby)',
            [
                'archiveid' => $archive->id,
                'userid' => $userid,
                'createdby' => $userid,
            ]
        );
    }

    if (!empty($archive->completionrequirevalidation)) {
        $conditions[] = $DB->record_exists_select(
            'uckkarchive_item',
            'archiveid = :archiveid
                 AND status = :status
                 AND (userid = :userid OR createdby = :createdby)',
            [
                'archiveid' => $archive->id,
                'status' => UCKKARCHIVE_STATUS_VALIDATED,
                'userid' => $userid,
                'createdby' => $userid,
            ]
        );
    }

    if (empty($conditions)) {
        return (bool)$type;
    }

    if ($type === COMPLETION_AND) {
        return !in_array(false, $conditions, true);
    }

    return in_array(true, $conditions, true);
}

/**
 * Reset course user data.
 *
 * Archive activities are institutional memory by default. User-owned records
 * are not removed during course reset unless future reset settings explicitly
 * request destructive action.
 *
 * @param stdClass $data Reset data.
 * @return array<int, array<string, mixed>>
 */
function uckkarchive_reset_userdata(stdClass $data): array {
    return [
        [
            'component' => get_string('modulenameplural', 'uckkarchive'),
            'item' => get_string('resetarchivespreserved', 'uckkarchive'),
            'error' => false,
        ],
    ];
}

/**
 * Refresh calendar events for all or one course.
 *
 * @param int $courseid Course id or 0 for all courses.
 * @return bool
 */
function uckkarchive_refresh_events($courseid = 0): bool {
    global $DB;

    $params = [];
    $coursesql = '';

    if (!empty($courseid)) {
        $coursesql = 'WHERE course = :courseid';
        $params['courseid'] = $courseid;
    }

    $archives = $DB->get_records_sql(
        "SELECT *
           FROM {uckkarchive}
           {$coursesql}",
        $params
    );

    foreach ($archives as $archive) {
        uckkarchive_update_calendar_events($archive);
    }

    return true;
}

/**
 * Extend module navigation.
 *
 * @param navigation_node $navref Navigation node.
 * @param stdClass $course Course record.
 * @param stdClass $module Course module record.
 * @param cm_info $cm Course module info.
 */
function uckkarchive_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm): void {
    $context = context_module::instance($cm->id);

    if (has_capability('mod/uckkarchive:additem', $context)) {
        $navref->add(
            get_string('addarchiveitem', 'uckkarchive'),
            new moodle_url('/mod/uckkarchive/add.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'uckkarchive_add_item',
            new pix_icon('t/add', '')
        );
    }

    if (has_capability('mod/uckkarchive:validateitem', $context)) {
        $navref->add(
            get_string('validatearchiveitems', 'uckkarchive'),
            new moodle_url('/mod/uckkarchive/validate.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'uckkarchive_validate_items',
            new pix_icon('i/checked', '')
        );
    }

    if (has_capability('mod/uckkarchive:export', $context)) {
        $navref->add(
            get_string('exportarchive', 'uckkarchive'),
            new moodle_url('/mod/uckkarchive/export.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'uckkarchive_export',
            new pix_icon('t/download', '')
        );
    }
}

/**
 * Return the latest user activity timestamp for one archive.
 *
 * @param int $archiveid Archive instance id.
 * @param int $userid User id.
 * @return int
 */
function uckkarchive_get_user_last_activity_time(int $archiveid, int $userid): int {
    global $DB;

    $times = [];

    foreach ([
        'uckkarchive_item' => 'createdby',
        'uckkarchive_kristal' => 'createdby',
        'uckkarchive_proof' => 'createdby',
        'uckkarchive_prov' => 'createdby',
        'uckkarchive_rev' => 'createdby',
        'uckkarchive_export' => 'createdby',
    ] as $table => $userfield) {
        $time = $DB->get_field_sql(
            "SELECT MAX(timemodified)
               FROM {{$table}}
              WHERE archiveid = :archiveid
                AND {$userfield} = :userid",
            [
                'archiveid' => $archiveid,
                'userid' => $userid,
            ]
        );

        if ($time) {
            $times[] = (int)$time;
        }
    }

    return empty($times) ? 0 : max($times);
}

/**
 * Update calendar events for an archive instance.
 *
 * @param stdClass $archive Archive data.
 */
function uckkarchive_update_calendar_events(stdClass $archive): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $instanceid = (int)($archive->id ?? $archive->instance ?? 0);

    if ($instanceid <= 0) {
        return;
    }

    $DB->delete_records('event', [
        'modulename' => 'uckkarchive',
        'instance' => $instanceid,
    ]);
}

/**
 * Create one calendar event.
 *
 * @param stdClass $archive Archive data.
 * @param int $cmid Course module id.
 * @param int $time Event timestamp.
 * @param string $name Event name.
 * @param string $eventtype Event type suffix.
 */
function uckkarchive_create_calendar_event(stdClass $archive, int $cmid, int $time, string $name, string $eventtype): void {
    global $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $event = new stdClass();
    $event->name = $name;
    $event->description = format_module_intro('uckkarchive', $archive, $cmid, false);
    $event->format = FORMAT_HTML;
    $event->courseid = (int)$archive->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'uckkarchive';
    $event->instance = (int)$archive->id;
    $event->type = CALENDAR_EVENT_TYPE_ACTION;
    $event->eventtype = 'uckkarchive_' . $eventtype;
    $event->timestart = $time;
    $event->timeduration = 0;
    $event->visible = instance_is_visible('uckkarchive', $archive);

    calendar_event::create($event, false);
}

/**
 * Update completion-related metadata after instance save.
 *
 * @param stdClass $archive Archive data.
 */
function uckkarchive_update_completion_metadata(stdClass $archive): void {
    global $DB;

    if (empty($archive->id)) {
        return;
    }

    $record = new stdClass();
    $record->id = (int)$archive->id;
    $record->timemodified = time();

    if (isset($archive->completionrequireitem)) {
        $record->completionrequireitem = empty($archive->completionrequireitem) ? 0 : 1;
    }

    if (isset($archive->completionrequirevalidation)) {
        $record->completionrequirevalidation = empty($archive->completionrequirevalidation) ? 0 : 1;
    }

    $DB->update_record(UCKKARCHIVE_TABLE, $record);
}

/**
 * Return supported archive file areas.
 *
 * @return string[]
 */
function uckkarchive_get_fileareas(): array {
    return [
        UCKKARCHIVE_FILEAREA_PROOF_FILES,
        UCKKARCHIVE_FILEAREA_DECISION_ATTACHMENTS,
        UCKKARCHIVE_FILEAREA_MINUTES_FILES,
        UCKKARCHIVE_FILEAREA_KRISTAL_FILES,
        UCKKARCHIVE_FILEAREA_PORTFOLIO_FILES,
        UCKKARCHIVE_FILEAREA_INTEGRITY_EXPORTS,
    ];
}

/**
 * Return whether the current user can view a file-area record.
 *
 * @param cm_info|stdClass $cm Course module.
 * @param context $context Module context.
 * @param string $filearea File area.
 * @param int $itemid File item id.
 * @return bool
 */
function uckkarchive_can_view_filearea_item($cm, context $context, string $filearea, int $itemid): bool {
    global $DB, $USER;

    if ($itemid <= 0) {
        return false;
    }

    $table = uckkarchive_get_filearea_table($filearea);

    if ($table === '') {
        return false;
    }

    $record = $DB->get_record($table, ['id' => $itemid], '*', IGNORE_MISSING);

    if (!$record) {
        return false;
    }

    $archiveid = (int)($record->archiveid ?? 0);

    if ($archiveid <= 0 || $archiveid !== (int)$cm->instance) {
        return false;
    }

    $visibility = uckkarchive_normalise_visibility($record->visibility ?? UCKKARCHIVE_VISIBILITY_COURSE);

    if (in_array($visibility, [
        UCKKARCHIVE_VISIBILITY_RESTRICTED,
        UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY,
    ], true)) {
        return has_capability('mod/uckkarchive:viewrestricted', $context);
    }

    if (in_array($visibility, [
        UCKKARCHIVE_VISIBILITY_HIDDEN,
        UCKKARCHIVE_VISIBILITY_ARCHIVED,
    ], true)) {
        return has_capability('mod/uckkarchive:viewrestricted', $context);
    }

    if (!empty($record->userid) && (int)$record->userid === (int)$USER->id) {
        return true;
    }

    if (!empty($record->createdby) && (int)$record->createdby === (int)$USER->id) {
        return true;
    }

    return in_array($visibility, [
        UCKKARCHIVE_VISIBILITY_COURSE,
        UCKKARCHIVE_VISIBILITY_GROUP,
        UCKKARCHIVE_VISIBILITY_COHORT,
        UCKKARCHIVE_VISIBILITY_PROGRAM,
        UCKKARCHIVE_VISIBILITY_INSTITUTION,
        UCKKARCHIVE_VISIBILITY_PUBLIC,
    ], true);
}

/**
 * Map file area to owner table.
 *
 * @param string $filearea File area.
 * @return string
 */
function uckkarchive_get_filearea_table(string $filearea): string {
    return match ($filearea) {
        UCKKARCHIVE_FILEAREA_PROOF_FILES => 'uckkarchive_proof',
        UCKKARCHIVE_FILEAREA_KRISTAL_FILES => 'uckkarchive_kristal',
        UCKKARCHIVE_FILEAREA_INTEGRITY_EXPORTS => 'uckkarchive_export',
        UCKKARCHIVE_FILEAREA_DECISION_ATTACHMENTS,
        UCKKARCHIVE_FILEAREA_MINUTES_FILES,
        UCKKARCHIVE_FILEAREA_PORTFOLIO_FILES => 'uckkarchive_item',
        default => '',
    };
}


/**
 * Build a database-safe archive instance record.
 *
 * This function is deliberately schema-owned. Moodle form/moduleinfo objects may
 * contain Moodle form/moduleinfo objects may contain fields that are not columns
 * in the uckkarchive table; they must not be passed directly to
 * insert_record()/update_record().
 *
 * @param stdClass $data Raw module data.
 * @param bool $isnew Whether this is a new instance.
 * @return stdClass Database-safe instance record.
 */
function uckkarchive_prepare_instance_record(stdClass $data, bool $isnew): stdClass {
    global $USER, $DB;

    $record = new stdClass();

    if (!$isnew) {
        $record->id = (int)($data->id ?? $data->instance ?? 0);
    }

    $record->course = (int)($data->course ?? 0);
    $record->name = trim((string)($data->name ?? ''));
    $record->intro = $data->intro ?? '';
    $record->introformat = (int)($data->introformat ?? FORMAT_HTML);

    $record->archivecode = uckkarchive_optional_text($data->archivecode ?? null, 100);
    $record->archivetype = clean_param((string)($data->archivetype ?? 'course'), PARAM_ALPHANUMEXT);
    $record->archivepolicy = clean_param((string)($data->archivepolicy ?? 'validated'), PARAM_ALPHANUMEXT);

    $visibility = uckkarchive_normalise_visibility($data->visibility ?? UCKKARCHIVE_VISIBILITY_COURSE);
    $record->visibility = $visibility;
    $record->defaultvisibility = uckkarchive_normalise_visibility($data->defaultvisibility ?? $visibility);

    $record->requirevalidation = empty($data->requirevalidation) ? 0 : 1;
    $record->allowpublicitems = empty($data->allowpublicitems) ? 0 : 1;
    $record->allowexports = isset($data->allowexports) ? (empty($data->allowexports) ? 0 : 1) : 1;

    $record->completionrequireitem = empty($data->completionrequireitem ?? 0) ? 0 : 1;
    $record->completionrequirevalidation = empty($data->completionrequirevalidation ?? 0) ? 0 : 1;

    $record->status = uckkarchive_normalise_status($data->status ?? UCKKARCHIVE_STATUS_ACTIVE);
    $record->versionno = max(1, (int)($data->versionno ?? 1));
    $record->metadata = uckkarchive_normalise_metadata($data->metadata ?? null);

    $record->timemodified = (int)($data->timemodified ?? time());
    $record->modifiedby = (int)($data->modifiedby ?? $USER->id ?? 0);

    if ($isnew) {
        $record->timecreated = (int)($data->timecreated ?? time());
        $record->createdby = (int)($data->createdby ?? $USER->id ?? 0);
    } else if (!empty($record->id)) {
        $current = $DB->get_record(UCKKARCHIVE_TABLE, ['id' => $record->id], 'id, timecreated, createdby, versionno', IGNORE_MISSING);

        if ($current) {
            $record->timecreated = (int)$current->timecreated;
            $record->createdby = (int)$current->createdby;
            $record->versionno = max((int)$current->versionno, $record->versionno);
        }
    }

    return $record;
}



/**
 * Return a clipped optional text value or null.
 *
 * @param mixed $value Raw value.
 * @param int $maxlength Maximum length.
 * @return string|null
 */
function uckkarchive_optional_text(mixed $value, int $maxlength): ?string {
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    return core_text::substr(clean_param($value, PARAM_TEXT), 0, $maxlength);
}

/**
 * Normalise archive status.
 *
 * @param string|null $status Raw status.
 * @return string
 */
function uckkarchive_normalise_status(?string $status): string {
    $status = clean_param((string)$status, PARAM_ALPHANUMEXT);

    $allowed = [
        UCKKARCHIVE_STATUS_DRAFT,
        UCKKARCHIVE_STATUS_ACTIVE,
        UCKKARCHIVE_STATUS_PENDING_REVIEW,
        UCKKARCHIVE_STATUS_VALIDATED,
        UCKKARCHIVE_STATUS_REJECTED,
        UCKKARCHIVE_STATUS_CORRECTION_REQUIRED,
        UCKKARCHIVE_STATUS_CONTESTED,
        UCKKARCHIVE_STATUS_INVALIDATED,
        UCKKARCHIVE_STATUS_CLOSED,
        UCKKARCHIVE_STATUS_ARCHIVED,
    ];

    return in_array($status, $allowed, true) ? $status : UCKKARCHIVE_STATUS_ACTIVE;
}

/**
 * Normalise archive visibility.
 *
 * @param string|null $visibility Raw visibility.
 * @return string
 */
function uckkarchive_normalise_visibility(?string $visibility): string {
    $visibility = clean_param((string)$visibility, PARAM_ALPHANUMEXT);

    $allowed = [
        UCKKARCHIVE_VISIBILITY_PRIVATE,
        UCKKARCHIVE_VISIBILITY_USER,
        UCKKARCHIVE_VISIBILITY_GROUP,
        UCKKARCHIVE_VISIBILITY_COURSE,
        UCKKARCHIVE_VISIBILITY_COHORT,
        UCKKARCHIVE_VISIBILITY_PROGRAM,
        UCKKARCHIVE_VISIBILITY_INSTITUTION,
        UCKKARCHIVE_VISIBILITY_PUBLIC,
        UCKKARCHIVE_VISIBILITY_RESTRICTED,
        UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY,
        UCKKARCHIVE_VISIBILITY_HIDDEN,
        UCKKARCHIVE_VISIBILITY_ARCHIVED,
    ];

    return in_array($visibility, $allowed, true) ? $visibility : UCKKARCHIVE_VISIBILITY_COURSE;
}

/**
 * Normalise metadata to JSON string or null.
 *
 * @param mixed $metadata Metadata value.
 * @return string|null
 */
function uckkarchive_normalise_metadata(mixed $metadata): ?string {
    if ($metadata === null || $metadata === '') {
        return null;
    }

    if (is_string($metadata)) {
        $decoded = json_decode($metadata, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    if ($metadata instanceof stdClass) {
        $metadata = (array)$metadata;
    }

    if (is_array($metadata)) {
        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return null;
}