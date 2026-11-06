#!/usr/bin/env python3
"""Build a CI-only synthetic v0.6.1 independent ZIP for native WP upgrader QA.

Never use this synthetic artifact as a real release. It derives from the exact
already-verified v0.6.0 candidate ZIP, changes only version declarations, and
stays inside disposable GitHub Actions runner storage.
"""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
import hashlib
import sys

if len(sys.argv) != 3:
    raise SystemExit("usage: build-synthetic-upgrade.py ORIGINAL_0.6.0_ZIP OUTPUT_0.6.1_ZIP")
source, destination = (Path(x) for x in sys.argv[1:3])
if not source.is_file() or destination.exists():
    raise SystemExit("Source ZIP missing or synthetic output already exists")
folder = "hashe-elementor-acf-editor/"
main = folder + "hashe-elementor-acf-editor.php"
readme = folder + "readme.txt"
with ZipFile(source) as original:
    if original.testzip() is not None:
        raise RuntimeError("Original candidate ZIP corrupt")
    names = original.namelist()
    if main not in names or readme not in names or len(names) != 9:
        raise RuntimeError("Unexpected original standalone ZIP identity")
    entries = {name: original.read(name) for name in names}

php = entries[main].decode("utf-8")
for before, after in [
    (" * Version: 0.6.0", " * Version: 0.6.1"),
    ("const VERSION = '0.6.0'", "const VERSION = '0.6.1'"),
]:
    if php.count(before) != 1:
        raise RuntimeError("Expected exact candidate version marker is missing")
    php = php.replace(before, after, 1)
entries[main] = php.encode("utf-8")

txt = entries[readme].decode("utf-8")
if txt.count("Stable tag: 0.6.0") != 1:
    raise RuntimeError("Expected candidate stable tag is missing")
entries[readme] = txt.replace("Stable tag: 0.6.0", "Stable tag: 0.6.1", 1).encode("utf-8")

with ZipFile(destination, "w", ZIP_DEFLATED) as output:
    for name in sorted(entries):
        output.writestr(name, entries[name])

with ZipFile(destination) as check:
    if check.testzip() is not None or set(check.namelist()) != set(names):
        raise RuntimeError("Synthetic ZIP structure invalid")
digest = hashlib.sha256(destination.read_bytes()).hexdigest()
print(f"SYNTHETIC_FIXTURE_ONLY={destination.name} SHA256={digest}")
print("NOTE=This is not a public approved tag/release and must never be distributed")
