<?php

abstract class Winning_GameType {
	
	protected $winning;

	protected function __construct($winning) {
		$this->winning = $winning;
	}

	public function process() {
		if (!$this->isGradeable()) {
			return null;
		}
		return $this->processGame();
	}

	protected function isGradeable() {
		$game = $this->winning->getGame();
		return (!is_null($game[$this->homeScore]) && !is_null($game[$this->visitorScore]));
	}
	
	abstract protected function processGame();

	public static function calcWin($risk, $odds) {
		if($odds > 0) {
			return $risk*$odds/100;
		} else {
			return $risk/$odds*-100;
		}
	}

	public static function getInstance($winning) {
		$bet = $winning->getBet();
		switch ($bet['type']) {
		case 'half_spread':
			return new Winning_Spread($winning);
		case 'half_total':
			return new Winning_Total($winning);
		case 'half_moneyline':
			return new Winning_MoneyLine($winning);
		case 'spread':
			return new Winning_Spread($winning);
		case 'total':
			return new Winning_Total($winning);
		case 'moneyline':
			return new Winning_MoneyLine($winning);
		case 'parlay':
			return new Winning_Parlay($winning);
		default:
			throw new Exception("Unable to find type for {$bet['type']}");
		}
		return null;
	}

	public static function getMoney($winner, &$bet) {
		if (is_null($winner)) {
			return 0;
		}
		if ($winner) {
			return Winning_GameType::calcWin($bet['risk'], $bet['odds']);
		} else {
			return -$bet['risk'];
		}
	}

	protected $homeScore = 'home_score_total';
	protected $visitorScore = 'visitor_score_total';
}


class Winning_Parlay extends Winning_GameType {
	public function process() {
		$bet = $this->winning->getBet();
		if (!is_array($bet['Parlay'])) {
			throw new Exception("Malformed Parlay");
		}
		$win = true;
		foreach ($bet['Parlay'] as $parlay) {
			$w = new Winning($parlay['Score'], $parlay['UserBet']);
			$isWin = $w->isWin();
			if (is_null($isWin)) {
				$win = null;
				break;
			} else {
				$win = $win && $isWin;
			}
		}
		return Winning_GameType::getMoney($win, $bet);
	}

	protected function processGame() {
		return $this->process();
	}
}

class Winning_Total extends Winning_GameType {

	protected function totalCovered($game, $over, $isOver) {
		if (($game[$this->homeScore] + $game[$this->visitorScore]) == $over) {
			return null;
		}	
		return $isOver == (($game[$this->homeScore] + $game[$this->visitorScore]) > $over);
	}

	protected function processGame() {
		$game = $this->winning->getGame();
		$bet = $this->winning->getBet();
		$winner = $this->totalCovered($game, $bet['spread'], $bet['direction'] == 'over');
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_MoneyLine extends Winning_GameType {

	protected function moneyCovered($game, $isHome) {
		if ($game[$this->homeScore] == $game[$this->visitorScore]) {
			return null;
		}
		return $isHome == ($game[$this->homeScore] > $game[$this->visitorScore]);
	}

	protected function processGame() {
		$game = $this->winning->getGame();
		$bet = $this->winning->getBet();
		$winner = $this->moneyCovered($game, $bet['direction'] == 'home');
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_Spread extends Winning_GameType {

	protected function spreadCovered($game, $spread) {
		if (($game[$this->homeScore] + $spread) == $game[$this->visitorScore]) {
			return null;
		}
		return ($game[$this->homeScore] + $spread) > $game[$this->visitorScore];
	}

	protected function processGame() {
		$game = $this->winning->getGame();
		$bet = $this->winning->getBet();
		$winner = $this->spreadCovered($game, ($bet['direction'] == 'home') ? $bet['spread'] : -$bet['spread']);
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_MoneyHalf extends Winning_MoneyLine {
	protected $homeScore = 'home_score_total_half';
	protected $visitorScore = 'visitor_score_total_half';
}

class Winning_TotalHalf extends Winning_Total {
	protected $homeScore = 'home_score_total_half';
	protected $visitorScore = 'visitor_score_total_half';
}

class Winning_SpreadHalf extends Winning_Spread {
	protected $homeScore = 'home_score_total_half';
	protected $visitorScore = 'visitor_score_total_half';
}

class Winning {

	private $game;
	private $bet;
	private $winnings;

	public function __construct($game, $bet) {
		$this->game = $game;
		$this->bet = $bet;
		$this->winnings = null;
	}

	public function getGame() {
		return $this->game;
	}

	public function getBet() {
		return $this->bet;
	}

	public function isWin() {
		if (is_null($this->winnings)) {
			$this->process();
		}
		if (is_null($this->winnings)) {
			return null;
		}
		return $this->winnings >= 0;
	}
	
	public function process() {
		$gt = Winning_GameType::getInstance($this);

		$this->winnings = $gt->process();

		return $this->winnings;
	}
}
