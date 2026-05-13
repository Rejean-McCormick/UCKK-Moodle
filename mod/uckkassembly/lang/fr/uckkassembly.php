<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * English language strings for the UCKK Assembly activity module.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCKK Assembly';
$string['modulename'] = 'UCKK Assembly';
$string['modulename_help'] = 'Use an UCKK Assembly to structure motions, amendments, objections, votes or readings, decisions, minutes, contestations, and archive output.';
$string['modulename_link'] = 'mod/uckkassembly/view';
$string['modulenameplural'] = 'UCKK assemblies';
$string['pluginadministration'] = 'UCKK Assembly administration';

// Capabilities.
$string['uckkassembly:addinstance'] = 'Add a new UCKK Assembly';
$string['uckkassembly:view'] = 'View UCKK Assembly';
$string['uckkassembly:createassembly'] = 'Create and configure UCKK assemblies';
$string['uckkassembly:proposemotion'] = 'Propose motions';
$string['uckkassembly:amendmotion'] = 'Amend motions';
$string['uckkassembly:objectmotion'] = 'Object to motions';
$string['uckkassembly:vote'] = 'Vote or record readings';
$string['uckkassembly:publishdecision'] = 'Publish Assembly decisions';
$string['uckkassembly:contestdecision'] = 'Contest Assembly decisions';
$string['uckkassembly:archive'] = 'Archive Assembly records';
$string['uckkassembly:viewrestricted'] = 'View restricted Assembly information';

// General fields.
$string['name'] = 'Assembly name';
$string['assemblyname'] = 'Assembly name';
$string['assemblyintro'] = 'Assembly introduction';
$string['assemblytype'] = 'Assembly type';
$string['assemblystatus'] = 'Assembly status';
$string['visibility'] = 'Visibility';
$string['governance'] = 'Governance';
$string['timeline'] = 'Timeline';
$string['settings'] = 'Assembly settings';
$string['configuration'] = 'Configuration';
$string['summary'] = 'Summary';
$string['description'] = 'Description';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['createdby'] = 'Created by';
$string['modifiedby'] = 'Modified by';
$string['timecreated'] = 'Created';
$string['timemodified'] = 'Last modified';

// Assembly types.
$string['assemblytype:savoirs'] = 'Knowledge Assembly';
$string['assemblytype:defis'] = 'Challenge Assembly';
$string['assemblytype:joueurs'] = 'Joueurs Assembly';
$string['assemblytype:batisseurs'] = 'Bâtisseurs Assembly';
$string['assemblytype:inquisiteurs'] = 'Inquisiteurs Assembly';
$string['assemblytype:grand_jeu'] = 'Grand Jeu Assembly';

// Statuses.
$string['status:draft'] = 'Draft';
$string['status:active'] = 'Active';
$string['status:hidden'] = 'Hidden';
$string['status:pending'] = 'Pending';
$string['status:pendingreview'] = 'Pending review';
$string['status:validated'] = 'Validated';
$string['status:rejected'] = 'Rejected';
$string['status:correctionrequired'] = 'Correction required';
$string['status:contested'] = 'Contested';
$string['status:invalidated'] = 'Invalidated';
$string['status:closed'] = 'Closed';
$string['status:archived'] = 'Archived';
$string['status:cancelled'] = 'Cancelled';
$string['status:published'] = 'Published';

// Visibility.
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

// Provenance.
$string['provenance'] = 'Provenance';
$string['provenance:human'] = 'Human';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:imported'] = 'Imported';
$string['provenance:system'] = 'System';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:integrity'] = 'Integrity';

// Form sections.
$string['motionsettings'] = 'Motion settings';
$string['votingsettings'] = 'Voting settings';
$string['decisionsettings'] = 'Decision settings';
$string['archivesettings'] = 'Archive settings';
$string['integritysettings'] = 'Integrity settings';
$string['completionsettings'] = 'Completion settings';

// Timeline.
$string['timeopen'] = 'Opening time';
$string['timeclose'] = 'Closing time';
$string['contestuntil'] = 'Contestability window ends';
$string['timeopen_help'] = 'Optional time when motions, amendments, objections, or readings may begin.';
$string['timeclose_help'] = 'Optional time when normal Assembly participation closes.';
$string['contestuntil_help'] = 'Optional deadline until which decisions may be contested.';
$string['calendarevent:opens'] = '{$a} opens';
$string['calendarevent:closes'] = '{$a} closes';
$string['calendarevent:contestuntil'] = '{$a} contestability window ends';

// Motions.
$string['motion'] = 'Motion';
$string['motions'] = 'Motions';
$string['motiontitle'] = 'Motion title';
$string['motionbody'] = 'Motion body';
$string['motiontype'] = 'Motion type';
$string['motionstatus'] = 'Motion status';
$string['motioncreated'] = 'Motion created.';
$string['motionupdated'] = 'Motion updated.';
$string['motiondeleted'] = 'Motion deleted.';
$string['motionpublished'] = 'Motion published.';
$string['motionarchived'] = 'Motion archived.';
$string['proposemotion'] = 'Propose motion';
$string['editmotion'] = 'Edit motion';
$string['viewmotion'] = 'View motion';
$string['nomotions'] = 'No motions have been proposed yet.';
$string['motionlocked'] = 'This motion is locked.';
$string['motionnotfound'] = 'Motion not found.';
$string['cannotproposemotion'] = 'You cannot propose a motion in this Assembly.';
$string['cannoteditmotion'] = 'You cannot edit this motion.';
$string['cannotviewmotion'] = 'You cannot view this motion.';

// Motion types.
$string['motiontype:information'] = 'Information';
$string['motiontype:recommendation'] = 'Recommendation';
$string['motiontype:validation'] = 'Validation';
$string['motiontype:correction'] = 'Correction';
$string['motiontype:rejection'] = 'Rejection';
$string['motiontype:archival'] = 'Archival';
$string['motiontype:integrity'] = 'Integrity';

// Amendments.
$string['amendment'] = 'Amendment';
$string['amendments'] = 'Amendments';
$string['amendmotion'] = 'Amend motion';
$string['amendmentbody'] = 'Amendment body';
$string['amendmentcreated'] = 'Amendment created.';
$string['amendmentupdated'] = 'Amendment updated.';
$string['amendmentdeleted'] = 'Amendment deleted.';
$string['noamendments'] = 'No amendments have been proposed yet.';
$string['cannotamendmotion'] = 'You cannot amend this motion.';

// Objections.
$string['objection'] = 'Objection';
$string['objections'] = 'Objections';
$string['objectmotion'] = 'Object to motion';
$string['objectionbody'] = 'Objection body';
$string['objectioncreated'] = 'Objection recorded.';
$string['objectionupdated'] = 'Objection updated.';
$string['objectiondeleted'] = 'Objection deleted.';
$string['noobjections'] = 'No objections have been recorded yet.';
$string['cannotobjectmotion'] = 'You cannot object to this motion.';

// Voting / readings.
$string['vote'] = 'Vote';
$string['votes'] = 'Votes';
$string['votingmethod'] = 'Voting method';
$string['votingmethod:readings'] = 'Readings';
$string['votingmethod:consent'] = 'Consent';
$string['votingmethod:majority'] = 'Majority';
$string['votingmethod:supermajority'] = 'Supermajority';
$string['votingmethod:consensus'] = 'Consensus';
$string['votingmethod:advisory'] = 'Advisory';
$string['quorum'] = 'Quorum';
$string['quorum_help'] = 'Minimum number of eligible participants required before a decision can be published. Set to 0 for no quorum rule.';
$string['castvote'] = 'Cast vote';
$string['changevote'] = 'Change vote';
$string['voterecorded'] = 'Vote recorded.';
$string['cannotvote'] = 'You cannot vote in this Assembly.';
$string['novotes'] = 'No votes have been recorded yet.';

// Vote values.
$string['vote:for'] = 'For';
$string['vote:against'] = 'Against';
$string['vote:abstain'] = 'Abstain';
$string['vote:block'] = 'Block';
$string['vote:reading'] = 'Reading recorded';
$string['vote:consent'] = 'Consent';
$string['vote:needswork'] = 'Needs work';

// Decisions.
$string['decision'] = 'Decision';
$string['decisions'] = 'Decisions';
$string['decisiontype'] = 'Decision type';
$string['decisionbody'] = 'Decision body';
$string['decisionstatus'] = 'Decision status';
$string['publishdecision'] = 'Publish decision';
$string['decisionpublished'] = 'Decision published.';
$string['decisionupdated'] = 'Decision updated.';
$string['decisionarchived'] = 'Decision archived.';
$string['nodecisions'] = 'No decisions have been published yet.';
$string['cannotpublishdecision'] = 'You cannot publish decisions in this Assembly.';

// Decision types.
$string['decisiontype:information'] = 'Information';
$string['decisiontype:recommendation'] = 'Recommendation';
$string['decisiontype:validation'] = 'Validation';
$string['decisiontype:correction'] = 'Correction';
$string['decisiontype:rejection'] = 'Rejection';
$string['decisiontype:archival'] = 'Archival';
$string['decisiontype:integrity'] = 'Integrity';

// Minutes.
$string['minutes'] = 'Minutes';
$string['minutesformat'] = 'Minutes format';
$string['minutesformat:structured'] = 'Structured';
$string['minutesformat:narrative'] = 'Narrative';
$string['minutesformat:transcript'] = 'Transcript';
$string['minutesbody'] = 'Minutes body';
$string['publishminutes'] = 'Publish minutes';
$string['minutespublished'] = 'Minutes published.';
$string['minutesupdated'] = 'Minutes updated.';
$string['nominutes'] = 'No minutes have been published yet.';

// Contestations.
$string['contestation'] = 'Contestation';
$string['contestations'] = 'Contestations';
$string['contestdecision'] = 'Contest decision';
$string['contestbody'] = 'Contestation body';
$string['contestreason'] = 'Reason for contestation';
$string['decisioncontested'] = 'Decision contested.';
$string['nocontestations'] = 'No contestations have been recorded yet.';
$string['cannotcontestdecision'] = 'You cannot contest this decision.';
$string['contestclosed'] = 'The contestability window is closed.';

// Archive.
$string['archive'] = 'Archive';
$string['archivepolicy'] = 'Archive policy';
$string['archivepolicy:none'] = 'No archive';
$string['archivepolicy:summary'] = 'Summary archive';
$string['archivepolicy:full'] = 'Full archive';
$string['archivepolicy:restricted_integrity'] = 'Restricted integrity archive';
$string['archiveassembly'] = 'Archive Assembly';
$string['assemblyarchived'] = 'Assembly archived.';
$string['cannotarchiveassembly'] = 'You cannot archive this Assembly.';
$string['archiveoutput'] = 'Archive output';
$string['archiveempty'] = 'No archive output has been created yet.';

// Integrity.
$string['integrity'] = 'Integrity';
$string['integritystate'] = 'Integrity state';
$string['integritystate:unverified'] = 'Unverified';
$string['integritystate:human_reviewed'] = 'Human reviewed';
$string['integritystate:verified'] = 'Verified';
$string['integritystate:contested'] = 'Contested';
$string['integritystate:invalidated'] = 'Invalidated';
$string['integritystate:archived'] = 'Archived';
$string['integritynotice'] = 'Integrity notice';
$string['integrityrestrictednotice'] = 'Some Assembly information is restricted to authorised integrity reviewers.';
$string['integritynonsovereignnotice'] = 'Integrity records are procedural and contestable. They must remain auditable and subject to authorised human review.';

// Configuration flags.
$string['allowmotions'] = 'Allow motions';
$string['allowamendments'] = 'Allow amendments';
$string['allowobjections'] = 'Allow objections';
$string['allowcontestations'] = 'Allow contestations';
$string['allowmotions_help'] = 'Allow participants with the required capability to propose motions.';
$string['allowamendments_help'] = 'Allow participants with the required capability to propose amendments.';
$string['allowobjections_help'] = 'Allow participants to record objections to motions.';
$string['allowcontestations_help'] = 'Allow participants with the required capability to contest decisions.';

// Completion.
$string['completionrequiresmotion'] = 'Require motion';
$string['completionrequiresmotion_help'] = 'The participant must propose at least one motion to complete this activity.';
$string['completionrequirevote'] = 'Require vote or reading';
$string['completionrequirevote_help'] = 'The participant must cast a vote or record a reading to complete this activity.';
$string['completionrequiresdecision'] = 'Require published decision';
$string['completionrequiresdecision_help'] = 'At least one decision must be published or archived for this activity to be completed.';
$string['completiondetail:motion'] = 'A motion must be submitted';
$string['completiondetail:vote'] = 'A vote or reading must be recorded';
$string['completiondetail:decision'] = 'A decision must be published';

// File areas.
$string['filearea:intro'] = 'Introduction';
$string['filearea:motionattachments'] = 'Motion attachments';
$string['filearea:amendmentattachments'] = 'Amendment attachments';
$string['filearea:decisionattachments'] = 'Decision attachments';
$string['filearea:minutesfiles'] = 'Minutes files';
$string['filearea:contestattachments'] = 'Contestation attachments';

// Recent activity / reports.
$string['recentmotions'] = '{$a} recent Assembly motion(s)';
$string['outline:motions'] = '{$a} motion(s)';
$string['outline:votes'] = '{$a} vote(s)';
$string['outline:contestations'] = '{$a} contestation(s)';
$string['resetuserdata'] = 'Delete Assembly participant data';

// Events.
$string['eventassemblycreated'] = 'Assembly created';
$string['eventassemblyupdated'] = 'Assembly updated';
$string['eventassemblydeleted'] = 'Assembly deleted';
$string['eventassemblyviewed'] = 'Assembly viewed';
$string['eventmotioncreated'] = 'Motion created';
$string['eventmotionupdated'] = 'Motion updated';
$string['eventamendmentcreated'] = 'Amendment created';
$string['eventobjectioncreated'] = 'Objection created';
$string['eventvotecast'] = 'Vote cast';
$string['eventdecisionpublished'] = 'Decision published';
$string['eventdecisioncontested'] = 'Decision contested';
$string['eventminutespublished'] = 'Minutes published';
$string['eventassemblyarchived'] = 'Assembly archived';

// Errors.
$string['missingassembly'] = 'Assembly not found.';
$string['invalidassemblytype'] = 'Invalid Assembly type.';
$string['invalidassemblystatus'] = 'Invalid Assembly status.';
$string['invalidvisibility'] = 'Invalid visibility.';
$string['invalidvotingmethod'] = 'Invalid voting method.';
$string['invalidmotion'] = 'Invalid motion.';
$string['invaliddecision'] = 'Invalid decision.';
$string['invalidcontestation'] = 'Invalid contestation.';
$string['timeclosemustbeafteropen'] = 'The closing time must be after the opening time.';
$string['contestuntilmustbeafterclose'] = 'The contestability deadline must be after the closing time.';
$string['nopermission'] = 'You do not have permission to perform this Assembly action.';

// UI actions.
$string['viewassembly'] = 'View Assembly';
$string['editassembly'] = 'Edit Assembly';
$string['backtoassembly'] = 'Back to Assembly';
$string['continueassembly'] = 'Continue Assembly';
$string['saveasdraft'] = 'Save as draft';
$string['publish'] = 'Publish';
$string['close'] = 'Close';
$string['reopen'] = 'Reopen';
$string['archiveaction'] = 'Archive';
$string['viewdetails'] = 'View details';

// Notices.
$string['assemblyderivednotice'] = 'Assembly records summarize deliberation. Decisions, contestations, integrity outcomes, and archive output remain governed records.';
$string['humanvalidationnotice'] = 'Assembly decisions and integrity-sensitive outcomes require authorised human validation.';
$string['publictheatrenotice'] = 'Public theatrical expression is permitted. Abuse, harassment, intimidation, doxxing, and fabricated evidence are not permitted.';

// Privacy.
$string['privacy:metadata'] = 'The UCKK Assembly activity stores Assembly participation records, including motions, amendments, objections, votes or readings, decisions, minutes, contestations, provenance, visibility, and audit metadata.';
$string['privacy:metadata:uckkassembly_motion'] = 'Information about motions proposed in an Assembly.';
$string['privacy:metadata:uckkassembly_motion:createdby'] = 'The user who proposed the motion.';
$string['privacy:metadata:uckkassembly_motion:timemodified'] = 'The time the motion was last modified.';
$string['privacy:metadata:uckkassembly_amend'] = 'Information about amendments proposed in an Assembly.';
$string['privacy:metadata:uckkassembly_object'] = 'Information about objections recorded in an Assembly.';
$string['privacy:metadata:uckkassembly_vote'] = 'Information about votes or readings recorded in an Assembly.';
$string['privacy:metadata:uckkassembly_vote:userid'] = 'The user who cast the vote or recorded the reading.';
$string['privacy:metadata:uckkassembly_decision'] = 'Information about decisions published by an Assembly.';
$string['privacy:metadata:uckkassembly_minutes'] = 'Information about Assembly minutes.';
$string['privacy:metadata:uckkassembly_contest'] = 'Information about contestations of Assembly decisions.';
$string['privacy:metadata:uckkassembly_contest:createdby'] = 'The user who opened the contestation.';