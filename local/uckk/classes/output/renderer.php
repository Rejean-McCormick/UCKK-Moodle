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
 * Output renderer for local_uckk.
 *
 * This renderer is the presentation layer for the local_uckk plugin.
 *
 * It prepares safe display contexts and delegates structured markup to
 * Mustache templates. It must not contain persistence logic, grading logic,
 * archive validation, integrity decisions, challenge workflows, assembly
 * decisions or AI authority.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\output;

use html_writer;
use local_uckk\api\canon_api;
use local_uckk\local\player_profile;
use moodle_url;
use plugin_renderer_base;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/uckk/locallib.php');

/**
 * Renderer for local_uckk.
 *
 * @package local_uckk
 */
class renderer extends plugin_renderer_base {
    /** Default component. */
    private const COMPONENT = 'local_uckk';

    /**
     * Render the local UCKK dashboard.
     *
     * Expected template:
     * local/uckk/templates/dashboard.mustache
     *
     * @param mixed $dashboard Dashboard data or renderable.
     * @return string Rendered HTML.
     */
    public function render_dashboard($dashboard): string {
        $data = $this->export_context($dashboard);
        $data = $this->with_base_context($data);

        if (!isset($data->title) || $data->title === '') {
            $data->title = get_string('dashboard', self::COMPONENT);
        }

        if (!isset($data->cards) || !is_array($data->cards)) {
            $data->cards = [];
        }

        $data->hascards = !empty($data->cards);

        if (!isset($data->navigation) || !is_array($data->navigation)) {
            $data->navigation = local_uckk_get_navigation_items();
        }

        $data->hasnavigation = !empty($data->navigation);

        return $this->render_from_template('local_uckk/dashboard', $data);
    }

    /**
     * Render a UCKK program card.
     *
     * Expected template:
     * local/uckk/templates/program_card.mustache
     *
     * @param mixed $program Program data or renderable.
     * @return string Rendered HTML.
     */
    public function render_program_card($program): string {
        $data = $this->export_context($program);

        $data->id = $data->id ?? 0;
        $data->fullname = format_string((string)($data->fullname ?? $data->title ?? ''));
        $data->shortname = (string)($data->shortname ?? '');
        $data->idnumber = (string)($data->idnumber ?? '');
        $data->programtype = local_uckk_normalise_key((string)($data->programtype ?? $data->type ?? 'program'));
        $data->programtypelabel = $data->programtypelabel ?? $this->program_type_label($data->programtype);
        $data->status = local_uckk_normalise_status((string)($data->status ?? 'draft'));
        $data->statuslabel = $data->statuslabel ?? local_uckk_get_status_label($data->status);
        $data->visibility = local_uckk_normalise_visibility((string)($data->visibility ?? 'institution'));
        $data->visibilitylabel = $data->visibilitylabel ?? local_uckk_get_visibility_label($data->visibility);

        $data->description = $this->format_optional_text($data->description ?? '');
        $data->hasdescription = trim(strip_tags((string)$data->description)) !== '';

        $data->url = $this->normalise_url($data->url ?? $this->program_url($data));
        $data->hasurl = $data->url !== '';

        $data->coursecount = (int)($data->coursecount ?? 0);
        $data->hascourses = $data->coursecount > 0;

        $data->badgecount = (int)($data->badgecount ?? 0);
        $data->hasbadges = $data->badgecount > 0;

        $data->competencycount = (int)($data->competencycount ?? 0);
        $data->hascompetencies = $data->competencycount > 0;

        $data->internalrecognition = !empty($data->internalrecognition);
        $data->requiresportfolio = !empty($data->requiresportfolio);
        $data->requiresarchive = !empty($data->requiresarchive);
        $data->requiresintegrityreview = !empty($data->requiresintegrityreview);

        if (!isset($data->metadata) || !is_array($data->metadata)) {
            $data->metadata = [];
        }

        $data->hasmetadata = !empty($data->metadata);
        $data->statusbadge = $this->render_status_badge($data->status, $data->statuslabel);
        $data->visibilitybadge = $this->render_visibility_badge($data->visibility, $data->visibilitylabel);

        return $this->render_from_template('local_uckk/program_card', $data);
    }

    /**
     * Render a UCKK pathway card.
     *
     * Expected template:
     * local/uckk/templates/pathway_card.mustache
     *
     * @param mixed $pathway Pathway data or renderable.
     * @return string Rendered HTML.
     */
    public function render_pathway_card($pathway): string {
        $data = $this->export_context($pathway);

        $data->id = $data->id ?? 0;
        $data->name = format_string((string)($data->name ?? $data->title ?? ''));
        $data->key = local_uckk_normalise_key((string)($data->key ?? $data->shortname ?? 'pathway'));
        $data->programkey = local_uckk_normalise_key((string)($data->programkey ?? ''));
        $data->programlabel = $data->programlabel ?? ($data->programkey !== '' ? local_uckk_get_program_label($data->programkey) : '');

        $data->status = local_uckk_normalise_status((string)($data->status ?? 'draft'));
        $data->statuslabel = $data->statuslabel ?? local_uckk_get_status_label($data->status);
        $data->visibility = local_uckk_normalise_visibility((string)($data->visibility ?? 'private'));
        $data->visibilitylabel = $data->visibilitylabel ?? local_uckk_get_visibility_label($data->visibility);

        $data->summary = $this->format_optional_text($data->summary ?? $data->description ?? '');
        $data->hassummary = trim(strip_tags((string)$data->summary)) !== '';

        $data->progress = $this->normalise_percent($data->progress ?? 0);
        $data->hasprogress = isset($data->hasprogress) ? !empty($data->hasprogress) : true;

        $data->url = $this->normalise_url($data->url ?? $this->pathway_url($data));
        $data->hasurl = $data->url !== '';

        if (!isset($data->courses) || !is_array($data->courses)) {
            $data->courses = [];
        }

        if (!isset($data->competencies) || !is_array($data->competencies)) {
            $data->competencies = [];
        }

        if (!isset($data->badges) || !is_array($data->badges)) {
            $data->badges = [];
        }

        $data->hascourses = !empty($data->courses);
        $data->hascompetencies = !empty($data->competencies);
        $data->hasbadges = !empty($data->badges);
        $data->statusbadge = $this->render_status_badge($data->status, $data->statuslabel);
        $data->visibilitybadge = $this->render_visibility_badge($data->visibility, $data->visibilitylabel);

        return $this->render_from_template('local_uckk/pathway_card', $data);
    }

    /**
     * Render a UCKK player profile.
     *
     * Expected template:
     * local/uckk/templates/player_profile.mustache
     *
     * @param mixed $profile Player profile object, data object, array or renderable.
     * @param bool $includeprivate Whether to include private fields.
     * @return string Rendered HTML.
     */
    public function render_player_profile($profile, bool $includeprivate = false): string {
        if ($profile instanceof player_profile) {
            $data = (object)$profile->export_for_template($includeprivate);
        } else {
            $data = $this->export_context($profile);
        }

        $data->userid = (int)($data->userid ?? 0);
        $data->displaytitle = format_string((string)($data->displaytitle ?? get_string('player', self::COMPONENT)));

        if (!isset($data->symbolicroles) || !is_array($data->symbolicroles)) {
            $data->symbolicroles = [];
        }

        if (!isset($data->activepathways) || !is_array($data->activepathways)) {
            $data->activepathways = [];
        }

        if (!isset($data->badgesummary) || !is_array($data->badgesummary)) {
            $data->badgesummary = [];
        }

        if (!isset($data->competencysummary) || !is_array($data->competencysummary)) {
            $data->competencysummary = [];
        }

        if (!isset($data->archivesummary) || !is_array($data->archivesummary)) {
            $data->archivesummary = [];
        }

        $data->hassymbolicroles = !empty($data->symbolicroles);
        $data->hasactivepathways = !empty($data->activepathways);
        $data->hasbadgesummary = !empty($data->badgesummary);
        $data->hascompetencysummary = !empty($data->competencysummary);
        $data->hasarchivesummary = !empty($data->archivesummary);

        $data->profileurl = $this->normalise_url(
            $data->profileurl ?? new moodle_url('/local/uckk/profile.php', ['userid' => $data->userid])
        );
        $data->moodleprofileurl = $this->normalise_url(
            $data->moodleprofileurl ?? new moodle_url('/user/profile.php', ['id' => $data->userid])
        );

        $data->visibility = local_uckk_normalise_visibility((string)($data->visibility ?? 'private'));
        $data->visibilitylabel = $data->visibilitylabel ?? local_uckk_get_visibility_label($data->visibility);
        $data->status = local_uckk_normalise_status((string)($data->status ?? 'active'));
        $data->statuslabel = $data->statuslabel ?? local_uckk_get_status_label($data->status);

        $data->statusbadge = $this->render_status_badge($data->status, $data->statuslabel);
        $data->visibilitybadge = $this->render_visibility_badge($data->visibility, $data->visibilitylabel);

        return $this->render_from_template('local_uckk/player_profile', $data);
    }

    /**
     * Render a UCKK canon panel.
     *
     * Expected template:
     * local/uckk/templates/canon_panel.mustache
     *
     * @param mixed $canon Canon data, canon registry, record or renderable.
     * @return string Rendered HTML.
     */
    public function render_canon_panel($canon = null): string {
        if ($canon === null && class_exists(canon_api::class)) {
            $data = (object)canon_api::export_registry_for_template();
        } else {
            $data = $this->export_context($canon);
        }

        $data = $this->with_base_context($data);

        if (!isset($data->title) || $data->title === '') {
            $data->title = get_string('canon', self::COMPONENT);
        }

        if (!isset($data->formula) || $data->formula === '') {
            $data->formula = class_exists(canon_api::class)
                ? canon_api::get_control_formula()
                : local_uckk_get_boundary_notice();
        }

        if (!isset($data->boundarywarning) || $data->boundarywarning === '') {
            $data->boundarywarning = class_exists(canon_api::class)
                ? canon_api::get_boundary_warning()
                : local_uckk_get_boundary_notice();
        }

        if (!isset($data->categories) || !is_array($data->categories)) {
            $data->categories = [];
        }

        if (!isset($data->boundaries) || !is_array($data->boundaries)) {
            $data->boundaries = [];
        }

        $data->hascategories = !empty($data->categories);
        $data->hasboundaries = !empty($data->boundaries);

        return $this->render_from_template('local_uckk/canon_panel', $data);
    }

    /**
     * Render a reusable UCKK status badge.
     *
     * Expected template:
     * local/uckk/templates/status_badge.mustache
     *
     * If the template is unavailable, Moodle will raise the normal template
     * exception during development, which is preferred over silent failure.
     *
     * @param string $status Status key.
     * @param string|null $label Optional display label.
     * @param string $extra Extra CSS class.
     * @return string Rendered HTML.
     */
    public function render_status_badge(string $status, ?string $label = null, string $extra = ''): string {
        $status = local_uckk_normalise_status($status);
        $label = $label ?? local_uckk_get_status_label($status);

        $data = [
            'type' => 'status',
            'key' => $status,
            'label' => $label,
            'cssclass' => trim('uckk-status-badge uckk-status-' . $status . ' ' . $this->status_class($status) . ' ' . $extra),
            'dataattributes' => local_uckk_attributes_for_template([
                'data-region' => 'uckk-status-badge',
                'data-status' => $status,
            ]),
        ];

        return $this->render_from_template('local_uckk/status_badge', $data);
    }

    /**
     * Render a reusable UCKK visibility badge.
     *
     * @param string $visibility Visibility key.
     * @param string|null $label Optional display label.
     * @param string $extra Extra CSS class.
     * @return string Rendered HTML.
     */
    public function render_visibility_badge(string $visibility, ?string $label = null, string $extra = ''): string {
        $visibility = local_uckk_normalise_visibility($visibility);
        $label = $label ?? local_uckk_get_visibility_label($visibility);

        $data = [
            'type' => 'visibility',
            'key' => $visibility,
            'label' => $label,
            'cssclass' => trim('uckk-visibility-badge uckk-visibility-' . $visibility . ' ' . $extra),
            'dataattributes' => local_uckk_attributes_for_template([
                'data-region' => 'uckk-visibility-badge',
                'data-visibility' => $visibility,
            ]),
        ];

        return $this->render_from_template('local_uckk/status_badge', $data);
    }

    /**
     * Render an internal recognition notice.
     *
     * @return string Rendered HTML.
     */
    public function render_internal_recognition_notice(): string {
        return html_writer::div(
            s(local_uckk_get_internal_recognition_notice()),
            'alert alert-light border uckk-internal-recognition-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-internal-recognition-notice',
            ]
        );
    }

    /**
     * Render the canonical UCKK boundary notice.
     *
     * @return string Rendered HTML.
     */
    public function render_boundary_notice(): string {
        return html_writer::div(
            s(local_uckk_get_boundary_notice()),
            'uckk-boundary-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-boundary-notice',
            ]
        );
    }

    /**
     * Render the AI non-sovereignty notice.
     *
     * @return string Rendered HTML.
     */
    public function render_ai_warning(): string {
        $label = local_uckk_get_string_or_fallback(
            'ai_nonsovereign',
            'theme_uckk',
            'IA non souveraine'
        );

        $content = html_writer::span(
            s($label),
            'uckk-ai-label mr-2'
        );

        $content .= html_writer::span(
            s(local_uckk_get_ai_warning()),
            'uckk-ai-warning-text'
        );

        return html_writer::div(
            $content,
            'uckk-ai-notice',
            [
                'role' => 'note',
                'data-region' => 'uckk-ai-warning',
            ]
        );
    }

    /**
     * Render an integrity notice.
     *
     * @return string Rendered HTML.
     */
    public function render_integrity_notice(): string {
        $title = local_uckk_get_string_or_fallback(
            'integrity_guardrail',
            'theme_uckk',
            'Garde-fou éthique et méthodologique'
        );

        $message = local_uckk_get_string_or_fallback(
            'integrity_notice',
            'theme_uckk',
            'Toute preuve, décision ou mise en scène peut être vérifiée si l’intégrité du jeu est en cause.'
        );

        $content = html_writer::tag('strong', s($title), ['class' => 'd-block mb-1']);
        $content .= html_writer::span(s($message));

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
     * Render a component installation/status summary.
     *
     * @param array|null $statuses Optional statuses. Defaults to local registry.
     * @return string Rendered HTML.
     */
    public function render_component_statuses(?array $statuses = null): string {
        $statuses = $statuses ?? local_uckk_get_component_statuses();

        if (empty($statuses)) {
            return $this->render_empty_state(
                get_string('components', self::COMPONENT),
                get_string('nothingtodisplay')
            );
        }

        $headers = [
            get_string('component', self::COMPONENT),
            get_string('path', self::COMPONENT),
            get_string('status'),
        ];

        $rows = [];

        foreach ($statuses as $status) {
            $installed = !empty($status['installed']);
            $rows[] = [
                s((string)($status['component'] ?? '')),
                html_writer::tag('code', s((string)($status['path'] ?? ''))),
                $installed
                    ? $this->render_status_badge('active', get_string('installed', self::COMPONENT))
                    : $this->render_status_badge('pending', get_string('missing', self::COMPONENT)),
            ];
        }

        return html_writer::table((object)[
            'head' => $headers,
            'data' => $rows,
            'attributes' => [
                'class' => 'generaltable uckk-component-statuses',
            ],
        ]);
    }

    /**
     * Render a generic empty state.
     *
     * @param string $title Title.
     * @param string $message Message.
     * @return string Rendered HTML.
     */
    public function render_empty_state(string $title, string $message): string {
        $content = html_writer::tag('h3', s($title), ['class' => 'h5 uckk-empty-state-title']);
        $content .= html_writer::tag('p', s($message), ['class' => 'uckk-empty-state-text']);

        return html_writer::div(
            $content,
            'uckk-empty-state',
            [
                'data-region' => 'uckk-empty-state',
            ]
        );
    }

    /**
     * Render a generic page heading block.
     *
     * @param string $title Title.
     * @param string|null $subtitle Optional subtitle.
     * @param string|null $description Optional description.
     * @return string Rendered HTML.
     */
    public function render_heading(string $title, ?string $subtitle = null, ?string $description = null): string {
        $content = html_writer::tag('h2', s($title), ['class' => 'uckk-page-title']);

        if ($subtitle !== null && trim($subtitle) !== '') {
            $content .= html_writer::tag('div', s($subtitle), ['class' => 'uckk-page-subtitle']);
        }

        if ($description !== null && trim($description) !== '') {
            $content .= html_writer::tag('p', s($description), ['class' => 'uckk-page-description']);
        }

        return html_writer::div(
            $content,
            'uckk-page-heading',
            [
                'data-region' => 'uckk-page-heading',
            ]
        );
    }

    /**
     * Export a renderable, templatable, object or array to a stdClass context.
     *
     * @param mixed $value Value to export.
     * @return stdClass
     */
    protected function export_context($value): stdClass {
        if ($value instanceof templatable) {
            $exported = $value->export_for_template($this);
            return $this->normalise_context($exported);
        }

        if ($value instanceof stdClass) {
            return $this->normalise_context($value);
        }

        if (is_array($value)) {
            return $this->normalise_context((object)$value);
        }

        if ($value === null) {
            return new stdClass();
        }

        if ($value instanceof renderable) {
            return new stdClass();
        }

        if (is_object($value)) {
            return $this->normalise_context((object)get_object_vars($value));
        }

        return (object)[
            'value' => $value,
        ];
    }

    /**
     * Add shared base context values.
     *
     * @param stdClass $data Existing context.
     * @return stdClass
     */
    protected function with_base_context(stdClass $data): stdClass {
        $data->component = self::COMPONENT;
        $data->uckk = local_uckk_get_string_or_fallback('uckk', 'theme_uckk', 'UCKK');
        $data->uckkfullname = local_uckk_get_string_or_fallback('uckkfullname', 'theme_uckk', 'Univers-Cité King Klown');
        $data->tagline = local_uckk_get_string_or_fallback(
            'uckktagline',
            'theme_uckk',
            'Comprendre le jeu. Jouer avec lucidité. Changer les règles.'
        );
        $data->boundarynotice = local_uckk_get_boundary_notice();
        $data->internalrecognitionnotice = local_uckk_get_internal_recognition_notice();
        $data->aiwarning = local_uckk_get_ai_warning();

        return $data;
    }

    /**
     * Normalise a context object for Mustache.
     *
     * @param mixed $context Raw context.
     * @return stdClass
     */
    protected function normalise_context($context): stdClass {
        if (is_array($context)) {
            $context = (object)$context;
        }

        if (!$context instanceof stdClass) {
            $context = (object)$context;
        }

        foreach ($context as $key => $value) {
            if ($value instanceof moodle_url) {
                $context->{$key} = $value->out(false);
            }
        }

        return $context;
    }

    /**
     * Return a normalised URL string.
     *
     * @param mixed $url URL value.
     * @return string
     */
    protected function normalise_url($url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url)) {
            return $url;
        }

        return '';
    }

    /**
     * Return a safe percentage.
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
     * Format optional HTML text safely.
     *
     * @param mixed $text Raw text.
     * @param int $format Text format.
     * @return string
     */
    protected function format_optional_text($text, int $format = FORMAT_HTML): string {
        if ($text === null || trim((string)$text) === '') {
            return '';
        }

        return format_text((string)$text, $format, [
            'overflowdiv' => true,
            'noclean' => false,
        ]);
    }

    /**
     * Build a default program URL.
     *
     * @param stdClass $program Program context.
     * @return string
     */
    protected function program_url(stdClass $program): string {
        if (!empty($program->id)) {
            return (new moodle_url('/local/uckk/programs.php', ['id' => (int)$program->id]))->out(false);
        }

        if (!empty($program->shortname)) {
            return (new moodle_url('/local/uckk/programs.php', ['program' => (string)$program->shortname]))->out(false);
        }

        return (new moodle_url('/local/uckk/programs.php'))->out(false);
    }

    /**
     * Build a default pathway URL.
     *
     * @param stdClass $pathway Pathway context.
     * @return string
     */
    protected function pathway_url(stdClass $pathway): string {
        if (!empty($pathway->id)) {
            return (new moodle_url('/local/uckk/pathways.php', ['id' => (int)$pathway->id]))->out(false);
        }

        if (!empty($pathway->key)) {
            return (new moodle_url('/local/uckk/pathways.php', ['pathway' => (string)$pathway->key]))->out(false);
        }

        return (new moodle_url('/local/uckk/pathways.php'))->out(false);
    }

    /**
     * Return display label for a program type.
     *
     * @param string $type Program type.
     * @return string
     */
    protected function program_type_label(string $type): string {
        $type = local_uckk_normalise_key($type);

        $labels = [
            'tronc_commun' => 'Tronc commun',
            'baccalaureat' => 'Baccalauréat',
            'mineure' => 'Mineure',
            'certificat' => 'Certificat',
            'seminaire' => 'Séminaire',
            'laboratoire' => 'Laboratoire',
            'program' => 'Programme',
        ];

        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Return CSS class for a status.
     *
     * @param string $status Status.
     * @return string
     */
    protected function status_class(string $status): string {
        $status = local_uckk_normalise_status($status);

        $success = [
            'active',
            'validated',
            'verified',
        ];

        $warning = [
            'draft',
            'pending',
            'pending_review',
            'correction_required',
            'contested',
        ];

        $danger = [
            'rejected',
            'invalidated',
            'cancelled',
        ];

        $info = [
            'closed',
            'archived',
        ];

        if (in_array($status, $success, true)) {
            return 'badge badge-success';
        }

        if (in_array($status, $warning, true)) {
            return 'badge badge-warning';
        }

        if (in_array($status, $danger, true)) {
            return 'badge badge-danger';
        }

        if (in_array($status, $info, true)) {
            return 'badge badge-info';
        }

        return 'badge badge-secondary';
    }
}

