# PERMANENT PROJECT RULE — GitHub Releases, ZIP Builds & Automatic Updates

This is a **permanent project workflow rule** for the WC Smart Checkout Builder plugin.

Follow this workflow for **every future feature, bug fix, improvement, or code change** in this project automatically.

## Workflow Requirements

1. **Version Bump**: Every release-worthy change requires updating the plugin version in `wc-smart-checkout-builder.php` (e.g. `MAJOR.MINOR.PATCH`).
2. **Build ZIP**: Create a production plugin ZIP containing the actual plugin folder at the root (e.g. `wc-smart-checkout-builder.zip` -> `wc-smart-checkout-builder/...`).
3. **Clean ZIPs**: Keep only the latest version ZIP file in the designated project folder (delete older `.zip` files from the working directory).
4. **Git Commit & Push**: Commit changes to Git and push to GitHub (`developer-zahir/wc-smart-checkout-builder`). Do NOT delete git history.
5. **GitHub Release**: Create a GitHub tag (e.g. `v1.3.0`) and Release (e.g. `WC Smart Checkout Builder v1.3.0`) with a short, concise changelog.
6. **Attach ZIP**: Attach the production ZIP to the GitHub Release.

## Automatic Updater
- The plugin includes an automatic updater (`class-updater.php`) that checks GitHub for releases.
- The updater relies on the ZIP attached to the GitHub release, comparing the installed version against the GitHub release tag/version.

**NEVER stop after writing the code. A feature is only fully complete after this release/update workflow is completed.**
