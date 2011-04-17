<?php

class Score extends AppModel {
	var $name = 'Score';
	var $validate = array(
		'home' => 'notEmpty',
		'visitor' => 'notEmpty',
	);

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
				if (count($option) == 2) {					
					$conds['game_date BETWEEN ? AND ?'] = $option;
				} else {					
					$enddate = date('Y-m-d 23:59:59', strtotime($option));
					$conds['game_date BETWEEN ? AND ?'] = array($option, $enddate);
				}
				break;
			case 'name':
				if (!is_array($option)) {
					$conds['or'] = array('home LIKE' => "%$option%", 'visitor LIKE' => "%$option%");
				} else {
					$conds['or'] = array('home' => $option, 'visitor' => $option);
				}
				break;
			case 'home':
				if (!is_array($option)) {
					$conds['home LIKE'] = "%$option%";
				} else {
					$conds['home'] = $option;
				}
				break;
			case 'visitor':
				if (!is_array($option)) {
					$conds['visitor LIKE'] = "%$option%";
				} else {
					$conds['visitor'] = $option;
				}
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

	public function findMatching($game, $timeframe = 4400) {
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
	
	public function findScoresBetweenDates($startdate, $enddate) {
		App::import('model', 'LeagueType');
		$LeagueType = new LeagueType();
		App::import('model', 'Odd');
		$Odd = new Odd();

		$cond = array(
			'conditions' => array('game_date BETWEEN ? AND ?' => array($startdate, $enddate)),
			'order' => array('game_date ASC')
		);
		$games = $this->find('all', $cond);
		$out = array();
		$scoreids = array();
		foreach ($games as $game) {
			$scoreids[] = $game['Score']['id'];
		}
		$odds = $Odd->find('list', array('conditions' => array('scoreid' => $scoreids), 'group' => 'scoreid', 'fields' => array('scoreid')));
		$odds = array_flip($odds);

		foreach ($games as $game) {
			$game = $game['Score'];
			$league = $LeagueType->getName($game['league']);
			if (!isset($out[$league])) {
				$out[$league] = array();
			}
			$date = date('n/j/y g:i A', strtotime($game['game_date']));
			if ($league == 'MLB') {
				$vpitch = $hpitch = '';
				if (!empty($game['visitExtra']) && !empty($game['homeExtra'])) {
					$vpitch = "(".self::abrev($game['visitExtra']).")";
					$hpitch = "(".self::abrev($game['homeExtra']).")";
				}
				$desc = "{$game['visitor']}<span class='smalltext'>$vpitch</span> @ {$game['home']}<span class='smalltext'>$hpitch</span> $date";
			} else {
				$desc = "{$game['visitor']} @ {$game['home']} $date";
			}
			$out[$league][] = array('desc' => $desc, 'scoreid' => $game['id'], 'odds' => isset($odds[$game['id']]));
		}
		return $out;
	}
	
	public static function abrev($str) {
		if (empty($str) || strlen($str) <= 3) {
			return $str;
		}
		return ucfirst($str[0].$str[1].$str[2]);
	}
}
