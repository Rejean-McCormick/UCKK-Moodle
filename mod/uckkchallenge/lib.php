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
 * Library callbacks for the UCKK challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_completion\api as completion_api;

/**
 * Return supported Moodle activity features.
 *
 * @param string $feature Feature constant.
 * @return mixed True, false, null, or feature-specific value.
 */
function uckkchallenge_supports(string $feature): mixed {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
            return true;

        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;

        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return true;

        default:
            if (
                defined('FEATURE_MOD_PURPOSE')
                && defined('MOD_PURPOSE_ASSESSMENT')
                && $feature === FEATURE_MOD_PURPOSE
            ) {
                return MOD_PURPOSE_ASSESSMENT;
            }

            return null;
    }
}

/**
 * Add a new UCKK challenge instance.
 *
 * @param stdClass $instancedata Activity form data.
 * @param moodleform|null $mform Form instance.
 * @return int New instance id.
 */
function uckkchallenge_add_instance(stdClass $instancedata, ?moodleform $mform = null): int {
    global $DB, $USER;

    $now = time();

    $record = uckkchallenge_normalise_instance_record($instancedata);
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->createdby = (int)$USER->id;
    $record->modifiedby = (int)$USER->id;
    $record->versionno = 1;

    $id = $DB->insert_record('uckkchallenge', $record);

    $record->id = $id;

    if (!empty($instancedata->coursemodule)) {
        $DB->set_field('course_modules', 'instance', $id, ['id' => $instancedata->coursemodule]);
    }

    uckkchallenge_save_related_records($record, $instancedata);
    uckkchallenge_grade_item_update($record);

    if (!empty($instancedata->completionexpected)) {
        \core_completion\api::update_completion_date_event(
            (int)$instancedata->coursemodule,
            'uckkchallenge',
            $id,
            (int)$instancedata->completionexpected
        );
    }

    uckkchallenge_trigger_event_if_available(
        '\mod_uckkchallenge\event\challenge_created',
        $record,
        $instancedata
    );

    return $id;
}

/**
 * Update an existing UCKK challenge instance.
 *
 * @param stdClass $instancedata Activity form data.
 * @param moodleform|null $mform Form instance.
 * @return bool
 */
function uckkchallenge_update_instance(stdClass $instancedata, ?moodleform $mform = null): bool {
    global $DB, $USER;

    $instancedata->id = (int)$instancedata->instance;

    $existing = $DB->get_record('uckkchallenge', ['id' => $instancedata->id], '*', MUST_EXIST);

    $record = uckkchallenge_normalise_instance_record($instancedata);
    $record->id = (int)$instancedata->id;
    $record->timecreated = (int)$existing->timecreated;
    $record->createdby = (int)$existing->createdby;
    $record->timemodified = time();
    $record->modifiedby = (int)$USER->id;
    $record->versionno = ((int)$existing->versionno) + 1;

    $DB->update_record('uckkchallenge', $record);

    uckkchallenge_save_related_records($record, $instancedata);
    uckkchallenge_grade_item_update($record);

    if (!empty($instancedata->completionexpected)) {
        \core_completion\api::update_completion_date_event(
            (int)$instancedata->coursemodule,
            'uckkchallenge',
            (int)$record->id,
            (int)$instancedata->completionexpected
        );
    }

    uckkchallenge_trigger_event_if_available(
        '\mod_uckkchallenge\event\challenge_updated',
        $record,
        $instancedata
    );

    return true;
}

/**
 * Delete an existing UCKK challenge instance and all owned child records.
 *
 * @param int $id Challenge instance id.
 * @return bool
 */
function uckkchallenge_delete_instance(int $id): bool {
    global $DB;

    if (!$challenge = $DB->get_record('uckkchallenge', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('uckkchallenge', $id, (int)$challenge->course, false, IGNORE_MISSING);

    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records('uckkchallenge_state', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge_eval', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge_proof', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge_sub', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge_corr', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge_rule', ['challengeid' => $id]);
    $DB->delete_records('uckkchallenge', ['id' => $id]);

    uckkchallenge_grade_item_delete($challenge);

    if ($cm) {
        \core_completion\api::update_completion_date_event((int)$cm->id, 'uckkchallenge', $id, null);
    }

    $transaction->allow_commit();

    uckkchallenge_trigger_event_if_available(
        '\mod_uckkchallenge\event\challenge_deleted',
        $challenge,
        (object)['coursemodule' => $cm ? (int)$cm->id : 0]
    );

    return true;
}

/**
 * Return course module information for course listings.
 *
 * @param cm_info|stdClass $cm Course module.
 * @return cached_cm_info|null
 */
function uckkchallenge_get_coursemodule_info(cm_info|stdClass $cm): ?cached_cm_info {
    global $DB;

    $challenge = $DB->get_record(
        'uckkchallenge',
        ['id' => $cm->instance],
        'id, name, intro, introformat, status, challenge_type, duedate, timemodified'
    );

    if (!$challenge) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = format_string($challenge->name, true);

    if (!empty($challenge->intro)) {
        $info->content = format_module_intro('uckkchallenge', $challenge, $cm->id, false);
    }

    $customdata = [
        'status' => $challenge->status,
        'challenge_type' => $challenge->challenge_type,
        'duedate' => (int)$challenge->duedate,
        'timemodified' => (int)$challenge->timemodified,
    ];

    $info->customdata = $customdata;

    return $info;
}

/**
 * Dynamic course module info adjustment.
 *
 * @param cm_info $cm Course module info.
 */
function uckkchallenge_cm_info_dynamic(cm_info $cm): void {
    global $DB, $USER;

    $challenge = $DB->get_record(
        'uckkchallenge',
        ['id' => $cm->instance],
        'id, status, visibility, duedate'
    );

    if (!$challenge) {
        return;
    }

    $context = context_module::instance((int)$cm->id);

    if (
        $challenge->visibility === 'restricted_integrity'
        && !has_capability('mod/uckkchallenge:validateintegrity', $context)
    ) {
        $cm->set_available(false, get_string('restrictedintegrity', 'uckkchallenge'));
        return;
    }

    if (
        in_array($challenge->status, ['hidden', 'invalidated', 'withdrawn'], true)
        && !has_capability('mod/uckkchallenge:createchallenge', $context)
    ) {
        $cm->set_available(false, get_string('challengenotavailable', 'uckkchallenge'));
        return;
    }

    if (
        (int)$challenge->duedate > 0
        && (int)$challenge->duedate < time()
        && has_capability('mod/uckkchallenge:submitproof', $context, $USER)
    ) {
        $cm->set_after_link(html_writer::span(
            get_string('challengeoverdue', 'uckkchallenge'),
            'badge badge-warning uckkchallenge-overdue'
        ));
    }
}

/**
 * Return a user's challenge participation summary.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param stdClass $mod Module record.
 * @param stdClass $uckkchallenge Challenge instance.
 * @return stdClass|null
 */
function uckkchallenge_user_outline(
    stdClass $course,
    stdClass $user,
    stdClass $mod,
    stdClass $uckkchallenge
): ?stdClass {
    global $DB;

    $submission = $DB->get_record_sql(
        "SELECT *
           FROM {uckkchallenge_sub}
          WHERE challengeid = :challengeid
            AND userid = :userid
       ORDER BY timemodified DESC",
        [
            'challengeid' => $uckkchallenge->id,
            'userid' => $user->id,
        ],
        IGNORE_MULTIPLE
    );

    if (!$submission) {
        return null;
    }

    $result = new stdClass();
    $result->info = get_string('submissionstatusx', 'uckkchallenge', $submission->status);
    $result->time = (int)$submission->timemodified;

    return $result;
}

/**
 * Print a user's complete activity information.
 *
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param stdClass $mod Module record.
 * @param stdClass $uckkchallenge Challenge instance.
 */
function uckkchallenge_user_complete(
    stdClass $course,
    stdClass $user,
    stdClass $mod,
    stdClass $uckkchallenge
): void {
    global $DB, $OUTPUT;

    $submission = $DB->get_record_sql(
        "SELECT *
           FROM {uckkchallenge_sub}
          WHERE challengeid = :challengeid
            AND userid = :userid
       ORDER BY timemodified DESC",
        [
            'challengeid' => $uckkchallenge->id,
            'userid' => $user->id,
        ],
        IGNORE_MULTIPLE
    );

    if (!$submission) {
        echo $OUTPUT->notification(get_string('nosubmission', 'uckkchallenge'), 'info');
        return;
    }

    echo html_writer::tag('p', get_string('submissionstatusx', 'uckkchallenge', $submission->status));

    if (!empty($submission->timemodified)) {
        echo html_writer::tag('p', get_string('lastmodified') . ': ' . userdate((int)$submission->timemodified));
    }
}

/**
 * Return recent activity for this module.
 *
 * @param stdClass $course Course record.
 * @param bool $viewfullnames Whether full names may be viewed.
 * @param int $timestart Start timestamp.
 * @return bool
 */
function uckkchallenge_print_recent_activity(stdClass $course, bool $viewfullnames, int $timestart): bool {
    global $DB, $OUTPUT, $PAGE;

    $records = $DB->get_records_sql(
        "SELECT s.id,
                s.challengeid,
                s.userid,
                s.status,
                s.timemodified,
                c.name,
                c.course
           FROM {uckkchallenge_sub} s
           JOIN {uckkchallenge} c ON c.id = s.challengeid
          WHERE c.course = :courseid
            AND s.timemodified > :timestart
       ORDER BY s.timemodified DESC",
        [
            'courseid' => $course->id,
            'timestart' => $timestart,
        ],
        0,
        10
    );

    if (!$records) {
        return false;
    }

    echo $OUTPUT->heading(get_string('recentchallengeactivity', 'uckkchallenge'), 3);

    echo html_writer::start_tag('ul', ['class' => 'uckkchallenge-recent-activity']);

    foreach ($records as $record) {
        $cm = get_coursemodule_from_instance(
            'uckkchallenge',
            (int)$record->challengeid,
            (int)$course->id,
            false,
            IGNORE_MISSING
        );

        if (!$cm) {
            continue;
        }

        $url = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $cm->id]);

        echo html_writer::tag(
            'li',
            html_writer::link($url, format_string($record->name)) . ' — ' .
            get_string('submissionstatusx', 'uckkchallenge', $record->status) . ' — ' .
            userdate((int)$record->timemodified)
        );
    }

    echo html_writer::end_tag('ul');

    return true;
}

/**
 * Return custom completion state.
 *
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module.
 * @param int $userid User id.
 * @param bool $type Completion type.
 * @return bool
 */
function uckkchallenge_get_completion_state(
    stdClass $course,
    cm_info|stdClass $cm,
    int $userid,
    bool $type
): bool {
    global $DB;

    $challenge = $DB->get_record(
        'uckkchallenge',
        ['id' => $cm->instance],
        'id, completionrequiresubmission, completionrequirevalidation'
    );

    if (!$challenge) {
        return $type;
    }

    $conditions = [];

    if (!empty($challenge->completionrequiresubmission)) {
        $conditions[] = $DB->record_exists('uckkchallenge_sub', [
            'challengeid' => $challenge->id,
            'userid' => $userid,
        ]);
    }

    if (!empty($challenge->completionrequirevalidation)) {
        $conditions[] = $DB->record_exists('uckkchallenge_eval', [
            'challengeid' => $challenge->id,
            'userid' => $userid,
            'status' => 'validated',
        ]);
    }

    if (!$conditions) {
        return $type;
    }

    return !in_array(false, $conditions, true);
}

/**
 * Update grades for one user or all users.
 *
 * @param stdClass $uckkchallenge Challenge instance.
 * @param int $userid Optional user id.
 */
function uckkchallenge_update_grades(stdClass $uckkchallenge, int $userid = 0): void {
    global $DB;

    require_once(__DIR__ . '/../../lib/gradelib.php');

    if ($userid > 0) {
        $evaluations = $DB->get_records('uckkchallenge_eval', [
            'challengeid' => $uckkchallenge->id,
            'userid' => $userid,
        ]);
    } else {
        $evaluations = $DB->get_records('uckkchallenge_eval', [
            'challengeid' => $uckkchallenge->id,
        ]);
    }

    $grades = [];

    foreach ($evaluations as $evaluation) {
        if (!isset($evaluation->grade)) {
            continue;
        }

        $grade = new stdClass();
        $grade->userid = (int)$evaluation->userid;
        $grade->rawgrade = (float)$evaluation->grade;
        $grade->dategraded = (int)$evaluation->timemodified;
        $grade->datesubmitted = (int)$evaluation->timecreated;

        $grades[$grade->userid] = $grade;
    }

    uckkchallenge_grade_item_update($uckkchallenge, $grades);
}

/**
 * Create or update the grade item.
 *
 * @param stdClass $uckkchallenge Challenge instance.
 * @param array|stdClass|null $grades Grades.
 * @return int Grade update status.
 */
function uckkchallenge_grade_item_update(stdClass $uckkchallenge, array|stdClass|null $grades = null): int {
    require_once(__DIR__ . '/../../lib/gradelib.php');

    $params = [
        'itemname' => $uckkchallenge->name,
        'idnumber' => $uckkchallenge->idnumber ?? null,
    ];

    if (!empty($uckkchallenge->grade) && (int)$uckkchallenge->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = (float)$uckkchallenge->grade;
        $params['grademin'] = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    return grade_update(
        'mod/uckkchallenge',
        (int)$uckkchallenge->course,
        'mod',
        'uckkchallenge',
        (int)$uckkchallenge->id,
        0,
        $grades,
        $params
    );
}

/**
 * Delete the grade item.
 *
 * @param stdClass $uckkchallenge Challenge instance.
 * @return int Grade update status.
 */
function uckkchallenge_grade_item_delete(stdClass $uckkchallenge): int {
    require_once(__DIR__ . '/../../lib/gradelib.php');

    return grade_update(
        'mod/uckkchallenge',
        (int)$uckkchallenge->course,
        'mod',
        'uckkchallenge',
        (int)$uckkchallenge->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Define reset options for the course reset form.
 *
 * @param MoodleQuickForm $mform Reset form.
 */
function uckkchallenge_reset_course_form_definition(MoodleQuickForm $mform): void {
    $mform->addElement('header', 'uckkchallengeheader', get_string('modulenameplural', 'uckkchallenge'));

    $mform->addElement(
        'advcheckbox',
        'reset_uckkchallenge_submissions',
        get_string('reset:submissions', 'uckkchallenge')
    );

    $mform->addElement(
        'advcheckbox',
        'reset_uckkchallenge_evaluations',
        get_string('reset:evaluations', 'uckkchallenge')
    );

    $mform->addElement(
        'advcheckbox',
        'reset_uckkchallenge_states',
        get_string('reset:states', 'uckkchallenge')
    );
}

/**
 * Provide reset defaults.
 *
 * @param stdClass $course Course record.
 * @return array<string, int>
 */
function uckkchallenge_reset_course_form_defaults(stdClass $course): array {
    return [
        'reset_uckkchallenge_submissions' => 1,
        'reset_uckkchallenge_evaluations' => 1,
        'reset_uckkchallenge_states' => 0,
    ];
}

/**
 * Reset UCKK challenge data in a course.
 *
 * @param stdClass $data Reset data.
 * @return array<int, array<string, mixed>>
 */
function uckkchallenge_reset_userdata(stdClass $data): array {
    global $DB;

    $status = [];
    $component = get_string('modulenameplural', 'uckkchallenge');

    $challengeids = $DB->get_fieldset_select(
        'uckkchallenge',
        'id',
        'course = :courseid',
        ['courseid' => $data->courseid]
    );

    if (!$challengeids) {
        return $status;
    }

    [$insql, $params] = $DB->get_in_or_equal($challengeids, SQL_PARAMS_NAMED, 'challengeid');

    if (!empty($data->reset_uckkchallenge_evaluations)) {
        $DB->delete_records_select('uckkchallenge_eval', "challengeid {$insql}", $params);

        $status[] = [
            'component' => $component,
            'item' => get_string('reset:evaluations', 'uckkchallenge'),
            'error' => false,
        ];
    }

    if (!empty($data->reset_uckkchallenge_submissions)) {
        $DB->delete_records_select('uckkchallenge_proof', "challengeid {$insql}", $params);
        $DB->delete_records_select('uckkchallenge_sub', "challengeid {$insql}", $params);

        $status[] = [
            'component' => $component,
            'item' => get_string('reset:submissions', 'uckkchallenge'),
            'error' => false,
        ];
    }

    if (!empty($data->reset_uckkchallenge_states)) {
        $DB->delete_records_select('uckkchallenge_state', "challengeid {$insql}", $params);

        $status[] = [
            'component' => $component,
            'item' => get_string('reset:states', 'uckkchallenge'),
            'error' => false,
        ];
    }

    return $status;
}

/**
 * Serve files from UCKK challenge file areas.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array<int, string> $args File args.
 * @param bool $forcedownload Force download.
 * @param array<string, mixed> $options File serving options.
 * @return bool
 */
function uckkchallenge_pluginfile(
    stdClass $course,
    stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    $allowedareas = [
        'intro',
        'statement_files',
        'rule_files',
        'evidence_requirement_files',
        'submission_files',
        'proof_files',
        'feedback_files',
        'archive_export_files',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    if ($filearea === 'intro') {
        require_capability('mod/uckkchallenge:view', $context);
    } else if (in_array($filearea, ['submission_files', 'proof_files', 'feedback_files'], true)) {
        if (
            !has_capability('mod/uckkchallenge:evaluate', $context)
            && !has_capability('mod/uckkchallenge:submitproof', $context)
        ) {
            return false;
        }
    } else if ($filearea === 'archive_export_files') {
        require_capability('mod/uckkchallenge:archive', $context);
    } else {
        require_capability('mod/uckkchallenge:view', $context);
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);

    if ($filename === null || $itemid === null) {
        return false;
    }

    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'mod_uckkchallenge',
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
 * Get standard course overview data.
 *
 * @param stdClass $course Course record.
 * @param array<int, cm_info> $cms Course modules.
 * @return array<int, stdClass>
 */
function uckkchallenge_get_course_overview_info(stdClass $course, array $cms): array {
    global $DB, $USER;

    $results = [];

    foreach ($cms as $cm) {
        $challenge = $DB->get_record(
            'uckkchallenge',
            ['id' => $cm->instance],
            'id, name, duedate, status'
        );

        if (!$challenge) {
            continue;
        }

        $info = new stdClass();
        $info->cmid = (int)$cm->id;
        $info->name = format_string($challenge->name);
        $info->status = $challenge->status;
        $info->duedate = (int)$challenge->duedate;
        $info->hasduedate = (int)$challenge->duedate > 0;
        $info->submitted = $DB->record_exists('uckkchallenge_sub', [
            'challengeid' => $challenge->id,
            'userid' => $USER->id,
        ]);

        $results[(int)$cm->id] = $info;
    }

    return $results;
}

/**
 * Normalise incoming form data to the main uckkchallenge table.
 *
 * @param stdClass $instancedata Raw form data.
 * @return stdClass
 */
function uckkchallenge_normalise_instance_record(stdClass $instancedata): stdClass {
    $record = new stdClass();

    $record->course = (int)$instancedata->course;
    $record->name = trim((string)$instancedata->name);
    $record->intro = $instancedata->intro ?? '';
    $record->introformat = (int)($instancedata->introformat ?? FORMAT_HTML);

    $record->challenge_type = clean_param(
        (string)($instancedata->challenge_type ?? 'internal_learning'),
        PARAM_ALPHANUMEXT
    );

    $record->statement = $instancedata->statement ?? '';
    $record->statementformat = (int)($instancedata->statementformat ?? FORMAT_HTML);

    $record->contexttext = $instancedata->contexttext ?? '';
    $record->contextformat = (int)($instancedata->contextformat ?? FORMAT_HTML);

    $record->expectedoutput = $instancedata->expectedoutput ?? '';
    $record->expectedoutputformat = (int)($instancedata->expectedoutputformat ?? FORMAT_HTML);

    $record->evaluationcriteria = $instancedata->evaluationcriteria ?? '';
    $record->evaluationcriteriaformat = (int)($instancedata->evaluationcriteriaformat ?? FORMAT_HTML);

    $record->ethicalconstraints = $instancedata->ethicalconstraints ?? '';
    $record->ethicalconstraintsformat = (int)($instancedata->ethicalconstraintsformat ?? FORMAT_HTML);

    $record->opensat = (int)($instancedata->opensat ?? 0);
    $record->duedate = (int)($instancedata->duedate ?? 0);
    $record->closesat = (int)($instancedata->closesat ?? 0);

    $record->allowsubmissionsfromdate = (int)($instancedata->allowsubmissionsfromdate ?? 0);
    $record->cutoffdate = (int)($instancedata->cutoffdate ?? 0);

    $record->submissionmode = clean_param(
        (string)($instancedata->submissionmode ?? 'individual'),
        PARAM_ALPHANUMEXT
    );

    $record->maxsubmissions = (int)($instancedata->maxsubmissions ?? 1);
    $record->requireintegrityreview = !empty($instancedata->requireintegrityreview) ? 1 : 0;
    $record->allowpublicsummary = !empty($instancedata->allowpublicsummary) ? 1 : 0;
    $record->archiveonvalidation = !empty($instancedata->archiveonvalidation) ? 1 : 0;

    $record->grade = (float)($instancedata->grade ?? 100);
    $record->completionrequiresubmission = !empty($instancedata->completionrequiresubmission) ? 1 : 0;
    $record->completionrequirevalidation = !empty($instancedata->completionrequirevalidation) ? 1 : 0;

    $record->status = uckkchallenge_normalise_status((string)($instancedata->status ?? 'draft'));
    $record->visibility = uckkchallenge_normalise_visibility((string)($instancedata->visibility ?? 'course'));
    $record->provenance = uckkchallenge_normalise_provenance((string)($instancedata->provenance ?? 'human'));
    $record->metadata = uckkchallenge_encode_metadata($instancedata->metadata ?? null);

    return $record;
}

/**
 * Persist activity-owned related records provided by mod_form.
 *
 * @param stdClass $challenge Challenge instance.
 * @param stdClass $instancedata Form data.
 */
function uckkchallenge_save_related_records(stdClass $challenge, stdClass $instancedata): void {
    global $DB, $USER;

    $now = time();
    $challengeid = (int)$challenge->id;

    if (property_exists($instancedata, 'rules')) {
        $DB->delete_records('uckkchallenge_rule', ['challengeid' => $challengeid]);

        foreach (uckkchallenge_normalise_list_data($instancedata->rules) as $sortorder => $rule) {
            $record = uckkchallenge_base_child_record($challenge, $USER, $now);
            $record->rulename = (string)($rule['name'] ?? get_string('rule', 'uckkchallenge'));
            $record->ruletext = (string)($rule['text'] ?? '');
            $record->ruleformat = (int)($rule['format'] ?? FORMAT_HTML);
            $record->sortorder = $sortorder + 1;

            $DB->insert_record('uckkchallenge_rule', $record);
        }
    }

    if (property_exists($instancedata, 'corridors')) {
        $DB->delete_records('uckkchallenge_corr', ['challengeid' => $challengeid]);

        foreach (uckkchallenge_normalise_list_data($instancedata->corridors) as $sortorder => $corridor) {
            $record = uckkchallenge_base_child_record($challenge, $USER, $now);
            $record->corridorname = (string)($corridor['name'] ?? get_string('corridor', 'uckkchallenge'));
            $record->corridortext = (string)($corridor['text'] ?? '');
            $record->corridorformat = (int)($corridor['format'] ?? FORMAT_HTML);
            $record->sortorder = $sortorder + 1;

            $DB->insert_record('uckkchallenge_corr', $record);
        }
    }

    uckkchallenge_record_state($challenge, $challenge->status, get_string('statechangedbyform', 'uckkchallenge'));
}

/**
 * Create a base child record for UCKK challenge-owned tables.
 *
 * @param stdClass $challenge Challenge instance.
 * @param stdClass $user Current user.
 * @param int $now Current timestamp.
 * @return stdClass
 */
function uckkchallenge_base_child_record(stdClass $challenge, stdClass $user, int $now): stdClass {
    $record = new stdClass();
    $record->challengeid = (int)$challenge->id;
    $record->courseid = (int)$challenge->course;
    $record->contextid = uckkchallenge_get_contextid((int)$challenge->id, (int)$challenge->course);
    $record->createdby = (int)$user->id;
    $record->modifiedby = (int)$user->id;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->status = 'active';
    $record->visibility = (string)$challenge->visibility;
    $record->versionno = 1;
    $record->provenancehash = null;
    $record->metadata = null;

    return $record;
}

/**
 * Record a state transition for a challenge.
 *
 * @param stdClass $challenge Challenge instance.
 * @param string $status New status.
 * @param string $note State note.
 */
function uckkchallenge_record_state(stdClass $challenge, string $status, string $note = ''): void {
    global $DB, $USER;

    $record = new stdClass();
    $record->challengeid = (int)$challenge->id;
    $record->courseid = (int)$challenge->course;
    $record->contextid = uckkchallenge_get_contextid((int)$challenge->id, (int)$challenge->course);
    $record->userid = null;
    $record->fromstatus = null;
    $record->tostatus = uckkchallenge_normalise_status($status);
    $record->note = $note;
    $record->createdby = (int)$USER->id;
    $record->modifiedby = (int)$USER->id;
    $record->timecreated = time();
    $record->timemodified = $record->timecreated;
    $record->status = 'active';
    $record->visibility = (string)$challenge->visibility;
    $record->versionno = 1;
    $record->provenancehash = null;
    $record->metadata = null;

    $DB->insert_record('uckkchallenge_state', $record);
}

/**
 * Get the module context id for a challenge.
 *
 * @param int $challengeid Challenge id.
 * @param int $courseid Course id.
 * @return int
 */
function uckkchallenge_get_contextid(int $challengeid, int $courseid): int {
    $cm = get_coursemodule_from_instance('uckkchallenge', $challengeid, $courseid, false, IGNORE_MISSING);

    if (!$cm) {
        return context_course::instance($courseid)->id;
    }

    return context_module::instance((int)$cm->id)->id;
}

/**
 * Normalise challenge status.
 *
 * @param string $status Status.
 * @return string
 */
function uckkchallenge_normalise_status(string $status): string {
    $status = clean_param($status, PARAM_ALPHANUMEXT);

    $allowed = [
        'draft',
        'published',
        'open',
        'submitted',
        'under_review',
        'integrity_review',
        'revision_required',
        'resubmitted',
        'validated',
        'archived',
        'closed',
        'contested',
        'invalidated',
        'withdrawn',
        'expired',
        'hidden',
        'cancelled',
    ];

    return in_array($status, $allowed, true) ? $status : 'draft';
}

/**
 * Normalise visibility.
 *
 * @param string $visibility Visibility.
 * @return string
 */
function uckkchallenge_normalise_visibility(string $visibility): string {
    $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

    $allowed = [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'program',
        'institution',
        'public',
        'restricted',
        'restricted_integrity',
        'hidden',
        'archived',
    ];

    return in_array($visibility, $allowed, true) ? $visibility : 'course';
}

/**
 * Normalise provenance.
 *
 * @param string $provenance Provenance.
 * @return string
 */
function uckkchallenge_normalise_provenance(string $provenance): string {
    $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);

    $allowed = [
        'human',
        'ai_assisted',
        'imported',
        'system',
        'archive',
        'assembly',
        'challenge',
        'integrity',
    ];

    return in_array($provenance, $allowed, true) ? $provenance : 'human';
}

/**
 * Encode metadata as JSON.
 *
 * @param mixed $metadata Metadata.
 * @return string|null
 */
function uckkchallenge_encode_metadata(mixed $metadata): ?string {
    if ($metadata === null || $metadata === '') {
        return null;
    }

    if (is_string($metadata)) {
        json_decode($metadata);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $metadata;
        }

        return json_encode(['value' => $metadata], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Normalise form list data.
 *
 * @param mixed $data Raw list data.
 * @return array<int, array<string, mixed>>
 */
function uckkchallenge_normalise_list_data(mixed $data): array {
    if ($data === null || $data === '') {
        return [];
    }

    if (is_string($data)) {
        $decoded = json_decode($data, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, 'is_array'));
        }

        return [];
    }

    if (is_array($data)) {
        return array_values(array_map(static function ($item): array {
            return is_object($item) ? (array)$item : (array)$item;
        }, $data));
    }

    return [];
}

/**
 * Trigger an event when the event class exists.
 *
 * @param string $eventclass Fully-qualified event class.
 * @param stdClass $challenge Challenge record.
 * @param stdClass $source Source data.
 */
function uckkchallenge_trigger_event_if_available(string $eventclass, stdClass $challenge, stdClass $source): void {
    if (!class_exists($eventclass)) {
        return;
    }

    $cmid = !empty($source->coursemodule)
        ? (int)$source->coursemodule
        : 0;

    if (!$cmid && !empty($challenge->id) && !empty($challenge->course)) {
        $cm = get_coursemodule_from_instance(
            'uckkchallenge',
            (int)$challenge->id,
            (int)$challenge->course,
            false,
            IGNORE_MISSING
        );

        $cmid = $cm ? (int)$cm->id : 0;
    }

    $context = $cmid
        ? context_module::instance($cmid)
        : context_course::instance((int)$challenge->course);

    $event = $eventclass::create([
        'objectid' => (int)$challenge->id,
        'context' => $context,
        'other' => [
            'status' => $challenge->status ?? null,
            'challenge_type' => $challenge->challenge_type ?? null,
        ],
    ]);

    $event->add_record_snapshot('uckkchallenge', $challenge);
    $event->trigger();
}