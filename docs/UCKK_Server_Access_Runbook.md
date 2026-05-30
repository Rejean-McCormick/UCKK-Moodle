# UCKK Moodle — Server Access and Deployment Runbook

_Last updated: 2026-05-30_

This document explains how to connect to the UCKK Moodle server, where the important files live, and how to safely update the application without losing the working production state.

## 1. Current production server

The current production/test VPS is:

```text
IP: 57.129.115.159
SSH user: ubuntu
SSH target: ubuntu@57.129.115.159
Moodle URL: http://57.129.115.159
```

In PowerShell, define the target as:

```powershell
$SshTarget = "ubuntu@57.129.115.159"
```

Then connect with:

```powershell
ssh $SshTarget
```

Equivalent direct command:

```powershell
ssh ubuntu@57.129.115.159
```

Do not store server passwords, Moodle admin passwords, database passwords, or private SSH keys inside the repository.

## 2. Main server paths

```text
Moodle application root:       /var/www/moodle
Moodle public web root:        /var/www/moodle/public
Moodle config.php:             /var/www/moodle/config.php
Moodle data directory:         /var/moodledata
UCKK source checkout/symlink:  /opt/uckk/uckk-moodle
UCKK releases directory:       /opt/uckk/releases
UCKK presets JSON:             /opt/uckk/uckk-moodle/academic_registry_json
```

Important plugin paths inside Moodle runtime:

```text
local_uckk runtime:            /var/www/moodle/public/local/uckk
theme_uckk runtime:            /var/www/moodle/public/theme/uckk
tool_uckkseed runtime:         /var/www/moodle/public/admin/tool/uckkseed
```

Important source paths:

```text
local_uckk source:             /opt/uckk/uckk-moodle/local/uckk
theme_uckk source:             /opt/uckk/uckk-moodle/theme/uckk
tool_uckkseed source:          /opt/uckk/uckk-moodle/admin/tool/uckkseed
```

## 3. Git repository and branch

GitHub repository:

```text
git@github.com:Rejean-McCormick/UCKK-Moodle.git
```

Current working branch:

```text
stabilize-runtime-20260522
```

Recent important commit:

```text
e9d158f Make public courses page list visible Moodle courses
```

Local Windows repository:

```text
C:\mycode\UCKK\uckk-moodle
```

Check local branch and status:

```powershell
$repo = "C:\mycode\UCKK\uckk-moodle"
git -C $repo status -sb
git -C $repo log -3 --oneline --decorate
```

## 4. SSH targets in scripts

When a script asks for a target such as:

```powershell
$SshTarget = "tonuser@uckk.org"
```

use the actual VPS target:

```powershell
$SshTarget = "ubuntu@57.129.115.159"
```

If a script asks for `$RemoteMoodleRoot`, usually leave it empty and let the script auto-detect Moodle:

```powershell
$RemoteMoodleRoot = ""
```

If auto-detection fails, use:

```powershell
$RemoteMoodleRoot = "/var/www/moodle/public"
```

## 5. Copying files between local and server

Preferred workflow: commit locally, push to GitHub, then deploy from Git on the server.

Emergency/manual copy from server to local:

```powershell
$server = "ubuntu@57.129.115.159"
$repo = "C:\mycode\UCKK\uckk-moodle"
scp "${server}:/opt/uckk/uckk-moodle/local/uckk/courses.php" "$repo\local\uckk\courses.php"
```

Manual copy from local to server should be avoided unless doing a controlled hotfix. If used, copy both source and runtime paths, then purge Moodle caches.

## 6. Production source versus runtime

The UCKK source tree and the Moodle runtime are separate.

Source:

```text
/opt/uckk/uckk-moodle
```

Runtime served by Nginx/Moodle:

```text
/var/www/moodle/public
```

A file can exist in the source tree but not be active unless it has been synced into the Moodle runtime. For UCKK plugins, the important runtime folders are:

```text
/var/www/moodle/public/local/uckk
/var/www/moodle/public/theme/uckk
/var/www/moodle/public/admin/tool/uckkseed
/var/www/moodle/public/course/format/uckk
/var/www/moodle/public/mod/uckkarchive
/var/www/moodle/public/mod/uckkchallenge
/var/www/moodle/public/mod/uckkassembly
```

## 7. Public pages now working

The public UCKK layout is active for pages such as:

```text
/local/uckk/index.php
/local/uckk/about.php
/local/uckk/programs.php
/local/uckk/courses.php
```

The courses page was updated so that it reads visible Moodle courses from the database and creates public cards with links like:

```text
/course/view.php?id=...
```

The production test confirmed:

```text
course_links_count=114
```

## 8. Moodle data seed

The seed JSON files are stored here:

```text
/opt/uckk/uckk-moodle/academic_registry_json
```

The seed tool is here:

```text
/var/www/moodle/public/admin/tool/uckkseed/cli/seed.php
```

Important note: `seed.php` does not accept `--mode=apply`. The plugin’s `defaultmode` setting was temporarily changed to `apply` during the successful seed, then restored to `dry_run`.

Current expected production data state:

```text
UCKK categories:       19
Moodle courses:        114
UCKK programs:         12
UCKK pathways:         12
tool_uckkseed mode:    dry_run
```

## 9. Public access settings

For public course visibility, the server was configured with:

```text
forcelogin = 0
autologinguests = 1
guestloginbutton = 1
guest enrolment enabled on public courses
categories visible
courses visible
```

This means anonymous visitors can browse the course list and open public course pages in consultation mode.

## 10. Useful diagnostic commands

Check services:

```powershell
ssh ubuntu@57.129.115.159 "systemctl is-active nginx; systemctl is-active php8.3-fpm; systemctl is-active mariadb"
```

Check Moodle URL from server:

```powershell
ssh ubuntu@57.129.115.159 "curl -sS -I -H 'Host: 57.129.115.159' http://127.0.0.1/local/uckk/courses.php | head"
```

Check Git branch on server:

```powershell
ssh ubuntu@57.129.115.159 "git -C /opt/uckk/uckk-moodle branch --show-current && git -C /opt/uckk/uckk-moodle log -1 --oneline --decorate"
```

Check Moodle DB counts:

```powershell
$server = "ubuntu@57.129.115.159"
$script = @'
cd /var/www/moodle/public
sudo -u www-data php <<'PHP'
<?php
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
global $DB;
echo "categories=" . $DB->count_records('course_categories') . PHP_EOL;
echo "course_non_site=" . $DB->count_records_select('course', 'id <> 1') . PHP_EOL;
echo "local_uckk_program=" . $DB->count_records('local_uckk_program') . PHP_EOL;
echo "local_uckk_pathway=" . $DB->count_records('local_uckk_pathway') . PHP_EOL;
PHP
'@
$script | ssh $server "bash -s"
```

## 11. Purging Moodle caches

After PHP, Mustache, theme, or public page changes, purge Moodle caches:

```powershell
ssh ubuntu@57.129.115.159 "sudo -u www-data php /var/www/moodle/public/admin/cli/purge_caches.php && sudo systemctl reload php8.3-fpm"
```

## 12. Files that should not be committed

Do not commit local backup files or temporary patch scripts unless intentionally promoted to tooling.

Examples to avoid committing:

```text
*.bak.*
local/uckk/courses.php.bak.before-vps-livecourses-*
tools/uckk_nomenclature_patch.py   # unless intentionally reviewed and accepted
```

Check before commit:

```powershell
$repo = "C:\mycode\UCKK\uckk-moodle"
git -C $repo status --short
git -C $repo diff --cached --stat
```

## 13. Recommended safe update flow

1. Work locally on `stabilize-runtime-20260522`.
2. Commit only reviewed files.
3. Push to GitHub.
4. Pull or deploy the branch on the server.
5. Sync source to Moodle runtime if needed.
6. Purge Moodle caches.
7. Test public pages:

```text
http://57.129.115.159/local/uckk/programs.php
http://57.129.115.159/local/uckk/courses.php
http://57.129.115.159/course/index.php
```

8. Test one course link:

```text
http://57.129.115.159/course/view.php?id=<courseid>
```

## 14. Security notes

- Never commit passwords.
- Never commit private SSH keys.
- Never paste database passwords into docs or chat logs.
- Prefer SSH keys over password login.
- If a password has been pasted publicly or into logs, rotate it.
- Keep database backups outside the Git repository.
