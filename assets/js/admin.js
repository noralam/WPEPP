;(function($){
	$(document).ready(function(){
		$('.wpepop-dismiss').on('click',function(){
			var url = new URL(location.href);
			url.searchParams.append('dismissed',1);
			location.href= url;
		});
		$('.wpepop-dedrev').on('click',function(){
			var url = new URL(location.href);
			url.searchParams.append('revadded',1);
			location.href= url;
		});

		$('.wpepp-dismiss-update').on('click', function() {
			var $notice = $(this).closest('.wpepp-update-notice');
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpepp_dismiss_update_notice',
					nonce: wpeppAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$notice.fadeOut();
					}
				}
			});
		});
	});

	
})(jQuery);
