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
 * Event emitted when a UCKK canon item is updated.
 *
 * A UCKK canon item may represent a rule, principle, definition, program
 * reference, governance text, symbolic boundary, methodological statement,
 * or institutional doctrine owned by local_uckk.
 *
 * This event records that a canon item changed. It does not validate the
 * change, publish an assembly decision, create an archive version, resolve
 * an integrity dispute, or decide institutional legitimacy.
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
 * Canon item updated event.
 *
 * Expected event data:
 *
 * ```php
 * canon_item_updated::create([
 *     'context' => $context,
 *     'objectid' => $canonitem->id,
 *     'other' => [
 *         'itemkey' => $canonitem->itemkey,
 *         'itemtype' => $canonitem->itemtype,
 *         'title' => $canonitem->title,
 *         'previousversion' => $previousversion,
 *         'newversion' => $canonitem->versionno,
 *         'visibility' => $canonitem->visibility,
 *         'status' => $canonitem->status,
 *         'provenanceid' => $canonitem->provenanceid,
 *     ],
 * ])->trigger();
 * ```
 *
 * Recommended helper:
 *
 * ```php
 * canon_item_updated::create_from_canon_item($canonitem, $previousversion)->trigger();
 * ```
 *
 * @package local_uckk
 */
final class canon_item_updated extends \core\event\base {
    /**
     * Initialise event properties.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_uckk_canon';
    }

    /**
     * Return the localised event name.
     *
     * Required language string:
     * - event_canon_item_updated
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_canon_item_updated', 'local_uckk');
    }

    /**
     * Return an event description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        $itemkey = $this->other['itemkey'] ?? '';
        $itemtype = $this->other['itemtype'] ?? '';
        $title = $this->other['title'] ?? '';
        $previousversion = isset($this->other['previousversion']) ? (int)$this->other['previousversion'] : 0;
        $newversion = isset($this->other['newversion']) ? (int)$this->other['newversion'] : 0;

        $description = "The user with id '{$this->userid}' updated the UCKK canon item with id '{$this->objectid}'";

        if ($itemkey !== '') {
            $description .= " and key '{$itemkey}'";
        }

        if ($itemtype !== '') {
            $description .= " of type '{$itemtype}'";
        }

        if ($title !== '') {
            $description .= " titled '{$title}'";
        }

        if ($previousversion > 0 || $newversion > 0) {
            $description .= " from version '{$previousversion}' to version '{$newversion}'";
        }

        $description .= '.';

        return $description;
    }

    /**
     * Return the URL for the updated canon item.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/uckk/canon.php', [
            'id' => $this->objectid,
        ]);
    }

    /**
     * Create the event from a canon item record.
     *
     * This helper keeps event creation consistent across admin pages,
     * service classes, seed tools and CLI scripts.
     *
     * Expected canon item fields:
     * - id
     * - itemkey
     * - itemtype
     * - title
     *
     * Optional canon item fields:
     * - contextid
     * - versionno
     * - visibility
     * - status
     * - provenanceid
     * - parentid
     *
     * @param stdClass $canonitem Canon item record.
     * @param int|null $previousversion Previous version number.
     * @param context|null $context Explicit Moodle context.
     * @return self
     */
    public static function create_from_canon_item(
        stdClass $canonitem,
        ?int $previousversion = null,
        ?context $context = null
    ): self {
        if (empty($canonitem->id)) {
            throw new \coding_exception('Cannot create canon_item_updated event without a canon item id.');
        }

        if ($context === null) {
            $context = self::resolve_context_from_canon_item($canonitem);
        }

        $newversion = isset($canonitem->versionno) ? (int)$canonitem->versionno : 0;

        return self::create([
            'context' => $context,
            'objectid' => (int)$canonitem->id,
            'other' => [
                'itemkey' => isset($canonitem->itemkey) ? (string)$canonitem->itemkey : '',
                'itemtype' => isset($canonitem->itemtype) ? (string)$canonitem->itemtype : '',
                'title' => isset($canonitem->title) ? (string)$canonitem->title : '',
                'previousversion' => $previousversion ?? 0,
                'newversion' => $newversion,
                'visibility' => isset($canonitem->visibility) ? (string)$canonitem->visibility : '',
                'status' => isset($canonitem->status) ? (string)$canonitem->status : '',
                'provenanceid' => isset($canonitem->provenanceid) ? (int)$canonitem->provenanceid : 0,
                'parentid' => isset($canonitem->parentid) ? (int)$canonitem->parentid : 0,
            ],
        ]);
    }

    /**
     * Create the event from before and after canon records.
     *
     * This helper is useful when a service already has the previous record.
     *
     * @param stdClass $before Canon item before update.
     * @param stdClass $after Canon item after update.
     * @param context|null $context Explicit Moodle context.
     * @return self
     */
    public static function create_from_change(
        stdClass $before,
        stdClass $after,
        ?context $context = null
    ): self {
        if (empty($after->id)) {
            throw new \coding_exception('Cannot create canon_item_updated event without the updated canon item id.');
        }

        if (!empty($before->id) && (int)$before->id !== (int)$after->id) {
            throw new \coding_exception('Cannot create canon_item_updated event from two different canon items.');
        }

        $previousversion = isset($before->versionno) ? (int)$before->versionno : 0;

        return self::create_from_canon_item($after, $previousversion, $context);
    }

    /**
     * Validate event data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('The objectid must be set for canon_item_updated events.');
        }

        if (!isset($this->other['itemkey'])) {
            throw new \coding_exception('The itemkey value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['itemtype'])) {
            throw new \coding_exception('The itemtype value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['title'])) {
            throw new \coding_exception('The title value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['previousversion'])) {
            throw new \coding_exception('The previousversion value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['newversion'])) {
            throw new \coding_exception('The newversion value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['visibility'])) {
            throw new \coding_exception('The visibility value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['status'])) {
            throw new \coding_exception('The status value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['provenanceid'])) {
            throw new \coding_exception('The provenanceid value must be set in other for canon_item_updated events.');
        }

        if (!isset($this->other['parentid'])) {
            throw new \coding_exception('The parentid value must be set in other for canon_item_updated events.');
        }

        if ((int)$this->other['previousversion'] < 0) {
            throw new \coding_exception('The previousversion value cannot be negative.');
        }

        if ((int)$this->other['newversion'] < 0) {
            throw new \coding_exception('The newversion value cannot be negative.');
        }

        if ((int)$this->other['provenanceid'] < 0) {
            throw new \coding_exception('The provenanceid value cannot be negative.');
        }

        if ((int)$this->other['parentid'] < 0) {
            throw new \coding_exception('The parentid value cannot be negative.');
        }
    }

    /**
     * Return object id mapping for backup and restore.
     *
     * @return array<string, string>
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_uckk_canon',
            'restore' => 'local_uckk_canon',
        ];
    }

    /**
     * Return other field mappings for backup and restore.
     *
     * Only ids pointing to Moodle or plugin records need mappings.
     * Scalar metadata such as itemkey, itemtype, title, status and visibility
     * does not need restore mapping.
     *
     * @return array<string, array<string, string>>
     */
    public static function get_other_mapping(): array {
        return [
            'provenanceid' => [
                'db' => 'local_uckk_prov',
                'restore' => 'local_uckk_prov',
            ],
            'parentid' => [
                'db' => 'local_uckk_canon',
                'restore' => 'local_uckk_canon',
            ],
        ];
    }

    /**
     * Determine whether this update changed the canon version number.
     *
     * @return bool
     */
    public function version_changed(): bool {
        return (int)$this->other['previousversion'] !== (int)$this->other['newversion'];
    }

    /**
     * Determine whether this event has linked provenance.
     *
     * @return bool
     */
    public function has_provenance(): bool {
        return !empty($this->other['provenanceid']);
    }

    /**
     * Determine whether this item has a parent canon item.
     *
     * @return bool
     */
    public function has_parent(): bool {
        return !empty($this->other['parentid']);
    }

    /**
     * Resolve context from a canon item record.
     *
     * @param stdClass $canonitem Canon item record.
     * @return context
     */
    private static function resolve_context_from_canon_item(stdClass $canonitem): context {
        if (!empty($canonitem->contextid)) {
            $context = context::instance_by_id((int)$canonitem->contextid, IGNORE_MISSING);

            if ($context !== false) {
                return $context;
            }
        }

        return context_system::instance();
    }
}