<?php

/**
 * convert 301127011 to 201010101md5..32..chars, undesired event that ESPN does not use unique ids
 */
class FixScoresShell extends Shell {
	var $uses = array('Score');

	private $official = false;

	public function main() {
		$allScores = $this->Score->find('all');
		$i = 0;
		foreach ($allScores as &$score) {
			$i++;
			$newid = date('Ymd', strtotime($score['Score']['game_date'])).$score['Score']['league'].md5(strtolower($score['Score']['home'].$score['Score']['visitor']));
			if (strlen($score['Score']['source_gameid']) == 9 && strlen($newid) == 41 && !empty($score['Score']['id'])) {
				$score['Score']['source_gameid'] = $newid;
				if ($this->official) {
					$this->Score->save($score);
				} else {
					echo "{$score['Score']['game_date']} $newid\n";
				}
			} else {
				echo "PROBLEM\n";
				var_dump($newid, $score);
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
