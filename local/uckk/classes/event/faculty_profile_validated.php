<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when an UCKK faculty profile JSON file is validated.
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
 * Event fired when an UCKK faculty profile JSON file is validated.
 *
 * This event is an audit record only. It must not contain the full faculty JSON,
 * Atlas JSON, rendered page content, private user data, grades, progress data,
 * enrolment state, validation internals, stack traces, or any other private
 * payload.
 *
 * Expected creation:
 *
 * ```php
 * $event = \local_uckk\event\faculty_profile_validated::create([
 *     'context' => \context_system::instance(),
 *     'other' => [
 *         'faculty_id' => 'faculty_metaphysique',
 *         'voie_id' => 'voie_metaphysique',
 *         'slug' => 'metaphysique',
 *         'status' => 'published',
 *         'visibility' => 'public',
 *         'validation_result' => 'valid',
 *         'error_count' => 0,
 *         'warning_count' => 0,
 *         'profile_hash' => 'sha256:...',
 *         'source' => 'faculty_validate',
 *     ],
 * ]);
 * $event->trigger();
 * ```
 */
final class faculty_profile_validated extends \core\event\base {
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
     * - event_faculty_profile_validated
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_faculty_profile_validated', 'local_uckk');
    }

    /**
     * Return human-readable event description.
     *
     * Keep this description limited to ids, status, counts and hashes.
     *
     * @return string
     */
    public function get_description(): string {
        $userid = (int)$this->userid;
        $facultyid = $this->get_other_string('faculty_id');
        $voieid = $this->get_other_string('voie_id');
        $slug = $this->get_other_string('slug');
        $status = $this->get_other_string('status');
        $visibility = $this->get_other_string('visibility');
        $result = $this->get_other_string('validation_result');
        $errorcount = $this->get_other_int('error_count');
        $warningcount = $this->get_other_int('warning_count');
        $profilehash = $this->get_other_string('profile_hash');

        $description = "The user with id '{$userid}' validated the UCKK faculty profile "
            . "with slug '{$slug}', faculty id '{$facultyid}', voie id '{$voieid}', "
            . "status '{$status}', visibility '{$visibility}', result '{$result}', "
            . "error count '{$errorcount}' and warning count '{$warningcount}'";

        if ($profilehash !== '') {
            $description .= " with profile hash '{$profilehash}'";
        }

        return $description . '.';
    }

    /**
     * Return URL related to the validation page.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $slug = $this->get_other_string('slug');

        if ($slug === '') {
            return new moodle_url('/local/uckk/faculty_validate.php');
        }

        return new moodle_url('/local/uckk/faculty_validate.php', ['slug' => $slug]);
    }

    /**
     * Validate event payload.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        $facultyid = $this->require_other_string('faculty_id');
        $voieid = $this->get_other_string('voie_id');
        $slug = $this->require_other_string('slug');
        $result = $this->require_other_string('validation_result');

        if (!preg_match('/^faculty_[a-z0-9_]+$/', $facultyid)) {
            throw new coding_exception('faculty_profile_validated requires a valid faculty_id.');
        }

        if ($voieid !== '' && !preg_match('/^voie_[a-z0-9_]+$/', $voieid)) {
            throw new coding_exception('faculty_profile_validated requires a valid voie_id when provided.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new coding_exception('faculty_profile_validated requires a valid faculty slug.');
        }

        if (!in_array($result, ['valid', 'invalid'], true)) {
            throw new coding_exception('faculty_profile_validated requires validation_result to be valid or invalid.');
        }

        $this->require_other_non_negative_int('error_count');
        $this->require_other_non_negative_int('warning_count');

        $profilehash = $this->get_other_string('profile_hash');
        if ($profilehash !== '' && !preg_match('/^(sha256:)?[a-f0-9]{64}$/', $profilehash)) {
            throw new coding_exception('faculty_profile_validated requires a valid sha256 profile_hash when provided.');
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
            throw new coding_exception("faculty_profile_validated requires '{$key}' in other.");
        }

        if (!is_scalar($this->other[$key])) {
            throw new coding_exception("faculty_profile_validated requires scalar '{$key}' in other.");
        }

        $value = trim((string)$this->other[$key]);

        if ($value === '') {
            throw new coding_exception("faculty_profile_validated requires non-empty '{$key}' in other.");
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
            throw new coding_exception("faculty_profile_validated requires '{$key}' in other.");
        }

        if (!is_numeric($this->other[$key])) {
            throw new coding_exception("faculty_profile_validated requires numeric '{$key}' in other.");
        }

        $value = (int)$this->other[$key];

        if ($value < 0) {
            throw new coding_exception("faculty_profile_validated requires non-negative '{$key}' in other.");
        }

        return $value;
    }
}