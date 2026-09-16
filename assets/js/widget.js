/**
 * WooCommerce Product Variation & Checkout Elementor Widget
 * Script: widget.js
 */

(function ($) {
	'use strict';

	/**
	 * Widget Controller Class
	 */
	function WCSCWidget($container) {
		this.$container = $container;
		this.productId = parseInt($container.data('product-id'), 10) || 0;
		this.productType = $container.data('product-type') || 'simple';
		this.defaultPriceHtml = $container.data('default-price') || '';
		this.rawPriceText = $container.data('raw-price') || '';
		this.$mainImg = $container.find('.wcsc-main-product-img');
		this.originImgSrc = this.$mainImg.data('origin-src') || this.$mainImg.attr('src');
		this.$priceContainer = $container.find('.wcsc-product-price');
		this.$stockContainer = $container.find('.wcsc-stock-status');
		this.$qtyInput = $container.find('.wcsc-qty-input');
		this.$loadingOverlay = $container.find('.wcsc-loading-overlay');
		this.currentVariationId = 0;
		this.isEditor = window.wcsc_params && window.wcsc_params.is_elementor_edit;
		this.syncTimeout = null;

		// Parse variation data
		var $varDataScript = $container.find('.wcsc-variations-data');
		this.variations = [];
		if ($varDataScript.length) {
			try {
				this.variations = JSON.parse($varDataScript.html()) || [];
			} catch (e) {
				console.error('WCSC: Failed to parse variations data', e);
			}
		}

		this.init();
	}

	WCSCWidget.prototype = {
		init: function () {
			this.bindEvents();
			this.applyCustomTexts();

			// Match default or initial variation on load
			if (this.productType === 'variable') {
				this.checkInitialSelection();
			} else {
				// Simple product initial button price
				if (this.rawPriceText) {
					this.updateButtonPrice(this.rawPriceText);
				}
			}
			this.rearrangeCheckoutLayout();
		},

		bindEvents: function () {
			var self = this;

			// Swatch Click
			this.$container.on('click', '.wcsc-swatch', function (e) {
				e.preventDefault();
				var $swatch = $(this);
				if ($swatch.hasClass('is-disabled')) {
					return;
				}

				var $group = $swatch.closest('.wcsc-attribute-group');
				var val = $swatch.data('value');
				var label = $swatch.data('label') || val;

				// Toggle selection
				$group.find('.wcsc-swatch').removeClass('is-selected');
				$swatch.addClass('is-selected');
				$group.find('.wcsc-attribute-input').val(val);

				var template = $group.find('.wcsc-attribute-header').attr('data-label-template');
				if (template) {
					if (template.indexOf('{{value}}') !== -1) {
						$group.find('.wcsc-attribute-label').text(template.replace('{{value}}', label));
					} else {
						$group.find('.wcsc-attribute-label').text(template);
					}
					$group.find('.wcsc-selected-value-label').text('');
				} else {
					$group.find('.wcsc-selected-value-label').text(label);
				}

				self.onAttributeChange();
			});

			// Quantity Plus / Minus
			this.$container.on('click', '.wcsc-qty-plus', function (e) {
				e.preventDefault();
				var current = parseInt(self.$qtyInput.val(), 10) || 1;
				var max = parseInt(self.$qtyInput.attr('max'), 10) || 9999;
				if (current < max) {
					self.$qtyInput.val(current + 1).trigger('change');
				}
			});

			this.$container.on('click', '.wcsc-qty-minus', function (e) {
				e.preventDefault();
				var current = parseInt(self.$qtyInput.val(), 10) || 1;
				var min = parseInt(self.$qtyInput.attr('min'), 10) || 1;
				if (current > min) {
					self.$qtyInput.val(current - 1).trigger('change');
				}
			});

			// Quantity Change
			this.$container.on('change', '.wcsc-qty-input', function () {
				var qty = parseInt($(this).val(), 10) || 1;
				var min = parseInt($(this).attr('min'), 10) || 1;
				if (qty < min) {
					$(this).val(min);
				}
				self.debouncedSync();
			});

			// Listen to WooCommerce checkout updates
			$(document.body).on('updated_checkout', function () {
				self.hideLoading();
				self.applyCustomTexts();
				self.rearrangeCheckoutLayout();
			});

			$(document.body).on('checkout_error', function () {
				self.hideLoading();
				self.rearrangeCheckoutLayout();
			});

			// Make shipping cards clickable
			this.$container.on('click', '.wcsc-shipping-card', function (e) {
				if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
					var $radio = $(this).find('input[type="radio"]');
					if ($radio.length && !$radio.is(':checked')) {
						$radio.prop('checked', true).trigger('change');
					}
				}
			});
			this.$container.on('change', 'input.shipping_method', function () {
				self.rearrangeCheckoutLayout();
			});
		},

		showLoading: function () {
			if (this.$loadingOverlay.length) {
				this.$loadingOverlay.addClass('is-active');
			}
			// Localized loading (no full blur)
			this.$container.find('form.checkout').addClass('wcsc-is-loading');
		},

		hideLoading: function () {
			if (this.$loadingOverlay.length) {
				this.$loadingOverlay.removeClass('is-active');
			}
			this.$container.find('form.checkout').removeClass('wcsc-is-loading');
		},

		applyCustomTexts: function () {
			var txtOrder = this.$container.data('txt-order');
			var txtBilling = this.$container.data('txt-billing');
			var txtProduct = this.$container.data('txt-product');
			var txtSubtotal = this.$container.data('txt-subtotal');
			var txtShipping = this.$container.data('txt-shipping');
			var txtTotal = this.$container.data('txt-total');

			if (txtOrder) {
				this.$container.find('#order_review_heading').text(txtOrder);
			}
			if (txtBilling) {
				this.$container.find('.woocommerce-billing-fields > h3, #customer_details .col-1 .wcsc-section-title').text(txtBilling);
			}
			if (txtProduct) {
				this.$container.find('table.shop_table th.product-name').text(txtProduct);
			}
			if (txtSubtotal) {
				this.$container.find('table.shop_table th.product-total').text(txtSubtotal);
				this.$container.find('table.shop_table tr.cart-subtotal th').text(txtSubtotal);
			}
			if (txtShipping) {
				this.$container.find('table.shop_table tr.woocommerce-shipping-totals th').text(txtShipping);
				this.$container.find('.wcsc-shipping-heading').text(txtShipping);
			}
			if (txtTotal) {
				this.$container.find('table.shop_table tr.order-total th').text(txtTotal);
			}
		},

		showErrorModal: function (messageText) {
			var text = messageText || 'অনুগ্রহ করে নাম এবং ফোন নাম্বার দিন।';
			var errorHtml = '<div class="wcsc-error-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#e53e3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>';
			errorHtml += '<h3 class="wcsc-error-title">' + text + '</h3>';
			var $modal = $('<div class="wcsc-error-modal-overlay"><div class="wcsc-error-modal"><span class="wcsc-error-modal-close">&times;</span><div class="wcsc-error-content">' + errorHtml + '</div></div></div>');
			$('body').append($modal);
			$modal.find('.wcsc-error-modal-close, .wcsc-error-modal-overlay').on('click', function(e) {
				if (e.target === this) {
					$modal.fadeOut(200, function() { $(this).remove(); });
				}
			});
		},

		rearrangeCheckoutLayout: function () {
			var $wrapper = this.$container.find('.wcsc-native-checkout-wrapper');
			if (!$wrapper.length) return;

			var shippingPos = $wrapper.data('shipping-pos');
			var buttonPos = $wrapper.data('button-pos');
			var errorDisp = $wrapper.data('error-disp');

			// Move Shipping
			if (shippingPos === 'inside_form') {
				var $shippingTotals = $wrapper.find('.woocommerce-shipping-totals');
				if ($shippingTotals.length) {
					var $shippingContainer = $wrapper.find('.wcsc-custom-shipping-container');
					if (!$shippingContainer.length) {
						$shippingContainer = $('<div class="wcsc-custom-shipping-container"></div>');
						// Try to append after customer details, fallback to before order review
						if ($wrapper.find('#customer_details').length) {
							$wrapper.find('#customer_details').after($shippingContainer);
						} else {
							$wrapper.find('#order_review').before($shippingContainer);
						}
					}
					var $methods = $shippingTotals.find('#shipping_method');
					if ($methods.length) {
						if (!$shippingContainer.find('.wcsc-shipping-heading').length) {
							var title = this.$container.data('txt-shipping') || 'Shipping';
							$shippingContainer.append('<h3 class="wcsc-shipping-heading wcsc-section-title">' + title + '</h3>');
						}
						$shippingContainer.find('#shipping_method').remove();
						$shippingContainer.append($methods);
						$shippingTotals.hide();
					}
				}
			}

			// Shipping Cards styling & active state
			var $methods = $wrapper.find('#shipping_method li');
			if ($methods.length) {
				if (!$methods.find('input[type="radio"]:checked').length) {
					// Select first by default if none selected
					$methods.first().find('input[type="radio"]').prop('checked', true).trigger('change');
				}
				$methods.each(function() {
					var $li = $(this);
					$li.addClass('wcsc-shipping-card');
					if ($li.find('input[type="radio"]').is(':checked')) {
						$li.addClass('is-active');
					} else {
						$li.removeClass('is-active');
					}
				});
			}

			// Move Order Button
			if (buttonPos === 'below_form') {
				var $payment = $wrapper.find('#payment');
				var $button = $payment.find('#place_order');
				if ($button.length) {
					var $btnContainer = $wrapper.find('.wcsc-custom-btn-container');
					if (!$btnContainer.length) {
						$btnContainer = $('<div class="wcsc-custom-btn-container"></div>');
						var $existingShipping = $wrapper.find('.wcsc-custom-shipping-container');
						if ($existingShipping.length) {
							$existingShipping.after($btnContainer);
						} else if ($wrapper.find('#customer_details').length) {
							$wrapper.find('#customer_details').after($btnContainer);
						} else {
							$wrapper.find('#order_review').before($btnContainer);
						}
					}
					$btnContainer.empty().append($button);
				}
			}

			// Move Errors
			var $errors = $wrapper.find('.woocommerce-error');
			if ($errors.length && errorDisp) {
				if (errorDisp === 'modal') {
					this.showErrorModal('অনুগ্রহ করে নাম এবং ফোন নাম্বার দিন।');
					$errors.remove();
				} else {
					var $errorContainer = $wrapper.find('.wcsc-custom-error-container');
					if (!$errorContainer.length) {
						$errorContainer = $('<div class="wcsc-custom-error-container"></div>');
						this.$container.find('.wcsc-variations-section').after($errorContainer);
					}
					$errorContainer.html($errors);
				}
			}
		},

		checkInitialSelection: function () {
			var self = this;
			var $groups = this.$container.find('.wcsc-attribute-group');

			$groups.each(function () {
				var $group = $(this);
				var $selected = $group.find('.wcsc-swatch.is-selected');
				if (!$selected.length) {
					// Select first available option as initial default
					$selected = $group.find('.wcsc-swatch:not(.is-disabled)').first();
					if ($selected.length) {
						$selected.addClass('is-selected');
						$group.find('.wcsc-attribute-input').val($selected.data('value'));
					}
				}
				if ($selected.length) {
					var valLabel = $selected.data('label') || $selected.data('value');
					var template = $group.find('.wcsc-attribute-header').attr('data-label-template');
					if (template) {
						if (template.indexOf('{{value}}') !== -1) {
							$group.find('.wcsc-attribute-label').text(template.replace('{{value}}', valLabel));
						} else {
							$group.find('.wcsc-attribute-label').text(template);
						}
						$group.find('.wcsc-selected-value-label').text('');
					} else {
						$group.find('.wcsc-selected-value-label').text(valLabel);
					}
				}
			});

			this.onAttributeChange(true);
		},

		getSelectedAttributes: function () {
			var attrs = {};
			this.$container.find('.wcsc-attribute-group').each(function () {
				var $group = $(this);
				var attrName = $group.data('attribute-name');
				var val = $group.find('.wcsc-attribute-input').val();
				if (attrName) {
					attrs[attrName] = val;
				}
			});
			return attrs;
		},

		onAttributeChange: function (isInitial) {
			var selectedAttrs = this.getSelectedAttributes();
			var matchedVariation = this.findMatchingVariation(selectedAttrs);

			if (matchedVariation) {
				this.currentVariationId = matchedVariation.variation_id;

				// Update Price
				if (matchedVariation.price_html && this.$priceContainer.length) {
					this.$priceContainer.html(matchedVariation.price_html);
				}

				// Update Main Image
				if (matchedVariation.image && matchedVariation.image.src && this.$mainImg.length) {
					this.$mainImg.attr('src', matchedVariation.image.src);
				} else if (this.originImgSrc && this.$mainImg.length) {
					this.$mainImg.attr('src', this.originImgSrc);
				}

				// Update Stock
				if (this.$stockContainer.length) {
					if (matchedVariation.is_in_stock) {
						this.$stockContainer.removeClass('out-of-stock').addClass('in-stock');
						if (matchedVariation.availability_html) {
							this.$stockContainer.html(matchedVariation.availability_html);
						} else {
							this.$stockContainer.text(window.wcsc_params ? window.wcsc_params.i18n.in_stock : 'In stock');
						}
					} else {
						this.$stockContainer.removeClass('in-stock').addClass('out-of-stock');
						this.$stockContainer.text(window.wcsc_params ? window.wcsc_params.i18n.out_of_stock : 'Out of stock');
					}
				}

				// Sync with Cart & Checkout
				if (!this.isEditor && !isInitial) {
					this.debouncedSync();
				} else if (this.isEditor) {
					// In Elementor Editor: update visual preview elements
					this.updateEditorPreview(matchedVariation);
				}
			} else {
				// No direct match found
				this.currentVariationId = 0;
			}
		},

		findMatchingVariation: function (selectedAttrs) {
			if (!this.variations.length) {
				return null;
			}

			for (var i = 0; i < this.variations.length; i++) {
				var variation = this.variations[i];
				var varAttrs = variation.attributes || {};
				var match = true;

				for (var name in selectedAttrs) {
					if (!selectedAttrs.hasOwnProperty(name)) continue;

					var selectedVal = String(selectedAttrs[name]).toLowerCase();
					// WC attributes keys can be 'attribute_pa_color' or 'attribute_color'
					var key1 = 'attribute_' + name.toLowerCase();
					var key2 = 'attribute_pa_' + name.toLowerCase().replace(/^pa_/, '');

					var varVal = '';
					if (typeof varAttrs[key1] !== 'undefined') {
						varVal = String(varAttrs[key1]).toLowerCase();
					} else if (typeof varAttrs[key2] !== 'undefined') {
						varVal = String(varAttrs[key2]).toLowerCase();
					} else if (typeof varAttrs[name] !== 'undefined') {
						varVal = String(varAttrs[name]).toLowerCase();
					}

					// If variation attribute is not blank (any) and doesn't match selected value
					if (varVal !== '' && varVal !== selectedVal) {
						match = false;
						break;
					}
				}

				if (match) {
					return variation;
				}
			}

			return null;
		},

		updateButtonPrice: function (priceText) {
			if (!priceText) return;
			var $btnPrice = this.$container.find('.wcsc-btn-price');
			if ($btnPrice.length) {
				$btnPrice.text(priceText);
			}
		},

		debouncedSync: function () {
			var self = this;
			if (this.syncTimeout) {
				clearTimeout(this.syncTimeout);
			}
			this.showLoading();
			this.syncTimeout = setTimeout(function () {
				self.syncCart();
			}, 250);
		},

		syncCart: function () {
			if (!window.wcsc_params || !window.wcsc_params.ajax_url) {
				this.hideLoading();
				return;
			}

			var self = this;
			var qty = parseInt(this.$qtyInput.val(), 10) || 1;
			var selectedAttrs = this.getSelectedAttributes();

			var postData = {
				action: 'wcsc_sync_cart',
				nonce: window.wcsc_params.nonce,
				product_id: this.productId,
				variation_id: this.currentVariationId,
				quantity: qty,
				attributes: selectedAttrs
			};

			$.ajax({
				url: window.wcsc_params.ajax_url,
				type: 'POST',
				data: postData,
				dataType: 'json',
				success: function (res) {
					if (res && res.success) {
						if (res.data && res.data.currency_text) {
							self.updateButtonPrice(res.data.currency_text);
						}
						// Trigger WooCommerce native checkout update
						$(document.body).trigger('update_checkout');
					} else {
						self.hideLoading();
					}
				},
				error: function (xhr, status, error) {
					console.warn('WCSC Cart Sync Error:', error);
					self.hideLoading();
				}
			});
		},

		updateEditorPreview: function (variation) {
			// Update preview price in order review table
			var $previewPrice = this.$container.find('.wcsc-preview-item-price, .wcsc-preview-subtotal, .wcsc-preview-total');
			if ($previewPrice.length && variation.price_html) {
				$previewPrice.html(variation.price_html);
			}

			// Update button price text
			if (variation.display_price) {
				var currencySymbol = '$';
				var $existingAmount = this.$container.find('.woocommerce-Price-currencySymbol');
				if ($existingAmount.length) {
					currencySymbol = $existingAmount.first().text();
				}
				this.updateButtonPrice(currencySymbol + variation.display_price);
			}
		}
	};

	/**
	 * Elementor & Document Initialization Hook
	 */
	function initWidgets($scope) {
		var $elements = $scope ? $scope.find('.wcsc-product-checkout-widget') : $('.wcsc-product-checkout-widget');
		$elements.each(function () {
			var $this = $(this);
			if (!$this.data('wcsc_initialized')) {
				$this.data('wcsc_initialized', true);
				new WCSCWidget($this);
			}
		});
	}

	$(document).ready(function () {
		initWidgets();
	});

	// Hook into Elementor Frontend
	$(window).on('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/wcsc_product_checkout.default',
				function ($scope) {
					initWidgets($scope);
				}
			);
		}
	});

})(jQuery);
