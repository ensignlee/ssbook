<?php
require_once(dirname(__FILE__).'/../common.php');

App::import('Vendor', 'scorer/football_week_num');
class vendor_footballWeekNumTest extends SSTest {
	/**
	 * @test
	 */
	public function testWeekNum() {
		$this->assertEq(50, 2010, "2010-12-29 11:00:00");
		$this->assertEq(51, 2010, "2010-12-29 13:00:00");
		$this->assertEq(51, 2010, "2010-12-30 11:00:00");
		$this->assertEq(51, 2010, "2010-12-31 13:00:00");
		$this->assertEq(51, 2010, "2011-01-01 11:00:00");
		$this->assertEq(51, 2010, "2011-01-02 11:00:00");
		$this->assertEq(51, 2010, "2011-01-03 13:00:00");
		$this->assertEq(51, 2010, "2011-01-04 11:00:00");
		$this->assertEq(51, 2010, "2011-01-05 11:00:00");

		for ($i = 0; $i < 52; $i++) {
			$this->assertEq($i, 2011, "2011-01-05 13:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-06 11:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-07 13:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-08 11:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-09 13:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-10 11:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-11 13:00:00", $i*7);
			$this->assertEq($i, 2011, "2011-01-12 11:00:00", $i*7);
		}
	}

	private function assertEq($dow, $y, $datestr, $more=0) {
		$WN = new FootballWeekNum();
		$datestr = date("Y-m-d H:i:s", strtotime("$datestr + $more days"));

		$this->assertEquals($dow, $WN->weekNum($datestr), "Date is $datestr, + $more");
		$this->assertEquals($y, $WN->yearNum($datestr), "Date is $datestr, + $more");
	}
}