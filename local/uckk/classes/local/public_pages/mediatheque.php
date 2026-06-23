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
 * Public catalogue page definition for local_uckk.
 *
 * This class owns the public page content definition for the UCKK Médiathèque.
 * Media records, permissions, public filtering, rights, content advisories
 * and cultural protocol decisions remain owned by mod_uckkarchive.
 *
 * It must not:
 * - query media tables;
 * - expose private internal fields;
 * - decide access rights;
 * - bypass content advisories;
 * - bypass cultural protocols;
 * - build private file URLs;
 * - mutate Moodle data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public catalogue page definition.
 *
 * @package local_uckk
 */
final class mediatheque {
    /**
     * Médiathèque explorer DOM id.
     */
    private const EXPLORER_ID = 'local-uckk-mediatheque-explorer';

    /**
     * Public Médiathèque search service.
     */
    private const SEARCH_SERVICE = 'mod_uckkarchive_search_mediatheque';

    /**
     * Return the public catalogue page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'wide',
            'typography' => 'institutional',

            'eyebrow' => 'Bibliothèque publique',
            'title' => 'Médiathèque UCKK',
            'subtitle' => 'Explorer les médias, collections, références et passages documentés de l’Univers-Cité King Klown.',
            'summary' => 'La Médiathèque est une porte d’entrée publique vers les savoirs, traces, œuvres, documents, sons, images, vidéos et références qui nourrissent l’UCKK. Elle rend les contenus consultables sans paywall, dans un cadre d’apprentissage familier, modernisé et ouvert.',
            'cardsheading' => 'Entrer dans la bibliothèque',

            'has_mediatheque_explorer' => true,
            'mediatheque_explorer_id' => self::EXPLORER_ID,
            'mediatheque_initial_state' => [
                'rootId' => self::EXPLORER_ID,
                'service' => self::SEARCH_SERVICE,
                'cmid' => 0,
                'archiveid' => 0,
                'query' => '',
                'filters' => [
                    'type' => 'all',
                    'mediatype' => 'all',
                    'collection' => '',
                    'tag' => '',
                    'source' => '',
                    'advisory' => 'all',
                    'cultural' => 'all',
                    'audience' => 'all',
                    'lang' => '',
                    'validation' => 'all',
                    'item' => '',
                ],
                'page' => 1,
                'perpage' => 12,
                'sort' => 'relevance',
                'sitewide' => true,
            ],

            'mediatheque' => [
                'explorer' => [
                    'key' => 'mediatheque_explorer',
                    'title' => 'Recherche publique',
                    'service' => self::SEARCH_SERVICE,
                    'surface' => 'local_uckk',
                    'dataowner' => 'mod_uckkarchive',
                ],
            ],

            'quicklinks' => [
                [
                    'label' => 'Rechercher',
                    'description' => 'Trouver des médias publics, collections ouvertes, références externes et passages documentés.',
                    'url' => '/local/uckk/mediatheque.php',
                ],
            ],

            'sections' => [
                [
                    'type' => 'orientation',
                    'eyebrow' => 'Exploration',
                    'title' => 'Une bibliothèque vivante de savoirs publics',
                    'body' => 'L’explorateur permet de chercher, filtrer et parcourir les contenus rendus publics par l’UCKK : médias, documents, références, collections et fragments contextualisés. La Médiathèque soutient la diffusion immédiate du savoir et l’apprentissage autonome.',
                    'items' => [
                        'Recherche par texte, format, source, collection, langue et mot-clé.',
                        'Parcours libre dans les contenus publics accessibles.',
                        'Repérage de documents, médias et passages utiles aux cours, voies, archives et assemblées.',
                    ],
                ],
                [
                    'type' => 'boundary',
                    'eyebrow' => 'Cadre de consultation',
                    'title' => 'Un accès ouvert, avec respect des droits',
                    'body' => 'La Médiathèque ouvre ce qui peut être partagé publiquement. Certains contenus peuvent rester limités lorsque des droits, avis de contenu, permissions ou protocoles culturels l’exigent.',
                ],
            ],

            'cards' => [
                [
                    'title' => 'Médias publics',
                    'body' => 'Voir les vidéos, sons, images, documents et références accessibles dans la bibliothèque publique.',
                    'type' => 'media',
                ],
                [
                    'title' => 'Collections',
                    'body' => 'Explorer des regroupements de contenus liés aux cours, voies, thèmes, archives et recherches UCKK.',
                    'type' => 'collection',
                ],
                [
                    'title' => 'Passages documentés',
                    'body' => 'Accéder à des moments, pages, extraits ou segments qui éclairent une idée, une controverse ou un apprentissage.',
                    'type' => 'marker',
                ],
            ],

            'notices' => [
                [
                    'title' => 'Bibliothèque ouverte',
                    'body' => 'La Médiathèque participe à la mission publique de l’UCKK : rendre les savoirs accessibles, partageables et praticables dans un environnement d’apprentissage ouvert.',
                    'type' => 'institutional',
                ],
                [
                    'title' => 'Consultation responsable',
                    'body' => 'Les résultats affichés respectent les droits, les avis de contenu, les permissions et les protocoles culturels définis par le moteur média institutionnel.',
                    'type' => 'light',
                ],
            ],

            'metadata' => [
                [
                    'label' => 'Surface publique',
                    'value' => 'local_uckk',
                ],
                [
                    'label' => 'Bibliothèque',
                    'value' => 'Médiathèque UCKK',
                ],
                [
                    'label' => 'Service',
                    'value' => 'Recherche du catalogue public',
                ],
                [
                    'label' => 'Portée par défaut',
                    'value' => 'Contenus publics du site',
                ],
            ],

            'cta' => [
                'title' => 'Explorer la Médiathèque',
                'body' => 'Lance une recherche ou applique un filtre pour parcourir les contenus publics par format, source, collection, langue ou mot-clé.',
                'url' => '/local/uckk/mediatheque.php',
                'label' => 'Ouvrir la recherche',
            ],
        ];
    }
}