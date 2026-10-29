# Verification and release scope

**Public release:** v0.5.0, derived from the user-accepted `0.5.0-qa.14` QA build. Only plugin version and author/license metadata differ in the functional PHP source; the shipped JavaScript, CSS and extended-field adapter come from the accepted QA files.

## Verified in the October 2026 QA environment

- Elementor 4.3.5, Elementor Pro 4.3.2 and ACF Pro 6.8.10.
- Exactly 36 built-in ACF/ACF Pro field types registered as Elementor controls; three of these are presentation-only and do not create data values.
- ACF field groups matched the current editable WordPress page, with an inline Save ACF Fields button following the fields.
- An authenticated real browser loaded controls without an embedded admin iframe/ACF form.
- Save with no changes issued no ACF AJAX write.
- Changed text field: AJAX save succeeded, one field updated, editor reopened with the saved value.
- The earlier duplicate ACF values in Elementor Page Settings were removed and ACF became the source of truth.
- Link, Google Map, Icon Picker, Group, Flexible Content and Relationship: structured edits saved and reopened successfully.
- Thirty-three baseline ACF data field values were restored and independently reconciled after browser tests; temporary browser access was revoked.
- PHP and JavaScript mapping/payload tests were run against the isolated QA installation.

## Limits and future compatibility testing

- This is not proof that every ACF type behaves identically for every possible nested or complex configuration.
- Third-party ACF field extensions, WordPress multisite, multilingual plugins, very large selectable item lists and all future versions were not exhaustively tested.
- The current built-in post, term and user selectors enumerate a maximum of 500 options per query. Some use cases may need dynamic search/pagination.
- Elementor-native composite editors differ visually from ACF's WordPress-admin editing screens.
- Customers should take backups and test advanced field mappings on staging before deployment.

No credentials, customer records, paid plugin binaries or proprietary ACF source are distributed.

License: [GPL-2.0-or-later](LICENSE). Maintainer: [Hashe Computer Solutions (Pvt) Ltd](https://www.hashe.com/).
