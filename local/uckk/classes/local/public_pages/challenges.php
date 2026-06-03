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
            'eyebrow' => 'Mettre la lucidité à l’épreuve',
            'title' => 'Défis UCKK',
            'subtitle' => 'Épreuves pédagogiques, traces, productions et révisions.',
            'summary' => 'Les défis servent à produire des traces, vérifier des apprentissages et rendre visibles des efforts structurés.',
            'sections' => [
                [
                    'title' => 'Pourquoi des défis',
                    'body' => 'Un défi transforme une intention d’apprentissage en action observable. Il peut demander une production, une preuve, une réflexion ou une validation.',
                ],
                [
                    'title' => 'Cycle général',
                    'body' => 'Un défi peut être proposé, accepté, réalisé, soumis, révisé, validé, contesté ou archivé selon les règles du contexte.',
                    'items' => [
                        'Comprendre la consigne.',
                        'Produire une trace.',
                        'Soumettre une preuve.',
                        'Recevoir une révision.',
                        'Archiver ce qui doit rester vérifiable.',
                    ],
                ],
            ],
            'cardsheading' => 'Repères pour les défis',
            'cards' => [
                [
                    'title' => 'Défis publics',
                    'body' => 'Présentation des défis visibles sans exposer les données privées.',
                    'type' => 'public',
                ],
                [
                    'title' => 'Révision',
                    'body' => 'Les validations et contestations suivent les permissions et les rôles Moodle.',
                    'type' => 'review',
                ],
            ],
            'notices' => [
                [
                    'title' => 'Protection des preuves',
                    'body' => 'Les preuves privées ne doivent pas être exposées dans les pages publiques.',
                    'type' => 'warning',
                ],
            ],
        ];
    }
}