<?php

class UserBet extends AppModel {
	var $name = 'UserBet';

	/**
	 * expects (type, direction, spread, risk, odds, scoreid, book, parlay, game_date)
	 */
	public function create($userid, &$bet) {
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
		return $this->save($save);
	}	
}
