<?php

class ScoresShell extends Shell {
	var $uses = array();

	public function main() {
		$time = empty($this->params['time']) ? false : $this->params['time'];
		if (empty($time)) {
			$this->usage();
			return 1;
		}

		$this->out("TIME=$time");
		return 0;
	}

	public function startup() {}

	public function usage() {
		$this->out("-time <date>");
	}
}
