#!/usr/bin/env bash
set -euo pipefail

# Export UCKK preset JSON files from a Moodle installation.
#
# Usage:
#   ./uckk-tools/export-presets.sh /path/to/moodle [output-dir]
#
# Environment:
#   MOODLE_DIR                  Moodle root, used when first argument is omitted.
#   UCKK_PRESETS_EXPORT_DIR     Output directory, used when second argument is omitted.
#   UCKK_EXPORT_MODE            export|copy, default: export.
#
# The preferred path uses tool_uckkseed's exporter CLI when available.
# Fallback copies the installed preset JSON files from local/uckk/presets.

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOODLE_DIR="${1:-${MOODLE_DIR:-}}"
OUT_DIR="${2:-${UCKK_PRESETS_EXPORT_DIR:-$ROOT/uckk-presets-export}}"
MODE="${UCKK_EXPORT_MODE:-export}"

REQUIRED_FILES=(
  categories.json
  courses.json
  course_templates.json
  cohorts.json
  roles.json
  capabilities.json
  competencies.json
  badges.json
  reports.json
  challenge_templates.json
  assembly_templates.json
  archive_templates.json
  integrity_policies.json
)

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

info() {
  printf '[uckk-export-presets] %s\n' "$*"
}

[ -n "$MOODLE_DIR" ] || fail "Usage: $0 /path/to/moodle [output-dir]"
[ -d "$MOODLE_DIR" ] || fail "Moodle directory not found: $MOODLE_DIR"
[ -f "$MOODLE_DIR/config.php" ] || fail "Moodle config.php not found in: $MOODLE_DIR"

mkdir -p "$OUT_DIR"

EXPORTER="$MOODLE_DIR/admin/tool/uckkseed/cli/export_presets.php"
INSTALLED_PRESETS="$MOODLE_DIR/local/uckk/presets"

if [ "$MODE" = "export" ] && [ -f "$EXPORTER" ]; then
  info "Using tool_uckkseed exporter."
  php "$EXPORTER" --outdir="$OUT_DIR"
else
  info "Exporter CLI unavailable or disabled; copying installed presets."

  [ -d "$INSTALLED_PRESETS" ] || fail "No exporter CLI and no installed preset directory found: $INSTALLED_PRESETS"

  for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$INSTALLED_PRESETS/$file" ]; then
      cp "$INSTALLED_PRESETS/$file" "$OUT_DIR/$file"
    else
      info "Missing optional installed preset: $file"
    fi
  done
fi

for file in "${REQUIRED_FILES[@]}"; do
  [ -f "$OUT_DIR/$file" ] || fail "Export did not produce required preset: $file"
done

if command -v python3 >/dev/null 2>&1; then
  python3 - "$OUT_DIR" <<'PY'
import json
import pathlib
import sys

root = pathlib.Path(sys.argv[1])

for path in sorted(root.glob("*.json")):
    with path.open(encoding="utf-8") as handle:
        json.load(handle)

print("JSON validation OK")
PY
else
  info "python3 not available; skipping JSON validation."
fi

MANIFEST="$OUT_DIR/MANIFEST.sha256"
: > "$MANIFEST"

find "$OUT_DIR" -maxdepth 1 -type f -name '*.json' | sort | while read -r file; do
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$file" >> "$MANIFEST"
  else
    shasum -a 256 "$file" >> "$MANIFEST"
  fi
done

cat > "$OUT_DIR/export-info.json" <<JSON
{
  "schema": "uckk.presets.export_info.v1",
  "created_at_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "moodle_dir": "$MOODLE_DIR",
  "output_dir": "$OUT_DIR",
  "mode": "$MODE",
  "manifest": "MANIFEST.sha256"
}
JSON

info "Export complete: $OUT_DIR"