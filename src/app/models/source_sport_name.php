<?php

class SourceSportName extends AppModel {
	var $name = 'SourceSportName';
	var $useTable = 'source_sport_name';
	var $primaryKey = 'id';

	public function setGivenName($source_id, $source_name, $league_id, $name) {
		
		$res = $this->find('first', array('conditions' => array(
		    'source_id' => $source_id,
		    'source_name' => $source_name,
		    'league_id' => $league_id
		)));
		if (empty($res)) {
			return false;
		}

		App::import('Model', 'GivenName');
		$GivenName = new GivenName();
		$id = $GivenName->getOrSet($name, $league_id);

		$res['SourceSportName']['given_name'] = $id;
		return $this->save($res);
	}

	public function getFullname($source_name) {
		$res = $this->find('first', array('conditions' => array(
		    'source_name' => $source_name,
		    array('not' => array('given_name' => null))
		)));
		if (empty($res)) {
			return false;
		}

		App::import('Model', 'GivenName');
		$GivenName = new GivenName();
		$res = $GivenName->findById($res['SourceSportName']['given_name']);
		if (empty($res)) {
			return false;
		}
		return $res['GivenName']['given_name'];

	}
}
