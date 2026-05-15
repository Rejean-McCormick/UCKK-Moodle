<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Confidentiality and visibility rules for integrity cases.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class confidentiality {
    public const PRIVATE_CASE = 'private';
    public const RESTRICTED = 'restricted';
    public const PUBLIC_SUMMARY = 'public_summary';
    public const PUBLIC_CASE = 'public';

    public static function values(): array {
        return [
            self::PRIVATE_CASE,
            self::RESTRICTED,
            self::PUBLIC_SUMMARY,
            self::PUBLIC_CASE,
        ];
    }

    public static function options(): array {
        return [
            self::PRIVATE_CASE => get_string('visibility', 'tool_uckkintegrity') . ': private',
            self::RESTRICTED => get_string('restricted', 'tool_uckkintegrity'),
            self::PUBLIC_SUMMARY => get_string('public_summary', 'tool_uckkintegrity'),
            self::PUBLIC_CASE => get_string('visibility', 'tool_uckkintegrity') . ': public',
        ];
    }

    public static function ensure_valid(?string $value): string {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            $value = self::RESTRICTED;
        }

        if (!in_array($value, self::values(), true)) {
            throw new \moodle_exception('invalidvisibility', 'tool_uckkintegrity');
        }

        return $value;
    }

    public static function is_restricted(\stdClass $case): bool {
        $visibility = self::ensure_valid((string) ($case->visibility ?? self::RESTRICTED));

        return in_array($visibility, [self::PRIVATE_CASE, self::RESTRICTED], true);
    }
}
