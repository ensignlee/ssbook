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

		$date = null;
		if (!empty($this->params['date']) && strtotime($this->params['date']) > 0) {
			$date = $this->params['date'];
		}

		$all = empty($this->params['all']) ? null : $this->params['all'];
		if (!empty($all)) {
			// Lots
			$date = empty($date) ? date('Y-m-d', strtotime('yesterday')) : $date;
			foreach (range(0, $all) as $add) {
				$newdate = date('Y-m-d', strtotime("$date + $add days"));
				$scorer->score($newdate);
				sleep(5);
			}
		} else {
			// Once
			$scorer->score($date);
		}
			
		return 0;
	}

	public function startup() {}

	public function usage() {
		$this->out("-type <espn> [-date YYYY-MM-DD]");
	}
}
