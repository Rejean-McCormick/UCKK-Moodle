<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalized filter object shared by report_uckk pages, sources, exports and templates.
 *
 * Canonical report filters:
 * - user
 * - cohort
 * - program
 * - course
 * - category
 * - date range
 * - status
 * - visibility
 * - competency
 * - badge
 * - challenge type
 * - assembly type
 * - integrity type
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filters {
    /** HTML dashboard output. */
    public const FORMAT_HTML = 'html';

    /** CSV export output. */
    public const FORMAT_CSV = 'csv';

    /** JSON audit export output. */
    public const FORMAT_JSON = 'json';

    /** @var string Selected canonical report key. */
    public string $report;

    /** @var string Output format. */
    public string $format;

    /** @var int Moodle user id filter. */
    public int $userid;

    /** @var int Moodle cohort id filter. */
    public int $cohortid;

    /** @var int UCKK program id filter. */
    public int $programid;

    /** @var int Moodle course id filter. */
    public int $courseid;

    /** @var int Moodle course category id filter. */
    public int $categoryid;

    /** @var int Competency id filter. */
    public int $competencyid;

    /** @var int Badge id filter. */
    public int $badgeid;

    /** @var string Workflow status filter. */
    public string $status;

    /** @var string Visibility filter. */
    public string $visibility;

    /** @var string Challenge type filter. */
    public string $challengetype;

    /** @var string Assembly type filter. */
    public string $assemblytype;

    /** @var string Integrity case type filter. */
    public string $integritytype;

    /** @var int Start timestamp filter. */
    public int $from;

    /** @var int End timestamp filter. */
    public int $to;

    /** @var int Maximum row count. */
    public int $limit;

    /**
     * Constructor.
     *
     * @param array<string,mixed> $values Raw filter values.
     */
    public function __construct(array $values = []) {
        $this->report = clean_param(
            $values['report'] ?? report_source::DEFAULT_REPORT,
            PARAM_ALPHANUMEXT
        );

        $this->format = clean_param(
            $values['format'] ?? self::FORMAT_HTML,
            PARAM_ALPHA
        );

        $this->userid = max(0, (int)($values['userid'] ?? 0));
        $this->cohortid = max(0, (int)($values['cohortid'] ?? 0));
        $this->programid = max(0, (int)($values['programid'] ?? 0));
        $this->courseid = max(0, (int)($values['courseid'] ?? 0));
        $this->categoryid = max(0, (int)($values['categoryid'] ?? 0));
        $this->competencyid = max(0, (int)($values['competencyid'] ?? 0));
        $this->badgeid = max(0, (int)($values['badgeid'] ?? 0));

        $this->status = clean_param($values['status'] ?? '', PARAM_ALPHANUMEXT);
        $this->visibility = clean_param($values['visibility'] ?? '', PARAM_ALPHANUMEXT);
        $this->challengetype = clean_param($values['challengetype'] ?? '', PARAM_ALPHANUMEXT);
        $this->assemblytype = clean_param($values['assemblytype'] ?? '', PARAM_ALPHANUMEXT);
        $this->integritytype = clean_param($values['integritytype'] ?? '', PARAM_ALPHANUMEXT);

        $this->from = max(0, (int)($values['from'] ?? 0));
        $this->to = max(0, (int)($values['to'] ?? 0));
        $this->limit = $this->normalize_limit($values['limit'] ?? 0);

        if (!in_array($this->format, [self::FORMAT_HTML, self::FORMAT_CSV, self::FORMAT_JSON], true)) {
            $this->format = self::FORMAT_HTML;
        }

        if ($this->to > 0 && $this->from > 0 && $this->to < $this->from) {
            [$this->from, $this->to] = [$this->to, $this->from];
        }
    }

    /**
     * Build filters from request parameters.
     *
     * @return self
     */
    public static function from_request(): self {
        return new self([
            'report' => optional_param('report', report_source::DEFAULT_REPORT, PARAM_ALPHANUMEXT),
            'format' => optional_param('format', self::FORMAT_HTML, PARAM_ALPHA),
            'userid' => optional_param('userid', 0, PARAM_INT),
            'cohortid' => optional_param('cohortid', 0, PARAM_INT),
            'programid' => optional_param('programid', 0, PARAM_INT),
            'courseid' => optional_param('courseid', 0, PARAM_INT),
            'categoryid' => optional_param('categoryid', 0, PARAM_INT),
            'competencyid' => optional_param('competencyid', 0, PARAM_INT),
            'badgeid' => optional_param('badgeid', 0, PARAM_INT),
            'status' => optional_param('status', '', PARAM_ALPHANUMEXT),
            'visibility' => optional_param('visibility', '', PARAM_ALPHANUMEXT),
            'challengetype' => optional_param('challengetype', '', PARAM_ALPHANUMEXT),
            'assemblytype' => optional_param('assemblytype', '', PARAM_ALPHANUMEXT),
            'integritytype' => optional_param('integritytype', '', PARAM_ALPHANUMEXT),
            'from' => optional_param('from', 0, PARAM_INT),
            'to' => optional_param('to', 0, PARAM_INT),
            'limit' => optional_param('limit', 0, PARAM_INT),
        ]);
    }

    /**
     * Return a cloned filter object with a different report key.
     *
     * @param string $report Canonical report key.
     * @return self
     */
    public function with_report(string $report): self {
        $values = $this->raw_values();
        $values['report'] = $report;

        return new self($values);
    }

    /**
     * Return a cloned filter object with a different output format.
     *
     * @param string $format Output format.
     * @return self
     */
    public function with_format(string $format): self {
        $values = $this->raw_values();
        $values['format'] = $format;

        return new self($values);
    }

    /**
     * Return URL parameters for links and exports.
     *
     * Empty values are omitted, except limit because it controls result size.
     *
     * @return array<string,int|string>
     */
    public function url_params(): array {
        $params = $this->raw_values();

        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                continue;
            }

            if ($value === '' || $value === 0 || $value === self::FORMAT_HTML) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Return active filters for template display.
     *
     * @return array<int,array<string,string>>
     */
    public function active_filters_for_template(): array {
        $labels = [
            'userid' => get_string('user', 'report_uckk'),
            'cohortid' => get_string('cohort', 'report_uckk'),
            'programid' => get_string('program', 'report_uckk'),
            'courseid' => get_string('course', 'report_uckk'),
            'categoryid' => get_string('category', 'report_uckk'),
            'competencyid' => get_string('competency', 'report_uckk'),
            'badgeid' => get_string('badge', 'report_uckk'),
            'status' => get_string('status', 'report_uckk'),
            'visibility' => get_string('visibility', 'report_uckk'),
            'challengetype' => get_string('challengetype', 'report_uckk'),
            'assemblytype' => get_string('assemblytype', 'report_uckk'),
            'integritytype' => get_string('integritytype', 'report_uckk'),
            'from' => get_string('from', 'report_uckk'),
            'to' => get_string('to', 'report_uckk'),
        ];

        $active = [];

        foreach ($labels as $field => $label) {
            $value = $this->{$field};

            if ($value === '' || $value === 0) {
                continue;
            }

            $active[] = [
                'field' => $field,
                'label' => $label,
                'value' => (string)$value,
            ];
        }

        return $active;
    }

    /**
     * Add equality SQL condition for a numeric id filter.
     *
     * @param string $filterproperty Filter property name.
     * @param string $field SQL field expression.
     * @param array<int,string> $conditions SQL conditions to update.
     * @param array<string,mixed> $params SQL params to update.
     * @param string|null $paramname Optional explicit param name.
     * @return void
     */
    public function add_id_condition(
        string $filterproperty,
        string $field,
        array &$conditions,
        array &$params,
        ?string $paramname = null
    ): void {
        if (!property_exists($this, $filterproperty)) {
            throw new \coding_exception('Unknown report filter property: ' . $filterproperty);
        }

        $value = (int)$this->{$filterproperty};

        if ($value <= 0) {
            return;
        }

        $paramname = $paramname ?? $filterproperty;
        $conditions[] = "$field = :$paramname";
        $params[$paramname] = $value;
    }

    /**
     * Add equality SQL condition for a text filter.
     *
     * @param string $filterproperty Filter property name.
     * @param string $field SQL field expression.
     * @param array<int,string> $conditions SQL conditions to update.
     * @param array<string,mixed> $params SQL params to update.
     * @param string|null $paramname Optional explicit param name.
     * @return void
     */
    public function add_text_condition(
        string $filterproperty,
        string $field,
        array &$conditions,
        array &$params,
        ?string $paramname = null
    ): void {
        if (!property_exists($this, $filterproperty)) {
            throw new \coding_exception('Unknown report filter property: ' . $filterproperty);
        }

        $value = (string)$this->{$filterproperty};

        if ($value === '') {
            return;
        }

        $paramname = $paramname ?? $filterproperty;
        $conditions[] = "$field = :$paramname";
        $params[$paramname] = $value;
    }

    /**
     * Add date range conditions against a timestamp field.
     *
     * @param string $field SQL field expression.
     * @param array<int,string> $conditions SQL conditions to update.
     * @param array<string,mixed> $params SQL params to update.
     * @param string $prefix Parameter prefix.
     * @return void
     */
    public function add_time_conditions(
        string $field,
        array &$conditions,
        array &$params,
        string $prefix
    ): void {
        if ($this->from > 0) {
            $conditions[] = "$field >= :{$prefix}from";
            $params[$prefix . 'from'] = $this->from;
        }

        if ($this->to > 0) {
            $conditions[] = "$field <= :{$prefix}to";
            $params[$prefix . 'to'] = $this->to;
        }
    }

    /**
     * Return all raw values.
     *
     * @return array<string,int|string>
     */
    public function raw_values(): array {
        return [
            'report' => $this->report,
            'format' => $this->format,
            'userid' => $this->userid,
            'cohortid' => $this->cohortid,
            'programid' => $this->programid,
            'courseid' => $this->courseid,
            'categoryid' => $this->categoryid,
            'competencyid' => $this->competencyid,
            'badgeid' => $this->badgeid,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'challengetype' => $this->challengetype,
            'assemblytype' => $this->assemblytype,
            'integritytype' => $this->integritytype,
            'from' => $this->from,
            'to' => $this->to,
            'limit' => $this->limit,
        ];
    }

    /**
     * Normalize row limit.
     *
     * @param mixed $limit Requested limit.
     * @return int
     */
    private function normalize_limit($limit): int {
        $configured = (int)get_config('report_uckk', 'defaultlimit');

        if ($configured <= 0) {
            $configured = 100;
        }

        $limit = (int)$limit;

        if ($limit <= 0) {
            $limit = $configured;
        }

        return max(1, min(10000, $limit));
    }
}