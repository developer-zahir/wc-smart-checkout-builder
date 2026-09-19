/**
 * WC Smart Checkout Builder - Elementor Editor Panel Integration
 *
 * Ensures all accordion sections start collapsed by default when
 * opening the Style Tab or settings panel, allowing the user to
 * selectively expand sections without auto-expanded clutter.
 */

(function ($) {
	'use strict';

	var userInteracted = false;
	var userClickTimer = null;

	function markUserClick() {
		userInteracted = true;
		clearTimeout(userClickTimer);
		userClickTimer = setTimeout(function () {
			userInteracted = false;
		}, 800);
	}

	function collapseAllSections($container) {
		if (userInteracted) {
			return;
		}
		var $target = ($container && $container.length) ? $container : $('#elementor-panel');
		var $openSections = $target.find('.elementor-control-section.elementor-open');
		if ($openSections.length) {
			$openSections.removeClass('elementor-open');
			$openSections.find('.elementor-section-content').hide();
		}
	}

	function runTimedCollapse($container) {
		userInteracted = false;
		var intervals = [10, 30, 60, 100, 160, 250, 400, 600];
		intervals.forEach(function (ms) {
			setTimeout(function () {
				if (!userInteracted) {
					collapseAllSections($container);
				}
			}, ms);
		});
	}

	function initEditorHooks() {
		// Detect direct user clicks on section titles
		$(document).on('mousedown.wcscSection click.wcscSection', '#elementor-panel .elementor-section-title', function () {
			markUserClick();
		});

		// When clicking any tab navigation (especially Style tab)
		$(document).on('click.wcscTab', '#elementor-panel .elementor-panel-navigation-tab, #elementor-panel .elementor-tab-control, #elementor-panel [data-tab]', function () {
			userInteracted = false;
			if (window.elementor && typeof window.elementor.getPanelView === 'function') {
				var pv = window.elementor.getPanelView();
				if (pv && typeof pv.getCurrentPageView === 'function') {
					var cpv = pv.getCurrentPageView();
					if (cpv && typeof cpv.activateFirstSection === 'function') {
						cpv.activateFirstSection = function () {};
					}
				}
			}
			runTimedCollapse($('#elementor-panel'));
		});

		if (!window.elementor || !window.elementor.hooks) {
			return;
		}

		var hookWidget = function (widgetName) {
			elementor.hooks.addAction('panel/open_editor/widget/' + widgetName, function (panel, model, view) {
				if (view && typeof view.activateFirstSection === 'function') {
					view.activateFirstSection = function () {};
				}
				if (panel && panel.currentView && typeof panel.currentView.activateFirstSection === 'function') {
					panel.currentView.activateFirstSection = function () {};
				}

				runTimedCollapse(panel && panel.$el ? panel.$el : $('#elementor-panel'));

				if (panel && panel.$el) {
					panel.$el.off('click.wcscTabCollapse').on('click.wcscTabCollapse', '.elementor-panel-navigation-tab, .elementor-tab-control', function () {
						userInteracted = false;
						if (panel.currentView && typeof panel.currentView.activateFirstSection === 'function') {
							panel.currentView.activateFirstSection = function () {};
						}
						runTimedCollapse(panel.$el);
					});
				}
			});
		};

		hookWidget('wcsc_product_checkout');
		hookWidget('wcsc_thank_you');

		// Monitor Elementor editor channel for tab switches
		if (window.elementor.channels && window.elementor.channels.editor) {
			window.elementor.channels.editor.on('section:activated', function (sectionName, editor) {
				if (!userInteracted) {
					collapseAllSections($('#elementor-panel'));
				}
			});
		}
	}

	if (window.elementor && window.elementor.hooks) {
		initEditorHooks();
	} else {
		$(window).on('elementor:init', initEditorHooks);
	}
})(jQuery);
