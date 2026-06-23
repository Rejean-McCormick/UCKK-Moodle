<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the technical foundation of the
// Univers-Cité King Klown.

/**
 * Public registrar page definition for local_uckk.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public registrar page definition.
 *
 * The class name remains "archives" because the internal route and legacy
 * code path still use archives.php. Public-facing labels must use Registraire.
 *
 * @package local_uckk
 */
final class archives {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'standard',
            'typography' => 'institutional',
            'eyebrow' => 'Bibliothèque publique et mémoire institutionnelle',
            'title' => 'Registraire UCKK',
            'subtitle' => 'Traces publiques, sources, décisions, corrections et repères de compréhension.',
            'summary' => 'Le registraire rend accessibles les traces utiles à la mémoire vivante de l’UCKK. Il soutient la diffusion du savoir, la vérification publique et la compréhension des transformations institutionnelles.',
            'sections' => [
                [
                    'title' => 'Une mémoire publique vivante',
                    'body' => 'Le registraire n’est pas un simple dépôt de fichiers. Il organise les traces publiques qui permettent de comprendre l’évolution de l’UCKK, ses décisions, ses méthodes, ses corrections et ses productions.',
                ],
                [
                    'title' => 'Ce que le registraire conserve',
                    'body' => 'Il peut conserver des versions publiques du canon, des décisions, des modèles, des appels, des productions, des corrections, des repères pédagogiques et des traces institutionnelles utiles à la compréhension commune.',
                ],
                [
                    'title' => 'Ce qui demeure protégé',
                    'body' => 'Les données personnelles, dossiers sensibles, preuves privées et informations réservées à des rôles autorisés ne sont pas exposés publiquement. Le registraire distingue ce qui peut être partagé de ce qui doit rester protégé.',
                ],
                [
                    'title' => 'Usage',
                    'body' => 'Le registraire sert à lire, vérifier, relier, transmettre et corriger. Il fait partie de la bibliothèque publique de l’UCKK : un espace ouvert pour suivre le savoir, ses sources et ses transformations.',
                ],
            ],
            'cardsheading' => 'Repères du registraire',
            'cards' => [
                [
                    'title' => 'Mémoire',
                    'body' => 'Conserver les traces publiques importantes.',
                    'type' => 'memory',
                ],
                [
                    'title' => 'Sources',
                    'body' => 'Relier les contenus à leur provenance et à leur contexte.',
                    'type' => 'evidence',
                ],
                [
                    'title' => 'Corrections',
                    'body' => 'Rendre les changements importants visibles et compréhensibles.',
                    'type' => 'correction',
                ],
                [
                    'title' => 'Transmission',
                    'body' => 'Soutenir la diffusion ouverte du savoir UCKK.',
                    'type' => 'knowledge',
                ],
            ],
            'notices' => [
                [
                    'title' => 'Registraire public',
                    'body' => 'Les contenus visibles ici appartiennent à la mémoire publique de l’UCKK. Ils doivent rester lisibles, vérifiables, contextualisés et respectueux des permissions.',
                    'type' => 'info',
                ],
            ],
        ];
    }
}