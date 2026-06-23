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
            'summary' => 'Cette page présente l’identité publique de l’UCKK : une bibliothèque vivante, un cadre d’apprentissage ouvert et un lieu de diffusion du savoir.',
            'sections' => [
                [
                    'title' => 'Ce qu’est l’UCKK',
                    'body' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire. Elle rassemble des Voies, des cours, des défis, des archives, une médiathèque, des Assemblées et des repères publics pour apprendre à comprendre le Grand Jeu social, agir avec lucidité et changer les règles.',
                ],
                [
                    'title' => 'Une bibliothèque publique vivante',
                    'body' => 'La première fonction de l’UCKK est la diffusion immédiate du savoir. Les pages publiques ouvrent un accès aux connaissances, aux méthodes, aux références et aux parcours d’apprentissage sans transformer le savoir en privilège fermé. L’UCKK s’inscrit dans l’esprit kOA : rendre les outils de compréhension plus accessibles, plus praticables et plus partageables.',
                ],
                [
                    'title' => 'Un cadre d’apprentissage modernisé',
                    'body' => 'L’UCKK utilise un cadre familier d’apprentissage — cours, voies, niveaux, exercices, archives et traces — en le modernisant pour servir la lecture critique des systèmes, la production de preuves, la mémoire collective et l’action située. Les parcours ne sont pas conçus comme une barrière d’accès, mais comme une manière d’organiser le savoir pour mieux s’y orienter.',
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
                    'body' => 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.',
                    'type' => 'light',
                ],
            ],
        ];
    }
}