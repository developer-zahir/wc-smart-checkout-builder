/**
 * WC Smart Checkout Builder - Elementor Editor Panel Integration
 *
 * Ensures all accordion sections start collapsed by default when
 * the widget settings panel is opened, allowing the user to
 * selectively expand sections without clutter.
 */

(function ($) {
	'use strict';

	function initEditorHooks() {
		if (!window.elementor || !window.elementor.hooks) {
			return;
		}

		var collapseSections = function (panel) {
			var $container = (panel && panel.$el && panel.$el.length) ? panel.$el : $('#elementor-panel');
			var $openSections = $container.find('.elementor-control-section.elementor-open');
			$openSections.removeClass('elementor-open');
			$openSections.find('.elementor-section-content').hide();
		};

		var hookWidget = function (widgetName) {
			elementor.hooks.addAction('panel/open_editor/widget/' + widgetName, function (panel, model, view) {
				var userInteracted = false;
				if (view && typeof view.activateFirstSection === 'function') {
					view.activateFirstSection = function () {};
				}

				var safeCollapse = function () {
					if (!userInteracted) {
						collapseSections(panel);
					}
				};

				// Initial collapses
				safeCollapse();
				setTimeout(safeCollapse, 20);
				setTimeout(safeCollapse, 60);

				// Prevent delayed collapse from closing section clicked by user
				if (panel && panel.$el) {
					panel.$el.off('click.wcscSectionClick').on('click.wcscSectionClick', '.elementor-section-title', function () {
						userInteracted = true;
					});

					// Collapse upon tab navigation (Content, Style, Advanced)
					panel.$el.off('click.wcscTabCollapse').on('click.wcscTabCollapse', '.elementor-panel-navigation-tab, .elementor-tab-control', function () {
						userInteracted = false;
						setTimeout(safeCollapse, 20);
						setTimeout(safeCollapse, 60);
					});
				}
			});
		};

		hookWidget('wcsc_product_checkout');
		hookWidget('wcsc_thank_you');
	}

	if (window.elementor && window.elementor.hooks) {
		initEditorHooks();
	} else {
		$(window).on('elementor:init', initEditorHooks);
	}
})(jQuery);
