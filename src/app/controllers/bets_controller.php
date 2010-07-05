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
				'game_date' => $score['game_date']
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
		$bets = array();
		foreach (array_keys($form['type']) as $iden) {
			list($dbkey, $num) = explode('_', $iden);
			$bets[] = array(
				'type' => $form['type'][$iden],
				'direction' => $form['direction'][$iden],
				'spread' => $form['spread'][$iden],
				'risk' => $form['risk'][$iden],
				'odds' => $form['odds'][$iden],
				'key' => $dbkey
			);
		}
		$this->set('bets', $bets);
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
		}
		$this->render("ajax_$action");
	}
}
