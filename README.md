# Hashe Elementor ACF Editor

**Free, open-source WordPress plugin by [Hashe Computer Solutions (Pvt) Ltd](https://www.hashe.com/).**

Edit the Advanced Custom Fields assigned to a page or post from **Elementor → Page Settings → ACF**. The controls belong to Elementor's sidebar; no WordPress editing screen, embedded ACF form, or iframe is used.

**Current release: v0.5.0** · **License: [GNU GPL v2 or later](LICENSE) (`GPL-2.0-or-later`)**

## Features

- Automatically detects active ACF field groups whose location rules match the current Elementor document.
- Maps all **36 built-in ACF and ACF Pro field types** to Elementor controls, including the three presentation-only types (Message, Tab and Accordion). ACF Pro is required for Pro-only field types.
- Provides Elementor-native inputs, switches, media controls, select controls, Gallery and Repeater rows with add/remove/reorder support, and custom Elementor-based editors for composite built-in types.
- Includes editing for Link, Post Object, Page Link, Relationship, Taxonomy, User, Google Map, Icon Picker, Group, Flexible Content and Clone.
- Places **Save ACF Fields** below the fields, separate from Elementor's normal page **Update/Publish** action.
- Uses ACF for field validation and storage. Saves **changed fields only**, protecting unchanged values from format conversion.
- Removes obsolete mirrored ACF values from Elementor Page Settings so ACF remains the source of truth.
- Checks the WordPress user's post-edit permission, a document-specific nonce, and location-matched field keys before saving.

## Requirements

- WordPress 5.8 or later; PHP 7.4 or later.
- Elementor installed and active.
- Advanced Custom Fields installed and active; **ACF Pro** for field types available only in ACF Pro.
- An account with permission to edit the selected page or post.

The browser-accepted QA environment used Elementor 4.3.5, Elementor Pro 4.3.2 and ACF Pro 6.8.10 in October 2026. The PHP and JavaScript checks run in GitHub Actions. Other versions, third-party field extensions, very large selectors, deeply nested field structures and multisite installations have not been exhaustively verified.

No ACF Pro, Elementor Pro or other proprietary licensed plugin files, license keys, or customer data are bundled.

## Install

1. Back up the target WordPress website and preferably test in staging first.
2. Download the **versioned plugin ZIP** from [GitHub Releases](https://github.com/HasheCS/hashe-elementor-acf-editor/releases), for example `hashe-elementor-acf-editor-v0.5.0.zip`. Do **not** use GitHub's automatic "Source code (zip)" for installation.
3. In WordPress go to **Plugins → Add New Plugin → Upload Plugin**. Select the versioned ZIP, install, and activate it.
4. Ensure Elementor and ACF are active; activate ACF Pro for its Pro-only fields.
5. Create or use an ACF field group assigned to the page or post you wish to edit.
6. Edit that page with Elementor and open **Page Settings → ACF**.
7. Make changes and click **Save ACF Fields** at the bottom. Continue to use Elementor's main Update/Publish button for Elementor layout changes.

## Data and editing behavior

The controls are Elementor-native representations of existing ACF field definitions, not a duplicate ACF editor embedded in a frame. The field groups, their saved values, ACF validation and permissions remain managed by WordPress and ACF. Saving through the ACF tab does not publish the page.

Complex built-in types can differ in presentation from the native ACF admin editor. For example, a Relationship uses an ordered Elementor repeater of post selectors; Google Map exposes address and coordinates; Flexible Content presents its layouts through an Elementor repeater. Large sites and advanced configurations should test those behaviors on staging.

The built-in post, term and user selectors query up to 500 available options per request in the current implementation; very large data sets may require future search/pagination support. Third-party custom ACF field types are not in this release's scope.

## Verification and limitations

The accepted QA build was tested in an authenticated Elementor browser session: controls mounted, Save was visible at the bottom, 33 data-bearing field values were serialized, changed-only saving and reloading worked, and Link, Google Map, Icon Picker, Group, Flexible Content and Relationship were changed, saved and reopened. The 33 baseline values were restored and verified after tests. The three other built-in types are presentation controls without saved data.

Those tests are meaningful acceptance evidence, not a guarantee of identical behavior for every configuration of all 36 field types. See [QA.md](QA.md) for the validation scope and known gaps.

## Support and contributions

Use [GitHub Issues](https://github.com/HasheCS/hashe-elementor-acf-editor/issues) to report reproducible problems. Include your WordPress, Elementor and ACF versions, field type and non-sensitive reproduction steps. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License, author and trademarks

**Developed and maintained by [Hashe Computer Solutions (Pvt) Ltd](https://www.hashe.com/).**

Copyright © 2026 Hashe Computer Solutions (Pvt) Ltd. This plugin is provided free of charge under **[GNU GPL v2 or later](LICENSE)** (`GPL-2.0-or-later`), which permits use, modification and redistribution subject to its conditions.

Elementor and Advanced Custom Fields (ACF) are third-party products and trademarks. This independent Hashe plugin is not endorsed by or affiliated with their respective owners.
