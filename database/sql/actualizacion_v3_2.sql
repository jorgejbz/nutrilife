-- =====================================================================
-- NutriLife · Actualización 3.2 — Platillos y alimentos personalizados
-- (módulo de plan alimenticio traído de la base secundaria).
-- Equivale a la migración:
--   2026_10_21_000001_add_dishes_and_custom_foods_to_diet_plans
--
-- Puedes usar este archivo o `php artisan migrate`.
-- Único cambio NO aditivo: diet_plan_items.food_id pasa a aceptar NULL
-- (los renglones existentes no cambian).
-- Los CHECK se aplican en MariaDB >= 10.2 / MySQL >= 8.0.16.
--
-- Después, para cargar los 20 platillos del sistema:
--   database/sql/catalogo_platillos.sql
-- =====================================================================

-- Platillos reutilizables. user_id NULL = platillo del sistema (lo ven
-- todos); con valor = platillo propio del nutriólogo.
CREATE TABLE `diet_plan_dishes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = platillo del sistema; con valor = platillo propio del nutriólogo',
  `name` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diet_plan_dishes_user_id_foreign` (`user_id`),
  CONSTRAINT `diet_plan_dishes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ingredientes del platillo: del catálogo `foods` o personalizados.
-- custom_kcal y macros = aporte total del ingrediente para sus `grams`.
CREATE TABLE `diet_plan_dish_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `diet_plan_dish_id` BIGINT UNSIGNED NOT NULL,
  `food_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `custom_name` VARCHAR(255) NULL DEFAULT NULL,
  `custom_kcal` DECIMAL(7,2) NULL DEFAULT NULL,
  `custom_protein_g` DECIMAL(6,2) NULL DEFAULT NULL,
  `custom_carbs_g` DECIMAL(6,2) NULL DEFAULT NULL,
  `custom_lipids_g` DECIMAL(6,2) NULL DEFAULT NULL,
  `grams` DECIMAL(6,2) NOT NULL DEFAULT 100.00,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diet_plan_dish_items_diet_plan_dish_id_foreign` (`diet_plan_dish_id`),
  KEY `diet_plan_dish_items_food_id_foreign` (`food_id`),
  CONSTRAINT `diet_plan_dish_items_diet_plan_dish_id_foreign` FOREIGN KEY (`diet_plan_dish_id`) REFERENCES `diet_plan_dishes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diet_plan_dish_items_food_id_foreign` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`),
  CONSTRAINT `diet_plan_dish_items_food_or_custom_check` CHECK (
    (`food_id` IS NOT NULL AND `custom_name` IS NULL AND `custom_kcal` IS NULL
      AND `custom_protein_g` IS NULL AND `custom_carbs_g` IS NULL AND `custom_lipids_g` IS NULL)
    OR (`food_id` IS NULL AND `custom_name` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Renglones del plan: del catálogo (food_id) o personalizados (custom_*).
-- diet_plan_dish_id = platillo del que salió el renglón; si se borra el
-- platillo queda NULL y el plan conserva su copia.
ALTER TABLE `diet_plan_items`
  MODIFY `food_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN `diet_plan_dish_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Platillo del que salió el renglón (los valores se copian)' AFTER `diet_plan_meal_id`,
  ADD COLUMN `custom_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Alimento fuera del catálogo (food_id NULL)' AFTER `food_id`,
  ADD COLUMN `custom_kcal` DECIMAL(7,2) NULL DEFAULT NULL COMMENT 'Aporte total del renglón' AFTER `custom_name`,
  ADD COLUMN `custom_protein_g` DECIMAL(6,2) NULL DEFAULT NULL AFTER `custom_kcal`,
  ADD COLUMN `custom_carbs_g` DECIMAL(6,2) NULL DEFAULT NULL AFTER `custom_protein_g`,
  ADD COLUMN `custom_lipids_g` DECIMAL(6,2) NULL DEFAULT NULL AFTER `custom_carbs_g`,
  ADD COLUMN `custom_portion_description` VARCHAR(255) NULL DEFAULT NULL AFTER `custom_lipids_g`,
  ADD COLUMN `custom_image_url` VARCHAR(500) NULL DEFAULT NULL AFTER `custom_portion_description`,
  ADD KEY `diet_plan_items_diet_plan_dish_id_foreign` (`diet_plan_dish_id`),
  ADD CONSTRAINT `diet_plan_items_diet_plan_dish_id_foreign` FOREIGN KEY (`diet_plan_dish_id`) REFERENCES `diet_plan_dishes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `diet_plan_items_food_or_custom_check` CHECK (
    (`food_id` IS NOT NULL AND `custom_name` IS NULL AND `custom_kcal` IS NULL
      AND `custom_protein_g` IS NULL AND `custom_carbs_g` IS NULL AND `custom_lipids_g` IS NULL)
    OR (`food_id` IS NULL AND `custom_name` IS NOT NULL)
  );

-- Si después corres `php artisan migrate`, no pasa nada: la migración
-- revisa si cada tabla, columna y CHECK ya existe y solo queda registrada.
