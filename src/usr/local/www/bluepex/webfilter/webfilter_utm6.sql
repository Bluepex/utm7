-- MySQL dump 10.13  Distrib 5.7.38, for FreeBSD12.3 (amd64)
--
-- Host: localhost    Database: webfilter
-- ------------------------------------------------------
-- Server version	5.7.38

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `webfilter`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `webfilter` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `webfilter`;

--
-- Table structure for table `access_categories`
--

DROP TABLE IF EXISTS `access_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_categories` (
  `accesses_id` bigint(20) unsigned DEFAULT NULL,
  `categories_id` smallint(6) NOT NULL,
  `accesses_id_http` bigint(20) unsigned DEFAULT NULL,
  `accesses_id_https` bigint(20) unsigned DEFAULT NULL,
  KEY `fk_accesses_categories_accesses` (`accesses_id`),
  KEY `fk_accesses_categories_categories` (`categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accesses`
--

DROP TABLE IF EXISTS `accesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url_str` varchar(4096) NOT NULL,
  `url_path` varchar(4096) NOT NULL,
  `url_no_qry` varchar(4096) DEFAULT NULL,
  `categories` varchar(4096) NOT NULL,
  `elapsed_ms` int(11) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `time_date` (`time_date`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_url_str` (`url_str`(767)),
  KEY `idx_groupname` (`groupname`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4326 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_rule` varchar(4096) NOT NULL,
  `action` varchar(256) NOT NULL,
  `rule` varchar(4096) DEFAULT NULL,
  `classification` varchar(4096) NOT NULL,
  `priority` int(11) DEFAULT NULL,
  `protocol` varchar(256) DEFAULT NULL,
  `src_ip_port` varchar(256) NOT NULL,
  `dir` varchar(15) NOT NULL,
  `dst_ip_port` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3189647 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alerts_dash`
--

DROP TABLE IF EXISTS `alerts_dash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts_dash` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_rule` varchar(4096) NOT NULL,
  `action` varchar(256) NOT NULL,
  `rule` varchar(4096) DEFAULT NULL,
  `classification` varchar(4096) NOT NULL,
  `priority` int(11) DEFAULT NULL,
  `protocol` varchar(256) DEFAULT NULL,
  `src_ip_port` varchar(256) NOT NULL,
  `dir` varchar(15) NOT NULL,
  `dst_ip_port` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=38867711 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `block_country`
--

DROP TABLE IF EXISTS `block_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `block_country` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` smallint(6) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_reports_info`
--

DROP TABLE IF EXISTS `dataclick_reports_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_reports_info` (
  `last_generated_reports` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_10_accessed_sites`
--

DROP TABLE IF EXISTS `dataclick_top_10_accessed_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_10_accessed_sites` (
  `url` varchar(4096) NOT NULL,
  `total` bigint(8) NOT NULL,
  `blocked` tinyint(1) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_10_categories`
--

DROP TABLE IF EXISTS `dataclick_top_10_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_10_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `total` bigint(8) NOT NULL,
  `blocked` tinyint(1) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_10_consumed_sites`
--

DROP TABLE IF EXISTS `dataclick_top_10_consumed_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_10_consumed_sites` (
  `url` varchar(4096) NOT NULL,
  `total` bigint(8) NOT NULL,
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_10_social_networks`
--

DROP TABLE IF EXISTS `dataclick_top_10_social_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_10_social_networks` (
  `site` varchar(255) NOT NULL,
  `total` bigint(8) NOT NULL,
  `blocked` tinyint(1) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_10_users`
--

DROP TABLE IF EXISTS `dataclick_top_10_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_10_users` (
  `total` bigint(8) NOT NULL,
  `user` varchar(50) DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `type` varchar(8) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dataclick_top_access_social_networks`
--

DROP TABLE IF EXISTS `dataclick_top_access_social_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dataclick_top_access_social_networks` (
  `username` varchar(50) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `total` bigint(8) DEFAULT '0',
  `size_bytes` bigint(8) DEFAULT '0',
  `period` varchar(20) DEFAULT NULL,
  KEY `period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geoip2_location`
--

DROP TABLE IF EXISTS `geoip2_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geoip2_location` (
  `geoname_id` int(11) NOT NULL,
  `locale_code` text NOT NULL,
  `continent_code` text NOT NULL,
  `continent_name` text NOT NULL,
  `country_iso_code` text,
  `country_name` text,
  `is_in_european_union` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geoip2_network_ipv4`
--

DROP TABLE IF EXISTS `geoip2_network_ipv4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geoip2_network_ipv4` (
  `network` varbinary(16) NOT NULL,
  `geoname_id` int(11) DEFAULT NULL,
  `registered_country_geoname_id` int(11) DEFAULT NULL,
  `represented_country_geoname_id` int(11) DEFAULT NULL,
  `is_anonymous_proxy` tinyint(1) DEFAULT NULL,
  `is_satellite_provider` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geoip2_network_ipv6`
--

DROP TABLE IF EXISTS `geoip2_network_ipv6`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geoip2_network_ipv6` (
  `network` varbinary(16) NOT NULL,
  `geoname_id` int(11) DEFAULT NULL,
  `registered_country_geoname_id` int(11) DEFAULT NULL,
  `represented_country_geoname_id` int(11) DEFAULT NULL,
  `is_anonymous_proxy` tinyint(1) DEFAULT NULL,
  `is_satellite_provider` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `http`
--

DROP TABLE IF EXISTS `http`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `http` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url_str` varchar(4096) NOT NULL,
  `url_path` varchar(4096) NOT NULL,
  `url_no_qry` varchar(4096) DEFAULT NULL,
  `categories` varchar(4096) NOT NULL,
  `elapsed_ms` int(11) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_url_str` (`url_str`(767)),
  KEY `idx_groupname` (`groupname`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=395000 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `http_dash`
--

DROP TABLE IF EXISTS `http_dash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `http_dash` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url_str` varchar(4096) NOT NULL,
  `url_path` varchar(4096) NOT NULL,
  `url_no_qry` varchar(4096) DEFAULT NULL,
  `categories` varchar(4096) NOT NULL,
  `elapsed_ms` int(11) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_url_str` (`url_str`(767)),
  KEY `idx_groupname` (`groupname`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=342637 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `https`
--

DROP TABLE IF EXISTS `https`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `https` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url_str` varchar(4096) NOT NULL,
  `url_path` varchar(4096) NOT NULL,
  `url_no_qry` varchar(4096) DEFAULT NULL,
  `categories` varchar(4096) NOT NULL,
  `elapsed_ms` int(11) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_url_str` (`url_str`(767)),
  KEY `idx_groupname` (`groupname`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=241457 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `https_dash`
--

DROP TABLE IF EXISTS `https_dash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `https_dash` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url_str` varchar(4096) NOT NULL,
  `url_path` varchar(4096) NOT NULL,
  `url_no_qry` varchar(4096) DEFAULT NULL,
  `categories` varchar(4096) NOT NULL,
  `elapsed_ms` int(11) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_url_str` (`url_str`(767)),
  KEY `idx_groupname` (`groupname`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB AUTO_INCREMENT=342637 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `justification`
--

DROP TABLE IF EXISTS `justification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `justification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `reason` varchar(200) NOT NULL,
  `url_blocked` varchar(4096) NOT NULL,
  `time_date` datetime NOT NULL,
  `rejected` tinyint(1) DEFAULT '0',
  `proxy_instance_id` int(2) NOT NULL,
  `proxy_instance_name` varchar(80) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referers`
--

DROP TABLE IF EXISTS `referers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referers` (
  `id_referer` bigint(20) unsigned NOT NULL,
  `url_referer` varchar(4096) DEFAULT NULL,
  KEY `fk_refereres_accesses` (`id_referer`),
  CONSTRAINT `referers_ibfk_1` FOREIGN KEY (`id_referer`) REFERENCES `accesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reports_queue`
--

DROP TABLE IF EXISTS `reports_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports_queue` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(10) NOT NULL,
  `params` text,
  `process_id` int(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_title` varchar(50) NOT NULL,
  `timezone` varchar(100) NOT NULL,
  `recaptcha` varchar(5) NOT NULL,
  `theme` varchar(100) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'BluePex - Dataclick 2.0','America/Sao_Paulo','no','https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/cosmo/bootstrap.min.css','0','0');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

--
-- Table structure for table `sshd`
--

DROP TABLE IF EXISTS `sshd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sshd` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` varchar(4096) NOT NULL,
  `user` varchar(256) NOT NULL,
  `src_ip` varchar(256) NOT NULL,
  `port` varchar(256) DEFAULT NULL,
  `date` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `idx_time_date` (`time_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_maps`
--

DROP TABLE IF EXISTS `table_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_maps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(1024) DEFAULT NULL,
  `id_position` bigint(20) DEFAULT NULL,
  `time_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `user_id` int(10) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` varchar(10) NOT NULL,
  `password` text NOT NULL,
  `last_login` varchar(100) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `banned_users` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@bluepexutm.com.br','Admin','Admin','1','sha256:1000:UJxHaaFpM44Bj1ka7U58GiSHUx3zRWid:Hq86/PHYj0utJLz2ciHzSehsidHAZX+A','2023-02-22 09:19:26 PM','approved','unban'),(99,'dev@bluepexutm.com.br','Dev','Developer','1','sha256:1000:JDJ5JDEwJHNESE1HeHF6RWo0NEZ0UGVOUHZqRmVIUFFuN1prbHcyeDZseGguWXNFWkI0bUhWeDFtc29L:Njf5u1iRlUF4PL0MfSn6eJXyz1j9Ehvs','2023-02-22 09:19:26 PM','approved','unban');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

--
-- Table structure for table `utm`
--

DROP TABLE IF EXISTS `utm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol` varchar(8) NOT NULL,
  `host` varchar(15) NOT NULL,
  `port` int(5) DEFAULT NULL,
  `username` varchar(16) NOT NULL,
  `password` varchar(100) NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `logo_name` varchar(20) NOT NULL,
  `logo_content` text,
  `serial` varchar(5) NOT NULL,
  `product_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `version`
--

--
-- Dumping data for table `utm`
--

LOCK TABLES `utm` WRITE;
/*!40000 ALTER TABLE `utm` DISABLE KEYS */;
INSERT INTO `utm` VALUES (1,'http','192.168.1.1',80,'admin','b1uepextum',NULL,NULL,1,'UTM','0','0','0','0');
/*!40000 ALTER TABLE `utm` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

DROP TABLE IF EXISTS `version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `version` (
  `version` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'webfilter'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `dataclick_clearlog` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = latin1 */ ;;
/*!50003 SET character_set_results = latin1 */ ;;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`webfilter`@`localhost`*/ /*!50106 EVENT `dataclick_clearlog` ON SCHEDULE EVERY 1 DAY STARTS '2023-08-07 16:39:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
                    START TRANSACTION;
                    DELETE FROM accesses WHERE (time_date < DATE_SUB(max(time_date), INTERVAL 3 MONTH) );
                    DELETE FROM justification WHERE (time_date < DATE_SUB(max(time_date), INTERVAL 3 MONTH) );
                    OPTIMIZE TABLE accesses, access_categories, referers, justification;
            COMMIT;
        END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
/*!50106 DROP EVENT IF EXISTS `dataclick_generate_reports` */;;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = latin1 */ ;;
/*!50003 SET character_set_results = latin1 */ ;;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`webfilter`@`localhost`*/ /*!50106 EVENT `dataclick_generate_reports` ON SCHEDULE EVERY 1 DAY STARTS '2023-08-07 16:36:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
        call dataclick_summary_reports('all');
      END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

--
-- Dumping routines for database 'webfilter'
--
/*!50003 DROP PROCEDURE IF EXISTS `AddColumnIfNotExists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `AddColumnIfNotExists`(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext,
	IN fieldDef text)
BEGIN
	IF NOT EXISTS (
		SELECT * FROM information_schema.COLUMNS
		WHERE column_name=fieldName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('ALTER TABLE ',dbName,'.',tableName,
			' ADD COLUMN ',fieldName,' ',fieldDef);
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `AddIndexIfNotExists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `AddIndexIfNotExists`(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext,
	IN indexName tinytext)
BEGIN
	IF NOT EXISTS (
		SELECT * FROM information_schema.STATISTICS
		WHERE index_name=indexName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('CREATE INDEX ',indexName,' ON ',dbName,'.',tableName,
			' (',fieldName,')');
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `dataclick_summary_reports` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `dataclick_summary_reports`( IN reports_period char(20) )
BEGIN
        SET @current_date = (SELECT NOW());

        IF reports_period = "all" THEN
          SET @reports_period = "daily, weekly, monthly";
        ELSE
          SET @reports_period = reports_period;
        END IF;

        SET @separator = ", ";
        SET @separatorLen = LENGTH( @separator );

        SET AUTOCOMMIT=0;
        START TRANSACTION;

        CREATE TABLE IF NOT EXISTS dataclick_top_10_users (
          total bigint(8) not null,
          user varchar (50),
          ip varchar(15),
          type varchar(8),
          period varchar(20),
          INDEX (period)
        );

        CREATE TABLE IF NOT EXISTS dataclick_top_10_accessed_sites (
		  url varchar(4096) not null,
		  total bigint(8) not null,
          blocked tinyint(1),
          period varchar(20),
          INDEX (period)
        );

        CREATE TABLE IF NOT EXISTS dataclick_top_10_categories(
          category_id int(11) NOT NULL,
          category_name varchar(50) NOT NULL,
          total bigint(8) NOT NULL,
          blocked tinyint(1),
          period varchar(20),
          INDEX (period)
        );

        CREATE TABLE IF NOT EXISTS dataclick_top_10_consumed_sites(
          url varchar(4096) not null,
          total bigint(8) not null,
          period varchar(20),
          INDEX (period)
        );

        CREATE TABLE IF NOT EXISTS dataclick_top_10_social_networks (
          site varchar(255) NOT NULL,
          total bigint(8) NOT NULL,
          blocked tinyint(1),
          period varchar(20),
          INDEX (period)
        );

        CREATE TABLE IF NOT EXISTS dataclick_top_access_social_networks (
          username varchar(50) default NULL,
          ipaddress varchar(15),
          site varchar(255),
          total bigint(8) default 0,
          size_bytes bigint(8) default 0,
          period varchar(20),
          INDEX (period)
        );

        WHILE @reports_period != '' DO

          SET @report_period  = SUBSTRING_INDEX(@reports_period, @separator, 1);
          SET @reports_period = SUBSTRING( @reports_period, LENGTH(@report_period) + 1 + @separatorLen );

          IF @report_period = "daily" THEN
            SET @interval = CONCAT("'", (SELECT DATE_SUB(@current_date, INTERVAL 1 DAY)) , "' AND '", @current_date , "'");
          ELSEIF @report_period = "weekly" THEN
            SET @interval = CONCAT("'", (SELECT DATE_SUB(@current_date, INTERVAL 1 WEEK)) , "' AND '", @current_date , "'");
          ELSEIF @report_period = "monthly" THEN
            SET @interval = CONCAT("'", (SELECT DATE_SUB(@current_date, INTERVAL 1 MONTH)) , "' AND '", @current_date , "'");
          END IF;

          -- TOP 10 USERS --
          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_10_users WHERE period='", @report_period , "'");
          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_10_users (
            SELECT SUM(size_bytes) AS total, IF(username='-' or username='', ip, username) AS user, ip, IF(username='-' or username='', 'ip', 'username') AS type, '", @report_period , "'
            FROM accesses
            WHERE (time_date between ", @interval , ") AND blocked=0
            GROUP BY username, ip
            ORDER BY total DESC
          );");
          PREPARE stmtp FROM @strQueryDelete;
          PREPARE stmtp1 FROM @strQueryInsert;
          EXECUTE stmtp;
          EXECUTE stmtp1;

          -- TOP 10 ACCESSED SITES --
          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_10_accessed_sites WHERE period='", @report_period , "'");
          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_10_accessed_sites (
            SELECT url_str as url, count(url_str) AS total, blocked, '", @report_period , "'
            FROM accesses
            WHERE (time_date between ", @interval , ") AND (url_str <> '')
            GROUP BY url, blocked
            ORDER BY total DESC
          );");
          PREPARE stmtp FROM @strQueryDelete;
          PREPARE stmtp1 FROM @strQueryInsert;
          EXECUTE stmtp;
          EXECUTE stmtp1;

          -- TOP 10 CATEGORIES --
          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_10_categories WHERE period='", @report_period , "'");
          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_10_categories (
            SELECT c.id as category_id, c.description AS category, count(b.categories_id) AS total, a.blocked, '", @report_period , "'
            FROM accesses a
            JOIN access_categories b ON b.accesses_id=a.id LEFT JOIN categories c ON c.id=b.categories_id
            WHERE (a.time_date between ", @interval , ") AND b.categories_id not in (99, 0)
            GROUP BY c.id, a.blocked
            ORDER BY total DESC
          );");
          PREPARE stmtp FROM @strQueryDelete;
          PREPARE stmtp1 FROM @strQueryInsert;
          EXECUTE stmtp;
          EXECUTE stmtp1;

          -- TOP 10 COSUMMED SITES --
          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_10_consumed_sites WHERE period='", @report_period , "'");
          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_10_consumed_sites (
            SELECT url_str as url, SUM(size_bytes) AS total, '", @report_period , "'
            FROM accesses
            WHERE (time_date between ", @interval , ") AND blocked=0
            GROUP BY url_str
            ORDER BY total DESC
          );");
          PREPARE stmtp FROM @strQueryDelete;
          PREPARE stmtp1 FROM @strQueryInsert;
          EXECUTE stmtp;
          EXECUTE stmtp1;

          -- TOP 10 SOCIAL NETWORKS --
          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_10_social_networks WHERE period='", @report_period , "'");
          PREPARE stmtp FROM @strQueryDelete;
          EXECUTE stmtp;

          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_10_social_networks (
            SELECT (CASE WHEN url_str LIKE '%facebook.com%' THEN 'Facebook' WHEN url_str LIKE '%youtube.com%' THEN 'Youtube' WHEN url_str LIKE '%linkedin.com%' THEN 'Linkedin' WHEN url_str LIKE '%twitter.com%' THEN 'Twitter' WHEN url_str LIKE '%instagram.com%' THEN 'Instagram' WHEN (url_str LIKE '%whatsapp.com%' OR url_str LIKE '%whatsapp.net%') THEN 'Whatsapp Web' END) as site, COUNT(*), blocked, '", @report_period , "' FROM accesses WHERE (time_date BETWEEN ", @interval , ") AND (url_str LIKE '%facebook.com%' OR url_str LIKE '%linkedin.com%' OR url_str LIKE '%twitter.com%' OR url_str LIKE '%youtube.com%' OR url_str LIKE '%instagram.com%' OR (url_str LIKE '%whatsapp.com%' OR url_str LIKE '%whatsapp.net%')) GROUP by site, blocked)");
          PREPARE stmtp FROM @strQueryInsert;
          EXECUTE stmtp;

          SET @strQueryDelete = CONCAT("DELETE FROM dataclick_top_access_social_networks WHERE period='", @report_period , "'");
          PREPARE stmtp FROM @strQueryDelete;
          EXECUTE stmtp;

          SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_top_access_social_networks (
            SELECT username, ip, (CASE WHEN url_str LIKE '%facebook.com%' THEN 'Facebook' WHEN url_str LIKE '%youtube.com%' THEN 'Youtube' WHEN url_str LIKE '%linkedin.com%' THEN 'Linkedin' WHEN url_str LIKE '%twitter.com%' THEN 'Twitter' WHEN url_str LIKE '%instagram.com%' THEN 'Instagram' WHEN (url_str LIKE '%whatsapp.com%' OR url_str LIKE '%whatsapp.net%') THEN 'Whatsapp Web' END) as site, COUNT(*), SUM(size_bytes), '", @report_period , "' FROM accesses WHERE (time_date BETWEEN ", @interval , ") AND blocked=0 AND (url_str LIKE '%facebook.com%' OR url_str LIKE '%linkedin.com%' OR url_str LIKE '%twitter.com%' OR url_str LIKE '%youtube.com%' OR url_str LIKE '%instagram.com%' OR (url_str LIKE '%whatsapp.com%' OR url_str LIKE '%whatsapp.net%')) GROUP by site, username, ip)");
          PREPARE stmtp FROM @strQueryInsert;
          EXECUTE stmtp;

        END WHILE;

        CREATE TABLE IF NOT EXISTS dataclick_reports_info(
            last_generated_reports TIMESTAMP
        );

        SET @strQueryDelete = "DELETE FROM dataclick_reports_info";
        PREPARE stmtp FROM @strQueryDelete;
        EXECUTE stmtp;

        SET @strQueryInsert = CONCAT("
          INSERT INTO dataclick_reports_info (last_generated_reports) VALUES ('", @current_date ,"');
        ");
        PREPARE stmtp FROM @strQueryInsert;
        EXECUTE stmtp;

        COMMIT;
      END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `DropColumnIfExists` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `DropColumnIfExists`(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext)
BEGIN
	IF EXISTS (
		SELECT * FROM information_schema.COLUMNS
		WHERE column_name=fieldName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('ALTER TABLE ',dbName,'.',tableName,
			' DROP COLUMN ',fieldName);
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `rebuild_webfilter_db` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `rebuild_webfilter_db`()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE dbversion, check_exists, id_conn, id_access, url_id_access INT;
  DECLARE cur_access CURSOR FOR SELECT id, url_id FROM accesses WHERE url_id IS NOT NULL;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  SET dbversion = ( SELECT version FROM version );
  IF dbversion <= 4 THEN
   
   CREATE TABLE IF NOT EXISTS status_update (id_connection int(5));
   
   IF NOT EXISTS (SELECT ID FROM information_schema.PROCESSLIST WHERE ID=(SELECT id_connection FROM status_update)) THEN
     
     DELETE FROM status_update;
     INSERT status_update SET id_connection = (SELECT CONNECTION_ID());
     
     SET FOREIGN_KEY_CHECKS = 0;
     
     CALL AddColumnIfNotExists(DATABASE(),'accesses','url_path','VARCHAR(3072) DEFAULT ""');
     CALL AddColumnIfNotExists(DATABASE(),'groups','status','TINYINT(1) NOT NULL');
     
     CALL AddIndexIfNotExists(DATABASE(),'accesses','url_path','accesses_url_path_idx');
     OPEN cur_access;
     read_loop: LOOP
       FETCH cur_access INTO id_access, url_id_access;
       IF done THEN
         LEAVE read_loop;
       END IF;
       
       SET @update_access = CONCAT("UPDATE accesses SET url_path=(SELECT p.description from paths p INNER JOIN urls u ON u.path_id=p.id WHERE u.id=",url_id_access,") WHERE id=",id_access," and url_path = ''");
       PREPARE stmt FROM @update_access;
       EXECUTE stmt;
       DEALLOCATE PREPARE stmt;
     END LOOP;
     CLOSE cur_access;
     
     DROP TABLES IF EXISTS urls_nokeys, access_categories_nokeys, accesses_nokeys, access_log, referer_log, netfilter_log, hosts, queries, schemes, topsites, topsites_nokeys, paths, urls, status_update;  
     
     SET FOREIGN_KEY_CHECKS = 1;
     
     UPDATE version SET version = 5;
    END IF;
  END IF;
 END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `updatedb` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`webfilter`@`localhost` PROCEDURE `updatedb`()
BEGIN
  DECLARE dbversion BIGINT;
  IF(SELECT COUNT(version) FROM version) = 0 THEN
    INSERT INTO version VALUES(5);
  END IF;
  SET dbversion = ( SELECT version FROM version );
  IF dbversion = 0 THEN
    SET dbversion = 1;
    CALL AddColumnIfNotExists(DATABASE(),'accesses','url_str','VARCHAR(3072) NOT NULL');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','url_no_qry','VARCHAR(3072)');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','elapsed_ms','INTEGER');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','size_bytes','INTEGER');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','group_id','BIGINT');
    ALTER TABLE netfilter_log MODIFY category CHAR(16);
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 1 THEN
    SET dbversion = 2;
    CREATE TABLE IF NOT EXISTS categories (
        id SMALLINT NOT NULL PRIMARY KEY,
        description VARCHAR(100)
    ) ENGINE = InnoDB;
    INSERT IGNORE INTO categories(id,description) VALUES
    (1,"Pornografia"),
    (2,"Musica"),
    (3,"Video"),
    (4,"Livro"),
    (5,"Emprego"),
    (6,"Esporte"),
    (7,"Jogos"),
    (8,"Humor"),
    (9,"Ensino a dist�ncia"),
    (10,"Batepapo"),
    (11,"Jornal"),
    (12,"Revista"),
    (13,"Anima��es"),
    (14,"Tutoriais"),
    (15,"Classificados"),
    (16,"Namoro on-line"),
    (17,"Curiosidades"),
    (18,"Compras"),
    (19,"Noticias"),
    (20,"Cartoes Virtuais"),
    (21,"Esoterismo"),
    (22,"Webmail"),
    (25,"Quadrinhos"),
    (26,"Televis�o"),
    (27,"Culinaria"),
    (28,"Armas"),
    (29,"Leil�es"),
    (30,"Viagem"),
    (31,"Animais"),
    (32,"Hackers"),
    (33,"Filmes"),
    (34,"Fotografia"),
    (35,"Companhias Aereas"),
    (36,"Artes"),
    (37,"Carros"),
    (38,"Bancos"),
    (39,"Blogs"),
    (40,"Drogas"),
    (41,"Relacionamentos"),
    (42,"Saude"),
    (43,"Seitas e cultos"),
    (44,"Banner"),
    (45,"Proxy"),
    (46,"Sites de busca"),
    (47,"Violencia"),
    (48,"Portais"),
    (49,"Nazismo"),
    (50,"Downloads"),
    (99,"N�o categorizado");
    CALL DropColumnIfExists(DATABASE(),'accesses','category');
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 2 THEN
    SET dbversion = 3;
    
    
    
    RENAME TABLE urls TO urls_nokeys;
    RENAME TABLE accesses TO accesses_nokeys;
    RENAME TABLE topsites TO topsites_nokeys;
    RENAME TABLE access_categories TO access_categories_nokeys;
    CREATE TABLE IF NOT EXISTS urls (
        id          SERIAL NOT NULL PRIMARY KEY,
        scheme_id   BIGINT UNSIGNED NOT NULL,
        host_id     BIGINT UNSIGNED NOT NULL,
        path_id     BIGINT UNSIGNED NOT NULL,
        query_id    BIGINT UNSIGNED,
        FOREIGN KEY fk_urls_scheme (scheme_id) REFERENCES schemes(id),
        FOREIGN KEY fk_urls_host (host_id) REFERENCES hosts(id),
        FOREIGN KEY fk_urls_paths (path_id) REFERENCES paths(id),
        FOREIGN KEY fk_urls_queries (query_id) REFERENCES queries(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS accesses (
        id          SERIAL NOT NULL PRIMARY KEY,
        time_date   TIMESTAMP NOT NULL,
        url_id      BIGINT UNSIGNED,
        url_str     VARCHAR(3072) NOT NULL,
        url_no_qry  VARCHAR(3072),
        categories  VARCHAR(3072) NOT NULL,
        elapsed_ms  INTEGER,
        size_bytes  INTEGER,
        blocked     BOOLEAN NOT NULL,
        ip          VARCHAR(15) NOT NULL,
        username    VARCHAR(255),
        groupname   VARCHAR(255),
        group_id    BIGINT,
        FOREIGN KEY fk_accesses_urls (url_id) REFERENCES urls(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS topsites (
        id          SERIAL NOT NULL PRIMARY KEY,
        host_id     BIGINT UNSIGNED NOT NULL UNIQUE,
        hits        INTEGER NOT NULL,
        elapsed_ms  INTEGER NOT NULL,
        size_bytes  INTEGER NOT NULL,
        FOREIGN KEY fk_topsites_hosts (host_id) REFERENCES hosts(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS access_categories (
        accesses_id BIGINT UNSIGNED NOT NULL,
        categories_id SMALLINT NOT NULL,
        FOREIGN KEY fk_accesses_categories_accesses (accesses_id) REFERENCES accesses(id) ON DELETE CASCADE,
        FOREIGN KEY fk_accesses_categories_categories (categories_id) REFERENCES categories(id)
    ) ENGINE = InnoDB;
    ALTER TABLE urls DISABLE KEYS;
    INSERT INTO urls (id, scheme_id, host_id, path_id, query_id)
    SELECT id, scheme_id, host_id, path_id, query_id
    FROM urls_nokeys;
    ALTER TABLE urls ENABLE KEYS;
    ALTER TABLE accesses DISABLE KEYS;
    INSERT INTO accesses (id, time_date, url_id, url_str, url_no_qry, elapsed_ms, size_bytes, blocked, ip, username, groupname, group_id)
    SELECT id, time_date, url_id, url_str, url_no_qry, categories, elapsed_ms, size_bytes, blocked, ip, username, groupname, group_id
    FROM accesses_nokeys;
    ALTER TABLE accesses ENABLE KEYS;
    ALTER TABLE topsites DISABLE KEYS;
    INSERT INTO topsites (id, host_id, hits, elapsed_ms, size_bytes)
    SELECT id, host_id, hits, elapsed_ms, size_bytes
    FROM topsites_nokeys;
    ALTER TABLE topsites ENABLE KEYS;
    ALTER TABLE access_categories DISABLE KEYS;
    INSERT INTO access_categories (accesses_id, categories_id)
    SELECT accesses_id, categories_id
    FROM access_categories;
    ALTER TABLE access_categories ENABLE KEYS;
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 3 THEN
    SET dbversion = 4;
    ALTER TABLE access_categories DROP FOREIGN KEY `access_categories_ibfk_1`;
    ALTER TABLE access_categories ADD CONSTRAINT `access_categories_ibfk_1` FOREIGN KEY (`accesses_id`) REFERENCES `accesses` (`id`) ON DELETE CASCADE;
    CALL AddIndexIfNotExists(DATABASE(),'accesses','url_str','accesses_url_str_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','url_no_qry','accesses_url_no_qry_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','categories','accesses_categories_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','blocked','accesses_blocked_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','ip','accesses_ip_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','username','accesses_username_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','groupname','accesses_groupname_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','group_id','accesses_group_id_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','hits','topsites_hits_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','elapsed_ms','topsites_elapsed_ms_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','size_bytes','topsites_size_bytes_idx');
   
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','ip','netfilter_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','url','netfilter_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','time','netfilter_log_time');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','username','netfilter_log_username');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','ip','access_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','url','access_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','time','access_log_time');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','username','access_log_username');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','ip','referer_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','url','referer_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','time','referer_log_time');
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 4 THEN
    SET dbversion = 5;
    CREATE TABLE IF NOT EXISTS users (
      id          SERIAL NOT NULL PRIMARY KEY,
      name        VARCHAR(255) NOT NULL UNIQUE,
      description VARCHAR(255),
      status      TINYINT(1)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS justification (
      id int(11) NOT NULL AUTO_INCREMENT,
      username varchar(255) NOT NULL,
      ip varchar(15) NOT NULL,
      reason varchar(200) NOT NULL,
      url_blocked varchar(3072) NOT NULL,
      time_date datetime NOT NULL,
      rejected tinyint(1) DEFAULT 0,
      proxy_instance_id INT(2) NOT NULL,
      proxy_instance_name VARCHAR(80) NOT NULL,
      status tinyint(1) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB;
  END IF;
  IF dbversion <= 5 THEN
   CALL AddColumnIfNotExists(DATABASE(),'accesses','categories','VARCHAR(3072)');
   CREATE TABLE IF NOT EXISTS `referers` (
     `id_referer` bigint(20) unsigned NOT NULL,
     `url_referer` varchar(3072) DEFAULT NULL,
     KEY `fk_refereres_accesses` (`id_referer`),
     CONSTRAINT `referers_ibfk_1` FOREIGN KEY (`id_referer`) REFERENCES `accesses` (`id`) ON DELETE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    INSERT IGNORE INTO categories(id,description) VALUES (0,"Nao categorizado");
  END IF;
  IF dbversion >= 5 THEN
    
    CALL AddColumnIfNotExists(DATABASE(),'justification','rejected','TINYINT(1) DEFAULT 0');
    CALL AddColumnIfNotExists(DATABASE(),'justification','proxy_instance_id','INT(2) NOT NULL');
    CALL AddColumnIfNotExists(DATABASE(),'justification','proxy_instance_name','VARCHAR(80) NOT NULL');

    CALL AddIndexIfNotExists(DATABASE(),'accesses','time_date','idx_time_date');
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*INSERT INTO utm (id, protocol, host, port, username, password, is_default, name, logo_name, logo_content, serial, product_key) VALUES (1, 'http', '192.168.1.1', '80', 'admin', 'b1uepextum', 1, 'UTM', 0, 0, 0, 0);*/
/*INSERT INTO settings VALUES (1, 'BluePex - Dataclick 2.0', 'America/Sao_Paulo', 'no', 'https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/cosmo/bootstrap.min.css', '0', '0');*/
/*INSERT INTO users (id, email, first_name, last_name, role, password, last_login, status, banned_users) VALUES (1, 'admin@bluepexutm.com.br', 'Admin', 'Admin', '1', 'sha256:1000:UJxHaaFpM44Bj1ka7U58GiSHUx3zRWid:Hq86/PHYj0utJLz2ciHzSehsidHAZX+A', '2023-02-22 09:19:26 PM', 'approved', 'unban');*/
/*INSERT INTO users (id, email, first_name, last_name, role, password, last_login, status, banned_users) VALUES (99, 'dev@bluepexutm.com.br', 'Dev', 'Developer', '1', 'sha256:1000:JDJ5JDEwJHNESE1HeHF6RWo0NEZ0UGVOUHZqRmVIUFFuN1prbHcyeDZseGguWXNFWkI0bUhWeDFtc29L:Njf5u1iRlUF4PL0MfSn6eJXyz1j9Ehvs', '2023-02-22 09:19:26 PM', 'approved', 'unban');*/

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-08-14 15:26:07
