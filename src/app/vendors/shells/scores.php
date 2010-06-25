<?php

class ScoresShell extends Shell {
	var $uses = array('Score');

	public function main() {
		$type = empty($this->params['type']) ? false : $this->params['type'];
		if (empty($type)) {
			$this->usage();
			return 1;
		}
		switch ($type) {
		case 'espn':
			App::import('Vendor', 'scorer/espn');
			$scorer = new Espn($this);
			break;
		default:
			$this->usage();
			return 1;
		}
		$scorer->score();
			
		return 0;
	}

	public function startup() {}

	public function usage() {
		$this->out("-type <espn>");
	}
}
