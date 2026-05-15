<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace report_uckk\local;

use context;
use dml_exception;
use moodle_url;
use stdClass;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry and lightweight standalone query adapter for UCKK reports.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class report_source {
    /** @var string */
    public const COMPONENT = 'report_uckk';

    /** @var string */
    public const REPORT_PLAYER_PROGRESS = 'player_progress';
    /** @var string */
    public const REPORT_COHORT_PROGRESS = 'cohort_progress';
    /** @var string */
    public const REPORT_PROGRAM_PROGRESS = 'program_progress';
    /** @var string */
    public const REPORT_COMPETENCY_MATRIX = 'competency_matrix';
    /** @var string */
    public const REPORT_BADGE_AWARDS = 'badge_awards';
    /** @var string */
    public const REPORT_CHALLENGE_STATUS = 'challenge_status';
    /** @var string */
    public const REPORT_ASSEMBLY_DECISIONS = 'assembly_decisions';
    /** @var string */
    public const REPORT_ARCHIVE_PRODUCTION = 'archive_production';
    /** @var string */
    public const REPORT_INTEGRITY_CASES = 'integrity_cases';

    /** @var string */
    public const DEFAULT_REPORT = self::REPORT_PLAYER_PROGRESS;

    /** @var string */
    private string $key;
    /** @var string */
    private string $tablename;
    /** @var array<int, array<string, string>> */
    private array $columns;
    /** @var string */
    private string $viewcapability;

    /**
     * Constructor.
     *
     * @param string $key
     * @param string $tablename
     * @param array<int, array<string, string>> $columns
     * @param string $viewcapability
     */
    public function __construct(string $key, string $tablename, array $columns, string $viewcapability = 'report/uckk:view') {
        $this->key = $key;
        $this->tablename = $tablename;
        $this->columns = $columns;
        $this->viewcapability = $viewcapability;
    }

    /**
     * Return all canonical report sources.
     *
     * @return array<string, self>
     */
    public static function all(): array {
        static $sources = null;
        if ($sources !== null) {
            return $sources;
        }

        $sources = [
            self::REPORT_PLAYER_PROGRESS => new self(self::REPORT_PLAYER_PROGRESS, 'local_uckk_pathway', [
                self::column('userid', 'column:user', 'User'),
                self::column('programid', 'column:program', 'Program'),
                self::column('status', 'column:status', 'Status'),
                self::column('visibility', 'column:visibility', 'Visibility'),
            ]),
            self::REPORT_COHORT_PROGRESS => new self(self::REPORT_COHORT_PROGRESS, 'cohort_members', [
                self::column('cohortid', 'column:cohort', 'Cohort'),
                self::column('userid', 'column:user', 'User'),
                self::column('timeadded', 'column:created', 'Created'),
            ]),
            self::REPORT_PROGRAM_PROGRESS => new self(self::REPORT_PROGRAM_PROGRESS, 'local_uckk_program', [
                self::column('id', 'column:id', 'ID'),
                self::column('name', 'column:name', 'Name'),
                self::column('status', 'column:status', 'Status'),
            ]),
            self::REPORT_COMPETENCY_MATRIX => new self(self::REPORT_COMPETENCY_MATRIX, 'competency', [
                self::column('id', 'column:id', 'ID'),
                self::column('shortname', 'column:competency', 'Competency'),
                self::column('idnumber', 'column:status', 'Status'),
            ]),
            self::REPORT_BADGE_AWARDS => new self(self::REPORT_BADGE_AWARDS, 'badge_issued', [
                self::column('userid', 'column:user', 'User'),
                self::column('badgeid', 'column:badge', 'Badge'),
                self::column('dateissued', 'column:issued', 'Issued'),
            ]),
            self::REPORT_CHALLENGE_STATUS => new self(self::REPORT_CHALLENGE_STATUS, 'uckkchallenge', [
                self::column('id', 'column:id', 'ID'),
                self::column('name', 'column:challenge', 'Challenge'),
                self::column('type', 'column:challengetype', 'Challenge type'),
                self::column('status', 'column:status', 'Status'),
            ]),
            self::REPORT_ASSEMBLY_DECISIONS => new self(self::REPORT_ASSEMBLY_DECISIONS, 'uckkassembly', [
                self::column('id', 'column:id', 'ID'),
                self::column('name', 'column:assembly', 'Assembly'),
                self::column('type', 'column:assemblytype', 'Assembly type'),
                self::column('status', 'column:status', 'Status'),
            ]),
            self::REPORT_ARCHIVE_PRODUCTION => new self(self::REPORT_ARCHIVE_PRODUCTION, 'uckkarchive_item', [
                self::column('id', 'column:id', 'ID'),
                self::column('title', 'column:archiveitem', 'Archive item'),
                self::column('visibility', 'column:visibility', 'Visibility'),
                self::column('validationstate', 'column:validationstate', 'Validation state'),
            ]),
            self::REPORT_INTEGRITY_CASES => new self(self::REPORT_INTEGRITY_CASES, 'tool_uckkintegrity_case', [
                self::column('id', 'column:id', 'ID'),
                self::column('type', 'column:casetype', 'Case type'),
                self::column('severity', 'column:severity', 'Severity'),
                self::column('status', 'column:status', 'Status'),
            ]),
        ];

        return $sources;
    }

    /**
     * Check whether a report key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool {
        return array_key_exists($key, self::all());
    }

    /**
     * Get one report source or the default source.
     *
     * @param string $key
     * @return self
     */
    public static function get(string $key): self {
        $all = self::all();
        return $all[$key] ?? $all[self::DEFAULT_REPORT];
    }

    /**
     * Get report key.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Get source table name.
     *
     * @return string
     */
    public function get_tablename(): string {
        return $this->tablename;
    }

    /**
     * Get column metadata.
     *
     * @return array<int, array<string, string>>
     */
    public function get_columns(): array {
        return $this->columns;
    }

    /**
     * Get report title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->lang('report:' . $this->key, ucfirst(str_replace('_', ' ', $this->key)));
    }

    /**
     * Get report description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->lang('reportdesc:' . $this->key, $this->lang('summary', 'Summary'));
    }

    /**
     * Check whether the viewer can access this source.
     *
     * @param context $context
     * @return bool
     */
    public function can_view(context $context): bool {
        return has_capability($this->viewcapability, $context);
    }

    /**
     * Return a lightweight count for the current filters.
     *
     * @param filters $filters
     * @return int
     */
    public function count_rows(filters $filters): int {
        global $DB;

        if (!$this->is_installed()) {
            return 0;
        }

        [$where, $params] = $this->build_where($filters);
        try {
            return (int) $DB->count_records_select($this->tablename, $where, $params);
        } catch (dml_exception) {
            return 0;
        }
    }

    /**
     * Get rows matching the current filters.
     *
     * @param filters $filters
     * @return array<int, array<string, mixed>>
     */
    public function get_rows(filters $filters): array {
        global $DB;

        if (!$this->is_installed()) {
            return [];
        }

        $fields = [];
        $available = $this->available_columns();
        foreach ($this->columns as $column) {
            if (isset($available[$column['field']])) {
                $fields[] = $column['field'];
            }
        }

        if (empty($fields)) {
            $fields = array_slice(array_keys($available), 0, 6);
        }

        [$where, $params] = $this->build_where($filters);
        $select = implode(', ', $fields);

        try {
            $records = $DB->get_records_select($this->tablename, $where, $params, 'id ASC', $select, 0, $filters->limit);
        } catch (dml_exception) {
            return [];
        }

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $record->{$field} ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Export this source as one template card.
     *
     * @param filters $filters
     * @return array<string, mixed>
     */
    public function export_card(filters $filters): array {
        return [
            'key' => $this->key,
            'title' => $this->get_title(),
            'description' => $this->get_description(),
            'total' => $this->count_rows($filters),
            'url' => (new moodle_url('/report/uckk/index.php', $filters->with_report($this->key)->url_params()))->out(false),
            'active' => $filters->report === $this->key,
        ];
    }

    /**
     * Whether the backing table exists.
     *
     * @return bool
     */
    public function is_installed(): bool {
        global $DB;

        $manager = $DB->get_manager();
        return $manager->table_exists(new xmldb_table($this->tablename));
    }

    /**
     * Return template-friendly empty-state explanation.
     *
     * @return string
     */
    public function installation_notice(): string {
        return $this->lang('notinstalled', 'The required source table is not installed yet.');
    }

    /**
     * Build a WHERE clause from shared filters.
     *
     * @param filters $filters
     * @return array{0:string,1:array<string,int|string>}
     */
    private function build_where(filters $filters): array {
        $conditions = ['1 = 1'];
        $params = [];

        $available = $this->available_columns();

        foreach ([
            'userid',
            'cohortid',
            'programid',
            'courseid',
            'categoryid',
            'competencyid',
            'badgeid',
        ] as $idfield) {
            if (isset($available[$idfield])) {
                $filters->add_id_condition($idfield, $idfield, $conditions, $params);
            }
        }

        foreach ([
            'status',
            'visibility',
            'challengetype',
            'assemblytype',
            'integritytype',
        ] as $textfield) {
            if (isset($available[$textfield])) {
                $filters->add_text_condition($textfield, $textfield, $conditions, $params);
            }
        }

        $timefield = isset($available['timecreated']) ? 'timecreated' :
            (isset($available['timemodified']) ? 'timemodified' :
            (isset($available['dateissued']) ? 'dateissued' : ''));

        if ($timefield !== '') {
            $filters->add_time_conditions($timefield, $conditions, $params, 'report');
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * Return the available DB columns for this source table.
     *
     * @return array<string, mixed>
     */
    private function available_columns(): array {
        global $DB;

        try {
            $columns = $DB->get_columns($this->tablename);
        } catch (dml_exception) {
            return [];
        }

        $result = [];
        foreach ($columns as $name => $column) {
            $result[strtolower($name)] = $column;
        }

        return $result;
    }

    /**
     * Safe string lookup with fallback.
     *
     * @param string $key
     * @param string $fallback
     * @return string
     */
    private function lang(string $key, string $fallback): string {
        return get_string_manager()->string_exists($key, self::COMPONENT) ? get_string($key, self::COMPONENT) : $fallback;
    }

    /**
     * Create a column spec.
     *
     * @param string $field
     * @param string $stringkey
     * @param string $fallback
     * @return array<string, string>
     */
    private static function column(string $field, string $stringkey, string $fallback): array {
        return [
            'key' => $field,
            'field' => $field,
            'stringkey' => $stringkey,
            'fallback' => $fallback,
            'label' => get_string_manager()->string_exists($stringkey, self::COMPONENT) ? get_string($stringkey, self::COMPONENT) : $fallback,
        ];
    }
}
