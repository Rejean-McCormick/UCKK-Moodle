<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Empty dynamic block provider for public UCKK faculty pages.
 *
 * This provider implements the documented "none" source provider:
 *
 * {
 *     "provider": "none"
 * }
 *
 * It is a null-object provider used for placeholders, intentionally inactive
 * blocks, or public page sections that should render their empty_state without
 * reading any live content.
 *
 * It does not:
 * - read Moodle data;
 * - read local_uckk data;
 * - expose private data;
 * - create fallback content;
 * - modify Moodle state;
 * - decide template permissions.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

defined('MOODLE_INTERNAL') || die();

/**
 * Null provider for placeholder dynamic blocks.
 *
 * @package local_uckk
 */
final class none_provider implements provider_interface {
    /** Source provider name. */
    public const PROVIDER = 'none';

    /**
     * Return no public items.
     *
     * The dispatcher is responsible for keeping the block shell, including:
     * id, type, title, empty_state, has_items, and visibility.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Resolved and normalised faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed> Empty payload containing no items.
     */
    public function get_items(array $block, array $faculty, array $pagecontext = []): array {
        return [
            'items' => [],
        ];
    }
}