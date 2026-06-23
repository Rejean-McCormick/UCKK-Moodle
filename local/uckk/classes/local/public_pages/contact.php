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
            'subtitle' => 'Trouver le bon point d’entrée dans l’Univers-Cité King Klown.',
            'summary' => 'Cette page aide à orienter les demandes vers les bons espaces : exploration publique, ressources ouvertes, questions sur les Voies, cours, archives, médias ou accès personnel.',

            'sections' => [
                [
                    'title' => 'Demandes générales',
                    'body' => 'Les demandes publiques peuvent concerner l’orientation dans l’UCKK, la compréhension du projet, les Voies, les cours, la médiathèque, les archives publiques ou les façons de contribuer à la diffusion du savoir.',
                ],
                [
                    'title' => 'Explorer avant de demander',
                    'body' => 'L’UCKK est d’abord une bibliothèque publique vivante. Plusieurs réponses se trouvent déjà dans les pages ouvertes : Voies, cours, médiathèque, archives, défis et assemblées donnent des repères pour entrer dans le Grand Jeu social.',
                ],
                [
                    'title' => 'Demandes privées',
                    'body' => 'Les questions liées à un dossier personnel, une preuve, un accès, une inscription, une décision ou un rôle spécifique doivent passer par les espaces UCKK appropriés après connexion.',
                ],
            ],

            'cardsheading' => 'Repères de contact',
            'cards' => [
                [
                    'title' => 'Voies',
                    'body' => 'Choisir un domaine d’entrée pour comprendre les grands parcours de savoir et d’action.',
                    'url' => '/local/uckk/programs.php',
                    'actionlabel' => 'Voir les Voies',
                    'type' => 'programs',
                ],
                [
                    'title' => 'Cours',
                    'body' => 'Explorer les cours publics ou accessibles pour trouver les ressources, notions et activités déjà organisées.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Voir les cours',
                    'type' => 'courses',
                ],
                [
                    'title' => 'Médiathèque',
                    'body' => 'Consulter les contenus publics, collections, références, médias et traces disponibles.',
                    'url' => '/local/uckk/mediatheque.php',
                    'actionlabel' => 'Explorer la médiathèque',
                    'type' => 'media',
                ],
                [
                    'title' => 'Registraire',
                    'body' => 'Consulter les traces publiques, preuves, décisions, corrections, versions et leçons utiles.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Voir le Registraire',
                    'type' => 'archives',
                ],
            ],

            'cta' => [
                'title' => 'Besoin d’un accès personnel?',
                'body' => 'Commence par les pages publiques lorsque ta demande concerne l’orientation ou la découverte. Connecte-toi seulement si ta demande concerne un dossier, un espace, une preuve, une inscription ou un rôle spécifique.',
                'url' => '/login/index.php',
                'label' => 'Se connecter',
            ],
        ];
    }
}