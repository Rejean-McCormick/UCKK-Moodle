<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

namespace theme_uckk\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\null_provider;

/**
 * Privacy provider for theme_uckk.
 *
 * @package    theme_uckk
 */
final class provider implements metadata_provider, null_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('theme_config', ['name' => 'Configuration name', 'value' => 'Configuration value'], get_string('privacy:metadata', 'theme_uckk'));
        return $collection;
    }

    public static function get_reason(): string {
        return get_string('privacy:metadata', 'theme_uckk');
    }
}
