/**
 * WooFlexiBundle Builder - Frontend Interaction & AJAX Cart Sync
 */
(function ($) {
	'use strict';

	/**
	 * Main Bundle Handler
	 */
	var WooFlexiBundle = {
		init: function () {
			var self = this;

			$('.wooflexi-bundle-wrapper').each(function () {
				self.initInstance($(this));
			});
		},

		/**
		 * Format price using WooCommerce localized currency settings
		 */
		formatPrice: function (price) {
			price = parseFloat(price) || 0;
			var decimals = typeof wooflexi_vars !== 'undefined' ? parseInt(wooflexi_vars.decimals, 10) : 2;
			var decimalSep = typeof wooflexi_vars !== 'undefined' ? wooflexi_vars.decimal_separator : '.';
			var thousandSep = typeof wooflexi_vars !== 'undefined' ? wooflexi_vars.thousand_separator : ',';
			var symbol = typeof wooflexi_vars !== 'undefined' ? wooflexi_vars.currency_symbol : '$';
			var pos = typeof wooflexi_vars !== 'undefined' ? wooflexi_vars.currency_pos : 'left';

			var parts = price.toFixed(decimals).split('.');
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
			var formattedNumber = parts.join(decimalSep);

			switch (pos) {
				case 'left_space':
					return symbol + ' ' + formattedNumber;
				case 'right':
					return formattedNumber + symbol;
				case 'right_space':
					return formattedNumber + ' ' + symbol;
				case 'left':
				default:
					return symbol + formattedNumber;
			}
		},

		/**
		 * Initialize a single bundle widget container
		 */
		initInstance: function ($wrapper) {
			var self = this;
			var minItems = parseInt($wrapper.data('min-items'), 10) || 1;
			var maxItems = parseInt($wrapper.data('max-items'), 10) || 0;
			var scrollTarget = $wrapper.data('scroll-target') || '#checkout';
			var tiers = $wrapper.data('tiers') || [];

			// Parse tiers if string
			if (typeof tiers === 'string') {
				try {
					tiers = JSON.parse(tiers);
				} catch (e) {
					tiers = [];
				}
			}

			// Sort tiers descending by min_qty
			tiers.sort(function (a, b) {
				return parseInt(b.min_qty, 10) - parseInt(a.min_qty, 10);
			});

			/**
			 * 1. Card Click / Selection Toggle
			 */
			$wrapper.on('click', '.wooflexi-card', function (e) {
				var $card = $(this);
				self.toggleCardSelection($card, $wrapper);
			});

			$wrapper.on('click', '.wooflexi-toggle-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $card = $(this).closest('.wooflexi-card');
				self.toggleCardSelection($card, $wrapper);
			});

			/**
			 * 2. Quantity buttons (+ / -)
			 */
			$wrapper.on('click', '.wooflexi-qty-plus', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $card = $(this).closest('.wooflexi-card');
				var $input = $card.find('.wooflexi-qty-input');
				var currentQty = parseInt($input.val(), 10) || 1;
				$input.val(currentQty + 1);

				// Auto-select card if not yet selected
				if (!$card.hasClass('is-selected')) {
					$card.addClass('is-selected');
				}
				self.updateSummary($wrapper, tiers, minItems, maxItems);
			});

			$wrapper.on('click', '.wooflexi-qty-minus', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $card = $(this).closest('.wooflexi-card');
				var $input = $card.find('.wooflexi-qty-input');
				var currentQty = parseInt($input.val(), 10) || 1;

				if (currentQty > 1) {
					$input.val(currentQty - 1);
				} else {
					// Deselect card when decreased to 0
					$card.removeClass('is-selected');
				}
				self.updateSummary($wrapper, tiers, minItems, maxItems);
			});

			$wrapper.on('change', '.wooflexi-qty-input', function (e) {
				var $card = $(this).closest('.wooflexi-card');
				var qty = parseInt($(this).val(), 10) || 1;
				if (qty < 1) {
					qty = 1;
					$(this).val(1);
				}
				self.updateSummary($wrapper, tiers, minItems, maxItems);
			});

			/**
			 * 3. Variable Product Select Changes
			 */
			$wrapper.on('change', '.wooflexi-variation-select', function (e) {
				e.stopPropagation();
				var $card = $(this).closest('.wooflexi-card');
				self.handleVariationChange($card);
				self.updateSummary($wrapper, tiers, minItems, maxItems);
			});

			/**
			 * 4. CTA Button Click (AJAX Sync & Smooth Scroll)
			 */
			$wrapper.on('click', '.wooflexi-cta-button', function (e) {
				e.preventDefault();
				var $btn = $(this);
				if ($btn.prop('disabled')) {
					return;
				}

				var selectedData = self.getSelectedItems($wrapper);
				if (!selectedData.length) {
					return;
				}

				// Check min items requirement
				var totalCount = 0;
				$.each(selectedData, function (i, item) {
					totalCount += item.quantity;
				});

				if (totalCount < minItems) {
					alert(wooflexi_vars.i18n.min_items_alert.replace('%d', minItems));
					return;
				}

				if (maxItems > 0 && totalCount > maxItems) {
					alert(wooflexi_vars.i18n.max_items_alert.replace('%d', maxItems));
					return;
				}

				self.syncCartWithCheckout($wrapper, $btn, selectedData, tiers, scrollTarget);
			});

			// Initial summary calculation
			self.updateSummary($wrapper, tiers, minItems, maxItems);
		},

		/**
		 * Toggle selection of a product card
		 */
		toggleCardSelection: function ($card, $wrapper) {
			var isVariable = $card.data('is-variable') == '1';

			// If selecting a variable product, verify options
			if (!$card.hasClass('is-selected') && isVariable) {
				var variationId = $card.data('selected-variation-id');
				if (!variationId) {
					alert(wooflexi_vars.i18n.select_variation);
					return;
				}
			}

			$card.toggleClass('is-selected');

			var minItems = parseInt($wrapper.data('min-items'), 10) || 1;
			var maxItems = parseInt($wrapper.data('max-items'), 10) || 0;
			var tiers = $wrapper.data('tiers') || [];

			this.updateSummary($wrapper, tiers, minItems, maxItems);
		},

		/**
		 * Handle variable product option selection
		 */
		handleVariationChange: function ($card) {
			var rawVariations = $card.data('product-variations');
			if (!rawVariations) {
				return;
			}

			var selectedAttrs = {};
			var allSelected = true;

			$card.find('.wooflexi-variation-select').each(function () {
				var attrName = $(this).data('attribute-name');
				var val = $(this).val();
				if (!val) {
					allSelected = false;
				}
				selectedAttrs[attrName] = val;
			});

			if (!allSelected) {
				$card.removeData('selected-variation-id');
				$card.removeData('selected-variation-price');
				$card.removeData('selected-attributes');
				return;
			}

			// Find matching variation
			var matchedVariation = null;

			$.each(rawVariations, function (index, variation) {
				var match = true;
				$.each(variation.attributes, function (attrKey, attrVal) {
					var normalizedKey = attrKey.replace('attribute_', '');
					if (attrVal !== '' && selectedAttrs[normalizedKey] !== attrVal) {
						match = false;
						return false;
					}
				});
				if (match) {
					matchedVariation = variation;
					return false;
				}
			});

			if (matchedVariation) {
				$card.data('selected-variation-id', matchedVariation.variation_id);
				$card.data('selected-variation-price', matchedVariation.display_price);
				$card.data('selected-attributes', selectedAttrs);

				// Update price display
				if (matchedVariation.price_html) {
					$card.find('.wooflexi-card-price').html(matchedVariation.price_html);
				}

				// Update image if variation has one
				if (matchedVariation.image && matchedVariation.image.thumb_src) {
					$card.find('.wooflexi-img').attr('src', matchedVariation.image.thumb_src);
				}
			} else {
				$card.removeData('selected-variation-id');
			}
		},

		/**
		 * Collect currently selected items
		 */
		getSelectedItems: function ($wrapper) {
			var items = [];

			$wrapper.find('.wooflexi-card.is-selected').each(function () {
				var $card = $(this);
				var productId = parseInt($card.data('product-id'), 10);
				var isVariable = $card.data('is-variable') == '1';
				var variationId = isVariable ? (parseInt($card.data('selected-variation-id'), 10) || 0) : 0;
				var quantity = parseInt($card.find('.wooflexi-qty-input').val(), 10) || 1;
				var attributes = isVariable ? ($card.data('selected-attributes') || {}) : {};

				items.push({
					product_id: productId,
					variation_id: variationId,
					quantity: quantity,
					attributes: attributes
				});
			});

			return items;
		},

		/**
		 * Calculate and update real-time summary bar
		 */
		updateSummary: function ($wrapper, tiers, minItems, maxItems) {
			var self = this;
			var totalItems = 0;
			var subtotal = 0;

			$wrapper.find('.wooflexi-card.is-selected').each(function () {
				var $card = $(this);
				var qty = parseInt($card.find('.wooflexi-qty-input').val(), 10) || 1;
				var isVariable = $card.data('is-variable') == '1';
				var itemPrice = isVariable && $card.data('selected-variation-price') !== undefined
					? parseFloat($card.data('selected-variation-price'))
					: parseFloat($card.data('base-price'));

				totalItems += qty;
				subtotal += (itemPrice * qty);
			});

			// Find matching discount tier
			var matchedTier = null;
			if (tiers && tiers.length > 0) {
				$.each(tiers, function (index, tier) {
					var minQty = parseInt(tier.min_qty, 10) || 0;
					if (totalItems >= minQty) {
						matchedTier = tier;
						return false; // Break loop
					}
				});
			}

			var discountAmount = 0;
			var discountLabel = '';

			if (matchedTier && subtotal > 0) {
				var discVal = parseFloat(matchedTier.discount_value) || 0;
				if (matchedTier.discount_type === 'percentage') {
					discountAmount = (subtotal * discVal) / 100;
					discountLabel = matchedTier.tier_label || discVal + '% OFF';
				} else {
					discountAmount = discVal;
					discountLabel = matchedTier.tier_label || self.formatPrice(discVal) + ' OFF';
				}
				if (discountAmount > subtotal) {
					discountAmount = subtotal;
				}
			}

			var finalPrice = Math.max(0, subtotal - discountAmount);

			// Update UI Elements
			var $bar = $wrapper.find('.wooflexi-summary-bar');
			$bar.find('.wooflexi-items-count').text(totalItems);

			var $tag = $bar.find('.wooflexi-discount-tag');
			if (discountAmount > 0 && discountLabel) {
				$tag.text(discountLabel).show();
			} else {
				$tag.hide();
			}

			var $strike = $bar.find('.wooflexi-subtotal-strike');
			if (discountAmount > 0) {
				$strike.text(self.formatPrice(subtotal)).show();
			} else {
				$strike.hide();
			}

			$bar.find('.wooflexi-final-price').text(self.formatPrice(finalPrice));

			// Rule notice validation
			var $notice = $bar.find('.wooflexi-rule-notice');
			var $ctaBtn = $bar.find('.wooflexi-cta-button');

			if (totalItems === 0) {
				$notice.hide();
				$ctaBtn.prop('disabled', true);
			} else if (totalItems < minItems) {
				$notice.text(wooflexi_vars.i18n.min_items_alert.replace('%d', minItems)).show();
				$ctaBtn.prop('disabled', true);
			} else if (maxItems > 0 && totalItems > maxItems) {
				$notice.text(wooflexi_vars.i18n.max_items_alert.replace('%d', maxItems)).show();
				$ctaBtn.prop('disabled', true);
			} else {
				$notice.hide();
				$ctaBtn.prop('disabled', false);
			}
		},

		/**
		 * AJAX Cart Synchronization & Smooth Scroll
		 */
		syncCartWithCheckout: function ($wrapper, $btn, items, tiers, scrollTarget) {
			var $loader = $btn.find('.wooflexi-cta-loader');
			var $text = $btn.find('.wooflexi-cta-text');
			var originalText = $text.text();

			$btn.prop('disabled', true);
			$loader.show();
			$text.text(wooflexi_vars.i18n.updating_cart);

			$.ajax({
				url: wooflexi_vars.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wooflexi_sync_cart',
					nonce: wooflexi_vars.nonce,
					items: JSON.stringify(items),
					tiers: JSON.stringify(tiers)
				},
				success: function (response) {
					if (response.success) {
						// 1. Trigger WooCommerce native checkout update
						$(document.body).trigger('update_checkout');

						// 2. Smooth scroll down to the embedded checkout section
						if ($(scrollTarget).length) {
							$('html, body').animate(
								{
									scrollTop: $(scrollTarget).offset().top - 40
								},
								650
							);
						}
					} else {
						alert(response.data && response.data.message ? response.data.message : 'Error updating checkout.');
					}
				},
				error: function () {
					alert('Server error occurred while syncing checkout.');
				},
				complete: function () {
					$btn.prop('disabled', false);
					$loader.hide();
					$text.text(originalText);
				}
			});
		}
	};

	// Initialize on DOM ready
	$(document).ready(function () {
		WooFlexiBundle.init();
	});

	// Re-init on Elementor frontend edit preview load
	$(window).on('elementor/frontend/init', function () {
		if (elementorFrontend && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/wooflexi_bundle_grid.default', function ($scope) {
				WooFlexiBundle.initInstance($scope.find('.wooflexi-bundle-wrapper'));
			});
		}
	});

})(jQuery);

