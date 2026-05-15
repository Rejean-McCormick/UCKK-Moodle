# 09 — Integrations, Reporting and Delivery

**Status:** Final delivery specification with standalone/connected-mode alignment  
**Purpose:** Define UCKK-Moodle standalone delivery, optional Konnaxion Smart Vote connected-mode integration, AI integration, reporting, seed execution, testing, release, implementation-readiness gates, canonical variable resolution, and acceptance.

## 0. Canonical variables consumed by this document

This document consumes the canonical variables defined by `00_master_execution_doctrine.md` and `11_cross_doc_alignment_registry.md`. It may add delivery detail, but it must not rename, invert, or silently redefine the root variables.

Shared document paths, operating modes, capability aliases, table names, service names, deprecated names, and standalone-vs-connected requirements are resolved by `DOC_11`. This document must not override `DOC_11`.

### 0.1 Document variables

| Variable | Canonical value | Use in this document |
|---|---|---|
| `DOC_00` | `00_master_execution_doctrine.md` | Root doctrine and correction authority. |
| `DOC_04` | `04_data_model_and_storage.md` | Canonical table, enum, state-machine, and storage definitions. |
| `DOC_05` | `05_roles_permissions_and_security.md` | Canonical capability registry. |
| `DOC_07` | `07_challenges_and_assemblies.md` | Canonical Assembly, Smart Vote, decision, and contestation workflows. |
| `DOC_08` | `08_integrity_archives_and_privacy.md` | Canonical archive, privacy, retention, redaction, and integrity rules. |
| `DOC_09` | `09_integrations_reporting_delivery.md` | This integration, reporting, delivery, testing, and acceptance document. |
| `DOC_10` | `10_konnaxion_smart_vote_integration_contract.md` | Optional Konnaxion Smart Vote connected-mode integration contract. |
| `DOC_11` | `11_cross_doc_alignment_registry.md` | Cross-document variable, alias, implementation-status, and drift registry. |
| `LEGACY_DOC_10` | `10_implementation_correction_plan.md` | Deprecated. Must not be used as the active product-tree target. |

### 0.2 Source-family variables

| Variable | Canonical value | Rule |
|---|---|---|
| `SOURCE_FAMILY_UCKK_CANON` | `UCKK canon` | Governs UCKK meaning, pedagogy, governance, and symbolic boundaries. |
| `SOURCE_FAMILY_KONNAXION` | `Konnaxion external source family` | External source of truth for Konnaxion-side Smart Vote objects and semantics. |
| `SOURCE_FAMILY_MOODLE_DOCS` | `Moodle developer documentation` | Governs Moodle implementation mechanics. |
| `EXTERNAL_SYSTEM_KONNAXION` | `Konnaxion` | External system integrated only through Moodle services. |
| `EXTERNAL_SIGNAL_EKOH` | `EkoH` | External ecosystem context or signal source only where explicitly mapped. |

### 0.3 Boundary variables

| Variable | Canonical value |
|---|---|
| `PRODUCT_SCOPE` | `UCKK-Moodle is the Moodle campus implementation of UCKK.` |
| `SMART_VOTE_CANONICAL_RULE` | `Konnaxion computes Smart Vote readings. UCKK-Moodle owns Assembly decisions. Archives preserve both, with provenance and contestability.` |
| `SMART_VOTE_AUTHORITY` | `computed_reading_only` |
| `ASSEMBLY_AUTHORITY` | `human_institutional_decision` |
| `ARCHIVE_AUTHORITY` | `provenance_and_contestation_memory` |
| `DIRECT_WRITE_RULE` | `external_systems_never_write_moodle_source_tables` |
| `PERMISSION_RULE` | `moodle_capabilities_remain_authoritative` |
| `OPERATING_MODE_STANDALONE` | `standalone_core` |
| `OPERATING_MODE_KONNAXION_CONNECTED` | `connected_konnaxion` |
| `KONNAXION_REQUIRED_FOR_CORE` | `false` |
| `SMART_VOTE_REQUIRED_FOR_CORE` | `false` |
| `KONNAXION_DEFAULT_STATE` | `disabled` |
| `SMART_VOTE_DEFAULT_STATE` | `disabled unless Konnaxion bridge is enabled` |

### 0.4 Plugin ownership variables

| Variable | Canonical owner | Responsibilities |
|---|---|---|
| `KONNAXION_BRIDGE_OWNER` | `local_uckk` | Configuration, authentication settings, endpoint client, object mapping, sync logs, shared integration services. |
| `SMART_VOTE_WORKFLOW_OWNER` | `mod_uckkassembly` | Smart Vote reading requests, vote-target mapping, snapshots, review, contestation, and Assembly linkage. |
| `ASSEMBLY_DECISION_OWNER` | `mod_uckkassembly` | Motions, deliberation, decision publication, minority report, and decision contestability. |
| `SMART_VOTE_ARCHIVE_OWNER` | `mod_uckkarchive` | Archive records and file areas for Smart Vote snapshots, decisions, minutes, and provenance packages. |
| `SMART_VOTE_REPORT_OWNER` | `report_uckk` | Smart Vote reports, institutional exports, filters, and privacy-aware visibility. |
| `SMART_VOTE_INTEGRITY_OWNER` | `tool_uckkintegrity` | Integrity warnings, contested readings, correction cases, and restricted review workflows. |
| `SMART_VOTE_SEED_OWNER` | `tool_uckkseed` | Idempotent presets for capabilities, mappings, report definitions, and integration defaults. |
| `SMART_VOTE_PRIVACY_OWNER` | every storing plugin | Each plugin that stores personal data owns its privacy provider, export, delete, anonymisation, and retention tests. |

### 0.5 Konnaxion object variables

| Variable | Canonical value | Moodle-side use |
|---|---|---|
| `KONNAXION_OBJECT_VOTE` | `Vote` | External vote object mapped to a Moodle-side vote target or reading source. |
| `KONNAXION_OBJECT_VOTE_MODALITY` | `VoteModality` | External voting modality/method mapped to a Moodle-side reading method. |
| `KONNAXION_OBJECT_VOTE_RESULT` | `VoteResult` | External result mapped into a Moodle-side Smart Vote reading snapshot. |
| `KONNAXION_OBJECT_INTEGRATION_MAPPING` | `IntegrationMapping` | External mapping object mirrored by Moodle-side mapping tables. |
| `KONNAXION_EXTERNAL_ID_FIELD` | `externalid` | Stores the Konnaxion identifier; never stores secrets. |
| `KONNAXION_EXTERNAL_TYPE_FIELD` | `externaltype` | Stores the Konnaxion object type. |
| `KONNAXION_SOURCE_VERSION_FIELD` | `sourceversion` | Stores the external source version, revision, or timestamp where available. |
| `KONNAXION_SYNC_STATUS_FIELD` | `syncstatus` | Stores Moodle-side sync state using the canonical sync enum. |
| `KONNAXION_PROVENANCE_HASH_FIELD` | `provenancehash` | Stores integrity hash for imported snapshots or mapped records where needed. |

Additional Konnaxion objects such as `UserExpertiseScore`, `UserEthicsScore`, `ConfidentialitySetting`, and `ScoreHistory` may be consumed only as mapped metadata. They do not become Moodle authority and must not be stored without explicit privacy, retention, visibility, and contestation rules.

### 0.6 Moodle-side Konnaxion table variables

| Variable | Canonical table | Owner | Purpose |
|---|---|---|---|
| `TABLE_KONNAXION_USER_MAP` | `local_uckk_kx_user_map` | `local_uckk` | Maps Moodle users to Konnaxion identities without exposing unnecessary external personal data. |
| `TABLE_KONNAXION_OBJECT_MAP` | `local_uckk_kx_object_map` | `local_uckk` | Maps Moodle objects to Konnaxion objects. |
| `TABLE_KONNAXION_SYNC_LOG` | `local_uckk_kx_sync_log` | `local_uckk` | Logs sync attempts, failures, retries, endpoint responses, and idempotency keys. |
| `TABLE_SMART_VOTE_TARGET_MAP` | `uckkassembly_kx_vote_target` | `mod_uckkassembly` | Maps Assembly motions, decisions, or deliberation objects to Konnaxion vote targets. |
| `TABLE_SMART_VOTE_SNAPSHOT` | `uckkassembly_sv_snapshot` | `mod_uckkassembly` | Stores Moodle-side immutable Smart Vote reading snapshots. |
| `TABLE_SMART_VOTE_RESULT_AUDIT` | `uckkassembly_sv_result_audit` | `mod_uckkassembly` | Stores review, correction, contestation, and supersession trail for Smart Vote results. |

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`.

### 0.7 Smart Vote reading variables

| Variable | Canonical field/key | Rule |
|---|---|---|
| `SV_RAW_DATA` | `raw_data` | Imported or referenced source facts before interpretation. |
| `SV_READING_METHOD` | `reading_method` | The declared method, modality, weighting, or algorithmic reading rule. |
| `SV_COMPUTED_READING` | `computed_reading` | The non-sovereign Smart Vote output. |
| `SV_EXPERTISE_WEIGHT` | `expertise_weight` | Any weighted-reading factor; personal or sensitive where linkable to a user. |
| `SV_HUMAN_DECISION` | `human_institutional_decision` | Moodle-side Assembly decision; must not be overwritten by Smart Vote. |
| `SV_MINORITY_REPORT` | `minority_report` | Documented minority position or dissenting signal. |
| `SV_INTEGRITY_WARNING` | `integrity_warning` | Warning or flag that may open integrity review but is not itself a sanction. |
| `SV_CONTESTATION_STATUS` | `contestation_status` | Contestation state for the snapshot or decision linkage. |
| `SV_ARCHIVE_ITEM_ID` | `archiveitemid` | Link to preserved archive item when archived. |

### 0.8 Status and state variables

| Variable | Allowed values | Owner |
|---|---|---|
| `KONNAXION_SYNC_STATUS_ENUM` | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled` | `local_uckk` |
| `KONNAXION_MAPPING_STATUS_ENUM` | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` | `local_uckk` |
| `SMART_VOTE_TARGET_STATUS_ENUM` | `draft`, `mapped`, `active`, `closed`, `archived`, `contested` | `mod_uckkassembly` |
| `SMART_VOTE_SNAPSHOT_STATUS_ENUM` | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` | `mod_uckkassembly` |
| `SMART_VOTE_AUDIT_STATUS_ENUM` | `recorded`, `reviewed`, `corrected`, `contested`, `resolved` | `mod_uckkassembly` |

### 0.9 Capability variables

| Variable | Canonical capability | Owner |
|---|---|---|
| `CAP_MANAGE_KONNAXION` | `local/uckk:manageintegrations` | `local_uckk` |
| `CAP_MAP_KONNAXION_OBJECTS` | `local/uckk:manageintegrations` | `local_uckk` |
| `CAP_VIEW_KONNAXION_LOGS` | `local/uckk:manageintegrations` | `local_uckk` |
| `CAP_REQUEST_SMART_VOTE` | `mod/uckkassembly:requestsmartvote` | `mod_uckkassembly` |
| `CAP_VIEW_SMART_VOTE` | `mod/uckkassembly:viewsmartvote` | `mod_uckkassembly` |
| `CAP_REVIEW_SMART_VOTE` | `mod/uckkassembly:reviewsmartvote` | `mod_uckkassembly` |
| `CAP_CONTEST_SMART_VOTE` | `mod/uckkassembly:contestsmartvote` | `mod_uckkassembly` |
| `CAP_ARCHIVE_SMART_VOTE` | `mod/uckkarchive:archivesmartvote` | `mod_uckkarchive` |
| `CAP_VIEW_SMART_VOTE_REPORTS` | `report/uckk:viewsmartvotereports` | `report_uckk` |

The first three Konnaxion capability variables intentionally map to the current implemented generic integration capability, `local/uckk:manageintegrations`, until narrower Konnaxion-specific capabilities are added to code and presets. Narrower aliases such as `local/uckk:managekonnaxion`, `local/uckk:mapkonnaxionobjects`, and `local/uckk:viewkonnaxionlogs` must not be treated as implemented capabilities unless `DOC_11`, `DOC_05`, `db/access.php`, presets, language strings, PHPUnit access tests, and Behat visibility tests are updated together.

Additional split capabilities may be added only if they are added to `DOC_05`, `DOC_11`, `presets/capabilities.json`, language strings, PHPUnit access tests, and Behat visibility tests. They must not replace the canonical capabilities above without a documented doctrine update.

### 0.10 Service and event variables

| Variable | Canonical name | Owner |
|---|---|---|
| `SERVICE_CREATE_KONNAXION_MAPPING` | `local_uckk_create_konnaxion_mapping` | `local_uckk` |
| `SERVICE_GET_KONNAXION_MAPPING` | `local_uckk_get_konnaxion_mapping` | `local_uckk` |
| `SERVICE_SYNC_KONNAXION` | `local_uckk_sync_konnaxion` | `local_uckk` |
| `SERVICE_REQUEST_SMART_VOTE_READING` | `mod_uckkassembly_request_smart_vote_reading` | `mod_uckkassembly` |
| `SERVICE_IMPORT_SMART_VOTE_SNAPSHOT` | `mod_uckkassembly_import_smart_vote_snapshot` | `mod_uckkassembly` |
| `SERVICE_CONTEST_SMART_VOTE_SNAPSHOT` | `mod_uckkassembly_contest_smart_vote_snapshot` | `mod_uckkassembly` |
| `SERVICE_GET_SMART_VOTE_REPORT` | `report_uckk_get_smart_vote_report` | `report_uckk` |
| `EVENT_KONNAXION_MAPPING_CREATED` | `local_uckk\event\konnaxion_mapping_created` | `local_uckk` |
| `EVENT_KONNAXION_SYNC_COMPLETED` | `local_uckk\event\konnaxion_sync_completed` | `local_uckk` |
| `EVENT_SMART_VOTE_READING_REQUESTED` | `mod_uckkassembly\event\smart_vote_reading_requested` | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_IMPORTED` | `mod_uckkassembly\event\smart_vote_snapshot_imported` | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_CONTESTED` | `mod_uckkassembly\event\smart_vote_snapshot_contested` | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_ARCHIVED` | `mod_uckkassembly\event\smart_vote_snapshot_archived` | `mod_uckkassembly` |

### 0.11 Preset registry variables

| Variable | Canonical preset | Required by |
|---|---|---|
| `PRESET_CAPABILITIES` | `presets/capabilities.json` | Roles, capabilities, tests, seed tool. |
| `PRESET_STATE_MACHINES` | `presets/state_machines.json` | Workflow statuses and transitions. |
| `PRESET_EVENTS` | `presets/events.json` | Event-class registry and tests. |
| `PRESET_KONNAXION_MAPPINGS` | `presets/konnaxion_mappings.json` | Connected-mode Konnaxion object map defaults and validation fixtures only. |
| `PRESET_PRIVACY_RETENTION` | `presets/privacy_retention.json` | Privacy, export, deletion, anonymisation, redaction, and retention tests. |
| `PRESET_EXPORTS` | `presets/exports.json` | Report/export definitions and acceptance evidence. |

## 1. Integration principle

UCKK-Moodle may connect to external systems, but Moodle remains the campus authority for learning records, capabilities, workflows, evidence, archives, reports, privacy behavior, and institutional decisions.

External systems may assist. They must not silently replace Moodle records.

No external integration may bypass:

```text
Moodle capabilities
Moodle contexts
UCKK provenance rules
archive rules
integrity review
privacy export/delete behavior
Moodle event logging
Assembly decision workflow
contestation workflow
retention policy
```

Canonical external-authority rule:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

This document uses `EXTERNAL_SYSTEM_KONNAXION` as an external source, not as a Moodle authority.

UCKK-Moodle has two delivery profiles:

```text
standalone_core
connected_konnaxion
```

`standalone_core` is the mandatory product baseline. It must install, seed, teach, deliberate, archive, report, enforce privacy, and pass tests without Konnaxion.

`connected_konnaxion` is an optional integration profile. It adds Konnaxion Smart Vote readings, EkoH/advisory signals where explicitly mapped, Konnaxion sync/mapping services, and connected-mode reports. It must not be required for core installation or ordinary UCKK-Moodle operation.

## 2. Optional Konnaxion Smart Vote connected-mode integration

All requirements in this section apply to `connected_konnaxion`. They are not release blockers for `standalone_core` unless the connected profile is enabled, packaged, or claimed as supported.

In `standalone_core`, Konnaxion and Smart Vote UI actions must be hidden or disabled, Konnaxion sync must not run, and missing Konnaxion configuration must not block Moodle installation, seed execution, Assembly decisions, Archive preservation, Integrity review, reporting, privacy export/delete, or tests.

### 2.1 Component ownership

When `connected_konnaxion` is enabled, the Konnaxion bridge is owned by:

```text
KONNAXION_BRIDGE_OWNER = local_uckk
```

In `connected_konnaxion`, the bridge is consumed by:

```text
SMART_VOTE_WORKFLOW_OWNER = mod_uckkassembly
SMART_VOTE_ARCHIVE_OWNER = mod_uckkarchive
SMART_VOTE_INTEGRITY_OWNER = tool_uckkintegrity
SMART_VOTE_REPORT_OWNER = report_uckk
SMART_VOTE_SEED_OWNER = tool_uckkseed
```

Do not create a separate plugin for the first integration pass unless the Konnaxion bridge becomes independently distributable.

Until then, `local_uckk` owns:

```text
Konnaxion configuration
API access
identity mapping
object mapping
sync log
endpoint client
shared service contracts
authentication settings
timeout and failure behavior
retry and idempotency policy
```

`mod_uckkassembly` owns:

```text
Smart Vote reading request
vote-target mapping
snapshot import
reading review
reading contestation
Assembly linkage
decision separation
```

`mod_uckkarchive` owns:

```text
Smart Vote archive package
snapshot archive item
decision archive item
minutes archive item
provenance package
visibility and retention enforcement
```

`tool_uckkintegrity` owns:

```text
integrity warnings
anomaly review
restricted correction workflow
invalidated reading trail
privacy/integrity escalation
```

`report_uckk` owns:

```text
Smart Vote institutional reports
Smart Vote export views
privacy-aware report filters
delivery evidence exports
```

### 2.2 Integration boundary

Konnaxion is an external collective-intelligence and Smart Vote system.

UCKK-Moodle may use Konnaxion for:

```text
vote modalities
vote collection or vote import
raw vote counts
weighted vote readings
EkoH-derived weight metadata where authorized
Smart Vote result snapshots
integration mapping identifiers
anomaly metadata
external result provenance
```

Konnaxion must not become Moodle authority.

Konnaxion must not directly perform these UCKK-Moodle actions:

```text
publish Assembly decisions
award Moodle badges
certify competencies
close integrity cases
modify archive records
modify challenge status
modify course completion
change Moodle roles or capabilities
silently create Moodle records
silently delete Moodle records
silently rewrite Moodle records
bypass Moodle privacy export/delete behavior
bypass Moodle event logging
write directly into Moodle source tables
```

The canonical direct-write rule is:

```text
DIRECT_WRITE_RULE = external_systems_never_write_moodle_source_tables
```

The canonical permission rule is:

```text
PERMISSION_RULE = moodle_capabilities_remain_authoritative
```

### 2.3 Konnaxion objects recognized by UCKK-Moodle

UCKK-Moodle must recognize the following Konnaxion-side objects through a stable API, approved SDK, or dedicated adapter:

```text
KONNAXION_OBJECT_VOTE = Vote
KONNAXION_OBJECT_VOTE_MODALITY = VoteModality
KONNAXION_OBJECT_VOTE_RESULT = VoteResult
KONNAXION_OBJECT_INTEGRATION_MAPPING = IntegrationMapping
```

Additional Konnaxion-side metadata objects may be consumed only when authorized and explicitly mapped:

```text
UserExpertiseScore
UserEthicsScore
ConfidentialitySetting
ScoreHistory
```

These additional objects are metadata sources only. They must not become Moodle authority and must not be stored unless the storing plugin implements:

```text
privacy metadata declaration
privacy export
deletion or anonymisation behavior
retention expiry
visibility restriction
audit event coverage
contestation or correction path
```

UCKK-Moodle must not query or mutate Konnaxion database tables directly.

Table names from Konnaxion documentation are semantic references only. The Moodle bridge must use:

```text
Konnaxion API endpoints
approved Konnaxion SDK
dedicated adapter class
documented mock adapter for tests
```

### 2.4 Smart Vote authority rule

Smart Vote is a reading method inside an Assembly workflow. It is not an automatic decision publisher.

Every Smart Vote use must preserve this separation:

| Layer | Variable | Owner | Meaning |
|---|---|---|---|
| Raw votes | `SV_RAW_DATA` | Konnaxion or Moodle proxy service | Individual or aggregated participation input. |
| Reading method | `SV_READING_METHOD` | Konnaxion and `mod_uckkassembly` | Declared modality, weighting, or reading method. |
| Computed reading | `SV_COMPUTED_READING` | Konnaxion | Non-sovereign Smart Vote output. |
| Assembly interpretation | `SV_HUMAN_DECISION` preparation layer | `mod_uckkassembly` | Human/institutional interpretation of the result. |
| Final decision | `ASSEMBLY_AUTHORITY` | `mod_uckkassembly` | UCKK decision after required capability and state checks. |
| Minority report | `SV_MINORITY_REPORT` | `mod_uckkassembly` and `mod_uckkarchive` | Documented dissenting or alternative interpretation. |
| Memory | `ARCHIVE_AUTHORITY` | `mod_uckkarchive` | Archived result, decision, provenance, minority report, and contestation path. |
| Integrity review | `SV_INTEGRITY_WARNING` | `tool_uckkintegrity` | Dispute, anomaly, correction, invalidation, or privacy review. |

Smart Vote may inform a decision.

Smart Vote must not:

```text
publish the final decision
award recognition
validate evidence
close contestation
override capability checks
replace Assembly reasoning
erase minority reports
hide integrity warnings
```

### 2.5 Connected-mode admin settings

`local_uckk` must define these settings when connected-mode support is included:

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

Secrets must use Moodle secure/password configuration handling.

Secrets must not appear in:

```text
reports
debug output
seed logs
archive exports
privacy exports except where legally required
exception pages
AI prompts
AI responses
Moodle event descriptions
Behat output
PHPUnit failure dumps
scheduled task output visible to non-admins
```

Payload logging must be disabled by default.

If payload logging is enabled, the privacy provider must cover:

```text
metadata declaration
export
deletion or anonymisation
retention expiry
restricted visibility
administrator warning
test coverage
```

### 2.6 Connected-mode capabilities

The canonical minimum Konnaxion and Smart Vote capabilities for `connected_konnaxion` are:

```text
CAP_MANAGE_KONNAXION           = local/uckk:manageintegrations
CAP_MAP_KONNAXION_OBJECTS      = local/uckk:manageintegrations
CAP_VIEW_KONNAXION_LOGS        = local/uckk:manageintegrations
CAP_REQUEST_SMART_VOTE         = mod/uckkassembly:requestsmartvote
CAP_VIEW_SMART_VOTE            = mod/uckkassembly:viewsmartvote
CAP_REVIEW_SMART_VOTE          = mod/uckkassembly:reviewsmartvote
CAP_CONTEST_SMART_VOTE         = mod/uckkassembly:contestsmartvote
CAP_ARCHIVE_SMART_VOTE         = mod/uckkarchive:archivesmartvote
CAP_VIEW_SMART_VOTE_REPORTS    = report/uckk:viewsmartvotereports
```

`tool_uckkintegrity` must add or map explicit integrity capabilities for:

```text
tool/uckkintegrity:reviewsmartvoteanomaly
tool/uckkintegrity:pausesmartvotereading
tool/uckkintegrity:invalidatesmartvotereading
```

If the implementation needs split capabilities for import, export, status viewing, or identity-map management, they must be added to the canonical capability registry and must not replace the canonical minimum capabilities.

Every capability must be:

```text
declared in the owning plugin's db/access.php
represented in DOC_05
represented in presets/capabilities.json
covered by language strings
covered by denied-path PHPUnit tests
covered by allowed-path PHPUnit tests
covered by Behat visibility tests
checked in every related page and service
```

### 2.7 Connected-mode Moodle-side mapping contract

In `connected_konnaxion`, UCKK-Moodle must maintain Moodle-side mappings for Konnaxion identities, objects, vote targets, snapshots, and sync logs.

The canonical storage definitions belong in `DOC_04`. This document defines delivery behavior.

Canonical Moodle-side tables are:

```text
TABLE_KONNAXION_USER_MAP       = local_uckk_kx_user_map
TABLE_KONNAXION_OBJECT_MAP     = local_uckk_kx_object_map
TABLE_KONNAXION_SYNC_LOG       = local_uckk_kx_sync_log
TABLE_SMART_VOTE_TARGET_MAP    = uckkassembly_kx_vote_target
TABLE_SMART_VOTE_SNAPSHOT      = uckkassembly_sv_snapshot
TABLE_SMART_VOTE_RESULT_AUDIT  = uckkassembly_sv_result_audit
```

Identity mapping must support:

```text
Moodle userid
Konnaxion externalid or stable pseudonymous id
externaltype
sourceversion
verification status
verification actor
verification timestamp
revocation status
conflict status
syncstatus
provenancehash
privacy export behavior
privacy deletion or anonymisation behavior
```

Object mapping must support:

```text
Moodle component
object type
object id
context id
Konnaxion target type
Konnaxion target id
Konnaxion module/context mapping details
mapping status
created by
timecreated
timemodified
sourceversion
syncstatus
provenancehash
```

Target identifiers must be stable and reproducible.

Motion-level readings use:

```text
konnaxiontargettype = uckk_assembly_motion
konnaxiontargetid   = moodle:{siteid}:mod_uckkassembly:{cmid}:motion:{motionid}
```

Assembly-level readings use:

```text
konnaxiontargettype = uckk_assembly
konnaxiontargetid   = moodle:{siteid}:mod_uckkassembly:{cmid}:assembly:{assemblyid}
```

The mapping payload sent to Konnaxion must include enough metadata to verify:

```text
Moodle site
component
context
activity module
Assembly
motion
callback policy
visibility policy
privacy notice state
```

The mapping payload must not expose unnecessary personal data.

### 2.8 External API contract

The bridge must isolate Konnaxion route details inside:

```text
local_uckk\service\konnaxion_client
```

Minimum required operations:

```text
health check
list Smart Vote modalities
create or resolve Smart Vote target
create or resolve integration mapping
submit proxy vote when enabled
fetch Smart Vote result
fetch EkoH weight metadata when authorized
fetch anomaly/result metadata when authorized
fetch source version where available
```

Expected adapter methods:

```text
healthcheck()
get_vote_modalities()
create_target($target)
create_integration_mapping($mapping)
get_integration_mapping($targettype, $targetid)
submit_vote($target, $vote)
get_vote_result($targettype, $targetid)
get_user_weight_summary($konnaxionuserid, $domain)
get_result_metadata($targettype, $targetid)
```

Canonical service names:

```text
SERVICE_CREATE_KONNAXION_MAPPING       = local_uckk_create_konnaxion_mapping
SERVICE_GET_KONNAXION_MAPPING          = local_uckk_get_konnaxion_mapping
SERVICE_SYNC_KONNAXION                 = local_uckk_sync_konnaxion
SERVICE_REQUEST_SMART_VOTE_READING     = mod_uckkassembly_request_smart_vote_reading
SERVICE_IMPORT_SMART_VOTE_SNAPSHOT     = mod_uckkassembly_import_smart_vote_snapshot
SERVICE_CONTEST_SMART_VOTE_SNAPSHOT    = mod_uckkassembly_contest_smart_vote_snapshot
SERVICE_GET_SMART_VOTE_REPORT          = report_uckk_get_smart_vote_report
```

HTTP route examples may be documented in implementation notes, but code must not hard-code undocumented Konnaxion internals outside the client class.

### 2.9 Smart Vote workflow

#### 2.9.1 Preparation

```text
1. Assembly enters a state where Smart Vote readings are allowed.
2. Authorized user requests Smart Vote through CAP_REQUEST_SMART_VOTE.
3. Moodle checks login, context, capability, Assembly type, Assembly state, and integrity status.
4. local_uckk resolves identity and object mapping requirements.
5. local_uckk creates or resolves the Konnaxion target and IntegrationMapping.
6. mod_uckkassembly records EVENT_SMART_VOTE_READING_REQUESTED.
7. mod_uckkassembly stores target status using SMART_VOTE_TARGET_STATUS_ENUM.
```

#### 2.9.2 Vote collection modes

Allowed modes:

```text
proxy_vote_from_moodle
external_vote_portal
```

`proxy_vote_from_moodle` means Moodle collects the participant's vote intent after Moodle session, context, eligibility, and capability checks, then sends it to Konnaxion.

`external_vote_portal` means Moodle links the participant to Konnaxion, then imports only verified result snapshots.

Both modes require:

```text
identity mapping
participant notice
visibility rule
capability check
audit event
privacy-provider coverage
retention policy
contestation path
```

#### 2.9.3 Result import

```text
1. Authorized user requests import, or a scheduled task imports after the voting window closes.
2. local_uckk fetches VoteResult and VoteModality metadata.
3. mod_uckkassembly validates target type, target id, modality, context, Assembly state, and mapping status.
4. mod_uckkassembly stores a Smart Vote snapshot in TABLE_SMART_VOTE_SNAPSHOT.
5. mod_uckkassembly stores audit trail in TABLE_SMART_VOTE_RESULT_AUDIT.
6. mod_uckkassembly displays the result as SV_COMPUTED_READING, not as SV_HUMAN_DECISION.
7. mod_uckkassembly records EVENT_SMART_VOTE_SNAPSHOT_IMPORTED.
```

Result import must reject:

```text
target mismatch
context mismatch
modality mismatch
stale result when refresh is required
mapping conflict
unresolved integrity block
malformed payload
missing quorum metadata when quorum is required
unexpected raw voter identity exposure
unknown source version when source version is required
missing reading method
missing provenance hash when archive snapshot is required
```

#### 2.9.4 Decision publication

A final Assembly decision using Smart Vote must include:

```text
motion reference
Smart Vote modality
raw vote count where available
weighted reading
quorum summary
integrity/anomaly warnings
human/institutional reasoning
minority report when present
contestation path
archive snapshot reference
source version
provenance hash
```

Smart Vote must never call the final decision publication service by itself.

Final decision publication must remain under:

```text
ASSEMBLY_AUTHORITY = human_institutional_decision
```

### 2.10 Failure and safety rules

| Situation | Required Moodle behavior |
|---|---|
| Konnaxion disabled | Hide Smart Vote start actions; preserve existing non-Smart-Vote Assembly workflows. |
| Konnaxion unavailable | Fail closed for Smart Vote import; do not fabricate, cache-as-final, or silently accept results. |
| Identity unmapped | Deny Smart Vote action or permit only configured unweighted local fallback. |
| Object mapping conflict | Pause Smart Vote reading and require authorized mapping review. |
| Target mismatch | Reject import and open or offer integrity review. |
| Stale result | Warn and require authorized refresh before decision publication. |
| Anomaly detected | Mark reading as restricted/contested and route to integrity review if configured. |
| Raw voter identities returned unexpectedly | Redact from display and archive; create privacy/integrity warning. |
| API secret missing | Disable bridge and show admin-only configuration error. |
| Payload validation fails | Reject payload, log metadata, and preserve audit event. |
| Timeout | Fail closed, log metadata, keep prior accepted reading non-final, and do not update snapshot. |
| Duplicate callback or retry | Use idempotency key and do not duplicate snapshot, audit row, or event. |
| Mapping revoked | Stop imports and require remapping before new Smart Vote actions. |
| Source version changed | Import only as new snapshot or supersession; never silently overwrite existing snapshot. |

### 2.11 Connected-mode classes, tasks, and events

These classes, tasks, and events are required for the connected profile. They must not block `standalone_core` installation when the Konnaxion bridge is disabled and the connected profile is not claimed as enabled.

`local_uckk` must deliver for `connected_konnaxion`:

```text
local/uckk/classes/service/konnaxion_client.php
local/uckk/classes/service/konnaxion_identity_mapper.php
local/uckk/classes/service/konnaxion_object_mapper.php
local/uckk/classes/service/konnaxion_sync_service.php
local/uckk/classes/service/smartvote_bridge.php
local/uckk/classes/service/smartvote_result_validator.php
local/uckk/classes/external/create_konnaxion_mapping.php
local/uckk/classes/external/get_konnaxion_mapping.php
local/uckk/classes/external/sync_konnaxion.php
local/uckk/classes/external/get_konnaxion_status.php
local/uckk/classes/event/konnaxion_mapping_created.php
local/uckk/classes/event/konnaxion_sync_completed.php
local/uckk/classes/event/konnaxion_request_failed.php
local/uckk/classes/event/konnaxion_mapping_revoked.php
local/uckk/classes/task/sync_konnaxion_mappings.php
```

`mod_uckkassembly` must deliver for `connected_konnaxion`:

```text
mod/uckkassembly/classes/local/smartvote_reading.php
mod/uckkassembly/classes/local/smartvote_snapshot_repository.php
mod/uckkassembly/classes/local/smartvote_target_repository.php
mod/uckkassembly/classes/local/smartvote_audit_repository.php
mod/uckkassembly/classes/external/request_smart_vote_reading.php
mod/uckkassembly/classes/external/import_smart_vote_snapshot.php
mod/uckkassembly/classes/external/contest_smart_vote_snapshot.php
mod/uckkassembly/classes/event/smart_vote_reading_requested.php
mod/uckkassembly/classes/event/smart_vote_snapshot_imported.php
mod/uckkassembly/classes/event/smart_vote_snapshot_contested.php
mod/uckkassembly/classes/event/smart_vote_snapshot_archived.php
```

`mod_uckkarchive` must deliver for `connected_konnaxion`:

```text
mod/uckkarchive/classes/local/smartvote_archive_package.php
mod/uckkarchive/classes/local/smartvote_snapshot_exporter.php
mod/uckkarchive/classes/event/smart_vote_archive_package_created.php
```

`tool_uckkintegrity` must deliver for `connected_konnaxion`:

```text
admin/tool/uckkintegrity/classes/local/smartvote_integrity_review.php
admin/tool/uckkintegrity/classes/local/smartvote_anomaly_case.php
admin/tool/uckkintegrity/classes/event/smartvote_anomaly_review_opened.php
admin/tool/uckkintegrity/classes/event/smartvote_reading_invalidated.php
```

`report_uckk` must deliver for `connected_konnaxion`:

```text
report/uckk/classes/external/get_smart_vote_report.php
report/uckk/classes/local/smartvote_report_repository.php
report/uckk/classes/local/smartvote_exporter.php
report/uckk/classes/table/smartvote_report_table.php
```

Canonical events must match the canonical event variables:

```text
EVENT_KONNAXION_MAPPING_CREATED
EVENT_KONNAXION_SYNC_COMPLETED
EVENT_SMART_VOTE_READING_REQUESTED
EVENT_SMART_VOTE_SNAPSHOT_IMPORTED
EVENT_SMART_VOTE_SNAPSHOT_CONTESTED
EVENT_SMART_VOTE_SNAPSHOT_ARCHIVED
```

### 2.12 Privacy, logging, and retention

Konnaxion-related data may include:

```text
Moodle userid
Konnaxion external identifier
pseudonymous identifier
vote participation metadata
Smart Vote reading
expertise weight
ethics score
raw vote counts
anomaly metadata
source version
provenance hash
sync log metadata
administrator action logs
```

The privacy provider of each storing plugin must cover:

```text
metadata declaration
user data export
delete or anonymise behavior
retention expiry
restricted export where needed
archive retention exception where required
redaction behavior
test coverage
```

Secrets must never be stored in logs, reports, events, archive packages, AI prompts, or AI responses.

Archive snapshots must preserve:

```text
SV_RAW_DATA when allowed
SV_READING_METHOD
SV_COMPUTED_READING
SV_EXPERTISE_WEIGHT where used and exportable
SV_HUMAN_DECISION when linked
SV_MINORITY_REPORT when present
SV_INTEGRITY_WARNING when present
SV_CONTESTATION_STATUS
SV_ARCHIVE_ITEM_ID
sourceversion
provenancehash
```

Retention must distinguish:

| Data class | Default posture |
|---|---|
| API secrets | Secure config only; never exported in reports or archives. |
| Sync metadata | Retain according to `konnaxion_retention_days`. |
| Payload logs | Disabled by default; strict retention and restricted visibility if enabled. |
| Identity mappings | Export/delete/anonymise according to user privacy policy and institutional record needs. |
| Object mappings | Retain while linked Moodle object exists; archive or invalidate when object is archived. |
| Smart Vote snapshots | Retain as institutional record when used in Assembly decision, subject to redaction rules. |
| Integrity warnings | Retain under integrity-case retention rules. |
| Archive packages | Retain according to archive visibility and institutional retention rules. |

### 2.13 Connected-mode Konnaxion acceptance gate

A `connected_konnaxion` release candidate fails this gate if any of these are true:

```text
Konnaxion writes directly into Moodle source tables.
Konnaxion identity/object mapping tables are missing.
Smart Vote snapshots are missing.
Smart Vote result audit table is missing.
Smart Vote appears as an automatic final decision.
Final Assembly decisions can be published without Moodle capability checks.
Smart Vote result import does not validate target, context, modality, state, and mapping.
Smart Vote result import overwrites earlier snapshots without audit trail.
Konnaxion API secrets appear in logs, reports, archives, events, AI prompts, or exports.
Payload logging has no privacy-provider coverage.
Konnaxion outage fabricates or silently accepts results.
Timeout, retry, failure, and idempotency behavior is untested.
Capability names do not match the canonical capability variables.
Service names do not match the canonical service variables.
Event names do not match the canonical event variables.
Status values do not match the canonical state variables.
```

## 3. AI integration

### 3.1 Component

AI integration is owned by:

```text
aiprovider_uckk
```

AI may also be consumed by:

```text
local_uckk
mod_uckkchallenge
mod_uckkassembly
mod_uckkarchive
tool_uckkintegrity
report_uckk
```

AI integration must follow Moodle AI provider conventions and UCKK non-sovereignty rules.

### 3.2 AI roles

AI may support:

```text
summarisation
translation
evidence clarification
course outline drafting
challenge hint generation
archive description drafting
integrity uncertainty detection
report explanation
Smart Vote result explanation
Konnaxion metadata summarisation
```

AI must not perform:

```text
final grading
final badge award
final competency certification
final Assembly decision
final integrity judgment
silent evidence validation
silent archive validation
secret-bearing prompt construction
Smart Vote result mutation
Konnaxion mapping mutation without service checks
```

### 3.3 AI actions

Allowed AI actions must be explicitly declared.

Each AI action must define:

```text
action name
originating component
context
required capability
input data categories
output data categories
retention rule
redaction rule
human review requirement
event logging
privacy-provider coverage
```

AI outputs that summarize Smart Vote or Konnaxion data must label themselves as explanations, not decisions.

### 3.4 AI logging settings

The AI provider must define settings for:

```text
enable_ai_provider
ai_default_model
ai_request_timeout_seconds
ai_log_prompts
ai_log_responses
ai_redact_personal_data
ai_redact_konnaxion_secrets
ai_require_human_review
ai_retention_days
```

Prompt and response logging must be disabled by default unless governance explicitly enables it.

If logs are retained, privacy providers must implement export and deletion/anonymisation behavior.

### 3.5 AI output label

Every AI-assisted output must include a visible or machine-readable label:

```text
AI-assisted draft. Not a final institutional decision.
```

When AI summarizes Smart Vote:

```text
AI-assisted explanation of a Smart Vote reading. Not a final Assembly decision.
```

When AI summarizes integrity or anomaly metadata:

```text
AI-assisted integrity summary. Not a final integrity judgment.
```

### 3.6 AI audit requirements

AI actions must emit events or logs sufficient to answer:

```text
who requested the action
what component requested it
which context it belonged to
what data categories were sent
whether personal data was redacted
whether Konnaxion data was included
whether Smart Vote data was included
what output was produced or retained
who reviewed or accepted the output
```

AI logs must not contain Konnaxion API secrets, hidden payload secrets, or unredacted data where redaction is configured.

## 4. Reporting

### 4.1 `report_uckk`

`report_uckk` owns core institutional reporting. In `connected_konnaxion`, it also owns Smart Vote and Konnaxion-derived reports.

Reports must be read-only unless a specific export or acknowledgement workflow is explicitly declared.

Core required reports:

```text
campus overview
program/pathway progress
challenge participation
challenge validation
Assembly activity
Assembly decisions
archive inventory
archive provenance
integrity cases
badge and competency recognition
seed execution history
AI usage audit
privacy/export readiness
release acceptance evidence
```

Connected-mode reports, required only when `connected_konnaxion` is enabled, packaged, or claimed as supported:

```text
Smart Vote readings
Smart Vote contestations
Smart Vote archive coverage
Konnaxion bridge status
Konnaxion sync history
Konnaxion mapping conflicts
Konnaxion failure/retry history
```

### 4.2 Report filters

Reports must support relevant filters:

```text
date range
course
category
cohort
role
pathway
program
activity module
Assembly
motion
decision status
Smart Vote snapshot status
Smart Vote contestation status
Konnaxion mapping status
Konnaxion sync status
integrity status
archive visibility
privacy-retention class
```

Every report must enforce Moodle capabilities and contexts. Smart Vote and Konnaxion filters apply only when the connected profile is enabled and the report includes connected-mode data.

Report visibility must use:

```text
CAP_VIEW_SMART_VOTE_REPORTS = report/uckk:viewsmartvotereports
```

where the report includes Smart Vote data in `connected_konnaxion`.

### 4.3 Export

Export formats:

```text
CSV
JSON
PDF when supported by Moodle stack
archive package manifest
release acceptance manifest
```

Exports must include:

```text
export title
export generator
export timestamp
Moodle site identifier
source filters
viewer userid
data provenance
privacy warning where needed
Konnaxion source version where applicable
Smart Vote snapshot id where applicable
archive item id where applicable
```

Smart Vote exports must distinguish:

```text
SV_RAW_DATA
SV_READING_METHOD
SV_COMPUTED_READING
SV_HUMAN_DECISION
SV_MINORITY_REPORT
SV_INTEGRITY_WARNING
SV_CONTESTATION_STATUS
SV_ARCHIVE_ITEM_ID
```

Reports and exports must not collapse Smart Vote readings into final decisions.

### 4.4 Report implementation contract

`report_uckk` must deliver for `standalone_core`:

```text
report/uckk/index.php
report/uckk/settings.php
report/uckk/db/access.php
report/uckk/db/services.php when core external services are declared
report/uckk/classes/local/report_repository.php
report/uckk/classes/local/exporter.php
report/uckk/classes/table/* when table renderers are used
report/uckk/classes/output/* when output renderers are used
report/uckk/classes/privacy/provider.php
report/uckk/lang/en/report_uckk.php
report/uckk/lang/fr/report_uckk.php
report/uckk/tests/
report/uckk/tests/behat/
```

`report_uckk` must additionally deliver for `connected_konnaxion`:

```text
report/uckk/classes/external/get_smart_vote_report.php
report/uckk/classes/local/smartvote_report_repository.php
report/uckk/classes/local/smartvote_exporter.php
report/uckk/classes/table/smartvote_report_table.php
connected-mode PHPUnit coverage
connected-mode Behat coverage
```

### 4.5 Report/export registry

All report and export definitions must be represented in:

```text
PRESET_EXPORTS = presets/exports.json
```

The export registry must include:

```text
report key
export key
owning component
source tables
source services
required capability
context rule
privacy class
retention class
supported formats
test fixture
acceptance evidence requirement
```

## 5. Seed execution

### 5.1 `tool_uckkseed` modes

`tool_uckkseed` must support:

```text
dry_run
apply
verify
rollback_plan
```

Seed execution must be idempotent.

Repeated execution must not duplicate:

```text
categories
courses
cohorts
roles
capabilities
competencies
badges
reports
navigation records
state machines
events
Konnaxion mapping defaults when connected-mode presets are enabled
privacy-retention rules
export definitions
```

### 5.2 Seed input files

Core required seed input files:

```text
presets/categories.json
presets/courses.json
presets/cohorts.json
presets/roles.json
PRESET_CAPABILITIES = presets/capabilities.json
presets/competencies.json
presets/badges.json
presets/reports.json
presets/navigation.json
PRESET_STATE_MACHINES = presets/state_machines.json
PRESET_EVENTS = presets/events.json
PRESET_PRIVACY_RETENTION = presets/privacy_retention.json
PRESET_EXPORTS = presets/exports.json
```

Connected-mode seed input file:

```text
PRESET_KONNAXION_MAPPINGS = presets/konnaxion_mappings.json
```

The connected-mode seed file is required only when the `connected_konnaxion` profile is enabled, packaged, or claimed as supported.

Each JSON file must have:

```text
schema version
stable keys
idempotent lookup keys
validation rules
owner component
test fixture coverage
```

### 5.3 Idempotency rule

Seed operations must use stable lookup keys.

Examples:

```text
course shortname
category idnumber
cohort idnumber
role shortname
capability name
competency idnumber
badge unique key
report key
navigation key
state-machine key
event key
Konnaxion mapping key
export key
privacy-retention key
```

Seed logs must report:

```text
created
updated
unchanged
skipped
failed
```

### 5.4 Seed log

`tool_uckkseed` must store seed execution logs with:

```text
mode
actor userid
timestamp
preset file
preset version
operation count
created count
updated count
unchanged count
skipped count
failed count
error summary
```

Logs that include personal data must be privacy-covered.

Connected-mode Konnaxion seed defaults must never store API secrets.

### 5.5 Seed safety requirements

Seed apply must not run without:

```text
sesskey
admin capability
valid JSON
schema validation
dependency verification
dry-run summary available
confirmation step
```

Seed rollback must produce a plan, not blindly delete institutional records.

## 6. Implementation-readiness gate

Before a release candidate may be installed in Moodle, it must pass all readiness checks in this section.

### 6.1 Filetype correctness

Every file must contain code appropriate to its extension.

```text
.php files must start with <?php unless they are deliberate template fragments.
.js files under amd/src must contain JavaScript only.
.mustache files must contain Mustache only.
.json files must contain valid JSON.
.xml files must contain valid XML.
.scss and .css files must not contain PHP controller logic.
```

A comment inside JavaScript that mentions a PHP API such as `$PAGE->requires->js_call_amd(...)` is a cleanup warning, not an install blocker by itself. Executable PHP/page-controller code inside `.js` remains a blocker.

Blocking defects:

```text
Markdown fences inside PHP source files
Executable PHP/page-controller code inside amd/src JavaScript files
JavaScript module code inside PHP page files
raw implementation instructions inside executable files
missing opening <?php in PHP source files
```

### 6.2 Moodle component correctness

Component names must match physical paths:

```text
theme/uckk                 => theme_uckk
course/format/uckk         => format_uckk
local/uckk                 => local_uckk
blocks/uckk_dashboard      => block_uckk_dashboard
mod/uckkchallenge          => mod_uckkchallenge
mod/uckkassembly           => mod_uckkassembly
mod/uckkarchive            => mod_uckkarchive
admin/tool/uckkseed        => tool_uckkseed
admin/tool/uckkintegrity   => tool_uckkintegrity
report/uckk                => report_uckk
ai/provider/uckk           => aiprovider_uckk
```

The following must agree with the component:

```text
version.php
@package
language component names
capability prefixes
service component names
event namespaces
AMD module names
template names
test namespaces
privacy provider namespaces
```

### 6.3 Required class layer

Any referenced namespaced class must have a matching file under `classes/`.

Required class families, where applicable:

```text
classes/form/*
classes/event/*
classes/external/*
classes/output/*
classes/table/*
classes/task/*
classes/local/*
classes/service/*
classes/privacy/provider.php
```

External services declared in `db/services.php` must map to real `classes/external/*` classes.

Scheduled tasks declared in `db/tasks.php` must map to real `classes/task/*` classes.

Events declared, triggered, or listed in `db/events.php` must map to real `classes/event/*` classes.

Forms referenced by pages must map to real `classes/form/*` classes or valid legacy form files.

### 6.4 Privacy provider coverage

Every plugin storing, displaying, exporting, logging, or deriving personal data must include a privacy provider.

Minimum expected privacy providers:

```text
local_uckk
mod_uckkarchive
mod_uckkassembly
mod_uckkchallenge
tool_uckkseed
tool_uckkintegrity
block_uckk_dashboard
format_uckk
report_uckk
aiprovider_uckk
```

When `connected_konnaxion` is enabled, Konnaxion-related data stored by `local_uckk`, `mod_uckkassembly`, `mod_uckkarchive`, `tool_uckkintegrity`, or `report_uckk` must be included in those plugins' privacy providers.

Each provider must define:

```text
metadata declaration
export behavior
delete behavior
anonymisation behavior
retention behavior
redaction behavior
archive-retention exception where needed
```

### 6.5 Page and service access checks

Every user-facing page, admin page, AJAX entry point, external service, report, export, and file-serving path must define:

```text
require_login behavior
context resolution
required capability
sesskey requirement for writes
parameter validation
output escaping
redirect/error behavior
privacy impact
events emitted
```

Write operations must not be possible by GET request alone.

### 6.6 Canonical variable alignment gate

The implementation is not ready until all canonical variables in this document are resolved across docs, presets, code, tests, and release artifacts.

Core blocking defects:

```text
active docs still point to LEGACY_DOC_10 as the correction target outside DOC_11 or migration notes
active docs still point to obsolete or missing DOC_10 filenames instead of the canonical DOC_10 outside DOC_11 or migration notes
core preset files are missing from seed input
core report/export definitions are missing from PRESET_EXPORTS
privacy/retention definitions are missing from PRESET_PRIVACY_RETENTION
status values for core workflows do not match canonical state variables
```

Connected-mode blocking defects, when `connected_konnaxion` is enabled or claimed as supported:

```text
Konnaxion capabilities do not match CAP_* variables
Konnaxion services do not match SERVICE_* variables
Konnaxion events do not match EVENT_* variables
Konnaxion tables do not match TABLE_* variables
Smart Vote fields do not match SV_* variables
Smart Vote/Konnaxion status values do not match *_STATUS_ENUM variables
PRESET_KONNAXION_MAPPINGS is missing when connected-mode presets are enabled
```

## 7. Static validation commands

A release candidate must pass these checks before installation in Moodle:

```bash
# PHP syntax.
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l

# Accidental Markdown fences in PHP.
grep -RIn --include='*.php' '```' .

# Accidental executable PHP/page-controller code in JavaScript.
# This is a blocking check. It intentionally does not fail on a plain comment that only mentions $PAGE.
grep -RIn --include='*.js' '<?php\|require_once\|defined *(\|optional_param\|required_param\|require_login\|require_capability' .

# PHP examples in JavaScript comments are warning-only cleanup items.
grep -RIn --include='*.js' '\$PAGE->requires->js_call_amd' . || true

# JSON syntax.
find . -name '*.json' -print0 | xargs -0 -n1 python3 -m json.tool >/dev/null

# XML syntax.
find . -name 'install.xml' -print0 | xargs -0 -n1 xmllint --noout

# Canonical variable drift checks.
# Deprecated names are allowed only inside DOC_11, explicit migration notes, or deprecated-alias tables.
grep -RIn '10_implementation_correction_plan.md\|konnaxion_alignment_and_correction_plan.md\|OPTIONAL_KONNAXION_CONTRACT' docs/ presets/ release/   | grep -v 'docs/11_cross_doc_alignment_registry.md'   | grep -v 'deprecated'   | grep -v 'migration' || true

grep -RIn 'configurekonnaxion\|usesmartvote\|viewsmarkvotereading\|viewsmartvotereading\|importsmartvoteresult\|exportsmartvotesnapshot\|contestsmartvotereading' docs/ presets/ plugins/   | grep -v 'docs/11_cross_doc_alignment_registry.md'   | grep -v 'deprecated'   | grep -v 'migration' || true
```

Project validation scripts may wrap these commands, but they must not replace them unless they provide equivalent coverage.

The grep checks above are drift detectors. They should return no active canonical references unless the match appears only inside `DOC_11`, an explicit migration note, or a deprecated-alias table.

## 8. Installation

Final standalone installation must support:

```text
copy plugin suite into Moodle plugin paths
run Moodle upgrade
purge Moodle caches
enable theme_uckk
configure local_uckk
run tool_uckkseed dry_run
run tool_uckkseed apply
verify state-machine presets
verify event presets
verify privacy-retention presets
verify export presets
verify reports
verify dashboard
verify privacy checks
run automated tests
```

Connected-mode installation additionally verifies:

```text
configure Konnaxion bridge disabled by default
verify Konnaxion mapping presets
verify Konnaxion bridge settings
verify Konnaxion health-check safe failure when unconfigured
verify Smart Vote actions are hidden unless enabled and authorized
```

Installation must be tested first on a clean disposable Moodle target.

### 8.1 Installation gate

A clean standalone target must pass:

```text
Moodle detects all plugins with correct component names.
Moodle upgrade completes without fatal error.
All install.xml files create expected core tables.
All upgrade.php files are syntactically valid and idempotent.
All declared core dependencies are present or documented.
All core capabilities appear under the correct component.
All scheduled tasks appear under the correct component.
All web service functions load their declared classes.
All privacy providers are discoverable.
Konnaxion bridge is disabled by default when present.
Smart Vote actions are hidden unless connected mode is enabled and authorized.
```

A connected-mode target must additionally pass:

```text
All Konnaxion and Smart Vote tables use canonical table names.
All canonical Konnaxion/Smart Vote capabilities exist.
All canonical Konnaxion/Smart Vote services exist.
All canonical Konnaxion/Smart Vote events exist.
Konnaxion health check fails safely when unconfigured.
Konnaxion bridge remains disabled by default unless explicitly configured.
```

## 9. Upgrade

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
Konnaxion mapping preservation plan when connected-mode data exists
Smart Vote snapshot preservation plan when connected-mode data exists
privacy/retention impact note
```

Upgrade code must not duplicate:

```text
tables
roles
capabilities
scheduled tasks
seed records
admin settings
Konnaxion mappings
Smart Vote snapshots
event registry rows
export registry rows
```

## 10. Testing

### 10.1 PHPUnit

Core PHPUnit coverage must pass without Konnaxion. Connected-mode PHPUnit coverage is required when `connected_konnaxion` is enabled, packaged, or claimed as supported.

Core PHPUnit coverage:

```text
local_uckk services
pathway assignment
provenance service
challenge state transitions
Assembly state transitions without Smart Vote
archive versioning without Smart Vote snapshots
integrity case transitions without Konnaxion-derived records
privacy export/delete for core UCKK records
seed idempotency for core presets
report query builders for core reports
AI request redaction
external service parameter validation for declared core services
capability failure paths for core capabilities
event creation for declared core events
scheduled task execution for declared core tasks
canonical variable resolution for standalone_core
```

Connected-mode PHPUnit coverage, required only when `connected_konnaxion` is enabled, packaged, or claimed as supported:

```text
Konnaxion client failure handling
Konnaxion identity mapping
Konnaxion object mapping
Konnaxion sync log
Konnaxion retry and idempotency
Konnaxion timeout behavior
Konnaxion source-version preservation
Smart Vote target mapping
Smart Vote result validation
Smart Vote snapshot storage
Smart Vote result audit
Smart Vote archive export
Smart Vote anomaly handling
Smart Vote contestation
Smart Vote report export
canonical variable resolution for connected_konnaxion
```

Recommended command pattern:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit local/uckk/tests
vendor/bin/phpunit mod/uckkarchive/tests
vendor/bin/phpunit mod/uckkassembly/tests
vendor/bin/phpunit mod/uckkchallenge/tests
vendor/bin/phpunit admin/tool/uckkseed/tests
vendor/bin/phpunit admin/tool/uckkintegrity/tests
vendor/bin/phpunit blocks/uckk_dashboard/tests
vendor/bin/phpunit course/format/uckk/tests
vendor/bin/phpunit report/uckk/tests
vendor/bin/phpunit ai/provider/uckk/tests
```

### 10.2 Behat

Core Behat workflows must pass without Konnaxion. Connected-mode Behat workflows are required when `connected_konnaxion` is enabled, packaged, or claimed as supported.

Required end-to-end workflows by profile:

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
Konnaxion bridge health check is performed by authorized admin
Konnaxion bridge health check fails safely when unconfigured
Assembly Smart Vote reading is started by authorized user
Smart Vote result is imported as reading, not final decision
Smart Vote reading is contested and routed to integrity review
Smart Vote snapshot is archived with provenance
Smart Vote report is visible only through CAP_VIEW_SMART_VOTE_REPORTS
Privacy export includes UCKK and Konnaxion mapping records

Konnaxion outage does not publish a final decision
Canonical capability names appear in role setup
```

Recommended command pattern:

```bash
php admin/tool/behat/cli/init.php
php admin/tool/behat/cli/run.php --tags='@uckk'
```

### 10.3 JavaScript build

All AMD source files must build successfully.

```bash
npx grunt amd
```

AMD source files must remain source-only JavaScript files.

Generated build artifacts must match Moodle build expectations and must not contain PHP controller code.

### 10.4 Manual smoke tests

Manual standalone smoke tests must pass without Konnaxion. Connected-mode smoke tests are required when `connected_konnaxion` is enabled, packaged, or claimed as supported.

Manual standalone smoke tests must verify:

```text
plugin installation page shows no missing dependencies
front page loads under theme_uckk
course using format_uckk loads
Joueur dashboard block loads
archive activity view loads
challenge activity view loads
Assembly activity view loads without Smart Vote
integrity admin index loads
seed admin page loads
report index loads
AI provider settings page loads
privacy registry detects UCKK providers
Konnaxion bridge absence or disabled state does not break standalone workflows
```

Manual connected-mode smoke tests, required only when `connected_konnaxion` is enabled, packaged, or claimed as supported:

```text
Konnaxion bridge settings page loads
Konnaxion bridge disabled state is safe
Konnaxion health check fails safely when unconfigured
Assembly Smart Vote panel loads only for authorized users
report_uckk Smart Vote report loads only for authorized users
Konnaxion identity/object mapping screens require capability
Smart Vote import cannot publish final decision
Smart Vote archive package includes provenance
```

## 11. Release package

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

### 11.1 Release manifest

The release package must include a manifest listing:

```text
release version
Moodle version target
plugin paths
plugin components
plugin versions
required dependencies
preset files
build commands used
test commands used
test results summary
known limitations
canonical variable resolution summary
Konnaxion bridge enabled/disabled default state when connected profile is included
Konnaxion API contract version tested when connected profile is included
Smart Vote modalities tested when connected profile is included
external integration test results when connected profile is included
privacy/retention test results
report/export test results
```

### 11.2 Canonical variable manifest

The release package must include a canonical variable manifest proving that:

```text
DOC_10 is 10_konnaxion_smart_vote_integration_contract.md
LEGACY_DOC_10 is not the active target
implemented standalone capability variables match db/access.php and presets/capabilities.json
implemented standalone service variables match db/services.php and classes/external
implemented standalone event variables match classes/event and presets/events.json
implemented standalone table variables match db/install.xml and DOC_04
connected-mode CAP_*, SERVICE_*, EVENT_*, TABLE_*, SV_*, and *_STATUS_ENUM variables are either implemented and tested, or explicitly marked as connected_konnaxion deferred in DOC_11 and the gap report
PRESET_* variables required for the claimed profile exist and validate as JSON
```

## 12. Acceptance checklist

### 12.1 Core standalone acceptance checklist

```text
[ ] PHP syntax check passes across all PHP files.
[ ] No Markdown fences remain inside PHP source files.
[ ] No PHP controller code exists inside amd/src JavaScript files.
[ ] No JavaScript module body is misplaced inside a PHP page file.
[ ] Every version.php component matches its plugin path.
[ ] Every declared core service class exists under classes/external.
[ ] Every declared core task class exists under classes/task.
[ ] Every triggered core event class exists under classes/event.
[ ] Every referenced form class exists.
[ ] Every plugin that handles personal data has a privacy provider.
[ ] Clean Moodle target installs all core plugins with Konnaxion disabled or unconfigured.
[ ] All plugins show stable maturity metadata.
[ ] Moodle upgrade completes without error.
[ ] UCKK theme applies successfully.
[ ] Seed dry-run produces expected standalone plan.
[ ] Seed apply creates all core campus objects.
[ ] Seed input includes capabilities, state machines, events, privacy retention, and exports.
[ ] UCKK-000 and TC101–TC108 exist and use format_uckk.
[ ] Roles and capabilities are applied.
[ ] Canonical capability variables match db/access.php and presets/capabilities.json for core workflows.
[ ] Joueur dashboard works.
[ ] Challenge workflow works.
[ ] Assembly workflow works without Smart Vote enabled.
[ ] Archive workflow works without Smart Vote snapshots.
[ ] Integrity workflow works without Konnaxion-derived records.
[ ] AI provider can be configured and disabled.
[ ] AI outputs are labelled non-authoritative.
[ ] Reports are visible only to authorized users.
[ ] Core report/export definitions exist in presets/exports.json.
[ ] Privacy export/delete works for core UCKK records.
[ ] Privacy/retention definitions exist in presets/privacy_retention.json.
[ ] JavaScript AMD build passes.
[ ] Behat suite passes for core workflows.
[ ] PHPUnit suite passes for core workflows.
[ ] No UCKK accreditation confusion appears in public UI.
[ ] UCKK / kOA / kOA Digital Ecosystem / King Klown / Konnaxion boundary is preserved.
[ ] Konnaxion is not required for standalone installation, seed execution, Assembly decisions, Archive preservation, Integrity review, reporting, privacy export/delete, or tests.
```

### 12.2 Optional Konnaxion-connected acceptance checklist

This checklist is required only when `connected_konnaxion` is enabled, packaged, or claimed as supported.

```text
[ ] Konnaxion bridge can be configured and disabled.
[ ] Konnaxion bridge is disabled safely by default.
[ ] Konnaxion health check works without exposing secrets.
[ ] Konnaxion health check fails safely when unconfigured.
[ ] Konnaxion identity/object mappings use canonical table names.
[ ] Konnaxion sync logs use canonical sync status values.
[ ] Konnaxion services use canonical service names.
[ ] Konnaxion events use canonical event names.
[ ] PRESET_KONNAXION_MAPPINGS exists and validates when connected-mode presets are enabled.
[ ] Smart Vote can be started only by authorized Assembly users.
[ ] Smart Vote actions are hidden unless enabled and authorized.
[ ] Smart Vote target mapping uses canonical target status values.
[ ] Smart Vote result import validates target, context, modality, state, and mapping.
[ ] Smart Vote result appears as a reading, not an automatic final decision.
[ ] Smart Vote snapshot stores raw data, reading method, computed reading, provenance, visibility, and contestation status.
[ ] Smart Vote snapshot archives method, result, provenance, visibility, and contestation path.
[ ] Smart Vote result audit records review, correction, contestation, and supersession.
[ ] Konnaxion outage fails safely.
[ ] Konnaxion timeout, retry, failure, and idempotency behavior is tested.
[ ] Konnaxion identity/object mappings are privacy-covered.
[ ] AI outputs involving Smart Vote are labelled non-authoritative.
[ ] Smart Vote reports use CAP_VIEW_SMART_VOTE_REPORTS.
[ ] Smart Vote reports and exports preserve provenance, reading method, contestation state, and privacy filtering.
[ ] Privacy export/delete covers Konnaxion mappings, Smart Vote snapshots, sync logs, and connected-mode user references where stored.
[ ] Smart Vote does not replace Assembly sovereignty.
[ ] Archives preserve Smart Vote snapshots and Assembly decisions separately, with provenance and contestability.
```

## 13. Final delivery statement

The core implementation is accepted when UCKK-Moodle can be installed from a clean target and used immediately as the standalone campus for UCKK, with institutional, academic, symbolic, integrity, archive, AI, reporting, delivery, privacy, and governance structures present and functional without requiring Konnaxion.

The Konnaxion-connected profile is accepted separately when the Konnaxion bridge, Smart Vote reading workflow, connected-mode reports, sync/mapping behavior, privacy coverage, archive preservation, failure handling, PHPUnit coverage, Behat coverage, and release evidence pass the connected-mode gates.

A code dump that contains scaffold files but fails filetype correctness, Moodle component correctness, class-layer discovery, privacy-provider discovery, canonical variable resolution, Moodle upgrade, JavaScript build, PHPUnit, or Behat for the claimed profile is not a final delivery. It is a correction candidate.

Konnaxion may compute Smart Vote readings. UCKK-Moodle Assemblées decide. Archives preserve both, with provenance and contestability.
