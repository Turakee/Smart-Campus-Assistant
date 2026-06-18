-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: smart_Campus_db
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
-- Table structure for table `admin_audit_log`
--

DROP TABLE IF EXISTS `admin_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `admin_audit_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_audit_log`
--

LOCK TABLES `admin_audit_log` WRITE;
/*!40000 ALTER TABLE `admin_audit_log` DISABLE KEYS */;
INSERT INTO `admin_audit_log` VALUES (1,1,'System initialized and configured','settings_updated',NULL,NULL,'2026-05-26 14:38:24');
/*!40000 ALTER TABLE `admin_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `administrators`
--

DROP TABLE IF EXISTS `administrators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administrators` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `administrators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrators`
--

LOCK TABLES `administrators` WRITE;
/*!40000 ALTER TABLE `administrators` DISABLE KEYS */;
INSERT INTO `administrators` VALUES (1,1,'Usman Auwal','IT Director'),(3,5,'admin',NULL);
/*!40000 ALTER TABLE `administrators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_analytics_log`
--

DROP TABLE IF EXISTS `ai_analytics_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_analytics_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `prediction_type` enum('schedule_optimization','attendance_risk','performance') NOT NULL,
  `prediction_result` text DEFAULT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'low',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  KEY `idx_type` (`prediction_type`),
  CONSTRAINT `ai_analytics_log_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_analytics_log`
--

LOCK TABLES `ai_analytics_log` WRITE;
/*!40000 ALTER TABLE `ai_analytics_log` DISABLE KEYS */;
INSERT INTO `ai_analytics_log` VALUES (1,1,'attendance_risk','{\"percentage\":0,\"score\":100,\"source\":\"php_fallback\"}','high','2026-05-09 13:59:46'),(2,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-09 13:59:46'),(3,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"php_prediction\"}','high','2026-05-09 13:59:46'),(4,1,'attendance_risk','{\"percentage\":0,\"score\":100,\"source\":\"php_fallback\"}','high','2026-05-12 07:10:00'),(5,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-12 07:10:00'),(6,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"php_prediction\"}','high','2026-05-12 07:10:00'),(7,1,'attendance_risk','{\"percentage\":0,\"score\":100,\"source\":\"php_fallback\"}','high','2026-05-12 19:46:36'),(8,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-12 19:46:36'),(9,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"php_prediction\"}','high','2026-05-12 19:46:36'),(10,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 10:59:54'),(11,1,'schedule_optimization','{\"score\":60,\"conflicts_resolved\":5,\"source\":\"cpp_engine\"}','low','2026-05-13 10:59:54'),(12,1,'performance','{\"predicted_score\":100,\"predicted_grade\":\"A\",\"grade_points\":4,\"attendance_percentage\":100,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"php_prediction\"}','low','2026-05-13 10:59:54'),(13,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:09:38'),(14,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 11:09:38'),(15,1,'performance','{\"predicted_score\":100,\"predicted_grade\":\"A\",\"grade_points\":4,\"attendance_percentage\":100,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"php_prediction\"}','low','2026-05-13 11:09:38'),(16,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 11:31:39'),(17,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 11:32:23'),(18,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:35:08'),(19,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:50:19'),(20,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:51:31'),(21,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:51:35'),(22,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 11:51:39'),(23,1,'performance','{\"predicted_score\":100,\"predicted_grade\":\"A\",\"grade_points\":4,\"attendance_percentage\":100,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"php_prediction\"}','low','2026-05-13 11:51:39'),(24,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 11:52:06'),(25,1,'attendance_risk','{\"percentage\":100,\"score\":0,\"source\":\"php_fallback\"}','low','2026-05-13 11:52:34'),(26,1,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-13 18:00:49'),(27,1,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-13 18:18:14'),(28,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"cpp_engine\"}','high','2026-05-13 18:18:14'),(29,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 19:13:22'),(30,1,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-13 19:14:39'),(31,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"cpp_engine\"}','high','2026-05-13 19:16:00'),(32,1,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-13 19:16:00'),(33,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 20:57:23'),(34,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":2,\"present\":\"2\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-13 20:57:23'),(35,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-13 21:16:30'),(36,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-13 21:16:30'),(37,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-13 21:19:21'),(38,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-13 21:21:04'),(39,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 05:49:42'),(40,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 05:49:42'),(41,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 05:55:31'),(42,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 05:55:32'),(43,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 05:55:35'),(44,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 05:55:35'),(45,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 05:55:40'),(46,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 05:55:40'),(47,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 06:06:48'),(48,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 06:06:48'),(49,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 06:06:48'),(50,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 06:08:51'),(51,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 06:08:52'),(52,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 06:08:52'),(53,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 18:42:46'),(54,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 18:42:46'),(55,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 18:42:46'),(56,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:05:34'),(57,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:05:35'),(58,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:05:35'),(59,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:12:00'),(60,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:12:00'),(61,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:12:00'),(62,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:12:59'),(63,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:12:59'),(64,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:12:59'),(65,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:13:00'),(66,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:13:00'),(67,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:13:00'),(68,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:13:54'),(69,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:13:54'),(70,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:13:54'),(71,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:22'),(72,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:23'),(73,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:21:23'),(74,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:41'),(75,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:41'),(76,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:21:41'),(77,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:44'),(78,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:21:44'),(79,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:21:45'),(80,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:09'),(81,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:09'),(82,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:24:09'),(83,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:11'),(84,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:12'),(85,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:24:12'),(86,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:12'),(87,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:13'),(88,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:24:13'),(89,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:13'),(90,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:13'),(91,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:24:14'),(92,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:15'),(93,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:24:16'),(94,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:24:16'),(95,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:28:18'),(96,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:28:18'),(97,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:28:18'),(98,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:39:40'),(99,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:39:40'),(100,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:39:41'),(101,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:46:13'),(102,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:46:21'),(103,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:46:25'),(104,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:56:04'),(105,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:56:43'),(106,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:56:50'),(107,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-14 19:58:52'),(108,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:58:52'),(109,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-14 19:58:53'),(110,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:59:06'),(111,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:59:07'),(112,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:59:10'),(113,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:59:11'),(114,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-14 19:59:12'),(115,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-19 08:38:57'),(116,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-19 08:38:57'),(117,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-19 08:41:29'),(118,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-21 23:02:13'),(119,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:02:13'),(120,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-21 23:02:13'),(121,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-21 23:17:25'),(122,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:17:26'),(123,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-21 23:17:26'),(124,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-21 23:23:37'),(125,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:23:38'),(126,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-21 23:23:38'),(127,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-21 23:27:26'),(128,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:27:27'),(129,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-21 23:27:27'),(130,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-21 23:31:38'),(131,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:31:38'),(132,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-21 23:31:39'),(133,3,'attendance_risk','{\"percentage\":0,\"score\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:58:08'),(134,3,'schedule_optimization','{\"score\":0,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-21 23:58:08'),(135,3,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"cpp_engine\"}','high','2026-05-21 23:58:08'),(136,4,'attendance_risk','{\"percentage\":0,\"score\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 01:26:28'),(137,4,'attendance_risk','{\"percentage\":0,\"score\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 01:26:39'),(138,4,'attendance_risk','{\"percentage\":0,\"score\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 01:30:16'),(139,4,'schedule_optimization','{\"score\":0,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 01:30:21'),(140,4,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"cpp_engine\"}','high','2026-05-22 01:30:26'),(141,3,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-22 01:32:58'),(142,3,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 01:33:12'),(143,3,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":1,\"present\":\"1\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"cpp_engine\"}','high','2026-05-22 01:33:18'),(144,1,'attendance_risk','{\"percentage\":80,\"score\":25,\"source\":\"cpp_engine\"}','low','2026-05-22 22:01:04'),(145,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 22:01:09'),(146,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":5,\"present\":\"4\",\"absent\":\"1\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-22 22:01:12'),(147,3,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-22 22:46:46'),(148,3,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-22 22:46:50'),(149,3,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":1,\"present\":\"1\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":1,\"source\":\"cpp_engine\"}','high','2026-05-22 22:46:55'),(150,1,'attendance_risk','{\"percentage\":100,\"score\":15,\"source\":\"cpp_engine\"}','low','2026-05-23 21:09:05'),(151,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-23 21:09:13'),(152,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":6,\"present\":\"6\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-23 21:09:25'),(153,1,'attendance_risk','{\"percentage\":50,\"score\":40,\"source\":\"cpp_engine\"}','medium','2026-05-25 20:55:50'),(154,1,'schedule_optimization','{\"score\":100,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-25 20:55:55'),(155,1,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":6,\"present\":\"3\",\"absent\":\"1\",\"late\":\"1\",\"courses_enrolled\":2,\"source\":\"cpp_engine\"}','high','2026-05-25 20:56:05'),(156,1,'attendance_risk','{\"percentage\":50,\"score\":40,\"source\":\"cpp_engine\"}','medium','2026-05-26 13:36:49'),(157,1,'attendance_risk','{\"percentage\":50,\"score\":40,\"source\":\"cpp_engine\"}','medium','2026-05-26 13:43:33'),(158,1,'attendance_risk','{\"percentage\":50,\"score\":40,\"source\":\"cpp_engine\"}','medium','2026-05-26 13:48:45'),(159,5,'attendance_risk','{\"percentage\":0,\"score\":0,\"source\":\"cpp_engine\"}','low','2026-05-28 09:45:22'),(160,5,'schedule_optimization','{\"score\":0,\"conflicts_resolved\":0,\"source\":\"cpp_engine\"}','low','2026-05-28 09:45:25'),(161,5,'performance','{\"predicted_score\":0,\"predicted_grade\":\"F\",\"grade_points\":0,\"attendance_percentage\":0,\"total_classes\":0,\"present\":\"0\",\"absent\":\"0\",\"late\":\"0\",\"courses_enrolled\":0,\"source\":\"cpp_engine\"}','high','2026-05-28 09:45:29');
/*!40000 ALTER TABLE `ai_analytics_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'absent',
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`date`),
  KEY `course_id` (`course_id`),
  KEY `idx_date` (`date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,1,'2026-05-13','late'),(2,1,1,'2026-05-14','absent'),(3,1,2,'2026-05-13','present'),(4,1,1,'2026-05-12','present'),(5,1,2,'2026-05-12','excused'),(6,3,3,'2026-05-22','present'),(7,1,1,'2026-05-23','present');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`booking_id`),
  KEY `student_id` (`student_id`),
  KEY `resource_id` (`resource_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `lecturer_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_code` (`course_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'Artificial intelligence','CS201','Computer Science',4,'Dr. Usman Auwal'),(2,'PHP','CS104','Computer Science',4,'Dr. Usman Auwal'),(3,'Maths','E101','Engineering',2,'Dr. Usman Auwal');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,3,'Attendance Risk Alert: Your attendance is below 75%','danger',0,'2026-05-09 13:59:46'),(2,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-09 13:59:46'),(3,3,'📅 Event: AI practice  - This is about introducing an AI intelligence\nlocation lab A\ntime 10:00 AM (Date: 2026-12-05)','info',0,'2026-05-09 14:09:50'),(4,3,'Attendance Risk Alert: Your attendance is below 75%','danger',0,'2026-05-12 07:10:00'),(5,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-12 07:10:00'),(6,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-13 18:18:14'),(7,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-14 18:42:46'),(8,3,'Message from Administrator:\ncome','info',0,'2026-05-14 18:50:33'),(9,3,'📅 Event: AI practice  - Learning how to use AI (Date: 2026-06-01)','info',1,'2026-05-14 18:53:03'),(10,3,'Message from Administrator:\nsee me','info',0,'2026-05-14 19:49:02'),(11,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-19 08:38:57'),(12,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',1,'2026-05-21 23:02:13'),(13,6,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-21 23:58:09'),(14,7,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-22 01:30:26'),(15,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',1,'2026-05-23 21:09:25'),(16,3,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-25 20:56:05'),(17,8,'Performance Alert: Your predicted grade is below passing. Immediate action required!','danger',0,'2026-05-28 09:45:29');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_name` varchar(100) NOT NULL,
  `resource_type` enum('classroom','lab','auditorium','other') DEFAULT 'classroom',
  `capacity` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `idx_type` (`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `course_id` (`course_id`),
  KEY `idx_day` (`day_of_week`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,1,'Thursday','08:30:00','10:30:00','Lab 4'),(2,2,'Monday','08:30:00','00:30:00','Lab'),(3,3,'Sunday','10:30:00','00:30:00','Lab 4');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_courses`
--

DROP TABLE IF EXISTS `student_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_courses` (
  `student_course_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_course_id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_courses`
--

LOCK TABLES `student_courses` WRITE;
/*!40000 ALTER TABLE `student_courses` DISABLE KEYS */;
INSERT INTO `student_courses` VALUES (1,1,1,'2026-05-12 20:26:05'),(7,3,3,'2026-05-22 00:44:53'),(10,1,2,'2026-05-23 21:04:07');
/*!40000 ALTER TABLE `student_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `enrollment_year` year(4) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_department` (`department`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,3,'Mustaapha Nasir','Computer Science',4,2026),(3,6,'Hamza Auwal','Engineering',1,2026),(4,7,'Ahmad Umar','Software Engineering',1,2026),(5,8,'Abdulkarim Dully','Computer Science',1,2026),(6,9,'Aliyu Isah','Computer Science',1,2026),(7,10,'Ibrahim Rabiu','Computer Science',4,2026);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','administrator','system_admin') NOT NULL,
  `is_system_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'sysadmin','admin@campus.edu','$2y$12$ABTCI8/oCAXma.OYjlPNB.EyPIt7KcYaYxk.JUYCmoRqEwpg5ZQUq','system_admin',0,'2026-05-09 13:14:54','2026-06-01 21:36:47',1),(3,'nasir','abuubaidaamirahmad@gmail.com','$2y$12$NlbLJAf09bXt8NxQKMPLG.7PNOidJWbLfpb6O03zEE/dNhytxnc4O','student',0,'2026-05-09 13:52:04','2026-05-28 09:04:55',1),(5,'admin','usmanauwalturakee@gmail.com','$2y$12$yMQbQ9PTOHKbp3uK2vUqie7evJH1WlLZwlG50oZN9z/EF.x0CHtiq','administrator',0,'2026-05-12 19:48:37','2026-06-01 20:53:55',1),(6,'hamza','hamza@gmail.com','$2y$12$1gXuWLMg11Ch44z4XCXJhOgGzQSmPB5Rzov8kWgeIp0UkOyALwnMm','student',0,'2026-05-21 23:57:45','2026-05-22 22:46:09',1),(7,'ahmad','ahmad@gmail.com','$2y$12$RKOX4zHvEu5/tieQN1h.WurHKEtcl9bN/LVnYrbFaah4OFSHB083W','student',0,'2026-05-22 00:16:53','2026-05-22 22:45:23',1),(8,'dully','dully@gmail.com','$2y$12$rwM1hxUscl35y5PcPPdQe.t5Y/gl75CpRvGpQMDQJftv0UXY94g1.','student',0,'2026-05-28 09:44:02','2026-06-01 20:52:58',1),(9,'haidar','aliyu@gmail.com','$2y$12$OFZe2/FhQBZR4rOax2OOyutqTXHQHeVSyVEwugHoXc1A2m5nc4cIi','student',0,'2026-06-01 20:52:52','2026-06-01 20:53:13',1),(10,'rabee','rabee@gmail.com','$2y$12$WtydwDDW.ECUjsQBLK8L1OgPYr4/v0nhPfkaJ06cxdl86ee6i/ye6','student',0,'2026-06-01 20:55:52','2026-06-01 20:56:05',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-01 23:36:56
