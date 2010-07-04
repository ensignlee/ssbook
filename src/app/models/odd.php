<?php

class Odd extends AppModel {
	var $name = 'Odd';

	public function latest($scoreid) {

		$cond = array(
			'conditions' => array('scoreid' => $scoreid),
			'order' => array('created DESC'),
			'group' => array('type')
		);

		return $this->find('all', $cond);
	}
}
