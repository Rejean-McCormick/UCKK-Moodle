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
 * Public programs page definition for local_uckk.
 *
 * This class owns the public page definition for the Voies UCKK page.
 * It may read active public program records for display only.
 *
 * It must not create programs, mutate pathways, enrol users, award badges,
 * validate competencies, or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Public programs page definition.
 *
 * @package local_uckk
 */
final class programs {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return self::with_program_cards([
            'layout' => 'wide',
            'typography' => 'institutional',
            'eyebrow' => 'Former par les voies',
            'title' => 'Voies UCKK',
            'subtitle' => 'Parcours publics, orientations pédagogiques et structures d’engagement.',
            'summary' => 'Les voies organisent les intentions de formation, les trajectoires et les grands ensembles d’apprentissage.',
            'sections' => [
                [
                    'type' => 'role',
                    'title' => 'Rôle des voies',
                    'body' => 'Une voie aide à situer un parcours d’apprentissage dans la cité-école. Elle peut relier des cours, des compétences, des défis, des preuves et des archives.',
                ],
                [
                    'type' => 'registry',
                    'eyebrow' => 'Répertoire public',
                    'title' => 'Voies et programmes actifs',
                    'body' => 'Les cartes ci-dessous sont générées depuis le registre Moodle UCKK des programmes actifs. Les éléments en brouillon, cachés ou archivés ne sont pas affichés publiquement.',
                ],
            ],
            'cards' => [],
            'notices' => [
                [
                    'body' => 'Les voies décrivent des structures pédagogiques internes à UCKK. Elles ne constituent pas des diplômes publics accrédités.',
                    'type' => 'institutional',
                ],
            ],
            'metadata' => [
                ['label' => 'Source', 'value' => 'Registre local_uckk_program'],
                ['label' => 'Filtre public', 'value' => 'status = active'],
            ],
            'cta' => [
                'title' => 'Explorer les cours',
                'body' => 'Les catégories Moodle liées aux voies regroupent les cours et unités d’apprentissage disponibles.',
                'url' => '/local/uckk/courses.php',
                'label' => 'Voir les cours',
            ],
        ]);
    }

    /**
     * Add live program cards from local_uckk_program to the public page definition.
     *
     * @param array<string, mixed> $definition Base page definition.
     * @return array<string, mixed>
     */
    private static function with_program_cards(array $definition): array {
        $cards = self::program_cards('active');

        // Program cards belong inside the public registry section only.
        // Keeping them at page level creates a second generic card section.
        $definition['cards'] = [];
        $definition['cardsheading'] = 'Repères publics';

        if (!isset($definition['sections']) || !is_array($definition['sections'])) {
            $definition['sections'] = [];
        }

        $attached = false;

        foreach ($definition['sections'] as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $title = (string)($section['title'] ?? '');
            $eyebrow = (string)($section['eyebrow'] ?? '');

            if ($title === 'Répertoire public' || $title === 'Voies et programmes actifs' || $eyebrow === 'Répertoire public') {
                $definition['sections'][$index]['cards'] = $cards;
                $attached = true;
                break;
            }
        }

        if (!$attached) {
            $definition['sections'][] = [
                'type' => 'registry',
                'eyebrow' => 'Répertoire public',
                'title' => 'Voies et programmes actifs',
                'body' => 'Ces cartes proviennent du registre UCKK des programmes actifs. Les éléments en brouillon ou non publiés ne sont pas affichés ici.',
                'cards' => $cards,
            ];
        }

        if (!isset($definition['metadata']) || !is_array($definition['metadata'])) {
            $definition['metadata'] = [];
        }

        $definition['metadata'][] = [
            'label' => 'Programmes actifs affichés',
            'value' => (string)count($cards),
        ];

        if (empty($cards)) {
            if (!isset($definition['notices']) || !is_array($definition['notices'])) {
                $definition['notices'] = [];
            }

            $definition['notices'][] = [
                'title' => 'Aucun programme actif',
                'body' => 'Aucun programme actif n’est actuellement disponible dans le registre public UCKK.',
                'type' => 'warning',
            ];
        }

        return $definition;
    }

    /**
     * Build public cards from active UCKK program records.
     *
     * @param string $status Program status to display.
     * @return array<int, array<string, mixed>>
     */
    private static function program_cards(string $status): array {
        global $CFG, $DB;

        if (!isset($CFG) || !isset($DB)) {
            return [];
        }

        if (!class_exists('xmldb_table')) {
            require_once($CFG->libdir . '/xmldb/xmldb_object.php');
        }

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_uckk_program'))) {
            return [];
        }

        $records = $DB->get_records_sql("
            SELECT
                p.id,
                p.shortname,
                p.fullname,
                p.programtype,
                p.status,
                p.visibility,
                p.sortorder,
                p.categoryid,
                c.name AS categoryname,
                c.visible AS categoryvisible
              FROM {local_uckk_program} p
         LEFT JOIN {course_categories} c ON c.id = p.categoryid
             WHERE p.status = :status
          ORDER BY p.programtype, p.sortorder, p.fullname
        ", [
            'status' => $status,
        ]);

        $cards = [];

        foreach ($records as $record) {
            $shortname = trim((string)($record->shortname ?? ''));
            $fullname = trim((string)($record->fullname ?? ''));
            $programtype = trim((string)($record->programtype ?? ''));
            $categoryname = trim((string)($record->categoryname ?? ''));
            $categoryvisible = (int)($record->categoryvisible ?? 0);
            $categoryid = (int)($record->categoryid ?? 0);

            $typelabel = self::program_type_label($programtype);
            $title = $fullname !== '' ? $fullname : $shortname;

            if ($title === '') {
                continue;
            }

            $bodyparts = [];

            if ($shortname !== '') {
                $bodyparts[] = 'Code : ' . $shortname . '.';
            }

            if ($typelabel !== '') {
                $bodyparts[] = 'Type : ' . $typelabel . '.';
            }

            if ($categoryname !== '') {
                $bodyparts[] = 'Catégorie Moodle : ' . $categoryname . '.';
            } else {
                $bodyparts[] = 'Aucune catégorie Moodle liée.';
            }

            $url = '';
            $actionlabel = '';

            if ($categoryid > 0 && $categoryvisible === 1) {
                $url = (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false);
                $actionlabel = 'Voir les cours';
            }

            $cards[] = [
                'eyebrow' => '',
                'title' => $title,
                'body' => implode(' ', $bodyparts),
                'url' => $url,
                'actionlabel' => $actionlabel,
                'type' => self::clean_modifier($programtype),
            ];
        }

        return $cards;
    }

    /**
     * Human label for a program type.
     *
     * @param string $programtype Program type.
     * @return string
     */
    private static function program_type_label(string $programtype): string {
        $labels = [
            'tronc_commun' => 'Tronc commun',
            'voie_uckk' => 'Voie UCKK',
            'voie_secondaire' => 'Voie secondaire',
            'baccalaureat' => 'Baccalauréat',
            'baccalauréat' => 'Baccalauréat',
            'mineure' => 'Mineure',
            'seminaire' => 'Séminaire',
            'séminaire' => 'Séminaire',
            'laboratoire' => 'Laboratoire',
            'lab' => 'Laboratoire',
            'atelier' => 'Atelier',
        ];

        if (isset($labels[$programtype])) {
            return $labels[$programtype];
        }

        $label = trim(str_replace('_', ' ', $programtype));

        return $label !== '' ? ucfirst($label) : '';
    }

    /**
     * Clean CSS modifier value.
     *
     * @param mixed $modifier Modifier.
     * @return string
     */
    private static function clean_modifier($modifier): string {
        if (!is_scalar($modifier)) {
            return '';
        }

        $modifier = strtolower(trim((string)$modifier));
        $modifier = preg_replace('/[^a-z0-9_-]+/', '-', $modifier) ?? '';
        $modifier = trim($modifier, '-');

        return $modifier;
    }
}