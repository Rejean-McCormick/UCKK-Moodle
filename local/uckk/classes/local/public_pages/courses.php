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

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public courses page definition.
 *
 * This class owns only the static public courses page definition:
 * page layout, hero copy and institutional framing.
 *
 * The live course directory, filters, metadata, empty-state notice and Moodle
 * course cards are assembled by /local/uckk/courses.php.
 *
 * It must not read Moodle courses, enrol users, mutate Moodle data,
 * award recognitions, validate work, or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class courses {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'institutional',
            'eyebrow' => 'Entrer dans les apprentissages',
            'title' => 'Cours UCKK',
            'subtitle' => 'Espaces Moodle publics ou accessibles selon les rôles.',
            'summary' => 'Les cours contiennent les activités, ressources et évaluations pédagogiques du campus.',
            'sections' => [],
            'cardsheading' => '',
            'cards' => [],
        ];
    }
}