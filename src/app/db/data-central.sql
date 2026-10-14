-- Script de llenado de datos de negocio Panga Urbana

-- Configuración inicial
-- Zona horaria México-CDMX
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-06:00";

--
-- Base de datos: `ixea_db`
--
USE `ixea_db`;

INSERT INTO `tenants`
(`id`, `name`, `domain`, `db_name`, `db_user`, `db_pass`)
VALUES
(0, 'Ixea', 'ixea.mx', 'u126819625_ixea_db', 'u126819625_ixea', 'JoKei1505.'),
(1, 'Tenant', 'tenant.mx', 'tenant_db', 'tenant', 'Tenant$2026.');

INSERT INTO `tenants_info`
(`tenant_id`, `name`, `slogan`, `rfc`, `address`, `phone`, `whatsapp`, `website`, `legal_owner`)
VALUES
(0, 'Ixea', 'Inteligencia para Construir Valor', 'PIMA920814GJ7', 'N/A', 'N/A', 'N/A', 'N/A', 'Ixea'),
(1, 'Tenant', 'Slogan Tenant', 'TENANT800101', 'Address', 'Phone', 'Whatsapp', 'Website', 'Tenant');

COMMIT;