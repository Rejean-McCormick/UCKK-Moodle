# 08 — Integrity, Archives and Privacy

**Status:** Final governance and compliance specification, standalone-first and Konnaxion-connected-mode aligned  
**Purpose:** Define Inquisiteur, Archives, provenance, privacy, retention, auditability, standalone archive completeness, optional Smart Vote contestability, optional Konnaxion-derived data handling, and implementation gates.

## 0. Canonical alignment variables for this document

This document must use the canonical variables defined by `DOC_00` and `DOC_11` and must not rename or silently redefine them. `DOC_11` owns shared cross-document names, deprecated aliases, operating-mode variables, and standalone-vs-connected classification.

| Variable                    | Canonical value                                                                                                                            | Rule in this document                                                                                         |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `DOC_08`                    | `08_integrity_archives_and_privacy.md`                                                                                                     | Owns integrity, archive, privacy, retention, redaction, provenance, and contestability rules.                 |
| `SOURCE_FAMILY_KONNAXION`   | `Konnaxion external source family`                                                                                                         | External source family for Smart Vote objects and readings in connected mode.                                  |
| `EXTERNAL_SYSTEM_KONNAXION` | `Konnaxion`                                                                                                                                | Optional external system integrated through Moodle-side services only.                                         |
| `SMART_VOTE_CANONICAL_RULE` | `Konnaxion computes Smart Vote readings. UCKK-Moodle owns Assembly decisions. Archives preserve both, with provenance and contestability.` | Binding rule when Smart Vote exists; standalone Assembly decisions remain valid without Smart Vote.            |
| `SMART_VOTE_AUTHORITY`      | `computed_reading_only`                                                                                                                    | Smart Vote may inform but must not decide, award, sanction, validate evidence, or close contestations.        |
| `ASSEMBLY_AUTHORITY`        | `human_institutional_decision`                                                                                                             | Final Assembly decisions remain Moodle-side human/institutional records.                                      |
| `ARCHIVE_AUTHORITY`         | `provenance_and_contestation_memory`                                                                                                       | Archives preserve imported readings, decision records, minority reports, provenance, and contestation trails. |
| `DIRECT_WRITE_RULE`         | `external_systems_never_write_moodle_source_tables`                                                                                        | Konnaxion must not write directly into Moodle source tables in connected mode.                                 |
| `PERMISSION_RULE`           | `moodle_capabilities_remain_authoritative`                                                                                                 | External identifiers, symbolic titles, or Konnaxion roles never grant Moodle permissions.                     |

### 0.1 Operating-mode variables

| Variable                              | Canonical value              | Rule in this document                                                                                  |
| ------------------------------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------ |
| `OPERATING_MODE_STANDALONE`           | `standalone_core`            | UCKK-Moodle integrity, archive, privacy, retention, and reporting workflows must work without Konnaxion. |
| `OPERATING_MODE_KONNAXION_CONNECTED` | `connected_konnaxion`        | Enables Konnaxion-derived readings, mappings, sync logs, Smart Vote snapshots, and connected-mode tests. |
| `KONNAXION_REQUIRED_FOR_CORE`         | `false`                      | Konnaxion-derived records must never be required for standalone archive completeness.                    |
| `SMART_VOTE_REQUIRED_FOR_CORE`        | `false`                      | Smart Vote snapshots must never be required for ordinary Assembly decision preservation.                 |
| `KONNAXION_DEFAULT_STATE`             | `disabled`                   | Konnaxion integration is disabled unless explicitly configured by authorized administrators.             |
| `SMART_VOTE_DEFAULT_STATE`            | `disabled_until_connected`   | Smart Vote actions, reports, and contestation paths are hidden or disabled until connected mode exists. |

All Konnaxion and Smart Vote requirements in this document are **connected-mode requirements** unless a sentence explicitly says they apply to standalone mode.

### 0.2 Smart Vote data variables

| Variable                 | Canonical field/key            | Privacy and archive rule                                                                              |
| ------------------------ | ------------------------------ | ----------------------------------------------------------------------------------------------------- |
| `SV_RAW_DATA`            | `raw_data`                     | Imported or referenced source facts before interpretation; privacy-classified before display/export.  |
| `SV_READING_METHOD`      | `reading_method`               | Declared method, modality, weighting, or algorithmic reading rule; must be archived with the reading. |
| `SV_COMPUTED_READING`    | `computed_reading`             | Non-sovereign Smart Vote output; may be contested and superseded.                                     |
| `SV_EXPERTISE_WEIGHT`    | `expertise_weight`             | Sensitive when linkable to a user; requires restricted handling and privacy export review.            |
| `SV_HUMAN_DECISION`      | `human_institutional_decision` | Moodle-side Assembly decision; cannot be overwritten by Smart Vote.                                   |
| `SV_MINORITY_REPORT`     | `minority_report`              | Preserved as deliberative memory, not hidden by aggregate results.                                    |
| `SV_INTEGRITY_WARNING`   | `integrity_warning`            | May open review; not itself a sanction or final finding.                                              |
| `SV_CONTESTATION_STATUS` | `contestation_status`          | Required on imported readings, snapshots, and linked decision records.                                |
| `SV_ARCHIVE_ITEM_ID`     | `archiveitemid`                | Link to preserved archive item when archived.                                                         |

### 0.3 Connected-mode Konnaxion table variables relevant to integrity, archive, and privacy

| Variable                        | Canonical table                | Owner              | DOC_08 responsibility                                                   |
| ------------------------------- | ------------------------------ | ------------------ | ----------------------------------------------------------------------- |
| `TABLE_KONNAXION_USER_MAP`      | `local_uckk_kx_user_map`       | `local_uckk`       | Privacy classification, export, deletion/anonymisation rules.           |
| `TABLE_KONNAXION_OBJECT_MAP`    | `local_uckk_kx_object_map`     | `local_uckk`       | Provenance, privacy coverage, retention classification.                 |
| `TABLE_KONNAXION_SYNC_LOG`      | `local_uckk_kx_sync_log`       | `local_uckk`       | Retention, redaction, operational log export rules.                     |
| `TABLE_SMART_VOTE_TARGET_MAP`   | `uckkassembly_kx_vote_target`  | `mod_uckkassembly` | Contestability, archive linkage, visibility and privacy classification. |
| `TABLE_SMART_VOTE_SNAPSHOT`     | `uckkassembly_sv_snapshot`     | `mod_uckkassembly` | Immutable reading snapshot, provenance, redaction, retention, export.   |
| `TABLE_SMART_VOTE_RESULT_AUDIT` | `uckkassembly_sv_result_audit` | `mod_uckkassembly` | Review, correction, contestation, supersession, and audit trail.        |

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`.

These tables are connected-mode tables. Their absence or emptiness must not prevent standalone installation, standalone integrity review, standalone archive preservation, or standalone privacy export for core UCKK-Moodle records.

### 0.4 Connected-mode capability variables relevant to this document

| Variable                      | Canonical capability                | Required protection                                                   |
| ----------------------------- | ----------------------------------- | --------------------------------------------------------------------- |
| `CAP_MANAGE_KONNAXION`        | `local/uckk:managekonnaxion`        | Manage Konnaxion configuration and integration behavior.              |
| `CAP_MAP_KONNAXION_OBJECTS`   | `local/uckk:mapkonnaxionobjects`    | Create and maintain Konnaxion object mappings.                        |
| `CAP_VIEW_KONNAXION_LOGS`     | `local/uckk:viewkonnaxionlogs`      | View Konnaxion sync logs after privacy filtering.                     |
| `CAP_REQUEST_SMART_VOTE`      | `mod/uckkassembly:requestsmartvote` | Request Smart Vote reading from mapped Assembly object.               |
| `CAP_VIEW_SMART_VOTE`         | `mod/uckkassembly:viewsmartvote`    | View permitted Smart Vote readings.                                   |
| `CAP_REVIEW_SMART_VOTE`       | `mod/uckkassembly:reviewsmartvote`  | Review, accept as reading, or supersede imported readings.            |
| `CAP_CONTEST_SMART_VOTE`      | `mod/uckkassembly:contestsmartvote` | Contest a Smart Vote snapshot or linked decision.                     |
| `CAP_ARCHIVE_SMART_VOTE`      | `mod/uckkarchive:archivesmartvote`  | Archive Smart Vote readings, snapshots, and provenance packages.      |
| `CAP_VIEW_SMART_VOTE_REPORTS` | `report/uckk:viewsmartvotereports`  | View Smart Vote reports after context, privacy, and redaction checks. |

These capabilities are connected-mode capabilities. They must not be required to open, review, archive, export, or report ordinary standalone UCKK integrity and archive records.

## 1. Integrity principle

UCKK-Moodle must make learning public enough to be meaningful and protected enough to avoid abuse.

Integrity protects:

```text
truth of facts
dignity of people
clarity of rules
quality of evidence
procedural justice
non-manipulation
contestability
memory
privacy
reversibility where legally required
source provenance
external-system accountability
```

Integrity must never be reduced to punishment. It is a procedural system for preserving trust, evidence, correction, appeal, contestability, and institutional memory.

When Konnaxion-connected mode is enabled, integrity must also protect the Smart Vote boundary:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

No integrity workflow may convert a Konnaxion reading, AI output, external sync status, symbolic title, or report warning into final institutional authority.

Standalone Assembly decisions and Archive records remain complete when no Konnaxion reading, Smart Vote snapshot, or Konnaxion-derived provenance exists. In standalone mode, absence of Smart Vote is valid provenance, not a missing record.

## 2. `tool_uckkintegrity`

### 2.1 Case types

```text
proof_quality
fiction_fact_confusion
ai_misuse
harassment_or_humiliation
dignity_violation
authority_capture
assessment_dispute
challenge_dispute
assembly_dispute
smart_vote_dispute
smart_vote_method_dispute
smart_vote_snapshot_dispute
konnaxion_mapping_dispute
konnaxion_sync_dispute
archive_correction
privacy_concern
data_export_concern
data_deletion_concern
retention_concern
redaction_concern
```

The `smart_vote_*` and `konnaxion_*` case types are enabled only for connected-mode records or for historical records imported from a connected-mode period. They are not required for standalone integrity operation.

### 2.2 Case state machine

```text
opened
→ triaged
→ assigned
→ under_review
→ waiting_for_response
→ correction_required
→ resolved
→ archived
```

Alternative states:

```text
dismissed
escalated
paused
reopened
superseded
linked_to_contestation
```

No state transition may occur without:

```text
actor id
timestamp
previous state
new state
reason
capability check
event record
source component
source object id
privacy classification
retention classification
```

When connected-mode records are involved, Smart Vote or Konnaxion-related case transitions must also record:

```text
Konnaxion object type when applicable
Konnaxion external id when applicable
Moodle mapping id when applicable
Smart Vote snapshot id when applicable
reading method
computed reading reference
human/institutional decision linkage when applicable
contestation status
archive item id when archived
```

### 2.3 Case record requirements

Every integrity case must include:

```text
case type
subject component
subject id
context
opened by
assigned Inquisiteur
summary
evidence links
parties
notes
decision
corrections
appeal path
archive summary
privacy classification
retention classification
public summary flag
restricted evidence flag
```

When connected-mode records are involved, Smart Vote and Konnaxion cases must additionally include:

```text
external system name
external object type
external object id
Moodle mapping table
Moodle mapping record id
sync status if relevant
reading method if relevant
raw data reference if retained
computed reading reference if retained
expertise weight classification if present
minority report linkage if present
Assembly decision linkage if present
archive item linkage
contestation status
superseded snapshot id if applicable
```

### 2.4 Inquisiteur actions

Allowed actions:

```text
open case
triage case
assign reviewer
request evidence
pause challenge validation
pause assembly publication
pause Smart Vote publication
pause Smart Vote report visibility
issue correction
recommend invalidation
record decision
close case
publish public summary
archive case summary
request redaction
request privacy export review
request deletion review
request Konnaxion mapping review
request Smart Vote snapshot review
mark Smart Vote snapshot contested
recommend Smart Vote snapshot supersession
```

Smart Vote and Konnaxion actions in this list apply only when connected-mode records exist. Standalone integrity review must not require Smart Vote publication, Smart Vote reports, or Konnaxion mapping review.

Forbidden silent actions:

```text
delete evidence without log
change grades without trace
modify archive history without version
hide a contestation without record
publish private case details publicly
assign self admin privileges
bypass privacy export/delete rules
convert AI output into final authority
convert Smart Vote output into final authority
convert Konnaxion sync output into final authority
overwrite Assembly decision with imported reading
overwrite Smart Vote snapshot without supersession trail
modify Konnaxion mapping without event and audit record
```

### 2.5 Required implementation files

`tool_uckkintegrity` must not be considered complete until these files exist and pass tests:

```text
admin/tool/uckkintegrity/version.php
admin/tool/uckkintegrity/db/access.php
admin/tool/uckkintegrity/db/install.xml
admin/tool/uckkintegrity/db/upgrade.php
admin/tool/uckkintegrity/db/services.php
admin/tool/uckkintegrity/classes/form/case_form.php
admin/tool/uckkintegrity/classes/form/review_form.php
admin/tool/uckkintegrity/classes/local/integrity_case.php
admin/tool/uckkintegrity/classes/local/integrity_policy.php
admin/tool/uckkintegrity/classes/local/smart_vote_integrity_policy.php
admin/tool/uckkintegrity/classes/local/konnaxion_integrity_policy.php
admin/tool/uckkintegrity/classes/output/case_view.php
admin/tool/uckkintegrity/classes/output/case_list.php
admin/tool/uckkintegrity/classes/external/open_case.php
admin/tool/uckkintegrity/classes/external/contest_smart_vote.php
admin/tool/uckkintegrity/classes/event/case_opened.php
admin/tool/uckkintegrity/classes/event/case_updated.php
admin/tool/uckkintegrity/classes/event/case_resolved.php
admin/tool/uckkintegrity/classes/event/case_archived.php
admin/tool/uckkintegrity/classes/event/smart_vote_contestation_opened.php
admin/tool/uckkintegrity/classes/event/konnaxion_mapping_disputed.php
admin/tool/uckkintegrity/classes/privacy/provider.php
admin/tool/uckkintegrity/amd/src/case.js
admin/tool/uckkintegrity/amd/src/review.js
admin/tool/uckkintegrity/tests/integrity_case_test.php
admin/tool/uckkintegrity/tests/smart_vote_integrity_test.php
admin/tool/uckkintegrity/tests/konnaxion_integrity_test.php
admin/tool/uckkintegrity/tests/privacy/provider_test.php
admin/tool/uckkintegrity/tests/behat/tool_uckkintegrity.feature
admin/tool/uckkintegrity/tests/behat/smart_vote_contestation.feature
```

Implementation rule:

```text
PHP page controllers must be .php files.
AMD modules must be JavaScript files.
No executable PHP, Moodle page-controller logic, `require_once`, `optional_param`, `required_param`, `require_login`, `require_capability`, or PHP variable usage may appear in `amd/src/*.js`.
A literal PHP example or `$PAGE->requires->js_call_amd(...)` reference inside a comment is documentation drift, not executable PHP; it should be cleaned during code polish but must not be classified as the same blocker as PHP controller code inside JavaScript.
No JavaScript AMD module may be stored as a .php page.
In connected mode, no integrity class may directly write to Konnaxion-owned external systems.
No integrity action may bypass Moodle capability checks.
```

## 3. `mod_uckkarchive`

### 3.1 Archive purpose

The archive is the institutional memory of UCKK-Moodle.

In standalone mode, it stores:

```text
proof
decisions
minutes
challenge results
course works
portfolio entries
Kristals
integrity summaries
versions
public summaries
restricted summaries
redaction decisions
privacy decisions
external-source provenance records where applicable
```

When Konnaxion-connected mode is enabled and connected-mode records exist, it may also store:

```text
Smart Vote reading snapshots
Smart Vote reading methods
Smart Vote minority reports
Smart Vote contestation summaries
Konnaxion mapping provenance packages
Konnaxion-derived report snapshots
```

The archive must preserve the distinction between:

```text
external raw data
computed reading
reviewed reading
human/institutional decision
minority report
integrity warning
contestation
correction
supersession
```

### 3.2 Archive item states

```text
draft
submitted
under_review
validated
published
restricted
contested
invalidated
superseded
archived
redacted
deleted_private
```

When connected-mode records exist, Smart Vote archive items may also use these sub-statuses through metadata or a dedicated field when implemented by the owning plugin:

```text
imported
accepted_as_reading
contested
superseded
archived
invalidated
```

### 3.3 Archive visibility

```text
private
course
cohort
program
institutional
public
restricted_integrity
restricted_privacy
restricted_smart_vote
restricted_konnaxion
```

Visibility must be enforced in:

```text
page controllers
external service classes
renderable/output classes
templates
file-serving callbacks
report queries
archive export services
Smart Vote report services
Konnaxion-derived data views
```

UI hiding is not sufficient. The service layer must enforce visibility before data is returned.

### 3.4 Required implementation files

`mod_uckkarchive` must not be considered complete until these files exist and pass tests:

```text
mod/uckkarchive/version.php
mod/uckkarchive/db/access.php
mod/uckkarchive/db/install.xml
mod/uckkarchive/db/upgrade.php
mod/uckkarchive/db/services.php
mod/uckkarchive/lib.php
mod/uckkarchive/locallib.php
mod/uckkarchive/mod_form.php
mod/uckkarchive/classes/form/archive_item_form.php
mod/uckkarchive/classes/local/archive_item.php
mod/uckkarchive/classes/local/archive_policy.php
mod/uckkarchive/classes/local/provenance.php
mod/uckkarchive/classes/local/versioning.php
mod/uckkarchive/classes/local/smart_vote_archive_policy.php
mod/uckkarchive/classes/local/konnaxion_provenance_policy.php
mod/uckkarchive/classes/output/archive_view.php
mod/uckkarchive/classes/output/archive_item_card.php
mod/uckkarchive/classes/external/get_archive.php
mod/uckkarchive/classes/external/get_archive_items.php
mod/uckkarchive/classes/external/add_item.php
mod/uckkarchive/classes/external/validate_item.php
mod/uckkarchive/classes/external/export_archive.php
mod/uckkarchive/classes/external/archive_smart_vote_snapshot.php
mod/uckkarchive/classes/event/archive_item_created.php
mod/uckkarchive/classes/event/archive_item_updated.php
mod/uckkarchive/classes/event/archive_item_validated.php
mod/uckkarchive/classes/event/archive_item_contested.php
mod/uckkarchive/classes/event/archive_item_redacted.php
mod/uckkarchive/classes/event/smart_vote_snapshot_archived.php
mod/uckkarchive/classes/privacy/provider.php
mod/uckkarchive/tests/archive_test.php
mod/uckkarchive/tests/smart_vote_archive_test.php
mod/uckkarchive/tests/konnaxion_provenance_test.php
mod/uckkarchive/tests/privacy/provider_test.php
mod/uckkarchive/tests/behat/uckkarchive.feature
mod/uckkarchive/tests/behat/smart_vote_archive.feature
```

Component rule:

```text
mod/uckkarchive/version.php must declare:
$plugin->component = 'mod_uckkarchive';
```

No archive plugin file may declare itself as `mod_uckkassembly`.

## 4. Provenance

Every archive item and critical UCKK object must include provenance.

Required provenance fields:

```text
origin component
origin object id
author
created time
source description
supporting evidence
revision history
validation state
hash when applicable
linked integrity case if applicable
privacy classification
retention classification
visibility state
external source family when applicable
external system when applicable
external object type when applicable
external object id when applicable
source version when applicable
provenance hash when applicable
```

Provenance must be attached to:

```text
challenge submissions
challenge evaluations
assembly motions
assembly votes/readings
assembly minutes
Smart Vote target mappings when connected mode is enabled
Smart Vote reading snapshots when connected mode is enabled
Smart Vote result audit records when connected mode is enabled
Smart Vote minority reports when connected mode is enabled
Smart Vote contestations when connected mode is enabled
Konnaxion user mappings when connected mode is enabled and retained
Konnaxion object mappings when connected mode is enabled
Konnaxion sync logs when connected mode is enabled and archived
archive items
integrity cases
AI-generated summaries
badges awarded from evidence
competency evidence
seed-generated institutional objects
```

AI-generated material must record:

```text
model/provider when known
requesting user
request context
prompt classification
redaction applied
human validation state
non-authority notice
```

When connected-mode material exists, Konnaxion-derived material must record:

```text
external system
external object type
external object id
source version or source timestamp
sync status
mapping record id
reading method
raw data reference
computed reading reference
review state
human/institutional decision linkage
contestation status
archive item id
```

## 5. Versioning

Archive item updates must create version records when:

```text
content changes
visibility changes
validation state changes
evidence link changes
public summary changes
integrity correction is applied
privacy redaction is applied
retention classification changes
AI-generated summary is replaced or validated
Smart Vote reading snapshot is accepted as reading
Smart Vote reading snapshot is contested
Smart Vote reading snapshot is superseded
Smart Vote minority report is added or changed
Konnaxion mapping provenance changes
Konnaxion-derived report snapshot is archived
```

Version records must include:

```text
previous state
new state
changed by
change reason
timestamp
integrity case id if applicable
privacy request id if applicable
redaction level if applicable
hash before when applicable
hash after when applicable
external source reference when applicable
Smart Vote snapshot id when applicable
superseded snapshot id when applicable
Konnaxion mapping id when applicable
```

Forbidden versioning behavior:

```text
overwrite validated public archive item without version
remove evidence link without version
change visibility without capability check
change public summary without audit event
delete restricted record without retention decision
overwrite Smart Vote snapshot without supersession record
remove Konnaxion provenance without audit event
change reading method without version
change human/institutional decision linkage without Assembly event
```

## 6. Privacy providers

Every plugin storing personal data must implement:

```text
classes/privacy/provider.php
```

Plugins that store user data must provide functions to:

```text
locate contexts
export user data
delete data for all users in a context
delete data for one user
locate users in a context
delete data for multiple users when applicable
redact restricted integrity data where full export is unsafe
preserve legally required institutional records where deletion is not allowed
classify Konnaxion-derived identifiers
export Smart Vote personal data where permitted
anonymize Smart Vote personal data where required
retain institutional Smart Vote aggregates where allowed
```

Plugins that do not store personal data must still document that fact through the proper Moodle privacy provider pattern.

Privacy providers are required for:

```text
local_uckk
block_uckk_dashboard
mod_uckkchallenge
mod_uckkassembly
mod_uckkarchive
tool_uckkintegrity
tool_uckkseed
report_uckk
aiprovider_uckk
format_uckk when preferences or user-specific display state are stored
theme_uckk when preferences or user-specific display state are stored
```

When connected-mode records exist, Konnaxion and Smart Vote privacy coverage must be owned as follows:

```text
local_uckk covers Konnaxion user mappings, object mappings, sync logs, configuration references, and shared integration records.
mod_uckkassembly covers Smart Vote target mappings, reading snapshots, result audit records, contestation links, and decision linkage.
mod_uckkarchive covers archived Smart Vote packages, archived provenance, archived minority reports, archived public summaries, redactions, and archive versions.
tool_uckkintegrity covers Smart Vote disputes, Konnaxion mapping disputes, restricted case evidence, and review notes.
report_uckk covers report caches only if they persist personal or sensitive derived data.
```

## 7. Personal data map

| Plugin                 | Personal data stored                                                                                                                                                   | Privacy provider expectation                                        |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| `local_uckk`           | Player profiles, symbolic roles, pathway assignments, portfolio links; in connected mode, Konnaxion user mappings, Konnaxion object mappings, and Konnaxion sync logs when actor-linked       | Full provider                                                       |
| `block_uckk_dashboard` | Usually none; preferences if stored; connected-mode displayed Smart Vote summaries if cached                                                                                          | Null provider or full provider if preferences/cache exist           |
| `mod_uckkchallenge`    | Submissions, feedback, files, reviews, validation history                                                                                                              | Full provider                                                       |
| `mod_uckkassembly`     | Motions, votes/readings, arguments, minutes contributions, contestation records; in connected mode, Smart Vote target mappings, Smart Vote snapshots, and Smart Vote result audit records     | Full provider                                                       |
| `mod_uckkarchive`      | Archive items, portfolio items, evidence files, provenance, validation records; in connected mode, archived Smart Vote snapshots, minority reports, and Konnaxion-derived provenance packages | Full provider                                                       |
| `tool_uckkintegrity`   | Case notes, evidence, decisions, parties, restricted records; in connected mode, Smart Vote disputes and Konnaxion mapping disputes                                                          | Full provider with redaction rules                                  |
| `tool_uckkseed`        | Seed logs, import actor, validation results when persisted, preset import errors involving users                                                                       | Full provider if logs persist; otherwise null provider              |
| `report_uckk`          | No primary data; displays derived data; in connected mode, may cache Smart Vote report rows if explicitly implemented                                                                     | Null provider or full provider documenting derived/report-only data |
| `aiprovider_uckk`      | Prompt/response logs if logging enabled, request actor, context                                                                                                        | Full provider if logging enabled                                    |
| `format_uckk`          | Course display state or user preferences if stored                                                                                                                     | Null provider or full provider if user data exists                  |
| `theme_uckk`           | User preferences only if stored                                                                                                                                        | Null provider or full provider if user data exists                  |

## 8. Privacy export requirements

User export must include:

```text
profile data
pathway assignments
challenge submissions
challenge feedback
assembly contributions
assembly participation records where exportable
Smart Vote participation records where exportable when connected mode is enabled
Smart Vote reading snapshots linked to the user where exportable when connected mode is enabled
Smart Vote contestations opened by or about the user when connected mode is enabled
Konnaxion user mappings linked to the user when connected mode is enabled
Konnaxion sync log entries linked to the user where retained when connected mode is enabled
archive items owned by user
portfolio items
integrity case participation where permitted
AI logs related to user where enabled
seed/import logs related to user where retained
```

Restricted integrity records may need redaction rules.

Export must distinguish:

```text
data authored by user
data about user
data mentioning user
data involving multiple parties
data imported from Konnaxion when connected mode is enabled
computed Smart Vote readings when connected mode is enabled
human/institutional Assembly decisions
institutional records that cannot be fully removed
restricted evidence requiring reviewer-only handling
expertise weights requiring restricted handling
minority reports requiring deliberative context
```

When Smart Vote export exists, it must never imply that a computed reading is the final Assembly decision unless the Assembly decision record separately says so.

## 9. Deletion and retention

Deletion must preserve institutional integrity while respecting privacy requirements.

Recommended behavior:

```text
User-owned private draft → delete
Validated public archive item → anonymize user where required, preserve institutional record when legally allowed
Integrity case involving multiple parties → redact according to role and retention policy
Assembly vote/readings → preserve aggregate decision, handle personal participation according to policy
Smart Vote raw personal participation data → delete, anonymize, or restrict according to policy and source contract
Smart Vote computed reading snapshot → preserve institutional snapshot where allowed, redact personal details when required
Smart Vote expertise weight linked to user → restrict, anonymize, or delete according to sensitivity and retention policy
Konnaxion user mapping → delete or anonymize when no longer needed, unless required for retained audit/provenance
Konnaxion object mapping → retain while the mapped institutional object is retained, unless invalidated
Konnaxion sync log → retain operationally for bounded period; redact identifiers when no longer needed
Challenge submission → delete or anonymize according to validation/publication state
AI log → delete, redact, or anonymize according to logging policy and context
Seed log → delete or anonymize actor if operational record can be preserved without identity
```

Retention configuration must exist for:

```text
integrity cases
restricted evidence
archive versions
Smart Vote reading snapshots
Smart Vote result audit records
Smart Vote contestations
Konnaxion user mappings
Konnaxion object mappings
Konnaxion sync logs
AI logs
seed logs
privacy request logs
audit events
```

Retention configuration must be visible to administrators and covered by tests.

When connected-mode records exist, Konnaxion-derived records must not be retained merely because they came from an external system. They must have a Moodle-side retention reason:

```text
active mapping
active Assembly workflow
archived decision provenance
open contestation
integrity case
privacy request
required audit period
institutional archive policy
```

## 10. Redaction model

Redaction levels:

```text
none
hide identity
remove private notes
remove files
remove external identifiers
remove expertise weights
replace with anonymized placeholder
restrict to integrity reviewers
restrict to privacy reviewers
restrict to Smart Vote reviewers
delete fully
```

Every redaction action must record:

```text
redaction level
actor
timestamp
reason
affected component
affected object id
previous visibility
new visibility
privacy request id if applicable
integrity case id if applicable
Smart Vote snapshot id if applicable
Konnaxion mapping id if applicable
external object id if retained in restricted form
```

Public summaries must never contain:

```text
private evidence
unredacted sensitive allegations
unnecessary personal data
AI-generated claims not validated by humans
private contact information
medical, legal, or protected data unless explicitly lawful and necessary
Konnaxion external identifiers unless explicitly approved
raw Smart Vote personal data
expertise weights linked to identifiable users
private minority-report authorship unless explicitly public
```

Redaction must not silently falsify the institutional record. When redaction changes what ordinary viewers can see, the archive must still preserve a restricted provenance trail for authorized reviewers where lawful and policy-permitted.

## 11. Connected-mode Smart Vote contestability model

When connected mode is enabled, every Smart Vote reading imported, computed, snapshotted, displayed, reported, or archived in UCKK-Moodle must be contestable. In standalone mode, ordinary Assembly votes/readings, decisions, minutes, and archive records remain contestable without Smart Vote.

### 11.1 Contestable objects

```text
Konnaxion user mapping
Konnaxion object mapping
Konnaxion sync result
Smart Vote target mapping
Smart Vote raw data reference
Smart Vote reading method
Smart Vote computed reading
Smart Vote expertise weight
Smart Vote snapshot
Smart Vote report row
Smart Vote minority report linkage
Smart Vote archive package
Assembly decision linkage to Smart Vote
```

### 11.2 Contestation states

```text
none
opened
under_review
waiting_for_response
correction_required
superseded
resolved
dismissed
archived
```

### 11.3 Contestation requirements

Every contestation must record:

```text
contestant
context
object type
object id
reason
evidence
privacy classification
retention classification
assigned reviewer
state
decision
correction if any
superseded object if any
archive item id
event record
```

### 11.4 Forbidden contestation behavior

```text
hiding a Smart Vote contestation without record
publishing contested Smart Vote reading as uncontested
using contested Smart Vote reading as final decision authority
deleting contested reading before retention decision
changing reading method without version
changing Konnaxion mapping without audit trail
closing contestation without decision note
```

## 12. Audit reports

`report_uckk` must support:

```text
archive validation queue
contested archive items
integrity cases by type
integrity cases by state
overdue integrity cases
challenge invalidations
assembly contestations
Smart Vote contestations
Smart Vote snapshots by state
Smart Vote snapshots by reading method
Smart Vote supersessions
Konnaxion mapping disputes
Konnaxion sync failures
Konnaxion sync retries
AI usage in restricted contexts
privacy exports
deletion/redaction actions
archive version changes
capability-sensitive access attempts where logged
```

Reports must enforce:

```text
context-aware capabilities
restricted record filtering
privacy redaction
no raw private notes for ordinary users
no unrestricted export of integrity data
no unrestricted export of Konnaxion identifiers
no unrestricted export of raw Smart Vote personal data
no presentation of computed Smart Vote reading as final Assembly decision
```

## 13. Implementation correction gates

This document is not complete in code until the implementation passes the core gates. Connected-mode gates are required only when Konnaxion/Smart Vote features are enabled or shipped in the selected release profile.

### 13.1 Filetype gate

```text
[ ] Every .php file starts with <?php unless it is a template intentionally not parsed as PHP.
[ ] No .php file contains Markdown fences accidentally copied from documentation.
[ ] Every amd/src/*.js file contains JavaScript only.
[ ] No amd/src/*.js file contains executable PHP, PHP opening tags, require_once, optional_param, required_param, require_login, require_capability, PHP variable usage, or Moodle page-controller logic.
[ ] Mustache templates contain Mustache/HTML only and no accidental PHP class files.
```

Static grep checks may still flag `$PAGE` or similar strings in comments. Those matches should be classified as `DOC_GATE_CLARIFICATION` or code-polish cleanup unless executable PHP/controller logic is actually present in the JavaScript file.

### 13.2 Moodle component gate

```text
[ ] Every version.php declares the component matching its plugin path.
[ ] Every plugin has correct $plugin->component.
[ ] Every plugin has correct @package values.
[ ] Every dependency declaration references an existing plugin.
[ ] No plugin file is copied from another plugin without corrected package/component names.
[ ] No `mod_uckkarchive` file declares itself as `mod_uckkassembly`.
[ ] No Konnaxion/Smart Vote implementation is placed in the wrong owning plugin when connected-mode features are shipped.
```

### 13.3 Class-layer gate

Every referenced namespaced class must exist under the correct plugin `classes/` path.

Required class categories:

```text
classes/form/*
classes/local/*
classes/output/*
classes/external/*
classes/event/*
classes/task/*
classes/privacy/provider.php
```

No `db/services.php` entry may exist unless the declared external class exists.

No `db/tasks.php` entry may exist unless the declared scheduled task class exists.

No page controller may import a form, event, policy, output, or local service class that does not exist.

Connected-mode Smart Vote and Konnaxion class requirements:

```text
[ ] In connected mode, Konnaxion privacy and sync policy classes exist in `local_uckk`.
[ ] In connected mode, Smart Vote contestation and snapshot policy classes exist in `mod_uckkassembly`.
[ ] In connected mode, Smart Vote archive policy classes exist in `mod_uckkarchive`.
[ ] In connected mode, Smart Vote integrity policy classes exist in `tool_uckkintegrity`.
[ ] In connected mode, Smart Vote report/export classes exist in `report_uckk`.
```

### 13.4 Capability gate

Every page, external service, file-serving callback, report, and write action must declare and test:

```text
context
required capability
allowed role archetypes
forbidden broad role grants
risk bitmask where applicable
restricted-data behavior
```

In connected mode, Konnaxion and Smart Vote capability checks must include:

```text
[ ] `local/uckk:managekonnaxion`
[ ] `local/uckk:mapkonnaxionobjects`
[ ] `local/uckk:viewkonnaxionlogs`
[ ] `mod/uckkassembly:requestsmartvote`
[ ] `mod/uckkassembly:viewsmartvote`
[ ] `mod/uckkassembly:reviewsmartvote`
[ ] `mod/uckkassembly:contestsmartvote`
[ ] `mod/uckkarchive:archivesmartvote`
[ ] `report/uckk:viewsmartvotereports`
```

These capability checks are not required for standalone records that do not invoke Konnaxion or Smart Vote.

### 13.5 Privacy gate

```text
[ ] Every personal data table maps to one privacy provider.
[ ] Every privacy provider has PHPUnit coverage.
[ ] Export includes all user-owned and user-related UCKK records where permitted.
[ ] Delete/anonymize behavior is defined for every table.
[ ] Multi-party records have redaction rules.
[ ] Restricted integrity data is not exposed in ordinary exports without policy handling.
[ ] In connected mode, Konnaxion user mappings are covered by `local_uckk` privacy provider.
[ ] In connected mode, Konnaxion sync logs have retention and redaction rules.
[ ] In connected mode, Smart Vote snapshots are covered by `mod_uckkassembly` privacy provider.
[ ] In connected mode, archived Smart Vote packages are covered by `mod_uckkarchive` privacy provider.
[ ] In connected mode, Smart Vote disputes are covered by `tool_uckkintegrity` privacy provider.
[ ] In connected mode, Smart Vote reports are privacy-filtered before display and export.
```

### 13.6 Install and upgrade gate

```text
[ ] PHP lint passes.
[ ] XMLDB install.xml validates.
[ ] upgrade.php is parseable and idempotent.
[ ] admin/cli/upgrade.php completes on a clean Moodle target.
[ ] admin/cli/purge_caches.php completes after install.
[ ] No plugin install warning is accepted as harmless without documentation.
[ ] In connected mode, Konnaxion and Smart Vote tables declared in DOC_04 exist in the correct owning plugins.
[ ] In connected mode, Konnaxion and Smart Vote privacy metadata is installed with the owning plugins.
[ ] No external system direct-write path is required for install or upgrade.
```

### 13.7 Test gate

Required test coverage:

```text
PHPUnit: integrity case transitions
PHPUnit: archive versioning
PHPUnit: archive visibility
PHPUnit: privacy export/delete for each data-storing plugin
PHPUnit: external service permission checks
PHPUnit: AI log redaction when enabled
PHPUnit: seed log retention when persisted
Connected mode — PHPUnit: Konnaxion user mapping privacy export/delete
Connected mode — PHPUnit: Konnaxion object mapping retention
Connected mode — PHPUnit: Konnaxion sync log retention and redaction
Connected mode — PHPUnit: Smart Vote snapshot export/redaction
Connected mode — PHPUnit: Smart Vote contestation workflow
Connected mode — PHPUnit: Smart Vote supersession/versioning
Connected mode — PHPUnit: Smart Vote report privacy filtering
Behat: Inquisiteur opens and closes case
Behat: archive item is validated and versioned
Behat: contested item produces integrity case
Behat: privacy export includes UCKK records
Behat: ordinary user cannot see restricted integrity data
Behat: AI warning remains non-authoritative
Connected mode — Behat: Smart Vote reading can be contested
Connected mode — Behat: contested Smart Vote reading is visibly marked
Connected mode — Behat: Smart Vote reading does not publish final Assembly decision
Connected mode — Behat: Konnaxion sync failure is logged and privacy-filtered
```

### 13.8 Canonical variable gate

```text
[ ] In connected mode, `SOURCE_FAMILY_KONNAXION` is used consistently.
[ ] In connected mode, `EXTERNAL_SYSTEM_KONNAXION` is used consistently.
[ ] In connected mode, `SMART_VOTE_CANONICAL_RULE` appears in every Smart Vote governance context.
[ ] In connected mode, `SMART_VOTE_AUTHORITY` remains `computed_reading_only`.
[ ] `ASSEMBLY_AUTHORITY` remains `human_institutional_decision`.
[ ] `ARCHIVE_AUTHORITY` remains `provenance_and_contestation_memory`.
[ ] `KONNAXION_REQUIRED_FOR_CORE` remains `false`.
[ ] `SMART_VOTE_REQUIRED_FOR_CORE` remains `false`.
[ ] In connected mode, all `TABLE_KONNAXION_*` and `TABLE_SMART_VOTE_*` names match DOC_00 and DOC_04.
[ ] In connected mode, all `SV_*` fields are handled in provenance, privacy, retention, redaction, and archive logic.
[ ] In connected mode, all Konnaxion/Smart Vote capabilities match DOC_05.
[ ] No alternate table, service, event, or capability names are introduced locally in DOC_08.
```

## 14. Definition of done

### 14.1 Core standalone definition of done

```text
[ ] Integrity cases work end-to-end without Konnaxion enabled.
[ ] Archive items are versioned.
[ ] Archive items enforce visibility in service layer and UI.
[ ] Integrity actions generate events.
[ ] Privacy providers export and delete personal data for core UCKK-Moodle records.
[ ] Public archive items cannot be silently modified.
[ ] Restricted integrity information is not exposed to ordinary users.
[ ] Reports support governance review without requiring Smart Vote records.
[ ] Every required core class referenced by pages/services/tasks exists.
[ ] Every core db/services.php declaration has a matching external class.
[ ] Every core db/tasks.php declaration has a matching task class.
[ ] PHP/JS filetype gates pass.
[ ] Moodle install and upgrade pass on a clean target with Konnaxion disabled.
[ ] PHPUnit and Behat coverage exists for standalone integrity, archive, and privacy workflows.
[ ] Standalone archives may preserve Assembly decisions without any Smart Vote snapshot.
[ ] Absence of a Smart Vote reading is valid provenance, not a missing record.
[ ] `KONNAXION_REQUIRED_FOR_CORE` remains `false`.
[ ] `SMART_VOTE_REQUIRED_FOR_CORE` remains `false`.
```

### 14.2 Optional Konnaxion-connected definition of done

```text
[ ] Konnaxion-derived records have privacy, retention, redaction, and provenance rules.
[ ] Smart Vote readings are archived as readings, not final decisions.
[ ] Smart Vote snapshots preserve raw data reference, reading method, computed reading, contestation status, and provenance.
[ ] Smart Vote contestations open integrity-review paths.
[ ] Smart Vote supersessions create version records.
[ ] Konnaxion mappings can be reviewed and contested.
[ ] Konnaxion sync logs are privacy-filtered and retention-bound.
[ ] Reports never expose raw Smart Vote or Konnaxion data beyond capability and privacy rules.
[ ] Archives preserve both Konnaxion-derived readings and UCKK-Moodle Assembly decisions with provenance and contestability when Smart Vote readings exist.
[ ] Connected-mode tests prove that disabling Konnaxion hides or disables Smart Vote-specific actions without breaking core archive and integrity workflows.
```


---

## Update note — 2026-05-13 filetype-gate clarification

This revision keeps the real blocker intact: executable PHP or Moodle page-controller code must not appear in AMD JavaScript files, and AMD JavaScript modules must not be stored as `.php` pages. It also prevents false classification of documentation examples or comment-only `$PAGE->requires->js_call_amd(...)` references as equivalent to executable PHP-in-JS blockers.
