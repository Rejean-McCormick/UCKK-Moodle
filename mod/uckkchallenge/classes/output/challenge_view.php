<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Renderable view model for a UCKK challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\output;

use context_module;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable view model for one UCKK challenge.
 *
 * This class prepares display data only. It does not mutate challenge state,
 * evaluate submissions, validate integrity, archive records, award badges, or
 * certify competencies.
 */
final class challenge_view implements renderable, templatable {
    /**
     * Challenge DB record.
     *
     * @var stdClass
     */
    private stdClass $challenge;

    /**
     * Course DB record.
     *
     * @var stdClass
     */
    private stdClass $course;

    /**
     * Course module record.
     *
     * @var stdClass
     */
    private stdClass $cm;

    /**
     * Module context.
     *
     * @var context_module
     */
    private context_module $context;

    /**
     * Prepared controller/service data.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Constructor.
     *
     * @param stdClass $challenge Challenge DB record.
     * @param stdClass $course Course DB record.
     * @param stdClass $cm Course module record.
     * @param context_module $context Module context.
     * @param array<string, mixed> $data Already permission-filtered display data.
     */
    public function __construct(
        stdClass $challenge,
        stdClass $course,
        stdClass $cm,
        context_module $context,
        array $data = []
    ) {
        $this->challenge = $challenge;
        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;
        $this->data = $data;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Template context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $status = $this->normalise_token((string)($this->challenge->status ?? 'active'), 'active');
        $visibility = $this->normalise_token((string)($this->challenge->visibility ?? 'course'), 'course');
        $challengetype = $this->normalise_token((string)($this->challenge->challengetype ?? $this->challenge->type ?? 'program'), 'program');

        $data = new stdClass();
        $data->uniqid = 'mod-uckkchallenge-view-' . (int)$this->cm->id;
        $data->component = 'mod_uckkchallenge';
        $data->id = (int)($this->challenge->id ?? 0);
        $data->cmid = (int)$this->cm->id;
        $data->courseid = (int)$this->course->id;
        $data->contextid = (int)$this->context->id;
        $data->userid = (int)($this->data['userid'] ?? 0);

        $data->title = format_string((string)($this->challenge->name ?? ''), true, ['context' => $this->context]);
        $data->intro = $this->format_intro();
        $data->hasintro = trim(strip_tags($data->intro)) !== '';

        $data->challengecode = (string)($this->challenge->challengecode ?? $this->challenge->code ?? '');
        $data->haschallengecode = trim($data->challengecode) !== '';
        $data->challengetype = $challengetype;
        $data->challengetypelabel = $this->get_component_string('challengetype:' . str_replace('_', '', $challengetype), ucfirst(str_replace('_', ' ', $challengetype)));

        $data->status = $status;
        $data->statuslabel = $this->get_component_string('status:' . str_replace('_', '', $status), ucfirst(str_replace('_', ' ', $status)));
        $data->statusclass = 'status-' . str_replace('_', '-', $status);
        $data->visibility = $visibility;
        $data->visibilitylabel = $this->get_component_string('visibility:' . str_replace('_', '', $visibility), ucfirst(str_replace('_', ' ', $visibility)));

        $data->statement = $this->format_text_field(['statement', 'prompt', 'description']);
        $data->hasstatement = trim(strip_tags($data->statement)) !== '';

        $data->contexttext = $this->format_text_field(['contexttext', 'challengecontext', 'background']);
        $data->hascontexttext = trim(strip_tags($data->contexttext)) !== '';

        $data->rules = $this->build_text_rows(['rules', 'instructions'], 'rule');
        $data->hasrules = !empty($data->rules);

        $data->corridors = $this->build_text_rows(['corridors'], 'corridor');
        $data->hascorridors = !empty($data->corridors);

        $data->ethicalconstraints = $this->format_text_field(['ethicalconstraints', 'ethics']);
        $data->hasethicalconstraints = trim(strip_tags($data->ethicalconstraints)) !== '';

        $data->evidencepolicy = $this->format_text_field(['evidencepolicy', 'evidencerequirements']);
        $data->hasevidencepolicy = trim(strip_tags($data->evidencepolicy)) !== '';

        $data->criteria = $this->format_text_field(['criteria', 'evaluationcriteria']);
        $data->hascriteria = trim(strip_tags($data->criteria)) !== '';

        $data->publicsummary = $this->format_text_field(['publicsummary', 'summary']);
        $data->haspublicsummary = trim(strip_tags($data->publicsummary)) !== '';

        $data->aipolicy = $this->format_text_field(['aipolicy']);
        $data->hasaipolicy = trim(strip_tags($data->aipolicy)) !== '';

        $data->timeline = $this->export_timeline();
        $data->hastimeline = $data->timeline->hasopen || $data->timeline->hasclose || $data->timeline->hasreviewby;

        $data->progress = $this->export_progress();
        $data->participant = (object)[
            'progresslabel' => $data->progress->progresslabel,
            'statuslabel' => $data->statuslabel,
            'rolelabel' => '',
        ];
        $data->hasparticipant = true;

        $data->actionsinfo = $this->export_actions_info();
        $data->actions = $this->export_action_rows($data->actionsinfo);
        $data->hasactions = !empty($data->actions);

        $data->notices = $this->export_message_list($this->data['notices'] ?? []);
        $data->hasnotices = !empty($data->notices);

        $data->warnings = $this->export_message_list($this->data['warnings'] ?? []);
        $data->haswarnings = !empty($data->warnings);

        $data->submissions = $this->export_records($this->data['submissions'] ?? [], 'submission');
        $data->hassubmissions = !empty($data->submissions);
        $data->submission = !empty($data->submissions) ? reset($data->submissions) : new stdClass();
        $data->hassubmission = !empty($data->submissions);

        $data->proofs = $this->export_records($this->data['proofs'] ?? [], 'proof');
        $data->hasproofs = !empty($data->proofs);

        $data->evaluations = $this->export_records($this->data['evaluations'] ?? [], 'evaluation');
        $data->hasevaluations = !empty($data->evaluations);
        $data->review = !empty($data->evaluations) ? reset($data->evaluations) : new stdClass();
        $data->hasreview = !empty($data->evaluations);

        $data->integritycases = $this->export_records($this->data['integritycases'] ?? [], 'integritycase');
        $data->hasintegritycases = !empty($data->integritycases);
        $data->integritypanel = (object)[
            'cases' => $data->integritycases,
            'hascases' => !empty($data->integritycases),
        ];
        $data->hasintegritypanel = false;

        $data->archiveitems = $this->export_records($this->data['archiveitems'] ?? [], 'archiveitem');
        $data->hasarchiveitems = !empty($data->archiveitems);
        $data->archive = (object)[
            'statuslabel' => '',
            'items' => $data->archiveitems,
            'hasitems' => !empty($data->archiveitems),
            'actionurl' => $data->actionsinfo->archiveurl,
            'actionlabel' => $data->actionsinfo->archivelabel,
        ];
        $data->hasarchive = !empty($data->archiveitems) || !empty($data->actionsinfo->canarchive);

        $data->competencies = $this->export_records($this->data['competencies'] ?? [], 'competency');
        $data->hascompetencies = !empty($data->competencies);

        $data->badges = $this->export_records($this->data['badges'] ?? [], 'badge');
        $data->hasbadges = !empty($data->badges);

        $data->showoverview = $this->get_bool_any(['showoverview'], true);
        $data->showevidence = $this->get_bool_any(['showevidence'], true);
        $data->showevaluation = $this->get_bool_any(['showevaluation'], true);
        $data->showintegrity = $this->get_bool_any(['showintegrity'], true);
        $data->showarchive = $this->get_bool_any(['showarchive'], true);
        $data->showrecognition = $this->get_bool_any(['showrecognition'], true);

        $data->emptylabel = $this->get_component_string('emptychallengeview', 'No challenge content has been published yet.');
        $data->nonsovereignailabel = $this->get_component_string('ainotauthority_desc', 'AI assistance is not an authority for this challenge.');

        return $data;
    }

    /**
     * Export timeline data with the names expected by challenge_view.mustache.
     *
     * @return stdClass
     */
    private function export_timeline(): stdClass {
        $timeopen = (int)($this->challenge->timeopen ?? $this->challenge->opensat ?? 0);
        $timeclose = (int)($this->challenge->timeclose ?? $this->challenge->closesat ?? $this->challenge->duedate ?? 0);
        $timereviewby = (int)($this->challenge->timereviewby ?? $this->challenge->reviewby ?? 0);
        $now = time();

        return (object)[
            'timeopen' => $timeopen,
            'hasopen' => $timeopen > 0,
            'hastimeopen' => $timeopen > 0,
            'openlabel' => $timeopen > 0 ? userdate($timeopen) : '',
            'timeopenlabel' => $timeopen > 0 ? userdate($timeopen) : '',

            'timeclose' => $timeclose,
            'hasclose' => $timeclose > 0,
            'hastimeclose' => $timeclose > 0,
            'closelabel' => $timeclose > 0 ? userdate($timeclose) : '',
            'timecloselabel' => $timeclose > 0 ? userdate($timeclose) : '',

            'timereviewby' => $timereviewby,
            'hasreviewby' => $timereviewby > 0,
            'hastimereviewby' => $timereviewby > 0,
            'reviewbylabel' => $timereviewby > 0 ? userdate($timereviewby) : '',
            'timereviewbylabel' => $timereviewby > 0 ? userdate($timereviewby) : '',

            'isnotopenyet' => $timeopen > 0 && $now < $timeopen,
            'isclosed' => $timeclose > 0 && $now > $timeclose,
            'isoverdue' => $timeclose > 0 && $now > $timeclose,
            'isduesoon' => $timeclose > $now && $timeclose <= $now + WEEKSECS,
        ];
    }

    /**
     * Export progress data.
     *
     * @return stdClass
     */
    private function export_progress(): stdClass {
        $progress = (array)($this->data['progress'] ?? []);
        $percent = $this->normalise_percent($progress['percent'] ?? $progress['progresspercent'] ?? 0);
        $completed = max(0, (int)($progress['completed'] ?? 0));
        $total = max(0, (int)($progress['total'] ?? 0));

        return (object)[
            'percent' => $percent,
            'progresspercent' => $percent,
            'progresslabel' => $total > 0
                ? $completed . ' / ' . $total . ' (' . $percent . '%)'
                : $percent . '%',
            'completed' => $completed,
            'total' => $total,
            'hascount' => $total > 0,
            'countlabel' => $total > 0 ? $completed . ' / ' . $total : '',
        ];
    }

    /**
     * Export action links as named flags.
     *
     * @return stdClass
     */
    private function export_actions_info(): stdClass {
        return (object)[
            'canedit' => $this->get_bool_any(['canedit', 'cancreatechallenge']),
            'editurl' => $this->get_url('editurl', new moodle_url('/course/modedit.php', ['update' => $this->cm->id])),
            'editlabel' => get_string('editsettings'),

            'cansubmit' => $this->get_bool_any(['cansubmit', 'cansubmitproof']),
            'submiturl' => $this->get_url('submiturl', new moodle_url('/mod/uckkchallenge/submit.php', ['id' => $this->cm->id])),
            'submitlabel' => $this->get_component_string('submitproof', 'Submit proof'),

            'canevaluate' => $this->get_bool_any(['canevaluate']),
            'evaluateurl' => $this->get_url('evaluateurl', new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $this->cm->id])),
            'evaluatelabel' => $this->get_component_string('evaluatechallenge', 'Evaluate challenge'),

            'canvalidateintegrity' => $this->get_bool_any(['canvalidateintegrity']),
            'integrityurl' => $this->get_url('integrityurl', new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $this->cm->id])),
            'integritylabel' => $this->get_component_string('integrityreview', 'Integrity review'),

            'cancontest' => $this->get_bool_any(['cancontest']),
            'contesturl' => $this->get_url('contesturl', new moodle_url('/mod/uckkchallenge/integrity.php', [
                'id' => $this->cm->id,
                'action' => 'contest',
            ])),
            'contestlabel' => $this->get_component_string('contestchallenge', 'Contest challenge'),

            'canarchive' => $this->get_bool_any(['canarchive']),
            'archiveurl' => $this->get_url('archiveurl', new moodle_url('/mod/uckkchallenge/archive.php', ['id' => $this->cm->id])),
            'archivelabel' => $this->get_component_string('archivechallenge', 'Archive challenge'),
        ];
    }

    /**
     * Export action links as the array expected by the template.
     *
     * @param stdClass $actions Action flags.
     * @return array<int, stdClass>
     */
    private function export_action_rows(stdClass $actions): array {
        $rows = [];

        if (!empty($actions->canedit)) {
            $rows[] = $this->action_row('edit', $actions->editlabel, $actions->editurl, false, true, false);
        }

        if (!empty($actions->cansubmit)) {
            $rows[] = $this->action_row('submit', $actions->submitlabel, $actions->submiturl, true, false, false);
        }

        if (!empty($actions->canevaluate)) {
            $rows[] = $this->action_row('evaluate', $actions->evaluatelabel, $actions->evaluateurl, false, true, false);
        }

        if (!empty($actions->canvalidateintegrity)) {
            $rows[] = $this->action_row('integrity', $actions->integritylabel, $actions->integrityurl, false, true, false);
        }

        if (!empty($actions->cancontest)) {
            $rows[] = $this->action_row('contest', $actions->contestlabel, $actions->contesturl, false, false, true);
        }

        if (!empty($actions->canarchive)) {
            $rows[] = $this->action_row('archive', $actions->archivelabel, $actions->archiveurl, false, true, false);
        }

        return array_values(array_filter($rows, static fn(stdClass $row): bool => trim($row->url) !== ''));
    }

    /**
     * Build one action row.
     *
     * @param string $key Key.
     * @param string $label Label.
     * @param string $url URL.
     * @param bool $primary Primary button.
     * @param bool $secondary Secondary button.
     * @param bool $danger Danger button.
     * @return stdClass
     */
    private function action_row(string $key, string $label, string $url, bool $primary, bool $secondary, bool $danger): stdClass {
        return (object)[
            'key' => $key,
            'label' => $label,
            'url' => $url,
            'primary' => $primary,
            'secondary' => $secondary,
            'danger' => $danger,
            'disabled' => false,
        ];
    }

    /**
     * Export generic list of records.
     *
     * @param mixed $records Raw list.
     * @param string $type Display type.
     * @return array<int, stdClass>
     */
    private function export_records(mixed $records, string $type): array {
        if (!is_array($records)) {
            return [];
        }

        $exported = [];

        foreach ($records as $record) {
            if (!is_array($record) && !$record instanceof stdClass) {
                continue;
            }

            $row = (array)$record;
            $title = (string)($row['title'] ?? $row['name'] ?? $row['label'] ?? '');
            $summary = (string)($row['summary'] ?? $row['description'] ?? $row['content'] ?? '');
            $status = $this->normalise_token((string)($row['status'] ?? ''), '');
            $visibility = $this->normalise_token((string)($row['visibility'] ?? ''), '');
            $timecreated = (int)($row['timecreated'] ?? 0);
            $timemodified = (int)($row['timemodified'] ?? 0);

            $exported[] = (object)[
                'id' => (int)($row['id'] ?? 0),
                'type' => $type,
                'title' => $title !== '' ? format_string($title, true, ['context' => $this->context]) : '',
                'name' => $title !== '' ? format_string($title, true, ['context' => $this->context]) : '',
                'hastitle' => $title !== '',
                'summary' => $summary !== '' ? format_text($summary, FORMAT_HTML, ['context' => $this->context]) : '',
                'hassummary' => $summary !== '',
                'content' => $summary !== '' ? format_text($summary, FORMAT_HTML, ['context' => $this->context]) : '',
                'hascontent' => $summary !== '',
                'status' => $status,
                'statuslabel' => $status !== '' ? $this->get_component_string('status:' . str_replace('_', '', $status), ucfirst(str_replace('_', ' ', $status))) : '',
                'hasstatus' => $status !== '',
                'statusclass' => $status !== '' ? 'status-' . str_replace('_', '-', $status) : '',
                'visibility' => $visibility,
                'visibilitylabel' => $visibility !== '' ? $this->get_component_string('visibility:' . str_replace('_', '', $visibility), ucfirst(str_replace('_', ' ', $visibility))) : '',
                'hasvisibility' => $visibility !== '',
                'url' => $this->normalise_url($row['url'] ?? null),
                'hasurl' => $this->normalise_url($row['url'] ?? null) !== '',
                'actionurl' => $this->normalise_url($row['actionurl'] ?? null),
                'hasactionurl' => $this->normalise_url($row['actionurl'] ?? null) !== '',
                'actionlabel' => (string)($row['actionlabel'] ?? get_string('view')),
                'timecreated' => $timecreated,
                'hastimecreated' => $timecreated > 0,
                'timecreatedlabel' => $timecreated > 0 ? userdate($timecreated) : '',
                'timemodified' => $timemodified,
                'hastimemodified' => $timemodified > 0,
                'timemodifiedlabel' => $timemodified > 0 ? userdate($timemodified) : '',
                'isrestricted' => in_array($visibility, ['restricted', 'restricted_integrity'], true),
                'isvalidated' => in_array($status, ['validated', 'accepted', 'approved', 'complete', 'completed'], true),
                'iscontested' => $status === 'contested',
                'metadata' => $this->normalise_metadata_for_template($row['metadata'] ?? []),
            ];
        }

        return $exported;
    }

    /**
     * Export notice/warning list.
     *
     * @param mixed $messages Raw messages.
     * @return array<int, stdClass>
     */
    private function export_message_list(mixed $messages): array {
        if (!is_array($messages)) {
            return [];
        }

        $exported = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $text = $message;
                $type = 'info';
            } else if (is_array($message) || $message instanceof stdClass) {
                $row = (array)$message;
                $text = (string)($row['message'] ?? $row['text'] ?? '');
                $type = $this->normalise_token((string)($row['type'] ?? 'info'), 'info');
            } else {
                continue;
            }

            if (trim($text) === '') {
                continue;
            }

            $exported[] = (object)[
                'type' => $type,
                'message' => format_string($text, true, ['context' => $this->context]),
                'class' => 'alert-' . $type,
            ];
        }

        return $exported;
    }

    /**
     * Format module intro safely.
     *
     * @return string
     */
    private function format_intro(): string {
        if (trim((string)($this->challenge->intro ?? '')) === '') {
            return '';
        }

        return format_module_intro('uckkchallenge', $this->challenge, (int)$this->cm->id);
    }

    /**
     * Format the first existing rich-text field from the challenge record.
     *
     * @param array<int, string> $fields Candidate fields.
     * @return string
     */
    private function format_text_field(array $fields): string {
        foreach ($fields as $field) {
            $value = trim((string)($this->challenge->{$field} ?? ''));

            if ($value === '') {
                continue;
            }

            $formatfield = $field . 'format';
            $format = isset($this->challenge->{$formatfield}) ? (int)$this->challenge->{$formatfield} : FORMAT_HTML;

            return format_text($value, $format, [
                'context' => $this->context,
                'noclean' => false,
                'overflowdiv' => true,
            ]);
        }

        return '';
    }

    /**
     * Build title/description rows from a plain text or HTML field.
     *
     * @param array<int, string> $fields Candidate fields.
     * @param string $type Row type.
     * @return array<int, stdClass>
     */
    private function build_text_rows(array $fields, string $type): array {
        foreach ($fields as $field) {
            $value = trim((string)($this->challenge->{$field} ?? ''));

            if ($value === '') {
                continue;
            }

            $plain = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $value)));
            $lines = preg_split('/\R+/', $plain) ?: [];
            $rows = [];

            foreach ($lines as $index => $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•0123456789.)");

                if ($line === '') {
                    continue;
                }

                $rows[] = (object)[
                    'title' => ucfirst($type) . ' ' . (count($rows) + 1),
                    'description' => format_text($line, FORMAT_PLAIN, ['context' => $this->context]),
                    'ruletype' => $type,
                    'statusclass' => '',
                    'required' => $type === 'rule',
                    'statuslabel' => '',
                ];
            }

            if (!empty($rows)) {
                return $rows;
            }
        }

        return [];
    }

    /**
     * Get a URL from provided data or fallback.
     *
     * @param string $key Data key.
     * @param moodle_url|null $fallback Fallback URL.
     * @return string
     */
    private function get_url(string $key, ?moodle_url $fallback = null): string {
        if (array_key_exists($key, $this->data)) {
            return $this->normalise_url($this->data[$key]);
        }

        return $fallback ? $fallback->out(false) : '';
    }

    /**
     * Normalise a URL-like value.
     *
     * @param mixed $url Raw URL.
     * @return string
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return $url;
        }

        return '';
    }

    /**
     * Get a boolean from any candidate data key.
     *
     * @param array<int, string> $keys Candidate keys.
     * @param bool $default Default.
     * @return bool
     */
    private function get_bool_any(array $keys, bool $default = false): bool {
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->data)) {
                return !empty($this->data[$key]);
            }
        }

        return $default;
    }

    /**
     * Return a component string with fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function get_component_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return $fallback;
    }

    /**
     * Normalise a token for CSS/status use.
     *
     * @param string $value Raw value.
     * @param string $default Default value.
     * @return string
     */
    private function normalise_token(string $value, string $default): string {
        $value = clean_param($value, PARAM_ALPHANUMEXT);

        return $value !== '' ? $value : $default;
    }

    /**
     * Normalise a percentage value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function normalise_percent(mixed $value): int {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, min(100, (int)$value));
    }

    /**
     * Prepare metadata for Mustache without exposing complex PHP values.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<int, stdClass>
     */
    private function normalise_metadata_for_template(mixed $metadata): array {
        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : ['metadata' => $metadata];
        }

        if ($metadata instanceof stdClass) {
            $metadata = (array)$metadata;
        }

        if (!is_array($metadata)) {
            return [];
        }

        $items = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $items[] = (object)[
                'key' => clean_param((string)$key, PARAM_TEXT),
                'value' => format_string((string)$value, true, ['context' => $this->context]),
            ];
        }

        return $items;
    }
}
