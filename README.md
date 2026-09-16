# wc-smart-checkout-builder

# WooCommerce Product Variation & Checkout Elementor Widget

A lightweight, high-performance Elementor widget for WooCommerce that seamlessly displays simple and variable products, handles button and image swatches, and embeds native WooCommerce checkout in-place.

## Features

- **Elementor Widget Integration:** Drag-and-drop the **Product Variation & Checkout** widget anywhere in Elementor.
- **Auto Product Detection:** Toggle **Use Current Product** to dynamically display products on single product pages or custom templates, or manually select any product.
- **Dynamic Variable & Simple Product Support:** Automatically detects attributes for variable products and generates clean variation swatches.
- **Custom Swatch Types & Labels:** Display options as text/buttons or images with fallback colors/images. Custom attribute titles (e.g., *কালার সিলেক্ট করুন*, *সাইজ সিলেক্ট করুন*) supported.
- **Native WooCommerce Checkout:** Directly renders native billing/shipping forms, payment gateways (Cash on Delivery, bKash, Nagad, Stripe, PayPal, SSLCommerz, etc.), and shipping methods.
- **1-Column & 2-Column Layouts:** Switch between side-by-side (2 columns) or stacked (1 column) layouts.
- **Individual Section Controls:** Toggle visibility for Billing Fields, Order Review ("Your Order"), Shipping Methods, and Payment Gateways.
- **Custom Text & Labels:** Freely customize section headings and table labels (Product, Subtotal, Shipping, Total) in any language.
- **Live Price & Dynamic Order Button:** Show real-time price updates in the "Order Now" button with customizable icons and attention-grabbing, scale-free animations (Rotating Beams, Gentle Bounce, Tada, Glow Pulse).
- **Smooth Loading State:** Backdrop blur with centered modern spinner during cart synchronization.
- **Safe Elementor Editor Preview:** High-fidelity visual preview inside Elementor with zero infinite loading loops or premature checkout submission.
- **Automatic GitHub Updates:** Integrated with GitHub Releases (`developer-zahir/wc-smart-checkout-builder`) for 1-click updates directly from the WordPress Admin Dashboard.

## Installation

1. Upload the `wc-smart-checkout-builder` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure **WooCommerce** and **Elementor** are installed and active.
4. Edit any page with Elementor and drag the **Product Variation & Checkout** widget onto your canvas.

## Auto Updates

When new releases or tags are published to `developer-zahir/wc-smart-checkout-builder` on GitHub, WordPress will automatically detect the update in **Dashboard -> Updates** and **Plugins**, allowing you to update with one click.
