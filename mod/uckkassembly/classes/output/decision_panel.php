<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Decision panel output object for UCKK Assemblies.
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
 * Renderable decision panel for one Assembly decision.
 *
 * This class prepares display data only. It must not publish a decision,
 * resolve contestations, validate integrity, count votes authoritatively,
 * alter minutes, or archive records.
 */
final class decision_panel implements renderable, templatable {
    /** Decision type: information. */
    public const TYPE_INFORMATION = 'information';

    /** Decision type: recommendation. */
    public const TYPE_RECOMMENDATION = 'recommendation';

    /** Decision type: validation. */
    public const TYPE_VALIDATION = 'validation';

    /** Decision type: correction. */
    public const TYPE_CORRECTION = 'correction';

    /** Decision type: rejection. */
    public const TYPE_REJECTION = 'rejection';

    /** Decision type: archival. */
    public const TYPE_ARCHIVAL = 'archival';

    /** Decision type: integrity. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Decision status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Decision status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Decision status: published. */
    public const STATUS_PUBLISHED = 'published';

    /** Decision status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Decision status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Decision status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Decision status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Decision status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Integrity state: unverified. */
    public const INTEGRITY_UNVERIFIED = 'unverified';

    /** Integrity state: human reviewed. */
    public const INTEGRITY_HUMAN_REVIEWED = 'human_reviewed';

    /** Integrity state: verified. */
    public const INTEGRITY_VERIFIED = 'verified';

    /** Integrity state: contested. */
    public const INTEGRITY_CONTESTED = 'contested';

    /** Integrity state: invalidated. */
    public const INTEGRITY_INVALIDATED = 'invalidated';

    /** Integrity state: archived. */
    public const INTEGRITY_ARCHIVED = 'archived';

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
     * Decision data.
     *
     * @var array<string, mixed>
     */
    private array $decision;

    /**
     * Reading rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $readings;

    /**
     * Minority report rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $minorityreports;

    /**
     * Contestation rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $contestations;

    /**
     * Permitted action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Constructor.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $contextid Moodle context id.
     * @param array<string, mixed>|stdClass $decision Permission-filtered decision data.
     * @param array<int, array<string, mixed>|stdClass> $readings Permission-filtered readings.
     * @param array<int, array<string, mixed>|stdClass> $minorityreports Permission-filtered minority reports.
     * @param array<int, array<string, mixed>|stdClass> $contestations Permission-filtered contestations.
     * @param array<int, array<string, mixed>|stdClass> $actions Permission-filtered actions.
     */
    public function __construct(
        int $assemblyid,
        int $cmid,
        int $contextid,
        array|stdClass $decision,
        array $readings = [],
        array $minorityreports = [],
        array $contestations = [],
        array $actions = []
    ) {
        $this->assemblyid = max(0, $assemblyid);
        $this->cmid = max(0, $cmid);
        $this->contextid = max(0, $contextid);
        $this->decision = $this->normalise_decision($decision);
        $this->readings = array_map([$this, 'normalise_reading'], $readings);
        $this->minorityreports = array_map([$this, 'normalise_minority_report'], $minorityreports);
        $this->contestations = array_map([$this, 'normalise_contestation'], $contestations);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
    }

    /**
     * Export data for the decision panel Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $decisionurl = new moodle_url('/mod/uckkassembly/decision.php', [
            'id' => $this->cmid,
            'decisionid' => $this->decision['id'],
        ]);

        $data = new stdClass();
        $data->assemblyid = $this->assemblyid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->heading = get_string('decisionpanel', 'uckkassembly');

        $data->id = $this->decision['id'];
        $data->title = $this->decision['title'];
        $data->decisiontype = $this->decision['decisiontype'];
        $data->decisiontypelabel = $this->get_decision_type_label($this->decision['decisiontype']);
        $data->status = $this->decision['status'];
        $data->statuslabel = $this->get_status_label($this->decision['status']);
        $data->statusclass = $this->get_status_class($this->decision['status']);
        $data->visibility = $this->decision['visibility'];
        $data->visibilitylabel = $this->get_visibility_label($this->decision['visibility']);

        $data->decisiontext = $this->decision['decisiontext'];
        $data->hasdecisiontext = $data->decisiontext !== '';

        $data->rationale = $this->decision['rationale'];
        $data->hasrationale = $data->rationale !== '';

        $data->evidencesummary = $this->decision['evidencesummary'];
        $data->hasevidencesummary = $data->evidencesummary !== '';

        $data->minutesurl = $this->decision['minutesurl'];
        $data->hasminutesurl = $data->minutesurl !== '';

        $data->archiveurl = $this->decision['archiveurl'];
        $data->hasarchiveurl = $data->archiveurl !== '';

        $data->decisionurl = $decisionurl->out(false);

        $data->timecreated = $this->decision['timecreated'];
        $data->timecreatedlabel = $this->decision['timecreated'] > 0 ? userdate($this->decision['timecreated']) : '';
        $data->hastimecreated = $this->decision['timecreated'] > 0;

        $data->timemodified = $this->decision['timemodified'];
        $data->timemodifiedlabel = $this->decision['timemodified'] > 0 ? userdate($this->decision['timemodified']) : '';
        $data->hastimemodified = $this->decision['timemodified'] > 0;

        $data->publishedat = $this->decision['publishedat'];
        $data->publishedatlabel = $this->decision['publishedat'] > 0 ? userdate($this->decision['publishedat']) : '';
        $data->haspublishedat = $this->decision['publishedat'] > 0;

        $data->contestuntil = $this->decision['contestuntil'];
        $data->contestuntillabel = $this->decision['contestuntil'] > 0 ? userdate($this->decision['contestuntil']) : '';
        $data->hascontestuntil = $this->decision['contestuntil'] > 0;
        $data->contestabilityopen = $this->decision['contestuntil'] > time();

        $data->integritystate = $this->decision['integritystate'];
        $data->integritystatelabel = $this->get_integrity_state_label($this->decision['integritystate']);
        $data->integritystateclass = $this->get_integrity_state_class($this->decision['integritystate']);

        $data->hasrestricteddata = $this->decision['hasrestricteddata'];
        $data->restrictedlabel = $data->hasrestricteddata ? get_string('restrictedvisibility', 'uckkassembly') : '';

        $data->readings = $this->build_reading_rows();
        $data->hasreadings = !empty($data->readings);

        $data->minorityreports = $this->build_minority_report_rows();
        $data->hasminorityreports = !empty($data->minorityreports);

        $data->contestations = $this->build_contestation_rows();
        $data->hascontestations = !empty($data->contestations);
        $data->opencontestationcount = $this->count_open_contestations();
        $data->hasopencontestations = $data->opencontestationcount > 0;

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->summaryitems = $this->build_summary_items($data);
        $data->notice = get_string('decisionnonsovereignnotice', 'uckkassembly');

        return $data;
    }

    /**
     * Normalise decision data.
     *
     * @param array<string, mixed>|stdClass $decision Raw decision data.
     * @return array<string, mixed>
     */
    private function normalise_decision(array|stdClass $decision): array {
        $row = (array)$decision;

        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));
        $type = $this->normalise_decision_type((string)($row['decisiontype'] ?? $row['type'] ?? self::TYPE_RECOMMENDATION));
        $integritystate = $this->normalise_integrity_state((string)($row['integritystate'] ?? self::INTEGRITY_UNVERIFIED));

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? get_string('decision', 'uckkassembly'))),
            'decisiontype' => $type,
            'status' => $status,
            'visibility' => clean_param((string)($row['visibility'] ?? 'course'), PARAM_ALPHANUMEXT),
            'decisiontext' => (string)($row['decisiontext'] ?? $row['content'] ?? ''),
            'rationale' => (string)($row['rationale'] ?? ''),
            'evidencesummary' => (string)($row['evidencesummary'] ?? ''),
            'minutesurl' => $this->normalise_url($row['minutesurl'] ?? null),
            'archiveurl' => $this->normalise_url($row['archiveurl'] ?? null),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
            'publishedat' => max(0, (int)($row['publishedat'] ?? $row['timepublished'] ?? 0)),
            'contestuntil' => max(0, (int)($row['contestuntil'] ?? $row['contestend'] ?? 0)),
            'integritystate' => $integritystate,
            'hasrestricteddata' => !empty($row['hasrestricteddata']) || (string)($row['visibility'] ?? '') === 'restricted_integrity',
        ];
    }

    /**
     * Normalise one reading row.
     *
     * @param array<string, mixed>|stdClass $reading Raw reading row.
     * @return array<string, mixed>
     */
    private function normalise_reading(array|stdClass $reading): array {
        $row = (array)$reading;

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'readingtype' => clean_param((string)($row['readingtype'] ?? $row['type'] ?? 'raw_count'), PARAM_ALPHANUMEXT),
            'label' => format_string((string)($row['label'] ?? '')),
            'summary' => (string)($row['summary'] ?? ''),
            'value' => format_string((string)($row['value'] ?? '')),
            'hasvalue' => trim((string)($row['value'] ?? '')) !== '',
            'weight' => (float)($row['weight'] ?? 0),
            'hasweight' => isset($row['weight']),
            'url' => $this->normalise_url($row['url'] ?? null),
        ];
    }

    /**
     * Normalise one minority report row.
     *
     * @param array<string, mixed>|stdClass $report Raw report row.
     * @return array<string, mixed>
     */
    private function normalise_minority_report(array|stdClass $report): array {
        $row = (array)$report;

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? get_string('minorityreport', 'uckkassembly'))),
            'summary' => (string)($row['summary'] ?? ''),
            'authorname' => format_string((string)($row['authorname'] ?? '')),
            'hasauthorname' => trim((string)($row['authorname'] ?? '')) !== '',
            'status' => clean_param((string)($row['status'] ?? 'active'), PARAM_ALPHANUMEXT),
            'statuslabel' => format_string((string)($row['statuslabel'] ?? '')),
            'url' => $this->normalise_url($row['url'] ?? null),
        ];
    }

    /**
     * Normalise one contestation row.
     *
     * @param array<string, mixed>|stdClass $contestation Raw contestation row.
     * @return array<string, mixed>
     */
    private function normalise_contestation(array|stdClass $contestation): array {
        $row = (array)$contestation;
        $status = clean_param((string)($row['status'] ?? 'opened'), PARAM_ALPHANUMEXT);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'status' => $status,
            'statuslabel' => $this->get_contestation_status_label($status, (string)($row['statuslabel'] ?? '')),
            'statusclass' => 'contestation-status-' . str_replace('_', '-', $status),
            'openedby' => format_string((string)($row['openedby'] ?? '')),
            'hasopenedby' => trim((string)($row['openedby'] ?? '')) !== '',
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'url' => $this->normalise_url($row['url'] ?? null),
        ];
    }

    /**
     * Normalise an action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action row.
     * @return array<string, mixed>
     */
    private function normalise_action(array|stdClass $action): array {
        $row = (array)$action;

        $key = clean_param((string)($row['key'] ?? $row['action'] ?? ''), PARAM_ALPHANUMEXT);
        $method = strtoupper(clean_param((string)($row['method'] ?? 'get'), PARAM_ALPHA));

        if (!in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        return [
            'key' => $key,
            'label' => format_string((string)($row['label'] ?? $this->get_action_label($key))),
            'url' => $this->normalise_url($row['url'] ?? null),
            'method' => $method,
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
        ];
    }

    /**
     * Build reading rows.
     *
     * @return array<int, stdClass>
     */
    private function build_reading_rows(): array {
        $rows = [];

        foreach ($this->readings as $reading) {
            $rows[] = (object)[
                'id' => $reading['id'],
                'readingtype' => $reading['readingtype'],
                'readingtypelabel' => $this->get_reading_type_label($reading['readingtype'], $reading['label']),
                'summary' => $reading['summary'],
                'hassummary' => $reading['summary'] !== '',
                'value' => $reading['value'],
                'hasvalue' => $reading['hasvalue'],
                'weight' => $reading['weight'],
                'hasweight' => $reading['hasweight'],
                'url' => $reading['url'],
                'hasurl' => $reading['url'] !== '',
            ];
        }

        return $rows;
    }

    /**
     * Build minority report rows.
     *
     * @return array<int, stdClass>
     */
    private function build_minority_report_rows(): array {
        $rows = [];

        foreach ($this->minorityreports as $report) {
            $rows[] = (object)[
                'id' => $report['id'],
                'title' => $report['title'],
                'summary' => $report['summary'],
                'hassummary' => $report['summary'] !== '',
                'authorname' => $report['authorname'],
                'hasauthorname' => $report['hasauthorname'],
                'status' => $report['status'],
                'statuslabel' => $report['statuslabel'],
                'hasstatuslabel' => $report['statuslabel'] !== '',
                'url' => $report['url'],
                'hasurl' => $report['url'] !== '',
            ];
        }

        return $rows;
    }

    /**
     * Build contestation rows.
     *
     * @return array<int, stdClass>
     */
    private function build_contestation_rows(): array {
        $rows = [];

        foreach ($this->contestations as $contestation) {
            $rows[] = (object)[
                'id' => $contestation['id'],
                'summary' => $contestation['summary'],
                'status' => $contestation['status'],
                'statuslabel' => $contestation['statuslabel'],
                'statusclass' => $contestation['statusclass'],
                'openedby' => $contestation['openedby'],
                'hasopenedby' => $contestation['hasopenedby'],
                'timecreated' => $contestation['timecreated'],
                'timecreatedlabel' => $contestation['timecreated'] > 0 ? userdate($contestation['timecreated']) : '',
                'hastimecreated' => $contestation['timecreated'] > 0,
                'url' => $contestation['url'],
                'hasurl' => $contestation['url'] !== '',
            ];
        }

        return $rows;
    }

    /**
     * Build action rows.
     *
     * @return array<int, stdClass>
     */
    private function build_action_rows(): array {
        $rows = [];

        foreach ($this->actions as $action) {
            if ($action['key'] === '' || $action['url'] === '') {
                continue;
            }

            $rows[] = (object)[
                'key' => $action['key'],
                'label' => $action['label'],
                'url' => $action['url'],
                'method' => $action['method'],
                'ispost' => $action['method'] === 'POST',
                'isget' => $action['method'] === 'GET',
                'primary' => $action['primary'],
                'danger' => $action['danger'],
                'secondary' => !$action['primary'] && !$action['danger'],
                'disabled' => $action['disabled'],
                'disabledreason' => $action['disabledreason'],
                'hasdisabledreason' => $action['disabledreason'] !== '',
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hasconfirmmessage' => $action['confirmmessage'] !== '',
            ];
        }

        return $rows;
    }

    /**
     * Build summary chips/items.
     *
     * @param stdClass $data Exported decision data.
     * @return array<int, stdClass>
     */
    private function build_summary_items(stdClass $data): array {
        return [
            (object)[
                'key' => 'decisiontype',
                'label' => get_string('decisiontype', 'uckkassembly'),
                'value' => $data->decisiontypelabel,
                'class' => 'decision-type-' . str_replace('_', '-', $data->decisiontype),
            ],
            (object)[
                'key' => 'status',
                'label' => get_string('status'),
                'value' => $data->statuslabel,
                'class' => $data->statusclass,
            ],
            (object)[
                'key' => 'integrity',
                'label' => get_string('integritystate', 'uckkassembly'),
                'value' => $data->integritystatelabel,
                'class' => $data->integritystateclass,
            ],
            (object)[
                'key' => 'contestations',
                'label' => get_string('opencontestations', 'uckkassembly'),
                'value' => (string)$data->opencontestationcount,
                'class' => $data->hasopencontestations ? 'state-warning' : 'state-clear',
            ],
        ];
    }

    /**
     * Count open contestations.
     *
     * @return int
     */
    private function count_open_contestations(): int {
        $closed = ['resolved', 'dismissed', 'archived', 'closed'];
        $count = 0;

        foreach ($this->contestations as $contestation) {
            if (!in_array($contestation['status'], $closed, true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Normalise decision type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_decision_type(string $type): string {
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
     * Normalise decision status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_PUBLISHED,
            self::STATUS_CONTESTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_INVALIDATED,
            self::STATUS_ARCHIVED,
            self::STATUS_CLOSED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise integrity state.
     *
     * @param string $state Raw state.
     * @return string
     */
    private function normalise_integrity_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);

        $allowed = [
            self::INTEGRITY_UNVERIFIED,
            self::INTEGRITY_HUMAN_REVIEWED,
            self::INTEGRITY_VERIFIED,
            self::INTEGRITY_CONTESTED,
            self::INTEGRITY_INVALIDATED,
            self::INTEGRITY_ARCHIVED,
        ];

        return in_array($state, $allowed, true) ? $state : self::INTEGRITY_UNVERIFIED;
    }

    /**
     * Get decision type label.
     *
     * @param string $type Decision type.
     * @return string
     */
    private function get_decision_type_label(string $type): string {
        return $this->get_component_string(
            'decisiontype:' . str_replace('_', '', $type),
            ucfirst(str_replace('_', ' ', $type))
        );
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return $this->get_component_string(
            'decisionstatus:' . str_replace('_', '', $status),
            ucfirst(str_replace('_', ' ', $status))
        );
    }

    /**
     * Get visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private function get_visibility_label(string $visibility): string {
        return $this->get_component_string(
            'visibility:' . str_replace('_', '', $visibility),
            ucfirst(str_replace('_', ' ', $visibility))
        );
    }

    /**
     * Get integrity state label.
     *
     * @param string $state Integrity state.
     * @return string
     */
    private function get_integrity_state_label(string $state): string {
        return $this->get_component_string(
            'integritystate:' . str_replace('_', '', $state),
            ucfirst(str_replace('_', ' ', $state))
        );
    }

    /**
     * Get reading type label.
     *
     * @param string $type Reading type.
     * @param string $fallback Explicit fallback.
     * @return string
     */
    private function get_reading_type_label(string $type, string $fallback = ''): string {
        if ($fallback !== '') {
            return $fallback;
        }

        return $this->get_component_string(
            'readingtype:' . str_replace('_', '', $type),
            ucfirst(str_replace('_', ' ', $type))
        );
    }

    /**
     * Get contestation status label.
     *
     * @param string $status Contestation status.
     * @param string $fallback Explicit fallback.
     * @return string
     */
    private function get_contestation_status_label(string $status, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        return $this->get_component_string(
            'contestationstatus:' . str_replace('_', '', $status),
            ucfirst(str_replace('_', ' ', $status))
        );
    }

    /**
     * Get default action label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_label(string $key): string {
        if ($key === '') {
            return get_string('view');
        }

        return $this->get_component_string(
            'decisionaction:' . str_replace('_', '', $key),
            ucfirst(str_replace('_', ' ', $key))
        );
    }

    /**
     * Get decision status CSS class.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return 'decision-status-' . str_replace('_', '-', $status);
    }

    /**
     * Get integrity state CSS class.
     *
     * @param string $state Integrity state.
     * @return string
     */
    private function get_integrity_state_class(string $state): string {
        return 'integrity-state-' . str_replace('_', '-', $state);
    }

    /**
     * Return a component string if it exists, otherwise fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function get_component_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return $fallback;
    }

    /**
     * Normalise a URL.
     *
     * @param mixed $url URL input.
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
