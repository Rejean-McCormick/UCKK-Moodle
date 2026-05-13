# 01 — Domain Boundaries and Glossary

**Status:** Final domain specification  
**Purpose:** Prevent conceptual confusion during design, development, UI writing, seeding, permissions, reporting, and documentation.

## 1. Boundary model

UCKK-Moodle must preserve a strict domain separation:

| Domain | Meaning | Moodle implementation |
|---|---|---|
| kOA | Movement, culture, method, social vision | Referenced, taught, integrated through selected APIs |
| UCKK | School / learning city | Main campus implemented in Moodle |
| kOA Digital Ecosystem | Operational digital infrastructure | External or future integration target, not collapsed into Moodle |
| King Klown | Narrative figure | Branding, challenges, symbolic pedagogy |
| Inquisiteur | Ethical and methodological guardrail | Integrity system and restricted capabilities |
| Assemblées | Collective legitimacy | Structured assembly activity and decision records |
| Archives | Memory | Archive activity, evidence records, provenance, reports |

## 2. Naming rules

### 2.1 Site name

```text
Univers-Cité King Klown — Moodle Campus
```

Short name:

```text
UCKK-Moodle
```

Public description:

```text
UCKK-Moodle is the Moodle-based campus for the Univers-Cité King Klown, an experimental learning city and internal recognition environment.
```

### 2.2 Forbidden wording

Do not use these labels in public or student-facing UI unless formal accreditation exists:

```text
official university degree
state-accredited bachelor's degree
public diploma equivalent
recognized university credit
```

Use instead:

```text
internal recognition
UCKK pathway
UCKK badge
experimental learning program
portfolio-based certification
internal baccalauréat
```

## 3. Glossary for implementation

| Term | Implementation meaning |
|---|---|
| Joueur | Moodle user enrolled as learner; also a UCKK profile state |
| Joueur lucide | Badge/status earned through evidence |
| Mentor | Teacher role with course and challenge evaluation capabilities |
| Bâtisseur | Badge/profile distinction, not a default Moodle system role |
| Archiviste | Restricted role/capability set for archive validation |
| Inquisiteur | Restricted integrity reviewer, not global administrator |
| Défi | Dedicated activity instance: `mod_uckkchallenge` |
| Assemblée | Dedicated activity instance: `mod_uckkassembly` |
| Archive | Dedicated activity instance: `mod_uckkarchive` plus cross-plugin evidence store |
| Kristal pédagogique | Structured knowledge item stored in archive, glossary, or local UCKK registry |
| Preuve | File, text, link, observation, decision, grade, or archive item supporting competence |
| Portfolio | Aggregated evidence map attached to a Joueur |
| Tronc commun | Mandatory course set seeded by `tool_uckkseed` |
| Baccalauréat UCKK | Internal pathway composed of Moodle categories/courses/competencies/badges |
| kOA cycle | Connaître → Choisir → Agir → Se souvenir |

## 4. Domain rules

### Rule D1 — Moodle is the campus, not the movement

Moodle implements the UCKK campus. It can teach and connect to kOA, but it must not pretend to be the whole kOA movement.

### Rule D2 — UCKK is an educational institution, not the whole infrastructure

UCKK can use, teach, document, and partly integrate the kOA Digital Ecosystem. Moodle must not absorb all kOA infrastructure concepts into LMS objects unless there is a direct pedagogical or governance purpose.

### Rule D3 — King Klown is narrative power, not institutional sovereignty

King Klown can appear in theme, challenge names, visual identity, onboarding, and public-facing prompts. King Klown must not override the Inquisiteur, Assemblées, evidence, or procedural rules.

### Rule D4 — The Inquisiteur is a guardrail, not arbitrary power

The Inquisiteur validates, contests, pauses, corrects, and documents. The role must be permission-limited, auditable, and contestable.

### Rule D5 — Assemblées produce documented decisions

An Assembly is not a chat room. It has motions, arguments, objections, amendments, votes/readings, decisions, minutes, and archive records.

### Rule D6 — Archives preserve memory with provenance

Archive items must include author, context, source, date, version, visibility, evidence relation, and integrity state.

### Rule D7 — AI is non-sovereign

AI features can clarify, summarize, map, draft, and detect uncertainty. AI outputs are never final decisions, final grades, final integrity judgments, or final evidence validation.

## 5. Object ownership map

| Object | Owner plugin | Notes |
|---|---|---|
| UCKK program | `local_uckk` | Canonical registry |
| UCKK pathway | `local_uckk` | Links courses, competencies, badges |
| UCKK course section layout | `format_uckk` | Course structure |
| Challenge | `mod_uckkchallenge` | Full activity workflow |
| Assembly | `mod_uckkassembly` | Full deliberation workflow |
| Archive item | `mod_uckkarchive` | Evidence, decisions, Kristals |
| Integrity case | `tool_uckkintegrity` | Cross-plugin case handling |
| Dashboard view | `block_uckk_dashboard` | User-facing summary |
| Reports | `report_uckk` | Institutional visibility |
| Seed data | `tool_uckkseed` | Installation and idempotent provisioning |
| AI provider | `ai/provider/uckk` | External AI bridge |
| Visual identity | `theme_uckk` | No business logic |

## 6. Canonical UI formulae

Use these statements consistently:

```text
Comprendre le jeu. Jouer avec lucidité. Changer les règles.
```

```text
Le spectacle est permis. L’abus ne l’est pas.
```

```text
La foi peut inspirer. Les faits doivent convaincre. La méthode doit pouvoir être vérifiée.
```

```text
King Klown révèle le jeu. L’Inquisiteur garde le jeu honnête. Les Assemblées rendent le jeu collectif. L’Archiviste garde la mémoire.
```

## 7. Definition of done

The domain layer is done when:

```text
[ ] Every plugin uses consistent UCKK terminology.
[ ] Public labels avoid accreditation confusion.
[ ] Roles and symbolic titles are not mixed.
[ ] AI is described as assistive, not authoritative.
[ ] King Klown is visible but not sovereign.
[ ] The Inquisiteur is powerful enough to protect integrity but constrained enough to be contestable.
[ ] Assemblées and Archives are real workflow objects, not labels only.
```
