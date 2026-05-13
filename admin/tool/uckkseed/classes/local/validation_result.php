<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Validation/result object for the UCKK Seed admin tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared result object for seed, reset, validate, and export operations.
 *
 * This object is intentionally data-focused. It does not create Moodle records,
 * delete records, assign capabilities, create courses, or decide seed workflow.
 */
final class validation_result {
    /** Run status: pending. */
    public const STATUS_PENDING = 'pending';

    /** Run status: running. */
    public const STATUS_RUNNING = 'running';

    /** Run status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Run status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Run status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Run status: skipped. */
    public const STATUS_SKIPPED = 'skipped';

    /** Run status: warning. */
    public const STATUS_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /**
     * Result status.
     *
     * @var string
     */
    private string $status = self::STATUS_PENDING;

    /**
     * Human-readable summary.
     *
     * @var string
     */
    private string $summary = '';

    /**
     * Result counters.
     *
     * @var array<string, int>
     */
    private array $counts = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'warnings' => 0,
        'errors' => 0,
    ];

    /**
     * Message rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $messages = [];

    /**
     * Created target rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $created = [];

    /**
     * Updated target rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $updated = [];

    /**
     * Skipped target rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $skipped = [];

    /**
     * Failed target rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $failed = [];

    /**
     * Extra metadata.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    /**
     * Constructor.
     *
     * @param string $status Initial status.
     * @param string $summary Initial summary.
     * @param array<string, mixed> $metadata Extra metadata.
     */
    public function __construct(
        string $status = self::STATUS_PENDING,
        string $summary = '',
        array $metadata = []
    ) {
        $this->status = self::normalise_status($status);
        $this->summary = trim($summary);
        $this->metadata = $metadata;
    }

    /**
     * Create a successful result.
     *
     * @param string $summary Summary.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public static function success(string $summary = '', array $metadata = []): self {
        return new self(self::STATUS_COMPLETED, $summary, $metadata);
    }

    /**
     * Create a warning result.
     *
     * @param string $summary Summary.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public static function warning(string $summary = '', array $metadata = []): self {
        return new self(self::STATUS_WARNING, $summary, $metadata);
    }

    /**
     * Create a failed result.
     *
     * @param string $summary Summary.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public static function failure(string $summary = '', array $metadata = []): self {
        return new self(self::STATUS_FAILED, $summary, $metadata);
    }

    /**
     * Rehydrate from stored/exported data.
     *
     * @param array<string, mixed>|stdClass $data Data.
     * @return self
     */
    public static function from_data(array|stdClass $data): self {
        $data = (array)$data;

        $result = new self(
            (string)($data['status'] ?? self::STATUS_PENDING),
            (string)($data['summary'] ?? ''),
            self::normalise_metadata($data['metadata'] ?? [])
        );

        if (!empty($data['counts']) && is_array($data['counts'])) {
            foreach ($data['counts'] as $key => $value) {
                $result->set_count((string)$key, (int)$value);
            }
        }

        foreach (['messages', 'created', 'updated', 'skipped', 'failed'] as $listkey) {
            if (empty($data[$listkey]) || !is_array($data[$listkey])) {
                continue;
            }

            foreach ($data[$listkey] as $row) {
                if ($listkey === 'messages') {
                    $message = (array)$row;
                    $result->add_message(
                        (string)($message['severity'] ?? self::SEVERITY_INFO),
                        (string)($message['message'] ?? ''),
                        (string)($message['component'] ?? 'tool_uckkseed'),
                        (string)($message['preset'] ?? ''),
                        (string)($message['targettype'] ?? ''),
                        (string)($message['targetkey'] ?? ''),
                        self::normalise_metadata($message['metadata'] ?? [])
                    );
                } else {
                    $result->{$listkey}[] = (array)$row;
                }
            }
        }

        return $result;
    }

    /**
     * Add an informational message.
     *
     * @param string $message Message.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_info(
        string $message,
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        return $this->add_message(
            self::SEVERITY_INFO,
            $message,
            'tool_uckkseed',
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Add a success message.
     *
     * @param string $message Message.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_success(
        string $message,
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        return $this->add_message(
            self::SEVERITY_SUCCESS,
            $message,
            'tool_uckkseed',
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Add a warning message.
     *
     * @param string $message Message.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_warning(
        string $message,
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        return $this->add_message(
            self::SEVERITY_WARNING,
            $message,
            'tool_uckkseed',
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Add an error message.
     *
     * @param string $message Message.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_error(
        string $message,
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        return $this->add_message(
            self::SEVERITY_ERROR,
            $message,
            'tool_uckkseed',
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Add a blocker message.
     *
     * @param string $message Message.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_blocker(
        string $message,
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        return $this->add_message(
            self::SEVERITY_BLOCKER,
            $message,
            'tool_uckkseed',
            $preset,
            $targettype,
            $targetkey,
            $metadata
        );
    }

    /**
     * Add a canonical message row.
     *
     * @param string $severity Severity.
     * @param string $message Message.
     * @param string $component Component.
     * @param string $preset Preset id.
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_message(
        string $severity,
        string $message,
        string $component = 'tool_uckkseed',
        string $preset = '',
        string $targettype = '',
        string $targetkey = '',
        array $metadata = []
    ): self {
        $severity = self::normalise_severity($severity);
        $message = trim($message);

        if ($message === '') {
            return $this;
        }

        $this->messages[] = [
            'severity' => $severity,
            'component' => clean_param($component, PARAM_COMPONENT),
            'preset' => clean_param($preset, PARAM_ALPHANUMEXT),
            'targettype' => clean_param($targettype, PARAM_ALPHANUMEXT),
            'targetkey' => clean_param($targetkey, PARAM_TEXT),
            'message' => $message,
            'metadata' => $metadata,
        ];

        if ($severity === self::SEVERITY_WARNING) {
            $this->increment('warnings');
        }

        if (in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
            $this->increment('errors');
            $this->status = self::STATUS_FAILED;
        } else if ($severity === self::SEVERITY_WARNING && $this->status === self::STATUS_COMPLETED) {
            $this->status = self::STATUS_WARNING;
        }

        return $this;
    }

    /**
     * Record a created target.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param int $targetid Target id.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_created(string $targettype, string $targetkey, int $targetid = 0, array $metadata = []): self {
        $this->created[] = $this->build_target_row($targettype, $targetkey, $targetid, $metadata);
        $this->increment('created');

        return $this;
    }

    /**
     * Record an updated target.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param int $targetid Target id.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_updated(string $targettype, string $targetkey, int $targetid = 0, array $metadata = []): self {
        $this->updated[] = $this->build_target_row($targettype, $targetkey, $targetid, $metadata);
        $this->increment('updated');

        return $this;
    }

    /**
     * Record a skipped target.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param int $targetid Target id.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_skipped(string $targettype, string $targetkey, int $targetid = 0, array $metadata = []): self {
        $this->skipped[] = $this->build_target_row($targettype, $targetkey, $targetid, $metadata);
        $this->increment('skipped');

        return $this;
    }

    /**
     * Record a failed target.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param int $targetid Target id.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function add_failed(string $targettype, string $targetkey, int $targetid = 0, array $metadata = []): self {
        $this->failed[] = $this->build_target_row($targettype, $targetkey, $targetid, $metadata);
        $this->increment('failed');
        $this->status = self::STATUS_FAILED;

        return $this;
    }

    /**
     * Merge another result into this result.
     *
     * @param self $other Other result.
     * @return self
     */
    public function merge(self $other): self {
        foreach ($other->get_counts() as $key => $count) {
            $this->counts[$key] = ($this->counts[$key] ?? 0) + (int)$count;
        }

        $this->messages = array_merge($this->messages, $other->get_messages());
        $this->created = array_merge($this->created, $other->get_created());
        $this->updated = array_merge($this->updated, $other->get_updated());
        $this->skipped = array_merge($this->skipped, $other->get_skipped());
        $this->failed = array_merge($this->failed, $other->get_failed());

        if ($other->get_summary() !== '') {
            $this->summary = trim($this->summary . "\n" . $other->get_summary());
        }

        $this->metadata = array_replace_recursive($this->metadata, $other->get_metadata());

        if ($other->has_errors()) {
            $this->status = self::STATUS_FAILED;
        } else if ($other->has_warnings() && $this->status === self::STATUS_COMPLETED) {
            $this->status = self::STATUS_WARNING;
        } else if ($this->status === self::STATUS_PENDING && $other->is_ok()) {
            $this->status = self::STATUS_COMPLETED;
        }

        return $this;
    }

    /**
     * Mark result completed when no errors exist.
     *
     * @param string $summary Optional summary.
     * @return self
     */
    public function complete(string $summary = ''): self {
        if ($summary !== '') {
            $this->summary = trim($summary);
        }

        if ($this->has_errors()) {
            $this->status = self::STATUS_FAILED;
        } else if ($this->has_warnings()) {
            $this->status = self::STATUS_WARNING;
        } else {
            $this->status = self::STATUS_COMPLETED;
        }

        return $this;
    }

    /**
     * Set status explicitly.
     *
     * @param string $status Status.
     * @return self
     */
    public function set_status(string $status): self {
        $this->status = self::normalise_status($status);

        return $this;
    }

    /**
     * Set summary.
     *
     * @param string $summary Summary.
     * @return self
     */
    public function set_summary(string $summary): self {
        $this->summary = trim($summary);

        return $this;
    }

    /**
     * Set metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public function set_metadata(array $metadata): self {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Add one metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $value Metadata value.
     * @return self
     */
    public function set_metadata_value(string $key, mixed $value): self {
        $key = clean_param($key, PARAM_ALPHANUMEXT);

        if ($key !== '') {
            $this->metadata[$key] = $value;
        }

        return $this;
    }

    /**
     * Increment a count.
     *
     * @param string $key Count key.
     * @param int $amount Amount.
     * @return self
     */
    public function increment(string $key, int $amount = 1): self {
        $key = clean_param($key, PARAM_ALPHANUMEXT);

        if ($key === '') {
            return $this;
        }

        $this->counts[$key] = ($this->counts[$key] ?? 0) + max(0, $amount);

        return $this;
    }

    /**
     * Set count value.
     *
     * @param string $key Count key.
     * @param int $value Count value.
     * @return self
     */
    public function set_count(string $key, int $value): self {
        $key = clean_param($key, PARAM_ALPHANUMEXT);

        if ($key !== '') {
            $this->counts[$key] = max(0, $value);
        }

        return $this;
    }

    /**
     * Whether the result is OK.
     *
     * @return bool
     */
    public function is_ok(): bool {
        return !$this->has_errors()
            && !in_array($this->status, [
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
            ], true);
    }

    /**
     * Whether the result has errors.
     *
     * @return bool
     */
    public function has_errors(): bool {
        return ($this->counts['errors'] ?? 0) > 0
            || ($this->counts['failed'] ?? 0) > 0
            || !empty($this->failed)
            || $this->has_message_severity(self::SEVERITY_ERROR)
            || $this->has_message_severity(self::SEVERITY_BLOCKER);
    }

    /**
     * Whether the result has warnings.
     *
     * @return bool
     */
    public function has_warnings(): bool {
        return ($this->counts['warnings'] ?? 0) > 0
            || $this->has_message_severity(self::SEVERITY_WARNING);
    }

    /**
     * Check whether a message severity exists.
     *
     * @param string $severity Severity.
     * @return bool
     */
    public function has_message_severity(string $severity): bool {
        $severity = self::normalise_severity($severity);

        foreach ($this->messages as $message) {
            if (($message['severity'] ?? '') === $severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Get summary.
     *
     * @return string
     */
    public function get_summary(): string {
        return $this->summary;
    }

    /**
     * Get counts.
     *
     * @return array<string, int>
     */
    public function get_counts(): array {
        return $this->counts;
    }

    /**
     * Get messages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_messages(): array {
        return $this->messages;
    }

    /**
     * Get created rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_created(): array {
        return $this->created;
    }

    /**
     * Get updated rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_updated(): array {
        return $this->updated;
    }

    /**
     * Get skipped rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_skipped(): array {
        return $this->skipped;
    }

    /**
     * Get failed rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_failed(): array {
        return $this->failed;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Export to plain array.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'status' => $this->status,
            'ok' => $this->is_ok(),
            'haserrors' => $this->has_errors(),
            'haswarnings' => $this->has_warnings(),
            'summary' => $this->summary,
            'counts' => $this->counts,
            'messages' => $this->messages,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Export to stdClass.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        return (object)$this->to_array();
    }

    /**
     * Export data for templates/output objects.
     *
     * @return stdClass
     */
    public function to_template_context(): stdClass {
        $data = $this->to_record();

        $data->statuslabel = $this->get_status_label();
        $data->statusclass = 'status-' . str_replace('_', '-', $this->status);
        $data->hascounts = !empty($this->counts);
        $data->hasmessages = !empty($this->messages);
        $data->hascreated = !empty($this->created);
        $data->hasupdated = !empty($this->updated);
        $data->hasskipped = !empty($this->skipped);
        $data->hasfailed = !empty($this->failed);

        $data->messages = array_map(static function (array $message): stdClass {
            $message['severityclass'] = 'severity-' . str_replace('_', '-', (string)($message['severity'] ?? 'info'));
            return (object)$message;
        }, $this->messages);

        $data->countitems = [];
        foreach ($this->counts as $key => $value) {
            $data->countitems[] = (object)[
                'key' => $key,
                'label' => $key,
                'value' => $value,
            ];
        }

        return $data;
    }

    /**
     * Build a compact JSON representation for logs.
     *
     * @return string
     */
    public function to_json(): string {
        return json_encode($this->to_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Get a status label.
     *
     * @return string
     */
    private function get_status_label(): string {
        $key = 'status_' . str_replace('-', '_', str_replace(':', '_', $this->status));

        if (get_string_manager()->string_exists($key, 'tool_uckkseed')) {
            return get_string($key, 'tool_uckkseed');
        }

        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Build a canonical target row.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param int $targetid Target id.
     * @param array<string, mixed> $metadata Metadata.
     * @return array<string, mixed>
     */
    private function build_target_row(
        string $targettype,
        string $targetkey,
        int $targetid = 0,
        array $metadata = []
    ): array {
        return [
            'targettype' => clean_param($targettype, PARAM_ALPHANUMEXT),
            'targetkey' => clean_param($targetkey, PARAM_TEXT),
            'targetid' => max(0, $targetid),
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_SKIPPED,
            self::STATUS_WARNING,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_PENDING;
    }

    /**
     * Normalise severity.
     *
     * @param string $severity Raw severity.
     * @return string
     */
    private static function normalise_severity(string $severity): string {
        $severity = clean_param($severity, PARAM_ALPHANUMEXT);

        $allowed = [
            self::SEVERITY_INFO,
            self::SEVERITY_SUCCESS,
            self::SEVERITY_WARNING,
            self::SEVERITY_ERROR,
            self::SEVERITY_BLOCKER,
        ];

        return in_array($severity, $allowed, true) ? $severity : self::SEVERITY_INFO;
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private static function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
```

Add these status strings to `admin/tool/uckkseed/lang/en/tool_uckkseed.php` if missing:

```php
$string['status_pending'] = 'Pending';
$string['status_running'] = 'Running';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';
$string['status_cancelled'] = 'Cancelled';
$string['status_skipped'] = 'Skipped';
$string['status_warning'] = 'Warning';
```

Add these to `admin/tool/uckkseed/lang/fr/tool_uckkseed.php` if missing:

```php
$string['status_pending'] = 'En attente';
$string['status_running'] = 'En cours';
$string['status_completed'] = 'Terminé';
$string['status_failed'] = 'Échec';
$string['status_cancelled'] = 'Annulé';
$string['status_skipped'] = 'Ignoré';
$string['status_warning'] = 'Avertissement';

