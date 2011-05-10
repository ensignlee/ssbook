<?php

class FeatureVote extends AppModel {
	var $name = 'FeatureVote';
	
	public function getAllVotes($fids) {
		if (empty($fids)) {
			return array();
		}
		return $this->find('all', array(
		    'fields' => array('featureid', 'SUM(up) up','SUM(down) down'),
		    'group' => 'featureid',
		    'conditions' => array('featureid' => $fids)
		));
	}
	
	public function getVotesFromUser($userid) {
		if (empty($userid)) {
			return array();
		}
		$res = $this->find('all', array('conditions' => array('userid' => $userid)));
		if (empty($res)) {
			return array();
		}
		$ret = array();
		foreach ($res as $row) {
			$ret[$row['FeatureVote']['featureid']] = $row['FeatureVote']['up'];
		}
		return $ret;
	}
	
	public function alreadyVoted($fid, $userid) {
		$res = $this->find('all', array('conditions' => array('userid' => $userid, 'featureid' => $fid)));
		return !empty($res);
	}
	
	public function vote($fid, $userid, $up) {
		if (empty($fid) || empty($userid) || $this->alreadyVoted($fid, $userid)) {
			return false;
		}
		$data = array(
		    'featureid' => $fid,
		    'userid' => $userid,
		    'up' => $up ? 1 : 0,
		    'down' => $up ? 0 : 1
		);
		$this->save($data);
	}
}