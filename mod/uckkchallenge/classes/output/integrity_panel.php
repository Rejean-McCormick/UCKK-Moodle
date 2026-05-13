<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Integrity panel output object for a UCKK challenge.
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
 * Renderable integrity panel for Défis King Klown.
 *
 * This class prepares display data only. It must not open cases, close cases,
 * issue corrections, invalidate submissions, alter evidence, award badges, or
 * decide integrity outcomes.
 */
final class integrity_panel implements renderable, templatable {
    /** Integrity state: unverified. */
    public const STATE_UNVERIFIED = 'unverified';

    /** Integrity state: human reviewed. */
    public const STATE_HUMAN_REVIEWED = 'human_reviewed';

    /** Integrity state: verified. */
    public const STATE_VERIFIED = 'verified';

    /** Integrity state: contested. */
    public const STATE_CONTESTED = 'contested';

    /** Integrity state: invalidated. */
    public const STATE_INVALIDATED = 'invalidated';

    /** Integrity state: archived. */
    public const STATE_ARCHIVED = 'archived';

    /** Case state: opened. */
    public const CASE_OPENED = 'opened';

    /** Case state: triaged. */
    public const CASE_TRIAGED = 'triaged';

    /** Case state: under review. */
    public const CASE_UNDER_REVIEW = 'under_review';

    /** Case state: waiting for response. */
    public const CASE_WAITING_FOR_RESPONSE = 'waiting_for_response';

    /** Case state: correction required. */
    public const CASE_CORRECTION_REQUIRED = 'correction_required';

    /** Case state: resolved. */
    public const CASE_RESOLVED = 'resolved';

    /** Case state: dismissed. */
    public const CASE_DISMISSED = 'dismissed';

    /** Case state: escalated. */
    public const CASE_ESCALATED = 'escalated';

    /** Case state: archived. */
    public const CASE_ARCHIVED = 'archived';

    /**
     * Challenge id.
     *
     * @var int
     */
    private int $challengeid;

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
     * Challenge title.
     *
     * @var string
     */
    private string $challengetitle;

    /**
     * Prepared integrity summary.
     *
     * @var array<string, mixed>
     */
    private array $summary;

    /**
     * Prepared integrity case rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $cases;

    /**
     * Prepared action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Constructor.
     *
     * @param int $challengeid Challenge instance id.
     * @param int $cmid Course module id.
     * @param int $contextid Moodle context id.
     * @param string $challengetitle Challenge title.
     * @param array<string, mixed> $summary Permission-filtered integrity summary.
     * @param array<int, array<string, mixed>|stdClass> $cases Permission-filtered case rows.
     * @param array<int, array<string, mixed>|stdClass> $actions Permission-filtered action rows.
     */
    public function __construct(
        int $challengeid,
        int $cmid,
        int $contextid,
        string $challengetitle,
        array $summary = [],
        array $cases = [],
        array $actions = []
    ) {
        $this->challengeid = max(0, $challengeid);
        $this->cmid = max(0, $cmid);
        $this->contextid = max(0, $contextid);
        $this->challengetitle = format_string($challengetitle);
        $this->summary = $summary;
        $this->cases = array_map([$this, 'normalise_case'], $cases);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
    }

    /**
     * Export context for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $state = $this->normalise_integrity_state((string)($this->summary['integritystate'] ?? self::STATE_UNVERIFIED));
        $challengeurl = new moodle_url('/mod/uckkchallenge/view.php', ['id' => $this->cmid]);
        $integrityurl = new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $this->cmid]);

        $data = new stdClass();
        $data->challengeid = $this->challengeid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->challengetitle = $this->challengetitle;
        $data->heading = get_string('integritypanel', 'uckkchallenge');

        $data->challengeurl = $challengeurl->out(false);
        $data->integrityurl = $integrityurl->out(false);

        $data->integritystate = $state;
        $data->integritystatelabel = $this->get_integrity_state_label($state);
        $data->integritystateclass = $this->get_state_class($state);

        $data->challengestatus = clean_param((string)($this->summary['challengestatus'] ?? ''), PARAM_ALPHANUMEXT);
        $data->challengestatuslabel = format_string((string)($this->summary['challengestatuslabel'] ?? $data->challengestatus));

        $data->hasrestricteddata = !empty($this->summary['hasrestricteddata']);
        $data->restrictedlabel = $data->hasrestricteddata
            ? get_string('restrictedvisibility', 'uckkchallenge')
            : '';

        $data->opencasecount = (int)($this->summary['opencasecount'] ?? $this->count_open_cases());
        $data->totalcasecount = count($this->cases);
        $data->hasopencases = $data->opencasecount > 0;

        $data->warnings = $this->normalise_warnings($this->summary['warnings'] ?? []);
        $data->haswarnings = !empty($data->warnings);

        $data->cases = $this->build_case_rows();
        $data->hascases = !empty($data->cases);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->hascorrectionrequired = $this->has_case_state(self::CASE_CORRECTION_REQUIRED);
        $data->hascontestation = $state === self::STATE_CONTESTED || $this->has_case_type('challenge_dispute');
        $data->hasinvalidation = $state === self::STATE_INVALIDATED || $this->has_case_state(self::CASE_ESCALATED);

        $data->summaryitems = $this->build_summary_items($data);
        $data->notice = get_string('integritynonsovereignnotice', 'uckkchallenge');
        $data->emptycaseslabel = get_string('integritycases:none', 'uckkchallenge');
        $data->emptyactionslabel = get_string('integrityactions:none', 'uckkchallenge');

        return $data;
    }

    /**
     * Normalise an integrity case row.
     *
     * @param array<string, mixed>|stdClass $case Raw case row.
     * @return array<string, mixed>
     */
    private function normalise_case(array|stdClass $case): array {
        $row = (array)$case;

        $status = $this->normalise_case_state((string)($row['status'] ?? self::CASE_OPENED));
        $casetype = clean_param((string)($row['casetype'] ?? $row['type'] ?? 'challenge_dispute'), PARAM_ALPHANUMEXT);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'casetype' => $casetype,
            'casetypelabel' => $this->get_case_type_label($casetype, (string)($row['casetypelabel'] ?? '')),
            'status' => $status,
            'statuslabel' => $this->get_case_state_label($status, (string)($row['statuslabel'] ?? '')),
            'statusclass' => $this->get_case_state_class($status),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'hasnotes' => trim((string)($row['notes'] ?? '')) !== '',
            'notes' => format_string((string)($row['notes'] ?? '')),
            'openedby' => format_string((string)($row['openedby'] ?? '')),
            'hasopenedby' => trim((string)($row['openedby'] ?? '')) !== '',
            'assignedto' => format_string((string)($row['assignedto'] ?? '')),
            'hasassignedto' => trim((string)($row['assignedto'] ?? '')) !== '',
            'timecreated' => (int)($row['timecreated'] ?? 0),
            'timemodified' => (int)($row['timemodified'] ?? 0),
            'url' => $this->normalise_url($row['url'] ?? null),
            'restricted' => !empty($row['restricted']),
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
        $method = strtoupper(clean_param((string)($row['method'] ?? 'post'), PARAM_ALPHA));

        if (!in_array($method, ['GET', 'POST'], true)) {
            $method = 'POST';
        }

        return [
            'key' => $key,
            'label' => format_string((string)($row['label'] ?? $this->get_action_label($key))),
            'description' => format_string((string)($row['description'] ?? '')),
            'hasdescription' => trim((string)($row['description'] ?? '')) !== '',
            'url' => $this->normalise_url($row['url'] ?? null),
            'method' => $method,
            'danger' => !empty($row['danger']),
            'primary' => !empty($row['primary']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'hasdisabledreason' => trim((string)($row['disabledreason'] ?? '')) !== '',
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
            'caseid' => max(0, (int)($row['caseid'] ?? 0)),
        ];
    }

    /**
     * Build exported case rows.
     *
     * @return array<int, stdClass>
     */
    private function build_case_rows(): array {
        $rows = [];

        foreach ($this->cases as $case) {
            $rows[] = (object)[
                'id' => $case['id'],
                'casetype' => $case['casetype'],
                'casetypelabel' => $case['casetypelabel'],
                'status' => $case['status'],
                'statuslabel' => $case['statuslabel'],
                'statusclass' => $case['statusclass'],
                'summary' => $case['summary'],
                'hasnotes' => $case['hasnotes'],
                'notes' => $case['notes'],
                'openedby' => $case['openedby'],
                'hasopenedby' => $case['hasopenedby'],
                'assignedto' => $case['assignedto'],
                'hasassignedto' => $case['hasassignedto'],
                'timecreated' => $case['timecreated'],
                'timecreatedlabel' => $case['timecreated'] > 0 ? userdate($case['timecreated']) : '',
                'hastimecreated' => $case['timecreated'] > 0,
                'timemodified' => $case['timemodified'],
                'timemodifiedlabel' => $case['timemodified'] > 0 ? userdate($case['timemodified']) : '',
                'hastimemodified' => $case['timemodified'] > 0,
                'url' => $case['url'],
                'hasurl' => $case['url'] !== '',
                'restricted' => $case['restricted'],
            ];
        }

        return $rows;
    }

    /**
     * Build exported action rows.
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
                'description' => $action['description'],
                'hasdescription' => $action['hasdescription'],
                'url' => $action['url'],
                'method' => $action['method'],
                'ispost' => $action['method'] === 'POST',
                'isget' => $action['method'] === 'GET',
                'danger' => $action['danger'],
                'primary' => $action['primary'],
                'secondary' => !$action['primary'] && !$action['danger'],
                'disabled' => $action['disabled'],
                'disabledreason' => $action['disabledreason'],
                'hasdisabledreason' => $action['hasdisabledreason'],
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hascaseid' => $action['caseid'] > 0,
                'caseid' => $action['caseid'],
            ];
        }

        return $rows;
    }

    /**
     * Build summary items for the top of the panel.
     *
     * @param stdClass $data Exported base data.
     * @return array<int, stdClass>
     */
    private function build_summary_items(stdClass $data): array {
        return [
            (object)[
                'key' => 'integritystate',
                'label' => get_string('integritystate', 'uckkchallenge'),
                'value' => $data->integritystatelabel,
                'class' => $data->integritystateclass,
            ],
            (object)[
                'key' => 'opencases',
                'label' => get_string('openintegritycases', 'uckkchallenge'),
                'value' => (string)$data->opencasecount,
                'class' => $data->hasopencases ? 'state-warning' : 'state-clear',
            ],
            (object)[
                'key' => 'restricted',
                'label' => get_string('restrictedvisibility', 'uckkchallenge'),
                'value' => $data->hasrestricteddata ? get_string('yes') : get_string('no'),
                'class' => $data->hasrestricteddata ? 'state-restricted' : 'state-clear',
            ],
        ];
    }

    /**
     * Normalise warnings.
     *
     * @param mixed $warnings Warning list.
     * @return array<int, stdClass>
     */
    private function normalise_warnings(mixed $warnings): array {
        if (!is_array($warnings)) {
            return [];
        }

        $normalised = [];

        foreach ($warnings as $warning) {
            if (is_string($warning)) {
                $message = trim($warning);
                $severity = 'warning';
            } else {
                $row = (array)$warning;
                $message = trim((string)($row['message'] ?? ''));
                $severity = clean_param((string)($row['severity'] ?? 'warning'), PARAM_ALPHANUMEXT);
            }

            if ($message === '') {
                continue;
            }

            $normalised[] = (object)[
                'message' => format_string($message),
                'severity' => $severity,
                'class' => 'alert-' . $this->get_warning_class($severity),
            ];
        }

        return $normalised;
    }

    /**
     * Count open cases.
     *
     * @return int
     */
    private function count_open_cases(): int {
        $count = 0;

        foreach ($this->cases as $case) {
            if (!in_array($case['status'], [self::CASE_RESOLVED, self::CASE_DISMISSED, self::CASE_ARCHIVED], true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check whether any case has a given state.
     *
     * @param string $state Case state.
     * @return bool
     */
    private function has_case_state(string $state): bool {
        foreach ($this->cases as $case) {
            if ($case['status'] === $state) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether any case has a given type.
     *
     * @param string $type Case type.
     * @return bool
     */
    private function has_case_type(string $type): bool {
        foreach ($this->cases as $case) {
            if ($case['casetype'] === $type) {
                return true;
            }
        }

        return false;
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
            self::STATE_UNVERIFIED,
            self::STATE_HUMAN_REVIEWED,
            self::STATE_VERIFIED,
            self::STATE_CONTESTED,
            self::STATE_INVALIDATED,
            self::STATE_ARCHIVED,
        ];

        return in_array($state, $allowed, true) ? $state : self::STATE_UNVERIFIED;
    }

    /**
     * Normalise case state.
     *
     * @param string $state Raw state.
     * @return string
     */
    private function normalise_case_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);

        $allowed = [
            self::CASE_OPENED,
            self::CASE_TRIAGED,
            self::CASE_UNDER_REVIEW,
            self::CASE_WAITING_FOR_RESPONSE,
            self::CASE_CORRECTION_REQUIRED,
            self::CASE_RESOLVED,
            self::CASE_DISMISSED,
            self::CASE_ESCALATED,
            self::CASE_ARCHIVED,
        ];

        return in_array($state, $allowed, true) ? $state : self::CASE_OPENED;
    }

    /**
     * Get integrity state label.
     *
     * @param string $state Canonical state.
     * @return string
     */
    private function get_integrity_state_label(string $state): string {
        $key = 'integritystate:' . str_replace('_', '', $state);

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Get case state label.
     *
     * @param string $state Canonical case state.
     * @param string $fallback Fallback label.
     * @return string
     */
    private function get_case_state_label(string $state, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'integritycase_status:' . str_replace('_', '', $state);

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Get case type label.
     *
     * @param string $type Canonical case type.
     * @param string $fallback Fallback label.
     * @return string
     */
    private function get_case_type_label(string $type, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'integritycase_type:' . str_replace('_', '', $type);

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get default action label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_label(string $key): string {
        $stringkey = 'integrityaction:' . str_replace('_', '', $key);

        if ($key !== '' && get_string_manager()->string_exists($stringkey, 'uckkchallenge')) {
            return get_string($stringkey, 'uckkchallenge');
        }

        return get_string('view');
    }

    /**
     * Get CSS class for integrity state.
     *
     * @param string $state Integrity state.
     * @return string
     */
    private function get_state_class(string $state): string {
        return 'integrity-state-' . str_replace('_', '-', $state);
    }

    /**
     * Get CSS class for case state.
     *
     * @param string $state Case state.
     * @return string
     */
    private function get_case_state_class(string $state): string {
        return 'case-state-' . str_replace('_', '-', $state);
    }

    /**
     * Map warning severity to Bootstrap alert class suffix.
     *
     * @param string $severity Severity key.
     * @return string
     */
    private function get_warning_class(string $severity): string {
        return match ($severity) {
            'danger', 'error', 'invalidated' => 'danger',
            'info', 'notice' => 'info',
            'success', 'clear' => 'success',
            default => 'warning',
        };
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