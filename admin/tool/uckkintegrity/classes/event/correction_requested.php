<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\event;

defined('MOODLE_INTERNAL') || die();

class correction_requested extends \core\event\base {
    protected function init(): void {
        $this->data['objecttable'] = 'tool_uckkintegrity_case';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name(): string {
        return get_string('eventcorrectionrequested', 'tool_uckkintegrity');
    }

    public function get_description(): string {
        return "The user with id '$this->userid' requested a correction for integrity case '$this->objectid'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $this->objectid]);
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'tool_uckkintegrity_case', 'restore' => 'tool_uckkintegrity_case'];
    }
}