<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle supports the technical course spaces of the
// Univers-Cité King Klown.

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public courses page definition.
 *
 * This class owns only the static public courses page definition:
 * page layout, hero copy and institutional framing.
 *
 * The live course directory, filters, metadata, empty-state notice and course
 * cards are assembled by /local/uckk/courses.php.
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
            'eyebrow' => 'Entrer dans la bibliothèque',
            'title' => 'Cours UCKK',
            'subtitle' => 'Cours publics, ressources ouvertes et parcours de lecture du campus UCKK.',
            'summary' => 'Les cours servent à diffuser immédiatement le savoir dans un cadre d’apprentissage familier, modernisé et accessible.',
            'sections' => [
                [
                    'title' => 'Une porte d’accès au savoir',
                    'body' => 'Chaque cours rassemble des ressources, activités, repères et pistes de travail pour explorer une question du Grand Jeu social.',
                ],
                [
                    'title' => 'Un cadre familier, modernisé',
                    'body' => 'UCKK utilise la forme reconnaissable du cours pour organiser la lecture, la pratique, la discussion et la mémoire des apprentissages.',
                ],
                [
                    'title' => 'Une bibliothèque vivante',
                    'body' => 'Les cours ne sont pas pensés comme une barrière d’accès au savoir, mais comme des chemins publics pour consulter, comprendre et relier les connaissances.',
                ],
            ],
            'cardsheading' => 'Cours disponibles',
            'cards' => [],
        ];
    }
}