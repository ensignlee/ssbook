<?php

class BetsController extends AppController {
	var $name = 'Bets';
	var $uses = array('LeagueType', 'Odd', 'Score', 'SourceType', 'UserBet');
	var $helpers = array('Html','Ajax','Javascript');
	var $components = array('RequestHandler');

	public function index() {
	}

	public function ajax($action = '') {
		$params = $this->params['url'];
		$scores = array();
		$date = date('Y-m-d'); //today for right now

		switch ($action) {
		case 'superbar':
			$text = $params['text'];
			$text = strtolower(" $text "); //give us some working room
			$options = array();
			if (($pos = strpos($text, ' - ')) !== false) {
				$strdate = strtotime(substr($text, $pos+2));
				if ($strdate > strtotime('2010-01-01')) {
					$text = substr($text, 0, $pos);
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
		}
		$this->set('scores', $scores);
	}
}
