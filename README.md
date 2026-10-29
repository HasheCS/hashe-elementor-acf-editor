# Hashe Elementor ACF Editor

Edit Advanced Custom Fields directly inside **Elementor → Page Settings → ACF**, using ACF's native controls. No iframe, embedded WordPress admin screen, or trip back to the block/classic editor.

An open-source project by [Hashe Computer Solutions](https://hashe.com/). Distributed under **GPL-2.0-or-later**.

**Current testing release: v0.3.1-rc.1.** This release supersedes the initial v0.2.0-rc.1 candidate, which had a JavaScript asset registration problem in Elementor 4.

## What it does

- Adds an **ACF** tab with the official ACF icon inside Elementor Page Settings.
- Finds ACF field groups whose WordPress location rules match the current post or page.
- Renders **ACF's own field controls**, including text, textarea, select, true/false, image, and Pro repeater fields. No substitute implementations.
- Retains native repeater behavior: Add Row, blank new row, drag-to-reorder, Remove confirmation, and saving the resulting order.
- Uses the native WordPress Media Library modal for ACF image selection.
- Provides a separate **Save ACF Fields** button, with success and validation messages. Changes remain available after reopening Elementor.
- Uses a dedicated WordPress AJAX endpoint with edit-post permission, document nonce, ACF field group allowlisting, and ACF validation.
- Styles the controls for Elementor's sidebar and light/dark user-interface colors.

## Requirements and compatibility

- WordPress 5.8+ and PHP 7.4+.
- Elementor and Advanced Custom Fields active. **ACF Pro is needed for Pro field types such as Repeater.**
- User account with permission to edit the document.

**Browser-verified environment (October 29, 2026):** WordPress on Solutions Moco, Elementor 4.3.5, Elementor Pro 4.3.2, and ACF Pro 6.8.10.

The plugin includes **no proprietary ACF Pro, Elementor Pro, or Font Awesome assets**. It uses the licensed site's installed ACF and Elementor APIs and does not bypass licenses.

## Install

1. Back up your WordPress installation and test on staging first.
2. Download the versioned, **WordPress-installable ZIP** from [GitHub Releases](https://github.com/HasheCS/hashe-elementor-acf-editor/releases). Do not use GitHub's automatic source-code ZIP for plugin installation.
3. In WordPress, go to **Plugins → Add New → Upload Plugin** and activate Hashe Elementor ACF Editor.
4. Ensure Elementor and ACF (ACF Pro for repeaters) are active.
5. Create an ACF field group with location rules matching the page or post you want to edit.
6. Open that document in Elementor, choose **Page Settings → ACF**, edit its values, and click **Save ACF Fields**.

**ACF saving is separate from Elementor's Update/Publish operation.** Elementor layout changes still use Elementor's normal save workflow.

## Why this integration matters

Elementor 4 resets the WordPress script/style registries when initializing its editor. ACF registers its native field assets earlier, on WordPress init. Without re-registering ACF's actual assets after Elementor's reset, forms may look correct but image selectors, repeaters, and other dynamic ACF controls are unresponsive.

This plugin restores ACF core and Pro asset registration within Elementor, initializes ACF field objects inside the dynamic Page Settings panel, and invokes ACF's standard value-saving functions. The field behavior remains under ACF's ownership.

## Browser acceptance on Solutions Moco

The following operations were run in an isolated authenticated Chrome browser on the actual Elementor editor:

| Test | Result |
|---|---|
| ACF tab icon and native field rendering | Passed |
| Add a blank repeater row | Passed |
| Edit and save nested repeater field values | Passed |
| Drag a row to reorder and retain its position after reload | Passed |
| Open ACF image picker, switch to Media Library and select an existing image | Passed |
| Save and persist text, image and repeater data after reopening Elementor | Passed |
| Remove a row with ACF's native confirmation and persist the deletion | Passed |
| Clear ACF's stale unsaved-changes prompt after successful AJAX save | Passed |
| Restore QA test data and return test page to draft | Passed |

See [QA.md](QA.md) for scope and limitations. Native WYSIWYG, third-party field types and every Elementor/ACF version combination have **not** been exhaustively tested; this remains a pre-release for broader community testing.

## Security and maintenance

Only authenticated users with WordPress edit permission for the specific document can submit changes. Server-side saves validate a document-specific WordPress nonce and accept only top-level field keys from ACF groups assigned to that document. ACF handles its own field validation and type-specific storage.

When installing on a new WordPress version or with third-party custom fields, first test on staging and report any compatibility issues through GitHub Issues.

The plugin does not alter existing ACF field-group definitions or Elementor templates. ACF repeater Table layouts are **shown as stacked blocks in Elementor's narrow sidebar only**; their saved ACF settings remain unchanged.

## License and contribution

[GPL-2.0-or-later](LICENSE). Copyright Hashe Computer Solutions.

See [CONTRIBUTING.md](CONTRIBUTING.md). Do not submit customer content, private URLs, credentials, or proprietary plugin files.
