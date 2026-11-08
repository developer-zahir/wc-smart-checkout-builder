## v1.9.21 — WooFlexiBundle Builder Sub-Plugin Added

### New Features
- WooFlexiBundle Builder: A brand new Elementor widget sub-plugin for dynamic product bundle grids with direct AJAX checkout
  - Product Query Control: Multi-select dropdown to choose specific WooCommerce products (Simple & Variable)
  - Min/Max Quantity Rules: Set minimum required and maximum allowed item selections before checkout
  - Tiered Discount Settings (Repeater): Define custom discounts by item count (2 items = 10%, 3 = 15%, 5+ = 20%) with percentage or fixed amount support
  - Layout Controls: Responsive grid columns (Desktop/Tablet/Mobile), card spacing, border styles, active highlight color, badge text
  - Scroll Target: Smooth-scroll to any checkout section (configurable selector)
  - Interactive Product Grid: Card selection toggle with animated checkmark, SELECTED badge, and visual active state
  - Variable Product Variations: Inline attribute dropdowns on each card
  - Quantity +/- Buttons: Per-item increment/decrement controls
  - Dynamic Summary Bar: Real-time price with tiered discount, item count, strikethrough original price, and discount tag
  - AJAX Cart Sync: Passes selected items with variations and quantities to WooCommerce cart without page reload, then triggers native checkout update and smooth scrolls
  - Auto Tiered Discount: Applied via woocommerce_cart_calculate_fees hook as a negative fee

### Files Added
- wooflexibundle-builder/wooflexibundle-builder.php
- wooflexibundle-builder/includes/elementor-widget.php
- wooflexibundle-builder/assets/js/frontend.js
- wooflexibundle-builder/assets/css/style.css

