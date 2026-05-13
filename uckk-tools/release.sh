#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLS_DIR="$ROOT/uckk-tools"
DIST_DIR="${UCKK_DIST_DIR:-$ROOT/dist}"
RELEASE_ROOT="${UCKK_RELEASE_DIR:-$ROOT/release}"
VERSION="${1:-${UCKK_RELEASE_VERSION:-$(date -u +%Y.%m.%d)}}"
RELEASE_DIR="$RELEASE_ROOT/$VERSION"

log() {
  printf '[uckk-release] %s\n' "$*"
}

fail() {
  printf '[uckk-release] ERROR: %s\n' "$*" >&2
  exit 1
}

require_file() {
  [ -f "$1" ] || fail "Missing required file: $1"
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

require_file "$TOOLS_DIR/validate.sh"
require_file "$TOOLS_DIR/build.sh"
require_file "$TOOLS_DIR/package.sh"

export UCKK_RELEASE_VERSION="$VERSION"
export UCKK_DIST_DIR="$DIST_DIR"

log "Preparing UCKK presets/tools release $VERSION"

log "Validating presets"
"$TOOLS_DIR/validate.sh"

log "Building release metadata"
"$TOOLS_DIR/build.sh"

log "Packaging release archives"
"$TOOLS_DIR/package.sh"

mkdir -p "$RELEASE_DIR"

log "Collecting package artifacts"

find "$DIST_DIR" -maxdepth 1 -type f \
  \( -name "uckk-presets-tools-$VERSION.zip" -o -name "uckk-presets-tools-$VERSION.tar.gz" \) \
  -exec cp {} "$RELEASE_DIR/" \;

if ! find "$RELEASE_DIR" -maxdepth 1 -type f | grep -q .; then
  fail "No release artifacts were created for version $VERSION"
fi

log "Writing release checksums"

CHECKSUMS="$RELEASE_DIR/SHA256SUMS"
: > "$CHECKSUMS"

find "$RELEASE_DIR" -maxdepth 1 -type f \
  \( -name '*.zip' -o -name '*.tar.gz' \) \
  | sort \
  | while read -r artifact; do
      sha256_file "$artifact" >> "$CHECKSUMS"
    done

log "Writing release notes"

cat > "$RELEASE_DIR/RELEASE-NOTES.md" <<MD
# UCKK presets/tools $VERSION

Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)

## Contents

- \`uckk-presets/*.json\`
- \`uckk-tools/*.sh\`
- build metadata
- SHA-256 checksums

## Validation completed

- JSON syntax validation
- cross-file reference validation
- idempotency key consistency
- package archive generation

## Seed files

- \`categories.json\`
- \`courses.json\`
- \`course_templates.json\`
- \`cohorts.json\`
- \`roles.json\`
- \`capabilities.json\`
- \`competencies.json\`
- \`badges.json\`
- \`reports.json\`
- \`challenge_templates.json\`
- \`assembly_templates.json\`
- \`archive_templates.json\`
- \`integrity_policies.json\`

## Install

\`\`\`bash
./uckk-tools/install.sh /path/to/moodle
\`\`\`

Default mode is dry-run. To apply:

\`\`\`bash
UCKK_SEED_MODE=apply ./uckk-tools/install.sh /path/to/moodle
\`\`\`

## Rollback note

Rollback support depends on \`tool_uckkseed\` keeping rollback tokens for seed-created objects.
MD

log "Writing release manifest"

find "$RELEASE_DIR" -maxdepth 1 -type f | sort > "$RELEASE_DIR/MANIFEST.txt"

log "Release complete: $RELEASE_DIR"
log "Checksums: $CHECKSUMS"