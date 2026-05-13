<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\output;

defined('MOODLE_INTERNAL') || die();

use context;
use moodle_url;
use renderable;
use renderer_base;
use report_uckk\local\filters;
use report_uckk\local\report_source;
use templatable;

/**
 * Dashboard renderable for UCKK institutional reports.
 *
 * This class prepares one coherent template context for:
 * - report cards;
 * - active report title and description;
 * - filter form values;
 * - active filter summary;
 * - report rows and columns;
 * - CSV / JSON export links;
 * - permission-aware export visibility.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class report_dashboard implements renderable, templatable {
    /** @var array<string,report_source> Registered report sources. */
    private array $sources;

    /** @var report_source Current report source. */
    private report_source $current;

    /** @var filters Current normalized filters. */
    private filters $filters;

    /** @var context Current Moodle context. */
    private context $context;

    /**
     * Constructor.
     *
     * @param array<string,report_source> $sources All report sources keyed by canonical report key.
     * @param report_source $current Selected report source.
     * @param filters $filters Current normalized filters.
     * @param context $context Moodle context.
     */
    public function __construct(array $sources, report_source $current, filters $filters, context $context) {
        $this->sources = $sources;
        $this->current = $current;
        $this->filters = $filters;
        $this->context = $context;
    }

    /**
     * Export data for report_uckk/report_dashboard.mustache.
     *
     * @param renderer_base $output Renderer.
     * @return array<string,mixed>
     */
    public function export_for_template(renderer_base $output): array {
        $current = $this->resolve_current_source();

        $cards = $this->cards_for_template($output, $current);
        $columns = $this->columns_for_template($current);
        $rows = $this->rows_for_template($current);
        $activefilters = $this->filters->active_filters_for_template();

        $baseparams = $this->filters->url_params();
        $baseparams['report'] = $current->get_key();

        $csvparams = array_merge($baseparams, [
            'format' => filters::FORMAT_CSV,
            'sesskey' => sesskey(),
        ]);

        $jsonparams = array_merge($baseparams, [
            'format' => filters::FORMAT_JSON,
            'sesskey' => sesskey(),
        ]);

        return [
            'title' => get_string('dashboard', 'report_uckk'),

            'cards' => $cards,
            'hascards' => !empty($cards),

            'currenttitle' => $current->get_title(),
            'currentdescription' => $current->get_description(),
            'reportkey' => $current->get_key(),

            'columns' => $columns,
            'rows' => $rows,
            'hasrows' => !empty($rows),
            'totalrows' => get_string('totalrows', 'report_uckk', count($rows)),

            'filters' => $this->filters_for_template($current),
            'activefilters' => $activefilters,
            'hasactivefilters' => !empty($activefilters),

            'actionurl' => (new moodle_url('/report/uckk/index.php'))->out(false),
            'clearurl' => (new moodle_url('/report/uckk/index.php', [
                'report' => $current->get_key(),
            ]))->out(false),

            'csvurl' => (new moodle_url('/report/uckk/index.php', $csvparams))->out(false),
            'jsonurl' => (new moodle_url('/report/uckk/index.php', $jsonparams))->out(false),

            'canexport' => has_capability('report/uckk:export', $this->context),
            'allowjsonexport' => (bool)get_config('report_uckk', 'allowjsonexport'),
        ];
    }

    /**
     * Resolve the current source safely.
     *
     * If the selected report requires a capability the user does not have,
     * fall back to the first visible report source.
     *
     * @return report_source
     */
    private function resolve_current_source(): report_source {
        if ($this->current->can_view($this->context)) {
            return $this->current;
        }

        foreach ($this->sources as $source) {
            if ($source->can_view($this->context)) {
                return $source;
            }
        }

        return $this->current;
    }

    /**
     * Prepare report cards for the dashboard.
     *
     * @param renderer_base $output Renderer.
     * @param report_source $current Current source.
     * @return array<int,array<string,mixed>>
     */
    private function cards_for_template(renderer_base $output, report_source $current): array {
        $cards = [];
        $showempty = (bool)get_config('report_uckk', 'showemptyreports');

        foreach ($this->sources as $source) {
            if (!$source->can_view($this->context)) {
                continue;
            }

            $card = $source->card($this->filters, $source->get_key() === $current->get_key());
            $carddata = $card->export_for_template($output);

            if ($showempty || !empty($carddata['total']) || !empty($carddata['active'])) {
                $cards[] = $carddata;
            }
        }

        return $cards;
    }

    /**
     * Prepare report columns for the table header.
     *
     * @param report_source $source Report source.
     * @return array<int,array<string,string>>
     */
    private function columns_for_template(report_source $source): array {
        $columns = [];

        foreach ($source->get_columns() as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $columns;
    }

    /**
     * Prepare rows for the report table.
     *
     * @param report_source $source Report source.
     * @return array<int,array<string,array<int,array<string,string>>>>
     */
    private function rows_for_template(report_source $source): array {
        $rows = [];
        $columns = $source->get_columns();

        foreach ($source->get_rows($this->filters) as $record) {
            $cells = [];

            foreach ($columns as $key => $label) {
                $cells[] = [
                    'key' => $key,
                    'value' => $this->format_cell_value($record[$key] ?? ''),
                ];
            }

            $rows[] = [
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    /**
     * Prepare filter values for report_uckk/report_filters.mustache.
     *
     * @param report_source $current Current source.
     * @return array<string,int|string>
     */
    private function filters_for_template(report_source $current): array {
        return [
            'report' => $current->get_key(),
            'userid' => $this->filters->userid,
            'cohortid' => $this->filters->cohortid,
            'programid' => $this->filters->programid,
            'courseid' => $this->filters->courseid,
            'categoryid' => $this->filters->categoryid,
            'competencyid' => $this->filters->competencyid,
            'badgeid' => $this->filters->badgeid,
            'status' => $this->filters->status,
            'visibility' => $this->filters->visibility,
            'challengetype' => $this->filters->challengetype,
            'assemblytype' => $this->filters->assemblytype,
            'integritytype' => $this->filters->integritytype,
            'from' => $this->filters->from,
            'to' => $this->filters->to,
            'limit' => $this->filters->limit,
        ];
    }

    /**
     * Format one table cell value for safe Mustache output.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function format_cell_value($value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? get_string('yes') : get_string('no');
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string)$value;
    }
}