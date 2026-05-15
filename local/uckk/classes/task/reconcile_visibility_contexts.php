<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\task;

use context;
use context_coursecat;
use context_system;
use local_uckk\local\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task reconciling visibility and context ids.
 *
 * @package    local_uckk
 */
final class reconcile_visibility_contexts extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('visibility', 'local_uckk');
    }

    public function execute(): void {
        global $DB;

        $tables = ['local_uckk_program', 'local_uckk_pathway', 'local_uckk_player', 'local_uckk_map', 'local_uckk_pathway_stat'];
        foreach ($tables as $table) {
            if (!$DB->get_manager()->table_exists($table)) {
                continue;
            }
            foreach ($DB->get_records($table) as $record) {
                $changed = false;

                if (property_exists($record, 'visibility')) {
                    $normalised = constants::normalise_key((string)$record->visibility);
                    if (!in_array($normalised, constants::allowed_visibilities(), true)) {
                        $record->visibility = constants::VISIBILITY_INSTITUTION;
                        $changed = true;
                    }
                }

                if (property_exists($record, 'contextid') && empty($record->contextid)) {
                    if ($table === 'local_uckk_program' && !empty($record->categoryid)) {
                        $record->contextid = context_coursecat::instance((int)$record->categoryid)->id;
                    } elseif ($table === 'local_uckk_pathway' && !empty($record->programid)) {
                        $program = $DB->get_record('local_uckk_program', ['id' => (int)$record->programid], 'id, contextid', IGNORE_MISSING);
                        $record->contextid = $program ? (int)$program->contextid : context_system::instance()->id;
                    } elseif ($table === 'local_uckk_player' && !empty($record->userid)) {
                        $record->contextid = \context_user::instance((int)$record->userid)->id;
                    } else {
                        $record->contextid = context_system::instance()->id;
                    }
                    $changed = true;
                }

                if ($changed) {
                    $DB->update_record($table, $record);
                }
            }
        }
    }
}
