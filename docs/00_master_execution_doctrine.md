# 00 — UCKK-Moodle Master Execution Doctrine

**Status:** Final implementation doctrine with standalone core and optional Konnaxion-connected alignment
**Target:** Moodle 5.1 adaptation for the Univers-Cité King Klown (UCKK)
**Workflow rule:** Complete final version. No stubs. No multi-phase placeholders. No "later" features inside the target scope.
**Correction rule:** Generated code is not acceptable until it passes the first-pass implementation gates defined in this doctrine. Konnaxion-connected gates apply only when the connected-mode profile is enabled.

## 0. Canonical alignment variables

These variables are binding across the documentation set, presets, generated code, tests, and release checks. A later file may add detail, but it must not rename, invert, or silently redefine these variables.

### 0.1 Document variables

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
| `DOC_10`                      | `10_konnaxion_smart_vote_integration_contract.md` | Optional Konnaxion-connected Smart Vote contract and acceptance gate.                   |
| `LEGACY_DOC_10`               | `10_implementation_correction_plan.md`            | Deprecated. Must not be used as the active product-tree target.                         |

### 0.2 Source-family variables

| Variable                    | Canonical value                    | Rule                                                                          |
| --------------------------- | ---------------------------------- | ----------------------------------------------------------------------------- |
| `SOURCE_FAMILY_UCKK_CANON`  | `UCKK canon`                       | Governs UCKK meaning, pedagogy, governance, and symbolic boundaries.          |
| `SOURCE_FAMILY_KONNAXION`   | `Konnaxion external source family` | External source of truth for Konnaxion-side Smart Vote objects and semantics. |
| `SOURCE_FAMILY_MOODLE_DOCS` | `Moodle developer documentation`   | Governs Moodle 5.1 implementation mechanics.                                  |
| `EXTERNAL_SYSTEM_KONNAXION` | `Konnaxion`                        | Optional external system integrated through Moodle services only.             |
| `EXTERNAL_SIGNAL_EKOH`      | `EkoH`                             | External ecosystem context or signal source only where explicitly mapped.     |

### 0.3 Boundary variables

| Variable                    | Canonical value                                                                                                                            | Rule                                                                                          |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------- |
| `PRODUCT_SCOPE`             | `UCKK-Moodle is the Moodle campus implementation of UCKK.`                                                                                 | It is not the whole kOA movement, kOA Digital Ecosystem, or Konnaxion platform.               |
| `SMART_VOTE_CANONICAL_RULE` | `Konnaxion computes Smart Vote readings. UCKK-Moodle owns Assembly decisions. Archives preserve both, with provenance and contestability.` | Must be repeated consistently in docs that mention Smart Vote.                                |
| `SMART_VOTE_AUTHORITY`      | `computed_reading_only`                                                                                                                    | Smart Vote may inform; it must not decide, award, validate, sanction, or close contestations. |
| `ASSEMBLY_AUTHORITY`        | `human_institutional_decision`                                                                                                             | Final Assembly decisions belong to Moodle-side Assembly workflow and permissions.             |
| `ARCHIVE_AUTHORITY`         | `provenance_and_contestation_memory`                                                                                                       | Archives preserve source, snapshot, decision, minority report, and contestation trail.        |
| `DIRECT_WRITE_RULE`         | `external_systems_never_write_moodle_source_tables`                                                                                        | Konnaxion must not write directly into Moodle source tables.                                  |
| `PERMISSION_RULE`           | `moodle_capabilities_remain_authoritative`                                                                                                 | External roles, labels, or identifiers never grant Moodle authority by themselves.            |

### 0.4 Operating-mode variables

| Variable                              | Canonical value                                                                                                                                  | Rule                                                                                                          |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `OPERATING_MODE_STANDALONE`           | `standalone_core`                                                                                                                                | UCKK-Moodle installs, seeds, teaches, deliberates, archives, reports, and enforces permissions without Konnaxion. |
| `OPERATING_MODE_KONNAXION_CONNECTED`  | `connected_konnaxion`                                                                                                                            | Optional connected mode that adds Konnaxion bridge, EkoH/advisory signals, Smart Vote readings, and cross-module organization. |
| `KONNAXION_REQUIRED_FOR_CORE`         | `false`                                                                                                                                          | Konnaxion must not be a hard dependency for the standalone campus.                                             |
| `SMART_VOTE_REQUIRED_FOR_CORE`        | `false`                                                                                                                                          | Smart Vote panels, services, mappings, snapshots, and reports must not be required for standalone workflows.   |
| `KONNAXION_DEFAULT_STATE`             | `disabled`                                                                                                                                       | The Konnaxion bridge is disabled unless an administrator explicitly configures and enables connected mode.      |
| `SMART_VOTE_DEFAULT_STATE`            | `disabled_unless_konnaxion_connected`                                                                                                            | Smart Vote actions are hidden or disabled unless connected mode is enabled and the user has the required capability. |
| `CORE_RELEASE_GATE`                   | `UCKK-Moodle installs and works as a standalone campus without Konnaxion.`                                                                        | Required for every release.                                                                                   |
| `CONNECTED_KONNAXION_RELEASE_GATE`    | `Konnaxion bridge and Smart Vote reading workflow work when connected mode is enabled.`                                                           | Required only for releases that claim Konnaxion-connected support.                                            |

### 0.5 Plugin ownership variables

| Variable                     | Canonical owner      | Responsibilities                                                                                                     |
| ---------------------------- | -------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `KONNAXION_BRIDGE_OWNER`     | `local_uckk`         | Connected-mode configuration, authentication settings, endpoint client, object mapping, sync logs, and shared integration services. |
| `SMART_VOTE_WORKFLOW_OWNER`  | `mod_uckkassembly`   | Connected-mode Smart Vote reading requests, vote-target mapping, snapshots, review, contestation, and Assembly linkage. |
| `ASSEMBLY_DECISION_OWNER`    | `mod_uckkassembly`   | Motions, deliberation, decision publication, minority report, and decision contestability.                           |
| `SMART_VOTE_ARCHIVE_OWNER`   | `mod_uckkarchive`    | Archive records and file areas for Smart Vote snapshots, decisions, minutes, and provenance packages.                |
| `SMART_VOTE_REPORT_OWNER`    | `report_uckk`        | Connected-mode Smart Vote reports, institutional exports, filters, and privacy-aware visibility.                    |
| `SMART_VOTE_INTEGRITY_OWNER` | `tool_uckkintegrity` | Integrity warnings, contested readings, correction cases, and restricted review workflows.                           |
| `SMART_VOTE_SEED_OWNER`      | `tool_uckkseed`      | Idempotent connected-mode presets for capabilities, mappings, report definitions, and integration defaults.          |
| `SMART_VOTE_PRIVACY_OWNER`   | every storing plugin | Each plugin that stores personal data owns its privacy provider, export, delete, anonymisation, and retention tests. |

### 0.6 Konnaxion object variables

| Variable                               | Canonical value      | Moodle-side use                                                              |
| -------------------------------------- | -------------------- | ---------------------------------------------------------------------------- |
| `KONNAXION_OBJECT_VOTE`                | `Vote`               | External vote object mapped to a Moodle-side vote target or reading source.  |
| `KONNAXION_OBJECT_VOTE_MODALITY`       | `VoteModality`       | External voting modality/method mapped to a Moodle-side reading method.      |
| `KONNAXION_OBJECT_VOTE_RESULT`         | `VoteResult`         | External result mapped into a Moodle-side Smart Vote reading snapshot.       |
| `KONNAXION_OBJECT_INTEGRATION_MAPPING` | `IntegrationMapping` | External mapping object mirrored by Moodle-side mapping tables.              |
| `KONNAXION_EXTERNAL_ID_FIELD`          | `externalid`         | Stores the Konnaxion identifier; never stores secrets.                       |
| `KONNAXION_EXTERNAL_TYPE_FIELD`        | `externaltype`       | Stores the Konnaxion object type.                                            |
| `KONNAXION_SOURCE_VERSION_FIELD`       | `sourceversion`      | Stores the external source version, revision, or timestamp where available.  |
| `KONNAXION_SYNC_STATUS_FIELD`          | `syncstatus`         | Stores Moodle-side sync state using the enum below.                          |
| `KONNAXION_PROVENANCE_HASH_FIELD`      | `provenancehash`     | Stores integrity hash for imported snapshots or mapped records where needed. |

### 0.7 Moodle-side Konnaxion table variables

| Variable                        | Canonical table                | Owner              | Purpose                                                                                        |
| ------------------------------- | ------------------------------ | ------------------ | ---------------------------------------------------------------------------------------------- |
| `TABLE_KONNAXION_USER_MAP`      | `local_uckk_kx_user_map`       | `local_uckk`       | Maps Moodle users to Konnaxion identities without exposing unnecessary external personal data. |
| `TABLE_KONNAXION_OBJECT_MAP`    | `local_uckk_kx_object_map`     | `local_uckk`       | Maps Moodle objects to Konnaxion objects.                                                      |
| `TABLE_KONNAXION_SYNC_LOG`      | `local_uckk_kx_sync_log`       | `local_uckk`       | Logs sync attempts, failures, retries, endpoint responses, and idempotency keys.               |
| `TABLE_SMART_VOTE_TARGET_MAP`   | `uckkassembly_kx_vote_target`  | `mod_uckkassembly` | Maps Assembly motions, decisions, or deliberation objects to Konnaxion vote targets.           |
| `TABLE_SMART_VOTE_SNAPSHOT`     | `uckkassembly_sv_snapshot`     | `mod_uckkassembly` | Stores Moodle-side immutable Smart Vote reading snapshots.                                     |
| `TABLE_SMART_VOTE_RESULT_AUDIT` | `uckkassembly_sv_result_audit` | `mod_uckkassembly` | Stores review, correction, contestation, and supersession trail for Smart Vote results.        |

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`. These tables are connected-mode tables; they are not required to contain records, expose UI, or run sync tasks in standalone mode.

### 0.8 Smart Vote reading variables

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

### 0.9 Status and state variables

| Variable                          | Allowed values                                                                                          | Owner              |
| --------------------------------- | ------------------------------------------------------------------------------------------------------- | ------------------ |
| `KONNAXION_SYNC_STATUS_ENUM`      | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled`                      | `local_uckk`       |
| `KONNAXION_MAPPING_STATUS_ENUM`   | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated`                                 | `local_uckk`       |
| `SMART_VOTE_TARGET_STATUS_ENUM`   | `draft`, `mapped`, `active`, `closed`, `archived`, `contested`                                          | `mod_uckkassembly` |
| `SMART_VOTE_SNAPSHOT_STATUS_ENUM` | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` | `mod_uckkassembly` |
| `SMART_VOTE_AUDIT_STATUS_ENUM`    | `recorded`, `reviewed`, `corrected`, `contested`, `resolved`                                            | `mod_uckkassembly` |

These values must be copied into `DOC_04`, `DOC_07`, `DOC_09`, `DOC_10`, `presets/state_machines.json`, PHPUnit state-machine tests, Behat workflow tests, and privacy/export tests where applicable. Konnaxion and Smart Vote state machines are mandatory only for the connected-mode profile.

### 0.10 Capability variables

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

Every capability above must appear in `db/access.php`, `DOC_05`, `presets/capabilities.json`, PHPUnit access tests, and Behat role-visibility tests before the connected-mode Konnaxion pass is complete. Standalone core workflows must not require these capabilities.

### 0.11 Service and event variables

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

Every connected-mode service must have a `db/services.php` declaration, `classes/external/*` implementation, parameter validation, return validation, capability checks, privacy notes, PHPUnit coverage, and failure-path tests. Every connected-mode event must have a matching `classes/event/*` implementation and must avoid leaking private Smart Vote or Konnaxion data in descriptions. Standalone mode must remain functional when these services are disabled by configuration.

### 0.12 Preset registry variables

| Variable                    | Canonical preset                  | Required by                                                               |
| --------------------------- | --------------------------------- | ------------------------------------------------------------------------- |
| `PRESET_CAPABILITIES`       | `presets/capabilities.json`       | Roles, capabilities, tests, seed tool.                                    |
| `PRESET_STATE_MACHINES`     | `presets/state_machines.json`     | Workflow statuses and transitions.                                        |
| `PRESET_EVENTS`             | `presets/events.json`             | Event-class registry and tests.                                           |
| `PRESET_KONNAXION_MAPPINGS` | `presets/konnaxion_mappings.json` | Connected-mode Konnaxion object map defaults and validation fixtures.     |
| `PRESET_PRIVACY_RETENTION`  | `presets/privacy_retention.json`  | Privacy, export, deletion, anonymisation, redaction, and retention tests. |
| `PRESET_EXPORTS`            | `presets/exports.json`            | Report/export definitions and acceptance evidence.                        |

## 1. Purpose

This documentation set defines the coherent final adaptation of Moodle into the operational campus of UCKK.

The goal is not to decorate Moodle and not to fork Moodle unnecessarily. The goal is to deliver a complete, installable, governed Moodle distribution that expresses UCKK through Moodle-native extension points, complete plugins, seed data, roles, capabilities, activities, reports, archives, AI governance, optional Konnaxion-connected Smart Vote integration, and institutional safeguards.

This doctrine is also the correction contract for implementation passes. When generated code contradicts this doctrine, the doctrine wins and the code must be corrected. When connected-mode Konnaxion integration code contradicts the Smart Vote boundary in this doctrine, the boundary wins on the Moodle side and the implementation must be corrected.

## 2. Governing decision

UCKK-Moodle is the **campus implementation** of the Univers-Cité King Klown.

It is:

* a Moodle-based learning and governance environment;
* a complete pedagogical distribution;
* a set of coordinated Moodle plugins;
* an institutional seed package;
* a delivery contract for implementation teams;
* a correction standard for generated or assisted code;
* a governed optional integration surface for external kOA systems such as Konnaxion when they assist UCKK workflows without owning Moodle records.

It is not:

* the whole kOA movement;
* the whole kOA Digital Ecosystem;
* the Konnaxion platform;
* a Smart Vote authority that replaces Assemblées, archive provenance, or institutional decision records;
* a generic LMS skin;
* a symbolic website only;
* a fork of Moodle core unless a core change is absolutely unavoidable;
* an experimental skeleton containing stubs;
* a collection of files that only looks complete by name.

## 3. Canonical boundary

The implementation must preserve this hierarchy:

```text
kOA = movement
UCKK = school / learning city
kOA Digital Ecosystem = operational digital infrastructure
King Klown = narrative and mobilizing figure
Inquisiteur = ethical and methodological guardrail
Assemblées = collective legitimacy
Archives = memory
Konnaxion = optional external Smart Vote and participation-reading source family
EkoH = external ecosystem context or signal source where explicitly mapped
Smart Vote = computed reading, never final institutional decision
```

Every UI label, plugin responsibility, data model, and permission must preserve the distinction.

The Konnaxion/Smart Vote boundary is canonical:

```text
Konnaxion computes Smart Vote readings.
UCKK-Moodle owns Assembly decisions.
Archives preserve both, with provenance and contestability.
```

In connected mode, Smart Vote may inform a deliberation, provide a computed reading, expose minority signals, and support reporting. Smart Vote must not publish a final Assembly decision, award UCKK recognition, validate evidence, close a contestation, or bypass Moodle permissions.

## 4. Final product definition

The final version is delivered as:

```text
uckk-moodle/
├── docs/
│   ├── 00_master_execution_doctrine.md
│   ├── 01_domain_boundaries_and_glossary.md
│   ├── 02_distribution_architecture.md
│   ├── 03_plugin_specifications.md
│   ├── 04_data_model_and_storage.md
│   ├── 05_roles_permissions_and_security.md
│   ├── 06_pedagogy_courses_competencies_badges.md
│   ├── 07_challenges_and_assemblies.md
│   ├── 08_integrity_archives_and_privacy.md
│   ├── 09_integrations_reporting_delivery.md
│   └── 10_konnaxion_smart_vote_integration_contract.md
│
├── plugins/
│   ├── theme/uckk
│   ├── course/format/uckk
│   ├── local/uckk
│   ├── blocks/uckk_dashboard
│   ├── mod/uckkchallenge
│   ├── mod/uckkassembly
│   ├── mod/uckkarchive
│   ├── admin/tool/uckkseed
│   ├── admin/tool/uckkintegrity
│   ├── report/uckk
│   └── ai/provider/uckk
│
├── presets/
│   ├── categories.json
│   ├── courses.json
│   ├── cohorts.json
│   ├── roles.json
│   ├── capabilities.json
│   ├── competencies.json
│   ├── badges.json
│   ├── reports.json
│   ├── navigation.json
│   ├── state_machines.json
│   ├── events.json
│   ├── konnaxion_mappings.json
│   ├── privacy_retention.json
│   └── exports.json
│
├── tests/
│   ├── phpunit/
│   ├── behat/
│   └── fixtures/
│
└── release/
    ├── installation.md
    ├── upgrade.md
    ├── rollback.md
    └── acceptance-checklist.md
```

The `plugins/` path is the distribution packaging path. The same plugins must be installable at their Moodle-native locations, for example `local/uckk`, `mod/uckkarchive`, `admin/tool/uckkseed`, `course/format/uckk`, `blocks/uckk_dashboard`, `theme/uckk`, `report/uckk`, and `ai/provider/uckk`.

`presets/konnaxion_mappings.json` is a connected-mode preset. Its absence, emptiness, or disabled state must not block standalone installation or seed execution unless the release explicitly claims connected-mode support.

The old `10_implementation_correction_plan.md` slot is replaced by `10_konnaxion_smart_vote_integration_contract.md`. `DOC_10` is the optional connected-mode contract for Konnaxion Smart Vote. It must not be used to make Konnaxion a hard dependency of the standalone campus.

## 5. No-stub implementation rule

Every plugin in scope must be production-complete at delivery.

A plugin is not complete unless it contains:

```text
version.php
db/install.xml when storing data
db/upgrade.php when data schema may evolve
db/access.php for capabilities
db/services.php when exposing AJAX, external, mobile, or web-service functions
db/tasks.php when scheduled or asynchronous work exists
lang/en/<component>.php
lang/fr/<component>.php
classes/
classes/privacy/provider.php when personal data is stored
classes/external/* for every db/services.php function
classes/form/* for every Moodle form referenced by a page or workflow
classes/event/* for every declared or triggered event
classes/output/* for renderables, exporters, tables, or template view models
classes/task/* for every scheduled task
classes/service/* or classes/local/* for integration, registry, Konnaxion bridge, and Smart Vote business services when connected mode is enabled
settings.php when configurable
lib.php when Moodle plugintype requires it
locallib.php only for procedural helper functions that are safe outside upgrade/install context
amd/src/*.js when client-side behaviour is required
templates/*.mustache when server-rendered UI is required
tests/phpunit
tests/behat
README.md
```

A plugin may have intentionally disabled features only if the disabled behavior is a documented configuration option, not an unfinished stub.

A page, service, task, event, AMD module, template, capability, install step, upgrade step, or test that references a missing class is a defect, not a placeholder.

## 6. First-pass implementation correction gates

Before any Moodle installation attempt, generated code must pass these first-pass gates.

### Gate 1 — Filetype correctness

Every file must contain code appropriate to its extension and Moodle location:

```text
.php files must start with <?php unless they are pure templates or non-PHP assets.
amd/src/*.js files must contain JavaScript only.
.mustache files must contain Mustache templates only.
.json files must contain valid JSON.
.xml files must contain valid XML.
.scss and .css files must not contain PHP controller logic.
```

The following are blocking defects:

```text
PHP page/controller code inside amd/src/*.js
JavaScript module code inside .php controller files
Markdown fences inside PHP source files
raw implementation instructions inside executable source files
missing opening <?php in PHP source files
```

### Gate 2 — Moodle component correctness

Every plugin must declare the component matching its Moodle-native path:

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

The `@package` value, `$plugin->component`, language component names, capability prefixes, service component names, event namespaces, AMD module names, template names, and test namespaces must agree with the component.

### Gate 3 — Class-layer completeness

The `classes/` layer is mandatory for any plugin that declares or uses namespaced services, forms, events, outputs, scheduled tasks, privacy providers, or business services.

Procedural `lib.php` and `locallib.php` files may expose Moodle callbacks and stable helpers. They must not replace the class layer for:

```text
external service implementations
forms
renderables/exporters/view models
events
scheduled tasks
privacy providers
integrity decisions
archive validation engines
assembly decision engines
challenge submission engines
Konnaxion bridge services
Smart Vote reading import, snapshot, reporting, and contestation services
AI processing providers
```

### Gate 4 — Access and context correctness

Every user-facing page and every service endpoint must define:

```text
required login state
Moodle context
required capability
parameter validation
sesskey requirement for state changes
redirect or error behavior for unauthorized access
audit/event behavior when state changes
external-system boundary when the action uses Konnaxion or another integration
```

When connected-mode support is claimed, Konnaxion-facing services must additionally define authentication, endpoint ownership, request validation, timeout behavior, failure behavior, retry/idempotency policy, sync direction, provenance, audit event emission, and privacy impact.

No UCKK-specific role may be implemented as a hidden global administrator. The Inquisiteur, Archiviste, Mentor, Bâtisseur, and assembly roles must be capability-limited and auditable.

### Gate 5 — Data and upgrade correctness

Every table must have:

```text
owning plugin
install.xml definition
upgrade path when schema may evolve
backup/restore coverage when activity data is portable
privacy export/delete behavior when personal data exists
test coverage for create/read/update/delete and integrity constraints
```

When connected-mode support is claimed, Konnaxion mapping, sync, Smart Vote snapshot, external result audit, and vote-target mapping tables must be owned by exactly one Moodle plugin and must never allow Konnaxion to write directly into Moodle source tables.

Files in `db/` must remain self-contained. They must not include plugin libraries, render UI, call workflow logic, or rely on classes that may not be available during install or upgrade.

### Gate 6 — Privacy correctness

Every plugin that stores or exposes personal data must implement `classes/privacy/provider.php`.

Privacy coverage must include:

```text
user profile extensions
challenge submissions and evidence
assembly participation, votes, minutes, and decisions
archive items, provenance, proof files, and validation states
integrity cases, appeals, decisions, and audit notes
seed logs that identify users
reports that expose user-related data
AI prompts, outputs, summaries, logs, or moderation traces when retained
connected-mode Konnaxion user mappings, object mappings, vote-target mappings, sync logs, result snapshots, reading methods, external identifiers, expertise weights, and Smart Vote audit records
```

### Gate 7 — Test and build correctness

A generated pass is not acceptable until the following can run cleanly in a disposable Moodle development installation:

```text
php -l on all PHP files
JSON validation on all presets
XML validation on all install.xml files
Moodle plugin discovery
admin/cli/upgrade.php --non-interactive
Moodle cache purge
Moodle AMD build through Grunt
PHPUnit for each plugin
Behat for each major workflow
privacy API tests for plugins with personal data
backup/restore tests for activity modules
seed idempotency tests
connected-mode Konnaxion connector tests with mocked responses when connected-mode support is claimed
connected-mode Smart Vote reading snapshot tests when connected-mode support is claimed
connected-mode Konnaxion timeout, failure, retry, disabled-state, and idempotency tests when connected-mode support is claimed
connected-mode report/export tests for Konnaxion-derived data when connected-mode support is claimed
```

### Gate 8 — Canonical alignment variable correctness

The canonical alignment pass is not complete until every core variable in section 0 is resolved consistently across documentation, presets, code, tests, and release artifacts. Connected-mode Konnaxion variables must also be resolved for any release that claims Konnaxion Smart Vote support.

Blocking defects include:

```text
an active product tree still pointing to 10_implementation_correction_plan.md
using Smart Vote as a final decision authority
renaming connected-mode Konnaxion tables, services, events, capabilities, or status enums outside the registry
using Konnaxion, EkoH, Smart Vote, Assembly decision, imported reading, result snapshot, and minority report interchangeably
storing connected-mode Konnaxion personal data without privacy-provider coverage
storing imported readings without raw data, reading method, computed reading, decision linkage, provenance, and contestation status
allowing an external identifier or symbolic role to grant Moodle capability
missing connected-mode tests for timeout, failure, retry, disabled state, idempotency, privacy export, and contestation paths when connected-mode support is claimed
```

Every generated implementation pass must include a variable-resolution checklist proving that:

```text
DOC_00 through DOC_10 use the same document names
connected-mode Konnaxion object variables map to Moodle-side table variables when connected-mode support is claimed
all core capability variables exist in db/access.php and presets/capabilities.json; connected-mode capabilities exist when connected-mode support is claimed
all enabled service variables exist in db/services.php and classes/external
all enabled event variables exist in classes/event and presets/events.json
all core status variables exist in DOC_04, DOC_07, DOC_09, and presets/state_machines.json; connected-mode status variables also exist in DOC_10 when connected-mode support is claimed
all privacy/retention variables exist in DOC_08 and presets/privacy_retention.json
all core report/export variables exist in DOC_09, report_uckk, and presets/exports.json; connected-mode report/export variables exist when connected-mode support is claimed
```

## 7. Core strategy

Moodle core remains untouched unless all of the following are true:

1. no plugin type can implement the requirement;
2. no theme override can implement the UI requirement safely;
3. no course format override can implement the course behavior;
4. no admin tool, local plugin, report source, or web service can implement the cross-cutting behavior;
5. the change has a written justification and a rollback strategy.

A failing plugin implementation is not evidence that Moodle core must be changed. Core changes are justified only after plugin, theme, course format, local plugin, admin tool, report, block, web service, and AI provider extension points have been exhausted.

External integrations follow the same rule. Konnaxion, when enabled, must be integrated through Moodle plugins, settings, services, scheduled tasks, events, reports, and privacy providers. Konnaxion must not require direct database writes into Moodle source tables, hidden administrator privileges, Moodle core patches, or unlogged bypasses of Assembly, Archive, Integrity, or capability workflows. Disabling Konnaxion must not disable standalone UCKK-Moodle workflows.

## 8. Source anchors

The implementation is governed by three source families:

### UCKK canon

* `UCKK_Canon/00_index.md`
* `UCKK_Canon/01_glossaire.md`
* `UCKK_Canon/02_architecture-generale-kOA-UCKK-digital-ecosystem.md`
* `UCKK_Canon/20_UCKK-document-fondateur.md`
* `UCKK_Canon/22_UCKK-gouvernance-assemblees-inquisiteur.md`
* `UCKK_Canon/23_UCKK-defis-theatre-public.md`
* `UCKK_Canon/30_UCKK-catalogue-academique.md`
* `UCKK_Canon/31_UCKK-tronc-commun.md`
* `UCKK_Canon/42_UCKK-liste-et-fiches-de-cours.md`
* `UCKK_Canon/cours/*.md`

### Konnaxion external source family

The Konnaxion reference file is an external source of truth for Konnaxion-side Smart Vote concepts. It must not be rewritten as part of UCKK-Moodle unless a documented contradiction is found in the Konnaxion source itself.

The connected-mode Moodle implementation must map, at minimum, the Konnaxion objects:

```text
Vote
VoteModality
VoteResult
IntegrationMapping
```

In connected mode, Konnaxion source data may be imported, snapshotted, reported, contested, and archived inside Moodle only through documented Moodle-side services and tables. The Konnaxion source remains external; UCKK-Moodle owns its local mappings, Assembly decisions, reports, privacy handling, and archive records.

### Moodle developer documentation

* `versioned_docs/version-5.1/apis.md`
* `versioned_docs/version-5.1/apis/plugintypes/index.md`
* `versioned_docs/version-5.1/apis/plugintypes/local/index.mdx`
* `versioned_docs/version-5.1/apis/plugintypes/format/index.md`
* `versioned_docs/version-5.1/apis/plugintypes/mod/index.mdx`
* `versioned_docs/version-5.1/apis/plugintypes/theme/index.md`
* `versioned_docs/version-5.1/apis/plugintypes/blocks/index.md`
* `versioned_docs/version-5.1/apis/subsystems/access.md`
* `versioned_docs/version-5.1/apis/subsystems/privacy/index.md`
* `versioned_docs/version-5.1/apis/core/reportbuilder/index.md`
* `versioned_docs/version-5.1/apis/subsystems/ai/index.md`
* `versioned_docs/version-5.1/apis/javascript/index.md`
* `versioned_docs/version-5.1/apis/subsystems/external/index.md`
* `versioned_docs/version-5.1/apis/subsystems/files/index.md`
* `versioned_docs/version-5.1/apis/subsystems/backup/index.md`
* `versioned_docs/version-5.1/apis/subsystems/events/index.md`
* `versioned_docs/version-5.1/apis/subsystems/tasks.md`
* `versioned_docs/version-5.1/apis/subsystems/output/index.md`
* `versioned_docs/version-5.1/apis/tools/phpunit/index.md`
* `versioned_docs/version-5.1/apis/tools/behat/index.md`

### Canonical registries

The documentation set must maintain these registries before generated implementation code is considered aligned:

| Registry                   | Canonical location                                                                                                                                                      | Purpose                                                                                    |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Capability registry        | `05_roles_permissions_and_security.md` and `presets/capabilities.json`                                                                                                  | Every capability declared in code, docs, tests, and seed data.                             |
| State-machine registry     | `04_data_model_and_storage.md`, `07_challenges_and_assemblies.md`, and `presets/state_machines.json`                                                                    | Allowed workflow states and transitions.                                                   |
| Event-class registry       | `02_distribution_architecture.md`, `03_plugin_specifications.md`, `07_challenges_and_assemblies.md`, `09_integrations_reporting_delivery.md`, and `presets/events.json` | Every emitted or observed Moodle event.                                                    |
| Konnaxion mapping registry | `04_data_model_and_storage.md`, `09_integrations_reporting_delivery.md`, `10_konnaxion_smart_vote_integration_contract.md`, and `presets/konnaxion_mappings.json` | Connected-mode external identifiers, Moodle-side objects, vote targets, snapshots, and sync status. |
| Seed preset path registry  | This doctrine, `09_integrations_reporting_delivery.md`, and `tool_uckkseed` tests                                                                                       | All required preset files and idempotent seed behavior.                                    |
| Privacy/retention registry | `08_integrity_archives_and_privacy.md` and `presets/privacy_retention.json`                                                                                             | Export, deletion, anonymisation, redaction, archive retention, and contestation retention. |
| Report/export registry     | `09_integrations_reporting_delivery.md`, `report_uckk`, and `presets/exports.json`                                                                                      | Institutional reports, Smart Vote reports, archive exports, and acceptance evidence.       |

No implementation pass may treat these registries as optional prose. Code, seed data, tests, and release acceptance must agree with the core registries for every release, and with the Konnaxion mapping registry for every release that claims connected-mode support.

## 9. Completion standard

UCKK-Moodle has two release gates: standalone core and optional Konnaxion-connected mode. The standalone core gate is mandatory for every release. The Konnaxion-connected gate is mandatory only for releases that claim Konnaxion Smart Vote support.

### 9.1 Core standalone completion

The standalone adaptation is complete only when a clean Moodle installation can be turned into UCKK-Moodle by installing the package and running the seed tool, producing:

* UCKK categories;
* tronc commun courses;
* internal programs;
* roles and capabilities;
* cohorts;
* competencies;
* badges;
* dashboards;
* challenge activity;
* assembly activity;
* archive activity;
* integrity system;
* reports;
* AI provider configuration that can be enabled or disabled safely;
* canonical core alignment variables resolved across docs, presets, services, events, tables, capabilities, privacy, reports, and tests;
* privacy providers;
* tests;
* documentation;
* acceptance evidence.

Standalone completion must not require Konnaxion credentials, Konnaxion mappings, Smart Vote snapshots, Smart Vote reports, external vote portals, Konnaxion sync tasks, or Konnaxion API availability.

### 9.2 Optional Konnaxion-connected completion

The connected-mode profile is complete only when the standalone gate passes and the enabled Konnaxion profile additionally provides:

* Konnaxion integration configuration;
* Konnaxion bridge enable/disable controls;
* Konnaxion mapping registry;
* Konnaxion health check without secret exposure;
* Konnaxion timeout, failure, retry, idempotency, and disabled-state behavior;
* Smart Vote target mapping;
* Smart Vote reading requests;
* Smart Vote reading snapshots;
* Smart Vote review, contestation, supersession, and archive linkage;
* Smart Vote reports and exports;
* connected-mode privacy, retention, redaction, provenance, and audit coverage;
* connected-mode PHPUnit, Behat, and failure-path acceptance evidence.

Completion requires both functional behavior and implementation hygiene. A feature that appears in the UI but fails install checks, privacy checks, upgrade checks, build checks, capability checks, or test checks is incomplete. A connected-mode feature that is disabled must fail closed without breaking standalone workflows.

## 10. Non-negotiable final checks

The final implementation must pass the core checks for every release. Connected-mode checks apply only when the release claims Konnaxion Smart Vote support.

### 10.1 Core standalone checks

```text
[ ] Moodle core remains clean or every core change is justified.
[ ] Every plugin installs without warnings with Konnaxion disabled.
[ ] Every plugin has version metadata and dependency declarations.
[ ] No plugin declares Konnaxion as a hard dependency for standalone mode.
[ ] Every plugin component name matches its Moodle-native path.
[ ] Every PHP source file parses with php -l.
[ ] Every JavaScript AMD source file contains JavaScript only.
[ ] Every PHP controller file contains PHP only and starts correctly.
[ ] No Markdown fences or implementation notes remain inside executable source files.
[ ] Every core table has install and upgrade support.
[ ] Every db/ file is self-contained and safe during install/upgrade.
[ ] Every enabled db/services.php declaration has a matching classes/external implementation.
[ ] Every referenced form class exists under classes/form.
[ ] Every triggered or declared event class exists under classes/event.
[ ] Every scheduled task declaration has a matching classes/task implementation or is disabled by configuration.
[ ] Every renderable/exporter/view model exists under classes/output or another appropriate class namespace.
[ ] Every personal data store has a privacy provider.
[ ] Every privacy provider is tested for export, deletion, and metadata coverage.
[ ] Every capability is explicit and context-aware.
[ ] Every state-changing page or service validates sesskey and permissions.
[ ] Every major standalone workflow has Behat coverage.
[ ] Every data service has PHPUnit coverage.
[ ] Every activity module has backup/restore coverage.
[ ] Moodle AMD build passes.
[ ] Core preset JSON validates.
[ ] The seed tool can create a full standalone UCKK campus.
[ ] The seed tool can be re-run idempotently.
[ ] The UCKK / kOA / kOA Digital Ecosystem / King Klown / Konnaxion distinction remains visible.
[ ] Badges and competencies are linked to evidence.
[ ] Challenges and assemblies are auditable without Smart Vote.
[ ] Assembly decisions work without Konnaxion or Smart Vote.
[ ] Archives preserve provenance without requiring Smart Vote snapshots.
[ ] The Inquisiteur cannot become an unrestricted super-admin by design.
[ ] AI outputs are never final authority.
[ ] Canonical core alignment variables from section 0 are resolved consistently across docs, presets, code, tests, and release artifacts.
[ ] `DOC_10` is `10_konnaxion_smart_vote_integration_contract.md` and is treated as optional connected-mode contract.
```

### 10.2 Optional Konnaxion-connected checks

```text
[ ] Konnaxion bridge is disabled safely by default.
[ ] Konnaxion can be enabled and disabled from Moodle administration settings.
[ ] Disabling Konnaxion hides or disables Smart Vote actions without breaking Assemblies.
[ ] Konnaxion table names match the canonical `TABLE_*` variables.
[ ] Konnaxion capabilities match the canonical `CAP_*` variables.
[ ] Konnaxion services and events match the canonical `SERVICE_*` and `EVENT_*` variables.
[ ] Smart Vote status values match the canonical `*_STATUS_ENUM` variables.
[ ] Konnaxion is declared as an external source family and never mistaken for Moodle ownership.
[ ] Konnaxion never writes directly into Moodle source tables.
[ ] Konnaxion mappings preserve external identifiers without exposing secrets or unnecessary personal data.
[ ] Smart Vote readings distinguish raw data, reading method, computed reading, human/institutional decision, minority report, and integrity warning.
[ ] Smart Vote readings cannot publish final Assembly decisions.
[ ] UCKK-Moodle Assembly decisions remain human/institutional, permission-checked, archived, and contestable.
[ ] Konnaxion timeout, failure, retry, disabled-state, and sync behavior is documented and tested.
[ ] Konnaxion-derived reports and exports preserve provenance and privacy controls.
[ ] Konnaxion outage fails safely and never fabricates or silently accepts results.
```

## 11. First-pass correction priority

When an implementation pass produces errors, corrections must happen in this order:

```text
1. Filetype correctness errors that prevent parsing or building.
2. Canonical alignment variable conflicts that would make generated files disagree.
3. Moodle component-name and plugin-discovery errors.
4. Missing classes referenced by services, pages, events, forms, tasks, outputs, or tests.
5. Database install/upgrade defects.
6. Connected-mode Konnaxion mapping, service-contract, source-table direct-write, disabled-state, and Smart Vote sovereignty defects.
7. Capability, context, sesskey, and access-control defects.
8. Privacy-provider defects.
9. Backup/restore defects.
10. PHPUnit failures.
11. Behat failures.
12. UI polish, language improvements, and non-blocking refinements.
```

A Moodle browser installation attempt must not be used as the first diagnostic step. Static checks and disposable CLI install checks come first.

## 12. Primary formula

> UCKK-Moodle is the standalone Moodle campus of the Univers-Cité King Klown: pedagogical like Moodle, symbolic like UCKK, governable like kOA, traceable like an archive, and contestable like a healthy institution.
>
> Konnaxion is an optional connected-mode integration. When connected, Konnaxion may compute Smart Vote readings, but UCKK-Moodle Assemblées decide, and Archives preserve both.
