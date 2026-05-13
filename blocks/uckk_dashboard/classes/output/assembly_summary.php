<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Assembly summary output model for block_uckk_dashboard.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uckk_dashboard\output;

use coding_exception;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable assembly summary card.
 *
 * This class prepares template-safe data for the UCKK dashboard assembly card.
 *
 * It must not:
 * - query the database;
 * - decide capabilities;
 * - expose restricted integrity information;
 * - publish or contest decisions;
 * - compute authoritative assembly state.
 *
 * Those responsibilities belong to services, permissions, and workflow classes.
 */
class assembly_summary implements renderable, templatable {

    /**
     * Assembly status classes used for display only.
     */
    private const STATUS_CLASSES = [
        'planned' => 'badge bg-secondary',
        'open' => 'badge bg-success',
        'motions_open' => 'badge bg-info text-dark',
        'deliberation' => 'badge bg-primary',
        'voting_or_reading' => 'badge bg-warning text-dark',
        'decision_draft' => 'badge bg-warning text-dark',
        'decision_published' => 'badge bg-success',
        'contestability_window' => 'badge bg-warning text-dark',
        'paused_for_integrity' => 'badge bg-danger',
        'contested' => 'badge bg-danger',
        'reopened' => 'badge bg-info text-dark',
        'invalidated' => 'badge bg-dark',
        'archived' => 'badge bg-secondary',
        'closed' => 'badge bg-secondary',
    ];

    /**
     * Assembly type classes used for display only.
     */
    private const TYPE_CLASSES = [
        'savoirs' => 'badge bg-light text-dark border',
        'defis' => 'badge bg-light text-dark border',
        'joueurs' => 'badge bg-light text-dark border',
        'batisseurs' => 'badge bg-light text-dark border',
        'inquisiteurs' => 'badge bg-light text-dark border',
        'grand_jeu' => 'badge bg-light text-dark border',
    ];

    /**
     * @var int Dashboard owner user id.
     */
    private int $userid;

    /**
     * @var array<int, array|stdClass> Prepared assembly rows.
     */
    private array $assemblies;

    /**
     * @var array<string, int> Prepared count values.
     */
    private array $counts;

    /**
     * @var moodle_url|null URL to the full assembly view.
     */
    private ?moodle_url $viewallurl;

    /**
     * @var string|null Optional heading override.
     */
    private ?string $heading;

    /**
     * @var string|null Optional summary text override.
     */
    private ?string $summary;

    /**
     * @var string|null Optional empty-state text override.
     */
    private ?string $emptymessage;

    /**
     * Constructor.
     *
     * The caller must provide already-authorized, already-filtered data.
     *
     * Expected assembly item keys:
     * - id int
     * - cmid int|null
     * - name string
     * - assemblytype string
     * - assemblytypelabel string|null
     * - status string
     * - statuslabel string|null
     * - coursefullname string|null
     * - url moodle_url|string|null
     * - actionurl moodle_url|string|null
     * - actionlabel string|null
     * - motioncount int|null
     * - decisioncount int|null
     * - contestationcount int|null
     * - unresolvedobjectioncount int|null
     * - timemodified string|null
     *
     * Expected count keys:
     * - total
     * - open
     * - motions
     * - decisions
     * - contested
     * - archived
     *
     * @param int $userid Dashboard owner user id.
     * @param array<int, array|stdClass> $assemblies Prepared assembly rows.
     * @param array<string, int> $counts Prepared count values.
     * @param moodle_url|null $viewallurl Optional full assembly page URL.
     * @param string|null $heading Optional heading override.
     * @param string|null $summary Optional summary override.
     * @param string|null $emptymessage Optional empty-state override.
     */
    public function __construct(
        int $userid,
        array $assemblies = [],
        array $counts = [],
        ?moodle_url $viewallurl = null,
        ?string $heading = null,
        ?string $summary = null,
        ?string $emptymessage = null
    ) {
        $this->userid = $userid;
        $this->assemblies = $assemblies;
        $this->counts = $counts;
        $this->viewallurl = $viewallurl;
        $this->heading = $heading;
        $this->summary = $summary;
        $this->emptymessage = $emptymessage;
    }

    /**
     * Export this object for the Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Template data.
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->userid = $this->userid;
        $data->heading = $this->heading ?? get_string('assemblysummary', 'block_uckk_dashboard');
        $data->summary = $this->summary ?? get_string('assemblysummary_help', 'block_uckk_dashboard');
        $data->emptymessage = $this->emptymessage ?? get_string('noassemblies', 'block_uckk_dashboard');

        $data->viewallurl = $this->viewallurl ? $this->viewallurl->out(false) : null;
        $data->hasviewallurl = !empty($data->viewallurl);

        $data->counts = $this->export_counts();
        $data->hascounts = $this->has_nonzero_count($data->counts);

        $data->assemblies = [];
        foreach ($this->assemblies as $assembly) {
            $data->assemblies[] = $this->export_assembly($assembly);
        }

        $data->hasassemblies = !empty($data->assemblies);

        return $data;
    }

    /**
     * Export count values.
     *
     * @return stdClass Template-safe count data.
     */
    private function export_counts(): stdClass {
        $counts = new stdClass();

        $counts->total = $this->normalise_count('total');
        $counts->open = $this->normalise_count('open');
        $counts->motions = $this->normalise_count('motions');
        $counts->decisions = $this->normalise_count('decisions');
        $counts->contested = $this->normalise_count('contested');
        $counts->archived = $this->normalise_count('archived');

        $counts->hastotal = $counts->total > 0;
        $counts->hasopen = $counts->open > 0;
        $counts->hasmotions = $counts->motions > 0;
        $counts->hasdecisions = $counts->decisions > 0;
        $counts->hascontested = $counts->contested > 0;
        $counts->hasarchived = $counts->archived > 0;

        return $counts;
    }

    /**
     * Export one prepared assembly row.
     *
     * @param array|stdClass $assembly Assembly item.
     * @return stdClass Template-safe assembly data.
     * @throws coding_exception
     */
    private function export_assembly(array|stdClass $assembly): stdClass {
        $source = (object) $assembly;
        $item = new stdClass();

        $item->id = $this->normalise_int($source->id ?? 0);
        $item->cmid = $this->normalise_int($source->cmid ?? 0);
        $item->hascmid = $item->cmid > 0;

        $item->name = clean_string((string)($source->name ?? ''));
        $item->hasname = $item->name !== '';

        $item->assemblytype = clean_param((string)($source->assemblytype ?? ''), PARAM_ALPHANUMEXT);
        $item->assemblytypelabel = clean_string(
            (string)($source->assemblytypelabel ?? $this->get_assembly_type_label($item->assemblytype))
        );
        $item->assemblytypeclasses = self::TYPE_CLASSES[$item->assemblytype] ?? 'badge bg-light text-dark border';
        $item->hasassemblytype = $item->assemblytype !== '';

        $item->status = clean_param((string)($source->status ?? ''), PARAM_ALPHANUMEXT);
        $item->statuslabel = clean_string(
            (string)($source->statuslabel ?? $this->get_status_label($item->status))
        );
        $item->statusclasses = self::STATUS_CLASSES[$item->status] ?? 'badge bg-secondary';
        $item->hasstatus = $item->status !== '';

        $item->coursefullname = clean_string((string)($source->coursefullname ?? ''));
        $item->hascoursefullname = $item->coursefullname !== '';

        $item->url = $this->normalise_url($source->url ?? null);
        $item->hasurl = $item->url !== '';

        $item->actionurl = $this->normalise_url($source->actionurl ?? null);
        $item->actionlabel = clean_string((string)($source->actionlabel ?? ''));
        $item->hasaction = $item->actionurl !== '' && $item->actionlabel !== '';

        $item->motioncount = $this->normalise_int($source->motioncount ?? 0);
        $item->decisioncount = $this->normalise_int($source->decisioncount ?? 0);
        $item->contestationcount = $this->normalise_int($source->contestationcount ?? 0);
        $item->unresolvedobjectioncount = $this->normalise_int($source->unresolvedobjectioncount ?? 0);

        $item->hasmotions = $item->motioncount > 0;
        $item->hasdecisions = $item->decisioncount > 0;
        $item->hascontestations = $item->contestationcount > 0;
        $item->hasunresolvedobjections = $item->unresolvedobjectioncount > 0;

        $item->timemodified = clean_string((string)($source->timemodified ?? ''));
        $item->hastimemodified = $item->timemodified !== '';

        $item->iscontested = $item->status === 'contested';
        $item->ispausedforintegrity = $item->status === 'paused_for_integrity';
        $item->isdecisionpublished = $item->status === 'decision_published';
        $item->isincontestabilitywindow = $item->status === 'contestability_window';

        return $item;
    }

    /**
     * Get a known count value.
     *
     * @param string $key Count key.
     * @return int Count value.
     */
    private function normalise_count(string $key): int {
        return max(0, (int)($this->counts[$key] ?? 0));
    }

    /**
     * Check whether any count is non-zero.
     *
     * @param stdClass $counts Count object.
     * @return bool
     */
    private function has_nonzero_count(stdClass $counts): bool {
        return $counts->total > 0
            || $counts->open > 0
            || $counts->motions > 0
            || $counts->decisions > 0
            || $counts->contested > 0
            || $counts->archived > 0;
    }

    /**
     * Normalise an integer value.
     *
     * @param mixed $value Raw value.
     * @return int Normalised value.
     */
    private function normalise_int(mixed $value): int {
        return max(0, (int)$value);
    }

    /**
     * Normalise a URL value for template output.
     *
     * @param mixed $url Raw URL value.
     * @return string Template-safe URL string.
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && $url !== '') {
            return clean_param($url, PARAM_URL);
        }

        return '';
    }

    /**
     * Get a human label for an assembly status.
     *
     * Missing strings are acceptable during early development, but the final
     * plugin must define all referenced language strings.
     *
     * @param string $status Status key.
     * @return string Status label.
     * @throws coding_exception
     */
    private function get_status_label(string $status): string {
        if ($status === '') {
            return '';
        }

        $stringkey = 'assemblystatus_' . $status;

        return get_string($stringkey, 'block_uckk_dashboard');
    }

    /**
     * Get a human label for an assembly type.
     *
     * @param string $assemblytype Assembly type key.
     * @return string Assembly type label.
     * @throws coding_exception
     */
    private function get_assembly_type_label(string $assemblytype): string {
        if ($assemblytype === '') {
            return '';
        }

        $stringkey = 'assemblytype_' . $assemblytype;

        return get_string($stringkey, 'block_uckk_dashboard');
    }
}