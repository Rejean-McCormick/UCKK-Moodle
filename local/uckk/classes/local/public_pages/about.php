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
            'summary' => 'UCKK ouvre une bibliothèque publique vivante, des Voies d’apprentissage, des cours, des archives et des espaces de pratique pour comprendre le jeu, agir avec lucidité et transformer les règles.',
            'sections' => [
                [
                    'title' => 'Ce qu’est l’UCKK',
                    'body' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire. Elle rassemble des Voies, des cours, des défis, des archives, une médiathèque, des Assemblées et des repères publics pour apprendre à lire le Grand Jeu social : ses règles, ses positions, ses récits, ses pouvoirs, ses preuves et ses possibilités de transformation.',
                ],
                [
                    'title' => 'Une bibliothèque publique vivante',
                    'body' => 'La première fonction de l’UCKK est la diffusion du savoir. Les pages publiques donnent accès à des connaissances, méthodes, références, cours et parcours d’apprentissage conçus pour être consultés, reliés, pratiqués et partagés. Dans l’esprit kOA, le savoir doit circuler : il doit aider à comprendre, à construire, à décider et à agir.',
                ],
                [
                    'title' => 'Des Voies pour apprendre à agir',
                    'body' => 'Les Voies UCKK organisent le savoir en parcours lisibles. Elles ne sont pas de simples catégories de cours : elles servent à apprendre une posture, une méthode et une capacité d’action. Grand Jeu social, économie, écologie, sciences politiques, architecture du sens, métaphysique, production augmentée par l’IA, intervention sociale, architecture sociotechnique et écosystème digital kOA forment ensemble un atlas pratique du monde social.',
                ],
                [
                    'title' => 'Un cadre d’apprentissage modernisé',
                    'body' => 'UCKK utilise un cadre familier d’apprentissage — cours, voies, niveaux, exercices, archives, traces et projets — en le modernisant pour servir la lecture critique des systèmes, la production de preuves, la mémoire collective et l’action située. Les parcours servent à s’orienter dans le savoir, à pratiquer des méthodes et à construire des artefacts utiles.',
                ],
                [
                    'title' => 'Organisation',
                    'body' => 'Les pages publiques donnent accès aux repères institutionnels de l’UCKK : Voies, cours, défis, Assemblées, Médiathèque, Registraire, intégrité et informations générales. Les espaces internes, inscriptions, rôles, permissions, validations et dossiers privés restent gérés dans les espaces appropriés.',
                ],
            ],
            'cardsheading' => 'Repères institutionnels',
            'cards' => [
                [
                    'title' => 'Bibliothèque publique',
                    'body' => 'Un accès ouvert aux cours, archives, ressources, cartes de lecture et repères utiles pour comprendre les systèmes et apprendre à agir.',
                    'type' => 'library',
                ],
                [
                    'title' => 'Établissement',
                    'body' => 'Un établissement virtuel de puissance opératoire pour lire les systèmes, produire des preuves, agir avec méthode et transformer les règles du Grand Jeu social.',
                    'type' => 'institution',
                ],
                [
                    'title' => 'Voies',
                    'body' => 'Des parcours publics pour apprendre à lire un domaine, pratiquer des méthodes, produire des artefacts et relier les savoirs entre eux.',
                    'url' => '/local/uckk/programs.php',
                    'actionlabel' => 'Explorer',
                    'type' => 'pathways',
                ],
                [
                    'title' => 'Cours publics',
                    'body' => 'Des portes d’entrée vers les notions, méthodes, exercices et espaces d’apprentissage disponibles dans chaque Voie.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Accéder',
                    'type' => 'courses',
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
                    'body' => 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.',
                    'type' => 'light',
                ],
            ],
        ];
    }
}