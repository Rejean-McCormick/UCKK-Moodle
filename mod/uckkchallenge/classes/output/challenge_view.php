<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace mod_uckkchallenge\output;

use context_module;
use mod_uckkchallenge\local\challenge as challenge_domain;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable view model for a UCKK challenge.
 *
 * This class prepares display data only. It does not:
 * - check Moodle capabilities;
 * - mutate challenge state;
 * - evaluate submissions;
 * - validate integrity;
 * - archive records;
 * - award badges;
 * - certify competencies.
 *
 * Controllers and services must pass already permission-filtered data.
 *
 * @package    mod_uckkchallenge
 * @category   output
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_view implements renderable, templatable {
    /**
     * Challenge record.
     *
     * @var stdClass
     */
    private stdClass $challenge;

    /**
     * Course record.
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
     * Prepared display data from the controller/service layer.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Constructor.
     *
     * Expected $data keys:
     * - userid
     * - viewurl
     * - editurl
     * - submiturl
     * - evaluateurl
     * - integrityurl
     * - archiveurl
     * - canedit
     * - cansubmit
     * - canevaluate
     * - canvalidateintegrity
     * - cancontest
     * - canarchive
     * - notices
     * - warnings
     * - submissions
     * - proofs
     * - evaluations
     * - integritycases
     * - archiveitems
     * - competencies
     * - badges
     * - progress
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
        $this->challenge = challenge_domain::normalise_record($challenge);
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
        $data = new stdClass();

        $data->uniqid = html_writer_id('mod-uckkchallenge-view-' . (int)$this->cm->id);
        $data->component = challenge_domain::COMPONENT;
        $data->courseid = (int)$this->course->id;
        $data->cmid = (int)$this->cm->id;
        $data->contextid = (int)$this->context->id;
        $data->userid = (int)($this->data['userid'] ?? 0);

        $data->challenge = $this->export_challenge_identity();
        $data->status = $this->export_status();
        $data->timeline = $this->export_timeline();
        $data->progress = $this->export_progress();
        $data->actions = $this->export_actions();

        $data->statement = $this->format_text_field('statement');
        $data->hasstatement = $data->statement !== '';

        $data->contexttext = $this->format_text_field('contexttext');
        $data->hascontexttext = $data->contexttext !== '';

        $data->rules = $this->format_text_field('rules');
        $data->hasrules = $data->rules !== '';

        $data->corridors = $this->format_text_field('corridors');
        $data->hascorridors = $data->corridors !== '';

        $data->ethicalconstraints = $this->format_text_field('ethicalconstraints');
        $data->hasethicalconstraints = $data->ethicalconstraints !== '';

        $data->evidencepolicy = $this->format_text_field('evidencepolicy');
        $data->hasevidencepolicy = $data->evidencepolicy !== '';

        $data->criteria = $this->format_text_field('criteria');
        $data->hascriteria = $data->criteria !== '';

        $data->publicsummary = $this->format_text_field('publicsummary');
        $data->haspublicsummary = $data->publicsummary !== '';

        $data->aipolicy = $this->format_text_field('aipolicy');
        $data->hasaipolicy = $data->aipolicy !== '';

        $data->notices = $this->export_message_list($this->data['notices'] ?? []);
        $data->hasnotices = !empty($data->notices);

        $data->warnings = $this->export_message_list($this->data['warnings'] ?? []);
        $data->haswarnings = !empty($data->warnings);

        $data->submissions = $this->export_records($this->data['submissions'] ?? [], 'submission');
        $data->hassubmissions = !empty($data->submissions);

        $data->proofs = $this->export_records($this->data['proofs'] ?? [], 'proof');
        $data->hasproofs = !empty($data->proofs);

        $data->evaluations = $this->export_records($this->data['evaluations'] ?? [], 'evaluation');
        $data->hasevaluations = !empty($data->evaluations);

        $data->integritycases = $this->export_records($this->data['integritycases'] ?? [], 'integritycase');
        $data->hasintegritycases = !empty($data->integritycases);

        $data->archiveitems = $this->export_records($this->data['archiveitems'] ?? [], 'archiveitem');
        $data->hasarchiveitems = !empty($data->archiveitems);

        $data->competencies = $this->export_records($this->data['competencies'] ?? [], 'competency');
        $data->hascompetencies = !empty($data->competencies);

        $data->badges = $this->export_records($this->data['badges'] ?? [], 'badge');
        $data->hasbadges = !empty($data->badges);

        $data->showoverview = $this->get_bool('showoverview', true);
        $data->showevidence = $this->get_bool('showevidence', true);
        $data->showevaluation = $this->get_bool('showevaluation', true);
        $data->showintegrity = $this->get_bool('showintegrity', true);
        $data->showarchive = $this->get_bool('showarchive', true);
        $data->showrecognition = $this->get_bool('showrecognition', true);

        $data->emptylabel = get_string('emptychallengeview', 'uckkchallenge');
        $data->nonsovereignailabel = get_string('ainotauthority_desc', 'uckkchallenge');

        return $data;
    }

    /**
     * Export challenge identity.
     *
     * @return stdClass
     */
    private function export_challenge_identity(): stdClass {
        return (object)[
            'id' => (int)($this->challenge->id ?? 0),
            'name' => format_string((string)$this->challenge->name, true, ['context' => $this->context]),
            'challengecode' => (string)$this->challenge->challengecode,
            'haschallengecode' => (string)$this->challenge->challengecode !== '',
            'challengetype' => (string)$this->challenge->challengetype,
            'challengetypelabel' => $this->get_prefixed_string(
                'challengetype',
                (string)$this->challenge->challengetype
            ),
            'courseid' => (int)$this->course->id,
            'coursename' => format_string((string)$this->course->fullname, true, ['context' => $this->context]),
            'cmid' => (int)$this->cm->id,
            'viewurl' => $this->get_url('viewurl', new moodle_url('/mod/uckkchallenge/view.php', ['id' => $this->cm->id])),
        ];
    }

    /**
     * Export status and state flags.
     *
     * @return stdClass
     */
    private function export_status(): stdClass {
        $domain = challenge_domain::from_record($this->challenge);
        $status = $domain->get_status();
        $visibility = $domain->get_visibility();

        return (object)[
            'status' => $status,
            'statuslabel' => $this->get_prefixed_string('status', $status),
            'statusclass' => 'status-' . str_replace('_', '-', $status),
            'visibility' => $visibility,
            'visibilitylabel' => $this->get_prefixed_string('visibility', $visibility),
            'visibilityclass' => 'visibility-' . str_replace('_', '-', $visibility),
            'archivepolicy' => (string)$this->challenge->archivepolicy,
            'archivepolicylabel' => $this->get_prefixed_string('archivepolicy', (string)$this->challenge->archivepolicy),
            'isopenforsubmission' => $domain->is_open_for_submission(),
            'isbeforeopentime' => $domain->is_before_open_time(),
            'isafterclosetime' => $domain->is_after_close_time(),
            'requiresreview' => $domain->requires_review(),
            'requiresintegrityreview' => $domain->requires_integrity_review(),
            'isterminal' => $domain->is_terminal(),
            'canproducearchiveoutput' => $domain->can_produce_archive_output(),
            'ispublicfacing' => $domain->is_public_facing(),
            'requirespublicsafeguards' => $domain->requires_public_safeguards(),
            'allowsaiassistance' => $domain->allows_ai_assistance(),
            'allowsaifinalauthority' => $domain->allows_ai_final_authority(),
        ];
    }

    /**
     * Export timeline data.
     *
     * @return stdClass
     */
    private function export_timeline(): stdClass {
        $timeopen = (int)($this->challenge->timeopen ?? 0);
        $timeclose = (int)($this->challenge->timeclose ?? 0);
        $timereviewby = (int)($this->challenge->timereviewby ?? 0);

        return (object)[
            'timeopen' => $timeopen,
            'hastimeopen' => $timeopen > 0,
            'timeopenlabel' => $timeopen > 0 ? userdate($timeopen) : '',
            'timeclose' => $timeclose,
            'hastimeclose' => $timeclose > 0,
            'timecloselabel' => $timeclose > 0 ? userdate($timeclose) : '',
            'timereviewby' => $timereviewby,
            'hastimereviewby' => $timereviewby > 0,
            'timereviewbylabel' => $timereviewby > 0 ? userdate($timereviewby) : '',
            'isoverdue' => $timeclose > 0 && $timeclose < time() && !challenge_domain::from_record($this->challenge)->is_terminal(),
            'isduesoon' => $timeclose > time() && $timeclose <= time() + WEEKSECS,
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
        $completed = (int)($progress['completed'] ?? 0);
        $total = (int)($progress['total'] ?? 0);

        return (object)[
            'percent' => $percent,
            'progresspercent' => $percent,
            'progresslabel' => get_string('progresspercent', 'uckkchallenge', $percent),
            'completed' => $completed,
            'total' => $total,
            'hascount' => $total > 0,
            'countlabel' => $total > 0 ? get_string('progresscount', 'uckkchallenge', (object)[
                'completed' => $completed,
                'total' => $total,
            ]) : '',
        ];
    }

    /**
     * Export action links.
     *
     * @return stdClass
     */
    private function export_actions(): stdClass {
        return (object)[
            'canedit' => $this->get_bool('canedit'),
            'editurl' => $this->get_url('editurl', new moodle_url('/course/modedit.php', ['update' => $this->cm->id])),
            'editlabel' => get_string('editsettings'),

            'cansubmit' => $this->get_bool('cansubmit'),
            'submiturl' => $this->get_url('submiturl', new moodle_url('/mod/uckkchallenge/submit.php', ['id' => $this->cm->id])),
            'submitlabel' => get_string('submitproof', 'uckkchallenge'),

            'canevaluate' => $this->get_bool('canevaluate'),
            'evaluateurl' => $this->get_url('evaluateurl', new moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $this->cm->id])),
            'evaluatelabel' => get_string('evaluatechallenge', 'uckkchallenge'),

            'canvalidateintegrity' => $this->get_bool('canvalidateintegrity'),
            'integrityurl' => $this->get_url('integrityurl', new moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $this->cm->id])),
            'integritylabel' => get_string('integrityreview', 'uckkchallenge'),

            'cancontest' => $this->get_bool('cancontest'),
            'contesturl' => $this->get_url('contesturl', new moodle_url('/mod/uckkchallenge/integrity.php', [
                'id' => $this->cm->id,
                'action' => 'contest',
            ])),
            'contestlabel' => get_string('contestchallenge', 'uckkchallenge'),

            'canarchive' => $this->get_bool('canarchive'),
            'archiveurl' => $this->get_url('archiveurl', new moodle_url('/mod/uckkchallenge/archive.php', ['id' => $this->cm->id])),
            'archivelabel' => get_string('archivechallenge', 'uckkchallenge'),

            'hasprimaryactions' => $this->get_bool('cansubmit')
                || $this->get_bool('canevaluate')
                || $this->get_bool('canvalidateintegrity')
                || $this->get_bool('canarchive'),
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
            $row = (array)$record;

            $title = (string)($row['title'] ?? $row['name'] ?? $row['label'] ?? '');
            $summary = (string)($row['summary'] ?? $row['description'] ?? '');
            $status = (string)($row['status'] ?? '');
            $visibility = (string)($row['visibility'] ?? '');

            $timecreated = (int)($row['timecreated'] ?? 0);
            $timemodified = (int)($row['timemodified'] ?? 0);

            $exported[] = (object)[
                'id' => (int)($row['id'] ?? 0),
                'type' => $type,
                'title' => $title !== '' ? format_string($title, true, ['context' => $this->context]) : '',
                'hastitle' => $title !== '',
                'summary' => $summary !== '' ? format_text($summary, FORMAT_HTML, ['context' => $this->context]) : '',
                'hassummary' => $summary !== '',
                'status' => $status,
                'statuslabel' => $status !== '' ? $this->get_record_status_label($status) : '',
                'hasstatus' => $status !== '',
                'statusclass' => $status !== '' ? 'status-' . str_replace('_', '-', $status) : '',
                'visibility' => $visibility,
                'visibilitylabel' => $visibility !== '' ? $this->get_prefixed_string('visibility', $visibility) : '',
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
                'isrestricted' => !empty($row['isrestricted']) || $visibility === challenge_domain::VISIBILITY_RESTRICTED_INTEGRITY,
                'isvalidated' => $status === challenge_domain::STATUS_VALIDATED,
                'iscontested' => $status === challenge_domain::STATUS_CONTESTED,
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
            $row = (array)$message;
            $text = (string)($row['message'] ?? $row['text'] ?? $message);

            if (trim($text) === '') {
                continue;
            }

            $type = clean_param((string)($row['type'] ?? 'info'), PARAM_ALPHANUMEXT);

            $exported[] = (object)[
                'type' => $type,
                'message' => format_string($text, true, ['context' => $this->context]),
                'class' => 'alert-' . $type,
            ];
        }

        return $exported;
    }

    /**
     * Format a rich-text field from the challenge record.
     *
     * @param string $field Field name.
     * @return string
     */
    private function format_text_field(string $field): string {
        $value = trim((string)($this->challenge->{$field} ?? ''));

        if ($value === '') {
            return '';
        }

        $formatfield = $field . 'format';
        $format = isset($this->challenge->{$formatfield}) ? (int)$this->challenge->{$formatfield} : FORMAT_HTML;

        return format_text($value, $format, [
            'context' => $this->context,
            'noclean' => false,
            'overflowdiv' => true,
        ]);
    }

    /**
     * Get a URL from provided data or a fallback.
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
     * Get a boolean from provided data.
     *
     * @param string $key Data key.
     * @param bool $default Default value.
     * @return bool
     */
    private function get_bool(string $key, bool $default = false): bool {
        if (!array_key_exists($key, $this->data)) {
            return $default;
        }

        return !empty($this->data[$key]);
    }

    /**
     * Get a component string in the prefix:value pattern.
     *
     * @param string $prefix Prefix.
     * @param string $value Value.
     * @return string
     */
    private function get_prefixed_string(string $prefix, string $value): string {
        $key = $prefix . ':' . $value;

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $value));
    }

    /**
     * Get generic status label for records that may use challenge or integrity states.
     *
     * @param string $status Status key.
     * @return string
     */
    private function get_record_status_label(string $status): string {
        if (get_string_manager()->string_exists('status:' . $status, 'uckkchallenge')) {
            return get_string('status:' . $status, 'uckkchallenge');
        }

        if (get_string_manager()->string_exists('integritystate:' . $status, 'uckkchallenge')) {
            return get_string('integritystate:' . $status, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $status));
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