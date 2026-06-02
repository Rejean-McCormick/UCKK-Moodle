# UCKK Ops Console — Runbook

## Objet

UCKK Ops Console pilote les opérations entre le repo source local, le runtime Moodle local, Git, le serveur `uckk.org` et les seeds Moodle.

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

Les modules lisent la configuration via `UckkOps.Common.psm1`.

## Workflow normal

1. Modifier la source locale dans `C:\mycode\UCKK\uckk-moodle`.
2. Onglet `Local Dev` : sync source → runtime local, purge caches, test local.
3. Onglet `Git` : status, diff, commit + push.
4. Onglet `uckk.org` : test SSH, pull serveur, sync source → runtime, upgrade, purge caches, reload PHP-FPM.
5. Onglet `Seed DB` : dry-run puis apply si un JSON seed doit modifier la DB Moodle.
6. Onglet `Smoke` : tester les URLs locales ou serveur.

## Actions sensibles

Les actions suivantes demandent confirmation dans le GUI :

```text
Commit + push
Pull serveur
Sync serveur source → runtime
Moodle upgrade serveur
Purge caches serveur
Reload PHP-FPM
Apply categories local
Apply categories serveur
```

## Notes

- Le runtime local n’est pas la source.
- Le runtime serveur n’est pas la source.
- La source officielle est le repo `uckk-moodle`.
- Les JSON de seed doivent être appliqués à la DB Moodle avant d’apparaître dans certaines interfaces.
