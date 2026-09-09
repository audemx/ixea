-- Script de creación de tablas para base de datos Ixea EROS
-- Enterprise ERP Architecture

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
-- DEFINICIÓN DE TABLAS (ORDEN ALFABÉTICO A-Z)
-- --------------------------------------------------------

--
-- 0. Estructura de tabla para `apps`
-- Registro de aplicaciones del sistema
--
CREATE TABLE `apps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Código único de la app',
  `title` varchar(50) NOT NULL COMMENT 'Nombre visible de la app',
  `permission_id` int(10) UNSIGNED NOT NULL COMMENT 'Relación con el permiso requerido',
  `icon` varchar(50) NOT NULL COMMENT 'Clase del icono Bootstrap',
  `color_id` int(10) UNSIGNED NOT NULL COMMENT 'Relación con system_colors',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 3=suspended, 5=archived',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_apps_code` (`code`),
  KEY `idx_apps_permission_id` (`permission_id`),
  KEY `idx_apps_color_id` (`color_id`),
  KEY `fk_apps_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de aplicaciones del sistema';

-- --------------------------------------------------------

--
-- 1. Estructura de tabla para `accounts`
-- Registro de cuentas contables globales
--
CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NULL COMMENT 'Código de la cuenta',
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la cuenta',
  `balance_type` enum('asset','liability') NOT NULL DEFAULT 'asset' COMMENT 'Tipo de balance',
  `account_type` enum('cash','bank','receivable','fixed','credit_card','loan','payable','virtual') NOT NULL COMMENT 'Tipo de cuenta',
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo de la cuenta',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 3=suspended, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_accounts_code` (`code`),
  KEY `fk_accounts_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cuentas contables globales';

-- --------------------------------------------------------

--
-- 2. Estructura de tabla para `areas`
-- Registro de areas físicas o distribución de espacios
--
CREATE TABLE `areas` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre del area',
  `description` text NULL COMMENT 'Descripción del area',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_areas_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de areas físicas o distribución de espacios';

-- --------------------------------------------------------

--
-- 3. Estructura de tabla para `attendance`
-- Registro de asistencia de usuarios
--
CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID único autoincremental no nulo positivo',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que realiza el registro',
  `type` enum('in','out') NOT NULL COMMENT 'Tipo de registro',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora del registro',
  PRIMARY KEY (`id`),
  KEY `idx_user_attendance` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de asistencia de usuarios';

-- --------------------------------------------------------

--
-- 4. Estructura de tabla para `bank_accounts`
-- Registro de cuentas bancarias
--
CREATE TABLE `bank_accounts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `beneficiary_name` varchar(255) NULL COMMENT 'Nombre del beneficiario',
  `bank_name` varchar(100) NULL COMMENT 'Nombre del banco',
  `account_number` varchar(20) NULL COMMENT 'Número de cuenta',
  `clabe` varchar(20) NULL COMMENT 'Clave bancaria estandarizada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha y hora de la última actualización',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cuentas bancarias';

-- --------------------------------------------------------

--
-- 5. Estructura de tabla para `batches`
-- Registro de lotes de productos
--
CREATE TABLE `batches` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `barcode` varchar(50) NULL COMMENT 'Código de barras',
  `type` enum('product','production') NOT NULL COMMENT 'Tipo de lote',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto lotificado',
  `initial_stock` decimal(12,4) NOT NULL COMMENT 'Stock inicial',
  `stock` decimal(12,4) NOT NULL COMMENT 'Stock actual',
  `expires_on` date NULL COMMENT 'Fecha de caducidad',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch_product` (`product_id`),
  KEY `idx_batch_barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de lotes de productos';

-- --------------------------------------------------------

--
-- 6. Estructura de tabla para `brands`
-- Registro de marcas de productos
--
CREATE TABLE `brands` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la marca',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_brands_name` (`name`),
  KEY `fk_brands_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de marcas de productos';

-- --------------------------------------------------------

--
-- 7. Estructura de tabla para `categories`
-- Registro de categorías de productos
--
CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NULL COMMENT 'Descripción de la categoría',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 3=Suspended, 5=Archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`),
  KEY `fk_categories_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de categorías de productos';

-- --------------------------------------------------------

--
-- 8. Estructura de tabla para `customer_credit_payments`
-- Registro de pagos de créditos de clientes
--
CREATE TABLE `customer_credit_payments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de clientes',
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el cliente solicitó factura',
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Impuesto del pago',
  `amount` decimal(12,2) NOT NULL COMMENT 'Monto del pago',
  `concept` varchar(255) NOT NULL COMMENT 'Concepto del pago: Indica folio de ventas relacionadas',
  `shift_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de turnos',
  `method_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de métodos de pago',
  `reference` varchar(100) NULL COMMENT 'Referencia del pago',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 13 COMMENT 'Estatus del registro: 9=cancelled, 10=returned, 13=applied',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pay_cust` (`customer_id`),
  KEY `fk_pay_shift` (`shift_id`),
  KEY `fk_pay_method` (`method_id`),
  KEY `fk_pay_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de pagos de créditos de clientes';

-- --------------------------------------------------------

--
-- 9. Estructura de tabla para `customer_credit_profiles`
-- Registro de perfiles de crédito de clientes
--
CREATE TABLE `customer_credit_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de clientes',
  `credit_limit` decimal(12,2) DEFAULT 2000.00 COMMENT 'Límite de crédito',
  `credit_days` int(11) DEFAULT 30 COMMENT 'Días de crédito',
  `current_score` int(11) DEFAULT 100 COMMENT 'Puntaje de crédito',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cc_profiles_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de perfiles de crédito de clientes';

-- --------------------------------------------------------

--
-- 10. Estructura de tabla para `customer_credit_sales`
-- Registro de ventas a crédito
--
CREATE TABLE `customer_credit_sales` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de clientes',
  `sale_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de ventas',
  `amount` decimal(12,2) NULL COMMENT 'Monto del crédito',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Estatus del registro: 6=pending, 7=paid, 9=cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cc_sales_customer` (`customer_id`),
  KEY `idx_cc_sales_sale` (`sale_id`),
  KEY `idx_cc_sales_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de ventas a crédito';

-- --------------------------------------------------------

--
-- 11. Estructura de tabla para `customers`
-- Registro de clientes
--
CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) NULL,
  `phone` varchar(20) NULL,
  `birthdate` date NULL,
  `gender` enum('male','female') NULL COMMENT 'Género del cliente',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=Active, 2=Inactive, 3=Suspended, 5=Archived',
  `credit_status_id` int(10) UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Estatus del registro: 1=Active, 2=Inactive, 3=Suspended',
  `tax_profile_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de registros fiscales',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customers_tax_id` (`tax_profile_id`),
  UNIQUE KEY `uk_customers_email` (`email`),
  UNIQUE KEY `uk_customers_phone` (`phone`),
  KEY `fk_customers_status` (`status_id`),
  KEY `fk_customers_credit_status` (`credit_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de clientes';

-- --------------------------------------------------------

--
-- 13. Estructura de tabla para `expense_categories`
-- Registro de categorías de gastos
--
CREATE TABLE `expense_categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NULL COMMENT 'Código de la categoría',
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la categoría',
  `description` varchar(255) NULL COMMENT 'Descripción de la categoría',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 3=suspended, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_expense_categories_code` (`code`),
  KEY `fk_expense_categories_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de categorías de gastos';

-- --------------------------------------------------------

--
-- 14. Estructura de tabla para `expenses`
-- Registro de gastos
--
CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que registra el gasto',
  `category_id` int(10) UNSIGNED NOT NULL COMMENT 'Categoría del gasto',
  `account_id` int(10) UNSIGNED NULL COMMENT 'Cuenta contable que afecta',
  `method_id` int(10) UNSIGNED NULL COMMENT 'Método de pago',
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si se generó factura',
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Impuesto del gasto',
  `amount` decimal(12,2) NOT NULL COMMENT 'Monto del gasto',
  `concept` varchar(255) NOT NULL COMMENT 'Concepto detallado del gasto',
  `reference` varchar(100) NULL COMMENT 'Referencia de pago',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Estatus del registro: 6=pending, 7=paid, 9=cancelled, 10=returned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_expense_user` (`user_id`),
  KEY `fk_expense_category` (`category_id`),
  KEY `fk_expense_account` (`account_id`),
  KEY `fk_expense_method` (`method_id`),
  KEY `fk_expense_status` (`status_id`),
  KEY `idx_expense_category` (`category_id`,`status_id`, `created_at`),
  KEY `idx_expense_account` (`account_id`,`status_id`, `paid_at`),
  KEY `idx_expense_method` (`method_id`,`status_id`, `paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de gastos';

-- --------------------------------------------------------

--
-- 15. Estructura de tabla para `finance_snapshots`
-- Registro de estados financieros globales del negocio
--
CREATE TABLE `finance_snapshots` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de usuarios',
  `liquidity` decimal(12,2) NULL COMMENT 'Liquidez del negocio',
  `receivable` decimal(12,2) NULL COMMENT 'Cuentas por cobrar',
  `loans` decimal(12,2) NULL COMMENT 'Préstamos',
  `payable` decimal(12,2) NULL COMMENT 'Cuentas por pagar',
  `liability` decimal(12,2) NULL COMMENT 'Pasivos',
  `income` decimal(12,2) NULL COMMENT 'Ingresos',
  `expenses` decimal(15,2) NULL COMMENT 'Gastos',
  `payments` decimal(15,2) NULL COMMENT 'Pagos',
  `profit` decimal(15,2) NULL COMMENT 'Utilidad',
  `inventory` decimal(15,2) NULL COMMENT 'Inventario',
  `worth` decimal(15,2) NULL COMMENT 'Patrimonio',
  `dividend` decimal(15,2) NULL COMMENT 'Dividendos',
  `tax_income` decimal(15,2) NULL COMMENT 'Impuestos sobre ingresos',
  `tax_daily` decimal(15,2) NULL COMMENT 'Impuestos de factura global',
  `tax_expenses` decimal(15,2) NULL COMMENT 'Impuestos sobre gastos',
  `tax_payments` decimal(15,2) NULL COMMENT 'Impuestos sobre pagos',
  `tax_profit` decimal(15,2) NULL COMMENT 'Impuestos sobre utilidades',
  `iva_in` decimal(15,2) NULL COMMENT 'IVA de ingresos',
  `iva_out` decimal(15,2) NULL COMMENT 'IVA de gastos',
  `iva_net` decimal(15,2) NULL COMMENT 'IVA neto',
  `isr` decimal(15,2) NULL,
  `period` date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_finance_snapshots_period` (`period`),
  KEY `fk_finance_snapshots_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de estados financieros globales del negocio';

-- --------------------------------------------------------

--
-- 16. Estructura de tabla para `ledgers`
-- Registro de transacciones contables globales
--
CREATE TABLE `ledgers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de cuentas contables',
  `type` enum('in','out') NOT NULL COMMENT 'Tipo de transacción',
  `amount` decimal(12,2) NOT NULL COMMENT 'Monto de la transacción',
  `concept` varchar(255) NOT NULL COMMENT 'Concepto de la transacción',
  `table_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de referencia',
  `record_id` int(10) UNSIGNED NULL COMMENT 'ID de la referencia',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `idx_ledger_systable` (`table_id`, `record_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de transacciones contables globales';

-- --------------------------------------------------------

--
-- 18. Estructura de tabla para `payment_methods`
-- Registro de métodos de pago
--
CREATE TABLE `payment_methods` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL COMMENT 'Código del método de pago',
  `name` varchar(50) NOT NULL COMMENT 'Nombre del método de pago',
  `account_in` int(10) UNSIGNED NULL COMMENT 'Cuenta contable de ingreso',
  `account_out` int(10) UNSIGNED NULL COMMENT 'Cuenta contable de egreso',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 3=suspended, 5=archived',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_methods_code` (`code`),
  KEY `fk_methods_account_in` (`account_in`),
  KEY `fk_methods_account_out` (`account_out`),
  KEY `fk_methods_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de métodos de pago';

-- --------------------------------------------------------

--
-- 19. Estructura de tabla para `permissions`
-- Registro de permisos de usuarios
--
CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT 'Clave del permiso',
  `name` varchar(50) NOT NULL COMMENT 'Nombre del permiso',
  `module` varchar(50) NULL COMMENT 'Módulo al que pertenece el permiso',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de permisos de usuarios';

-- --------------------------------------------------------

--
-- 20. Estructura de tabla para `product_units`
-- Registro de unidades de compra, venta y conversión de los productos
--
CREATE TABLE `product_units` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `barcode` varchar(50) NULL COMMENT 'Código de barras',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto',
  `unit_id` int(10) UNSIGNED NOT NULL COMMENT 'Unidad de medida',
  `factor` decimal(12,4) NOT NULL DEFAULT 1.0000 COMMENT 'Factor de conversión (Si es 1, es la unidad de medida base)',
  `price` decimal(12,4) DEFAULT 0.0000 COMMENT 'Precio de venta',
  `cost` decimal(12,4) DEFAULT 0.0000 COMMENT 'Precio de compra',
  `type` enum('both','buy','sell') NOT NULL DEFAULT 'buy' COMMENT 'Tipo de uso de la unidad',
  `supplier_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de proveedores',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_punits_unit` (`product_id`,`unit_id`),
  KEY `idx_unit_barcode` (`barcode`),
  KEY `fk_unit_supplier` (`supplier_id`),
  KEY `fk_unit_master` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de unidades de compra, venta y conversión de los productos';

-- --------------------------------------------------------

--
-- 22. Estructura de tabla para `products`
-- Registro de productos
--
CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NULL COMMENT 'Categoría del producto',
  `brand_id` int(10) UNSIGNED NULL COMMENT 'Marca del producto',
  `sku` varchar(50) NOT NULL COMMENT 'Código del producto',
  `name` varchar(200) NOT NULL COMMENT 'Nombre del producto',
  `description` text NULL COMMENT 'Descripción del producto',
  `unit_id` int(10) UNSIGNED NOT NULL COMMENT 'Unidad de medida',
  `is_taxable` tinyint(1) DEFAULT 1 COMMENT 'Incluye impuestos',
  `stock` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Stock actual',
  `min_stock` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Stock mínimo',
  `max_stock` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Stock máximo',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 2=inactive, 3=suspended, 4=discontinued, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_sku` (`sku`),
  KEY `fk_product_category` (`category_id`),
  KEY `fk_product_brand` (`brand_id`),
  KEY `fk_product_unit` (`unit_id`),
  KEY `fk_product_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de productos';

-- --------------------------------------------------------

--
-- 23. Estructura de tabla para `purchase_details`
-- Registro de detalles de compra
--
CREATE TABLE `purchase_details` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) UNSIGNED NOT NULL COMMENT 'Orden de compra',
  `item` int(10) UNSIGNED NOT NULL COMMENT 'Número de ítem',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto',
  `unit_id` int(10) UNSIGNED NULL COMMENT 'Unidad de compra',
  `quantity` decimal(12,4) NOT NULL COMMENT 'Cantidad',
  `unitary` decimal(12,2) NOT NULL COMMENT 'Costo unitario',
  `discount` decimal(12,2) DEFAULT 0.00 COMMENT 'Descuento aplicado',
  `tax` decimal(12,2) DEFAULT 0.00 COMMENT 'Impuesto',
  `amount` decimal(12,2) NOT NULL COMMENT 'Total',
  PRIMARY KEY (`id`),
  KEY `fk_pdetails_purchase` (`purchase_id`),
  KEY `fk_pdetails_product` (`product_id`),
  KEY `fk_pdetails_unit` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de detalles de compra';

-- --------------------------------------------------------

--
-- 24. Estructura de tabla para `purchases`
-- Registro de compras
--
CREATE TABLE `purchases` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL COMMENT 'Código único de la compra',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de usuarios (quien genera)',
  `payer_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de usuarios (quien paga)',
  `handler_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de usuarios (quien recibe)',
  `supplier_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de proveedores',
  `is_taxable` tinyint(1) DEFAULT 0 COMMENT 'Indica si la compra se facturó',
  `items` int(11) NOT NULL COMMENT 'Cantidad de items en la compra',
  `amount` decimal(12,2) NOT NULL COMMENT 'Monto total de la compra',
  `paid_status` int(10) UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Estado del registro: 6=pending, 7=paid, 9=cancelled, 10=returned',
  `method_id` int(10) UNSIGNED NULL COMMENT 'Relaciona la tabla de métodos de pago',
  `reference` varchar(100) NULL COMMENT 'Referencia del pago',
  `received_status` int(10) UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Estado del registro: 6=pending, 8=received, 9=cancelled, 10=returned',
  `notes` text NULL COMMENT 'Notas de la compra',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL COMMENT 'Fecha del pago', 
  `received_at` timestamp NULL COMMENT 'Fecha de recepción',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_purchase_folio` (`folio`),
  KEY `idx_purchase_date` (`created_at`),
  KEY `idx_purchase_finance` (`created_at`,`supplier_id`,`method_id`,`is_taxable`,`amount`),
  KEY `fk_purchase_user` (`user_id`),
  KEY `fk_purchase_supplier` (`supplier_id`),
  KEY `fk_purchase_payer` (`payer_id`),
  KEY `fk_purchase_handler` (`handler_id`),
  KEY `fk_purchase_method` (`method_id`),
  KEY `fk_purchase_paid_status` (`paid_status`),
  KEY `fk_purchase_received_status` (`received_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de compras';

-- --------------------------------------------------------

--
-- 26. Estructura de tabla para `role_permissions`
-- Asignación de permisos a roles
--
CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) UNSIGNED NOT NULL COMMENT 'Rol',
  `permission_id` int(10) UNSIGNED NOT NULL COMMENT 'Permiso',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 3=suspended, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `fk_permission_role` (`permission_id`),
  KEY `fk_role_perm_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Asignación de permisos a roles';

-- --------------------------------------------------------

--
-- 27. Estructura de tabla para `roles`
-- Registro de roles del sistema
--
CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`code`),
  UNIQUE KEY `uk_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de roles del sistema';

-- --------------------------------------------------------

--
-- 28. Estructura de tabla para `sale_details`
-- Registro de detalle de ventas
--
CREATE TABLE `sale_details` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` int(10) UNSIGNED NOT NULL COMMENT 'Orden de venta',
  `item` int(10) UNSIGNED NOT NULL COMMENT 'Número de ítem',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto',
  `unit_id` int(10) UNSIGNED NULL COMMENT 'Unidad de venta',
  `quantity` decimal(12,4) NOT NULL COMMENT 'Cantidad',
  `unitary` decimal(12,2) NOT NULL COMMENT 'Precio unitario',
  `discount` decimal(12,2) DEFAULT 0.00 COMMENT 'Descuento aplicado',
  `tax` decimal(12,2) DEFAULT 0.00 COMMENT 'Impuesto',
  `amount` decimal(12,2) NOT NULL COMMENT 'Total',
  PRIMARY KEY (`id`),
  KEY `fk_sdetails_sale` (`sale_id`),
  KEY `fk_sdetails_product` (`product_id`),
  KEY `fk_sdetails_unit` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de detalle de ventas';

-- --------------------------------------------------------

--
-- 29. Estructura de tabla para `sales`
-- Registro de ventas
--
CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL COMMENT 'Código único de la venta',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que realizó la venta',
  `handler_id` int(10) UNSIGNED NULL COMMENT 'Usuario que entregó',
  `customer_id` int(10) UNSIGNED NULL COMMENT 'Cliente',
  `items` int(11) NOT NULL COMMENT 'Cantidad de productos vendidos',
  `is_taxable` tinyint(1) DEFAULT 0 COMMENT 'Indica si la venta se factura',
  `discount` decimal(12,2) DEFAULT 0.00 COMMENT 'Descuento aplicado a la venta',
  `amount` decimal(12,2) NOT NULL COMMENT 'Total de la venta',
  `shift_id` int(10) UNSIGNED NULL COMMENT 'Turno de la venta',
  `paid_status` int(10) UNSIGNED NULL COMMENT 'Estado del pago: 6=pending, 7=paid, 9=cancelled, 10=returned',
  `method_id` int(10) UNSIGNED NULL COMMENT 'Método de pago',
  `reference` varchar(100) NULL COMMENT 'Referencia de pago',
  `delivered_status` int(10) UNSIGNED NULL COMMENT 'Estado de la entrega: 6=pending, 8=delivered, 9=cancelled, 10=returned',
  `notes` text NULL COMMENT 'Notas adicionales de la venta',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL COMMENT 'Fecha del pago',
  `delivered_at` timestamp NULL COMMENT 'Fecha de la entrega',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sale_folio` (`folio`),
  KEY `idx_sale_date` (`created_at`),
  KEY `idx_sale_finance` (`created_at`,`customer_id`,`method_id`,`is_taxable`,`amount`),
  KEY `fk_sale_user` (`user_id`),
  KEY `fk_sale_customer` (`customer_id`),
  KEY `fk_sale_shift` (`shift_id`),
  KEY `fk_sale_handler` (`handler_id`),
  KEY `fk_sales_method` (`method_id`),
  KEY `fk_sales_paid` (`paid_status`),
  KEY `fk_sales_delivered` (`delivered_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de ventas';

-- --------------------------------------------------------

--
-- 30. Estructura de tabla para `shifts`
-- Registro de turnos de cajero
--
CREATE TABLE `shifts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de usuarios',
  `opening_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de apertura',
  `closing_time` timestamp NULL COMMENT 'Fecha de cierre',
  `opening_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto inicial',
  `system_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'Lo que el sistema calcula',
  `real_amount` decimal(12,2) NULL COMMENT 'Lo que el cajero cuenta',
  `notes` text NULL COMMENT 'Notas del turno',
  PRIMARY KEY (`id`),
  KEY `idx_user_opening` (`user_id`,`opening_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de turnos de cajero';

-- --------------------------------------------------------

--
-- 31. Estructura de tabla para `statuses`
-- Tabla para el registro de estados transaccionales
--
CREATE TABLE `statuses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Nombre del estado',
  `color` int(10) UNSIGNED NULL COMMENT 'Color asociado al estado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_color` (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para el registro de estados transaccionales';

-- --------------------------------------------------------

--
-- 32. Estructura de tabla para `stock_movements`
-- Registro de movimientos de stock
--
CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Producto',
  `quantity` decimal(12,4) NOT NULL COMMENT 'Cantidad en unidad principal',
  `type` enum('in','out') NOT NULL COMMENT 'Tipo de movimiento',
  `unit_id` int(10) UNSIGNED NULL COMMENT 'Unidad de referencia',
  `table_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con la tabla system_tables',
  `record_id` int(10) UNSIGNED NULL COMMENT 'ID del registro origen',
  `batch_id` int(10) UNSIGNED NULL COMMENT 'ID del lote',
  `notes` text NULL COMMENT 'Notas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha del movimiento',
  PRIMARY KEY (`id`),
  KEY `idx_product_movement` (`product_id`),
  KEY `idx_stock_systable` (`table_id`,`record_id`,`created_at`),
  KEY `fk_move_unit` (`unit_id`),
  KEY `fk_move_batch` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de movimientos de stock';

-- --------------------------------------------------------

--
-- 33. Estructura de tabla para `supplier_credit_profiles`
-- Registro de perfiles de crédito de proveedores
--
CREATE TABLE `supplier_credit_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `credit_limit` decimal(12,2) DEFAULT 10000.00,
  `credit_days` int(11) DEFAULT 30,
  `current_score` int(11) DEFAULT 100,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scp_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de perfiles de crédito de proveedores';

-- --------------------------------------------------------

--
-- 34. Estructura de tabla para `suppliers`
-- Registro de proveedores
--
CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NULL,
  `email` varchar(100) NULL,
  `phone` varchar(20) NULL,
  `tax_profile_id` int(10) UNSIGNED NOT NULL,
  `delivery_days` int(11) DEFAULT 7,
  `bank_account` int(10) UNSIGNED NULL,
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estado del registro: 1=active, 2=inactive, 3=suspended, 5=archived',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_supplier_tax_id` (`tax_profile_id`),
  UNIQUE KEY `uk_supplier_email` (`email`),
  UNIQUE KEY `uk_supplier_phone` (`phone`),
  KEY `fk_supplier_bank` (`bank_account`),
  KEY `fk_supplier_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de proveedores';

-- --------------------------------------------------------

--
-- 35. Estructura de tabla para `system_actions`
-- Registro de acciones del sistema que puede realizar un usuario
--
CREATE TABLE `system_actions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Nombre de la acción',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus de la acción: 1=active, 2=inactive, 3=suspended, 5=archived',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sysaction_name` (`name`),
  KEY `fk_sysaction_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de acciones del sistema que puede realizar un usuario';

-- --------------------------------------------------------

--
-- 36. Estructura de tabla para `system_colors`
-- Registro de colores del sistema
--
CREATE TABLE `system_colors` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Nombre del color',
  `hex` varchar(7) NOT NULL COMMENT 'Código hexadecimal del color',
  `description` varchar(255) NULL COMMENT 'Descripción del color',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_syscolor_name` (`name`),
  UNIQUE KEY `uk_syscolor_hex` (`hex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de colores del sistema';

-- --------------------------------------------------------

--
-- 37. Estructura de tabla para `system_logs`
-- Registro de bitácora del sistema
--
CREATE TABLE `system_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con la tabla users',
  `action_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con la tabla system_actions',
  `status_id` int(10) UNSIGNED NOT NULL COMMENT 'Estatus del proceso 14=success, 15=error',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `table_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona con la tabla system_tables',
  `record_id` int(10) UNSIGNED NULL COMMENT 'ID de la referencia',
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(512) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_syslog_user` (`user_id`),
  KEY `fk_syslog_action` (`action_id`),
  KEY `idx_syslog_systable` (`table_id`,`record_id`,`created_at`),
  KEY `fk_syslog_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de bitácora del sistema';

-- --------------------------------------------------------

--
-- 38. Estructura de tabla para `system_tables`
-- Registro de todas las tablas del sistema
--
CREATE TABLE `system_tables` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Nombre de la tabla',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus de la tabla: 1=active, 2=inactive, 4=discontinued, 5=archived',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_systable_name` (`name`),
  KEY `fk_systable_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de todas las tablas del sistema';

-- --------------------------------------------------------

--
-- 40. Estructura de tabla para `tax_profiles`
-- Registro de información fiscal
--
CREATE TABLE `tax_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rfc` varchar(13) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `tax_regime` varchar(100) NULL,
  `billing_email` varchar(100) NULL,
  `address_street` varchar(150) NULL,
  `address_ext_num` varchar(20) NULL,
  `address_int_num` varchar(20) NULL,
  `address_neighborhood` varchar(150) NULL,
  `address_city` varchar(150) NULL,
  `address_state` varchar(150) NULL,
  `address_zip` varchar(10) NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_taxprofile_rfc` (`rfc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de información fiscal';

-- --------------------------------------------------------

--
-- 41. Estructura de tabla para `till_movements`
-- Movimientos de caja / turno
--
CREATE TABLE `till_movements` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('in','out') NOT NULL COMMENT 'Entrada o Salida',
  `amount` decimal(12,2) NOT NULL,
  `concept` varchar(255) NOT NULL,
  `method_id` int(10) UNSIGNED NULL COMMENT 'Método de pago',
  `table_id` int(10) UNSIGNED NOT NULL COMMENT 'Relaciona la tabla de referencia',
  `record_id` int(10) UNSIGNED NULL COMMENT 'ID de la referencia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shift_type` (`shift_id`, `created_at`),
  KEY `idx_tillmove_systable` (`table_id`, `record_id`, `created_at`),
  KEY `fk_tillmove_shift` (`shift_id`),
  KEY `fk_tillmove_user` (`user_id`),
  KEY `fk_tillmove_method` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Movimientos de caja / turno';

-- --------------------------------------------------------

--
-- 42. Estructura de tabla para `units`
-- Registro de unidades de medida
--
CREATE TABLE `units` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 3=suspended, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unit_code` (`code`),
  UNIQUE KEY `uk_unit_name` (`name`),
  KEY `fk_units_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de unidades de medida';

-- --------------------------------------------------------

--
-- 43. Estructura de tabla para `users`
-- Registro de usuarios del sistema
--
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) UNSIGNED NULL COMMENT 'Rol que tiene asignado',
  `tax_profile_id` int(10) UNSIGNED NOT NULL COMMENT 'Información fiscal',
  `email` varchar(150) NOT NULL COMMENT 'Correo electrónico',
  `phone` varchar(15) NULL COMMENT 'Número de teléfono',
  `password_hash` varchar(255) NULL COMMENT 'Hash de la contraseña',
  `google_id` varchar(50) NULL COMMENT 'ID de cuenta de Google',
  `first_name` varchar(100) NOT NULL COMMENT 'Nombre',
  `last_name` varchar(100) NULL COMMENT 'Apellido',
  `auth_pin` char(4) NULL COMMENT 'PIN de 4 digitos para autenticación rápida',
  `gmail_token` text NULL COMMENT 'Token de cuenta de Google',
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Estatus del registro: 1=active, 2=inactive, 3=suspended, 5=archived',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_tax_id` (`tax_profile_id`),
  UNIQUE KEY `uk_user_email` (`email`),
  UNIQUE KEY `uk_user_google_id` (`google_id`),
  KEY `fk_user_role` (`role_id`),
  KEY `fk_user_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de usuarios del sistema';

-- --------------------------------------------------------
-- RESTRICCIONES Y LLAVES FORÁNEAS
-- --------------------------------------------------------

-- Filtros para `apps`
ALTER TABLE `apps`
  ADD CONSTRAINT `fk_apps_permissions` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_apps_colors` FOREIGN KEY (`color_id`) REFERENCES `system_colors` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_apps_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `accounts`
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `areas`
ALTER TABLE `areas`
  ADD CONSTRAINT `fk_areas_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `attendance`
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

-- Filtros para `batches`
ALTER TABLE `batches`
  ADD CONSTRAINT `fk_batches_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

-- Filtros para `brands`
ALTER TABLE `brands`
  ADD CONSTRAINT `fk_brands_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `categories`
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `customer_credit_payments`
ALTER TABLE `customer_credit_payments`
  ADD CONSTRAINT `fk_cc_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_payments_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_payments_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_payments_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `customer_credit_profiles`
ALTER TABLE `customer_credit_profiles`
  ADD CONSTRAINT `fk_cc_profiles_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

-- Filtros para `customer_credit_sales`
ALTER TABLE `customer_credit_sales`
  ADD CONSTRAINT `fk_cc_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_sales_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_sales_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `customers`
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_rfc` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customers_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_status` FOREIGN KEY (`credit_status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `expense_categories`
ALTER TABLE `expense_categories`
  ADD CONSTRAINT `fk_expense_categories_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `expenses`
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `finance_snapshots`
ALTER TABLE `finance_snapshots`
  ADD CONSTRAINT `fk_finance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

-- Filtros para `ledgers`
ALTER TABLE `ledgers`
  ADD CONSTRAINT `fk_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ledger_systable` FOREIGN KEY (`table_id`) REFERENCES `system_tables` (`id`) ON UPDATE CASCADE;

-- Filtros para `payment_methods`
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_account_in` FOREIGN KEY (`account_in`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_account_out` FOREIGN KEY (`account_out`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `product_units`
ALTER TABLE `product_units`
  ADD CONSTRAINT `fk_unit_master` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unit_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unit_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

-- Filtros para `products`
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prod_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_main_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `purchase_details`
ALTER TABLE `purchase_details`
  ADD CONSTRAINT `fk_pdetails_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pdetails_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pdetails_unit` FOREIGN KEY (`unit_id`) REFERENCES `product_units` (`id`) ON UPDATE CASCADE;

-- Filtros para `purchases`
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_payer` FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_handler` FOREIGN KEY (`handler_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_paid_status` FOREIGN KEY (`paid_status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_received_status` FOREIGN KEY (`received_status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `role_permissions`
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_perm_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_perm_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `sale_details`
ALTER TABLE `sale_details`
  ADD CONSTRAINT `fk_sdetails_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sdetails_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sdetails_unit` FOREIGN KEY (`unit_id`) REFERENCES `product_units` (`id`) ON UPDATE CASCADE;

-- Filtros para `sales`
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_handler` FOREIGN KEY (`handler_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_paid` FOREIGN KEY (`paid_status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_delivered` FOREIGN KEY (`delivered_status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `shifts`
ALTER TABLE `shifts`
  ADD CONSTRAINT `fk_shifts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

-- Filtros para `statuses`
ALTER TABLE `statuses`
  ADD CONSTRAINT `fk_statuses_color` FOREIGN KEY (`color`) REFERENCES `system_colors` (`id`) ON UPDATE CASCADE;

-- Filtros para `stock_movements`
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stockmove_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stockmove_unit` FOREIGN KEY (`unit_id`) REFERENCES `product_units` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stockmove_systable` FOREIGN KEY (`table_id`) REFERENCES `system_tables` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stockmove_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON UPDATE CASCADE;

-- Filtros para `supplier_credit_profiles`
ALTER TABLE `supplier_credit_profiles`
  ADD CONSTRAINT `fk_scp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

-- Filtros para `suppliers`
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_supplier_rfc` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_supplier_bank` FOREIGN KEY (`bank_account`) REFERENCES `bank_accounts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_supplier_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `system_actions`
ALTER TABLE `system_actions`
  ADD CONSTRAINT `fk_system_actions_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `system_logs`
ALTER TABLE `system_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_logs_action` FOREIGN KEY (`action_id`) REFERENCES `system_actions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_logs_systable` FOREIGN KEY (`table_id`) REFERENCES `system_tables` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_logs_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `system_tables`
ALTER TABLE `system_tables`
  ADD CONSTRAINT `fk_sys_tables_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `till_movements`
ALTER TABLE `till_movements`
  ADD CONSTRAINT `fk_tillmove_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tillmove_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tillmove_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tillmove_systable` FOREIGN KEY (`table_id`) REFERENCES `system_tables` (`id`) ON UPDATE CASCADE;

-- Filtros para `units`
ALTER TABLE `units`
  ADD CONSTRAINT `fk_units_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

-- Filtros para `users`
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_tax_id` FOREIGN KEY (`tax_profile_id`) REFERENCES `tax_profiles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE;

COMMIT;
