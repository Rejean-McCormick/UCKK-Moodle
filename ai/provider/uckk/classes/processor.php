<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Processor for governed UCKK AI requests.
 *
 * AI in UCKK is assistive only. It can summarize, map, draft, compare and
 * prepare review material, but it cannot grade, validate integrity, close cases,
 * publish assembly decisions, award badges, certify competencies or erase evidence.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class processor {
    /** AI can summarize course material. */
    public const ACTION_SUMMARISE_COURSE_MATERIAL = 'summarise_course_material';

    /** AI can map a problem. */
    public const ACTION_MAP_PROBLEM = 'map_problem';

    /** AI can extract uncertainty markers. */
    public const ACTION_EXTRACT_UNCERTAINTIES = 'extract_uncertainties';

    /** AI can draft a learner reflection. */
    public const ACTION_DRAFT_REFLECTION = 'draft_reflection';

    /** AI can summarize assembly discussions. */
    public const ACTION_SUMMARISE_ASSEMBLY = 'summarise_assembly';

    /** AI can critique AI output. */
    public const ACTION_CRITIQUE_AI_OUTPUT = 'critique_ai_output';

    /** AI can prepare integrity review material, but never decide it. */
    public const ACTION_PREPARE_INTEGRITY_REVIEW = 'prepare_integrity_review';

    /** Default timeout for provider calls. */
    private const DEFAULT_TIMEOUT = 45;

    /**
     * Allowed actions.
     *
     * @return string[]
     */
    public static function allowed_actions(): array {
        return [
            self::ACTION_SUMMARISE_COURSE_MATERIAL,
            self::ACTION_MAP_PROBLEM,
            self::ACTION_EXTRACT_UNCERTAINTIES,
            self::ACTION_DRAFT_REFLECTION,
            self::ACTION_SUMMARISE_ASSEMBLY,
            self::ACTION_CRITIQUE_AI_OUTPUT,
            self::ACTION_PREPARE_INTEGRITY_REVIEW,
        ];
    }

    /**
     * Process an AI request.
     *
     * @param string $action One of the ACTION_* constants.
     * @param string $prompt User or system prompt.
     * @param \context $context Moodle context where the request originates.
     * @param array<string,mixed> $metadata Optional request metadata.
     * @return \stdClass Response object.
     */
    public static function process(string $action, string $prompt, \context $context, array $metadata = []): \stdClass {
        global $USER;

        self::require_available($action, $context);

        $prompt = self::normalise_prompt($prompt);

        if (self::should_redact_user_data()) {
            $prompt = self::redact_user_data($prompt);
            $metadata['redacted'] = true;
        } else {
            $metadata['redacted'] = false;
        }

        $payload = self::build_payload($action, $prompt, $context, $metadata);

        if (self::should_log_prompts()) {
            self::log_record('prompt', $action, $context, $prompt, $metadata);
        }

        $rawresponse = self::call_provider($payload);
        $content = self::extract_content($rawresponse);

        $response = (object)[
            'action' => $action,
            'contextid' => $context->id,
            'userid' => $USER->id,
            'content' => self::with_non_authority_label($content),
            'rawresponse' => $rawresponse,
            'metadata' => $metadata,
            'timecreated' => time(),
        ];

        if (self::should_log_responses()) {
            self::log_record('response', $action, $context, $response->content, [
                'metadata' => $metadata,
                'rawresponse' => $rawresponse,
            ]);
        }

        return $response;
    }

    /**
     * Validate availability, configuration, action, context and capability.
     *
     * @param string $action Action key.
     * @param \context $context Context.
     * @return void
     */
    private static function require_available(string $action, \context $context): void {
        if (!get_config('aiprovider_uckk', 'enable_provider')) {
            throw new \moodle_exception('aiproviderdisabled', 'aiprovider_uckk');
        }

        if (!in_array($action, self::allowed_actions(), true)) {
            throw new \moodle_exception('invalidaction', 'aiprovider_uckk');
        }

        require_capability('aiprovider/uckk:use', $context);

        if (self::is_integrity_context($context) && !get_config('aiprovider_uckk', 'allow_in_integrity_contexts')) {
            throw new \moodle_exception('integritycontextdisabled', 'aiprovider_uckk');
        }

        $endpoint = trim((string)get_config('aiprovider_uckk', 'provider_endpoint'));
        if ($endpoint === '') {
            throw new \moodle_exception('missingendpoint', 'aiprovider_uckk');
        }
    }

    /**
     * Normalize prompt content.
     *
     * @param string $prompt Prompt.
     * @return string
     */
    private static function normalise_prompt(string $prompt): string {
        $prompt = trim(clean_param($prompt, PARAM_RAW));

        if ($prompt === '') {
            throw new \moodle_exception('emptyprompt', 'aiprovider_uckk');
        }

        return $prompt;
    }

    /**
     * Build provider payload.
     *
     * @param string $action Action key.
     * @param string $prompt Prompt.
     * @param \context $context Context.
     * @param array<string,mixed> $metadata Metadata.
     * @return array<string,mixed>
     */
    private static function build_payload(string $action, string $prompt, \context $context, array $metadata): array {
        global $USER, $SITE;

        return [
            'model' => self::provider_model(),
            'action' => $action,
            'prompt' => self::system_instruction($action) . "\n\n" . $prompt,
            'max_tokens' => self::max_tokens(),
            'metadata' => [
                'component' => 'aiprovider_uckk',
                'siteid' => $SITE->id ?? 0,
                'userid' => $USER->id,
                'contextid' => $context->id,
                'contextlevel' => $context->contextlevel,
                'action' => $action,
                'uckk' => $metadata,
            ],
        ];
    }

    /**
     * Return the action-specific system instruction.
     *
     * @param string $action Action key.
     * @return string
     */
    private static function system_instruction(string $action): string {
        $guardrail = 'You are assisting UCKK-Moodle. You are not a final authority. '
            . 'Do not grade, certify, validate integrity, close cases, publish decisions, '
            . 'award badges, erase evidence, or present uncertain information as fact.';

        $instructions = [
            self::ACTION_SUMMARISE_COURSE_MATERIAL =>
                'Summarize course material clearly. Preserve uncertainty and cite missing evidence when relevant.',
            self::ACTION_MAP_PROBLEM =>
                'Map the problem into actors, forces, constraints, assumptions, risks and possible next questions.',
            self::ACTION_EXTRACT_UNCERTAINTIES =>
                'Extract uncertainty markers, unsupported claims, factual gaps and evidence needs.',
            self::ACTION_DRAFT_REFLECTION =>
                'Draft a learner reflection that remains explicitly revisable by the learner.',
            self::ACTION_SUMMARISE_ASSEMBLY =>
                'Summarize assembly discussion, motions, objections, minority positions and unresolved questions.',
            self::ACTION_CRITIQUE_AI_OUTPUT =>
                'Critique the AI output for overclaiming, missing evidence, bias, unsafe authority and uncertainty.',
            self::ACTION_PREPARE_INTEGRITY_REVIEW =>
                'Prepare neutral integrity-review notes only. Do not decide, sanction, validate or close the case.',
        ];

        return $guardrail . "\n\n" . ($instructions[$action] ?? '');
    }

    /**
     * Call the configured AI provider endpoint.
     *
     * The endpoint is expected to accept a JSON payload and return either:
     * - {"content": "..."}
     * - {"choices": [{"message": {"content": "..."}}]}
     * - {"choices": [{"text": "..."}]}
     *
     * @param array<string,mixed> $payload Payload.
     * @return array<string,mixed>
     */
    private static function call_provider(array $payload): array {
        $endpoint = trim((string)get_config('aiprovider_uckk', 'provider_endpoint'));
        $apikey = trim((string)get_config('aiprovider_uckk', 'provider_apikey'));

        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($apikey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apikey;
        }

        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => self::DEFAULT_TIMEOUT,
        ];

        $response = $curl->post($endpoint, json_encode($payload), $options);
        $errno = $curl->get_errno();
        $info = $curl->get_info();

        if ($errno) {
            throw new \moodle_exception('providerrequestfailed', 'aiprovider_uckk', '', $errno);
        }

        $httpcode = (int)($info['http_code'] ?? 0);
        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('providerbadstatus', 'aiprovider_uckk', '', $httpcode);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \moodle_exception('providerinvalidjson', 'aiprovider_uckk');
        }

        return $decoded;
    }

    /**
     * Extract text content from a provider response.
     *
     * @param array<string,mixed> $rawresponse Raw response.
     * @return string
     */
    private static function extract_content(array $rawresponse): string {
        if (isset($rawresponse['content']) && is_string($rawresponse['content'])) {
            return trim($rawresponse['content']);
        }

        if (isset($rawresponse['choices'][0]['message']['content'])
                && is_string($rawresponse['choices'][0]['message']['content'])) {
            return trim($rawresponse['choices'][0]['message']['content']);
        }

        if (isset($rawresponse['choices'][0]['text']) && is_string($rawresponse['choices'][0]['text'])) {
            return trim($rawresponse['choices'][0]['text']);
        }

        throw new \moodle_exception('provideremptyresponse', 'aiprovider_uckk');
    }

    /**
     * Add the required non-authority label.
     *
     * @param string $content Provider content.
     * @return string
     */
    private static function with_non_authority_label(string $content): string {
        $label = current_language() === 'fr'
            ? 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.'
            : 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';

        return $label . "\n\n" . trim($content);
    }

    /**
     * Return configured provider model.
     *
     * @return string
     */
    private static function provider_model(): string {
        $model = trim((string)get_config('aiprovider_uckk', 'provider_model'));
        return $model !== '' ? $model : 'default';
    }

    /**
     * Return configured maximum tokens.
     *
     * @return int
     */
    private static function max_tokens(): int {
        $maxtokens = (int)get_config('aiprovider_uckk', 'max_tokens');
        return $maxtokens > 0 ? min($maxtokens, 32000) : 2048;
    }

    /**
     * Whether prompts should be logged.
     *
     * @return bool
     */
    private static function should_log_prompts(): bool {
        return (bool)get_config('aiprovider_uckk', 'log_prompts');
    }

    /**
     * Whether responses should be logged.
     *
     * @return bool
     */
    private static function should_log_responses(): bool {
        return (bool)get_config('aiprovider_uckk', 'log_responses');
    }

    /**
     * Whether user data should be redacted before provider submission.
     *
     * @return bool
     */
    private static function should_redact_user_data(): bool {
        return (bool)get_config('aiprovider_uckk', 'redact_user_data_before_send');
    }

    /**
     * Basic redaction for personal identifiers before sending content to the provider.
     *
     * @param string $text Input text.
     * @return string
     */
    private static function redact_user_data(string $text): string {
        $patterns = [
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu' => '[redacted-email]',
            '/\b(?:\+?\d[\d\s().-]{7,}\d)\b/u' => '[redacted-phone]',
            '/\buserid\s*[:=]\s*\d+\b/iu' => 'userid:[redacted]',
            '/\buser\s*id\s*[:=]\s*\d+\b/iu' => 'user id:[redacted]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Detect whether this request is in an integrity-sensitive context.
     *
     * @param \context $context Context.
     * @return bool
     */
    private static function is_integrity_context(\context $context): bool {
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            return false;
        }

        $path = $context->get_url();
        if ($path && strpos($path->out(false), 'uckkintegrity') !== false) {
            return true;
        }

        return has_capability('tool/uckkintegrity:reviewcase', $context, null, false)
            || has_capability('tool/uckkintegrity:viewrestricted', $context, null, false);
    }

    /**
     * Log prompts or responses when configured.
     *
     * If the table does not exist yet, this method silently skips storage so the
     * processor remains safe during install/upgrade.
     *
     * @param string $type prompt|response.
     * @param string $action Action key.
     * @param \context $context Context.
     * @param string $content Logged content.
     * @param array<string,mixed> $metadata Metadata.
     * @return void
     */
    private static function log_record(string $type, string $action, \context $context, string $content, array $metadata = []): void {
        global $DB, $USER;

        if (!$DB->get_manager()->table_exists('aiprovider_uckk_log')) {
            return;
        }

        $now = time();

        $DB->insert_record('aiprovider_uckk_log', (object)[
            'userid' => $USER->id,
            'contextid' => $context->id,
            'action' => $action,
            'recordtype' => $type,
            'content' => $content,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timecreated' => $now,
        ]);
    }
}