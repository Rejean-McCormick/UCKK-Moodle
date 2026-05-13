<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

use report_uckk\local\filters;
use report_uckk\local\report_source;

/**
 * Tests for report_uckk registry, filters and report-source alignment.
 *
 * @package    report_uckk
 * @covers     \report_uckk\local\filters
 * @covers     \report_uckk\local\report_source
 */
final class report_uckk_report_test extends advanced_testcase {
    /**
     * Report registry must expose every canonical UCKK report key.
     */
    public function test_report_registry_contains_expected_sources(): void {
        $sources = report_source::all();

        $expected = [
            report_source::REPORT_PLAYER_PROGRESS,
            report_source::REPORT_COHORT_PROGRESS,
            report_source::REPORT_PROGRAM_PROGRESS,
            report_source::REPORT_COMPETENCY_MATRIX,
            report_source::REPORT_BADGE_AWARDS,
            report_source::REPORT_CHALLENGE_STATUS,
            report_source::REPORT_ASSEMBLY_DECISIONS,
            report_source::REPORT_ARCHIVE_PRODUCTION,
            report_source::REPORT_INTEGRITY_CASES,
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $sources);
            $this->assertSame($key, $sources[$key]->get_key());
            $this->assertNotEmpty($sources[$key]->get_columns());
        }
    }

    /**
     * Unknown report keys should safely resolve to the default report.
     */
    public function test_get_unknown_report_returns_default_source(): void {
        $source = report_source::get('not_a_real_report');

        $this->assertSame(report_source::DEFAULT_REPORT, $source->get_key());
    }

    /**
     * Filters should normalize scalar request-like values.
     */
    public function test_filters_normalize_values(): void {
        set_config('defaultlimit', 100, 'report_uckk');

        $filters = new filters([
            'report' => report_source::REPORT_CHALLENGE_STATUS,
            'format' => filters::FORMAT_CSV,
            'userid' => -42,
            'cohortid' => 5,
            'programid' => 6,
            'courseid' => 7,
            'categoryid' => 8,
            'competencyid' => 9,
            'badgeid' => 10,
            'status' => 'validated',
            'visibility' => 'institutional',
            'challengetype' => 'capstone',
            'assemblytype' => 'savoirs',
            'integritytype' => 'proof_quality',
            'from' => 200,
            'to' => 100,
            'limit' => 25,
        ]);

        $this->assertSame(report_source::REPORT_CHALLENGE_STATUS, $filters->report);
        $this->assertSame(filters::FORMAT_CSV, $filters->format);
        $this->assertSame(0, $filters->userid);
        $this->assertSame(5, $filters->cohortid);
        $this->assertSame(6, $filters->programid);
        $this->assertSame(7, $filters->courseid);
        $this->assertSame(8, $filters->categoryid);
        $this->assertSame(9, $filters->competencyid);
        $this->assertSame(10, $filters->badgeid);
        $this->assertSame('validated', $filters->status);
        $this->assertSame('institutional', $filters->visibility);
        $this->assertSame('capstone', $filters->challengetype);
        $this->assertSame('savoirs', $filters->assemblytype);
        $this->assertSame('proof_quality', $filters->integritytype);

        // Constructor swaps an inverted date range.
        $this->assertSame(100, $filters->from);
        $this->assertSame(200, $filters->to);

        $this->assertSame(25, $filters->limit);
    }

    /**
     * Invalid export format falls back to HTML.
     */
    public function test_filters_invalid_format_falls_back_to_html(): void {
        $filters = new filters([
            'format' => 'xlsx',
        ]);

        $this->assertSame(filters::FORMAT_HTML, $filters->format);
    }

    /**
     * with_report() must clone filters without mutating the original object.
     */
    public function test_with_report_returns_modified_clone(): void {
        $original = new filters([
            'report' => report_source::REPORT_PLAYER_PROGRESS,
            'userid' => 12,
        ]);

        $clone = $original->with_report(report_source::REPORT_ARCHIVE_PRODUCTION);

        $this->assertSame(report_source::REPORT_PLAYER_PROGRESS, $original->report);
        $this->assertSame(report_source::REPORT_ARCHIVE_PRODUCTION, $clone->report);
        $this->assertSame($original->userid, $clone->userid);
    }

    /**
     * with_format() must clone filters without mutating the original object.
     */
    public function test_with_format_returns_modified_clone(): void {
        $original = new filters([
            'format' => filters::FORMAT_HTML,
            'courseid' => 3,
        ]);

        $clone = $original->with_format(filters::FORMAT_JSON);

        $this->assertSame(filters::FORMAT_HTML, $original->format);
        $this->assertSame(filters::FORMAT_JSON, $clone->format);
        $this->assertSame($original->courseid, $clone->courseid);
    }

    /**
     * URL params should omit empty filters but keep meaningful selected values.
     */
    public function test_url_params_omit_empty_values(): void {
        $filters = new filters([
            'report' => report_source::REPORT_BADGE_AWARDS,
            'format' => filters::FORMAT_HTML,
            'userid' => 0,
            'badgeid' => 44,
            'status' => '',
            'limit' => 50,
        ]);

        $params = $filters->url_params();

        $this->assertSame(report_source::REPORT_BADGE_AWARDS, $params['report']);
        $this->assertSame(44, $params['badgeid']);
        $this->assertSame(50, $params['limit']);
        $this->assertArrayNotHasKey('userid', $params);
        $this->assertArrayNotHasKey('status', $params);
        $this->assertArrayNotHasKey('format', $params);
    }

    /**
     * Active filters should expose only non-empty filters to templates.
     */
    public function test_active_filters_for_template_returns_non_empty_filters(): void {
        $filters = new filters([
            'userid' => 99,
            'status' => 'open',
            'visibility' => '',
            'from' => 12345,
            'to' => 0,
        ]);

        $active = $filters->active_filters_for_template();
        $fields = array_column($active, 'field');

        $this->assertContains('userid', $fields);
        $this->assertContains('status', $fields);
        $this->assertContains('from', $fields);
        $this->assertNotContains('visibility', $fields);
        $this->assertNotContains('to', $fields);
    }

    /**
     * ID helper should add SQL only when the filter has a positive value.
     */
    public function test_add_id_condition_adds_positive_filter_only(): void {
        $filters = new filters([
            'courseid' => 15,
            'cohortid' => 0,
        ]);

        $conditions = ['1 = 1'];
        $params = [];

        $filters->add_id_condition('courseid', 'c.id', $conditions, $params);
        $filters->add_id_condition('cohortid', 'co.id', $conditions, $params);

        $this->assertContains('c.id = :courseid', $conditions);
        $this->assertNotContains('co.id = :cohortid', $conditions);
        $this->assertSame(15, $params['courseid']);
        $this->assertArrayNotHasKey('cohortid', $params);
    }

    /**
     * Text helper should add SQL only when the filter is non-empty.
     */
    public function test_add_text_condition_adds_non_empty_filter_only(): void {
        $filters = new filters([
            'status' => 'validated',
            'visibility' => '',
        ]);

        $conditions = ['1 = 1'];
        $params = [];

        $filters->add_text_condition('status', 's.status', $conditions, $params);
        $filters->add_text_condition('visibility', 's.visibility', $conditions, $params);

        $this->assertContains('s.status = :status', $conditions);
        $this->assertNotContains('s.visibility = :visibility', $conditions);
        $this->assertSame('validated', $params['status']);
        $this->assertArrayNotHasKey('visibility', $params);
    }

    /**
     * Time helper should add from/to SQL conditions when timestamps are present.
     */
    public function test_add_time_conditions_adds_range_filters(): void {
        $filters = new filters([
            'from' => 1000,
            'to' => 2000,
        ]);

        $conditions = ['1 = 1'];
        $params = [];

        $filters->add_time_conditions('x.timecreated', $conditions, $params, 'report');

        $this->assertContains('x.timecreated >= :reportfrom', $conditions);
        $this->assertContains('x.timecreated <= :reportto', $conditions);
        $this->assertSame(1000, $params['reportfrom']);
        $this->assertSame(2000, $params['reportto']);
    }

    /**
     * Helper methods should reject unknown filter properties.
     */
    public function test_condition_helpers_reject_unknown_properties(): void {
        $filters = new filters();

        $conditions = [];
        $params = [];

        $this->expectException(coding_exception::class);
        $filters->add_id_condition('missingfilter', 'x.id', $conditions, $params);
    }

    /**
     * Raw values must contain every shared filter field.
     */
    public function test_raw_values_contains_all_shared_fields(): void {
        $filters = new filters();
        $values = $filters->raw_values();

        $expected = [
            'report',
            'format',
            'userid',
            'cohortid',
            'programid',
            'courseid',
            'categoryid',
            'competencyid',
            'badgeid',
            'status',
            'visibility',
            'challengetype',
            'assemblytype',
            'integritytype',
            'from',
            'to',
            'limit',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $values);
        }
    }
}