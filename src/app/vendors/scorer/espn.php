<?php

// In PEAR
require('phpQuery.php');

class Espn_Log extends Object {
	public function log($str, $type = 'debug') {
		$str = "ESPN: $str";
		parent::log($str, $type);
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
		//$this->types[] = new Espn_MLB();
		//$this->types[] = new Espn_NBA();
		//$this->types[] = new Espn_NFL();
		$this->types[] = new Espn_NCAAF();

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
			$this->loadType($type);
			$this->parseType();
			$success = $this->saveType();
			$this->log("Saving $success game(s)");
			sleep(1);
		}
	}

	public function saveType() {
		$success = 0;
		if (!empty($this->scores)) {
			foreach ($this->scores as $score) {
				$score['sourceid'] = $this->sourceid;
				$this->shell->Score->setToRecord('source_gameid', $score['source_gameid']);
				if (!empty($this->shell->Score->id)) {
					unset($score['game_date']);
				}
				if ($this->shell->Score->save($score)) {
					$success++;
				} else {
					throw new Exception('Unable to save game'.json_encode(array($score, $this->shell->Score->validationErrors)));
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
		$out = date('Y-m-d H:i:s', strtotime(str_replace('ET', 'EDT', $str)));
		return $out;
	}
}

class Espn_NCAAF extends Espn_NFL {

	public $leagueName = 'NCAAF';
	
        public function getUrl($date) {
                return sprintf('http://scores.espn.go.com/college-football/scoreboard?date=%s', date('Ymd', strtotime($date)));
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
			$out[] = $row;
		}

		$scores = pq('.preview');
		foreach ($scores as $score) {
			$row = $this->parseScore($score, true);
			$out[] = $row;
		}

		return $out;
	}

	protected function parseScore($score, $preview = false) {
		$row = array();
		$dateStr = pq($score)->parents('.gameDay-Container')->prev()->text();
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
		if ($status == "Final") {
			$status = "";
		}
		$row['game_date'] = self::createDate("$dateStr $status");
		
		$id = pq($score)->attr('id');
		if (preg_match('/[0-9]+/', $id, $m)) {
			$row['source_gameid'] = $m[0];
		} else {
			throw new Exception('Unable to find source_gameid');
		}
		return $row;
	}

}

class Espn_MLB extends Espn_Scorer {
	
	public $leagueName = 'MLB';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/mlb/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}

	public function parseHtml($html) {
		$doc = phpQuery::newDocument($html);

		$date = pq('.key-dates > h2')->text();
		if (strpos($date, 'Scores for') === false) {
			throw new Exception('Unable to find correct date');
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
		$status = Espn::replaceNull(pq("span[id$='statusLine2']", $score)->text());
		$row['game_date'] = self::createDate("$gametime $status");
		
		$id = pq($score)->attr('id');
		if (preg_match('/[0-9]+/', $id, $m)) {
			$row['source_gameid'] = $m[0];
		} else {
			throw new Exception('Unable to find source_gameid');
		}
		return $row;
	}
}

class Espn_NBA extends Espn_MLB {
	
	public $leagueName = 'NBA';

	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/nba/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}
	
	protected function parseScore($score, $gametime) {
		$row = parent::parseScore($score, $gametime);
		list($visitor, $home) = $this->getHalf($score);
		$row['visitor_score_half'] = $visitor;
		$row['home_score_half'] = $home;
		return $row;
	}

	protected function getHalf($score) {
		$titles = pq('th[id*="lsh"]', $score);
		$periods = array();
		foreach ($titles as $title) {
			$id = pq($title)->attr('id');
			if (!preg_match('/[0-9T]$/', $id, $m)) {
				throw new Exception('Unable to find scores');
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
			throw new Exception('Unable to locate periods 1 and 2');
		}
		$visitor = pq("td[id$=als{$one}]", $score)->text() + pq("td[id$=als{$two}]", $score)->text();
		$home = pq("td[id$=hls{$one}]", $score)->text() + pq("td[id$=hls{$two}]", $score)->text();
		return array($visitor, $home);
	}
}
