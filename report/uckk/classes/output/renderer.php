<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for UCKK institutional reports.
 *
 * Rendering stays in Moodle output classes and Mustache templates; report
 * querying and filtering stay in local/report classes.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render the main UCKK reports dashboard.
     *
     * @param report_dashboard $dashboard Dashboard renderable.
     * @return string Rendered HTML.
     */
    public function render_report_dashboard(report_dashboard $dashboard): string {
        return $this->render_from_template(
            'report_uckk/report_dashboard',
            $dashboard->export_for_template($this)
        );
    }

    /**
     * Render a single report card.
     *
     * @param report_card $card Card renderable.
     * @return string Rendered HTML.
     */
    public function render_report_card(report_card $card): string {
        return $this->render_from_template(
            'report_uckk/report_card',
            $card->export_for_template($this)
        );
    }

    /**
     * Render report filters from an already-prepared template context.
     *
     * This helper is useful for tests or future pages that need the same
     * filter partial outside the main dashboard.
     *
     * @param array<string,mixed> $context Template context.
     * @return string Rendered HTML.
     */
    public function render_report_filters(array $context): string {
        return $this->render_from_template(
            'report_uckk/report_filters',
            $context
        );
    }

    /**
     * Render a plain table for report rows.
     *
     * The dashboard template normally handles tables directly. This method
     * provides a safe reusable fallback for exports, print views, or tests.
     *
     * @param array<int,array<string,string>> $columns Column metadata.
     * @param array<int,array<string,mixed>> $rows Row data.
     * @return string Rendered HTML table.
     */
    public function render_rows_table(array $columns, array $rows): string {
        if (empty($rows)) {
            return $this->notification(
                get_string('norows', 'report_uckk'),
                \core\output\notification::NOTIFY_INFO
            );
        }

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable table report-uckk-table';
        $table->head = array_map(static function(array $column): string {
            return s($column['label'] ?? '');
        }, $columns);

        foreach ($rows as $row) {
            $cells = [];
            foreach ($columns as $column) {
                $key = $column['key'] ?? '';
                $cells[] = s((string)($row[$key] ?? ''));
            }
            $table->data[] = $cells;
        }

        return \html_writer::table($table);
    }
}