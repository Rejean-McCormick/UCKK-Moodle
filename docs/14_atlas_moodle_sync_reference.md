# DOC_14 — UCKK Atlas → Moodle Sync Reference

**Document canonique proposé :** `docs/14_atlas_moodle_sync_reference.md`
**Composant principal :** `local_uckk`
**Portée :** référence technique pour la validation, le mapping, le dry-run, l’application contrôlée et le reporting de synchronisation entre les JSON Atlas, les profils Faculty JSON et Moodle.
**Dépendance normative :** `docs/12_faculty_pages_atlas_public_contract.md`
**Dépendance auteur :** `docs/13_faculty_json_authoring_guide.md`
**Statut :** référence d’implémentation.
**Version :** `UCKK-ATLAS-MOODLE-SYNC-REFERENCE-0.2`
**Feature canonique :** `uckk_atlas_moodle_sync`
**Composant Moodle propriétaire :** `local_uckk`

---

# 1. Objet

Ce document définit comment `local_uckk` prépare, valide, compare et synchronise les informations publiques, pédagogiques et opérationnelles issues des JSON Atlas et Faculty vers Moodle.

La synchronisation ne remplace pas les JSON.

Elle produit ou met à jour uniquement des objets Moodle dérivés, contrôlés et traçables.

La projection publique doit soutenir la fonction première de l’UCKK :

```text
ouvrir une bibliothèque publique vivante;
rendre les connaissances accessibles;
organiser des parcours de lecture et d’apprentissage;
relier cours, Voies, médias, archives, défis et assemblées;
préserver les traces utiles sans exposer de données privées.
```

Formule canonique :

```text
Atlas JSON
→ vérité canonique de la Voie

Faculty JSON
→ vérité éditoriale publique de la faculté

Moodle
→ vérité opérationnelle des catégories, cours, annonces, événements, badges, accès et espaces d’apprentissage

local_uckk
→ lit, valide, normalise, compare, prépare, rapporte et applique les changements autorisés
```

---

# 2. Principe de non-destruction

La synchronisation Atlas → Moodle doit être conservatrice.

Elle doit :

```text
valider avant de mapper;
normaliser avant de comparer;
comparer avant d’écrire;
faire un dry-run avant tout apply;
journaliser uniquement ids, statuts, compteurs et hashes;
ne jamais exposer de données privées;
ne jamais supprimer du contenu Moodle non géré par UCKK sans règle explicite;
préserver la fonction publique de bibliothèque ouverte;
éviter de transformer une reconnaissance interne en promesse externe.
```

Elle ne doit pas :

```text
modifier les JSON Atlas;
modifier les JSON Faculty;
inscrire automatiquement des utilisateurs;
modifier des notes;
modifier des progressions individuelles;
publier des badges personnels;
exposer des données privées;
créer des rôles symboliques;
déduire des permissions depuis un titre symbolique;
confondre publication publique du savoir et certification formelle.
```

---

# 3. Sources de vérité

| Couche                | Source                                               | Autorité                              | Peut être modifiée par sync                  |
| --------------------- | ---------------------------------------------------- | ------------------------------------- | -------------------------------------------- |
| Programme pédagogique | `local/uckk/atlas/voies/voie_*.json`                 | Vérité canonique de la Voie           | Non                                          |
| Manifest Atlas        | `local/uckk/atlas/atlas_manifest.json`               | Index des Voies autorisées            | Non                                          |
| Page publique         | `local/uckk/content/faculties/*.faculty.json`        | Vérité éditoriale publique            | Non                                          |
| Manifest Faculty      | `local/uckk/content/faculties/faculty_manifest.json` | Index des facultés autorisées         | Non                                          |
| Moodle category       | Base Moodle                                          | Vérité opérationnelle                 | Oui, si dry-run valide                       |
| Moodle course         | Base Moodle                                          | Vérité opérationnelle                 | Oui, si dry-run valide                       |
| Moodle custom fields  | Base Moodle                                          | Métadonnées de sync                   | Oui, si dry-run valide                       |
| Moodle badge          | Base Moodle                                          | Reconnaissance interne opérationnelle | Oui, si explicitement activé                 |
| Moodle forum/calendar | Base Moodle                                          | Source dynamique publique             | Lecture prioritaire; écriture non nécessaire |

---

# 4. Fichiers impliqués

## 4.1 Fichiers JSON

```text
local/uckk/atlas/atlas_manifest.json
local/uckk/atlas/atlas_schema.json
local/uckk/atlas/voies/voie_*.json

local/uckk/content/faculties/faculty_manifest.json
local/uckk/content/faculties/faculty_schema.json
local/uckk/content/faculties/*.faculty.json
```

## 4.2 Services PHP Atlas

```text
local/uckk/classes/local/atlas/atlas_manifest.php
local/uckk/classes/local/atlas/voie_repository.php
local/uckk/classes/local/atlas/voie_validator.php
local/uckk/classes/local/atlas/voie_normalizer.php
local/uckk/classes/local/atlas/voie_slugger.php
local/uckk/classes/local/atlas/voie_moodle_mapper.php
local/uckk/classes/local/atlas/atlas_cache.php
```

## 4.3 Services PHP Faculty

```text
local/uckk/classes/local/faculty/faculty_manifest.php
local/uckk/classes/local/faculty/faculty_registry.php
local/uckk/classes/local/faculty/faculty_repository.php
local/uckk/classes/local/faculty/faculty_validator.php
local/uckk/classes/local/faculty/faculty_normalizer.php
local/uckk/classes/local/faculty/faculty_page_builder.php
local/uckk/classes/local/faculty/faculty_moodle_mapper.php
local/uckk/classes/local/faculty/faculty_cache.php
```

## 4.4 Contrôleurs et outils

```text
local/uckk/faculty_sync.php
local/uckk/faculty_validate.php
local/uckk/faculty_cache.php

local/uckk/cli/sync_atlas.php
local/uckk/cli/validate_faculties.php
local/uckk/cli/purge_faculty_cache.php
```

## 4.5 Services externes optionnels

```text
local/uckk/classes/external/validate_atlas_voie.php
local/uckk/classes/external/validate_faculty_profile.php
local/uckk/classes/external/get_faculty_sync_report.php
local/uckk/classes/external/run_atlas_sync_dryrun.php
```

## 4.6 DB declarations

```text
local/uckk/db/access.php
local/uckk/db/caches.php
local/uckk/db/services.php
```

## 4.7 Events

```text
local/uckk/classes/event/atlas_voie_validated.php
local/uckk/classes/event/atlas_sync_dryrun_completed.php
local/uckk/classes/event/atlas_sync_applied.php
local/uckk/classes/event/faculty_profile_validated.php
local/uckk/classes/event/faculty_cache_purged.php
```

---

# 5. Modes de synchronisation

## 5.1 Modes autorisés

```text
validate
dry_run
apply
report
```

## 5.2 `validate`

`validate` lit les JSON, valide les manifests, valide les schémas, vérifie les correspondances et produit un rapport sans contacter les fonctions d’écriture Moodle.

Actions autorisées :

```text
charger atlas_manifest.json;
charger faculty_manifest.json;
charger les 10 JSON Atlas;
charger les 10 JSON Faculty;
valider les schémas;
normaliser les structures;
calculer les hashes sources;
vérifier les IDs canoniques;
vérifier les slugs;
vérifier les correspondances Atlas ↔ Faculty;
retourner erreurs, warnings, compteurs.
```

Actions interdites :

```text
créer catégorie;
créer cours;
modifier cours;
modifier badge;
vider cache;
émettre event apply.
```

## 5.3 `dry_run`

`dry_run` compare l’état normalisé attendu avec l’état réel Moodle et produit un plan.

Actions autorisées :

```text
tout ce que validate autorise;
chercher catégories Moodle existantes;
chercher cours Moodle existants;
chercher custom fields existants;
calculer create/update/unchanged/warning/blocker;
produire un sync report;
émettre atlas_sync_dryrun_completed.
```

Actions interdites :

```text
écrire dans Moodle;
créer catégorie;
créer cours;
modifier cours;
modifier badge;
supprimer objet Moodle;
modifier cohort;
modifier rôle;
modifier inscription.
```

## 5.4 `apply`

`apply` applique uniquement un plan qui a passé `dry_run`.

Actions autorisées :

```text
créer ou mettre à jour catégories Moodle autorisées;
créer ou mettre à jour cours Moodle autorisés;
écrire les custom fields autorisés;
créer ou mettre à jour badges de Voie si explicitement activé;
écrire les hashes de source;
écrire le sync status;
purger caches concernés;
émettre atlas_sync_applied.
```

Actions interdites :

```text
appliquer sans validation;
appliquer sans capability;
modifier notes;
modifier progressions individuelles;
inscrire des utilisateurs;
supprimer contenu non UCKK;
modifier activités apprenantes existantes non gérées;
écrire des données privées dans les logs.
```

## 5.5 `report`

`report` lit le dernier rapport disponible ou reconstruit un rapport de comparaison sans appliquer.

Actions autorisées :

```text
résumer état Atlas;
résumer état Faculty;
résumer état Moodle;
afficher actions recommandées;
afficher warnings et blockers;
afficher source hashes;
afficher last sync status.
```

---

# 6. Capabilities

| Variable                       | Capability                         | Usage                             |
| ------------------------------ | ---------------------------------- | --------------------------------- |
| `CAP_VALIDATE_ATLAS_JSON`      | `local/uckk:validateatlasjson`     | Lancer validation Atlas           |
| `CAP_MANAGE_FACULTY_PROFILES`  | `local/uckk:managefacultyprofiles` | Valider ou gérer profils Faculty  |
| `CAP_SYNC_ATLAS_MOODLE`        | `local/uckk:syncatlasmoodle`       | Lancer dry-run ou apply           |
| `CAP_VIEW_FACULTY_SYNC_REPORT` | `local/uckk:viewfacultysyncreport` | Voir rapports de sync             |
| `CAP_PURGE_FACULTY_CACHE`      | `local/uckk:purgefacultycache`     | Purger cache des pages de faculté |

Règles :

```text
validate côté admin exige capability.
dry_run exige CAP_SYNC_ATLAS_MOODLE.
apply exige CAP_SYNC_ATLAS_MOODLE.
report exige CAP_VIEW_FACULTY_SYNC_REPORT.
Les visiteurs anonymes ne peuvent jamais lancer sync.
Aucun titre symbolique ne donne permission.
Aucune faculté ne donne permission par elle-même.
```

---

# 7. Ordre d’exécution canonique

```text
1. Charger atlas_manifest.json.
2. Charger faculty_manifest.json.
3. Valider les manifests.
4. Pour chaque item canonique :
   4.1 charger le JSON Atlas;
   4.2 valider le JSON Atlas;
   4.3 normaliser le JSON Atlas;
   4.4 charger le JSON Faculty;
   4.5 valider le JSON Faculty;
   4.6 normaliser le JSON Faculty;
   4.7 vérifier la correspondance Atlas ↔ Faculty;
   4.8 mapper vers un modèle Moodle attendu;
   4.9 lire l’état Moodle réel;
   4.10 calculer le diff;
   4.11 produire actions prévues.
5. Si mode=dry_run, retourner le plan sans écrire.
6. Si mode=apply, appliquer seulement les actions autorisées.
7. Émettre l’event correspondant.
8. Purger les caches concernés si apply modifie des données publiques.
9. Retourner un rapport complet.
```

---

# 8. Correspondance canonique des 10 facultés

| Ordre | `voie_id`                                    | `faculty_id`                                    | `slug`                                  | Nom public recommandé                          | Nom court public        | `code` | `course_prefix` | `category_idnumber` | Hub       |
| ----: | -------------------------------------------- | ----------------------------------------------- | --------------------------------------- | ---------------------------------------------- | ----------------------- | ------ | --------------- | ------------------- | --------- |
|     1 | `voie_grand_jeu_social`                      | `faculty_grand_jeu_social`                      | `grand-jeu-social`                      | Voie du Grand Jeu social                       | Grand Jeu social        | `GJS`  | `GJS`           | `UCKK-GJS`          | `GJS-HUB` |
|     2 | `voie_economie`                              | `faculty_economie`                              | `economie`                              | Voie d’Économie                                | Économie                | `EC`   | `EC`            | `UCKK-EC`           | `EC-HUB`  |
|     3 | `voie_ecologie`                              | `faculty_ecologie`                              | `ecologie`                              | Voie d’Écologie                                | Écologie                | `ECL`  | `ECL`           | `UCKK-ECL`          | `ECL-HUB` |
|     4 | `voie_sciences_politiques`                   | `faculty_sciences_politiques`                   | `sciences-politiques`                   | Voie des Sciences politiques                   | Sciences politiques     | `SP`   | `SP`            | `UCKK-SP`           | `SP-HUB`  |
|     5 | `voie_linguistique_architecture_du_sens`     | `faculty_linguistique_architecture_du_sens`     | `linguistique-architecture-du-sens`     | Voie de la Linguistique et de l’architecture du sens | Linguistique et sens | `LI`   | `LI`            | `UCKK-LI`           | `LI-HUB`  |
|     6 | `voie_metaphysique`                          | `faculty_metaphysique`                          | `metaphysique`                          | Voie de la Métaphysique                        | Métaphysique            | `ME`   | `ME`            | `UCKK-ME`           | `ME-HUB`  |
|     7 | `voie_ia_gouvernable`                        | `faculty_ia_gouvernable`                        | `ia-gouvernable`                        | Voie de la Production augmentée par l’IA        | Production IA           | `IA`   | `IA`            | `UCKK-IA`           | `IA-HUB`  |
|     8 | `voie_intervention_sociale_systemes_humains` | `faculty_intervention_sociale_systemes_humains` | `intervention-sociale-systemes-humains` | Voie de l’Intervention sociale et des systèmes humains | Intervention sociale | `IS`   | `IS`            | `UCKK-IS`           | `IS-HUB`  |
|     9 | `voie_architecture_sociotechnique`           | `faculty_architecture_sociotechnique`           | `architecture-sociotechnique`           | Voie d’Architecture sociotechnique             | Architecture sociotechnique | `AS` | `AS`            | `UCKK-AS`           | `AS-HUB`  |
|    10 | `voie_ecosysteme_digital_koa`                | `faculty_ecosysteme_digital_koa`                | `ecosysteme-digital-koa`                | Voie de l’Architecture de l’écosystème digital kOA | Écosystème digital kOA | `KOA`  | `KOA`           | `UCKK-KOA`          | `KOA-HUB` |

## 8.1 Règle de compatibilité pour la voie IA

Les identifiants techniques historiques de la voie IA restent stables afin de ne pas casser les manifests, le mapping Moodle, les catégories, les cours, les badges et les rapports de sync.

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

Le nom public et éditorial de cette voie n’est plus « IA gouvernable ».

Utiliser :

```text
Nom public = Voie de la Production augmentée par l’IA
Nom court principal = Production IA
Nom court éditorial = Production augmentée
Titre symbolique = Maître d’œuvre augmenté
Domaine public = Production outillée
```

Règle éditoriale : l’IA est présentée comme un outil de production, de création, de recherche, d’écriture, de graphisme, de programmation, de documentation, d’accompagnement, de prototypage, de vérification et de construction. Elle ne doit jamais être présentée comme une autorité, une instance de contrôle ou un sujet auquel kOA délègue le jugement humain.

Interdit dans les projections publiques :

```text
IA gouvernable comme nom public principal
faire gouverner l’IA
donner le contrôle à l’IA
déléguer la décision à l’IA
présenter l’IA comme autorité finale
```

Autorisé :

```text
Production augmentée par l’IA
Production IA
IA comme outil de construction
IA comme atelier de travail
responsabilité humaine
validation humaine
traces de travail
preuves de vérification
```

---

# 9. Mapping Atlas → Faculty → Moodle

## 9.1 Mapping top-level

| Atlas field            | Faculty field                                | Moodle target                          | Règle                           |
| ---------------------- | -------------------------------------------- | -------------------------------------- | ------------------------------- |
| `voie_id`              | `voie_id`                                    | custom field `uckk_voie_id`            | identique                       |
| `code`                 | `moodle.course_prefix`                       | course idnumber prefix                 | cohérent                        |
| `nom`                  | `identity.name`                              | category fullname                      | peut être adapté publiquement   |
| `domaine_operatoire`   | `identity.domain`                            | custom field `uckk_domaine_operatoire` | identique ou override documenté |
| `niveau_vise`          | `identity.level`                             | custom field `uckk_niveau_vise`        | identique                       |
| `titre_symbolique`     | `identity.title_symbolique`                  | custom field `uckk_titre_symbolique`   | identique                       |
| `statut`               | `governance` / sync report                   | custom field `uckk_statut`             | public seulement                |
| `definition_courte`    | `atlas_projection`                           | course/category summary                | dérivé                          |
| `angle_fondamental`    | `atlas_projection`                           | page/course summary                    | dérivé                          |
| `competence_centrale`  | `atlas_projection`                           | custom field or summary                | dérivé                          |
| `cours_conceptuels`    | `atlas_projection.show_courses`              | Moodle courses                         | généré si activé                |
| `projet_final`         | `atlas_projection.show_projet_final`         | capstone shell / summary               | dérivé                          |
| `limites_ethiques`     | `atlas_projection.show_limites_ethiques`     | notice/page summary                    | dérivé                          |
| `relations_intervoies` | `atlas_projection.show_relations_intervoies` | related faculty cards                  | dérivé                          |
| `tags`                 | `seo.keywords` / filters                     | custom field optional                  | optionnel                       |

## 9.2 Mapping cours

| Atlas course field          | Moodle course field                                           |
| --------------------------- | ------------------------------------------------------------- |
| `cours_id`                  | `course.idnumber` and `course.shortname`                      |
| `nom`                       | `course.fullname`                                             |
| `ordre`                     | course sort order                                             |
| `concept_maitre.concept_id` | custom field `uckk_concept_maitre_id`                         |
| `concept_maitre.nom`        | custom field `uckk_concept_maitre_nom`                        |
| `artefact_maitrise.type`    | custom field `uckk_artefact_type`                             |
| `artefact_maitrise.nom`     | custom field `uckk_artefact_nom`                              |
| `criteres_passage`          | completion criteria / rubric text, only if explicitly enabled |
| `relations`                 | future graph links, report-only by default                    |

## 9.3 Convention `course.idnumber`

```text
course.idnumber = cours_id
course.shortname = cours_id
course.fullname = cours_id + " — " + nom
```

Exemple :

```text
GJS101 — Cartographie du Grand Jeu social
```

Règles :

```text
course.idnumber est stable.
course.shortname est stable.
course.fullname peut être mis à jour depuis Atlas.
course.idnumber ne doit jamais être dérivé d’un slug URL.
course.idnumber ne doit jamais contenir d’espace.
```

## 9.4 Convention catégorie

```text
category.idnumber = category_idnumber
category.name = identity.short_name ou Atlas nom normalisé
category.description = résumé public filtré
```

Exemple :

```text
category.idnumber = UCKK-GJS
category.name = Grand Jeu social

category.idnumber = UCKK-IA
category.name = Production IA
```

## 9.5 Convention hub course

Chaque faculté peut avoir un cours hub.

```text
CODE-HUB
```

Exemples :

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

Usage :

```text
annonces publiques;
événements publics;
ressources publiques;
orientation générale;
lien vers les cours de la Voie;
ateliers publics de production augmentée lorsque la voie concernée est Production IA.
```

Interdit :

```text
notes privées;
progression individuelle;
badges personnels;
preuves privées;
dossiers étudiants.
```

---

# 10. Custom fields Moodle

## 10.1 Catégories Moodle

Custom fields recommandés :

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

## 10.2 Cours Moodle

Custom fields recommandés :

```text
uckk_cours_id
uckk_voie_id
uckk_faculty_id
uckk_ordre
uckk_concept_maitre_id
uckk_concept_maitre_nom
uckk_artefact_type
uckk_artefact_nom
uckk_reconnaissance_interne
uckk_atlas_source_hash
uckk_sync_status
```

## 10.3 Badges / reconnaissances internes

Convention recommandée :

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

Règles :

```text
Le badge est un shell de reconnaissance interne UCKK.
Le sync ne doit pas attribuer le badge à un utilisateur.
Le sync peut créer le badge shell seulement si l’option est explicitement activée.
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

---

# 11. Hashes de source

## 11.1 Objectif

Les hashes permettent de détecter les changements sans logger tout le JSON.

Chaque sync report doit inclure :

```text
atlas_source_hash
faculty_source_hash
combined_source_hash
```

## 11.2 Calcul recommandé

```text
atlas_source_hash = sha256(JSON normalisé Atlas)
faculty_source_hash = sha256(JSON normalisé Faculty)
combined_source_hash = sha256(atlas_source_hash + ":" + faculty_source_hash)
```

## 11.3 Règles

```text
Ne pas logger le JSON complet.
Ne pas logger les textes longs.
Ne pas logger les données privées.
Logger uniquement ids, status, counts, hashes, action summaries.
```

---

# 12. États de sync

Valeurs autorisées pour `uckk_sync_status` :

```text
not_synced
validated
dry_run_clean
dry_run_warnings
dry_run_blocked
applied
applied_with_warnings
failed
manual_review_required
```

Usage :

| Status                   | Signification                              |
| ------------------------ | ------------------------------------------ |
| `not_synced`             | Aucun sync connu                           |
| `validated`              | JSON valides, pas encore comparés à Moodle |
| `dry_run_clean`          | Aucun blocker, aucune action dangereuse    |
| `dry_run_warnings`       | Sync possible avec warnings                |
| `dry_run_blocked`        | Apply interdit                             |
| `applied`                | Apply terminé sans warning                 |
| `applied_with_warnings`  | Apply terminé avec warnings                |
| `failed`                 | Apply ou validation échoué                 |
| `manual_review_required` | Intervention humaine requise               |

---

# 13. Types d’action dans un sync plan

Valeurs autorisées :

```text
create_category
update_category
create_hub_course
update_hub_course
create_course
update_course
create_custom_field
update_custom_field
create_badge_shell
update_badge_shell
purge_cache
noop
warning
blocker
```

Interdits par défaut :

```text
delete_category
delete_course
delete_badge
delete_user_data
enrol_user
unenrol_user
assign_role
remove_role
award_badge
revoke_badge
modify_grade
modify_completion
```

---

# 14. Format du rapport de dry-run

## 14.1 Structure générale

```json
{
  "schema_version": "UCKK-ATLAS-SYNC-REPORT-0.1",
  "mode": "dry_run",
  "status": "dry_run_clean",
  "generated_at": "2026-06-13T00:00:00Z",
  "component": "local_uckk",
  "source": {
    "atlas_manifest": "local/uckk/atlas/atlas_manifest.json",
    "faculty_manifest": "local/uckk/content/faculties/faculty_manifest.json"
  },
  "counts": {
    "faculties_total": 10,
    "atlas_files_total": 10,
    "faculty_files_total": 10,
    "categories_create": 0,
    "categories_update": 0,
    "courses_create": 0,
    "courses_update": 0,
    "warnings": 0,
    "blockers": 0
  },
  "items": [],
  "warnings": [],
  "blockers": [],
  "hashes": {
    "atlas_manifest_hash": "",
    "faculty_manifest_hash": "",
    "combined_manifest_hash": ""
  }
}
```

## 14.2 Item de rapport

```json
{
  "faculty_id": "faculty_grand_jeu_social",
  "voie_id": "voie_grand_jeu_social",
  "slug": "grand-jeu-social",
  "code": "GJS",
  "category_idnumber": "UCKK-GJS",
  "hub_course_idnumber": "GJS-HUB",
  "atlas_file": "voie_grand_jeu_social.json",
  "faculty_file": "grand-jeu-social.faculty.json",
  "status": "dry_run_clean",
  "source_hashes": {
    "atlas_source_hash": "",
    "faculty_source_hash": "",
    "combined_source_hash": ""
  },
  "moodle_state": {
    "category_exists": true,
    "hub_course_exists": true,
    "course_count_expected": 10,
    "course_count_existing": 10
  },
  "actions": [
    {
      "type": "noop",
      "target": "category",
      "idnumber": "UCKK-GJS",
      "message": "Category already aligned."
    }
  ],
  "warnings": [],
  "blockers": []
}
```

## 14.3 Blocker

```json
{
  "code": "missing_atlas_file",
  "severity": "blocker",
  "faculty_id": "faculty_grand_jeu_social",
  "voie_id": "voie_grand_jeu_social",
  "message": "Atlas file is listed in manifest but cannot be loaded."
}
```

## 14.4 Warning

```json
{
  "code": "category_missing",
  "severity": "warning",
  "faculty_id": "faculty_grand_jeu_social",
  "voie_id": "voie_grand_jeu_social",
  "message": "Moodle category does not exist and would be created during apply."
}
```

---

# 15. Format du rapport `apply`

```json
{
  "schema_version": "UCKK-ATLAS-SYNC-REPORT-0.1",
  "mode": "apply",
  "status": "applied",
  "generated_at": "2026-06-13T00:00:00Z",
  "component": "local_uckk",
  "dry_run_required": true,
  "dry_run_hash": "",
  "counts": {
    "faculties_total": 10,
    "categories_created": 0,
    "categories_updated": 0,
    "hub_courses_created": 0,
    "hub_courses_updated": 0,
    "courses_created": 0,
    "courses_updated": 0,
    "badge_shells_created": 0,
    "badge_shells_updated": 0,
    "cache_purges": 0,
    "warnings": 0,
    "blockers": 0,
    "errors": 0
  },
  "items": [],
  "warnings": [],
  "errors": [],
  "hashes": {
    "atlas_manifest_hash": "",
    "faculty_manifest_hash": "",
    "combined_manifest_hash": ""
  }
}
```

Règles :

```text
apply doit référencer le dry_run_hash.
apply doit refuser si le dry-run est stale.
apply doit refuser si blockers > 0.
apply doit refuser si capability absente.
apply doit refuser si le mode source_atlas.sync_mode n’autorise pas la sync.
```

---

# 16. Blockers obligatoires

Le sync doit bloquer si :

```text
atlas_manifest.json invalide;
faculty_manifest.json invalide;
un fichier Atlas listé manque;
un fichier Faculty listé manque;
voie_id mismatch;
faculty_id mismatch;
slug mismatch;
course_prefix mismatch;
category_idnumber mismatch;
schema_version inconnue;
provider dynamic block inconnu;
cours_conceptuels n’a pas exactement 10 cours;
cours_id dupliqué;
course idnumber cible déjà un cours non UCKK protégé;
custom field requis absent et impossible à créer;
permission manquante;
sync_mode interdit;
hash dry-run stale;
tentative d’exposition de données privées;
tentative d’inscription utilisateur;
tentative de modification de note;
tentative de suppression non autorisée.
```

---

# 17. Warnings recommandés

Le sync peut warning si :

```text
category Moodle absente mais créable;
hub course absent mais créable;
course absent mais créable;
fullname Moodle diffère d’Atlas;
description Moodle diffère de la projection publique;
custom field optionnel absent;
badge shell absent et badges activés;
relations_intervoies non mappées;
criteres_passage présents mais non publiés;
concepts_associes masqués publiquement;
cache désactivé.
```

---

# 18. Règles de création des catégories

## 18.1 Input minimal

```text
faculty_id
voie_id
slug
code
category_idnumber
identity.short_name
identity.name
identity.domain
identity.level
identity.title_symbolique
```

## 18.2 Output Moodle attendu

```text
course_categories.idnumber = category_idnumber
course_categories.name = identity.short_name
course_categories.description = résumé public filtré
custom fields = métadonnées UCKK
```

## 18.3 Règles

```text
Ne pas créer deux catégories avec le même idnumber.
Ne pas prendre possession d’une catégorie non UCKK sans validation humaine.
Ne pas écraser une description manuelle si elle n’a pas été marquée gérée par UCKK.
Ne pas utiliser category_id depuis JSON comme source principale.
Préférer category_idnumber pour résolution stable.
La description publique doit privilégier le savoir accessible, les parcours, les cours, les archives et l’apprentissage ouvert.
```

---

# 19. Règles de création des cours

## 19.1 Input minimal

```text
cours_id
ordre
nom
voie_id
faculty_id
code
course_prefix
category_idnumber
concept_maitre.concept_id
concept_maitre.nom
artefact_maitrise.type
artefact_maitrise.nom
atlas_source_hash
```

## 19.2 Output Moodle attendu

```text
course.idnumber = cours_id
course.shortname = cours_id
course.fullname = cours_id + " — " + nom
course.category = category resolved by category_idnumber
course.visible = controlled by Faculty visibility rules
course.format = uckk if available; otherwise site default
course.customfields = UCKK metadata
```

## 19.3 Règles

```text
Ne pas changer idnumber d’un cours existant.
Ne pas déplacer un cours existant non UCKK sans validation humaine.
Ne pas écraser du contenu pédagogique manuel.
Ne pas supprimer activités existantes.
Ne pas créer inscriptions.
Ne pas modifier achèvements.
Ne pas modifier notes.
Le résumé public du cours doit rester orienté vers l’apprentissage, les ressources, les concepts, les pratiques et les traces utiles.
```

---

# 20. Règles de création du cours hub

## 20.1 Input minimal

```text
hub_course_idnumber
category_idnumber
identity.name
identity.short_name
hero.summary
dynamic_blocks
```

## 20.2 Output Moodle attendu

```text
course.idnumber = CODE-HUB
course.shortname = CODE-HUB
course.fullname = "Hub — " + identity.short_name
course.category = category resolved by category_idnumber
course.visible = true if profile is public or restricted
```

## 20.3 Rôle

Le hub peut porter :

```text
annonces;
calendrier public;
ressources publiques;
orientation générale;
liens vers les cours;
liens vers la médiathèque;
liens vers les archives publiques.
```

Le hub ne doit pas porter :

```text
notes privées;
progression individuelle;
preuves privées;
votes privés;
dossiers personnels.
```

---

# 21. Règles de badges / reconnaissances internes

## 21.1 Badge shell

Le sync peut préparer un badge shell de Voie si l’option est explicitement activée.

Convention :

```text
idnumber = UCKK-BADGE-{CODE}-PO
name = Reconnaissance interne — {identity.short_name}
```

## 21.2 Interdictions

Le sync ne doit jamais :

```text
attribuer un badge;
révoquer un badge;
publier une liste de détenteurs;
exposer des preuves privées;
déduire un achèvement depuis un JSON;
présenter une reconnaissance interne comme une certification formelle externe.
```

## 21.3 Formule publique unique

Si une note de reconnaissance doit être affichée publiquement, utiliser une seule formule courte :

```text
Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.
```

Cette phrase ne doit pas devenir le thème principal d’une page publique.

---

# 22. Règles de visibilité

## 22.1 Faculty visibility

| Faculty `visibility` | Effet sync recommandé                                                       |
| -------------------- | --------------------------------------------------------------------------- |
| `public`             | category/course shells publics ou visibles selon Moodle policy              |
| `hidden`             | ne pas afficher publiquement; sync possible mais visibilité Moodle prudente |
| `restricted`         | accès via login/capability; pas d’exposition publique anonyme               |

## 22.2 Enrolment visibility

| `enrolment_visibility` | Effet                                                       |
| ---------------------- | ----------------------------------------------------------- |
| `hidden`               | ne pas afficher les liens Moodle                            |
| `public_info_only`     | afficher programme public, pas d’inscription auto           |
| `login_required`       | lien visible après login                                    |
| `enrolment_required`   | lien visible seulement aux inscrits si contrôlé côté Moodle |

---

# 23. Services internes

## 23.1 `voie_repository`

Responsabilités :

```text
résoudre chemin Atlas depuis manifest;
charger JSON;
refuser chemin arbitraire;
retourner données brutes;
ne pas mapper Moodle;
ne pas valider permissions.
```

## 23.2 `voie_validator`

Responsabilités :

```text
valider schema_version;
valider champs obligatoires;
valider cours_conceptuels length = 10;
valider cours_id;
valider ordre 1..10;
valider concept_maitre;
valider concepts_associes;
retourner erreurs/warnings structurés.
```

## 23.3 `voie_normalizer`

Responsabilités :

```text
ordonner cours_conceptuels par ordre;
normaliser chaînes;
normaliser listes vides;
calculer hash source;
préparer modèle stable pour diff.
```

## 23.4 `voie_moodle_mapper`

Responsabilités :

```text
mapper Atlas normalisé vers catégories/cours/custom fields;
produire expected Moodle model;
produire action candidates;
ne pas écrire en base directement sauf méthode apply explicitement documentée;
ne jamais gérer permissions utilisateur.
```

## 23.5 `faculty_repository`

Responsabilités :

```text
charger Faculty JSON depuis manifest;
refuser chemin arbitraire;
retourner données brutes;
ne pas rendre Mustache;
ne pas appeler Moodle write APIs.
```

## 23.6 `faculty_validator`

Responsabilités :

```text
valider schema_version;
valider slug;
valider source_atlas;
valider moodle;
valider navigation;
valider dynamic_blocks;
valider providers;
valider guardrails;
retourner erreurs/warnings structurés.
```

## 23.7 `faculty_normalizer`

Responsabilités :

```text
normaliser status;
normaliser visibility;
normaliser anchors;
normaliser dynamic_blocks;
calculer hash source;
préparer modèle stable.
```

## 23.8 `faculty_moodle_mapper`

Responsabilités :

```text
mapper Faculty normalisé vers category metadata;
mapper hub course;
mapper public visibility;
mapper dynamic block source references;
produire expected Moodle model.
```

---

# 24. CLI

## 24.1 Validation

```bash
php local/uckk/cli/validate_faculties.php
```

Doit vérifier :

```text
atlas manifest;
faculty manifest;
10 Atlas JSON;
10 Faculty JSON;
correspondances;
providers;
anchors;
guardrails;
source_atlas.sync_mode.
```

## 24.2 Dry-run sync

```bash
php local/uckk/cli/sync_atlas.php --mode=dry_run
```

Options recommandées :

```text
--mode=dry_run
--faculty=grand-jeu-social
--all
--json
--verbose
```

## 24.3 Apply sync

```bash
php local/uckk/cli/sync_atlas.php --mode=apply --confirm=1
```

Règles :

```text
apply exige confirm=1;
apply exige validation complète;
apply doit refuser blockers;
apply doit produire rapport;
apply doit émettre event.
```

## 24.4 Report

```bash
php local/uckk/cli/sync_atlas.php --mode=report --json
```

---

# 25. Contrôleur admin

Fichier :

```text
local/uckk/faculty_sync.php
```

Responsabilités :

```text
require_login();
require_capability('local/uckk:syncatlasmoodle', context_system::instance());
afficher formulaire de mode;
lancer validation/dry-run/apply;
afficher rapport;
ne pas accepter chemin de fichier depuis URL;
ne pas exposer JSON brut.
```

Paramètres autorisés :

```text
mode
faculty
confirm
sesskey
format
```

Paramètres interdits :

```text
path
file
dir
json
raw
callback
```

---

# 26. Services externes

## 26.1 `local_uckk_run_atlas_sync_dryrun`

Usage :

```text
lancer dry-run depuis interface admin ou service autorisé.
```

Exigences :

```text
db/services.php;
classes/external/run_atlas_sync_dryrun.php;
validation stricte des paramètres;
capability local/uckk:syncatlasmoodle;
retour typé;
aucun apply.
```

## 26.2 `local_uckk_get_faculty_sync_report`

Usage :

```text
retourner un rapport de sync filtré.
```

Exigences :

```text
capability local/uckk:viewfacultysyncreport;
ne pas retourner JSON source complet;
ne pas retourner données privées;
retourner ids, status, actions, warnings, blockers, hashes.
```

---

# 27. Events

## 27.1 `atlas_voie_validated`

Déclenché quand :

```text
un JSON Atlas est validé.
```

Données autorisées :

```text
voie_id;
code;
status;
error_count;
warning_count;
atlas_source_hash.
```

Données interdites :

```text
contenu JSON complet;
données privées;
texte long pédagogique complet.
```

## 27.2 `atlas_sync_dryrun_completed`

Déclenché quand :

```text
un dry-run est terminé.
```

Données autorisées :

```text
mode;
status;
faculty_count;
action_count;
warning_count;
blocker_count;
combined_manifest_hash.
```

## 27.3 `atlas_sync_applied`

Déclenché quand :

```text
un apply est terminé.
```

Données autorisées :

```text
mode;
status;
created_count;
updated_count;
warning_count;
error_count;
combined_manifest_hash.
```

---

# 28. Cache

Caches concernés :

```text
local_uckk/atlas_manifest
local_uckk/atlas_voie
local_uckk/faculty_manifest
local_uckk/faculty_profile
local_uckk/faculty_page
local_uckk/faculty_dynamic_block
local_uckk/faculty_sync_report
```

Règles :

```text
validate ne purge pas.
dry_run peut écrire ou rafraîchir un rapport cache si configuré.
apply purge faculty_page et faculty_dynamic_block pour les facultés touchées.
purge manuelle exige CAP_PURGE_FACULTY_CACHE.
```

---

# 29. Sécurité

## 29.1 Chemins

Interdit :

```text
accepter un chemin depuis l’URL;
charger un fichier hors manifest;
résoudre ../;
résoudre chemin absolu;
utiliser slug comme chemin.
```

Autorisé :

```text
slug → faculty_manifest → faculty_file;
voie_id → atlas_manifest → atlas_file.
```

## 29.2 Permissions

Règles :

```text
Public page ≠ sync permission.
Faculty editor ≠ Moodle admin.
Symbolic title ≠ capability.
Reconnaissance interne ≠ permission.
```

## 29.3 Données privées

Le sync ne doit pas lire ou écrire :

```text
notes;
progression individuelle;
achèvements individuels;
dossiers personnels;
preuves privées;
votes privés;
feedback privé;
badges personnels affichés publiquement.
```

---

# 30. Stratégie de diff

## 30.1 Diff catégorie

Comparer :

```text
category.idnumber;
category.name;
category.description;
custom fields UCKK;
visibility policy.
```

Ne pas comparer :

```text
champs Moodle non UCKK;
contenu manuel non marqué managed_by=local_uckk;
statistiques;
inscriptions.
```

## 30.2 Diff cours

Comparer :

```text
course.idnumber;
course.shortname;
course.fullname;
course.category;
course.visible;
custom fields UCKK;
source hash.
```

Ne pas comparer :

```text
sections manuelles;
activités non générées;
notes;
achèvement;
enrolments;
logs.
```

## 30.3 Diff badge shell

Comparer :

```text
badge.idnumber;
badge.name;
badge.description publique;
issuer;
status shell.
```

Ne pas comparer :

```text
awards;
recipients;
evidence;
endorsements personnels.
```

---

# 31. Compatibilité avec `tool_uckkseed`

`local_uckk` peut préparer un modèle Moodle attendu.

`tool_uckkseed` peut être utilisé plus tard pour appliquer des catégories, cours, champs custom, badges ou compétences depuis des presets.

Règle :

```text
local_uckk garde le contrat Atlas/Faculty/Moodle.
tool_uckkseed garde les opérations de seed génériques.
Aucun des deux ne doit contourner validation, dry-run ou capabilities.
```

Quand `tool_uckkseed` est utilisé :

```text
local_uckk produit le plan ou preset dérivé;
tool_uckkseed valide le preset;
tool_uckkseed dry-run;
tool_uckkseed apply;
local_uckk lit le résultat et met à jour le sync report.
```

---

# 32. Compatibilité avec `format_uckk`

`format_uckk` peut rendre les cours Moodle selon l’identité UCKK.

Règle :

```text
local_uckk ne doit pas injecter de logique de format de cours.
format_uckk ne doit pas devenir source de vérité Atlas.
format_uckk doit servir l’apprentissage, l’orientation, les ressources et les traces utiles.
```

---

# 33. Compatibilité avec `theme_uckk`

`theme_uckk` fournit l’identité visuelle publique.

Règle :

```text
theme_uckk ne contient pas de logique de sync.
theme_uckk ne lit pas directement les JSON Atlas.
theme_uckk ne valide pas les profiles Faculty.
theme_uckk doit soutenir la lisibilité publique, pas remplacer la couche éditoriale.
```

---

# 34. Compatibilité avec `report_uckk`

`report_uckk` peut afficher des rapports de cohérence.

Règle :

```text
local_uckk produit les données de sync.
report_uckk peut les lire ou les présenter.
report_uckk ne doit pas appliquer de sync.
```

---

# 35. Tests obligatoires

## 35.1 PHPUnit

Fichiers concernés :

```text
local/uckk/tests/atlas_manifest_test.php
local/uckk/tests/voie_repository_test.php
local/uckk/tests/voie_validator_test.php
local/uckk/tests/voie_normalizer_test.php
local/uckk/tests/faculty_manifest_test.php
local/uckk/tests/faculty_registry_test.php
local/uckk/tests/faculty_repository_test.php
local/uckk/tests/faculty_validator_test.php
local/uckk/tests/faculty_moodle_mapper_test.php
local/uckk/tests/faculty_cache_test.php
```

Tests sync recommandés :

```text
manifest Atlas valide;
manifest Faculty valide;
10 facultés présentes;
10 JSON Atlas chargés;
10 JSON Faculty chargés;
mismatch voie_id bloque;
mismatch slug bloque;
provider inconnu bloque;
cours_conceptuels != 10 bloque;
dry-run ne modifie pas Moodle;
apply refuse sans capability;
apply refuse si blockers;
hash source change si JSON change;
course idnumber convention respectée;
category_idnumber convention respectée;
private data never appears in report.
```

## 35.2 Behat

Fichiers concernés :

```text
local/uckk/tests/behat/faculty_sync_dryrun.feature
local/uckk/tests/behat/faculty_admin_validation.feature
```

Scénarios recommandés :

```text
admin launches dry-run for all faculties;
admin sees blockers;
admin cannot apply blocked sync;
non-admin cannot access sync page;
dry-run report does not show raw JSON;
public user cannot access sync page;
cache purge event appears after apply.
```

---

# 36. Commandes de validation statique

Depuis la racine du dépôt :

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

```bash
find . -name '*.json' -print0 | xargs -0 -n1 python3 -m json.tool >/dev/null
```

````bash
grep -RIn --include='*.php' '```' local/uckk || true
````

```bash
grep -RIn --include='*.js' '<?php\|require_once\|defined *(\|optional_param\|required_param\|require_login\|require_capability' local/uckk/amd/src || true
```

Commandes plugin attendues :

```bash
php local/uckk/cli/validate_faculties.php
php local/uckk/cli/sync_atlas.php --mode=dry_run --all --json
```

---

# 37. Checklist avant `apply`

```text
[ ] Tous les JSON Atlas sont valides.
[ ] Tous les JSON Faculty sont valides.
[ ] atlas_manifest.json est valide.
[ ] faculty_manifest.json est valide.
[ ] Les 10 facultés canoniques sont présentes.
[ ] Aucun slug non canonique.
[ ] Aucun voie_id non canonique.
[ ] Aucun faculty_id non canonique.
[ ] Aucun provider inconnu.
[ ] Aucun target orphelin.
[ ] Aucun cours_id dupliqué.
[ ] Chaque Voie a exactement 10 cours.
[ ] Le dry-run est récent.
[ ] Le dry-run n’a aucun blocker.
[ ] Les warnings sont acceptés manuellement.
[ ] La capability sync est présente.
[ ] Le sesskey est valide.
[ ] Le mode apply a confirmation explicite.
[ ] Le rapport ne contient pas de JSON brut.
[ ] Le rapport ne contient pas de données privées.
[ ] Les textes publics projetés restent orientés vers l’apprentissage ouvert, les ressources, les cours, les archives et la diffusion du savoir.
```

---

# 38. Erreurs fréquentes

## 38.1 Confondre JSON et Moodle

Interdit :

```text
modifier voie_*.json depuis apply;
modifier *.faculty.json depuis apply.
```

Autorisé :

```text
Moodle reçoit une projection dérivée et traçable.
```

## 38.2 Prendre le slug comme chemin

Interdit :

```text
/local/uckk/faculty_sync.php?file=../../secret.json
```

Autorisé :

```text
slug → manifest → file canonique.
```

## 38.3 Écrire sans dry-run

Interdit :

```text
mode=apply direct sans plan.
```

Autorisé :

```text
validate → dry_run → review → apply.
```

## 38.4 Logger trop de données

Interdit :

```text
logger le JSON complet;
logger tous les critères;
logger les textes longs;
logger des données personnelles.
```

Autorisé :

```text
logger ids, status, counts, hashes.
```

## 38.5 Confondre reconnaissance interne et promesse externe

Interdit :

```text
présenter une reconnaissance interne comme une promesse formelle externe;
faire porter la page publique par une notice défensive;
répéter la limite institutionnelle dans chaque bloc visible.
```

Autorisé :

```text
présenter les Voies comme parcours publics de savoir;
présenter les cours comme espaces d’apprentissage;
présenter les archives comme mémoire consultable;
utiliser une seule note institutionnelle courte lorsque nécessaire.
```

---

# 39. Résumé opératoire

La synchronisation correcte est :

```text
manifests d’abord;
JSON ensuite;
validation avant normalisation;
normalisation avant mapping;
mapping avant diff;
diff avant dry-run;
dry-run avant apply;
apply avec capability;
logs avec hashes;
cache purgé après changement public;
aucune donnée privée;
aucune promesse institutionnelle implicite.
```

Règle finale :

```text
Si un objet Moodle ne peut pas être relié à un ID canonique UCKK,
à un manifest valide,
à un hash source,
et à une action de dry-run,
alors le sync ne doit pas le modifier.
```

Règle finale propre à la voie IA :

```text
Si une projection publique de la voie IA présente l’IA comme gouvernante, autorité finale ou instance de contrôle,
elle doit être rejetée ou réécrite.

La projection publique doit parler de Production augmentée par l’IA,
de Production IA,
de Maître d’œuvre augmenté,
de responsabilité humaine,
et de traces vérifiables de construction.
```

Règle éditoriale finale :

```text
Si une projection publique rend l’UCKK plus défensive que transmissive,
elle doit être réécrite.

La page publique doit d’abord ouvrir le savoir.
La limite institutionnelle doit rester claire, courte et secondaire.
```
