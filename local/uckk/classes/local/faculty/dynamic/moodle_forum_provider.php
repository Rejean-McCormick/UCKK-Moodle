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
 * Moodle forum dynamic block provider for public UCKK faculty pages.
 *
 * This provider reads public discussions from a Moodle forum declared in a
 * Faculty Profile dynamic block source:
 *
 * {
 *     "provider": "moodle_forum",
 *     "course_idnumber": "GJS-HUB",
 *     "forum_name": "Annonces"
 * }
 *
 * It is intentionally read-only and fail-closed. If the course, forum, module,
 * context, capability, visibility, or Moodle forum API cannot be resolved, it
 * returns no items.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

use cm_info;
use context;
use context_module;
use dml_exception;
use moodle_url;
use stdClass;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Public Moodle forum provider.
 *
 * @package local_uckk
 */
final class moodle_forum_provider implements provider_interface {
    /** Source provider name. */
    private const PROVIDER = 'moodle_forum';

    /** Default number of discussions to read. */
    private const DEFAULT_LIMIT = 5;

    /** Maximum public discussions read from one block. */
    private const MAX_LIMIT = 50;

    /** Overfetch multiplier before visibility filtering. */
    private const OVERFETCH_MULTIPLIER = 3;

    /**
     * Return public forum items for one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Resolved and normalised faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed> Payload containing public items.
     */
    public function get_items(array $block, array $faculty, array $pagecontext = []): array {
        global $CFG;

        $items = [];

        try {
            $source = self::get_source($block);

            if (self::clean_key((string)($source['provider'] ?? '')) !== self::PROVIDER) {
                return ['items' => $items];
            }

            $limit = self::normalise_limit($block['limit'] ?? null);

            if ($limit <= 0) {
                return ['items' => $items];
            }

            $course = $this->resolve_course($source);

            if ($course === null || !$this->is_public_course($course)) {
                return ['items' => $items];
            }

            $forum = $this->resolve_forum($source, $course);

            if ($forum === null) {
                return ['items' => $items];
            }

            $cm = $this->resolve_course_module($course, (int)$forum->id);

            if ($cm === null || !$this->is_public_course_module($cm)) {
                return ['items' => $items];
            }

            $context = context_module::instance((int)$cm->id, IGNORE_MISSING);

            if (!$context instanceof context) {
                return ['items' => $items];
            }

            if (!has_capability('mod/forum:viewdiscussion', $context)) {
                return ['items' => $items];
            }

            require_once($CFG->dirroot . '/mod/forum/lib.php');

            foreach ($this->get_latest_discussions($forum, $limit) as $discussion) {
                if (!$this->can_export_discussion($forum, $discussion, $context)) {
                    continue;
                }

                $items[] = $this->export_discussion($discussion, $forum, $context);

                if (count($items) >= $limit) {
                    break;
                }
            }
        } catch (Throwable $e) {
            debugging('local_uckk Moodle forum dynamic block provider failed.', DEBUG_DEVELOPER);
            return ['items' => []];
        }

        return ['items' => $items];
    }

    /**
     * Resolve the Moodle course from the documented source config.
     *
     * @param array<string, mixed> $source Dynamic block source.
     * @return stdClass|null
     */
    private function resolve_course(array $source): ?stdClass {
        global $DB;

        $courseidnumber = self::clean_text($source['course_idnumber'] ?? '');

        if ($courseidnumber === '') {
            return null;
        }

        try {
            $course = $DB->get_record('course', ['idnumber' => $courseidnumber], '*', IGNORE_MISSING);
        } catch (dml_exception $e) {
            return null;
        }

        return $course instanceof stdClass ? $course : null;
    }

    /**
     * Resolve the Moodle forum from the documented source config.
     *
     * @param array<string, mixed> $source Dynamic block source.
     * @param stdClass $course Moodle course record.
     * @return stdClass|null
     */
    private function resolve_forum(array $source, stdClass $course): ?stdClass {
        global $DB;

        $forumname = self::clean_text($source['forum_name'] ?? '');

        if ($forumname === '') {
            return null;
        }

        try {
            $forums = $DB->get_records(
                'forum',
                [
                    'course' => (int)$course->id,
                    'name' => $forumname,
                ],
                'id ASC',
                '*',
                0,
                1
            );
        } catch (dml_exception $e) {
            return null;
        }

        $forum = reset($forums);

        return $forum instanceof stdClass ? $forum : null;
    }

    /**
     * Resolve the course module info for a forum instance.
     *
     * @param stdClass $course Moodle course record.
     * @param int $forumid Forum instance id.
     * @return cm_info|null
     */
    private function resolve_course_module(stdClass $course, int $forumid): ?cm_info {
        try {
            $modinfo = get_fast_modinfo($course);
            $instances = $modinfo->get_instances_of('forum');
        } catch (Throwable $e) {
            return null;
        }

        foreach ($instances as $cm) {
            if ((int)$cm->instance === $forumid) {
                return $cm;
            }
        }

        return null;
    }

    /**
     * Return latest public candidate discussions for a forum.
     *
     * Final visibility checks happen after this query.
     *
     * @param stdClass $forum Forum record.
     * @param int $limit Public item limit.
     * @return array<int, stdClass>
     */
    private function get_latest_discussions(stdClass $forum, int $limit): array {
        global $DB;

        $postconditions = [];
        $params = [
            'forumid' => (int)$forum->id,
        ];

        if ($DB->get_manager()->field_exists('forum_posts', 'deleted')) {
            $postconditions[] = 'p.deleted = 0';
        }

        if ($DB->get_manager()->field_exists('forum_posts', 'privatereplyto')) {
            $postconditions[] = '(p.privatereplyto IS NULL OR p.privatereplyto = 0)';
        }

        $where = 'd.forum = :forumid';

        if (!empty($postconditions)) {
            $where .= ' AND ' . implode(' AND ', $postconditions);
        }

        $sql = "
            SELECT
                d.id AS discussionid,
                d.course,
                d.forum,
                d.name AS discussionname,
                d.groupid,
                d.userid AS discussionuserid,
                d.timemodified,
                d.usermodified,
                d.firstpost,
                p.id AS postid,
                p.subject,
                p.message,
                p.messageformat,
                p.created,
                p.modified,
                p.userid AS postuserid
              FROM {forum_discussions} d
              JOIN {forum_posts} p ON p.id = d.firstpost
             WHERE {$where}
          ORDER BY d.timemodified DESC, p.created DESC";

        $fetchlimit = max($limit, $limit * self::OVERFETCH_MULTIPLIER);

        try {
            return array_values($DB->get_records_sql($sql, $params, 0, $fetchlimit));
        } catch (dml_exception $e) {
            return [];
        }
    }

    /**
     * Whether the course can be used as a public source.
     *
     * Hidden courses fail closed even when the current user could see them.
     *
     * @param stdClass $course Moodle course record.
     * @return bool
     */
    private function is_public_course(stdClass $course): bool {
        return !empty($course->visible);
    }

    /**
     * Whether the course module can be used as a public source.
     *
     * Hidden modules fail closed even when the current user could see them.
     *
     * @param cm_info $cm Course module info.
     * @return bool
     */
    private function is_public_course_module(cm_info $cm): bool {
        if (empty($cm->visible)) {
            return false;
        }

        return !empty($cm->uservisible);
    }

    /**
     * Whether one discussion can be exported publicly.
     *
     * @param stdClass $forum Forum record.
     * @param stdClass $discussion Discussion candidate.
     * @param context $context Module context.
     * @return bool
     */
    private function can_export_discussion(stdClass $forum, stdClass $discussion, context $context): bool {
        global $USER;

        if (!function_exists('forum_user_can_see_discussion')) {
            return false;
        }

        try {
            $discussionrecord = (object)[
                'id' => (int)$discussion->discussionid,
                'course' => (int)$discussion->course,
                'forum' => (int)$discussion->forum,
                'name' => (string)$discussion->discussionname,
                'groupid' => (int)$discussion->groupid,
                'userid' => (int)$discussion->discussionuserid,
                'timemodified' => (int)$discussion->timemodified,
                'usermodified' => (int)$discussion->usermodified,
                'firstpost' => (int)$discussion->firstpost,
            ];

            return (bool)forum_user_can_see_discussion($forum, $discussionrecord, $context, $USER);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Export one discussion as a public dynamic block item.
     *
     * The dispatcher will normalise the final Mustache item shape.
     *
     * @param stdClass $discussion Discussion and first post row.
     * @param stdClass $forum Forum record.
     * @param context $context Module context.
     * @return array<string, string>
     */
    private function export_discussion(stdClass $discussion, stdClass $forum, context $context): array {
        $title = self::format_public_string(
            (string)($discussion->subject ?: $discussion->discussionname),
            $context
        );

        $summary = self::summarise_message(
            (string)$discussion->message,
            (int)$discussion->messageformat,
            $context
        );

        $timestamp = (int)($discussion->modified ?: $discussion->created ?: $discussion->timemodified);
        $url = new moodle_url('/mod/forum/discuss.php', ['d' => (int)$discussion->discussionid]);

        return [
            'title' => $title,
            'summary' => $summary,
            'date' => $timestamp > 0 ? userdate($timestamp, get_string('strftimedatetime', 'langconfig')) : '',
            'url' => $url->out(false),
            'source_label' => self::format_public_string((string)$forum->name, $context),
        ];
    }

    /**
     * Format a public Moodle string.
     *
     * @param string $value Raw value.
     * @param context $context Moodle context.
     * @return string
     */
    private static function format_public_string(string $value, context $context): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return trim(format_string($value, true, ['context' => $context]));
    }

    /**
     * Produce a plain public summary from a forum post message.
     *
     * @param string $message Raw message.
     * @param int $format Moodle text format.
     * @param context $context Moodle context.
     * @return string
     */
    private static function summarise_message(string $message, int $format, context $context): string {
        if (trim($message) === '') {
            return '';
        }

        $html = format_text(
            $message,
            $format,
            [
                'context' => $context,
                'trusted' => false,
                'noclean' => false,
                'overflowdiv' => false,
                'filter' => true,
            ]
        );

        if (function_exists('html_to_text')) {
            $plain = html_to_text($html, 0, false);
        } else {
            $plain = strip_tags($html);
        }

        $plain = trim(preg_replace('/\s+/u', ' ', $plain) ?? '');

        if ($plain === '') {
            return '';
        }

        return shorten_text(clean_param($plain, PARAM_TEXT), 280);
    }

    /**
     * Extract source config from a block.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @return array<string, mixed>
     */
    private static function get_source(array $block): array {
        $source = $block['source'] ?? [];

        return is_array($source) ? $source : [];
    }

    /**
     * Normalise a public block limit.
     *
     * @param mixed $value Raw limit.
     * @return int
     */
    private static function normalise_limit($value): int {
        if ($value === null || $value === '') {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int)$value;

        if ($limit < 0) {
            return 0;
        }

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * Clean a source key.
     *
     * @param string $value Raw key.
     * @return string
     */
    private static function clean_key(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean public source text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_text($value): string {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        return clean_param(strip_tags($value), PARAM_TEXT);
    }
}