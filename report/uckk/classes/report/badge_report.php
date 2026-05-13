<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Badge awards report.
 *
 * Reports Moodle badge award activity for the UCKK institutional dashboard.
 *
 * Canonical report key:
 * - badge_awards
 *
 * Supported filters:
 * - badgeid
 * - userid
 * - courseid
 * - status
 * - from / to, applied to badge issue date
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class badge_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_BADGE_AWARDS;
    }

    /**
     * Return report columns.
     *
     * Column keys must match the row keys returned by get_rows().
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'badge' => get_string('column:badge', 'report_uckk'),
            'course' => get_string('column:course', 'report_uckk'),
            'issued' => get_string('column:issued', 'report_uckk'),
            'lastissued' => get_string('column:lastissued', 'report_uckk'),
            'status' => get_string('column:status', 'report_uckk'),
        ];
    }

    /**
     * Return badge award rows matching the selected filters.
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('badge') || !$this->table_exists('badge_issued')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->badgeid > 0) {
            $conditions[] = 'b.id = :badgeid';
            $params['badgeid'] = $filters->badgeid;
        }

        if ($filters->userid > 0) {
            $conditions[] = 'bi.userid = :userid';
            $params['userid'] = $filters->userid;
        }

        if ($filters->courseid > 0) {
            $conditions[] = 'b.courseid = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if ($filters->status !== '') {
            $conditions[] = 'b.status = :status';
            $params['status'] = $filters->status;
        }

        $filters->add_time_conditions('bi.dateissued', $conditions, $params, 'badge');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT b.id,
                       b.name AS badge,
                       c.fullname AS course,
                       b.status,
                       COUNT(bi.id) AS issued,
                       MAX(bi.dateissued) AS lastissued
                  FROM {badge} b
             LEFT JOIN {course} c ON c.id = b.courseid
             LEFT JOIN {badge_issued} bi ON bi.badgeid = b.id
                  $where
              GROUP BY b.id, b.name, c.fullname, b.status
              ORDER BY b.name ASC, b.id ASC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'badge' => format_string($record->badge),
                'course' => $record->course ? format_string($record->course) : get_string('site'),
                'issued' => (int)$record->issued,
                'lastissued' => !empty($record->lastissued) ? $this->format_time((int)$record->lastissued) : '-',
                'status' => $this->format_badge_status((int)$record->status),
            ];
        }

        return $rows;
    }

    /**
     * Convert Moodle badge status integers to readable report text.
     *
     * @param int $status Badge status.
     * @return string
     */
    private function format_badge_status(int $status): string {
        $statusmap = [
            BADGE_STATUS_INACTIVE => get_string('inactive', 'badges'),
            BADGE_STATUS_ACTIVE => get_string('active', 'badges'),
            BADGE_STATUS_INACTIVE_LOCKED => get_string('inactive', 'badges'),
            BADGE_STATUS_ACTIVE_LOCKED => get_string('active', 'badges'),
            BADGE_STATUS_ARCHIVED => get_string('archived', 'badges'),
        ];

        return $statusmap[$status] ?? (string)$status;
    }
}