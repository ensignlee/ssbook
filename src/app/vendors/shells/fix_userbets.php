<?php

/**
 * convert 301127011 to 201010101md5..32..chars, undesired event that ESPN does not use unique ids
 */
class FixUserbetsShell extends Shell {
	var $uses = array('UserBets', 'Score');

	private $official = false;

	public function main() {
		$allBets = $this->UserBets->find('all');
		$i = 0;
		foreach ($allBets as $bet) {
			$i++;

			$userbetid = $bet['UserBets']['id'];
			$betgamedate = $bet['UserBets']['game_date'];

			$scoreid = $bet['UserBets']['scoreid'];
			$scoregamedate = null;
			$isParlay = false;
			if (!is_null($scoreid)) {
				$score = $this->Score->findById($scoreid);
				$scoregamedate = $score['Score']['game_date'];
			} else if (in_array($bet['UserBets']['type'], array('parlay', 'teaser'))) {
				$isParlay = true;
				$pbets = $this->UserBets->findAllByParlayid($bet['UserBets']['id']);
				if (!empty($pbets)) {
					$maxDate = 0;
					foreach ($pbets as $pbet) {
						$pscoreid = $pbet['UserBets']['scoreid'];
						if (!is_null($pscoreid)) {
							$pscore = $this->Score->findById($pscoreid);
							$thisdate = $pscore['Score']['game_date'];
						}
						if (is_null($thisdate)) {
							echo "No gamedate found\n";
							$maxDate = 0;
							break;
						}
						$maxDate = max($maxDate, strtotime($thisdate));
					}
					if ($maxDate > strtotime('2000-01-01')) {
						$scoregamedate = date('Y-m-d H:i:s', $maxDate);
					} else {
						echo "Unable to determine max game date\n";
						var_dump($pbets);
					}
				}
			}

			if (empty($scoregamedate) || empty($userbetid)) {
				echo "PROBLEM!\n";
				var_dump($bet);exit;
			}
			
			if ($betgamedate != $scoregamedate) {
				if ($isParlay) {
					echo "Parlay - ";
				}
				echo "Game dates do not match, attempting to fix - $betgamedate != $scoregamedate\n";
				if ($this->official) {
					$this->UserBets->id = $userbetid;
					$bet['UserBets']['game_date'] = $scoregamedate;
					if (!$this->UserBets->save($bet)) {
						echo "FATAL ERROR! Unable to save bet\n";
						exit;
					}
				}
			}			
			if ($i % 100 == 0) {
				echo "$i...\n";
			}
		}
	}

	public function startup() {
		$go = isset($this->params['go']);
		$dry = isset($this->params['dry']);
		if (!($go ^ $dry)) {
			$this->usage();
			exit;
		}
		$this->official = $go;
	}

	public function usage() {
		$this->out("-dry|-go");
	}
}
