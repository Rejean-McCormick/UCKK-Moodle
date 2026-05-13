<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * English strings for the UCKK seed admin tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCKK seed';
$string['privacy:metadata'] = 'The UCKK seed tool stores administrator execution logs for seed, reset, validation, and preset export operations.';

// Navigation and page headings.
$string['seeduckk'] = 'Seed UCKK distribution';
$string['seeddistribution'] = 'Seed UCKK distribution';
$string['resetdistribution'] = 'Reset UCKK seeded content';
$string['validatedistribution'] = 'Validate UCKK distribution';
$string['exportpreset'] = 'Export UCKK preset';
$string['settings'] = 'UCKK seed settings';
$string['seedsummary'] = 'Seed summary';
$string['validationreport'] = 'Validation report';
$string['preset'] = 'Preset';
$string['presets'] = 'Presets';
$string['presetcard'] = 'Preset card';
$string['uckkseed'] = 'UCKK seed';
$string['uckkseed:view'] = 'View UCKK seed tool';

// Capabilities.
$string['uckkseed:seed'] = 'Seed the UCKK distribution';
$string['uckkseed:reset'] = 'Reset UCKK seeded content';
$string['uckkseed:validate'] = 'Validate the UCKK distribution';
$string['uckkseed:exportpresets'] = 'Export UCKK seed presets';

$string['capability:seed'] = 'Seed UCKK distribution';
$string['capability:reset'] = 'Reset UCKK seeded content';
$string['capability:validate'] = 'Validate UCKK distribution';
$string['capability:exportpresets'] = 'Export UCKK presets';

// Settings.
$string['enabletool'] = 'Enable UCKK seed tool';
$string['enabletool_desc'] = 'Allow authorised administrators to use the UCKK seed tool.';
$string['allowcli'] = 'Allow CLI execution';
$string['allowcli_desc'] = 'Allow the UCKK seed CLI scripts to run seed, reset, validation, and export operations.';
$string['allowreset'] = 'Allow reset operations';
$string['allowreset_desc'] = 'Allow authorised users to reset UCKK seeded content. Reset operations must remain scoped to seeded content only.';
$string['allowdryrun'] = 'Allow dry-run mode';
$string['allowdryrun_desc'] = 'Allow users to preview seed, reset, and validation actions without changing Moodle data.';
$string['defaultmode'] = 'Default execution mode';
$string['defaultmode_desc'] = 'The default mode used by the seed form and CLI scripts when no explicit mode is provided.';
$string['presetpath'] = 'Preset path';
$string['presetpath_desc'] = 'Path to the UCKK seed preset directory. The default is admin/tool/uckkseed/presets.';
$string['logretentiondays'] = 'Log retention in days';
$string['logretentiondays_desc'] = 'Number of days to retain UCKK seed execution logs.';
$string['autoseedoninstall'] = 'Auto-seed on install';
$string['autoseedoninstall_desc'] = 'Automatically seed the UCKK distribution when the scheduled seed task runs. Use with care on production sites.';
$string['requireconfirmation'] = 'Require confirmation';
$string['requireconfirmation_desc'] = 'Require explicit confirmation before applying seed or reset operations.';
$string['configmissing'] = 'The UCKK seed configuration is missing or incomplete.';
$string['toolisdisabled'] = 'The UCKK seed tool is disabled.';

// Actions.
$string['action'] = 'Action';
$string['actions'] = 'Actions';
$string['action_seed'] = 'Seed';
$string['action_reset'] = 'Reset';
$string['action_validate'] = 'Validate';
$string['action_export_preset'] = 'Export preset';
$string['action:seed'] = 'Seed';
$string['action:reset'] = 'Reset';
$string['action:validate'] = 'Validate';
$string['action:export_preset'] = 'Export preset';

// Modes.
$string['mode'] = 'Mode';
$string['mode_apply'] = 'Apply';
$string['mode_dry_run'] = 'Dry run';
$string['mode_report'] = 'Report';
$string['mode_rollback_plan'] = 'Rollback plan';
$string['mode:apply'] = 'Apply';
$string['mode:dry_run'] = 'Dry run';
$string['mode:report'] = 'Report';
$string['mode:rollback_plan'] = 'Rollback plan';
$string['dryrun'] = 'Dry run';
$string['dryrunnotice'] = 'Dry-run mode is enabled. No Moodle data will be changed.';
$string['rollbackplan'] = 'Rollback plan';
$string['rollbackplannotice'] = 'Rollback-plan mode reports what would need to be reversed. It does not delete arbitrary Moodle data.';
$string['reportmode'] = 'Report mode';
$string['applymode'] = 'Apply mode';

// Statuses.
$string['status'] = 'Status';
$string['status_pending'] = 'Pending';
$string['status_running'] = 'Running';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';
$string['status_cancelled'] = 'Cancelled';
$string['status_skipped'] = 'Skipped';
$string['status_warning'] = 'Warning';
$string['status:pending'] = 'Pending';
$string['status:running'] = 'Running';
$string['status:completed'] = 'Completed';
$string['status:failed'] = 'Failed';
$string['status:cancelled'] = 'Cancelled';
$string['status:skipped'] = 'Skipped';
$string['status:warning'] = 'Warning';

// Validation severities.
$string['severity'] = 'Severity';
$string['severity_info'] = 'Info';
$string['severity_success'] = 'Success';
$string['severity_warning'] = 'Warning';
$string['severity_error'] = 'Error';
$string['severity_blocker'] = 'Blocker';
$string['severity:info'] = 'Info';
$string['severity:success'] = 'Success';
$string['severity:warning'] = 'Warning';
$string['severity:error'] = 'Error';
$string['severity:blocker'] = 'Blocker';

// Preset labels.
$string['preset_categories'] = 'Categories';
$string['preset_courses'] = 'Courses';
$string['preset_cohorts'] = 'Cohorts';
$string['preset_roles'] = 'Roles';
$string['preset_capabilities'] = 'Capabilities';
$string['preset_competencies'] = 'Competencies';
$string['preset_badges'] = 'Badges';
$string['preset_reports'] = 'Reports';
$string['preset_course_templates'] = 'Course templates';
$string['preset_challenge_templates'] = 'Challenge templates';
$string['preset_assembly_templates'] = 'Assembly templates';
$string['preset_archive_templates'] = 'Archive templates';

$string['preset:categories'] = 'Categories';
$string['preset:courses'] = 'Courses';
$string['preset:cohorts'] = 'Cohorts';
$string['preset:roles'] = 'Roles';
$string['preset:capabilities'] = 'Capabilities';
$string['preset:competencies'] = 'Competencies';
$string['preset:badges'] = 'Badges';
$string['preset:reports'] = 'Reports';
$string['preset:course_templates'] = 'Course templates';
$string['preset:challenge_templates'] = 'Challenge templates';
$string['preset:assembly_templates'] = 'Assembly templates';
$string['preset:archive_templates'] = 'Archive templates';

$string['presetfile'] = 'Preset file';
$string['presetfilename'] = 'Preset filename';
$string['presetpathmissing'] = 'The preset path does not exist.';
$string['presetnotfound'] = 'The requested preset was not found: {$a}';
$string['presetinvalid'] = 'The preset is invalid: {$a}';
$string['presetschema'] = 'Preset schema';
$string['presetschemaexpected'] = 'Expected preset schema: {$a}';
$string['presetitemcount'] = '{$a} item(s)';
$string['presetrequired'] = 'Required preset';
$string['presetenabled'] = 'Enabled preset';
$string['presetdisabled'] = 'Disabled preset';
$string['presetexported'] = 'Preset exported.';
$string['presetexportfailed'] = 'Preset export failed.';

// Forms.
$string['seedform'] = 'Seed form';
$string['resetform'] = 'Reset form';
$string['components'] = 'Components';
$string['selectedcomponents'] = 'Selected components';
$string['selectpresets'] = 'Select presets';
$string['selectcomponents'] = 'Select components';
$string['confirm'] = 'Confirm';
$string['force'] = 'Force';
$string['returnurl'] = 'Return URL';
$string['confirmationrequired'] = 'You must confirm this operation before it can run.';
$string['confirmseed'] = 'I understand that this will seed the UCKK distribution.';
$string['confirmreset'] = 'I understand that this will reset selected UCKK seeded content.';
$string['confirmvalidate'] = 'Validate the current UCKK distribution.';
$string['confirmexportpreset'] = 'Export the selected UCKK preset.';
$string['nothingselected'] = 'Nothing was selected.';
$string['invalidmode'] = 'Invalid seed mode.';
$string['invalidaction'] = 'Invalid seed action.';
$string['invalidpreset'] = 'Invalid preset.';
$string['invalidcomponent'] = 'Invalid component.';
$string['cannotreset'] = 'Reset operations are disabled.';
$string['cannotruncli'] = 'CLI execution is disabled.';
$string['cannotseed'] = 'You do not have permission to seed the UCKK distribution.';
$string['cannotvalidate'] = 'You do not have permission to validate the UCKK distribution.';
$string['cannotexportpresets'] = 'You do not have permission to export UCKK presets.';

// Reset scopes.
$string['scope'] = 'Scope';
$string['resetscope'] = 'Reset scope';
$string['scope_reset_seed_logs'] = 'Reset seed logs';
$string['scope_reset_seeded_content'] = 'Reset seeded content';
$string['scope_reset_seeded_courses'] = 'Reset seeded courses';
$string['scope_reset_seeded_roles'] = 'Reset seeded roles';
$string['scope_reset_seeded_badges'] = 'Reset seeded badges';
$string['scope_reset_all_uckk_seeded_content'] = 'Reset all UCKK seeded content';
$string['reset_seed_logs'] = 'Reset seed logs';
$string['reset_seeded_content'] = 'Reset seeded content';
$string['reset_seeded_courses'] = 'Reset seeded courses';
$string['reset_seeded_roles'] = 'Reset seeded roles';
$string['reset_seeded_badges'] = 'Reset seeded badges';
$string['reset_all_uckk_seeded_content'] = 'Reset all UCKK seeded content';
$string['resetwarning'] = 'Reset operations only affect UCKK seeded content and must not delete arbitrary Moodle data.';
$string['resetcompleted'] = 'Reset completed.';
$string['resetfailed'] = 'Reset failed.';

// Results and counts.
$string['summary'] = 'Summary';
$string['details'] = 'Details';
$string['message'] = 'Message';
$string['messages'] = 'Messages';
$string['counts'] = 'Counts';
$string['created'] = 'Created';
$string['updated'] = 'Updated';
$string['skipped'] = 'Skipped';
$string['failed'] = 'Failed';
$string['warnings'] = 'Warnings';
$string['errors'] = 'Errors';
$string['blockers'] = 'Blockers';
$string['createdcount'] = '{$a} created';
$string['updatedcount'] = '{$a} updated';
$string['skippedcount'] = '{$a} skipped';
$string['failedcount'] = '{$a} failed';
$string['warningscount'] = '{$a} warning(s)';
$string['errorscount'] = '{$a} error(s)';
$string['noissues'] = 'No issues found.';
$string['nomessages'] = 'No messages.';
$string['norecords'] = 'No records.';
$string['resultok'] = 'Result OK';
$string['resultfailed'] = 'Result failed';
$string['haserrors'] = 'Has errors';
$string['haswarnings'] = 'Has warnings';
$string['validationpassed'] = 'Validation passed.';
$string['validationfailed'] = 'Validation failed.';
$string['validationcompleted'] = 'Validation completed.';
$string['seedcompleted'] = 'Seed completed.';
$string['seedfailed'] = 'Seed failed.';
$string['seedskipped'] = 'Seed skipped.';
$string['seedstarted'] = 'Seed started.';
$string['seedrunning'] = 'Seed running.';
$string['operationcompleted'] = 'Operation completed.';
$string['operationfailed'] = 'Operation failed.';

// Run and log labels.
$string['run'] = 'Run';
$string['runid'] = 'Run ID';
$string['runstatus'] = 'Run status';
$string['runcreated'] = 'Run created';
$string['runfinished'] = 'Run finished';
$string['executionlog'] = 'Execution log';
$string['seedlog'] = 'Seed log';
$string['logentry'] = 'Log entry';
$string['targettype'] = 'Target type';
$string['targetkey'] = 'Target key';
$string['targetid'] = 'Target ID';
$string['component'] = 'Component';
$string['source'] = 'Source';
$string['metadata'] = 'Metadata';
$string['timecreated'] = 'Created';
$string['timemodified'] = 'Modified';
$string['duration'] = 'Duration';
$string['durationlabel'] = 'Duration';
$string['userid'] = 'User ID';
$string['createdby'] = 'Created by';
$string['modifiedby'] = 'Modified by';

// Seeder components and target types.
$string['targettype:category'] = 'Category';
$string['targettype:course'] = 'Course';
$string['targettype:cohort'] = 'Cohort';
$string['targettype:role'] = 'Role';
$string['targettype:capability'] = 'Capability';
$string['targettype:competency'] = 'Competency';
$string['targettype:badge'] = 'Badge';
$string['targettype:report'] = 'Report';
$string['targettype:course_template'] = 'Course template';
$string['targettype:challenge_template'] = 'Challenge template';
$string['targettype:assembly_template'] = 'Assembly template';
$string['targettype:archive_template'] = 'Archive template';

$string['seed:category'] = 'Category seed';
$string['seed:course'] = 'Course seed';
$string['seed:cohort'] = 'Cohort seed';
$string['seed:role'] = 'Role seed';
$string['seed:competency'] = 'Competency seed';
$string['seed:badge'] = 'Badge seed';
$string['seed:report'] = 'Report seed';

// Component names.
$string['component:local_uckk'] = 'UCKK local registry';
$string['component:theme_uckk'] = 'UCKK theme';
$string['component:format_uckk'] = 'UCKK course format';
$string['component:block_uckk_dashboard'] = 'UCKK dashboard block';
$string['component:mod_uckkchallenge'] = 'UCKK challenge';
$string['component:mod_uckkassembly'] = 'UCKK assembly';
$string['component:mod_uckkarchive'] = 'UCKK archive';
$string['component:tool_uckkintegrity'] = 'UCKK integrity tool';
$string['component:report_uckk'] = 'UCKK report';
$string['component:tool_uckkseed'] = 'UCKK seed tool';

// CLI.
$string['cli:seed:description'] = 'Seed the UCKK distribution.';
$string['cli:reset:description'] = 'Reset UCKK seeded content.';
$string['cli:validate:description'] = 'Validate the UCKK distribution.';
$string['cli:export_preset:description'] = 'Export a UCKK preset.';
$string['cli:option:dry-run'] = 'Preview the operation without changing data.';
$string['cli:option:report'] = 'Return a report without applying changes.';
$string['cli:option:rollback-plan'] = 'Generate a rollback plan.';
$string['cli:option:preset'] = 'Limit the operation to one preset.';
$string['cli:option:component'] = 'Limit the operation to one component.';
$string['cli:option:target'] = 'Limit the operation to one target key.';
$string['cli:option:force'] = 'Force the operation where supported.';
$string['cli:option:json'] = 'Return JSON output.';
$string['cli:completed'] = 'CLI operation completed.';
$string['cli:failed'] = 'CLI operation failed.';
$string['cli:disabled'] = 'CLI execution is disabled for the UCKK seed tool.';
$string['cli:requiresconfirm'] = 'This operation requires --force or explicit confirmation.';

// Scheduled task.
$string['task_seed_distribution'] = 'Seed UCKK distribution';
$string['task_seed_distribution_desc'] = 'Runs the configured UCKK seed distribution task.';
$string['task:disabled'] = 'The UCKK seed scheduled task is disabled by configuration.';
$string['task:dryrun'] = 'The UCKK seed scheduled task ran in dry-run mode.';
$string['task:completed'] = 'The UCKK seed scheduled task completed.';
$string['task:failed'] = 'The UCKK seed scheduled task failed.';

// Privacy metadata: run table.
$string['privacy:metadata:tool_uckkseed_run'] = 'Stores UCKK seed execution runs.';
$string['privacy:metadata:tool_uckkseed_run:userid'] = 'The user who initiated the run.';
$string['privacy:metadata:tool_uckkseed_run:action'] = 'The action executed by the run.';
$string['privacy:metadata:tool_uckkseed_run:mode'] = 'The execution mode used by the run.';
$string['privacy:metadata:tool_uckkseed_run:status'] = 'The final or current status of the run.';
$string['privacy:metadata:tool_uckkseed_run:summary'] = 'A summary of the seed operation.';
$string['privacy:metadata:tool_uckkseed_run:metadata'] = 'Additional JSON metadata for the run.';
$string['privacy:metadata:tool_uckkseed_run:timecreated'] = 'The time the run was created.';
$string['privacy:metadata:tool_uckkseed_run:timemodified'] = 'The time the run was last modified.';

// Privacy metadata: log table.
$string['privacy:metadata:tool_uckkseed_log'] = 'Stores UCKK seed execution log entries.';
$string['privacy:metadata:tool_uckkseed_log:runid'] = 'The seed run associated with this log entry.';
$string['privacy:metadata:tool_uckkseed_log:userid'] = 'The user associated with this log entry, where applicable.';
$string['privacy:metadata:tool_uckkseed_log:level'] = 'The log severity level.';
$string['privacy:metadata:tool_uckkseed_log:message'] = 'The log message.';
$string['privacy:metadata:tool_uckkseed_log:metadata'] = 'Additional JSON metadata for the log entry.';
$string['privacy:metadata:tool_uckkseed_log:timecreated'] = 'The time the log entry was created.';

// Validation messages.
$string['validation:missingpreset'] = 'Missing preset: {$a}';
$string['validation:invalidschema'] = 'Invalid preset schema for {$a}.';
$string['validation:missingitems'] = 'Preset {$a} does not contain an items list.';
$string['validation:missingkey'] = 'A preset item is missing a key.';
$string['validation:duplicatekey'] = 'Duplicate key found: {$a}';
$string['validation:missingcomponent'] = 'Missing component: {$a}';
$string['validation:missingdependency'] = 'Missing dependency: {$a}';
$string['validation:capabilitymissing'] = 'Missing capability: {$a}';
$string['validation:rolesymbolic'] = 'Symbolic identities must not be created as Moodle technical roles: {$a}';
$string['validation:badgewithoutvalidation'] = 'Badge {$a} must require evidence and human validation.';
$string['validation:courseformat'] = 'UCKK seeded courses must use course format value "uckk".';
$string['validation:ok'] = 'Validation OK.';

// Seed messages.
$string['seed:categorycreated'] = 'Category created: {$a}';
$string['seed:categoryupdated'] = 'Category updated: {$a}';
$string['seed:coursecreated'] = 'Course created: {$a}';
$string['seed:courseupdated'] = 'Course updated: {$a}';
$string['seed:cohortcreated'] = 'Cohort created: {$a}';
$string['seed:cohortupdated'] = 'Cohort updated: {$a}';
$string['seed:rolecreated'] = 'Role created: {$a}';
$string['seed:roleupdated'] = 'Role updated: {$a}';
$string['seed:capabilityassigned'] = 'Capability assigned: {$a}';
$string['seed:competencycreated'] = 'Competency created: {$a}';
$string['seed:competencyupdated'] = 'Competency updated: {$a}';
$string['seed:badgecreated'] = 'Badge created: {$a}';
$string['seed:badgeupdated'] = 'Badge updated: {$a}';
$string['seed:reportcreated'] = 'Report created: {$a}';
$string['seed:reportupdated'] = 'Report updated: {$a}';
$string['seed:templateskipped'] = 'Template skipped: {$a}';
$string['seed:itemskipped'] = 'Seed item skipped: {$a}';
$string['seed:itemfailed'] = 'Seed item failed: {$a}';

// Report/export labels.
$string['export'] = 'Export';
$string['exportformat'] = 'Export format';
$string['exportjson'] = 'Export JSON';
$string['downloadpreset'] = 'Download preset';
$string['viewreport'] = 'View report';
$string['downloadreport'] = 'Download report';
$string['backtosettings'] = 'Back to settings';
$string['backtoseedtool'] = 'Back to UCKK seed tool';

// Warnings and governance notices.
$string['governancenotice'] = 'The UCKK seed tool installs and validates campus structure. It must not replace the owning plugins for challenge workflow, assembly decisions, archive validation, integrity cases, reports, badges, or competencies.';
$string['symbolicrolesnotice'] = 'Symbolic UCKK identities must not be created as Moodle technical roles. Use badges, cohorts, competencies, profile fields, portfolio titles, and archive distinctions instead.';
$string['humanvalidationnotice'] = 'Seeded badges and competencies must preserve evidence and human validation requirements.';
$string['coremodificationnotice'] = 'UCKK-Moodle must not modify Moodle core for seed operations.';