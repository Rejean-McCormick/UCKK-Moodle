# 07 — Challenges and Assemblies

**Status:** Final workflow specification  
**Purpose:** Define Défis King Klown and Assemblées as complete Moodle activity modules.

## 1. Design principle

Challenges and Assemblies are not ordinary forums or assignments. They are UCKK governance and pedagogy objects with rules, evidence, decisions, integrity review, and archive output.

## 2. `mod_uckkchallenge`

### 2.1 Challenge structure

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

### 2.2 Challenge types

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

### 2.3 Challenge state machine

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

### 2.4 Challenge evidence model

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

### 2.5 Challenge evaluation

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

### 2.6 Challenge anti-abuse constraints

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

## 3. `mod_uckkassembly`

### 3.1 Assembly structure

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

### 3.2 Assembly types

```text
savoirs
defis
joueurs
batisseurs
inquisiteurs
grand_jeu
```

### 3.3 Assembly state machine

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

### 3.4 Motion state machine

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

### 3.5 Decision model

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

## 4. Smart Vote / multiple readings

Do not hide authority in a single opaque algorithm.

The assembly module can show multiple readings side by side:

```text
raw count
participant count by role
mentor reading
competency-informed reading
minority objection reading
integrity warning
```

Every reading must be labeled as a reading, not an automatic truth.

## 5. Integration between challenges and assemblies

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

## 6. Archive integration

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

## 7. Notifications

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

## 8. Reports

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

## 9. Definition of done

```text
[ ] Challenge activity can be created by authorized users.
[ ] Challenge accepts evidence with files and text.
[ ] Challenge supports team and individual submissions.
[ ] Challenge supports evaluation, integrity review, archive export.
[ ] Assembly activity supports motions, objections, votes/readings, decisions.
[ ] Assembly decisions can be contested and archived.
[ ] Both modules emit events.
[ ] Both modules implement privacy providers.
[ ] Both modules have Behat workflows for primary states.
```
