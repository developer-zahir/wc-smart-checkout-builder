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

		// Hook into opening the widget settings panel
		elementor.hooks.addAction('panel/open_editor/widget/wcsc_product_checkout', function (panel, model, view) {
			var collapseAllSections = function () {
				if (!panel || !panel.$el) {
					return;
				}
				var $openSections = panel.$el.find('.elementor-control-section.elementor-open');
				$openSections.removeClass('elementor-open');
				$openSections.find('.elementor-section-content').hide();
			};

			// Collapse upon initial panel render
			setTimeout(collapseAllSections, 20);
			setTimeout(collapseAllSections, 80);
			setTimeout(collapseAllSections, 200);

			// Collapse upon tab navigation (Content, Style, Advanced)
			panel.$el.off('click.wcscTabSwitch').on('click.wcscTabSwitch', '.elementor-panel-navigation-tab, .elementor-tab-control', function () {
				setTimeout(collapseAllSections, 20);
				setTimeout(collapseAllSections, 80);
			});
		});

		// Also apply to Thank You widget panel
		elementor.hooks.addAction('panel/open_editor/widget/wcsc_thank_you', function (panel, model, view) {
			var collapseAllSections = function () {
				if (!panel || !panel.$el) {
					return;
				}
				var $openSections = panel.$el.find('.elementor-control-section.elementor-open');
				$openSections.removeClass('elementor-open');
				$openSections.find('.elementor-section-content').hide();
			};

			setTimeout(collapseAllSections, 20);
			setTimeout(collapseAllSections, 80);
			setTimeout(collapseAllSections, 200);

			panel.$el.off('click.wcscTabSwitch').on('click.wcscTabSwitch', '.elementor-panel-navigation-tab, .elementor-tab-control', function () {
				setTimeout(collapseAllSections, 20);
				setTimeout(collapseAllSections, 80);
			});
		});
	}

	if (window.elementor && window.elementor.hooks) {
		initEditorHooks();
	} else {
		$(window).on('elementor:init', initEditorHooks);
	}
})(jQuery);
