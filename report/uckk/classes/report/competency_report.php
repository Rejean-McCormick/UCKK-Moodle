<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * UCKK competency matrix report.
 *
 * This report reads Moodle competency records and summarizes user competency
 * ratings/proficiency where Moodle competency tracking is available.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class competency_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_COMPETENCY_MATRIX;
    }

    /**
     * Return report columns.
     *
     * The keys here must match every row returned by get_rows().
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'competency' => get_string('column:competency', 'report_uckk'),
            'idnumber' => get_string('column:id', 'report_uckk'),
            'framework' => get_string('column:category', 'report_uckk'),
            'ratings' => get_string('column:ratings', 'report_uckk'),
            'rating' => get_string('column:rating', 'report_uckk'),
            'proficient' => get_string('column:total', 'report_uckk'),
            'modified' => get_string('column:modified', 'report_uckk'),
        ];
    }

    /**
     * Return rows for the competency matrix report.
     *
     * Supported filters:
     * - competencyid
     * - userid
     * - status, mapped to user competency status when available
     * - from/to, applied to competency timemodified
     * - limit
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('competency')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->competencyid > 0) {
            $conditions[] = 'c.id = :competencyid';
            $params['competencyid'] = $filters->competencyid;
        }

        $filters->add_time_conditions('c.timemodified', $conditions, $params, 'competency');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        if ($this->table_exists('competency_usercomp')) {
            return $this->get_rows_with_user_competencies($where, $params, $filters);
        }

        return $this->get_rows_without_user_competencies($where, $params, $filters);
    }

    /**
     * Return competency rows with user competency aggregates.
     *
     * @param string $where SQL WHERE clause.
     * @param array<string,mixed> $params SQL params.
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    private function get_rows_with_user_competencies(string $where, array $params, filters $filters): array {
        global $DB;

        $userjoincondition = 'uc.competencyid = c.id';

        if ($filters->userid > 0) {
            $userjoincondition .= ' AND uc.userid = :userid';
            $params['userid'] = $filters->userid;
        }

        if ($filters->status !== '') {
            $userjoincondition .= ' AND uc.status = :usercompstatus';
            $params['usercompstatus'] = $filters->status;
        }

        $frameworkjoin = $this->table_exists('competency_framework')
            ? 'LEFT JOIN {competency_framework} cf ON cf.id = c.competencyframeworkid'
            : '';

        $frameworkfield = $this->table_exists('competency_framework')
            ? 'cf.shortname'
            : "''";

        $sql = "SELECT c.id,
                       c.shortname AS competency,
                       c.idnumber,
                       $frameworkfield AS framework,
                       COUNT(uc.id) AS ratings,
                       AVG(uc.grade) AS rating,
                       SUM(CASE WHEN uc.proficiency = 1 THEN 1 ELSE 0 END) AS proficient,
                       c.timemodified AS modified
                  FROM {competency} c
                  $frameworkjoin
             LEFT JOIN {competency_usercomp} uc ON $userjoincondition
                  $where
              GROUP BY c.id, c.shortname, c.idnumber, c.timemodified" .
              ($this->table_exists('competency_framework') ? ', cf.shortname' : '') . "
              ORDER BY c.shortname ASC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'competency' => format_string($record->competency),
                'idnumber' => $record->idnumber ?: '-',
                'framework' => $record->framework ?: '-',
                'ratings' => (int)$record->ratings,
                'rating' => $record->rating !== null ? round((float)$record->rating, 2) : '-',
                'proficient' => (int)$record->proficient,
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }

    /**
     * Return competency rows when user competency aggregates are unavailable.
     *
     * @param string $where SQL WHERE clause.
     * @param array<string,mixed> $params SQL params.
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    private function get_rows_without_user_competencies(string $where, array $params, filters $filters): array {
        global $DB;

        $frameworkjoin = $this->table_exists('competency_framework')
            ? 'LEFT JOIN {competency_framework} cf ON cf.id = c.competencyframeworkid'
            : '';

        $frameworkfield = $this->table_exists('competency_framework')
            ? 'cf.shortname'
            : "''";

        $sql = "SELECT c.id,
                       c.shortname AS competency,
                       c.idnumber,
                       $frameworkfield AS framework,
                       0 AS ratings,
                       NULL AS rating,
                       0 AS proficient,
                       c.timemodified AS modified
                  FROM {competency} c
                  $frameworkjoin
                  $where
              ORDER BY c.shortname ASC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'competency' => format_string($record->competency),
                'idnumber' => $record->idnumber ?: '-',
                'framework' => $record->framework ?: '-',
                'ratings' => 0,
                'rating' => '-',
                'proficient' => 0,
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }
}