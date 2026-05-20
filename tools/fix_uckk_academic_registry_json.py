#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Verbose fixer/normalizer for UCKK academic_registry_json presets.

Run examples:

    # Dry run, detailed output, no writes, no backups.
    python tools/fix_uckk_academic_registry_json.py academic_registry_json --dry-run --verbose

    # Very verbose, with field-level logs and unified diffs.
    python tools/fix_uckk_academic_registry_json.py academic_registry_json --dry-run --very-verbose --show-diff

    # Actually write files, creating timestamped .bak backups.
    python tools/fix_uckk_academic_registry_json.py academic_registry_json --verbose

    # Write a machine-readable report.
    python tools/fix_uckk_academic_registry_json.py academic_registry_json --dry-run --verbose --report-json fix_report.json

Purpose:

    This script normalizes the JSON files that belong to the UCKK Moodle
    academic registry seed folder. It is intentionally conservative:

    - It does not delete source/canon fields.
    - It adds canonical runtime fields expected by seed handlers.
    - It keeps compatibility aliases such as category_idnumber.
    - It creates backups before writing, unless --dry-run is used.
    - It reports what changed and what still needs code/db/access.php alignment.

Important:

    This script fixes JSON shape and normalization only.
    It cannot create missing Moodle plugin capabilities in db/access.php.
    It cannot implement missing seed handlers.
    It cannot fix a seeder route that sends a preset to the wrong handler.
"""

from __future__ import annotations

import argparse
import copy
import dataclasses
import difflib
import json
import re
import shutil
import sys
from datetime import datetime
from pathlib import Path
from typing import Any, Iterable


PRESET_VERSION = 2026051200
SCHEMA = "uckkseed.preset.v1"
COMPONENT = "tool_uckkseed"

DEFAULT_FILES = [
    "roles.json",
    "capabilities.json",
    "badges.json",
    "courses.json",
    "reports.json",
    "competencies.json",
    "programs.json",
    "pathways.json",
    "course_templates.json",
    "challenge_templates.json",
    "assembly_templates.json",
    "archive_templates.json",
]

TEMPLATE_FILES = {
    "course_templates.json",
    "challenge_templates.json",
    "assembly_templates.json",
    "archive_templates.json",
}

TEMPLATE_EXPECTED_COMPONENTS = {
    "course_templates": "format_uckk",
    "challenge_templates": "mod_uckkchallenge",
    "assembly_templates": "mod_uckkassembly",
    "archive_templates": "mod_uckkarchive",
}

COURSE_TEMPLATE_BY_RULE = {
    "tronc_commun": "uckk_tronc_commun_course",
    "seminaire": "uckk_seminaire_course",
    "laboratoire": "uckk_laboratoire_course",
    "mineure": "uckk_mineure_course",
    "program": "uckk_program_course",
    "standard": "uckk_standard_course",
}

REPORT_CAPABILITY_DEFAULTS = {
    "joueur_progress": "report/uckk:view",
    "cohort_progress": "report/uckk:viewall",
    "program_progress": "report/uckk:viewall",
    "competency_matrix": "report/uckk:viewall",
    "badge_awards": "report/uckk:viewall",
    "challenge_status": "report/uckk:viewall",
    "assembly_decisions": "report/uckk:viewall",
    "archive_production": "report/uckk:viewall",
    "integrity_cases": "report/uckk:viewrestricted",
    "ai_usage": "report/uckk:viewall",
    "privacy_exports": "report/uckk:viewall",
}

REQUIRED_FIELDS_BY_PRESET = {
    "programs": [
        "key",
        "shortname",
        "idnumber",
        "name",
        "fullname",
        "category",
        "program_type",
        "status",
        "visibility",
        "visible",
    ],
    "pathways": [
        "key",
        "shortname",
        "idnumber",
        "name",
        "fullname",
        "program_id",
        "pathway_type",
        "status",
        "visibility",
    ],
    "roles": [
        "shortname",
        "name",
        "archetype",
        "contextlevels",
        "capabilities",
    ],
    "capabilities": [
        "role",
        "capability",
        "permission",
        "context",
        "component",
    ],
    "competencies": [
        "key",
        "name",
        "shortname",
        "fullname",
        "idnumber",
        "visible",
    ],
    "badges": [
        "key",
        "name",
        "description",
        "idnumber",
        "criteria",
        "competencies",
        "visible",
    ],
    "courses": [
        "key",
        "fullname",
        "shortname",
        "idnumber",
        "category",
        "format",
        "summary",
        "summaryformat",
        "visible",
        "template",
    ],
    "reports": [
        "key",
        "name",
        "component",
        "capability",
        "source",
        "enabled",
    ],
    "course_templates": [
        "key",
        "name",
        "component",
        "defaults",
    ],
    "challenge_templates": [
        "key",
        "name",
        "component",
        "defaults",
    ],
    "assembly_templates": [
        "key",
        "name",
        "component",
        "defaults",
    ],
    "archive_templates": [
        "key",
        "name",
        "component",
        "defaults",
    ],
}

UNIQUE_FIELDS_BY_PRESET = {
    "categories": ["key", "idnumber"],
    "programs": ["key", "shortname", "idnumber"],
    "pathways": ["key", "shortname", "idnumber"],
    "roles": ["shortname"],
    "capabilities": [],
    "competencies": ["key", "idnumber"],
    "badges": ["key", "idnumber"],
    "course_templates": ["key"],
    "challenge_templates": ["key"],
    "assembly_templates": ["key"],
    "archive_templates": ["key"],
    "courses": ["key", "shortname", "idnumber"],
    "reports": ["key"],
}


@dataclasses.dataclass
class Change:
    file: str
    item: str
    field: str
    before: Any
    after: Any
    reason: str


@dataclasses.dataclass
class FileResult:
    filename: str
    path: str
    preset: str | None
    exists: bool
    changed: bool = False
    backup_path: str | None = None
    item_count: int = 0
    change_count: int = 0
    changes: list[Change] = dataclasses.field(default_factory=list)
    warnings: list[str] = dataclasses.field(default_factory=list)
    errors: list[str] = dataclasses.field(default_factory=list)
    diff: str | None = None


@dataclasses.dataclass
class RunReport:
    registry: str
    dry_run: bool
    started_at: str
    finished_at: str | None = None
    files_seen: int = 0
    files_changed: int = 0
    files_missing: int = 0
    total_changes: int = 0
    total_warnings: int = 0
    total_errors: int = 0
    results: list[FileResult] = dataclasses.field(default_factory=list)


class Logger:
    def __init__(self, verbose: bool = False, very_verbose: bool = False, quiet: bool = False) -> None:
        self.verbose = verbose or very_verbose
        self.very_verbose = very_verbose
        self.quiet = quiet

    def info(self, message: str) -> None:
        if not self.quiet:
            print(message)

    def verbose_info(self, message: str) -> None:
        if self.verbose and not self.quiet:
            print(message)

    def detail(self, message: str) -> None:
        if self.very_verbose and not self.quiet:
            print(message)

    def warning(self, message: str) -> None:
        if not self.quiet:
            print(f"WARNING: {message}", file=sys.stderr)

    def error(self, message: str) -> None:
        if not self.quiet:
            print(f"ERROR: {message}", file=sys.stderr)


class Mutator:
    """
    Helper that changes items while recording field-level reasons.
    """

    def __init__(self, filename: str, result: FileResult, logger: Logger) -> None:
        self.filename = filename
        self.result = result
        self.logger = logger

    def item_label(self, item: dict[str, Any], fallback_index: int | None = None) -> str:
        for field in ["key", "shortname", "idnumber", "id", "code", "name", "title"]:
            value = item.get(field)
            if value not in (None, ""):
                return str(value)
        if fallback_index is not None:
            return f"item#{fallback_index}"
        return "item"

    def record(self, item_label: str, field: str, before: Any, after: Any, reason: str) -> None:
        if before == after:
            return

        change = Change(
            file=self.filename,
            item=item_label,
            field=field,
            before=copy.deepcopy(before),
            after=copy.deepcopy(after),
            reason=reason,
        )
        self.result.changes.append(change)
        self.result.change_count += 1
        self.logger.detail(
            f"      - {item_label}.{field}: {short_repr(before)} -> {short_repr(after)} ({reason})"
        )

    def set_value(
        self,
        item: dict[str, Any],
        field: str,
        value: Any,
        reason: str,
        fallback_index: int | None = None,
    ) -> None:
        before = item.get(field, None)
        if before != value:
            item[field] = value
            self.record(self.item_label(item, fallback_index), field, before, value, reason)

    def setdefault_value(
        self,
        item: dict[str, Any],
        field: str,
        value: Any,
        reason: str,
        fallback_index: int | None = None,
        treat_empty_as_missing: bool = True,
    ) -> None:
        before = item.get(field, None)
        missing = field not in item or (treat_empty_as_missing and item.get(field) in (None, ""))
        if missing:
            item[field] = value
            self.record(self.item_label(item, fallback_index), field, before, value, reason)

    def set_path(
        self,
        item: dict[str, Any],
        path: list[str],
        value: Any,
        reason: str,
        fallback_index: int | None = None,
    ) -> None:
        cursor: dict[str, Any] = item

        for part in path[:-1]:
            if part not in cursor or not isinstance(cursor[part], dict):
                before = cursor.get(part)
                cursor[part] = {}
                self.record(
                    self.item_label(item, fallback_index),
                    ".".join(path[:-1]),
                    before,
                    {},
                    "create nested object",
                )
            cursor = cursor[part]

        field = path[-1]
        before = cursor.get(field, None)
        if before != value:
            cursor[field] = value
            self.record(self.item_label(item, fallback_index), ".".join(path), before, value, reason)

    def ensure_metadata(self, item: dict[str, Any], fallback_index: int | None = None) -> dict[str, Any]:
        before = item.get("metadata", None)
        if not isinstance(before, dict):
            item["metadata"] = {}
            self.record(
                self.item_label(item, fallback_index),
                "metadata",
                before,
                {},
                "metadata must be a JSON object",
            )
        return item["metadata"]


def short_repr(value: Any, limit: int = 90) -> str:
    rendered = json.dumps(value, ensure_ascii=False, sort_keys=True)
    if len(rendered) > limit:
        return rendered[: limit - 3] + "..."
    return rendered


def load_json(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8-sig") as handle:
        data = json.load(handle)

    if not isinstance(data, dict):
        raise ValueError("top-level JSON must be an object")

    if "items" not in data:
        raise ValueError("top-level JSON object must contain items")

    if not isinstance(data["items"], list):
        raise ValueError("top-level items must be an array")

    return data


def save_json(path: Path, data: dict[str, Any]) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as handle:
        json.dump(data, handle, ensure_ascii=False, indent=2)
        handle.write("\n")


def canonical_json_text(data: dict[str, Any]) -> str:
    return json.dumps(data, ensure_ascii=False, indent=2) + "\n"


def backup(path: Path) -> Path:
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_path = path.with_suffix(path.suffix + f".{stamp}.bak")
    shutil.copy2(path, backup_path)
    return backup_path


def ensure_envelope(data: dict[str, Any], preset: str, mutator: Mutator) -> None:
    current = data.get("schema")
    if current != SCHEMA:
        data["schema"] = SCHEMA
        mutator.record("<envelope>", "schema", current, SCHEMA, "normalize common preset envelope")

    current = data.get("component")
    if current != COMPONENT:
        data["component"] = COMPONENT
        mutator.record("<envelope>", "component", current, COMPONENT, "normalize common preset envelope")

    current = data.get("preset")
    if current != preset:
        data["preset"] = preset
        mutator.record("<envelope>", "preset", current, preset, "normalize preset id from filename")

    current = data.get("version")
    if current != PRESET_VERSION:
        data["version"] = PRESET_VERSION
        mutator.record("<envelope>", "version", current, PRESET_VERSION, "normalize preset version")

    if "items" not in data or not isinstance(data["items"], list):
        before = data.get("items")
        data["items"] = []
        mutator.record("<envelope>", "items", before, [], "items must be an array")


def visible_int(value: Any, default: int = 1) -> int:
    if value is None:
        return default

    if isinstance(value, bool):
        return 1 if value else 0

    if isinstance(value, int):
        return 1 if value else 0

    if isinstance(value, str):
        clean = value.strip().lower()
        if clean in {"1", "true", "yes", "y", "visible", "public", "active", "institution"}:
            return 1
        if clean in {"0", "false", "no", "n", "hidden", "private", "inactive", "draft"}:
            return 0

    return default


def capability_component(capability: str) -> str:
    """
    Convert Moodle capability names to Frankenstyle component names.

    Examples:
        local/uckk:viewcampus           -> local_uckk
        format/uckk:viewcoursemap       -> format_uckk
        block/uckk_dashboard:view       -> block_uckk_dashboard
        mod/uckkchallenge:view          -> mod_uckkchallenge
        tool/uckkseed:seed              -> tool_uckkseed
        report/uckk:view                -> report_uckk
    """
    if not isinstance(capability, str) or "/" not in capability:
        return ""

    before_colon = capability.split(":", 1)[0]
    parts = before_colon.split("/", 1)

    if len(parts) != 2:
        return ""

    plugintype, pluginname = parts
    plugintype = plugintype.strip()
    pluginname = pluginname.strip()

    if not plugintype or not pluginname:
        return ""

    return f"{plugintype}_{pluginname}"


def normalize_key(value: str) -> str:
    value = value.strip().lower()
    value = re.sub(r"[^a-z0-9]+", "_", value)
    value = re.sub(r"_+", "_", value)
    return value.strip("_")


def first_present(item: dict[str, Any], *keys: str, default: Any = None) -> Any:
    for key in keys:
        if key in item and item[key] not in (None, ""):
            return item[key]
    return default


def promote_moodle_fields(item: dict[str, Any], mutator: Mutator, index: int) -> None:
    moodle = item.get("moodle")
    if not isinstance(moodle, dict):
        return

    for field in [
        "shortname",
        "fullname",
        "idnumber",
        "category_idnumber",
        "category_path",
        "format",
        "visible",
        "lang",
        "enablecompletion",
        "summaryformat",
    ]:
        if field not in item and field in moodle:
            mutator.setdefault_value(
                item,
                field,
                moodle[field],
                f"promote Moodle-facing field from moodle.{field}",
                fallback_index=index,
            )


def infer_course_template(item: dict[str, Any]) -> str:
    category = str(first_present(item, "category", "category_idnumber", default="")).upper()
    course_type = str(item.get("course_type", "")).lower()
    code = str(first_present(item, "code", "shortname", "idnumber", default="")).upper()

    if category == "UCKK-TC" or code.startswith("UCKK-TC"):
        return COURSE_TEMPLATE_BY_RULE["tronc_commun"]

    if "seminaire" in course_type or "séminaire" in course_type:
        return COURSE_TEMPLATE_BY_RULE["seminaire"]

    if "laboratoire" in course_type or "lab" in course_type:
        return COURSE_TEMPLATE_BY_RULE["laboratoire"]

    if "mineure" in course_type:
        return COURSE_TEMPLATE_BY_RULE["mineure"]

    return COURSE_TEMPLATE_BY_RULE["program"]


def normalize_courses(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "courses", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"courses.json item {index}: skipped non-object item")
            continue

        promote_moodle_fields(item, mutator, index)

        if "key" not in item or item.get("key") in (None, ""):
            source = first_present(item, "code", "shortname", "idnumber", "title", default=f"course_{index}")
            mutator.setdefault_value(item, "key", normalize_key(str(source)), "derive stable key", index)

        mutator.setdefault_value(
            item,
            "fullname",
            first_present(item, "title", "name", "short_title", default=item["key"]),
            "derive Moodle fullname",
            index,
        )

        mutator.setdefault_value(
            item,
            "shortname",
            first_present(item, "code", "idnumber", default=str(item["key"]).upper()),
            "derive Moodle shortname",
            index,
        )

        mutator.setdefault_value(
            item,
            "idnumber",
            first_present(item, "code", "shortname", default=item["shortname"]),
            "derive Moodle idnumber",
            index,
        )

        if "category" not in item or item.get("category") in (None, ""):
            mutator.setdefault_value(
                item,
                "category",
                first_present(item, "category_idnumber", default=None),
                "category is canonical lookup field; copied from category_idnumber",
                index,
            )

        if "category_idnumber" not in item and item.get("category"):
            mutator.setdefault_value(
                item,
                "category_idnumber",
                item["category"],
                "keep compatibility alias for category lookup",
                index,
            )

        mutator.setdefault_value(item, "format", "uckk", "default UCKK course format", index)
        mutator.setdefault_value(item, "summary", item.get("description", ""), "default empty/description summary", index)

        if item.get("summaryformat") != 1:
            mutator.set_value(item, "summaryformat", 1, "normalize Moodle summaryformat", index)

        normalized_visible = visible_int(item.get("visible"), default=0)
        if item.get("visible") != normalized_visible:
            mutator.set_value(item, "visible", normalized_visible, "normalize visible to Moodle int 0/1", index)

        mutator.setdefault_value(item, "lang", "fr", "default language", index)

        if not isinstance(item.get("enablecompletion"), bool):
            mutator.set_value(
                item,
                "enablecompletion",
                bool(item.get("enablecompletion", True)),
                "normalize enablecompletion to boolean",
                index,
            )

        if "template" not in item or item.get("template") in (None, ""):
            mutator.setdefault_value(
                item,
                "template",
                infer_course_template(item),
                "infer seed-managed course template",
                index,
            )

        mutator.setdefault_value(item, "startdate", 0, "default no fixed start date", index)
        mutator.setdefault_value(item, "enddate", 0, "default no fixed end date", index)
        mutator.setdefault_value(item, "sections", [], "default no explicit section payload", index)
        mutator.setdefault_value(item, "completion", {}, "default empty completion rules", index)
        mutator.setdefault_value(item, "sortorder", index * 10, "stable default sort order", index)

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "courses":
            mutator.set_path(item, ["metadata", "source_preset"], "courses", "record source preset", index)

        for optional_field in [
            "course_type",
            "course_type_label",
            "requirement_default",
            "catalog_status_label",
            "academic_block",
            "learning_outcomes",
            "assessment",
            "ai_metadata",
            "source",
            "source_additional",
        ]:
            if optional_field in item and optional_field not in metadata:
                mutator.set_path(
                    item,
                    ["metadata", optional_field],
                    copy.deepcopy(item[optional_field]),
                    "preserve source/canon field in metadata",
                    index,
                )


def normalize_programs(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "programs", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"programs.json item {index}: skipped non-object item")
            continue

        source = first_present(item, "code", "shortname", "idnumber", "title", default=f"program_{index}")
        mutator.setdefault_value(item, "key", normalize_key(str(source)), "derive stable key", index)
        mutator.setdefault_value(item, "shortname", first_present(item, "code", "idnumber", default=item["key"]), "derive shortname", index)
        mutator.setdefault_value(
            item,
            "fullname",
            first_present(item, "title", "name", "short_title", default=item["shortname"]),
            "derive fullname",
            index,
        )
        mutator.setdefault_value(item, "name", item["fullname"], "derive generic display name", index)
        mutator.setdefault_value(
            item,
            "idnumber",
            f"UCKK-PROG-{str(item['shortname']).upper()}",
            "derive stable idnumber",
            index,
        )

        if "category" not in item or item.get("category") in (None, ""):
            mutator.setdefault_value(
                item,
                "category",
                first_present(item, "category_idnumber", default=None),
                "category is canonical lookup field; copied from category_idnumber",
                index,
            )

        if "category_idnumber" not in item and item.get("category"):
            mutator.setdefault_value(item, "category_idnumber", item["category"], "keep compatibility alias", index)

        mutator.setdefault_value(item, "description", item.get("summary", ""), "derive description from summary", index)
        mutator.setdefault_value(item, "summary", item.get("description", ""), "derive summary from description", index)
        mutator.setdefault_value(item, "status", "active", "default active status", index)
        mutator.setdefault_value(item, "visibility", "institution", "default institutional visibility", index)

        normalized_visible = visible_int(item.get("visible"), default=1)
        if item.get("visible") != normalized_visible:
            mutator.set_value(item, "visible", normalized_visible, "normalize visible to Moodle int 0/1", index)

        mutator.setdefault_value(item, "sortorder", index * 10, "stable default sort order", index)

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "programs":
            mutator.set_path(item, ["metadata", "source_preset"], "programs", "record source preset", index)


def normalize_pathways(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "pathways", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"pathways.json item {index}: skipped non-object item")
            continue

        source = first_present(item, "idnumber", "shortname", "title", default=f"pathway_{index}")
        mutator.setdefault_value(item, "key", normalize_key(str(source)), "derive stable key", index)
        mutator.setdefault_value(item, "shortname", item["key"], "derive shortname", index)
        mutator.setdefault_value(
            item,
            "fullname",
            first_present(item, "title", "name", default=item["shortname"]),
            "derive fullname",
            index,
        )
        mutator.setdefault_value(item, "name", item["fullname"], "derive generic display name", index)
        mutator.setdefault_value(item, "idnumber", str(item["shortname"]).upper(), "derive idnumber", index)

        mutator.setdefault_value(
            item,
            "pathway_type",
            item.get("sequence_model") or "ordered_pathway",
            "derive pathway_type from sequence_model",
            index,
        )
        mutator.setdefault_value(item, "status", "active", "default active status", index)
        mutator.setdefault_value(item, "visibility", "institution", "default institutional visibility", index)
        mutator.setdefault_value(item, "sortorder", index * 10, "stable default sort order", index)
        mutator.setdefault_value(item, "description", item.get("summary", ""), "default description", index)

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "pathways":
            mutator.set_path(item, ["metadata", "source_preset"], "pathways", "record source preset", index)


def normalize_competencies(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "competencies", mutator)

    framework_idnumber = None
    framework_id = None

    for item in data["items"]:
        if isinstance(item, dict) and item.get("object_type") == "competency_framework":
            framework_idnumber = item.get("idnumber") or framework_idnumber
            framework_id = item.get("id") or framework_id
            break

    framework_idnumber = framework_idnumber or "UCKK-COMP-FRAMEWORK"
    framework_id = framework_id or "competency_framework:uckk-academic"

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"competencies.json item {index}: skipped non-object item")
            continue

        source = first_present(item, "idnumber", "shortname", "title", default=f"competency_{index}")
        mutator.setdefault_value(item, "key", normalize_key(str(source)), "derive stable key", index)
        mutator.setdefault_value(
            item,
            "name",
            first_present(item, "title", "fullname", "short_title", default=item["key"]),
            "derive display name",
            index,
        )
        mutator.setdefault_value(
            item,
            "shortname",
            first_present(item, "short_title", "name", default=item["key"]),
            "derive shortname",
            index,
        )
        mutator.setdefault_value(
            item,
            "fullname",
            first_present(item, "title", "name", default=item["shortname"]),
            "derive fullname",
            index,
        )

        normalized_visible = visible_int(item.get("visible"), default=1)
        if item.get("visible") != normalized_visible:
            mutator.set_value(item, "visible", normalized_visible, "normalize visible to Moodle int 0/1", index)

        mutator.setdefault_value(item, "descriptionformat", 1, "default Moodle descriptionformat", index)

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "competencies":
            mutator.set_path(item, ["metadata", "source_preset"], "competencies", "record source preset", index)

        if item.get("object_type") == "competency_framework":
            continue

        mutator.setdefault_value(
            item,
            "framework",
            item.get("framework_id") or framework_id,
            "add canonical framework alias",
            index,
        )
        mutator.setdefault_value(
            item,
            "framework_id",
            item["framework"],
            "keep compatibility framework_id alias",
            index,
        )
        mutator.setdefault_value(
            item,
            "parent",
            item.get("parent_idnumber") or framework_idnumber,
            "add canonical parent alias",
            index,
        )
        mutator.setdefault_value(
            item,
            "parent_idnumber",
            item["parent"],
            "keep compatibility parent_idnumber alias",
            index,
        )
        mutator.set_path(
            item,
            ["metadata", "framework_idnumber"],
            framework_idnumber,
            "record resolved framework idnumber",
            index,
        )


def normalize_badges(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "badges", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"badges.json item {index}: skipped non-object item")
            continue

        source = first_present(item, "idnumber", "id", "title", "name", default=f"badge_{index}")
        mutator.setdefault_value(item, "key", normalize_key(str(source)), "derive stable key", index)
        mutator.setdefault_value(
            item,
            "name",
            first_present(item, "title", "short_title", default=item["key"]),
            "derive display name",
            index,
        )

        if "description" not in item or item.get("description") in (None, ""):
            recognition = item.get("recognition")
            if isinstance(recognition, dict):
                description = recognition.get("public_status_notice") or recognition.get("title") or item["name"]
            else:
                description = item["name"]
            mutator.setdefault_value(item, "description", description, "derive badge description", index)

        if "idnumber" not in item or item.get("idnumber") in (None, ""):
            derived = str(first_present(item, "key", "id", default=f"badge_{index}")).upper().replace(":", "-")
            mutator.setdefault_value(item, "idnumber", derived, "derive badge idnumber", index)

        mutator.setdefault_value(item, "type", item.get("badge_type") or "parchemin_uckk", "add canonical type alias", index)

        if "criteria" not in item:
            mutator.setdefault_value(
                item,
                "criteria",
                copy.deepcopy(item.get("award_criteria", [])),
                "add criteria alias from award_criteria",
                index,
            )

        if "award_criteria" not in item:
            mutator.setdefault_value(
                item,
                "award_criteria",
                copy.deepcopy(item.get("criteria", [])),
                "keep award_criteria compatibility alias",
                index,
            )

        if "competencies" not in item:
            mutator.setdefault_value(
                item,
                "competencies",
                copy.deepcopy(item.get("linked_competency_ids", [])),
                "add competencies alias from linked_competency_ids",
                index,
            )

        if "linked_competency_ids" not in item:
            mutator.setdefault_value(
                item,
                "linked_competency_ids",
                copy.deepcopy(item.get("competencies", [])),
                "keep linked_competency_ids compatibility alias",
                index,
            )

        normalized_visible = visible_int(item.get("visible"), default=1)
        if item.get("visible") != normalized_visible:
            mutator.set_value(item, "visible", normalized_visible, "normalize visible to Moodle int 0/1", index)

        normalized_enabled = visible_int(item.get("enabled"), default=1)
        if item.get("enabled") != normalized_enabled:
            mutator.set_value(item, "enabled", normalized_enabled, "normalize enabled to int 0/1", index)

        mutator.setdefault_value(item, "requiredarchive", True, "badges require archived evidence by default", index)
        mutator.setdefault_value(item, "requireshumanvalidation", True, "badges require human validation by default", index)

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "badges":
            mutator.set_path(item, ["metadata", "source_preset"], "badges", "record source preset", index)


def normalize_roles(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "roles", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"roles.json item {index}: skipped non-object item")
            continue

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "roles":
            mutator.set_path(item, ["metadata", "source_preset"], "roles", "record source preset", index)

        capabilities = item.get("capabilities", [])
        if not isinstance(capabilities, list):
            mutator.set_value(item, "capabilities", [], "roles.capabilities must be an array", index)
            continue

        role_label = mutator.item_label(item, index)

        for cap_index, cap in enumerate(capabilities, start=1):
            if not isinstance(cap, dict):
                mutator.result.warnings.append(f"roles.json {role_label}: skipped non-object capability #{cap_index}")
                continue

            capability = cap.get("capability", "")
            if "component" not in cap or not cap["component"]:
                before = cap.get("component")
                cap["component"] = capability_component(capability)
                mutator.record(
                    role_label,
                    f"capabilities[{cap_index}].component",
                    before,
                    cap["component"],
                    "derive component from capability name",
                )

            if "permission" in cap and isinstance(cap["permission"], str):
                new_value = cap["permission"].strip().lower()
                if cap["permission"] != new_value:
                    before = cap["permission"]
                    cap["permission"] = new_value
                    mutator.record(role_label, f"capabilities[{cap_index}].permission", before, new_value, "normalize permission")

            if "context" in cap and isinstance(cap["context"], str):
                new_value = cap["context"].strip().lower()
                if cap["context"] != new_value:
                    before = cap["context"]
                    cap["context"] = new_value
                    mutator.record(role_label, f"capabilities[{cap_index}].context", before, new_value, "normalize context")


def normalize_capabilities(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "capabilities", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"capabilities.json item {index}: skipped non-object item")
            continue

        capability = item.get("capability", "")

        if "component" not in item or not item["component"]:
            mutator.setdefault_value(
                item,
                "component",
                capability_component(capability),
                "derive component from capability name",
                index,
            )

        if "permission" in item and isinstance(item["permission"], str):
            new_value = item["permission"].strip().lower()
            if item["permission"] != new_value:
                mutator.set_value(item, "permission", new_value, "normalize permission", index)

        if "context" in item and isinstance(item["context"], str):
            new_value = item["context"].strip().lower()
            if item["context"] != new_value:
                mutator.set_value(item, "context", new_value, "normalize context", index)


def normalize_reports(data: dict[str, Any], mutator: Mutator) -> None:
    ensure_envelope(data, "reports", mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"reports.json item {index}: skipped non-object item")
            continue

        key = item.get("key") or item.get("source") or f"report_{index}"
        if item.get("key") != key:
            mutator.set_value(item, "key", key, "derive report key", index)

        mutator.setdefault_value(item, "name", str(key).replace("_", " ").title(), "derive report name", index)
        mutator.setdefault_value(item, "component", "report_uckk", "default report component", index)

        if "capability" not in item or not item["capability"]:
            metadata = item.get("metadata") if isinstance(item.get("metadata"), dict) else {}
            capability = (
                metadata.get("capability")
                or metadata.get("viewcapability")
                or REPORT_CAPABILITY_DEFAULTS.get(str(key))
                or "report/uckk:view"
            )
            mutator.setdefault_value(item, "capability", capability, "repair top-level report capability", index)

        if not isinstance(item.get("enabled"), bool):
            mutator.set_value(item, "enabled", bool(item.get("enabled", True)), "normalize enabled to boolean", index)

        mutator.setdefault_value(item, "source", key, "default report source", index)

        metadata = mutator.ensure_metadata(item, index)
        mutator.setdefault_value(item, "visibility", metadata.get("defaultvisibility", "institution"), "default report visibility", index)
        mutator.setdefault_value(
            item,
            "sortorder",
            int(metadata.get("sortorder", index * 10)),
            "stable default sort order",
            index,
        )

        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != "reports":
            mutator.set_path(item, ["metadata", "source_preset"], "reports", "record source preset", index)


def normalize_templates(data: dict[str, Any], preset: str, expected_component: str | None, mutator: Mutator) -> None:
    ensure_envelope(data, preset, mutator)

    for index, item in enumerate(data["items"], start=1):
        if not isinstance(item, dict):
            mutator.result.warnings.append(f"{preset}.json item {index}: skipped non-object item")
            continue

        if expected_component and ("component" not in item or not item["component"]):
            mutator.setdefault_value(item, "component", expected_component, "set expected template component", index)

        mutator.setdefault_value(item, "defaults", {}, "templates need defaults object", index)
        mutator.setdefault_value(item, "sections", [], "templates need sections array", index)
        mutator.setdefault_value(item, "activities", [], "templates need activities array", index)
        mutator.setdefault_value(item, "completion", {}, "templates need completion object", index)

        defaults = item.get("defaults")
        if isinstance(defaults, dict):
            if "visible" in defaults:
                normalized = visible_int(defaults["visible"], default=1)
                if defaults["visible"] != normalized:
                    before = defaults["visible"]
                    defaults["visible"] = normalized
                    mutator.record(
                        mutator.item_label(item, index),
                        "defaults.visible",
                        before,
                        normalized,
                        "normalize defaults.visible to Moodle int 0/1",
                    )
            elif defaults.get("visibility") in {"course", "institution", "public"}:
                before = defaults.get("visible")
                defaults["visible"] = 1
                mutator.record(
                    mutator.item_label(item, index),
                    "defaults.visible",
                    before,
                    1,
                    "derive visible from defaults.visibility",
                )

        metadata = mutator.ensure_metadata(item, index)
        if metadata.get("seeded_by") != "tool_uckkseed":
            mutator.set_path(item, ["metadata", "seeded_by"], "tool_uckkseed", "record seeder owner", index)
        if metadata.get("source_preset") != preset:
            mutator.set_path(item, ["metadata", "source_preset"], preset, "record source preset", index)
        if metadata.get("template_only") is not True:
            mutator.set_path(item, ["metadata", "template_only"], True, "mark preset as template-only, not course_seed payload", index)


def validate_uniqueness(data: dict[str, Any], filename: str) -> list[str]:
    errors: list[str] = []
    preset = str(data.get("preset", ""))
    fields = UNIQUE_FIELDS_BY_PRESET.get(preset, [])
    seen: dict[str, set[str]] = {field: set() for field in fields}

    for index, item in enumerate(data.get("items", []), start=1):
        if not isinstance(item, dict):
            continue

        for field in fields:
            value = item.get(field)
            if value in (None, ""):
                errors.append(f"{filename}: item {index} missing required unique field {field}")
                continue

            value_s = str(value)
            if value_s in seen[field]:
                errors.append(f"{filename}: duplicate {field}={value_s}")
            seen[field].add(value_s)

    return errors


def validate_required_fields(data: dict[str, Any], filename: str) -> list[str]:
    errors: list[str] = []
    preset = str(data.get("preset", ""))
    required = REQUIRED_FIELDS_BY_PRESET.get(preset, [])

    for index, item in enumerate(data.get("items", []), start=1):
        if not isinstance(item, dict):
            errors.append(f"{filename}: item {index} is not an object")
            continue

        item_label = str(first_present(item, "key", "shortname", "idnumber", "id", default=f"item#{index}"))

        for field in required:
            if field not in item or item[field] in (None, ""):
                errors.append(f"{filename}: {item_label} missing {field}")

        if preset == "roles":
            capabilities = item.get("capabilities", [])
            if not isinstance(capabilities, list):
                errors.append(f"{filename}: {item_label} capabilities is not an array")
                continue

            for cap_index, cap in enumerate(capabilities, start=1):
                if not isinstance(cap, dict):
                    errors.append(f"{filename}: {item_label} capability #{cap_index} is not an object")
                    continue

                for field in ["capability", "permission", "context", "component"]:
                    if field not in cap or cap[field] in (None, ""):
                        errors.append(f"{filename}: {item_label} capability #{cap_index} missing {field}")

    return errors


def ensure_list(value: Any) -> list[Any]:
    if value is None:
        return []
    if isinstance(value, list):
        return value
    return [value]


def collect_refs(items: Iterable[Any], fields: list[str]) -> set[str]:
    refs: set[str] = set()

    for item in items:
        if not isinstance(item, dict):
            continue

        for field in fields:
            value = item.get(field)
            if value not in (None, ""):
                refs.add(str(value))

    return refs


def validate_cross_refs(all_data: dict[str, dict[str, Any]]) -> list[str]:
    warnings: list[str] = []

    programs = all_data.get("programs.json", {}).get("items", [])
    pathways = all_data.get("pathways.json", {}).get("items", [])
    courses = all_data.get("courses.json", {}).get("items", [])
    competencies = all_data.get("competencies.json", {}).get("items", [])
    badges = all_data.get("badges.json", {}).get("items", [])

    program_ids = collect_refs(programs, ["id", "key", "idnumber", "shortname", "code"])
    pathway_ids = collect_refs(pathways, ["id", "key", "idnumber", "shortname"])
    course_ids = collect_refs(courses, ["id", "key", "idnumber", "shortname", "code"])
    competency_ids = collect_refs(competencies, ["id", "key", "idnumber", "shortname"])
    badge_ids = collect_refs(badges, ["id", "key", "idnumber", "shortname"])

    for badge in badges:
        if not isinstance(badge, dict):
            continue

        label = str(first_present(badge, "key", "idnumber", "id", default="<badge>"))

        program_id = badge.get("program_id")
        if program_id and program_id not in program_ids:
            warnings.append(f"badges.json: {label} references unknown program_id={program_id}")

        pathway_id = badge.get("pathway_id")
        if pathway_id and pathway_id not in pathway_ids:
            warnings.append(f"badges.json: {label} references unknown pathway_id={pathway_id}")

        for comp_ref in ensure_list(badge.get("competencies")) + ensure_list(badge.get("linked_competency_ids")):
            if comp_ref and comp_ref not in competency_ids:
                warnings.append(f"badges.json: {label} references unknown competency={comp_ref}")

        linked_course_ids = badge.get("linked_course_ids")
        if isinstance(linked_course_ids, dict):
            for group, refs in linked_course_ids.items():
                for course_ref in ensure_list(refs):
                    if course_ref and course_ref not in course_ids:
                        warnings.append(f"badges.json: {label} group {group} references unknown course={course_ref}")
        elif isinstance(linked_course_ids, list):
            for course_ref in linked_course_ids:
                if course_ref and course_ref not in course_ids:
                    warnings.append(f"badges.json: {label} references unknown course={course_ref}")

    for pathway in pathways:
        if not isinstance(pathway, dict):
            continue

        label = str(first_present(pathway, "key", "idnumber", "id", default="<pathway>"))

        program_id = pathway.get("program_id")
        if program_id and program_id not in program_ids:
            warnings.append(f"pathways.json: {label} references unknown program_id={program_id}")

        for badge_ref in ensure_list(pathway.get("badge_refs")):
            if badge_ref and badge_ref not in badge_ids:
                warnings.append(f"pathways.json: {label} references unknown badge={badge_ref}")

        for comp_ref in ensure_list(pathway.get("competency_refs")):
            if comp_ref and comp_ref not in competency_ids:
                warnings.append(f"pathways.json: {label} references unknown competency={comp_ref}")

        cycles = pathway.get("cycles")
        if isinstance(cycles, list):
            for cycle_index, cycle in enumerate(cycles, start=1):
                if not isinstance(cycle, dict):
                    continue

                for course_ref in ensure_list(cycle.get("course_refs")):
                    if isinstance(course_ref, dict):
                        ref = course_ref.get("course_id") or course_ref.get("course_code") or course_ref.get("idnumber")
                    else:
                        ref = course_ref

                    if ref and ref not in course_ids:
                        warnings.append(f"pathways.json: {label} cycle #{cycle_index} references unknown course={ref}")

    return sorted(set(warnings))


def normalize_file_data(filename: str, data: dict[str, Any], result: FileResult, logger: Logger) -> dict[str, Any]:
    mutator = Mutator(filename, result, logger)
    preset = filename[:-5]

    logger.verbose_info(f"  Normalizing preset={preset}")

    if filename == "courses.json":
        normalize_courses(data, mutator)
    elif filename == "programs.json":
        normalize_programs(data, mutator)
    elif filename == "pathways.json":
        normalize_pathways(data, mutator)
    elif filename == "competencies.json":
        normalize_competencies(data, mutator)
    elif filename == "badges.json":
        normalize_badges(data, mutator)
    elif filename == "roles.json":
        normalize_roles(data, mutator)
    elif filename == "capabilities.json":
        normalize_capabilities(data, mutator)
    elif filename == "reports.json":
        normalize_reports(data, mutator)
    elif filename in TEMPLATE_FILES:
        expected = TEMPLATE_EXPECTED_COMPONENTS.get(preset)
        normalize_templates(data, preset, expected, mutator)
    else:
        ensure_envelope(data, preset, mutator)

    result.preset = str(data.get("preset"))
    result.item_count = len(data.get("items", [])) if isinstance(data.get("items"), list) else 0
    result.errors.extend(validate_uniqueness(data, filename))
    result.errors.extend(validate_required_fields(data, filename))

    return data


def process_file(
    path: Path,
    dry_run: bool,
    show_diff: bool,
    logger: Logger,
) -> tuple[FileResult, dict[str, Any] | None]:
    result = FileResult(
        filename=path.name,
        path=str(path),
        preset=None,
        exists=path.exists(),
    )

    if not path.exists():
        result.warnings.append(f"{path.name}: file missing; skipped")
        return result, None

    logger.verbose_info(f"\nFILE {path.name}")
    logger.verbose_info(f"  Path: {path}")

    try:
        original_text = path.read_text(encoding="utf-8-sig")
        data = load_json(path)
    except Exception as exc:
        result.errors.append(f"{path.name}: failed to read/parse JSON: {exc}")
        return result, None

    before_for_compare = json.dumps(data, ensure_ascii=False, sort_keys=True)
    normalized_data = normalize_file_data(path.name, data, result, logger)
    after_for_compare = json.dumps(normalized_data, ensure_ascii=False, sort_keys=True)

    result.changed = before_for_compare != after_for_compare
    result.change_count = len(result.changes)

    if show_diff and result.changed:
        normalized_text = canonical_json_text(normalized_data)
        diff_lines = difflib.unified_diff(
            original_text.splitlines(),
            normalized_text.splitlines(),
            fromfile=f"{path.name} original",
            tofile=f"{path.name} normalized",
            lineterm="",
        )
        result.diff = "\n".join(diff_lines)

    if result.changed and not dry_run:
        try:
            backup_path = backup(path)
            save_json(path, normalized_data)
            result.backup_path = str(backup_path)
        except Exception as exc:
            result.errors.append(f"{path.name}: failed to write normalized file: {exc}")

    return result, normalized_data


def result_to_dict(result: FileResult, include_changes: bool = True, include_diff: bool = False) -> dict[str, Any]:
    payload = dataclasses.asdict(result)

    if not include_changes:
        payload["changes"] = []

    if not include_diff:
        payload["diff"] = None

    return payload


def write_report_json(report: RunReport, path: Path, include_changes: bool, include_diff: bool) -> None:
    payload = dataclasses.asdict(report)
    payload["results"] = [
        result_to_dict(result, include_changes=include_changes, include_diff=include_diff)
        for result in report.results
    ]

    with path.open("w", encoding="utf-8", newline="\n") as handle:
        json.dump(payload, handle, ensure_ascii=False, indent=2)
        handle.write("\n")


def print_summary(report: RunReport, logger: Logger, show_changes: bool) -> None:
    logger.info("")
    logger.info("SUMMARY")
    logger.info("=======")
    logger.info(f"Registry:       {report.registry}")
    logger.info(f"Dry run:        {'yes' if report.dry_run else 'no'}")
    logger.info(f"Files seen:     {report.files_seen}")
    logger.info(f"Files changed:  {report.files_changed}")
    logger.info(f"Files missing:  {report.files_missing}")
    logger.info(f"Total changes:  {report.total_changes}")
    logger.info(f"Warnings:       {report.total_warnings}")
    logger.info(f"Errors:         {report.total_errors}")

    logger.info("")
    logger.info("PER-FILE STATUS")
    logger.info("===============")

    for result in report.results:
        if not result.exists:
            status = "MISSING"
        elif result.errors:
            status = "ERROR"
        elif result.changed and report.dry_run:
            status = "WOULD UPDATE"
        elif result.changed:
            status = "UPDATED"
        else:
            status = "OK"

        logger.info(
            f"{status:12} {result.filename:32} "
            f"items={result.item_count:<4} changes={result.change_count:<4} "
            f"warnings={len(result.warnings):<3} errors={len(result.errors):<3}"
        )

        if result.backup_path:
            logger.info(f"             backup: {result.backup_path}")

        if show_changes and result.changes:
            for change in result.changes:
                logger.info(
                    f"             - {change.item}.{change.field}: "
                    f"{short_repr(change.before)} -> {short_repr(change.after)} "
                    f"[{change.reason}]"
                )

        for warning in result.warnings:
            logger.info(f"             WARNING: {warning}")

        for error in result.errors:
            logger.info(f"             ERROR: {error}")

    if any(result.diff for result in report.results):
        logger.info("")
        logger.info("DIFFS")
        logger.info("=====")
        for result in report.results:
            if result.diff:
                logger.info("")
                logger.info(result.diff)

    logger.info("")
    if report.dry_run:
        logger.info("Dry run only: no files were written and no .bak files were created.")
    else:
        logger.info("Write mode: changed files were backed up and overwritten with normalized JSON.")

    logger.info("")
    logger.info("Recommended next commands:")
    logger.info("  php admin\\cli\\purge_caches.php")
    logger.info("  php admin\\cli\\upgrade.php --non-interactive")
    logger.info("  cd public")
    logger.info("  php admin\\tool\\uckkseed\\cli\\validate.php --presetpath=academic_registry_json")


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Verbose fixer/normalizer for UCKK academic_registry_json presets."
    )
    parser.add_argument(
        "registry",
        nargs="?",
        default="academic_registry_json",
        help="Path to academic_registry_json folder.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Normalize in memory and report changes without writing files or creating backups.",
    )
    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Print per-file progress and summary details.",
    )
    parser.add_argument(
        "--very-verbose",
        action="store_true",
        help="Print field-level changes while processing.",
    )
    parser.add_argument(
        "--show-changes",
        action="store_true",
        help="Print every recorded change in the final summary.",
    )
    parser.add_argument(
        "--show-diff",
        action="store_true",
        help="Print unified diffs for changed files. Best used with --dry-run.",
    )
    parser.add_argument(
        "--report-json",
        default=None,
        help="Optional path to write a machine-readable JSON report.",
    )
    parser.add_argument(
        "--report-include-changes",
        action="store_true",
        help="Include field-level changes in --report-json output.",
    )
    parser.add_argument(
        "--report-include-diff",
        action="store_true",
        help="Include unified diffs in --report-json output. Can be large.",
    )
    parser.add_argument(
        "--files",
        nargs="*",
        default=None,
        help="Optional subset of JSON filenames to process.",
    )
    parser.add_argument(
        "--quiet",
        action="store_true",
        help="Suppress console output except fatal argument errors.",
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Return non-zero if warnings remain. Errors always return non-zero.",
    )

    args = parser.parse_args()

    logger = Logger(
        verbose=args.verbose,
        very_verbose=args.very_verbose,
        quiet=args.quiet,
    )

    registry = Path(args.registry).resolve()

    started_at = datetime.now().isoformat(timespec="seconds")

    report = RunReport(
        registry=str(registry),
        dry_run=bool(args.dry_run),
        started_at=started_at,
    )

    if not registry.exists() or not registry.is_dir():
        logger.error(f"registry folder not found: {registry}")
        return 2

    filenames = args.files if args.files else DEFAULT_FILES
    filenames = [name if name.endswith(".json") else f"{name}.json" for name in filenames]

    logger.info("UCKK academic_registry_json fixer")
    logger.info("================================")
    logger.info(f"Registry: {registry}")
    logger.info(f"Mode:     {'DRY RUN' if args.dry_run else 'WRITE'}")
    logger.info(f"Verbose:  {'very' if args.very_verbose else 'yes' if args.verbose else 'no'}")

    all_normalized_data: dict[str, dict[str, Any]] = {}

    for filename in filenames:
        path = registry / filename
        result, normalized_data = process_file(
            path=path,
            dry_run=bool(args.dry_run),
            show_diff=bool(args.show_diff),
            logger=logger,
        )

        report.results.append(result)

        if result.exists:
            report.files_seen += 1
        else:
            report.files_missing += 1

        if result.changed:
            report.files_changed += 1

        report.total_changes += result.change_count
        report.total_warnings += len(result.warnings)
        report.total_errors += len(result.errors)

        if normalized_data is not None:
            all_normalized_data[filename] = normalized_data

        if not args.verbose and not args.very_verbose and not args.quiet:
            if not result.exists:
                print(f"MISSING      {filename}")
            elif result.errors:
                print(f"ERROR        {filename} errors={len(result.errors)}")
            elif result.changed and args.dry_run:
                print(f"WOULD UPDATE {filename} changes={result.change_count}")
            elif result.changed:
                print(f"UPDATED      {filename} changes={result.change_count}")
            else:
                print(f"OK           {filename}")

    cross_ref_warnings = validate_cross_refs(all_normalized_data)
    if cross_ref_warnings:
        cross_result = FileResult(
            filename="<cross-preset-validation>",
            path=str(registry),
            preset=None,
            exists=True,
            changed=False,
            item_count=0,
            change_count=0,
            warnings=cross_ref_warnings,
        )
        report.results.append(cross_result)
        report.total_warnings += len(cross_ref_warnings)

    report.finished_at = datetime.now().isoformat(timespec="seconds")

    print_summary(
        report=report,
        logger=logger,
        show_changes=bool(args.show_changes or args.very_verbose),
    )

    if args.report_json:
        report_path = Path(args.report_json).resolve()
        write_report_json(
            report,
            report_path,
            include_changes=bool(args.report_include_changes),
            include_diff=bool(args.report_include_diff),
        )
        logger.info("")
        logger.info(f"Report written: {report_path}")

    if report.total_errors:
        return 1

    if args.strict and report.total_warnings:
        return 1

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

