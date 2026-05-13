<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * English language strings for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'UCKK Challenge';
$string['pluginadministration'] = 'UCKK Challenge administration';
$string['modulename'] = 'UCKK Challenge';
$string['modulename_help'] = 'The UCKK Challenge activity supports Défis King Klown: structured challenges with rules, evidence, review, integrity safeguards, and archive output.';
$string['modulename_link'] = 'mod/uckkchallenge/view';
$string['modulenameplural'] = 'UCKK Challenges';

$string['uckkchallenge:addinstance'] = 'Add a new UCKK Challenge';
$string['uckkchallenge:view'] = 'View UCKK Challenge';
$string['uckkchallenge:createchallenge'] = 'Create UCKK challenges';
$string['uckkchallenge:submitproof'] = 'Submit challenge proof';
$string['uckkchallenge:evaluate'] = 'Evaluate challenge submissions';
$string['uckkchallenge:validateintegrity'] = 'Validate challenge integrity';
$string['uckkchallenge:archive'] = 'Archive challenge output';

// General UI.
$string['challenge'] = 'Challenge';
$string['challenges'] = 'Challenges';
$string['challengeidentity'] = 'Challenge identity';
$string['challengestatement'] = 'Challenge statement';
$string['challengecontext'] = 'Challenge context';
$string['challengerules'] = 'Rules';
$string['challengecorridors'] = 'Corridors of action';
$string['evidencerequirements'] = 'Evidence requirements';
$string['evaluationcriteria'] = 'Evaluation criteria';
$string['ethicalconstraints'] = 'Ethical constraints';
$string['timeline'] = 'Timeline';
$string['participants'] = 'Participants';
$string['submissions'] = 'Submissions';
$string['review'] = 'Review';
$string['integrityreview'] = 'Integrity review';
$string['archiveoutput'] = 'Archive output';
$string['badgescompetencies'] = 'Badges and competencies';

$string['name'] = 'Challenge name';
$string['name_help'] = 'Enter the visible name of this UCKK Challenge.';
$string['intro'] = 'Description';
$string['intro_help'] = 'Describe the challenge, its purpose, and expected learning or governance value.';
$string['statement'] = 'Statement';
$string['statement_help'] = 'State the challenge clearly. The statement should define what is being asked, why it matters, and what evidence is expected.';
$string['context'] = 'Context';
$string['context_help'] = 'Describe the learning, institutional, social, or public context of this challenge.';
$string['rules'] = 'Rules';
$string['rules_help'] = 'Define what is allowed, required, prohibited, and contestable.';
$string['corridors'] = 'Corridors of action';
$string['corridors_help'] = 'Describe acceptable action paths. Corridors prevent unbounded public pressure, harassment, or unsafe mobilisation.';
$string['evidence'] = 'Evidence';
$string['evidence_help'] = 'Describe the evidence required to complete the challenge.';
$string['criteria'] = 'Criteria';
$string['criteria_help'] = 'Describe how submissions will be evaluated.';
$string['ethics'] = 'Ethical constraints';
$string['ethics_help'] = 'Describe dignity, safety, integrity, privacy, and anti-abuse constraints.';
$string['visibility'] = 'Visibility';
$string['provenance'] = 'Provenance';
$string['status'] = 'Status';
$string['metadata'] = 'Metadata';
$string['duedate'] = 'Due date';
$string['reviewdeadline'] = 'Review deadline';
$string['none'] = 'None';
$string['notset'] = 'Not set';
$string['savechanges'] = 'Save changes';
$string['submitproof'] = 'Submit proof';
$string['evaluate'] = 'Evaluate';
$string['archive'] = 'Archive';
$string['viewarchive'] = 'View archive';
$string['openintegritycase'] = 'Open integrity case';
$string['contest'] = 'Contest';
$string['continue'] = 'Continue';
$string['viewdetails'] = 'View details';

// Challenge types.
$string['type'] = 'Challenge type';
$string['type:internal_learning'] = 'Internal learning';
$string['type:public_pedagogical'] = 'Public pedagogical';
$string['type:institutional_audit'] = 'Institutional audit';
$string['type:system_mapping'] = 'System mapping';
$string['type:prototype'] = 'Prototype';
$string['type:mobilisation'] = 'Mobilisation';
$string['type:capstone'] = 'Capstone';
$string['type:king_klown_public'] = 'King Klown public challenge';

// Challenge statuses.
$string['status:draft'] = 'Draft';
$string['status:published'] = 'Published';
$string['status:open'] = 'Open';
$string['status:submitted'] = 'Submitted';
$string['status:under_review'] = 'Under review';
$string['status:integrity_review'] = 'Integrity review';
$string['status:revision_required'] = 'Revision required';
$string['status:resubmitted'] = 'Resubmitted';
$string['status:validated'] = 'Validated';
$string['status:archived'] = 'Archived';
$string['status:closed'] = 'Closed';
$string['status:contested'] = 'Contested';
$string['status:invalidated'] = 'Invalidated';
$string['status:withdrawn'] = 'Withdrawn';
$string['status:expired'] = 'Expired';

// Common UCKK statuses.
$string['commonstatus:draft'] = 'Draft';
$string['commonstatus:active'] = 'Active';
$string['commonstatus:hidden'] = 'Hidden';
$string['commonstatus:pending'] = 'Pending';
$string['commonstatus:pending_review'] = 'Pending review';
$string['commonstatus:validated'] = 'Validated';
$string['commonstatus:rejected'] = 'Rejected';
$string['commonstatus:correction_required'] = 'Correction required';
$string['commonstatus:contested'] = 'Contested';
$string['commonstatus:invalidated'] = 'Invalidated';
$string['commonstatus:closed'] = 'Closed';
$string['commonstatus:archived'] = 'Archived';
$string['commonstatus:cancelled'] = 'Cancelled';

// Visibility.
$string['visibility:private'] = 'Private';
$string['visibility:user'] = 'User';
$string['visibility:group'] = 'Group';
$string['visibility:course'] = 'Course';
$string['visibility:cohort'] = 'Cohort';
$string['visibility:program'] = 'Program';
$string['visibility:institution'] = 'Institution';
$string['visibility:public'] = 'Public';
$string['visibility:restricted'] = 'Restricted';
$string['visibility:restricted_integrity'] = 'Restricted integrity';
$string['visibility:hidden'] = 'Hidden';
$string['visibility:archived'] = 'Archived';

// Provenance.
$string['provenance:human'] = 'Human';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:imported'] = 'Imported';
$string['provenance:system'] = 'System';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:integrity'] = 'Integrity';

// Proof types.
$string['proof'] = 'Proof';
$string['proofs'] = 'Proofs';
$string['proof:type'] = 'Proof type';
$string['proof:text'] = 'Text';
$string['proof:file'] = 'File';
$string['proof:url'] = 'URL';
$string['proof:dataset'] = 'Dataset';
$string['proof:image'] = 'Image';
$string['proof:video'] = 'Video';
$string['proof:testimony'] = 'Testimony';
$string['proof:observation'] = 'Observation';
$string['proof:ai_log'] = 'AI log';
$string['proof:decision_record'] = 'Decision record';
$string['proof:source'] = 'Source';
$string['proof:author'] = 'Author';
$string['proof:date'] = 'Date';
$string['proof:relationtocriteria'] = 'Relation to criteria';
$string['proof:integritystate'] = 'Integrity state';
$string['proof:submit'] = 'Submit proof';
$string['proof:submitted'] = 'Proof submitted';
$string['proof:none'] = 'No proof has been submitted yet.';

// Evaluation.
$string['evaluation'] = 'Evaluation';
$string['rubric'] = 'Rubric';
$string['mentorfeedback'] = 'Mentor feedback';
$string['competencyrating'] = 'Competency rating';
$string['badgetrigger'] = 'Badge trigger';
$string['publicsummary'] = 'Public summary';
$string['privatefeedback'] = 'Private feedback';
$string['archiveexport'] = 'Archive export';
$string['evaluation:pending'] = 'Evaluation pending';
$string['evaluation:completed'] = 'Evaluation completed';
$string['evaluation:requiresrevision'] = 'Revision required';
$string['evaluation:validated'] = 'Validated';

// Integrity and anti-abuse.
$string['integrity'] = 'Integrity';
$string['integritynotice'] = 'Integrity notice';
$string['integrityrequired'] = 'Integrity review required';
$string['integrityclear'] = 'No integrity issue is currently recorded.';
$string['integritypaused'] = 'Validation is paused while an integrity case is open.';
$string['antiabuse'] = 'Anti-abuse constraints';
$string['antiabuse_help'] = 'Challenges must not enable targeted harassment, humiliation, confusion between fiction and fact, fabricated evidence, coordinated intimidation, doxxing, unbounded public pressure, or AI deception.';
$string['contestability'] = 'Contestability';
$string['contestability_help'] = 'Challenge outcomes can be contested through the UCKK integrity and review workflow when rules allow it.';

// AI policy.
$string['ai'] = 'AI';
$string['aiassisted'] = 'AI-assisted';
$string['ailabel'] = 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';
$string['aipolicy'] = 'AI policy';
$string['aipolicy_help'] = 'AI may assist drafting, mapping, summarising, and uncertainty extraction. It must not grade final work, validate integrity, close cases, publish decisions, award badges, certify competencies, erase evidence, or replace human review.';
$string['airequireshumanvalidation'] = 'AI outputs require human validation';
$string['ainotfinalauthority'] = 'AI is not a final authority.';

// Settings.
$string['settings:general'] = 'General settings';
$string['settings:general_desc'] = 'Configure the general behaviour of Défis King Klown.';
$string['settings:enabled'] = 'Enable UCKK challenges';
$string['settings:enabled_desc'] = 'Allow UCKK Challenge activities to be used.';
$string['settings:defaultvisibility'] = 'Default visibility';
$string['settings:defaultvisibility_desc'] = 'Default visibility assigned to new challenge records.';
$string['settings:defaultprovenance'] = 'Default provenance';
$string['settings:defaultprovenance_desc'] = 'Default provenance assigned to new challenge records.';

$string['settings:workflow'] = 'Workflow settings';
$string['settings:workflow_desc'] = 'Configure evidence, validation, contestation, and review defaults.';
$string['settings:requireevidence'] = 'Require evidence';
$string['settings:requireevidence_desc'] = 'Require each challenge submission to include evidence.';
$string['settings:requireprovenance'] = 'Require provenance';
$string['settings:requireprovenance_desc'] = 'Require provenance information for challenge evidence.';
$string['settings:requirehumanvalidation'] = 'Require human validation';
$string['settings:requirehumanvalidation_desc'] = 'Require human validation before a challenge can be treated as validated.';
$string['settings:allowpublicchallenges'] = 'Allow public challenges';
$string['settings:allowpublicchallenges_desc'] = 'Allow challenge summaries to be exposed publicly when visibility and permissions allow it.';
$string['settings:allowcontestation'] = 'Allow contestation';
$string['settings:allowcontestation_desc'] = 'Allow validated challenge outcomes to be contested through the UCKK workflow.';
$string['settings:defaultreviewdays'] = 'Default review period';
$string['settings:defaultreviewdays_desc'] = 'Default time allowed for review after a challenge submission.';

$string['settings:integrity'] = 'Integrity settings';
$string['settings:integrity_desc'] = 'Configure integrity review and archive behaviour for challenges.';
$string['settings:enableintegrityreview'] = 'Enable integrity review';
$string['settings:enableintegrityreview_desc'] = 'Allow challenges to enter the Inquisiteur integrity review workflow.';
$string['settings:pausevalidationonintegritycase'] = 'Pause validation when an integrity case is open';
$string['settings:pausevalidationonintegritycase_desc'] = 'Prevent final validation while a related integrity case remains open.';
$string['settings:archivevalidatedproofs'] = 'Archive validated proofs';
$string['settings:archivevalidatedproofs_desc'] = 'Send validated challenge proofs to the UCKK archive workflow when appropriate.';

$string['settings:ai'] = 'AI settings';
$string['settings:ai_desc'] = 'Configure non-sovereign AI assistance in challenges.';
$string['settings:allowaiassistance'] = 'Allow AI assistance';
$string['settings:allowaiassistance_desc'] = 'Allow AI-assisted drafting, mapping, summarising, and uncertainty extraction where permitted.';
$string['settings:logaiuse'] = 'Log AI use';
$string['settings:logaiuse_desc'] = 'Log AI prompts and outputs associated with challenge work.';
$string['settings:requireaiuncertaintylabel'] = 'Require AI uncertainty label';
$string['settings:requireaiuncertaintylabel_desc'] = 'Require AI-assisted content to display that it is not a final authority.';
$string['settings:allowaidecisionautomation'] = 'Allow AI decision automation';
$string['settings:allowaidecisionautomation_desc'] = 'This must remain disabled in UCKK-Moodle. AI may assist but must not validate, grade, certify, or close decisions.';

// Events.
$string['eventchallengecreated'] = 'UCKK Challenge created';
$string['eventchallengeupdated'] = 'UCKK Challenge updated';
$string['eventchallengeviewed'] = 'UCKK Challenge viewed';
$string['eventchallengedeleted'] = 'UCKK Challenge deleted';
$string['eventsubmissioncreated'] = 'UCKK Challenge submission created';
$string['eventsubmissionupdated'] = 'UCKK Challenge submission updated';
$string['eventsubmissionreviewed'] = 'UCKK Challenge submission reviewed';
$string['eventintegrityreviewrequested'] = 'UCKK Challenge integrity review requested';
$string['eventchallengearchived'] = 'UCKK Challenge archived';

// Completion.
$string['completiondetail:submitproof'] = 'Submit proof';
$string['completiondetail:validated'] = 'Receive validation';
$string['completiondetail:archive'] = 'Archive challenge output';
$string['completionrequiresubmission'] = 'Student must submit proof';
$string['completionrequirevalidation'] = 'Submission must be validated';
$string['completionrequirearchive'] = 'Challenge output must be archived';

// Errors.
$string['error:challengeclosed'] = 'This challenge is closed.';
$string['error:challengenotopen'] = 'This challenge is not open for submissions.';
$string['error:nopermission'] = 'You do not have permission to perform this action.';
$string['error:missingevidence'] = 'Evidence is required.';
$string['error:missingprovenance'] = 'Provenance is required.';
$string['error:invalidstatus'] = 'Invalid challenge status.';
$string['error:invalidvisibility'] = 'Invalid visibility value.';
$string['error:invalidprovenance'] = 'Invalid provenance value.';
$string['error:integritycaseopen'] = 'This action is blocked while an integrity case is open.';
$string['error:aidecisionautomationdisabled'] = 'AI decision automation is disabled in UCKK-Moodle.';

// Privacy.
$string['privacy:metadata:uckkchallenge'] = 'Stores UCKK Challenge activity instances.';
$string['privacy:metadata:uckkchallenge:course'] = 'The course containing the challenge.';
$string['privacy:metadata:uckkchallenge:name'] = 'The challenge name.';
$string['privacy:metadata:uckkchallenge:intro'] = 'The challenge description.';
$string['privacy:metadata:uckkchallenge:status'] = 'The challenge status.';
$string['privacy:metadata:uckkchallenge:visibility'] = 'The challenge visibility.';
$string['privacy:metadata:uckkchallenge:provenance'] = 'The challenge provenance.';
$string['privacy:metadata:uckkchallenge:timecreated'] = 'The time the challenge was created.';
$string['privacy:metadata:uckkchallenge:timemodified'] = 'The time the challenge was last modified.';

$string['privacy:metadata:uckkchallenge_sub'] = 'Stores UCKK Challenge submissions.';
$string['privacy:metadata:uckkchallenge_sub:challengeid'] = 'The related challenge.';
$string['privacy:metadata:uckkchallenge_sub:userid'] = 'The user who submitted proof.';
$string['privacy:metadata:uckkchallenge_sub:status'] = 'The submission status.';
$string['privacy:metadata:uckkchallenge_sub:visibility'] = 'The submission visibility.';
$string['privacy:metadata:uckkchallenge_sub:provenance'] = 'The submission provenance.';
$string['privacy:metadata:uckkchallenge_sub:timecreated'] = 'The time the submission was created.';
$string['privacy:metadata:uckkchallenge_sub:timemodified'] = 'The time the submission was last modified.';