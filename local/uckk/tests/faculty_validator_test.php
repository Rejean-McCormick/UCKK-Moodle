<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Faculty Profile validator.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use JsonException;
use local_uckk\local\faculty\faculty_validator;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\faculty\faculty_validator.
 *
 * The Faculty validator protects the public Faculty Profile JSON contract. It
 * validates editorial public profile data only; it must not accept Atlas course
 * duplication, rendered page payloads, private Moodle data, arbitrary slugs, or
 * file paths supplied as identifiers.
 *
 * @covers \local_uckk\local\faculty\faculty_validator
 */
final class faculty_validator_test extends advanced_testcase {
    /** Faculty profile schema version. */
    private const FACULTY_SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Atlas schema version expected in source_atlas. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Required top-level Faculty Profile fields. */
    private const REQUIRED_TOP_LEVEL_FIELDS = [
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

    /** Required source_atlas fields. */
    private const REQUIRED_SOURCE_ATLAS_FIELDS = [
        'file',
        'schema_version_expected',
        'sync_mode',
    ];

    /** Required Moodle mapping fields. */
    private const REQUIRED_MOODLE_FIELDS = [
        'category_id',
        'category_idnumber',
        'course_prefix',
        'public_course_listing',
        'enrolment_visibility',
        'hub_course_idnumber',
    ];

    /** Required identity fields. */
    private const REQUIRED_IDENTITY_FIELDS = [
        'eyebrow',
        'name',
        'short_name',
        'title_symbolique',
        'domain',
        'level',
        'faculty_role',
        'one_sentence',
    ];

    /** Required SEO fields. */
    private const REQUIRED_SEO_FIELDS = [
        'title',
        'description',
        'keywords',
    ];

    /** Required hero fields. */
    private const REQUIRED_HERO_FIELDS = [
        'title',
        'subtitle',
        'summary',
        'primary_cta',
        'secondary_cta',
    ];

    /** Required CTA fields. */
    private const REQUIRED_CTA_FIELDS = [
        'label',
        'target',
    ];

    /** Required atlas_projection fields. */
    private const REQUIRED_ATLAS_PROJECTION_FIELDS = [
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

    /** Required dynamic block fields. */
    private const REQUIRED_DYNAMIC_BLOCK_FIELDS = [
        'id',
        'type',
        'title',
        'source',
        'limit',
        'visibility',
        'empty_state',
    ];

    /** Required featured block fields. */
    private const REQUIRED_FEATURED_BLOCK_FIELDS = [
        'type',
        'title',
        'body',
    ];

    /** Required FAQ fields. */
    private const REQUIRED_FAQ_FIELDS = [
        'question',
        'answer',
    ];

    /** Required contact fields. */
    private const REQUIRED_CONTACT_FIELDS = [
        'label',
        'body',
        'email',
        'cta',
    ];

    /** Required governance fields. */
    private const REQUIRED_GOVERNANCE_FIELDS = [
        'owner',
        'editorial_status',
        'last_reviewed',
        'review_notes',
        'public_claims_guardrails',
    ];

    /** Required cache fields. */
    private const REQUIRED_CACHE_FIELDS = [
        'enabled',
        'ttl_seconds',
    ];

    /** Allowed profile status values. */
    private const ALLOWED_STATUS = [
        'draft',
        'published',
        'archived',
    ];

    /** Allowed profile visibility values. */
    private const ALLOWED_VISIBILITY = [
        'public',
        'hidden',
        'restricted',
    ];

    /** Allowed source_atlas sync modes. */
    private const ALLOWED_SYNC_MODES = [
        'read_only',
        'preview_only',
        'moodle_sync_allowed',
    ];

    /** Allowed enrolment visibility values. */
    private const ALLOWED_ENROLMENT_VISIBILITY = [
        'hidden',
        'public_info_only',
        'login_required',
        'enrolment_required',
    ];

    /** Allowed section types. */
    private const ALLOWED_SECTION_TYPES = [
        'text',
        'markdown',
        'quote',
        'principle',
        'notice',
        'cards',
        'callout',
        'two_column',
    ];

    /** Allowed dynamic block types. */
    private const ALLOWED_DYNAMIC_BLOCK_TYPES = [
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
    private const ALLOWED_SOURCE_PROVIDERS = [
        'moodle_forum',
        'moodle_calendar',
        'moodle_category',
        'moodle_course_customfield',
        'local_uckk_news',
        'local_uckk_manual',
        'none',
    ];

    /** Allowed featured block types. */
    private const ALLOWED_FEATURED_BLOCK_TYPES = [
        'principle',
        'notice',
        'warning',
        'quote',
        'stat',
        'method',
        'ethics',
        'cta',
    ];

    /** Allowed governance editorial status values. */
    private const ALLOWED_EDITORIAL_STATUS = [
        'draft',
        'review',
        'approved',
        'needs_update',
        'archived',
    ];

    /** Fields that belong to Atlas Voies, not Faculty Profiles. */
    private const FORBIDDEN_ATLAS_DUPLICATION_FIELDS = [
        'courses',
        'cours_conceptuels',
        'concept_maitre',
        'concepts_associes',
        'criteres_passage',
        'projet_final',
        'limites_ethiques',
        'relations_intervoies',
        'artefact_maitrise',
    ];

    /** Fields that belong to runtime/private Moodle data, not public profiles. */
    private const FORBIDDEN_PRIVATE_OR_RUNTIME_FIELDS = [
        'userid',
        'user_id',
        'email_private',
        'grade',
        'grades',
        'score',
        'completion',
        'completionstate',
        'progress',
        'lastaccess',
        'roles',
        'capabilities',
        'enrolments',
        'sesskey',
        'token',
        'password',
        'html',
        'rendered',
        'announcements',
        'events',
        'forum_posts',
        'calendar_events',
    ];

    /** Forbidden slug aliases and path-like values. */
    private const FORBIDDEN_SLUGS = [
        '',
        'grand_jeu_social',
        'voie-grand-jeu-social',
        'uckk-grand-jeu-social',
        'faculte-grand-jeu-social',
        'grandjeusocial',
        'GRAND-JEU-SOCIAL',
        ' grand-jeu-social ',
        'grand-jeu-social.faculty.json',
        'content/faculties/grand-jeu-social.faculty.json',
        '../grand-jeu-social',
        '..\\grand-jeu-social',
        '/grand-jeu-social',
        'grand-jeu-social/../../economie',
        'https://example.test/grand-jeu-social',
    ];

    /** Forbidden legacy or drifted Voie ids. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /**
     * The validator class and canonical validate() method must exist.
     */
    public function test_validator_exposes_canonical_validate_method(): void {
        $this->assertTrue(
            class_exists(faculty_validator::class),
            faculty_validator::class . ' must exist.'
        );

        $this->assertTrue(
            method_exists(faculty_validator::class, 'validate'),
            faculty_validator::class . ' must expose validate(array $profile).'
        );
    }

    /**
     * A complete canonical Faculty Profile fixture must validate.
     */
    public function test_validate_accepts_complete_canonical_faculty_profile(): void {
        $this->assert_validation_passes(self::valid_profile());
    }

    /**
     * Every shipped manifest-declared Faculty Profile must validate.
     */
    public function test_validate_accepts_all_manifest_declared_faculty_profiles(): void {
        foreach (self::manifest_items() as $item) {
            $this->assertArrayHasKey('faculty_file', $item);
            $this->assertIsString($item['faculty_file']);

            $profile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']);

            $this->assert_validation_passes($profile);
        }
    }

    /**
     * Invalid schema versions must fail closed.
     */
    public function test_validate_rejects_invalid_schema_version(): void {
        $profile = self::valid_profile();
        $profile['schema_version'] = 'UCKK-FACULTY-0.0';

        $this->assert_validation_fails($profile, 'Invalid schema_version must be rejected.');
    }

    /**
     * Missing required top-level fields must fail closed.
     */
    public function test_validate_rejects_missing_required_top_level_fields(): void {
        foreach (self::REQUIRED_TOP_LEVEL_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile[$field]);

            $this->assert_validation_fails($profile, 'Missing top-level field must be rejected: ' . $field);
        }
    }

    /**
     * Invalid status and visibility values must fail closed.
     */
    public function test_validate_rejects_invalid_status_and_visibility_values(): void {
        $profile = self::valid_profile();
        $profile['status'] = 'online';

        $this->assert_validation_fails($profile, 'Invalid status must be rejected.');

        $profile = self::valid_profile();
        $profile['visibility'] = 'world';

        $this->assert_validation_fails($profile, 'Invalid visibility must be rejected.');
    }

    /**
     * Slugs must be canonical public route tokens, not aliases or paths.
     */
    public function test_validate_rejects_forbidden_slug_aliases_and_paths(): void {
        foreach (self::FORBIDDEN_SLUGS as $slug) {
            $profile = self::valid_profile();
            $profile['slug'] = $slug;

            $this->assert_validation_fails($profile, 'Forbidden slug must be rejected: ' . $slug);
        }
    }

    /**
     * Forbidden legacy Voie ids must fail closed.
     */
    public function test_validate_rejects_forbidden_voie_id_aliases(): void {
        foreach (self::FORBIDDEN_VOIE_IDS as $voieid) {
            $profile = self::valid_profile();
            $profile['voie_id'] = $voieid;

            $this->assert_validation_fails($profile, 'Forbidden voie_id alias must be rejected: ' . $voieid);
        }
    }

    /**
     * source_atlas must contain the documented fields and values.
     */
    public function test_validate_rejects_invalid_source_atlas_block(): void {
        foreach (self::REQUIRED_SOURCE_ATLAS_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['source_atlas'][$field]);

            $this->assert_validation_fails($profile, 'Missing source_atlas field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['source_atlas']['file'] = '../voie_grand_jeu_social.json';
        $this->assert_validation_fails($profile, 'Path traversal in source_atlas.file must be rejected.');

        $profile = self::valid_profile();
        $profile['source_atlas']['file'] = 'grand-jeu-social.faculty.json';
        $this->assert_validation_fails($profile, 'Faculty file in source_atlas.file must be rejected.');

        $profile = self::valid_profile();
        $profile['source_atlas']['schema_version_expected'] = 'UCKK-ATLAS-0.1';
        $this->assert_validation_fails($profile, 'Invalid Atlas schema expectation must be rejected.');

        $profile = self::valid_profile();
        $profile['source_atlas']['sync_mode'] = 'apply_now';
        $this->assert_validation_fails($profile, 'Invalid source_atlas.sync_mode must be rejected.');
    }

    /**
     * Moodle mapping must contain the documented fields and values.
     */
    public function test_validate_rejects_invalid_moodle_mapping_block(): void {
        foreach (self::REQUIRED_MOODLE_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['moodle'][$field]);

            $this->assert_validation_fails($profile, 'Missing moodle field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['moodle']['category_idnumber'] = 'GJS';
        $this->assert_validation_fails($profile, 'Invalid category_idnumber must be rejected.');

        $profile = self::valid_profile();
        $profile['moodle']['course_prefix'] = 'BAD';
        $this->assert_validation_fails($profile, 'Invalid course_prefix must be rejected.');

        $profile = self::valid_profile();
        $profile['moodle']['public_course_listing'] = 'true';
        $this->assert_validation_fails($profile, 'Non-boolean public_course_listing must be rejected.');

        $profile = self::valid_profile();
        $profile['moodle']['enrolment_visibility'] = 'everyone';
        $this->assert_validation_fails($profile, 'Invalid enrolment_visibility must be rejected.');

        $profile = self::valid_profile();
        $profile['moodle']['hub_course_idnumber'] = 'BAD-HUB';
        $this->assert_validation_fails($profile, 'Hub course idnumber must match course prefix convention.');
    }

    /**
     * Identity, SEO and hero blocks must contain documented structures.
     */
    public function test_validate_rejects_invalid_identity_seo_or_hero_blocks(): void {
        foreach (self::REQUIRED_IDENTITY_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['identity'][$field]);

            $this->assert_validation_fails($profile, 'Missing identity field must be rejected: ' . $field);
        }

        foreach (self::REQUIRED_SEO_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['seo'][$field]);

            $this->assert_validation_fails($profile, 'Missing seo field must be rejected: ' . $field);
        }

        foreach (self::REQUIRED_HERO_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['hero'][$field]);

            $this->assert_validation_fails($profile, 'Missing hero field must be rejected: ' . $field);
        }

        foreach (['primary_cta', 'secondary_cta'] as $ctakey) {
            foreach (self::REQUIRED_CTA_FIELDS as $field) {
                $profile = self::valid_profile();
                unset($profile['hero'][$ctakey][$field]);

                $this->assert_validation_fails($profile, 'Missing hero CTA field must be rejected: ' . $ctakey . '.' . $field);
            }
        }

        $profile = self::valid_profile();
        $profile['seo']['keywords'] = 'UCKK,Faculté';
        $this->assert_validation_fails($profile, 'seo.keywords must be an array.');
    }

    /**
     * Public claims must not promise public accreditation or public degrees.
     */
    public function test_validate_rejects_public_accreditation_claims(): void {
        $profile = self::valid_profile();
        $profile['seo']['title'] = 'Diplôme public accrédité UCKK';

        $this->assert_validation_fails($profile, 'Public accreditation claim in seo.title must be rejected.');

        $profile = self::valid_profile();
        $profile['seo']['description'] = 'Université accréditée offrant un grade public.';

        $this->assert_validation_fails($profile, 'Public accreditation claim in seo.description must be rejected.');

        $profile = self::valid_profile();
        $profile['faq'][0]['answer'] = 'Cette faculté donne un diplôme public reconnu par l’État.';

        $this->assert_validation_fails($profile, 'Public accreditation claim in FAQ must be rejected.');
    }

    /**
     * CTA targets must be safe public targets.
     */
    public function test_validate_rejects_unsafe_cta_targets(): void {
        foreach (['javascript:alert(1)', 'data:text/html,test', 'file:///etc/passwd'] as $target) {
            $profile = self::valid_profile();
            $profile['hero']['primary_cta']['target'] = $target;

            $this->assert_validation_fails($profile, 'Unsafe primary CTA target must be rejected: ' . $target);
        }

        $profile = self::valid_profile();
        $profile['contact']['cta']['target'] = 'javascript:alert(1)';

        $this->assert_validation_fails($profile, 'Unsafe contact CTA target must be rejected.');
    }

    /**
     * Navigation targets must reference existing sections or dynamic blocks.
     */
    public function test_validate_rejects_orphan_navigation_targets(): void {
        $profile = self::valid_profile();
        $profile['navigation'][] = [
            'label' => 'Orpheline',
            'target' => '#missing-target',
        ];

        $this->assert_validation_fails($profile, 'Orphan navigation target must be rejected.');
    }

    /**
     * Sections must have unique ids and documented types.
     */
    public function test_validate_rejects_invalid_sections(): void {
        $profile = self::valid_profile();
        $profile['sections'][1]['id'] = $profile['sections'][0]['id'];

        $this->assert_validation_fails($profile, 'Duplicate section ids must be rejected.');

        $profile = self::valid_profile();
        $profile['sections'][0]['type'] = 'invented_section_type';

        $this->assert_validation_fails($profile, 'Unknown section type must be rejected.');

        $profile = self::valid_profile();
        unset($profile['sections'][0]['body']);

        $this->assert_validation_fails($profile, 'Missing section body must be rejected.');
    }

    /**
     * atlas_projection must be complete and boolean.
     */
    public function test_validate_rejects_incomplete_or_non_boolean_atlas_projection(): void {
        foreach (self::REQUIRED_ATLAS_PROJECTION_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['atlas_projection'][$field]);

            $this->assert_validation_fails($profile, 'Missing atlas_projection field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['atlas_projection']['show_courses'] = 'yes';

        $this->assert_validation_fails($profile, 'Non-boolean atlas_projection value must be rejected.');
    }

    /**
     * Dynamic blocks must use documented types, providers, visibility and limits.
     */
    public function test_validate_rejects_invalid_dynamic_blocks(): void {
        foreach (self::REQUIRED_DYNAMIC_BLOCK_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['dynamic_blocks'][0][$field]);

            $this->assert_validation_fails($profile, 'Missing dynamic block field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['dynamic_blocks'][0]['type'] = 'invented_block_type';
        $this->assert_validation_fails($profile, 'Unknown dynamic block type must be rejected.');

        $profile = self::valid_profile();
        $profile['dynamic_blocks'][0]['source']['provider'] = 'invented_provider';
        $this->assert_validation_fails($profile, 'Unknown dynamic block provider must be rejected.');

        $profile = self::valid_profile();
        $profile['dynamic_blocks'][0]['visibility'] = 'world';
        $this->assert_validation_fails($profile, 'Invalid dynamic block visibility must be rejected.');

        $profile = self::valid_profile();
        $profile['dynamic_blocks'][0]['limit'] = -1;
        $this->assert_validation_fails($profile, 'Negative dynamic block limit must be rejected.');
    }

    /**
     * Featured blocks must use documented types and required fields.
     */
    public function test_validate_rejects_invalid_featured_blocks(): void {
        foreach (self::REQUIRED_FEATURED_BLOCK_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['featured_blocks'][0][$field]);

            $this->assert_validation_fails($profile, 'Missing featured block field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['featured_blocks'][0]['type'] = 'invented_feature_type';

        $this->assert_validation_fails($profile, 'Unknown featured block type must be rejected.');
    }

    /**
     * FAQ entries must remain public, textual and complete.
     */
    public function test_validate_rejects_invalid_faq_entries(): void {
        foreach (self::REQUIRED_FAQ_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['faq'][0][$field]);

            $this->assert_validation_fails($profile, 'Missing FAQ field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['faq'][0]['question'] = [];

        $this->assert_validation_fails($profile, 'Non-string FAQ question must be rejected.');
    }

    /**
     * Contact, governance and cache blocks must use documented structures.
     */
    public function test_validate_rejects_invalid_contact_governance_or_cache_blocks(): void {
        foreach (self::REQUIRED_CONTACT_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['contact'][$field]);

            $this->assert_validation_fails($profile, 'Missing contact field must be rejected: ' . $field);
        }

        foreach (self::REQUIRED_GOVERNANCE_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['governance'][$field]);

            $this->assert_validation_fails($profile, 'Missing governance field must be rejected: ' . $field);
        }

        foreach (self::REQUIRED_CACHE_FIELDS as $field) {
            $profile = self::valid_profile();
            unset($profile['cache'][$field]);

            $this->assert_validation_fails($profile, 'Missing cache field must be rejected: ' . $field);
        }

        $profile = self::valid_profile();
        $profile['governance']['owner'] = 'theme_uckk';
        $this->assert_validation_fails($profile, 'Invalid governance owner must be rejected.');

        $profile = self::valid_profile();
        $profile['governance']['editorial_status'] = 'published';
        $this->assert_validation_fails($profile, 'Invalid governance editorial_status must be rejected.');

        $profile = self::valid_profile();
        $profile['governance']['public_claims_guardrails'] = 'guardrails';
        $this->assert_validation_fails($profile, 'governance.public_claims_guardrails must be an array.');

        $profile = self::valid_profile();
        $profile['cache']['enabled'] = 'true';
        $this->assert_validation_fails($profile, 'cache.enabled must be boolean.');

        $profile = self::valid_profile();
        $profile['cache']['ttl_seconds'] = 0;
        $this->assert_validation_fails($profile, 'cache.ttl_seconds must be positive.');
    }

    /**
     * Faculty Profile must not duplicate Atlas course internals.
     */
    public function test_validate_rejects_atlas_course_duplication_fields(): void {
        foreach (self::FORBIDDEN_ATLAS_DUPLICATION_FIELDS as $field) {
            $profile = self::valid_profile();
            $profile[$field] = [];

            $this->assert_validation_fails($profile, 'Atlas duplication field must be rejected in Faculty Profile: ' . $field);
        }
    }

    /**
     * Faculty Profile must not contain private or runtime Moodle payloads.
     */
    public function test_validate_rejects_private_or_runtime_moodle_payloads(): void {
        foreach (self::FORBIDDEN_PRIVATE_OR_RUNTIME_FIELDS as $field) {
            $profile = self::valid_profile();
            $profile[$field] = 'private';

            $this->assert_validation_fails($profile, 'Private/runtime field must be rejected: ' . $field);
        }
    }

    /**
     * Cross-file validation must match Faculty manifest and Atlas identity.
     */
    public function test_validate_rejects_cross_file_identity_mismatches(): void {
        $profile = self::valid_profile();
        $profile['faculty_id'] = 'faculty_economie';

        $this->assert_validation_fails($profile, 'faculty_id/slug mismatch must be rejected.');

        $profile = self::valid_profile();
        $profile['source_atlas']['file'] = 'voie_economie.json';

        $this->assert_validation_fails($profile, 'source_atlas.file/voie_id mismatch must be rejected.');

        $profile = self::valid_profile();
        $profile['moodle']['course_prefix'] = 'EC';

        $this->assert_validation_fails($profile, 'course_prefix/Atlas code mismatch must be rejected.');

        $profile = self::valid_profile();
        $profile['identity']['title_symbolique'] = 'Titre symbolique inventé';

        $this->assert_validation_fails($profile, 'identity.title_symbolique mismatch must be rejected.');
    }

    /**
     * Assert that a Faculty Profile validates.
     *
     * Supports these implementation styles:
     * - validate() returns true/false;
     * - validate() returns an array with valid/errors/messages fields;
     * - validate() returns a validation result object;
     * - validate() returns void/null on success and throws on failure.
     *
     * @param array<string, mixed> $profile Faculty Profile payload.
     */
    private function assert_validation_passes(array $profile): void {
        try {
            $result = self::validator()->validate($profile);
        } catch (Throwable $exception) {
            $this->fail('Validation was expected to pass, but failed: ' . $exception->getMessage());
        }

        $this->assertTrue(
            self::validation_result_is_valid($result),
            'Validation was expected to pass.'
        );
    }

    /**
     * Assert that a Faculty Profile fails validation.
     *
     * @param array<string, mixed> $profile Faculty Profile payload.
     * @param string $message Assertion message.
     */
    private function assert_validation_fails(array $profile, string $message): void {
        try {
            $result = self::validator()->validate($profile);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
            return;
        }

        $this->assertFalse(
            self::validation_result_is_valid($result),
            $message
        );
    }

    /**
     * Instantiate the validator under test.
     *
     * @return faculty_validator
     */
    private static function validator(): faculty_validator {
        return new faculty_validator();
    }

    /**
     * Convert common validation result shapes to a boolean.
     *
     * @param mixed $result Validation result.
     * @return bool
     */
    private static function validation_result_is_valid(mixed $result): bool {
        if ($result === null) {
            return true;
        }

        if (is_bool($result)) {
            return $result;
        }

        if (is_array($result)) {
            if (array_key_exists('valid', $result)) {
                return (bool) $result['valid'];
            }

            if (array_key_exists('success', $result)) {
                return (bool) $result['success'];
            }

            if (array_key_exists('errors', $result) && is_array($result['errors'])) {
                return count($result['errors']) === 0;
            }

            if (array_key_exists('messages', $result) && is_array($result['messages'])) {
                return !self::messages_contain_errors($result['messages']);
            }

            return true;
        }

        if (is_object($result)) {
            foreach (['is_valid', 'isValid', 'valid'] as $method) {
                if (method_exists($result, $method)) {
                    return (bool) $result->{$method}();
                }
            }

            foreach (['has_errors', 'hasErrors'] as $method) {
                if (method_exists($result, $method)) {
                    return !(bool) $result->{$method}();
                }
            }

            foreach (['get_errors', 'getErrors'] as $method) {
                if (method_exists($result, $method)) {
                    $errors = $result->{$method}();
                    return is_array($errors) && count($errors) === 0;
                }
            }

            foreach (['get_messages', 'getMessages'] as $method) {
                if (method_exists($result, $method)) {
                    $messages = $result->{$method}();
                    return is_array($messages) && !self::messages_contain_errors($messages);
                }
            }

            if (property_exists($result, 'valid')) {
                return (bool) $result->valid;
            }

            if (property_exists($result, 'errors') && is_array($result->errors)) {
                return count($result->errors) === 0;
            }
        }

        return true;
    }

    /**
     * Detect error/blocker messages in common validation message structures.
     *
     * @param array<int|string, mixed> $messages Messages.
     * @return bool
     */
    private static function messages_contain_errors(array $messages): bool {
        foreach ($messages as $message) {
            if (is_array($message)) {
                $severity = (string) ($message['severity'] ?? $message['level'] ?? '');
                if (in_array($severity, ['error', 'blocker', 'failed'], true)) {
                    return true;
                }
            } else if (is_object($message)) {
                $severity = '';

                if (property_exists($message, 'severity')) {
                    $severity = (string) $message->severity;
                } else if (method_exists($message, 'get_severity')) {
                    $severity = (string) $message->get_severity();
                }

                if (in_array($severity, ['error', 'blocker', 'failed'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Build a complete canonical Faculty Profile fixture.
     *
     * @return array<string, mixed>
     */
    private static function valid_profile(): array {
        return [
            'schema_version' => self::FACULTY_SCHEMA_VERSION,
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'status' => 'published',
            'visibility' => 'public',
            'source_atlas' => [
                'file' => 'voie_grand_jeu_social.json',
                'schema_version_expected' => self::ATLAS_SCHEMA_VERSION,
                'sync_mode' => 'read_only',
            ],
            'moodle' => [
                'category_id' => null,
                'category_idnumber' => 'UCKK-GJS',
                'course_prefix' => 'GJS',
                'public_course_listing' => true,
                'enrolment_visibility' => 'public_info_only',
                'hub_course_idnumber' => 'GJS-HUB',
            ],
            'identity' => [
                'eyebrow' => 'Voie UCKK',
                'name' => 'Voie du Grand Jeu social',
                'short_name' => 'Grand Jeu social',
                'title_symbolique' => 'Maître du Grand Jeu social',
                'domain' => 'Systèmes sociaux',
                'level' => 'Puissance opératoire',
                'faculty_role' => 'Faculté interne UCKK',
                'one_sentence' => 'Comprendre les jeux sociaux comme systèmes d’action.',
            ],
            'seo' => [
                'title' => 'Faculté du Grand Jeu social — UCKK',
                'description' => 'Page publique de la Voie UCKK du Grand Jeu social.',
                'keywords' => [
                    'UCKK',
                    'Voie',
                    'Grand Jeu social',
                ],
            ],
            'hero' => [
                'title' => 'Faculté du Grand Jeu social',
                'subtitle' => 'Lire les systèmes sociaux sans les confondre avec les personnes.',
                'summary' => 'Présentation publique de la Voie du Grand Jeu social.',
                'primary_cta' => [
                    'label' => 'Voir le programme',
                    'target' => '#programme',
                ],
                'secondary_cta' => [
                    'label' => 'Lire la notice',
                    'target' => '#notice',
                ],
            ],
            'navigation' => [
                [
                    'label' => 'Présentation',
                    'target' => '#presentation',
                ],
                [
                    'label' => 'Programme',
                    'target' => '#programme',
                ],
                [
                    'label' => 'Notice',
                    'target' => '#notice',
                ],
                [
                    'label' => 'Ressources',
                    'target' => '#resources',
                ],
            ],
            'sections' => [
                [
                    'id' => 'presentation',
                    'type' => 'text',
                    'title' => 'Présentation',
                    'body' => 'Cette faculté présente la Voie du Grand Jeu social.',
                ],
                [
                    'id' => 'programme',
                    'type' => 'callout',
                    'title' => 'Programme',
                    'body' => 'Le programme public est projeté depuis l’Atlas.',
                ],
                [
                    'id' => 'notice',
                    'type' => 'notice',
                    'title' => 'Notice institutionnelle',
                    'body' => 'Reconnaissance interne UCKK; ne constitue pas un diplôme public accrédité.',
                ],
            ],
            'atlas_projection' => [
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
            ],
            'dynamic_blocks' => [
                [
                    'id' => 'resources',
                    'type' => 'public_resources',
                    'title' => 'Ressources publiques',
                    'source' => [
                        'provider' => 'local_uckk_manual',
                        'items' => [
                            [
                                'title' => 'Ressource publique',
                                'summary' => 'Ressource publique de présentation.',
                                'url' => 'https://example.test/resource',
                            ],
                        ],
                    ],
                    'limit' => 3,
                    'visibility' => 'public',
                    'empty_state' => 'Aucune ressource publique pour le moment.',
                ],
            ],
            'featured_blocks' => [
                [
                    'type' => 'principle',
                    'title' => 'Principe',
                    'body' => 'Ne pas confondre système social et personne.',
                ],
            ],
            'faq' => [
                [
                    'question' => 'Cette page présente-t-elle un diplôme public?',
                    'answer' => 'Non. Elle présente une reconnaissance interne UCKK.',
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'body' => 'Pour les questions publiques sur cette faculté.',
                'email' => '',
                'cta' => [
                    'label' => 'Nous contacter',
                    'target' => '/local/uckk/contact.php',
                ],
            ],
            'governance' => [
                'owner' => 'local_uckk',
                'editorial_status' => 'approved',
                'last_reviewed' => null,
                'review_notes' => '',
                'public_claims_guardrails' => [
                    'Ne pas promettre de diplôme public accrédité.',
                    'Ne pas exposer de progression individuelle.',
                ],
            ],
            'cache' => [
                'enabled' => true,
                'ttl_seconds' => 3600,
            ],
        ];
    }

    /**
     * Load manifest items.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function manifest_items(): array {
        $manifest = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . 'faculty_manifest.json');

        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $items = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            $items[] = $item;
        }

        self::assertCount(10, $items);

        return $items;
    }

    /**
     * Return the Faculty content directory.
     *
     * @return string
     */
    private static function faculty_dir(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'uckk'
            . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'faculties';
    }

    /**
     * Read and decode a JSON file.
     *
     * @param string $path Absolute path.
     * @return array<string, mixed>
     */
    private static function read_json_file(string $path): array {
        self::assertFileExists($path);
        self::assertFileIsReadable($path);

        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail(sprintf(
                'Invalid JSON in %s: %s',
                $path,
                $exception->getMessage()
            ));
        }

        self::assertIsArray($decoded);

        return $decoded;
    }
}
