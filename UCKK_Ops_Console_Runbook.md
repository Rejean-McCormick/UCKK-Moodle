# UCKK Ops Console — Runbook

## Objet

UCKK Ops Console pilote les opérations courantes entre le repo source local `uckk-moodle`, le runtime Moodle local, Git, le serveur `uckk.org` et les seeds Moodle.

```text
source locale → runtime local → Git → serveur source → serveur runtime → DB/cache Moodle
```

## Fichiers de l’app

```text
tools/uckk-ops/uckk_ops_gui.ps1
tools/uckk-ops/uckk-ops.config.json
tools/uckk-ops/lib/UckkOps.Common.psm1
tools/uckk-ops/lib/UckkOps.Local.psm1
tools/uckk-ops/lib/UckkOps.Git.psm1
tools/uckk-ops/lib/UckkOps.Server.psm1
tools/uckk-ops/lib/UckkOps.Seed.psm1
tools/uckk-ops/lib/UckkOps.Smoke.psm1
tools/uckk-ops/UCKK_Ops_Console_RUN.bat
docs/UCKK_Ops_Console_Runbook.md
```

## Source de configuration

Toutes les variables viennent de :

```text
tools/uckk-ops/uckk-ops.config.json
```

Aucun chemin ne doit être défini directement dans les modules `.psm1`.

## Chemins locaux

```text
Source Git locale:
C:\mycode\UCKK\uckk-moodle

Runtime Moodle local:
C:\mycode\UCKK\moodle\moodle\public

URL locale:
http://localhost:8000
```

## Chemins serveur

```text
SSH:
ubuntu@57.129.115.159

Source serveur:
/opt/uckk/uckk-moodle

Runtime Moodle serveur:
/var/www/moodle/public

Moodle root:
/var/www/moodle

Moodle data:
/var/moodledata

URL publique:
https://uckk.org
```

## Workflow normal

### 1. Développement local

```text
Check paths
Sync source → Moodle local
Purge caches local
Start Moodle local
```

Tester ensuite dans le navigateur.

### 2. Git

```text
Git status
Git diff
Commit + push
```

Ne pas pousser si le diff contient des secrets, `config.php`, dumps SQL, clés privées ou fichiers personnels.

### 3. Déploiement serveur

```text
Test SSH
Pull server repo
Sync server source → runtime
Moodle upgrade
Purge server caches
Reload php-fpm
Smoke test
```

Ne jamais faire de sync serveur sans vérifier le dernier commit.

### 4. Seeds Moodle

Pour `academic_registry_json/categories.json` :

```text
Dry-run categories
Apply categories
Purge caches
Smoke test course index
```

Modifier le JSON ne suffit pas : l’affichage Moodle des catégories vient de la DB Moodle.

## Actions sensibles

Les actions suivantes doivent demander confirmation :

```text
Commit + push
Sync server source → runtime
Moodle upgrade
Apply categories
Apply courses
Apply programs
Apply pathways
```

Message recommandé :

```text
Cette action modifie uckk.org ou sa base Moodle. Continuer ?
```

## Smoke tests recommandés

Local :

```text
http://localhost:8000
http://localhost:8000/course/index.php
http://localhost:8000/local/uckk/programs.php
```

Serveur :

```text
https://uckk.org
https://uckk.org/course/index.php
https://uckk.org/local/uckk/programs.php
```

## Règle de sécurité

Ne jamais stocker dans le repo :

```text
mots de passe
clés SSH privées
tokens
config.php serveur
dumps SQL
secrets Moodle
secrets DB
```

## Rollback minimal

```text
1. Revenir au commit précédent dans /opt/uckk/uckk-moodle
2. Resynchroniser source serveur → runtime
3. Relancer upgrade Moodle si nécessaire
4. Purger les caches
5. Tester uckk.org
```

Commande indicative :

```bash
cd /opt/uckk/uckk-moodle
git log --oneline -5
git checkout <commit_precedent>
```
