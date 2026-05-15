# 11 — Cross-Document Alignment Registry

**Status:** Canonical alignment registry for UCKK-Moodle documentation and implementation correction  
**Purpose:** Provide one shared source of truth for document paths, operating modes, authority boundaries, plugin ownership, current code-snapshot names, target connected-mode names, deprecated aliases, and drift checks.

This file is intentionally a registry, not a narrative specification. Other documentation files must consume these variables instead of redefining them locally.

## 0. Registry authority

This registry is binding for cross-document consistency.

```text
DOC_00 remains the root doctrine for UCKK-Moodle meaning and authority.
DOC_11 resolves cross-document names, paths, aliases, implementation status, and migration targets.
If a local documentation file conflicts with DOC_11 on a shared variable, DOC_11 wins unless DOC_00 explicitly overrides it.
```

Every active documentation file must include this rule near the top:

```text
This document consumes docs/11_cross_doc_alignment_registry.md.
It must not redefine shared variables. If a local rule conflicts with DOC_11, DOC_11 and DOC_00 win.
```

## 1. Code-snapshot basis

This registry is aligned against the UCKK-Moodle code snapshot generated on `2026-05-13T09:21:20`.

The snapshot contains the installable plugin suite and docs under these uploaded volume files:

```text
uckk-moodle_20260513_092119_01_ROOT.txt
uckk-moodle_20260513_092119_02_mod.txt
uckk-moodle_20260513_092119_03_admin.txt
uckk-moodle_20260513_092119_99_OTHERS.txt
```

The code snapshot currently implements UCKK-Moodle core plugin paths and core Moodle capabilities. It does **not** yet implement Konnaxion/Smart Vote connected-mode tables, services, events, or Smart Vote-specific capabilities in executable code.

Therefore:

```text
Konnaxion/Smart Vote variables in this registry are connected-mode targets unless marked as implemented_now.
A connected-mode target must not be treated as a standalone-core requirement.
```

## 2. Operating-mode variables

| Variable | Canonical value | Status | Rule |
|---|---:|---|---|
| `OPERATING_MODE_STANDALONE` | `standalone_core` | `implemented_now` | UCKK-Moodle installs, seeds, teaches, deliberates, archives, reports, and passes core tests without Konnaxion. |
| `OPERATING_MODE_KONNAXION_CONNECTED` | `connected_konnaxion` | `target_connected_mode` | Optional profile where Konnaxion bridge, Smart Vote readings, EkoH/advisory signals, mappings, sync logs, and connected reports are enabled. |
| `KONNAXION_REQUIRED_FOR_CORE` | `false` | `implemented_now` | No plugin may make Konnaxion a hard dependency for standalone install or ordinary UCKK workflows. |
| `SMART_VOTE_REQUIRED_FOR_CORE` | `false` | `implemented_now` | Assemblies, archives, reports, integrity review, and seed operations must work without Smart Vote. |
| `KONNAXION_DEFAULT_STATE` | `disabled` | `target_connected_mode` | Konnaxion bridge settings, services, tasks, UI panels, and reports are hidden or fail closed until enabled. |
| `SMART_VOTE_DEFAULT_STATE` | `disabled_until_connected` | `target_connected_mode` | Smart Vote request/import/report actions are available only in connected mode and only with explicit Moodle capabilities. |
| `CORE_RELEASE_GATE` | `standalone_core_install_and_workflows_pass` | `implemented_now` | Core acceptance must not require Konnaxion credentials, endpoints, mappings, Smart Vote snapshots, or Konnaxion reports. |
| `CONNECTED_RELEASE_GATE` | `konnaxion_connected_profile_passes` | `target_connected_mode` | Connected acceptance applies only when Konnaxion/Smart Vote features are enabled for that release profile. |

## 3. Document path registry

| Variable | Canonical path | Status | Rule |
|---|---|---|---|
| `DOC_00` | `docs/00_master_execution_doctrine.md` | active | Root doctrine and correction authority. |
| `DOC_01` | `docs/01_domain_boundaries_and_glossary.md` | active | Domain vocabulary and boundaries. |
| `DOC_02` | `docs/02_distribution_architecture.md` | active | Distribution architecture and dependency direction. |
| `DOC_03` | `docs/03_plugin_specifications.md` | active | Plugin implementation contract. |
| `DOC_04` | `docs/04_data_model_and_storage.md` | active | Table ownership, enums, state machines, and storage constraints. |
| `DOC_05` | `docs/05_roles_permissions_and_security.md` | active | Capability and access-control registry. |
| `DOC_06` | `docs/06_pedagogy_courses_competencies_badges.md` | active | Courses, competencies, evidence, and badge rules. |
| `DOC_07` | `docs/07_challenges_and_assemblies.md` | active | Challenge, Assembly, readings, decisions, and contestations. |
| `DOC_08` | `docs/08_integrity_archives_and_privacy.md` | active | Archive, privacy, retention, redaction, integrity. |
| `DOC_09` | `docs/09_integrations_reporting_delivery.md` | active | Integrations, reporting, delivery, release, acceptance. |
| `DOC_10` | `docs/10_konnaxion_smart_vote_integration_contract.md` | active_optional_connected_mode | Optional Konnaxion Smart Vote connected-mode contract. |
| `DOC_11` | `docs/11_cross_doc_alignment_registry.md` | active | This registry. |
| `LEGACY_DOC_10` | `docs/10_implementation_correction_plan.md` | deprecated | Historical correction-plan slot. Must not be active target. |

Deprecated and forbidden document variables:

```text
OPTIONAL_KONNAXION_CONTRACT
```

Deprecated and forbidden active document path:

```text
docs/10_konnaxion_alignment_and_correction_plan.md
```

If that file is later created, it must receive a new variable such as `DOC_12_KONNAXION_ALIGNMENT_PLAN`; it must not replace the active `DOC_10` path above.

## 4. Product boundary registry

| Variable | Canonical value | Rule |
|---|---|---|
| `PRODUCT_SCOPE` | `UCKK-Moodle is the Moodle campus implementation of UCKK.` | UCKK-Moodle is not the whole kOA movement or the whole Konnaxion ecosystem. |
| `SOURCE_FAMILY_UCKK_CANON` | `UCKK canon` | Governs UCKK meaning, pedagogy, governance, and symbolic boundaries. |
| `SOURCE_FAMILY_MOODLE_DOCS` | `Moodle developer documentation` | Governs Moodle implementation mechanics. |
| `SOURCE_FAMILY_KONNAXION` | `Konnaxion external source family` | External source of truth for Konnaxion-side Smart Vote objects and semantics. |
| `EXTERNAL_SYSTEM_KONNAXION` | `Konnaxion` | External system integrated through Moodle services only. |
| `EXTERNAL_SIGNAL_EKOH` | `EkoH` | External expertise/ethics/reputation context or signal source only where explicitly mapped. |

## 5. Non-negotiable authority variables

| Variable | Canonical value | Rule |
|---|---|---|
| `SMART_VOTE_CANONICAL_RULE` | `Konnaxion computes Smart Vote readings. UCKK-Moodle owns Assembly decisions. Archives preserve both, with provenance and contestability.` | Must be preserved anywhere Smart Vote is referenced. |
| `SMART_VOTE_AUTHORITY` | `computed_reading_only` | Smart Vote may inform; it must not decide, award, validate, sanction, publish final decisions, or close contestations. |
| `ASSEMBLY_AUTHORITY` | `human_institutional_decision` | Final Assembly decisions belong to Moodle-side Assembly workflow and permissions. |
| `ARCHIVE_AUTHORITY` | `provenance_and_contestation_memory` | Archives preserve source, snapshot, decision, minority report, provenance, and contestation trail. |
| `PERMISSION_RULE` | `moodle_capabilities_remain_authoritative` | External roles, labels, identifiers, weights, or scores never grant Moodle authority by themselves. |
| `DIRECT_WRITE_RULE` | `external_systems_never_write_moodle_source_tables` | Konnaxion and other external systems must not write directly into Moodle source tables. |
| `AI_AUTHORITY` | `assistive_only` | AI outputs are drafts/explanations and never final authority. |

## 6. Plugin component registry

These are the canonical Moodle component names and install paths present in the code snapshot.

| Component | Path | Status | Role |
|---|---|---|---|
| `theme_uckk` | `theme/uckk` | implemented_now | Visual identity and layouts. |
| `format_uckk` | `course/format/uckk` | implemented_now | UCKK course format. |
| `local_uckk` | `local/uckk` | implemented_now | Core registry, shared services, profiles, programs, pathways, integration coordination. |
| `block_uckk_dashboard` | `blocks/uckk_dashboard` | implemented_now | Dashboard display aggregator. |
| `mod_uckkchallenge` | `mod/uckkchallenge` | implemented_now | Challenge activity. |
| `mod_uckkassembly` | `mod/uckkassembly` | implemented_now | Assembly activity and ordinary votes/readings/decisions. |
| `mod_uckkarchive` | `mod/uckkarchive` | implemented_now | Archive activity and evidence/provenance preservation. |
| `tool_uckkseed` | `admin/tool/uckkseed` | implemented_now | Campus seed and preset operations. |
| `tool_uckkintegrity` | `admin/tool/uckkintegrity` | implemented_now | Integrity/Inquisiteur workflow. |
| `report_uckk` | `report/uckk` | implemented_now | Institutional reporting. |
| `aiprovider_uckk` | `ai/provider/uckk` | implemented_now | Governed AI provider. |

Forbidden component roots unless used only as packaging/staging directories:

```text
plugins/mod/uckkarchive
plugins/local/uckk
plugins/theme/uckk
local_uckkkonnaxion
mod_uckksmartvote
report_uckksmartvote
```

A separate Konnaxion plugin may be introduced only through a new architecture decision. Until then, Konnaxion-connected mode is owned through the existing suite.

## 7. Plugin ownership variables

| Variable | Canonical owner | Status | Responsibilities |
|---|---|---|---|
| `KONNAXION_BRIDGE_OWNER` | `local_uckk` | target_connected_mode | Configuration, endpoint client, authentication settings, object mapping, identity mapping, sync logs, shared integration services. |
| `SMART_VOTE_WORKFLOW_OWNER` | `mod_uckkassembly` | target_connected_mode | Smart Vote reading requests, vote-target mapping, snapshots, review, contestation, Assembly linkage. |
| `ASSEMBLY_DECISION_OWNER` | `mod_uckkassembly` | implemented_now | Motions, deliberation, decisions, minutes, minority reports, decision contestability. |
| `SMART_VOTE_ARCHIVE_OWNER` | `mod_uckkarchive` | target_connected_mode | Archive records and file areas for Smart Vote snapshots, decisions, minutes, provenance packages. |
| `ARCHIVE_RECORD_OWNER` | `mod_uckkarchive` | implemented_now | Archive items, proofs, Kristals, provenance, revisions, exports. |
| `SMART_VOTE_REPORT_OWNER` | `report_uckk` | target_connected_mode | Smart Vote reports, exports, filters, privacy-aware visibility. |
| `REPORT_OWNER` | `report_uckk` | implemented_now | UCKK institutional reports. |
| `SMART_VOTE_INTEGRITY_OWNER` | `tool_uckkintegrity` | target_connected_mode | Integrity warnings, contested readings, correction cases, restricted review workflows. |
| `INTEGRITY_OWNER` | `tool_uckkintegrity` | implemented_now | Integrity cases, notes, appeals, reviews, corrections. |
| `SMART_VOTE_SEED_OWNER` | `tool_uckkseed` | target_connected_mode | Optional connected-mode presets for capabilities, mappings, reports, retention. |
| `SEED_OWNER` | `tool_uckkseed` | implemented_now | Core campus seed, validation, export, reset, logs. |
| `SMART_VOTE_PRIVACY_OWNER` | `every_storing_plugin` | target_connected_mode | Each plugin that stores Smart Vote/Konnaxion personal data owns its own provider coverage. |

## 8. Current implemented capability registry

These capabilities are present in the current code snapshot and may be used in standalone-core documentation.

### 8.1 `local_uckk`

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
```

`local/uckk:manageintegrations` is the current implemented integration-administration capability. Until a narrower connected-mode Konnaxion capability migration is implemented, docs must not claim that `local/uckk:managekonnaxion`, `local/uckk:mapkonnaxionobjects`, or `local/uckk:viewkonnaxionlogs` exist in executable code.

### 8.2 `mod_uckkassembly`

```text
mod/uckkassembly:addinstance
mod/uckkassembly:view
mod/uckkassembly:createassembly
mod/uckkassembly:proposemotion
mod/uckkassembly:amendmotion
mod/uckkassembly:vote
mod/uckkassembly:publishdecision
mod/uckkassembly:contestdecision
mod/uckkassembly:archive
```

No Smart Vote-specific Assembly capability is implemented in the current code snapshot.

### 8.3 `mod_uckkarchive`

```text
mod/uckkarchive:addinstance
mod/uckkarchive:view
mod/uckkarchive:additem
mod/uckkarchive:validateitem
mod/uckkarchive:reviseitem
mod/uckkarchive:viewrestricted
mod/uckkarchive:export
```

No Smart Vote-specific Archive capability is implemented in the current code snapshot.

### 8.4 `report_uckk`

```text
report/uckk:view
report/uckk:viewall
report/uckk:export
```

No Smart Vote-specific Report capability is implemented in the current code snapshot.

### 8.5 `tool_uckkintegrity`

```text
tool/uckkintegrity:view
tool/uckkintegrity:opencase
tool/uckkintegrity:reviewcase
tool/uckkintegrity:assigncase
tool/uckkintegrity:issuecorrection
tool/uckkintegrity:invalidate
tool/uckkintegrity:closecase
tool/uckkintegrity:viewrestricted
```

### 8.6 `tool_uckkseed`

```text
tool/uckkseed:seed
tool/uckkseed:reset
tool/uckkseed:validate
tool/uckkseed:exportpresets
```

### 8.7 Other implemented capabilities

```text
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

aiprovider/uckk:configure
aiprovider/uckk:use
aiprovider/uckk:viewlogs
```

## 9. Target connected-mode capability registry

These variables are allowed as connected-mode targets only. They are not current standalone-core facts until implemented in `db/access.php`, presets, language strings, PHPUnit tests, Behat tests, and service/page checks.

| Variable | Target capability | Owner | Status | Implementation rule |
|---|---|---|---|---|
| `CAP_MANAGE_KONNAXION` | `local/uckk:manageintegrations` | `local_uckk` | implemented_now_as_generic | Use current generic integration capability unless a narrower migration is implemented. |
| `CAP_MAP_KONNAXION_OBJECTS` | `local/uckk:manageintegrations` | `local_uckk` | implemented_now_as_generic | Mapping administration is covered by `manageintegrations` until a narrower capability exists. |
| `CAP_VIEW_KONNAXION_LOGS` | `local/uckk:manageintegrations` | `local_uckk` | implemented_now_as_generic | Sync/status logs are integration-admin data until a narrower capability exists. |
| `CAP_REQUEST_SMART_VOTE` | `mod/uckkassembly:requestsmartvote` | `mod_uckkassembly` | target_connected_mode | Must be added before Smart Vote request UI/service is enabled. |
| `CAP_VIEW_SMART_VOTE` | `mod/uckkassembly:viewsmartvote` | `mod_uckkassembly` | target_connected_mode | Must be added before Smart Vote readings are displayed. |
| `CAP_REVIEW_SMART_VOTE` | `mod/uckkassembly:reviewsmartvote` | `mod_uckkassembly` | target_connected_mode | Must be added before review/import workflow is enabled. |
| `CAP_CONTEST_SMART_VOTE` | `mod/uckkassembly:contestsmartvote` | `mod_uckkassembly` | target_connected_mode | Must be added before Smart Vote contestation is enabled. |
| `CAP_ARCHIVE_SMART_VOTE` | `mod/uckkarchive:archivesmartvote` | `mod_uckkarchive` | target_connected_mode | Must be added before Smart Vote archive action is enabled. |
| `CAP_VIEW_SMART_VOTE_REPORTS` | `report/uckk:viewsmartvotereports` | `report_uckk` | target_connected_mode | Must be added before Smart Vote reports are enabled. |

### 9.1 Capability migration rule

If the project chooses to introduce narrower Konnaxion capabilities later, the migration must update all of these together:

```text
db/access.php
presets/capabilities.json
admin/tool/uckkseed/presets/capabilities.json
language strings
service/page capability checks
role presets
PHPUnit allow/deny tests
Behat visibility tests
docs/05_roles_permissions_and_security.md
docs/11_cross_doc_alignment_registry.md
```

Until that migration exists, documentation must not state that the narrower `local/uckk:*konnaxion*` capabilities are implemented.

## 10. Deprecated and forbidden capability aliases

The following names must not be used as active canonical values.

```text
local/uckk:managekonnaxion
local/uckk:configurekonnaxion
local/uckk:managekonnaxionmappings
local/uckk:managekonnaxionidentitymap
local/uckk:managekonnaxionobjectmap
local/uckk:mapkonnaxionobjects
local/uckk:viewkonnaxionstatus
local/uckk:viewkonnaxionlogs
local/uckk:syncsmartvote
local/uckk:viewsmartvotelogs
mod/uckkassembly:opensmartvote
mod/uckkassembly:viewsmarkvotereading
mod/uckkassembly:viewsmartvotereading
mod/uckkassembly:usesmartvote
mod/uckkassembly:exportsmartvotetarget
mod/uckkassembly:importsmartvoteresult
mod/uckkassembly:invalidatesmartvote
mod/uckkassembly:contestsmartvotereading
mod/uckkarchive:viewsmartvotesnapshot
mod/uckkarchive:archivesmartvotesnapshot
report/uckk:viewsmartvote
report/uckk:exportsmartvote
report/uckk:viewkonnaxionlogs
```

Special typo ban:

```text
mod/uckkassembly:viewsmarkvotereading
```

This typo must never appear in final code, presets, or active documentation except inside this deprecated-alias registry.

## 11. Current implemented table registry

These tables exist in the current code snapshot and are valid standalone-core tables.

### 11.1 `local_uckk`

```text
local_uckk_program
local_uckk_pathway
local_uckk_player
local_uckk_role
local_uckk_canon
local_uckk_prov
local_uckk_reflect
local_uckk_map
local_uckk_pathway_stat
```

### 11.2 `mod_uckkassembly`

```text
uckkassembly
uckkassembly_motion
uckkassembly_amend
uckkassembly_object
uckkassembly_vote
uckkassembly_decision
uckkassembly_minutes
uckkassembly_contest
```

### 11.3 `mod_uckkarchive`

```text
uckkarchive
uckkarchive_item
uckkarchive_kristal
uckkarchive_proof
uckkarchive_prov
uckkarchive_rev
uckkarchive_export
```

### 11.4 Admin and AI tables

```text
tool_uckkseed_run
tool_uckkseed_log
tool_uckkintegrity_case
tool_uckkintegrity_note
tool_uckkintegrity_appeal
aiprovider_uckk_log
```

## 12. Target connected-mode table registry

These tables are target connected-mode tables. They are not current code-snapshot tables and must not be required for standalone-core install or tests until implemented.

| Variable | Target table | Owner | Status | Rule |
|---|---|---|---|---|
| `TABLE_KONNAXION_USER_MAP` | `local_uckk_kx_user_map` | `local_uckk` | target_connected_mode | Maps Moodle users to Konnaxion identities without unnecessary external personal data. |
| `TABLE_KONNAXION_OBJECT_MAP` | `local_uckk_kx_object_map` | `local_uckk` | target_connected_mode | Maps Moodle objects to Konnaxion objects. |
| `TABLE_KONNAXION_SYNC_LOG` | `local_uckk_kx_sync_log` | `local_uckk` | target_connected_mode | Logs sync attempts, failures, retries, endpoint responses, and idempotency keys. |
| `TABLE_SMART_VOTE_TARGET_MAP` | `uckkassembly_kx_vote_target` | `mod_uckkassembly` | target_connected_mode | Maps Assembly motions, decisions, or deliberation objects to Konnaxion vote targets. |
| `TABLE_SMART_VOTE_SNAPSHOT` | `uckkassembly_sv_snapshot` | `mod_uckkassembly` | target_connected_mode | Stores Moodle-side immutable Smart Vote reading snapshots. |
| `TABLE_SMART_VOTE_RESULT_AUDIT` | `uckkassembly_sv_result_audit` | `mod_uckkassembly` | target_connected_mode | Stores review, correction, contestation, and supersession trail for Smart Vote results. |

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`.

Deprecated table names:

```text
local_uckk_konnaxion_identity_map
local_uckk_konnaxion_object_map
uckkassembly_smartvote_snapshot
uckkassembly_smartvote_result
uckkassembly_smartvote_audit
```

## 13. Current implemented service registry

These service functions exist in the current code snapshot and are valid standalone-core service names.

### 13.1 `local_uckk`

```text
local_uckk_get_player_dashboard
local_uckk_get_programs
local_uckk_get_pathways
local_uckk_get_pathway_map
local_uckk_get_player_profile
local_uckk_update_player_profile
local_uckk_get_canon_items
local_uckk_get_status_options
```

### 13.2 `mod_uckkassembly`

```text
mod_uckkassembly_get_assembly_state
mod_uckkassembly_get_motion_list
mod_uckkassembly_get_motion
mod_uckkassembly_submit_motion
mod_uckkassembly_submit_amendment
mod_uckkassembly_submit_objection
mod_uckkassembly_submit_vote
mod_uckkassembly_get_vote_results
mod_uckkassembly_get_decision
mod_uckkassembly_publish_decision
mod_uckkassembly_contest_decision
mod_uckkassembly_get_minutes_panel
mod_uckkassembly_save_minutes
mod_uckkassembly_publish_minutes
mod_uckkassembly_get_integrity_panel
mod_uckkassembly_open_integrity_case
mod_uckkassembly_get_archive_preview
mod_uckkassembly_archive_assembly
```

### 13.3 `mod_uckkarchive`

```text
mod_uckkarchive_get_archive
mod_uckkarchive_get_archive_items
mod_uckkarchive_get_archive_item
mod_uckkarchive_get_archive_item_card
mod_uckkarchive_get_proofs
mod_uckkarchive_get_provenance_panel
mod_uckkarchive_get_kristal
mod_uckkarchive_get_revisions
mod_uckkarchive_get_restricted_item
mod_uckkarchive_save_item_draft
mod_uckkarchive_add_item
mod_uckkarchive_add_proof
mod_uckkarchive_update_provenance
mod_uckkarchive_validate_item
mod_uckkarchive_revise_item
mod_uckkarchive_create_kristal
mod_uckkarchive_update_kristal
mod_uckkarchive_get_export_preview
mod_uckkarchive_export_items
mod_uckkarchive_get_export_status
```

### 13.4 `tool_uckkintegrity`

```text
tool_uckkintegrity_open_case
```

## 14. Target connected-mode service and event registry

These names are target connected-mode names. They must not be declared as active until matching classes, access checks, privacy handling, tests, and UI gates exist.

| Variable | Target name | Owner | Status |
|---|---|---|---|
| `SERVICE_CREATE_KONNAXION_MAPPING` | `local_uckk_create_konnaxion_mapping` | `local_uckk` | target_connected_mode |
| `SERVICE_GET_KONNAXION_MAPPING` | `local_uckk_get_konnaxion_mapping` | `local_uckk` | target_connected_mode |
| `SERVICE_SYNC_KONNAXION` | `local_uckk_sync_konnaxion` | `local_uckk` | target_connected_mode |
| `SERVICE_REQUEST_SMART_VOTE_READING` | `mod_uckkassembly_request_smart_vote_reading` | `mod_uckkassembly` | target_connected_mode |
| `SERVICE_IMPORT_SMART_VOTE_SNAPSHOT` | `mod_uckkassembly_import_smart_vote_snapshot` | `mod_uckkassembly` | target_connected_mode |
| `SERVICE_CONTEST_SMART_VOTE_SNAPSHOT` | `mod_uckkassembly_contest_smart_vote_snapshot` | `mod_uckkassembly` | target_connected_mode |
| `SERVICE_GET_SMART_VOTE_REPORT` | `report_uckk_get_smart_vote_report` | `report_uckk` | target_connected_mode |
| `EVENT_KONNAXION_MAPPING_CREATED` | `local_uckk\\event\\konnaxion_mapping_created` | `local_uckk` | target_connected_mode |
| `EVENT_KONNAXION_SYNC_COMPLETED` | `local_uckk\\event\\konnaxion_sync_completed` | `local_uckk` | target_connected_mode |
| `EVENT_SMART_VOTE_READING_REQUESTED` | `mod_uckkassembly\\event\\smart_vote_reading_requested` | `mod_uckkassembly` | target_connected_mode |
| `EVENT_SMART_VOTE_SNAPSHOT_IMPORTED` | `mod_uckkassembly\\event\\smart_vote_snapshot_imported` | `mod_uckkassembly` | target_connected_mode |
| `EVENT_SMART_VOTE_SNAPSHOT_CONTESTED` | `mod_uckkassembly\\event\\smart_vote_snapshot_contested` | `mod_uckkassembly` | target_connected_mode |
| `EVENT_SMART_VOTE_SNAPSHOT_ARCHIVED` | `mod_uckkassembly\\event\\smart_vote_snapshot_archived` | `mod_uckkassembly` | target_connected_mode |

## 15. Konnaxion object and field registry

These are semantic references to Konnaxion-side objects. Moodle code must access them only through an approved API, SDK, or adapter.

| Variable | Canonical external object | Moodle-side rule |
|---|---|---|
| `KONNAXION_OBJECT_VOTE` | `Vote` | External vote object mapped to a Moodle-side vote target or reading source. |
| `KONNAXION_OBJECT_VOTE_MODALITY` | `VoteModality` | External voting modality/method mapped to a Moodle-side reading method. |
| `KONNAXION_OBJECT_VOTE_RESULT` | `VoteResult` | External result mapped into a Moodle-side Smart Vote reading snapshot. |
| `KONNAXION_OBJECT_INTEGRATION_MAPPING` | `IntegrationMapping` | External mapping object mirrored by Moodle-side mapping tables. |
| `KONNAXION_OBJECT_USER_EXPERTISE_SCORE` | `UserExpertiseScore` | External metadata only; never Moodle authority. |
| `KONNAXION_OBJECT_USER_ETHICS_SCORE` | `UserEthicsScore` | External metadata only; never Moodle authority. |
| `KONNAXION_OBJECT_CONFIDENTIALITY_SETTING` | `ConfidentialitySetting` | External metadata only; must not bypass Moodle visibility. |
| `KONNAXION_OBJECT_SCORE_HISTORY` | `ScoreHistory` | External metadata only; must not become Moodle source record. |

Moodle-side field keys:

| Variable | Canonical field/key | Rule |
|---|---|---|
| `KONNAXION_EXTERNAL_ID_FIELD` | `externalid` | Stores the Konnaxion identifier; never stores secrets. |
| `KONNAXION_EXTERNAL_TYPE_FIELD` | `externaltype` | Stores the Konnaxion object type. |
| `KONNAXION_SOURCE_VERSION_FIELD` | `sourceversion` | Stores the external source version, revision, or timestamp where available. |
| `KONNAXION_SYNC_STATUS_FIELD` | `syncstatus` | Stores Moodle-side sync state using `KONNAXION_SYNC_STATUS_ENUM`. |
| `KONNAXION_PROVENANCE_HASH_FIELD` | `provenancehash` | Stores integrity hash for imported snapshots or mapped records where needed. |

## 16. Smart Vote reading field registry

| Variable | Canonical field/key | Rule |
|---|---|---|
| `SV_RAW_DATA` | `raw_data` | Imported or referenced source facts before interpretation. |
| `SV_READING_METHOD` | `reading_method` | Declared method, modality, weighting, or algorithmic reading rule. |
| `SV_COMPUTED_READING` | `computed_reading` | Non-sovereign Smart Vote output. |
| `SV_EXPERTISE_WEIGHT` | `expertise_weight` | Weighted-reading factor; personal or sensitive where linkable to a user. |
| `SV_HUMAN_DECISION` | `human_institutional_decision` | Moodle-side Assembly decision; must not be overwritten by Smart Vote. |
| `SV_MINORITY_REPORT` | `minority_report` | Documented minority position or dissenting signal. |
| `SV_INTEGRITY_WARNING` | `integrity_warning` | Warning or flag that may open integrity review but is not itself a sanction. |
| `SV_CONTESTATION_STATUS` | `contestation_status` | Contestation state for the snapshot or decision linkage. |
| `SV_ARCHIVE_ITEM_ID` | `archiveitemid` | Link to preserved archive item when archived. |

## 17. Status and state registry

These are connected-mode target enums. They must not be required by standalone-core code until matching tables/classes exist.

| Variable | Allowed values | Owner | Status |
|---|---|---|---|
| `KONNAXION_SYNC_STATUS_ENUM` | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled` | `local_uckk` | target_connected_mode |
| `KONNAXION_MAPPING_STATUS_ENUM` | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` | `local_uckk` | target_connected_mode |
| `SMART_VOTE_TARGET_STATUS_ENUM` | `draft`, `mapped`, `active`, `closed`, `archived`, `contested` | `mod_uckkassembly` | target_connected_mode |
| `SMART_VOTE_SNAPSHOT_STATUS_ENUM` | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` | `mod_uckkassembly` | target_connected_mode |
| `SMART_VOTE_AUDIT_STATUS_ENUM` | `recorded`, `reviewed`, `corrected`, `contested`, `resolved` | `mod_uckkassembly` | target_connected_mode |

## 18. Preset registry

The code snapshot contains both runtime preset locations and repository-level preset mirrors. Runtime docs should prefer `admin/tool/uckkseed/presets/*` when referring to installed Moodle behavior.

| Variable | Canonical runtime preset | Repository mirror | Status | Rule |
|---|---|---|---|---|
| `PRESET_CAPABILITIES` | `admin/tool/uckkseed/presets/capabilities.json` | `uckk-presets/capabilities.json` | implemented_now | Roles and capabilities. |
| `PRESET_CATEGORIES` | `admin/tool/uckkseed/presets/categories.json` | `uckk-presets/categories.json` | implemented_now | Course categories. |
| `PRESET_COURSES` | `admin/tool/uckkseed/presets/courses.json` | `uckk-presets/courses.json` | implemented_now | Courses. |
| `PRESET_COURSE_TEMPLATES` | `admin/tool/uckkseed/presets/course_templates.json` | `uckk-presets/course_templates.json` | implemented_now | Course templates. |
| `PRESET_COHORTS` | `admin/tool/uckkseed/presets/cohorts.json` | `uckk-presets/cohorts.json` | implemented_now | Cohorts. |
| `PRESET_ROLES` | `admin/tool/uckkseed/presets/roles.json` | `uckk-presets/roles.json` | implemented_now | Role definitions. |
| `PRESET_COMPETENCIES` | `admin/tool/uckkseed/presets/competencies.json` | `uckk-presets/competencies.json` | implemented_now | Competencies. |
| `PRESET_BADGES` | `admin/tool/uckkseed/presets/badges.json` | `uckk-presets/badges.json` | implemented_now | Badges. |
| `PRESET_REPORTS` | `admin/tool/uckkseed/presets/reports.json` | `uckk-presets/reports.json` | implemented_now | Report seed definitions. |
| `PRESET_ARCHIVE_TEMPLATES` | `admin/tool/uckkseed/presets/archive_templates.json` | `uckk-presets/archive_templates.json` | implemented_now | Archive templates. |
| `PRESET_ASSEMBLY_TEMPLATES` | `admin/tool/uckkseed/presets/assembly_templates.json` | `uckk-presets/assembly_templates.json` | implemented_now | Assembly templates. |
| `PRESET_CHALLENGE_TEMPLATES` | `admin/tool/uckkseed/presets/challenge_templates.json` | `uckk-presets/challenge_templates.json` | implemented_now | Challenge templates. |
| `PRESET_STATE_MACHINES` | `admin/tool/uckkseed/presets/state_machines.json` | `uckk-presets/state_machines.json` | target_or_missing_in_snapshot | Must exist before docs make it a hard seed input. |
| `PRESET_EVENTS` | `admin/tool/uckkseed/presets/events.json` | `uckk-presets/events.json` | target_or_missing_in_snapshot | Must exist before docs make it a hard seed input. |
| `PRESET_KONNAXION_MAPPINGS` | `admin/tool/uckkseed/presets/konnaxion_mappings.json` | `uckk-presets/konnaxion_mappings.json` | target_connected_mode | Must not be required for standalone-core seed. |
| `PRESET_PRIVACY_RETENTION` | `admin/tool/uckkseed/presets/privacy_retention.json` | `uckk-presets/privacy_retention.json` | target_or_missing_in_snapshot | Must exist before docs make it a hard seed input. |
| `PRESET_EXPORTS` | `admin/tool/uckkseed/presets/exports.json` | `uckk-presets/exports.json` | target_or_missing_in_snapshot | Must exist before docs make it a hard seed input. |

Docs may use short paths such as `presets/capabilities.json` only when the surrounding text explicitly means “packaged preset path.” Runtime implementation docs should use the full `admin/tool/uckkseed/presets/...` path.

## 19. Conditionality matrix

| Requirement | `standalone_core` | `connected_konnaxion` |
|---|---|---|
| Moodle plugin installation | required | required |
| Core seed dry-run/apply | required | required |
| Konnaxion credentials/endpoints | forbidden as requirement | required when enabled |
| Ordinary Assembly motions/votes/readings/decisions | required | required |
| Smart Vote request/import/review/contest/archive/report | not required; hidden/fail-closed | required |
| Archive evidence/provenance/versioning | required | required |
| Smart Vote snapshot archive | not required | required if Smart Vote used in decision context |
| Integrity cases | required | required |
| Smart Vote anomaly/integrity review | not required | required |
| Core UCKK reports | required | required |
| Konnaxion/Smart Vote reports | not required | required |
| AI provider configurable/disableable | required | required |
| Konnaxion outage/retry/idempotency tests | not required | required |
| Privacy provider coverage for core records | required | required |
| Privacy provider coverage for Konnaxion/Smart Vote records | not required unless stored | required when stored/exposed |

## 20. Required reference line for each doc

Each documentation file from `DOC_00` through `DOC_10` must include this line near the top:

```text
This document consumes docs/11_cross_doc_alignment_registry.md for shared variables, document paths, capability names, table names, operating modes, deprecated aliases, and standalone-vs-connected requirements.
```

## 21. Drift detection rules

### 21.1 Deprecated document path detector

The following should return no active references except inside this registry or explicit migration notes:

```bash
grep -RIn '10_konnaxion_alignment_and_correction_plan.md\|OPTIONAL_KONNAXION_CONTRACT\|10_implementation_correction_plan.md' docs/ release/ admin/tool/uckkseed/presets/ uckk-presets/ || true
```

### 21.2 Deprecated capability detector

The following should return no active references except inside this registry or explicit migration notes:

```bash
grep -RIn 'configurekonnaxion\|managekonnaxion\|mapkonnaxionobjects\|viewkonnaxionlogs\|usesmartvote\|viewsmarkvotereading\|viewsmartvotereading\|opensmartvote\|importsmartvoteresult\|exportsmartvotesnapshot\|contestsmartvotereading' docs/ admin/tool/uckkseed/presets/ uckk-presets/ local/ mod/ report/ || true
```

### 21.3 Connected-mode target detector

The following may return references in docs, but executable code must contain matching classes before connected mode is considered implemented:

```bash
grep -RIn 'local_uckk_kx_user_map\|local_uckk_kx_object_map\|local_uckk_kx_sync_log\|uckkassembly_kx_vote_target\|uckkassembly_sv_snapshot\|uckkassembly_sv_result_audit' docs/ local/ mod/ report/ admin/tool/uckkseed/presets/ uckk-presets/ || true
```

### 21.4 Filetype blocker detector

The code snapshot contains generated-file risks. These are implementation blockers, not documentation variables:

```bash
grep -RIn --include='*.js' '<?php\|require_once\|\$PAGE\|optional_param\|required_param' .
grep -RIn --include='*.php' '```' .
```

Any match in executable files must be fixed in code, not normalized in documentation.

## 22. Release-manifest rule

Every release manifest must state both modes separately:

```text
Core standalone status: pass/fail
Connected Konnaxion status: not included / included-pass / included-fail
```

A release may pass `standalone_core` while `connected_konnaxion` is not included, provided no docs or UI claim Konnaxion/Smart Vote is operational.

A release may not claim `connected_konnaxion` support unless all target connected-mode variables used by that release are implemented in code, presets, privacy providers, tests, and release evidence.

## 23. Final canonical sentence

Use this sentence consistently across the documentation set:

```text
UCKK-Moodle is self-standing. Konnaxion is an optional connected-mode integration that can add Smart Vote readings, EkoH/advisory signals, and cross-module organization. Konnaxion never replaces Moodle permissions, UCKK academic authority, Assembly decisions, Archive provenance, Integrity review, or AI non-sovereignty rules.
```
