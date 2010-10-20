<?php

require_once(dirname(__FILE__).'/../common.php');

App::import('Vendor', 'calculator/winning');
class vendor_calculatorWinningTest extends SSTest {

	private static function b($type, $direction, $risk, $odds, $spread) {
		return array(
			'type' => $type,
			'direction' => $direction,
			'risk' => $risk,
			'odds' => $odds,
			'spread' => $spread
		);
	}

	private static function g($ht, $hh, $vt, $vh) {
		return array(
			'home_score_half' => $hh,
			'home_score_total' => $ht,
			'visitor_score_half' => $vh,
			'visitor_score_total' => $vt
		);
	}

	/**
	 * @test
	 */
	public function testSpread() {
		$game = self::g(5, 2, 8, 4);

		$bet = self::b('spread', 'visitor', 110, -110, -5);
		$Winning = new Winning($game, $bet);
		$this->assertFalse($Winning->isWin());
		$this->assertEquals(-110, $Winning->process());
		$bet = self::b('spread', 'visitor', 110, -110, -2);
		$Winning = new Winning($game, $bet);
		$this->assertTrue($Winning->isWin());
		$this->assertEquals(100, $Winning->process());
		$bet = self::b('spread', 'home', 110, -110, 1);
		$Winning = new Winning($game, $bet);
		$this->assertFalse($Winning->isWin());
		$this->assertEquals(-110, $Winning->process());
		$bet = self::b('spread', 'home', 110, -110, 4);
		$Winning = new Winning($game, $bet);
		$this->assertTrue($Winning->isWin());
		$this->assertEquals(100, $Winning->process());
		$bet = self::b('spread', 'home', 110, -110, 3);
		$Winning = new Winning($game, $bet);
		$this->assertEquals(0, $Winning->process());
	}

	/**
	 * @test
	 */
	public function testSpecificSpread() {
		$game = self::g(13, 3, 20, 17);

		$bet = self::b('spread', 'visitor', 110, -110, 10);
		$Winning = new Winning($game, $bet);
		$this->assertTrue($Winning->isWin());
		$this->assertEquals(100, $Winning->process());
	}
}
