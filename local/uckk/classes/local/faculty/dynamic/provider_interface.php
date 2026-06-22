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
 * Public dynamic block provider contract for UCKK faculty pages.
 *
 * Providers are read-only adapters. They receive a dynamic block definition
 * from a Faculty Profile JSON, the resolved faculty profile, and optional page
 * builder context. They must return public, already-filtered items only.
 *
 * Providers must not:
 * - expose private user data;
 * - expose grades, completion, enrolment status, or participants;
 * - expose private forum posts or private calendar events;
 * - modify Moodle data;
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
 * Interface for public faculty dynamic block providers.
 *
 * @package local_uckk
 */
interface provider_interface {
    /**
     * Return public items for one dynamic block.
     *
     * The returned array may be either:
     *
     * ```php
     * [
     *     'items' => [
     *         [
     *             'title' => '',
     *             'summary' => '',
     *             'date' => '',
     *             'url' => '',
     *             'source_label' => '',
     *         ],
     *     ],
     * ]
     * ```
     *
     * or directly:
     *
     * ```php
     * [
     *     [
     *         'title' => '',
     *         'summary' => '',
     *         'date' => '',
     *         'url' => '',
     *         'source_label' => '',
     *     ],
     * ]
     * ```
     *
     * The dispatcher normalises the final Mustache shape.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Resolved and normalised faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed>|array<int, array<string, mixed>> Public block items or payload containing items.
     */
    public function get_items(array $block, array $faculty, array $pagecontext = []): array;
}