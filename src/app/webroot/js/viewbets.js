(function($) {

// On window load
$(function() {
	function convertLabel(str) {
		return str.toLowerCase().replace(/[^a-z]+/g, '_');
	}
	function hideAll(buttonEl) {
		$(buttonEl).parents('.buttons').find('td').each(function() {
			if (this != buttonEl) {
				var text = convertLabel($(this).data('label'));
				$('.show_'+text).css('display', 'none');
			}
		});
	}
	$('.buttons td').click(function() {
		var text = convertLabel($(this).data('label'));
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
		//jel.text('Please Wait...');
		$.get(
			SS.Cake.base + '/bets/shortlink',
			{shorturl: thisurl},
			function(data) {
				$.modal(data);
			}
		);
		return false;
	});
	
	$('#deleteBets').click(function() {
		return confirm('Are you sure that you want to delete these bet(s)?');
	});
	
	$('#editBets').click(function() {
		var serial = $('#betsForm input[type=checkbox]:checked');
		var tagids = $.map(serial, function(elm) {
			var name = $(elm).attr('name');
			return name.substring(4, name.length-1);
		});
		popupEditBetsWindow(tagids);
	});
	
	function popupEditBetsWindow(tagids) {
		$.getJSON(
			SS.Cake.base + '/bets/editbets/'+tagids.join(','),
			{},
			function (data) {
				$.modal(data.html, {onShow: function() {
					setupModalEvents(this);
				}});
			}
		);
	}
	
	function setupModalEvents(modal) {
		$('#editOkay').click(function() {
			$.post(
				SS.Cake.base + '/bets/editbets',
				$('#modalForm').serialize(),
				function (data) {
					if (data == 'true') {
						location.reload();
						modal.close();
					} else {
						alert('Unable to save data, please try again');
						return false;
					}
				}
			)
			return false;
		});
		$('#editCancel').click(function() {
			modal.close();
			return false;
		});
	}
});

})(jQuery);
