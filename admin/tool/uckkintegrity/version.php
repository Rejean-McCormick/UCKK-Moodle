<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'tool_uckkintegrity';
$plugin->version = 2026051200;
$plugin->requires = 2025100600;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
$plugin->dependencies = [
    'local_uckk' => ANY_VERSION,
    'mod_uckkarchive' => ANY_VERSION,
    'mod_uckkchallenge' => ANY_VERSION,
    'mod_uckkassembly' => ANY_VERSION,
];