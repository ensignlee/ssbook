<?php

class Score extends AppModel {
	var $name = 'Score';

	public function setToRecord($field, $value) {
		$out = $this->find('first', array('conditions' => array($field => $value)));
		if (empty($out)) {
			$this->id = null;
		} else {
			$this->id = $out[$this->name]['id'];
		}
	}

	public function getLastGameDate($sourceid) {
		$this->order = 'game_date DESC';
		$out = $this->find('first', array('conditions' => 
			array(
				'sourceid' => $sourceid, 
				'not' => array('home_score_total' => null)
			), 'order' => array('game_date DESC')));
		if (empty($out)) {
			return false;
		} else {
			return $out[$this->name]['game_date'];
		}
	}

	public function findMatching($game, $timeframe = 1800) {
		$start = date('Y-m-d H:i:s', strtotime($game['game_date']) - $timeframe);
		$end = date('Y-m-d H:i:s', strtotime($game['game_date']) + $timeframe);
		$existing_games = $this->find('all', array('conditions' => array(
			'game_date BETWEEN ? AND ?' => array($start,  $end),
			'league' => $game['league_id']
		)));

		$r = explode(' ', $game['home']);
		$homes = array();
		foreach ($r as $s) {
			$homes[strtolower($s)] = true;
		}
		$r = explode(' ', $game['visitor']);
		$visitors = array();
		foreach ($r as $s) {
			$visitors[strtolower($s)] = true;
		}

		// Decided that if its withen an hour like this, then do not need both. :/
		foreach ($existing_games as $e) {
			foreach (explode(' ', $e[$this->name]['home']) as $home) {
				if (isset($homes[strtolower($home)])) {
					return $e;
				}
			}
			foreach (explode(' ', $e[$this->name]['visitor']) as $visitor) {
				if (isset($visitors[strtolower($visitor)])) {
					return $e;
				}
			}
		}
		return false;
	}
}
