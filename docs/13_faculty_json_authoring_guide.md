# DOC_13 — UCKK Faculty JSON Authoring Guide

**Document canonique proposé :** `docs/13_faculty_json_authoring_guide.md`
**Composant principal :** `local_uckk`
**Portée :** guide de rédaction, édition, révision et validation des fichiers `*.faculty.json`.
**Dépendance normative :** `docs/12_faculty_pages_atlas_public_contract.md`
**Statut :** guide d’auteur pour les profils publics de facultés.
**Version :** `UCKK-FACULTY-AUTHORING-GUIDE-0.1`
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

Leur fonction principale est de présenter chaque Voie comme une porte d’entrée publique dans la bibliothèque vivante UCKK : un espace de lecture, d’orientation, de méthode et de pratique du savoir.

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
respecter les guardrails publics UCKK;
ne jamais exposer de données privées Moodle.
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

---

# 4. Workflow auteur

Pour créer ou modifier un profil de faculté :

```text
1. Identifier la faculté dans le tableau canonique.
2. Vérifier le voie_id, faculty_id, slug, code, course_prefix.
3. Vérifier le fichier Atlas référencé.
4. Remplir les champs top-level obligatoires.
5. Rédiger identity, seo, hero, sections, faq, contact.
6. Définir atlas_projection sans dupliquer les données Atlas.
7. Configurer les dynamic_blocks seulement avec des providers autorisés.
8. Ajouter les guardrails publics.
9. Valider le JSON.
10. Vérifier que les targets de navigation ne sont pas orphelines.
```

---

# 5. Tableau canonique des facultés

| Ordre | `voie_id`                                    | `faculty_id`                                    | `slug`                                  | `code` | `course_prefix` | `category_idnumber` | `atlas_file`                                      | `faculty_file`                                       |
| ----: | -------------------------------------------- | ----------------------------------------------- | --------------------------------------- | ------ | --------------- | ------------------- | ------------------------------------------------- | ---------------------------------------------------- |
|     1 | `voie_grand_jeu_social`                      | `faculty_grand_jeu_social`                      | `grand-jeu-social`                      | `GJS`  | `GJS`           | `UCKK-GJS`          | `voie_grand_jeu_social.json`                      | `grand-jeu-social.faculty.json`                      |
|     2 | `voie_economie`                              | `faculty_economie`                              | `economie`                              | `EC`   | `EC`            | `UCKK-EC`           | `voie_economie.json`                              | `economie.faculty.json`                              |
|     3 | `voie_ecologie`                              | `faculty_ecologie`                              | `ecologie`                              | `ECL`  | `ECL`           | `UCKK-ECL`          | `voie_ecologie.json`                              | `ecologie.faculty.json`                              |
|     4 | `voie_sciences_politiques`                   | `faculty_sciences_politiques`                   | `sciences-politiques`                   | `SP`   | `SP`            | `UCKK-SP`           | `voie_sciences_politiques.json`                   | `sciences-politiques.faculty.json`                   |
|     5 | `voie_linguistique_architecture_du_sens`     | `faculty_linguistique_architecture_du_sens`     | `linguistique-architecture-du-sens`     | `LI`   | `LI`            | `UCKK-LI`           | `voie_linguistique_architecture_du_sens.json`     | `linguistique-architecture-du-sens.faculty.json`     |
|     6 | `voie_metaphysique`                          | `faculty_metaphysique`                          | `metaphysique`                          | `ME`   | `ME`            | `UCKK-ME`           | `voie_metaphysique.json`                          | `metaphysique.faculty.json`                          |
|     7 | `voie_ia_gouvernable`                        | `faculty_ia_gouvernable`                        | `ia-gouvernable`                        | `IA`   | `IA`            | `UCKK-IA`           | `voie_ia_gouvernable.json`                        | `ia-gouvernable.faculty.json`                        |
|     8 | `voie_intervention_sociale_systemes_humains` | `faculty_intervention_sociale_systemes_humains` | `intervention-sociale-systemes-humains` | `IS`   | `IS`            | `UCKK-IS`           | `voie_intervention_sociale_systemes_humains.json` | `intervention-sociale-systemes-humains.faculty.json` |
|     9 | `voie_architecture_sociotechnique`           | `faculty_architecture_sociotechnique`           | `architecture-sociotechnique`           | `AS`   | `AS`            | `UCKK-AS`           | `voie_architecture_sociotechnique.json`           | `architecture-sociotechnique.faculty.json`           |
|    10 | `voie_ecosysteme_digital_koa`                | `faculty_ecosysteme_digital_koa`                | `ecosysteme-digital-koa`                | `KOA`  | `KOA`           | `UCKK-KOA`          | `voie_ecosysteme_digital_koa.json`                | `ecosysteme-digital-koa.faculty.json`                |

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
    "title_symbolique": "",
    "domain": "",
    "level": "Puissance opératoire",
    "faculty_role": "",
    "one_sentence": ""
  },
  "seo": {
    "title": "Voie du Grand Jeu social — Faculté UCKK",
    "description": "",
    "keywords": [
      "UCKK",
      "Voie",
      "Grand Jeu social",
      "bibliothèque publique"
    ]
  },
  "hero": {
    "title": "Voie du Grand Jeu social",
    "subtitle": "",
    "summary": "",
    "primary_cta": {
      "label": "Explorer la voie",
      "target": "#programme"
    },
    "secondary_cta": {
      "label": "Voir les annonces",
      "target": "#annonces"
    }
  },
  "navigation": [
    {
      "label": "Présentation",
      "target": "#presentation"
    },
    {
      "label": "Programme",
      "target": "#programme"
    },
    {
      "label": "Annonces",
      "target": "#annonces"
    },
    {
      "label": "FAQ",
      "target": "#faq"
    },
    {
      "label": "Contact",
      "target": "#contact"
    }
  ],
  "sections": [
    {
      "id": "presentation",
      "type": "text",
      "title": "Présentation",
      "body": "Cette faculté présente une Voie UCKK comme parcours public de lecture, de méthode et de pratique dans la bibliothèque vivante de l’Univers-Cité King Klown."
    },
    {
      "id": "clarte",
      "type": "notice",
      "title": "Clarté publique",
      "body": "Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels."
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
      "title": "Annonces de la faculté",
      "source": {
        "provider": "moodle_forum",
        "course_idnumber": "GJS-HUB",
        "forum_name": "Annonces"
      },
      "limit": 5,
      "visibility": "public",
      "empty_state": "Aucune annonce publique pour le moment."
    }
  ],
  "featured_blocks": [
    {
      "type": "principle",
      "title": "Voir le jeu avant de jouer",
      "body": "Toute action responsable commence par la lecture des règles, des positions, des ressources, des récits et des preuves qui structurent une situation."
    },
    {
      "type": "library",
      "title": "Ouvrir l’accès au savoir",
      "body": "Cette Voie fait partie d’une bibliothèque publique vivante : elle sert à rendre les outils de compréhension plus accessibles, plus partageables et plus praticables."
    },
    {
      "type": "ethics",
      "title": "Agir avec responsabilité",
      "body": "La puissance opératoire visée doit rester lisible, contestable, responsable et compatible avec l’intégrité des personnes concernées."
    }
  ],
  "faq": [
    {
      "question": "À quoi sert cette Voie?",
      "answer": "Elle sert à apprendre à lire un domaine, à relier des savoirs, à pratiquer des méthodes et à produire des traces utiles dans le cadre de la bibliothèque publique UCKK."
    },
    {
      "question": "La page contient-elle toute la matière des cours?",
      "answer": "Non. La page publique présente la faculté et projette certains éléments de l’Atlas. Les contenus détaillés, activités, validations et espaces de travail restent dans Moodle selon leurs règles d’accès."
    },
    {
      "question": "Que signifie le titre symbolique associé à cette Voie?",
      "answer": "C’est un repère narratif et pédagogique interne. Il aide à nommer une posture d’apprentissage ou de pratique sans constituer un titre professionnel reconnu par l’État."
    }
  ],
  "contact": {
    "label": "Contact",
    "body": "Pour toute question sur cette Voie, consultez les annonces publiques ou les espaces Moodle associés.",
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
      "Présenter la Voie comme un parcours public de lecture, de méthode et de pratique.",
      "Mettre en avant l’accès au savoir, la lisibilité et la diffusion publique.",
      "Ne pas afficher de progression, notes ou données privées d’étudiants.",
      "Ne pas présenter une Voie expérimentale comme officiellement reconnue.",
      "Ne pas transformer un titre symbolique en titre professionnel reconnu par l’État.",
      "Ne pas transformer l’accès au savoir en barrière ou en promesse de statut."
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

Interdit :

```json
{
  "faculty_id": "grand_jeu_social",
  "voie_id": "voie-grand-jeu-social",
  "slug": "voie-grand-jeu-social"
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

Exemple :

```json
"visibility": "public"
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

Interdit :

```json
{
  "file": "../../secret.json"
}
```

```json
{
  "file": "/tmp/voie.json"
}
```

Règle :

```text
source_atlas.file doit être un nom de fichier Atlas canonique, pas un chemin.
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

Interdit :

```text
revendiquer une accréditation publique;
présenter la faculté comme université reconnue par l’État;
promettre un titre professionnel réglementé;
présenter une reconnaissance interne comme diplôme public.
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
  "title": "Voie du Grand Jeu social — Faculté UCKK",
  "description": "Présentation publique de la Voie du Grand Jeu social dans l’Univers-Cité King Klown.",
  "keywords": [
    "UCKK",
    "Voie",
    "Grand Jeu social",
    "bibliothèque publique"
  ]
}
```

Interdit :

```text
université accréditée;
diplôme reconnu;
grade universitaire officiel;
certification professionnelle garantie;
équivalence gouvernementale.
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

Exemple valide :

```json
{
  "primary_cta": {
    "label": "Explorer la voie",
    "target": "#programme"
  },
  "secondary_cta": {
    "label": "Voir les annonces",
    "target": "#annonces"
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

Exemple :

```json
[
  {
    "label": "Présentation",
    "target": "#presentation"
  },
  {
    "label": "Programme",
    "target": "#programme"
  },
  {
    "label": "Annonces",
    "target": "#annonces"
  },
  {
    "label": "FAQ",
    "target": "#faq"
  },
  {
    "label": "Contact",
    "target": "#contact"
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

Exemple :

```json
[
  {
    "id": "presentation",
    "type": "text",
    "title": "Présentation",
    "body": "Cette faculté présente une Voie comme parcours public de lecture, de méthode et de pratique dans la bibliothèque UCKK."
  },
  {
    "id": "principes",
    "type": "principle",
    "title": "Principe de travail",
    "body": "Apprendre à lire, relier, pratiquer et agir dans un cadre ouvert, vérifiable et responsable."
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
```

Exemple minimal public :

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

---

# 20. `dynamic_blocks`

Structure :

```json
[
  {
    "id": "annonces",
    "type": "announcements",
    "title": "Annonces de la faculté",
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
```

---

# 21. Exemples de blocs dynamiques

## 21.1 Annonces Moodle

```json
{
  "id": "annonces",
  "type": "announcements",
  "title": "Annonces de la faculté",
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
    "course_idnumber": "GJS-HUB"
  },
  "limit": 5,
  "visibility": "public",
  "empty_state": "Aucun événement public prévu."
}
```

## 21.3 Liste de cours depuis catégorie Moodle

```json
{
  "id": "cours-moodle",
  "type": "moodle_course_list",
  "title": "Cours Moodle associés",
  "source": {
    "provider": "moodle_category",
    "category_idnumber": "UCKK-GJS"
  },
  "limit": 10,
  "visibility": "public",
  "empty_state": "La liste des cours Moodle n’est pas encore publiée."
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

Types autorisés :

```text
principle
notice
warning
quote
stat
method
ethics
cta
library
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
    "type": "library",
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

À éviter dans `featured_blocks` :

```text
un bloc principal intitulé Reconnaissance interne;
une répétition de la formule diplôme/accréditation;
une mise en scène défensive de ce que la Voie n’est pas.
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
    "question": "À quoi sert cette Voie?",
    "answer": "Elle sert à apprendre à lire un domaine, à relier des savoirs, à pratiquer des méthodes et à produire des traces utiles dans le cadre de la bibliothèque publique UCKK."
  },
  {
    "question": "La page contient-elle toute la matière des cours?",
    "answer": "Non. La page publique présente la faculté et projette certains éléments de l’Atlas. Les contenus détaillés, activités, validations et espaces de travail restent dans Moodle selon leurs règles d’accès."
  },
  {
    "question": "Que signifie le titre symbolique associé à cette Voie?",
    "answer": "C’est un repère narratif et pédagogique interne. Il aide à nommer une posture d’apprentissage ou de pratique sans constituer un titre professionnel reconnu par l’État."
  }
]
```

La mention de reconnaissance doit apparaître au plus une fois par page publique, idéalement dans une notice sobre :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.
```

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
```

Exemple :

```json
{
  "label": "Contact",
  "body": "Pour toute question sur cette Voie, consultez les annonces publiques ou les espaces Moodle associés.",
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

Guardrails recommandés :

```json
[
  "Présenter la Voie comme un parcours public de lecture, de méthode et de pratique.",
  "Mettre en avant l’accès au savoir, la lisibilité et la diffusion publique.",
  "Ne pas afficher de progression, notes ou données privées d’étudiants.",
  "Ne pas présenter une Voie expérimentale comme officiellement reconnue.",
  "Ne pas transformer un titre symbolique en titre professionnel reconnu par l’État.",
  "Ne pas transformer l’accès au savoir en barrière ou en promesse de statut."
]
```

La limite institutionnelle sur les reconnaissances doit rester vraie, mais ne doit pas dominer le contenu public.

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
Programme public de la Voie.
Présentation publique de la faculté.
Cadre d’apprentissage ouvert.
Diffusion publique du savoir.
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
cette faculté n’est pas accréditée.
```

Règle éditoriale :

```text
La limite de reconnaissance doit être dite une seule fois, sobrement.
Elle ne doit pas devenir un bloc récurrent, une FAQ obligatoire ou le thème principal des pages publiques.
```

Formule canonique recommandée :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.
```

---

# 28. Règles de sécurité éditoriale

Interdit dans tous les champs texte :

```text
<script>
javascript:
data:
file:
iframe non contrôlée
HTML brut dangereux
identifiants privés
adresses privées sans consentement
tokens
secrets
chemins serveur
données personnelles d’étudiants
```

Les champs `body`, `summary`, `description`, `answer` et `empty_state` doivent être rendus par les filtres Moodle appropriés côté serveur.

---

# 29. Contrôle des anchors

Chaque élément de navigation doit pointer vers un élément existant.

Exemple valide :

```json
"navigation": [
  {
    "label": "Présentation",
    "target": "#presentation"
  },
  {
    "label": "Programme",
    "target": "#programme"
  },
  {
    "label": "Annonces",
    "target": "#annonces"
  }
]
```

Doivent exister :

```text
sections[].id = presentation
atlas_projection rend un bloc avec id programme
dynamic_blocks[].id = annonces
```

Interdit :

```json
{
  "label": "Équipe",
  "target": "#equipe"
}
```

si aucune section, aucun bloc ou aucun élément rendu ne possède l’id `equipe`.

---

# 30. Contrôle des providers

Les providers autorisés sont strictement :

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
  "provider": "external_api"
}
```

```json
{
  "provider": "database"
}
```

Tout nouveau provider doit être ajouté au contrat DOC_12 avant usage.

---

# 31. Contrôle des IDs

Chaque profil doit respecter :

```text
faculty_id unique.
voie_id unique.
slug unique.
section.id unique.
dynamic_blocks[].id unique.
navigation[].target non orphelin.
category_idnumber cohérent.
course_prefix cohérent.
hub_course_idnumber cohérent.
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
    "eyebrow": "Voie UCKK",
    "name": "Voie de l’Économie",
    "short_name": "Économie",
    "title_symbolique": "",
    "domain": "",
    "level": "Puissance opératoire",
    "faculty_role": "",
    "one_sentence": ""
  },
  "seo": {
    "title": "Voie de l’Économie — Faculté UCKK",
    "description": "Présentation publique de la Voie de l’Économie dans l’Univers-Cité King Klown.",
    "keywords": [
      "UCKK",
      "Voie",
      "Économie",
      "bibliothèque publique"
    ]
  },
  "hero": {
    "title": "Voie de l’Économie",
    "subtitle": "",
    "summary": "",
    "primary_cta": {
      "label": "Explorer la voie",
      "target": "#programme"
    },
    "secondary_cta": {
      "label": "Voir les annonces",
      "target": "#annonces"
    }
  },
  "navigation": [
    {
      "label": "Présentation",
      "target": "#presentation"
    },
    {
      "label": "Programme",
      "target": "#programme"
    },
    {
      "label": "Annonces",
      "target": "#annonces"
    },
    {
      "label": "FAQ",
      "target": "#faq"
    }
  ],
  "sections": [
    {
      "id": "presentation",
      "type": "text",
      "title": "Présentation",
      "body": "Cette faculté présente la Voie comme parcours public de lecture, de méthode et de pratique dans la bibliothèque UCKK."
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
      "title": "Annonces de la faculté",
      "source": {
        "provider": "moodle_forum",
        "course_idnumber": "EC-HUB",
        "forum_name": "Annonces"
      },
      "limit": 5,
      "visibility": "public",
      "empty_state": "Aucune annonce publique pour le moment."
    }
  ],
  "featured_blocks": [
    {
      "type": "principle",
      "title": "Lire les systèmes économiques",
      "body": "Cette Voie aide à comprendre les ressources, les échanges, les dépendances, les incitatifs et les règles qui organisent la vie économique."
    },
    {
      "type": "library",
      "title": "Ouvrir l’accès au savoir économique",
      "body": "La page publique sert à rendre les outils de compréhension économique plus accessibles, plus partageables et plus praticables."
    }
  ],
  "faq": [
    {
      "question": "À quoi sert cette Voie?",
      "answer": "Elle sert à apprendre à lire les systèmes économiques, à comprendre les ressources et les échanges, et à relier ces savoirs aux situations concrètes."
    },
    {
      "question": "Les cours Moodle sont-ils visibles publiquement?",
      "answer": "La page peut présenter le programme public. L’accès aux espaces Moodle dépend des règles d’inscription et de visibilité configurées."
    }
  ],
  "contact": {
    "label": "Contact",
    "body": "Pour toute question sur cette Voie, consultez les annonces publiques ou les espaces Moodle associés.",
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
      "Présenter la Voie comme un parcours public de lecture, de méthode et de pratique.",
      "Mettre en avant l’accès au savoir, la lisibilité et la diffusion publique.",
      "Ne pas afficher de progression, notes ou données privées d’étudiants.",
      "Ne pas présenter une Voie expérimentale comme officiellement reconnue.",
      "Ne pas transformer un titre symbolique en titre professionnel reconnu par l’État.",
      "Ne pas transformer l’accès au savoir en barrière ou en promesse de statut."
    ]
  },
  "cache": {
    "enabled": true,
    "ttl_seconds": 3600
  }
}
```

---

# 33. Validation manuelle avant commit

Avant de committer un `*.faculty.json`, vérifier :

```text
[ ] Le fichier est du JSON valide.
[ ] Le fichier est dans local/uckk/content/faculties/.
[ ] schema_version = UCKK-FACULTY-0.1.
[ ] faculty_id correspond au tableau canonique.
[ ] voie_id correspond au tableau canonique.
[ ] slug correspond au tableau canonique.
[ ] source_atlas.file correspond au JSON Atlas canonique.
[ ] moodle.category_idnumber correspond au tableau canonique.
[ ] moodle.course_prefix correspond au tableau canonique.
[ ] moodle.hub_course_idnumber suit CODE-HUB.
[ ] status est draft, published ou archived.
[ ] visibility est public, hidden ou restricted.
[ ] navigation ne contient aucune target orpheline.
[ ] sections[].id est unique.
[ ] dynamic_blocks[].id est unique.
[ ] dynamic_blocks[].source.provider est autorisé.
[ ] atlas_projection ne duplique pas les cours.
[ ] FAQ ne promet pas d’accréditation.
[ ] Le profil met d’abord en avant l’accès au savoir, la méthode et la bibliothèque publique.
[ ] La limite de reconnaissance apparaît au plus une fois dans le contenu public visible.
[ ] featured_blocks ne sert pas à répéter les limites d’accréditation.
[ ] governance.public_claims_guardrails est présent.
[ ] Aucune donnée privée Moodle n’est exposée.
[ ] Aucune variable non documentée n’est introduite.
```

---

# 34. Validation CLI recommandée

Commandes génériques recommandées depuis la racine du dépôt :

```bash
python3 -m json.tool local/uckk/content/faculties/faculty_manifest.json >/dev/null
python3 -m json.tool local/uckk/content/faculties/faculty_schema.json >/dev/null

find local/uckk/content/faculties -name '*.faculty.json' -print0 \
  | xargs -0 -n1 python3 -m json.tool >/dev/null
```

Commande plugin attendue lorsque l’outil existe :

```bash
php local/uckk/cli/validate_faculties.php
```

La validation doit vérifier au minimum :

```text
manifest valide;
schéma valide;
10 profils présents;
slugs uniques;
faculty_id uniques;
voie_id uniques;
providers autorisés;
targets de navigation valides;
guardrails publics présents;
aucune donnée privée exposée.
```

---

# 35. Erreurs fréquentes

## 35.1 Mauvais emplacement

Interdit :

```text
docs/grand-jeu-social.faculty.json
local/uckk/templates/grand-jeu-social.faculty.json
local/uckk/classes/grand-jeu-social.faculty.json
```

Autorisé :

```text
local/uckk/content/faculties/grand-jeu-social.faculty.json
```

## 35.2 Duplication des cours Atlas

Interdit :

```json
{
  "courses": []
}
```

Autorisé :

```json
{
  "atlas_projection": {
    "show_courses": true
  }
}
```

## 35.3 Provider inventé

Interdit :

```json
{
  "provider": "rss_feed"
}
```

Autorisé seulement si le provider est ajouté au contrat DOC_12, au validator et au provider PHP correspondant.

## 35.4 Target orpheline

Interdit :

```json
{
  "label": "Éthique",
  "target": "#ethique"
}
```

sans :

```json
{
  "id": "ethique"
}
```

dans `sections` ou `dynamic_blocks`.

## 35.5 Confusion accréditation

Interdit :

```text
Cette faculté délivre un diplôme reconnu.
Cette Voie mène à une certification officielle.
Ce parcours donne un titre professionnel reconnu par l’État.
Cette faculté est une université accréditée.
```

À éviter comme formulation récurrente :

```text
Cette Voie ne donne pas de diplôme public accrédité.
Les Parchemins ne constituent pas des diplômes publics accrédités.
Cette faculté ne prétend pas à un statut universitaire public accrédité.
```

Autorisé, une seule fois par page publique visible :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.
```

Principe :

```text
La page publique ne doit pas être structurée autour de la peur de confusion.
Elle doit être structurée autour de l’accès au savoir, de la clarté institutionnelle, de la méthode et de la pratique.
```

---

# 36. Règles IA anti-drift pour l’auteur

Une IA ou un générateur de contenu ne doit jamais inventer :

```text
nouveau slug
nouveau voie_id
nouveau faculty_id
nouveau course prefix
nouveau provider
nouveau dynamic block type
nouveau template variable
nouveau custom field
nouvelle capability
nouvel event
nouveau service
```

sans modification préalable du contrat DOC_12.

Une IA ne doit jamais confondre :

```text
Faculty Profile ≠ Atlas Voie
Atlas Voie ≠ Moodle course
Moodle category ≠ faculty JSON
Parchemin ≠ diplôme public
Dynamic block ≠ static section
Provider ≠ template
Template ≠ permission layer
Slug ≠ file path
```

---

# 37. Résumé opératoire

Un bon `*.faculty.json` est :

```text
canonique dans ses IDs;
public dans son ton;
strict dans ses providers;
sobre dans ses promesses;
centré sur l’accès au savoir;
aligné avec son Atlas;
non dupliquant;
validable par schéma;
rendu par Mustache;
sécurisé contre les targets orphelines;
protégé contre l’exposition de données privées.
```

La règle finale :

```text
Si un champ, provider, type, slug, variable ou comportement n’est pas documenté dans DOC_12,
il ne doit pas apparaître dans un profil Faculty JSON.

Si une limite institutionnelle doit être rappelée,
elle doit l’être une seule fois, sobrement,
sans déplacer le centre éditorial de la page.
```
