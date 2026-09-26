-- =====================================================================
-- PLATAFORMA NUTRICIONAL — Script SQL completo (v2)
-- Solo tablas de negocio. NO incluye tablas internas de Laravel
-- (sessions, cache, jobs, migrations, failed_jobs, job_batches,
-- password_reset_tokens) — esas ya vienen con el framework.
-- Motor: MySQL 8 / MariaDB 10.4+  |  Charset: utf8mb4
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `proyecto` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `proyecto`;

-- ---------------------------------------------------------------------
-- 1. ROLES Y USUARIOS (nutriólogos / admins)
-- ---------------------------------------------------------------------

CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'nutriologo', 'Profesional que gestiona pacientes, citas, evaluaciones y dietas'),
(2, 'admin', 'Administrador de la plataforma: ve suscripciones, ingresos y métricas globales');

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `access_code` CHAR(6) NOT NULL COMMENT 'Código de 6 dígitos para que sus pacientes entren a la app móvil',
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_access_code_unique` (`access_code`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. PACIENTES
-- ---------------------------------------------------------------------

CREATE TABLE `patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Nutriólogo dueño del expediente',
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `birth_date` DATE DEFAULT NULL,
  `sex` VARCHAR(20) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL COMMENT 'Junto con el access_code del nutriólogo, es su credencial de entrada a la app móvil',
  `occupation` VARCHAR(255) DEFAULT NULL,
  `goal` TEXT DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_user_id_email_unique` (`user_id`,`email`),
  CONSTRAINT `patients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. AGENDA, CONSULTAS, ANTROPOMETRÍA, COBROS AL PACIENTE
-- ---------------------------------------------------------------------

CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `appointment_at` DATETIME NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'Pendiente',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_user_id_foreign` (`user_id`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `consultations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `consultation_at` DATETIME NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `observations` TEXT DEFAULT NULL,
  `recommendations` TEXT DEFAULT NULL,
  `next_visit` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultations_user_id_foreign` (`user_id`),
  KEY `consultations_patient_id_foreign` (`patient_id`),
  KEY `consultations_appointment_id_foreign` (`appointment_id`),
  CONSTRAINT `consultations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `consultations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `consultations_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `evaluations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `evaluation_date` DATE NOT NULL,
  `weight` DECIMAL(6,2) DEFAULT NULL,
  `height` DECIMAL(5,2) DEFAULT NULL,
  `bmi` DECIMAL(5,2) DEFAULT NULL,
  `body_fat` DECIMAL(5,2) DEFAULT NULL,
  `muscle_mass` DECIMAL(6,2) DEFAULT NULL,
  `waist` DECIMAL(6,2) DEFAULT NULL,
  `hip` DECIMAL(6,2) DEFAULT NULL,
  `chest` DECIMAL(6,2) DEFAULT NULL,
  `arm` DECIMAL(6,2) DEFAULT NULL,
  `thigh` DECIMAL(6,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluations_patient_id_foreign` (`patient_id`),
  CONSTRAINT `evaluations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `concept` VARCHAR(255) DEFAULT NULL,
  `payment_method` VARCHAR(255) NOT NULL DEFAULT 'Efectivo',
  `status` VARCHAR(255) NOT NULL DEFAULT 'Pagado',
  `reference` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_patient_id_foreign` (`patient_id`),
  KEY `payments_appointment_id_foreign` (`appointment_id`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `payments_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. CATÁLOGOS CLÍNICOS Y SU RELACIÓN CON EL PACIENTE
-- ---------------------------------------------------------------------

CREATE TABLE `pathologies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `allergens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `patient_pathologies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `pathology_id` BIGINT UNSIGNED NOT NULL,
  `diagnosed_at` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_pathology_unique` (`patient_id`,`pathology_id`),
  KEY `patient_pathologies_pathology_id_foreign` (`pathology_id`),
  CONSTRAINT `patient_pathologies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_pathologies_pathology_id_foreign` FOREIGN KEY (`pathology_id`) REFERENCES `pathologies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `patient_allergens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `allergen_id` BIGINT UNSIGNED NOT NULL,
  `severity` ENUM('leve','moderada','severa') DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_allergen_unique` (`patient_id`,`allergen_id`),
  KEY `patient_allergens_allergen_id_foreign` (`allergen_id`),
  CONSTRAINT `patient_allergens_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_allergens_allergen_id_foreign` FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. CATÁLOGO DE ALIMENTOS Y EQUIVALENTES (SMAE / INCAP)
-- ---------------------------------------------------------------------

CREATE TABLE `food_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `food_groups_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `foods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `food_group_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `portion_grams` DECIMAL(6,2) NOT NULL,
  `portion_description` VARCHAR(255) DEFAULT NULL,
  `kcal` DECIMAL(7,2) NOT NULL,
  `protein_g` DECIMAL(6,2) NOT NULL,
  `lipids_g` DECIMAL(6,2) NOT NULL,
  `carbs_g` DECIMAL(6,2) NOT NULL,
  `fiber_g` DECIMAL(6,2) DEFAULT NULL,
  `sodium_mg` DECIMAL(7,2) DEFAULT NULL,
  `glycemic_index` SMALLINT UNSIGNED DEFAULT NULL,
  `source` VARCHAR(100) NOT NULL DEFAULT 'INCAP-SMAE',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `foods_food_group_id_foreign` (`food_group_id`),
  CONSTRAINT `foods_food_group_id_foreign` FOREIGN KEY (`food_group_id`) REFERENCES `food_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `food_allergens` (
  `food_id` BIGINT UNSIGNED NOT NULL,
  `allergen_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`food_id`,`allergen_id`),
  KEY `food_allergens_allergen_id_foreign` (`allergen_id`),
  CONSTRAINT `food_allergens_food_id_foreign` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `food_allergens_allergen_id_foreign` FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `food_pathology_restrictions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pathology_id` BIGINT UNSIGNED NOT NULL,
  `food_id` BIGINT UNSIGNED DEFAULT NULL,
  `food_group_id` BIGINT UNSIGNED DEFAULT NULL,
  `restriction_type` ENUM('prohibido','limitado') NOT NULL,
  `max_portions_per_day` DECIMAL(4,1) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fpr_pathology_id_foreign` (`pathology_id`),
  KEY `fpr_food_id_foreign` (`food_id`),
  KEY `fpr_food_group_id_foreign` (`food_group_id`),
  CONSTRAINT `fpr_pathology_id_foreign` FOREIGN KEY (`pathology_id`) REFERENCES `pathologies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fpr_food_id_foreign` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fpr_food_group_id_foreign` FOREIGN KEY (`food_group_id`) REFERENCES `food_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fpr_food_or_group_check` CHECK (
    (`food_id` IS NOT NULL AND `food_group_id` IS NULL) OR
    (`food_id` IS NULL AND `food_group_id` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. PLANES DE DIETA
-- ---------------------------------------------------------------------

CREATE TABLE `diet_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `target_kcal` DECIMAL(7,2) NOT NULL,
  `target_protein_g` DECIMAL(6,2) NOT NULL,
  `target_lipids_g` DECIMAL(6,2) NOT NULL,
  `target_carbs_g` DECIMAL(6,2) NOT NULL,
  `starts_at` DATE NOT NULL,
  `ends_at` DATE DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diet_plans_patient_id_foreign` (`patient_id`),
  KEY `diet_plans_user_id_foreign` (`user_id`),
  CONSTRAINT `diet_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `diet_plans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `diet_plan_meals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `diet_plan_id` BIGINT UNSIGNED NOT NULL,
  `meal_name` VARCHAR(100) NOT NULL,
  `meal_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diet_plan_meals_diet_plan_id_foreign` (`diet_plan_id`),
  CONSTRAINT `diet_plan_meals_diet_plan_id_foreign` FOREIGN KEY (`diet_plan_id`) REFERENCES `diet_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `diet_plan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `diet_plan_meal_id` BIGINT UNSIGNED NOT NULL,
  `food_id` BIGINT UNSIGNED NOT NULL,
  `equivalents` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `grams` DECIMAL(6,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diet_plan_items_meal_id_foreign` (`diet_plan_meal_id`),
  KEY `diet_plan_items_food_id_foreign` (`food_id`),
  CONSTRAINT `diet_plan_items_meal_id_foreign` FOREIGN KEY (`diet_plan_meal_id`) REFERENCES `diet_plan_meals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diet_plan_items_food_id_foreign` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7. SUSCRIPCIONES DE LA PLATAFORMA
-- ---------------------------------------------------------------------

CREATE TABLE `subscription_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(8,2) NOT NULL,
  `billing_period` ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  `max_patients` INT UNSIGNED DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subscription_plan_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('en_prueba','activa','vencida','cancelada') NOT NULL DEFAULT 'en_prueba',
  `starts_at` DATE NOT NULL,
  `current_period_ends_at` DATE NOT NULL,
  `cancelled_at` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_user_id_foreign` (`user_id`),
  KEY `subscriptions_plan_id_foreign` (`subscription_plan_id`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subscription_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(8,2) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `payment_method` VARCHAR(100) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pagado',
  `reference` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_payments_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `subscription_payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Orden de siembra (seeders) sugerido:
-- roles -> users -> subscription_plans -> subscriptions -> patients ->
-- pathologies -> allergens -> patient_pathologies -> patient_allergens ->
-- food_groups -> foods -> food_allergens -> food_pathology_restrictions ->
-- appointments -> consultations -> evaluations -> payments ->
-- diet_plans -> diet_plan_meals -> diet_plan_items -> subscription_payments
-- =====================================================================
