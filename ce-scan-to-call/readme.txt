=== CE Scan-to-Call ===
Contributors: camelbackeast
Tags: phone, tel links, qr code, popup, click to call
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Desktop visitors who click a phone link get a QR-code popup so they can scan and call from their phone.

== Description ==

Phone links (tel:) work on phones but do nothing useful on most desktop computers. This plugin catches those clicks on desktop and shows a popup with a QR code (generated in the browser from the number that was clicked), the number itself, and an optional button such as "Schedule a consultation". Phones and tablets keep normal tap-to-call.

* Works site-wide with one listener: header, footer, page-builder buttons, Kadence blocks, links added later.
* Configure text, button, colors and font under Settings > Scan-to-Call.
* Optional GTM/GA4 dataLayer events: scan_to_call_open, scan_to_call_close, scan_to_call_schedule_click.
* Accessible: Esc / click-outside / X to close, keyboard focus trap, focus returns to the link.
* The popup makes no external requests. Bundles qrcode-generator (MIT) and Plugin Update Checker (MIT); the update check contacts GitHub from the WordPress admin only.
* Add ?cesc_test=1 to any URL to force the popup on any device for testing.
* Developers: filter `cesc_config` to override any setting in code.

== Installation ==

1. Upload the ce-scan-to-call folder (or the zip) via Plugins > Add New > Upload Plugin.
2. Activate.
3. Go to Settings > Scan-to-Call, set your button link and colors, and save.

== Changelog ==

= 1.1.0 =
* Automatic updates from GitHub releases (normal "Update available" notice on the Plugins screen).
* Added Update URI header and an upgrade routine for future setting migrations.
* Settings page shows the installed version.

= 1.0.0 =
* Initial release.
