<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when a public UCKK faculty profile page is viewed.
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
 * Event fired when a public UCKK faculty profile page is viewed.
 *
 * This event is an audit record only. It must not contain the full faculty JSON,
 * Atlas JSON, rendered page content, user private data, grades, progress data,
 * enrolment state, IP addresses, or any other non-public payload.
 *
 * Expected creation:
 *
 * ```php
 * $event = \local_uckk\event\faculty_profile_viewed::create([
 *     'context' => \context_system::instance(),
 *     'other' => [
 *         'faculty_id' => 'faculty_metaphysique',
 *         'voie_id' => 'voie_metaphysique',
 *         'slug' => 'metaphysique',
 *         'status' => 'published',
 *         'visibility' => 'public',
 *     ],
 * ]);
 * $event->trigger();
 * ```
 */
final class faculty_profile_viewed extends \core\event\base {
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
     * - event_faculty_profile_viewed
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_faculty_profile_viewed', 'local_uckk');
    }

    /**
     * Return human-readable event description.
     *
     * Keep this description limited to ids, status and visibility.
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

        return "The user with id '{$userid}' viewed the UCKK faculty profile "
            . "with slug '{$slug}', faculty id '{$facultyid}', voie id '{$voieid}', "
            . "status '{$status}' and visibility '{$visibility}'.";
    }

    /**
     * Return URL related to the viewed faculty profile.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $slug = $this->get_other_string('slug');

        if ($slug === '') {
            return new moodle_url('/local/uckk/faculty.php');
        }

        return new moodle_url('/local/uckk/faculty.php', ['slug' => $slug]);
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
        $voieid = $this->require_other_string('voie_id');
        $slug = $this->require_other_string('slug');
        $this->require_other_string('status');
        $this->require_other_string('visibility');

        if (!preg_match('/^faculty_[a-z0-9_]+$/', $facultyid)) {
            throw new coding_exception('faculty_profile_viewed requires a valid faculty_id.');
        }

        if (!preg_match('/^voie_[a-z0-9_]+$/', $voieid)) {
            throw new coding_exception('faculty_profile_viewed requires a valid voie_id.');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new coding_exception('faculty_profile_viewed requires a valid faculty slug.');
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
            throw new coding_exception("faculty_profile_viewed requires '{$key}' in other.");
        }

        if (!is_scalar($this->other[$key])) {
            throw new coding_exception("faculty_profile_viewed requires scalar '{$key}' in other.");
        }

        $value = trim((string)$this->other[$key]);

        if ($value === '') {
            throw new coding_exception("faculty_profile_viewed requires non-empty '{$key}' in other.");
        }

        return $value;
    }
}