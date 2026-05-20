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
```

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
  "generated_at": "...",
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

| Preset | JSON file | Final handler | Owner / target |
|---|---|---|---|
| `categories` | `categories.json` | `category_seed` | Moodle course categories |
| `programs` | `programs.json` | `program_seed` | `local_uckk_program` |
| `pathways` | `pathways.json` | `pathway_seed` | `local_uckk_pathway` |
| `cohorts` | `cohorts.json` | `cohort_seed` | Moodle cohorts |
| `roles` | `roles.json` | `role_seed` | Moodle roles and embedded role capabilities |
| `capabilities` | `capabilities.json` | `capability_seed` | Moodle role capability assignments |
| `competencies` | `competencies.json` | `competency_seed` | Moodle core competency framework |
| `badges` | `badges.json` | `badge_seed` | Moodle badges |
| `course_templates` | `course_templates.json` | `course_template_seed` | seed-managed course templates |
| `challenge_templates` | `challenge_templates.json` | `challenge_template_seed` | `mod_uckkchallenge` template definitions |
| `assembly_templates` | `assembly_templates.json` | `assembly_template_seed` | `mod_uckkassembly` template definitions |
| `archive_templates` | `archive_templates.json` | `archive_template_seed` | `mod_uckkarchive` template definitions |
| `courses` | `courses.json` | `course_seed` | Moodle courses |
| `reports` | `reports.json` | `report_seed` | `report_uckk` seeded report definitions |

### Final default validation/seed order

```text
categories
programs
pathways
cohorts
roles
capabilities
competencies
badges
course_templates
challenge_templates
assembly_templates
archive_templates
courses
reports
```

### Current implementation gap

The current `seeder.php` supports the runtime envelope and 12 presets, but it does not yet route `programs` or `pathways`, routes `capabilities` to `role_seed`, and routes all template presets to `course_seed`. The final implementation must correct that routing.

---

## 5. Common item rules

### 5.1 Stable `key`

`key` is the seed tool stable object key. It must be unique inside one preset.

Recommended format:

```text
[a-z0-9_]+
```

Examples:

```text
uckk_tc101
tronc_commun
pathway_tronc_commun_main
joueur_progress
```

### 5.2 Stable canonical `id`

Where source/canon identifiers are present, `id` may use namespaced identifiers:

```text
category:uckk-tronc-commun
program:tronc-commun
pathway:tronc-commun-main
course:uckk-tc101
competency:tronc-commun:synthesis
badge:tronc-commun-completion
```

`id` is not a database id and must never depend on an auto-increment value.

### 5.3 Moodle reconciliation identifiers

Use Moodle/API reconciliation identifiers wherever possible:

| Field | Meaning |
|---|---|
| `idnumber` | Moodle-visible reconciliation id where available |
| `shortname` | Moodle shortname or local short key |
| `fullname` | Moodle fullname or local display title |
| `name` | generic display name for non-course objects |
| `category` | category idnumber/key consumed by `course_seed` and related seeders |

`category_idnumber` may exist as a compatibility/source field, but final seeders should treat `category` as the normalized category lookup field.

### 5.4 Status and visibility

Use these normalized fields when relevant:

```json
{
  "status": "active",
  "visibility": "institution",
  "visible": 1
}
```

`visible` may be generated as `true`/`false`, but final Moodle-facing seed JSON should prefer `1`/`0`.

### 5.5 Metadata

`metadata` must be JSON-serializable and non-authoritative. Seeders must not require business-critical fields to exist only inside `metadata` when they are needed for validation, lookup, or creation.

### 5.6 AI metadata

Academic presets may include `ai_metadata` for search, retrieval, summarization, classification, recommendation, curriculum navigation, and documentation generation. AI metadata must not decide grades, badge awards, program completion, evidence validation, integrity cases, assembly decisions, or Parchemin recognition.

Recommended structure:

```json
{
  "ai_metadata": {
    "summary_for_retrieval": "",
    "keywords": [],
    "tags": [],
    "related_concepts": [],
    "source_fragments": [],
    "non_authority_rule": "AI outputs are assistive and never final authority."
  }
}
```

---

# 6. Preset contracts

---

## 6.1 `categories.json`

**Preset:** `categories`  
**Handler:** `category_seed`  
**Moodle owner:** `core_course_category` / `course_categories`  
**Idempotency key:** `course_categories.idnumber`

### Required item fields

```json
{
  "key": "uckk_tc",
  "name": "01 — Tronc commun obligatoire",
  "idnumber": "UCKK-TC",
  "parent": "UCKK",
  "description": "...",
  "sortorder": 20,
  "visible": 1,
  "metadata": {}
}
```

### Accepted aliases

`parent_idnumber` may be present as a source/canon alias. Final seed JSON should include `parent` directly:

```json
{
  "parent_idnumber": "UCKK",
  "parent": "UCKK"
}
```

### Required category idnumbers for the current course set

These category idnumbers must exist because `courses.json` references them:

```text
UCKK-TC
UCKK-GJS
UCKK-KOA
UCKK-AS
UCKK-SP
UCKK-EC
UCKK-ECO
UCKK-ME
UCKK-IA
UCKK-LI
UCKK-IS
UCKK-MV
```

System/support categories may retain longer idnumbers:

```text
UCKK-CAT-00-ACCUEIL-ORIENTATION
UCKK-CAT-13-SEMINAIRES-LABS
UCKK-CAT-90-DEFIS-KING-KLOWN
UCKK-CAT-91-ASSEMBLEES
UCKK-CAT-92-ARCHIVES-PORTFOLIOS
UCKK-CAT-99-INTEGRITE-INQUISITEUR
```

### Validation rules

- `key` required and unique.
- `name` required.
- `idnumber` required and unique.
- `parent`, when present, should resolve to another category `key` or `idnumber` in the preset or existing Moodle categories.
- `visible` normalizes to boolean/integer visibility.

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

| JSON field | `local_uckk_program` column |
|---|---|
| `shortname` | `shortname` |
| `fullname` / `name` / `title` | `fullname` |
| `program_type` | `programtype` |
| `category` / `category_idnumber` | resolves to `categoryid` |
| `description` / `summary` | `description` |
| `sortorder` | `sortorder` |
| `status` | `status` |
| `visibility` | `visibility` |
| generated seed context | `contextid`, `createdby`, `modifiedby`, `timecreated`, `timemodified` |
| `metadata` plus extra canon fields | `metadata` |

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

### Validation rules

- `key`, `shortname`, `fullname`, `program_type`, and `status` required.
- `category` must reference an existing `categories.idnumber` when present.
- `code`, `shortname`, and `idnumber` must be unique within `programs.json`.
- Public accreditation disclaimers remain description/metadata content, not role/capability logic.

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

| JSON field | `local_uckk_pathway` column |
|---|---|
| `program_id` / `program_code` | resolves to `programid` |
| `shortname` | `shortname` |
| `fullname` / `name` / `title` | `fullname` |
| `pathway_type` / `sequence_model` | `pathwaytype` |
| flattened required course ids from `cycles[].course_refs[]` | `requiredcourseids` |
| `badge_refs` | `requiredbadges` |
| `competency_refs` | `requiredcompetencies` |
| `description` | `description` |
| `sortorder` | `sortorder` |
| `status` | `status` |
| `visibility` | `visibility` |
| generated seed context | `contextid`, `createdby`, `modifiedby`, `timecreated`, `timemodified` |
| `metadata` plus `cycles`, `completion_rule`, evidence details | `metadata` |

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

- `program_id` must match a seeded program `id`, or `program_code` must match a seeded program `code`.
- `course_code` must match a seeded course `code`/`idnumber`, or `course_id` must match a seeded course `id`.
- `badge_refs` must match seeded badge `id`, `idnumber`, or `key`.
- `competency_refs` must match seeded competency `id`, `idnumber`, or `key`.
- `cycles[].course_refs[]` must be ordered and deterministic.

---

## 6.4 `cohorts.json`

**Preset:** `cohorts`  
**Handler:** `cohort_seed`  
**Moodle owner:** core cohorts  
**Idempotency key:** `idnumber`

### Required item fields

```json
{
  "key": "uckk_all",
  "name": "UCKK — Communauté complète",
  "idnumber": "UCKK-COHORT-ALL",
  "context": "system",
  "category": "",
  "description": "...",
  "visible": 1,
  "status": "active",
  "visibility": "institution",
  "metadata": {}
}
```

### Validation rules

- `key`, `name`, and `idnumber` required.
- `idnumber` should start with `UCKK-COHORT-`.
- `context` must be `system` or `course_category`.
- If `context` is `course_category`, `category` is required and must reference a category idnumber/key.
- Cohorts may refer to technical or symbolic UCKK concepts in metadata, but must not create Moodle roles.

---

## 6.5 `roles.json`

**Preset:** `roles`  
**Handler:** `role_seed`  
**Moodle owner:** Moodle roles and embedded role capabilities  
**Idempotency key:** `shortname`

### Required item fields

```json
{
  "shortname": "uckkmanager",
  "name": "Gestionnaire UCKK",
  "description": "...",
  "archetype": "manager",
  "contextlevels": ["system", "course_category", "course", "module", "user"],
  "capabilities": [
    {
      "capability": "local/uckk:viewcampus",
      "permission": "allow",
      "context": "system",
      "component": "local_uckk",
      "metadata": {}
    }
  ],
  "metadata": {}
}
```

### Technical role shortnames

Only these technical Moodle roles may be created:

```text
uckkmanager
uckkmentor
uckkplayer
uckkarchivist
uckkinquisitor
uckkobserver
uckkpublicguest
```

Symbolic UCKK identities must not be Moodle roles. They belong in badges, cohorts, profile fields, portfolios, competencies, archive distinctions, or metadata.

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
course_category
course
module
block
user
```

---

## 6.6 `capabilities.json`

**Preset:** `capabilities`  
**Final handler:** `capability_seed`  
**Current migration source:** equivalent to role capability assignments  
**Moodle owner:** Moodle role capabilities  
**Idempotency key:** `role + capability + context`

`capabilities.json` is seedable in the final target. It must not be treated as canon-only.

### Required item fields

```json
{
  "role": "uckkmanager",
  "capability": "local/uckk:viewcampus",
  "permission": "allow",
  "context": "system",
  "component": "local_uckk",
  "metadata": {}
}
```

### Validation rules

- `role` must match a technical role shortname from `roles.json`.
- `capability` must be a real Moodle capability declared by a plugin `db/access.php` or a valid core capability.
- `component` must match the owning Moodle component name.
- `permission` must be one of `allow`, `prevent`, `prohibit`, `inherit`.
- `context` must be one of the allowed context level names.
- Final code should validate `capabilities.json` with `capability_seed`, not `role_seed`.

### Allowed UCKK capability prefixes

```text
local/uckk:
format/uckk:
block/uckk_dashboard:
mod/uckkchallenge:
mod/uckkassembly:
mod/uckkarchive:
tool/uckkseed:
tool/uckkintegrity:
report/uckk:
aiprovider/uckk:
```

---

## 6.7 `competencies.json`

**Preset:** `competencies`  
**Handler:** `competency_seed`  
**Moodle owner:** core competency API  
**Idempotency key:** `framework + idnumber`

### Required item fields

```json
{
  "key": "uckk_comp_tc101",
  "id": "competency:tc101",
  "object_type": "competency",
  "idnumber": "UCKK-COMP-TC101",
  "name": "Compétence — Cartographie des idées avec l’IA",
  "shortname": "Cartographie des idées avec l’IA",
  "fullname": "Compétence — Cartographie des idées avec l’IA",
  "description": "...",
  "descriptionformat": 1,
  "framework": "uckk_competency_framework",
  "parent": "UCKK-COMP-FRAMEWORK",
  "scale": "uckk_competency_progression",
  "sortorder": 10,
  "visible": 1,
  "metadata": {}
}
```

### Object types

```text
competency_framework
program_synthesis
course_competency
competency
```

### Validation rules

- `key`, `idnumber`, `shortname`, and `framework` required.
- `idnumber` must be stable and unique inside the framework.
- Final accepted idnumber pattern:

```text
^UCKK-COMP-[A-Z0-9-]+$
```

- Numeric legacy ids such as `UCKK-COMP-001` may remain valid.
- Current ids such as `UCKK-COMP-TC101`, `UCKK-COMP-KOA204`, and `UCKK-COMP-TC-SYNTHESIS` are valid final ids.
- If code still warns on non-`UCKK-COMP-###`, update the code to this reference.

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

### Validation rules

- `key`, `name`, `description`, and `type` required.
- `type` must be `site` or `course` unless the handler is explicitly extended.
- `key` must be unique.
- `idnumber`, when present, must be unique.
- `competencies` should use competency `idnumber` values.
- `linked_competency_ids` may preserve canonical competency `id` values.
- `linked_course_ids` may preserve canonical course `id` values; seed logic must resolve them through course `id`, `code`, or `idnumber`.
- Program completion badges and pathway completion badges are first-class final seed objects.
- Symbolic legacy badges may exist, but are not the only supported badge family.

---

## 6.9 `course_templates.json`

**Preset:** `course_templates`  
**Final handler:** `course_template_seed`  
**Owner:** `tool_uckkseed` / `format_uckk` template registry  
**Idempotency key:** `key`

Templates are not courses. They must not be validated by `course_seed`.

### Required item fields

```json
{
  "key": "uckk_standard_course",
  "name": "UCKK standard course",
  "component": "format_uckk",
  "description": "...",
  "defaults": {
    "format": "uckk",
    "numsections": 9,
    "visible": true,
    "lang": "fr",
    "courseformatoptions": {}
  },
  "sections": [],
  "activities": [],
  "completion": {},
  "metadata": {}
}
```

### Validation rules

- `key`, `name`, `component`, and `defaults` required.
- `component` must be `format_uckk` unless explicitly extended.
- `sections[].number` must be unique inside a template.
- `sections[].key` must be unique inside a template.
- `activities[]` must reference valid activity components when present.
- No `category` is required because templates are not courses.

---

## 6.10 `challenge_templates.json`

**Preset:** `challenge_templates`  
**Final handler:** `challenge_template_seed`  
**Owner:** `mod_uckkchallenge` template registry  
**Idempotency key:** `key`

Templates are not courses. They must not be validated by `course_seed`.

### Required item fields

```json
{
  "key": "internal_learning",
  "name": "Défi d’apprentissage interne",
  "component": "mod_uckkchallenge",
  "description": "...",
  "defaults": {
    "challenge_type": "internal_learning",
    "status": "draft",
    "visibility": "course",
    "provenance": "system",
    "validationstate": "unverified",
    "requires_human_validation": true,
    "allow_ai_assistance": true,
    "allow_ai_decision": false,
    "archive_required": true,
    "participant_roles": [],
    "evidence_requirements": [],
    "evaluation_criteria": [],
    "badges": [],
    "competencies": []
  },
  "sections": [],
  "activities": [],
  "completion": {},
  "metadata": {}
}
```

### Validation rules

- `key`, `name`, `component`, and `defaults` required.
- `component` must be `mod_uckkchallenge`.
- `defaults.badges[]` must resolve to a badge `key`, `id`, or `idnumber`.
- `defaults.competencies[]` must resolve to a competency `key`, `id`, or `idnumber`.
- `participant_roles[]`, when used as Moodle role references, must reference technical role shortnames.
- Symbolic roles may appear only in metadata or non-Moodle-facing narrative fields.

---

## 6.11 `assembly_templates.json`

**Preset:** `assembly_templates`  
**Final handler:** `assembly_template_seed`  
**Owner:** `mod_uckkassembly` template registry  
**Idempotency key:** `key`

Templates are not courses. They must not be validated by `course_seed`.

### Required item fields

```json
{
  "key": "assembly_savoirs",
  "name": "Assemblée des savoirs",
  "component": "mod_uckkassembly",
  "description": "...",
  "defaults": {
    "assemblytype": "savoirs",
    "status": "active",
    "visibility": "course",
    "allowmotions": true,
    "allowamendments": true,
    "allowvotes": true,
    "requireminutes": true,
    "requirearchive": true,
    "quorum": {},
    "threshold": {}
  },
  "sections": [],
  "activities": [],
  "completion": {},
  "metadata": {}
}
```

### Validation rules

- `key`, `name`, `component`, and `defaults` required.
- `component` must be `mod_uckkassembly`.
- `defaults.assemblytype` must map to `uckkassembly.assemblytype`.
- Decision, motion, vote, quorum, threshold, contestation, archive, and minutes defaults must remain module template defaults, not course fields.

---

## 6.12 `archive_templates.json`

**Preset:** `archive_templates`  
**Final handler:** `archive_template_seed`  
**Owner:** `mod_uckkarchive` template registry  
**Idempotency key:** `key`

Templates are not courses. They must not be validated by `course_seed`.

### Required item fields

```json
{
  "key": "uckk_archive_course_default",
  "name": "Archive UCKK — Cours standard",
  "component": "mod_uckkarchive",
  "description": "...",
  "defaults": {
    "module": "uckkarchive",
    "name": "Archive du cours",
    "intro": "...",
    "introformat": 1,
    "status": "active",
    "visibility": "course",
    "validationstate": "unverified",
    "archivepolicy": "course_memory",
    "visible": 1,
    "fileareas": [],
    "allowed_itemtypes": [],
    "allowed_prooftypes": [],
    "requires_human_validation": true
  },
  "sections": [],
  "activities": [],
  "completion": {},
  "metadata": {}
}
```

### Validation rules

- `key`, `name`, `component`, and `defaults` required.
- `component` must be `mod_uckkarchive`.
- `defaults.module` must be `uckkarchive`.
- `allowed_itemtypes[]` and `allowed_prooftypes[]` are validation vocabularies for archive item/proof workflows.
- Archive templates do not create `uckkarchive_item` rows; they define module/template defaults.

---

## 6.13 `courses.json`

**Preset:** `courses`  
**Handler:** `course_seed`  
**Moodle owner:** Moodle courses  
**Idempotency key:** `idnumber`; `shortname` uniqueness enforced.

### Required item fields

```json
{
  "key": "uckk_tc101",
  "id": "course:uckk-tc101",
  "code": "UCKK-TC101",
  "fullname": "Cartographie des idées avec l’IA",
  "shortname": "UCKK-TC101",
  "idnumber": "UCKK-TC101",
  "category": "UCKK-TC",
  "category_idnumber": "UCKK-TC",
  "format": "uckk",
  "template": "uckk_tronc_commun_course",
  "summary": "...",
  "summaryformat": 1,
  "visible": 0,
  "lang": "fr",
  "enablecompletion": true,
  "startdate": 0,
  "enddate": 0,
  "sections": [],
  "completion": {},
  "metadata": {},
  "sortorder": 10
}
```

### Validation rules

- `key`, `fullname`, `shortname`, `category`, `format`, and `idnumber` required.
- `category` must reference an existing `categories.idnumber` or a category key recognized by `category_seed`.
- `category_idnumber` may be retained, but `category` is the normalized seed field.
- `format` must be `uckk`; other formats should warn and be forced only by explicit handler policy.
- `shortname` must be unique.
- `idnumber` must be unique.
- `key` must be unique.
- `template`, when present, must reference `course_templates.key`.
- Canonical fields such as `learning_outcomes`, `assessment`, `ai_metadata`, and `source` may be preserved and stored as metadata or used by future course enrichment handlers.

---

## 6.14 `reports.json`

**Preset:** `reports`  
**Handler:** `report_seed`  
**Owner:** `report_uckk` definitions stored through `tool_uckkseed` config  
**Idempotency key:** `key`

### Required item fields

```json
{
  "key": "joueur_progress",
  "name": "Progression des joueurs",
  "shortname": "joueur_progress",
  "description": "...",
  "component": "report_uckk",
  "capability": "report/uckk:view",
  "source": "player_progress",
  "enabled": true,
  "sortorder": 10,
  "metadata": {}
}
```

### Allowed report capabilities

```text
report/uckk:view
report/uckk:viewall
report/uckk:export
```

### Validation rules

- `key`, `name`, `component`, `capability`, and `source` required.
- `component` should be `report_uckk` unless explicitly extended by `report_seed`.
- `capability` must be one of the allowed report capabilities.
- `source` must be a supported report source.
- Report definitions do not execute reports and do not bypass report permissions.

---

# 7. Capability registry

The following plugin files are authoritative for whether a UCKK capability exists:

```text
local/uckk/db/access.php
course/format/uckk/db/access.php
blocks/uckk_dashboard/db/access.php
mod/uckkchallenge/db/access.php
mod/uckkassembly/db/access.php
mod/uckkarchive/db/access.php
admin/tool/uckkseed/db/access.php
admin/tool/uckkintegrity/db/access.php
report/uckk/db/access.php
ai/provider/uckk/db/access.php
```

Current UCKK capabilities in the snapshot:

```text
local/uckk:viewcampus
local/uckk:manageprograms
local/uckk:managepathways
local/uckk:manageprofiles
local/uckk:managecanon
local/uckk:viewreports
local/uckk:exportdata
local/uckk:viewrestricted
local/uckk:manageintegrations
format/uckk:viewcoursemap
format/uckk:viewevidenceindicators
format/uckk:viewarchiveindicators
format/uckk:viewintegritymarkers
format/uckk:configuresections
format/uckk:manageblueprint
format/uckk:resetsectionnames
format/uckk:viewdiagnostics
block/uckk_dashboard:addinstance
block/uckk_dashboard:myaddinstance
block/uckk_dashboard:view
block/uckk_dashboard:viewothers
block/uckk_dashboard:configure
mod/uckkchallenge:addinstance
mod/uckkchallenge:view
mod/uckkchallenge:createchallenge
mod/uckkchallenge:submitproof
mod/uckkchallenge:evaluate
mod/uckkchallenge:validateintegrity
mod/uckkchallenge:archive
mod/uckkassembly:addinstance
mod/uckkassembly:view
mod/uckkassembly:createassembly
mod/uckkassembly:proposemotion
mod/uckkassembly:amendmotion
mod/uckkassembly:vote
mod/uckkassembly:publishdecision
mod/uckkassembly:contestdecision
mod/uckkassembly:archive
mod/uckkarchive:addinstance
mod/uckkarchive:view
mod/uckkarchive:additem
mod/uckkarchive:validateitem
mod/uckkarchive:reviseitem
mod/uckkarchive:viewrestricted
mod/uckkarchive:export
tool/uckkseed:seed
tool/uckkseed:reset
tool/uckkseed:validate
tool/uckkseed:exportpresets
tool/uckkintegrity:view
tool/uckkintegrity:opencase
tool/uckkintegrity:reviewcase
tool/uckkintegrity:assigncase
tool/uckkintegrity:issuecorrection
tool/uckkintegrity:invalidate
tool/uckkintegrity:closecase
tool/uckkintegrity:viewrestricted
report/uckk:view
report/uckk:viewall
report/uckk:export
aiprovider/uckk:configure
aiprovider/uckk:use
aiprovider/uckk:viewlogs
```

If `roles.json` or `capabilities.json` references a capability that is not declared in these files or in Moodle core, validation must fail.

After changing `db/access.php`, bump the corresponding plugin version and run Moodle upgrade.

---

# 8. Cross-preset dependencies

| Preset | Depends on |
|---|---|
| `categories` | none |
| `programs` | `categories` |
| `pathways` | `programs`, `courses`, `competencies`, `badges` |
| `cohorts` | `categories` for category-context cohorts |
| `roles` | plugin capabilities declared in `db/access.php` |
| `capabilities` | `roles`, plugin capabilities declared in `db/access.php` |
| `competencies` | Moodle core competency API |
| `badges` | `competencies`, `courses`, `programs`, `pathways` |
| `course_templates` | activity components when activities are declared |
| `challenge_templates` | `badges`, `competencies`, `roles` when referenced |
| `assembly_templates` | `mod_uckkassembly` |
| `archive_templates` | `mod_uckkarchive` |
| `courses` | `categories`, `course_templates` |
| `reports` | `report_uckk`, report capabilities |

---

# 9. Final implementation alignment requirements

To make this reference executable as the final version, `tool_uckkseed` must implement these alignments:

1. Add `PRESET_PROGRAMS = 'programs'` and route it to `program_seed`.
2. Add `PRESET_PATHWAYS = 'pathways'` and route it to `pathway_seed`.
3. Implement `program_seed` using `local_uckk_program`.
4. Implement `pathway_seed` using `local_uckk_pathway`.
5. Implement `capability_seed` and route `capabilities` to it instead of `role_seed`.
6. Implement `course_template_seed`, `challenge_template_seed`, `assembly_template_seed`, and `archive_template_seed`.
7. Stop routing template presets to `course_seed`.
8. Update `competency_seed` idnumber validation to accept `^UCKK-COMP-[A-Z0-9-]+$`.
9. Ensure `course_seed` accepts `category` as canonical and may derive it from `category_idnumber` during item normalization.
10. Ensure `category_seed` accepts `parent` as canonical and may derive it from `parent_idnumber` during item normalization.
11. Ensure `badge_seed` supports final program/pathway completion badge fields in addition to symbolic legacy badge fields.
12. Keep `reports.json` using top-level `capability`; do not move report capability exclusively into metadata.
13. Ensure generated export payloads use the runtime envelope from this reference.
14. Ensure `validate`, dry run, apply, reset, and export all use the same preset ids and handlers.

---

# 10. Validation checklist

Before committing any `academic_registry_json/*.json` change:

```text
[ ] Top-level envelope is schema/component/preset/version/items.
[ ] preset matches filename without .json.
[ ] items is an array.
[ ] Every item has the required stable key for its preset.
[ ] All idempotency keys are unique in the preset.
[ ] All cross-preset references resolve.
[ ] All capabilities exist in plugin db/access.php or Moodle core.
[ ] All Moodle category references resolve through categories.idnumber.
[ ] No symbolic UCKK identity is created as a Moodle role.
[ ] Program/pathway objects are seedable, not canon-only.
[ ] Templates are validated by template seeders, not course_seed.
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

This reference was generated from the repository snapshot dated `2026-05-19T11:04:28`, especially:

```text
admin/tool/uckkseed/classes/local/seeder.php
admin/tool/uckkseed/classes/local/category_seed.php
admin/tool/uckkseed/classes/local/course_seed.php
admin/tool/uckkseed/classes/local/cohort_seed.php
admin/tool/uckkseed/classes/local/role_seed.php
admin/tool/uckkseed/classes/local/competency_seed.php
admin/tool/uckkseed/classes/local/badge_seed.php
admin/tool/uckkseed/classes/local/report_seed.php
local/uckk/db/install.xml
local/uckk/classes/local/program.php
local/uckk/classes/local/pathway.php
all plugin db/access.php files listed above
academic_registry_json/*.json
docs/JSON preset format for UCKK academic registry.txt
```
