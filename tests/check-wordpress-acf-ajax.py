#!/usr/bin/env python3
"""Check Hashe Field Editor's PHP AJAX action in disposable WordPress.

Calls separate WP-CLI processes against the same ephemeral WP database.
Never run against production or an existing site. The caller must set
HASHE_QA_EPHEMERAL=1 and WP_ROOT to a temporary GitHub Actions WP path.
"""
from __future__ import annotations
import json
import os
from pathlib import Path
import re
import subprocess
import sys

root = Path(__file__).resolve().parent
wp_bin = os.environ.get("WP_BIN", "")
wp_root = os.environ.get("WP_ROOT", "")
if (
    os.environ.get("HASHE_QA_EPHEMERAL") != "1"
    or not wp_bin or not wp_root
    or not Path(wp_bin).is_file()
    or not Path(wp_root, "wp-config.php").is_file()
    or "wordpress" not in wp_root
):
    raise SystemExit("ERROR: Only disposable GitHub Actions WordPress QA is allowed")

fixture = root / "wordpress-acf-ajax-integration.php"
stage_order = [
    "prepare",
    "forbidden",
    "bad-nonce",
    "rogue-only",
    "invalid-required",
    "verify-original",
    "valid-save",
    "verify-saved",
    "cleanup",
]
expected_marker = {
    "prepare": "HEAE_AJAX_PREPARE=PASS",
    "verify-original": "HEAE_AJAX_DENIALS_PRESERVED_DATA=PASS",
    "verify-saved": "HEAE_AJAX_REAL_ACF_SAVE=PASS",
    "cleanup": "HEAE_AJAX_CLEANUP=PASS",
}
expected_json_success = {
    "forbidden": False,
    "rogue-only": False,
    "invalid-required": False,
    "valid-save": True,
}

for stage in stage_order:
    env = {**os.environ, "HASHE_QA_AJAX_STAGE": stage}
    process = subprocess.run(
        ["php", wp_bin, "eval-file", str(fixture), f"--path={wp_root}"],
        text=True, encoding="utf-8", errors="replace",
        stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=env, timeout=45,
        check=False,
    )
    stdout, stderr = process.stdout.strip(), process.stderr.strip()
    if stage in expected_marker:
        if process.returncode != 0 or expected_marker[stage] not in stdout:
            raise SystemExit(
                f"FAIL stage={stage} exit={process.returncode}\n"
                f"STDOUT={stdout[-900:]}\nSTDERR={stderr[-900:]}"
            )
    elif stage == "bad-nonce":
        # Core check_ajax_referer(..., ..., true) terminates on invalid nonce.
        if "-1" not in stdout and "nonce" not in stderr.lower():
            raise SystemExit(
                f"FAIL bad-nonce did not reject request: exit={process.returncode} "
                f"stdout={stdout[-700:]} stderr={stderr[-700:]}"
            )
    else:
        # WP_CLI may write notices/warnings to stdout before the JSON.
        # Only accept one parseable final JSON object from the AJAX handler.
        match = re.search(r'(\{"success":(?:true|false),"data":.*\})\s*$', stdout, re.S)
        if not match:
            raise SystemExit(
                f"FAIL AJAX stage={stage} lacks valid JSON: exit={process.returncode}\n"
                f"STDOUT={stdout[-1000:]}\nSTDERR={stderr[-1000:]}"
            )
        try:
            response = json.loads(match.group(1))
        except json.JSONDecodeError as exc:
            raise SystemExit(f"FAIL JSON decode stage={stage}: {exc}") from exc
        if response.get("success") is not expected_json_success[stage]:
            raise SystemExit(f"FAIL unexpected AJAX status stage={stage}: {response}")
        data = response.get("data", {})
        if stage == "valid-save" and data.get("updated_fields") != 4:
            raise SystemExit(f"FAIL field allowlist/response count stage={stage}: {data}")
        if stage == "invalid-required" and not data.get("errors"):
            raise SystemExit(f"FAIL missing real ACF validation errors: {data}")
    print(f"HEAE_REAL_WP_AJAX_STAGE={stage}=PASS")
print("HEAE_REAL_WP_AJAX_ACCESS_NONCE_VALIDATION_ALLOWLIST_SAVE=PASS")
