# UCKK Ops Console — Documentation opérationnelle

**Statut :** document courant de fonctionnement  
**Portée :** `tools/uckk-ops` et workflows locaux/serveur de UCKK Moodle  
**Date :** 2026-06-04

## 1. Rôle de l’Ops Console

UCKK Ops Console est l’outil d’orchestration pour préparer, vérifier, publier et déployer le dépôt UCKK Moodle.

Elle coordonne quatre familles d’actions :

```text
1. Scanner les changements
2. Préparer le runtime local
3. Publier le code vers GitHub
4. Déployer le code sur OVH
```

Le scan est toujours read-only. Les actions qui modifient le runtime, les caches, Git ou le serveur sont exécutées uniquement par des étapes explicites.

## 2. Sources d’autorité

La configuration vient de :

```text
tools/uckk-ops/uckk-ops.config.json
```

La logique opérationnelle doit être centralisée dans les modules :

```text
tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
tools/uckk-ops/lib/UckkOps.Local.psm1
tools/uckk-ops/lib/UckkOps.Server.psm1
tools/uckk-ops/lib/UckkOps.Git.psm1
tools/uckk-ops/lib/UckkOps.Smoke.psm1
tools/uckk-ops/lib/UckkOps.Seed.psm1
tools/uckk-ops/lib/UckkOps.Common.psm1
```

La GUI est une couche d’interface :

```text
tools/uckk-ops/uckk_ops_gui.ps1
```

Elle affiche les boutons, les logs, les confirmations et les résultats. Elle ne doit pas devenir une deuxième source de vérité pour les règles de workflow.

## 3. Racines opérationnelles

Les racines locales et serveur sont fournies par la config.

```text
local.sourceRoot        = C:\mycode\UCKK\uckk-moodle
local.runtimeRoot       = C:\mycode\UCKK\moodle\moodle\public
local.moodleRoot        = C:\mycode\UCKK\moodle\moodle
local.moodleCliRoot     = C:\mycode\UCKK\moodle\moodle\admin\cli
local.localUrl          = http://127.0.0.1:8000

git.repoRoot            = C:\mycode\UCKK\uckk-moodle
git.remote              = origin
git.branch              = main

server.sshTarget        = ubuntu@57.129.115.159
server.sourceRoot       = /opt/uckk/uckk-moodle
server.runtimeRoot      = /var/www/moodle/public
server.moodleRoot       = /var/www/moodle
server.moodleCliRoot    = /var/www/moodle/admin/cli
server.publicUrl        = https://uckk.org
```

Aucun chemin Moodle ne doit être hardcodé dans la logique si la config le fournit déjà.

## 4. Principe source/runtime

Le dépôt source est l’autorité Git.

```text
sourceRoot = dépôt de travail Git
runtimeRoot = copie locale exécutable par Moodle
```

Le runtime est une cible de synchronisation. Il peut être supprimé, régénéré ou resynchronisé depuis le source.

Règle de base :

```text
sourceRoot → runtimeRoot
```

Le runtime ne doit pas devenir la seule copie d’un fichier qui doit être commité.

## 5. Workflow opérateur

Le workflow simple est :

```text
1. Scanner
2. Préparer local
3. Publier GitHub
4. Publier OVH
```

### 5.1 Scanner

Le scan lit l’état Git et produit un `UpdatePlan`.

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

### 5.2 Préparer local

La préparation locale exécute les actions nécessaires selon le plan.

Ordre opérationnel :

```text
1. Sync source → runtime local
2. Build AMD si nécessaire
3. Upgrade Moodle local si nécessaire
4. Apply UCKK site profile local
5. Purge caches local si nécessaire
6. Launch/open Moodle local
7. Smoke tests local si nécessaire
```

### 5.3 Publier GitHub

La publication GitHub est une étape explicite.

Elle s’appuie sur :

```text
git status
git diff
git add
git commit
git push
```

L’opérateur doit pouvoir revoir les changements avant commit.

### 5.4 Publier OVH

La publication OVH utilise le plan courant.

Ordre serveur :

```text
1. Pull source serveur
2. Sync source serveur → runtime serveur
3. Upgrade Moodle serveur si nécessaire
4. Apply UCKK site profile serveur
5. Purge caches serveur si nécessaire
6. Smoke tests serveur si nécessaire
```

Si aucun plan récent n’est disponible, le fallback sécurisé est :

```text
1. Pull serveur
2. Sync serveur
3. Purge caches serveur
4. Smoke serveur
```

## 6. Scan et objet UpdatePlan

La fonction centrale est :

```powershell
Get-UckkOpsUpdatePlan
```

Elle retourne un objet de planification.

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

## 7. Détection des changements

La détection Git utilise :

```powershell
git -C $RepoRoot status --porcelain=v1
```

Cette sortie détecte les fichiers modifiés, supprimés, déplacés et non suivis.

Exemples :

```text
 M local/uckk/db/services.php
 M local/uckk/version.php
?? local/uckk/amd/src/course_explorer.js
?? local/uckk/templates/pages/course_explorer.mustache
 M blocks/uckk_dashboard/lang/fr/block_uckk_dashboard.php
 M theme/uckk/scss/uckk.scss
?? theme/uckk/layout/login.php
```

## 8. Mapping des composants

Le mapping utilise la liste `components` de la config.

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

Les sous-dossiers suivants ne sont pas des composants autonomes :

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

Ils sont couverts par leur composant parent.

## 9. Composants non Moodle

Certains chemins peuvent être commités/poussés sans action Moodle.

```text
tools/uckk-ops/** → tools/uckk-ops
docs/**           → docs
```

Ces chemins ne déclenchent pas :

```text
Local sync
AMD build
Moodle upgrade
Purge caches Moodle
Smoke tests Moodle
Seed
Deploy serveur
```

## 10. Règles d’action

### 10.1 AMD source

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

### 10.2 AMD build

```text
*/amd/build/*.js
*/amd/build/*.map
→ NeedsLocalSync = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerSmoke = true
```

### 10.3 Upgrade Moodle

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

### 10.4 Langues

```text
*/lang/*.php
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
```

### 10.5 Templates

```text
*/templates/*.mustache
→ NeedsLocalSync = true
→ NeedsPurgeCaches = true
→ NeedsSmokeTests = true
→ NeedsServerSync = true
→ NeedsServerPurge = true
→ NeedsServerSmoke = true
```

### 10.6 Styles et thème

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

Tout changement `theme/uckk/**` déclenche une purge caches serveur après sync OVH afin d’éviter un état mélangé :

```text
nouveau PHP / ancien CSS compilé
```

### 10.7 Registry / seed

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

Le scan peut recommander un seed dry-run, mais ne lance jamais un seed apply automatiquement.

## 11. Contrat AMD

Le build AMD doit produire les fichiers `amd/build` qui seront publiés.

Politique opérationnelle :

```text
1. Le source Git reste l’autorité.
2. Les fichiers amd/build générés doivent exister dans sourceRoot avant commit.
3. Le runtime local est ensuite synchronisé depuis sourceRoot.
```

Commande AMD locale :

```powershell
cd "C:\mycode\UCKK\moodle\moodle"
npx grunt amd --root="C:\mycode\UCKK\uckk-moodle\local\uckk" --no-color
```

Alternative runtime-only acceptée pour validation locale :

```powershell
cd "C:\mycode\UCKK\moodle\moodle"
npx grunt amd --root=public/local/uckk --no-color
```

Si le build est fait dans le runtime, il valide le comportement local, mais il ne remplace pas le build source requis pour GitHub.

Sous Windows, en cas d’erreur `kill EPERM`, l’Ops Console arrête les vieux processus Node puis retente une fois :

```powershell
Get-Process node -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process rollup -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process grunt -ErrorAction SilentlyContinue | Stop-Process -Force
```

## 12. Purge caches locale

La purge locale utilise le Moodle root, pas le web root.

Commande correcte :

```powershell
cd "C:\mycode\UCKK\moodle\moodle"
php .\admin\cli\purge_caches.php
```

Commande incorrecte :

```powershell
php .\public\admin\cli\purge_caches.php
```

L’Ops Console résout le script depuis :

```text
local.moodleCliRoot
local.runtimeRoot/admin/cli
local.moodleRoot/admin/cli
```

## 13. GUI — onglet Simple

L’onglet Simple présente le workflow comme une séquence claire.

```text
Étape 1 — Scanner
[Scan changes]

Étape 2 — Préparer localement
[Préparer local]

Étape 3 — Publier le code
[Publier GitHub]

Étape 4 — Publier en ligne
[Publier OVH]
```

Le scan affiche au minimum :

```text
Changed components
Changed files
Required local actions
Required server actions
Warnings
Recommended order
AMD command preview
Smoke URLs
```

Le bouton `Run normal workflow` n’appartient pas à l’onglet Simple.

S’il reste disponible ailleurs, il est explicitement nommé :

```text
DANGER: Run full workflow without validation
```

## 14. Déploiement serveur planifié

La fonction serveur planifiée est :

```powershell
Invoke-UckkServerDeployPlanned
```

Signature opérationnelle :

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
2. sync source → runtime serveur
3. upgrade.php serveur si Plan.NeedsServerUpgrade
4. apply UCKK site profile serveur
5. purge_caches.php serveur si Plan.NeedsServerPurge ou ForcePurge
6. smoke tests serveur si Plan.NeedsServerSmoke ou ForceSmoke
```

Elle logge chaque étape et ne fait jamais de seed apply automatique.

## 15. Smoke URLs

### Local

```text
http://127.0.0.1:8000
http://127.0.0.1:8000/login/index.php
http://127.0.0.1:8000/course/index.php
http://127.0.0.1:8000/local/uckk/programs.php
http://127.0.0.1:8000/local/uckk/courses.php
http://127.0.0.1:8000/local/uckk/mediatheque.php
```

### Serveur

```text
https://uckk.org
https://uckk.org/login/index.php
https://uckk.org/course/index.php
https://uckk.org/local/uckk/programs.php
https://uckk.org/local/uckk/courses.php
https://uckk.org/local/uckk/mediatheque.php
```

## 16. Ordres recommandés

### 16.1 Dépôt propre

```text
No actions required.
```

### 16.2 Outils Ops seulement

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

### 16.3 Templates ou styles

Exemple :

```text
local/uckk/templates/pages/course_explorer.mustache
theme/uckk/scss/uckk.scss
```

Ordre :

```text
1. Scan changes
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

### 16.4 AMD source

Exemple :

```text
local/uckk/amd/src/course_explorer.js
```

Ordre :

```text
1. Scan changes
2. Build AMD from source component
3. Sync source to local runtime
4. Purge local caches
5. Run local smoke tests
6. Review Git diff/status
7. Commit
8. Push
9. Deploy server
10. Purge server caches
11. Run server smoke tests
```

### 16.5 DB / version

Exemple :

```text
local/uckk/db/services.php
local/uckk/version.php
```

Ordre :

```text
1. Scan changes
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

## 17. Tests manuels

### Test 1 — dépôt propre

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

### Test 4 — fichier DB modifié

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

### Test 5 — thème modifié

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

### Test 6 — outils Ops seulement

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

## 18. Critères d’acceptation

```text
- Le scan fonctionne sur un dépôt propre.
- Le scan détecte les fichiers modifiés et non suivis.
- Le scan classe correctement les composants Moodle.
- Le scan classe tools/uckk-ops sans déclencher d’action Moodle.
- Le scan classe docs sans déclencher d’action Moodle.
- Le scan détecte AMD build requis pour amd/src/*.js.
- Le scan détecte upgrade requis pour db/*.php, install.xml et version.php.
- Le scan détecte purge requise pour lang/templates/styles/theme.
- Le scan distingue actions locales et actions serveur.
- Le scan affiche un ordre recommandé incluant le serveur.
- Préparer local utilise un ordre cohérent avec source/runtime.
- Build AMD produit des fichiers amd/build commitables.
- Purge locale utilise moodleRoot/admin/cli/purge_caches.php.
- Publier OVH purge les caches serveur quand le plan le demande.
- Publier OVH lance les smoke tests serveur quand le plan le demande.
- Le scan n’exécute aucune action destructive ou distante.
- L’onglet Simple ne contient pas de full workflow automatique.
```

## 19. Hors périmètre

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
