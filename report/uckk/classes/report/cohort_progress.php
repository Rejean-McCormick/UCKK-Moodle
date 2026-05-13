<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Cohort progress report.
 *
 * This report is intentionally based on Moodle core cohort tables, with an
 * optional join to the UCKK player profile table when it exists.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort_progress extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_COHORT_PROGRESS;
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
            'cohort' => get_string('column:cohort', 'report_uckk'),
            'category' => get_string('column:category', 'report_uckk'),
            'members' => get_string('column:members', 'report_uckk'),
            'players' => get_string('column:players', 'report_uckk'),
            'visibility' => get_string('column:visibility', 'report_uckk'),
            'created' => get_string('column:created', 'report_uckk'),
            'modified' => get_string('column:modified', 'report_uckk'),
        ];
    }

    /**
     * Return cohort rows matching the selected filters.
     *
     * Supported aligned filters:
     * - cohortid
     * - categoryid
     * - userid
     * - visibility
     * - from / to, applied to cohort creation time
     * - limit
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->cohortid > 0) {
            $conditions[] = 'c.id = :cohortid';
            $params['cohortid'] = $filters->cohortid;
        }

        if ($filters->categoryid > 0) {
            $conditions[] = 'ctx.contextlevel = :categorycontextlevel';
            $conditions[] = 'ctx.instanceid = :categoryid';
            $params['categorycontextlevel'] = CONTEXT_COURSECAT;
            $params['categoryid'] = $filters->categoryid;
        }

        if ($filters->userid > 0) {
            $conditions[] = 'cm.userid = :userid';
            $params['userid'] = $filters->userid;
        }

        if ($filters->visibility !== '') {
            $conditions[] = 'c.visible = :visible';
            $params['visible'] = $this->visibility_to_int($filters->visibility);
        }

        $filters->add_time_conditions('c.timecreated', $conditions, $params, 'cohort');

        $where = 'WHERE ' . implode(' AND ', $conditions);
        $playerjoin = '';
        $playercount = '0';

        if ($this->table_exists('local_uckk_player_profile')) {
            $playerjoin = 'LEFT JOIN {local_uckk_player_profile} p ON p.userid = cm.userid';
            $playercount = 'COUNT(DISTINCT p.userid)';
        }

        $sql = "SELECT c.id,
                       c.name AS cohort,
                       COALESCE(cc.name, '') AS category,
                       COUNT(DISTINCT cm.userid) AS members,
                       $playercount AS players,
                       c.visible AS visibility,
                       c.timecreated AS created,
                       c.timemodified AS modified
                  FROM {cohort} c
                  JOIN {context} ctx ON ctx.id = c.contextid
             LEFT JOIN {course_categories} cc
                    ON cc.id = ctx.instanceid
                   AND ctx.contextlevel = :coursecatcontext
             LEFT JOIN {cohort_members} cm ON cm.cohortid = c.id
                       $playerjoin
                       $where
              GROUP BY c.id, c.name, cc.name, c.visible, c.timecreated, c.timemodified
              ORDER BY c.name ASC, c.id ASC";

        $params['coursecatcontext'] = CONTEXT_COURSECAT;

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'cohort' => format_string($record->cohort),
                'category' => $record->category !== '' ? format_string($record->category) : '-',
                'members' => (int)$record->members,
                'players' => (int)$record->players,
                'visibility' => !empty($record->visibility) ? get_string('yes') : get_string('no'),
                'created' => $this->format_time((int)$record->created),
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }

    /**
     * Convert a report visibility filter into Moodle cohort visible value.
     *
     * Accepted values:
     * - visible, public, 1, yes
     * - hidden, private, 0, no
     *
     * Unknown non-empty values default to visible to avoid broadening the query.
     *
     * @param string $visibility Visibility filter.
     * @return int
     */
    private function visibility_to_int(string $visibility): int {
        $visibility = strtolower(trim($visibility));

        if (in_array($visibility, ['hidden', 'private', '0', 'no', 'false'], true)) {
            return 0;
        }

        return 1;
    }
}