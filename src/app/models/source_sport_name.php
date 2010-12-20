<?php

App::import('Model', 'GivenName');
class SourceSportName extends AppModel {
	var $name = 'SourceSportName';
	var $useTable = 'source_sport_name';
	var $primaryKey = 'id';

	private $GivenName;

	function __construct($id = null, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->GivenName = new GivenName();
	}

	public function setGivenName($source_id, $source_name, $league_id, $name) {
		
		$res = $this->find('first', array('conditions' => array(
		    'source_id' => $source_id,
		    'source_name' => $source_name,
		    'league_id' => $league_id
		)));
		if (empty($res)) {
			return false;
		}
		$id = $this->GivenName->getOrSet($name, $league_id);

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
		
		$this->GivenName = new GivenName();
		$res = $this->GivenName->findById($res['SourceSportName']['given_name']);
		if (empty($res)) {
			return false;
		}
		return $res['GivenName']['given_name'];

	}

	/**
	 * Who knows what garbage text is going to be sent. Potentially send
	 * back a list of better candidates
	 * @param <type> $text
	 * @return trim($text) if nothing is found to match
	 */
	public function lookup($text) {
		if (empty($text)) {
			return $text;
		}
		$text = trim("$text");
		$res = $this->GivenName->find('list', array('fields' => array('id'), 'conditions' => array(
		    'given_name LIKE' => "%$text%"
		)));
		if (!empty($res)) {
			$given_name_ids = array_values($res);
			$res = $this->find('list', array('fields' => array('source_name'), 'conditions' => array(
			    'given_name' => $given_name_ids
			)));
			if (!empty($res)) {
				return array_unique(array_values($res));
			}
		}
		return $text;
	}
}
