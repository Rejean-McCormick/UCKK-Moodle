<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider for UCKK institutional reports.
 *
 * The report_uckk plugin displays derived institutional data from other UCKK
 * components. It does not own primary personal data and does not create its own
 * report data tables.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements null_provider {
    /**
     * Explain why this plugin does not store personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}