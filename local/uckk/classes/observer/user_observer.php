<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\observer;

use local_uckk\local\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * User observer callbacks for local_uckk.
 *
 * @package    local_uckk
 */
final class user_observer {
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        $userid = (int)$event->objectid;

        foreach (['local_uckk_player', 'local_uckk_pathway_stat', 'local_uckk_reflect'] as $table) {
            if ($DB->get_manager()->table_exists($table)) {
                $DB->delete_records($table, ['userid' => $userid]);
            }
        }

        if ($DB->get_manager()->table_exists('local_uckk_map')) {
            $DB->delete_records('local_uckk_map', ['userid' => $userid]);
            $DB->delete_records('local_uckk_map', ['sourcecomponent' => constants::COMPONENT . '_player', 'sourceid' => $userid]);
        }

        if ($DB->get_manager()->table_exists('local_uckk_prov')) {
            $DB->delete_records('local_uckk_prov', ['userid' => $userid]);
        }
    }
}
