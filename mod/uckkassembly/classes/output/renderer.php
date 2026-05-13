<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Output renderer for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\output;

use plugin_renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for mod_uckkassembly output classes.
 *
 * This renderer delegates display work to Mustache templates. It must not
 * publish decisions, count votes authoritatively, resolve contestations,
 * validate integrity, archive records, or decide visibility.
 */
final class renderer extends plugin_renderer_base {
    /**
     * Render the main Assembly view.
     *
     * Template:
     * mod_uckkassembly/templates/assembly_view.mustache
     *
     * @param templatable $page Assembly view output object.
     * @return string Rendered HTML.
     */
    public function render_assembly_view(templatable $page): string {
        return $this->render_template_object('mod_uckkassembly/assembly_view', $page);
    }

    /**
     * Render an Assembly summary card.
     *
     * Template:
     * mod_uckkassembly/templates/assembly_summary.mustache
     *
     * @param templatable $summary Assembly summary output object.
     * @return string Rendered HTML.
     */
    public function render_assembly_summary(templatable $summary): string {
        return $this->render_template_object('mod_uckkassembly/assembly_summary', $summary);
    }

    /**
     * Render the Assembly status badge.
     *
     * Template:
     * mod_uckkassembly/templates/status_badge.mustache
     *
     * @param templatable $badge Status badge output object.
     * @return string Rendered HTML.
     */
    public function render_status_badge(templatable $badge): string {
        return $this->render_template_object('mod_uckkassembly/status_badge', $badge);
    }

    /**
     * Render the Assembly participant panel.
     *
     * Template:
     * mod_uckkassembly/templates/participant_panel.mustache
     *
     * @param templatable $panel Participant panel output object.
     * @return string Rendered HTML.
     */
    public function render_participant_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/participant_panel', $panel);
    }

    /**
     * Render the Assembly agenda panel.
     *
     * Template:
     * mod_uckkassembly/templates/agenda_panel.mustache
     *
     * @param templatable $panel Agenda panel output object.
     * @return string Rendered HTML.
     */
    public function render_agenda_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/agenda_panel', $panel);
    }

    /**
     * Render one motion card.
     *
     * Template:
     * mod_uckkassembly/templates/motion_card.mustache
     *
     * @param templatable $motion Motion card output object.
     * @return string Rendered HTML.
     */
    public function render_motion_card(templatable $motion): string {
        return $this->render_template_object('mod_uckkassembly/motion_card', $motion);
    }

    /**
     * Render a motion list.
     *
     * Template:
     * mod_uckkassembly/templates/motion_list.mustache
     *
     * @param templatable $list Motion list output object.
     * @return string Rendered HTML.
     */
    public function render_motion_list(templatable $list): string {
        return $this->render_template_object('mod_uckkassembly/motion_list', $list);
    }

    /**
     * Render an amendment panel.
     *
     * Template:
     * mod_uckkassembly/templates/amendment_panel.mustache
     *
     * @param templatable $panel Amendment panel output object.
     * @return string Rendered HTML.
     */
    public function render_amendment_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/amendment_panel', $panel);
    }

    /**
     * Render an argument list.
     *
     * Template:
     * mod_uckkassembly/templates/argument_list.mustache
     *
     * @param templatable $list Argument list output object.
     * @return string Rendered HTML.
     */
    public function render_argument_list(templatable $list): string {
        return $this->render_template_object('mod_uckkassembly/argument_list', $list);
    }

    /**
     * Render an objection panel.
     *
     * Template:
     * mod_uckkassembly/templates/objection_panel.mustache
     *
     * @param templatable $panel Objection panel output object.
     * @return string Rendered HTML.
     */
    public function render_objection_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/objection_panel', $panel);
    }

    /**
     * Render the vote/readings panel.
     *
     * Template:
     * mod_uckkassembly/templates/vote_panel.mustache
     *
     * @param templatable $panel Vote panel output object.
     * @return string Rendered HTML.
     */
    public function render_vote_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/vote_panel', $panel);
    }

    /**
     * Render a Smart Vote / multiple-readings panel.
     *
     * Template:
     * mod_uckkassembly/templates/reading_panel.mustache
     *
     * @param templatable $panel Reading panel output object.
     * @return string Rendered HTML.
     */
    public function render_reading_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/reading_panel', $panel);
    }

    /**
     * Render a decision panel.
     *
     * Template:
     * mod_uckkassembly/templates/decision_panel.mustache
     *
     * @param templatable $panel Decision panel output object.
     * @return string Rendered HTML.
     */
    public function render_decision_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/decision_panel', $panel);
    }

    /**
     * Render a minutes panel.
     *
     * Template:
     * mod_uckkassembly/templates/minutes_panel.mustache
     *
     * @param templatable $panel Minutes panel output object.
     * @return string Rendered HTML.
     */
    public function render_minutes_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/minutes_panel', $panel);
    }

    /**
     * Render a minority report panel.
     *
     * Template:
     * mod_uckkassembly/templates/minority_report_panel.mustache
     *
     * @param templatable $panel Minority report panel output object.
     * @return string Rendered HTML.
     */
    public function render_minority_report_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/minority_report_panel', $panel);
    }

    /**
     * Render a contestation panel.
     *
     * Template:
     * mod_uckkassembly/templates/contestation_panel.mustache
     *
     * @param templatable $panel Contestation panel output object.
     * @return string Rendered HTML.
     */
    public function render_contestation_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/contestation_panel', $panel);
    }

    /**
     * Render an integrity panel.
     *
     * Template:
     * mod_uckkassembly/templates/integrity_panel.mustache
     *
     * @param templatable $panel Integrity panel output object.
     * @return string Rendered HTML.
     */
    public function render_integrity_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/integrity_panel', $panel);
    }

    /**
     * Render an archive panel.
     *
     * Template:
     * mod_uckkassembly/templates/archive_panel.mustache
     *
     * @param templatable $panel Archive panel output object.
     * @return string Rendered HTML.
     */
    public function render_archive_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/archive_panel', $panel);
    }

    /**
     * Render a timeline panel.
     *
     * Template:
     * mod_uckkassembly/templates/timeline_panel.mustache
     *
     * @param templatable $panel Timeline panel output object.
     * @return string Rendered HTML.
     */
    public function render_timeline_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/timeline_panel', $panel);
    }

    /**
     * Render an action bar.
     *
     * Template:
     * mod_uckkassembly/templates/action_bar.mustache
     *
     * @param templatable $bar Action bar output object.
     * @return string Rendered HTML.
     */
    public function render_action_bar(templatable $bar): string {
        return $this->render_template_object('mod_uckkassembly/action_bar', $bar);
    }

    /**
     * Render an empty state.
     *
     * Template:
     * mod_uckkassembly/templates/empty_state.mustache
     *
     * @param templatable $state Empty state output object.
     * @return string Rendered HTML.
     */
    public function render_empty_state(templatable $state): string {
        return $this->render_template_object('mod_uckkassembly/empty_state', $state);
    }

    /**
     * Render a generic notification panel.
     *
     * Template:
     * mod_uckkassembly/templates/notice_panel.mustache
     *
     * @param templatable $panel Notice panel output object.
     * @return string Rendered HTML.
     */
    public function render_notice_panel(templatable $panel): string {
        return $this->render_template_object('mod_uckkassembly/notice_panel', $panel);
    }

    /**
     * Export a templatable object and render a Mustache template.
     *
     * @param string $templatename Moodle template name.
     * @param templatable $outputobject Output object.
     * @return string Rendered HTML.
     */
    private function render_template_object(string $templatename, templatable $outputobject): string {
        $data = $outputobject->export_for_template($this);

        return $this->render_from_template($templatename, $data);
    }
}