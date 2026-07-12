-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: manejo_ventas_tienda
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
-- Table structure for table `boleta_summaries`
--

DROP TABLE IF EXISTS `boleta_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boleta_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_referencia` date NOT NULL,
  `fecha_envio` date NOT NULL,
  `correlativo` int(10) unsigned NOT NULL,
  `identificador` varchar(30) NOT NULL,
  `ticket` varchar(255) DEFAULT NULL,
  `estado` varchar(12) NOT NULL DEFAULT 'pendiente',
  `total_boletas` int(10) unsigned NOT NULL DEFAULT 0,
  `xml_path` varchar(255) DEFAULT NULL,
  `cdr_path` varchar(255) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `boleta_summaries_company_id_identificador_unique` (`company_id`,`identificador`),
  KEY `boleta_summaries_user_id_foreign` (`user_id`),
  KEY `boleta_summaries_company_id_fecha_referencia_index` (`company_id`,`fecha_referencia`),
  CONSTRAINT `boleta_summaries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boleta_summaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boleta_summaries`
--

LOCK TABLES `boleta_summaries` WRITE;
/*!40000 ALTER TABLE `boleta_summaries` DISABLE KEYS */;
INSERT INTO `boleta_summaries` VALUES (1,1,1,'2026-07-05','2026-07-05',1,'RC-20260705-1',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-05 05:18:13','2026-07-05 05:18:13'),(2,1,1,'2026-07-05','2026-07-05',2,'RC-20260705-2',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-05 05:43:38','2026-07-05 05:43:38'),(3,1,1,'2026-07-06','2026-07-06',1,'RC-20260706-1',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-06 18:46:26','2026-07-06 18:46:26'),(4,1,1,'2026-07-06','2026-07-06',2,'RC-20260706-2',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-06 18:47:52','2026-07-06 18:47:52'),(5,1,1,'2026-07-06','2026-07-06',3,'RC-20260706-3',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-06 18:51:46','2026-07-06 18:51:46'),(6,1,1,'2026-07-06','2026-07-06',4,'RC-20260706-4',NULL,'pendiente',1,NULL,NULL,NULL,NULL,'2026-07-06 18:52:22','2026-07-06 18:52:22'),(7,1,1,'2026-07-06','2026-07-06',5,'RC-20260706-5','1783346476904','aceptado',2,'sunat/10734944101/rc/10734944101-RC-20260705-5.xml','sunat/10734944101/rc/R-RC-20260706-5.zip','El Resumen diario RC-20260705-5, ha sido aceptado','2026-07-06 19:00:46','2026-07-06 19:00:45','2026-07-06 19:03:02'),(8,1,1,'2026-07-06','2026-07-06',6,'RC-20260706-6','1783346509965','aceptado',2,'sunat/10734944101/rc/10734944101-RC-20260705-6.xml','sunat/10734944101/rc/R-RC-20260706-6.zip','El Resumen diario RC-20260705-6, ha sido aceptado','2026-07-06 19:01:19','2026-07-06 19:01:18','2026-07-06 19:02:59');
/*!40000 ALTER TABLE `boleta_summaries` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 22:08:28
