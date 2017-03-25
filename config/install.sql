SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `tngs_notification_input` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `input` text NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tngs_notification_log` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tngs_notification_msgqueue` (
  `tmid` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tngs_notification_news` (
  `idx` int(11) NOT NULL,
  `date` date NOT NULL,
  `text` text NOT NULL,
  `department` varchar(10) NOT NULL,
  `type` varchar(10) NOT NULL,
  `url` varchar(255) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fbpost` tinyint(1) NOT NULL DEFAULT '0',
  `fbmessage` tinyint(1) NOT NULL DEFAULT '0',
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tngs_notification_user` (
  `uid` varchar(255) NOT NULL,
  `tmid` varchar(255) NOT NULL,
  `sid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `fbmessage` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `tngs_notification_input`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `tngs_notification_log`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `tngs_notification_msgqueue`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `tngs_notification_news`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `tngs_notification_user`
  ADD UNIQUE KEY `tmid` (`tmid`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
