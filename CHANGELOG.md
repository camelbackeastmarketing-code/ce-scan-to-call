# Changelog

## Unreleased
- readme.txt: Tested up to 7.1 (the plugin has run on WordPress 7.1.3 at camelbackeast.com since 1.2.1).
- Release build: refuses to publish if `Tested up to` is older than the current WordPress release.

## 1.2.1
- Camelback East contact box (settings-page sidebar) and the popup number override placeholder now use (623) 456-7490 / `+16234567490`.

## 1.2.0
- Settings page sidebar: "what it does / what it doesn't do" notes and an optional Camelback East contact box with a locally generated QR code (setting `show_contact_box`, default on; filter `cesc_contact_box`).
- "Phone number override" relabeled as display-only, with a note that it does not fix phone links.
- Placeholder number is now +16027301024.
- Added `license.txt` (GPL v2) inside the plugin folder.

## 1.1.0
- Automatic updates from GitHub releases (Plugin Update Checker 5.6).
- Update URI header and an upgrade routine (`cesc_maybe_upgrade`) for future setting migrations.
- Settings page shows the installed version.

## 1.0.0
- Initial release: desktop `tel:` links open a QR-code popup; settings page for text, button, colors and font; GTM/GA4 dataLayer events.
