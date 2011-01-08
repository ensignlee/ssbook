(function($) {

// On window load
$(function() {
	function convertLabel(str) {
		return str.toLowerCase().replace(/[^a-z]+/g, '_');
	}
	function hideAll(buttonEl) {
		$(buttonEl).parents('.buttons').find('td').each(function() {
			if (this != buttonEl) {
				var text = convertLabel($(this).text());
				$('.show_'+text).css('display', 'none');
			}
		});
	}
	$('.buttons td').click(function() {
		var text = convertLabel($(this).text());
		hideAll(this);
		$('.show_'+text).css('display', 'table');
	});

	// On click, set all checkboxes to whether or not this one is checked
	$('.check-all-parent').click(function() {
		$('.check-all').attr('checked', !!$(this).attr('checked'));
	});
	$('.check-all').click(function() {
		$('.check-all-parent').attr('checked', false);
	});

	// Lookup the shortlink and fix event to click
	$('.shortlink').click(function() {
		var jel = $(this);
		var thisurl = decodeURIComponent(jel.data('url'));
		jel.text('Please Wait...');
		$.getJSON(
			SS.Cake.base + '/bets/shortlink',
			{shorturl: thisurl},
			function(data) {
				jel.parent('div').html('<span>'+data['shorturl']+'</span>');
			}
		);
		return false;
	});
});

})(jQuery);
