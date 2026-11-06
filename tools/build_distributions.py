#!/usr/bin/env python3
"""Build two auditable, UNPUBLISHED ACF Editor candidate ZIPs from one source.

WordPress.org: its own slug/text domain, mandatory default-off consent, NO independent updater.
Independent: preserves existing GitHub plugin basename, isolated Hashe update
and default-off consent modules, and a namespaced Update URI.
No site, WordPress.org account, API endpoint or GitHub release is changed.
"""
from __future__ import annotations
import argparse
import hashlib
import re
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
ORG_SLUG = "hashe-field-editor"
GITHUB_SLUG = "hashe-elementor-acf-editor"
ENTRY = GITHUB_SLUG + ".php"
ALLOWED_FILES = (
    ENTRY,
    "readme.txt",
    "LICENSE",
    "assets/editor-native.js",
    "assets/editor-native.css",
    "includes/extended-fields.php",
)
EXTRA_FILES = (
    "distribution/independent/bootstrap.php",
    "distribution/independent/hashe-core/v1/update-client.php",
    "distribution/independent/hashe-core/v1/consent-client.php",
)

ORG_CONSENT_FILES = (
    "distribution/wordpress-org/consent-bootstrap.php",
    "distribution/independent/hashe-core/v1/consent-client.php",
)


def replace_once(text: str, old: str, new: str, context: str) -> str:
    if text.count(old) != 1:
        raise ValueError(f"{context}: expected exactly one {old!r}, got {text.count(old)}")
    return text.replace(old, new, 1)


def source_version() -> str:
    main = (ROOT / ENTRY).read_text(encoding="utf-8")
    match = re.search(r"^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$", main, re.M)
    if not match:
        raise ValueError("Missing numeric version in plugin header")
    version = match.group(1)
    if f"const VERSION = '{version}'" not in main:
        raise ValueError("Version constant differs from plugin header")
    if f"Stable tag: {version}" not in (ROOT / "readme.txt").read_text(encoding="utf-8"):
        raise ValueError("Readme stable tag differs from plugin header")
    for dep in (ROOT / ENTRY, ROOT / "readme.txt"):
        if "Requires Plugins: elementor, advanced-custom-fields" not in dep.read_text(encoding="utf-8"):
            raise ValueError(f"Missing WordPress dependencies in {dep}")
    return version


def contents(distribution: str) -> dict[str, bytes]:
    standalone = distribution == "independent"
    folder = GITHUB_SLUG if standalone else ORG_SLUG
    result = {}
    for relative in ALLOWED_FILES:
        path = ROOT / relative
        content = path.read_bytes()
        output_path = relative
        if relative.endswith(".php") or relative == "readme.txt":
            data = content.decode("utf-8")
            if not standalone:
                # WordPress.org owns its native updates. No Update URI or
                # custom update module may enter this package.
                if relative.endswith(".php"):
                    # Change gettext text-domain constants, not the GitHub
                    # source URL or unrelated persistent setting names.
                    data = data.replace("'" + GITHUB_SLUG + "'", "'" + ORG_SLUG + "'")
                    data = data.replace(
                        " * Text Domain: " + GITHUB_SLUG,
                        " * Text Domain: " + ORG_SLUG
                    )
                if relative == ENTRY:
                    output_path = ORG_SLUG + ".php"
                    data = replace_once(
                        data,
                        "require_once __DIR__ . '/includes/extended-fields.php';",
                        "require_once __DIR__ . '/includes/extended-fields.php';\n"
                        "require_once __DIR__ . '/includes/consent-bootstrap.php';",
                        "Mandatory WordPress.org consent bootstrap",
                    )
                if relative == "readme.txt":
                    data = data.replace(
                        "2. Download the versioned installable ZIP from https://github.com/HasheCS/"
                        + GITHUB_SLUG + "/releases (not the automatic source ZIP).",
                        "2. Install via WordPress.org Plugins > Add New once approved; "
                        "during review, install the directory candidate ZIP on staging."
                    )
            elif relative == ENTRY:
                data = replace_once(
                    data,
                    " * Requires Plugins: elementor, advanced-custom-fields",
                    " * Requires Plugins: elementor, advanced-custom-fields\n"
                    " * Update URI: https://plugins.hashe.com/" + GITHUB_SLUG + "/",
                    "Independent Update URI"
                )
                data = replace_once(
                    data,
                    "require_once __DIR__ . '/includes/extended-fields.php';",
                    "require_once __DIR__ . '/includes/extended-fields.php';\n"
                    "require_once __DIR__ . '/includes/standalone-bootstrap.php';",
                    "Independent bootstrap"
                )
            content = data.encode("utf-8")
        result[f"{folder}/{output_path}"] = content

    if standalone:
        for relative in EXTRA_FILES:
            suffix = relative.removeprefix("distribution/independent/")
            if suffix == "bootstrap.php":
                suffix = "standalone-bootstrap.php"
            result[f"{folder}/includes/{suffix}"] = (ROOT / relative).read_bytes()
    else:
        for relative in ORG_CONSENT_FILES:
            suffix = ("consent-bootstrap.php" if relative.endswith("consent-bootstrap.php")
                      else "hashe-core/v1/consent-client.php")
            data = (ROOT / relative).read_text(encoding="utf-8")
            data = data.replace("'" + GITHUB_SLUG + "'", "'" + ORG_SLUG + "'")
            result[f"{folder}/includes/{suffix}"] = data.encode("utf-8")
        if any(b"Update URI:" in entry or b"update_plugins_plugins.hashe.com" in entry
               for entry in result.values()):
            raise ValueError("WordPress.org package would include external updater")
        if not any(b"hashe_acf_optional_reporting_v1" in entry for entry in result.values()):
            raise ValueError("WordPress.org mandatory consent client missing")
    return result


def build(out: Path) -> None:
    version = source_version()
    out.mkdir(parents=True, exist_ok=True)
    sums = []
    for kind, slug, suffix in (
        ("wordpress-org", ORG_SLUG, "-wporg"),
        ("independent", GITHUB_SLUG, ""),
    ):
        name = f"{slug}-v{version}{suffix}.zip"
        target = out / name
        if target.exists():
            raise FileExistsError(f"Refusing to overwrite existing candidate: {target}")
        payload = contents(kind)
        # Reproducible ZIP identity is a release requirement. ZipFile.writestr
        # with a string name inserts the current clock time into each entry;
        # identical source then produces different ZIP checksums. Pin entry
        # metadata, file mode, ordering and compression deterministically.
        with ZipFile(target, "w", ZIP_DEFLATED, compresslevel=9) as archive:
            for entry, data in sorted(payload.items()):
                info = ZipInfo(entry, date_time=(1980, 1, 1, 0, 0, 0))
                info.create_system = 3  # Unix metadata, independent of builder OS.
                info.external_attr = 0o100644 << 16
                info.compress_type = ZIP_DEFLATED
                archive.writestr(info, data, compress_type=ZIP_DEFLATED, compresslevel=9)
        with ZipFile(target) as archive:
            if archive.testzip() is not None:
                raise ValueError("Corrupted candidate archive")
            if archive.namelist() != sorted(payload):
                raise ValueError("Unexpected package entries")
        digest = hashlib.sha256(target.read_bytes()).hexdigest()
        sums.append(f"{digest}  {name}")
        print(f"CANDIDATE={name} FILES={len(payload)} SHA256={digest}")
    (out / "SHA256SUMS.txt").write_text("\n".join(sums) + "\n", encoding="utf-8")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "dist-candidate")
    args = parser.parse_args()
    build(args.output)
