#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${UCKK_DIST_DIR:-$ROOT/dist}"
BUILD_DIR="${UCKK_BUILD_DIR:-$ROOT/build}"
VERSION="${UCKK_RELEASE_VERSION:-$(date -u +%Y%m%d%H%M%S)}"
PACKAGE_NAME="uckk-presets-tools-$VERSION"
PACKAGE_DIR="$DIST_DIR/$PACKAGE_NAME"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

info() {
  printf '[uckk-package] %s\n' "$*"
}

require_file() {
  [ -f "$1" ] || fail "Missing required file: $1"
}

require_dir() {
  [ -d "$1" ] || fail "Missing required directory: $1"
}

require_command() {
  command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

require_command zip
require_command tar

require_dir "$ROOT/uckk-presets"
require_dir "$ROOT/uckk-tools"
require_file "$ROOT/uckk-tools/validate.sh"
require_file "$ROOT/uckk-tools/build.sh"

info "Validating presets"
"$ROOT/uckk-tools/validate.sh"

info "Building manifest"
"$ROOT/uckk-tools/build.sh"

mkdir -p "$DIST_DIR"
rm -rf "$PACKAGE_DIR"
mkdir -p "$PACKAGE_DIR"

info "Copying package files"
cp -R "$ROOT/uckk-presets" "$PACKAGE_DIR/"
cp -R "$ROOT/uckk-tools" "$PACKAGE_DIR/"
cp -R "$BUILD_DIR" "$PACKAGE_DIR/"

if [ -f "$ROOT/README.md" ]; then
  cp "$ROOT/README.md" "$PACKAGE_DIR/"
fi

if [ -f "$ROOT/MANIFEST.sha256" ]; then
  cp "$ROOT/MANIFEST.sha256" "$PACKAGE_DIR/SOURCE-MANIFEST.sha256"
fi

cat > "$PACKAGE_DIR/PACKAGE.json" <<JSON
{
  "schema": "uckk.package.v1",
  "name": "$PACKAGE_NAME",
  "version": "$VERSION",
  "created_at_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "contents": [
    "uckk-presets",
    "uckk-tools",
    "build",
    "README.md",
    "SOURCE-MANIFEST.sha256"
  ],
  "validation": {
    "json": "passed",
    "cross_file_references": "passed",
    "manifest": "generated"
  }
}
JSON

info "Creating archives"
(
  cd "$DIST_DIR"
  rm -f "$PACKAGE_NAME.zip" "$PACKAGE_NAME.tar.gz"
  zip -qr "$PACKAGE_NAME.zip" "$PACKAGE_NAME"
  tar -czf "$PACKAGE_NAME.tar.gz" "$PACKAGE_NAME"
)

info "Writing archive checksums"
(
  cd "$DIST_DIR"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$PACKAGE_NAME.zip" "$PACKAGE_NAME.tar.gz" > "$PACKAGE_NAME.sha256"
  else
    shasum -a 256 "$PACKAGE_NAME.zip" "$PACKAGE_NAME.tar.gz" > "$PACKAGE_NAME.sha256"
  fi
)

info "Verifying ZIP archive"
unzip -t "$DIST_DIR/$PACKAGE_NAME.zip" >/dev/null

info "Verifying TAR.GZ archive"
tar -tzf "$DIST_DIR/$PACKAGE_NAME.tar.gz" >/dev/null

info "Package ready"
printf '%s\n' "$DIST_DIR/$PACKAGE_NAME.zip"
printf '%s\n' "$DIST_DIR/$PACKAGE_NAME.tar.gz"
printf '%s\n' "$DIST_DIR/$PACKAGE_NAME.sha256"