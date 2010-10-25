-- phpMyAdmin SQL Dump
-- version 2.11.1.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 10, 2010 at 10:04 PM
-- Server version: 5.0.45
-- PHP Version: 5.2.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `ssdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `league_types`
--

CREATE TABLE `league_types` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(250) character set utf8 collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `odds`
--

CREATE TABLE `odds` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `scoreid` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `type` enum('moneyline','half_moneyline','spread','half_spread','total','half_total') NOT NULL,
  `spread_home` float default NULL,
  `spread_visitor` float default NULL,
  `total` float default NULL,
  `odds_home` int(11) NOT NULL COMMENT 'over',
  `odds_visitor` int(11) NOT NULL COMMENT 'under',
  `sourceid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `source` (`sourceid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `game_date` datetime NOT NULL,
  `sourceid` int(10) unsigned NOT NULL,
  `source_gameid` int(11) default NULL COMMENT 'unique id possibly given by source',
  `home` varchar(250) collate utf8_unicode_ci NOT NULL,
  `visitor` varchar(250) collate utf8_unicode_ci NOT NULL,
  `league` smallint(5) unsigned NOT NULL,
  `home_score_half` smallint(6) default NULL,
  `home_score_total` smallint(6) default NULL,
  `visitor_score_half` smallint(6) default NULL,
  `visitor_score_total` smallint(6) default NULL,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
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
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(250) character set utf8 collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `username` varchar(50) collate utf8_unicode_ci default NULL,
  `password` char(40) character set latin1 default NULL,
  `first_name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `last_name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `email` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_bets`
--

CREATE TABLE `user_bets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` mediumint(8) unsigned NOT NULL,
  `scoreid` int(10) unsigned default NULL,
  `game_date` datetime NOT NULL,
  `type` enum('moneyline','half_moneyline','second_moneyline','spread','half_spread','second_spread','total','half_total','second_total','other','parlay','teaser') NOT NULL,
  `direction` enum('home','visitor','over','under') default NULL,
  `spread` float default NULL,
  `odds` smallint(6) default NULL,
  `risk` float unsigned default NULL,
  `pt` enum('parlay','teaser') default NULL,
  `parlayid` int(10) unsigned default NULL,
  `sourceid` int(10) unsigned default NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `scoreid` (`scoreid`),
  KEY `userid` (`userid`),
  KEY `source` (`sourceid`)
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

