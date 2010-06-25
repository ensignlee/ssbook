<?php

// In PEAR
require('phpQuery.php');

class Espn {

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

	public function score() {
		foreach ($this->types as $type) {
			$this->loadType($type);
			$this->parseType();
			$this->saveType();
		}
	}

	public function saveType() {
		$success = 0;
		if (!empty($this->scores)) {
			foreach ($this->scores as $score) {
				$score['sourceid'] = $this->sourceid;
				$this->shell->Score->setToRecord('source_gameid', $score['source_gameid']);
				if ($this->shell->Score->save($score)) {
					$success++;
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
		$this->html = curl_file_get_contents($url);
	}
}

abstract class Espn_Scorer {
	protected $league;
	public $leagueName;

	abstract public function getUrl($date);
	abstract public function parseHtml($html);
	public function setLeague() {
		App::import('Model', 'league_type');
		$lt = new LeagueType();
		$this->league = $lt->getOrSet($this->leagueName);
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
			$row = array();
			$away = pq("tr[id$='awayHeader']", $score);
			$home = pq("tr[id$='homeHeader']", $score);
			$row['visitor'] = pq('.team-name', $away)->text();
			$row['home'] = pq('.team-name', $home)->text();
			$row['visitor_score_total'] = pq('.team-score', $away)->text();
			$row['home_score_total'] = pq('.team-score', $home)->text();
			$row['game_date'] = $time;
			$row['league'] = $this->league;
			
			$id = pq($score)->attr('id');
			if (preg_match('/[0-9]+/', $id, $m)) {
				$row['source_gameid'] = $m[0];
			} else {
				throw new Exception('Unable to find source_gameid');
			}
			$out[] = $row;
		}
		return $out;
	}
}
