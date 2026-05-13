<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace block_uckk_dashboard;

use block_uckk_dashboard\output\dashboard_block;
use block_uckk_dashboard\privacy\provider;
use context_system;
use context_user;
use core_privacy\local\metadata\null_provider;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/uckk_dashboard/block_uckk_dashboard.php');

/**
 * Tests for the UCKK dashboard block.
 *
 * The block displays permission-filtered UCKK dashboard summaries. It does not
 * own canonical pathway, challenge, assembly, archive, badge, competency,
 * integrity, or profile records.
 *
 * @package    block_uckk_dashboard
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class block_uckk_dashboard_test extends \advanced_testcase {
    /**
     * Prepare each test with a clean database state.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * The block initialises with the canonical plugin title.
     */
    public function test_block_initialises_with_expected_title(): void {
        $block = new \block_uckk_dashboard();
        $block->init();

        $this->assertSame(
            get_string('pluginname', 'block_uckk_dashboard'),
            $block->title
        );
    }

    /**
     * Dashboard capabilities are registered with Moodle accesslib.
     */
    public function test_dashboard_capabilities_are_registered(): void {
        $expected = [
            'block/uckk_dashboard:addinstance',
            'block/uckk_dashboard:myaddinstance',
            'block/uckk_dashboard:view',
            'block/uckk_dashboard:viewothers',
            'block/uckk_dashboard:configure',
        ];

        foreach ($expected as $capability) {
            $this->assertNotEmpty(
                get_capability_info($capability),
                "Missing capability: {$capability}"
            );
        }
    }

    /**
     * A normal user does not receive permission to view other dashboards by default.
     */
    public function test_viewothers_capability_is_not_granted_by_default(): void {
        $viewer = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();

        $this->setUser($viewer);

        $this->assertFalse(
            has_capability(
                'block/uckk_dashboard:viewothers',
                context_user::instance((int) $target->id),
                $viewer
            )
        );
    }

    /**
     * A staff role can be granted permission to view other users' dashboards.
     */
    public function test_viewothers_capability_can_be_granted_to_staff_role(): void {
        $viewer = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();

        $this->assign_capability_to_user(
            (int) $viewer->id,
            'block/uckk_dashboard:viewothers'
        );

        $this->setUser($viewer);

        $this->assertTrue(
            has_capability(
                'block/uckk_dashboard:viewothers',
                context_user::instance((int) $target->id),
                $viewer
            )
        );
    }

    /**
     * A user must have the dashboard view capability to see the block.
     */
    public function test_view_capability_can_be_granted_to_user(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->assign_capability_to_user(
            (int) $user->id,
            'block/uckk_dashboard:view'
        );

        $this->setUser($user);

        $this->assertTrue(
            has_capability(
                'block/uckk_dashboard:view',
                context_system::instance(),
                $user
            )
        );
    }

    /**
     * The dashboard output object exports a safe default template context.
     */
    public function test_dashboard_block_exports_safe_default_context(): void {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $dashboard = new dashboard_block((int) $user->id);
        $data = $dashboard->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame((int) $user->id, (int) $data->userid);
        $this->assertNotEmpty($data->uniqid);
        $this->assertSame('joueur', $data->viewertype);
        $this->assertSame(get_string('viewer:joueur', 'block_uckk_dashboard'), $data->viewerlabel);
        $this->assertSame(get_string('dashboardtitle', 'block_uckk_dashboard'), $data->title);

        $this->assertTrue($data->hasdashboard);
        $this->assertFalse($data->haserrors);
        $this->assertSame('', $data->errormessage);

        $this->assertIsArray($data->notices);
        $this->assertTrue($data->hasnotices);

        $this->assertTrue($data->showpathway);
        $this->assertTrue($data->showprogress);
        $this->assertTrue($data->showchallenges);
        $this->assertTrue($data->showassemblies);
        $this->assertTrue($data->showbadges);
        $this->assertTrue($data->showarchive);
        $this->assertTrue($data->showintegrity);
        $this->assertTrue($data->showdeadlines);
        $this->assertTrue($data->showportfolio);

        $this->assertFalse($data->showstaff);
        $this->assertFalse($data->canconfigure);
    }

    /**
     * The dashboard output object exports supplied, already-filtered section data.
     */
    public function test_dashboard_block_exports_permission_filtered_section_data(): void {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $dashboard = new dashboard_block((int) $user->id, [
            'title' => 'UCKK cockpit',
            'subtitle' => 'Prepared dashboard data.',
            'viewertype' => 'mentor',
            'viewerlabel' => get_string('viewer:mentor', 'block_uckk_dashboard'),
            'canrefresh' => true,
            'canconfigure' => true,
            'configureurl' => new moodle_url('/blocks/uckk_dashboard/settings.php'),

            'showpathway' => true,
            'pathway' => [
                'id' => 10,
                'shortname' => 'grand_jeu_social',
                'fullname' => 'Baccalauréat UCKK — Grand Jeu social',
                'programname' => 'Grand Jeu social',
                'status' => 'active',
                'statuslabel' => 'Active',
                'statusclass' => 'status-active',
                'progresspercent' => 50,
                'progresslabel' => '50 %',
                'coursescompleted' => 4,
                'coursestotal' => 8,
                'competenciesachieved' => 7,
                'competenciestotal' => 14,
                'badgesearned' => 2,
                'badgestotal' => 6,
            ],

            'showarchive' => true,
            'archiveitems' => [
                [
                    'title' => 'Validated proof',
                    'status' => 'validated',
                    'statuslabel' => 'Validated',
                    'summary' => 'Evidence already filtered for this viewer.',
                    'url' => new moodle_url('/mod/uckkarchive/view.php', ['id' => 3]),
                ],
            ],

            'showintegrity' => false,
            'integrityitems' => [
                [
                    'title' => 'Restricted integrity case that must not be exported',
                    'status' => 'pending_review',
                ],
            ],

            'showstaff' => true,
            'staffitems' => [
                [
                    'title' => get_string('staff:submissions', 'block_uckk_dashboard'),
                    'count' => 3,
                    'summary' => 'Submissions awaiting review.',
                    'url' => new moodle_url('/mod/uckkchallenge/index.php'),
                ],
            ],
        ]);

        $data = $dashboard->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('UCKK cockpit', $data->title);
        $this->assertSame('Prepared dashboard data.', $data->subtitle);
        $this->assertSame('mentor', $data->viewertype);
        $this->assertSame(get_string('viewer:mentor', 'block_uckk_dashboard'), $data->viewerlabel);

        $this->assertTrue($data->canrefresh);
        $this->assertTrue($data->canconfigure);
        $this->assertNotEmpty($data->configureurl);

        $this->assertTrue($data->showpathway);
        $this->assertNotEmpty($data->pathway);
        $this->assertSame('grand_jeu_social', $data->pathway->shortname);
        $this->assertSame(50, (int) $data->pathway->progresspercent);

        $this->assertTrue($data->showarchive);
        $this->assertTrue($data->hasarchiveitems);
        $this->assertCount(1, $data->archiveitems);
        $this->assertSame('Validated proof', $data->archiveitems[0]->title);

        $this->assertFalse($data->showintegrity);
        $this->assertFalse($data->hasintegrityitems);
        $this->assertEmpty($data->integrityitems);

        $this->assertTrue($data->showstaff);
        $this->assertTrue($data->hasstaffitems);
        $this->assertCount(1, $data->staffitems);
    }

    /**
     * Empty dashboard data is exported as explicit empty states.
     */
    public function test_dashboard_block_exports_empty_states(): void {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $dashboard = new dashboard_block((int) $user->id, [
            'pathway' => null,
            'archiveitems' => [],
            'integrityitems' => [],
            'deadlines' => [],
            'portfolioitems' => [],
            'staffitems' => [],
        ]);

        $data = $dashboard->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($data->hasdashboard);

        $this->assertEmpty($data->pathway);
        $this->assertFalse($data->hasarchiveitems);
        $this->assertFalse($data->hasintegrityitems);
        $this->assertFalse($data->hasdeadlines);
        $this->assertFalse($data->hasportfolioitems);
        $this->assertFalse($data->hasstaffitems);
    }

    /**
     * Error state is explicit and does not silently render normal dashboard data.
     */
    public function test_dashboard_block_exports_error_state(): void {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $dashboard = new dashboard_block((int) $user->id, [
            'hasdashboard' => false,
            'haserrors' => true,
            'errormessage' => get_string('error:nopermission', 'block_uckk_dashboard'),
        ]);

        $data = $dashboard->export_for_template($PAGE->get_renderer('core'));

        $this->assertFalse($data->hasdashboard);
        $this->assertTrue($data->haserrors);
        $this->assertSame(
            get_string('error:nopermission', 'block_uckk_dashboard'),
            $data->errormessage
        );
    }

    /**
     * The block privacy provider declares that the block stores no personal data.
     */
    public function test_privacy_provider_declares_no_own_personal_data(): void {
        $this->assertInstanceOf(null_provider::class, new provider());

        $this->assertSame(
            'privacy:metadata',
            provider::get_reason()
        );

        $this->assertSame(
            get_string('privacy:metadata', 'block_uckk_dashboard'),
            get_string(provider::get_reason(), 'block_uckk_dashboard')
        );
    }

    /**
     * Assign a capability to a user at system context for testing.
     *
     * @param int $userid User id.
     * @param string $capability Capability name.
     */
    private function assign_capability_to_user(int $userid, string $capability): void {
        $systemcontext = context_system::instance();

        $roleid = $this->getDataGenerator()->create_role([
            'shortname' => 'uckkdashboardtest' . substr(md5($capability . $userid), 0, 8),
            'name' => 'UCKK dashboard test role',
            'archetype' => '',
        ]);

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