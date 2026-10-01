# CE Scan-to-Call

WordPress plugin by Camelback East Marketing. When a **desktop** visitor clicks a phone (`tel:`) link, a popup shows a QR code they can scan to call from their phone. Phones and tablets keep normal tap-to-call.

- One site-wide listener catches every `tel:` link (header, footer, page-builder buttons, links added later).
- Settings under **Settings > Scan-to-Call**: heading, button text/link, colors, font, optional fixed number.
- Optional GTM/GA4 dataLayer events: `scan_to_call_open`, `scan_to_call_close`, `scan_to_call_schedule_click`.
- No external requests from the popup. QR codes are generated in the browser.
- The settings page sidebar explains what the plugin does and does not do. It does not repair broken or partial phone links, so fix the links on the site itself.
- Add `?cesc_test=1` to any URL to force the popup on any device.

## Install

1. Download `ce-scan-to-call.zip` from the [latest release](../../releases/latest).
2. WordPress admin: **Plugins > Add New > Upload Plugin**, choose the zip, activate.
3. **Settings > Scan-to-Call**: set the button link and colors, save.

After the first install, updates appear on the normal Plugins screen (use **Check for updates** to force a check).

## Repo layout

```
ce-scan-to-call/          the plugin (this folder is what gets zipped)
  ce-scan-to-call.php     main file: settings, front-end enqueue, updater, upgrade routine
  assets/                 popup JS and the bundled QR library
  lib/plugin-update-checker/   GitHub auto-update library (MIT)
  readme.txt, uninstall.php
.github/workflows/release.yml  builds the zip and publishes a release when you push a v* tag
CHANGELOG.md, RELEASING.md
```

## Making changes

See [RELEASING.md](RELEASING.md). Short version: edit, bump the version in three places, update the changelog, push a tag.

## Rules that keep updates safe

- Never rename the `cesc_settings` option. Every site's saved settings live there.
- Give every new setting a default in `cesc_defaults()`.
- If a setting changes shape, migrate it in `cesc_maybe_upgrade()`.
- Sites must not have to re-save settings after an update.

## Private repo option

The plugin contains no secrets, so a public repo needs no configuration. If the repo is ever made private, add a read-only fine-grained token (Contents: read) to each site's `wp-config.php`:

```php
define( 'CESC_GITHUB_TOKEN', 'github_pat_xxx' );
```

## Third-party code

- [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) by Kazuhiko Arase, MIT.
- [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) by Janis Elsts, MIT.

License: GPL-2.0-or-later.
