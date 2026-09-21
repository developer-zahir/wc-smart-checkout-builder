# AGENTS.md — WC Smart Checkout Builder

## Project Overview

A WordPress plugin providing an Elementor widget for WooCommerce product variation
selection and checkout customization. Includes a license management system with
server-side verification, transient caching, and instant revocation webhooks.

## Linting & Type Checking

No formal lint or typecheck tools are currently configured in this project
(no `composer.json`, `package.json`, `phpcs.xml`, or `.eslintrc`).

When PHP is available locally, a basic syntax check can be run:

```bash
php -l includes/class-license-manager.php
php -l includes/class-plugin.php
php -l includes/class-checkout-handler.php
php -l includes/class-elementor-widget.php
php -l includes/class-thankyou-widget.php
php -l includes/class-product-handler.php
php -l includes/class-updater.php
php -l includes/class-variation-handler.php
php -l wc-smart-checkout-builder.php
php -l server/class-tp-license-server.php
```

## Key Files

| File | Purpose |
|------|---------|
| `wc-smart-checkout-builder.php` | Plugin bootstrap, dependency checks |
| `includes/class-plugin.php` | Core singleton, CPT registration, settings, admin UI |
| `includes/class-license-manager.php` | Client-side license verification, caching, webhook receiver, alerts |
| `includes/class-checkout-handler.php` | WooCommerce checkout rendering with plugin-owned layout blocks |
| `includes/class-elementor-widget.php` | Elementor widget with content & style controls |
| `includes/class-product-handler.php` | Product detection, search, order bump queries |
| `includes/class-thankyou-widget.php` | Elementor thank-you widget |
| `includes/class-updater.php` | GitHub-based plugin auto-updater |
| `includes/class-variation-handler.php` | Product variation handling |
| `assets/css/widget.css` | Strictly scoped frontend CSS (wrapper: `.wcas-checkout-wrapper`) |
| `assets/js/widget.js` | Frontend JavaScript (guarded by `.wcas-checkout-wrapper` checks) |
| `assets/js/editor.js` | Elementor editor accordion collapse logic |
| `server/class-tp-license-server.php` | Server-side license management (reference, not part of client plugin) |

## CSS Scoping Rules

All frontend CSS MUST be scoped under `.wcas-checkout-wrapper` or
`.wcsc-product-checkout-widget`. Never use bare global selectors such as
`button`, `input`, `.woocommerce`, `#place_order`, or `.woocommerce-NoticeGroup-checkout`
without a plugin-owned wrapper prefix.

## JavaScript Guard Pattern

Global event listeners (`$(document)`, `$(document).ajaxComplete`, etc.) MUST
include an early return guard:

```js
if (!$('.wcas-checkout-wrapper').length) return;
```

DOM queries that extract data from WooCommerce (e.g., order totals, button text)
MUST use `self.$container.find(...)` or `this.$container.find(...)`, never
`$(document).find(...)` or bare `$('...')`.

## License System

See `.agents/rules/licensing-system.md` for the complete architecture, API
contracts, caching rules, and release safety guidelines.
