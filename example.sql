/*

Sample database to test the datagrid extension

*/

DROP DATABASE IF EXISTS `lmx_example`;
CREATE DATABASE `lmx_example` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `lmx_example`;

DROP TABLE IF EXISTS `test_table`;
CREATE TABLE `test_table` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `TestText` varchar(32) NOT NULL,
  `TestSelect2Single` int(11) NOT NULL,
  `TestDateTime` datetime NOT NULL,
  `TestPhoto` text NOT NULL,
  `TestSignature` text NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
