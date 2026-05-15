Updated `03_plugin_specifications.md` content below, based on the attached plugin specification and the Konnaxion alignment instructions.  

# 03 — Plugin Specifications

**Status:** Final plugin contract, revised for standalone-core and optional Konnaxion-connected operation  
**Purpose:** Define what each UCKK-Moodle plugin must deliver before code generation, install testing, PHPUnit, Behat, or release packaging, while preserving UCKK-Moodle as a self-standing Moodle distribution.

## 0. Global implementation contract

Every plugin must be Moodle-native and must respect its plugin path, component name, file type, capability model, privacy model, operating-mode boundary, Smart Vote boundary, Konnaxion integration boundary, and test boundary.

UCKK-Moodle must be installable and usable in standalone-core mode. Konnaxion and Smart Vote integration are optional connected-mode features. A plugin may expose Konnaxion-connected behavior only when the Konnaxion bridge is enabled, configured, permission-checked, privacy-covered, and tested.

### 0.1 Filetype correctness

Every generated file must match its extension and Moodle location.

```text
.php files must start with <?php unless they are intentional mixed PHP/HTML layout files.
.php files must not contain Markdown fences or generator notes.
.js files under amd/src/ must contain JavaScript only.
.mustache files must contain Mustache templates only.
.json files must be valid JSON.
.xml files must be valid XMLDB or Moodle XML as appropriate.
```

### 0.2 Component correctness

Every `version.php` must declare the component that matches its physical path.

```text
theme/uckk                      => theme_uckk
course/format/uckk              => format_uckk
local/uckk                      => local_uckk
blocks/uckk_dashboard           => block_uckk_dashboard
mod/uckkchallenge               => mod_uckkchallenge
mod/uckkassembly                => mod_uckkassembly
mod/uckkarchive                 => mod_uckkarchive
admin/tool/uckkseed             => tool_uckkseed
admin/tool/uckkintegrity        => tool_uckkintegrity
report/uckk                     => report_uckk
ai/provider/uckk                => aiprovider_uckk
```

Package names, language component names, capability prefixes, service names, event namespaces, privacy providers, and tests must use the same component identity.

### 0.3 Required class layer

A plugin is not complete if it only contains procedural controllers, `lib.php`, templates, and AMD files. When the plugin declares services, forms, events, observers, privacy metadata, scheduled tasks, renderables, or business logic, it must include matching autoloaded classes under `classes/`.

Use these directories where relevant:

```text
classes/external/
classes/form/
classes/event/
classes/local/
classes/output/
classes/privacy/provider.php
classes/task/
classes/table/
classes/reportbuilder/
classes/observer.php or classes/observer/
classes/hook_listener.php
classes/service/
```

### 0.4 Page, service, and capability contract

Every PHP page and external service must explicitly document and enforce:

```text
required login state
context level
required capability
allowed roles/archetypes
read/write nature
AJAX/mobile exposure, if any
privacy impact
main table ownership
Konnaxion/Smart Vote boundary, if relevant
important events emitted
```

### 0.5 Test contract

Every plugin must include tests matching its responsibilities.

```text
tests/*_test.php for PHP unit/integration coverage
tests/behat/*.feature for major user workflows
tests/fixtures/ for reusable test records where needed
tests/external/*_test.php for web services where needed
```

### 0.6 Operating-mode variables

These variables are binding for this document and must be used when separating core plugin obligations from optional Konnaxion-connected obligations.

| Variable | Canonical value | Rule |
|---|---|---|
| `OPERATING_MODE_STANDALONE` | `standalone_core` | UCKK-Moodle installs, seeds, teaches, deliberates, archives, reports, and passes core tests without Konnaxion. |
| `OPERATING_MODE_KONNAXION_CONNECTED` | `connected_konnaxion` | Optional profile where Konnaxion bridge, Smart Vote readings, EkoH/advisory signals, mappings, sync logs, and connected reports are enabled. |
| `KONNAXION_REQUIRED_FOR_CORE` | `false` | No plugin may make Konnaxion a hard dependency for standalone install or ordinary UCKK workflows. |
| `SMART_VOTE_REQUIRED_FOR_CORE` | `false` | Assemblies, archives, reports, integrity review, and seed operations must work without Smart Vote. |
| `KONNAXION_DEFAULT_STATE` | `disabled` | Konnaxion bridge settings, services, tasks, UI panels, and reports are hidden or fail closed until enabled. |
| `SMART_VOTE_DEFAULT_STATE` | `disabled_until_connected` | Smart Vote request/import/report actions are available only in connected mode and only with explicit Moodle capabilities. |
| `CORE_RELEASE_GATE` | `standalone_core_install_and_workflows_pass` | Core acceptance cannot require Konnaxion credentials, endpoints, mappings, Smart Vote snapshots, or Konnaxion reports. |
| `CONNECTED_RELEASE_GATE` | `konnaxion_connected_profile_passes` | Connected acceptance applies only when Konnaxion/Smart Vote features are enabled for that release profile. |

### 0.7 Canonical alignment variables

This document consumes the canonical alignment variables defined by `00_master_execution_doctrine.md`. Plugin specifications must use these names consistently in code generation, tests, services, events, capabilities, privacy providers, seed presets, and reports.

#### Document variables

| Variable                      | Canonical value                                   | Rule                                                                                    |
| ----------------------------- | ------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `DOC_00`                      | `00_master_execution_doctrine.md`                 | Root doctrine and correction authority.                                                 |
| `DOC_01`                      | `01_domain_boundaries_and_glossary.md`            | Domain vocabulary and forbidden/allowed wording.                                        |
| `DOC_02`                      | `02_distribution_architecture.md`                 | Plugin distribution, dependency direction, and integration layer.                       |
| `DOC_03`                      | `03_plugin_specifications.md`                     | Plugin implementation contract.                                                         |
| `DOC_04`                      | `04_data_model_and_storage.md`                    | Table ownership, enums, state machines, and storage constraints.                        |
| `DOC_05`                      | `05_roles_permissions_and_security.md`            | Capability and access-control registry.                                                 |
| `DOC_06`                      | `06_pedagogy_courses_competencies_badges.md`      | Courses, competencies, evidence, and badge rules.                                       |
| `DOC_07`                      | `07_challenges_and_assemblies.md`                 | Challenge, Assembly, Smart Vote, decision, and contestation workflows.                  |
| `DOC_08`                      | `08_integrity_archives_and_privacy.md`            | Archive, privacy, retention, redaction, and integrity safeguards.                       |
| `DOC_09`                      | `09_integrations_reporting_delivery.md`           | Integration contracts, reports, exports, and delivery checks.                           |
| `DOC_10`                      | `10_konnaxion_smart_vote_integration_contract.md` | Optional connected-mode Konnaxion Smart Vote integration contract.                      |
| `LEGACY_DOC_10`               | `10_implementation_correction_plan.md`            | Deprecated. Must not be used as the active product-tree target.                         |

#### Boundary variables

| Variable                    | Canonical value                                                                                                                            | Rule                                                                                          |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------- |
| `SOURCE_FAMILY_KONNAXION`   | `Konnaxion external source family`                                                                                                         | External source of truth for Konnaxion-side Smart Vote objects and semantics.                 |
| `EXTERNAL_SYSTEM_KONNAXION` | `Konnaxion`                                                                                                                                | External system integrated through Moodle services only.                                      |
| `SMART_VOTE_CANONICAL_RULE` | `Konnaxion computes Smart Vote readings. UCKK-Moodle owns Assembly decisions. Archives preserve both, with provenance and contestability.` | Must be preserved in every plugin that touches Smart Vote.                                    |
| `SMART_VOTE_AUTHORITY`      | `computed_reading_only`                                                                                                                    | Smart Vote may inform; it must not decide, award, validate, sanction, or close contestations. |
| `ASSEMBLY_AUTHORITY`        | `human_institutional_decision`                                                                                                             | Final Assembly decisions belong to Moodle-side Assembly workflow and permissions.             |
| `ARCHIVE_AUTHORITY`         | `provenance_and_contestation_memory`                                                                                                       | Archives preserve source, snapshot, decision, minority report, and contestation trail.        |
| `DIRECT_WRITE_RULE`         | `external_systems_never_write_moodle_source_tables`                                                                                        | Konnaxion must not write directly into Moodle source tables.                                  |
| `PERMISSION_RULE`           | `moodle_capabilities_remain_authoritative`                                                                                                 | External roles, labels, or identifiers never grant Moodle authority by themselves.            |

#### Plugin ownership variables

| Variable                     | Canonical owner      | Responsibilities                                                                                                     |
| ---------------------------- | -------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `KONNAXION_BRIDGE_OWNER`     | `local_uckk`         | Configuration, authentication settings, endpoint client, object mapping, sync logs, shared integration services.     |
| `SMART_VOTE_WORKFLOW_OWNER`  | `mod_uckkassembly`   | Smart Vote reading requests, vote-target mapping, snapshots, review, contestation, and Assembly linkage.             |
| `ASSEMBLY_DECISION_OWNER`    | `mod_uckkassembly`   | Motions, deliberation, decision publication, minority report, and decision contestability.                           |
| `SMART_VOTE_ARCHIVE_OWNER`   | `mod_uckkarchive`    | Archive records and file areas for Smart Vote snapshots, decisions, minutes, and provenance packages.                |
| `SMART_VOTE_REPORT_OWNER`    | `report_uckk`        | Smart Vote reports, institutional exports, filters, and privacy-aware visibility.                                    |
| `SMART_VOTE_INTEGRITY_OWNER` | `tool_uckkintegrity` | Integrity warnings, contested readings, correction cases, and restricted review workflows.                           |
| `SMART_VOTE_SEED_OWNER`      | `tool_uckkseed`      | Idempotent presets for capabilities, mappings, report definitions, and integration defaults.                         |
| `SMART_VOTE_PRIVACY_OWNER`   | every storing plugin | Each plugin that stores personal data owns its privacy provider, export, delete, anonymisation, and retention tests. |

#### Konnaxion object variables

| Variable                               | Canonical value      | Moodle-side use                                                              |
| -------------------------------------- | -------------------- | ---------------------------------------------------------------------------- |
| `KONNAXION_OBJECT_VOTE`                | `Vote`               | External vote object mapped to a Moodle-side vote target or reading source.  |
| `KONNAXION_OBJECT_VOTE_MODALITY`       | `VoteModality`       | External voting modality/method mapped to a Moodle-side reading method.      |
| `KONNAXION_OBJECT_VOTE_RESULT`         | `VoteResult`         | External result mapped into a Moodle-side Smart Vote reading snapshot.       |
| `KONNAXION_OBJECT_INTEGRATION_MAPPING` | `IntegrationMapping` | External mapping object mirrored by Moodle-side mapping tables.              |
| `KONNAXION_EXTERNAL_ID_FIELD`          | `externalid`         | Stores the Konnaxion identifier; never stores secrets.                       |
| `KONNAXION_EXTERNAL_TYPE_FIELD`        | `externaltype`       | Stores the Konnaxion object type.                                            |
| `KONNAXION_SOURCE_VERSION_FIELD`       | `sourceversion`      | Stores the external source version, revision, or timestamp where available.  |
| `KONNAXION_SYNC_STATUS_FIELD`          | `syncstatus`         | Stores Moodle-side sync state using `KONNAXION_SYNC_STATUS_ENUM`.            |
| `KONNAXION_PROVENANCE_HASH_FIELD`      | `provenancehash`     | Stores integrity hash for imported snapshots or mapped records where needed. |

#### Moodle-side table variables

| Variable                        | Canonical table                | Owner              | Purpose                                                                                        |
| ------------------------------- | ------------------------------ | ------------------ | ---------------------------------------------------------------------------------------------- |
| `TABLE_KONNAXION_USER_MAP`      | `local_uckk_kx_user_map`       | `local_uckk`       | Maps Moodle users to Konnaxion identities without exposing unnecessary external personal data. |
| `TABLE_KONNAXION_OBJECT_MAP`    | `local_uckk_kx_object_map`     | `local_uckk`       | Maps Moodle objects to Konnaxion objects.                                                      |
| `TABLE_KONNAXION_SYNC_LOG`      | `local_uckk_kx_sync_log`       | `local_uckk`       | Logs sync attempts, failures, retries, endpoint responses, and idempotency keys.               |
| `TABLE_SMART_VOTE_TARGET_MAP`   | `uckkassembly_kx_vote_target`  | `mod_uckkassembly` | Maps Assembly motions, decisions, or deliberation objects to Konnaxion vote targets.           |
| `TABLE_SMART_VOTE_SNAPSHOT`     | `uckkassembly_sv_snapshot`     | `mod_uckkassembly` | Stores Moodle-side immutable Smart Vote reading snapshots.                                     |
| `TABLE_SMART_VOTE_RESULT_AUDIT` | `uckkassembly_sv_result_audit` | `mod_uckkassembly` | Stores review, correction, contestation, and supersession trail for Smart Vote results.        |

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`.

#### Smart Vote field variables

| Variable                 | Canonical field/key            | Rule                                                                         |
| ------------------------ | ------------------------------ | ---------------------------------------------------------------------------- |
| `SV_RAW_DATA`            | `raw_data`                     | Imported or referenced source facts before interpretation.                   |
| `SV_READING_METHOD`      | `reading_method`               | The declared method, modality, weighting, or algorithmic reading rule.       |
| `SV_COMPUTED_READING`    | `computed_reading`             | The non-sovereign Smart Vote output.                                         |
| `SV_EXPERTISE_WEIGHT`    | `expertise_weight`             | Any weighted-reading factor; personal or sensitive where linkable to a user. |
| `SV_HUMAN_DECISION`      | `human_institutional_decision` | Moodle-side Assembly decision; must not be overwritten by Smart Vote.        |
| `SV_MINORITY_REPORT`     | `minority_report`              | Documented minority position or dissenting signal.                           |
| `SV_INTEGRITY_WARNING`   | `integrity_warning`            | Warning or flag that may open integrity review but is not itself a sanction. |
| `SV_CONTESTATION_STATUS` | `contestation_status`          | Contestation state for the snapshot or decision linkage.                     |
| `SV_ARCHIVE_ITEM_ID`     | `archiveitemid`                | Link to preserved archive item when archived.                                |

#### State variables

| Variable                          | Allowed values                                                                                          | Owner              |
| --------------------------------- | ------------------------------------------------------------------------------------------------------- | ------------------ |
| `KONNAXION_SYNC_STATUS_ENUM`      | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled`                      | `local_uckk`       |
| `KONNAXION_MAPPING_STATUS_ENUM`   | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated`                                 | `local_uckk`       |
| `SMART_VOTE_TARGET_STATUS_ENUM`   | `draft`, `mapped`, `active`, `closed`, `archived`, `contested`                                          | `mod_uckkassembly` |
| `SMART_VOTE_SNAPSHOT_STATUS_ENUM` | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` | `mod_uckkassembly` |
| `SMART_VOTE_AUDIT_STATUS_ENUM`    | `recorded`, `reviewed`, `corrected`, `contested`, `resolved`                                            | `mod_uckkassembly` |

#### Capability variables

| Variable                      | Canonical capability                | Owner              |
| ----------------------------- | ----------------------------------- | ------------------ |
| `CAP_MANAGE_KONNAXION`        | `local/uckk:managekonnaxion`        | `local_uckk`       |
| `CAP_MAP_KONNAXION_OBJECTS`   | `local/uckk:mapkonnaxionobjects`    | `local_uckk`       |
| `CAP_VIEW_KONNAXION_LOGS`     | `local/uckk:viewkonnaxionlogs`      | `local_uckk`       |
| `CAP_REQUEST_SMART_VOTE`      | `mod/uckkassembly:requestsmartvote` | `mod_uckkassembly` |
| `CAP_VIEW_SMART_VOTE`         | `mod/uckkassembly:viewsmartvote`    | `mod_uckkassembly` |
| `CAP_REVIEW_SMART_VOTE`       | `mod/uckkassembly:reviewsmartvote`  | `mod_uckkassembly` |
| `CAP_CONTEST_SMART_VOTE`      | `mod/uckkassembly:contestsmartvote` | `mod_uckkassembly` |
| `CAP_ARCHIVE_SMART_VOTE`      | `mod/uckkarchive:archivesmartvote`  | `mod_uckkarchive`  |
| `CAP_VIEW_SMART_VOTE_REPORTS` | `report/uckk:viewsmartvotereports`  | `report_uckk`      |

#### Service and event variables

| Variable                              | Canonical name                                         | Owner              |
| ------------------------------------- | ------------------------------------------------------ | ------------------ |
| `SERVICE_CREATE_KONNAXION_MAPPING`    | `local_uckk_create_konnaxion_mapping`                  | `local_uckk`       |
| `SERVICE_GET_KONNAXION_MAPPING`       | `local_uckk_get_konnaxion_mapping`                     | `local_uckk`       |
| `SERVICE_SYNC_KONNAXION`              | `local_uckk_sync_konnaxion`                            | `local_uckk`       |
| `SERVICE_REQUEST_SMART_VOTE_READING`  | `mod_uckkassembly_request_smart_vote_reading`          | `mod_uckkassembly` |
| `SERVICE_IMPORT_SMART_VOTE_SNAPSHOT`  | `mod_uckkassembly_import_smart_vote_snapshot`          | `mod_uckkassembly` |
| `SERVICE_CONTEST_SMART_VOTE_SNAPSHOT` | `mod_uckkassembly_contest_smart_vote_snapshot`         | `mod_uckkassembly` |
| `SERVICE_GET_SMART_VOTE_REPORT`       | `report_uckk_get_smart_vote_report`                    | `report_uckk`      |
| `EVENT_KONNAXION_MAPPING_CREATED`     | `local_uckk\event\konnaxion_mapping_created`           | `local_uckk`       |
| `EVENT_KONNAXION_SYNC_COMPLETED`      | `local_uckk\event\konnaxion_sync_completed`            | `local_uckk`       |
| `EVENT_SMART_VOTE_READING_REQUESTED`  | `mod_uckkassembly\event\smart_vote_reading_requested`  | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_IMPORTED`  | `mod_uckkassembly\event\smart_vote_snapshot_imported`  | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_CONTESTED` | `mod_uckkassembly\event\smart_vote_snapshot_contested` | `mod_uckkassembly` |
| `EVENT_SMART_VOTE_SNAPSHOT_ARCHIVED`  | `mod_uckkassembly\event\smart_vote_snapshot_archived`  | `mod_uckkassembly` |

#### Preset variables

| Variable                    | Canonical preset                  | Required by                                                               |
| --------------------------- | --------------------------------- | ------------------------------------------------------------------------- |
| `PRESET_CAPABILITIES`       | `presets/capabilities.json`       | Roles, capabilities, tests, seed tool.                                    |
| `PRESET_STATE_MACHINES`     | `presets/state_machines.json`     | Workflow statuses and transitions.                                        |
| `PRESET_EVENTS`             | `presets/events.json`             | Event-class registry and tests.                                           |
| `PRESET_KONNAXION_MAPPINGS` | `presets/konnaxion_mappings.json` | Optional connected-mode Konnaxion object map defaults and validation fixtures. |
| `PRESET_PRIVACY_RETENTION`  | `presets/privacy_retention.json`  | Privacy, export, deletion, anonymisation, redaction, and retention tests. |
| `PRESET_EXPORTS`            | `presets/exports.json`            | Report/export definitions and acceptance evidence.                        |

## 1. `theme_uckk`

### Purpose

Provide visual identity, layouts, templates, and UI language for UCKK while keeping all institutional, grading, workflow, permission, archive, integrity, Smart Vote, Konnaxion, and AI decisions outside the theme.

### Must deliver

```text
theme/uckk/version.php
theme/uckk/config.php
theme/uckk/lib.php
theme/uckk/settings.php
theme/uckk/db/upgrade.php
theme/uckk/lang/en/theme_uckk.php
theme/uckk/lang/fr/theme_uckk.php
theme/uckk/scss/
theme/uckk/scss/preset/default.scss
theme/uckk/templates/
theme/uckk/layout/
theme/uckk/pix/
theme/uckk/amd/src/
theme/uckk/classes/output/
theme/uckk/classes/privacy/provider.php
theme/uckk/tests/
theme/uckk/tests/behat/
theme/uckk/README.md
```

### Features

* UCKK frontpage layout.
* Dashboard-friendly layout.
* Course, challenge, assembly, archive visual variants.
* King Klown visual layer without confusing symbolic and institutional authority.
* Smart Vote labels rendered as readings, never decisions, only where connected-mode data is enabled and visible.
* Konnaxion labels rendered as external-source data where shown in connected mode.
* Accessibility-compliant contrast, navigation, language strings, and keyboard behavior.
* French-first and English-ready UI.
* Boost-compatible inheritance and fallback behavior.
* SCSS-driven styling only; no legacy stylesheet dependency unless explicitly justified.

### Must not do

* No grading logic.
* No permission decisions beyond presentation checks already made by Moodle.
* No challenge, assembly, archive, integrity, report, seed, Konnaxion, Smart Vote, or AI workflow logic.
* No data ownership.
* No hidden authority escalation through templates or layouts.

### Required tests

```text
PHPUnit: theme callbacks, SCSS callbacks, privacy provider, output context builders.
Behat: frontpage, dashboard layout, language switching, accessibility-critical navigation, and connected-mode Smart Vote reading label visibility when enabled.
JS: AMD modules build cleanly through Moodle's Grunt pipeline.
```

## 2. `format_uckk`

### Purpose

Define the standard UCKK course structure and course-page presentation without owning institutional records, Smart Vote records, Konnaxion records, or activity workflows.

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
course/format/uckk/format.php
course/format/uckk/settings.php
course/format/uckk/db/access.php
course/format/uckk/db/upgrade.php
course/format/uckk/classes/output/
course/format/uckk/classes/local/
course/format/uckk/classes/privacy/provider.php
course/format/uckk/templates/
course/format/uckk/amd/src/
course/format/uckk/lang/en/format_uckk.php
course/format/uckk/lang/fr/format_uckk.php
course/format/uckk/tests/
course/format/uckk/tests/behat/
course/format/uckk/README.md
```

### Features

* UCKK section map.
* Section metadata.
* Evidence indicators.
* Archive indicators.
* Integrity warning indicators.
* Smart Vote reading indicators where connected mode is enabled and readings are sourced from `mod_uckkassembly`.
* Course index support.
* Completion summary.
* Compatibility with Moodle course editing, activity chooser, drag/drop section operations, and course backup/restore.

### Must not do

* Must not store source challenge submissions.
* Must not store source assembly motions, votes, readings, or decisions.
* Must not store Smart Vote snapshots.
* Must not store Konnaxion mappings.
* Must not store archive item content.
* Must not make integrity decisions.
* Must not bypass Moodle course editing permissions.

### Required tests

```text
PHPUnit: section map, metadata mapping, privacy provider, output builders.
Behat: create UCKK course, edit sections, view indicators, preserve Moodle editing behavior.
JS: course format AMD modules build and initialize only on intended regions.
```

## 3. `local_uckk`

### Purpose

Own the core institutional registry, shared services, shared navigation, component registry, and cross-plugin coordination rules. In connected mode, also own the Moodle-side Konnaxion bridge.

### Must deliver

```text
local/uckk/version.php
local/uckk/index.php
local/uckk/canon.php
local/uckk/pathways.php
local/uckk/settings.php
local/uckk/lib.php
local/uckk/db/install.xml
local/uckk/db/upgrade.php
local/uckk/db/access.php
local/uckk/db/services.php
local/uckk/db/events.php
local/uckk/db/hooks.php
local/uckk/db/tasks.php
local/uckk/classes/service/
local/uckk/classes/external/
local/uckk/classes/event/
local/uckk/classes/local/
local/uckk/classes/output/
local/uckk/classes/privacy/provider.php
local/uckk/classes/task/
local/uckk/classes/observer/
local/uckk/classes/hook_listener.php
local/uckk/templates/
local/uckk/amd/src/
local/uckk/lang/en/local_uckk.php
local/uckk/lang/fr/local_uckk.php
local/uckk/tests/
local/uckk/tests/behat/
local/uckk/README.md
```

### Optional connected-mode deliverables

When `OPERATING_MODE_KONNAXION_CONNECTED` is enabled, `local_uckk` must also deliver:

```text
local/uckk/konnaxion.php
local/uckk/classes/service/konnaxion_client.php
local/uckk/classes/service/konnaxion_mapping_service.php
local/uckk/classes/service/konnaxion_sync_service.php
local/uckk/classes/external/create_konnaxion_mapping.php
local/uckk/classes/external/get_konnaxion_mapping.php
local/uckk/classes/external/sync_konnaxion.php
local/uckk/classes/event/konnaxion_mapping_created.php
local/uckk/classes/event/konnaxion_sync_completed.php
```

These files and classes must not be hard requirements for standalone-core workflows unless the connected profile is explicitly selected.

### Owns

* Programs.
* Pathways.
* Symbolic roles.
* Joueur profiles.
* UCKK campus settings.
* Shared provenance records.
* Shared visibility rules.
* Navigation registry.
* Component registry and dependency map.

### Owns in connected mode only

* Konnaxion configuration.
* Konnaxion user mappings.
* Konnaxion object mappings.
* Konnaxion sync logs.
* Shared Konnaxion endpoint client and service contract.

### Does not own

* Challenge submissions.
* Assembly motions, votes, Smart Vote snapshots, or decisions.
* Archive item content.
* Integrity case records.
* AI prompt/response logs.
* Assembly Smart Vote target mappings.
* Assembly Smart Vote result audits.
* Final Assembly decisions.

When connected mode is enabled, `local_uckk` owns the Konnaxion bridge layer, not Smart Vote institutional meaning. It can authenticate to Konnaxion, map external identifiers, call endpoints, log sync state, and expose stable helper services. It must not publish Assembly decisions, overwrite Assembly records, or treat external Konnaxion identity as Moodle permission.

### Core required classes

```text
local_uckk\external\*
local_uckk\event\program_created
local_uckk\event\pathway_assigned
local_uckk\event\profile_updated
local_uckk\observer\user_observer
local_uckk\observer\course_observer
local_uckk\observer\category_observer
local_uckk\observer\completion_observer
local_uckk\hook_listener
local_uckk\task\*
local_uckk\privacy\provider
```

### Optional Konnaxion-connected classes

Required only when `OPERATING_MODE_KONNAXION_CONNECTED` is enabled:

```text
local_uckk\event\konnaxion_mapping_created
local_uckk\event\konnaxion_sync_completed
local_uckk\service\konnaxion_client
local_uckk\service\konnaxion_mapping_service
local_uckk\service\konnaxion_sync_service
local_uckk\external\create_konnaxion_mapping
local_uckk\external\get_konnaxion_mapping
local_uckk\external\sync_konnaxion
```

### Konnaxion bridge contract

If connected mode is enabled, `local_uckk` implements `KONNAXION_BRIDGE_OWNER`.

In connected mode, it must define these Moodle-side storage responsibilities in `DOC_04` and matching XMLDB tables:

```text
TABLE_KONNAXION_USER_MAP = local_uckk_kx_user_map
TABLE_KONNAXION_OBJECT_MAP = local_uckk_kx_object_map
TABLE_KONNAXION_SYNC_LOG = local_uckk_kx_sync_log
```

In connected mode, it must define these capabilities in `db/access.php` and `DOC_05`:

```text
CAP_MANAGE_KONNAXION = local/uckk:managekonnaxion
CAP_MAP_KONNAXION_OBJECTS = local/uckk:mapkonnaxionobjects
CAP_VIEW_KONNAXION_LOGS = local/uckk:viewkonnaxionlogs
```

In connected mode, it must expose these external services only with parameter validation, return validation, sesskey/token rules, context checks, capability checks, and privacy notes:

```text
SERVICE_CREATE_KONNAXION_MAPPING = local_uckk_create_konnaxion_mapping
SERVICE_GET_KONNAXION_MAPPING = local_uckk_get_konnaxion_mapping
SERVICE_SYNC_KONNAXION = local_uckk_sync_konnaxion
```

In connected mode, it must emit these events without leaking secrets or unnecessary personal data in event descriptions:

```text
EVENT_KONNAXION_MAPPING_CREATED = local_uckk\event\konnaxion_mapping_created
EVENT_KONNAXION_SYNC_COMPLETED = local_uckk\event\konnaxion_sync_completed
```

In connected mode, the Konnaxion bridge must support these sync states:

```text
KONNAXION_SYNC_STATUS_ENUM = queued, running, succeeded, failed, retry_waiting, skipped, disabled
KONNAXION_MAPPING_STATUS_ENUM = draft, active, suspended, superseded, archived, invalidated
```

The bridge must never write directly into `mod_uckkassembly`, `mod_uckkarchive`, `tool_uckkintegrity`, or `report_uckk` source tables. It must expose stable services that those plugins call or observe. When disabled, its tasks, UI, services, and reports must fail closed without breaking standalone-core workflows.

### Required tests

```text
PHPUnit: registry, dependencies, core external services, observer behavior, privacy provider, and connected-mode Konnaxion mapping CRUD plus Konnaxion sync timeout/failure/retry/idempotency when enabled.
Behat: profile/pathway screens, navigation, role-specific access, and connected-mode Konnaxion configuration visibility by capability when enabled.
```

## 4. `block_uckk_dashboard`

### Purpose

Display the Joueur and staff cockpit by aggregating state from source plugins. The block must display, not own, institutional workflow state.

### Must deliver

```text
blocks/uckk_dashboard/version.php
blocks/uckk_dashboard/block_uckk_dashboard.php
blocks/uckk_dashboard/edit_form.php
blocks/uckk_dashboard/db/access.php
blocks/uckk_dashboard/db/upgrade.php
blocks/uckk_dashboard/classes/output/
blocks/uckk_dashboard/classes/local/
blocks/uckk_dashboard/classes/privacy/provider.php
blocks/uckk_dashboard/templates/
blocks/uckk_dashboard/amd/src/
blocks/uckk_dashboard/lang/en/block_uckk_dashboard.php
blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
blocks/uckk_dashboard/tests/
blocks/uckk_dashboard/tests/behat/
blocks/uckk_dashboard/README.md
```

### Dashboard cards

```text
My pathway
Tronc commun
My competencies
My badges
My challenges
My assemblies
My Smart Vote readings, when connected mode is enabled
My archive
My integrity feedback
My deadlines
My portfolio
```

### Role-specific variants

| Viewer       | Dashboard emphasis                                               |
| ------------ | ---------------------------------------------------------------- |
| Joueur       | Progress, evidence, tasks, permitted Smart Vote readings         |
| Mentor       | Submissions, evaluation, cohorts, Assembly participation signals |
| Archiviste   | Items awaiting validation, Smart Vote snapshots awaiting archive when connected mode is enabled |
| Inquisiteur  | Open integrity cases, contested readings where such readings exist |
| Gestionnaire | Campus reports, configuration, and Konnaxion sync health when connected mode is enabled |

### Must not do

* Must not create source records.
* Must not mutate challenge, assembly, archive, or integrity state.
* Must not expose restricted archive, Smart Vote, Konnaxion, or integrity data without capability checks.
* Must not compute grades.
* Must not compute Smart Vote readings or mutate Smart Vote snapshots.

### Required tests

```text
PHPUnit: card data providers, capability filtering, privacy provider, and connected-mode Smart Vote card filtering when enabled.
Behat: dashboard visible cards by role, hidden restricted cards by role, block config, and connected-mode Smart Vote card visibility when enabled.
JS: refresh/dashboard modules build and degrade safely.
```

## 5. `mod_uckkchallenge`

### Purpose

Dedicated Moodle activity module for Défis King Klown.

### Must deliver

```text
mod/uckkchallenge/version.php
mod/uckkchallenge/lib.php
mod/uckkchallenge/locallib.php
mod/uckkchallenge/mod_form.php
mod/uckkchallenge/view.php
mod/uckkchallenge/submit.php
mod/uckkchallenge/archive.php
mod/uckkchallenge/integrity.php
mod/uckkchallenge/db/install.xml
mod/uckkchallenge/db/upgrade.php
mod/uckkchallenge/db/access.php
mod/uckkchallenge/db/events.php
mod/uckkchallenge/db/services.php
mod/uckkchallenge/classes/external/
mod/uckkchallenge/classes/form/
mod/uckkchallenge/classes/event/
mod/uckkchallenge/classes/local/
mod/uckkchallenge/classes/output/
mod/uckkchallenge/classes/privacy/provider.php
mod/uckkchallenge/classes/task/
mod/uckkchallenge/templates/
mod/uckkchallenge/amd/src/
mod/uckkchallenge/lang/en/uckkchallenge.php
mod/uckkchallenge/lang/fr/uckkchallenge.php
mod/uckkchallenge/tests/
mod/uckkchallenge/tests/behat/
mod/uckkchallenge/README.md
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

* Challenge statement.
* Rules.
* Corridors of action.
* Evidence requirements.
* Individual or team submissions.
* Mentor evaluation.
* Integrity review.
* Archive export.
* Badge and competency links.
* Optional Assembly reference where a challenge requires deliberation.
* Optional read-only display of Assembly state; Smart Vote state only when connected mode is enabled and authorized.

### Required classes

```text
mod_uckkchallenge\external\*
mod_uckkchallenge\form\submission_form
mod_uckkchallenge\form\review_form
mod_uckkchallenge\event\challenge_created
mod_uckkchallenge\event\submission_created
mod_uckkchallenge\event\submission_reviewed
mod_uckkchallenge\event\challenge_archived
mod_uckkchallenge\local\state_machine
mod_uckkchallenge\local\archive_exporter
mod_uckkchallenge\local\integrity_bridge
mod_uckkchallenge\output\*
mod_uckkchallenge\privacy\provider
```

### Must not do

* Must not own Assembly decisions.
* Must not own Smart Vote snapshots.
* Must not own Konnaxion mappings.
* Must not award badges directly without competency, evidence, integrity, and archive rules.
* Must not bypass archive or integrity workflow.
* Must not treat a Smart Vote reading as validation of challenge evidence.

### Required tests

```text
PHPUnit: state machine, submission lifecycle, review, archive handoff, integrity handoff, privacy provider.
Behat: create challenge, submit evidence, review, request revision, validate, archive, contest.
```

## 6. `mod_uckkassembly`

### Purpose

Dedicated Moodle activity module for Assemblées. In standalone-core mode it owns motions, deliberation, ordinary votes/readings, decisions, minutes, contestations, and archive linkage. In connected mode it also owns the Moodle-side Smart Vote workflow, vote-target mapping, result snapshots, review, contestation, and decision linkage.

### Must deliver

```text
mod/uckkassembly/version.php
mod/uckkassembly/lib.php
mod/uckkassembly/locallib.php
mod/uckkassembly/mod_form.php
mod/uckkassembly/view.php
mod/uckkassembly/propose.php
mod/uckkassembly/contest.php
mod/uckkassembly/decision.php
mod/uckkassembly/minutes.php
mod/uckkassembly/vote.php
mod/uckkassembly/db/install.xml
mod/uckkassembly/db/upgrade.php
mod/uckkassembly/db/access.php
mod/uckkassembly/db/events.php
mod/uckkassembly/db/services.php
mod/uckkassembly/classes/external/
mod/uckkassembly/classes/form/
mod/uckkassembly/classes/event/
mod/uckkassembly/classes/local/
mod/uckkassembly/classes/output/
mod/uckkassembly/classes/privacy/provider.php
mod/uckkassembly/classes/task/
mod/uckkassembly/templates/
mod/uckkassembly/amd/src/
mod/uckkassembly/lang/en/uckkassembly.php
mod/uckkassembly/lang/fr/uckkassembly.php
mod/uckkassembly/tests/
mod/uckkassembly/tests/behat/
mod/uckkassembly/README.md
```

### Optional connected-mode deliverables

Required only when Konnaxion-connected mode is enabled:

```text
mod/uckkassembly/smartvote.php
mod/uckkassembly/smartvote_review.php
mod/uckkassembly/classes/external/request_smart_vote_reading.php
mod/uckkassembly/classes/external/import_smart_vote_snapshot.php
mod/uckkassembly/classes/external/contest_smart_vote_snapshot.php
mod/uckkassembly/classes/form/smart_vote_request_form.php
mod/uckkassembly/classes/form/smart_vote_contestation_form.php
mod/uckkassembly/classes/local/smart_vote_service.php
mod/uckkassembly/classes/local/smart_vote_repository.php
mod/uckkassembly/classes/local/smart_vote_snapshot_repository.php
mod/uckkassembly/classes/local/smart_vote_result_audit_repository.php
```

These files must not be required to create ordinary Assemblies, record ordinary votes/readings, publish human decisions, write minutes, contest decisions, or archive Assembly records in standalone-core mode.

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

* Motions.
* Structured arguments.
* Objections.
* Amendments.
* Ordinary votes/readings.
* Decision publication.
* Minority report.
* Contestation.
* Minutes.
* Archive export.

### Optional connected-mode features

* Konnaxion Smart Vote reading request.
* Smart Vote target mapping.
* Smart Vote snapshot import.
* Smart Vote review and contestation.

### Smart Vote connected-mode state contract

When connected mode is enabled, `mod_uckkassembly` owns these Smart Vote status variables:

```text
SMART_VOTE_TARGET_STATUS_ENUM = draft, mapped, active, closed, archived, contested
SMART_VOTE_SNAPSHOT_STATUS_ENUM = imported, under_review, accepted_as_reading, contested, superseded, archived, invalidated
SMART_VOTE_AUDIT_STATUS_ENUM = recorded, reviewed, corrected, contested, resolved
```

A Smart Vote snapshot is never a final Assembly decision. The snapshot must preserve:

```text
SV_RAW_DATA
SV_READING_METHOD
SV_COMPUTED_READING
SV_EXPERTISE_WEIGHT when present
SV_HUMAN_DECISION linkage when a decision exists
SV_MINORITY_REPORT linkage when a minority report exists
SV_INTEGRITY_WARNING when present
SV_CONTESTATION_STATUS
SV_ARCHIVE_ITEM_ID when archived
```

### Optional connected-mode capabilities

Required only when connected mode is enabled:

```text
CAP_REQUEST_SMART_VOTE = mod/uckkassembly:requestsmartvote
CAP_VIEW_SMART_VOTE = mod/uckkassembly:viewsmartvote
CAP_REVIEW_SMART_VOTE = mod/uckkassembly:reviewsmartvote
CAP_CONTEST_SMART_VOTE = mod/uckkassembly:contestsmartvote
```

### Optional connected-mode services

Required only when connected mode is enabled:

```text
SERVICE_REQUEST_SMART_VOTE_READING = mod_uckkassembly_request_smart_vote_reading
SERVICE_IMPORT_SMART_VOTE_SNAPSHOT = mod_uckkassembly_import_smart_vote_snapshot
SERVICE_CONTEST_SMART_VOTE_SNAPSHOT = mod_uckkassembly_contest_smart_vote_snapshot
```

### Optional connected-mode events

Required only when connected mode is enabled:

```text
EVENT_SMART_VOTE_READING_REQUESTED = mod_uckkassembly\event\smart_vote_reading_requested
EVENT_SMART_VOTE_SNAPSHOT_IMPORTED = mod_uckkassembly\event\smart_vote_snapshot_imported
EVENT_SMART_VOTE_SNAPSHOT_CONTESTED = mod_uckkassembly\event\smart_vote_snapshot_contested
EVENT_SMART_VOTE_SNAPSHOT_ARCHIVED = mod_uckkassembly\event\smart_vote_snapshot_archived
```

### Core required classes

```text
mod_uckkassembly\external\*
mod_uckkassembly\form\motion_form
mod_uckkassembly\form\argument_form
mod_uckkassembly\form\vote_form
mod_uckkassembly\event\motion_created
mod_uckkassembly\event\vote_cast
mod_uckkassembly\event\decision_published
mod_uckkassembly\event\minutes_archived
mod_uckkassembly\local\state_machine
mod_uckkassembly\local\quorum_calculator
mod_uckkassembly\local\archive_exporter
mod_uckkassembly\output\*
mod_uckkassembly\privacy\provider
```

### Optional connected-mode classes

Required only when connected mode is enabled:

```text
mod_uckkassembly\external\request_smart_vote_reading
mod_uckkassembly\external\import_smart_vote_snapshot
mod_uckkassembly\external\contest_smart_vote_snapshot
mod_uckkassembly\form\smart_vote_request_form
mod_uckkassembly\form\smart_vote_contestation_form
mod_uckkassembly\event\smart_vote_reading_requested
mod_uckkassembly\event\smart_vote_snapshot_imported
mod_uckkassembly\event\smart_vote_snapshot_contested
mod_uckkassembly\event\smart_vote_snapshot_archived
mod_uckkassembly\local\smart_vote_service
mod_uckkassembly\local\smart_vote_repository
mod_uckkassembly\local\smart_vote_snapshot_repository
mod_uckkassembly\local\smart_vote_result_audit_repository
```

### Must not do

* Must not override site-wide governance roles.
* Must not hide minority reports when policy requires visibility.
* Must not publish final decisions without capability and state checks.
* Must not let AI become an assembly decision-maker.
* Must not let Smart Vote become an Assembly decision-maker.
* Must not allow Konnaxion to write directly into Assembly source tables.
* Must not treat Konnaxion `VoteResult` as a final decision.
* Must not overwrite minority reports, human decisions, or contestation records with computed readings.
* Must not import a Smart Vote snapshot without raw data, reading method, computed reading, source version or timestamp when available, provenance hash where required, review state, and contestation state.

### Required tests

```text
PHPUnit: motion lifecycle, ordinary voting/readings, quorum, decision publication, privacy provider, archive handoff, and connected-mode Smart Vote target mapping, reading request, snapshot import, review, and contestation when enabled.
Behat: create assembly, propose motion, amend, vote, publish human decision, archive minutes, and connected-mode Smart Vote request/review/contestation scenarios when enabled.
```

## 7. `mod_uckkarchive`

### Purpose

Dedicated Moodle activity module for Archives, evidence preservation, provenance, versioning, and institutional memory.

### Must deliver

```text
mod/uckkarchive/version.php
mod/uckkarchive/lib.php
mod/uckkarchive/locallib.php
mod/uckkarchive/mod_form.php
mod/uckkarchive/view.php
mod/uckkarchive/item.php
mod/uckkarchive/versioning.php
mod/uckkarchive/db/install.xml
mod/uckkarchive/db/upgrade.php
mod/uckkarchive/db/access.php
mod/uckkarchive/db/events.php
mod/uckkarchive/db/services.php
mod/uckkarchive/classes/external/
mod/uckkarchive/classes/form/
mod/uckkarchive/classes/event/
mod/uckkarchive/classes/local/
mod/uckkarchive/classes/output/
mod/uckkarchive/classes/privacy/provider.php
mod/uckkarchive/classes/task/
mod/uckkarchive/templates/
mod/uckkarchive/amd/src/
mod/uckkarchive/lang/en/uckkarchive.php
mod/uckkarchive/lang/fr/uckkarchive.php
mod/uckkarchive/tests/
mod/uckkarchive/tests/behat/
mod/uckkarchive/README.md
```

### Optional connected-mode deliverables

Required only when connected mode is enabled:

```text
mod/uckkarchive/smartvote.php
mod/uckkarchive/classes/event/smart_vote_snapshot_archived.php
mod/uckkarchive/classes/local/smart_vote_archive_service.php
```

### Core features

* Archive item creation.
* Archive item validation.
* Smart Vote snapshot preservation when connected mode is enabled.
* Versioning.
* Provenance chain.
* Visibility rules.
* Evidence relations.
* Assembly minutes preservation.
* Challenge evidence preservation.
* Integrity review references.
* Export package generation.

### Core required classes

```text
mod_uckkarchive\external\*
mod_uckkarchive\form\archive_item_form
mod_uckkarchive\form\archive_validation_form
mod_uckkarchive\event\archive_item_created
mod_uckkarchive\event\archive_item_validated
mod_uckkarchive\event\archive_item_version_created
mod_uckkarchive\local\archive_repository
mod_uckkarchive\local\version_repository
mod_uckkarchive\local\provenance_service
mod_uckkarchive\local\exporter
mod_uckkarchive\output\*
mod_uckkarchive\privacy\provider
```

### Optional connected-mode classes

Required only when connected mode is enabled:

```text
mod_uckkarchive\event\smart_vote_snapshot_archived
mod_uckkarchive\local\smart_vote_archive_service
```

### Optional connected-mode capabilities

Required only when connected mode is enabled:

```text
CAP_ARCHIVE_SMART_VOTE = mod/uckkarchive:archivesmartvote
```

### Must not do

* Must not fabricate provenance.
* Must not convert Smart Vote readings into final Assembly decisions.
* Must not archive Konnaxion-derived data without source, method, snapshot, visibility, and contestation metadata.
* Must not expose restricted evidence without capability checks.
* Must not delete institutional memory without retention and redaction rules.
* Must not silently change archived content after validation.

### Required tests

```text
PHPUnit: archive item lifecycle, versioning, provenance, file areas, privacy provider, and connected-mode Smart Vote snapshot archival when enabled.
Behat: create archive item, validate, version, preserve Assembly minutes, export package, and connected-mode Smart Vote snapshot archival when enabled.
```

## 8. `tool_uckkseed`

### Purpose

Install and update the UCKK campus structure through repeatable, idempotent seed operations.

### Must deliver

```text
admin/tool/uckkseed/version.php
admin/tool/uckkseed/index.php
admin/tool/uckkseed/cli/seed.php
admin/tool/uckkseed/cli/dryrun.php
admin/tool/uckkseed/settings.php
admin/tool/uckkseed/db/install.xml
admin/tool/uckkseed/db/upgrade.php
admin/tool/uckkseed/db/access.php
admin/tool/uckkseed/db/tasks.php
admin/tool/uckkseed/classes/form/
admin/tool/uckkseed/classes/local/
admin/tool/uckkseed/classes/output/
admin/tool/uckkseed/classes/privacy/provider.php
admin/tool/uckkseed/classes/task/
admin/tool/uckkseed/lang/en/tool_uckkseed.php
admin/tool/uckkseed/lang/fr/tool_uckkseed.php
admin/tool/uckkseed/tests/
admin/tool/uckkseed/tests/behat/
admin/tool/uckkseed/README.md
presets/categories.json
presets/courses.json
presets/cohorts.json
presets/roles.json
presets/capabilities.json
presets/competencies.json
presets/badges.json
presets/reports.json
presets/navigation.json
presets/state_machines.json
presets/events.json
presets/privacy_retention.json
presets/exports.json
```

### Optional connected-mode presets

Required only when connected mode is enabled:

```text
presets/konnaxion_mappings.json
```

### Seed objects

* Categories.
* Courses.
* Cohorts.
* Roles.
* Capabilities.
* Competency frameworks.
* Badges.
* Reports.
* Navigation.
* State machines.
* Event registry.
* Konnaxion mapping defaults when connected mode is enabled.
* Privacy/retention defaults.
* Export definitions.
* Default dashboard configuration.
* Standard archive templates.

### Must not do

* Must not silently overwrite hand-edited records.
* Must not create duplicate records on rerun.
* Must not create active Konnaxion mappings without explicit admin configuration.
* Must not seed Smart Vote capabilities without matching role, test, and privacy registry entries.
* Must not grant unrestricted admin-like permissions to symbolic roles.
* Must not seed incomplete stubs.

### Required tests

```text
PHPUnit: dry-run, idempotency, dependency validation, core preset JSON validation, rollback log, and connected-mode Konnaxion preset validation when enabled.
Behat: admin seed workflow, rerun idempotency, seeded campus visible, restricted seed access, and connected-mode Konnaxion preset validation when enabled.
CLI: seed.php and dryrun.php execute without interactive assumptions.
```

## 9. `tool_uckkintegrity`

### Purpose

Implement the Inquisiteur workflow for integrity reviews, contested evidence, contested readings, contested decisions, AI uncertainty flags, and procedural corrections.

### Must deliver

```text
admin/tool/uckkintegrity/version.php
admin/tool/uckkintegrity/index.php
admin/tool/uckkintegrity/case.php
admin/tool/uckkintegrity/review.php
admin/tool/uckkintegrity/settings.php
admin/tool/uckkintegrity/db/install.xml
admin/tool/uckkintegrity/db/upgrade.php
admin/tool/uckkintegrity/db/access.php
admin/tool/uckkintegrity/db/events.php
admin/tool/uckkintegrity/db/services.php
admin/tool/uckkintegrity/db/tasks.php
admin/tool/uckkintegrity/classes/external/
admin/tool/uckkintegrity/classes/form/
admin/tool/uckkintegrity/classes/event/
admin/tool/uckkintegrity/classes/local/
admin/tool/uckkintegrity/classes/output/
admin/tool/uckkintegrity/classes/privacy/provider.php
admin/tool/uckkintegrity/classes/task/
admin/tool/uckkintegrity/templates/
admin/tool/uckkintegrity/amd/src/
admin/tool/uckkintegrity/lang/en/tool_uckkintegrity.php
admin/tool/uckkintegrity/lang/fr/tool_uckkintegrity.php
admin/tool/uckkintegrity/tests/
admin/tool/uckkintegrity/tests/behat/
admin/tool/uckkintegrity/README.md
```

### Case types

* Evidence integrity.
* Challenge evaluation dispute.
* Assembly procedure issue.
* Smart Vote integrity warning when connected mode is enabled.
* Contested Konnaxion-derived reading when connected mode is enabled.
* Archive provenance issue.
* AI uncertainty flag.
* Procedural correction.

### Core required classes

```text
tool_uckkintegrity\external\*
tool_uckkintegrity\form\case_form
tool_uckkintegrity\form\review_form
tool_uckkintegrity\event\case_opened
tool_uckkintegrity\event\case_reviewed
tool_uckkintegrity\event\case_closed
tool_uckkintegrity\local\case_repository
tool_uckkintegrity\local\review_service
tool_uckkintegrity\output\*
tool_uckkintegrity\privacy\provider
```

### Optional connected-mode classes

Required only when connected mode is enabled:

```text
tool_uckkintegrity\local\smart_vote_integrity_service
```

### Must not do

* Must not act as a hidden administrator.
* Must not convert a Smart Vote integrity warning into an automatic sanction.
* Must not bypass Assembly contestation when reviewing Konnaxion-derived readings.
* Must not delete or rewrite source evidence without archive/redaction rules.
* Must not hide integrity actions from audit trails.
* Must not resolve disputes without documented outcome and capability checks.

### Required tests

```text
PHPUnit: case lifecycle, restricted access, privacy provider, and connected-mode Smart Vote warning/contested-reading review when enabled.
Behat: open case, assign reviewer, review contested evidence, close with outcome, deny unauthorized access, and connected-mode contested Smart Vote reading review when enabled.
```

## 10. `report_uckk`

### Purpose

Provide institutional reporting over UCKK records without becoming the owner of source workflow data.

### Must deliver

```text
report/uckk/version.php
report/uckk/index.php
report/uckk/settings.php
report/uckk/db/access.php
report/uckk/db/services.php
report/uckk/db/upgrade.php
report/uckk/classes/local/
report/uckk/classes/output/
report/uckk/classes/reportbuilder/
report/uckk/classes/privacy/provider.php
report/uckk/templates/
report/uckk/amd/src/
report/uckk/lang/en/report_uckk.php
report/uckk/lang/fr/report_uckk.php
report/uckk/tests/
report/uckk/tests/behat/
report/uckk/README.md
```

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
smart_vote_readings, when connected mode is enabled
smart_vote_contestations, when connected mode is enabled
konnaxion_sync_status, when connected mode is enabled
integrity_cases
ai_usage
privacy_exports
```

### Core required classes

```text
report_uckk\local\report_repository
report_uckk\local\access_filter
report_uckk\output\*
report_uckk\reportbuilder\datasource\*
report_uckk\privacy\provider
```

### Optional connected-mode classes

Required only when connected mode is enabled:

```text
report_uckk\local\smart_vote_report_repository
report_uckk\external\get_smart_vote_report
```

### Optional connected-mode capability

Required only when connected mode is enabled:

```text
CAP_VIEW_SMART_VOTE_REPORTS = report/uckk:viewsmartvotereports
```

### Optional connected-mode service

Required only when connected mode is enabled:

```text
SERVICE_GET_SMART_VOTE_REPORT = report_uckk_get_smart_vote_report
```

### Must not do

* Must not bypass source plugin capability checks.
* Must not expose personally identifiable or integrity-sensitive data through aggregate reports.
* Must not mutate source records.
* Must not cache restricted data without retention and invalidation rules.
* Must not report Smart Vote readings as final decisions.
* Must not expose Konnaxion external identifiers unless the viewer has the required capability and the report requires them.
* Must not export Smart Vote data without provenance, reading method, contestation state, privacy filtering, and archive linkage where applicable.

### Required tests

```text
PHPUnit: report data sources, access filtering, privacy provider, and connected-mode Smart Vote report service plus Konnaxion identifier redaction when enabled.
Behat: report visibility by role, restricted report denial, export behavior, and connected-mode Smart Vote report visibility when enabled.
```

## 11. `aiprovider_uckk`

### Purpose

Bridge UCKK-Moodle to governed AI services while preserving human authority, privacy controls, logging configuration, redaction, and workflow boundaries.

### Must deliver

```text
ai/provider/uckk/version.php
ai/provider/uckk/settings.php
ai/provider/uckk/db/install.xml
ai/provider/uckk/db/upgrade.php
ai/provider/uckk/db/access.php
ai/provider/uckk/classes/local/
ai/provider/uckk/classes/provider/
ai/provider/uckk/classes/privacy/provider.php
ai/provider/uckk/classes/task/
ai/provider/uckk/lang/en/aiprovider_uckk.php
ai/provider/uckk/lang/fr/aiprovider_uckk.php
ai/provider/uckk/tests/
ai/provider/uckk/tests/behat/
ai/provider/uckk/README.md
```

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

* Every AI output is labeled as non-authoritative.
* Prompts and responses can be logged according to site settings.
* Sensitive workflows can disable AI.
* AI cannot grade, sanction, validate integrity, publish final decisions, award badges, validate archive records, resolve disputes, compute Smart Vote readings, or reinterpret Konnaxion results as decisions.
* Logged prompt/response records must be exportable and deletable according to Moodle privacy rules where they contain personal data.

### Required classes

```text
aiprovider_uckk\local\client
aiprovider_uckk\local\redactor
aiprovider_uckk\local\logger
aiprovider_uckk\local\policy
aiprovider_uckk\provider\*
aiprovider_uckk\task\cleanup_logs
aiprovider_uckk\privacy\provider
```

### Required tests

```text
PHPUnit: provider enable/disable, redaction, logging policy, action authorization, privacy provider.
Behat: governed AI labels, disabled sensitive workflow, restricted log access.
```

## 12. Cross-plugin dependency contract

The canonical dependency direction is:

```text
local_uckk
  -> shared registry and component map in standalone-core mode
  -> optional Konnaxion bridge, Konnaxion mapping services, and sync logs in connected mode

theme_uckk
  -> presentation only, may depend on local_uckk

format_uckk
  -> course structure, may reference local_uckk and mod_uckkarchive

mod_uckkchallenge
  -> challenge workflow, may call local_uckk, mod_uckkarchive, tool_uckkintegrity

mod_uckkassembly
  -> assembly workflow in standalone-core mode; optional Smart Vote workflow in connected mode; may call local_uckk Konnaxion services only when enabled, plus mod_uckkarchive and tool_uckkintegrity

mod_uckkarchive
  -> archive source of truth; preserves ordinary provenance packages in standalone mode and Smart Vote snapshots only in connected mode; may call local_uckk

tool_uckkintegrity
  -> integrity workflow, may call local_uckk, archive, challenge, assembly

tool_uckkseed
  -> installation/seeding orchestrator, may call all UCKK plugins through stable APIs

block_uckk_dashboard
  -> display aggregator, may read from source plugins after capability checks

report_uckk
  -> report aggregator; may read Smart Vote and Konnaxion-derived Moodle-side snapshots from source plugins after capability checks only in connected mode

aiprovider_uckk
  -> governed AI bridge, may call local_uckk for policy/context but cannot own workflow authority
```

No plugin may create circular ownership of source records. Reading across plugins must go through stable helper/service APIs, not direct assumptions about another plugin's internal tables, unless explicitly documented and tested.

When connected mode is enabled, Konnaxion integration must follow this direction:

```text
Konnaxion external source family
  -> local_uckk Konnaxion bridge
  -> mod_uckkassembly Smart Vote workflow
  -> mod_uckkarchive provenance/archive preservation
  -> report_uckk reports and exports
```

Konnaxion never writes directly into Moodle source tables. Konnaxion identifiers never grant Moodle authority. Smart Vote readings never publish final Assembly decisions.

## 13. Implementation correction gates

Before installing in Moodle, the codebase must pass these preflight gates:

```text
[ ] No PHP code appears in amd/src/*.js.
[ ] No JavaScript-only module appears in a .php controller file.
[ ] No Markdown fences appear in .php files.
[ ] Every .php file parses with php -l.
[ ] Every version.php component matches its plugin path.
[ ] Every declared service in db/services.php has a matching classes/external class.
[ ] Every event referenced or emitted has a matching classes/event class.
[ ] Every observer callback points to an existing class or function.
[ ] Every scheduled task in db/tasks.php has a matching classes/task class.
[ ] Every form reference has a matching classes/form class or Moodle form file.
[ ] Every plugin storing personal data has classes/privacy/provider.php.
[ ] Every install.xml validates as XMLDB.
[ ] Every JSON preset validates as JSON.
[ ] Every canonical variable from section 0.7 is resolved consistently across declarations, services, events, capabilities, tests, and presets.
[ ] In connected mode, every Konnaxion table, capability, service, event, and status enum uses the canonical variable name and value.
[ ] In connected mode, every Smart Vote workflow keeps computed readings separate from human/institutional decisions.
[ ] In standalone-core mode, ordinary Assembly, Archive, Integrity, Report, and Seed workflows pass without Konnaxion credentials, endpoints, mappings, or Smart Vote snapshots.
[ ] Every AMD module builds through Moodle's Grunt pipeline.
```

After preflight, the codebase must pass these Moodle gates:

```text
[ ] Installs into a clean disposable Moodle site.
[ ] admin/cli/upgrade.php --non-interactive passes.
[ ] Caches purge without warnings.
[ ] PHPUnit initializes and plugin tests pass.
[ ] Behat initializes and major workflow features pass.
[ ] Privacy API checks pass for every plugin storing or exposing personal data.
[ ] Backup/restore checks pass for activity modules.
[ ] Seed dry-run passes.
[ ] Seed run creates a complete campus.
[ ] Seed rerun is idempotent.
[ ] Standalone-core workflows pass with Konnaxion disabled.
[ ] In connected mode, Konnaxion timeout, failure, retry, disabled-state, and idempotency tests pass.
[ ] In connected mode, Smart Vote request, import, review, contestation, archive, report, privacy export, and redaction tests pass.
```

## 14. Plugin completion checklist

Every plugin must pass:

```text
[ ] Installs cleanly.
[ ] Upgrades cleanly.
[ ] Has a correct version.php component declaration.
[ ] Has dependency declarations where needed.
[ ] Has component strings in English and French.
[ ] Has required capabilities in db/access.php.
[ ] Uses canonical capability variables from section 0.7 when touching Konnaxion or Smart Vote in connected mode.
[ ] Uses Moodle context checks on every page and service.
[ ] Emits events for important actions.
[ ] Uses canonical event variables from section 0.7 when touching Konnaxion or Smart Vote in connected mode.
[ ] Has matching classes for every service, form, event, observer, task, output object, and privacy provider it declares.
[ ] Has privacy provider when storing or exposing personal data.
[ ] In connected mode, covers Konnaxion mappings, external identifiers, Smart Vote snapshots, expertise weights, contestations, and reports when stored or exposed.
[ ] Has PHPUnit tests for services and business rules.
[ ] Has Behat tests for major user workflows.
[ ] Has AMD JavaScript that builds cleanly where browser behavior exists.
[ ] Has install.xml and upgrade.php coverage for all owned tables.
[ ] Uses canonical table variables from section 0.7 for Konnaxion and Smart Vote storage when connected-mode storage is enabled.
[ ] Has backup/restore coverage when it is an activity module.
[ ] Has README with purpose, install path, dependencies, admin settings, capabilities, data ownership, and test command.
```

## 15. Standalone and connected-mode completion gates

### 15.1 Core standalone completion gate

The plugin suite is complete in standalone-core mode only when:

```text
[ ] All core plugins install cleanly with Konnaxion disabled.
[ ] Core pages, services, events, tasks, forms, outputs, privacy providers, and tests resolve without Konnaxion credentials or endpoints.
[ ] Assemblies support motions, ordinary votes/readings, decisions, minutes, contestations, and archive export without Smart Vote.
[ ] Archives preserve Assembly decisions, challenge evidence, provenance, versions, and integrity records without Smart Vote snapshots.
[ ] Reports render institutional UCKK records without Smart Vote or Konnaxion data.
[ ] Seed creates the UCKK campus without active Konnaxion mappings.
[ ] Dashboard, course format, challenge, archive, integrity, report, and AI workflows fail closed or hide Konnaxion/Smart Vote panels when connected mode is disabled.
```

### 15.2 Optional Konnaxion-connected completion gate

The connected profile is complete only when:

```text
[ ] Konnaxion bridge settings are disabled by default and can be enabled by authorized administrators.
[ ] Konnaxion mappings, sync logs, services, events, tasks, privacy providers, and tests use the canonical variables in section 0.7.
[ ] Smart Vote readings can be requested, imported, reviewed, contested, archived, reported, exported, and redacted with Moodle capability checks.
[ ] Smart Vote readings remain separate from human/institutional Assembly decisions.
[ ] Konnaxion failures, timeouts, retries, disabled state, and idempotency are tested.
[ ] Connected-mode features never become hard dependencies for the standalone-core gate.
```

## 16. Non-negotiable authority boundaries

```text
Theme presents; it does not decide.
Course format structures; it does not own workflow records.
Dashboard displays; it does not mutate source records.
Reports aggregate; they do not become source of truth.
Konnaxion computes Smart Vote readings; it does not own Moodle decisions.
Smart Vote informs Assemblées; it does not decide.
Seed creates and updates declared objects; it does not silently overwrite hand-edited records.
Archive preserves provenance; it does not invent legitimacy.
Assembly decides through Moodle-side human/institutional workflow; it does not surrender authority to external readings.
Integrity reviews; it does not become unrestricted administration.
AI assists; it never becomes final authority.
```


---

## Update note

This version aligns `DOC_03` with the shared UCKK-Moodle operating-mode rule: standalone-core mode is complete without Konnaxion, while Konnaxion/Smart Vote features are optional connected-mode obligations.
