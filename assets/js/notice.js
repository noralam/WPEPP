/**
 * WPEPP admin notice interactions.
 */
(function () {
	'use strict';

	function dismissBadge() {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', wpeppNotice.ajaxUrl, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.send(
			'action=wpepp_dismiss_new_badge&nonce=' +
			encodeURIComponent(wpeppNotice.nonce)
		);
	}

	function hideTooltip() {
		var tooltip = document.getElementById('wpepp-menu-tooltip');
		if (tooltip) {
			tooltip.style.transition = 'opacity .25s, transform .25s';
			tooltip.style.opacity = '0';
			tooltip.style.transform = 'translateX(-10px)';
			setTimeout(function () {
				tooltip.remove();
			}, 300);
		}
		// Also remove the badge text
		var badge = document.querySelector('.wpepp-menu-new-badge');
		if (badge) {
			badge.remove();
		}
	}

	document.addEventListener('DOMContentLoaded', function () {

		/* ── Position the menu tooltip next to the WPEPP menu item ── */
		var tooltip = document.getElementById('wpepp-menu-tooltip');
		var badge = document.querySelector('.wpepp-menu-new-badge');

		if (tooltip && badge) {
			var menuItem = badge.closest('li');
			if (menuItem) {
				var rect = menuItem.getBoundingClientRect();
				// Position to the right of admin menu
				var adminMenu = document.getElementById('adminmenu');
				var menuRight = adminMenu ? adminMenu.getBoundingClientRect().right : rect.right;
				tooltip.style.left = (menuRight + 12) + 'px';
				tooltip.style.top = Math.max(8, rect.top - 6) + 'px';
			}
		}

		/* ── Tooltip "Got it" button ── */
		var gotItBtn = document.getElementById('wpepp-tooltip-got-it');
		if (gotItBtn) {
			gotItBtn.addEventListener('click', function () {
				dismissBadge();
				hideTooltip();
			});
		}

		/* ── Tooltip "Dismiss" button ── */
		var tooltipDismiss = document.getElementById('wpepp-tooltip-dismiss');
		if (tooltipDismiss) {
			tooltipDismiss.addEventListener('click', function () {
				dismissBadge();
				hideTooltip();
			});
		}

		/* ── Dismiss Pro notice ── */
		var dismissBtn = document.getElementById('wpepp-dismiss-pro-notice');
		if (dismissBtn) {
			dismissBtn.addEventListener('click', function () {
				var notice = document.getElementById('wpepp-pro-notice');
				if (notice) {
					notice.style.transition = 'opacity .3s, max-height .3s';
					notice.style.opacity = '0';
					notice.style.maxHeight = '0';
					notice.style.overflow = 'hidden';
					notice.style.margin = '0';
					notice.style.padding = '0';
				}

				var xhr = new XMLHttpRequest();
				xhr.open('POST', wpeppNotice.ajaxUrl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.send(
					'action=wpepp_dismiss_pro_notice&nonce=' +
					encodeURIComponent(wpeppNotice.nonce)
				);
			});
		}
	});
})();
