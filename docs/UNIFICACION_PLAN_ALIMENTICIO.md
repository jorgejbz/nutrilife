# Unificación del módulo de plan alimenticio (actualización 3.2)

Base principal: `Base de datos principal/NutriLife` + dump `proyecto (1).sql` (BD `proyecto`).
Base secundaria: `base secundaria/nutrilife` + dump `nutrilife.sql` (BD `nutrilife`).
Ambas en MariaDB 10.4.32 · Laravel 13.

**Resultado:** `Proyecto actualizado/NutriLife` (código unificado) y
`Proyecto actualizado/proyecto_unificado.sql` (BD unificada). Las secciones 7
y 9 describen lo que se hizo y cómo se verificó.

## 1. Resumen

La principal ya tiene el módulo de planes completo (crear/editar/duplicar,
constructor, avisos clínicos, permisos por suscripción). Lo que **solo existe
en la secundaria** son dos características:

1. **Alimentos personalizados**: renglones del plan que no salen del catálogo
   `foods` (`food_id` NULL + columnas `custom_*`).
2. **Platillos**: recetas reutilizables (`diet_plan_dishes` + ingredientes
   `diet_plan_dish_items`) que se copian a una comida del plan.

Decisión: la **principal es la única fuente de verdad** (código y BD). Absorbe
esas dos características con una migración aditiva (salvo `food_id`, que pasa
a aceptar NULL). La funcionalidad de la secundaria ya está portada (sección 7):
ambos programadores trabajan desde ahora sobre este proyecto.

`docs/PLANES_ALIMENTICIOS.md` §5.2 ya dejaba esto como pendiente ("si se
aprueban, conviene que los platillos tengan `user_id`"). Esta actualización lo
implementa.

## 2. Diagnóstico: diferencias entre las dos bases

### 2.1 Estructura (29 tablas en común; la secundaria tiene 2 más)

| Tabla | Principal | Secundaria |
|---|---|---|
| `diet_plan_dishes` | — | `id, name NULL, image_url, timestamps` |
| `diet_plan_dish_items` | — | `id, diet_plan_dish_id, food_id NULL, custom_name, custom_kcal/protein/carbs/lipids DECIMAL(8,2), grams DECIMAL(8,2)` |
| `diet_plan_items.food_id` | NOT NULL | NULL |
| `diet_plan_items` columnas extra | — | `dish_id`, `custom_name`, `custom_kcal`, `custom_protein_g`, `custom_carbs_g`, `custom_lipids_g`, `custom_portion_description`, `custom_image_url` |
| `diet_plans.goal`, `.instructions` | Sí (3.1) | — |
| `diet_plan_meals.meal_time`, `.notes` | Sí (3.1) | — |
| `users` registro profesional y activación (6 columnas) | Sí (3.1) | — |

Lo que tiene la principal y no la secundaria (3.1) ya está en el repositorio
principal: el programador de la secundaria lo recibe al pasarse a él.

### 2.2 Catálogos: incompatibles por ID

| | Principal | Secundaria |
|---|---|---|
| `food_groups` | 8 grupos SMAE (1 = Frutas … 8 = Azúcares) | 10 grupos (1 = Cereales … 10 = Otros) |
| `foods` | 89 alimentos | 132 (10 con `source = 'Prueba NutriLife'`) |
| Nombres en común | 28 | 28 |

El mismo `food_id` significa alimentos distintos en cada base. **No se importa
el catálogo de la secundaria.** No rompe nada: en la secundaria ningún
renglón de plan ni ingrediente de platillo apunta a `foods` (todos tienen
`food_id` NULL), así que nada depende de esos IDs.

### 2.3 Problemas encontrados en la secundaria (no se deben portar)

| Problema | Impacto |
|---|---|
| Las migraciones no corresponden a la BD real: `create_diet_plan_dishes` solo crea `id` + timestamps; `diet_plan_dish_items`, `dish_id` y `custom_image_url` **no tienen migración** (se hicieron en phpMyAdmin). | En una BD nueva el módulo falla. |
| La migración `2026_10_02_020209` usa `MODIFY` crudo (solo MySQL). | Rompe las pruebas (SQLite). |
| `diet_plan_items.dish_id` **sin llave foránea**. | Puede apuntar a platillos inexistentes. |
| `storeDish` inventa valores: ingredientes sin macros reciben 100 kcal / 5 g / 10 g / 2 g; platillos sin ingredientes se insertan con 350 kcal / 25 / 30 / 10. También copia esos valores a ingredientes **del catálogo**, dejando dos fuentes de verdad. | Datos clínicos falsos en planes de pacientes (renglones 56 y 67 de la secundaria tienen la firma 350/25/30/10). |
| Rutas de planes **fuera** del grupo `auth` (`routes/web.php` líneas 119–129); `show` y `storeDish` no revisan dueño. | Cualquiera puede ver planes ajenos o insertar platillos en ellos. |
| `diet_plan_dishes` en `utf8mb4_unicode_ci`; el resto en `utf8mb4_general_ci`. | "Illegal mix of collations" al comparar textos entre tablas. |
| Precisión `DECIMAL(8,2)` en `diet_plan_dish_items.grams`, pero `diet_plan_items.grams` es `DECIMAL(6,2)`. | Copiar un ingrediente > 9999.99 g al plan falla. |

### 2.4 Nota sobre los dumps de phpMyAdmin

Los dos dumps pierden el `CHECK fpr_food_or_group_check` de
`food_pathology_restrictions` (sale como `) ;` sin `ENGINE`). No usen dumps de
phpMyAdmin como esquema de referencia: la referencia es `proyecto_v3.sql` +
`actualizacion_v3_1.sql` + `actualizacion_v3_2.sql` (o las migraciones).

## 3. Inventario del módulo en la secundaria

| Capa | Elemento |
|---|---|
| Tablas | `diet_plan_dishes`, `diet_plan_dish_items` |
| Columnas | `diet_plan_items`: `dish_id`, 7 columnas `custom_*`, `food_id` NULL |
| Llaves foráneas | `diet_plan_dish_items.diet_plan_dish_id → diet_plan_dishes` (CASCADE). Faltaban: `dish_id`, `diet_plan_dish_items.food_id` |
| Datos | 20 platillos; 13 ingredientes en 4 platillos (todos personalizados) |
| Modelos | `DietPlanDish`, `DietPlanDishItem`, `DietPlanItem` (fillable, `dish()`, `isCustom()`) |
| Controladores | `DietPlanMealController::storeDish`, `DietPlanItemController::storeCustom`, `DietPlanController::show` (envía `$dishes`) |
| Rutas | `diet-plans.meals.dishes.store`, `diet-plans.items.custom.store` |
| Vista | `diet-plans/show.blade.php`: drawer de platillos, formulario de alimento personalizado, totales con `custom_*` |

## 4. Esquema unificado

```
users ─┬─< diet_plans ─< diet_plan_meals ─< diet_plan_items >─┬─ foods (RESTRICT)
       │                                         │             │
       │                                         └─ SET NULL ─>┤
       └─< diet_plan_dishes (user_id NULL = sistema) ─< diet_plan_dish_items >─ foods (RESTRICT)
                    (CASCADE)                              (CASCADE)
```

| Tabla | Cambio |
|---|---|
| `diet_plan_dishes` (nueva) | `id`, **`user_id` NULL** → `users` ON DELETE CASCADE, `name` **NOT NULL**, `image_url` VARCHAR(500), timestamps |
| `diet_plan_dish_items` (nueva) | `diet_plan_dish_id` → CASCADE, `food_id` NULL → `foods` (**nueva FK**), `custom_name`, `custom_kcal` DECIMAL(7,2), macros DECIMAL(6,2), `grams` DECIMAL(6,2) DEFAULT 100 |
| `diet_plan_items` | `food_id` NULL; **`diet_plan_dish_id`** → `diet_plan_dishes` ON DELETE SET NULL (nueva FK); 7 columnas `custom_*` |
| CHECK en ambas tablas de renglones | del catálogo (`food_id` y sin `custom_name`/macros) **o** personalizado (`custom_name` y sin `food_id`), nunca ambos ni ninguno |

Decisiones de diseño:

- **`user_id` en platillos**: NULL = platillo del sistema (visible para todas
  las cuentas); con valor = platillo propio del nutriólogo, aislado por
  `user_id` como pacientes, citas y planes.
- **`dish_id` → `diet_plan_dish_id`**: mismo nombre que en
  `diet_plan_dish_items` y la convención de Laravel (`belongsTo` lo infiere).
- **Copia, no referencia**: al insertar un platillo, sus ingredientes se
  **copian** a `diet_plan_items`. Editar o borrar el platillo no cambia los
  planes ya hechos; `diet_plan_dish_id` solo indica de dónde salió el renglón.
- **`custom_kcal` y macros = aporte total del renglón** para sus `grams`
  (mismo criterio que la vista de la secundaria). Para los del catálogo se
  siguen calculando desde `foods` y no se guardan.
- **Precisión igual a `foods`** para que los límites `DietPlan::MAX_KCAL`,
  `MAX_MACRO_G` y `DietPlanItem::MAX_GRAMS` apliquen igual.
- **CHECK en la BD** (como `fpr_food_or_group_check`): impide renglones vacíos
  y renglones con dos fuentes de verdad. En SQLite (pruebas) lo cubre la
  validación.

## 5. Scripts

| Archivo | Qué hace |
|---|---|
| `database/migrations/2026_10_21_000001_add_dishes_and_custom_foods_to_diet_plans.php` | Migración. Idempotente (revisa tablas, columnas y CHECK). `down()` se niega a revertir si hay renglones personalizados, en vez de borrarlos. |
| `database/sql/actualizacion_v3_2.sql` | Equivalente en SQL de la migración. |
| `database/sql/catalogo_platillos.sql` | 20 platillos del sistema + 13 ingredientes de la secundaria. Idempotente (busca por nombre). |
| `database/seeders/DietPlanDishSeeder.php` | Lo mismo que `catalogo_platillos.sql`; lo llama `DatabaseSeeder`. |
| `../proyecto_unificado.sql` | La BD principal completa ya unificada (datos de `proyecto (1).sql` + 3.2 + platillos), exportada con `mysqldump`. |

Cómo aplicar (elige una):

- **A. Reemplazar la BD por la unificada** (lo más simple): respaldar
  `proyecto`, y en phpMyAdmin importar `proyecto_unificado.sql` sobre ella
  (borra y recrea cada tabla). Ya trae la migración registrada.
- **B. Actualizar la BD existente sin reemplazarla**: respaldar `proyecto`;
  `php artisan migrate` (o `database/sql/actualizacion_v3_2.sql`); luego
  `php artisan db:seed --class=DietPlanDishSeeder` (o
  `database/sql/catalogo_platillos.sql`).

`proyecto_unificado.sql` además restaura el CHECK `fpr_food_or_group_check`
que el dump de phpMyAdmin había perdido (§2.4).

Instalación limpia: `proyecto_v3.sql` → `actualizacion_v3_1.sql` →
`actualizacion_v3_2.sql` → catálogos (`catalogos_clinicos.sql`,
`catalogo_platillos.sql`).

## 6. Datos a revisar con el nutriólogo

| Platillo | Problema |
|---|---|
| Licuado Proteico de Avena, Plátano y Almendras | En la secundaria tenía como ingredientes salmón, espárragos y camote. **Corregido al importar**: se ligaron a "Filete de Salmón al Horno con Espárragos y Camote". |
| Sincronizadas de Pavo | "Tortilla de Maíz o Harina Integral" con `grams = 2.00` (¿2 piezas?) y sin macros. |
| Varios | Sin macros (se importan en NULL, no se inventan): Tortilla, Mezcla de Lechugas y Pepino, Aceitunas Negras, Salmón, Espárragos, Camote. |
| Pechuga (150 g), Pechuga de Pavo (60 g), Pechuga en Tiras (120 g) | Los tres tienen 220 kcal / 35 g proteína / 0 / 5: valores copiados, no ajustados a los gramos. |
| 16 de 20 platillos | Sin ingredientes. Insertarlos hoy no aporta datos nutricionales. |

Si los ingredientes se pueden ligar a alimentos del catálogo `foods` (en vez de
personalizados), los macros salen del catálogo y los avisos de alérgenos y
patologías funcionan.

## 7. Cambios de código (hechos)

| Archivo | Cambio |
|---|---|
| `app/Models/Concerns/HasFoodOrCustomNutrients.php` (nuevo) | Lógica común de renglón "del catálogo o personalizado": `isCustom()`, `displayName()`, `nutrients()` (del catálogo o de `custom_*`), `hasNutrients()`. La usan `DietPlanItem` y `DietPlanDishItem`. |
| `app/Models/DietPlanItem.php` | `diet_plan_dish_id` y `custom_*` en `$fillable` y `casts`; relación `dish()`; constante `CUSTOM_NUTRIENTS`; `customNutrientsFor($gramos)` para reescalar. |
| `app/Models/DietPlanDish.php`, `DietPlanDishItem.php` (nuevos) | Con `user_id`, scope `visibleTo($user)` (sistema + propios), `totals()` y `missingNutrientsCount()`. |
| `app/Models/User.php` | Relación `dietPlanDishes()`. |
| `app/Http/Controllers/DietPlanItemController.php` | `storeCustom` (límites `MAX_*`, `equivalents = 1`); `storeDish` (copia ingredientes en `DB::transaction`, solo platillos `visibleTo`, **sin valores inventados**, rechaza platillos sin ingredientes); `update` reescala el aporte de los personalizados sin pasar los límites de la BD. |
| `app/Http/Controllers/DietPlanController.php` | `show` envía `$dishes` (sistema + propios; los vacíos al final) y avisos de los ingredientes del catálogo; `duplicate` usa `replicate()` y copia todas las columnas del renglón. |
| `routes/web.php` | `diet-plans.items.custom.store` (`POST …/alimentos-personalizados`) y `diet-plans.meals.dishes.store` (`POST …/platillos`), dentro de `feature:diet_plans` + `scopeBindings()`; `DietPlanPolicy` en cada acción. |
| `resources/views/diet-plans/show.blade.php` | Renglones personalizados (insignia, porción, imagen, platillo de origen, "sin datos nutricionales", "no se revisan alergias ni patologías"); botones "Agregar platillo" y "Alimento personalizado" por comida; diálogos de platillos (buscador, aporte, ingredientes) y de alimento personalizado. |
| `resources/js/pages/diet-plans.js` + `public/build` | Abrir los diálogos por comida, reabrirlos tras un error de validación, buscador de platillos. Assets recompilados (`npm run build`). |
| `public/css/admin.css` | Estilos nuevos (sección "Platillos y alimentos personalizados"). |
| `database/seeders/DietPlanDishSeeder.php` (nuevo) + `DatabaseSeeder` | Catálogo de platillos del sistema. |
| `tests/Feature/DietPlanDishesAndCustomFoodsTest.php` (nuevo) | 8 pruebas: personalizado (alta, límites, reescalado), platillo (copia, es copia, borrar platillo), platillos vacíos/ajenos, duplicar plan, aislamiento y permisos, seeder. |

Pendiente (no se hizo, fuera de alcance): pantalla para que cada nutriólogo
cree y edite **sus propios** platillos. La BD ya lo soporta (`user_id`), y
`visibleTo` ya los muestra en el constructor.

## 8. Cómo trabajan los dos programadores desde ahora

1. **Un solo repositorio y una sola BD**: este proyecto. La carpeta
   secundaria queda solo como referencia; no se fusiona tal cual.
2. Cada quien trabaja en ramas sobre este proyecto (el `.git` del principal
   viene incluido) y se integra por pull request.
3. **Ningún cambio de BD en phpMyAdmin**: todo cambio = migración +
   SQL equivalente en `database/sql/actualizacion_vX.sql`, como 3.1 y 3.2.
4. Las migraciones deben correr en SQLite (pruebas): nada de `MODIFY` crudo;
   lo exclusivo de MySQL va protegido por driver, como en esta migración.
5. BD local de cada quien: recrearla desde la instalación limpia de la
   sección 5 (o `php artisan migrate:fresh --seed`). Los planes de prueba de
   la secundaria (9 planes de los pacientes 2–10 del usuario 1) **no se
   migran**: dependen de usuarios y pacientes que no existen en la principal.
   Para datos de prueba usar `DemoDataSeeder`.
6. El catálogo de alimentos maestro es el de la principal (SMAE). Si se
   quieren los ~104 alimentos extra de la secundaria, se agregan a
   `FoodSeeder` mapeando sus grupos a los 8 grupos SMAE (tarea aparte).

## 9. Verificación

Hecho en MariaDB 10.4.32 local, en bases temporales (ya borradas):

- Sobre una copia de `proyecto (1).sql`: `actualizacion_v3_2.sql` y
  `catalogo_platillos.sql` (dos veces: no duplica) → 20 platillos,
  13 ingredientes; el renglón existente del plan quedó intacto.
- Instalación limpia `proyecto_v3.sql` → 3.1 → 3.2 → catálogo: OK.
- Integridad: renglón sin alimento ni nombre → rechazado por CHECK; alimento
  del catálogo + `custom_kcal` → rechazado; platillo inexistente → rechazado
  por FK; borrar un platillo → sus ingredientes se borran y los renglones de
  planes conservan su copia con `diet_plan_dish_id` NULL; borrar un alimento
  usado en un plan → rechazado; gramos fuera de rango → rechazado.
- `MODIFY food_id` con el CHECK ya presente (lo que hace `migrate` después
  del SQL): OK.

Con PHP 8.4 (portátil, solo para esta verificación; la PHP local es 8.0):

- `php artisan test`: **91 pruebas OK** (83 que ya había + 8 nuevas), en SQLite.
- `php artisan migrate` sobre una copia de la BD principal en MariaDB: crea
  tablas, FKs y CHECK; `migrate:rollback` la revierte limpio y se vuelve a
  aplicar; con un renglón personalizado, el rollback **se niega**. Con el SQL
  ya aplicado, `migrate` solo la registra (sin duplicar CHECK).
- `DietPlanDishSeeder` dos veces: 20 platillos y 13 ingredientes.
- `proyecto_unificado.sql` reimportado en otra BD: 31 tablas, 3 CHECK, sin
  migraciones pendientes y los mismos conteos que la BD original en usuarios,
  pacientes, citas, consultas, evaluaciones, pagos, alimentos, planes,
  comidas, renglones y suscripciones.

No se probó a mano en navegador: los diálogos nuevos se verificaron con las
pruebas (la página se genera y los envíos funcionan) y la compilación de
Vite; conviene darles una vuelta en el navegador.
