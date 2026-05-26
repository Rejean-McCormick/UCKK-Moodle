# UCKK Moodle — dossiers, source unique et junctions

## Objectif

Le projet Moodle complet n’est pas le repo GitHub UCKK. Moodle reste un runtime séparé, et le repo `uckk-moodle` contient seulement les composants UCKK.

Depuis l’installation des junctions, Moodle exécute les composants UCKK directement depuis le repo `uckk-moodle`. Il n’y a donc plus de copie active séparée dans `public` pour ces composants.

---

## Dossiers principaux

### Runtime Moodle

```text
C:\mycode\UCKK\moodle\moodle\public
```

Ce dossier contient Moodle complet : core Moodle, vendor, admin, course, mod, theme, etc.

Il sert à :

```text
- démarrer Moodle localement
- tester dans le navigateur
- purger les caches Moodle
- exécuter les commandes CLI Moodle
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
```

Exemple :

```powershell
cd "C:\mycode\UCKK\uckk-moodle"
git status
```

---

## Junctions actives

Les dossiers UCKK dans Moodle `public` sont maintenant des junctions Windows vers le repo `uckk-moodle`.

```text
C:\mycode\UCKK\moodle\moodle\public\mod\uckkchallenge
→ C:\mycode\UCKK\uckk-moodle\mod\uckkchallenge

C:\mycode\UCKK\moodle\moodle\public\mod\uckkassembly
→ C:\mycode\UCKK\uckk-moodle\mod\uckkassembly

C:\mycode\UCKK\moodle\moodle\public\mod\uckkarchive
→ C:\mycode\UCKK\uckk-moodle\mod\uckkarchive

C:\mycode\UCKK\moodle\moodle\public\course\format\uckk
→ C:\mycode\UCKK\uckk-moodle\course\format\uckk

C:\mycode\UCKK\moodle\moodle\public\admin\tool\uckkseed
→ C:\mycode\UCKK\uckk-moodle\admin\tool\uckkseed

C:\mycode\UCKK\moodle\moodle\public\theme\uckk
→ C:\mycode\UCKK\uckk-moodle\theme\uckk
```

Conséquence : modifier un fichier dans `uckk-moodle` modifie automatiquement ce que Moodle voit dans `public`.

---

## Source unique active

Pour les composants listés ci-dessus, la source active est :

```text
C:\mycode\UCKK\uckk-moodle
```

Les anciens dossiers physiques dans Moodle `public` ont été déplacés dans un dossier de sauvegarde :

```text
C:\mycode\UCKK\runtime-uckk-backup-YYYYMMDD-HHMMSS
```

Ces backups ne sont pas actifs. Ils peuvent rester comme sécurité ou être supprimés plus tard après validation complète.

---

## Workflow quotidien

### 1. Modifier le code

Toujours ouvrir et modifier :

```text
C:\mycode\UCKK\uckk-moodle
```

Exemples :

```text
Challenge  : C:\mycode\UCKK\uckk-moodle\mod\uckkchallenge
Assembly   : C:\mycode\UCKK\uckk-moodle\mod\uckkassembly
Archive    : C:\mycode\UCKK\uckk-moodle\mod\uckkarchive
Format     : C:\mycode\UCKK\uckk-moodle\course\format\uckk
Seed tool  : C:\mycode\UCKK\uckk-moodle\admin\tool\uckkseed
Theme      : C:\mycode\UCKK\uckk-moodle\theme\uckk
```

---

### 2. Tester dans Moodle

Moodle est lancé depuis :

```text
C:\mycode\UCKK\moodle\moodle\public
```

Commandes utiles :

```powershell
cd "C:\mycode\UCKK\moodle\moodle\public"

php -r 'define("CLI_SCRIPT", true); require "config.php"; purge_all_caches(); echo "Caches purged" . PHP_EOL;'

php -S localhost:8000 -t .
```

Puis ouvrir :

```text
http://localhost:8000
```

---

### 3. Valider PHP

Exemples :

```powershell
cd "C:\mycode\UCKK\moodle\moodle\public"

php -l .\mod\uckkchallenge\view.php
php -l .\mod\uckkassembly\classes\output\assembly_view.php
php -l .\mod\uckkarchive\classes\output\archive_view.php
```

Même si ces chemins sont sous `public`, ils pointent vers le repo `uckk-moodle` grâce aux junctions.

---

### 4. Commit / push

Toujours depuis le repo source :

```powershell
cd "C:\mycode\UCKK\uckk-moodle"

git status
git add -A
git commit -m "Message du changement"
git push
```

---

## À ne plus faire

Ne plus utiliser l’ancien workflow de synchronisation :

```text
uckk-moodle → copier manuellement vers → moodle/public
```

Ne plus utiliser l’ancienne app Python de sync pour `Replace selected` ou `Archive + replace selected`, car elle peut remplacer les junctions par des dossiers physiques et recréer des duplicats actifs.

---

## Vérifier les junctions

Commande de vérification :

```powershell
$RUNTIME = "C:\mycode\UCKK\moodle\moodle\public"

Get-Item `
  "$RUNTIME\mod\uckkchallenge", `
  "$RUNTIME\mod\uckkassembly", `
  "$RUNTIME\mod\uckkarchive", `
  "$RUNTIME\course\format\uckk", `
  "$RUNTIME\admin\tool\uckkseed", `
  "$RUNTIME\theme\uckk" |
Select-Object FullName, LinkType, Target
```

Résultat attendu :

```text
LinkType = Junction
Target   = C:\mycode\UCKK\uckk-moodle\...
```

---

## Résumé court

```text
Modifier ici : C:\mycode\UCKK\uckk-moodle
Tester ici   : C:\mycode\UCKK\moodle\moodle\public
Git ici      : C:\mycode\UCKK\uckk-moodle
```

Les composants UCKK actifs dans Moodle pointent maintenant vers une seule source : le repo `uckk-moodle`.
