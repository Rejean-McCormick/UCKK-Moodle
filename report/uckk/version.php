<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Version metadata for the UCKK institutional reports plugin.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->component = 'report_uckk';
$plugin->version   = 2026051200;
$plugin->requires  = 2025100600;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => ANY_VERSION,
    'mod_uckkchallenge' => ANY_VERSION,
    'mod_uckkassembly' => ANY_VERSION,
    'mod_uckkarchive' => ANY_VERSION,
    'tool_uckkintegrity' => ANY_VERSION,
];