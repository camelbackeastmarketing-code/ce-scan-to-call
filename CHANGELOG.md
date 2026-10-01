# Changelog

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
