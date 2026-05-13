# 03 — Plugin Specifications

**Status:** Final plugin contract  
**Purpose:** Define what each UCKK-Moodle plugin must deliver.

## 1. `theme_uckk`

### Purpose

Provide visual identity, layouts, and UI language for UCKK.

### Must deliver

```text
theme/uckk/version.php
theme/uckk/config.php
theme/uckk/settings.php
theme/uckk/lang/en/theme_uckk.php
theme/uckk/lang/fr/theme_uckk.php
theme/uckk/scss/
theme/uckk/templates/
theme/uckk/layout/
theme/uckk/pix/
theme/uckk/classes/output/
```

### Features

- UCKK frontpage layout.
- Dashboard-friendly layout.
- Course, challenge, assembly, archive visual variants.
- King Klown visual layer without confusing symbolic and institutional authority.
- Accessibility-compliant contrast and navigation.
- French-first and English-ready UI.

### Must not do

- No grading logic.
- No permission logic.
- No workflow logic.
- No data ownership.

## 2. `format_uckk`

### Purpose

Define the standard UCKK course structure.

### Default sections

```text
0. Orientation
1. Concepts
2. Matière canonique
3. Atelier
4. Preuves
5. Délibération
6. Livrable
7. Évaluation
8. Archive
```

### Must deliver

```text
course/format/uckk/version.php
course/format/uckk/lib.php
course/format/uckk/classes/
course/format/uckk/classes/output/
course/format/uckk/templates/
course/format/uckk/amd/src/
course/format/uckk/lang/en/format_uckk.php
course/format/uckk/lang/fr/format_uckk.php
course/format/uckk/db/access.php
```

### Features

- UCKK section map.
- Section metadata.
- Evidence indicators.
- Archive indicators.
- Integrity warning indicators.
- Course index support.
- Completion summary.
- Compatibility with Moodle course editing.

## 3. `local_uckk`

### Purpose

Own the core institutional registry and shared services.

### Must deliver

```text
local/uckk/version.php
local/uckk/settings.php
local/uckk/db/install.xml
local/uckk/db/upgrade.php
local/uckk/db/access.php
local/uckk/db/services.php
local/uckk/db/events.php
local/uckk/classes/service/
local/uckk/classes/external/
local/uckk/classes/privacy/provider.php
local/uckk/classes/output/
local/uckk/templates/
local/uckk/lang/en/local_uckk.php
local/uckk/lang/fr/local_uckk.php
```

### Owns

- Programs.
- Pathways.
- Symbolic roles.
- Joueur profiles.
- UCKK campus settings.
- Shared provenance records.
- Shared visibility rules.
- Navigation registry.

### Does not own

- Challenge submissions.
- Assembly motions.
- Archive item content.
- Integrity case records.

It can reference these objects but must not duplicate their source data.

## 4. `block_uckk_dashboard`

### Purpose

Display the Joueur and staff cockpit.

### Must deliver

```text
blocks/uckk_dashboard/version.php
blocks/uckk_dashboard/block_uckk_dashboard.php
blocks/uckk_dashboard/classes/output/
blocks/uckk_dashboard/templates/
blocks/uckk_dashboard/amd/src/
blocks/uckk_dashboard/lang/en/block_uckk_dashboard.php
blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
blocks/uckk_dashboard/db/access.php
```

### Dashboard cards

```text
My pathway
Tronc commun
My competencies
My badges
My challenges
My assemblies
My archive
My integrity feedback
My deadlines
My portfolio
```

### Role-specific variants

| Viewer | Dashboard emphasis |
|---|---|
| Joueur | Progress, evidence, tasks |
| Mentor | Submissions, evaluation, cohorts |
| Archiviste | Items awaiting validation |
| Inquisiteur | Open integrity cases |
| Gestionnaire | Campus reports and configuration |

## 5. `mod_uckkchallenge`

### Purpose

Dedicated activity module for Défis King Klown.

### Must deliver

```text
mod/uckkchallenge/version.php
mod/uckkchallenge/lib.php
mod/uckkchallenge/mod_form.php
mod/uckkchallenge/view.php
mod/uckkchallenge/db/install.xml
mod/uckkchallenge/db/upgrade.php
mod/uckkchallenge/db/access.php
mod/uckkchallenge/classes/
mod/uckkchallenge/classes/privacy/provider.php
mod/uckkchallenge/classes/output/
mod/uckkchallenge/templates/
mod/uckkchallenge/amd/src/
mod/uckkchallenge/lang/en/uckkchallenge.php
mod/uckkchallenge/lang/fr/uckkchallenge.php
```

### Workflow states

```text
draft
published
open
submitted
under_review
integrity_review
revision_required
validated
archived
contested
invalidated
closed
```

### Core features

- Challenge statement.
- Rules.
- Corridors of action.
- Evidence requirements.
- Individual or team submissions.
- Mentor evaluation.
- Integrity review.
- Archive export.
- Badge and competency links.

## 6. `mod_uckkassembly`

### Purpose

Dedicated activity module for Assemblées.

### Must deliver

```text
mod/uckkassembly/version.php
mod/uckkassembly/lib.php
mod/uckkassembly/mod_form.php
mod/uckkassembly/view.php
mod/uckkassembly/db/install.xml
mod/uckkassembly/db/upgrade.php
mod/uckkassembly/db/access.php
mod/uckkassembly/classes/
mod/uckkassembly/classes/privacy/provider.php
mod/uckkassembly/classes/output/
mod/uckkassembly/templates/
mod/uckkassembly/amd/src/
mod/uckkassembly/lang/en/uckkassembly.php
mod/uckkassembly/lang/fr/uckkassembly.php
```

### Assembly types

```text
savoirs
defis
joueurs
batisseurs
inquisiteurs
grand_jeu
```

### Core features

- Motions.
- Structured arguments.
- Objections.
- Amendments.
- Vote/readings.
- Decision publication.
- Minority report.
- Contestation.
- Minutes.
- Archive export.

## 7. `mod_uckkarchive`

### Purpose

Dedicated activity module for Archives, evidence, decisions, and Kristals.

### Core item types

```text
proof
decision
course_work
challenge_result
assembly_minutes
integrity_case_summary
kristal
reflection
portfolio_item
version_record
```

### Required file areas

```text
proof_files
decision_attachments
minutes_files
kristal_files
portfolio_files
integrity_exports
```

## 8. `tool_uckkseed`

### Purpose

Install the full UCKK campus.

### Required behavior

- Idempotent seeding.
- Dry-run mode.
- Report mode.
- Rollback plan for seed-created objects.
- JSON/YAML seed input.
- Capability-safe execution.
- Audit log.

### Creates

```text
categories
courses
cohorts
groups
roles
capability assignments
competency frameworks
badges
course templates
activity instances
reports
dashboard blocks
navigation
```

## 9. `tool_uckkintegrity`

### Purpose

Implement Inquisiteur workflows.

### Core case types

```text
proof_quality
fiction_fact_confusion
harassment_or_humiliation
authority_capture
ai_misuse
assessment_dispute
challenge_dispute
assembly_dispute
archive_correction
```

### Case states

```text
opened
triaged
under_review
waiting_for_response
correction_required
resolved
dismissed
escalated
archived
```

## 10. `report_uckk`

### Purpose

Provide institutional reporting.

### Report families

```text
joueur_progress
cohort_progress
program_progress
competency_matrix
badge_awards
challenge_status
assembly_decisions
archive_production
integrity_cases
ai_usage
privacy_exports
```

## 11. `aiprovider_uckk`

### Purpose

Bridge UCKK-Moodle to governed AI services.

### Actions

```text
summarise_course_material
map_problem
extract_uncertainties
draft_reflection
summarise_assembly
critique_ai_output
prepare_integrity_review
```

### Constraints

- Every AI output is labeled as non-authoritative.
- Prompts and responses can be logged according to site settings.
- Sensitive workflows can disable AI.
- AI cannot grade, sanction, validate integrity, or publish final decisions.

## 12. Plugin completion checklist

Every plugin must pass:

```text
[ ] Installs cleanly.
[ ] Upgrades cleanly.
[ ] Has component strings in English and French.
[ ] Has required capabilities.
[ ] Uses Moodle context checks.
[ ] Emits events for important actions.
[ ] Has privacy provider when storing personal data.
[ ] Has PHPUnit tests for services.
[ ] Has Behat tests for major user workflows.
[ ] Has README with purpose, install path, dependencies, and admin settings.
```
