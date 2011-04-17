ALTER TABLE  `scores` ADD  `homeExtra` VARCHAR( 250 ) NULL AFTER  `visitor`;
ALTER TABLE  `scores` ADD  `visitExtra` VARCHAR( 250 ) NULL AFTER  `homeExtra`;
DELETE FROM  `scores` WHERE  `game_date` >=  '2011-04-18 00:00:00' AND `league` = 1;