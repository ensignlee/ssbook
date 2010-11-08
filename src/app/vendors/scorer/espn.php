<?php

// In PEAR
require('phpQuery.php');

class Espn_Log extends Object {
	public function log($str, $type = 'debug') {
		parent::log('['.getmypid().'] '.$str, 'espn_'.date('Ymd'));
		if ($type == 'error') {
			echo $str; // Echo goes to email
		}
	}
}

class Espn extends Espn_Log {

	private $types = null;
	private $cur = null;
	private $date = null;
	private $sourceid = null;

	public function __construct($shell) {
		$this->shell = $shell;
		if (empty($this->shell->Score)) {
			throw new Exception('Unable to find Score model');
		}
		$this->types = array();
		$this->types[] = new Espn_MLB();
		$this->types[] = new Espn_NBA();
		$this->types[] = new Espn_NFL();
		$this->types[] = new Espn_NCAAF();
		$this->types[] = new Espn_NHL();

		$this->setSourceId();
		$this->date = $this->shell->Score->getLastGameDate($this->sourceid);
		if (empty($this->date)) {
			$this->date = date('Y-m-d', strtotime('-30 days'));
		} else {
			$this->date = date('Y-m-d', strtotime("{$this->date} +1 day"));
		}
	}

	private function setSourceId() {
		App::import('Model', 'SourceType');
		$st = new SourceType();
		$this->sourceid = $st->getOrSet('ESPN');
	}

	public static function replaceNull($str) {
		$tstr = mb_trim($str);
		// Match number or letter
		if ($tstr == "" || !preg_match('/[A-Za-z_0-9]+/', $tstr)) {
			return null;
		}
		return $tstr;
	}

	public static function addNull($left, $right) {
		if (is_null($left) || is_null($right)) {
			return null;
		}
		return $left + $right;
	}

	public function score($date = null) {
		if (!empty($date)) {
			$this->date = $date;
		}

		foreach ($this->types as $type) {
			try {
				$this->loadType($type);
				$this->parseType();
				$success = $this->saveType();
				$this->log("Saving $success game(s)");
				sleep(1);
			} catch (Exception $e) {
				$this->log($e->getMessage()."\n", 'error');
			}
		}
	}

	/**
	 * Potentially this should be set before we actually change the names
	 * to something more human readible. This way we can match games
	 * that are the same name from ESPN itself
	 * TODO: Look into making sure this does not collide?
	 * @param <type> $score
	 * @return string
	 */
	private function createSourceGameId($score) {
		if (empty($score['home']) || empty($score['visitor']) || 
			empty($score['league']) || empty($score['game_date'])) {
			new Exception("Unable to create id from score".json_encode($score));
		}
		$home = strtolower($score['home']);
		$visitor = strtolower($score['visitor']);
		$league = $score['league'];
		$date = date('Ymd', strtotime($score['game_date']));

		$id = $date.$league.md5($home.$visitor);
		return $id;
	}

	public function saveType() {
		$success = 0;
		if (!empty($this->scores)) {
			foreach ($this->scores as $score) {

				if (isset($score['game_date'])) {
					if (empty($score['game_date'])) {
						$this->log('Game date cannot be empty '.json_encode($score), 'error');
						continue;
					}
					$t = strtotime($score['game_date']);
					if ($t == false || $t < strtotime('2000-01-01')) {
						$this->log('Game time to small '.json_encode($score), 'error');
						continue;
					}
				}

				$score['sourceid'] = $this->sourceid;
				if (!isset($score['source_gameid'])) {
					// ESPNs id cannot be trusted
					$score['source_gameid'] = $this->createSourceGameId($score);
				}
				$this->shell->Score->setToRecord('source_gameid', $score['source_gameid']);

				// Only remove the game_date if it already exists
				if (!empty($this->shell->Score->id)) {
					if (date('h', strtotime($score['game_date'])) <= 0) {
						unset($score['game_date']);
					}
					$league = $this->shell->Score->read('league');
					if (empty($league) || $league['Score']['league'] != $score['league']) {
						$this->log("Score league does not match".json_encode($score), 'error');
					}
				}
				
				if ($this->shell->Score->save($score)) {
					$success++;
					$this->log("Saving {$score['visitor']} @ {$score['home']}");
				} else {
					$this->log('Unable to save game'.json_encode(array($score, $this->shell->Score->validationErrors)), 'error');
				}
			}
		}
		return $success;
	}

	public function parseType() {
		if (empty($this->cur)) {
			throw new Exception('No type selected');
		}
		if (empty($this->html)) {
			throw new Exception('No html found');
		}
		$this->scores = $this->cur->parseHtml($this->html);
	}

	public function loadType($type) {
		if (empty($type)) {
			throw new Exception('No type selected');
		}
		$type->setLeague();
		$this->cur = $type;
		$url = $this->cur->getUrl($this->date);
		$this->log("Looking up $url");
		$this->html = curl_file_get_contents($url);
		file_put_contents('/tmp/'.date('Ymd').$type->leagueName, $this->html);
	}
}

abstract class Espn_Scorer extends Espn_Log {
	protected $league;
	public $leagueName;

	abstract public function getUrl($date);
	abstract public function parseHtml($html);
	public function setLeague() {
		App::import('Model', 'league_type');
		$lt = new LeagueType();
		$this->league = $lt->getOrSet($this->leagueName);
	} 

	public function addDays() {
		return 1;
	}

	protected static function createDate($str) {
		$strtime = strtotime(str_replace('ET', 'EDT', $str));
		if ($strtime === false) {
			return false;
		}
		$out = date('Y-m-d H:i:s', $strtime);
		return $out;
	}
}

class Espn_NCAAF extends Espn_NFL {

	public $leagueName = 'NCAAF';
	
        public function getUrl($date) {
                return sprintf('http://scores.espn.go.com/ncf/scoreboard?confId=80&date=%s', date('Ymd', strtotime($date)));
        }
}

class Espn_NFL extends Espn_Scorer {
	
	public $leagueName = 'NFL';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/nfl/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}
	
	public function addDays() {
		return 6; // Only 6, just to be careful?
	}

	public function parseHtml($html) {
		$doc = phpQuery::newDocument($html);
		$out = array();

		$scores = pq('.final-state');
		foreach ($scores as $score) {
			$row = $this->parseScore($score);
			if (!empty($row)) {
				$out[] = $row;
			}
		}

		$scores = pq('.preview');
		foreach ($scores as $score) {
			$row = $this->parseScore($score, true);
			if (!empty($row)) {
				$out[] = $row;
			}
		}

		return $out;
	}

	protected function parseScore($score, $preview = false) {
		$row = array();
		$dateStr = pq($score)->parents('.gameDay-Container')->prev()->text();
		if (empty($dateStr)) {
			$this->log('Unable to find dateStr '.json_encode($score), 'error');
		}
		$away = pq(".visitor", $score);
		$home = pq(".home", $score);
		$row['visitor'] = pq('.team-name a', $away)->text();
		$row['home'] = pq('.team-name a', $home)->text();

		if (!$preview) {
			$row['visitor_score_total'] = Espn::replaceNull(pq('.score .final', $away)->text());
			$row['home_score_total'] = Espn::replaceNull(pq('.score .final', $home)->text());
			$row['visitor_score_half'] = Espn::addNull(Espn::replaceNull(pq('li[id$=aScore1]', $away)->text()), Espn::replaceNull(pq('li[id$=aScore2]', $away)->text()));
			$row['home_score_half'] = Espn::addNull(Espn::replaceNull(pq('li[id$=hScore1]', $home)->text()), Espn::replaceNull(pq('li[id$=hScore2]', $home)->text()));
		}

		$row['league'] = $this->league;
		$status = Espn::replaceNull(pq(".game-status", $score)->text());
		if (strpos($status,"Final") !== false) {
			$status = "";
		}
		if ($status == "TBD") {
			$this->log("Game is still TBD {$row['visitor']} @ {$row['home']}");
			return false;
		}
		$row['game_date'] = self::createDate("$dateStr $status");
		
		return $row;
	}

}

class Espn_NHL extends Espn_MLB {
	public $leagueName = 'NHL';
	protected $statusLine = 'statusLine2Left';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/nhl/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}
}

class Espn_MLB extends Espn_Scorer {

	protected $statusLine = 'statusLine2';
	public $leagueName = 'MLB';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/mlb/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}

	public function parseHtml($html) {
		$doc = phpQuery::newDocument($html);

		$date = pq('.key-dates > h2')->text();
		if (strpos($date, 'Scores for') === false) {
			throw new Exception("Unable to find correct date\n".$html);
		}
		$time = date('Y-m-d', strtotime(str_replace('Scores for', '', $date)));

		$scores = pq('.mod-scorebox-final');
		$out = array();
		foreach ($scores as $score) {
			$row = $this->parseScore($score, $time);
			$out[] = $row;
		}

		$scores = pq('.mod-scorebox-pregame');
		foreach ($scores as $score) {
			$row = $this->parseScore($score, $time);
			$out[] = $row;
		}

		return $out;
	}

	protected function parseScore($score, $gametime) {
		$row = array();
		$away = pq("tr[id$='awayHeader']", $score);
		$home = pq("tr[id$='homeHeader']", $score);
		$row['visitor'] = pq('.team-name', $away)->text();
		$row['home'] = pq('.team-name', $home)->text();
		$row['visitor_score_total'] = Espn::replaceNull(pq('.team-score', $away)->text());
		$row['home_score_total'] = Espn::replaceNull(pq('.team-score', $home)->text());
		$row['league'] = $this->league;
		$status = Espn::replaceNull(pq("span[id$='{$this->statusLine}']", $score)->text());
		$parsedtime = self::createDate("$gametime $status");
		if ($parsedtime === false) {
			$parsedtime = self::createDate("$gametime");
		}
		$row['game_date'] = $parsedtime;
		
		return $row;
	}
}

class Espn_NBA extends Espn_MLB {
	
	protected $statusLine = 'statusLine2Left';
	public $leagueName = 'NBA';

	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/nba/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}
	
	protected function parseScore($score, $gametime) {
		$row = parent::parseScore($score, $gametime);
		try {
			list($visitor, $home) = $this->getHalf($score);
			$row['visitor_score_half'] = $visitor;
			$row['home_score_half'] = $home;
		} catch (Exception $e) {
			$this->log('Could not find score '.$e->getMessage());
		}
		return $row;
	}

	protected function getHalf($score) {
		$titles = pq('th[id*="lsh"]', $score);
		$periods = array();
		foreach ($titles as $title) {
			$id = pq($title)->attr('id');
			if (!preg_match('/[0-9T]$/', $id, $m)) {
				throw new Exception('Unable to find scores'.json_encode($score));
			}
			$cid = $m[0];
			$periods[$cid] = pq($title)->text();
		}
		$one = $two = null;
		foreach ($periods as $num => $p) {
			if ($p === "1") {
				$one = $num;
			}
			if ($p === "2") {
				$two = $num;
			}
		}
		if (empty($one) || empty($two)) {
			throw new Exception('Unable to locate periods 1 and 2'.json_encode($score));
		}
		$visitor = pq("td[id$=als{$one}]", $score)->text() + pq("td[id$=als{$two}]", $score)->text();
		$home = pq("td[id$=hls{$one}]", $score)->text() + pq("td[id$=hls{$two}]", $score)->text();
		return array($visitor, $home);
	}
}
