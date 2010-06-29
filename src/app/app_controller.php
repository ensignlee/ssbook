<?php
class AppController extends Controller {
	var $components = array('Auth');
	var $helpers = array('Html', 'Javascript', 'Session', 'Form');

	public function beforeFilter() {
		parent::beforeFilter();
	}
}
