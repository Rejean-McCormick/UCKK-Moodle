<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

use aiprovider_uckk\provider;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;

/**
 * Tests for UCKK AI provider processors.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \aiprovider_uckk\provider
 * @covers     \aiprovider_uckk\abstract_processor
 * @covers     \aiprovider_uckk\process_generate_text
 * @covers     \aiprovider_uckk\process_summarise_text
 */
final class aiprovider_uckk_processor_test extends advanced_testcase {
    /**
     * Provider advertises only the governed text actions used by UCKK.
     */
    public function test_provider_action_list_contains_expected_actions(): void {
        $actions = provider::get_action_list();

        $this->assertContains(generate_text::class, $actions);
        $this->assertContains(summarise_text::class, $actions);

        $this->assertNotContains('\core_ai\aiactions\generate_image', $actions);
        $this->assertNotContains('\core_ai\aiactions\generate_audio', $actions);
    }

    /**
     * Every advertised action must have a matching process_<action> class.
     */
    public function test_every_supported_action_has_processor_class(): void {
        $expected = [
            generate_text::class => \aiprovider_uckk\process_generate_text::class,
            summarise_text::class => \aiprovider_uckk\process_summarise_text::class,
        ];

        foreach (provider::get_action_list() as $actionclass) {
            $this->assertArrayHasKey($actionclass, $expected, 'Unsupported provider action declared: ' . $actionclass);
            $this->assertTrue(class_exists($expected[$actionclass]), 'Missing processor class: ' . $expected[$actionclass]);
        }
    }

    /**
     * Processor classes must inherit Moodle's AI process base class.
     */
    public function test_processors_extend_core_ai_process_base(): void {
        $processors = [
            \aiprovider_uckk\process_generate_text::class,
            \aiprovider_uckk\process_summarise_text::class,
        ];

        foreach ($processors as $processorclass) {
            $this->assertTrue(class_exists($processorclass), 'Missing processor class: ' . $processorclass);
            $this->assertTrue(
                is_subclass_of($processorclass, \core_ai\process_base::class),
                $processorclass . ' must extend ' . \core_ai\process_base::class
            );
        }
    }

    /**
     * The shared abstract processor should also inherit Moodle's process base.
     */
    public function test_abstract_processor_extends_core_ai_process_base(): void {
        $this->assertTrue(class_exists(\aiprovider_uckk\abstract_processor::class));
        $this->assertTrue(is_subclass_of(\aiprovider_uckk\abstract_processor::class, \core_ai\process_base::class));
    }

    /**
     * Processor classes must expose a process method.
     */
    public function test_processors_expose_process_method(): void {
        $processors = [
            \aiprovider_uckk\process_generate_text::class,
            \aiprovider_uckk\process_summarise_text::class,
        ];

        foreach ($processors as $processorclass) {
            $reflection = new ReflectionClass($processorclass);

            $this->assertTrue($reflection->hasMethod('process'), $processorclass . ' must implement process().');

            $method = $reflection->getMethod('process');
            $this->assertTrue($method->isPublic(), $processorclass . '::process() must be public.');
        }
    }

    /**
     * UCKK governed action names are accepted.
     */
    public function test_governed_actions_are_allowed(): void {
        $allowed = [
            'summarise_course_material',
            'map_problem',
            'extract_uncertainties',
            'draft_reflection',
            'summarise_assembly',
            'critique_ai_output',
            'prepare_integrity_review',
            'generate_text',
            'summarise_text',
        ];

        foreach ($allowed as $actionname) {
            $this->assertTrue(provider::is_governed_action_allowed($actionname), $actionname . ' should be allowed.');
            $this->assertFalse(provider::is_sovereign_action_forbidden($actionname), $actionname . ' should not be forbidden.');
        }
    }

    /**
     * Sovereign/final-authority action names are forbidden.
     */
    public function test_sovereign_actions_are_forbidden(): void {
        $forbidden = [
            'grade_final_work',
            'validate_integrity',
            'close_integrity_case',
            'publish_assembly_decision',
            'award_badge',
            'certify_competency',
            'erase_evidence',
            'validate_archive_item',
            'replace_human_review',
        ];

        foreach ($forbidden as $actionname) {
            $this->assertTrue(provider::is_sovereign_action_forbidden($actionname), $actionname . ' should be forbidden.');
            $this->assertFalse(provider::is_governed_action_allowed($actionname), $actionname . ' should not be allowed.');
        }
    }

    /**
     * AI output must carry the English non-authority label.
     */
    public function test_label_output_adds_english_non_authority_notice(): void {
        $labelled = provider::label_output('Draft response.', 'en');

        $this->assertStringStartsWith(provider::NON_AUTHORITY_LABEL_EN, $labelled);
        $this->assertStringContainsString('Draft response.', $labelled);
    }

    /**
     * AI output must carry the French non-authority label.
     */
    public function test_label_output_adds_french_non_authority_notice(): void {
        $labelled = provider::label_output('Réponse brouillon.', 'fr');

        $this->assertStringStartsWith(provider::NON_AUTHORITY_LABEL_FR, $labelled);
        $this->assertStringContainsString('Réponse brouillon.', $labelled);
    }

    /**
     * Empty AI output still returns the required non-authority label.
     */
    public function test_empty_output_returns_only_non_authority_label(): void {
        $this->assertSame(provider::NON_AUTHORITY_LABEL_EN, provider::label_output('', 'en'));
        $this->assertSame(provider::NON_AUTHORITY_LABEL_FR, provider::label_output('', 'fr'));
    }

    /**
     * The provider exposes the expected configuration constants used by processors.
     */
    public function test_provider_configuration_constants_are_stable(): void {
        $expected = [
            'enable_provider',
            'provider_endpoint',
            'provider_model',
            'api_key',
            'log_prompts',
            'log_responses',
            'allow_in_integrity_contexts',
            'allow_in_public_challenges',
            'redact_user_data_before_send',
            'max_tokens',
            'retention_days',
        ];

        $actual = [
            provider::CONFIG_ENABLE_PROVIDER,
            provider::CONFIG_PROVIDER_ENDPOINT,
            provider::CONFIG_PROVIDER_MODEL,
            provider::CONFIG_API_KEY,
            provider::CONFIG_LOG_PROMPTS,
            provider::CONFIG_LOG_RESPONSES,
            provider::CONFIG_ALLOW_IN_INTEGRITY_CONTEXTS,
            provider::CONFIG_ALLOW_IN_PUBLIC_CHALLENGES,
            provider::CONFIG_REDACT_USER_DATA_BEFORE_SEND,
            provider::CONFIG_MAX_TOKENS,
            provider::CONFIG_RETENTION_DAYS,
        ];

        $this->assertSame($expected, $actual);
    }
}