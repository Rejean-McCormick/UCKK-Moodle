<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Assembly decisions report.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assembly_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_ASSEMBLY_DECISIONS;
    }

    /**
     * Return report columns.
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'assembly' => get_string('column:assembly', 'report_uckk'),
            'course' => get_string('column:course', 'report_uckk'),
            'assemblytype' => get_string('column:assemblytype', 'report_uckk'),
            'status' => get_string('column:status', 'report_uckk'),
            'motions' => get_string('column:motions', 'report_uckk'),
            'decisions' => get_string('column:decisions', 'report_uckk'),
        ];
    }

    /**
     * Return report rows.
     *
     * @param filters $filters Active report filters.
     * @return array<int,array<string,mixed>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('uckkassembly')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->courseid > 0) {
            $conditions[] = 'a.course = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if ($filters->status !== '') {
            $conditions[] = 'a.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->assemblytype !== '') {
            $conditions[] = 'a.assemblytype = :assemblytype';
            $params['assemblytype'] = $filters->assemblytype;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $hasmotions = $this->table_exists('uckkassembly_motion');
        $hasdecisions = $this->table_exists('uckkassembly_decision');

        $motionjoin = $hasmotions
            ? 'LEFT JOIN {uckkassembly_motion} m ON m.assemblyid = a.id'
            : '';

        $decisionjoin = $hasdecisions
            ? 'LEFT JOIN {uckkassembly_decision} d ON d.assemblyid = a.id'
            : '';

        $motioncount = $hasmotions ? 'COUNT(DISTINCT m.id)' : '0';
        $decisioncount = $hasdecisions ? 'COUNT(DISTINCT d.id)' : '0';

        $sql = "SELECT a.id,
                       a.name AS assembly,
                       c.fullname AS course,
                       a.assemblytype,
                       a.status,
                       {$motioncount} AS motions,
                       {$decisioncount} AS decisions
                  FROM {uckkassembly} a
                  JOIN {course} c ON c.id = a.course
                  {$motionjoin}
                  {$decisionjoin}
                  {$where}
              GROUP BY a.id, a.name, c.fullname, a.assemblytype, a.status
              ORDER BY a.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        return $this->records_to_rows($records);
    }
}