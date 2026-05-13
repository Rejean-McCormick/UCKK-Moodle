<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for the UCKK pathway API.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use context_coursecat;
use context_system;
use local_uckk\event\pathway_assigned;
use local_uckk\local\constants;
use local_uckk\service\pathway_service;
use local_uckk\service\program_service;
use local_uckk\service\profile_service;
use required_capability_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the UCKK pathway service contract.
 *
 * These tests intentionally exercise the public service layer rather than
 * directly mutating implementation details. The storage model may evolve, but
 * the API contract must remain stable for dashboards, reports, seed tooling,
 * and privacy exports.
 *
 * @covers \local_uckk\service\pathway_service
 * @covers \local_uckk\service\program_service
 * @covers \local_uckk\service\profile_service
 */
final class pathway_api_test extends advanced_testcase {
    /**
     * System context used by setup helpers.
     *
     * @var \context_system
     */
    private \context_system $systemcontext;

    /**
     * Course category context used for program/pathway tests.
     *
     * @var \context_coursecat
     */
    private \context_coursecat $categorycontext;

    /**
     * Course category linked to the test UCKK program.
     *
     * @var stdClass
     */
    private stdClass $category;

    /**
     * Prepare common Moodle context fixtures.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);

        $this->systemcontext = context_system::instance();

        $this->category = $this->getDataGenerator()->create_category([
            'name' => 'UCKK - Tronc commun obligatoire',
            'idnumber' => 'uckk_test_tronc_commun',
        ]);

        $this->categorycontext = context_coursecat::instance($this->category->id);
    }

    /**
     * The service must create a pathway with canonical UCKK values.
     */
    public function test_create_pathway_returns_canonical_record(): void {
        $this->setAdminUser();

        $program = $this->create_program();
        $courses = $this->create_required_courses();

        $service = new pathway_service();

        $pathway = $service->create_pathway([
            'programid' => $program->id,
            'shortname' => 'tronc_commun_initial',
            'fullname' => 'Parcours initial du tronc commun',
            'requiredcourseids' => array_column($courses, 'id'),
            'requiredbadges' => [
                constants::BADGE_JOUEUR_INITIE,
            ],
            'requiredcompetencies' => [
                constants::COMP_READ_GAME,
                constants::COMP_KNOW_CHOOSE_ACT_REMEMBER,
            ],
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_PROGRAM,
            'contextid' => $this->categorycontext->id,
        ]);

        $this->assertNotEmpty($pathway->id);
        $this->assertSame($program->id, (int) $pathway->programid);
        $this->assertSame('tronc_commun_initial', $pathway->shortname);
        $this->assertSame('Parcours initial du tronc commun', $pathway->fullname);
        $this->assertSame(constants::STATUS_ACTIVE, $pathway->status);
        $this->assertSame(constants::VISIBILITY_PROGRAM, $pathway->visibility);
        $this->assertSame($this->categorycontext->id, (int) $pathway->contextid);

        $this->assertSame(
            array_column($courses, 'id'),
            array_map('intval', $pathway->requiredcourseids)
        );

        $this->assertContains(constants::BADGE_JOUEUR_INITIE, $pathway->requiredbadges);
        $this->assertContains(constants::COMP_READ_GAME, $pathway->requiredcompetencies);
        $this->assertContains(constants::COMP_KNOW_CHOOSE_ACT_REMEMBER, $pathway->requiredcompetencies);
    }

    /**
     * A pathway assignment must create or update the Joueur profile state.
     */
    public function test_assign_pathway_adds_pathway_to_player_profile(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $profileservice = new profile_service();
        $pathwayservice = new pathway_service();

        $profilebefore = $profileservice->get_or_create_profile($player->id);
        $this->assertEmpty($profilebefore->activepathwayids);

        $assignment = $pathwayservice->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $this->assertNotEmpty($assignment->id);
        $this->assertSame($player->id, (int) $assignment->userid);
        $this->assertSame($pathway->id, (int) $assignment->pathwayid);
        $this->assertSame(constants::PROGRESS_NOT_STARTED, $assignment->progress);
        $this->assertSame(constants::STATUS_ACTIVE, $assignment->status);
        $this->assertSame(constants::VISIBILITY_USER, $assignment->visibility);

        $profileafter = $profileservice->get_profile($player->id);
        $this->assertContains($pathway->id, array_map('intval', $profileafter->activepathwayids));
    }

    /**
     * Assigning the same active pathway twice to the same user must be idempotent.
     */
    public function test_assign_pathway_is_idempotent_for_same_user_and_pathway(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $service = new pathway_service();

        $first = $service->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $second = $service->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $this->assertSame((int) $first->id, (int) $second->id);
        $this->assertSame((int) $first->userid, (int) $second->userid);
        $this->assertSame((int) $first->pathwayid, (int) $second->pathwayid);

        $assigned = $service->get_user_pathways($player->id);
        $matching = array_filter($assigned, static function(stdClass $record) use ($pathway): bool {
            return (int) $record->pathwayid === (int) $pathway->id;
        });

        $this->assertCount(1, $matching);
    }

    /**
     * Pathway assignment must require pathway-management permission.
     */
    public function test_assign_pathway_requires_managepathways_capability(): void {
        $this->setAdminUser();

        $manager = $this->getDataGenerator()->create_user();
        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $this->setUser($manager);

        $this->expectException(required_capability_exception::class);

        (new pathway_service())->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: $manager->id
        );
    }

    /**
     * Pathway assignment must work when the current user has the correct capability.
     */
    public function test_assign_pathway_allows_user_with_managepathways_capability(): void {
        $this->setAdminUser();

        $manager = $this->getDataGenerator()->create_user();
        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $this->assignUserCapability(
            'local/uckk:managepathways',
            $this->categorycontext->id,
            0,
            $manager->id
        );

        $this->setUser($manager);

        $assignment = (new pathway_service())->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: $manager->id
        );

        $this->assertNotEmpty($assignment->id);
        $this->assertSame($player->id, (int) $assignment->userid);
        $this->assertSame($pathway->id, (int) $assignment->pathwayid);
    }

    /**
     * Pathway assignment must emit the canonical pathway_assigned event.
     */
    public function test_assign_pathway_emits_pathway_assigned_event(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $sink = $this->redirectEvents();

        $assignment = (new pathway_service())->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $events = $sink->get_events();
        $sink->close();

        $matchedevents = array_filter($events, static function($event): bool {
            return $event instanceof pathway_assigned;
        });

        $this->assertCount(1, $matchedevents);

        /** @var pathway_assigned $event */
        $event = reset($matchedevents);

        $this->assertSame($this->categorycontext->id, $event->contextid);
        $this->assertSame('local_uckk_pathway', $event->objecttable);
        $this->assertSame((int) $pathway->id, (int) $event->objectid);
        $this->assertSame((int) $player->id, (int) $event->relateduserid);
        $this->assertSame((int) $assignment->id, (int) $event->other['assignmentid']);
    }

    /**
     * Progress updates must preserve canonical progress states.
     */
    public function test_set_pathway_progress_uses_canonical_progress_states(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $service = new pathway_service();

        $service->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $updated = $service->set_pathway_progress(
            userid: $player->id,
            pathwayid: $pathway->id,
            progress: constants::PROGRESS_IN_PROGRESS,
            modifiedby: get_admin()->id
        );

        $this->assertSame(constants::PROGRESS_IN_PROGRESS, $updated->progress);

        $state = $service->get_user_pathway_state($player->id, $pathway->id);
        $this->assertSame(constants::PROGRESS_IN_PROGRESS, $state->progress);
    }

    /**
     * Invalid progress states must be rejected.
     */
    public function test_set_pathway_progress_rejects_non_canonical_state(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $service = new pathway_service();

        $service->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $this->expectException(\coding_exception::class);

        $service->set_pathway_progress(
            userid: $player->id,
            pathwayid: $pathway->id,
            progress: 'almost_done_but_not_canonical',
            modifiedby: get_admin()->id
        );
    }

    /**
     * Symbolic titles must not create Moodle role assignments.
     */
    public function test_pathway_assignment_does_not_create_symbolic_moodle_roles(): void {
        global $DB;

        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();
        $pathway = $this->create_pathway($program);

        $rolecountbefore = $DB->count_records('role_assignments', [
            'userid' => $player->id,
        ]);

        (new profile_service())->update_symbolic_roles($player->id, [
            constants::SYMBOLIC_ROLE_JOUEUR,
            constants::SYMBOLIC_ROLE_BATISSEUR,
            constants::SYMBOLIC_ROLE_CARTOGRAPHE,
        ]);

        (new pathway_service())->assign_pathway(
            userid: $player->id,
            pathwayid: $pathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $rolecountafter = $DB->count_records('role_assignments', [
            'userid' => $player->id,
        ]);

        $this->assertSame($rolecountbefore, $rolecountafter);
    }

    /**
     * A hidden pathway must not be returned to a normal player.
     */
    public function test_hidden_pathway_is_not_returned_to_player_without_restricted_access(): void {
        $this->setAdminUser();

        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();

        $activepathway = $this->create_pathway($program, [
            'shortname' => 'visible_pathway',
            'fullname' => 'Visible pathway',
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_PROGRAM,
        ]);

        $hiddenpathway = $this->create_pathway($program, [
            'shortname' => 'hidden_pathway',
            'fullname' => 'Hidden pathway',
            'status' => constants::STATUS_HIDDEN,
            'visibility' => constants::VISIBILITY_HIDDEN,
        ]);

        $service = new pathway_service();

        $service->assign_pathway(
            userid: $player->id,
            pathwayid: $activepathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $service->assign_pathway(
            userid: $player->id,
            pathwayid: $hiddenpathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $this->setUser($player);

        $pathways = $service->get_user_pathways($player->id);
        $shortnames = array_map(static function(stdClass $record): string {
            return $record->shortname;
        }, $pathways);

        $this->assertContains('visible_pathway', $shortnames);
        $this->assertNotContains('hidden_pathway', $shortnames);
    }

    /**
     * A privileged viewer may retrieve hidden pathway assignments.
     */
    public function test_hidden_pathway_is_returned_to_viewer_with_restricted_access(): void {
        $this->setAdminUser();

        $manager = $this->getDataGenerator()->create_user();
        $player = $this->getDataGenerator()->create_user();
        $program = $this->create_program();

        $hiddenpathway = $this->create_pathway($program, [
            'shortname' => 'restricted_pathway',
            'fullname' => 'Restricted pathway',
            'status' => constants::STATUS_HIDDEN,
            'visibility' => constants::VISIBILITY_HIDDEN,
        ]);

        $service = new pathway_service();

        $service->assign_pathway(
            userid: $player->id,
            pathwayid: $hiddenpathway->id,
            contextid: $this->categorycontext->id,
            assignedby: get_admin()->id
        );

        $this->assignUserCapability(
            'local/uckk:viewrestricted',
            $this->categorycontext->id,
            0,
            $manager->id
        );

        $this->setUser($manager);

        $pathways = $service->get_user_pathways($player->id, includehidden: true);
        $shortnames = array_map(static function(stdClass $record): string {
            return $record->shortname;
        }, $pathways);

        $this->assertContains('restricted_pathway', $shortnames);
    }

    /**
     * Create a canonical active program.
     *
     * @return stdClass
     */
    private function create_program(): stdClass {
        return (new program_service())->create_program([
            'shortname' => constants::PROGRAM_TC,
            'fullname' => 'Tronc commun obligatoire',
            'programtype' => constants::TYPE_TRONC_COMMUN,
            'categoryid' => $this->category->id,
            'description' => 'Programme canonique de base pour les Joueurs UCKK.',
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_PROGRAM,
            'contextid' => $this->categorycontext->id,
            'sortorder' => 10,
        ]);
    }

    /**
     * Create a canonical active pathway.
     *
     * @param stdClass $program Program record.
     * @param array $overrides Optional field overrides.
     * @return stdClass
     */
    private function create_pathway(stdClass $program, array $overrides = []): stdClass {
        $courses = $this->create_required_courses();

        $defaults = [
            'programid' => $program->id,
            'shortname' => 'tronc_commun_initial',
            'fullname' => 'Parcours initial du tronc commun',
            'requiredcourseids' => array_column($courses, 'id'),
            'requiredbadges' => [
                constants::BADGE_JOUEUR_INITIE,
            ],
            'requiredcompetencies' => [
                constants::COMP_READ_GAME,
                constants::COMP_MAP_SYSTEM,
                constants::COMP_KNOW_CHOOSE_ACT_REMEMBER,
            ],
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_PROGRAM,
            'contextid' => $this->categorycontext->id,
        ];

        return (new pathway_service())->create_pathway(array_merge($defaults, $overrides));
    }

    /**
     * Create a minimal set of courses required by the test pathway.
     *
     * @return stdClass[]
     */
    private function create_required_courses(): array {
        $course1 = $this->getDataGenerator()->create_course([
            'category' => $this->category->id,
            'fullname' => 'UCKK-TC101 — Cartographie des idées avec l’IA',
            'shortname' => constants::COURSE_TC101,
            'idnumber' => constants::COURSE_TC101,
        ]);

        $course2 = $this->getDataGenerator()->create_course([
            'category' => $this->category->id,
            'fullname' => 'UCKK-TC102 — Intelligence collective, expertise située et décision légitime',
            'shortname' => constants::COURSE_TC102,
            'idnumber' => constants::COURSE_TC102,
        ]);

        return [$course1, $course2];
    }
}