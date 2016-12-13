DROP TABLE IF EXISTS `banned_hdd`;
CREATE TABLE `banned_hdd` (
  `uniId` int(10) NOT NULL AUTO_INCREMENT,
  `hdd_serial` varchar(50) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `details` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uniId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;