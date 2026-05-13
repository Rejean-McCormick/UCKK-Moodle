# 08 — Integrity, Archives and Privacy

**Status:** Final governance and compliance specification  
**Purpose:** Define Inquisiteur, Archives, provenance, privacy, retention, and auditability.

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
```

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
archive_correction
privacy_concern
```

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
issue correction
recommend invalidation
record decision
close case
publish public summary
archive case summary
```

Forbidden silent actions:

```text
delete evidence without log
change grades without trace
modify archive history without version
hide a contestation without record
publish private case details publicly
assign self admin privileges
```

## 3. `mod_uckkarchive`

### 3.1 Archive purpose

The archive is the institutional memory of UCKK-Moodle.

It stores:

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
```

Visibility must be enforced in service layer and UI.

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
```

Version records must include:

```text
previous state
new state
changed by
change reason
timestamp
integrity case id if applicable
```

## 6. Privacy providers

Every plugin storing personal data must implement:

```text
classes/privacy/provider.php
```

Plugins that store user data must provide functions to locate contexts, export user data, delete data for all users in a context, delete data for a user, locate users in a context, and delete data for multiple users when applicable.

## 7. Personal data map

| Plugin | Personal data stored |
|---|---|
| `local_uckk` | Player profiles, symbolic roles, pathway assignments |
| `block_uckk_dashboard` | Usually none, unless preferences are stored |
| `mod_uckkchallenge` | Submissions, feedback, files, reviews |
| `mod_uckkassembly` | Motions, votes/readings, arguments, minutes contributions |
| `mod_uckkarchive` | Archive items, portfolio items, evidence files |
| `tool_uckkintegrity` | Case notes, evidence, decisions, parties |
| `report_uckk` | No primary data; displays derived data |
| `aiprovider_uckk` | Prompt/response logs if logging enabled |

## 8. Privacy export requirements

User export must include:

```text
profile data
pathway assignments
challenge submissions
assembly contributions
archive items owned by user
portfolio items
integrity case participation where permitted
AI logs related to user where enabled
```

Restricted integrity records may need redaction rules.

## 9. Deletion and retention

Deletion must preserve institutional integrity while respecting privacy requirements.

Recommended behavior:

```text
User-owned private draft → delete
Validated public archive item → anonymize user where required, preserve institutional record when legally allowed
Integrity case involving multiple parties → redact according to role and retention policy
Assembly vote/readings → preserve aggregate decision, handle personal participation according to policy
Challenge submission → delete or anonymize according to validation/publication state
```

## 10. Redaction model

Redaction levels:

```text
none
hide identity
remove private notes
remove files
replace with anonymized placeholder
restrict to integrity reviewers
delete fully
```

## 11. Audit reports

`report_uckk` must support:

```text
archive validation queue
contested archive items
integrity cases by type
integrity cases by state
overdue integrity cases
challenge invalidations
assembly contestations
AI usage in restricted contexts
privacy exports
deletion/redaction actions
```

## 12. Definition of done

```text
[ ] Integrity cases work end-to-end.
[ ] Archive items are versioned.
[ ] Archive items enforce visibility.
[ ] Integrity actions generate events.
[ ] Privacy providers export and delete personal data.
[ ] Public archive items cannot be silently modified.
[ ] Restricted integrity information is not exposed to ordinary users.
[ ] Reports support governance review.
```
