# Structure actuelle des styles UCKK Moodle

_Date de l’audit : 2026-05-27_

Ce document résume la structure CSS/SCSS actuellement observée dans ton installation `uckk-moodle`, avec les responsabilités de chaque couche, les frontières à respecter, et les points de conflit déjà identifiés autour des boutons verts, des liens visités et du menu gauche.

---

## 1. Vue d’ensemble

La structure actuelle n’est pas un seul système de style. Elle est composée de plusieurs couches :

```text
theme_uckk
├── scss/uckk.scss                  # Identité globale + tokens + skin Boost global
├── scss/navigation.scss            # Navigation Moodle/Boost : navbar, drawers, course index
├── scss/_uckk-public-chrome.scss   # Chrome Moodle autour des pages publiques local_uckk
├── scss/archive.scss               # Surfaces archive côté thème
├── scss/assembly.scss              # Surfaces assembly côté thème
├── scss/challenge.scss             # Surfaces challenge côté thème
└── autres fichiers SCSS de domaine

course/format/uckk
└── styles.css                      # Format de cours UCKK : sections, badges, course map

mod/uckkarchive
└── styles.css                      # UI interne du module archive/media/advisory/export

local/uckk
└── pas de styles.css trouvé        # Les classes local-uckk existent, mais le CSS source reste à localiser
```

Le problème actuel vient surtout du fait qu’une règle globale dans `theme/uckk/scss/uckk.scss` colore tous les liens visités avec `!important`, ce qui déborde sur les boutons et la navigation.

---

## 2. Couche `theme_uckk`

### 2.1 `theme/uckk/scss/uckk.scss`

**Rôle actuel :**

`uckk.scss` est la couche principale de l’identité visuelle globale.

Il contient :

```text
- variables SCSS officielles : $theme-uckk-*
- custom properties globales : --theme-uckk-*
- alias runtime : --uckk-*
- styles partagés .theme-uckk-*
- frontpage .theme-uckk-frontpage
- skin global Boost/Moodle
- top navbar global
- secondary navigation
- dashboard overrides
```

**Responsabilité normale :**

```text
theme_uckk/scss/uckk.scss
= identité globale + tokens + adaptation générale Moodle/Boost
```

**Ce fichier peut styler :**

```text
- body / page shell Moodle
- navbar globale
- page header
- tables Moodle générales
- formulaires génériques
- boutons génériques Moodle
- surfaces dashboard
- frontpage theme-owned
```

**Ce fichier ne devrait pas cibler directement :**

```text
- composants internes mod_uckkarchive très spécifiques
- composants local_uckk internes
- logique de cours format_uckk
- états métier archive/assembly/challenge
```

**Point problématique actuel :**

```scss
a,
a:visited,
.btn-link {
    color: var(--uckk-petrol) !important;
}
```

Cette règle est trop globale. Elle force tous les liens visités à être pétrole, y compris :

```text
- <a class="btn btn-secondary">
- boutons CTA verts/pétrole
- liens de navigation actifs
- éléments du menu gauche
- liens dans drawers/courseindex
```

C’est la source principale des textes invisibles sur fond vert/pétrole.

**Correction conceptuelle :**

Remplacer la règle globale par des règles limitées :

```text
- liens textuels génériques : OK
- boutons : exclus
- nav links : exclus
- drawers/courseindex : exclus
- dropdown items : exclus
```

---

### 2.2 `theme/uckk/scss/navigation.scss`

**Rôle actuel :**

Ce fichier est explicitement dédié à la navigation Moodle/Boost.

Il déclare une frontière claire :

```text
theme_uckk owns Moodle/Boost global navigation presentation
local_uckk owns public institutional page structure and content
```

**Responsabilité normale :**

```text
theme/uckk/scss/navigation.scss
= navbar Moodle, drawers, course index, nav-link, dropdown navigation
```

**Ce fichier peut styler :**

```text
- .navbar
- .primary-navigation
- .secondary-navigation
- .drawercontent
- .courseindex
- .nav-link
- .dropdown-item
- .breadcrumb
```

**État actuel :**

Ce fichier semble être le bon endroit pour la navigation, mais il n’est probablement pas la source du bug principal. Ses règles peuvent être écrasées par `uckk.scss` si `uckk.scss` charge après ou utilise `!important`.

**Conclusion :**

Ne pas corriger le menu gauche dans `mod/uckkarchive/styles.css`. Corriger d’abord la règle globale dans `uckk.scss`.

---

### 2.3 `theme/uckk/scss/_uckk-public-chrome.scss`

**Rôle actuel :**

Ce fichier cible le chrome Moodle autour des pages publiques `local_uckk`.

Il indique explicitement :

```text
Owns Moodle header/breadcrumb/title refinements for public UCKK pages.
Does not style local_uckk page components.
```

**Responsabilité normale :**

```text
theme/uckk/scss/_uckk-public-chrome.scss
= page title, breadcrumb, top navigation rhythm for local_uckk public layouts
```

**Ce fichier peut styler :**

```text
- body.pagelayout-local_uckk_public #page-header h1
- body:has(.local-uckk-public-page) #page-header h1
- breadcrumb / page-navbar
- top Moodle navigation text rhythm on public pages
```

**Ce fichier ne devrait pas styler :**

```text
- .local-uckk-public-cta__action
- contenu interne local_uckk
- boutons CTA internes
```

---

### 2.4 `theme/uckk/scss/archive.scss`

**Rôle actuel :**

Ce fichier contient une couche thème pour les surfaces archive génériques :

```text
.theme-uckk-archive
.theme-uckk-archive-card
.theme-uckk-archive-state
.theme-uckk-archive-item
.theme-uckk-provenance
.theme-uckk-revisions
.theme-uckk-proof
.theme-uckk-kristal
```

**Responsabilité normale :**

```text
theme/uckk/scss/archive.scss
= composants visuels archive theme-owned, réutilisables ou génériques
```

**Relation avec `mod_uckkarchive` :**

Il contient une section d’intégration limitée :

```scss
.path-mod-uckkarchive .theme-uckk-archive,
.path-mod-uckkarchive .theme-uckk-archive-item,
.path-mod-uckkarchive .theme-uckk-archive__toolbar {
    margin-bottom: 1.5rem;
}
```

**Frontière recommandée :**

```text
archive.scss peut styler les composants .theme-uckk-archive*
mod/uckkarchive/styles.css doit styler les composants .uckkarchive-*
```

---

### 2.5 `theme/uckk/scss/assembly.scss`

**Rôle actuel :**

Styles de présentation pour les interfaces Assembly :

```text
.uckk-assembly
.uckk-assembly-card
.uckk-motion
.uckk-decision
.uckk-contestation
.uckk-minority-report
```

**Responsabilité normale :**

```text
theme/uckk/scss/assembly.scss
= surfaces assembly visuelles, pas de logique métier
```

**Pas impliqué directement dans le bug des boutons verts**, sauf si des règles globales de thème l’affectent.

---

### 2.6 `theme/uckk/scss/challenge.scss`

**Rôle actuel :**

Styles de présentation pour les interfaces Challenge / Défis.

**Responsabilité normale :**

```text
theme/uckk/scss/challenge.scss
= surfaces challenge visuelles, pas de logique métier
```

**Pas impliqué directement dans le bug actuel**, sauf à travers les règles globales dans `uckk.scss`.

---

## 3. Couche `course/format/uckk`

### `course/format/uckk/styles.css`

**Rôle actuel :**

Ce fichier est scoped au format de cours UCKK.

Il contient :

```text
.format-uckk
.uckk-course-format
.uckk-course-map
.uckk-section
.uckk-section-header
.uckk-section-index
.uckk-section-indicator
.courseindex .uckk-courseindex-badge
```

**Responsabilité normale :**

```text
course/format/uckk/styles.css
= structure visuelle des cours UCKK, sections, course map, indicateurs
```

**Ce fichier peut styler :**

```text
- sections de cours
- badges dans le course index
- zones evidence/deliberation/archive/integrity
- layout du format de cours
```

**Ce fichier ne devrait pas corriger :**

```text
- les boutons d’export mod_uckkarchive
- les CTA local_uckk
- la règle globale a:visited
- la navbar Moodle globale
```

**État actuel :**

Le fichier semble correctement scoped avec `.format-uckk`. Il ne semble pas être la cause principale du texte invisible.

---

## 4. Couche `mod_uckkarchive`

### `mod/uckkarchive/styles.css`

**Rôle actuel :**

Ce fichier est scoped au module :

```text
.path-mod-uckkarchive
```

Il contient des styles pour :

```text
- archive view
- export screen
- item cards
- Kristal cards
- proof cards
- provenance panels
- validation panels
- media library
- media cards
- media collections
- media upload
- external works
- content advisory panels
```

**Responsabilité normale :**

```text
mod/uckkarchive/styles.css
= UI interne du module archive/media/content advisory/export
```

**Ce fichier peut styler :**

```text
.path-mod-uckkarchive .uckkarchive-*
.path-mod-uckkarchive .uckkarchive-media-*
.path-mod-uckkarchive .uckkarchive-export-*
.path-mod-uckkarchive .uckkarchive-content-advisory-*
```

**Ce fichier ne devrait pas styler :**

```text
- .navbar
- .drawer
- .courseindex global
- .local-uckk-public-*
- tous les a:visited du site
```

**Patch local actuellement ajouté :**

```css
/* Keep Bootstrap-like action links readable when visited. */
.path-mod-uckkarchive a.btn,
.path-mod-uckkarchive a.btn:visited,
.path-mod-uckkarchive a.btn:hover,
.path-mod-uckkarchive a.btn:focus {
    color: var(--uckk-paper-soft) !important;
}

/* Preserve outline buttons before hover. */
.path-mod-uckkarchive a.btn-outline-primary,
.path-mod-uckkarchive a.btn-outline-primary:visited {
    color: var(--uckk-petrol) !important;
}

.path-mod-uckkarchive a.btn-outline-primary:hover,
.path-mod-uckkarchive a.btn-outline-primary:focus {
    color: var(--uckk-paper-soft) !important;
}
```

**Évaluation :**

Ce patch corrige le symptôme dans `mod_uckkarchive`, mais pas la cause globale. Il devrait être remplacé par un patch local plus propre ou retiré après correction de `uckk.scss`.

---

## 5. Couche `local_uckk`

### État actuel observé

Le fichier suivant a été demandé mais n’existe pas dans le dump :

```text
C:\mycode\UCKK\uckk-moodle\local\uckk\styles.css
```

Pourtant, l’inspecteur montre des classes :

```text
.local-uckk-public-cta__action
.local-uckk-public-page
```

Cela veut dire que le style de `local_uckk` est probablement dans l’un de ces endroits :

```text
theme/uckk/scss/uckk.scss
theme/uckk/scss/_uckk-public-chrome.scss
un autre fichier SCSS non encore isolé
un fichier compilé/généré
des templates local/uckk/templates/*.mustache qui utilisent des classes non encore stylées localement
```

**Responsabilité normale attendue :**

```text
local_uckk
= structure et composants publics institutionnels
```

**Ce qui devrait être dans local_uckk :**

```text
.local-uckk-public-page
.local-uckk-public-hero
.local-uckk-public-cta
.local-uckk-public-cta__action
```

**Ce qui devrait rester dans theme_uckk :**

```text
- tokens
- typo globale
- page header Moodle
- breadcrumb Moodle
- skin Boost
```

---

## 6. Cascade actuelle et conflit principal

### Cascade logique attendue

```text
theme_uckk tokens
↓
theme_uckk Boost/global skin
↓
theme_uckk navigation
↓
course/format/uckk styles
↓
mod_uckkarchive styles
↓
local_uckk component styles
```

### Conflit actuel

La règle globale suivante dans `uckk.scss` est trop forte :

```scss
a,
a:visited,
.btn-link {
    color: var(--uckk-petrol) !important;
}
```

Elle écrase des styles qui devraient rester propriétaires :

```text
- boutons Bootstrap remplis
- boutons CTA publics
- menu gauche / course index
- liens actifs dans navigation
- boutons de module
```

### Symptômes observés

```text
- bouton vert avec texte vert invisible
- bouton Annuler dans export avec texte invisible
- bouton/menu Archive du séminaire affecté
- nécessité de forcer plusieurs fois le texte en blanc
```

### Cause racine

```text
Un style global a:visited !important est utilisé comme règle de couleur générale.
```

### Correction racine

```text
Ne jamais appliquer a:visited !important globalement aux boutons et navigations.
Limiter visited aux liens textuels simples.
```

---

## 7. Matrice de propriété recommandée

| Zone | Fichier propriétaire | Scope recommandé | À éviter |
|---|---|---|---|
| Tokens UCKK | `theme/uckk/scss/uckk.scss` | `:root`, `--theme-uckk-*`, `--uckk-*` | logique métier |
| Boost global skin | `theme/uckk/scss/uckk.scss` | `body`, `#page`, `.main-inner`, tables/forms génériques | règles trop larges sur `a:visited !important` |
| Navbar/drawers/courseindex | `theme/uckk/scss/navigation.scss` | `.navbar`, `.drawercontent`, `.courseindex`, `.nav-link` | composants `local_uckk` internes |
| Public page chrome | `theme/uckk/scss/_uckk-public-chrome.scss` | `pagelayout-local_uckk_public`, `#page-header`, breadcrumb | CTA internes |
| Public components | `local/uckk` CSS/SCSS à localiser | `.local-uckk-*` | styles Moodle globaux |
| Format de cours | `course/format/uckk/styles.css` | `.format-uckk`, `.uckk-section-*` | boutons du module archive |
| Module archive | `mod/uckkarchive/styles.css` | `.path-mod-uckkarchive .uckkarchive-*` | navbar, drawer, courseindex global |
| Archive theme components | `theme/uckk/scss/archive.scss` | `.theme-uckk-archive*` | `.uckkarchive-*` internes au module |

---

## 8. Règles de nettoyage proposées

### 8.1 Dans `theme/uckk/scss/uckk.scss`

Remplacer :

```scss
a,
a:visited,
.btn-link {
    color: var(--uckk-petrol) !important;
}
```

par une logique plus sélective :

```scss
/* Text links only. Do not override buttons or navigation. */
#page a:not(.btn):not(.nav-link):not(.dropdown-item):not(.list-group-item):not([role="button"]),
#page a:visited:not(.btn):not(.nav-link):not(.dropdown-item):not(.list-group-item):not([role="button"]),
#page .btn-link {
    color: var(--uckk-petrol) !important;
}

#page a:hover:not(.btn):not(.nav-link):not(.dropdown-item):not(.list-group-item):not([role="button"]),
#page .btn-link:hover {
    color: var(--uckk-petrol-dark) !important;
}
```

Puis ajouter une protection générique pour les boutons remplis :

```scss
#page .btn-primary,
#page .btn-primary:visited,
#page .btn-secondary,
#page .btn-secondary:visited,
#page .btn-success,
#page .btn-success:visited,
#page input[type="submit"],
#page button[type="submit"] {
    color: var(--uckk-paper-soft) !important;
}
```

### 8.2 Dans `mod/uckkarchive/styles.css`

Retirer le patch symptomatique final ou le remplacer par un patch strictement interne :

```css
.path-mod-uckkarchive .uckkarchive-export a.btn,
.path-mod-uckkarchive .uckkarchive-export a.btn:visited,
.path-mod-uckkarchive .uckkarchive-media-library a.btn,
.path-mod-uckkarchive .uckkarchive-media-library a.btn:visited {
    text-decoration: none !important;
}
```

Ne pas y corriger le menu gauche ou les CTA publics.

### 8.3 Dans `navigation.scss`

Ne pas intervenir tant que `uckk.scss` n’est pas corrigé. Si le menu gauche reste affecté après correction de `uckk.scss`, inspecter ensuite :

```text
.drawercontent .list-group-item
.drawercontent .nav-link
.courseindex .courseindex-link
.courseindex a
```

---

## 9. Structure cible recommandée

```text
theme/uckk/scss/uckk.scss
├── tokens
├── aliases runtime
├── shared theme components
├── Boost global shell
├── tables/forms/cards globales
├── boutons génériques Moodle
└── liens textuels seulement, sans écraser nav/boutons

theme/uckk/scss/navigation.scss
├── top navbar
├── primary navigation
├── secondary navigation
├── drawers
├── course index
├── breadcrumbs
└── focus states navigation

theme/uckk/scss/_uckk-public-chrome.scss
├── page header public
├── breadcrumb public
└── rythme typographique du chrome Moodle public

local/uckk/[style source à localiser]
├── .local-uckk-public-page
├── .local-uckk-public-cta
├── .local-uckk-public-cta__action
└── composants publics institutionnels

course/format/uckk/styles.css
├── .format-uckk
├── .uckk-course-map
├── .uckk-section
├── .uckk-section-indicator
├── .uckk-courseindex-badge
└── layout du cours UCKK

mod/uckkarchive/styles.css
├── .path-mod-uckkarchive .uckkarchive-view
├── .path-mod-uckkarchive .uckkarchive-export
├── .path-mod-uckkarchive .uckkarchive-media-library
├── .path-mod-uckkarchive .uckkarchive-media-card
├── .path-mod-uckkarchive .uckkarchive-content-advisory-panel
└── UI interne du module seulement
```

---

## 10. Priorités de correction

### Priorité 1

Corriger `theme/uckk/scss/uckk.scss`.

Objectif :

```text
Supprimer l’effet global a:visited sur boutons/navigation.
```

### Priorité 2

Nettoyer `mod/uckkarchive/styles.css`.

Objectif :

```text
Retirer les patches de compensation maintenant inutiles ou les limiter au module.
```

### Priorité 3

Localiser le style source de `local-uckk-public-cta__action`.

Objectif :

```text
Donner un propriétaire clair aux boutons CTA publics.
```

### Priorité 4

Tester :

```text
- bouton Annuler export
- bouton Exporter le média
- bouton Ajouter une collection
- bouton CTA local_uckk
- menu gauche Archive du séminaire
- nav active et visited
```

---

## 11. Commandes utiles

### Chercher les règles dangereuses

```powershell
cd C:\mycode\UCKK\uckk-moodle

Select-String -Path .\theme\uckk\scss\*.scss,.\theme\uckk\scss\**\*.scss,.\mod\uckkarchive\styles.css,.\course\format\uckk\styles.css `
  -Pattern "a:visited|\.btn-link|\.btn-primary|\.btn-secondary|courseindex|drawercontent|local-uckk-public-cta" `
  -Context 3,8
```

### Chercher où les classes `local-uckk` sont définies

```powershell
cd C:\mycode\UCKK\uckk-moodle

Get-ChildItem -Recurse -File .\local\uckk,.\theme\uckk |
  Where-Object { $_.Extension -in ".scss",".css",".mustache",".php" } |
  Select-String -Pattern "local-uckk-public-cta|local-uckk-public-page|local-uckk" -Context 3,8
```

### Purger Moodle après changement CSS/SCSS

```powershell
cd C:\mycode\UCKK\moodle\moodle\public

php -r "define('CLI_SCRIPT', true); require 'config.php'; purge_all_caches(); echo 'Caches purged' . PHP_EOL;"
```

Puis dans le navigateur :

```text
Ctrl + F5
```

---

## 12. Résumé décisionnel

Le correctif propre n’est pas de forcer le texte blanc sur chaque bouton touché.

Le correctif propre est :

```text
1. retirer le pouvoir global de a:visited !important
2. laisser chaque couche propriétaire styler ses propres boutons
3. garder la navigation sous navigation.scss
4. garder mod_uckkarchive scoped à .path-mod-uckkarchive
5. localiser et isoler les styles .local-uckk-*
```

