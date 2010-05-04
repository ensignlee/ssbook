<?php

class PagesController extends AppController {
	var $name = 'Pages';
	var $uses = array();

	public function display() {
		$this->pageTitle = "Home";
		$user = $this->Auth->user();
		$this->set('user',$user['User']);
	}
}
