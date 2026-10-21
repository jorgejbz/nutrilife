-- =====================================================================
-- NutriLife · Actualización 3.3: crédito de la foto de cada platillo
-- Equivale a la migración 2026_10_21_000002_add_image_credit_to_diet_plan_dishes.
-- Aditiva: agrega una columna opcional, no modifica datos.
-- Se puede ejecutar varias veces (revisa si la columna ya existe).
--
-- Las fotos y sus créditos se cargan con:
--   php artisan db:seed --class=DietPlanDishSeeder
-- (archivos en public/images/platillos; créditos en
--  docs/CREDITOS_FOTOS_PLATILLOS.md)
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'diet_plan_dishes' AND column_name = 'image_credit'
);

SET @sql := IF(@existe = 0,
    'ALTER TABLE diet_plan_dishes ADD COLUMN image_credit VARCHAR(255) NULL DEFAULT NULL COMMENT ''Crédito de la foto: Autor · Licencia'' AFTER image_url',
    'SELECT ''image_credit ya existe'' AS aviso');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_10_21_000002_add_image_credit_to_diet_plan_dishes', COALESCE(MAX(batch), 0) + 1 FROM migrations
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_10_21_000002_add_image_credit_to_diet_plan_dishes');
