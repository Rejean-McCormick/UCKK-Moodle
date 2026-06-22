<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when an UCKK Atlas to Moodle sync dry-run is completed.
 *
 * @package    local_uckk
 * @category   event
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\event;

use coding_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when an UCKK Atlas to Moodle sync dry-run is completed.
 *
 * This event is an audit record only. It must not contain the full Atlas JSON,
 * Faculty JSON, rendered content, course payloads, category payloads, private
 * user data, grades, progress data, enrolment state, stack traces, or any other
 * private payload.
 *
 * Expected creation:
 *
 * ```php
 * $event = \local_uckk\event\atlas_sync_dryrun_completed::create([
 *     'context' => \context_system::instance(),
 *     'other' => [
 *         'sync_result' => 'completed',
 *         'scope' => 'all',
 *         'voie_count' => 10,
 *         'category_count' => 10,
 *         'course_count' => 100,
 *         'create_count' => 0,
 *         'update_count' => 12,
 *         'hide_count' => 0,
 *         'noop_count' => 98,
 *         'error_count' => 0,
 *         'warning_count' => 2,
 *         'report_hash' => 'sha256:...',
 *         'source' => 'run_atlas_sync_dryrun',
 *     ],
 * ]);
 * $event->trigger();
 * ```
 */
final class atlas_sync_dryrun_completed extends \core\event\base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = null;
    }

    /**
     * Return localized event name.
     *
     * Required language string:
     * - event_atlas_sync_dryrun_completed
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_atlas_sync_dryrun_completed', 'local_uckk');
    }

    /**
     * Return human-readable event description.
     *
     * Keep this description limited to status, scope, counts and hashes.
     *
     * @return string
     */
    public function get_description(): string {
        $userid = (int)$this->userid;
        $result = $this->get_other_string('sync_result');
        $scope = $this->get_other_string('scope');
        $voiecount = $this->get_other_int('voie_count');
        $categorycount = $this->get_other_int('category_count');
        $coursecount = $this->get_other_int('course_count');
        $createcount = $this->get_other_int('create_count');
        $updatecount = $this->get_other_int('update_count');
        $hidecount = $this->get_other_int('hide_count');
        $noopcount = $this->get_other_int('noop_count');
        $errorcount = $this->get_other_int('error_count');
        $warningcount = $this->get_other_int('warning_count');
        $reporthash = $this->get_other_string('report_hash');
        $source = $this->get_other_string('source');

        $description = "The user with id '{$userid}' completed an UCKK Atlas sync dry-run "
            . "with result '{$result}', scope '{$scope}', voie count '{$voiecount}', "
            . "category count '{$categorycount}', course count '{$coursecount}', "
            . "create count '{$createcount}', update count '{$updatecount}', "
            . "hide count '{$hidecount}', noop count '{$noopcount}', "
            . "error count '{$errorcount}' and warning count '{$warningcount}'";

        if ($reporthash !== '') {
            $description .= " with report hash '{$reporthash}'";
        }

        if ($source !== '') {
            $description .= " from source '{$source}'";
        }

        return $description . '.';
    }

    /**
     * Return URL related to the Atlas sync dry-run page.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/uckk/faculty_sync.php', ['mode' => 'dryrun']);
    }

    /**
     * Validate event payload.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        $result = $this->require_other_string('sync_result');
        $scope = $this->require_other_string('scope');

        if (!in_array($result, ['completed', 'completed_with_warnings', 'failed'], true)) {
            throw new coding_exception(
                'atlas_sync_dryrun_completed requires sync_result to be completed, completed_with_warnings or failed.'
            );
        }

        if (!in_array($scope, ['all', 'single_voie', 'selected'], true)) {
            throw new coding_exception(
                'atlas_sync_dryrun_completed requires scope to be all, single_voie or selected.'
            );
        }

        $this->require_other_non_negative_int('voie_count');
        $this->require_other_non_negative_int('category_count');
        $this->require_other_non_negative_int('course_count');
        $this->require_other_non_negative_int('create_count');
        $this->require_other_non_negative_int('update_count');
        $this->require_other_non_negative_int('hide_count');
        $this->require_other_non_negative_int('noop_count');
        $this->require_other_non_negative_int('error_count');
        $this->require_other_non_negative_int('warning_count');

        $reporthash = $this->get_other_string('report_hash');
        if ($reporthash !== '' && !preg_match('/^(sha256:)?[a-f0-9]{64}$/', $reporthash)) {
            throw new coding_exception(
                'atlas_sync_dryrun_completed requires a valid sha256 report_hash when provided.'
            );
        }

        $source = $this->get_other_string('source');
        if ($source !== '' && !preg_match('/^[a-z0-9_:-]+$/', $source)) {
            throw new coding_exception(
                'atlas_sync_dryrun_completed requires a valid source when provided.'
            );
        }
    }

    /**
     * Return a scalar string from the event other payload.
     *
     * @param string $key Payload key.
     * @param string $default Default value.
     * @return string
     */
    private function get_other_string(string $key, string $default = ''): string {
        $value = $this->other[$key] ?? $default;

        if (!is_scalar($value)) {
            return $default;
        }

        return trim((string)$value);
    }

    /**
     * Require a non-empty scalar string from the event other payload.
     *
     * @param string $key Payload key.
     * @return string
     * @throws coding_exception
     */
    private function require_other_string(string $key): string {
        if (!array_key_exists($key, $this->other)) {
            throw new coding_exception("atlas_sync_dryrun_completed requires '{$key}' in other.");
        }

        if (!is_scalar($this->other[$key])) {
            throw new coding_exception("atlas_sync_dryrun_completed requires scalar '{$key}' in other.");
        }

        $value = trim((string)$this->other[$key]);

        if ($value === '') {
            throw new coding_exception("atlas_sync_dryrun_completed requires non-empty '{$key}' in other.");
        }

        return $value;
    }

    /**
     * Return an integer from the event other payload.
     *
     * @param string $key Payload key.
     * @param int $default Default value.
     * @return int
     */
    private function get_other_int(string $key, int $default = 0): int {
        if (!array_key_exists($key, $this->other)) {
            return $default;
        }

        if (!is_numeric($this->other[$key])) {
            return $default;
        }

        return (int)$this->other[$key];
    }

    /**
     * Require a non-negative integer from the event other payload.
     *
     * @param string $key Payload key.
     * @return int
     * @throws coding_exception
     */
    private function require_other_non_negative_int(string $key): int {
        if (!array_key_exists($key, $this->other)) {
            throw new coding_exception("atlas_sync_dryrun_completed requires '{$key}' in other.");
        }

        if (!is_numeric($this->other[$key])) {
            throw new coding_exception("atlas_sync_dryrun_completed requires numeric '{$key}' in other.");
        }

        $value = (int)$this->other[$key];

        if ($value < 0) {
            throw new coding_exception("atlas_sync_dryrun_completed requires non-negative '{$key}' in other.");
        }

        return $value;
    }
}