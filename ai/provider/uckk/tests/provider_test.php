<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk;

defined('MOODLE_INTERNAL') || die();

use aiprovider_uckk\local\prompt_policy;

/**
 * Tests for the governed UCKK AI provider policy.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \aiprovider_uckk\local\prompt_policy
 */
final class provider_test extends \advanced_testcase {
    /**
     * Reset the test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('enable_provider', 1, 'aiprovider_uckk');
        set_config('allow_in_integrity_contexts', 0, 'aiprovider_uckk');
        set_config('allow_in_public_challenges', 0, 'aiprovider_uckk');
        set_config('redact_user_data_before_send', 1, 'aiprovider_uckk');
        set_config('max_tokens', 2048, 'aiprovider_uckk');
        set_config('log_prompts', 1, 'aiprovider_uckk');
        set_config('log_responses', 0, 'aiprovider_uckk');
    }

    /**
     * The policy exposes the canonical allowed AI actions.
     *
     * @return void
     */
    public function test_allowed_actions_are_registered(): void {
        $actions = prompt_policy::actions();

        $this->assertContains(prompt_policy::ACTION_SUMMARISE_COURSE_MATERIAL, $actions);
        $this->assertContains(prompt_policy::ACTION_MAP_PROBLEM, $actions);
        $this->assertContains(prompt_policy::ACTION_EXTRACT_UNCERTAINTIES, $actions);
        $this->assertContains(prompt_policy::ACTION_DRAFT_REFLECTION, $actions);
        $this->assertContains(prompt_policy::ACTION_SUMMARISE_ASSEMBLY, $actions);
        $this->assertContains(prompt_policy::ACTION_CRITIQUE_AI_OUTPUT, $actions);
        $this->assertContains(prompt_policy::ACTION_PREPARE_INTEGRITY_REVIEW, $actions);
    }

    /**
     * Unknown AI actions are rejected.
     *
     * @return void
     */
    public function test_unsupported_action_is_rejected(): void {
        $this->assertFalse(prompt_policy::is_supported_action('award_badge'));

        $this->expectException(\moodle_exception::class);
        prompt_policy::require_supported_action('award_badge');
    }

    /**
     * AI must not perform sovereign institutional actions.
     *
     * @return void
     */
    public function test_forbidden_purposes_are_rejected(): void {
        $forbidden = [
            'grade',
            'final_grade',
            'grade_final_work',
            'validate_integrity',
            'final_integrity_judgement',
            'close_integrity_case',
            'publish_assembly_decision',
            'award_badge',
            'certify_competency',
            'validate_archive_item',
            'erase_evidence',
            'replace_human_review',
        ];

        foreach ($forbidden as $purpose) {
            $this->assertTrue(prompt_policy::is_forbidden_purpose($purpose), $purpose . ' should be forbidden.');
        }

        $this->expectException(\moodle_exception::class);
        prompt_policy::require_allowed_purpose('close_integrity_case');
    }

    /**
     * The provider can be globally disabled.
     *
     * @return void
     */
    public function test_provider_enabled_reflects_configuration(): void {
        $this->assertTrue(prompt_policy::provider_enabled());

        set_config('enable_provider', 0, 'aiprovider_uckk');

        $this->assertFalse(prompt_policy::provider_enabled());
    }

    /**
     * Integrity contexts are blocked unless explicitly enabled.
     *
     * @return void
     */
    public function test_integrity_context_is_blocked_by_default(): void {
        $context = \context_system::instance();

        $this->assertFalse(prompt_policy::can_run(
            $context,
            prompt_policy::ACTION_PREPARE_INTEGRITY_REVIEW,
            'tool_uckkintegrity',
            'prepare_integrity_review',
            'restricted'
        ));
    }

    /**
     * Integrity contexts can be enabled by administrator setting.
     *
     * @return void
     */
    public function test_integrity_context_can_be_enabled(): void {
        $context = \context_system::instance();

        set_config('allow_in_integrity_contexts', 1, 'aiprovider_uckk');

        $this->assertTrue(prompt_policy::can_run(
            $context,
            prompt_policy::ACTION_PREPARE_INTEGRITY_REVIEW,
            'tool_uckkintegrity',
            'prepare_integrity_review',
            'restricted'
        ));
    }

    /**
     * Public challenge contexts are blocked unless explicitly enabled.
     *
     * @return void
     */
    public function test_public_challenge_context_is_blocked_by_default(): void {
        $context = \context_system::instance();

        $this->assertFalse(prompt_policy::can_run(
            $context,
            prompt_policy::ACTION_MAP_PROBLEM,
            'mod_uckkchallenge',
            'draft_reflection',
            'king_klown_public'
        ));
    }

    /**
     * Public challenge contexts can be enabled by administrator setting.
     *
     * @return void
     */
    public function test_public_challenge_context_can_be_enabled(): void {
        $context = \context_system::instance();

        set_config('allow_in_public_challenges', 1, 'aiprovider_uckk');

        $this->assertTrue(prompt_policy::can_run(
            $context,
            prompt_policy::ACTION_MAP_PROBLEM,
            'mod_uckkchallenge',
            'draft_reflection',
            'king_klown_public'
        ));
    }

    /**
     * Redaction removes common personal-data patterns before external sending.
     *
     * @return void
     */
    public function test_redact_prompt_removes_personal_data_when_enabled(): void {
        $prompt = implode("\n", [
            'Student: Jane Doe',
            'Email: jane.doe@example.test',
            'Phone: +1 514 555 1234',
            'userid: 42',
            'Card: 1111 2222 3333 4444',
            'Please summarize this reflection.',
        ]);

        $redacted = prompt_policy::redact_prompt($prompt);

        $this->assertStringContainsString('[redacted-person]', $redacted);
        $this->assertStringContainsString('[redacted-email]', $redacted);
        $this->assertStringContainsString('[redacted-phone]', $redacted);
        $this->assertStringContainsString('[redacted-user-id]', $redacted);
        $this->assertStringContainsString('[redacted-number]', $redacted);

        $this->assertStringNotContainsString('jane.doe@example.test', $redacted);
        $this->assertStringNotContainsString('+1 514 555 1234', $redacted);
        $this->assertStringNotContainsString('1111 2222 3333 4444', $redacted);
    }

    /**
     * Redaction can be disabled by administrator setting.
     *
     * @return void
     */
    public function test_redact_prompt_returns_original_when_redaction_disabled(): void {
        set_config('redact_user_data_before_send', 0, 'aiprovider_uckk');

        $prompt = 'Email: jane.doe@example.test';

        $this->assertSame($prompt, prompt_policy::redact_prompt($prompt));
    }

    /**
     * The non-authority label is localized.
     *
     * @return void
     */
    public function test_non_authority_label_is_localized(): void {
        $this->assertSame(
            prompt_policy::NON_AUTHORITY_LABEL_EN,
            prompt_policy::non_authority_label('en')
        );

        $this->assertSame(
            prompt_policy::NON_AUTHORITY_LABEL_FR,
            prompt_policy::non_authority_label('fr')
        );
    }

    /**
     * Output is labeled as non-authoritative.
     *
     * @return void
     */
    public function test_label_output_appends_non_authority_label_once(): void {
        $output = 'This is a draft summary.';
        $labeled = prompt_policy::label_output($output, 'en');

        $this->assertStringContainsString($output, $labeled);
        $this->assertStringContainsString(prompt_policy::NON_AUTHORITY_LABEL_EN, $labeled);

        $again = prompt_policy::label_output($labeled, 'en');

        $this->assertSame($labeled, $again);
    }

    /**
     * The system preamble includes the non-sovereignty guardrails.
     *
     * @return void
     */
    public function test_system_preamble_contains_guardrails(): void {
        $preamble = prompt_policy::system_preamble(prompt_policy::ACTION_MAP_PROBLEM, 'en');

        $this->assertStringContainsString('assistive only', $preamble);
        $this->assertStringContainsString('must not grade final work', $preamble);
        $this->assertStringContainsString('validate integrity', $preamble);
        $this->assertStringContainsString('award badges', $preamble);
        $this->assertStringContainsString('Action: ' . prompt_policy::ACTION_MAP_PROBLEM, $preamble);
        $this->assertStringContainsString(prompt_policy::NON_AUTHORITY_LABEL_EN, $preamble);
    }

    /**
     * Requested max tokens are clamped to the configured maximum.
     *
     * @return void
     */
    public function test_max_tokens_are_clamped(): void {
        set_config('max_tokens', 1000, 'aiprovider_uckk');

        $this->assertSame(1000, prompt_policy::max_tokens(null));
        $this->assertSame(500, prompt_policy::max_tokens(500));
        $this->assertSame(1000, prompt_policy::max_tokens(5000));
    }

    /**
     * A policy-safe payload can be built for an allowed request.
     *
     * @return void
     */
    public function test_build_payload_for_allowed_request(): void {
        $context = \context_system::instance();

        $payload = prompt_policy::build_payload(
            $context,
            prompt_policy::ACTION_DRAFT_REFLECTION,
            'Student: Jane Doe' . "\n" . 'Draft a reflection about evidence.',
            'local_uckk',
            'draft_reflection',
            'private',
            'en',
            500
        );

        $this->assertSame(prompt_policy::ACTION_DRAFT_REFLECTION, $payload['action']);
        $this->assertSame($context->id, $payload['contextid']);
        $this->assertSame('local_uckk', $payload['component']);
        $this->assertSame('draft_reflection', $payload['purpose']);
        $this->assertSame('private', $payload['visibility']);
        $this->assertSame(500, $payload['maxtokens']);
        $this->assertSame(prompt_policy::NON_AUTHORITY_LABEL_EN, $payload['label']);
        $this->assertTrue($payload['redacted']);
        $this->assertStringContainsString('[redacted-person]', $payload['prompt']);
        $this->assertStringContainsString('assistive only', $payload['system']);
    }

    /**
     * Payload creation rejects forbidden purposes.
     *
     * @return void
     */
    public function test_build_payload_rejects_forbidden_purpose(): void {
        $context = \context_system::instance();

        $this->expectException(\moodle_exception::class);

        prompt_policy::build_payload(
            $context,
            prompt_policy::ACTION_DRAFT_REFLECTION,
            'Please award this badge.',
            'local_uckk',
            'award_badge',
            'private',
            'en',
            500
        );
    }

    /**
     * Payload creation rejects disabled provider.
     *
     * @return void
     */
    public function test_build_payload_rejects_disabled_provider(): void {
        $context = \context_system::instance();

        set_config('enable_provider', 0, 'aiprovider_uckk');

        $this->expectException(\moodle_exception::class);

        prompt_policy::build_payload(
            $context,
            prompt_policy::ACTION_MAP_PROBLEM,
            'Map this problem.',
            'local_uckk',
            'map_problem',
            'private',
            'en',
            500
        );
    }
}