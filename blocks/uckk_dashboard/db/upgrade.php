<?php
// This file is part of UCKK-Moodle.
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for block_uckk_dashboard.
 *
 * This file must remain self-contained:
 * - no require_once;
 * - no calls to plugin classes or plugin helper functions;
 * - no dashboard rendering logic;
 * - no permissions, workflow, or data ownership changes.
 *
 * The block currently stores no custom database tables. Per-instance block
 * configuration is handled by Moodle's block instance configuration storage.
 *
 * @param int $oldversion The currently installed plugin version.
 * @return bool
 */
function xmldb_block_uckk_dashboard_upgrade(int $oldversion): bool {
    if ($oldversion < 2026051200) {
        // Initial stable UCKK-Moodle dashboard block version.
        //
        // No schema migration is required here because a fresh installation is
        // handled by Moodle's plugin installation process. This savepoint exists
        // only for sites that may have installed an earlier development build.
        upgrade_block_savepoint(true, 2026051200, 'uckk_dashboard');
    }

    return true;
}