<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Evaluation panel output for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable evaluation panel for a Défi King Klown.
 *
 * This class exports already permission-filtered evaluation data for
 * mod_uckkchallenge/evaluation_panel.mustache.
 *
 * It must not:
 * - change challenge or submission status;
 * - write grades;
 * - validate integrity;
 * - award badges;
 * - certify competencies;
 * - archive evidence.
 */
final class evaluation_panel implements renderable, templatable {
    /** @var string Evaluation status: not started. */
    private const STATUS_NOT_STARTED = 'not_started';

    /** @var string Evaluation status: in progress. */
    private const STATUS_IN_PROGRESS = 'in_progress';

    /** @var string Evaluation status: pending integrity review. */
    private const STATUS_PENDING_INTEGRITY = 'pending_integrity';

    /** @var string Evaluation status: correction required. */
    private const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** @var string Evaluation status: validated. */
    private const STATUS_VALIDATED = 'validated';

    /** @var string Evaluation status: rejected. */
    private const STATUS_REJECTED = 'rejected';

    /** @var string Evaluation status: contested. */
    private const STATUS_CONTESTED = 'contested';

    /** @var string Evaluation status: archived. */
    private const STATUS_ARCHIVED = 'archived';

    /** @var int Challenge id. */
    private int $challengeid;

    /** @var int Course module id. */
    private int $cmid;

    /** @var int|null Submission id. */
    private ?int $submissionid;

    /** @var int|null Evaluation id. */
    private ?int $evaluationid;

    /** @var int|null User id being evaluated. */
    private ?int $userid;

    /** @var array<string, mixed> Prepared, permission-filtered panel data. */
    private array $data;

    /**
     * Constructor.
     *
     * @param int $challengeid Challenge instance id.
     * @param int $cmid Course module id.
     * @param array<string, mixed>|stdClass $data Prepared panel data.
     * @param int|null $submissionid Optional submission id.
     * @param int|null $evaluationid Optional evaluation id.
     * @param int|null $userid Optional evaluated user id.
     */
    public function __construct(
        int $challengeid,
        int $cmid,
        array|stdClass $data = [],
        ?int $submissionid = null,
        ?int $evaluationid = null,
        ?int $userid = null
    ) {
        $this->challengeid = $challengeid;
        $this->cmid = $cmid;
        $this->submissionid = $submissionid;
        $this->evaluationid = $evaluationid;
        $this->userid = $userid;
        $this->data = (array)$data;
    }

    /**
     * Export template context.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $status = $this->normalise_status((string)($this->data['status'] ?? self::STATUS_NOT_STARTED));

        $context = new stdClass();
        $context->challengeid = $this->challengeid;
        $context->cmid = $this->cmid;
        $context->submissionid = $this->submissionid ?? 0;
        $context->evaluationid = $this->evaluationid ?? 0;
        $context->userid = $this->userid ?? 0;

        $context->heading = $this->get_string_value('heading', get_string('evaluationpanel', 'uckkchallenge'));
        $context->challengeheading = $this->get_string_value('challengeheading', '');
        $context->submissionheading = $this->get_string_value('submissionheading', '');

        $context->status = $status;
        $context->statuslabel = $this->get_status_label($status, $this->get_string_value('statuslabel', ''));
        $context->statusclass = $this->get_status_class($status);

        $context->hasevaluation = !empty($this->data['hasevaluation']);
        $context->isempty = !$context->hasevaluation;
        $context->emptylabel = $this->get_string_value(
            'emptylabel',
            get_string('evaluation:none', 'uckkchallenge')
        );

        $context->score = $this->normalise_nullable_number($this->data['score'] ?? null);
        $context->maxscore = $this->normalise_nullable_number($this->data['maxscore'] ?? null);
        $context->scorelabel = $this->build_score_label($context->score, $context->maxscore);
        $context->hasscore = $context->score !== null || $context->maxscore !== null;

        $context->gradeletter = $this->get_string_value('gradeletter', '');
        $context->hasgradeletter = $context->gradeletter !== '';

        $context->publicfeedback = $this->get_string_value('publicfeedback', '');
        $context->haspublicfeedback = $context->publicfeedback !== '';

        $context->privatefeedback = $this->get_string_value('privatefeedback', '');
        $context->hasprivatefeedback = $context->privatefeedback !== '' && !empty($this->data['showprivatefeedback']);

        $context->mentorname = $this->get_string_value('mentorname', '');
        $context->hasmentor = $context->mentorname !== '';

        $context->timemodified = (int)($this->data['timemodified'] ?? 0);
        $context->timemodifiedlabel = $context->timemodified > 0 ? userdate($context->timemodified) : '';
        $context->hastimemodified = $context->timemodified > 0;

        $context->criteria = $this->export_criteria($this->data['criteria'] ?? []);
        $context->hascriteria = !empty($context->criteria);

        $context->competencies = $this->export_competencies($this->data['competencies'] ?? []);
        $context->hascompetencies = !empty($context->competencies);

        $context->badges = $this->export_badges($this->data['badges'] ?? []);
        $context->hasbadges = !empty($context->badges);

        $context->integrity = $this->export_integrity($this->data['integrity'] ?? []);
        $context->hasintegrity = !empty($context->integrity->state) || !empty($context->integrity->notice);

        $context->archive = $this->export_archive($this->data['archive'] ?? []);
        $context->hasarchive = !empty($context->archive->status) || !empty($context->archive->url);

        $context->canedit = !empty($this->data['canedit']);
        $context->canevaluate = !empty($this->data['canevaluate']);
        $context->canvalidateintegrity = !empty($this->data['canvalidateintegrity']);
        $context->cancontest = !empty($this->data['cancontest']);
        $context->canarchive = !empty($this->data['canarchive']);

        $context->actions = $this->export_actions($this->data['actions'] ?? []);
        $context->hasactions = !empty($context->actions);

        $context->aiassisted = !empty($this->data['aiassisted']);
        $context->ailabel = $context->aiassisted
            ? get_string('aiassistednonfinal', 'uckkchallenge')
            : '';

        $context->notice = $this->get_string_value(
            'notice',
            get_string('evaluationnonsovereignnotice', 'uckkchallenge')
        );

        return $context;
    }

    /**
     * Export rubric/evaluation criteria.
     *
     * @param mixed $criteria Raw criteria.
     * @return array<int, stdClass>
     */
    private function export_criteria(mixed $criteria): array {
        if (!is_array($criteria)) {
            return [];
        }

        $exported = [];

        foreach ($criteria as $criterion) {
            $criterion = (array)$criterion;

            $score = $this->normalise_nullable_number($criterion['score'] ?? null);
            $maxscore = $this->normalise_nullable_number($criterion['maxscore'] ?? null);

            $row = new stdClass();
            $row->key = clean_param((string)($criterion['key'] ?? ''), PARAM_ALPHANUMEXT);
            $row->name = format_string((string)($criterion['name'] ?? ''));
            $row->description = (string)($criterion['description'] ?? '');
            $row->level = clean_param((string)($criterion['level'] ?? ''), PARAM_ALPHANUMEXT);
            $row->levellabel = format_string((string)($criterion['levellabel'] ?? ''));
            $row->score = $score;
            $row->maxscore = $maxscore;
            $row->scorelabel = $this->build_score_label($score, $maxscore);
            $row->hasscore = $score !== null || $maxscore !== null;
            $row->feedback = (string)($criterion['feedback'] ?? '');
            $row->hasfeedback = trim($row->feedback) !== '';
            $row->achieved = !empty($criterion['achieved']);
            $row->needswork = !empty($criterion['needswork']);
            $row->statusclass = $this->get_criterion_status_class($row);

            if ($row->name !== '') {
                $exported[] = $row;
            }
        }

        return $exported;
    }

    /**
     * Export linked competency ratings.
     *
     * @param mixed $competencies Raw competencies.
     * @return array<int, stdClass>
     */
    private function export_competencies(mixed $competencies): array {
        if (!is_array($competencies)) {
            return [];
        }

        $exported = [];

        foreach ($competencies as $competency) {
            $competency = (array)$competency;

            $row = new stdClass();
            $row->id = (int)($competency['id'] ?? 0);
            $row->code = clean_param((string)($competency['code'] ?? ''), PARAM_TEXT);
            $row->name = format_string((string)($competency['name'] ?? ''));
            $row->rating = clean_param((string)($competency['rating'] ?? ''), PARAM_ALPHANUMEXT);
            $row->ratinglabel = format_string((string)($competency['ratinglabel'] ?? ''));
            $row->validated = !empty($competency['validated']);
            $row->humanvalidated = !empty($competency['humanvalidated']);
            $row->url = $this->normalise_url($competency['url'] ?? null);
            $row->hasurl = $row->url !== '';

            if ($row->code !== '' || $row->name !== '') {
                $exported[] = $row;
            }
        }

        return $exported;
    }

    /**
     * Export badge trigger/award display rows.
     *
     * @param mixed $badges Raw badges.
     * @return array<int, stdClass>
     */
    private function export_badges(mixed $badges): array {
        if (!is_array($badges)) {
            return [];
        }

        $exported = [];

        foreach ($badges as $badge) {
            $badge = (array)$badge;

            $row = new stdClass();
            $row->id = (int)($badge['id'] ?? 0);
            $row->key = clean_param((string)($badge['key'] ?? ''), PARAM_ALPHANUMEXT);
            $row->name = format_string((string)($badge['name'] ?? ''));
            $row->status = clean_param((string)($badge['status'] ?? 'pending'), PARAM_ALPHANUMEXT);
            $row->statuslabel = format_string((string)($badge['statuslabel'] ?? ''));
            $row->eligible = !empty($badge['eligible']);
            $row->awarded = !empty($badge['awarded']);
            $row->blocked = !empty($badge['blocked']);
            $row->reason = (string)($badge['reason'] ?? '');
            $row->hasreason = trim($row->reason) !== '';
            $row->url = $this->normalise_url($badge['url'] ?? null);
            $row->hasurl = $row->url !== '';

            if ($row->key !== '' || $row->name !== '') {
                $exported[] = $row;
            }
        }

        return $exported;
    }

    /**
     * Export integrity summary.
     *
     * @param mixed $integrity Raw integrity data.
     * @return stdClass
     */
    private function export_integrity(mixed $integrity): stdClass {
        $integrity = is_array($integrity) || is_object($integrity) ? (array)$integrity : [];

        $data = new stdClass();
        $data->state = clean_param((string)($integrity['state'] ?? ''), PARAM_ALPHANUMEXT);
        $data->statelabel = format_string((string)($integrity['statelabel'] ?? ''));
        $data->notice = (string)($integrity['notice'] ?? '');
        $data->hasnotice = trim($data->notice) !== '';
        $data->caseid = (int)($integrity['caseid'] ?? 0);
        $data->hascase = $data->caseid > 0;
        $data->restricted = !empty($integrity['restricted']);
        $data->url = $this->normalise_url($integrity['url'] ?? null);
        $data->hasurl = $data->url !== '';

        return $data;
    }

    /**
     * Export archive summary.
     *
     * @param mixed $archive Raw archive data.
     * @return stdClass
     */
    private function export_archive(mixed $archive): stdClass {
        $archive = is_array($archive) || is_object($archive) ? (array)$archive : [];

        $data = new stdClass();
        $data->status = clean_param((string)($archive['status'] ?? ''), PARAM_ALPHANUMEXT);
        $data->statuslabel = format_string((string)($archive['statuslabel'] ?? ''));
        $data->itemid = (int)($archive['itemid'] ?? 0);
        $data->hasitem = $data->itemid > 0;
        $data->summary = (string)($archive['summary'] ?? '');
        $data->hassummary = trim($data->summary) !== '';
        $data->url = $this->normalise_url($archive['url'] ?? null);
        $data->hasurl = $data->url !== '';

        return $data;
    }

    /**
     * Export permitted action links/buttons.
     *
     * @param mixed $actions Raw actions.
     * @return array<int, stdClass>
     */
    private function export_actions(mixed $actions): array {
        if (!is_array($actions)) {
            return [];
        }

        $exported = [];

        foreach ($actions as $action) {
            $action = (array)$action;

            if (empty($action['url']) || empty($action['label'])) {
                continue;
            }

            $row = new stdClass();
            $row->key = clean_param((string)($action['key'] ?? ''), PARAM_ALPHANUMEXT);
            $row->label = format_string((string)$action['label']);
            $row->url = $this->normalise_url($action['url']);
            $row->primary = !empty($action['primary']);
            $row->danger = !empty($action['danger']);
            $row->disabled = !empty($action['disabled']);
            $row->disabledreason = format_string((string)($action['disabledreason'] ?? ''));
            $row->hasdisabledreason = $row->disabledreason !== '';

            if ($row->url !== '') {
                $exported[] = $row;
            }
        }

        return $exported;
    }

    /**
     * Get a string value from prepared data.
     *
     * @param string $key Data key.
     * @param string $default Default value.
     * @return string
     */
    private function get_string_value(string $key, string $default = ''): string {
        return (string)($this->data[$key] ?? $default);
    }

    /**
     * Normalize evaluation status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING_INTEGRITY,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CONTESTED,
            self::STATUS_ARCHIVED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_NOT_STARTED;
    }

    /**
     * Get a status label.
     *
     * @param string $status Status.
     * @param string $fallback Optional supplied label.
     * @return string
     */
    private function get_status_label(string $status, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'evaluationstatus:' . str_replace('_', '', $status);

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get a status CSS class.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return 'status-' . str_replace('_', '-', $status);
    }

    /**
     * Get CSS class for one criterion row.
     *
     * @param stdClass $criterion Criterion row.
     * @return string
     */
    private function get_criterion_status_class(stdClass $criterion): string {
        if (!empty($criterion->achieved)) {
            return 'is-achieved';
        }

        if (!empty($criterion->needswork)) {
            return 'needs-work';
        }

        return 'is-pending';
    }

    /**
     * Normalize a nullable numeric value.
     *
     * @param mixed $value Raw value.
     * @return float|null
     */
    private function normalise_nullable_number(mixed $value): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Build score label.
     *
     * @param float|null $score Score.
     * @param float|null $maxscore Max score.
     * @return string
     */
    private function build_score_label(?float $score, ?float $maxscore): string {
        if ($score === null && $maxscore === null) {
            return '';
        }

        if ($score !== null && $maxscore !== null) {
            return format_float($score, 2, true, true) . ' / ' . format_float($maxscore, 2, true, true);
        }

        if ($score !== null) {
            return format_float($score, 2, true, true);
        }

        return get_string('scoreoutof', 'uckkchallenge', format_float((float)$maxscore, 2, true, true));
    }

    /**
     * Normalize a URL value.
     *
     * @param mixed $url URL value.
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
}