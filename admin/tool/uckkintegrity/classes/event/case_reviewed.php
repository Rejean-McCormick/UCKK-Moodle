<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Integrity case reviewed event.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class case_reviewed extends \core\event\base {

    /**
     * Initialise event.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_uckkintegrity_case';
    }

    /**
     * Human-readable event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcasereviewed', 'tool_uckkintegrity');
    }

    /**
     * Event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' reviewed the integrity case with id '{$this->objectid}'.";
    }

    /**
     * URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/uckkintegrity/review.php', ['id' => $this->objectid]);
    }
}