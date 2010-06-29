if (typeof SS == 'undefined') {
	SS = {};
}

(function ($) {

SS.Superbar = function(selector, jDateSelector) {
	this.jSelect = $(selector);
	this.jDateSelector = jDateSelector;
	this.jSelect.keyup($.proxy(this.onKeyUp, this));
	this.url = SS.Cake.base + '/bets/ajax/superbar';
	this.divHeight = '300px';
}

$.extend(SS.Superbar.prototype, {

	getValue : function() {
		return this.jSelect.val();
	},

	onKeyUp : function(e) {
		var val = this.getValue();
		switch (e.which) {
		case 38: // Up
			this.goUp();
			return false;
		case 40: // Down
			this.goDown();
			return false;
		case 13: // Enter
			this.selectCurrent();
			return false;
		default:
			this.request(val);
		}
	},

	getHoverLi : function() {
		if (this['dropdownDiv'] === undefined) {
			return null;
		}
		var out = this.dropdownDiv.find('.hover');
		if (out.length) {
			return out;
		} else {
			return null;
		}
	},

	selectCurrent : function () {
		var hli = this.getHoverLi();
		if (hli) {
			this.jSelect.val(hli.text());
		}
		this.hideDiv();
	},

	goUp : function() {
		var hli = this.getHoverLi();		
		if (hli) {
			hli.removeClass('hover');
			hli.prev().addClass('hover');
		} else {
			this.dropdownDiv.find('li:last').addClass('hover');
		}
	},
	
	goDown : function() {
		var hli = this.getHoverLi();		
		if (hli) {
			hli.removeClass('hover');
			hli.next().addClass('hover');
		} else {
			this.dropdownDiv.find('li:first').addClass('hover');
		}
	},

	response : function (data, textStatus) {
		if (textStatus != 'success') {
			alert('Unable to read from server please try again');
		}
		this.showDropdown(data);
	},

	showDropdown : function (data) {
		var p = this.jSelect.position();
		var h = this.jSelect.outerHeight();
		var w = this.jSelect.innerWidth();
		var l = p.left;
		var t = p.top + h;
		var _this = this;
		 
		this.createOrShowDiv(t, l, w);

		var html = '<ul>';
		$.each(data, function (key, v) {
			html += '<li class="scoreid_'+v.scoreid+'">'+v.html+'</li>';
		});
		html += '</ul>';
		this.dropdownDiv.html(html).find('li').click(function() {
			var clazzez = $(this).attr('class').split(' ');
			$.each(clazzez, function (key, clazz) {
				if (!clazz.match(/scoreid_/)) {
					return false;
				}
				var scoreid = clazz.replace('scoreid_', '');
				_this.gameClick(scoreid);
			});
		}).hover(function() { $(this).addClass('hover') }, function() { $(this).removeClass('hover') });
	},

	gameClick : function (scoreid) {
		console.debug('scoreid', scoreid);
		this.selectCurrent();
	},

	createOrShowDiv : function(t, l, w) {
		if (this['dropdownDiv'] === undefined) {
			var ndiv = $('<div class="dropdown"></div>').appendTo($('body'));
			ndiv.css({
				top : t+'px',
				left : l+'px',
				width : w+'px',
				height : this.divHeight+'px'
			});
			this.dropdownDiv = ndiv;
		}
		this.dropdownDiv.css('display', 'block');		
		$('body').one('click', $.proxy(this.hideDiv, this));
	},

	hideDiv : function() {
		if (this['dropdownDiv'] !== undefined) {
			this.dropdownDiv.css('display', 'none');
		}
	},

	request : function(val) {
		if (val.length >= 2) {
			$.getJSON(this.url, {text : val}, $.proxy(this.response, this));
		} else {
			this.hideDiv();
		}
	}

});

$(function() {
var superbar = new SS.Superbar('#superbar');
});

})(jQuery);
