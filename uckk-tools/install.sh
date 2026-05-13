#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOODLE_DIR="${1:-${MOODLE_DIR:-}}"
MODE="${UCKK_SEED_MODE:-dry_run}"
PRESETS_DIR="${UCKK_PRESETS_DIR:-$ROOT/uckk-presets}"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

info() {
  printf '[uckk-install] %s\n' "$*"
}

usage() {
  cat >&2 <<'USAGE'
Usage:
  ./uckk-tools/install.sh /path/to/moodle

Environment:
  UCKK_SEED_MODE=dry_run|apply|repair
  UCKK_PRESETS_DIR=/path/to/uckk-presets

Default:
  UCKK_SEED_MODE=dry_run
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
[ -d "$PRESETS_DIR" ] || fail "Preset directory not found: $PRESETS_DIR"
[ -f "$ROOT/uckk-tools/validate.sh" ] || fail "validate.sh not found."

info "Validating presets."
UCKK_PRESETS_DIR="$PRESETS_DIR" "$ROOT/uckk-tools/validate.sh"

DEST="$MOODLE_DIR/local/uckk/presets"

info "Installing presets into: $DEST"
mkdir -p "$DEST"
cp -R "$PRESETS_DIR/"*.json "$DEST/"

SEED_CLI="$MOODLE_DIR/admin/tool/uckkseed/cli/seed.php"
IMPORT_CLI="$MOODLE_DIR/admin/tool/uckkseed/cli/import_presets.php"

if [ -f "$IMPORT_CLI" ]; then
  info "Running tool_uckkseed import in mode: $MODE"
  php "$IMPORT_CLI" --presetdir="$DEST" --mode="$MODE"
elif [ -f "$SEED_CLI" ]; then
  info "Running tool_uckkseed seed in mode: $MODE"
  php "$SEED_CLI" --presetdir="$DEST" --mode="$MODE"
else
  info "tool_uckkseed CLI not found. Presets were copied only."
  info "Expected one of:"
  info "  $IMPORT_CLI"
  info "  $SEED_CLI"
fi

if [ -f "$MOODLE_DIR/admin/cli/upgrade.php" ]; then
  info "Moodle upgrade CLI available. Run manually if plugins were newly copied:"
  info "  php $MOODLE_DIR/admin/cli/upgrade.php --non-interactive"
fi

info "Install step complete."