# Hashe Elementor ACF Editor

Edit Advanced Custom Fields **inside Elementor's Page Settings**. No iframe, WordPress toolbar, or trip back to the standard WordPress editor.

**Free and open-source software**, developed and maintained by [Hashe Computer Solutions (Pvt) Ltd](https://www.hashe.com/).

**Testing release candidate — v0.2.0-rc.1.** Backend regression checks passed on Solutions Moco, and the native Elementor sidebar layout was visually approved. The full image-picker and repeater-button interaction flow still needs final interactive acceptance before a stable release.

## Features

- Adds an **ACF** tab to Elementor's Page Settings.
- Detects the current WordPress page/post and displays only active ACF field groups whose location rules match that document.
- Renders ACF's own input controls directly in the Elementor sidebar, rather than maintaining substitute field widgets.
- Provides a separate **Save ACF Fields** button, with an inline success or validation error message.
- Supports field types supplied by the connected ACF installation, including text, textarea, select, true/false, image and repeater (Pro field types need ACF Pro).
- No embedded WordPress admin page, iframe, or hardcoded customer-specific identifiers.
- Permission-aware and nonce-protected: submissions can update only field keys belonging to ACF groups assigned to the current document.
- Uses scoped Elementor-sidebar styles that follow the editor theme.

## Compatibility

**Verified test environment:** WordPress on Solutions Moco, Elementor 4.3.5, Elementor Pro 4.3.2 and ACF Pro 6.8.10 (October 2026). Requires PHP 7.4+ and WordPress 5.8+.

Elementor and ACF need to be active. This candidate has been tested against ACF **Pro**; every third-party field type or Elementor fork is not guaranteed to work. Standard ACF Free compatibility is a future test target, not a verified claim.

The plugin ships **no ACF, ACF Pro, Elementor or Elementor Pro binaries** and does not bypass software licenses.

## Installation

1. Back up the website.
2. Download the versioned WordPress-installable ZIP **from GitHub Releases** (not the GitHub-generated source ZIP).
3. In WordPress, choose **Plugins → Add New Plugin → Upload Plugin**, upload the ZIP and activate it.
4. Make sure Elementor and ACF Pro are active.
5. Create an ACF field group with location rules matching a page or post you edit with Elementor.
6. Open that document in Elementor → **Page Settings → ACF**.
7. Edit the fields and click **Save ACF Fields**.

**Important:** Saving ACF field values is separate from Elementor's main Update/Publish button.

## How it works

The plugin hooks Elementor document control registration and adds a native ACF field control section. It uses ACF's own rendering and input scripts to initialize complex controls.

Only ACF fields belonging to location-matched groups are accepted by a dedicated authenticated WordPress AJAX endpoint. The endpoint checks post edit permissions, a per-document nonce, ACF validation errors, and uses ACF's official save pathway.

## QA and known limitations

- **Passed:** Native sidebar rendering without iframe, ACF group scoping, value persistence, repeater backend save, required-field validation, permission enforcement, nonce rejection, and field-key allowlisting.
- **Passed:** User visual inspection of the actual Elementor Page Settings interface on Solutions Moco.
- **Still to confirm interactively:** Opening and choosing an image in the ACF media picker, using Add Row/Delete Row on a repeater, and saving those values entirely through the browser.
- Third-party ACF controls may require additional CSS/JS adjustments. Validate them on a staging site before using the plugin in production.
- This version deliberately stays a **pre-release** until complete interactive browser checks are accepted.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Do not include private website data, customer content, or proprietary ACF Pro source in contributions.

## Author, license and trademarks

**Developer and maintainer:** [Hashe Computer Solutions (Pvt) Ltd](https://www.hashe.com/).

**License:** [GNU General Public License v2 or later](LICENSE) (`GPL-2.0-or-later`). This plugin is provided free of charge and may be used, modified and redistributed under the GPL terms.

Copyright © 2026 Hashe Computer Solutions (Pvt) Ltd.

Elementor and Advanced Custom Fields (ACF) are third-party products and trademarks. This is an independent Hashe integration; no affiliation or endorsement is implied.
