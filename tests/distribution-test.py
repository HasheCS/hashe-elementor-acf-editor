#!/usr/bin/env python3
"""Offline, read-only QA for the two unpublished distribution candidates."""
import hashlib
import tempfile
from pathlib import Path
from zipfile import ZipFile
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "tools"))
from build_distributions import ORG_SLUG, GITHUB_SLUG, source_version, build

version = source_version()
with tempfile.TemporaryDirectory(prefix="hashe-acf-qa-") as temporary:
    folder = Path(temporary)
    build(folder)
    org = folder / f"{ORG_SLUG}-v{version}-wporg.zip"
    ind = folder / f"{GITHUB_SLUG}-v{version}.zip"
    assert org.is_file() and ind.is_file()
    with ZipFile(org) as z:
        entries = set(z.namelist())
        assert len(entries) == 6, entries
        main = z.read(f"{ORG_SLUG}/{ORG_SLUG}.php").decode()
        readme = z.read(f"{ORG_SLUG}/readme.txt").decode()
        extended = z.read(f"{ORG_SLUG}/includes/extended-fields.php").decode()
        assert "Plugin Name: Hashe Field Editor" in main
        assert readme.startswith("=== Hashe Field Editor ===")
        assert ORG_SLUG == "hashe-field-editor"
        assert "Text Domain: " + ORG_SLUG in main
        assert "Requires Plugins: elementor, advanced-custom-fields" in main
        assert "Update URI:" not in main
        assert "standalone-bootstrap" not in main
        assert "update_plugins_plugins.hashe.com" not in main + readme + extended
        assert "telemetry/report" not in main + readme + extended
        assert GITHUB_SLUG + ".php" not in entries
        assert "github.com/HasheCS/" + GITHUB_SLUG in main + readme
        assert "Stable tag: " + version in readme
        assert all(x.startswith(ORG_SLUG + "/") for x in entries)
        assert z.testzip() is None
    with ZipFile(ind) as z:
        entries = set(z.namelist())
        assert len(entries) == 9, entries
        main = z.read(f"{GITHUB_SLUG}/{GITHUB_SLUG}.php").decode()
        bootstrap = z.read(f"{GITHUB_SLUG}/includes/standalone-bootstrap.php").decode()
        update = z.read(f"{GITHUB_SLUG}/includes/hashe-core/v1/update-client.php").decode()
        consent = z.read(f"{GITHUB_SLUG}/includes/hashe-core/v1/consent-client.php").decode()
        assert "Requires Plugins: elementor, advanced-custom-fields" in main
        assert "Update URI: https://plugins.hashe.com/" + GITHUB_SLUG + "/" in main
        assert "require_once __DIR__ . '/includes/standalone-bootstrap.php';" in main
        assert "update_plugins_plugins.hashe.com" in update
        assert "hash_file( 'sha256'" in update and "hash_equals" in update
        assert "'allowed'" in consent and "'revocation-pending'" in consent
        assert "hashe_acf_optional_reporting" in consent
        assert "Hashe Font Awesome" not in consent
        assert f"{GITHUB_SLUG}/{GITHUB_SLUG}.php" in entries
        assert z.testzip() is None
        assert all(x.startswith(GITHUB_SLUG + "/") for x in entries)
    manifest = (folder / "SHA256SUMS.txt").read_text()
    assert len(manifest.splitlines()) == 2
    for archive in [org, ind]:
        digest = hashlib.sha256(archive.read_bytes()).hexdigest()
        assert f"{digest}  {archive.name}" in manifest
    print("PASS: plugin dependencies and consistent candidate versions")
    print("PASS: directory package has new slug/text domain and no independent updates/telemetry")
    print("PASS: independent package preserves old folder identity and adds checksum-gated updater")
    print("PASS: opt-in reporting stays separate from WordPress update requests")
    print("PASS: both ZIP files valid and SHA256 manifest agrees")
