<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Motion list output object for the UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable list of assembly motions.
 *
 * This class prepares display data only. It must not create motions, amend
 * motions, publish decisions, count votes authoritatively, close contestations,
 * or archive the assembly.
 */
final class motion_list implements renderable, templatable {
    /** Motion status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Motion status: submitted. */
    public const STATUS_SUBMITTED = 'submitted';

    /** Motion status: open. */
    public const STATUS_OPEN = 'open';

    /** Motion status: amended. */
    public const STATUS_AMENDED = 'amended';

    /** Motion status: accepted. */
    public const STATUS_ACCEPTED = 'accepted';

    /** Motion status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Motion status: withdrawn. */
    public const STATUS_WITHDRAWN = 'withdrawn';

    /** Motion status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Motion status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Motion type: information. */
    public const TYPE_INFORMATION = 'information';

    /** Motion type: recommendation. */
    public const TYPE_RECOMMENDATION = 'recommendation';

    /** Motion type: validation. */
    public const TYPE_VALIDATION = 'validation';

    /** Motion type: correction. */
    public const TYPE_CORRECTION = 'correction';

    /** Motion type: rejection. */
    public const TYPE_REJECTION = 'rejection';

    /** Motion type: archival. */
    public const TYPE_ARCHIVAL = 'archival';

    /** Motion type: integrity. */
    public const TYPE_INTEGRITY = 'integrity';

    /**
     * Assembly id.
     *
     * @var int
     */
    private int $assemblyid;

    /**
     * Course module id.
     *
     * @var int
     */
    private int $cmid;

    /**
     * Context id.
     *
     * @var int
     */
    private int $contextid;

    /**
     * Permission-filtered motion rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $motions;

    /**
     * Optional heading.
     *
     * @var string
     */
    private string $heading;

    /**
     * Optional URL for proposing a motion.
     *
     * @var moodle_url|null
     */
    private ?moodle_url $proposeurl;

    /**
     * Optional URL for viewing all motions.
     *
     * @var moodle_url|null
     */
    private ?moodle_url $viewallurl;

    /**
     * Whether current viewer can propose motions.
     *
     * @var bool
     */
    private bool $canpropose;

    /**
     * Whether current viewer can view restricted rows already included.
     *
     * @var bool
     */
    private bool $canviewrestricted;

    /**
     * Constructor.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $contextid Context id.
     * @param array<int, array<string, mixed>|stdClass> $motions Permission-filtered motions.
     * @param moodle_url|null $proposeurl Optional propose motion URL.
     * @param moodle_url|null $viewallurl Optional full motion list URL.
     * @param bool $canpropose Whether viewer can propose motions.
     * @param bool $canviewrestricted Whether viewer can view restricted motion details.
     * @param string|null $heading Optional heading override.
     */
    public function __construct(
        int $assemblyid,
        int $cmid,
        int $contextid,
        array $motions = [],
        ?moodle_url $proposeurl = null,
        ?moodle_url $viewallurl = null,
        bool $canpropose = false,
        bool $canviewrestricted = false,
        ?string $heading = null
    ) {
        $this->assemblyid = max(0, $assemblyid);
        $this->cmid = max(0, $cmid);
        $this->contextid = max(0, $contextid);
        $this->motions = array_map([$this, 'normalise_motion'], $motions);
        $this->proposeurl = $proposeurl;
        $this->viewallurl = $viewallurl;
        $this->canpropose = $canpropose;
        $this->canviewrestricted = $canviewrestricted;
        $this->heading = $heading ?? get_string('motions', 'uckkassembly');
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->assemblyid = $this->assemblyid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->heading = $this->heading;

        $data->motions = $this->build_motion_rows();
        $data->hasmotions = !empty($data->motions);
        $data->emptymessage = get_string('motions:none', 'uckkassembly');

        $data->counts = $this->build_counts();
        $data->hascontested = $data->counts->contested > 0;
        $data->hasopen = $data->counts->open > 0;
        $data->hasaccepted = $data->counts->accepted > 0;
        $data->hasrejected = $data->counts->rejected > 0;

        $data->canpropose = $this->canpropose && $this->proposeurl !== null;
        $data->proposeurl = $this->proposeurl ? $this->proposeurl->out(false) : '';
        $data->proposelabel = get_string('proposemotion', 'uckkassembly');

        $data->hasviewallurl = $this->viewallurl !== null;
        $data->viewallurl = $this->viewallurl ? $this->viewallurl->out(false) : '';
        $data->viewalllabel = get_string('viewallmotions', 'uckkassembly');

        $data->canviewrestricted = $this->canviewrestricted;
        $data->restrictednotice = $this->canviewrestricted
            ? get_string('restricteddatavisible', 'uckkassembly')
            : '';

        return $data;
    }

    /**
     * Normalise one motion row.
     *
     * @param array<string, mixed>|stdClass $motion Raw motion row.
     * @return array<string, mixed>
     */
    private function normalise_motion(array|stdClass $motion): array {
        $row = (array)$motion;

        $id = max(0, (int)($row['id'] ?? 0));
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_SUBMITTED));
        $motiontype = $this->normalise_motion_type((string)($row['motiontype'] ?? $row['decisiontype'] ?? self::TYPE_RECOMMENDATION));

        $url = $this->normalise_url($row['url'] ?? null);
        if ($url === '' && $id > 0 && $this->cmid > 0) {
            $url = (new moodle_url('/mod/uckkassembly/motion.php', [
                'id' => $this->cmid,
                'motionid' => $id,
            ]))->out(false);
        }

        $metadata = $this->normalise_metadata($row['metadata'] ?? []);

        return [
            'id' => $id,
            'assemblyid' => max(0, (int)($row['assemblyid'] ?? $this->assemblyid)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? get_string('motion', 'uckkassembly'))),
            'summary' => (string)($row['summary'] ?? ''),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'description' => (string)($row['description'] ?? $row['motiontext'] ?? ''),
            'hasdescription' => trim((string)($row['description'] ?? $row['motiontext'] ?? '')) !== '',
            'motiontype' => $motiontype,
            'motiontypelabel' => $this->get_motion_type_label($motiontype),
            'status' => $status,
            'statuslabel' => $this->get_status_label($status, (string)($row['statuslabel'] ?? '')),
            'statusclass' => $this->get_status_class($status),
            'visibility' => clean_param((string)($row['visibility'] ?? 'course'), PARAM_ALPHANUMEXT),
            'visibilitylabel' => format_string((string)($row['visibilitylabel'] ?? '')),
            'proposedby' => format_string((string)($row['proposedby'] ?? $row['authorname'] ?? '')),
            'hasproposedby' => trim((string)($row['proposedby'] ?? $row['authorname'] ?? '')) !== '',
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
            'sortorder' => max(0, (int)($row['sortorder'] ?? 0)),
            'amendmentcount' => max(0, (int)($row['amendmentcount'] ?? $metadata['amendmentcount'] ?? 0)),
            'objectioncount' => max(0, (int)($row['objectioncount'] ?? $metadata['objectioncount'] ?? 0)),
            'contestcount' => max(0, (int)($row['contestcount'] ?? $metadata['contestcount'] ?? 0)),
            'votereading' => $this->normalise_vote_reading($row['votereading'] ?? $metadata['votereading'] ?? []),
            'url' => $url,
            'hasurl' => $url !== '',
            'amendurl' => $this->normalise_url($row['amendurl'] ?? null),
            'voteurl' => $this->normalise_url($row['voteurl'] ?? null),
            'contesturl' => $this->normalise_url($row['contesturl'] ?? null),
            'decisionurl' => $this->normalise_url($row['decisionurl'] ?? null),
            'canamend' => !empty($row['canamend']),
            'canvote' => !empty($row['canvote']),
            'cancontest' => !empty($row['cancontest']),
            'canpublishdecision' => !empty($row['canpublishdecision']),
            'restricted' => !empty($row['restricted']) || (string)($row['visibility'] ?? '') === 'restricted_integrity',
            'metadata' => $metadata,
        ];
    }

    /**
     * Build exported motion rows.
     *
     * @return array<int, stdClass>
     */
    private function build_motion_rows(): array {
        $rows = [];

        foreach ($this->motions as $motion) {
            $row = new stdClass();

            $row->id = $motion['id'];
            $row->assemblyid = $motion['assemblyid'];
            $row->title = $motion['title'];
            $row->summary = $motion['summary'];
            $row->hassummary = $motion['hassummary'];
            $row->description = $motion['description'];
            $row->hasdescription = $motion['hasdescription'];
            $row->motiontype = $motion['motiontype'];
            $row->motiontypelabel = $motion['motiontypelabel'];
            $row->status = $motion['status'];
            $row->statuslabel = $motion['statuslabel'];
            $row->statusclass = $motion['statusclass'];
            $row->visibility = $motion['visibility'];
            $row->visibilitylabel = $motion['visibilitylabel'];
            $row->hasvisibilitylabel = $motion['visibilitylabel'] !== '';
            $row->proposedby = $motion['proposedby'];
            $row->hasproposedby = $motion['hasproposedby'];

            $row->timecreated = $motion['timecreated'];
            $row->timecreatedlabel = $motion['timecreated'] > 0 ? userdate($motion['timecreated']) : '';
            $row->hastimecreated = $motion['timecreated'] > 0;

            $row->timemodified = $motion['timemodified'];
            $row->timemodifiedlabel = $motion['timemodified'] > 0 ? userdate($motion['timemodified']) : '';
            $row->hastimemodified = $motion['timemodified'] > 0;

            $row->amendmentcount = $motion['amendmentcount'];
            $row->hasamendments = $motion['amendmentcount'] > 0;
            $row->objectioncount = $motion['objectioncount'];
            $row->hasobjections = $motion['objectioncount'] > 0;
            $row->contestcount = $motion['contestcount'];
            $row->hascontests = $motion['contestcount'] > 0;

            $row->votereading = $motion['votereading'];
            $row->hasvotereading = $motion['votereading']->total > 0;

            $row->url = $motion['url'];
            $row->hasurl = $motion['hasurl'];
            $row->amendurl = $motion['amendurl'];
            $row->hasamendurl = $motion['amendurl'] !== '';
            $row->voteurl = $motion['voteurl'];
            $row->hasvoteurl = $motion['voteurl'] !== '';
            $row->contesturl = $motion['contesturl'];
            $row->hascontesturl = $motion['contesturl'] !== '';
            $row->decisionurl = $motion['decisionurl'];
            $row->hasdecisionurl = $motion['decisionurl'] !== '';

            $row->canamend = $motion['canamend'] && $motion['amendurl'] !== '';
            $row->canvote = $motion['canvote'] && $motion['voteurl'] !== '';
            $row->cancontest = $motion['cancontest'] && $motion['contesturl'] !== '';
            $row->canpublishdecision = $motion['canpublishdecision'] && $motion['decisionurl'] !== '';
            $row->hasactions = $row->canamend || $row->canvote || $row->cancontest || $row->canpublishdecision;

            $row->restricted = $motion['restricted'];
            $row->showrestrictedmarker = $motion['restricted'] && $this->canviewrestricted;

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Build motion count summary.
     *
     * @return stdClass
     */
    private function build_counts(): stdClass {
        $counts = new stdClass();
        $counts->total = count($this->motions);
        $counts->draft = 0;
        $counts->submitted = 0;
        $counts->open = 0;
        $counts->amended = 0;
        $counts->accepted = 0;
        $counts->rejected = 0;
        $counts->withdrawn = 0;
        $counts->contested = 0;
        $counts->archived = 0;
        $counts->restricted = 0;

        foreach ($this->motions as $motion) {
            $status = $motion['status'];

            if (property_exists($counts, $status)) {
                $counts->{$status}++;
            }

            if ($motion['restricted']) {
                $counts->restricted++;
            }
        }

        return $counts;
    }

    /**
     * Normalise vote reading summary.
     *
     * @param mixed $reading Raw reading.
     * @return stdClass
     */
    private function normalise_vote_reading(mixed $reading): stdClass {
        $data = is_array($reading) || $reading instanceof stdClass ? (array)$reading : [];

        $result = new stdClass();
        $result->for = max(0, (int)($data['for'] ?? 0));
        $result->against = max(0, (int)($data['against'] ?? 0));
        $result->abstain = max(0, (int)($data['abstain'] ?? 0));
        $result->block = max(0, (int)($data['block'] ?? 0));
        $result->total = max(0, (int)($data['total'] ?? 0));

        if ($result->total <= 0) {
            $result->total = $result->for + $result->against + $result->abstain + $result->block;
        }

        $result->forlabel = get_string('vote:for', 'uckkassembly');
        $result->againstlabel = get_string('vote:against', 'uckkassembly');
        $result->abstainlabel = get_string('vote:abstain', 'uckkassembly');
        $result->blocklabel = get_string('vote:block', 'uckkassembly');

        $result->forpercent = $this->calculate_percent($result->for, $result->total);
        $result->againstpercent = $this->calculate_percent($result->against, $result->total);
        $result->abstainpercent = $this->calculate_percent($result->abstain, $result->total);
        $result->blockpercent = $this->calculate_percent($result->block, $result->total);

        return $result;
    }

    /**
     * Calculate a percentage.
     *
     * @param int $value Value.
     * @param int $total Total.
     * @return int
     */
    private function calculate_percent(int $value, int $total): int {
        if ($total <= 0) {
            return 0;
        }

        return (int)round(($value / $total) * 100);
    }

    /**
     * Normalise motion status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_OPEN,
            self::STATUS_AMENDED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
            self::STATUS_CONTESTED,
            self::STATUS_ARCHIVED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_SUBMITTED;
    }

    /**
     * Normalise motion type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_motion_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        $allowed = [
            self::TYPE_INFORMATION,
            self::TYPE_RECOMMENDATION,
            self::TYPE_VALIDATION,
            self::TYPE_CORRECTION,
            self::TYPE_REJECTION,
            self::TYPE_ARCHIVAL,
            self::TYPE_INTEGRITY,
        ];

        return in_array($type, $allowed, true) ? $type : self::TYPE_RECOMMENDATION;
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @param string $fallback Optional fallback label.
     * @return string
     */
    private function get_status_label(string $status, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'motionstatus:' . str_replace('_', '', $status);

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get motion type label.
     *
     * @param string $type Motion type.
     * @return string
     */
    private function get_motion_type_label(string $type): string {
        $key = 'decisiontype:' . str_replace('_', '', $type);

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get status CSS class.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return 'motion-status-' . str_replace('_', '-', $status);
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            return $decoded;
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return [];
    }

    /**
     * Normalise URL.
     *
     * @param mixed $url Raw URL.
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