# TODO — Refactor propre de l’explorateur de cours public UCKK

## Objectif

Rendre l’explorateur de cours public cohérent, maintenable et centralisé.

Le comportement attendu est simple :

```text
Même données
Même rendu de carte
Même statut de page
Même résultat visuel
avant filtre et après filtre AJAX
```

## Problèmes à corriger

### 1. Rendu non centralisé des cartes de cours

Actuellement, la carte de cours est rendue par deux chemins différents :

```text
Avant filtre :
PHP → Mustache → course_explorer.mustache

Après filtre :
AJAX → course_explorer.js → création DOM manuelle
```

Conséquence : un correctif appliqué au rendu initial ne corrige pas forcément le rendu après filtre.

### 2. Statut “Cours affichés” non mis à jour après filtre

Le bloc de page :

```text
Statut de la page
Cours affichés
114 / 114
```

est généré au chargement initial, mais n’est pas relié proprement à l’explorateur AJAX.

Après filtrage, le nombre visible change, mais la metadata de page reste figée.

### 3. Contrat de données trop flou

Plusieurs noms existent pour les mêmes concepts :

```text
shortname / code
category / categorylabel / eyebrow
body / summary / description
total_label / resultscountlabel / resultsummary
```

Ces synonymes favorisent les divergences entre PHP, Mustache et JavaScript.

### 4. Metadata sans clé stable

La metadata de page est actuellement surtout descriptive :

```php
[
    'label' => 'Cours affichés',
    'value' => '114 / 114',
]
```

Il manque une clé technique stable, par exemple :

```php
[
    'key' => 'course_count',
    'label' => 'Cours affichés',
    'value' => '114 / 114',
]
```

Sans clé stable, le JavaScript devrait cibler du texte visible, ce qui est fragile.

### 5. Contrôleur trop chargé

`local/uckk/courses.php` fait trop de choses :

```text
- lecture de la requête
- lecture des cours Moodle
- filtrage
- tri
- construction des cartes
- construction des filtres
- construction de la metadata
- construction du contexte initial
- rendu de la page
```

Le contrôleur devrait seulement lire la requête, appeler un service applicatif, puis rendre la page.

---

## Fichiers à corriger

### Obligatoires

```text
local/uckk/courses.php
local/uckk/classes/output/public_page.php
local/uckk/classes/external/search_public_courses.php
local/uckk/templates/public/metadata.mustache
local/uckk/templates/pages/course_explorer.mustache
local/uckk/amd/src/course_explorer.js
```

### À créer

```text
local/uckk/templates/components/course_card.mustache
local/uckk/classes/local/course_explorer.php
```

Nom alternatif acceptable pour la classe :

```text
local/uckk/classes/local/public_courses.php
```

### Optionnel, mais recommandé

```text
local/uckk/tests/behat/local_uckk_course_explorer.feature
```

---

## Contrat de données cible

### Vocabulaire canonique

Utiliser ces noms partout où possible :

```text
coursecode      = code public du cours, ex. UCKK-AS251
pathwaylabel    = libellé de voie, ex. 04 — Voie de l’Architecture sociotechnique
summary         = résumé court du cours
visiblecount    = nombre de cours affichés après filtres
totalcount      = nombre total de cours publics
statuslabel     = texte complet de statut, ex. 14 / 114
courses         = liste des cartes de cours
hasmore         = pagination disponible
```

### Exemple de réponse AJAX cible

```json
{
  "courses": [],
  "visiblecount": 14,
  "totalcount": 114,
  "statuslabel": "14 / 114",
  "hasmore": false,
  "page": 1,
  "perpage": 12
}
```

### Exemple de contexte initial cible

Le contexte initial PHP doit exposer les mêmes concepts :

```php
[
    'courses' => $courses,
    'visiblecount' => $visiblecount,
    'totalcount' => $totalcount,
    'statuslabel' => $visiblecount . ' / ' . $totalcount,
    'hasmore' => $hasmore,
]
```

---

## Plan de refactor

### Étape 1 — Créer un partial unique pour les cartes

Créer :

```text
local/uckk/templates/components/course_card.mustache
```

Responsabilité unique : rendre une carte de cours.

Le partial doit afficher :

```text
Titre
Résumé
Voie sans libellé “Voie”
Code sans libellé “Numéro de cours”
```

Rendu attendu :

```text
Boucles de rétroaction clinique et apprentissage institutionnel
Appliquer les principes de feedback, diagnostic, suivi et apprentissage institutionnel au domaine de la santé.

04 — Voie de l’Architecture sociotechnique

UCKK-AS251
```

### Étape 2 — Faire utiliser le partial par le rendu initial

Modifier :

```text
local/uckk/templates/pages/course_explorer.mustache
```

Remplacer le HTML interne de carte par :

```mustache
{{> local_uckk/components/course_card }}
```

### Étape 3 — Faire utiliser le même partial par AJAX

Modifier :

```text
local/uckk/amd/src/course_explorer.js
```

Remplacer la création DOM manuelle des cartes par le rendu via Moodle templates :

```js
import Templates from 'core/templates';

const renderCourseCard = async course => {
    return Templates.render('local_uckk/components/course_card', course);
};
```

Puis injecter le HTML retourné dans la région résultats.

Le JavaScript ne doit plus décider du HTML exact d’une carte.

### Étape 4 — Ajouter une clé stable aux metadata

Modifier :

```text
local/uckk/courses.php
```

Faire en sorte que la metadata “Cours affichés” contienne une clé stable :

```php
[
    'key' => 'course_count',
    'label' => 'Cours affichés',
    'value' => $visiblecount . ' / ' . $totalcount,
]
```

### Étape 5 — Exporter la clé metadata

Modifier :

```text
local/uckk/classes/output/public_page.php
```

S’assurer que l’export metadata conserve :

```text
key
label
value
```

Et ajoute au besoin :

```text
region
classes
```

Exemple de région :

```text
page-metadata-course-count
```

### Étape 6 — Exposer une région DOM stable

Modifier :

```text
local/uckk/templates/public/metadata.mustache
```

Pour la metadata avec `key = course_count`, rendre un attribut stable :

```html
<dd
    class="local-uckk-public-meta__value"
    data-region="course-count-summary"
>
    {{value}}
</dd>
```

Éviter de cibler le texte visible “Cours affichés”.

### Étape 7 — Mettre à jour le statut après AJAX

Modifier :

```text
local/uckk/amd/src/course_explorer.js
```

Après chaque réponse AJAX, mettre à jour :

```text
[data-region="course-status"]
[data-region="course-count-summary"]
```

Le champ metadata doit recevoir :

```js
response.statuslabel
```

ou, en fallback contrôlé :

```js
`${response.visiblecount} / ${response.totalcount}`
```

### Étape 8 — Formaliser le service applicatif

Créer :

```text
local/uckk/classes/local/course_explorer.php
```

Responsabilités :

```text
- lire les cours publics visibles
- construire les cartes
- filtrer
- trier
- paginer
- produire le contrat initial
- produire le contrat AJAX
```

Le contrôleur `courses.php` et le service externe `search_public_courses.php` doivent appeler cette même classe.

### Étape 9 — Alléger le contrôleur

Modifier :

```text
local/uckk/courses.php
```

Objectif final :

```php
$state = course_explorer::request_state();
$context = course_explorer::initial_context($state);

$definition = public_pages::definition('courses');
$definition['course_explorer'] = $context;
$definition['metadata'] = course_explorer::page_metadata($context);

echo $OUTPUT->render(new public_page('courses', $definition));
```

### Étape 10 — Aligner le service AJAX

Modifier :

```text
local/uckk/classes/external/search_public_courses.php
```

Le service AJAX doit retourner le même contrat que le contexte initial :

```text
courses
visiblecount
totalcount
statuslabel
hasmore
page
perpage
```

Il ne doit pas reconstruire une logique parallèle.

---

## Critères d’acceptation

### Avant filtre

Le rendu initial doit afficher une carte comme ceci :

```text
Titre du cours
Résumé du cours

Voie affichée sans label

Code affiché sans label
```

### Après filtre

Après sélection d’une voie :

```text
- les cartes gardent exactement le même HTML structurel
- les libellés “Voie” et “Numéro de cours” ne reviennent pas
- le statut “Cours affichés” est mis à jour
- le résultat visuel reste cohérent avec le rendu initial
```

### Statut de page

Avant filtre :

```text
Cours affichés
114 / 114
```

Après filtre :

```text
Cours affichés
14 / 114
```

ou toute valeur réelle selon le filtre.

### Aucun ciblage fragile

Interdit :

```text
- cibler le texte “Cours affichés”
- reconstruire les cartes en DOM manuel dans JS
- répliquer la logique de filtrage dans plusieurs fichiers
- utiliser metadata comme fallback non typé pour deviner les champs cours
```

---

## Commandes de validation

### Build AMD

Depuis la racine Moodle locale :

```powershell
Set-Location C:\mycode\UCKK\moodle\moodle
npx grunt amd --root=public/local/uckk --no-color
```

### Purge caches

```powershell
php .\admin\cli\purge_caches.php
```

### Smoke local

```powershell
Invoke-WebRequest http://127.0.0.1:8000/local/uckk/courses.php
```

### Vérification navigateur

Avant filtre :

```js
document.querySelector('[data-region="course-count-summary"]')?.textContent.trim()
```

Après filtre :

```js
document.querySelector('[data-region="course-count-summary"]')?.textContent.trim()
```

Les deux valeurs doivent refléter l’état réel de l’explorateur.

---

## Risques

### Risque 1 — Ancien JS servi par Moodle

Si `amd/src/course_explorer.js` change, il faut rebuild AMD.

Sinon la page filtrée continuera à utiliser l’ancien comportement.

### Risque 2 — Caches Moodle

Les templates Mustache et JS compilés peuvent rester en cache.

Toujours purger après modification :

```powershell
php .\admin\cli\purge_caches.php
```

### Risque 3 — Contrat PHP/AJAX divergent

Si `courses.php` et `search_public_courses.php` ne passent pas par la même classe de service, les divergences reviendront.

---

## Non-objectifs de cette passe

Ne pas modifier :

```text
theme/uckk/*
academic_registry_json/*
admin/tool/uckkseed/*
course/format/uckk/*
```

Ne pas modifier le seed ou les données de cours.

Ne pas modifier la taxonomie pédagogique.

Ne pas faire de refonte visuelle globale.

---

## Résumé final

Cette passe doit transformer l’explorateur de cours public en composant propre :

```text
Un service de données
Un contrat stable
Un partial de carte
Un statut DOM stable
Un JS qui orchestre seulement
```

Objectif : éliminer les écarts entre rendu initial et rendu filtré.
