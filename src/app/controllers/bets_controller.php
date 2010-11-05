<?php

class BetsController extends AppController {
	var $name = 'Bets';
	var $uses = array('LeagueType', 'Odd', 'Score', 'SourceType', 'UserBet');
	var $helpers = array('Html','Ajax','Javascript');
	var $components = array('Auth', 'Session', 'RequestHandler');

	public function index() {
	}
	
	public function v($id = null) {
		if (empty($id)) {
			$this->Session->setFlash('Invalid id');
		}
		$this->UserBet->id = $id;
		$scoreid = $this->UserBet->read('scoreid');
		$this->Score->id = $id;
		$score = $this->Score->read();
		$this->set('score', $score);
	}

	public function delete($id = null) {
		if (empty($id)) {
			$this->Session->setFlash('Invalid id');
		}
		$this->UserBet->id = $id;
		if ($this->UserBet->delete()) {
			$this->Session->setFlash('Bet Removed');
		} else {
			$this->Session->setFlash('Unable to remove bet');
		}
		$this->redirect('/bets/view');
	}

	private function superbar($params, $date) {
		$text = $params['text'];
		$text = strtolower(" $text "); //give us some working room
		$match = array();
		if (preg_match('%((0?[1-9]|1[012])(:[0-5]\d){0,2}\s*([AP]M|[ap]m))%', $text, $match)) {
			$text = str_replace($match[0], '', $text);
		}

		$options = array();
		if (preg_match('@[0-9]+/[0-9]+/[0-9]+@', $text, $match)) {
			$mstr = $match[0];
			$strdate = strtotime($mstr);
			if ($strdate > strtotime('2010-01-01')) {
				$text = str_replace($mstr, '', $text);
				$options['game_date'] = date('Y-m-d', $strdate);
			}
		}
		if (!empty($params['startdate']) && !empty($params['enddate'])) {
			$options['game_date'] = array(
			    date('Y-m-d 00:00:00', strtotime($params['startdate'])),
			    date('Y-m-d 23:59:59', strtotime($params['enddate']))
			);
		}
		if (!isset($options['game_date'])) {
			$options['close_date'] = $date;
		}
		if (strpos($text, ' v ') !== false) {
			$teams = explode('v', $text);
			$options['home'] = trim($teams[0]);
			$options['visitor'] = trim($teams[1]);
			$text = '';
		} else if (strpos($text, ' @ ') !== false) {
			$teams = explode('@', $text);
			$options['home'] = trim($teams[1]);
			$options['visitor'] = trim($teams[0]);
			$text = '';
		} else if (($lid = $this->LeagueType->contains($text)) !== false) {
			$options['league'] = $lid;
			$text = '';
		}
		$text = trim($text);
		if (!empty($text)) {
			if (($rawt = strtotime($text)) > strtotime('2010-01-01')) {
				$options['game_date'] = date('Y-m-d', $rawt);
			} else {
				$options['name'] = $text;
			}
		}		
			
		$scores = $this->Score->matchOption($options);
		$this->set('scores', $scores);
	}

	private function getbet($params) {
		//$this->Score->query("SET time_zone = 'US/Central';");
		if (!empty($params['scoreid'])) {
			$score = $this->Score->findById($params['scoreid']);
			$score = $score['Score'];
			$bet = array(
				'scoreid' => $score['id'],
				'home' => $score['home'],
				'visitor' => $score['visitor'],
				'league' => $score['league'],
				'game_date' => $score['game_date'],
				'type' => 'spread'
			);
			$odds = $this->Odd->latest($params['scoreid']);
			$bet['odds'] = array();
			foreach ($odds as $odd) {
				$odd = $odd['Odd'];
				$bet['odds'][] = $odd;
			}
		}
		$this->set('bet', $bet);
	}

	public function createbets() {
		$form = $this->params['form'];
		$form = $form + array('parlay' => array(), 'direction' => array(), 'spread' => array());
		$bets = array();
		foreach (array_keys($form['type']) as $iden) {
			list($dbkey, $num) = explode('_', $iden);
			if (!isset($form['direction'][$iden])) {
				$form['direction'][$iden] = null;
			}
			if (!isset($form['spread'][$iden])) {
				$form['spread'][$iden] = null;
			}
			if (!isset($form['parlay'][$iden])) {
				$form['parlay'][$iden] = null;
			}
			$bet = array(
				'type' => $form['type'][$iden],
				'direction' => $form['direction'][$iden],
				'spread' => $form['spread'][$iden],
				'risk' => $form['risk'][$iden],
				'odds' => $form['odds'][$iden],
				'key' => $dbkey,
				'scoreid' => str_replace('SS', '', $dbkey),
				'book' => $form['book'][$iden],
				'date_std' => isset($form['date_std'][$iden]) ? $form['date_std'][$iden] : null,
				'parlay' => $this->parseParlay($form['parlay'][$iden])
			);
			if ($bet['type'] == 'parlay' || $bet['type'] == 'teaser') {
				$date_std = 0;
				foreach ($bet['parlay'] as $p) {
					$date_std = max($date_std, strtotime($p['date_std']));
				}
				$bet['date_std'] = gmdate('Y-m-d H:i:s', $date_std);
				$bet['scoreid'] = null;
				$userid = $this->Auth->user('id');
				$this->UserBet->persist($userid, $bet);
				$saveParlays = array();
				foreach ($bet['parlay'] as $iden => $p) {
					list($dbkey, $num) = explode('_', $iden);
					$p['parlayid'] = $bet['id'];
					$p['scoreid'] = str_replace('SS', '', $dbkey);
					$p['pt'] = $bet['type'];
					$saveParlays[] = $p;
				}
				$this->saveBets($saveParlays);
			} else {
				$bets[] = $bet;
			}
		}
		list($saveBets, $unsavedBets) = $this->saveBets($bets);
		
		$this->set('savedBets', $saveBets);
		$this->set('unsavedBets', $unsavedBets);
		
		// Flash redirect
		App::import('Helper', 'Html');
		$html = new HtmlHelper();
		$link = $html->link('View your bets', '/bets/view');
		$this->Session->setFlash("Bet(s) entered successfully. $link");
		$this->redirect('/bets/');
	}

	private function saveBets($bets) {
		$saved = $notSaved = array();
		$userid = $this->Auth->user('id');
		
		foreach ($bets as $bet) {
			if ($this->UserBet->persist($userid, $bet)) {
				$saved[] = $bet;
			} else {
				$notSaved[] = $bet;
			}
		}
		return array($saved, $notSaved);
	}

	private function parseParlay($parlays) {
		if (empty($parlays)) {
			return false;
		}
		$out = array();
		foreach ($parlays as $key => $game) {
			$gameinfo = explode(';', $game);
			$i = array();
			foreach ($gameinfo as $row) {
				list($k, $v) = explode('=', $row);
				$i[$k] = $v;
			}
			$out[$key] = $i;
		}
		return $out;
	}

	private function getStartEnd($params) {
		$startdate = $params['startdate'];
		$enddate = $params['enddate'];
		$rawStart = strtotime($startdate);
		$rawEnd = strtotime($enddate);
		if ($rawStart === false || $rawStart < strtotime('2008-01-01')) {
			$startdate = date('Y-m-d');
			$rawStart = strtotime($startdate);
		}
		if ($rawEnd === false || $rawEnd < $rawStart) {
			$enddate = date('Y-m-d', $rawStart);
		}
		$enddate = date('Y-m-d 23:59:59', strtotime($enddate));
		return array($startdate, $enddate);
	}

	private function accorselect($params) {
		list($startdate, $enddate) = $this->getStartEnd($params);
		$leagues = $this->Score->findScoresBetweenDates($startdate, $enddate);
		$this->set('leagues', $leagues);
		$this->set('startdate', date('n/j/Y', strtotime($startdate)));
		$this->set('enddate', date('n/j/Y', strtotime($enddate)));
	}

	public function ajax($action = '') {
		$params = $this->params['url'];
		$date = date('Y-m-d H:i:s'); //today for right now

		switch ($action) {
		case 'superbar':
			$this->superbar($params, $date);
			break;
		case 'getbet':
			$this->getbet($params);
			break;
		case 'accorselect':
			$this->accorselect($params);
			break;
		}
		$this->render("ajax_$action");
	}

	private function winLossTie(&$bets) {
		$record = array(
		    'win' => 0,
		    'loss' => 0,
		    'tie' => 0,
		    'dollarsWon' => 0
		);
		foreach ($bets as $bet) {
			$winning = $bet['winning'];
			if (!is_null($winning)) {
				if ($winning == 0) {
					$record['tie']++;
				} else if ($winning > 0) {
					$record['win']++;
				} else {
					$record['loss']++;
				}
				$record['dollarsWon'] += $winning;
			}
		}
		$record['winningPercentage'] = safe_div($record['win'], ($record['win']+$record['loss']+$record['tie']));
		return $record;
	}

	private function allStats(&$bets) {
		$allStats = array(
		    'earned' => 0,
		    'num' => 0,
		    'bet' => 0,
		    'odds' => 0,
		    'breakEven' => 0
		);
		foreach ($bets as $bet) {
			$winning = $bet['winning'];
			if (!is_null($winning)) {
				$allStats['num']++;
				$allStats['earned'] += $winning;
				$allStats['bet'] += $bet['risk'];

				$odds = $bet['odds'];
				if ($odds > 0) {
					$allStats['breakEven'] += 1/(($odds/100)+1);
					$allStats['odds'] += ($odds-100);
				} else {
					$allStats['breakEven'] += 1/($odds/(-100)+1);
					$allStats['odds'] += ($odds+100);
				}
			}
		}
		$odds = safe_div($allStats['odds'], $allStats['num']);
		$allStats['avgOdds'] = ($odds > 0) ? $odds + 100 : $odds - 100;
		$allStats['breakEven'] = safe_div($allStats['breakEven'], $allStats['num']);
		$allStats['avgEarned'] = safe_div($allStats['earned'], $allStats['num']);
		$allStats['avgBet'] = safe_div($allStats['bet'], $allStats['num']);
		$allStats['roi'] = safe_div($allStats['avgEarned'], $allStats['avgBet']);

		return $allStats;
	}

	private function graphData(&$bets) {
		$earnedData = array();
		$earned = 0;
		$i = 0;
		foreach ($bets as $bet) {
			$winning = $bet['winning'];
			if (!is_null($winning)) {
				$earned += $winning;
			}
			$earnedData[] = array($i, $earned);
			$i++;
		}
		return array($earnedData);
	}

	private function fixCond($cond) {
		$ret = array();
		$fixedCond = array();
		$possibleTypes = $this->getBetTypes();
		foreach ($cond as $key => $val) {
			if ($val !== false) {
				switch ($key) {
				case 'league':
					$vals = explode(',', $val);
					$set = array();
					$fixedCond[$key] = array();
					foreach ($vals as &$val) {
						$sqlval = $this->LeagueType->contains($val);
						if ($sqlval !== false) {
							$fixedCond[$key][] = $val;
							$set[] = $sqlval;
						}
					}
					if (!empty($set)) {
						$ret[$key] = $set;
					}
					break;
				case 'book':
					$vals = explode(',', $val);
					$set = array();
					$fixedCond[$key] = array();
					foreach ($vals as &$val) {
						$sqlval = $this->SourceType->contains($val);
						if ($sqlval !== false) {
							$fixedCond[$key][] = $val;
							$set[] = $sqlval;
						}
					}
					if (!empty($set)) {
						$ret['UserBet.sourceid'] = $set;
					}
					break;
				case 'type':
					$vals = explode(',', $val);
					$set = array();
					$fixedCond[$key] = array();
					foreach ($vals as &$val) {
						if (isset($possibleTypes[$val])) {
							$fixedCond[$key][] = $val;
							$set[] = $val;
						}
					}
					if (!empty($set)) {
						$ret[$key] = $set;
					}
					break;
				case 'beton':
					$vals = explode(',', $val);
					if (!empty($vals)) {
						$fixedCond[$key] = $vals;
					}
					break;
				default:
					$vals = explode(',', $val);					
					if (!empty($vals)) {
						$fixedCond[$key] = $vals;
						$ret[$key] = $vals;
					}
				}
			}
		}
		return array($ret, $fixedCond);
	}

	private function getBetTypes() {
		return array_combine(
			$this->UserBet->possibleTypes(),
			array_map(array('Inflector', 'humanize'), $this->UserBet->possibleTypes())
		);
	}

	private function setFilters(&$bets, $distinct) {
		$ret = array();
		$distincts = array();
		foreach ($bets as $bet) {
			foreach ($distinct as $key) {
				if (!isset($distincts[$key])) {
					$distincts[$key] = array();
				}
				if (!empty($bet[$key])) {
					$distincts[$key][$bet[$key]] = true;
				}
			}
		}
		foreach ($distinct as $key) {
			if (isset($distincts[$key])) {
				$ret[$key] = array_keys($distincts[$key]);
			}
		}
		return $ret;
	}

	private function getCondAsMap($cond) {
		$ret = array();
		foreach ($cond as $key => $rows) {
			$ret[$key] = array_combine(array_values($rows), array_fill(0, count($rows), true));
		}
		return $ret;
	}

	private function reformatBets(&$bets) {
		$ret = array();
		foreach ($bets as $bet) {
			$ret[] = $this->reformatBet($bet);
		}
		return $ret;
	}

	private function getBetOn($userBet, $score) {
		switch ($userBet['type']) {
		case 'moneyline':
		case 'half_moneyline':
		case 'second_moneyline':
		case 'spread':
		case 'half_spread':
		case 'second_spread':
			return ($userBet['direction'] == 'home') ? $score['home'] : $score['visitor'];
		case 'total':
		case 'half_total':
		case 'second_total':
			return $userBet['direction'];
		case 'parlay':		
			return count($userBet['Parlay']).' team parlay';
		case 'teaser':
			return 'Teaser';
		}
	}

	private function reformatBet($bet) {
		$userBet = $bet['UserBet'];
		$score = $bet['Score'];

		$userBetGameDate = strtotime($userBet['game_date']);
		$scoreGameDate = strtotime($score['game_date']);

		$parlayBetGameDate = 0;
		$parlays = false;
		if (!empty($userBet['Parlay'])) {
			$parlays = $this->reformatBets($userBet['Parlay']);
			foreach ($parlays as $row) {
				$parlayBetGameDate = max($parlayBetGameDate, strtotime($row['date']));
			}
		}

		$fields = array(
		    'betid' => $userBet['id'],
		    'scoreid' => $score['id'],
		    'date' => date('Y-m-d', max($userBetGameDate, $scoreGameDate, $parlayBetGameDate)),
		    'league' => $score['league'],
		    'beton' => $this->getBetOn($userBet, $score),
		    'type' => $userBet['type'],
		    'line' => (float)$userBet['spread'],
		    'home' => $score['home'],
		    'visitor' => $score['visitor'],
		    'risk' => $userBet['risk'],
		    'odds' => $userBet['odds'],
		    'winning' => $userBet['winning'],
		    'book' => $userBet['source'],
		    'parlays' => $parlays
		);
		return $fields;
	}

	private function isMatchingBet($bet, $cond, $keys) {
		foreach ($keys as $key) {
			if (isset($cond[$key])) {
				foreach ($cond[$key] as $match) {
					if ($match == $bet[$key]) {
						return true;
					}
				}
			}
		}
		return false;
	}

	private function filterNonSql(&$bets, $cond, $keys) {
		$ret = array();
		$i = 0;
		foreach ($keys as $key) {
			if (isset($cond[$key])) {
				$i++;
			}
		}
		// No non sql filters
		if ($i <= 0) {
			return $bets;
		}
		foreach ($bets as $bet) {
			if ($this->isMatchingBet($bet, $cond, $keys)) {
				$ret[] = $bet;
			}
		}
		return $ret;
	}

	public function view() {
		
		$cond = array(
		    'home' => $this->urlGetVar('home'),
		    'visitor' => $this->urlGetVar('visitor'),
		    'type' => $this->urlGetVar('type'),
		    'league' => $this->urlGetVar('league'),
		    'beton' => $this->urlGetVar('beton'),
		    'book' => $this->urlGetVar('book')
		);		
		list($sqlcond, $cond) = $this->fixCond($cond);
		$this->set('cond', $cond);
		$this->set('condAsMap', $this->getCondAsMap($cond));

		$sort = $this->urlGetVar('sort', 'date,desc');
		if (strpos($sort, ',') !== false) {
			list($this->sortKey, $this->sortDir) = explode(',', $sort);
		} else {
			$this->sortKey = $sort;
			$this->sortDir = 'desc';
		}
		$this->set('sortKey', $this->sortKey);
		$this->set('sortDir', $this->sortDir);

		$bets = $this->UserBet->getAll($this->Auth->user('id'), null, $sqlcond);
		$bets = $this->reformatBets($bets);
		$bets = $this->filterNonSql($bets, $cond, array('beton'));
		usort($bets, array($this, '_sort_bets'));
		
		$filters = $this->setFilters($bets, array('home', 'visitor', 'type', 'league', 'beton', 'book'));
		$this->set('filters', $filters);

		$record = $this->winLossTie($bets);
		$this->set('record', $record);
		$allStats = $this->allStats($bets);
		$this->set('allStats', $allStats);
		$this->set('graphData', $this->graphData($bets));

		$this->set('bets', $bets);
	}

	private function _sort_bets($left, $right) {
		$left = $left[$this->sortKey];
		$right = $right[$this->sortKey];
		$asc = $this->sortDir == 'asc';
		if ($left == $right) {
			return 0;
		}
		if ($left > $right) {
			return $asc ? 1 : -1;
		} else {
			return $asc ? -1 : 1;
		}
	}
}
