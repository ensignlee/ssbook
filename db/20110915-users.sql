ALTER TABLE `users`  ADD `active` BOOLEAN NOT NULL DEFAULT '0' AFTER `email`;
UPDATE users SET `active` = '1';
