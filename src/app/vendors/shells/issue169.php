<?php

App::import('Vendor', 'scorer/espn');
App::import('Vendor', 'scorer/football_week_num');

/**
 * @property Score Score
 * @property mixed LeagueType
 * @property mixed SourceType
 * @author loyd
 */
class Issue169Shell extends Shell {
	var $uses = array(
	    'Score',
		'LeagueType',
		'SourceType',
	    'Odd'
	);
	private $official = false;

	public function main() {
		$espn = new Espn($this->Score, $this->LeagueType, $this->SourceType, new FootballWeekNum());
		$allScores = $this->Score->find('all', array('conditions' => array('sourceid' => 1, 'active' => 1), 'order' => 'id ASC'));

		$builtScores = array();
		foreach ($allScores as $score) {
			$row = $score['Score'];
			$builtScores[$row['id']] = array('Score' => $row, 'new_source_gameid' => $espn->createSourceGameId($row));
		}

		$merge = array();
		foreach ($builtScores as $sid => $row) {
			if ($row['Score']['source_gameid'] != $row['new_source_gameid']) {
				if (empty($merge[$row['new_source_gameid']])) {
					$merge[$row['new_source_gameid']] = array();
				}
				$merge[$row['new_source_gameid']][] = $row;
			}
		}

		foreach ($merge as $source_gameid => $rows) {
			if (count($rows) == 1) {
				$this->updateSGID($rows[0]['Score'], $source_gameid);
			} else {
				$this->fixBetsAndMerge($rows);
			}
		}
	}

	private function updateSGID($game, $source_gameid) {
		//echo "-- UPDATING {$game['id']} from {$game['source_gameid']} -> $source_gameid : LT({$game['league']})\n";
		echo "UPDATE `scores` SET `source_gameid` = '$source_gameid' WHERE `id`={$game['id']} LIMIT 1;\n";
	}

	private function delete($toid, $rows) {
		$ids = array();
		foreach ($rows as $row) {
			$ids[] = $row['Score']['id'];
		}

		// has to move the bet first, FK will fail. Good, we will not remove anything that has bets

		//echo "-- MOVEBETS (".implode($ids,',').") to $toid\n";
		foreach ($ids as $id) {
			echo "UPDATE `user_bets` SET `scoreid`='$toid' WHERE `scoreid`='$id';\n";
		}

		//echo "-- DELETE (".implode($ids,',').")\n";
		foreach ($ids as $id) {
			echo "DELETE FROM `scores` WHERE `id`=$id LIMIT 1;\n";
		}
	}

	private function fixBetsAndMerge($rows) {
		$ids = array();
		foreach ($rows as $row) {
			$ids[] = $row['Score']['id'];
		}
		if (!$this->verifyAllSame($rows)) {
			if (!$this->official) {
				echo "-- ERRORRRRRR!! (".implode($ids,',').")\n";
			}
			return;
		}

		//echo "-- MERGE (".implode($ids,',').") ";
		$this->updateSGID($rows[0]['Score'], $rows[0]['new_source_gameid']);
		$this->delete($rows[0]['Score']['id'], array_slice($rows, 1));
	}

	private function verifyAllSame($rows) {
		$f = $rows[0]['Score'];
		$home = $f['home'];
		$visitor = $f['visitor'];
		$hsh = $f['home_score_half'];
		$hst = $f['home_score_total'];
		$vsh = $f['visitor_score_half'];
		$vst = $f['visitor_score_total'];
		foreach ($rows as $row) {
			$r = $row['Score'];
			$homeR = $r['home'];
			$visitorR = $r['visitor'];
			$hshR = $r['home_score_half'];
			$hstR = $r['home_score_total'];
			$vshR = $r['visitor_score_half'];
			$vstR = $r['visitor_score_total'];

			if (
				$home != $homeR ||
				$visitor != $visitorR ||
				$hsh != $hshR ||
				$hst != $hstR ||
				$vsh != $vshR ||
				$vst != $vstR
			) {
				return false;
			}
		}
		return true;
	}

	public function startup() {
	}
}