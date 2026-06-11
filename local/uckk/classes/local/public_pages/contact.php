<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle supports the technical Moodle implementation of the
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
            'subtitle' => 'Orientation publique pour contacter ou rejoindre l’Univers-Cité King Klown.',
            'summary' => 'Cette page indique les voies générales de contact et rappelle les limites des demandes publiques.',
            'sections' => [
                [
                    'title' => 'Demandes générales',
                    'body' => 'Les demandes publiques peuvent concerner l’orientation, la compréhension de l’UCKK, les voies, les cours, la médiathèque ou le Registraire.',
                ],
                [
                    'title' => 'Demandes privées',
                    'body' => 'Les questions liées à un dossier personnel, une preuve, une inscription ou une décision doivent passer par les espaces UCKK appropriés.',
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
                    'title' => 'Registraire',
                    'body' => 'Consulter les traces publiques, preuves, décisions et corrections disponibles.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Voir le Registraire',
                    'type' => 'archives',
                ],
            ],
            'cta' => [
                'title' => 'Besoin d’orientation?',
                'body' => 'Commence par les pages publiques, puis connecte-toi à ton espace UCKK si ta demande concerne un dossier, un espace ou un rôle spécifique.',
                'url' => '/login/index.php',
                'label' => 'Se connecter',
            ],
        ];
    }
}