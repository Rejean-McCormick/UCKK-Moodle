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

/**
 * Event fired when a UCKK player profile is updated.
 *
 * This event records changes to the local UCKK player profile layer:
 * display title, symbolic roles, active pathways, visibility, portfolio links
 * and other UCKK profile metadata.
 *
 * It must not be used for Moodle core user profile changes, role assignments,
 * badge awards, competency ratings, archive validation, integrity decisions,
 * or enrolment changes. Those actions belong to their own Moodle events.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\event;

use coding_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Player profile updated event.
 *
 * Expected creation data:
 *
 * ```php
 * $event = \local_uckk\event\player_profile_updated::create([
 *     'context' => \context_user::instance($targetuserid),
 *     'objectid' => $profileid,
 *     'relateduserid' => $targetuserid,
 *     'other' => [
 *         'changedfields' => ['displaytitle', 'symbolicroles'],
 *         'oldvisibility' => 'private',
 *         'newvisibility' => 'course',
 *         'source' => 'profile_form',
 *     ],
 * ]);
 * $event->add_record_snapshot('local_uckk_player', $profilerecord);
 * $event->trigger();
 * ```
 *
 * @package local_uckk
 */
final class player_profile_updated extends \core\event\base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_uckk_player';
    }

    /**
     * Return localized event name.
     *
     * Required language string:
     * - event_player_profile_updated
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_player_profile_updated', 'local_uckk');
    }

    /**
     * Return human-readable event description.
     *
     * @return string
     */
    public function get_description(): string {
        $actor = (int)$this->userid;
        $target = (int)$this->relateduserid;
        $profileid = (int)$this->objectid;

        $changedfields = $this->get_changed_fields();
        $changedfieldsdescription = empty($changedfields)
            ? 'no declared fields'
            : implode(', ', $changedfields);

        return "The user with id '{$actor}' updated the UCKK player profile with id "
            . "'{$profileid}' for the user with id '{$target}'. Changed fields: "
            . "'{$changedfieldsdescription}'.";
    }

    /**
     * Return URL related to the updated profile.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        if (!empty($this->relateduserid)) {
            return new moodle_url('/local/uckk/profile.php', [
                'userid' => $this->relateduserid,
            ]);
        }

        return new moodle_url('/local/uckk/index.php');
    }

    /**
     * Return legacy log data.
     *
     * UCKK-Moodle targets Moodle's modern event system. This method is only
     * provided for compatibility with tooling that still checks legacy data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata(): ?array {
        return null;
    }

    /**
     * Validate event data before triggering.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new coding_exception('The objectid must be set to the UCKK player profile id.');
        }

        if (empty($this->relateduserid)) {
            throw new coding_exception('The relateduserid must be set to the Moodle user id of the UCKK player.');
        }

        if (!isset($this->other['changedfields'])) {
            throw new coding_exception('The changedfields value must be set in other.');
        }

        if (!is_array($this->other['changedfields'])) {
            throw new coding_exception('The changedfields value must be an array.');
        }

        foreach ($this->other['changedfields'] as $field) {
            if (!is_string($field) || trim($field) === '') {
                throw new coding_exception('Every changed field must be a non-empty string.');
            }
        }

        if (isset($this->other['oldvisibility']) && !is_string($this->other['oldvisibility'])) {
            throw new coding_exception('The oldvisibility value must be a string when provided.');
        }

        if (isset($this->other['newvisibility']) && !is_string($this->other['newvisibility'])) {
            throw new coding_exception('The newvisibility value must be a string when provided.');
        }

        if (isset($this->other['source']) && !is_string($this->other['source'])) {
            throw new coding_exception('The source value must be a string when provided.');
        }
    }

    /**
     * Return object id mapping for backup/restore.
     *
     * The local_uckk_player table is a local institutional profile table.
     * It is not restored as a normal course activity object.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_uckk_player',
            'restore' => 'local_uckk_player',
        ];
    }

    /**
     * Return mappings for values stored in the "other" field.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [];
    }

    /**
     * Return fields changed during this update.
     *
     * @return string[]
     */
    public function get_changed_fields(): array {
        if (empty($this->other['changedfields']) || !is_array($this->other['changedfields'])) {
            return [];
        }

        $fields = [];

        foreach ($this->other['changedfields'] as $field) {
            $field = trim((string)$field);
            if ($field !== '') {
                $fields[] = $field;
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * Return old profile visibility if supplied.
     *
     * @return string
     */
    public function get_old_visibility(): string {
        return isset($this->other['oldvisibility'])
            ? (string)$this->other['oldvisibility']
            : '';
    }

    /**
     * Return new profile visibility if supplied.
     *
     * @return string
     */
    public function get_new_visibility(): string {
        return isset($this->other['newvisibility'])
            ? (string)$this->other['newvisibility']
            : '';
    }

    /**
     * Return event source if supplied.
     *
     * Examples:
     * - profile_form
     * - seed_tool
     * - external_service
     * - pathway_sync
     * - admin_action
     *
     * @return string
     */
    public function get_source(): string {
        return isset($this->other['source'])
            ? (string)$this->other['source']
            : '';
    }

    /**
     * Create and trigger the event from a profile record.
     *
     * This helper keeps event creation consistent across services, forms,
     * admin tools and external functions.
     *
     * @param \stdClass $profile UCKK player profile record.
     * @param string[] $changedfields Changed field names.
     * @param array<string, mixed> $other Extra event data.
     * @return self
     */
    public static function create_from_profile(\stdClass $profile, array $changedfields, array $other = []): self {
        if (empty($profile->id)) {
            throw new coding_exception('Profile record must include an id.');
        }

        if (empty($profile->userid)) {
            throw new coding_exception('Profile record must include a userid.');
        }

        $userid = (int)$profile->userid;
        $context = \context_user::instance($userid, MUST_EXIST);

        $other['changedfields'] = array_values(array_unique(array_filter(array_map(static function($field): string {
            return trim((string)$field);
        }, $changedfields))));

        $event = self::create([
            'context' => $context,
            'objectid' => (int)$profile->id,
            'relateduserid' => $userid,
            'other' => $other,
        ]);

        $event->add_record_snapshot('local_uckk_player', $profile);

        return $event;
    }
}