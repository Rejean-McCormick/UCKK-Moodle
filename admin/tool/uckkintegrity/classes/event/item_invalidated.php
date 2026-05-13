<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an integrity-sensitive item is invalidated.
 *
 * This event is used when an Inquisiteur records that a challenge proof,
 * assembly decision, archive item, or other UCKK object should no longer be
 * treated as valid evidence.
 *
 * @package    tool_uckkintegrity
 */
class item_invalidated extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['objecttable'] = 'tool_uckkintegrity_case';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return the event display name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventiteminvalidated', 'tool_uckkintegrity');
    }

    /**
     * Return a human-readable event description.
     *
     * Expected keys in $this->other:
     * - subjectcomponent: component owning the invalidated item.
     * - subjectid: related object id.
     *
     * @return string
     */
    public function get_description(): string {
        $subjectcomponent = $this->other['subjectcomponent'] ?? 'unknown component';
        $subjectid = $this->other['subjectid'] ?? 0;

        return "The user with id '{$this->userid}' invalidated item '{$subjectcomponent}:{$subjectid}' " .
            "through integrity case '{$this->objectid}'.";
    }

    /**
     * Return the case URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/uckkintegrity/case.php', [
            'id' => $this->objectid,
        ]);
    }

    /**
     * Return object ID mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'tool_uckkintegrity_case',
            'restore' => 'tool_uckkintegrity_case',
        ];
    }

    /**
     * Validate event data before dispatch.
     *
     * @throws \coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('The objectid must be set to the integrity case id.');
        }

        if (empty($this->other['subjectcomponent'])) {
            throw new \coding_exception('The subjectcomponent value must be provided.');
        }

        if (!isset($this->other['subjectid'])) {
            throw new \coding_exception('The subjectid value must be provided.');
        }
    }
}