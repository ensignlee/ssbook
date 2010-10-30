if (typeof SS == 'undefined') {
	SS = {};
}

(function ($) {

SS.FilterMenu = function(selector) {
	this.selector = selector;
};

$.extend(SS.FilterMenu.prototype, {
	
	init: function(filters) {
		var menu = [];
		var self = this;
		$.each(filters, function(key, val) {
			var obj = {};
			obj[val] = {
				onclick: function(menuItem) {
					return self.putCheckMark(menuItem, val);
				}
			};
			menu.push(obj);
		});
		$(self.selector).contextMenu(menu, {
			theme: 'vista',
			hideCallback: function() {
				self.onHide(this);
			}
		});
	},

	onHide: function(cmenu) {
		if (!cmenu.menu) {
			return false;
		}

		var filters = [];
		$.each(cmenu.menu.find('.item-checked'), function () {
			var data = $(this).data('filter');
			if (data) {
				filters.push(data);
			}
		});
		console.info('filters', filters);
	},

	putCheckMark: function (menuItem, filter) {
		$(menuItem).find('.context-menu-item-inner').toggleClass('item-checked').data('filter', filter);
		return false;
	}
});

}(jQuery));