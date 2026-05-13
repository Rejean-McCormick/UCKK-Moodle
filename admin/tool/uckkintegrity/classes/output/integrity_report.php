<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderable integrity report.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uckkintegrity\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\integrity_policy;
use tool_uckkintegrity\local\severity;

/**
 * Integrity report renderable.
 */
class integrity_report implements renderable, templatable {
    /** @var int Default overdue threshold for active integrity cases. */
    private const DEFAULT_OVERDUE_DAYS = 14;

    /** @var int Maximum number of recent/overdue rows shown in dashboard report. */
    private const DEFAULT_LIMIT = 20;

    /** @var array Report filters. */
    private array $filters;

    /**
     * Constructor.
     *
     * @param array $filters Optional report filters.
     */
    public function __construct(array $filters = []) {
        $this->filters = $filters;
    }

    /**
     * Export report data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $context = \context_system::instance();
        require_capability('tool/uckkintegrity:view', $context);

        return (object) [
            'summary' => $this->get_summary(),
            'statuses' => $this->get_grouped_counts('status'),
            'severities' => $this->get_grouped_counts('severity'),
            'casetypes' => $this->get_grouped_counts('casetype'),
            'recentcases' => $this->get_case_rows($this->get_recent_cases()),
            'overduecases' => $this->get_case_rows($this->get_overdue_cases()),
            'hasrecentcases' => !empty($this->get_recent_cases()),
            'hasoverduecases' => !empty($this->get_overdue_cases()),
            'reporturl' => (new \moodle_url('/admin/tool/uckkintegrity/report.php'))->out(false),
            'casesurl' => (new \moodle_url('/admin/tool/uckkintegrity/index.php'))->out(false),
        ];
    }

    /**
     * Get headline summary values.
     *
     * @return array
     */
    private function get_summary(): array {
        global $DB;

        $table = integrity_case::TABLE;

        $active = [
            'opened',
            'triaged',
            'assigned',
            'under_review',
            'waiting_for_response',
            'correction_required',
            'paused',
            'reopened',
            'escalated',
        ];

        [$activesql, $activeparams] = $DB->get_in_or_equal($active, SQL_PARAMS_NAMED, 'active');

        $total = (int) $DB->count_records($table);
        $open = (int) $DB->count_records_select($table, "status {$activesql}", $activeparams);
        $closed = (int) $DB->count_records_select($table, "status NOT {$activesql}", $activeparams);
        $critical = (int) $DB->count_records($table, ['severity' => severity::CRITICAL]);
        $overdue = count($this->get_overdue_cases());

        return [
            [
                'label' => get_string('report:totalcases', 'tool_uckkintegrity'),
                'value' => $total,
            ],
            [
                'label' => get_string('report:opencases', 'tool_uckkintegrity'),
                'value' => $open,
            ],
            [
                'label' => get_string('report:closedcases', 'tool_uckkintegrity'),
                'value' => $closed,
            ],
            [
                'label' => get_string('report:criticalcases', 'tool_uckkintegrity'),
                'value' => $critical,
            ],
            [
                'label' => get_string('report:overduecases', 'tool_uckkintegrity'),
                'value' => $overdue,
            ],
        ];
    }

    /**
     * Get grouped case counts.
     *
     * @param string $field Group field.
     * @return array
     */
    private function get_grouped_counts(string $field): array {
        global $DB;

        if (!in_array($field, ['status', 'severity', 'casetype'], true)) {
            throw new \coding_exception('Unsupported integrity report grouping.');
        }

        $sql = "SELECT {$field}, COUNT(1) AS total
                  FROM {" . integrity_case::TABLE . "}
              GROUP BY {$field}
              ORDER BY {$field} ASC";

        $rows = [];
        foreach ($DB->get_records_sql($sql) as $row) {
            $value = $row->{$field};

            if ($field === 'status') {
                $label = get_string('status:' . $value, 'tool_uckkintegrity');
                $url = new \moodle_url('/admin/tool/uckkintegrity/index.php', ['status' => $value]);
            } else if ($field === 'severity') {
                $label = get_string('severity:' . $value, 'tool_uckkintegrity');
                $url = new \moodle_url('/admin/tool/uckkintegrity/index.php', ['severity' => $value]);
            } else {
                $label = get_string('type:' . $value, 'tool_uckkintegrity');
                $url = new \moodle_url('/admin/tool/uckkintegrity/index.php', ['casetype' => $value]);
            }

            $rows[] = [
                'key' => s($value),
                'label' => $label,
                'total' => (int) $row->total,
                'url' => $url->out(false),
            ];
        }

        return $rows;
    }

    /**
     * Get recent integrity cases.
     *
     * @return array
     */
    private function get_recent_cases(): array {
        return integrity_case::get_cases($this->filters, 0, self::DEFAULT_LIMIT);
    }

    /**
     * Get overdue active cases.
     *
     * @return array
     */
    private function get_overdue_cases(): array {
        global $DB;

        $overduedays = (int) get_config('tool_uckkintegrity', 'overduedays');
        if ($overduedays <= 0) {
            $overduedays = self::DEFAULT_OVERDUE_DAYS;
        }

        $cutoff = time() - ($overduedays * DAYSECS);

        $activestatuses = [
            'opened',
            'triaged',
            'assigned',
            'under_review',
            'waiting_for_response',
            'correction_required',
            'paused',
            'reopened',
            'escalated',
        ];

        [$statussql, $params] = $DB->get_in_or_equal($activestatuses, SQL_PARAMS_NAMED, 'status');
        $params['cutoff'] = $cutoff;

        $sql = "SELECT *
                  FROM {" . integrity_case::TABLE . "}
                 WHERE status {$statussql}
                   AND timemodified < :cutoff
              ORDER BY timemodified ASC, id ASC";

        return array_values($DB->get_records_sql($sql, $params, 0, self::DEFAULT_LIMIT));
    }

    /**
     * Convert case records to template rows.
     *
     * @param array $cases Case records.
     * @return array
     */
    private function get_case_rows(array $cases): array {
        $rows = [];

        foreach ($cases as $case) {
            $rows[] = [
                'id' => (int) $case->id,
                'casetype' => get_string('type:' . $case->casetype, 'tool_uckkintegrity'),
                'status' => get_string('status:' . $case->status, 'tool_uckkintegrity'),
                'severity' => get_string('severity:' . $case->severity, 'tool_uckkintegrity'),
                'subjectcomponent' => s($case->subjectcomponent),
                'subjectid' => (int) $case->subjectid,
                'summary' => shorten_text(format_text($case->summary, FORMAT_PLAIN), 140),
                'timecreated' => userdate($case->timecreated),
                'timemodified' => userdate($case->timemodified),
                'url' => (new \moodle_url('/admin/tool/uckkintegrity/case.php', [
                    'id' => $case->id,
                ]))->out(false),
            ];
        }

        return $rows;
    }
}