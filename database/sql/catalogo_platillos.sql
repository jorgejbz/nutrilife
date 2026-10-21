-- =====================================================================
-- NutriLife · Catálogo de platillos del sistema (user_id NULL)
-- Origen: base secundaria (`nutrilife`), tablas diet_plan_dishes y
-- diet_plan_dish_items. Requiere actualizacion_v3_2.sql (o la migración).
-- Solo INSERTA datos. Se puede ejecutar varias veces: no duplica (busca
-- el platillo por nombre y el ingrediente por platillo + nombre).
-- Equivale a: php artisan db:seed --class=DietPlanDishSeeder
--
-- Corrección aplicada al importar: en la base secundaria los ingredientes
-- "Filete de Salmón Fresco", "Espárragos Verdes al Vapor" y "Camote
-- Horneado" estaban ligados al platillo 6 (Licuado Proteico de Avena…).
-- Aquí se ligan a "Filete de Salmón al Horno con Espárragos y Camote".
--
-- Ingredientes SIN kcal/macros en el origen (se cargan en NULL; NO se
-- inventan valores): Tortilla, Mezcla de Lechugas, Aceitunas, Salmón,
-- Espárragos y Camote. 16 de los 20 platillos no traen ingredientes.
-- Ver docs/UNIFICACION_PLAN_ALIMENTICIO.md, sección 6.
-- =====================================================================

START TRANSACTION;

-- Platillos (20)
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Omelette de Claras con Espinacas y Queso', 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Omelette de Claras con Espinacas y Queso');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Pechuga a la Plancha con Papa al Horno y Ensalada', 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Pechuga a la Plancha con Papa al Horno y Ensalada');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Sincronizadas de Pavo con Queso Panela', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Sincronizadas de Pavo con Queso Panela');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Ensalada Griega con Pollo y Aceitunas', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Ensalada Griega con Pollo y Aceitunas');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Bowl de Avena con Plátano y Crema de Maní', 'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Bowl de Avena con Plátano y Crema de Maní');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Licuado Proteico de Avena, Plátano y Almendras', 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Licuado Proteico de Avena, Plátano y Almendras');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Hot Cakes de Avena y Plátano con Frutos Rojos', 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Hot Cakes de Avena y Plátano con Frutos Rojos');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Tostadas de Aguacate con Huevo Pochado', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Tostadas de Aguacate con Huevo Pochado');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Smoothie Bowl de Frutos Rojos y Semillas de Chía', 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Smoothie Bowl de Frutos Rojos y Semillas de Chía');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Chilaquiles Verdes Ligeros con Pollo y Queso Panela', 'https://images.unsplash.com/photo-1599974579688-8dbdd335c77f?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Chilaquiles Verdes Ligeros con Pollo y Queso Panela');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Filete de Salmón al Horno con Espárragos y Camote', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Filete de Salmón al Horno con Espárragos y Camote');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Fajitas de Res con Pimientos y Cebolla', 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Fajitas de Res con Pimientos y Cebolla');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Ceviche de Pescado Blanco con Tostadas Horneadas', 'https://images.unsplash.com/photo-1535400255456-984241443b29?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Ceviche de Pescado Blanco con Tostadas Horneadas');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Pechuga Empanizada con Amaranto y Ensalada Verde', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Pechuga Empanizada con Amaranto y Ensalada Verde');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Tacos de Pescado a la Mexicana con Tortilla de Maíz', 'https://images.unsplash.com/photo-1512838243191-e81e8f66f1fd?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Tacos de Pescado a la Mexicana con Tortilla de Maíz');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Bowl de Quinoa con Verduras Salteadas y Tofu', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Bowl de Quinoa con Verduras Salteadas y Tofu');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Ensalada de Atún con Verduras y Galletas Integrales', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Ensalada de Atún con Verduras y Galletas Integrales');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Wrap de Pechuga de Pavo con Lechuga y Hummus', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Wrap de Pechuga de Pavo con Lechuga y Hummus');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Tacos de Lechuga con Guisado de Pavo y Aguacate', 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Tacos de Lechuga con Guisado de Pavo y Aguacate');
INSERT INTO diet_plan_dishes (user_id, name, image_url, created_at, updated_at)
SELECT NULL, 'Crema de Calabacita con Pechuga Deshebrada', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=300&auto=format&fit=crop', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM diet_plan_dishes WHERE user_id IS NULL AND name = 'Crema de Calabacita con Pechuga Deshebrada');

-- Ingredientes (13), todos personalizados (food_id NULL) en el origen.
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Pechuga de Pollo a la Plancha', 220.00, 35.00, 0.00, 5.00, 150.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Pechuga a la Plancha con Papa al Horno y Ensalada'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Pechuga de Pollo a la Plancha');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Papa al Horno', 130.00, 3.00, 28.00, 1.00, 120.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Pechuga a la Plancha con Papa al Horno y Ensalada'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Papa al Horno');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Ensalada Mixta (Lechuga y Jitomate)', 35.00, 1.00, 5.00, 1.00, 80.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Pechuga a la Plancha con Papa al Horno y Ensalada'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Ensalada Mixta (Lechuga y Jitomate)');
-- grams = 2.00 en el origen (probablemente "2 piezas"): revisar.
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Tortilla de Maíz o Harina Integral', NULL, NULL, NULL, NULL, 2.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Sincronizadas de Pavo con Queso Panela'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Tortilla de Maíz o Harina Integral');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Pechuga de Pavo San Rafael', 220.00, 35.00, 0.00, 5.00, 60.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Sincronizadas de Pavo con Queso Panela'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Pechuga de Pavo San Rafael');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Queso Panela Light', 110.00, 7.00, 2.00, 8.00, 40.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Sincronizadas de Pavo con Queso Panela'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Queso Panela Light');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Pechuga de Pollo en Tiras', 220.00, 35.00, 0.00, 5.00, 120.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Ensalada Griega con Pollo y Aceitunas'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Pechuga de Pollo en Tiras');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Mezcla de Lechugas y Pepino', NULL, NULL, NULL, NULL, 100.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Ensalada Griega con Pollo y Aceitunas'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Mezcla de Lechugas y Pepino');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Queso Feta o Panela', 110.00, 7.00, 2.00, 8.00, 30.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Ensalada Griega con Pollo y Aceitunas'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Queso Feta o Panela');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Aceitunas Negras', NULL, NULL, NULL, NULL, 20.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Ensalada Griega con Pollo y Aceitunas'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Aceitunas Negras');
-- Corregido: en el origen apuntaban al platillo 6 (Licuado Proteico).
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Filete de Salmón Fresco', NULL, NULL, NULL, NULL, 180.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Filete de Salmón al Horno con Espárragos y Camote'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Filete de Salmón Fresco');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Espárragos Verdes al Vapor', NULL, NULL, NULL, NULL, 80.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Filete de Salmón al Horno con Espárragos y Camote'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Espárragos Verdes al Vapor');
INSERT INTO diet_plan_dish_items (diet_plan_dish_id, food_id, custom_name, custom_kcal, custom_protein_g, custom_carbs_g, custom_lipids_g, grams, created_at, updated_at)
SELECT d.id, NULL, 'Camote Horneado', NULL, NULL, NULL, NULL, 100.00, NOW(), NOW() FROM diet_plan_dishes d
WHERE d.user_id IS NULL AND d.name = 'Filete de Salmón al Horno con Espárragos y Camote'
  AND NOT EXISTS (SELECT 1 FROM diet_plan_dish_items i WHERE i.diet_plan_dish_id = d.id AND i.custom_name = 'Camote Horneado');

COMMIT;
