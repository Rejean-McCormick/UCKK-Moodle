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
 * Event emitted when a UCKK pathway is completed.
 *
 * A pathway completion is an institutional learning event. It may be used by
 * reports, dashboards, badges, archive workflows, notification systems and
 * integration services.
 *
 * This event must not itself:
 * - award badges;
 * - validate archives;
 * - close integrity cases;
 * - certify public accreditation;
 * - modify pathway data;
 * - perform external side effects directly.
 *
 * Those actions must be handled by services, observers, scheduled tasks or
 * dedicated plugins with their own capability checks.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\event;

use coding_exception;
use context;
use context_user;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Pathway completed event.
 *
 * Expected event data:
 *
 * ```php
 * pathway_completed::create([
 *     'context' => context_user::instance($userid),
 *     'objectid' => $pathwayid,
 *     'relateduserid' => $userid,
 *     'other' => [
 *         'programid' => $programid,
 *         'pathwaykey' => 'tronc_commun',
 *         'pathwayname' => 'Tronc commun obligatoire',
 *         'completiontime' => time(),
 *         'completionstate' => 'completed',
 *         'sourcecomponent' => 'local_uckk',
 *         'archiveitemid' => null,
 *         'badgekeys' => [
 *             'joueur_initie',
 *             'joueur_lucide',
 *         ],
 *     ],
 * ])->trigger();
 * ```
 *
 * @package local_uckk
 */
class pathway_completed extends \core\event\base {
    /**
     * Initialise the event.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_uckk_pathway';
    }

    /**
     * Return the localized event name.
     *
     * Required language string:
     * - event_pathway_completed
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_pathway_completed', 'local_uckk');
    }

    /**
     * Return a human-readable event description.
     *
     * @return string
     */
    public function get_description(): string {
        $userid = $this->relateduserid ?? 0;
        $pathwayid = $this->objectid;
        $programid = $this->other['programid'] ?? 0;
        $pathwayname = $this->other['pathwayname'] ?? '';
        $pathwaykey = $this->other['pathwaykey'] ?? '';

        if ($pathwayname !== '') {
            return "The user with id '{$userid}' completed the UCKK pathway '{$pathwayname}' "
                . "with id '{$pathwayid}' in program id '{$programid}'.";
        }

        if ($pathwaykey !== '') {
            return "The user with id '{$userid}' completed the UCKK pathway '{$pathwaykey}' "
                . "with id '{$pathwayid}' in program id '{$programid}'.";
        }

        return "The user with id '{$userid}' completed the UCKK pathway with id '{$pathwayid}' "
            . "in program id '{$programid}'.";
    }

    /**
     * Return the URL related to this event.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $params = [];

        if (!empty($this->relateduserid)) {
            $params['userid'] = $this->relateduserid;
        }

        if (!empty($this->objectid)) {
            $params['pathwayid'] = $this->objectid;
        }

        if (!empty($this->other['programid'])) {
            $params['programid'] = $this->other['programid'];
        }

        return new moodle_url('/local/uckk/pathways.php', $params);
    }

    /**
     * Validate event data.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new coding_exception('The pathway_completed event requires objectid.');
        }

        if (empty($this->relateduserid)) {
            throw new coding_exception('The pathway_completed event requires relateduserid.');
        }

        if (empty($this->other['programid'])) {
            throw new coding_exception('The pathway_completed event requires other[programid].');
        }

        if (empty($this->other['pathwaykey']) && empty($this->other['pathwayname'])) {
            throw new coding_exception('The pathway_completed event requires other[pathwaykey] or other[pathwayname].');
        }

        if (empty($this->other['completiontime'])) {
            throw new coding_exception('The pathway_completed event requires other[completiontime].');
        }

        if (empty($this->other['completionstate'])) {
            throw new coding_exception('The pathway_completed event requires other[completionstate].');
        }

        if (!is_int($this->other['completiontime'])) {
            throw new coding_exception('The pathway_completed event requires other[completiontime] to be an integer timestamp.');
        }

        if (!is_string($this->other['completionstate'])) {
            throw new coding_exception('The pathway_completed event requires other[completionstate] to be a string.');
        }

        if (!empty($this->other['badgekeys']) && !is_array($this->other['badgekeys'])) {
            throw new coding_exception('The pathway_completed event requires other[badgekeys] to be an array when provided.');
        }
    }

    /**
     * Return a snapshot of data associated with the event.
     *
     * This helps observers and report code avoid re-querying basic pathway
     * identity data when the event already contains it.
     *
     * @return array<string, mixed>
     */
    public function get_pathway_snapshot(): array {
        return [
            'pathwayid' => $this->objectid,
            'programid' => $this->other['programid'] ?? null,
            'pathwaykey' => $this->other['pathwaykey'] ?? null,
            'pathwayname' => $this->other['pathwayname'] ?? null,
            'userid' => $this->relateduserid,
            'completiontime' => $this->other['completiontime'] ?? null,
            'completionstate' => $this->other['completionstate'] ?? null,
            'sourcecomponent' => $this->other['sourcecomponent'] ?? 'local_uckk',
            'archiveitemid' => $this->other['archiveitemid'] ?? null,
            'badgekeys' => $this->other['badgekeys'] ?? [],
        ];
    }

    /**
     * Create a pathway completed event with a consistent UCKK payload.
     *
     * This factory is optional but keeps event creation consistent across
     * services, tasks and observers.
     *
     * @param int $userid User who completed the pathway.
     * @param int $pathwayid UCKK pathway id.
     * @param int $programid UCKK program id.
     * @param string $pathwaykey Stable pathway key.
     * @param string $pathwayname Human-readable pathway name.
     * @param context|null $context Event context. Defaults to user context.
     * @param array<string, mixed> $extra Extra event data for "other".
     * @return self
     */
    public static function create_from_pathway_completion(
        int $userid,
        int $pathwayid,
        int $programid,
        string $pathwaykey,
        string $pathwayname,
        ?context $context = null,
        array $extra = []
    ): self {
        $context = $context ?? context_user::instance($userid);

        $other = array_merge([
            'programid' => $programid,
            'pathwaykey' => $pathwaykey,
            'pathwayname' => $pathwayname,
            'completiontime' => time(),
            'completionstate' => 'completed',
            'sourcecomponent' => 'local_uckk',
            'archiveitemid' => null,
            'badgekeys' => [],
        ], $extra);

        return self::create([
            'context' => $context,
            'objectid' => $pathwayid,
            'relateduserid' => $userid,
            'other' => $other,
        ]);
    }

    /**
     * Return whether this event should be considered internal recognition.
     *
     * UCKK pathway completion is an internal UCKK learning recognition. It is
     * not a public or state-accredited diploma by itself.
     *
     * @return bool
     */
    public function is_internal_recognition(): bool {
        return true;
    }

    /**
     * Return whether the pathway completion has linked badge keys.
     *
     * @return bool
     */
    public function has_badge_keys(): bool {
        return !empty($this->other['badgekeys']) && is_array($this->other['badgekeys']);
    }

    /**
     * Return linked badge keys.
     *
     * @return string[]
     */
    public function get_badge_keys(): array {
        if (!$this->has_badge_keys()) {
            return [];
        }

        return array_values(array_filter($this->other['badgekeys'], static function($badgekey): bool {
            return is_string($badgekey) && trim($badgekey) !== '';
        }));
    }
}