-- Script de creación de tablas para complemento Ixea Bistro

-- Configuración inicial
-- Zona horaria México-CDMX
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-06:00";

--
-- Base de datos: `ixea_db`
--
USE `ixea_db`;

--
-- 1. Estructura de tabla para `channels`
-- Registro de los canales de venta
--
CREATE TABLE `channels`(
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre del canal',
  `description` text NULL COMMENT 'Descripción del canal',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_channels_name` (`name`),
  KEY `idx_channels_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de canales';

-- --------------------------------------------------------

--
-- 1. Estructura de tabla para `menu`
-- Registro de la carta o menú del restaurante o negocio de comidas
--
CREATE TABLE `menu` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre del item',
  `description` varchar(255) NOT NULL COMMENT 'Descripción del item',
  `category_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de categorías',
  `station_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de estaciones',
  `price` decimal(10,2) NOT NULL COMMENT 'Precio del item',
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo del item',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 3=suspended, 4=discontinued, 5=archived',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_menu_category` (`category_id`),
  KEY `idx_menu_station` (`station_id`),
  KEY `idx_menu_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de la carta o menú del restaurante o negocio de comidas';

-- --------------------------------------------------------

--
-- 18. Estructura de tabla para `menu_categories`
-- Registro de categorías del menú
--
CREATE TABLE `menu_categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NULL COMMENT 'Descripción de la categoría',
  `emoji` varchar(10) NULL COMMENT 'Emoji de la categoría',
  `sort_order` int(10) UNSIGNED NULL COMMENT 'Orden de visualización de la categoría',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_menu_categories_name` (`name`),
  KEY `idx_menu_categories_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de categorías del menú';

-- --------------------------------------------------------

--
-- 18. Estructura de tabla para `menu_modifier_groups`
-- Registro de grupos de modificadores de menu
--
CREATE TABLE `menu_modifier_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto relacionado con la tabla menu',
  `group_id` int(10) UNSIGNED NOT NULL COMMENT 'Grupo de modificadores relacionado con la tabla modifier_groups',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mmg_prod_group` (`product_id`, `group_id`),
  KEY `idx_mmg_prod` (`product_id`),
  KEY `idx_mmg_group` (`group_id`),
  KEY `idx_mmg_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación de platillos del menú con sus múltiples grupos de modificadores';

-- --------------------------------------------------------

--
-- 18. Estructura de tabla para `modifiers`
-- Registro de modificadores de productos
--
CREATE TABLE `modifiers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NULL COMMENT 'Descripción del modificador',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio del modificador',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_modifiers_name` (`name`),
  KEY `idx_modifiers_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de modificadores de productos';

-- --------------------------------------------------------

--
-- 19. Estructura de tabla para `modifier_groups`
-- Registro de grupos de modificadores
--
CREATE TABLE `modifier_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre del grupo',
  `required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el modificador es obligatorio',
  `max_count` int(10) UNSIGNED NULL COMMENT 'Cantidad máxima de modificadores',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_modifier_groups_name` (`name`),
  KEY `idx_modifier_groups_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 20. Estructura de tabla para `modifier_grouped`
-- Registro de modificadores agrupados: Relaciona modificadores con grupos
--
CREATE TABLE `modifier_grouped` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL COMMENT 'Grupo de modificadores',
  `modifier_id` int(10) UNSIGNED NOT NULL COMMENT 'Modificador',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_modifier_grouped` (`group_id`, `modifier_id`),
  KEY `idx_modifier_grouped_group` (`group_id`),
  KEY `idx_modifier_grouped_modifier` (`modifier_id`),
  KEY `idx_modifier_grouped_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 29. Estructura de tabla para `orders`
-- Registro de ordenes
--
CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL COMMENT 'Código único de la orden',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que atendió la orden',
  `table_id` int(10) UNSIGNED NULL COMMENT 'Mesa asignada a la orden',
  `customer_id` int(10) UNSIGNED NULL COMMENT 'Cliente',
  `type` enum('take_away','delivery','dine_in') NOT NULL DEFAULT 'dine_in' COMMENT 'Tipo de orden',
  `people_count` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Cantidad de personas',
  `items` int(10) NOT NULL COMMENT 'Cantidad de productos vendidos',
  `is_taxable` tinyint(1) DEFAULT 0 COMMENT 'Indica si la orden se factura',
  `discount` decimal(12,2) DEFAULT 0.00 COMMENT 'Descuento aplicado a la venta',
  `amount` decimal(12,2) NOT NULL COMMENT 'Total de la venta',
  `shift_id` int(10) UNSIGNED NULL COMMENT 'Turno de caja',
  `paid_status` int(10) UNSIGNED NULL COMMENT 'Estado del pago: 6=pending, 7=paid, 9=cancelled, 10=returned',
  `method_id` int(10) UNSIGNED NULL COMMENT 'Método de pago',
  `reference` varchar(100) NULL COMMENT 'Referencia de pago',
  `notes` text NULL COMMENT 'Notas adicionales de la venta',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL COMMENT 'Fecha del pago',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_folio` (`folio`),
  KEY `idx_order_date` (`created_at`),
  KEY `idx_order_finance` (`created_at`,`customer_id`,`method_id`,`amount`),
  KEY `fk_order_user` (`user_id`),
  KEY `fk_order_customer` (`customer_id`),
  KEY `fk_order_shift` (`shift_id`),
  KEY `fk_order_method` (`method_id`),
  KEY `fk_order_paid` (`paid_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de órdenes';

-- --------------------------------------------------------

--
-- 28. Estructura de tabla para `order_items`
-- Registro de detalle de órdenes
--
CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NOT NULL COMMENT 'Orden',
  `item` int(10) UNSIGNED NOT NULL COMMENT 'Número de ítem',
  `target` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Persona que ordenó',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Item de la orden',
  `notes` text NULL COMMENT 'Notas adicionales de la orden',
  `status_id` int(10) UNSIGNED NOT NULL COMMENT 'Estado del registro: 6=pending, 8=delivered, 9=cancelled, 10=returned',
  `quantity` decimal(10,2) NOT NULL COMMENT 'Cantidad',
  `unitary` decimal(10,2) NOT NULL COMMENT 'Precio unitario',
  `discount` decimal(10,2) DEFAULT 0.00 COMMENT 'Descuento aplicado',
  `amount` decimal(10,2) NOT NULL COMMENT 'Total',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_at` timestamp NULL COMMENT 'Fecha de entrega',
  PRIMARY KEY (`id`),
  KEY `fk_orderitems_order` (`order_id`),
  KEY `fk_orderitems_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de items de órdenes';

-- --------------------------------------------------------

--
-- 29. Estructura de tabla para `order_item_modifiers`
-- Registro de modificadores de items de órdenes
--
CREATE TABLE `order_item_modifiers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL COMMENT 'Ítem de orden',
  `modifier_id` int(10) UNSIGNED NOT NULL COMMENT 'Modificador',
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Por si piden doble de algo (ej: Doble queso)',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio histórico cobrado por este modificador',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total (quantity * price)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_oim_item_modifier` (`item_id`, `modifier_id`),
  KEY `idx_oim_item` (`item_id`),
  KEY `idx_oim_modifier` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de los modificadores seleccionados para cada item de la orden';

-- --------------------------------------------------------

--
-- 21. Estructura de tabla para `production`
-- Registro productos elaborados en producción
--
CREATE TABLE `production` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL COMMENT 'Categoría del producto',
  `sku` varchar(50) NOT NULL COMMENT 'Código del producto',
  `name` varchar(200) NOT NULL COMMENT 'Nombre del producto',
  `description` text NULL COMMENT 'Descripción del producto',
  `unit_id` int(10) UNSIGNED NOT NULL COMMENT 'Unidad de medida',
  `stock` decimal(12,4) NOT NULL COMMENT 'Cantidad actual',
  `min_stock` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Stock mínimo',
  `max_stock` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Stock máximo',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 2=inactive, 3=suspended, 4=discontinued, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_production_sku` (`sku`),
  KEY `fk_production_category` (`category_id`),
  KEY `fk_production_unit` (`unit_id`),
  KEY `fk_production_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro productos elaborados en producción';

-- --------------------------------------------------------

--
-- 25. Estructura de tabla para `recipes`
-- Registro de recetas e insumos
--
CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` enum('menu','production') NOT NULL COMMENT 'Tipo de uso: menu o produccion',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto (unidad base)',
  `supply_type` enum('product','production') NOT NULL COMMENT 'Tipo de materia prima: producto o produccion',
  `supply_id` int(10) UNSIGNED NOT NULL COMMENT 'Materia prima (unidad base)',
  `quantity` decimal(12,4) NOT NULL COMMENT 'Cantidad de materia prima (unidad base)',
  PRIMARY KEY (`id`),
  KEY `idx_recipe_product` (`product_id`),
  KEY `idx_recipe_supply` (`supply_type`, `supply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de recetas e insumos';

-- --------------------------------------------------------

--
-- 12. Estructura de tabla para `estaciones`
-- Registro de estaciones (operativas)
--
CREATE TABLE `stations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Nombre de la estación',
  `description` text NULL COMMENT 'Descripción de la estación',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stations_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de estaciones (operativas)';

-- --------------------------------------------------------

--
-- 39. Estructura de tabla para `tables`
-- Registro de mesas de servicio
--
CREATE TABLE `tables` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la mesa o entidad',
  `area_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Relaciona la tabla de areas',
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Capacidad de personas',
  `pos_x` int(10) UNSIGNED NOT NULL COMMENT 'Posición en el eje X',
  `pos_y` int(10) UNSIGNED NOT NULL COMMENT 'Posición en el eje Y',
  `size` enum('S', 'M', 'L') NOT NULL COMMENT 'Tamaño de la mesa',
  `orientation` enum('H', 'V') NOT NULL COMMENT 'Orientación de la mesa',
  `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de personas en la mesa',
  `use_status_id` int(10) UNSIGNED NOT NULL DEFAULT 12 COMMENT 'Estado de uso: 6=pending, 11=open, 12=closed',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 3=suspended, 5=archived',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tables_area` (`area_id`),
  KEY `fk_tables_use_status` (`use_status_id`),
  KEY `fk_tables_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de mesas de servicio';

-- --------------------------------------------------------
-- RESTRICCIONES Y LLAVES FORÁNEAS
-- --------------------------------------------------------

-- Filtros para `channels`
ALTER TABLE `channels`
  ADD CONSTRAINT `fk_channels_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `menu`
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_menu_station` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_menu_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `menu_categories`
ALTER TABLE `menu_categories`
  ADD CONSTRAINT `fk_menu_categories_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `menu_modifier_groups`
ALTER TABLE `menu_modifier_groups`
  ADD CONSTRAINT `fk_mmg_prod` FOREIGN KEY (`product_id`) REFERENCES `menu` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mmg_group` FOREIGN KEY (`group_id`) REFERENCES `modifier_groups` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mmg_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `modifiers`
ALTER TABLE `modifiers`
  ADD CONSTRAINT `fk_modifiers_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `modifier_groups`
ALTER TABLE `modifier_groups`
  ADD CONSTRAINT `fk_modifier_groups_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `modifier_grouped`
ALTER TABLE `modifier_grouped`
  ADD CONSTRAINT `fk_modifier_grouped_group` FOREIGN KEY (`group_id`) REFERENCES `modifier_groups` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modifier_grouped_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `modifiers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modifier_grouped_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `orders`
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_paid` FOREIGN KEY (`paid_status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `order_items`
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `menu` (`id`) ON UPDATE CASCADE;

-- Filtros para `order_item_modifiers`
ALTER TABLE `order_item_modifiers`
  ADD CONSTRAINT `fk_oim_item` FOREIGN KEY (`item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oim_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `modifiers` (`id`) ON UPDATE CASCADE;

-- Filtros para `production`
ALTER TABLE `production`
  ADD CONSTRAINT `fk_production_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_production_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_production_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `recipes`
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

-- Filtros para `stations`
ALTER TABLE `stations`
  ADD CONSTRAINT `fk_stations_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `tables`
ALTER TABLE `tables`
  ADD CONSTRAINT `fk_tables_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tables_use_status` FOREIGN KEY (`use_status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tables_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

COMMIT;