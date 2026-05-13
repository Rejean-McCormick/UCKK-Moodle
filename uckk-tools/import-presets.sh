#!/usr/bin/env bash
set -euo pipefail

MOODLE_DIR="${1:-${MOODLE_DIR:-}}"
IN_DIR="${2:-${UCKK_PRESETS_DIR:-./uckk-presets}}"
MODE="${UCKK_SEED_MODE:-dry_run}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

info() {
  printf '[uckk-import-presets] %s\n' "$*"
}

usage() {
  cat >&2 <<'USAGE'
Usage:
  ./uckk-tools/import-presets.sh /path/to/moodle [preset-dir]

Environment:
  MOODLE_DIR=/path/to/moodle
  UCKK_PRESETS_DIR=/path/to/uckk-presets
  UCKK_SEED_MODE=dry_run|apply|repair

Default:
  preset-dir: ./uckk-presets
  mode: dry_run
USAGE
}

case "$MODE" in
  dry_run|apply|repair)
    ;;
  *)
    fail "Invalid UCKK_SEED_MODE '$MODE'. Expected dry_run, apply, or repair."
    ;;
esac

if [ -z "$MOODLE_DIR" ]; then
  usage
  exit 2
fi

[ -d "$MOODLE_DIR" ] || fail "Moodle directory not found: $MOODLE_DIR"
[ -f "$MOODLE_DIR/config.php" ] || fail "Moodle config.php not found in: $MOODLE_DIR"
[ -d "$IN_DIR" ] || fail "Preset directory not found: $IN_DIR"

if [ -f "$SCRIPT_DIR/validate.sh" ]; then
  info "Validating presets from: $IN_DIR"
  UCKK_PRESETS_DIR="$IN_DIR" "$SCRIPT_DIR/validate.sh"
else
  info "validate.sh not found beside import-presets.sh; checking JSON syntax only."
  python3 - "$IN_DIR" <<'PY'
import json
import pathlib
import sys

root = pathlib.Path(sys.argv[1])
required = [
    "categories.json",
    "courses.json",
    "course_templates.json",
    "cohorts.json",
    "roles.json",
    "capabilities.json",
    "competencies.json",
    "badges.json",
    "reports.json",
    "challenge_templates.json",
    "assembly_templates.json",
    "archive_templates.json",
    "integrity_policies.json",
]

for name in required:
    path = root / name
    if not path.exists():
        raise SystemExit(f"Missing preset: {name}")
    with path.open(encoding="utf-8") as handle:
        json.load(handle)

print("JSON syntax OK")
PY
fi

DEST="$MOODLE_DIR/local/uckk/presets"

info "Copying presets into Moodle target: $DEST"
mkdir -p "$DEST"
cp -R "$IN_DIR/"*.json "$DEST/"

IMPORT_CLI="$MOODLE_DIR/admin/tool/uckkseed/cli/import_presets.php"
SEED_CLI="$MOODLE_DIR/admin/tool/uckkseed/cli/seed.php"

if [ -f "$IMPORT_CLI" ]; then
  info "Running tool_uckkseed import in mode: $MODE"
  php "$IMPORT_CLI" --presetdir="$DEST" --mode="$MODE"
elif [ -f "$SEED_CLI" ]; then
  info "Running tool_uckkseed seed in mode: $MODE"
  php "$SEED_CLI" --presetdir="$DEST" --mode="$MODE"
else
  info "tool_uckkseed import CLI not found. Presets were copied only."
  info "Expected one of:"
  info "  $IMPORT_CLI"
  info "  $SEED_CLI"
fi

info "Import step complete."