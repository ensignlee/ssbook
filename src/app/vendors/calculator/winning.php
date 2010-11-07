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
		$game = $this->getGame();
		//TODO : Is Parlay gradeable?
		if (is_null($game) || is_null($game['id'])) {
			$bet = $this->getBet();
			return $this->allGraded($bet['Parlay']);
		}
		return (!is_null($game['home_score_total']) && !is_null($game['visitor_score_total']));
	}
	
	private function allGraded($games) {
		foreach ($games as $game) {
			if (is_null($game['Score']['home_score_total']) || is_null($game['Score']['visitor_score_total'])) {
				return false;
			}
		}
		return true;
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
			return new Winning_SpreadHalf($winning);
		case 'half_total':
			return new Winning_TotalHalf($winning);
		case 'half_moneyline':
			return new Winning_MoneyHalf($winning);
		case 'second_spread':
			return new Winning_SpreadSecond($winning);
		case 'second_total':
			return new Winning_TotalSecond($winning);
		case 'second_moneyline':
			return new Winning_MoneySecond($winning);
		case 'spread':
			return new Winning_Spread($winning);
		case 'total':
			return new Winning_Total($winning);
		case 'moneyline':
			return new Winning_MoneyLine($winning);
		case 'parlay':
		case 'teaser':
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

	public function getBet() {
		return $this->winning->getBet();
	}

	public function getGame() {
		return $this->winning->getGame();
	}
	
	public function getHomeScore() {
		$game = $this->getGame();
		return $game[$this->homeScore];
	}

	public function getVisitorScore() {
		$game = $this->getGame();
		return $game[$this->visitorScore];
	}
}


class Winning_Parlay extends Winning_GameType {
	public function allParlays() {
		$bet = $this->winning->getBet();
		if (!is_array($bet['Parlay'])) {
			throw new Exception("Malformed Parlay");
		}
		$win = true;                
		foreach ($bet['Parlay'] as $parlay) {
			$parlay['UserBet']['risk'] = 100; //Simulate a bet on this game
			$w = new Winning($parlay['Score'], $parlay['UserBet']);
			$isWin = $w->process();
			if (is_null($isWin)) {
				$win = null;
				break;
			} else {
				$isWin = $isWin > 0;
				$win = $win && $isWin;
			}
		}
		return Winning_GameType::getMoney($win, $bet);
	}

	protected function processGame() {
		return $this->allParlays();
	}
}

class Winning_Total extends Winning_GameType {

	protected function totalCovered($over, $isOver) {
		if (($this->getHomeScore() + $this->getVisitorScore()) == $over) {
			return null;
		}	
		return $isOver == (($this->getHomeScore() + $this->getVisitorScore()) > $over);
	}

	protected function processGame() {
		$bet = $this->winning->getBet();
		$winner = $this->totalCovered($bet['spread'], $bet['direction'] == 'over');
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_MoneyLine extends Winning_GameType {

	protected function moneyCovered($isHome) {
		if ($this->getHomeScore() == $this->getVisitorScore()) {
			return null;
		}
		return $isHome == ($this->getHomeScore() > $this->getVisitorScore());
	}

	protected function processGame() {
		$bet = $this->winning->getBet();
		$winner = $this->moneyCovered($bet['direction'] == 'home');
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_Spread extends Winning_GameType {

	protected function spreadCovered($spread) {
		if (($this->getHomeScore() + $spread) == $this->getVisitorScore()) {
			return null;
		}
		return ($this->getHomeScore() + $spread) > $this->getVisitorScore();
	}

	protected function processGame() {
		$bet = $this->winning->getBet();
		$winner = $this->spreadCovered(($bet['direction'] == 'home') ? $bet['spread'] : -$bet['spread']);
		if (!is_null($winner) && $bet['direction'] == 'visitor') {
			$winner = !$winner;
		}
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning_MoneyHalf extends Winning_MoneyLine {
	protected $homeScore = 'home_score_half';
	protected $visitorScore = 'visitor_score_half';
}

class Winning_TotalHalf extends Winning_Total {
	protected $homeScore = 'home_score_half';
	protected $visitorScore = 'visitor_score_half';
}

class Winning_SpreadHalf extends Winning_Spread {
	protected $homeScore = 'home_score_half';
	protected $visitorScore = 'visitor_score_half';
}

class Winning_TotalSecond extends Winning_Total {
	public function getHomeScore() {
		$game = $this->getGame();
		return $game['home_score_total'] - $game['home_score_half'];
	}

	public function getVisitorScore() {
		$game = $this->getGame();
		return $game['visitor_score_total'] - $game['visitor_score_half'];
	}
}

class Winning_SpreadSecond extends Winning_Spread {
	public function getHomeScore() {
		$game = $this->getGame();
		return $game['home_score_total'] - $game['home_score_half'];
	}

	public function getVisitorScore() {
		$game = $this->getGame();
		return $game['visitor_score_total'] - $game['visitor_score_half'];
	}
}

class Winning_MoneySecond extends Winning_MoneyLine {
	public function getHomeScore() {
		$game = $this->getGame();
		return $game['home_score_total'] - $game['home_score_half'];
	}

	public function getVisitorScore() {
		$game = $this->getGame();
		return $game['visitor_score_total'] - $game['visitor_score_half'];
	}
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
