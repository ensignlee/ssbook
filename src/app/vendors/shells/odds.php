<?php

class OddsShell extends Shell {
	var $uses = array('Odd', 'Score', 'LeagueType', 'SourceType');

	public function main() {
		$type = empty($this->params['type']) ? false : $this->params['type'];
		if (empty($type)) {
			$this->usage();
			return 1;
		}
		switch ($type) {
		case 'pinnacle':
			App::import('Vendor', 'odder/pinnacle');
			$odder = new Pinnacle($this);
			break;
		default:
			$this->usage();
			return 1;
		}

		$odder->match();
			
		return 0;
	}

	public function startup() {}

	public function usage() {
		$this->out("-type <pinnacle>");
	}
}
