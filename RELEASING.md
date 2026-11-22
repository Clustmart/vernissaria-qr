# Releasing the plugin

The plugin is published on **wordpress.org** (slug `vernissaria-qr`, account
`vernissaria`). wordpress.org uses **SVN**, not git. This repo (GitHub) is the
source; a release copies the committed files into SVN.

Overview: bump version → merge to `main` → build zip → **test on staging** → publish to
wordpress.org → tag on GitHub.

## 1. Prepare the version (in a PR)

- [ ] Update the version in **three** places, all the same:
  - `vernissaria-qr/vernissaria-qr.php`: the header line `Version:` and the `VERNISSARIA_QR_VERSION` constant
  - `vernissaria-qr/readme.txt`: `Stable tag:`
- [ ] `readme.txt`: add a changelog entry `= x.y.z =`. For important changes also add an
      `== Upgrade Notice ==` entry. WordPress shows that text next to the update link
      on every site's Plugins screen; keep it under 300 characters.
- [ ] `readme.txt`: set `Tested up to:` to the current WordPress version.
- [ ] `CHANGELOG.md`: add an entry.
- [ ] `php -l` on every changed PHP file.
- [ ] Merge the PR to `main`, then `git checkout main && git pull`.

## 2. Build the zip

```bash
scripts/release-wporg.sh x.y.z
```

The script:
1. checks the three version numbers match and that a changelog entry exists;
2. builds `dist/vernissaria-qr-x.y.z.zip` from **git HEAD**, so only committed files
   go in, and drops `.DS_Store` and `*.map`;
3. prepares the wordpress.org SVN working copy in `../wporg-svn`: trunk is synced and
   the tag is created, but **nothing is uploaded**.

You can re-run it any time, e.g. after a fix found during testing.

## 3. Test on staging (before publishing)

Install the zip on a staging site: Plugins → Add New → **Upload Plugin** → choose the
zip → **Replace current with uploaded**.

> ⚠️ **If staging is a copy of the live site's database**, its posts carry the *live*
> QR codes (`_vernissaria_redirect_key`). Saving such a post on staging makes the plugin
> point the **printed live QR code** to the staging URL.
>
> - Since November 2026 (API with keys + plugin 1.4.0) this is blocked **once staging is connected**: the
>   staging key is only valid for `staging.…`, so the API answers 403.
> - So: **click Connect first**, before editing anything.
> - For an older version or an unconnected staging site, don't edit copied posts.

Checklist:
- [ ] The plugin shows the new version and activates without errors. With
      `WP_DEBUG` on, look at `wp-content/debug.log`.
- [ ] The Dashboard shows the notice *"…requires a free API key…"*, and "Remind me later" hides it.
- [ ] Settings → Vernissaria QR → **Connect** → *"Connected for staging.example.com (key vqr_…)"*.
      Staging must be reachable from the internet over **HTTPS without a password**
      (no HTTP basic auth), because the API calls back to it. If Connect fails, the
      message says why.
- [ ] The notice is gone, and the Dashboard visitor-statistics widget loads.
- [ ] Create a **new** test post → Publish → the "Artwork Details" box shows a QR code and a redirect key.
- [ ] Change the title and update → no error. The new label shows in the API stats.
- [ ] Settings → Print QR Codes → Generate PDF works.
- [ ] Optional, on the API server: `python -m app.manage list-keys` shows the staging
      domain with a recent "last used".
- [ ] Afterwards, delete the test post. Its QR code stays in the API under the staging
      domain; that's harmless.

## 4. Publish to wordpress.org

The script printed the command. Review the list of changes, then:

```bash
cd ../wporg-svn && svn ci -m "Release x.y.z" --username vernissaria
```

The password is the **SVN password** from your wordpress.org profile → *Account &
Security*. It's a separate password from your login.

- [ ] Within a few minutes, https://wordpress.org/plugins/vernissaria-qr/ shows the new
      version. Sites see the update in their dashboard; sites with auto-updates install it.
- [ ] Assets on the plugin page (banner, icon, screenshots) live in SVN `assets/` and
      are not touched by the script.

## 5. Tag on GitHub

```bash
git tag vx.y.z && git push origin vx.y.z
gh release create vx.y.z dist/vernissaria-qr-x.y.z.zip --title "x.y.z" --notes "See CHANGELOG.md"
```

## Notes

- **API first.** If a release depends on new API endpoints, deploy the API
  (`Clustmart/vernissaria-qr-api`, `docs/DEPLOYMENT.md`) **before** publishing the plugin.
- `make_zip.sh` is an older local helper (gitignored). Use `scripts/release-wporg.sh`.
