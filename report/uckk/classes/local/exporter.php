<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\local;

defined('MOODLE_INTERNAL') || die();

use context;
use moodle_exception;

/**
 * Report exporter for report_uckk.
 *
 * This class uses the same canonical report source and normalized filter
 * objects as the HTML dashboard, so exported rows stay aligned with the
 * rendered report.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class exporter {
    /** @var report_source Report source being exported. */
    private report_source $source;

    /** @var filters Active normalized filters. */
    private filters $filters;

    /** @var context Export context. */
    private context $context;

    /**
     * Constructor.
     *
     * @param report_source $source Report source.
     * @param filters $filters Active filters.
     * @param context $context Moodle context.
     */
    public function __construct(report_source $source, filters $filters, context $context) {
        $this->source = $source;
        $this->filters = $filters;
        $this->context = $context;
    }

    /**
     * Send the selected export format.
     *
     * @return void
     */
    public function send(): void {
        require_capability('report/uckk:export', $this->context);

        if ($this->filters->format === filters::FORMAT_JSON && !get_config('report_uckk', 'allowjsonexport')) {
            throw new moodle_exception('nopermissions', 'error', '', get_string('exportjson', 'report_uckk'));
        }

        $this->log_export();

        if ($this->filters->format === filters::FORMAT_JSON) {
            $this->send_json();
        }

        if ($this->filters->format === filters::FORMAT_CSV) {
            $this->send_csv();
        }

        throw new moodle_exception('invalidexportformat', 'report_uckk');
    }

    /**
     * Send CSV export.
     *
     * @return void
     */
    private function send_csv(): void {
        $filename = $this->filename('csv');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $handle = fopen('php://output', 'w');

        // UTF-8 BOM improves Excel compatibility without changing Moodle data.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, array_values($this->source->get_columns()));

        foreach ($this->source->get_rows($this->filters) as $row) {
            fputcsv($handle, $this->ordered_row_values($row));
        }

        fclose($handle);
        exit;
    }

    /**
     * Send JSON export.
     *
     * @return void
     */
    private function send_json(): void {
        global $USER;

        $payload = [
            'component' => 'report_uckk',
            'report' => $this->source->get_key(),
            'title' => $this->source->get_title(),
            'generatedat' => time(),
            'generatedby' => [
                'id' => $USER->id,
                'fullname' => fullname($USER),
            ],
            'contextid' => $this->context->id,
            'columns' => $this->source->get_columns(),
            'filters' => $this->filters->url_params(),
            'rows' => $this->source->get_rows($this->filters),
        ];

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->filename('json') . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Return row values in the same order as get_columns().
     *
     * @param array<string,scalar|null> $row Report row.
     * @return array<int,string>
     */
    private function ordered_row_values(array $row): array {
        $values = [];

        foreach (array_keys($this->source->get_columns()) as $columnkey) {
            $value = $row[$columnkey] ?? '';

            if ($value === null) {
                $value = '';
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $values[] = (string)$value;
        }

        return $values;
    }

    /**
     * Build a stable export filename.
     *
     * @param string $extension File extension.
     * @return string
     */
    private function filename(string $extension): string {
        $reportkey = clean_param($this->source->get_key(), PARAM_FILE);
        $timestamp = gmdate('Ymd-His');

        return 'uckk-' . $reportkey . '-' . $timestamp . '.' . $extension;
    }

    /**
     * Log the export action.
     *
     * The preferred implementation uses report_uckk\event\report_exported when
     * present. The class_exists guard keeps this exporter usable even before the
     * optional event file is added.
     *
     * @return void
     */
    private function log_export(): void {
        if (!class_exists('\\report_uckk\\event\\report_exported')) {
            debugging('report_uckk export logging event class is missing.', DEBUG_DEVELOPER);
            return;
        }

        $event = \report_uckk\event\report_exported::create([
            'context' => $this->context,
            'other' => [
                'report' => $this->source->get_key(),
                'format' => $this->filters->format,
                'filters' => $this->filters->url_params(),
            ],
        ]);

        $event->trigger();
    }
}