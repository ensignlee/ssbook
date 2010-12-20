<?php

class GivenName extends AppModel {
	var $name = 'GivenName';

	public function getOrSet($name, $league_id=null) {
		$res = $this->find('first', array('conditions' => array('given_name' => $name, 'league_id' => $league_id)));
		if (empty($res)) {
			$this->create();
			$this->save(array('given_name' => $name, 'league_id' => $league_id));
			return $this->getLastInsertId();
		} else {
			return $res['GivenName']['id'];
		}
	}
}