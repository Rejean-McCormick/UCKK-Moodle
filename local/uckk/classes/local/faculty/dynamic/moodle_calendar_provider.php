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
 * Moodle calendar dynamic block provider for public UCKK faculty pages.
 *
 * This provider reads public Moodle calendar events attached to the category
 * declared in a Faculty Profile dynamic block source:
 *
 * {
 *     "provider": "moodle_calendar",
 *     "category_idnumber": "UCKK-GJS"
 * }
 *
 * It is intentionally read-only and fail-closed. It does not expose private
 * user events, grades, completion data, enrolment data, participants, or hidden
 * courses/categories.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

use context_coursecat;
use dml_exception;
use moodle_url;
use stdClass;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Public Moodle calendar provider.
 *
 * @package local_uckk
 */
final class moodle_calendar_provider implements provider_interface {
    /** Source provider name. */
    private const PROVIDER = 'moodle_calendar';

    /** Default number of events to read. */
    private const DEFAULT_LIMIT = 5;

    /** Maximum public events read from one block. */
    private const MAX_LIMIT = 50;

    /** Default number of days ahead for public calendar lookup. */
    private const DEFAULT_DAYS_AHEAD = 180;

    /** Maximum number of days ahead for public calendar lookup. */
    private const MAX_DAYS_AHEAD = 366;

    /** Overfetch multiplier before public filtering. */
    private const OVERFETCH_MULTIPLIER = 3;

    /**
     * Return public calendar items for one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Resolved and normalised faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed> Payload containing public items.
     */
    public function get_items(array $block, array $faculty, array $pagecontext = []): array {
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

            $category = $this->resolve_category($source, $faculty);

            if ($category === null || !$this->is_public_category($category)) {
                return ['items' => $items];
            }

            $courseids = $this->get_public_course_ids_in_category((int)$category->id);
            $events = $this->get_candidate_events($category, $courseids, $source, $limit);

            foreach ($events as $event) {
                if (!$this->can_export_event($event, (int)$category->id, $courseids)) {
                    continue;
                }

                $items[] = $this->export_event($event);

                if (count($items) >= $limit) {
                    break;
                }
            }
        } catch (Throwable $e) {
            debugging('local_uckk Moodle calendar dynamic block provider failed.', DEBUG_DEVELOPER);
            return ['items' => []];
        }

        return ['items' => $items];
    }

    /**
     * Resolve Moodle category from the documented source config.
     *
     * Source category_idnumber is preferred. The faculty moodle.category_idnumber
     * fallback is already a documented Faculty Profile field.
     *
     * @param array<string, mixed> $source Dynamic block source.
     * @param array<string, mixed> $faculty Faculty profile.
     * @return stdClass|null
     */
    private function resolve_category(array $source, array $faculty): ?stdClass {
        global $DB;

        $categoryidnumber = self::clean_text($source['category_idnumber'] ?? '');

        if ($categoryidnumber === '' && isset($faculty['moodle']) && is_array($faculty['moodle'])) {
            $categoryidnumber = self::clean_text($faculty['moodle']['category_idnumber'] ?? '');
        }

        if ($categoryidnumber === '') {
            return null;
        }

        try {
            $category = $DB->get_record(
                'course_categories',
                ['idnumber' => $categoryidnumber],
                '*',
                IGNORE_MISSING
            );
        } catch (dml_exception $e) {
            return null;
        }

        return $category instanceof stdClass ? $category : null;
    }

    /**
     * Whether the category can be used as a public source.
     *
     * Hidden categories fail closed even when the current user could see them.
     *
     * @param stdClass $category Moodle course category record.
     * @return bool
     */
    private function is_public_category(stdClass $category): bool {
        if (property_exists($category, 'visible') && empty($category->visible)) {
            return false;
        }

        return true;
    }

    /**
     * Return visible course ids directly inside the category.
     *
     * Direct category membership is used deliberately. Recursive category event
     * aggregation can be added later only if documented in the contract.
     *
     * @param int $categoryid Moodle category id.
     * @return int[]
     */
    private function get_public_course_ids_in_category(int $categoryid): array {
        global $DB;

        if ($categoryid <= 0) {
            return [];
        }

        try {
            $records = $DB->get_records(
                'course',
                [
                    'category' => $categoryid,
                    'visible' => 1,
                ],
                'sortorder ASC, id ASC',
                'id'
            );
        } catch (dml_exception $e) {
            return [];
        }

        return array_map('intval', array_keys($records));
    }

    /**
     * Get candidate category and course calendar events.
     *
     * @param stdClass $category Moodle course category record.
     * @param int[] $courseids Public course ids in this category.
     * @param array<string, mixed> $source Dynamic block source.
     * @param int $limit Public item limit.
     * @return array<int, stdClass>
     */
    private function get_candidate_events(stdClass $category, array $courseids, array $source, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('event')) {
            return [];
        }

        $now = time();
        $daysahead = self::normalise_days_ahead($source['days_ahead'] ?? null);
        $maxstart = $now + ($daysahead * DAYSECS);

        $params = [
            'now' => $now,
            'maxstart' => $maxstart,
            'categoryid' => (int)$category->id,
        ];

        $conditions = [
            'e.timestart >= :now',
            'e.timestart <= :maxstart',
        ];

        if ($DB->get_manager()->field_exists('event', 'visible')) {
            $conditions[] = 'e.visible = 1';
        }

        if ($DB->get_manager()->field_exists('event', 'userid')) {
            $conditions[] = '(e.userid IS NULL OR e.userid = 0)';
        }

        $sourceconditions = [];

        if ($DB->get_manager()->field_exists('event', 'categoryid')) {
            $sourceconditions[] = 'e.categoryid = :categoryid';
        }

        if (!empty($courseids)) {
            [$courseinsql, $courseparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
            $sourceconditions[] = "e.courseid {$courseinsql}";
            $params += $courseparams;
        }

        if (empty($sourceconditions)) {
            return [];
        }

        $conditions[] = '(' . implode(' OR ', $sourceconditions) . ')';

        $sql = '
            SELECT e.*
              FROM {event} e
             WHERE ' . implode(' AND ', $conditions) . '
          ORDER BY e.timestart ASC, e.id ASC';

        $fetchlimit = max($limit, $limit * self::OVERFETCH_MULTIPLIER);

        try {
            return array_values($DB->get_records_sql($sql, $params, 0, $fetchlimit));
        } catch (dml_exception $e) {
            return [];
        }
    }

    /**
     * Whether one calendar event can be exported publicly.
     *
     * @param stdClass $event Moodle calendar event record.
     * @param int $categoryid Resolved public category id.
     * @param int[] $courseids Public course ids.
     * @return bool
     */
    private function can_export_event(stdClass $event, int $categoryid, array $courseids): bool {
        if (property_exists($event, 'visible') && empty($event->visible)) {
            return false;
        }

        if (property_exists($event, 'userid') && (int)$event->userid > 0) {
            return false;
        }

        if (property_exists($event, 'categoryid') && (int)$event->categoryid > 0) {
            return (int)$event->categoryid === $categoryid;
        }

        if (property_exists($event, 'courseid') && (int)$event->courseid > 0) {
            return in_array((int)$event->courseid, $courseids, true);
        }

        return false;
    }

    /**
     * Export one event as a public dynamic block item.
     *
     * The dispatcher normalises the final Mustache shape for events:
     * title, date_start, date_end, location, url.
     *
     * @param stdClass $event Moodle calendar event record.
     * @return array<string, string>
     */
    private function export_event(stdClass $event): array {
        $start = (int)($event->timestart ?? 0);
        $duration = max(0, (int)($event->timeduration ?? 0));
        $end = $duration > 0 ? $start + $duration : 0;

        return [
            'title' => self::format_public_event_name((string)($event->name ?? '')),
            'date_start' => $start > 0 ? userdate($start, get_string('strftimedatetime', 'langconfig')) : '',
            'date_end' => $end > 0 ? userdate($end, get_string('strftimedatetime', 'langconfig')) : '',
            'location' => self::clean_text($event->location ?? ''),
            'url' => self::event_url($event),
        ];
    }

    /**
     * Build a public Moodle calendar URL for an event.
     *
     * @param stdClass $event Moodle calendar event record.
     * @return string
     */
    private static function event_url(stdClass $event): string {
        $eventid = (int)($event->id ?? 0);

        if ($eventid <= 0) {
            return '';
        }

        $url = new moodle_url('/calendar/view.php', [
            'view' => 'day',
            'time' => (int)($event->timestart ?? time()),
        ]);

        return $url->out(false);
    }

    /**
     * Clean a public event name.
     *
     * @param string $value Raw event name.
     * @return string
     */
    private static function format_public_event_name(string $value): string {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return trim(format_string($value, true, ['context' => context_coursecat::instance(0, IGNORE_MISSING)]));
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
     * Normalise the number of days ahead for the calendar lookup.
     *
     * @param mixed $value Raw days ahead.
     * @return int
     */
    private static function normalise_days_ahead($value): int {
        if ($value === null || $value === '') {
            return self::DEFAULT_DAYS_AHEAD;
        }

        $days = (int)$value;

        if ($days <= 0) {
            return self::DEFAULT_DAYS_AHEAD;
        }

        return min($days, self::MAX_DAYS_AHEAD);
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