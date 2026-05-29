# UCKK Moodle — source unique, runtime Moodle et synchronisation

## Objectif

Le projet Moodle complet n’est pas le repo GitHub UCKK.

Moodle reste un **runtime séparé**, tandis que le repo `uckk-moodle` contient les composants UCKK à versionner, modifier, committer et pousser.

La règle finale est :

```text
uckk-moodle = source Git unique
moodle\moodle\public = runtime Moodle copié / généré / lancé
```

Les composants UCKK ne doivent pas être moitié dans le runtime, moitié dans le repo, ni dépendre d’un mélange fragile de junctions.

---

## Dossiers principaux

### Runtime Moodle

```text
C:\mycode\UCKK\moodle\moodle\public
```

Ce dossier contient l’installation Moodle complète :

```text
- Moodle core
- vendor
- admin
- course
- mod
- theme
- local
- config.php
- scripts CLI Moodle
```

Il sert à :

```text
- démarrer Moodle localement
- tester dans le navigateur
- purger les caches Moodle
- exécuter les commandes CLI Moodle
- contenir la copie active des plugins UCKK au moment du test
```

Exemple :

```powershell
cd "C:\mycode\UCKK\moodle\moodle\public"
php -S localhost:8000 -t .
```

---

### Repo source UCKK

```text
C:\mycode\UCKK\uckk-moodle
```

Ce dossier est le repo GitHub UCKK.

Il sert à :

```text
- modifier le code UCKK
- suivre les changements avec git
- commit / push vers GitHub
- servir de source unique pour les composants UCKK
```

Exemple :

```powershell
cd "C:\mycode\UCKK\uckk-moodle"
git status
```

---

## Principe final

Toujours modifier le code UCKK dans :

```text
C:\mycode\UCKK\uckk-moodle
```

Toujours lancer/tester Moodle depuis :

```text
C:\mycode\UCKK\moodle\moodle\public
```

Le runtime Moodle reçoit une copie fraîche des composants UCKK via un script de synchronisation lancé avant le démarrage de Moodle.

---

## Pourquoi ne plus utiliser les junctions

Les junctions Windows peuvent sembler pratiques, mais ils changent la manière dont PHP résout certains chemins avec `__DIR__` et `realpath()`.

Exemple problématique :

```php
require_once(__DIR__ . '/../../config.php');
```

Ce code est normal dans un plugin Moodle quand le fichier est physiquement sous :

```text
C:\mycode\UCKK\moodle\moodle\public\local\uckk
```

Il pointe alors vers :

```text
C:\mycode\UCKK\moodle\moodle\public\config.php
```

Mais si `public\local\uckk` est un junction vers :

```text
C:\mycode\UCKK\uckk-moodle\local\uckk
```

PHP peut résoudre `__DIR__` vers le repo source, puis chercher :

```text
C:\mycode\UCKK\uckk-moodle\config.php
```

Ce fichier n’existe pas. Résultat : le runtime casse.

Conclusion :

```text
Les plugins Moodle exécutables doivent être physiquement copiés dans le runtime Moodle.
Le repo reste la source unique, mais le runtime exécute des copies.
```

---

## Composants UCKK à synchroniser

Les composants UCKK à copier depuis le repo vers Moodle runtime sont :

```text
local\uckk
mod\uckkarchive
mod\uckkchallenge
mod\uckkassembly
theme\uckk
admin\tool\uckkseed
admin\tool\uckkintegrity
course\format\uckk
ai\provider\uckk
```

Selon l’évolution du projet, ajouter ici les autres composants UCKK :

```text
blocks\...
availability\...
```

---

## Workflow quotidien

### 1. Modifier le code

Toujours ouvrir et modifier :

```text
C:\mycode\UCKK\uckk-moodle
```

Exemples :

```text
Core UCKK  : C:\mycode\UCKK\uckk-moodle\local\uckk
Challenge  : C:\mycode\UCKK\uckk-moodle\mod\uckkchallenge
Assembly   : C:\mycode\UCKK\uckk-moodle\mod\uckkassembly
Archive    : C:\mycode\UCKK\uckk-moodle\mod\uckkarchive
Format     : C:\mycode\UCKK\uckk-moodle\course\format\uckk
Seed tool  : C:\mycode\UCKK\uckk-moodle\admin\tool\uckkseed
Integrity  : C:\mycode\UCKK\uckk-moodle\admin\tool\uckkintegrity
Theme      : C:\mycode\UCKK\uckk-moodle\theme\uckk
AI provider: C:\mycode\UCKK\uckk-moodle\ai\provider\uckk
```

---

### 2. Lancer la petite app Moodle

La petite app de lancement doit faire automatiquement :

```text
1. vérifier que les chemins existent
2. vérifier qu’aucune cible UCKK dans public n’est un junction
3. créer un backup des composants UCKK actuellement actifs dans Moodle runtime
4. copier les composants UCKK depuis uckk-moodle vers moodle\moodle\public
5. purger les caches Moodle
6. lancer Moodle
```

Ordre logique :

```text
uckk-moodle
→ sync vers moodle\moodle\public
→ purge caches
→ php -S localhost:8000 -t .
```

---

### 3. Tester dans Moodle

Moodle est lancé depuis :

```text
C:\mycode\UCKK\moodle\moodle\public
```

Puis ouvrir :

```text
http://localhost:8000
```

ou :

```text
http://127.0.0.1:8000
```

---

### 4. Valider PHP

Exemples :

```powershell
cd "C:\mycode\UCKK\moodle\moodle\public"

php -l .\local\uckk\programs.php
php -l .\mod\uckkchallenge\view.php
php -l .\mod\uckkassembly\classes\output\assembly_view.php
php -l .\mod\uckkarchive\classes\output\archive_view.php
```

Ces fichiers sont testés dans le runtime, mais leur source officielle reste dans :

```text
C:\mycode\UCKK\uckk-moodle
```

---

### 5. Commit / push

Toujours depuis le repo source :

```powershell
cd "C:\mycode\UCKK\uckk-moodle"

git status
git add -A
git commit -m "Message du changement"
git push
```

---

## Script de synchronisation recommandé

Créer :

```text
C:\mycode\UCKK\uckk-moodle\tools\sync-to-local-moodle.ps1
```

Contenu recommandé :

```powershell
$ErrorActionPreference = "Stop"

$REPO = "C:\mycode\UCKK\uckk-moodle"
$MOODLE = "C:\mycode\UCKK\moodle\moodle\public"
$BACKUPROOT = "C:\mycode\UCKK\moodle-runtime-backups"

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backup = Join-Path $BACKUPROOT "uckk_custom_$timestamp"

$items = @(
    @{ Source = "local\uckk"; Target = "local\uckk" },
    @{ Source = "mod\uckkarchive"; Target = "mod\uckkarchive" },
    @{ Source = "mod\uckkchallenge"; Target = "mod\uckkchallenge" },
    @{ Source = "mod\uckkassembly"; Target = "mod\uckkassembly" },
    @{ Source = "theme\uckk"; Target = "theme\uckk" },
    @{ Source = "admin\tool\uckkseed"; Target = "admin\tool\uckkseed" },
    @{ Source = "admin\tool\uckkintegrity"; Target = "admin\tool\uckkintegrity" },
    @{ Source = "course\format\uckk"; Target = "course\format\uckk" },
    @{ Source = "ai\provider\uckk"; Target = "ai\provider\uckk" }
)

Write-Host "UCKK sync starting..." -ForegroundColor Cyan
Write-Host "Source:  $REPO"
Write-Host "Runtime: $MOODLE"
Write-Host "Backup:  $backup"

if (-not (Test-Path $REPO)) {
    throw "Repo not found: $REPO"
}

if (-not (Test-Path $MOODLE)) {
    throw "Moodle runtime not found: $MOODLE"
}

if (-not (Test-Path (Join-Path $MOODLE "config.php"))) {
    throw "Moodle config.php not found in runtime: $MOODLE"
}

New-Item -ItemType Directory -Force -Path $backup | Out-Null

foreach ($item in $items) {
    $src = Join-Path $REPO $item.Source
    $dst = Join-Path $MOODLE $item.Target
    $bak = Join-Path $backup $item.Target

    if (-not (Test-Path $src)) {
        Write-Host "SKIP missing source: $src" -ForegroundColor Yellow
        continue
    }

    if (Test-Path $dst) {
        $existing = Get-Item $dst

        if ($existing.LinkType) {
            throw "Target is a junction/symlink and must be removed before sync: $dst"
        }

        New-Item -ItemType Directory -Force -Path (Split-Path $bak) | Out-Null

        robocopy $dst $bak /MIR /XD ".git" "node_modules" ".scannerwork" /XF "*.tmp" "*.log" | Out-Host

        if ($LASTEXITCODE -gt 7) {
            throw "Backup robocopy failed for $($item.Target), exit code $LASTEXITCODE"
        }
    }

    New-Item -ItemType Directory -Force -Path (Split-Path $dst) | Out-Null

    Write-Host "Sync $($item.Source) -> $($item.Target)" -ForegroundColor Green

    robocopy $src $dst /MIR `
        /XD ".git" "node_modules" ".scannerwork" `
        /XF "*.tmp" "*.log" `
        | Out-Host

    if ($LASTEXITCODE -gt 7) {
        throw "Sync robocopy failed for $($item.Source), exit code $LASTEXITCODE"
    }
}

Write-Host "Purging Moodle caches..." -ForegroundColor Cyan

Push-Location $MOODLE
php -r "define('CLI_SCRIPT', true); require 'config.php'; purge_all_caches(); echo 'Caches purged' . PHP_EOL;"
Pop-Location

Write-Host "UCKK sync complete." -ForegroundColor Green
```

---

## Intégration dans la petite app de lancement

Avant de lancer Moodle, l’app doit exécuter :

```powershell
pwsh -ExecutionPolicy Bypass -File "C:\mycode\UCKK\uckk-moodle\tools\sync-to-local-moodle.ps1"
```

Puis lancer Moodle :

```powershell
cd "C:\mycode\UCKK\moodle\moodle\public"
php -S localhost:8000 -t .
```

La petite app peut afficher :

```text
1. Source repo détectée
2. Runtime Moodle détecté
3. Backup créé
4. Sync terminée
5. Caches purgés
6. Moodle lancé
```

---

## Backups runtime

Les backups de composants UCKK actifs sont créés ici :

```text
C:\mycode\UCKK\moodle-runtime-backups
```

Nom typique :

```text
uckk_custom_YYYYMMDD_HHMMSS
```

Ces backups servent à revenir rapidement à l’état précédent du runtime si une synchronisation introduit un problème.

Ils ne sont pas la source officielle. La source officielle reste :

```text
C:\mycode\UCKK\uckk-moodle
```

---

## Vérifier qu’il ne reste pas de junctions UCKK

Commande de vérification :

```powershell
$RUNTIME = "C:\mycode\UCKK\moodle\moodle\public"

$paths = @(
    "$RUNTIME\local\uckk",
    "$RUNTIME\mod\uckkchallenge",
    "$RUNTIME\mod\uckkassembly",
    "$RUNTIME\mod\uckkarchive",
    "$RUNTIME\course\format\uckk",
    "$RUNTIME\admin\tool\uckkseed",
    "$RUNTIME\admin\tool\uckkintegrity",
    "$RUNTIME\theme\uckk",
    "$RUNTIME\ai\provider\uckk"
)

foreach ($path in $paths) {
    if (Test-Path $path) {
        Get-Item $path | Select-Object FullName, LinkType, Target
    }
}
```

Résultat attendu :

```text
LinkType =
Target   =
```

Aucune cible UCKK active dans `public` ne doit être un junction.

---

## À ne plus faire

Ne plus créer de junctions UCKK dans :

```text
C:\mycode\UCKK\moodle\moodle\public
```

Ne plus modifier directement les fichiers UCKK dans :

```text
C:\mycode\UCKK\moodle\moodle\public
```

Ne plus utiliser un workflow où certains composants sont en junction et d’autres en copie physique.

Ne plus utiliser une app de sync qui remplace arbitrairement des dossiers sans backup.

Ne pas créer de `config.php` shim dans :

```text
C:\mycode\UCKK\uckk-moodle
```

---

## Déploiement serveur

Sur un serveur, ne pas déployer les composants UCKK comme symlinks vers un repo externe.

Déployer les plugins comme dossiers physiques dans l’installation Moodle :

```text
/path/to/moodle/local/uckk
/path/to/moodle/mod/uckkarchive
/path/to/moodle/mod/uckkchallenge
/path/to/moodle/mod/uckkassembly
/path/to/moodle/theme/uckk
/path/to/moodle/admin/tool/uckkseed
/path/to/moodle/admin/tool/uckkintegrity
/path/to/moodle/course/format/uckk
/path/to/moodle/ai/provider/uckk
```

Ensuite exécuter les commandes Moodle habituelles :

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

---

## Résumé court

```text
Modifier ici : C:\mycode\UCKK\uckk-moodle
Tester ici   : C:\mycode\UCKK\moodle\moodle\public
Git ici      : C:\mycode\UCKK\uckk-moodle
Backup ici   : C:\mycode\UCKK\moodle-runtime-backups
```

Le runtime Moodle reçoit les composants UCKK par synchronisation contrôlée avant lancement.

```text
uckk-moodle
→ sync-to-local-moodle.ps1
→ moodle\moodle\public
→ purge caches
→ launch Moodle
```

Source unique : `uckk-moodle`.

Runtime : `moodle\moodle\public`.

Aucune junction UCKK active dans le runtime.
