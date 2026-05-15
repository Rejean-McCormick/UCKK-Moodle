<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\service;

use context_system;
use context_user;
use local_uckk\event\profile_updated;
use local_uckk\local\constants;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Player profile service for local_uckk.
 *
 * @package    local_uckk
 */
final class profile_service {
    private const TABLE = 'local_uckk_player';

    public function get_or_create_profile(int $userid, array $defaults = []): stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['userid' => $userid]);
        if ($record) {
            return $this->export_record($record);
        }

        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $payload = array_merge([
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_SYSTEM,
            'metadata' => [],
        ], $defaults);

        return $this->create_or_update_profile($userid, $payload);
    }

    public function get_profile(int $userid): stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['userid' => $userid], '*', MUST_EXIST);
        return $this->export_record($record);
    }

    public function get_profile_for_viewer(int $targetuserid, ?int $viewerid = null): stdClass {
        global $USER;

        $viewerid = $viewerid ?? (int)($USER->id ?? 0);
        $profile = $this->get_profile($targetuserid);

        if ($viewerid === $targetuserid) {
            return $profile;
        }

        $context = context_system::instance();
        require_capability('local/uckk:viewcampus', $context, $viewerid);

        if (in_array($profile->visibility, [constants::VISIBILITY_RESTRICTED, constants::VISIBILITY_RESTRICTED_INTEGRITY, constants::VISIBILITY_HIDDEN], true)) {
            require_capability('local/uckk:viewrestricted', $context, $viewerid);
        }

        return $profile;
    }

    public function create_or_update_profile(int $userid, array $payload): stdClass {
        global $DB, $USER;

        $actorid = (int)($USER->id ?? 0);
        $usercontext = context_user::instance($userid);

        if ($actorid !== $userid) {
            require_capability('local/uckk:manageprofiles', $usercontext);
        }

        $displayname = trim((string)($payload['displayname'] ?? ''));
        if ($displayname === '') {
            $user = \core_user::get_user($userid, '*', MUST_EXIST);
            $displayname = fullname($user);
        }

        $symbolicrole = constants::assert_allowed((string)($payload['symbolicrole'] ?? constants::SYMBOLIC_ROLE_JOUEUR), constants::symbolic_roles(), 'Invalid symbolic role.');
        $status = constants::assert_allowed((string)($payload['status'] ?? constants::STATUS_ACTIVE), array_merge(constants::allowed_statuses(), [constants::STATUS_HIDDEN]), 'Invalid profile status.');
        $visibility = constants::assert_allowed((string)($payload['visibility'] ?? constants::VISIBILITY_USER), constants::allowed_visibilities(), 'Invalid profile visibility.');
        $provenance = constants::assert_allowed((string)($payload['provenance'] ?? constants::PROVENANCE_HUMAN), constants::allowed_provenances(), 'Invalid profile provenance.');

        $now = time();
        $record = $DB->get_record(self::TABLE, ['userid' => $userid]);
        $isupdate = (bool)$record;

        $metadata = $isupdate ? $this->decode_json($record->metadata ?? '') : [];
        $metadata = array_merge($metadata, is_array($payload['metadata'] ?? null) ? $payload['metadata'] : []);
        $metadata['provenance'] = $provenance;

        if (!$record) {
            $record = (object)[
                'userid' => $userid,
                'displaytitle' => $displayname,
                'symbolicroles' => $this->encode_json([$symbolicrole]),
                'activepathwayids' => $this->encode_json([]),
                'primarypathwayid' => null,
                'portfolioarchiveid' => null,
                'integrityflags' => $this->encode_json([]),
                'preferredlang' => null,
                'timezone' => null,
                'courseid' => null,
                'cmid' => null,
                'contextid' => $usercontext->id,
                'createdby' => $actorid,
                'modifiedby' => $actorid,
                'timecreated' => $now,
                'timemodified' => $now,
                'status' => $status,
                'visibility' => $visibility,
                'versionno' => 1,
                'metadata' => $this->encode_json($metadata),
            ];
            $record->provenancehash = $this->build_hash($userid, $provenance, $record->metadata, $now);
            $record->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $record->displaytitle = $displayname;
            $record->symbolicroles = $this->encode_json([$symbolicrole]);
            $record->contextid = $usercontext->id;
            $record->status = $status;
            $record->visibility = $visibility;
            $record->modifiedby = $actorid;
            $record->timemodified = $now;
            $record->versionno = (int)$record->versionno + 1;
            $record->metadata = $this->encode_json($metadata);
            $record->provenancehash = $this->build_hash($userid, $provenance, $record->metadata, $now);
            $DB->update_record(self::TABLE, $record);
        }

        $fresh = $DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST);
        $exported = $this->export_record($fresh);

        if ($isupdate) {
            $event = profile_updated::create([
                'context' => $usercontext,
                'objectid' => (int)$fresh->id,
                'relateduserid' => $userid,
                'other' => [
                    'versionno' => (int)$fresh->versionno,
                    'symbolicrole' => $symbolicrole,
                ],
            ]);
            $event->trigger();
        }

        return $exported;
    }

    public function update_symbolic_roles(int $userid, array $roles): stdClass {
        $roles = array_values(array_unique(array_map(static fn(string $v): string => constants::assert_allowed($v, constants::symbolic_roles(), 'Invalid symbolic role.'), $roles)));
        $primary = $roles[0] ?? constants::SYMBOLIC_ROLE_JOUEUR;

        return $this->create_or_update_profile($userid, [
            'displayname' => $this->get_or_create_profile($userid)->displayname,
            'symbolicrole' => $primary,
            'status' => $this->get_profile($userid)->status,
            'visibility' => $this->get_profile($userid)->visibility,
            'provenance' => $this->get_profile($userid)->provenance,
            'metadata' => array_merge($this->get_profile($userid)->metadata, ['symbolicroles' => $roles]),
        ]);
    }

    public function add_active_pathway(int $userid, int $pathwayid): stdClass {
        global $DB, $USER;

        $profile = $this->get_or_create_profile($userid);
        $active = array_values(array_unique(array_map('intval', $profile->activepathwayids)));
        if (!in_array($pathwayid, $active, true)) {
            $active[] = $pathwayid;
        }
        sort($active);

        $record = $DB->get_record(self::TABLE, ['id' => $profile->id], '*', MUST_EXIST);
        $record->activepathwayids = $this->encode_json($active);
        if (empty($record->primarypathwayid)) {
            $record->primarypathwayid = $pathwayid;
        }
        $record->modifiedby = (int)($USER->id ?? 0);
        $record->timemodified = time();
        $record->versionno = (int)$record->versionno + 1;
        $record->metadata = $this->encode_json($this->decode_json($record->metadata ?? ''));
        $DB->update_record(self::TABLE, $record);

        return $this->export_record($DB->get_record(self::TABLE, ['id' => $profile->id], '*', MUST_EXIST));
    }

    private function export_record(stdClass $record): stdClass {
        $record->displayname = (string)($record->displaytitle ?? '');
        $record->displaytitle = (string)($record->displaytitle ?? '');
        $record->symbolicroles = $this->decode_json($record->symbolicroles ?? '[]');
        if (!empty($record->metadata)) {
            $metadata = $this->decode_json($record->metadata);
            if (isset($metadata['symbolicroles']) && is_array($metadata['symbolicroles'])) {
                $record->symbolicroles = array_values(array_unique(array_merge($record->symbolicroles, array_map('strval', $metadata['symbolicroles']))));
            }
        }
        $record->symbolicrole = (string)($record->symbolicroles[0] ?? constants::SYMBOLIC_ROLE_JOUEUR);
        $record->activepathwayids = array_map('intval', $this->decode_json($record->activepathwayids ?? '[]'));
        $record->integrityflags = $this->decode_json($record->integrityflags ?? '[]');
        $record->metadata = $this->decode_json($record->metadata ?? '{}');
        $record->provenance = (string)($record->metadata['provenance'] ?? constants::PROVENANCE_HUMAN);
        $record->versionno = (int)$record->versionno;
        $record->createdby = (int)$record->createdby;
        $record->modifiedby = empty($record->modifiedby) ? 0 : (int)$record->modifiedby;
        $record->contextid = (int)$record->contextid;
        return $record;
    }

    private function decode_json(?string $value): array {
        if ($value === null || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function encode_json($value): string {
        $json = json_encode($value ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '[]' : $json;
    }

    private function build_hash(int $userid, string $provenance, string $payload, int $time): string {
        return hash('sha256', implode('|', [constants::COMPONENT, 'player', (string)$userid, $provenance, $payload, (string)$time]));
    }
}
