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
 * Faculty profile normalizer for local_uckk.
 *
 * This class normalizes decoded *.faculty.json arrays into a stable canonical
 * shape for page builders, external services and output classes.
 *
 * It does not:
 * - read JSON files;
 * - validate the full schema;
 * - query Moodle categories, courses, forums or calendars;
 * - load Atlas voie JSON files;
 * - render HTML;
 * - check permissions;
 * - introduce Mustache template variables outside DOC_12.
 *
 * Template-facing enrichment belongs to faculty_page_builder and output
 * classes. This normalizer keeps canonical JSON keys and stores internal
 * technical metadata only under the reserved _normalized key.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalizes public faculty profile data.
 */
final class faculty_normalizer {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Canonical faculty profile schema version. */
    public const SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Canonical Atlas schema expected by faculty profiles. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Institutional recognition note for public UCKK pages. */
    public const INTERNAL_RECOGNITION_NOTICE =
        'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';

    /** Public positioning note for faculty pages. */
    public const ANTI_CONFUSION_NOTICE =
        'Cette page présente une Voie UCKK comme parcours public de savoir dans l’Univers-Cité King Klown. '
        . 'Elle sert à diffuser, organiser et pratiquer des connaissances dans un cadre d’apprentissage ouvert.';

    /** Source Atlas sync mode used by public profile reads. */
    public const SOURCE_ATLAS_SYNC_MODE_READ_ONLY = 'read_only';

    /** Default cache TTL. */
    private const DEFAULT_CACHE_TTL_SECONDS = 3600;

    /** Allowed status values. */
    private const STATUSES = [
        'draft',
        'published',
        'archived',
    ];

    /** Allowed visibility values. */
    private const VISIBILITIES = [
        'public',
        'hidden',
        'restricted',
    ];

    /** Allowed source_atlas.sync_mode values. */
    private const ATLAS_SYNC_MODES = [
        'read_only',
        'preview_only',
        'moodle_sync_allowed',
    ];

    /** Allowed enrolment visibility values. */
    private const ENROLMENT_VISIBILITIES = [
        'hidden',
        'public_info_only',
        'login_required',
        'enrolment_required',
    ];

    /** Allowed section types. */
    private const SECTION_TYPES = [
        'text',
        'markdown',
        'quote',
        'principle',
        'notice',
        'cards',
        'callout',
        'two_column',
    ];

    /** Default Atlas projection flags. */
    private const DEFAULT_ATLAS_PROJECTION = [
        'show_definition_courte' => true,
        'show_angle_fondamental' => true,
        'show_competence_centrale' => true,
        'show_seuils_progression' => true,
        'show_courses' => true,
        'show_course_codes' => true,
        'show_concept_maitre' => true,
        'show_concepts_associes' => false,
        'show_artefacts' => true,
        'show_criteres_passage' => false,
        'show_projet_final' => true,
        'show_limites_ethiques' => true,
        'show_relations_intervoies' => true,
        'show_tags' => false,
    ];

    /** Allowed dynamic block types. */
    private const DYNAMIC_BLOCK_TYPES = [
        'announcements',
        'events',
        'moodle_course_list',
        'featured_courses',
        'faculty_news',
        'related_faculties',
        'public_resources',
        'cta_panel',
    ];

    /** Allowed dynamic providers. */
    private const DYNAMIC_PROVIDERS = [
        'moodle_forum',
        'moodle_calendar',
        'moodle_category',
        'moodle_course_customfield',
        'local_uckk_news',
        'local_uckk_manual',
        'none',
    ];

    /** Allowed featured block types. */
    private const FEATURED_BLOCK_TYPES = [
        'principle',
        'notice',
        'warning',
        'quote',
        'stat',
        'method',
        'ethics',
        'cta',
    ];

    /** Allowed governance editorial statuses. */
    private const EDITORIAL_STATUSES = [
        'draft',
        'review',
        'approved',
        'needs_update',
        'archived',
    ];

    /**
     * Normalize a decoded faculty profile.
     *
     * @param array<string, mixed> $profile Decoded faculty profile.
     * @param array<string, mixed>|null $registryrecord Optional manifest/registry record.
     * @return array<string, mixed>
     */
    public function normalize(array $profile, ?array $registryrecord = null): array {
        $normalized = [];

        $normalized['schema_version'] = $this->string_value($profile, 'schema_version', self::SCHEMA_VERSION);
        $normalized['faculty_id'] = $this->string_value($profile, 'faculty_id', $this->registry_string($registryrecord, 'faculty_id'));
        $normalized['voie_id'] = $this->string_value($profile, 'voie_id', $this->registry_string($registryrecord, 'voie_id'));
        $normalized['slug'] = $this->normalize_slug(
            $this->string_value($profile, 'slug', $this->registry_string($registryrecord, 'slug'))
        );
        $normalized['status'] = $this->enum_value($profile, 'status', self::STATUSES, $this->registry_string($registryrecord, 'status', 'draft'));
        $normalized['visibility'] = $this->enum_value(
            $profile,
            'visibility',
            self::VISIBILITIES,
            $this->registry_string($registryrecord, 'visibility', 'hidden')
        );

        $normalized['source_atlas'] = $this->normalize_source_atlas($profile['source_atlas'] ?? [], $registryrecord);
        $normalized['moodle'] = $this->normalize_moodle($profile['moodle'] ?? [], $registryrecord);
        $normalized['identity'] = $this->normalize_identity($profile['identity'] ?? []);
        $normalized['seo'] = $this->normalize_seo($profile['seo'] ?? [], $normalized);
        $normalized['hero'] = $this->normalize_hero($profile['hero'] ?? [], $normalized);
        $normalized['navigation'] = $this->normalize_navigation($profile['navigation'] ?? []);
        $normalized['sections'] = $this->normalize_sections($profile['sections'] ?? []);
        $normalized['atlas_projection'] = $this->normalize_atlas_projection($profile['atlas_projection'] ?? []);
        $normalized['dynamic_blocks'] = $this->normalize_dynamic_blocks($profile['dynamic_blocks'] ?? []);
        $normalized['featured_blocks'] = $this->normalize_featured_blocks($profile['featured_blocks'] ?? []);
        $normalized['faq'] = $this->normalize_faq($profile['faq'] ?? []);
        $normalized['contact'] = $this->normalize_contact($profile['contact'] ?? []);
        $normalized['governance'] = $this->normalize_governance($profile['governance'] ?? []);
        $normalized['cache'] = $this->normalize_cache($profile['cache'] ?? []);

        if (isset($profile['overrides']) && is_array($profile['overrides'])) {
            $normalized['overrides'] = $this->normalize_overrides($profile['overrides']);
        }

        $normalized['_normalized'] = [
            'component' => self::COMPONENT,
            'normalizer' => self::class,
            'public' => $normalized['status'] === 'published' && $normalized['visibility'] === 'public',
            'restricted' => $normalized['status'] === 'published' && $normalized['visibility'] === 'restricted',
            'has_sections' => !empty($normalized['sections']),
            'has_dynamic_blocks' => !empty($normalized['dynamic_blocks']),
            'has_featured_blocks' => !empty($normalized['featured_blocks']),
            'has_faq' => !empty($normalized['faq']),
            'cache_enabled' => $normalized['cache']['enabled'],
            'cache_ttl_seconds' => $normalized['cache']['ttl_seconds'],
        ];

        return $normalized;
    }

    /**
     * Normalize multiple decoded faculty profiles.
     *
     * @param array<int, array<string, mixed>> $profiles Profiles.
     * @return array<int, array<string, mixed>>
     */
    public function normalize_many(array $profiles): array {
        $normalized = [];

        foreach ($profiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }

            $normalized[] = $this->normalize($profile);
        }

        return $normalized;
    }

    /**
     * Normalize a profile into a small listing record.
     *
     * This is useful for index pages and APIs that must not expose all editorial
     * content.
     *
     * @param array<string, mixed> $profile Decoded faculty profile.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function normalize_listing_record(array $profile, ?array $registryrecord = null): array {
        $normalized = $this->normalize($profile, $registryrecord);

        return [
            'faculty_id' => $normalized['faculty_id'],
            'voie_id' => $normalized['voie_id'],
            'slug' => $normalized['slug'],
            'status' => $normalized['status'],
            'visibility' => $normalized['visibility'],
            'identity' => [
                'name' => $normalized['identity']['name'],
                'short_name' => $normalized['identity']['short_name'],
                'title_symbolique' => $normalized['identity']['title_symbolique'],
                'domain' => $normalized['identity']['domain'],
                'one_sentence' => $normalized['identity']['one_sentence'],
            ],
            'hero' => [
                'title' => $normalized['hero']['title'],
                'subtitle' => $normalized['hero']['subtitle'],
                'summary' => $normalized['hero']['summary'],
            ],
            'moodle' => [
                'category_idnumber' => $normalized['moodle']['category_idnumber'],
                'course_prefix' => $normalized['moodle']['course_prefix'],
                'hub_course_idnumber' => $normalized['moodle']['hub_course_idnumber'],
            ],
            '_normalized' => [
                'component' => self::COMPONENT,
                'normalizer' => self::class,
                'listing_record' => true,
                'public' => $normalized['_normalized']['public'],
                'restricted' => $normalized['_normalized']['restricted'],
            ],
        ];
    }

    /**
     * Normalize source_atlas.
     *
     * @param mixed $source Source Atlas object.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    private function normalize_source_atlas($source, ?array $registryrecord): array {
        $source = is_array($source) ? $source : [];

        return [
            'file' => $this->safe_filename(
                $this->string_value($source, 'file', $this->registry_string($registryrecord, 'atlas_file')),
                '/^voie_[a-z0-9_]+\.json$/'
            ),
            'schema_version_expected' => $this->string_value(
                $source,
                'schema_version_expected',
                self::ATLAS_SCHEMA_VERSION
            ),
            'sync_mode' => $this->enum_value(
                $source,
                'sync_mode',
                self::ATLAS_SYNC_MODES,
                self::SOURCE_ATLAS_SYNC_MODE_READ_ONLY
            ),
        ];
    }

    /**
     * Normalize Moodle metadata.
     *
     * @param mixed $moodle Moodle object.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    private function normalize_moodle($moodle, ?array $registryrecord): array {
        $moodle = is_array($moodle) ? $moodle : [];

        $courseprefix = $this->string_value(
            $moodle,
            'course_prefix',
            $this->registry_string($registryrecord, 'course_prefix')
        );

        return [
            'category_id' => $this->nullable_int_value($moodle, 'category_id'),
            'category_idnumber' => $this->uppercase_string(
                $this->string_value(
                    $moodle,
                    'category_idnumber',
                    $this->registry_string($registryrecord, 'category_idnumber')
                )
            ),
            'course_prefix' => $this->uppercase_string($courseprefix),
            'public_course_listing' => $this->bool_value($moodle, 'public_course_listing', true),
            'enrolment_visibility' => $this->enum_value(
                $moodle,
                'enrolment_visibility',
                self::ENROLMENT_VISIBILITIES,
                'public_info_only'
            ),
            'hub_course_idnumber' => $this->uppercase_string(
                $this->string_value($moodle, 'hub_course_idnumber', $courseprefix !== '' ? $courseprefix . '-HUB' : '')
            ),
        ];
    }

    /**
     * Normalize identity.
     *
     * @param mixed $identity Identity object.
     * @return array<string, string>
     */
    private function normalize_identity($identity): array {
        $identity = is_array($identity) ? $identity : [];

        return [
            'eyebrow' => $this->text_value($identity, 'eyebrow'),
            'name' => $this->text_value($identity, 'name'),
            'short_name' => $this->text_value($identity, 'short_name'),
            'title_symbolique' => $this->text_value($identity, 'title_symbolique'),
            'domain' => $this->text_value($identity, 'domain'),
            'level' => $this->text_value($identity, 'level'),
            'faculty_role' => $this->text_value($identity, 'faculty_role'),
            'one_sentence' => $this->text_value($identity, 'one_sentence'),
        ];
    }

    /**
     * Normalize SEO object.
     *
     * @param mixed $seo SEO object.
     * @param array<string, mixed> $profile Partial normalized profile.
     * @return array<string, mixed>
     */
    private function normalize_seo($seo, array $profile): array {
        $seo = is_array($seo) ? $seo : [];

        $fallbacktitle = $profile['identity']['name'] ?? '';
        $fallbackdescription = $profile['identity']['one_sentence'] ?? '';

        return [
            'title' => $this->text_value($seo, 'title', $fallbacktitle),
            'description' => $this->text_value($seo, 'description', $fallbackdescription),
            'keywords' => $this->string_list($seo['keywords'] ?? []),
        ];
    }

    /**
     * Normalize hero object.
     *
     * @param mixed $hero Hero object.
     * @param array<string, mixed> $profile Partial normalized profile.
     * @return array<string, mixed>
     */
    private function normalize_hero($hero, array $profile): array {
        $hero = is_array($hero) ? $hero : [];

        return [
            'title' => $this->text_value($hero, 'title', $profile['identity']['name'] ?? ''),
            'subtitle' => $this->text_value($hero, 'subtitle', $profile['identity']['title_symbolique'] ?? ''),
            'summary' => $this->text_value($hero, 'summary', $profile['identity']['one_sentence'] ?? ''),
            'primary_cta' => $this->normalize_cta($hero['primary_cta'] ?? [], 'Voir le programme', '#programme'),
            'secondary_cta' => $this->normalize_cta($hero['secondary_cta'] ?? [], 'Contacter la faculté', '#contact', true),
        ];
    }

    /**
     * Normalize navigation items.
     *
     * @param mixed $navigation Navigation list.
     * @return array<int, array<string, string>>
     */
    private function normalize_navigation($navigation): array {
        if (!is_array($navigation)) {
            return [];
        }

        $items = [];

        foreach ($navigation as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = $this->text_value($item, 'label');
            $target = $this->target_value($item['target'] ?? '');

            if ($label === '' && $target === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'target' => $target,
            ];
        }

        return $items;
    }

    /**
     * Normalize sections.
     *
     * @param mixed $sections Sections list.
     * @return array<int, array<string, mixed>>
     */
    private function normalize_sections($sections): array {
        if (!is_array($sections)) {
            return [];
        }

        $items = [];
        $seen = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $id = $this->anchor_id($section['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;

            $item = [
                'id' => $id,
                'type' => $this->enum_from_value($section['type'] ?? '', self::SECTION_TYPES, 'text'),
                'title' => $this->text_value($section, 'title'),
                'body' => $this->raw_text_value($section, 'body'),
            ];

            if (isset($section['items']) && is_array($section['items'])) {
                $item['items'] = $this->normalize_generic_items($section['items']);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Normalize Atlas projection flags.
     *
     * @param mixed $projection Atlas projection object.
     * @return array<string, bool>
     */
    private function normalize_atlas_projection($projection): array {
        $projection = is_array($projection) ? $projection : [];
        $normalized = [];

        foreach (self::DEFAULT_ATLAS_PROJECTION as $key => $default) {
            $normalized[$key] = $this->bool_value($projection, $key, $default);
        }

        return $normalized;
    }

    /**
     * Normalize dynamic blocks.
     *
     * @param mixed $blocks Dynamic blocks.
     * @return array<int, array<string, mixed>>
     */
    private function normalize_dynamic_blocks($blocks): array {
        if (!is_array($blocks)) {
            return [];
        }

        $items = [];
        $seen = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $id = $this->anchor_id($block['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;

            $items[] = [
                'id' => $id,
                'type' => $this->enum_from_value($block['type'] ?? '', self::DYNAMIC_BLOCK_TYPES, 'cta_panel'),
                'title' => $this->text_value($block, 'title'),
                'source' => $this->normalize_dynamic_source($block['source'] ?? []),
                'limit' => $this->positive_int_value($block, 'limit', 5),
                'visibility' => $this->enum_value($block, 'visibility', self::VISIBILITIES, 'public'),
                'empty_state' => $this->text_value($block, 'empty_state'),
            ];
        }

        return $items;
    }

    /**
     * Normalize one dynamic block source.
     *
     * @param mixed $source Source object.
     * @return array<string, mixed>
     */
    private function normalize_dynamic_source($source): array {
        $source = is_array($source) ? $source : [];

        $normalized = [
            'provider' => $this->enum_value($source, 'provider', self::DYNAMIC_PROVIDERS, 'none'),
        ];

        foreach ([
            'course_idnumber',
            'forum_name',
            'category_idnumber',
            'customfield',
            'news_key',
            'manual_key',
        ] as $field) {
            if (array_key_exists($field, $source)) {
                $normalized[$field] = $this->text_value($source, $field);
            }
        }

        return $normalized;
    }

    /**
     * Normalize featured blocks.
     *
     * @param mixed $blocks Featured blocks.
     * @return array<int, array<string, string>>
     */
    private function normalize_featured_blocks($blocks): array {
        if (!is_array($blocks)) {
            return [];
        }

        $items = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $title = $this->text_value($block, 'title');
            $body = $this->raw_text_value($block, 'body');

            if ($title === '' && $body === '') {
                continue;
            }

            $items[] = [
                'type' => $this->enum_from_value($block['type'] ?? '', self::FEATURED_BLOCK_TYPES, 'notice'),
                'title' => $title,
                'body' => $body,
            ];
        }

        return $items;
    }

    /**
     * Normalize FAQ.
     *
     * @param mixed $faq FAQ list.
     * @return array<int, array<string, string>>
     */
    private function normalize_faq($faq): array {
        if (!is_array($faq)) {
            return [];
        }

        $items = [];

        foreach ($faq as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = $this->text_value($item, 'question');
            $answer = $this->raw_text_value($item, 'answer');

            if ($question === '' && $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    /**
     * Normalize contact.
     *
     * @param mixed $contact Contact object.
     * @return array<string, mixed>
     */
    private function normalize_contact($contact): array {
        $contact = is_array($contact) ? $contact : [];

        return [
            'label' => $this->text_value($contact, 'label', 'Contact'),
            'body' => $this->raw_text_value($contact, 'body'),
            'email' => $this->email_value($contact['email'] ?? ''),
            'cta' => $this->normalize_cta($contact['cta'] ?? [], '', '', true),
        ];
    }

    /**
     * Normalize governance.
     *
     * @param mixed $governance Governance object.
     * @return array<string, mixed>
     */
    private function normalize_governance($governance): array {
        $governance = is_array($governance) ? $governance : [];

        $guardrails = $this->string_list($governance['public_claims_guardrails'] ?? []);
        if (empty($guardrails)) {
            $guardrails = [
                'Présenter la Voie comme un espace public de diffusion du savoir, de lecture critique et d’apprentissage ouvert.',
                self::INTERNAL_RECOGNITION_NOTICE,
                'Ne pas afficher de progression, notes ou données privées d’étudiants.',
            ];
        }

        return [
            'owner' => self::COMPONENT,
            'editorial_status' => $this->enum_value(
                $governance,
                'editorial_status',
                self::EDITORIAL_STATUSES,
                'draft'
            ),
            'last_reviewed' => $this->nullable_string_value($governance, 'last_reviewed'),
            'review_notes' => $this->raw_text_value($governance, 'review_notes'),
            'public_claims_guardrails' => $guardrails,
        ];
    }

    /**
     * Normalize cache settings.
     *
     * @param mixed $cache Cache object.
     * @return array<string, mixed>
     */
    private function normalize_cache($cache): array {
        $cache = is_array($cache) ? $cache : [];

        return [
            'enabled' => $this->bool_value($cache, 'enabled', true),
            'ttl_seconds' => $this->positive_int_value($cache, 'ttl_seconds', self::DEFAULT_CACHE_TTL_SECONDS),
        ];
    }

    /**
     * Normalize optional overrides without applying them.
     *
     * Overrides are editorial metadata. Application rules belong in the page
     * builder so the merge can stay explicit.
     *
     * @param array<string, mixed> $overrides Overrides object.
     * @return array<string, array<string, mixed>>
     */
    private function normalize_overrides(array $overrides): array {
        $normalized = [];

        foreach ($overrides as $key => $override) {
            if (!is_string($key) || !is_array($override)) {
                continue;
            }

            $normalized[$key] = [
                'enabled' => $this->bool_value($override, 'enabled', false),
                'reason' => $this->raw_text_value($override, 'reason'),
                'source_value' => $this->raw_text_value($override, 'source_value'),
                'public_value' => $this->raw_text_value($override, 'public_value'),
            ];
        }

        return $normalized;
    }

    /**
     * Normalize a CTA object.
     *
     * @param mixed $cta CTA object.
     * @param string $defaultlabel Default label.
     * @param string $defaulttarget Default target.
     * @param bool $allowempty Whether empty values are allowed.
     * @return array<string, string>
     */
    private function normalize_cta($cta, string $defaultlabel, string $defaulttarget, bool $allowempty = false): array {
        $cta = is_array($cta) ? $cta : [];

        $label = $this->text_value($cta, 'label', $defaultlabel);
        $target = $this->target_value($cta['target'] ?? $defaulttarget);

        if (!$allowempty && $label === '') {
            $label = $defaultlabel;
        }

        if (!$allowempty && $target === '') {
            $target = $defaulttarget;
        }

        return [
            'label' => $label,
            'target' => $target,
        ];
    }

    /**
     * Normalize generic editorial items used inside cards/two_column sections.
     *
     * @param array<int, mixed> $items Raw items.
     * @return array<int, array<string, string>>
     */
    private function normalize_generic_items(array $items): array {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = $this->text_value($item, 'title');
            $body = $this->raw_text_value($item, 'body');

            if ($title === '' && $body === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'body' => $body,
            ];
        }

        return $normalized;
    }

    /**
     * Return a string field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function string_value(array $data, string $key, string $default = ''): string {
        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            return $default;
        }

        return trim($data[$key]);
    }

    /**
     * Return a text field.
     *
     * Text fields are trimmed only. HTML filtering belongs to output/rendering.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function text_value(array $data, string $key, string $default = ''): string {
        return $this->string_value($data, $key, $default);
    }

    /**
     * Return a raw text field.
     *
     * Markdown/body filtering belongs to the page builder/output layer.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function raw_text_value(array $data, string $key, string $default = ''): string {
        return $this->string_value($data, $key, $default);
    }

    /**
     * Return a nullable string field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return string|null
     */
    private function nullable_string_value(array $data, string $key): ?string {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (!is_string($data[$key])) {
            return null;
        }

        $value = trim($data[$key]);

        return $value === '' ? null : $value;
    }

    /**
     * Return a boolean field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param bool $default Default.
     * @return bool
     */
    private function bool_value(array $data, string $key, bool $default): bool {
        if (!array_key_exists($key, $data) || !is_bool($data[$key])) {
            return $default;
        }

        return $data[$key];
    }

    /**
     * Return a nullable integer field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return int|null
     */
    private function nullable_int_value(array $data, string $key): ?int {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        if (is_string($data[$key]) && preg_match('/^\d+$/', $data[$key])) {
            return (int)$data[$key];
        }

        return null;
    }

    /**
     * Return a positive integer field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param int $default Default.
     * @return int
     */
    private function positive_int_value(array $data, string $key, int $default): int {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];

        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return max(0, (int)$value);
        }

        return $default;
    }

    /**
     * Return an enum field from an array key.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param array<int, string> $allowed Allowed values.
     * @param string $default Default.
     * @return string
     */
    private function enum_value(array $data, string $key, array $allowed, string $default): string {
        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            return $default;
        }

        return $this->enum_from_value($data[$key], $allowed, $default);
    }

    /**
     * Return an enum value.
     *
     * @param mixed $value Value.
     * @param array<int, string> $allowed Allowed values.
     * @param string $default Default.
     * @return string
     */
    private function enum_from_value($value, array $allowed, string $default): string {
        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);

        if (in_array($value, $allowed, true)) {
            return $value;
        }

        return $default;
    }

    /**
     * Return a string list with empty entries removed.
     *
     * @param mixed $value Value.
     * @return array<int, string>
     */
    private function string_list($value): array {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $items[] = $item;
        }

        return array_values(array_unique($items));
    }

    /**
     * Normalize a slug.
     *
     * @param string $slug Slug.
     * @return string
     */
    private function normalize_slug(string $slug): string {
        $slug = strtolower(trim($slug));

        if (!faculty_manifest::is_valid_slug($slug)) {
            return '';
        }

        return $slug;
    }

    /**
     * Normalize an anchor id without the hash.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function anchor_id($value): string {
        if (!is_string($value)) {
            return '';
        }

        $value = strtolower(trim($value));

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * Normalize a target URL or anchor.
     *
     * This method is intentionally conservative. Full target validation belongs
     * to faculty_validator.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function target_value($value): string {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $lower = strtolower($value);

        if ($this->starts_with($lower, 'javascript:')
                || $this->starts_with($lower, 'data:')
                || $this->starts_with($lower, 'file:')) {
            return '';
        }

        return $value;
    }

    /**
     * Normalize an email address.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function email_value($value): string {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return '';
        }

        return $value;
    }

    /**
     * Normalize a safe file name.
     *
     * @param string $filename File name.
     * @param string $pattern Regex pattern.
     * @return string
     */
    private function safe_filename(string $filename, string $pattern): string {
        $filename = trim($filename);

        if ($filename === '') {
            return '';
        }

        if (basename($filename) !== $filename) {
            return '';
        }

        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return '';
        }

        if (!preg_match($pattern, $filename)) {
            return '';
        }

        return $filename;
    }

    /**
     * Return a registry string value.
     *
     * @param array<string, mixed>|null $registryrecord Registry record.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function registry_string(?array $registryrecord, string $key, string $default = ''): string {
        if ($registryrecord === null) {
            return $default;
        }

        if (!array_key_exists($key, $registryrecord) || !is_string($registryrecord[$key])) {
            return $default;
        }

        $value = trim($registryrecord[$key]);

        return $value === '' ? $default : $value;
    }

    /**
     * Uppercase a stable technical identifier.
     *
     * @param string $value Value.
     * @return string
     */
    private function uppercase_string(string $value): string {
        return strtoupper(trim($value));
    }

    /**
     * Return whether a string starts with a prefix.
     *
     * @param string $value Value.
     * @param string $prefix Prefix.
     * @return bool
     */
    private function starts_with(string $value, string $prefix): bool {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}