<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main assembly view output object.
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
 * Renderable main view for one UCKK assembly.
 *
 * This class prepares display data only. It must not create motions, amend
 * motions, record votes, publish decisions, resolve contests, archive minutes,
 * or execute integrity actions.
 */
final class assembly_view implements renderable, templatable {
    /** Assembly type: savoirs. */
    public const TYPE_SAVOIRS = 'savoirs';

    /** Assembly type: defis. */
    public const TYPE_DEFIS = 'defis';

    /** Assembly type: joueurs. */
    public const TYPE_JOUEURS = 'joueurs';

    /** Assembly type: batisseurs. */
    public const TYPE_BATISSEURS = 'batisseurs';

    /** Assembly type: inquisiteurs. */
    public const TYPE_INQUISITEURS = 'inquisiteurs';

    /** Assembly type: grand jeu. */
    public const TYPE_GRAND_JEU = 'grand_jeu';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Decision status: proposed. */
    public const DECISION_PROPOSED = 'proposed';

    /** Decision status: adopted. */
    public const DECISION_ADOPTED = 'adopted';

    /** Decision status: rejected. */
    public const DECISION_REJECTED = 'rejected';

    /** Decision status: contested. */
    public const DECISION_CONTESTED = 'contested';

    /** Decision status: archived. */
    public const DECISION_ARCHIVED = 'archived';

    /**
     * Assembly instance id.
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
     * Course id.
     *
     * @var int
     */
    private int $courseid;

    /**
     * Context id.
     *
     * @var int
     */
    private int $contextid;

    /**
     * Assembly record data.
     *
     * @var array<string, mixed>
     */
    private array $assembly;

    /**
     * Permission-filtered motion rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $motions;

    /**
     * Permission-filtered decision rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $decisions;

    /**
     * Permission-filtered contest rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $contests;

    /**
     * Permission-filtered minutes rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $minutes;

    /**
     * Permission-filtered action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Optional integrity panel context.
     *
     * @var array<string, mixed>|stdClass|null
     */
    private array|stdClass|null $integritypanel;

    /**
     * Optional archive panel context.
     *
     * @var array<string, mixed>|stdClass|null
     */
    private array|stdClass|null $archivepanel;

    /**
     * Constructor.
     *
     * @param int $assemblyid Assembly instance id.
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param int $contextid Context id.
     * @param array<string, mixed>|stdClass $assembly Prepared assembly data.
     * @param array<int, array<string, mixed>|stdClass> $motions Prepared motions.
     * @param array<int, array<string, mixed>|stdClass> $decisions Prepared decisions.
     * @param array<int, array<string, mixed>|stdClass> $contests Prepared contests.
     * @param array<int, array<string, mixed>|stdClass> $minutes Prepared minutes.
     * @param array<int, array<string, mixed>|stdClass> $actions Prepared permitted actions.
     * @param array<string, mixed>|stdClass|null $integritypanel Optional integrity panel data.
     * @param array<string, mixed>|stdClass|null $archivepanel Optional archive panel data.
     */
    public function __construct(
        int $assemblyid,
        int $cmid,
        int $courseid,
        int $contextid,
        array|stdClass $assembly,
        array $motions = [],
        array $decisions = [],
        array $contests = [],
        array $minutes = [],
        array $actions = [],
        array|stdClass|null $integritypanel = null,
        array|stdClass|null $archivepanel = null
    ) {
        $this->assemblyid = max(0, $assemblyid);
        $this->cmid = max(0, $cmid);
        $this->courseid = max(0, $courseid);
        $this->contextid = max(0, $contextid);
        $this->assembly = (array)$assembly;
        $this->motions = array_map([$this, 'normalise_motion'], $motions);
        $this->decisions = array_map([$this, 'normalise_decision'], $decisions);
        $this->contests = array_map([$this, 'normalise_contest'], $contests);
        $this->minutes = array_map([$this, 'normalise_minutes'], $minutes);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
        $this->integritypanel = $integritypanel;
        $this->archivepanel = $archivepanel;
    }

    /**
     * Export template data.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $status = $this->normalise_status((string)($this->assembly['status'] ?? self::STATUS_DRAFT));
        $type = $this->normalise_type((string)($this->assembly['assemblytype'] ?? $this->assembly['type'] ?? self::TYPE_SAVOIRS));
        $visibility = clean_param((string)($this->assembly['visibility'] ?? 'course'), PARAM_ALPHANUMEXT);

        $viewurl = new moodle_url('/mod/uckkassembly/view.php', ['id' => $this->cmid]);

        $data = new stdClass();
        $data->uniqid = 'uckkassembly-view-' . $this->cmid;
        $data->id = $this->assemblyid;
        $data->cmid = $this->cmid;
        $data->courseid = $this->courseid;
        $data->contextid = $this->contextid;
        $data->viewurl = $viewurl->out(false);

        $data->title = format_string((string)($this->assembly['name'] ?? ''));
        $data->intro = (string)($this->assembly['intro'] ?? '');
        $data->hasintro = trim($data->intro) !== '';

        $data->assemblytype = $type;
        $data->assemblytypelabel = $this->get_type_label($type);
        $data->status = $status;
        $data->statuslabel = $this->get_status_label($status);
        $data->statusclass = $this->get_status_class($status);
        $data->visibility = $visibility;
        $data->visibilitylabel = $this->get_visibility_label($visibility);

        $data->purpose = (string)($this->assembly['purpose'] ?? '');
        $data->haspurpose = trim($data->purpose) !== '';

        $data->timeline = $this->build_timeline();
        $data->hastimeline = $data->timeline->hasopen
            || $data->timeline->hasclose
            || $data->timeline->hasmotionclose
            || $data->timeline->hasvoteopen
            || $data->timeline->hasvoteclose;

        $data->governance = $this->build_governance_summary();
        $data->hasgovernance = true;

        $data->motions = $this->build_motion_rows();
        $data->hasmotions = !empty($data->motions);
        $data->motioncount = count($data->motions);
        $data->emptymotionslabel = get_string('motions:none', 'uckkassembly');

        $data->decisions = $this->build_decision_rows();
        $data->hasdecisions = !empty($data->decisions);
        $data->decisioncount = count($data->decisions);
        $data->emptydecisionslabel = get_string('decisions:none', 'uckkassembly');

        $data->contests = $this->build_contest_rows();
        $data->hascontests = !empty($data->contests);
        $data->contestcount = count($data->contests);
        $data->emptycontestslabel = get_string('contests:none', 'uckkassembly');

        $data->minutes = $this->build_minutes_rows();
        $data->hasminutes = !empty($data->minutes);
        $data->emptyminuteslabel = get_string('minutes:none', 'uckkassembly');

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->integritypanel = $this->normalise_optional_panel($this->integritypanel);
        $data->hasintegritypanel = !empty((array)$data->integritypanel);

        $data->archivepanel = $this->normalise_optional_panel($this->archivepanel);
        $data->hasarchivepanel = !empty((array)$data->archivepanel);

        $data->notices = $this->normalise_messages($this->assembly['notices'] ?? []);
        $data->hasnotices = !empty($data->notices);

        $data->warnings = $this->normalise_messages($this->assembly['warnings'] ?? [], 'warning');
        $data->haswarnings = !empty($data->warnings);

        $data->summarycards = $this->build_summary_cards($data);
        $data->authoritynotice = get_string('assemblynonsovereignnotice', 'uckkassembly');

        return $data;
    }

    /**
     * Build timeline export data.
     *
     * @return stdClass
     */
    private function build_timeline(): stdClass {
        $now = time();

        $timeopen = (int)($this->assembly['timeopen'] ?? 0);
        $timeclose = (int)($this->assembly['timeclose'] ?? 0);
        $timemotionclose = (int)($this->assembly['timemotionclose'] ?? 0);
        $timevoteopen = (int)($this->assembly['timevoteopen'] ?? 0);
        $timevoteclose = (int)($this->assembly['timevoteclose'] ?? 0);

        return (object)[
            'timeopen' => $timeopen,
            'hasopen' => $timeopen > 0,
            'openlabel' => $timeopen > 0 ? userdate($timeopen) : '',

            'timeclose' => $timeclose,
            'hasclose' => $timeclose > 0,
            'closelabel' => $timeclose > 0 ? userdate($timeclose) : '',

            'timemotionclose' => $timemotionclose,
            'hasmotionclose' => $timemotionclose > 0,
            'motioncloselabel' => $timemotionclose > 0 ? userdate($timemotionclose) : '',

            'timevoteopen' => $timevoteopen,
            'hasvoteopen' => $timevoteopen > 0,
            'voteopenlabel' => $timevoteopen > 0 ? userdate($timevoteopen) : '',

            'timevoteclose' => $timevoteclose,
            'hasvoteclose' => $timevoteclose > 0,
            'votecloselabel' => $timevoteclose > 0 ? userdate($timevoteclose) : '',

            'isnotopenyet' => $timeopen > 0 && $now < $timeopen,
            'isclosed' => $timeclose > 0 && $now > $timeclose,
            'ismotionclosed' => $timemotionclose > 0 && $now > $timemotionclose,
            'isvoteopen' => ($timevoteopen === 0 || $now >= $timevoteopen)
                && ($timevoteclose === 0 || $now <= $timevoteclose),
            'isvoteclosed' => $timevoteclose > 0 && $now > $timevoteclose,
        ];
    }

    /**
     * Build governance summary.
     *
     * @return stdClass
     */
    private function build_governance_summary(): stdClass {
        $quorum = max(0, (int)($this->assembly['quorum'] ?? 0));
        $threshold = max(0, min(100, (float)($this->assembly['decisionthreshold'] ?? 50)));
        $method = clean_param((string)($this->assembly['votingmethod'] ?? 'simple_majority'), PARAM_ALPHANUMEXT);
        $archivepolicy = clean_param((string)($this->assembly['archivepolicy'] ?? 'summary'), PARAM_ALPHANUMEXT);

        return (object)[
            'quorum' => $quorum,
            'hasquorum' => $quorum > 0,
            'votingmethod' => $method,
            'votingmethodlabel' => $this->get_lang_or_fallback('votingmethod:' . str_replace('_', '', $method), $method),
            'decisionthreshold' => format_float($threshold, 2),
            'hasdecisionthreshold' => true,
            'allowmotions' => !empty($this->assembly['allowmotions']),
            'allowamendments' => !empty($this->assembly['allowamendments']),
            'allowcontests' => !empty($this->assembly['allowcontests']),
            'integrityrequired' => !empty($this->assembly['integrityrequired']),
            'archivepolicy' => $archivepolicy,
            'archivepolicylabel' => $this->get_lang_or_fallback('archivepolicy:' . str_replace('_', '', $archivepolicy), $archivepolicy),
        ];
    }

    /**
     * Normalise one motion row.
     *
     * @param array<string, mixed>|stdClass $motion Raw motion.
     * @return array<string, mixed>
     */
    private function normalise_motion(array|stdClass $motion): array {
        $row = (array)$motion;
        $status = clean_param((string)($row['status'] ?? 'draft'), PARAM_ALPHANUMEXT);
        $url = $this->normalise_url($row['url'] ?? null);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => (string)($row['summary'] ?? ''),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'status' => $status,
            'statuslabel' => $this->get_lang_or_fallback('motionstatus:' . str_replace('_', '', $status), $status),
            'statusclass' => 'motion-status-' . str_replace('_', '-', $status),
            'decisiontype' => clean_param((string)($row['decisiontype'] ?? ''), PARAM_ALPHANUMEXT),
            'decisiontypelabel' => $this->get_decision_type_label((string)($row['decisiontype'] ?? '')),
            'proposedby' => format_string((string)($row['proposedby'] ?? '')),
            'hasproposedby' => trim((string)($row['proposedby'] ?? '')) !== '',
            'timecreated' => (int)($row['timecreated'] ?? 0),
            'timelabel' => !empty($row['timecreated']) ? userdate((int)$row['timecreated']) : '',
            'url' => $url,
            'hasurl' => $url !== '',
            'voteurl' => $this->normalise_url($row['voteurl'] ?? null),
            'hasvoteurl' => !empty($row['voteurl']),
            'canvote' => !empty($row['canvote']),
            'canamend' => !empty($row['canamend']),
            'canpublishdecision' => !empty($row['canpublishdecision']),
            'votesummary' => $this->normalise_vote_summary($row['votesummary'] ?? []),
        ];
    }

    /**
     * Normalise one decision row.
     *
     * @param array<string, mixed>|stdClass $decision Raw decision.
     * @return array<string, mixed>
     */
    private function normalise_decision(array|stdClass $decision): array {
        $row = (array)$decision;
        $status = clean_param((string)($row['status'] ?? self::DECISION_PROPOSED), PARAM_ALPHANUMEXT);
        $url = $this->normalise_url($row['url'] ?? null);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'decisiontype' => clean_param((string)($row['decisiontype'] ?? ''), PARAM_ALPHANUMEXT),
            'decisiontypelabel' => $this->get_decision_type_label((string)($row['decisiontype'] ?? '')),
            'summary' => (string)($row['summary'] ?? ''),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'status' => $status,
            'statuslabel' => $this->get_lang_or_fallback('decisionstatus:' . str_replace('_', '', $status), $status),
            'statusclass' => 'decision-status-' . str_replace('_', '-', $status),
            'timecreated' => (int)($row['timecreated'] ?? 0),
            'timelabel' => !empty($row['timecreated']) ? userdate((int)$row['timecreated']) : '',
            'url' => $url,
            'hasurl' => $url !== '',
            'contesturl' => $this->normalise_url($row['contesturl'] ?? null),
            'hascontesturl' => !empty($row['contesturl']),
            'archiveurl' => $this->normalise_url($row['archiveurl'] ?? null),
            'hasarchiveurl' => !empty($row['archiveurl']),
        ];
    }

    /**
     * Normalise one contest row.
     *
     * @param array<string, mixed>|stdClass $contest Raw contest.
     * @return array<string, mixed>
     */
    private function normalise_contest(array|stdClass $contest): array {
        $row = (array)$contest;
        $status = clean_param((string)($row['status'] ?? 'pending_review'), PARAM_ALPHANUMEXT);
        $url = $this->normalise_url($row['url'] ?? null);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['summary'] ?? '')),
            'summary' => (string)($row['summary'] ?? ''),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'status' => $status,
            'statuslabel' => $this->get_lang_or_fallback('conteststatus:' . str_replace('_', '', $status), $status),
            'statusclass' => 'contest-status-' . str_replace('_', '-', $status),
            'openedby' => format_string((string)($row['openedby'] ?? '')),
            'hasopenedby' => trim((string)($row['openedby'] ?? '')) !== '',
            'timecreated' => (int)($row['timecreated'] ?? 0),
            'timelabel' => !empty($row['timecreated']) ? userdate((int)$row['timecreated']) : '',
            'url' => $url,
            'hasurl' => $url !== '',
            'restricted' => !empty($row['restricted']),
        ];
    }

    /**
     * Normalise one minutes row.
     *
     * @param array<string, mixed>|stdClass $minutes Raw minutes.
     * @return array<string, mixed>
     */
    private function normalise_minutes(array|stdClass $minutes): array {
        $row = (array)$minutes;
        $status = clean_param((string)($row['status'] ?? 'draft'), PARAM_ALPHANUMEXT);
        $url = $this->normalise_url($row['url'] ?? null);

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? get_string('minutes', 'uckkassembly'))),
            'summary' => (string)($row['summary'] ?? ''),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'status' => $status,
            'statuslabel' => $this->get_lang_or_fallback('minutesstatus:' . str_replace('_', '', $status), $status),
            'timecreated' => (int)($row['timecreated'] ?? 0),
            'timelabel' => !empty($row['timecreated']) ? userdate((int)$row['timecreated']) : '',
            'url' => $url,
            'hasurl' => $url !== '',
        ];
    }

    /**
     * Normalise one permitted action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action.
     * @return array<string, mixed>
     */
    private function normalise_action(array|stdClass $action): array {
        $row = (array)$action;
        $key = clean_param((string)($row['key'] ?? $row['action'] ?? ''), PARAM_ALPHANUMEXT);
        $url = $this->normalise_url($row['url'] ?? null);

        return [
            'key' => $key,
            'label' => format_string((string)($row['label'] ?? $this->get_lang_or_fallback('action:' . str_replace('_', '', $key), $key))),
            'url' => $url,
            'hasurl' => $url !== '',
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'secondary' => empty($row['primary']) && empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'hasdisabledreason' => trim((string)($row['disabledreason'] ?? '')) !== '',
        ];
    }

    /**
     * Build exported motion rows.
     *
     * @return array<int, stdClass>
     */
    private function build_motion_rows(): array {
        return array_map(static fn(array $row): stdClass => (object)$row, $this->motions);
    }

    /**
     * Build exported decision rows.
     *
     * @return array<int, stdClass>
     */
    private function build_decision_rows(): array {
        return array_map(static fn(array $row): stdClass => (object)$row, $this->decisions);
    }

    /**
     * Build exported contest rows.
     *
     * @return array<int, stdClass>
     */
    private function build_contest_rows(): array {
        return array_map(static fn(array $row): stdClass => (object)$row, $this->contests);
    }

    /**
     * Build exported minutes rows.
     *
     * @return array<int, stdClass>
     */
    private function build_minutes_rows(): array {
        return array_map(static fn(array $row): stdClass => (object)$row, $this->minutes);
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

            $rows[] = (object)$action;
        }

        return $rows;
    }

    /**
     * Build top summary cards.
     *
     * @param stdClass $data Exported template data.
     * @return array<int, stdClass>
     */
    private function build_summary_cards(stdClass $data): array {
        return [
            (object)[
                'key' => 'motions',
                'label' => get_string('motions', 'uckkassembly'),
                'value' => (string)$data->motioncount,
                'class' => $data->hasmotions ? 'has-items' : 'is-empty',
            ],
            (object)[
                'key' => 'decisions',
                'label' => get_string('decisions', 'uckkassembly'),
                'value' => (string)$data->decisioncount,
                'class' => $data->hasdecisions ? 'has-items' : 'is-empty',
            ],
            (object)[
                'key' => 'contests',
                'label' => get_string('contests', 'uckkassembly'),
                'value' => (string)$data->contestcount,
                'class' => $data->hascontests ? 'has-warning' : 'is-clear',
            ],
            (object)[
                'key' => 'status',
                'label' => get_string('status'),
                'value' => $data->statuslabel,
                'class' => $data->statusclass,
            ],
        ];
    }

    /**
     * Normalise vote summary data.
     *
     * @param mixed $votesummary Raw vote summary.
     * @return stdClass
     */
    private function normalise_vote_summary(mixed $votesummary): stdClass {
        $row = is_array($votesummary) || $votesummary instanceof stdClass ? (array)$votesummary : [];

        return (object)[
            'support' => (int)($row['support'] ?? 0),
            'oppose' => (int)($row['oppose'] ?? 0),
            'abstain' => (int)($row['abstain'] ?? 0),
            'requestamendment' => (int)($row['requestamendment'] ?? $row['request_amendment'] ?? 0),
            'block' => (int)($row['block'] ?? 0),
            'total' => (int)($row['total'] ?? 0),
            'hasvotes' => !empty($row['total']),
            'resultlabel' => format_string((string)($row['resultlabel'] ?? '')),
            'hasresultlabel' => trim((string)($row['resultlabel'] ?? '')) !== '',
        ];
    }

    /**
     * Normalise optional nested panel data.
     *
     * @param array<string, mixed>|stdClass|null $panel Panel data.
     * @return stdClass
     */
    private function normalise_optional_panel(array|stdClass|null $panel): stdClass {
        if ($panel === null) {
            return new stdClass();
        }

        return (object)$panel;
    }

    /**
     * Normalise notices/warnings.
     *
     * @param mixed $messages Raw messages.
     * @param string $defaulttype Default alert type.
     * @return array<int, stdClass>
     */
    private function normalise_messages(mixed $messages, string $defaulttype = 'info'): array {
        if (!is_array($messages)) {
            return [];
        }

        $normalised = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $normalised[] = (object)[
                    'type' => $defaulttype,
                    'message' => format_string($message),
                ];
                continue;
            }

            $row = (array)$message;
            $text = trim((string)($row['message'] ?? ''));

            if ($text === '') {
                continue;
            }

            $normalised[] = (object)[
                'type' => clean_param((string)($row['type'] ?? $defaulttype), PARAM_ALPHANUMEXT),
                'message' => format_string($text),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise assembly status.
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
            self::STATUS_CONTESTED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
            self::STATUS_CANCELLED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise assembly type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        $allowed = [
            self::TYPE_SAVOIRS,
            self::TYPE_DEFIS,
            self::TYPE_JOUEURS,
            self::TYPE_BATISSEURS,
            self::TYPE_INQUISITEURS,
            self::TYPE_GRAND_JEU,
        ];

        return in_array($type, $allowed, true) ? $type : self::TYPE_SAVOIRS;
    }

    /**
     * Return assembly type label.
     *
     * @param string $type Type.
     * @return string
     */
    private function get_type_label(string $type): string {
        return $this->get_lang_or_fallback('assemblytype:' . str_replace('_', '', $type), $type);
    }

    /**
     * Return status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return $this->get_lang_or_fallback('status:' . str_replace('_', '', $status), $status);
    }

    /**
     * Return CSS class for status.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_class(string $status): string {
        return 'status-' . str_replace('_', '-', $status);
    }

    /**
     * Return visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private function get_visibility_label(string $visibility): string {
        return $this->get_lang_or_fallback('visibility:' . str_replace('_', '', $visibility), $visibility);
    }

    /**
     * Return decision type label.
     *
     * @param string $type Decision type.
     * @return string
     */
    private function get_decision_type_label(string $type): string {
        if ($type === '') {
            return '';
        }

        return $this->get_lang_or_fallback('decisiontype:' . str_replace('_', '', $type), $type);
    }

    /**
     * Return component language string if present, else a readable fallback.
     *
     * @param string $key Language string key.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function get_lang_or_fallback(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $fallback));
    }

    /**
     * Normalise a URL value.
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
