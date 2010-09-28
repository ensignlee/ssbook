<?php

class Pinnacle_Log extends Object {
	public function log($str, $type = 'debug') {
		parent::log('['.getmypid().'] '.$str, 'pinnacle_'.date('Ymd'));
		if ($type == 'error') {
			echo $str;
		}
	}
}

class Pinnacle extends Pinnacle_Log {

	private $lastid = false;
	private $sourceid = null;
	private $xml = null;
	private $matches = null;

	private $shell = null;

	public function __construct(&$shell) {
		$this->shell = $shell;
		$this->lastGame = Cache::read('pinnacle_lastGame');
		$this->sourceid = $this->shell->SourceType->getOrSet('Pinnacle');
	}

	public function match() {
		try {
			$this->xml = $this->readXml();
			$games = $this->getGameInfo();
			$this->matches = array();
			foreach ($games as $game) {
				$match = $this->findGameMatch($game);
				if ($match !== false) {
					$this->matches[] = $match;
				} else {
					$this->log("Unable to find match for ".json_encode($game));
				}
			}
			$success = $this->saveMatches();
			$this->log("Finished creating $success odd(s)");
			Cache::write('pinnacle_lastGame', $this->lastGame);
		} catch (Exception $e) {
			$this->log('Unable to read odds'.$e->getMessage(), 'error');
		}
	}
	
	private function saveMatches() {
		$success = 0;
		foreach ($this->matches as $match) {
			$data = array('sourceid' => $this->sourceid, 'scoreid' => $match['game']['scoreid']);
			foreach ($match['types'] as $name => $type) {
				$cdata = $data;
				$cdata = array_merge($data, $type);
				$cdata['type'] = $name;
				$this->shell->Odd->create();
				if ($this->shell->Odd->save($cdata)) {
					$success++;
				} else {
					throw new Exception('Trouble saving odds for game');
				}
			}			
		}
		return $success;
	}

	private function findGameMatch($game) {
		$matched = $this->shell->Score->findMatching($game['game']);
		if ($matched === false) {
			return false;
		}
		$game['game']['scoreid'] = $matched['Score']['id'];
		return $game;
	}

	private function getGameInfo() {
		if (empty($this->xml)) {
			throw new Exception('Was unable to read XML');
		}
		$sxml = simplexml_load_string($this->xml);
		$this->lastGame = (int)$sxml->lastGame;
		$this->log("Last game = {$this->lastGame}");

		$out = array();
		$skipped = 0;
		$this->log("Found potentially ".count($sxml->events->event)." events");
		foreach ($sxml->events->event as $event) {
			$event = $this->parseEvent($event);
			if (!empty($event) && !empty($event['types'])) {
				$out[] = $event;
			} else {
				$skipped++;
			}
		}

		if ($skipped > 0) {
			$this->log("Skipped $skipped events");
		}

		return $out;
	}

	private function parseEvent($event) {

		$league_name = $this->parseLeagueName("{$event->sporttype} {$event->league}");
		if ($league_name === false) {
			return false;
		}
		$league_id = $this->shell->LeagueType->getOrSet($league_name);

		$game_strtime = strtotime("{$event->event_datetimeGMT} GMT");
		if (empty($game_strtime)) {
			throw new Exception("Unable to parse date time {$event->event_datetimeGMT}");
		}
		$game_date = date('Y-m-d H:i:s', $game_strtime);

		list($home, $visitor) = $this->parseHomeVisit($event->participants->participant);
		if (empty($home) || empty($visitor)) {
			throw new Exception('Unable to read home visitor'.json_encode($event));
		}

		$game = array(
			'game_date' => $game_date,
			'league_name' => $league_name,
			'league_id' => $league_id,
			'home' => $home,
			'visitor' => $visitor
		);
		$ps = $event->periods->period;
		$types = array();
		foreach ($ps as $p) {
			if (strtolower($p->period_update) != 'open') {
				continue;
			}			

			if (strtolower($p->period_description) == 'game') {
				if (isset($p->total)) {
					$types['total'] = array(
						'odds_home' => "{$p->total->over_adjust}",
						'odds_visitor' => "{$p->total->under_adjust}",
						'total' => "{$p->total->total_points}"
					);
				}
				if (isset($p->spread)) {
					$types['spread'] = array(
						'odds_home' => "{$p->spread->spread_adjust_home}",
						'spread_home' => "{$p->spread->spread_home}",
						'odds_visitor' => "{$p->spread->spread_adjust_visiting}",
						'spread_visitor' => "{$p->spread->spread_visiting}"
					);
				}
				if (isset($p->moneyline)) {
					$types['moneyline'] = array(
						'odds_visitor' => "{$p->moneyline->moneyline_visiting}",
						'odds_home' => "{$p->moneyline->moneyline_home}"
					);
				}
			}
		}
		return array('game' => $game, 'types' => $types);
	}

	private function parseHomeVisit($ps) {
		$home = false;
		$visitor = false;
		foreach ($ps as $p) {
			if (strtolower($p->visiting_home_draw) == "home") {
				$home = "{$p->participant_name}";
			} else {
				$visitor = "{$p->participant_name}";
			}
		}
		return array($home, $visitor);
	}

	private function parseLeagueName($name) {
		switch(strtolower($name)) {
		case 'baseball mlb':
			return "MLB";
		case 'football nfl':
			return 'NFL';
		case 'football ncaa':
			return 'NCAAF';
		default:
			return false;
		}
	}
	
	private function getUrl() {
		$url = 'http://xml.pinnaclesports.com/pinnacleFeed.asp';
		if (!empty($this->lastGame)) {
			$url .= "?lastGame={$this->lastGame}";
		}
		return $url;
	}

	private function readXml() {
		$url = $this->getUrl();
		$this->log("Fetching $url");
		$xml = curl_file_get_contents($url);
		file_put_contents('/tmp/'.date('Ymd').'pinnacle', $xml);
		return $xml;
	}
}
