-- Script de llenado de datos de negocio Panga Urbana

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
-- DATOS DE NEGOCIO
-- --------------------------------------------------------

INSERT INTO `areas` 
  (id, name, description, status_id) 
VALUES 
  (1, 'Terraza', 'Zona de Terraza', 1),
  (2, 'Interior', 'Zona Interior', 1);

INSERT INTO `channels` 
  (id, name, description, icon, status_id)
VALUES 
  (1, 'local', 'Restaurante', 'bi-building text-cyan-400', 1),
  (2, 'rappi', 'Rappi', 'bi-basket text-orange-400', 1),
  (3, 'uber', 'Uber Eats', 'bi-bicycle text-emerald-500', 1),
  (4, 'didi', 'Didi Food', 'bi-bag-check text-rose-400', 2),
  (5, 'phone', 'Teléfono', 'bi-phone text-blue-400', 1),
  (6, 'wa', 'WhatsApp', 'bi-whatsapp text-green-400', 1),
  (7, 'web', 'En línea', 'bi-globe text-purple-400', 2),
  (8, 'app', 'App', 'bi-app text-indigo-400', 2),
  (9, 'kiosk', 'Kiosko', 'bi-display text-pink-400', 2);

INSERT INTO `stations` 
  (id, name, description, emoji, type, sort, status_id) 
VALUES 
  (1, 'Caja', 'Caja', '💵', null, 1, 1),
  (2, 'Fria', 'Barra Fria', '🥗', 'kds', 2, 1),
  (3, 'Caliente', 'Cocina Caliente',  '🍳', 'kds', 3, 1),
  (4, 'Cuartito', 'Plancha', '🔥', 'kds', 4, 1),
  (5, 'Barra', 'Bebidas', '🥤', 'kds', 5, 1);


INSERT INTO `tables` 
  (name, area_id, capacity, pos_x, pos_y, size, orientation, use_status_id, status_id) 
VALUES 
  ('Mesa 1', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 2', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 3', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 4', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 5', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 6', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 7', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 8', 1, 3, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 9', 1, 4, 0, 0, 'S', 'H', 11, 1), 
  ('Mesa 10', 1, 4, 0, 0, 'S', 'H', 11, 1),
  ('Mesa 11', 2, 3, 0, 0, 'S', 'H', 11, 1),
  ('Mesa 12', 2, 3, 0, 0, 'S', 'H', 11, 1),
  ('Mesa 13', 2, 4, 0, 0, 'S', 'H', 11, 1),
  ('Mesa 14', 2, 4, 0, 0, 'S', 'H', 11, 1);
  

INSERT INTO `menu_categories`
  (id, name, description, emoji, sort, status_id)
VALUES
  (1, 'Favoritos', 'Más pedidos del menú.', '🔥', 1, 1),
  (2, 'Pa Botanear', 'Platillos de salida rápida.', '🍢', 2, 1),
  (3, 'Cocktails', 'A nuestro estilo, ni muy jarocho, ni muy noreste. Pepino, cebolla morada, cilantro, jitomate y aguacate.', '🦐', 3, 1),
  (4, 'Aguachiles', 'Salseado artesanal hecho en mocajete, como debe ser. ¡Di no al aguachile de licuadora!', '🌶️', 4, 1),
  (5, 'Ceviches', 'Ceviches y Curtidos.', '🐟', 5, 1),
  (6, 'Tostadas', 'Bañadas en su salsa especial.', '🧆', 6, 1),
  (7, 'Maíz y Harina', 'El puro grap mexicano', '🌮', 7, 1),
  (8, 'Caldos y Pastas', 'Caldos y Pastas.', '🍝', 8, 1),
  (9, 'Platos Fuertes', 'Para los que tienen hambre.', '🐳', 9, 1),
  (10, 'Pa Plebitos', 'Nuestro menú infantil.', '👶', 10, 1),
  (11, 'Postres', 'Postres', '🍮', 10, 1),
  (12, 'Bebidas', 'Bebidas', '🥤', 11, 1),
  (13, 'Cervezas', 'Cervezas', '🍻', 12, 1),
  (14, 'Tarros', 'Tarros', '🍺', 13, 1),
  (15, 'Alcoholes', 'Vinos, carajillo', '🍷', 14, 1),
  (16, 'Extras', 'Extras', '🍟', 15, 1);

INSERT INTO `menu`
  (id, name, description, category_id, station_id, price, prep_time)
VALUES
(1,'Caldito de camarón','Con aguacate, cebolla y cilantro. Porción: 250ml.',2,3,47,3),
(2,'Pescadilla sierra','Guisado de sierra acompañada de aguacate, cebolla morada, cilantro y su rajita de mayonesa de chiltepin. Porción: 50g.',2,4,45,3),
(3,'Pescadilla jaiba','Guisado de jaiba acompañada de aguacate, cebolla morada, cilantro y su rajita de mayonesa de chiltepin. Porción: 50g.',2,4,50,3),
(4,'Frijoles puercos','Receta Sinaloense. Batido de frijoles en manteca, chiles, adobo, queso, chorizo y tocino. Porción: 300g.',2,3,162,7),
(5,'Aros y camarones crunchy','Capeado crujiente con coco. Acompañados con verdurita y aderezo de chiltepin y chipotle. Porción: 110g.',2,3,194,12),
(6,'Guacamole Panga','Aguacate, cebolla morada y camarón cocido; sazonados con la receta de la casa. Porción: 200g.',2,2,198,8),
(7,'Taquitos dorados de jaiba','Guisado de pulpa de jaiba, sobre cama de guacamole, con pico de gallo, queso y crema. Porción: 200g.',2,4,173,10),
(8,'Cocktail De camarón (Chico)','Pepino, cebolla morada, cilantro, jitomate y aguacate. Porción: 380ml.',3,2,166,8),
(9,'Cocktail De camarón (Grande)','Pepino, cebolla morada, cilantro, jitomate y aguacate. Porción: 500ml.',3,2,239,8),
(10,'Cocktail Campechano (Chico)','Camarón y pulpo, pepino, cebolla morada, cilantro, jitomate y aguacate. Porción: 380ml.',3,2,177,8),
(11,'Cocktail Campechano (Grande)','Camarón y pulpo, pepino, cebolla morada, cilantro, jitomate y aguacate. Porción: 500ml.',3,2,263,8),
(12,'Aguachile Camarón','Del merito Topolobampo, Sinaloa. Porción: 180g.',4,2,257.00,10),
(13,'Aguachile Atún','Centro de lomo, pura mantequilla de Mazatlán. Porción: 150g.',4,2,267,10),
(14,'Aguachile Mixto','Camarón, atún y pulpo de la península. Porción: 200g.',4,2,312,10),
(15,'Aguachile Arrachera Norteña','Suaves tiras de arrachera. Porción: 200g.',4,2,246.00,10),
(16,'Ceviche Mochis','Camarón crudo y cocido, pulpo, callo de almeja, pico de gallo y pepino; bañado en jugos suaves. Porción: 300g.',5,2,329,10),
(17,'Ceviche Colimote','Molida de sierra curtida en jugo de limón, con jitomate, cebolla blanca, zanahoria, cilantro y sal de Colima. Porción: 300g.',5,2,156,8),
(18,'Ceviche en salsas negras','Equilibrado con pepino y cebolla morada, todo salseado con una suave mezcla de nuestros jugos negros. Porción: 300g.',5,2,259,8),
(19,'Esto no es un ceviche','Equilibrado con pepino y cebolla morada, todo salseado con una suave mezcla de nuestros jugos negros. Porción: 300g.',5,2,259,8),
(20,'Ceviche del Canelo','A la Caribeña, con cubitos de mango, pepino, cebolla, cilantro y aguacate; bañados con un aderezo de chipotle. Porción: 300g.',5,2,259,8),
(21,'Torre de Mariscos','Camarón crudo y cocido, atún, pulpo, callo de almeja bañado en nuestra salsa negra intensa. Porción: 500g.',5,2,509,14),
(22,'Yaquesita','Camarón crudo y cocido, pulpo, callo de almeja y atún en un salseado negro intenso de chiltepín. Porción: 400g.',5,2,346,10),
(23,'Piedra de Mar','Molcajete con camarón crudo y cocido, atún, pulpo y callo de almeja bañado en salsa negra suave. Porción: 500g.',5,2,396,14),
(24,'Tostada De ceviche colimote','En cama de mayonesa de chiltepín tatemado y coronado con aguacate. Porción: 180g.',6,2,69,6),
(25,'Tostada De la Patrona','Camarón crudo y cocido, pulpo, atún y callo de almeja, pepino, cebolla morada y mayonesa de chiltepín tatemado. Bañada en una salsa oscura con chiltepín y habanero. Porción: 250g.',6,2,259,8),
(26,'Tostada Bañada en salsa negra (Pulpo)','Pepino, cebolla morada y mayonesa de chiltepín. Porción: 150g.',6,2,220,6),
(27,'Tostada Bañada en salsa negra (Camarón)','Pepino, cebolla morada y mayonesa de chiltepín. Porción: 150g.',6,2,167,6),
(28,'Tostada Bañada en salsa negra (Atún)','Pepino, cebolla morada y mayonesa de chiltepín. Porción: 150g.',6,2,167,6),
(29,'Taco de torito (1pza)','Jalapeños capeados rellenos de queso con frijoles puercos, acompañados de col morada (camarón, marlín ahumado o pescado). Porción: 100g.',7,4,109,8),
(30,'Taco gobernador (1pza)','Con queso, chile poblano, cebolla y jitomate. Sazonado con ajo y pimienta sobre tortilla de harina (camarón o marlín ahumado). Porción: 110g',7,4,116,8),
(31,'Volcán (1 pza)','Con guacamole, cebolla caramelizada, mayonesa de chiltepín y costra de queso sobre una tostada de maíz (atún, camarón, marlín ahumado, arrachera). Porción: 100g',7,4,117,8),
(32,'Taquitos estilo baja (orden de 3)','Capeados, en tortilla de maíz, mayonesa de chiltepín, cebolla morada curtida, col y aguacate (camarón o pescado). Porción: 240g',7,3,189.00,10),
(33,'Machete culichi','Tortilla de maíz bañada en adobo, con frijoles puercos, queso, cebolla morada y aguacate (camarón, marlín ahumado o atún). Porción: 150g',7,4,161,10),
(34,'Burrito de mar (o tierra)','En tortilla de harina, con frijoles puercos, mayonesa de chiltepin y ensalada. Acompañado de papitas (atún, camarón, marlín ahumado o arrachera). Porción: 300g',7,4,222,10),
(35,'Taco Costra "Oli"','Tortilla de harina, costra de queso, frijoles puercos, camarón, cebolla curtida, mayonesa de chiltepín y cilantro. Porción: 100g',7,4,136,8),
(36,'Adobada de pulpo','Tortilla adobada con pulpo, guacamole, cebolla morada y ajillo. Porción: 100g.',7,4,130,8),
(37,'Enchiladas suizas de marlín','Verdes, bien cremosas. Coronadas con aguacate, cebolla morada y cilantro. Porción: 300g',7,3,195,12),
(38,'Caldo 7 mares','Tradicional caldo de las costas del pacífico con pescado, pulpo, calamar, jaiba, callo y almejas; va gratinado. Porción: 500ml',8,3,315,12),
(39,'Pasta con camarones','Al dente, bañada en cremosa salsa poblana con camarones y tocino a la mantequilla. Porción: 300g',8,3,185,12),
(40,'Cazuela de marlín','Para taquear. Delicioso estofado de marlín ahumado con verduritas, chile chipotle, poblano y el toque de la casa. Porción: 300g',8,3,201,12),
(41,'Molcajete Mar y Tierra "El Arco"','Tiras de arrachera y atún, longaniza, camarones, pulpo, pescado, pimientos, cebolla, aguacate y queso. Porción: 700g',9,3,543,15),
(42,'Empapelados De Pescado','Envuelto en aluminio, gratinado con cebolla, poblano, jitomate sazonado. Porción: 350g',9,3,254,12),
(43,'Empapelados De mariscos','Pescado, camarón y pulpo. Envuelto en aluminio, gratinado con cebolla, poblano, jitomate sazonado. Porción: 350g',9,3,375,12),
(44,'Camarones estilo zarandeados','A la plancha, con cáscara abiertos en mariposa, marinados con adobo de zarandeado, servidos con ensalada. Porción: 200g',9,3,238,12),
(45,'Camarones Panga','Camarón con cáscara y cabeza acompañados de ensalada y puré o papas a la francesa (Mantequilla-Parmesano, Mango-Habanero ó Enchipotlados). Porción: 300g',9,3,239.00,12),
(46,'Hamburguesa','Centro de lomo de atún o sirloin de res, jitomate, cebolla morada, lechuga y mayonesa de chiltepin; con papitas fritas. Porción: 250g',9,4,276,12),
(47,'Mojarra frita','Sazonada con el toque de la Panga acompañada de tortillas, puré o papas y ensalada. Porción: 300g',9,3,295.00,15),
(48,'Atún Sellado','Medallón de atún sobre una cama de puré de papa y bañado en una salsa de mantequilla y limón. Porción: 200g',9,4,197.00,12),
(49,'Filetitos de pescado','Capeados con guarnición de verdura o puré de papa. Porción: 160g',9,3,185.00,12),
(50,'Arrachera de res','Acompañada de arroz y ensalada. Porción: 200g',9,4,195.00,12),
(51,'Pescaditos y/o camarones capeados','100g de pescado, camarón o combinados, capeados; acompañados de papitas a la francesa. Porción: 160g',10,3,154.00,10),
(52,'Pasta a la crema con camarones','100g camarón cocido con pasta a la crema y mantequilla. Porción: 180g',10,3,185,12),
(53,'Cheesecake de guayaba','Cheesecake de guayaba.',11,5,75,3),
(54,'Flan de la casa','Con rompope o cajeta.',11,5,55,3),
(55,'Soda de frutos (600 ml)','Con esencia de frutos con un toque de limón y rodajas de pepino y fruta (Manzana verde, frutos rojos ó maracuyá).',12,5,70.00,4),
(56,'Agua Fresca Jamaica (450 ml)','Agua fresca de jamaica.',12,5,37.00,3),
(57,'Agua Fresca Cebada (450 ml)','Agua fresca de cebada.',12,5,40.00,3),
(58,'Naranjada (450 ml)','Naranjada natural/mineral.',12,5,48.00,4),
(59,'Refrescos (355 ml)','Coca, Coca Zero, Sprite, Sidral, Fanta, Agua Mineral.',12,5,37.00,2),
(60,'Café americano (355 ml)','Café americano caliente.',12,5,45.00,4),
(61,'Cerveza regular (355 ml)','Victoria, Corona, Tecate, Carta Blanca, XX Lager y XX Ambar.',13,5,55.00,2),
(62,'Cerveza premium (355 ml)','Pacífico, Michelob Ultra, Modelo Especial, Negra Modelo, Stella Artois, Heineken, Bohemia Oscura y Regular.',13,5,65.00,2),
(63,'Cahuama, ballena, ballenón','Presentación caguama/ballena.',13,5,164.00,2),
(64,'Cerveza artesanal (355 ml)','Chelita Forzan tipo IPA, Blonde Ale, Ambar Ale, con toque de mezcal, Porter.',13,5,80.00,2),
(65,'Tarro chelado o michelado','Jugo de limón, más pizca y escarcha de sal o tamarindo escarchado con sabor miguelito-tajín, mora azul, fresa.',14,5,26.00,4),
(66,'Tomatazo','Clamato y limón. Escarcha de tamarindo y chilito en polvo.',14,5,37.00,4),
(67,'Tarro con petróleo','Tomatazo más nuestra salsa negra secreta y limón.',14,5,53.00,4),
(68,'Tarro cubano','Nuestra salsa negra secreta y limón.',14,5,39.00,4),
(69,'Botella de vino blanco/tinto (750ml)','Selección de la casa.',15,5,420.00,3),
(70,'Copa de vino blanco/tinto (200ml)','Selección de la casa.',15,5,123.00,3),
(71,'Carajillo (300ml)','Preparado digestivo con café.',15,5,95.00,7),
(72, 'Limonada (450 ml)', 'Naranjada', 12, 5, 48.00, 5),
(73, 'Papas Fritas', 'Papas fritas', 16, 5, 75.00, 3),
(74, 'Orden de arroz', 'Orden de arroz', 16, 5, 25.00, 3),
(75, 'Mayonesa', 'Orden de mayonesa', 16, 2, 0, 2),
(76, 'Chiltepin', 'Orden de chiltepin', 16, 2, 0, 2),
(77, 'Orden de catsup', 'Orden de catsup', 16, 2, 0, 2),
(78, 'Orden de salsa', 'Orden de salsa', 16, 2, 0, 2),
(79, 'Aguacate', 'Orden de aguacate', 16, 2, 30, 3),
(80, 'Cerveza sin alcohol', 'Cerveza sin alcohol', 13, 5, 65.00, 2);

INSERT INTO `modifiers`
  (id, name, description, price)
VALUES
  (1, 'Sin Cebolla', 'Cebolla morada.', 0.00),
  (2, 'Sin Cilantro', 'Cilantro fresco.', 0.00),
  (3, 'Sin Jitomate', 'Jitomate fresco.', 0.00),
  (4, 'Sin Pepino', 'Pepino fresco.', 0.00),
  (5, 'Sin Aguacate', 'Aguacate fresco.', 0.00),
  (6, 'Sin Mayonesa', 'Mayonesa de la casa.', 0.00),
  (7, 'Sin Chile', 'No picante.', 0.00),
  (8, 'Sin Camarón', 'Sin camarón.', 0.00),
  (9, 'Sin Pulpo', 'Sin pulpo.', 0.00),
  (10, 'Sin Callo', 'Sin callo de almeja.', 0.00),
  (11, 'Sin Jaiba', 'Sin jaiba.', 0.00),
  (12, 'Sin Pescado', 'Sin pescado.', 0.00),
  (13, 'Negro', 'Salseado negro.', 0.00),
  (14, 'Verde', 'Salseado verde.', 0.00),
  (15, 'Rojo', 'Salseado rojo.', 0.00),
  (16, 'Tatemado', 'Salseado rojo tatemado.', 0.00),
  (17, 'Natural', 'Agua natural', 0.00),
  (18, 'Mineral', 'Agua mineral', 0.00),
  (19, 'Pulpo', 'Pulpo cocido', 40.00),
  (20, 'Camarón crudo', 'Camarón crudo', 0.00),
  (21, 'Camarón cocido', 'Camarón cocido', 0.00),
  (22, 'Atún crudo', 'Atún crudo', 0.00),
  (23, 'Atún cocido', 'Atún cocido', 0.00),
  (24, 'Marlin', 'Estofado de marlin', 0.00),
  (25, 'Pescado', 'Filete de pescado', 0.00),
  (26, 'Victoria', 'Cerveza Victoria', 0.00),
  (27, 'Corona', 'Cerveza Corona', 0.00),
  (28, 'XX Lager', 'Cerveza XX Lager', 0.00),
  (29, 'XX Ambar', 'Cerveza XX Ambar', 0.00),
  (30, 'Pacifico', 'Cerveza Pacifico', 0.00),
  (31, 'Ultra', 'Cerveza Ultra', 0.00),
  (32, 'Modelo Especial', 'Cerveza Modelo Especial', 0.00),
  (33, 'Negra Modelo', 'Cerveza Negra Modelo', 0.00),
  (34, 'Bohemia', 'Cerveza Bohemia', 0.00),
  (35, 'Heineken', 'Cerveza Heineken', 0.00),
  (36,  'Stella Artois', 'Cerveza Stella Artois', 0.00),
  (37, 'Corona Cero', 'Cerveza Corona Cero', 0.00),
  (38, 'Porter', 'Forzan Porter', 0.00),
  (39, 'Amber', 'Forzan Porter', 0.00),
  (40, 'Pale', 'Forzan Porter', 0.00),
  (41, 'Blonde', 'Forzan Porter', 0.00),
  (42, 'Coco', 'Forzan Porter', 0.00),
  (43, 'Manco', 'Forzan Porter', 0.00),
  (44, 'Mezcal', 'Forzan Porter', 0.00),
  (45, 'Coca', 'Coca-Cola', 0.00),
  (46, 'Coca Zero', 'Coca-Cola Zero', 0.00),
  (47, 'Sprite', 'Sprite', 0.00),
  (48, 'Sidral', 'Sidral', 0.00),
  (49, 'Fanta', 'Fanta', 0.00),
  (50, 'Agua Mineral', 'Agua Mineral', 0.00),
  (51, 'Hamburguesa de Atún', 'Hamburguesa de Atún', 0.00),
  (52, 'Hamburguesa de Res', 'Hamburguesa de Res', 0.00),
  (53, 'Parmesano', 'Mantequilla y parmesano', 0.00),
  (54, 'Mango-Habanero', 'Mango y habanero', 0.00),
  (55, 'Enchipotlados', 'Aderezo chipotle', 0.00),
  (56, 'Mojo de Ajo', 'Aderezo de ajo', 0.00),
  (57, 'B. Corona', 'Ballena corona', 0.00),
  (58, 'B. Victoria', 'Ballena victoria', 0.00),
  (59, 'B. XX Lager', 'Ballena xx lager', 0.00),
  (60, 'B. XX Ambar', 'Ballena xx ambar', 0.00),
  (61, 'B. Pacifico', 'Ballena pacifico', 0.00),
  (62, 'B. Modelo', 'Ballena modelo especial', 0.00),
  (63, 'B. Negra', 'Ballena negra modelo', 0.00),
  (64, 'B. Heineken', 'Ballena heineken', 0.00),
  (65, 'B. Stella Artois', 'Ballena stella artois', 0.00),
  (66, 'Solo camarón', 'Solo camarón', 0.00),
  (67, 'Solo calamar', 'Solo calamar', 0.00);

INSERT INTO `modifier_groups`
  (id, name, required, max_count)
VALUES
  (1, 'Sin Verdura', 0, null),
  (2, 'Sin Ingrediente', 0, null),
  (3, 'Sin Marisco', 0, null),
  (4, 'Extras', 0, null),
  (5, 'Salseado', 1, 1),
  (6, 'Tipo de Agua', 1, 1),
  (7, 'Marisco fresco', 1, null),
  (8, 'Marisco preparado', 1, 1),
  (9, 'Regular', 1, 1),
  (10, 'Premium', 1, 1),
  (11, 'Cero alcohol', 1, 1),
  (12, 'Artesanal', 1, 1),
  (13, 'Refrescos', 1, 1),
  (14, 'Hamburguesa', 1, 1),
  (15, 'Salsa/Aderezo', 1, 1),
  (16, 'Ballena', 1, 1),
  (17, 'Crunchy', 0, 1);

INSERT INTO `modifier_grouped`
  (group_id, modifier_id)
VALUES
  (1, 1),(1, 2),(1, 3),(1, 4),(1, 5),
  (2, 6),(2, 7),
  (3, 8),(3, 9),(3, 10),(3, 11),(3, 12),
  (4, 14),
  (5, 13),(5, 14),(5, 15),(5, 16),
  (6, 17),(6, 18),
  (7, 19),(7, 20),(7, 21),(7, 22),
  (8, 19),(8, 21),(8, 23),(8, 25),
  (9, 26),(9, 27),(9, 28),(9, 29),
  (10, 30),(10, 31),(10, 32),(10, 33),(10,35),(10,36),
  (11, 37),
  (12, 38),(11, 39),(11, 40),(11, 41),(11, 42),(11, 43),(11, 44),
  (13, 45),(13, 46),(13, 47),(13, 48),(13, 49),(13, 50),
  (14, 51),(14, 52),
  (15, 53),(15, 54),(15, 55),(15, 56),
  (16, 57),(16, 58),(16, 59),(16, 60),(16, 61),(16, 62),(16, 63),(16, 64),(16, 65),
  (17, 66),(17, 67);

INSERT INTO `menu_modifier_groups`
  (product_id, group_id)
VALUES
  (8, 1),(9, 1),(10, 1),(11, 1),
  (12, 5),(13, 5),(14, 5),(15, 5),
  (18, 7),(19, 7),(20, 7),
  (29, 8),(30, 8),(31, 8),(33, 8),(34, 8),
  (46, 14),
  (45, 15),
  (61, 9),
  (62, 10),
  (63, 16),
  (64, 12),
  (80, 11),
  (5, 17);

-- Llenado de perfil fiscal
INSERT INTO `tax_profiles`
(`id`, `rfc`, `company_name`)
VALUES
(1, 'AAAA010101AAA', 'Público en General'),
(2, 'PIMM031125XXX', 'Moises Apolo Del Pilar Miranda'),
(3, 'PIMD880701XXX', 'Diego Aaron Del Pilar Miranda');

-- Llenado de usuarios
INSERT INTO `users`
(`id`, `role_id`, `first_name`, `last_name`, `phone`, `email`, `password`, `google_id`, `auth_pin`)
VALUES
(0, 0, 'Augusto', 'Del Pilar', '+52(55)81354641', 'ixea@tenant.mx', '$2y$10$ny2C4REThqPI5a6onGeBEuZxg78VWw6rBWr8ibxzX/BDGaVnsAXIe', '101139605893263092788', '0000'),
(1, 1, 'Tenant', 'Test', null, 'admin@tenant.mx', '$2y$10$ny2C4REThqPI5a6onGeBEuZxg78VWw6rBWr8ibxzX/BDGaVnsAXIe', null, '1111'),
(2, 2, 'Tenant', 'Test', null, 'cajero@tenant.mx', '$2y$10$ny2C4REThqPI5a6onGeBEuZxg78VWw6rBWr8ibxzX/BDGaVnsAXIe', null, '2222'),
(3, 3, 'Tenant', 'Test', null, 'mesero@tenant.mx', '$2y$10$ny2C4REThqPI5a6onGeBEuZxg78VWw6rBWr8ibxzX/BDGaVnsAXIe', null, '3333');
  
COMMIT;