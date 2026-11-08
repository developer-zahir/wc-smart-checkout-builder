## v1.9.22 — Auto-Update System Overhaul

### Critical Fix: WordPress Dashboard Update Detection

The auto-update system has been completely rebuilt with a 3-tier fallback strategy to ensure reliable update detection from the WordPress plugins dashboard.

### Changes

- **NEW: `update.json` manifest** — A lightweight JSON file at the repo root that the updater reads directly via raw GitHub URL (no API rate limits, always available)
- **Rebuilt `class-updater.php`** — Clean OOP updater with 3-tier fallback:
  1. `update.json` raw file (fastest, no rate limit)
  2. GitHub Releases API (60 req/hr)
  3. GitHub Tags API (last resort)
- **Fixed ZIP packaging** — ZIP now correctly contains plugin folder at root (`wc-smart-checkout-builder/`) so WordPress can extract and activate without folder rename issues
- **Improved admin notice** — Manual "Check for Update" link in plugin row now shows clear status messages
- **Cache improvements** — 12-hour cache with proper invalidation on update

### How to Update

1. Go to **WordPress Admin → Plugins**
2. Click **"Check for Update"** in the WC Smart Checkout Builder row
3. Or wait for WordPress automatic background check
4. Click **"Update now"** when the update appears

### Included: WooFlexiBundle Builder Sub-Plugin

A new Elementor widget sub-plugin for dynamic product bundle grids with:
- Multi-product selection grid
- Tiered discounts (Repeater control)
- Variable product variations inline
- AJAX cart sync + smooth scroll to checkout
