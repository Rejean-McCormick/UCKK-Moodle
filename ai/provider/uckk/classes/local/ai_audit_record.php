<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Audit record for governed UCKK AI requests.
 *
 * This class stores audit metadata for AI calls. Prompt and response bodies are
 * stored only when the corresponding plugin settings allow it.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_audit_record {
    /** Audit table name. */
    public const TABLE = 'aiprovider_uckk_audit';

    /** Request was created but no provider response has been recorded yet. */
    public const STATUS_PENDING = 'pending';

    /** Request completed successfully. */
    public const STATUS_COMPLETED = 'completed';

    /** Request failed. */
    public const STATUS_FAILED = 'failed';

    /** Request was blocked by policy before calling the provider. */
    public const STATUS_BLOCKED = 'blocked';

    /** No redaction was applied. */
    public const REDACTION_NONE = 'none';

    /** User-identifying data was redacted before sending to provider. */
    public const REDACTION_USER_DATA = 'user_data';

    /** Prompt body was not stored. */
    public const REDACTION_PROMPT_NOT_STORED = 'prompt_not_stored';

    /** Response body was not stored. */
    public const REDACTION_RESPONSE_NOT_STORED = 'response_not_stored';

    /** @var int|null Record id. */
    private ?int $id = null;

    /** @var int Moodle context id. */
    private int $contextid;

    /** @var int Moodle user id. */
    private int $userid;

    /** @var string AI action key. */
    private string $action;

    /** @var string Provider endpoint or provider identifier. */
    private string $provider;

    /** @var string Provider model identifier. */
    private string $model;

    /** @var string|null Stored prompt text, when logging is enabled. */
    private ?string $prompt;

    /** @var string|null Stored response text, when logging is enabled. */
    private ?string $response;

    /** @var string Request status. */
    private string $status;

    /** @var string Redaction state. */
    private string $redactionstate;

    /** @var int Prompt token count, when known. */
    private int $prompttokens;

    /** @var int Completion token count, when known. */
    private int $completiontokens;

    /** @var int Request latency in milliseconds, when known. */
    private int $latencyms;

    /** @var string|null Provider or policy error code. */
    private ?string $errorcode;

    /** @var string|null Provider or policy error message. */
    private ?string $errormessage;

    /** @var array<string,mixed> Additional structured metadata. */
    private array $metadata;

    /** @var int Creation timestamp. */
    private int $timecreated;

    /** @var int Modification timestamp. */
    private int $timemodified;

    /**
     * Constructor.
     *
     * @param int $contextid Context id.
     * @param int $userid User id.
     * @param string $action AI action key.
     * @param string $provider Provider endpoint or identifier.
     * @param string $model Provider model.
     * @param string|null $prompt Prompt text.
     * @param string|null $response Response text.
     * @param string $status Request status.
     * @param string $redactionstate Redaction state.
     * @param int $prompttokens Prompt token count.
     * @param int $completiontokens Completion token count.
     * @param int $latencyms Latency in milliseconds.
     * @param string|null $errorcode Error code.
     * @param string|null $errormessage Error message.
     * @param array<string,mixed> $metadata Additional metadata.
     * @param int|null $id Existing record id.
     * @param int|null $timecreated Creation timestamp.
     * @param int|null $timemodified Modification timestamp.
     */
    public function __construct(
        int $contextid,
        int $userid,
        string $action,
        string $provider,
        string $model,
        ?string $prompt = null,
        ?string $response = null,
        string $status = self::STATUS_PENDING,
        string $redactionstate = self::REDACTION_NONE,
        int $prompttokens = 0,
        int $completiontokens = 0,
        int $latencyms = 0,
        ?string $errorcode = null,
        ?string $errormessage = null,
        array $metadata = [],
        ?int $id = null,
        ?int $timecreated = null,
        ?int $timemodified = null
    ) {
        $now = time();

        $this->id = $id;
        $this->contextid = $contextid;
        $this->userid = $userid;
        $this->action = self::clean_key($action);
        $this->provider = clean_param($provider, PARAM_URL);
        $this->model = clean_param($model, PARAM_TEXT);
        $this->prompt = $prompt === null ? null : clean_text($prompt, FORMAT_PLAIN);
        $this->response = $response === null ? null : clean_text($response, FORMAT_PLAIN);
        $this->status = self::normalize_status($status);
        $this->redactionstate = self::normalize_redaction_state($redactionstate);
        $this->prompttokens = max(0, $prompttokens);
        $this->completiontokens = max(0, $completiontokens);
        $this->latencyms = max(0, $latencyms);
        $this->errorcode = $errorcode === null ? null : clean_param($errorcode, PARAM_ALPHANUMEXT);
        $this->errormessage = $errormessage === null ? null : clean_text($errormessage, FORMAT_PLAIN);
        $this->metadata = $metadata;
        $this->timecreated = $timecreated ?? $now;
        $this->timemodified = $timemodified ?? $now;
    }

    /**
     * Create and persist a pending audit record for an AI request.
     *
     * @param \context $context Originating Moodle context.
     * @param string $action AI action key.
     * @param string $prompt Prompt text before optional storage redaction.
     * @param array<string,mixed> $metadata Additional metadata.
     * @param int|null $userid User id. Defaults to current user.
     * @return self
     */
    public static function create_for_request(
        \context $context,
        string $action,
        string $prompt,
        array $metadata = [],
        ?int $userid = null
    ): self {
        global $USER;

        $provider = (string)get_config('aiprovider_uckk', 'provider_endpoint');
        $model = (string)get_config('aiprovider_uckk', 'provider_model');

        $logprompts = (bool)get_config('aiprovider_uckk', 'log_prompts');
        $redactuserdata = (bool)get_config('aiprovider_uckk', 'redact_user_data_before_send');

        $storedprompt = $logprompts ? $prompt : null;
        $redactionstate = self::REDACTION_NONE;

        if (!$logprompts) {
            $redactionstate = self::REDACTION_PROMPT_NOT_STORED;
        } else if ($redactuserdata) {
            $storedprompt = self::redact_user_data($storedprompt);
            $redactionstate = self::REDACTION_USER_DATA;
        }

        $record = new self(
            $context->id,
            $userid ?? (int)$USER->id,
            $action,
            $provider,
            $model,
            $storedprompt,
            null,
            self::STATUS_PENDING,
            $redactionstate,
            0,
            0,
            0,
            null,
            null,
            $metadata
        );

        $record->save();

        return $record;
    }

    /**
     * Create and persist a blocked audit record.
     *
     * @param \context $context Originating context.
     * @param string $action AI action key.
     * @param string $reason Blocking reason.
     * @param array<string,mixed> $metadata Additional metadata.
     * @param int|null $userid User id. Defaults to current user.
     * @return self
     */
    public static function create_blocked(
        \context $context,
        string $action,
        string $reason,
        array $metadata = [],
        ?int $userid = null
    ): self {
        global $USER;

        $record = new self(
            $context->id,
            $userid ?? (int)$USER->id,
            $action,
            (string)get_config('aiprovider_uckk', 'provider_endpoint'),
            (string)get_config('aiprovider_uckk', 'provider_model'),
            null,
            null,
            self::STATUS_BLOCKED,
            self::REDACTION_PROMPT_NOT_STORED,
            0,
            0,
            0,
            'policy_blocked',
            $reason,
            $metadata
        );

        $record->save();

        return $record;
    }

    /**
     * Load an audit record by id.
     *
     * @param int $id Audit record id.
     * @return self
     */
    public static function get(int $id): self {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);

        return self::from_record($record);
    }

    /**
     * Persist a successful provider response.
     *
     * @param string $response Provider response text.
     * @param int $prompttokens Prompt token count.
     * @param int $completiontokens Completion token count.
     * @param int $latencyms Latency in milliseconds.
     * @param array<string,mixed> $metadata Additional metadata to merge.
     * @return void
     */
    public function mark_completed(
        string $response,
        int $prompttokens = 0,
        int $completiontokens = 0,
        int $latencyms = 0,
        array $metadata = []
    ): void {
        $logresponses = (bool)get_config('aiprovider_uckk', 'log_responses');

        $this->response = $logresponses ? clean_text($response, FORMAT_PLAIN) : null;
        $this->status = self::STATUS_COMPLETED;
        $this->prompttokens = max(0, $prompttokens);
        $this->completiontokens = max(0, $completiontokens);
        $this->latencyms = max(0, $latencyms);
        $this->errorcode = null;
        $this->errormessage = null;
        $this->metadata = array_merge($this->metadata, $metadata);

        if (!$logresponses) {
            $this->redactionstate = self::REDACTION_RESPONSE_NOT_STORED;
        }

        $this->save();
    }

    /**
     * Persist a provider or policy error.
     *
     * @param string $errorcode Error code.
     * @param string $errormessage Error message.
     * @param int $latencyms Latency in milliseconds.
     * @param array<string,mixed> $metadata Additional metadata to merge.
     * @return void
     */
    public function mark_failed(
        string $errorcode,
        string $errormessage,
        int $latencyms = 0,
        array $metadata = []
    ): void {
        $this->status = self::STATUS_FAILED;
        $this->errorcode = clean_param($errorcode, PARAM_ALPHANUMEXT);
        $this->errormessage = clean_text($errormessage, FORMAT_PLAIN);
        $this->latencyms = max(0, $latencyms);
        $this->metadata = array_merge($this->metadata, $metadata);

        $this->save();
    }

    /**
     * Save the audit record.
     *
     * @return int Audit record id.
     */
    public function save(): int {
        global $DB;

        $this->timemodified = time();
        $record = $this->to_record();

        if ($this->id === null) {
            $this->id = (int)$DB->insert_record(self::TABLE, $record);
        } else {
            $record->id = $this->id;
            $DB->update_record(self::TABLE, $record);
        }

        return $this->id;
    }

    /**
     * Delete audit records older than configured retention.
     *
     * @return int Number of deleted records.
     */
    public static function purge_expired(): int {
        global $DB;

        $retentiondays = (int)get_config('aiprovider_uckk', 'retention_days');
        if ($retentiondays <= 0) {
            return 0;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);

        return $DB->delete_records_select(self::TABLE, 'timecreated < :cutoff', ['cutoff' => $cutoff]);
    }

    /**
     * Convert this object to a database record.
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return (object)[
            'contextid' => $this->contextid,
            'userid' => $this->userid,
            'action' => $this->action,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt' => $this->prompt,
            'response' => $this->response,
            'status' => $this->status,
            'redactionstate' => $this->redactionstate,
            'prompttokens' => $this->prompttokens,
            'completiontokens' => $this->completiontokens,
            'latencyms' => $this->latencyms,
            'errorcode' => $this->errorcode,
            'errormessage' => $this->errormessage,
            'metadata' => self::encode_metadata($this->metadata),
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }

    /**
     * Build object from database record.
     *
     * @param \stdClass $record Database record.
     * @return self
     */
    public static function from_record(\stdClass $record): self {
        return new self(
            (int)$record->contextid,
            (int)$record->userid,
            (string)$record->action,
            (string)$record->provider,
            (string)$record->model,
            $record->prompt ?? null,
            $record->response ?? null,
            (string)$record->status,
            (string)$record->redactionstate,
            (int)$record->prompttokens,
            (int)$record->completiontokens,
            (int)$record->latencyms,
            $record->errorcode ?? null,
            $record->errormessage ?? null,
            self::decode_metadata($record->metadata ?? null),
            (int)$record->id,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }

    /**
     * Return audit record id.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Return request status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Return context id.
     *
     * @return int
     */
    public function get_contextid(): int {
        return $this->contextid;
    }

    /**
     * Return user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return action key.
     *
     * @return string
     */
    public function get_action(): string {
        return $this->action;
    }

    /**
     * Encode metadata as JSON.
     *
     * @param array<string,mixed> $metadata Metadata.
     * @return string|null
     */
    private static function encode_metadata(array $metadata): ?string {
        if (empty($metadata)) {
            return null;
        }

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode metadata JSON.
     *
     * @param string|null $metadata Metadata JSON.
     * @return array<string,mixed>
     */
    private static function decode_metadata(?string $metadata): array {
        if ($metadata === null || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Normalize status value.
     *
     * @param string $status Status.
     * @return string
     */
    private static function normalize_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $valid = [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_BLOCKED,
        ];

        return in_array($status, $valid, true) ? $status : self::STATUS_PENDING;
    }

    /**
     * Normalize redaction state.
     *
     * @param string $state Redaction state.
     * @return string
     */
    private static function normalize_redaction_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);

        $valid = [
            self::REDACTION_NONE,
            self::REDACTION_USER_DATA,
            self::REDACTION_PROMPT_NOT_STORED,
            self::REDACTION_RESPONSE_NOT_STORED,
        ];

        return in_array($state, $valid, true) ? $state : self::REDACTION_NONE;
    }

    /**
     * Clean action keys while preserving Moodle-style snake case.
     *
     * @param string $key Action key.
     * @return string
     */
    private static function clean_key(string $key): string {
        return clean_param(core_text::strtolower($key), PARAM_ALPHANUMEXT);
    }

    /**
     * Basic user-data redaction before prompt storage.
     *
     * Provider request redaction should happen before transport as well; this
     * method protects the local audit copy.
     *
     * @param string $text Text to redact.
     * @return string
     */
    private static function redact_user_data(string $text): string {
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $text);
        $text = preg_replace('/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[redacted-phone]', $text);

        return $text ?? '';
    }
}