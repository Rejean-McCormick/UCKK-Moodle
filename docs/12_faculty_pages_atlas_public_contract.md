# DOC_12 — UCKK Faculty Pages, Atlas JSON, Moodle Sync and Public Site Contract

**Document canonique proposé :** `docs/12_faculty_pages_atlas_public_contract.md`
**Composant principal :** `local_uckk`
**Portée :** pages publiques de facultés / Voies UCKK, lecture des 10 JSON Atlas, profils publics de faculté, rendu Mustache, blocs dynamiques Moodle, synchronisation Moodle native, variables anti-dérive.
**Statut :** contrat complet d’implémentation.
**Version :** `UCKK-FACULTY-CONTRACT-0.1`
**Audience principale :** assistants IA, agents de génération de code, développeurs travaillant en parallèle, validateurs de cohérence.
**Règle de travail :** ce document définit l’ensemble complet de l’extension. Ne pas fragmenter en “phases”. Ne pas créer de noms, champs, fichiers, classes, templates, variables, slugs ou providers non documentés ici.
**Règle anti-drift :** si un fichier généré ou modifié introduit une variable absente de ce document, le fichier est incomplet jusqu’à ce que la variable soit ajoutée ici ou retirée du code.

---

# 1. Objet

Cette extension transforme les 10 JSON de Voies UCKK en pages publiques de type **faculté**, alignées avec Moodle.

Le but n’est pas de créer dix pages manuelles isolées.

Le but est de créer un système complet où :

```text
Atlas JSON
→ définit le programme canonique de chaque Voie

Faculty JSON
→ définit la page publique éditoriale de chaque faculté

Moodle
→ fournit les catégories, cours, annonces, événements, inscriptions, achèvements, badges et contenus dynamiques

local_uckk
→ lit, valide, normalise, fusionne, rend et synchronise
```

Formule canonique :

```text
Les JSON Atlas définissent ce qui est enseigné.
Les JSON Faculty définissent comment chaque faculté se présente publiquement.
Moodle exécute la formation et fournit les éléments dynamiques.
Le plugin local_uckk fait le pont entre les trois.
```

---

# 2. Décision structurante

## 2.1 Source de vérité

Il y a trois sources de vérité distinctes.

| Couche                   | Source                                                     | Autorité                    |
| ------------------------ | ---------------------------------------------------------- | --------------------------- |
| Programme pédagogique    | `atlas/voies/voie_*.json`                                  | Vérité canonique de la Voie |
| Page publique de faculté | `content/faculties/*.faculty.json`                         | Vérité éditoriale publique  |
| Activité Moodle réelle   | base Moodle, catégories, cours, forums, calendrier, badges | Vérité opérationnelle       |

Aucune couche ne doit absorber les deux autres.

## 2.2 Interdiction de duplication

Les `*.faculty.json` ne doivent pas recopier les dix cours complets, les concepts associés ou les critères de passage. Ils décident seulement quoi afficher depuis l’Atlas.

Interdit :

```json
{
  "courses": [
    {
      "cours_id": "GJS101",
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
    "show_concept_maitre": true,
    "show_concepts_associes": false
  }
}
```

## 2.3 Principe de génération

Chaque page publique de faculté est générée ainsi :

```text
faculty.php?slug=grand-jeu-social
→ faculty_registry resolves slug
→ faculty_repository loads grand-jeu-social.faculty.json
→ voie_repository loads voie_grand_jeu_social.json
→ faculty_page_builder merges both
→ dynamic_block_provider injects public Moodle data
→ faculty_page.mustache renders page
```

---

# 3. Noms canoniques

## 3.1 Nom de la fonctionnalité

| Variable                          | Valeur canonique               |
| --------------------------------- | ------------------------------ |
| `FEATURE_FACULTY_PAGES`           | `uckk_faculty_pages`           |
| `FEATURE_FACULTY_PUBLIC_PROFILE`  | `uckk_faculty_profile`         |
| `FEATURE_ATLAS_PUBLIC_PROJECTION` | `uckk_atlas_public_projection` |
| `FEATURE_ATLAS_MOODLE_SYNC`       | `uckk_atlas_moodle_sync`       |
| `FEATURE_FACULTY_DYNAMIC_BLOCKS`  | `uckk_faculty_dynamic_blocks`  |

## 3.2 Noms publics

| Objet                    | Nom public français      | Nom technique      |
| ------------------------ | ------------------------ | ------------------ |
| Page publique de Voie    | Page de faculté          | `faculty_page`     |
| Fichier éditorial public | Profil public de faculté | `faculty_profile`  |
| Programme canonique      | JSON Atlas de Voie       | `atlas_voie`       |
| Liste des facultés       | Répertoire des facultés  | `faculty_index`    |
| Bloc dynamique           | Bloc dynamique public    | `dynamic_block`    |
| Projection des cours     | Programme de faculté     | `atlas_projection` |

## 3.3 Orientation publique et note institutionnelle

Les pages publiques UCKK doivent d’abord présenter l’Univers-Cité King Klown comme une bibliothèque publique vivante, un campus ouvert et un cadre d’apprentissage familier, modernisé et accessible.

Le but immédiat n’est pas d’offrir des certifications, diplômes ou titres formels.

Le but immédiat est :

```text
diffuser le savoir
rendre les parcours lisibles
ouvrir les cours publics
organiser les archives
donner accès aux médiathèques
situer les Voies dans le Grand Jeu social
rendre les méthodes praticables
```

Formule publique principale :

```text
UCKK est une bibliothèque publique vivante et un campus ouvert pour comprendre le Grand Jeu social, jouer avec lucidité et changer les règles.
```

Formule institutionnelle courte, à utiliser au maximum une fois par page lorsque nécessaire :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

Règles :

```text
Ne pas centrer les pages publiques sur les limites d’accréditation.
Ne pas répéter la note institutionnelle dans les sections, FAQ, notices et garde-fous.
Ne pas transformer une page de Voie en avertissement juridique.
Ne pas présenter UCKK comme une promesse de certification future.
Présenter d’abord l’accès au savoir, les cours publics, les archives, les ressources et les méthodes.
```

---

# 4. Emplacement des fichiers

## 4.1 Arborescence canonique

```text
local/uckk/
  faculty.php

  atlas/
    atlas_manifest.json
    atlas_schema.json
    voies/
      voie_grand_jeu_social.json
      voie_economie.json
      voie_ecologie.json
      voie_sciences_politiques.json
      voie_linguistique_architecture_du_sens.json
      voie_metaphysique.json
      voie_ia_gouvernable.json
      voie_intervention_sociale_systemes_humains.json
      voie_architecture_sociotechnique.json
      voie_ecosysteme_digital_koa.json

  content/
    faculties/
      faculty_manifest.json
      faculty_schema.json
      grand-jeu-social.faculty.json
      economie.faculty.json
      ecologie.faculty.json
      sciences-politiques.faculty.json
      linguistique-architecture-du-sens.faculty.json
      metaphysique.faculty.json
      ia-gouvernable.faculty.json
      intervention-sociale-systemes-humains.faculty.json
      architecture-sociotechnique.faculty.json
      ecosysteme-digital-koa.faculty.json

  classes/
    local/
      atlas/
        atlas_manifest.php
        voie_repository.php
        voie_validator.php
        voie_normalizer.php
        voie_slugger.php
        voie_moodle_mapper.php
        atlas_cache.php

      faculty/
        faculty_manifest.php
        faculty_registry.php
        faculty_repository.php
        faculty_validator.php
        faculty_normalizer.php
        faculty_page_builder.php
        faculty_moodle_mapper.php
        faculty_dynamic_block_provider.php
        faculty_cache.php

      public_pages/
        faculties.php

    output/
      faculty_page.php
      faculty_course_card.php
      faculty_dynamic_block.php

  templates/
    faculty_page.mustache
    faculty_hero.mustache
    faculty_navigation.mustache
    faculty_sections.mustache
    faculty_atlas_projection.mustache
    faculty_course_card.mustache
    faculty_dynamic_block.mustache
    faculty_notice.mustache
    faculty_faq.mustache
    faculty_contact.mustache
```

## 4.2 Fichiers interdits comme source principale

Ne pas créer comme source principale :

```text
local/uckk/grand-jeu-social.php
local/uckk/economie.php
local/uckk/ecologie.php
```

Un seul contrôleur public commun est autorisé :

```text
local/uckk/faculty.php
```

## 4.3 Fichiers PHP par faculté

Les fichiers PHP par faculté ne sont pas la source du contenu.

Ils sont interdits par défaut.

Exception possible uniquement si elle est explicitement documentée :

```text
classes/local/faculty/overrides/<slug>.php
```

Ces overrides doivent être rares, testés, et ne doivent jamais remplacer `*.faculty.json`.

---

# 5. Composants Moodle impliqués

| Composant            | Rôle dans cette extension                                                                              |
| -------------------- | ------------------------------------------------------------------------------------------------------ |
| `local_uckk`         | propriétaire de la lecture Atlas, profils de faculté, pages publiques, mapping Moodle, registry, cache |
| `tool_uckkseed`      | peut générer ou appliquer catégories, cours, champs custom, badges, compétences depuis les JSON        |
| `format_uckk`        | peut rendre les cours Moodle selon le format UCKK                                                      |
| `theme_uckk`         | identité visuelle publique, sans logique métier                                                        |
| `report_uckk`        | rapports d’alignement, sync, validation, cohérence                                                     |
| `mod_uckkarchive`    | peut servir plus tard aux Kristals, preuves et archives publiques filtrées                             |
| `mod_uckkassembly`   | non requis pour les pages de facultés; utile seulement pour décisions et gouvernance                   |
| `mod_uckkchallenge`  | non requis pour les pages de facultés; utile pour défis liés aux cours                                 |
| `tool_uckkintegrity` | peut valider les garde-fous publics et signaler les dérives                                            |

---

# 6. Contrat d’autorité

## 6.1 Ordre d’autorité

Lorsqu’une IA doit décider quel champ, nom ou comportement appliquer :

```text
1. Ce document DOC_12
2. 00_master_execution_doctrine.md
3. 01_domain_boundaries_and_glossary.md
4. refactor_public_pages_contract.md
5. json_preset_reference.md
6. Les 10 JSON Atlas validés
7. Les 10 JSON Faculty validés
8. Code existant local_uckk
```

## 6.2 Règle de non-réinterprétation

Une IA ne doit pas renommer :

```text
Voie → Faculty dans les JSON Atlas
Faculty → Voie dans les JSON publics
Parchemin → badge public accrédité
Smart Vote → décision
Konnaxion → Moodle
Moodle category → Atlas concept
Concept associé → cours Moodle
```

## 6.3 Règle de portée

Cette extension concerne :

```text
pages publiques de facultés
lecture des 10 JSON Atlas
profils publics de facultés
rendu Mustache
blocs dynamiques publics
mapping Moodle natif
préparation de sync Moodle
validation anti-drift
```

Elle ne concerne pas :

```text
notes privées
progression d’un étudiant
dossiers personnels
badges personnels affichés publiquement
votes d’assemblée privés
preuves privées
Konnaxion Smart Vote
IA souveraine
accréditation publique
```

---

# 7. Les 10 facultés canoniques

## 7.1 Tableau canonique

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

## 7.2 Interdictions de slugs

Ne pas utiliser :

```text
grand_jeu_social
voie-grand-jeu-social
uckk-grand-jeu-social
faculte-grand-jeu-social
grandjeusocial
```

Utiliser :

```text
grand-jeu-social
```

Même règle pour les autres facultés : slugs en minuscules, sans accents, séparés par tirets.

## 7.3 Interdictions de `voie_id`

Ne jamais utiliser :

```text
voie_intelligence_artificielle_gouvernable
```

Utiliser :

```text
voie_ia_gouvernable
```

Ne jamais utiliser :

```text
voie_linguistique
```

Utiliser :

```text
voie_linguistique_architecture_du_sens
```

Ne jamais utiliser :

```text
voie_koa
```

Utiliser :

```text
voie_ecosysteme_digital_koa
```

---

# 8. Schéma Atlas de Voie

## 8.1 Nom du schéma

| Variable               | Valeur                                 |
| ---------------------- | -------------------------------------- |
| `ATLAS_SCHEMA_VERSION` | `UCKK-ATLAS-0.2-draft`                 |
| `ATLAS_SCHEMA_FILE`    | `local/uckk/atlas/atlas_schema.json`   |
| `ATLAS_MANIFEST_FILE`  | `local/uckk/atlas/atlas_manifest.json` |
| `ATLAS_VOIE_DIR`       | `local/uckk/atlas/voies/`              |

## 8.2 Champs top-level obligatoires

Chaque `voie_*.json` doit contenir :

```json
{
  "schema_version": "UCKK-ATLAS-0.2-draft",
  "voie_id": "",
  "code": "",
  "nom": "",
  "domaine_operatoire": "",
  "niveau_vise": "Puissance opératoire",
  "titre_symbolique": "",
  "parchemin": "",
  "statut": "Voie fondatrice UCKK",
  "definition_courte": "",
  "angle_fondamental": "",
  "competence_centrale": "",
  "seuils_progression": [],
  "cours_conceptuels": [],
  "projet_final": {},
  "limites_ethiques": [],
  "relations_intervoies": [],
  "tags": []
}
```

## 8.3 Champs top-level optionnels mais recommandés

```json
{
  "titre_interne_vise": "",
  "version": "0.2 — normalisation Atlas",
  "role_dans_atlas": "",
  "principe_fondateur": "",
  "distinctions_clefs": [],
  "risques_specifiques": [],
  "exigences_gouvernance": []
}
```

## 8.4 Cours conceptuel

Chaque item dans `cours_conceptuels` doit suivre :

```json
{
  "cours_id": "",
  "ordre": 1,
  "nom": "",
  "concept_maitre": {
    "concept_id": "",
    "nom": "",
    "type": "concept_maitre",
    "definition_courte": "",
    "fonction_pedagogique": ""
  },
  "concepts_associes": [
    {
      "concept_id": "",
      "nom": "",
      "type": "concept_associe",
      "notions_fines": []
    }
  ],
  "artefact_maitrise": {
    "type": "",
    "nom": "",
    "description": ""
  },
  "criteres_passage": [],
  "relations": []
}
```

## 8.5 Règles de cours

Chaque Voie doit avoir exactement 10 cours.

```text
cours_conceptuels.length = 10
ordre = 1..10
cours_id = CODE + numéro à trois chiffres
```

Exemples :

```text
GJS101
GJS102
...
GJS110
```

```text
EC101
...
EC110
```

## 8.6 Règles de concepts

Chaque `concept_maitre` doit avoir :

```json
"type": "concept_maitre"
```

Chaque concept associé doit avoir :

```json
"type": "concept_associe",
"notions_fines": []
```

Les notions fines restent vides dans cette version.

Interdiction :

```json
"notions_fines": null
```

Autorisé :

```json
"notions_fines": []
```

## 8.7 Règle de non-duplication interne

Dans un même cours, un `concept_id` ne doit pas apparaître deux fois.

Le `concept_id` du concept-maître ne doit pas être répété dans `concepts_associes`.

---

# 9. Schéma Faculty Profile

## 9.1 Nom du schéma

| Variable                 | Valeur                                               |
| ------------------------ | ---------------------------------------------------- |
| `FACULTY_SCHEMA_VERSION` | `UCKK-FACULTY-0.1`                                   |
| `FACULTY_SCHEMA_FILE`    | `local/uckk/content/faculties/faculty_schema.json`   |
| `FACULTY_MANIFEST_FILE`  | `local/uckk/content/faculties/faculty_manifest.json` |
| `FACULTY_PROFILE_DIR`    | `local/uckk/content/faculties/`                      |

## 9.2 Champs obligatoires

Chaque `*.faculty.json` doit contenir :

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

## 9.3 `status`

Allowed values :

```text
draft
published
archived
```

Règles :

```text
draft = visible seulement aux éditeurs autorisés
published = visible publiquement
archived = non listé, affichable seulement si lien direct autorisé
```

## 9.4 `visibility`

Allowed values :

```text
public
hidden
restricted
```

Règles :

```text
public = accessible sans login
hidden = non accessible publiquement
restricted = accès contrôlé par Moodle capability
```

## 9.5 `source_atlas`

Structure obligatoire :

```json
{
  "file": "voie_grand_jeu_social.json",
  "schema_version_expected": "UCKK-ATLAS-0.2-draft",
  "sync_mode": "read_only"
}
```

Allowed `sync_mode` :

```text
read_only
preview_only
moodle_sync_allowed
```

Règles :

```text
read_only = la page lit l’Atlas sans modifier Moodle
preview_only = rendu de test, pas public
moodle_sync_allowed = les données peuvent servir au dry-run/apply admin
```

## 9.6 `moodle`

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

Allowed `enrolment_visibility` :

```text
hidden
public_info_only
login_required
enrolment_required
```

Règles :

```text
public_info_only = montrer le programme, pas inscrire automatiquement
login_required = afficher certains liens seulement aux utilisateurs connectés
enrolment_required = afficher seulement aux inscrits
hidden = ne pas afficher les liens Moodle
```

## 9.7 `identity`

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
identity.name doit correspondre au nom public de la Voie.
identity.short_name est le nom court de navigation.
identity.title_symbolique doit correspondre au JSON Atlas, sauf override volontaire documenté.
identity.domain doit correspondre à domaine_operatoire.
identity.level doit correspondre à niveau_vise.
```

## 9.8 `seo`

Structure obligatoire :

```json
{
  "title": "",
  "description": "",
  "keywords": []
}
```

Règles :

```text
seo.title ne doit pas promettre un diplôme public.
seo.description ne doit pas utiliser “université accréditée”.
keywords peut contenir UCKK, Voie, domaine, concepts publics.
```

## 9.9 `hero`

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

Allowed CTA target :

```text
#anchor
/local/uckk/...
https://...
```

Interdit :

```text
javascript:
data:
file:
```

## 9.10 `navigation`

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
Chaque target doit référencer une section, un bloc dynamique ou un élément rendu.
Ne pas créer de target orpheline.
Ne pas créer de section sans target si elle est importante.
```

## 9.11 `sections`

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

Allowed `section.type` :

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

Règles :

```text
text = body texte brut filtré
markdown = body Markdown filtré côté serveur
quote = citation éditoriale
principle = principe institutionnel
notice = avertissement
cards = cartes éditoriales
callout = bloc accentué
two_column = layout éditorial
```

## 9.12 `atlas_projection`

Structure complète :

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

Règles :

```text
show_concepts_associes = false par défaut pour page publique.
show_criteres_passage = false par défaut sauf page détaillée.
show_courses = true par défaut.
show_projet_final = true par défaut.
show_limites_ethiques = true par défaut.
```

## 9.13 `dynamic_blocks`

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

Allowed `dynamic_blocks.type` :

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

Allowed `source.provider` :

```text
moodle_forum
moodle_calendar
moodle_category
moodle_course_customfield
local_uckk_news
local_uckk_manual
none
```

## 9.14 `featured_blocks`

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

Allowed `featured_blocks.type` :

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

## 9.15 `faq`

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
Ne pas transformer la FAQ en répétition des limites de reconnaissance.
```

## 9.16 `contact`

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
target peut pointer vers contact.php ou #annonces.
Ne jamais exposer une adresse privée sans consentement.
```

## 9.17 `governance`

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

Allowed `editorial_status` :

```text
draft
review
approved
needs_update
archived
```

Règles :

```text
public_claims_guardrails est un champ de gouvernance éditoriale.
Il peut rester vide si la page ne contient pas de risque public particulier.
S’il est utilisé, il ne doit pas devenir une source de répétition publique.
Préférer une seule note institutionnelle discrète lorsque nécessaire.
```

## 9.18 `cache`

Structure obligatoire :

```json
{
  "enabled": true,
  "ttl_seconds": 3600
}
```

---

# 10. `faculty_manifest.json`

## 10.1 Objet

`faculty_manifest.json` liste les 10 facultés autorisées, leurs fichiers, slugs et état public.

Aucun slug ne doit être résolu depuis une entrée URL non listée dans ce fichier.

## 10.2 Structure

```json
{
  "schema_version": "UCKK-FACULTY-MANIFEST-0.1",
  "generated_from": "manual",
  "items": [
    {
      "faculty_id": "faculty_grand_jeu_social",
      "voie_id": "voie_grand_jeu_social",
      "slug": "grand-jeu-social",
      "faculty_file": "grand-jeu-social.faculty.json",
      "atlas_file": "voie_grand_jeu_social.json",
      "status": "published",
      "visibility": "public",
      "category_idnumber": "UCKK-GJS",
      "course_prefix": "GJS",
      "sortorder": 1
    }
  ]
}
```

## 10.3 Règles de validation

Chaque item doit satisfaire :

```text
faculty_id unique
voie_id unique
slug unique
faculty_file existe
atlas_file existe
category_idnumber unique
course_prefix unique sauf exception documentée
sortorder unique de 1 à 10
```

---

# 11. `atlas_manifest.json`

## 11.1 Structure

```json
{
  "schema_version": "UCKK-ATLAS-MANIFEST-0.1",
  "atlas_schema_version": "UCKK-ATLAS-0.2-draft",
  "items": [
    {
      "voie_id": "voie_grand_jeu_social",
      "code": "GJS",
      "nom": "Voie du Grand Jeu social",
      "file": "voie_grand_jeu_social.json",
      "course_prefix": "GJS",
      "category_idnumber": "UCKK-GJS",
      "sortorder": 1
    }
  ]
}
```

## 11.2 Règles

```text
Le manifest Atlas ne contient pas les cours.
Le manifest Atlas ne contient pas les sections publiques.
Le manifest Atlas ne contient pas les annonces.
Il pointe seulement vers les fichiers de Voies et leurs variables stables.
```

---

# 12. Mapping Atlas → Faculty → Moodle

## 12.1 Mapping top-level

| Atlas field            | Faculty field               | Moodle field                | Règle                                     |
| ---------------------- | --------------------------- | --------------------------- | ----------------------------------------- |
| `voie_id`              | `voie_id`                   | custom field `uckk_voie_id` | doit être identique                       |
| `code`                 | `moodle.course_prefix`      | course idnumber prefix      | doit être cohérent                        |
| `nom`                  | `identity.name`             | category fullname           | peut être adapté publiquement             |
| `domaine_operatoire`   | `identity.domain`           | custom field                | doit être identique ou override documenté |
| `niveau_vise`          | `identity.level`            | custom field                | doit être identique                       |
| `titre_symbolique`     | `identity.title_symbolique` | custom field                | doit être identique                       |
| `definition_courte`    | projection                  | description publique        | dérivé                                    |
| `angle_fondamental`    | projection                  | page text                   | dérivé                                    |
| `competence_centrale`  | projection                  | page text                   | dérivé                                    |
| `cours_conceptuels`    | projection                  | Moodle courses              | généré                                    |
| `projet_final`         | projection                  | capstone activity/course    | dérivé                                    |
| `limites_ethiques`     | projection                  | notice/page                 | dérivé                                    |
| `relations_intervoies` | projection                  | related faculty cards       | dérivé                                    |
| `tags`                 | SEO / filters               | custom field                | optionnel                                 |

## 12.2 Mapping cours

| Atlas course field          | Moodle course field                    |
| --------------------------- | -------------------------------------- |
| `cours_id`                  | `idnumber` and `shortname`             |
| `nom`                       | `fullname`                             |
| `ordre`                     | course sort order                      |
| `concept_maitre.concept_id` | custom field `uckk_concept_maitre_id`  |
| `concept_maitre.nom`        | custom field `uckk_concept_maitre_nom` |
| `artefact_maitrise.type`    | custom field `uckk_artefact_type`      |
| `artefact_maitrise.nom`     | description / activity title           |
| `criteres_passage`          | completion criteria / rubric text      |
| `relations`                 | future graph links                     |

## 12.3 Course idnumber convention

```text
course.idnumber = cours_id
course.shortname = cours_id
course.fullname = cours_id + " — " + nom
```

Exemple :

```text
GJS101 — Cartographie du Grand Jeu social
```

## 12.4 Hub course convention

Chaque faculté peut avoir un hub Moodle public ou semi-public.

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

Le hub sert aux annonces publiques, événements et informations générales.

---

# 13. Champs custom Moodle

## 13.1 Catégories Moodle

Custom fields recommandés pour catégories :

```text
uckk_faculty_id
uckk_voie_id
uckk_code
uckk_slug
uckk_domaine_operatoire
uckk_niveau_vise
uckk_titre_symbolique
uckk_statut
uckk_schema_version
uckk_faculty_profile_version
uckk_atlas_source_hash
uckk_faculty_source_hash
```

## 13.2 Cours Moodle

Custom fields recommandés pour cours :

```text
uckk_cours_id
uckk_voie_id
uckk_faculty_id
uckk_ordre
uckk_concept_maitre_id
uckk_concept_maitre_nom
uckk_artefact_type
uckk_artefact_nom
uckk_parchemin
uckk_atlas_source_hash
uckk_sync_status
```

## 13.3 Badges / Parchemins

Mapping recommandé :

```text
Parchemin de Puissance opératoire — Voie du Grand Jeu social
→ Moodle badge de Voie
→ idnumber: UCKK-BADGE-GJS-PO
```

Convention :

```text
UCKK-BADGE-{CODE}-PO
```

Exemples :

```text
UCKK-BADGE-GJS-PO
UCKK-BADGE-EC-PO
UCKK-BADGE-ECL-PO
UCKK-BADGE-SP-PO
UCKK-BADGE-LI-PO
UCKK-BADGE-ME-PO
UCKK-BADGE-IA-PO
UCKK-BADGE-IS-PO
UCKK-BADGE-AS-PO
UCKK-BADGE-KOA-PO
```

---

# 14. Rendu public

## 14.1 Contrôleur

Fichier unique :

```text
local/uckk/faculty.php
```

Paramètre autorisé :

```text
slug
```

Exemple :

```text
/local/uckk/faculty.php?slug=grand-jeu-social
```

Règles :

```text
slug est obligatoire.
slug doit exister dans faculty_manifest.json.
Aucun chemin de fichier ne doit être accepté depuis l’URL.
La page publique ne doit pas exiger login si visibility=public.
La page restricted doit appeler require_login().
```

## 14.2 Classe registry

```php
local_uckk\local\faculty\faculty_registry
```

Responsabilités :

```text
charger faculty_manifest.json
résoudre slug → faculty_id
résoudre faculty_id → fichiers
interdire les slugs inconnus
retourner les métadonnées minimales
```

Méthodes canoniques :

```php
public static function all(): array;
public static function get_by_slug(string $slug): array;
public static function get_by_faculty_id(string $facultyid): array;
public static function exists_slug(string $slug): bool;
```

## 14.3 Repositories

### `voie_repository`

```php
local_uckk\local\atlas\voie_repository
```

Responsabilités :

```text
lire un fichier voie_*.json
valider JSON brut
retourner array ou DTO normalisé
ne pas rendre HTML
ne pas interroger Moodle sauf si explicitement mapper
```

Méthodes :

```php
public function get_by_voie_id(string $voieid): array;
public function get_by_file(string $filename): array;
public function all(): array;
```

### `faculty_repository`

```php
local_uckk\local\faculty\faculty_repository
```

Responsabilités :

```text
lire *.faculty.json
valider le profil public
résoudre la visibilité
ne pas interroger les annonces
```

Méthodes :

```php
public function get_by_slug(string $slug): array;
public function get_by_faculty_id(string $facultyid): array;
public function all_public(): array;
```

## 14.4 Builder

```php
local_uckk\local\faculty\faculty_page_builder
```

Responsabilités :

```text
fusionner faculty profile + Atlas
appliquer atlas_projection
injecter dynamic block placeholders
demander données publiques aux providers
préparer un tableau exportable vers Mustache
ne pas faire d’évaluation privée
ne pas créer de cours
ne pas modifier Moodle
```

Méthode canonique :

```php
public function build(string $slug): array;
```

## 14.5 Output class

```php
local_uckk\output\faculty_page
```

Responsabilités :

```text
recevoir le tableau construit
implémenter export_for_template()
préparer variables Mustache
échapper/filtrer ce qui doit l’être
```

Template :

```text
local_uckk/faculty_page
```

Fichier :

```text
templates/faculty_page.mustache
```

---

# 15. Variables Mustache

## 15.1 Variables top-level

Le template `faculty_page.mustache` reçoit uniquement :

```text
page
hero
navigation
identity
sections
atlas
courses
project_final
limits
relations
dynamic_blocks
featured_blocks
faq
contact
notices
metadata
```

## 15.2 `page`

```json
{
  "slug": "",
  "faculty_id": "",
  "voie_id": "",
  "status": "",
  "visibility": "",
  "seo_title": "",
  "seo_description": "",
  "canonical_url": ""
}
```

## 15.3 `hero`

```json
{
  "eyebrow": "",
  "title": "",
  "subtitle": "",
  "summary": "",
  "primary_cta": {},
  "secondary_cta": {}
}
```

## 15.4 `identity`

```json
{
  "name": "",
  "short_name": "",
  "title_symbolique": "",
  "domain": "",
  "level": "",
  "faculty_role": "",
  "one_sentence": ""
}
```

## 15.5 `atlas`

```json
{
  "definition_courte": "",
  "angle_fondamental": "",
  "competence_centrale": "",
  "seuils_progression": [],
  "show_definition_courte": true,
  "show_angle_fondamental": true,
  "show_competence_centrale": true,
  "show_seuils_progression": true
}
```

## 15.6 `courses`

Chaque course card :

```json
{
  "cours_id": "",
  "ordre": 1,
  "nom": "",
  "fullname": "",
  "concept_maitre_nom": "",
  "concept_maitre_definition": "",
  "artefact_type": "",
  "artefact_nom": "",
  "artefact_description": "",
  "moodle_url": "",
  "is_moodle_available": false
}
```

Ne pas exposer dans la page publique par défaut :

```text
concepts_associes
criteres_passage
notes
participants
completion status
```

## 15.7 `dynamic_blocks`

Chaque bloc :

```json
{
  "id": "",
  "type": "",
  "title": "",
  "items": [],
  "has_items": false,
  "empty_state": "",
  "visibility": "public"
}
```

Le template ne décide pas les permissions.

Le provider doit filtrer avant export.

---

# 16. Blocs dynamiques

## 16.1 Règle générale

Un bloc dynamique ne stocke pas le contenu vivant dans le `*.faculty.json`.

Il stocke :

```text
type
provider
source
limit
visibility
empty_state
```

Le contenu réel vient de Moodle ou de `local_uckk`.

## 16.2 Providers autorisés

### `moodle_forum`

Usage :

```text
annonces publiques
nouvelles de la faculté
messages épinglés
```

Source :

```json
{
  "provider": "moodle_forum",
  "course_idnumber": "GJS-HUB",
  "forum_name": "Annonces"
}
```

Règles :

```text
Ne montrer que les discussions publiques.
Ne montrer aucun message de forum de cours privé.
Ne pas exposer les auteurs si le réglage public ne l’autorise pas.
```

### `moodle_calendar`

Usage :

```text
événements
rencontres publiques
dates de présentation
```

Source :

```json
{
  "provider": "moodle_calendar",
  "category_idnumber": "UCKK-GJS"
}
```

Règles :

```text
Ne montrer que les événements publics.
Ne pas afficher événements privés d’utilisateur.
```

### `moodle_category`

Usage :

```text
liste de cours Moodle associés
```

Source :

```json
{
  "provider": "moodle_category",
  "category_idnumber": "UCKK-GJS"
}
```

Règles :

```text
Afficher seulement les cours visibles publiquement.
Si un cours exige login, afficher une notice au lieu d’un lien direct d’inscription.
```

### `local_uckk_news`

Usage :

```text
annonces éditoriales stockées dans local_uckk
```

Source :

```json
{
  "provider": "local_uckk_news",
  "faculty_id": "faculty_grand_jeu_social"
}
```

### `local_uckk_manual`

Usage :

```text
blocs statiques contrôlés par JSON
```

Source :

```json
{
  "provider": "local_uckk_manual"
}
```

### `none`

Usage :

```text
placeholder sans source active
```

Source :

```json
{
  "provider": "none"
}
```

## 16.3 Types de blocs

### `announcements`

Champs item :

```json
{
  "title": "",
  "summary": "",
  "date": "",
  "url": "",
  "source_label": ""
}
```

### `events`

Champs item :

```json
{
  "title": "",
  "date_start": "",
  "date_end": "",
  "location": "",
  "url": ""
}
```

### `moodle_course_list`

Champs item :

```json
{
  "cours_id": "",
  "fullname": "",
  "summary": "",
  "url": "",
  "availability_label": ""
}
```

### `related_faculties`

Champs item :

```json
{
  "faculty_id": "",
  "slug": "",
  "name": "",
  "relation": "",
  "url": ""
}
```

---

# 17. Sécurité publique

## 17.1 Données interdites en page publique

Ne jamais afficher :

```text
notes
feedback privé
progression individuelle
achèvement individuel
statut d’inscription individuel
liste d’étudiants
soumissions
preuves privées
votes privés
rapports privés
commentaires internes
cas d’intégrité
données Konnaxion personnelles
```

## 17.2 Données autorisées

Autorisé :

```text
nom public de la faculté
description publique
programme de cours
compétence centrale
projet final
limites éthiques
relations intervoies
annonces explicitement publiques
événements explicitement publics
cours visibles
liens vers Moodle selon permissions
note institutionnelle unique si nécessaire
```

## 17.3 Filtrage

Tout contenu texte issu des JSON doit passer par un filtre serveur approprié avant rendu.

Règles :

```text
Ne pas injecter HTML brut non filtré.
Ne pas faire confiance au JSON comme HTML sûr.
Ne pas rendre Markdown sans filtration.
Ne pas construire d’URL sans validation.
```

## 17.4 Retenue éditoriale publique

Les pages publiques ne doivent pas répéter les limites de reconnaissance comme thème central.

La règle éditoriale est :

```text
une page publique doit ouvrir le savoir avant de limiter les attentes
```

La note institutionnelle courte peut apparaître une seule fois lorsque le contexte l’exige :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

Cette note ne doit pas être dupliquée dans :

```text
la section héro
les sections principales
les blocs featured
la FAQ
les notices automatiques
les garde-fous publics
les cartes de cours
```

Sauf nécessité explicite, les pages doivent privilégier :

```text
bibliothèque publique
diffusion immédiate du savoir
cadre d’apprentissage ouvert
cours publics
archives
médiathèque
assemblées
défis
méthodes praticables
```

---

# 18. Cache

## 18.1 Variables de cache

| Variable                | Valeur                             |
| ----------------------- | ---------------------------------- |
| `CACHE_FACULTY_PROFILE` | `local_uckk/faculty_profile`       |
| `CACHE_ATLAS_VOIE`      | `local_uckk/atlas_voie`            |
| `CACHE_FACULTY_PAGE`    | `local_uckk/faculty_page`          |
| `CACHE_DYNAMIC_BLOCK`   | `local_uckk/faculty_dynamic_block` |

## 18.2 Clés de cache

```text
faculty_profile:{slug}:{hash}
atlas_voie:{voie_id}:{hash}
faculty_page:{slug}:{atlas_hash}:{faculty_hash}
dynamic_block:{slug}:{block_id}:{provider}:{hash}
```

## 18.3 Source hash

Chaque fichier lu doit produire un hash :

```text
sha256(file contents)
```

Variables :

```text
atlas_source_hash
faculty_source_hash
merged_page_hash
```

## 18.4 Invalidation

Vider cache quand :

```text
un voie_*.json change
un *.faculty.json change
faculty_manifest.json change
atlas_manifest.json change
un réglage local_uckk lié aux pages change
un provider dynamique demande refresh
```

---

# 19. Sync Moodle

## 19.1 Mode lecture seule

Par défaut :

```text
Faculty page generation = read-only
```

Aucun cours, badge ou champ custom n’est créé pendant une visite publique.

## 19.2 Mode dry-run

Commande ou écran admin :

```text
tool_uckkseed / local_uckk atlas sync dry-run
```

Le dry-run produit :

```text
catégories à créer
catégories à mettre à jour
cours à créer
cours à mettre à jour
champs custom manquants
badges à créer
diffs de hash
erreurs de validation
```

## 19.3 Mode apply

Autorisé uniquement via admin Moodle ou outil seed avec capability.

Ne jamais lancer depuis une page publique.

## 19.4 Statuts sync

Allowed values :

```text
not_synced
in_sync
changed_in_json
changed_in_moodle
conflict
missing_in_moodle
missing_in_json
sync_error
```

## 19.5 Conflict rule

Si Moodle a été modifié manuellement et que le JSON a changé :

```text
status = conflict
ne pas écraser automatiquement
produire un rapport
demander décision admin
```

---

# 20. Capabilities

## 20.1 Capabilities nouvelles proposées

| Variable                       | Capability                         | Usage                                          |
| ------------------------------ | ---------------------------------- | ---------------------------------------------- |
| `CAP_VIEW_PUBLIC_FACULTIES`    | `local/uckk:viewpublicfaculties`   | Voir pages publiques restreintes si nécessaire |
| `CAP_MANAGE_FACULTY_PROFILES`  | `local/uckk:managefacultyprofiles` | Gérer profils publics de faculté               |
| `CAP_VALIDATE_ATLAS_JSON`      | `local/uckk:validateatlasjson`     | Lancer validation Atlas                        |
| `CAP_SYNC_ATLAS_MOODLE`        | `local/uckk:syncatlasmoodle`       | Lancer dry-run/apply sync Moodle               |
| `CAP_VIEW_FACULTY_SYNC_REPORT` | `local/uckk:viewfacultysyncreport` | Voir rapport de sync                           |
| `CAP_PURGE_FACULTY_CACHE`      | `local/uckk:purgefacultycache`     | Purger cache pages facultés                    |

## 20.2 Règles

```text
Les visiteurs anonymes peuvent voir visibility=public.
Les pages restricted exigent require_login().
Les actions admin exigent capability.
Aucun symbolic title ne donne permission.
```

---

# 21. Événements

## 21.1 Événements proposés

| Variable                            | Event class                                    | Quand                                    |
| ----------------------------------- | ---------------------------------------------- | ---------------------------------------- |
| `EVENT_FACULTY_PROFILE_VIEWED`      | `local_uckk\event\faculty_profile_viewed`      | page faculty consultée si logging activé |
| `EVENT_FACULTY_PROFILE_VALIDATED`   | `local_uckk\event\faculty_profile_validated`   | validation admin                         |
| `EVENT_ATLAS_VOIE_VALIDATED`        | `local_uckk\event\atlas_voie_validated`        | validation d’un JSON Atlas               |
| `EVENT_ATLAS_SYNC_DRYRUN_COMPLETED` | `local_uckk\event\atlas_sync_dryrun_completed` | dry-run terminé                          |
| `EVENT_ATLAS_SYNC_APPLIED`          | `local_uckk\event\atlas_sync_applied`          | sync appliquée                           |
| `EVENT_FACULTY_CACHE_PURGED`        | `local_uckk\event\faculty_cache_purged`        | cache purgé                              |

## 21.2 Règles

```text
Ne pas logger de données privées dans description.
Ne pas logger tout le contenu JSON.
Logger seulement ids, status, counts, hashes.
```

---

# 22. Services

## 22.1 Services internes PHP

```text
local_uckk\local\atlas\voie_repository
local_uckk\local\atlas\voie_validator
local_uckk\local\atlas\voie_normalizer
local_uckk\local\atlas\voie_moodle_mapper

local_uckk\local\faculty\faculty_registry
local_uckk\local\faculty\faculty_repository
local_uckk\local\faculty\faculty_validator
local_uckk\local\faculty\faculty_normalizer
local_uckk\local\faculty\faculty_page_builder
local_uckk\local\faculty\faculty_dynamic_block_provider
local_uckk\local\faculty\faculty_moodle_mapper
```

## 22.2 Services externes optionnels

Ne pas exposer tant que non nécessaire.

Si exposés :

```text
local_uckk_validate_faculty_profile
local_uckk_validate_atlas_voie
local_uckk_get_faculty_public_page
local_uckk_get_faculty_sync_report
local_uckk_run_atlas_sync_dryrun
```

Règles :

```text
Chaque service externe exige db/services.php.
Chaque service externe exige classes/external/*.
Chaque service externe valide paramètres et retours.
Chaque service externe vérifie capabilities.
```

---

# 23. Templates

## 23.1 Template principal

```text
templates/faculty_page.mustache
```

Rôle :

```text
shell global de page faculté
aucune logique métier
aucun appel DB
aucune décision de permission
aucun fallback de sécurité
```

## 23.2 Partials

```text
templates/faculty_hero.mustache
templates/faculty_navigation.mustache
templates/faculty_sections.mustache
templates/faculty_atlas_projection.mustache
templates/faculty_course_card.mustache
templates/faculty_dynamic_block.mustache
templates/faculty_notice.mustache
templates/faculty_faq.mustache
templates/faculty_contact.mustache
```

## 23.3 Règle anti-variable sauvage

Si un template utilise :

```mustache
{{new_variable}}
```

alors `new_variable` doit être documentée ici dans la section variables Mustache avant commit.

---

# 24. Lang strings

## 24.1 Composant

```text
lang/fr/local_uckk.php
lang/en/local_uckk.php
```

## 24.2 Strings minimales

```php
$string['faculty'] = 'Faculté';
$string['faculties'] = 'Facultés';
$string['facultyprofile'] = 'Profil public de faculté';
$string['facultyatlasprogram'] = 'Programme de la Voie';
$string['facultycourses'] = 'Cours publics';
$string['facultyannouncements'] = 'Annonces de la voie';
$string['facultyevents'] = 'Événements publics';
$string['facultyprojectfinal'] = 'Projet final';
$string['facultyethicallimits'] = 'Limites éthiques';
$string['facultyrelations'] = 'Relations avec les autres Voies';
$string['internalrecognitionnotice'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['publicknowledgeframework'] = 'Bibliothèque publique et cadre d’apprentissage ouvert';
$string['openknowledgeaccess'] = 'Accès public au savoir';
```

Règles :

```text
Ne pas hardcoder les labels publics dans PHP.
Ne pas traduire les IDs.
Ne pas traduire les slugs.
Ne pas répéter la note de reconnaissance dans plusieurs chaînes publiques.
```

---

# 25. Style public

## 25.1 Classes CSS racine

```text
.local-uckk-faculty
.local-uckk-faculty-hero
.local-uckk-faculty-nav
.local-uckk-faculty-section
.local-uckk-faculty-course-card
.local-uckk-faculty-dynamic-block
.local-uckk-faculty-notice
.local-uckk-faculty-faq
```

## 25.2 Interdictions

Ne pas utiliser :

```text
.faculty
.course-card
.hero
.card
```

sans préfixe `local-uckk-`.

## 25.3 Règle

Le thème peut styliser, mais ne doit pas porter la logique.

```text
theme_uckk = présentation
local_uckk = données et rendu public
tool_uckkseed = sync/presets
```

---

# 26. Validation JSON

## 26.1 Validation Atlas

Chaque `voie_*.json` doit valider :

```text
JSON syntax valid
schema_version = UCKK-ATLAS-0.2-draft
voie_id matches manifest
code matches manifest
10 courses
course ids match prefix
orders 1..10
concept_maitre exists
concept_maitre.type = concept_maitre
concepts_associes array
all concepts_associes have type concept_associe
all notions_fines are arrays
artefact_maitrise exists
criteres_passage non-empty
projet_final exists
limites_ethiques non-empty
relations_intervoies references valid voie_id
tags array
```

## 26.2 Validation Faculty

Chaque `*.faculty.json` doit valider :

```text
JSON syntax valid
schema_version = UCKK-FACULTY-0.1
faculty_id matches manifest
voie_id references valid Atlas file
slug matches manifest
source_atlas.file exists
moodle.category_idnumber matches manifest
moodle.course_prefix matches Atlas code
identity fields present
seo fields present
hero fields present
navigation targets exist
sections have unique ids
atlas_projection complete
dynamic_blocks providers allowed
featured_blocks types allowed
faq valid
governance metadata valid
public_claims_guardrails optional and non-repetitive when present
credential-oriented wording limited to one quiet institutional note when necessary
cache config valid
```

## 26.3 Cross-file validation

Le validateur doit vérifier :

```text
faculty.voie_id == atlas.voie_id
faculty.moodle.course_prefix == atlas.code
faculty.identity.title_symbolique == atlas.titre_symbolique unless override declared
faculty.identity.domain == atlas.domaine_operatoire unless override declared
faculty.identity.level == atlas.niveau_vise
faculty.source_atlas.file points to actual atlas file
faculty.slug unique
faculty_id unique
category_idnumber unique
course_prefix unique
relations_intervoies point to valid voie_id
```

---

# 27. Variables d’override

## 27.1 Règle générale

Un override doit être explicite.

Structure :

```json
{
  "overrides": {
    "identity.title_symbolique": {
      "enabled": true,
      "reason": "Titre public simplifié",
      "source_value": "Joueur lucide",
      "public_value": "Joueur lucide"
    }
  }
}
```

## 27.2 Overrides autorisés

```text
identity.name
identity.short_name
identity.title_symbolique
identity.domain
hero.title
hero.subtitle
seo.title
seo.description
```

## 27.3 Overrides interdits

```text
voie_id
faculty_id
slug
source_atlas.file
cours_id
concept_id
schema_version
category_idnumber
course_prefix
```

---

# 28. Exemple minimal complet de `*.faculty.json`

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
    "eyebrow": "Voie fondatrice UCKK",
    "name": "Voie du Grand Jeu social",
    "short_name": "Grand Jeu social",
    "title_symbolique": "Joueur lucide",
    "domain": "Systèmes sociaux",
    "level": "Puissance opératoire",
    "faculty_role": "Voie transversale mère de l’Atlas conceptuel UCKK.",
    "one_sentence": "Lire les règles, pouvoirs, récits, preuves, ressources et transformations du monde social."
  },
  "seo": {
    "title": "Voie du Grand Jeu social — UCKK",
    "description": "La Voie du Grand Jeu social ouvre un cadre public d’apprentissage pour lire les systèmes sociaux, comprendre les règles du jeu et agir avec intégrité.",
    "keywords": [
      "UCKK",
      "Grand Jeu social",
      "systèmes sociaux",
      "pouvoir",
      "institutions",
      "récits",
      "preuves"
    ]
  },
  "hero": {
    "title": "Voie du Grand Jeu social",
    "subtitle": "Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.",
    "summary": "Cette faculté ouvre une bibliothèque publique et un cadre d’apprentissage pour cartographier les règles visibles et invisibles, les positions, les pouvoirs, les récits, les preuves, les ressources et les points de transformation d’un système social.",
    "primary_cta": {
      "label": "Explorer les cours publics",
      "target": "#cours"
    },
    "secondary_cta": {
      "label": "Voir l’Atlas des Voies",
      "target": "/local/uckk/programs.php"
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
      "label": "Cours",
      "target": "#cours"
    },
    {
      "label": "Projet final",
      "target": "#projet-final"
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
      "title": "Une faculté pour lire le jeu avant d’y jouer",
      "body": "La Voie du Grand Jeu social sert de matrice transversale aux autres Voies UCKK. Elle donne une grammaire pour lire les règles, institutions, récits, pouvoirs, langages, preuves, ressources, comportements et possibilités de transformation."
    },
    {
      "id": "pourquoi",
      "type": "text",
      "title": "Pourquoi cette Voie existe",
      "body": "Les sociétés ne sont pas seulement des ensembles d’individus. Elles sont aussi des systèmes de positions, de contraintes, de scènes publiques, de statuts, d’accès, de récits et de preuves."
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
    },
    {
      "id": "cours-moodle",
      "type": "moodle_course_list",
      "title": "Cours associés",
      "source": {
        "provider": "moodle_category",
        "category_idnumber": "UCKK-GJS"
      },
      "limit": 20,
      "visibility": "public",
      "empty_state": "Les cours Moodle associés seront affichés ici lorsqu’ils seront disponibles."
    }
  ],
  "featured_blocks": [
    {
      "type": "principle",
      "title": "Principe central",
      "body": "Voir le jeu comme jeu ne sert pas à manipuler : cela sert à agir avec plus de responsabilité."
    },
    {
      "type": "notice",
      "title": "Bibliothèque publique",
      "body": "Le but immédiat de cette page est de rendre le savoir accessible dans un cadre d’apprentissage ouvert, familier et modernisé."
    }
  ],
  "faq": [
    {
      "question": "Cette Voie est-elle obligatoire?",
      "answer": "Elle peut servir de tronc de lecture commun, car elle fournit la grammaire générale du Grand Jeu social."
    },
    {
      "question": "Quel est le but immédiat de cette Voie?",
      "answer": "Le but immédiat est la diffusion publique du savoir dans un cadre d’apprentissage ouvert. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future."
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
      "Garder la note de reconnaissance unique, discrète et non centrale; ne pas transformer la page publique en avertissement répété sur les diplômes ou statuts."
    ]
  },
  "cache": {
    "enabled": true,
    "ttl_seconds": 3600
  }
}
```

---

# 29. Tests obligatoires

## 29.1 Tests unitaires

```text
voie_repository loads all 10 JSON files
voie_validator rejects invalid schema_version
voie_validator rejects missing concept_maitre.type
voie_validator rejects non-array notions_fines
voie_validator rejects invalid relation voie_id
faculty_registry resolves all 10 slugs
faculty_repository loads all 10 faculty profiles
faculty_validator rejects unknown provider
faculty_validator rejects orphan navigation targets
faculty_page_builder builds all 10 pages
faculty_page_builder applies atlas_projection
dynamic_block_provider filters public data
moodle_mapper maps course ids correctly
```

## 29.2 Tests Behat

```text
anonymous user opens published faculty page
anonymous user cannot open hidden faculty page
anonymous user sees open public knowledge framing
anonymous user does not see repeated credential/accreditation caveats
institutional recognition note appears at most once when required
anonymous user sees 10 courses from Atlas projection
anonymous user does not see private completion status
logged-in user sees allowed Moodle links
unknown slug returns safe 404
faculty page renders empty dynamic blocks safely
```

## 29.3 Tests de fichiers

```text
All JSON files validate.
All PHP files pass php -l.
All Mustache templates use documented variables only.
No template contains business logic.
No public page accepts file path from request.
```

---

# 30. Règles IA anti-drift

## 30.1 Ne jamais inventer ces éléments

Une IA ne doit pas inventer :

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

sans l’ajouter explicitement à ce document.

## 30.2 Ne jamais confondre

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

## 30.3 Règle de génération

Avant de générer un fichier, l’IA doit identifier :

```text
1. Le composant Moodle propriétaire.
2. Le chemin exact.
3. Le schéma applicable.
4. Les variables déjà documentées.
5. Les sources de données autorisées.
6. Les données interdites.
7. Les tests associés.
```

## 30.4 Règle de modification

Avant de modifier un JSON :

```text
Valider le manifest.
Valider le schéma.
Vérifier les IDs.
Vérifier les slugs.
Vérifier les relations.
Vérifier les provider names.
Vérifier les règles de retenue éditoriale publique.
```

## 30.5 Règle de sortie

Une IA qui produit du code doit produire aussi :

```text
fichiers modifiés
variables utilisées
nouveaux champs éventuels
tests à exécuter
risques anti-drift
```

---

# 31. Définition de complétion

L’extension est complète lorsque les éléments suivants existent et sont cohérents :

```text
[ ] local/uckk/atlas/atlas_manifest.json
[ ] local/uckk/atlas/atlas_schema.json
[ ] local/uckk/atlas/voies/ contient les 10 voie_*.json
[ ] local/uckk/content/faculties/faculty_manifest.json
[ ] local/uckk/content/faculties/faculty_schema.json
[ ] local/uckk/content/faculties/ contient les 10 *.faculty.json
[ ] local/uckk/faculty.php existe
[ ] faculty.php résout uniquement les slugs autorisés
[ ] voie_repository lit les 10 JSON Atlas
[ ] voie_validator valide les 10 JSON Atlas
[ ] faculty_repository lit les 10 Faculty Profiles
[ ] faculty_validator valide les 10 Faculty Profiles
[ ] faculty_page_builder fusionne Atlas + Faculty + dynamique
[ ] templates/faculty_page.mustache existe
[ ] tous les partials faculty_* existent
[ ] les templates n’utilisent aucune variable non documentée
[ ] les pages publiques affichent les 10 cours
[ ] les pages publiques foregroundent la bibliothèque publique, les cours ouverts et la diffusion du savoir
[ ] la note institutionnelle de reconnaissance apparaît au maximum une fois lorsque nécessaire
[ ] les pages publiques n’affichent aucune donnée privée
[ ] les blocs dynamiques échouent proprement si Moodle ne contient aucune annonce
[ ] dry-run sync Moodle produit un rapport sans modifier la DB
[ ] apply sync Moodle est protégé par capability
[ ] cache peut être purgé
[ ] tests PHPUnit et Behat couvrent la fonctionnalité
```

---

# 32. Résumé exécutable

```text
Créer une couche Faculty Profile normalisée.
Garder les JSON Atlas comme source canonique pédagogique.
Garder Moodle comme source opérationnelle.
Générer les pages publiques en fusionnant Atlas + Faculty Profile + blocs dynamiques filtrés.
Ne jamais écrire dans Moodle depuis une page publique.
Ne jamais exposer de données privées.
Ne jamais inventer un champ non documenté.
Ne jamais présenter les Parchemins comme diplômes publics.
Ne jamais confondre page de faculté et cours Moodle.
Ne jamais utiliser un slug comme chemin de fichier.
Ne jamais transformer les pages publiques en répétition défensive sur l’accréditation.
```

Formule finale :

```text
Atlas définit.
Faculty Profile raconte.
Moodle exécute.
local_uckk relie.
Mustache affiche.
La page publique ouvre le savoir.
L’IA ne renomme rien.
```
