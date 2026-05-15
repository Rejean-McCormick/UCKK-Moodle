<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\task;

use local_uckk\service\pathway_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task rebuilding pathway statistics.
 *
 * @package    local_uckk
 */
final class rebuild_pathway_statistics extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('pathwayprogress', 'local_uckk');
    }

    public function execute(): void {
        (new pathway_service())->rebuild_pathway_statistics();
    }
}
