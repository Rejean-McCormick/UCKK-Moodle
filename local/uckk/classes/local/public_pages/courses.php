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
 * page layout, hero copy and public editorial framing.
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
            'eyebrow' => 'Apprendre, pratiquer, produire',
            'title' => 'Cours publics UCKK',
            'subtitle' => 'Des portes d’entrée vers les Voies, les méthodes et les pratiques de l’Univers-Cité King Klown.',
            'summary' => 'Les cours publics UCKK servent à comprendre le Grand Jeu social, à pratiquer des méthodes, à produire des artefacts et à relier les savoirs dans un cadre d’apprentissage ouvert, familier et modernisé.',
            'sections' => [
                [
                    'title' => 'Entrer dans une Voie',
                    'body' => 'Chaque cours ouvre un accès concret à une Voie UCKK. Il présente une question, une méthode, un concept ou un artefact à explorer pour mieux lire les règles, les systèmes et les possibilités d’action du Grand Jeu social.',
                ],
                [
                    'title' => 'Transformer le savoir en pratique',
                    'body' => 'Un cours UCKK n’est pas seulement un texte à consulter. Il invite à écrire, cartographier, coder, documenter, débattre, vérifier, créer, corriger et produire des traces de compréhension utilisables.',
                ],
                [
                    'title' => 'Ouvrir les espaces disponibles',
                    'body' => 'Quand un espace de cours est disponible, la carte du cours permet d’y accéder pour consulter les ressources, suivre les activités publiques ou rejoindre le cadre d’apprentissage prévu. Certains espaces peuvent demander une connexion selon leur usage.',
                ],
            ],
            'cardsheading' => 'Accéder aux cours',
            'cards' => [],
        ];
    }
}