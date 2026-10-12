-- =====================================================================
-- NutriLife · Usuarios de demostración que YA COMPRARON el sistema
-- Uno por cada plan: Esencial, Pro y Consultorio.
--
-- Base de datos: proyecto (proyecto_v3.sql). Solo INSERT/UPDATE de
-- datos; NO cambia la estructura de ninguna tabla.
--
-- Qué hace:
--   1. Asegura que existan los 3 planes (si ya corriste
--      SubscriptionPlanSeeder no los duplica).
--   2. Asigna a cada plan su límite de pacientes (max_patients).
--   3. Crea 3 nutriólogos verificados.
--   4. A cada uno le crea una suscripción ACTIVA (ya pasó su prueba
--      de 7 días y compró) y sus 2 cobros mensuales.
--
-- Contraseña de los 3 usuarios:  NutriLife2026!
-- (hash bcrypt generado con password_hash de PHP, compatible con Laravel)
--
-- Ejecutar UNA sola vez (en phpMyAdmin: pestaña SQL de la BD "proyecto").
-- Si se ejecuta de nuevo fallará por los correos/códigos únicos y la
-- transacción no dejará nada a medias. Para repetirlo, usa primero el
-- bloque de LIMPIEZA del final.
-- =====================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. Planes (mismos precios que la landing)
-- ---------------------------------------------------------------------
INSERT INTO subscription_plans (name, price, billing_period, max_patients, active, created_at, updated_at)
SELECT 'Esencial', 249.00, 'mensual', NULL, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE name = 'Esencial');

INSERT INTO subscription_plans (name, price, billing_period, max_patients, active, created_at, updated_at)
SELECT 'Pro', 399.00, 'mensual', NULL, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE name = 'Pro');

INSERT INTO subscription_plans (name, price, billing_period, max_patients, active, created_at, updated_at)
SELECT 'Consultorio', 599.00, 'mensual', NULL, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE name = 'Consultorio');

-- ---------------------------------------------------------------------
-- 2. Diferencia que SÍ guarda la BD: límite de pacientes
--    Esencial    →  50 pacientes
--    Pro         → 200 pacientes
--    Consultorio → sin límite (NULL)
-- ---------------------------------------------------------------------
UPDATE subscription_plans SET max_patients = 50,   updated_at = NOW() WHERE name = 'Esencial';
UPDATE subscription_plans SET max_patients = 200,  updated_at = NOW() WHERE name = 'Pro';
UPDATE subscription_plans SET max_patients = NULL, updated_at = NOW() WHERE name = 'Consultorio';

SET @plan_esencial    = (SELECT id FROM subscription_plans WHERE name = 'Esencial'    ORDER BY id LIMIT 1);
SET @plan_pro         = (SELECT id FROM subscription_plans WHERE name = 'Pro'         ORDER BY id LIMIT 1);
SET @plan_consultorio = (SELECT id FROM subscription_plans WHERE name = 'Consultorio' ORDER BY id LIMIT 1);

-- ---------------------------------------------------------------------
-- 3. Nutriólogos (role_id = 1). Se registraron el 31/08/2026,
--    usaron su prueba de 7 días y compraron el 07/09/2026.
-- ---------------------------------------------------------------------
SET @password = '$2y$12$2I5sT6Xj9J8zT7mYoDmn1.NnRzgSyGhw8ps6.hW0k7.OMf19ZcV1W';

INSERT INTO users (name, email, email_verified_at, password, role_id, access_code, created_at, updated_at) VALUES
    ('Dra. Laura Méndez Ortiz',   'esencial@nutrilife.test',    '2026-08-31 10:00:00', @password, 1, '724901', '2026-08-31 10:00:00', NOW()),
    ('Dr. Carlos Ibarra Ruiz',    'pro@nutrilife.test',         '2026-08-31 11:00:00', @password, 1, '724902', '2026-08-31 11:00:00', NOW()),
    ('Clínica Vida Sana (Dra. Paola Herrera)', 'consultorio@nutrilife.test', '2026-08-31 12:00:00', @password, 1, '724903', '2026-08-31 12:00:00', NOW());

SET @user_esencial    = (SELECT id FROM users WHERE email = 'esencial@nutrilife.test');
SET @user_pro         = (SELECT id FROM users WHERE email = 'pro@nutrilife.test');
SET @user_consultorio = (SELECT id FROM users WHERE email = 'consultorio@nutrilife.test');

-- ---------------------------------------------------------------------
-- 4a. Historial: la prueba gratuita que ya terminó (31/08 → 07/09)
-- ---------------------------------------------------------------------
INSERT INTO subscriptions (user_id, subscription_plan_id, status, starts_at, current_period_ends_at, cancelled_at, created_at, updated_at) VALUES
    (@user_esencial,    @plan_esencial,    'vencida', '2026-08-31', '2026-09-07', NULL, '2026-08-31 10:00:00', '2026-09-07 09:00:00'),
    (@user_pro,         @plan_esencial,    'vencida', '2026-08-31', '2026-09-07', NULL, '2026-08-31 11:00:00', '2026-09-07 09:00:00'),
    (@user_consultorio, @plan_esencial,    'vencida', '2026-08-31', '2026-09-07', NULL, '2026-08-31 12:00:00', '2026-09-07 09:00:00');

-- ---------------------------------------------------------------------
-- 4b. Suscripción ACTIVA (la compra). Se inserta después de la prueba
--     para que sea la más reciente (User::latestSubscription) y el panel
--     ya no muestre el aviso de prueba.
--     Periodo mensual vigente: 07/10/2026 → 07/11/2026.
-- ---------------------------------------------------------------------
INSERT INTO subscriptions (user_id, subscription_plan_id, status, starts_at, current_period_ends_at, cancelled_at, created_at, updated_at) VALUES
    (@user_esencial,    @plan_esencial,    'activa', '2026-09-07', '2026-11-07', NULL, '2026-09-07 10:15:00', '2026-10-07 10:15:00');
SET @sub_esencial = LAST_INSERT_ID();

INSERT INTO subscriptions (user_id, subscription_plan_id, status, starts_at, current_period_ends_at, cancelled_at, created_at, updated_at) VALUES
    (@user_pro,         @plan_pro,         'activa', '2026-09-07', '2026-11-07', NULL, '2026-09-07 11:20:00', '2026-10-07 11:20:00');
SET @sub_pro = LAST_INSERT_ID();

INSERT INTO subscriptions (user_id, subscription_plan_id, status, starts_at, current_period_ends_at, cancelled_at, created_at, updated_at) VALUES
    (@user_consultorio, @plan_consultorio, 'activa', '2026-09-07', '2026-11-07', NULL, '2026-09-07 12:30:00', '2026-10-07 12:30:00');
SET @sub_consultorio = LAST_INSERT_ID();

-- ---------------------------------------------------------------------
-- 5. Cobros de la plataforma (septiembre y octubre)
-- ---------------------------------------------------------------------
INSERT INTO subscription_payments (subscription_id, amount, paid_at, payment_method, status, reference, created_at, updated_at) VALUES
    (@sub_esencial,    249.00, '2026-09-07 10:15:00', 'Tarjeta',       'pagado', 'NL-ESE-202609', '2026-09-07 10:15:00', '2026-09-07 10:15:00'),
    (@sub_esencial,    249.00, '2026-10-07 10:15:00', 'Tarjeta',       'pagado', 'NL-ESE-202610', '2026-10-07 10:15:00', '2026-10-07 10:15:00'),
    (@sub_pro,         399.00, '2026-09-07 11:20:00', 'Transferencia', 'pagado', 'NL-PRO-202609', '2026-09-07 11:20:00', '2026-09-07 11:20:00'),
    (@sub_pro,         399.00, '2026-10-07 11:20:00', 'Transferencia', 'pagado', 'NL-PRO-202610', '2026-10-07 11:20:00', '2026-10-07 11:20:00'),
    (@sub_consultorio, 599.00, '2026-09-07 12:30:00', 'Tarjeta',       'pagado', 'NL-CON-202609', '2026-09-07 12:30:00', '2026-09-07 12:30:00'),
    (@sub_consultorio, 599.00, '2026-10-07 12:30:00', 'Tarjeta',       'pagado', 'NL-CON-202610', '2026-10-07 12:30:00', '2026-10-07 12:30:00');

COMMIT;

-- ---------------------------------------------------------------------
-- Comprobación (opcional): un renglón por usuario con su plan activo
-- ---------------------------------------------------------------------
SELECT u.name AS nutriologo, u.email, u.access_code, p.name AS plan, p.price,
       COALESCE(p.max_patients, 'Sin límite') AS limite_pacientes,
       s.status, s.current_period_ends_at AS vence,
       (SELECT COUNT(*) FROM subscription_payments sp WHERE sp.subscription_id = s.id) AS pagos
FROM users u
JOIN subscriptions s ON s.user_id = u.id AND s.status = 'activa'
JOIN subscription_plans p ON p.id = s.subscription_plan_id
WHERE u.email LIKE '%@nutrilife.test'
ORDER BY p.price;

-- ---------------------------------------------------------------------
-- LIMPIEZA (solo si necesitas volver a ejecutar este script)
-- subscriptions → users tiene ON DELETE CASCADE y
-- subscription_payments → subscriptions también, así que basta con:
-- ---------------------------------------------------------------------
-- DELETE FROM users WHERE email IN ('esencial@nutrilife.test', 'pro@nutrilife.test', 'consultorio@nutrilife.test');
