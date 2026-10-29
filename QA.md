# Verification and release status

**Release candidate:** v0.3.1-rc.1

**Source of functionality:** the Hashe Elementor ACF Editor QA build tested on Solutions Moco. Published code differs only in the version label and release documentation; the ACF scripts and save logic are the same as the browser-accepted QA build.

## Verified October 29, 2026

- WordPress + Elementor 4.3.5 + Elementor Pro 4.3.2 + ACF Pro 6.8.10.
- Isolated headless Chrome with a temporary, authorized WordPress editing session.
- Accessed actual Elementor Page Settings on QA post 654.
- Confirmed icon and field model initialization, including ACF Pro repeater JavaScript.
- Clicked native Add Fact footer button: new row blank.
- Entered nested values, dragged the row above the existing rows.
- Selected an existing Media Library attachment in the native image picker.
- Clicked the plugin's Save ACF Fields button. AJAX response: HTTP 200 and success.
- Reloaded Elementor and verified headline, selected image and reordered repeater values persisted.
- Clicked native Remove Row, confirmed ACF's own confirmation tooltip, saved, reopened, and verified the deletion persisted.
- Verified ACF's unsaved-change warning was not left active after successful AJAX saving.
- All tracked end-to-end browser checks passed.
- QA page values restored to original sample; page reverted to draft status.
- Temporary test WordPress authentication token revoked and test cookie/profile removed.

## Security and source checks

- Authenticated WordPress AJAX only.
- Document edit capability and nonce enforced server-side.
- Only field keys from location-matched ACF field groups accepted.
- ACF's validation and save functions used for nested field types.
- No iframe or WordPress Tools admin embedding.
- No ACF Pro or Elementor Pro source/files bundled.
- Original DataWatch implementation not changed.

## Still not fully verified

- All third-party ACF field types and external custom field libraries.
- All historical and future Elementor/ACF version combinations.
- Every supported browser and mobile viewport.
- WordPress multisite and localization configurations.

This repository should remain in **pre-release** until wider compatibility feedback supports a stable release. Do not claim universal field-type compatibility from a single QA installation.

The browser occasionally reported TinyMCE permissions-policy warnings and a generic HTTP 404 console message. The latter's resource identity was not established; neither interrupted the validated ACF workflows. Browser policy diagnostics were not hidden or suppressed.
