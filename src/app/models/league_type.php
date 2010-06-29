<?php

class LeagueType extends AppModel {
	var $name = 'LeagueType';

	public function contains($name) {
		$out = $this->find('first', array('conditions' => array('name' => trim($name))));
		if (empty($out)) {
			return false;
		} else {
			return $out[$this->name]['id'];
		}
	}	

	public function getOrSet($name) {
		if (empty($name)) {
			return null;
		}

		$out = $this->find('first', array('conditions' => array('name' => $name)));
		if (empty($out)) {
			$this->create();
			$this->save(array('name' => $name));
			return $this->id;
		} else {
			return $out[$this->name]['id'];
		}

		return false;
	}
}
