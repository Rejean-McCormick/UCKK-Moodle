# 00 — UCKK-Moodle Master Execution Doctrine

**Status:** Final implementation doctrine  
**Target:** Moodle 5.1 adaptation for the Univers-Cité King Klown (UCKK)  
**Workflow rule:** Complete final version. No stubs. No multi-phase placeholders. No "later" features inside the target scope.

## 1. Purpose

This documentation set defines the coherent final adaptation of Moodle into the operational campus of UCKK.

The goal is not to decorate Moodle and not to fork Moodle unnecessarily. The goal is to deliver a complete, installable, governed Moodle distribution that expresses UCKK through Moodle-native extension points, complete plugins, seed data, roles, capabilities, activities, reports, archives, AI governance, and institutional safeguards.

## 2. Governing decision

UCKK-Moodle is the **campus implementation** of the Univers-Cité King Klown.

It is:

- a Moodle-based learning and governance environment;
- a complete pedagogical distribution;
- a set of coordinated Moodle plugins;
- an institutional seed package;
- a delivery contract for implementation teams.

It is not:

- the whole kOA movement;
- the whole kOA Digital Ecosystem;
- a generic LMS skin;
- a symbolic website only;
- a fork of Moodle core unless a core change is absolutely unavoidable;
- an experimental skeleton containing stubs.

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
```

Every UI label, plugin responsibility, data model, and permission must preserve the distinction.

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
│   └── 09_integrations_reporting_delivery.md
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
│   └── navigation.json
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

## 5. No-stub implementation rule

Every plugin in scope must be production-complete at delivery.

A plugin is not complete unless it contains:

```text
version.php
db/install.xml when storing data
db/upgrade.php when data schema may evolve
db/access.php for capabilities
lang/en/<component>.php
lang/fr/<component>.php
classes/
classes/privacy/provider.php when personal data is stored
settings.php when configurable
lib.php when Moodle plugintype requires it
amd/src or templates when UI requires it
tests/phpunit
tests/behat
README.md
```

A plugin may have intentionally disabled features only if the disabled behavior is a documented configuration option, not an unfinished stub.

## 6. Core strategy

Moodle core remains untouched unless all of the following are true:

1. no plugin type can implement the requirement;
2. no theme override can implement the UI requirement safely;
3. no course format override can implement the course behavior;
4. no admin tool, local plugin, report source, or web service can implement the cross-cutting behavior;
5. the change has a written justification and a rollback strategy.

## 7. Source anchors

The implementation is governed by two source families:

### UCKK canon

- `UCKK_Canon/00_index.md`
- `UCKK_Canon/01_glossaire.md`
- `UCKK_Canon/02_architecture-generale-kOA-UCKK-digital-ecosystem.md`
- `UCKK_Canon/20_UCKK-document-fondateur.md`
- `UCKK_Canon/22_UCKK-gouvernance-assemblees-inquisiteur.md`
- `UCKK_Canon/23_UCKK-defis-theatre-public.md`
- `UCKK_Canon/30_UCKK-catalogue-academique.md`
- `UCKK_Canon/31_UCKK-tronc-commun.md`
- `UCKK_Canon/42_UCKK-liste-et-fiches-de-cours.md`
- `UCKK_Canon/cours/*.md`

### Moodle developer documentation

- `versioned_docs/version-5.1/apis.md`
- `versioned_docs/version-5.1/apis/plugintypes/index.md`
- `versioned_docs/version-5.1/apis/plugintypes/local/index.mdx`
- `versioned_docs/version-5.1/apis/plugintypes/format/index.md`
- `versioned_docs/version-5.1/apis/plugintypes/mod/index.mdx`
- `versioned_docs/version-5.1/apis/plugintypes/theme/index.md`
- `versioned_docs/version-5.1/apis/plugintypes/blocks/index.md`
- `versioned_docs/version-5.1/apis/subsystems/access.md`
- `versioned_docs/version-5.1/apis/subsystems/privacy/index.md`
- `versioned_docs/version-5.1/apis/core/reportbuilder/index.md`
- `versioned_docs/version-5.1/apis/subsystems/ai/index.md`

## 8. Completion standard

The adaptation is complete only when a clean Moodle installation can be turned into UCKK-Moodle by installing the package and running the seed tool, producing:

- UCKK categories;
- tronc commun courses;
- internal programs;
- roles and capabilities;
- cohorts;
- competencies;
- badges;
- dashboards;
- challenge activity;
- assembly activity;
- archive activity;
- integrity system;
- reports;
- AI provider configuration;
- privacy providers;
- tests;
- documentation;
- acceptance evidence.

## 9. Non-negotiable final checks

The final implementation must pass these checks:

```text
[ ] Moodle core remains clean or every core change is justified.
[ ] Every plugin installs without warnings.
[ ] Every plugin has version metadata and dependency declarations.
[ ] Every table has install and upgrade support.
[ ] Every personal data store has a privacy provider.
[ ] Every capability is explicit and context-aware.
[ ] Every major workflow has Behat coverage.
[ ] Every data service has PHPUnit coverage.
[ ] The seed tool can create a full UCKK campus.
[ ] The seed tool can be re-run idempotently.
[ ] The UCKK / kOA / kOA Digital Ecosystem / King Klown distinction remains visible.
[ ] Badges and competencies are linked to evidence.
[ ] Challenges and assemblies are auditable.
[ ] The Inquisiteur cannot become an unrestricted super-admin by design.
[ ] AI outputs are never final authority.
[ ] Archives preserve provenance.
```

## 10. Primary formula

> UCKK-Moodle is the final Moodle campus of the Univers-Cité King Klown: pedagogical like Moodle, symbolic like UCKK, governable like kOA, traceable like an archive, and contestable like a healthy institution.
