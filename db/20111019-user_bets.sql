ALTER TABLE  `user_bets` DROP INDEX  `userid` ,
ADD INDEX  `userid` (  `userid` ,  `modified` )