(function($) {

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
});

})(jQuery);
