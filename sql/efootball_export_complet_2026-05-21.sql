-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: efootball
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `efootball`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `efootball` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `efootball`;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `platform` varchar(50) DEFAULT 'Multi',
  `delivery_time` varchar(100) DEFAULT '5 minutes',
  `binding_status` varchar(255) DEFAULT 'Lié à un email',
  `product_status` varchar(20) NOT NULL DEFAULT 'available',
  `approval_status` varchar(20) NOT NULL DEFAULT 'approved',
  `seller_note` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery_images`)),
  `why_choose_us` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`why_choose_us`)),
  `author_username` varchar(100) NOT NULL,
  `author_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `price`, `platform`, `delivery_time`, `binding_status`, `product_status`, `approval_status`, `seller_note`, `image`, `gallery_images`, `why_choose_us`, `author_username`, `author_user_id`, `created_at`, `updated_at`) VALUES (3,'PUISSANCE 3160','puissance-3160','COMPTE AVEC DES JOUEURS EPICS TEL QUE MESSI 2015, LAUTARO 2021 EPIC , NEDVED 103     ',15000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'6c57d36bddb77ded.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:54:13','2026-05-03 19:53:38'),(4,'PUISSANCE 3220','puissance-3220','COMPTE AVEC DES JOUEURS EPICS TEL QUE SALAH BLITZ CURLER , MESSI 2015',55000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'d15d49d8c5af18fc.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:55:55','2026-05-03 18:25:08'),(5,'PUISSANCE 3178','puissance-3178','COMPTE AVEC DES JOUEURS EPICS TEL QUE GULLIT 105 , KAHN 106 , CR7 GOAL POACHER',28000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'f24f12c4efe44be6.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:58:55','2026-05-09 11:23:30'),(6,'PUISSANCE 3197','puissance-3197','COMPTE AVEC DES EPIC TEL QUE MBAPPE BLITZ CURLER , BALE 2018',45000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'b04c56310c1fb86c.jpg','[\"b04c56310c1fb86c.jpg\",\"dbdd576ed37b8c73.jpg\"]',NULL,'khalil11',NULL,'2025-10-19 14:13:36','2026-05-01 00:15:18');
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `timestamp`) VALUES (40,3,1,'sallut',0,'2025-10-30 12:57:45'),(41,3,1,'sallut',0,'2025-10-30 12:57:45'),(42,3,1,'sallut',0,'2025-10-30 12:57:45'),(43,3,1,'sallut',0,'2025-10-30 12:57:45'),(44,3,1,'sallut',0,'2025-10-30 12:57:46'),(45,3,1,'sallut',0,'2025-10-30 12:57:46'),(46,3,1,'sallut',0,'2025-10-30 12:57:46'),(47,3,1,'sallut',0,'2025-10-30 12:57:46'),(48,3,1,'sallut',0,'2025-10-30 12:57:46'),(49,3,1,'salut',0,'2025-10-30 12:58:18'),(50,3,1,'salut',0,'2025-10-30 12:58:18'),(51,3,1,'salut',0,'2025-10-30 12:58:18'),(52,3,1,'salut',0,'2025-10-30 12:58:19'),(53,3,1,'salut',0,'2025-10-30 12:58:19'),(54,3,1,'salut',0,'2025-10-30 12:58:19'),(55,3,1,'sallut',0,'2025-10-30 13:00:55'),(56,3,1,'sallut',0,'2025-10-30 13:00:55'),(57,3,1,'sallut',0,'2025-10-30 13:00:55'),(58,3,1,'sallut',0,'2025-10-30 13:00:56'),(59,3,1,'sallut',0,'2025-10-30 13:00:56'),(60,3,1,'sallut',0,'2025-10-30 13:00:56'),(61,3,1,'sallut',0,'2025-10-30 13:00:56'),(62,3,1,'sallut',0,'2025-10-30 13:00:56'),(63,3,1,'sallut',0,'2025-10-30 13:01:10'),(64,3,1,'sallut',0,'2025-10-30 13:01:10'),(65,3,1,'sallut',0,'2025-10-30 13:03:56'),(66,3,1,'sallut',0,'2025-10-30 13:03:56'),(67,3,1,'sallut',0,'2025-10-30 13:03:57'),(68,3,1,'sallut',0,'2025-10-30 13:03:57'),(69,3,1,'sallut',0,'2025-10-30 13:03:57'),(70,3,1,'sallut',0,'2025-10-30 13:03:57'),(71,3,1,'sallut',0,'2025-10-30 13:03:58'),(72,3,1,'sallut',0,'2025-10-30 13:03:58'),(73,3,1,'sallut',0,'2025-10-30 13:03:58'),(74,3,1,'sallut',0,'2025-10-30 13:03:58'),(75,3,1,'sallut',0,'2025-10-30 13:03:58'),(76,3,1,'sallut',0,'2025-10-30 13:03:58'),(77,3,1,'sallut',0,'2025-10-30 13:03:58'),(78,3,1,'sallut',0,'2025-10-30 13:03:58'),(79,3,1,'sallut',0,'2025-10-30 13:03:58'),(80,3,1,'sallut',0,'2025-10-30 13:03:58'),(81,3,1,'sallut',0,'2025-10-30 13:03:58'),(82,3,1,'sallut',0,'2025-10-30 13:03:58'),(83,3,1,'sallut',0,'2025-10-30 13:03:59'),(84,3,1,'sallut',0,'2025-10-30 13:03:59'),(85,3,1,'sallut',0,'2025-10-30 13:03:59'),(86,3,1,'sallut',0,'2025-10-30 13:03:59'),(87,5,3,'salut',0,'2025-10-30 13:08:27'),(88,3,1,'bonjour rakh',0,'2025-10-30 13:11:02'),(89,5,3,'bonjour boy',0,'2025-10-30 13:11:44'),(90,5,3,'nice',0,'2025-10-30 13:14:34'),(91,2,1,'breudeuh',0,'2025-10-30 13:17:05'),(92,5,2,'yh',0,'2025-10-30 13:17:29'),(93,5,2,'gg',0,'2025-10-30 13:28:02'),(94,5,2,'salut',0,'2025-10-30 13:31:42'),(95,5,2,'salut',0,'2025-10-30 13:58:46'),(96,5,2,'salut',0,'2025-10-30 13:58:51'),(97,3,1,'sa',0,'2025-10-30 13:59:25'),(98,5,3,'ok',0,'2025-10-30 14:04:46'),(99,5,3,'bb',0,'2025-10-30 14:08:36'),(100,3,1,'ok',0,'2025-10-30 14:09:16'),(101,3,1,'ya probleme',0,'2025-10-30 14:18:27'),(102,3,5,'merci',1,'2025-10-30 14:56:08'),(103,5,3,'nice',0,'2025-10-30 14:56:22'),(104,5,2,'z',0,'2026-04-22 15:20:45');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `article_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` (`id`, `order_id`, `article_id`, `quantity`, `price`) VALUES (1,1,1,1,0.00),(2,2,5,1,28000.00),(3,2,4,1,55000.00),(5,4,5,1,28000.00),(6,5,6,1,45000.00),(7,6,6,1,45000.00),(8,7,5,1,28000.00),(9,8,4,1,55000.00),(10,9,6,1,45000.00),(12,13,4,1,55000.00),(13,14,5,1,28000.00),(14,15,6,1,45000.00),(15,16,6,1,45000.00),(16,17,3,1,15000.00),(17,18,5,1,28000.00),(18,19,4,1,55000.00),(19,20,3,1,15000.00),(20,21,6,1,45000.00),(21,22,6,1,45000.00),(22,23,3,1,15000.00),(23,24,4,1,55000.00),(24,25,3,1,15000.00),(25,26,5,1,28000.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `visiteur` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` (`id`, `user_id`, `total_price`, `order_date`, `status`) VALUES (1,2,0.00,'2025-10-19 13:45:13','pending'),(2,2,83000.00,'2025-10-19 14:04:30','pending'),(4,4,28000.00,'2025-10-21 15:36:42','pending'),(5,5,0.00,'2025-10-23 16:43:12','pending'),(6,3,0.00,'2025-10-23 23:55:02','en_attente'),(7,3,0.00,'2025-10-24 00:16:55','en_attente'),(8,5,55000.00,'2025-10-24 00:19:29','validee'),(9,5,45000.00,'2025-10-28 16:48:44','en_attente'),(13,2,55000.00,'2025-10-29 23:36:18','validee'),(14,2,28000.00,'2025-10-30 00:06:54','validee'),(15,5,45000.00,'2025-10-30 00:11:32','validee'),(16,5,45000.00,'2025-10-30 00:18:21','validee'),(17,5,15000.00,'2026-04-22 16:08:57','annulee'),(18,5,28000.00,'2026-04-30 23:15:14','en_attente'),(19,5,55000.00,'2026-04-30 23:24:29','en_attente'),(20,6,15000.00,'2026-04-30 23:27:50','annulee'),(21,3,45000.00,'2026-04-30 23:41:21','annulee'),(22,3,45000.00,'2026-05-01 00:15:18','en_attente'),(23,3,15000.00,'2026-05-01 00:54:26','annulee'),(24,5,55000.00,'2026-05-03 18:25:08','en_attente'),(25,8,15000.00,'2026-05-03 19:53:38','en_attente'),(26,9,28000.00,'2026-05-09 11:23:30','en_attente'),(27,5,0.00,'2026-05-19 13:09:28','annulee');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `nom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `montant` int(11) NOT NULL,
  `date_paiement` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements`
--

LOCK TABLES `paiements` WRITE;
/*!40000 ALTER TABLE `paiements` DISABLE KEYS */;
INSERT INTO `paiements` (`id`, `order_id`, `nom`, `telephone`, `montant`, `date_paiement`) VALUES (1,13,'Diagne','776465326',55000,'2025-10-30 00:06:15'),(2,14,'Diagne','776468532',28000,'2025-10-30 00:07:03'),(3,15,'Diagne','777777777',45000,'2025-10-30 00:11:42');
/*!40000 ALTER TABLE `paiements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visiteur`
--

DROP TABLE IF EXISTS `visiteur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visiteur` (
  `nom` varchar(55) NOT NULL,
  `email` varchar(55) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(120) DEFAULT NULL,
  `seller_id_type` varchar(30) DEFAULT NULL,
  `seller_id_number` varchar(60) DEFAULT NULL,
  `seller_ine` varchar(60) DEFAULT NULL,
  `seller_id_photo` varchar(255) DEFAULT NULL,
  `seller_verified` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(66) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `prenom` varchar(66) NOT NULL,
  `username` varchar(65) NOT NULL,
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `avatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visiteur`
--

LOCK TABLES `visiteur` WRITE;
/*!40000 ALTER TABLE `visiteur` DISABLE KEYS */;
INSERT INTO `visiteur` (`nom`, `email`, `telephone`, `adresse`, `ville`, `seller_id_type`, `seller_id_number`, `seller_ine`, `seller_id_photo`, `seller_verified`, `password`, `role`, `prenom`, `username`, `id`, `avatar`) VALUES ('diagne','diagneibeu13@gmail.com',NULL,NULL,NULL,'carte_identite','12222222222',NULL,NULL,0,'$2y$10$zs94JeeA32VV46/kEtfR0OOtJB5nUSAa.zajRRto.KfrAsnUUsS7S','user','khalil','khalil11',2,'96ca37ff662e1eaf6947f76c.jpg'),('diagne','diagneibeu14@gmail.com','775072936','rue 102','dakar','autre','123344444',NULL,'kyc_3_1778585256.jpg',1,'$2y$10$ZySory2.s5fqz..39I.KiuVjfWXTUaU1cOcBgbpYpLMBuTUCVoh/O','seller','khalil','khalil13',3,NULL),('Diagne','moustaphadiagne2025@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'$2y$10$r.oRhplaKES260Q5qkLYke9.kXfy5syWdKxt78sfqoOtghI7EmC4u','user','Moustapha','moustapha2025',4,'fe79ad933f7a6e65c1d5da16.jpg'),('diagne','diagneibeu10@gmail.com','775072936','rue 102','dakar',NULL,NULL,NULL,NULL,0,'$2y$10$.n0nbkvr.s5SWXZ7O0MhlOuayG.wiLpX08Y8k0cMZkb6McHFGi0Ba','admin','khalil','khalildiagne10',5,'2ece4d8042e7b1e20767fef3.jpg'),('diagne','papadiagne10@gmail.com','775072936','rue 102','dakar',NULL,NULL,NULL,NULL,0,'$2y$10$kpBoB.c9goV77Dec0osDreuZmWDZ5GXFwWSV97L1UAVQphIW2dRRy','user','papa','Papediagne1234',6,NULL),('diagne','papadia10@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'$2y$10$XiOIDQEbl/VHiHuxv53.AedW4Kd6FjpIojO6yWnq3Zo0/f.83vYgm','user','papa','Papediagne10',7,NULL),('diagne','papadiagne15@gmail.com','787878877','rue 102','dakar',NULL,NULL,NULL,NULL,0,'$2y$10$2bOxpLRPDoriieiKzOXHV.f8JznbO4ljujomAmOtYaEprWAdZuGu6','user','papa','Papediagne123',8,NULL),('wone','basswd@gmail.com','708788888','dakar','dakar',NULL,NULL,NULL,NULL,0,'$2y$10$bHZfNKx3Haaeb7UIOdU10.Hd5yrERA/2eQe1QFkTC2xJLwwZOmwa2','user','bass','Bass10',9,NULL);
/*!40000 ALTER TABLE `visiteur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist_items`
--

DROP TABLE IF EXISTS `wishlist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `article_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_article` (`user_id`,`article_id`),
  KEY `idx_article_id` (`article_id`),
  CONSTRAINT `fk_wishlist_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `visiteur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist_items`
--

LOCK TABLES `wishlist_items` WRITE;
/*!40000 ALTER TABLE `wishlist_items` DISABLE KEYS */;
INSERT INTO `wishlist_items` (`id`, `user_id`, `article_id`, `created_at`) VALUES (2,5,5,'2026-04-25 13:31:28');
/*!40000 ALTER TABLE `wishlist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'efootball'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-21 23:50:41
