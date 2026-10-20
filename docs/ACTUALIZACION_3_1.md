# NutriLife 3.1 — auditoría, correcciones y datos de prueba

Base: zip "NutriLife" del 20/10/2026 (incluye Planes alimenticios).
Laravel 13.31 · PHP ^8.3 (vendor pide 8.4.1) · AdminLTE 4 · Vite · Chart.js (CDN).

## Cómo instalar esta actualización

1. Copia los archivos del zip sobre tu proyecto.
2. Base de datos (solo **agrega** columnas; no borra ni cambia nada):
   `php artisan migrate` **o** ejecuta `database/sql/actualizacion_v3_1.sql`.
3. `npm run build` (cambiaron `forms.js`, `dashboard.js` y varios JS de páginas; nuevo `pages/foods-index.js`).
4. `php artisan optimize:clear`.
5. Opcional, solo en local: `php artisan db:seed --class=DemoDataSeeder`.

## Configuración (un solo lugar: `.env` → `config/nutrilife.php`)

| Variable | Por defecto | Qué controla |
|---|---|---|
| `NUTRILIFE_TRIAL_DAYS` | 7 | Días de prueba gratuita |
| `NUTRILIFE_ACTIVATION_DELAY_MINUTES` | 5 | Minutos para habilitar una cuenta nueva (0 = inmediato) |
| `NUTRILIFE_SIMULATED_PAYMENTS` | true | Botón "Elegir paquete" con pago simulado |
| `APP_TIMEZONE` | America/Mexico_City | "Hoy", agenda, vencimientos |

## Cambios en la base de datos (aditivos)

| Tabla | Columna | Uso |
|---|---|---|
| users | `professional_license` VARCHAR(20) UNIQUE NULL | Cédula profesional (solo formato: 7–8 dígitos) |
| users | `degree_number` VARCHAR(50) NULL | Número de título |
| users | `professional_verified_at` TIMESTAMP NULL | Verificación profesional **real** (hoy nadie la llena) |
| users | `account_status` VARCHAR(20) DEFAULT 'activa' | `pendiente` / `activa`. Las cuentas existentes quedan activas |
| users | `activates_at`, `activated_at` TIMESTAMP NULL | Cuándo se habilita / se habilitó |
| diet_plans | `goal` VARCHAR(255) NULL, `instructions` TEXT NULL | Objetivo e indicaciones del plan |
| diet_plan_meals | `meal_time` TIME NULL, `notes` VARCHAR(255) NULL | Horario e indicación por comida |

## Qué se encontró y qué se hizo

1. **Fechas.** Varias hojas fijaban `color-scheme: dark` en los campos de fecha:
   en modo claro el icono del calendario era blanco sobre blanco (invisible) y
   el clic en el texto no abre el calendario en Chrome. Arreglo global (CSS +
   `forms.js` con `showPicker()`), validado en los 9 campos de fecha del panel,
   claro/oscuro, escritorio y móvil. Validación de servidor: nacimiento y
   diagnóstico entre 1900 y hoy; evaluación y consulta no futuras; pago no
   futuro; cita pendiente/confirmada no en el pasado. Formato guardado sin
   cambios (AAAA-MM-DD).
2. **Prueba de 7 días.** El inicio real es `subscriptions.created_at`; vence a
   la hora exacta. Al vencer (o si un paquete pagado vence) el middleware manda
   a `/contratar`; `/contratar` y cerrar sesión nunca se bloquean (sin bucles).
   Pantalla con los paquetes reales de `subscription_plans` y las funciones de
   `config/nutrilife.php`; pago simulado registrado como "Simulado".
3. **Dashboard.** Las gráficas usaban arreglos fijos en la vista
   (`[0,0,0,0,0,$total]`) y los indicadores tenían porcentajes inventados
   ("Sistema operativo", barras al 70 %). Ahora todo sale de
   `App\Services\DashboardStats`, filtrado por el nutriólogo: pacientes
   acumulados por mes, activos/inactivos, citas de la semana (sin canceladas),
   ingresos cobrados por mes, próximas citas, pagos pendientes, asistencia del
   mes y planes activos. Filtro 6/12 meses y mensajes cuando no hay datos.
4. **Estado de pacientes.** "Inactivo" usaba el mismo verde que "Activo". Ahora
   hay un componente único (`partials/patient-status`): activo verde con ✓,
   inactivo gris con ⏸, en lista, ficha, vista previa y métricas, más filtro
   Todos/Activos/Inactivos en `/pacientes`.
5. **Registro y activación.** Se piden cédula y número de título. La cuenta
   queda `pendiente`, se muestra "¡Gracias por registrarte!" y se habilita sola
   al cumplirse el tiempo (se revisa en el servidor al iniciar sesión o entrar
   a cualquier ruta privada). El usuario no puede activarse por petición
   manipulada (columnas no asignables). Activarse NO marca la cédula como
   verificada.
6. **Planes alimenticios.** Objetivo e indicaciones del plan, horario e
   indicación por comida (editable), duplicar el plan para el mismo u otro
   paciente propio (opción de finalizar el anterior), búsqueda por plan,
   objetivo o paciente, y contexto clínico en el constructor (última
   evaluación y última consulta, solo lectura). Los macros del formulario se
   comprueban con 4/4/9 kcal por gramo (solo informativo).
7. **Cerrar sesión.** Era un enlace con `onclick` a un form oculto e icono de
   Bootstrap Icons. Ahora es un botón real dentro de `<form method="POST">`
   con `@csrf`, en un menú con nombre, correo y tipo de cuenta; redirige al
   login con aviso. Las páginas privadas se envían con `Cache-Control:
   no-store`, así que "Atrás" no muestra datos después de salir.
8. **Datos de prueba.** `DemoDataSeeder` (idempotente, no corre en producción).

## Usuarios de prueba (contraseña `NutriLife2026!`, solo local)

| Correo | Escenario |
|---|---|
| esencial@nutrilife.test | Esencial activo · 12 pacientes (1 inactivo) · sin planes |
| pro@nutrilife.test | Pro activo · 10 pacientes · planes alimenticios |
| consultorio@nutrilife.test | Consultorio activo · 14 pacientes · planes |
| prueba@nutrilife.test | Prueba vigente (2 días) · 2 pacientes |
| prueba-vencida@nutrilife.test | Prueba vencida → pantalla de contratación |
| pendiente@nutrilife.test | Cuenta pendiente de activación |
| cancelada@nutrilife.test | Pro cancelado, aún dentro del periodo pagado |
| vencida@nutrilife.test | Paquete pagado ya vencido |

Las fechas son relativas al día en que se ejecuta el seeder. Los alimentos los
carga `FoodSeeder` (ver abajo).

## Catálogo de alimentos (`FoodSeeder`)

`php artisan db:seed --class=FoodSeeder` — 89 alimentos comunes en México en 8
grupos (frutas, verduras, cereales y tubérculos, leguminosas, origen animal,
leche y derivados, grasas y semillas, azúcares). También corre dentro de
`php artisan db:seed` y de `DemoDataSeeder`.

- **Convención de la tabla:** los nutrientes son **por porción**
  (`portion_grams` + `portion_description`, p. ej. 30 g = 1 tortilla); la app
  calcula `valor × gramos / portion_grams`. Los valores se tomaron por 100 g
  (aprox. USDA FoodData Central; queso panela con el equivalente SMAE) y se
  convierten a la porción.
- **Campos llenados:** grupo, nombre (con estado: crudo/cocido/escurrido),
  porción en g y casera, kcal, proteína, lípidos, hidratos, fibra, sodio,
  índice glucémico (solo en 31 con valor publicado; el resto NULL), `source`
  y `active`; alérgenos en `food_allergens` (gluten, leche/lactosa, huevo,
  cacahuate, nueces, pescado, mariscos) si `ClinicalCatalogSeeder` ya corrió.
- **Sin duplicados y sin borrar:** busca por nombre; si existe con la fuente
  del seeder lo actualiza, si es de otra fuente no lo toca. Los alimentos de
  prueba de la 3.1 (`Prueba (promedio SMAE)`) se renombran y corrigen en su
  mismo registro (los planes que los usan no se rompen).
- Son valores de referencia aproximados, no sustituyen un SMAE completo.

## Catálogo de alimentos: página `/alimentos` y diálogo de los planes

Antes no había página de catálogo; solo el diálogo "Agregar alimento" del
plan, que tenía estos fallos:

- **El diálogo completo se desplazaba** (además de su lista): el título, el
  buscador y los botones salían de la vista y quedaba un hueco en blanco.
  Causa: los radios de cada opción, posicionados, estiraban el área
  desplazable del `<dialog>`. Arreglo: la lista es `position: relative` y
  el diálogo `overflow: hidden`; solo la lista se desplaza.
- **La página de atrás se movía** al llegar al final de la lista con la
  rueda. Arreglo: `overscroll-behavior: contain` y bloqueo del scroll de la
  página mientras hay un diálogo abierto (`html.nl-dialog-open`).
- **Fila de grupos con scroll horizontal** que cortaba "Cereales y tubér…"
  y no se podía desplazar con el ratón. Ahora los chips se acomodan en
  varias líneas y en celular es una lista desplegable.
- Se agregó: contador de resultados, "Limpiar búsqueda", P/C/G de cada
  alimento, mensaje sin resultados con acción, y enlace "Ver catálogo
  completo" (otra pestaña). En celular el diálogo usa casi toda la pantalla
  y no abre el teclado de golpe.

**Página nueva `/alimentos`** (menú Nutrición → Catálogo de alimentos; mismo
acceso que Planes alimenticios: Pro, Consultorio y prueba). Solo consulta,
no modifica datos.

- Búsqueda por nombre (sin importar acentos ni orden de palabras), filtro
  por grupo (`food_groups`; en esta BD "categoría" y "grupo" son lo mismo),
  excluir un alérgeno (`food_allergens`) y ordenar por nombre, kcal,
  proteína o fibra. Filtros activos visibles, cada uno se quita con su
  enlace, y "Limpiar filtros".
- Tarjetas con grupo, porción (casera y gramos), kcal y P/C/G por porción;
  "Ver detalle" abre un diálogo con fibra, sodio, índice glucémico,
  alérgenos, fuente y el equivalente por 100 g. Campos vacíos = "Sin dato".
- 24 alimentos por página. Estados: cargando, catálogo vacío, sin
  resultados y error de base de datos (503 con "Reintentar", se registra en
  el log).
- Columnas: 1 en celular, 3 en tablet/laptop, 4 en escritorio ancho.

## Pruebas

`php vendor/bin/phpunit`: 83 pruebas, 513 aserciones, todas OK (incluye
activación, prueba y contratación, dashboard, fechas, estados, cierre de
sesión, planes y doble ejecución del seeder). Además: recorrido de las 21
pantallas del panel en claro/oscuro a 1280 y 390 px (sin errores ni desborde).

## Pendientes / decisiones

- **Panel de administración de usuarios:** no existe. Para ver cuentas
  pendientes: `SELECT name, email, account_status, activates_at FROM users
  WHERE account_status = 'pendiente';`
- **Aviso al habilitarse la cuenta:** no se envía correo (no hay correo
  configurado); la pantalla indica la hora aproximada.
- **Pasarela de pago real** y **verificación real de cédula** (SEP).
- **Límite de pacientes por plan** (`max_patients`): se muestra, no se aplica.
- **Catálogo de alimentos SMAE completo** (hoy 89 alimentos de referencia).
- Las gráficas usan Chart.js desde CDN (como antes).
