# Release Acceptance Checklist

**Status:** Release gate checklist for UCKK-Moodle final code revision  
**Applies to:** UCKK-Moodle code snapshot and release package  
**Primary mode:** `standalone_core`  
**Optional mode:** `connected_konnaxion`

This file is the executable release checklist for the code-revision phase.

It separates:

```text
standalone_core = required for final core release
connected_konnaxion = optional connected-mode profile; required only when claimed as supported
```

A release may pass `standalone_core` while `connected_konnaxion` is marked `not_included`, provided no UI, docs, seed preset, release note, or manifest claims that Konnaxion/Smart Vote is operational.

## 0. Release decision summary

Fill this block before release sign-off.

```text
Release version:
Snapshot/source revision:
Moodle target version:
PHP target version:
Database target:
Reviewer:
Review date:

Core standalone status: pass / fail
Connected Konnaxion status: not_included / included_pass / included_fail
Final release decision: pass / fail
```

## 1. Governing release rules

```text
UCKK-Moodle is self-standing.
Konnaxion is optional connected-mode integration.
Smart Vote is a reading, not an automatic decision.
Moodle capabilities remain authoritative.
External systems never write directly into Moodle source tables.
AI outputs are assistive only.
```

Required registry alignment:

```text
DOC_00 = docs/00_master_execution_doctrine.md
DOC_11 = docs/11_cross_doc_alignment_registry.md
OPERATING_MODE_STANDALONE = standalone_core
OPERATING_MODE_KONNAXION_CONNECTED = connected_konnaxion
KONNAXION_REQUIRED_FOR_CORE = false
SMART_VOTE_REQUIRED_FOR_CORE = false
```

## 2. Release package completeness

The release package must contain:

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

Checklist:

```text
[ ] release/acceptance-checklist.md exists.
[ ] release/installation.md exists or is explicitly deferred.
[ ] release/upgrade.md exists or is explicitly deferred.
[ ] release/rollback.md exists or is explicitly deferred.
[ ] README.md is UTF-8 Markdown and not null-byte/UTF-16 corrupted.
[ ] CHANGELOG.md exists or is explicitly deferred.
[ ] LICENSE.md exists or is explicitly deferred.
[ ] docs/11_cross_doc_alignment_registry.md exists and is current.
[ ] docs/12_current_code_snapshot_gap_report.md exists before final sign-off.
```

## 3. Static filetype blockers

These checks are required for `standalone_core`.

### 3.1 PHP files

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

Pass criteria:

```text
[ ] Every PHP file parses successfully.
[ ] Every PHP source file starts with <?php unless it is an intentional mixed PHP/HTML layout file.
[ ] No PHP file contains Markdown fences or generator notes.
[ ] No PHP file contains AMD JavaScript module bodies misplaced in PHP pages.
```

Known current-snapshot blockers to clear:

```text
[ ] admin/tool/uckkintegrity/case.php starts with <?php and contains valid PHP/page code only.
[ ] admin/tool/uckkintegrity/review.php starts with <?php or is replaced/moved if it is actually AMD JavaScript.
[ ] blocks/uckk_dashboard/lang/en/block_uckk_dashboard.php starts with <?php.
[ ] blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php starts with <?php.
```

### 3.2 AMD JavaScript files

```bash
grep -RIn --include='*.js' '<?php\|require_once\|optional_param\|required_param\|require_login\|require_capability' . || true
```

Pass criteria:

```text
[ ] amd/src/*.js files contain JavaScript only.
[ ] No executable PHP, Moodle page-controller code, require_once, optional_param, required_param, require_login, or require_capability appears in amd/src/*.js.
[ ] PHP examples in comments are removed or rewritten as neutral documentation comments.
```

Known current-snapshot blockers to clear:

```text
[ ] admin/tool/uckkintegrity/amd/src/case.js contains JavaScript only.
[ ] admin/tool/uckkintegrity/amd/src/review.js contains JavaScript only.
```

Known current-snapshot comment drift to clear or explicitly waive:

```text
[ ] blocks/uckk_dashboard/amd/src/refresh.js has no PHP controller examples in comments.
[ ] course/format/uckk/amd/src/courseformat.js has no PHP controller examples in comments.
[ ] course/format/uckk/amd/src/sectionnav.js has no PHP controller examples in comments.
[ ] local/uckk/amd/src/pathway.js has no PHP controller examples in comments.
[ ] mod/uckkarchive/amd/src/archive.js has no PHP controller examples in comments.
[ ] mod/uckkarchive/amd/src/export.js has no PHP controller examples in comments.
[ ] mod/uckkarchive/amd/src/kristal.js has no PHP controller examples in comments.
[ ] mod/uckkassembly/amd/src/assembly.js has no PHP controller examples in comments.
[ ] mod/uckkassembly/amd/src/minutes.js has no PHP controller examples in comments.
[ ] mod/uckkassembly/amd/src/motion.js has no PHP controller examples in comments.
[ ] mod/uckkassembly/amd/src/vote.js has no PHP controller examples in comments.
[ ] mod/uckkchallenge/amd/src/evaluation.js has no PHP controller examples in comments.
[ ] mod/uckkchallenge/amd/src/proof.js has no PHP controller examples in comments.
[ ] theme/uckk/amd/src/frontpage.js has no PHP controller examples in comments.
```

### 3.3 Mustache templates

```bash
grep -RIn --include='*.mustache' '<?php\|require_once\|class .*extends\|namespace ' . || true
```

Pass criteria:

```text
[ ] Mustache files contain Mustache templates only.
[ ] No PHP classes, restore tasks, controllers, or executable code appear in templates.
```

Known current-snapshot blocker to clear:

```text
[ ] mod/uckkarchive/templates/kristal_card.mustache contains Mustache template content only.
[ ] Any PHP restore-task content is located under mod/uckkarchive/backup/moodle2/restore_uckkarchive_activity_task.class.php or another correct PHP class file.
```

### 3.4 JSON, XML, CSS, SCSS

```bash
find . -name '*.json' -print0 | xargs -0 -n1 python3 -m json.tool >/dev/null
find . -name 'install.xml' -print0 | xargs -0 -n1 xmllint --noout
grep -RIn --include='*.css' --include='*.scss' '<?php\|require_once\|\$PAGE' . || true
```

Pass criteria:

```text
[ ] All JSON files validate.
[ ] All install.xml files validate as XML.
[ ] CSS/SCSS files contain style code only.
```

## 4. Moodle component identity

Every plugin must declare a component matching its physical path.

```text
theme/uckk                    => theme_uckk
course/format/uckk            => format_uckk
local/uckk                    => local_uckk
blocks/uckk_dashboard         => block_uckk_dashboard
mod/uckkchallenge             => mod_uckkchallenge
mod/uckkassembly              => mod_uckkassembly
mod/uckkarchive               => mod_uckkarchive
admin/tool/uckkseed           => tool_uckkseed
admin/tool/uckkintegrity      => tool_uckkintegrity
report/uckk                   => report_uckk
ai/provider/uckk              => aiprovider_uckk
```

Checklist:

```text
[ ] Every version.php has the correct $plugin->component.
[ ] Every @package tag matches the component or is corrected.
[ ] Every language file component name matches the plugin.
[ ] Every capability prefix matches the plugin component.
[ ] Every service component matches the plugin component.
[ ] Every event namespace matches the plugin component.
[ ] Every AMD module name matches the plugin component.
[ ] Every template name matches the plugin component.
[ ] Every privacy provider namespace matches the plugin component.
```

Known current-snapshot blocker to clear:

```text
[ ] mod/uckkarchive/version.php declares $plugin->component = 'mod_uckkarchive'.
```

## 5. Declared class existence

The release fails if a page, service, event, task, form, output renderer, local class, or test references a class that does not exist.

Required checks:

```text
[ ] Every db/services.php classname maps to an existing classes/external/*.php class.
[ ] Every db/tasks.php classname maps to an existing classes/task/*.php class.
[ ] Every db/events.php observer maps to an existing callback/class where applicable.
[ ] Every triggered event class exists under classes/event/.
[ ] Every referenced form class exists under classes/form/ or a valid legacy form path.
[ ] Every referenced output class exists under classes/output/.
[ ] Every referenced local/service/api class exists under classes/local/, classes/service/, or classes/api/.
```

Current-snapshot class-creation groups to resolve:

```text
[ ] mod/uckkarchive referenced event/external/form/local/output classes exist.
[ ] mod/uckkassembly referenced completion/event/external/local/output classes exist.
[ ] mod/uckkchallenge referenced event/external/local/output/task classes exist.
[ ] local_uckk referenced api/event/external/local/service/task classes exist.
[ ] tool_uckkseed referenced form/local/output classes exist.
[ ] tool_uckkintegrity referenced external/form/local/output classes exist.
[ ] block_uckk_dashboard referenced output classes exist.
[ ] format_uckk referenced local classes exist.
[ ] report_uckk referenced local/output classes exist.
[ ] aiprovider_uckk referenced provider/local classes exist.
```

## 6. Privacy provider gate

Every plugin that stores, displays, exports, logs, derives, or aggregates personal data must include a privacy provider.

Checklist:

```text
[ ] theme/uckk/classes/privacy/provider.php exists or is explicitly metadata-only with null provider.
[ ] course/format/uckk/classes/privacy/provider.php exists.
[ ] local/uckk/classes/privacy/provider.php exists.
[ ] blocks/uckk_dashboard/classes/privacy/provider.php exists.
[ ] mod/uckkchallenge/classes/privacy/provider.php exists.
[ ] mod/uckkassembly/classes/privacy/provider.php exists.
[ ] mod/uckkarchive/classes/privacy/provider.php exists.
[ ] admin/tool/uckkseed/classes/privacy/provider.php exists.
[ ] admin/tool/uckkintegrity/classes/privacy/provider.php exists.
[ ] report/uckk/classes/privacy/provider.php exists.
[ ] ai/provider/uckk/classes/privacy/provider.php exists.
```

Provider behavior checklist:

```text
[ ] Metadata declaration is implemented.
[ ] User data export is implemented or explicitly empty where no personal data is stored.
[ ] Delete/anonymise behavior is implemented or explicitly empty where no personal data is stored.
[ ] Retention/redaction behavior is documented where records are institutional.
[ ] Archive-retention exceptions are documented where applicable.
[ ] PHPUnit privacy provider tests exist or are explicitly deferred with reason.
```

## 7. Database and upgrade gate

Checklist:

```text
[ ] Every db/install.xml validates.
[ ] Every table name matches DOC_04 and DOC_11.
[ ] Every primary key/index is valid for Moodle XMLDB.
[ ] Every db/upgrade.php parses and is idempotent.
[ ] Upgrade does not duplicate tables, roles, capabilities, scheduled tasks, seed records, admin settings, or event registry rows.
[ ] Backup/restore code exists for activity modules where required.
[ ] Activity restore code is in PHP class files, not templates.
```

## 8. Capability and access-control gate

Standalone-core capabilities must match the current code and DOC_11.

Checklist:

```text
[ ] local/uckk capabilities match local/uckk/db/access.php and DOC_11.
[ ] mod/uckkassembly capabilities match mod/uckkassembly/db/access.php and DOC_11.
[ ] mod/uckkarchive capabilities match mod/uckkarchive/db/access.php and DOC_11.
[ ] mod/uckkchallenge capabilities match mod/uckkchallenge/db/access.php and DOC_11.
[ ] tool_uckkseed capabilities match admin/tool/uckkseed/db/access.php and DOC_11.
[ ] tool_uckkintegrity capabilities match admin/tool/uckkintegrity/db/access.php and DOC_11.
[ ] report_uckk capabilities match report/uckk/db/access.php and DOC_11.
[ ] aiprovider_uckk capabilities match ai/provider/uckk/db/access.php and DOC_11.
[ ] Deprecated aliases are absent from active code and presets.
[ ] Every page resolves context before checking capability.
[ ] Every write operation requires sesskey or valid external-service token.
[ ] No role label, symbolic title, external identifier, or Konnaxion value grants Moodle authority.
```

Deprecated aliases must appear only in DOC_11 or explicit migration notes.

## 9. Seed and preset gate

Standalone-core seed must not require Konnaxion.

Checklist:

```text
[ ] Core seed dry-run works without Konnaxion credentials.
[ ] Core seed apply works without Konnaxion credentials.
[ ] Core seed verify works without Konnaxion credentials.
[ ] Core seed rollback_plan works without Konnaxion credentials.
[ ] Core presets are valid JSON.
[ ] Runtime preset paths under admin/tool/uckkseed/presets/ are correct.
[ ] Repository mirror preset paths under uckk-presets/ are either synced or explicitly non-runtime mirrors.
[ ] Seed logs do not store secrets.
[ ] Seed idempotency prevents duplicates.
[ ] Konnaxion mapping presets are not required for standalone-core.
```

## 10. Standalone-core functional gate

These must pass without Konnaxion enabled.

```text
[ ] Moodle detects all plugins with correct component names.
[ ] Moodle upgrade completes without fatal error.
[ ] Moodle caches can be purged.
[ ] theme_uckk applies successfully.
[ ] format_uckk course loads.
[ ] local_uckk settings and pages load.
[ ] block_uckk_dashboard displays for authorized users.
[ ] mod_uckkchallenge can be created and viewed.
[ ] mod_uckkassembly can be created and viewed.
[ ] mod_uckkarchive can be created and viewed.
[ ] tool_uckkseed page loads.
[ ] tool_uckkintegrity index loads.
[ ] report_uckk index loads.
[ ] aiprovider_uckk settings page loads.
[ ] Joueur dashboard works.
[ ] Challenge proof submission workflow works or is explicitly deferred.
[ ] Challenge evaluation workflow works or is explicitly deferred.
[ ] Assembly motion workflow works or is explicitly deferred.
[ ] Assembly ordinary vote/readings workflow works or is explicitly deferred.
[ ] Assembly decision publication workflow works or is explicitly deferred.
[ ] Archive item/proof/provenance workflow works or is explicitly deferred.
[ ] Integrity case workflow works or is explicitly deferred.
[ ] Reports are visible only to authorized users.
```

## 11. AMD build gate

```bash
npx grunt amd
```

Checklist:

```text
[ ] AMD source files build.
[ ] Generated build files are produced where expected.
[ ] No AMD source contains PHP controller code.
[ ] No JavaScript failure breaks core page workflows.
[ ] JavaScript calls only declared external services.
[ ] Server-side permission checks remain authoritative.
```

## 12. PHPUnit gate

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

Standalone-core pass criteria:

```text
[ ] PHPUnit initializes.
[ ] Core tests pass for installed components.
[ ] Privacy provider tests pass or are explicitly deferred with reason.
[ ] Access-control allow/deny tests pass.
[ ] External service parameter validation tests pass.
[ ] Seed idempotency tests pass.
[ ] Archive/integrity/report tests pass or are explicitly deferred with reason.
[ ] No test requires Konnaxion credentials for standalone-core.
```

## 13. Behat gate

Recommended command pattern:

```bash
php admin/tool/behat/cli/init.php
php admin/tool/behat/cli/run.php --tags='@uckk'
```

Standalone-core pass criteria:

```text
[ ] Behat initializes.
[ ] Install/seed campus flow passes or is explicitly deferred.
[ ] Joueur dashboard scenario passes or is explicitly deferred.
[ ] Challenge scenario passes or is explicitly deferred.
[ ] Assembly ordinary decision scenario passes or is explicitly deferred.
[ ] Archive scenario passes or is explicitly deferred.
[ ] Integrity scenario passes or is explicitly deferred.
[ ] Report visibility scenario passes or is explicitly deferred.
[ ] AI non-authority warning scenario passes or is explicitly deferred.
[ ] No scenario requires Konnaxion credentials for standalone-core.
```

## 14. Backup/restore gate

Required for activity modules:

```text
[ ] mod_uckkchallenge backup and restore are valid or explicitly deferred.
[ ] mod_uckkassembly backup and restore are valid or explicitly deferred.
[ ] mod_uckkarchive backup and restore are valid.
[ ] Backup code is PHP only.
[ ] Restore code is PHP only.
[ ] No backup/restore PHP appears in Mustache templates.
[ ] Restored records preserve ownership, provenance, visibility, and privacy constraints.
```

## 15. Documentation alignment gate

Checklist:

```text
[ ] docs/11_cross_doc_alignment_registry.md is current.
[ ] docs/12_current_code_snapshot_gap_report.md lists all known blockers.
[ ] docs do not claim connected_konnaxion is implemented unless it is included and tested.
[ ] docs do not require Konnaxion for standalone-core.
[ ] DOC_10 path is docs/10_konnaxion_smart_vote_integration_contract.md.
[ ] OPTIONAL_KONNAXION_CONTRACT is not used as an active variable.
[ ] Deprecated capability aliases appear only in DOC_11 or migration notes.
[ ] Static grep gates distinguish executable PHP-in-JS from harmless deprecated-alias registry entries.
[ ] Release notes state whether connected_konnaxion is included, not included, or deferred.
```

## 16. Connected Konnaxion / Smart Vote gate

This section is required only when `connected_konnaxion` is included or claimed as supported.

If not included, mark:

```text
connected_konnaxion = not_included
```

and complete only the deferral checks below.

### 16.1 Deferral checks when not included

```text
[ ] No UI claims Konnaxion is operational.
[ ] No UI claims Smart Vote is operational.
[ ] No release manifest claims connected_konnaxion support.
[ ] Missing Konnaxion tables are documented as target_connected_mode, not core blockers.
[ ] Missing Smart Vote tables are documented as target_connected_mode, not core blockers.
[ ] Missing Konnaxion services/events/classes are documented as target_connected_mode, not core blockers.
[ ] Missing Smart Vote services/events/classes are documented as target_connected_mode, not core blockers.
```

### 16.2 Required checks when included

```text
[ ] Konnaxion bridge settings exist and are disabled by default.
[ ] Konnaxion API credentials use secure Moodle config handling.
[ ] Konnaxion health check works without exposing secrets.
[ ] Konnaxion health check fails safely when unconfigured.
[ ] Konnaxion user/object mapping tables exist with canonical names.
[ ] Konnaxion sync log table exists with canonical name.
[ ] Smart Vote target/snapshot/audit tables exist with canonical names.
[ ] Konnaxion services exist and match DOC_11.
[ ] Smart Vote services exist and match DOC_11.
[ ] Konnaxion events exist and match DOC_11.
[ ] Smart Vote events exist and match DOC_11.
[ ] Smart Vote request/import/review/contest/archive/report flows are capability-checked.
[ ] Smart Vote result appears as reading, not final decision.
[ ] Smart Vote snapshots preserve raw data reference, method, computed reading, provenance, visibility, and contestation status.
[ ] Konnaxion outage fails safely.
[ ] Konnaxion timeout, retry, failure, disabled-state, and idempotency behavior is tested.
[ ] Konnaxion/Smart Vote privacy export/delete/redaction is tested.
[ ] Connected-mode PHPUnit tests pass.
[ ] Connected-mode Behat tests pass.
```

## 17. Security and privacy sign-off

```text
[ ] No secrets appear in logs, reports, archives, events, AI prompts, AI responses, Behat output, or PHPUnit failure dumps.
[ ] No write operation is possible by GET alone.
[ ] All external services validate parameters and returns.
[ ] All file-serving paths check context, capability, visibility, and privacy.
[ ] Restricted integrity/archive data is never exposed to ordinary users.
[ ] AI outputs are labelled non-authoritative.
[ ] AI prompt/response logs are disabled by default or privacy-covered.
[ ] Data export/delete behavior is documented and tested.
```

## 18. Final sign-off table

| Gate | Required for standalone_core | Status | Notes |
|---|---:|---|---|
| Release package completeness | yes | pass / fail | |
| Static filetype blockers | yes | pass / fail | |
| Moodle component identity | yes | pass / fail | |
| Declared class existence | yes | pass / fail | |
| Privacy providers | yes | pass / fail | |
| Database/upgrade | yes | pass / fail | |
| Capability/access-control | yes | pass / fail | |
| Seed/presets | yes | pass / fail | |
| Standalone functional checks | yes | pass / fail | |
| AMD build | yes | pass / fail | |
| PHPUnit | yes | pass / fail | |
| Behat | yes | pass / fail | |
| Backup/restore | yes, for activity modules | pass / fail | |
| Documentation alignment | yes | pass / fail | |
| Connected Konnaxion/Smart Vote | only if claimed | not_included / pass / fail | |
| Security/privacy sign-off | yes | pass / fail | |

Final decision:

```text
[ ] PASS — standalone_core release is accepted.
[ ] PASS — connected_konnaxion profile is accepted.
[ ] PASS — connected_konnaxion is not included and is explicitly deferred.
[ ] FAIL — release is rejected pending listed fixes.
```

Reviewer notes:

```text

```
