<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\local;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;

/**
 * Provenance and privacy-aware logging for governed UCKK AI actions.
 *
 * This class is intentionally service-layer only. It does not decide whether an
 * AI action is valid; callers must perform action-specific permission checks.
 * This logger records traceability, hashes, optional prompt/response bodies, and
 * shared UCKK provenance links.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provenance_logger {
    /** AI provider component name. */
    public const COMPONENT = 'aiprovider_uckk';

    /** AI provider local log table. */
    public const LOG_TABLE = 'aiprovider_uckk_log';

    /** Shared UCKK provenance table. */
    public const PROVENANCE_TABLE = 'local_uckk_provenance';

    /** Provenance state: draft/generated but not human-verified. */
    public const STATE_DRAFT = 'draft';

    /** Provenance state: human verified. */
    public const STATE_VERIFIED = 'verified';

    /** Provenance state: contested. */
    public const STATE_CONTESTED = 'contested';

    /** Provenance state: invalidated. */
    public const STATE_INVALIDATED = 'invalidated';

    /** Redaction state when no redaction was requested. */
    public const REDACTION_NONE = 'none';

    /** Redaction state when prompt/response text was redacted before logging or sending. */
    public const REDACTION_REDACTED = 'redacted';

    /** AI output authority label required by UCKK governance. */
    public const NON_AUTHORITY_LABEL_EN =
        'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';

    /** French AI output authority label required by UCKK governance. */
    public const NON_AUTHORITY_LABEL_FR =
        'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';

    /**
     * Start an AI provenance log record.
     *
     * @param string $action Canonical AI action key.
     * @param context|null $context Moodle context for the originating action.
     * @param string|null $prompt Prompt text before provider call.
     * @param array<string,mixed> $metadata Extra structured metadata.
     * @param string|null $sourcecomponent Originating component.
     * @param int|null $sourceid Originating object id.
     * @return int Log id, or 0 when the local log table is unavailable.
     */
    public static function log_request(
        string $action,
        ?context $context = null,
        ?string $prompt = null,
        array $metadata = [],
        ?string $sourcecomponent = null,
        ?int $sourceid = null
    ): int {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_ai_allowed($context);

        if (!self::table_exists(self::LOG_TABLE)) {
            return 0;
        }

        $now = time();
        $prompt = self::normalise_text($prompt);
        $redactedprompt = self::should_redact_before_send()
            ? self::redact_text($prompt)
            : $prompt;

        $record = (object)[
            'contextid' => $context->id,
            'userid' => $USER->id ?? 0,
            'action' => self::normalise_key($action, 100),
            'provider' => self::COMPONENT,
            'model' => self::normalise_config('provider_model', 100),
            'endpoint' => self::normalise_config('provider_endpoint', 255),
            'sourcecomponent' => self::normalise_key($sourcecomponent ?? '', 100),
            'sourceid' => $sourceid ?: null,
            'prompt' => self::should_log_prompts() ? $redactedprompt : null,
            'response' => null,
            'prompthash' => self::hash_text($redactedprompt),
            'responsehash' => null,
            'redactionstate' => self::should_redact_before_send() ? self::REDACTION_REDACTED : self::REDACTION_NONE,
            'status' => 'requested',
            'metadata' => self::encode_json($metadata + [
                'non_authority_label_en' => self::NON_AUTHORITY_LABEL_EN,
                'non_authority_label_fr' => self::NON_AUTHORITY_LABEL_FR,
            ]),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $logid = (int)$DB->insert_record(self::LOG_TABLE, $record);

        self::write_shared_provenance(
            itemtype: 'ai_request',
            itemid: $logid,
            sourcecomponent: $sourcecomponent,
            sourceid: $sourceid,
            sourcetext: $redactedprompt,
            hash: $record->prompthash,
            state: self::STATE_DRAFT,
            metadata: [
                'action' => $record->action,
                'contextid' => $context->id,
                'logid' => $logid,
            ]
        );

        self::trigger_event('request_logged', $context, $logid, [
            'action' => $record->action,
            'sourcecomponent' => $record->sourcecomponent,
            'sourceid' => $record->sourceid,
        ]);

        return $logid;
    }

    /**
     * Complete an AI provenance log record with response information.
     *
     * @param int $logid Log id returned by log_request().
     * @param string|null $response Provider response text.
     * @param string $status Final status, for example completed, failed, blocked.
     * @param array<string,mixed> $metadata Extra structured metadata.
     * @return void
     */
    public static function log_response(
        int $logid,
        ?string $response,
        string $status = 'completed',
        array $metadata = []
    ): void {
        global $DB;

        if ($logid <= 0 || !self::table_exists(self::LOG_TABLE)) {
            return;
        }

        $record = $DB->get_record(self::LOG_TABLE, ['id' => $logid], '*', IGNORE_MISSING);
        if (!$record) {
            return;
        }

        $response = self::normalise_text($response);
        $redactedresponse = self::should_redact_before_send()
            ? self::redact_text($response)
            : $response;

        $existingmetadata = self::decode_json($record->metadata ?? null);
        $record->response = self::should_log_responses() ? $redactedresponse : null;
        $record->responsehash = self::hash_text($redactedresponse);
        $record->status = self::normalise_key($status, 64);
        $record->metadata = self::encode_json($existingmetadata + $metadata);
        $record->timemodified = time();

        $DB->update_record(self::LOG_TABLE, $record);

        self::write_shared_provenance(
            itemtype: 'ai_response',
            itemid: $logid,
            sourcecomponent: $record->sourcecomponent ?: null,
            sourceid: !empty($record->sourceid) ? (int)$record->sourceid : null,
            sourcetext: $redactedresponse,
            hash: $record->responsehash,
            state: self::STATE_DRAFT,
            metadata: [
                'action' => $record->action,
                'contextid' => (int)$record->contextid,
                'logid' => $logid,
                'status' => $record->status,
            ]
        );

        $context = context::instance_by_id((int)$record->contextid, IGNORE_MISSING) ?: context_system::instance();
        self::trigger_event('response_logged', $context, $logid, [
            'action' => $record->action,
            'status' => $record->status,
        ]);
    }

    /**
     * Mark an AI log as verified, contested, or invalidated.
     *
     * @param int $logid AI log id.
     * @param string $state Provenance state.
     * @param string $reason Human-readable reason.
     * @return void
     */
    public static function mark_state(int $logid, string $state, string $reason = ''): void {
        global $DB;

        if ($logid <= 0 || !self::table_exists(self::LOG_TABLE)) {
            return;
        }

        $state = self::normalise_state($state);
        $record = $DB->get_record(self::LOG_TABLE, ['id' => $logid], '*', IGNORE_MISSING);
        if (!$record) {
            return;
        }

        $metadata = self::decode_json($record->metadata ?? null);
        $metadata['provenance_state'] = $state;
        $metadata['state_reason'] = clean_text($reason, FORMAT_PLAIN);
        $metadata['state_changed_at'] = time();

        $record->metadata = self::encode_json($metadata);
        $record->timemodified = time();
        $DB->update_record(self::LOG_TABLE, $record);

        self::write_shared_provenance(
            itemtype: 'ai_state',
            itemid: $logid,
            sourcecomponent: $record->sourcecomponent ?: null,
            sourceid: !empty($record->sourceid) ? (int)$record->sourceid : null,
            sourcetext: $reason,
            hash: self::hash_text($reason),
            state: $state,
            metadata: [
                'action' => $record->action,
                'contextid' => (int)$record->contextid,
                'logid' => $logid,
            ]
        );
    }

    /**
     * Build a labelled response for UI use.
     *
     * @param string $response AI response text.
     * @param string $lang Language code.
     * @return string
     */
    public static function label_response(string $response, string $lang = 'en'): string {
        $label = str_starts_with($lang, 'fr') ? self::NON_AUTHORITY_LABEL_FR : self::NON_AUTHORITY_LABEL_EN;

        return $label . "\n\n" . $response;
    }

    /**
     * Remove expired AI logs according to retention_days.
     *
     * @return int Number of deleted records.
     */
    public static function purge_expired_logs(): int {
        global $DB;

        if (!self::table_exists(self::LOG_TABLE)) {
            return 0;
        }

        $retentiondays = (int)get_config(self::COMPONENT, 'retention_days');
        if ($retentiondays <= 0) {
            return 0;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);

        return $DB->delete_records_select(self::LOG_TABLE, 'timecreated < :cutoff', ['cutoff' => $cutoff]);
    }

    /**
     * Write a shared UCKK provenance record if local_uckk provenance is installed.
     *
     * @param string $itemtype Provenance item type.
     * @param int $itemid Provenance item id.
     * @param string|null $sourcecomponent Source component.
     * @param int|null $sourceid Source object id.
     * @param string|null $sourcetext Source description/text.
     * @param string|null $hash Hash.
     * @param string $state Provenance state.
     * @param array<string,mixed> $metadata Extra metadata.
     * @return int Inserted provenance id, or 0 if unavailable.
     */
    private static function write_shared_provenance(
        string $itemtype,
        int $itemid,
        ?string $sourcecomponent,
        ?int $sourceid,
        ?string $sourcetext,
        ?string $hash,
        string $state,
        array $metadata = []
    ): int {
        global $DB, $USER;

        if (!self::table_exists(self::PROVENANCE_TABLE)) {
            return 0;
        }

        $now = time();

        $record = (object)[
            'component' => self::COMPONENT,
            'itemtype' => self::normalise_key($itemtype, 100),
            'itemid' => $itemid,
            'sourcecomponent' => self::normalise_key($sourcecomponent ?? self::COMPONENT, 100),
            'sourceid' => $sourceid ?: null,
            'sourcetext' => self::normalise_text($sourcetext),
            'hash' => $hash,
            'state' => self::normalise_state($state),
        ];

        if ($DB->get_manager()->field_exists(self::PROVENANCE_TABLE, 'metadata')) {
            $record->metadata = self::encode_json($metadata);
        }
        if ($DB->get_manager()->field_exists(self::PROVENANCE_TABLE, 'createdby')) {
            $record->createdby = $USER->id ?? 0;
        }
        if ($DB->get_manager()->field_exists(self::PROVENANCE_TABLE, 'timecreated')) {
            $record->timecreated = $now;
        }
        if ($DB->get_manager()->field_exists(self::PROVENANCE_TABLE, 'timemodified')) {
            $record->timemodified = $now;
        }

        return (int)$DB->insert_record(self::PROVENANCE_TABLE, $record);
    }

    /**
     * Require AI use in the selected context.
     *
     * @param context $context Moodle context.
     * @return void
     */
    private static function require_ai_allowed(context $context): void {
        require_capability('aiprovider/uckk:use', $context);

        if (self::is_restricted_context($context) && !get_config(self::COMPONENT, 'allow_in_integrity_contexts')) {
            throw new \required_capability_exception(
                $context,
                'aiprovider/uckk:use',
                'nopermissions',
                ''
            );
        }
    }

    /**
     * Decide whether the context should be treated as restricted for AI use.
     *
     * @param context $context Moodle context.
     * @return bool
     */
    private static function is_restricted_context(context $context): bool {
        if (has_capability('tool/uckkintegrity:viewrestricted', $context)) {
            return true;
        }

        if (has_capability('mod/uckkarchive:viewrestricted', $context)) {
            return true;
        }

        return false;
    }

    /**
     * Trigger an optional plugin event if the event class exists.
     *
     * @param string $eventname Short event name.
     * @param context $context Context.
     * @param int $objectid Related object id.
     * @param array<string,mixed> $other Event data.
     * @return void
     */
    private static function trigger_event(string $eventname, context $context, int $objectid, array $other = []): void {
        $classname = '\\aiprovider_uckk\\event\\ai_' . $eventname;

        if (!class_exists($classname)) {
            return;
        }

        $classname::create([
            'context' => $context,
            'objectid' => $objectid,
            'other' => $other,
        ])->trigger();
    }

    /**
     * Check whether a table exists.
     *
     * @param string $tablename Table name without braces.
     * @return bool
     */
    private static function table_exists(string $tablename): bool {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }

    /**
     * Whether prompt bodies should be stored.
     *
     * @return bool
     */
    private static function should_log_prompts(): bool {
        return (bool)get_config(self::COMPONENT, 'log_prompts');
    }

    /**
     * Whether response bodies should be stored.
     *
     * @return bool
     */
    private static function should_log_responses(): bool {
        return (bool)get_config(self::COMPONENT, 'log_responses');
    }

    /**
     * Whether text should be redacted before sending/logging.
     *
     * @return bool
     */
    private static function should_redact_before_send(): bool {
        return (bool)get_config(self::COMPONENT, 'redact_user_data_before_send');
    }

    /**
     * Redact common personal or secret-bearing values.
     *
     * @param string|null $text Input text.
     * @return string|null Redacted text.
     */
    private static function redact_text(?string $text): ?string {
        if ($text === null || $text === '') {
            return $text;
        }

        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $text);
        $text = preg_replace('/\b(?:\+?\d[\d .()\-]{7,}\d)\b/', '[redacted-phone]', $text);
        $text = preg_replace('/\b(token|api[_-]?key|secret|password)\s*[:=]\s*\S+/i', '$1=[redacted-secret]', $text);
        $text = preg_replace('/\buserid\s*[:=]\s*\d+\b/i', 'userid=[redacted-userid]', $text);

        return $text;
    }

    /**
     * Normalize free text for storage.
     *
     * @param string|null $text Text.
     * @return string|null
     */
    private static function normalise_text(?string $text): ?string {
        if ($text === null) {
            return null;
        }

        $text = trim($text);
        if ($text === '') {
            return null;
        }

        return clean_text($text, FORMAT_PLAIN);
    }

    /**
     * Normalize a component/action/status-like key.
     *
     * @param string $value Raw key.
     * @param int $maxlength Max length.
     * @return string
     */
    private static function normalise_key(string $value, int $maxlength): string {
        $value = clean_param($value, PARAM_ALPHANUMEXT);
        return substr($value, 0, $maxlength);
    }

    /**
     * Normalize a provenance state.
     *
     * @param string $state State.
     * @return string
     */
    private static function normalise_state(string $state): string {
        $state = self::normalise_key($state, 64);

        if (!in_array($state, [
            self::STATE_DRAFT,
            self::STATE_VERIFIED,
            self::STATE_CONTESTED,
            self::STATE_INVALIDATED,
        ], true)) {
            return self::STATE_DRAFT;
        }

        return $state;
    }

    /**
     * Read and normalize a plugin config string.
     *
     * @param string $name Config name.
     * @param int $maxlength Max length.
     * @return string|null
     */
    private static function normalise_config(string $name, int $maxlength): ?string {
        $value = get_config(self::COMPONENT, $name);

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return substr(clean_param($value, PARAM_TEXT), 0, $maxlength);
    }

    /**
     * Hash text with SHA-256.
     *
     * @param string|null $text Text.
     * @return string|null
     */
    private static function hash_text(?string $text): ?string {
        if ($text === null || $text === '') {
            return null;
        }

        return hash('sha256', $text);
    }

    /**
     * Encode metadata as JSON.
     *
     * @param array<string,mixed> $metadata Metadata.
     * @return string|null
     */
    private static function encode_json(array $metadata): ?string {
        if (empty($metadata)) {
            return null;
        }

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode JSON metadata.
     *
     * @param string|null $json JSON.
     * @return array<string,mixed>
     */
    private static function decode_json(?string $json): array {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}