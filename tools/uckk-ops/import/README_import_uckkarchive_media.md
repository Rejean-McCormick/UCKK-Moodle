# Import UCKK Archive Media Inventory — paquet v5

Ce paquet corrige le lanceur Windows pour ne plus dépendre d'un chemin Moodle codé en dur.

Structure cible côté repo source :

```text
tools/uckk-ops/import/Import-UckkArchiveMedia.ps1
tools/uckk-ops/import/import_uckkarchive_media.php
tools/uckk-ops/import/Run-UckkArchiveImport.bat
```

Le repo source local canonique reste :

```text
C:\mycode\UCKK\uckk-moodle
```

L'inventaire par défaut reste :

```text
C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json
```

## Changement v5

`MOODLE_ROOT` est maintenant vide par défaut dans le `.bat` :

```bat
set "MOODLE_ROOT="
```

Le lanceur tente ensuite de trouver automatiquement le dossier qui contient `config.php`.

Il peut aussi recevoir le chemin Moodle en troisième argument :

```bat
Run-UckkArchiveImport.bat dryrun 42 "C:\chemin\vers\moodle-root"
Run-UckkArchiveImport.bat apply 42 "C:\chemin\vers\moodle-root"
```

Tu peux également définir une variable d'environnement :

```bat
set UCKK_MOODLE_ROOT=C:\chemin\vers\moodle-root
Run-UckkArchiveImport.bat dryrun 42
```

## Si config.php n'est pas trouvé

Lance :

```bat
dir /s /b "C:\mycode\UCKK\config.php"
```

Puis prends le dossier parent du `config.php` trouvé et passe-le au `.bat` comme troisième argument.

## Dry-run

```bat
Run-UckkArchiveImport.bat
```

ou :

```bat
Run-UckkArchiveImport.bat dryrun 42
```

## Apply

```bat
Run-UckkArchiveImport.bat apply 42
```

## Principe

`Run-UckkArchiveImport.bat` appelle `Import-UckkArchiveMedia.ps1`.

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

Les originaux ne sont pas copiés dans `public/`. Ils sont stockés via Moodle File API, typiquement :

```text
component = mod_uckkarchive
filearea  = media_original
itemid    = uckkarchive_media_version.id
filepath  = /
filename  = proposed_filename
```

Formats supportés par défaut :

```text
.docx
.pdf
```


## Notes v6

Le lanceur `Run-UckkArchiveImport.bat` corrige deux problèmes de la v5 :

```text
- il ne passe plus -ImporterPath au wrapper PowerShell;
- il n'accepte plus les config.php de plugins/themes pendant l'auto-détection.
```

Un vrai `config.php` Moodle est détecté seulement s'il contient :

```php
$CFG->dbtype
```

Si l'auto-détection échoue, passer explicitement le dossier qui contient le vrai `config.php` Moodle :

```bat
Run-UckkArchiveImport.bat dryrun 42 "C:\chemin\vers\moodle-root"
```


## v8 — correction importer repo-side

La v8 corrige le cas où le prévol cherchait `import_uckkarchive_media.php` sous `MOODLE_ROOT/tools/...`.
L'importer PHP est maintenant explicitement repo-side et doit rester à côté du wrapper PowerShell :

```text
C:\mycode\UCKK\uckk-moodle\tools\uckk-ops\import\Run-UckkArchiveImport.bat
C:\mycode\UCKK\uckk-moodle\tools\uckk-ops\import\Import-UckkArchiveMedia.ps1
C:\mycode\UCKK\uckk-moodle\tools\uckk-ops\import\import_uckkarchive_media.php
```

Le Moodle root demeure :

```text
C:\mycode\UCKK\moodle\moodle
```

Le `.bat` refuse maintenant d'appeler un vieux `Import-UckkArchiveMedia.ps1` sans le marqueur `UCKK_IMPORT_WRAPPER_VERSION=8`.
