<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCKK integrity';

// Navigation.
$string['cases'] = 'Integrity cases';
$string['case'] = 'Case';
$string['case:view'] = 'View integrity case';
$string['opencase'] = 'Open case';
$string['reviewcase'] = 'Review case';
$string['decision'] = 'Decision';
$string['appeal'] = 'Appeal';
$string['report'] = 'Integrity report';

// Settings.
$string['settings:general'] = 'Integrity settings';
$string['settings:general_desc'] = 'Configure the UCKK Inquisiteur integrity workflow.';
$string['settings:enabled'] = 'Enable integrity workflow';
$string['settings:enabled_desc'] = 'Allow integrity cases to be opened, reviewed, corrected, appealed and closed.';
$string['settings:defaultseverity'] = 'Default severity';
$string['settings:defaultseverity_desc'] = 'Default severity used when a new integrity case is opened.';
$string['settings:appealwindow'] = 'Appeal window';
$string['settings:appealwindow_desc'] = 'Default time during which a decision can be appealed.';
$string['settings:restrictpublicsummaries'] = 'Restrict public summaries until closure';
$string['settings:restrictpublicsummaries_desc'] = 'Public summaries are visible only after an integrity reviewer closes the case.';
$string['settings:retentiondays'] = 'Retention days';
$string['settings:retentiondays_desc'] = 'Recommended retention period for integrity records before administrative review.';

// Capabilities.
$string['uckkintegrity:view'] = 'View integrity cases';
$string['uckkintegrity:opencase'] = 'Open integrity cases';
$string['uckkintegrity:reviewcase'] = 'Review integrity cases';
$string['uckkintegrity:assigncase'] = 'Assign integrity cases';
$string['uckkintegrity:issuecorrection'] = 'Issue integrity corrections';
$string['uckkintegrity:invalidate'] = 'Invalidate integrity-sensitive items';
$string['uckkintegrity:closecase'] = 'Close integrity cases';
$string['uckkintegrity:viewrestricted'] = 'View restricted integrity data';

// Case fields.
$string['casetype'] = 'Case type';
$string['subjectcomponent'] = 'Subject component';
$string['subjectid'] = 'Subject ID';
$string['contextid'] = 'Context ID';
$string['openedby'] = 'Opened by';
$string['assignedto'] = 'Assigned Inquisiteur user ID';
$string['severity'] = 'Severity';
$string['status'] = 'Status';
$string['summary'] = 'Summary';
$string['decisiontext'] = 'Decision text';
$string['correction'] = 'Correction';
$string['appealpath'] = 'Appeal path';
$string['archivesummary'] = 'Archive summary';
$string['archiveitemid'] = 'Archive item ID';
$string['visibility'] = 'Visibility';
$string['metadata'] = 'Metadata';
$string['casefiles'] = 'Case evidence files';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['timeclosed'] = 'Closed';
$string['actions'] = 'Actions';
$string['notes'] = 'Notes';
$string['notetype'] = 'Note type';
$string['body'] = 'Body';
$string['parties'] = 'Parties';
$string['public_summary'] = 'Public summary';
$string['restricted'] = 'Restricted';
$string['save'] = 'Save';
$string['viewcase'] = 'View case';
$string['recorddecision'] = 'Record decision';
$string['submitappeal'] = 'Submit appeal';
$string['recentcases'] = 'Recent cases';
$string['statuscounts'] = 'Cases by status';
$string['severitycounts'] = 'Cases by severity';
$string['noresults'] = 'No integrity cases found.';

// Case messages.
$string['caseopened'] = 'Integrity case opened.';
$string['reviewrecorded'] = 'Review recorded.';
$string['decisionrecorded'] = 'Decision recorded.';
$string['appealrecorded'] = 'Appeal recorded.';
$string['correctionrequested'] = 'Correction requested.';
$string['caseclosed'] = 'Integrity case closed.';
$string['iteminvalidated'] = 'Item invalidated.';

// Errors.
$string['invalidcaseid'] = 'Invalid integrity case ID.';
$string['notpermitted'] = 'You do not have permission to access this integrity record.';
$string['invalidtransition'] = 'This case cannot move from {$a->from} to {$a->to}.';
$string['unknownstatus'] = 'Unknown integrity status.';
$string['unknowntype'] = 'Unknown integrity case type.';
$string['invalidseverity'] = 'Unknown integrity severity.';
$string['invalidvisibility'] = 'Unknown integrity visibility level.';

// Events.
$string['eventcaseopened'] = 'Integrity case opened';
$string['eventcasereviewed'] = 'Integrity case reviewed';
$string['eventcorrectionrequested'] = 'Integrity correction requested';
$string['eventcorrectionissued'] = 'Integrity correction issued';
$string['eventiteminvalidated'] = 'Integrity item invalidated';
$string['eventcaseclosed'] = 'Integrity case closed';

// Severity.
$string['severity:low'] = 'Low';
$string['severity:normal'] = 'Normal';
$string['severity:high'] = 'High';
$string['severity:critical'] = 'Critical';

// Case types.
$string['type:proof_quality'] = 'Proof quality';
$string['type:fiction_fact_confusion'] = 'Fiction/fact confusion';
$string['type:ai_misuse'] = 'AI misuse';
$string['type:harassment_or_humiliation'] = 'Harassment or humiliation';
$string['type:dignity_violation'] = 'Dignity violation';
$string['type:authority_capture'] = 'Authority capture';
$string['type:assessment_dispute'] = 'Assessment dispute';
$string['type:challenge_dispute'] = 'Challenge dispute';
$string['type:assembly_dispute'] = 'Assembly dispute';
$string['type:archive_correction'] = 'Archive correction';
$string['type:privacy_concern'] = 'Privacy concern';

// Case states.
$string['status:opened'] = 'Opened';
$string['status:triaged'] = 'Triaged';
$string['status:assigned'] = 'Assigned';
$string['status:under_review'] = 'Under review';
$string['status:waiting_for_response'] = 'Waiting for response';
$string['status:correction_required'] = 'Correction required';
$string['status:resolved'] = 'Resolved';
$string['status:archived'] = 'Archived';
$string['status:dismissed'] = 'Dismissed';
$string['status:escalated'] = 'Escalated';
$string['status:paused'] = 'Paused';
$string['status:reopened'] = 'Reopened';
$string['status:closed'] = 'Closed';

// Note types.
$string['note:observation'] = 'Observation';
$string['note:evidence'] = 'Evidence';
$string['note:response'] = 'Response';
$string['note:decision'] = 'Decision';
$string['note:correction'] = 'Correction';
$string['note:appeal'] = 'Appeal';

// Privacy.
$string['privacy:metadata:case'] = 'Integrity cases store review records for evidence, challenges, assemblies and archives.';
$string['privacy:metadata:case:openedby'] = 'The user who opened the case.';
$string['privacy:metadata:case:assignedto'] = 'The Inquisiteur assigned to the case.';
$string['privacy:metadata:case:summary'] = 'The case summary.';
$string['privacy:metadata:case:decision'] = 'The case decision.';
$string['privacy:metadata:case:correction'] = 'Corrections requested for the case.';
$string['privacy:metadata:case:metadata'] = 'Additional structured case metadata.';
$string['privacy:metadata:note'] = 'Integrity case notes store observations, evidence, responses, corrections and decisions.';
$string['privacy:metadata:note:userid'] = 'The author of the note.';
$string['privacy:metadata:note:body'] = 'The note content.';
$string['privacy:metadata:appeal'] = 'Appeals store contestations of case outcomes.';
$string['privacy:metadata:appeal:userid'] = 'The user who submitted the appeal.';
$string['privacy:metadata:appeal:body'] = 'The appeal content.';
$string['privacy:pathcases'] = 'Integrity cases';
$string['privacy:pathnotes'] = 'Integrity notes';
$string['privacy:pathappeals'] = 'Integrity appeals';