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

	public function matchOption($options, $limit=20) {

		$conds = array();
		foreach ($options as $key => $option) {
			switch($key) {
			case 'close_date': 
				$days = 14;
				$closeafter = date('Y-m-d 23:59:59', strtotime("$option + $days days"));
				$conds['game_date BETWEEN ? AND ?'] = array($option, $closeafter);
				break;
			case 'game_date':
				$enddate = date('Y-m-d 23:59:59', strtotime($option));
				$conds['game_date BETWEEN ? AND ?'] = array($option, $enddate);
				break;
			case 'name':
				$conds['or'] = array('home LIKE' => "%$option%", 'visitor LIKE' => "%$option%");
				break;
			case 'home':
				$conds['home LIKE'] = "%$option%";
				break;
			case 'visitor':
				$conds['visitor LIKE'] = "%$option%";
				break;
			case 'league':
				$conds['league'] = $option;
				break;
			default:
				throw new Exception("$option is not supported");
			}
		}

		$resAfter = $this->find('all', array('conditions' => $conds, 'order' => 'game_date ASC'));
		$out = array();
		foreach (array($resAfter) as $res) {
			foreach ($res as $row) {	
				if (count($out) >= $limit) {
					break;
				}
				$row = $row[$this->name];
				$out[$row['id']] = $row;
			}
		}
		return $out;
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
