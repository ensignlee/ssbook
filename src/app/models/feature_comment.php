<?php

class FeatureComment extends AppModel {
	var $name = 'FeatureComment';
	var $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'userid',
			'type' => 'LEFT OUTER'
		)
	);
	
	public function getComments($fid) {
		if (empty($fid)) {
			return array();
		}
		return $this->find('all', array('conditions' => array('featureid' => $fid), 'order' => 'FeatureComment.created'));
	}
	
	public function addComment($fid, $userid, $comment) {
		$data = array(
		    'featureid' => $fid,
		    'userid' => $userid,
		    'comment' => $comment
		);
		return $this->save($data);
	}
}