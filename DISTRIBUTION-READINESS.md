# ACF Editor v0.6.0 — dual-distribution candidate (NOT RELEASED)

## Current authoritative checkpoint — HP-PLUG-001 / HP-WEB-001 v1.2, November 4, 2026

**Binding owner policy supersedes earlier proposed one-ZIP exception and earlier
assumption that WordPress.org edition may omit installation reporting.** The
historical entries below describe prior snapshots; this section is the
current release assessment and is NOT authorization to publish.

- **Both channels required:** directory candidate `hashe-field-editor/hashe-field-editor.php`
  uses WordPress.org-native updates; independent
  `hashe-elementor-acf-editor/hashe-elementor-acf-editor.php` uses
  Hashe's approved immutable release feed, never the directory updater.
  These are distinct plugin basenames and should **not** be installed
  simultaneously as two copies of the same feature. Never migrate silently.
- **Mandatory opt-in capability, voluntary permission:** both v0.6.0
  packages now ship the per-plugin admin-only Allow / Skip / Revoke
  client. No optional reporting is activated without affirmative consent
  and an approved secured service contract. Existing WordPress.org
  package no longer deletes consent capability. No live proof/enrollment
  endpoint is assumed or invoked.
- **Release synchronization:** an accepted, authorized GitHub stable ZIP
  triggers the website's automatic verified release sync without a second
  website approval. This is HP-WEB-001's backend delivery obligation;
  the Phase 5 production integration is **not yet deployed**.
- **Conditionally approved PR #5:** Owner authorization to merge and
  publish v0.6.0 activates only after all actual browser, security,
  compatibility, privacy/integration, update, packaging and rollback
  acceptance gates pass. The PR is still draft; main and public v0.5.1
  remain unchanged. WordPress.org upload/reviewer acceptance is separate.

**New v1.2 test evidence:**
- Exact code/package head `d5a615a553d0afded50714c918dc84aac35236fa`
  and subsequent test-only coexistence update
  `547f92c45b41a66c22445569fb494e849d10cb58`.
  All nine disposable WordPress lifecycle jobs and PHP/JS and package QA
  completed SUCCESS. The directory package includes the default-off
  consent client, tested in the real disposable WordPress
  Allow/Skip/Revoke flow; independent-channel ZIP SHA256
  `256c56a5aa559b480c6cac92bae5b919253e8da65c48aec93054614f5928c62f`
  (9 entries); directory ZIP SHA256
  `0f21b080a06e23acba7e8a232c772f44e700b399db9a3dd96d7b40d5b0666f3f`
  (8 entries). Exact build source and ZIP input files are unchanged
  by the later test-only CI update.
- New pinned FA7 `45a58b2cce1c6609415bdf39789c0928e654d850`
  WordPress.org coexistence acceptance PASSED on disposable WordPress:
  both active, no third-party update hooks, no PHP collisions, isolated
  opt-in state/cron and skip behavior. Evidence:
  https://github.com/HasheCS/hashe-elementor-acf-editor/actions/runs/41838593233
- Existing independent v0.5.1 upgrade, ACF Free AJAX save,
  ACF Pro 36-control server registration and all source-unit checks
  retain their earlier evidence. The updated ACF directory Plugin Check
  CI action passed. This is not reviewer approval.

**Release-blocking evidence still OPEN:**
1. Real authenticated Chrome **typed field edit -> Save -> server-side
   ACF value -> reload** on v0.6.0 (and representative ACF Pro advanced
   field behavior), not only a no-change Save. Current Windows UI
   accessibility/keystroke automation did not reliably mutate the native
   control. A genuine graphical-browser proof is mandatory.
2. Backend's joint HP-API-001 v1.1 integration contract is still
   PROPOSED; real opt-in site proof/enrollment/report/revoke,
   30-day original-consent correctness and secure service E2E are untested.
   No reporting deployment or production activation is authorized.
3. WordPress.org official `hashe-field-editor` slug/reviewer
   acceptance, approved binary directory artwork import and banner
   approval, and final source/license/security acceptance remain open.
4. Automatic Phase 5 sync and actual newer stable independent
   `Update now` from a live website-approved feed cannot pass until
   the backend is deployed and a version is legitimately approved.
   Old public v0.5.1 requires a documented one-time managed/manual
   first adoption, then built-in updates.
5. Final release preflight must verify the exact approved merge commit,
   immutable tag, archive SHA, backups and tested rollback, and no
   new unstaged changes or material regressions.

**PPP staging restoration:** Following supervised Solutions QA,
the original Field Editor v0.5.1 main PHP SHA256
`e0384be7f9e40ad029e1b8b04981acfa3ff8054a1d7ff812e3f90a074fa5b21e`,
QA draft #654 original field/settings and status, and FA7
installation fingerprint were independently verified restored.
No stable tag, PR merge, published ZIP, WordPress.org submission,
backend deployment or production telemetry activation was performed.


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
| WordPress.org | `hashe-field-editor/hashe-field-editor.php` | WordPress.org core update service | Feature code + default-off consent client; no custom updater |
| Independent | `hashe-elementor-acf-editor/hashe-elementor-acf-editor.php` | Hashe approved stable update metadata only, verified SHA-256 ZIP | Same feature code, isolated update client, default-off optional consent client |

**SUPERSEDED by HP-PLUG-001 v1.2:** The owner mandates two channel-appropriate ZIPs from approved source, with consent capability in both. The independent artifact must match GitHub and Hashe's website byte-for-byte; WordPress.org owns its own update provider. Do not publish before the remaining acceptance gates.

## Update status
- Independent Update URI: `https://plugins.hashe.com/hashe-elementor-acf-editor/`.
- Candidate update feed URL: `https://plugins.hashe.com/api/v1/updates/hashe-elementor-acf-editor` (**corrected in draft**).
- Read-only GET 2026-11-03: HTTP 200, stable v0.5.1 and matching GitHub ZIP/SHA256. The website currently returns `tested=null` and `requires_php=null`; strict updater validation therefore fails closed pending compatibility evidence and website-owned contract correction.
- The inherited versioned update module compares SemVer, requires a stable/approved release manifest, pins the exact HasheCS GitHub ZIP path, checks its SHA-256 before handing it to WordPress, and does not send analytics data.
- No silent release, auto-enable of WordPress autoupdate settings, fallback to unreviewed source or plugin migration.
- The independent plugin folder/main basename intentionally remain identical to v0.5.1 for upgrades. Never install the WordPress.org and independent variant concurrently, since they implement the same PHP classes/controls. Coexistence and migration deserve explicit verification before publication.

## Optional installation reporting
- Both candidate distributions contain the mandatory default-off opt-in module under HP-PLUG-001 v1.2.
- Default no contract, no opt-in action, no telemetry network calls, no site ID. Skip remains local.
- A future live consent path requires HP-WEB-001 Phase 7: authentication contract, retention/disclosure, revocation/deletion endpoint, abuse control and integration testing.
- Do not advertise functioning live reporting merely because candidate client files exist.
- The directory variant now includes the same collision-safe consent client and a directory-specific bootstrap while excluding the external updater. No external reporting occurs before an approved security/privacy contract and administrator opt-in.

## Readiness gates (currently NOT certified)
1. PHP 7.4/8.2/8.3 lint + existing ACF/Elementor PHP and JS regression suite.
2. Build both ZIPs, compare identity and contents, inspect SHA-256 and disallowed files.
3. WordPress.org Plugin Check on **actual WordPress.org ZIP**.
4. Isolated WordPress clean install, dependency-missing behavior, ACF Free and Pro, UI and Save ACF Fields full regression; WordPress 6.5+ / Elementor compatibility.
5. WordPress.org naming/slug approval, Hashe ownership proof using @hashe.com email or authorized DNS TXT record.
6. Live standalone update: older ZIP -> approved newer ZIP, safe checksum rejection/rollback, opt-out unaffected; no updates to stable/prerelease branches without approval.
7. Consent Skip/Allow/revoke and server-side deletion end-to-end; service down and offline behavior.
8. Apply HP-PLUG-001 v1.2's binding two-channel rule, complete peer review and all mandatory evidence before the owner's conditional PR #5 merge/tag/publication authority can take effect; WordPress.org approval remains separate.

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


## 2026-11-03 — Authenticated Chrome browser Save acceptance: BLOCKED, staging restored

- Verified the user-authenticated Chrome profile and open Solutions Moco Elementor tab. Opened the dedicated Field Editor QA draft page 654 in that same Chrome profile; no credentials exported and no Chrome restart.
- Native Elementor UI accessibility exposed 35 numbered field labels (the message-type field renders differently), ACF Page Settings, and the Save ACF Fields button.
- Existing v0.5.1 no-change Save baseline: twice displayed "ACF controls are still initializing. Nothing was saved; try again." In-browser JavaScript diagnostics showed the HEAE global, 36 schema fields, a live Elementor Page Settings model and a matching Save button DOM node.
- Draft correction removed the 60-second (tries >= 600) initialization cutoff, replacing it with MutationObserver and a 1-second fallback; new delayed-baseline.cjs tests simulated late opening, no-op, changed-field save, and no duplicate writes. Those tests passed in Windows Node and GitHub CI. Windows CRLF metadata regression was also corrected.
- **Authenticated v0.6.0 browser acceptance FAILED:** temporarily installed the exact candidate from head 9029f9ed214d293b06066b8e3ef357ec1edc96a5, reloaded Elementor, opened ACF, and clicked unchanged Save. Browser still displayed "ACF controls are still initializing. Nothing was saved; try again." The timeout correction is insufficient. Do not mark this gate passed.
- Full source-head CI and disposable WordPress tests passed (runs 41620312259 and 41620312266). Those green tests do NOT supersede the real browser failure.
- Verified private backup and rollback ZIP and captured only draft post 654's test text plus Elementor settings metadata using tests/staging-browser-fixture.php outside webroot. Candidate independent ZIP SHA-256 8dd2f763edf69dc0758e5b9e527385ff119fd641921034ed4d719a17b13add8e. Candidate directory ZIP SHA-256 942c78595821c7db6e615d12a9b9199072767f6ffbe6b1a8981c8de27aec9d96. Neither was released.
- A bounded server-side watchdog and unconditional rollback trap protected the test. No ACF field edits were attempted after the no-op failure. Explicit completion signal triggered rollback. Restored original active Field Editor v0.5.1 and its SHA, test text, Elementor settings, unchanged FA7 QA plugin SHA and draft status of page 654. FINAL_PPP_ROLLBACK_EXIT=0 and SSH_FINAL_EXIT_STATUS=0.
- Next diagnostic: verify the actual editor-native.js asset version loaded by Chrome and the per-field snapshot readiness/conversion. Add an automated regression for the observed mechanism, then repeat browser no-change, changed-field, post-reload and restoration tests.
- STRICT HOLD: PR #5 remains open/draft. No merge, tag, public GitHub v0.6.0, WordPress.org resubmission or release-readiness claim until browser saving passes and the remaining HP-PLUG-001 gates are independently signed off.
