<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Core renderer helpers for the UCKK theme.
 *
 * UCKK is a thin child theme over Boost. This renderer must not replace
 * Moodle or Boost behaviour; it only exposes presentation helpers for
 * UCKK-specific templates and layout fragments.
 *
 * It must not contain institutional workflow rules, grading logic,
 * integrity decisions, archive validation rules, or permission policy.
 *
 * Business data must be prepared by the relevant plugin, renderable,
 * output exporter, external service, or block. This renderer only formats
 * safe display data and delegates HTML to Mustache templates where possible.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uckk\output;

use html_writer;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK core renderer.
 *
 * This class intentionally extends Boost and should not override Moodle core
 * rendering methods unless there is a documented presentational requirement.
 *
 * @package    theme_uckk
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Render the UCKK campus header.
     *
     * This helper is intended for UCKK-specific presentation surfaces such as
     * the front page, dashboard fragments, blocks, and Mustache-backed outputs.
     * It must not be used to decide page access, academic state, or workflow.
     *
     * @param array $overrides Optional display overrides.
     * @return string Rendered HTML.
     */
    public function uckk_campus_header(array $overrides = []): string {
        $data = $this->get_uckk_base_context();

        $data->title = $overrides['title'] ?? get_string('uckkfullname', 'theme_uckk');
        $data->subtitle = $overrides['subtitle'] ?? get_string('uckkcampus', 'theme_uckk');
        $data->tagline = $overrides['tagline'] ?? get_string('uckktagline', 'theme_uckk');
        $data->description = $overrides['description'] ?? get_string('uckkdescription', 'theme_uckk');
        $data->showtagline = $overrides['showtagline'] ?? true;
        $data->showdescription = $overrides['showdescription'] ?? true;
        $data->showformula = $overrides['showformula'] ?? true;
        $data->formula = get_string('formula_short', 'theme_uckk');

        return $this->render_from_template('theme_uckk/dashboard_header', $data);
    }

    /**
     * Render a canonical UCKK statement.
     *
     * @param string|null $statement Statement text. Defaults to the UCKK governance formula.
     * @param string $type Visual type: default, method, warning, integrity, ai.
     * @param string|null $title Optional title.
     * @return string Rendered HTML.
     */
    public function uckk_canonical_statement(?string $statement = null, string $type = 'default', ?string $title = null): string {
        $statement = $statement ?? get_string('formula_governance', 'theme_uckk');
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        $classes = [
            'uckk-canonical-statement',
            'uckk-canonical-statement-' . $type,
        ];

        if ($type === 'warning') {
            $classes[] = 'uckk-warning-statement';
        }

        if ($type === 'method') {
            $classes[] = 'uckk-method-statement';
        }

        if ($type === 'integrity') {
            $classes[] = 'uckk-integrity-notice';
        }

        if ($type === 'ai') {
            $classes[] = 'uckk-ai-notice';
        }

        $content = '';

        if ($title !== null && $title !== '') {
            $content .= html_writer::tag('strong', s($title), ['class' => 'd-block mb-1']);
        }

        $content .= html_writer::span(s($statement), 'uckk-canonical-statement-text');

        return html_writer::div($content, implode(' ', $classes), ['role' => 'note']);
    }

    /**
     * Render the UCKK canonical boundary notice.
     *
     * This notice helps preserve the distinction between UCKK, kOA,
     * kOA Digital Ecosystem, King Klown, Inquisiteur, Assemblées and Archives.
     *
     * @return string Rendered HTML.
     */
    public function uckk_boundary_notice(): string {
        $message = get_string('footer_canonicalwarning', 'theme_uckk');

        return html_writer::div(
            s($message),
            'uckk-boundary-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-boundary-notice',
            ]
        );
    }

    /**
     * Render an accreditation / internal recognition notice.
     *
     * @return string Rendered HTML.
     */
    public function uckk_internal_recognition_notice(): string {
        return html_writer::div(
            s(get_string('uckknotaccredited', 'theme_uckk')),
            'alert alert-light border uckk-canonical-statement uckk-canonical-statement-sm',
            [
                'role' => 'note',
                'data-region' => 'uckk-internal-recognition-notice',
            ]
        );
    }

    /**
     * Render a UCKK status badge.
     *
     * This is visual only. The status must be computed by the caller.
     *
     * @param string $status Machine status.
     * @param string|null $label Optional display label.
     * @param string $extra Extra CSS classes.
     * @return string Rendered HTML.
     */
    public function uckk_status_badge(string $status, ?string $label = null, string $extra = ''): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);
        $label = $label ?? $this->get_status_label($status);

        $classes = trim(
            'uckk-status-badge uckk-status-' . $status . ' badge ' . $this->get_bootstrap_badge_class($status) . ' ' . $extra
        );

        return html_writer::span(
            s($label),
            $classes,
            [
                'data-region' => 'uckk-status-badge',
                'data-status' => $status,
            ]
        );
    }

    /**
     * Render a UCKK visibility badge.
     *
     * This is visual only. Visibility rules must be computed by the caller.
     *
     * @param string $visibility Visibility key.
     * @param string|null $label Optional display label.
     * @return string Rendered HTML.
     */
    public function uckk_visibility_badge(string $visibility, ?string $label = null): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);
        $label = $label ?? $this->get_visibility_label($visibility);

        return html_writer::span(
            s($label),
            'uckk-visibility-badge uckk-visibility-' . $visibility,
            [
                'data-region' => 'uckk-visibility-badge',
                'data-visibility' => $visibility,
            ]
        );
    }

    /**
     * Render a symbolic UCKK badge.
     *
     * This is a visual label only. It must not be confused with Moodle badges
     * or Moodle technical roles.
     *
     * @param string $label Badge label.
     * @param string $type Symbolic type.
     * @return string Rendered HTML.
     */
    public function uckk_symbolic_badge(string $label, string $type = 'default'): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);

        return html_writer::span(
            s($label),
            'uckk-symbolic-badge uckk-symbolic-badge-' . $type,
            [
                'data-region' => 'uckk-symbolic-badge',
                'data-symbolic-type' => $type,
            ]
        );
    }

    /**
     * Render the UCKK AI warning.
     *
     * @return string Rendered HTML.
     */
    public function uckk_ai_warning(): string {
        return html_writer::div(
            html_writer::span(s(get_string('ai_nonsovereign', 'theme_uckk')), 'uckk-ai-label mr-2')
            . html_writer::span(s(get_string('ai_warning', 'theme_uckk'))),
            'uckk-ai-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-ai-warning',
            ]
        );
    }

    /**
     * Render the UCKK integrity notice.
     *
     * @return string Rendered HTML.
     */
    public function uckk_integrity_notice(): string {
        $content = html_writer::tag(
            'strong',
            s(get_string('integrity_guardrail', 'theme_uckk')),
            ['class' => 'd-block mb-1']
        );

        $content .= html_writer::span(s(get_string('integrity_notice', 'theme_uckk')));

        return html_writer::div(
            $content,
            'uckk-integrity-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-integrity-notice',
            ]
        );
    }

    /**
     * Render a compact UCKK challenge teaser.
     *
     * This method prepares a safe display context and delegates markup to
     * theme_uckk/challenge_teaser. It does not decide permissions; callers
     * should pass canview, cansubmit and canreviewintegrity explicitly.
     *
     * @param array|stdClass $challenge Challenge display data.
     * @return string Rendered HTML.
     */
    public function uckk_challenge_teaser($challenge): string {
        $data = (object)(array)$challenge;

        $data->id = $data->id ?? 0;
        $data->title = $data->title ?? get_string('challenge', 'theme_uckk');
        $data->summary = $data->summary ?? '';
        $data->viewurl = $this->normalise_url($data->viewurl ?? '#');
        $data->status = clean_param($data->status ?? 'draft', PARAM_ALPHANUMEXT);
        $data->statuslabel = $data->statuslabel ?? $this->get_status_label($data->status);
        $data->statustype = $data->statustype ?? $this->get_status_type($data->status);

        $data->challengecode = $data->challengecode ?? null;
        $data->typelabel = $data->typelabel ?? null;
        $data->imageurl = $this->normalise_url($data->imageurl ?? '');
        $data->hasimage = !empty($data->imageurl);

        $data->duedate = $data->duedate ?? '';
        $data->hasduedate = !empty($data->duedate);

        $data->participantscount = (int)($data->participantscount ?? 0);
        $data->hasparticipants = !empty($data->hasparticipants) || $data->participantscount > 0;

        $data->proofscount = (int)($data->proofscount ?? 0);
        $data->hasproofs = !empty($data->hasproofs) || $data->proofscount > 0;

        $data->completionpercent = $this->normalise_percent($data->completionpercent ?? 0);
        $data->hasprogress = !empty($data->hasprogress) || $data->completionpercent > 0;

        $data->submiturl = $this->normalise_url($data->submiturl ?? '');
        $data->hassubmiturl = !empty($data->submiturl);

        $data->archiveurl = $this->normalise_url($data->archiveurl ?? '');
        $data->hasarchiveurl = !empty($data->archiveurl);

        $data->integrityurl = $this->normalise_url($data->integrityurl ?? '');
        $data->hasintegrityurl = !empty($data->integrityurl);

        $data->integrityrequired = !empty($data->integrityrequired);
        $data->iscontested = !empty($data->iscontested);
        $data->isinvalidated = !empty($data->isinvalidated);
        $data->isarchived = !empty($data->isarchived);

        // Permission flags are display inputs, not theme decisions.
        $data->canview = !empty($data->canview);
        $data->cansubmit = !empty($data->cansubmit);
        $data->canreviewintegrity = !empty($data->canreviewintegrity);
        $data->showpublicwarning = !isset($data->showpublicwarning) || !empty($data->showpublicwarning);

        if (!isset($data->tags) || !is_array($data->tags)) {
            $data->tags = [];
        }

        $data->hastags = !empty($data->tags);

        return $this->render_from_template('theme_uckk/challenge_teaser', $data);
    }

    /**
     * Render a generic UCKK card.
     *
     * @param string $title Card title.
     * @param string $body Card body.
     * @param array $options Display options.
     * @return string Rendered HTML.
     */
    public function uckk_card(string $title, string $body, array $options = []): string {
        $status = clean_param($options['status'] ?? '', PARAM_ALPHANUMEXT);

        $data = (object)[
            'title' => $title,
            'body' => $body,
            'subtitle' => $options['subtitle'] ?? '',
            'hassubtitle' => !empty($options['subtitle']),
            'url' => $this->normalise_url($options['url'] ?? ''),
            'hasurl' => !empty($options['url']),
            'linklabel' => $options['linklabel'] ?? get_string('template_viewdetails', 'theme_uckk'),
            'classes' => $this->normalise_classes($options['classes'] ?? ''),
            'status' => $status,
            'hasstatus' => $status !== '',
            'statuslabel' => $options['statuslabel'] ?? ($status !== '' ? $this->get_status_label($status) : ''),
        ];

        return $this->render_from_template('theme_uckk/uckk_card', $data);
    }

    /**
     * Build base template data shared by UCKK theme templates.
     *
     * @return stdClass
     */
    protected function get_uckk_base_context(): stdClass {
        global $CFG;

        $course = $this->page->course ?? null;

        $data = new stdClass();
        $data->sitename = format_string($course->fullname ?? get_site()->fullname);
        $data->wwwroot = $CFG->wwwroot;
        $data->pageurl = $this->page->url instanceof moodle_url ? $this->page->url->out(false) : '';
        $data->component = 'theme_uckk';

        $data->uckk = get_string('uckk', 'theme_uckk');
        $data->uckkfullname = get_string('uckkfullname', 'theme_uckk');
        $data->uckkcampus = get_string('uckkcampus', 'theme_uckk');
        $data->tagline = get_string('uckktagline', 'theme_uckk');
        $data->formula = get_string('formula_governance', 'theme_uckk');
        $data->boundarynotice = get_string('footer_canonicalwarning', 'theme_uckk');

        return $data;
    }

    /**
     * Normalise a URL-like value for safe template output.
     *
     * @param mixed $url URL value.
     * @return string
     */
    protected function normalise_url($url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url)) {
            return trim($url);
        }

        return '';
    }

    /**
     * Normalise a space-separated CSS class list.
     *
     * @param mixed $classes Raw class list.
     * @return string
     */
    protected function normalise_classes($classes): string {
        if (!is_string($classes)) {
            return '';
        }

        return trim(preg_replace('/[^A-Za-z0-9_\-\s]/', '', $classes));
    }

    /**
     * Normalise a percentage value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    protected function normalise_percent($value): int {
        $value = (int)$value;

        if ($value < 0) {
            return 0;
        }

        if ($value > 100) {
            return 100;
        }

        return $value;
    }

    /**
     * Get a readable status label from theme language strings.
     *
     * This is a display fallback only. It must not be used to infer workflow state.
     *
     * @param string $status Status key.
     * @return string
     */
    protected function get_status_label(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);
        $stringkey = 'status_' . str_replace('-', '', $status);

        if (get_string_manager()->string_exists($stringkey, 'theme_uckk')) {
            return get_string($stringkey, 'theme_uckk');
        }

        $challengekey = 'challenge_' . str_replace('-', '', $status);
        if (get_string_manager()->string_exists($challengekey, 'theme_uckk')) {
            return get_string($challengekey, 'theme_uckk');
        }

        return ucfirst(str_replace(['_', '-'], ' ', $status));
    }

    /**
     * Get a readable visibility label from theme language strings.
     *
     * This is a display fallback only. It must not be used to infer visibility rules.
     *
     * @param string $visibility Visibility key.
     * @return string
     */
    protected function get_visibility_label(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);
        $stringkey = 'visibility_' . str_replace('-', '', $visibility);

        if (get_string_manager()->string_exists($stringkey, 'theme_uckk')) {
            return get_string($stringkey, 'theme_uckk');
        }

        return ucfirst(str_replace(['_', '-'], ' ', $visibility));
    }

    /**
     * Get a UCKK status type compatible with presentational templates.
     *
     * @param string $status Status key.
     * @return string Bootstrap-ish status type.
     */
    protected function get_status_type(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $success = [
            'active',
            'open',
            'validated',
            'verified',
        ];

        $warning = [
            'pending',
            'pending_review',
            'under_review',
            'integrity_review',
            'correction_required',
            'contested',
        ];

        $danger = [
            'rejected',
            'invalidated',
            'cancelled',
        ];

        $info = [
            'archived',
            'closed',
        ];

        if (in_array($status, $success, true)) {
            return 'success';
        }

        if (in_array($status, $warning, true)) {
            return 'warning';
        }

        if (in_array($status, $danger, true)) {
            return 'danger';
        }

        if (in_array($status, $info, true)) {
            return 'info';
        }

        return 'secondary';
    }

    /**
     * Get Bootstrap badge class from UCKK status.
     *
     * Kept compatible with Bootstrap 4 naming used by Moodle/Boost.
     *
     * @param string $status Status key.
     * @return string
     */
    protected function get_bootstrap_badge_class(string $status): string {
        return 'badge-' . $this->get_status_type($status);
    }
}
