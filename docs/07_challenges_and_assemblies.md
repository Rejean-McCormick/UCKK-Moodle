# 07 — Challenges and Assemblies

**Status:** Final workflow and implementation specification  
**Purpose:** Define Défis King Klown and Assemblées as complete Moodle activity modules, including workflow rules, Moodle file structure, service boundaries, privacy coverage, events, test gates, standalone operation, and optional Konnaxion-connected Smart Vote readings.


## 0. Operating-mode boundary

UCKK-Moodle must support two distinct operating modes:

```text
standalone_core
connected_konnaxion
```

In `standalone_core` mode, Challenges and Assemblies must work without Konnaxion, EkoH, Smart Vote imports, external identity mappings, or external vote-result snapshots.

In `connected_konnaxion` mode, Konnaxion may add Smart Vote readings, EkoH/advisory signals, and external participation context. These additions are optional integration features. They must not become hard requirements for installing, seeding, teaching, challenging, deliberating, archiving, reporting, testing, or using UCKK-Moodle.

Canonical authority rule:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

Smart Vote is `computed_reading_only`. It may inform deliberation, but it must not decide, award, validate, sanction, publish, bypass Moodle permissions, or close contestations.

## 1. Design principle

Challenges and Assemblies are not ordinary forums or assignments. They are UCKK governance and pedagogy objects with rules, evidence, decisions, integrity review, and archive output.

They must be implemented as real Moodle activity modules:

```text
mod_uckkchallenge
mod_uckkassembly
```

They must not be simulated only through pages, templates, JavaScript, presets, or seed data.

Standalone challenge and assembly workflows are core UCKK-Moodle features. Konnaxion-connected Smart Vote support is an optional enhancement layer, not a prerequisite for either module.

## 2. Implementation correction rule

No generated implementation for these modules is acceptable until the following are true:

```text
[ ] Every PHP file starts as PHP and contains PHP only.
[ ] Every AMD source file under amd/src/*.js contains JavaScript only.
[ ] No Markdown fences or documentation notes remain inside PHP source files.
[ ] Every version.php declares the component matching its plugin path.
[ ] Every declared external service has a matching class under classes/external/.
[ ] Every referenced form has a matching class under classes/form/.
[ ] Every referenced event has a matching class under classes/event/.
[ ] Every referenced service has a matching class under classes/local/ or classes/service/.
[ ] Every personal-data store has classes/privacy/provider.php.
[ ] Every public page uses require_login(), context resolution, and require_capability().
[ ] Every write action uses sesskey validation or Moodle external API validation.
[ ] Every workflow state transition emits an event.
[ ] Every critical workflow has PHPUnit and Behat coverage.
```

This document is therefore both a workflow specification and a code-generation contract.

## 3. `mod_uckkchallenge`

### 3.1 Challenge structure

Every challenge contains:

```text
identity
statement
context
rules
corridors of action
evidence requirements
evaluation criteria
ethical constraints
timeline
participants
submissions
review
integrity validation
archive output
badges / competencies
```

### 3.2 Challenge types

```text
internal_learning
public_pedagogical
institutional_audit
system_mapping
prototype
mobilisation
capstone
king_klown_public
```

### 3.3 Challenge state machine

```text
draft
→ published
→ open
→ submitted
→ under_review
→ integrity_review
→ revision_required
→ resubmitted
→ validated
→ archived
→ closed
```

Alternative states:

```text
contested
invalidated
withdrawn
expired
```

### 3.4 Challenge evidence model

Evidence can be:

```text
text submission
file submission
URL
archive item
portfolio item
assembly decision
mentor observation
Inquisiteur note
AI collaboration journal
external artifact
```

Every evidence item must include:

```text
source
author
date
visibility
relation to criteria
provenance
integrity state
```

### 3.5 Challenge evaluation

Evaluation must support:

```text
rubric
mentor feedback
competency rating
badge trigger
integrity review
public summary
private feedback
archive export
```

### 3.6 Challenge anti-abuse constraints

Challenges must not enable:

```text
targeted harassment
humiliation
confusion between fiction and fact
fabricated evidence
coordinated intimidation
doxxing
unbounded public pressure
AI-generated false evidence
```

If a risk is detected, the challenge can be paused by capability:

```text
mod/uckkchallenge:validateintegrity
tool/uckkintegrity:reviewcase
```

### 3.7 Required Moodle files

`mod_uckkchallenge` must deliver:

```text
mod/uckkchallenge/version.php
mod/uckkchallenge/lib.php
mod/uckkchallenge/locallib.php
mod/uckkchallenge/mod_form.php
mod/uckkchallenge/view.php
mod/uckkchallenge/submit.php
mod/uckkchallenge/archive.php
mod/uckkchallenge/integrity.php
mod/uckkchallenge/index.php

mod/uckkchallenge/db/install.xml
mod/uckkchallenge/db/upgrade.php
mod/uckkchallenge/db/access.php
mod/uckkchallenge/db/events.php
mod/uckkchallenge/db/services.php

mod/uckkchallenge/classes/form/submission_form.php
mod/uckkchallenge/classes/form/evaluation_form.php
mod/uckkchallenge/classes/form/revision_form.php

mod/uckkchallenge/classes/local/challenge_service.php
mod/uckkchallenge/classes/local/submission_service.php
mod/uckkchallenge/classes/local/evidence_service.php
mod/uckkchallenge/classes/local/evaluation_service.php
mod/uckkchallenge/classes/local/integrity_service.php
mod/uckkchallenge/classes/local/archive_service.php

mod/uckkchallenge/classes/external/get_challenge_state.php
mod/uckkchallenge/classes/external/get_submission.php
mod/uckkchallenge/classes/external/submit_evidence.php
mod/uckkchallenge/classes/external/submit_challenge.php
mod/uckkchallenge/classes/external/evaluate_submission.php
mod/uckkchallenge/classes/external/request_revision.php
mod/uckkchallenge/classes/external/open_integrity_case.php
mod/uckkchallenge/classes/external/archive_challenge.php

mod/uckkchallenge/classes/event/challenge_created.php
mod/uckkchallenge/classes/event/challenge_published.php
mod/uckkchallenge/classes/event/challenge_opened.php
mod/uckkchallenge/classes/event/submission_created.php
mod/uckkchallenge/classes/event/submission_updated.php
mod/uckkchallenge/classes/event/submission_submitted.php
mod/uckkchallenge/classes/event/submission_evaluated.php
mod/uckkchallenge/classes/event/revision_requested.php
mod/uckkchallenge/classes/event/challenge_validated.php
mod/uckkchallenge/classes/event/challenge_contested.php
mod/uckkchallenge/classes/event/challenge_invalidated.php
mod/uckkchallenge/classes/event/challenge_archived.php

mod/uckkchallenge/classes/output/challenge_view.php
mod/uckkchallenge/classes/output/submission_view.php
mod/uckkchallenge/classes/output/evidence_list.php
mod/uckkchallenge/classes/output/evaluation_panel.php
mod/uckkchallenge/classes/output/integrity_panel.php
mod/uckkchallenge/classes/output/archive_panel.php

mod/uckkchallenge/classes/privacy/provider.php

mod/uckkchallenge/templates/challenge_view.mustache
mod/uckkchallenge/templates/submission_view.mustache
mod/uckkchallenge/templates/evidence_list.mustache
mod/uckkchallenge/templates/evaluation_panel.mustache
mod/uckkchallenge/templates/integrity_panel.mustache
mod/uckkchallenge/templates/archive_panel.mustache

mod/uckkchallenge/amd/src/challenge.js
mod/uckkchallenge/amd/src/submission.js
mod/uckkchallenge/amd/src/evidence.js
mod/uckkchallenge/amd/src/evaluation.js

mod/uckkchallenge/lang/en/uckkchallenge.php
mod/uckkchallenge/lang/fr/uckkchallenge.php

mod/uckkchallenge/tests/challenge_test.php
mod/uckkchallenge/tests/submission_test.php
mod/uckkchallenge/tests/evidence_test.php
mod/uckkchallenge/tests/evaluation_test.php
mod/uckkchallenge/tests/privacy/provider_test.php
mod/uckkchallenge/tests/behat/uckkchallenge.feature
```

### 3.8 Challenge page/controller rule

Challenge PHP page files may only perform:

```text
bootstrap Moodle
read request params
resolve course/module/context
require_login
require_capability
validate sesskey for write actions
call service classes
prepare output renderables
render page
```

Business logic must live in service classes, not in page controllers.

Templates must not query the database.

JavaScript must not contain PHP, Moodle page bootstrap code, direct SQL, or hidden workflow authority.

### 3.9 Challenge capabilities

Minimum capabilities:

```text
mod/uckkchallenge:addinstance
mod/uckkchallenge:view
mod/uckkchallenge:createchallenge
mod/uckkchallenge:submitproof
mod/uckkchallenge:evaluate
mod/uckkchallenge:validateintegrity
mod/uckkchallenge:archive
mod/uckkchallenge:viewallsubmissions
mod/uckkchallenge:manage
```

Every capability must define:

```text
riskbitmask
captype
contextlevel
archetypes
```

### 3.10 Challenge privacy coverage

The challenge module stores personal data:

```text
participants
submissions
evidence text
evidence files
feedback
rubric ratings
revision requests
competency links
badge-trigger records
integrity references
archive references
```

`classes/privacy/provider.php` must support:

```text
get_metadata()
get_contexts_for_userid()
export_user_data()
delete_data_for_all_users_in_context()
delete_data_for_user()
get_users_in_context()
delete_data_for_users()
```

Deletion must respect archive and institutional-memory rules:

```text
private draft submission → delete
unvalidated evidence → delete or anonymize
validated public archive output → preserve record, anonymize where required
integrity-linked evidence → preserve restricted record, redact according to policy
```

## 4. `mod_uckkassembly`

### 4.1 Assembly structure

Every Assembly contains:

```text
assembly type
scope
participants
rules
agenda
motions
arguments
objections
amendments
vote/readings
decision
minority report
contestability window
minutes
archive output
```

### 4.2 Assembly types

```text
savoirs
defis
joueurs
batisseurs
inquisiteurs
grand_jeu
```

### 4.3 Assembly state machine

```text
planned
→ open
→ motions_open
→ deliberation
→ voting_or_reading
→ decision_draft
→ decision_published
→ contestability_window
→ archived
→ closed
```

Alternative states:

```text
paused_for_integrity
contested
reopened
invalidated
```

### 4.4 Motion state machine

```text
submitted
→ accepted_for_deliberation
→ amended
→ ready_for_decision
→ decided
→ archived
```

Alternative states:

```text
rejected
withdrawn
merged
contested
```

### 4.5 Decision model

A decision must include:

```text
motion reference
decision text
decision method
participants
result
reasoning
evidence used
unresolved objections
minority report if applicable
appeal/contestation path
archive link
```

### 4.6 Required Moodle files

`mod_uckkassembly` must deliver:

```text
mod/uckkassembly/version.php
mod/uckkassembly/lib.php
mod/uckkassembly/locallib.php
mod/uckkassembly/mod_form.php
mod/uckkassembly/view.php
mod/uckkassembly/propose.php
mod/uckkassembly/vote.php
mod/uckkassembly/decision.php
mod/uckkassembly/minutes.php
mod/uckkassembly/contest.php
mod/uckkassembly/index.php

mod/uckkassembly/db/install.xml
mod/uckkassembly/db/upgrade.php
mod/uckkassembly/db/access.php
mod/uckkassembly/db/events.php
mod/uckkassembly/db/services.php

mod/uckkassembly/classes/form/motion_form.php
mod/uckkassembly/classes/form/amendment_form.php
mod/uckkassembly/classes/form/objection_form.php
mod/uckkassembly/classes/form/vote_form.php
mod/uckkassembly/classes/form/minutes_form.php
mod/uckkassembly/classes/form/contest_form.php

mod/uckkassembly/classes/local/assembly_service.php
mod/uckkassembly/classes/local/motion_service.php
mod/uckkassembly/classes/local/amendment_service.php
mod/uckkassembly/classes/local/objection_service.php
mod/uckkassembly/classes/local/vote_service.php
mod/uckkassembly/classes/local/decision_service.php
mod/uckkassembly/classes/local/minutes_service.php
mod/uckkassembly/classes/local/integrity_service.php
mod/uckkassembly/classes/local/archive_service.php

mod/uckkassembly/classes/external/get_assembly_state.php
mod/uckkassembly/classes/external/get_motion_list.php
mod/uckkassembly/classes/external/get_motion.php
mod/uckkassembly/classes/external/submit_motion.php
mod/uckkassembly/classes/external/submit_amendment.php
mod/uckkassembly/classes/external/submit_objection.php
mod/uckkassembly/classes/external/submit_vote.php
mod/uckkassembly/classes/external/get_vote_results.php
mod/uckkassembly/classes/external/get_decision.php
mod/uckkassembly/classes/external/publish_decision.php
mod/uckkassembly/classes/external/contest_decision.php
mod/uckkassembly/classes/external/get_minutes_panel.php
mod/uckkassembly/classes/external/save_minutes.php
mod/uckkassembly/classes/external/publish_minutes.php
mod/uckkassembly/classes/external/open_integrity_case.php
mod/uckkassembly/classes/external/archive_assembly.php

mod/uckkassembly/classes/event/assembly_created.php
mod/uckkassembly/classes/event/assembly_opened.php
mod/uckkassembly/classes/event/motion_submitted.php
mod/uckkassembly/classes/event/motion_amended.php
mod/uckkassembly/classes/event/objection_submitted.php
mod/uckkassembly/classes/event/vote_submitted.php
mod/uckkassembly/classes/event/decision_drafted.php
mod/uckkassembly/classes/event/decision_published.php
mod/uckkassembly/classes/event/decision_contested.php
mod/uckkassembly/classes/event/minutes_published.php
mod/uckkassembly/classes/event/assembly_archived.php
mod/uckkassembly/classes/event/assembly_closed.php

mod/uckkassembly/classes/output/assembly_view.php
mod/uckkassembly/classes/output/agenda_panel.php
mod/uckkassembly/classes/output/motion_list.php
mod/uckkassembly/classes/output/motion_view.php
mod/uckkassembly/classes/output/vote_panel.php
mod/uckkassembly/classes/output/decision_panel.php
mod/uckkassembly/classes/output/minutes_panel.php
mod/uckkassembly/classes/output/integrity_panel.php
mod/uckkassembly/classes/output/archive_panel.php

mod/uckkassembly/classes/privacy/provider.php

mod/uckkassembly/templates/assembly_view.mustache
mod/uckkassembly/templates/agenda_panel.mustache
mod/uckkassembly/templates/motion_list.mustache
mod/uckkassembly/templates/motion_view.mustache
mod/uckkassembly/templates/vote_panel.mustache
mod/uckkassembly/templates/decision_panel.mustache
mod/uckkassembly/templates/minutes_panel.mustache
mod/uckkassembly/templates/integrity_panel.mustache
mod/uckkassembly/templates/archive_panel.mustache

mod/uckkassembly/amd/src/assembly.js
mod/uckkassembly/amd/src/motion.js
mod/uckkassembly/amd/src/vote.js
mod/uckkassembly/amd/src/minutes.js

mod/uckkassembly/lang/en/uckkassembly.php
mod/uckkassembly/lang/fr/uckkassembly.php

mod/uckkassembly/tests/assembly_test.php
mod/uckkassembly/tests/motion_test.php
mod/uckkassembly/tests/vote_test.php
mod/uckkassembly/tests/decision_test.php
mod/uckkassembly/tests/minutes_test.php
mod/uckkassembly/tests/privacy/provider_test.php
mod/uckkassembly/tests/behat/uckkassembly.feature
```

### 4.7 Assembly page/controller rule

Assembly PHP page files may only perform:

```text
bootstrap Moodle
read request params
resolve course/module/context
require_login
require_capability
validate sesskey for write actions
call service classes
prepare output renderables
render page
```

Business logic must live in service classes.

Templates must not contain business logic.

AMD JavaScript must only provide interaction, progressive enhancement, form-panel behavior, refresh behavior, and client-side convenience.

No final decision, vote result, integrity action, or archive action may be decided only in JavaScript.

### 4.8 Assembly capabilities

Minimum capabilities:

```text
mod/uckkassembly:addinstance
mod/uckkassembly:view
mod/uckkassembly:submitmotion
mod/uckkassembly:submitamendment
mod/uckkassembly:submitobjection
mod/uckkassembly:vote
mod/uckkassembly:publishdecision
mod/uckkassembly:contestdecision
mod/uckkassembly:manageminutes
mod/uckkassembly:validateintegrity
mod/uckkassembly:archive
mod/uckkassembly:manage
```

Every capability must define:

```text
riskbitmask
captype
contextlevel
archetypes
```

### 4.9 Assembly privacy coverage

The assembly module stores personal data:

```text
participants
motions
arguments
amendments
objections
votes/readings where user-linked
minutes contributions
minority reports
contestations
integrity references
archive references
```

`classes/privacy/provider.php` must support:

```text
get_metadata()
get_contexts_for_userid()
export_user_data()
delete_data_for_all_users_in_context()
delete_data_for_user()
get_users_in_context()
delete_data_for_users()
```

Deletion must preserve institutional decision integrity:

```text
private draft motion → delete
unpublished objection → delete or anonymize
published decision → preserve decision, anonymize personal attribution where required
minutes → preserve record, redact personal information where required
integrity-linked contestation → preserve restricted record, redact according to policy
```

## 5. Readings, voting, and optional Smart Vote

Do not hide authority in a single opaque algorithm.

The assembly module must support ordinary Moodle-side voting/readings in standalone mode. It may also display Konnaxion Smart Vote readings in connected mode.

### 5.1 Standalone readings

In standalone mode, the assembly module can show multiple Moodle-side readings side by side:

```text
raw count
participant count by role
mentor reading
competency-informed reading
minority objection reading
integrity warning
```

Standalone readings must be reproducible, documented, permission-checked, auditable, contestable, exportable to archive, and covered by PHPUnit.

Every reading must be labeled as a reading, not an automatic truth.

### 5.2 Optional Konnaxion-connected Smart Vote

Smart Vote is available only when Konnaxion-connected mode is enabled and configured.

When Konnaxion is disabled, unavailable, or not configured:

```text
Smart Vote panels are hidden or disabled.
Smart Vote actions are unavailable.
Smart Vote imports are skipped safely.
Assembly motions, objections, amendments, readings, decisions, minutes, contestations, and archive exports still work.
Absence of a Smart Vote reading is valid provenance, not a missing record.
```

When Konnaxion-connected mode is enabled, Smart Vote may provide:

```text
external vote target mapping
external voting modality
weighted reading result
expertise or EkoH advisory signal
minority or anomaly signal
source version or provenance hash
```

### 5.3 Smart Vote implementation rule

Smart Vote is not sovereign.

The stored decision must distinguish:

```text
raw data
reading method
computed reading
human/institutional decision
minority report
integrity warning
```

The system may calculate or import readings, but it must not silently convert a reading into final authority.

Every Smart Vote calculation or imported result must be:

```text
reproducible
documented
permission-checked
auditable
contestable
exportable to archive
covered by PHPUnit
```

### 5.4 Authority and publication boundary

Smart Vote may inform a deliberation, but it must not:

```text
publish the final Assembly decision
award recognition
validate evidence
close a contestation
override capability checks
replace Assembly reasoning
erase minority reports
hide integrity warnings
```

Final Assembly decisions remain Moodle-side human/institutional records controlled by `mod_uckkassembly`, Moodle capabilities, documented state transitions, minutes, contestability, and archive provenance.

### 5.5 Archive boundary for readings

When a Smart Vote reading is used, the archive export must preserve the reading separately from the Assembly decision:

```text
Smart Vote reading snapshot
source/provenance reference
reading method
computed reading
review or contestation state
human/institutional decision
minority report where applicable
integrity warning where applicable
```

A standalone Assembly archive may contain no Smart Vote snapshot. That is valid when the Assembly did not use Konnaxion-connected mode.

## 6. Integration between challenges and assemblies

A challenge may generate an assembly.

Examples:

```text
Challenge submission requires Assembly of Savoirs review.
Public challenge requires Assembly of Defis validation.
Contested challenge opens Assembly of Inquisiteurs.
Capstone challenge produces Assembly of Grand Jeu presentation.
```

An assembly may generate a challenge.

Examples:

```text
Assembly decision creates a reform challenge.
Assembly identifies a system to map.
Assembly assigns a prototype task to Bâtisseurs.
```

### 6.1 Cross-module dependency rule

The modules may reference each other through IDs and service calls, but source data must remain owned by the correct module.

```text
Challenge submissions belong to mod_uckkchallenge.
Assembly motions and decisions belong to mod_uckkassembly.
Archive item content belongs to mod_uckkarchive.
Integrity case records belong to tool_uckkintegrity.
Shared registry data belongs to local_uckk.
```

Cross-module references must include:

```text
source component
source table
source id
context id
visibility
provenance
integrity state
archive state
```

## 7. Archive integration

Every completed challenge can create archive items:

```text
challenge statement
winning / validated submissions
evidence package
mentor evaluation
integrity summary
public summary
lessons learned
```

Every completed assembly creates archive items:

```text
agenda
motions
decision
minutes
minority reports
evidence
contestations
final archive summary
```

### 7.1 Archive creation rule

Challenges and Assemblies do not directly bypass `mod_uckkarchive`.

Archive creation must go through an archive service boundary:

```text
mod_uckkchallenge → challenge archive service → mod_uckkarchive API/service
mod_uckkassembly → assembly archive service → mod_uckkarchive API/service
```

The archive payload must include:

```text
origin component
origin object id
course module id
context id
author
created time
visibility
summary
evidence links
integrity status
provenance
version seed
```

## 8. Integrity integration

Challenges and Assemblies can open or link integrity cases.

Integrity review must be routed through:

```text
tool_uckkintegrity
```

Challenge and Assembly modules may request:

```text
open integrity case
link existing integrity case
pause validation
pause publication
record correction received
record invalidation
record contestation
```

They must not grant the Inquisiteur unrestricted administrative power.

Integrity actions must be logged and evented.

## 9. Notifications

Required notifications:

```text
challenge opened
challenge closing soon
submission received
review requested
revision required
challenge validated
challenge contested
assembly opened
motion submitted
decision published
contestability window ending
integrity case opened
archive item created
```

Notification handlers must respect:

```text
visibility
role
context
capability
privacy restrictions
integrity restrictions
```

## 10. Reports

`report_uckk` must show:

```text
open challenges
submissions by status
validation rate
integrity case rate
archive output
assembly attendance
motion counts
decision counts
contestation counts
unresolved objections
```

Reports must never expose private challenge evidence, restricted integrity records, or unpublished assembly material to ordinary users.

All report queries must be capability-filtered.

## 11. Events

Challenge events must cover:

```text
challenge_created
challenge_published
challenge_opened
submission_created
submission_updated
submission_submitted
submission_evaluated
revision_requested
challenge_validated
challenge_contested
challenge_invalidated
challenge_archived
```

Assembly events must cover:

```text
assembly_created
assembly_opened
motion_submitted
motion_amended
objection_submitted
vote_submitted
decision_drafted
decision_published
decision_contested
minutes_published
assembly_archived
assembly_closed
```

Every event must define:

```text
object table
object id
context
related user where applicable
other data where applicable
crud
edulevel
description
```

## 12. External services

External services must only exist when needed by AMD JavaScript, mobile access, AJAX behavior, or controlled integrations.

Konnaxion-connected Smart Vote services are optional connected-mode services. They must not be required for standalone Challenge or Assembly operation. If the Konnaxion bridge is disabled, connected-mode services must fail closed without blocking ordinary Assembly workflows.

Every external service must include:

```text
db/services.php declaration
classes/external/{service_name}.php implementation
execute_parameters()
execute()
execute_returns()
validate_context()
capability checks
sesskey or token-aware behavior where applicable
privacy-aware output filtering
PHPUnit coverage
```

External services must not expose raw database records without permission filtering and formatting.

## 13. Testing and acceptance gates

### 13.1 Static preflight

Before installing in Moodle:

```text
[ ] php -l passes for every PHP file.
[ ] No PHP token appears in amd/src/*.js.
[ ] No Markdown fence appears in *.php.
[ ] Every version.php component matches the plugin path.
[ ] install.xml files parse.
[ ] db/services.php classes exist.
[ ] db/tasks.php classes exist.
[ ] db/events.php observers reference existing classes/functions.
[ ] All language strings referenced by pages/templates exist in English and French.
```

### 13.2 Moodle install gate

The modules pass only when:

```text
[ ] Both modules install in a clean Moodle target.
[ ] Both modules appear in the activity chooser.
[ ] admin/cli/upgrade.php completes without warnings.
[ ] Moodle caches can be purged without error.
[ ] Activity instances can be created, edited, viewed, backed up, and restored.
```

### 13.3 PHPUnit coverage

Challenge PHPUnit must cover:

```text
state transitions
submission creation
evidence validation
evaluation rules
integrity pause
archive export payload
privacy export
privacy deletion/anonymization
external service parameters and returns
capability denial paths
```

Assembly PHPUnit must cover:

```text
state transitions
motion creation
amendments
objections
vote/readings
standalone Assembly flow with Smart Vote disabled
Smart Vote panel/action hidden or disabled when Konnaxion is disabled
optional Smart Vote reading import when Konnaxion-connected mode is enabled
Smart Vote advisory-only decision protection
decision publication
contestability window
minutes publication
archive export payload
privacy export
privacy deletion/anonymization
external service parameters and returns
capability denial paths
```

### 13.4 Behat coverage

Challenge Behat scenarios must cover:

```text
teacher creates challenge
student submits evidence
mentor evaluates submission
revision is requested
student resubmits
integrity case pauses validation
challenge is validated
challenge is archived
ordinary user cannot access restricted evidence
```

Assembly Behat scenarios must cover:

```text
teacher creates assembly
participant submits motion
participant submits objection
participant votes or contributes reading
standalone Assembly reaches a decision with Smart Vote disabled
Smart Vote controls are hidden or disabled when Konnaxion is disabled
Facilitateur opens a Smart Vote reading only in Konnaxion-connected mode
Konnaxion Smart Vote result is imported as a reading only
Smart Vote result does not publish Assembly decision automatically
decision is drafted
decision is published
decision is contested
minutes are published
assembly is archived
ordinary user cannot access restricted decision material
```

### 13.5 JavaScript gate

AMD JavaScript passes only when:

```text
[ ] All amd/src/*.js files are valid JavaScript.
[ ] No PHP code appears in JavaScript files.
[ ] Grunt AMD build succeeds.
[ ] JavaScript calls only declared external services.
[ ] JavaScript failure does not break core page workflows.
[ ] Server-side permission checks remain authoritative.
```

## 14. Definition of done

```text
[ ] Challenge activity can be created by authorized users.
[ ] Challenge accepts evidence with files and text.
[ ] Challenge supports team and individual submissions.
[ ] Challenge supports evaluation, integrity review, archive export.
[ ] Challenge emits events for every major state transition.
[ ] Challenge implements privacy provider export and deletion behavior.
[ ] Challenge has PHPUnit and Behat coverage.

[ ] Assembly activity can be created by authorized users.
[ ] Assembly supports motions, objections, amendments, votes/readings, decisions, and minutes.
[ ] Assembly works in standalone mode without Konnaxion or Smart Vote enabled.
[ ] Smart Vote panels/actions are hidden or disabled when Konnaxion-connected mode is disabled.
[ ] In Konnaxion-connected mode, Smart Vote readings are displayed as readings only, not final decisions.
[ ] Assembly decisions can be contested and archived.
[ ] Assembly emits events for every major state transition.
[ ] Assembly implements privacy provider export and deletion behavior.
[ ] Assembly has PHPUnit and Behat coverage.

[ ] Both modules install cleanly.
[ ] Both modules pass PHP lint.
[ ] Both modules pass Moodle upgrade.
[ ] Both modules pass AMD build.
[ ] Both modules pass privacy checks.
[ ] Both modules keep business logic out of templates and JavaScript.
[ ] Both modules preserve provenance and archive traceability.
[ ] Both modules remain contestable and auditable.
[ ] Optional Konnaxion-connected features fail closed without breaking standalone workflows.
[ ] Smart Vote never replaces Moodle permissions, Assembly decisions, minutes, archive provenance, or integrity review.
```