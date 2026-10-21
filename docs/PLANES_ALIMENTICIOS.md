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
2. ~~**Alimentos personalizados y platillos**~~: **hecho en la
   actualización 3.2** (con `user_id` en platillos). Ver
   `docs/UNIFICACION_PLAN_ALIMENTICIO.md`.
3. **Varios nutriólogos en un Consultorio**: requiere tablas nuevas
   (consultorio, miembros, roles internos).
4. **Límite de pacientes por plan** (`max_patients`): sigue sin aplicarse.

## 6. Pruebas

`tests/Feature/DietPlanAccessTest.php` y `tests/Feature/DietPlanManagementTest.php`
(21 pruebas): acceso por cada tipo de cuenta, rutas bloqueadas con peticiones
manipuladas, cambio de plan en caliente, registro nuevo con prueba, flujo
completo, límites de la BD, aislamiento entre dos consultorios, avisos de
alergia y cálculo de totales. Suite completa: 46 pruebas, 270 aserciones, OK.

## 7. Migración de la interfaz de `nutrilife_planesAlim` (21/10/2026)

La versión de Planes alimenticios que se usa es la de `nutrilife_planesAlim`
(diseño y flujo). Se integró sobre el backend de la versión final, que ya era
compatible con la BD `nutrilife` y más seguro (policy, `feature:diet_plans`,
`scopeBindings`, límites DECIMAL, platillos sin valores inventados).

| Origen | Versión final |
|---|---|
| `dish_id` en `diet_plan_items` | `diet_plan_dish_id` (FK real, ON DELETE SET NULL) |
| Rutas fuera de `auth`, sin revisar dueño | Grupo `auth` + `feature:diet_plans` + `DietPlanPolicy` |
| `storeDish` inventaba 350 kcal / 100 kcal | Rechaza platillos sin ingredientes; copia valores reales |
| `forceDelete` del plan | Soft delete (`deleted_at`), conserva comidas |
| CSS en línea que forzaba tema oscuro en todo el panel | `public/css/diet-plans.css` (prefijo `dp-`), modo claro/oscuro y responsivo |
| JS en línea con `onclick` | `resources/js/pages/diet-plans.js` (Vite) |
| Un drawer por comida | Un drawer compartido por tipo (mismo comportamiento) |

Se conservaron, con el diseño del origen, las funciones que el origen no tenía:
editar plan y finalizarlo, duplicar/asignar, cambiar gramos, editar comida
(nombre, horario, indicación), objetivo e indicaciones, avisos de alergias y
patologías, filtros del listado y ficha del paciente.

Sin cambios en la BD: no se copiaron las migraciones `2026_10_02_*` del origen
(su estructura ya existe por `2026_10_21_000001`).
Pruebas: `tests/Feature/DietPlanMigratedUiTest.php` (5) — suite completa 96 OK.

## 8. Catálogo de platillos y paneles en celular (21/10/2026)

**Catálogo de platillos** (`/platillos`, `dishes.index`, menú «Catálogo de
platillos»): hermano del Catálogo de alimentos, solo consulta y mismo acceso
(`feature:diet_plans`). Muestra los platillos del sistema y los propios
(`DietPlanDish::visibleTo`), con búsqueda por nombre o ingrediente, filtros
(origen, datos nutricionales, excluir alérgeno), orden, 12 por página y un
detalle con la tabla de ingredientes y aporte. Archivos:
`DishCatalogController`, `resources/views/dishes/index.blade.php`,
`resources/js/pages/dishes-index.js`, sección «CATÁLOGO DE PLATILLOS» de
`public/css/diet-plans.css`, `tests/Feature/DishCatalogTest.php`.
El panel «Platillo» del constructor enlaza al catálogo.

**Paneles laterales (drawers) del constructor**: encabezado y botón principal
fijos (`.dp-drawer-actions`), un solo scroll (la lista de platillos ya no
tiene scroll propio), alto `100dvh`, en celular una franja del fondo visible
para cerrar tocándola y ajustes para pantallas bajas (celular horizontal).

## 9. Catálogo INCMNSZ 2015 y recetas de los platillos (21/10/2026)

**Importación de `proyecto_con_catalogos.sql`.** El dump traía `DROP TABLE`
de todas las tablas, `foods` con otros IDs (el 1 era "Arroz, atole", no
"Manzana") y `diet_plan_items` vacío: importarlo completo habría borrado los
renglones de los planes y apuntado los existentes a otros alimentos. Usuarios,
pacientes, citas, consultas, pagos y planes eran idénticos. Por eso se importó
**solo lo nuevo** con `IncmnszCatalogSeeder` (datos en
`database/data/catalogo_incmnsz_2015.json`): solo agrega, por nombre y se
puede repetir.

| | Antes | Después |
|---|---|---|
| Alimentos | 89 (SMAE) | 1,588 (+1,499 INCMNSZ; 14 frutas con el mismo nombre se conservan del SMAE) |
| Grupos | 8 | 21 (se reutilizan Frutas, Verduras, Leche y derivados) |
| Alérgenos / patologías | 11 / 18 | 14 / 32 (mismos IDs + nuevos) |
| Alimento-alérgeno | 34 | 618 |

**Recetas.** `DietPlanDishSeeder` ahora carga la receta completa de los 20
platillos (114 ingredientes): ligados al catálogo (SMAE e INCMNSZ) y, solo si
no existen ahí, personalizados con valores USDA por 100 g
(`DietPlanDishSeeder::CUSTOM_FOODS`: tofu, espárragos, queso feta, pechuga de
pavo, pavo molido, hummus, galletas integrales). Reemplaza recetas del
sistema distintas a la definida y nunca toca platillos propios. Los planes
que ya tenían un platillo insertado conservan su copia anterior.

Orden en una BD nueva: `FoodSeeder` → `IncmnszCatalogSeeder` →
`DietPlanDishSeeder` (ya así en `DatabaseSeeder`).

Pendiente: el constructor carga todo el catálogo en el selector (~1.4 MB con
1,588 alimentos); conviene una búsqueda por AJAX si el catálogo crece más.

## 10. Usuario de la base secundaria y fotos de los platillos (21/10/2026)

**Usuario importado de `nutrilife_usuariosmanu.sql`.** Manuel
(josemanuelgarcializaola2212@gmail.com) quedó como usuario 8, con su misma
contraseña y código de acceso, y con sus 10 pacientes (IDs 17–26), 3 citas,
1 consulta, 2 pagos y 9 planes (29 comidas, 35 renglones). Se importó en
una transacción con IDs nuevos y relaciones reconectadas; no traía
suscripción, así que al entrar la app le crea la prueba gratuita. Los 3
renglones sin kcal del plan «Dieta Cetogénica» venían así (copia de una
receta incompleta) y no se inventaron valores.

**Fotos de los platillos (actualización 3.3).** Las 20 fotos genéricas se
reemplazaron por fotos que muestran cada platillo, de Wikimedia Commons,
guardadas en `public/images/platillos/`. Columna nueva
`diet_plan_dishes.image_credit` (migración `2026_10_21_000002`, SQL
`actualizacion_v3_3.sql`); el crédito se muestra en el detalle del Catálogo
de platillos. Lista y licencias: `docs/CREDITOS_FOTOS_PLATILLOS.md`.
`DietPlanDish::imageSrc()` / `DietPlanItem::imageSrc()` convierten la ruta
con `asset()` (funciona con XAMPP en subcarpeta). Al cambiar la foto de un
platillo, `DietPlanDishSeeder` actualiza también las copias de la foto
anterior en los planes (solo la foto).

## 11. Datos de prueba por cuenta (21/10/2026)

`DemoAccountsSeeder` (y su atajo `ProDemoSeeder`) carga datos de prueba por
cuenta según lo que permite su paquete: pacientes con historial, citas de
hoy y futuras, un paciente inactivo y uno dado de baja, pagos (uno pendiente)
y, solo en Pro/Consultorio, planes con platillos propios, platillos
insertados, un alimento personalizado y avisos de alergia. Cuentas:
pro@, consultorio@, esencial@nutrilife.test, Manuel (se le activa Pro) y
jorgejb234@gmail.com (prueba vencida, sin planes). Repetible y sin borrar.

Se eliminaron las cuentas test@example.com, jjuarezbravo2@gmail.com y
fernandogarcia@nutrilife.com con sus datos. A 8 pacientes importados de
Manuel les faltaba `created_at` (rompía su ficha): se completó y la ficha ya
tolera una fecha vacía.

## 12. Más platillos del sistema (21/10/2026)

Se agregaron 19 platillos del sistema (39 en total) con receta completa del
catálogo y foto de Wikimedia Commons (`DietPlanDishSeeder`; créditos en
`docs/CREDITOS_FOTOS_PLATILLOS.md`). Se borró el platillo propio de prueba
«Molletes Integrales con Pico de Gallo» de pro@ (los planes conservan su copia)
y se quitó de `DemoAccountsSeeder`.
