<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_uckk;

use aiprovider_uckk\local\prompt_policy;
use core_ai\aiactions\responses\response_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared processor base for UCKK governed text actions.
 *
 * This class keeps the provider wrapper thin: it translates Moodle AI actions
 * into governed UCKK payloads and returns a compliant response object.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_processor extends \core_ai\process_base {
    /**
     * Process the AI request into a Moodle response object.
     *
     * @return response_base
     */
    public function process(): response_base {
        try {
            $payload = $this->query_ai_api();
            $response = $this->get_response(true);
            $response->set_response_data($payload);
            return $response;
        } catch (\Throwable $e) {
            $response = $this->get_response(false, 500, $e->getMessage());
            $response->set_response_data([
                'content' => '',
                'error' => $e->getMessage(),
            ]);
            return $response;
        }
    }

    /**
     * Query the provider API.
     *
     * For the standalone-core pass this provider remains non-sovereign and thin.
     * The returned payload is already labelled and policy-constrained.
     *
     * @return array<string, mixed>
     */
    protected function query_ai_api(): array {
        $actionname = $this->extract_action_name();
        $governedaction = $this->map_action_to_governed_action($actionname);
        $contextid = (int) $this->get_action_config('contextid', 0);
        $userid = (int) $this->get_action_config('userid', 0);
        $prompt = $this->extract_prompt();
        $component = (string) $this->get_action_config('component', 'aiprovider_uckk');
        $purpose = (string) $this->get_action_config('purpose', $governedaction);
        $visibility = (string) $this->get_action_config('visibility', 'private');
        $lang = (string) $this->get_action_config('lang', current_language());
        $requestedtokens = (int) $this->get_action_config('maxtokens', 0);

        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if ($context === false || $context === null) {
            $context = \context_system::instance();
        }

        $payload = prompt_policy::build_payload(
            $context,
            $governedaction,
            $prompt,
            $component,
            $purpose,
            $visibility,
            $lang,
            $requestedtokens > 0 ? $requestedtokens : null
        );

        $content = $this->build_mock_content($payload['prompt'], $governedaction, $lang);

        return [
            'success' => true,
            'provider' => 'uckk',
            'model' => $this->get_provider_config(provider::CONFIG_PROVIDER_MODEL, ''),
            'userid' => $userid,
            'payload' => $payload,
            'content' => prompt_policy::label_output($content, $lang),
        ];
    }

    /**
     * Extract the AI action name.
     *
     * @return string
     */
    protected function extract_action_name(): string {
        if (method_exists($this->action, 'get_name')) {
            return (string) $this->action::get_name();
        }

        $parts = explode('\\', get_class($this->action));
        return strtolower((string) end($parts));
    }

    /**
     * Extract a prompt-like field from the action.
     *
     * @return string
     */
    protected function extract_prompt(): string {
        foreach (['prompttext', 'text', 'content'] as $field) {
            $value = $this->get_action_config($field, null);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Get an action configuration value if available.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function get_action_config(string $name, mixed $default): mixed {
        if (method_exists($this->action, 'get_configuration')) {
            $value = $this->action->get_configuration($name);
            return $value !== null ? $value : $default;
        }

        return $default;
    }

    /**
     * Get a provider configuration value with snapshot-compatible fallbacks.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function get_provider_config(string $name, mixed $default): mixed {
        if (isset($this->provider->config[$name])) {
            return $this->provider->config[$name];
        }

        if ($name === provider::CONFIG_API_KEY) {
            return get_config(provider::COMPONENT, 'api_key')
                ?: get_config(provider::COMPONENT, 'provider_apikey')
                ?: $default;
        }

        return get_config(provider::COMPONENT, $name) ?: $default;
    }

    /**
     * Map core AI actions to governed UCKK actions.
     *
     * @param string $actionname
     * @return string
     */
    protected function map_action_to_governed_action(string $actionname): string {
        return match ($actionname) {
            'generate_text' => prompt_policy::ACTION_DRAFT_REFLECTION,
            'summarise_text' => prompt_policy::ACTION_SUMMARISE_COURSE_MATERIAL,
            default => prompt_policy::ACTION_DRAFT_REFLECTION,
        };
    }

    /**
     * Build a local mock response body for the standalone-core pass.
     *
     * @param string $prompt
     * @param string $governedaction
     * @param string $lang
     * @return string
     */
    protected function build_mock_content(string $prompt, string $governedaction, string $lang): string {
        $prefix = strtolower($lang) === 'fr'
            ? 'Réponse préparée selon la politique UCKK pour l’action '
            : 'Response prepared under the UCKK governance policy for action ';

        $summary = trim($prompt);
        if ($summary === '') {
            $summary = strtolower($lang) === 'fr' ? 'Aucun texte source fourni.' : 'No source text provided.';
        } else if (core_text::strlen($summary) > 500) {
            $summary = core_text::substr($summary, 0, 500) . '…';
        }

        return $prefix . $governedaction . ".\n\n" . $summary;
    }
}
