<?php
class AppController extends Controller {
	var $components = array('RememberMe', 'Auth', 'Session','RequestHandler');
	var $helpers = array('Html', 'Javascript', 'Form', 'Session', 'RandomTip');

	public function beforeFilter() {
		parent::beforeFilter();

		$this->RememberMe->check();

		// Suppress debug messages in ajax requests
		if($this->RequestHandler->isAjax()) {
			error_reporting(0);
		}
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

    protected function urlParams() {
        $copy = $this->params['url'];
        unset($copy['url']);
        return isset($this->params) ? $copy : array();
    }
	
	protected function _div($a, $b) {
		if (is_null($a) || is_null($b)) {
			return null;
		}
		if (empty($a) || empty($b)) {
			return 0;
		}
		return $a / $b;
	}
}
