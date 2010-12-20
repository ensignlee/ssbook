<?php

// In PEAR
require('phpQuery.php');

class NameParserLink {
	public $name;
	public $link;

	private $fullname = null;

	function __construct($name, $link) {
		$this->name = $name;
		$this->link = $link;
	}

	public function getFullname() {
		if (empty($this->fullname)) {
			$html = curl_file_get_contents($this->link);
			$doc = phpQuery::newDocument($html);
			$pq = pq('#sub-branding .logo b', $doc);
			$this->fullname = $pq->text();
		}
		return $this->fullname;
	}
}

abstract class NameParser {
	abstract public function getUrl();
	protected $_cacheLinks;

	protected function getHtml() {
		return curl_file_get_contents($this->getUrl());
	}

	public function getTeamLinks() {
		$html = $this->getHtml();
		$doc = phpQuery::newDocument($html);
		$links = pq('h5 a.bi', $doc);
		$this->_cacheLinks = array();
		foreach ($links as $link) {
			$p = pq($link);
			$this->_cacheLinks[] = new NameParserLink($p->text(), $p->attr('href'));
		}
		return $this->_cacheLinks;
	}

	/**
	 * Match what is the most likely fullnames
	 * @param <type> $name
	 */
	public function getFullnames($name) {
		if (empty($name)) {
			return array();
		}
		$links = array();
		foreach ($this->_cacheLinks as $link) {
			if (strpos($link->name, $name) !== false) {
				$links[] = $link;
			}
		}
		$ret = array();
		foreach ($links as $link) {
			$ret[] = $link->getFullname();
		}
		$ret[] = "n";
		return $ret;
	}
}

class NameParserMLB extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/mlb/teams';
	}
}

class NameParserNFL extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/nfl/teams';
	}
}

class NameParserNCAAF extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/college-football/teams';
	}
}

class NameParserNCAAB extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/mens-college-basketball/teams';
	}
}

class NameParserNBA extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/nba/teams';
	}
}

class NameParserNHL extends NameParser {
	public function getUrl() {
		return 'http://espn.go.com/nhl/teams';
	}
}

class TeamnamesShell extends Shell {
	var $uses = array('SourceSportName', 'SourceType', 'LeagueType');

	private $sourceId;

	private $nameParsers;

	private function getNameParser($league_name) {
		return array_lookup($league_name, $this->nameParsers, null);
	}

	public function main() {
		$all = $this->SourceSportName->find('all', array('conditions' => array('source_id' => $this->sourceId, 'given_name' => null)));
		if (!empty($all)) {
			foreach ($all as $res) {
				sleep(1);
				$source_name = $res['SourceSportName']['source_name'];
				$league_id = $res['SourceSportName']['league_id'];
				$league_name = $this->LeagueType->getName($league_id);
				$parser = $this->getNameParser($league_name);
				if (!empty($parser)) {
					$parser->getTeamLinks();
				} else {
					$this->out('Unable to find parser for '.$league_name);
					continue;
				}

				// Get 5 tries to do this
				$i = 0;
				$in = 'n';
				$expanded = $source_name;
				while ($i < 2 && $in == 'n') {
					$i++;

					// We have all the team links parsed
					$fullnames = $parser->getFullnames($expanded);
					if (empty($fullnames)) {
						$this->out('Unable to match anything for '.$expanded);
						continue;
					}

					// 1 being nothing(n) 2 being (CORRECTNAME/n) so still take 0
					if (count($fullnames) == 1 || (count($fullnames) == 2 && $expanded == $source_name)) {
						$in = $fullnames[0];
					} else {
						if ($i > 1) {
							$in = $this->in("$expanded\t$league_name", $fullnames, $fullnames[0]);
						} else {
							$in = 'n'; // Assume we do not know at first
						}
					}

					if ($in == 'n') {
						$this->out("Trying to expand $source_name");
						//$expanded = $this->in("Expand $expanded\t$league_name");
						// Auto Expand
						$potentialFullname = $this->SourceSportName->getFullname($source_name);
						if (empty($potentialFullname)) {
							break;
						} else {
							$words = explode(' ', $potentialFullname);
							$newsize = min(count($words), max(2, count($words) - 2));
							$new = array_splice($words, 0, $newsize);
							$expanded = implode(' ', $new);
						}
					}
				}

				if (!empty($in)) {
					if ($in != 'n') {
						if ($this->SourceSportName->setGivenName($this->sourceId, $source_name, $league_id, $in)) {
							$this->out("Saving $league_name\t$source_name\t$in");
						} else {
							$this->out('ERROR: Not Saved!');
						}
					} else {
						$this->out("Not saving $league_name\t$source_name");
						continue;
					}
				}
			}
		}
	}

	public function startup() {
		$source = array_lookup('type', $this->params, false);
		if (empty($source)) {
			$this->usage();
			exit;
		}
		$this->out("Using $source");
		$sourceId = $this->SourceType->contains($source);
		if (empty($sourceId)) {
			$this->out("Unable to find source id.");
			exit;
		}
		$this->sourceId = $sourceId;

		$this->nameParsers = array(
		    'MLB' => new NameParserMLB(),
		    'NFL' => new NameParserNFL(),
		    'NCAAF' => new NameParserNCAAF(),
		    'NCAAB' => new NameParserNCAAB(),
		    'NBA' => new NameParserNBA(),
		    'NHL' => new NameParserNHL()
		);
	}

	public function usage() {
		$this->out("-type <espn>");
	}
}
