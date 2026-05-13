<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// later.

/**
 * Badge summary renderable for the UCKK dashboard block.
 *
 * This class prepares badge summary data for Mustache rendering only.
 * It does not award badges, evaluate competencies, check evidence, or make
 * integrity decisions. Those decisions belong to Moodle badge/competency
 * systems and the UCKK service layer.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_uckk_dashboard\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable badge summary card.
 */
final class badge_summary implements renderable, templatable {
    /**
     * Maximum number of badge rows rendered by the compact dashboard card.
     */
    private const DEFAULT_LIMIT = 6;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $badges;

    /**
     * @var int|null Total earned badges. When null, inferred from $badges.
     */
    private ?int $earnedcount;

    /**
     * @var int|null Total available badges. When null, inferred from $badges.
     */
    private ?int $availablecount;

    /**
     * @var moodle_url|null Link to a complete badge page.
     */
    private ?moodle_url $viewallurl;

    /**
     * @var int Maximum rendered badge rows.
     */
    private int $limit;

    /**
     * Constructor.
     *
     * Badge entries are intentionally plain arrays so this renderable can be fed
     * from Moodle badges, UCKK seed definitions, or local_uckk service DTOs.
     *
     * Supported badge keys:
     * - key: stable UCKK badge key.
     * - name: display name.
     * - description: short description.
     * - imageurl: optional badge image URL.
     * - url: optional detail URL.
     * - earned: bool.
     * - pendingreview: bool.
     * - dateissued: Unix timestamp.
     * - dateissuedformatted: preformatted issue date.
     * - progress: integer 0..100.
     * - status: stable status key.
     * - statuslabel: display label.
     *
     * @param array<int, array<string, mixed>|stdClass> $badges Badge summary rows.
     * @param int|null $earnedcount Total earned count, or null to infer.
     * @param int|null $availablecount Total available count, or null to infer.
     * @param moodle_url|null $viewallurl Optional complete badge page URL.
     * @param int $limit Maximum rows rendered in the card.
     */
    public function __construct(
        array $badges,
        ?int $earnedcount = null,
        ?int $availablecount = null,
        ?moodle_url $viewallurl = null,
        int $limit = self::DEFAULT_LIMIT
    ) {
        $this->badges = array_values(array_map(static function ($badge): array {
            return (array) $badge;
        }, $badges));

        $this->earnedcount = $earnedcount;
        $this->availablecount = $availablecount;
        $this->viewallurl = $viewallurl;
        $this->limit = max(0, $limit);
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $badges = $this->get_limited_badges($output);

        $earnedcount = $this->earnedcount ?? $this->count_earned_badges();
        $availablecount = $this->availablecount ?? count($this->badges);

        $data = new stdClass();
        $data->title = get_string('mybadges', 'block_uckk_dashboard');
        $data->badges = $badges;
        $data->hasbadges = !empty($badges);

        $data->earnedcount = $earnedcount;
        $data->availablecount = $availablecount;
        $data->remainingcount = max(0, $availablecount - $earnedcount);

        $data->progresspercent = $this->calculate_progress_percent($earnedcount, $availablecount);
        $data->hasprogress = $availablecount > 0;

        $data->hasmorebadges = count($this->badges) > $this->limit && $this->limit > 0;
        $data->hiddenbadgecount = $data->hasmorebadges ? count($this->badges) - $this->limit : 0;

        $data->viewallurl = $this->viewallurl ? $this->viewallurl->out(false) : null;
        $data->hasviewallurl = $this->viewallurl !== null;
        $data->viewalllabel = get_string('viewallbadges', 'block_uckk_dashboard');

        $data->emptytitle = get_string('nobadgesyet', 'block_uckk_dashboard');
        $data->emptymessage = get_string('nobadgesyet_desc', 'block_uckk_dashboard');

        return $data;
    }

    /**
     * Return the template name used by the renderer.
     *
     * @return string
     */
    public function get_template_name(): string {
        return 'block_uckk_dashboard/badge_summary';
    }

    /**
     * Normalize and limit rendered badge rows.
     *
     * @param renderer_base $output Renderer.
     * @return array<int, stdClass>
     */
    private function get_limited_badges(renderer_base $output): array {
        if ($this->limit === 0) {
            return [];
        }

        $badges = array_slice($this->badges, 0, $this->limit);

        return array_map(function (array $badge) use ($output): stdClass {
            return $this->normalise_badge($badge, $output);
        }, $badges);
    }

    /**
     * Normalize one badge row for Mustache.
     *
     * @param array<string, mixed> $badge Badge row.
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    private function normalise_badge(array $badge, renderer_base $output): stdClass {
        $earned = $this->is_badge_earned($badge);
        $pendingreview = !empty($badge['pendingreview']);

        $status = $badge['status'] ?? null;
        if ($status === null) {
            if ($pendingreview) {
                $status = 'pending_review';
            } else {
                $status = $earned ? 'completed' : 'not_started';
            }
        }

        $dateissued = isset($badge['dateissued']) ? (int) $badge['dateissued'] : 0;

        $row = new stdClass();
        $row->key = clean_param((string)($badge['key'] ?? $badge['shortname'] ?? ''), PARAM_ALPHANUMEXT);
        $row->name = (string)($badge['name'] ?? '');
        $row->description = (string)($badge['description'] ?? '');

        $row->earned = $earned;
        $row->pendingreview = $pendingreview;
        $row->available = !$earned && !$pendingreview;

        $row->status = clean_param((string)$status, PARAM_ALPHANUMEXT);
        $row->statuslabel = $this->get_status_label($badge, $row->status);

        $row->progress = $this->normalise_progress($badge['progress'] ?? ($earned ? 100 : 0));
        $row->hasprogress = $row->progress > 0 && $row->progress < 100;

        $row->dateissued = $dateissued;
        $row->hasdateissued = $dateissued > 0;
        $row->dateissuedformatted = $this->get_date_issued_label($badge, $dateissued);

        $row->imageurl = $this->normalise_url($badge['imageurl'] ?? null);
        $row->hasimage = $row->imageurl !== null;

        $row->url = $this->normalise_url($badge['url'] ?? null);
        $row->hasurl = $row->url !== null;

        $row->criteria = (string)($badge['criteria'] ?? '');
        $row->hascriteria = $row->criteria !== '';

        return $row;
    }

    /**
     * Determine whether a badge row is earned.
     *
     * @param array<string, mixed> $badge Badge row.
     * @return bool
     */
    private function is_badge_earned(array $badge): bool {
        if (array_key_exists('earned', $badge)) {
            return (bool)$badge['earned'];
        }

        if (array_key_exists('awarded', $badge)) {
            return (bool)$badge['awarded'];
        }

        if (!empty($badge['dateissued'])) {
            return true;
        }

        $status = (string)($badge['status'] ?? '');

        return in_array($status, ['earned', 'awarded', 'completed', 'validated'], true);
    }

    /**
     * Count earned badges from the supplied rows.
     *
     * @return int
     */
    private function count_earned_badges(): int {
        $count = 0;

        foreach ($this->badges as $badge) {
            if ($this->is_badge_earned($badge)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate summary progress.
     *
     * @param int $earnedcount Earned count.
     * @param int $availablecount Available count.
     * @return int
     */
    private function calculate_progress_percent(int $earnedcount, int $availablecount): int {
        if ($availablecount <= 0) {
            return 0;
        }

        return min(100, max(0, (int)round(($earnedcount / $availablecount) * 100)));
    }

    /**
     * Normalize a percentage value.
     *
     * @param mixed $progress Raw progress.
     * @return int
     */
    private function normalise_progress(mixed $progress): int {
        if (!is_numeric($progress)) {
            return 0;
        }

        return min(100, max(0, (int)round((float)$progress)));
    }

    /**
     * Resolve the badge status label.
     *
     * @param array<string, mixed> $badge Badge row.
     * @param string $status Status key.
     * @return string
     */
    private function get_status_label(array $badge, string $status): string {
        if (!empty($badge['statuslabel'])) {
            return (string)$badge['statuslabel'];
        }

        return match ($status) {
            'completed', 'earned', 'awarded', 'validated' =>
                get_string('badgeearned', 'block_uckk_dashboard'),
            'pending', 'pending_review' =>
                get_string('badgependingreview', 'block_uckk_dashboard'),
            default =>
                get_string('badgeavailable', 'block_uckk_dashboard'),
        };
    }

    /**
     * Resolve the issue date label.
     *
     * @param array<string, mixed> $badge Badge row.
     * @param int $dateissued Unix timestamp.
     * @return string
     */
    private function get_date_issued_label(array $badge, int $dateissued): string {
        if (!empty($badge['dateissuedformatted'])) {
            return (string)$badge['dateissuedformatted'];
        }

        if ($dateissued <= 0) {
            return '';
        }

        return userdate($dateissued, get_string('strftimedate', 'langconfig'));
    }

    /**
     * Normalize a URL-like value for template output.
     *
     * @param mixed $url Raw URL.
     * @return string|null
     */
    private function normalise_url(mixed $url): ?string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return clean_param($url, PARAM_URL);
        }

        return null;
    }
}