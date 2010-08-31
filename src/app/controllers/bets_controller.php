<?php

class BetsController extends AppController {
	var $name = 'Bets';
	var $uses = array('LeagueType', 'Odd', 'Score', 'SourceType', 'UserBet');
	var $helpers = array('Html','Ajax','Javascript');
	var $components = array('RequestHandler');

	public function index() {
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
			if ($bet['type'] == 'parlay') {
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
					$p['pt'] = 'parlay';
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
		$this->set('startdate', date('Y-m-d', strtotime($startdate)));
		$this->set('enddate', date('Y-m-d', strtotime($enddate)));
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

	public function view() {
		$bets = $this->UserBet->getAll($this->Auth->user('id'));		
		$this->set('bets', $bets);
	}
}
