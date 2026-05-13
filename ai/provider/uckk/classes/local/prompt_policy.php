<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Prompt governance policy for the UCKK AI provider.
 *
 * This class centralizes the UCKK rule that AI is assistive, non-sovereign,
 * permission-checked, context-aware, privacy-aware, and never the final
 * authority for evidence, grades, integrity, badges, archives, or assemblies.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class prompt_policy {
    /** AI action: summarize course material. */
    public const ACTION_SUMMARISE_COURSE_MATERIAL = 'summarise_course_material';

    /** AI action: map a problem. */
    public const ACTION_MAP_PROBLEM = 'map_problem';

    /** AI action: extract uncertainties. */
    public const ACTION_EXTRACT_UNCERTAINTIES = 'extract_uncertainties';

    /** AI action: draft reflection. */
    public const ACTION_DRAFT_REFLECTION = 'draft_reflection';

    /** AI action: summarize assembly. */
    public const ACTION_SUMMARISE_ASSEMBLY = 'summarise_assembly';

    /** AI action: critique AI output. */
    public const ACTION_CRITIQUE_AI_OUTPUT = 'critique_ai_output';

    /** AI action: prepare integrity review. */
    public const ACTION_PREPARE_INTEGRITY_REVIEW = 'prepare_integrity_review';

    /** English non-authority label. */
    public const NON_AUTHORITY_LABEL_EN =
        'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';

    /** French non-authority label. */
    public const NON_AUTHORITY_LABEL_FR =
        'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';

    /**
     * Allowed UCKK AI actions.
     */
    private const ALLOWED_ACTIONS = [
        self::ACTION_SUMMARISE_COURSE_MATERIAL,
        self::ACTION_MAP_PROBLEM,
        self::ACTION_EXTRACT_UNCERTAINTIES,
        self::ACTION_DRAFT_REFLECTION,
        self::ACTION_SUMMARISE_ASSEMBLY,
        self::ACTION_CRITIQUE_AI_OUTPUT,
        self::ACTION_PREPARE_INTEGRITY_REVIEW,
    ];

    /**
     * Purposes that AI must never perform.
     */
    private const FORBIDDEN_PURPOSES = [
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

    /**
     * Components treated as restricted or high-risk by default.
     */
    private const RESTRICTED_COMPONENTS = [
        'tool_uckkintegrity',
        'mod_uckkarchive',
    ];

    /**
     * Return all permitted action keys.
     *
     * @return string[]
     */
    public static function actions(): array {
        return self::ALLOWED_ACTIONS;
    }

    /**
     * Return all forbidden purpose keys.
     *
     * @return string[]
     */
    public static function forbidden_purposes(): array {
        return self::FORBIDDEN_PURPOSES;
    }

    /**
     * Check whether the provider is enabled.
     *
     * @return bool
     */
    public static function provider_enabled(): bool {
        return (bool)get_config('aiprovider_uckk', 'enable_provider');
    }

    /**
     * Normalize an action key.
     *
     * @param string $action Raw action.
     * @return string
     */
    public static function normalize_action(string $action): string {
        return clean_param(core_text::strtolower(trim($action)), PARAM_ALPHANUMEXT);
    }

    /**
     * Check whether an action is supported.
     *
     * @param string $action Action key.
     * @return bool
     */
    public static function is_supported_action(string $action): bool {
        return in_array(self::normalize_action($action), self::ALLOWED_ACTIONS, true);
    }

    /**
     * Require a supported action.
     *
     * @param string $action Action key.
     * @return string Normalized action.
     */
    public static function require_supported_action(string $action): string {
        $action = self::normalize_action($action);

        if (!self::is_supported_action($action)) {
            throw new \moodle_exception('unsupportedaction', 'aiprovider_uckk', '', $action);
        }

        return $action;
    }

    /**
     * Check whether a purpose is forbidden.
     *
     * @param string $purpose Purpose key.
     * @return bool
     */
    public static function is_forbidden_purpose(string $purpose): bool {
        $purpose = clean_param(core_text::strtolower(trim($purpose)), PARAM_ALPHANUMEXT);

        return in_array($purpose, self::FORBIDDEN_PURPOSES, true);
    }

    /**
     * Require that the request purpose is not forbidden.
     *
     * @param string $purpose Purpose key.
     * @return void
     */
    public static function require_allowed_purpose(string $purpose): void {
        if (self::is_forbidden_purpose($purpose)) {
            throw new \moodle_exception('forbiddenpurpose', 'aiprovider_uckk', '', $purpose);
        }
    }

    /**
     * Check whether the current user can use the AI provider in a context.
     *
     * @param \context $context Moodle context.
     * @return bool
     */
    public static function user_can_use(\context $context): bool {
        return self::provider_enabled() && has_capability('aiprovider/uckk:use', $context);
    }

    /**
     * Require provider use capability in context.
     *
     * @param \context $context Moodle context.
     * @return void
     */
    public static function require_can_use(\context $context): void {
        if (!self::provider_enabled()) {
            throw new \moodle_exception('providernotenabled', 'aiprovider_uckk');
        }

        require_capability('aiprovider/uckk:use', $context);
    }

    /**
     * Check whether the request targets an integrity context.
     *
     * @param string|null $component Origin component.
     * @param string|null $purpose Request purpose.
     * @return bool
     */
    public static function is_integrity_context(?string $component = null, ?string $purpose = null): bool {
        $component = clean_param((string)$component, PARAM_COMPONENT);
        $purpose = clean_param(core_text::strtolower((string)$purpose), PARAM_ALPHANUMEXT);

        return $component === 'tool_uckkintegrity'
            || $purpose === 'prepare_integrity_review'
            || $purpose === self::ACTION_PREPARE_INTEGRITY_REVIEW;
    }

    /**
     * Check whether the request targets a public challenge context.
     *
     * @param string|null $component Origin component.
     * @param string|null $visibility Visibility value.
     * @return bool
     */
    public static function is_public_challenge_context(?string $component = null, ?string $visibility = null): bool {
        $component = clean_param((string)$component, PARAM_COMPONENT);
        $visibility = clean_param(core_text::strtolower((string)$visibility), PARAM_ALPHANUMEXT);

        return $component === 'mod_uckkchallenge'
            && in_array($visibility, ['public', 'institutional', 'king_klown_public'], true);
    }

    /**
     * Check whether a component is restricted by default.
     *
     * @param string|null $component Origin component.
     * @return bool
     */
    public static function is_restricted_component(?string $component): bool {
        $component = clean_param((string)$component, PARAM_COMPONENT);

        return in_array($component, self::RESTRICTED_COMPONENTS, true);
    }

    /**
     * Check whether AI is allowed for the supplied context metadata.
     *
     * @param \context $context Moodle context.
     * @param string $action Action key.
     * @param string|null $component Origin component.
     * @param string|null $purpose Request purpose.
     * @param string|null $visibility Visibility value.
     * @return bool
     */
    public static function can_run(
        \context $context,
        string $action,
        ?string $component = null,
        ?string $purpose = null,
        ?string $visibility = null
    ): bool {
        if (!self::user_can_use($context) || !self::is_supported_action($action)) {
            return false;
        }

        if ($purpose !== null && self::is_forbidden_purpose($purpose)) {
            return false;
        }

        if (self::is_integrity_context($component, $purpose)
                && !get_config('aiprovider_uckk', 'allow_in_integrity_contexts')) {
            return false;
        }

        if (self::is_public_challenge_context($component, $visibility)
                && !get_config('aiprovider_uckk', 'allow_in_public_challenges')) {
            return false;
        }

        if (self::is_restricted_component($component)
                && !get_config('aiprovider_uckk', 'allow_in_integrity_contexts')) {
            return false;
        }

        return true;
    }

    /**
     * Require that AI can run for the supplied context metadata.
     *
     * @param \context $context Moodle context.
     * @param string $action Action key.
     * @param string|null $component Origin component.
     * @param string|null $purpose Request purpose.
     * @param string|null $visibility Visibility value.
     * @return string Normalized action.
     */
    public static function require_can_run(
        \context $context,
        string $action,
        ?string $component = null,
        ?string $purpose = null,
        ?string $visibility = null
    ): string {
        $action = self::require_supported_action($action);
        self::require_can_use($context);

        if ($purpose !== null) {
            self::require_allowed_purpose($purpose);
        }

        if (!self::can_run($context, $action, $component, $purpose, $visibility)) {
            throw new \moodle_exception('airunnotallowed', 'aiprovider_uckk');
        }

        return $action;
    }

    /**
     * Whether prompts should be redacted before being sent to the provider.
     *
     * @return bool
     */
    public static function should_redact_before_send(): bool {
        return (bool)get_config('aiprovider_uckk', 'redact_user_data_before_send');
    }

    /**
     * Apply conservative redaction to a prompt.
     *
     * This is not a substitute for capability checks. It is a privacy guardrail
     * used before sending content to an external AI endpoint.
     *
     * @param string $prompt Prompt text.
     * @return string Redacted prompt.
     */
    public static function redact_prompt(string $prompt): string {
        if (!self::should_redact_before_send()) {
            return $prompt;
        }

        $patterns = [
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu' => '[redacted-email]',
            '/\b(?:\+?\d[\d\s().-]{7,}\d)\b/u' => '[redacted-phone]',
            '/\b\d{4}[- ]?\d{4}[- ]?\d{4}[- ]?\d{4}\b/u' => '[redacted-number]',
            '/\b(?:userid|user id|id utilisateur)\s*[:#=]?\s*\d+\b/iu' => '[redacted-user-id]',
            '/\b(?:student|learner|joueur|user|utilisateur)\s*[:#=]\s*[^\n\r]+/iu' => '[redacted-person]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $prompt) ?? $prompt;
    }

    /**
     * Build the system preamble enforced for every UCKK AI request.
     *
     * @param string $action Action key.
     * @param string|null $lang Language code.
     * @return string
     */
    public static function system_preamble(string $action, ?string $lang = null): string {
        $action = self::require_supported_action($action);
        $label = self::non_authority_label($lang);

        return implode("\n", [
            'You are operating inside UCKK-Moodle.',
            'You are assistive only.',
            'You must not grade final work, validate integrity, close cases, publish assembly decisions, award badges, certify competencies, erase evidence, or replace human review.',
            'You must distinguish facts, hypotheses, interpretations, narratives, and decisions.',
            'You must preserve the distinction between UCKK, kOA, the kOA Digital Ecosystem, King Klown, Inquisiteur, Assemblées, and Archives.',
            'You must identify uncertainty rather than hide it.',
            'Action: ' . $action,
            'Required output label: ' . $label,
        ]);
    }

    /**
     * Return the non-authority label in the selected language.
     *
     * @param string|null $lang Language code.
     * @return string
     */
    public static function non_authority_label(?string $lang = null): string {
        $lang = clean_param($lang ?: current_language(), PARAM_LANG);

        return str_starts_with($lang, 'fr')
            ? self::NON_AUTHORITY_LABEL_FR
            : self::NON_AUTHORITY_LABEL_EN;
    }

    /**
     * Append the non-authority label to provider output if missing.
     *
     * @param string $output Raw AI output.
     * @param string|null $lang Language code.
     * @return string
     */
    public static function label_output(string $output, ?string $lang = null): string {
        $label = self::non_authority_label($lang);

        if (str_contains($output, $label)) {
            return $output;
        }

        return trim($output) . "\n\n" . $label;
    }

    /**
     * Clamp max tokens according to site settings.
     *
     * @param int|null $requested Requested token count.
     * @return int
     */
    public static function max_tokens(?int $requested = null): int {
        $configured = (int)get_config('aiprovider_uckk', 'max_tokens');

        if ($configured <= 0) {
            $configured = 2048;
        }

        if ($requested === null || $requested <= 0) {
            return $configured;
        }

        return min($requested, $configured);
    }

    /**
     * Build a policy-safe prompt payload.
     *
     * @param \context $context Moodle context.
     * @param string $action Action key.
     * @param string $prompt User prompt.
     * @param string|null $component Origin component.
     * @param string|null $purpose Request purpose.
     * @param string|null $visibility Visibility value.
     * @param string|null $lang Language code.
     * @param int|null $maxtokens Requested max tokens.
     * @return array<string,mixed>
     */
    public static function build_payload(
        \context $context,
        string $action,
        string $prompt,
        ?string $component = null,
        ?string $purpose = null,
        ?string $visibility = null,
        ?string $lang = null,
        ?int $maxtokens = null
    ): array {
        $action = self::require_can_run($context, $action, $component, $purpose, $visibility);

        return [
            'action' => $action,
            'contextid' => $context->id,
            'component' => clean_param((string)$component, PARAM_COMPONENT),
            'purpose' => clean_param((string)$purpose, PARAM_ALPHANUMEXT),
            'visibility' => clean_param((string)$visibility, PARAM_ALPHANUMEXT),
            'system' => self::system_preamble($action, $lang),
            'prompt' => self::redact_prompt(clean_text($prompt, FORMAT_PLAIN)),
            'maxtokens' => self::max_tokens($maxtokens),
            'label' => self::non_authority_label($lang),
            'redacted' => self::should_redact_before_send(),
        ];
    }
}