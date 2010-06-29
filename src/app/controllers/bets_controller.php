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
			$scores = $this->Score->matchName($text, $date);
		}
		$this->set('scores', $scores);
	}
}
