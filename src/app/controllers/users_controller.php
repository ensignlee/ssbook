<?php

class UsersController extends AppController {

	var $name = 'Users';

	public function beforeFilter() {
		$this->Auth->allow(array('create'));
		$this->Auth->autoRedirect = false;
	}
	
	function login() {
		if ($this->Auth->user()) {
			if (!empty($this->data) && !empty($this->data['User'])) {
				$user = $this->data['User'];
				if (!empty($user['remember'])) {
					$this->RememberMe->remember($user['username'], $user['password']);
				}
			}

			// Will redirect back to the same page once, to clear the post data, if started here
			$this->redirect($this->Auth->redirect());
		}
	}

	function logout() {
		$this->Auth->logout();
		$this->RememberMe->delete();
		$this->redirect('/');
	}

	function create() {
		if (!empty($this->data)) {
			try {
				$this->User->data = $this->data;
				if ($this->isValidCreate($this->data['User'])) {
					if ($this->User->save()) {
						$this->redirect('/');
						return;
					}
				}
				$this->Session->setFlash('Unable to create user');
			} catch (Exception $e) {
				$this->Session->setFlash($e->getMessage());
			}
		}
		unset($this->data['User']['password']);
		unset($this->data['User']['password2']);
	}

	private function isValidCreate($user) {
		if (!empty($user['password'])) {
			if (strlen($user['password2']) >= 6) {
				if ($user['password'] == $this->Auth->password($user['password2'])) {
					if ($this->User->find('count', array('conditions' => array('username' => $user['username']))) == 0) {
						return true;
					} else {
						throw new Exception('Username already exists');
					}
				} else {
					throw new Exception('Password confirmation does not match');
				}
			} else {
				throw new Exception('Password much be 6 characters or greater');
			}
		}
		return false;
	}
						
}
