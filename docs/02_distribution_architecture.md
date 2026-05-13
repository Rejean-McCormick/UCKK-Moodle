# 02 — Distribution Architecture

**Status:** Final technical architecture  
**Purpose:** Define the complete UCKK-Moodle distribution and how all plugins fit together.

## 1. Architectural principle

UCKK-Moodle is a coordinated plugin distribution installed on Moodle. It avoids unnecessary core modifications and uses Moodle extension points for each responsibility.

## 2. Distribution layers

```text
Layer 1 — Moodle Core
Layer 2 — Moodle native subsystems
Layer 3 — UCKK plugin suite
Layer 4 — UCKK seed data
Layer 5 — UCKK institutional workflows
Layer 6 — External integrations
```

## 3. Plugin suite

| Component | Moodle type | Main responsibility |
|---|---|---|
| `theme_uckk` | theme | Visual identity and layouts |
| `format_uckk` | course format | Standard UCKK course structure |
| `local_uckk` | local plugin | Domain registry and shared services |
| `block_uckk_dashboard` | block | Joueur dashboard |
| `mod_uckkchallenge` | activity module | Défis King Klown |
| `mod_uckkassembly` | activity module | Assemblées |
| `mod_uckkarchive` | activity module | Archives and evidence |
| `tool_uckkseed` | admin tool | Complete campus installation |
| `tool_uckkintegrity` | admin tool | Inquisiteur workflow |
| `report_uckk` | report plugin | Institutional reports |
| `aiprovider_uckk` | AI provider plugin | Governed AI integration |

## 4. Dependency graph

```text
theme_uckk
    └── no hard dependency except Moodle core

format_uckk
    └── local_uckk

block_uckk_dashboard
    ├── local_uckk
    ├── mod_uckkchallenge
    ├── mod_uckkassembly
    ├── mod_uckkarchive
    └── tool_uckkintegrity

mod_uckkchallenge
    ├── local_uckk
    ├── mod_uckkarchive
    └── tool_uckkintegrity

mod_uckkassembly
    ├── local_uckk
    ├── mod_uckkarchive
    └── tool_uckkintegrity

mod_uckkarchive
    └── local_uckk

tool_uckkseed
    ├── local_uckk
    ├── format_uckk
    ├── mod_uckkchallenge
    ├── mod_uckkassembly
    ├── mod_uckkarchive
    ├── block_uckk_dashboard
    ├── tool_uckkintegrity
    └── report_uckk

tool_uckkintegrity
    ├── local_uckk
    ├── mod_uckkarchive
    ├── mod_uckkchallenge
    └── mod_uckkassembly

report_uckk
    ├── local_uckk
    ├── mod_uckkchallenge
    ├── mod_uckkassembly
    ├── mod_uckkarchive
    └── tool_uckkintegrity

aiprovider_uckk
    └── local_uckk
```

## 5. Plugin naming and paths

Use Moodle component naming strictly.

```text
theme_uckk              → theme/uckk
format_uckk             → course/format/uckk
local_uckk              → local/uckk
block_uckk_dashboard    → blocks/uckk_dashboard
mod_uckkchallenge       → mod/uckkchallenge
mod_uckkassembly        → mod/uckkassembly
mod_uckkarchive         → mod/uckkarchive
tool_uckkseed           → admin/tool/uckkseed
tool_uckkintegrity      → admin/tool/uckkintegrity
report_uckk             → report/uckk
aiprovider_uckk         → ai/provider/uckk
```

## 6. Shared service layer

`local_uckk` owns shared services used by the rest of the suite.

Required service classes:

```text
local_uckk\service\program_service
local_uckk\service\pathway_service
local_uckk\service\profile_service
local_uckk\service\competency_service
local_uckk\service\badge_service
local_uckk\service\provenance_service
local_uckk\service\visibility_service
local_uckk\service\event_service
local_uckk\service\navigation_service
local_uckk\service\integrity_bridge
local_uckk\service\archive_bridge
```

## 7. Event strategy

All major UCKK actions must emit Moodle events.

Required event families:

```text
local_uckk\event\program_created
local_uckk\event\pathway_assigned
local_uckk\event\profile_updated

mod_uckkchallenge\event\challenge_created
mod_uckkchallenge\event\proof_submitted
mod_uckkchallenge\event\challenge_validated
mod_uckkchallenge\event\challenge_contested

mod_uckkassembly\event\assembly_created
mod_uckkassembly\event\motion_submitted
mod_uckkassembly\event\vote_recorded
mod_uckkassembly\event\decision_published
mod_uckkassembly\event\decision_contested

mod_uckkarchive\event\archive_item_created
mod_uckkarchive\event\archive_item_validated
mod_uckkarchive\event\archive_item_versioned

tool_uckkintegrity\event\case_opened
tool_uckkintegrity\event\case_reviewed
tool_uckkintegrity\event\correction_issued
tool_uckkintegrity\event\case_closed
```

## 8. UI rendering rule

Moodle UI must use Moodle output patterns:

```text
classes/output/*
templates/*.mustache
amd/src/*.js when interactivity is required
lang/en/*.php
lang/fr/*.php
```

Business logic must not live in templates or JavaScript. UI calls services and renderables.

## 9. External service rule

External APIs must be declared only when needed. They must be permission-checked, token-aware, privacy-aware, and documented.

Potential services:

```text
local_uckk_get_player_dashboard
local_uckk_get_pathway_map
mod_uckkchallenge_submit_proof
mod_uckkassembly_submit_motion
mod_uckkarchive_create_item
tool_uckkintegrity_open_case
report_uckk_get_summary
```

## 10. Build target

The final package must support:

```text
Moodle: 5.1 target
PHP: target version according to Moodle 5.1 requirements
Database: Moodle-supported DBs only
Languages: English and French strings
Theme: responsive
Tests: PHPUnit + Behat
Privacy: full provider coverage
Upgrade: all plugins upgrade-safe
```

## 11. Definition of done

```text
[ ] All plugin dependencies are declared in version.php.
[ ] All plugins install in a clean Moodle target.
[ ] Seed tool can create the complete campus.
[ ] Dashboard renders for Joueur, Mentor, Archiviste, Inquisiteur, Gestionnaire.
[ ] Activity modules appear in Moodle activity chooser.
[ ] Reports render with permission checks.
[ ] Privacy providers pass Moodle privacy checks.
[ ] No plugin stores data without schema, privacy, tests, and upgrade path.
```
