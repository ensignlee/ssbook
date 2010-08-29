<?php

class UserBet extends AppModel {
	var $name = 'UserBet';
	var $belongsTo = array(
		'Score' => array(
			'className' => 'Score',
			'foreignKey' => 'scoreid'
		)
	);

	/**
	 * expects (type, direction, spread, risk, odds, scoreid, book, parlay, game_date)
	 */
	public function persist($userid, &$bet) {
		$this->create();
		$save = array(
			'userid' => $userid,
			'scoreid' => $bet['scoreid'],
			'game_date' => $bet['date_std'],
			'type' => $bet['type'],
			'direction' => $bet['direction'],
			'spread' => $bet['spread'],
			'odds' => $bet['odds'],
			'risk' => $bet['risk']
		);
		if (!empty($bet['book'])) {
			$save['sourceid'] = $this->getSaveSource($bet['book']);
			$bet['sourceid'] = $save['sourceid'];
		}
		$success = $this->save($save);
		if ($success) {
			$bet['id'] = $this->id;
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

	public function getAll($userid) {
		$bets = $this->find('all', array(
			'conditions' => array('userid' => $userid)
		));

		App::import('Model', 'LeagueType');
		$this->LeagueType = new LeagueType();
		App::import('Model', 'SourceType');
		$this->SourceType = new SourceType();

		foreach ($bets as &$bet) {
			$bet['Score']['league'] = $this->LeagueType->getName($bet['Score']['league']);
			$bet['UserBet']['source'] = $this->SourceType->getName($bet['UserBet']['sourceid']);
			$bet['UserBet']['bet'] = self::buildBet($bet['UserBet']);
			$bet['UserBet']['winning'] = self::calcWinning($bet['Score'], $bet['UserBet']);
		}
		return $bets;
	}

	public static function calcWinning($score, $bet) {
		return "na";
	}

	public static function buildBet($bet) {
		$ret = "";
		switch ($bet['type']) {
		case 'spread':
		case 'halfspread':
		case 'total':
		case 'halftotal':
			$ret .= $bet['spread'];
		case 'moneyline':
			$ret .= "({$bet['odds']})";
		}
		return $ret;
	}
}
