=== Hashe Elementor ACF Editor ===
Contributors: hashe
Tags: elementor, acf, custom fields, repeater, media library
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 0.3.1-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Edit ACF fields in Elementor Page Settings using native ACF controls, without an admin iframe.

== Description ==

Hashe Elementor ACF Editor adds a native ACF tab to Elementor Page Settings. It automatically finds ACF groups assigned to the current page or post and renders native ACF field controls, with secure separate AJAX saving.

This pre-release has been browser-tested for image selection from the WordPress Media Library, adding/removing/reordering repeater rows, nested field persistence, save-and-reopen, and form validation on Elementor 4.3.5 and ACF Pro 6.8.10. ACF Pro is required for its Pro field types.

The plugin fixes the Elementor editor asset-registry reset that can otherwise prevent ACF core/Pro controls from initializing. No ACF, Elementor or paid-plugin binaries are bundled.

== Installation ==

1. Back up the website and obtain the versioned WordPress ZIP from GitHub Releases.
2. Upload under Plugins > Add New > Upload Plugin and activate.
3. Activate Elementor and Advanced Custom Fields (ACF Pro for repeater fields).
4. Create an ACF field group assigned to the relevant page or post.
5. Open that document in Elementor > Page Settings > ACF.
6. Update values and click Save ACF Fields.

== Changelog ==

= 0.3.1-rc.1 =
* Restore native ACF JavaScript and style registrations after Elementor 4 resets WordPress asset registries.
* Load and initialize ACF core and Pro field objects inside Elementor's dynamic controls panel.
* Preserve native Media Library image selection and repeater add/remove/reorder operations.
* Display the official ACF tab icon and improve sidebar field layout.
* Save field values and nested repeater order with permission and nonce validation.
* Clear ACF's own unsaved-changes warning after successful AJAX save.
* Completed authenticated browser tests on Solutions Moco; broader compatibility remains in pre-release testing.

= 0.2.0-rc.1 =
* First public Hashe-branded, iframe-free ACF editing candidate.
* Native field rendering, post-specific ACF matching and secure AJAX save.
* Superseded by 0.3.1-rc.1 due to Elementor asset initialization issue.
