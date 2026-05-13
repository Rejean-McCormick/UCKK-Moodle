<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Vote panel output object for UCKK assemblies.
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
 * Renderable vote panel for an assembly motion.
 *
 * This class prepares already-filtered display data only. It does not record
 * votes, count votes authoritatively, publish decisions, close motions,
 * resolve contestations, or archive records.
 */
final class vote_panel implements renderable, templatable {
    /** Vote choice: for. */
    public const VOTE_FOR = 'for';

    /** Vote choice: against. */
    public const VOTE_AGAINST = 'against';

    /** Vote choice: abstain. */
    public const VOTE_ABSTAIN = 'abstain';

    /** Vote choice: concern. */
    public const VOTE_CONCERN = 'concern';

    /** Vote choice: block. */
    public const VOTE_BLOCK = 'block';

    /** Vote state: not open. */
    public const STATE_NOT_OPEN = 'not_open';

    /** Vote state: open. */
    public const STATE_OPEN = 'open';

    /** Vote state: closed. */
    public const STATE_CLOSED = 'closed';

    /** Vote state: counted. */
    public const STATE_COUNTED = 'counted';

    /** Vote state: contested. */
    public const STATE_CONTESTED = 'contested';

    /** Vote state: archived. */
    public const STATE_ARCHIVED = 'archived';

    /**
     * Assembly instance id.
     *
     * @var int
     */
    private int $assemblyid;

    /**
     * Motion id.
     *
     * @var int
     */
    private int $motionid;

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
     * Motion title.
     *
     * @var string
     */
    private string $motiontitle;

    /**
     * Prepared vote summary.
     *
     * @var array<string, mixed>
     */
    private array $summary;

    /**
     * Prepared vote options.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $options;

    /**
     * Prepared aggregate result rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $results;

    /**
     * Prepared viewer vote data.
     *
     * @var array<string, mixed>
     */
    private array $uservote;

    /**
     * Prepared permitted actions.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Constructor.
     *
     * @param int $assemblyid Assembly instance id.
     * @param int $motionid Motion id.
     * @param int $cmid Course module id.
     * @param int $contextid Moodle context id.
     * @param string $motiontitle Motion title.
     * @param array<string, mixed> $summary Already-filtered vote summary.
     * @param array<int, array<string, mixed>|stdClass> $options Vote options.
     * @param array<int, array<string, mixed>|stdClass> $results Aggregate result rows.
     * @param array<string, mixed>|stdClass|null $uservote Viewer vote row.
     * @param array<int, array<string, mixed>|stdClass> $actions Permitted action rows.
     */
    public function __construct(
        int $assemblyid,
        int $motionid,
        int $cmid,
        int $contextid,
        string $motiontitle,
        array $summary = [],
        array $options = [],
        array $results = [],
        array|stdClass|null $uservote = null,
        array $actions = []
    ) {
        $this->assemblyid = max(0, $assemblyid);
        $this->motionid = max(0, $motionid);
        $this->cmid = max(0, $cmid);
        $this->contextid = max(0, $contextid);
        $this->motiontitle = format_string($motiontitle);
        $this->summary = $summary;
        $this->options = empty($options)
            ? $this->get_default_vote_options()
            : array_map([$this, 'normalise_option'], $options);
        $this->results = array_map([$this, 'normalise_result'], $results);
        $this->uservote = $uservote === null ? [] : $this->normalise_user_vote($uservote);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
    }

    /**
     * Export template context.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $state = $this->normalise_vote_state((string)($this->summary['state'] ?? self::STATE_NOT_OPEN));

        $data = new stdClass();
        $data->assemblyid = $this->assemblyid;
        $data->motionid = $this->motionid;
        $data->cmid = $this->cmid;
        $data->contextid = $this->contextid;
        $data->motiontitle = $this->motiontitle;

        $data->heading = get_string('votepanel', 'uckkassembly');
        $data->state = $state;
        $data->statelabel = $this->get_vote_state_label($state);
        $data->stateclass = 'vote-state-' . str_replace('_', '-', $state);

        $data->motionurl = (new moodle_url('/mod/uckkassembly/motion.php', [
            'id' => $this->cmid,
            'motionid' => $this->motionid,
        ]))->out(false);

        $data->voteurl = (new moodle_url('/mod/uckkassembly/vote.php', [
            'id' => $this->cmid,
            'motionid' => $this->motionid,
        ]))->out(false);

        $data->isopen = $state === self::STATE_OPEN;
        $data->isclosed = in_array($state, [
            self::STATE_CLOSED,
            self::STATE_COUNTED,
            self::STATE_CONTESTED,
            self::STATE_ARCHIVED,
        ], true);
        $data->iscontested = $state === self::STATE_CONTESTED;

        $data->quorumrequired = (int)($this->summary['quorumrequired'] ?? 0);
        $data->quorumpresent = (int)($this->summary['quorumpresent'] ?? 0);
        $data->hasquorumtarget = $data->quorumrequired > 0;
        $data->quorummet = $data->hasquorumtarget && $data->quorumpresent >= $data->quorumrequired;

        $data->thresholdlabel = format_string((string)($this->summary['thresholdlabel'] ?? ''));
        $data->hasthresholdlabel = $data->thresholdlabel !== '';

        $data->eligiblevoters = (int)($this->summary['eligiblevoters'] ?? 0);
        $data->votecount = $this->get_total_vote_count();
        $data->turnoutpercent = $this->calculate_turnout_percent($data->eligiblevoters, $data->votecount);
        $data->hasturnout = $data->eligiblevoters > 0;

        $data->options = $this->build_option_rows();
        $data->hasoptions = !empty($data->options);

        $data->results = $this->build_result_rows($data->votecount);
        $data->hasresults = !empty($data->results);

        $data->uservote = $this->build_user_vote();
        $data->hasuservote = !empty($data->uservote->choice ?? '');
        $data->uservotelabel = $data->hasuservote
            ? $data->uservote->choicelabel
            : get_string('votenotcast', 'uckkassembly');

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->warnings = $this->normalise_warnings($this->summary['warnings'] ?? []);
        $data->haswarnings = !empty($data->warnings);

        $data->notice = get_string('votegovernancenotice', 'uckkassembly');

        return $data;
    }

    /**
     * Default vote options.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_default_vote_options(): array {
        return [
            $this->normalise_option(['choice' => self::VOTE_FOR]),
            $this->normalise_option(['choice' => self::VOTE_AGAINST]),
            $this->normalise_option(['choice' => self::VOTE_ABSTAIN]),
            $this->normalise_option(['choice' => self::VOTE_CONCERN]),
            $this->normalise_option(['choice' => self::VOTE_BLOCK]),
        ];
    }

    /**
     * Normalise vote option.
     *
     * @param array<string, mixed>|stdClass $option Raw option.
     * @return array<string, mixed>
     */
    private function normalise_option(array|stdClass $option): array {
        $row = (array)$option;
        $choice = $this->normalise_vote_choice((string)($row['choice'] ?? $row['key'] ?? self::VOTE_ABSTAIN));

        return [
            'choice' => $choice,
            'label' => format_string((string)($row['label'] ?? $this->get_vote_choice_label($choice))),
            'description' => format_string((string)($row['description'] ?? '')),
            'hasdescription' => trim((string)($row['description'] ?? '')) !== '',
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'hasdisabledreason' => trim((string)($row['disabledreason'] ?? '')) !== '',
            'danger' => !empty($row['danger']) || $choice === self::VOTE_BLOCK,
            'primary' => !empty($row['primary']) || $choice === self::VOTE_FOR,
            'sortorder' => (int)($row['sortorder'] ?? $this->get_choice_sortorder($choice)),
        ];
    }

    /**
     * Normalise result row.
     *
     * @param array<string, mixed>|stdClass $result Raw result.
     * @return array<string, mixed>
     */
    private function normalise_result(array|stdClass $result): array {
        $row = (array)$result;
        $choice = $this->normalise_vote_choice((string)($row['choice'] ?? $row['key'] ?? self::VOTE_ABSTAIN));

        return [
            'choice' => $choice,
            'label' => format_string((string)($row['label'] ?? $this->get_vote_choice_label($choice))),
            'count' => max(0, (int)($row['count'] ?? 0)),
            'weight' => max(0.0, (float)($row['weight'] ?? 0)),
            'class' => 'vote-choice-' . str_replace('_', '-', $choice),
        ];
    }

    /**
     * Normalise current user vote.
     *
     * @param array<string, mixed>|stdClass $uservote Raw user vote.
     * @return array<string, mixed>
     */
    private function normalise_user_vote(array|stdClass $uservote): array {
        $row = (array)$uservote;
        $choice = trim((string)($row['choice'] ?? ''));

        if ($choice !== '') {
            $choice = $this->normalise_vote_choice($choice);
        }

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'choice' => $choice,
            'choicelabel' => $choice !== '' ? $this->get_vote_choice_label($choice) : '',
            'rationale' => format_string((string)($row['rationale'] ?? '')),
            'hasrationale' => trim((string)($row['rationale'] ?? '')) !== '',
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
            'canchange' => !empty($row['canchange']),
        ];
    }

    /**
     * Normalise permitted action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action.
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
            'url' => $this->normalise_url($row['url'] ?? null),
            'method' => $method,
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'hasdisabledreason' => trim((string)($row['disabledreason'] ?? '')) !== '',
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
            'hasconfirmmessage' => trim((string)($row['confirmmessage'] ?? '')) !== '',
        ];
    }

    /**
     * Build option rows for Mustache.
     *
     * @return array<int, stdClass>
     */
    private function build_option_rows(): array {
        $options = $this->options;

        usort($options, static function (array $a, array $b): int {
            return $a['sortorder'] <=> $b['sortorder'];
        });

        $rows = [];

        foreach ($options as $option) {
            $selected = !empty($this->uservote['choice']) && $this->uservote['choice'] === $option['choice'];

            $rows[] = (object)[
                'choice' => $option['choice'],
                'label' => $option['label'],
                'description' => $option['description'],
                'hasdescription' => $option['hasdescription'],
                'disabled' => $option['disabled'],
                'disabledreason' => $option['disabledreason'],
                'hasdisabledreason' => $option['hasdisabledreason'],
                'danger' => $option['danger'],
                'primary' => $option['primary'],
                'secondary' => !$option['primary'] && !$option['danger'],
                'selected' => $selected,
                'class' => 'vote-choice-' . str_replace('_', '-', $option['choice']),
            ];
        }

        return $rows;
    }

    /**
     * Build result rows for Mustache.
     *
     * @param int $total Total votes.
     * @return array<int, stdClass>
     */
    private function build_result_rows(int $total): array {
        $rows = [];

        foreach ($this->results as $result) {
            $percent = $total > 0 ? round(($result['count'] / $total) * 100, 1) : 0.0;

            $rows[] = (object)[
                'choice' => $result['choice'],
                'label' => $result['label'],
                'count' => $result['count'],
                'weight' => $result['weight'],
                'percent' => $percent,
                'percentlabel' => format_float($percent, 1) . ' %',
                'class' => $result['class'],
                'hasvotes' => $result['count'] > 0,
            ];
        }

        return $rows;
    }

    /**
     * Build current user vote row.
     *
     * @return stdClass
     */
    private function build_user_vote(): stdClass {
        $data = new stdClass();

        if (empty($this->uservote)) {
            $data->id = 0;
            $data->choice = '';
            $data->choicelabel = '';
            $data->rationale = '';
            $data->hasrationale = false;
            $data->timecreated = 0;
            $data->timecreatedlabel = '';
            $data->timemodified = 0;
            $data->timemodifiedlabel = '';
            $data->canchange = false;
            return $data;
        }

        $data->id = $this->uservote['id'];
        $data->choice = $this->uservote['choice'];
        $data->choicelabel = $this->uservote['choicelabel'];
        $data->rationale = $this->uservote['rationale'];
        $data->hasrationale = $this->uservote['hasrationale'];
        $data->timecreated = $this->uservote['timecreated'];
        $data->timecreatedlabel = $this->uservote['timecreated'] > 0 ? userdate($this->uservote['timecreated']) : '';
        $data->timemodified = $this->uservote['timemodified'];
        $data->timemodifiedlabel = $this->uservote['timemodified'] > 0 ? userdate($this->uservote['timemodified']) : '';
        $data->canchange = $this->uservote['canchange'];

        return $data;
    }

    /**
     * Build permitted action rows.
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
                'hasdisabledreason' => $action['hasdisabledreason'],
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hasconfirmmessage' => $action['hasconfirmmessage'],
            ];
        }

        return $rows;
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

        $rows = [];

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

            $rows[] = (object)[
                'message' => format_string($message),
                'severity' => $severity,
                'class' => 'alert-' . $this->get_warning_class($severity),
            ];
        }

        return $rows;
    }

    /**
     * Get total vote count from result rows.
     *
     * @return int
     */
    private function get_total_vote_count(): int {
        $total = 0;

        foreach ($this->results as $result) {
            $total += (int)$result['count'];
        }

        return $total;
    }

    /**
     * Calculate turnout percent.
     *
     * @param int $eligible Eligible voters.
     * @param int $votes Vote count.
     * @return float
     */
    private function calculate_turnout_percent(int $eligible, int $votes): float {
        if ($eligible <= 0) {
            return 0.0;
        }

        return round(($votes / $eligible) * 100, 1);
    }

    /**
     * Normalise vote choice.
     *
     * @param string $choice Raw choice.
     * @return string
     */
    private function normalise_vote_choice(string $choice): string {
        $choice = clean_param($choice, PARAM_ALPHANUMEXT);

        $allowed = [
            self::VOTE_FOR,
            self::VOTE_AGAINST,
            self::VOTE_ABSTAIN,
            self::VOTE_CONCERN,
            self::VOTE_BLOCK,
        ];

        return in_array($choice, $allowed, true) ? $choice : self::VOTE_ABSTAIN;
    }

    /**
     * Normalise vote state.
     *
     * @param string $state Raw state.
     * @return string
     */
    private function normalise_vote_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATE_NOT_OPEN,
            self::STATE_OPEN,
            self::STATE_CLOSED,
            self::STATE_COUNTED,
            self::STATE_CONTESTED,
            self::STATE_ARCHIVED,
        ];

        return in_array($state, $allowed, true) ? $state : self::STATE_NOT_OPEN;
    }

    /**
     * Get vote choice label.
     *
     * @param string $choice Canonical choice.
     * @return string
     */
    private function get_vote_choice_label(string $choice): string {
        $key = 'votechoice:' . str_replace('_', '', $choice);

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $choice));
    }

    /**
     * Get vote state label.
     *
     * @param string $state Canonical state.
     * @return string
     */
    private function get_vote_state_label(string $state): string {
        $key = 'votestate:' . str_replace('_', '', $state);

        if (get_string_manager()->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Get action label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_label(string $key): string {
        $stringkey = 'voteaction:' . str_replace('_', '', $key);

        if ($key !== '' && get_string_manager()->string_exists($stringkey, 'uckkassembly')) {
            return get_string($stringkey, 'uckkassembly');
        }

        return get_string('view');
    }

    /**
     * Get default choice sort order.
     *
     * @param string $choice Vote choice.
     * @return int
     */
    private function get_choice_sortorder(string $choice): int {
        return match ($choice) {
            self::VOTE_FOR => 10,
            self::VOTE_AGAINST => 20,
            self::VOTE_ABSTAIN => 30,
            self::VOTE_CONCERN => 40,
            self::VOTE_BLOCK => 50,
            default => 100,
        };
    }

    /**
     * Map warning severity to Bootstrap alert class suffix.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_warning_class(string $severity): string {
        return match ($severity) {
            'danger', 'error', 'blocked', 'contested' => 'danger',
            'info', 'notice' => 'info',
            'success', 'clear' => 'success',
            default => 'warning',
        };
    }

    /**
     * Normalise URL to string.
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
```

Add these strings to `mod/uckkassembly/lang/en/uckkassembly.php`:

```php
$string['votepanel'] = 'Vote panel';
$string['votenotcast'] = 'No vote cast';
$string['votegovernancenotice'] = 'Vote information is procedural assembly data. Final decisions, publication, contestation, minutes, and archive status require authorised human review and traceable records.';

$string['votechoice:for'] = 'For';
$string['votechoice:against'] = 'Against';
$string['votechoice:abstain'] = 'Abstain';
$string['votechoice:concern'] = 'Concern';
$string['votechoice:block'] = 'Block';

$string['votestate:notopen'] = 'Not open';
$string['votestate:open'] = 'Open';
$string['votestate:closed'] = 'Closed';
$string['votestate:counted'] = 'Counted';
$string['votestate:contested'] = 'Contested';
$string['votestate:archived'] = 'Archived';

$string['voteaction:castvote'] = 'Cast vote';
$string['voteaction:changevote'] = 'Change vote';
$string['voteaction:closevote'] = 'Close vote';
$string['voteaction:countvote'] = 'Count vote';
$string['voteaction:contestvote'] = 'Contest vote';
$string['voteaction:publishdecision'] = 'Publish decision';
```

Add these strings to `mod/uckkassembly/lang/fr/uckkassembly.php`:

```php
$string['votepanel'] = 'Panneau de vote';
$string['votenotcast'] = 'Aucun vote enregistré';
$string['votegovernancenotice'] = 'Les informations de vote sont des données procédurales d’assemblée. Les décisions finales, la publication, la contestation, le procès-verbal et l’archivage exigent une revue humaine autorisée et des traces vérifiables.';

$string['votechoice:for'] = 'Pour';
$string['votechoice:against'] = 'Contre';
$string['votechoice:abstain'] = 'Abstention';
$string['votechoice:concern'] = 'Réserve';
$string['votechoice:block'] = 'Blocage';

$string['votestate:notopen'] = 'Non ouvert';
$string['votestate:open'] = 'Ouvert';
$string['votestate:closed'] = 'Fermé';
$string['votestate:counted'] = 'Comptabilisé';
$string['votestate:contested'] = 'Contesté';
$string['votestate:archived'] = 'Archivé';

$string['voteaction:castvote'] = 'Voter';
$string['voteaction:changevote'] = 'Modifier le vote';
$string['voteaction:closevote'] = 'Fermer le vote';
$string['voteaction:countvote'] = 'Comptabiliser le vote';
$string['voteaction:contestvote'] = 'Contester le vote';
$string['voteaction:publishdecision'] = 'Publier la décision';
