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
 * Moodle category dynamic block provider for public UCKK faculty pages.
 *
 * This provider reads visible Moodle courses from the course category declared
 * in a Faculty Profile dynamic block source:
 *
 * {
 *     "provider": "moodle_category",
 *     "category_idnumber": "UCKK-GJS"
 * }
 *
 * It is intentionally read-only and fail-closed. It does not expose grades,
 * completion data, enrolment data, participants, hidden courses, hidden
 * categories, or private course state.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

use context_course;
use dml_exception;
use moodle_url;
use stdClass;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Public Moodle category course-list provider.
 *
 * @package local_uckk
 */
final class moodle_category_provider implements provider_interface {
    /** Source provider name. */
    private const PROVIDER = 'moodle_category';

    /** Default number of courses to read. */
    private const DEFAULT_LIMIT = 20;

    /** Maximum public courses read from one block. */
    private const MAX_LIMIT = 50;

    /** Public item availability: open. */
    private const AVAILABILITY_PUBLIC = 'public';

    /** Public item availability: login required. */
    private const AVAILABILITY_LOGIN_REQUIRED = 'login_required';

    /**
     * Return public course items for one dynamic block.
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

            foreach ($this->get_visible_courses((int)$category->id, $limit) as $course) {
                if (!$this->can_export_course($course)) {
                    continue;
                }

                $items[] = $this->export_course($course);

                if (count($items) >= $limit) {
                    break;
                }
            }
        } catch (Throwable $e) {
            debugging('local_uckk Moodle category dynamic block provider failed.', DEBUG_DEVELOPER);
            return ['items' => []];
        }

        return ['items' => $items];
    }

    /**
     * Resolve Moodle category from the documented source config.
     *
     * Source category_idnumber is preferred. The faculty moodle.category_idnumber
     * fallback is already part of the Faculty Profile Moodle mapping.
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
     * Hidden categories fail closed even when the current user can see them.
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
     * Return visible courses directly inside the category.
     *
     * Direct category membership is deliberate. Recursive category traversal is
     * not added here because it is not part of the current dynamic block source
     * contract.
     *
     * @param int $categoryid Moodle category id.
     * @param int $limit Public item limit.
     * @return array<int, stdClass>
     */
    private function get_visible_courses(int $categoryid, int $limit): array {
        global $DB;

        if ($categoryid <= 0 || $limit <= 0) {
            return [];
        }

        try {
            $courses = $DB->get_records(
                'course',
                [
                    'category' => $categoryid,
                    'visible' => 1,
                ],
                'sortorder ASC, fullname ASC, id ASC',
                '*',
                0,
                $limit
            );
        } catch (dml_exception $e) {
            return [];
        }

        return array_values($courses);
    }

    /**
     * Whether one course may be exported to the public faculty page.
     *
     * @param stdClass $course Moodle course record.
     * @return bool
     */
    private function can_export_course(stdClass $course): bool {
        if (empty($course->visible)) {
            return false;
        }

        if ((int)($course->id ?? 0) <= 1) {
            return false;
        }

        return true;
    }

    /**
     * Export one Moodle course as a public dynamic block item.
     *
     * The dispatcher normalises course-list items to:
     * cours_id, fullname, summary, url, availability_label.
     *
     * @param stdClass $course Moodle course record.
     * @return array<string, string>
     */
    private function export_course(stdClass $course): array {
        $context = context_course::instance((int)$course->id, IGNORE_MISSING);
        $availability = $this->course_availability($course, $context);
        $url = '';

        if ($availability === self::AVAILABILITY_PUBLIC) {
            $url = (new moodle_url('/course/view.php', ['id' => (int)$course->id]))->out(false);
        }

        return [
            'cours_id' => self::course_public_code($course),
            'fullname' => self::format_course_name($course, $context),
            'summary' => self::format_course_summary($course, $context),
            'url' => $url,
            'availability_label' => self::availability_label($availability),
        ];
    }

    /**
     * Determine the public availability state of a course.
     *
     * A visible course can still require login/enrolment. In that case, the
     * public page exports the course card without a direct course URL.
     *
     * @param stdClass $course Moodle course record.
     * @param context_course|null $context Course context.
     * @return string
     */
    private function course_availability(stdClass $course, ?context_course $context): string {
        if ($context === null) {
            return self::AVAILABILITY_LOGIN_REQUIRED;
        }

        if (isloggedin() && !isguestuser()) {
            return self::AVAILABILITY_PUBLIC;
        }

        if (!empty($course->guest) || $this->has_guest_enrolment((int)$course->id)) {
            return self::AVAILABILITY_PUBLIC;
        }

        return self::AVAILABILITY_LOGIN_REQUIRED;
    }

    /**
     * Whether the course has an enabled guest enrolment method.
     *
     * @param int $courseid Moodle course id.
     * @return bool
     */
    private function has_guest_enrolment(int $courseid): bool {
        global $DB;

        if ($courseid <= 0 || !$DB->get_manager()->table_exists('enrol')) {
            return false;
        }

        try {
            return $DB->record_exists(
                'enrol',
                [
                    'courseid' => $courseid,
                    'enrol' => 'guest',
                    'status' => 0,
                ]
            );
        } catch (dml_exception $e) {
            return false;
        }
    }

    /**
     * Return the public course code.
     *
     * @param stdClass $course Moodle course record.
     * @return string
     */
    private static function course_public_code(stdClass $course): string {
        $idnumber = self::clean_text($course->idnumber ?? '');

        if ($idnumber !== '') {
            return $idnumber;
        }

        return self::clean_text($course->shortname ?? '');
    }

    /**
     * Format a public course name.
     *
     * @param stdClass $course Moodle course record.
     * @param context_course|null $context Course context.
     * @return string
     */
    private static function format_course_name(stdClass $course, ?context_course $context): string {
        $name = (string)($course->fullname ?? '');

        if ($name === '') {
            $name = (string)($course->shortname ?? '');
        }

        if ($name === '') {
            return '';
        }

        if ($context !== null) {
            return trim(format_string($name, true, ['context' => $context]));
        }

        return self::clean_text($name);
    }

    /**
     * Format a public course summary.
     *
     * @param stdClass $course Moodle course record.
     * @param context_course|null $context Course context.
     * @return string
     */
    private static function format_course_summary(stdClass $course, ?context_course $context): string {
        $summary = (string)($course->summary ?? '');

        if (trim($summary) === '') {
            return '';
        }

        if ($context !== null) {
            $html = format_text(
                $summary,
                (int)($course->summaryformat ?? FORMAT_HTML),
                [
                    'context' => $context,
                    'trusted' => false,
                    'noclean' => false,
                    'overflowdiv' => false,
                    'filter' => true,
                ]
            );
        } else {
            $html = $summary;
        }

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
     * Return the public availability label.
     *
     * @param string $availability Availability state.
     * @return string
     */
    private static function availability_label(string $availability): string {
        if ($availability === self::AVAILABILITY_PUBLIC) {
            return '';
        }

        return get_string('loginrequired', 'moodle');
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