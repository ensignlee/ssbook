<?php

class RandomTipHelper extends AppHelper {
	public function get($name, $action) {
		$Tips = $this->getObject($name, $action);
		if (empty($Tips)) {
			return "";
		} else {
			return "<h4>Did you know</h4>".$Tips->getTip();
		}
	}
	private function getObject($name, $action) {
		switch ($name) {
		case 'Bets':
			switch ($action) {
			case 'index':
				return new EnterTips();
			case 'view':
				return new ViewTips();
			}
		}
		return null;
	}
}

abstract class Tips {
	abstract public function getTips();
	protected function getSize() {
		return count($this->getTips());
	}
	public function getTip() {
		$r = rand(0, $this->getSize()-1);
		$Tips = $this->getTips();
		return $Tips[$r];
	}
}

class EnterTips extends Tips {
	private static $Tips = array(
		'You can use the search bar to search for games by team names?',
		'You can type <b>NFL</b> before any team names to filter out games in other leagues?',
		'You can create parlays/teasers by selecting the games to parlay, entering in the spreads, and clicking <b>Parlay/Teaser?</b>',
		'You can click on the league names on the left to select games?',
		'You can delete the <b>odds</b> number, enter in a <b>risk</b> and <b>to win</b> amount, and the site will calculate your odds for you?'
	);
	public function getTips() {
		return self::$Tips;
	}
}

class ViewTips extends Tips {
	private static $Tips = array(
		'You can filter the results on the page by clicking on the column headers in your bet history?',
		"You can <b>tag</b> bets by checking off the bets to tag and clicking <b>Tag</b>? You can use tags to see which bets belong to which systems.",
		"You can <b>tag</b> bets by checking off the bets to tag and clicking <b>Tag</b>? You can use tags to see which bets you made following a someone else.",
		"You can <b>tag</b> bets by checking off the bets to tag and clicking <b>Tag</b>? You can use tags to add an attribute that isn't in the table.",
		"Clicking <b>Reset Filters</b> will clear all of your filters and show you all the bets you've recorded?",
		'When you click the link to share your page, it will display with the filters you have on currently?'
	);
	public function getTips() {
		return self::$Tips;
	}
}