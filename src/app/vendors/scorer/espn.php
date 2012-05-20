<?php

// In PEAR
require('phpQuery.php');

class Espn_Log extends Object {
	private $DEBUG=false;
	public function log($str, $type = 'debug') {
		if ($type == 'debug' && !$this->DEBUG) {
			return;
		}
		parent::log('['.getmypid().'] '.$type.' '.$str, 'espn_'.date('Ymd'));
		if ($type == 'error') {
			echo $str; // Echo goes to email
		}
	}
}

App::import('Vendor', 'scorer/football_week_num');
class Espn extends Espn_Log {

	private $FW;
	private $Score;
	private $LeagueType;
	private $SourceType;

	private $types = null;
	private $cur = null;
	private $date = null;
	private $sourceid = null;

	public function __construct(Score $Score, LeagueType $LeagueType, SourceType $SourceType, FootballWeekNum $FW) {
		$this->Score = $Score;
		$this->LeagueType = $LeagueType;
		$this->SourceType = $SourceType;
		$this->FW = $FW;

		$this->types = array();
		$this->types[] = new Espn_MLB($this->LeagueType);
		$this->types[] = new Espn_NHL($this->LeagueType);
		$this->types[] = new Espn_NCAAB($this->LeagueType);
		$this->types[] = new Espn_NCAAB_March($this->LeagueType);
		$this->types[] = new Espn_NCAAB_March2($this->LeagueType);
		$this->types[] = new Espn_NCAAB_March3($this->LeagueType);
		$this->types[] = new Espn_NBA($this->LeagueType);
		
//		$this->types[] = new Espn_NFL($this->LeagueType);
//		$this->types[] = new Espn_NCAAF($this->LeagueType);
//		$this->types[] = new Espn_NCAAF_AA($this->LeagueType);

		$this->setSourceId();
		$this->date = $this->Score->getLastGameDate($this->sourceid);
		if (empty($this->date)) {
			$this->date = date('Y-m-d', strtotime('-30 days'));
		} else {
			$this->date = date('Y-m-d', strtotime("{$this->date} +1 day"));
		}
	}

	private function setSourceId() {
		$this->sourceid = $this->SourceType->getOrSet('ESPN');
	}

	public static function replaceNull($str) {
		$tstr = mb_trim($str);
		// Match number or letter
		if ($tstr == "" || !preg_match('/[A-Za-z_0-9]+/', $tstr)) {
			return null;
		}
		return $tstr;
	}
	
	public static function emptyNull($str) {
		return empty($str) ? null : $str;
	}
	
	public static function nonbreakingTrim($str) {
		return mb_trim($str, "\xC2\xA0\n ");
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
				$this->log("Saving $success game(s)", 'info');
				sleep(1);
			} catch (Exception $e) {
				$this->log($e->getMessage()."\n", 'error');
			}
		}
	}

	public static function makeId($date, $league, $home, $visitor) {
		$both = array(strtolower($home), strtolower($visitor));
		sort($both); // Do this to keep duplicates popping up from ESPN screwups
		$league = (int)($league);
		return $date.$league.md5($both[0].$both[1]);
	}

	/**
	 * Potentially this should be set before we actually change the names
	 * to something more human readible. This way we can match games
	 * that are the same name from ESPN itself
	 * TODO: Look into making sure this does not collide?
	 * @param <type> $score
	 * @return string
	 */
	public function createSourceGameId($score) {
		if (empty($score['home']) || empty($score['visitor']) || 
			empty($score['league']) || empty($score['game_date'])) {
			new Exception("Unable to create id from score".json_encode($score));
		}
		$home = $score['home'];
		if (!empty($score['homeExtra'])) {
			$home .= $score['homeExtra'];
		}
		$home = trim($home);
		$visitor = $score['visitor'];
		if (!empty($score['visitExtra'])) {
			$visitor .= $score['visitExtra'];
		}
		$visitor = trim($visitor);
		$league = $score['league'];

		$isFootball = $this->LeagueType->leagueIsFootball($league);
		if ($isFootball) {
			$date = $this->FW->yearNum($score['game_date']).$this->FW->weekNum($score['game_date']);
		} else {
			$date = date('Ymd', strtotime($score['game_date']));
		}

		$this->log("Making id with $date,$league,$home,$visitor", 'info');
		$id = self::makeId($date, $league, $home, $visitor);
		return $id;
	}

	public function saveType() {
		$success = 0;
		if (!empty($this->scores)) {
			$this->log('Found '.count($this->scores).' to save.', 'info');
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
				$score['active'] = 1;
				if (!isset($score['source_gameid'])) {
					// ESPNs id cannot be trusted
					$score['source_gameid'] = $this->createSourceGameId($score);
				}
				$this->Score->setToRecord('source_gameid', $score['source_gameid']);

				// Only remove the game_date if it already exists
				if (!empty($this->Score->id)) {
					if (date('H', strtotime($score['game_date'])) <= 0) {
						unset($score['game_date']);
					}
					$league = $this->Score->read('league');
					if (empty($league) || $league['Score']['league'] != $score['league']) {
						$this->log("Score league does not match".json_encode($score), 'error');
					}
				}
				
				if ($this->Score->save($score)) {
					$success++;
					$this->log("Saving {$score['visitor']} @ {$score['home']} {$this->Score->id}", 'info');
				} else {
					$this->log('Unable to save game'.json_encode(array($score, $this->Score->validationErrors)), 'error');
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
		$this->log("Looking up $url", 'info');
		$this->html = curl_file_get_contents($url);
		file_put_contents('/tmp/prod/'.date('Ymd-His-').$type->leagueName, $this->html);
	}
}

abstract class Espn_Scorer extends Espn_Log {
	private $LT;
	protected $league;
	public $leagueName;

	public function __construct(LeagueType $LT) {
		$this->LT = $LT;
	}

	abstract public function getUrl($date);
	abstract public function parseHtml($html);
	public function setLeague() {
		$this->league = $this->LT->getOrSet($this->leagueName);
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

	protected function getHalf($score) {
		return array('','');
	}
}

class Espn_NCAAF_AA extends Espn_NFL {

	public $leagueName = 'NCAAF';
	
        public function getUrl($date) {
                return sprintf('http://scores.espn.go.com/ncf/scoreboard?confId=81&date=%s', date('Ymd', strtotime($date)));
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
		if (strpos($dateStr, "Today") !== false) {
			$this->log("Date is today '$dateStr'");
			$dateStr = date('Y-m-d');
		}
		$away = pq(".visitor", $score);
		$home = pq(".home", $score);
		
		$visitorName = pq('.team-name a', $away)->text();
		if (empty($visitorName)) {
			$visitorName = pq('.team-name', $away)->text();
		}
		$homeName = pq('.team-name a', $home)->text();
		if (empty($homeName)) {
			$homeName = pq('.team-name', $home)->text();
		}
		
		$row['visitor'] = $visitorName;
		$row['home'] = $homeName;

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
		if ($status == "Postponed") {
			$this->log("This game was postponed {$row['visitor']} @ {$row['home']}");
			return false;
		}
		$row['game_date'] = self::createDate("$dateStr $status");
		
		return $row;
	}

}

class Espn_NHL extends Espn_MLB {
	
	public $leagueName = 'NHL';
	protected $statusLine = '*[id$=statusLine2Left]';
	protected $teamname = '.team-name';
	protected $finalScores = '.mod-scorebox-final';
	protected $pregameScores = '.mod-scorebox-pregame';
	protected $awaySelector = '*[id$="awayHeader"]';
	protected $homeSelector = '*[id$="homeHeader"]';
	protected $awayScoreSelector = '*[id$="awayHeaderScore"]';
	protected $homeScoreSelector = '*[id$="homeHeaderScore"]';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/nhl/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}
	
	protected function verify($row) {
		return true;
	}
}

class Espn_MLB extends Espn_Scorer {

	public $leagueName = 'MLB';
	protected $statusLine = '.game-status';
	protected $teamname = '*[id$="TeamName"]';
	protected $finalScores = '.mod-scorebox.final-state';
	protected $pregameScores = '.mod-scorebox.preview';
	protected $awaySelector = '.team.away';
	protected $homeSelector = '.team.home';
	protected $awayScoreSelector = '.finalScore';
	protected $homeScoreSelector = '.finalScore';

	public function getUrl($date) {
		return sprintf('http://scores.espn.go.com/mlb/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}

	public function parseHtml($html) {
		$doc = phpQuery::newDocument($html);

		$date = pq('.key-dates > h2', $doc)->text();
		if (strpos($date, 'Scores for') === false) {
			throw new Exception("Unable to find correct date\n".$html);
		}
		$time = date('Y-m-d', strtotime(str_replace('Scores for', '', $date)));

		$scores = pq($this->finalScores, $doc);
		$out = array();
		foreach ($scores as $score) {
			$row = $this->parseScore($score, $time);
			if ($this->verify($row)) {
				$out[] = $row;
			} else {
				$this->log("Did not verify. {$row['home']} @ {$row['visitor']}", 'warn');
			}
		}

		$scores = pq($this->pregameScores, $doc);
		foreach ($scores as $score) {
			$row = $this->parseScore($score, $time);
			if ($this->verify($row)) {
				$out[] = $row;
			} else {
				$this->log("Did not verify. {$row['home']} @ {$row['visitor']}", 'warn');
			}
		}

		return $out;
	}
	
	protected function verify($row) {
		return !(empty($row['homeExtra']) || empty($row['visitExtra']));
	}

	protected function parseScore($score, $gametime) {
		$this->log("Attempting to parse score for $gametime");
		
		$row = array();
		$away = pq($this->awaySelector, $score);
		$home = pq($this->homeSelector, $score);
		$row['visitor'] = trim(pq($this->teamname, $away)->text());
		$row['home'] = trim(pq($this->teamname, $home)->text());

		$this->log("Hometeam = [{$row['home']}], Awayteam = [{$row['visitor']}]");

		$row['visitor_score_total'] = Espn::replaceNull(pq($this->awayScoreSelector, $away)->text());
		$row['home_score_total'] = Espn::replaceNull(pq($this->homeScoreSelector, $home)->text());
		$row['league'] = $this->league;

		$this->log("VST = {$row['visitor_score_total']}, HST = {$row['home_score_total']}, league = {$row['league']}");

		$status = Espn::replaceNull(pq($this->statusLine, $score)->text());
		$parsedtime = self::createDate("$gametime $status");
		if ($parsedtime === false) {
			$parsedtime = self::createDate("$gametime");
		}
		$this->log("Parsed time = [$parsedtime]");
		
		// Adding in the pitchers
		$homePitcher = pq("div[id$='homeStarter']", $score);
		$visitPitcher = pq("div[id$='awayStarter']", $score);
		$row['homeExtra'] = Espn::emptyNull(Espn::nonbreakingTrim(pq('a', $homePitcher)->text()));
		$row['visitExtra'] = Espn::emptyNull(Espn::nonbreakingTrim(pq('a', $visitPitcher)->text()));
		$half = $this->getHalf($score);
		$row['visitor_score_half'] = $half[0];
		$row['home_score_half'] = $half[1];
		$this->log("HomeExtra = {$row['homeExtra']}, VisitExtra = {$row['visitExtra']}, VSH = {$row['visitor_score_half']}, HSH = {$row['home_score_half']}");
		
		$row['game_date'] = $parsedtime;
		
		return $row;
	}
}

class Espn_NCAAB extends Espn_MLB {

	public $leagueName = 'NCAAB';
	protected $statusLine = '.game-status';
	protected $finalScores = '.main-games.final-state';
	protected $pregameScores = '.main-games.preview';
	protected $awaySelector = 'div.team.visitor';
	protected $homeSelector = 'div.team.home';
	protected $awayScoreSelector = '.final[id$="awayHeaderScore"]';
	protected $homeScoreSelector = '.final[id$="homeHeaderScore"]';

	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/ncb/scoreboard?date=%s&confId=50', date('Ymd', strtotime($date)));
	}

	protected function verify($row) {
		return true;
	}

	protected function getHalf($score) {
		$titles = pq('*[id*="als"]', $score);
		$periods = array();
		foreach ($titles as $title) {
			$id = pq($title)->attr('id');
			if (!preg_match('/[0-9T]$/', $id, $m)) {
				throw new Exception('Unable to find scores'.json_encode($score));
			}
			$cid = $m[0];
			$periods[$cid] = pq($title)->text();
		}
		$one = null;
		foreach ($periods as $p => $num) {
			if ($p == "2") {
				$one = $p;
			}
		}
		if (empty($one)) {
			throw new Exception('Unable to locate periods 1'.json_encode($score));
		}
		$visitor = pq(".team.visitor *[id$=Scores] *[id$=als{$one}]", $score)->text();
		$home = pq(".team.home *[id$=Scores] *[id$=hls{$one}]", $score)->text();
		return array($visitor, $home);
	}
}
	
class Espn_NCAAB_March extends Espn_NCAAB {
	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/ncb/scoreboard?date=%s&confId=100', date('Ymd', strtotime($date)));
	}
}
class Espn_NCAAB_March2 extends Espn_NCAAB {
	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/ncb/scoreboard?date=%s&confId=56', date('Ymd', strtotime($date)));
	}
}
class Espn_NCAAB_March3 extends Espn_NCAAB {
	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/ncb/scoreboard?date=%s&confId=55', date('Ymd', strtotime($date)));
	}
}

class Espn_NBA extends Espn_NHL {
	
	public $leagueName = 'NBA';
	protected $statusLine = '.game-status';
	protected $teamname = '*[id$="TeamName"]';
	protected $finalScores = '.mod-scorebox.final-state';
	protected $pregameScores = '.mod-scorebox.preview';
	protected $awaySelector = '.team.away';
	protected $homeSelector = '.team.home';
	protected $awayScoreSelector = '.finalScore';
	protected $homeScoreSelector = '.finalScore';

	public function getUrl($date) {
		return  sprintf('http://scores.espn.go.com/nba/scoreboard?date=%s', date('Ymd', strtotime($date)));
	}

	protected function getHalf($score) {
/*
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
		if (is_null($one) || is_null($two)) {
			return array('','');
		}
		$visitor = pq("td[id$=als{$one}]", $score)->text() + pq("td[id$=als{$two}]", $score)->text();
		$home = pq("td[id$=hls{$one}]", $score)->text() + pq("td[id$=hls{$two}]", $score)->text();
 */
		$visitor = pq("[id$=alsh1]", $score)->text() + pq("[id$=alsh2]", $score)->text();
		$home = pq("[id$=hlsh1]", $score)->text() + pq("[id$=hlsh2]", $score)->text();
		return array($visitor, $home);
	}
}
