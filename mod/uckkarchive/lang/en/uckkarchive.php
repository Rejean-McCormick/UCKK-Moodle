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

