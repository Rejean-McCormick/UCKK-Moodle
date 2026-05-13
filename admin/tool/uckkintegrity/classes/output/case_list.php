<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use tool_uckkintegrity\local\integrity_policy;

/**
 * Renderable case list for UCKK integrity cases.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 UCKK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_list implements renderable, templatable {
    /** @var array Integrity case records. */
    private array $cases;

    /** @var array Active list filters. */
    private array $filters;

    /** @var int Total matching records. */
    private int $total;

    /** @var int Current page number. */
    private int $page;

    /** @var int Records per page. */
    private int $perpage;

    /**
     * Constructor.
     *
     * @param array $cases Integrity case records.
     * @param array $filters Active filters.
     * @param int $total Total matching records.
     * @param int $page Current page number.
     * @param int $perpage Records per page.
     */
    public function __construct(
        array $cases,
        array $filters = [],
        int $total = 0,
        int $page = 0,
        int $perpage = 50
    ) {
        $this->cases = $cases;
        $this->filters = $filters;
        $this->total = $total;
        $this->page = $page;
        $this->perpage = $perpage;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output Renderer.
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        $cases = [];

        foreach ($this->cases as $case) {
            $cases[] = [
                'id' => (int) $case->id,
                'casetype' => self::safe_case_type_label((string) $case->casetype),
                'subjectcomponent' => s($case->subjectcomponent),
                'subjectid' => (int) $case->subjectid,
                'severity' => self::safe_severity_label((string) $case->severity),
                'status' => self::safe_status_label((string) $case->status),
                'summary' => shorten_text(format_text($case->summary, FORMAT_PLAIN), 160),
                'openedby' => (int) $case->openedby,
                'assignedto' => !empty($case->assignedto) ? (int) $case->assignedto : 0,
                'visibility' => s($case->visibility),
                'timecreated' => userdate((int) $case->timecreated),
                'timemodified' => userdate((int) $case->timemodified),
                'url' => (new \moodle_url('/admin/tool/uckkintegrity/case.php', [
                    'id' => $case->id,
                ]))->out(false),
                'reviewurl' => (new \moodle_url('/admin/tool/uckkintegrity/review.php', [
                    'id' => $case->id,
                ]))->out(false),
                'decisionurl' => (new \moodle_url('/admin/tool/uckkintegrity/decision.php', [
                    'id' => $case->id,
                ]))->out(false),
            ];
        }

        return (object) [
            'cases' => $cases,
            'hascases' => !empty($cases),
            'total' => $this->total,
            'page' => $this->page,
            'perpage' => $this->perpage,
            'filters' => $this->export_filters(),
            'hasfilters' => !empty(array_filter($this->filters)),
            'createurl' => (new \moodle_url('/admin/tool/uckkintegrity/case.php', [
                'action' => 'create',
            ]))->out(false),
            'reporturl' => (new \moodle_url('/admin/tool/uckkintegrity/report.php'))->out(false),
        ];
    }

    /**
     * Export active filters.
     *
     * @return array
     */
    private function export_filters(): array {
        $filters = [];

        foreach (['status', 'severity', 'casetype'] as $name) {
            if (!empty($this->filters[$name])) {
                $filters[] = [
                    'name' => $name,
                    'value' => s($this->filters[$name]),
                ];
            }
        }

        return $filters;
    }

    /**
     * Return a safe status label.
     *
     * @param string $status Status key.
     * @return string
     */
    private static function safe_status_label(string $status): string {
        return in_array($status, integrity_policy::STATUSES, true)
            ? get_string('status:' . $status, 'tool_uckkintegrity')
            : s($status);
    }

    /**
     * Return a safe case type label.
     *
     * @param string $casetype Case type key.
     * @return string
     */
    private static function safe_case_type_label(string $casetype): string {
        return in_array($casetype, integrity_policy::CASE_TYPES, true)
            ? get_string('type:' . $casetype, 'tool_uckkintegrity')
            : s($casetype);
    }

    /**
     * Return a safe severity label.
     *
     * @param string $severity Severity key.
     * @return string
     */
    private static function safe_severity_label(string $severity): string {
        $key = 'severity:' . $severity;
        return get_string_manager()->string_exists($key, 'tool_uckkintegrity')
            ? get_string($key, 'tool_uckkintegrity')
            : s($severity);
    }
}