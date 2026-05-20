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
$string['presetpath'] = 'Academic registry JSON path';
$string['presetpath_desc'] = 'Path to the UCKK academic registry JSON directory. Relative paths are resolved from the Moodle root. Default: academic_registry_json.';
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

$string['presetfile'] = 'Academic registry JSON file';
$string['presetfilename'] = 'Academic registry JSON filename';
$string['presetpathmissing'] = 'The academic registry JSON path does not exist.';
$string['presetnotfound'] = 'The requested academic registry JSON preset was not found: {$a}';
$string['presetinvalid'] = 'The academic registry JSON preset is invalid: {$a}';
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
$string['metadata'] = 'Metadata';
$string['runid'] = 'Run ID';
$string['created'] = 'Created';
$string['updated'] = 'Updated';
$string['skipped'] = 'Skipped';
$string['failed'] = 'Failed';
$string['warnings'] = 'Warnings';
$string['errors'] = 'Errors';
$string['count_created'] = 'Created: {$a}';
$string['count_updated'] = 'Updated: {$a}';
$string['count_skipped'] = 'Skipped: {$a}';
$string['count_failed'] = 'Failed: {$a}';
$string['count_warnings'] = 'Warnings: {$a}';
$string['count_errors'] = 'Errors: {$a}';
$string['validationpassed'] = 'Validation passed.';
$string['validationfailed'] = 'Validation failed.';
$string['seedcompleted'] = 'Seed completed.';
$string['seedfailed'] = 'Seed failed.';
$string['seedtooldisabled'] = 'The seed tool is disabled.';
$string['missingcapability'] = 'Missing required capability.';
$string['cliaccessdisabled'] = 'CLI access is disabled for the UCKK seed tool.';

// CLI.
$string['cli:seed:help'] = 'Seed the UCKK distribution from canonical presets.';
$string['cli:reset:help'] = 'Reset UCKK seeded content.';
$string['cli:validate:help'] = 'Validate UCKK seed presets and current seeded state.';
$string['cli:exportpreset:help'] = 'Export UCKK seed presets.';
$string['cli:invalidoption'] = 'Invalid CLI option: {$a}';
$string['cli:missingoption'] = 'Missing CLI option: {$a}';
$string['cli:confirmrequired'] = 'Confirmation is required. Add --confirm to continue.';
$string['cli:dryrun'] = 'CLI dry run';
$string['cli:jsonoutput'] = 'JSON output';
$string['cli:quiet'] = 'Quiet mode';

// Events and logging.
$string['event_seed_started'] = 'UCKK seed started';
$string['event_seed_completed'] = 'UCKK seed completed';
$string['event_seed_failed'] = 'UCKK seed failed';
$string['event_reset_started'] = 'UCKK reset started';
$string['event_reset_completed'] = 'UCKK reset completed';
$string['event_reset_failed'] = 'UCKK reset failed';
$string['event_validation_started'] = 'UCKK validation started';
$string['event_validation_completed'] = 'UCKK validation completed';
$string['event_preset_exported'] = 'UCKK preset exported';
$string['logentry'] = 'Log entry';
$string['logentries'] = 'Log entries';
$string['logretentiontask'] = 'Purge old UCKK seed logs';
$string['scheduledseedtask'] = 'Scheduled UCKK seed distribution';

// Category seeding.
$string['categoryseed:created'] = 'Category created: {$a}';
$string['categoryseed:updated'] = 'Category updated: {$a}';
$string['categoryseed:skipped'] = 'Category skipped: {$a}';
$string['categoryseed:reset'] = 'Category reset: {$a}';
$string['categoryseed:validationok'] = 'Category validation OK: {$a}';
$string['categoryseed:error_missingidnumber'] = 'Category definition is missing an idnumber.';
$string['categoryseed:error_missingname'] = 'Category definition is missing a name.';
$string['categoryseed:error_duplicateidnumber'] = 'Duplicate category idnumber: {$a}';
$string['categoryseed:error_parentnotfound'] = 'Parent category not found: {$a}';
$string['categoryseed:warning_existingmanual'] = 'Existing category is not marked as UCKK seeded: {$a}';

// Course seeding.
$string['courseseed:created'] = 'Course created: {$a}';
$string['courseseed:updated'] = 'Course updated: {$a}';
$string['courseseed:skipped'] = 'Course skipped: {$a}';
$string['courseseed:reset'] = 'Course reset: {$a}';
$string['courseseed:validationok'] = 'Course validation OK: {$a}';
$string['courseseed:error_missingidnumber'] = 'Course definition is missing an idnumber.';
$string['courseseed:error_missingshortname'] = 'Course definition is missing a shortname.';
$string['courseseed:error_missingfullname'] = 'Course definition is missing a fullname.';
$string['courseseed:error_missingcategory'] = 'Course definition is missing a category.';
$string['courseseed:error_categorynotfound'] = 'Course category not found: {$a}';
$string['courseseed:error_duplicateshortname'] = 'Duplicate course shortname: {$a}';
$string['courseseed:error_duplicateidnumber'] = 'Duplicate course idnumber: {$a}';
$string['courseseed:warning_existingmanual'] = 'Existing course is not marked as UCKK seeded: {$a}';

// Cohort seeding.
$string['cohortseed:created'] = 'Cohort created: {$a}';
$string['cohortseed:updated'] = 'Cohort updated: {$a}';
$string['cohortseed:skipped'] = 'Cohort skipped: {$a}';
$string['cohortseed:reset'] = 'Cohort reset: {$a}';
$string['cohortseed:validationok'] = 'Cohort validation OK: {$a}';
$string['cohortseed:error_missingidnumber'] = 'Cohort definition is missing an idnumber.';
$string['cohortseed:error_missingname'] = 'Cohort definition is missing a name.';
$string['cohortseed:error_duplicateidnumber'] = 'Duplicate cohort idnumber: {$a}';
$string['cohortseed:error_invalidcontext'] = 'Invalid cohort context: {$a}';
$string['cohortseed:warning_existingmanual'] = 'Existing cohort is not marked as UCKK seeded: {$a}';

// Role seeding.
$string['roleseed:created'] = 'Role created: {$a}';
$string['roleseed:updated'] = 'Role updated: {$a}';
$string['roleseed:skipped'] = 'Role skipped: {$a}';
$string['roleseed:reset'] = 'Role reset: {$a}';
$string['roleseed:validationok'] = 'Role validation OK: {$a}';
$string['roleseed:error_missingidnumber'] = 'Role definition is missing an idnumber.';
$string['roleseed:error_missingshortname'] = 'Role definition is missing a shortname.';
$string['roleseed:error_missingname'] = 'Role definition is missing a name.';
$string['roleseed:error_duplicateidnumber'] = 'Duplicate role idnumber: {$a}';
$string['roleseed:error_duplicateshortname'] = 'Duplicate role shortname: {$a}';
$string['roleseed:error_invalidarchetype'] = 'Invalid role archetype: {$a}';
$string['roleseed:warning_existingmanual'] = 'Existing role is not marked as UCKK seeded: {$a}';

// Capability seeding.
$string['capabilityseed:created'] = 'Capability override created: {$a}';
$string['capabilityseed:updated'] = 'Capability override updated: {$a}';
$string['capabilityseed:skipped'] = 'Capability override skipped: {$a}';
$string['capabilityseed:reset'] = 'Capability override reset: {$a}';
$string['capabilityseed:validationok'] = 'Capability validation OK: {$a}';
$string['capabilityseed:error_missingrole'] = 'Capability definition is missing a role.';
$string['capabilityseed:error_missingcapability'] = 'Capability definition is missing a capability.';
$string['capabilityseed:error_invalidpermission'] = 'Invalid capability permission: {$a}';
$string['capabilityseed:error_rolenotfound'] = 'Role not found for capability override: {$a}';
$string['capabilityseed:warning_unknowncapability'] = 'Unknown Moodle capability: {$a}';

// Competency seeding.
$string['competencyseed:created'] = 'Competency created: {$a}';
$string['competencyseed:updated'] = 'Competency updated: {$a}';
$string['competencyseed:skipped'] = 'Competency skipped: {$a}';
$string['competencyseed:reset'] = 'Competency reset: {$a}';
$string['competencyseed:validationok'] = 'Competency validation OK: {$a}';
$string['competencyseed:error_missingidnumber'] = 'Competency definition is missing an idnumber.';
$string['competencyseed:error_missingname'] = 'Competency definition is missing a name.';
$string['competencyseed:error_missingframework'] = 'Competency definition is missing a framework.';
$string['competencyseed:error_duplicateidnumber'] = 'Duplicate competency idnumber: {$a}';
$string['competencyseed:error_frameworknotfound'] = 'Competency framework not found: {$a}';
$string['competencyseed:warning_existingmanual'] = 'Existing competency is not marked as UCKK seeded: {$a}';

// Badge seeding.
$string['badgeseed:created'] = 'Badge created: {$a}';
$string['badgeseed:updated'] = 'Badge updated: {$a}';
$string['badgeseed:skipped'] = 'Badge skipped: {$a}';
$string['badgeseed:reset'] = 'Badge reset: {$a}';
$string['badgeseed:validationok'] = 'Badge validation OK: {$a}';
$string['badgeseed:error_missingidnumber'] = 'Badge definition is missing an idnumber.';
$string['badgeseed:error_missingname'] = 'Badge definition is missing a name.';
$string['badgeseed:error_duplicateidnumber'] = 'Duplicate badge idnumber: {$a}';
$string['badgeseed:error_invalidtype'] = 'Invalid badge type: {$a}';
$string['badgeseed:error_coursenotfound'] = 'Badge course not found: {$a}';
$string['badgeseed:warning_existingmanual'] = 'Existing badge is not marked as UCKK seeded: {$a}';

// Template seeding.
$string['templateseed:created'] = 'Template created: {$a}';
$string['templateseed:updated'] = 'Template updated: {$a}';
$string['templateseed:skipped'] = 'Template skipped: {$a}';
$string['templateseed:reset'] = 'Template reset: {$a}';
$string['templateseed:validationok'] = 'Template validation OK: {$a}';
$string['templateseed:error_missingkey'] = 'Template definition is missing a key.';
$string['templateseed:error_missingname'] = 'Template definition is missing a name.';
$string['templateseed:error_duplicatekey'] = 'Duplicate template key: {$a}';
$string['templateseed:error_invalidcomponent'] = 'Invalid template component: {$a}';
$string['templateseed:warning_existingmanual'] = 'Existing template is not marked as UCKK seeded: {$a}';

// Report seeding.
$string['reportseed:created'] = 'Report created: {$a}';
$string['reportseed:updated'] = 'Report updated: {$a}';
$string['reportseed:skipped'] = 'Report skipped: {$a}';
$string['reportseed:reset'] = 'Report reset: {$a}';
$string['reportseed:validationok'] = 'Report validation OK: {$a}';
$string['reportseed:error_missingkey'] = 'Report definition is missing a key.';
$string['reportseed:error_missingname'] = 'Report definition is missing a name.';
$string['reportseed:error_missingcomponent'] = 'Report definition is missing a component.';
$string['reportseed:error_missingcapability'] = 'Report definition is missing a capability: {$a}';
$string['reportseed:error_invalidenabled'] = 'Report definition has an invalid enabled flag: {$a}';
$string['reportseed:warning_componentmissing'] = 'Report component not found: {$a}';

// Generic validation messages.
$string['validation:error'] = 'Validation error';
$string['validation:warning'] = 'Validation warning';
$string['validation:success'] = 'Validation success';
$string['validation:blocker'] = 'Validation blocker';
$string['validation:missingfield'] = 'Missing field: {$a}';
$string['validation:invalidfield'] = 'Invalid field: {$a}';
$string['validation:duplicatekey'] = 'Duplicate key: {$a}';
$string['validation:unknownpreset'] = 'Unknown preset: {$a}';
$string['validation:unknowncomponent'] = 'Unknown component: {$a}';
$string['validation:invalidschema'] = 'Invalid schema.';
$string['validation:unsupportedversion'] = 'Unsupported preset version: {$a}';

// Export.
$string['exportformat'] = 'Export format';
$string['exportpretty'] = 'Pretty JSON';
$string['exportincludeinactive'] = 'Include inactive items';
$string['exportdestination'] = 'Export destination';
$string['exportoverwrite'] = 'Overwrite existing files';
$string['exportcompleted'] = 'Export completed.';
$string['exportfailed'] = 'Export failed.';
$string['exportfilename'] = 'Export filename';
$string['exportdirectory'] = 'Export directory';

// Privacy.
$string['privacy:path:runs'] = 'UCKK seed runs';
$string['privacy:path:logs'] = 'UCKK seed logs';
$string['privacy:metadata:tool_uckkseed_run'] = 'Stores UCKK seed tool execution runs.';
$string['privacy:metadata:tool_uckkseed_run:action'] = 'Seed action.';
$string['privacy:metadata:tool_uckkseed_run:mode'] = 'Execution mode.';
$string['privacy:metadata:tool_uckkseed_run:status'] = 'Execution status.';
$string['privacy:metadata:tool_uckkseed_run:presets'] = 'Preset ids selected for the run.';
$string['privacy:metadata:tool_uckkseed_run:components'] = 'Components selected for the run.';
$string['privacy:metadata:tool_uckkseed_run:summary'] = 'Execution summary.';
$string['privacy:metadata:tool_uckkseed_run:userid'] = 'Administrator user id.';
$string['privacy:metadata:tool_uckkseed_run:timecreated'] = 'Creation timestamp.';
$string['privacy:metadata:tool_uckkseed_run:timemodified'] = 'Last modification timestamp.';
$string['privacy:metadata:tool_uckkseed_log'] = 'Stores UCKK seed tool execution log entries.';
$string['privacy:metadata:tool_uckkseed_log:runid'] = 'Seed run id.';
$string['privacy:metadata:tool_uckkseed_log:severity'] = 'Log severity.';
$string['privacy:metadata:tool_uckkseed_log:message'] = 'Log message.';
$string['privacy:metadata:tool_uckkseed_log:preset'] = 'Preset id.';
$string['privacy:metadata:tool_uckkseed_log:component'] = 'Component name.';
$string['privacy:metadata:tool_uckkseed_log:targettype'] = 'Target type.';
$string['privacy:metadata:tool_uckkseed_log:targetkey'] = 'Target key.';
$string['privacy:metadata:tool_uckkseed_log:metadata'] = 'Additional execution metadata.';
$string['privacy:metadata:tool_uckkseed_log:timecreated'] = 'Creation timestamp.';

// Notifications.
$string['notification:seedqueued'] = 'UCKK seed task queued.';
$string['notification:seedrunning'] = 'UCKK seed task is running.';
$string['notification:seedcompleted'] = 'UCKK seed task completed.';
$string['notification:seedfailed'] = 'UCKK seed task failed.';
$string['notification:validationcompleted'] = 'UCKK validation completed.';
$string['notification:resetcompleted'] = 'UCKK reset completed.';

// Help.
$string['help:seed'] = 'Seed creates or updates canonical UCKK Moodle objects from the selected presets.';
$string['help:reset'] = 'Reset removes or reverts only content that was explicitly marked as UCKK seeded.';
$string['help:validate'] = 'Validation checks preset files and current Moodle state without changing data.';
$string['help:exportpreset'] = 'Export writes the current canonical seed state to JSON preset files.';
$string['help:dryrun'] = 'Dry-run mode reports planned changes without applying them.';
$string['help:rollbackplan'] = 'Rollback-plan mode reports what would be needed to reverse seeded changes.';

// Additional strings used by templates or output renderers.
$string['none'] = 'None';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['unknown'] = 'Unknown';
$string['notavailable'] = 'Not available';
$string['continue'] = 'Continue';
$string['cancel'] = 'Cancel';
$string['back'] = 'Back';
$string['download'] = 'Download';
$string['viewdetails'] = 'View details';
$string['showmore'] = 'Show more';
$string['showless'] = 'Show less';
$string['filters'] = 'Filters';
$string['target'] = 'Target';
$string['targettype'] = 'Target type';
$string['targetkey'] = 'Target key';
$string['component'] = 'Component';
$string['source'] = 'Source';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';
$string['startedat'] = 'Started at';
$string['finishedat'] = 'Finished at';
$string['duration'] = 'Duration';
$string['userid'] = 'User ID';
$string['username'] = 'Username';
$string['json'] = 'JSON';
$string['prettyjson'] = 'Pretty JSON';
$string['rawjson'] = 'Raw JSON';
$string['viewjson'] = 'View JSON';

// Domain safeguards.
$string['safeguard:noacademicjudgement'] = 'The seed tool must not make academic judgement decisions.';
$string['safeguard:noautocertification'] = 'The seed tool must not automatically certify competencies.';
$string['safeguard:noautobadgeaward'] = 'The seed tool must not automatically award badges.';
$string['safeguard:nointegrityoverride'] = 'The seed tool must not override integrity or archive validation.';
$string['safeguard:noarbitrarydelete'] = 'The seed tool must not delete arbitrary Moodle data.';

// Common errors.
$string['error:invalidjson'] = 'Invalid JSON: {$a}';
$string['error:filenotfound'] = 'File not found: {$a}';
$string['error:filenotreadable'] = 'File not readable: {$a}';
$string['error:directorynotwritable'] = 'Directory not writable: {$a}';
$string['error:operationnotallowed'] = 'Operation not allowed.';
$string['error:missingrequiredfield'] = 'Missing required field: {$a}';
$string['error:invalididentifier'] = 'Invalid identifier: {$a}';
$string['error:unsupportedpreset'] = 'Unsupported preset: {$a}';
$string['error:handlernotfound'] = 'No handler found for preset: {$a}';
$string['error:moodlerecordnotfound'] = 'Moodle record not found: {$a}';
$string['error:moodleapierror'] = 'Moodle API error: {$a}';

// Success messages.
$string['success:operationcompleted'] = 'Operation completed successfully.';
$string['success:validationpassed'] = 'Validation passed.';
$string['success:dryruncompleted'] = 'Dry run completed.';
$string['success:reportgenerated'] = 'Report generated.';

// Misc.
$string['managedbyuckkseed'] = 'Managed by UCKK seed';
$string['seededcontent'] = 'Seeded content';
$string['manualcontent'] = 'Manual content';
$string['canonical'] = 'Canonical';
$string['noncanonical'] = 'Non-canonical';
$string['idnumber'] = 'ID number';
$string['shortname'] = 'Shortname';
$string['fullname'] = 'Full name';
$string['description'] = 'Description';
$string['name'] = 'Name';
$string['key'] = 'Key';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';
$string['priority'] = 'Priority';
$string['sortorder'] = 'Sort order';
$string['language'] = 'Language';
$string['version'] = 'Version';
$string['schema'] = 'Schema';
$string['items'] = 'Items';
$string['item'] = 'Item';
$string['type'] = 'Type';
$string['context'] = 'Context';
$string['path'] = 'Path';
$string['parent'] = 'Parent';
$string['children'] = 'Children';

// Component labels.
$string['component:tool_uckkseed'] = 'UCKK seed tool';
$string['component:local_uckk'] = 'UCKK local plugin';
$string['component:mod_uckkarchive'] = 'UCKK archive activity';
$string['component:mod_uckkassembly'] = 'UCKK assembly activity';
$string['component:mod_uckkchallenge'] = 'UCKK challenge activity';
$string['component:theme_uckk'] = 'UCKK theme';
$string['component:report_uckk'] = 'UCKK reports';
$string['component:block_uckk_dashboard'] = 'UCKK dashboard block';
$string['component:ai_provider_uckk'] = 'UCKK AI provider';

// Scheduled tasks.
$string['task_seed_distribution'] = 'Seed UCKK distribution';
$string['task_cleanup_logs'] = 'Clean up old UCKK seed logs';

// Output card labels.
$string['card:preset'] = 'Preset';
$string['card:status'] = 'Status';
$string['card:counts'] = 'Counts';
$string['card:messages'] = 'Messages';
$string['card:metadata'] = 'Metadata';
$string['card:actions'] = 'Actions';

// Behat.
$string['behat:seedtoolpage'] = 'UCKK seed tool page';
$string['behat:seedformsubmitted'] = 'UCKK seed form submitted';
$string['behat:validationvisible'] = 'UCKK validation report is visible';
$string['behat:summaryvisible'] = 'UCKK seed summary is visible';
$string['presetexportpathmissing'] = 'Export destination is missing.';
$string['presetfilenotfound'] = 'Preset file not found: {$a}';
$string['presetfilenotreadable'] = 'Preset file is not readable: {$a}';
$string['presetjsoninvalid'] = 'Preset JSON is invalid: {$a->preset} ({$a->error})';
$string['presetmissingitems'] = 'Preset is missing an items array: {$a}';
$string['presetmismatch'] = 'Preset file does not match expected preset id. Expected {$a->expected}, found {$a->found}.';
$string['presetvalidationfailed'] = 'Preset validation failed: {$a}';
$string['presetexportfailedmessage'] = 'Preset export failed: {$a}';
$string['presetexportunsupported'] = 'Preset export is not supported for this preset: {$a}';
$string['presethandlerfailed'] = 'Preset handler failed: {$a->preset} ({$a->error})';
$string['presetmissinghandler'] = 'No seed handler is available for preset: {$a}';
$string['resetnotconfirmed'] = 'Reset was not confirmed.';
$string['notimplemented'] = 'Not implemented yet.';
$string['moodlerecordmissing'] = 'Required Moodle record is missing: {$a}';
$string['invalidpermission'] = 'Invalid permission: {$a}';
$string['rolenotfound'] = 'Role not found: {$a}';
$string['capabilityunknown'] = 'Unknown capability: {$a}';
$string['componentmissing'] = 'Component not installed: {$a}';
$string['roleprotected'] = 'Protected role cannot be reset: {$a}';
$string['invalidcomponent'] = 'Invalid component: {$a}';
$string['componentnotresettable'] = 'Component cannot be reset: {$a}';
$string['resetunsupported'] = 'Reset is not supported for this scope: {$a}';
$string['unsafeoutputpath'] = 'Unsafe export output path: {$a}';
$string['exportwritedenied'] = 'Export write denied: {$a}';
$string['exporttargetexists'] = 'Export target already exists: {$a}';
$string['exportwritefailed'] = 'Export write failed: {$a}';
$string['exportunsupported'] = 'Export is not supported for this preset: {$a}';
$string['invalidformat'] = 'Invalid format: {$a}';
$string['invalidvisibility'] = 'Invalid visibility: {$a}';
$string['invalidboolean'] = 'Invalid boolean value: {$a}';
$string['invalidarray'] = 'Expected array value: {$a}';
$string['invalidstring'] = 'Expected non-empty string: {$a}';
$string['invalidint'] = 'Expected integer value: {$a}';
$string['validationok'] = 'Validation OK: {$a}';
$string['resetok'] = 'Reset OK: {$a}';
$string['seedok'] = 'Seed OK: {$a}';

// Category seed detailed strings.
$string['categoryseed:error_missingkey'] = 'Category definition is missing a key.';
$string['categoryseed:error_duplicatekey'] = 'Duplicate category key: {$a}';
$string['categoryseed:error_invalidvisibility'] = 'Category has invalid visibility: {$a}';
$string['categoryseed:error_invalidparent'] = 'Category has an invalid parent: {$a}';

// Course seed detailed strings.
$string['courseseed:error_missingkey'] = 'Course definition is missing a key.';
$string['courseseed:error_duplicatekey'] = 'Duplicate course key: {$a}';
$string['courseseed:error_invalidformat'] = 'Course has an invalid format: {$a}';
$string['courseseed:error_invalidvisibility'] = 'Course has invalid visibility: {$a}';
$string['courseseed:error_templateunsupported'] = 'Course template is not supported yet: {$a}';
$string['courseseed:warning_missingtemplate'] = 'Course template not found: {$a}';

// Cohort seed detailed strings.
$string['cohortseed:error_missingkey'] = 'Cohort definition is missing a key.';
$string['cohortseed:error_duplicatekey'] = 'Duplicate cohort key: {$a}';
$string['cohortseed:error_invalidvisibility'] = 'Cohort has invalid visibility: {$a}';
$string['cohortseed:error_invalididnumber'] = 'Cohort has invalid idnumber: {$a}';

// Role seed detailed strings.
$string['roleseed:error_missingkey'] = 'Role definition is missing a key.';
$string['roleseed:error_duplicatekey'] = 'Duplicate role key: {$a}';
$string['roleseed:error_invalidcapability'] = 'Role has an invalid capability: {$a}';
$string['roleseed:error_invalidcontextlevel'] = 'Role has an invalid context level: {$a}';
$string['roleseed:error_invalidpermission'] = 'Role has an invalid permission: {$a}';

// Capability seed detailed strings.
$string['capabilityseed:error_missingkey'] = 'Capability definition is missing a key.';
$string['capabilityseed:error_duplicatekey'] = 'Duplicate capability key: {$a}';
$string['capabilityseed:error_invalidcontextlevel'] = 'Capability has an invalid context level: {$a}';

// Competency seed detailed strings.
$string['competencyseed:error_missingkey'] = 'Competency definition is missing a key.';
$string['competencyseed:error_duplicatekey'] = 'Duplicate competency key: {$a}';
$string['competencyseed:error_invalidscale'] = 'Competency has an invalid scale: {$a}';
$string['competencyseed:error_invalidrule'] = 'Competency has an invalid rule: {$a}';
$string['competencyseed:error_missingparent'] = 'Competency parent not found: {$a}';

// Badge seed detailed strings.
$string['badgeseed:error_missingkey'] = 'Badge definition is missing a key.';
$string['badgeseed:error_duplicatekey'] = 'Duplicate badge key: {$a}';
$string['badgeseed:error_invalidcriteria'] = 'Badge has invalid criteria: {$a}';
$string['badgeseed:error_missingcompetency'] = 'Badge references a missing competency: {$a}';
$string['badgeseed:error_missingcourse'] = 'Badge references a missing course: {$a}';

// Template seed detailed strings.
$string['templateseed:error_missingcomponent'] = 'Template definition is missing a component.';
$string['templateseed:error_missingtemplate'] = 'Template definition is missing template data.';
$string['templateseed:error_invalidtemplate'] = 'Template data is invalid: {$a}';

// Report seed detailed strings.
$string['reportseed:error_duplicatekey'] = 'Duplicate report key: {$a}';
$string['reportseed:error_invalidcapability'] = 'Report definition has an invalid capability: {$a}';
$string['reportseed:error_missingsource'] = 'Report definition is missing a source.';
$string['reportseed:warning_unknownsource'] = 'Unknown report source: {$a}';