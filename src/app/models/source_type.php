<?php

App::import('Model', 'LeagueType');
class SourceType extends LeagueType {
	var $name = 'SourceType';

	public function getName($id) {
		if (empty($this->_cacheMap)) {
			$this->_cacheMap = $this->find('list');
		}
		return isset($this->_cacheMap[$id]) ? $this->_cacheMap[$id] : '';
	}

	public function getOrSet($name) {
		if (empty($name) || strtolower(trim($name)) == 'none') {
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
