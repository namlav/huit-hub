-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: huit_db
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Cơ sở Tân Phú','140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP.HCM'),(2,'Cơ sở Sơn Kỳ','76 Sơn Kỳ, P. Tân Quý, Q. Tân Phú, TP.HCM'),(3,'Cơ sở Tân Bình','98 Lý Thường Kiệt, P. 7, Q. Tân Bình, TP.HCM'),(4,'Trung tâm Thực hành','8 Nguyễn Ảnh Thủ, P. Hiệp Thành, Q. 12, TP.HCM');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int unsigned NOT NULL,
  `branch_id` int unsigned NOT NULL,
  `subject_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_week` tinyint unsigned NOT NULL,
  `shift_or_periods` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_slot` (`teacher_id`,`day_of_week`,`shift_or_periods`),
  UNIQUE KEY `uniq_room_slot` (`branch_id`,`room`,`day_of_week`,`shift_or_periods`),
  KEY `idx_schedules_branch` (`branch_id`,`day_of_week`),
  CONSTRAINT `fk_schedules_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,18,1,'Thiết kế cơ sở dữ liệu','A.304',2,'Sáng - Ca 1 (Tiết 1-3)'),(2,10,2,'Quản trị mạng','A.110',3,'Sáng - Ca 2 (Tiết 4-6)'),(3,7,3,'Hệ điều hành Linux','A.209',4,'Chiều - Ca 3 (Tiết 7-9)'),(4,9,4,'Quản trị mạng','A.307',5,'Chiều - Ca 4 (Tiết 10-12)'),(5,13,1,'Machine Learning','A.202',6,'Sáng - Ca 1 (Tiết 1-3)'),(6,16,2,'SQL nâng cao','A.402',7,'Sáng - Ca 2 (Tiết 4-6)'),(7,14,3,'Xử lý ngôn ngữ tự nhiên','A.405',2,'Chiều - Ca 3 (Tiết 7-9)'),(8,9,4,'Hệ điều hành Linux','A.403',3,'Chiều - Ca 4 (Tiết 10-12)'),(9,4,1,'Thiết kế Web với HTML/CSS','A.410',4,'Sáng - Ca 1 (Tiết 1-3)'),(10,15,2,'Thiết kế cơ sở dữ liệu','A.311',5,'Sáng - Ca 2 (Tiết 4-6)'),(11,2,3,'Thiết kế Web với HTML/CSS','A.211',6,'Chiều - Ca 3 (Tiết 7-9)'),(12,15,4,'SQL nâng cao','A.403',7,'Chiều - Ca 4 (Tiết 10-12)'),(13,12,1,'Xử lý ngôn ngữ tự nhiên','A.306',2,'Sáng - Ca 1 (Tiết 1-3)'),(14,15,2,'SQL nâng cao','A.405',3,'Sáng - Ca 2 (Tiết 4-6)'),(15,16,3,'Thiết kế cơ sở dữ liệu','A.306',4,'Chiều - Ca 3 (Tiết 7-9)'),(16,15,4,'Thiết kế cơ sở dữ liệu','A.107',5,'Chiều - Ca 4 (Tiết 10-12)'),(17,2,1,'Thiết kế Web với HTML/CSS','A.201',6,'Sáng - Ca 1 (Tiết 1-3)'),(18,13,2,'Xử lý ngôn ngữ tự nhiên','A.109',7,'Sáng - Ca 2 (Tiết 4-6)'),(19,1,3,'Lập trình PHP','A.404',2,'Chiều - Ca 3 (Tiết 7-9)'),(20,14,4,'Xử lý ngôn ngữ tự nhiên','A.205',3,'Chiều - Ca 4 (Tiết 10-12)'),(21,18,1,'SQL nâng cao','A.203',4,'Sáng - Ca 1 (Tiết 1-3)'),(22,3,2,'Thiết kế Web với HTML/CSS','A.404',5,'Sáng - Ca 2 (Tiết 4-6)'),(23,3,3,'Lập trình PHP','A.101',6,'Chiều - Ca 3 (Tiết 7-9)'),(24,4,4,'Thiết kế Web với HTML/CSS','A.212',7,'Chiều - Ca 4 (Tiết 10-12)'),(25,16,1,'Hệ quản trị cơ sở dữ liệu','A.202',2,'Sáng - Ca 1 (Tiết 1-3)'),(26,3,2,'Phát triển ứng dụng ReactJS','A.301',3,'Sáng - Ca 2 (Tiết 4-6)'),(27,4,3,'Thiết kế Web với HTML/CSS','A.305',4,'Chiều - Ca 3 (Tiết 7-9)'),(28,17,4,'Thiết kế cơ sở dữ liệu','A.203',5,'Chiều - Ca 4 (Tiết 10-12)'),(29,15,1,'Hệ quản trị cơ sở dữ liệu','A.106',6,'Sáng - Ca 1 (Tiết 1-3)'),(30,1,2,'Thiết kế Web với HTML/CSS','A.303',7,'Sáng - Ca 2 (Tiết 4-6)'),(31,9,3,'Hệ điều hành Linux','A.104',2,'Chiều - Ca 3 (Tiết 7-9)'),(32,18,4,'SQL nâng cao','A.312',3,'Chiều - Ca 4 (Tiết 10-12)'),(33,10,1,'Hệ điều hành Linux','A.403',4,'Sáng - Ca 1 (Tiết 1-3)'),(34,9,2,'An toàn thông tin','A.301',5,'Sáng - Ca 2 (Tiết 4-6)'),(35,8,3,'An toàn thông tin','A.303',6,'Chiều - Ca 3 (Tiết 7-9)'),(36,13,4,'Xử lý ngôn ngữ tự nhiên','A.112',7,'Chiều - Ca 4 (Tiết 10-12)');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expertise` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teachers_email` (`email`),
  KEY `idx_teachers_expertise` (`expertise`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,'Sơn Khoát','tin.ly@example.org','0999951366','Lập trình Web'),(2,'Giả Khai','ninh.lap@example.com','0988603099','Lập trình Web'),(3,'Dư Hải Thông','khe.dam@example.com','0919845665','Lập trình Web'),(4,'Cụ. Tòng Trầm','xuyen43@example.com','0958282791','Lập trình Web'),(5,'Anh. Mẫn Giác','dao89@example.com','0970265650','Lập trình Web'),(6,'Ông. Phan Bổng','nhiem.can@example.com','0938707730','Mạng máy tính'),(7,'Lưu Hưng Thực','cuong.cat@example.org','0989470133','Mạng máy tính'),(8,'Nông Bảo Tuyến','tong.hien@example.net','0981536489','Mạng máy tính'),(9,'Mộc Bội Liên','giap.cai@example.net','0966792774','Mạng máy tính'),(10,'Đái Hoa Hải','ky.dien@example.net','0977124358','Mạng máy tính'),(11,'Chế Phúc Thuận','son.cu@example.net','0959783540','Trí tuệ nhân tạo'),(12,'Đỗ Lộc','quan.thac@example.com','0993401056','Trí tuệ nhân tạo'),(13,'Chị. Lại Hiệp','abien@example.org','0953926745','Trí tuệ nhân tạo'),(14,'Khoa Bội Thêu','nhu.to@example.org','0941968285','Trí tuệ nhân tạo'),(15,'Bác. Huỳnh Phương Tiển','quang.giap@example.com','0923394139','Cơ sở dữ liệu'),(16,'Cụ. Lô Mẫn','hong.lo@example.com','0918451918','Cơ sở dữ liệu'),(17,'Anh. Tào Nghĩa Tiền','ung.quynh@example.com','0953145657','Cơ sở dữ liệu'),(18,'Chú. Phương Nhân Mạnh','ngon53@example.org','0947898525','Cơ sở dữ liệu');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18  7:56:12
