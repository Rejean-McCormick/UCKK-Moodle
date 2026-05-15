# 02 — Distribution Architecture

**Status:** Final technical architecture  
**Purpose:** Define the complete UCKK-Moodle distribution, physical plugin layout, dependency rules, install/test gates, and the boundary between standalone core operation and optional connected-mode integrations.

This document consumes `docs/11_cross_doc_alignment_registry.md` for shared variables, document paths, capability names, table names, operating modes, deprecated aliases, and standalone-vs-connected requirements.

## 1. Architectural principle

UCKK-Moodle is a coordinated plugin distribution installed on Moodle.

It avoids Moodle core modifications and uses Moodle extension points for each responsibility:

- plugin components;
- activity modules;
- course format;
- local shared services;
- admin tools;
- block dashboard;
- report plugin;
- AI provider plugin;
- Moodle events;
- Moodle external services;
- Moodle privacy providers;
- Moodle renderers, templates, AMD modules, and language strings.

Core Moodle files must not be patched unless a separate, explicit core-modification document exists.

## 2. Distribution layers

```text
Layer 1 — Moodle Core
Layer 2 — Moodle native subsystems
Layer 3 — UCKK plugin suite
Layer 4 — UCKK seed data
Layer 5 — UCKK institutional workflows
Layer 6 — Optional external integrations
```

Layer 3 plugins must be installable as normal Moodle plugins.  
Layer 4 seed data must be optional and repeatable.  
Layer 5 workflows must be implemented through permissions, events, services, scheduled tasks, and reports.  
Layer 6 integrations are optional adapters. They must never bypass Moodle permissions, privacy rules, or audit logs.

UCKK-Moodle has two operating modes:

```text
OPERATING_MODE_STANDALONE = standalone_core
OPERATING_MODE_KONNAXION_CONNECTED = connected_konnaxion
```

Standalone mode is the default and must be complete without any external integration. UCKK-Moodle must install, seed, teach, deliberate, archive, report, enforce integrity rules, and run tests without Konnaxion.

Konnaxion-connected mode is an optional Layer 6 profile. When enabled, it may add Smart Vote readings, EkoH/advisory signals, external participation context, and better cross-module organization. It must not become a hard dependency for standalone installation or core workflows.

Layer 6 adapters must fail closed: outage, timeout, disabled configuration, or missing external credentials must not fabricate results, silently accept external state, or break standalone UCKK workflows.

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

Each component must be internally consistent:

```text
plugin path     ↔ component name
version.php     ↔ component name
language file   ↔ component name
capabilities    ↔ component name
external funcs  ↔ component name
classes path    ↔ namespace
tests           ↔ component name
privacy provider ↔ component name
```

Example:

```text
mod/uckkarchive/version.php
    $plugin->component = 'mod_uckkarchive';
```

A plugin must never declare another plugin’s component name.

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

Dependencies must be declared in each plugin’s `version.php` when the plugin cannot operate without another UCKK component.

Konnaxion must not appear as a required plugin dependency for standalone mode. Konnaxion support is represented as optional settings, optional services, optional scheduled tasks, optional events, and optional tests inside the existing UCKK plugin suite.

If connected mode is enabled, `local_uckk` owns the Konnaxion bridge surface and other plugins consume it through Moodle-side services and events. If connected mode is disabled, the same plugins must continue to install and operate without Konnaxion credentials, mappings, Smart Vote snapshots, or Konnaxion sync logs.

Dependency declarations must be version-aware and upgrade-safe.

## 5. Plugin naming and physical paths

Use Moodle component naming strictly.

These are the canonical Moodle installation paths:

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

The development repository may contain packaging scripts, documentation, presets, and tools, but the installable plugin code must resolve to the paths above.

Do not invent alternate plugin roots such as:

```text
plugins/mod/uckkarchive
plugins/local/uckk
plugins/theme/uckk
```

unless those directories are only packaging/staging directories and are copied into the canonical Moodle paths during build.

## 6. Required plugin file structure

Each plugin must use Moodle’s expected structure for its type.

Minimum common structure:

```text
version.php
lang/en/*.php
lang/fr/*.php
db/access.php
db/install.xml              when the plugin owns tables
db/upgrade.php              when the plugin owns schema evolution
classes/
classes/privacy/provider.php
tests/
```

When relevant, plugins must also include:

```text
settings.php
lib.php
locallib.php
db/services.php
db/tasks.php
db/events.php
classes/event/*
classes/external/*
classes/form/*
classes/output/*
classes/task/*
amd/src/*.js
templates/*.mustache
styles.css or scss/*
tests/behat/*.feature
```

Generated code is not acceptable until referenced classes exist.

A PHP file may reference a namespaced class only if the matching file exists under `classes/` or is provided by Moodle core.

Examples:

```text
mod_uckkarchive\form\archive_item_form
    → mod/uckkarchive/classes/form/archive_item_form.php

mod_uckkarchive\event\archive_item_created
    → mod/uckkarchive/classes/event/archive_item_created.php

mod_uckkarchive\external\create_item
    → mod/uckkarchive/classes/external/create_item.php

local_uckk\task\recalculate_pathways
    → local/uckk/classes/task/recalculate_pathways.php
```

## 7. Filetype correctness rule

Every file must contain code for its declared filetype.

Required rules:

```text
*.php       → PHP only; must start with <?php unless it is a template fragment intentionally included by PHP.
*.js        → JavaScript only; must not contain executable PHP, Moodle page-controller code, require_once, optional_param, required_param, PHP opening tags, or PHP variable usage.
*.mustache  → Mustache template only; no business logic.
*.json      → valid JSON only.
*.xml       → valid XMLDB-compatible XML where applicable.
*.scss      → SCSS only.
*.css       → CSS only.
```

Forbidden generated artifacts:

```text
Markdown fences inside PHP files.
Executable PHP controller code inside amd/src/*.js.
JavaScript AMD module bodies inside *.php page files.
Wrong copied component names in version.php.
Missing classes referenced by pages, services, tasks, forms, or tests.
```

This rule is an install blocker when executable code is misplaced. Literal references to Moodle PHP APIs inside documentation comments are documentation drift, not runtime PHP; remove or rewrite them during cleanup, but do not classify them as equivalent to executable PHP.

## 8. Shared service layer

`local_uckk` owns shared services used by the rest of the suite.

Core required service classes:

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

Optional Konnaxion-connected service classes:

```text
local_uckk\service\konnaxion_client
local_uckk\service\konnaxion_mapping_service
local_uckk\service\konnaxion_sync_service
```

These Konnaxion-connected services are required only when `OPERATING_MODE_KONNAXION_CONNECTED` is enabled. They must not block standalone installation, seed execution, course workflows, Assembly workflows, Archive workflows, Integrity workflows, reports, or tests when the bridge is disabled.

Shared services must not replace plugin ownership.

Example:

```text
local_uckk may define shared provenance logic.
mod_uckkarchive still owns archive tables, archive events, archive files, and archive privacy export/delete.
```

## 9. Event strategy

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

Each event must define:

```text
context
objectid when applicable
relateduserid when applicable
other data when required
crud type
edulevel
description
url when applicable
```

Events must not leak private evidence or hidden integrity information through public descriptions.

Optional Konnaxion-connected event families may be added when the connected profile is implemented:

```text
local_uckk\event\konnaxion_mapping_created
local_uckk\event\konnaxion_sync_completed
mod_uckkassembly\event\smart_vote_reading_requested
mod_uckkassembly\event\smart_vote_snapshot_imported
mod_uckkassembly\event\smart_vote_snapshot_contested
mod_uckkassembly\event\smart_vote_snapshot_archived
```

These events are connected-mode events. They must not be required for standalone Assembly decisions, ordinary votes/readings, minutes, archive records, integrity cases, or reports.

## 10. UI rendering rule

Moodle UI must use Moodle output patterns:

```text
classes/output/*
templates/*.mustache
amd/src/*.js when interactivity is required
lang/en/*.php
lang/fr/*.php
```

Business logic must not live in templates or JavaScript.

JavaScript may:

```text
enhance UI;
toggle panels;
refresh already-authorised data;
submit forms or AJAX requests to Moodle external services;
update accessibility state;
render returned templates.
```

JavaScript must not:

```text
decide permissions;
validate official evidence;
award badges;
change canonical archive state;
make integrity decisions;
calculate official pathway progress;
bypass sesskey or capability checks.
```

Templates must not:

```text
perform business decisions;
embed secrets;
expose hidden fields that grant authority;
assume data is visible without server-side filtering.
```

## 11. External service rule

External APIs must be declared only when needed.

Every function in `db/services.php` must have a matching implementation class under the component namespace.

Example:

```text
mod_uckkarchive_create_item
    → mod_uckkarchive\external\create_item
    → mod/uckkarchive/classes/external/create_item.php
```

External services must be:

```text
permission-checked;
context-aware;
sesskey/token-aware as appropriate;
privacy-aware;
parameter-validated;
return-value validated;
covered by PHPUnit;
documented.
```

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

No external service may trust client-side filtering, role labels, hidden form values, or JavaScript-only checks.

Optional Konnaxion-connected services may include:

```text
local_uckk_create_konnaxion_mapping
local_uckk_get_konnaxion_mapping
local_uckk_sync_konnaxion
mod_uckkassembly_request_smart_vote_reading
mod_uckkassembly_import_smart_vote_snapshot
mod_uckkassembly_contest_smart_vote_snapshot
report_uckk_get_smart_vote_report
```

These services must be disabled, hidden, or fail safely when connected mode is disabled. They must validate Moodle context, capability, sesskey/token requirements, privacy posture, external target state, and return values. They must not write directly into another plugin's source tables except through the owning plugin's documented Moodle-side service layer.

## 12. Privacy and data ownership

Every plugin that stores, derives, displays, exports, or processes personal data must include:

```text
classes/privacy/provider.php
```

Privacy providers must cover:

```text
user data export;
user data deletion;
context deletion;
metadata description;
linked subsystem data where applicable;
files and file areas;
events and audit implications where applicable.
```

Minimum privacy-provider coverage is required for:

```text
local_uckk
mod_uckkchallenge
mod_uckkassembly
mod_uckkarchive
tool_uckkintegrity
tool_uckkseed
block_uckk_dashboard
format_uckk
report_uckk
aiprovider_uckk
theme_uckk when it stores user preferences or personal configuration
```

No plugin may store personal data without:

```text
schema ownership;
privacy provider;
capability model;
upgrade path;
tests.
```

In connected mode, Konnaxion mappings, Smart Vote snapshots, sync logs, imported/advisory EkoH signals, and external identifiers are personal-data-bearing or privacy-sensitive whenever they can be linked to a Moodle user, Assembly, Archive item, Integrity case, report, or cohort. They require privacy export/delete/anonymisation rules, retention policy, redaction rules, and capability checks.

In standalone mode, absence of Konnaxion mappings, Smart Vote snapshots, and sync logs is valid system state and must not be treated as missing data.

## 13. Seed data and presets

Seed data is owned by `tool_uckkseed`.

Preset files may live in:

```text
uckk-presets/*
admin/tool/uckkseed/presets/*
```

The canonical import location for Moodle runtime is:

```text
admin/tool/uckkseed/presets/*
```

Seed data must be:

```text
valid JSON;
schema-versioned;
idempotent where possible;
safe to re-run;
auditable;
language-aware where relevant;
separable from plugin installation.
```

Installing the plugin suite and importing the UCKK campus seed are two different operations.

Plugin installation must not require seed import to complete.

Standalone seed import must not require Konnaxion credentials, Konnaxion mappings, Smart Vote snapshots, or external API access.

Optional connected-mode presets may include:

```text
presets/konnaxion_mappings.json
presets/state_machines.json entries for Konnaxion sync and Smart Vote snapshots
presets/capabilities.json entries for Konnaxion and Smart Vote capabilities
presets/events.json entries for connected-mode events
presets/exports.json entries for Smart Vote reports
presets/privacy_retention.json entries for connected-mode records
```

Connected-mode preset validation must be separate from standalone seed validation.

## 14. Build target

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
JavaScript: Moodle AMD build passes
Accessibility: keyboard and screen-reader usable
Standalone mode: complete without external integration credentials
Connected mode: optional Konnaxion bridge can be enabled, disabled, tested, and failed safely
```

## 15. Pre-install static gates

Before attempting Moodle installation, the repository must pass:

```text
PHP syntax check for all *.php files.
No PHP code inside *.js files.
No JavaScript AMD module bodies inside *.php page files.
No Markdown fences inside *.php files.
Valid JSON for all preset files.
Valid install.xml files.
Correct component names in every version.php.
All referenced classes exist.
All db/services.php declarations map to external classes.
All db/tasks.php declarations map to task classes.
All plugins with personal data include privacy providers.
No standalone-required plugin declares Konnaxion as a hard dependency.
Connected-mode classes, services, tasks, and events are guarded by configuration and capability checks.
```

Recommended commands:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 \
  | xargs -0 -n1 php -l

# Executable PHP/controller-code indicators in AMD JavaScript.
# Investigate matches manually: matches in documentation comments are cleanup items,
# while executable PHP/page-controller code is a blocker.
grep -RIn --include='*.js' '<?php\|require_once\|optional_param\|required_param' .
grep -RIn --include='*.js' '\$PAGE\|\$OUTPUT\|\$CFG\|require_login\|require_capability' .

grep -RIn --include='*.php' '```' .

find . -name 'install.xml' -print0 \
  | xargs -0 -n1 xmllint --noout
```

These gates are not optional.

## 16. Moodle install gates

After static gates pass, install only into a disposable Moodle development instance.

Required install checks:

```text
Moodle plugin discovery detects every plugin.
admin/cli/upgrade.php completes successfully.
admin/cli/purge_caches.php completes successfully.
Activity modules appear in the activity chooser.
Admin tools appear under Site administration.
Course format can be selected.
Theme can be selected.
Block can be added.
Report can be opened by an authorised user.
AI provider is discoverable when the Moodle AI subsystem is enabled.
Standalone campus workflows work while Layer 6 integrations are disabled.
Konnaxion bridge settings can remain disabled without installation warnings or fatal errors.
```

Recommended commands from Moodle root:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

## 17. Test gates

After install gates pass, run tests in this order:

```text
1. PHPUnit bootstrap
2. PHPUnit per plugin
3. JavaScript AMD build
4. Behat non-JavaScript scenarios
5. Behat JavaScript scenarios
6. Privacy provider tests
7. Seed import validation
8. Upgrade-path tests
9. Standalone-mode regression tests with Layer 6 disabled
10. Connected-mode tests only when Konnaxion bridge fixtures are enabled
```

Recommended PHPUnit targets:

```bash
php admin/tool/phpunit/cli/init.php

vendor/bin/phpunit mod/uckkarchive/tests
vendor/bin/phpunit mod/uckkassembly/tests
vendor/bin/phpunit mod/uckkchallenge/tests
vendor/bin/phpunit local/uckk/tests
vendor/bin/phpunit admin/tool/uckkseed/tests
vendor/bin/phpunit admin/tool/uckkintegrity/tests
vendor/bin/phpunit blocks/uckk_dashboard/tests
vendor/bin/phpunit course/format/uckk/tests
vendor/bin/phpunit report/uckk/tests
vendor/bin/phpunit ai/provider/uckk/tests
```

Recommended JavaScript build:

```bash
npx grunt amd
```

Recommended Behat sequence:

```bash
php admin/tool/behat/cli/init.php

php admin/tool/behat/cli/run.php --tags="@mod_uckkarchive"
php admin/tool/behat/cli/run.php --tags="@mod_uckkassembly"
php admin/tool/behat/cli/run.php --tags="@mod_uckkchallenge"
php admin/tool/behat/cli/run.php --tags="@local_uckk"
php admin/tool/behat/cli/run.php --tags="@tool_uckkintegrity"
php admin/tool/behat/cli/run.php --tags="@javascript"
```

Optional connected-mode Behat sequences must be tagged separately, for example:

```bash
php admin/tool/behat/cli/run.php --tags="@konnaxion"
php admin/tool/behat/cli/run.php --tags="@smartvote"
```

Those tags must not be part of the mandatory standalone test gate unless the release explicitly targets the connected profile.

## 18. Definition of done

### 18.1 Core standalone definition of done

```text
[ ] All standalone-required plugin dependencies are declared in version.php.
[ ] No standalone-required plugin declares Konnaxion as a hard dependency.
[ ] Every version.php component matches its plugin path.
[ ] No PHP file contains Markdown fences or non-PHP code.
[ ] No AMD JavaScript file contains executable PHP or Moodle page-controller code.
[ ] Every referenced standalone class exists under classes/.
[ ] Every standalone db/services.php function maps to a real external class.
[ ] Every standalone db/tasks.php task maps to a real task class.
[ ] All plugins install in a clean Moodle target with Layer 6 disabled.
[ ] Seed tool can create the complete standalone campus.
[ ] Dashboard renders for Joueur, Mentor, Archiviste, Inquisiteur, Gestionnaire.
[ ] Activity modules appear in Moodle activity chooser.
[ ] Reports render with permission checks.
[ ] Privacy providers pass Moodle privacy checks.
[ ] No plugin stores data without schema, privacy, tests, and upgrade path.
[ ] PHPUnit passes for each standalone plugin workflow.
[ ] Behat passes for core workflows.
[ ] Moodle AMD JavaScript build passes.
[ ] Upgrade from previous plugin versions is safe.
[ ] Konnaxion bridge disabled state does not break install, seed, courses, challenges, assemblies, archives, integrity, reports, privacy, or tests.
```

### 18.2 Optional Konnaxion-connected definition of done

```text
[ ] Konnaxion bridge is disabled safely by default.
[ ] Konnaxion bridge can be enabled and disabled from Moodle admin settings.
[ ] Konnaxion is integrated through Layer 6 adapters, not Moodle core patches.
[ ] Konnaxion does not write directly into Moodle source tables.
[ ] Connected-mode service classes exist for every enabled Konnaxion service.
[ ] Connected-mode event classes exist for every emitted Konnaxion or Smart Vote event.
[ ] Connected-mode scheduled tasks are configuration-guarded and fail closed.
[ ] Konnaxion credentials are optional for standalone mode and secret-safe in connected mode.
[ ] Smart Vote readings are presented as readings, not automatic Assembly decisions.
[ ] Smart Vote panels/actions are hidden or disabled when connected mode is disabled.
[ ] Konnaxion mappings, sync logs, Smart Vote snapshots, and Smart Vote reports are privacy-covered when connected mode is enabled.
[ ] Konnaxion outage, timeout, retry, and idempotency behavior is tested.
[ ] Connected-mode PHPUnit and Behat tags pass when Konnaxion fixtures are enabled.
```

## 19. Current implementation correction priority

The first correction pass must prioritise install and build blockers before feature completeness.

Priority order:

```text
1. Filetype correctness
   - PHP files contain PHP.
   - JS files contain JavaScript, not executable PHP or Moodle page-controller code.
   - Mustache files contain templates only.
   - PHP/Moodle API references inside JS comments are removed or rewritten to avoid grep false positives.

2. Component correctness
   - version.php component names match plugin paths.
   - package docblocks match component names.
   - capabilities and language strings use the correct component.

3. Class layer completion
   - forms
   - events
   - external services
   - output classes
   - scheduled tasks
   - privacy providers

4. Install correctness
   - install.xml
   - upgrade.php
   - version.php dependencies
   - admin/cli/upgrade.php

5. Test correctness
   - PHPUnit
   - Behat
   - AMD build
   - preset validation
   - privacy tests

6. Standalone/connected-mode boundary correctness
   - UCKK-Moodle installs and runs with Layer 6 disabled.
   - Konnaxion bridge settings are optional and disabled by default.
   - Smart Vote UI and services are hidden or fail safely when connected mode is disabled.
   - Connected-mode tests are separate from mandatory standalone gates.
```

The distribution is not considered runnable until priorities 1–4 pass.
