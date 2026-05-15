<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Minutes panel output object for UCKK Assemblies.
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
 * Renderable panel for Assembly minutes.
 *
 * This class prepares display data only. It must not publish minutes, modify
 * decisions, resolve contestations, validate integrity, or archive records.
 */
final class minutes_panel implements renderable, templatable {
    /** Minutes type: working notes. */
    private const TYPE_WORKING = 'working';

    /** Minutes type: official record. */
    private const TYPE_OFFICIAL = 'official';

    /** Minutes type: public summary. */
    private const TYPE_PUBLIC_SUMMARY = 'public_summary';

    /** Minutes type: restricted integrity summary. */
    private const TYPE_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Status: draft. */
    private const STATUS_DRAFT = 'draft';

    /** Status: active. */
    private const STATUS_ACTIVE = 'active';

    /** Status: pending review. */
    private const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    private const STATUS_VALIDATED = 'validated';

    /** Status: correction required. */
    private const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    private const STATUS_CONTESTED = 'contested';

    /** Status: closed. */
    private const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    private const STATUS_ARCHIVED = 'archived';

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
     * Moodle context id.
     *
     * @var int
     */
    private int $contextid;

    /**
     * Assembly title.
     *
     * @var string
     */
    private string $assemblytitle;

    /**
     * Prepared minutes rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $minutes;

    /**
     * Prepared action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Panel options.
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $contextid Moodle context id.
     * @param string $assemblytitle Assembly title.
     * @param array<int, array<string, mixed>|stdClass> $minutes Permission-filtered minutes rows.
     * @param array<int, array<string, mixed>|stdClass> $actions Permission-filtered action rows.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct(
        int $assemblyid,
        int $cmid,
        int $contextid,
        string $assemblytitle,
        array $minutes = [],
        array $actions = [],
        array $options = []
    ) {
        $this->assemblyid = max(0, $assemblyid);
        $this->cmid = max(0, $cmid);
        $this->contextid = max(0, $contextid);
        $this->assemblytitle = format_string($assemblytitle);
        $this->minutes = array_map([$this, 'normalise_minutes'], $minutes);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
        $this->options = $options;
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $latest = $this->get_latest_minutes();
        $history = $this->get_history_minutes($latest['id'] ?? 0);

        $data = new stdClass();
        $data->assemblyid = $this->assemblyid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->assemblytitle = $this->assemblytitle;
        $data->heading = (string)($this->options['heading'] ?? get_string('minutes', 'uckkassembly'));

        $data->viewurl = (new moodle_url('/mod/uckkassembly/view.php', ['id' => $this->cmid]))->out(false);
        $data->minutesurl = (new moodle_url('/mod/uckkassembly/minutes.php', ['id' => $this->cmid]))->out(false);

        $data->hasminutes = !empty($this->minutes);
        $data->latest = $latest ? $this->export_minutes_row($latest, true) : null;
        $data->haslatest = $latest !== null;

        $data->history = array_map(
            fn(array $minutes): stdClass => $this->export_minutes_row($minutes, false),
            $history
        );
        $data->hashistory = !empty($data->history);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->counts = $this->build_counts();
        $data->summaryitems = $this->build_summary_items($data->counts);

        $data->hasofficial = $this->has_minutes_type(self::TYPE_OFFICIAL);
        $data->haspublicsummary = $this->has_minutes_type(self::TYPE_PUBLIC_SUMMARY);
        $data->hasrestricted = $this->has_restricted_minutes();
        $data->hascontested = $this->has_status(self::STATUS_CONTESTED);
        $data->hascorrectionrequired = $this->has_status(self::STATUS_CORRECTION_REQUIRED);
        $data->hasarchived = $this->has_status(self::STATUS_ARCHIVED);

        $data->emptylabel = get_string('nominutes', 'uckkassembly');
        $data->restrictednotice = $data->hasrestricted ? get_string('minutesrestrictednotice', 'uckkassembly') : '';
        $data->notice = get_string('minutesnonsovereignnotice', 'uckkassembly');

        return $data;
    }

    /**
     * Normalise one minutes row.
     *
     * @param array<string, mixed>|stdClass $minutes Raw row.
     * @return array<string, mixed>
     */
    private function normalise_minutes(array|stdClass $minutes): array {
        $row = (array)$minutes;

        $type = $this->normalise_type((string)($row['minutestype'] ?? $row['type'] ?? self::TYPE_WORKING));
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'assemblyid' => max(0, (int)($row['assemblyid'] ?? $this->assemblyid)),
            'motionid' => max(0, (int)($row['motionid'] ?? 0)),
            'decisionid' => max(0, (int)($row['decisionid'] ?? 0)),
            'minutestype' => $type,
            'minutestypelabel' => $this->get_type_label($type, (string)($row['minutestypelabel'] ?? '')),
            'title' => format_string((string)($row['title'] ?? get_string('minutes', 'uckkassembly'))),
            'bodyhtml' => (string)($row['bodyhtml'] ?? $row['body'] ?? ''),
            'hasbody' => trim((string)($row['bodyhtml'] ?? $row['body'] ?? '')) !== '',
            'publicsummaryhtml' => (string)($row['publicsummaryhtml'] ?? $row['publicsummary'] ?? ''),
            'haspublicsummary' => trim((string)($row['publicsummaryhtml'] ?? $row['publicsummary'] ?? '')) !== '',
            'decisionsummaryhtml' => (string)($row['decisionsummaryhtml'] ?? $row['decisionsummary'] ?? ''),
            'hasdecisionsummary' => trim((string)($row['decisionsummaryhtml'] ?? $row['decisionsummary'] ?? '')) !== '',
            'status' => $status,
            'statuslabel' => $this->get_status_label($status, (string)($row['statuslabel'] ?? '')),
            'statusclass' => 'status-' . str_replace('_', '-', $status),
            'visibility' => clean_param((string)($row['visibility'] ?? 'course'), PARAM_ALPHANUMEXT),
            'visibilitylabel' => format_string((string)($row['visibilitylabel'] ?? '')),
            'versionno' => max(1, (int)($row['versionno'] ?? 1)),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
            'timepublished' => max(0, (int)($row['timepublished'] ?? 0)),
            'createdbyname' => format_string((string)($row['createdbyname'] ?? '')),
            'modifiedbyname' => format_string((string)($row['modifiedbyname'] ?? '')),
            'url' => $this->normalise_url($row['url'] ?? null),
            'editurl' => $this->normalise_url($row['editurl'] ?? null),
            'archiveurl' => $this->normalise_url($row['archiveurl'] ?? null),
            'restricted' => !empty($row['restricted']) || $type === self::TYPE_RESTRICTED_INTEGRITY,
            'canedit' => !empty($row['canedit']),
            'canarchive' => !empty($row['canarchive']),
            'metadata' => $this->normalise_metadata($row['metadata'] ?? []),
        ];
    }

    /**
     * Normalise one action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action.
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
     * Export a minutes row.
     *
     * @param array<string, mixed> $minutes Normalised minutes row.
     * @param bool $islatest Whether this is the latest minutes record.
     * @return stdClass
     */
    private function export_minutes_row(array $minutes, bool $islatest): stdClass {
        return (object)[
            'id' => $minutes['id'],
            'assemblyid' => $minutes['assemblyid'],
            'motionid' => $minutes['motionid'],
            'hasmotion' => $minutes['motionid'] > 0,
            'decisionid' => $minutes['decisionid'],
            'hasdecision' => $minutes['decisionid'] > 0,
            'minutestype' => $minutes['minutestype'],
            'minutestypelabel' => $minutes['minutestypelabel'],
            'title' => $minutes['title'],
            'bodyhtml' => $minutes['bodyhtml'],
            'hasbody' => $minutes['hasbody'],
            'publicsummaryhtml' => $minutes['publicsummaryhtml'],
            'haspublicsummary' => $minutes['haspublicsummary'],
            'decisionsummaryhtml' => $minutes['decisionsummaryhtml'],
            'hasdecisionsummary' => $minutes['hasdecisionsummary'],
            'status' => $minutes['status'],
            'statuslabel' => $minutes['statuslabel'],
            'statusclass' => $minutes['statusclass'],
            'visibility' => $minutes['visibility'],
            'visibilitylabel' => $minutes['visibilitylabel'],
            'hasvisibilitylabel' => $minutes['visibilitylabel'] !== '',
            'versionno' => $minutes['versionno'],
            'timecreated' => $minutes['timecreated'],
            'timecreatedlabel' => $minutes['timecreated'] > 0 ? userdate($minutes['timecreated']) : '',
            'hastimecreated' => $minutes['timecreated'] > 0,
            'timemodified' => $minutes['timemodified'],
            'timemodifiedlabel' => $minutes['timemodified'] > 0 ? userdate($minutes['timemodified']) : '',
            'hastimemodified' => $minutes['timemodified'] > 0,
            'timepublished' => $minutes['timepublished'],
            'timepublishedlabel' => $minutes['timepublished'] > 0 ? userdate($minutes['timepublished']) : '',
            'hastimepublished' => $minutes['timepublished'] > 0,
            'createdbyname' => $minutes['createdbyname'],
            'hascreatedbyname' => $minutes['createdbyname'] !== '',
            'modifiedbyname' => $minutes['modifiedbyname'],
            'hasmodifiedbyname' => $minutes['modifiedbyname'] !== '',
            'url' => $minutes['url'],
            'hasurl' => $minutes['url'] !== '',
            'editurl' => $minutes['editurl'],
            'hasediturl' => $minutes['editurl'] !== '',
            'archiveurl' => $minutes['archiveurl'],
            'hasarchiveurl' => $minutes['archiveurl'] !== '',
            'restricted' => $minutes['restricted'],
            'canedit' => $minutes['canedit'],
            'canarchive' => $minutes['canarchive'],
            'islatest' => $islatest,
        ];
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
     * Build panel counts.
     *
     * @return stdClass
     */
    private function build_counts(): stdClass {
        $counts = (object)[
            'total' => count($this->minutes),
            'draft' => 0,
            'official' => 0,
            'publicsummary' => 0,
            'restricted' => 0,
            'archived' => 0,
        ];

        foreach ($this->minutes as $minutes) {
            if ($minutes['status'] === self::STATUS_DRAFT) {
                $counts->draft++;
            }

            if ($minutes['minutestype'] === self::TYPE_OFFICIAL) {
                $counts->official++;
            }

            if ($minutes['minutestype'] === self::TYPE_PUBLIC_SUMMARY) {
                $counts->publicsummary++;
            }

            if (!empty($minutes['restricted'])) {
                $counts->restricted++;
            }

            if ($minutes['status'] === self::STATUS_ARCHIVED) {
                $counts->archived++;
            }
        }

        return $counts;
    }

    /**
     * Build summary item rows.
     *
     * @param stdClass $counts Counts.
     * @return array<int, stdClass>
     */
    private function build_summary_items(stdClass $counts): array {
        return [
            (object)[
                'key' => 'total',
                'label' => get_string('minutesplural', 'uckkassembly'),
                'value' => (string)$counts->total,
                'class' => 'minutes-count-total',
            ],
            (object)[
                'key' => 'official',
                'label' => get_string('minutestype:official', 'uckkassembly'),
                'value' => (string)$counts->official,
                'class' => 'minutes-count-official',
            ],
            (object)[
                'key' => 'restricted',
                'label' => get_string('restrictedvisibility', 'uckkassembly'),
                'value' => (string)$counts->restricted,
                'class' => $counts->restricted > 0 ? 'minutes-count-restricted' : 'minutes-count-clear',
            ],
            (object)[
                'key' => 'archived',
                'label' => get_string('status:archived', 'uckkassembly'),
                'value' => (string)$counts->archived,
                'class' => 'minutes-count-archived',
            ],
        ];
    }

    /**
     * Return latest minutes row.
     *
     * @return array<string, mixed>|null
     */
    private function get_latest_minutes(): ?array {
        if (empty($this->minutes)) {
            return null;
        }

        $minutes = $this->minutes;

        usort($minutes, static function(array $a, array $b): int {
            $apublished = (int)$a['timepublished'];
            $bpublished = (int)$b['timepublished'];

            if ($apublished !== $bpublished) {
                return $bpublished <=> $apublished;
            }

            return ((int)$b['timemodified']) <=> ((int)$a['timemodified']);
        });

        return $minutes[0];
    }

    /**
     * Return historical minutes rows excluding latest id.
     *
     * @param int $latestid Latest minutes id.
     * @return array<int, array<string, mixed>>
     */
    private function get_history_minutes(int $latestid): array {
        $history = array_filter($this->minutes, static function(array $minutes) use ($latestid): bool {
            return (int)$minutes['id'] !== $latestid;
        });

        usort($history, static function(array $a, array $b): int {
            return ((int)$b['timemodified']) <=> ((int)$a['timemodified']);
        });

        return array_values($history);
    }

    /**
     * Check for a minutes type.
     *
     * @param string $type Minutes type.
     * @return bool
     */
    private function has_minutes_type(string $type): bool {
        foreach ($this->minutes as $minutes) {
            if ($minutes['minutestype'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for a minutes status.
     *
     * @param string $status Status.
     * @return bool
     */
    private function has_status(string $status): bool {
        foreach ($this->minutes as $minutes) {
            if ($minutes['status'] === $status) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether any minutes are restricted.
     *
     * @return bool
     */
    private function has_restricted_minutes(): bool {
        foreach ($this->minutes as $minutes) {
            if (!empty($minutes['restricted'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise minutes type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        $allowed = [
            self::TYPE_WORKING,
            self::TYPE_OFFICIAL,
            self::TYPE_PUBLIC_SUMMARY,
            self::TYPE_RESTRICTED_INTEGRITY,
        ];

        return in_array($type, $allowed, true) ? $type : self::TYPE_WORKING;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Get type label.
     *
     * @param string $type Type.
     * @param string $fallback Fallback.
     * @return string
     */
    private function get_type_label(string $type, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'minutestype:' . str_replace('_', '', $type);

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @param string $fallback Fallback.
     * @return string
     */
    private function get_status_label(string $status, string $fallback = ''): string {
        if ($fallback !== '') {
            return format_string($fallback);
        }

        $key = 'status:' . $status;

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get default action label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_label(string $key): string {
        $stringkey = 'minutesaction:' . str_replace('_', '', $key);

        if ($key !== '' && get_string_manager()->string_exists($stringkey, 'uckkassembly')) {
            return get_string($stringkey, 'uckkassembly');
        }

        return get_string('view');
    }

    /**
     * Normalise URL.
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

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Metadata value.
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
}
