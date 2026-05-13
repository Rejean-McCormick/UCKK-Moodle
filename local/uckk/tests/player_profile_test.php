<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace local_uckk;

use context_system;
use local_uckk\event\profile_updated;
use local_uckk\local\constants;
use local_uckk\service\profile_service;
use required_capability_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for UCKK player profile handling.
 *
 * These tests define the expected service contract for local_uckk player
 * profiles. The profile service owns UCKK symbolic profile data, while Moodle
 * roles remain permission groups only.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class player_profile_test extends \advanced_testcase {
    /**
     * Profile service under test.
     *
     * @var profile_service
     */
    private profile_service $profileservice;

    /**
     * Prepare each test with a clean database state.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->profileservice = new profile_service();
    }

    /**
     * A player profile can be created with canonical status, visibility,
     * provenance and symbolic-role values.
     */
    public function test_create_player_profile_sets_canonical_defaults(): void {
        global $DB;

        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Ada',
            'lastname' => 'Joueuse',
        ]);

        $this->setUser($admin);

        $profile = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => 'Ada Joueuse',
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
            'metadata' => [
                'portfoliointro' => 'Entrée initiale dans le parcours UCKK.',
            ],
        ]);

        $this->assertNotEmpty($profile->id);
        $this->assertSame((int) $user->id, (int) $profile->userid);
        $this->assertSame('Ada Joueuse', $profile->displayname);
        $this->assertSame(constants::SYMBOLIC_ROLE_JOUEUR, $profile->symbolicrole);
        $this->assertSame(constants::STATUS_ACTIVE, $profile->status);
        $this->assertSame(constants::VISIBILITY_USER, $profile->visibility);
        $this->assertSame(constants::PROVENANCE_HUMAN, $profile->provenance);
        $this->assertSame(1, (int) $profile->versionno);
        $this->assertSame((int) $admin->id, (int) $profile->createdby);
        $this->assertSame((int) $admin->id, (int) $profile->modifiedby);

        $this->assertTrue($DB->record_exists('local_uckk_player', [
            'id' => $profile->id,
            'userid' => $user->id,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
        ]));
    }

    /**
     * Updating a player profile increments the version and emits the canonical
     * local_uckk profile_updated event.
     */
    public function test_update_player_profile_increments_version_and_emits_event(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $profile = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $eventsink = $this->redirectEvents();

        $updated = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => 'Joueuse lucide',
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR_LUCIDE,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $events = $eventsink->get_events();
        $eventsink->close();

        $this->assertSame((int) $profile->id, (int) $updated->id);
        $this->assertSame('Joueuse lucide', $updated->displayname);
        $this->assertSame(constants::SYMBOLIC_ROLE_JOUEUR_LUCIDE, $updated->symbolicrole);
        $this->assertSame(2, (int) $updated->versionno);

        $matchingevents = array_filter($events, static function (\core\event\base $event): bool {
            return $event instanceof profile_updated;
        });

        $this->assertCount(1, $matchingevents);

        /** @var profile_updated $event */
        $event = reset($matchingevents);
        $this->assertSame((int) $user->id, (int) $event->relateduserid);
        $this->assertSame((int) $updated->id, (int) $event->objectid);
        $this->assertSame('local_uckk_player', $event->objecttable);
    }

    /**
     * Symbolic UCKK roles are profile distinctions. They must not be silently
     * created as Moodle technical roles.
     */
    public function test_symbolic_profile_role_does_not_create_moodle_role(): void {
        global $DB;

        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $profile = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_BATISSEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->assertSame(constants::SYMBOLIC_ROLE_BATISSEUR, $profile->symbolicrole);

        $this->assertFalse($DB->record_exists('role', [
            'shortname' => constants::SYMBOLIC_ROLE_BATISSEUR,
        ]));
    }

    /**
     * A player can view their own non-restricted profile.
     */
    public function test_user_can_view_own_profile(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $created = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->setUser($user);

        $visible = $this->profileservice->get_profile_for_viewer(
            (int) $user->id,
            (int) $user->id
        );

        $this->assertSame((int) $created->id, (int) $visible->id);
        $this->assertSame((int) $user->id, (int) $visible->userid);
        $this->assertSame(constants::VISIBILITY_USER, $visible->visibility);
    }

    /**
     * Restricted profiles require local/uckk:viewrestricted.
     */
    public function test_restricted_profile_requires_explicit_capability(): void {
        $admin = get_admin();
        $player = $this->getDataGenerator()->create_user();
        $viewer = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => fullname($player),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_RESTRICTED,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->setUser($viewer);

        $this->expectException(required_capability_exception::class);

        $this->profileservice->get_profile_for_viewer(
            (int) $player->id,
            (int) $viewer->id
        );
    }

    /**
     * A user with local/uckk:viewrestricted can view a restricted profile.
     */
    public function test_restricted_profile_can_be_viewed_with_capability(): void {
        $admin = get_admin();
        $player = $this->getDataGenerator()->create_user();
        $viewer = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $created = $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => fullname($player),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_RESTRICTED,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->assign_capability_to_user((int) $viewer->id, 'local/uckk:viewrestricted');

        $this->setUser($viewer);

        $visible = $this->profileservice->get_profile_for_viewer(
            (int) $player->id,
            (int) $viewer->id
        );

        $this->assertSame((int) $created->id, (int) $visible->id);
        $this->assertSame(constants::VISIBILITY_RESTRICTED, $visible->visibility);
    }

    /**
     * A viewer without local/uckk:manageprofiles cannot update another user's profile.
     */
    public function test_profile_update_requires_manageprofiles_capability(): void {
        $admin = get_admin();
        $player = $this->getDataGenerator()->create_user();
        $viewer = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => fullname($player),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->setUser($viewer);

        $this->expectException(required_capability_exception::class);

        $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => 'Unauthorized change',
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR_LUCIDE,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * A user with local/uckk:manageprofiles can update another user's profile.
     */
    public function test_profile_update_is_allowed_with_manageprofiles_capability(): void {
        $admin = get_admin();
        $player = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $created = $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => fullname($player),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->assign_capability_to_user((int) $manager->id, 'local/uckk:manageprofiles');

        $this->setUser($manager);

        $updated = $this->profileservice->create_or_update_profile((int) $player->id, [
            'displayname' => 'Managed UCKK player',
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR_LUCIDE,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);

        $this->assertSame((int) $created->id, (int) $updated->id);
        $this->assertSame('Managed UCKK player', $updated->displayname);
        $this->assertSame(constants::SYMBOLIC_ROLE_JOUEUR_LUCIDE, $updated->symbolicrole);
        $this->assertSame((int) $manager->id, (int) $updated->modifiedby);
    }

    /**
     * Profile metadata is stored as JSON and returned as decoded structured data.
     */
    public function test_profile_metadata_round_trips_as_structured_data(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $profile = $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
            'metadata' => [
                'portfoliosections' => [
                    'system_map',
                    'ai_collaboration_journal',
                    'ethics_reflection',
                    'archive_links',
                ],
                'publictitle' => 'Joueur UCKK',
            ],
        ]);

        $reloaded = $this->profileservice->get_profile((int) $profile->userid);

        $this->assertIsArray($reloaded->metadata);
        $this->assertSame('Joueur UCKK', $reloaded->metadata['publictitle']);
        $this->assertContains('system_map', $reloaded->metadata['portfoliosections']);
        $this->assertContains('archive_links', $reloaded->metadata['portfoliosections']);
    }

    /**
     * Invalid symbolic roles are rejected rather than stored silently.
     */
    public function test_invalid_symbolic_role_is_rejected(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $this->expectException(\invalid_parameter_exception::class);

        $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => 'siteadministrator',
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Invalid visibility values are rejected.
     */
    public function test_invalid_visibility_is_rejected(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $this->expectException(\invalid_parameter_exception::class);

        $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => 'everyone_everywhere',
            'provenance' => constants::PROVENANCE_HUMAN,
        ]);
    }

    /**
     * Invalid provenance values are rejected.
     */
    public function test_invalid_provenance_is_rejected(): void {
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($admin);

        $this->expectException(\invalid_parameter_exception::class);

        $this->profileservice->create_or_update_profile((int) $user->id, [
            'displayname' => fullname($user),
            'symbolicrole' => constants::SYMBOLIC_ROLE_JOUEUR,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'provenance' => 'anonymous_unverified_magic',
        ]);
    }

    /**
     * Assign a capability to a user at system context for testing.
     *
     * @param int $userid User id.
     * @param string $capability Capability name.
     */
    private function assign_capability_to_user(int $userid, string $capability): void {
        $systemcontext = context_system::instance();

        $shortname = 'uckktest' . substr(md5($capability . ':' . $userid), 0, 12);
        $roleid = create_role('UCKK test role', $shortname, 'Temporary UCKK PHPUnit role.');

        assign_capability(
            $capability,
            CAP_ALLOW,
            $roleid,
            $systemcontext->id,
            true
        );

        role_assign($roleid, $userid, $systemcontext->id);
        accesslib_clear_all_caches_for_unit_testing();
    }
}