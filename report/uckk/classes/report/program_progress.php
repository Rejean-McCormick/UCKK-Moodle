<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Program progress report.
 *
 * Shows UCKK programs, their Moodle category link, program type, status,
 * linked course count, and timestamps.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_progress extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_PROGRAM_PROGRESS;
    }

    /**
     * Return report columns.
     *
     * Keys must match the row keys returned by get_rows().
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'program' => get_string('column:program', 'report_uckk'),
            'category' => get_string('column:category', 'report_uckk'),
            'programtype' => get_string('column:programtype', 'report_uckk'),
            'courses' => get_string('column:courses', 'report_uckk'),
            'status' => get_string('column:status', 'report_uckk'),
            'created' => get_string('column:created', 'report_uckk'),
            'modified' => get_string('column:modified', 'report_uckk'),
        ];
    }

    /**
     * Return rows matching the selected filters.
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('local_uckk_program')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->programid > 0) {
            $conditions[] = 'p.id = :programid';
            $params['programid'] = $filters->programid;
        }

        if ($filters->categoryid > 0) {
            $conditions[] = 'p.categoryid = :categoryid';
            $params['categoryid'] = $filters->categoryid;
        }

        if ($filters->courseid > 0) {
            $conditions[] = 'c.id = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if ($filters->status !== '') {
            $conditions[] = 'p.status = :status';
            $params['status'] = $filters->status;
        }

        $filters->add_time_conditions('p.timecreated', $conditions, $params, 'program');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT p.id,
                       p.fullname AS program,
                       p.programtype,
                       p.status,
                       p.timecreated AS created,
                       p.timemodified AS modified,
                       cc.name AS category,
                       COUNT(DISTINCT c.id) AS courses
                  FROM {local_uckk_program} p
             LEFT JOIN {course_categories} cc ON cc.id = p.categoryid
             LEFT JOIN {course} c ON c.category = p.categoryid
                       $where
              GROUP BY p.id,
                       p.fullname,
                       p.programtype,
                       p.status,
                       p.timecreated,
                       p.timemodified,
                       cc.name,
                       p.sortorder
              ORDER BY p.sortorder ASC, p.fullname ASC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'program' => format_string($record->program),
                'category' => $record->category ? format_string($record->category) : '-',
                'programtype' => $record->programtype ?: '-',
                'courses' => (int)$record->courses,
                'status' => $record->status ?: '-',
                'created' => $this->format_time((int)$record->created),
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }
}