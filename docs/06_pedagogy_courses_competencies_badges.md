# 06 — Pedagogy, Courses, Competencies and Badges

**Status:** Final academic implementation specification  
**Purpose:** Define how UCKK pedagogy becomes Moodle courses, competencies, badges, portfolios, and completion rules.

## 1. Academic structure

Final Moodle category tree:

```text
UCKK
├── 00_Accueil_et_orientation
├── 01_Tronc_commun_obligatoire
├── 02_Baccalaureat_Grand_Jeu_social
├── 03_Baccalaureat_Architecture_ecosysteme_digital_kOA
├── 04_Baccalaureat_Architecture_sociotechnique
├── 05_Baccalaureat_Sciences_politiques
├── 06_Baccalaureat_Economie
├── 07_Baccalaureat_Ecologie
├── 08_Baccalaureat_Metaphysique
├── 09_Baccalaureat_IA_gouvernable
├── 10_Baccalaureat_Linguistique_architecture_du_sens
├── 11_Baccalaureat_Intervention_sociale
├── 12_Mineure_Medias_vivants_theatre_public
├── 13_Seminaires_avances_laboratoires
├── 90_Defis_King_Klown
├── 91_Assemblees
├── 92_Archives_et_Portfolios
└── 99_Integrite_Inquisiteur
```

## 2. Seeded courses

### Orientation

```text
UCKK-000 — Entrer dans l’Univers-Cité King Klown
```

### Tronc commun

```text
UCKK-TC101 — Cartographie des idées avec l’IA
UCKK-TC102 — Intelligence collective, expertise située et décision légitime
UCKK-TC103 — Agitation institutionnelle et mesure de l’utilité réelle
UCKK-TC104 — Société des flux : argent, information et pouvoir
UCKK-TC105 — Fiction fondatrice, vérité morale et récits symboliques
UCKK-TC106 — Mobilisation multi-corridor et coopération pratique
UCKK-TC107 — Introduction à kOA : connaissance, décision, action, mémoire
UCKK-TC108 — Éthique, intégrité et Inquisiteur méthodologique
```

## 3. UCKK course format

Every UCKK course uses `format_uckk` unless a standard Moodle format is explicitly justified.

Default section semantics:

| Section | Purpose | Typical Moodle components |
|---|---|---|
| Orientation | Course entry, rules, expectations | Page, book, label |
| Concepts | Key concepts and definitions | Page, glossary, book |
| Matière canonique | Core reading and source material | File, page, URL, book |
| Atelier | Active learning | Forum, workshop, assignment |
| Preuves | Evidence production | Assignment, database, challenge |
| Délibération | Collective interpretation | Forum, assembly |
| Livrable | Final deliverable | Assignment, challenge submission |
| Évaluation | Grading and feedback | Gradebook, rubric |
| Archive | Final memory | Archive activity |

## 4. Completion design

Each course must have:

```text
course completion enabled
activity completion enabled
completion criteria mapped to evidence
manual override allowed for Mentor and Gestionnaire
archive export on completion when required
```

Required completion categories:

```text
viewed orientation
completed concept checks
submitted at least one evidence item
participated in deliberation
submitted final deliverable
received evaluation
archived final artifact
```

## 5. Competency framework

Framework name:

```text
UCKK — Compétences fondamentales
```

Core competencies:

```text
UCKK-COMP-001 — Lire le Grand Jeu social
UCKK-COMP-002 — Cartographier un système
UCKK-COMP-003 — Distinguer fait, hypothèse, interprétation, récit et décision
UCKK-COMP-004 — Utiliser l’IA comme outil non souverain
UCKK-COMP-005 — Produire une preuve vérifiable
UCKK-COMP-006 — Participer à une assemblée structurée
UCKK-COMP-007 — Concevoir une mobilisation responsable
UCKK-COMP-008 — Documenter une décision
UCKK-COMP-009 — Archiver un apprentissage
UCKK-COMP-010 — Appliquer l’éthique UCKK
UCKK-COMP-011 — Détecter l’autorité cachée
UCKK-COMP-012 — Construire un artefact utile
UCKK-COMP-013 — Assurer la contestabilité
UCKK-COMP-014 — Relier connaissance, décision, action et mémoire
```

## 6. Competency ratings

Use a stable scale:

```text
0 — Not observed
1 — Attempted
2 — Demonstrated with support
3 — Demonstrated independently
4 — Demonstrated with public-quality evidence
5 — Demonstrated and archived as reusable Kristal
```

## 7. Competency mapping by tronc commun course

| Course | Primary competencies |
|---|---|
| TC101 | COMP-002, COMP-003, COMP-004, COMP-005 |
| TC102 | COMP-006, COMP-008, COMP-013 |
| TC103 | COMP-001, COMP-005, COMP-011, COMP-012 |
| TC104 | COMP-001, COMP-002, COMP-011 |
| TC105 | COMP-003, COMP-010, COMP-013 |
| TC106 | COMP-006, COMP-007, COMP-010 |
| TC107 | COMP-014, COMP-001, COMP-008 |
| TC108 | COMP-010, COMP-013, COMP-005 |

## 8. Badge system

### Foundational badges

```text
Badge — Joueur initié
Badge — Joueur lucide
Badge — Cartographe de systèmes
Badge — Gardien de la preuve
Badge — Participant d’Assemblée
Badge — Bâtisseur de prototype
Badge — Archiviste de décision
Badge — Défi King Klown validé
Badge — Inquisition méthodologique réussie
```

### Program badges

```text
Badge — Grand Jeu social
Badge — Architecture écosystème digital kOA
Badge — Architecture sociotechnique
Badge — Sciences politiques
Badge — Économie
Badge — Écologie
Badge — Métaphysique
Badge — IA gouvernable
Badge — Linguistique et architecture du sens
Badge — Intervention sociale
Badge — Médias vivants et théâtre public responsable
```

## 9. Badge award rules

Every badge must require:

```text
[ ] course/activity completion;
[ ] evidence submission;
[ ] human validation;
[ ] competency threshold;
[ ] archive item or portfolio entry;
[ ] no unresolved integrity block.
```

No badge may be awarded solely because a user viewed content.

## 10. Portfolio of Joueur lucide

The portfolio is the central evidence object for tronc commun.

Required portfolio sections:

```text
System map
Flow analysis
AI collaboration journal
Assembly participation record
Institutional utility audit
Symbolic narrative analysis
Mobilisation strategy
Ethics reflection
Final synthesis
Archive links
```

## 11. Seed data requirements

`tool_uckkseed` must create:

```text
[ ] category tree
[ ] UCKK-000
[ ] TC101–TC108
[ ] course templates
[ ] competency framework
[ ] badges
[ ] initial cohorts
[ ] dashboard block placement
[ ] default archive activity
[ ] default assembly activity where relevant
[ ] default challenge activity where relevant
```

## 12. Definition of done

```text
[ ] A new Joueur can enter UCKK-000 and see the complete path.
[ ] TC101–TC108 are created with full section structure.
[ ] Competencies are linked to activities and evidence.
[ ] Badges require evidence and validation.
[ ] Portfolio can aggregate archive items.
[ ] Program pathways are visible in dashboard.
[ ] Reports show progress by course, cohort, competency, and badge.
```
