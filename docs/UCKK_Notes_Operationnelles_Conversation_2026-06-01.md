# UCKK — Notes opérationnelles apprises pendant l’intervention

_Date : 2026-06-01_
_Portée : éléments compris pendant le dépannage, mais pas assez explicites dans les docs actuelles._

---

## 1. Modèle mental simplifié

Il faut toujours distinguer trois choses :

```text
Repo source = les fichiers officiels versionnés Git
Runtime Moodle = le Moodle qui roule vraiment
DB Moodle = ce que Moodle affiche vraiment pour certaines données
```

Exemple :

```text
academic_registry_json/categories.json
```

n’est pas affiché directement par Moodle. Ce fichier est une source. Il faut ensuite appliquer le seed pour écrire les catégories dans la DB Moodle.

Image simple :

```text
categories.json = recette
seed categories = appliquer la recette
DB Moodle = ce que Moodle affiche
```

---

## 2. `academic_registry_json` n’est pas un composant runtime

Ne pas traiter :

```text
academic_registry_json
```

comme :

```text
local/uckk
mod/uckkarchive
theme/uckk
admin/tool/uckkseed
```

Les plugins doivent être synchronisés dans le runtime Moodle. Le registre académique JSON, lui, doit rester dans le repo source et être appliqué par le seed.

Flux correct :

```text
modifier academic_registry_json/categories.json
→ git commit + push
→ git pull serveur
→ seed categories
→ purge caches
→ vérifier dans Moodle
```

---

## 3. Le GUI ne doit pas cacher les prompts SSH/sudo

Pendant l’intervention, le GUI semblait gelé parce que SSH attendait un mot de passe ou une confirmation en arrière-plan.

Règle : avant d’utiliser les boutons serveur du GUI, vérifier SSH dans PowerShell :

```powershell
ssh -i "$env:USERPROFILE\.ssh\id_ed25519" -o IdentitiesOnly=yes -o BatchMode=yes ubuntu@57.129.115.159 "echo OK"
```

Résultat obligatoire :

```text
OK
```

Si ce test ne répond pas `OK`, ne pas cliquer :

```text
Pull serveur
Dry-run categories serveur
Apply categories serveur
Purger caches serveur
```

---

## 4. Ajouter la clé SSH depuis PowerShell

La clé privée locale existait et n’avait pas de passphrase, mais le serveur ne l’acceptait pas encore.

Commande qui a corrigé l’accès SSH :

```powershell
Get-Content "$env:USERPROFILE\.ssh\id_ed25519.pub" | ssh ubuntu@57.129.115.159 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys"
```

Après ça, le test SSH a répondu :

```text
OK
```

Conclusion : le problème n’était pas la clé locale; le serveur ne connaissait pas encore sa clé publique.

---

## 5. Le seed UCKK ne s’applique pas avec `--force` seul

Observation importante : cette commande :

```powershell
ssh -tt ubuntu@57.129.115.159 "cd /var/www/moodle/public && sudo -u www-data php admin/tool/uckkseed/cli/seed.php --presetpath=/opt/uckk/uckk-moodle/academic_registry_json --preset=categories --force"
```

a retourné :

```text
Mode: dry_run
No distribution records will be written in this mode.
```

Donc :

```text
--force seul ne suffit pas
```

Le mode réel vient de la configuration Moodle :

```text
tool_uckkseed/defaultmode
```

---

## 6. `--mode=apply` n’est pas accepté par le seed CLI actuel

Cette commande a échoué :

```text
--mode=apply
```

Erreur :

```text
Unknown option(s): --mode=apply
```

Donc le bon chemin est :

```text
1. changer temporairement defaultmode à apply
2. lancer seed.php avec --force
3. remettre defaultmode à dry_run
4. purger les caches
```

---

## 7. Commande d’application qui a fonctionné

La partie importante qui a fonctionné :

```bash
cd /var/www/moodle/public

sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
set_config('defaultmode', 'apply', 'tool_uckkseed');
echo "defaultmode=apply\n";
PHP

sudo -u www-data php /var/www/moodle/public/admin/tool/uckkseed/cli/seed.php --presetpath=/opt/uckk/uckk-moodle/academic_registry_json --preset=categories --force
```

Résultat obtenu :

```text
Mode: apply
Status: completed
created: 0
updated: 12
skipped: 7
failed: 0
warnings: 0
errors: 0
```

Conclusion : les catégories ont été appliquées correctement.

---

## 8. Toujours remettre le seed en mode safe

Après un apply, remettre :

```text
defaultmode=dry_run
```

Commande utilisée :

```bash
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
set_config('defaultmode', 'dry_run', 'tool_uckkseed');
echo "defaultmode=dry_run\n";
PHP
```

Résultat obtenu :

```text
defaultmode=dry_run
```

---

## 9. Chemin réel de purge trouvé pendant l’intervention

La doc indiquait parfois :

```text
/var/www/moodle/public/admin/cli/purge_caches.php
```

Mais sur le serveur, ce chemin a échoué :

```text
Could not open input file: /var/www/moodle/public/admin/cli/purge_caches.php
```

Le chemin réel trouvé automatiquement était :

```text
/var/www/moodle/admin/cli/purge_caches.php
```

Commande robuste à conserver :

```bash
PURGE=""
for f in \
  /var/www/moodle/public/admin/cli/purge_caches.php \
  /var/www/moodle/admin/cli/purge_caches.php
do
  if [ -f "$f" ]; then
    PURGE="$f"
    break
  fi
done

sudo -u www-data php "$PURGE"
```

---

## 10. Quand une fenêtre SSH semble gelée

Si une fenêtre PowerShell/SSH semble gelée après un heredoc ou une commande distante :

```text
ne pas paniquer
ouvrir une nouvelle fenêtre PowerShell 7
vérifier l’état avec une commande courte
```

Exemple :

```powershell
ssh ubuntu@57.129.115.159 "echo OK"
```

Il est possible que la première session soit restée dans un shell distant interactif sans afficher clairement la fin.

---

## 11. Style opératoire recommandé pour l’utilisateur

Ne pas donner 8 petits blocs séparés quand un seul bloc complet peut être collé.

Mais arrêter aux jonctions importantes :

```text
1. test SSH
2. git pull
3. dry-run
4. apply
5. cleanup/cache
6. smoke test
```

Ne pas donner de branchement compliqué avant la jonction. Par exemple, donner un bloc, attendre le résultat, puis seulement ensuite décider.

Forme préférée :

```text
Colle ce bloc.
Arrête là.
Colle-moi le résultat.
```

---

## 12. Ce que le GUI devrait apprendre de cette intervention

Le GUI devrait empêcher les actions serveur si SSH n’est pas prêt.

Avant tout bouton serveur, il devrait tester :

```powershell
ssh -o BatchMode=yes ubuntu@57.129.115.159 "echo OK"
```

Si échec : afficher :

```text
SSH non prêt. Corriger l’accès SSH avant d’utiliser les actions serveur.
```

Le GUI ne devrait pas lancer de commande qui peut demander un mot de passe invisible.

---

## 13. Encodage du fichier GUI PowerShell

Le GUI a démarré, mais les accents étaient cassés :

```text
VÃ©rifier chemins
Sync source â†’ Moodle local
```

Cause probable : fichier `.ps1` lu comme ANSI par Windows PowerShell.

Correction : sauvegarder le fichier en UTF-8 avec BOM, ou utiliser PowerShell 7.

Commande de correction :

```powershell
$path = "C:\mycode\UCKK\uckk-moodle\tools\uckk-ops\uckk_ops_gui.ps1"
$bytes = [System.IO.File]::ReadAllBytes($path)
$text = [System.Text.Encoding]::UTF8.GetString($bytes)
$utf8bom = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText($path, $text, $utf8bom)
```

---

## 14. État final de l’intervention categories

Ce qui a été confirmé :

```text
SSH fonctionnel : oui
Git pull serveur : oui
Dry-run categories : completed, errors 0
Apply categories : completed, updated 12, errors 0
Seed defaultmode remis à dry_run : oui
Purge caches : oui
```

À vérifier visuellement ensuite :

```text
https://uckk.org/course/index.php
```

But de la vérification : confirmer que les noms publics de catégories sont ceux du registre corrigé.
