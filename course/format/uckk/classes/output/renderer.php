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
 * Renderer for the UCKK course format.
 *
 * Moodle 4+ requires every course format plugin to define its own renderer.
 * This renderer intentionally extends the core course format section renderer
 * and lets the UCKK output classes and Mustache templates handle presentation.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace format_uckk\output;

use core_courseformat\output\section_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK course format renderer.
 */
class renderer extends section_renderer {
}