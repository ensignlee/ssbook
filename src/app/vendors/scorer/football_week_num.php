<?php
/**
 * Use this to consider a week 12pm wednesday to 12pm the next wednesday
 * Hopefully this will be helpful for keep football games from being
 * moved around randomly
 *
 * @author loyd
 */
 
class FootballWeekNum {

	public function weekNum($dateStr) {
		$d = strtotime($dateStr);
		$Y = date('Y', $d);
		$firstWednesday = date('z', strtotime("first wednesday $Y-01-01"));
		$doy = date('z', $d) - $firstWednesday;
		$dow = date('w', $d);
		$hod = date('G', $d);

		if (empty($d)) {
			return false;
		}
		$weekNum = floor($doy / 7);
		if ($dow == 3 && $hod <= 12) {
			$weekNum--;
		}
		return ($weekNum + 52) % 52; // wrap negative around
	}

	public function yearNum($dateStr) {
		$d = strtotime($dateStr);
		$Y = date('Y', $d);
		$firstWednesday = date('z', strtotime("first wednesday $Y-01-01"));
		$doy = date('z', $d);
		$hod = date('G', $d);

		if (empty($d)) {
			return false;
		}
		if ($doy < $firstWednesday || ($doy == $firstWednesday && $hod <= 12)) {
			$Y--;
		}
		return $Y;
	}

}
