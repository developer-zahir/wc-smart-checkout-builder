---
name: Plugin Release & Local ZIP Workflow
description: Comprehensive workflow for version bumping, local production ZIP creation, GitHub release, and automatic WordPress dashboard updates for WC Smart Checkout Builder.
---

# Plugin Release & Local ZIP Workflow

Whenever a new feature is completed or significant bug fixes are resolved for the **WC Smart Checkout Builder** plugin, you **MUST** automatically perform the following release steps without asking the user:

## 1. Increment Plugin Version
- Increase the plugin version appropriately in `wc-smart-checkout-builder.php` (both in the plugin header `* Version:` and `define( 'WCSC_VERSION', '...' )`).
- Ensure the new version number is consistent across all relevant files.

## 2. Build the Production Plugin ZIP
- After development and testing, create the final **production-ready WordPress plugin ZIP file** (e.g. `wc-smart-checkout-builder.zip` containing the root folder `wc-smart-checkout-builder/`).
- The ZIP must contain the correct installable WordPress plugin structure (ready to upload directly via `WordPress Dashboard → Plugins → Add New Plugin → Upload Plugin`).
- Exclude unnecessary dev/system files (`.git`, `.agents`, `.kilo`, `.DS_Store`, etc.).

## 3. Keep the Latest ZIP in the Local Project Folder
- Store the newly generated production ZIP in the designated local project folder.
- Delete any previous/old ZIP files from the folder (ensure only the latest production ZIP remains).
- Do not delete source/development files or Git history.

## 4. Commit, Tag, and Release on GitHub
Follow this sequence:
```text
Code changes
↓
Version increment
↓
Production build
↓
Latest ZIP created
↓
Old local ZIP removed
↓
Latest ZIP stored locally
↓
Git commit (e.g. git commit -m "feat/fix: [brief description]")
↓
Git push (origin main)
↓
New GitHub tag (e.g. git tag -a v1.3.6 -m "Release v1.3.6")
↓
Push tag (e.g. git push origin v1.3.6)
↓
GitHub Release with attached production ZIP (via gh release create if gh CLI available or tag push)
```

The ZIP attached to GitHub Release must be the **same production ZIP** that is stored in the local release folder.

## 5. WordPress Dashboard Update Compatibility
- Ensure the release is compatible with `class-updater.php`.
- The update must be detectable and installable directly from `WordPress Dashboard → Plugins → WC Smart Checkout Builder`.

## 6. Final Verification Checklist
Before considering the task complete, verify:
- [x] Plugin version is incremented correctly.
- [x] Production ZIP is generated with valid WordPress plugin directory structure.
- [x] Latest ZIP exists in the local project folder.
- [x] Old ZIP files removed.
- [x] Git changes committed and pushed.
- [x] GitHub tag created and pushed.
- [x] WordPress Dashboard automatic update can detect the new version.

**NEVER stop after writing the code. A feature or fix is only fully complete after this release/update workflow is completed.**
