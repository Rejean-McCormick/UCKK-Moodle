# 09 — Integrations, Reporting and Delivery

**Status:** Final delivery specification  
**Purpose:** Define AI integration, reporting, seed execution, testing, release, and acceptance.

## 1. Integration principle

UCKK-Moodle may connect to external systems, but Moodle remains the campus authority for learning records, capabilities, workflows, evidence, archives, and reports.

External systems may assist. They must not silently replace Moodle records.

## 2. AI integration

### 2.1 Component

```text
aiprovider_uckk
```

### 2.2 AI roles

AI can:

```text
summarize material
map a problem
extract themes
identify uncertainties
draft reflections
summarize assembly discussions
prepare comparison views
critique AI output
support accessibility transformations
```

AI cannot:

```text
grade final work
validate integrity
close integrity cases
publish assembly decisions
award badges
certify competencies
erase evidence
replace human review
```

### 2.3 AI actions

```text
summarise_course_material
map_problem
extract_uncertainties
draft_reflection
summarise_assembly
critique_ai_output
prepare_integrity_review
```

### 2.4 AI logging settings

Admin settings:

```text
enable_provider
provider_endpoint
provider_model
log_prompts
log_responses
allow_in_integrity_contexts
allow_in_public_challenges
redact_user_data_before_send
max_tokens
retention_days
```

### 2.5 AI output label

Every AI response must display:

```text
AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.
```

French:

```text
Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.
```

## 3. Reporting

### 3.1 `report_uckk`

Reports must be permission-aware and context-aware.

Report sources:

```text
Joueur progress
Cohort progress
Program progress
Competency matrix
Badge awards
Challenge status
Challenge evidence
Assembly decisions
Archive production
Integrity cases
AI usage
Privacy exports
Seed execution
```

### 3.2 Report filters

Every report should support relevant filters:

```text
user
cohort
program
course
category
date range
status
visibility
competency
badge
challenge type
assembly type
integrity type
```

### 3.3 Export

Export formats:

```text
CSV
XLSX if available
HTML print view
JSON for internal audit if enabled
```

Exports must log:

```text
who exported
when
report type
filter summary
context
```

## 4. Seed execution

### 4.1 `tool_uckkseed` modes

```text
dry_run
apply
repair
report
rollback_seeded_objects
```

### 4.2 Seed input files

```text
presets/categories.json
presets/courses.json
presets/cohorts.json
presets/roles.json
presets/capabilities.json
presets/competencies.json
presets/badges.json
presets/reports.json
presets/navigation.json
```

### 4.3 Idempotency rule

Running the seed twice must not duplicate objects.

Use stable keys:

```text
category idnumber
course shortname
cohort idnumber
competency idnumber
badge name + issuer
role shortname
report shortname
```

### 4.4 Seed log

Each run records:

```text
run id
mode
started by
started at
completed at
created objects
updated objects
skipped objects
errors
warnings
rollback tokens
```

## 5. Installation

Final installation must support:

```text
copy plugin suite into Moodle plugin paths
run Moodle upgrade
enable theme_uckk
configure local_uckk
run tool_uckkseed dry_run
run tool_uckkseed apply
verify reports
verify dashboard
verify privacy checks
run automated tests
```

## 6. Upgrade

Every plugin must include:

```text
version.php
db/upgrade.php when schema changes
upgrade notes
backward-compatible table changes
data migration tests
```

Breaking changes require:

```text
migration plan
rollback plan
administrator notice
data backup recommendation
```

## 7. Testing

### 7.1 PHPUnit

Required unit coverage:

```text
local_uckk services
pathway assignment
provenance service
challenge state transitions
assembly state transitions
archive versioning
integrity case transitions
privacy export/delete
seed idempotency
report query builders
AI request redaction
```

### 7.2 Behat

Required end-to-end workflows:

```text
install seed and view campus
Joueur completes UCKK-000
Joueur submits challenge proof
Mentor evaluates challenge
Inquisiteur opens and closes case
Assembly publishes decision
Decision is contested and archived
Archiviste validates archive item
Badge is awarded after evidence validation
Report is viewed by Gestionnaire
AI summary is generated with non-authority warning
Privacy export includes UCKK records
```

## 8. Release package

Final release contains:

```text
plugins/
presets/
docs/
tests/
release/installation.md
release/upgrade.md
release/rollback.md
release/acceptance-checklist.md
CHANGELOG.md
LICENSE.md
README.md
```

## 9. Acceptance checklist

```text
[ ] Clean Moodle target installs all plugins.
[ ] All plugins show stable maturity metadata.
[ ] Moodle upgrade completes without error.
[ ] UCKK theme applies successfully.
[ ] Seed dry-run produces expected plan.
[ ] Seed apply creates all campus objects.
[ ] UCKK-000 and TC101–TC108 exist and use format_uckk.
[ ] Roles and capabilities are applied.
[ ] Joueur dashboard works.
[ ] Challenge workflow works.
[ ] Assembly workflow works.
[ ] Archive workflow works.
[ ] Integrity workflow works.
[ ] AI provider can be configured and disabled.
[ ] Reports are visible only to authorized users.
[ ] Privacy export/delete works.
[ ] Behat suite passes.
[ ] PHPUnit suite passes.
[ ] No UCKK accreditation confusion appears in public UI.
[ ] UCKK / kOA / kOA Digital Ecosystem / King Klown boundary is preserved.
```

## 10. Final delivery statement

The implementation is accepted only when UCKK-Moodle can be installed from a clean target and used immediately as the final campus for UCKK, with all institutional, academic, symbolic, integrity, archive, AI, reporting, and governance structures present and functional.
