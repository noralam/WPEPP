/**
 * WPEPP admin notice interactions.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {

		/* ── Review notice dismiss ── */
		function sendReviewDismiss(snooze) {
			var notice = document.getElementById('wpepp-review-notice');
			if (notice) {
				notice.style.transition = 'opacity .3s';
				notice.style.opacity = '0';
				setTimeout(function () { notice.remove(); }, 320);
			}
			var xhr = new XMLHttpRequest();
			xhr.open('POST', wpeppNotice.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send(
				'action=wpepp_dismiss_review_notice&nonce=' +
				encodeURIComponent(wpeppNotice.nonce) +
				'&snooze=' + (snooze ? '1' : '0')
			);
		}

		var rateBtn = document.getElementById('wpepp-review-rate');
		if (rateBtn) {
			rateBtn.addEventListener('click', function () {
				sendReviewDismiss(false);
			});
		}

		var remindBtn = document.getElementById('wpepp-review-remind');
		if (remindBtn) {
			remindBtn.addEventListener('click', function () {
				sendReviewDismiss(true);
			});
		}

		var doneBtn = document.getElementById('wpepp-review-done');
		if (doneBtn) {
			doneBtn.addEventListener('click', function () {
				sendReviewDismiss(false);
			});
		}

		/* ── Dismiss Pro / 3.0 update notice ── */
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
