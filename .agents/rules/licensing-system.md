---
name: Licensing System Architecture & Rules
description: Complete architecture, API contracts, caching, and release rules for the WC Smart Checkout Builder licensing system.
---

# WC Smart Checkout Builder - Licensing System Documentation

This document explains the exact architecture and implementation of the licensing system used in **WC Smart Checkout Builder**. Refer to this document whenever modifying licensing or release logic.

---

## 1. Core Principles
1. **Client-Only Codebase**: This repository contains **ONLY** the client plugin (`wc-smart-checkout-builder`). The licensing server code is maintained separately by the user on their own server (`app.developerzahir.com`). **NEVER** add or bundle server plugins or server zip files in this codebase or in GitHub releases.
2. **Release Asset Safety**: The GitHub release must **ONLY** have `wc-smart-checkout-builder.zip` attached. `includes/class-updater.php` strictly targets `wc-smart-checkout-builder.zip` to prevent client sites from downloading unexpected packages during WordPress dashboard 1-click updates.

---

## 2. API Communication & Endpoints

### A. Client to Server Check / Activation Endpoint
- **URL**: `https://app.developerzahir.com/tp-server/v1/check`
- **Fallback Query**: `https://app.developerzahir.com/?tp_action=check_license`
- **Notice**: Custom endpoint (no `/wp-json/` required to bypass REST API security locks).
- **HTTP Method**: `POST`
- **Request Parameters**:
  | Field | Description | Example |
  |---|---|---|
  | `key` | License key entered by user | `TP-XXXX-XXXX-XXXX-XXXX` |
  | `url` | Normalized client domain | `example.com` |
  | `plugin` | Product identifier | `WC Smart Checkout Builder` |
  | `action` | Action type | `activate` or `check` |

- **Server JSON Response**:
  ```json
  {
    "status": "active",
    "message": "License is verified and active."
  }
  ```
  Failure cases:
  - Key missing/not found: `{"status": "invalid", "message": "License key does not exist."}`
  - Key inactive: `{"status": "inactive", "message": "License key is inactive."}`
  - Domain mismatch: `{"status": "inactive", "message": "Domain mismatch! Key is registered to X, requested from Y. Subdomains require separate licenses."}`

### B. Subdomain & Domain Normalization Rules
- **Root Domain Equivalence**: `www.site.com` and `site.com` are treated as identical.
- **Strict Subdomain Isolation**: Distinct subdomains (e.g. `shop.site.com`, `app.site.com`, `dev.site.com`) require separate license keys and do NOT match the root domain or each other.
- **Client Domain Normalizer**: Implemented in `License_Manager::get_site_domain()`.

### C. Server to Client Instant Revocation Webhook
- **Client URL**: `https://{client-domain}/tp-client/v1/update-license` (or `?tp_action=update_license`)
- **HTTP Method**: `POST`
- **Payload**: `{"key": "TP-XXXX-XXXX-XXXX-XXXX"}`
- When a license is trashed or set to inactive on the server, the server sends this webhook to immediately purge the 6-hour cache on the client site.

---

## 3. Client Caching & Graceful Fallback
- **Cache**: 6-hour WordPress transient (`tp_license_status_{md5(key)}`).
- **Offline / Downtime Graceful Fallback**: If `wp_remote_post` fails or the server is temporarily down, the client plugin retains the last known verified status so production client sites and checkout funnels are never broken by transient network issues.

---

## 4. UI, Diagnostics & Timeout Protection (`class-plugin.php`)
- **Interactive Countdown**: When clicking "Activate License", the button shows a 10-second countdown (`অ্যাক্টিভেট হচ্ছে... (10s)`).
- **Hard Timeout (`AbortController`)**: If the request takes longer than 10 seconds (due to cURL hangs or firewall delay), the button unlocks automatically with an informative Bengali alert.
- **Safe Nonce Verification**: Uses `check_ajax_referer('wcsc_license_nonce', 'nonce', false)` so expired nonces return descriptive JSON messages rather than raw `-1` integers.
- **Persistent Error Log**: Previous activation errors are saved to `wcsc_last_license_error` and displayed in a warning banner on the License tab.
- **Server Connection Diagnostics**: An on-page "সার্ভার সংযোগ টেস্ট করুন" button triggers `wcsc_test_license_connection` to measure ping latency and verify if the host allows outbound cURL calls to `app.developerzahir.com`.
