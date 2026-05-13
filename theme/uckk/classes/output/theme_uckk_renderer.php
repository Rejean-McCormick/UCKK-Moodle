<?php
// This file is part of UCKK-Moodle - https://moodle.org/
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// UCKK-Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with UCKK-Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * UCKK theme renderer.
 *
 * This renderer centralises rendering helpers for UCKK theme templates.
 *
 * It must remain a presentation-layer renderer. It may prepare safe default
 * values for theme templates and call render_from_template(), but it must not
 * decide academic progression, challenge validation, assembly legitimacy,
 * archive validation, integrity outcomes, badge attribution, competency
 * mastery, or AI authority.
 *
 * Institutional workflow belongs to the dedicated UCKK plugins:
 * - local_uckk for the institutional registry and shared services.
 * - mod_uckkchallenge for Défis King Klown.
 * - mod_uckkassembly for Assemblées.
 * - mod_uckkarchive for Archives and Kristals.
 * - tool_uckkintegrity for Inquisiteur/integrity cases.
 * - report_uckk for institutional reports.
 * - aiprovider_uckk for governed AI provider integration.
 *
 * @package    theme_uckk
 * @copyright  2026 Momus et Bouche Cousue
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uckk\output;

use plugin_renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for UCKK theme-specific templates.
 *
 * The methods in this class accept data already prepared by the caller.
 * They only normalise the data shape, apply safe defaults for missing visual
 * labels, and delegate HTML generation to Mustache templates.
 */
class theme_uckk_renderer extends plugin_renderer_base {
    /**
     * Render the UCKK dashboard header.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function dashboard_header(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->title = $context->title ?? get_string('dashboardtitle', 'theme_uckk');
        $context->subtitle = $context->subtitle ?? get_string('dashboardsubtitle', 'theme_uckk');
        $context->campuslabel = $context->campuslabel ?? get_string('campuslabel', 'theme_uckk');
        $context->progresspercent = $this->normalise_percent($context->progresspercent ?? 0);
        $context->hasalerts = !empty($context->hasalerts);
        $context->showprogram = !empty($context->showprogram);
        $context->quicklinks = $this->normalise_list($context->quicklinks ?? []);

        return $this->render_from_template('theme_uckk/dashboard_header', $context);
    }

    /**
     * Render the UCKK course header.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function course_header(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->campuslabel = $context->campuslabel ?? get_string('campuslabel', 'theme_uckk');
        $context->coursefullname = $context->coursefullname ?? '';
        $context->coursesummary = $context->coursesummary ?? '';
        $context->coursecode = $context->coursecode ?? '';
        $context->programname = $context->programname ?? '';
        $context->progresspercent = $this->normalise_percent($context->progresspercent ?? 0);

        $context->showprogram = !empty($context->showprogram);
        $context->showcoursecode = !empty($context->showcoursecode);
        $context->showcoursestatus = !empty($context->showcoursestatus);
        $context->showcourselevel = !empty($context->showcourselevel);
        $context->showcourserole = !empty($context->showcourserole);
        $context->hasprogress = !empty($context->hasprogress);
        $context->showintegritynotice = !empty($context->showintegritynotice);
        $context->showarchive = !empty($context->showarchive);
        $context->showcompetencies = !empty($context->showcompetencies);
        $context->showbadges = !empty($context->showbadges);
        $context->showainotice = !empty($context->showainotice);

        $context->actions = $this->normalise_list($context->actions ?? []);
        $context->metadata = $this->normalise_list($context->metadata ?? []);

        return $this->render_from_template('theme_uckk/course_header', $context);
    }

    /**
     * Render a generic UCKK card.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function uckk_card(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->title = $context->title ?? '';
        $context->subtitle = $context->subtitle ?? '';
        $context->content = $context->content ?? '';
        $context->url = $context->url ?? '';
        $context->icon = $context->icon ?? '';
        $context->status = $context->status ?? '';
        $context->statusclass = $this->normalise_css_identifier($context->statusclass ?? 'default');

        $context->showurl = !empty($context->url);
        $context->showicon = !empty($context->icon);
        $context->showstatus = !empty($context->status);

        return $this->render_from_template('theme_uckk/uckk_card', $context);
    }

    /**
     * Render UCKK navigation.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function uckk_navigation(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->label = $context->label ?? get_string('uckknavigation', 'theme_uckk');
        $context->items = $this->normalise_list($context->items ?? []);

        return $this->render_from_template('theme_uckk/uckk_navigation', $context);
    }

    /**
     * Render a challenge teaser.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function challenge_teaser(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->title = $context->title ?? get_string('challenge', 'theme_uckk');
        $context->type = $context->type ?? get_string('challenge', 'theme_uckk');
        $context->typeshortname = $this->normalise_css_identifier($context->typeshortname ?? 'challenge');
        $context->description = $context->description ?? '';
        $context->status = $context->status ?? '';
        $context->statusclass = $this->normalise_css_identifier($context->statusclass ?? 'default');

        $context->showdate = !empty($context->showdate);
        $context->showproofcount = !empty($context->showproofcount);
        $context->showparticipantcount = !empty($context->showparticipantcount);
        $context->showarchive = !empty($context->showarchive);
        $context->showintegrity = !empty($context->showintegrity);
        $context->showprimaryaction = !empty($context->showprimaryaction);

        return $this->render_from_template('theme_uckk/challenge_teaser', $context);
    }

    /**
     * Render an assembly teaser.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function assembly_teaser(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->title = $context->title ?? get_string('assembly', 'theme_uckk');
        $context->type = $context->type ?? get_string('assembly', 'theme_uckk');
        $context->typeshortname = $this->normalise_css_identifier($context->typeshortname ?? 'assembly');
        $context->description = $context->description ?? '';
        $context->status = $context->status ?? '';
        $context->statusclass = $this->normalise_css_identifier($context->statusclass ?? 'default');

        $context->showdate = !empty($context->showdate);
        $context->showmotioncount = !empty($context->showmotioncount);
        $context->showdecisioncount = !empty($context->showdecisioncount);
        $context->showparticipantcount = !empty($context->showparticipantcount);
        $context->hascontestation = !empty($context->hascontestation);
        $context->archived = !empty($context->archived);
        $context->showarchive = !empty($context->showarchive);
        $context->showintegrity = !empty($context->showintegrity);
        $context->showprimaryaction = !empty($context->showprimaryaction);

        return $this->render_from_template('theme_uckk/assembly_teaser', $context);
    }

    /**
     * Render an integrity notice.
     *
     * @param array|stdClass $data Template context.
     * @return string Rendered HTML.
     */
    public function integrity_notice(array|stdClass $data = []): string {
        $context = $this->normalise_context($data);

        $context->title = $context->title ?? get_string('integrity', 'theme_uckk');
        $context->message = $context->message ?? get_string('integritynotice', 'theme_uckk');
        $context->url = $context->url ?? '';
        $context->linklabel = $context->linklabel ?? get_string('viewintegrityspace', 'theme_uckk');
        $context->noticeclass = $this->normalise_css_identifier($context->noticeclass ?? 'secondary');
        $context->showlink = !empty($context->url);

        return $this->render_from_template('theme_uckk/integrity_notice', $context);
    }

    /**
     * Convert template context data to stdClass.
     *
     * @param array|stdClass $data Raw template data.
     * @return stdClass Normalised template data.
     */
    private function normalise_context(array|stdClass $data): stdClass {
        if ($data instanceof stdClass) {
            return clone $data;
        }

        return (object) $data;
    }

    /**
     * Normalise a template list so Mustache can use section-first checks.
     *
     * @param mixed $items Raw items.
     * @return array
     */
    private function normalise_list(mixed $items): array {
        if ($items instanceof stdClass) {
            $items = (array) $items;
        }

        if (!is_array($items)) {
            return [];
        }

        $normalised = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $normalised[] = $item;
                continue;
            }

            if (is_array($item)) {
                $normalised[] = (object) $item;
            }
        }

        return $normalised;
    }

    /**
     * Keep percentage values inside the valid progress range.
     *
     * @param mixed $value Raw percentage value.
     * @return int Percentage between 0 and 100.
     */
    private function normalise_percent(mixed $value): int {
        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, min(100, (int) round((float) $value)));
    }

    /**
     * Convert arbitrary short labels to safe CSS identifier fragments.
     *
     * This is intentionally conservative because the output is used only as
     * a class suffix or data attribute value, never as business data.
     *
     * @param mixed $value Raw identifier.
     * @return string Safe identifier.
     */
    private function normalise_css_identifier(mixed $value): string {
        if (!is_scalar($value)) {
            return 'default';
        }

        $identifier = strtolower((string) $value);
        $identifier = preg_replace('/[^a-z0-9_-]+/', '-', $identifier);
        $identifier = trim($identifier, '-_');

        return $identifier !== '' ? $identifier : 'default';
    }
}