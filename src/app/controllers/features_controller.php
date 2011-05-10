<?php

class FeaturesController extends AppController {

	var $name = 'Features';
	var $uses = array(
	    'Feature',
	    'FeatureVote',
	    'FeatureComment'
	);

	public function beforeFilter() {
		$this->Auth->allow(array('index', 'info'));
		$this->Auth->autoRedirect = false;
	}
	
	public function vote($fid='', $up='') {
		$userid = $this->Auth->user('id');
		if ($this->Feature->existsAndActive($fid)) {
			$this->FeatureVote->vote($fid, $userid, $up==1);
		}
		$this->redirect('/features/index');
	}
	
	public function info($fid='') {
		if (empty($fid)) {
			$this->redirect('/features/index');
			return false;
		}
		$active = $this->Feature->existsAndActive($fid);
		$userid = $this->Auth->user('id');
		
		$this->Feature->id = $fid;
		$res = $this->Feature->read();
		if (empty($res)) {
			$this->redirect('/features/index');
			return false;
		}
		
		$comments = $this->FeatureComment->getComments($fid);
		$this->set('comments', $comments);
		
		$this->set('userid', $userid);
		$this->set('feature', $res);
		$this->set('active', $active);
	}
	
	public function comment($fid='') {
		if (empty($fid) || !$this->Feature->existsAndActive($fid)) {
			$this->redirect('/features/index');
			return false;
		}
		$comment = $this->data['FeatureComment']['comment'];
		if (!empty($comment)) {
			$userid = $this->Auth->user('id');
			$this->FeatureComment->addComment($fid, $userid, $comment);			
		}
		$this->redirect('/features/info/'.$fid);
	}
	
	public function index() {
		$userid = $this->Auth->user('id');
		$features = $this->Feature->getActive();
		$fids = array();
		foreach ($features as $row) {
			$fids[] = $row['Feature']['id'];
		}
		$featureVotes = $this->FeatureVote->getAllVotes($fids);
		
		//n^2 running time, but usually that number is like n=5 so 25. Which is like nothing
		foreach ($featureVotes as $row) {
			foreach ($features as &$feat) {
				if ($feat['Feature']['id'] == $row['FeatureVote']['featureid']) {
					$feat['FeatureVote'] = $row[0];
				}
			}
		}
		
		$userVotes = $this->FeatureVote->getVotesFromUser($userid);
		
		usort($features, array($this, '_sort_features'));
		
		$this->set('userid', $userid);
		$this->set('userVotes', $userVotes);
		$this->set('features', $features);
	}
	
	/**
	 * -1 => a < b
	 * 0  => a == b
	 * 1  => a > b 
	 * Where the greatest value should be at the top, and those with less than 10 votes also
	 * are at the top
	 * @param type $a
	 * @param type $b 
	 */
	private function _sort_features($a, $b) {
		$votesA = empty($a['FeatureVote']) ? array('up' => 0, 'down' => 0) : $a['FeatureVote'];
		$votesB = empty($b['FeatureVote']) ? array('up' => 0, 'down' => 0) : $b['FeatureVote'];
		$sumA = $votesA['up'] + $votesA['down'];
		$sumB = $votesB['up'] + $votesB['down'];
		$perA = $this->_div($votesA['up'], $sumA);
		$perB = $this->_div($votesB['up'], $sumB);
		return $this->_sort_features_help($sumA, $sumB, $perA, $perB);
	}
	
	/**
	 * @VisibleForTesting
	 */
	public function _sort_features_help($sumA, $sumB, $perA, $perB) {
		if ($sumA < 10 && $sumB > 10) {
			return -1;
		}
		if ($sumA > 10 && $sumB < 10) {
			return 1;
		}
		
		// assuming that both are >= 10 or both are < 10. Either way its the same logic
		if ($perA == $perB) {
			if ($sumA == $sumB) {
				return 0;
			}
			return $sumA > $sumB ? -1 : 1;
		}
		return $perA > $perB ? -1 : 1;
	}
}
