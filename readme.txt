=== Hashe Elementor ACF Editor ===
Contributors: hashe
Tags: elementor, acf, custom fields, page settings
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 0.2.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Edit ACF fields directly in Elementor Page Settings without a WordPress admin iframe.

== Description ==
Adds an ACF tab to Elementor's Page Settings and renders the ACF fields assigned to the current page/post. Edit field values without leaving Elementor and save them with the dedicated Save ACF Fields button.

Uses real ACF inputs and checks the current user's permission, field assignment, WordPress nonce and ACF field validation before saving. Designed and tested with ACF Pro.

This is a testing release candidate; complete media picker and repeater UI interaction checks before production use.

== Installation ==
1. Download the versioned WordPress-installable ZIP from GitHub Releases.
2. In WordPress select Plugins > Add New > Upload Plugin, install and activate.
3. Ensure Elementor and Advanced Custom Fields Pro are active.
4. Create an ACF field group assigned to a relevant page or post.
5. Edit that page with Elementor, open Page Settings > ACF, and save values.

== Changelog ==
= 0.2.0-rc.1 =
* First public release candidate under Hashe branding.
* Native, iframe-free ACF controls in Elementor Page Settings.
* Location-aware field grouping and permission-checked AJAX saves.
* Preserves ACF image and repeater controls using the active ACF installation.
* Security and functional regression tests added.
