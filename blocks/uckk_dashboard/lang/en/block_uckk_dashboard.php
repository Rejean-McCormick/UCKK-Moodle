<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * English strings for the UCKK dashboard block.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['settingsdisplayheading'] = 'Dashboard display';
$string['settingsdisplayheading_desc'] = 'Configure which UCKK dashboard cards are shown by default. These settings do not bypass capabilities or visibility rules.';

$string['settingsbehaviourheading'] = 'Dashboard behaviour';
$string['settingsbehaviourheading_desc'] = 'Configure refresh and summary limits for the UCKK dashboard block.';

$string['defaultviewmode'] = 'Default dashboard view mode';
$string['defaultviewmode_desc'] = 'Choose the default display emphasis. Auto lets the block choose a view based on the current user capabilities and context.';
$string['viewmode_auto'] = 'Automatic';
$string['viewmode_player'] = 'Joueur';
$string['viewmode_mentor'] = 'Mentor';
$string['viewmode_archivist'] = 'Archivist';
$string['viewmode_inquisitor'] = 'Inquisiteur';
$string['viewmode_manager'] = 'UCKK manager';

$string['showpathway'] = 'Show pathway card';
$string['showpathway_desc'] = 'Display the current UCKK pathway summary when available.';
$string['showtronccommun'] = 'Show tronc commun card';
$string['showtronccommun_desc'] = 'Display progress in the required UCKK common core.';
$string['showcompetencies'] = 'Show competencies card';
$string['showcompetencies_desc'] = 'Display UCKK competency progress when available.';
$string['showbadges'] = 'Show badges card';
$string['showbadges_desc'] = 'Display UCKK badges earned or in progress.';
$string['showchallenges'] = 'Show challenges card';
$string['showchallenges_desc'] = 'Display UCKK challenge activity summaries.';
$string['showassemblies'] = 'Show assemblies card';
$string['showassemblies_desc'] = 'Display UCKK assembly participation and decision summaries.';
$string['showarchive'] = 'Show archive card';
$string['showarchive_desc'] = 'Display UCKK archive and portfolio evidence summaries.';
$string['showintegrity'] = 'Show integrity feedback card';
$string['showintegrity_desc'] = 'Display integrity feedback only when the viewer is allowed to see it.';
$string['showdeadlines'] = 'Show deadlines card';
$string['showdeadlines_desc'] = 'Display upcoming UCKK-related deadlines.';
$string['showportfolio'] = 'Show portfolio card';
$string['showportfolio_desc'] = 'Display the Joueur portfolio summary when available.';

$string['refreshinterval'] = 'Automatic refresh interval';
$string['refreshinterval_desc'] = 'Choose how often the dashboard may refresh its summaries. This controls client-side refresh only.';
$string['refreshinterval_none'] = 'No automatic refresh';
$string['refreshinterval_1min'] = 'Every minute';
$string['refreshinterval_5min'] = 'Every 5 minutes';
$string['refreshinterval_15min'] = 'Every 15 minutes';
$string['refreshinterval_30min'] = 'Every 30 minutes';

$string['maxsummaryitems'] = 'Maximum summary items';
$string['maxsummaryitems_desc'] = 'Maximum number of items to show in each dashboard summary list.';
$string['showrestrictedindicators'] = 'Show restricted indicators when permitted';
$string['showrestrictedindicators_desc'] = 'Show restricted-status indicators only to users who already have the required capabilities.';
$string['allowmanualrefresh'] = 'Allow manual refresh';
$string['allowmanualrefresh_desc'] = 'Allow users to manually refresh dashboard summaries from the block interface.';
