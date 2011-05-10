<?php

class RemoveGamesShell extends Shell {
	var $uses = array(
	    'Score'
	);
	var $tasks = array(
	    'SqlDump'
	);

	private $debug = false;
	
	private $idsCancelled = array();
	
	private function cancelScore($score) {
		$id = $score['Score']['id'];
		if (empty($this->idsCancelled[$id])) {
			$score['Score']['active'] = 0;
			$this->log("Removing game ".json_encode($score), 'info');
			$this->Score->save($score);
			$this->idsCancelled[$id] = true;
		} else {
			$this->log("Already removed $id", 'info');
		}
	}
	
	private function removeOldScores() {
		$scores = $this->Score->findBadGames();
		$len = count($scores);
		$this->log("Found $len old scores", 'info');
		
		foreach ($scores as $score) {
			$this->cancelScore($score);
		}
	}
	
	private function removeCloseScores() {
		$scores = $this->Score->findCloseGames();
		$len = count($scores);
		$this->log("Found $len close games", 'info');
		
		foreach ($scores as $score) {
			$this->removeWrongGame($score);
		}
	}
	
	private function removeWrongGame($scores) {
		$splitUpGames = array();
		$i = 0;
		$buffer = 60 * 60 * 3;
		foreach ($scores as $score) {
			$found = false;
			$thisGameDate = strtotime($score['Score']['game_date']);
			foreach ($splitUpGames as &$rows) {
				if (count($rows) > 0) {
					$row = $rows[0];
					if (abs($thisGameDate - strtotime($row['Score']['game_date'])) < $buffer) {
						$found = true;
						$rows[] = $score;
					}
				}
			}
			unset($rows);
			if (!$found) {
				$splitUpGames[$i] = array($score);
				$i++;
			}
		}
		foreach ($splitUpGames as $split) {
			$this->removeAllButLast($split);
		}
	}
	
	private function removeAllButLast($scores) {
		if (empty($scores) || count($scores) <= 1) {
			return false;
		}
		usort($scores, array($this, '_sort_modified_date'));
		$first = true;
		foreach ($scores as $score) {
			// first one is the last modified
			if ($first) {
				$first = false;
				continue;
			}
			$this->cancelScore($score);
		}
	}
	
	private function _sort_modified_date($left, $right) {
		$l = strtotime($left['Score']['modified']);
		$r = strtotime($right['Score']['modified']);
		if ($l == $r) {
			return 0;
		}
		return $l > $r ? -1 : 1;
	}

	public function main() {
		$this->removeOldScores();
		
		$this->removeCloseScores();
		
		if ($this->debug) {
			$this->SqlDump->execute();
		}
	}

	public function startup() {
		$this->debug = empty($this->params['debug']) ? false : true;
	}
}
