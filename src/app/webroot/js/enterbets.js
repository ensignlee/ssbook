if (typeof SS == 'undefined') {
	SS = {};
}

(function ($) {

SS.calcWin = function(risk, odds) {
	if(odds > 0) {
		return risk*odds/100;
	} else {
		return risk/odds*-100;
	}
};

SS.Superbar = function(selector, Enterbets) {
	this.jSelect = $(selector);

	this.jSelect.keydown($.proxy(this.onKeyPress, this));
	this.jSelect.keyup($.proxy(this.onKeyUp, this));
	this.jSelect.focus($.proxy(this.onFocus, this));

	this.url = SS.Cake.base + '/bets/ajax/superbar';
	this.divHeight = '300px';
	this.lastVal = '';

	this.lastRequest = null;

	this.Enterbets = Enterbets;
	this.doneLoadingBet = true;
};

$.extend(SS.Superbar.prototype, {

	focus : function() {
		this.jSelect.focus();
	},

	getValue : function() {
		return this.jSelect.val();
	},

	onFocus : function(e) {
		if (this.doneLoadingBet) {
			this.lastVal = null;
			this.onKeyUp();
		}
	},

	onKeyUp : function(e) {
		var val = this.getValue();
		if (val != this.lastVal && this.doneLoadingBet) {
			this.request(val);
			this.lastVal = val;
		}
	},

	onKeyPress : function(e) {
		switch (e.which) {
		case 38: // Up
			this.goUp();
			e.stopPropagation();
			return false;
		case 40: // Down
			this.goDown();
			e.stopPropagation();
			return false;
		case 13: // Enter
			this.selectCurrent();
			e.stopPropagation();
			return false;
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
		this.abort();

		if (hli) {
			var text = $.trim(hli.text());
			this.jSelect.val('');
			this.lastVal = text;
			var clazzez = hli.attr('class').split(' ');
			var _this = this;

			$.each(clazzez, function (key, clazz) {
				if (!clazz.match(/scoreid_/)) {
					return false;
				}
				var scoreid = clazz.replace('scoreid_', '');
				_this.gameClick(scoreid);
			});
		} else {
			this.createGame(null);
		}
		this.hideDiv();
	},

	goUp : function() {
		if (this['dropdownDiv'] === undefined) {
			return false;
		}

		var hli = this.getHoverLi();		
		if (hli) {
			hli.removeClass('hover');
			hli.prev().addClass('hover');
		} else {
			this.dropdownDiv.find('li:last').addClass('hover');
		}
	},
	
	goDown : function() {
		if (this['dropdownDiv'] === undefined) {
			return false;
		}

		var hli = this.getHoverLi();		
		if (hli) {
			hli.removeClass('hover');
			hli.next().addClass('hover');
		} else {
			this.dropdownDiv.find('li:first').addClass('hover');
		}
	},

	response : function (data, textStatus) {
		if (!data) {
			return false;
		}
		if (textStatus != 'success') {
			alert('Unable to read from server please try again');
		}
		this.showDropdown(data);
	},

	showDropdown : function (data) {
		if (!data.length) {
			this.hideDiv();
			return false;
		}

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
		this.dropdownDiv.html(html).find('li')
			.click($.proxy(this.selectCurrent, this))
			.hover(function() { $(this).addClass('hover') }, function() { $(this).removeClass('hover') });
	},

	gameClick : function (scoreid) {
		this.createGame(scoreid);
	},

	createGame : function (scoreid) {
		var data = { scoreid : scoreid };
		if (!scoreid) {
			data['text'] = this.getValue();
		}
		this.doneLoadingBet = false;
		this.Enterbets.done = $.proxy(this, 'enterbetsDone');
		this.Enterbets.add(data);
	},

	enterbetsDone : function() {
		this.doneLoadingBet = true;
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
		$(window).one('click', $.proxy(this.hideDiv, this));
	},

	hideDiv : function() {
		if (this['dropdownDiv'] !== undefined) {
			this.dropdownDiv.css('display', 'none');
		}
	},

	abort : function() {
		if (this.lastRequest) {
			this.lastRequest.abort();
		}
		this.lastRequest = null;
	},

	request : function(val) {
		this.abort();

		if (val.length >= 2) {
			this.lastRequest = $.getJSON(this.url, {text : val}, $.proxy(this.response, this));
		} else {
			this.hideDiv();
		}
	}

});

SS.Enterbets = function(selector) {
	this.jSelect = $(selector);
	this.jBets = null;
	this.url = SS.Cake.base + '/bets/createbets';
	this.ajaxUrl = SS.Cake.base + '/bets/ajax/getbet';
	this.iconurl = SS.Cake.base + '/img/icons/';
	this.idenNumber = 0;
}

SS.Enterbets.TYPES = [
	{name:'spread',desc:"Spread",show:'Spread'},
	{name:'total',desc:"Total",show:'Total'},
	{name:'moneyline',desc:"Moneyline",show:''},
	{name:'half_spread',desc:"1st Half Spread",show:'Spread'},
	{name:'half_total',desc:"1st Half Total",show:'Total'},
	{name:'half_moneyline',desc:"1st Half Moneyline",show:''},
	{name:'second_spread',desc:"2nd Half Spread",show:'Spread'},
	{name:'second_total',desc:"2nd Half Total",show:'Total'},
	{name:'second_moneyline',desc:"2nd Half Moneyline",show:''}
];

$.extend(SS.Enterbets.prototype, {

	render : function() {
		this.jSelect.html("<form action='"+this.url+"' method='post'><div class='bets'>&nbsp;</div><div class='record'><input type='submit' value='Add Bets' /><button id='parlay' type='button'>Parlay/Teaser</button></div>");
		var _this = this;
		this.jSelect.ready(function() {
			_this.jBets = _this.jSelect.find('.bets');
			_this.jSelect.find('form').submit($.proxy(_this.onSubmit, _this));
			_this.jSelect.find('#parlay').click($.proxy(_this, 'onParlay'));
		});
	},

	onParlay : function() {
		var _this = this;
		var parlaybets = this.jBets.find(':checked').parents('.bet');

		var gamesinfo = [];
		var success = !!parlaybets.length && parlaybets.length > 1;
		parlaybets.each(function(key, bet) {
			var info = _this.getBetInfo($(bet));
			success = success && _this.betParlayValid(info);	
			gamesinfo.push(info);
		});

		if (!success) {
			alert('Unable to create parlay');
			return false;
		}

		this.idenNumber++;
		var iden = 'parlay_'+this.idenNumber;
		var bet = this.renderParlay(gamesinfo, iden);
		var calcedOdd = '', prevCalcedOdd = 100;
		$.each(gamesinfo, function(key, val) {
			if (val.odds == '') {
				calcedOdd = '';
				return false;
			}
			if (calcedOdd == '') {
				calcedOdd = 0;
			}
			calcedOdd = SS.calcWin(prevCalcedOdd, val.odds);
			calcedOdd += prevCalcedOdd;
			prevCalcedOdd = calcedOdd;
		});
		calcedOdd -= 100;
		if (calcedOdd < 100) {
			calcedOdd = Math.round(-1000000/calcedOdd)/100;
		} 

		var _this = this;
		this.jBets.prepend(bet).ready(function() {
			bet.find('.risk').focus();
			bet.find('.odds').val(calcedOdd);
			bet.append(_this.buildBetInput(gamesinfo, iden));
			_this.setupEvents(bet, gamesinfo, iden);
			parlaybets.remove();
		});
		return false;
	},

	buildBetInput : function(parlaybets, iden) {
		var h = '';
		var _this = this;
		$.each(parlaybets, function (key, val) {
			h += '<input type="hidden" name="parlay['+iden+']['+val.iden+']" value="'+_this.betInfoToCSV(val)+'" />';
		});
		return $(h);
	},

	betInfoToCSV : function(betinfo) {
		var ret = [];
		$.each(betinfo, function(key, val) {
			ret.push(key+'='+val);
		});
		return ret.join(';');
	},

	betParlayValid : function(info) {
		switch(info.type) {
		case 'moneyline':
		case 'half_moneyline':
		case 'second_moneyline':
		case 'spread':
		case 'half_spread':
		case 'second_spread':
		case 'total':
		case 'half_total':
		case 'second_total':
			return info.spread != '';
		}			
		return false;
	},

	onSubmit : function () {
		//console.debug('currently submitting');
		if (!this.validateAll()) {
			alert('Unable to validate all');
			return false;
		}
	},

	/**
         * Adding a bet with {scoreid, [text]}
         */
	add : function (data) {
		var _this = this;
		$.getJSON(this.ajaxUrl, data, function(data) {
			_this.done();
			if (data) {
				_this.show(data);
			}
		});
	},

	done : function() {},

	/**
	 * @param <string> iden Identifier "SS[scoreid]" "incremental"
	 */
	renderBet : function (home, visitor, datetime, type, iden) {
		var h = '<td class="icon"><img src="'+this.iconurl+'wrong.png"/></td><td><select class="type" name="type['+iden+']">';
		$.each(SS.Enterbets.TYPES, function (key, val) {
			h += '<option value="'+val.name+'"';
			if (val.name == type) {
				h += ' selected="selected"';
			}
			h += '>'+val.desc+'</option>';
		});
		h += '</select></td>';
		h += '<td class="direction">&nbsp;</td>';

		h += '<td><input type="text" class="spread" name="spread['+iden+']" /></td>';
		h += '<td><input type="text" class="risk" name="risk['+iden+']" /></td>';
		h += '<td><input type="text" class="odds" name="odds['+iden+']" /></td>';
		h += '<td><input type="text" class="towin" name="towin['+iden+']" /></td>';
		h += '<td><input type="text" class="book" name="book['+iden+']" /></td>';
		var ttl = '<tr><td><input type="checkbox" /></td><td colspan="2">Type</td><td class="type_header">&nbsp;</td><td>Risk</td><td>Odds</td><td>To Win</td><td>Book</td></tr>';

		var datestr = datetime.toString('M/d/yy h:mm tt');
		var date_std = datetime.toString('yyyy-MM-dd HH:mm:ssZ');
		var je = $('<div class="bet"><table><tr><td>&nbsp;</td><td colspan="7" class="teamnames"><span class="teamnames_visitor">'+visitor+'</span> @ <span class="teamnames_home">'+home+'</span> <span class="teamnames_datestr">'+datestr+'<input type="hidden" name="date_std['+iden+']" class="date_std" value="'+date_std+'" /></td></td></tr>'+ttl+'<tr>'+h+'</tr></table><div class="close"><img src="'+this.iconurl+'close.png" /></div></div>');
		return je;
	},

	renderParlay : function (gamesinfo, iden) {
		var title = this.titleText(gamesinfo);
		var h = '<div class="bet-parlay"><table><tr><td>&nbsp;</td><td colspan="7">'+title+'</td></tr>';
		h += '<tr><td>&nbsp;</td><td colspan="2">Type</td>';
		h += '<td>Games</td><td>Risk</td><td>Odds</td><td>To Win</td><td>Book</td></tr>';
		h += '<tr><td class="icon">&nbsp;</td><td>';
		h += '<select name="type['+iden+']"><option value="parlay">Parlay/Teaser</option></select>';
		h += '</td><td>&nbsp;</td>';

		h += '<td>'+gamesinfo.length+'</td>';
		h += '<td><input type="text" class="risk" name="risk['+iden+']" /></td>';
		h += '<td><input type="text" class="odds" name="odds['+iden+']" /></td>';
		h += '<td><input type="text" class="towin" name="towin['+iden+']" /></td>';
		h += '<td><input type="text" class="book" name="book['+iden+']" /></td>';
		h += '</tr><td>&nbsp;</td><td colspan="7" class="gametext">';
		h += this.gameText(gamesinfo);
		h += '</td></tr></table>';
		h += '<div class="close"><img src="'+this.iconurl+'close.png" /></div></div>';

		return $(h);
	},

	titleText : function(gamesinfo) {
		if (!gamesinfo || !gamesinfo.length) {
			return '';
		}
		var t = [];
		$.each(gamesinfo, function(key, val) {
			t.push(val.teamnames);
		});
		return t.join(', ');
	},

	gameText : function(gamesinfo) {
		if (!gamesinfo || !gamesinfo.length) {
			return '';
		}
		var t = '';
		var _this = this;
		$.each(gamesinfo, function(key, val) {
			t += '<div>'+_this.singleGameText(val)+'</div>';
		});
		return t;
	},

	singleGameText : function(game) {
		var t = '';
		var spread = game.spread;
		switch(game.type) {
		case 'moneyline':
		case 'half_moneyline':
		case 'second_moneyline':
			spread = 'M/L';			
		case 'spread':
		case 'half_spread':
		case 'second_spread':
			if (game.direction == 'home') {
				t += game.home;
			} else {
				t += game.visitor;
			}
			return t+ ' '+spread;
		case 'total':
		case 'half_total':
		case 'second_total':
			return game.visitor+' @ '+game.home+' '+game.type+' '+spread;
		}
		return '';
	},

	getBetInfo : function(bet) {
		var info = {};
		info['teamnames'] = bet.find('.teamnames').text();
		info['home'] = bet.find('.teamnames_home').text();
		info['visitor'] = bet.find('.teamnames_visitor').text();
		info['datestr'] = bet.find('.teamnames_datestr').text();
		info['date_std'] = bet.find('.date_std').val();
		info['spread'] = bet.find('.spread').val();
		var iden = /[a-zA-Z]+[0-9_]+/.exec(bet.find('.risk').attr('name'));
		info['iden'] = iden[0];
		info['risk'] = bet.find('.risk').val();
		info['odds'] = bet.find('.odds').val();
		info['towin'] = bet.find('.book').val();
		info['type'] = bet.find('.type').val();
		info['direction'] = bet.find('.direction select').val();
		return info;
	},

	spreadChange : function(bet, val) {
		//console.debug('spreadChange', bet, val);
		this.validate(bet);
	},

	calcRisk : function(win, odds) {
		if(odds == 0)
			return;

		if(odds > 0) {
			return Math.round(win*10000/odds)/100;
		} else {
			return Math.round(win/-1*odds)/100;
		}
	},
	
	calcWin : function (risk, odds) {
		if(odds == 0)
			return;

		return Math.round(SS.calcWin(risk, odds)*100)/100;
	},

	oddsChange : function(bet, val) {
		var odds = parseFloat(bet.find('.odds').val());
		var towin = parseFloat(bet.find('.towin').val());

		if (odds == 0 || odds == NaN) {
			return;
		}
		this.riskChange(bet, val);
		this.validate(bet);
	},

	riskChange : function(bet, val) {
		var risk = parseFloat(bet.find('.risk').val());
		var odds = parseFloat(bet.find('.odds').val());
		if (!(isNaN(risk) || isNaN(odds)) && risk > 0 && odds != 0) {
			bet.find('.towin').val(this.calcWin(risk, odds));
		}
		this.validate(bet);
	},
	
	towinChange : function(bet, val) {
		var towin = parseFloat(bet.find('.towin').val());
		var odds = parseFloat(bet.find('.odds').val());
		if (!(isNaN(towin) || isNaN(odds)) && towin > 0 && odds != 0) {
			bet.find('.risk').val(this.calcRisk(towin, odds));
		}
		this.validate(bet);
	},
	
	typeChange : function(bet, type, data, iden) {
		var _this = this;
		var dir = bet.find('.direction select').val();
		$.each(SS.Enterbets.TYPES, function (key, val) {
			if (val.name == type) {
				bet.find('.type_header').text(val.show);
				return false;
			} 
		});
		// Set the other stuff
		var odd = null;
		if (data.odds !== undefined && data.odds.length) {
			$.each(data.odds, function (key, val) {
				if (val.type == type) {
					odd = val;
					return false;
				}
			});
		}
		var h = '<select name="direction['+iden+']">';
		var hsel = '';
		var vsel = '';
		
		// Default to visitor for non totals
		if (!dir && !(type == 'total' || type == 'second_total' || type == 'half_total')) {
			dir = 'visitor';
		}
		// Do not use dir if it is a non total direction
		if (type == 'total' || type == 'second_total' || type == 'half_total') {
			if (dir && dir == 'visitor' || dir == 'home') {
				dir = null;
			}
		}

		if (!dir || dir == 'over' || dir == 'home') {
			hsel = 'selected="selected"';
		} else {
			vsel = 'selected="selected"';
		}

		var home = data.home;
		var visitor = data.visitor;
		if (type == 'half_spread' || type == 'half_moneyline' || type == 'spread' || type == 'moneyline' || type == 'second_spread' || type == 'second_moneyline') {
			h += '<option '+vsel+' value="visitor">'+visitor+'</option><option '+hsel+' value="home">'+home+'</option>';
		} else {
			h += '<option '+hsel+' value="over">Over</option><option '+vsel+' value="under">Under</option>';
		}
		h += '</select>';
		var setodd = function() {
			var newdir = bet.find('.direction select').val();
			_this.setOdd(bet, odd, type, newdir);
		}
		bet.find('.direction').html(h).change(setodd).ready(function() {
			if (type == 'moneyline' || type == 'half_moneyline' || type == 'second_moneyline') {
				bet.find('.spread').hide();
			} else {
				bet.find('.spread').show();
			}
			
			setodd(bet, odd, dir);
		});
	},
	
	setOdd : function (bet, odd, type, dir) {
		if (odd) {
			//console.debug('odd', odd);
			switch(type) {
			case 'moneyline':
			case 'half_moneyline':
			case 'second_moneyline':
				bet.find('.spread').val('0');
				if (dir == 'home') {
					bet.find('.odds').val(odd.odds_home);
				} else {
					bet.find('.odds').val(odd.odds_visitor);
				}
				break;
			case 'spread':
			case 'half_spread':
			case 'second_spread':
				if (dir == 'home') {
					bet.find('.spread').val(odd.spread_home);
				} else {
					bet.find('.spread').val(odd.spread_visitor);
				}
				bet.find('.odds').val('-110');
				break;
			case 'total':
			case 'half_total':
			case 'second_total':
				if (dir == 'over') {
					bet.find('.spread').val(odd.total);
				} else {
					bet.find('.spread').val(odd.total);
				}
				bet.find('.odds').val('-110');
				break;
			}
		} else {
			bet.find('.spread').val('');
			bet.find('.odds').val('-110');
		}

		this.oddsChange(bet, bet.find('.odd').val());
		this.validate(bet);
	},

	validate : function(bet) {
		var sels = ['.risk', '.odds', '.towin'];
		var success = true;
		$.each(sels, function(key, val) {
			var v = bet.find(val).val();
			if (v == NaN || v == 0) {
				success = false;
				return false;
			}
		});
		if (success) {
			bet.find('.icon img').attr('src', this.iconurl+'check.png');
			return true;
		} else {
			bet.find('.icon img').attr('src', this.iconurl+'wrong.png');
			return false;
		}
	},

	validateAll : function() {
		var success = true;
		var _this = this;
		this.jBets.find('.bet').each(function (idx, bet) {
			if (!_this.validate($(bet))) {
				success = false;
			}
		});
		return success;
	},

	closeBet : function(bet) {
		//console.debug('close', bet);
		bet.remove();
	},

	setupEvents : function(bet, data, iden) {
		var _this = this;
		bet.find('.spread').keyup(function() { _this.spreadChange(bet, bet.find('.spread').val()); });
		bet.find('.risk').keyup(function() { _this.riskChange(bet, bet.find('.risk').val()); });
		bet.find('.odds').keyup(function() { _this.oddsChange(bet, bet.find('.odds').val()); });
		bet.find('.towin').keyup(function() { _this.towinChange(bet, bet.find('.towin').val()); });
		bet.find('.close img').click(function() { _this.closeBet(bet); });

		var typeC = function() { _this.typeChange(bet, bet.find('.type').val(), data, iden); };
		bet.find('.type').change(typeC);
		typeC();
	},
	
	show : function (data) {
		var num = this.idenNumber++;
		var iden = 'SS'+data.scoreid+'_'+num;
		var bet = this.renderBet(data.home, data.visitor, new Date(data.game_date), data.type, iden);
		var _this = this;
		this.jBets.prepend(bet).ready(function() {
			_this.setupEvents(bet, data, iden);
		});
	}

});

SS.Accorselect = function(select, Enterbets, startdateselect, enddateselect) {
	this.jSelect = $(select);
	this.jStartdate = $(startdateselect);
	this.jEnddate = $(enddateselect);
	this.Enterbets = Enterbets;
	this.url = SS.Cake.base + '/bets/ajax/accorselect';
}

$.extend(SS.Accorselect.prototype, {
	
	setupDates : function() {
		var _this = this;
		this.jStartdate.change(function() {
			_this.find();
		});
		this.jEnddate.change(function() {
			_this.find();
		});		
	},

	render : function(json) {
		var h = '';
		var leagues = json.leagues;
		var startdate = json.startdate;
		var enddate = json.enddate;
		this.setStartdate(startdate);
		this.setEnddate(enddate);

		$.each(leagues, function(league, games) {
			h += '<h1 class="head">'+league+'</h1><ul>';
			$.each(games, function (key, game) {
				var clazz = '';
				if (game.odds) {
					clazz = ' withodds';
				}
				h += '<li class="selectgame-'+game.scoreid+clazz+'">'+game.desc+'</li>';
			});
			h += '</ul>';
		});

		var _this = this;
		this.jSelect.html(h).ready(function(){
			_this.jSelect.find('.head').click(function() {
				$(this).next().toggle('fast');
				return false;
			}).next().hide();

			_this.jSelect.find('li[class^=selectgame]').click(function() {
				var clazz = $(this).attr('class').split('-');
				var data = { scoreid : clazz[1] };
				_this.Enterbets.add(data);
				//$(this).parent().toggle('fast');
				return false;
			}).hover(function () { $(this).addClass('hover') }, function() { $(this).removeClass('hover') });
		});
	 },

	setStartdate : function(dt) {
		this.jStartdate.val(dt);
	},

	setEnddate : function(dt) {
		this.jEnddate.val(dt);
	},

	getStartdate : function() {
		return new Date(this.jStartdate.val());
	},

	getEnddate : function() {
		return new Date(this.jEnddate.val());
	},

	find : function() {
		this.findDates(this.getStartdate(), this.getEnddate());
	},

	findDates : function(startdate, enddate) {
		var _this = this;
		$.getJSON(this.url, {
				startdate : startdate.toString('yyyy-MM-dd'),
				enddate : enddate.toString('yyyy-MM-dd')
			},
			function (json) {
				_this.render(json);
			});
	}
});

$(function() {
	var enterbets = new SS.Enterbets('#enterbets');
	enterbets.render();
	var superbar = new SS.Superbar('#superbar', enterbets);
	var accorselect = new SS.Accorselect('#accorselect', enterbets, 'input[name=startdate]', 'input[name=enddate]');
	accorselect.setupDates();
	accorselect.find();	

	superbar.focus();
});

})(jQuery);
