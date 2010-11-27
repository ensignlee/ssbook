<?php
class AppController extends Controller {
	var $components = array('RememberMe', 'Auth', 'Session');
	var $helpers = array('Html', 'Javascript', 'Form', 'Session');

	public function beforeFilter() {
		parent::beforeFilter();

		$this->RememberMe->check();
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
