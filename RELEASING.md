# Releasing a new version

## 1. Make and test the change
- Edit files under `ce-scan-to-call/`.
- Test on a staging copy or one low-risk site first: desktop click opens the popup, Esc/X/outside click closes it, the button link works, a phone (or Chrome mobile emulation) still dials directly, settings still save.

## 2. Pick the version number
- Fix only: `1.1.1`
- New feature, nothing breaks: `1.2.0`
- Something existing behaves differently: `2.0.0`

## 3. Bump it in all three places (the release build fails if they differ)
1. `ce-scan-to-call/ce-scan-to-call.php`: the `Version:` header line
2. `ce-scan-to-call/ce-scan-to-call.php`: `define( 'CESC_VERSION', 'x.y.z' );`
3. `ce-scan-to-call/readme.txt`: `Stable tag:`

Then add a section to `CHANGELOG.md` and to the changelog in `readme.txt`.

If a saved setting changed shape, add a migration in `cesc_maybe_upgrade()` and keep `cesc_defaults()` in sync.

## 4. Commit and tag
```
git add -A
git commit -m "Release 1.1.1"
git push
git tag v1.1.1
git push origin v1.1.1
```
Pushing the tag triggers the **Release** workflow (Actions tab). It checks the versions match, builds `ce-scan-to-call.zip`, and publishes a GitHub release with that zip attached.

## 5. Roll out
- Sites show "Update available" within about 12 hours, or immediately after clicking **Check for updates** on the Plugins screen.
- Update your own site first, confirm it works, then the client sites.

## Rolling back
- Delete the bad release and its tag on GitHub (or publish a fixed higher version, which is safer).
- On a broken site, upload the previous version's zip from the Releases page (Plugins > Add New > Upload, then "Replace current with uploaded").

## Yearly housekeeping
- Check qrcode-generator and Plugin Update Checker for new versions.
- Test against the current WordPress version and update `Tested up to` in `readme.txt`.
