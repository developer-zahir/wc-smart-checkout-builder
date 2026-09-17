---
name: Plugin Release Workflow
description: Automatically handle version bumping and GitHub releases for WC Smart Checkout Builder.
---

# Plugin Release Workflow

Whenever a new feature is completed or significant bug fixes are resolved for the **WC Smart Checkout Builder** plugin, you **MUST** automatically perform the following release steps without asking the user:

1. **Bump Version:** Increment the plugin version number in `wc-smart-checkout-builder.php` (e.g., from `1.1.8` to `1.1.9`).
2. **Commit Changes:** Run `git add .` and `git commit -m "feat: [brief description of features]"` to commit the new features.
3. **Push to Main:** Run `git push` to push the changes to the origin.
4. **Tag Release:** Create a new git tag matching the bumped version (e.g., `git tag 1.1.9`).
5. **Push Tag:** Push the tag to GitHub (e.g., `git push origin 1.1.9`) so that the `class-updater.php` script can detect it and push the update to the WordPress dashboard.

Do this automatically as the final step of any feature request.
