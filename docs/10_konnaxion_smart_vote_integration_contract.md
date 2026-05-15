# 10 — Konnaxion Smart Vote Integration Contract

**Status:** Optional connected-mode integration contract  
**Target systems:** UCKK-Moodle + Konnaxion v14  
**Purpose:** Define how UCKK-Moodle can connect to Konnaxion Smart Vote without making Konnaxion a core dependency and without weakening Moodle permissions, UCKK Assembly legitimacy, archive provenance, privacy, or integrity review.

This document consumes `docs/11_cross_doc_alignment_registry.md` for shared variables, document paths, capability names, table names, operating modes, deprecated aliases, and standalone-vs-connected requirements. It must not redefine shared variables locally.

## 0. Operating profiles

UCKK-Moodle has two supported operating profiles.

| Profile | Meaning | Release rule |
|---|---|---|
| `standalone_core` | UCKK-Moodle operates without Konnaxion. | Must install, seed, teach, deliberate, archive, report, and enforce permissions without Konnaxion. |
| `connected_konnaxion` | UCKK-Moodle enables the Konnaxion bridge for Smart Vote and EkoH/advisory readings. | Accepted only when the bridge, mappings, privacy rules, archive snapshots, reports, and failure handling pass connected-mode gates. |

Core variables:

```text
KONNAXION_REQUIRED_FOR_CORE = false
SMART_VOTE_REQUIRED_FOR_CORE = false
KONNAXION_DEFAULT_STATE = disabled
SMART_VOTE_DEFAULT_STATE = disabled unless Konnaxion bridge is enabled
```

Konnaxion is an optional Layer 6 integration. It must never be a hard dependency for standalone installation, standalone seed execution, ordinary Assembly decisions, archive records, integrity workflows, or core reports.

Current code-snapshot status:

```text
standalone_core = implemented target for the next code revision
connected_konnaxion = target connected-mode profile, not a current standalone-core blocker
```

Executable code must not be forced to create Konnaxion/Smart Vote classes, services, tables, presets, or reports unless the connected profile is explicitly implemented.

## 1. Governing rule

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

Smart Vote is a decision-support reading, not a sovereign decision-maker. It may inform an Assembly, but it must not automatically publish a decision, award a badge, validate a competency, close an integrity case, modify an archive item, or replace a human/procedural decision path.

## 2. Domain boundary

| Domain | Responsibility |
|---|---|
| UCKK-Moodle | Campus authority for learning records, assemblies, decisions, archives, permissions, reports, privacy, and integrity review. |
| Konnaxion | External collective-intelligence system providing EkoH reputation/advisory data, vote modalities, weighted vote aggregation, Smart Vote results, and analytics when connected mode is enabled. |
| `local_uckk` | Core integration coordinator. In connected mode, Moodle-side Konnaxion bridge owner: settings, endpoint client, object mapping, user mapping, sync logs, and shared bridge services. |
| `mod_uckkassembly` | Source of truth for Assembly motions, deliberation, decision state, minority reports, Smart Vote reading workflow, contestability, and minutes. |
| `mod_uckkarchive` | Source of truth for archived snapshots, provenance, evidence, decision memory, and versioned records. |
| `tool_uckkintegrity` | Source of truth for disputes, anomalies, procedural corrections, privacy concerns, and integrity review. |
| `report_uckk` | Permission-aware reporting on UCKK-side records. In connected mode, also reports on imported Smart Vote snapshots. |

Authority boundaries:

```text
SMART_VOTE_AUTHORITY = computed_reading_only
ASSEMBLY_AUTHORITY = human_institutional_decision
ARCHIVE_AUTHORITY = provenance_and_contestation_memory
PERMISSION_RULE = moodle_capabilities_remain_authoritative
DIRECT_WRITE_RULE = external_systems_never_write_moodle_source_tables
```

## 3. Architecture decision

Do not add a new Moodle plugin for the first connected-mode integration pass.

Use the existing UCKK distribution:

```text
local_uckk
  -> optional Konnaxion bridge, endpoint client, identity mapper, object mapper, sync logs, configuration

mod_uckkassembly
  -> Assembly workflow, optional Smart Vote reading panel, motion-to-target workflow, decision publication

mod_uckkarchive
  -> immutable archive snapshots of Smart Vote readings and final decisions

tool_uckkintegrity
  -> Smart Vote anomaly/dispute/correction workflow

report_uckk
  -> read-only reports on Smart Vote readings, Assembly decisions, archive status, and integrity flags
```

A separate plugin such as `local_uckkkonnaxion` may be introduced only if the bridge becomes independently distributable. Until then, `local_uckk` is the correct owner because it already owns shared services, institutional registry, and cross-plugin coordination.

Connected-mode services must degrade safely. If Konnaxion is disabled, unavailable, misconfigured, or unauthorized, UCKK-Moodle must continue to support non-Smart-Vote Assembly workflows.

## 4. Optional Moodle settings

When connected mode is implemented, add these settings to `local_uckk/settings.php`:

```text
enable_konnaxion_bridge
konnaxion_base_url
konnaxion_api_client_id
konnaxion_api_secret
konnaxion_api_timeout_seconds
konnaxion_verify_tls
konnaxion_fail_closed
konnaxion_log_metadata
konnaxion_log_payloads
konnaxion_retention_days
konnaxion_pseudonymize_user_ids
smartvote_enabled
smartvote_default_modality
smartvote_allowed_assembly_types
smartvote_require_archive_snapshot
smartvote_require_integrity_review_on_anomaly
smartvote_allow_proxy_vote_from_moodle
smartvote_allow_external_vote_portal
smartvote_import_requires_review
smartvote_archive_on_decision_publish
smartvote_max_result_age_seconds
```

Default values:

```text
enable_konnaxion_bridge = false
smartvote_enabled = false
konnaxion_fail_closed = true
konnaxion_log_payloads = false
smartvote_import_requires_review = true
smartvote_archive_on_decision_publish = true
```

Security rules:

```text
API secrets must use Moodle secure/password config handling.
Secrets must never appear in reports, debug output, seed logs, archive exports, exception pages, events, AI prompts, or AI logs.
Payload logging is disabled by default.
If payload logging is enabled, privacy-provider export/deletion rules must cover it.
```

## 5. Optional connected-mode Moodle capabilities

Capabilities must use the canonical `CAP_*` variables from the root doctrine and roles/security registry.

### `local_uckk`

The current code snapshot implements a generic integration-administration capability:

```text
CAP_MANAGE_KONNAXION      = local/uckk:manageintegrations
CAP_MAP_KONNAXION_OBJECTS = local/uckk:manageintegrations
CAP_VIEW_KONNAXION_LOGS   = local/uckk:manageintegrations
```

Narrower Konnaxion capabilities may be introduced later only through a coordinated migration in `DOC_05`, `DOC_11`, `db/access.php`, presets, language strings, services/pages, PHPUnit tests, and Behat tests. Until then, this contract must not claim that `local/uckk:managekonnaxion`, `local/uckk:mapkonnaxionobjects`, or `local/uckk:viewkonnaxionlogs` are implemented executable capabilities.

### `mod_uckkassembly`

These capabilities are target connected-mode capabilities. They must not be required for standalone-core Assembly workflows until implemented in `db/access.php`, presets, language strings, services/pages, PHPUnit tests, and Behat tests.

```text
CAP_REQUEST_SMART_VOTE = mod/uckkassembly:requestsmartvote
CAP_VIEW_SMART_VOTE    = mod/uckkassembly:viewsmartvote
CAP_REVIEW_SMART_VOTE  = mod/uckkassembly:reviewsmartvote
CAP_CONTEST_SMART_VOTE = mod/uckkassembly:contestsmartvote
```

### `mod_uckkarchive`

This is a target connected-mode capability. It must not be required for standalone-core Archive workflows until implemented.

```text
CAP_ARCHIVE_SMART_VOTE = mod/uckkarchive:archivesmartvote
```

### `report_uckk`

This is a target connected-mode capability. It must not be required for standalone-core reports until implemented.

```text
CAP_VIEW_SMART_VOTE_REPORTS = report/uckk:viewsmartvotereports
```

Do not use drifting, premature, or misspelled capability names as active values. These names may appear only in `DOC_11` deprecated-alias registry or explicit migration notes:

```text
local/uckk:managekonnaxion
local/uckk:configurekonnaxion
local/uckk:managekonnaxionmappings
local/uckk:mapkonnaxionobjects
local/uckk:viewkonnaxionlogs
local/uckk:viewkonnaxionstatus
mod/uckkassembly:opensmartvote
mod/uckkassembly:viewsmarkvotereading
mod/uckkassembly:viewsmartvotereading
mod/uckkassembly:usesmartvote
mod/uckkassembly:importsmartvoteresult
mod/uckkassembly:exportsmartvotetarget
mod/uckkassembly:exportsmartvotesnapshot
mod/uckkassembly:contestsmartvotereading
mod/uckkarchive:viewsmartvotesnapshot
mod/uckkarchive:archivesmartvotesnapshot
report/uckk:viewsmartvote
report/uckk:exportsmartvote
report/uckk:viewkonnaxionlogs
```

The typo `mod/uckkassembly:viewsmarkvotereading` is explicitly banned.

Naming note: keep the public UI label as **Smart Vote**, but use lowercase Moodle capability names with `smartvote` as one word.

## 6. Optional connected-mode data model additions

These tables are part of `connected_konnaxion` mode. They are target connected-mode tables and must not be required for standalone UCKK-Moodle installation, upgrade, seed execution, tests, or operation until implemented in `DOC_04`, XMLDB, upgrade steps, privacy providers, presets, PHPUnit, and Behat.

### 6.1 `local_uckk_kx_user_map`

Owner: `local_uckk`  
Purpose: Map Moodle users/Joueurs to Konnaxion identities without exposing unnecessary external personal data.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `userid` | Moodle user id |
| `externalid` | Konnaxion user id or stable pseudonymous id |
| `externaltype` | Konnaxion object type, normally `user` |
| `externalhash` | Hash used for reports/exports when raw external id should not be exposed |
| `mappingstatus` | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` |
| `verifiedby` | Moodle user id that verified the map |
| `timeverified` | Verification timestamp |
| `sourceversion` | Konnaxion source version/revision/timestamp where available |
| `provenancehash` | Integrity hash for mapping provenance where needed |
| `metadata` | JSON for mapping source, consent marker, or external reference |
| `timecreated` | Creation timestamp |
| `timemodified` | Modification timestamp |

Privacy: personal data; full privacy-provider coverage required.

### 6.2 `local_uckk_kx_object_map`

Owner: `local_uckk`  
Purpose: Map Moodle-side objects to Konnaxion objects and Smart Vote targets.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `component` | Moodle component, e.g. `mod_uckkassembly` |
| `objecttype` | `assembly`, `motion`, `decision`, `challenge`, `archive_item` |
| `objectid` | Moodle-side object id |
| `contextid` | Moodle context id |
| `externaltype` | Konnaxion object type, e.g. `Vote`, `VoteModality`, `VoteResult`, `IntegrationMapping` |
| `externalid` | External Konnaxion object id |
| `sourceversion` | External source version/revision/timestamp where available |
| `mappingdetails` | JSON matching Konnaxion `IntegrationMapping` expectations |
| `mappingstatus` | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` |
| `provenancehash` | Integrity hash for mapped record provenance |
| `createdby` | Moodle user id |
| `timecreated` | Creation timestamp |
| `timemodified` | Modification timestamp |

Privacy: may contain user ids and institutional object references; privacy-provider coverage required.

### 6.3 `local_uckk_kx_sync_log`

Owner: `local_uckk`  
Purpose: Log Konnaxion sync attempts, retries, failures, endpoint responses, and idempotency keys without leaking secrets.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `operation` | Sync operation name |
| `component` | Moodle component initiating or receiving the sync |
| `objecttype` | Moodle object type |
| `objectid` | Moodle object id |
| `externaltype` | Konnaxion object type |
| `externalid` | Konnaxion object id |
| `syncstatus` | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled` |
| `attemptno` | Attempt number |
| `idempotencykey` | Stable idempotency key |
| `httpstatus` | HTTP status or adapter status |
| `errorcode` | Normalized error code |
| `errormessage` | Redacted error message |
| `metadata` | Redacted sync metadata |
| `timecreated` | Creation timestamp |
| `timemodified` | Modification timestamp |

Privacy: operational logs may contain personal-data references; retention and redaction rules are required.

### 6.4 `uckkassembly_kx_vote_target`

Owner: `mod_uckkassembly`  
Purpose: Map Assembly motions, decisions, or deliberation objects to Konnaxion vote targets.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `assemblyid` | Assembly instance id |
| `motionid` | Motion id, nullable for Assembly-level readings |
| `contextid` | Moodle context id |
| `objectmapid` | Link to `local_uckk_kx_object_map` |
| `targettype` | Stable target type |
| `targetid` | Stable target id |
| `modality` | Smart Vote modality name |
| `targetstatus` | `draft`, `mapped`, `active`, `closed`, `archived`, `contested` |
| `createdby` | Moodle user id |
| `timecreated` | Creation timestamp |
| `timemodified` | Modification timestamp |

### 6.5 `uckkassembly_sv_snapshot`

Owner: `mod_uckkassembly`  
Purpose: Store imported Smart Vote result snapshots without duplicating every Konnaxion vote record.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `assemblyid` | Assembly instance id |
| `motionid` | Motion id |
| `contextid` | Moodle context id |
| `votetargetid` | Link to `uckkassembly_kx_vote_target` |
| `externaltype` | Konnaxion result object type, e.g. `VoteResult` |
| `externalid` | External Konnaxion result id |
| `sourceversion` | External result version/revision/timestamp |
| `raw_data` | Imported or referenced source facts before interpretation, redacted as configured |
| `reading_method` | Vote modality, parameters, thresholds, calculation notes |
| `computed_reading` | Non-sovereign Smart Vote output |
| `expertise_weight` | Weighted-reading factor where applicable; sensitive when linkable to a user |
| `minority_report` | Dissenting or alternative interpretation where applicable |
| `integrity_warning` | Flags, anomalies, excluded votes summary, warnings |
| `contestation_status` | Contestation state for the snapshot |
| `archiveitemid` | Archive item id once archived |
| `provenancehash` | Hash of canonical snapshot payload |
| `fetchedby` | Moodle user id that imported the snapshot |
| `timefetched` | Import timestamp |
| `snapshotstatus` | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` |

Privacy: personal or derived personal data may exist in snapshot payloads; privacy provider must export/delete/redact according to visibility, retention, and archive rules.

### 6.6 `uckkassembly_sv_result_audit`

Owner: `mod_uckkassembly`  
Purpose: Store review, correction, contestation, and supersession trail for Smart Vote readings.

| Field | Purpose |
|---|---|
| `id` | Primary key |
| `snapshotid` | Link to `uckkassembly_sv_snapshot` |
| `auditstatus` | `recorded`, `reviewed`, `corrected`, `contested`, `resolved` |
| `action` | Review/correction/contestation action |
| `reason` | Human-readable reason |
| `changedby` | Moodle user id |
| `timecreated` | Creation timestamp |
| `metadata` | Redacted JSON metadata |

## 7. Target naming contract

Use stable target identifiers.

```text
targettype = uckk_assembly_motion
targetid   = moodle:{siteid}:mod_uckkassembly:{cmid}:motion:{motionid}
```

For Assembly-level readings:

```text
targettype = uckk_assembly
targetid   = moodle:{siteid}:mod_uckkassembly:{cmid}:assembly:{assemblyid}
```

For Challenge validation readings, only if later enabled:

```text
targettype = uckk_challenge_submission
targetid   = moodle:{siteid}:mod_uckkchallenge:{cmid}:submission:{submissionid}
```

The mapping must also be registered in Konnaxion `IntegrationMapping` using an adapter-safe payload such as:

```json
{
  "module_name": "uckk_moodle",
  "context_type": "assembly_motion",
  "mapping_details": {
    "moodle_site_id": "...",
    "component": "mod_uckkassembly",
    "cmid": "...",
    "assemblyid": "...",
    "motionid": "...",
    "contextid": "...",
    "callback_policy": "moodle_authority_archive_snapshot"
  }
}
```

## 8. Smart Vote workflow

### 8.1 Preparation

```text
1. Assembly enters deliberation or voting_or_reading state.
2. Authorized user with mod/uckkassembly:requestsmartvote selects Smart Vote as a reading method.
3. Moodle checks assembly type, context, capability, state transition, and integrity status.
4. local_uckk creates or resolves the Konnaxion object mapping.
5. Moodle sends the mapped target, modality, voting window, eligibility policy, and metadata to Konnaxion.
6. Assembly records event: smart_vote_reading_requested.
```

### 8.2 Vote collection

Two modes are allowed when connected mode is enabled:

```text
proxy_vote_from_moodle
external_vote_portal
```

`proxy_vote_from_moodle` means Moodle collects the user's intent after Moodle capability/session checks and transmits it to Konnaxion.

`external_vote_portal` means Moodle sends participants to Konnaxion; Moodle later imports result snapshots.

Both modes require identity mapping, consent/notice where required, and audit events.

### 8.3 Result import

```text
1. Authorized user requests result import or scheduled task imports after voting window closes.
2. local_uckk fetches Konnaxion VoteResult and modality metadata.
3. mod_uckkassembly validates the result against the current motion, mapping, context, expected source version, and target id.
4. Moodle stores a uckkassembly_sv_snapshot.
5. Assembly displays the result as a Smart Vote reading, not a final decision.
6. Assembly records event: smart_vote_snapshot_imported.
```

### 8.4 Decision publication

A final UCKK decision must still include:

```text
motion reference
decision text
decision method
participants
raw reading where available
Smart Vote weighted reading where used
reasoning
evidence used
unresolved objections
minority report if applicable
appeal/contestation path
archive link
```

Smart Vote results cannot automatically call `decision_published`.

### 8.5 Archive output

Archive must include, when Smart Vote is used:

```text
Assembly metadata
motion metadata
Smart Vote target mapping
vote modality
raw vote count or source reference
weighted result
quorum summary
integrity/anomaly flags
snapshot hash
import timestamp
final UCKK decision
minority report
contestation window
archive visibility
retention classification
```

The archive item must not expose raw Konnaxion vote records unless the configured visibility and privacy rules explicitly allow it.

Standalone archive records may exist without any Smart Vote snapshot. Absence of Smart Vote is valid provenance, not a missing record.

## 9. Optional connected-mode classes

These classes are required only for the connected Konnaxion profile. They must not prevent standalone installation when the Konnaxion bridge is disabled.

### 9.1 `local_uckk`

```text
local_uckk\service\konnaxion_client
local_uckk\service\konnaxion_mapping_service
local_uckk\service\konnaxion_sync_service
local_uckk\external\create_konnaxion_mapping
local_uckk\external\get_konnaxion_mapping
local_uckk\external\sync_konnaxion
local_uckk\event\konnaxion_mapping_created
local_uckk\event\konnaxion_sync_completed
```

### 9.2 `mod_uckkassembly`

```text
mod_uckkassembly\local\smartvote_reading
mod_uckkassembly\local\smartvote_snapshot_repository
mod_uckkassembly\external\request_smart_vote_reading
mod_uckkassembly\external\import_smart_vote_snapshot
mod_uckkassembly\external\contest_smart_vote_snapshot
mod_uckkassembly\event\smart_vote_reading_requested
mod_uckkassembly\event\smart_vote_snapshot_imported
mod_uckkassembly\event\smart_vote_snapshot_contested
mod_uckkassembly\event\smart_vote_snapshot_archived
mod_uckkassembly\output\smartvote_panel
mod/uckkassembly/templates/smartvote_panel.mustache
mod/uckkassembly/amd/src/smartvote.js
```

### 9.3 `mod_uckkarchive`

```text
mod_uckkarchive\local\smartvote_snapshot_exporter
mod_uckkarchive\event\smart_vote_snapshot_archived
```

### 9.4 `tool_uckkintegrity`

```text
tool_uckkintegrity\local\smartvote_anomaly_policy
```

### 9.5 `report_uckk`

```text
report_uckk\table\smartvote_readings_table
report_uckk\output\smartvote_report
```

## 10. External API contract assumptions

UCKK-Moodle must not assume Konnaxion internal table names are direct API contracts. The bridge must use stable Konnaxion API endpoints, an approved Konnaxion SDK, or an explicit adapter layer.

Minimum expected Konnaxion-side operations:

```text
GET  /api/smart-vote/modalities
POST /api/smart-vote/targets
POST /api/smart-vote/votes
GET  /api/smart-vote/results/{target_type}/{target_id}
POST /api/integration-mappings
GET  /api/ekoh/users/{id}/weights?domain=...
GET  /api/health
```

If Konnaxion exposes different routes, the Moodle bridge must isolate that difference inside `local_uckk\service\konnaxion_client`.

## 11. Failure and safety rules

| Situation | Required behavior |
|---|---|
| Konnaxion disabled | Hide or disable Smart Vote actions; ordinary Assembly workflow remains available. |
| Konnaxion unavailable | Do not fabricate result. Mark Smart Vote reading unavailable. Assembly may continue with a non-Smart-Vote method only if authorized and recorded. |
| Identity unmapped | User cannot cast Smart Vote until mapped or may cast only unweighted local Moodle vote if the Assembly permits it. |
| Result target mismatch | Reject import and open/offer integrity review. |
| Result stale | Warn and require authorized refresh before decision publication. |
| Anomaly detected | Pause use of result for final decision until Inquisiteur review, if configured. |
| Privacy export requested | Export Moodle-side mapping and snapshots; do not export Konnaxion-side records unless Konnaxion API and consent/rights permit it. |
| Deletion request | Delete/anonymize Moodle mapping where allowed; preserve institutional archived decision snapshots according to retention policy. |
| Payload contains raw voter identities | Redact before display, report, and archive unless explicitly allowed by visibility and privacy rules. |

## 12. Required tests

### 12.1 Standalone regression tests

```text
Konnaxion disabled by default
UCKK-Moodle installs without Konnaxion credentials
seed tool can create standalone campus without Konnaxion mappings
ordinary Assembly workflow works without Smart Vote
Smart Vote panels/actions are hidden or disabled when Konnaxion is disabled
reports render without Smart Vote data
archives can preserve Assembly decisions without Smart Vote snapshots
```

### 12.2 Connected-mode PHPUnit

```text
local_uckk/tests/konnaxion_client_test.php
local_uckk/tests/konnaxion_mapping_service_test.php
local_uckk/tests/konnaxion_sync_service_test.php
local_uckk/tests/privacy/provider_konnaxion_test.php
mod_uckkassembly/tests/smartvote_reading_test.php
mod_uckkassembly/tests/smartvote_snapshot_test.php
mod_uckkassembly/tests/privacy/provider_smartvote_test.php
tool_uckkintegrity/tests/smartvote_anomaly_test.php
report_uckk/tests/smartvote_report_test.php
```

Required connected-mode coverage, only when connected-mode code is implemented or claimed as supported:

```text
authorized user can request Smart Vote reading
unauthorized user is denied
wrong context is denied
identity mapping is privacy-exportable
object mapping uses stable target identifiers
Konnaxion timeout fails safely
result import rejects target mismatch
result import rejects invalid modality
snapshot hash is reproducible
archive export redacts raw voter identity
final decision cannot auto-publish from Smart Vote alone
integrity anomaly can pause use of reading
```

### 12.3 Connected-mode Behat

```text
mod/uckkassembly/tests/behat/uckkassembly_smartvote.feature
local/uckk/tests/behat/konnaxion_bridge.feature
report/uckk/tests/behat/smartvote_report.feature
```

User workflow coverage:

```text
Gestionnaire enables and configures Konnaxion bridge
authorized user verifies identity/object mapping
authorized Assembly owner requests Smart Vote reading
Joueur casts Smart Vote or follows external vote link
authorized user imports Smart Vote result
Assembly publishes final decision using Smart Vote as one reading
minority report remains visible where policy requires it
Archive stores final decision and Smart Vote snapshot
unauthorized user cannot view restricted Smart Vote details
integrity reviewer contests or invalidates suspicious reading
```

## 13. Documentation patch list

Apply these changes to the UCKK-Moodle documentation set.

### 13.1 `00_master_execution_doctrine.md`

Declare:

```text
UCKK-Moodle is self-standing.
Konnaxion is an optional connected-mode integration.
Smart Vote is required only for connected_konnaxion acceptance, not standalone_core acceptance.
```

Split completion and final checks into:

```text
core standalone gate
optional Konnaxion-connected gate
```

### 13.2 `01_domain_boundaries_and_glossary.md`

Add boundary rows for:

```text
Konnaxion
EkoH
Smart Vote
standalone_core
connected_konnaxion
```

### 13.3 `02_distribution_architecture.md`

Place Konnaxion under optional Layer 6:

```text
Layer 6 — Optional external integrations
    └── Konnaxion v14 Smart Vote / EkoH bridge
```

Layer 6 must not be a hard dependency for standalone install, seed, Assembly, archive, integrity, report, or tests.

### 13.4 `03_plugin_specifications.md`

Add connected-mode Konnaxion bridge responsibilities to `local_uckk`.  
Add connected-mode Smart Vote reading support to `mod_uckkassembly`.  
Add connected-mode Smart Vote snapshot display to `report_uckk`.  
Split all Konnaxion classes into:

```text
core required classes
optional Konnaxion-connected classes
```

Add explicit rule:

```text
Smart Vote is a reading method, not an automatic decision publisher.
```

### 13.5 `04_data_model_and_storage.md`

Add optional connected-mode table rows:

```text
local_uckk_kx_user_map
local_uckk_kx_object_map
local_uckk_kx_sync_log
uckkassembly_kx_vote_target
uckkassembly_sv_snapshot
uckkassembly_sv_result_audit
```

Add privacy-provider and test rows for those tables.

### 13.6 `05_roles_permissions_and_security.md`

Use only the canonical capabilities listed in section 5 of this document, unless `DOC_00` is deliberately expanded.

Add security rule:

```text
External reputation, vote weight, Smart Vote result, Konnaxion role, or external identifier cannot grant Moodle authority by itself.
```

### 13.7 `06_pedagogy_courses_competencies_badges.md`

Clarify that Konnaxion/EkoH/Smart Vote signals are optional connected-mode advisory signals. They cannot auto-complete courses, auto-rate competencies, auto-award badges, or validate UCKK evidence.

### 13.8 `07_challenges_and_assemblies.md`

Add Smart Vote as a connected-mode Assembly reading method.  
Do not remove existing non-Smart-Vote voting/readings.

Add explicit standalone rule:

```text
Assembly workflows must work without Smart Vote enabled.
```

### 13.9 `08_integrity_archives_and_privacy.md`

Add Smart Vote readings and Konnaxion mappings to connected-mode provenance requirements.  
Standalone archives may preserve Assembly decisions without Smart Vote snapshots.

### 13.10 `09_integrations_reporting_delivery.md`

Split acceptance into:

```text
core standalone acceptance checklist
optional Konnaxion-connected acceptance checklist
```

Move Konnaxion bridge, Smart Vote import, Smart Vote snapshots, Smart Vote reports, outage behavior, and Konnaxion privacy coverage into the connected-mode checklist.

### 13.11 `11_cross_doc_alignment_registry.md`

Ensure `DOC_11` remains the canonical registry for:

```text
implemented_now vs target_connected_mode
current executable capabilities
target connected-mode capabilities
deprecated aliases
standalone-vs-connected conditionality
```

This contract must follow `DOC_11`; it must not introduce a conflicting capability, table, service, event, or preset name.

### 13.12 `12_current_code_snapshot_gap_report.md`

Before code revision, classify each gap as one of:

```text
CODE_FIX_STANDALONE_BLOCKER
CODE_FIX_CORE_COMPLETION
DOC_GATE_CLARIFICATION
CONNECTED_MODE_DEFERRED
```

Konnaxion and Smart Vote target items belong in `CONNECTED_MODE_DEFERRED` unless the connected profile is explicitly selected for the current code pass.

### 13.13 Presets

Add connected-mode entries only where the corresponding preset file exists.

Navigation entry:

```json
{
  "key": "uckk.smartvote",
  "label_fr": "Lectures Smart Vote",
  "label_en": "Smart Vote Readings",
  "component": "mod_uckkassembly",
  "capability": "mod/uckkassembly:viewsmartvote",
  "requires": {
    "enable_konnaxion_bridge": true,
    "smartvote_enabled": true
  }
}
```

Capability presets must use the canonical capabilities from section 5.

## 14. Connected-mode acceptance gate

The Konnaxion Smart Vote bridge is not accepted until all connected-mode checks pass. These checks do not block `standalone_core` acceptance unless the release claims `connected_konnaxion` support:

```text
[ ] UCKK-Moodle still installs and operates with Konnaxion disabled.
[ ] Konnaxion can be enabled/disabled from Moodle admin settings.
[ ] Konnaxion bridge is disabled safely by default.
[ ] Moodle can perform a Konnaxion health check without exposing secrets.
[ ] Moodle user ↔ Konnaxion user mapping works and is privacy-covered.
[ ] Moodle object ↔ Konnaxion object mapping works.
[ ] Assembly motion ↔ Konnaxion Smart Vote target mapping works.
[ ] Smart Vote reading can be requested only by authorized users.
[ ] Result import validates target, modality, context, source version, hash, and state.
[ ] Smart Vote result is displayed as a reading, not a final decision.
[ ] Final decision publication still requires Moodle capability and state checks.
[ ] Archive snapshot stores method, result, hash, provenance, visibility, and contestation path.
[ ] Minority report and contestation path remain available.
[ ] Integrity reviewer can pause, contest, or invalidate a reading.
[ ] Reports enforce context, capability, visibility, and redaction rules.
[ ] Privacy export/delete covers mappings, snapshots, logs, and user references.
[ ] Konnaxion outage fails safely without fabricating or silently accepting results.
[ ] PHPUnit and Behat coverage exists for all critical connected-mode paths.
```

## 15. Final compatibility statement

UCKK-Moodle is self-standing. Konnaxion is an optional connected-mode integration that can add Smart Vote readings, EkoH/advisory signals, and better cross-module organization. Konnaxion never replaces Moodle permissions, UCKK academic authority, Assembly decisions, Archive provenance, Integrity review, privacy rules, or AI non-sovereignty rules.
