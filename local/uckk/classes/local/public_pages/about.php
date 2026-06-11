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
 * Public about page definition for UCKK.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public about page definition.
 *
 * @package local_uckk
 */
final class about {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'editorial',
            'eyebrow' => 'Situer l’UCKK',
            'title' => 'À propos',
            'subtitle' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire consacré au Grand Jeu social.',
            'summary' => 'Cette page présente l’identité publique de l’UCKK, son rôle, ses limites et son organisation générale.',
            'sections' => [
                [
                    'title' => 'Ce qu’est l’UCKK',
                    'body' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire. Il rassemble des Voies, des cours, des défis, des preuves, des Assemblées, un Registraire, des portfolios et des formes de reconnaissance interne pour former des Joueurs capables de comprendre le Grand Jeu social, d’agir avec lucidité et de documenter leur progression.',
                ],
                [
                    'title' => 'Ce que l’UCKK n’est pas',
                    'body' => 'L’UCKK n’est pas une université publique accréditée par l’État. Ses Niveaux, Parchemins UCKK, portfolios, dossiers de passage et reconnaissances internes ne doivent pas être présentés comme des diplômes publics accrédités, des grades universitaires ou des titres professionnels reconnus par l’État, sauf reconnaissance officielle future.',
                ],
                [
                    'title' => 'Organisation',
                    'body' => 'Les pages publiques donnent accès aux repères institutionnels de l’UCKK : Voies, cours, défis, Assemblées, Médiathèque, Registraire, intégrité et informations générales. Les espaces internes, inscriptions, rôles, permissions, validations et dossiers privés restent gérés dans les espaces appropriés.',
                ],
            ],
            'cardsheading' => 'Repères institutionnels',
            'cards' => [
                [
                    'title' => 'Établissement',
                    'body' => 'Un établissement virtuel de puissance opératoire pour lire les systèmes, produire des preuves, agir avec méthode et transformer les règles du Grand Jeu social.',
                    'type' => 'institution',
                ],
                [
                    'title' => 'Canon',
                    'body' => 'Un cadre de vocabulaire, de limites et de cohérence institutionnelle pour stabiliser l’identité, les règles et les repères UCKK.',
                    'type' => 'canon',
                ],
                [
                    'title' => 'Registraire',
                    'body' => 'Une mémoire structurée des traces publiques, décisions, preuves, corrections et versions utiles à la compréhension de l’UCKK.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Consulter',
                    'type' => 'archives',
                ],
            ],
            'notices' => [
                [
                    'body' => 'Cette page est informative. Les règles de permissions, d’inscription, de validation et de reconnaissance restent gérées par les composants UCKK appropriés.',
                    'type' => 'light',
                ],
            ],
        ];
    }
}