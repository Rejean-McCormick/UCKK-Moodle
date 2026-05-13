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
 * Event emitted when a UCKK pathway is created.
 *
 * A UCKK pathway is an internal learning path owned by local_uckk.
 * It may link courses, competencies, badges, challenges, assemblies,
 * archive expectations and portfolio requirements.
 *
 * This event only records the creation of the pathway record. It does not
 * enrol users, assign Moodle roles, grant capabilities, award badges,
 * validate competencies, publish decisions or create archive records.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\event;

use context;
use context_system;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Pathway created event.
 *
 * Expected event data:
 *
 * ```php
 * pathway_created::create([
 *     'context' => $context,
 *     'objectid' => $pathway->id,
 *     'other' => [
 *         'shortname' => $pathway->shortname,
 *         'fullname' => $pathway->fullname,
 *         'programid' => $pathway->programid,
 *         'visibility' => $pathway->visibility,
 *         'status' => $pathway->status,
 *     ],
 * ])->trigger();
 * ```
 *
 * Recommended helper:
 *
 * ```php
 * pathway_created::create_from_pathway($pathway)->trigger();
 * ```
 *
 * @package local_uckk
 */
final class pathway_created extends \core\event\base {
    /**
     * Initialise event properties.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_uckk_pathway';
    }

    /**
     * Return the localised event name.
     *
     * Required language string:
     * - event_pathway_created
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_pathway_created', 'local_uckk');
    }

    /**
     * Return an event description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        $shortname = $this->other['shortname'] ?? '';
        $fullname = $this->other['fullname'] ?? '';
        $programid = isset($this->other['programid']) ? (int)$this->other['programid'] : 0;

        $description = "The user with id '{$this->userid}' created the UCKK pathway with id '{$this->objectid}'";

        if ($shortname !== '') {
            $description .= " and shortname '{$shortname}'";
        }

        if ($fullname !== '') {
            $description .= " named '{$fullname}'";
        }

        if ($programid > 0) {
            $description .= " in UCKK program '{$programid}'";
        }

        $description .= '.';

        return $description;
    }

    /**
     * Return the URL for the created pathway.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/uckk/pathways.php', [
            'id' => $this->objectid,
        ]);
    }

    /**
     * Create the event from a pathway record.
     *
     * This helper keeps event creation consistent across program services,
     * seed tools, CLI scripts and admin pages.
     *
     * Expected pathway fields:
     * - id
     * - shortname
     * - fullname
     *
     * Optional pathway fields:
     * - contextid
     * - programid
     * - visibility
     * - status
     *
     * @param stdClass $pathway Pathway record.
     * @param context|null $context Explicit Moodle context.
     * @return self
     */
    public static function create_from_pathway(stdClass $pathway, ?context $context = null): self {
        if (empty($pathway->id)) {
            throw new \coding_exception('Cannot create pathway_created event without a pathway id.');
        }

        if ($context === null) {
            $context = self::resolve_context_from_pathway($pathway);
        }

        return self::create([
            'context' => $context,
            'objectid' => (int)$pathway->id,
            'other' => [
                'shortname' => isset($pathway->shortname) ? (string)$pathway->shortname : '',
                'fullname' => isset($pathway->fullname) ? (string)$pathway->fullname : '',
                'programid' => isset($pathway->programid) ? (int)$pathway->programid : 0,
                'visibility' => isset($pathway->visibility) ? (string)$pathway->visibility : '',
                'status' => isset($pathway->status) ? (string)$pathway->status : '',
            ],
        ]);
    }

    /**
     * Validate event data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('The objectid must be set for pathway_created events.');
        }

        if (!isset($this->other['shortname'])) {
            throw new \coding_exception('The shortname value must be set in other for pathway_created events.');
        }

        if (!isset($this->other['fullname'])) {
            throw new \coding_exception('The fullname value must be set in other for pathway_created events.');
        }

        if (!isset($this->other['programid'])) {
            throw new \coding_exception('The programid value must be set in other for pathway_created events.');
        }

        if (!isset($this->other['visibility'])) {
            throw new \coding_exception('The visibility value must be set in other for pathway_created events.');
        }

        if (!isset($this->other['status'])) {
            throw new \coding_exception('The status value must be set in other for pathway_created events.');
        }
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array<string, string>
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_uckk_pathway',
            'restore' => 'local_uckk_pathway',
        ];
    }

    /**
     * Return other field mappings for backup and restore.
     *
     * `programid` points to the local_uckk program table when present.
     * Other values are scalar metadata and do not need restore mapping.
     *
     * @return array<string, array<string, string>|false>
     */
    public static function get_other_mapping(): array {
        return [
            'programid' => [
                'db' => 'local_uckk_program',
                'restore' => 'local_uckk_program',
            ],
            'shortname' => false,
            'fullname' => false,
            'visibility' => false,
            'status' => false,
        ];
    }

    /**
     * Resolve context from a pathway record.
     *
     * @param stdClass $pathway Pathway record.
     * @return context
     */
    private static function resolve_context_from_pathway(stdClass $pathway): context {
        if (!empty($pathway->contextid)) {
            $context = context::instance_by_id((int)$pathway->contextid, IGNORE_MISSING);

            if ($context !== false) {
                return $context;
            }
        }

        return context_system::instance();
    }
}