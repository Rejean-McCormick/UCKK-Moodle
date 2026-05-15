#!/usr/bin/env python3
"""
scan_moodle_strings.py

Inventorie les références de chaînes Moodle dans un dépôt/plugin suite, puis compare
ces références avec les définitions présentes dans lang/*/<component>.php.

Usage:
    python3 tools/scan_moodle_strings.py /path/to/moodle/repo
    python3 tools/scan_moodle_strings.py . --out var/string_inventory
    python3 tools/scan_moodle_strings.py . --include-dynamic

Sorties:
    string_references.csv       Toutes les références statiques trouvées.
    string_missing.csv          Références statiques sans définition lang trouvée.
    string_defined_unused.csv   Définitions lang non référencées par le scan.
    string_dynamic.csv          Références dynamiques/ambiguës à relire manuellement.
    string_definitions.csv      Inventaire des $string[...] définis.
    string_inventory.json       Données complètes.
    string_missing.md           Rapport lisible par composant.

Portée:
    - PHP: get_string('id', 'component'), new lang_string('id', 'component')
    - JS AMD: getString('id', 'component'), getStrings([{key: 'id', component: 'component'}])
    - Mustache: {{#str}} id, component {{/str}}
    - Définitions: lang/*/<component>.php avec $string['id'] = ...

Limites assumées:
    - Les chaînes construites dynamiquement sont listées séparément, non résolues.
    - Les appels get_string('id') sans composant sont rattachés au composant inféré
      depuis le chemin du fichier.
"""

from __future__ import annotations

import argparse
import ast
import csv
import json
import re
import sys
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Iterable


EXCLUDED_DIRS = {
    ".git", ".idea", ".vscode", "node_modules", "vendor", "moodledata",
    "coverage", "build", "dist", ".grunt", ".sass-cache", "__pycache__",
}

TEXT_EXTENSIONS = {
    ".php", ".js", ".mustache", ".html", ".xml", ".json", ".md", ".txt", ".feature",
}

SCAN_EXTENSIONS = {
    ".php", ".js", ".mustache", ".html", ".xml", ".feature",
}

LANG_FILE_RE = re.compile(r"(?:^|/)lang/([^/]+)/([a-zA-Z0-9_]+)\.php$")
PHP_STRING_DEF_RE = re.compile(
    r"""\$string\s*\[\s*(['"])(?P<key>(?:\\.|(?!\1).)+)\1\s*\]\s*=""",
    re.MULTILINE,
)

# PHP static calls.
PHP_GET_STRING_RE = re.compile(
    r"""(?P<fn>\bget_string|(?:\\?[a-zA-Z_][a-zA-Z0-9_]*\\)*lang_string)\s*
        \(\s*
        (?P<q1>['"])(?P<key>(?:\\.|(?!\2).)+)(?P=q1)
        (?:\s*,\s*(?P<q2>['"])(?P<component>(?:\\.|(?!\4).)+)(?P=q2))?
    """,
    re.VERBOSE | re.MULTILINE,
)

PHP_NEW_LANG_STRING_RE = re.compile(
    r"""new\s+(?:\\?[a-zA-Z_][a-zA-Z0-9_]*\\)*lang_string\s*
        \(\s*
        (?P<q1>['"])(?P<key>(?:\\.|(?!\1).)+)(?P=q1)
        (?:\s*,\s*(?P<q2>['"])(?P<component>(?:\\.|(?!\3).)+)(?P=q2))?
    """,
    re.VERBOSE | re.MULTILINE,
)

PHP_DYNAMIC_GET_STRING_RE = re.compile(
    r"""\bget_string\s*\(\s*(?!['"])""",
    re.MULTILINE,
)

# JS static calls.
JS_GET_STRING_RE = re.compile(
    r"""(?P<fn>\bgetString|\bget_string|(?:Str|str)\.get_string|(?:Str|str)\.getString)\s*
        \(\s*
        (?P<q1>['"`])(?P<key>(?:\\.|(?!\2).)+)(?P=q1)
        (?:\s*,\s*(?P<q2>['"`])(?P<component>(?:\\.|(?!\4).)+)(?P=q2))?
    """,
    re.VERBOSE | re.MULTILINE,
)

JS_GET_STRINGS_OBJECT_RE = re.compile(
    r"""\{\s*
        (?:(?:key|string|identifier)\s*:\s*(?P<q1>['"`])(?P<key>(?:\\.|(?!\1).)+)(?P=q1)\s*,\s*
           component\s*:\s*(?P<q2>['"`])(?P<component>(?:\\.|(?!\3).)+)(?P=q2)
        |
           component\s*:\s*(?P<q3>['"`])(?P<component2>(?:\\.|(?!\4).)+)(?P=q3)\s*,\s*
           (?:key|string|identifier)\s*:\s*(?P<q4>['"`])(?P<key2>(?:\\.|(?!\6).)+)(?P=q4)
        )
        [^}]*\}""",
    re.VERBOSE | re.MULTILINE | re.DOTALL,
)

JS_DYNAMIC_GET_STRING_RE = re.compile(
    r"""\bgetString\s*\(\s*(?!['"`])""",
    re.MULTILINE,
)

# Mustache Moodle str helper:
# {{#str}} identifier, component {{/str}}
# {{# str }} identifier, component, value {{/ str }}
MUSTACHE_STR_RE = re.compile(
    r"""\{\{\s*#\s*str\s*\}\}
        (?P<body>.*?)
        \{\{\s*/\s*str\s*\}\}""",
    re.VERBOSE | re.DOTALL,
)


@dataclass(frozen=True)
class Definition:
    component: str
    key: str
    lang: str
    path: str
    line: int


@dataclass(frozen=True)
class Reference:
    component: str
    key: str
    path: str
    line: int
    kind: str
    raw: str
    component_inferred: bool = False


@dataclass(frozen=True)
class DynamicReference:
    path: str
    line: int
    kind: str
    raw: str
    reason: str


def normalise_path(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def is_text_file(path: Path) -> bool:
    return path.suffix.lower() in TEXT_EXTENSIONS


def should_skip(path: Path) -> bool:
    return any(part in EXCLUDED_DIRS for part in path.parts)


def read_text(path: Path) -> str:
    data = path.read_bytes()
    if b"\x00" in data[:2000]:
        # Common for corrupted UTF-16-like files. Keep a best-effort text view.
        try:
            return data.decode("utf-16", errors="replace")
        except UnicodeError:
            return data.replace(b"\x00", b"").decode("utf-8", errors="replace")
    return data.decode("utf-8", errors="replace")


def line_number(text: str, pos: int) -> int:
    return text.count("\n", 0, pos) + 1


def unescape_php_string(value: str) -> str:
    # Good enough for Moodle string identifiers/components.
    try:
        return ast.literal_eval("'" + value.replace("'", "\\'") + "'")
    except Exception:
        return value.replace("\\'", "'").replace('\\"', '"').replace("\\\\", "\\")


def infer_component_from_path(relpath: str) -> str | None:
    parts = relpath.split("/")
    if len(parts) >= 2 and parts[0] == "local":
        return f"local_{parts[1]}"
    if len(parts) >= 2 and parts[0] == "mod":
        return f"mod_{parts[1]}"
    if len(parts) >= 2 and parts[0] == "blocks":
        return f"block_{parts[1]}"
    if len(parts) >= 3 and parts[0] == "admin" and parts[1] == "tool":
        return f"tool_{parts[2]}"
    if len(parts) >= 3 and parts[0] == "course" and parts[1] == "format":
        return f"format_{parts[2]}"
    if len(parts) >= 2 and parts[0] == "theme":
        return f"theme_{parts[1]}"
    if len(parts) >= 2 and parts[0] == "report":
        return f"report_{parts[1]}"
    if len(parts) >= 3 and parts[0] == "ai" and parts[1] == "provider":
        return f"aiprovider_{parts[2]}"
    return None


def parse_definitions(path: Path, root: Path, text: str) -> list[Definition]:
    rel = normalise_path(path, root)
    match = LANG_FILE_RE.search(rel)
    if not match:
        return []
    lang = match.group(1)
    component = match.group(2)
    out: list[Definition] = []
    for m in PHP_STRING_DEF_RE.finditer(text):
        out.append(Definition(
            component=component,
            key=unescape_php_string(m.group("key")),
            lang=lang,
            path=rel,
            line=line_number(text, m.start()),
        ))
    return out


def make_ref(
    root: Path,
    path: Path,
    text: str,
    match: re.Match,
    kind: str,
    component: str | None,
    key: str,
    raw: str | None = None,
) -> Reference | DynamicReference:
    rel = normalise_path(path, root)
    inferred = False
    if not component:
        component = infer_component_from_path(rel)
        inferred = True
    if not component:
        return DynamicReference(
            path=rel,
            line=line_number(text, match.start()),
            kind=kind,
            raw=(raw if raw is not None else match.group(0)).strip().replace("\n", " "),
            reason="component_absent_and_not_inferable",
        )
    return Reference(
        component=unescape_php_string(component),
        key=unescape_php_string(key),
        path=rel,
        line=line_number(text, match.start()),
        kind=kind,
        raw=(raw if raw is not None else match.group(0)).strip().replace("\n", " "),
        component_inferred=inferred,
    )


def parse_php_refs(path: Path, root: Path, text: str) -> tuple[list[Reference], list[DynamicReference]]:
    refs: list[Reference] = []
    dyn: list[DynamicReference] = []

    for rx, kind in ((PHP_GET_STRING_RE, "php_get_string"), (PHP_NEW_LANG_STRING_RE, "php_lang_string")):
        for m in rx.finditer(text):
            ref = make_ref(
                root=root,
                path=path,
                text=text,
                match=m,
                kind=kind,
                component=m.groupdict().get("component"),
                key=m.group("key"),
            )
            if isinstance(ref, Reference):
                refs.append(ref)
            else:
                dyn.append(ref)

    for m in PHP_DYNAMIC_GET_STRING_RE.finditer(text):
        dyn.append(DynamicReference(
            path=normalise_path(path, root),
            line=line_number(text, m.start()),
            kind="php_get_string_dynamic",
            raw=text[m.start(): text.find(")", m.start()) + 1].strip().replace("\n", " ")[:240],
            reason="dynamic_identifier_or_expression",
        ))

    return refs, dyn


def parse_js_refs(path: Path, root: Path, text: str) -> tuple[list[Reference], list[DynamicReference]]:
    refs: list[Reference] = []
    dyn: list[DynamicReference] = []

    for m in JS_GET_STRING_RE.finditer(text):
        ref = make_ref(
            root=root,
            path=path,
            text=text,
            match=m,
            kind="js_get_string",
            component=m.groupdict().get("component"),
            key=m.group("key"),
        )
        if isinstance(ref, Reference):
            refs.append(ref)
        else:
            dyn.append(ref)

    for m in JS_GET_STRINGS_OBJECT_RE.finditer(text):
        key = m.groupdict().get("key") or m.groupdict().get("key2")
        component = m.groupdict().get("component") or m.groupdict().get("component2")
        if key and component:
            ref = make_ref(
                root=root,
                path=path,
                text=text,
                match=m,
                kind="js_get_strings_object",
                component=component,
                key=key,
            )
            if isinstance(ref, Reference):
                refs.append(ref)
            else:
                dyn.append(ref)

    for m in JS_DYNAMIC_GET_STRING_RE.finditer(text):
        dyn.append(DynamicReference(
            path=normalise_path(path, root),
            line=line_number(text, m.start()),
            kind="js_get_string_dynamic",
            raw=text[m.start(): text.find(")", m.start()) + 1].strip().replace("\n", " ")[:240],
            reason="dynamic_identifier_or_expression",
        ))

    return refs, dyn


def parse_mustache_refs(path: Path, root: Path, text: str) -> tuple[list[Reference], list[DynamicReference]]:
    refs: list[Reference] = []
    dyn: list[DynamicReference] = []

    for m in MUSTACHE_STR_RE.finditer(text):
        body = " ".join(m.group("body").strip().split())
        parts = [p.strip() for p in body.split(",")]
        if not parts or not parts[0]:
            continue
        key = parts[0]
        component = parts[1] if len(parts) > 1 and parts[1] else None
        # Ignore variables in the string helper; log them as dynamic.
        if key.startswith("{{") or (component and component.startswith("{{")):
            dyn.append(DynamicReference(
                path=normalise_path(path, root),
                line=line_number(text, m.start()),
                kind="mustache_str_dynamic",
                raw=m.group(0).strip().replace("\n", " ")[:240],
                reason="dynamic_mustache_string_key_or_component",
            ))
            continue

        ref = make_ref(
            root=root,
            path=path,
            text=text,
            match=m,
            kind="mustache_str",
            component=component,
            key=key,
            raw=m.group(0),
        )
        if isinstance(ref, Reference):
            refs.append(ref)
        else:
            dyn.append(ref)

    return refs, dyn


def iter_files(root: Path) -> Iterable[Path]:
    for path in root.rglob("*"):
        if path.is_file() and not should_skip(path) and is_text_file(path):
            yield path


def write_csv(path: Path, rows: list[dict], fields: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fields, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)


def main() -> int:
    parser = argparse.ArgumentParser(description="Scan Moodle string references and lang definitions.")
    parser.add_argument("root", nargs="?", default=".", help="Repository root.")
    parser.add_argument("--out", default="string_inventory", help="Output directory.")
    parser.add_argument(
        "--include-dynamic",
        action="store_true",
        help="Include dynamic references in the main JSON report. They are always written to CSV.",
    )
    args = parser.parse_args()

    root = Path(args.root).resolve()
    outdir = Path(args.out).resolve()

    if not root.exists():
        print(f"Root not found: {root}", file=sys.stderr)
        return 2

    definitions: list[Definition] = []
    references: list[Reference] = []
    dynamic: list[DynamicReference] = []

    for path in iter_files(root):
        rel = normalise_path(path, root)
        text = read_text(path)

        definitions.extend(parse_definitions(path, root, text))

        if path.suffix.lower() not in SCAN_EXTENSIONS:
            continue

        if path.suffix.lower() == ".php":
            refs, dyn = parse_php_refs(path, root, text)
        elif path.suffix.lower() == ".js":
            refs, dyn = parse_js_refs(path, root, text)
        elif path.suffix.lower() == ".mustache":
            refs, dyn = parse_mustache_refs(path, root, text)
        else:
            refs, dyn = [], []

        references.extend(refs)
        dynamic.extend(dyn)

    def_index = {(d.component, d.key) for d in definitions}
    ref_index = {(r.component, r.key) for r in references}

    missing = [r for r in references if (r.component, r.key) not in def_index]
    unused = [d for d in definitions if (d.component, d.key) not in ref_index]

    references_sorted = sorted(references, key=lambda r: (r.component, r.key, r.path, r.line))
    definitions_sorted = sorted(definitions, key=lambda d: (d.component, d.key, d.lang, d.path, d.line))
    missing_sorted = sorted(missing, key=lambda r: (r.component, r.key, r.path, r.line))
    unused_sorted = sorted(unused, key=lambda d: (d.component, d.key, d.lang, d.path, d.line))
    dynamic_sorted = sorted(dynamic, key=lambda d: (d.path, d.line, d.kind))

    write_csv(outdir / "string_references.csv", [asdict(r) for r in references_sorted], [
        "component", "key", "path", "line", "kind", "component_inferred", "raw",
    ])
    write_csv(outdir / "string_missing.csv", [asdict(r) for r in missing_sorted], [
        "component", "key", "path", "line", "kind", "component_inferred", "raw",
    ])
    write_csv(outdir / "string_definitions.csv", [asdict(d) for d in definitions_sorted], [
        "component", "key", "lang", "path", "line",
    ])
    write_csv(outdir / "string_defined_unused.csv", [asdict(d) for d in unused_sorted], [
        "component", "key", "lang", "path", "line",
    ])
    write_csv(outdir / "string_dynamic.csv", [asdict(d) for d in dynamic_sorted], [
        "path", "line", "kind", "raw", "reason",
    ])

    json_payload = {
        "root": str(root),
        "counts": {
            "definitions": len(definitions_sorted),
            "references": len(references_sorted),
            "missing": len(missing_sorted),
            "defined_unused": len(unused_sorted),
            "dynamic": len(dynamic_sorted),
        },
        "definitions": [asdict(d) for d in definitions_sorted],
        "references": [asdict(r) for r in references_sorted],
        "missing": [asdict(r) for r in missing_sorted],
        "defined_unused": [asdict(d) for d in unused_sorted],
    }
    if args.include_dynamic:
        json_payload["dynamic"] = [asdict(d) for d in dynamic_sorted]

    outdir.mkdir(parents=True, exist_ok=True)
    (outdir / "string_inventory.json").write_text(
        json.dumps(json_payload, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    # Markdown report grouped by component/key.
    lines: list[str] = []
    lines.append("# Moodle string inventory — missing strings")
    lines.append("")
    lines.append("## Summary")
    lines.append("")
    for name, value in json_payload["counts"].items():
        lines.append(f"- {name}: {value}")
    lines.append("")

    if not missing_sorted:
        lines.append("No missing static string definitions found.")
    else:
        by_component: dict[str, dict[str, list[Reference]]] = {}
        for ref in missing_sorted:
            by_component.setdefault(ref.component, {}).setdefault(ref.key, []).append(ref)

        for component in sorted(by_component):
            lines.append(f"## {component}")
            lines.append("")
            for key in sorted(by_component[component]):
                refs_for_key = by_component[component][key]
                lines.append(f"### `{key}`")
                lines.append("")
                for ref in refs_for_key:
                    inferred = " inferred-component" if ref.component_inferred else ""
                    lines.append(f"- `{ref.path}:{ref.line}` — `{ref.kind}`{inferred}")
                lines.append("")
                lines.append("Suggested placeholder:")
                lines.append("")
                lines.append("```php")
                lines.append(f"$string['{key}'] = '{key}';")
                lines.append("```")
                lines.append("")

    (outdir / "string_missing.md").write_text("\n".join(lines), encoding="utf-8")

    print("String inventory complete.")
    print(f"Root: {root}")
    print(f"Output: {outdir}")
    for name, value in json_payload["counts"].items():
        print(f"{name}: {value}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
