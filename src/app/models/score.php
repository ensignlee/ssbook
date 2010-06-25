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
		$out = $this->find('first', array('conditions' => array('sourceid' => $sourceid)));
		if (empty($out)) {
			return false;
		} else {
			return $out[$this->name]['game_date'];
		}
	}
}
