# Planes alimenticios — integración y permisos por plan

## 1. Diagnóstico de la versión anterior (zip `nutrilife (7)`)

La sección venía de la rama de ManuDEV04 (commits "Apartado realizar dietas" y
"Implementacion de menus en la creacion de dietas") más cambios sin commit.

| Qué tenía | Estado real |
|---|---|
| Listado, crear plan, constructor con resumen del día y comidas | Funcionaba |
| Agregar alimento del catálogo `foods` por gramos | Funcionaba |
| Alimentos personalizados (`custom_*`, `food_id` NULL) | Migración `2026_10_02_020209` **altera la BD definitiva** y usa `MODIFY` (solo MySQL: rompía todas las pruebas) |
| Platillos (`diet_plan_dishes`, `diet_plan_dish_items`, `dish_id`, `custom_image_url`) | **Sin migración real**: la tabla de platillos se creaba solo con `id` y timestamps; las demás columnas/tablas se habían creado a mano. En una BD nueva fallaba. Si un platillo no tenía ingredientes se insertaban valores inventados (350 kcal, 25 g proteína…) |
| Editar plan / finalizarlo | No existía (el plan quedaba "Activo" para siempre) |
| Seguridad | Las rutas estaban **fuera** del grupo `auth`; `show` no revisaba dueño (cualquiera podía ver cualquier plan cambiando el número en la URL) |
| Diseño | ~4,000 líneas de CSS dentro de las vistas, independiente del sistema de diseño |

## 2. Qué se integró (sobre la BD definitiva, sin cambiarla)

Decisión aprobada: **respetar `proyecto_v3.sql`**. Se usan solo `diet_plans`,
`diet_plan_meals`, `diet_plan_items`, `foods`, `food_groups`, `food_allergens`,
`food_pathology_restrictions`, `patient_allergens` y `patient_pathologies`.

- **Listado** con métricas, filtros por paciente y estado, avance de calorías.
- **Crear / editar plan**: paciente (solo propios y activos; no se cambia al
  editar), nombre, fechas, objetivos diarios con verificación en vivo de que
  los macros (4/4/9 kcal por gramo) cuadran con las calorías; switch
  Activo/Finalizado.
- **Constructor**: resumen del día vs. objetivos (kcal y P/C/G), tarjeta del
  paciente (objetivo, alergias, patologías), tiempos de comida con
  sugerencias, buscador del catálogo por nombre y grupo con vista previa del
  aporte, cambio de gramos en línea, quitar alimento o comida.
- **Avisos clínicos** con relaciones que ya existían en la BD: si el alimento
  contiene un alérgeno del paciente o está prohibido/limitado para alguna de
  sus patologías. Solo informan; no bloquean.
- **Ficha del paciente**: bloque con sus planes y botón "Nuevo plan".
- **Eliminar plan** = soft delete (`diet_plans.deleted_at`), como pacientes,
  consultas y pagos.

Equivalentes = gramos ÷ `foods.portion_grams` (DECIMAL(4,2), máx. 99.99).
Kcal y macros no se guardan: se calculan del catálogo.

## 3. Permisos

Fuente única: `config/nutrilife.php` → `features.diet_plans`.

| Cuenta | Acceso |
|---|---|
| Pro / Consultorio con suscripción `activa` y periodo vigente | Sí |
| Pro / Consultorio `cancelada` antes de que termine el periodo pagado | Sí, hasta `current_period_ends_at` |
| Prueba gratuita vigente (`en_prueba`) | Sí |
| Esencial, prueba vencida, suscripción `vencida`, periodo terminado | No → pantalla "Disponible en Pro y Consultorio" (403) |
| Administrador | No (no es cuenta de nutriólogo) |

Capas (ninguna depende de la otra):

1. Menú: `'can' => 'diet-plans'` (Gate en `AppServiceProvider`).
2. Rutas: middleware `feature:diet_plans` (`EnsureFeature`) en todo el grupo,
   incluidas POST/PUT/PATCH/DELETE y peticiones JSON.
3. `{plan}` solo se resuelve entre los planes del nutriólogo autenticado
   (404 para planes ajenos) y `scopeBindings()` obliga a que la comida sea
   del plan y el alimento de la comida.
4. `DietPlanPolicy` (feature + dueño) en cada acción del controlador.
5. Validación: `patient_id` debe ser propio, activo y vigente.

El acceso se calcula en cada petición desde la suscripción más reciente, así
que comprar, cancelar o vencer se refleja de inmediato. Si una cuenta baja de
Pro a Esencial, sus planes **se conservan** en la BD pero no se pueden abrir
hasta que vuelva a un plan que los incluya.

**Consultorio:** la BD no tiene tabla de consultorios ni de equipos; una
cuenta Consultorio es un usuario más. El aislamiento es por `user_id`, igual
que pacientes, citas, consultas y pagos.

## 4. Cambios en la base de datos

Ninguno. Se eliminaron las dos migraciones de la versión anterior
(`2026_10_02_012125_create_diet_plan_dishes_table.php` y
`2026_10_02_020209_add_custom_food_fields_to_diet_plan_items_table.php`).

Si tu BD local ya tiene esas columnas/tablas porque las corriste, no estorban
(son opcionales y el código nuevo no las usa). Para dejarla idéntica a la
definitiva, vuelve a importar `proyecto_v3.sql` en una BD limpia.

## 5. Pendientes que necesitan decisión

1. **Catálogo de alimentos**: la tabla `foods` está vacía en la BD
   definitiva. `FoodCatalogSeeder` (de la versión anterior) solo trae 10
   alimentos de prueba con `source = 'Prueba NutriLife'`. Para uso real hay
   que cargar el catálogo SMAE/INCAP (y, si se quiere, `food_allergens` y
   `food_pathology_restrictions` para que salgan los avisos).
2. **Alimentos personalizados y platillos**: requieren columnas/tablas
   nuevas. Si se aprueban, conviene que los platillos tengan `user_id` para
   que cada cuenta tenga los suyos.
3. **Varios nutriólogos en un Consultorio**: requiere tablas nuevas
   (consultorio, miembros, roles internos).
4. **Límite de pacientes por plan** (`max_patients`): sigue sin aplicarse.

## 6. Pruebas

`tests/Feature/DietPlanAccessTest.php` y `tests/Feature/DietPlanManagementTest.php`
(21 pruebas): acceso por cada tipo de cuenta, rutas bloqueadas con peticiones
manipuladas, cambio de plan en caliente, registro nuevo con prueba, flujo
completo, límites de la BD, aislamiento entre dos consultorios, avisos de
alergia y cálculo de totales. Suite completa: 46 pruebas, 270 aserciones, OK.
