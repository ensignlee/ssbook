<?php

class LeagueType extends AppModel {
	var $name = 'LeagueType';
	protected $_cacheMap = array();

	public function contains($name) {
		$out = $this->find('first', array('conditions' => array('name' => trim($name))));
		if (empty($out)) {
			return false;
		} else {
			return $out[$this->name]['id'];
		}
	}	

	public function getName($id) {
		if (empty($this->_cacheMap)) {
			$this->_cacheMap = $this->find('list');
		}
		return isset($this->_cacheMap[$id]) ? $this->_cacheMap[$id] : '';
	}

	public function getList() {
		if (empty($this->_cacheMap)) {
			$this->_cacheMap = $this->find('list');
		}
		return $this->_cacheMap;
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
