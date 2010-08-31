<?php

abstract class Winning_GameType {
	
	protected $winning;

	protected function __construct($winning) {
		$this->winning = $winning;
	}

	abstract public function process();

	public static function calcWin($risk, $odds) {
		if($odds > 0) {
			return $risk*$odds/100;
		} else {
			return $risk/$odds*-100;
		}
	}

	public static function getInstance($winning) {
		$bet = $winning->getBet();
echo "get instance";
		switch ($bet['type']) {
		case 'spread':
			return new Winning_Spread($winning);
		case 'parlay':
			return new Winning_Parlay($winning);
		default:
			throw new Exception("Unable to find type for {$bet['type']}");
		}
		return null;
	}

	public static function getMoney($winner, &$bet) {
		if (is_null($winner)) {
			return null;
		}
		if ($winner) {
			return Winning_GameType::calcWin($bet['risk'], $bet['odds']);
		} else {
			return -$bet['risk'];
		}
	}
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
echo "parlay";
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
			
}

class Winning_Spread extends Winning_GameType {

	protected function spreadCovered($game, $spread) {
		if (!is_null($game['home_score_total']) && !is_null($game['visitor_score_total'])) {
var_dump($game, $spread);
			return ($game['home_score_total'] + $spread) > $game['visitor_score_total'];
		}
		return null;
	}

	public function process() {
		$game = $this->winning->getGame();
		$bet = $this->winning->getBet();
		$winner = $this->spreadCovered($game, ($bet['direction'] == 'home') ? $bet['spread'] : -$bet['spread']);
		return Winning_GameType::getMoney($winner, $bet);
	}
}

class Winning {

	private $game;
	private $bet;
	private $winnings;

	public function __construct($game, $bet) {
echo "create game";
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
		return $this->winnings > 0;
	}
	
	public function process() {
		$gt = Winning_GameType::getInstance($this);

		$this->winnings = $gt->process();

		return $this->winnings;
	}
}
