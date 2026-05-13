# 04 — Data Model and Storage

**Status:** Final data design  
**Purpose:** Define the cross-plugin UCKK-Moodle data model.

## 1. Common storage principles

All UCKK tables must use Moodle-compatible data definitions and upgrade paths.

Common columns for UCKK-owned tables:

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

`metadata` stores JSON only when the field is genuinely variable. Stable fields must be first-class columns.

## 2. Context strategy

| Object | Moodle context |
|---|---|
| Program registry | System or category context |
| Pathway assignment | User + category context |
| Course format data | Course context |
| Challenge instance | Module context |
| Challenge submission | Module context |
| Assembly instance | Module context |
| Motion / vote / decision | Module context |
| Archive item | Module context, course context, or user context depending on owner |
| Integrity case | System, course, module, or user context |
| Dashboard preference | User context |
| AI prompt/response logs | Context of originating action |

## 3. `local_uckk` tables

### `local_uckk_program`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| shortname | VARCHAR(100) | Stable program key |
| fullname | VARCHAR(255) | Display name |
| programtype | VARCHAR(64) | tronc_commun, baccalaureat, mineure, lab, seminar |
| categoryid | BIGINT | Linked Moodle category |
| description | LONGTEXT | Canonical description |
| status | VARCHAR(64) | active, hidden, archived |
| sortorder | BIGINT | Display order |

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
| status | VARCHAR(64) | active, archived |

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
| visibility | VARCHAR(64) | private, cohort, public |

### `local_uckk_provenance`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| component | VARCHAR(100) | Owning plugin |
| itemtype | VARCHAR(100) | Object type |
| itemid | BIGINT | Object id |
| sourcecomponent | VARCHAR(100) | Origin |
| sourceid | BIGINT NULL | Origin id |
| sourcetext | LONGTEXT NULL | Human source description |
| hash | VARCHAR(128) NULL | Integrity hash |
| state | VARCHAR(64) | draft, verified, contested, invalidated |

## 4. `mod_uckkchallenge` tables

### `uckkchallenge`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | Instance id |
| course | BIGINT | Moodle course id |
| name | VARCHAR(255) | Activity name |
| intro | LONGTEXT | Moodle intro |
| challengecode | VARCHAR(100) | Stable challenge code |
| challengetype | VARCHAR(64) | public, internal, evaluated, lab |
| statement | LONGTEXT | Challenge statement |
| rules | LONGTEXT | Rules |
| criteria | LONGTEXT | Evaluation criteria |
| evidencepolicy | LONGTEXT | Expected proof |
| corridors | LONGTEXT | JSON corridors |
| integrityrequired | TINYINT | Requires Inquisiteur review |
| archivepolicy | VARCHAR(64) | none, summary, full |
| timeopen | BIGINT | Open time |
| timeclose | BIGINT | Close time |
| status | VARCHAR(64) | Workflow state |

### `uckkchallenge_submission`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| challengeid | BIGINT | FK |
| userid | BIGINT | Submitter |
| groupid | BIGINT NULL | Team |
| title | VARCHAR(255) | Submission title |
| body | LONGTEXT | Submission text |
| proofsummary | LONGTEXT | Evidence summary |
| status | VARCHAR(64) | submitted, review, revision, validated |
| grade | DECIMAL NULL | Grade if used |
| mentorfeedback | LONGTEXT NULL | Feedback |
| integritycaseid | BIGINT NULL | Integrity case |
| archiveitemid | BIGINT NULL | Archive link |

## 5. `mod_uckkassembly` tables

### `uckkassembly`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | Instance id |
| course | BIGINT | Course id |
| name | VARCHAR(255) | Assembly name |
| assemblytype | VARCHAR(64) | savoirs, defis, joueurs, batisseurs, inquisiteurs, grand_jeu |
| scope | VARCHAR(64) | course, cohort, program, public |
| rules | LONGTEXT | Procedure |
| decisionmethod | VARCHAR(64) | consensus, vote, smart_reading, mentor_decision |
| status | VARCHAR(64) | planned, open, deliberation, decision, archived |

### `uckkassembly_motion`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| assemblyid | BIGINT | FK |
| proposerid | BIGINT | User |
| title | VARCHAR(255) | Motion title |
| body | LONGTEXT | Motion |
| status | VARCHAR(64) | submitted, accepted, amended, rejected, decided |
| decisionid | BIGINT NULL | FK |

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

## 6. `mod_uckkarchive` tables

### `uckkarchive`

Standard Moodle module instance table.

### `uckkarchive_item`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| archiveid | BIGINT | Module instance |
| itemtype | VARCHAR(64) | proof, decision, kristal, portfolio_item, minutes |
| title | VARCHAR(255) | Title |
| summary | LONGTEXT | Summary |
| body | LONGTEXT | Content |
| owneruserid | BIGINT NULL | User owner |
| sourcecomponent | VARCHAR(100) | Origin |
| sourceitemid | BIGINT NULL | Origin id |
| validationstate | VARCHAR(64) | draft, reviewed, validated, contested, invalidated |
| visibility | VARCHAR(64) | private, cohort, course, public |

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

## 7. `tool_uckkintegrity` tables

### `tool_uckkintegrity_case`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| casetype | VARCHAR(100) | Case type |
| subjectcomponent | VARCHAR(100) | Related plugin |
| subjectid | BIGINT | Related object |
| openedby | BIGINT | User |
| assignedto | BIGINT NULL | Inquisiteur |
| severity | VARCHAR(64) | low, normal, high, critical |
| status | VARCHAR(64) | opened, triaged, review, correction, closed |
| summary | LONGTEXT | Summary |
| decision | LONGTEXT NULL | Decision |
| archiveitemid | BIGINT NULL | Archive link |

### `tool_uckkintegrity_note`

| Field | Type | Purpose |
|---|---|---|
| id | BIGINT | PK |
| caseid | BIGINT | FK |
| userid | BIGINT | Author |
| notetype | VARCHAR(64) | observation, evidence, response, decision |
| body | LONGTEXT | Note |
| visibility | VARCHAR(64) | restricted, parties, public_summary |

## 8. File API areas

| Component | File area | Purpose |
|---|---|---|
| `mod_uckkchallenge` | `submission` | Challenge proof files |
| `mod_uckkchallenge` | `feedback` | Mentor feedback attachments |
| `mod_uckkassembly` | `motion` | Motion attachments |
| `mod_uckkassembly` | `minutes` | Minutes and decision files |
| `mod_uckkarchive` | `item` | Archive item files |
| `mod_uckkarchive` | `version` | Versioned snapshots |
| `tool_uckkintegrity` | `case` | Restricted case evidence |
| `local_uckk` | `profile` | Optional profile artifacts |

## 9. Data integrity rules

```text
[ ] Every archive item must have provenance.
[ ] Every validated challenge must have at least one proof record.
[ ] Every assembly decision must have a motion and decision record.
[ ] Every integrity decision must have a case record.
[ ] Every user-facing status must be stored, not inferred only from UI.
[ ] Every important change must emit an event.
[ ] Deletion must follow privacy and retention rules.
```

## 10. Definition of done

The data layer is done when:

```text
[ ] install.xml validates.
[ ] upgrade.php covers schema evolution.
[ ] All foreign keys are indexed.
[ ] All status fields have documented state machines.
[ ] File areas are declared and tested.
[ ] Privacy export and deletion are implemented.
[ ] Seed data can be created idempotently.
[ ] Reports can query all required institutional objects.
```
