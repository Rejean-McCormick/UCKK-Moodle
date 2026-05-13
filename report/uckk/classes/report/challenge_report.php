<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Challenge status report.
 *
 * Reports Défi activity status, submission counts, validation counts,
 * contested submissions, integrity case links, and archive output.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_CHALLENGE_STATUS;
    }

    /**
     * Return report columns.
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'challenge' => get_string('column:challenge', 'report_uckk'),
            'course' => get_string('column:course', 'report_uckk'),
            'challengetype' => get_string('column:challengetype', 'report_uckk'),
            'status' => get_string('column:status', 'report_uckk'),
            'submissions' => get_string('column:submissions', 'report_uckk'),
            'validated' => get_string('column:validated', 'report_uckk'),
            'contested' => get_string('column:contested', 'report_uckk'),
            'integritycases' => get_string('column:integrity', 'report_uckk'),
            'archiveitems' => get_string('column:archive', 'report_uckk'),
        ];
    }

    /**
     * Return rows matching the selected filters.
     *
     * @param filters $filters Normalized dashboard/export filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('uckkchallenge')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->courseid > 0) {
            $conditions[] = 'ch.course = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if ($filters->status !== '') {
            $conditions[] = 'ch.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->challengetype !== '') {
            $conditions[] = 'ch.challengetype = :challengetype';
            $params['challengetype'] = $filters->challengetype;
        }

        $filters->add_time_conditions('ch.timeopen', $conditions, $params, 'challenge');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        if ($this->table_exists('uckkchallenge_submission')) {
            $sql = "SELECT ch.id,
                           ch.name AS challenge,
                           c.fullname AS course,
                           ch.challengetype,
                           ch.status,
                           COUNT(DISTINCT s.id) AS submissions,
                           COALESCE(SUM(CASE WHEN s.status = 'validated' THEN 1 ELSE 0 END), 0) AS validated,
                           COALESCE(SUM(CASE WHEN s.status = 'contested' THEN 1 ELSE 0 END), 0) AS contested,
                           COALESCE(COUNT(DISTINCT s.integritycaseid), 0) AS integritycases,
                           COALESCE(COUNT(DISTINCT s.archiveitemid), 0) AS archiveitems
                      FROM {uckkchallenge} ch
                      JOIN {course} c ON c.id = ch.course
                 LEFT JOIN {uckkchallenge_submission} s ON s.challengeid = ch.id
                      $where
                  GROUP BY ch.id, ch.name, c.fullname, ch.challengetype, ch.status
                  ORDER BY ch.id DESC";
        } else {
            $sql = "SELECT ch.id,
                           ch.name AS challenge,
                           c.fullname AS course,
                           ch.challengetype,
                           ch.status,
                           0 AS submissions,
                           0 AS validated,
                           0 AS contested,
                           0 AS integritycases,
                           0 AS archiveitems
                      FROM {uckkchallenge} ch
                      JOIN {course} c ON c.id = ch.course
                      $where
                  ORDER BY ch.id DESC";
        }

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'challenge' => format_string($record->challenge),
                'course' => format_string($record->course),
                'challengetype' => $record->challengetype ?: '-',
                'status' => $record->status ?: '-',
                'submissions' => (int)$record->submissions,
                'validated' => (int)$record->validated,
                'contested' => (int)$record->contested,
                'integritycases' => (int)$record->integritycases,
                'archiveitems' => (int)$record->archiveitems,
            ];
        }

        return $rows;
    }
}