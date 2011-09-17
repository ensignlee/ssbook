<?php

class User extends AppModel {
	var $name = 'User';

	function getActivationCode() {
		if(!isset($this->id)) {
			return false;
		}

		$code = Security::hash(Configure::read('Security.salt').$this->field('created').$this->field('email'));
		// The code will be sent out in an email, so chop it down to something shorter
		$code = substr($code, 0, 10);

		return $code;
	}
}

?>
