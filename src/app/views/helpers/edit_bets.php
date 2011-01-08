<?php

class EditBetsHelper extends AppHelper {
	
	var $helpers = array('EditBets');
	
	public function renderBet($bet) {
		$view = &ClassRegistry::getObject('view');
		$types = $view->getVar('types');
		$betType = $bet['UserBet']['type'];
		$betTypeParent = $this->parentType($betType);
		
		if ($betTypeParent == 'parlay') {
			return $view->element('parlay_bet', array(
			    'bet' => $bet,
			    'types' => $types,
			    'betType' => $betType
			));
		} else {
			return $view->element('reg_bet', array(
			    'bet' => $bet, 
			    'types' => $types, 
			    'betType' => $betType,
			    'betTypeParent' => $betTypeParent));
		}
	}
	
	private static $PTYPES = array(
	    'moneyline' => 'moneyline',
	    'half_moneyline' => 'moneyline',
	    'second_moneyline' => 'moneyline',
	    'spread' => 'spread',
	    'half_spread' => 'spread',
	    'second_spread' => 'spread',
	    'total' => 'total',
	    'half_total' => 'total',
	    'second_total' => 'total',
	    'other' => 'other',
	    'parlay' => 'parlay',
	    'teaser' => 'parlay'
	);
	
	private function parentType($bettype) {
		return isset(self::$PTYPES[$bettype]) ? self::$PTYPES[$bettype] : 'other';
	}
}