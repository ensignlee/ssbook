<?php

class UserBet extends AppModel {
	var $name = 'UserBet';
	var $belongsTo = array(
		'Score' => array(
			'className' => 'Score',
			'foreignKey' => 'scoreid',
			'type' => 'LEFT OUTER'
		)
	);
	var $hasAndBelongsToMany = array(
		'Tag' =>
		    array(
			'className'              => 'Tag',
			'joinTable'              => 'user_bets_tags',
			'foreignKey'             => 'user_bets_id',
			'associationForeignKey'  => 'tag_id',
			'unique'                 => false));

	public function possibleTypes() {
		return array(
		    'moneyline' => 'M/L',
		    'half_moneyline' => '1st Half M/L',
		    'second_moneyline' => '2nd Half M/L',
		    'spread' => 'Spread',
		    'half_spread' => '1st Half Spread',
		    'second_spread' => '2nd Half Spread',
		    'total' => 'Total',
		    'half_total' => '1st Half Total',
		    'second_total' => '2nd Half Total',
		    'team_total' => 'Team Total',
		    'half_team_total' => '1st Half Team Total',
		    'second_team_total' => '2nd Half Team Total',
		    'parlay' => 'Parlay',
		    'teaser' => 'Teaser'
		);
	}

	public function possibleDirections() {
		return array(
		    'home',
		    'visitor',
		    'over',
		    'under',
		    'home_over',
		    'home_under',
		    'visitor_over',
		    'visitor_under'
		);
	}	

	/**
	 * expects (type, direction, spread, risk, odds, scoreid, book, parlay, game_date)
	 */
	public function persist($userid, &$bet, $trans=true) {
		// Begin a transaction
		if ($trans) {
			$this->begin();
		}
		$errors = array();
		
		$this->create();
		$save = array(
			'userid' => $userid,
			'scoreid' => array_lookup('scoreid', $bet, null),
			'game_date' => array_lookup('date_std', $bet, null),
			'type' => $bet['type'],
			'direction' => array_lookup('direction', $bet, null),
			'spread' => array_lookup('spread', $bet, 0),
			'odds' => array_lookup('odds', $bet, -110),
			'risk' => array_lookup('risk', $bet, 1),
			'parlayid' => array_lookup('parlayid', $bet, null),
			'pt' => array_lookup('pt', $bet, null),
		);
		$tagname = empty($bet['tag']) ? '' : $bet['tag'];
		
		// If we have parlays, then we want to get the max gamedate of all of them		
		if (!empty($bet['parlays'])) {
			$gamedate = 0;
			foreach ($bet['parlays'] as $parlay) {
				$gamedate = max($gamedate, strtotime($parlay['date_std']));
			}
			$save['game_date'] = date('Y-m-d H:i:s', $gamedate);
		}

		if (!empty($bet['book'])) {
			$save['sourceid'] = $this->getSaveSource($bet['book']);
			$bet['sourceid'] = $save['sourceid'];
		}
		
		$success = $this->save($save);
		if ($success) {
			$bet['id'] = $this->id;
			if (!empty($tagname)) {
				$this->Tag->saveBetsWithTag($tagname, array($bet['id']));
			}
		
			$betid = $this->id;
		
			if (!empty($bet['parlays'])) {
				foreach ($bet['parlays'] as $parlay) {
					$parlay['parlayid'] = $betid;
					$parlay['pt'] = $bet['type'];
					$psuccess = $this->persist($userid, $parlay, false);
					if (empty($psuccess)) {
						$errors[] = "Unable to persist game";
						$success = false;
					}
				}
			}
		
			// Need to set the id back to the original
			$this->id = $betid;
		} else {
			$errors[] = "error in saving original";
		}
		
		// Finish transaction
		if ($trans) {
			if ($success) {
				$this->commit();
			} else {
				$this->rollback();
			}
		}
		
		return $success;
	}	

	private function getSaveSource($name) {
		if (empty($this->SourceType)) {
			App::import('Model', 'SourceType');
			$this->SourceType = new SourceType();
		}
		return $this->SourceType->getOrSet($name);
	}

	public function lastBet($userid) {
		$this->unbindModel(array(
			'belongsTo' => array('Score'),
		    'hasAndBelongsToMany' => array('Tag')
	    ));
		$bets = $this->find('first', array(
		    'conditions' => array('userid' => $userid),
			'order' => array('userid,UserBet.modified Desc')
		));
		return empty($bets) ? 0 : strtotime($bets['UserBet']['modified']);
	}

	public function getAll($userid, $cond = array()) {
		$cond = safe_array_merge($cond, array('userid' => $userid, 'parlayid' => null));
		return $this->getAllCond($cond);
	}
	
	public function getAllIds($ids, $cond = array()) {
		$cond = safe_array_merge($cond, array('UserBet.id' => $ids));
		return $this->getAllCond($cond);
	}
		
	private function getAllCond($cond) {		
		
		$bets = $this->find('all', array(
			'conditions' => $cond
		));

		App::import('Model', 'LeagueType');
		$this->LeagueType = new LeagueType();
		App::import('Model', 'SourceType');
		$this->SourceType = new SourceType();

		foreach ($bets as &$bet) {
			$bet['Score']['league'] = $this->LeagueType->getName($bet['Score']['league']);
			$bet['UserBet']['source'] = $this->SourceType->getName($bet['UserBet']['sourceid']);
			$bet['UserBet']['bet'] = self::buildBet($bet['UserBet']);
			if ($bet['UserBet']['type'] == 'parlay' || $bet['UserBet']['type'] == 'teaser') {
				$bet['UserBet']['Parlay'] = $this->getParlays($bet['UserBet']['id']);
			}
			$nullRisk = is_null($bet['UserBet']['risk']);
			if ($nullRisk) {
				$bet['UserBet']['risk'] = 1;
			}
			$winning = self::calcWinning($bet['Score'], $bet['UserBet']);
			$bet['UserBet']['winning'] = $winning;
		}
		return $bets;
	}
	
	public function getParlays($id, $cond = array()) {
		$cond = safe_array_merge($cond, array('parlayid' => $id));
		return $this->getAllCond($cond);
	}

	public static function calcWinning($score, $bet) {
		App::import('Vendor', 'calculator/winning');
		$w = new Winning($score, $bet);
		return $w->process();
	}

	public static function buildBet($bet) {
		$ret = "";
		switch ($bet['type']) {
		case 'spread':
		case 'half_spread':
		case 'total':
		case 'half_total':
			$ret .= $bet['spread'];
		case 'half_moneyline':
		case 'moneyline':
			$ret .= "({$bet['odds']})";
		}
		return $ret;
	}
}
