#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PRESETS_DIR="${UCKK_PRESETS_DIR:-$ROOT/uckk-presets}"

fail() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

info() {
    printf '[uckk-validate] %s\n' "$*"
}

[ -d "$PRESETS_DIR" ] || fail "Preset directory not found: $PRESETS_DIR"

required=(
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

for file in "${required[@]}"; do
    [ -f "$PRESETS_DIR/$file" ] || fail "Missing preset: $file"
done

python3 - "$PRESETS_DIR" <<'__PY__'
import json
import pathlib
import sys

root = pathlib.Path(sys.argv[1])

for path in sorted(root.glob("*.json")):
    with path.open(encoding="utf-8") as f:
        json.load(f)

load = lambda n: json.loads((root / n).read_text(encoding="utf-8"))

cat, courses, comps, badges, roles, caps, reports = [
    load(n)
    for n in [
        "categories.json",
        "courses.json",
        "competencies.json",
        "badges.json",
        "roles.json",
        "capabilities.json",
        "reports.json",
    ]
]

category_ids = {cat["root"]["idnumber"]} | {c["idnumber"] for c in cat["categories"]}
comp_ids = {c["idnumber"] for c in comps["competencies"]}
badge_keys = {b["key"] for b in badges["badges"]}
role_names = {r["shortname"] for r in roles["roles"]}
report_names = [r["shortname"] for r in reports["reports"]]

for c in courses["courses"]:
    assert c["category"] in category_ids, (
        f"Course {c['shortname']} references missing category {c['category']}"
    )

    for comp in c.get("competencies", []):
        assert comp in comp_ids, (
            f"Course {c['shortname']} references missing competency {comp}"
        )

    for badge in c.get("badges", []):
        assert badge in badge_keys, (
            f"Course {c['shortname']} references missing badge {badge}"
        )

for cap in caps["capabilities"]:
    for role in cap.get("allow", []):
        assert role in role_names, (
            f"Capability {cap['name']} references missing role {role}"
        )

assert len(set(report_names)) == len(report_names), "Duplicate report shortname detected"

print("cross-file checks OK")
__PY__

info "All preset JSON files are valid and internally aligned."