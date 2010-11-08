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
}
