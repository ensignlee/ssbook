<?php

class PagesController extends AppController {
	var $name = 'Pages';
	var $uses = array();

	public function beforeFilter() {
		$this->Auth->allow('display', 'view', 'enter');
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
}
