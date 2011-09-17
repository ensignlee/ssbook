<?php

class UsersController extends AppController {

	var $name = 'Users';
	var $components = array('Email');

	public function beforeFilter() {
		$this->Auth->allow(array('create', 'activate'));
		$this->Auth->autoRedirect = false;
	}
	
	function login() {
		$auth_user = $this->Auth->user();
		if ($auth_user) {
			// Check if this account has been activated
			if($auth_user['User']['active']) {
				if (!empty($this->data) && !empty($this->data['User'])) {
					$user = $this->data['User'];
					if (!empty($user['remember'])) {
						$this->RememberMe->remember($user['username'], $user['password']);
					}
				}

				// Will redirect back to the same page once, to clear the post data, if started here
				$this->redirect($this->Auth->redirect());
			} else {
				// If account is not active, log them out
				$this->Auth->logout();
				$this->RememberMe->delete();
			}
		}
		$error = false;
		if (!empty($this->data) && !empty($this->data['User'])) {
			$error = true;
		}
		$this->set('error', $error);
	}

	function logout() {
		$this->Auth->logout();
		$this->RememberMe->delete();
		$this->redirect('/');
	}

	function create() {
		$this->set('hideLogin', true);
		if (!empty($this->data)) {
			try {
				$this->User->data = $this->data;
				if ($this->isValidCreate($this->data['User'])) {
					if ($this->User->save()) {
						$this->sendActivationEmail($this->User->getLastInsertID());
						$this->redirect('/pages/welcome');
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

	function activate($user_id = null, $activation_code = null) {
		$this->User->id = $user_id;
		if($this->User->exists() && $activation_code == $this->User->getActivationCode()) {
			$this->User->saveField('active', 1);
			$this->redirect('/pages/activated');
		}

		// If the user hasn't been redirected, they'll see the failure message in activate.ctp
	}
	
	public function profile() {
		$appuser = $this->Auth->user();
		$appuser = $appuser['User'];
		$username = $appuser['username'];
		$email = $appuser['email'];
		
		$this->set('username', $username);
		if (!empty($this->data)) {
			$user = $this->data['User'];
			$user['username'] = $username;
			$message = array();
			if (empty($user['password']) || empty($user['password2'])) {
				$pass = 'AOEU1234aeou!@#'; // must be valid password
				$user['password'] = $pass;
				$user['password2'] = $pass;
			} else {
				$message[] = 'Changed password';
			}
			if ($appuser['email'] != $user['email']) {
				$message[] = 'Changed email';
			}
			try {
				$user['password'] = $this->Auth->password($user['password']);
				if (!empty($message) && $this->isValidCreate($user, false)) {
					$this->User->id = $appuser['id'];
					if ($this->User->save($user)) {
						$email = $user['email'];
						$this->Auth->login($user);
						$this->Session->setFlash("Sucess: ".implode(',', $message));
					} else {
						$this->Session->setFlash('Unable to create user');
					}
				}
			} catch (Exception $e) {
				$this->Session->setFlash($e->getMessage());
			}
		}
		
		$this->data = array('User' => array('email' => $email));
	}

	private function isValidCreate($user, $checkexists=true) {
		
		if (preg_match("/^[a-zA-Z_.0-9\-]+$/", $user['username']) == 0) {
			throw new Exception('Username can only contain letters,numbers,underscores,periods, and dashes');
		}
		
		if (strlen($user['username']) <= 2) {
			throw new Exception('Username must be at least 3 characters long');
		}
		
		if (!$this->validEmail($user['email'])) {
			throw new Exception('Please enter a valid email');
		}
		
		if (!empty($user['password'])) {
			if (strlen($user['password2']) >= 6) {
				if ($user['password'] == $this->Auth->password($user['password2'])) {
					if (!$checkexists || $this->User->find('count', array('conditions' => array('username' => $user['username']))) == 0) {
						return true;
					} else {
						throw new Exception('Username already exists');
					}
				} else {
					throw new Exception('Password confirmation does not match');
				}
			} else {
				throw new Exception('Password must be 6 characters or greater');
			}
		}
		return false;
	}
					
	/**
	Validate an email address.
	Provide email address (raw input)
	Returns true if the email address has the email 
	address format and the domain exists.
	 * @author http://www.linuxjournal.com/article/9585?page=0,3
	*/
	function validEmail($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      $isValid = false;
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
		 // local part length exceeded
		 $isValid = false;
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
		 // domain part length exceeded
		 $isValid = false;
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
		 // local part starts or ends with '.'
		 $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
		 // local part has two consecutive dots
		 $isValid = false;
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
		 // character not valid in domain part
		 $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
		 // domain part has two consecutive dots
		 $isValid = false;
	      }
	      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
	      {
		 // character not valid in local part unless 
		 // local part is quoted
		 if (!preg_match('/^"(\\\\"|[^"])+"$/',
		     str_replace("\\\\","",$local)))
		 {
		    $isValid = false;
		 }
	      }
	      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	      {
		 // domain not found in DNS
		 $isValid = false;
	      }
	   }
	   return $isValid;
	}

	private function sendActivationEmail($user_id) {
		$this->User->id = $user_id;
		$user = $this->User->read();
		$user = $user['User'];

		$activation_url = Router::url(array('controller' => 'users', 'action' => 'activate'), true);
		$activation_url .= '/'.$user['id'].'/'.$this->User->getActivationCode();
		$this->set('activation_url', $activation_url);
		$this->set('username', $user['username']);

		$this->Email->from = 'SharpBetTracker App<no-reply@sharpbettracker.com>';
		$this->Email->to = $user['email'];
		$this->Email->subject = 'Please confirm your email address';
		$this->Email->template = 'activate_account';
		$this->Email->sendAs = 'text';
		$this->Email->send();
	}
}
