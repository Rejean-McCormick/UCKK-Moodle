# UCKK Seed JSON Preset Reference

**Path:** `admin/tool/uckkseed/docs/json_preset_reference.md`  
**Component:** `tool_uckkseed`  
**Status:** final target reference for the UCKK Moodle distribution  
**Preset schema:** `uckkseed.preset.v1`  
**Preset version:** `2026051200`  
**Registry folder:** `academic_registry_json`

This document is the central reference for every seedable JSON preset file in `academic_registry_json`.
It is normative for JSON shape, idempotency keys, ownership, handler routing, and cross-preset references.

The standard is aligned first with Moodle executable code, Moodle APIs, plugin storage, and `db/access.php` capability declarations. It is then aligned with UCKK-Moodle documentation. If a documentation page contradicts this file or the executing Moodle handlers, update the documentation or explicitly change the handler and then update this reference.

---

## 1. Authority order

When deciding whether a JSON field is valid, use this precedence:

1. Moodle executable code and storage:
   - `admin/tool/uckkseed/classes/local/seeder.php`
   - `admin/tool/uckkseed/classes/local/*_seed.php`
   - plugin `db/install.xml`, `db/access.php`, and Moodle core APIs
2. This reference file.
3. Current `academic_registry_json/*.json` files.
4. UCKK-Moodle documentation under `docs/`.

The documentation may be corrected to match the final seed contract. The seed contract must not depend on Moodle core changes.

---

## 2. Common preset envelope

Every seedable JSON file **must** use this top-level runtime envelope:

```json
{
  "schema": "uckkseed.preset.v1",
  "component": "tool_uckkseed",
  "preset": "<preset_id>",
  "version": 2026051200,
  "items": []
}
````

Optional top-level `metadata` is allowed:

```json
{
  "metadata": {
    "source_family": "UCKK canon",
    "generated_at": "2026-05-19T00:00:00-04:00",
    "notes": []
  }
}
```

The following envelope is **not** valid as the active runtime seed envelope:

```json
{
  "schema_version": "1.0",
  "preset_type": "courses",
  "source_family": "UCKK canon",
  "items": []
}
```

Those fields may be preserved only inside top-level `metadata`.

---

## 3. Presets governed by this reference

```text
academic_registry_json/categories.json
academic_registry_json/programs.json
academic_registry_json/pathways.json
academic_registry_json/cohorts.json
academic_registry_json/roles.json
academic_registry_json/capabilities.json
academic_registry_json/competencies.json
academic_registry_json/badges.json
academic_registry_json/course_templates.json
academic_registry_json/challenge_templates.json
academic_registry_json/assembly_templates.json
academic_registry_json/archive_templates.json
academic_registry_json/courses.json
academic_registry_json/reports.json
```

The current snapshot contains these 14 files. Final `tool_uckkseed` must treat all 14 as part of the governed registry.

---

## 4. Final handler routing

Final `tool_uckkseed` must support these presets and handlers:

| Preset                | JSON file                  | Final handler             | Owner / target                              |
| --------------------- | -------------------------- | ------------------------- | ------------------------------------------- |
| `categories`          | `categories.json`          | `category_seed`           | Moodle course categories                    |
| `programs`            | `programs.json`            | `program_seed`            | `local_uckk_program`                        |
| `cohorts`             | `cohorts.json`             | `cohort_seed`             | Moodle cohorts                              |
| `roles`               | `roles.json`               | `role_seed`               | Moodle roles and embedded role capabilities |
| `capabilities`        | `capabilities.json`        | `capability_seed`         | Moodle role capability assignments          |
| `competencies`        | `competencies.json`        | `competency_seed`         | Moodle core competency framework            |
| `course_templates`    | `course_templates.json`    | `course_template_seed`    | seed-managed course templates               |
| `challenge_templates` | `challenge_templates.json` | `challenge_template_seed` | `mod_uckkchallenge` template definitions    |
| `assembly_templates`  | `assembly_templates.json`  | `assembly_template_seed`  | `mod_uckkassembly` template definitions     |
| `archive_templates`   | `archive_templates.json`   | `archive_template_seed`   | `mod_uckkarchive` template definitions      |
| `courses`             | `courses.json`             | `course_seed`             | Moodle courses                              |
| `badges`              | `badges.json`              | `badge_seed`              | Moodle badges                               |
| `pathways`            | `pathways.json`            | `pathway_seed`            | `local_uckk_pathway`                        |
| `reports`             | `reports.json`             | `report_seed`             | `report_uckk` seeded report definitions     |

### Final default validation/seed order

The default order must satisfy hard database dependencies first and allow soft cross-preset references to resolve by stable JSON keys during validation.

```text
categories
programs
cohorts
roles
capabilities
competencies
course_templates
challenge_templates
assembly_templates
archive_templates
courses
badges
pathways
reports
```

### Soft-reference rule

Some preset families refer to each other conceptually before every Moodle object exists. These references are allowed as stable JSON references during validation and dry run:

```text
badges may reference programs, pathways, courses, and competencies by stable JSON key/id/idnumber.
pathways may reference courses, competencies, and badges by stable JSON key/id/idnumber.
```

Validation may warn when a referenced object does not yet exist in Moodle storage, but it must not fail only because a soft reference is resolved from another preset file rather than from the database. Apply mode must be idempotent and safe to rerun.

### Current implementation gap

`seeder.php` and the individual `*_seed.php` handlers are the authoritative runtime routing layer. All entry points must expose the same governed preset registry:

```text
cli/seed.php
cli/reset.php
cli/export_preset.php
classes/form/seed_form.php
classes/form/reset_form.php
```

If any entry point omits `programs` or `pathways`, that entry point is incomplete even if direct `validate.php --preset=programs` or `validate.php --preset=pathways` works.

---

## 5. Common item rules

### 5.1 Stable `key`

Every item must have a stable `key` unless the handler explicitly uses a different required key.

```json
{
  "key": "uckk_tc101"
}
```

Rules:

* lowercase snake case or compact stable identifier preferred
* no runtime database ids
* no translated label as the only identifier
* may be used in metadata, config markers, rollback plans, and audit logs

### 5.2 Stable canonical `id`

Every item should have a semantic `id` when the source JSON already carries one.

Examples:

```text
program:tronc-commun
pathway:tronc-commun-main
course:uckk-tc101
badge:tronc-commun-completion
competency:tronc-commun:synthesis
```

Canonical ids must be stored in `metadata` when the Moodle target table has no dedicated column.

### 5.3 Moodle reconciliation identifiers

Handlers should prefer this reconciliation order:

1. Moodle/native idnumber or shortname when available.
2. Seed-managed metadata marker.
3. Config marker owned by `tool_uckkseed`.
4. Name/title only as last resort and only when safe.

No handler may depend on transient numeric ids from JSON.

### 5.4 Status and visibility

JSON presets may contain domain statuses. Handlers must either:

* accept the value directly;
* map it to a storage-safe value; or
* validate and reject it with an explicit message.

Handlers must not silently discard status/visibility fields.

### 5.5 Metadata

`metadata` is allowed for every item. It must remain JSON-serializable and deterministic.

### 5.6 AI metadata

AI metadata is allowed only as provenance or drafting context.

Allowed:

```json
{
  "metadata": {
    "ai_assisted": true,
    "review_state": "human_reviewed",
    "source_prompt": "..."
  }
}
```

Not allowed:

* automatic grading authority
* automatic badge award authority
* automatic competency certification authority
* automatic integrity closure authority
* automatic archive validation authority

AI metadata is non-authoritative unless a human-controlled handler explicitly uses it.

---

# 6. Preset contracts

---

## 6.1 `categories.json`

**Preset:** `categories`
**Handler:** `category_seed`
**Moodle owner:** course categories
**Idempotency key:** `idnumber`

### Required item fields

```json
{
  "key": "UCKK-TC",
  "idnumber": "UCKK-TC",
  "name": "Tronc commun obligatoire",
  "parent": "",
  "visible": 1,
  "sortorder": 10,
  "description": "...",
  "metadata": {}
}
```

### Accepted aliases

Handlers should accept:

| Canonical  | Accepted aliases                           |
| ---------- | ------------------------------------------ |
| `idnumber` | `key`, `category_idnumber`                 |
| `name`     | `title`, `fullname`                        |
| `parent`   | `parent_idnumber`, `parent_category`       |
| `visible`  | `visibility` when numeric/boolean mappable |

### Required category idnumbers for the current course set

The active `courses.json` and `programs.json` reference these category idnumbers:

```text
UCKK-TC
UCKK-GJS
UCKK-KOA
UCKK-AS
UCKK-SP
UCKK-ME
UCKK-LI
UCKK-LEAD
UCKK-ARCH
UCKK-UX
UCKK-ETHIC
UCKK-IA
```

`categories.json` must include those before course/program validation is considered complete.

### Validation rules

* `idnumber` required and unique.
* `name` required.
* `parent`, when present, must reference another seeded category idnumber or an existing Moodle category idnumber.
* No category may create a cycle.
* `idnumber` must fit Moodle `course_categories.idnumber`.

---

## 6.2 `programs.json`

**Preset:** `programs`
**Final handler:** `program_seed`
**Moodle/local owner:** `local_uckk`
**Table:** `local_uckk_program`
**Idempotency key:** `shortname`; `idnumber` should also be unique in the JSON even if not a table column.

`programs.json` is seedable in the final target. It is not canon-only.

### Required item fields

```json
{
  "key": "tronc_commun",
  "id": "program:tronc-commun",
  "code": "TC",
  "shortname": "TC",
  "idnumber": "UCKK-PROG-TC",
  "name": "Tronc commun obligatoire",
  "fullname": "Tronc commun obligatoire",
  "program_type": "tronc_commun",
  "category": "UCKK-TC",
  "category_idnumber": "UCKK-TC",
  "description": "...",
  "summary": "...",
  "status": "active",
  "visibility": "institution",
  "visible": 1,
  "sortorder": 10,
  "domain": {},
  "palier": {},
  "recognition": {},
  "tags": [],
  "metadata": {}
}
```

### Storage mapping

| JSON field                         | `local_uckk_program` column                                           |
| ---------------------------------- | --------------------------------------------------------------------- |
| `shortname`                        | `shortname`                                                           |
| `fullname` / `name` / `title`      | `fullname`                                                            |
| `program_type`                     | `programtype`                                                         |
| `category` / `category_idnumber`   | resolves to `categoryid`                                              |
| `description` / `summary`          | `description`                                                         |
| `sortorder`                        | `sortorder`                                                           |
| `status`                           | `status`                                                              |
| `visibility`                       | `visibility`                                                          |
| generated seed context             | `contextid`, `createdby`, `modifiedby`, `timecreated`, `timemodified` |
| `metadata` plus extra canon fields | `metadata`                                                            |

### Program type vocabulary

Final handlers should accept current JSON/source vocabulary and map it to local storage values where needed.

Recommended JSON values:

```text
tronc_commun
voie_uckk
mineure
seminaire
laboratoire
orientation
palier
parcours_transversal
```

Local model values include:

```text
tronccommun
baccalaureat
mineure
lab
seminar
transversal
```

### Runtime status vocabulary

`program_seed` must either accept or normalize every status used by `programs.json`.

The safe runtime vocabulary is:

```text
draft
active
hidden
archived
pending
pending_review
validated
rejected
correction_required
contested
invalidated
closed
cancelled
completed
blocked
```

`internal_experimental` is not a valid runtime status unless `program_seed` is explicitly extended to accept it. Experimental programs should use `draft` unless the lifecycle vocabulary is intentionally expanded.

### Validation rules

* `key`, `shortname`, `fullname`, `program_type`, and `status` required.
* `category` must reference an existing `categories.idnumber` when present.
* `code`, `shortname`, and `idnumber` must be unique within `programs.json`.
* Public accreditation disclaimers remain description/metadata content, not role/capability logic.

---

## 6.3 `pathways.json`

**Preset:** `pathways`
**Final handler:** `pathway_seed`
**Moodle/local owner:** `local_uckk`
**Table:** `local_uckk_pathway`
**Idempotency key:** `programid + shortname`; `idnumber` should also be unique in the JSON.

`pathways.json` is seedable in the final target. It is not canon-only.

### Required item fields

```json
{
  "key": "tronc_commun_main",
  "id": "pathway:tronc-commun-main",
  "idnumber": "UCKK-PATH-TRONC-COMMUN-MAIN",
  "shortname": "tronc_commun_main",
  "name": "Parcours principal — Tronc commun obligatoire",
  "fullname": "Parcours principal — Tronc commun obligatoire",
  "program_id": "program:tronc-commun",
  "program_code": "TC",
  "pathway_type": "ordered_courses",
  "sequence_model": "ordered_courses",
  "status": "active",
  "visibility": "institution",
  "sortorder": 10,
  "prerequisite_pathway_refs": [],
  "cycles": [],
  "completion_rule": {},
  "competency_refs": [],
  "badge_refs": [],
  "evidence_requirements": [],
  "metadata": {}
}
```

### Storage mapping

| JSON field                                                    | `local_uckk_pathway` column                                           |
| ------------------------------------------------------------- | --------------------------------------------------------------------- |
| `program_id` / `program_code`                                 | resolves to `programid`                                               |
| `shortname`                                                   | `shortname`                                                           |
| `fullname` / `name` / `title`                                 | `fullname`                                                            |
| `pathway_type` / `sequence_model`                             | `pathwaytype`                                                         |
| flattened required course ids from `cycles[].course_refs[]`   | `requiredcourseids`                                                   |
| `badge_refs`                                                  | `requiredbadges`                                                      |
| `competency_refs`                                             | `requiredcompetencies`                                                |
| `description`                                                 | `description`                                                         |
| `sortorder`                                                   | `sortorder`                                                           |
| `status`                                                      | `status`                                                              |
| `visibility`                                                  | `visibility`                                                          |
| generated seed context                                        | `contextid`, `createdby`, `modifiedby`, `timecreated`, `timemodified` |
| `metadata` plus `cycles`, `completion_rule`, evidence details | `metadata`                                                            |

### Course reference shape

Inside each `cycles[].course_refs[]`:

```json
{
  "course_id": "course:uckk-tc101",
  "course_code": "UCKK-TC101",
  "requirement": "required",
  "sequence": 1,
  "recognition_mode": "program_specific",
  "variant_group": "variant:..."
}
```

### Recommended vocabularies

`sequence_model` / `pathway_type`:

```text
ordered_courses
cycles
phases
modules
portfolio_based
tronc_commun
baccalaureat
mineure
seminaire
laboratoire
```

`requirement`:

```text
required
optional
advanced_option
final_project
portfolio
challenge
assembly_participation
```

`recognition_mode`:

```text
shared_course
program_specific
specialized_variant
equivalent
external_recognition_pending
```

### Validation rules

* `program_id` must match a seeded program `id`, or `program_code` must match a seeded program `code`.
* `course_code` must match a seeded course `code`/`idnumber`, or `course_id` must match a seeded course `id`.
* `badge_refs` must match seeded badge `id`, `idnumber`, or `key` from `badges.json`, or an existing Moodle badge identifier when available.
* `competency_refs` must match seeded competency `id`, `idnumber`, or `key`.
* `cycles[].course_refs[]` must be ordered and deterministic.
* Validation must not require Moodle's `badge` table to contain non-core/generated columns such as `uniquehash`. When badge columns are inspected, the handler must first check the installed table schema.

---

## 6.4 `cohorts.json`

**Preset:** `cohorts`
**Handler:** `cohort_seed`
**Moodle owner:** Moodle cohorts
**Idempotency key:** `idnumber`

### Required item fields

```json
{
  "key": "UCKK-TC-Aspirants",
  "idnumber": "UCKK-TC-Aspirants",
  "name": "Aspirants — Tronc commun",
  "context": "system",
  "description": "...",
  "visible": 1,
  "metadata": {}
}
```

### Validation rules

* `idnumber` required and unique.
* `name` required.
* `context` must be `system`, `category`, or `course`.
* Category/course contexts must reference valid seeded/existing category/course ids.

---

## 6.5 `roles.json`

**Preset:** `roles`
**Handler:** `role_seed`
**Moodle owner:** Moodle roles
**Idempotency key:** `shortname`

### Required item fields

```json
{
  "key": "uckklearner",
  "shortname": "uckklearner",
  "name": "UCKK learner",
  "description": "...",
  "archetype": "student",
  "contextlevels": ["course"],
  "permissions": [],
  "metadata": {}
}
```

### Technical role shortnames

Seeded Moodle roles must remain technical roles, not symbolic identities.

Allowed technical role examples:

```text
uckklearner
uckkmentor
uckkmanager
uckkarchivist
uckkintegrityofficer
uckkassemblyfacilitator
uckkauditor
```

Symbolic titles such as `Le Mage`, `Inquisiteur`, `Architecte`, `Cartographe`, `Archiviste mythique` must remain profile, badge, cohort, pathway, or display metadata, not Moodle role shortnames.

### Permission values

```text
allow
prevent
prohibit
inherit
```

### Allowed context levels

```text
system
user
category
course
module
block
```

Handler must map these to Moodle context constants.

---

## 6.6 `capabilities.json`

**Preset:** `capabilities`
**Handler:** `capability_seed`
**Moodle owner:** Moodle role capability assignments
**Idempotency key:** `role + capability`

### Required item fields

```json
{
  "key": "uckkmanager:local/uckk:manageprograms",
  "role": "uckkmanager",
  "capability": "local/uckk:manageprograms",
  "permission": "allow",
  "context": "system",
  "metadata": {}
}
```

### Validation rules

* `role` must reference a seeded/existing role shortname.
* `capability` must exist in Moodle core or plugin `db/access.php`.
* `permission` must be `allow`, `prevent`, `prohibit`, or `inherit`.
* `context` must be valid for the target capability.
* Capability context mismatches must be reported as warnings or errors depending on strictness.

### Allowed UCKK capability prefixes

```text
local/uckk:
block/uckk_dashboard:
mod/uckkchallenge:
mod/uckkassembly:
mod/uckkarchive:
tool/uckkintegrity:
report/uckk:
format/uckk:
theme/uckk:
aiprovider/uckk:
```

---

## 6.7 `competencies.json`

**Preset:** `competencies`
**Handler:** `competency_seed`
**Moodle owner:** Moodle core competency framework
**Idempotency key:** framework idnumber + competency idnumber

### Required item fields

```json
{
  "key": "UCKK-COMP-TC-SYNTHESIS",
  "idnumber": "UCKK-COMP-TC-SYNTHESIS",
  "shortname": "Synthesis",
  "description": "...",
  "framework": "UCKK-COMP",
  "parent": "",
  "sortorder": 10,
  "scale": "defaultcompetencescale",
  "metadata": {}
}
```

### Object types

`competencies.json` may contain both framework and competency rows. Use `object_type` when needed:

```text
framework
competency
```

### Validation rules

* Framework idnumber required for frameworks.
* Competency idnumber required for competencies.
* Current and final handlers must accept UCKK competency idnumbers matching:

```text
^UCKK-COMP-[A-Z0-9-]+$
```

* Parent references must resolve within the same preset or existing Moodle competency storage.
* AI-generated competency descriptions are metadata/provenance only.

---

## 6.8 `badges.json`

**Preset:** `badges`
**Handler:** `badge_seed`
**Moodle owner:** Moodle badges
**Idempotency key:** `key`; `idnumber` should also be unique.

### Required item fields

```json
{
  "key": "tronc_commun_completion",
  "id": "badge:tronc-commun-completion",
  "object_type": "badge",
  "idnumber": "UCKK-BADGE-TC-COMPLETION",
  "name": "Socle commun UCKK — Joueur lucide en formation",
  "title": "Socle commun UCKK — Joueur lucide en formation",
  "short_title": "Tronc commun",
  "description": "...",
  "type": "site",
  "badge_type": "tronc_commun_completion",
  "status": "active",
  "visible": 1,
  "enabled": 1,
  "program_id": "program:tronc-commun",
  "pathway_id": "pathway:tronc-commun-main",
  "criteria": [
    "pathway_completion",
    "human_validation",
    "competency_threshold",
    "archive_or_portfolio",
    "no_unresolved_integrity_block"
  ],
  "competencies": ["UCKK-COMP-TC-SYNTHESIS"],
  "linked_competency_ids": ["competency:tronc-commun:synthesis"],
  "linked_course_ids": {
    "required": [],
    "optional": [],
    "final_project": []
  },
  "award_criteria": [],
  "requiredarchive": true,
  "requireshumanvalidation": true,
  "issuer": {},
  "metadata": {}
}
```

### Required badge criteria vocabulary

Final badge seed logic must support these criteria:

```text
pathway_completion
course_completion
evidence_submission
human_validation
competency_threshold
archive_or_portfolio
no_unresolved_integrity_block
assembly_participation
challenge_completion
```

### Moodle badge schema compatibility

`badge_seed` must use Moodle's installed badge schema as the source of truth.

Seed ownership must not depend on a required `badge.uniquehash` column. The handler may use optional/generated columns only after checking that they exist in the installed table. Seed-managed badges should be reconciled by stable JSON `key` / `idnumber` and plugin-owned config markers such as:

```text
tool_uckkseed.badge_<key>_id
tool_uckkseed.badge_<key>_definition
```

This keeps the seed tool compatible with Moodle installations whose `badge` table does not contain `uniquehash`.

### Validation rules

* `key`, `name`, `description`, and `type` required.
* `type` must be `site` or `course` unless the handler is explicitly extended.
* `key` must be unique.
* `idnumber`, when present, must be unique.
* `competencies` should use competency `idnumber` values.
* `linked_competency_ids` may preserve canonical competency `id` values.
* `linked_course_ids` may preserve canonical course `id` values; seed logic must resolve them through course `id`, `code`, or `idnumber`.
* Program completion badges and pathway completion badges are first-class final seed objects.
* Symbolic legacy badges may exist, but are not the only supported badge family.

---

## 6.9 `course_templates.json`

**Preset:** `course_templates`
**Handler:** `course_template_seed`
**Moodle owner:** seed-managed template definitions
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "uckk_standard_course",
  "name": "UCKK standard course",
  "description": "...",
  "format": "uckk",
  "sections": [],
  "activities": [],
  "completion_defaults": {},
  "metadata": {}
}
```

### Validation rules

* `key` required and unique.
* `name` required.
* Template validation must not create Moodle courses.
* Template presets must not be routed through `course_seed`.
* Activity component references must be optional unless strict mode is enabled.

---

## 6.10 `challenge_templates.json`

**Preset:** `challenge_templates`
**Handler:** `challenge_template_seed`
**Moodle owner:** `mod_uckkchallenge` template definitions
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "uckk_reflection_challenge",
  "name": "UCKK reflection challenge",
  "description": "...",
  "visibility": "institution",
  "sections": [],
  "requirements": [],
  "metadata": {}
}
```

### Validation rules

* `key` required and unique.
* `name` required.
* Sections should have stable `key` or deterministic `number`.
* Badge, competency, and role references must resolve when strict mode is used.
* Challenge templates must not create course activities directly unless apply mode explicitly instantiates them.

---

## 6.11 `assembly_templates.json`

**Preset:** `assembly_templates`
**Handler:** `assembly_template_seed`
**Moodle owner:** `mod_uckkassembly` template definitions
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "uckk_deliberation_assembly",
  "name": "UCKK deliberation assembly",
  "description": "...",
  "visibility": "institution",
  "validationstate": "draft",
  "roles": [],
  "steps": [],
  "metadata": {}
}
```

### Validation rules

* `key` required and unique.
* `name` required.
* `visibility` must be in the handler vocabulary.
* `validationstate` must be in the handler vocabulary.
* Human review state must not be treated as AI authority.

---

## 6.12 `archive_templates.json`

**Preset:** `archive_templates`
**Handler:** `archive_template_seed`
**Moodle owner:** `mod_uckkarchive` template definitions
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "uckk_archive_portfolio",
  "name": "UCKK archive portfolio",
  "description": "...",
  "defaults": {
    "visibility": "private"
  },
  "fields": [],
  "metadata": {}
}
```

### Validation rules

* `key` required and unique.
* `name` required.
* `defaults.visibility` must be in the archive handler vocabulary.
* Archive templates must not validate archive evidence directly.
* Privacy and retention policy metadata must remain explicit.

---

## 6.13 `courses.json`

**Preset:** `courses`
**Handler:** `course_seed`
**Moodle owner:** Moodle courses
**Idempotency key:** `shortname`; `idnumber` should also be unique.

### Required item fields

```json
{
  "key": "uckk_tc101",
  "id": "course:uckk-tc101",
  "shortname": "UCKK-TC101",
  "idnumber": "UCKK-TC101",
  "fullname": "UCKK TC101 — Introduction",
  "category": "UCKK-TC",
  "category_idnumber": "UCKK-TC",
  "format": "uckk",
  "visible": 1,
  "summary": "...",
  "metadata": {}
}
```

### Validation rules

* `shortname` required and unique.
* `fullname` required.
* `category` / `category_idnumber` must resolve to `categories.idnumber` or existing Moodle category idnumber.
* `summaryformat` must be numeric or safely normalized.
* `format` must be installed or safely default to Moodle format.
* Course creation must not automatically enrol users unless explicitly configured.
* Course creation must not automatically award badges or certify competencies.

---

## 6.14 `reports.json`

**Preset:** `reports`
**Handler:** `report_seed`
**Moodle owner:** `report_uckk`
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "uckk_program_progress",
  "name": "UCKK program progress",
  "capability": "report/uckk:view",
  "description": "...",
  "metadata": {}
}
```

### Allowed report capabilities

```text
report/uckk:view
report/uckk:viewown
report/uckk:viewall
report/uckk:export
```

### Validation rules

* `key` required and unique.
* `name` required.
* `capability` required.
* `capability` must exist in `report/uckk/db/access.php`.
* Report definitions must not expose restricted integrity/archive data without explicit capabilities.

---

# 7. Capability registry

This reference recognizes the following UCKK capability families.

## 7.1 `local_uckk`

```text
local/uckk:view
local/uckk:manage
local/uckk:manageprograms
local/uckk:viewprograms
local/uckk:managepathways
local/uckk:viewpathways
local/uckk:manageprofiles
local/uckk:viewprofiles
local/uckk:managecanon
local/uckk:viewcanon
local/uckk:assignpathways
local/uckk:viewreports
```

## 7.2 `block_uckk_dashboard`

```text
block/uckk_dashboard:view
block/uckk_dashboard:viewothers
block/uckk_dashboard:configure
block/uckk_dashboard:addinstance
block/uckk_dashboard:myaddinstance
```

## 7.3 `mod_uckkchallenge`

```text
mod/uckkchallenge:view
mod/uckkchallenge:addinstance
mod/uckkchallenge:submit
mod/uckkchallenge:grade
mod/uckkchallenge:manage
mod/uckkchallenge:viewallsubmissions
mod/uckkchallenge:validate
mod/uckkchallenge:override
```

## 7.4 `mod_uckkassembly`

```text
mod/uckkassembly:view
mod/uckkassembly:addinstance
mod/uckkassembly:participate
mod/uckkassembly:facilitate
mod/uckkassembly:manage
mod/uckkassembly:viewall
mod/uckkassembly:recorddecision
mod/uckkassembly:publish
```

## 7.5 `mod_uckkarchive`

```text
mod/uckkarchive:view
mod/uckkarchive:addinstance
mod/uckkarchive:submit
mod/uckkarchive:revise
mod/uckkarchive:validate
mod/uckkarchive:manage
mod/uckkarchive:viewrestricted
mod/uckkarchive:export
```

## 7.6 `tool_uckkintegrity`

```text
tool/uckkintegrity:view
tool/uckkintegrity:viewall
tool/uckkintegrity:opencase
tool/uckkintegrity:review
tool/uckkintegrity:decide
tool/uckkintegrity:appeal
tool/uckkintegrity:manage
tool/uckkintegrity:export
```

## 7.7 `report_uckk`

```text
report/uckk:view
report/uckk:viewown
report/uckk:viewall
report/uckk:export
```

## 7.8 `format_uckk`

```text
format/uckk:viewmap
format/uckk:editmap
format/uckk:managepathways
```

## 7.9 `theme_uckk`

```text
theme/uckk:configure
theme/uckk:viewdebug
```

## 7.10 `aiprovider_uckk`

```text
aiprovider/uckk:use
aiprovider/uckk:configure
aiprovider/uckk:viewlogs
```

---

# 8. Cross-preset dependencies

Dependencies are divided into **hard storage dependencies** and **soft semantic references**.

Hard dependencies must be available before apply writes the target record. Soft references may be checked against JSON presets before the referenced Moodle object exists.

| Preset                | Hard dependencies                                                                     | Soft references allowed                                       |
| --------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `categories`          | none                                                                                  | none                                                          |
| `programs`            | `categories` when `category` / `category_idnumber` is present                         | none                                                          |
| `cohorts`             | Moodle cohort API; `categories` for category-context cohorts                          | none                                                          |
| `roles`               | plugin capabilities declared in `db/access.php`                                       | none                                                          |
| `capabilities`        | `roles`, plugin capabilities declared in `db/access.php`                              | none                                                          |
| `competencies`        | Moodle core competency API                                                            | framework/parent ids inside the same preset                   |
| `course_templates`    | activity components when activities are declared                                      | none                                                          |
| `challenge_templates` | `mod_uckkchallenge`                                                                   | badges, competencies, roles by stable reference               |
| `assembly_templates`  | `mod_uckkassembly`                                                                    | roles, cohorts, pathways by stable reference                  |
| `archive_templates`   | `mod_uckkarchive`                                                                     | roles, cohorts, pathways by stable reference                  |
| `courses`             | `categories`, `course_templates` when a template is referenced                        | competencies, badges, programs/pathways by stable reference   |
| `badges`              | Moodle badge API; Moodle badge table columns actually present in the installed schema | programs, pathways, courses, competencies by stable reference |
| `pathways`            | `programs`                                                                            | courses, competencies, badges by stable reference             |
| `reports`             | `report_uckk`, report capabilities                                                    | none                                                          |

This split removes the circular hard dependency between `badges` and `pathways`. A badge may describe a pathway-completion badge before the pathway DB row exists, and a pathway may list badge references before the badge DB row exists, as long as both references use stable JSON identifiers and validation can resolve them from preset files.

---

# 9. Final implementation alignment requirements

To make this reference executable as the final version, `tool_uckkseed` must maintain these alignments:

1. Keep `PRESET_PROGRAMS = 'programs'` routed to `program_seed`.
2. Keep `PRESET_PATHWAYS = 'pathways'` routed to `pathway_seed`.
3. Keep `program_seed` writing to `local_uckk_program`.
4. Keep `pathway_seed` writing to `local_uckk_pathway`.
5. Keep `capability_seed` routed for `capabilities`; do not route capabilities through `role_seed`.
6. Keep `course_template_seed`, `challenge_template_seed`, `assembly_template_seed`, and `archive_template_seed` routed for their template presets.
7. Do not route template presets to `course_seed`.
8. Ensure CLI and form entry points expose the same preset registry as `seeder.php`, including `programs` and `pathways`.
9. Ensure `validate`, dry run, apply, reset, and export use the same preset ids and handlers.
10. Ensure `competency_seed` idnumber validation accepts `^UCKK-COMP-[A-Z0-9-]+$`.
11. Ensure `course_seed` accepts `category` as canonical and may derive it from `category_idnumber` during item normalization.
12. Ensure `category_seed` accepts `parent` as canonical and may derive it from `parent_idnumber` during item normalization.
13. Ensure `badge_seed` supports final program/pathway completion badge fields in addition to symbolic legacy badge fields.
14. Ensure `badge_seed` does not require non-core Moodle badge columns such as `badge.uniquehash`. Optional columns may be used only after checking the installed schema.
15. Keep `reports.json` using top-level `capability`; do not move report capability exclusively into metadata.
16. Ensure generated export payloads use the runtime envelope from this reference.

---

# 10. Validation checklist

Before committing any `academic_registry_json/*.json` change:

```text
[ ] Top-level envelope is schema/component/preset/version/items.
[ ] preset matches filename without .json.
[ ] items is an array.
[ ] Every item has the required stable key for its preset.
[ ] All idempotency keys are unique in the preset.
[ ] All hard cross-preset dependencies resolve.
[ ] Soft cross-preset references resolve either from Moodle storage or from another governed JSON preset.
[ ] All capabilities exist in plugin db/access.php or Moodle core.
[ ] All Moodle category references resolve through categories.idnumber.
[ ] No symbolic UCKK identity is created as a Moodle role.
[ ] Program/pathway objects are seedable, not canon-only.
[ ] Templates are validated by template seeders, not course_seed.
[ ] Badge seed logic does not require non-core Moodle badge columns.
[ ] AI metadata is non-authoritative.
[ ] Public status notices avoid accreditation confusion.
[ ] Standalone mode does not require Konnaxion.
```

Run:

```bash
cd C:\mycode\UCKK\moodle\moodle
php admin\cli\purge_caches.php
php admin\cli\upgrade.php --non-interactive

cd C:\mycode\UCKK\moodle\moodle\public
php admin\tool\uckkseed\cli\validate.php --presetpath=academic_registry_json
```

---

# 11. Migration note from the older canon document

The older document `docs/JSON preset format for UCKK academic registry.txt` described a canonical envelope using:

```json
{
  "schema_version": "1.0",
  "preset_type": "courses",
  "source_family": "UCKK canon",
  "items": []
}
```

That structure is no longer the active runtime seed envelope. It remains useful as historical/conceptual documentation, but the final seedable JSON contract is this reference:

```json
{
  "schema": "uckkseed.preset.v1",
  "component": "tool_uckkseed",
  "preset": "courses",
  "version": 2026051200,
  "items": []
}
```

Do not keep both formats as peer runtime standards. If canon fields are needed, preserve them inside `metadata` or as item-level domain fields.

---

# 12. Source basis

This reference is aligned to the repository snapshots dated `2026-05-19T11:04:28` and `2026-05-20T09:03:31`, especially:

```text
admin/tool/uckkseed/classes/local/seeder.php
admin/tool/uckkseed/classes/local/category_seed.php
admin/tool/uckkseed/classes/local/program_seed.php
admin/tool/uckkseed/classes/local/pathway_seed.php
admin/tool/uckkseed/classes/local/course_seed.php
admin/tool/uckkseed/classes/local/cohort_seed.php
admin/tool/uckkseed/classes/local/role_seed.php
admin/tool/uckkseed/classes/local/competency_seed.php
admin/tool/uckkseed/classes/local/badge_seed.php
admin/tool/uckkseed/classes/local/report_seed.php
admin/tool/uckkseed/cli/seed.php
admin/tool/uckkseed/cli/reset.php
admin/tool/uckkseed/cli/export_preset.php
admin/tool/uckkseed/classes/form/seed_form.php
admin/tool/uckkseed/classes/form/reset_form.php
local/uckk/db/install.xml
local/uckk/classes/local/program.php
local/uckk/classes/local/pathway.php
all plugin db/access.php files listed above
academic_registry_json/*.json
docs/JSON preset format for UCKK academic registry.txt
```


