<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\observer;

defined('MOODLE_INTERNAL') || die();

/**
 * Course category observer callbacks for local_uckk.
 *
 * @package    local_uckk
 */
final class category_observer {
    public static function course_category_deleted(\core\event\course_category_deleted $event): void {
        global $DB;

        $categoryid = (int)$event->objectid;
        if (!$DB->get_manager()->table_exists('local_uckk_program')) {
            return;
        }

        $programids = $DB->get_fieldset_select('local_uckk_program', 'id', 'categoryid = :categoryid', ['categoryid' => $categoryid]);
        if (!empty($programids) && $DB->get_manager()->table_exists('local_uckk_pathway')) {
            [$insql, $params] = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_uckk_pathway', "programid $insql", $params);
        }
        $DB->delete_records('local_uckk_program', ['categoryid' => $categoryid]);
    }
}
