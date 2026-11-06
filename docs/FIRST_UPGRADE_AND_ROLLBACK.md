# Hashe Field Editor: first adoption of native WordPress updates

**Release scope:** independent Hashe/GitHub **v0.5.1 → v0.6.0**, after the new stable release has passed approval and been publicly published. This document does **not** approve or publish that release.

## Why a one-time manual upgrade is required

The currently distributed v0.5.1 independent plugin ZIP predates the new Hashe native WordPress update-client bootstrap. An old installation **cannot be relied upon** to discover its very first upgrade automatically. Once the verified v0.6.0 independent edition has been installed in the original location, future approved stable versions can be offered through WordPress's built-in update interface, using the Hashe metadata feed and SHA-256-verified immutable GitHub assets. Optional Hashe installation reporting is never needed for updates.

## Controlled first adoption (WP administrator)

1. Check that a new **stable** v0.6.0 release and asset `hashe-elementor-acf-editor-v0.6.0.zip` actually exist at https://github.com/HasheCS/hashe-elementor-acf-editor/releases. Do **not** use GitHub's automatically generated Source code ZIP, a PR build, a development ZIP, or a WordPress.org-specific ZIP.
2. Verify the published asset's SHA-256 against its release checksum file and the independent update metadata from https://plugins.hashe.com/. A matching version name alone is insufficient; abort if the two sources disagree.
3. Take and independently verify a WordPress files **and database** backup, with a tested restoration path. First perform this upgrade on staging and document current functional behavior. Check WordPress 6.5+, PHP 7.4+, compatible Elementor and Advanced Custom Fields/ACF Pro.
4. WordPress Admin > Plugins > Add New Plugin > Upload Plugin: upload `hashe-elementor-acf-editor-v0.6.0.zip`. When WordPress identifies the already installed plugin, choose **Replace current with uploaded** (available on supported current WordPress installations). Preserve the original folder and main-file identity: `hashe-elementor-acf-editor/hashe-elementor-acf-editor.php`. Do not uninstall the prior plugin first merely to upgrade, or move it to a different slug.
5. Verify the plugin is active and displays version **v0.6.0**, existing settings/content remain intact, and there are no new PHP/WordPress errors. ACF field group values reside in normal WordPress/ACF storage. Before replacement, export relevant field groups, confirm that existing field values remain, and check Elementor Page Settings > ACF after the upgrade. ACF Pro is a separate licensed dependency. The WordPress.org package has a **different** plugin slug (`hashe-field-editor`) and must not silently replace this independent installation.
6. Confirm that native update checks are served through the **independent Hashe channel** for later approved versions (WordPress's normal Update now workflow), and that installation-reporting consent remains off unless independently and explicitly granted.
7. Reconcile the before/after baseline and record the actual installed ZIP SHA-256, time, site, result and recovery reference. Do not infer successful adoption from a website handoff/redirect alone.

## Abort and rollback

If the ZIP hash, folder identity, compatibility, install/update result or essential functionality fails, **stop**. Restore the exact prior verified plugin folder, configuration and database state from the tested site backup as appropriate; never replace the historic published ZIP in GitHub. Do not automatically revert unrelated site changes made after the backup. Verify WordPress admin, active plugins and the affected page features after recovery, and report the incident without hidden telemetry.

**Channel invariant:** Hashe Field Editor independent updates originate from the published approved Hashe/GitHub release and the Hashe update feed, not from an unreviewed branch or WordPress.org ZIP. The first-adoption procedure does not publish or approve anything.
