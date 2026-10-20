-- =====================================================================
-- NutriLife · Actualización 3.1 (SOLO agrega columnas; no borra ni
-- cambia nada existente). Equivale a las migraciones:
--   2026_10_20_000001_add_professional_and_activation_fields_to_users_table
--   2026_10_20_000002_add_goal_instructions_and_meal_times_to_diet_plans
--
-- Puedes usar este archivo o `php artisan migrate`.
-- Las cuentas existentes quedan con account_status = 'activa'.
-- =====================================================================

ALTER TABLE `users`
    ADD COLUMN `professional_license` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Cédula profesional (capturada; no implica verificación)' AFTER `access_code`,
    ADD COLUMN `degree_number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Número de título profesional' AFTER `professional_license`,
    ADD COLUMN `professional_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Verificación profesional real (pendiente de implementar)' AFTER `degree_number`,
    ADD COLUMN `account_status` VARCHAR(20) NOT NULL DEFAULT 'activa' COMMENT 'pendiente | activa' AFTER `professional_verified_at`,
    ADD COLUMN `activates_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Cuándo se habilita una cuenta pendiente' AFTER `account_status`,
    ADD COLUMN `activated_at` TIMESTAMP NULL DEFAULT NULL AFTER `activates_at`,
    ADD UNIQUE KEY `users_professional_license_unique` (`professional_license`);

ALTER TABLE `diet_plans`
    ADD COLUMN `goal` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Objetivo general del plan' AFTER `name`,
    ADD COLUMN `instructions` TEXT NULL DEFAULT NULL COMMENT 'Indicaciones y observaciones del nutriólogo' AFTER `goal`;

ALTER TABLE `diet_plan_meals`
    ADD COLUMN `meal_time` TIME NULL DEFAULT NULL COMMENT 'Horario sugerido' AFTER `meal_order`,
    ADD COLUMN `notes` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Indicación de la comida' AFTER `meal_time`;

-- Si después corres `php artisan migrate`, no pasa nada: las migraciones
-- revisan si cada columna ya existe y solo quedan registradas.
