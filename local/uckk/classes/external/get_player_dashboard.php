<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * External service returning the UCKK player dashboard.
 *
 * This service returns a safe, permission-checked dashboard payload for a
 * Joueur. It is designed for use by the UCKK dashboard block, theme widgets,
 * mobile clients and external integrations.
 *
 * It does not mutate data, enrol users, award badges, validate competencies,
 * decide integrity cases, grade work, or publish archive records.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use completion_info;
use context_course;
use context_system;
use core_badges\badge;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Get the UCKK player dashboard.
 *
 * @package local_uckk
 */
final class get_player_dashboard extends external_api {
    /** Default dashboard item limit. */
    private const DEFAULT_LIMIT = 8;

    /** Maximum dashboard item limit. */
    private const MAX_LIMIT = 50;

    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /**
     * Describe service parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'Target user id. Use 0 for the current user.',
                VALUE_DEFAULT,
                0
            ),
            'courseid' => new external_value(
                PARAM_INT,
                'Optional course context id. Use 0 for the site/system context.',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of items per dashboard list.',
                VALUE_DEFAULT,
                self::DEFAULT_LIMIT
            ),
            'includeprivate' => new external_value(
                PARAM_BOOL,
                'Whether to include private/self-only dashboard details when permitted.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Execute service.
     *
     * @param int $userid Target user id. 0 means current user.
     * @param int $courseid Optional course context id.
     * @param int $limit Maximum items per list.
     * @param bool $includeprivate Include private data when permitted.
     * @return array<string, mixed>
     */
    public static function execute(
        int $userid = 0,
        int $courseid = 0,
        int $limit = self::DEFAULT_LIMIT,
        bool $includeprivate = false
    ): array {
        global $USER, $DB;

        [
            'userid' => $userid,
            'courseid' => $courseid,
            'limit' => $limit,
            'includeprivate' => $includeprivate,
        ] = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
            'limit' => $limit,
            'includeprivate' => $includeprivate,
        ]);

        require_login();

        $targetuserid = $userid > 0 ? $userid : (int)$USER->id;
        $isself = $targetuserid === (int)$USER->id;

        $context = self::resolve_context($courseid);
        self::validate_context($context);

        if (!$isself) {
            require_capability('local/uckk:manageprofiles', $context);
        } else if (!has_capability('local/uckk:viewcampus', $context)) {
            require_capability('local/uckk:viewcampus', context_system::instance());
        }

        $limit = self::normalise_limit($limit);
        $includeprivate = $includeprivate && $isself;

        $user = $DB->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);

        $profile = self::get_player_profile($targetuserid, $includeprivate);
        $courses = self::get_courses($targetuserid, $limit);
        $pathway = self::get_pathway($targetuserid);
        $progress = self::get_progress_summary($targetuserid, $courses);
        $competencies = self::get_competencies($targetuserid, $limit);
        $badges = self::get_badges($targetuserid, $limit);
        $challenges = self::get_challenges($targetuserid, $limit);
        $assemblies = self::get_assemblies($targetuserid, $limit);
        $archives = self::get_archives($targetuserid, $limit, $includeprivate);
        $integrity = self::get_integrity($targetuserid, $limit, $includeprivate);
        $deadlines = self::get_deadlines($targetuserid, $courses, $limit);
        $portfolio = self::get_portfolio($targetuserid, $includeprivate);
        $warnings = self::get_warnings($context, $targetuserid);

        return [
            'userid' => $targetuserid,
            'generatedat' => time(),
            'contextid' => $context->id,
            'isself' => $isself,
            'includeprivate' => $includeprivate,
            'user' => [
                'id' => (int)$user->id,
                'fullname' => fullname($user),
                'profileurl' => (new moodle_url('/user/profile.php', ['id' => $user->id]))->out(false),
            ],
            'profile' => $profile,
            'pathway' => $pathway,
            'progress' => $progress,
            'courses' => $courses,
            'competencies' => $competencies,
            'badges' => $badges,
            'challenges' => $challenges,
            'assemblies' => $assemblies,
            'archives' => $archives,
            'integrity' => $integrity,
            'deadlines' => $deadlines,
            'portfolio' => $portfolio,
            'warnings' => $warnings,
        ];
    }

    /**
     * Describe service return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'Target user id.'),
            'generatedat' => new external_value(PARAM_INT, 'Unix timestamp when dashboard was generated.'),
            'contextid' => new external_value(PARAM_INT, 'Moodle context id used for validation.'),
            'isself' => new external_value(PARAM_BOOL, 'Whether the viewer is the target user.'),
            'includeprivate' => new external_value(PARAM_BOOL, 'Whether private self data is included.'),

            'user' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id.'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name.'),
                'profileurl' => new external_value(PARAM_URL, 'User profile URL.'),
            ]),

            'profile' => new external_single_structure([
                'displaytitle' => new external_value(PARAM_TEXT, 'UCKK display title.'),
                'symbolicroles' => new external_multiple_structure(
                    new external_single_structure([
                        'key' => new external_value(PARAM_ALPHANUMEXT, 'Symbolic role key.'),
                        'label' => new external_value(PARAM_TEXT, 'Symbolic role label.'),
                    ]),
                    'Symbolic roles.',
                    VALUE_DEFAULT,
                    []
                ),
                'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Profile visibility.'),
                'hasprofile' => new external_value(PARAM_BOOL, 'Whether an explicit UCKK profile exists.'),
            ]),

            'pathway' => new external_single_structure([
                'activepathwayid' => new external_value(PARAM_INT, 'Active pathway id.', VALUE_DEFAULT, 0),
                'name' => new external_value(PARAM_TEXT, 'Active pathway name.', VALUE_DEFAULT, ''),
                'program' => new external_value(PARAM_TEXT, 'Program name.', VALUE_DEFAULT, ''),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Pathway status.', VALUE_DEFAULT, 'none'),
                'progresspercent' => new external_value(PARAM_INT, 'Pathway completion percentage.', VALUE_DEFAULT, 0),
            ]),

            'progress' => new external_single_structure([
                'coursecount' => new external_value(PARAM_INT, 'Number of enrolled courses.'),
                'completedcourses' => new external_value(PARAM_INT, 'Number of completed courses.'),
                'courseprogresspercent' => new external_value(PARAM_INT, 'Overall course completion percent.'),
                'competencycount' => new external_value(PARAM_INT, 'Number of competencies returned.'),
                'badgecount' => new external_value(PARAM_INT, 'Number of badges returned.'),
                'challengecount' => new external_value(PARAM_INT, 'Number of challenges returned.'),
                'assemblycount' => new external_value(PARAM_INT, 'Number of assemblies returned.'),
                'archivecount' => new external_value(PARAM_INT, 'Number of archive items returned.'),
                'integrityopencount' => new external_value(PARAM_INT, 'Number of open integrity records returned.'),
            ]),

            'courses' => new external_multiple_structure(
                self::course_structure(),
                'Courses visible in the dashboard.'
            ),

            'competencies' => new external_multiple_structure(
                self::competency_structure(),
                'Competencies visible in the dashboard.'
            ),

            'badges' => new external_multiple_structure(
                self::badge_structure(),
                'Badges visible in the dashboard.'
            ),

            'challenges' => new external_multiple_structure(
                self::challenge_structure(),
                'Challenges visible in the dashboard.'
            ),

            'assemblies' => new external_multiple_structure(
                self::assembly_structure(),
                'Assemblies visible in the dashboard.'
            ),

            'archives' => new external_multiple_structure(
                self::archive_structure(),
                'Archive items visible in the dashboard.'
            ),

            'integrity' => new external_multiple_structure(
                self::integrity_structure(),
                'Integrity records visible in the dashboard.'
            ),

            'deadlines' => new external_multiple_structure(
                self::deadline_structure(),
                'Upcoming deadlines visible in the dashboard.'
            ),

            'portfolio' => new external_single_structure([
                'itemcount' => new external_value(PARAM_INT, 'Number of portfolio items.'),
                'proofcount' => new external_value(PARAM_INT, 'Number of proof items.'),
                'reflectioncount' => new external_value(PARAM_INT, 'Number of reflection items.'),
                'archiveurl' => new external_value(PARAM_URL, 'Portfolio archive URL.', VALUE_DEFAULT, ''),
            ]),

            'warnings' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Warning type.'),
                    'message' => new external_value(PARAM_TEXT, 'Warning message.'),
                ]),
                'Non-fatal warnings.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Resolve dashboard context.
     *
     * @param int $courseid Course id.
     * @return \context
     */
    private static function resolve_context(int $courseid): \context {
        if ($courseid > 0) {
            return context_course::instance($courseid);
        }

        return context_system::instance();
    }

    /**
     * Normalise limit.
     *
     * @param int $limit Raw limit.
     * @return int
     */
    private static function normalise_limit(int $limit): int {
        if ($limit <= 0) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * Get player profile.
     *
     * @param int $userid User id.
     * @param bool $includeprivate Include private values.
     * @return array<string, mixed>
     */
    private static function get_player_profile(int $userid, bool $includeprivate): array {
        global $DB;

        $profile = [
            'displaytitle' => get_string('joueur', 'theme_uckk'),
            'symbolicroles' => [
                [
                    'key' => 'joueur',
                    'label' => get_string('joueur', 'theme_uckk'),
                ],
            ],
            'visibility' => 'private',
            'hasprofile' => false,
        ];

        if (!$DB->get_manager()->table_exists('local_uckk_player')) {
            return $profile;
        }

        $record = $DB->get_record('local_uckk_player', ['userid' => $userid]);
        if (!$record) {
            return $profile;
        }

        $profile['hasprofile'] = true;
        $profile['displaytitle'] = format_string($record->displaytitle ?? $profile['displaytitle']);
        $profile['visibility'] = clean_param($record->visibility ?? 'private', PARAM_ALPHANUMEXT);

        if (!$includeprivate && $profile['visibility'] === 'private') {
            $profile['symbolicroles'] = [];
            return $profile;
        }

        $roles = [];
        if (!empty($record->symbolicroles)) {
            $decoded = json_decode($record->symbolicroles, true);
            if (is_array($decoded)) {
                foreach ($decoded as $role) {
                    $key = clean_param((string)$role, PARAM_ALPHANUMEXT);
                    if ($key === '') {
                        continue;
                    }

                    $roles[] = [
                        'key' => $key,
                        'label' => self::humanise_key($key),
                    ];
                }
            }
        }

        if (!empty($roles)) {
            $profile['symbolicroles'] = $roles;
        }

        return $profile;
    }

    /**
     * Get enrolled courses.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_courses(int $userid, int $limit): array {
        global $CFG;

        require_once($CFG->dirroot . '/lib/enrollib.php');

        $courses = enrol_get_users_courses(
            $userid,
            true,
            'id, shortname, fullname, idnumber, visible, format, startdate, enddate, category'
        );

        $items = [];
        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            if (!$context) {
                continue;
            }

            $completionpercent = self::get_course_completion_percent($course, $userid);

            $items[] = [
                'id' => (int)$course->id,
                'shortname' => format_string($course->shortname, true, ['context' => $context]),
                'fullname' => format_string($course->fullname, true, ['context' => $context]),
                'idnumber' => clean_param($course->idnumber ?? '', PARAM_TEXT),
                'format' => clean_param($course->format ?? '', PARAM_ALPHANUMEXT),
                'visible' => (bool)$course->visible,
                'progresspercent' => $completionpercent,
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * Get pathway summary.
     *
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private static function get_pathway(int $userid): array {
        global $DB;

        $pathway = [
            'activepathwayid' => 0,
            'name' => '',
            'program' => '',
            'status' => 'none',
            'progresspercent' => 0,
        ];

        if (!$DB->get_manager()->table_exists('local_uckk_pathway')) {
            return $pathway;
        }

        $sql = "SELECT p.*
                  FROM {local_uckk_pathway} p
             LEFT JOIN {local_uckk_player} u ON " . $DB->sql_like('u.activepathwayids', ':pathwaypattern', false) . "
                 WHERE u.userid = :userid
              ORDER BY p.id ASC";

        try {
            $record = $DB->get_record_sql($sql, [
                'userid' => $userid,
                'pathwaypattern' => '%',
            ], IGNORE_MULTIPLE);
        } catch (\Throwable $e) {
            $record = false;
        }

        if (!$record) {
            return $pathway;
        }

        $pathway['activepathwayid'] = (int)$record->id;
        $pathway['name'] = format_string($record->fullname ?? $record->shortname ?? '');
        $pathway['program'] = '';
        $pathway['status'] = clean_param($record->status ?? 'active', PARAM_ALPHANUMEXT);
        $pathway['progresspercent'] = 0;

        return $pathway;
    }

    /**
     * Get progress summary.
     *
     * @param int $userid User id.
     * @param array<int, array<string, mixed>> $courses Courses.
     * @return array<string, int>
     */
    private static function get_progress_summary(int $userid, array $courses): array {
        $completed = 0;
        $totalprogress = 0;

        foreach ($courses as $course) {
            $progress = (int)($course['progresspercent'] ?? 0);
            $totalprogress += $progress;

            if ($progress >= 100) {
                $completed++;
            }
        }

        $coursecount = count($courses);
        $average = $coursecount > 0 ? (int)round($totalprogress / $coursecount) : 0;

        return [
            'coursecount' => $coursecount,
            'completedcourses' => $completed,
            'courseprogresspercent' => $average,
            'competencycount' => 0,
            'badgecount' => 0,
            'challengecount' => 0,
            'assemblycount' => 0,
            'archivecount' => 0,
            'integrityopencount' => 0,
        ];
    }

    /**
     * Get competencies.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_competencies(int $userid, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('competency_usercomp')) {
            return [];
        }

        $sql = "SELECT uc.id,
                       uc.userid,
                       uc.competencyid,
                       uc.proficiency,
                       uc.grade,
                       uc.timemodified,
                       c.shortname,
                       c.idnumber
                  FROM {competency_usercomp} uc
                  JOIN {competency} c ON c.id = uc.competencyid
                 WHERE uc.userid = :userid
              ORDER BY uc.timemodified DESC";

        $records = $DB->get_records_sql($sql, ['userid' => $userid], 0, $limit);
        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => (int)$record->competencyid,
                'shortname' => format_string($record->shortname ?? ''),
                'idnumber' => clean_param($record->idnumber ?? '', PARAM_TEXT),
                'proficient' => !empty($record->proficiency),
                'grade' => isset($record->grade) ? (int)$record->grade : 0,
                'timemodified' => (int)($record->timemodified ?? 0),
            ];
        }

        return $items;
    }

    /**
     * Get badges.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_badges(int $userid, int $limit): array {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $records = badges_get_user_badges($userid, 0, null, null, null, true);
        $items = [];

        foreach ($records as $record) {
            $badgeid = (int)($record->id ?? $record->badgeid ?? 0);
            $imageurl = '';

            if ($badgeid > 0) {
                try {
                    $badge = new badge($badgeid);
                    $imageurl = moodle_url::make_pluginfile_url(
                        $badge->get_context()->id,
                        'badges',
                        'badgeimage',
                        $badge->id,
                        '/',
                        'f1'
                    )->out(false);
                } catch (\Throwable $e) {
                    $imageurl = '';
                }
            }

            $items[] = [
                'id' => $badgeid,
                'name' => format_string($record->name ?? ''),
                'description' => format_text($record->description ?? '', FORMAT_HTML),
                'dateissued' => (int)($record->dateissued ?? 0),
                'imageurl' => $imageurl,
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * Get UCKK challenges.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_challenges(int $userid, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('uckkchallenge')) {
            return [];
        }

        $records = $DB->get_records('uckkchallenge', null, 'timemodified DESC, id DESC', '*', 0, $limit);
        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => (int)$record->id,
                'courseid' => (int)($record->course ?? 0),
                'name' => format_string($record->name ?? ''),
                'status' => clean_param($record->status ?? 'active', PARAM_ALPHANUMEXT),
                'timeclose' => (int)($record->timeclose ?? 0),
                'url' => self::plugin_index_url('mod', 'uckkchallenge', '/mod/uckkchallenge/view.php', ['id' => $record->id]),
            ];
        }

        return $items;
    }

    /**
     * Get UCKK assemblies.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_assemblies(int $userid, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('uckkassembly')) {
            return [];
        }

        $records = $DB->get_records('uckkassembly', null, 'timemodified DESC, id DESC', '*', 0, $limit);
        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => (int)$record->id,
                'courseid' => (int)($record->course ?? 0),
                'name' => format_string($record->name ?? ''),
                'assemblytype' => clean_param($record->assemblytype ?? 'savoirs', PARAM_ALPHANUMEXT),
                'status' => clean_param($record->status ?? 'active', PARAM_ALPHANUMEXT),
                'url' => self::plugin_index_url('mod', 'uckkassembly', '/mod/uckkassembly/view.php', ['id' => $record->id]),
            ];
        }

        return $items;
    }

    /**
     * Get archive items.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @param bool $includeprivate Include private data.
     * @return array<int, array<string, mixed>>
     */
    private static function get_archives(int $userid, int $limit, bool $includeprivate): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('uckkarchive_item')) {
            return [];
        }

        $conditions = [];
        $params = [];

        if (!$includeprivate) {
            $conditions[] = "visibility <> :privatevisibility";
            $params['privatevisibility'] = 'private';
        }

        $where = empty($conditions) ? '1 = 1' : implode(' AND ', $conditions);

        $records = $DB->get_records_select(
            'uckkarchive_item',
            $where,
            $params,
            'timemodified DESC, id DESC',
            '*',
            0,
            $limit
        );

        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => (int)$record->id,
                'title' => format_string($record->title ?? ''),
                'itemtype' => clean_param($record->itemtype ?? 'archive_item', PARAM_ALPHANUMEXT),
                'validationstate' => clean_param($record->validationstate ?? 'draft', PARAM_ALPHANUMEXT),
                'visibility' => clean_param($record->visibility ?? 'private', PARAM_ALPHANUMEXT),
                'url' => self::plugin_index_url('mod', 'uckkarchive', '/mod/uckkarchive/item.php', ['id' => $record->id]),
            ];
        }

        return $items;
    }

    /**
     * Get integrity records.
     *
     * @param int $userid User id.
     * @param int $limit Max items.
     * @param bool $includeprivate Include private data.
     * @return array<int, array<string, mixed>>
     */
    private static function get_integrity(int $userid, int $limit, bool $includeprivate): array {
        global $DB;

        if (!$includeprivate || !$DB->get_manager()->table_exists('tool_uckki_case')) {
            return [];
        }

        $records = $DB->get_records_select(
            'tool_uckki_case',
            'openedby = :userid OR assignedto = :assignedto',
            [
                'userid' => $userid,
                'assignedto' => $userid,
            ],
            'timemodified DESC, id DESC',
            '*',
            0,
            $limit
        );

        $items = [];

        foreach ($records as $record) {
            $items[] = [
                'id' => (int)$record->id,
                'casetype' => clean_param($record->casetype ?? 'integrity_case', PARAM_ALPHANUMEXT),
                'status' => clean_param($record->status ?? 'opened', PARAM_ALPHANUMEXT),
                'severity' => clean_param($record->severity ?? 'normal', PARAM_ALPHANUMEXT),
                'url' => self::plugin_index_url('tool', 'uckkintegrity', '/admin/tool/uckkintegrity/case.php', ['id' => $record->id]),
            ];
        }

        return $items;
    }

    /**
     * Get deadlines.
     *
     * @param int $userid User id.
     * @param array<int, array<string, mixed>> $courses Courses.
     * @param int $limit Max items.
     * @return array<int, array<string, mixed>>
     */
    private static function get_deadlines(int $userid, array $courses, int $limit): array {
        global $DB;

        $items = [];

        if ($DB->get_manager()->table_exists('uckkchallenge')) {
            $records = $DB->get_records_select(
                'uckkchallenge',
                'timeclose > :now',
                ['now' => time()],
                'timeclose ASC',
                '*',
                0,
                $limit
            );

            foreach ($records as $record) {
                $items[] = [
                    'id' => (int)$record->id,
                    'type' => 'challenge',
                    'name' => format_string($record->name ?? ''),
                    'time' => (int)$record->timeclose,
                    'url' => self::plugin_index_url('mod', 'uckkchallenge', '/mod/uckkchallenge/view.php', ['id' => $record->id]),
                ];
            }
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * Get portfolio summary.
     *
     * @param int $userid User id.
     * @param bool $includeprivate Include private data.
     * @return array<string, mixed>
     */
    private static function get_portfolio(int $userid, bool $includeprivate): array {
        global $DB;

        $summary = [
            'itemcount' => 0,
            'proofcount' => 0,
            'reflectioncount' => 0,
            'archiveurl' => '',
        ];

        if ($DB->get_manager()->table_exists('uckkarchive_item')) {
            $params = ['userid' => $userid];
            $visibilitysql = $includeprivate ? '' : " AND visibility <> 'private'";

            $summary['itemcount'] = (int)$DB->count_records_select(
                'uckkarchive_item',
                'owneruserid = :userid' . $visibilitysql,
                $params
            );

            $summary['proofcount'] = (int)$DB->count_records_select(
                'uckkarchive_item',
                "owneruserid = :userid AND itemtype = 'proof'" . $visibilitysql,
                $params
            );

            $summary['reflectioncount'] = (int)$DB->count_records_select(
                'uckkarchive_item',
                "owneruserid = :userid AND itemtype = 'reflection'" . $visibilitysql,
                $params
            );

            $summary['archiveurl'] = self::plugin_index_url('mod', 'uckkarchive', '/mod/uckkarchive/index.php', []);
        }

        return $summary;
    }

    /**
     * Get non-fatal warnings.
     *
     * @param \context $context Moodle context.
     * @param int $userid User id.
     * @return array<int, array<string, string>>
     */
    private static function get_warnings(\context $context, int $userid): array {
        global $DB;

        $warnings = [];

        if (!$DB->get_manager()->table_exists('local_uckk_player')) {
            $warnings[] = [
                'type' => 'missing_profile_table',
                'message' => 'UCKK player profile table is not available yet.',
            ];
        }

        if (!has_capability('local/uckk:viewcampus', $context)) {
            $warnings[] = [
                'type' => 'limited_capability',
                'message' => 'Dashboard data may be limited by current permissions.',
            ];
        }

        return $warnings;
    }

    /**
     * Get course completion percent.
     *
     * @param stdClass $course Course record.
     * @param int $userid User id.
     * @return int
     */
    private static function get_course_completion_percent(stdClass $course, int $userid): int {
        global $CFG;

        require_once($CFG->libdir . '/completionlib.php');

        try {
            $completion = new completion_info($course);
            if (!$completion->is_enabled()) {
                return 0;
            }

            $activities = $completion->get_activities();
            if (empty($activities)) {
                return 0;
            }

            $completed = 0;
            $total = 0;

            foreach ($activities as $activity) {
                if (empty($activity->completion)) {
                    continue;
                }

                $total++;
                $data = $completion->get_data($activity, false, $userid);
                if (!empty($data->completionstate)) {
                    $completed++;
                }
            }

            if ($total === 0) {
                return 0;
            }

            return (int)round(($completed / $total) * 100);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Build a plugin URL only when a plugin directory exists.
     *
     * @param string $type Plugin type.
     * @param string $name Plugin name.
     * @param string $path URL path.
     * @param array<string, mixed> $params URL parameters.
     * @return string
     */
    private static function plugin_index_url(string $type, string $name, string $path, array $params): string {
        if (\core_component::get_plugin_directory($type, $name) === null) {
            return '';
        }

        return (new moodle_url($path, $params))->out(false);
    }

    /**
     * Convert a machine key to a readable label.
     *
     * @param string $key Machine key.
     * @return string
     */
    private static function humanise_key(string $key): string {
        $key = str_replace(['_', '-'], ' ', $key);
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        return \core_text::strtotitle($key);
    }

    /**
     * Return course structure.
     *
     * @return external_single_structure
     */
    private static function course_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course id.'),
            'shortname' => new external_value(PARAM_TEXT, 'Course shortname.'),
            'fullname' => new external_value(PARAM_TEXT, 'Course fullname.'),
            'idnumber' => new external_value(PARAM_TEXT, 'Course idnumber.'),
            'format' => new external_value(PARAM_ALPHANUMEXT, 'Course format.'),
            'visible' => new external_value(PARAM_BOOL, 'Whether course is visible.'),
            'progresspercent' => new external_value(PARAM_INT, 'Completion percentage.'),
            'url' => new external_value(PARAM_URL, 'Course URL.'),
        ]);
    }

    /**
     * Return competency structure.
     *
     * @return external_single_structure
     */
    private static function competency_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Competency id.'),
            'shortname' => new external_value(PARAM_TEXT, 'Competency shortname.'),
            'idnumber' => new external_value(PARAM_TEXT, 'Competency idnumber.'),
            'proficient' => new external_value(PARAM_BOOL, 'Whether proficient.'),
            'grade' => new external_value(PARAM_INT, 'Competency grade.'),
            'timemodified' => new external_value(PARAM_INT, 'Last modified timestamp.'),
        ]);
    }

    /**
     * Return badge structure.
     *
     * @return external_single_structure
     */
    private static function badge_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Badge id.'),
            'name' => new external_value(PARAM_TEXT, 'Badge name.'),
            'description' => new external_value(PARAM_RAW, 'Badge description.'),
            'dateissued' => new external_value(PARAM_INT, 'Date issued timestamp.'),
            'imageurl' => new external_value(PARAM_URL, 'Badge image URL.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return challenge structure.
     *
     * @return external_single_structure
     */
    private static function challenge_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Challenge id.'),
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'name' => new external_value(PARAM_TEXT, 'Challenge name.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Challenge status.'),
            'timeclose' => new external_value(PARAM_INT, 'Close timestamp.'),
            'url' => new external_value(PARAM_URL, 'Challenge URL.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return assembly structure.
     *
     * @return external_single_structure
     */
    private static function assembly_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Assembly id.'),
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'name' => new external_value(PARAM_TEXT, 'Assembly name.'),
            'assemblytype' => new external_value(PARAM_ALPHANUMEXT, 'Assembly type.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Assembly status.'),
            'url' => new external_value(PARAM_URL, 'Assembly URL.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return archive structure.
     *
     * @return external_single_structure
     */
    private static function archive_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Archive item id.'),
            'title' => new external_value(PARAM_TEXT, 'Archive item title.'),
            'itemtype' => new external_value(PARAM_ALPHANUMEXT, 'Archive item type.'),
            'validationstate' => new external_value(PARAM_ALPHANUMEXT, 'Validation state.'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility.'),
            'url' => new external_value(PARAM_URL, 'Archive item URL.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return integrity structure.
     *
     * @return external_single_structure
     */
    private static function integrity_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Integrity case id.'),
            'casetype' => new external_value(PARAM_ALPHANUMEXT, 'Case type.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Case status.'),
            'severity' => new external_value(PARAM_ALPHANUMEXT, 'Case severity.'),
            'url' => new external_value(PARAM_URL, 'Integrity case URL.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Return deadline structure.
     *
     * @return external_single_structure
     */
    private static function deadline_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Deadline source id.'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Deadline type.'),
            'name' => new external_value(PARAM_TEXT, 'Deadline name.'),
            'time' => new external_value(PARAM_INT, 'Deadline timestamp.'),
            'url' => new external_value(PARAM_URL, 'Deadline URL.', VALUE_DEFAULT, ''),
        ]);
    }
}

