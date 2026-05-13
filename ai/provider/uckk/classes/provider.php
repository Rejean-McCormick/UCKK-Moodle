<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk;

defined('MOODLE_INTERNAL') || die();

use core_ai\aiactions\generate_text;
use core_ai\aiactions\summarise_text;

/**
 * UCKK AI provider.
 *
 * This provider bridges Moodle's AI subsystem to the configured UCKK-governed
 * AI endpoint. It intentionally exposes only assistive text actions.
 *
 * UCKK rule:
 * AI output is never final authority. Facts, evidence, grades, integrity
 * decisions, archive validation, badge awards and assembly decisions must
 * remain human-governed Moodle records.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider extends \core_ai\provider {
    /** Provider config key: enabled flag. */
    public const CONFIG_ENABLE_PROVIDER = 'enable_provider';

    /** Provider config key: external endpoint. */
    public const CONFIG_PROVIDER_ENDPOINT = 'provider_endpoint';

    /** Provider config key: model name. */
    public const CONFIG_PROVIDER_MODEL = 'provider_model';

    /** Provider config key: API key or shared secret. */
    public const CONFIG_API_KEY = 'api_key';

    /** Provider config key: log prompts. */
    public const CONFIG_LOG_PROMPTS = 'log_prompts';

    /** Provider config key: log responses. */
    public const CONFIG_LOG_RESPONSES = 'log_responses';

    /** Provider config key: allow AI in integrity contexts. */
    public const CONFIG_ALLOW_IN_INTEGRITY_CONTEXTS = 'allow_in_integrity_contexts';

    /** Provider config key: allow AI in public challenges. */
    public const CONFIG_ALLOW_IN_PUBLIC_CHALLENGES = 'allow_in_public_challenges';

    /** Provider config key: redact user data before sending externally. */
    public const CONFIG_REDACT_USER_DATA_BEFORE_SEND = 'redact_user_data_before_send';

    /** Provider config key: maximum output/request tokens. */
    public const CONFIG_MAX_TOKENS = 'max_tokens';

    /** Provider config key: retention period for optional logs. */
    public const CONFIG_RETENTION_DAYS = 'retention_days';

    /** Default model when the provider instance does not override it. */
    public const DEFAULT_MODEL = 'uckk-governed-default';

    /** Default maximum token budget. */
    public const DEFAULT_MAX_TOKENS = 2048;

    /** Default retention days for optional provider-owned logs. */
    public const DEFAULT_RETENTION_DAYS = 30;

    /** English non-authority label required on AI responses. */
    public const NON_AUTHORITY_LABEL_EN =
        'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';

    /** French non-authority label required on AI responses. */
    public const NON_AUTHORITY_LABEL_FR =
        'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';

    /**
     * Return the Moodle AI actions supported by this provider.
     *
     * UCKK-specific use cases such as map_problem, extract_uncertainties,
     * draft_reflection, critique_ai_output and prepare_integrity_review are
     * implemented as governed prompt profiles over Moodle's text actions.
     *
     * @return array<int,class-string>
     */
    public static function get_action_list(): array {
        return [
            generate_text::class,
            summarise_text::class,
        ];
    }

    /**
     * Check whether this provider instance has enough configuration to run.
     *
     * Moodle provider instances store their configuration on $this->config.
     * The exact form fields are defined by the plugin hook listener, but the
     * keys are centralized here so processors and tests use the same names.
     *
     * @return bool
     */
    public function is_provider_configured(): bool {
        if (!$this->get_bool_config(self::CONFIG_ENABLE_PROVIDER, true)) {
            return false;
        }

        return $this->get_string_config(self::CONFIG_PROVIDER_ENDPOINT) !== ''
            && $this->get_string_config(self::CONFIG_PROVIDER_MODEL, self::DEFAULT_MODEL) !== '';
    }

    /**
     * Return the configured endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string {
        return $this->get_string_config(self::CONFIG_PROVIDER_ENDPOINT);
    }

    /**
     * Return the configured model.
     *
     * @return string
     */
    public function get_model(): string {
        return $this->get_string_config(self::CONFIG_PROVIDER_MODEL, self::DEFAULT_MODEL);
    }

    /**
     * Return the configured API key, if any.
     *
     * Some UCKK deployments may use an internal gateway with network-level
     * trust instead of a per-instance API key, so this is not required by
     * is_provider_configured().
     *
     * @return string
     */
    public function get_api_key(): string {
        return $this->get_string_config(self::CONFIG_API_KEY);
    }

    /**
     * Whether prompts should be logged by UCKK provider-owned logging.
     *
     * Moodle core AI action logging still applies independently.
     *
     * @return bool
     */
    public function should_log_prompts(): bool {
        return $this->get_bool_config(self::CONFIG_LOG_PROMPTS, false);
    }

    /**
     * Whether responses should be logged by UCKK provider-owned logging.
     *
     * @return bool
     */
    public function should_log_responses(): bool {
        return $this->get_bool_config(self::CONFIG_LOG_RESPONSES, false);
    }

    /**
     * Whether the provider may be used in integrity contexts.
     *
     * Even when true, AI may only prepare, summarize or critique. It must not
     * validate integrity, issue sanctions, close cases or replace human review.
     *
     * @return bool
     */
    public function allows_integrity_contexts(): bool {
        return $this->get_bool_config(self::CONFIG_ALLOW_IN_INTEGRITY_CONTEXTS, false);
    }

    /**
     * Whether the provider may be used in public challenge contexts.
     *
     * @return bool
     */
    public function allows_public_challenges(): bool {
        return $this->get_bool_config(self::CONFIG_ALLOW_IN_PUBLIC_CHALLENGES, false);
    }

    /**
     * Whether user-identifying data should be redacted before external send.
     *
     * @return bool
     */
    public function should_redact_user_data_before_send(): bool {
        return $this->get_bool_config(self::CONFIG_REDACT_USER_DATA_BEFORE_SEND, true);
    }

    /**
     * Maximum token budget.
     *
     * @return int
     */
    public function get_max_tokens(): int {
        $tokens = $this->get_int_config(self::CONFIG_MAX_TOKENS, self::DEFAULT_MAX_TOKENS);

        return max(1, $tokens);
    }

    /**
     * Retention period for optional UCKK provider logs.
     *
     * @return int
     */
    public function get_retention_days(): int {
        $days = $this->get_int_config(self::CONFIG_RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS);

        return max(0, $days);
    }

    /**
     * Return the AI non-authority warning label.
     *
     * @param string|null $lang Optional Moodle/current language code.
     * @return string
     */
    public static function get_non_authority_label(?string $lang = null): string {
        $lang = $lang ?? current_language();

        if (str_starts_with($lang, 'fr')) {
            return self::NON_AUTHORITY_LABEL_FR;
        }

        return self::NON_AUTHORITY_LABEL_EN;
    }

    /**
     * Prefix or append the non-authority label to text output.
     *
     * Processors should call this before returning generated text to Moodle.
     *
     * @param string $text AI generated text.
     * @param string|null $lang Optional language code.
     * @return string
     */
    public static function label_output(string $text, ?string $lang = null): string {
        $text = trim($text);

        if ($text === '') {
            return self::get_non_authority_label($lang);
        }

        return self::get_non_authority_label($lang) . "\n\n" . $text;
    }

    /**
     * Return true when an action name is permitted by UCKK doctrine.
     *
     * This method is for processors, placement bridges, tests and future
     * UCKK-specific action wrappers. It rejects names that imply final
     * authority or institutional decision-making.
     *
     * @param string $actionname Action/profile name.
     * @return bool
     */
    public static function is_governed_action_allowed(string $actionname): bool {
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

        return in_array($actionname, $allowed, true);
    }

    /**
     * Return true when an action name is explicitly forbidden.
     *
     * @param string $actionname Action/profile name.
     * @return bool
     */
    public static function is_sovereign_action_forbidden(string $actionname): bool {
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

        return in_array($actionname, $forbidden, true);
    }

    /**
     * Get a string value from provider instance configuration.
     *
     * @param string $key Config key.
     * @param string $default Default value.
     * @return string
     */
    private function get_string_config(string $key, string $default = ''): string {
        $value = $this->config[$key] ?? $default;

        if (is_array($value)) {
            $value = reset($value);
        }

        return trim((string)$value);
    }

    /**
     * Get a boolean value from provider instance configuration.
     *
     * @param string $key Config key.
     * @param bool $default Default value.
     * @return bool
     */
    private function get_bool_config(string $key, bool $default = false): bool {
        if (!array_key_exists($key, $this->config)) {
            return $default;
        }

        return (bool)$this->config[$key];
    }

    /**
     * Get an integer value from provider instance configuration.
     *
     * @param string $key Config key.
     * @param int $default Default value.
     * @return int
     */
    private function get_int_config(string $key, int $default = 0): int {
        if (!array_key_exists($key, $this->config)) {
            return $default;
        }

        return (int)$this->config[$key];
    }
}