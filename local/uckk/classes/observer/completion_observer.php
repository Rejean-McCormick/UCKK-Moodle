<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\observer;

use local_uckk\local\constants;
use local_uckk\service\pathway_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Completion observer callbacks for local_uckk.
 *
 * @package    local_uckk
 */
final class completion_observer {
    public static function course_completed(\core\event\course_completed $event): void {
        global $DB;

        $userid = (int)$event->relateduserid;
        $courseid = (int)$event->courseid;
        if ($userid <= 0 || $courseid <= 0 || !$DB->get_manager()->table_exists('local_uckk_pathway')) {
            return;
        }

        $service = new pathway_service();
        foreach ($DB->get_records('local_uckk_pathway') as $pathway) {
            $required = json_decode((string)$pathway->requiredcourseids, true);
            $required = is_array($required) ? array_map('intval', $required) : [];
            if (!in_array($courseid, $required, true)) {
                continue;
            }

            $state = null;
            try {
                $state = $service->get_user_pathway_state($userid, (int)$pathway->id);
            } catch (\Throwable $e) {
                $state = null;
            }
            if (!$state) {
                continue;
            }

            $progress = count($required) === 1 ? constants::PROGRESS_COMPLETED : constants::PROGRESS_IN_PROGRESS;
            $service->set_pathway_progress($userid, (int)$pathway->id, $progress, (int)($event->userid ?? 0));
        }

        $service->rebuild_pathway_statistics($userid);
    }
}
