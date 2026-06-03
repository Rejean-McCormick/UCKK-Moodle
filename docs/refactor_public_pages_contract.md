# Refactor public pages — contrat d’exécution

**Projet :** UCKK-Moodle  
**Composant Moodle :** `local_uckk`  
**Portée :** pages publiques institutionnelles `local/uckk`  
**Règle de travail :** documentation d’abord, puis code fichier par fichier.  
**Statut :** contrat à valider avant modification du code.

---

## 1. Objectif

Refactoriser les pages publiques UCKK sans changer leur responsabilité fonctionnelle :

```text
État actuel
- contrôleurs publics minces ;
- un registre central lourd : classes/local/public_pages.php ;
- un template public_page.mustache trop large ;
- certains blocs spécialisés, surtout Médiathèque, déjà séparés partiellement.

État cible
- un contrat de variables fixe ;
- un shell Mustache public minimal ;
- des partials Mustache réutilisables ;
- une définition PHP par page publique ;
- un registre central réduit à un routeur/agrégateur ;
- zéro décision métier dans les templates ;
- zéro HTML lourd dans les contrôleurs publics.
```

Le refactor doit améliorer la maintenabilité, pas réécrire le produit.

---

## 2. Hors-scope

Ce refactor ne doit pas :

```text
- changer le modèle de données ;
- ajouter de nouvelles tables ;
- modifier les permissions Moodle ;
- modifier les rôles ;
- décider les droits d’accès aux médias ;
- exposer des données privées ;
- changer les règles d’intégrité ;
- créer une nouvelle API AJAX ;
- déplacer la logique média de mod_uckkarchive vers local_uckk ;
- transformer les reconnaissances UCKK en diplômes publics accrédités ;
- refaire tout styles.css ;
- refactoriser canon.php, pathways.php ou les pages admin.
```

---

## 3. Principe d’exécution

Aucune modification massive.

```text
1. Un fichier est modifié ou créé.
2. Le fichier est relu contre ce contrat.
3. La validation minimale du fichier est faite.
4. Seulement ensuite, on passe au fichier suivant.
```

Si un fichier crée une ambiguïté de variable, on arrête le codage et on met d’abord ce contrat à jour.

---

## 4. Variables globales fixes

| Variable | Valeur fixe |
|---|---|
| `COMPONENT` | `local_uckk` |
| `PLUGIN_ROOT` | `local/uckk` |
| `PUBLIC_ROUTE_ROOT` | `/local/uckk/` |
| `PUBLIC_PAGE_REGISTRY_CLASS` | `local_uckk\local\public_pages` |
| `PUBLIC_PAGE_RENDERABLE_CLASS` | `local_uckk\output\public_page` |
| `PUBLIC_PAGE_TEMPLATE` | `local_uckk/public_page` |
| `PUBLIC_PAGE_LAYOUT` | `local_uckk_public` |
| `PUBLIC_CSS` | `/local/uckk/styles.css` |
| `PUBLIC_PAGE_TYPE` | `public` |
| `PUBLIC_IS_PUBLIC` | `true` |
| `DESIGN_VERSION` | `2026-layout-v2` |
| `FONT_STRATEGY` | `libre-baskerville-primary-eb-garamond-accent` |

---

## 5. Slugs publics fixes

### 5.1 Slugs gérés par le shell public dans ce refactor

```text
home
about
programs
courses
challenges
assemblies
integrity
archives
mediatheque
news
contact
```

### 5.2 Route existante hors lot principal

```text
campus
```

`campus.php` existe dans `local/uckk`, mais son rendu actuel ne passe pas par `local_uckk\local\public_pages` et `local_uckk\output\public_page`. Il ne doit pas être migré dans le même lot que les pages publiques ci-dessus sauf décision explicite.

Variable de statut :

```text
CAMPUS_PUBLIC_REFACTOR_STATUS = out_of_scope_batch_1
```

---

## 6. Routes publiques fixes

| Slug | Route | Contrôleur |
|---|---|---|
| `home` | `/local/uckk/index.php` | `index.php` |
| `about` | `/local/uckk/about.php` | `about.php` |
| `programs` | `/local/uckk/programs.php` | `programs.php` |
| `courses` | `/local/uckk/courses.php` | `courses.php` |
| `challenges` | `/local/uckk/challenges.php` | `challenges.php` |
| `assemblies` | `/local/uckk/assemblies.php` | `assemblies.php` |
| `integrity` | `/local/uckk/integrity.php` | `integrity.php` |
| `archives` | `/local/uckk/archives.php` | `archives.php` |
| `mediatheque` | `/local/uckk/mediatheque.php` | `mediatheque.php` |
| `news` | `/local/uckk/news.php` | `news.php` |
| `contact` | `/local/uckk/contact.php` | `contact.php` |

---

## 7. Contrat des contrôleurs publics

Tous les contrôleurs publics du lot principal doivent rester minces.

Forme cible générale :

```php
$context = context_system::instance();
$slug = 'about';

\local_uckk\local\public_pages::setup_page($slug, $context);

$definition = \local_uckk\local\public_pages::definition($slug);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));
echo $OUTPUT->footer();
```

Règles :

```text
- pas de contenu institutionnel dans les contrôleurs ;
- pas de HTML direct ;
- pas de classes CSS locales construites à la main ;
- pas de navigation locale dupliquée ;
- pas de logique d’archives, de rôles, de badges, d’intégrité ou d’inscription ;
- exception contrôlée : mediatheque.php peut lire les paramètres URL et préparer l’état initial AMD.
```

---

## 8. Exception contrôlée : `mediatheque.php`

`mediatheque.php` reste un contrôleur mince, mais il possède des paramètres publics nécessaires à l’explorateur.

Variables autorisées dans `mediatheque.php` :

```text
cmid
archiveid
q
type
mediatype
collection
tag
source
advisory
cultural
audience
lang
validation
sort
page
perpage
item
```

État initial AMD autorisé :

```text
rootId
service
cmid
archiveid
query
filters
page
perpage
sort
sitewide
```

Service AJAX fixe :

```text
MEDIATHEQUE_SEARCH_SERVICE = mod_uckkarchive_search_mediatheque
```

Règles :

```text
- local_uckk affiche la surface publique ;
- mod_uckkarchive reste propriétaire des médias, droits, avis de contenu, protocoles culturels et filtres d’accès ;
- mediatheque.php ne doit pas interroger directement les tables média ;
- mediatheque.php peut passer des overrides à public_page :
  - mediatheque_explorer_id
  - mediatheque_initial_state
  - has_mediatheque_explorer
```

---

## 9. Classes PHP finales

### 9.1 Classe registre conservée

```text
classes/local/public_pages.php
```

Classe :

```php
namespace local_uckk\local;

final class public_pages
```

Responsabilités finales :

```text
- nettoyer le slug ;
- configurer PAGE ;
- configurer le breadcrumb ;
- résoudre le titre de page ;
- fournir la navigation commune ;
- appeler la classe de définition correspondant au slug ;
- fusionner la base commune et la définition de page ;
- ne plus contenir le contenu complet de toutes les pages.
```

Méthodes publiques conservées :

```php
public static function setup_page(string $slug, ?context $context = null): void;
public static function definition(string $slug): array;
```

Méthodes internes autorisées :

```php
private static function base_definition(string $slug): array;
private static function page_definition(string $slug): array;
private static function default_navigation(): array;
private static function merge_definition(array $base, array $overrides): array;
private static function clean_slug(string $slug): string;
private static function script_for_slug(string $slug): string;
private static function page_title(string $slug): string;
private static function site_heading(): string;
private static function string_or_fallback(string $identifier, string $fallback): string;
```

### 9.2 Classes de définition par page

Dossier cible :

```text
classes/local/public_pages/
```

Namespace cible :

```php
namespace local_uckk\local\public_pages;
```

Convention de méthode :

```php
public static function definition(): array;
```

Fichiers à créer :

```text
classes/local/public_pages/home.php
classes/local/public_pages/about.php
classes/local/public_pages/programs.php
classes/local/public_pages/courses.php
classes/local/public_pages/challenges.php
classes/local/public_pages/assemblies.php
classes/local/public_pages/integrity.php
classes/local/public_pages/archives.php
classes/local/public_pages/mediatheque.php
classes/local/public_pages/news.php
classes/local/public_pages/contact.php
```

Classes à créer :

```php
local_uckk\local\public_pages\home
local_uckk\local\public_pages\about
local_uckk\local\public_pages\programs
local_uckk\local\public_pages\courses
local_uckk\local\public_pages\challenges
local_uckk\local\public_pages\assemblies
local_uckk\local\public_pages\integrity
local_uckk\local\public_pages\archives
local_uckk\local\public_pages\mediatheque
local_uckk\local\public_pages\news
local_uckk\local\public_pages\contact
```

Chaque classe de page :

```text
- retourne un tableau de définition ;
- ne rend aucun HTML ;
- ne connaît pas $OUTPUT ;
- ne configure pas $PAGE ;
- ne fait pas de redirect ;
- ne modifie pas la base de données ;
- ne lit pas les paramètres HTTP, sauf décision documentée séparément.
```

---

## 10. Cas particulier : `programs`

La page `programs` utilise actuellement des cartes dynamiques depuis le registre Moodle UCKK des programmes actifs.

Responsabilité cible :

```text
classes/local/public_pages/programs.php
```

Cette classe peut contenir :

```php
public static function definition(): array;
private static function with_program_cards(array $definition): array;
private static function program_cards(string $status): array;
private static function program_type_label(string $programtype): string;
private static function clean_modifier($modifier): string;
```

Règles :

```text
- public_pages.php ne doit plus contenir la requête SQL des programmes actifs ;
- les cartes dynamiques restent limitées à programs ;
- si la table local_uckk_program n’existe pas, la page doit continuer à fonctionner ;
- les programmes non actifs ne sont pas affichés publiquement ;
- aucune donnée privée n’est exposée.
```

---

## 11. Classe de rendu conservée

Fichier :

```text
classes/output/public_page.php
```

Classe :

```php
namespace local_uckk\output;

final class public_page implements renderable, templatable
```

Responsabilités :

```text
- recevoir slug + définition ;
- normaliser les anciennes clés si nécessaire ;
- exporter un objet stdClass pour Mustache ;
- générer les flags booléens ;
- générer les classes CSS ;
- ne pas décider le contenu institutionnel ;
- ne pas interroger la base de données ;
- ne pas rendre de HTML.
```

Template fixe retourné :

```php
private const TEMPLATE = 'local_uckk/public_page';
```

---

## 12. Templates Mustache finaux

### 12.1 Shell principal

```text
templates/public_page.mustache
```

Nom Moodle :

```text
local_uckk/public_page
```

Responsabilité :

```text
- contenir le conteneur racine ;
- appeler les partials ;
- ne contenir aucune boucle longue ;
- ne contenir aucun bloc spécialisé lourd ;
- ne pas contenir de logique métier.
```

Forme cible :

```mustache
<div
    id="{{uniqid}}"
    class="{{classes}}"
    data-region="local-uckk-public-page"
    data-component="{{component}}"
    data-page-slug="{{slug}}"
    data-page-type="{{pagetype}}"
    data-layout="{{layout}}"
    data-navigation-layout="{{navigationlayout}}"
    data-has-aside="{{#hasaside}}true{{/hasaside}}{{^hasaside}}false{{/hasaside}}"
    {{#ispublic}}data-public="true"{{/ispublic}}
>
    <div class="{{shellclasses}}" data-region="local-uckk-public-shell">
        {{#hasnavigation}}
            {{> local_uckk/public/nav }}
        {{/hasnavigation}}

        {{> local_uckk/public/hero }}

        {{#hasquicklinks}}
            {{> local_uckk/public/quicklinks }}
        {{/hasquicklinks}}

        <div class="{{bodyclasses}}" data-region="local-uckk-public-body">
            <main class="{{mainclasses}}" data-region="local-uckk-public-main">
                {{#hassections}}
                    {{> local_uckk/public/sections }}
                {{/hassections}}

                {{#has_home_feature}}
                    {{> local_uckk/pages/home_feature }}
                {{/has_home_feature}}

                {{#has_mediatheque_explorer}}
                    {{> local_uckk/pages/mediatheque_explorer }}
                {{/has_mediatheque_explorer}}

                {{#hascards}}
                    {{> local_uckk/public/cards }}
                {{/hascards}}
            </main>

            {{#hasaside}}
                {{> local_uckk/public/aside }}
            {{/hasaside}}
        </div>
    </div>
</div>
```

### 12.2 Partials publics communs

Dossier :

```text
templates/public/
```

| Fichier | Nom Moodle | Responsabilité |
|---|---|---|
| `nav.mustache` | `local_uckk/public/nav` | Navigation publique |
| `hero.mustache` | `local_uckk/public/hero` | Eyebrow, titre, sous-titre, résumé, boundary notice si rendu |
| `quicklinks.mustache` | `local_uckk/public/quicklinks` | Repères rapides |
| `sections.mustache` | `local_uckk/public/sections` | Boucle des sections de page |
| `cards.mustache` | `local_uckk/public/cards` | Cartes de page génériques |
| `aside.mustache` | `local_uckk/public/aside` | Colonne latérale et appels de sous-partials |
| `notices.mustache` | `local_uckk/public/notices` | Notices exportées par `public_page.php` |
| `metadata.mustache` | `local_uckk/public/metadata` | Liste metadata |
| `cta.mustache` | `local_uckk/public/cta` | Bloc CTA |
| `prompt_groups.mustache` | `local_uckk/public/prompt_groups` | Groupes d’invites réutilisables |

Note contractuelle : `notices` et `hasnotices` existent déjà dans le contexte exporté. Le refactor doit soit rendre les notices via `public/notices.mustache`, soit documenter explicitement leur non-rendu. La cible retenue est de les rendre dans l’aside.

### 12.3 Templates spécialisés par page

Dossier :

```text
templates/pages/
```

| Fichier | Nom Moodle | Responsabilité |
|---|---|---|
| `home_feature.mustache` | `local_uckk/pages/home_feature` | Bloc spécifique Accueil, si activé |
| `mediatheque_explorer.mustache` | `local_uckk/pages/mediatheque_explorer` | Explorateur Médiathèque public |

Migration :

```text
Ancien : templates/mediatheque_explorer.mustache
Ancien nom : local_uckk/mediatheque_explorer

Nouveau : templates/pages/mediatheque_explorer.mustache
Nouveau nom : local_uckk/pages/mediatheque_explorer
```

---

## 13. Contrat de contexte Mustache racine

Clés racine autorisées dans `public_page.mustache` :

```text
uniqid
slug
component
pagetype
ispublic
layout
navigationlayout
classes
rootclasses
layoutclasses
shellclasses
bodyclasses
mainclasses
asideclasses
railclasses
headerclasses
heroclasses
contentclasses
asideinnerclasses
typographyclasses
sectiongridclasses
cardgridclasses
designversion
fontstrategy

eyebrow
title
subtitle
summary
boundarynotice

haseyebrow
hassubtitle
hassummary
hasboundarynotice

navigation
hasnavigation
navigationclasses

quicklinks
hasquicklinks

sections
hassections

cards
hascards
cardsheading

notices
hasnotices

metadata
hasmetadata

cta
hascta

hasaside

has_home_feature
home_feature

has_mediatheque_explorer
mediatheque_explorer_id
mediatheque_initial_state
mediatheque_initial_state_json
```

Règle : tout nouveau champ Mustache doit être ajouté ici avant d’être codé.

---

## 14. Flags booléens autorisés

```text
ispublic
haseyebrow
hassubtitle
hassummary
hasboundarynotice
hasnavigation
hasquicklinks
hassections
hascards
hasnotices
hasmetadata
hascta
hasaside
has_home_feature
has_mediatheque_explorer
haspromptgroups
hasitems
hasaction
hasariacurrent
active
```

Règles :

```text
- les flags sont calculés par public_page.php ;
- les templates ne déduisent pas les flags ;
- une section Mustache doit utiliser un flag clair si le champ peut être vide ;
- pas de nouveau préfixe aléatoire comme is_, show_, enable_ sans mise à jour de ce contrat.
```

---

## 15. Contrat des tableaux exportés

### 15.1 `navigation[]`

```text
key
label
url
active
classes
itemclasses
ariacurrent
hasariacurrent
```

### 15.2 `quicklinks[]`

```text
label
description
url
classes
```

### 15.3 `sections[]`

```text
type
eyebrow
title
body
items
cards
classes
hasitems
```

`sections[].items[]` autorise :

```text
eyebrow
title
body
url
actionlabel
classes
hasaction
```

### 15.4 `cards[]`

```text
eyebrow
title
body
url
actionlabel
type
classes
hasaction
```

### 15.5 `notices[]`

```text
title
body
type
classes
hastitle
```

Types autorisés :

```text
institutional
integrity
warning
light
```

### 15.6 `metadata[]`

```text
label
value
```

### 15.7 `cta`

```text
title
body
url
label
actionlabel
classes
```

### 15.8 `promptgroups[]`

```text
eyebrow
title
body
items
classes
```

`promptgroups[].items[]` autorise :

```text
title
body
url
label
classes
```

---

## 16. Valeurs fixes de layout

Layouts autorisés :

```text
standard
wide
full
```

Navigation layouts autorisés :

```text
singleline
wrap
```

Typography values autorisées :

```text
institutional
editorial
display
```

Visual style documenté :

```text
civic-encyclopedic-retrofuturism
```

---

## 17. Règles CSS

Fichier CSS unique conservé :

```text
styles.css
```

Pas de fichier CSS par page.

Préfixes autorisés :

```text
local-uckk-
local-uckk-public-
local-uckk-public-nav-
local-uckk-public-hero-
local-uckk-public-section-
local-uckk-public-card-
local-uckk-public-aside-
local-uckk-public-meta-
local-uckk-public-cta-
local-uckk-public-notice-
local-uckk-public-prompt-
local-uckk-home-
local-uckk-mediatheque-
```

Organisation cible dans `styles.css` :

```css
/* Public shell */
/* Public nav */
/* Public hero */
/* Public quicklinks */
/* Public sections */
/* Public cards */
/* Public aside */
/* Public notices */
/* Public metadata */
/* Public CTA */
/* Public prompt groups */
/* Page: home */
/* Page: mediatheque */
/* Responsive */
```

Règles :

```text
- ne pas renommer massivement les classes existantes pendant le split Mustache ;
- d’abord préserver le HTML rendu ;
- ensuite seulement nettoyer le CSS ;
- aucune classe Bootstrap nouvelle sans justification ;
- aucun style inline.
```

---

## 18. Règles de langue

Fichiers de langue existants :

```text
lang/fr/local_uckk.php
lang/en/local_uckk.php
```

Règles :

```text
- ne pas ajouter de chaîne tant qu’un texte reste strictement interne au template et déjà existant ;
- ajouter une chaîne si un nouveau libellé devient réutilisable ;
- ajouter les clés en français et en anglais dans le même changement ;
- ne pas mélanger les changements de langue avec le découpage PHP sauf nécessité.
```

Clés candidates si externalisation décidée :

```text
public_quicklinks_heading
public_metadata_heading
public_aside_aria_label
public_cta_default_label
public_navigation_aria_label
```

---

## 19. AMD et Médiathèque

Fichier AMD conservé :

```text
amd/src/mediatheque_explorer.js
```

Module AMD fixe :

```text
local_uckk/mediatheque_explorer
```

Appel autorisé :

```php
$PAGE->requires->js_call_amd('local_uckk/mediatheque_explorer', 'init', [$initialstate]);
```

Règles :

```text
- ne pas renommer le module AMD dans ce refactor ;
- ne pas changer le contrat de service AJAX ;
- ne pas déplacer la recherche côté PHP local_uckk ;
- le template spécialisé affiche seulement le conteneur et l’état initial.
```

---

## 20. Arborescence cible

```text
local/uckk/
├── index.php
├── about.php
├── programs.php
├── courses.php
├── challenges.php
├── assemblies.php
├── integrity.php
├── archives.php
├── mediatheque.php
├── news.php
├── contact.php
├── campus.php                         # hors lot principal
│
├── classes/
│   ├── local/
│   │   ├── public_pages.php
│   │   └── public_pages/
│   │       ├── home.php
│   │       ├── about.php
│   │       ├── programs.php
│   │       ├── courses.php
│   │       ├── challenges.php
│   │       ├── assemblies.php
│   │       ├── integrity.php
│   │       ├── archives.php
│   │       ├── mediatheque.php
│   │       ├── news.php
│   │       └── contact.php
│   │
│   └── output/
│       └── public_page.php
│
├── templates/
│   ├── public_page.mustache
│   │
│   ├── public/
│   │   ├── nav.mustache
│   │   ├── hero.mustache
│   │   ├── quicklinks.mustache
│   │   ├── sections.mustache
│   │   ├── cards.mustache
│   │   ├── aside.mustache
│   │   ├── notices.mustache
│   │   ├── metadata.mustache
│   │   ├── cta.mustache
│   │   └── prompt_groups.mustache
│   │
│   ├── pages/
│   │   ├── home_feature.mustache
│   │   └── mediatheque_explorer.mustache
│   │
│   ├── program_card.mustache
│   └── public_prompt_groups.mustache   # à supprimer ou laisser comme compat temporaire selon usage réel
│
├── styles.css
└── amd/
    └── src/
        └── mediatheque_explorer.js
```

---

## 21. Fichiers à créer

```text
docs/refactor_public_pages_contract.md

templates/public/nav.mustache
templates/public/hero.mustache
templates/public/quicklinks.mustache
templates/public/sections.mustache
templates/public/cards.mustache
templates/public/aside.mustache
templates/public/notices.mustache
templates/public/metadata.mustache
templates/public/cta.mustache
templates/public/prompt_groups.mustache

templates/pages/home_feature.mustache
templates/pages/mediatheque_explorer.mustache

classes/local/public_pages/home.php
classes/local/public_pages/about.php
classes/local/public_pages/programs.php
classes/local/public_pages/courses.php
classes/local/public_pages/challenges.php
classes/local/public_pages/assemblies.php
classes/local/public_pages/integrity.php
classes/local/public_pages/archives.php
classes/local/public_pages/mediatheque.php
classes/local/public_pages/news.php
classes/local/public_pages/contact.php
```

---

## 22. Fichiers à modifier

```text
templates/public_page.mustache
classes/local/public_pages.php
classes/output/public_page.php
mediatheque.php
styles.css
lang/fr/local_uckk.php       # seulement si nouvelles chaînes
lang/en/local_uckk.php       # seulement si nouvelles chaînes
```

---

## 23. Fichiers à ne pas modifier dans ce refactor

```text
classes/api/*
classes/external/*
classes/form/*
classes/local/program.php
classes/local/pathway.php
classes/local/canon_item.php
classes/local/player_profile.php
classes/privacy/provider.php
db/*
canon.php
pathways.php
campus.php                  # sauf lot séparé explicite
```

---

## 24. Ordre exact de codage fichier par fichier

### Lot 0 — Documentation et sécurité

```text
0.1 Créer docs/refactor_public_pages_contract.md
0.2 git status --short
0.3 git switch -c refactor-public-pages
```

### Lot 1 — Dossiers

```text
1.1 Créer templates/public/
1.2 Créer templates/pages/
1.3 Créer classes/local/public_pages/
```

### Lot 2 — Partials Mustache communs

Coder un fichier à la fois, en copiant d’abord le HTML existant depuis `public_page.mustache`.

```text
2.1 templates/public/nav.mustache
2.2 templates/public/hero.mustache
2.3 templates/public/quicklinks.mustache
2.4 templates/public/sections.mustache
2.5 templates/public/cards.mustache
2.6 templates/public/notices.mustache
2.7 templates/public/metadata.mustache
2.8 templates/public/cta.mustache
2.9 templates/public/aside.mustache
2.10 templates/public/prompt_groups.mustache
```

Validation après chaque fichier Mustache :

```text
- vérifier que les variables utilisées existent dans ce contrat ;
- vérifier que le partial ne contient pas de variable nouvelle non documentée ;
- vérifier que le partial ne contient aucune logique métier ;
- vérifier l’indentation Mustache.
```

### Lot 3 — Partials spécialisés

```text
3.1 templates/pages/mediatheque_explorer.mustache
3.2 templates/pages/home_feature.mustache
```

Règle : `mediatheque_explorer.mustache` doit être déplacé sans changer le contrat AMD.

### Lot 4 — Shell Mustache

```text
4.1 templates/public_page.mustache
```

Règle : le shell devient uniquement un conteneur + appels de partials.

Validation :

```text
- aucune boucle longue dans le shell ;
- aucune carte directement rendue dans le shell ;
- aucune metadata directement rendue dans le shell ;
- aucune logique Médiathèque directement dans le shell ;
- le nom du template reste local_uckk/public_page.
```

### Lot 5 — Classes de page PHP

Créer et valider une classe à la fois :

```text
5.1 classes/local/public_pages/home.php
5.2 classes/local/public_pages/about.php
5.3 classes/local/public_pages/programs.php
5.4 classes/local/public_pages/courses.php
5.5 classes/local/public_pages/challenges.php
5.6 classes/local/public_pages/assemblies.php
5.7 classes/local/public_pages/integrity.php
5.8 classes/local/public_pages/archives.php
5.9 classes/local/public_pages/mediatheque.php
5.10 classes/local/public_pages/news.php
5.11 classes/local/public_pages/contact.php
```

Validation après chaque fichier PHP :

```powershell
php -l local\uckk\classes\local\public_pages\home.php
```

Adapter le nom du fichier à chaque étape.

### Lot 6 — Registre central

```text
6.1 classes/local/public_pages.php
```

Objectif : réduire le fichier à un registre/routeur.

Validation :

```text
- public_pages::setup_page() fonctionne encore ;
- public_pages::definition($slug) fonctionne encore ;
- clean_slug garde les mêmes slugs autorisés ;
- default_navigation garde les mêmes routes ;
- la définition de page vient des nouvelles classes ;
- la requête SQL programmes n’est plus dans public_pages.php.
```

### Lot 7 — Exporter de rendu

```text
7.1 classes/output/public_page.php
```

Changements autorisés :

```text
- ajouter has_home_feature ;
- ajouter home_feature si nécessaire ;
- ajouter promptgroups / haspromptgroups si nécessaire ;
- préserver les clés existantes ;
- préserver TEMPLATE = local_uckk/public_page.
```

Validation :

```powershell
php -l local\uckk\classes\output\public_page.php
```

### Lot 8 — Contrôleur Médiathèque

```text
8.1 mediatheque.php
```

Objectif : vérifier que le contrôleur passe seulement les overrides nécessaires et que le template spécialisé utilisé est le nouveau partial `local_uckk/pages/mediatheque_explorer` via le shell.

Validation :

```text
- URL params conservés ;
- initialstate conservé ;
- js_call_amd conservé ;
- aucun accès direct aux tables média ;
- aucun HTML fallback modifié sauf nécessité.
```

### Lot 9 — CSS et langue

```text
9.1 styles.css
9.2 lang/fr/local_uckk.php si nécessaire
9.3 lang/en/local_uckk.php si nécessaire
```

Règle : CSS en dernier, seulement après stabilisation du HTML.

---

## 25. Commandes de validation locales

Depuis le dépôt source :

```powershell
cd "C:\mycode\UCKK\uckk-moodle"

git status --short
git diff --check
```

Lint PHP des fichiers modifiés :

```powershell
php -l local\uckk\classes\local\public_pages.php
php -l local\uckk\classes\output\public_page.php
php -l local\uckk\mediatheque.php
php -l local\uckk\index.php
php -l local\uckk\about.php
php -l local\uckk\programs.php
php -l local\uckk\courses.php
php -l local\uckk\challenges.php
php -l local\uckk\assemblies.php
php -l local\uckk\integrity.php
php -l local\uckk\archives.php
php -l local\uckk\news.php
php -l local\uckk\contact.php
```

Synchronisation locale Moodle :

```powershell
robocopy ".\local\uckk" "C:\mycode\UCKK\moodle\moodle\public\local\uckk" /MIR /XD ".git" "node_modules" "vendor"

cd "C:\mycode\UCKK\moodle\moodle"
php admin\cli\purge_caches.php
```

Pages à ouvrir après purge :

```text
/local/uckk/index.php
/local/uckk/about.php
/local/uckk/programs.php
/local/uckk/courses.php
/local/uckk/challenges.php
/local/uckk/assemblies.php
/local/uckk/integrity.php
/local/uckk/archives.php
/local/uckk/mediatheque.php
/local/uckk/news.php
/local/uckk/contact.php
```

---

## 26. Critères de non-régression

Le refactor est invalide si :

```text
- une page publique affiche une erreur Mustache ;
- une page publique perd sa navigation ;
- mediatheque.php ne lance plus l’AMD ;
- programs.php perd les cartes dynamiques actives ;
- une page publique expose des données privées ;
- public_pages.php contient encore tout le contenu de toutes les pages après le lot 6 ;
- public_page.mustache contient encore les gros blocs de rendu après le lot 4 ;
- styles.css est réécrit avant stabilisation du HTML ;
- campus.php est modifié sans lot séparé ;
- une variable Mustache non documentée est ajoutée.
```

---

## 27. Critères de fin

Le refactor est terminé quand :

```text
- docs/refactor_public_pages_contract.md existe ;
- public_page.mustache est un shell ;
- templates/public/*.mustache contient les blocs communs ;
- templates/pages/*.mustache contient les blocs spécialisés ;
- classes/local/public_pages/*.php contient les définitions par page ;
- classes/local/public_pages.php est réduit à un routeur/agrégateur ;
- classes/output/public_page.php exporte toutes les variables documentées ;
- les 11 pages publiques du lot principal fonctionnent après purge caches ;
- git diff --check ne signale rien ;
- les fichiers PHP modifiés passent php -l.
```

---

## 28. Règle de commit

Commits recommandés :

```text
Commit 1 — Add public page refactor contract
Commit 2 — Split public page Mustache into partials
Commit 3 — Split public page definitions by slug
Commit 4 — Wire public page registry to page definition classes
Commit 5 — Finalize public page rendering context and CSS cleanup
```

Ne pas mélanger :

```text
- split Mustache ;
- split PHP ;
- nettoyage CSS ;
- ajout de contenu ;
- migration campus.
```

---

## 29. Décisions verrouillées

```text
DÉCISION 1
Un seul fichier de documentation pilote ce refactor.

DÉCISION 2
Le code est modifié fichier par fichier.

DÉCISION 3
Le shell public reste local_uckk/public_page.

DÉCISION 4
Les pages publiques principales sont au nombre de 11 dans le lot 1.

DÉCISION 5
campus.php est hors lot principal.

DÉCISION 6
mediatheque.php garde son état initial AMD spécifique.

DÉCISION 7
programs possède la seule logique dynamique de cartes dans les définitions publiques.

DÉCISION 8
styles.css reste unique.

DÉCISION 9
Les notices exportées doivent avoir une cible de rendu documentée.

DÉCISION 10
Toute nouvelle variable doit être ajoutée à ce contrat avant d’être utilisée.
```
