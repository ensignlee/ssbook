<?php

class Feature extends AppModel {
	var $name = 'Feature';
	
	public function getActive() {
		return $this->find('all', array('condition' => array('active' => 1)));
	}
	
	public function existsAndActive($fid) {
		$res = $this->find('all', array('condition' => array('id' => $fid, 'active' => 1)));
		return !empty($res);
	}
}
