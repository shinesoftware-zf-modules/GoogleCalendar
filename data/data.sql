-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: Nov 06, 2014 alle 17:29
-- Versione del server: 5.5.40-0ubuntu0.14.04.1
-- Versione PHP: 5.5.16-1+deb.sury.org~precise+2

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `tango`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `googlecalendar_profiles`
--

DROP TABLE IF EXISTS `googlecalendar_profiles`;
CREATE TABLE IF NOT EXISTS `googlecalendar_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `parameter` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `createdat` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103 ;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `googlecalendar_profiles`
--
ALTER TABLE `googlecalendar_profiles`
  ADD CONSTRAINT `googlecalendar_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
