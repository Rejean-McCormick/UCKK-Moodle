<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Archive production report.
 *
 * Reports archive items by type, provenance source, validation state,
 * visibility and creation time.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class archive_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_ARCHIVE_PRODUCTION;
    }

    /**
     * Return report columns.
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'archiveitem' => get_string('column:archiveitem', 'report_uckk'),
            'itemtype' => get_string('column:itemtype', 'report_uckk'),
            'sourcecomponent' => get_string('column:sourcecomponent', 'report_uckk'),
            'validationstate' => get_string('column:validationstate', 'report_uckk'),
            'visibility' => get_string('column:visibility', 'report_uckk'),
            'created' => get_string('column:created', 'report_uckk'),
        ];
    }

    /**
     * Return archive report rows.
     *
     * Supported filters:
     * - userid: archive owner user id.
     * - status: archive validation state.
     * - visibility: archive item visibility.
     * - from/to: creation timestamp range.
     * - limit: maximum rows.
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('uckkarchive_item')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->userid > 0) {
            $conditions[] = 'i.owneruserid = :archiveuserid';
            $params['archiveuserid'] = $filters->userid;
        }

        if ($filters->status !== '') {
            $conditions[] = 'i.validationstate = :archivevalidationstate';
            $params['archivevalidationstate'] = $filters->status;
        }

        if ($filters->visibility !== '') {
            $conditions[] = 'i.visibility = :archivevisibility';
            $params['archivevisibility'] = $filters->visibility;
        }

        $filters->add_time_conditions('i.timecreated', $conditions, $params, 'archive');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT i.id,
                       i.title AS archiveitem,
                       i.itemtype,
                       i.sourcecomponent,
                       i.validationstate,
                       i.visibility,
                       i.timecreated AS created
                  FROM {uckkarchive_item} i
                 $where
              ORDER BY i.timecreated DESC, i.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'archiveitem' => format_string($record->archiveitem),
                'itemtype' => $record->itemtype,
                'sourcecomponent' => $record->sourcecomponent,
                'validationstate' => $record->validationstate,
                'visibility' => $record->visibility,
                'created' => $this->format_time((int)$record->created),
            ];
        }

        return $rows;
    }
}