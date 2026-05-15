<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a player profile is updated.
 *
 * @package    local_uckk
 */
final class profile_updated extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_uckk_player';
    }

    public static function get_name(): string {
        if (get_string_manager()->string_exists('event_player_profile_updated', 'local_uckk')) {
            return get_string('event_player_profile_updated', 'local_uckk');
        }
        return 'UCKK player profile updated';
    }

    public function get_description(): string {
        return "The UCKK player profile '{$this->objectid}' for user '{$this->relateduserid}' was updated.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/user/profile.php', ['id' => $this->relateduserid]);
    }

    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->relateduserid)) {
            throw new \coding_exception('Profile updated event requires relateduserid.');
        }
    }
}
