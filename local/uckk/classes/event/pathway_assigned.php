<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a pathway is assigned to a player.
 *
 * @package    local_uckk
 */
final class pathway_assigned extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_uckk_pathway';
    }

    public static function get_name(): string {
        if (get_string_manager()->string_exists('event_pathway_assigned', 'local_uckk')) {
            return get_string('event_pathway_assigned', 'local_uckk');
        }
        return 'UCKK pathway assigned';
    }

    public function get_description(): string {
        return "The user with id '{$this->relateduserid}' was assigned UCKK pathway '{$this->objectid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/uckk/pathways.php', ['id' => $this->objectid]);
    }

    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->relateduserid)) {
            throw new \coding_exception('Pathway assignment event requires relateduserid.');
        }
        if (!array_key_exists('assignmentid', $this->other)) {
            throw new \coding_exception('Pathway assignment event requires assignmentid in other[].');
        }
    }
}
