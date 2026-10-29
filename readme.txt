=== Hashe Elementor ACF Editor ===
Contributors: hashe
Tags: elementor, acf, acf pro, custom fields, page settings
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Edit WordPress ACF fields in Elementor Page Settings using Elementor-native controls, without an iframe.

== Description ==

Free, open-source WordPress plugin developed and maintained by Hashe Computer Solutions (Pvt) Ltd.
Company: https://www.hashe.com/
Source and issues: https://github.com/HasheCS/hashe-elementor-acf-editor

The plugin registers an ACF tab under Elementor Page Settings, using Elementor-native controls for all 36 built-in ACF and ACF Pro field types. ACF Pro is necessary for Pro-only field types.

Includes Elementor Repeater/Gallery operations; Link, Relationship, Group, Flexible Content, Clone and Google Map editors; permission-aware, nonce-protected, field-key scoped AJAX saving; and a Save ACF Fields button at the bottom of the ACF tab.

ACF remains the source of truth for values. Only changed fields are submitted to avoid rewriting untouched values.

Important: Elementor-native representations of composite fields are not identical in appearance to ACF's WordPress-admin editors. Third-party custom ACF field types, very large selectors, deeply nested structures and all version combinations have not been exhaustively tested.

No ACF Pro, Elementor Pro or other proprietary paid components are bundled. Elementor and Advanced Custom Fields are independent third-party trademarks.

== Installation ==

1. Back up the site and test on staging.
2. Download the versioned installable ZIP from https://github.com/HasheCS/hashe-elementor-acf-editor/releases (not the automatic source ZIP).
3. WordPress > Plugins > Add New Plugin > Upload Plugin, then activate.
4. Activate Elementor and Advanced Custom Fields; use ACF Pro for Pro field types.
5. Create or use ACF field groups matching the page or post.
6. Edit the document in Elementor > Page Settings > ACF.
7. Modify fields and select Save ACF Fields. Save Elementor layout changes separately.

== Frequently Asked Questions ==

= Is this plugin free? =
Yes. It is free, open source, and licensed under GPL-2.0-or-later.

= Is ACF Pro bundled? =
No. ACF Pro and Elementor Pro, where used, are installed and licensed separately.

= Are all third-party field add-ons supported? =
No. The supported scope is built-in ACF and ACF Pro field types.

= Does Save ACF Fields publish the Elementor page? =
No. Elementor's main Update/Publish action is separate.

== Changelog ==

= 0.5.0 =
* First stable public build following user acceptance of the 36 built-in ACF/ACF Pro field-type QA matrix.
* Elementor-native field controls, with an inline bottom Save ACF Fields action; no ACF iframe or embedded form.
* Advanced Elementor-based controls for Relationship, Group, Clone, Flexible Content, Google Map and other built-in ACF types.
* Changed-only field saving and removal of stale ACF mirrors from Elementor Page Settings.
* Standardized Hashe Computer Solutions (Pvt) Ltd attribution and GPL licensing.
