<?php

App::import('Core', 'Controller'); 
App::import('Component', 'Email');
App::import('Component','Auth');

class MigrateShell extends Shell {
	var $uses = array(
	    'Score',
	    'UserBet',
	    'SourceType',
	    'LeagueType',
	    'User'
	);

	private $official = false;
	private $ss = null;
	private $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

	private $typeidToType = array(
		1 => 'spread',
		2 => 'total',
		3 => 'moneyline',
		4 => 'half_spread',
		5 => 'half_total'
	);

	private $typeidToTable = array(
		1 => 'lineSpread',
		2 => 'lineTotal',
		3 => 'lineOdds',
		4 => 'lineSpread',
		5 => 'lineTotal'
	);

	public function main() {
		$recentUserIds = $this->getRecentUserIds();
		$users = $this->lookupUserInfo($recentUserIds);
		foreach ($users as $user) {
			$this->migrate($user);
		}
		$inactiveUsers = $this->lookupOtherUserInfo($recentUserIds);
		foreach ($inactiveUsers as $user) {
			$this->sendMigrationEmail($user['email'], $user['username']);
		}
	}

	private function getPass() {
		$pass = "";
		for ($i = 0; $i < 8; $i++) {
			$j = rand(0, strlen($this->chars)-1);
			$c = $this->chars{$j};
			$pass .= $c;
		}
		return $pass;
	}

	private function saveBet($bet, $userid) {
		$game_date = date('Y-m-d H:i:s', $bet['game']['timebegin']);
		$score = array(
			'game_date' => $game_date,
			'source_gameid' => 'ss'.$bet['id'],
			'home' => $bet['game']['homeTeamName'],
			'visitor' => $bet['game']['visitorTeamName'],
			'home_score_half' => $bet['game']['halfHomeScore'],
			'home_score_total' => $bet['game']['homeScore'],
			'visitor_score_half' => $bet['game']['halfVisitorScore'],
			'visitor_score_total' => $bet['game']['visitorScore'],
			'sourceid' => $this->sourceid,
			'league' => $this->getLeague($bet['game']['league'])
		);
		$scoreid = $this->saveSBTScore($score);

		$userbet = array(
			'scoreid' => $scoreid,
			'userid' => $userid,
			'game_date'=> $game_date,
			'type' => $bet['type'],
			'direction' => $bet['odds']['direction'],
			'sourceid' => $this->sourceid,
			'spread' => (strpos($bet['type'], 'total') !== false) ? $bet['odds']['total'] : ($bet['home'] == 1 ? $bet['odds']['spread_home'] : $bet['odds']['spread_visitor']),
			'odds' => $bet['home'] == 1 ? $bet['odds']['odds_home'] : $bet['odds']['odds_visitor'],
			'risk' => $bet['qty']
		);
		$this->saveSBTUserBet($userbet);
	}

	private function saveSBTUserBet($userbet) {
		if ($this->official) {
			$this->UserBet->create();
			if (!$this->UserBet->save($userbet)) {
				throw new Exception("Unable to save userbet ".json_encode($userbet));
			}
		} else {
			echo "save userbet ".json_encode($userbet)."\n";
			return 1;
		}
		return $this->UserBet->id;
	}

	private function getLeague($league) {
		return $this->LeagueType->getOrSet($league);
	}

	private function saveSBTScore($score) {
		if ($this->official) {
			$this->Score->create();
			if (!$this->Score->save($score)) {
				throw new Exception("Unable to save score ".json_encode($score));
			}
		} else {
			echo "save score".json_encode($score)."\n";
			return 1;
		}
		return $this->Score->id;
	}

	private function migrate($user) {
		$password = $this->getPass();
		$this->log("password for {$user['username']} {$user['email']} = $password", 'info');
		$userid = $this->createUser($user, $password);
		if (empty($userid)) {
			$this->log("user already exists, not importing bets", 'info');
			return false;
		}

		$bets = $this->getUserBets($user['id']);
		foreach ($bets as &$bet) {
			$this->fillInBet($bet);
		}
		unset($bet);
		$this->log('Transferring '.count($bets).' bets', 'info');

		foreach ($bets as $bet) {
			$this->saveBet($bet, $userid);
		}
		
		$this->sendMigrationEmail($user['email'], $user['username'], $password);
	}
	
	private function sendMigrationEmail($email, $username, $password='') {
		$this->Email->from = 'Edmund Lee<edmund@sharpbettracker.com>';
		$this->Email->to = $email;
		$this->Email->subject = 'Thank you for using SageStats - Changes made for you!';
		
		if (!empty($password)) {
			$this->log('Sending active message to '.$username, 'info');
			$message = $this->getActiveMessage($username, $password);
		} else {
			$this->log('Sending inactive message to '.$username, 'info');
			$message = $this->getInactiveMessage($username);
		}
		
		if (false) {
			$this->Email->send($message);
			sleep(1);
		} else {
			echo "send email ".json_encode(array('from'=>$this->Email->from, 'to'=>$this->Email->to, 'subject'=>$this->Email->subject,'message'=>$message));
		}
	}
	
	private function getInactiveMessage($username) {
		return <<<EOD
Dear $username,     
       
Thank you for using SageStats. Based on comments and feedback from users like you, we've created a brand new platform to make using the site easier, including:

*A MUCH easier way to enter your bets (completely redesigned!)
*Faster grading for your wagers
*Improved filtering to better analyze your wagers

You can also record new types of wagers on the new platform, including:
*Past bets (so your history doesn't get screwed up when you forget one bet)
*Parlays/Teasers
*1st Half / 2nd Half Bets
*NHL Hockey

You can access the new platform at http://www.sharpbettracker.com/ instead of http://www.sagestats.com/ . Simply create a new user account and you're good to go!

We think you'll find the new user interface a lot more attractive, a lot more useful, and most importantly, *A LOT EASIER TO USE*. We look forward to seeing you there!

Sincerely,

Edmund Lee

P.S. If you want to keep your previous account history, please email me at edmund@sharpbettracker.com with your previous username and we'll take care of it for you!
EOD;
	}
	
	private function getActiveMessage($username, $password) {
		return <<<EOD
Dear $username,
 
Thank you for using SageStats. Based on comments and feedback from users like you, we've created a brand new platform to make using the site easier, including:

*An easier way to enter your bets
*Faster grading for your wagers
*Improved filtering to better analyze your wagers

You can also record new types of wagers on the new platform, including:
*Past bets (so your history doesn't get screwed up when you forget one bet)
*Parlays/Teasers
*1st Half / 2nd Half Bets
*NHL Hockey

You can access the new platform at http://www.sharpbettracker.com/ instead of http://www.sagestats.com/ . We've kept your account history intact, so all you have to do is log in using your SageStats login and you'll be up and running immediately!

We think you'll find the new user interface a lot more attractive, a lot more useful, and most importantly, *A LOT EASIER TO USE*. We look forward to seeing you there!

Sincerely,

Edmund Lee
SharpBetTracker.com

P.S. One snag though...since we encrypt your passwords, we don't know what they are and so we couldn't move your passwords over as well. We've given you a temporary password for now: $password  Once you login, you can click on the "Profile" button on the top right to change your password to whatever you'd like.
EOD;
	}

	private function createUser($user, $password) {
		$password = $this->Auth->password($password);
		$appuser = $this->User->find('first', array('conditions'=>array('username'=>$user['username'])));
		if (!empty($appuser)) {
			return false;
		}
		$user['password'] = $password;
		if ($this->official) {
			$this->User->create();
			if (!$this->User->save($user)) {
				$this->log('Unable to save user '.json_encode($user));
				return false;
			} else {
				$id = $this->User->id;
				$this->log('Created '.$user['username'].' width id='.$id, 'info');
				return $id;
			}
		} else {
			echo "save user ".json_encode($user)."\n";
			return 1;
		}
		$this->log('Never should get here');
		return false;
	}

	private function fillInBet(&$bet) {
		$gameid = $bet['gameid'];
		$sql = "select g.*,snh.descr league,concat(snh.city,' ',snh.name) homeTeamName, concat(snv.city,' ',snv.name) visitorTeamName from game g join sport_names snv on (g.visitorTeam = snv.id) join sport_names snh on (g.homeTeam = snh.id) where g.id = $gameid;";
		$res = $this->ss->query($sql);
		$row = $res->fetch_assoc();
		$bet['game'] = $row;

		$func = "fillIn".$this->typeidToTable[$bet['typeid']];
		$this->{$func}(&$bet, $bet['home'] == 1);
	}

	private function fillInlineSpread(&$bet, $home) {
		$betid = $bet['lineid'];
		$sql = "select homeSpread spread_home, visitorSpread spread_visitor, NULL total, homeOdds odds_home, visitorOdds odds_visitor from lineSpread where id = $betid";
		$res = $this->ss->query($sql);
		$row = $res->fetch_assoc();
		$row['direction'] = $home ? 'home' : 'visitor';
		$row['type'] = 'spread';
		$bet['odds'] = $row;
	}
	// home=1 is over
	private function fillInlineTotal(&$bet, $home) {
                $betid = $bet['lineid'];
                $sql = "select NULL spread_home, NULL spread_visitor, total, overOdds odds_home, underOdds odds_visitor from lineTotal where id = $betid";
                $res = $this->ss->query($sql);
                $row = $res->fetch_assoc();
                $row['direction'] = $home ? 'over' : 'under';
                $row['type'] = 'total';
                $bet['odds'] = $row;
	}
	private function fillInlineOdds(&$bet, $home) {
                $betid = $bet['lineid'];
                $sql = "select NULL spread_home, NULL spread_visitor, NULL total, homeOdds odds_home, visitorOdds odds_visitor from lineOdds where id = $betid";
                $res = $this->ss->query($sql);
                $row = $res->fetch_assoc();
                $row['direction'] = $home ? 'home' : 'visitor';
                $row['type'] = 'moneyline';
                $bet['odds'] = $row;
	}

	private function getUserBets($userid) {
		$res = $this->ss->query("select * from userbet where userid = $userid");
		$rows = array();
		while (($row = $res->fetch_assoc()) !== null) {
			if (empty($this->typeidToType[$row['typeid']])) {
				throw new Exception("unable to find type of typeid {$row['typeid']}");
			} else {
				$row['type'] = $this->typeidToType[$row['typeid']];
			}
			$rows[] = $row;
		}
		return $rows;
	}

	private function getRecentUserIds() {
		$enterTime = strtotime('-2 months');
		$res = $this->ss->query("select distinct(userid) userid from userbet where enterTime >= $enterTime");
		$ids = array();
		while (($row = $res->fetch_assoc()) !== null) {
			$ids[] = $row['userid'];
		}
		return $ids;			
	}

	private function lookupUserInfo($ids) {
		$res = $this->ss->query("select id,username,email from user where id in (".implode(',',$ids).")");
		$rows = array();
		while (($row = $res->fetch_assoc()) !== null) {
			$rows[] = $row;
		}
		return $rows;
	}
	
	private function lookupOtherUserInfo($ids) {
		$res = $this->ss->query("select id,username,email from user where id not in (".implode(',',$ids).")");
		$rows = array();
		while (($row = $res->fetch_assoc()) !== null) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function startup() {
		$go = isset($this->params['go']);
		$dry = isset($this->params['dry']);
		if (!($go ^ $dry)) {
			$this->usage();
			exit;
		}
		$this->official = $go;
		$this->ss = new mysqli('localhost', 'root', '', 'sagestats');
		$this->sourceid = $this->SourceType->getOrSet('SageStats');
		
		$this->Auth = new AuthComponent(null);
		$this->Controller = new Controller(); 
		$this->Email = new EmailComponent(null); 
		$this->Email->startup($this->Controller); 
	}

	public function usage() {
		$this->out("-dry|-go");
	}
}
