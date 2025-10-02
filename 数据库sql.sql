-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: test_faka_com
-- ------------------------------------------------------
-- Server version	5.7.44-log

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
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,'修改密码','管理员修改了登录密码','112.14.79.153','Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36','2025-10-02 17:51:19'),(2,'修改密码','管理员修改了登录密码','112.14.79.153','Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36','2025-10-02 17:51:45');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','0192023a7bbd73250516f069df18b500','admin@yourdomain.com','2025-10-02 15:50:49','2025-10-02 17:51:45');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `card_number` varchar(255) NOT NULL,
  `card_password` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cards_product_status` (`product_id`,`status`),
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (11,7,'复活币*2赠幸运福袋','',1,'2025-10-02 15:59:02'),(12,7,'F5CE64A929544426','',1,'2025-10-02 15:59:02'),(13,7,'6EF66B3B69731899','',1,'2025-10-02 15:59:02'),(14,7,'A457784A667CAC16','',1,'2025-10-02 15:59:02'),(15,7,'760CC38225612258','',1,'2025-10-02 15:59:02'),(16,7,'5950DCA5AAA33253','',1,'2025-10-02 15:59:02'),(17,7,'7647F459B84A5E2D','',1,'2025-10-02 15:59:02'),(18,7,'2594928547461C51','',1,'2025-10-02 15:59:02'),(19,7,'34A66BDCBA86235B','',1,'2025-10-02 15:59:02'),(20,7,'B77B6DB36BF2F857','',1,'2025-10-02 15:59:02'),(21,7,'DC8ED2A9D883D8C5','',1,'2025-10-02 15:59:02'),(22,7,'0CB9737AFC965CCC','',1,'2025-10-02 15:59:02'),(23,7,'4AD1DAFB9470795C','',1,'2025-10-02 15:59:02'),(24,7,'9C90801AD4CC3CE4','',1,'2025-10-02 15:59:02'),(25,7,'B6117E0F11C83C32','',1,'2025-10-02 15:59:02'),(26,7,'8F288175C506A574','',1,'2025-10-02 15:59:02'),(27,7,'CC9E56D74F9DAE8E','',1,'2025-10-02 15:59:02'),(28,7,'6DCA2BB8ED2B9DBF','',1,'2025-10-02 15:59:02'),(29,7,'18A3F43CBA0C0516','',1,'2025-10-02 15:59:02'),(30,7,'B44F12F11DF34566','',1,'2025-10-02 15:59:02'),(31,7,'D8F8673A1617E07F','',1,'2025-10-02 15:59:02'),(32,7,'AD9B9F28E6032330','',1,'2025-10-02 15:59:02'),(33,7,'1DD26E00289BDC45','',1,'2025-10-02 15:59:02'),(34,7,'E6F8FEDF36227E31','',1,'2025-10-02 15:59:02'),(35,7,'625BC7B09F41E3B8','',0,'2025-10-02 15:59:02'),(36,7,'D73026DA0845AB27','',0,'2025-10-02 15:59:02'),(37,7,'C53B3FBD28F3C355','',0,'2025-10-02 15:59:02'),(38,7,'D6BFA913CE7C5493','',0,'2025-10-02 15:59:02'),(39,7,'A880C76B0C378A7F','',0,'2025-10-02 15:59:02'),(40,7,'1A2A30E6BDF55617','',0,'2025-10-02 15:59:02'),(41,7,'5E813417F028A70B','',0,'2025-10-02 15:59:02'),(42,7,'135FEBDAB02494A8','',0,'2025-10-02 15:59:02'),(43,7,'E07B6B6C842F644D','',0,'2025-10-02 15:59:02'),(44,7,'2379688F8BD90347','',0,'2025-10-02 15:59:02'),(45,7,'0AA4B801FA3DE4A4','',0,'2025-10-02 15:59:02'),(46,7,'A7674249DD6C83E3','',0,'2025-10-02 15:59:02'),(47,7,'4FDEB0677408DEA4','',0,'2025-10-02 15:59:02'),(48,7,'D4EEB77A50AA2077','',0,'2025-10-02 15:59:02'),(49,7,'849532963673A084','',0,'2025-10-02 15:59:02'),(50,7,'D5646BA78238132F','',0,'2025-10-02 15:59:02'),(51,7,'ACDD27696B2B9D4E','',0,'2025-10-02 15:59:02'),(52,7,'DB8C213BF6210BC1','',0,'2025-10-02 15:59:02'),(53,7,'DFA0145B04949673','',0,'2025-10-02 15:59:02'),(54,7,'384FBF6A0353F06C','',0,'2025-10-02 15:59:02'),(55,7,'B0CE641E003CA161','',0,'2025-10-02 15:59:02'),(56,7,'206D07B9A164A63C','',0,'2025-10-02 15:59:02'),(57,7,'F2C0C3FA8BB557B7','',0,'2025-10-02 15:59:02'),(58,7,'13C55D937953611B','',0,'2025-10-02 15:59:02'),(59,7,'21CC987B413CE1C4','',0,'2025-10-02 15:59:02'),(60,7,'A18B96DA6651898A','',0,'2025-10-02 15:59:02'),(61,7,'ED5D74F58D732C09','',0,'2025-10-02 15:59:02');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` tinyint(4) DEFAULT '1',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `balance` decimal(10,2) DEFAULT '0.00',
  `random_slug` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categories_slug` (`random_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'游戏点卡','各类游戏充值卡密',1,1,'2025-10-02 15:50:49',0.00,'catVXRKa7VP7n'),(5,'奇迹暖暖与地下城','',1,0,'2025-10-02 19:07:56',0.00,'catJEUfL6y880'),(6,'魔刹搬空团','',1,0,'2025-10-02 20:05:11',0.00,'catpEOxu4iIAN'),(7,'回归巨龙','',1,0,'2025-10-02 20:05:46',0.00,'catDx0fCV2uu8');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usage`
--

DROP TABLE IF EXISTS `coupon_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) DEFAULT NULL,
  `order_no` varchar(50) DEFAULT NULL,
  `user_contact` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usage`
--

LOCK TABLES `coupon_usage` WRITE;
/*!40000 ALTER TABLE `coupon_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('fixed','percent') DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT '0.00',
  `usage_limit` int(11) DEFAULT '0',
  `used_count` int(11) DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `applicable_categories` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'WELCOME10','新用户优惠券','fixed',10.00,20.00,10.00,100,0,'2025-10-02','2025-11-01',1,NULL,'2025-10-02 15:59:04'),(2,'SAVE20%','通用折扣券','percent',20.00,50.00,50.00,200,0,'2025-10-02','2025-12-01',1,NULL,'2025-10-02 15:59:04'),(3,'FREESHIP','免单券','fixed',0.00,0.00,0.00,50,0,'2025-10-02','2025-10-09',1,NULL,'2025-10-02 15:59:04'),(4,'efwefeghewrh','12测','fixed',2.00,0.00,0.00,0,0,'2025-10-03','2025-10-16',1,'4','2025-10-02 16:41:27');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_cards`
--

DROP TABLE IF EXISTS `order_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `card_id` int(11) DEFAULT NULL,
  `card_number` varchar(255) NOT NULL,
  `card_password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_cards`
--

LOCK TABLES `order_cards` WRITE;
/*!40000 ALTER TABLE `order_cards` DISABLE KEYS */;
INSERT INTO `order_cards` VALUES (1,'202510030219410490',11,'复活币*2赠幸运福袋','','2025-10-02 18:20:05'),(2,'202510030219410490',12,'F5CE64A929544426','','2025-10-02 18:20:05'),(3,'202510030219410490',13,'6EF66B3B69731899','','2025-10-02 18:20:05'),(4,'202510030219410490',14,'A457784A667CAC16','','2025-10-02 18:20:05'),(5,'202510030219410490',15,'760CC38225612258','','2025-10-02 18:20:05'),(6,'202510030228276256',16,'5950DCA5AAA33253','','2025-10-02 18:28:41'),(7,'202510030228276256',17,'7647F459B84A5E2D','','2025-10-02 18:28:41'),(8,'202510030228276256',18,'2594928547461C51','','2025-10-02 18:28:41'),(9,'202510030228276256',19,'34A66BDCBA86235B','','2025-10-02 18:28:41'),(10,'202510030228276256',20,'B77B6DB36BF2F857','','2025-10-02 18:28:41'),(11,'202510030412209437',21,'DC8ED2A9D883D8C5','','2025-10-02 20:12:40'),(12,'202510030412209437',22,'0CB9737AFC965CCC','','2025-10-02 20:12:40'),(13,'202510030454484686',23,'4AD1DAFB9470795C','','2025-10-02 20:54:53'),(14,'202510030454484686',24,'9C90801AD4CC3CE4','','2025-10-02 20:54:53'),(15,'202510030454484686',25,'B6117E0F11C83C32','','2025-10-02 20:54:53'),(16,'202510030454484686',26,'8F288175C506A574','','2025-10-02 20:54:53'),(17,'202510030454484686',27,'CC9E56D74F9DAE8E','','2025-10-02 20:54:53'),(18,'202510030455296614',28,'6DCA2BB8ED2B9DBF','','2025-10-02 20:56:02'),(19,'202510030455296614',29,'18A3F43CBA0C0516','','2025-10-02 20:56:02'),(20,'202510030455296614',30,'B44F12F11DF34566','','2025-10-02 20:56:02'),(21,'202510030455296614',31,'D8F8673A1617E07F','','2025-10-02 20:56:02'),(22,'202510030455296614',32,'AD9B9F28E6032330','','2025-10-02 20:56:02'),(23,'202510030456505397',33,'1DD26E00289BDC45','','2025-10-02 20:56:57'),(24,'202510030456505397',34,'E6F8FEDF36227E31','','2025-10-02 20:56:57');
/*!40000 ALTER TABLE `order_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT '1',
  `contact_info` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0',
  `pay_method` varchar(50) DEFAULT NULL,
  `card_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` timestamp NULL DEFAULT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `final_amount` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `product_id` (`product_id`),
  KEY `card_id` (`card_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_paid_at` (`paid_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'202510030057320237',7,'测试',0.01,1,'2533448753',0,NULL,NULL,'2025-10-02 16:57:32',NULL,0,'',0.00,0.01),(2,'202510030110541539',7,'测试',0.01,1,'2533448753',0,NULL,NULL,'2025-10-02 17:10:54',NULL,0,'',0.00,0.01),(3,'202510030111023414',7,'测试',0.01,1,'2533448753@qq.com',0,NULL,NULL,'2025-10-02 17:11:02',NULL,0,'',0.00,0.01),(4,'202510030111492112',7,'测试',0.01,1,'2533448753@qq.com',0,NULL,NULL,'2025-10-02 17:11:49',NULL,0,'',0.00,0.01),(5,'202510030214272704',7,'测试',0.01,15,'2533448753',0,NULL,NULL,'2025-10-02 18:14:27',NULL,0,'',0.00,0.15),(6,'202510030214352449',7,'测试',0.01,15,'2533448753',0,NULL,NULL,'2025-10-02 18:14:35',NULL,0,'',0.00,0.15),(7,'202510030219410490',7,'测试',0.01,5,'2533448753',1,'alipay',NULL,'2025-10-02 18:19:41','2025-10-02 18:20:05',0,'',0.00,0.05),(8,'202510030228276256',7,'测试',0.01,5,'2533448753',1,'alipay',NULL,'2025-10-02 18:28:27','2025-10-02 18:28:41',0,'',0.00,0.05),(9,'202510030412209437',7,'测试',0.05,2,'2533448753',1,'alipay',NULL,'2025-10-02 20:12:20','2025-10-02 20:12:40',0,'',0.00,0.10),(10,'202510030453442706',7,'测试',0.05,2,'2533448753',0,NULL,NULL,'2025-10-02 20:53:44',NULL,0,'',0.00,0.10),(11,'202510030454214617',7,'测试',1.00,2,'2533448753',0,NULL,NULL,'2025-10-02 20:54:21',NULL,0,'',0.00,2.00),(12,'202510030454484686',7,'测试',1.00,5,'2533448753',1,'wxpay',NULL,'2025-10-02 20:54:48','2025-10-02 20:54:53',0,'',0.00,5.00),(13,'202510030455296614',7,'测试',1.00,5,'2533448753',1,'alipay',NULL,'2025-10-02 20:55:29','2025-10-02 20:56:02',0,'',0.00,5.00),(14,'202510030456505397',7,'测试',1.00,2,'2533448753',1,'alipay',NULL,'2025-10-02 20:56:50','2025-10-02 20:56:57',0,'',0.00,2.00);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `status` tinyint(4) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category_status` (`category_id`,`status`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (7,1,'测试','册封为非晚高峰我给我给我',1.00,1,'2025-10-02 15:52:28');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(4) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'guchen666','$2y$10$TQfAdNdzXAlkB.dKK7ROHO7McUaiZ7PXQOtiuvfldb7CmKThFUMTi','2533448753@qq.com','17605878082',0.00,1,'2025-10-02 19:40:57','2025-10-02 19:40:57'),(2,'qwe253344','$2y$10$bWuJue5G77dMgI/gI0.1ee2SH5./rYB2ncuFGES0NzFV5BKW1f6lm','2533448753@qq.com','17605878082',0.00,1,'2025-10-02 19:48:05','2025-10-02 19:48:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `withdraw_no` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `order_count` int(11) DEFAULT '0',
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `applicant_name` varchar(100) DEFAULT '系统',
  `applicant_contact` varchar(100) DEFAULT NULL,
  `withdraw_account` varchar(255) DEFAULT NULL,
  `withdraw_method` varchar(50) DEFAULT 'alipay',
  `admin_notes` text,
  `processed_by` varchar(100) DEFAULT NULL,
  `approved_amount` decimal(10,2) DEFAULT '0.00',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `withdraw_no` (`withdraw_no`),
  KEY `category_id` (`category_id`),
  KEY `idx_withdrawals_status` (`status`),
  KEY `idx_withdrawals_created_at` (`created_at`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawals`
--

LOCK TABLES `withdrawals` WRITE;
/*!40000 ALTER TABLE `withdrawals` DISABLE KEYS */;
INSERT INTO `withdrawals` VALUES (1,'WD20251003023550001',1,'游戏点卡',0.02,2,'failed','系统','','','alipay','1','admin',0.02,'2025-10-02 19:09:47','2025-10-02 18:35:50'),(2,'WD20251003031007001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:22:26','2025-10-02 19:10:07'),(3,'WD20251003031013001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:22:24','2025-10-02 19:10:13'),(4,'WD20251003031014001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:22:23','2025-10-02 19:10:14'),(6,'WD20251003031015001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:22:21','2025-10-02 19:10:15'),(7,'WD20251003031016001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:22:19','2025-10-02 19:10:16'),(8,'WD20251003032235001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:24:44','2025-10-02 19:22:35'),(9,'WD20251003032258001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:24:43','2025-10-02 19:22:58'),(10,'WD20251003032259001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:24:41','2025-10-02 19:22:59'),(11,'WD20251003032431001',1,'游戏点卡',0.10,2,'failed','系统','','','alipay','1','admin',0.10,'2025-10-02 19:24:39','2025-10-02 19:24:31'),(12,'WD20251003041807001',1,'游戏点卡',0.10,3,'processed','系统','','','alipay','111','admin',0.10,'2025-10-02 20:18:14','2025-10-02 20:18:07'),(13,'WD20251003045521001',1,'游戏点卡',4.94,4,'processed','系统','','','alipay','','admin',4.94,'2025-10-02 20:55:50','2025-10-02 20:55:21'),(14,'WD20251003045711001',1,'游戏点卡',6.92,6,'processed','系统','','','alipay','','admin',6.92,'2025-10-02 21:00:05','2025-10-02 20:57:11');
/*!40000 ALTER TABLE `withdrawals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'test_faka_com'
--

--
-- Dumping routines for database 'test_faka_com'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-03  5:08:59
