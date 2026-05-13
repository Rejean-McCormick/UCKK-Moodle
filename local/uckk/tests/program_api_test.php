<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// At your option any later version.
//
// Moodle is distributed in the hope that it will be useful,
// But WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// Along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the UCKK programme service API.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk;

use context_coursecat;
use context_system;
use core\exception\required_capability_exception;
use dml_exception;
use invalid_parameter_exception;
use local_uckk\service\program_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the UCKK programme service API.
 *
 * These tests define the service contract for the canonical
 * local_uckk_program registry.
 *
 * @covers \local_uckk\service\program_service
 */
final class program_api_test extends \advanced_testcase {

    /**
     * Create a valid programme payload.
     *
     * @param array $overrides Field overrides.
     * @return array
     */
    private function program_payload(array $overrides = []): array {
        return array_merge([
            'shortname' => 'tronc_commun',
            'fullname' => 'Tronc commun obligatoire',
            'programtype' => 'tronc_commun',
            'categoryid' => null,
            'description' => 'Programme canonique commun à tous les Joueurs UCKK.',
            'descriptionformat' => FORMAT_HTML,
            'status' => 'active',
            'visibility' => 'institution',
            'sortorder' => 10,
            'metadata' => [
                'canonicalkey' => 'PROGRAM_TC',
                'source' => 'uckkseed',
            ],
        ], $overrides);
    }

    /**
     * Give the current test user a capability in the supplied context.
     *
     * @param string $capability Capability name.
     * @param \context $context Moodle context.
     * @return int Role id.
     */
    private function assign_capability(string $capability, \context $context): int {
        return $this->assignUserCapability($capability, $context->id);
    }

    /**
     * Test that a manager can create a programme in the system context.
     */
    public function test_create_program_in_system_context(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $program = $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );

        $this->assertNotEmpty($program->id);
        $this->assertSame('tronc_commun', $program->shortname);
        $this->assertSame('Tronc commun obligatoire', $program->fullname);
        $this->assertSame('tronc_commun', $program->programtype);
        $this->assertSame('active', $program->status);
        $this->assertSame('institution', $program->visibility);
        $this->assertSame($systemcontext->id, (int) $program->contextid);
        $this->assertSame((int) $user->id, (int) $program->createdby);
        $this->assertSame(1, (int) $program->versionno);

        $record = $DB->get_record('local_uckk_program', ['id' => $program->id], '*', MUST_EXIST);
        $this->assertSame('tronc_commun', $record->shortname);
        $this->assertSame('active', $record->status);
        $this->assertSame('institution', $record->visibility);
        $this->assertNotEmpty($record->timecreated);
        $this->assertNotEmpty($record->timemodified);
        $this->assertJson($record->metadata);
    }

    /**
     * Test that a manager can create a programme linked to a course category.
     */
    public function test_create_program_in_course_category_context(): void {
        $this->resetAfterTest(true);

        $category = $this->getDataGenerator()->create_category([
            'name' => '01_Tronc_commun_obligatoire',
            'idnumber' => 'UCKK_CAT_TC',
        ]);

        $categorycontext = context_coursecat::instance($category->id);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assign_capability('local/uckk:manageprograms', $categorycontext);

        $service = new program_service();
        $program = $service->create_program(
            $this->program_payload([
                'categoryid' => $category->id,
            ]),
            $categorycontext,
            (int) $user->id
        );

        $this->assertSame((int) $category->id, (int) $program->categoryid);
        $this->assertSame((int) $categorycontext->id, (int) $program->contextid);
        $this->assertSame('tronc_commun', $program->shortname);
    }

    /**
     * Test that programme shortnames are stable unique keys.
     */
    public function test_create_program_rejects_duplicate_shortname(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();

        $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );

        $this->expectException(dml_exception::class);

        $service->create_program(
            $this->program_payload([
                'fullname' => 'Duplicated tronc commun',
            ]),
            $systemcontext,
            (int) $user->id
        );
    }

    /**
     * Test that required programme fields are validated before insert.
     */
    public function test_create_program_requires_shortname_fullname_and_type(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();

        $this->expectException(invalid_parameter_exception::class);

        $service->create_program(
            $this->program_payload([
                'shortname' => '',
                'fullname' => '',
                'programtype' => '',
            ]),
            $systemcontext,
            (int) $user->id
        );
    }

    /**
     * Test that programme creation requires local/uckk:manageprograms.
     */
    public function test_create_program_requires_manageprograms_capability(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();

        $service = new program_service();

        $this->expectException(required_capability_exception::class);

        $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );
    }

    /**
     * Test fetching a programme by id.
     */
    public function test_get_program_by_id(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $created = $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );

        $fetched = $service->get_program((int) $created->id, $systemcontext);

        $this->assertSame((int) $created->id, (int) $fetched->id);
        $this->assertSame('tronc_commun', $fetched->shortname);
        $this->assertSame('Tronc commun obligatoire', $fetched->fullname);
    }

    /**
     * Test fetching a programme by canonical shortname.
     */
    public function test_get_program_by_shortname(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $created = $service->create_program(
            $this->program_payload([
                'shortname' => 'grand_jeu_social',
                'fullname' => 'Grand Jeu social',
                'programtype' => 'baccalaureat',
            ]),
            $systemcontext,
            (int) $user->id
        );

        $fetched = $service->get_program_by_shortname('grand_jeu_social', $systemcontext);

        $this->assertSame((int) $created->id, (int) $fetched->id);
        $this->assertSame('grand_jeu_social', $fetched->shortname);
        $this->assertSame('baccalaureat', $fetched->programtype);
    }

    /**
     * Test listing programmes by status and visibility.
     */
    public function test_list_programs_filters_by_status_and_visibility(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();

        $service->create_program(
            $this->program_payload([
                'shortname' => 'tronc_commun',
                'fullname' => 'Tronc commun obligatoire',
                'status' => 'active',
                'visibility' => 'institution',
                'sortorder' => 10,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $service->create_program(
            $this->program_payload([
                'shortname' => 'metaphysique',
                'fullname' => 'Métaphysique',
                'programtype' => 'baccalaureat',
                'status' => 'hidden',
                'visibility' => 'institution',
                'sortorder' => 20,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $service->create_program(
            $this->program_payload([
                'shortname' => 'seminaires_avances_laboratoires',
                'fullname' => 'Séminaires avancés et laboratoires',
                'programtype' => 'seminaire',
                'status' => 'active',
                'visibility' => 'restricted',
                'sortorder' => 30,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $programs = $service->list_programs([
            'status' => 'active',
            'visibility' => 'institution',
        ], $systemcontext);

        $this->assertCount(1, $programs);

        $program = reset($programs);
        $this->assertSame('tronc_commun', $program->shortname);
        $this->assertSame('active', $program->status);
        $this->assertSame('institution', $program->visibility);
    }

    /**
     * Test listing programmes is ordered by sortorder then fullname.
     */
    public function test_list_programs_orders_by_sortorder_then_fullname(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();

        $service->create_program(
            $this->program_payload([
                'shortname' => 'economy',
                'fullname' => 'Économie',
                'programtype' => 'baccalaureat',
                'sortorder' => 30,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $service->create_program(
            $this->program_payload([
                'shortname' => 'ecology',
                'fullname' => 'Écologie',
                'programtype' => 'baccalaureat',
                'sortorder' => 20,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $service->create_program(
            $this->program_payload([
                'shortname' => 'ai',
                'fullname' => 'Intelligence artificielle gouvernable',
                'programtype' => 'baccalaureat',
                'sortorder' => 10,
            ]),
            $systemcontext,
            (int) $user->id
        );

        $programs = array_values($service->list_programs([
            'status' => 'active',
        ], $systemcontext));

        $this->assertCount(3, $programs);
        $this->assertSame('ai', $programs[0]->shortname);
        $this->assertSame('ecology', $programs[1]->shortname);
        $this->assertSame('economy', $programs[2]->shortname);
    }

    /**
     * Test updating a programme preserves the stable shortname.
     */
    public function test_update_program_preserves_shortname_and_increments_version(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $created = $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );

        $updated = $service->update_program(
            (int) $created->id,
            [
                'shortname' => 'should_not_replace_stable_key',
                'fullname' => 'Tronc commun UCKK',
                'description' => 'Description mise à jour.',
                'status' => 'hidden',
                'visibility' => 'program',
            ],
            $systemcontext,
            (int) $user->id
        );

        $this->assertSame('tronc_commun', $updated->shortname);
        $this->assertSame('Tronc commun UCKK', $updated->fullname);
        $this->assertSame('hidden', $updated->status);
        $this->assertSame('program', $updated->visibility);
        $this->assertSame(2, (int) $updated->versionno);
        $this->assertSame((int) $user->id, (int) $updated->modifiedby);
    }

    /**
     * Test archiving a programme changes status without deleting the record.
     */
    public function test_archive_program_marks_record_archived(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $created = $service->create_program(
            $this->program_payload(),
            $systemcontext,
            (int) $user->id
        );

        $archived = $service->archive_program(
            (int) $created->id,
            $systemcontext,
            (int) $user->id
        );

        $this->assertSame('archived', $archived->status);
        $this->assertSame(2, (int) $archived->versionno);

        $this->assertTrue($DB->record_exists('local_uckk_program', [
            'id' => $created->id,
            'status' => 'archived',
        ]));
    }

    /**
     * Test restricted programmes require local/uckk:viewrestricted.
     */
    public function test_restricted_programs_require_viewrestricted_capability(): void {
        $this->resetAfterTest(true);

        $manager = $this->getDataGenerator()->create_user();
        $viewer = $this->getDataGenerator()->create_user();

        $systemcontext = context_system::instance();

        $this->setUser($manager);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $service->create_program(
            $this->program_payload([
                'shortname' => 'advanced_labs',
                'fullname' => 'Séminaires avancés et laboratoires',
                'programtype' => 'seminaire',
                'visibility' => 'restricted',
            ]),
            $systemcontext,
            (int) $manager->id
        );

        $this->setUser($viewer);
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);

        $programs = $service->list_programs([
            'visibility' => 'restricted',
        ], $systemcontext);

        $this->assertCount(0, $programs);

        $this->assign_capability('local/uckk:viewrestricted', $systemcontext);

        $restrictedprograms = $service->list_programs([
            'visibility' => 'restricted',
        ], $systemcontext);

        $this->assertCount(1, $restrictedprograms);

        $program = reset($restrictedprograms);
        $this->assertSame('advanced_labs', $program->shortname);
        $this->assertSame('restricted', $program->visibility);
    }

    /**
     * Test programme metadata is stored as JSON and decoded by the API.
     */
    public function test_program_metadata_round_trip(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $systemcontext = context_system::instance();
        $this->assign_capability('local/uckk:viewcampus', $systemcontext);
        $this->assign_capability('local/uckk:manageprograms', $systemcontext);

        $service = new program_service();
        $created = $service->create_program(
            $this->program_payload([
                'metadata' => [
                    'canonicalkey' => 'PROGRAM_AI',
                    'formula' => 'L’IA aide. Elle ne décide pas.',
                    'ai_policy' => [
                        'non_sovereign' => true,
                        'requires_human_validation' => true,
                    ],
                ],
            ]),
            $systemcontext,
            (int) $user->id
        );

        $fetched = $service->get_program((int) $created->id, $systemcontext);

        $this->assertIsArray($fetched->metadata);
        $this->assertSame('PROGRAM_AI', $fetched->metadata['canonicalkey']);
        $this->assertSame('L’IA aide. Elle ne décide pas.', $fetched->metadata['formula']);
        $this->assertTrue($fetched->metadata['ai_policy']['non_sovereign']);
        $this->assertTrue($fetched->metadata['ai_policy']['requires_human_validation']);
    }
}