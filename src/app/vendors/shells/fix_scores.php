<?php

/**
 * convert 301127011 to 201010101md5..32..chars, undesired event that ESPN does not use unique ids
 */
App::import('vendor', 'scorer/espn');
class FixScoresShell extends Shell {
	var $uses = array(
	    'Score',
	    'Odd'
	);

	private $official = false;

	private function exists($id) {
		$res = $this->Score->findBySourceGameid($id);
		return !empty($res);
	}

	public function main() {
		$allScores = $this->Score->find('all');
		$i = 0;
		foreach ($allScores as &$score) {
			$i++;
			$newid = Espn::makeId(date('Ymd', strtotime($score['Score']['game_date'])), $score['Score']['league'], $score['Score']['home'], $score['Score']['visitor']);
			if (strlen($score['Score']['source_gameid']) == 41 && strlen($newid) == 41 && !empty($score['Score']['id'])) {
				$oldid = $score['Score']['source_gameid'];
				$score['Score']['source_gameid'] = $newid;
				if ($oldid != $newid) {

					// Only check this stuff on the dry run. After that all bets are off
					if (!$this->official && $this->exists($newid)) {
						echo "what you want to do? $oldid -> $newid\n";
						$resScore = $this->Score->findBySourceGameid($newid);
						if (empty($resScore) || $score['Score']['id'] == $resScore['Score']['id']) {
							echo "PROBLEM!";
							exit;
						}
						$res = $this->Odd->findByScoreid($score['Score']['id']);
						$res2 = $this->Odd->findByScoreid($resScore['Score']['id']);
						$remove = null;
						if (empty($res) && empty($res2)) {
							$remove = $score['Score']['id'] > $resScore['Score']['id'] ? $resScore : $score;
							$other = $score['Score']['id'] <= $resScore['Score']['id'] ? $resScore : $score;

							// If the one that we are removing has a score.
							if (!is_null($remove['Score']['home_score_total'])) {
								// both should have a score
								if (is_null($score['Score']['home_score_total']) || is_null($resScore['Score']['home_score_total'])) {
									$this->out("ERROR DELETING SCORE!!!!");
									$this->in('go ahead delete the other?');
									$remove = $other;
								}
							}

							$in = $this->in("Remove score?", array('y','n'), 'y');
							if ($in == 'y') {
								$this->Score->delete($remove['Score']['id']);
							}

						} else {
							$this->out('MONKEYS!!!');

							if (!empty($res) && !empty($res2)) {
								$this->out("OHNOES");
								exit;
							}

							$infoThatNeedsToStay = $score['Score']['id'] > $resScore['Score']['id'] ? $score : $resScore;
							$scoreThatNeedsToStay = empty($res) ? $resScore : $score;
							$scoreThatNeedsToGo = empty($res) ? $score : $resScore;
							$idtogo = $scoreThatNeedsToGo['Score']['id'];
							$idtostay = $scoreThatNeedsToStay['Score']['id'];

							if ($idtogo == $idtostay) {
								$this->out("AHHHH");
								exit;
							}

							if ($idtostay == $infoThatNeedsToStay['Score']['id']) {
								$this->in('Same ids, removing smaller id without score, and odds');
								$this->Score->delete($idtogo);
							} else {
								if ($infoThatNeedsToStay['Score']['id'] != $idtogo) {
									$this->out('Something awful happend!');
									exit;
								}
								$this->in('Differ ids, moving contents of larger into smaller id, remove larger');
								$infoThatNeedsToStay['Score']['id'] = $idtostay;
								$this->Score->delete($idtogo);
								$this->Score->save($infoThatNeedsToStay);
							}
						}
					}

					if ($this->official) {
						if (!$this->Score->save($score)) {
							$this->out('Big problem');
							exit;
						} else {
							$this->out('Success');
						}
					} else {
						$this->out("{$score['Score']['game_date']} $newid");
					}
					
				} else {
					$this->out("same");
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
