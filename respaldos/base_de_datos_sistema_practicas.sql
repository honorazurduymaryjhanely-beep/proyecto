-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sistema_practicas
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
-- Current Database: `sistema_practicas`
--

/*!40000 DROP DATABASE IF EXISTS `sistema_practicas`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sistema_practicas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sistema_practicas`;

--
-- Table structure for table `actividades_practica`
--

DROP TABLE IF EXISTS `actividades_practica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividades_practica` (
  `id_actividad` int(11) NOT NULL AUTO_INCREMENT,
  `id_practica` int(11) NOT NULL,
  `nombre_actividad` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_actividad`),
  KEY `idx_id_practica` (`id_practica`),
  KEY `idx_fecha_registro` (`fecha_registro`),
  KEY `idx_registrado_por` (`registrado_por`),
  CONSTRAINT `fk_actividades_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas_laborales` (`id_practica`) ON UPDATE CASCADE,
  CONSTRAINT `fk_actividades_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Actividades realizadas durante las pr??cticas laborales';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividades_practica`
--

LOCK TABLES `actividades_practica` WRITE;
/*!40000 ALTER TABLE `actividades_practica` DISABLE KEYS */;
INSERT INTO `actividades_practica` VALUES (3,7,'Reparación de equipos','Repación de la computadoras de escritorio en el laboratorio de sistemas informmaticos de la institución','2026-08-27',1,'2026-08-27 15:17:35');
/*!40000 ALTER TABLE `actividades_practica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones_supervision`
--

DROP TABLE IF EXISTS `asignaciones_supervision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asignaciones_supervision` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_practica` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=reasignado',
  `observaciones` text DEFAULT NULL,
  `asignado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_id_practica` (`id_practica`),
  KEY `idx_id_docente` (`id_docente`),
  KEY `idx_estado` (`estado`),
  KEY `idx_asignado_por` (`asignado_por`),
  CONSTRAINT `fk_asignaciones_asignado_por` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_docente` FOREIGN KEY (`id_docente`) REFERENCES `docentes_supervisores` (`id_docente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asignaciones_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas_laborales` (`id_practica`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignaciones de docentes supervisores a pr??cticas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones_supervision`
--

LOCK TABLES `asignaciones_supervision` WRITE;
/*!40000 ALTER TABLE `asignaciones_supervision` DISABLE KEYS */;
INSERT INTO `asignaciones_supervision` VALUES (5,7,5,'2026-08-27',1,NULL,1,'2026-08-27 15:14:52'),(6,5,5,'2026-08-27',0,NULL,1,'2026-08-27 15:19:39'),(7,8,5,'2026-08-27',1,NULL,1,'2026-08-27 15:28:32'),(8,9,5,'2026-08-27',0,NULL,1,'2026-08-27 15:30:33'),(9,10,5,'2026-10-26',1,'Asignación inicial al registrar la práctica',7,'2026-10-26 22:25:30'),(12,5,5,'2026-10-26',1,'Restauración',1,'2026-10-26 23:30:44'),(13,9,5,'2026-10-26',1,'Restauración',1,'2026-10-26 23:30:44');
/*!40000 ALTER TABLE `asignaciones_supervision` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `accion` enum('crear','modificar','eliminar','login','logout','acceso_denegado') NOT NULL,
  `tabla_afectada` varchar(50) NOT NULL,
  `id_registro` int(11) DEFAULT NULL COMMENT 'ID del registro afectado en la tabla',
  `datos_anteriores` text DEFAULT NULL COMMENT 'JSON con datos antes del cambio',
  `datos_nuevos` text DEFAULT NULL COMMENT 'JSON con datos despu??s del cambio',
  `ip_direccion` varchar(45) DEFAULT NULL COMMENT 'Direcci??n IP del usuario (IPv4 o IPv6)',
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_accion` (`accion`),
  KEY `idx_tabla_afectada` (`tabla_afectada`),
  KEY `idx_fecha_hora` (`fecha_hora`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de auditor??a de acciones del sistema';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,1,'login','usuarios',1,NULL,NULL,'::1','2026-08-26 18:31:18'),(2,1,'logout','usuarios',1,NULL,NULL,'::1','2026-08-26 18:39:15'),(3,1,'login','usuarios',1,NULL,NULL,'::1','2026-08-26 18:39:33'),(4,1,'crear','instituciones_receptoras',8,NULL,'{\"nit\":\"10001646\"}','::1','2026-08-26 20:11:51'),(5,1,'crear','estudiantes',8,NULL,'{\"cedula\":\"5132859\"}','::1','2026-08-26 20:12:29'),(6,1,'crear','convenios',6,NULL,'{\"titulo\":\"StartITSEC: Plataforma digital para emprendimientos innovadores del instituto ITSEC\"}','::1','2026-08-26 20:55:48'),(7,1,'crear','docentes_supervisores',5,NULL,'{\"cedula\":\"523654\"}','::1','2026-08-26 20:57:30'),(8,1,'modificar','convenios',6,'{\"titulo\":\"StartITSEC: Plataforma digital para emprendimientos innovadores del instituto ITSEC\"}','{\"titulo\":\"StartITSEC: Plataforma digital para emprendimientos innovadores del instituto ITSEC\"}','::1','2026-08-26 20:58:34'),(9,1,'crear','practicas_laborales',5,NULL,'{\"id_estudiante\":8,\"id_convenio\":6}','::1','2026-08-26 21:00:34'),(10,1,'crear','respaldos',NULL,NULL,'{\"archivo\":\"respaldo_sistema_practicas_20260827_030201.sql\"}','::1','2026-08-26 21:02:01'),(11,1,'login','usuarios',1,NULL,NULL,'::1','2026-08-27 14:11:52'),(12,1,'login','usuarios',1,NULL,NULL,'192.168.137.169','2026-08-27 14:17:09'),(13,1,'login','usuarios',1,NULL,NULL,'192.168.137.169','2026-08-27 14:24:37'),(14,1,'crear','estudiantes',13,NULL,'{\"cedula\":\"12588815\"}','192.168.137.169','2026-08-27 14:26:50'),(15,1,'modificar','estudiantes',13,'{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','192.168.137.169','2026-08-27 14:29:35'),(16,1,'modificar','estudiantes',13,'{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','192.168.137.169','2026-08-27 14:29:51'),(17,1,'modificar','instituciones_receptoras',8,NULL,'{\"estado\":0}','192.168.137.169','2026-08-27 14:46:52'),(18,1,'modificar','instituciones_receptoras',8,NULL,'{\"estado\":1}','192.168.137.169','2026-08-27 14:47:39'),(19,1,'modificar','practicas_laborales',5,NULL,'{\"nuevo_estado\":\"en_curso\"}','192.168.137.169','2026-08-27 14:50:03'),(20,1,'crear','estudiantes',14,NULL,'{\"cedula\":\"1245879\"}','192.168.137.169','2026-08-27 14:51:54'),(21,1,'crear','estudiantes',15,NULL,'{\"cedula\":\"12865899\"}','192.168.137.169','2026-08-27 14:53:40'),(22,1,'crear','estudiantes',16,NULL,'{\"cedula\":\"13257675\"}','192.168.137.169','2026-08-27 14:55:41'),(23,1,'crear','instituciones_receptoras',11,NULL,'{\"nit\":\"12345678\"}','192.168.137.169','2026-08-27 14:59:45'),(24,1,'login','usuarios',1,NULL,NULL,'192.168.137.169','2026-08-27 15:02:01'),(25,1,'crear','convenios',8,NULL,'{\"titulo\":\"Mantenimiento del gabinete de Sistemas Informaticos\"}','192.168.137.169','2026-08-27 15:04:23'),(26,1,'login','usuarios',1,NULL,NULL,'192.168.137.169','2026-08-27 15:11:30'),(27,1,'crear','practicas_laborales',7,NULL,'{\"id_estudiante\":14,\"id_convenio\":8}','192.168.137.169','2026-08-27 15:14:18'),(28,1,'modificar','practicas_laborales',7,NULL,'{\"nuevo_estado\":\"en_curso\"}','192.168.137.169','2026-08-27 15:14:39'),(29,1,'modificar','asignaciones_supervision',7,NULL,'{\"id_docente\":3}','192.168.137.169','2026-08-27 15:14:52'),(30,1,'crear','actividades_practica',3,NULL,'{\"id_practica\":7}','192.168.137.169','2026-08-27 15:17:35'),(31,1,'crear','horas_cumplidas',5,NULL,'{\"id_practica\":7,\"horas\":8}','192.168.137.169','2026-08-27 15:18:48'),(32,1,'modificar','asignaciones_supervision',5,NULL,'{\"id_docente\":3}','192.168.137.169','2026-08-27 15:19:39'),(33,1,'crear','horas_cumplidas',6,NULL,'{\"id_practica\":7,\"horas\":12}','192.168.137.169','2026-08-27 15:22:21'),(34,1,'modificar','practicas_laborales',7,NULL,'{\"nuevo_estado\":\"suspendida\"}','192.168.137.169','2026-08-27 15:23:13'),(35,1,'crear','practicas_laborales',8,NULL,'{\"id_estudiante\":16,\"id_convenio\":8}','192.168.137.169','2026-08-27 15:24:32'),(36,1,'modificar','practicas_laborales',8,NULL,'{\"nuevo_estado\":\"en_curso\"}','192.168.137.169','2026-08-27 15:24:42'),(37,1,'modificar','asignaciones_supervision',8,NULL,'{\"id_docente\":3}','192.168.137.169','2026-08-27 15:28:32'),(38,1,'modificar','practicas_laborales',8,NULL,'{\"nuevo_estado\":\"completada\"}','192.168.137.169','2026-08-27 15:28:39'),(39,1,'crear','practicas_laborales',9,NULL,'{\"id_estudiante\":15,\"id_convenio\":6}','192.168.137.169','2026-08-27 15:30:24'),(40,1,'modificar','asignaciones_supervision',9,NULL,'{\"id_docente\":3}','192.168.137.169','2026-08-27 15:30:33'),(41,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 08:56:10'),(42,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 09:08:11'),(43,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 09:21:41'),(44,1,'modificar','usuarios',3,NULL,'{\"estado\":0}','::1','2026-10-26 09:23:14'),(45,1,'modificar','usuarios',3,NULL,'{\"estado\":1}','::1','2026-10-26 09:23:17'),(46,1,'crear','estudiantes',21,NULL,'{\"cedula\":\"6532544\"}','::1','2026-10-26 09:28:08'),(47,1,'crear','usuarios',6,NULL,'{\"nombre_usuario\":\"favio\",\"id_rol\":3}','::1','2026-10-26 09:44:36'),(48,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 10:01:04'),(49,1,'login','usuarios',1,NULL,NULL,'127.0.0.1','2026-10-26 11:10:40'),(50,1,'modificar','estudiantes',14,'{\"nombre\":\"Bianca Cespedes Siles\"}','{\"nombre\":\"Bianca Cespedes Siles\"}','127.0.0.1','2026-10-26 11:11:19'),(51,1,'modificar','estudiantes',14,'{\"nombre\":\"Bianca Cespedes Siles\"}','{\"nombre\":\"Bianca Cespedes Siles\"}','127.0.0.1','2026-10-26 11:11:27'),(52,1,'logout','usuarios',1,NULL,NULL,'127.0.0.1','2026-10-26 11:25:23'),(53,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 11:26:31'),(54,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 20:50:45'),(55,1,'modificar','estudiantes',13,'{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','{\"nombre\":\"Mary Jhanely Honor Azurduy\"}','::1','2026-10-26 20:53:56'),(56,1,'modificar','estudiantes',8,'{\"nombre\":\"Choque Huarachi Favio\"}','{\"nombre\":\"Choque Huarachi Favio\"}','::1','2026-10-26 20:54:23'),(57,1,'modificar','estudiantes',16,'{\"nombre\":\"Marlen Aneliz Calvimontes\"}','{\"nombre\":\"Marlen Aneliz Calvimontes\"}','::1','2026-10-26 20:54:37'),(58,1,'modificar','estudiantes',15,'{\"nombre\":\"Mauricio Roberto Acho Lopez\"}','{\"nombre\":\"Mauricio Roberto Acho Lopez\"}','::1','2026-10-26 20:54:50'),(59,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 20:56:34'),(60,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:01:28'),(61,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 21:02:17'),(62,3,'login','usuarios',3,NULL,NULL,'::1','2026-10-26 21:02:21'),(63,3,'logout','usuarios',3,NULL,NULL,'::1','2026-10-26 21:04:32'),(64,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:04:37'),(65,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 21:22:03'),(66,6,'login','usuarios',6,NULL,NULL,'::1','2026-10-26 21:22:09'),(67,6,'logout','usuarios',6,NULL,NULL,'::1','2026-10-26 21:22:35'),(68,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:23:00'),(69,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 21:26:37'),(70,6,'login','usuarios',6,NULL,NULL,'::1','2026-10-26 21:26:42'),(71,6,'logout','usuarios',6,NULL,NULL,'::1','2026-10-26 21:26:54'),(72,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:29:00'),(73,1,'crear','docentes_supervisores',8,NULL,'{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"cedula_identidad\":\"985565\",\"carrera\":\"Sistemas Informáticos\",\"telefono\":\"76895652\",\"correo_electronico\":\"marianela10@gmail.com\"}','::1','2026-10-26 21:33:47'),(74,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 21:34:07'),(75,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:34:20'),(76,1,'modificar','docentes_supervisores',8,'{\"id_docente\":8,\"codigo_docente\":\"DOC-000008\",\"nombre_completo\":\"Marianela Cardenas Rojas\",\"cedula_identidad\":\"985565\",\"carrera\":\"Sistemas Informáticos\",\"telefono\":\"76895652\",\"correo_electronico\":\"marianela10@gmail.com\",\"estado\":1,\"creado_por\":1,\"fecha_creacion\":\"2026-10-26 21:33:47\",\"fecha_modificacion\":\"2026-10-26 21:33:47\"}','{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"cedula_identidad\":\"985565\",\"carrera\":\"Sistemas Informáticos\",\"telefono\":\"76895652\",\"correo_electronico\":\"marianela10@gmail.com\"}','::1','2026-10-26 21:34:49'),(77,1,'crear','usuarios',7,NULL,'{\"nombre_usuario\":\"Marianela\",\"id_rol\":2}','::1','2026-10-26 21:37:23'),(78,1,'modificar','docentes_supervisores',8,'{\"estado\":1}','{\"estado\":0}','::1','2026-10-26 21:37:39'),(79,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 21:37:57'),(80,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 21:38:04'),(81,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 21:38:50'),(82,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 21:38:54'),(83,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 22:03:55'),(84,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 22:04:04'),(85,7,'modificar','docentes_supervisores',8,'{\"estado\":0}','{\"estado\":1}','::1','2026-10-26 22:04:41'),(86,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 22:06:42'),(87,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 22:10:36'),(88,1,'crear','estudiantes',22,NULL,'{\"cedula\":\"6585226\"}','::1','2026-10-26 22:22:29'),(89,1,'modificar','usuarios',3,'{\"nombre_completo\":\"Juan Carlos Lopez\",\"id_rol\":2}','{\"nombre_completo\":\"Juan Carlos Lopez\",\"id_rol\":4}','::1','2026-10-26 22:23:25'),(90,1,'modificar','usuarios',7,'{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','::1','2026-10-26 22:23:40'),(91,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 22:23:48'),(92,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 22:23:56'),(93,7,'crear','practicas_laborales',10,NULL,'{\"id_estudiante\":22,\"id_convenio\":6}','::1','2026-10-26 22:25:30'),(94,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 22:29:22'),(95,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 22:29:27'),(96,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 22:52:11'),(97,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 22:52:22'),(98,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 22:53:17'),(99,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 22:53:23'),(100,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:10:45'),(101,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 23:10:56'),(102,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 23:11:26'),(103,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:11:31'),(104,1,'crear','docentes_supervisores',10,NULL,'{\"nombre_completo\":\"Mariluz flores\",\"cedula_identidad\":\"6598654\",\"carrera\":\"Secretariado Ejecutivo\",\"telefono\":\"75565622\",\"correo_electronico\":\"\"}','::1','2026-10-26 23:12:32'),(105,1,'modificar','docentes_supervisores',10,'{\"id_docente\":10,\"codigo_docente\":\"DOC-000010\",\"nombre_completo\":\"Mariluz flores\",\"cedula_identidad\":\"6598654\",\"carrera\":\"Secretariado Ejecutivo\",\"telefono\":\"75565622\",\"correo_electronico\":null,\"estado\":1,\"creado_por\":1,\"fecha_creacion\":\"2026-10-26 23:12:32\",\"fecha_modificacion\":\"2026-10-26 23:12:32\"}','{\"nombre_completo\":\"Mariluz flores\",\"cedula_identidad\":\"6598654\",\"carrera\":\"Secretariado Ejecutivo\",\"telefono\":\"75565622\",\"correo_electronico\":\"\"}','::1','2026-10-26 23:12:48'),(106,1,'crear','usuarios',8,NULL,'{\"nombre_usuario\":\"jaime\",\"id_rol\":4}','::1','2026-10-26 23:14:03'),(107,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:14:25'),(108,8,'login','usuarios',8,NULL,NULL,'::1','2026-10-26 23:14:30'),(109,8,'logout','usuarios',8,NULL,NULL,'::1','2026-10-26 23:15:29'),(110,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:15:35'),(111,1,'modificar','usuarios',8,'{\"nombre_completo\":\"Jaime guerrero\",\"id_rol\":4}','{\"nombre_completo\":\"Jaime guerrero\",\"id_rol\":2}','::1','2026-10-26 23:15:49'),(112,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:15:55'),(113,8,'login','usuarios',8,NULL,NULL,'::1','2026-10-26 23:16:01'),(114,8,'logout','usuarios',8,NULL,NULL,'::1','2026-10-26 23:19:04'),(115,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:19:09'),(116,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:31:34'),(117,8,'login','usuarios',8,NULL,NULL,'::1','2026-10-26 23:31:40'),(118,8,'logout','usuarios',8,NULL,NULL,'::1','2026-10-26 23:32:21'),(119,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:32:27'),(120,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:35:54'),(121,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:36:23'),(122,1,'modificar','usuarios',7,'{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','::1','2026-10-26 23:36:54'),(123,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:48:40'),(124,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 23:48:51'),(125,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 23:49:16'),(126,1,'login','usuarios',1,NULL,NULL,'::1','2026-10-26 23:49:23'),(127,1,'modificar','usuarios',7,'{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','{\"nombre_completo\":\"Marianela Cardenas Rojas\",\"id_rol\":2}','::1','2026-10-26 23:49:34'),(128,1,'logout','usuarios',1,NULL,NULL,'::1','2026-10-26 23:50:16'),(129,7,'login','usuarios',7,NULL,NULL,'::1','2026-10-26 23:50:25'),(130,7,'logout','usuarios',7,NULL,NULL,'::1','2026-10-26 23:51:19'),(131,6,'login','usuarios',6,NULL,NULL,'::1','2026-10-26 23:51:26');
/*!40000 ALTER TABLE `auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carreras`
--

DROP TABLE IF EXISTS `carreras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carreras` (
  `id_carrera` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_carrera`),
  UNIQUE KEY `uk_nombre_carrera` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cat??logo de carreras del ITSEC SUCRE';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carreras`
--

LOCK TABLES `carreras` WRITE;
/*!40000 ALTER TABLE `carreras` DISABLE KEYS */;
INSERT INTO `carreras` VALUES (1,'Secretariado Ejecutivo',1,'2026-08-26 19:54:41'),(4,'Turismo',1,'2026-08-26 19:54:41'),(5,'Mercadotecnia',1,'2026-08-26 19:54:41'),(8,'Contaduría General',1,'2026-08-26 20:04:54'),(9,'Sistemas Informáticos',1,'2026-08-26 20:04:54');
/*!40000 ALTER TABLE `carreras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `convenios`
--

DROP TABLE IF EXISTS `convenios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `convenios` (
  `id_convenio` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_convenio` varchar(20) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_institucion` int(11) NOT NULL,
  `fecha_firma` date NOT NULL,
  `fecha_inicio_vigencia` date NOT NULL,
  `fecha_fin_vigencia` date NOT NULL,
  `documento_adjunto` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo adjunto',
  `estado` enum('vigente','expirado','cancelado') NOT NULL DEFAULT 'vigente',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_convenio`),
  UNIQUE KEY `uk_codigo_convenio` (`codigo_convenio`),
  KEY `idx_id_institucion` (`id_institucion`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vigencia` (`fecha_inicio_vigencia`,`fecha_fin_vigencia`),
  KEY `idx_creado_por` (`creado_por`),
  CONSTRAINT `fk_convenios_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_convenios_institucion` FOREIGN KEY (`id_institucion`) REFERENCES `instituciones_receptoras` (`id_institucion`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Convenios institucionales entre ITSEC SUCRE e instituciones receptoras';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `convenios`
--

LOCK TABLES `convenios` WRITE;
/*!40000 ALTER TABLE `convenios` DISABLE KEYS */;
INSERT INTO `convenios` VALUES (6,'CON-000006','StartITSEC: Plataforma digital para emprendimientos innovadores del instituto ITSEC','realizar las practicas',8,'2026-08-20','2026-08-20','2026-12-12','convenio_20260827025548_5b3e6669.pdf','vigente',1,'2026-08-26 20:55:48','2026-08-26 20:58:34'),(8,'CON-000008','Mantenimiento del gabinete de Sistemas Informaticos','Practicas Laborales',11,'2026-08-25','2026-08-26','2027-08-26','convenio_20260827210423_34676cf0.pdf','vigente',1,'2026-08-27 15:04:23','2026-08-27 15:04:23');
/*!40000 ALTER TABLE `convenios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docentes_supervisores`
--

DROP TABLE IF EXISTS `docentes_supervisores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docentes_supervisores` (
  `id_docente` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `codigo_docente` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `cedula_identidad` varchar(10) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(150) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_docente`),
  UNIQUE KEY `uk_codigo_docente` (`codigo_docente`),
  UNIQUE KEY `uk_cedula_identidad_docente` (`cedula_identidad`),
  KEY `idx_especialidad` (`carrera`),
  KEY `idx_estado` (`estado`),
  KEY `idx_creado_por` (`creado_por`),
  KEY `idx_docente_usuario` (`id_usuario`),
  CONSTRAINT `fk_docentes_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Docentes supervisores de pr??cticas laborales';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docentes_supervisores`
--

LOCK TABLES `docentes_supervisores` WRITE;
/*!40000 ALTER TABLE `docentes_supervisores` DISABLE KEYS */;
INSERT INTO `docentes_supervisores` VALUES (5,3,'DOC-000005','Juan Carlos Lopez','523654','Sistemas Informáticos','76572652','favnet1010@gmail.com',1,1,'2026-08-26 20:57:30','2026-10-26 23:26:21'),(8,7,'DOC-000008','Marianela Cardenas Rojas','985565','Secretariado Ejecutivo','75789932','marianela10@gmail.com',1,1,'2026-10-26 21:33:47','2026-10-26 23:49:34'),(10,NULL,'DOC-000010','Mariluz flores','6598654','Secretariado Ejecutivo','75565622',NULL,1,1,'2026-10-26 23:12:32','2026-10-26 23:12:32'),(11,8,'DOC-000011','Jaime guerrero','653254','Secretariado Ejecutivo','653254','jaime@itsec.edu.bo',1,1,'2026-10-26 23:26:21','2026-10-26 23:26:21');
/*!40000 ALTER TABLE `docentes_supervisores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_estudiante` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `cedula_identidad` varchar(10) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `curso` varchar(20) NOT NULL,
  `paralelo` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `uk_codigo_estudiante` (`codigo_estudiante`),
  UNIQUE KEY `uk_cedula_identidad` (`cedula_identidad`),
  KEY `idx_carrera` (`carrera`),
  KEY `idx_estado` (`estado`),
  KEY `idx_nombre_completo` (`nombre_completo`),
  KEY `idx_creado_por` (`creado_por`),
  CONSTRAINT `fk_estudiantes_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estudiantes registrados para realizar pr??cticas laborales';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (8,'EST-000008','Choque Huarachi Favio','5132859','2000-12-12','Sistemas Informáticos','3ro','B','65231545','favnet10@gmail.com','Calle padre martin cruz s/n',1,1,'2026-08-26 20:12:29','2026-10-26 22:43:08'),(13,'EST-000013','Mary Jhanely Honor Azurduy','12588815','2007-06-30','Secretariado Ejecutivo','2do','B','74357197','honormary1@gmail.com',NULL,1,1,'2026-08-27 14:26:50','2026-10-26 20:53:56'),(14,'EST-000014','Bianca Cespedes Siles','1245879','2006-10-19','Turismo','3ro','A','89996735','honormary1@gmail.com',NULL,1,1,'2026-08-27 14:51:54','2026-10-26 11:11:27'),(15,'EST-000015','Mauricio Roberto Acho Lopez','12865899','2004-12-20','Sistemas Informáticos','3ro','A','27687690','honormary1@gmail.com',NULL,1,1,'2026-08-27 14:53:40','2026-10-26 20:54:50'),(16,'EST-000016','Marlen Aneliz Calvimontes','13257675','2003-06-08','Mercadotecnia','2do','B','23457787','honormary1@gmail.com','Calle Junin',1,1,'2026-08-27 14:55:41','2026-10-26 20:54:37'),(21,'EST-000021','Carlos Daniel Bracamonte Calvo','6532544','2000-12-22','Sistemas Informáticos','1ro','A','75899665','prueba@gmail.com','Zona Piscina Olimpica Calle Martin Cruz s/n',1,1,'2026-10-26 09:28:08','2026-10-26 09:28:08'),(22,'EST-000022','Carla Daniela Contreras','6585226','2000-12-12','Sistemas Informáticos','3ro Año','A','75789932',NULL,'Calle padre martin cruz s/n',1,1,'2026-10-26 22:22:29','2026-10-26 22:22:29');
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horas_cumplidas`
--

DROP TABLE IF EXISTS `horas_cumplidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `horas_cumplidas` (
  `id_registro_horas` int(11) NOT NULL AUTO_INCREMENT,
  `id_practica` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cantidad_horas` decimal(4,2) NOT NULL,
  `descripcion_actividades` varchar(500) NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_registro_horas`),
  KEY `idx_id_practica` (`id_practica`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_registrado_por` (`registrado_por`),
  CONSTRAINT `fk_horas_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas_laborales` (`id_practica`) ON UPDATE CASCADE,
  CONSTRAINT `fk_horas_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `chk_cantidad_horas` CHECK (`cantidad_horas` > 0 and `cantidad_horas` <= 12)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de horas cumplidas por los estudiantes en pr??cticas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horas_cumplidas`
--

LOCK TABLES `horas_cumplidas` WRITE;
/*!40000 ALTER TABLE `horas_cumplidas` DISABLE KEYS */;
INSERT INTO `horas_cumplidas` VALUES (5,7,'2026-08-27',8.00,'olimpiadas',1,'2026-08-27 15:18:48'),(6,7,'2026-08-27',12.00,'Documentación de Registro de Estudiantes',1,'2026-08-27 15:22:21');
/*!40000 ALTER TABLE `horas_cumplidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instituciones_receptoras`
--

DROP TABLE IF EXISTS `instituciones_receptoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instituciones_receptoras` (
  `id_institucion` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_institucion` varchar(20) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo_electronico` varchar(150) DEFAULT NULL,
  `nombre_mae` varchar(150) DEFAULT NULL,
  `telefono_mae` varchar(20) DEFAULT NULL,
  `nombre_contacto` varchar(150) NOT NULL,
  `cargo_contacto` varchar(100) NOT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_institucion`),
  UNIQUE KEY `uk_codigo_institucion` (`codigo_institucion`),
  UNIQUE KEY `uk_nit` (`nit`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_estado` (`estado`),
  KEY `idx_creado_por` (`creado_por`),
  CONSTRAINT `fk_instituciones_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Instituciones receptoras de estudiantes en pr??cticas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instituciones_receptoras`
--

LOCK TABLES `instituciones_receptoras` WRITE;
/*!40000 ALTER TABLE `instituciones_receptoras` DISABLE KEYS */;
INSERT INTO `instituciones_receptoras` VALUES (8,'INS-000008','Gobierno Autónomo Municipal de Sucre','10001646','Calle Martin Cruz 12','6554211','favnet10@gmail.com',NULL,NULL,'Ing Gabriel Mendez','Secretaria de Finanzas',NULL,1,1,'2026-08-26 20:11:51','2026-08-27 14:47:39'),(11,'INS-000011','Escuela Superior de Formación de Maestros','12345678','AV. Maestro','63938848','honormary1@gmail.com',NULL,NULL,'68793476','Encargado de Sistemas',NULL,1,1,'2026-08-27 14:59:45','2026-08-27 14:59:45');
/*!40000 ALTER TABLE `instituciones_receptoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `practicas_historial_estado`
--

DROP TABLE IF EXISTS `practicas_historial_estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `practicas_historial_estado` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_practica` int(11) NOT NULL,
  `estado_anterior` enum('pendiente','en_curso','completada','suspendida') NOT NULL,
  `estado_nuevo` enum('pendiente','en_curso','completada','suspendida') NOT NULL,
  `fecha_cambio` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL COMMENT 'Motivo del cambio de estado',
  PRIMARY KEY (`id_historial`),
  KEY `idx_id_practica` (`id_practica`),
  KEY `idx_fecha_cambio` (`fecha_cambio`),
  KEY `idx_id_usuario` (`id_usuario`),
  CONSTRAINT `fk_historial_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas_laborales` (`id_practica`) ON UPDATE CASCADE,
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios de estado de las pr??cticas laborales';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `practicas_historial_estado`
--

LOCK TABLES `practicas_historial_estado` WRITE;
/*!40000 ALTER TABLE `practicas_historial_estado` DISABLE KEYS */;
INSERT INTO `practicas_historial_estado` VALUES (11,5,'pendiente','pendiente','2026-08-26 21:00:34',1,'Registro inicial de la práctica.'),(13,5,'pendiente','en_curso','2026-08-27 14:50:03',1,NULL),(14,7,'pendiente','pendiente','2026-08-27 15:14:18',1,'Registro inicial de la práctica.'),(15,7,'pendiente','en_curso','2026-08-27 15:14:39',1,NULL),(16,7,'en_curso','suspendida','2026-08-27 15:23:13',1,'Falta de coordinación de horarios'),(17,8,'pendiente','pendiente','2026-08-27 15:24:32',1,'Registro inicial de la práctica.'),(18,8,'pendiente','en_curso','2026-08-27 15:24:42',1,NULL),(19,8,'en_curso','completada','2026-08-27 15:28:39',1,NULL),(20,9,'pendiente','pendiente','2026-08-27 15:30:24',1,'Registro inicial de la práctica.'),(21,10,'pendiente','pendiente','2026-10-26 22:25:30',7,'Registro inicial de la práctica.');
/*!40000 ALTER TABLE `practicas_historial_estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `practicas_laborales`
--

DROP TABLE IF EXISTS `practicas_laborales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `practicas_laborales` (
  `id_practica` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_practica` varchar(20) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_institucion` int(11) NOT NULL,
  `id_convenio` int(11) NOT NULL,
  `descripcion_actividades` text DEFAULT NULL,
  `area_departamento` varchar(150) DEFAULT NULL COMMENT '??rea o departamento en la instituci??n',
  `fecha_inicio` date NOT NULL,
  `fecha_fin_prevista` date NOT NULL,
  `fecha_finalizacion_real` date DEFAULT NULL,
  `total_horas_requeridas` int(11) NOT NULL,
  `estado` enum('pendiente','en_curso','completada','suspendida') NOT NULL DEFAULT 'pendiente',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_practica`),
  UNIQUE KEY `uk_codigo_practica` (`codigo_practica`),
  KEY `idx_id_estudiante` (`id_estudiante`),
  KEY `idx_id_institucion` (`id_institucion`),
  KEY `idx_id_convenio` (`id_convenio`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_fecha_fin_prevista` (`fecha_fin_prevista`),
  KEY `idx_creado_por` (`creado_por`),
  CONSTRAINT `fk_practicas_convenio` FOREIGN KEY (`id_convenio`) REFERENCES `convenios` (`id_convenio`) ON UPDATE CASCADE,
  CONSTRAINT `fk_practicas_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_practicas_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON UPDATE CASCADE,
  CONSTRAINT `fk_practicas_institucion` FOREIGN KEY (`id_institucion`) REFERENCES `instituciones_receptoras` (`id_institucion`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pr??cticas laborales asignadas a estudiantes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `practicas_laborales`
--

LOCK TABLES `practicas_laborales` WRITE;
/*!40000 ALTER TABLE `practicas_laborales` DISABLE KEYS */;
INSERT INTO `practicas_laborales` VALUES (5,'PRA-000005',8,8,6,'Realizar las practicas de acuerdo al convenio','Area de Sistemas','2026-08-26','2026-12-10',NULL,60,'en_curso',1,'2026-08-26 21:00:34','2026-08-27 14:50:03'),(7,'PRA-000007',14,11,8,NULL,'Laboratorio de Computación','2026-08-31','2026-10-30',NULL,300,'suspendida',1,'2026-08-27 15:14:18','2026-08-27 15:23:13'),(8,'PRA-000008',16,11,8,NULL,'Laboratorio de Computación','2026-08-28','2026-09-14','2026-08-27',120,'completada',1,'2026-08-27 15:24:32','2026-08-27 15:28:39'),(9,'PRA-000009',15,8,6,NULL,'Laboratorio de Computación','2026-08-25','2026-11-25',NULL,80,'pendiente',1,'2026-08-27 15:30:24','2026-08-27 15:30:24'),(10,'PRA-000010',22,8,6,'Realizar trabajos en el area de sistemas','Area de Sistemas','2026-10-12','2027-01-31',NULL,300,'pendiente',7,'2026-10-26 22:25:30','2026-10-26 22:25:30');
/*!40000 ALTER TABLE `practicas_laborales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `respaldos`
--

DROP TABLE IF EXISTS `respaldos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(255) NOT NULL,
  `tamano_bytes` bigint(20) DEFAULT NULL COMMENT 'Tama??o del archivo en bytes',
  `id_usuario` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_respaldo`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_fecha_creacion` (`fecha_creacion`),
  CONSTRAINT `fk_respaldos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de respaldos de la base de datos del sistema';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `respaldos`
--

LOCK TABLES `respaldos` WRITE;
/*!40000 ALTER TABLE `respaldos` DISABLE KEYS */;
INSERT INTO `respaldos` VALUES (3,'respaldo_sistema_practicas_20260827_030201.sql',29435,1,'2026-08-26 21:02:01');
/*!40000 ALTER TABLE `respaldos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `permisos` text DEFAULT NULL COMMENT 'JSON con permisos detallados del rol',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uk_nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles del sistema con permisos de acceso';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'administrador','Acceso completo a todos los m??dulos del sistema','{\n    \"dashboard\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"usuarios\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"estudiantes\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"instituciones\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"convenios\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"supervisores\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"practicas\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"reportes\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"respaldos\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true},\n    \"busqueda\": {\"ver\": true, \"crear\": true, \"editar\": true, \"eliminar\": true}\n}',1,'2026-08-26 18:28:55'),(2,'docente_encargado','Docente encargado de las prácticas de su carrera','{\"dashboard\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"usuarios\":{\"ver\":false,\"crear\":false,\"editar\":false,\"eliminar\":false},\"estudiantes\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"instituciones\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"convenios\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"supervisores\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"practicas\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"reportes\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"respaldos\":{\"ver\":false,\"crear\":false,\"editar\":false,\"eliminar\":false},\"busqueda\":{\"ver\":true,\"crear\":false,\"editar\":false,\"eliminar\":false}}',1,'2026-08-26 18:28:55'),(3,'consulta','Acceso de solo lectura a consultas y reportes','{\n    \"dashboard\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"usuarios\": {\"ver\": false, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"estudiantes\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"instituciones\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"convenios\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"supervisores\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"practicas\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"reportes\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"respaldos\": {\"ver\": false, \"crear\": false, \"editar\": false, \"eliminar\": false},\n    \"busqueda\": {\"ver\": true, \"crear\": false, \"editar\": false, \"eliminar\": false}\n}',1,'2026-08-26 18:28:55'),(4,'docente_supervisor','Docente supervisor de prácticas laborales','{\"dashboard\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"usuarios\":{\"ver\":false,\"crear\":false,\"editar\":false,\"eliminar\":false},\"estudiantes\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"instituciones\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"convenios\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"supervisores\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"practicas\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"reportes\":{\"ver\":true,\"crear\":true,\"editar\":true,\"eliminar\":false},\"respaldos\":{\"ver\":false,\"crear\":false,\"editar\":false,\"eliminar\":false},\"busqueda\":{\"ver\":true,\"crear\":false,\"editar\":false,\"eliminar\":false}}',1,'2026-10-26 21:28:59');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `correo_electronico` varchar(150) NOT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `uk_correo_electronico` (`correo_electronico`),
  KEY `idx_id_rol` (`id_rol`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema con credenciales y rol asignado';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador del Sistema','admin','$2y$10$b1OLoKq.n.ycZBNN8L/gW.4PnKqMRq1G9zxddU5FgFkkCBf0GXDN2','admin@itsec.edu.bo',NULL,NULL,1,1,'2026-10-26 23:49:23','2026-08-26 18:28:55','2026-10-26 23:49:23'),(3,'Juan Carlos Lopez','523654','$2y$10$FO3Y7xRNDNUltKpuIBWf.uWHwyCia4GQPn3F0SOuhM.fVoWEMi3g6','favnet1010@gmail.com','Sistemas Informáticos','76572652',4,1,'2026-10-26 21:02:21','2026-08-26 21:23:32','2026-10-26 22:23:25'),(6,'choque huarachi','favio','$2y$10$EtTanuq/fEqiWlGW3lwNHOznnDDNKNDojikrVXqGiWE8u0/QM6U42','favnet10@gmail.com',NULL,NULL,3,1,'2026-10-26 23:51:26','2026-10-26 09:44:36','2026-10-26 23:51:26'),(7,'Marianela Cardenas Rojas','Marianela','$2y$10$khF0Sx/HOjOJJMy9cNbKQ.SlTqr8gr3uUuj34sQ9FoHyU.6fbV8h2','marianela10@gmail.com','Secretariado Ejecutivo','75789932',2,1,'2026-10-26 23:50:25','2026-10-26 21:37:23','2026-10-26 23:50:25'),(8,'Jaime guerrero','jaime','$2y$10$0L//29y0H.jBbPk37oSuD.4ggeX3WdZqKEgFkXpLYOkGMOi5EhdMy','jaime@itsec.edu.bo','Secretariado Ejecutivo','653254',2,1,'2026-10-26 23:31:40','2026-10-26 23:14:03','2026-10-26 23:31:40');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sistema_practicas'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-26 23:56:10
