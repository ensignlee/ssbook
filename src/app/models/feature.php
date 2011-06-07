<?php

class Feature extends AppModel {
	var $name = 'Feature';
	
	public function getActive() {
		return $this->find('all', array('conditions' => array('active' => 1)));
	}
	
	public function existsAndActive($fid) {
		$res = $this->find('all', array('conditions' => array('id' => $fid, 'active' => 1)));
		return !empty($res);
	}
}
