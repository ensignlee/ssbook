if (typeof SS == 'undefined') {
	SS = {};
}

(function ($) {

SS.FilterMenu = function(name, hiddenForm, selector) {
	this.selector = selector;
	this.hiddenForm = hiddenForm;
	this.name = name;
	this.sortDir = false;
};

$.extend(SS.FilterMenu.prototype, {

	init: function(filters, filtersSelected) {
		var self = this;
		var menu = [
			{'Sort A-Z': {
				onclick: function(menuItem, menu) {
					self.sortDir = 'asc';
					self.applyFilter(menu);
				},
				icon: SS.Cake.base+'/img/icons/sort-ascend.png'}},
			{'Sort Z-A': {
				onclick: function(menuItem, menu) {
					self.sortDir = 'desc';
					self.applyFilter(menu);
				},
				icon: SS.Cake.base+'/img/icons/sort-descend.png'}},
			$.contextMenu.separator
		];

		if (filters['range']) {
			var obj = {};
			var handler = {
				onclick: function() {
					return false;
				},
				hoverClassName: 'none'
			};
			
			var gtename = 'gte'+this.name;
			var gteval = filtersSelected['gte'] !== undefined ? filtersSelected['gte'] : '';
			var keyHtml = '<label for="'+gtename+'">&gt;=</label> <input type="text" name="'+gtename+'" id="'+gtename+'" value="'+gteval+'" />';
			obj[keyHtml] = handler;
			
			var ltename = 'lte'+this.name;
			var lteval = filtersSelected['lte'] !== undefined ? filtersSelected['lte'] : '';
			keyHtml = '<label for="'+ltename+'">&lt;=</label> <input type="text" name="'+ltename+'" id="'+ltename+'" value="'+lteval+'" />';
			obj[keyHtml] = handler;
			
			menu.push(obj);
			menu.push($.contextMenu.separator);
		}
		
		if (filters['list'] !== undefined) {
			var filtersSet = (typeof filtersSelected) != 'string';
			var addobj = {
				'All' : {
					onclick: function(menuItem, menu) {
						self.toggleAll(menuItem, menu);
						return false;
					},
					className: filtersSet ? 'all-filter-item' : 'all-filter-item item-checked'
				}
			}
			menu.push(addobj);
			$.each(filters['list'], function(key, val) {
				var obj = {};
				obj[val] = {
					onclick: function(menuItem, menu) {
						return self.putCheckMark(menuItem, val, menu);
					},
					data: {'filter':val},
					className: 'filter-item'
				};
				if (!filtersSet || filtersSelected[val]) {
					obj[val]['className'] = 'filter-item item-checked';
				}
				menu.push(obj);
			});
			menu.push($.contextMenu.separator);
		}		
		menu.push({
			'<b>Apply</b>' : function(menuItem, menu) {
				self.applyFilter(menu);
			}
		});
		$(self.selector).contextMenu(menu, {
			theme: 'vista',
			hideCallback: function() {
				self.onHide(this);
			},
			bindAction: 'click',
			constrainToScreen: false
		});
		this.formSerial = $(this.hiddenForm).serialize();
	},

	applyFilter: function(cmenu) {
		cmenu.hide();
	},

	isAllChecked: function(cmenu) {
		return $(cmenu.menu).find('.all-filter-item.item-checked').length > 0;
	},

	onHide: function(cmenu) {
		var self = this;
		if (!cmenu.menu) {
			return false;
		}

		var filters = [];
		if (!this.isAllChecked(cmenu)) {
			$.each(cmenu.menu.find('.item-checked'), function () {
				var data = $(this).data('filter');
				if (data) {
					filters.push(data);
				}
			});
		}
		// Expected to be in this order by the backend. Alos need to put in blank
		// with the comma in order for it to know if the first gte value is blank
		$.each(['#gte'+this.name, '#lte'+this.name], function (key, val) {
			$.each(cmenu.menu.find(val), function() {
				filters.push($(this).val());
			});
		});
		
		if (this.sortDir) {
			$(this.hiddenForm).find('[name=sort]').val(this.name+','+this.sortDir);
		}
		
	       var f = $(this.hiddenForm);
	       var inputs = f.find('[name='+this.name+']');
	       var filtString = filters.join(',');
	       // Check for "," which is 2 empty filters for the lte and gte
	       if (inputs.length == 0 && filtString.length && filtString != ',') {
		       f.append($('<input type="hidden" name="'+this.name+'" />'));
	       }
	       f.ready(function() {
		       var inp = f.find('[name='+self.name+']');
		       inp.val(filtString).ready(function() {
			       if (f.serialize() != self.formSerial) {
				       f.submit();
			       }
		       });
	       });
	},

	toggleAll: function(menuItem, cmenu) {
		var wasSelected = $(menuItem).attr('class').indexOf('item-checked') >= 0;
		$(menuItem).toggleClass('item-checked');
		if (wasSelected) {
			$(cmenu.menu).find('.item-checked').each(function() {
				$(this).toggleClass('item-checked');
			});
		} else {
			$(cmenu.menu).find('.filter-item').addClass('item-checked');
		}
	},

	putCheckMark: function (menuItem, filter, cmenu) {
		$(cmenu.menu).find('.all-filter-item').removeClass('item-checked');
		$(menuItem).toggleClass('item-checked').data('filter', filter);
		return false;
	}
});

}(jQuery));
