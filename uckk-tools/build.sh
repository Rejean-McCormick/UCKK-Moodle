#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PRESETS_DIR="${UCKK_PRESETS_DIR:-$ROOT/uckk-presets}"
TOOLS_DIR="$ROOT/uckk-tools"
BUILD_DIR="${UCKK_BUILD_DIR:-$ROOT/build}"

log() {
  printf '[uckk-build] %s\n' "$*"
}

fail() {
  printf '[uckk-build] ERROR: %s\n' "$*" >&2
  exit 1
}

require_file() {
  [ -f "$1" ] || fail "Missing required file: $1"
}

require_dir() {
  [ -d "$1" ] || fail "Missing required directory: $1"
}

sha256_file() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$1"
  elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$1"
  else
    fail "Neither sha256sum nor shasum is available."
  fi
}

require_dir "$PRESETS_DIR"
require_dir "$TOOLS_DIR"
require_file "$TOOLS_DIR/validate.sh"

log "Validating UCKK presets"
"$TOOLS_DIR/validate.sh"

mkdir -p "$BUILD_DIR"

MANIFEST="$BUILD_DIR/MANIFEST.sha256"
BUILDINFO="$BUILD_DIR/build-info.json"
FILELIST="$BUILD_DIR/files.txt"

: > "$MANIFEST"
: > "$FILELIST"

log "Creating preset file manifest"

find "$PRESETS_DIR" -type f -name '*.json' | sort | while read -r file; do
  relpath="${file#$ROOT/}"
  printf '%s\n' "$relpath" >> "$FILELIST"
  sha256_file "$file" >> "$MANIFEST"
done

log "Writing build metadata"

cat > "$BUILDINFO" <<JSON
{
  "schema": "uckk.build_info.v1",
  "component": "uckk-presets-tools",
  "created_at_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "root": "$ROOT",
  "presets_dir": "$PRESETS_DIR",
  "tools_dir": "$TOOLS_DIR",
  "build_dir": "$BUILD_DIR",
  "manifest": "MANIFEST.sha256",
  "file_list": "files.txt",
  "validation": {
    "json_syntax": true,
    "cross_file_references": true,
    "idempotency_keys": [
      "category.idnumber",
      "course.shortname",
      "cohort.idnumber",
      "competency.idnumber",
      "badge.name + issuer",
      "role.shortname",
      "report.shortname"
    ]
  }
}
JSON

if command -v shellcheck >/dev/null 2>&1; then
  log "Running shellcheck on uckk-tools scripts"
  find "$TOOLS_DIR" -maxdepth 1 -type f -name '*.sh' -print0 | xargs -0 shellcheck
else
  log "shellcheck not found; skipping shell lint"
fi

log "Build complete"
log "Manifest: $MANIFEST"
log "Build info: $BUILDINFO"