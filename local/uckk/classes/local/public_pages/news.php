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
            'title' => 'Actualités UCKK',
            'subtitle' => 'Informations publiques, appels, jalons et annonces institutionnelles.',
            'summary' => 'Les actualités présentent les informations publiques utiles au suivi de la cité-école.',
            'sections' => [
                [
                    'title' => 'Fonction',
                    'body' => 'Les actualités servent à annoncer des jalons, appels, corrections, publications ou événements publics.',
                ],
                [
                    'title' => 'Sobriété',
                    'body' => 'Une actualité publique doit rester claire, datée, vérifiable et limitée à ce qui peut être rendu public.',
                ],
            ],
            'cardsheading' => 'Repères d’actualité',
            'cards' => [
                [
                    'title' => 'Appels',
                    'body' => 'Invitations publiques à participer, contribuer ou consulter.',
                    'type' => 'calls',
                ],
                [
                    'title' => 'Jalons',
                    'body' => 'Moments importants du développement du campus.',
                    'type' => 'milestones',
                ],
                [
                    'title' => 'Corrections',
                    'body' => 'Informations sur les ajustements publics importants.',
                    'type' => 'corrections',
                ],
            ],
            'notices' => [
                [
                    'body' => 'Les actualités publiques ne doivent pas exposer de données personnelles ou de décisions internes non publiées.',
                    'type' => 'light',
                ],
            ],
        ];
    }
}