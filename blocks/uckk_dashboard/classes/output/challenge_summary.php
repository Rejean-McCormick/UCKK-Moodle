<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace block_uckk_dashboard\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable challenge summary for the UCKK dashboard block.
 *
 * This class prepares display data only. Challenge records, submissions,
 * reviews, integrity states, archive exports, badge links, and competency links
 * remain owned by mod_uckkchallenge and related UCKK services.
 *
 * Expected item shape:
 * [
 *     'id' => 12,
 *     'title' => 'Map a hidden rule',
 *     'status' => 'open',
 *     'statuslabel' => 'Open',
 *     'coursename' => 'UCKK-TC101',
 *     'duedate' => 1773427200,
 *     'url' => new moodle_url('/mod/uckkchallenge/view.php', ['id' => 45]),
 *     'actionurl' => new moodle_url('/mod/uckkchallenge/view.php', ['id' => 45]),
 *     'actionlabel' => 'Continue',
 *     'canreview' => false,
 *     'hasintegritynotice' => false,
 *     'integritynotice' => '',
 * ]
 *
 * @package    block_uckk_dashboard
 * @category   output
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_summary implements renderable, templatable {
    /**
     * Canonical challenge statuses displayed by the dashboard.
     */
    private const STATUS_DRAFT = 'draft';
    private const STATUS_PUBLISHED = 'published';
    private const STATUS_OPEN = 'open';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_UNDER_REVIEW = 'under_review';
    private const STATUS_INTEGRITY_REVIEW = 'integrity_review';
    private const STATUS_REVISION_REQUIRED = 'revision_required';
    private const STATUS_RESUBMITTED = 'resubmitted';
    private const STATUS_VALIDATED = 'validated';
    private const STATUS_ARCHIVED = 'archived';
    private const STATUS_CONTESTED = 'contested';
    private const STATUS_INVALIDATED = 'invalidated';
    private const STATUS_CLOSED = 'closed';
    private const STATUS_EXPIRED = 'expired';
    private const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * User id for whom this summary is rendered.
     *
     * @var int
     */
    private int $userid;

    /**
     * Prepared challenge rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $challenges;

    /**
     * URL to the full challenge list.
     *
     * @var moodle_url|null
     */
    private ?moodle_url $viewallurl;

    /**
     * Whether the current viewer may see staff review affordances.
     *
     * @var bool
     */
    private bool $canreview;

    /**
     * Optional heading override.
     *
     * @var string|null
     */
    private ?string $heading;

    /**
     * Constructor.
     *
     * @param int $userid User id whose dashboard is displayed.
     * @param array<int, array<string, mixed>|stdClass> $challenges Permission-filtered challenge rows.
     * @param moodle_url|null $viewallurl Optional URL to all visible challenges.
     * @param bool $canreview Whether staff review indicators may be displayed.
     * @param string|null $heading Optional heading override.
     */
    public function __construct(
        int $userid,
        array $challenges = [],
        ?moodle_url $viewallurl = null,
        bool $canreview = false,
        ?string $heading = null
    ) {
        $this->userid = $userid;
        $this->challenges = array_map([$this, 'normalise_challenge'], $challenges);
        $this->viewallurl = $viewallurl;
        $this->canreview = $canreview;
        $this->heading = $heading;
    }

    /**
     * Export data for the challenge summary Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass JSON-serialisable template context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->userid = $this->userid;
        $data->heading = $this->heading ?? get_string('challengesummary', 'block_uckk_dashboard');
        $data->haschallenges = !empty($this->challenges);
        $data->emptylabel = get_string('challenge:none', 'block_uckk_dashboard');
        $data->canreview = $this->canreview;

        $data->counts = $this->build_counts();
        $data->statcards = $this->build_stat_cards($data->counts);
        $data->challenges = $this->build_challenge_rows();
        $data->hasurgentitems = $this->has_urgent_items($data->challenges);

        $data->hasviewallurl = $this->viewallurl !== null;
        $data->viewallurl = $this->viewallurl ? $this->viewallurl->out(false) : '';
        $data->viewalllabel = get_string('challenge:viewall', 'block_uckk_dashboard');

        return $data;
    }

    /**
     * Convert an incoming challenge row to a predictable internal array.
     *
     * @param array<string, mixed>|stdClass $challenge Raw challenge row.
     * @return array<string, mixed>
     */
    private function normalise_challenge(array|stdClass $challenge): array {
        $row = (array) $challenge;

        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_OPEN));
        $title = trim((string)($row['title'] ?? $row['name'] ?? ''));

        if ($title === '') {
            $title = get_string('card:challenges', 'block_uckk_dashboard');
        }

        $url = $this->normalise_url($row['url'] ?? null);
        $actionurl = $this->normalise_url($row['actionurl'] ?? $url);

        $duedate = isset($row['duedate']) ? (int)$row['duedate'] : 0;
        $duedatelabel = (string)($row['duedatelabel'] ?? '');
        if ($duedate > 0 && $duedatelabel === '') {
            $duedatelabel = userdate($duedate, get_string('strftimedatefullshort', 'langconfig'));
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'cmid' => (int)($row['cmid'] ?? 0),
            'courseid' => (int)($row['courseid'] ?? 0),
            'title' => format_string($title),
            'status' => $status,
            'statuslabel' => $this->get_status_label($status, (string)($row['statuslabel'] ?? '')),
            'statusclass' => $this->get_status_class($status),
            'coursename' => format_string((string)($row['coursename'] ?? '')),
            'hascoursename' => trim((string)($row['coursename'] ?? '')) !== '',
            'duedate' => $duedate,
            'duedatelabel' => $duedatelabel,
            'hasduedate' => $duedate > 0 || $duedatelabel !== '',
            'isoverdue' => $this->is_overdue($duedate, $status),
            'isurgent' => $this->is_urgent($duedate, $status),
            'url' => $url,
            'hasurl' => $url !== '',
            'actionurl' => $actionurl,
            'hasactionurl' => $actionurl !== '',
            'actionlabel' => (string)($row['actionlabel'] ?? $this->get_default_action_label($status)),
            'canreview' => $this->canreview && !empty($row['canreview']),
            'hasintegritynotice' => !empty($row['hasintegritynotice']) || trim((string)($row['integritynotice'] ?? '')) !== '',
            'integritynotice' => format_string((string)($row['integritynotice'] ?? '')),
            'progresspercent' => $this->normalise_percent($row['progresspercent'] ?? null),
            'hasprogress' => isset($row['progresspercent']),
        ];
    }

    /**
     * Build status counts for the visible challenge set.
     *
     * @return stdClass
     */
    private function build_counts(): stdClass {
        $counts = (object)[
            'total' => count($this->challenges),
            'active' => 0,
            'pendingreview' => 0,
            'revisionrequired' => 0,
            'validated' => 0,
            'contested' => 0,
            'archived' => 0,
            'integrityreview' => 0,
            'overdue' => 0,
        ];

        foreach ($this->challenges as $challenge) {
            $status = $challenge['status'];

            if (in_array($status, [self::STATUS_PUBLISHED, self::STATUS_OPEN, self::STATUS_SUBMITTED, self::STATUS_RESUBMITTED], true)) {
                $counts->active++;
            }

            if ($status === self::STATUS_UNDER_REVIEW) {
                $counts->pendingreview++;
            }

            if ($status === self::STATUS_REVISION_REQUIRED) {
                $counts->revisionrequired++;
            }

            if ($status === self::STATUS_VALIDATED) {
                $counts->validated++;
            }

            if ($status === self::STATUS_CONTESTED) {
                $counts->contested++;
            }

            if ($status === self::STATUS_ARCHIVED) {
                $counts->archived++;
            }

            if ($status === self::STATUS_INTEGRITY_REVIEW) {
                $counts->integrityreview++;
            }

            if (!empty($challenge['isoverdue'])) {
                $counts->overdue++;
            }
        }

        return $counts;
    }

    /**
     * Build dashboard statistic cards.
     *
     * @param stdClass $counts Status counts.
     * @return array<int, stdClass>
     */
    private function build_stat_cards(stdClass $counts): array {
        return [
            $this->make_stat_card('active', get_string('challenge:active', 'block_uckk_dashboard'), $counts->active),
            $this->make_stat_card('pendingreview', get_string('challenge:pendingreview', 'block_uckk_dashboard'), $counts->pendingreview),
            $this->make_stat_card('revisionrequired', get_string('challenge:revisionrequired', 'block_uckk_dashboard'), $counts->revisionrequired),
            $this->make_stat_card('validated', get_string('challenge:validated', 'block_uckk_dashboard'), $counts->validated),
            $this->make_stat_card('contested', get_string('challenge:contested', 'block_uckk_dashboard'), $counts->contested),
            $this->make_stat_card('archived', get_string('challenge:archived', 'block_uckk_dashboard'), $counts->archived),
        ];
    }

    /**
     * Build one statistic card.
     *
     * @param string $key Stable card key.
     * @param string $label Human-readable card label.
     * @param int $count Count.
     * @return stdClass
     */
    private function make_stat_card(string $key, string $label, int $count): stdClass {
        return (object)[
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'hasitems' => $count > 0,
            'class' => 'block-uckk-dashboard-challenge-summary__stat--' . $key,
        ];
    }

    /**
     * Build exported challenge rows.
     *
     * @return array<int, stdClass>
     */
    private function build_challenge_rows(): array {
        $rows = [];

        foreach ($this->challenges as $challenge) {
            $rows[] = (object)[
                'id' => $challenge['id'],
                'cmid' => $challenge['cmid'],
                'courseid' => $challenge['courseid'],
                'title' => $challenge['title'],
                'status' => $challenge['status'],
                'statuslabel' => $challenge['statuslabel'],
                'statusclass' => $challenge['statusclass'],
                'coursename' => $challenge['coursename'],
                'hascoursename' => $challenge['hascoursename'],
                'duedate' => $challenge['duedate'],
                'duedatelabel' => $challenge['duedatelabel'],
                'hasduedate' => $challenge['hasduedate'],
                'isoverdue' => $challenge['isoverdue'],
                'isurgent' => $challenge['isurgent'],
                'url' => $challenge['url'],
                'hasurl' => $challenge['hasurl'],
                'actionurl' => $challenge['actionurl'],
                'hasactionurl' => $challenge['hasactionurl'],
                'actionlabel' => $challenge['actionlabel'],
                'canreview' => $challenge['canreview'],
                'hasintegritynotice' => $challenge['hasintegritynotice'],
                'integritynotice' => $challenge['integritynotice'],
                'progresspercent' => $challenge['progresspercent'],
                'hasprogress' => $challenge['hasprogress'],
            ];
        }

        return $rows;
    }

    /**
     * Determine whether at least one challenge requires urgent attention.
     *
     * @param array<int, stdClass> $challenges Exported challenge rows.
     * @return bool
     */
    private function has_urgent_items(array $challenges): bool {
        foreach ($challenges as $challenge) {
            if (!empty($challenge->isoverdue) || !empty($challenge->isurgent)) {
                return true;
            }

            if (in_array($challenge->status, [self::STATUS_REVISION_REQUIRED, self::STATUS_CONTESTED], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a safe canonical status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_OPEN,
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_INTEGRITY_REVIEW,
            self::STATUS_REVISION_REQUIRED,
            self::STATUS_RESUBMITTED,
            self::STATUS_VALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
            self::STATUS_EXPIRED,
            self::STATUS_WITHDRAWN,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_OPEN;
    }

    /**
     * Return a display label for a challenge status.
     *
     * @param string $status Canonical status.
     * @param string $fallback Optional fallback label.
     * @return string
     */
    private function get_status_label(string $status, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $stringkey = 'challenge_status:' . str_replace('_', '', $status);

        if (get_string_manager()->string_exists($stringkey, 'block_uckk_dashboard')) {
            return get_string($stringkey, 'block_uckk_dashboard');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Return CSS class suffix for a status.
     *
     * @param string $status Canonical status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return 'status-' . str_replace('_', '-', $status);
    }

    /**
     * Return the default action label for a challenge state.
     *
     * @param string $status Canonical status.
     * @return string
     */
    private function get_default_action_label(string $status): string {
        if (in_array($status, [self::STATUS_UNDER_REVIEW, self::STATUS_INTEGRITY_REVIEW], true)) {
            return get_string('action:review', 'block_uckk_dashboard');
        }

        if ($status === self::STATUS_REVISION_REQUIRED) {
            return get_string('action:submit', 'block_uckk_dashboard');
        }

        if (in_array($status, [self::STATUS_ARCHIVED, self::STATUS_VALIDATED, self::STATUS_CLOSED], true)) {
            return get_string('action:viewdetails', 'block_uckk_dashboard');
        }

        return get_string('action:continue', 'block_uckk_dashboard');
    }

    /**
     * Normalize a Moodle URL or string URL.
     *
     * @param mixed $url Raw URL value.
     * @return string
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return (new moodle_url($url))->out(false);
        }

        return '';
    }

    /**
     * Normalize a percent value to 0..100.
     *
     * @param mixed $value Raw percent.
     * @return int
     */
    private function normalise_percent(mixed $value): int {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, min(100, (int)$value));
    }

    /**
     * Determine whether a challenge is overdue.
     *
     * @param int $duedate Unix timestamp.
     * @param string $status Canonical status.
     * @return bool
     */
    private function is_overdue(int $duedate, string $status): bool {
        if ($duedate <= 0 || $duedate >= time()) {
            return false;
        }

        return !in_array($status, [
            self::STATUS_VALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CLOSED,
            self::STATUS_INVALIDATED,
            self::STATUS_WITHDRAWN,
        ], true);
    }

    /**
     * Determine whether a challenge is due soon.
     *
     * @param int $duedate Unix timestamp.
     * @param string $status Canonical status.
     * @return bool
     */
    private function is_urgent(int $duedate, string $status): bool {
        if ($duedate <= 0 || $this->is_overdue($duedate, $status)) {
            return false;
        }

        if (in_array($status, [
            self::STATUS_VALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CLOSED,
            self::STATUS_INVALIDATED,
            self::STATUS_WITHDRAWN,
        ], true)) {
            return false;
        }

        return $duedate <= (time() + DAYSECS * 7);
    }
}