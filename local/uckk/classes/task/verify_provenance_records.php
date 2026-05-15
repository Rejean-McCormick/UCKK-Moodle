<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\task;

use local_uckk\local\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task verifying provenance hashes in local_uckk-owned tables.
 *
 * @package    local_uckk
 */
final class verify_provenance_records extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('provenance', 'local_uckk');
    }

    public function execute(): void {
        global $DB;

        $tables = [
            'local_uckk_program' => 'shortname',
            'local_uckk_pathway' => 'shortname',
            'local_uckk_player' => 'userid',
            'local_uckk_map' => 'mapkey',
            'local_uckk_pathway_stat' => 'pathwayid',
        ];

        foreach ($tables as $table => $keyfield) {
            if (!$DB->get_manager()->table_exists($table)) {
                continue;
            }
            foreach ($DB->get_records($table) as $record) {
                $payload = (string)($record->metadata ?? ($record->statdata ?? ''));
                $key = (string)($record->{$keyfield} ?? $record->id);
                $time = (int)($record->timemodified ?? $record->timecreated ?? time());
                $hash = hash('sha256', implode('|', [constants::COMPONENT, $table, $key, $payload, (string)$time]));
                if ((string)($record->provenancehash ?? '') !== $hash) {
                    $record->provenancehash = $hash;
                    $DB->update_record($table, $record);
                }
            }
        }
    }
}
