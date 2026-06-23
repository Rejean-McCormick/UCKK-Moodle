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
 * Public assemblies page definition for local_uckk.
 *
 * This class owns the public page definition for the Assemblées UCKK page.
 * It defines presentation data only.
 *
 * It must not publish decisions, mutate assembly records, expose private
 * deliberation data, assign roles, validate recognitions, or replace Moodle
 * permissions and governance spaces.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public assemblies page definition.
 *
 * @package local_uckk
 */
final class assemblies {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'institutional',
            'eyebrow' => 'Lire, délibérer, transmettre',
            'title' => 'Assemblées UCKK',
            'subtitle' => 'Espaces publics de discussion, d’orientation et de mémoire collective.',
            'summary' => 'Les assemblées rendent visibles les moments où une communauté apprend ensemble : poser des questions, confronter des lectures, formuler des orientations et conserver des traces utiles.',
            'sections' => [
                [
                    'title' => 'Un lieu de parole structurée',
                    'body' => 'Une assemblée UCKK n’est pas seulement une réunion. C’est un cadre d’apprentissage collectif où les idées sont mises en discussion, les désaccords deviennent lisibles et les orientations peuvent être formulées avec méthode.',
                ],
                [
                    'title' => 'Délibérer pour mieux comprendre',
                    'body' => 'Les assemblées servent à ralentir le jugement, organiser les contributions et rendre les choix plus intelligibles. Elles aident à comprendre le jeu social avant de prétendre en changer les règles.',
                ],
                [
                    'title' => 'Mémoire publique',
                    'body' => 'Lorsque des traces sont publiées, elles doivent rester sobres, vérifiables et respectueuses des règles de confidentialité. Leur rôle est de soutenir la transmission, pas d’exposer inutilement les personnes.',
                ],
            ],
            'cardsheading' => 'Repères d’assemblée',
            'cards' => [
                [
                    'title' => 'Discussion',
                    'body' => 'Ouvrir un sujet, recueillir des lectures et faire apparaître les questions importantes.',
                    'type' => 'discussion',
                ],
                [
                    'title' => 'Délibération',
                    'body' => 'Transformer des contributions dispersées en compréhension commune, objections claires et orientations possibles.',
                    'type' => 'deliberation',
                ],
                [
                    'title' => 'Mémoire',
                    'body' => 'Conserver les traces utiles à la bibliothèque publique, à l’apprentissage collectif et à la continuité institutionnelle.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Voir les archives',
                    'type' => 'archive',
                ],
            ],
            'notices' => [
                [
                    'body' => 'Les assemblées publiques soutiennent la discussion et la mémoire collective. Les décisions internes, permissions et espaces de gouvernance demeurent gérés dans les cadres prévus à cet effet.',
                    'type' => 'institutional',
                ],
            ],
        ];
    }
}