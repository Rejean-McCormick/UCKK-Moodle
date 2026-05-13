# 05 — Roles, Permissions and Security

**Status:** Final access control specification  
**Purpose:** Define Moodle roles, capabilities, contexts, and security constraints for UCKK-Moodle.

## 1. Security principle

UCKK symbolic identities must not become uncontrolled Moodle roles.

Use Moodle roles only for permission groups. Use badges, profile titles, cohorts, and competencies for symbolic or pedagogical identities.

## 2. Technical roles

| Role | Purpose |
|---|---|
| Administrateur Moodle | Full Moodle administration |
| Gestionnaire UCKK | Manage UCKK configuration, programs, seed outputs, reports |
| Mentor UCKK | Teach, evaluate, guide challenges and assemblies |
| Joueur | Learn, submit proof, join assemblies, complete challenges |
| Archiviste UCKK | Validate, version, and manage archive items |
| Inquisiteur UCKK | Review integrity cases and issue corrections |
| Observateur | Restricted read-only participant |
| Invité public limité | Public-facing read-only access where allowed |

## 3. Symbolic titles

Do not create default Moodle roles for:

```text
Bâtisseur
Joueur lucide
Cartographe de systèmes
Architecte du sens
Architecte d’opportunités
Gardien des systèmes vivants
Gardien de la preuve
```

Represent these through:

```text
badges
profile fields
cohorts
competency states
portfolio titles
archive distinctions
```

## 4. Capability naming

Use Moodle component naming:

```text
local/uckk:viewcampus
local/uckk:manageprograms
local/uckk:managepathways
local/uckk:manageprofiles
local/uckk:managecanon
local/uckk:viewrestrictedprofile

format/uckk:configuresections
format/uckk:viewintegritymarkers

block/uckk_dashboard:view
block/uckk_dashboard:viewothers
block/uckk_dashboard:configure

mod/uckkchallenge:addinstance
mod/uckkchallenge:view
mod/uckkchallenge:createchallenge
mod/uckkchallenge:submitproof
mod/uckkchallenge:evaluate
mod/uckkchallenge:validateintegrity
mod/uckkchallenge:contest
mod/uckkchallenge:archive

mod/uckkassembly:addinstance
mod/uckkassembly:view
mod/uckkassembly:createassembly
mod/uckkassembly:proposemotion
mod/uckkassembly:amendmotion
mod/uckkassembly:vote
mod/uckkassembly:publishdecision
mod/uckkassembly:contestdecision
mod/uckkassembly:archive

mod/uckkarchive:addinstance
mod/uckkarchive:view
mod/uckkarchive:additem
mod/uckkarchive:validateitem
mod/uckkarchive:versionitem
mod/uckkarchive:viewrestricted
mod/uckkarchive:export

tool/uckkseed:run
tool/uckkseed:dryrun
tool/uckkseed:rollback

tool/uckkintegrity:view
tool/uckkintegrity:opencase
tool/uckkintegrity:reviewcase
tool/uckkintegrity:assigncase
tool/uckkintegrity:issuecorrection
tool/uckkintegrity:invalidate
tool/uckkintegrity:closecase
tool/uckkintegrity:viewrestricted

report/uckk:view
report/uckk:viewall
report/uckk:export

aiprovider/uckk:configure
aiprovider/uckk:use
aiprovider/uckk:viewlogs
```

## 5. Context levels

| Capability family | Context |
|---|---|
| Campus management | System |
| Program/pathway management | Category or system |
| Course format | Course |
| Challenge activity | Module |
| Assembly activity | Module |
| Archive activity | Module/course/user depending on archive |
| Integrity cases | System/course/module depending on subject |
| Reports | System/category/course depending on report |
| Dashboard | User/course/system depending on viewer |

## 6. Role capability matrix

| Capability group | Admin | Gestionnaire | Mentor | Joueur | Archiviste | Inquisiteur | Observateur |
|---|---:|---:|---:|---:|---:|---:|---:|
| Manage UCKK programs | yes | yes | no | no | no | no | no |
| Configure pathways | yes | yes | no | no | no | no | no |
| Teach courses | yes | limited | yes | no | no | no | no |
| Submit proof | yes | no | optional | yes | no | no | no |
| Evaluate proof | yes | optional | yes | no | no | no | no |
| Validate archives | yes | optional | no | no | yes | optional | no |
| Open integrity case | yes | yes | yes | yes | yes | yes | no |
| Review integrity case | yes | no | no | no | no | yes | no |
| Invalidate challenge | yes | no | no | no | no | yes | no |
| Publish assembly decision | yes | yes | yes when facilitator | no | no | no | no |
| View restricted reports | yes | yes | limited | own only | limited | integrity-related | no |

## 7. Inquisiteur constraints

The Inquisiteur must be powerful but not arbitrary.

Rules:

```text
[ ] Inquisiteur can review and pause integrity-sensitive objects.
[ ] Inquisiteur can issue corrections.
[ ] Inquisiteur can invalidate challenge validation when evidence fails.
[ ] Inquisiteur cannot silently delete evidence.
[ ] Inquisiteur cannot modify archive history without version record.
[ ] Inquisiteur cannot assign themselves unrestricted admin rights.
[ ] Inquisiteur decisions are logged and contestable.
[ ] Inquisiteur actions generate events.
```

## 8. Archive security

Archive visibility levels:

```text
private
course
cohort
program
institutional
public
restricted_integrity
```

Rules:

```text
[ ] Restricted integrity archive items require explicit capability.
[ ] Public archive items must be manually validated.
[ ] Evidence files inherit archive item visibility unless overridden.
[ ] Version history must remain available to authorized reviewers.
```

## 9. AI security

AI permissions must be explicit.

Rules:

```text
[ ] AI cannot run in restricted contexts unless enabled.
[ ] AI logs must obey privacy settings.
[ ] AI output must be visibly labeled non-authoritative.
[ ] AI cannot assign grades.
[ ] AI cannot close integrity cases.
[ ] AI cannot publish assembly decisions.
[ ] AI cannot validate archive items.
```

## 10. Audit logging

Every privileged action must create an event:

```text
program changed
pathway assigned
proof evaluated
challenge validated
challenge invalidated
assembly decision published
archive item validated
archive item versioned
integrity case opened
integrity case closed
AI action requested
report exported
seed executed
```

## 11. Definition of done

```text
[ ] db/access.php exists for every plugin needing capabilities.
[ ] Capabilities are context-appropriate.
[ ] No symbolic title is treated as global authority.
[ ] Privileged actions are evented.
[ ] Restricted content cannot be viewed by ordinary users.
[ ] Behat tests confirm permission boundaries.
[ ] PHPUnit tests confirm service-layer capability checks.
```
