<?php

class PagesController extends AppController {
	var $name = 'Pages';
	var $uses = array();
	var $components = array('Email');

	public function beforeFilter() {
		$this->Auth->allow('display', 'view', 'enter', 'feedback', 'welcome');
		parent::beforeFilter();
	}

	public function display() {
		$this->pageTitle = "Home";
	}

	public function view() {
		$this->pageTitle = "View";
	}

	public function enter() {
		$this->pageTitle = "Enter";
	}
	
	public function welcome() {
		$this->pageTitle = "Welcome";
	}
	
	public function feedback() {
		$this->pageTitle = "Feedback";
		$sent = false;
		
		if (!empty($this->params['form'])) {
			$form = $this->params['form'];
			$email = $form['email'];
			$username = $form['username'];
			$feedback = $form['feedback'];
			
			$message = "Email: $email\nUsername: $username\nFeedback:\n$feedback";
			$this->Email->from = 'SharpBetTracker App<no-reply@sharpbettracker.com>';
			$this->Email->to = Configure::read('feedback.email');
			$this->Email->subject = 'User Feedback';
			$this->Email->send($message);
			$sent = true;
		}
		$this->set('sent', $sent);
	}
}
