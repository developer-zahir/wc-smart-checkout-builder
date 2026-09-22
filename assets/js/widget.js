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
			this.styleShippingMethods();
			this.stylePaymentMethods();

			// Match default or initial variation on load
			if (this.productType === 'variable') {
				this.checkInitialSelection();
			} else {
				// Simple product initial button price
				if (this.rawPriceText) {
					this.updateButtonPrice(this.rawPriceText);
				}
			}

			this.syncOrderButtonPrice();
			this.initMobileStickyObserver();
			this.syncOrderButtonAnimationColor();
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
					if (/\{+(?:value|selected_variation)\}+/i.test(template)) {
						$group.find('.wcsc-attribute-label').text(template.replace(/\{+(?:value|selected_variation)\}+/gi, label));
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

			// Listen to WooCommerce checkout updates (guarded: only when our wrapper is present)
			$(document.body).on('updated_checkout', function (event, data) {
				if (!self.$container.find('.wcas-checkout-wrapper').length && !$('.wcas-checkout-wrapper').length) return;
				self.hideLoading();
				self.applyCustomTexts();
				self.styleShippingMethods();
				self.stylePaymentMethods();

				// Synchronously sync Order Now button price
				self.syncOrderButtonPrice();

				// Suppress any inline WooCommerce error notices injected during AJAX (scoped)
				$('.wcsc-product-checkout-widget .woocommerce-NoticeGroup-checkout, .wcsc-product-checkout-widget .woocommerce-NoticeGroup, .wcsc-product-checkout-widget .woocommerce-error, .wcsc-product-checkout-widget .checkout-inline-error-message, .wcas-checkout-wrapper .woocommerce-NoticeGroup-checkout, .wcas-checkout-wrapper .woocommerce-NoticeGroup, .wcas-checkout-wrapper .woocommerce-error, .wcas-checkout-wrapper .checkout-inline-error-message').hide().remove();

				// Ensure Order Review item thumbnail matches current selected variation ONLY for main product (first item)
				if (self.currentVariationId && self.variations.length) {
					for (var i = 0; i < self.variations.length; i++) {
						if (self.variations[i].variation_id === self.currentVariationId) {
							if (self.variations[i].image && self.variations[i].image.src) {
								var $cartImg = self.$container.find('.woocommerce-checkout-review-order-table tbody tr.wcas-order-review-product:first .wcsc-cart-item-image, .woocommerce-checkout-review-order-table tbody tr.cart_item:first .wcsc-cart-item-image');
								if ($cartImg.length) {
									$cartImg.attr('src', self.variations[i].image.src).removeAttr('srcset');
								}
							}
							break;
						}
					}
				}

				// CRITICAL BUG FIX: Ensure secondary duplicate order buttons injected into #payment by native WC AJAX are removed
				self.$container.find('.wcas-block-payment #place_order, .wcas-block-payment .place-order').remove();
			});

			// Catch WooCommerce AJAX updates for shipping/totals recalculation (guarded)
			$(document).ajaxComplete(function (event, xhr, settings) {
				if (!$('.wcas-checkout-wrapper').length) return;
				if (settings && ((settings.data && typeof settings.data === 'string' && settings.data.indexOf('update_order_review') !== -1) || (settings.url && settings.url.indexOf('update_order_review') !== -1))) {
					setTimeout(function () {
						self.syncOrderButtonPrice();
					}, 50);
				}
			});

			$(document.body).on('checkout_error', function (event, errorMessage) {
				if (!self.$container.find('.wcas-checkout-wrapper').length && !$('.wcas-checkout-wrapper').length) return;
				self.hideLoading();
				self.styleShippingMethods();

				// Prevent browser from scrolling up abruptly
				$('html, body').stop();

				// Suppress inline notice group completely
				$('.woocommerce-NoticeGroup-checkout, .wcas-checkout-wrapper .woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-NoticeGroup').hide().remove();

				// Extract and parse error messages
				var messages = [];
				if (errorMessage) {
					var $temp = $('<div>').html(errorMessage);
					$temp.find('li').each(function () {
						var text = $.trim($(this).text());
						if (text && messages.indexOf(text) === -1) {
							messages.push(text);
						}
					});
					if (!messages.length) {
						var rawText = $.trim($temp.text());
						if (rawText && messages.indexOf(rawText) === -1) {
							messages.push(rawText);
						}
					}
				}

				if (!messages.length) {
					self.$container.find('.form-row.woocommerce-invalid, .form-row.woocommerce-invalid-required-field').each(function () {
						var label = $(this).find('label').text().replace(/[\*\:]/g, '').trim();
						var msg = label ? label + ' পূরণ করা বাধ্যতামূলক' : 'প্রয়োজনীয় তথ্য সঠিকভাবে প্রদান করুন';
						if (messages.indexOf(msg) === -1) {
							messages.push(msg);
						}
					});
				}

				if (!messages.length) {
					messages.push('অর্ডার সম্পন্ন করতে প্রয়োজনীয় তথ্য সঠিকভাবে প্রদান করুন।');
				}

				self.showValidationModal(messages);
			});

			// Listen for WooCommerce variation events (guarded: only when our wrapper is present)
			$(document.body).on('found_variation', function (event, variation) {
				if (!$('.wcas-checkout-wrapper').length) return;
				if (variation) {
					self.debouncedSync();
				}
			});

			$(document.body).on('reset_data', function () {
				if (!$('.wcas-checkout-wrapper').length) return;
				self.debouncedSync();
			});

			// Order Bump Card & Button Click / Toggle
			this.$container.on('click', '.wcsc-order-bump-card', function (e) {
				if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label') || $(e.target).closest('.wcsc-order-bump-btn').length) {
					return;
				}
				e.preventDefault();
				var $card = $(this);
				var $chk = $card.find('.wcsc-order-bump-checkbox');
				var nextState = !$chk.prop('checked');
				$chk.prop('checked', nextState).trigger('change');
			});

			this.$container.on('change', '.wcsc-order-bump-checkbox', function (e) {
				e.stopPropagation();
				var $chk = $(this);
				var productId = $chk.data('product-id');
				var isAdding = $chk.is(':checked');
				var $card = $chk.closest('.wcsc-order-bump-card');
				var $btn = $card.find('.wcsc-order-bump-btn');
				var actionText = $card.data('action-text') || 'অর্ডার যুক্ত করুন';

				$card.toggleClass('is-selected', isAdding);
				$btn.toggleClass('is-active', isAdding);
				$btn.find('.wcsc-order-bump-btn-icon').text(isAdding ? '✓' : '+');
				$btn.find('.wcsc-order-bump-btn-text').text(isAdding ? 'যুক্ত হয়েছে' : actionText);

				self.toggleOrderBump(productId, isAdding ? 'add' : 'remove');
			});

			this.$container.on('click', '.wcsc-order-bump-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var $card = $(this).closest('.wcsc-order-bump-card');
				var $chk = $card.find('.wcsc-order-bump-checkbox');
				var nextState = !$chk.prop('checked');
				$chk.prop('checked', nextState).trigger('change');
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
			this.$container.on('change', 'input.shipping_method, #shipping_method input[type="radio"]', function () {
				self.styleShippingMethods();
				setTimeout(function () {
					self.syncOrderButtonPrice();
				}, 60);
			});

			this.$container.on('click', '.wcas-block-payment ul.payment_methods li.wc_payment_method', function (e) {
				if ($(e.target).is('input[type="radio"]') || $(e.target).is('a') || $(e.target).closest('.payment_box').length) {
					return;
				}
				var $radio = $(this).find('input[type="radio"]');
				if ($radio.length && !$radio.is(':checked')) {
					$radio.prop('checked', true).trigger('change');
				}
			});

			this.$container.on('change', 'input[name="payment_method"]', function () {
				self.stylePaymentMethods();
			});

			// Mobile sticky floating order button click -> scroll smoothly to checkout and immediately hide
			this.$container.on('click', '.wcsc-mobile-sticky-btn, .wcsc-floating-btn', function (e) {
				e.preventDefault();
				var stickyBar = self.$container.find('.wcsc-mobile-sticky-bar')[0] || document.getElementById('wcsc-mobile-sticky-bar');
				if (stickyBar) {
					$(stickyBar).addClass('is-hidden');
				}
				var $target = self.$container.find('.wcas-checkout-wrapper');
				if (!$target.length) {
					$target = $('.wcas-checkout-wrapper');
				}
				if ($target.length) {
					$('html, body').animate({
						scrollTop: $target.offset().top - 20
					}, 450);
				}
			});

			// Close modal on close button click (top-left X or bottom button)
			$(document).on('click', '.wcas-modal-top-close-btn, .wcsc-modal-top-close-btn, .wcsc-phone-modal-close-btn, .wcsc-validation-modal-close-btn', function (e) {
				e.preventDefault();
				var $modal = $('#wcsc-validation-modal, #wcsc-phone-modal');
				$modal.fadeOut(200);
				var $firstInvalid = $modal.data('first-invalid') || self.$container.find('.wcsc-invalid, .wcas-input-error').first();
				if ($firstInvalid && $firstInvalid.length) {
					$('html, body').animate({
						scrollTop: $firstInvalid.offset().top - 100
					}, 300);
					$firstInvalid.focus();
				}
			});

			// Close modal on backdrop click
			$(document).on('click', '#wcsc-validation-modal, #wcsc-phone-modal', function (e) {
				if ($(e.target).is('#wcsc-validation-modal, #wcsc-phone-modal')) {
					var $modal = $(this);
					$modal.fadeOut(200);
					var $firstInvalid = $modal.data('first-invalid') || self.$container.find('.wcsc-invalid').first();
					if ($firstInvalid && $firstInvalid.length) {
						$('html, body').animate({
							scrollTop: $firstInvalid.offset().top - 100
						}, 300);
						$firstInvalid.focus();
					}
				}
			});

			// Intercept checkout submit for client-side required field validation & phone validation
			var validateCheckoutForm = function (e) {
				var $form = self.$container.find('form.checkout');
				if ($form.length) {
					// Block submission completely if plugin license is unauthorized
					if (self.$container.find('.wcsc-license-unauthorized-notice').length || self.$container.find('#place_order.wcsc-license-unauthorized').length) {
						if (e) {
							e.preventDefault();
							e.stopImmediatePropagation();
						}
						var $notice = self.$container.find('.wcsc-license-unauthorized-notice');
						if ($notice.length) {
							$('html, body').animate({
								scrollTop: $notice.offset().top - 150
							}, 300);
						}
						return false;
					}

					var hasInvalid = false;
					var $firstInvalid = null;
					var missingFields = [];

					// Clear previous error states
					$form.find('.wcsc-invalid, .wcas-input-error').removeClass('wcsc-invalid wcas-input-error');
					$form.find('.form-row.woocommerce-invalid').removeClass('woocommerce-invalid');

					// 1. Validate all visible required fields
					$form.find('input[required], textarea[required], select[required], .validate-required input.input-text, .validate-required textarea, .validate-required select, .validate-required input').each(function () {
						var $field = $(this);
						if (!$field.is(':visible') || $field.is(':disabled')) {
							return;
						}
						var val = $.trim($field.val() || '');
						if (!val) {
							hasInvalid = true;
							$field.addClass('wcsc-invalid wcas-input-error');
							$field.closest('.form-row').addClass('woocommerce-invalid');
							if (!$firstInvalid) {
								$firstInvalid = $field;
							}
							var nameAttr = ($field.attr('name') || '').toLowerCase();
							var msg = '';
							if (nameAttr.indexOf('first_name') !== -1 || nameAttr.indexOf('last_name') !== -1 || nameAttr.indexOf('name') !== -1) {
								msg = 'আপনার নাম প্রদান করুন';
							} else if (nameAttr.indexOf('phone') !== -1) {
								msg = 'আপনার মোবাইল নম্বরটি দিন';
							} else if (nameAttr.indexOf('address_1') !== -1 || nameAttr.indexOf('address') !== -1) {
								msg = 'আপনার সম্পূর্ণ ঠিকানা প্রদান করুন';
							} else if (nameAttr.indexOf('city') !== -1) {
								msg = 'শহর / জেলা প্রদান করুন';
							} else {
								var label = $field.closest('.form-row').find('label').text().replace(/[\*\:]/g, '').trim();
								msg = label ? label + ' পূরণ করা বাধ্যতামূলক' : 'প্রয়োজনীয় তথ্য প্রদান করুন';
							}
							if (missingFields.indexOf(msg) === -1) {
								missingFields.push(msg);
							}
						}
					});

					// 2. Validate phone format ONLY if phone field is non-empty and validation is enabled
					var $hasValidation = self.$container.find('input[name="wcsc_bd_phone_validation"]');
					var $phone = self.$container.find('input[name="billing_phone"]');
					if ($phone.length && $phone.is(':visible')) {
						var rawPhone = $.trim($phone.val() || '').replace(/[\s\-\(\)]/g, '');
						if (rawPhone) {
							if ($hasValidation.length && $hasValidation.val() === '1') {
								var bdPhoneRegex = /^(?:\+?880|880|0)?1[3-9]\d{8}$/;
								if (!bdPhoneRegex.test(rawPhone)) {
									hasInvalid = true;
									$phone.addClass('wcsc-invalid wcas-input-error');
									$phone.closest('.form-row').addClass('woocommerce-invalid');
									var phoneErr = '১১ সংখ্যার একটি সঠিক মোবাইল নম্বর প্রদান করুন';
									if (missingFields.indexOf(phoneErr) === -1) {
										missingFields.push(phoneErr);
									}
									if (!$firstInvalid) {
										$firstInvalid = $phone;
									}
								}
							}
						}
					}

					if (hasInvalid && missingFields.length > 0) {
						if (e) {
							e.preventDefault();
							e.stopImmediatePropagation();
						}
						self.showValidationModal(missingFields, $firstInvalid);
						return false;
					}
				}
				return true;
			};

			this.$container.on('click', '#place_order, .wcsc-order-now-btn', function (e) {
				if ($(this).hasClass('wcsc-license-unauthorized') || self.$container.find('.wcsc-license-unauthorized-notice').length) {
					e.preventDefault();
					e.stopImmediatePropagation();
					var $notice = self.$container.find('.wcsc-license-unauthorized-notice');
					if ($notice.length) {
						$('html, body').animate({
							scrollTop: $notice.offset().top - 150
						}, 300);
					}
					return false;
				}
				if (!validateCheckoutForm(e)) {
					return false;
				}
			});

			this.$container.on('submit', 'form.checkout', function (e) {
				if (!validateCheckoutForm(e)) {
					return false;
				}
			});

			// Clear invalid state on user input/change
			this.$container.on('input change', 'input, textarea, select', function () {
				var $input = $(this);
				if ($.trim($input.val() || '')) {
					$input.removeClass('wcsc-invalid wcas-input-error');
					$input.closest('.form-row').removeClass('woocommerce-invalid');
				}
			});
		},

		showLoading: function () {
			// Removed visual loading overlay as requested
		},

		hideLoading: function () {
			// Removed visual loading overlay as requested
		},

		applyCustomTexts: function () {
			var txtOrder = this.$container.data('txt-order');
			var txtBilling = this.$container.data('txt-billing');
			var txtProduct = this.$container.data('txt-product');
			var txtSubtotal = this.$container.data('txt-subtotal');
			var txtShipping = this.$container.data('txt-shipping');
			var txtPayment = this.$container.data('txt-payment');
			var txtTotal = this.$container.data('txt-total');

			if (txtOrder) {
				this.$container.find('#order_review_heading').text(txtOrder);
			}
			if (txtBilling) {
				this.$container.find('.wcas-block-checkout-form .wcsc-section-title, .woocommerce-billing-fields > h3, #customer_details .col-1 .wcsc-section-title').text(txtBilling);
			}
			if (txtPayment) {
				this.$container.find('.wcas-block-payment .wcsc-section-title').text(txtPayment);
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

		styleShippingMethods: function () {
			var $wrapper = this.$container.find('.wcas-checkout-wrapper');
			if (!$wrapper.length) return;

			// Ship methods are rendered natively inside the plugin-owned
			// .wcas-block-shipping container by the PHP layer. JS only adds
			// the interactive card classes (.wcsc-shipping-card / .is-active)
			// so the CSS styling and click-to-select behaviour work.
			var $methods = $wrapper.find('.wcas-block-shipping #shipping_method li');
			if ($methods.length) {
				if (!$methods.find('input[type="radio"]:checked').length) {
					$methods.first().find('input[type="radio"]').prop('checked', true).trigger('change');
				}
				$methods.each(function () {
					var $li = $(this);
					$li.addClass('wcsc-shipping-card');
					if ($li.find('input[type="radio"]').is(':checked')) {
						$li.addClass('is-active');
					} else {
						$li.removeClass('is-active');
					}
				});
			}
		},

		stylePaymentMethods: function () {
			var $wrapper = this.$container.find('.wcas-checkout-wrapper');
			if (!$wrapper.length) return;

			var $methods = $wrapper.find('.wcas-block-payment ul.payment_methods li.wc_payment_method');
			if ($methods.length) {
				if (!$methods.find('input[type="radio"]:checked').length) {
					$methods.first().find('input[type="radio"]').prop('checked', true).trigger('change');
				}
				$methods.each(function () {
					var $li = $(this);
					if ($li.find('input[type="radio"]').is(':checked')) {
						$li.addClass('is-active');
					} else {
						$li.removeClass('is-active');
					}
				});
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
						if (/\{+(?:value|selected_variation)\}+/i.test(template)) {
							$group.find('.wcsc-attribute-label').text(template.replace(/\{+(?:value|selected_variation)\}+/gi, valLabel));
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

				// Update Order Review item thumbnail (first/main product only)
				var $cartItemImg = this.$container.find('.woocommerce-checkout-review-order-table tbody tr.wcas-order-review-product:first .wcsc-cart-item-image, .woocommerce-checkout-review-order-table tbody tr.cart_item:first .wcsc-cart-item-image');
				if ($cartItemImg.length) {
					if (matchedVariation.image && matchedVariation.image.src) {
						$cartItemImg.attr('src', matchedVariation.image.src).removeAttr('srcset');
					} else if (this.originImgSrc) {
						$cartItemImg.attr('src', this.originImgSrc).removeAttr('srcset');
					}
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

		decodeHtmlEntities: function (text) {
			if (!text) return '';
			try {
				var tempElem = document.createElement('textarea');
				tempElem.innerHTML = text;
				var decoded = tempElem.value || '';
				if (decoded.indexOf('&') !== -1 && decoded.indexOf(';') !== -1) {
					tempElem.innerHTML = decoded;
					decoded = tempElem.value || decoded;
				}
				return decoded.replace(/\u00a0/g, ' ').trim();
			} catch (e) {
				return String(text).replace(/&amp;/g, '&').replace(/\u00a0/g, ' ').trim();
			}
		},

		syncOrderButtonPrice: function (customPrice) {
			var decodedTotal = '';

			if (customPrice) {
				decodedTotal = this.decodeHtmlEntities(customPrice);
			} else {
				// Extract the updated grand total from WooCommerce order review table (scoped to our container)
				var $totalElement = this.$container.find('.order-total .amount, tr.order-total .woocommerce-Price-amount, .wcsc-ty-totals td, .woocommerce-checkout-review-order-table tr.order-total .woocommerce-Price-amount, .woocommerce-checkout-review-order-table .order-total .woocommerce-Price-amount').last();
				if (!$totalElement.length) {
					$totalElement = this.$container.find('.wcas-checkout-wrapper .order-total .amount, .wcas-checkout-wrapper tr.order-total .woocommerce-Price-amount').last();
				}
				if ($totalElement.length) {
					var rawHtml = $totalElement.html();
					decodedTotal = this.decodeHtmlEntities(rawHtml);
				}
			}

			if (!decodedTotal) {
				return;
			}

			// Clean non-breaking spaces and tags
			decodedTotal = decodedTotal.replace(/\u00a0/g, ' ').replace(/<[^>]*>/g, '').trim();

			// Update all order buttons synchronously (scoped to our container only)
			var $buttons = this.$container.find('#place_order, .wcsc-order-now-btn, .wcas-block-order-button button');
			$buttons.each(function () {
				var $button = $(this);
				var templateText = $button.attr('data-template-text') || $button.data('template-text');
				if (!templateText) {
					var currentVal = $button.attr('value') || $button.text();
					if (currentVal && currentVal.indexOf('{total_price}') !== -1) {
						templateText = currentVal;
					} else {
						templateText = 'Order Now - {total_price}';
					}
				}

				// Inject decoded price into button template
				var newButtonText = templateText.replace('{total_price}', decodedTotal).trim();

				// If button has separate inner text wrapper (e.g. beam/icon buttons), update inner text
				var $innerBtnText = $button.find('.wcsc-btn-text');

				if ($innerBtnText.length) {
					$innerBtnText.html(newButtonText);
				} else {
					$button.html(newButtonText);
				}

				// Update DOM text and attributes synchronously
				$button.attr('value', newButtonText);
				$button.attr('data-value', newButtonText);

				// Also update any standalone .wcsc-btn-price
				$button.find('.wcsc-btn-price').text(decodedTotal);
			});

			this.$container.find('.wcsc-btn-price').text(decodedTotal);
		},

		updateButtonPrice: function (priceText) {
			this.syncOrderButtonPrice(priceText);
		},

		getSelectedBumpProductIds: function () {
			var ids = [];
			this.$container.find('.wcsc-order-bump-checkbox:checked').each(function () {
				var pid = parseInt($(this).data('product-id'), 10);
				if (pid > 0 && ids.indexOf(pid) === -1) {
					ids.push(pid);
				}
			});
			return ids;
		},

		toggleOrderBump: function (productId, actionType) {
			if (!window.wcsc_params || !window.wcsc_params.ajax_url) {
				return;
			}
			var self = this;
			self.showLoading();

			$.ajax({
				url: window.wcsc_params.ajax_url,
				type: 'POST',
				data: {
					action: 'wcsc_toggle_order_bump',
					nonce: window.wcsc_params.nonce,
					product_id: productId,
					action_type: actionType
				},
				dataType: 'json',
				success: function (res) {
					if (res && res.success) {
						if (res.data && res.data.currency_text) {
							self.updateButtonPrice(res.data.currency_text);
						}
						// Refresh native WooCommerce checkout fragments
						$(document.body).trigger('update_checkout');
					} else {
						self.hideLoading();
					}
				},
				error: function (xhr, status, error) {
					console.warn('WCSC Order Bump Error:', error);
					self.hideLoading();
				}
			});
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
				attributes: selectedAttrs,
				bump_product_ids: this.getSelectedBumpProductIds()
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

		initMobileStickyObserver: function () {
			var self = this;
			var stickyBar = self.$container.find('.wcsc-mobile-sticky-bar')[0] || document.getElementById('wcsc-mobile-sticky-bar');
			if (!stickyBar) {
				return;
			}

			// In Elementor editor preview, keep the floating button visible and previewable
			if ($('body').hasClass('elementor-editor-active') || (window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode())) {
				$(stickyBar).removeClass('is-hidden');
				return;
			}

			// Track visibility of main checkout wrapper (.wcas-checkout-wrapper)
			var targetWrapper = self.$container.find('.wcas-checkout-wrapper')[0] || document.querySelector('.wcas-checkout-wrapper') || self.$container[0];
			if (!targetWrapper) {
				return;
			}

			var updateVisibility = function () {
				if (window.innerWidth >= 1024) {
					return;
				}
				var rect = targetWrapper.getBoundingClientRect();
				var windowHeight = window.innerHeight || document.documentElement.clientHeight;

				// When .wcas-checkout-wrapper enters the viewport, hide floating button
				if (rect.top < windowHeight && rect.bottom > 0) {
					$(stickyBar).addClass('is-hidden');
				} else if (rect.top >= windowHeight) {
					// User is above checkout form on landing page sections -> display floating button
					$(stickyBar).removeClass('is-hidden');
				} else {
					// User scrolled below checkout section -> keep hidden
					$(stickyBar).addClass('is-hidden');
				}
			};

			if ('IntersectionObserver' in window) {
				var observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							// Checkout enters viewport -> hide floating button
							$(stickyBar).addClass('is-hidden');
						} else {
							var rect = entry.boundingClientRect;
							// If checkout is below viewport, user is browsing higher content -> show floating button
							if (rect.top > 0) {
								$(stickyBar).removeClass('is-hidden');
							} else {
								// User has scrolled below checkout section -> keep hidden
								$(stickyBar).addClass('is-hidden');
							}
						}
					});
				}, {
					threshold: 0.01,
					rootMargin: '0px 0px 0px 0px'
				});
				observer.observe(targetWrapper);
			}

			// Safety fallback on scroll and resize
			$(window).on('scroll resize orientationchange', function () {
				updateVisibility();
			});

			updateVisibility();
		},

		syncOrderButtonAnimationColor: function () {
			var $btn = this.$container.find('.wcsc-order-now-btn, #place_order');
			if ($btn.length) {
				var el = $btn[0];
				var computedBg = window.getComputedStyle(el).backgroundColor;
				if (computedBg && computedBg !== 'rgba(0, 0, 0, 0)' && computedBg !== 'transparent') {
					el.style.setProperty('--wcsc-order-btn-bg', computedBg);
				}
			}
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

			// Update preview item thumbnail (first/main product only)
			var $cartItemImg = this.$container.find('.woocommerce-checkout-review-order-table tbody tr.wcas-order-review-product:first .wcsc-cart-item-image, .woocommerce-checkout-review-order-table tbody tr.cart_item:first .wcsc-cart-item-image');
			if ($cartItemImg.length && variation.image && variation.image.src) {
				$cartItemImg.attr('src', variation.image.src).removeAttr('srcset');
			}
		},

		showValidationModal: function (messages, firstInvalid, customTitle) {
			var self = this;
			var $modal = $('#wcsc-validation-modal, #wcsc-phone-modal');
			if (!$modal.length) {
				$modal = self.$container.find('#wcsc-validation-modal, #wcsc-phone-modal');
			}
			if ($modal.length) {
				var titleText = customTitle || 'প্রয়োজনীয় তথ্য পূরণ করুন';
				$modal.find('.wcsc-phone-modal-title, .wcsc-validation-modal-title').text(titleText);

				var $list = $modal.find('.wcsc-missing-fields-list');
				var $intro = $modal.find('.wcsc-modal-intro-text');
				if ($intro.length) {
					$intro.text('অনুগ্রহ করে নিচের তথ্যগুলো সঠিকভাবে প্রদান করুন:').show();
				}
				if ($list.length) {
					$list.empty();
					if (Array.isArray(messages)) {
						messages.forEach(function (text) {
							$list.append('<li><svg class="wcsc-missing-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg> <span>' + text + '</span></li>');
						});
					} else if (typeof messages === 'string') {
						$list.append('<li><svg class="wcsc-missing-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg> <span>' + messages + '</span></li>');
					}
				} else {
					var msgText = Array.isArray(messages) ? messages.join('<br>') : messages;
					$modal.find('.wcsc-phone-modal-message, .wcsc-validation-modal-message').html(msgText);
				}
				$modal.data('first-invalid', firstInvalid || null).fadeIn(200);
			} else if (firstInvalid) {
				$('html, body').animate({
					scrollTop: firstInvalid.offset().top - 100
				}, 300);
				firstInvalid.focus();
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
