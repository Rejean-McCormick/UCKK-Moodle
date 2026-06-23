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
 * Public challenges page definition for UCKK.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public challenges page definition.
 *
 * @package local_uckk
 */
final class challenges {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'institutional',
            'eyebrow' => 'Apprendre par la pratique',
            'title' => 'Défis UCKK',
            'subtitle' => 'Des exercices publics pour mettre le savoir en action.',
            'summary' => 'Les défis prolongent la bibliothèque publique UCKK : ils invitent à comprendre, essayer, produire, documenter et réviser dans un cadre d’apprentissage familier, modernisé et ouvert.',
            'sections' => [
                [
                    'title' => 'Pourquoi des défis',
                    'body' => 'Un défi transforme une question en pratique. Il permet de passer de la lecture à l’action, de l’idée à la trace, de l’intuition à une production observable. Le but n’est pas de fermer l’accès au savoir, mais d’aider chacun à l’éprouver, le manipuler et le rendre partageable.',
                ],
                [
                    'title' => 'Cycle général',
                    'body' => 'Chaque défi peut être lu comme une petite scène d’apprentissage : on comprend la consigne, on agit, on produit une trace, puis on révise ce qui mérite d’être clarifié, corrigé ou conservé.',
                    'items' => [
                        'Lire la consigne et le contexte.',
                        'Explorer les ressources ouvertes.',
                        'Produire une trace ou une réponse.',
                        'Recevoir une révision ou une orientation.',
                        'Conserver ce qui peut enrichir la mémoire commune.',
                    ],
                ],
                [
                    'title' => 'Un cadre ouvert, pas une barrière',
                    'body' => 'Les défis ne sont pas un paywall de connaissance. Ils servent à rendre l’apprentissage plus concret, plus lisible et plus actif, sans remplacer l’accès libre aux cours, archives, médias et repères publics.',
                ],
            ],
            'cardsheading' => 'Repères pour les défis',
            'cards' => [
                [
                    'title' => 'Défis publics',
                    'body' => 'Des propositions visibles qui invitent à explorer un thème, formuler une réponse, produire une trace ou tester une idée sans exposer de données privées.',
                    'type' => 'public',
                ],
                [
                    'title' => 'Traces et productions',
                    'body' => 'Une trace peut être un texte, une carte, une lecture commentée, une observation, une ressource, une synthèse, une correction ou une proposition de méthode.',
                    'type' => 'trace',
                ],
                [
                    'title' => 'Révision',
                    'body' => 'La révision sert à améliorer la clarté, la qualité et la responsabilité des productions. Elle peut orienter, corriger, questionner ou proposer une meilleure formulation.',
                    'type' => 'review',
                ],
            ],
            'notices' => [
                [
                    'title' => 'Protection des traces privées',
                    'body' => 'Les preuves privées, dossiers internes, données sensibles et éléments restreints ne doivent pas être exposés dans les pages publiques.',
                    'type' => 'warning',
                ],
            ],
        ];
    }
}