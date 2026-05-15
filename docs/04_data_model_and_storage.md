# 04 — Data Model and Storage

**Status:** Final data design + standalone/connected-mode implementation correction contract  
**Purpose:** Define the cross-plugin UCKK-Moodle data model, including the standalone core storage contract and the optional Konnaxion-connected storage contract that enables Smart Vote readings without making Konnaxion a core dependency.

This document is normative. A generated plugin file is not considered complete merely because it declares a table name, service, form, event, scheduled task, or privacy string. Every stored object must have a table owner, Moodle context, capability model, privacy handling, install/upgrade path, tests, and a documented state machine when it has workflow status.

UCKK-Moodle is self-standing. Konnaxion is an optional connected-mode integration. When Konnaxion is disabled or unavailable, UCKK-Moodle must still install, seed, teach, deliberate, archive, report, and enforce permissions through Moodle-owned data.

## 0. Operating-mode storage boundary

UCKK-Moodle has two storage profiles:

| Profile | Meaning | Storage rule |
|---|---|---|
| `standalone_core` | UCKK-Moodle without Konnaxion | All core UCKK tables, file areas, privacy providers, state machines, reports, and tests must work without Konnaxion records. |
| `connected_konnaxion` | UCKK-Moodle connected to Konnaxion | Adds Konnaxion mapping tables, sync logs, Smart Vote target mapping, Smart Vote snapshots, and Smart Vote audit records. |

Canonical storage rules:

```text
KONNAXION_REQUIRED_FOR_CORE = false
SMART_VOTE_REQUIRED_FOR_CORE = false
KONNAXION_DEFAULT_STATE = disabled
SMART_VOTE_DEFAULT_STATE = disabled unless Konnaxion bridge is enabled
DIRECT_WRITE_RULE = external_systems_never_write_moodle_source_tables
PERMISSION_RULE = moodle_capabilities_remain_authoritative
SMART_VOTE_AUTHORITY = computed_reading_only
```

Standalone mode must treat the absence of Konnaxion mappings, Smart Vote targets, Smart Vote snapshots, and Konnaxion sync logs as valid. An empty connected-mode table is not a missing institutional record.

Connected mode may store imported or referenced Konnaxion data only through Moodle-owned tables, services, privacy providers, archive rules, and audit events. Konnaxion must never write directly into Moodle source tables.

## 1. Common storage principles

All UCKK tables must use Moodle-compatible XMLDB definitions and Moodle upgrade paths.

Initial schema belongs in each owning plugin's `db/install.xml`. Schema evolution belongs in that same plugin's `db/upgrade.php`. Runtime code, rendering code, service calls, validation workflows, archive export, privacy deletion, and seed execution must not be placed in `db/upgrade.php`.

Stable fields must be first-class columns. `metadata` may store JSON only when the field is genuinely variable, extension-oriented, or non-query-critical. A value that is filtered, joined, permission-checked, sorted, reported, exported for privacy, or used in a state transition must be a real column.

Common columns for UCKK-owned object tables:

```text
id BIGINT PK
courseid BIGINT NULL
cmid BIGINT NULL
contextid BIGINT NOT NULL
userid BIGINT NULL
createdby BIGINT NOT NULL
modifiedby BIGINT NULL
timecreated BIGINT NOT NULL
timemodified BIGINT NOT NULL
status VARCHAR(64) NOT NULL
visibility VARCHAR(64) NOT NULL
versionno BIGINT NOT NULL DEFAULT 1
provenancehash VARCHAR(128) NULL
metadata LONGTEXT NULL
```

Implementation notes:

- Moodle activity instance tables use Moodle's expected activity-module field conventions, including `course`, `name`, `intro`, `introformat`, `timecreated`, and `timemodified` where applicable.
- Subordinate UCKK records should use `courseid`, `cmid`, and `contextid` when the object can be addressed outside the activity instance row.
- Every foreign-key-like column must be indexed, even when Moodle XMLDB does not enforce a physical FK on all supported databases.
- Every `userid`, `createdby`, `modifiedby`, `openedby`, `assignedto`, `proposerid`, `changedby`, and `owneruserid` field is personal-data-bearing and must be covered by the owning plugin's privacy provider.
- Every table containing long text authored by a user must define privacy export, deletion/anonymisation, and retention behavior.
- Every `status` and `visibility` column must have an allowed-value list in this document and tests for invalid transitions.

## 2. Implementation correction rules

The current implementation pass must be corrected against these rules before install testing:

```text
[ ] Every table listed here exists in exactly one owning plugin's db/install.xml.
[ ] Every table has a future-safe db/upgrade.php path in the same plugin.
[ ] Every table has a privacy-provider entry, or a documented null-provider reason.
[ ] Every table with user data has PHPUnit coverage for create/read/update/delete/privacy export.
[ ] Every status field has a documented state machine and transition guard.
[ ] Every visibility field has a documented access rule and context check.
[ ] Every file area has a pluginfile handler and capability check.
[ ] Every external service that reads/writes table data has a matching classes/external/* implementation.
[ ] Every event named in db/events.php or emitted by write code has a matching classes/event/* implementation.
[ ] Every scheduled task named in db/tasks.php has a matching classes/task/* implementation.
[ ] No page, service, task, upgrade step, or seed action references a table or class that does not exist.
[ ] Standalone mode works when Konnaxion bridge and Smart Vote are disabled.
[ ] Connected-mode Konnaxion tables use the canonical names in this document.
[ ] Connected-mode Smart Vote records remain readings, not final Assembly decisions.
```

## 3. Table ownership registry

| Table | Owner plugin | Primary context | Personal data | Privacy action | Required tests |
|---|---|---|---|---|---|
| `local_uckk_program` | `local_uckk` | System/category | No direct user data | Metadata export only if user annotations are added | PHPUnit registry tests |
| `local_uckk_pathway` | `local_uckk` | Category/user | May reference user assignment indirectly | Export assigned pathway records | PHPUnit pathway tests |
| `local_uckk_player_profile` | `local_uckk` | User | Yes | Export/delete/anonymise by user | PHPUnit privacy + profile tests |
| `local_uckk_provenance` | `local_uckk` | Source object context | May reference users and authored text | Export by user where linked; retain institutional provenance when legally required | PHPUnit provenance tests |
| `local_uckk_kx_user_map` | `local_uckk` | User/system | Yes, identity-linking data | Export/delete/anonymise according to user privacy policy; never expose secrets | PHPUnit connected-mode privacy + mapping tests |
| `local_uckk_kx_object_map` | `local_uckk` | Source object context | Usually no direct user data, may become personal by linkage | Export where linked to a user; retain/invalidate with mapped object | PHPUnit mapping + provenance tests |
| `local_uckk_kx_sync_log` | `local_uckk` | System/source object context | May contain admin user ids and endpoint metadata | Retain according to Konnaxion retention setting; redact payloads and secrets | PHPUnit sync log + retention tests |
| `uckkchallenge` | `mod_uckkchallenge` | Module | Limited author/config data | Export module participation metadata | PHPUnit module tests |
| `uckkchallenge_submission` | `mod_uckkchallenge` | Module | Yes | Export/delete/anonymise submissions and links | PHPUnit + Behat submission tests |
| `uckkassembly` | `mod_uckkassembly` | Module | Limited author/config data | Export participation metadata | PHPUnit module tests |
| `uckkassembly_motion` | `mod_uckkassembly` | Module | Yes | Export/delete/anonymise authored motions where policy allows | PHPUnit motion tests |
| `uckkassembly_decision` | `mod_uckkassembly` | Module | May contain user references | Export where user contributed; retain institutional decision record | PHPUnit decision tests |
| `uckkassembly_kx_vote_target` | `mod_uckkassembly` | Module | May reference participants or mapped objects | Export where user-linked; archive/invalidate with Assembly object | PHPUnit Smart Vote target + capability tests |
| `uckkassembly_sv_snapshot` | `mod_uckkassembly` | Module | May contain aggregated or linkable voting data | Export/redact according to visibility and source privacy; retain when used in decisions | PHPUnit Smart Vote snapshot + privacy tests |
| `uckkassembly_sv_result_audit` | `mod_uckkassembly` | Module/integrity context | Yes, may reference reviewers and contesters | Export/restrict/retain under integrity and Assembly retention rules | PHPUnit Smart Vote audit + contestation tests |
| `uckkarchive` | `mod_uckkarchive` | Module | Limited config data | Export archive participation metadata | PHPUnit module tests |
| `uckkarchive_item` | `mod_uckkarchive` | Module/course/user | Yes | Export/delete/anonymise according to visibility and retention | PHPUnit archive tests |
| `uckkarchive_version` | `mod_uckkarchive` | Parent archive item | Yes | Export versions where user-authored; retention rules apply | PHPUnit versioning tests |
| `tool_uckkintegrity_case` | `tool_uckkintegrity` | System/course/module/user | Yes, sensitive | Restricted export; deletion/anonymisation controlled by case retention | PHPUnit privacy + case tests |
| `tool_uckkintegrity_note` | `tool_uckkintegrity` | Parent integrity case | Yes, sensitive | Restricted export; deletion/anonymisation controlled by case retention | PHPUnit note tests |
| `tool_uckkseed_log` | `tool_uckkseed` | System | Admin user ids and logs | Export/delete old logs by retention | PHPUnit seed log tests |
| `aiprovider_uckk_log` | `aiprovider_uckk` | Originating action context | Yes, sensitive | Export/delete prompt/response logs; redact when configured | PHPUnit AI privacy tests |

Plugins with no own tables must say so explicitly in their privacy provider and tests:

| Plugin | Expected storage posture |
|---|---|
| `theme_uckk` | Presentation-only; no own tables. Settings are Moodle config. |
| `format_uckk` | Prefer Moodle `course_format_options`; no own tables unless later justified. |
| `block_uckk_dashboard` | Prefer block config and read-only aggregation; user preferences need an explicit table and privacy provider. |
| `report_uckk` | Read-only reports; no own tables unless caching is introduced. Cached personal data requires privacy coverage. |

## 4. Context strategy

| Object | Moodle context | Required access rule |
|---|---|---|
| Program registry | System or category context | View/manage through `local/uckk:*` capabilities. |
| Pathway assignment | User + category context | User can view own assignment; managers view within category/system scope. |
| Course format data | Course context | Course capability checks only; no hidden learner data in diagnostics. |
| Challenge instance | Module context | Module view/manage capabilities. |
| Challenge submission | Module context | Submitter, mentor, grader, or manager according to capability. |
| Assembly instance | Module context | Module participation/view/manage capabilities. |
| Motion / vote / decision | Module context | Participant visibility plus decision archive rules. |
| Archive item | Module, course, or user context depending on owner | Visibility and provenance determine access. |
| Integrity case | System, course, module, or user context | Restricted Inquisiteur capabilities; no broad manager leakage by default. |
| Dashboard preference | User context | User owns own preference; managers do not need write access. |
| AI prompt/response logs | Context of originating action | Only authorised log viewers; never exposed to normal learners by default. |
| Konnaxion user mapping | User + system context | User privacy controls plus `local/uckk:managekonnaxion` or equivalent mapped capability. |
| Konnaxion object mapping | Context of mapped Moodle object | Same visibility as mapped object plus Konnaxion mapping capability. |
| Konnaxion sync log | System + mapped object context when available | Restricted operational visibility; payloads disabled/redacted by default. |
| Smart Vote target | Assembly module context | Assembly capability checks; hidden when Konnaxion-connected mode is disabled. |
| Smart Vote snapshot | Assembly module context | View only as a reading, never as a final decision; archive visibility applies when preserved. |
| Smart Vote result audit | Assembly or integrity context | Restricted review/contestation permissions; never broad report visibility by default. |

Context ids must be stored when they are needed for privacy export, capability checks, event payloads, report filtering, or file serving. If a context can be derived safely from `cmid` or `courseid`, the code may derive it, but tests must prove the derivation.

## 5. `local_uckk` tables

### `local_uckk_program`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| shortname | VARCHAR(100) | Stable program key |
| fullname | VARCHAR(255) | Display name |
| programtype | VARCHAR(64) | `tronc_commun`, `baccalaureat`, `mineure`, `lab`, `seminar` |
| categoryid | BIGINT | Linked Moodle category |
| description | LONGTEXT | Canonical description |
| status | VARCHAR(64) | `draft`, `active`, `hidden`, `archived` |
| sortorder | BIGINT | Display order |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required: local_uckk\local\program_repository, local_uckk\external\get_programs
Events: local_uckk\event\program_created, local_uckk\event\program_updated
Privacy: no direct user data unless user-authored descriptions are later added
Tests: repository, external service, seed idempotency
```

### `local_uckk_pathway`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| programid | BIGINT | Program FK |
| shortname | VARCHAR(100) | Pathway key |
| fullname | VARCHAR(255) | Display name |
| requiredcourseids | LONGTEXT | JSON list |
| requiredbadges | LONGTEXT | JSON list |
| requiredcompetencies | LONGTEXT | JSON list |
| status | VARCHAR(64) | `draft`, `active`, `archived` |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required: local_uckk\local\pathway_repository, local_uckk\external\get_pathways
Events: local_uckk\event\pathway_created, local_uckk\event\pathway_updated
Privacy: pathway definitions are not personal; assignments are personal if stored separately
Tests: repository, JSON validation, seed idempotency
```

### `local_uckk_player_profile`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| userid | BIGINT | Moodle user |
| displaytitle | VARCHAR(255) | Public UCKK title |
| symbolicroles | LONGTEXT | JSON list |
| activepathwayids | LONGTEXT | JSON list |
| portfolioarchiveid | BIGINT NULL | Archive link |
| integrityflags | LONGTEXT NULL | JSON |
| visibility | VARCHAR(64) | `private`, `cohort`, `course`, `public` |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required: local_uckk\local\profile_repository, local_uckk\external\get_profile, local_uckk\external\update_profile
Events: local_uckk\event\profile_updated
Privacy: full export/delete/anonymise by userid
Tests: repository, permission checks, privacy provider, Behat profile view/edit workflow
```

### `local_uckk_provenance`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| component | VARCHAR(100) | Owning plugin |
| itemtype | VARCHAR(100) | Object type |
| itemid | BIGINT | Object id |
| contextid | BIGINT | Context of the target object |
| sourcecomponent | VARCHAR(100) | Origin |
| sourceid | BIGINT NULL | Origin id |
| sourcetext | LONGTEXT NULL | Human source description |
| hash | VARCHAR(128) NULL | Integrity hash |
| state | VARCHAR(64) | `draft`, `verified`, `contested`, `invalidated` |
| createdby | BIGINT | User who created the provenance record |
| timecreated | BIGINT | Creation time |

Implementation contract:

```text
Owner: local_uckk unless a plugin keeps provenance locally
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required: local_uckk\local\provenance_repository
Events: local_uckk\event\provenance_created, local_uckk\event\provenance_state_changed
Privacy: export records created by or directly referencing a user; retain institutional hashes when needed
Tests: hash stability, state transition, privacy export
```

## 6. Optional Konnaxion-connected storage

This section is active only for `connected_konnaxion` mode. These tables are not required for standalone UCKK-Moodle behavior, but if the Konnaxion bridge is enabled they become part of the connected-mode acceptance gate.

The abbreviation `kx` is allowed only in table and internal variable names. User-facing documentation and UI must use `Konnaxion`.

### `local_uckk_kx_user_map`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| userid | BIGINT | Moodle user id |
| externalid | VARCHAR(255) | Konnaxion user identifier |
| externaltype | VARCHAR(100) | Konnaxion object type, normally `User` or equivalent identity type |
| sourceversion | VARCHAR(255) NULL | External source version/revision/timestamp where available |
| syncstatus | VARCHAR(64) | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled` |
| mappingstatus | VARCHAR(64) | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` |
| provenancehash | VARCHAR(128) NULL | Integrity hash for mapping provenance |
| createdby | BIGINT | User who created or approved mapping |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required in connected mode: local_uckk\service\konnaxion_mapping_service, local_uckk\external\create_konnaxion_mapping, local_uckk\external\get_konnaxion_mapping
Events: local_uckk\event\konnaxion_mapping_created
Privacy: identity-linking data; export/delete/anonymise by userid where policy allows; never store API secrets
Tests: connected-mode mapping CRUD, privacy provider, capability checks, disabled-mode behavior
```

### `local_uckk_kx_object_map`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| component | VARCHAR(100) | Moodle component owning the mapped object |
| itemtype | VARCHAR(100) | Moodle-side object type |
| itemid | BIGINT | Moodle-side object id |
| contextid | BIGINT | Context of the mapped object |
| externalid | VARCHAR(255) | Konnaxion object identifier |
| externaltype | VARCHAR(100) | Konnaxion object type such as `Vote`, `VoteModality`, `VoteResult`, or `IntegrationMapping` |
| sourceversion | VARCHAR(255) NULL | External source version/revision/timestamp where available |
| mappingstatus | VARCHAR(64) | `draft`, `active`, `suspended`, `superseded`, `archived`, `invalidated` |
| provenancehash | VARCHAR(128) NULL | Integrity hash for mapping provenance |
| createdby | BIGINT | User who created mapping |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required in connected mode: local_uckk\service\konnaxion_mapping_service
Events: local_uckk\event\konnaxion_mapping_created
Privacy: export if user-linked; retain/invalidate according to mapped Moodle object lifecycle
Tests: mapping uniqueness, context checks, invalidation, privacy metadata
```

### `local_uckk_kx_sync_log`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| component | VARCHAR(100) | Moodle component initiating sync |
| itemtype | VARCHAR(100) NULL | Moodle-side object type |
| itemid | BIGINT NULL | Moodle-side object id |
| contextid | BIGINT NULL | Context when available |
| operation | VARCHAR(100) | `healthcheck`, `map`, `sync`, `request_reading`, `import_snapshot`, `retry` |
| syncstatus | VARCHAR(64) | `queued`, `running`, `succeeded`, `failed`, `retry_waiting`, `skipped`, `disabled` |
| endpoint | VARCHAR(255) NULL | Endpoint label or path, never full secret-bearing URL |
| statuscode | BIGINT NULL | Remote status code when available |
| idempotencykey | VARCHAR(128) NULL | Retry/idempotency key |
| errormessage | LONGTEXT NULL | Redacted error summary |
| metadata | LONGTEXT NULL | Redacted JSON metadata; payload logging disabled by default |
| createdby | BIGINT NULL | User or task actor where applicable |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: local_uckk
Install path: local/uckk/db/install.xml
Upgrade path: local/uckk/db/upgrade.php
Classes required in connected mode: local_uckk\service\konnaxion_sync_service, local_uckk\external\sync_konnaxion
Events: local_uckk\event\konnaxion_sync_completed
Privacy: restricted operational log; secrets and raw payloads forbidden by default; retention cleanup required
Tests: timeout, retry, disabled state, idempotency, retention cleanup, secret redaction
```

### `uckkassembly_kx_vote_target`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| assemblyid | BIGINT | FK to `uckkassembly.id` |
| motionid | BIGINT NULL | FK to `uckkassembly_motion.id` when target is motion-specific |
| contextid | BIGINT | Assembly module context |
| externalid | VARCHAR(255) | Konnaxion vote target identifier |
| externaltype | VARCHAR(100) | Usually `Vote` or `IntegrationMapping` |
| targetstatus | VARCHAR(64) | `draft`, `mapped`, `active`, `closed`, `archived`, `contested` |
| readingmethod | VARCHAR(100) NULL | Smart Vote modality or weighting method |
| sourceversion | VARCHAR(255) NULL | External source version/revision/timestamp where available |
| provenancehash | VARCHAR(128) NULL | Mapping or request hash |
| createdby | BIGINT | User who requested or approved target |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

Implementation contract:

```text
Owner: mod_uckkassembly
Install path: mod/uckkassembly/db/install.xml
Upgrade path: mod/uckkassembly/db/upgrade.php
Classes required in connected mode: mod_uckkassembly\external\request_smart_vote_reading
Events: mod_uckkassembly\event\smart_vote_reading_requested
Privacy: export if linked to user-created motion or request; retain with Assembly decision when used
Tests: target mapping, capability checks, disabled-mode hiding, state transitions
```

### `uckkassembly_sv_snapshot`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| assemblyid | BIGINT | FK to `uckkassembly.id` |
| motionid | BIGINT NULL | FK to `uckkassembly_motion.id` |
| targetid | BIGINT | FK to `uckkassembly_kx_vote_target.id` |
| contextid | BIGINT | Assembly module context |
| raw_data | LONGTEXT NULL | Imported or referenced source facts before interpretation; store aggregated/redacted form where possible |
| reading_method | VARCHAR(100) | Declared modality, weighting, or algorithmic reading rule |
| computed_reading | LONGTEXT | Non-sovereign Smart Vote output |
| expertise_weight | LONGTEXT NULL | Weighting metadata; sensitive when linkable to users |
| minority_report | LONGTEXT NULL | Dissenting or alternative interpretation if supplied |
| integrity_warning | LONGTEXT NULL | Warning that may open integrity review but is not itself a sanction |
| contestation_status | VARCHAR(64) | `imported`, `under_review`, `accepted_as_reading`, `contested`, `superseded`, `archived`, `invalidated` |
| archiveitemid | BIGINT NULL | Archive item preserving snapshot when archived |
| provenancehash | VARCHAR(128) NULL | Snapshot integrity hash |
| importedby | BIGINT | User or task actor that imported snapshot |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: mod_uckkassembly
Install path: mod/uckkassembly/db/install.xml
Upgrade path: mod/uckkassembly/db/upgrade.php
Classes required in connected mode: mod_uckkassembly\external\import_smart_vote_snapshot
Events: mod_uckkassembly\event\smart_vote_snapshot_imported, mod_uckkassembly\event\smart_vote_snapshot_archived
Privacy: classify raw/weighted data before display/export; retain institutional snapshot when used in decision, with redaction rules
Tests: import validation, no overwrite without audit trail, privacy export/redaction, archive linkage
```

### `uckkassembly_sv_result_audit`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| snapshotid | BIGINT | FK to `uckkassembly_sv_snapshot.id` |
| contextid | BIGINT | Assembly or integrity context |
| auditstatus | VARCHAR(64) | `recorded`, `reviewed`, `corrected`, `contested`, `resolved` |
| action | VARCHAR(100) | Review, correction, contestation, supersession, archive, or invalidation action |
| note | LONGTEXT NULL | Audit note or correction reason |
| changedby | BIGINT | User who performed action |
| supersedessnapshotid | BIGINT NULL | Earlier snapshot superseded by this action |
| archiveitemid | BIGINT NULL | Archive item preserving the audit record |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: mod_uckkassembly
Install path: mod/uckkassembly/db/install.xml
Upgrade path: mod/uckkassembly/db/upgrade.php
Classes required in connected mode: mod_uckkassembly\external\contest_smart_vote_snapshot
Events: mod_uckkassembly\event\smart_vote_snapshot_contested
Privacy: restricted audit trail; export user-authored contestations where policy allows; retain institutional audit history
Tests: review, correction, contestation, supersession, archive linkage, restricted visibility
```

Smart Vote data remains `computed_reading_only`. The human/institutional Assembly decision remains in `uckkassembly_decision`; Smart Vote snapshots must not overwrite that table or publish decisions automatically.

## 7. `mod_uckkchallenge` tables

### `uckkchallenge`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | Instance id |
| course | BIGINT | Moodle course id |
| name | VARCHAR(255) | Activity name |
| intro | LONGTEXT | Moodle intro |
| introformat | SMALLINT | Moodle intro format |
| challengecode | VARCHAR(100) | Stable challenge code |
| challengetype | VARCHAR(64) | `public`, `internal`, `evaluated`, `lab` |
| statement | LONGTEXT | Challenge statement |
| rules | LONGTEXT | Rules |
| criteria | LONGTEXT | Evaluation criteria |
| evidencepolicy | LONGTEXT | Expected proof |
| corridors | LONGTEXT | JSON corridors |
| integrityrequired | TINYINT | Requires Inquisiteur review |
| archivepolicy | VARCHAR(64) | `none`, `summary`, `full` |
| timeopen | BIGINT | Open time |
| timeclose | BIGINT | Close time |
| status | VARCHAR(64) | `draft`, `open`, `closed`, `archived` |
| timecreated | BIGINT | Creation time |
| timemodified | BIGINT | Modified time |

### `uckkchallenge_submission`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| challengeid | BIGINT | FK to `uckkchallenge.id` |
| userid | BIGINT | Submitter |
| groupid | BIGINT NULL | Team |
| title | VARCHAR(255) | Submission title |
| body | LONGTEXT | Submission text |
| proofsummary | LONGTEXT | Evidence summary |
| status | VARCHAR(64) | `draft`, `submitted`, `review`, `revision`, `validated`, `archived` |
| grade | DECIMAL NULL | Grade if used |
| mentorfeedback | LONGTEXT NULL | Feedback |
| integritycaseid | BIGINT NULL | Integrity case |
| archiveitemid | BIGINT NULL | Archive link |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

Implementation contract:

```text
Owner: mod_uckkchallenge
Install path: mod/uckkchallenge/db/install.xml
Upgrade path: mod/uckkchallenge/db/upgrade.php
Classes required: mod_uckkchallenge\external\*, mod_uckkchallenge\event\*, mod_uckkchallenge\privacy\provider
Events: challenge_viewed, submission_created, submission_updated, submission_validated, submission_archived
Privacy: export/delete/anonymise submissions, feedback, files, and user references
Tests: lib callbacks, repository/service methods, privacy provider, Behat submission workflow
```

## 8. `mod_uckkassembly` tables

### `uckkassembly`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | Instance id |
| course | BIGINT | Course id |
| name | VARCHAR(255) | Assembly name |
| intro | LONGTEXT | Moodle intro |
| introformat | SMALLINT | Moodle intro format |
| assemblytype | VARCHAR(64) | `savoirs`, `defis`, `joueurs`, `batisseurs`, `inquisiteurs`, `grand_jeu` |
| scope | VARCHAR(64) | `course`, `cohort`, `program`, `public` |
| rules | LONGTEXT | Procedure |
| decisionmethod | VARCHAR(64) | `consensus`, `vote`, `smart_reading`, `mentor_decision` |
| status | VARCHAR(64) | `planned`, `open`, `deliberation`, `decision`, `archived` |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

### `uckkassembly_motion`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| assemblyid | BIGINT | FK |
| proposerid | BIGINT | User |
| title | VARCHAR(255) | Motion title |
| body | LONGTEXT | Motion |
| status | VARCHAR(64) | `submitted`, `accepted`, `amended`, `rejected`, `decided` |
| decisionid | BIGINT NULL | FK |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

### `uckkassembly_decision`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| assemblyid | BIGINT | FK |
| motionid | BIGINT | FK |
| decisiontext | LONGTEXT | Decision |
| reasoning | LONGTEXT | Rationale |
| minorityreport | LONGTEXT NULL | Minority record |
| contestableuntil | BIGINT NULL | Appeal window |
| archiveitemid | BIGINT NULL | Archive link |
| createdby | BIGINT | User who recorded decision |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: mod_uckkassembly
Install path: mod/uckkassembly/db/install.xml
Upgrade path: mod/uckkassembly/db/upgrade.php
Classes required: mod_uckkassembly\external\*, mod_uckkassembly\event\*, mod_uckkassembly\privacy\provider
Events: motion_submitted, motion_updated, vote_cast, decision_recorded, decision_archived
Privacy: export/delete/anonymise participant content where policy allows; retain institutional decisions with attribution rules
Tests: motion/decision services, state transitions, privacy provider, Behat assembly workflow
```

## 9. `mod_uckkarchive` tables

### `uckkarchive`

Standard Moodle module instance table.

Required Moodle-style fields:

```text
id
course
name
intro
introformat
timecreated
timemodified
```

Additional UCKK archive configuration fields may be added only when they are stable and reportable. Variable display settings belong in module config only if they are not used for access control, reporting, privacy export, or state transitions.

### `uckkarchive_item`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| archiveid | BIGINT | Module instance |
| contextid | BIGINT | Access/privacy context |
| itemtype | VARCHAR(64) | `proof`, `decision`, `kristal`, `portfolio_item`, `minutes` |
| title | VARCHAR(255) | Title |
| summary | LONGTEXT | Summary |
| body | LONGTEXT | Content |
| owneruserid | BIGINT NULL | User owner |
| sourcecomponent | VARCHAR(100) | Origin |
| sourceitemid | BIGINT NULL | Origin id |
| validationstate | VARCHAR(64) | `draft`, `reviewed`, `validated`, `contested`, `invalidated` |
| visibility | VARCHAR(64) | `private`, `cohort`, `course`, `public` |
| provenancehash | VARCHAR(128) NULL | Integrity/provenance hash |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

### `uckkarchive_version`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| itemid | BIGINT | Archive item |
| versionno | BIGINT | Version |
| body | LONGTEXT | Snapshot |
| changedby | BIGINT | User |
| changereason | LONGTEXT | Reason |
| timecreated | BIGINT | Created |

Implementation contract:

```text
Owner: mod_uckkarchive
Install path: mod/uckkarchive/db/install.xml
Upgrade path: mod/uckkarchive/db/upgrade.php
Classes required: mod_uckkarchive\form\*, mod_uckkarchive\external\*, mod_uckkarchive\event\*, mod_uckkarchive\privacy\provider
Events: archive_viewed, archive_item_created, archive_item_updated, archive_item_validated, archive_item_contested, archive_item_exported
Privacy: export/delete/anonymise owned items, versions, files, and restricted evidence according to retention rules
Tests: lib callbacks, file API, provenance, external services, privacy provider, Behat archive workflow
```

## 10. `tool_uckkintegrity` tables

### `tool_uckkintegrity_case`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| casetype | VARCHAR(100) | Case type |
| subjectcomponent | VARCHAR(100) | Related plugin |
| subjectid | BIGINT | Related object |
| contextid | BIGINT | Case context |
| openedby | BIGINT | User |
| assignedto | BIGINT NULL | Inquisiteur |
| severity | VARCHAR(64) | `low`, `normal`, `high`, `critical` |
| status | VARCHAR(64) | `opened`, `triaged`, `under_review`, `waiting_for_response`, `correction_required`, `resolved`, `dismissed`, `escalated`, `archived` |
| summary | LONGTEXT | Summary |
| decision | LONGTEXT NULL | Decision |
| archiveitemid | BIGINT NULL | Archive link |
| timecreated | BIGINT | Created time |
| timemodified | BIGINT | Modified time |

### `tool_uckkintegrity_note`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| caseid | BIGINT | FK |
| userid | BIGINT | Author |
| notetype | VARCHAR(64) | `observation`, `evidence`, `response`, `decision` |
| body | LONGTEXT | Note |
| visibility | VARCHAR(64) | `restricted`, `parties`, `public_summary` |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: tool_uckkintegrity
Install path: admin/tool/uckkintegrity/db/install.xml
Upgrade path: admin/tool/uckkintegrity/db/upgrade.php
Classes required: tool_uckkintegrity\form\*, tool_uckkintegrity\external\*, tool_uckkintegrity\event\*, tool_uckkintegrity\privacy\provider
Events: case_opened, case_assigned, note_created, decision_recorded, case_closed
Privacy: sensitive data; restricted export and deletion with retention controls
Tests: case repository, capability checks, privacy provider, Behat case workflow
```

## 11. `tool_uckkseed` tables

### `tool_uckkseed_log`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| userid | BIGINT | Admin/operator user |
| action | VARCHAR(64) | `seed`, `reset`, `validate`, `export_preset` |
| mode | VARCHAR(64) | `dryrun`, `execute` |
| status | VARCHAR(64) | `started`, `completed`, `failed` |
| summary | LONGTEXT | Human summary |
| metadata | LONGTEXT NULL | JSON execution detail |
| timecreated | BIGINT | Execution time |

Implementation contract:

```text
Owner: tool_uckkseed
Install path: admin/tool/uckkseed/db/install.xml
Upgrade path: admin/tool/uckkseed/db/upgrade.php
Classes required: tool_uckkseed\local\*, tool_uckkseed\form\*, tool_uckkseed\privacy\provider
Events: seed_started, seed_completed, seed_failed, reset_started, reset_completed, validation_completed
Privacy: export/delete admin execution logs by userid and retention period
Tests: idempotent seed, dry run, reset scoping, privacy provider
```

## 12. `aiprovider_uckk` tables

### `aiprovider_uckk_log`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| userid | BIGINT | Requesting user |
| contextid | BIGINT | Originating context |
| actionname | VARCHAR(100) | AI action |
| model | VARCHAR(100) | Provider model |
| prompttext | LONGTEXT NULL | Prompt sent, if logging enabled |
| responsetext | LONGTEXT NULL | Response stored, if logging enabled |
| status | VARCHAR(64) | `requested`, `completed`, `failed`, `redacted`, `deleted` |
| metadata | LONGTEXT NULL | JSON technical detail |
| timecreated | BIGINT | Created time |

Implementation contract:

```text
Owner: aiprovider_uckk
Install path: ai/provider/uckk/db/install.xml
Upgrade path: ai/provider/uckk/db/upgrade.php
Classes required: aiprovider_uckk\privacy\provider and provider/service classes required by Moodle AI integration
Events: ai_request_created, ai_request_completed, ai_request_failed, ai_log_deleted
Privacy: export/delete/redact prompts and responses by userid and retention settings
Tests: provider behavior, redaction, retention cleanup, privacy provider
```

AI storage constraints:

```text
[ ] AI output is never stored as final authority.
[ ] AI logs must preserve originating contextid.
[ ] Prompt and response logging must be configurable.
[ ] Redaction settings must affect stored data, not only display output.
[ ] Integrity, grading, validation, and sanction workflows cannot delegate final decisions to AI.
```

## 13. File API areas

| Component | File area | Purpose | Required access check |
|---|---|---|---|
| `mod_uckkchallenge` | `submission` | Challenge proof files | Submitter/mentor/grader/manager by module context |
| `mod_uckkchallenge` | `feedback` | Mentor feedback attachments | Submitter and authorised reviewers only |
| `mod_uckkassembly` | `motion` | Motion attachments | Assembly participant or manager by module context |
| `mod_uckkassembly` | `minutes` | Minutes and decision files | Assembly visibility + decision publication state |
| `mod_uckkassembly` | `smartvote_snapshot` | Optional connected-mode Smart Vote snapshot attachments or preserved source packages | Assembly Smart Vote visibility + archive/redaction rules |
| `mod_uckkarchive` | `item` | Archive item files | Archive item visibility + provenance state |
| `mod_uckkarchive` | `version` | Versioned snapshots | Same as parent item, with restricted invalidated/contested handling |
| `mod_uckkarchive` | `smartvote_archive` | Optional archived Smart Vote reading packages | Archive visibility + provenance + contestation state |
| `tool_uckkintegrity` | `case` | Restricted case evidence | Inquisiteur/restricted parties only |
| `local_uckk` | `profile` | Optional profile artifacts | Owner or authorised viewer only |
| `aiprovider_uckk` | none by default | AI logs are DB text, not files | Add file area only with privacy contract |

File API contract:

```text
[ ] Each file area has a *_pluginfile() handler or documented Moodle subsystem handler.
[ ] Each handler validates context, itemid, filearea, capability, and visibility.
[ ] Files linked to personal data are included in privacy export/delete.
[ ] Tests cover allowed and denied access.
```

## 14. State machines

### Generic visibility states

```text
private -> cohort -> course -> public
public -> course -> cohort -> private
```

Visibility changes must be logged by event when they affect access or publication.

### Archive validation states

```text
draft -> reviewed -> validated
draft -> reviewed -> contested
validated -> contested -> reviewed -> validated
contested -> invalidated
```

### Challenge submission states

```text
draft -> submitted -> review -> revision -> submitted
review -> validated
validated -> archived
submitted -> archived
```

### Assembly decision states

```text
planned -> open -> deliberation -> decision -> archived
```

### Integrity case states

```text
opened -> triaged -> under_review -> waiting_for_response -> under_review
under_review -> correction_required -> under_review
under_review -> resolved -> archived
under_review -> dismissed -> archived
under_review -> escalated -> archived
```

### Optional Konnaxion sync states

```text
queued -> running -> succeeded
queued -> running -> failed -> retry_waiting -> queued
queued -> skipped
queued -> disabled
running -> failed
failed -> retry_waiting
```

### Optional Konnaxion mapping states

```text
draft -> active
active -> suspended -> active
active -> superseded -> archived
active -> invalidated
suspended -> archived
```

### Optional Smart Vote target states

```text
draft -> mapped -> active -> closed -> archived
active -> contested
contested -> active
contested -> archived
```

### Optional Smart Vote snapshot states

```text
imported -> under_review -> accepted_as_reading
imported -> under_review -> contested
accepted_as_reading -> archived
accepted_as_reading -> superseded
contested -> under_review
contested -> invalidated
```

### Optional Smart Vote audit states

```text
recorded -> reviewed -> resolved
recorded -> contested -> reviewed -> resolved
reviewed -> corrected -> resolved
```

Every transition requires:

```text
[ ] A capability check.
[ ] A context check.
[ ] A repository/service method.
[ ] An event emission.
[ ] A PHPUnit test.
[ ] A Behat scenario when user-facing.
```

## 15. Data integrity rules

```text
[ ] Every archive item must have provenance.
[ ] Every validated challenge must have at least one proof record or a documented no-file evidence policy.
[ ] Every assembly decision must have a motion and decision record.
[ ] Every integrity decision must have a case record and decision note.
[ ] Every user-facing status must be stored, not inferred only from UI.
[ ] Every important change must emit an event.
[ ] Every personal-data table must be included in privacy export and deletion/anonymisation.
[ ] Every foreign-key-like field must be indexed.
[ ] Every cross-plugin reference must tolerate the target plugin being disabled or missing unless declared as a dependency.
[ ] Every seed-created record must be idempotently identifiable by stable key.
[ ] Standalone mode must tolerate empty or absent connected-mode records.
[ ] Konnaxion must never write directly into Moodle source tables.
[ ] Konnaxion mappings must preserve external identifiers without storing secrets.
[ ] Smart Vote snapshots must distinguish raw data, reading method, computed reading, human/institutional decision reference, minority report, integrity warning, contestation status, and archive linkage.
[ ] Smart Vote snapshots must not overwrite `uckkassembly_decision` or publish final decisions.
```

## 16. Install and upgrade rules

```text
[ ] db/install.xml creates only this plugin's own tables.
[ ] db/install.xml validates with Moodle XMLDB tools.
[ ] db/upgrade.php is self-contained and has no rendering, service calls, page setup, or workflow logic.
[ ] Every schema change is guarded by table/field/index existence checks.
[ ] Every upgrade step ends with upgrade_plugin_savepoint().
[ ] Data migrations are safe to re-run after partial failure.
[ ] Fresh install and upgrade-from-previous-version both produce the same final schema.
[ ] Connected-mode tables are created by their owning plugins only.
[ ] Konnaxion bridge disabled state does not block install or upgrade.
[ ] Empty connected-mode tables do not cause seed, report, privacy, or archive failures.
```

No plugin may create another plugin's tables. Cross-plugin data must be created through seed/repository APIs after all dependent plugins are installed.

## 17. Privacy and retention rules

```text
[ ] Each plugin has classes/privacy/provider.php.
[ ] Null providers are allowed only when the plugin truly stores no personal data.
[ ] Every userid-like field is mapped in privacy metadata.
[ ] Every authored LONGTEXT field is exported or explicitly retained/anonymised by policy.
[ ] Every file area containing personal data participates in privacy export/delete.
[ ] Integrity cases and archive records may use retention rules, but the rule must be explicit.
[ ] AI logs must respect redaction and retention settings.
[ ] Seed logs must have retention cleanup.
[ ] Konnaxion identity mappings are identity-linking personal data and require privacy export/delete/anonymisation rules.
[ ] Konnaxion object mappings require provenance and lifecycle retention rules.
[ ] Konnaxion sync logs require redaction, secret exclusion, and retention cleanup.
[ ] Smart Vote snapshots require privacy classification before display, export, report, archive, or AI use.
[ ] Absence of a Smart Vote reading is valid standalone provenance, not a missing record.
```

Privacy providers required by storage:

```text
local/uckk/classes/privacy/provider.php
mod/uckkchallenge/classes/privacy/provider.php
mod/uckkassembly/classes/privacy/provider.php
mod/uckkarchive/classes/privacy/provider.php
admin/tool/uckkintegrity/classes/privacy/provider.php
admin/tool/uckkseed/classes/privacy/provider.php
ai/provider/uckk/classes/privacy/provider.php
```

Privacy providers required to declare null/no-own-data posture when appropriate:

```text
theme/uckk/classes/privacy/provider.php
course/format/uckk/classes/privacy/provider.php
blocks/uckk_dashboard/classes/privacy/provider.php
report/uckk/classes/privacy/provider.php
```

If any of those later stores personal data, the null posture must be replaced by a full provider.

## 18. Definition of done

The data layer has two acceptance gates.

### 18.1 Core standalone acceptance

```text
[ ] install.xml validates for every core plugin.
[ ] upgrade.php covers schema evolution for every core plugin.
[ ] All foreign-key-like fields are indexed.
[ ] All core status fields have documented state machines.
[ ] File areas are declared, served, permission-checked, and tested.
[ ] Privacy export and deletion/anonymisation are implemented for core tables.
[ ] Seed data can be created idempotently without Konnaxion.
[ ] Reports can query all required institutional objects without Smart Vote snapshots.
[ ] External services have matching classes/external implementations.
[ ] Events have matching classes/event implementations.
[ ] Scheduled tasks have matching classes/task implementations.
[ ] PHPUnit covers repository/service/privacy behavior.
[ ] Behat covers major standalone user workflows.
[ ] A clean Moodle install can install the package without warnings.
[ ] UCKK-Moodle remains functional when Konnaxion bridge and Smart Vote are disabled.
```

### 18.2 Optional Konnaxion-connected acceptance

```text
[ ] Connected-mode tables use canonical names: local_uckk_kx_user_map, local_uckk_kx_object_map, local_uckk_kx_sync_log, uckkassembly_kx_vote_target, uckkassembly_sv_snapshot, uckkassembly_sv_result_audit.
[ ] Konnaxion identity/object mappings are privacy-covered and capability-checked.
[ ] Konnaxion sync logs redact secrets, avoid raw payload logging by default, and obey retention rules.
[ ] Konnaxion timeout, retry, failure, disabled, skipped, and idempotency states are tested.
[ ] Smart Vote target mappings require Assembly context and capability checks.
[ ] Smart Vote snapshots store reading data separately from final Assembly decisions.
[ ] Smart Vote result audit records review, correction, contestation, supersession, and archive linkage.
[ ] Smart Vote snapshots and audits can be archived with provenance and contestability.
[ ] Reports can show Smart Vote readings only with permission filtering and clear non-authority labels.
[ ] Privacy export/delete/redaction covers connected-mode mappings, snapshots, audits, and logs.
[ ] Connected-mode absence or outage fails safely and does not fabricate or silently accept Smart Vote results.
```

## 19. First-pass correction checklist

Use this checklist before trying to start Moodle with the generated package:

```text
[ ] No PHP controller code exists in amd/src/*.js.
[ ] No JavaScript AMD module code exists in *.php page files.
[ ] No Markdown fences or prose instructions exist inside PHP source files.
[ ] Every version.php component matches its plugin path.
[ ] Every declared db/services.php classname exists under classes/external/.
[ ] Every db/events.php event class exists under classes/event/.
[ ] Every db/tasks.php task class exists under classes/task/.
[ ] Every form reference exists under classes/form/ or the correct Moodle form location.
[ ] Every privacy string has a matching privacy provider implementation.
[ ] php -l passes for all PHP files.
[ ] JSON presets validate.
[ ] install.xml files validate.
[ ] Moodle admin/cli/upgrade.php passes in a disposable development site.
[ ] Standalone install passes with Konnaxion bridge disabled.
[ ] Connected-mode table names match the canonical names in this document.
[ ] No old Konnaxion table names remain: local_uckk_konnaxion_identity_map, local_uckk_konnaxion_object_map, uckkassembly_smartvote_snapshot.
[ ] No Smart Vote table or service can overwrite `uckkassembly_decision`.
```
