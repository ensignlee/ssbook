-- phpMyAdmin SQL Dump
-- version 3.3.7deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 07, 2010 at 07:06 PM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-1ubuntu9.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `sharpbettracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `league_types`
--

CREATE TABLE `league_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `odds`
--

CREATE TABLE `odds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scoreid` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `type` enum('moneyline','half_moneyline','spread','half_spread','total','half_total') NOT NULL,
  `spread_home` float DEFAULT NULL,
  `spread_visitor` float DEFAULT NULL,
  `total` float DEFAULT NULL,
  `odds_home` int(11) NOT NULL COMMENT 'over',
  `odds_visitor` int(11) NOT NULL COMMENT 'under',
  `sourceid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `source` (`sourceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_date` datetime NOT NULL,
  `sourceid` int(10) unsigned NOT NULL,
  `source_gameid` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'unique id possibly given by source',
  `home` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `visitor` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `league` smallint(5) unsigned NOT NULL,
  `home_score_half` smallint(6) DEFAULT NULL,
  `home_score_total` smallint(6) DEFAULT NULL,
  `visitor_score_half` smallint(6) DEFAULT NULL,
  `visitor_score_total` smallint(6) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_date` (`game_date`),
  KEY `source` (`sourceid`),
  KEY `league` (`league`),
  KEY `source_gameid` (`sourceid`,`source_gameid`,`league`,`game_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `source_types`
--

CREATE TABLE `source_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` char(40) CHARACTER SET latin1 DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_bets`
--

CREATE TABLE `user_bets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) unsigned NOT NULL,
  `scoreid` int(10) unsigned DEFAULT NULL,
  `game_date` datetime NOT NULL,
  `type` enum('moneyline','half_moneyline','second_moneyline','spread','half_spread','second_spread','total','half_total','second_total','other','parlay','teaser') NOT NULL,
  `direction` enum('home','visitor','over','under') DEFAULT NULL,
  `spread` float DEFAULT NULL,
  `odds` smallint(6) DEFAULT NULL,
  `risk` float unsigned DEFAULT NULL,
  `pt` enum('parlay','teaser') DEFAULT NULL,
  `parlayid` int(10) unsigned DEFAULT NULL,
  `sourceid` int(10) unsigned DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scoreid` (`scoreid`),
  KEY `userid` (`userid`),
  KEY `source` (`sourceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_bets_tags`
--

CREATE TABLE `user_bets_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_bets_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `odds`
--
ALTER TABLE `odds`
  ADD CONSTRAINT `odds_ibfk_1` FOREIGN KEY (`sourceid`) REFERENCES `source_types` (`id`);

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`sourceid`) REFERENCES `source_types` (`id`),
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`league`) REFERENCES `league_types` (`id`);

--
-- Constraints for table `user_bets`
--
ALTER TABLE `user_bets`
  ADD CONSTRAINT `user_bets_ibfk_6` FOREIGN KEY (`userid`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_bets_ibfk_7` FOREIGN KEY (`scoreid`) REFERENCES `scores` (`id`),
  ADD CONSTRAINT `user_bets_ibfk_8` FOREIGN KEY (`sourceid`) REFERENCES `source_types` (`id`);

