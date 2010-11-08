<?php
class AppController extends Controller {
	var $components = array('Auth');
	var $helpers = array('Html', 'Javascript', 'Session', 'Form');

	public function beforeFilter() {
		parent::beforeFilter();
	}

	public function beforeRender() {
		parent::beforeRender();
		
		$user = $this->Auth->user();
		$this->set('user',$user['User']);
	}

	protected function urlGetVar($var, $default = false) {
		if (isset($this->params) && isset($this->params['url']) && isset($this->params['url'][$var])) {
			return !numberSafeEmpty($this->params['url'][$var]) ? $this->params['url'][$var] : $default;
		}
		return $default;
	}
}
