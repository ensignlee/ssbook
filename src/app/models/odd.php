<?php

class Odd extends AppModel {
	var $name = 'Odd';

	public function latest($scoreid) {

		$cond = array(
			'conditions' => array('scoreid' => $scoreid),
			'order' => array('created DESC'),
			'group' => array('type'),
			'fields' => array('MAX(id) max_id')
		);
		$res = $this->find('all', $cond);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row[0]['max_id'];
		}
	
		$cond = array(
			'conditions' => array('id' => $ids)
		);
		return $this->find('all', $cond);
	}
}
