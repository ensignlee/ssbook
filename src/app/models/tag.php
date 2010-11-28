<?php

class Tag extends AppModel {
	var $name = 'Tag';
	var $hasAndBelongsToMany = array(
		'UserBet' =>
		    array(
			'className'              => 'UserBet',
			'joinTable'              => 'user_bets_tags',
			'foreignKey'             => 'user_bets_id',
			'associationForeignKey'  => 'tag_id',
			'unique'                 => false));

	public function saveBetsWithTag($tagname, $ids) {
		$tag = $this->findByName($tagname);
		if (empty($tag)) {
			$this->save(array('name' => $tagname));
			$tagid = $this->id;
		} else {
			$tagid = $tag['Tag']['id'];
		}
		foreach ($ids as $id) {
			$this->UserBetsTag->create();
			$this->UserBetsTag->save(array('user_bets_id' => $id, 'tag_id' => $tagid));
		}
		return true;
	}
}
