# 06 — Pedagogy, Courses, Competencies and Badges

**Status:** Implementation-corrected academic specification, standalone-first and Konnaxion-connected compatible  
**Purpose:** Define how UCKK pedagogy becomes Moodle courses, competencies, badges, portfolios, completion rules, seed data, optional Konnaxion/EkoH advisory alignment, privacy coverage, and testable implementation contracts.

## 0. Operating mode and Konnaxion alignment invariant

UCKK-Moodle is self-standing. The academic campus, course structure, competencies, badges, portfolios, completion rules, evidence validation, archive records, privacy handling, reports, and integrity decisions must work without Konnaxion.

Operating mode variables:

```text
OPERATING_MODE_STANDALONE = standalone_core
OPERATING_MODE_KONNAXION_CONNECTED = connected_konnaxion
KONNAXION_REQUIRED_FOR_CORE = false
SMART_VOTE_REQUIRED_FOR_CORE = false
KONNAXION_DEFAULT_STATE = disabled
SMART_VOTE_DEFAULT_STATE = disabled unless the Konnaxion bridge is enabled
```

When Konnaxion-connected mode is enabled, UCKK-Moodle may consume Konnaxion signals for pedagogical insight, collective intelligence, expertise weighting, and Smart Vote readings. These signals remain external advisory inputs. UCKK-Moodle remains the academic authority for courses, competencies, badges, portfolios, completion, archive records, privacy, reports, and integrity decisions.

Canonical rule:

```text
Konnaxion may inform learning recognition.
Konnaxion must not award UCKK recognition by itself.
UCKK-Moodle stores the academic decision and its evidence.
The Archive preserves the decision, the evidence, and any imported Konnaxion reading.
```

Konnaxion/EkoH/Smart Vote data are treated as external advisory signals in connected mode unless another UCKK document explicitly promotes a specific integration to a governed Moodle service with capability checks, privacy coverage, tests, and archive provenance.

When Konnaxion-connected mode is enabled, Konnaxion may provide:

```text
EkoH expertise signal
EkoH ethics signal
Smart Vote weighted reading
Smart Vote vote-result snapshot
Konnaxion portfolio/certification reference
Konnaxion analytical report reference
```

Konnaxion may not directly:

```text
complete a Moodle course
rate a Moodle competency
award a Moodle badge
validate UCKK evidence
publish an Assembly decision
close an integrity case
create or modify an archive item
replace Mentor, Inquisiteur, Archiviste, or Assembly authority
```

Standalone absence of Konnaxion data is valid. Missing Smart Vote/EkoH/Konnaxion records must not block course completion, competency rating, badge award, portfolio validation, reporting, or archive publication unless a specific connected-mode workflow explicitly requires them.

## 1. Academic structure

Final Moodle category tree:

```text
UCKK
├── 00_Accueil_et_orientation
├── 01_Tronc_commun_obligatoire
├── 02_Baccalaureat_Grand_Jeu_social
├── 03_Baccalaureat_Architecture_ecosysteme_digital_kOA
├── 04_Baccalaureat_Architecture_sociotechnique
├── 05_Baccalaureat_Sciences_politiques
├── 06_Baccalaureat_Economie
├── 07_Baccalaureat_Ecologie
├── 08_Baccalaureat_Metaphysique
├── 09_Baccalaureat_IA_gouvernable
├── 10_Baccalaureat_Linguistique_architecture_du_sens
├── 11_Baccalaureat_Intervention_sociale
├── 12_Mineure_Medias_vivants_theatre_public
├── 13_Seminaires_avances_laboratoires
├── 90_Defis_King_Klown
├── 91_Assemblees
├── 92_Archives_et_Portfolios
└── 99_Integrite_Inquisiteur
```

These categories are seeded Moodle course categories. They are not public accreditation claims. Labels such as `Baccalaureat` mean internal UCKK learning pathway unless formal external accreditation exists.

## 2. Seeded courses

### Orientation

```text
UCKK-000 — Entrer dans l’Univers-Cité King Klown
```

### Tronc commun

```text
UCKK-TC101 — Cartographie des idées avec l’IA
UCKK-TC102 — Intelligence collective, expertise située et décision légitime
UCKK-TC103 — Agitation institutionnelle et mesure de l’utilité réelle
UCKK-TC104 — Société des flux : argent, information et pouvoir
UCKK-TC105 — Fiction fondatrice, vérité morale et récits symboliques
UCKK-TC106 — Mobilisation multi-corridor et coopération pratique
UCKK-TC107 — Introduction à kOA : connaissance, décision, action, mémoire
UCKK-TC108 — Éthique, intégrité et Inquisiteur méthodologique
```

### Optional Konnaxion-connected course emphasis

Konnaxion concepts belong mostly in TC102, TC107, and TC108, with limited supporting use in TC101 and TC103. These concepts can be taught in standalone mode through examples, simulations, or static materials. Live Konnaxion/EkoH/Smart Vote signals are used only when Konnaxion-connected mode is enabled.

| Course | Konnaxion relevance | Required boundary |
|---|---|---|
| TC101 | AI-assisted mapping may compare human map, AI map, and external signal map. | AI and Konnaxion signals remain non-sovereign. |
| TC102 | Smart Vote, EkoH, expertise weighting, and legitimacy of collective decisions. | Weighted reading is not final institutional decision. |
| TC103 | External metrics may support utility analysis. | Metrics cannot replace evidence of real-world impact. |
| TC107 | kOA cycle can reference Konnaxion as operational infrastructure. | Moodle remains UCKK campus, not the whole kOA ecosystem. |
| TC108 | Integrity review must cover algorithmic weighting, privacy, contestability, and external authority capture. | Inquisiteur review and archive provenance remain Moodle-side. |

### Seed invariants

Each seeded course must have a stable:

```text
shortname
fullname
idnumber
category idnumber
course format
visible state
language-neutral key
preset ownership marker
external alignment marker, if connected-mode metadata exists
```

The seed tool must be idempotent. Re-running the seed must update UCKK-owned records without duplicating categories, courses, competencies, badges, activities, cohorts, dashboard placements, or external-alignment metadata.

## 3. UCKK course format

Every UCKK course uses `format_uckk` unless a standard Moodle format is explicitly justified.

Default section semantics:

| Section | Purpose | Typical Moodle components |
|---|---|---|
| Orientation | Course entry, rules, expectations | Page, book, label |
| Concepts | Key concepts and definitions | Page, glossary, book |
| Matière canonique | Core reading and source material | File, page, URL, book |
| Atelier | Active learning | Forum, workshop, assignment |
| Preuves | Evidence production | Assignment, database, challenge |
| Délibération | Collective interpretation | Forum, assembly |
| Livrable | Final deliverable | Assignment, challenge submission |
| Évaluation | Grading and feedback | Gradebook, rubric |
| Archive | Final memory | Archive activity |

`format_uckk` owns course layout, section naming, section metadata, and course-format diagnostics only. It must not create seed data, badges, competencies, archive records, challenge submissions, assembly decisions, integrity cases, Konnaxion mappings, Smart Vote readings, or EkoH-derived recognition.

## 4. Completion design

Each course must have:

```text
course completion enabled
activity completion enabled
completion criteria mapped to evidence
manual override allowed for Mentor and Gestionnaire
archive export on completion when required
```

Required completion categories:

```text
viewed orientation
completed concept checks
submitted at least one evidence item
participated in deliberation
submitted final deliverable
received evaluation
archived final artifact
```

Completion must be evidence-aware. A completion tick alone is not sufficient for badges, competency proof, or portfolio recognition unless it links to a validated evidence item or a validated archive item.

Konnaxion/EkoH/Smart Vote data must not directly mark a Moodle course complete. In Konnaxion-connected mode, imported external signals may appear as evidence references only when:

```text
source system is identified
Konnaxion target or object mapping is recorded
import timestamp is recorded
privacy classification is recorded
Mentor or authorized service validates relevance
archive or portfolio reference is created where required
```

## 5. Competency framework

Framework name:

```text
UCKK — Compétences fondamentales
```

Core competencies:

```text
UCKK-COMP-001 — Lire le Grand Jeu social
UCKK-COMP-002 — Cartographier un système
UCKK-COMP-003 — Distinguer fait, hypothèse, interprétation, récit et décision
UCKK-COMP-004 — Utiliser l’IA comme outil non souverain
UCKK-COMP-005 — Produire une preuve vérifiable
UCKK-COMP-006 — Participer à une assemblée structurée
UCKK-COMP-007 — Concevoir une mobilisation responsable
UCKK-COMP-008 — Documenter une décision
UCKK-COMP-009 — Archiver un apprentissage
UCKK-COMP-010 — Appliquer l’éthique UCKK
UCKK-COMP-011 — Détecter l’autorité cachée
UCKK-COMP-012 — Construire un artefact utile
UCKK-COMP-013 — Assurer la contestabilité
UCKK-COMP-014 — Relier connaissance, décision, action et mémoire
UCKK-COMP-015 — Interpréter une lecture Smart Vote sans la confondre avec une décision
UCKK-COMP-016 — Évaluer les limites d’un score d’expertise ou de réputation
```

COMP-015 and COMP-016 are added for Konnaxion alignment. They are academic competencies about interpreting collective-intelligence signals; they are not permissions to operate Konnaxion or publish decisions.

## 6. Competency ratings

Use a stable scale:

```text
0 — Not observed
1 — Attempted
2 — Demonstrated with support
3 — Demonstrated independently
4 — Demonstrated with public-quality evidence
5 — Demonstrated and archived as reusable Kristal
```

Ratings 3–5 require a recorded evaluator, context, date, evidence reference, and source activity. Ratings 4–5 require archive or portfolio linkage.

### 6.1 External signal rule

Standalone competency ratings must work without external Konnaxion/EkoH/Smart Vote data.

In Konnaxion-connected mode, Konnaxion/EkoH/Smart Vote signals may support a competency-rating discussion but must not write the competency rating directly.

Allowed connected-mode use:

```text
Mentor views Konnaxion/EkoH signal as supporting context.
Assembly views Smart Vote reading as one labeled reading among others.
Joueur includes external Konnaxion artifact in portfolio with provenance.
Report displays imported external signal beside UCKK evidence, with clear label.
```

Forbidden use in every mode:

```text
EkoH score automatically sets competency rating.
Smart Vote result automatically validates competency.
Konnaxion certification automatically awards UCKK badge.
External analytics automatically closes UCKK evidence review.
```

### 6.2 Optional Konnaxion domain mapping

Standalone UCKK competency records must remain valid without Konnaxion domain mappings.

In Konnaxion-connected mode, seed presets may map UCKK competencies to Konnaxion expertise categories for display, reporting, and advisory comparison.

| UCKK competency | Suggested Konnaxion domain mapping | Use |
|---|---|---|
| COMP-001 | social_systems, civic_analysis | Advisory comparison only |
| COMP-002 | systems_mapping, knowledge_architecture | Advisory comparison only |
| COMP-003 | epistemic_integrity, media_literacy | Advisory comparison only |
| COMP-004 | governable_ai, ai_literacy | Advisory comparison only |
| COMP-005 | evidence_quality, research_methods | Advisory comparison only |
| COMP-006 | collective_intelligence, civic_deliberation | Advisory comparison only |
| COMP-007 | mobilisation, project_coordination | Advisory comparison only |
| COMP-008 | decision_documentation, governance | Advisory comparison only |
| COMP-009 | archival_practice, memory_systems | Advisory comparison only |
| COMP-010 | ethics, procedural_justice | Advisory comparison only |
| COMP-011 | authority_analysis, institutional_integrity | Advisory comparison only |
| COMP-012 | prototyping, practical_impact | Advisory comparison only |
| COMP-013 | contestability, appeal_process | Advisory comparison only |
| COMP-014 | knowledge_decision_action_memory | Advisory comparison only |
| COMP-015 | smart_vote_literacy, consensus_analysis | Advisory comparison only |
| COMP-016 | reputation_systems, expertise_weighting | Advisory comparison only |

The mapping must be versioned when used. A Konnaxion category rename must not silently change a historical UCKK competency record. Absence of a Konnaxion mapping is not an academic data error in standalone mode.

## 7. Competency mapping by tronc commun course

| Course | Primary competencies |
|---|---|
| TC101 | COMP-002, COMP-003, COMP-004, COMP-005 |
| TC102 | COMP-006, COMP-008, COMP-013, COMP-015, COMP-016 |
| TC103 | COMP-001, COMP-005, COMP-011, COMP-012 |
| TC104 | COMP-001, COMP-002, COMP-011 |
| TC105 | COMP-003, COMP-010, COMP-013 |
| TC106 | COMP-006, COMP-007, COMP-010 |
| TC107 | COMP-014, COMP-001, COMP-008, COMP-016 |
| TC108 | COMP-010, COMP-013, COMP-005, COMP-011, COMP-016 |

Each mapping must be represented in seed presets and validated by tests. Missing competency IDs, duplicated IDs, or references to unseeded competencies are blockers.

## 8. Badge system

### Foundational badges

```text
Badge — Joueur initié
Badge — Joueur lucide
Badge — Cartographe de systèmes
Badge — Gardien de la preuve
Badge — Participant d’Assemblée
Badge — Lecteur Smart Vote responsable
Badge — Gardien de l’expertise pondérée
Badge — Bâtisseur de prototype
Badge — Archiviste de décision
Badge — Défi King Klown validé
Badge — Inquisition méthodologique réussie
```

### Program badges

```text
Badge — Grand Jeu social
Badge — Architecture écosystème digital kOA
Badge — Architecture sociotechnique
Badge — Sciences politiques
Badge — Économie
Badge — Écologie
Badge — Métaphysique
Badge — IA gouvernable
Badge — Linguistique et architecture du sens
Badge — Intervention sociale
Badge — Médias vivants et théâtre public responsable
```

### Badge implementation contract

Each badge preset must define:

```text
key
name_en
name_fr
description_en
description_fr
issuer identity
criteria summary
competency requirements
evidence requirements
archive requirements
integrity requirements
external signal policy
Konnaxion/EkoH/Smart Vote relevance, if connected-mode metadata exists
expiry policy, if any
visibility
```

Badge keys must be stable and machine-readable. Display names may change; keys must not change after release without migration support.

## 9. Badge award rules

Every badge must require:

```text
[ ] course/activity completion;
[ ] evidence submission;
[ ] human validation;
[ ] competency threshold;
[ ] archive item or portfolio entry;
[ ] no unresolved integrity block.
```

No badge may be awarded solely because a user viewed content.

No badge may be awarded solely because a user has:

```text
high EkoH expertise score
high EkoH ethics score
favorable Smart Vote result
Konnaxion certification reference
Konnaxion portfolio item
Konnaxion leaderboard status
Konnaxion analytics signal
```

Automated badge award logic is permitted only when all required evidence, competency ratings, archive links, and integrity checks are already recorded in UCKK-Moodle. Human validation remains required for badges that claim judgment, public-quality evidence, integrity success, assembly participation, Smart Vote interpretation, EkoH interpretation, or reusable Kristal status.

### 9.1 Konnaxion-specific badges

The following badges may exist only as UCKK educational recognition, not as Konnaxion authority transfer. They may be awarded in standalone mode using UCKK evidence, simulations, or reflective work; live Konnaxion data is optional and never sufficient by itself:

| Badge | Required evidence |
|---|---|
| Lecteur Smart Vote responsable | Portfolio reflection explaining raw vote, weighted reading, decision, minority report, and contestability. |
| Gardien de l’expertise pondérée | Evidence that the Joueur can explain EkoH-style weighting, ethics multiplier risk, privacy concerns, and authority-capture risk. |

These badges must require Mentor validation and archive/portfolio linkage.

## 10. Portfolio of Joueur lucide

The portfolio is the central evidence object for tronc commun.

Required portfolio sections:

```text
System map
Flow analysis
AI collaboration journal
Assembly participation record
Smart Vote / weighted-reading reflection
EkoH / expertise-weighting reflection
Institutional utility audit
Symbolic narrative analysis
Mobilisation strategy
Ethics reflection
Final synthesis
Archive links
```

The portfolio must aggregate references; it must not silently copy or fork evidence without provenance. Portfolio entries must retain links to source course, source activity, author, timestamp, evaluator, archive state, and integrity state.

When Konnaxion-connected mode adds external Konnaxion references, those references must additionally retain:

```text
external system name
external module name
external object type
external object id or stable mapping id
import timestamp
retrieval actor or scheduled task id
visibility classification
privacy classification
hash of imported snapshot where applicable
source URL or API reference where permitted
```

Portfolio display must label external signals clearly:

```text
External advisory signal. Not a UCKK competency rating, badge award, or final decision.
```

French:

```text
Signal consultatif externe. Ce n’est pas une note de compétence UCKK, une attribution de badge ou une décision finale.
```

## 11. Seed data requirements

`tool_uckkseed` must create the standalone academic campus without requiring Konnaxion.

Core standalone seed requirements:

```text
[ ] category tree
[ ] UCKK-000
[ ] TC101–TC108
[ ] course templates
[ ] competency framework
[ ] badges
[ ] initial cohorts
[ ] dashboard block placement
[ ] default archive activity
[ ] default assembly activity where relevant
[ ] default challenge activity where relevant
```

Optional Konnaxion-connected seed requirements:

```text
[ ] Konnaxion-aware competency domain mappings
[ ] external signal policies for badges and competencies
[ ] connected-mode report labels for Smart Vote/EkoH advisory signals
[ ] connected-mode dashboard labels for external advisory signals
```

Connected-mode seed records must never become prerequisites for standalone course creation, competency creation, badge creation, or portfolio creation.

### 11.1 Canonical preset path rule

The distribution-level source of truth is:

```text
presets/*.json
```

The plugin-local runtime copy is:

```text
admin/tool/uckkseed/presets/*.json
```

These are not competing sources. The packaging pipeline copies or symlinks distribution presets into the plugin-local preset directory for Moodle installation. If both locations exist, the build must verify byte-for-byte or hash-equivalent consistency before release.

Core academic seed presets:

```text
presets/categories.json
presets/courses.json
presets/course_templates.json
presets/competencies.json
presets/badges.json
presets/cohorts.json
presets/roles.json
presets/capabilities.json
```

Optional Konnaxion-connected academic presets:

```text
presets/konnaxion_domain_map.json
presets/external_signal_policies.json
```

Plugin-local packaged core copies:

```text
admin/tool/uckkseed/presets/categories.json
admin/tool/uckkseed/presets/courses.json
admin/tool/uckkseed/presets/course_templates.json
admin/tool/uckkseed/presets/competencies.json
admin/tool/uckkseed/presets/badges.json
admin/tool/uckkseed/presets/cohorts.json
admin/tool/uckkseed/presets/roles.json
admin/tool/uckkseed/presets/capabilities.json
```

Plugin-local packaged optional connected-mode copies:

```text
admin/tool/uckkseed/presets/konnaxion_domain_map.json
admin/tool/uckkseed/presets/external_signal_policies.json
```

The seed tool must validate core cross-file references before writing records:

```text
course category exists
course competencies exist
course badges exist
badge competency requirements exist
role shortnames exist
capabilities reference known roles
report shortnames are unique
seed ownership markers are present
```

When optional Konnaxion-connected presets are present or connected mode is enabled, the seed tool must also validate:

```text
Konnaxion domain mappings reference known UCKK competency ids
external signal policies reference known badges or competencies
external signal policies declare advisory/non-authoritative status
connected-mode presets do not make Konnaxion required for standalone operation
```

Missing optional Konnaxion-connected presets must not block standalone seed validation.

## 12. Moodle implementation ownership

| Concern | Owning component | Notes |
|---|---|---|
| Campus/category/course seeding | `tool_uckkseed` | Idempotent creation and update of canonical UCKK records |
| Course section semantics | `format_uckk` | Layout, section metadata, diagnostics |
| Programs, pathways, profiles | `local_uckk` | Cross-campus academic logic and dashboard source data |
| Optional Konnaxion identity/object/domain mapping | `local_uckk` | Connected-mode external advisory bridge and mapping registry only |
| Optional Konnaxion academic signal policy | `local_uckk` | Defines how EkoH/Smart Vote signals may be displayed or considered when enabled |
| Challenges and proofs | `mod_uckkchallenge` | Evidence-producing activity |
| Assemblies and decisions | `mod_uckkassembly` | Deliberation and governance decisions; Smart Vote reading display belongs here only when connected mode is enabled |
| Archives and portfolios | `mod_uckkarchive` plus `local_uckk` | Evidence memory, provenance, export, validation |
| Integrity review | `tool_uckkintegrity` | Blocks, appeals, reviews, decisions |
| Dashboard display | `block_uckk_dashboard` | Summary surface only; no authority bypass |
| Reports | `report_uckk` | Read-only reporting with permission filtering |
| AI assistance | `aiprovider_uckk` | Non-authoritative assistance only |

No component may bypass the owning component’s permissions or persistence rules. Dashboard, report, AI, and Konnaxion bridge features must read filtered data; they must not become alternate write paths. Standalone mode must not call Konnaxion services, require Konnaxion mappings, or treat absent external signals as data corruption.

## 13. Required class layer

This specification is not implementation-complete unless the required class layer exists.

Core standalone required classes:

```text
local/uckk/classes/api/program_api.php
local/uckk/classes/api/pathway_api.php
local/uckk/classes/api/competency_api.php
local/uckk/classes/local/academic_signal_policy.php
local/uckk/classes/privacy/provider.php

admin/tool/uckkseed/classes/local/validator.php
admin/tool/uckkseed/classes/local/seeder.php
admin/tool/uckkseed/classes/local/preset_consistency_validator.php
admin/tool/uckkseed/classes/privacy/provider.php

course/format/uckk/classes/privacy/provider.php

mod/uckkchallenge/classes/form/*.php
mod/uckkchallenge/classes/event/*.php
mod/uckkchallenge/classes/external/*.php
mod/uckkchallenge/classes/privacy/provider.php

mod/uckkassembly/classes/form/*.php
mod/uckkassembly/classes/event/*.php
mod/uckkassembly/classes/external/*.php
mod/uckkassembly/classes/privacy/provider.php

mod/uckkarchive/classes/form/*.php
mod/uckkarchive/classes/event/*.php
mod/uckkarchive/classes/external/*.php
mod/uckkarchive/classes/privacy/provider.php

admin/tool/uckkintegrity/classes/form/*.php
admin/tool/uckkintegrity/classes/event/*.php
admin/tool/uckkintegrity/classes/external/*.php
admin/tool/uckkintegrity/classes/local/external_authority_capture_policy.php
admin/tool/uckkintegrity/classes/privacy/provider.php

block/uckk_dashboard/classes/output/*.php
block/uckk_dashboard/classes/privacy/provider.php

report/uckk/classes/privacy/provider.php

ai/provider/uckk/classes/privacy/provider.php
```

Optional Konnaxion-connected required classes when connected mode is enabled:

```text
local/uckk/classes/local/konnaxion_domain_mapper.php
mod/uckkassembly/classes/local/smartvote_reading.php
mod/uckkarchive/classes/local/external_signal_snapshot.php
report/uckk/classes/table/external_signal_report_table.php
```

Any page, service, scheduled task, event observer, test, or AMD module that references a namespaced class must have a matching Moodle autoloadable class file.

The broader Konnaxion bridge client classes belong to the integration specification. This pedagogy document requires only the academic policy, mapping, portfolio, archive, and reporting classes needed to prevent external scores from becoming hidden badge or competency authority. Connected-mode classes must not be loaded or required in standalone workflows unless a feature explicitly checks that connected mode is enabled.

## 14. Privacy and evidence contract

The following core UCKK data are personal or potentially personal and require privacy coverage in standalone mode:

```text
course progress
activity completion
competency ratings
badge awards
portfolio entries
evidence submissions
archive items
assembly participation
challenge attempts
integrity cases
AI interaction logs
reports containing identifiable progress
```

The following connected-mode data are personal or potentially personal and require privacy coverage when Konnaxion-connected mode is enabled:

```text
Konnaxion identity mappings
Konnaxion object mappings
EkoH expertise or ethics signal snapshots
Smart Vote result snapshots
external signal policy decisions linked to a user
```

Every owning plugin must declare what it stores, how it exports user data, and how it deletes or anonymizes user data where Moodle privacy APIs require it.

Archive records that must be preserved for institutional accountability may be retained only under explicit retention rules, with user-visible policy language and restricted access.

External Konnaxion data must be minimized in connected mode:

```text
store mapping ids instead of raw external identifiers where possible
store snapshots only when needed for evidence, archive, appeal, or report reproducibility
hash imported payloads when full payload retention is not required
redact raw voter identity unless explicitly permitted by policy
separate advisory signal display from UCKK academic decision fields
```

Standalone privacy export must not fail because no Konnaxion mapping, EkoH signal, or Smart Vote snapshot exists.

## 15. Test and validation gates

This document is implemented only when the following core standalone checks pass:

```text
[ ] PHP syntax lint passes for all .php files.
[ ] JavaScript AMD source contains only JavaScript.
[ ] No Markdown fences are present inside .php files.
[ ] Every version.php component matches its plugin path.
[ ] Moodle install/upgrade completes without warnings in a disposable site.
[ ] Seed validation passes before writes with Konnaxion disabled.
[ ] Seed apply mode creates UCKK categories and courses.
[ ] Seed apply mode is idempotent.
[ ] Distribution presets and plugin-local presets are hash-equivalent for core academic presets.
[ ] Course format tests confirm canonical section names.
[ ] PHPUnit confirms competency and badge reference integrity.
[ ] PHPUnit confirms COMP-015 and COMP-016 seed correctly as literacy competencies.
[ ] PHPUnit confirms external signal policies cannot auto-rate competencies.
[ ] PHPUnit confirms external signal policies cannot auto-award badges.
[ ] PHPUnit confirms service-layer capability checks.
[ ] Behat confirms UCKK-000 and TC101–TC108 are visible to expected users.
[ ] Behat confirms evidence is required before badge award.
[ ] Behat confirms dashboard progress does not reveal restricted data.
[ ] Privacy provider tests pass for all personal-data plugins.
[ ] Standalone workflows pass without Konnaxion mappings, EkoH signals, or Smart Vote snapshots.
```

When Konnaxion-connected mode is enabled or optional connected presets are packaged, the following additional checks must pass:

```text
[ ] PHPUnit confirms Konnaxion domain mappings reference existing UCKK competencies.
[ ] PHPUnit confirms optional connected-mode presets do not block standalone seed validation.
[ ] PHPUnit confirms privacy export/delete coverage for imported external signal snapshots.
[ ] Behat confirms Smart Vote/EkoH signals display as advisory where enabled.
[ ] Behat confirms connected-mode labels clearly distinguish external advisory signals from UCKK academic decisions.
[ ] Connected-mode reports show Konnaxion advisory signals only with permission filtering and clear labels.
```

## 16. Definition of done

Core standalone definition of done:

```text
[ ] A new Joueur can enter UCKK-000 and see the complete path.
[ ] TC101–TC108 are created with full section structure.
[ ] Competencies are linked to activities and evidence.
[ ] COMP-015 and COMP-016 exist for Smart Vote and expertise-weighting literacy.
[ ] Badges require evidence and validation.
[ ] No Konnaxion/EkoH/Smart Vote signal can auto-complete courses, auto-rate competencies, or auto-award badges.
[ ] Portfolio can aggregate archive items and labeled external advisory references when such references exist.
[ ] Program pathways are visible in dashboard.
[ ] Reports show progress by course, cohort, competency, and badge.
[ ] The seed tool can validate, dry-run, apply, export, and reset core academic presets with Konnaxion disabled.
[ ] Every academic object has a stable ID, owner component, privacy status, and external-signal policy where applicable.
[ ] Every implementation file respects its Moodle file type: PHP in .php, JS in amd/src/*.js, templates in .mustache.
[ ] Absence of Konnaxion mappings, EkoH signals, and Smart Vote snapshots does not block installation, seeding, teaching, completion, competency rating, badge award, portfolio validation, reporting, archive publication, or privacy export.
```

Optional Konnaxion-connected definition of done:

```text
[ ] The seed tool validates Konnaxion domain maps and external signal policies when connected-mode presets are present.
[ ] Konnaxion/EkoH/Smart Vote signals display as advisory where enabled.
[ ] Reports may show Konnaxion advisory signals only with permission filtering and clear labels.
[ ] Connected-mode portfolio references preserve source system, object id or mapping id, import timestamp, privacy classification, and provenance hash where applicable.
[ ] Connected-mode evidence, report, archive, and privacy tests prove that external signals remain advisory and non-sovereign.
```
