# 01 — Domain Boundaries and Glossary

**Status:** Final domain specification, standalone/Konnaxion-aligned  
**Purpose:** Prevent conceptual confusion during design, development, UI writing, seeding, permissions, reporting, documentation, connected integrations, and implementation review.

## 1. Boundary model

UCKK-Moodle must preserve a strict domain separation:

| Domain | Meaning | Moodle implementation |
|---|---|---|
| kOA | Movement, culture, method, social vision | Referenced, taught, integrated through selected APIs |
| UCKK | School / learning city | Main campus implemented in Moodle |
| UCKK-Moodle | Moodle campus implementation of UCKK | Self-standing Moodle distribution; must install and operate without Konnaxion |
| kOA Digital Ecosystem | Operational digital infrastructure | External or future integration target, not collapsed into Moodle |
| Konnaxion | External collective-intelligence platform | Optional connected-mode integration; provides Smart Vote/EkoH readings and better cross-module organization when enabled |
| EkoH | External expertise, ethics, reputation, or weighting signal from Konnaxion | Advisory signal only where explicitly mapped; never Moodle authority by itself |
| Smart Vote | Konnaxion weighted reading system | Optional Assembly reading in connected mode; never final Moodle decision |
| King Klown | Narrative figure | Branding, challenges, symbolic pedagogy |
| Inquisiteur | Ethical and methodological guardrail | Integrity system and restricted capabilities |
| Assemblées | Collective legitimacy | Structured assembly activity and decision records |
| Archives | Memory | Archive activity, evidence records, provenance, reports |

Core boundary invariant:

```text
UCKK-Moodle is self-standing.
Konnaxion is optional connected intelligence.
Smart Vote may inform Assemblées but never replaces them.
```

## 2. Naming rules

### 2.1 Site name

```text
Univers-Cité King Klown — Moodle Campus
```

Short name:

```text
UCKK-Moodle
```

Public description:

```text
UCKK-Moodle is the Moodle-based campus for the Univers-Cité King Klown, an experimental learning city and internal recognition environment.
```

### 2.2 Forbidden wording

Do not use these labels in public or student-facing UI unless formal accreditation exists:

```text
official university degree
state-accredited bachelor's degree
public diploma equivalent
recognized university credit
```

Use instead:

```text
internal recognition
UCKK pathway
UCKK badge
experimental learning program
portfolio-based certification
internal baccalauréat
```

### 2.3 Component naming rule

Moodle component names must match their physical plugin paths exactly.

| Path | Component |
|---|---|
| `local/uckk` | `local_uckk` |
| `course/format/uckk` | `format_uckk` |
| `theme/uckk` | `theme_uckk` |
| `blocks/uckk_dashboard` | `block_uckk_dashboard` |
| `mod/uckkchallenge` | `mod_uckkchallenge` |
| `mod/uckkassembly` | `mod_uckkassembly` |
| `mod/uckkarchive` | `mod_uckkarchive` |
| `admin/tool/uckkseed` | `tool_uckkseed` |
| `admin/tool/uckkintegrity` | `tool_uckkintegrity` |
| `report/uckk` | `report_uckk` |
| `ai/provider/uckk` | `aiprovider_uckk` |

A plugin is not installable until `version.php`, language strings, capabilities, service declarations, tests, and namespaces use the same component name consistently.

### 2.4 Operating-mode naming rule

Use these operating-mode names consistently:

| Name | Meaning | Required behavior |
|---|---|---|
| Standalone mode | UCKK-Moodle running without Konnaxion | Must install, seed, teach, deliberate, archive, report, and enforce permissions without Konnaxion |
| Konnaxion-connected mode | UCKK-Moodle with Konnaxion bridge enabled | May add Smart Vote readings, EkoH/advisory signals, external mappings, sync logs, and better cross-module organization |

Forbidden wording:

```text
Konnaxion is required for UCKK-Moodle.
Smart Vote is required for Assemblées.
UCKK-Moodle is incomplete without Konnaxion.
Konnaxion decides Moodle outcomes.
Smart Vote publishes final Assembly decisions.
```

Use instead:

```text
Konnaxion is an optional connected-mode integration.
Smart Vote is an optional connected-mode reading.
UCKK-Moodle remains functional in standalone mode.
Assemblées decide; Smart Vote may inform.
Archives preserve both readings and decisions when connected mode is used.
```

## 3. Glossary for implementation

| Term | Implementation meaning |
|---|---|
| Joueur | Moodle user enrolled as learner; also a UCKK profile state |
| Joueur lucide | Badge/status earned through evidence |
| Mentor | Teacher role with course and challenge evaluation capabilities |
| Bâtisseur | Badge/profile distinction, not a default Moodle system role |
| Archiviste | Restricted role/capability set for archive validation |
| Inquisiteur | Restricted integrity reviewer, not global administrator |
| Défi | Dedicated activity instance: `mod_uckkchallenge` |
| Assemblée | Dedicated activity instance: `mod_uckkassembly` |
| Archive | Dedicated activity instance: `mod_uckkarchive` plus cross-plugin evidence store |
| Kristal pédagogique | Structured knowledge item stored in archive, glossary, or local UCKK registry |
| Preuve | File, text, link, observation, decision, grade, or archive item supporting competence |
| Portfolio | Aggregated evidence map attached to a Joueur |
| Tronc commun | Mandatory course set seeded by `tool_uckkseed` |
| Baccalauréat UCKK | Internal pathway composed of Moodle categories/courses/competencies/badges |
| kOA cycle | Connaître → Choisir → Agir → Se souvenir |
| Standalone mode | Core UCKK-Moodle operating mode with Konnaxion disabled or absent |
| Konnaxion-connected mode | Optional operating mode where UCKK-Moodle connects to Konnaxion through Moodle-controlled settings, services, capabilities, logs, privacy coverage, and tests |
| Konnaxion bridge | Moodle-side integration layer owned by `local_uckk` when connected mode is enabled |
| Konnaxion mapping | Moodle-side mapping between a Moodle object/user/context and a Konnaxion object/user/context; never a permission grant by itself |
| EkoH signal | External advisory expertise, ethics, reputation, or weighting signal from Konnaxion; never final Moodle authority |
| Smart Vote reading | Optional weighted or computed reading from Konnaxion, displayed inside an Assembly workflow as decision support only |
| Smart Vote snapshot | Moodle-side preserved copy or reference of a Smart Vote reading with method, provenance, visibility, contestation status, and archive linkage where applicable |
| Assembly decision | Human/institutional Moodle-side decision published through `mod_uckkassembly` with capability checks and archive provenance |
| Page controller | PHP entry point that loads Moodle config, checks login, resolves context, checks capabilities, processes input, and renders a page |
| AMD module | JavaScript-only UI module under `amd/src`; it must not contain PHP page-controller code |
| Service class | Namespaced class under `classes/external`, `classes/local`, or another appropriate `classes/` namespace that contains reusable business logic |
| Privacy provider | `classes/privacy/provider.php` implementation declaring, exporting, and deleting personal data where required |
| Acceptance gate | A documented pass/fail check before install, testing, or release |
| Core acceptance gate | Pass/fail check for standalone UCKK-Moodle |
| Connected acceptance gate | Pass/fail check for optional Konnaxion-connected features |

## 4. Domain rules

### Rule D1 — Moodle is the campus, not the movement

Moodle implements the UCKK campus. It can teach and connect to kOA, but it must not pretend to be the whole kOA movement.

### Rule D2 — UCKK is an educational institution, not the whole infrastructure

UCKK can use, teach, document, and partly integrate the kOA Digital Ecosystem. Moodle must not absorb all kOA infrastructure concepts into LMS objects unless there is a direct pedagogical or governance purpose.

### Rule D3 — King Klown is narrative power, not institutional sovereignty

King Klown can appear in theme, challenge names, visual identity, onboarding, and public-facing prompts. King Klown must not override the Inquisiteur, Assemblées, evidence, or procedural rules.

### Rule D4 — The Inquisiteur is a guardrail, not arbitrary power

The Inquisiteur validates, contests, pauses, corrects, and documents. The role must be permission-limited, auditable, and contestable.

### Rule D5 — Assemblées produce documented decisions

An Assembly is not a chat room. It has motions, arguments, objections, amendments, votes/readings, decisions, minutes, and archive records.

### Rule D6 — Archives preserve memory with provenance

Archive items must include author, context, source, date, version, visibility, evidence relation, and integrity state.

### Rule D7 — AI is non-sovereign

AI features can clarify, summarize, map, draft, and detect uncertainty. AI outputs are never final decisions, final grades, final integrity judgments, or final evidence validation.

### Rule D8 — Symbolic language never overrides Moodle security

Symbolic titles, narrative labels, and UCKK statuses must never be treated as implicit Moodle authority. Permissions must be represented through explicit Moodle capabilities, contexts, role assignments, and service checks.

### Rule D9 — Konnaxion is optional connected intelligence, not the Moodle campus

UCKK-Moodle must install, seed, teach, deliberate, archive, report, and enforce permissions without Konnaxion.

When Konnaxion-connected mode is enabled, Konnaxion may provide Smart Vote readings, EkoH/advisory signals, external mappings, analytics, and cross-module organization. These signals remain external or imported readings inside Moodle. They must not become enrolment authority, role authority, capability authority, academic authority, archive authority, integrity authority, or final Assembly authority.

Canonical Smart Vote rule:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

### Rule D10 — External systems never write Moodle authority directly

External systems, including Konnaxion, must not write directly into Moodle source tables or bypass Moodle services, permissions, privacy providers, events, logs, archive provenance, or integrity review.

Moodle-side integration records must be created through documented Moodle services, capability checks, state machines, and privacy-aware storage.

## 5. Object ownership map

| Object | Owner plugin | Notes |
|---|---|---|
| UCKK program | `local_uckk` | Canonical registry |
| UCKK pathway | `local_uckk` | Links courses, competencies, badges |
| UCKK course section layout | `format_uckk` | Course structure |
| Challenge | `mod_uckkchallenge` | Full activity workflow |
| Assembly | `mod_uckkassembly` | Full deliberation workflow |
| Archive item | `mod_uckkarchive` | Evidence, decisions, Kristals |
| Integrity case | `tool_uckkintegrity` | Cross-plugin case handling |
| Dashboard view | `block_uckk_dashboard` | User-facing summary |
| Reports | `report_uckk` | Institutional visibility |
| Seed data | `tool_uckkseed` | Installation and idempotent provisioning |
| AI provider | `aiprovider_uckk` | External AI bridge |
| Visual identity | `theme_uckk` | No business logic |
| Konnaxion bridge settings | `local_uckk` | Optional connected-mode settings and service configuration; disabled by default |
| Konnaxion identity/object mappings | `local_uckk` | Optional connected-mode mapping records; never grant Moodle authority by themselves |
| Smart Vote target mapping | `mod_uckkassembly` | Optional connected-mode Assembly-to-Konnaxion target linkage |
| Smart Vote reading snapshot | `mod_uckkassembly` | Optional connected-mode reading record; never final decision |
| Smart Vote archived memory | `mod_uckkarchive` | Preserves connected-mode readings and final Assembly decisions separately with provenance |
| Smart Vote integrity review | `tool_uckkintegrity` | Reviews contested, anomalous, invalidated, privacy-sensitive, or superseded readings |
| Smart Vote reports | `report_uckk` | Displays permission-filtered connected-mode readings and exports where enabled |

## 6. Implementation boundary rules

These rules prevent conceptual boundaries from becoming code errors.

### Rule I1 — File type boundaries are strict

```text
[ ] PHP page controllers live in .php files and start with <?php.
[ ] AMD modules live in amd/src/*.js and contain JavaScript only.
[ ] Mustache templates contain presentation markup only.
[ ] JSON preset files contain data only.
[ ] Documentation comments may explain code, but Markdown fences must not appear inside executable PHP files.
```

### Rule I2 — Page controllers and reusable logic are separate

A page controller may coordinate the request, but reusable UCKK behavior must move into namespaced classes.

```text
[ ] Page controllers load config.php through the correct relative path.
[ ] Page controllers call require_login().
[ ] Page controllers resolve the correct Moodle context.
[ ] Page controllers call require_capability() before privileged actions.
[ ] Business rules live in classes/local, classes/form, classes/external, classes/event, classes/output, or classes/task as appropriate.
```

### Rule I3 — External services must have real classes

Every function declared in `db/services.php` must have a matching implementation class under the component namespace.

```text
[ ] local_uckk\external\*
[ ] mod_uckkchallenge\external\*
[ ] mod_uckkassembly\external\*
[ ] mod_uckkarchive\external\*
[ ] tool_uckkintegrity\external\*
[ ] report_uckk\external\* when report services are exposed
[ ] block_uckk_dashboard\external\* when dashboard AJAX services are exposed
```

Declared services without matching classes are documentation promises, not working Moodle functionality.

### Rule I4 — Privacy coverage follows data ownership

Every plugin that stores or exposes personal data must implement privacy metadata, export, and deletion behavior.

```text
[ ] local_uckk profile/pathway data
[ ] mod_uckkchallenge submissions, evidence, evaluations, and challenge state
[ ] mod_uckkassembly motions, votes, objections, decisions, minutes, and contestations
[ ] mod_uckkarchive archive items, provenance, validation, visibility, and versions
[ ] tool_uckkintegrity cases, decisions, appeals, logs, and restrictions
[ ] tool_uckkseed seed execution logs when user-linked
[ ] block_uckk_dashboard user summaries when stored or cached
[ ] report_uckk report preferences, exports, and access logs when stored
[ ] aiprovider_uckk prompts, responses, logs, and redaction records when stored
```

When Konnaxion-connected mode is enabled, privacy coverage must also include any Moodle-side Konnaxion identity mappings, object mappings, sync logs, Smart Vote target mappings, Smart Vote reading snapshots, Smart Vote audit records, archive packages, and report exports.

### Rule I5 — A symbolic workflow is incomplete until it is testable

A UCKK concept is not implemented merely because labels, templates, or language strings exist.

```text
[ ] It has a database model when persistent state is required.
[ ] It has capabilities and context checks.
[ ] It has events for privileged or auditable actions.
[ ] It has privacy coverage when personal data is involved.
[ ] It has PHPUnit coverage for services and state transitions.
[ ] It has Behat coverage for major user workflows.
[ ] It appears in seed data when part of the default campus.
```

### Rule I6 — Standalone and connected-mode behavior must stay separate

Standalone mode is the core product. Konnaxion-connected mode is an optional integration profile.

```text
[ ] UCKK-Moodle install does not require Konnaxion credentials.
[ ] UCKK-Moodle seed does not require Konnaxion mappings.
[ ] Assemblies work without Smart Vote enabled.
[ ] Archives can preserve Assembly decisions without Smart Vote snapshots.
[ ] Reports work without Konnaxion-derived data.
[ ] Konnaxion UI, tasks, services, and reports are hidden, disabled, skipped, or fail closed when connected mode is disabled.
[ ] Connected-mode failures do not corrupt Moodle source records or fabricate Smart Vote results.
```

## 7. Canonical UI formulae

Use these statements consistently:

```text
Comprendre le jeu. Jouer avec lucidité. Changer les règles.
```

```text
Le spectacle est permis. L’abus ne l’est pas.
```

```text
La foi peut inspirer. Les faits doivent convaincre. La méthode doit pouvoir être vérifiée.
```

```text
King Klown révèle le jeu. L’Inquisiteur garde le jeu honnête. Les Assemblées rendent le jeu collectif. L’Archiviste garde la mémoire.
```

Use these Konnaxion-connected-mode statements consistently:

```text
UCKK-Moodle fonctionne sans Konnaxion.
```

```text
Konnaxion peut enrichir l’organisation collective; il ne remplace pas l’autorité Moodle.
```

```text
Smart Vote est une lecture. L’Assemblée décide. L’Archive conserve la trace.
```

```text
EkoH peut informer une lecture; il ne confère pas de rôle, de badge, de note ou de décision Moodle.
```

## 8. Definition of done

The domain layer is done when:

```text
[ ] Every plugin uses consistent UCKK terminology.
[ ] Public labels avoid accreditation confusion.
[ ] Roles and symbolic titles are not mixed.
[ ] AI is described as assistive, not authoritative.
[ ] King Klown is visible but not sovereign.
[ ] The Inquisiteur is powerful enough to protect integrity but constrained enough to be contestable.
[ ] Assemblées and Archives are real workflow objects, not labels only.
[ ] Konnaxion, EkoH, and Smart Vote are defined as optional connected-mode concepts, not standalone requirements.
[ ] Standalone mode is defined and can be described without Konnaxion.
[ ] Konnaxion-connected mode is defined as an optional integration profile.
[ ] Smart Vote is always described as a reading, not a final decision.
[ ] EkoH is always described as an advisory signal, not Moodle authority.
[ ] Moodle capabilities remain authoritative over external mappings, scores, vote weights, imported results, and request parameters.
[ ] Every plugin component name matches its physical Moodle path.
[ ] PHP, JavaScript, Mustache, JSON, and Markdown content remain in the correct file types.
[ ] Every declared service, form, event, task, and output object has a matching class file.
[ ] Every personal data owner has privacy-provider coverage.
[ ] Connected-mode Konnaxion data has privacy-provider coverage when enabled.
[ ] Every major standalone workflow has PHPUnit and/or Behat coverage before core release.
[ ] Every enabled Konnaxion-connected workflow has PHPUnit and/or Behat coverage before connected-mode release.
```
