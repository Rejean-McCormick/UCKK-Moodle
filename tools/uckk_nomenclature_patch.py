#!/usr/bin/env python3
from pathlib import Path
import argparse
import shutil
import sys

EXCLUDED_DIRS = {
    ".git",
    "node_modules",
    "vendor",
    "string_inventory",
    "__pycache__",
}

INCLUDED_EXTENSIONS = {
    ".json",
    ".php",
    ".md",
    ".mustache",
    ".html",
    ".txt",
    ".yml",
    ".yaml",
    ".csv",
}

# Remplacements visibles seulement.
# On ne touche pas les identifiants techniques:
# magie-operable, magie_operable, MAGIE-OPERABLE, program:*magie-operable*, etc.
REPLACEMENTS = [
    # Extra pass: cohort/template/doc visible nomenclature.
    ("Cohorte du baccalauréat interne Grand Jeu social.", "Cohorte de la Voie du Grand Jeu social."),
    ("Cohorte du baccalauréat interne Architecture écosystème digital kOA.", "Cohorte de la Voie de l’Architecture de l’écosystème digital kOA."),
    ("Cohorte du baccalauréat interne Architecture sociotechnique.", "Cohorte de la Voie de l’Architecture sociotechnique."),
    ("Cohorte du baccalauréat interne Sciences politiques.", "Cohorte de la Voie des Sciences politiques."),
    ("Cohorte du baccalauréat interne Économie.", "Cohorte de la Voie de l’Économie."),
    ("Cohorte du baccalauréat interne Écologie.", "Cohorte de la Voie de l’Écologie."),
    ("Cohorte du baccalauréat interne Métaphysique.", "Cohorte de la Voie de la Métaphysique."),
    ("Cohorte du baccalauréat interne Production augmentée par l’IA.", "Cohorte de la Voie de la Production augmentée par l’IA."),
    ("Cohorte du baccalauréat interne Linguistique et architecture du sens.", "Cohorte de la Voie de la Linguistique et de l’architecture du sens."),
    ("Cohorte du baccalauréat interne Intervention sociale.", "Cohorte de la Voie de l’Intervention sociale et des systèmes humains."),

    ("Baccalauréat UCKK — Grand Jeu social", "Voie du Grand Jeu social"),
    ("Baccalauréat UCKK — Architecture écosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Baccalauréat UCKK — Architecture sociotechnique", "Voie de l’Architecture sociotechnique"),
    ("Baccalauréat UCKK — Sciences politiques", "Voie des Sciences politiques"),
    ("Baccalauréat UCKK — Économie", "Voie de l’Économie"),
    ("Baccalauréat UCKK — Écologie", "Voie de l’Écologie"),
    ("Baccalauréat UCKK — Métaphysique", "Voie de la Métaphysique"),
    ("Baccalauréat UCKK — Production IA", "Voie de la Production augmentée par l’IA"),
    ("Baccalauréat UCKK — Linguistique et architecture du sens", "Voie de la Linguistique et de l’architecture du sens"),
    ("Baccalauréat UCKK — Intervention sociale", "Voie de l’Intervention sociale et des systèmes humains"),

    ("Baccalauréat en Grand Jeu social", "Voie du Grand Jeu social"),
    ("Baccalauréat en Architecture de l’écosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Baccalauréat en Architecture écosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Baccalauréat en Architecture sociotechnique", "Voie de l’Architecture sociotechnique"),
    ("Baccalauréat en Sciences politiques", "Voie des Sciences politiques"),
    ("Baccalauréat en Économie", "Voie de l’Économie"),
    ("Baccalauréat en Écologie", "Voie de l’Écologie"),
    ("Baccalauréat en Métaphysique", "Voie de la Métaphysique"),
    ("Baccalauréat en Production augmentée par l’IA", "Voie de la Production augmentée par l’IA"),
    ("Baccalauréat en Linguistique et architecture du sens", "Voie de la Linguistique et de l’architecture du sens"),
    ("Baccalauréat en Intervention sociale et systèmes humains", "Voie de l’Intervention sociale et des systèmes humains"),

    ("Baccalauréat UCKK", "Voie UCKK"),
    # Voies publiques — formes longues à corriger en premier.
    ("Voie de Magie opérable en Architecture de l’écosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Voie de Magie operable en Architecture de l’ecosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Voie d’Architecture sociotechnique — Palier de Magie opérable", "Voie de l’Architecture sociotechnique"),
    ("Voie d'Économie — Palier de Magie opérable", "Voie de l’Économie"),
    ("Voie d’Économie — Palier de Magie opérable", "Voie de l’Économie"),
    ("Voie d'Écologie — Palier de Magie opérable", "Voie de l’Écologie"),
    ("Voie d’Écologie — Palier de Magie opérable", "Voie de l’Écologie"),
    ("Voie de la Production augmentée par l’IA — Palier de Magie opérable", "Voie de la Production augmentée par l’IA"),
    ("Voie de l’Intervention sociale et des systèmes humains — Palier de Magie opérable", "Voie de l’Intervention sociale et des systèmes humains"),
    ("Voie de Linguistique et architecture du sens — Palier de Magie opérable", "Voie de la Linguistique et de l’architecture du sens"),
    ("Voie de Métaphysique — Palier de Magie opérable", "Voie de la Métaphysique"),
    ("Voie des Sciences politiques — Palier de Magie opérable", "Voie des Sciences politiques"),
    ("Voie du Grand Jeu social — Palier de Magie opérable", "Voie du Grand Jeu social"),

    # Anciennes catégories publiques.
    ("Baccalauréat Grand Jeu social", "Voie du Grand Jeu social"),
    ("Baccalauréat Architecture écosystème digital kOA", "Voie de l’Architecture de l’écosystème digital kOA"),
    ("Baccalauréat Architecture sociotechnique", "Voie de l’Architecture sociotechnique"),
    ("Baccalauréat Sciences politiques", "Voie des Sciences politiques"),
    ("Baccalauréat Économie", "Voie de l’Économie"),
    ("Baccalauréat Écologie", "Voie de l’Écologie"),
    ("Baccalauréat Métaphysique", "Voie de la Métaphysique"),
    ("Baccalauréat Production augmentée par l’IA", "Voie de la Production augmentée par l’IA"),
    ("Baccalauréat Linguistique et architecture du sens", "Voie de la Linguistique et de l’architecture du sens"),
    ("Baccalauréat Intervention sociale et systèmes humains", "Voie de l’Intervention sociale et des systèmes humains"),
    ("Baccalauréat interne", "Voie UCKK — Niveau visé : Puissance opératoire"),

    # Parchemins et niveaux.
    ("Parchemin de Grande Archimagie", "Parchemin de Grande Archipuissance"),
    ("Parchemin d’Archimagie", "Parchemin d’Archipuissance"),
    ("Parchemin d'Archimagie", "Parchemin d'Archipuissance"),
    ("Parchemin de Magie opérable", "Parchemin de Puissance opératoire"),
    ("Niveau de Magie opérable", "Niveau de Puissance opératoire"),
    ("Palier de Magie opérable", "Niveau de Puissance opératoire"),

    # Termes généraux visibles.
    ("Grande Archimagie", "Grande Archipuissance"),
    ("Archimagie", "Archipuissance"),
    ("Magie opérable", "Puissance opératoire"),
    ("Paliers", "Niveaux"),
    ("Palier", "Niveau"),
]

TECHNICAL_PATTERNS_TO_REPORT = [
    "magie-operable",
    "magie_operable",
    "MAGIE-OPERABLE",
    "program:",
    "pathway:",
    "competency:",
    "badge:",
]

def should_skip(path: Path) -> bool:
    if any(part in EXCLUDED_DIRS for part in path.parts):
        return True
    if path.name.endswith(".bak"):
        return True
    return False

def iter_target_files(root: Path):
    for path in root.rglob("*"):
        if should_skip(path):
            continue
        if path.is_file() and path.suffix.lower() in INCLUDED_EXTENSIONS:
            yield path

def read_text(path: Path):
    try:
        return path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        try:
            return path.read_text(encoding="utf-8-sig")
        except UnicodeDecodeError:
            return None

def apply_replacements(text: str):
    changes = []
    for old, new in REPLACEMENTS:
        count = text.count(old)
        if count:
            text = text.replace(old, new)
            changes.append((old, new, count))
    return text, changes

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("root", nargs="?", default=".", help="Racine du repo uckk-moodle")
    parser.add_argument("--apply", action="store_true", help="Écrit les fichiers modifiés")
    parser.add_argument("--no-backup", action="store_true", help="Ne crée pas de .bak avant écriture")
    args = parser.parse_args()

    root = Path(args.root).resolve()
    if not root.exists():
        print(f"Racine introuvable: {root}", file=sys.stderr)
        sys.exit(1)

    total_files = 0
    total_replacements = 0

    print("MODE:", "APPLICATION" if args.apply else "SIMULATION")
    print("RACINE:", root)
    print()

    for path in iter_target_files(root):
        original = read_text(path)
        if original is None:
            continue

        updated, changes = apply_replacements(original)
        if not changes:
            continue

        total_files += 1
        file_total = sum(c for _, _, c in changes)
        total_replacements += file_total

        rel = path.relative_to(root)
        print(f"{rel}  ({file_total} remplacement(s))")
        for old, new, count in changes:
            print(f"  {count}x {old!r} -> {new!r}")

        if args.apply and updated != original:
            if not args.no_backup:
                backup = path.with_suffix(path.suffix + ".bak")
                if not backup.exists():
                    shutil.copy2(path, backup)
            path.write_text(updated, encoding="utf-8")

    print()
    print(f"Fichiers touchés: {total_files}")
    print(f"Remplacements: {total_replacements}")

    print()
    print("Vérification des identifiants techniques encore présents, à NE PAS remplacer globalement:")
    for pattern in TECHNICAL_PATTERNS_TO_REPORT:
        hits = []
        for path in iter_target_files(root):
            text = read_text(path)
            if text and pattern in text:
                hits.append(str(path.relative_to(root)))
        if hits:
            print(f"  {pattern}: {len(hits)} fichier(s)")

if __name__ == "__main__":
    main()
