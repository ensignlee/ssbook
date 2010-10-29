<?php
class AppController extends Controller {
	var $components = array('Auth');
	var $helpers = array('Html', 'Javascript', 'Session', 'Form');

	public function beforeFilter() {
		parent::beforeFilter();
	}

	protected function urlGetVar($var, $default = false) {
		if (isset($this->params) && isset($this->params['url']) && isset($this->params['url'][$var])) {
			return !numberSafeEmpty($this->params['url'][$var]) ? $this->params['url'][$var] : $default;
		}
		return $default;
	}
}
