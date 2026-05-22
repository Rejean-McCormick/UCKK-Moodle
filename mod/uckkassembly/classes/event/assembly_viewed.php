<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace mod_uckkassembly\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a UCKK assembly is viewed.
 *
 * @package    mod_uckkassembly
 */
final class assembly_viewed extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'uckkassembly';
    }

    public static function get_name(): string {
        $manager = get_string_manager();

        if ($manager->string_exists('eventassemblyviewed', 'uckkassembly')) {
            return get_string('eventassemblyviewed', 'uckkassembly');
        }

        return 'UCKK assembly viewed';
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the UCKK assembly with id " .
            "'{$this->objectid}' in course module id '{$this->contextinstanceid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/uckkassembly/view.php', [
            'id' => $this->contextinstanceid,
        ]);
    }

    public static function get_objectid_mapping(): array {
        return [
            'db' => 'uckkassembly',
            'restore' => 'uckkassembly',
        ];
    }

    public static function get_other_mapping(): array {
        return [
            'courseid' => [
                'db' => 'course',
                'restore' => 'course',
            ],
            'cmid' => [
                'db' => 'course_modules',
                'restore' => 'course_module',
            ],
        ];
    }

    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('The assembly_viewed event must use module context.');
        }

        if (empty($this->objectid)) {
            throw new \coding_exception('The assembly_viewed event requires objectid.');
        }

        if (empty($this->other['courseid'])) {
            throw new \coding_exception('The assembly_viewed event requires courseid in other.');
        }

        if (empty($this->other['cmid'])) {
            throw new \coding_exception('The assembly_viewed event requires cmid in other.');
        }
    }
}
