<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

declare(strict_types=1);

/**
 * Event emitted when an Atlas to Moodle synchronisation is applied.
 *
 * This event records that an Atlas sync changed Moodle operational state.
 *
 * It must not contain:
 * - raw Atlas JSON;
 * - raw Faculty Profile JSON;
 * - learner data;
 * - enrolment data;
 * - grades;
 * - private course activity content.
 *
 * It may contain only:
 * - ids;
 * - status values;
 * - counters;
 * - checksums;
 * - sync mode metadata.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Atlas sync applied event.
 */
final class atlas_sync_applied extends \core\event\base {
    /**
     * Required keys in other[].
     */
    private const REQUIRED_OTHER_KEYS = [
        'syncid',
        'source',
        'status',
        'voiecount',
        'categorycreated',
        'categoryupdated',
        'categoryskipped',
        'coursecreated',
        'courseupdated',
        'courseskipped',
        'warningcount',
        'errorcount',
        'checksum',
    ];

    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return the localized event name.
     *
     * @return string
     */
    public static function get_name(): string {
        if (get_string_manager()->string_exists('event_atlas_sync_applied', 'local_uckk')) {
            return get_string('event_atlas_sync_applied', 'local_uckk');
        }

        return 'UCKK Atlas sync applied';
    }

    /**
     * Return a safe event description.
     *
     * The description intentionally contains only ids, status, counts and hash.
     *
     * @return string
     */
    public function get_description(): string {
        $syncid = $this->get_other_string('syncid');
        $source = $this->get_other_string('source');
        $status = $this->get_other_string('status');
        $voiecount = $this->get_other_int('voiecount');
        $categorycreated = $this->get_other_int('categorycreated');
        $categoryupdated = $this->get_other_int('categoryupdated');
        $coursecreated = $this->get_other_int('coursecreated');
        $courseupdated = $this->get_other_int('courseupdated');
        $warningcount = $this->get_other_int('warningcount');
        $errorcount = $this->get_other_int('errorcount');
        $checksum = $this->get_other_string('checksum');

        return "The user with id '{$this->userid}' applied UCKK Atlas sync '{$syncid}' "
            . "from source '{$source}' with status '{$status}'. "
            . "Voies: {$voiecount}. "
            . "Categories created/updated: {$categorycreated}/{$categoryupdated}. "
            . "Courses created/updated: {$coursecreated}/{$courseupdated}. "
            . "Warnings/errors: {$warningcount}/{$errorcount}. "
            . "Checksum: '{$checksum}'.";
    }

    /**
     * Return the canonical URL for the sync report.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/uckk/faculty_sync.php', [
            'syncid' => $this->get_other_string('syncid'),
        ]);
    }

    /**
     * Create the event from a sync report array.
     *
     * Expected report keys:
     *
     * syncid, source, status, voiecount,
     * categorycreated, categoryupdated, categoryskipped,
     * coursecreated, courseupdated, courseskipped,
     * warningcount, errorcount, checksum.
     *
     * Optional report keys:
     *
     * categorydeleted, coursedeleted, badgecreated, badgeupdated, badgeskipped,
     * fieldcreated, fieldupdated, fieldskipped, dryrunchecksum, durationms.
     *
     * @param \context $context Event context.
     * @param array<string, mixed> $report Sanitized sync report.
     * @return self
     */
    public static function create_from_report(\context $context, array $report): self {
        $other = [
            'syncid' => self::clean_report_string($report, 'syncid'),
            'source' => self::clean_report_string($report, 'source', 'atlas'),
            'status' => self::clean_report_string($report, 'status'),
            'voiecount' => self::clean_report_int($report, 'voiecount'),
            'categorycreated' => self::clean_report_int($report, 'categorycreated'),
            'categoryupdated' => self::clean_report_int($report, 'categoryupdated'),
            'categoryskipped' => self::clean_report_int($report, 'categoryskipped'),
            'coursecreated' => self::clean_report_int($report, 'coursecreated'),
            'courseupdated' => self::clean_report_int($report, 'courseupdated'),
            'courseskipped' => self::clean_report_int($report, 'courseskipped'),
            'warningcount' => self::clean_report_int($report, 'warningcount'),
            'errorcount' => self::clean_report_int($report, 'errorcount'),
            'checksum' => self::clean_report_checksum($report, 'checksum'),
        ];

        foreach ([
            'categorydeleted',
            'coursedeleted',
            'badgecreated',
            'badgeupdated',
            'badgeskipped',
            'fieldcreated',
            'fieldupdated',
            'fieldskipped',
            'durationms',
        ] as $key) {
            if (array_key_exists($key, $report)) {
                $other[$key] = self::clean_report_int($report, $key);
            }
        }

        if (array_key_exists('dryrunchecksum', $report)) {
            $other['dryrunchecksum'] = self::clean_report_checksum($report, 'dryrunchecksum');
        }

        return self::create([
            'context' => $context,
            'other' => $other,
        ]);
    }

    /**
     * Validate event data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        foreach (self::REQUIRED_OTHER_KEYS as $key) {
            if (!array_key_exists($key, $this->other)) {
                throw new \coding_exception("atlas_sync_applied requires '{$key}' in other[].");
            }
        }

        if ($this->other['syncid'] === '') {
            throw new \coding_exception('atlas_sync_applied requires a non-empty syncid.');
        }

        if ($this->other['status'] === '') {
            throw new \coding_exception('atlas_sync_applied requires a non-empty status.');
        }

        if ($this->other['checksum'] === '') {
            throw new \coding_exception('atlas_sync_applied requires a non-empty checksum.');
        }

        foreach ([
            'voiecount',
            'categorycreated',
            'categoryupdated',
            'categoryskipped',
            'coursecreated',
            'courseupdated',
            'courseskipped',
            'warningcount',
            'errorcount',
        ] as $key) {
            if (!is_int($this->other[$key]) || $this->other[$key] < 0) {
                throw new \coding_exception("atlas_sync_applied requires '{$key}' to be a non-negative integer.");
            }
        }
    }

    /**
     * Legacy log data.
     *
     * @return array<int, mixed>
     */
    protected function get_legacy_logdata(): array {
        return [
            SITEID,
            'local_uckk',
            'atlas sync applied',
            $this->get_url()->out(false),
            $this->get_other_string('syncid'),
            $this->contextinstanceid,
        ];
    }

    /**
     * Return object id mapping.
     *
     * There is no dedicated sync table in the DOC_12 contract, so no object id
     * restore mapping is declared here.
     *
     * @return array<string, mixed>
     */
    public static function get_objectid_mapping(): array {
        return [];
    }

    /**
     * Return other[] mapping.
     *
     * other[] contains operational ids/counts/checksums only. There are no user
     * ids, course ids, category ids or content records that require restore
     * mapping.
     *
     * @return array<string, mixed>
     */
    public static function get_other_mapping(): array {
        return [];
    }

    /**
     * Safely read a string from other[].
     *
     * @param string $key Other key.
     * @param string $default Default value.
     * @return string
     */
    private function get_other_string(string $key, string $default = ''): string {
        if (!array_key_exists($key, $this->other)) {
            return $default;
        }

        $value = $this->other[$key];

        if (is_scalar($value)) {
            return clean_param((string)$value, PARAM_TEXT);
        }

        return $default;
    }

    /**
     * Safely read an integer from other[].
     *
     * @param string $key Other key.
     * @param int $default Default value.
     * @return int
     */
    private function get_other_int(string $key, int $default = 0): int {
        if (!array_key_exists($key, $this->other)) {
            return $default;
        }

        return max(0, (int)$this->other[$key]);
    }

    /**
     * Clean a string report value.
     *
     * @param array<string, mixed> $report Sync report.
     * @param string $key Report key.
     * @param string $default Default value.
     * @return string
     */
    private static function clean_report_string(array $report, string $key, string $default = ''): string {
        if (!array_key_exists($key, $report)) {
            return $default;
        }

        $value = $report[$key];

        if (!is_scalar($value)) {
            return $default;
        }

        return clean_param(trim((string)$value), PARAM_TEXT);
    }

    /**
     * Clean an integer report value.
     *
     * @param array<string, mixed> $report Sync report.
     * @param string $key Report key.
     * @return int
     */
    private static function clean_report_int(array $report, string $key): int {
        if (!array_key_exists($key, $report)) {
            return 0;
        }

        return max(0, (int)$report[$key]);
    }

    /**
     * Clean a checksum report value.
     *
     * @param array<string, mixed> $report Sync report.
     * @param string $key Report key.
     * @return string
     */
    private static function clean_report_checksum(array $report, string $key): string {
        if (!array_key_exists($key, $report)) {
            return '';
        }

        $value = $report[$key];

        if (!is_scalar($value)) {
            return '';
        }

        $checksum = strtolower(trim((string)$value));

        if ($checksum === '') {
            return '';
        }

        if (!preg_match('/^[a-f0-9]{32,64}$/', $checksum)) {
            return clean_param($checksum, PARAM_ALPHANUMEXT);
        }

        return $checksum;
    }
}