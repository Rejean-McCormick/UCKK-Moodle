<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\observer;

use local_uckk\service\pathway_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Course observer callbacks for local_uckk.
 *
 * @package    local_uckk
 */
final class course_observer {
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        $courseid = (int)$event->objectid;
        if (!$DB->get_manager()->table_exists('local_uckk_pathway')) {
            return;
        }

        $service = new pathway_service();
        $records = $DB->get_records('local_uckk_pathway');
        foreach ($records as $record) {
            $required = json_decode((string)$record->requiredcourseids, true);
            if (!is_array($required) || !in_array($courseid, array_map('intval', $required), true)) {
                continue;
            }
            $required = array_values(array_filter(array_map('intval', $required), static fn(int $id): bool => $id !== $courseid));
            $service->update_pathway((int)$record->id, ['requiredcourseids' => $required]);
        }
    }
}
