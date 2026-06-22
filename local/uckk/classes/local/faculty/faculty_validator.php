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
 * Faculty profile validator for local_uckk.
 *
 * This validator checks canonical *.faculty.json files against DOC_12.
 *
 * It validates:
 * - JSON syntax;
 * - schema_version = UCKK-FACULTY-0.1;
 * - manifest identity consistency;
 * - required top-level sections;
 * - navigation targets;
 * - section, dynamic block and featured block types;
 * - allowed dynamic providers;
 * - Atlas projection completeness;
 * - governance guardrails;
 * - cache configuration;
 * - optional Atlas cross-file consistency.
 *
 * It does not:
 * - render HTML;
 * - query Moodle categories, courses, forums or calendars;
 * - grant permissions;
 * - sync Atlas data into Moodle;
 * - accept public URL paths as trusted file paths;
 * - invent slugs, providers, block types or custom fields.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use JsonException;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Validator for public faculty profile JSON files.
 */
final class faculty_validator {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Canonical faculty profile schema version. */
    public const FACULTY_SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Canonical Atlas schema expected by faculty profiles. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

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

    /** Allowed moodle.enrolment_visibility values. */
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

    /** Required Atlas projection boolean flags. */
    private const ATLAS_PROJECTION_FLAGS = [
        'show_definition_courte',
        'show_angle_fondamental',
        'show_competence_centrale',
        'show_seuils_progression',
        'show_courses',
        'show_course_codes',
        'show_concept_maitre',
        'show_concepts_associes',
        'show_artefacts',
        'show_criteres_passage',
        'show_projet_final',
        'show_limites_ethiques',
        'show_relations_intervoies',
        'show_tags',
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

    /** Allowed dynamic source providers. */
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

    /** Allowed governance editorial_status values. */
    private const EDITORIAL_STATUSES = [
        'draft',
        'review',
        'approved',
        'needs_update',
        'archived',
    ];

    /** Allowed override keys. */
    private const ALLOWED_OVERRIDES = [
        'identity.name',
        'identity.short_name',
        'identity.title_symbolique',
        'identity.domain',
        'hero.title',
        'hero.subtitle',
        'seo.title',
        'seo.description',
    ];

    /** Explicitly forbidden override keys. */
    private const FORBIDDEN_OVERRIDES = [
        'voie_id',
        'faculty_id',
        'slug',
        'source_atlas.file',
        'cours_id',
        'concept_id',
        'schema_version',
        'category_idnumber',
        'course_prefix',
    ];

    /**
     * Faculty manifest loader.
     *
     * @var faculty_manifest
     */
    private faculty_manifest $manifest;

    /**
     * Constructor.
     *
     * @param faculty_manifest|null $manifest Optional manifest loader, useful for tests.
     */
    public function __construct(?faculty_manifest $manifest = null) {
        $this->manifest = $manifest ?? new faculty_manifest();
    }

    /**
     * Validate every faculty profile listed in faculty_manifest.json.
     *
     * @return array<string, mixed>
     */
    public function validate_all(): array {
        $result = $this->new_result('faculty_manifest');

        try {
            $items = $this->manifest->get_items();
        } catch (Throwable $exception) {
            $this->add_error($result, 'manifest', $exception->getMessage());
            return $this->finish($result);
        }

        $children = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                $this->add_error($result, 'manifest.items', 'Manifest item must be an object.');
                continue;
            }

            $child = $this->validate_manifest_item($item);
            $children[] = $child;

            if (!$child['valid']) {
                foreach ($child['errors'] as $error) {
                    $this->add_error(
                        $result,
                        $error['field'],
                        '[' . ($item['slug'] ?? 'unknown') . '] ' . $error['message']
                    );
                }
            }

            foreach ($child['warnings'] as $warning) {
                $this->add_warning(
                    $result,
                    $warning['field'],
                    '[' . ($item['slug'] ?? 'unknown') . '] ' . $warning['message']
                );
            }
        }

        $result['children'] = $children;
        $result['counts']['profiles'] = count($children);

        return $this->finish($result);
    }

    /**
     * Validate one faculty profile by canonical slug.
     *
     * @param string $slug Faculty slug.
     * @return array<string, mixed>
     */
    public function validate_by_slug(string $slug): array {
        $result = $this->new_result($slug);

        $cleaned = strtolower(trim($slug));
        if (!faculty_manifest::is_valid_slug($cleaned)) {
            $this->add_error($result, 'slug', 'Invalid faculty slug format.');
            return $this->finish($result);
        }

        $item = $this->manifest->get_by_slug($cleaned);
        if ($item === null) {
            $this->add_error($result, 'slug', 'Unknown faculty slug: ' . $cleaned);
            return $this->finish($result);
        }

        return $this->validate_manifest_item($item);
    }

    /**
     * Validate one decoded faculty profile.
     *
     * @param array<string, mixed> $profile Decoded faculty profile.
     * @param array<string, mixed>|null $manifestitem Optional manifest item for identity checks.
     * @param array<string, mixed>|null $atlas Optional decoded Atlas voie for cross-file checks.
     * @param string $source Human-readable source label.
     * @return array<string, mixed>
     */
    public function validate_profile(
        array $profile,
        ?array $manifestitem = null,
        ?array $atlas = null,
        string $source = ''
    ): array {
        $result = $this->new_result($source);

        $this->validate_top_level($profile, $result);
        $this->validate_status_visibility($profile, $result);
        $this->validate_source_atlas($profile, $result);
        $this->validate_moodle($profile, $result);
        $this->validate_identity($profile, $result);
        $this->validate_seo($profile, $result);

        $anchors = $this->collect_rendered_anchors($profile);

        $this->validate_hero($profile, $result, $anchors);
        $this->validate_navigation($profile, $result, $anchors);
        $this->validate_sections($profile, $result);
        $this->validate_atlas_projection($profile, $result);
        $this->validate_dynamic_blocks($profile, $result);
        $this->validate_featured_blocks($profile, $result);
        $this->validate_faq($profile, $result);
        $this->validate_contact($profile, $result, $anchors);
        $this->validate_governance($profile, $result);
        $this->validate_cache($profile, $result);
        $this->validate_overrides($profile, $result);

        if ($manifestitem !== null) {
            $this->validate_manifest_consistency($profile, $manifestitem, $result);
        }

        if ($atlas !== null) {
            $this->validate_atlas_consistency($profile, $atlas, $result);
        }

        return $this->finish($result);
    }

    /**
     * Validate one faculty profile file.
     *
     * @param string $path Absolute file path.
     * @param array<string, mixed>|null $manifestitem Optional manifest item.
     * @param array<string, mixed>|null $atlas Optional decoded Atlas voie.
     * @return array<string, mixed>
     */
    public function validate_file(string $path, ?array $manifestitem = null, ?array $atlas = null): array {
        $result = $this->new_result($path);

        $profile = $this->read_json_file($path, $result);
        if ($profile === null) {
            return $this->finish($result);
        }

        $profilecheck = $this->validate_profile($profile, $manifestitem, $atlas, $path);

        return $this->merge_results($result, $profilecheck);
    }

    /**
     * Validate a profile and return only a boolean.
     *
     * @param array<string, mixed> $profile Decoded faculty profile.
     * @param array<string, mixed>|null $manifestitem Optional manifest item.
     * @param array<string, mixed>|null $atlas Optional decoded Atlas voie.
     * @return bool
     */
    public function is_valid_profile(array $profile, ?array $manifestitem = null, ?array $atlas = null): bool {
        return $this->validate_profile($profile, $manifestitem, $atlas)['valid'];
    }

    /**
     * Validate a single manifest item by reading the faculty profile and Atlas file.
     *
     * @param array<string, mixed> $item Manifest item.
     * @return array<string, mixed>
     */
    private function validate_manifest_item(array $item): array {
        $source = (string)($item['slug'] ?? $item['faculty_file'] ?? 'faculty_profile');
        $result = $this->new_result($source);

        try {
            $profilepath = $this->manifest->get_faculty_file_path($item);
        } catch (Throwable $exception) {
            $this->add_error($result, 'faculty_file', $exception->getMessage());
            return $this->finish($result);
        }

        $profile = $this->read_json_file($profilepath, $result);
        if ($profile === null) {
            return $this->finish($result);
        }

        $atlas = null;
        try {
            $atlaspath = $this->manifest->get_atlas_file_path($item);
            $atlas = $this->read_json_file($atlaspath, $result, 'source_atlas.file');
        } catch (Throwable $exception) {
            $this->add_error($result, 'source_atlas.file', $exception->getMessage());
        }

        $profilecheck = $this->validate_profile($profile, $item, $atlas, $profilepath);

        return $this->merge_results($result, $profilecheck);
    }

    /**
     * Validate required top-level fields.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_top_level(array $profile, array &$result): void {
        $required = [
            'schema_version',
            'faculty_id',
            'voie_id',
            'slug',
            'status',
            'visibility',
            'source_atlas',
            'moodle',
            'identity',
            'seo',
            'hero',
            'navigation',
            'sections',
            'atlas_projection',
            'dynamic_blocks',
            'featured_blocks',
            'faq',
            'contact',
            'governance',
            'cache',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $profile)) {
                $this->add_error($result, $field, 'Missing required top-level field.');
            }
        }

        if (($profile['schema_version'] ?? null) !== self::FACULTY_SCHEMA_VERSION) {
            $this->add_error(
                $result,
                'schema_version',
                'schema_version must be ' . self::FACULTY_SCHEMA_VERSION . '.'
            );
        }

        $this->require_string($profile, 'faculty_id', $result);
        $this->require_string($profile, 'voie_id', $result);
        $this->require_string($profile, 'slug', $result);

        if (isset($profile['faculty_id']) && is_string($profile['faculty_id'])
                && !preg_match('/^faculty_[a-z0-9_]+$/', $profile['faculty_id'])) {
            $this->add_error($result, 'faculty_id', 'faculty_id has an invalid format.');
        }

        if (isset($profile['voie_id']) && is_string($profile['voie_id'])
                && !preg_match('/^voie_[a-z0-9_]+$/', $profile['voie_id'])) {
            $this->add_error($result, 'voie_id', 'voie_id has an invalid format.');
        }

        if (isset($profile['slug']) && is_string($profile['slug'])
                && !faculty_manifest::is_valid_slug($profile['slug'])) {
            $this->add_error($result, 'slug', 'slug has an invalid format.');
        }
    }

    /**
     * Validate status and visibility.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_status_visibility(array $profile, array &$result): void {
        $this->require_enum($profile, 'status', self::STATUSES, $result);
        $this->require_enum($profile, 'visibility', self::VISIBILITIES, $result);

        if (($profile['status'] ?? null) === 'published' && ($profile['visibility'] ?? null) === 'hidden') {
            $this->add_warning($result, 'visibility', 'Published faculty profile is hidden.');
        }
    }

    /**
     * Validate source_atlas.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_source_atlas(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'source_atlas', $result)) {
            return;
        }

        $source = $profile['source_atlas'];

        $this->require_string($source, 'file', $result, 'source_atlas.file');
        $this->require_string($source, 'schema_version_expected', $result, 'source_atlas.schema_version_expected');
        $this->require_enum($source, 'sync_mode', self::ATLAS_SYNC_MODES, $result, 'source_atlas.sync_mode');

        if (isset($source['file']) && is_string($source['file'])) {
            if (basename($source['file']) !== $source['file'] || strpos($source['file'], '/') !== false
                    || strpos($source['file'], '\\') !== false) {
                $this->add_error($result, 'source_atlas.file', 'Atlas file must be a file name, not a path.');
            }

            if (!preg_match('/^voie_[a-z0-9_]+\.json$/', $source['file'])) {
                $this->add_error($result, 'source_atlas.file', 'Atlas file has an invalid format.');
            }
        }

        if (($source['schema_version_expected'] ?? null) !== self::ATLAS_SCHEMA_VERSION) {
            $this->add_error(
                $result,
                'source_atlas.schema_version_expected',
                'Expected Atlas schema version must be ' . self::ATLAS_SCHEMA_VERSION . '.'
            );
        }
    }

    /**
     * Validate moodle object.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_moodle(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'moodle', $result)) {
            return;
        }

        $moodle = $profile['moodle'];

        if (!array_key_exists('category_id', $moodle)) {
            $this->add_error($result, 'moodle.category_id', 'Missing required field.');
        } else if ($moodle['category_id'] !== null && !is_int($moodle['category_id'])) {
            $this->add_error($result, 'moodle.category_id', 'category_id must be null or integer.');
        }

        $this->require_string($moodle, 'category_idnumber', $result, 'moodle.category_idnumber');
        $this->require_string($moodle, 'course_prefix', $result, 'moodle.course_prefix');
        $this->require_bool($moodle, 'public_course_listing', $result, 'moodle.public_course_listing');
        $this->require_enum(
            $moodle,
            'enrolment_visibility',
            self::ENROLMENT_VISIBILITIES,
            $result,
            'moodle.enrolment_visibility'
        );
        $this->require_string($moodle, 'hub_course_idnumber', $result, 'moodle.hub_course_idnumber');

        if (isset($moodle['category_idnumber']) && is_string($moodle['category_idnumber'])
                && !preg_match('/^UCKK-[A-Z0-9]+$/', $moodle['category_idnumber'])) {
            $this->add_error($result, 'moodle.category_idnumber', 'category_idnumber has an invalid format.');
        }

        if (isset($moodle['course_prefix']) && is_string($moodle['course_prefix'])
                && !preg_match('/^[A-Z0-9]+$/', $moodle['course_prefix'])) {
            $this->add_error($result, 'moodle.course_prefix', 'course_prefix has an invalid format.');
        }

        if (isset($moodle['hub_course_idnumber']) && is_string($moodle['hub_course_idnumber'])
                && !preg_match('/^[A-Z0-9]+-HUB$/', $moodle['hub_course_idnumber'])) {
            $this->add_error($result, 'moodle.hub_course_idnumber', 'hub_course_idnumber must follow the HUB convention.');
        }
    }

    /**
     * Validate identity object.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_identity(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'identity', $result)) {
            return;
        }

        $identity = $profile['identity'];

        foreach ([
            'eyebrow',
            'name',
            'short_name',
            'title_symbolique',
            'domain',
            'level',
            'faculty_role',
            'one_sentence',
        ] as $field) {
            $this->require_string($identity, $field, $result, 'identity.' . $field);
        }
    }

    /**
     * Validate seo object.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_seo(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'seo', $result)) {
            return;
        }

        $seo = $profile['seo'];

        $this->require_string($seo, 'title', $result, 'seo.title');
        $this->require_string($seo, 'description', $result, 'seo.description');

        if (!array_key_exists('keywords', $seo) || !is_array($seo['keywords'])) {
            $this->add_error($result, 'seo.keywords', 'keywords must be an array.');
            return;
        }

        foreach ($seo['keywords'] as $index => $keyword) {
            if (!is_string($keyword) || trim($keyword) === '') {
                $this->add_error($result, 'seo.keywords.' . $index, 'Keyword must be a non-empty string.');
            }
        }

        $this->warn_public_claims($seo['title'] ?? '', 'seo.title', $result);
        $this->warn_public_claims($seo['description'] ?? '', 'seo.description', $result);
    }

    /**
     * Validate hero object.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     * @param array<int, string> $anchors Rendered anchors.
     */
    private function validate_hero(array $profile, array &$result, array $anchors): void {
        if (!$this->require_object($profile, 'hero', $result)) {
            return;
        }

        $hero = $profile['hero'];

        foreach (['title', 'subtitle', 'summary'] as $field) {
            $this->require_string($hero, $field, $result, 'hero.' . $field);
        }

        $this->validate_cta($hero, 'primary_cta', $result, $anchors, false, 'hero.primary_cta');
        $this->validate_cta($hero, 'secondary_cta', $result, $anchors, true, 'hero.secondary_cta');
    }

    /**
     * Validate navigation.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     * @param array<int, string> $anchors Rendered anchors.
     */
    private function validate_navigation(array $profile, array &$result, array $anchors): void {
        if (!array_key_exists('navigation', $profile) || !is_array($profile['navigation'])) {
            $this->add_error($result, 'navigation', 'navigation must be an array.');
            return;
        }

        foreach ($profile['navigation'] as $index => $item) {
            $base = 'navigation.' . $index;

            if (!is_array($item)) {
                $this->add_error($result, $base, 'Navigation item must be an object.');
                continue;
            }

            $this->require_string($item, 'label', $result, $base . '.label');
            $this->require_string($item, 'target', $result, $base . '.target');

            if (isset($item['target']) && is_string($item['target'])) {
                $this->validate_target($item['target'], $base . '.target', $result, $anchors, false);
            }
        }
    }

    /**
     * Validate sections.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_sections(array $profile, array &$result): void {
        if (!array_key_exists('sections', $profile) || !is_array($profile['sections'])) {
            $this->add_error($result, 'sections', 'sections must be an array.');
            return;
        }

        $ids = [];

        foreach ($profile['sections'] as $index => $section) {
            $base = 'sections.' . $index;

            if (!is_array($section)) {
                $this->add_error($result, $base, 'Section must be an object.');
                continue;
            }

            $this->require_string($section, 'id', $result, $base . '.id');
            $this->require_enum($section, 'type', self::SECTION_TYPES, $result, $base . '.type');
            $this->require_string($section, 'title', $result, $base . '.title');
            $this->require_string($section, 'body', $result, $base . '.body');

            if (isset($section['id']) && is_string($section['id'])) {
                if (!$this->is_valid_anchor_id($section['id'])) {
                    $this->add_error($result, $base . '.id', 'Section id has an invalid format.');
                }

                if (isset($ids[$section['id']])) {
                    $this->add_error($result, $base . '.id', 'Section id must be unique.');
                }

                $ids[$section['id']] = true;
            }
        }
    }

    /**
     * Validate atlas_projection.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_atlas_projection(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'atlas_projection', $result)) {
            return;
        }

        $projection = $profile['atlas_projection'];

        foreach (self::ATLAS_PROJECTION_FLAGS as $flag) {
            $this->require_bool($projection, $flag, $result, 'atlas_projection.' . $flag);
        }

        foreach (array_keys($projection) as $field) {
            if (!in_array($field, self::ATLAS_PROJECTION_FLAGS, true)) {
                $this->add_warning($result, 'atlas_projection.' . $field, 'Unknown atlas_projection flag.');
            }
        }

        if (($projection['show_concepts_associes'] ?? null) === true) {
            $this->add_warning(
                $result,
                'atlas_projection.show_concepts_associes',
                'show_concepts_associes should be false by default on public pages.'
            );
        }

        if (($projection['show_criteres_passage'] ?? null) === true) {
            $this->add_warning(
                $result,
                'atlas_projection.show_criteres_passage',
                'show_criteres_passage should be false by default on public pages.'
            );
        }
    }

    /**
     * Validate dynamic_blocks.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_dynamic_blocks(array $profile, array &$result): void {
        if (!array_key_exists('dynamic_blocks', $profile) || !is_array($profile['dynamic_blocks'])) {
            $this->add_error($result, 'dynamic_blocks', 'dynamic_blocks must be an array.');
            return;
        }

        $ids = [];

        foreach ($profile['dynamic_blocks'] as $index => $block) {
            $base = 'dynamic_blocks.' . $index;

            if (!is_array($block)) {
                $this->add_error($result, $base, 'Dynamic block must be an object.');
                continue;
            }

            $this->require_string($block, 'id', $result, $base . '.id');
            $this->require_enum($block, 'type', self::DYNAMIC_BLOCK_TYPES, $result, $base . '.type');
            $this->require_string($block, 'title', $result, $base . '.title');
            $this->require_enum($block, 'visibility', self::VISIBILITIES, $result, $base . '.visibility');
            $this->require_string($block, 'empty_state', $result, $base . '.empty_state');

            if (!array_key_exists('limit', $block) || !is_int($block['limit']) || $block['limit'] < 0) {
                $this->add_error($result, $base . '.limit', 'limit must be an integer greater than or equal to zero.');
            }

            if (isset($block['id']) && is_string($block['id'])) {
                if (!$this->is_valid_anchor_id($block['id'])) {
                    $this->add_error($result, $base . '.id', 'Dynamic block id has an invalid format.');
                }

                if (isset($ids[$block['id']])) {
                    $this->add_error($result, $base . '.id', 'Dynamic block id must be unique.');
                }

                $ids[$block['id']] = true;
            }

            if (!array_key_exists('source', $block) || !is_array($block['source'])) {
                $this->add_error($result, $base . '.source', 'source must be an object.');
                continue;
            }

            $source = $block['source'];
            $this->require_enum($source, 'provider', self::DYNAMIC_PROVIDERS, $result, $base . '.source.provider');

            if (($source['provider'] ?? null) === 'moodle_forum') {
                $this->require_string($source, 'course_idnumber', $result, $base . '.source.course_idnumber');
                $this->require_string($source, 'forum_name', $result, $base . '.source.forum_name');
            }

            if (($source['provider'] ?? null) === 'moodle_category') {
                $this->require_string($source, 'category_idnumber', $result, $base . '.source.category_idnumber');
            }
        }
    }

    /**
     * Validate featured_blocks.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_featured_blocks(array $profile, array &$result): void {
        if (!array_key_exists('featured_blocks', $profile) || !is_array($profile['featured_blocks'])) {
            $this->add_error($result, 'featured_blocks', 'featured_blocks must be an array.');
            return;
        }

        foreach ($profile['featured_blocks'] as $index => $block) {
            $base = 'featured_blocks.' . $index;

            if (!is_array($block)) {
                $this->add_error($result, $base, 'Featured block must be an object.');
                continue;
            }

            $this->require_enum($block, 'type', self::FEATURED_BLOCK_TYPES, $result, $base . '.type');
            $this->require_string($block, 'title', $result, $base . '.title');
            $this->require_string($block, 'body', $result, $base . '.body');

            $this->warn_public_claims($block['body'] ?? '', $base . '.body', $result);
        }
    }

    /**
     * Validate FAQ.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_faq(array $profile, array &$result): void {
        if (!array_key_exists('faq', $profile) || !is_array($profile['faq'])) {
            $this->add_error($result, 'faq', 'faq must be an array.');
            return;
        }

        foreach ($profile['faq'] as $index => $item) {
            $base = 'faq.' . $index;

            if (!is_array($item)) {
                $this->add_error($result, $base, 'FAQ item must be an object.');
                continue;
            }

            $this->require_string($item, 'question', $result, $base . '.question');
            $this->require_string($item, 'answer', $result, $base . '.answer');

            $this->warn_public_claims($item['answer'] ?? '', $base . '.answer', $result);
        }
    }

    /**
     * Validate contact.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     * @param array<int, string> $anchors Rendered anchors.
     */
    private function validate_contact(array $profile, array &$result, array $anchors): void {
        if (!$this->require_object($profile, 'contact', $result)) {
            return;
        }

        $contact = $profile['contact'];

        $this->require_string($contact, 'label', $result, 'contact.label');
        $this->require_string($contact, 'body', $result, 'contact.body');

        if (!array_key_exists('email', $contact) || !is_string($contact['email'])) {
            $this->add_error($result, 'contact.email', 'email must be a string; use empty string when absent.');
        } else if ($contact['email'] !== '' && filter_var($contact['email'], FILTER_VALIDATE_EMAIL) === false) {
            $this->add_error($result, 'contact.email', 'email must be valid or empty.');
        }

        $this->validate_cta($contact, 'cta', $result, $anchors, true, 'contact.cta');
    }

    /**
     * Validate governance.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_governance(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'governance', $result)) {
            return;
        }

        $governance = $profile['governance'];

        $this->require_string($governance, 'owner', $result, 'governance.owner');
        $this->require_enum(
            $governance,
            'editorial_status',
            self::EDITORIAL_STATUSES,
            $result,
            'governance.editorial_status'
        );

        if (($governance['owner'] ?? null) !== self::COMPONENT) {
            $this->add_error($result, 'governance.owner', 'owner must be local_uckk.');
        }

        if (!array_key_exists('last_reviewed', $governance)) {
            $this->add_error($result, 'governance.last_reviewed', 'Missing required field.');
        } else if ($governance['last_reviewed'] !== null && !is_string($governance['last_reviewed'])) {
            $this->add_error($result, 'governance.last_reviewed', 'last_reviewed must be null or string.');
        }

        $this->require_string($governance, 'review_notes', $result, 'governance.review_notes', true);

        if (!array_key_exists('public_claims_guardrails', $governance)
                || !is_array($governance['public_claims_guardrails'])) {
            $this->add_error($result, 'governance.public_claims_guardrails', 'public_claims_guardrails must be an array.');
            return;
        }

        if (count($governance['public_claims_guardrails']) === 0) {
            $this->add_error($result, 'governance.public_claims_guardrails', 'At least one public guardrail is required.');
        }

        foreach ($governance['public_claims_guardrails'] as $index => $guardrail) {
            if (!is_string($guardrail) || trim($guardrail) === '') {
                $this->add_error(
                    $result,
                    'governance.public_claims_guardrails.' . $index,
                    'Guardrail must be a non-empty string.'
                );
            }
        }
    }

    /**
     * Validate cache config.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_cache(array $profile, array &$result): void {
        if (!$this->require_object($profile, 'cache', $result)) {
            return;
        }

        $cache = $profile['cache'];

        $this->require_bool($cache, 'enabled', $result, 'cache.enabled');

        if (!array_key_exists('ttl_seconds', $cache) || !is_int($cache['ttl_seconds']) || $cache['ttl_seconds'] < 0) {
            $this->add_error($result, 'cache.ttl_seconds', 'ttl_seconds must be an integer greater than or equal to zero.');
        }
    }

    /**
     * Validate optional overrides.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $result Result.
     */
    private function validate_overrides(array $profile, array &$result): void {
        if (!array_key_exists('overrides', $profile)) {
            return;
        }

        if (!is_array($profile['overrides'])) {
            $this->add_error($result, 'overrides', 'overrides must be an object.');
            return;
        }

        foreach ($profile['overrides'] as $key => $override) {
            $field = 'overrides.' . $key;

            if (in_array($key, self::FORBIDDEN_OVERRIDES, true)) {
                $this->add_error($result, $field, 'This override key is forbidden.');
            }

            if (!in_array($key, self::ALLOWED_OVERRIDES, true)) {
                $this->add_error($result, $field, 'Unknown or undocumented override key.');
            }

            if (!is_array($override)) {
                $this->add_error($result, $field, 'Override entry must be an object.');
                continue;
            }

            $this->require_bool($override, 'enabled', $result, $field . '.enabled');
            $this->require_string($override, 'reason', $result, $field . '.reason');
            $this->require_string($override, 'source_value', $result, $field . '.source_value', true);
            $this->require_string($override, 'public_value', $result, $field . '.public_value', true);
        }
    }

    /**
     * Validate consistency against the manifest item.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $item Manifest item.
     * @param array<string, mixed> $result Result.
     */
    private function validate_manifest_consistency(array $profile, array $item, array &$result): void {
        $comparisons = [
            'faculty_id' => 'faculty_id',
            'voie_id' => 'voie_id',
            'slug' => 'slug',
            'status' => 'status',
            'visibility' => 'visibility',
        ];

        foreach ($comparisons as $profilefield => $manifestfield) {
            if (array_key_exists($profilefield, $profile)
                    && array_key_exists($manifestfield, $item)
                    && $profile[$profilefield] !== $item[$manifestfield]) {
                $this->add_error(
                    $result,
                    $profilefield,
                    $profilefield . ' must match faculty_manifest.json.'
                );
            }
        }

        if (($profile['source_atlas']['file'] ?? null) !== ($item['atlas_file'] ?? null)) {
            $this->add_error($result, 'source_atlas.file', 'source_atlas.file must match manifest atlas_file.');
        }

        if (($profile['moodle']['category_idnumber'] ?? null) !== ($item['category_idnumber'] ?? null)) {
            $this->add_error(
                $result,
                'moodle.category_idnumber',
                'moodle.category_idnumber must match manifest category_idnumber.'
            );
        }

        if (($profile['moodle']['course_prefix'] ?? null) !== ($item['course_prefix'] ?? null)) {
            $this->add_error(
                $result,
                'moodle.course_prefix',
                'moodle.course_prefix must match manifest course_prefix.'
            );
        }
    }

    /**
     * Validate consistency against decoded Atlas voie JSON.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, mixed> $result Result.
     */
    private function validate_atlas_consistency(array $profile, array $atlas, array &$result): void {
        if (($atlas['schema_version'] ?? null) !== self::ATLAS_SCHEMA_VERSION) {
            $this->add_error(
                $result,
                'source_atlas.schema_version_expected',
                'Referenced Atlas file schema_version must be ' . self::ATLAS_SCHEMA_VERSION . '.'
            );
        }

        if (($profile['voie_id'] ?? null) !== ($atlas['voie_id'] ?? null)) {
            $this->add_error($result, 'voie_id', 'faculty.voie_id must match atlas.voie_id.');
        }

        if (($profile['moodle']['course_prefix'] ?? null) !== ($atlas['code'] ?? null)) {
            $this->add_error($result, 'moodle.course_prefix', 'moodle.course_prefix must match atlas.code.');
        }

        $this->compare_atlas_identity(
            $profile,
            $atlas,
            'identity.title_symbolique',
            'titre_symbolique',
            true,
            $result
        );

        $this->compare_atlas_identity(
            $profile,
            $atlas,
            'identity.domain',
            'domaine_operatoire',
            true,
            $result
        );

        $this->compare_atlas_identity(
            $profile,
            $atlas,
            'identity.level',
            'niveau_vise',
            false,
            $result
        );

        $this->validate_atlas_relations($atlas, $result);
    }

    /**
     * Compare one faculty identity value to one Atlas value.
     *
     * @param array<string, mixed> $profile Profile.
     * @param array<string, mixed> $atlas Atlas voie.
     * @param string $profilepath Dot path in profile.
     * @param string $atlasfield Atlas field.
     * @param bool $overrideallowed Whether an enabled override may explain the difference.
     * @param array<string, mixed> $result Result.
     */
    private function compare_atlas_identity(
        array $profile,
        array $atlas,
        string $profilepath,
        string $atlasfield,
        bool $overrideallowed,
        array &$result
    ): void {
        $profilevalue = $this->get_dot_value($profile, $profilepath);
        $atlasvalue = $atlas[$atlasfield] ?? null;

        if (!is_string($profilevalue) || !is_string($atlasvalue)) {
            return;
        }

        if ($profilevalue === $atlasvalue) {
            return;
        }

        if ($overrideallowed && $this->has_enabled_override($profile, $profilepath)) {
            return;
        }

        $message = $profilepath . ' must match atlas.' . $atlasfield;
        if ($overrideallowed) {
            $message .= ' unless an explicit override is declared.';
        }

        $this->add_error($result, $profilepath, $message);
    }

    /**
     * Validate Atlas relations against known voie_id values from the manifest.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, mixed> $result Result.
     */
    private function validate_atlas_relations(array $atlas, array &$result): void {
        if (!array_key_exists('relations_intervoies', $atlas)) {
            return;
        }

        if (!is_array($atlas['relations_intervoies'])) {
            $this->add_error($result, 'atlas.relations_intervoies', 'relations_intervoies must be an array.');
            return;
        }

        try {
            $validvoieids = [];
            foreach ($this->manifest->get_items() as $item) {
                if (isset($item['voie_id']) && is_string($item['voie_id'])) {
                    $validvoieids[$item['voie_id']] = true;
                }
            }
        } catch (Throwable $exception) {
            $this->add_warning($result, 'atlas.relations_intervoies', 'Unable to load manifest for relation checks.');
            return;
        }

        foreach ($atlas['relations_intervoies'] as $index => $relation) {
            if (!is_array($relation)) {
                $this->add_error($result, 'atlas.relations_intervoies.' . $index, 'Relation must be an object.');
                continue;
            }

            if (!isset($relation['voie_id']) || !is_string($relation['voie_id'])) {
                $this->add_error(
                    $result,
                    'atlas.relations_intervoies.' . $index . '.voie_id',
                    'Relation voie_id is required.'
                );
                continue;
            }

            if (!isset($validvoieids[$relation['voie_id']])) {
                $this->add_error(
                    $result,
                    'atlas.relations_intervoies.' . $index . '.voie_id',
                    'Relation points to an unknown voie_id.'
                );
            }
        }
    }

    /**
     * Validate a CTA object.
     *
     * @param array<string, mixed> $parent Parent object.
     * @param string $key CTA key.
     * @param array<string, mixed> $result Result.
     * @param array<int, string> $anchors Rendered anchors.
     * @param bool $allowemptytarget Whether an empty target is allowed.
     * @param string $field Field path.
     */
    private function validate_cta(
        array $parent,
        string $key,
        array &$result,
        array $anchors,
        bool $allowemptytarget,
        string $field
    ): void {
        if (!array_key_exists($key, $parent) || !is_array($parent[$key])) {
            $this->add_error($result, $field, 'CTA must be an object.');
            return;
        }

        $cta = $parent[$key];

        $this->require_string($cta, 'label', $result, $field . '.label', true);
        $this->require_string($cta, 'target', $result, $field . '.target', $allowemptytarget);

        if (isset($cta['target']) && is_string($cta['target'])) {
            $this->validate_target($cta['target'], $field . '.target', $result, $anchors, $allowemptytarget);
        }
    }

    /**
     * Validate a target URL or anchor.
     *
     * @param string $target Target.
     * @param string $field Field path.
     * @param array<string, mixed> $result Result.
     * @param array<int, string> $anchors Rendered anchors.
     * @param bool $allowempty Whether empty string is allowed.
     */
    private function validate_target(
        string $target,
        string $field,
        array &$result,
        array $anchors,
        bool $allowempty
    ): void {
        $target = trim($target);

        if ($target === '') {
            if (!$allowempty) {
                $this->add_error($result, $field, 'Target cannot be empty.');
            }
            return;
        }

        $lower = strtolower($target);

        if ($this->starts_with($lower, 'javascript:')
                || $this->starts_with($lower, 'data:')
                || $this->starts_with($lower, 'file:')) {
            $this->add_error($result, $field, 'Forbidden target scheme.');
            return;
        }

        if ($this->starts_with($target, '#')) {
            if (!preg_match('/^#[a-z0-9]+(?:-[a-z0-9]+)*$/', $target)) {
                $this->add_error($result, $field, 'Anchor target has an invalid format.');
                return;
            }

            if (!in_array($target, $anchors, true)) {
                $this->add_error($result, $field, 'Anchor target is orphaned: ' . $target);
            }

            return;
        }

        if ($this->starts_with($target, '/local/uckk/')) {
            if (strpos($target, '..') !== false) {
                $this->add_error($result, $field, 'Local target must not contain path traversal.');
            }
            return;
        }

        if ($this->starts_with($target, 'https://')) {
            if (filter_var($target, FILTER_VALIDATE_URL) === false) {
                $this->add_error($result, $field, 'HTTPS target is not a valid URL.');
            }
            return;
        }

        if ($target === 'contact.php') {
            return;
        }

        $this->add_error($result, $field, 'Target must be an anchor, /local/uckk/... path, HTTPS URL, or contact.php.');
    }

    /**
     * Collect anchors rendered by known profile sections.
     *
     * @param array<string, mixed> $profile Profile.
     * @return array<int, string>
     */
    private function collect_rendered_anchors(array $profile): array {
        $anchors = [
            '#hero',
            '#programme',
            '#atlas',
            '#atlas-projection',
            '#featured',
            '#contact',
        ];

        if (isset($profile['sections']) && is_array($profile['sections'])) {
            foreach ($profile['sections'] as $section) {
                if (is_array($section) && isset($section['id']) && is_string($section['id'])
                        && $this->is_valid_anchor_id($section['id'])) {
                    $anchors[] = '#' . $section['id'];
                }
            }
        }

        if (isset($profile['dynamic_blocks']) && is_array($profile['dynamic_blocks'])) {
            foreach ($profile['dynamic_blocks'] as $block) {
                if (is_array($block) && isset($block['id']) && is_string($block['id'])
                        && $this->is_valid_anchor_id($block['id'])) {
                    $anchors[] = '#' . $block['id'];
                }
            }
        }

        if (!empty($profile['faq']) && is_array($profile['faq'])) {
            $anchors[] = '#faq';
        }

        if (isset($profile['atlas_projection']) && is_array($profile['atlas_projection'])) {
            if (($profile['atlas_projection']['show_courses'] ?? false) === true) {
                $anchors[] = '#cours';
            }

            if (($profile['atlas_projection']['show_projet_final'] ?? false) === true) {
                $anchors[] = '#projet-final';
            }

            if (($profile['atlas_projection']['show_limites_ethiques'] ?? false) === true) {
                $anchors[] = '#limites-ethiques';
            }

            if (($profile['atlas_projection']['show_relations_intervoies'] ?? false) === true) {
                $anchors[] = '#relations';
            }
        }

        return array_values(array_unique($anchors));
    }

    /**
     * Read a JSON file.
     *
     * @param string $path Absolute path.
     * @param array<string, mixed> $result Result.
     * @param string $field Field path.
     * @return array<string, mixed>|null
     */
    private function read_json_file(string $path, array &$result, string $field = 'file'): ?array {
        if (!is_readable($path)) {
            $this->add_error($result, $field, 'JSON file is not readable: ' . $path);
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->add_error($result, $field, 'Unable to read JSON file: ' . $path);
            return null;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->add_error($result, $field, 'Invalid JSON syntax: ' . $exception->getMessage());
            return null;
        }

        if (!is_array($data)) {
            $this->add_error($result, $field, 'JSON root must be an object.');
            return null;
        }

        return $data;
    }

    /**
     * Require an object field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param array<string, mixed> $result Result.
     * @param string|null $field Field path.
     * @return bool
     */
    private function require_object(array $data, string $key, array &$result, ?string $field = null): bool {
        $field = $field ?? $key;

        if (!array_key_exists($key, $data) || !is_array($data[$key]) || $this->is_list_array($data[$key])) {
            $this->add_error($result, $field, 'Field must be an object.');
            return false;
        }

        return true;
    }

    /**
     * Require a string field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param array<string, mixed> $result Result.
     * @param string|null $field Field path.
     * @param bool $allowempty Whether empty string is allowed.
     */
    private function require_string(
        array $data,
        string $key,
        array &$result,
        ?string $field = null,
        bool $allowempty = false
    ): void {
        $field = $field ?? $key;

        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            $this->add_error($result, $field, 'Field must be a string.');
            return;
        }

        if (!$allowempty && trim($data[$key]) === '') {
            $this->add_error($result, $field, 'Field cannot be empty.');
        }
    }

    /**
     * Require a boolean field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param array<string, mixed> $result Result.
     * @param string|null $field Field path.
     */
    private function require_bool(array $data, string $key, array &$result, ?string $field = null): void {
        $field = $field ?? $key;

        if (!array_key_exists($key, $data) || !is_bool($data[$key])) {
            $this->add_error($result, $field, 'Field must be a boolean.');
        }
    }

    /**
     * Require an enum field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param array<int, string> $allowed Allowed values.
     * @param array<string, mixed> $result Result.
     * @param string|null $field Field path.
     */
    private function require_enum(
        array $data,
        string $key,
        array $allowed,
        array &$result,
        ?string $field = null
    ): void {
        $field = $field ?? $key;

        if (!array_key_exists($key, $data) || !is_string($data[$key]) || trim($data[$key]) === '') {
            $this->add_error($result, $field, 'Field must be a non-empty string.');
            return;
        }

        if (!in_array($data[$key], $allowed, true)) {
            $this->add_error($result, $field, 'Invalid value: ' . $data[$key]);
        }
    }

    /**
     * Warn on public accreditation-risk claims.
     *
     * @param mixed $value Text.
     * @param string $field Field.
     * @param array<string, mixed> $result Result.
     */
    private function warn_public_claims($value, string $field, array &$result): void {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $lower = strtolower($value);
        $riskterms = [
            'université accréditée',
            'diplôme public',
            'grade universitaire',
            'titre professionnel reconnu',
        ];

        foreach ($riskterms as $term) {
            if (strpos($lower, $term) !== false) {
                $this->add_warning(
                    $result,
                    $field,
                    'Public claim may create accreditation confusion: ' . $term
                );
            }
        }
    }

    /**
     * Return whether an override is enabled.
     *
     * @param array<string, mixed> $profile Profile.
     * @param string $key Override key.
     * @return bool
     */
    private function has_enabled_override(array $profile, string $key): bool {
        return isset($profile['overrides'][$key])
            && is_array($profile['overrides'][$key])
            && ($profile['overrides'][$key]['enabled'] ?? false) === true;
    }

    /**
     * Return a value from a nested dot path.
     *
     * @param array<string, mixed> $data Data.
     * @param string $path Dot path.
     * @return mixed
     */
    private function get_dot_value(array $data, string $path) {
        $value = $data;

        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Return true when an id can be used as an HTML anchor.
     *
     * @param string $id Anchor id.
     * @return bool
     */
    private function is_valid_anchor_id(string $id): bool {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) === 1;
    }

    /**
     * Return true when array is a list.
     *
     * @param array<mixed> $array Array.
     * @return bool
     */
    private function is_list_array(array $array): bool {
        if ($array === []) {
            return false;
        }

        return array_keys($array) === range(0, count($array) - 1);
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

    /**
     * Create a new validation result.
     *
     * @param string $source Source label.
     * @return array<string, mixed>
     */
    private function new_result(string $source): array {
        return [
            'component' => self::COMPONENT,
            'validator' => self::class,
            'source' => $source,
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'info' => [],
            'counts' => [
                'errors' => 0,
                'warnings' => 0,
            ],
            'children' => [],
            'summary' => '',
        ];
    }

    /**
     * Add an error.
     *
     * @param array<string, mixed> $result Result.
     * @param string $field Field.
     * @param string $message Message.
     */
    private function add_error(array &$result, string $field, string $message): void {
        $result['errors'][] = [
            'severity' => self::SEVERITY_ERROR,
            'field' => $field,
            'message' => $message,
        ];

        $result['counts']['errors'] = count($result['errors']);
    }

    /**
     * Add a warning.
     *
     * @param array<string, mixed> $result Result.
     * @param string $field Field.
     * @param string $message Message.
     */
    private function add_warning(array &$result, string $field, string $message): void {
        $result['warnings'][] = [
            'severity' => self::SEVERITY_WARNING,
            'field' => $field,
            'message' => $message,
        ];

        $result['counts']['warnings'] = count($result['warnings']);
    }

    /**
     * Merge two validation results.
     *
     * @param array<string, mixed> $base Base result.
     * @param array<string, mixed> $extra Extra result.
     * @return array<string, mixed>
     */
    private function merge_results(array $base, array $extra): array {
        foreach ($extra['errors'] as $error) {
            $base['errors'][] = $error;
        }

        foreach ($extra['warnings'] as $warning) {
            $base['warnings'][] = $warning;
        }

        if (!empty($extra['children'])) {
            foreach ($extra['children'] as $child) {
                $base['children'][] = $child;
            }
        }

        return $this->finish($base);
    }

    /**
     * Finalise a validation result.
     *
     * @param array<string, mixed> $result Result.
     * @return array<string, mixed>
     */
    private function finish(array $result): array {
        $result['counts']['errors'] = count($result['errors']);
        $result['counts']['warnings'] = count($result['warnings']);
        $result['valid'] = $result['counts']['errors'] === 0;
        $result['summary'] = $result['valid']
            ? 'Faculty profile validation passed.'
            : 'Faculty profile validation failed.';

        return $result;
    }
}

