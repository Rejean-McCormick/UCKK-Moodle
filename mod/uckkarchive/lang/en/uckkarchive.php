<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * English language strings for the UCKK Archive activity module.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// -----------------------------------------------------------------------------
// Plugin identity.
// -----------------------------------------------------------------------------

$string['pluginname'] = 'UCKK Archive';
$string['pluginadministration'] = 'UCKK Archive administration';
$string['modulename'] = 'UCKK Archive';
$string['modulenameplural'] = 'UCKK Archives';
$string['modulename_help'] = 'The UCKK Archive activity stores evidence, decisions, course work, Kristals, portfolio items, integrity summaries, version records, and exportable archive packages with provenance, visibility, validation, and revision history.';
$string['modulename_link'] = 'mod/uckkarchive/view';
$string['uckkarchive:addinstance'] = 'Add a new UCKK Archive activity';
$string['uckkarchive:view'] = 'View UCKK Archive';
$string['uckkarchive:additem'] = 'Add archive item';
$string['uckkarchive:validateitem'] = 'Validate archive item';
$string['uckkarchive:reviseitem'] = 'Revise archive item';
$string['uckkarchive:versionitem'] = 'Version archive item';
$string['uckkarchive:viewrestricted'] = 'View restricted archive items';
$string['uckkarchive:export'] = 'Export archive packages';

// -----------------------------------------------------------------------------
// General labels.
// -----------------------------------------------------------------------------

$string['activity'] = 'Activity';
$string['actions'] = 'Actions';
$string['add'] = 'Add';
$string['additem'] = 'Add archive item';
$string['archive'] = 'Archive';
$string['archiveactivity'] = 'Archive activity';
$string['archiveitem'] = 'Archive item';
$string['archiveitems'] = 'Archive items';
$string['archiveitemdetails'] = 'Archive item details';
$string['archiveoverview'] = 'Archive overview';
$string['archivepolicy'] = 'Archive policy';
$string['archives'] = 'Archives';
$string['backtoarchive'] = 'Back to archive';
$string['backtoarchives'] = 'Back to archives';
$string['cancel'] = 'Cancel';
$string['close'] = 'Close';
$string['confirm'] = 'Confirm';
$string['continue'] = 'Continue';
$string['course'] = 'Course';
$string['created'] = 'Created';
$string['createdby'] = 'Created by';
$string['description'] = 'Description';
$string['details'] = 'Details';
$string['download'] = 'Download';
$string['edit'] = 'Edit';
$string['empty'] = 'Nothing to display.';
$string['export'] = 'Export';
$string['files'] = 'Files';
$string['filter'] = 'Filter';
$string['item'] = 'Item';
$string['items'] = 'Items';
$string['metadata'] = 'Metadata';
$string['modified'] = 'Modified';
$string['modifiedby'] = 'Modified by';
$string['name'] = 'Name';
$string['none'] = 'None';
$string['notes'] = 'Notes';
$string['open'] = 'Open';
$string['owner'] = 'Owner';
$string['preview'] = 'Preview';
$string['provenance'] = 'Provenance';
$string['reason'] = 'Reason';
$string['records'] = 'Records';
$string['restricted'] = 'Restricted';
$string['restricted_integrity'] = 'Restricted integrity';
$string['revision'] = 'Revision';
$string['revisions'] = 'Revisions';
$string['save'] = 'Save';
$string['search'] = 'Search';
$string['source'] = 'Source';
$string['status'] = 'Status';
$string['submit'] = 'Submit';
$string['summary'] = 'Summary';
$string['title'] = 'Title';
$string['type'] = 'Type';
$string['validation'] = 'Validation';
$string['visibility'] = 'Visibility';
$string['version'] = 'Version';
$string['versionno'] = 'Version {$a}';
$string['view'] = 'View';
$string['viewdetails'] = 'View details';

// -----------------------------------------------------------------------------
// Activity instance form.
// -----------------------------------------------------------------------------

$string['archivename'] = 'Archive name';
$string['archivename_help'] = 'Name of this UCKK Archive activity.';
$string['archiveintro'] = 'Archive introduction';
$string['archivecode'] = 'Archive code';
$string['archivecode_help'] = 'Stable internal code for this archive. Use letters, numbers, underscores, or hyphens.';
$string['archivecontext'] = 'Archive context';
$string['archivecontext_help'] = 'Describe what this archive is meant to preserve and who may use it.';
$string['archivepurpose'] = 'Archive purpose';
$string['archivepurpose_help'] = 'Explain why this archive exists: evidence, decisions, portfolio, course memory, Kristals, integrity summaries, or institutional memory.';
$string['defaultvisibility'] = 'Default visibility';
$string['defaultvisibility_help'] = 'Default visibility applied to new items unless the item form overrides it.';
$string['allowpublicitems'] = 'Allow public items';
$string['allowpublicitems_help'] = 'Allow manually validated archive items to be made public.';
$string['requirevalidation'] = 'Require validation before publication';
$string['requirevalidation_help'] = 'Require an Archiviste or authorised reviewer to validate items before they are treated as verified or public.';
$string['allowkristals'] = 'Allow Kristals';
$string['allowkristals_help'] = 'Allow this archive to store Kristal records: distilled learning, decision, proof, or memory fragments.';
$string['allowexports'] = 'Allow exports';
$string['allowexports_help'] = 'Allow authorised users to export archive packages.';
$string['completionrequirevalidateditem'] = 'Student must have a validated archive item';
$string['completionrequireitem'] = 'Student must add an archive item';
$string['completionrequirekristal'] = 'Student must create a Kristal';

// -----------------------------------------------------------------------------
// Settings.
// -----------------------------------------------------------------------------

$string['settings:general'] = 'General archive settings';
$string['settings:general_desc'] = 'Configure UCKK Archive defaults.';
$string['settings:defaultvisibility'] = 'Default archive visibility';
$string['settings:defaultvisibility_desc'] = 'Default visibility used when a new archive item does not specify a visibility.';
$string['settings:requirevalidation'] = 'Require validation';
$string['settings:requirevalidation_desc'] = 'Require validation before archive items become verified or public.';
$string['settings:allowpublicarchives'] = 'Allow public archive items';
$string['settings:allowpublicarchives_desc'] = 'Allow archive items to become publicly visible after manual validation.';
$string['settings:allowrestrictedintegrity'] = 'Allow restricted integrity archives';
$string['settings:allowrestrictedintegrity_desc'] = 'Allow archive items to be marked as restricted to users with integrity or restricted archive capabilities.';
$string['settings:enablekristals'] = 'Enable Kristals';
$string['settings:enablekristals_desc'] = 'Enable Kristal creation and display in UCKK Archives.';
$string['settings:enableexports'] = 'Enable export packages';
$string['settings:enableexports_desc'] = 'Allow authorised users to generate export packages.';
$string['settings:retentiondays'] = 'Default retention period';
$string['settings:retentiondays_desc'] = 'Default retention period in days. Use 0 for no automatic expiry.';
$string['settings:maxexportitems'] = 'Maximum items per export';
$string['settings:maxexportitems_desc'] = 'Maximum number of archive items included in one export package.';
$string['settings:provenance'] = 'Provenance settings';
$string['settings:provenance_desc'] = 'Configure archive provenance and validation behaviour.';
$string['settings:requireprovenance'] = 'Require provenance';
$string['settings:requireprovenance_desc'] = 'Require a provenance statement for every archive item.';
$string['settings:requirevalidationnotes'] = 'Require validation notes';
$string['settings:requirevalidationnotes_desc'] = 'Require validators to provide notes when validating, rejecting, or invalidating an item.';

// -----------------------------------------------------------------------------
// Item form.
// -----------------------------------------------------------------------------

$string['archiveitemform'] = 'Archive item form';
$string['itemtitle'] = 'Item title';
$string['itemtitle_help'] = 'Clear title for the archive item.';
$string['itemsummary'] = 'Item summary';
$string['itemsummary_help'] = 'Short summary safe for the selected visibility level.';
$string['itemcontent'] = 'Item content';
$string['itemcontent_help'] = 'Main archive content. Do not include restricted details unless the item visibility allows it.';
$string['itemtype'] = 'Item type';
$string['itemtype_help'] = 'Choose the type that best describes the archive item.';
$string['itemstatus'] = 'Item status';
$string['itemvisibility'] = 'Item visibility';
$string['itemvisibility_help'] = 'Controls who may see this archive item. Restricted integrity items require explicit capability.';
$string['itemprovenance'] = 'Item provenance';
$string['itemprovenance_help'] = 'Explain where the item comes from, who produced it, what was transformed, and what can be verified.';
$string['itemsource'] = 'Source';
$string['itemsource_help'] = 'Original source, system, activity, person, archive, or external reference for this item.';
$string['sourcecomponent'] = 'Source component';
$string['sourcecomponent_help'] = 'Moodle component that originated the item, when known.';
$string['sourceid'] = 'Source ID';
$string['sourceid_help'] = 'Record ID in the source component, when known.';
$string['sourceurl'] = 'Source URL';
$string['sourceurl_help'] = 'Optional URL pointing to the source.';
$string['sourceauthor'] = 'Source or author';
$string['sourceauthor_help'] = 'Person, group, system, activity, or archive source behind the item.';
$string['sourcedate'] = 'Source date';
$string['sourcedate_help'] = 'Date associated with the original source.';
$string['license'] = 'Licence';
$string['license_help'] = 'Licence or reuse condition attached to the item, when applicable.';
$string['tags'] = 'Tags';
$string['tags_help'] = 'Optional comma-separated tags for filtering and reporting.';
$string['files_help'] = 'Files attached to this archive item. Files inherit the item visibility unless a service applies a stricter rule.';
$string['prooffiles'] = 'Proof files';
$string['decisionattachments'] = 'Decision attachments';
$string['minutesfiles'] = 'Minutes files';
$string['kristalfiles'] = 'Kristal files';
$string['portfoliofiles'] = 'Portfolio files';
$string['integrityexports'] = 'Integrity exports';
$string['addarchiveitem'] = 'Add archive item';
$string['editarchiveitem'] = 'Edit archive item';
$string['archiveitemsaved'] = 'Archive item saved.';
$string['archiveitemcreated'] = 'Archive item created.';
$string['archiveitemupdated'] = 'Archive item updated.';
$string['cannotadditem'] = 'You cannot add archive items here.';
$string['cannotedititem'] = 'You cannot edit this archive item.';
$string['cannotviewitem'] = 'You cannot view this archive item.';
$string['missingarchiveitem'] = 'Archive item not found.';
$string['invaliditemtype'] = 'Invalid archive item type.';
$string['invaliditemstatus'] = 'Invalid archive item status.';
$string['invaliditemvisibility'] = 'Invalid archive item visibility.';
$string['provenancerequired'] = 'A provenance statement is required.';
$string['contentorsourcefilesrequired'] = 'Add content, a source URL, or at least one file.';

// -----------------------------------------------------------------------------
// Kristals.
// -----------------------------------------------------------------------------

$string['kristal'] = 'Kristal';
$string['kristals'] = 'Kristals';
$string['kristalform'] = 'Kristal form';
$string['addkristal'] = 'Add Kristal';
$string['editkristal'] = 'Edit Kristal';
$string['kristaltitle'] = 'Kristal title';
$string['kristaltitle_help'] = 'Short title for this Kristal.';
$string['kristalstatement'] = 'Kristal statement';
$string['kristalstatement_help'] = 'Distilled statement of learning, decision, proof, insight, or memory.';
$string['kristalcontext'] = 'Kristal context';
$string['kristalcontext_help'] = 'Explain the context from which this Kristal was distilled.';
$string['kristalproof'] = 'Kristal proof';
$string['kristalproof_help'] = 'Evidence or references supporting the Kristal.';
$string['kristaltype'] = 'Kristal type';
$string['kristalvisibility'] = 'Kristal visibility';
$string['kristalcreated'] = 'Kristal created.';
$string['kristalupdated'] = 'Kristal updated.';
$string['kristalempty'] = 'No Kristals are available.';
$string['cannotaddkristal'] = 'You cannot add Kristals here.';
$string['cannoteditkristal'] = 'You cannot edit this Kristal.';
$string['cannotviewkristal'] = 'You cannot view this Kristal.';
$string['invalidkristaltype'] = 'Invalid Kristal type.';

// -----------------------------------------------------------------------------
// Validation.
// -----------------------------------------------------------------------------

$string['validate'] = 'Validate';
$string['validateitem'] = 'Validate archive item';
$string['validationform'] = 'Validation form';
$string['validationstate'] = 'Validation state';
$string['validationnotes'] = 'Validation notes';
$string['validationnotes_help'] = 'Explain the validation decision, uncertainty, corrections required, or reason for rejection.';
$string['validationdecision'] = 'Validation decision';
$string['validationdecision_help'] = 'Choose how this archive item should be treated after review.';
$string['validatedby'] = 'Validated by';
$string['timevalidated'] = 'Validation time';
$string['archiveitemvalidated'] = 'Archive item validated.';
$string['archiveitemrejected'] = 'Archive item rejected.';
$string['archiveiteminvalidated'] = 'Archive item invalidated.';
$string['archiveitemcorrectionrequired'] = 'Correction required for archive item.';
$string['archiveitemcontested'] = 'Archive item contested.';
$string['cannotvalidateitem'] = 'You cannot validate this archive item.';
$string['validationrequiresnotes'] = 'Validation notes are required for this decision.';
$string['manualvalidationrequired'] = 'Manual validation required';
$string['publicvalidationrequired'] = 'Public archive items must be manually validated.';
$string['restrictedvalidationrequired'] = 'Restricted integrity archive items require explicit validation.';

$string['validationdecision:validate'] = 'Validate';
$string['validationdecision:reject'] = 'Reject';
$string['validationdecision:correction_required'] = 'Correction required';
$string['validationdecision:contest'] = 'Contest';
$string['validationdecision:invalidate'] = 'Invalidate';

// -----------------------------------------------------------------------------
// Revision and versioning.
// -----------------------------------------------------------------------------

$string['revise'] = 'Revise';
$string['reviseitem'] = 'Revise archive item';
$string['revisionform'] = 'Revision form';
$string['revisionreason'] = 'Revision reason';
$string['revisionreason_help'] = 'Explain why this revision is needed. Archive history must remain traceable.';
$string['revisionnotes'] = 'Revision notes';
$string['revisioncreated'] = 'Revision created.';
$string['archiveitemrevised'] = 'Archive item revised.';
$string['previousversion'] = 'Previous version';
$string['currentversion'] = 'Current version';
$string['versionhistory'] = 'Version history';
$string['versionrecord'] = 'Version record';
$string['cannotreviseitem'] = 'You cannot revise this archive item.';
$string['cannotversionitem'] = 'You cannot create a version record for this item.';
$string['revisionrequiresreason'] = 'A revision reason is required.';
$string['revisionnonsovereignnotice'] = 'A revision preserves archive history. It must not silently erase evidence or provenance.';

// -----------------------------------------------------------------------------
// Export.
// -----------------------------------------------------------------------------

$string['exportarchive'] = 'Export archive';
$string['exportform'] = 'Export form';
$string['exportpackage'] = 'Export package';
$string['exportpackages'] = 'Export packages';
$string['exportformat'] = 'Export format';
$string['exportscope'] = 'Export scope';
$string['exportvisibility'] = 'Export visibility';
$string['exportreason'] = 'Export reason';
$string['exportreason_help'] = 'Explain why this archive export is needed.';
$string['exportincludeproofs'] = 'Include proof files';
$string['exportincludeprovenance'] = 'Include provenance';
$string['exportincludehistory'] = 'Include revision history';
$string['exportincludeintegrity'] = 'Include integrity summaries';
$string['exportredactrestricted'] = 'Redact restricted details';
$string['exportredactrestricted_help'] = 'Remove or summarize restricted integrity details from the export package unless the viewer has explicit permission.';
$string['exportgenerated'] = 'Archive export generated.';
$string['archiveitemexported'] = 'Archive item exported.';
$string['cannotexportarchive'] = 'You cannot export this archive.';
$string['cannotexportitem'] = 'You cannot export this archive item.';
$string['exportrequiresreason'] = 'An export reason is required.';
$string['exportempty'] = 'No export packages are available.';
$string['downloadexport'] = 'Download export';
$string['exportqueued'] = 'Export queued.';
$string['exportfailed'] = 'Export failed.';
$string['exportsucceeded'] = 'Export completed.';

$string['exportformat:json'] = 'JSON';
$string['exportformat:zip'] = 'ZIP package';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:pdf'] = 'PDF summary';
$string['exportscope:item'] = 'Single item';
$string['exportscope:archive'] = 'Whole archive';
$string['exportscope:course'] = 'Course archive';
$string['exportscope:user'] = 'User portfolio archive';
$string['exportscope:integrity'] = 'Integrity archive';

// -----------------------------------------------------------------------------
// Item types.
// -----------------------------------------------------------------------------

$string['itemtype:proof'] = 'Proof';
$string['itemtype:decision'] = 'Decision';
$string['itemtype:course_work'] = 'Course work';
$string['itemtype:challenge_result'] = 'Challenge result';
$string['itemtype:assembly_minutes'] = 'Assembly minutes';
$string['itemtype:integrity_case_summary'] = 'Integrity case summary';
$string['itemtype:kristal'] = 'Kristal';
$string['itemtype:reflection'] = 'Reflection';
$string['itemtype:portfolio_item'] = 'Portfolio item';
$string['itemtype:version_record'] = 'Version record';
$string['itemtype:archive_item'] = 'Archive item';

// -----------------------------------------------------------------------------
// Kristal types.
// -----------------------------------------------------------------------------

$string['kristaltype:learning'] = 'Learning Kristal';
$string['kristaltype:decision'] = 'Decision Kristal';
$string['kristaltype:proof'] = 'Proof Kristal';
$string['kristaltype:reflection'] = 'Reflection Kristal';
$string['kristaltype:method'] = 'Method Kristal';
$string['kristaltype:governance'] = 'Governance Kristal';
$string['kristaltype:integrity'] = 'Integrity Kristal';
$string['kristaltype:memory'] = 'Memory Kristal';

// -----------------------------------------------------------------------------
// Proof types.
// -----------------------------------------------------------------------------

$string['prooftype'] = 'Proof type';
$string['prooftype:text'] = 'Text';
$string['prooftype:file'] = 'File';
$string['prooftype:url'] = 'URL';
$string['prooftype:dataset'] = 'Dataset';
$string['prooftype:image'] = 'Image';
$string['prooftype:video'] = 'Video';
$string['prooftype:testimony'] = 'Testimony';
$string['prooftype:observation'] = 'Observation';
$string['prooftype:ai_log'] = 'AI log';
$string['prooftype:decision_record'] = 'Decision record';
$string['prooftype:archive_item'] = 'Archive item';

// -----------------------------------------------------------------------------
// Statuses.
// -----------------------------------------------------------------------------

$string['status:draft'] = 'Draft';
$string['status:active'] = 'Active';
$string['status:hidden'] = 'Hidden';
$string['status:pending'] = 'Pending';
$string['status:pending_review'] = 'Pending review';
$string['status:validated'] = 'Validated';
$string['status:rejected'] = 'Rejected';
$string['status:correction_required'] = 'Correction required';
$string['status:contested'] = 'Contested';
$string['status:invalidated'] = 'Invalidated';
$string['status:closed'] = 'Closed';
$string['status:archived'] = 'Archived';
$string['status:cancelled'] = 'Cancelled';

$string['statuslabel:draft'] = 'Draft';
$string['statuslabel:active'] = 'Active';
$string['statuslabel:hidden'] = 'Hidden';
$string['statuslabel:pending'] = 'Pending';
$string['statuslabel:pendingreview'] = 'Pending review';
$string['statuslabel:validated'] = 'Validated';
$string['statuslabel:rejected'] = 'Rejected';
$string['statuslabel:correctionrequired'] = 'Correction required';
$string['statuslabel:contested'] = 'Contested';
$string['statuslabel:invalidated'] = 'Invalidated';
$string['statuslabel:closed'] = 'Closed';
$string['statuslabel:archived'] = 'Archived';
$string['statuslabel:cancelled'] = 'Cancelled';

// -----------------------------------------------------------------------------
// Visibility.
// -----------------------------------------------------------------------------

$string['visibility:private'] = 'Private';
$string['visibility:user'] = 'User';
$string['visibility:group'] = 'Group';
$string['visibility:course'] = 'Course';
$string['visibility:cohort'] = 'Cohort';
$string['visibility:program'] = 'Program';
$string['visibility:institution'] = 'Institution';
$string['visibility:institutional'] = 'Institutional';
$string['visibility:public'] = 'Public';
$string['visibility:restricted'] = 'Restricted';
$string['visibility:restricted_integrity'] = 'Restricted integrity';
$string['visibility:hidden'] = 'Hidden';
$string['visibility:archived'] = 'Archived';

$string['visibility_help:private'] = 'Visible only to authorised owners and reviewers.';
$string['visibility_help:course'] = 'Visible within the course context to users with permission.';
$string['visibility_help:program'] = 'Visible within the related UCKK program.';
$string['visibility_help:institution'] = 'Visible to authorised institutional viewers.';
$string['visibility_help:public'] = 'Public only after manual validation.';
$string['visibility_help:restricted_integrity'] = 'Visible only to users with restricted archive or integrity permissions.';

// -----------------------------------------------------------------------------
// Provenance.
// -----------------------------------------------------------------------------

$string['provenance:human'] = 'Human';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:imported'] = 'Imported';
$string['provenance:system'] = 'System';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:integrity'] = 'Integrity';

$string['provenancehash'] = 'Provenance hash';
$string['provenancehash_help'] = 'Optional hash used to detect or document provenance changes.';
$string['provenancepanel'] = 'Provenance panel';
$string['provenancesource'] = 'Provenance source';
$string['provenancestatement'] = 'Provenance statement';
$string['provenancestatement_help'] = 'Explain origin, authorship, transformations, uncertainty, and verification path.';
$string['provenanceverified'] = 'Provenance verified';
$string['provenanceunverified'] = 'Provenance unverified';
$string['provenancewarning'] = 'Provenance warning';

// -----------------------------------------------------------------------------
// Validation states.
// -----------------------------------------------------------------------------

$string['validationstate:unverified'] = 'Unverified';
$string['validationstate:human_reviewed'] = 'Human reviewed';
$string['validationstate:verified'] = 'Verified';
$string['validationstate:contested'] = 'Contested';
$string['validationstate:invalidated'] = 'Invalidated';
$string['validationstate:archived'] = 'Archived';

$string['validation:unverified'] = 'Unverified';
$string['validation:human_reviewed'] = 'Human reviewed';
$string['validation:verified'] = 'Verified';
$string['validation:contested'] = 'Contested';
$string['validation:invalidated'] = 'Invalidated';
$string['validation:archived'] = 'Archived';

// -----------------------------------------------------------------------------
// Archive policies.
// -----------------------------------------------------------------------------

$string['archivepolicy:none'] = 'No archive policy';
$string['archivepolicy:summary'] = 'Summary archive';
$string['archivepolicy:full'] = 'Full archive';
$string['archivepolicy:restricted_integrity'] = 'Restricted integrity archive';
$string['archivepolicy:portfolio'] = 'Portfolio archive';
$string['archivepolicy:institutional_memory'] = 'Institutional memory';

// -----------------------------------------------------------------------------
// Pages and panels.
// -----------------------------------------------------------------------------

$string['archiveview'] = 'Archive view';
$string['archiveviewempty'] = 'No archive items are available for this view.';
$string['archiveitemcard'] = 'Archive item card';
$string['kristalcard'] = 'Kristal card';
$string['proofcard'] = 'Proof card';
$string['validationpanel'] = 'Validation panel';
$string['archiveactions'] = 'Archive actions';
$string['itemactions'] = 'Item actions';
$string['kristalactions'] = 'Kristal actions';
$string['validationactions'] = 'Validation actions';
$string['exportactions'] = 'Export actions';
$string['viewarchiveitem'] = 'View archive item';
$string['viewkristal'] = 'View Kristal';
$string['viewprovenance'] = 'View provenance';
$string['viewrevisionhistory'] = 'View revision history';
$string['viewvalidation'] = 'View validation';
$string['viewexport'] = 'View export';
$string['emptyarchive'] = 'This archive has no visible items.';
$string['emptykristals'] = 'This archive has no visible Kristals.';
$string['emptyproofs'] = 'No proof records are available.';
$string['emptyrevisions'] = 'No revisions are available.';
$string['emptyexports'] = 'No exports are available.';
$string['restrictednotice'] = 'This item contains restricted archive data.';
$string['publicnotice'] = 'Public archive visibility requires manual validation.';
$string['archivenonsovereignnotice'] = 'Archive records preserve memory and evidence. Validation, integrity decisions, public visibility, and exports require authorised human review.';
$string['archivegovernancenotice'] = 'The UCKK Archive preserves evidence, decisions, Kristals, portfolios, and version history. It must not be used to silently erase evidence or bypass integrity review.';

// -----------------------------------------------------------------------------
// Controller messages.
// -----------------------------------------------------------------------------

$string['invalidarchiveaction'] = 'Invalid archive action.';
$string['invalidvalidationaction'] = 'Invalid validation action.';
$string['invalidexportaction'] = 'Invalid export action.';
$string['invalidrevisionaction'] = 'Invalid revision action.';
$string['invalidvisibility'] = 'Invalid visibility.';
$string['invalidstatus'] = 'Invalid status.';
$string['invalidprovenance'] = 'Invalid provenance.';
$string['cannotviewarchive'] = 'You cannot view this archive.';
$string['cannotviewrestricted'] = 'You cannot view restricted archive data.';
$string['cannotmanagearchive'] = 'You cannot manage this archive.';
$string['cannotdeleteitem'] = 'You cannot delete this archive item.';
$string['deleteitemnotallowed'] = 'Archive items must be revised or invalidated, not silently deleted.';
$string['itemnotfound'] = 'Archive item not found.';
$string['archivenotfound'] = 'Archive not found.';
$string['kristalnotfound'] = 'Kristal not found.';
$string['exportnotfound'] = 'Export package not found.';
$string['nopermission'] = 'You do not have permission to perform this archive action.';

// -----------------------------------------------------------------------------
// Events.
// -----------------------------------------------------------------------------

$string['eventarchiveitemcreated'] = 'Archive item created';
$string['eventarchiveitemvalidated'] = 'Archive item validated';
$string['eventarchiveitemrevised'] = 'Archive item revised';
$string['eventarchiveitemexported'] = 'Archive item exported';
$string['eventkristalcreated'] = 'Kristal created';
$string['eventkristalupdated'] = 'Kristal updated';
$string['eventarchiveviewed'] = 'Archive viewed';
$string['eventarchiveitemviewed'] = 'Archive item viewed';
$string['eventexportgenerated'] = 'Archive export generated';

// -----------------------------------------------------------------------------
// Scheduled tasks.
// -----------------------------------------------------------------------------

$string['task:validate_pending_items'] = 'Validate pending archive items';
$string['task:generate_archive_exports'] = 'Generate archive exports';
$string['task:cleanup_expired_exports'] = 'Clean up expired archive exports';

// -----------------------------------------------------------------------------
// External services and Ajax.
// -----------------------------------------------------------------------------

$string['service:get_archive_view'] = 'Get archive view';
$string['service:get_archive_item'] = 'Get archive item';
$string['service:save_archive_item'] = 'Save archive item';
$string['service:validate_archive_item'] = 'Validate archive item';
$string['service:revise_archive_item'] = 'Revise archive item';
$string['service:create_kristal'] = 'Create Kristal';
$string['service:generate_export'] = 'Generate archive export';

$string['archive:refreshing'] = 'Refreshing archive…';
$string['archive:refreshed'] = 'Archive refreshed.';
$string['archive:refreshfailed'] = 'Unable to refresh archive.';
$string['archive:saving'] = 'Saving archive item…';
$string['archive:saved'] = 'Archive item saved.';
$string['archive:savefailed'] = 'Unable to save archive item.';
$string['archive:validating'] = 'Validating archive item…';
$string['archive:validated'] = 'Archive item validated.';
$string['archive:validationfailed'] = 'Unable to validate archive item.';
$string['archive:exporting'] = 'Generating archive export…';
$string['archive:exported'] = 'Archive export generated.';
$string['archive:exportfailed'] = 'Unable to generate archive export.';

$string['kristal:refreshing'] = 'Refreshing Kristal…';
$string['kristal:refreshed'] = 'Kristal refreshed.';
$string['kristal:refreshfailed'] = 'Unable to refresh Kristal.';
$string['kristal:saving'] = 'Saving Kristal…';
$string['kristal:saved'] = 'Kristal saved.';
$string['kristal:savefailed'] = 'Unable to save Kristal.';

// -----------------------------------------------------------------------------
// Completion.
// -----------------------------------------------------------------------------

$string['completiondetail:items'] = 'Add archive item';
$string['completiondetail:validateditems'] = 'Have validated archive item';
$string['completiondetail:kristals'] = 'Create Kristal';
$string['completionitems'] = 'Archive items required';
$string['completionvalidateditems'] = 'Validated archive items required';
$string['completionkristals'] = 'Kristals required';

// -----------------------------------------------------------------------------
// Privacy.
// -----------------------------------------------------------------------------

$string['privacy:metadata'] = 'The UCKK Archive activity stores archive records, evidence, provenance, validation decisions, revisions, Kristals, and export records.';
$string['privacy:metadata:uckkarchive'] = 'Archive activity instance settings.';
$string['privacy:metadata:uckkarchive_item'] = 'Archive items created or validated by users.';
$string['privacy:metadata:uckkarchive_item:userid'] = 'The user associated with the archive item.';
$string['privacy:metadata:uckkarchive_item:createdby'] = 'The user who created the archive item.';
$string['privacy:metadata:uckkarchive_item:modifiedby'] = 'The user who last modified the archive item.';
$string['privacy:metadata:uckkarchive_item:title'] = 'The archive item title.';
$string['privacy:metadata:uckkarchive_item:summary'] = 'The archive item summary.';
$string['privacy:metadata:uckkarchive_item:content'] = 'The archive item content.';
$string['privacy:metadata:uckkarchive_item:status'] = 'The archive item status.';
$string['privacy:metadata:uckkarchive_item:visibility'] = 'The archive item visibility.';
$string['privacy:metadata:uckkarchive_item:metadata'] = 'Additional archive item metadata.';
$string['privacy:metadata:uckkarchive_kristal'] = 'Kristal records created from archive items or learning evidence.';
$string['privacy:metadata:uckkarchive_proof'] = 'Proof records associated with archive items.';
$string['privacy:metadata:uckkarchive_prov'] = 'Provenance records associated with archive items.';
$string['privacy:metadata:uckkarchive_rev'] = 'Revision records associated with archive items.';
$string['privacy:metadata:uckkarchive_export'] = 'Export package records.';
$string['privacy:metadata:files'] = 'Files attached to archive items, proofs, decisions, minutes, Kristals, portfolios, or integrity exports.';
$string['privacy:path:archives'] = 'UCKK archive records';
$string['privacy:path:kristals'] = 'UCKK archive Kristals';
$string['privacy:path:proofs'] = 'UCKK archive proofs';
$string['privacy:path:revisions'] = 'UCKK archive revisions';
$string['privacy:path:exports'] = 'UCKK archive exports';

// -----------------------------------------------------------------------------
// Backup and restore.
// -----------------------------------------------------------------------------

$string['backupincludeitems'] = 'Include archive items';
$string['backupincludeproofs'] = 'Include proof files';
$string['backupincludekristals'] = 'Include Kristals';
$string['backupincludeprovenance'] = 'Include provenance';
$string['backupincluderevisions'] = 'Include revision history';
$string['restorearchiveitems'] = 'Restore archive items';

// -----------------------------------------------------------------------------
// Errors.
// -----------------------------------------------------------------------------

$string['error:missingcontext'] = 'Missing archive context.';
$string['error:missingcourse'] = 'Missing course.';
$string['error:missingcm'] = 'Missing course module.';
$string['error:missingitem'] = 'Missing archive item.';
$string['error:missingarchive'] = 'Missing archive.';
$string['error:missingkristal'] = 'Missing Kristal.';
$string['error:missingexport'] = 'Missing export package.';
$string['error:invalidjson'] = 'Invalid JSON metadata.';
$string['error:invalidsource'] = 'Invalid source reference.';
$string['error:invalidfilearea'] = 'Invalid archive file area.';
$string['error:restricted'] = 'This archive item is restricted.';
$string['error:publicrequiresvalidation'] = 'Public archive items require manual validation.';
$string['error:cannotautomatevalidation'] = 'Archive validation cannot be automated.';
$string['error:cannotdeletehistory'] = 'Archive history cannot be silently deleted.';
$string['error:exporttoolarge'] = 'This export package is too large.';
$string['error:nothingtoexport'] = 'There is nothing to export.';

// -----------------------------------------------------------------------------
// File areas.
// -----------------------------------------------------------------------------

$string['filearea:proof_files'] = 'Proof files';
$string['filearea:decision_attachments'] = 'Decision attachments';
$string['filearea:minutes_files'] = 'Minutes files';
$string['filearea:kristal_files'] = 'Kristal files';
$string['filearea:portfolio_files'] = 'Portfolio files';
$string['filearea:integrity_exports'] = 'Integrity exports';
$string['filearea:item_content'] = 'Archive item content';
$string['filearea:export_packages'] = 'Export packages';

// -----------------------------------------------------------------------------
// Reports and filters.
// -----------------------------------------------------------------------------

$string['filter:itemtype'] = 'Filter by item type';
$string['filter:status'] = 'Filter by status';
$string['filter:visibility'] = 'Filter by visibility';
$string['filter:validationstate'] = 'Filter by validation state';
$string['filter:provenance'] = 'Filter by provenance';
$string['filter:createdby'] = 'Filter by creator';
$string['filter:datefrom'] = 'Date from';
$string['filter:dateto'] = 'Date to';
$string['report:archiveproduction'] = 'Archive production';
$string['report:validateditems'] = 'Validated items';
$string['report:pendingitems'] = 'Pending items';
$string['report:restricteditems'] = 'Restricted items';
$string['report:exports'] = 'Exports';

// -----------------------------------------------------------------------------
// Misc UI.
// -----------------------------------------------------------------------------

$string['confirmvalidate'] = 'Validate this archive item?';
$string['confirmreject'] = 'Reject this archive item?';
$string['confirminvalidate'] = 'Invalidate this archive item?';
$string['confirmrevision'] = 'Create a new revision for this archive item?';
$string['confirmexport'] = 'Generate this archive export?';
$string['confirmpublicvisibility'] = 'Make this archive item public after validation?';
$string['yesvalidate'] = 'Yes, validate';
$string['yesreject'] = 'Yes, reject';
$string['yesinvalidate'] = 'Yes, invalidate';
$string['yesrevise'] = 'Yes, revise';
$string['yesexport'] = 'Yes, export';
// -----------------------------------------------------------------------------
// Inventory additions from missing string scan.
// -----------------------------------------------------------------------------

// General additions.
$string['action:done'] = 'Action completed.';
$string['action:failed'] = 'Action failed.';
$string['action:running'] = 'Action in progress…';
$string['aiassistancemustbedisclosed'] = 'AI assistance must be disclosed.';
$string['aiassisted'] = 'AI assisted';
$string['aigovernance'] = 'AI governance';
$string['ailog'] = 'AI log';
$string['ailogrequired'] = 'An AI log is required.';
$string['aimetadatarequired'] = 'AI metadata is required.';
$string['ainonsovereignnotice'] = 'AI assistance is non-authoritative. Archive and validation decisions require authorised human review.';
$string['aipolicy'] = 'AI policy';
$string['all'] = 'All';
$string['allowailogs'] = 'Allow AI logs';
$string['allowarchiveitems'] = 'Allow archive items';
$string['allowcontestation'] = 'Allow contestation';
$string['allowportfolioitems'] = 'Allow portfolio items';
$string['allowproofs'] = 'Allow proofs';
$string['archivistnotes'] = 'Archivist notes';
$string['atleastonearchivetypeenabled'] = 'At least one archive type must be enabled.';
$string['backtoarchiveitem'] = 'Back to archive item';
$string['badgekeys'] = 'Badge keys';
$string['calendarevent:closes'] = '{$a} closes';
$string['calendarevent:opens'] = '{$a} opens';
$string['cancelexport'] = 'Cancel export';
$string['cancelexportbody'] = 'Cancel this archive export request?';
$string['canonref'] = 'Canon reference';
$string['canonreference'] = 'Canon reference';
$string['competencycodes'] = 'Competency codes';
$string['confirmarchiveaction'] = 'Confirm archive action';
$string['confirmarchiveactionbody'] = 'Confirm that you want to perform this archive action.';
$string['confirmexportbody'] = 'Generate this archive export package?';
$string['confirmkristalsubmit'] = 'Submit Kristal?';
$string['confirmkristalsubmitbody'] = 'Submit this Kristal for the configured archive workflow?';
$string['confirmrestrictedexportbody'] = 'This export may contain restricted archive data. Confirm that you are authorised to continue.';
$string['content'] = 'Content';
$string['contestabilitydays'] = 'Contestability period';
$string['contestabilitydaysinvalid'] = 'The contestability period must be zero or more days.';
$string['contestationallowed'] = 'Contestation allowed';
$string['correctionrequiresrevision'] = 'A correction requires a revision record.';
$string['createversionrecord'] = 'Create version record';
$string['currentstatus'] = 'Current status';
$string['currentvalidationstate'] = 'Current validation state';
$string['currentvisibility'] = 'Current visibility';
$string['defaultaipolicy'] = 'Default AI policy';
$string['defaultitemtype'] = 'Default item type';
$string['defaultprovenance'] = 'Default provenance';
$string['defaultvalidationstate'] = 'Default validation state';
$string['ethicalnotes'] = 'Ethical notes';
$string['evidencepolicy'] = 'Evidence policy';
$string['forcerevisions'] = 'Force revisions';
$string['format'] = 'Format';
$string['general'] = 'General';
$string['generatedat'] = 'Generated at';
$string['includefilesinexports'] = 'Include files in exports';
$string['includerevisionsinexports'] = 'Include revisions in exports';
$string['integrityandai'] = 'Integrity and AI';
$string['integritynotes'] = 'Integrity notes';
$string['integritynotesrequired'] = 'Integrity notes are required.';
$string['integrityrequired'] = 'Integrity review is required.';
$string['integrityreview'] = 'Integrity review';
$string['integritysummary'] = 'Integrity summary';
$string['integritysummaryrequired'] = 'An integrity summary is required.';
$string['invalidarchivecode'] = 'Invalid archive code.';
$string['invalidexportformat'] = 'Invalid export format.';
$string['invalidexportmode'] = 'Invalid export mode.';
$string['invalidexportscope'] = 'Invalid export scope.';
$string['invalidexportvisibility'] = 'Invalid export visibility.';
$string['invalidjsonorlist'] = 'Enter valid JSON or a valid list.';
$string['invalidmetadatajson'] = 'Invalid metadata JSON.';
$string['invalidshortname'] = 'Invalid short name.';
$string['invalidsourcecomponent'] = 'Invalid source component.';
$string['invalidvalidationstate'] = 'Invalid validation state.';
$string['itemcount'] = 'Item count';
$string['itemtype:integrity_summary'] = 'Integrity summary';
$string['itemtype:minutes'] = 'Minutes';
$string['itemtype:public_summary'] = 'Public summary';
$string['itemvalidated'] = 'Item validated';
$string['metadatajson'] = 'Metadata JSON';
$string['nouserarchiveitems'] = 'No user archive items are available.';
$string['originarea'] = 'Origin area';
$string['origincomponent'] = 'Origin component';
$string['originid'] = 'Origin ID';
$string['originrecord'] = 'Origin record';
$string['origintype'] = 'Origin type';
$string['privatefeedback'] = 'Private feedback';
$string['proof'] = 'Proof';
$string['proof_files'] = 'Proof files';
$string['proofrecords'] = 'Proof records';
$string['proofs'] = 'Proofs';
$string['proofs:none'] = 'No proofs are available.';
$string['publicsummary'] = 'Public summary';
$string['publicsummaryrequired'] = 'A public summary is required.';
$string['readyforexport'] = 'Ready for export';
$string['recordid'] = 'Record ID';
$string['redactrestrictedexports'] = 'Redact restricted exports';
$string['requestexport'] = 'Request export';
$string['requestvalidation'] = 'Request validation';
$string['required'] = 'Required';
$string['requiresintegritycase'] = 'Requires integrity case';
$string['requiresrevision'] = 'Requires revision';
$string['restrictednotes'] = 'Restricted notes';
$string['restrictednotesrequired'] = 'Restricted notes are required.';
$string['restrictedvisibletoyou'] = 'Restricted information is visible to you.';
$string['retentionnotes'] = 'Retention notes';
$string['retentionpolicy'] = 'Retention policy';
$string['retentionpolicy:course_lifetime'] = 'Course lifetime';
$string['retentionpolicy:institutional_memory'] = 'Institutional memory';
$string['retentionpolicy:program_lifetime'] = 'Program lifetime';
$string['retentionpolicy:restricted_integrity'] = 'Restricted integrity retention';
$string['revisearchiveitem'] = 'Revise archive item';
$string['revisionandmemory'] = 'Revision and memory';
$string['revisionandretention'] = 'Revision and retention';
$string['revisionhistory'] = 'Revision history';
$string['revisioninstructions'] = 'Revision instructions';
$string['revisioninstructionsrequired'] = 'Revision instructions are required.';
$string['revisionpolicy'] = 'Revision policy';
$string['revisionpolicy:none'] = 'No revision policy';
$string['revisionpolicy:version_every_edit'] = 'Version every edit';
$string['revisionpolicy:version_on_change'] = 'Version on change';
$string['revisionpolicy:version_on_validation'] = 'Version on validation';
$string['savearchiveitem'] = 'Save archive item';
$string['savekristaldraft'] = 'Save Kristal draft';
$string['savevalidationdraft'] = 'Save validation draft';
$string['scope'] = 'Scope';
$string['shortname'] = 'Short name';
$string['sourcetitle'] = 'Source title';
$string['status:pendingreview'] = 'Pending review';
$string['submitkristal'] = 'Submit Kristal';
$string['submitvalidation'] = 'Submit validation';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';
$string['uncertaintynotes'] = 'Uncertainty notes';
$string['updatepreview'] = 'Update preview';
$string['validatearchiveitems'] = 'Validate archive items';
$string['validateditemneedsreviewstate'] = 'Validated items must use a reviewed validation state.';
$string['versionnumber'] = 'Version number';
$string['versionx'] = 'Version {$a}';
$string['visibilityandreview'] = 'Visibility and review';
$string['visibilityconfirmed'] = 'Visibility confirmed';
$string['visibilitymustbeconfirmed'] = 'Visibility must be confirmed.';
$string['visibilitynotes'] = 'Visibility notes';
$string['visibilityreview'] = 'Visibility review';

// Archive item and governance additions.
$string['archiveaddnotice'] = 'Add archive records only when provenance, visibility, and review requirements are clear.';
$string['archiveexport'] = 'Archive export';
$string['archiveexportqueued'] = 'Archive export queued.';
$string['archivegovernance'] = 'Archive governance';
$string['archiveitem:loaded'] = 'Archive item loaded.';
$string['archiveitem:loadfailed'] = 'Unable to load archive item.';
$string['archiveitem:loading'] = 'Loading archive item…';
$string['archiveitemactions'] = 'Archive item actions';
$string['archiveitembody'] = 'Archive item body';
$string['archiveitemcontent'] = 'Archive item content';
$string['archiveitemfiles'] = 'Archive item files';
$string['archiveitemgovernancenotice'] = 'Archive items must preserve provenance, visibility, validation state, and revision history.';
$string['archiveitemidentity'] = 'Archive item identity';
$string['archiveitempolicy'] = 'Archive item policy';
$string['archiveitemrequirescontent'] = 'Archive item content is required.';
$string['archiveitems:none'] = 'No archive items are available.';
$string['archiveitemtitle'] = 'Archive item title';
$string['archiveitemtype'] = 'Archive item type';
$string['archivememorynotice'] = 'Archive memory preserves evidence and decisions; it must not replace review or consent.';
$string['archivemissingidentifiers'] = 'Archive identifiers are missing.';
$string['archivereason'] = 'Archive reason';
$string['archivescope'] = 'Archive scope';
$string['archivescopefield'] = 'Archive scope field';
$string['archivesource'] = 'Archive source';
$string['archivestatus'] = 'Archive status';
$string['archivetype'] = 'Archive type';
$string['archivetype:assembly_memory'] = 'Assembly memory';
$string['archivetype:challenge_output'] = 'Challenge output';
$string['archivetype:course_memory'] = 'Course memory';
$string['archivetype:integrity_memory'] = 'Integrity memory';
$string['archivetype:kristal_library'] = 'Kristal library';
$string['archivetype:portfolio_archive'] = 'Portfolio archive';
$string['archivetype:proof_repository'] = 'Proof repository';
$string['archivevalidation'] = 'Archive validation';
$string['badge:aiassisted'] = 'AI assisted';
$string['badge:contested'] = 'Contested';
$string['badge:invalidated'] = 'Invalidated';
$string['badge:restricted'] = 'Restricted';
$string['badge:validated'] = 'Validated';
$string['notice:aiassisted'] = 'AI assistance was used. Treat this as non-authoritative until reviewed.';
$string['notice:archiveitem'] = 'Archive item notice';
$string['notice:restricted'] = 'This archive record contains restricted information.';

// Kristal additions.
$string['kristalalignment'] = 'Kristal alignment';
$string['kristalbody'] = 'Kristal body';
$string['kristalbodyrequired'] = 'Kristal body is required.';
$string['kristalcontent'] = 'Kristal content';
$string['kristalerror'] = 'Kristal error';
$string['kristalgovernancenotice'] = 'Kristals must preserve provenance, context, visibility, and validation requirements.';
$string['kristalidentity'] = 'Kristal identity';
$string['kristalmissingcontent'] = 'Kristal content is required.';
$string['kristalmissingtitle'] = 'Kristal title is required.';
$string['kristalrefreshed'] = 'Kristal refreshed.';
$string['kristalrefreshfailed'] = 'Unable to refresh Kristal.';
$string['kristalrefreshing'] = 'Refreshing Kristal…';
$string['kristals:none'] = 'No Kristals are available.';
$string['kristalsaved'] = 'Kristal saved.';
$string['kristalsavefailed'] = 'Unable to save Kristal.';
$string['kristalsaving'] = 'Saving Kristal…';
$string['kristalshortname'] = 'Kristal short name';
$string['kristalsubmitfailed'] = 'Unable to submit Kristal.';
$string['kristalsubmitted'] = 'Kristal submitted.';
$string['kristalsubmitting'] = 'Submitting Kristal…';
$string['kristalsummary'] = 'Kristal summary';
$string['kristaltype:canonlink'] = 'Canon link';
$string['kristaltype:concept'] = 'Concept';
$string['kristaltype:decisionmemory'] = 'Decision memory';
$string['kristaltype:definition'] = 'Definition';
$string['kristaltype:principle'] = 'Principle';
$string['kristaltype:proofsynthesis'] = 'Proof synthesis';
$string['kristaltype:synthesis'] = 'Synthesis';

// Validation and provenance additions.
$string['contextverified'] = 'Context verified';
$string['evidencerelationverified'] = 'Evidence relation verified';
$string['provenance:loaded'] = 'Provenance loaded.';
$string['provenance:loadfailed'] = 'Unable to load provenance.';
$string['provenance:loading'] = 'Loading provenance…';
$string['provenancegovernancenotice'] = 'Provenance records must identify origin, transformations, validation state, and limits of authority.';
$string['provenancemustbeverified'] = 'Provenance must be verified.';
$string['provenancenotes'] = 'Provenance notes';
$string['provenancepolicy'] = 'Provenance policy';
$string['provenancerecordcount'] = 'Provenance record count';
$string['provenancerecords'] = 'Provenance records';
$string['provenancerecords:none'] = 'No provenance records are available.';
$string['provenancestatementrequired'] = 'A provenance statement is required.';
$string['provenancevalidation'] = 'Provenance validation';
$string['sourceverified'] = 'Source verified';
$string['validationcriteria'] = 'Validation criteria';
$string['validationgovernancenotice'] = 'Validation decisions must be traceable, human-reviewable, and separate from archive storage.';
$string['validationgrade'] = 'Validation grade';
$string['validationgrademustbeinrange'] = 'The validation grade must be within the allowed range.';
$string['validationrequested'] = 'Validation requested.';
$string['validationstatement'] = 'Validation statement';
$string['validationstatementrequired'] = 'A validation statement is required.';
$string['validationworkflow'] = 'Validation workflow';
$string['validationworkflow:archivist_review'] = 'Archivist review';
$string['validationworkflow:human_review'] = 'Human review';
$string['validationworkflow:integrity_review'] = 'Integrity review';
$string['validationworkflow:none'] = 'No validation workflow';

// Export additions.
$string['export:failed'] = 'Export failed.';
$string['export:noitems:body'] = 'There are no archive items available for this export.';
$string['export:noitems:title'] = 'No items to export';
$string['export:previewfailed'] = 'Unable to load export preview.';
$string['export:previewloaded'] = 'Export preview loaded.';
$string['export:previewloading'] = 'Loading export preview…';
$string['export:started'] = 'Export started.';
$string['export:starting'] = 'Starting export…';
$string['exportarchiveitem'] = 'Export archive item';
$string['exportauditnote'] = 'Export audit note';
$string['exportcancelfailed'] = 'Unable to cancel export.';
$string['exportcancelled'] = 'Export cancelled.';
$string['exportconfirmpolicy'] = 'Confirm export policy';
$string['exportconfirmpolicyrequired'] = 'You must confirm the export policy.';
$string['exportdescription'] = 'Export description';
$string['exporterror'] = 'Export error';
$string['exporterror:missingarchive'] = 'The archive for this export could not be found.';
$string['exportformat:html'] = 'HTML';
$string['exportformat:mbz_manifest'] = 'Moodle backup manifest';
$string['exportgovernance'] = 'Export governance';
$string['exportgovernancenotice'] = 'Exports must respect provenance, visibility, integrity restrictions, and privacy requirements.';
$string['exportincludefiles'] = 'Include files';
$string['exportincludehashes'] = 'Include hashes';
$string['exportincludeintegritysummary'] = 'Include integrity summary';
$string['exportincludekristals'] = 'Include Kristals';
$string['exportincludemetadata'] = 'Include metadata';
$string['exportincludeprivatefields'] = 'Include private fields';
$string['exportincludeversions'] = 'Include versions';
$string['exportitemids'] = 'Export item IDs';
$string['exportitemidsrequired'] = 'Select at least one archive item to export.';
$string['exportmissingidentifiers'] = 'Export identifiers are missing.';
$string['exportmode'] = 'Export mode';
$string['exportmode:immediate'] = 'Immediate';
$string['exportmode:queued'] = 'Queued';
$string['exportnotice'] = 'Export notice';
$string['exportnotice_desc'] = 'Explain how archive exports should be reviewed, redacted, and used.';
$string['exportoptions'] = 'Export options';
$string['exportpackagename'] = 'Export package name';
$string['exportpolicy'] = 'Export policy';
$string['exportpolicy:full_with_revisions'] = 'Full export with revisions';
$string['exportpolicy:none'] = 'No export policy';
$string['exportpolicy:public_only'] = 'Public items only';
$string['exportpolicy:restricted_redacted'] = 'Restricted items, redacted';
$string['exportpolicy:validated_only'] = 'Validated items only';
$string['exportpollingstopped'] = 'Export status polling stopped.';
$string['exportpreparing'] = 'Preparing export…';
$string['exportpreview'] = 'Export preview';
$string['exportprivacy'] = 'Export privacy';
$string['exportprovenance'] = 'Export provenance';
$string['exportready'] = 'Export ready.';
$string['exportreasonrequired'] = 'An export reason is required.';
$string['exportredactpersonaldata'] = 'Redact personal data';
$string['exportrefreshed'] = 'Export refreshed.';
$string['exportrefreshfailed'] = 'Unable to refresh export.';
$string['exportrefreshing'] = 'Refreshing export…';
$string['exportrestrictednotallowed'] = 'Restricted archive data cannot be included in this export.';
$string['exports'] = 'Exports';
$string['exports:none'] = 'No exports are available.';
$string['exportscope:portfolio'] = 'Portfolio';
$string['exportscope:publicitems'] = 'Public items';
$string['exportscope:restrictedintegrity'] = 'Restricted integrity items';
$string['exportscope:selecteditems'] = 'Selected items';
$string['exportscope:validateditems'] = 'Validated items';
$string['exportstatusfilter'] = 'Export status filter';
$string['exportsummary'] = 'Export summary';
$string['exporttimefrom'] = 'Export time from';
$string['exporttimeto'] = 'Export time to';
$string['exporttimetomustbeafterfrom'] = 'The export end time must be after the start time.';
$string['exporttypefilter'] = 'Export type filter';
$string['exportunredactednotallowed'] = 'Unredacted restricted archive data cannot be exported.';

// Settings additions.
$string['settings:ai'] = 'AI settings';
$string['settings:ai_desc'] = 'Configure AI assistance, AI validation boundaries, uncertainty labels, and AI-use logging.';
$string['settings:allowaiassistance'] = 'Allow AI assistance';
$string['settings:allowaiassistance_desc'] = 'Allow AI assistance in archive workflows while keeping final authority with authorised users.';
$string['settings:allowaivalidation'] = 'Allow AI validation support';
$string['settings:allowaivalidation_desc'] = 'Allow AI to support validation review without making authoritative decisions.';
$string['settings:allowpublicitems'] = 'Allow public archive items';
$string['settings:allowpublicitems_desc'] = 'Allow archive items to become public when validation and visibility requirements are met.';
$string['settings:blockrestrictedexports'] = 'Block restricted exports';
$string['settings:blockrestrictedexports_desc'] = 'Prevent exports from including restricted archive data unless explicitly allowed.';
$string['settings:defaultexportformat'] = 'Default export format';
$string['settings:defaultexportformat_desc'] = 'Default format used for new archive export requests.';
$string['settings:defaultprovenance'] = 'Default provenance';
$string['settings:defaultprovenance_desc'] = 'Default provenance assigned to new archive records when no specific value is provided.';
$string['settings:defaultvalidationstate'] = 'Default validation state';
$string['settings:defaultvalidationstate_desc'] = 'Default validation state assigned to new archive records.';
$string['settings:enabled'] = 'Enable UCKK Archive';
$string['settings:enabled_desc'] = 'Enable UCKK Archive activity features.';
$string['settings:enablerevisions'] = 'Enable revisions';
$string['settings:enablerevisions_desc'] = 'Allow archive items and Kristals to keep revision history.';
$string['settings:enablevalidation'] = 'Enable validation';
$string['settings:enablevalidation_desc'] = 'Enable validation workflows for archive records.';
$string['settings:export'] = 'Export settings';
$string['settings:export_desc'] = 'Configure archive export behaviour and limits.';
$string['settings:generateexports'] = 'Generate exports';
$string['settings:generateexports_desc'] = 'Allow queued archive export packages to be generated by scheduled tasks.';
$string['settings:includefilesinexports'] = 'Include files in exports';
$string['settings:includefilesinexports_desc'] = 'Include attached files in archive export packages when permitted.';
$string['settings:itempolicy'] = 'Item policy';
$string['settings:itempolicy_desc'] = 'Configure archive item creation, protection, visibility, and revision rules.';
$string['settings:kristal'] = 'Kristal settings';
$string['settings:kristal_desc'] = 'Configure Kristal creation, validation, and limits.';
$string['settings:kristalsrequirevalidation'] = 'Kristals require validation';
$string['settings:kristalsrequirevalidation_desc'] = 'Require Kristals to be validated before they are treated as verified archive records.';
$string['settings:lockvalidateditems'] = 'Lock validated items';
$string['settings:lockvalidateditems_desc'] = 'Prevent direct editing of validated archive items unless a revision workflow is used.';
$string['settings:logaiuse'] = 'Log AI use';
$string['settings:logaiuse_desc'] = 'Record AI assistance metadata for archive transparency and review.';
$string['settings:maxkristalitems'] = 'Maximum Kristals';
$string['settings:maxkristalitems_desc'] = 'Maximum number of Kristals that can be created for one archive context.';
$string['settings:pausevalidationonintegritycase'] = 'Pause validation during integrity cases';
$string['settings:pausevalidationonintegritycase_desc'] = 'Pause archive validation when an associated integrity case is open.';
$string['settings:protectrestricteditems'] = 'Protect restricted items';
$string['settings:protectrestricteditems_desc'] = 'Apply additional safeguards to restricted archive items.';
$string['settings:requireaiuncertaintylabel'] = 'Require AI uncertainty label';
$string['settings:requireaiuncertaintylabel_desc'] = 'Require AI-assisted outputs to disclose uncertainty and non-authority boundaries.';
$string['settings:requirechangereason'] = 'Require change reason';
$string['settings:requirechangereason_desc'] = 'Require users to explain changes to archive records.';
$string['settings:requirecontext'] = 'Require context';
$string['settings:requirecontext_desc'] = 'Require contextual information for archive records and Kristals.';
$string['settings:requirehumanvalidation'] = 'Require human validation';
$string['settings:requirehumanvalidation_desc'] = 'Require authorised human review before archive records are treated as validated.';
$string['settings:requirevisibility'] = 'Require visibility selection';
$string['settings:requirevisibility_desc'] = 'Require users to select an explicit visibility level for archive records.';
$string['settings:taskbatchsize'] = 'Task batch size';
$string['settings:taskbatchsize_desc'] = 'Maximum number of archive records processed by each scheduled task run.';
$string['settings:tasks'] = 'Scheduled tasks';
$string['settings:tasks_desc'] = 'Configure scheduled archive maintenance, validation, and export tasks.';
$string['settings:validatependingitems'] = 'Validate pending items';
$string['settings:validatependingitems_desc'] = 'Allow scheduled tasks to prepare pending archive items for validation review.';
$string['settings:validation'] = 'Validation settings';
$string['settings:validation_desc'] = 'Configure archive validation workflow, review requirements, and safeguards.';

// Completion, privacy, and task additions.
$string['completionarchiveexported_desc'] = 'Require the student to export an archive package.';
$string['completionarchivestate_desc'] = 'Require the archive to reach the selected state.';
$string['completionarchivevalidated_desc'] = 'Require the archive to contain a validated item.';
$string['completionitemadded_desc'] = 'Require the student to add an archive item.';
$string['completionitemvalidated_desc'] = 'Require the student to have an archive item validated.';
$string['completionkristalcreated_desc'] = 'Require the student to create a Kristal.';
$string['privacy:deleted'] = 'This archive record has been deleted.';
$string['resetarchivespreserved'] = 'Archive records are preserved during reset.';
$string['task:validation_correction_reason'] = 'Automatic validation marked this item as requiring correction.';
$string['task:validation_ready_reason'] = 'Automatic validation marked this item as ready for review.';
$string['task_generate_archive_exports'] = 'Generate archive exports';
$string['useroutline'] = 'Archive activity contribution';
