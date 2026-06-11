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
            'eyebrow' => 'Registre public institutionnel',
            'title' => 'Registraire UCKK',
            'subtitle' => 'Mémoire institutionnelle, preuves publiques, décisions et corrections.',
            'summary' => 'Le registraire conserve les traces publiques utiles à la compréhension de l’UCKK, de ses décisions et de ses transformations.',
            'sections' => [
                [
                    'title' => 'Ce que le registraire conserve',
                    'body' => 'Le registraire peut conserver des versions publiques du canon, des décisions, des modèles, des corrections, des appels, des productions publiques et des traces institutionnelles.',
                ],
                [
                    'title' => 'Ce que le registraire ne doit pas exposer',
                    'body' => 'Il ne doit pas publier de données personnelles, de dossiers sensibles, de preuves privées ou d’informations réservées à des rôles autorisés.',
                ],
                [
                    'title' => 'Usage',
                    'body' => 'Le registraire sert à comprendre, vérifier, relier et corriger. Il n’est pas un simple dépôt de fichiers.',
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
                    'title' => 'Preuves',
                    'body' => 'Relier les preuves publiques à leur contexte.',
                    'type' => 'evidence',
                ],
                [
                    'title' => 'Corrections',
                    'body' => 'Rendre les changements importants compréhensibles.',
                    'type' => 'correction',
                ],
            ],
            'notices' => [
                [
                    'title' => 'Registraire public',
                    'body' => 'Les contenus visibles ici doivent respecter la confidentialité, les permissions, la provenance et les limites institutionnelles de l’UCKK.',
                    'type' => 'warning',
                ],
            ],
        ];
    }
}