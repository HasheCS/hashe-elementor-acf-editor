# ACF Editor v0.6.0 — dual-distribution candidate (NOT RELEASED)

Date: 2026-11-02. Scope: HasheCS/hashe-elementor-acf-editor only.

## Authority and controls
- Governance: HP-PLUG-001 (2026-11-01), HP-WEB-001 (website service remains separately controlled).
- Existing public stable: v0.5.1, unchanged. WordPress.org submission remains pending; do not upload or reply without candidate acceptance.
- ACF/Elementor editor/save functionality is intentionally unchanged from v0.5.1. No site changes.
- Minimum WordPress in this candidate: 6.5 (native dependency headers).
- Dependencies: `elementor, advanced-custom-fields`. ACF Pro 6.8.4 or later advertises support for satisfying `advanced-custom-fields`; check older ACF Pro versions before claiming compatibility.
- Working candidate name: **Hashe Field Editor for Elementor with Advanced Custom Fields**.
- Proposed WordPress.org slug: `hashe-field-editor-for-elementor-with-acf`. Official slug reservation and uniqueness review are PENDING.

## Two distinct plugin identities from one audited codebase
WordPress.org Plugin Directory guideline 8 and Plugin Check forbid a competing third-party updater. Therefore the two update identities MUST NOT share a slug or installation path.

| Distribution | Directory/entrypoint | Updates | Contents |
|---|---|---|---|
| WordPress.org | `hashe-field-editor-for-elementor-with-acf/hashe-field-editor-for-elementor-with-acf.php` | WordPress.org core update service | Feature code + readme/LICENSE; no custom updater or telemetry client |
| Independent | `hashe-elementor-acf-editor/hashe-elementor-acf-editor.php` | Hashe approved stable update metadata only, verified SHA-256 ZIP | Same feature code, isolated update client, default-off optional consent client |

This **requires a documented exception/clarification** to HP-PLUG-001's literal “one identical ZIP” language: two separately verified immutable release artifacts, from the same approved source/version, with only distribution-specific headers and standalone service modules. The governing Google Doc has NOT been changed. Do not publish either variant until this is explicitly reconciled and authorized.

## Update status
- Independent Update URI: `https://plugins.hashe.com/hashe-elementor-acf-editor/`.
- Candidate update feed URL: `https://plugins.hashe.com/api/v1/plugins/hashe-elementor-acf-editor/update`.
- URL selection is **a proposal**, not an assertion that an endpoint exists. HP-WEB-001 Phase 6 owns the actual production feed.
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

GitHub CI builds and tests both ZIPs as ephemeral artifacts. DO NOT mistake CI artifacts for published stable releases. Current release.yml still targets legacy v0.5.1 directory identity; it must be redesigned and re-reviewed before ANY tag/publishing action for v0.6.0. Never create a v0.6.0 tag before that safety gate.

## Rollback and scope
No runtime site changes. Rollback is close the draft PR or delete only this isolated candidate branch after approval. Published v0.5.1 ZIP, protected main, WordPress.org submission and plugins.hashe.com remain unchanged.
