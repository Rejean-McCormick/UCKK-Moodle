<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

class confidentiality {
    public const RESTRICTED = 'restricted';
    public const PARTIES = 'parties';
    public const PUBLIC_SUMMARY = 'public_summary';

    public static function values(): array {
        return [self::RESTRICTED, self::PARTIES, self::PUBLIC_SUMMARY];
    }

    public static function menu(): array {
        return [
            self::RESTRICTED => get_string('restricted', 'tool_uckkintegrity'),
            self::PARTIES => get_string('parties', 'tool_uckkintegrity'),
            self::PUBLIC_SUMMARY => get_string('public_summary', 'tool_uckkintegrity'),
        ];
    }
}