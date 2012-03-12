<?php

App::import('Core', 'Controller');
App::import('Component', 'Email');
class EmailEveryoneShell extends Shell {
	var $uses = array(
		'User'
	);

	private $go = false;
	private $official = false;
	private $uid = 0;

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

		echo "Emailing $uid {$user['username']}\n";

		if ($this->official) {
			$this->Email->from = 'Edmund Lee<edmund@sharpbettracker.com>';
			$this->Email->to = $user['email'];
			$this->Email->subject = 'Grading Problems Fixed';
			$this->Email->template = 'fixed_grading';
			$this->Email->sendAs = 'text';
			$this->Email->send();
		} else {
			echo "Not sending email.\n";
		}
	}

	public function startup() {
		$this->go = isset($this->params['go']);
		$this->official = isset($this->params['official']);
		$this->uid = isset($this->params['uid']);

		$this->Email = new EmailComponent(null);
	}
}