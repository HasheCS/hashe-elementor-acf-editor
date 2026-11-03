# ACF Editor v0.6.0 — dual-distribution candidate (NOT RELEASED)

Date: 2026-11-02. Scope: HasheCS/hashe-elementor-acf-editor only.

## Authority and controls
- Governance: HP-PLUG-001 (2026-11-01), HP-WEB-001 (website service remains separately controlled).
- Existing public stable: v0.5.1, unchanged. WordPress.org submission remains pending; do not upload or reply without candidate acceptance.
- ACF/Elementor editor/save functionality is intentionally unchanged from v0.5.1. No site changes.
- Minimum WordPress in this candidate: 6.5 (native dependency headers).
- Dependencies: `elementor, advanced-custom-fields`. ACF Pro 6.8.4 or later advertises support for satisfying `advanced-custom-fields`; check older ACF Pro versions before claiming compatibility.
- Working candidate name: **Hashe Field Editor**.
- Proposed WordPress.org slug: `hashe-field-editor`. Public exact-slug lookup (2026-11-03): WordPress.org plugin information API returns HTTP 404 and public URL redirects to plugin search. This does NOT establish ownership/reservation; official slug reservation and reviewer approval remain PENDING.

Directory subtitle/description: **Edit Advanced Custom Fields directly in Elementor Page Settings using native Elementor controls.** This explains the integration without putting third-party trademarks in the plugin name or slug.
## Two distinct plugin identities from one audited codebase
WordPress.org Plugin Directory guideline 8 and Plugin Check forbid a competing third-party updater. Therefore the two update identities MUST NOT share a slug or installation path.

| Distribution | Directory/entrypoint | Updates | Contents |
|---|---|---|---|
| WordPress.org | `hashe-field-editor/hashe-field-editor.php` | WordPress.org core update service | Feature code + readme/LICENSE; no custom updater or telemetry client |
| Independent | `hashe-elementor-acf-editor/hashe-elementor-acf-editor.php` | Hashe approved stable update metadata only, verified SHA-256 ZIP | Same feature code, isolated update client, default-off optional consent client |

This **requires a documented exception/clarification** to HP-PLUG-001's literal “one identical ZIP” language: two separately verified immutable release artifacts, from the same approved source/version, with only distribution-specific headers and standalone service modules. The governing Google Doc has NOT been changed. Do not publish either variant until this is explicitly reconciled and authorized.

## Update status
- Independent Update URI: `https://plugins.hashe.com/hashe-elementor-acf-editor/`.
- Candidate update feed URL: `https://plugins.hashe.com/api/v1/updates/hashe-elementor-acf-editor` (**corrected in draft**).
- Read-only GET 2026-11-03: HTTP 200, stable v0.5.1 and matching GitHub ZIP/SHA256. The website currently returns `tested=null` and `requires_php=null`; strict updater validation therefore fails closed pending compatibility evidence and website-owned contract correction.
- The inherited versioned update module compares SemVer, requires a stable/approved release manifest, pins the exact HasheCS GitHub ZIP path, checks its SHA-256 before handing it to WordPress, and does not send analytics data.
- No silent release, auto-enable of WordPress autoupdate settings, fallback to unreviewed source or plugin migration.
- The independent plugin folder/main basename intentionally remain identical to v0.5.1 for upgrades. Never install the WordPress.org and independent variant concurrently, since they implement the same PHP classes/controls. Coexistence and migration deserve explicit verification before publication.

## Optional installation reporting
- Only the independent candidate contains the opt-in module.
- Default no contract, no opt-in action, no telemetry network calls, no site ID. Skip remains local.
- A future live consent path requires HP-WEB-001 Phase 7: authentication contract, retention/disclosure, revocation/deletion endpoint, abuse control and integration testing.
- Do not advertise functioning live reporting merely because candidate client files exist.
- A directory variant may need its own compliant, consent-based reporting after backend readiness. It is deliberately omitted from this prereview ZIP to avoid unnecessary third-party contact.

## Readiness gates (currently NOT certified)
1. PHP 7.4/8.2/8.3 lint + existing ACF/Elementor PHP and JS regression suite.
2. Build both ZIPs, compare identity and contents, inspect SHA-256 and disallowed files.
3. WordPress.org Plugin Check on **actual WordPress.org ZIP**.
4. Isolated WordPress clean install, dependency-missing behavior, ACF Free and Pro, UI and Save ACF Fields full regression; WordPress 6.5+ / Elementor compatibility.
5. WordPress.org naming/slug approval, Hashe ownership proof using @hashe.com email or authorized DNS TXT record.
6. Live standalone update: older ZIP -> approved newer ZIP, safe checksum rejection/rollback, opt-out unaffected; no updates to stable/prerelease branches without approval.
7. Consent Skip/Allow/revoke and server-side deletion end-to-end; service down and offline behavior.
8. Reconcile HP-PLUG-001 distribution-artifact exception, peer review, final user approval BEFORE merge/tag/release/WordPress.org upload or email.

## Reproducible build
`python3 tests/distribution-test.py`
`python3 tools/build_distributions.py --output dist-candidate`

GitHub CI builds and tests both ZIPs as ephemeral artifacts. DO NOT mistake CI artifacts for published stable releases. Draft release.yml is now manual-only (`workflow_dispatch`), with explicit approval, existing annotated stable tag matching `main`, and the independent ZIP builder. This candidate workflow has not been used to publish. Final workflow QA and company dual-package policy clarification remain blocking; do not tag until approved.

## 2026-11-03 engineering and ownership checkpoint

- WordPress.org owner-domain proof: TXT at hashe.com root with value `wordpressorg-hashe-verification` verified via 1.1.1.1, 8.8.8.8, kevin.quicns.com and jon.quicns.com. DNS evidence is ready; official reviewer acceptance remains pending.
- Square icon master and 128px/256px versions are approved and checksum-locked in `BRAND-APPROVAL.md`; binary import and independent directory-banner approval remain outstanding.
- Independent update route has been corrected in the draft bootstrap, with a package regression guard; the live feed serves v0.5.1 with incomplete compatibility metadata.
- **PASS:** disposable WordPress 7.1.4 integration (run 41590800121): nine real runtime jobs on PHP 7.4, 8.2 and 8.3, including independent clean/v0.5.1 upgrade and WordPress.org clean install, real Elementor and ACF Free, ACF text-field storage, native hooks, default-off privacy, deactivate/reactivate/uninstall. This is not editor-browser or ACF Pro QA.
- **PASS:** actual WordPress core upgrader simulation (run 41590520276): synthetic v0.6.1 not published; rejected bad SHA256 before touching installed plugin, accepted verified synthetic ZIP, and made no optional-reporting consent. The synthetic test is not live real-feed update acceptance.
- **PASS:** simulated HTTP, real WordPress consent workflow (run 41590973856): default-off, Skip, explicit checked Allow, report, offline behavior, revoke failure, pending deletion, stale Skip/Allow rejected, retry success, identity removal. No real backend transmission or privacy acceptance has occurred.
- **Open gates:** ACF Pro, browser-level Elementor field save, advanced type regression, missing-dependency experience, WordPress lower-bound compatibility, real service update-now, live report/revoke backend, and final peer/security review.
- Draft release workflow requires manual approval; no tag, main merge, public ZIP, WordPress.org upload or site deployment occurred.
- Release remains BLOCKED by real acceptance testing, owner-endpoint contract, optional reporting backend/privacy contract, HP-PLUG-001 two-artifact clarification, approved artwork import, final immutable ZIP and explicit CEO release approval.

## Rollback and scope
No runtime site changes. Rollback is close the draft PR or delete only this isolated candidate branch after approval. Published v0.5.1 ZIP, protected main, WordPress.org submission and plugins.hashe.com remain unchanged.


## PPP checkpoint — 2026-11-03: reproducibility and ACF Pro staging QA

**Scope:** Hashe Field Editor only. Font Awesome and the Plugins Website workstreams were not changed.

### GitHub and real WordPress QA
- Candidate head 5837ea60a3557f5b4c09f6915f0887b748bc5082; CI runs 41611327530 and 41611327503 both SUCCESS. PR #5 remains open and draft.
- Real WordPress AJAX tests passed: unauthorized edits, bad nonce, field allowlist, required-field validation, four typed field writes, persistence, and preserving native Elementor Page Settings while removing stale ACF mirrors.
- Real Elementor document registration test passed for native field controls and Save button identity; delegated JavaScript click/AJAX logic is separately tested. Browser visual/editor interaction remains untested.

### Reproducible package defect and correction
- Pre-fix same-source ZIP builds generated different SHA-256 values due to current-time metadata. This was confirmed with two independent builds: NONREPRODUCIBLE.
- The candidate builder now pins member timestamp, UNIX file mode, entry order and compression. The distribution QA tests two independent byte-for-byte builds, and the checks passed again on staging hosting with a 3-second delay.
- Exact candidate independent ZIP: hashe-elementor-acf-editor-v0.6.0.zip, 9 files, SHA-256 470ee5946fb4fc09197986aee9cea87574bc1bd7e9c35402eb0432f0138297d7.
- Exact candidate directory ZIP: hashe-field-editor-v0.6.0-wporg.zip, 6 files, SHA-256 a82ba0f8ba74898ae910e520210441810c21fdd1bfdb3fefd68c721bc7beaf2e.
- Neither ZIP is a published release. Any feature/source change requires repeating the artifact checks.

### Verified Solutions Moco ACF Pro acceptance and rollback
- Authorized route: Windows Desktop Commander -> ssh sasse -> dev.sasseagency.com/solutions. WordPress 7.1.4, Elementor 4.3.5, Elementor Pro 4.3.2, ACF Pro 6.8.10.
- Before site mutation, complete v0.5.1 plugin and QA database backups were saved privately outside webroot: /bigdisk/home/devsasseagency/.hashe-field-editor-qa/backup-20261103T091533Z/. Hashes and gzip tests passed; original plugin was extracted and verified with diff-based comparison.
- Temporarily installed candidate v0.6.0 via WP-CLI and ran source-versioned, scope-locked read-only ACF Pro test on QA draft page 654: one matching field group, 36 assigned fields, all 36 native Elementor field controls present, 8 additional ACF Pro adapters mapped and native Save action hooks present.
- Unconditional rollback installed v0.5.1. Exact old main-file SHA-256 e0384be7f9e40ad029e1b8b04981acfa3ff8054a1d7ff812e3f90a074fa5b21e passed. Shared FA7 QA3 plugin main-file hash unchanged. Page 654 remains draft. Final staging execution exit code 0.
- This verifies server-side ACF Pro controls and adapters only. It does NOT certify browser UI, field entry, every field save, or post-reload persistence in the actual Elementor browser editor.

### Update service dependency
- Read-only live JSON feed at https://plugins.hashe.com/api/v1/updates/hashe-elementor-acf-editor still serves stable v0.5.1 with the known GitHub SHA, but tested and requires_php are null.
- The strict updater intentionally rejects incomplete compatibility metadata. Website workstream owns the fix; this plugin thread made NO website changes.

### Unresolved release blockers
1. Authenticated Elementor browser acceptance for field rendering, 36-field interaction and saving, reload persistence, and native Elementor/Font Awesome coexistence.
2. Independently verified live stable-version Update now, integrity failure and rollback under a valid owner-fed metadata contract, without telemetry consent.
3. Live privacy-approved opt-in, report, revoke and deletion backend acceptance; mocked client tests alone are not sufficient.
4. Final directory artwork binary import/banner approval and formal HP-PLUG-001 dual-package exception.
5. Peer/security signoff, final release ZIP/tag/checksum and explicit CEO approval before GitHub merge or publication; WordPress.org resubmission later.

**No production code, main, public GitHub release, WordPress.org submission or website backend was changed.**
