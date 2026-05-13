<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Integrity cases report.
 *
 * Reports Inquisiteur cases from tool_uckkintegrity without owning or
 * duplicating integrity records.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class integrity_report extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_INTEGRITY_CASES;
    }

    /**
     * Restricted integrity reporting requires the broader report capability.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return 'report/uckk:viewall';
    }

    /**
     * Return report columns.
     *
     * @return array<string,string>
     */
    public function get_columns(): array {
        return [
            'id' => get_string('column:id', 'report_uckk'),
            'casetype' => get_string('column:casetype', 'report_uckk'),
            'severity' => get_string('column:severity', 'report_uckk'),
            'status' => get_string('column:status', 'report_uckk'),
            'openedby' => get_string('column:openedby', 'report_uckk'),
            'assignedto' => get_string('column:assignedto', 'report_uckk'),
            'archive' => get_string('column:archive', 'report_uckk'),
            'created' => get_string('column:created', 'report_uckk'),
            'modified' => get_string('column:modified', 'report_uckk'),
        ];
    }

    /**
     * Return rows matching normalized report filters.
     *
     * Supported aligned filters:
     * - integritytype maps to tool_uckkintegrity_case.casetype
     * - status maps to tool_uckkintegrity_case.status
     * - visibility maps to tool_uckkintegrity_case.visibility when present
     * - userid matches openedby or assignedto
     * - from/to filter timecreated
     *
     * @param filters $filters Normalized dashboard filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('tool_uckkintegrity_case')) {
            return $this->unavailable_row();
        }

        $conditions = ['1 = 1'];
        $params = [];

        if ($filters->integritytype !== '') {
            $conditions[] = 'ic.casetype = :integritytype';
            $params['integritytype'] = $filters->integritytype;
        }

        if ($filters->status !== '') {
            $conditions[] = 'ic.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->visibility !== '') {
            $conditions[] = 'ic.visibility = :visibility';
            $params['visibility'] = $filters->visibility;
        }

        if ($filters->userid > 0) {
            $conditions[] = '(ic.openedby = :openedbyuserid OR ic.assignedto = :assignedtouserid)';
            $params['openedbyuserid'] = $filters->userid;
            $params['assignedtouserid'] = $filters->userid;
        }

        $filters->add_time_conditions('ic.timecreated', $conditions, $params, 'integrity');

        $openedbyname = $DB->sql_fullname('ou.firstname', 'ou.lastname');
        $assignedtoname = $DB->sql_fullname('au.firstname', 'au.lastname');

        $sql = "SELECT ic.id,
                       ic.casetype,
                       ic.severity,
                       ic.status,
                       ic.archiveitemid AS archive,
                       ic.timecreated AS created,
                       ic.timemodified AS modified,
                       $openedbyname AS openedbyname,
                       $assignedtoname AS assignedtoname
                  FROM {tool_uckkintegrity_case} ic
             LEFT JOIN {user} ou ON ou.id = ic.openedby
             LEFT JOIN {user} au ON au.id = ic.assignedto
                 WHERE " . implode(' AND ', $conditions) . "
              ORDER BY ic.timecreated DESC, ic.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => $record->id,
                'casetype' => self::localized_integrity_value('type', $record->casetype),
                'severity' => self::localized_integrity_value('severity', $record->severity),
                'status' => self::localized_integrity_value('status', $record->status),
                'openedby' => self::empty_to_dash($record->openedbyname ?? ''),
                'assignedto' => self::empty_to_dash($record->assignedtoname ?? ''),
                'archive' => !empty($record->archive) ? $record->archive : '-',
                'created' => $this->format_time((int)$record->created),
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }

    /**
     * Localize tool_uckkintegrity enum values when the owning plugin string exists.
     *
     * @param string $prefix String prefix, for example status, severity, or type.
     * @param string|null $value Raw enum value.
     * @return string
     */
    private static function localized_integrity_value(string $prefix, ?string $value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        $stringkey = $prefix . ':' . $value;
        if (get_string_manager()->string_exists($stringkey, 'tool_uckkintegrity')) {
            return get_string($stringkey, 'tool_uckkintegrity');
        }

        return s($value);
    }

    /**
     * Normalize empty strings for report display/export.
     *
     * @param string|null $value Value.
     * @return string
     */
    private static function empty_to_dash(?string $value): string {
        $value = trim((string)$value);

        return $value === '' ? '-' : $value;
    }
}