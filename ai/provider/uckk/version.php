<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Version metadata for the UCKK governed AI provider.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->component = 'aiprovider_uckk';
$plugin->version = 2026051200;
$plugin->requires = 2025100600;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';

$plugin->dependencies = [
    'local_uckk' => 2026051200,
];