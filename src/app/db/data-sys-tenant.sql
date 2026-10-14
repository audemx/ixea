-- Script de llenado de datos de sistema para Ixea EROS

-- Configuración inicial
-- Zona horaria México-CDMX
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-06:00";

--
-- Base de datos: `tenant_db`
--
USE `tenant_db`;

-- --------------------------------------------------------
-- DATOS DE SISTEMA
-- --------------------------------------------------------

-- Llenado de colores de sistema
INSERT INTO `sys_colors` (`id`, `name`, `hex`, `description`) VALUES
(1, 'black', '#2c3e50', 'Color negro'),
(2, 'blue', '#84b6f4', 'Color azul'),
(3, 'green', '#9DD6AD', 'Color verde'),
(4, 'cyan', '#00cfd5', 'Color cyan'),
(5, 'purple', '#9b59b6', 'Color morado'),
(6, 'gray', '#495057', 'Color gris'),
(7, 'brown', '#A52A2A', 'Color marron'),
(8, 'silver', '#C0C0C0', 'Color plateado'),
(9, 'bronze', '#CD7F32', 'Color bronce'),
(10, 'beige', '#F5F5DC', 'Color beige'),
(11, 'red', '#ff6961', 'Color rojo'),
(12, 'magenta', '#CF71AE', 'Color magenta'),
(13, 'orange', '#e67e22', 'Color naranja'),
(14, 'pink', '#FFC0CB', 'Color rosa'),
(15, 'gold', '#D4AF37', 'Color dorado'),
(16, 'yellow', '#f1c40f', 'Color amarillo'),
(17, 'white', '#FFFFFF', 'Color blanco');

-- Llenado de statuses de base de datos
INSERT INTO `statuses` (`id`, `name`, `color`) VALUES
(1, 'active', 3), -- registros activos en general
(2, 'inactive', 6), -- registros inactivos en general
(3, 'suspended', 13), -- objeto suspendido (se bloquea uso temporalmente, pero sigue visible)
(4, 'discontinued', 7), -- objeto descontinuado (permite salidas, pero evita ingresos)
(5, 'archived', 1), -- objeto eliminado (se oculta completamente, evita ingresos y salidas)
(6, 'pending', 6), -- objeto pendiente (está en proceso)
(7, 'paid', 3), -- objeto pagado
(8, 'delivered', 3), -- objeto entregado
(9, 'cancelled', 11), -- objeto cancelado
(10, 'returned', 11), -- objeto devuelto
(11, 'open', 3), -- objeto abierto
(12, 'closed', 6), -- objeto cerrado
(13, 'applied', 3), -- objeto aplicado
(14, 'success', 3), -- proceso exitoso
(15, 'error', 11), -- proceso con error
(16, 'rejected', 6); -- proceso rechazado

-- Llenado de acciones de sistema
INSERT INTO `sys_actions` (`id`, `name`, `status_id`) VALUES
-- CRUD Base
(1, 'read', 1),
(2, 'create', 1),
(3, 'update', 1),
(4, 'delete', 1),
(5, 'restore', 1),
-- Flujos de Negocio y Control (ERP)
(6, 'approve', 1),
(7, 'reject', 1),
(8, 'cancel', 1),
(9, 'post', 1),
-- Archivos e Informes
(10, 'upload', 1),
(11, 'download', 1),
(12, 'export', 1),
(13, 'print', 1),
-- Sesión y Seguridad
(14, 'login', 1),
(15, 'logout', 1),
(16, 'auth', 1),
(17, 'assign', 1),
-- Sistema e Integraciones
(18, 'launch', 1),
(19, 'terminate', 1),
(20, 'sync', 1);

-- Llenado dinámico de `sys_tables`
INSERT INTO `sys_tables` (`id`, `name`, `is_enum`, `is_pivot`, `is_catalog`) VALUES
(1, 'bank_accounts', 0, 0, 0),
(2, 'permissions', 1, 0, 0),
(3, 'roles', 0, 0, 0),
(4, 'sys_colors', 1, 0, 0),
(5, 'tax_profiles', 0, 0, 1),
(6, 'apps', 0, 0, 0),
(7, 'accounts', 1, 0, 1),
(8, 'areas', 0, 0, 1),
(9, 'attendance', 0, 0, 0),
(10, 'batches', 0, 0, 0),
(11, 'brands', 0, 0, 1),
(12, 'categories', 0, 0, 1),
(13, 'customer_credit_payments', 0, 0, 0),
(14, 'customer_credit_profiles', 0, 0, 0),
(15, 'customer_credit_sales', 0, 0, 0),
(16, 'customers', 0, 0, 1),
(17, 'expense_categories', 0, 0, 0),
(18, 'expenses', 0, 0, 0),
(19, 'finance_snapshots', 0, 0, 0),
(20, 'ledgers', 0, 0, 0),
(21, 'payment_methods', 1, 0, 0),
(22, 'product_units', 0, 1, 1),
(23, 'products', 0, 0, 1),
(24, 'purchase_details', 0, 0, 0),
(25, 'purchases', 0, 0, 0),
(26, 'role_permissions', 0, 1, 0),
(27, 'sale_details', 0, 0, 0),
(28, 'sales', 0, 0, 0),
(29, 'shifts', 0, 0, 0),
(30, 'statuses', 1, 0, 0),
(31, 'stock_movements', 0, 0, 0),
(32, 'supplier_credit_profiles', 0, 0, 0),
(33, 'suppliers', 0, 0, 1),
(34, 'sys_actions', 1, 0, 0),
(35, 'sys_logs', 0, 0, 0),
(36, 'sys_tables', 1, 0, 0),
(37, 'till_movements', 0, 0, 0),
(38, 'units', 0, 0, 1),
(39, 'users', 0, 0, 1),
(40, 'channels', 1, 0, 0),
(41, 'menu', 0, 0, 1),
(42, 'menu_categories', 0, 0, 1),
(43, 'menu_modifier_groups', 0, 1, 0),
(44, 'modifiers', 0, 0, 1),
(45, 'modifier_groups', 0, 0, 1),
(46, 'modifier_grouped', 0, 1, 0),
(47, 'orders', 0, 0, 0),
(48, 'order_external_details', 0, 0, 0),
(49, 'order_items', 0, 0, 0),
(50, 'order_item_modifiers', 0, 0, 0),
(51, 'order_payments', 0, 0, 0),
(52, 'production', 0, 0, 0),
(53, 'recipes', 0, 0, 1),
(54, 'stations', 0, 0, 1),
(55, 'tables', 0, 0, 1);

-- Llenado de roles
INSERT INTO `roles`
(`id`, `parent_id`, `name`, `description`)
VALUES 
(0, null, 'superadmin', 'Super Admin: Acceso total al sistema.'),
(1, null, 'admin', 'Admin: Control Administrativo'),
(2, 1, 'manager', 'Gerente: Responsable Operativo'),
(3, 2, 'waiter', 'Mesero');

-- Llenado de permisos
INSERT INTO `permissions` (`id`, `name`, `description`, `module`) VALUES
(1, 'app_till', 'Acceso a App Caja', 'Caja'),
(2, 'app_purchases', 'Acceso a App Compras', 'Compras'),
(3, 'app_payments', 'Acceso a App Pagos', 'Pagos'),
(4, 'app_receipts', 'Acceso a App Entradas', 'Compras'),
(5, 'app_credit', 'Acceso a App Crédito', 'Crédito'),
(6, 'app_checker', 'Acceso a App Checador', 'RRHH'),
(7, 'app_stock', 'Acceso a App Inventario', 'Inventario'),
(8, 'app_customers', 'Acceso a App Clientes', 'Clientes'),
(9, 'app_users', 'Acceso a App Usuarios', 'Usuarios'),
(10, 'app_suppliers', 'Acceso a App Proveedores', 'Proveedores'),
(11, 'app_employees', 'Acceso a App Empleados', 'RRHH'),
(12, 'app_catalogs', 'Acceso a App Catálogos', 'Admin'),
(13, 'app_system', 'Acceso a App Sistema', 'Sistema'),
(14, 'app_reports', 'Acceso a App Reportes', 'Reportes'),
(15, 'app_finances', 'Acceso a App Finanzas', 'Finanzas'),
(16, 'app_bistro_pos', 'Acceso a App Bistro POS', 'Ventas'),
(17, 'app_bistro_kds', 'Acceso a App Bistro KDS', 'Producción'),
(18, 'can_waiter', 'Permiso para meserear', 'Ventas'),
(19, 'app_sales', 'Acceso a App Ventas', 'Ventas'),
(20, 'app_deliveries', 'Acceso a App Entregas', 'Ventas'),
(21, 'app_bistro_till', 'Acceso a App Bistro Caja', 'Caja');

INSERT INTO `role_permissions`
(`role_id`, `permission_id`)
VALUES
(1, 9),
(2, 21),
(3, 16),
(3, 17),
(3, 18);

-- Llenado de apps
INSERT INTO `apps` (`id`, `code`, `title`, `permission_id`, `icon`, `color_id`) VALUES
(1, 'till', 'Caja', 1, 'bi-safe2-fill', 3),
(2, 'purchases', 'Compras', 2, 'bi-bag-check-fill', 13),
(3, 'payments', 'Pagos', 3, 'bi-cash-coin', 13),
(4, 'receipt', 'Entradas', 4, 'bi-journal-check', 13),
(5, 'credit', 'Crédito', 5, 'bi-credit-card-2-front-fill', 3),
(6, 'checker', 'Checador', 6, 'bi-clock-history', 12),
(7, 'stock', 'Inventario', 7, 'bi-boxes', 5),
(8, 'customers', 'Clientes', 8, 'bi-person-badge-fill', 2),
(9, 'users', 'Usuarios', 9, 'bi-people-fill', 2),
(10, 'suppliers', 'Proveedores', 10, 'bi-truck-flatbed', 13),
(11, 'employees', 'RRHH', 11, 'bi-person-vcard-fill', 12),
(12, 'catalogs', 'Catalogos', 12, 'bi-journal-bookmark-fill', 6),
(13, 'system', 'Respaldos', 13, 'bi-shield-lock-fill', 6),
(14, 'reports', 'Reportes', 14, 'bi-graph-up-arrow', 4),
(15, 'finances', 'Finanzas', 15, 'bi-bank2', 4),
(16, 'bistro-pos', 'Bistro POS', 16, 'bi-fork-knife', 3),
(17, 'bistro-kds', 'Bistro KDS', 17, 'bi-display', 3),
(18, 'pos', 'Ventas', 19, 'bi-cart-fill', 3),
(19, 'delivery', 'Entregas', 20, 'bi-box2-fill', 3),
(20, 'bistro-till', 'Bistro Caja', 21, 'bi-safe2-fill', 3);

INSERT INTO `accounts`
(id, name, description, balance_type, account_type)
VALUES
(1, 'till', 'Caja', 'asset', 'cash'),
(2, 'bank', 'Banco', 'asset', 'bank'),
(3, 'credit_card', 'Tarjeta de crédito', 'liability', 'virtual'),
(4, 'terminal', 'Terminal', 'asset', 'virtual'),
(5, 'payable', 'Por pagar', 'liability', 'payable'),
(6, 'inventory', 'Inventario', 'asset', 'inventory'),
(7, 'uber', 'Uber Eats', 'asset', 'virtual'),
(8, 'rappi', 'Rappi', 'asset', 'virtual'),
(9, 'didi', 'Didi Food', 'asset', 'virtual');

INSERT INTO `expense_categories`
  (`id`, `name`, `description`)
VALUES
  (1, 'purchases', 'ADQUISICIÓN DE MERCANCÍAS'),
  (2, 'dividends', 'DIVIDENDOS'),
  (3, 'rrhh', 'NÓMINA Y RRHH'),
  (4, 'services', 'SERVICIOS'),
  (5, 'maintenance', 'RENTAS Y MANTENIMIENTO'),
  (6, 'fiscal', 'FISCALES E IMPUESTOS'),
  (7, 'debt_payment', 'PAGO DE DEUDA'),
  (8, 'interest', 'INTERESES Y COMISIONES'),
  (9, 'marketing', 'MARKETING Y PUBLICIDAD'),
  (10, 'supplies', 'SUMINISTROS DE OFICINA'),
  (11, 'generals', 'GENERALES');

INSERT INTO `payment_methods`
  (`id`, `name`, `description`, `icon`, `account_in`, `account_out`)
  VALUES
  (1, 'cash', 'Efectivo', 'bi-cash-stack text-emerald-400', 1, 1),
  (2, 'card', 'Visa / Mastercard', 'bi-credit-card-2-front text-blue-400', 4, 2),
  (3, 'amex', 'American Express', 'bi-credit-card text-sky-400', 4, null),
  (4, 'transfer', 'Transferencia', 'bi-arrow-left-right text-purple-400', 2, 2),
  (5, 'credit_card', 'Tarjeta de crédito', 'bi-credit-card-fill text-amber-400', null, 3),
  (6, 'uber', 'Uber Eats', 'bi-bicycle text-emerald-500', 7, 7),
  (7, 'rappi', 'Rappi', 'bi-basket text-orange-400', 8, 8),
  (8, 'didi', 'Didi Food', 'bi-bag-check text-rose-400', 9, 9);


COMMIT;