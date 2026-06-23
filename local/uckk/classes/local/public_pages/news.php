<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle provides the technical Moodle implementation for the
// Univers-Cité King Klown.

/**
 * Public news page definition for UCKK.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public news page definition.
 *
 * @package local_uckk
 */
final class news {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'institutional',
            'eyebrow' => 'Nouvelles et appels',
            'title' => 'Journal public UCKK',
            'subtitle' => 'Annonces, publications, appels ouverts et jalons d’une bibliothèque vivante.',
            'summary' => 'Le journal public suit l’évolution de l’UCKK : nouvelles publications, appels à contribution, ouvertures de parcours, enrichissements de la médiathèque, corrections et repères utiles pour comprendre ce qui change.',
            'sections' => [
                [
                    'title' => 'Fonction',
                    'body' => 'Les nouvelles rendent visible le travail public de l’UCKK : diffuser le savoir, annoncer les ressources ouvertes, documenter les jalons et inviter à participer lorsque des contributions sont possibles.',
                ],
                [
                    'title' => 'Ce qui est publié',
                    'body' => 'On y trouve des annonces de cours, de Voies, d’archives, de médiathèque, d’assemblées, d’appels publics, de changements importants et de corrections utiles à la lecture du projet.',
                ],
                [
                    'title' => 'Sobriété',
                    'body' => 'Chaque nouvelle doit rester claire, datée, vérifiable et orientée vers l’usage public : comprendre ce qui est disponible, ce qui évolue et comment y accéder.',
                ],
            ],
            'cardsheading' => 'Repères d’actualité',
            'cards' => [
                [
                    'title' => 'Appels ouverts',
                    'body' => 'Invitations à lire, contribuer, commenter, proposer, tester ou participer à une démarche publique.',
                    'type' => 'calls',
                ],
                [
                    'title' => 'Jalons',
                    'body' => 'Moments importants dans le développement de l’UCKK, de ses Voies, de sa médiathèque, de ses archives ou de ses outils.',
                    'type' => 'milestones',
                ],
                [
                    'title' => 'Publications',
                    'body' => 'Mises en ligne de contenus, parcours, ressources, textes, supports ou repères destinés à la diffusion ouverte du savoir.',
                    'type' => 'publications',
                ],
                [
                    'title' => 'Corrections',
                    'body' => 'Clarifications, ajustements publics, corrections de contenu et changements de version utiles à la compréhension du projet.',
                    'type' => 'corrections',
                ],
            ],
            'notices' => [
                [
                    'body' => 'Le journal public ne publie pas de données personnelles, de dossiers privés ni de décisions internes non destinées à la publication.',
                    'type' => 'light',
                ],
            ],
        ];
    }
}