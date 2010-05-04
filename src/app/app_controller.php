<?php
class AppController extends Controller {
	var $components = array('Auth');

	public function beforeFilter() {
		parent::beforeFilter();
	}
}
