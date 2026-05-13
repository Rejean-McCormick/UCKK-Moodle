<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Progress summary output model for block_uckk_dashboard.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uckk_dashboard\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use moodle_url;
use named_templatable;
use renderable;
use renderer_base;
use stdClass;

/**
 * Progress summary renderable.
 *
 * This class receives already-authorised dashboard data and normalises it for
 * blocks/uckk_dashboard/templates/progress_summary.mustache.
 *
 * It must not:
 * - query canonical progress tables directly;
 * - decide permissions;
 * - award badges;
 * - certify competencies;
 * - validate evidence;
 * - resolve integrity blocks.
 */
final class progress_summary implements renderable, named_templatable {
    /**
     * Canonical progress state: not started.
     */
    private const PROGRESS_NOT_STARTED = 'not_started';

    /**
     * Canonical progress state: in progress.
     */
    private const PROGRESS_IN_PROGRESS = 'in_progress';

    /**
     * Canonical progress state: pending review.
     */
    private const PROGRESS_PENDING_REVIEW = 'pending_review';

    /**
     * Canonical progress state: completed.
     */
    private const PROGRESS_COMPLETED = 'completed';

    /**
     * Canonical progress state: blocked.
     */
    private const PROGRESS_BLOCKED = 'blocked';

    /**
     * @var int User id this summary describes.
     */
    private int $userid;

    /**
     * @var int Context id used by the dashboard source.
     */
    private int $contextid;

    /**
     * @var string Display title.
     */
    private string $title;

    /**
     * @var string Optional display subtitle.
     */
    private string $subtitle;

    /**
     * @var int Overall progress percentage.
     */
    private int $overallpercent;

    /**
     * @var string Overall canonical progress status.
     */
    private string $status;

    /**
     * @var array<int, array<string, mixed>> Course rows.
     */
    private array $courses;

    /**
     * @var array<int, array<string, mixed>> Competency rows.
     */
    private array $competencies;

    /**
     * @var array<int, array<string, mixed>> Badge rows.
     */
    private array $badges;

    /**
     * @var array<int, array<string, mixed>> Alert rows.
     */
    private array $alerts;

    /**
     * @var moodle_url|null Optional URL for the full progress page.
     */
    private ?moodle_url $viewurl;

    /**
     * Constructor.
     *
     * Expected shape:
     *
     * [
     *     'userid' => 123,
     *     'contextid' => 456,
     *     'title' => 'My progress',
     *     'subtitle' => 'Tronc commun',
     *     'overallpercent' => 42,
     *     'status' => 'in_progress',
     *     'courses' => [...],
     *     'competencies' => [...],
     *     'badges' => [...],
     *     'alerts' => [...],
     *     'viewurl' => new moodle_url('/local/uckk/...')
     * ]
     *
     * @param array<string, mixed> $summary Pre-authorised dashboard summary data.
     */
    public function __construct(array $summary) {
        $this->userid = $this->clean_int($summary['userid'] ?? 0);
        $this->contextid = $this->clean_int($summary['contextid'] ?? 0);
        $this->title = $this->clean_string($summary['title'] ?? '');
        $this->subtitle = $this->clean_string($summary['subtitle'] ?? '');
        $this->overallpercent = $this->clean_percent($summary['overallpercent'] ?? 0);
        $this->status = $this->clean_status($summary['status'] ?? self::PROGRESS_NOT_STARTED);

        $this->courses = $this->normalise_courses($summary['courses'] ?? []);
        $this->competencies = $this->normalise_competencies($summary['competencies'] ?? []);
        $this->badges = $this->normalise_badges($summary['badges'] ?? []);
        $this->alerts = $this->normalise_alerts($summary['alerts'] ?? []);

        $this->viewurl = $this->normalise_url($summary['viewurl'] ?? null);

        if ($this->userid <= 0) {
            throw new coding_exception('progress_summary requires a valid userid.');
        }

        if ($this->contextid <= 0) {
            throw new coding_exception('progress_summary requires a valid contextid.');
        }
    }

    /**
     * Get the Mustache template name.
     *
     * @param renderer_base $renderer Renderer instance.
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'block_uckk_dashboard/progress_summary';
    }

    /**
     * Export data for progress_summary.mustache.
     *
     * @param renderer_base $output Renderer instance.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->userid = $this->userid;
        $data->contextid = $this->contextid;
        $data->title = $this->title;
        $data->subtitle = $this->subtitle;

        $data->overallpercent = $this->overallpercent;
        $data->overallpercentlabel = $this->overallpercent . '%';
        $data->status = $this->status;
        $data->statuslabel = $this->get_status_label($this->status);
        $data->statusclass = $this->get_status_class($this->status);

        $data->courses = $this->to_objects($this->courses);
        $data->competencies = $this->to_objects($this->competencies);
        $data->badges = $this->to_objects($this->badges);
        $data->alerts = $this->to_objects($this->alerts);

        $data->hascourses = !empty($this->courses);
        $data->hascompetencies = !empty($this->competencies);
        $data->hasbadges = !empty($this->badges);
        $data->hasalerts = !empty($this->alerts);
        $data->hasviewurl = $this->viewurl !== null;

        $data->coursecount = count($this->courses);
        $data->competencycount = count($this->competencies);
        $data->badgecount = count($this->badges);
        $data->alertcount = count($this->alerts);

        $data->completedcoursecount = $this->count_by_status($this->courses, self::PROGRESS_COMPLETED);
        $data->completedcompetencycount = $this->count_by_status($this->competencies, self::PROGRESS_COMPLETED);
        $data->earnedbadgecount = $this->count_earned_badges($this->badges);

        $data->viewurl = $this->viewurl ? $this->viewurl->out(false) : '';

        return $data;
    }

    /**
     * Clean an integer value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function clean_int(mixed $value): int {
        return max(0, (int) $value);
    }

    /**
     * Clean a percentage value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function clean_percent(mixed $value): int {
        return min(100, max(0, (int) $value));
    }

    /**
     * Clean a display string.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function clean_string(mixed $value): string {
        return trim((string) $value);
    }

    /**
     * Clean a progress status.
     *
     * @param mixed $value Raw status.
     * @return string
     */
    private function clean_status(mixed $value): string {
        $status = $this->clean_string($value);

        $allowed = [
            self::PROGRESS_NOT_STARTED,
            self::PROGRESS_IN_PROGRESS,
            self::PROGRESS_PENDING_REVIEW,
            self::PROGRESS_COMPLETED,
            self::PROGRESS_BLOCKED,
        ];

        return in_array($status, $allowed, true) ? $status : self::PROGRESS_NOT_STARTED;
    }

    /**
     * Normalise a Moodle URL.
     *
     * @param mixed $value Raw URL value.
     * @return moodle_url|null
     */
    private function normalise_url(mixed $value): ?moodle_url {
        if ($value instanceof moodle_url) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return new moodle_url($value);
        }

        return null;
    }

    /**
     * Normalise course progress rows.
     *
     * @param mixed $courses Raw course data.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_courses(mixed $courses): array {
        if (!is_array($courses)) {
            return [];
        }

        $normalised = [];

        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $status = $this->clean_status($course['status'] ?? self::PROGRESS_NOT_STARTED);
            $percent = $this->clean_percent($course['percent'] ?? 0);
            $url = $this->normalise_url($course['url'] ?? null);

            $normalised[] = [
                'id' => $this->clean_int($course['id'] ?? 0),
                'shortname' => $this->clean_string($course['shortname'] ?? ''),
                'fullname' => $this->clean_string($course['fullname'] ?? ''),
                'status' => $status,
                'statuslabel' => $this->get_status_label($status),
                'statusclass' => $this->get_status_class($status),
                'percent' => $percent,
                'percentlabel' => $percent . '%',
                'url' => $url ? $url->out(false) : '',
                'hasurl' => $url !== null,
                'current' => !empty($course['current']),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise competency progress rows.
     *
     * @param mixed $competencies Raw competency data.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_competencies(mixed $competencies): array {
        if (!is_array($competencies)) {
            return [];
        }

        $normalised = [];

        foreach ($competencies as $competency) {
            if (!is_array($competency)) {
                continue;
            }

            $status = $this->clean_status($competency['status'] ?? self::PROGRESS_NOT_STARTED);
            $rating = $this->clean_int($competency['rating'] ?? 0);

            $normalised[] = [
                'id' => $this->clean_int($competency['id'] ?? 0),
                'idnumber' => $this->clean_string($competency['idnumber'] ?? ''),
                'shortname' => $this->clean_string($competency['shortname'] ?? ''),
                'status' => $status,
                'statuslabel' => $this->get_status_label($status),
                'statusclass' => $this->get_status_class($status),
                'rating' => min(5, $rating),
                'ratinglabel' => $this->get_competency_rating_label(min(5, $rating)),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise badge rows.
     *
     * @param mixed $badges Raw badge data.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_badges(mixed $badges): array {
        if (!is_array($badges)) {
            return [];
        }

        $normalised = [];

        foreach ($badges as $badge) {
            if (!is_array($badge)) {
                continue;
            }

            $url = $this->normalise_url($badge['url'] ?? null);
            $earned = !empty($badge['earned']);

            $normalised[] = [
                'id' => $this->clean_int($badge['id'] ?? 0),
                'name' => $this->clean_string($badge['name'] ?? ''),
                'description' => $this->clean_string($badge['description'] ?? ''),
                'earned' => $earned,
                'earnedlabel' => $earned
                    ? get_string('badgeearned', 'block_uckk_dashboard')
                    : get_string('badgenotearned', 'block_uckk_dashboard'),
                'url' => $url ? $url->out(false) : '',
                'hasurl' => $url !== null,
            ];
        }

        return $normalised;
    }

    /**
     * Normalise alert rows.
     *
     * Alerts are display-only dashboard notices. Integrity decisions and access
     * rules must already have been resolved before constructing this object.
     *
     * @param mixed $alerts Raw alert data.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_alerts(mixed $alerts): array {
        if (!is_array($alerts)) {
            return [];
        }

        $normalised = [];

        foreach ($alerts as $alert) {
            if (!is_array($alert)) {
                continue;
            }

            $level = $this->clean_string($alert['level'] ?? 'info');

            if (!in_array($level, ['info', 'warning', 'danger', 'success'], true)) {
                $level = 'info';
            }

            $normalised[] = [
                'level' => $level,
                'levelclass' => 'alert-' . $level,
                'message' => $this->clean_string($alert['message'] ?? ''),
            ];
        }

        return array_values(array_filter($normalised, static function(array $alert): bool {
            return $alert['message'] !== '';
        }));
    }

    /**
     * Count rows by canonical progress status.
     *
     * @param array<int, array<string, mixed>> $rows Rows.
     * @param string $status Status to count.
     * @return int
     */
    private function count_by_status(array $rows, string $status): int {
        return count(array_filter($rows, static function(array $row) use ($status): bool {
            return ($row['status'] ?? '') === $status;
        }));
    }

    /**
     * Count earned badges.
     *
     * @param array<int, array<string, mixed>> $badges Badge rows.
     * @return int
     */
    private function count_earned_badges(array $badges): int {
        return count(array_filter($badges, static function(array $badge): bool {
            return !empty($badge['earned']);
        }));
    }

    /**
     * Convert rows to stdClass objects for Mustache.
     *
     * @param array<int, array<string, mixed>> $rows Rows.
     * @return array<int, stdClass>
     */
    private function to_objects(array $rows): array {
        return array_map(static function(array $row): stdClass {
            return (object) $row;
        }, $rows);
    }

    /**
     * Get a display label for a canonical progress status.
     *
     * @param string $status Canonical progress status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return match ($status) {
            self::PROGRESS_IN_PROGRESS => get_string('progressinprogress', 'block_uckk_dashboard'),
            self::PROGRESS_PENDING_REVIEW => get_string('progresspendingreview', 'block_uckk_dashboard'),
            self::PROGRESS_COMPLETED => get_string('progresscompleted', 'block_uckk_dashboard'),
            self::PROGRESS_BLOCKED => get_string('progressblocked', 'block_uckk_dashboard'),
            default => get_string('progressnotstarted', 'block_uckk_dashboard'),
        };
    }

    /**
     * Get a CSS modifier class for a canonical progress status.
     *
     * @param string $status Canonical progress status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return match ($status) {
            self::PROGRESS_IN_PROGRESS => 'uckk-status-in-progress',
            self::PROGRESS_PENDING_REVIEW => 'uckk-status-pending-review',
            self::PROGRESS_COMPLETED => 'uckk-status-completed',
            self::PROGRESS_BLOCKED => 'uckk-status-blocked',
            default => 'uckk-status-not-started',
        };
    }

    /**
     * Get a display label for the UCKK competency scale.
     *
     * @param int $rating Rating from 0 to 5.
     * @return string
     */
    private function get_competency_rating_label(int $rating): string {
        return match ($rating) {
            1 => get_string('competencyrating1', 'block_uckk_dashboard'),
            2 => get_string('competencyrating2', 'block_uckk_dashboard'),
            3 => get_string('competencyrating3', 'block_uckk_dashboard'),
            4 => get_string('competencyrating4', 'block_uckk_dashboard'),
            5 => get_string('competencyrating5', 'block_uckk_dashboard'),
            default => get_string('competencyrating0', 'block_uckk_dashboard'),
        };
    }
}