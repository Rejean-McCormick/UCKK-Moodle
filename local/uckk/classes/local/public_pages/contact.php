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
 * Public contact page definition.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class contact {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'editorial',
            'eyebrow' => 'Entrer en relation',
            'title' => 'Contact',
            'subtitle' => 'Orientation publique pour contacter ou rejoindre le campus UCKK.',
            'summary' => 'Cette page indique les voies générales de contact et rappelle les limites des demandes publiques.',
            'sections' => [
                [
                    'title' => 'Demandes générales',
                    'body' => 'Les demandes publiques peuvent concerner l’orientation, la compréhension du campus, les voies, les cours ou les archives.',
                ],
                [
                    'title' => 'Demandes privées',
                    'body' => 'Les questions liées à un dossier personnel, une preuve, une inscription ou une décision doivent passer par les espaces Moodle appropriés.',
                ],
            ],
            'cardsheading' => 'Repères de contact',
            'cards' => [
                [
                    'title' => 'Cours',
                    'body' => 'Commencer par consulter les cours publics ou accessibles.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Voir les cours',
                    'type' => 'courses',
                ],
                [
                    'title' => 'Archives',
                    'body' => 'Consulter les traces publiques disponibles.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Voir les archives',
                    'type' => 'archives',
                ],
            ],
            'cta' => [
                'title' => 'Besoin d’orientation?',
                'body' => 'Commence par les pages publiques, puis connecte-toi à Moodle si ta demande concerne un espace ou un rôle spécifique.',
                'url' => '/login/index.php',
                'label' => 'Se connecter',
            ],
        ];
    }
}