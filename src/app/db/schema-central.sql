-- Script de central para base de datos ixea_db
-- Ixea EROS
-- Enterprise Resource Operating System

-- Configuración inicial
-- Zona horaria México-CDMX
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-06:00";

--
-- Base de datos: `ixea_db`
--
CREATE DATABASE IF NOT EXISTS `ixea_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ixea_db`;

-- --------------------------------------------------------
-- DEFINICIÓN DE TABLAS
-- --------------------------------------------------------

--
-- 1. Tabla de Unidades de Negocio (Tenants / Empresas)
--
CREATE TABLE `tenants` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL COMMENT 'Nombre comercial',
  `domain` VARCHAR(150) NOT NULL COMMENT 'Dominio de correo',
  `db_host` VARCHAR(150) NOT NULL DEFAULT '127.0.0.1' COMMENT 'Host de la base de datos',
  `db_port` INT UNSIGNED NOT NULL DEFAULT 3306 COMMENT 'Puerto de la base de datos',
  `db_name` VARCHAR(100) NOT NULL COMMENT 'Nombre de la BD',
  `db_user` VARCHAR(50) NOT NULL COMMENT 'Usuario de la base de datos',
  `db_pass` VARCHAR(255) NOT NULL COMMENT 'Contraseña de la base de datos (root)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`domain`),
  UNIQUE KEY (`db_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de Unidades de Negocio (Tenants / Empresas)';

-- 2. Tabla Central de Usuarios (Globales de Ixea)
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_super` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Acceso global a todo el panel de Ixea',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de Usuarios Globales de Ixea';

-- 3. Tabla Pivote: Relación Usuario <-> Tenant (Membresías)
CREATE TABLE `tenant_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'Rol principal en este tenant (ej. admin, manager)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_tenant_unique` (`user_id`, `tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla Pivote: Relación Usuario <-> Tenant (Membresías)';

-- 4. Tenants  Info
CREATE TABLE `tenants_info` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slogan` VARCHAR(255) NULL,
  `rfc` VARCHAR(20) NOT NULL,
  `address` VARCHAR(255) NULL,
  `phone` VARCHAR(20) NULL,
  `whatsapp` VARCHAR(20) NULL,
  `website` VARCHAR(20) NULL,
  `legal_owner` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_info_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_info_rfc` (`rfc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de Información de Tenants';

ALTER TABLE `tenants_info`
ADD CONSTRAINT `fk_tenant_info_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON UPDATE CASCADE;