# Import UCKK Archive Media Inventory — paquet v2

Ce paquet remplace la première proposition qui ajoutait un fichier sous `mod/uckkarchive/cli/`.

La nouvelle structure garde l’import comme outil d’opération :

```text
tools/uckk-ops/import/Import-UckkArchiveMedia.ps1
tools/uckk-ops/import/import_uckkarchive_media.php
```

Aucun fichier n’est ajouté dans `mod/uckkarchive/cli/`.

## Principe

`Import-UckkArchiveMedia.ps1` fait le prévol :

```text
- validation de uckk_inventory.json
- résolution des originaux depuis un dossier non public
- vérification des extensions supportées
- rapport JSON de prévol
- appel du bootstrapper PHP Moodle
```

`import_uckkarchive_media.php` charge Moodle avec `config.php`, puis écrit via :

```text
- $DB pour les tables uckkarchive_media, uckkarchive_media_version, source, tags, advisories, relations
- Moodle File API pour les originaux
```

Les originaux ne sont pas copiés dans `public/`. Ils sont stockés par Moodle File API, typiquement :

```text
component = mod_uckkarchive
filearea  = media_original
itemid    = uckkarchive_media_version.id
filepath  = /
filename  = proposed_filename
```

## Installation dans le runtime Moodle

Depuis la racine Moodle runtime, créer le dossier :

```powershell
New-Item -ItemType Directory -Force `
  -Path C:\mycode\UCKK\moodle\moodle\public\tools\uckk-ops\import
```

Copier les deux fichiers :

```powershell
Copy-Item .\tools\uckk-ops\import\Import-UckkArchiveMedia.ps1 `
  C:\mycode\UCKK\moodle\moodle\public\tools\uckk-ops\import\Import-UckkArchiveMedia.ps1 -Force

Copy-Item .\tools\uckk-ops\import\import_uckkarchive_media.php `
  C:\mycode\UCKK\moodle\moodle\public\tools\uckk-ops\import\import_uckkarchive_media.php -Force
```

## Dry-run

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File `
  C:\mycode\UCKK\moodle\moodle\public\tools\uckk-ops\import\Import-UckkArchiveMedia.ps1 `
  -MoodleRoot "C:\mycode\UCKK\moodle\moodle\public" `
  -InventoryPath "C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json" `
  -OriginalsDir "C:\mycode\UCKK\uckk-import\uckkarchive\originals" `
  -CmId 42 `
  -Mode DryRun
```

## Apply

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File `
  C:\mycode\UCKK\moodle\moodle\public\tools\uckk-ops\import\Import-UckkArchiveMedia.ps1 `
  -MoodleRoot "C:\mycode\UCKK\moodle\moodle\public" `
  -InventoryPath "C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json" `
  -OriginalsDir "C:\mycode\UCKK\uckk-import\uckkarchive\originals" `
  -CmId 42 `
  -Mode Apply
```

## Paramètres utiles

```text
-CmId               recommandé : résout le contexte module Moodle
-ArchiveId          alternative si l’instance est connue
-UserId             force createdby/modifiedby
-AllowMissingFiles  permet un import partiel de métadonnées pendant le prévol
-UpdateMetadata     met à jour les métadonnées si le média existe déjà
-ForceNewVersion    force une nouvelle version même si le SHA-256 existe déjà
-Offset / -Limit    import par lot
```

## Notes de compatibilité

Le PHP filtre dynamiquement les champs selon les colonnes réellement présentes dans la DB Moodle. Il supporte donc les noms historiques et les noms du schéma courant quand c’est possible, sans écrire de colonnes absentes.

Formats supportés par défaut :

```text
.docx
.pdf
```

Le `.doc` legacy n’est pas activé par défaut.
