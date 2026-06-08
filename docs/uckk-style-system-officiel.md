# UCKK — Documentation officielle du système de styles public

**Document :** Guide officiel du style public UCKK  
**Composant :** `local_uckk`  
**Portée :** Pages institutionnelles publiques UCKK dans Moodle  
**Statut :** Référence d’implémentation  
**Version :** 1.1.0  
**Date :** 2026-06-08

---

## 1. Objet

Ce document définit le système de styles officiel des pages publiques UCKK.

Il sert à centraliser l’identité visuelle, les règles de nommage, les composants d’interface, les limites d’intervention et les critères de qualité pour les pages institutionnelles publiques du plugin Moodle `local_uckk`.

Le but est d’éviter le patchage, les styles dispersés, les classes concurrentes et les comportements visuels incohérents.

---

## 2. Principe directeur

Les pages publiques UCKK doivent donner l’impression d’une **cité-école sérieuse, expérimentale, civique et documentée**.

Elles ne doivent pas ressembler à :

- un Moodle brut;
- un portail universitaire générique;
- une landing page de startup;
- une interface fantasy chaotique;
- un patchwork de styles locaux;
- une fausse institution accréditée.

Elles doivent exprimer :

- la clarté institutionnelle;
- la lisibilité;
- la rigueur;
- la mémoire;
- la preuve;
- la méthode;
- le théâtre public responsable;
- l’identité visuelle UCKK.

---

## 3. Canon visuel

Le style UCKK public est :

> **Rétrofuturisme civique encyclopédique**

ou :

> **Affiche institutionnelle utopique néo-académique**

Il combine :

- parchemin;
- vert pétrole profond;
- or vieilli;
- encre noire-verte;
- typographie institutionnelle;
- grilles sobres;
- cartes lisibles;
- bordures fines;
- hiérarchie documentaire;
- symbolique sans excès fantasy.

---

## 4. Portée du style

Le fichier de style public agit uniquement sur les pages publiques du plugin `local_uckk`.

### Inclus

Le système couvre :

- Accueil;
- À propos;
- Voies;
- Cours;
- Défis;
- Assemblées;
- Intégrité;
- Archives;
- Actualités;
- Contact;
- navigation publique UCKK;
- héros de page;
- cartes;
- notices;
- tableaux;
- listes;
- appels à action;
- métadonnées;
- sections documentaires.

### Exclu

Le système ne doit pas prendre possession de :

- la navigation principale Moodle;
- les drawers Moodle;
- les contrôles d’édition;
- les formulaires Moodle génériques;
- les interfaces de notation;
- les rapports administratifs;
- les pages système;
- les activités Moodle qui ont leur propre UI;
- les workflows internes;
- les décisions de permission;
- les règles de reconnaissance;
- les cas d’intégrité.

---

## 5. Fichiers propriétaires du style public

Le système officiel repose sur ces fichiers.

```text
public/local/uckk/styles.css
public/local/uckk/templates/public_page.mustache
public/local/uckk/classes/output/public_page.php
public/local/uckk/classes/local/public_pages.php
```

### Rôle des fichiers

| Fichier | Rôle |
|---|---|
| `styles.css` | Source unique du style public UCKK pour `local_uckk` |
| `public_page.mustache` | Template unique des pages publiques |
| `classes/output/public_page.php` | Objet de rendu Moodle vers Mustache |
| `classes/local/public_pages.php` | Registre central des pages, navigation et définitions |

Les contrôleurs publics comme `index.php`, `about.php`, `contact.php`, etc., doivent rester minces.

---

## 6. Source unique de vérité

Le style public doit suivre ce principe :

```text
1 système
1 template
1 helper central
1 fichier CSS
10 contrôleurs minces
```

Les pages ne doivent pas redéfinir leur propre navigation, leur propre shell ou leurs propres classes principales.

---

## 7. Contrat HTML officiel

Toutes les pages publiques doivent utiliser la même structure logique.

```html
<div class="local-uckk local-uckk-public-page local-uckk-public-page--{slug}">
    <nav class="local-uckk-public-nav">
        ...
    </nav>

    <header class="local-uckk-public-hero">
        ...
    </header>

    <main class="local-uckk-public-main">
        ...
    </main>

    <aside class="local-uckk-public-aside">
        ...
    </aside>
</div>
```

---

## 8. Convention de nommage CSS

La convention officielle est :

```text
.local-uckk-{component}
.local-uckk-{component}__{element}
.local-uckk-{component}--{modifier}
.is-{state}
```

Exemples :

```text
.local-uckk-public-page
.local-uckk-public-page--home
.local-uckk-public-nav
.local-uckk-public-nav__link
.local-uckk-public-nav__link.is-active
.local-uckk-public-card
.local-uckk-public-notice
```

---

## 9. Classes officielles

### Racine

```text
.local-uckk
.local-uckk-public-page
.local-uckk-public-page--{slug}
```

### Navigation

```text
.local-uckk-public-nav
.local-uckk-public-nav__list
.local-uckk-public-nav__item
.local-uckk-public-nav__link
.local-uckk-public-nav__link.is-active
```

### Hero

```text
.local-uckk-public-hero
.local-uckk-public-eyebrow
.local-uckk-public-title
.local-uckk-public-subtitle
.local-uckk-public-summary
.local-uckk-public-boundary
```

### Corps

```text
.local-uckk-public-body
.local-uckk-public-main
.local-uckk-public-aside
.local-uckk-public-section
.local-uckk-public-section__title
.local-uckk-public-section__body
```

### Cartes

```text
.local-uckk-public-card
.local-uckk-public-card__eyebrow
.local-uckk-public-card__title
.local-uckk-public-card__body
.local-uckk-public-card__action
```

### Notices

```text
.local-uckk-public-notice
.local-uckk-public-notice--institutional
.local-uckk-public-notice--integrity
.local-uckk-public-notice--warning
.local-uckk-public-notice__title
.local-uckk-public-notice__body
```

### Tableaux

```text
.local-uckk-public-table
.local-uckk-public-table--equivalence
```

### Métadonnées

```text
.local-uckk-public-meta
.local-uckk-public-meta__label
.local-uckk-public-meta__value
```

### Appels à action

```text
.local-uckk-public-cta
.local-uckk-public-cta__title
.local-uckk-public-cta__body
.local-uckk-public-cta__action
```

---

## 10. Classes à abandonner

Les classes suivantes ne doivent plus être utilisées dans les nouvelles pages publiques :

```text
.local-uckk-public-page__nav-link
.local-uckk-public-nav-link
.local-uckk-kicker
.local-uckk-page-title
.local-uckk-page-intro
.local-uckk-info-card
.local-uckk-status-notice
.local-uckk-equivalence-table
```

Elles peuvent exister temporairement dans le code hérité, mais ne doivent plus être la base du système officiel.

---

## 11. Design tokens

Les tokens doivent être définis dans la racine `.local-uckk`.

```css
.local-uckk {
    --uckk-ink: var(--theme-uckk-ink, #172321);
    --uckk-ink-soft: var(--theme-uckk-ink-soft, #2f3f3b);
    --uckk-ink-muted: var(--theme-uckk-ink-muted, #52625e);

    --uckk-petrol: var(--theme-uckk-petrol, #1e6864);
    --uckk-petrol-dark: var(--theme-uckk-petrol-dark, #164c49);
    --uckk-petrol-soft: var(--theme-uckk-petrol-soft, #dbecea);
    --uckk-petrol-wash: var(--theme-uckk-petrol-wash, rgba(30, 104, 100, 0.08));

    --uckk-parchment: var(--theme-uckk-parchment, #f6f0df);
    --uckk-parchment-light: var(--theme-uckk-parchment-light, #fbf7eb);
    --uckk-parchment-deep: var(--theme-uckk-parchment-deep, #e6dcc2);

    --uckk-gold: var(--theme-uckk-gold, #b99045);
    --uckk-gold-dark: var(--theme-uckk-gold-dark, #7f642f);
    --uckk-gold-wash: var(--theme-uckk-gold-wash, rgba(185, 144, 69, 0.12));

    --uckk-card: var(--theme-uckk-card, #fffaf0);
    --uckk-line: var(--theme-uckk-line, rgba(23, 35, 33, 0.16));
    --uckk-shadow: var(--theme-uckk-shadow, 0 16px 36px rgba(23, 35, 33, 0.11));
    --uckk-shadow-soft: var(--theme-uckk-shadow-soft, 0 7px 18px rgba(23, 35, 33, 0.075));

    --uckk-radius: var(--theme-uckk-radius, 0.9rem);
    --uckk-radius-small: var(--theme-uckk-radius-small, 0.55rem);

    --uckk-public-base-width: var(--theme-uckk-public-max-width, 1420px);
    --uckk-public-max-width: min(calc(var(--uckk-public-base-width) + 3.5rem), 1480px);
    --uckk-public-gutter: clamp(0.75rem, 1.5vw, 1.55rem);
    --uckk-public-content-inset: var(--theme-uckk-public-content-inset, clamp(1rem, 2vw, 1.75rem));
}
```

Les tokens de largeur actuels ne sont plus limités à `1180px` pour toutes les pages. Le rail public peut aller jusqu’à environ `1480px` selon le layout, avec des variantes standard, wide ou full.

---

## 12. Couleurs officielles

| Usage | Token | Valeur |
|---|---:|---:|
| Texte principal | `--uckk-ink` | `#172321` |
| Texte secondaire | `--uckk-ink-soft` | `#2f3f3b` |
| Marque principale | `--uckk-petrol` | `#1e6864` |
| Marque sombre | `--uckk-petrol-dark` | `#164c49` |
| Fond parchemin | `--uckk-parchment` | `#f6f0df` |
| Fond clair | `--uckk-parchment-light` | `#fbf7eb` |
| Or vieilli | `--uckk-gold` | `#b99045` |
| Or sombre | `--uckk-gold-dark` | `#7f642f` |
| Bronze | `--uckk-bronze` | `#9b7441` |
| Bleu-gris | `--uckk-bluegrey` | `#5f7876` |
| Carte | `--uckk-card` | `#fffaf0` |

---

## 12.1 Canvas public, rail et motif héraldique

Le style public utilise un canvas décoratif limité aux pages publiques `local_uckk`.

Le canvas officiel est porté par :

```text
body.pagelayout-local_uckk_public
body:has(.local-uckk-public-page)
#page
#page.drawers
```

Il sert à poser :

```text
- le fond parchemin général ;
- le rail central de contenu ;
- le motif héraldique latéral sur grand écran.
```

Le motif héraldique est défini par :

```css
--uckk-side-motif-url: var(
    --theme-uckk-side-motif-url,
    url("/local/uckk/pix/heraldic-mosaic.gif")
);
```

Sur desktop, ce motif peut être répété autour du rail central. Il ne doit jamais nuire à la lecture du contenu central.

Sur mobile, le motif héraldique doit être désactivé complètement. Il ne doit pas apparaître sous forme de tranche, de bande latérale, de reste de tile ou de fragment derrière le centre de la page.

Règle officielle mobile :

```css
@media (max-width: 767.98px) {
    body.pagelayout-local_uckk_public,
    body:has(.local-uckk-public-page),
    body.pagelayout-local_uckk_public #page,
    body:has(.local-uckk-public-page) #page,
    body.pagelayout-local_uckk_public #page.drawers,
    body:has(.local-uckk-public-page) #page.drawers {
        background-color: var(--uckk-page-ground) !important;
        background-image: none !important;
        background-repeat: no-repeat !important;
        background-size: auto !important;
        background-position: 0 0 !important;
        background-attachment: scroll !important;
    }
}
```

Cette règle appartient à `local/uckk/styles.css`, parce qu’elle concerne le canvas des pages publiques `local_uckk`. Elle ne doit pas être déplacée dans `theme/uckk/scss/navigation.scss` ni traitée comme un problème de logo ou de barre de navigation.

---

## 13. Typographie

### Titres

Les titres principaux utilisent une sérif institutionnelle.

```css
font-family: Georgia, "Times New Roman", serif;
```

Usage :

- titre de page;
- titres de section;
- titres de cartes importantes;
- titres de notices.

### Texte courant

Le texte courant hérite de Moodle/Boost pour préserver la compatibilité.

Il doit rester :

- lisible;
- suffisamment grand;
- sobre;
- sans surcharge décorative.

### Hiérarchie recommandée

| Élément | Taille |
|---|---:|
| Titre de page | `clamp(2rem, 4vw, 3.35rem)` |
| Titre de section | `clamp(1.35rem, 2.2vw, 1.85rem)` |
| Intro | `clamp(1rem, 1.4vw, 1.15rem)` |
| Texte courant | `1rem` |
| Métadonnées | `0.82rem` |

---

## 14. Navigation publique

La navigation doit être :

- visible;
- compacte;
- lisible;
- utilisable au clavier;
- clairement active;
- cohérente sur toutes les pages.

### État actif

L’état actif doit avoir un contraste fort.

```css
.local-uckk-public-nav__link.is-active,
.local-uckk-public-nav__link[aria-current="page"] {
    background: var(--uckk-petrol);
    color: var(--uckk-parchment-light);
    border-color: var(--uckk-gold);
}
```

Le texte actif ne doit jamais avoir la même couleur que le fond.

---

## 15. Boutons

Les boutons UCKK doivent respecter Moodle/Bootstrap tout en portant l’identité UCKK.

### Primaire

```text
fond : vert pétrole
texte : parchemin clair
bordure : vert pétrole sombre ou or
```

### Secondaire

```text
fond : transparent ou parchemin
texte : vert pétrole sombre
bordure : vert pétrole
```

### Interdit

- bouton actif sans contraste;
- texte vert sur fond vert;
- fond or saturé avec texte blanc faible;
- `!important` sauf justification exceptionnelle;
- bouton qui change de taille au hover.

---

## 16. Cartes

Les cartes sont le module principal du site public.

Elles doivent être :

- sobres;
- séparées par des bordures fines;
- lisibles;
- alignées;
- non surchargées;
- légèrement ombrées.

Structure recommandée :

```html
<article class="local-uckk-public-card">
    <p class="local-uckk-public-card__eyebrow">Fonction</p>
    <h3 class="local-uckk-public-card__title">Défis</h3>
    <p class="local-uckk-public-card__body">...</p>
    <p class="local-uckk-public-card__action">
        <a href="...">Consulter</a>
    </p>
</article>
```

---

## 17. Notices institutionnelles

Les notices ne doivent pas crier. Elles doivent encadrer, clarifier et protéger l’institution.

Notice officielle courte :

> Reconnaissances internes UCKK — attestations de parcours propres à la cité-école, non encore reconnues par l’État.

Cette notice peut apparaître :

- sur À propos;
- sur Voies;
- sur Contact;
- dans le pied de page public;
- dans les pages de reconnaissance.

---

## 18. Tableaux

Les tableaux UCKK servent aux repères institutionnels.

Ils doivent être :

- sobres;
- lisibles;
- à bordure fine;
- horizontaux sur desktop;
- scrollables sur mobile.

Exemple : tableau d’équivalences d’appellations.

Colonnes recommandées :

```text
Ancienne appellation
Durée cumulative
Niveau UCKK
Titre interne
Parchemin UCKK
```

---

## 19. Responsive

### Desktop

- rail public centré jusqu’à environ `1480px` selon le layout ;
- largeur standard possible autour de `1180px` pour les pages non larges ;
- grilles sobres, souvent en 2 colonnes pour les cartes ;
- aside possible à droite ;
- motif héraldique latéral autorisé comme canvas décoratif, hors du contenu central.

### Tablette

- navigation flexible ;
- cartes en 1 ou 2 colonnes selon l’espace ;
- aside replié sous le contenu ou en grille ;
- marges et gouttières réduites ;
- motif décoratif toléré seulement s’il ne nuit pas au rail central.

### Mobile

- navigation scrollable ou liste compacte ;
- tableaux en scroll horizontal ;
- cartes pleine largeur ;
- titres réduits mais lisibles ;
- aside pleine largeur ;
- motif héraldique latéral interdit ;
- aucune image répétée, tranche d’armoiries ou tile décoratif ne doit apparaître derrière le contenu.

### Règle mobile obligatoire

Sur les écrans `max-width: 767.98px`, les fonds décoratifs de `body`, `#page` et `#page.drawers` doivent être neutralisés pour les pages publiques `local_uckk`.

La règle doit rester dans `local/uckk/styles.css`, non dans le thème global, car elle corrige le canvas propre au contrat public `local_uckk`.

---

## 20. Accessibilité

Le système doit respecter :

- contraste suffisant;
- `aria-current="page"` pour la navigation active;
- focus visible;
- titres hiérarchiques;
- liens explicites;
- absence d’information uniquement par couleur;
- tables lisibles;
- contenu accessible sans JavaScript.

### Focus officiel

```css
.local-uckk :focus-visible {
    outline: 3px solid rgba(185, 144, 69, 0.55);
    outline-offset: 3px;
}
```

---

## 21. Règles d’intégration Moodle

### Styles

Chaque page publique doit charger :

```php
$PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));
```

ou passer par un helper central qui le fait.

### Contrôleur mince

Un contrôleur public idéal :

```php
<?php
require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();

\local_uckk\local\public_pages::setup_page('contact', $context);

echo $OUTPUT->header();
echo $OUTPUT->render(new \local_uckk\output\public_page('contact'));
echo $OUTPUT->footer();
```

Le contrôleur ne doit pas contenir :

- navigation;
- cartes codées à la main;
- style inline;
- logique de reconnaissance;
- logique de permission complexe;
- HTML massif.

---

## 22. Relation avec `theme_uckk`

Le plugin `local_uckk` possède le style des **pages publiques institutionnelles UCKK**.

Le thème `theme_uckk` possède :

- l’identité globale;
- les variables visuelles globales;
- l’intégration Boost;
- le header général;
- les styles Moodle partagés.

Règle :

```text
local_uckk/styles.css
→ pages publiques UCKK seulement

theme_uckk/scss/*.scss
→ thème Moodle global et identité visuelle générale
```

Le plugin local ne doit pas corriger le header global Moodle.  
Le thème ne doit pas contenir la logique des pages publiques.

Exception importante : le canvas public `body/#page/#page.drawers` utilisé uniquement par les pages `local_uckk` appartient à `local/uckk/styles.css`. Le motif héraldique latéral, même s’il participe à l’identité visuelle globale, est appliqué par le style public et doit donc être corrigé dans `local_uckk` lorsqu’il affecte les pages publiques.

Règle de décision :

```text
Logo, navbar, menu utilisateur, Boost chrome -> theme_uckk.
Rail public, hero, cartes, notices, métadonnées, motif latéral local_uckk -> local/uckk/styles.css.
```

---

## 23. Interdictions

Interdit dans le style public :

```text
styles inline
navigation recopiée dans chaque page
!important comme stratégie principale
couleurs codées hors tokens
nouvelles classes sans convention
CSS global non scopé
prise de contrôle des pages Moodle core
patch en bas de fichier sans refactor
mélange de plusieurs conventions de classes
motif héraldique visible sur smartphone
correction du canvas public dans navigation.scss
confusion entre logo de navbar et background décoratif de page
```

---

## 24. Critères de qualité

Une page publique UCKK est acceptée si :

- elle utilise le template officiel;
- elle utilise la navigation officielle;
- elle charge `styles.css`;
- elle ne redéfinit pas son propre système;
- elle affiche clairement son état actif;
- elle respecte les couleurs officielles;
- elle reste lisible sur mobile;
- elle n’affiche pas de chaîne manquante;
- elle ne casse pas Moodle;
- elle ne prétend pas à une reconnaissance étatique;
- elle demeure cohérente avec le canon UCKK.

---

## 25. Checklist QA

### PHP

```powershell
C:\php\8.4\php.exe -l public\local\uckk\index.php
C:\php\8.4\php.exe -l public\local\uckk\classes\local\public_pages.php
C:\php\8.4\php.exe -l public\local\uckk\classes\output\public_page.php
```

### Cache

```powershell
C:\php\8.4\php.exe admin\cli\purge_caches.php
```

### Pages

Tester :

```text
/local/uckk/index.php
/local/uckk/about.php
/local/uckk/programs.php
/local/uckk/courses.php
/local/uckk/challenges.php
/local/uckk/assemblies.php
/local/uckk/integrity.php
/local/uckk/archives.php
/local/uckk/news.php
/local/uckk/contact.php
```

### À vérifier visuellement

```text
navigation uniforme
état actif lisible
pas de texte sur fond de même couleur
cartes alignées
titres cohérents
notices sobres
mobile acceptable
tableaux scrollables
aucun motif héraldique visible sur smartphone
aucune tranche de background décoratif derrière le contenu central
fond public mobile uniforme après purge des caches
```

---

## 26. Politique de migration

La migration doit être faite proprement.

### Étape unique

Remplacer ensemble :

```text
public/local/uckk/styles.css
public/local/uckk/templates/public_page.mustache
public/local/uckk/classes/output/public_page.php
public/local/uckk/classes/local/public_pages.php
```

Puis simplifier les contrôleurs publics.

### À éviter

Ne pas corriger page par page avec des patchs indépendants.

Ne pas ajouter des exceptions pour chaque page.

Ne pas résoudre un problème de contraste avec un bloc isolé si le système de classes est incohérent.

---

## 27. Glossaire visuel

| Terme | Définition |
|---|---|
| Shell public | Structure commune d’une page publique UCKK |
| Hero | Bandeau principal d’une page |
| Notice | Encadré institutionnel ou canonique |
| Carte | Module de contenu compact |
| CTA | Appel à action |
| Token | Variable CSS officielle |
| État actif | Page courante dans la navigation |
| Boundary notice | Notice de frontière institutionnelle |
| Canon visuel | Ensemble des règles symboliques et graphiques UCKK |

---

## 28. Résumé exécutable

Le système officiel est :

```text
Un seul style public.
Un seul template.
Un seul registre de pages.
Des contrôleurs minces.
Des classes cohérentes.
Des tokens centralisés.
Un canvas public documenté.
Un motif héraldique autorisé sur desktop.
Aucun motif héraldique sur smartphone.
Aucun patch local non documenté.
Aucune prise de contrôle de Moodle core.
```

La priorité :

```text
lisible
institutionnel
sobre
cohérent
canonique
maintenable
```

