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
				if (view && typeof view.activateFirstSection === 'function') {
					view.activateFirstSection = function () {};
				}

				// Staggered collapse executions to guarantee closed state upon rendering
				collapseSections(panel);
				setTimeout(function () { collapseSections(panel); }, 10);
				setTimeout(function () { collapseSections(panel); }, 50);
				setTimeout(function () { collapseSections(panel); }, 150);
				setTimeout(function () { collapseSections(panel); }, 350);

				// Collapse upon tab navigation (Content, Style, Advanced)
				if (panel && panel.$el) {
					panel.$el.off('click.wcscTabCollapse').on('click.wcscTabCollapse', '.elementor-panel-navigation-tab, .elementor-tab-control', function () {
						setTimeout(function () { collapseSections(panel); }, 20);
						setTimeout(function () { collapseSections(panel); }, 80);
						setTimeout(function () { collapseSections(panel); }, 200);
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
