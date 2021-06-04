-- Adminer 4.7.8 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `BuildQueue`;
CREATE TABLE `BuildQueue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `search` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `cancelled` bit(1) NOT NULL,
  `newestAvailablePost` bigint(20) NOT NULL,
  `oldestAvailablePost` bigint(20) NOT NULL,
  `newestUnavailablePost` bigint(20) NOT NULL,
  `oldestUnavailablePost` bigint(20) NOT NULL,
  `availableFound` int(11) DEFAULT NULL,
  `unavailableFound` int(11) DEFAULT NULL,
  `createdTimestamp` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `DownloadQueue`;
CREATE TABLE `DownloadQueue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buildQueueId` int(11) NOT NULL,
  `pauseState` json DEFAULT NULL,
  `complete` bit(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `buildQueueId` (`buildQueueId`),
  CONSTRAINT `DownloadQueue_ibfk_5` FOREIGN KEY (`buildQueueId`) REFERENCES `BuildQueue` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `EventLog`;
CREATE TABLE `EventLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` bigint(20) NOT NULL,
  `application` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `ip` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  CONSTRAINT `EventLog_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `FailedDownloads`;
CREATE TABLE `FailedDownloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` json NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Favorites`;
CREATE TABLE `Favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `itemId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `itemId` (`itemId`),
  CONSTRAINT `Favorites_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `Users` (`id`),
  CONSTRAINT `Favorites_ibfk_2` FOREIGN KEY (`itemId`) REFERENCES `Items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `ItemMetadata`;
CREATE TABLE `ItemMetadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemId` int(11) NOT NULL,
  `key` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `itemId` (`itemId`),
  CONSTRAINT `ItemMetadata_ibfk_2` FOREIGN KEY (`itemId`) REFERENCES `Items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Items`;
CREATE TABLE `Items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `md5Checksum` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `storageLocation` longtext COLLATE utf8mb4_unicode_ci,
  `previewStorageLocation` longtext COLLATE utf8mb4_unicode_ci,
  `createdTimestamp` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `md5Checksum` (`md5Checksum`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `ItemTagMappings`;
CREATE TABLE `ItemTagMappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `itemId` (`itemId`),
  KEY `tagId` (`tagId`),
  CONSTRAINT `ItemTagMappings_ibfk_3` FOREIGN KEY (`itemId`) REFERENCES `Items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ItemTagMappings_ibfk_4` FOREIGN KEY (`tagId`) REFERENCES `Tags` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Permissions`;
CREATE TABLE `Permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `permission` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  CONSTRAINT `Permissions_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Tags`;
CREATE TABLE `Tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`(256))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayName` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastLoginTimestamp` bigint(20) DEFAULT NULL,
  `lastLoginIp` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2021-01-20 17:53:00
