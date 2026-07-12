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

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(4) NOT NULL DEFAULT '0000',
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ubigeo` varchar(6) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_codigo_unique` (`company_id`,`codigo`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,1,'0000','Tienda Principal','Av. Principal 123, Lima',NULL,'01-7000000',1,1,'2026-06-25 19:01:48','2026-07-06 05:33:11'),(2,1,'0001','Sucursal Norte','Av. Túpac Amaru 456, Comas',NULL,NULL,0,1,'2026-06-25 19:01:48','2026-07-06 05:33:11');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:8:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"dashboard.ver\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:20:\"inventario.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:16:\"ventas.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:17:\"compras.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"caja.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"reportes.ver\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:5;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:18:\"usuarios.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:23:\"configuracion.gestionar\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"Administrador\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"Vendedor\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:10:\"Almacenero\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:6:\"Cajero\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:8:\"Contador\";s:1:\"c\";s:3:\"web\";}}}',1783726246);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `cash_session_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(10) NOT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'otro',
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `metodo_pago` varchar(15) NOT NULL DEFAULT 'efectivo',
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_movements_cash_session_id_foreign` (`cash_session_id`),
  KEY `cash_movements_user_id_foreign` (`user_id`),
  KEY `cash_movements_sale_id_foreign` (`sale_id`),
  KEY `cash_movements_purchase_id_foreign` (`purchase_id`),
  KEY `cash_mov_cs_idx` (`company_id`,`cash_session_id`),
  CONSTRAINT `cash_movements_cash_session_id_foreign` FOREIGN KEY (`cash_session_id`) REFERENCES `cash_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_movements_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_movements_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,1,1,1,'2026-06-26 05:20:01','ingreso','venta','Venta B001-000002',360.00,'efectivo',3,NULL,'B001-000002','2026-06-26 05:20:01','2026-06-26 05:20:01'),(2,1,1,1,'2026-06-26 05:21:06','egreso','pago_compra','Pago compra #3',1298.00,'efectivo',NULL,3,'','2026-06-26 05:21:06','2026-06-26 05:21:06'),(3,1,1,1,'2026-06-27 22:54:16','ingreso','venta','Venta B001-000003',36.00,'efectivo',4,NULL,'B001-000003','2026-06-27 22:54:16','2026-06-27 22:54:16'),(4,1,1,1,'2026-06-27 22:57:08','ingreso','venta','Venta NV01-000001',18.00,'efectivo',5,NULL,'NV01-000001','2026-06-27 22:57:08','2026-06-27 22:57:08'),(5,1,1,1,'2026-06-27 23:15:01','ingreso','venta','Venta B001-000004',330.00,'efectivo',6,NULL,'B001-000004','2026-06-27 23:15:01','2026-06-27 23:15:01'),(6,1,1,1,'2026-07-01 18:35:45','egreso','reembolso','Reembolso venta B001-000006',30.00,'efectivo',8,NULL,'B001-000006','2026-07-01 18:35:45','2026-07-01 18:35:45'),(7,1,1,1,'2026-07-05 05:12:49','ingreso','venta','Venta B001-000007',180.00,'efectivo',9,NULL,'B001-000007','2026-07-05 05:12:49','2026-07-05 05:12:49'),(8,1,1,1,'2026-07-05 05:15:15','egreso','pago_compra','Pago compra F004-',147.50,'efectivo',NULL,4,'F004-','2026-07-05 05:15:15','2026-07-05 05:15:15'),(9,1,2,1,'2026-07-08 03:20:03','egreso','reembolso','Reembolso venta F001-000002',180.00,'efectivo',21,NULL,'F001-000002','2026-07-08 03:20:03','2026-07-08 03:20:03'),(10,1,2,1,'2026-07-08 03:26:30','ingreso','venta','Venta B001-000020',3.50,'efectivo',23,NULL,'B001-000020','2026-07-08 03:26:30','2026-07-08 03:26:30'),(11,1,2,1,'2026-07-09 02:27:44','egreso','pago_compra','Pago compra #7',14.75,'efectivo',NULL,7,'','2026-07-09 02:27:44','2026-07-09 02:27:44'),(12,1,2,1,'2026-07-10 20:40:13','ingreso','venta','Venta B001-000021',1.50,'efectivo',24,NULL,'B001-000021','2026-07-10 20:40:13','2026-07-10 20:40:13');
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_registers`
--

DROP TABLE IF EXISTS `cash_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_registers_company_id_nombre_unique` (`company_id`,`nombre`),
  KEY `cash_registers_branch_id_foreign` (`branch_id`),
  CONSTRAINT `cash_registers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_registers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_registers`
--

LOCK TABLES `cash_registers` WRITE;
/*!40000 ALTER TABLE `cash_registers` DISABLE KEYS */;
INSERT INTO `cash_registers` VALUES (1,1,1,'Caja Principal',1,'2026-06-26 05:08:38','2026-06-26 05:08:38');
/*!40000 ALTER TABLE `cash_registers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_sessions`
--

DROP TABLE IF EXISTS `cash_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `cash_register_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `fecha_apertura` timestamp NOT NULL DEFAULT current_timestamp(),
  `monto_apertura` decimal(14,2) NOT NULL DEFAULT 0.00,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `monto_esperado` decimal(14,2) DEFAULT NULL,
  `monto_contado` decimal(14,2) DEFAULT NULL,
  `diferencia` decimal(14,2) DEFAULT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'abierta',
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_sessions_cash_register_id_foreign` (`cash_register_id`),
  KEY `cash_sessions_user_id_foreign` (`user_id`),
  KEY `cash_sessions_closed_by_foreign` (`closed_by`),
  KEY `cash_sessions_company_id_estado_index` (`company_id`,`estado`),
  CONSTRAINT `cash_sessions_cash_register_id_foreign` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_sessions_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_sessions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_sessions`
--

LOCK TABLES `cash_sessions` WRITE;
/*!40000 ALTER TABLE `cash_sessions` DISABLE KEYS */;
INSERT INTO `cash_sessions` VALUES (1,1,1,1,1,'2026-06-26 05:19:13',1.00,'2026-07-05 05:16:50',-550.50,0.00,550.50,'cerrada',NULL,'2026-06-26 05:19:13','2026-07-05 05:16:50'),(2,1,1,1,NULL,'2026-07-08 03:16:57',10.00,NULL,NULL,NULL,NULL,'abierta',NULL,'2026-07-08 03:16:57','2026-07-08 03:16:57');
/*!40000 ALTER TABLE `cash_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_company_id_nombre_unique` (`company_id`,`nombre`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,1,'Abarrotes',NULL,1,'2026-06-25 19:11:49','2026-07-05 05:09:18'),(2,1,'Bebidas',NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(3,1,'Limpieza',NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(4,1,'Cuidado personal',NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(5,1,'Lácteos',NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ruc` varchar(11) NOT NULL,
  `razon_social` varchar(255) NOT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ubigeo` varchar(6) DEFAULT NULL,
  `departamento` varchar(255) DEFAULT NULL,
  `provincia` varchar(255) DEFAULT NULL,
  `distrito` varchar(255) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `igv_porcentaje` decimal(5,2) NOT NULL DEFAULT 18.00,
  `sunat_modo` varchar(20) NOT NULL DEFAULT 'beta',
  `agente_percepcion` tinyint(1) NOT NULL DEFAULT 0,
  `percepcion_tasa` decimal(5,2) NOT NULL DEFAULT 2.00,
  `percepcion_regimen` varchar(2) NOT NULL DEFAULT '01',
  `sol_user` varchar(255) DEFAULT NULL,
  `sol_pass` text DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `certificate_password` text DEFAULT NULL,
  `gre_client_id` varchar(255) DEFAULT NULL,
  `gre_client_secret` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_ruc_unique` (`ruc`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'10734944101','EMPRESA DEMO COMERCIAL S.A.C.','Demo Comercial','Av. Principal 123, Lima','150101','LIMA','LIMA','LIMA','01-7000000','ventas@democomercial.pe',NULL,'PEN',18.00,'beta',0,2.00,'01','MODDATOS','eyJpdiI6ImQrUThodG5Yd3dtSGRPYnZzdHMrbGc9PSIsInZhbHVlIjoiUlBaMWdIV1FUa0FBbmNtZGNOWlFGdz09IiwibWFjIjoiMDk1ZTI2NDA3NTlkNGYwZDdjNGExYzMxNThjOWVjYjdiMGQ1NGE2MGFhYTU5YjcyYWM0ZDkwNzI3NDgzN2E1MiIsInRhZyI6IiJ9','sunat/10734944101/cert.pfx','eyJpdiI6IndHTFNMOVFjczZGUTBIZ3ZLVmxyYkE9PSIsInZhbHVlIjoiWTlyMTVCdXpmUzUxdnlvSFl6TzIvUT09IiwibWFjIjoiNDE0MGY0MjAyZThiYmVlMDRiMjJiZTVjNDM0ZWMyYWJlNGE0NzRhY2QxNzI3ZTM0ZTRiOTQ4MGI4ZWJjNjEzZCIsInRhZyI6IiJ9',NULL,NULL,1,'2026-06-25 19:01:48','2026-07-06 19:29:20');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tipo_documento` varchar(2) NOT NULL DEFAULT '1',
  `numero_documento` varchar(20) DEFAULT NULL,
  `razon_social` varchar(255) NOT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ubigeo` varchar(6) DEFAULT NULL,
  `estado_contribuyente` varchar(20) DEFAULT NULL,
  `condicion_domicilio` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_doc_unique` (`company_id`,`tipo_documento`,`numero_documento`),
  KEY `customers_company_id_razon_social_index` (`company_id`,`razon_social`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,1,'6','20512345678','COMERCIAL RAMÍREZ S.A.C.',NULL,'Av. Industrial 456, Lima',NULL,NULL,NULL,'ventas@ramirez.pe','01-5551234',1,'2026-06-25 19:38:31','2026-06-25 19:38:31'),(2,1,'6','20487654321','DISTRIBUIDORA SUR E.I.R.L.',NULL,'Jr. Comercio 789, Arequipa',NULL,NULL,NULL,NULL,NULL,1,'2026-06-25 19:38:31','2026-06-25 19:38:31'),(3,1,'1','45678912','María López Gutiérrez',NULL,NULL,NULL,NULL,NULL,NULL,'987654321',1,'2026-06-25 19:38:31','2026-06-25 19:38:31'),(4,1,'1','70123456','Carlos Fernández Ríos',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-06-25 19:38:31','2026-06-25 19:38:31');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `despatch_items`
--

DROP TABLE IF EXISTS `despatch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `despatch_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `despatch_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(14,4) NOT NULL,
  `unidad` varchar(5) NOT NULL DEFAULT 'NIU',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `despatch_items_despatch_id_foreign` (`despatch_id`),
  KEY `despatch_items_product_id_foreign` (`product_id`),
  CONSTRAINT `despatch_items_despatch_id_foreign` FOREIGN KEY (`despatch_id`) REFERENCES `despatches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `despatch_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `despatch_items`
--

LOCK TABLES `despatch_items` WRITE;
/*!40000 ALTER TABLE `despatch_items` DISABLE KEYS */;
INSERT INTO `despatch_items` VALUES (1,1,1,'Arroz Superior 5kg',2.0000,'NIU','2026-07-01 18:38:28','2026-07-01 18:38:28'),(2,2,NULL,'Aceite Vegetal 1L',1.0000,'NIU','2026-07-08 03:23:29','2026-07-08 03:23:29');
/*!40000 ALTER TABLE `despatch_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `despatches`
--

DROP TABLE IF EXISTS `despatches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `despatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `serie` varchar(5) NOT NULL DEFAULT 'T001',
  `correlativo` int(10) unsigned NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_traslado` date NOT NULL,
  `cod_traslado` varchar(2) NOT NULL DEFAULT '01',
  `des_traslado` varchar(255) NOT NULL,
  `mod_traslado` varchar(2) NOT NULL DEFAULT '02',
  `peso_total` decimal(12,3) NOT NULL DEFAULT 0.000,
  `und_peso` varchar(5) NOT NULL DEFAULT 'KGM',
  `num_bultos` int(10) unsigned NOT NULL DEFAULT 1,
  `dest_tipo_doc` varchar(2) NOT NULL DEFAULT '6',
  `dest_num_doc` varchar(20) DEFAULT NULL,
  `dest_nombre` varchar(255) NOT NULL,
  `partida_ubigeo` varchar(6) DEFAULT NULL,
  `partida_direccion` varchar(255) NOT NULL,
  `llegada_ubigeo` varchar(6) DEFAULT NULL,
  `llegada_direccion` varchar(255) NOT NULL,
  `transportista_doc` varchar(20) DEFAULT NULL,
  `transportista_nombre` varchar(255) DEFAULT NULL,
  `transportista_mtc` varchar(30) DEFAULT NULL,
  `vehiculo_placa` varchar(20) DEFAULT NULL,
  `chofer_tipo_doc` varchar(2) DEFAULT NULL,
  `chofer_num_doc` varchar(20) DEFAULT NULL,
  `chofer_licencia` varchar(20) DEFAULT NULL,
  `chofer_nombres` varchar(255) DEFAULT NULL,
  `chofer_apellidos` varchar(255) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `sunat_estado` varchar(15) NOT NULL DEFAULT 'no_enviado',
  `sunat_ticket` varchar(255) DEFAULT NULL,
  `sunat_xml_path` varchar(255) DEFAULT NULL,
  `sunat_cdr_path` varchar(255) DEFAULT NULL,
  `sunat_hash` varchar(255) DEFAULT NULL,
  `sunat_mensaje` text DEFAULT NULL,
  `sunat_enviado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `despatches_doc_unique` (`company_id`,`serie`,`correlativo`),
  KEY `despatches_branch_id_foreign` (`branch_id`),
  KEY `despatches_user_id_foreign` (`user_id`),
  KEY `despatches_sale_id_foreign` (`sale_id`),
  KEY `despatches_company_id_fecha_emision_index` (`company_id`,`fecha_emision`),
  CONSTRAINT `despatches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `despatches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `despatches_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `despatches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `despatches`
--

LOCK TABLES `despatches` WRITE;
/*!40000 ALTER TABLE `despatches` DISABLE KEYS */;
INSERT INTO `despatches` VALUES (1,1,1,1,NULL,'T001',1,'2026-07-06','2026-07-06','01','Venta','02',10.000,'KGM',1,'6','20000000001','CLIENTE DE PRUEBA S.A.C.','150101','AV. ORIGEN 123 - LIMA','150101','AV. DESTINO 456 - LIMA',NULL,NULL,NULL,'AAA111','1','44556677','Q44556677','JUAN','PEREZ','Prueba GRE beta','aceptado','test-76098040-4eef-4ce3-b82c-9c543104a1ac','sunat/10734944101/xml/20161515648-09-T001-1.xml','sunat/10734944101/cdr/R-20161515648-09-T001-1.zip','/1uKgMM1tXVXzfzi9Z/Ln+gahPs=','ACEPTADA','2026-07-06 19:25:52','2026-07-01 18:38:28','2026-07-06 19:25:56'),(2,1,1,1,22,'T001',2,'2026-07-07','2026-07-07','01','Venta','02',1.000,'KGM',1,'1','70123456','Carlos Fernández Ríos','150101','Av. Principal 123, Lima',NULL,'lima',NULL,NULL,NULL,'m253','1','45612332','142563','ferva vasquez',NULL,NULL,'no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-08 03:23:29','2026-07-08 03:23:29');
/*!40000 ALTER TABLE `despatches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(30) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `cantidad` decimal(15,4) NOT NULL,
  `costo_unitario` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `saldo_cantidad` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `saldo_costo_promedio` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `saldo_valor` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `referencia` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_product_id_foreign` (`product_id`),
  KEY `inventory_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `inventory_movements_user_id_foreign` (`user_id`),
  KEY `inv_mov_cpwf_idx` (`company_id`,`product_id`,`warehouse_id`,`fecha`),
  CONSTRAINT `inventory_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES (1,1,1,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',120.0000,22.0000,120.0000,22.0000,2640.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(2,1,2,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',80.0000,12.5000,80.0000,12.5000,1000.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(3,1,3,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',60.0000,14.0000,60.0000,14.0000,840.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(4,1,4,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',8.0000,13.0000,8.0000,13.0000,104.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(5,1,5,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',150.0000,9.0000,150.0000,9.0000,1350.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(6,1,6,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',25.0000,2.2000,25.0000,2.2000,55.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(7,1,7,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',200.0000,0.9000,200.0000,0.9000,180.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(8,1,8,1,NULL,'2026-06-25 19:11:49','entrada','Stock inicial',90.0000,2.8000,90.0000,2.8000,252.0000,'Seed','2026-06-25 19:11:49','2026-06-25 19:11:49'),(9,1,9,1,1,'2026-06-26 03:55:15','entrada','Stock inicial',20.0000,15.0000,20.0000,15.0000,300.0000,'Alta de producto','2026-06-26 03:55:15','2026-06-26 03:55:15'),(10,1,7,1,1,'2026-06-26 04:00:34','salida','Venta B001-000001',10.0000,0.9000,190.0000,0.9000,171.0000,'B001-000001','2026-06-26 04:00:34','2026-06-26 04:00:34'),(11,1,9,1,1,'2026-06-26 05:04:25','ajuste_entrada','Cambio',10.0000,15.0000,30.0000,15.0000,450.0000,NULL,'2026-06-26 05:04:25','2026-06-26 05:04:25'),(12,1,9,1,1,'2026-06-26 05:12:06','entrada','Compra a COMERCIALIZADORA LIMA NORTE S.A.',100.0000,10.0000,130.0000,11.1538,1450.0000,'Compra #1','2026-06-26 05:12:06','2026-06-26 05:12:06'),(13,1,2,1,1,'2026-06-26 05:12:06','entrada','Compra a COMERCIALIZADORA LIMA NORTE S.A.',1.0000,12.5000,81.0000,12.5000,1012.5000,'Compra #1','2026-06-26 05:12:06','2026-06-26 05:12:06'),(14,1,2,1,1,'2026-06-26 05:15:23','entrada','Compra a proveedor',100.0000,12.5000,181.0000,12.5000,2262.5000,'Compra #2','2026-06-26 05:15:23','2026-06-26 05:15:23'),(15,1,2,1,1,'2026-06-26 05:17:35','salida','Venta F001-000001',10.0000,12.5000,171.0000,12.5000,2137.5000,'F001-000001','2026-06-26 05:17:35','2026-06-26 05:17:35'),(16,1,2,1,1,'2026-06-26 05:20:01','salida','Venta B001-000002',20.0000,12.5000,151.0000,12.5000,1887.5000,'B001-000002','2026-06-26 05:20:01','2026-06-26 05:20:01'),(17,1,1,1,1,'2026-06-26 05:21:06','entrada','Compra a DISTRIBUIDORA MAYORISTA ANDINA S.A.C.',50.0000,22.0000,170.0000,22.0000,3740.0000,'Compra #3','2026-06-26 05:21:06','2026-06-26 05:21:06'),(18,1,2,1,1,'2026-06-27 22:54:16','salida','Venta B001-000003',2.0000,12.5000,149.0000,12.5000,1862.5000,'B001-000003','2026-06-27 22:54:16','2026-06-27 22:54:16'),(19,1,7,1,1,'2026-06-27 22:57:08','salida','Venta NV01-000001',12.0000,0.9000,178.0000,0.9000,160.2000,'NV01-000001','2026-06-27 22:57:08','2026-06-27 22:57:08'),(20,1,9,1,1,'2026-06-27 23:15:00','salida','Venta B001-000004',10.0000,11.1538,120.0000,11.1538,1338.4560,'B001-000004','2026-06-27 23:15:01','2026-06-27 23:15:01'),(21,1,2,1,1,'2026-06-27 23:15:01','salida','Venta B001-000004',10.0000,12.5000,139.0000,12.5000,1737.5000,'B001-000004','2026-06-27 23:15:01','2026-06-27 23:15:01'),(22,1,1,1,1,'2026-07-01 18:17:55','salida','Venta B001-000005',1.0000,22.0000,169.0000,22.0000,3718.0000,'B001-000005','2026-07-01 18:17:55','2026-07-01 18:17:55'),(23,1,1,1,1,'2026-07-01 18:19:07','salida','Venta B001-000006',1.0000,22.0000,168.0000,22.0000,3696.0000,'B001-000006','2026-07-01 18:19:07','2026-07-01 18:19:07'),(24,1,1,1,1,'2026-07-01 18:35:45','entrada','Anulación venta B001-000006',1.0000,22.0000,169.0000,22.0000,3718.0000,'BC01-000001','2026-07-01 18:35:45','2026-07-01 18:35:45'),(25,1,2,1,1,'2026-07-05 05:11:31','transferencia_salida','Almacén Principal → Almacén Secundario',50.0000,12.5000,89.0000,12.5000,1112.5000,'Transferencia #1','2026-07-05 05:11:31','2026-07-05 05:11:31'),(26,1,2,2,1,'2026-07-05 05:11:31','transferencia_entrada','Almacén Principal → Almacén Secundario',50.0000,12.5000,139.0000,12.5000,1737.5000,'Transferencia #1','2026-07-05 05:11:31','2026-07-05 05:11:31'),(27,1,2,1,1,'2026-07-05 05:12:49','salida','Venta B001-000007',10.0000,12.5000,129.0000,12.5000,1612.5000,'B001-000007','2026-07-05 05:12:49','2026-07-05 05:12:49'),(28,1,2,1,1,'2026-07-05 05:15:15','entrada','Compra a DISTRIBUIDORA MAYORISTA ANDINA S.A.C.',10.0000,12.5000,139.0000,12.5000,1737.5000,'Compra F004-','2026-07-05 05:15:15','2026-07-05 05:15:15'),(29,1,2,1,1,'2026-07-05 05:20:43','entrada','Anulación venta B001-000007',10.0000,12.5000,149.0000,12.5000,1862.5000,'BC01-000002','2026-07-05 05:20:43','2026-07-05 05:20:43'),(30,1,2,1,1,'2026-07-05 05:26:03','salida','Venta B001-000008',1.0000,12.5000,148.0000,12.5000,1850.0000,'B001-000008','2026-07-05 05:26:03','2026-07-05 05:26:03'),(31,1,2,1,1,'2026-07-05 22:25:42','salida','Venta B001-000009',1.0000,12.5000,147.0000,12.5000,1837.5000,'B001-000009','2026-07-05 22:25:42','2026-07-05 22:25:42'),(32,1,2,1,1,'2026-07-05 22:26:26','entrada','Compra a COMERCIALIZADORA LIMA NORTE S.A.',1.0000,12.5000,148.0000,12.5000,1850.0000,'Compra #5','2026-07-05 22:26:26','2026-07-05 22:26:26'),(33,1,9,1,1,'2026-07-06 04:55:19','salida','Venta B001-000010',2.0000,11.1538,118.0000,11.1538,1316.1484,'B001-000010','2026-07-06 04:55:19','2026-07-06 04:55:19'),(34,1,7,1,1,'2026-07-06 04:55:19','salida','Venta B001-000010',10.0000,0.9000,168.0000,0.9000,151.2000,'B001-000010','2026-07-06 04:55:19','2026-07-06 04:55:19'),(35,1,2,1,1,'2026-07-06 05:03:45','entrada','Compra a DISTRIBUIDORA MAYORISTA ANDINA S.A.C.',1.0000,12.5000,149.0000,12.5000,1862.5000,'Compra #6','2026-07-06 05:03:45','2026-07-06 05:03:45'),(36,1,2,1,1,'2026-07-06 05:28:28','salida','Venta B001-000011',2.0000,12.5000,147.0000,12.5000,1837.5000,'B001-000011','2026-07-06 05:28:28','2026-07-06 05:28:28'),(37,1,7,1,1,'2026-07-06 18:47:45','salida','Venta B001-000012',1.0000,0.9000,167.0000,0.9000,150.3000,'B001-000012','2026-07-06 18:47:45','2026-07-06 18:47:45'),(38,1,5,1,1,'2026-07-06 18:51:19','salida','Venta B001-000013',5.0000,9.0000,145.0000,9.0000,1305.0000,'B001-000013','2026-07-06 18:51:19','2026-07-06 18:51:19'),(39,1,6,1,1,'2026-07-06 18:52:14','salida','Venta B001-000014',4.0000,2.2000,21.0000,2.2000,46.2000,'B001-000014','2026-07-06 18:52:14','2026-07-06 18:52:14'),(40,1,9,1,1,'2026-07-06 19:00:29','salida','Venta B001-000015',15.0000,11.1538,103.0000,11.1538,1148.8414,'B001-000015','2026-07-06 19:00:29','2026-07-06 19:00:29'),(41,1,2,1,1,'2026-07-06 19:00:29','salida','Venta B001-000015',4.0000,12.5000,143.0000,12.5000,1787.5000,'B001-000015','2026-07-06 19:00:29','2026-07-06 19:00:29'),(42,1,7,1,1,'2026-07-06 19:00:41','salida','Venta B001-000016',1.0000,0.9000,166.0000,0.9000,149.4000,'B001-000016','2026-07-06 19:00:41','2026-07-06 19:00:41'),(43,1,4,1,1,'2026-07-06 19:00:41','salida','Venta B001-000016',1.0000,13.0000,7.0000,13.0000,91.0000,'B001-000016','2026-07-06 19:00:41','2026-07-06 19:00:41'),(44,1,1,1,1,'2026-07-06 19:01:03','salida','Venta B001-000017',1.0000,22.0000,168.0000,22.0000,3696.0000,'B001-000017','2026-07-06 19:01:03','2026-07-06 19:01:03'),(45,1,6,1,1,'2026-07-06 19:01:03','salida','Venta B001-000017',1.0000,2.2000,20.0000,2.2000,44.0000,'B001-000017','2026-07-06 19:01:03','2026-07-06 19:01:03'),(46,1,8,1,1,'2026-07-06 19:01:14','salida','Venta B001-000018',1.0000,2.8000,89.0000,2.8000,249.2000,'B001-000018','2026-07-06 19:01:14','2026-07-06 19:01:14'),(47,1,4,1,1,'2026-07-06 19:01:14','salida','Venta B001-000018',1.0000,13.0000,6.0000,13.0000,78.0000,'B001-000018','2026-07-06 19:01:14','2026-07-06 19:01:14'),(48,1,2,1,1,'2026-07-08 03:08:51','entrada','Ingreso lote 252525225211',1000.0000,12.5000,1143.0000,12.5000,14287.5000,'252525225211','2026-07-08 03:08:51','2026-07-08 03:08:51'),(49,1,2,2,1,'2026-07-08 03:13:39','salida','Venta F001-000002',10.0000,12.5000,1133.0000,12.5000,14162.5000,'F001-000002','2026-07-08 03:13:39','2026-07-08 03:13:39'),(50,1,2,1,1,'2026-07-08 03:14:53','salida','Venta B001-000019',1.0000,12.5000,1132.0000,12.5000,14150.0000,'B001-000019','2026-07-08 03:14:53','2026-07-08 03:14:53'),(51,1,2,2,1,'2026-07-08 03:20:03','entrada','Anulación venta F001-000002',10.0000,12.5000,1142.0000,12.5000,14275.0000,'FC01-000001','2026-07-08 03:20:03','2026-07-08 03:20:03'),(52,1,6,1,1,'2026-07-08 03:26:30','salida','Venta B001-000020',1.0000,2.2000,19.0000,2.2000,41.8000,'B001-000020','2026-07-08 03:26:30','2026-07-08 03:26:30'),(53,1,2,1,1,'2026-07-09 02:27:44','entrada','Compra a DISTRIBUIDORA MAYORISTA ANDINA S.A.C.',1.0000,12.5000,1143.0000,12.5000,14287.5000,'Compra #7','2026-07-09 02:27:44','2026-07-09 02:27:44'),(54,1,7,1,1,'2026-07-10 20:40:13','salida','Venta B001-000021',1.0000,0.9000,165.0000,0.9000,148.5000,'B001-000021','2026-07-10 20:40:13','2026-07-10 20:40:13');
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_25_100001_create_companies_table',1),(5,'2026_06_25_100002_create_branches_table',1),(6,'2026_06_25_100003_add_erp_fields_to_users_table',1),(7,'2026_06_25_200001_create_categories_table',1),(8,'2026_06_25_200002_create_units_table',1),(9,'2026_06_25_200003_create_warehouses_table',1),(10,'2026_06_25_200004_create_products_table',1),(11,'2026_06_25_200005_create_product_stocks_table',1),(12,'2026_06_25_200006_create_inventory_movements_table',1),(13,'2026_06_25_300001_create_customers_table',1),(14,'2026_06_25_300002_create_sales_table',1),(15,'2026_06_25_300003_create_sale_items_table',1),(16,'2026_06_25_400001_create_suppliers_table',1),(17,'2026_06_25_400002_create_purchases_table',1),(18,'2026_06_25_400003_create_purchase_items_table',1),(19,'2026_06_25_400004_create_purchase_payments_table',1),(20,'2026_06_25_500001_create_cash_registers_table',1),(21,'2026_06_25_500002_create_cash_sessions_table',1),(22,'2026_06_25_500003_create_cash_movements_table',1),(23,'2026_06_25_500004_create_sale_payments_table',1),(24,'2026_06_25_500005_add_monto_cobrado_to_sales_table',1),(25,'2026_06_26_135154_create_permission_tables',1),(26,'2026_06_26_140001_add_sunat_fields_to_sales_table',1),(27,'2026_06_26_140002_add_sunat_credentials_to_companies_table',1),(28,'2026_06_26_150001_create_boleta_summaries_table',1),(29,'2026_06_26_150001_create_transfers_table',1),(30,'2026_06_26_150002_add_boleta_summary_to_sales_table',1),(31,'2026_06_26_150002_create_transfer_items_table',1),(32,'2026_06_26_160001_create_notes_table',1),(33,'2026_06_26_160002_create_note_items_table',1),(34,'2026_06_26_170001_create_despatches_table',1),(35,'2026_06_26_170002_create_despatch_items_table',1),(36,'2026_06_26_180001_create_quotes_table',1),(37,'2026_06_26_180002_create_quote_items_table',1),(38,'2026_06_26_190001_add_controla_lotes_to_products_table',1),(39,'2026_06_26_190002_create_product_lots_table',1),(40,'2026_06_27_000001_add_sale_item_id_to_note_items_table',1),(41,'2026_06_27_120001_add_certificate_password_to_companies_table',1),(42,'2026_06_27_130001_add_percepcion_fields',1),(43,'2026_06_27_160001_add_tipo_afectacion_igv',1),(44,'2026_06_27_170001_create_voided_documents_table',1),(45,'2026_07_05_120001_add_percepcion_to_purchases',1),(46,'2026_07_05_130001_add_percepcion_tasa_to_sales',1),(47,'2026_07_06_140001_widen_sunat_mensaje_columns',1),(48,'2026_07_06_150001_add_gre_credentials',1),(49,'2026_07_10_090001_add_cliente_direccion_to_sales_table',2),(50,'2026_07_10_090002_add_contribuyente_status_to_customers_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note_items`
--

DROP TABLE IF EXISTS `note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `sale_item_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(14,4) NOT NULL,
  `precio_unitario` decimal(14,4) NOT NULL,
  `descuento` decimal(14,2) NOT NULL DEFAULT 0.00,
  `afecto_igv` tinyint(1) NOT NULL DEFAULT 1,
  `base` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `note_items_note_id_foreign` (`note_id`),
  KEY `note_items_product_id_foreign` (`product_id`),
  KEY `note_items_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `note_items_note_id_foreign` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `note_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `note_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_items`
--

LOCK TABLES `note_items` WRITE;
/*!40000 ALTER TABLE `note_items` DISABLE KEYS */;
INSERT INTO `note_items` VALUES (1,1,1,9,'Arroz Superior 5kg',1.0000,30.0000,0.00,1,25.42,4.58,30.00,'2026-07-01 18:35:45','2026-07-01 18:35:45'),(2,2,2,10,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,152.54,27.46,180.00,'2026-07-05 05:20:43','2026-07-05 05:20:43'),(3,3,2,27,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,152.54,27.46,180.00,'2026-07-08 03:20:03','2026-07-08 03:20:03');
/*!40000 ALTER TABLE `note_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `tipo` varchar(10) NOT NULL,
  `tipo_doc` varchar(2) NOT NULL,
  `serie` varchar(5) NOT NULL,
  `correlativo` int(10) unsigned NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `cod_motivo` varchar(2) NOT NULL,
  `des_motivo` varchar(255) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sunat_estado` varchar(15) NOT NULL DEFAULT 'no_enviado',
  `sunat_xml_path` varchar(255) DEFAULT NULL,
  `sunat_cdr_path` varchar(255) DEFAULT NULL,
  `sunat_hash` varchar(255) DEFAULT NULL,
  `sunat_mensaje` text DEFAULT NULL,
  `sunat_enviado_at` timestamp NULL DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notes_comprobante_unique` (`company_id`,`tipo_doc`,`serie`,`correlativo`),
  KEY `notes_branch_id_foreign` (`branch_id`),
  KEY `notes_user_id_foreign` (`user_id`),
  KEY `notes_sale_id_foreign` (`sale_id`),
  KEY `notes_company_id_fecha_index` (`company_id`,`fecha`),
  CONSTRAINT `notes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (1,1,1,1,8,'credito','07','BC01',1,'2026-07-01 18:35:45','PEN','01','Anulación de la operación',25.42,4.58,30.00,'aceptado','sunat/10734944101/xml/10734944101-07-BC01-1.xml','sunat/10734944101/cdr/R-10734944101-07-BC01-1.zip','CXjBZ2dVXSSQWK/3831+Y+cX0qU=','La Nota de Credito numero BC01-1, ha sido aceptada','2026-07-01 18:35:54',NULL,'2026-07-01 18:35:45','2026-07-01 18:35:54'),(2,1,1,1,9,'credito','07','BC01',2,'2026-07-05 05:20:43','PEN','09','Disminución en el valor',152.54,27.46,180.00,'aceptado','sunat/10734944101/xml/10734944101-07-BC01-2.xml','sunat/10734944101/cdr/R-10734944101-07-BC01-2.zip','xczr+QMq6bjcnTKUwirkNoCrjz4=','La Nota de Credito numero BC01-2, ha sido aceptada','2026-07-05 05:20:44',NULL,'2026-07-05 05:20:43','2026-07-05 05:20:44'),(3,1,1,1,21,'credito','07','FC01',1,'2026-07-08 03:20:03','PEN','01','Anulación de la operación',152.54,27.46,180.00,'no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-08 03:20:03','2026-07-08 03:20:03');
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.ver','web','2026-06-26 18:52:24','2026-06-26 18:52:24'),(2,'inventario.gestionar','web','2026-06-26 18:52:24','2026-06-26 18:52:24'),(3,'ventas.gestionar','web','2026-06-26 18:52:24','2026-06-26 18:52:24'),(4,'compras.gestionar','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(5,'caja.gestionar','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(6,'reportes.ver','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(7,'usuarios.gestionar','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(8,'configuracion.gestionar','web','2026-06-26 18:52:25','2026-06-26 18:52:25');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_lots`
--

DROP TABLE IF EXISTS `product_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_lots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `lote` varchar(50) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_lots_unique` (`product_id`,`warehouse_id`,`lote`),
  KEY `product_lots_warehouse_id_foreign` (`warehouse_id`),
  KEY `product_lots_company_id_fecha_vencimiento_index` (`company_id`,`fecha_vencimiento`),
  CONSTRAINT `product_lots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_lots_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_lots_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_lots`
--

LOCK TABLES `product_lots` WRITE;
/*!40000 ALTER TABLE `product_lots` DISABLE KEYS */;
INSERT INTO `product_lots` VALUES (1,1,2,1,'252525225211','2026-07-08',1000.0000,'2026-07-08 03:08:51','2026-07-08 03:08:51');
/*!40000 ALTER TABLE `product_lots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stocks`
--

DROP TABLE IF EXISTS `product_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `cantidad` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_stocks_product_id_warehouse_id_unique` (`product_id`,`warehouse_id`),
  KEY `product_stocks_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `product_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stocks`
--

LOCK TABLES `product_stocks` WRITE;
/*!40000 ALTER TABLE `product_stocks` DISABLE KEYS */;
INSERT INTO `product_stocks` VALUES (1,1,1,168.0000,'2026-06-25 19:11:49','2026-07-06 19:01:03'),(2,2,1,1093.0000,'2026-06-25 19:11:49','2026-07-09 02:27:44'),(3,3,1,60.0000,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(4,4,1,6.0000,'2026-06-25 19:11:49','2026-07-06 19:01:14'),(5,5,1,145.0000,'2026-06-25 19:11:49','2026-07-06 18:51:19'),(6,6,1,19.0000,'2026-06-25 19:11:49','2026-07-08 03:26:30'),(7,7,1,165.0000,'2026-06-25 19:11:49','2026-07-10 20:40:13'),(8,8,1,89.0000,'2026-06-25 19:11:49','2026-07-06 19:01:14'),(9,9,1,103.0000,'2026-06-26 03:55:15','2026-07-06 19:00:29'),(10,2,2,50.0000,'2026-07-05 05:11:31','2026-07-08 03:20:03');
/*!40000 ALTER TABLE `product_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `codigo` varchar(50) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(10) NOT NULL DEFAULT 'bien',
  `precio_compra` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `precio_venta` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `afecto_igv` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_afectacion_igv` varchar(2) NOT NULL DEFAULT '10',
  `costo_promedio` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `stock_minimo` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `controla_stock` tinyint(1) NOT NULL DEFAULT 1,
  `controla_lotes` tinyint(1) NOT NULL DEFAULT 0,
  `imagen_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_company_id_codigo_unique` (`company_id`,`codigo`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_company_id_codigo_barras_index` (`company_id`,`codigo_barras`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,1,'P0001',NULL,'Arroz Superior 5kg',NULL,'bien',22.0000,30.0000,1,'10',22.0000,20.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(2,1,1,1,'P0002',NULL,'Aceite Vegetal 1L',NULL,'bien',12.5000,18.0000,1,'10',12.5000,15.0000,1,1,NULL,1,'2026-06-25 19:11:49','2026-07-08 03:08:51'),(3,1,2,1,'P0003',NULL,'Gaseosa 3L',NULL,'bien',14.0000,23.0000,1,'10',14.0000,24.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(4,1,3,1,'P0004',NULL,'Detergente 2kg',NULL,'bien',13.0000,20.0000,1,'10',13.0000,10.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(5,1,5,1,'P0005',NULL,'Leche Evaporada 400g',NULL,'bien',9.0000,12.5000,1,'10',9.0000,30.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(6,1,4,1,'P0006',NULL,'Jabón de tocador',NULL,'bien',2.2000,3.5000,1,'10',2.2000,40.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(7,1,2,1,'P0007',NULL,'Agua mineral 625ml',NULL,'bien',0.9000,1.5000,1,'10',0.9000,50.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(8,1,1,1,'P0008',NULL,'Fideos spaghetti 500g',NULL,'bien',2.8000,4.2000,1,'10',2.8000,30.0000,1,0,NULL,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(9,1,5,2,'12',NULL,'Milo',NULL,'bien',10.0000,15.0000,1,'10',11.1538,2.0000,1,0,NULL,1,'2026-06-26 03:55:15','2026-06-26 05:12:06');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(14,4) NOT NULL,
  `costo_unitario` decimal(14,4) NOT NULL,
  `descuento` decimal(14,2) NOT NULL DEFAULT 0.00,
  `afecto_igv` tinyint(1) NOT NULL DEFAULT 1,
  `base` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,9,'Milo',100.0000,10.0000,0.00,1,1000.00,180.00,1180.00,'2026-06-26 05:12:06','2026-06-26 05:12:06'),(2,1,2,'Aceite Vegetal 1L',1.0000,12.5000,0.00,1,12.50,2.25,14.75,'2026-06-26 05:12:06','2026-06-26 05:12:06'),(3,2,2,'Aceite Vegetal 1L',100.0000,12.5000,1.00,1,1249.00,224.82,1473.82,'2026-06-26 05:15:23','2026-06-26 05:15:23'),(4,3,1,'Arroz Superior 5kg',50.0000,22.0000,0.00,1,1100.00,198.00,1298.00,'2026-06-26 05:21:06','2026-06-26 05:21:06'),(5,4,2,'Aceite Vegetal 1L',10.0000,12.5000,0.00,1,125.00,22.50,147.50,'2026-07-05 05:15:15','2026-07-05 05:15:15'),(6,5,2,'Aceite Vegetal 1L',1.0000,12.5000,0.00,1,12.50,2.25,14.75,'2026-07-05 22:26:26','2026-07-05 22:26:26'),(7,6,2,'Aceite Vegetal 1L',1.0000,12.5000,0.00,1,12.50,2.25,14.75,'2026-07-06 05:03:45','2026-07-06 05:03:45'),(8,7,2,'Aceite Vegetal 1L',1.0000,12.5000,0.00,1,12.50,2.25,14.75,'2026-07-09 02:27:44','2026-07-09 02:27:44');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_payments`
--

DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `metodo_pago` varchar(15) NOT NULL DEFAULT 'efectivo',
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_payments_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_payments_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_payments`
--

LOCK TABLES `purchase_payments` WRITE;
/*!40000 ALTER TABLE `purchase_payments` DISABLE KEYS */;
INSERT INTO `purchase_payments` VALUES (1,1,1,'2026-06-26',1194.75,'efectivo','Pago al contado','2026-06-26 05:12:06','2026-06-26 05:12:06'),(2,3,1,'2026-06-26',1298.00,'efectivo','Pago al contado','2026-06-26 05:21:06','2026-06-26 05:21:06'),(3,4,1,'2026-07-05',147.50,'efectivo','Pago al contado','2026-07-05 05:15:15','2026-07-05 05:15:15'),(4,2,1,'2026-07-05',1473.82,'transferencia',NULL,'2026-07-05 05:15:49','2026-07-05 05:15:49'),(5,5,1,'2026-07-05',14.75,'efectivo','Pago al contado','2026-07-05 22:26:26','2026-07-05 22:26:26'),(6,6,1,'2026-07-06',14.90,'efectivo','Pago al contado','2026-07-06 05:03:45','2026-07-06 05:03:45'),(7,7,1,'2026-07-08',15.05,'efectivo','Pago al contado','2026-07-09 02:27:44','2026-07-09 02:27:44');
/*!40000 ALTER TABLE `purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_comprobante` varchar(15) NOT NULL DEFAULT 'factura',
  `serie` varchar(10) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `fecha` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `percepcion_tasa` decimal(5,2) NOT NULL DEFAULT 0.00,
  `percepcion_monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_pagado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado_pago` varchar(12) NOT NULL DEFAULT 'pendiente',
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchases_branch_id_foreign` (`branch_id`),
  KEY `purchases_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  KEY `purchases_company_id_fecha_index` (`company_id`,`fecha`),
  KEY `purchases_company_id_estado_pago_index` (`company_id`,`estado_pago`),
  CONSTRAINT `purchases_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,1,1,1,3,1,'boleta',NULL,NULL,'2026-06-26',NULL,'PEN',1012.50,182.25,1194.75,0.00,0.00,1194.75,'pagada',NULL,'2026-06-26 05:12:06','2026-06-26 05:12:06'),(2,1,1,1,NULL,1,'factura',NULL,NULL,'2026-06-26','2026-06-26','PEN',1249.00,224.82,1473.82,0.00,0.00,1473.82,'pagada',NULL,'2026-06-26 05:15:23','2026-07-05 05:15:49'),(3,1,1,1,1,1,'boleta',NULL,NULL,'2026-06-26',NULL,'PEN',1100.00,198.00,1298.00,0.00,0.00,1298.00,'pagada',NULL,'2026-06-26 05:21:06','2026-06-26 05:21:06'),(4,1,1,1,1,1,'factura','F004',NULL,'2026-07-05',NULL,'PEN',125.00,22.50,147.50,0.00,0.00,147.50,'pagada',NULL,'2026-07-05 05:15:15','2026-07-05 05:15:15'),(5,1,1,1,3,1,'factura',NULL,NULL,'2026-07-05',NULL,'PEN',12.50,2.25,14.75,0.00,0.00,14.75,'pagada',NULL,'2026-07-05 22:26:26','2026-07-05 22:26:26'),(6,1,1,1,1,1,'factura',NULL,NULL,'2026-07-06',NULL,'PEN',12.50,2.25,14.75,1.00,0.15,14.90,'pagada',NULL,'2026-07-06 05:03:45','2026-07-06 05:03:45'),(7,1,1,1,1,1,'factura',NULL,NULL,'2026-07-08',NULL,'PEN',12.50,2.25,14.75,2.00,0.30,15.05,'pagada',NULL,'2026-07-09 02:27:44','2026-07-09 02:27:44');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_items`
--

DROP TABLE IF EXISTS `quote_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quote_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(14,4) NOT NULL,
  `precio_unitario` decimal(14,4) NOT NULL,
  `descuento` decimal(14,2) NOT NULL DEFAULT 0.00,
  `afecto_igv` tinyint(1) NOT NULL DEFAULT 1,
  `base` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quote_items_quote_id_foreign` (`quote_id`),
  KEY `quote_items_product_id_foreign` (`product_id`),
  CONSTRAINT `quote_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quote_items_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_items`
--

LOCK TABLES `quote_items` WRITE;
/*!40000 ALTER TABLE `quote_items` DISABLE KEYS */;
INSERT INTO `quote_items` VALUES (1,1,2,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,152.54,27.46,180.00,'2026-07-05 05:13:43','2026-07-05 05:13:43'),(2,2,2,'Aceite Vegetal 1L',40.0000,18.0000,0.00,1,610.17,109.83,720.00,'2026-07-05 05:14:18','2026-07-05 05:14:18');
/*!40000 ALTER TABLE `quote_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `tipo` varchar(12) NOT NULL,
  `serie` varchar(6) NOT NULL,
  `correlativo` int(10) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `fecha_validez` date DEFAULT NULL,
  `moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `cliente_documento` varchar(20) DEFAULT NULL,
  `cliente_nombre` varchar(255) NOT NULL DEFAULT 'Sin cliente',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `descuento_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(12) NOT NULL DEFAULT 'pendiente',
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotes_doc_unique` (`company_id`,`tipo`,`serie`,`correlativo`),
  KEY `quotes_branch_id_foreign` (`branch_id`),
  KEY `quotes_user_id_foreign` (`user_id`),
  KEY `quotes_customer_id_foreign` (`customer_id`),
  KEY `quotes_sale_id_foreign` (`sale_id`),
  KEY `quotes_company_id_tipo_estado_index` (`company_id`,`tipo`,`estado`),
  CONSTRAINT `quotes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotes`
--

LOCK TABLES `quotes` WRITE;
/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (1,1,1,1,4,'cotizacion','COT1',1,'2026-07-05',NULL,'PEN','70123456','Carlos Fernández Ríos',152.54,27.46,0.00,180.00,'pendiente',NULL,NULL,'2026-07-05 05:13:43','2026-07-05 05:13:43'),(2,1,1,1,4,'pedido','PED1',1,'2026-07-05',NULL,'PEN','70123456','Carlos Fernández Ríos',610.17,109.83,0.00,720.00,'pendiente',NULL,NULL,'2026-07-05 05:14:18','2026-07-05 05:14:18');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(2,1),(2,3),(3,1),(3,2),(3,4),(4,1),(4,3),(4,5),(5,1),(5,4),(5,5),(6,1),(6,5),(7,1),(8,1);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(2,'Vendedor','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(3,'Almacenero','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(4,'Cajero','web','2026-06-26 18:52:25','2026-06-26 18:52:25'),(5,'Contador','web','2026-06-26 18:52:25','2026-06-26 18:52:25');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(14,4) NOT NULL,
  `precio_unitario` decimal(14,4) NOT NULL,
  `descuento` decimal(14,2) NOT NULL DEFAULT 0.00,
  `afecto_igv` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_afectacion` varchar(2) NOT NULL DEFAULT '10',
  `base` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `costo_unitario` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,1,7,'Agua mineral 625ml',10.0000,1.5000,0.00,1,'10',12.71,2.29,15.00,0.9000,'2026-06-26 04:00:34','2026-06-26 04:00:34'),(2,2,2,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,'10',152.54,27.46,180.00,12.5000,'2026-06-26 05:17:35','2026-06-26 05:17:35'),(3,3,2,'Aceite Vegetal 1L',20.0000,18.0000,0.00,1,'10',305.08,54.92,360.00,12.5000,'2026-06-26 05:20:01','2026-06-26 05:20:01'),(4,4,2,'Aceite Vegetal 1L',2.0000,18.0000,0.00,1,'10',30.51,5.49,36.00,12.5000,'2026-06-27 22:54:16','2026-06-27 22:54:16'),(5,5,7,'Agua mineral 625ml',12.0000,1.5000,0.00,1,'10',15.25,2.75,18.00,0.9000,'2026-06-27 22:57:08','2026-06-27 22:57:08'),(6,6,9,'Milo',10.0000,15.0000,0.00,1,'10',127.12,22.88,150.00,11.1538,'2026-06-27 23:15:00','2026-06-27 23:15:00'),(7,6,2,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,'10',152.54,27.46,180.00,12.5000,'2026-06-27 23:15:01','2026-06-27 23:15:01'),(8,7,1,'Arroz Superior 5kg',1.0000,30.0000,0.00,1,'10',25.42,4.58,30.00,22.0000,'2026-07-01 18:17:55','2026-07-01 18:17:55'),(9,8,1,'Arroz Superior 5kg',1.0000,30.0000,0.00,1,'10',25.42,4.58,30.00,22.0000,'2026-07-01 18:19:07','2026-07-01 18:19:07'),(10,9,2,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,'10',152.54,27.46,180.00,12.5000,'2026-07-05 05:12:49','2026-07-05 05:12:49'),(11,10,2,'Aceite Vegetal 1L',1.0000,18.0000,0.00,1,'10',15.25,2.75,18.00,12.5000,'2026-07-05 05:26:03','2026-07-05 05:26:03'),(12,11,2,'Aceite Vegetal 1L',1.0000,18.0000,0.00,1,'10',15.25,2.75,18.00,12.5000,'2026-07-05 22:25:42','2026-07-05 22:25:42'),(13,12,9,'Milo',2.0000,15.0000,0.00,1,'10',25.42,4.58,30.00,11.1538,'2026-07-06 04:55:19','2026-07-06 04:55:19'),(14,12,7,'Agua mineral 625ml',10.0000,1.5000,0.00,1,'10',12.71,2.29,15.00,0.9000,'2026-07-06 04:55:19','2026-07-06 04:55:19'),(15,13,2,'Aceite Vegetal 1L',2.0000,18.0000,0.00,1,'10',30.51,5.49,36.00,12.5000,'2026-07-06 05:28:28','2026-07-06 05:28:28'),(16,14,7,'Agua mineral 625ml',1.0000,1.5000,0.00,1,'10',1.27,0.23,1.50,0.9000,'2026-07-06 18:47:45','2026-07-06 18:47:45'),(17,15,5,'Leche Evaporada 400g',5.0000,12.5000,0.00,1,'10',52.97,9.53,62.50,9.0000,'2026-07-06 18:51:19','2026-07-06 18:51:19'),(18,16,6,'Jabón de tocador',4.0000,3.5000,0.00,1,'10',11.86,2.14,14.00,2.2000,'2026-07-06 18:52:14','2026-07-06 18:52:14'),(19,17,9,'Milo',15.0000,15.0000,0.00,1,'10',190.68,34.32,225.00,11.1538,'2026-07-06 19:00:29','2026-07-06 19:00:29'),(20,17,2,'Aceite Vegetal 1L',4.0000,18.0000,0.00,1,'10',61.02,10.98,72.00,12.5000,'2026-07-06 19:00:29','2026-07-06 19:00:29'),(21,18,7,'Agua mineral 625ml',1.0000,1.5000,0.00,1,'10',1.27,0.23,1.50,0.9000,'2026-07-06 19:00:41','2026-07-06 19:00:41'),(22,18,4,'Detergente 2kg',1.0000,20.0000,0.00,1,'10',16.95,3.05,20.00,13.0000,'2026-07-06 19:00:41','2026-07-06 19:00:41'),(23,19,1,'Arroz Superior 5kg',1.0000,30.0000,0.00,1,'10',25.42,4.58,30.00,22.0000,'2026-07-06 19:01:03','2026-07-06 19:01:03'),(24,19,6,'Jabón de tocador',1.0000,3.5000,0.00,1,'10',2.97,0.53,3.50,2.2000,'2026-07-06 19:01:03','2026-07-06 19:01:03'),(25,20,8,'Fideos spaghetti 500g',1.0000,4.2000,0.00,1,'10',3.56,0.64,4.20,2.8000,'2026-07-06 19:01:14','2026-07-06 19:01:14'),(26,20,4,'Detergente 2kg',1.0000,20.0000,0.00,1,'10',16.95,3.05,20.00,13.0000,'2026-07-06 19:01:14','2026-07-06 19:01:14'),(27,21,2,'Aceite Vegetal 1L',10.0000,18.0000,0.00,1,'10',152.54,27.46,180.00,12.5000,'2026-07-08 03:13:39','2026-07-08 03:13:39'),(28,22,2,'Aceite Vegetal 1L',1.0000,18.0000,0.00,1,'10',15.25,2.75,18.00,12.5000,'2026-07-08 03:14:53','2026-07-08 03:14:53'),(29,23,6,'Jabón de tocador',1.0000,3.5000,0.00,1,'10',2.97,0.53,3.50,2.2000,'2026-07-08 03:26:30','2026-07-08 03:26:30'),(30,24,7,'Agua mineral 625ml',1.0000,1.5000,0.00,1,'10',1.27,0.23,1.50,0.9000,'2026-07-10 20:40:13','2026-07-10 20:40:13');
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_payments`
--

DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `metodo_pago` varchar(15) NOT NULL DEFAULT 'efectivo',
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_payments_sale_id_foreign` (`sale_id`),
  KEY `sale_payments_user_id_foreign` (`user_id`),
  CONSTRAINT `sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_payments`
--

LOCK TABLES `sale_payments` WRITE;
/*!40000 ALTER TABLE `sale_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `boleta_summary_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_comprobante` varchar(15) NOT NULL DEFAULT 'boleta',
  `serie` varchar(5) NOT NULL,
  `correlativo` int(10) unsigned NOT NULL,
  `cliente_documento` varchar(20) DEFAULT NULL,
  `cliente_nombre` varchar(255) NOT NULL DEFAULT 'Consumidor final',
  `cliente_direccion` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `moneda` varchar(3) NOT NULL DEFAULT 'PEN',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(14,2) NOT NULL DEFAULT 0.00,
  `descuento_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `aplica_percepcion` tinyint(1) NOT NULL DEFAULT 0,
  `percepcion_tasa` decimal(5,2) NOT NULL DEFAULT 0.00,
  `percepcion_monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto_cobrado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(12) NOT NULL DEFAULT 'pagada',
  `sunat_estado` varchar(15) NOT NULL DEFAULT 'no_enviado',
  `sunat_xml_path` varchar(255) DEFAULT NULL,
  `sunat_cdr_path` varchar(255) DEFAULT NULL,
  `sunat_hash` varchar(255) DEFAULT NULL,
  `sunat_ticket` varchar(255) DEFAULT NULL,
  `sunat_mensaje` text DEFAULT NULL,
  `sunat_enviado_at` timestamp NULL DEFAULT NULL,
  `metodo_pago` varchar(15) NOT NULL DEFAULT 'efectivo',
  `monto_recibido` decimal(14,2) NOT NULL DEFAULT 0.00,
  `vuelto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_comprobante_unique` (`company_id`,`tipo_comprobante`,`serie`,`correlativo`),
  KEY `sales_branch_id_foreign` (`branch_id`),
  KEY `sales_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  KEY `sales_company_id_fecha_index` (`company_id`,`fecha`),
  KEY `sales_boleta_summary_id_foreign` (`boleta_summary_id`),
  CONSTRAINT `sales_boleta_summary_id_foreign` FOREIGN KEY (`boleta_summary_id`) REFERENCES `boleta_summaries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,NULL,1,1,1,NULL,1,'boleta','B001',1,NULL,'Consumidor final',NULL,'2026-06-26 04:00:34','PEN',12.71,2.29,0.00,15.00,0,0.00,0.00,0.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',16.00,1.00,NULL,'2026-06-26 04:00:34','2026-06-26 04:00:34'),(2,NULL,1,1,1,2,1,'factura','F001',1,'20487654321','DISTRIBUIDORA SUR E.I.R.L.',NULL,'2026-06-26 05:17:35','PEN',152.54,27.46,0.00,180.00,0,0.00,0.00,0.00,'pendiente','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'credito',0.00,0.00,NULL,'2026-06-26 05:17:35','2026-06-26 05:17:35'),(3,NULL,1,1,1,3,1,'boleta','B001',2,'45678912','María López Gutiérrez',NULL,'2026-06-26 05:20:01','PEN',305.08,54.92,0.00,360.00,0,0.00,0.00,360.00,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-2.xml','sunat/10734944101/cdr/R-10734944101-03-B001-2.zip','j5utcL7EhsW+r6OWSrPxxmuuN5U=',NULL,'La Boleta numero B001-2, ha sido aceptada','2026-06-27 22:47:10','efectivo',0.00,0.00,NULL,'2026-06-26 05:20:01','2026-06-27 22:47:10'),(4,NULL,1,1,1,4,1,'boleta','B001',3,'70123456','Carlos Fernández Ríos',NULL,'2026-06-27 22:54:16','PEN',30.51,5.49,0.00,36.00,0,0.00,0.00,36.00,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-3.xml','sunat/10734944101/cdr/R-10734944101-03-B001-3.zip','rFr+RUA/ZHzRovxcv/q1TJ2jkYs=',NULL,'La Boleta numero B001-3, ha sido aceptada','2026-06-27 22:55:13','efectivo',0.00,0.00,NULL,'2026-06-27 22:54:16','2026-06-27 22:55:13'),(5,NULL,1,1,1,4,1,'nota_venta','NV01',1,'70123456','Carlos Fernández Ríos',NULL,'2026-06-27 22:57:08','PEN',15.25,2.75,0.00,18.00,0,0.00,0.00,18.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',0.00,0.00,NULL,'2026-06-27 22:57:08','2026-06-27 22:57:08'),(6,NULL,1,1,1,4,1,'boleta','B001',4,'70123456','Carlos Fernández Ríos',NULL,'2026-06-27 23:15:00','PEN',279.66,50.34,0.00,330.00,0,0.00,0.00,330.00,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-4.xml','sunat/10734944101/cdr/R-10734944101-03-B001-4.zip','4Vj9eDuNk+/JR9+gHZ9abIYJmYU=',NULL,'La Boleta numero B001-4, ha sido aceptada','2026-06-27 23:16:25','efectivo',0.00,0.00,NULL,'2026-06-27 23:15:00','2026-06-27 23:16:25'),(7,NULL,1,1,1,NULL,1,'boleta','B001',5,NULL,'Consumidor final',NULL,'2026-07-01 18:17:55','PEN',25.42,4.58,0.00,30.00,0,0.00,0.00,30.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',100.00,70.00,NULL,'2026-07-01 18:17:55','2026-07-01 18:17:55'),(8,NULL,1,1,1,NULL,1,'boleta','B001',6,NULL,'Consumidor final',NULL,'2026-07-01 18:19:07','PEN',25.42,4.58,0.00,30.00,0,0.00,0.00,30.00,'anulada','aceptado','sunat/10734944101/xml/10734944101-03-B001-6.xml','sunat/10734944101/cdr/R-10734944101-03-B001-6.zip','iNp8pT+OnacJgHuEX0TxiR/oPsA=',NULL,'La Boleta numero B001-6, ha sido aceptada','2026-07-01 18:19:08','efectivo',100.00,70.00,NULL,'2026-07-01 18:19:07','2026-07-01 18:35:45'),(9,1,1,1,1,4,1,'boleta','B001',7,'70123456','Carlos Fernández Ríos',NULL,'2026-07-05 05:12:49','PEN',152.54,27.46,0.00,180.00,0,0.00,0.00,180.00,'anulada','aceptado','sunat/10734944101/xml/10734944101-03-B001-7.xml','sunat/10734944101/cdr/R-10734944101-03-B001-7.zip','9JZTqccSlciHhdrXEWRSa3YlReI=',NULL,'La Boleta numero B001-7, ha sido aceptada','2026-07-05 05:17:39','efectivo',0.00,0.00,NULL,'2026-07-05 05:12:49','2026-07-05 05:20:43'),(10,2,1,1,1,4,1,'boleta','B001',8,'70123456','Carlos Fernández Ríos',NULL,'2026-07-05 05:26:03','PEN',15.25,2.75,0.00,18.00,0,0.00,0.00,18.00,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-8.xml','sunat/10734944101/cdr/R-10734944101-03-B001-8.zip','31brW+W9gFr5ayvyarg9Prj8CBs=',NULL,'La Boleta numero B001-8, ha sido aceptada','2026-07-05 05:26:22','efectivo',0.00,0.00,NULL,'2026-07-05 05:26:03','2026-07-05 05:43:38'),(11,NULL,1,1,1,4,1,'boleta','B001',9,'70123456','Carlos Fernández Ríos',NULL,'2026-07-05 22:25:42','PEN',15.25,2.75,0.00,18.00,0,0.00,0.00,18.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',0.00,0.00,NULL,'2026-07-05 22:25:42','2026-07-05 22:25:42'),(12,NULL,1,1,1,3,1,'boleta','B001',10,'45678912','María López Gutiérrez',NULL,'2026-07-06 04:55:19','PEN',38.13,6.87,0.00,45.00,0,0.00,0.00,45.00,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-10.xml','sunat/10734944101/cdr/R-10734944101-03-B001-10.zip','sj6B4dhlM4H4rrI4zl+DqX25Rpk=',NULL,'La Boleta numero B001-10, ha sido aceptada','2026-07-06 04:56:04','efectivo',0.00,0.00,NULL,'2026-07-06 04:55:19','2026-07-06 04:56:04'),(13,3,1,1,1,4,1,'boleta','B001',11,'70123456','Carlos Fernández Ríos',NULL,'2026-07-06 05:28:28','PEN',30.51,5.49,0.00,36.00,0,0.00,0.00,36.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',0.00,0.00,NULL,'2026-07-06 05:28:28','2026-07-06 18:46:26'),(14,4,1,1,1,NULL,1,'boleta','B001',12,NULL,'Consumidor final',NULL,'2026-07-06 18:47:45','PEN',1.27,0.23,0.00,1.50,0,0.00,0.00,1.50,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',0.00,0.00,NULL,'2026-07-06 18:47:45','2026-07-06 18:47:52'),(15,5,1,1,1,NULL,1,'boleta','B001',13,NULL,'Consumidor final',NULL,'2026-07-06 18:51:19','PEN',52.97,9.53,0.00,62.50,0,0.00,0.00,62.50,'pagada','aceptado','sunat/10734944101/xml/10734944101-03-B001-13.xml','sunat/10734944101/cdr/R-10734944101-03-B001-13.zip','Rtb7nfVGZSh6r8TI5iNMyisX8aY=',NULL,'La Boleta numero B001-13, ha sido aceptada','2026-07-06 18:51:41','efectivo',0.00,0.00,NULL,'2026-07-06 18:51:19','2026-07-06 18:51:46'),(16,6,1,1,1,NULL,1,'boleta','B001',14,NULL,'Consumidor final',NULL,'2026-07-06 18:52:14','PEN',11.86,2.14,0.00,14.00,0,0.00,0.00,14.00,'pagada','no_enviado',NULL,NULL,NULL,NULL,NULL,NULL,'efectivo',0.00,0.00,NULL,'2026-07-06 18:52:14','2026-07-06 18:52:22'),(17,7,1,1,1,NULL,1,'boleta','B001',15,NULL,'Consumidor final',NULL,'2026-07-06 19:00:29','PEN',251.70,45.30,0.00,297.00,0,0.00,0.00,297.00,'pagada','aceptado',NULL,NULL,NULL,NULL,'Aceptada en resumen RC-20260706-5','2026-07-06 19:03:02','efectivo',0.00,0.00,NULL,'2026-07-06 19:00:29','2026-07-06 19:03:02'),(18,7,1,1,1,NULL,1,'boleta','B001',16,NULL,'Consumidor final',NULL,'2026-07-06 19:00:41','PEN',18.22,3.28,0.00,21.50,0,0.00,0.00,21.50,'pagada','aceptado',NULL,NULL,NULL,NULL,'Aceptada en resumen RC-20260706-5','2026-07-06 19:03:02','efectivo',0.00,0.00,NULL,'2026-07-06 19:00:41','2026-07-06 19:03:02'),(19,8,1,1,1,NULL,1,'boleta','B001',17,NULL,'Consumidor final',NULL,'2026-07-06 19:01:03','PEN',28.39,5.11,0.00,33.50,0,0.00,0.00,33.50,'pagada','aceptado',NULL,NULL,NULL,NULL,'Aceptada en resumen RC-20260706-6','2026-07-06 19:02:59','efectivo',0.00,0.00,NULL,'2026-07-06 19:01:03','2026-07-06 19:02:59'),(20,8,1,1,1,NULL,1,'boleta','B001',18,NULL,'Consumidor final',NULL,'2026-07-06 19:01:14','PEN',20.51,3.69,0.00,24.20,0,0.00,0.00,24.20,'pagada','aceptado',NULL,NULL,NULL,NULL,'Aceptada en resumen RC-20260706-6','2026-07-06 19:02:59','efectivo',0.00,0.00,NULL,'2026-07-06 19:01:14','2026-07-06 19:02:59'),(21,NULL,1,1,2,1,1,'factura','F001',2,'20512345678','COMERCIAL RAMÍREZ S.A.C.',NULL,'2026-07-08 03:13:39','PEN',152.54,27.46,0.00,180.00,0,0.00,0.00,180.00,'anulada','simulado','sunat/10734944101/xml/10734944101-01-F001-2.xml',NULL,'UbxzNt9dXDP57I2DH1xCUpTmXd0=',NULL,'Comprobante generado y firmado en modo simulación (no enviado a SUNAT). Configura el certificado y la clave SOL para emitir realmente.','2026-07-09 02:26:57','efectivo',0.00,0.00,NULL,'2026-07-08 03:13:39','2026-07-09 02:26:57'),(22,NULL,1,1,1,4,1,'boleta','B001',19,'70123456','Carlos Fernández Ríos',NULL,'2026-07-08 03:14:53','PEN',15.25,2.75,0.00,18.00,0,0.00,0.00,18.00,'pagada','simulado','sunat/10734944101/xml/10734944101-03-B001-19.xml',NULL,'Jx7OQxPiYYV6q/IoNMUFAM6TjmU=',NULL,'Comprobante generado y firmado en modo simulación (no enviado a SUNAT). Configura el certificado y la clave SOL para emitir realmente.','2026-07-09 02:23:06','efectivo',0.00,0.00,NULL,'2026-07-08 03:14:53','2026-07-09 02:23:06'),(23,NULL,1,1,1,4,1,'boleta','B001',20,'70123456','Carlos Fernández Ríos',NULL,'2026-07-08 03:26:30','PEN',2.97,0.53,0.00,3.50,0,0.00,0.00,3.50,'pagada','simulado','sunat/10734944101/xml/10734944101-03-B001-20.xml',NULL,'DkjVHANwZMKRKkG4W3kqtIRA52M=',NULL,'Comprobante generado y firmado en modo simulación (no enviado a SUNAT). Configura el certificado y la clave SOL para emitir realmente.','2026-07-09 02:02:16','efectivo',4.00,0.50,NULL,'2026-07-08 03:26:30','2026-07-09 02:02:16'),(24,NULL,1,1,1,1,1,'boleta','B001',21,'20512345678','COMERCIAL RAMÍREZ S.A.C.',NULL,'2026-07-10 20:40:13','PEN',1.27,0.23,0.00,1.50,0,0.00,0.00,1.50,'pagada','simulado','sunat/10734944101/xml/10734944101-03-B001-21.xml',NULL,'6BiRg0mVVOKO0cRyLz4C8rbnf80=',NULL,'Comprobante generado y firmado en modo simulación (no enviado a SUNAT). Configura el certificado y la clave SOL para emitir realmente.','2026-07-10 20:40:28','efectivo',0.00,0.00,NULL,'2026-07-10 20:40:13','2026-07-10 20:40:28');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('NmTRHTRnRhwZ3Pk1WWQPyAp5fjXmTIPOaXZ1l4U1',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZ3dsQ25aaG1zNU1hWHJOcm5XVkN2N0Y2QVI1MlhtdmZIYm9rY2JjWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO319',1783738455),('ZslycMrW2Xs8UiTDi1YamN6KK63ABa0R5odaQtb2',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaGZJRjNzWTdjZG5RbWc5dVB3eER5OGVxa1ppYWY5QmRXbUFPUjBBSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC92ZW50YXMvMjQvcGRmIjtzOjU6InJvdXRlIjtzOjEwOiJ2ZW50YXMucGRmIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1783698035);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tipo_documento` varchar(2) NOT NULL DEFAULT '6',
  `numero_documento` varchar(20) DEFAULT NULL,
  `razon_social` varchar(255) NOT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_doc_unique` (`company_id`,`tipo_documento`,`numero_documento`),
  KEY `suppliers_company_id_razon_social_index` (`company_id`,`razon_social`),
  CONSTRAINT `suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,1,'6','20100050678','DISTRIBUIDORA MAYORISTA ANDINA S.A.C.','Mayorista Andina','Av. Argentina 2300, Cercado de Lima','Jorge Quispe','ventas@mayoristaandina.pe','01-3334455',1,'2026-06-26 03:51:11','2026-06-26 03:51:11'),(2,1,'6','20557788990','IMPORTACIONES DEL PACÍFICO E.I.R.L.',NULL,'Jr. Cusco 145, Lima','Ana Torres',NULL,'01-7778899',1,'2026-06-26 03:51:11','2026-06-26 03:51:11'),(3,1,'6','20602233445','COMERCIALIZADORA LIMA NORTE S.A.',NULL,'Av. Túpac Amaru 1800, Independencia',NULL,NULL,NULL,1,'2026-06-26 03:51:11','2026-06-26 03:51:11');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_items`
--

DROP TABLE IF EXISTS `transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(15,4) NOT NULL,
  `costo_unitario` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_items_transfer_id_foreign` (`transfer_id`),
  KEY `transfer_items_product_id_foreign` (`product_id`),
  CONSTRAINT `transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transfer_items_transfer_id_foreign` FOREIGN KEY (`transfer_id`) REFERENCES `transfers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_items`
--

LOCK TABLES `transfer_items` WRITE;
/*!40000 ALTER TABLE `transfer_items` DISABLE KEYS */;
INSERT INTO `transfer_items` VALUES (1,1,2,'Aceite Vegetal 1L',50.0000,12.5000,'2026-07-05 05:11:31','2026-07-05 05:11:31');
/*!40000 ALTER TABLE `transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfers`
--

DROP TABLE IF EXISTS `transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `origin_warehouse_id` bigint(20) unsigned NOT NULL,
  `dest_warehouse_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(12) NOT NULL DEFAULT 'completada',
  `observacion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfers_origin_warehouse_id_foreign` (`origin_warehouse_id`),
  KEY `transfers_dest_warehouse_id_foreign` (`dest_warehouse_id`),
  KEY `transfers_user_id_foreign` (`user_id`),
  KEY `transfers_company_id_fecha_index` (`company_id`,`fecha`),
  CONSTRAINT `transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfers_dest_warehouse_id_foreign` FOREIGN KEY (`dest_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfers_origin_warehouse_id_foreign` FOREIGN KEY (`origin_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfers`
--

LOCK TABLES `transfers` WRITE;
/*!40000 ALTER TABLE `transfers` DISABLE KEYS */;
INSERT INTO `transfers` VALUES (1,1,1,2,1,'2026-07-05 05:11:31','completada',NULL,'2026-07-05 05:11:31','2026-07-05 05:11:31');
/*!40000 ALTER TABLE `transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `abreviatura` varchar(10) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_company_id_codigo_unique` (`company_id`,`codigo`),
  CONSTRAINT `units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,1,'NIU','Unidad','UND',1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(2,1,'KGM','Kilogramo','KG',1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(3,1,'LTR','Litro','L',1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(4,1,'BX','Caja','CJA',1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(5,1,'PK','Paquete','PQT',1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(6,1,'DZN','Docena','DOC',1,'2026-06-25 19:11:49','2026-06-25 19:11:49');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,1,'Administrador','admin@erp.test',NULL,'Gerente General',NULL,1,NULL,'2026-06-25 19:01:48','$2y$12$rsLrvtXwZlP8QDyRHivKdeKYU1o8x8VpjbwxiqNfob2Eup58F4Hya','KvTuOvDUpRi9rsM6vpKtdrkNaXptrjCMw32PykgIDvowF3FFrIe9nCjEyroS','2026-06-25 19:01:48','2026-06-25 19:01:48');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voided_documents`
--

DROP TABLE IF EXISTS `voided_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voided_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_referencia` date NOT NULL,
  `fecha_comunicacion` date NOT NULL,
  `correlativo` int(10) unsigned NOT NULL,
  `identificador` varchar(30) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `ticket` varchar(255) DEFAULT NULL,
  `estado` varchar(12) NOT NULL DEFAULT 'pendiente',
  `xml_path` varchar(255) DEFAULT NULL,
  `cdr_path` varchar(255) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voided_documents_company_id_identificador_unique` (`company_id`,`identificador`),
  KEY `voided_documents_user_id_foreign` (`user_id`),
  KEY `voided_documents_sale_id_foreign` (`sale_id`),
  CONSTRAINT `voided_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voided_documents_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `voided_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voided_documents`
--

LOCK TABLES `voided_documents` WRITE;
/*!40000 ALTER TABLE `voided_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `voided_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_company_id_codigo_unique` (`company_id`,`codigo`),
  KEY `warehouses_branch_id_foreign` (`branch_id`),
  CONSTRAINT `warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,1,1,'ALM01','Almacén Principal',NULL,1,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(2,1,NULL,'ALM02','Almacén Secundario',NULL,0,1,'2026-06-25 19:11:49','2026-06-25 19:11:49'),(3,1,2,'ALM03','Almacen tercero','sn',0,1,'2026-07-05 05:09:54','2026-07-08 03:07:22');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'manejo_ventas_tienda'
--

--
-- Dumping routines for database 'manejo_ventas_tienda'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 22:08:29
