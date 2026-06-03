Voici la version mise à jour du document `docs/uckk_ops_update_plan.md`, avec la gestion serveur ajoutée : purge caches OVH après sync, smoke serveur, et distinction local/serveur. Source mise à jour depuis ta mini-doc actuelle. 

# UCKK Ops — Mini-doc upgrade : Update Plan

## Fichier cible

```text
docs/uckk_ops_update_plan.md
```

## Objectif

Ajouter à l’app UCKK Ops une étape simple de détection avant les opérations de sync, build, upgrade, purge, smoke et déploiement serveur.

Cette étape doit répondre à une seule question :

```text
Quels changements existent, et quelles actions sont nécessaires localement et sur le serveur?
```

Le scan ne doit rien exécuter automatiquement.

## Principe

L’app garde un workflow simple en trois temps :

```text
1. Préparer localement
2. Publier le code
3. Publier en ligne
```

On ajoute un mode :

```text
Scan update plan
```

Ce mode lit l’état Git du dépôt source, classe les fichiers modifiés par composant Moodle, puis recommande les actions nécessaires.

Le scan est strictement read-only.

Il ne doit jamais exécuter :

```text
robocopy
rsync
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

## Racines utilisées

Les valeurs doivent venir de `uckk-ops.config.json`.

```text
sourceRoot          = C:\mycode\UCKK\uckk-moodle
runtimeRoot         = C:\mycode\UCKK\moodle\moodle\public
moodleRoot          = C:\mycode\UCKK\moodle\moodle
moodleCliRoot       = C:\mycode\UCKK\moodle\moodle\admin\cli
localUrl            = http://127.0.0.1:8000
repoRoot            = C:\mycode\UCKK\uckk-moodle
branch              = main

server.sshTarget    = ubuntu@57.129.115.159
server.sourceRoot   = /opt/uckk/uckk-moodle
server.runtimeRoot  = /var/www/moodle/public
server.moodleRoot   = /var/www/moodle
server.moodleCliRoot = /var/www/moodle/admin/cli
server.publicUrl    = https://uckk.org
```

Aucun chemin Moodle ne doit être hardcodé dans le nouveau module si la config le fournit déjà.

## Fichiers à modifier

```text
tools/uckk-ops/uckk-ops.config.json
tools/uckk-ops/uckk_ops_gui.ps1
tools/uckk-ops/lib/UckkOps.Server.psm1
tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
docs/uckk_ops_update_plan.md
```

## Fichiers à ne pas modifier dans cette phase

```text
tools/uckk-ops/UCKK_Ops_Console_RUN.bat
tools/uckk-ops/lib/UckkOps.Common.psm1
tools/uckk-ops/lib/UckkOps.Git.psm1
tools/uckk-ops/lib/UckkOps.Local.psm1
tools/uckk-ops/lib/UckkOps.Seed.psm1
tools/uckk-ops/lib/UckkOps.Smoke.psm1
```

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
 M theme/uckk/scss/uckk.scss
?? theme/uckk/layout/login.php
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

theme/uckk/layout/login.php
→ theme/uckk
```

Ne pas ajouter les sous-dossiers suivants comme composants :

```text
local/uckk/amd/src
local/uckk/amd/build
local/uckk/templates/pages
local/uckk/classes/external
theme/uckk/scss
theme/uckk/layout
theme/uckk/amd/src
theme/uckk/amd/build
```

Ils sont déjà couverts par leur composant parent.

## Composants non Moodle

Certains chemins doivent être classés sans déclencher d’action Moodle.

```text
tools/uckk-ops/**
→ tools/uckk-ops

docs/**
→ docs
```

Ces chemins peuvent justifier un commit/push, mais ne doivent pas déclencher :

```text
Local sync
AMD build
Moodle upgrade
Purge caches Moodle
Smoke tests Moodle
Seed
Deploy serveur
```

## Objet retourné

La fonction principale doit retourner un objet simple.

```powershell
[pscustomobject]@{
    HasChanges          = $true
    HasMoodleChanges    = $true
    IsOpsOnly           = $false

    ChangedFiles        = @()
    ChangedComponents   = @()
    UnmappedFiles       = @()

    NeedsLocalSync      = $false
    NeedsAmdBuild       = $false
    NeedsMoodleUpgrade  = $false
    NeedsPurgeCaches    = $false
    NeedsSmokeTests     = $false
    NeedsSeedApply      = $false

    NeedsServerSync     = $false
    NeedsServerUpgrade  = $false
    NeedsServerPurge    = $false
    NeedsServerSmoke    = $false

    Reasons             = @()
    Warnings            = @()
    RecommendedOrder    = @()
    CommandPreview      = @()
    SuggestedSmokeUrls  = @()

    VisibleActions      = [pscustomobject]@{
        BuildAmd          = $false
        MoodleUpgrade     = $false
        PurgeCaches       = $false
        SmokeLocal        = $false
        SeedDryRun        = $false
        ServerDeploy      = $false
        ServerUpgrade     = $false
        ServerPurgeCaches = $false
        ServerSmoke       = $false
    }

    SimpleStatus         = ""
    SpecialInstructions  = @()
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
6. distinguer actions locales et actions serveur
7. retourner un objet UpdatePlan
```

Cette fonction ne doit rien exécuter.

## Fonctions internes proposées

```powershell
Get-UckkOpsChangedFiles
Resolve-UckkOpsChangedComponent
Resolve-UckkOpsRequiredActions
Get-UckkOpsRecommendedOrder
Get-UckkOpsCommandPreview
Get-UckkOpsSuggestedSmokeUrls
Format-UckkOpsUpdatePlan
```

## Règles d’action locales

### AMD source

```text
*/amd/src/*.js
→ NeedsAmdBuild = true
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

### AMD build

```text
*/amd/build/*.js
*/amd/build/*.map
→ NeedsLocalSync = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerSmoke = true
```

### Upgrade Moodle

```text
*/db/*.php
*/db/install.xml
*/version.php
→ NeedsMoodleUpgrade = true
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerUpgrade = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

### Langues

```text
*/lang/*.php
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
```

### Templates

```text
*/templates/*.mustache
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

### Styles et thème

```text
*/styles.css
theme/uckk/**
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

Important : tout changement `theme/uckk/**` doit déclencher une purge caches serveur après le sync OVH. Sinon le serveur peut servir un mélange :

```text
nouveau PHP / ancien CSS compilé
```

### Registry / seed

```text
academic_registry_json/*.json
→ NeedsLocalSync = true
→ NeedsSeedApply = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

Le scan peut recommander un seed dry-run, mais ne doit jamais lancer un seed apply automatiquement.

## Ordre recommandé

### Dépôt propre

```text
No actions required.
```

### Changement outil seulement

Exemple :

```text
tools/uckk-ops/uckk_ops_gui.ps1
```

Ordre :

```text
1. Review Git diff/status
2. Commit
3. Push
```

Aucune action Moodle locale ou serveur.

### Changement thème

Exemple :

```text
theme/uckk/layout/login.php
theme/uckk/scss/uckk.scss
```

Ordre :

```text
1. Validate source
2. Sync source to local runtime
3. Purge local caches
4. Run local smoke tests
5. Review Git diff/status
6. Commit
7. Push
8. Deploy server
9. Purge server caches
10. Run server smoke tests
```

### Changement AMD source

Exemple :

```text
local/uckk/amd/src/course_explorer.js
```

Ordre :

```text
1. Build AMD
2. Sync source to local runtime
3. Purge local caches
4. Run local smoke tests
5. Review Git diff/status
6. Commit
7. Push
8. Deploy server
9. Purge server caches
10. Run server smoke tests
```

### Changement DB / version

Exemple :

```text
local/uckk/db/services.php
local/uckk/version.php
```

Ordre :

```text
1. Validate source
2. Sync source to local runtime
3. Run Moodle upgrade local
4. Purge local caches
5. Run local smoke tests
6. Review Git diff/status
7. Commit
8. Push
9. Deploy server
10. Run Moodle upgrade server
11. Purge server caches
12. Run server smoke tests
```

## GUI — Onglet Simple

L’onglet Simple doit présenter le workflow comme une séquence claire.

```text
Étape 1 — Préparer localement
[Scan changes] [Sync local] [Launch local]

Actions spéciales après scan :
[Build AMD]
[Upgrade local]
[Purge caches local]
[Smoke local]
[Seed dry-run]

Étape 2 — Publier le code
[Sync GitHub]

Étape 3 — Publier en ligne
[Trigger OVH sync]
[Purge server caches]
[Smoke server]
```

Le bouton ancien :

```text
Run normal workflow
```

ne doit plus être visible dans l’onglet Simple.

S’il reste accessible ailleurs, il doit être renommé explicitement :

```text
DANGER: Run full workflow without validation
```

## GUI — Comportement du scan

Le bouton :

```text
Scan changes
```

appelle :

```powershell
Get-UckkOpsUpdatePlan
```

Affichage minimal attendu :

```text
Changed components:
- theme/uckk
- local/uckk

Required local actions:
- AMD build: yes/no
- Local sync: yes/no
- Moodle upgrade: yes/no
- Purge local caches: yes/no
- Smoke local: yes/no
- Seed dry-run: yes/no

Required server actions:
- Server sync: yes/no
- Server upgrade: yes/no
- Purge server caches: yes/no
- Smoke server: yes/no

Recommended order:
1. ...
2. ...
3. ...
```

Le bouton ne doit rien exécuter.

## GUI — Trigger OVH sync

Le bouton :

```text
Trigger OVH sync
```

ne doit plus faire seulement :

```powershell
Invoke-UckkServerPull
Sync-UckkServerSourceToRuntime
```

Il doit utiliser le plan courant.

Comportement recommandé :

```powershell
Invoke-UckkServerDeployPlanned -Plan $Script:LastUpdatePlan
```

Si aucun scan n’a été fait, l’app doit afficher une confirmation :

```text
Aucun plan d’update récent n’est disponible.
Voulez-vous quand même déployer sur OVH avec purge caches serveur et smoke tests?
```

En fallback sécurisé, sans plan récent :

```text
1. Pull serveur
2. Sync serveur
3. Purge caches serveur
4. Smoke serveur
```

## Fonction serveur planifiée

Ajouter dans :

```text
tools/uckk-ops/lib/UckkOps.Server.psm1
```

la fonction :

```powershell
Invoke-UckkServerDeployPlanned
```

Signature proposée :

```powershell
function Invoke-UckkServerDeployPlanned {
    param(
        [object]$Plan,
        [switch]$ForcePurge,
        [switch]$ForceSmoke
    )
}
```

Responsabilités :

```text
1. git pull serveur
2. sync source -> runtime serveur
3. si Plan.NeedsServerUpgrade : upgrade.php serveur
4. si Plan.NeedsServerPurge ou ForcePurge : purge_caches.php serveur
5. si Plan.NeedsServerSmoke ou ForceSmoke : smoke tests serveur
```

Elle doit logger chaque étape.

Elle ne doit pas faire de seed apply automatique.

## Comportement serveur minimal accepté

Pour éviter les états incohérents, après un `Trigger OVH sync`, si le plan indique un changement Moodle mais que les flags serveur sont absents ou impossibles à lire, l’app doit faire au minimum :

```text
1. Pull serveur
2. Sync serveur
3. Purge caches serveur
4. Smoke serveur
```

C’est plus sûr que de déployer sans purge.

## Smoke URLs à ajouter

Dans `uckk-ops.config.json`, ajouter ou conserver :

### Local

```json
"http://127.0.0.1:8000",
"http://127.0.0.1:8000/login/index.php",
"http://127.0.0.1:8000/course/index.php",
"http://127.0.0.1:8000/local/uckk/programs.php",
"http://127.0.0.1:8000/local/uckk/courses.php",
"http://127.0.0.1:8000/local/uckk/mediatheque.php"
```

### Serveur

```json
"https://uckk.org",
"https://uckk.org/login/index.php",
"https://uckk.org/course/index.php",
"https://uckk.org/local/uckk/programs.php",
"https://uckk.org/local/uckk/courses.php",
"https://uckk.org/local/uckk/mediatheque.php"
```

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
NeedsServerSync = true
NeedsServerPurge = true
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
NeedsServerSync = true
NeedsServerPurge = true
NeedsServerSmoke = true
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
NeedsServerSync = true
NeedsServerUpgrade = true
NeedsServerPurge = true
NeedsServerSmoke = true
```

### Test 5 — thème login modifié

Fichiers modifiés :

```text
theme/uckk/layout/login.php
theme/uckk/scss/uckk.scss
```

Résultat attendu :

```text
ChangedComponents = theme/uckk
NeedsLocalSync = true
NeedsPurgeCaches = true
NeedsSmokeTests = true
NeedsServerSync = true
NeedsServerPurge = true
NeedsServerSmoke = true
NeedsMoodleUpgrade = false
NeedsAmdBuild = false
```

Après `Trigger OVH sync`, l’app doit exécuter :

```text
Pull serveur
Sync serveur
Purge caches serveur
Smoke serveur
```

### Test 6 — outils Ops seulement

Fichier modifié :

```text
tools/uckk-ops/uckk_ops_gui.ps1
```

Résultat attendu :

```text
ChangedComponents = tools/uckk-ops
NeedsLocalSync = false
NeedsPurgeCaches = false
NeedsSmokeTests = false
NeedsServerSync = false
NeedsServerPurge = false
NeedsServerSmoke = false
```

## Critères d’acceptation

```text
- Le scan fonctionne sur un dépôt propre.
- Le scan détecte les fichiers modifiés et non suivis.
- Le scan classe correctement local/uckk.
- Le scan classe correctement blocks/uckk_dashboard.
- Le scan classe correctement theme/uckk.
- Le scan classe tools/uckk-ops sans déclencher d’action Moodle.
- Le scan classe docs sans déclencher d’action Moodle.
- Le scan détecte AMD build requis pour amd/src/*.js.
- Le scan détecte upgrade requis pour db/*.php, install.xml et version.php.
- Le scan détecte purge requise pour lang/templates/styles/theme.
- Le scan distingue actions locales et actions serveur.
- Le scan affiche un ordre recommandé incluant le serveur.
- Trigger OVH sync purge les caches serveur quand le plan le demande.
- Trigger OVH sync lance les smoke tests serveur quand le plan le demande.
- Le scan n’exécute aucune action destructive ou distante.
- Le bouton Simple ne contient plus de full workflow automatique.
```

## Stratégie de commit

Commit unique recommandé pour cette vague :

```text
Improve UCKK Ops planned server deploy workflow
```

Inclut :

```text
tools/uckk-ops/uckk-ops.config.json
tools/uckk-ops/uckk_ops_gui.ps1
tools/uckk-ops/lib/UckkOps.Server.psm1
tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
docs/uckk_ops_update_plan.md
```

## Hors périmètre

```text
- Seed apply automatique
- Auto-commit
- Auto-push
- Auto-build sans confirmation
- Correction automatique des fichiers Moodle
- Gestion fine des dépendances entre plugins
- Réécriture du formulaire login Moodle
- Déploiement serveur sans confirmation utilisateur
```
