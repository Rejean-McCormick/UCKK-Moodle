# DOC_13 — UCKK Faculty JSON Authoring Guide

**Document canonique proposé :** `docs/13_faculty_json_authoring_guide.md`  
**Composant principal :** `local_uckk`  
**Portée :** guide de rédaction, édition, révision et validation des fichiers `*.faculty.json`.  
**Dépendance normative :** `docs/12_faculty_pages_atlas_public_contract.md`  
**Statut :** guide d’auteur pour les profils publics de facultés.  
**Version :** `UCKK-FACULTY-AUTHORING-GUIDE-0.2`  
**Schéma cible :** `UCKK-FACULTY-0.1`  
**Chemin des profils :** `local/uckk/content/faculties/`

---

# 1. Objet

Ce document explique comment rédiger les fichiers JSON publics de faculté :

```text
local/uckk/content/faculties/*.faculty.json
```

Ces fichiers ne sont pas les programmes pédagogiques complets.  
Ils sont les profils éditoriaux publics des facultés UCKK.

Leur fonction principale est de présenter chaque Voie comme une porte d’entrée publique dans l’Univers-Cité King Klown : une bibliothèque vivante, un cadre d’apprentissage ouvert et un espace de lecture, d’orientation, de méthode et de pratique du savoir.

Formule de travail :

```text
Atlas JSON = ce qui est enseigné.
Faculty JSON = comment la faculté se présente publiquement.
Moodle = activité réelle, cours, annonces, calendrier, inscriptions, achèvements.
local_uckk = lecture, validation, fusion, rendu, synchronisation.
```

Un `*.faculty.json` doit donc :

```text
présenter publiquement une faculté;
référencer son JSON Atlas;
choisir quoi projeter depuis l’Atlas;
définir la structure éditoriale de la page;
configurer les blocs dynamiques publics;
mettre en avant l’accès au savoir, la lisibilité et la pratique;
orienter vers les cours publics disponibles;
respecter les règles publiques UCKK;
ne jamais exposer de données privées Moodle.
```

Règle éditoriale centrale :

```text
La page publique ouvre le savoir avant de limiter les attentes.
```

---

# 2. Fichiers concernés

Les fichiers éditoriaux publics sont :

```text
local/uckk/content/faculties/grand-jeu-social.faculty.json
local/uckk/content/faculties/economie.faculty.json
local/uckk/content/faculties/ecologie.faculty.json
local/uckk/content/faculties/sciences-politiques.faculty.json
local/uckk/content/faculties/linguistique-architecture-du-sens.faculty.json
local/uckk/content/faculties/metaphysique.faculty.json
local/uckk/content/faculties/ia-gouvernable.faculty.json
local/uckk/content/faculties/intervention-sociale-systemes-humains.faculty.json
local/uckk/content/faculties/architecture-sociotechnique.faculty.json
local/uckk/content/faculties/ecosysteme-digital-koa.faculty.json
```

Ils sont listés par :

```text
local/uckk/content/faculties/faculty_manifest.json
```

Ils sont validés par :

```text
local/uckk/content/faculties/faculty_schema.json
local/uckk/classes/local/faculty/faculty_validator.php
```

---

# 3. Règle principale de non-duplication

Un profil `*.faculty.json` ne doit pas recopier les cours Atlas complets.

Interdit :

```json
{
  "courses": [
    {
      "cours_id": "GJS101",
      "nom": "Cartographie du Grand Jeu social",
      "concepts_associes": []
    }
  ]
}
```

Autorisé :

```json
{
  "atlas_projection": {
    "show_courses": true,
    "show_course_codes": true,
    "show_concept_maitre": true,
    "show_concepts_associes": false
  }
}
```

Le contenu pédagogique canonique reste dans :

```text
local/uckk/atlas/voies/voie_*.json
```

Le contenu éditorial public reste dans :

```text
local/uckk/content/faculties/*.faculty.json
```

La page publique ne doit pas expliquer la mécanique interne avec des phrases comme :

```text
projeté depuis le JSON Atlas;
dérivé de l’Atlas;
espaces Moodle visibles associés au préfixe;
préfixe IA, AS, ECL, etc.
```

Ces détails peuvent exister dans les champs techniques, mais ils ne doivent pas devenir du texte public.

---

# 4. Workflow auteur

Pour créer ou modifier un profil de faculté :

```text
1. Identifier la faculté dans le tableau canonique.
2. Vérifier le voie_id, faculty_id, slug, code, course_prefix.
3. Vérifier le fichier Atlas référencé.
4. Conserver les identifiants techniques stables.
5. Rédiger le nom public, le nom court, le titre symbolique et le domaine.
6. Rédiger identity, seo, hero, sections, faq et contact en langage public.
7. Définir atlas_projection sans dupliquer les données Atlas.
8. Configurer les dynamic_blocks seulement avec des providers autorisés.
9. Orienter les CTA vers les cours, les annonces ou les sections utiles.
10. Vérifier que la note de reconnaissance n’est pas répétée.
11. Valider le JSON.
12. Vérifier que les targets de navigation ne sont pas orphelines.
```

---

# 5. Tableau canonique des facultés

| Ordre | `voie_id` | `faculty_id` | `slug` | `code` | `course_prefix` | `category_idnumber` | `atlas_file` | `faculty_file` | Nom public recommandé |
| ----: | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | `voie_grand_jeu_social` | `faculty_grand_jeu_social` | `grand-jeu-social` | `GJS` | `GJS` | `UCKK-GJS` | `voie_grand_jeu_social.json` | `grand-jeu-social.faculty.json` | Voie du Grand Jeu social |
| 2 | `voie_economie` | `faculty_economie` | `economie` | `EC` | `EC` | `UCKK-EC` | `voie_economie.json` | `economie.faculty.json` | Voie d’Économie |
| 3 | `voie_ecologie` | `faculty_ecologie` | `ecologie` | `ECL` | `ECL` | `UCKK-ECL` | `voie_ecologie.json` | `ecologie.faculty.json` | Voie d’Écologie |
| 4 | `voie_sciences_politiques` | `faculty_sciences_politiques` | `sciences-politiques` | `SP` | `SP` | `UCKK-SP` | `voie_sciences_politiques.json` | `sciences-politiques.faculty.json` | Voie des Sciences politiques |
| 5 | `voie_linguistique_architecture_du_sens` | `faculty_linguistique_architecture_du_sens` | `linguistique-architecture-du-sens` | `LI` | `LI` | `UCKK-LI` | `voie_linguistique_architecture_du_sens.json` | `linguistique-architecture-du-sens.faculty.json` | Voie de la Linguistique et de l’architecture du sens |
| 6 | `voie_metaphysique` | `faculty_metaphysique` | `metaphysique` | `ME` | `ME` | `UCKK-ME` | `voie_metaphysique.json` | `metaphysique.faculty.json` | Voie de la Métaphysique |
| 7 | `voie_ia_gouvernable` | `faculty_ia_gouvernable` | `ia-gouvernable` | `IA` | `IA` | `UCKK-IA` | `voie_ia_gouvernable.json` | `ia-gouvernable.faculty.json` | Voie de la Production augmentée par l’IA |
| 8 | `voie_intervention_sociale_systemes_humains` | `faculty_intervention_sociale_systemes_humains` | `intervention-sociale-systemes-humains` | `IS` | `IS` | `UCKK-IS` | `voie_intervention_sociale_systemes_humains.json` | `intervention-sociale-systemes-humains.faculty.json` | Voie de l’Intervention sociale et des systèmes humains |
| 9 | `voie_architecture_sociotechnique` | `faculty_architecture_sociotechnique` | `architecture-sociotechnique` | `AS` | `AS` | `UCKK-AS` | `voie_architecture_sociotechnique.json` | `architecture-sociotechnique.faculty.json` | Voie d’Architecture sociotechnique |
| 10 | `voie_ecosysteme_digital_koa` | `faculty_ecosysteme_digital_koa` | `ecosysteme-digital-koa` | `KOA` | `KOA` | `UCKK-KOA` | `voie_ecosysteme_digital_koa.json` | `ecosysteme-digital-koa.faculty.json` | Voie de l’Architecture de l’écosystème digital kOA |

## 5.1 Convention spéciale pour la Voie IA

Les identifiants techniques restent stables :

```text
voie_id = voie_ia_gouvernable
faculty_id = faculty_ia_gouvernable
slug = ia-gouvernable
code = IA
course_prefix = IA
category_idnumber = UCKK-IA
hub_course_idnumber = IA-HUB
atlas_file = voie_ia_gouvernable.json
faculty_file = ia-gouvernable.faculty.json
```

Mais le nom public recommandé change :

```text
Nom public : Voie de la Production augmentée par l’IA
Nom court : Production IA
Nom court éditorial : Production augmentée
Titre symbolique : Maître d’œuvre augmenté
Domaine : Production outillée
```

Règle éditoriale :

```text
Ne pas présenter cette Voie comme une délégation du contrôle à l’IA.
Présenter l’IA comme outil de production, de création, de code, de documentation, d’accompagnement, de vérification et de prototypage.
La responsabilité, la décision et l’autorité finale demeurent humaines.
```

---

# 6. Squelette complet d’un profil Faculty JSON

Ce squelette montre la structure complète attendue.  
Les valeurs doivent être adaptées à chaque faculté canonique.

```json
{
  "schema_version": "UCKK-FACULTY-0.1",
  "faculty_id": "faculty_grand_jeu_social",
  "voie_id": "voie_grand_jeu_social",
  "slug": "grand-jeu-social",
  "status": "published",
  "visibility": "public",
  "source_atlas": {
    "file": "voie_grand_jeu_social.json",
    "schema_version_expected": "UCKK-ATLAS-0.2-draft",
    "sync_mode": "read_only"
  },
  "moodle": {
    "category_id": null,
    "category_idnumber": "UCKK-GJS",
    "course_prefix": "GJS",
    "public_course_listing": true,
    "enrolment_visibility": "public_info_only",
    "hub_course_idnumber": "GJS-HUB"
  },
  "identity": {
    "eyebrow": "Voie UCKK",
    "name": "Voie du Grand Jeu social",
    "short_name": "Grand Jeu social",
    "title_symbolique": "Joueur lucide",
    "domain": "Systèmes sociaux",
    "level": "Puissance opératoire",
    "faculty_role": "Voie qui ouvre une grammaire publique pour lire la société comme un système de règles, d’acteurs, de pouvoirs, de récits, de preuves, de ressources et de transformations possibles.",
    "one_sentence": "Lire les règles, les positions, les pouvoirs, les récits, les preuves et les ressources du Grand Jeu social afin d’agir avec plus de lucidité."
  },
  "seo": {
    "title": "Voie du Grand Jeu social — UCKK",
    "description": "Présentation publique de la Voie du Grand Jeu social dans l’Univers-Cité King Klown : repères, cours, méthodes et pratiques pour comprendre le Grand Jeu social.",
    "keywords": [
      "UCKK",
      "Voie",
      "Grand Jeu social",
      "bibliothèque publique",
      "puissance opératoire"
    ]
  },
  "hero": {
    "title": "Voie du Grand Jeu social",
    "subtitle": "Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.",
    "summary": "Cette voie fait partie de l’Univers-Cité King Klown : un établissement virtuel de puissance opératoire consacré au Grand Jeu social. Elle rassemble des cours publics, des repères, des méthodes et des exercices pour apprendre à lire un domaine, agir avec lucidité et construire des preuves de compréhension.",
    "primary_cta": {
      "label": "Comprendre la voie",
      "target": "#programme"
    },
    "secondary_cta": {
      "label": "Accéder aux cours",
      "target": "#cours-moodle"
    }
  },
  "navigation": [
    {
      "label": "Présentation",
      "target": "#presentation"
    },
    {
      "label": "Parcours",
      "target": "#programme"
    },
    {
      "label": "Cours",
      "target": "#cours-moodle"
    },
    {
      "label": "Projet final",
      "target": "#projet-final"
    },
    {
      "label": "Éthique",
      "target": "#ethique"
    },
    {
      "label": "Annonces",
      "target": "#annonces"
    }
  ],
  "sections": [
    {
      "id": "presentation",
      "type": "text",
      "title": "Une voie pour lire le jeu avant d’y jouer",
      "body": "Cette faculté présente une Voie UCKK comme parcours public de lecture, de méthode et de pratique dans la bibliothèque vivante de l’Univers-Cité King Klown."
    },
    {
      "id": "programme",
      "type": "text",
      "title": "Parcours de la Voie",
      "body": "Cette voie organise un parcours public de lecture, de méthode et de pratique. Elle aide à comprendre un domaine du Grand Jeu social, à reconnaître ses règles visibles et invisibles, puis à développer des capacités d’action plus lucides, responsables et vérifiables."
    },
    {
      "id": "cours",
      "type": "callout",
      "title": "Explorer les cours publics",
      "body": "Les cours publics sont les principales portes d’entrée dans cette voie. Ils permettent de découvrir les concepts, méthodes, exercices et artefacts d’apprentissage, puis d’ouvrir l’espace de cours correspondant lorsqu’il est disponible."
    },
    {
      "id": "projet-final",
      "type": "text",
      "title": "Projet final",
      "body": "Le projet final permet de produire une trace opératoire : carte, dossier, prototype, analyse, méthode, intervention, système documentaire ou artefact public démontrant la compréhension de la voie."
    },
    {
      "id": "ethique",
      "type": "notice",
      "title": "Éthique publique",
      "body": "La puissance opératoire visée doit rester lisible, contestable, responsable et compatible avec l’intégrité des personnes concernées."
    }
  ],
  "atlas_projection": {
    "show_definition_courte": true,
    "show_angle_fondamental": true,
    "show_competence_centrale": true,
    "show_seuils_progression": true,
    "show_courses": true,
    "show_course_codes": true,
    "show_concept_maitre": true,
    "show_concepts_associes": false,
    "show_artefacts": true,
    "show_criteres_passage": false,
    "show_projet_final": true,
    "show_limites_ethiques": true,
    "show_relations_intervoies": true,
    "show_tags": false
  },
  "dynamic_blocks": [
    {
      "id": "annonces",
      "type": "announcements",
      "title": "Annonces de la voie",
      "source": {
        "provider": "moodle_forum",
        "course_idnumber": "GJS-HUB",
        "forum_name": "Annonces"
      },
      "limit": 5,
      "visibility": "public",
      "empty_state": "Aucune annonce publique pour le moment."
    },
    {
      "id": "cours-moodle",
      "type": "moodle_course_list",
      "title": "Accéder aux cours",
      "source": {
        "provider": "moodle_category",
        "category_idnumber": "UCKK-GJS"
      },
      "limit": 10,
      "visibility": "public",
      "empty_state": "Les cours publics de cette voie seront affichés ici lorsqu’ils seront disponibles."
    }
  ],
  "featured_blocks": [
    {
      "type": "principle",
      "title": "Voir le jeu avant de jouer",
      "body": "Toute action responsable commence par la lecture des règles, des positions, des ressources, des récits et des preuves qui structurent une situation."
    },
    {
      "type": "method",
      "title": "Pratiquer avec méthode",
      "body": "Une Voie UCKK sert à transformer des savoirs en repères praticables, en exercices, en cartes et en artefacts vérifiables."
    },
    {
      "type": "ethics",
      "title": "Agir avec responsabilité",
      "body": "La puissance opératoire ne doit pas devenir opacité, domination ou manipulation. Elle doit rester lisible, contestable et responsable."
    }
  ],
  "faq": [
    {
      "question": "Par où commencer?",
      "answer": "Commencez par la présentation de la voie, puis ouvrez les cours publics disponibles. Chaque cours sert de porte d’entrée vers une notion, une méthode ou un artefact d’apprentissage."
    },
    {
      "question": "Faut-il être inscrit pour apprendre?",
      "answer": "Les pages publiques donnent accès aux repères essentiels de la voie. Certains espaces de cours peuvent demander une connexion ou une inscription selon leur usage, mais l’orientation générale du savoir reste publique."
    },
    {
      "question": "Que signifie le titre symbolique associé à cette Voie?",
      "answer": "C’est un repère narratif et pédagogique interne. Il aide à nommer une posture d’apprentissage ou de pratique. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future."
    }
  ],
  "contact": {
    "label": "Contact",
    "body": "Pour suivre cette voie, commencez par les cours disponibles, puis consultez les annonces publiques pour les nouvelles, rencontres et ouvertures d’espaces d’apprentissage.",
    "email": "",
    "cta": {
      "label": "Voir les annonces",
      "target": "#annonces"
    }
  },
  "governance": {
    "owner": "local_uckk",
    "editorial_status": "approved",
    "last_reviewed": null,
    "review_notes": "",
    "public_claims_guardrails": [
      "Garder la note de reconnaissance unique, discrète et non centrale; ne pas transformer la page publique en avertissement répété sur les diplômes, statuts ou certifications."
    ]
  },
  "cache": {
    "enabled": true,
    "ttl_seconds": 3600
  }
}
```

---

# 7. Champs top-level obligatoires

Chaque profil doit contenir exactement les familles de champs suivantes :

```json
{
  "schema_version": "UCKK-FACULTY-0.1",
  "faculty_id": "",
  "voie_id": "",
  "slug": "",
  "status": "draft",
  "visibility": "hidden",
  "source_atlas": {},
  "moodle": {},
  "identity": {},
  "seo": {},
  "hero": {},
  "navigation": [],
  "sections": [],
  "atlas_projection": {},
  "dynamic_blocks": [],
  "featured_blocks": [],
  "faq": [],
  "contact": {},
  "governance": {},
  "cache": {}
}
```

Règles :

```text
Aucun champ obligatoire ne doit être omis.
Aucun champ structurel ne doit changer de type.
Les listes vides doivent rester des listes.
Les objets vides doivent rester des objets.
Ne pas utiliser null pour remplacer une liste.
```

---

# 8. `schema_version`

Valeur obligatoire :

```json
"schema_version": "UCKK-FACULTY-0.1"
```

Interdit :

```json
"schema_version": "1.0"
```

```json
"schema_version": "faculty"
```

```json
"schema_version": null
```

---

# 9. `faculty_id`, `voie_id`, `slug`

Ces trois valeurs doivent correspondre au tableau canonique.

Exemple valide :

```json
{
  "faculty_id": "faculty_grand_jeu_social",
  "voie_id": "voie_grand_jeu_social",
  "slug": "grand-jeu-social"
}
```

Règles :

```text
faculty_id commence par faculty_.
voie_id commence par voie_.
slug est en minuscules.
slug n’a pas d’accents.
slug utilise des tirets.
slug ne doit jamais être interprété comme un chemin de fichier.
```

Règle spéciale : le slug `ia-gouvernable` peut être conservé pour compatibilité technique même si le nom public devient `Production IA` ou `Voie de la Production augmentée par l’IA`.

---

# 10. `status`

Valeurs autorisées :

```text
draft
published
archived
```

Usage :

```text
draft = profil en rédaction, visible seulement aux éditeurs autorisés.
published = profil public si visibility=public.
archived = profil retiré de la liste active.
```

Exemple :

```json
"status": "published"
```

---

# 11. `visibility`

Valeurs autorisées :

```text
public
hidden
restricted
```

Usage :

```text
public = accessible sans login.
hidden = non accessible publiquement.
restricted = accès contrôlé par capability Moodle.
```

Règle :

```text
status=published avec visibility=hidden ne doit pas être listé publiquement.
status=draft ne doit pas être visible anonymement.
visibility=restricted doit passer par Moodle et ne doit pas exposer de données privées.
```

---

# 12. `source_atlas`

Structure obligatoire :

```json
{
  "file": "voie_grand_jeu_social.json",
  "schema_version_expected": "UCKK-ATLAS-0.2-draft",
  "sync_mode": "read_only"
}
```

Valeurs autorisées pour `sync_mode` :

```text
read_only
preview_only
moodle_sync_allowed
```

Usage recommandé :

```text
read_only = défaut pour pages publiques.
preview_only = prévisualisation ou brouillon.
moodle_sync_allowed = autorise l’usage dans dry-run/apply admin.
```

Règle :

```text
source_atlas.file doit être un nom de fichier Atlas canonique, pas un chemin.
Ce champ est technique; ne pas reprendre son vocabulaire dans le texte public.
```

---

# 13. `moodle`

Structure obligatoire :

```json
{
  "category_id": null,
  "category_idnumber": "UCKK-GJS",
  "course_prefix": "GJS",
  "public_course_listing": true,
  "enrolment_visibility": "public_info_only",
  "hub_course_idnumber": "GJS-HUB"
}
```

Valeurs autorisées pour `enrolment_visibility` :

```text
hidden
public_info_only
login_required
enrolment_required
```

Règles :

```text
category_id peut rester null.
category_idnumber doit correspondre au tableau canonique.
course_prefix doit correspondre au code.
hub_course_idnumber suit la convention CODE-HUB.
public_course_listing contrôle l’affichage public du programme.
```

Exemples de hubs :

```text
GJS-HUB
EC-HUB
ECL-HUB
SP-HUB
LI-HUB
ME-HUB
IA-HUB
IS-HUB
AS-HUB
KOA-HUB
```

Règle éditoriale :

```text
Ne pas écrire publiquement “préfixe GJS”, “préfixe ECL”, “espaces Moodle associés au préfixe”, ou “dérivé de Moodle”.
Écrire plutôt “cours publics”, “accéder aux cours”, “espaces de cours disponibles”.
```

---

# 14. `identity`

Structure obligatoire :

```json
{
  "eyebrow": "Voie UCKK",
  "name": "",
  "short_name": "",
  "title_symbolique": "",
  "domain": "",
  "level": "Puissance opératoire",
  "faculty_role": "",
  "one_sentence": ""
}
```

Règles :

```text
identity.name est le nom public complet.
identity.short_name est utilisé dans la navigation.
identity.title_symbolique doit correspondre au JSON Atlas sauf override documenté.
identity.domain doit correspondre à domaine_operatoire sauf override documenté.
identity.level doit correspondre à niveau_vise.
identity.one_sentence doit être public, sobre, non promotionnel.
```

Pour la Voie IA, utiliser publiquement :

```json
{
  "name": "Voie de la Production augmentée par l’IA",
  "short_name": "Production IA",
  "title_symbolique": "Maître d’œuvre augmenté",
  "domain": "Production outillée"
}
```

Interdit :

```text
revendiquer une accréditation publique;
présenter la faculté comme université reconnue par l’État;
promettre un titre professionnel réglementé;
présenter une reconnaissance interne comme diplôme public;
présenter l’IA comme autorité finale ou instance de contrôle.
```

---

# 15. `seo`

Structure obligatoire :

```json
{
  "title": "",
  "description": "",
  "keywords": []
}
```

Bon exemple :

```json
{
  "title": "Voie du Grand Jeu social — UCKK",
  "description": "Présentation publique de la Voie du Grand Jeu social dans l’Univers-Cité King Klown : repères, cours, méthodes et pratiques pour comprendre le Grand Jeu social.",
  "keywords": [
    "UCKK",
    "Voie",
    "Grand Jeu social",
    "bibliothèque publique"
  ]
}
```

Bon exemple pour la Voie IA :

```json
{
  "title": "Production augmentée par l’IA — UCKK",
  "description": "La Voie de la Production augmentée par l’IA ouvre un parcours public pour utiliser l’intelligence artificielle comme outil d’écriture, de code, de graphisme, de documentation, de prototypage, d’accompagnement, de vérification et de production responsable.",
  "keywords": [
    "UCKK",
    "Production IA",
    "Production augmentée",
    "intelligence artificielle",
    "IA comme outil",
    "code",
    "documentation",
    "graphisme",
    "écriture",
    "responsabilité humaine"
  ]
}
```

Interdit comme thème SEO :

```text
université accréditée;
diplôme reconnu;
grade universitaire officiel;
certification professionnelle garantie;
équivalence gouvernementale;
IA autonome;
IA dirigeante;
contrôle par l’IA.
```

---

# 16. `hero`

Structure obligatoire :

```json
{
  "title": "",
  "subtitle": "",
  "summary": "",
  "primary_cta": {
    "label": "",
    "target": ""
  },
  "secondary_cta": {
    "label": "",
    "target": ""
  }
}
```

Targets autorisés :

```text
#anchor
/local/uckk/...
https://...
```

Targets interdits :

```text
javascript:
data:
file:
ftp:
```

Exemple recommandé :

```json
{
  "primary_cta": {
    "label": "Comprendre la voie",
    "target": "#programme"
  },
  "secondary_cta": {
    "label": "Accéder aux cours",
    "target": "#cours-moodle"
  }
}
```

Exemple recommandé pour la Voie IA :

```json
{
  "title": "Production augmentée par l’IA",
  "subtitle": "Écrire, coder, créer, documenter et produire avec l’IA, sans lui déléguer l’autorité.",
  "summary": "Cette voie apprend à utiliser l’IA comme outil de production : livres, graphisme, programmes, documentation institutionnelle, prototypes, méthodes d’accompagnement et systèmes de travail. L’IA accélère la construction; la décision, la responsabilité et la vérification demeurent humaines.",
  "primary_cta": {
    "label": "Comprendre la voie",
    "target": "#programme"
  },
  "secondary_cta": {
    "label": "Accéder aux cours",
    "target": "#cours-moodle"
  }
}
```

---

# 17. `navigation`

Structure :

```json
[
  {
    "label": "Présentation",
    "target": "#presentation"
  }
]
```

Règles :

```text
Chaque target doit pointer vers un élément rendu.
Chaque #target doit correspondre à un id de section, bloc dynamique, FAQ ou contact.
Ne pas créer de target orpheline.
Ne pas créer une section importante sans entrée de navigation.
```

Navigation recommandée :

```json
[
  {
    "label": "Présentation",
    "target": "#presentation"
  },
  {
    "label": "Parcours",
    "target": "#programme"
  },
  {
    "label": "Cours",
    "target": "#cours-moodle"
  },
  {
    "label": "Projet final",
    "target": "#projet-final"
  },
  {
    "label": "Éthique",
    "target": "#ethique"
  },
  {
    "label": "Annonces",
    "target": "#annonces"
  }
]
```

---

# 18. `sections`

Structure :

```json
[
  {
    "id": "presentation",
    "type": "text",
    "title": "",
    "body": ""
  }
]
```

Types autorisés :

```text
text
markdown
quote
principle
notice
cards
callout
two_column
```

Usage :

```text
text = texte brut filtré.
markdown = Markdown filtré côté serveur.
quote = citation éditoriale.
principle = principe institutionnel.
notice = avertissement.
cards = cartes éditoriales.
callout = bloc accentué.
two_column = layout éditorial.
```

Exemple recommandé :

```json
[
  {
    "id": "presentation",
    "type": "text",
    "title": "Présentation",
    "body": "Cette faculté présente une Voie comme parcours public de lecture, de méthode et de pratique dans la bibliothèque UCKK."
  },
  {
    "id": "programme",
    "type": "text",
    "title": "Parcours de la Voie",
    "body": "Cette voie organise un parcours public pour comprendre un domaine du Grand Jeu social, reconnaître ses règles visibles et invisibles, puis développer des capacités d’action plus lucides et responsables."
  },
  {
    "id": "cours",
    "type": "callout",
    "title": "Explorer les cours publics",
    "body": "Les cours publics sont les principales portes d’entrée dans cette voie. Ils permettent de découvrir les concepts, méthodes, exercices et artefacts d’apprentissage, puis d’ouvrir l’espace de cours correspondant lorsqu’il est disponible."
  }
]
```

Exemple recommandé pour la Voie IA :

```json
[
  {
    "id": "presentation",
    "type": "text",
    "title": "Une voie pour produire avec l’IA",
    "body": "La Voie de la Production augmentée par l’IA aborde l’intelligence artificielle comme un atelier de production. Elle sert à écrire, coder, concevoir, documenter, créer, prototyper, réviser et accompagner, sans remplacer le jugement humain."
  },
  {
    "id": "programme",
    "type": "text",
    "title": "Un parcours de production outillée",
    "body": "Cette voie apprend à formuler une intention, préparer un contexte, dialoguer avec un modèle, transformer une sortie en matériau de travail, vérifier les résultats, documenter les limites et intégrer l’IA dans un processus productif responsable."
  },
  {
    "id": "cours",
    "type": "callout",
    "title": "Explorer les cours publics",
    "body": "Les cours publics apprennent à utiliser l’IA pour écrire, coder, concevoir des images, préparer des documents institutionnels, prototyper des outils, soutenir l’accompagnement humain et vérifier les productions."
  }
]
```

Règles :

```text
section.id est unique dans le fichier.
section.id est en minuscules, sans espace.
section.type doit être autorisé.
section.body ne contient pas de HTML dangereux.
section.body ne contient pas de données privées.
section.body ne doit pas exposer la mécanique de sync Atlas/Moodle.
```

---

# 19. `atlas_projection`

Structure complète recommandée :

```json
{
  "show_definition_courte": true,
  "show_angle_fondamental": true,
  "show_competence_centrale": true,
  "show_seuils_progression": true,
  "show_courses": true,
  "show_course_codes": true,
  "show_concept_maitre": true,
  "show_concepts_associes": false,
  "show_artefacts": true,
  "show_criteres_passage": false,
  "show_projet_final": true,
  "show_limites_ethiques": true,
  "show_relations_intervoies": true,
  "show_tags": false
}
```

Valeurs publiques recommandées :

```text
show_courses = true
show_course_codes = true
show_concept_maitre = true
show_concepts_associes = false
show_criteres_passage = false
show_projet_final = true
show_limites_ethiques = true
```

Règle :

```text
atlas_projection décide quoi afficher depuis Atlas.
atlas_projection ne recopie pas les données Atlas.
Le texte public ne doit pas parler au visiteur de projection Atlas comme si c’était le sujet de la page.
```

---

# 20. `dynamic_blocks`

Structure :

```json
[
  {
    "id": "annonces",
    "type": "announcements",
    "title": "Annonces de la voie",
    "source": {
      "provider": "moodle_forum",
      "course_idnumber": "GJS-HUB",
      "forum_name": "Annonces"
    },
    "limit": 5,
    "visibility": "public",
    "empty_state": "Aucune annonce publique pour le moment."
  }
]
```

Types autorisés :

```text
announcements
events
moodle_course_list
featured_courses
faculty_news
related_faculties
public_resources
cta_panel
```

Providers autorisés :

```text
moodle_forum
moodle_calendar
moodle_category
moodle_course_customfield
local_uckk_news
local_uckk_manual
none
```

Règles :

```text
dynamic_blocks affiche seulement des données publiques.
Un provider inconnu invalide le profil.
Un bloc dynamique doit avoir un empty_state public.
Un bloc ne doit pas exposer de notes, progression, achèvement privé ou données personnelles.
Le titre public d’un bloc ne doit pas être un message d’administration système.
```

---

# 21. Exemples de blocs dynamiques

## 21.1 Annonces Moodle

```json
{
  "id": "annonces",
  "type": "announcements",
  "title": "Annonces de la voie",
  "source": {
    "provider": "moodle_forum",
    "course_idnumber": "GJS-HUB",
    "forum_name": "Annonces"
  },
  "limit": 5,
  "visibility": "public",
  "empty_state": "Aucune annonce publique pour le moment."
}
```

## 21.2 Événements Moodle

```json
{
  "id": "evenements",
  "type": "events",
  "title": "Événements publics",
  "source": {
    "provider": "moodle_calendar",
    "category_idnumber": "UCKK-GJS"
  },
  "limit": 5,
  "visibility": "public",
  "empty_state": "Aucun événement public annoncé pour le moment."
}
```

## 21.3 Liste de cours depuis catégorie Moodle

```json
{
  "id": "cours-moodle",
  "type": "moodle_course_list",
  "title": "Accéder aux cours",
  "source": {
    "provider": "moodle_category",
    "category_idnumber": "UCKK-GJS"
  },
  "limit": 10,
  "visibility": "public",
  "empty_state": "Les cours publics de cette voie seront affichés ici lorsqu’ils seront disponibles."
}
```

Éviter :

```json
{
  "title": "Espaces Moodle associés",
  "empty_state": "Aucun cours Moodle public associé à cette voie pour le moment."
}
```

Préférer :

```json
{
  "title": "Accéder aux cours",
  "empty_state": "Les cours publics de cette voie seront affichés ici lorsqu’ils seront disponibles."
}
```

## 21.4 Bloc manuel

```json
{
  "id": "ressources",
  "type": "public_resources",
  "title": "Ressources publiques",
  "source": {
    "provider": "local_uckk_manual",
    "items": [
      {
        "label": "Guide public de la faculté",
        "url": "/local/uckk/faculty.php?slug=grand-jeu-social"
      }
    ]
  },
  "limit": 3,
  "visibility": "public",
  "empty_state": "Aucune ressource publique pour le moment."
}
```

## 21.5 Bloc désactivé sans provider

```json
{
  "id": "appel",
  "type": "cta_panel",
  "title": "Participer",
  "source": {
    "provider": "none"
  },
  "limit": 1,
  "visibility": "public",
  "empty_state": "Les modalités de participation seront annoncées plus tard."
}
```

---

# 22. `featured_blocks`

Structure :

```json
[
  {
    "type": "principle",
    "title": "",
    "body": ""
  }
]
```

Types autorisés par le validateur :

```text
principle
notice
warning
quote
stat
method
ethics
cta
```

Usage recommandé :

```text
Les featured_blocks servent à mettre en avant les principes publics de la Voie :
lecture du monde, méthode, accès au savoir, éthique, pratique, orientation.
Ils ne doivent pas devenir un espace de répétition des limites d’accréditation.
```

Exemple recommandé :

```json
[
  {
    "type": "principle",
    "title": "Voir le jeu avant de jouer",
    "body": "Toute action responsable commence par la lecture des règles, des positions, des ressources, des récits et des preuves qui structurent une situation."
  },
  {
    "type": "method",
    "title": "Ouvrir l’accès au savoir",
    "body": "Cette Voie fait partie d’une bibliothèque publique vivante : elle sert à rendre les outils de compréhension plus accessibles, plus partageables et plus praticables."
  },
  {
    "type": "ethics",
    "title": "Agir avec responsabilité",
    "body": "La puissance opératoire visée doit rester lisible, contestable, responsable et compatible avec l’intégrité des personnes concernées."
  }
]
```

Exemple recommandé pour la Voie IA :

```json
[
  {
    "type": "principle",
    "title": "L’IA comme outil de production",
    "body": "L’IA sert à écrire, coder, créer, documenter, prototyper, comparer, corriger et accélérer le travail humain. Elle ne reçoit pas l’autorité finale."
  },
  {
    "type": "method",
    "title": "Transformer la sortie en matériau",
    "body": "Une réponse d’IA n’est qu’un point de départ. La méthode consiste à la relire, la tester, la corriger, la situer, la comparer et l’intégrer dans un travail humain vérifiable."
  },
  {
    "type": "ethics",
    "title": "Ne pas déléguer l’autorité",
    "body": "kOA n’utilise pas l’IA pour remplacer le jugement humain. L’IA peut accélérer la production, mais la responsabilité, la décision et l’interprétation demeurent humaines."
  }
]
```

À éviter dans `featured_blocks` :

```text
un bloc principal intitulé Reconnaissance interne;
une répétition de la formule diplôme/accréditation;
une mise en scène défensive de ce que la Voie n’est pas;
un type non validé comme library ou knowledge si le validateur ne l’autorise pas.
```

---

# 23. `faq`

Structure :

```json
[
  {
    "question": "",
    "answer": ""
  }
]
```

Règles :

```text
FAQ publique seulement.
Ne pas répondre sur progression individuelle.
Ne pas promettre accréditation.
Ne pas fournir de conseil légal, médical ou financier comme autorité UCKK.
Ne pas présenter le Parchemin comme diplôme public.
Ne pas transformer la FAQ en répétition des limites institutionnelles.
```

Exemples recommandés :

```json
[
  {
    "question": "Par où commencer?",
    "answer": "Commencez par la présentation de la voie, puis ouvrez les cours publics disponibles. Chaque cours sert de porte d’entrée vers une notion, une méthode ou un artefact d’apprentissage."
  },
  {
    "question": "Faut-il être inscrit pour apprendre?",
    "answer": "Les pages publiques donnent accès aux repères essentiels de la voie. Certains espaces de cours peuvent demander une connexion ou une inscription selon leur usage, mais l’orientation générale du savoir reste publique."
  },
  {
    "question": "Que signifie le titre symbolique associé à cette Voie?",
    "answer": "C’est un repère narratif et pédagogique interne. Il aide à nommer une posture d’apprentissage ou de pratique. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future."
  }
]
```

Exemples recommandés pour la Voie IA :

```json
[
  {
    "question": "Cette voie apprend-elle à donner le contrôle à l’IA?",
    "answer": "Non. Cette voie apprend à utiliser l’IA comme outil de production. Elle aide à écrire, coder, créer, documenter, prototyper, accompagner et vérifier, sans déplacer l’autorité finale hors du jugement humain."
  },
  {
    "question": "Que peut-on produire avec l’IA?",
    "answer": "On peut produire des livres, images, interfaces, scripts, programmes, documents institutionnels, assistants, cartes, analyses, protocoles, méthodes d’accompagnement et systèmes de travail. Chaque production doit être relue, testée, corrigée et assumée humainement."
  },
  {
    "question": "Que signifie Maître d’œuvre augmenté?",
    "answer": "C’est le titre symbolique associé à cette voie. Il désigne une posture de production responsable : coordonner des outils d’IA, garder l’intention humaine, vérifier les résultats et assumer les choix. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future."
  }
]
```

La mention de reconnaissance doit apparaître au plus une fois par page publique :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

Ne pas ajouter systématiquement :

```text
Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.
```

Cette information peut être vraie, mais elle ne doit pas devenir un thème répété.

---

# 24. `contact`

Structure :

```json
{
  "label": "Contact",
  "body": "",
  "email": "",
  "cta": {
    "label": "",
    "target": ""
  }
}
```

Règles :

```text
email peut être vide.
email ne doit pas être une adresse privée sans consentement.
cta.target peut pointer vers #annonces, #contact ou une page local_uckk autorisée.
Le texte de contact doit orienter l’utilisateur, pas décrire la mécanique Moodle.
```

Exemple recommandé :

```json
{
  "label": "Contact",
  "body": "Pour suivre cette voie, commencez par les cours disponibles, puis consultez les annonces publiques pour les nouvelles, rencontres et ouvertures d’espaces d’apprentissage.",
  "email": "",
  "cta": {
    "label": "Voir les annonces",
    "target": "#annonces"
  }
}
```

---

# 25. `governance`

Structure obligatoire :

```json
{
  "owner": "local_uckk",
  "editorial_status": "draft",
  "last_reviewed": null,
  "review_notes": "",
  "public_claims_guardrails": []
}
```

Valeurs autorisées pour `editorial_status` :

```text
draft
review
approved
needs_update
archived
```

Guardrail recommandé :

```json
[
  "Garder la note de reconnaissance unique, discrète et non centrale; ne pas transformer la page publique en avertissement répété sur les diplômes, statuts ou certifications."
]
```

Guardrail recommandé pour la Voie IA :

```json
[
  "Présenter l’IA comme outil de production, jamais comme autorité finale, sujet de gouvernance autonome ou instance de contrôle."
]
```

Règles :

```text
public_claims_guardrails peut être vide si la page ne présente pas de risque public particulier.
S’il est présent, il ne doit pas contenir une longue liste répétitive.
Les guardrails sont des notes de gouvernance éditoriale, pas le message principal de la page.
La limite institutionnelle sur les reconnaissances doit rester vraie, mais ne doit pas dominer le contenu public.
```

---

# 26. `cache`

Structure obligatoire :

```json
{
  "enabled": true,
  "ttl_seconds": 3600
}
```

Règles :

```text
enabled=true par défaut.
ttl_seconds=3600 par défaut.
ttl_seconds doit être un entier positif.
Une page publique doit pouvoir être purgée par local_uckk.
```

---

# 27. Règles de rédaction publique

Chaque profil doit respecter ces règles :

```text
Ton public, sobre, institutionnel.
Priorité à la diffusion du savoir.
Priorité à la bibliothèque publique vivante.
Priorité à la lisibilité, à la méthode et à la pratique.
Priorité aux cours publics comme portes d’entrée.
Aucune promesse d’accréditation.
Aucune promesse de reconnaissance gouvernementale.
Aucune promesse professionnelle réglementée.
Aucune donnée privée Moodle.
Aucune note, achèvement, progression, inscription individuelle.
Aucune confusion entre Voie, cours Moodle, catégorie Moodle et faculté publique.
```

Formules autorisées :

```text
Page de faculté UCKK.
Voie UCKK.
Parcours public de lecture, de méthode et de pratique.
Bibliothèque publique vivante.
Cadre d’apprentissage ouvert.
Diffusion publique du savoir.
Cours publics.
Accéder aux cours.
Explorer les cours publics.
Reconnaissance interne UCKK, sauf reconnaissance officielle future.
```

Formules à éviter comme thèmes principaux :

```text
diplôme public accrédité;
université officiellement reconnue;
grade universitaire d’État;
certification professionnelle garantie;
équivalence officielle;
titre légalement reconnu;
cette Voie ne donne pas de diplôme;
non accrédité;
aucun statut universitaire public;
préfixe Moodle;
dérivé de l’Atlas;
projeté depuis le JSON Atlas;
espaces Moodle associés.
```

Pour la Voie IA, éviter :

```text
IA gouvernable comme nom public principal;
donner le contrôle à l’IA;
gouvernance autonome de l’IA;
IA comme décideur;
IA comme autorité finale.
```

Pour la Voie IA, préférer :

```text
Production augmentée par l’IA;
Production IA;
Maître d’œuvre augmenté;
IA comme outil de production;
écrire, coder, créer, documenter, accompagner, prototyper, vérifier;
responsabilité humaine;
production vérifiable.
```

---

# 28. Règles de sécurité éditoriale

Ne jamais afficher publiquement :

```text
notes;
progression individuelle;
achèvement individuel;
statut d’inscription individuel;
feedback privé;
soumissions privées;
preuves privées;
votes privés;
données personnelles;
adresses privées;
cas d’intégrité;
journaux IA privés;
rapports confidentiels;
commentaires internes.
```

Si un bloc dynamique provient de Moodle, il doit filtrer avant rendu.

---

# 29. Contrôle des anchors

Chaque navigation target doit correspondre à un élément rendu.

Valide :

```json
{
  "label": "Cours",
  "target": "#cours-moodle"
}
```

si `dynamic_blocks` contient :

```json
{
  "id": "cours-moodle",
  "type": "moodle_course_list"
}
```

Invalide :

```json
{
  "label": "Ressources",
  "target": "#ressources"
}
```

si aucune section ou bloc dynamique n’a :

```json
"id": "ressources"
```

Anchors recommandés :

```text
#presentation
#programme
#cours
#cours-moodle
#projet-final
#ethique
#annonces
#evenements
#faq
#contact
```

---

# 30. Contrôle des providers

Providers autorisés :

```text
moodle_forum
moodle_calendar
moodle_category
moodle_course_customfield
local_uckk_news
local_uckk_manual
none
```

Interdit :

```json
{
  "provider": "wordpress"
}
```

```json
{
  "provider": "google_calendar"
}
```

```json
{
  "provider": "custom_api"
}
```

Si un nouveau provider est nécessaire, il doit être ajouté dans :

```text
docs/12_faculty_pages_atlas_public_contract.md
docs/13_faculty_json_authoring_guide.md
local/uckk/classes/local/faculty/faculty_validator.php
local/uckk/classes/local/faculty/faculty_dynamic_block_provider.php
```

---

# 31. Contrôle des IDs

Les IDs suivants ne doivent jamais être renommés sans migration explicite :

```text
faculty_id
voie_id
slug
category_idnumber
course_prefix
hub_course_idnumber
source_atlas.file
```

Règle :

```text
Changer le nom public est permis.
Changer l’ID technique exige une migration.
```

Exemple :

```text
Nom public de la Voie IA = Voie de la Production augmentée par l’IA
ID technique conservé = voie_ia_gouvernable
Slug conservé = ia-gouvernable
```

---

# 32. Exemple abrégé pour `economie.faculty.json`

```json
{
  "schema_version": "UCKK-FACULTY-0.1",
  "faculty_id": "faculty_economie",
  "voie_id": "voie_economie",
  "slug": "economie",
  "status": "published",
  "visibility": "public",
  "source_atlas": {
    "file": "voie_economie.json",
    "schema_version_expected": "UCKK-ATLAS-0.2-draft",
    "sync_mode": "read_only"
  },
  "moodle": {
    "category_id": null,
    "category_idnumber": "UCKK-EC",
    "course_prefix": "EC",
    "public_course_listing": true,
    "enrolment_visibility": "public_info_only",
    "hub_course_idnumber": "EC-HUB"
  },
  "identity": {
    "eyebrow": "Voie fondatrice UCKK",
    "name": "Voie d’Économie",
    "short_name": "Économie",
    "title_symbolique": "Architecte d’opportunités",
    "domain": "Ressources",
    "level": "Puissance opératoire",
    "faculty_role": "Voie qui apprend à lire, cartographier et transformer les règles de circulation, de capture et de redistribution de la valeur.",
    "one_sentence": "Comprendre les ressources, les flux, les incitatifs, les marchés, le travail, la valeur et les modèles économiques comme règles du Grand Jeu social."
  },
  "seo": {
    "title": "Voie d’Économie — UCKK",
    "description": "Présentation publique de la Voie d’Économie : ressources, flux, valeur, marchés, travail, capture, redistribution et modèles économiques dans le Grand Jeu social.",
    "keywords": [
      "UCKK",
      "Économie",
      "ressources",
      "valeur",
      "marchés",
      "travail",
      "Grand Jeu social"
    ]
  },
  "hero": {
    "title": "Voie d’Économie",
    "subtitle": "Lire les ressources, les flux, la valeur et les opportunités.",
    "summary": "Cette voie ouvre un parcours public pour comprendre les règles économiques du Grand Jeu social : production, échange, capture, redistribution, travail, incitatifs, modèles d’affaires et conditions d’accès aux ressources.",
    "primary_cta": {
      "label": "Comprendre la voie",
      "target": "#programme"
    },
    "secondary_cta": {
      "label": "Accéder aux cours",
      "target": "#cours-moodle"
    }
  },
  "sections": [
    {
      "id": "presentation",
      "type": "text",
      "title": "Une voie pour lire les règles de la valeur",
      "body": "La Voie d’Économie étudie les ressources, les flux, les incitatifs, les marchés, le travail, la dette, la valeur, la capture et la redistribution comme règles du Grand Jeu social."
    },
    {
      "id": "programme",
      "type": "text",
      "title": "Parcours de la Voie",
      "body": "Cette voie organise un parcours public pour comprendre comment la valeur circule, où elle se concentre, comment elle se justifie et quelles formes de redistribution ou de transformation deviennent possibles."
    },
    {
      "id": "cours",
      "type": "callout",
      "title": "Explorer les cours publics",
      "body": "Les cours publics sont les principales portes d’entrée dans cette voie. Ils permettent de découvrir les concepts, méthodes, exercices et artefacts d’apprentissage, puis d’ouvrir l’espace de cours correspondant lorsqu’il est disponible."
    }
  ],
  "dynamic_blocks": [
    {
      "id": "cours-moodle",
      "type": "moodle_course_list",
      "title": "Accéder aux cours",
      "source": {
        "provider": "moodle_category",
        "category_idnumber": "UCKK-EC"
      },
      "limit": 10,
      "visibility": "public",
      "empty_state": "Les cours publics de cette voie seront affichés ici lorsqu’ils seront disponibles."
    }
  ],
  "governance": {
    "owner": "local_uckk",
    "editorial_status": "approved",
    "last_reviewed": null,
    "review_notes": "",
    "public_claims_guardrails": [
      "Garder la note de reconnaissance unique, discrète et non centrale; ne pas transformer la page publique en avertissement répété sur les diplômes, statuts ou certifications."
    ]
  },
  "cache": {
    "enabled": true,
    "ttl_seconds": 3600
  }
}
```

---

# 33. Exemple abrégé pour `ia-gouvernable.faculty.json`

```json
{
  "schema_version": "UCKK-FACULTY-0.1",
  "faculty_id": "faculty_ia_gouvernable",
  "voie_id": "voie_ia_gouvernable",
  "slug": "ia-gouvernable",
  "status": "published",
  "visibility": "public",
  "source_atlas": {
    "file": "voie_ia_gouvernable.json",
    "schema_version_expected": "UCKK-ATLAS-0.2-draft",
    "sync_mode": "read_only"
  },
  "moodle": {
    "category_id": null,
    "category_idnumber": "UCKK-IA",
    "course_prefix": "IA",
    "public_course_listing": true,
    "enrolment_visibility": "public_info_only",
    "hub_course_idnumber": "IA-HUB"
  },
  "identity": {
    "eyebrow": "Voie ouverte UCKK",
    "name": "Voie de la Production augmentée par l’IA",
    "short_name": "Production IA",
    "title_symbolique": "Maître d’œuvre augmenté",
    "domain": "Production outillée",
    "level": "Puissance opératoire",
    "faculty_role": "Voie consacrée à l’usage de l’intelligence artificielle comme outil de production, d’écriture, de code, de graphisme, de documentation, d’accompagnement, de prototypage, de vérification et d’accélération du travail humain.",
    "one_sentence": "Utiliser l’IA pour produire mieux, documenter plus clairement, vérifier davantage et relier les idées, sans lui déléguer la décision, la responsabilité ou l’autorité finale."
  },
  "hero": {
    "title": "Production augmentée par l’IA",
    "subtitle": "Écrire, coder, créer, documenter et produire avec l’IA, sans lui déléguer l’autorité.",
    "summary": "Cette voie apprend à utiliser l’IA comme outil de production : livres, graphisme, programmes, documentation institutionnelle, prototypes, méthodes d’accompagnement et systèmes de travail. L’IA accélère la construction; la décision, la responsabilité et la vérification demeurent humaines.",
    "primary_cta": {
      "label": "Comprendre la voie",
      "target": "#programme"
    },
    "secondary_cta": {
      "label": "Accéder aux cours",
      "target": "#cours-moodle"
    }
  },
  "governance": {
    "owner": "local_uckk",
    "editorial_status": "approved",
    "last_reviewed": null,
    "review_notes": "Note éditoriale interne : renommage public vers Production augmentée par l’IA. Les identifiants techniques faculty_ia_gouvernable, voie_ia_gouvernable et ia-gouvernable sont conservés pour compatibilité Atlas/Moodle.",
    "public_claims_guardrails": [
      "Présenter l’IA comme outil de production, jamais comme autorité finale, sujet de gouvernance autonome ou instance de contrôle."
    ]
  }
}
```

---

# 34. Validation manuelle avant commit

Avant commit, vérifier :

```text
[ ] Le JSON est syntaxiquement valide.
[ ] Le schema_version est UCKK-FACULTY-0.1.
[ ] faculty_id correspond au manifest.
[ ] voie_id correspond au manifest.
[ ] slug correspond au manifest.
[ ] source_atlas.file existe.
[ ] category_idnumber correspond au manifest.
[ ] course_prefix correspond au code.
[ ] Tous les CTA pointent vers des anchors existants.
[ ] Tous les providers sont autorisés.
[ ] Tous les featured_blocks.type sont autorisés.
[ ] Aucun contenu privé Moodle n’est exposé.
[ ] Le texte public ne contient pas de jargon de sync Atlas/Moodle.
[ ] La note de reconnaissance n’apparaît pas plus d’une fois.
[ ] Les cours sont présentés comme portes d’entrée publiques.
[ ] Pour la Voie IA, le nom public est Production augmentée par l’IA.
[ ] Pour la Voie IA, le titre symbolique est Maître d’œuvre augmenté.
[ ] Pour la Voie IA, l’IA est présentée comme outil de production, jamais comme autorité.
```

---

# 35. Validation CLI recommandée

Commandes recommandées :

```bash
php -l local/uckk/classes/local/faculty/faculty_validator.php
php -l local/uckk/classes/local/faculty/faculty_page_builder.php
```

Validation JSON :

```bash
python -m json.tool local/uckk/content/faculties/grand-jeu-social.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/economie.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/ecologie.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/sciences-politiques.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/linguistique-architecture-du-sens.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/metaphysique.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/ia-gouvernable.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/intervention-sociale-systemes-humains.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/architecture-sociotechnique.faculty.json >/dev/null
python -m json.tool local/uckk/content/faculties/ecosysteme-digital-koa.faculty.json >/dev/null
```

Validation applicative recommandée :

```bash
php admin/cli/purge_caches.php
php local/uckk/cli/validate_faculties.php
```

---

# 36. Erreurs fréquentes

## 36.1 Mauvais emplacement

Interdit :

```text
local/uckk/grand-jeu-social.faculty.json
```

Correct :

```text
local/uckk/content/faculties/grand-jeu-social.faculty.json
```

## 36.2 Duplication des cours Atlas

Interdit :

```json
"courses": []
```

Correct :

```json
"atlas_projection": {
  "show_courses": true
}
```

## 36.3 Provider inventé

Interdit :

```json
"provider": "notion"
```

Correct :

```json
"provider": "local_uckk_manual"
```

ou :

```json
"provider": "moodle_category"
```

## 36.4 Target orpheline

Interdit :

```json
{
  "label": "Cours",
  "target": "#cours"
}
```

si aucun élément rendu ne porte :

```json
"id": "cours"
```

Correct :

```json
{
  "label": "Cours",
  "target": "#cours-moodle"
}
```

si le bloc dynamique existe :

```json
{
  "id": "cours-moodle",
  "type": "moodle_course_list"
}
```

## 36.5 Confusion accréditation

Mauvais thème principal :

```text
Cette Voie ne donne pas un diplôme public accrédité.
```

Meilleure formulation, si nécessaire une seule fois :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

## 36.6 Messages trop système

À éviter :

```text
Le programme public est projeté depuis le JSON Atlas.
Les cours sont dérivés de l’Atlas et des espaces Moodle visibles associés au préfixe AS.
Espaces Moodle associés.
Aucun cours Moodle public associé à cette voie pour le moment.
```

À écrire :

```text
Cette voie organise un parcours public de lecture, de méthode et de pratique.
Les cours publics sont les principales portes d’entrée dans cette voie.
Accéder aux cours.
Les cours publics de cette voie seront affichés ici lorsqu’ils seront disponibles.
```

## 36.7 Mauvais cadrage de la Voie IA

À éviter :

```text
Voie de l’Intelligence artificielle gouvernable.
IA gouvernable.
Bâtisseur augmenté.
Donner le contrôle à l’IA.
Gouvernance autonome des systèmes d’IA.
```

À écrire :

```text
Voie de la Production augmentée par l’IA.
Production IA.
Maître d’œuvre augmenté.
Utiliser l’IA comme outil de production.
Écrire, coder, créer, documenter, accompagner, prototyper et vérifier avec l’IA.
La responsabilité et l’autorité finale demeurent humaines.
```

---

# 37. Règles IA anti-drift pour l’auteur

Avant de modifier un `*.faculty.json`, une IA doit identifier :

```text
1. Le fichier exact.
2. La faculté concernée.
3. Le voie_id.
4. Le faculty_id.
5. Le slug.
6. Le fichier Atlas.
7. Le code et le course_prefix.
8. Les anchors existants.
9. Les dynamic_blocks existants.
10. Les provider names existants.
11. Les types autorisés.
12. Les textes publics à corriger.
13. Les notes institutionnelles répétées.
14. Les formulations système à supprimer.
```

Une IA ne doit pas inventer :

```text
nouveau voie_id;
nouveau faculty_id;
nouveau slug;
nouveau course_prefix;
nouveau provider;
nouveau type de bloc;
nouveau champ top-level;
nouvelle variable Mustache;
nouveau statut;
nouvelle visibilité;
nouvelle capacité Moodle.
```

Exception : un nom public peut être modifié sans changer les IDs techniques, si la compatibilité est explicitement documentée dans `review_notes`.

---

# 38. Résumé opératoire

```text
Un Faculty JSON n’est pas le programme complet.
Un Faculty JSON est la présentation publique d’une Voie.
Le visiteur doit comprendre quoi apprendre, pourquoi cela compte, et où accéder aux cours.
Le texte public doit parler de savoir, méthode, pratique, cours et responsabilité.
Le texte public ne doit pas parler comme un système de sync Atlas/Moodle.
Les cours publics sont des portes d’entrée, pas une barrière.
La reconnaissance UCKK reste interne, sauf reconnaissance officielle future, mais cette limite ne doit pas dominer la page.
Les identifiants techniques restent stables.
Les noms publics peuvent évoluer lorsque le canon éditorial l’exige.
La Voie IA s’appelle publiquement Production augmentée par l’IA.
L’IA est un outil de production, pas une autorité finale.
```

Formule finale :

```text
Atlas enseigne.
Faculty raconte.
Moodle exécute.
local_uckk relie.
Mustache affiche.
La page publique ouvre le savoir.
Les cours donnent accès.
L’IA aide à produire.
L’humain demeure responsable.
```
