<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Integrity case severity values.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class severity {
    /** Low severity. */
    public const LOW = 'low';

    /** Normal severity. */
    public const NORMAL = 'normal';

    /** High severity. */
    public const HIGH = 'high';

    /** Critical severity. */
    public const CRITICAL = 'critical';

    /** @var array<string,int> Severity weights used for sorting and comparisons. */
    private const WEIGHTS = [
        self::LOW => 10,
        self::NORMAL => 20,
        self::HIGH => 30,
        self::CRITICAL => 40,
    ];

    /**
     * Return all valid severity values.
     *
     * @return string[]
     */
    public static function values(): array {
        return array_keys(self::WEIGHTS);
    }

    /**
     * Return severity options suitable for Moodle forms.
     *
     * @return array<string,string>
     */
    public static function options(): array {
        return [
            self::LOW => get_string('severity:low', 'tool_uckkintegrity'),
            self::NORMAL => get_string('severity:normal', 'tool_uckkintegrity'),
            self::HIGH => get_string('severity:high', 'tool_uckkintegrity'),
            self::CRITICAL => get_string('severity:critical', 'tool_uckkintegrity'),
        ];
    }

    /**
     * Return the configured default severity.
     *
     * @return string
     */
    public static function default(): string {
        $configured = get_config('tool_uckkintegrity', 'defaultseverity');

        if (is_string($configured) && self::is_valid($configured)) {
            return $configured;
        }

        return self::NORMAL;
    }

    /**
     * Check whether a severity value is valid.
     *
     * @param string|null $severity Severity value.
     * @return bool
     */
    public static function is_valid(?string $severity): bool {
        return $severity !== null && array_key_exists($severity, self::WEIGHTS);
    }

    /**
     * Normalize a severity value.
     *
     * Invalid or empty values become the configured default severity.
     *
     * @param string|null $severity Severity value.
     * @return string
     */
    public static function normalize(?string $severity): string {
        if ($severity === null) {
            return self::default();
        }

        $severity = trim(core_text::strtolower($severity));

        if (self::is_valid($severity)) {
            return $severity;
        }

        return self::default();
    }

    /**
     * Get the localized label for a severity value.
     *
     * @param string|null $severity Severity value.
     * @return string
     */
    public static function label(?string $severity): string {
        $severity = self::normalize($severity);
        $options = self::options();

        return $options[$severity];
    }

    /**
     * Get the numeric weight for a severity.
     *
     * @param string|null $severity Severity value.
     * @return int
     */
    public static function weight(?string $severity): int {
        $severity = self::normalize($severity);

        return self::WEIGHTS[$severity];
    }

    /**
     * Check whether one severity is at least as serious as another.
     *
     * @param string|null $actual Actual severity.
     * @param string|null $minimum Minimum severity.
     * @return bool
     */
    public static function at_least(?string $actual, ?string $minimum): bool {
        return self::weight($actual) >= self::weight($minimum);
    }

    /**
     * Return true when the severity requires urgent attention.
     *
     * @param string|null $severity Severity value.
     * @return bool
     */
    public static function is_urgent(?string $severity): bool {
        return self::at_least($severity, self::HIGH);
    }
}