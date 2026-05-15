<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook listeners for local_uckk.
 *
 * @package    local_uckk
 */
final class hook_listener {
    /**
     * Configure container hook.
     *
     * This listener is intentionally conservative. Standalone-core local_uckk
     * does not require service-container rewrites to function, so discovery can
     * safely call this method with no side effects.
     *
     * @param mixed $hook Hook object.
     * @return void
     */
    public static function configure_container($hook): void {
        // No-op by design for standalone-core.
        // The class exists so Moodle hook discovery succeeds and future
        // connected-mode bindings can be added without changing db/hooks.php.
    }
}
