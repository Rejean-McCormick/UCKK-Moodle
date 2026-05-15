# 05 — Roles, Permissions and Security

**Status:** Final access control specification, standalone-first with optional Konnaxion-connected gate  
**Purpose:** Define Moodle roles, capabilities, contexts, security constraints, optional Konnaxion Smart Vote authority boundaries, and acceptance checks for UCKK-Moodle.

This document consumes `docs/11_cross_doc_alignment_registry.md` for shared operating modes, document paths, current code-snapshot capabilities, target connected-mode capabilities, deprecated aliases, and standalone-vs-connected requirements.

## 1. Security principle

UCKK symbolic identities must not become uncontrolled Moodle roles.

Use Moodle roles only for permission groups. Use badges, profile titles, cohorts, competencies, portfolio titles, archive distinctions, and seeded metadata for symbolic or pedagogical identities.

Security decisions must be made by Moodle capabilities checked in the correct Moodle context. No UI label, badge, cohort, symbolic title, preset value, JavaScript state, Konnaxion score, external vote weight, imported result, or request parameter may grant authority by itself.

UCKK-Moodle must be secure and usable in standalone mode without Konnaxion. Konnaxion may assist UCKK-Moodle only when Konnaxion-connected mode is enabled. In that mode, Konnaxion may provide Smart Vote readings, EkoH-weighted readings, external analytics, or external identity/object mappings. It must never become the Moodle authority for enrolment, roles, capabilities, grades, badges, competencies, archive validation, integrity decisions, or final Assembly decisions.

Canonical Smart Vote authority rule:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

Canonical operating-mode rule:

```text
OPERATING_MODE_STANDALONE = standalone_core
OPERATING_MODE_KONNAXION_CONNECTED = connected_konnaxion
KONNAXION_REQUIRED_FOR_CORE = false
SMART_VOTE_REQUIRED_FOR_CORE = false
KONNAXION_DEFAULT_STATE = disabled
SMART_VOTE_DEFAULT_STATE = disabled unless Konnaxion-connected mode is enabled
```

Standalone mode must support normal UCKK-Moodle operation: courses, roles, challenges, assemblies, archives, integrity review, reporting, privacy, and AI governance. Konnaxion-connected mode adds optional Smart Vote readings and Konnaxion organization signals without replacing Moodle authority.

Every privileged operation must answer these questions before implementation is accepted:

```text
[ ] Which plugin owns the operation?
[ ] Which capability authorizes it?
[ ] Which Moodle context is checked?
[ ] Which PHP page, external service, CLI command, task, callback, or integration worker performs the check?
[ ] Which event is emitted if the operation changes state?
[ ] Which PHPUnit or Behat test proves authorized users are allowed?
[ ] Which PHPUnit or Behat test proves unauthorized users are denied?
[ ] Which privacy provider covers stored personal data?
[ ] If Konnaxion is involved, which Moodle record is the source of authority?
[ ] If Konnaxion is involved, which imported value is only advisory/read-only?
```

## 2. Technical roles

| Role | Purpose | Implementation rule |
|---|---|---|
| Administrateur Moodle | Full Moodle administration | Existing Moodle administrator role; not replaced by UCKK. |
| Gestionnaire UCKK | Manage UCKK configuration, programs, seed outputs, reports, and approved external integrations | Seeded technical role with explicit UCKK capabilities only. |
| Mentor UCKK | Teach, evaluate, guide challenges and assemblies | Course/module-scoped where possible. |
| Joueur | Learn, submit proof, join assemblies, complete challenges, cast authorized votes | Learner role; no administrative authority. |
| Facilitateur d'Assemblée | Facilitate Assembly workflow where assigned | Module-scoped capability bundle; not a global admin role. |
| Archiviste UCKK | Validate, version, and manage archive items | Archive-specific authority; not general content administrator. |
| Inquisiteur UCKK | Review integrity cases and issue corrections | Integrity authority with contestability and event logging. |
| Observateur | Restricted read-only participant | Read-only role; no restricted integrity access by default. |
| Invité public limité | Public-facing read-only access where allowed | Public/guest access only to explicitly public validated material. |
| Service d'intégration Konnaxion | Optional connected-mode technical actor for server-to-server exchange | Must not be a human-facing Moodle role; must use restricted service credentials and service-layer capability checks. |

The Konnaxion integration service actor exists only when Konnaxion-connected mode is enabled. It must not be enrolled as a normal learner, mentor, manager, Inquisiteur, or administrator. It is a technical integration identity with the minimum service permissions required to exchange mappings and readings.

## 3. Symbolic titles

Do not create default Moodle roles for:

```text
Bâtisseur
Joueur lucide
Cartographe de systèmes
Architecte du sens
Architecte d'opportunités
Gardien des systèmes vivants
Gardien de la preuve
Expert EkoH
Voix pondérée
Contributeur validé Konnaxion
```

Represent these through:

```text
badges
profile fields
cohorts
competency states
portfolio titles
archive distinctions
seeded metadata
report labels
external mapping metadata
```

Symbolic titles may appear in UI, dashboards, reports, badges, certificates, optional Smart Vote reading panels, and archive records, but they must not bypass `has_capability()`, `require_capability()`, enrolment checks, visibility checks, privacy rules, or Assembly decision rules.

## 4. Capability naming and canonical registry

Use Moodle component capability naming:

```text
component/capabilityowner:capabilityname
```

Examples:

```text
local/uckk:viewcampus
mod/uckkchallenge:submitproof
mod/uckkassembly:vote
mod/uckkassembly:viewsmartvote
tool/uckkintegrity:reviewcase
report/uckk:viewsmartvotereports
```

Every capability listed as `implemented_now` in this document must exist in the owning plugin's `db/access.php`. Every current `db/access.php` capability must appear in this document or in the plugin-specific implementation specification.

Capability names in this section are canonical for the current code snapshot unless explicitly marked as `target_connected_mode`. Other documents must reference this registry instead of redefining conflicting names.

Konnaxion-connected capabilities are optional connected-mode capabilities. They must not be required for standalone course, challenge, assembly, archive, integrity, or report workflows. Target connected-mode capabilities must not be treated as implemented until they exist in `db/access.php`, presets, language strings, page/service checks, PHPUnit tests, and Behat visibility tests.

### 4.1 Core UCKK capabilities

These capabilities are implemented in the current code snapshot and are required for standalone UCKK-Moodle.

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

`local/uckk:manageintegrations` is the implemented integration-administration capability in the current code snapshot. Until narrower Konnaxion capabilities are implemented, Konnaxion bridge configuration, mapping administration, and connected-mode log visibility must be guarded by `local/uckk:manageintegrations` plus service-layer context, privacy, and fail-closed checks.

### 4.2 Optional Konnaxion-connected capability targets

These capabilities are target connected-mode capabilities. They are **not implemented in the current code snapshot** unless the capability is explicitly listed in section 4.1. They must not be required for standalone UCKK-Moodle operation.

Current implemented Konnaxion/integration administration capability:

```text
local/uckk:manageintegrations
```

Target connected-mode capabilities that may be added in a later connected-profile implementation:

```text
mod/uckkassembly:requestsmartvote
mod/uckkassembly:viewsmartvote
mod/uckkassembly:reviewsmartvote
mod/uckkassembly:contestsmartvote
mod/uckkarchive:archivesmartvote
report/uckk:viewsmartvotereports
```

Do not add narrower `local/uckk:*konnaxion*` capabilities unless the migration updates `db/access.php`, presets, language strings, page/service checks, PHPUnit tests, Behat visibility tests, and `DOC_11` together.

Capability meanings:

| Capability | Meaning | Status |
|---|---|---|
| `local/uckk:manageintegrations` | Configure and administer optional external integrations, including Konnaxion bridge settings when connected mode is implemented. | implemented now |
| `mod/uckkassembly:requestsmartvote` | Request, export, open, refresh, or receive a Smart Vote reading for an Assembly object through the bridge. | target connected-mode |
| `mod/uckkassembly:viewsmartvote` | View an authorized Smart Vote reading without private payloads, voter identity, restricted mappings, or secrets. | target connected-mode |
| `mod/uckkassembly:reviewsmartvote` | Review, accept as an advisory reading, invalidate, or send to integrity review a Smart Vote result. | target connected-mode |
| `mod/uckkassembly:contestsmartvote` | Contest an imported Smart Vote reading or mapping-derived reading. | target connected-mode |
| `mod/uckkarchive:archivesmartvote` | Archive a Smart Vote reading/snapshot with provenance and privacy classification. | target connected-mode |
| `report/uckk:viewsmartvotereports` | View or export Smart Vote reports and connected-mode audit summaries. | target connected-mode |

### 4.3 Corrected capability spellings

The following implemented spelling is canonical for current integration administration:

```text
local/uckk:manageintegrations
```

The following spellings are valid target connected-mode spellings if the connected profile is implemented:

```text
mod/uckkassembly:requestsmartvote
mod/uckkassembly:viewsmartvote
mod/uckkassembly:reviewsmartvote
mod/uckkassembly:contestsmartvote
mod/uckkarchive:archivesmartvote
report/uckk:viewsmartvotereports
```

The misspelled or obsolete names below must not be implemented and must not appear in generated code, presets, services, tests, or active UI checks:

```text
mod/uckkassembly:viewsmarkvotereading
mod/uckkassembly:viewsmartvotereading
mod/uckkassembly:exportsmartvotetarget
mod/uckkarchive:viewsmartvotesnapshot
mod/uckkarchive:archivesmartvotesnapshot
```

### 4.4 Deprecated capability aliases

The following names may appear only in this deprecated-alias section, `DOC_11`, or explicit migration notes. They are deprecated and must not be used in final code, active presets, active services, tests, templates, AMD modules, or runtime checks.

| Deprecated or drifting name | Current or target replacement |
|---|---|
| `local/uckk:managekonnaxion` | `local/uckk:manageintegrations` until a narrower connected-mode migration exists |
| `local/uckk:configurekonnaxion` | `local/uckk:manageintegrations` |
| `local/uckk:managekonnaxionmappings` | `local/uckk:manageintegrations` until a narrower connected-mode migration exists |
| `local/uckk:mapkonnaxionobjects` | `local/uckk:manageintegrations` until a narrower connected-mode migration exists |
| `local/uckk:viewkonnaxionstatus` | `local/uckk:manageintegrations` |
| `local/uckk:viewkonnaxionlogs` | `local/uckk:manageintegrations` until a narrower connected-mode migration exists |
| `local/uckk:syncsmartvote` | restricted service identity plus `local/uckk:manageintegrations` for administration |
| `local/uckk:viewsmartvotelogs` | `local/uckk:manageintegrations` |
| `mod/uckkassembly:submitmotion` | `mod/uckkassembly:proposemotion` |
| `mod/uckkassembly:submitamendment` | `mod/uckkassembly:amendmotion` |
| `mod/uckkassembly:submitobjection` | not implemented in current snapshot; use documented objection workflow only after capability migration |
| `mod/uckkassembly:managevotes` | not implemented in current snapshot; use service-layer checks around `mod/uckkassembly:vote` until migration |
| `mod/uckkassembly:manageminutes` | not implemented in current snapshot; minutes operations require service/page checks until migration |
| `mod/uckkassembly:validateintegrity` | not implemented in current snapshot; integrity review belongs to `tool/uckkintegrity:*` capabilities |
| `mod/uckkassembly:votesmartvote` | `mod/uckkassembly:requestsmartvote` target for requesting; `mod/uckkassembly:vote` for participant vote |
| `mod/uckkassembly:opensmartvote` | `mod/uckkassembly:requestsmartvote` target |
| `mod/uckkassembly:exportsmartvotetarget` | `mod/uckkassembly:requestsmartvote` target |
| `mod/uckkassembly:importsmartvoteresult` | `mod/uckkassembly:reviewsmartvote` target plus restricted service identity |
| `mod/uckkassembly:invalidatesmartvote` | `mod/uckkassembly:reviewsmartvote` target plus integrity-review authority |
| `mod/uckkassembly:publishsmartvote` | `mod/uckkassembly:reviewsmartvote` target plus `mod/uckkassembly:publishdecision` |
| `mod/uckkassembly:viewsmarkvotereading` | banned typo; no replacement except `mod/uckkassembly:viewsmartvote` target |
| `mod/uckkassembly:viewsmartvotereading` | `mod/uckkassembly:viewsmartvote` target |
| `mod/uckkarchive:versionitem` | `mod/uckkarchive:reviseitem` |
| `mod/uckkarchive:viewsmartvotesnapshot` | `mod/uckkassembly:viewsmartvote` or `report/uckk:viewsmartvotereports`, depending on context, after connected-mode migration |
| `mod/uckkarchive:archivesmartvotesnapshot` | `mod/uckkarchive:archivesmartvote` target |
| `tool/uckkseed:run` | `tool/uckkseed:seed` |
| `tool/uckkseed:dryrun` | `tool/uckkseed:validate` or `tool/uckkseed:seed` depending on mode |
| `tool/uckkseed:rollback` | `tool/uckkseed:reset` where reset semantics are explicitly safe |
| `tool/uckkintegrity:reviewsmartvote` | `tool/uckkintegrity:reviewcase` plus connected-mode Smart Vote context checks |
| `report/uckk:viewsmartvote` | `report/uckk:viewsmartvotereports` target |
| `report/uckk:exportsmartvote` | `report/uckk:viewsmartvotereports` target plus export checks |
| `report/uckk:viewkonnaxionlogs` | `local/uckk:manageintegrations` until narrower log capability exists |
| `mod/uckkchallenge:contest` | not implemented in current snapshot; use documented contest workflow only after capability migration |
| `mod/uckkchallenge:viewallsubmissions` | not implemented in current snapshot; use context-aware report/service checks until migration |
| `mod/uckkchallenge:manage` | not implemented in current snapshot; use specific implemented capabilities |
| `mod/uckkchallenge:publishchallenge` | `mod/uckkchallenge:createchallenge` plus state transition permissions |
| `tool/uckkintegrity:invalidatechallenge` | `tool/uckkintegrity:invalidate` plus subject-component check |

Final code must fail static review if deprecated names appear in:

```text
db/access.php
db/services.php
classes/external/*
classes/event/*
classes/local/*
classes/service/*
templates/*
amd/src/*
tests/*
presets/*.json
```

Deprecated names may appear only in documentation sections explicitly labelled as deprecated aliases or migration notes.

### 4.5 Capability implementation rule

For each capability, `db/access.php` must define:

```text
captype
contextlevel
archetypes when appropriate
riskbitmask for write, config, XSS, spam, personal-data, or trust-sensitive actions
clonepermissionsfrom where a standard Moodle capability is the closest parent
```

A capability is incomplete until at least one test proves:

```text
authorized role can perform the action
unauthorized role is denied
wrong context is denied
restricted personal data is not leaked
external imported data cannot bypass Moodle authority
```

## 5. Context levels

| Capability family | Default context | Rule |
|---|---|---|
| Campus viewing | System | May expose public/non-restricted UCKK navigation only. |
| Campus configuration | System | Restricted to administrator or Gestionnaire UCKK. |
| Program/pathway management | Course category or system | Prefer category context when program belongs to a category; system only for global registries. |
| Konnaxion configuration | System | Optional connected-mode only. Endpoint, credentials, global enablement, retention, and fail-closed behavior are system-only and require `local/uckk:manageintegrations` until narrower Konnaxion capabilities are implemented. |
| Konnaxion identity mapping | System plus user context | Optional connected-mode only. Mapping writes require `local/uckk:manageintegrations` until narrower mapping capabilities are implemented; reads must respect user privacy and purpose limitation. |
| Konnaxion object mapping | Source object context | Optional connected-mode only. Mapping must derive Moodle context from the source Assembly, motion, archive item, report, or profile record. |
| Smart Vote reading request | Assembly module context | Optional connected-mode only. Request/export only the minimum target payload needed by Konnaxion; never export hidden evidence unless explicitly permitted. |
| Smart Vote result review | Assembly module context | Optional connected-mode only. Imported result is an advisory reading; final decision still requires Assembly decision capability. |
| Course format | Course | Must not grant plugin-wide authority. |
| Dashboard | Block, user, course, or system | Dashboard is display-only unless a separate owning capability authorizes mutation. |
| Challenge activity | Module | Challenge submission, evaluation, validation, contestation, and archive handoff are module-scoped. |
| Assembly activity | Module | Motions, votes, Smart Vote readings, decisions, contestation, and archive handoff are module-scoped. |
| Archive activity | Module, course, or user | Visibility must be checked per archive item and inherited file visibility. |
| Integrity cases | System, course, module, or user | Use the narrowest context matching the subject of the case. |
| Reports | Course, category, or system | Course reports must not imply cross-course access. |
| AI provider | System plus subject context | Configuration is system-level; use of AI must also check the subject context. |
| Seed tool | System | Dry-run, run, rollback, and export are system administrative operations. |

When a request includes `courseid`, `cmid`, `userid`, `archiveid`, `caseid`, `programid`, `pathwayid`, `assemblyid`, `motionid`, `konnaxionmappingid`, or `smartvoteresultid`, the implementation must derive the context from the stored record and not trust the request parameter alone.

## 6. Role capability matrix

| Capability group | Admin | Gestionnaire | Mentor | Facilitateur | Joueur | Archiviste | Inquisiteur | Observateur | Public limité | Konnaxion service |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Manage Moodle site | yes | no | no | no | no | no | no | no | no | no |
| Manage UCKK programs | yes | yes | no | no | no | no | no | no | no | no |
| Configure pathways | yes | yes | no | no | no | no | no | no | no | no |
| Configure Konnaxion integration (connected mode) | yes | yes | no | no | no | no | no | no | no | no |
| Manage Konnaxion mappings (connected mode) | yes | yes | no | limited | own identity view only | no | audit only | no | no | service-only |
| View Konnaxion status/logs (connected mode) | yes | yes | no | limited | no | no | limited | no | no | service-only |
| Request/review Smart Vote (connected mode) | yes | yes | no | limited by Assembly | no | no | review only | no | no | service-only |
| Teach courses | yes | limited | yes | no | no | no | no | no | no | no |
| Submit proof | yes | no | optional | no | yes | no | no | no | no | no |
| Evaluate proof | yes | optional | yes | no | no | no | no | no | no | no |
| Validate archives | yes | optional | no | no | no | yes | optional | no | no | no |
| Open integrity case | yes | yes | yes | yes | yes | yes | yes | no | no | no |
| Review integrity case | yes | no | no | no | no | no | yes | no | no | no |
| Invalidate challenge | yes | no | no | no | no | no | yes | no | no | no |
| Propose Assembly motion | yes | yes | yes | yes | yes when participant | no | no | no | no | no |
| Amend Assembly motion | yes | yes | yes | yes | yes when participant | no | no | no | no | no |
| Cast Assembly vote | yes | optional | optional | optional | yes when participant | no | no | no | no | no |
| Request Smart Vote reading (connected mode) | yes | yes | optional | yes | no | no | review only | no | no | no |
| Review imported Smart Vote result (connected mode) | yes | yes | no | yes | no | no | review only | no | no | service-only |
| Contest Smart Vote result (connected mode) | yes | yes | yes | yes | yes when affected | yes if archive affected | yes | no | no | no |
| Review/invalidate Smart Vote result (connected mode) | yes | no | no | no | no | no | yes | no | no | no |
| Publish Assembly decision | yes | yes | yes when facilitator | yes when assigned | no | no | no | no | no | no |
| View restricted reports | yes | yes | limited | limited | own only | limited | integrity-related | no | no | no |
| View Smart Vote reports (connected mode) | yes | yes | limited | limited | own/public only | archive-related | integrity-related | no | no | no |
| Export Smart Vote reports (connected mode) | yes | yes | no | no | no | no | no | no | no | no |
| Configure AI provider | yes | no | no | no | no | no | no | no | no | no |
| Use governed AI | yes | optional | optional | optional | optional | no | optional | no | no | no |
| View AI logs | yes | optional | no | no | no | no | integrity-related | no | no | no |

## 7. Page, service, task, CLI, and integration security gates

Every executable entry point must have an explicit security gate.

### 7.1 PHP pages

Every user-facing PHP page must:

```text
[ ] start with valid PHP only
[ ] require config.php through a correct relative path
[ ] call require_login() unless intentionally public
[ ] resolve the Moodle context from trusted records
[ ] call require_capability() for privileged actions
[ ] verify sesskey() for state-changing actions
[ ] use optional_param()/required_param() with strict PARAM_* types
[ ] escape output through Moodle output APIs or templates
[ ] avoid authority decisions in JavaScript
[ ] avoid trusting external Konnaxion identifiers without local mapping validation
```

### 7.2 External services and AJAX

Every `db/services.php` function must have a matching class under:

```text
classes/external/
```

Each external function must:

```text
[ ] validate parameters
[ ] derive and validate context
[ ] call self::validate_context($context) where appropriate
[ ] check capability before reading restricted data or mutating state
[ ] return filtered data only
[ ] avoid leaking hidden, restricted, or cross-context records
[ ] prevent imported Smart Vote readings from changing decisions directly
[ ] have PHPUnit tests for allow/deny cases
```

### 7.3 Scheduled tasks

Every `db/tasks.php` class must live under:

```text
classes/task/
```

Scheduled tasks must:

```text
[ ] never elevate symbolic roles
[ ] never elevate Konnaxion scores into Moodle authority
[ ] log privileged state changes
[ ] respect component ownership
[ ] avoid exposing personal data in logs
[ ] be idempotent where possible
[ ] fail closed when external integration integrity cannot be verified
```

### 7.4 CLI scripts

Every CLI script must:

```text
[ ] be located under cli/
[ ] declare CLI_SCRIPT before requiring config.php
[ ] require an administrator or system-level capability when executed through web-equivalent logic
[ ] support dry-run for destructive or mass operations
[ ] emit auditable output without exposing secrets, tokens, private learner data, private vote payloads, or Konnaxion credentials
```

### 7.5 Konnaxion integration services

Konnaxion integration services are required only in Konnaxion-connected mode. Standalone UCKK-Moodle must install, run, test, and pass security checks with these services disabled or absent from active configuration.

All Konnaxion integration code must be service-layer code, not template logic, JavaScript authority, or direct database shortcuts.

Required connected-mode security rules:

```text
[ ] Konnaxion never writes directly into Moodle source tables.
[ ] Moodle never treats Konnaxion `VoteResult` as an Assembly decision.
[ ] Moodle stores imported Smart Vote results as versioned snapshots/readings.
[ ] Every outbound Smart Vote reading request has a Moodle actor, context, capability, and event.
[ ] Every inbound Smart Vote result has a Moodle actor or service identity, context, capability, signature/checksum validation, and event.
[ ] Every imported reading can be contested.
[ ] Every contested reading can be invalidated by integrity review.
[ ] Every final Assembly decision records whether it used, ignored, contradicted, or deferred a Smart Vote reading.
[ ] External API credentials use secure Moodle configuration and are never printed in UI, logs, reports, exports, or exceptions.
[ ] Network failure, timeout, malformed response, unknown mapping, stale mapping, or failed verification must fail closed.
```

Required connected-mode service classes:

```text
local_uckk\service\konnaxion_client
local_uckk\service\konnaxion_mapping_service
local_uckk\service\konnaxion_sync_service
mod_uckkassembly\service\smart_vote_bridge
mod_uckkassembly\service\smart_vote_result_validator
mod_uckkassembly\service\smart_vote_audit_service
```

## 8. Inquisiteur constraints

The Inquisiteur must be powerful but not arbitrary.

Rules:

```text
[ ] Inquisiteur can review and pause integrity-sensitive objects.
[ ] Inquisiteur can issue corrections.
[ ] Inquisiteur can invalidate challenge validation when evidence fails.
[ ] Inquisiteur can request archive review.
[ ] Inquisiteur can review contested Smart Vote readings.
[ ] Inquisiteur can invalidate a Smart Vote snapshot if mapping, source, signature, method, privacy, or procedural integrity fails.
[ ] Inquisiteur cannot silently delete evidence.
[ ] Inquisiteur cannot erase archive history.
[ ] Inquisiteur cannot modify archive history without version record.
[ ] Inquisiteur cannot assign themselves unrestricted admin rights.
[ ] Inquisiteur cannot close their own contested case without secondary review.
[ ] Inquisiteur cannot convert a Smart Vote result into a final Assembly decision.
[ ] Inquisiteur decisions are logged and contestable.
[ ] Inquisiteur actions generate events.
```

Implementation requirements:

```text
tool/uckkintegrity/classes/event/integrity_case_opened.php
tool/uckkintegrity/classes/event/integrity_case_reviewed.php
tool/uckkintegrity/classes/event/integrity_case_closed.php
tool/uckkintegrity/classes/event/integrity_correction_issued.php
tool/uckkintegrity/classes/event/challenge_invalidated.php
tool/uckkintegrity/classes/event/smart_vote_reviewed.php
tool/uckkintegrity/classes/event/smart_vote_invalidated.php
tool/uckkintegrity/classes/privacy/provider.php
```

## 9. Archive security

Archive visibility levels:

```text
private
course
cohort
program
institutional
public
restricted_integrity
restricted_privacy
```

Rules:

```text
[ ] Restricted integrity archive items require explicit capability.
[ ] Public archive items must be manually validated.
[ ] Evidence files inherit archive item visibility unless explicitly restricted further.
[ ] Version history must remain available to authorized reviewers.
[ ] Version history must not be silently rewritten.
[ ] Export requires explicit export capability.
[ ] File serving must check item visibility and file ownership context.
[ ] Smart Vote snapshots archived from Konnaxion must preserve method, modality, mapping, imported timestamp, importing actor/service, raw-result summary, weighted-result summary, privacy classification, and contestation state.
[ ] Smart Vote snapshots must not expose private voter identity, external scores, demographic filters, or EkoH details unless an explicit capability and privacy rule allow it.
[ ] Standalone archives may preserve Assembly decisions without any Smart Vote snapshot.
[ ] Absence of a Smart Vote reading is valid provenance in standalone mode, not a missing record.
```

Implementation requirements:

```text
mod/uckkarchive/db/access.php
mod/uckkarchive/classes/event/archive_item_created.php
mod/uckkarchive/classes/event/archive_item_validated.php
mod/uckkarchive/classes/event/archive_item_versioned.php
mod/uckkarchive/classes/event/smart_vote_snapshot_archived.php
mod/uckkarchive/classes/privacy/provider.php
mod/uckkarchive/tests/* permission checks
mod/uckkarchive/tests/behat/* restricted visibility checks
```

## 10. Konnaxion and Smart Vote security

### 10.1 Integration boundary

Konnaxion is an external system. UCKK-Moodle may use it only in Konnaxion-connected mode to compute Smart Vote readings for Assembly motions, decisions, consultations, reports, or other explicitly mapped objects.

Konnaxion may provide:

```text
external vote target id
vote modality
raw vote count or raw vote distribution
weighted vote reading
EkoH-weighted reading metadata
result timestamp
method/configuration summary
result confidence or completeness indicator when available
integration mapping reference
```

Konnaxion must not provide or override:

```text
Moodle role assignment
Moodle capability grants
course enrolment
challenge validation
archive validation
integrity closure
badge awards
competency ratings
Assembly final decision
Moodle grades
privacy deletion authority
```

### 10.2 Smart Vote states requiring security checks

These states exist only in Konnaxion-connected mode. Standalone Assemblies must work without them.

The following state transitions are privileged and must be evented:

```text
not_mapped → mapped_to_konnaxion
mapped_to_konnaxion → target_exported
target_exported → smart_vote_open
smart_vote_open → smart_vote_closed
smart_vote_closed → result_imported
result_imported → result_reviewed
result_reviewed → result_contested
result_reviewed → result_archived
result_contested → result_under_integrity_review
result_under_integrity_review → result_validated
result_under_integrity_review → result_invalidated
result_archived → assembly_decision_published
```

Required checks:

```text
[ ] Mapping requires `local/uckk:manageintegrations` in the current snapshot, or a later `local/uckk:mapkonnaxionobjects` capability only after the connected-mode capability migration is implemented; source-object capability is still required.
[ ] Requesting, exporting, or opening a Smart Vote reading requires `mod/uckkassembly:requestsmartvote`.
[ ] Inbound result handling requires `mod/uckkassembly:reviewsmartvote` or restricted service identity.
[ ] Result viewing requires `mod/uckkassembly:viewsmartvote` plus visibility rules.
[ ] Result contestation requires `mod/uckkassembly:contestsmartvote` or affected-party rights.
[ ] Result invalidation requires `mod/uckkassembly:reviewsmartvote` plus integrity-review authority where required.
[ ] Archiving requires `mod/uckkarchive:archivesmartvote` and source Assembly authority.
[ ] Smart Vote reports require `report/uckk:viewsmartvotereports`.
[ ] Final decision publishing requires `mod/uckkassembly:publishdecision` and must not be performed by Konnaxion.
```

### 10.3 Smart Vote privacy floor

The minimum privacy rule is:

```text
A normal participant may see the public or participant-visible Smart Vote reading for an Assembly they can access.
A normal participant must not automatically see private voter identity, external EkoH weights, demographic filter details, raw exported payloads, hidden mappings, private API errors, or restricted integrity notes.
```

Stored Smart Vote data must be classified as one of:

```text
public_reading
participant_reading
restricted_method_metadata
restricted_identity_mapping
restricted_vote_payload
restricted_integrity_review
restricted_privacy_review
```

### 10.4 Integration identity and credential handling

```text
[ ] Konnaxion endpoint URL, API key, client secret, webhook secret, signing key, and token configuration are system-level settings.
[ ] Secrets use Moodle secure/password configuration handling.
[ ] Secrets must never appear in reports, debug pages, seed logs, exceptions, CLI output, Behat output, PHPUnit failure messages, or downloadable exports.
[ ] Webhook or API response verification must validate signature/checksum, target mapping, freshness window, expected modality, expected context, and replay protection.
[ ] Failed verification must create an audit event and must not import the result.
```

### 10.5 Anti-capture rules

```text
[ ] Smart Vote cannot make final decisions automatically.
[ ] Smart Vote cannot hide minority reports.
[ ] Smart Vote cannot suppress contestation.
[ ] Smart Vote cannot erase raw Moodle Assembly records.
[ ] Smart Vote cannot replace minutes.
[ ] Smart Vote cannot bypass Inquisiteur review.
[ ] Smart Vote cannot promote external expertise scores into Moodle roles.
[ ] Smart Vote cannot award badges or competencies.
[ ] Smart Vote cannot publish archives without Archiviste/archive capability.
```

## 11. AI security

AI permissions must be explicit.

Rules:

```text
[ ] AI cannot run in restricted contexts unless enabled.
[ ] AI use must check both aiprovider/uckk:use and the subject-context capability.
[ ] AI logs must obey privacy settings.
[ ] AI prompts must be redacted according to configuration.
[ ] AI output must be visibly labeled non-authoritative.
[ ] AI cannot assign grades.
[ ] AI cannot close integrity cases.
[ ] AI cannot publish assembly decisions.
[ ] AI cannot validate archive items.
[ ] AI cannot create Moodle roles or assign capabilities.
[ ] AI cannot interpret or summarize restricted Smart Vote mappings unless the user has Smart Vote report/log authority.
```

Implementation requirements:

```text
ai/provider/uckk/db/access.php
ai/provider/uckk/classes/privacy/provider.php
ai/provider/uckk/tests/provider_test.php
ai/provider/uckk/tests/processor_test.php
```

## 12. Privacy and personal data

Every plugin that stores or exposes user-linked data must implement:

```text
classes/privacy/provider.php
```

Required coverage:

| Plugin | Privacy coverage required |
|---|---|
| local_uckk | Profiles, programs, pathways, portfolio/canon relationships, provenance, optional Konnaxion identity mappings, optional Konnaxion object mappings, optional Smart Vote sync logs, imported-reading audit metadata when connected mode is enabled. |
| mod_uckkchallenge | Challenge submissions, proof, evaluation, validation, contestation. |
| mod_uckkassembly | Motions, votes, objections, amendments, decisions, minutes, optional Smart Vote reading requests, Smart Vote result snapshots, and Smart Vote contestations when connected mode is enabled. |
| mod_uckkarchive | Archive items, evidence files, provenance, validation, visibility, optional Smart Vote archived snapshots when connected mode is enabled. |
| tool_uckkintegrity | Cases, reviews, corrections, decisions, restricted evidence links, optional Smart Vote integrity review records when connected mode is enabled. |
| tool_uckkseed | Seed logs, imported preset metadata, operator actions. |
| block_uckk_dashboard | Dashboard preferences or cached user summaries if stored; optional Smart Vote dashboard summaries if cached in connected mode. |
| format_uckk | Course-format user preferences or section state if stored. |
| report_uckk | Saved filters, exports, report access logs, optional Smart Vote report access logs and Konnaxion log access when connected mode is enabled. |
| aiprovider_uckk | Prompt logs, response logs, model/action metadata, redaction state. |

Privacy provider tests must prove:

```text
[ ] export includes all personal UCKK records for the user
[ ] in connected mode, export includes user-linked Konnaxion mappings where Moodle stores them
[ ] in connected mode, export includes Moodle-side Smart Vote participation/snapshot records where the user is linked
[ ] delete removes or anonymizes user-linked data where Moodle privacy rules require it
[ ] in connected mode, Konnaxion identity mappings are deleted, anonymized, or retained according to documented retention rules
[ ] restricted integrity records are handled safely
[ ] Smart Vote snapshots do not expose unnecessary external weights or private voters
[ ] AI logs obey redaction and deletion rules
```

## 13. Audit logging

Every privileged action must create a Moodle event with exact class names. Human-readable audit labels are not enough.

### 13.1 Required audit events

Konnaxion and Smart Vote events are required only in Konnaxion-connected mode.

```text
local_uckk\event\program_changed
local_uckk\event\pathway_assigned
local_uckk\event\profile_updated
local_uckk\event\konnaxion_identity_mapping_created
local_uckk\event\konnaxion_identity_mapping_updated
local_uckk\event\konnaxion_identity_mapping_deleted
local_uckk\event\konnaxion_object_mapping_created
local_uckk\event\konnaxion_object_mapping_updated
local_uckk\event\smart_vote_sync_started
local_uckk\event\smart_vote_sync_completed
local_uckk\event\smart_vote_sync_failed

mod_uckkchallenge\event\proof_submitted
mod_uckkchallenge\event\proof_evaluated
mod_uckkchallenge\event\challenge_validated
mod_uckkchallenge\event\challenge_invalidated
mod_uckkchallenge\event\challenge_contested

mod_uckkassembly\event\assembly_motion_proposed
mod_uckkassembly\event\assembly_motion_amended
mod_uckkassembly\event\assembly_objection_submitted
mod_uckkassembly\event\assembly_vote_cast
mod_uckkassembly\event\smart_vote_target_exported
mod_uckkassembly\event\smart_vote_opened
mod_uckkassembly\event\smart_vote_result_imported
mod_uckkassembly\event\smart_vote_result_reviewed
mod_uckkassembly\event\smart_vote_result_contested
mod_uckkassembly\event\smart_vote_result_invalidated
mod_uckkassembly\event\assembly_decision_published
mod_uckkassembly\event\assembly_decision_contested
mod_uckkassembly\event\assembly_minutes_updated

mod_uckkarchive\event\archive_item_created
mod_uckkarchive\event\archive_item_validated
mod_uckkarchive\event\archive_item_versioned
mod_uckkarchive\event\archive_item_exported
mod_uckkarchive\event\smart_vote_snapshot_archived

tool_uckkintegrity\event\integrity_case_opened
tool_uckkintegrity\event\integrity_case_reviewed
tool_uckkintegrity\event\integrity_case_closed
tool_uckkintegrity\event\integrity_correction_issued
tool_uckkintegrity\event\smart_vote_reviewed
tool_uckkintegrity\event\smart_vote_invalidated

aiprovider_uckk\event\ai_action_requested
aiprovider_uckk\event\ai_log_viewed

report_uckk\event\report_viewed
report_uckk\event\report_exported
report_uckk\event\smart_vote_report_viewed
report_uckk\event\smart_vote_report_exported

tool_uckkseed\event\seed_dry_run_executed
tool_uckkseed\event\seed_executed
tool_uckkseed\event\seed_rollback_executed
```

Events must include enough object/context information for audit without exposing secrets, private prompt content, private voter identity, external EkoH details, raw private payloads, or unnecessary personal data.

### 13.2 Event naming rule

Every event class must use the exact names above unless a later canonical event registry replaces this section. Older human-readable names such as `assembly vote cast` or drifted names such as `vote_submitted` must be mapped to the canonical class name `mod_uckkassembly\event\assembly_vote_cast`.

## 14. Implementation correction gates

The next code pass is not accepted until these gates pass.

### 14.1 Filetype correctness

```text
[ ] PHP files contain PHP only and start with <?php unless intentionally non-PHP.
[ ] JavaScript AMD files contain JavaScript only.
[ ] No PHP page/controller code exists under amd/src/*.js.
[ ] No JavaScript module code is stored as a .php page.
[ ] No Markdown fences or documentation-only text remain inside executable PHP.
```

### 14.2 Component correctness

```text
[ ] Every version.php component matches its plugin path.
[ ] Every @package value matches the component.
[ ] Every capability owner matches its plugin component.
[ ] Every service classname namespace matches the owning plugin.
[ ] Every Konnaxion integration class lives under the declared owner component and is optional for standalone mode.
```

### 14.3 Class-layer completeness

Every referenced class must exist under the owning plugin's `classes/` directory.

Required class families where applicable:

```text
classes/form/
classes/event/
classes/external/
classes/output/
classes/local/
classes/service/
classes/task/
classes/privacy/provider.php
```

No controller, service declaration, template, AMD module, scheduled task, PHPUnit test, Konnaxion adapter, Smart Vote bridge, or Behat workflow may reference a missing class.

### 14.4 Access-control completeness

```text
[ ] Every PHP page has require_login() or an explicit public-access justification.
[ ] Every privileged page has require_capability().
[ ] Every external service checks context and capability.
[ ] Every state-changing POST validates sesskey().
[ ] Every file-serving endpoint checks item visibility and context.
[ ] Every report query filters by capability and context.
[ ] Every dashboard query filters by viewer authority.
[ ] In connected mode, every Konnaxion exchange checks mapping, source context, capability, and privacy class.
[ ] Every imported Smart Vote result is advisory until a Moodle Assembly decision is explicitly published.
```

## 15. Testing requirements

### 15.1 Static checks

```text
php -l on every PHP file
JavaScript syntax/build check for every amd/src/*.js
XMLDB install.xml validation
JSON preset validation
grep check for PHP tokens in JS files
grep check for Markdown fences in PHP files
grep check for deprecated capability names in active code, presets, services, tests, templates, and AMD modules
grep check for misspelled or deprecated Smart Vote capabilities in active code and presets
grep check for direct Konnaxion writes into Moodle source tables
component/version.php path check
```

### 15.2 PHPUnit

Required PHPUnit coverage:

```text
standalone install and core workflows pass with Konnaxion disabled
Konnaxion-connected capabilities are not required for standalone workflows
local_uckk capability checks
program and pathway service allow/deny cases
profile visibility and provenance checks
Konnaxion identity mapping allow/deny cases when connected mode is enabled
Konnaxion object mapping allow/deny cases when connected mode is enabled
Smart Vote export/import validation when connected mode is enabled
Smart Vote failed signature/checksum/replay handling when connected mode is enabled
Smart Vote stale mapping rejection when connected mode is enabled
Smart Vote advisory-only decision protection when connected mode is enabled
challenge state transitions and permission denials
assembly motion/vote/decision permission denials
assembly Smart Vote state transitions and permission denials when connected mode is enabled
archive item visibility, validation, revision, export permissions
Smart Vote snapshot archive visibility and export permissions when connected mode is enabled
integrity case transitions and Inquisiteur constraints
Smart Vote integrity review and invalidation when connected mode is enabled
privacy export/delete per plugin
seed dry-run/run/rollback permissions
report query builders and export permissions
Smart Vote report access and export restrictions when connected mode is enabled
AI request authorization, redaction, and log visibility
```

### 15.3 Behat

Required Behat workflows:

```text
standalone user completes core UCKK-Moodle workflows with Konnaxion disabled
unauthorized user cannot access UCKK management
Joueur sees only own dashboard data
Joueur submits challenge proof
Mentor evaluates challenge proof
unauthorized user cannot evaluate proof
Assembly motion is proposed and voted on
unauthorized user cannot publish Assembly decision
when connected mode is enabled, Facilitateur opens a Smart Vote reading for an Assembly motion
when connected mode is enabled, unauthorized user cannot open Smart Vote reading
when connected mode is enabled, Konnaxion Smart Vote result is imported as a reading only
when connected mode is enabled, Smart Vote result does not publish Assembly decision automatically
when connected mode is enabled, participant contests Smart Vote result
when connected mode is enabled, Inquisiteur invalidates flawed Smart Vote result
when connected mode is enabled, Archiviste archives Smart Vote snapshot
ordinary user cannot view restricted Smart Vote mapping or payload
Archiviste validates archive item
ordinary user cannot view restricted integrity archive item
Inquisiteur opens and closes case
Inquisiteur cannot silently delete evidence
Report is visible only to authorized users
when connected mode is enabled, Smart Vote report is visible only to authorized users
AI summary is generated with non-authority warning
Privacy export includes UCKK records
when connected mode is enabled, privacy export handles Konnaxion mappings safely
Public UI avoids accreditation confusion
```

## 16. Definition of done

The standalone-core definition of done uses the implemented capabilities in section 4.1. Connected-mode Konnaxion/Smart Vote checks apply only when that profile is enabled, packaged, or claimed as supported.

```text
[ ] UCKK-Moodle installs and works with Konnaxion disabled.
[ ] db/access.php exists for every plugin needing capabilities.
[ ] All capabilities are listed, context-appropriate, and tested.
[ ] Deprecated capability aliases do not appear in final code.
[ ] Misspelled Smart Vote capabilities do not appear in final code.
[ ] No symbolic title is treated as global authority.
[ ] No external Konnaxion score or result is treated as Moodle authority.
[ ] Every executable entry point has a security gate.
[ ] Every external service has a matching classes/external implementation.
[ ] Every scheduled task has a matching classes/task implementation.
[ ] In Konnaxion-connected mode, every enabled Konnaxion integration service has a matching service/local class and tests.
[ ] Every personal data store has a classes/privacy/provider.php implementation.
[ ] Privileged actions are evented.
[ ] Restricted content cannot be viewed by ordinary users.
[ ] AI cannot become final authority.
[ ] Smart Vote cannot become final authority.
[ ] Archives preserve provenance and version history.
[ ] In Konnaxion-connected mode, archives preserve Smart Vote readings and Assembly decisions separately; in standalone mode, archives accept Assembly decisions without Smart Vote snapshots.
[ ] Behat tests confirm permission boundaries.
[ ] PHPUnit tests confirm service-layer capability checks.
[ ] Static checks pass before Moodle install is attempted.
```
