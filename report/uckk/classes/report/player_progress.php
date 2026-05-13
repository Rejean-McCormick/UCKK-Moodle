<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\report;

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Joueur progress report.
 *
 * Uses the canonical UCKK player profile registry owned by local_uckk and
 * exposes derived institutional reporting data through report_uckk.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class player_progress extends report_source {
    /**
     * Return the canonical report key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::REPORT_PLAYER_PROGRESS;
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
            'user' => get_string('column:user', 'report_uckk'),
            'email' => get_string('column:email', 'report_uckk'),
            'pathways' => get_string('column:pathways', 'report_uckk'),
            'portfolio' => get_string('column:portfolio', 'report_uckk'),
            'integrity' => get_string('column:integrity', 'report_uckk'),
            'visibility' => get_string('column:visibility', 'report_uckk'),
            'modified' => get_string('column:modified', 'report_uckk'),
        ];
    }

    /**
     * Return Joueur progress rows.
     *
     * @param filters $filters Normalized report filters.
     * @return array<int,array<string,scalar|null>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->table_exists('local_uckk_player_profile')) {
            return $this->unavailable_row();
        }

        $conditions = ['u.deleted = 0'];
        $params = [];

        if ($filters->userid > 0) {
            $conditions[] = 'p.userid = :userid';
            $params['userid'] = $filters->userid;
        }

        if ($filters->visibility !== '') {
            $conditions[] = 'p.visibility = :visibility';
            $params['visibility'] = $filters->visibility;
        }

        $filters->add_time_conditions('p.timemodified', $conditions, $params, 'player');

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT p.id,
                       p.userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       p.activepathwayids AS pathways,
                       p.portfolioarchiveid AS portfolio,
                       p.integrityflags AS integrity,
                       p.visibility,
                       p.timemodified AS modified
                  FROM {local_uckk_player_profile} p
                  JOIN {user} u ON u.id = p.userid
                  $where
              ORDER BY p.timemodified DESC, p.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, $filters->limit);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'id' => (int)$record->id,
                'user' => fullname($record),
                'email' => $record->email,
                'pathways' => $this->format_json_or_empty($record->pathways ?? null),
                'portfolio' => !empty($record->portfolio) ? (int)$record->portfolio : '-',
                'integrity' => $this->format_json_or_empty($record->integrity ?? null),
                'visibility' => $record->visibility ?: '-',
                'modified' => $this->format_time((int)$record->modified),
            ];
        }

        return $rows;
    }

    /**
     * Convert a JSON/text field into a compact report value.
     *
     * @param string|null $value Stored JSON/text value.
     * @return string
     */
    private function format_json_or_empty(?string $value): string {
        if ($value === null || trim($value) === '') {
            return '-';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return s($value);
        }

        if (is_array($decoded)) {
            if (empty($decoded)) {
                return '-';
            }

            return s(implode(', ', array_map(static function($item): string {
                if (is_scalar($item) || $item === null) {
                    return (string)$item;
                }

                return json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }, $decoded)));
        }

        if (is_scalar($decoded) || $decoded === null) {
            return $decoded === null ? '-' : s((string)$decoded);
        }

        return s(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}