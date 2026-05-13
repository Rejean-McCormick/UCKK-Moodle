<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Output renderer for block_uckk_dashboard.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uckk_dashboard\output;

use plugin_renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the UCKK dashboard block.
 *
 * This class intentionally contains no dashboard business rules, permission
 * decisions, workflow state transitions, or data ownership logic.
 *
 * Data must be prepared by services and renderable output classes before it
 * reaches this renderer.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the complete dashboard block.
     *
     * @param dashboard_block $dashboard Dashboard renderable.
     * @return string Rendered HTML.
     */
    public function render_dashboard_block(dashboard_block $dashboard): string {
        return $this->render_uckk_template(
            'block_uckk_dashboard/dashboard_block',
            $dashboard
        );
    }

    /**
     * Render the progress summary card.
     *
     * @param progress_summary $summary Progress summary renderable.
     * @return string Rendered HTML.
     */
    public function render_progress_summary(progress_summary $summary): string {
        return $this->render_uckk_template(
            'block_uckk_dashboard/progress_summary',
            $summary
        );
    }

    /**
     * Render the challenge summary card.
     *
     * @param challenge_summary $summary Challenge summary renderable.
     * @return string Rendered HTML.
     */
    public function render_challenge_summary(challenge_summary $summary): string {
        return $this->render_uckk_template(
            'block_uckk_dashboard/challenge_summary',
            $summary
        );
    }

    /**
     * Render the assembly summary card.
     *
     * @param assembly_summary $summary Assembly summary renderable.
     * @return string Rendered HTML.
     */
    public function render_assembly_summary(assembly_summary $summary): string {
        return $this->render_uckk_template(
            'block_uckk_dashboard/assembly_summary',
            $summary
        );
    }

    /**
     * Render the badge summary card.
     *
     * @param badge_summary $summary Badge summary renderable.
     * @return string Rendered HTML.
     */
    public function render_badge_summary(badge_summary $summary): string {
        return $this->render_uckk_template(
            'block_uckk_dashboard/badge_summary',
            $summary
        );
    }

    /**
     * Render a UCKK dashboard template from a templatable output object.
     *
     * @param string $templatename Full Moodle template name.
     * @param templatable $output Output object.
     * @return string Rendered HTML.
     */
    protected function render_uckk_template(string $templatename, templatable $output): string {
        $data = $output->export_for_template($this);

        return parent::render_from_template($templatename, $data);
    }
}