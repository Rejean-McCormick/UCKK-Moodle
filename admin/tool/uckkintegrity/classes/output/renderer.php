<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for UCKK integrity output components.
 *
 * Business logic stays in local service classes and renderable/exportable
 * classes. This renderer only maps renderables to Mustache templates.
 *
 * @package    tool_uckkintegrity
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render a single integrity case view.
     *
     * @param case_view $page Case view renderable.
     * @return string
     */
    public function render_case_view(case_view $page): string {
        return $this->render_from_template(
            'tool_uckkintegrity/case_view',
            $page->export_for_template($this)
        );
    }

    /**
     * Render the integrity case list.
     *
     * @param case_list $page Case list renderable.
     * @return string
     */
    public function render_case_list(case_list $page): string {
        return $this->render_from_template(
            'tool_uckkintegrity/case_list',
            $page->export_for_template($this)
        );
    }

    /**
     * Render the decision panel.
     *
     * @param decision_panel $page Decision panel renderable.
     * @return string
     */
    public function render_decision_panel(decision_panel $page): string {
        return $this->render_from_template(
            'tool_uckkintegrity/decision_panel',
            $page->export_for_template($this)
        );
    }

    /**
     * Render the integrity report.
     *
     * @param integrity_report $page Integrity report renderable.
     * @return string
     */
    public function render_integrity_report(integrity_report $page): string {
        return $this->render_from_template(
            'tool_uckkintegrity/integrity_report',
            $page->export_for_template($this)
        );
    }
}