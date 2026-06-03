# UCKK Ops — Mini-doc upgrade : Update Plan

## Fichier cible

```text
docs/uckk_ops_update_plan.md
```

## Objectif

Ajouter à l’app UCKK Ops une étape simple de détection avant les opérations de sync/build/upgrade.

Cette étape doit répondre à une seule question :

```text
Quels changements existent, et quelles actions sont nécessaires?
```

Elle ne doit rien exécuter automatiquement.

## Principe

L’app garde son workflow actuel.

On ajoute seulement un mode :

```text
Scan update plan
```

Ce mode lit l’état Git du dépôt source, classe les fichiers modifiés par composant Moodle, puis recommande les actions nécessaires.

## Racines utilisées

Les valeurs doivent venir de `uckk-ops.config.json`.

```text
sourceRoot     = C:\mycode\UCKK\uckk-moodle
runtimeRoot    = C:\mycode\UCKK\moodle\moodle\public
moodleRoot     = C:\mycode\UCKK\moodle\moodle
moodleCliRoot  = C:\mycode\UCKK\moodle\moodle\admin\cli
localUrl       = http://127.0.0.1:8000
repoRoot       = C:\mycode\UCKK\uckk-moodle
branch         = main
```

Aucun chemin Moodle ne doit être hardcodé dans le nouveau module si la config le fournit déjà.

## Fichier à créer

```text
tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
```

## Fichiers à modifier

```text
tools/uckk-ops/uckk-ops.config.json
tools/uckk-ops/uckk_ops_gui.ps1
tools/uckk-ops/lib/UckkOps.Git.psm1
tools/uckk-ops/lib/UckkOps.Local.psm1
tools/uckk-ops/lib/UckkOps.Smoke.psm1
tools/uckk-ops/UCKK_Ops_Console_RUN.bat
docs/uckk_ops_update_plan.md
```

## Fichiers à ne pas modifier dans cette phase

```text
tools/uckk-ops/lib/UckkOps.Server.psm1
tools/uckk-ops/lib/UckkOps.Seed.psm1
```

Le serveur et les seeds restent hors périmètre pour cette première version du plan d’update.

## Détection des changements

La détection doit utiliser :

```powershell
git -C $RepoRoot status --porcelain=v1
```

Raison : cette sortie détecte à la fois les fichiers modifiés et les nouveaux fichiers non suivis.

Exemples de fichiers à détecter :

```text
 M local/uckk/db/services.php
 M local/uckk/version.php
?? local/uckk/amd/src/course_explorer.js
?? local/uckk/templates/pages/course_explorer.mustache
 M blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
```

## Mapping des composants

Le mapping doit utiliser la liste `components` de la config.

Exemples :

```text
local/uckk/amd/src/course_explorer.js
→ local/uckk

blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
→ blocks/uckk_dashboard

theme/uckk/scss/uckk.scss
→ theme/uckk
```

Ne pas ajouter les sous-dossiers suivants comme composants :

```text
local/uckk/amd/src
local/uckk/amd/build
local/uckk/templates/pages
local/uckk/classes/external
```

Ils sont déjà couverts par le composant parent `local/uckk`.

## Objet retourné

La fonction principale doit retourner un objet simple.

```powershell
[pscustomobject]@{
    HasChanges         = $true
    ChangedFiles       = @()
    ChangedComponents  = @()
    NeedsLocalSync     = $false
    NeedsAmdBuild      = $false
    NeedsMoodleUpgrade = $false
    NeedsPurgeCaches   = $false
    NeedsSmokeTests    = $false
    NeedsSeedApply     = $false
    Warnings           = @()
    RecommendedOrder   = @()
}
```

## Fonction principale

```powershell
Get-UckkOpsUpdatePlan
```

Responsabilités :

```text
1. lire la config
2. lire git status --porcelain=v1
3. extraire les chemins modifiés
4. classer les fichiers par composant
5. appliquer les règles d’action
6. retourner un objet UpdatePlan
```

Cette fonction ne doit pas exécuter :

```text
robocopy
grunt
upgrade.php
purge_caches.php
git add
git commit
git push
ssh
seed
deploy serveur
```

## Fonctions internes proposées

```powershell
Get-UckkOpsChangedFiles
Resolve-UckkOpsChangedComponent
Resolve-UckkOpsRequiredActions
Format-UckkOpsUpdatePlan
```

## Règles d’action

### AMD

```text
*/amd/src/*.js
→ NeedsAmdBuild = true
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
```

```text
*/amd/build/*.js
*/amd/build/*.map
→ NeedsLocalSync = true
→ NeedsSmokeTests = true
```

### Upgrade Moodle

```text
*/db/*.php
*/db/install.xml
*/version.php
→ NeedsMoodleUpgrade = true
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
```

### Langues

```text
*/lang/*.php
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
```

### Templates et styles

```text
*/templates/*.mustache
*/styles.css
theme/uckk/**
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
```

### Seed

```text
academic_registry_json/*.json
→ NeedsSeedApply = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
```

### App Ops elle-même

```text
tools/uckk-ops/**
→ aucune action Moodle requise
```

## Ordre recommandé

L’objet `RecommendedOrder` doit contenir seulement les étapes nécessaires.

Ordre complet possible :

```text
1. Validate source
2. Build AMD
3. Sync source to local runtime
4. Run Moodle upgrade
5. Purge local caches
6. Run local smoke tests
7. Review Git diff/status
8. Commit
9. Push
```

Exemple pour un changement `local/uckk/db/services.php` :

```text
1. Sync source to local runtime
2. Run Moodle upgrade
3. Purge local caches
4. Run local smoke tests
5. Review Git diff/status
6. Commit
```

Exemple pour un changement `local/uckk/amd/src/course_explorer.js` :

```text
1. Build AMD
2. Sync source to local runtime
3. Purge local caches
4. Run local smoke tests
5. Review Git diff/status
6. Commit
```

## Smoke URLs à ajouter

Dans `uckk-ops.config.json`, ajouter la nouvelle page Cours.

Local :

```json
"http://127.0.0.1:8000/local/uckk/courses.php"
```

Serveur :

```json
"https://uckk.org/local/uckk/courses.php"
```

## GUI

Ajouter un bouton :

```text
Scan update plan
```

Ce bouton appelle :

```powershell
Get-UckkOpsUpdatePlan
```

Affichage minimal attendu :

```text
Changed components:
- local/uckk
- blocks/uckk_dashboard

Required actions:
- AMD build: yes/no
- Local sync: yes/no
- Moodle upgrade: yes/no
- Purge caches: yes/no
- Smoke tests: yes/no
- Seed apply: yes/no

Recommended order:
1. ...
2. ...
3. ...
```

Le bouton ne doit rien exécuter.

## Tests manuels attendus

### Test 1 — dépôt propre

Commande :

```powershell
Get-UckkOpsUpdatePlan
```

Résultat attendu :

```text
HasChanges = false
ChangedFiles = empty
ChangedComponents = empty
No actions required
```

### Test 2 — fichier langue modifié

Fichier modifié :

```text
blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
```

Résultat attendu :

```text
ChangedComponents = blocks/uckk_dashboard
NeedsLocalSync = true
NeedsPurgeCaches = true
NeedsSmokeTests = false ou true selon choix UI
NeedsMoodleUpgrade = false
NeedsAmdBuild = false
```

### Test 3 — fichier AMD source modifié

Fichier modifié :

```text
local/uckk/amd/src/course_explorer.js
```

Résultat attendu :

```text
ChangedComponents = local/uckk
NeedsAmdBuild = true
NeedsLocalSync = true
NeedsPurgeCaches = true
NeedsSmokeTests = true
```

### Test 4 — db/services.php modifié

Fichier modifié :

```text
local/uckk/db/services.php
```

Résultat attendu :

```text
ChangedComponents = local/uckk
NeedsMoodleUpgrade = true
NeedsLocalSync = true
NeedsPurgeCaches = true
NeedsSmokeTests = true
```

## Critères d’acceptation

```text
- Le scan fonctionne sur un dépôt propre.
- Le scan détecte les fichiers modifiés et non suivis.
- Le scan classe correctement local/uckk.
- Le scan classe correctement blocks/uckk_dashboard.
- Le scan détecte AMD build requis pour amd/src/*.js.
- Le scan détecte upgrade requis pour db/*.php et version.php.
- Le scan détecte purge requise pour lang/templates/styles.
- Le scan affiche un ordre recommandé.
- Le scan n’exécute aucune action destructive ou distante.
```

## Stratégie de commit

Commit 1 :

```text
Add UCKK Ops update plan documentation
```

Commit 2 :

```text
Add UCKK Ops update plan scanner
```

Commit 3 :

```text
Expose update plan scan in UCKK Ops GUI
```

## Hors périmètre

```text
- Déploiement serveur automatique
- Seed automatique
- Auto-commit
- Auto-push
- Auto-build sans confirmation
- Correction automatique des fichiers Moodle
- Gestion fine des dépendances entre plugins
```
