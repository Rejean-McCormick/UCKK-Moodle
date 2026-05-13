<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Scheduled task to rebuild UCKK player profiles.
 *
 * This task rebuilds the derived summary layer stored in local_uckk_player.
 *
 * It must remain conservative:
 *
 * - it does not delete Moodle users;
 * - it does not delete portfolio content;
 * - it does not validate proofs;
 * - it does not close integrity cases;
 * - it does not award badges directly;
 * - it does not make pedagogical or disciplinary decisions;
 * - it does not turn AI, badges, counts or rankings into sovereign authority.
 *
 * Source plugins keep ownership of their source data. This task only rebuilds
 * the player profile summary used by dashboards and reports.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\task;

use core\task\scheduled_task;
use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/uckk/locallib.php');

/**
 * Rebuild UCKK player profiles.
 *
 * @package local_uckk
 */
class rebuild_player_profiles extends scheduled_task {
    /** Player profile table. */
    private const PLAYER_TABLE = 'local_uckk_player';

    /** Batch size for user processing. */
    private const BATCH_SIZE = 500;

    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /**
     * Return the task name shown in Moodle admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return local_uckk_get_string_or_fallback(
            'task:rebuildplayerprofiles',
            self::COMPONENT,
            'Rebuild UCKK player profiles'
        );
    }

    /**
     * Execute the scheduled task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists(self::PLAYER_TABLE)) {
            mtrace('UCKK player profile table does not exist. Nothing to rebuild.');
            return;
        }

        if (!$this->table_has_columns(self::PLAYER_TABLE, ['userid'])) {
            mtrace('UCKK player profile table is missing required column userid. Task stopped.');
            return;
        }

        raise_memory_limit(MEMORY_EXTRA);

        $started = time();
        $totalusers = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        mtrace('Starting UCKK player profile rebuild.');

        $userfields = [
            'id',
            'firstname',
            'lastname',
            'username',
            'email',
            'deleted',
            'confirmed',
            'suspended',
            'timecreated',
            'timemodified',
        ];

        $sql = 'SELECT ' . implode(', ', $userfields) . '
                  FROM {user}
                 WHERE deleted = 0
                   AND confirmed = 1
              ORDER BY id ASC';

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $user) {
            $totalusers++;

            try {
                $result = $this->rebuild_profile_for_user($user);

                if ($result === 'created') {
                    $created++;
                } else if ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                $failed++;
                mtrace('Failed rebuilding UCKK profile for user id ' . $user->id . ': ' . $exception->getMessage());
            }

            if ($totalusers % self::BATCH_SIZE === 0) {
                mtrace(
                    'Processed ' . $totalusers
                    . ' users. Created: ' . $created
                    . '. Updated: ' . $updated
                    . '. Skipped: ' . $skipped
                    . '. Failed: ' . $failed
                    . '.'
                );
            }
        }

        $rs->close();

        $duration = time() - $started;

        mtrace(
            'Finished UCKK player profile rebuild. '
            . 'Users: ' . $totalusers
            . '. Created: ' . $created
            . '. Updated: ' . $updated
            . '. Skipped: ' . $skipped
            . '. Failed: ' . $failed
            . '. Duration: ' . $duration . ' seconds.'
        );
    }

    /**
     * Rebuild the profile summary for a single user.
     *
     * @param stdClass $user Moodle user record.
     * @return string created|updated|skipped
     */
    private function rebuild_profile_for_user(stdClass $user): string {
        global $DB;

        if (empty($user->id)) {
            return 'skipped';
        }

        $userid = (int)$user->id;
        $existing = $DB->get_record(self::PLAYER_TABLE, ['userid' => $userid], '*', IGNORE_MISSING);

        $now = time();

        $record = new stdClass();
        $record->userid = $userid;
        $record->displaytitle = $this->build_display_title($user, $existing);
        $record->symbolicroles = local_uckk_json_encode($this->build_symbolic_roles($userid, $existing));
        $record->activepathways = local_uckk_json_encode($this->build_active_pathways($userid, $existing));
        $record->portfolioarchiveid = $this->build_portfolio_archive_id($userid, $existing);
        $record->badgesummary = local_uckk_json_encode($this->build_badge_summary($userid));
        $record->competencysummary = local_uckk_json_encode($this->build_competency_summary($userid));
        $record->archivesummary = local_uckk_json_encode($this->build_archive_summary($userid));
        $record->integrityflags = local_uckk_json_encode($this->build_integrity_flags($userid));
        $record->visibility = $this->preserve_visibility($existing);
        $record->status = $this->build_status($user, $existing);
        $record->metadata = local_uckk_json_encode($this->build_metadata($user, $existing));
        $record->timemodified = $now;

        if ($existing) {
            $record->id = (int)$existing->id;

            if (property_exists($existing, 'timecreated')) {
                $record->timecreated = (int)$existing->timecreated;
            }

            $record = $this->filter_record_to_table_columns(self::PLAYER_TABLE, $record);
            $DB->update_record(self::PLAYER_TABLE, $record);

            return 'updated';
        }

        $record->timecreated = $now;
        $record = $this->filter_record_to_table_columns(self::PLAYER_TABLE, $record);
        $DB->insert_record(self::PLAYER_TABLE, $record);

        return 'created';
    }

    /**
     * Build the UCKK display title.
     *
     * @param stdClass $user Moodle user.
     * @param stdClass|false $existing Existing profile.
     * @return string
     */
    private function build_display_title(stdClass $user, $existing): string {
        if ($existing && !empty($existing->displaytitle)) {
            return clean_param((string)$existing->displaytitle, PARAM_TEXT);
        }

        return 'Joueur';
    }

    /**
     * Build symbolic UCKK roles for the user.
     *
     * Symbolic roles are not Moodle technical roles. They are a UCKK semantic
     * layer used for display and orientation.
     *
     * @param int $userid User id.
     * @param stdClass|false $existing Existing profile.
     * @return string[]
     */
    private function build_symbolic_roles(int $userid, $existing): array {
        $roles = [];

        if ($existing && !empty($existing->symbolicroles)) {
            $roles = array_merge($roles, local_uckk_json_decode_array($existing->symbolicroles));
        }

        $roles[] = 'joueur';

        foreach ($this->get_moodle_role_shortnames($userid) as $shortname) {
            switch ($shortname) {
                case 'uckkplayer':
                    $roles[] = 'joueur';
                    break;

                case 'uckkmentor':
                    $roles[] = 'joueur_lucide';
                    break;

                case 'uckkarchivist':
                    $roles[] = 'archiviste';
                    break;

                case 'uckkinquisitor':
                    $roles[] = 'inquisiteur';
                    break;

                case 'editingteacher':
                case 'teacher':
                    $roles[] = 'batisseur';
                    break;

                case 'manager':
                    $roles[] = 'batisseur';
                    break;
            }
        }

        if ($this->user_has_archival_activity($userid)) {
            $roles[] = 'archiviste';
        }

        if ($this->user_has_verified_competencies($userid)) {
            $roles[] = 'joueur_lucide';
        }

        $roles = array_map([$this, 'normalise_key'], $roles);
        $roles = array_filter($roles);
        $roles = array_unique($roles);

        $allowed = local_uckk_get_symbolic_roles();
        $roles = array_values(array_filter($roles, static function(string $role) use ($allowed): bool {
            return in_array($role, $allowed, true);
        }));

        if (!in_array('joueur', $roles, true)) {
            array_unshift($roles, 'joueur');
        }

        return $roles;
    }

    /**
     * Build active pathway references for the user.
     *
     * @param int $userid User id.
     * @param stdClass|false $existing Existing profile.
     * @return string[]
     */
    private function build_active_pathways(int $userid, $existing): array {
        global $DB;

        $pathways = [];

        if ($existing && !empty($existing->activepathways)) {
            $pathways = array_merge($pathways, local_uckk_json_decode_array($existing->activepathways));
        }

        if ($DB->get_manager()->table_exists('local_uckk_pathway')
            && $this->table_has_columns('local_uckk_pathway', ['userid'])) {
            $records = $DB->get_records(
                'local_uckk_pathway',
                ['userid' => $userid],
                'id ASC',
                '*'
            );

            foreach ($records as $record) {
                if (isset($record->status)
                    && !in_array((string)$record->status, ['active', 'pending', 'validated'], true)) {
                    continue;
                }

                if (!empty($record->pathwaykey)) {
                    $pathways[] = (string)$record->pathwaykey;
                } else if (!empty($record->shortname)) {
                    $pathways[] = (string)$record->shortname;
                } else {
                    $pathways[] = 'pathway_' . (int)$record->id;
                }
            }
        }

        $pathways = array_map([$this, 'normalise_key'], $pathways);
        $pathways = array_filter($pathways);

        return array_values(array_unique($pathways));
    }

    /**
     * Preserve or infer portfolio archive id.
     *
     * @param int $userid User id.
     * @param stdClass|false $existing Existing profile.
     * @return int|null
     */
    private function build_portfolio_archive_id(int $userid, $existing): ?int {
        global $DB;

        if ($existing && isset($existing->portfolioarchiveid) && (int)$existing->portfolioarchiveid > 0) {
            return (int)$existing->portfolioarchiveid;
        }

        if ($DB->get_manager()->table_exists('uckkarchive_item')
            && $this->table_has_columns('uckkarchive_item', ['userid', 'itemtype'])) {
            $record = $DB->get_record(
                'uckkarchive_item',
                [
                    'userid' => $userid,
                    'itemtype' => 'portfolio',
                ],
                'id',
                IGNORE_MISSING
            );

            if ($record) {
                return (int)$record->id;
            }
        }

        return null;
    }

    /**
     * Build Moodle/UCKK badge summary.
     *
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private function build_badge_summary(int $userid): array {
        global $DB;

        $summary = [
            'total' => 0,
            'uckk' => 0,
            'recent' => [],
            'lastissued' => 0,
        ];

        if (!$DB->get_manager()->table_exists('badge_issued')
            || !$DB->get_manager()->table_exists('badge')) {
            return $summary;
        }

        $sql = "SELECT bi.id,
                       bi.dateissued,
                       b.name,
                       b.idnumber
                  FROM {badge_issued} bi
                  JOIN {badge} b ON b.id = bi.badgeid
                 WHERE bi.userid = :userid
              ORDER BY bi.dateissued DESC, bi.id DESC";

        $records = $DB->get_records_sql($sql, ['userid' => $userid], 0, 10);

        foreach ($records as $record) {
            $summary['total']++;

            $idnumber = (string)($record->idnumber ?? '');
            if (stripos($idnumber, 'UCKK') === 0 || stripos((string)$record->name, 'UCKK') !== false) {
                $summary['uckk']++;
            }

            if (count($summary['recent']) < 5) {
                $summary['recent'][] = [
                    'name' => format_string((string)$record->name),
                    'idnumber' => $idnumber,
                    'dateissued' => (int)$record->dateissued,
                ];
            }

            if ((int)$record->dateissued > (int)$summary['lastissued']) {
                $summary['lastissued'] = (int)$record->dateissued;
            }
        }

        if (!empty($records)) {
            $summary['total'] = $DB->count_records('badge_issued', ['userid' => $userid]);
        }

        return $summary;
    }

    /**
     * Build competency summary.
     *
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private function build_competency_summary(int $userid): array {
        global $DB;

        $summary = [
            'total' => 0,
            'proficient' => 0,
            'uckk' => 0,
            'uckkproficient' => 0,
            'recent' => [],
        ];

        if (!$DB->get_manager()->table_exists('competency_usercomp')) {
            return $summary;
        }

        $hascompetency = $DB->get_manager()->table_exists('competency');

        if ($hascompetency) {
            $sql = "SELECT uc.id,
                           uc.competencyid,
                           uc.proficiency,
                           uc.grade,
                           uc.timemodified,
                           c.idnumber,
                           c.shortname
                      FROM {competency_usercomp} uc
                      JOIN {competency} c ON c.id = uc.competencyid
                     WHERE uc.userid = :userid
                  ORDER BY uc.timemodified DESC, uc.id DESC";
        } else {
            $sql = "SELECT uc.id,
                           uc.competencyid,
                           uc.proficiency,
                           uc.grade,
                           uc.timemodified
                      FROM {competency_usercomp} uc
                     WHERE uc.userid = :userid
                  ORDER BY uc.timemodified DESC, uc.id DESC";
        }

        $records = $DB->get_records_sql($sql, ['userid' => $userid], 0, 50);

        foreach ($records as $record) {
            $summary['total']++;

            if (!empty($record->proficiency)) {
                $summary['proficient']++;
            }

            $idnumber = (string)($record->idnumber ?? '');
            $isuckk = stripos($idnumber, 'UCKK-COMP-') === 0;

            if ($isuckk) {
                $summary['uckk']++;

                if (!empty($record->proficiency)) {
                    $summary['uckkproficient']++;
                }
            }

            if (count($summary['recent']) < 8) {
                $summary['recent'][] = [
                    'idnumber' => $idnumber,
                    'shortname' => isset($record->shortname) ? format_string((string)$record->shortname) : '',
                    'proficiency' => !empty($record->proficiency),
                    'grade' => isset($record->grade) ? (int)$record->grade : null,
                    'timemodified' => isset($record->timemodified) ? (int)$record->timemodified : 0,
                ];
            }
        }

        return $summary;
    }

    /**
     * Build archive summary.
     *
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private function build_archive_summary(int $userid): array {
        global $DB;

        $summary = [
            'items' => 0,
            'proofs' => 0,
            'kristals' => 0,
            'exports' => 0,
            'lastactivity' => 0,
        ];

        if ($DB->get_manager()->table_exists('uckkarchive_item')
            && $this->table_has_columns('uckkarchive_item', ['userid'])) {
            $summary['items'] = $DB->count_records('uckkarchive_item', ['userid' => $userid]);
            $summary['lastactivity'] = max(
                $summary['lastactivity'],
                $this->get_table_last_modified_for_user('uckkarchive_item', $userid)
            );
        }

        if ($DB->get_manager()->table_exists('uckkarchive_proof')
            && $this->table_has_columns('uckkarchive_proof', ['userid'])) {
            $summary['proofs'] = $DB->count_records('uckkarchive_proof', ['userid' => $userid]);
            $summary['lastactivity'] = max(
                $summary['lastactivity'],
                $this->get_table_last_modified_for_user('uckkarchive_proof', $userid)
            );
        }

        if ($DB->get_manager()->table_exists('uckkarchive_kristal')
            && $this->table_has_columns('uckkarchive_kristal', ['userid'])) {
            $summary['kristals'] = $DB->count_records('uckkarchive_kristal', ['userid' => $userid]);
            $summary['lastactivity'] = max(
                $summary['lastactivity'],
                $this->get_table_last_modified_for_user('uckkarchive_kristal', $userid)
            );
        }

        if ($DB->get_manager()->table_exists('uckkarchive_export')
            && $this->table_has_columns('uckkarchive_export', ['userid'])) {
            $summary['exports'] = $DB->count_records('uckkarchive_export', ['userid' => $userid]);
            $summary['lastactivity'] = max(
                $summary['lastactivity'],
                $this->get_table_last_modified_for_user('uckkarchive_export', $userid)
            );
        }

        return $summary;
    }

    /**
     * Build integrity flags.
     *
     * This summary must not expose private case details. It stores counts and
     * high-level status only.
     *
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private function build_integrity_flags(int $userid): array {
        global $DB;

        $flags = [
            'open_cases' => 0,
            'blocking' => false,
            'correction_required' => false,
            'lastactivity' => 0,
        ];

        if (!$DB->get_manager()->table_exists('tool_uckki_case')
            || !$this->table_has_columns('tool_uckki_case', ['userid'])) {
            return $flags;
        }

        $records = $DB->get_records('tool_uckki_case', ['userid' => $userid]);

        foreach ($records as $record) {
            $status = $this->normalise_key((string)($record->status ?? ''));

            if (!in_array($status, ['closed', 'archived', 'cancelled'], true)) {
                $flags['open_cases']++;
            }

            if (in_array($status, ['correction_required', 'invalidated'], true)) {
                $flags['correction_required'] = true;
            }

            if (!empty($record->blocking)) {
                $flags['blocking'] = true;
            }

            $flags['lastactivity'] = max(
                $flags['lastactivity'],
                (int)($record->timemodified ?? $record->timecreated ?? 0)
            );
        }

        return $flags;
    }

    /**
     * Preserve existing visibility, or default to private.
     *
     * @param stdClass|false $existing Existing profile.
     * @return string
     */
    private function preserve_visibility($existing): string {
        if ($existing && !empty($existing->visibility)) {
            return local_uckk_normalise_visibility((string)$existing->visibility, 'private');
        }

        return 'private';
    }

    /**
     * Build player profile status.
     *
     * @param stdClass $user Moodle user.
     * @param stdClass|false $existing Existing profile.
     * @return string
     */
    private function build_status(stdClass $user, $existing): string {
        if (!empty($user->suspended)) {
            return 'suspended';
        }

        if ($existing && !empty($existing->status)) {
            $status = $this->normalise_key((string)$existing->status);

            if (in_array($status, ['draft', 'active', 'pending', 'validated', 'closed', 'archived'], true)) {
                return $status;
            }
        }

        return 'active';
    }

    /**
     * Build metadata.
     *
     * @param stdClass $user Moodle user.
     * @param stdClass|false $existing Existing profile.
     * @return array<string, mixed>
     */
    private function build_metadata(stdClass $user, $existing): array {
        $metadata = [];

        if ($existing && !empty($existing->metadata)) {
            $metadata = local_uckk_json_decode_array($existing->metadata);
        }

        $metadata['rebuiltby'] = self::class;
        $metadata['rebuilttime'] = time();
        $metadata['moodle_user_timemodified'] = (int)($user->timemodified ?? 0);
        $metadata['moodle_user_timecreated'] = (int)($user->timecreated ?? 0);
        $metadata['non_sovereign_summary'] = true;
        $metadata['privacy_note'] = 'Derived summary only. Source data remains owned by source components.';

        return $metadata;
    }

    /**
     * Return Moodle role shortnames assigned to a user.
     *
     * @param int $userid User id.
     * @return string[]
     */
    private function get_moodle_role_shortnames(int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT r.shortname
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $userid]);
        $roles = [];

        foreach ($records as $record) {
            if (!empty($record->shortname)) {
                $roles[] = (string)$record->shortname;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Determine whether a user has archival activity.
     *
     * @param int $userid User id.
     * @return bool
     */
    private function user_has_archival_activity(int $userid): bool {
        global $DB;

        if ($DB->get_manager()->table_exists('uckkarchive_item')
            && $this->table_has_columns('uckkarchive_item', ['userid'])
            && $DB->record_exists('uckkarchive_item', ['userid' => $userid])) {
            return true;
        }

        if ($DB->get_manager()->table_exists('uckkarchive_kristal')
            && $this->table_has_columns('uckkarchive_kristal', ['userid'])
            && $DB->record_exists('uckkarchive_kristal', ['userid' => $userid])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user has verified UCKK competencies.
     *
     * @param int $userid User id.
     * @return bool
     */
    private function user_has_verified_competencies(int $userid): bool {
        global $DB;

        if (!$DB->get_manager()->table_exists('competency_usercomp')
            || !$DB->get_manager()->table_exists('competency')) {
            return false;
        }

        $sql = "SELECT 1
                  FROM {competency_usercomp} uc
                  JOIN {competency} c ON c.id = uc.competencyid
                 WHERE uc.userid = :userid
                   AND uc.proficiency = 1
                   AND " . $DB->sql_like('c.idnumber', ':prefix', false, false) . "";

        return $DB->record_exists_sql($sql, [
            'userid' => $userid,
            'prefix' => 'UCKK-COMP-%',
        ]);
    }

    /**
     * Get the latest modification time for a user-owned table.
     *
     * @param string $table Table name.
     * @param int $userid User id.
     * @return int
     */
    private function get_table_last_modified_for_user(string $table, int $userid): int {
        global $DB;

        if (!$DB->get_manager()->table_exists($table)
            || !$this->table_has_columns($table, ['userid'])) {
            return 0;
        }

        $timefield = null;

        if ($this->table_has_columns($table, ['timemodified'])) {
            $timefield = 'timemodified';
        } else if ($this->table_has_columns($table, ['timecreated'])) {
            $timefield = 'timecreated';
        }

        if ($timefield === null) {
            return 0;
        }

        $sql = "SELECT MAX({$timefield})
                  FROM {{$table}}
                 WHERE userid = :userid";

        return (int)$DB->get_field_sql($sql, ['userid' => $userid]);
    }

    /**
     * Check whether a table has all listed columns.
     *
     * @param string $table Table name.
     * @param string[] $requiredcolumns Required column names.
     * @return bool
     */
    private function table_has_columns(string $table, array $requiredcolumns): bool {
        global $DB;

        if (!$DB->get_manager()->table_exists($table)) {
            return false;
        }

        $columns = $DB->get_columns($table);

        foreach ($requiredcolumns as $column) {
            if (!array_key_exists($column, $columns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter record fields to existing table columns.
     *
     * This makes the task safer during early schema evolution.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     * @return stdClass
     */
    private function filter_record_to_table_columns(string $table, stdClass $record): stdClass {
        global $DB;

        $columns = $DB->get_columns($table);
        $filtered = new stdClass();

        foreach ($record as $field => $value) {
            if (array_key_exists($field, $columns)) {
                $filtered->{$field} = $value;
            }
        }

        return $filtered;
    }

    /**
     * Normalise a key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = trim(\core_text::strtolower($value));
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim((string)$value, '_');
    }
}

