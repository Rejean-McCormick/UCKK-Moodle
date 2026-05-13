<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\local;

defined('MOODLE_INTERNAL') || die();

use context;
use moodle_url;
use report_uckk\output\report_card;

/**
 * Base class and registry for UCKK institutional report sources.
 *
 * Canonical report keys are defined here so routing, report classes, language
 * strings, templates, exports and tests all use the same vocabulary.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class report_source {
    /** Joueur progress report key. */
    public const REPORT_PLAYER_PROGRESS = 'player_progress';

    /** Cohort progress report key. */
    public const REPORT_COHORT_PROGRESS = 'cohort_progress';

    /** Program progress report key. */
    public const REPORT_PROGRAM_PROGRESS = 'program_progress';

    /** Competency matrix report key. */
    public const REPORT_COMPETENCY_MATRIX = 'competency_matrix';

    /** Badge awards report key. */
    public const REPORT_BADGE_AWARDS = 'badge_awards';

    /** Challenge status report key. */
    public const REPORT_CHALLENGE_STATUS = 'challenge_status';

    /** Assembly decisions report key. */
    public const REPORT_ASSEMBLY_DECISIONS = 'assembly_decisions';

    /** Archive production report key. */
    public const REPORT_ARCHIVE_PRODUCTION = 'archive_production';

    /** Integrity cases report key. */
    public const REPORT_INTEGRITY_CASES = 'integrity_cases';

    /** AI usage report key. */
    public const REPORT_AI_USAGE = 'ai_usage';

    /** Privacy exports report key. */
    public const REPORT_PRIVACY_EXPORTS = 'privacy_exports';

    /** Default dashboard report. */
    public const DEFAULT_REPORT = self::REPORT_PLAYER_PROGRESS;

    /**
     * Return all implemented report sources.
     *
     * The doctrine also names AI usage and privacy export reports. Their keys
     * are reserved above, but they are not registered here unless corresponding
     * concrete classes are present.
     *
     * @return array<string,self>
     */
    public static function all(): array {
        return [
            self::REPORT_PLAYER_PROGRESS => new \report_uckk\report\player_progress(),
            self::REPORT_COHORT_PROGRESS => new \report_uckk\report\cohort_progress(),
            self::REPORT_PROGRAM_PROGRESS => new \report_uckk\report\program_progress(),
            self::REPORT_COMPETENCY_MATRIX => new \report_uckk\report\competency_report(),
            self::REPORT_BADGE_AWARDS => new \report_uckk\report\badge_report(),
            self::REPORT_CHALLENGE_STATUS => new \report_uckk\report\challenge_report(),
            self::REPORT_ASSEMBLY_DECISIONS => new \report_uckk\report\assembly_report(),
            self::REPORT_ARCHIVE_PRODUCTION => new \report_uckk\report\archive_report(),
            self::REPORT_INTEGRITY_CASES => new \report_uckk\report\integrity_report(),
        ];
    }

    /**
     * Return a report source by key.
     *
     * @param string $key Report key.
     * @return self
     */
    public static function get(string $key): self {
        $sources = self::all();

        if (array_key_exists($key, $sources)) {
            return $sources[$key];
        }

        return $sources[self::DEFAULT_REPORT];
    }

    /**
     * Return true when a report key is registered.
     *
     * @param string $key Report key.
     * @return bool
     */
    public static function exists(string $key): bool {
        return array_key_exists($key, self::all());
    }

    /**
     * Return canonical keys for all registered reports.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys(self::all());
    }

    /**
     * Return menu options for report selector UI.
     *
     * @param context|null $context Optional context for permission filtering.
     * @return array<string,string>
     */
    public static function menu(?context $context = null): array {
        $menu = [];

        foreach (self::all() as $key => $source) {
            if ($context !== null && !$source->can_view($context)) {
                continue;
            }

            $menu[$key] = $source->get_title();
        }

        return $menu;
    }

    /**
     * Canonical report key.
     *
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Report columns keyed by row field.
     *
     * @return array<string,string>
     */
    abstract public function get_columns(): array;

    /**
     * Rows matching selected filters.
     *
     * Each row key must match get_columns().
     *
     * @param filters $filters Normalized filters.
     * @return array<int,array<string,scalar|null>>
     */
    abstract public function get_rows(filters $filters): array;

    /**
     * Localized report title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('report:' . $this->get_key(), 'report_uckk');
    }

    /**
     * Localized report description.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('reportdesc:' . $this->get_key(), 'report_uckk');
    }

    /**
     * Required capability for this report.
     *
     * Reports default to normal report visibility. Sensitive reports, such as
     * integrity cases, can override this and require report/uckk:viewall.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return 'report/uckk:view';
    }

    /**
     * Check whether the current user can view this report.
     *
     * @param context $context Moodle context.
     * @return bool
     */
    public function can_view(context $context): bool {
        return has_capability($this->get_required_capability(), $context);
    }

    /**
     * Build a dashboard card for this report.
     *
     * @param filters $filters Current filters.
     * @param bool $active Whether this source is selected.
     * @return report_card
     */
    public function card(filters $filters, bool $active = false): report_card {
        $cardfilters = $filters->with_report($this->get_key());
        $url = new moodle_url('/report/uckk/index.php', $cardfilters->url_params());

        return new report_card(
            $this->get_key(),
            $this->get_title(),
            $this->get_description(),
            $this->get_total($filters),
            $url,
            $active
        );
    }

    /**
     * Return total rows matching filters.
     *
     * @param filters $filters Normalized filters.
     * @return int
     */
    public function get_total(filters $filters): int {
        $rows = $this->get_rows($filters);

        if ($this->is_unavailable_rows($rows)) {
            return 0;
        }

        return count($rows);
    }

    /**
     * Return true if a Moodle table exists.
     *
     * @param string $tablename Table name without braces.
     * @return bool
     */
    protected function table_exists(string $tablename): bool {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }

    /**
     * Convert Moodle DB records into report rows using this source's columns.
     *
     * @param array<int|string,\stdClass> $records Database records.
     * @return array<int,array<string,scalar|null>>
     */
    protected function records_to_rows(array $records): array {
        $columns = array_keys($this->get_columns());
        $rows = [];

        foreach ($records as $record) {
            $row = [];

            foreach ($columns as $column) {
                $value = $record->{$column} ?? null;

                if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }

                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $row[$column] = $value;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Format a timestamp for display.
     *
     * @param int|null $timestamp Unix timestamp.
     * @return string
     */
    protected function format_time(?int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }

        return userdate($timestamp);
    }

    /**
     * Format a database status value.
     *
     * @param string|null $status Status.
     * @param string|null $component Optional component for localized status strings.
     * @return string
     */
    protected function format_status(?string $status, ?string $component = null): string {
        if ($status === null || $status === '') {
            return '-';
        }

        if ($component !== null) {
            $identifier = 'status:' . $status;
            if (get_string_manager()->string_exists($identifier, $component)) {
                return get_string($identifier, $component);
            }
        }

        return s($status);
    }

    /**
     * Return a consistent unavailable-source row.
     *
     * Used when a dependent plugin table is not installed yet. This keeps the
     * dashboard renderable while making the missing source explicit.
     *
     * @return array<int,array<string,scalar|null>>
     */
    protected function unavailable_row(): array {
        $columns = array_keys($this->get_columns());
        $row = [];

        foreach ($columns as $column) {
            $row[$column] = '-';
        }

        $firstkey = reset($columns);
        if ($firstkey !== false) {
            $row[$firstkey] = get_string('notinstalled', 'report_uckk');
        }

        return [$row];
    }

    /**
     * Check whether a row set is the standard unavailable-source row.
     *
     * @param array<int,array<string,scalar|null>> $rows Rows.
     * @return bool
     */
    private function is_unavailable_rows(array $rows): bool {
        if (count($rows) !== 1) {
            return false;
        }

        $firstrow = reset($rows);
        if (!is_array($firstrow)) {
            return false;
        }

        return in_array(get_string('notinstalled', 'report_uckk'), $firstrow, true);
    }
}