<?php

class EmailEveryoneShell {
	var $uses = array(
		'User'
	);

	var $helpers = array(
		'Email'
	);

	private $go = false;

	public function main() {
		if ($this->go) {
			$users = $this->User->find(array('conditions' => array('active' => 1)));
			var_dump($users);exit;
		} else if (!empty($this->uid)) {
			$this->email($this->uid);
		}
	}

	private function email($uid) {
		$this->User->id = $uid;
		$user = $this->User->read();
		$user = $user['User'];

		echo "Emailing $uid {$user['username']}";

		if ($this->offical) {
			$this->Email->from = 'Edmund Lee<edmund@sharpbettracker.com>';
			$this->Email->to = $user['email'];
			$this->Email->subject = 'Grading Problems Fixed';
			$this->Email->template = 'fixed_grading';
			$this->Email->sendAs = 'text';
			$this->Email->send();
		} else {
			echo "Not sending email.";
		}
	}

	public function startup() {
		$this->go = isset($this->params['go']);
		$this->official = isset($this->params['official']);
		$this->uid = isset($this->params['uid']);
	}
}