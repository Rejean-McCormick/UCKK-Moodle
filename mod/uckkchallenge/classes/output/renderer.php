<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Renderer for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\output;

use coding_exception;
use moodle_url;
use plugin_renderer_base;
use renderable;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for mod_uckkchallenge output classes.
 *
 * This renderer delegates HTML structure to Mustache templates. It must not
 * decide workflow transitions, validation, integrity outcomes, badge awards,
 * competency certification, archive export, or permission policy.
 */
final class renderer extends plugin_renderer_base {
    /**
     * Render the main challenge page.
     *
     * Template: mod_uckkchallenge/challenge_page
     *
     * @param renderable|templatable $page Renderable page object.
     * @return string HTML.
     */
    public function render_challenge_page(renderable|templatable $page): string {
        return $this->render_template_object($page, 'mod_uckkchallenge/challenge_page');
    }

    /**
     * Render a challenge header.
     *
     * Template: mod_uckkchallenge/challenge_header
     *
     * @param renderable|templatable $header Renderable header object.
     * @return string HTML.
     */
    public function render_challenge_header(renderable|templatable $header): string {
        return $this->render_template_object($header, 'mod_uckkchallenge/challenge_header');
    }

    /**
     * Render a challenge summary.
     *
     * Template: mod_uckkchallenge/challenge_summary
     *
     * @param renderable|templatable $summary Renderable summary object.
     * @return string HTML.
     */
    public function render_challenge_summary(renderable|templatable $summary): string {
        return $this->render_template_object($summary, 'mod_uckkchallenge/challenge_summary');
    }

    /**
     * Render challenge rules.
     *
     * Template: mod_uckkchallenge/rules_panel
     *
     * @param renderable|templatable $rules Renderable rules object.
     * @return string HTML.
     */
    public function render_rules_panel(renderable|templatable $rules): string {
        return $this->render_template_object($rules, 'mod_uckkchallenge/rules_panel');
    }

    /**
     * Render challenge corridors of action.
     *
     * Template: mod_uckkchallenge/corridors_panel
     *
     * @param renderable|templatable $corridors Renderable corridors object.
     * @return string HTML.
     */
    public function render_corridors_panel(renderable|templatable $corridors): string {
        return $this->render_template_object($corridors, 'mod_uckkchallenge/corridors_panel');
    }

    /**
     * Render evidence requirements.
     *
     * Template: mod_uckkchallenge/evidence_panel
     *
     * @param renderable|templatable $evidence Renderable evidence object.
     * @return string HTML.
     */
    public function render_evidence_panel(renderable|templatable $evidence): string {
        return $this->render_template_object($evidence, 'mod_uckkchallenge/evidence_panel');
    }

    /**
     * Render learner submission summary.
     *
     * Template: mod_uckkchallenge/submission_summary
     *
     * @param renderable|templatable $summary Renderable submission summary object.
     * @return string HTML.
     */
    public function render_submission_summary(renderable|templatable $summary): string {
        return $this->render_template_object($summary, 'mod_uckkchallenge/submission_summary');
    }

    /**
     * Render one submission card.
     *
     * Template: mod_uckkchallenge/submission_card
     *
     * @param renderable|templatable $submission Renderable submission card object.
     * @return string HTML.
     */
    public function render_submission_card(renderable|templatable $submission): string {
        return $this->render_template_object($submission, 'mod_uckkchallenge/submission_card');
    }

    /**
     * Render evaluation panel.
     *
     * Template: mod_uckkchallenge/evaluation_panel
     *
     * @param renderable|templatable $evaluation Renderable evaluation object.
     * @return string HTML.
     */
    public function render_evaluation_panel(renderable|templatable $evaluation): string {
        return $this->render_template_object($evaluation, 'mod_uckkchallenge/evaluation_panel');
    }

    /**
     * Render integrity panel.
     *
     * Template: mod_uckkchallenge/integrity_panel
     *
     * @param renderable|templatable $integrity Renderable integrity object.
     * @return string HTML.
     */
    public function render_integrity_panel(renderable|templatable $integrity): string {
        return $this->render_template_object($integrity, 'mod_uckkchallenge/integrity_panel');
    }

    /**
     * Render archive panel.
     *
     * Template: mod_uckkchallenge/archive_panel
     *
     * @param renderable|templatable $archive Renderable archive object.
     * @return string HTML.
     */
    public function render_archive_panel(renderable|templatable $archive): string {
        return $this->render_template_object($archive, 'mod_uckkchallenge/archive_panel');
    }

    /**
     * Render a challenge timeline panel.
     *
     * Template: mod_uckkchallenge/timeline_panel
     *
     * @param renderable|templatable $timeline Renderable timeline object.
     * @return string HTML.
     */
    public function render_timeline_panel(renderable|templatable $timeline): string {
        return $this->render_template_object($timeline, 'mod_uckkchallenge/timeline_panel');
    }

    /**
     * Render a competency/badge recognition panel.
     *
     * Template: mod_uckkchallenge/recognition_panel
     *
     * @param renderable|templatable $recognition Renderable recognition object.
     * @return string HTML.
     */
    public function render_recognition_panel(renderable|templatable $recognition): string {
        return $this->render_template_object($recognition, 'mod_uckkchallenge/recognition_panel');
    }

    /**
     * Render a status badge.
     *
     * Template: mod_uckkchallenge/status_badge
     *
     * @param renderable|templatable $badge Renderable status badge object.
     * @return string HTML.
     */
    public function render_status_badge(renderable|templatable $badge): string {
        return $this->render_template_object($badge, 'mod_uckkchallenge/status_badge');
    }

    /**
     * Render a generic action bar.
     *
     * Template: mod_uckkchallenge/action_bar
     *
     * @param renderable|templatable $actions Renderable action bar object.
     * @return string HTML.
     */
    public function render_action_bar(renderable|templatable $actions): string {
        return $this->render_template_object($actions, 'mod_uckkchallenge/action_bar');
    }

    /**
     * Render an empty state.
     *
     * Template: mod_uckkchallenge/empty_state
     *
     * @param renderable|templatable $emptystate Renderable empty state object.
     * @return string HTML.
     */
    public function render_empty_state(renderable|templatable $emptystate): string {
        return $this->render_template_object($emptystate, 'mod_uckkchallenge/empty_state');
    }

    /**
     * Render a Moodle notification with standard Bootstrap/Moodle classes.
     *
     * This helper is display-only. It does not log, validate, or change any
     * challenge state.
     *
     * @param string $message Notification text.
     * @param string $type Notification type: info, success, warning, danger.
     * @return string HTML.
     */
    public function uckk_notification(string $message, string $type = 'info'): string {
        $type = clean_param($type, PARAM_ALPHA);

        $allowed = [
            'info',
            'success',
            'warning',
            'danger',
        ];

        if (!in_array($type, $allowed, true)) {
            $type = 'info';
        }

        $data = (object)[
            'message' => $message,
            'type' => $type,
        ];

        return $this->render_from_template('mod_uckkchallenge/notification', $data);
    }

    /**
     * Render a small link button.
     *
     * This helper is useful for templates that receive pre-authorised action
     * URLs from service/controller code.
     *
     * @param moodle_url|string $url Target URL.
     * @param string $label Button label.
     * @param string $variant Button variant.
     * @param array<string, string> $attributes Extra link attributes.
     * @return string HTML.
     */
    public function action_link(
        moodle_url|string $url,
        string $label,
        string $variant = 'secondary',
        array $attributes = []
    ): string {
        $variant = clean_param($variant, PARAM_ALPHA);

        $allowedvariants = [
            'primary',
            'secondary',
            'success',
            'warning',
            'danger',
            'link',
        ];

        if (!in_array($variant, $allowedvariants, true)) {
            $variant = 'secondary';
        }

        $data = (object)[
            'url' => $url instanceof moodle_url ? $url->out(false) : (new moodle_url($url))->out(false),
            'label' => $label,
            'variant' => $variant,
            'attributes' => $this->normalise_attributes($attributes),
        ];

        return $this->render_from_template('mod_uckkchallenge/action_link', $data);
    }

    /**
     * Render an exported template object.
     *
     * @param renderable|templatable $object Renderable object.
     * @param string $template Template name.
     * @return string HTML.
     */
    private function render_template_object(renderable|templatable $object, string $template): string {
        if (!$object instanceof templatable) {
            throw new coding_exception('UCKK challenge output objects must implement templatable.');
        }

        $data = $object->export_for_template($this);
        $data = $this->normalise_template_data($data);

        return $this->render_from_template($template, $data);
    }

    /**
     * Normalise template data.
     *
     * Moodle templates expect arrays, stdClass objects, booleans, numbers, and
     * strings. This method gives renderables a small safety net without moving
     * business logic into the renderer.
     *
     * @param mixed $data Exported template data.
     * @return stdClass|array
     */
    private function normalise_template_data(mixed $data): stdClass|array {
        if ($data instanceof stdClass || is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            return (object)get_object_vars($data);
        }

        throw new coding_exception('Template data must be an array or stdClass object.');
    }

    /**
     * Convert associative HTML attributes into a template-friendly list.
     *
     * @param array<string, string> $attributes Raw attributes.
     * @return array<int, stdClass>
     */
    private function normalise_attributes(array $attributes): array {
        $normalised = [];

        foreach ($attributes as $name => $value) {
            $name = clean_param((string)$name, PARAM_ALPHANUMEXT);

            if ($name === '') {
                continue;
            }

            $normalised[] = (object)[
                'name' => $name,
                'value' => (string)$value,
            ];
        }

        return $normalised;
    }
}